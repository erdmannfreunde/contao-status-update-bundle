<?php

use Contao\System;
use ErdmannFreunde\ContaoStatusUpdateBundle\Model\StatusUpdateModel;

// Backend module
$GLOBALS['BE_MOD']['system']['status_updates'] = [
    'tables' => ['tl_status_update'],
];

// Model
$GLOBALS['TL_MODELS']['tl_status_update'] = StatusUpdateModel::class;

// Backend CSS (nur im Backend laden)
$request = System::getContainer()->get('request_stack')->getCurrentRequest();
$scopeMatcher = System::getContainer()->get('contao.routing.scope_matcher');

if (null !== $request && $scopeMatcher->isBackendRequest($request)) {
    $GLOBALS['TL_CSS'][] = 'bundles/erdmannfreundecontaostatusupdate/css/backend_status_messages.css';
}
