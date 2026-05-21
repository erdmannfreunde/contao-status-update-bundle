<?php

use Contao\System;
use ErdmannFreunde\ContaoStatusUpdateBundle\Model\StatusUpdateModel;
use ErdmannFreunde\ContaoStatusUpdateBundle\Model\StatusUpdateSettingsModel;

// Backend module
$GLOBALS['BE_MOD']['system']['status_updates'] = [
    'tables' => ['tl_status_update', 'tl_status_update_settings'],
];

// Models
$GLOBALS['TL_MODELS']['tl_status_update'] = StatusUpdateModel::class;
$GLOBALS['TL_MODELS']['tl_status_update_settings'] = StatusUpdateSettingsModel::class;

// Backend CSS (nur im Backend laden)
$request = System::getContainer()->get('request_stack')->getCurrentRequest();
$scopeMatcher = System::getContainer()->get('contao.routing.scope_matcher');

if (null !== $request && $scopeMatcher->isBackendRequest($request)) {
    $GLOBALS['TL_CSS'][] = 'bundles/erdmannfreundecontaostatusupdate/css/backend_status_messages.css';
}
