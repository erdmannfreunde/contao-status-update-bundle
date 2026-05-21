<?php

declare(strict_types=1);

namespace ErdmannFreunde\ContaoStatusUpdateBundle\Mailer;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sends a notification mail when a status update transitions to "completed".
 */
class NotificationMailer
{
    public function __construct(
        private readonly Connection $connection,
        private readonly MailerInterface $mailer,
        private readonly InsertTagParser $insertTagParser,
        private readonly Security $security,
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function sendForUpdate(int $updateId): void
    {
        $settings = $this->connection->fetchAssociative(
            'SELECT * FROM tl_status_update_settings WHERE id = 1'
        );

        if (!$settings || empty($settings['enable_notifications'])) {
            return;
        }

        $update = $this->connection->fetchAssociative(
            'SELECT * FROM tl_status_update WHERE id = ?',
            [$updateId]
        );

        if (!$update) {
            return;
        }

        $recipients = $this->collectRecipients($settings);

        if ($recipients === []) {
            return;
        }

        $this->framework->initialize();

        [$subject, $body] = $this->renderMessage($settings, $update);

        $email = (new Email())
            ->from($this->getFromAddress())
            ->subject($subject)
            ->html($body)
            ->text(trim(strip_tags($body)));

        foreach ($recipients as $address) {
            $email->addTo($address);
        }

        $this->mailer->send($email);
    }

    private function getFromAddress(): string
    {
        $this->framework->initialize();
        $config = $this->framework->getAdapter(Config::class);

        $address = (string) $config->get('adminEmail');

        return $address !== '' ? $address : 'no-reply@localhost';
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return list<string>
     */
    private function collectRecipients(array $settings): array
    {
        $emails = [];

        $userIds = array_filter(array_map('intval', StringUtil::deserialize($settings['notification_recipients'] ?? null, true)));

        if ($userIds !== []) {
            $rows = $this->connection->fetchFirstColumn(
                'SELECT email FROM tl_user WHERE id IN (?)',
                [$userIds],
                [ArrayParameterType::INTEGER]
            );
            foreach ($rows as $address) {
                if (\is_string($address) && filter_var($address, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $address;
                }
            }
        }

        $current = $this->security->getUser();
        if ($current instanceof BackendUser && filter_var($current->email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $current->email;
        }

        return array_values(array_unique($emails));
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $update
     *
     * @return array{0: string, 1: string}
     */
    private function renderMessage(array $settings, array $update): array
    {
        // Parse insert tags on the admin-controlled template FIRST, so that
        // any insert-tag-like content inside the status update (title /
        // description) cannot be smuggled into the parser via token
        // substitution.
        $subjectTemplate = $this->insertTagParser->replaceInline(
            (string) ($settings['notification_subject'] ?? '')
        );
        $bodyTemplate = $this->insertTagParser->replace(
            (string) ($settings['notification_body'] ?? '')
        );

        $tokens = [
            '##title##' => (string) ($update['title'] ?? ''),
            '##description##' => (string) ($update['description'] ?? ''),
            '##date##' => date('d.m.Y', (int) ($update['date'] ?? 0)),
        ];

        $subject = strtr($subjectTemplate, $tokens);
        $body = strtr($bodyTemplate, $tokens);

        if ($subject === '') {
            $subject = $this->translator->trans(
                'tl_status_update_settings.notification_subject_default',
                ['%title%' => $update['title'] ?? ''],
                'contao_tl_status_update_settings'
            );
        }

        return [$subject, $body];
    }
}
