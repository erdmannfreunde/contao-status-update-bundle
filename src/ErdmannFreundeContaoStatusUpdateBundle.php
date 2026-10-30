<?php

declare(strict_types=1);

/*
 * This file is part of erdmannfreunde/contao-status-update-bundle.
 *
 * (c) Erdmann & Freunde <https://erdmann-freunde.de>
 *
 * @license LGPL-3.0-or-later
 */

namespace ErdmannFreunde\ContaoStatusUpdateBundle;

use ErdmannFreunde\ContaoStatusUpdateBundle\DependencyInjection\ErdmannFreundeContaoStatusUpdateExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ErdmannFreundeContaoStatusUpdateBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ErdmannFreundeContaoStatusUpdateExtension
    {
        return new ErdmannFreundeContaoStatusUpdateExtension();
    }
}
