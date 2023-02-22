<?php
/**
 * Component group management form.
 *
 * @category   Modules.
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

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access SNMO Groups Management'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/functions_network_components.php';

$id = (int) get_parameter('id');
$sec = (is_metaconsole() === true) ? 'advanced' : 'gmodules';

if ($id) {
    $group = network_components_get_group($id);
    $name = $group['name'];
    $parent = $group['parent'];
} else {
    $name = '';
    $parent = '';
}

$table = new stdClass();
$table->class = 'databox';

if (is_metaconsole() === true) {
    $table->class = 'databox data';
    $table->head[0] = ($id) ? __('Update Group Component') : __('Create Group Component');
    $table->head_colspan[0] = 4;
    $table->headstyle[0] = 'text-align: center';
}

$table->style = [];
$table->style[0] = 'width: 0';
$table->style[1] = 'width: 0';

$table->data = [];
$table->data[0][0] = __('Name');
$table->data[0][1] = __('Parent');
$table->data[1][0] = html_print_input_text('name', $name, '', 0, 255, true, false, false, '', 'w100p');
$table->data[1][1] = html_print_select(
    network_components_get_groups(),
    'parent',
    $parent,
    false,
    __('None'),
    0,
    true,
    false,
    false
);

$manageNcGroupsUrl = 'index.php?sec='.$sec.'&sec2=godmode/modules/manage_nc_groups';

echo '<form method="post" action="'.$manageNcGroupsUrl.'">';
html_print_table($table);

if ($id) {
    html_print_input_hidden('update', 1);
    html_print_input_hidden('id', $id);
    $actionButtonTitle = __('Update');
} else {
    html_print_input_hidden('create', 1);
    $actionButtonTitle = __('Create');
}

$actionButtons = [];

$actionButtons[] = html_print_submit_button(
    $actionButtonTitle,
    'crt',
    false,
    ['icon' => 'wand'],
    true
);

$actionButtons[] = html_print_go_back_button(
    $manageNcGroupsUrl,
    ['button_class' => ''],
    true
);

html_print_action_buttons(
    implode('', $actionButtons),
    [ 'type' => 'form_action']
);

echo '</form>';
