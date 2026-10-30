<?php

declare(strict_types=1);

use Contao\EasyCodingStandard\Set\SetList;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withSets([SetList::CONTAO])
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/contao',
    ])
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' => "This file is part of erdmannfreunde/contao-status-update-bundle.\n\n(c) Erdmann & Freunde <https://erdmann-freunde.de>\n\n@license LGPL-3.0-or-later",
    ])
    // Serial is faster than parallel on a project this size, and a fixer that
    // blows up reports once instead of once per worker.
    ->withoutParallel()
;
