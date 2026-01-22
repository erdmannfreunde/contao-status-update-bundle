<?php

declare(strict_types=1);

namespace ErdmannFreunde\ContaoStatusUpdateBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Displays status updates in the backend dashboard based on date visibility rules.
 */
#[AsHook('getSystemMessages')]
class StatusUpdateSystemMessageListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Get system messages for the backend dashboard.
     */
    public function __invoke(): string
    {
        $this->framework->initialize();

        $currentTime = Date::floorToMinute();
        $currentDate = strtotime(date('Y-m-d 00:00:00', $currentTime));

        // Query status updates with visibility calculation
        $updates = $this->connection->fetchAllAssociative(
            'SELECT * FROM tl_status_update WHERE published = 1 ORDER BY date DESC'
        );

        $messages = [];

        foreach ($updates as $update) {
            $eventDate = (int) $update['date'];
            $showDaysBefore = $update['show_days_before'];
            $showDaysAfter = $update['show_days_after'];

            if ($this->shouldDisplayUpdate($currentDate, $eventDate, $showDaysBefore, $showDaysAfter)) {
                $messages[] = $this->formatMessage($update, $currentDate, $eventDate);
            }
        }

        return implode('', $messages);
    }

    /**
     * Determine if a status update should be displayed based on visibility rules.
     */
    private function shouldDisplayUpdate(
        int $currentDate,
        int $eventDate,
        string $showDaysBefore,
        string $showDaysAfter
    ): bool {
        $daysDiff = (int) (($eventDate - $currentDate) / 86400); // 86400 = seconds in a day

        // Event is in the future or today
        if ($daysDiff >= 0) {
            return match($showDaysBefore) {
                '7_days' => $daysDiff <= 7,
                '10_days' => $daysDiff <= 10,
                '30_days' => $daysDiff <= 30,
                'only_on_event_day' => $daysDiff === 0,
                default => false,
            };
        }

        // Event is in the past
        $daysAgo = abs($daysDiff);

        return match($showDaysAfter) {
            'only_on_event_day' => false, // Already past, don't show
            '1_day' => $daysAgo <= 1,
            '7_days' => $daysAgo <= 7,
            default => false,
        };
    }

    /**
     * Format the status update message for display in backend.
     */
    private function formatMessage(array $update, int $currentDate, int $eventDate): string
    {
        $title = htmlspecialchars($update['title']);
        $description = $update['description'] ?? '';
        $formattedDate = date('d.m.Y', $eventDate);

        // Determine message type based on timing
        $messageClass = 'tl_info';
        $daysDiff = (int) (($eventDate - $currentDate) / 86400);

        if ($daysDiff < 0) {
            $messageClass = 'tl_confirm'; // Past event - green
        } elseif ($daysDiff === 0) {
            $messageClass = 'tl_error'; // Today - red/important
        } elseif ($daysDiff <= 3) {
            $messageClass = 'tl_new'; // Soon - yellow/warning
        }

        $html = sprintf(
            '<div class="%s"><p><strong>%s: %s</strong></p>%s</div>',
            $messageClass,
            $formattedDate,
            $title,
            $description ? '<div>' . $description . '</div>' : ''
        );

        return $html;
    }
}
