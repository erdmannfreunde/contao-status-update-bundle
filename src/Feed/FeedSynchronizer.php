<?php

declare(strict_types=1);

namespace ErdmannFreunde\ContaoStatusUpdateBundle\Feed;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;
use Contao\InputEncodingMode;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use ErdmannFreunde\ContaoStatusUpdateBundle\Mailer\NotificationMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Mirrors the items of a subscribed status update feed into tl_status_update.
 *
 * The feed content ends up as HTML in the backend dashboard, so everything
 * coming from the remote side is treated as untrusted: strict validation,
 * an HTML allowlist, encoded insert tags and a fixed backend scope.
 */
class FeedSynchronizer
{
    private const FEED_VERSION = 1;
    private const MAX_BYTES = 524288;
    private const MAX_ITEMS = 50;

    /**
     * Allowed options per field; the first entry is the DCA default.
     */
    private const DAY_OPTIONS = [
        'show_days_before' => ['7_days', '10_days', '30_days', 'only_on_event_day'],
        'show_days_after' => ['only_on_event_day', '1_day', '7_days', '30_days'],
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly NotificationMailer $mailer,
        #[Autowire(service: 'monolog.logger.contao.cron')]
        private readonly LoggerInterface $cronLogger,
        #[Autowire(service: 'monolog.logger.contao.error')]
        private readonly LoggerInterface $errorLogger,
        private readonly string|null $feedUrl = null,
    ) {
    }

    public function sync(): void
    {
        if (null === $this->feedUrl) {
            return;
        }

        // Sets the configured time zone, which the feed dates are parsed in
        $this->framework->initialize();

        try {
            $items = $this->fetchItems($this->feedUrl);
        } catch (ExceptionInterface|\JsonException|\UnexpectedValueException $e) {
            $this->errorLogger->error(\sprintf('Status-Update-Feed "%s" konnte nicht synchronisiert werden: %s', $this->feedUrl, $e->getMessage()));

            return;
        }

        $host = (string) parse_url($this->feedUrl, PHP_URL_HOST);
        $sanitizer = $this->createSanitizer();

        [$inserted, $updated, $completedIds, $deleted] = $this->connection->transactional(
            function () use ($items, $host, $sanitizer): array {
                $existing = $this->connection->fetchAllAssociativeIndexed(
                    "SELECT feed_uid, id, feed_updated, completed FROM tl_status_update WHERE feed_uid != ''"
                );

                $inserted = $updated = 0;
                $uids = $completedIds = [];

                foreach ($items as $item) {
                    $uid = $host.':'.$item['id'];
                    $uids[] = $uid;

                    $data = [
                        'tstamp' => time(),
                        'title' => $this->encodeInput(mb_substr(trim($item['title']), 0, 255), InputEncodingMode::encodeAll),
                        'description' => $this->encodeInput($sanitizer->sanitize($item['description']), InputEncodingMode::encodeNone),
                        'date' => $item['date'],
                        'show_days_before' => $this->dayOption('show_days_before', $item['show_days_before']),
                        'show_days_after' => $this->dayOption('show_days_after', $item['show_days_after']),
                        'scope' => 'backend',
                        'feed_updated' => $item['updated'],
                    ];

                    // Local fields (published, completed, notification_sent) are only set on insert.
                    // An item that arrives already completed was never shown as open here, so no mail.
                    if (!isset($existing[$uid])) {
                        $this->connection->insert('tl_status_update', $data + [
                            'feed_uid' => $uid,
                            'published' => 1,
                            'completed' => (int) $item['completed'],
                            'notification_sent' => (int) $item['completed'],
                        ]);
                        ++$inserted;
                        continue;
                    }

                    $local = $existing[$uid];

                    if ($item['updated'] > (int) $local['feed_updated']) {
                        $this->connection->update('tl_status_update', $data, ['feed_uid' => $uid]);
                        ++$updated;
                    }

                    // Completion is only ever set by the feed, never reset
                    if ($item['completed'] && !$local['completed']) {
                        $this->connection->update('tl_status_update', ['completed' => 1], ['id' => $local['id']]);
                        $completedIds[] = (int) $local['id'];
                    }
                }

                // Items leave the feed only after their display window has ended
                // or when they were withdrawn, so a missing item can be removed.
                if ([] === $uids) {
                    $deleted = $this->connection->executeStatement("DELETE FROM tl_status_update WHERE feed_uid != ''");
                } else {
                    $deleted = $this->connection->executeStatement(
                        "DELETE FROM tl_status_update WHERE feed_uid != '' AND feed_uid NOT IN (?)",
                        [$uids],
                        [ArrayParameterType::STRING]
                    );
                }

                return [$inserted, $updated, $completedIds, (int) $deleted];
            }
        );

        foreach ($completedIds as $id) {
            $this->sendCompletionMail($id);
        }

        if ($inserted + $updated + \count($completedIds) + $deleted > 0) {
            $this->cronLogger->info(\sprintf('Status-Update-Feed "%s" synchronisiert: %d neu, %d aktualisiert, %d abgeschlossen, %d entfernt.', $this->feedUrl, $inserted, $updated, \count($completedIds), $deleted));
        }
    }

    /**
     * Same "exactly once" behaviour as completing an update in the backend
     * (see SendCompletionMailListener).
     */
    private function sendCompletionMail(int $id): void
    {
        if (1 === (int) $this->connection->fetchOne('SELECT notification_sent FROM tl_status_update WHERE id = ?', [$id])) {
            return;
        }

        try {
            $this->mailer->sendForUpdate($id);
        } catch (TransportExceptionInterface $e) {
            $this->errorLogger->error(\sprintf('Abschluss-Mail für Status-Update ID %d konnte nicht versendet werden: %s', $id, $e->getMessage()));

            return;
        }

        $this->connection->executeStatement('UPDATE tl_status_update SET notification_sent = 1 WHERE id = ?', [$id]);
    }

    /**
     * @return array<int, array{id: int, updated: int, title: string, description: string, date: int, show_days_before: string, show_days_after: string, completed: bool}>
     */
    private function fetchItems(string $url): array
    {
        $response = $this->httpClient->request('GET', $url, [
            'timeout' => 5,
            'max_duration' => 10,
            'max_redirects' => 0,
            'headers' => ['Accept' => 'application/json'],
            'on_progress' => static function (int $downloaded): void {
                if ($downloaded > self::MAX_BYTES) {
                    throw new \UnexpectedValueException('Antwort ist größer als erlaubt.');
                }
            },
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new \UnexpectedValueException(\sprintf('Unerwarteter HTTP-Status %d.', $response->getStatusCode()));
        }

        $content = $response->getContent();

        if (\strlen($content) > self::MAX_BYTES) {
            throw new \UnexpectedValueException('Antwort ist größer als erlaubt.');
        }

        $data = json_decode($content, true, 16, JSON_THROW_ON_ERROR);

        if (!\is_array($data) || self::FEED_VERSION !== ($data['version'] ?? null) || !\is_array($data['items'] ?? null) || !array_is_list($data['items'])) {
            throw new \UnexpectedValueException('Unbekanntes Feed-Format.');
        }

        if (\count($data['items']) > self::MAX_ITEMS) {
            throw new \UnexpectedValueException(\sprintf('Mehr als %d Einträge im Feed.', self::MAX_ITEMS));
        }

        $items = [];

        foreach ($data['items'] as $index => $item) {
            if (
                !\is_array($item)
                || !\is_int($item['id'] ?? null)
                || !\is_int($item['updated'] ?? null)
                || !\is_string($item['title'] ?? null)
                || '' === trim($item['title'])
                || !\is_string($item['date'] ?? null)
            ) {
                throw new \UnexpectedValueException(\sprintf('Eintrag %d ist unvollständig.', $index));
            }

            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $item['date']);

            if (false === $date || $date->format('Y-m-d') !== $item['date']) {
                throw new \UnexpectedValueException(\sprintf('Eintrag %d hat ein ungültiges Datum.', $index));
            }

            $items[$item['id']] = [
                'id' => $item['id'],
                'updated' => $item['updated'],
                'title' => $item['title'],
                'description' => \is_string($item['description'] ?? null) ? $item['description'] : '',
                // Interpreted as local midnight, like dates entered in the backend
                'date' => $date->getTimestamp(),
                'show_days_before' => \is_string($item['show_days_before'] ?? null) ? $item['show_days_before'] : '',
                'show_days_after' => \is_string($item['show_days_after'] ?? null) ? $item['show_days_after'] : '',
                'completed' => true === ($item['completed'] ?? false),
            ];
        }

        return $items;
    }

    private function createSanitizer(): HtmlSanitizer
    {
        $config = (new HtmlSanitizerConfig())
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('a', ['href'])
            ->allowLinkSchemes(['https', 'mailto'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(self::MAX_BYTES)
        ;

        // Keep the text of common wrappers, drop everything else (incl. images)
        foreach (['div', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $element) {
            $config = $config->blockElement($element);
        }

        return new HtmlSanitizer($config);
    }

    /**
     * Same encoding DC_Table applies to backend input (incl. insert tags).
     */
    private function encodeInput(string $value, InputEncodingMode $mode): string
    {
        return $this->framework->getAdapter(Input::class)->encodeInput($value, $mode);
    }

    private function dayOption(string $field, string $value): string
    {
        return \in_array($value, self::DAY_OPTIONS[$field], true) ? $value : self::DAY_OPTIONS[$field][0];
    }
}
