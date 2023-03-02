<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Group Management2'
    );
    include 'general/noaccess.php';
    return;
}

if (is_metaconsole() === false) {
    // Header
    ui_print_standard_header(
        __('Module group management'),
        'images/module_group.png',
        false,
        '',
        true,
        [],
        [
            [
                'link'  => '',
                'label' => __('Resources'),
            ],
            [
                'link'  => '',
                'label' => __('Module groups'),
            ],
        ]
    );
}

// Init vars
$icon = '';
$name = '';
$id_parent = 0;
$alerts_disabled = 0;
$custom_id = '';

$create_group = (bool) get_parameter('create_group');
$id_group = (int) get_parameter('id_group');
$offset = (int) get_parameter('offset', 0);

if ($id_group) {
    $group = db_get_row('tmodule_group', 'id_mg', $id_group);
    if ($group) {
        $name = $group['name'];
    } else {
        ui_print_error_message(__('There was a problem loading group'));
        echo '</table>';
        echo '</div>';
        echo '<div id="both">&nbsp;</div>';
        echo '</div>';
        echo '<div id="foot">';
        // include 'general/footer.php';
        echo '</div>';
        echo '</div>';
        exit;
    }
}

$table = new stdClass();
$table->class = 'databox';
$table->style[0] = 'font-weight: bold';
$table->data = [];
$table->data[0][0] = __('Name');
$table->data[1][0] = html_print_input_text('name', $name, '', 35, 100, true);


echo '</span>';
if (is_metaconsole() === true) {
    $formUrl = 'index.php?sec=advanced&sec2=advanced/component_management&tab=module_group&offset='.$offset;
} else {
    $formUrl = 'index.php?sec=gmodules&sec2=godmode/groups/modu_group_list&offset='.$offset;
}

echo '<form name="grupo" method="POST" action="'.$formUrl.'">';
html_print_table($table);

if ($id_group) {
    html_print_input_hidden('update_group', 1);
    html_print_input_hidden('id_group', $id_group);
    $actionButtonTitle = __('Update');
    $actionButtonName = 'updbutton';
} else {
    $actionButtonTitle = __('Create');
    $actionButtonName = 'crtbutton';
    html_print_input_hidden('create_group', 1);
}

$actionButtons = [];

$actionButtons[] = html_print_submit_button(
    $actionButtonTitle,
    $actionButtonName,
    false,
    ['icon' => 'wand'],
    true
);

$actionButtons[] = html_print_go_back_button(
    ui_get_full_url('index.php?sec=gmodules&sec2=godmode/groups/modu_group_list'),
    ['button_class' => ''],
    true
);

html_print_action_buttons(
    implode('', $actionButtons),
    ['type' => 'form_action']
);
echo '</form>';
