<?php

declare(strict_types=1);

/*
 * This file is part of erdmannfreunde/contao-status-update-bundle.
 *
 * (c) Erdmann & Freunde <https://erdmann-freunde.de>
 *
 * @license LGPL-3.0-or-later
 */

namespace ErdmannFreunde\ContaoStatusUpdateBundle\Model;

use Contao\Model;

/**
 * @property int    $id
 * @property int    $tstamp
 * @property bool   $enable_notifications
 * @property string $notification_subject
 * @property string $notification_body
 * @property string $notification_recipients
 */
class StatusUpdateSettingsModel extends Model
{
    protected static $strTable = 'tl_status_update_settings';
}
