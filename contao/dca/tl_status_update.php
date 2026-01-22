<?php

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_status_update'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'markAsCopy' => 'title',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
                'date' => 'index',
                'published' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['date DESC'],
            'flag' => DataContainer::SORT_DAY_DESC,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['title', 'date'],
            'format' => '%s <span class="label-info">[%s]</span>',
            'label_callback' => ['tl_status_update', 'formatLabel'],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'href' => 'act=toggle&amp;field=published',
                'icon' => 'visible.svg',
                'showInHeader' => true,
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '{title_legend},title,description;{date_legend},date,show_days_before,show_days_after;{publish_legend},published',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_status_update']['title'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'description' => [
            'label' => &$GLOBALS['TL_LANG']['tl_status_update']['description'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'helpwizard' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'sql' => "mediumtext NULL",
        ],
        'date' => [
            'label' => &$GLOBALS['TL_LANG']['tl_status_update']['date'],
            'exclude' => true,
            'default' => time(),
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_DAY_DESC,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'show_days_before' => [
            'label' => &$GLOBALS['TL_LANG']['tl_status_update']['show_days_before'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => ['7_days', '10_days', '30_days', 'only_on_event_day'],
            'reference' => &$GLOBALS['TL_LANG']['tl_status_update']['show_days_before_options'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default '7_days'",
        ],
        'show_days_after' => [
            'label' => &$GLOBALS['TL_LANG']['tl_status_update']['show_days_after'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => ['only_on_event_day', '1_day', '7_days'],
            'reference' => &$GLOBALS['TL_LANG']['tl_status_update']['show_days_after_options'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default 'only_on_event_day'",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_status_update']['published'],
            'exclude' => true,
            'toggle' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ],
];

/**
 * Provide miscellaneous methods for tl_status_update DCA.
 */
class tl_status_update extends Backend
{
    /**
     * Format the list label with colored date indicator.
     */
    public function formatLabel(array $row, string $label): string
    {
        $datum = date('d.m.Y', (int) $row['date']);
        $today = strtotime(date('Y-m-d 00:00:00'));
        $eventDate = (int) $row['date'];

        $colorClass = '';
        if ($eventDate < $today) {
            $colorClass = 'style="color: #999;"'; // Past - gray
        } elseif ($eventDate === $today) {
            $colorClass = 'style="color: #c33;"'; // Today - red
        } elseif ($eventDate <= $today + (3 * 86400)) {
            $colorClass = 'style="color: #f90;"'; // Within 3 days - orange
        }

        return sprintf(
            '%s <span class="label-info" %s>[%s]</span>',
            $row['title'],
            $colorClass,
            $datum
        );
    }
}
