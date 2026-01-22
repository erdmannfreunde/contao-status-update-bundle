<?php

declare(strict_types=1);

namespace ErdmannFreunde\ContaoStatusUpdateBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use ErdmannFreunde\ContaoStatusUpdateBundle\ErdmannFreundeContaoStatusUpdateBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ErdmannFreundeContaoStatusUpdateBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
