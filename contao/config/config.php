<?php

use ErdmannFreunde\ContaoStatusUpdateBundle\Model\StatusUpdateModel;

// Backend module
$GLOBALS['BE_MOD']['system']['status_updates'] = [
    'tables' => ['tl_status_update'],
];

// Model
$GLOBALS['TL_MODELS']['tl_status_update'] = StatusUpdateModel::class;

// Backend CSS
$GLOBALS['TL_CSS'][] = 'bundles/erdmannfreundecontaostatusupdate/css/backend_status_messages.css';
