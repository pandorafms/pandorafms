<?php
/**
 * Event responses list view.
 *
 * @category   Events
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

// Load global vars.
global $config;

require_once $config['homedir'].'/include/functions_event_responses.php';

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Group Management'
    );
    include 'general/noaccess.php';
    return;
}

$event_responses = event_responses_get_responses();

if (empty($event_responses)) {
    ui_print_info_message(['no_close' => true, 'message' => __('No responses found') ]);
    $event_responses = [];
    return;
}

$table = new stdClass();
$table->class = 'info_table';
$table->styleTable = 'margin: 10px 10px 0';
$table->cellpadding = 0;
$table->cellspacing = 0;

$table->size = [];
$table->size[0] = '200px';
$table->size[2] = '100px';
$table->size[3] = '70px';

$table->style[2] = 'text-align:left;';

$table->head[0] = __('Name');
$table->head[1] = __('Description');
$table->head[2] = __('Group');
$table->head[3] = __('Actions');

$table->data = [];

foreach ($event_responses as $response) {
    if (!check_acl_restricted_all($config['id_user'], $response['id_group'], 'PM')) {
        continue;
    }

    $data = [];
    $data[0] = '<a href="index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=editor&id_response='.$response['id'].'&amp;pure='.$config['pure'].'">'.$response['name'].'</a>';
    $data[1] = $response['description'];
    $data[2] = ui_print_group_icon($response['id_group'], true);
    $table->cellclass[][3] = 'table_action_buttons';
    $data[3] = html_print_anchor(
        [
            'href'    => 'index.php?sec=geventos&sec2=godmode/events/events&section=responses&action=delete_response&id_response='.$response['id'].'&amp;pure='.$config['pure'],
            'content' => html_print_image(
                'images/delete.svg',
                true,
                [
                    'title' => __('Delete'),
                    'class' => 'invert_filter main_menu_icon',
                ]
            ),
        ],
        true
    );

    $data[3] .= html_print_anchor(
        [
            'href'    => 'index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=editor&id_response='.$response['id'].'&amp;pure='.$config['pure'],
            'content' => html_print_image(
                'images/edit.svg',
                true,
                [
                    'title' => __('Edit'),
                    'class' => 'invert_filter main_menu_icon',
                ]
            ),
        ],
        true
    );
    $table->data[] = $data;
}

html_print_table($table);


echo '<form method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=editor&amp;pure='.$config['pure'].'">';
html_print_action_buttons(
    html_print_submit_button(
        __('Create response'),
        'create_response_button',
        false,
        ['icon' => 'wand'],
        true
    ),
    ['type' => 'form_action']
);
echo '</form>';
