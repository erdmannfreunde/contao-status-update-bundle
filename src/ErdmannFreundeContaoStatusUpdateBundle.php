<?php

declare(strict_types=1);

namespace ErdmannFreunde\ContaoStatusUpdateBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ErdmannFreundeContaoStatusUpdateBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
