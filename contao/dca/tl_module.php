<?php

declare(strict_types=1);

/*
 * This file is part of erdmannfreunde/contao-status-update-bundle.
 *
 * (c) Erdmann & Freunde <https://erdmann-freunde.de>
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_module']['palettes']['status_updates'] =
    '{title_legend},name,headline,type'
    .';{template_legend:hide},customTpl'
    .';{protected_legend:hide},protected'
    .';{expert_legend:hide},guests,cssID';
