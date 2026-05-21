<?php

use Contao\Backend;
use Contao\Controller;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;

$GLOBALS['TL_DCA']['tl_status_update_settings'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'closed' => true,
        'notCopyable' => true,
        'notDeletable' => true,
        'notCreatable' => true,
        'onload_callback' => [
            ['tl_status_update_settings', 'ensureSingletonRow'],
            ['tl_status_update_settings', 'redirectToEdit'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['id'],
            'panelLayout' => '',
        ],
        'label' => [
            'fields' => ['id'],
            'format' => '%s',
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
        ],
    ],

    'palettes' => [
        '__selector__' => ['enable_notifications'],
        'default' => '{notification_legend},enable_notifications',
    ],

    'subpalettes' => [
        'enable_notifications' => 'notification_subject,notification_body,notification_recipients',
    ],

    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'enable_notifications' => [
            'label' => &$GLOBALS['TL_LANG']['tl_status_update_settings']['enable_notifications'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'notification_subject' => [
            'label' => &$GLOBALS['TL_LANG']['tl_status_update_settings']['notification_subject'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'clr long'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'notification_body' => [
            'label' => &$GLOBALS['TL_LANG']['tl_status_update_settings']['notification_body'],
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'helpwizard' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'sql' => "mediumtext NULL",
        ],
        'notification_recipients' => [
            'label' => &$GLOBALS['TL_LANG']['tl_status_update_settings']['notification_recipients'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_user.name',
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => "blob NULL",
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ],
    ],
];

class tl_status_update_settings extends Backend
{
    /**
     * Make sure exactly one settings row with id=1 exists.
     */
    public function ensureSingletonRow(?DataContainer $dc = null): void
    {
        $connection = System::getContainer()->get('database_connection');
        \assert($connection instanceof Connection);

        $exists = (bool) $connection->fetchOne('SELECT id FROM tl_status_update_settings WHERE id = 1');

        if (!$exists) {
            $connection->executeStatement(
                'INSERT IGNORE INTO tl_status_update_settings (id, tstamp) VALUES (1, ?)',
                [time()]
            );
        }
    }

    /**
     * Redirect the listing view directly to the edit form for id=1.
     */
    public function redirectToEdit(?DataContainer $dc = null): void
    {
        if (Input::get('act') || Input::get('key')) {
            return;
        }

        Controller::redirect(Backend::addToUrl('act=edit&id=1'));
    }
}
