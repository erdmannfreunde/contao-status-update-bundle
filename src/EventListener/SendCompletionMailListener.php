<?php

declare(strict_types=1);

namespace ErdmannFreunde\ContaoStatusUpdateBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use ErdmannFreunde\ContaoStatusUpdateBundle\Mailer\NotificationMailer;

/**
 * Fires when the "completed" field is saved on tl_status_update.
 * Sends a notification mail only on transition 0 -> 1.
 */
#[AsCallback(table: 'tl_status_update', target: 'fields.completed.save')]
class SendCompletionMailListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly NotificationMailer $mailer,
    ) {
    }

    public function __invoke(mixed $value, DataContainer $dc): mixed
    {
        if (empty($value) || !$dc->id) {
            return $value;
        }

        $alreadySent = (int) $this->connection->fetchOne(
            'SELECT notification_sent FROM tl_status_update WHERE id = ?',
            [(int) $dc->id]
        );

        if ($alreadySent === 1) {
            return $value;
        }

        $this->mailer->sendForUpdate((int) $dc->id);

        $this->connection->executeStatement(
            'UPDATE tl_status_update SET notification_sent = 1 WHERE id = ?',
            [(int) $dc->id]
        );

        return $value;
    }
}
