<?php
/**
 * Calendar: edit page
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Alert
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Extras required.
ui_require_css_file('wizard');

// Header.
\ui_print_page_header(
    // Title.
    __('Calendars Edit'),
    // Icon.
    'images/gm_alerts.png',
    // Return.
    false,
    // Help.
    'alert_special_days',
    // Godmode.
    true,
    // Options.
    $tabs
);

if (empty($message) === false) {
    echo $message;
}

$inputs = [];

// Name.
$inputs[] = [
    'label'     => __('Name'),
    'arguments' => [
        'type'     => 'text',
        'name'     => 'name',
        'required' => true,
        'value'    => $calendar->name(),
    ],
];

// Group.
$inputs[] = [
    'label'     => __('Group'),
    'arguments' => [
        'type'           => 'select_groups',
        'returnAllGroup' => true,
        'name'           => 'id_group',
        'selected'       => $calendar->id_group(),
    ],
];

// Description.
$inputs[] = [
    'label'     => __('Description'),
    'arguments' => [
        'type'     => 'textarea',
        'name'     => 'description',
        'required' => false,
        'value'    => $calendar->description(),
        'rows'     => 50,
        'columns'  => 30,
    ],
];


// Submit.
$inputs[] = [
    'arguments' => [
        'name'       => 'button',
        'label'      => (($create === true) ? __('Create') : __('Update')),
        'type'       => 'submit',
        'attributes' => 'class="sub next"',
    ],
];

// Print form.
HTML::printForm(
    [
        'form'   => [
            'action' => $url.'&op=edit&action=save&id='.$calendar->id(),
            'method' => 'POST',
        ],
        'inputs' => $inputs,
    ],
    false,
    true
);
