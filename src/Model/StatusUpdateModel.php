<?php

declare(strict_types=1);

namespace ErdmannFreunde\ContaoStatusUpdateBundle\Model;

use Contao\Model;
use Contao\Model\Collection;

/**
 * Status Update Model
 *
 * @property int    $id
 * @property int    $tstamp
 * @property string $title
 * @property string $description
 * @property int    $date
 * @property string $show_days_before
 * @property string $show_days_after
 * @property bool   $published
 */
class StatusUpdateModel extends Model
{
    protected static $strTable = 'tl_status_update';

    /**
     * Find all published status updates ordered by date DESC.
     *
     * @param array<string, mixed> $options
     * @return Collection|StatusUpdateModel|null
     */
    public static function findPublished(array $options = []): Collection|StatusUpdateModel|null
    {
        $t = static::$strTable;
        $columns = ["$t.published=?"];
        $values = [1];

        if (!isset($options['order'])) {
            $options['order'] = "$t.date DESC";
        }

        return static::findBy($columns, $values, $options);
    }

    /**
     * Find visible status updates for a given date.
     *
     * @param int $currentDate
     * @param array<string, mixed> $options
     * @return Collection|StatusUpdateModel|null
     */
    public static function findVisibleForDate(int $currentDate, array $options = []): Collection|StatusUpdateModel|null
    {
        $t = static::$strTable;
        $columns = ["$t.published=?"];
        $values = [1];

        if (!isset($options['order'])) {
            $options['order'] = "$t.date DESC";
        }

        return static::findBy($columns, $values, $options);
    }
}
