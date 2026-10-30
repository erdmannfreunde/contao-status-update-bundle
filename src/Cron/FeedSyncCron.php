<?php

declare(strict_types=1);

/*
 * This file is part of erdmannfreunde/contao-status-update-bundle.
 *
 * (c) Erdmann & Freunde <https://erdmann-freunde.de>
 *
 * @license LGPL-3.0-or-later
 */

namespace ErdmannFreunde\ContaoStatusUpdateBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use ErdmannFreunde\ContaoStatusUpdateBundle\Feed\FeedSynchronizer;

/**
 * Pulls the subscribed status update feed (if configured) once per hour.
 */
#[AsCronJob('hourly')]
class FeedSyncCron
{
    public function __construct(private readonly FeedSynchronizer $synchronizer)
    {
    }

    public function __invoke(): void
    {
        $this->synchronizer->sync();
    }
}
