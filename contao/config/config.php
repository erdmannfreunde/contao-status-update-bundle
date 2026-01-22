<?php

use ErdmannFreunde\ContaoStatusUpdateBundle\Model\StatusUpdateModel;

// Backend module
$GLOBALS['BE_MOD']['content']['status_updates'] = [
    'tables' => ['tl_status_update'],
];

// Model
$GLOBALS['TL_MODELS']['tl_status_update'] = StatusUpdateModel::class;
