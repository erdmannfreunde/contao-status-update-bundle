<?php

declare(strict_types=1);

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
