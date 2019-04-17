<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
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
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Group Management'
    );
    include 'general/noaccess.php';
    return;
}

if (is_ajax()) {
    $get_group_json = (bool) get_parameter('get_group_json');
    $get_group_agents = (bool) get_parameter('get_group_agents');

    if ($get_group_json) {
        $id_group = (int) get_parameter('id_group');

        if (! check_acl($config['id_user'], $id_group, 'AR')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access Alert Management'
            );
            echo json_encode(false);
            return;
        }

        $group = db_get_row('tmodule_group', 'id_mg', $id_group);

        echo json_encode($group);
        return;
    }

    return;
}

if (!is_metaconsole()) {
    // Header.
    ui_print_page_header(
        __('Module groups defined in %s', get_product_name()),
        'images/module_group.png',
        false,
        '',
        true,
        ''
    );
}

$create_group = (bool) get_parameter('create_group');
$update_group = (bool) get_parameter('update_group');
$delete_group = (bool) get_parameter('delete_group');

// Create group.
if ($create_group) {
    $name = (string) get_parameter('name');
    $icon = (string) get_parameter('icon');
    $id_parent = (int) get_parameter('id_parent');
    $alerts_disabled = (bool) get_parameter('alerts_disabled');
    $custom_id = (string) get_parameter('custom_id');
    $check = db_get_value('name', 'tmodule_group', 'name', $name);

    if ($name) {
        if (!$check) {
            $result = db_process_sql_insert(
                'tmodule_group',
                ['name' => $name]
            );

            if ($result) {
                ui_print_success_message(__('Group successfully created'));
            } else {
                ui_print_error_message(
                    __('There was a problem creating group')
                );
            }
        } else {
            ui_print_error_message(
                __('Each module group must have a different name')
            );
        }
    } else {
        ui_print_error_message(__('Module group must have a name'));
    }
}

// Update group.
if ($update_group) {
    $id_group = (int) get_parameter('id_group');
    $name = (string) get_parameter('name');
    $icon = (string) get_parameter('icon');
    $id_parent = (int) get_parameter('id_parent');
    $alerts_enabled = (bool) get_parameter('alerts_enabled');
    $custom_id = (string) get_parameter('custom_id');
    $check = db_get_value('name', 'tmodule_group', 'name', $name);
    $subcheck = db_get_value('name', 'tmodule_group', 'id_mg', $id_group);

    if ($name) {
        if (!$check || $subcheck == $name) {
            $result = db_process_sql_update(
                'tmodule_group',
                ['name' => $name],
                ['id_mg' => $id_group]
            );

            if ($result !== false) {
                ui_print_success_message(__('Group successfully updated'));
            } else {
                ui_print_error_message(
                    __('There was a problem modifying group')
                );
            }
        } else {
            ui_print_error_message(
                __('Each module group must have a different name')
            );
        }
    } else {
        ui_print_error_message(__('Module group must have a name'));
    }
}

// Delete group.
if ($delete_group) {
    $id_group = (int) get_parameter('id_group');

    $result = db_process_sql_delete('tmodule_group', ['id_mg' => $id_group]);

    if ($result) {
        $result = db_process_sql_update(
            'tagente_modulo',
            ['id_module_group' => 0],
            ['id_module_group' => $id_group]
        );
        db_process_sql_update(
            'tpolicy_modules',
            ['id_module_group' => 0],
            ['id_module_group' => $id_group]
        );
        db_process_sql_update(
            'tcontainer_item',
            ['id_module_group' => 0],
            ['id_module_group' => $id_group]
        );
        db_process_sql_update(
            'tnetwork_component',
            ['id_module_group' => 0],
            ['id_module_group' => $id_group]
        );
        db_process_sql_update(
            'treport_content',
            ['id_module_group' => 0],
            ['id_module_group' => $id_group]
        );
        db_process_sql_update(
            'tnetwork_map',
            ['id_module_group' => 0],
            ['id_module_group' => $id_group]
        );
        db_process_sql_update(
            'tlocal_component',
            ['id_module_group' => 0],
            ['id_module_group' => $id_group]
        );
        db_process_sql_update(
            'treport_content_template',
            ['id_module_group' => 0],
            ['id_module_group' => $id_group]
        );

        // A group with no modules can be deleted,
        // to avoid a message error then do the follwing.
        if ($result !== false) {
            $result = true;
        }
    }

    if (! $result) {
        ui_print_error_message(__('There was a problem deleting group'));
    } else {
        ui_print_success_message(__('Group successfully deleted'));
    }
}

// Prepare pagination.
$total_groups = db_get_num_rows('SELECT * FROM tmodule_group');
$url = ui_get_url_refresh(['offset' => false]);
$offset = (int) get_parameter('offset', 0);


$sql = 'SELECT *
    FROM tmodule_group
    ORDER BY name ASC
    LIMIT '.$offset.', '.$config['block_size'];

$groups = db_get_all_rows_sql($sql);

$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table';

if (!empty($groups)) {
    $table->head = [];
    $table->head[0] = __('ID');
    $table->head[1] = __('Name');
    $table->head[2] = __('Delete');
    $table->align = [];
    $table->align[1] = 'left';
    $table->align[2] = 'left';
    $table->size[2] = '5%';
    $table->data = [];

    foreach ($groups as $id_group) {
        $data = [];
        $data[0] = $id_group['id_mg'];

        $data[1] = '<strong><a href="index.php?sec=gmodules&sec2=godmode/groups/configure_modu_group&id_group='.$id_group['id_mg'].'">'.ui_print_truncate_text($id_group['name'], GENERIC_SIZE_TEXT).'</a></strong>';
        if (is_metaconsole()) {
            $data[2] = '<a href="index.php?sec=advanced&sec2=advanced/component_management&tab=module_group&id_group='.$id_group['id_mg'].'&delete_group=1" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image('images/cross.png', true, ['border' => '0']).'</a>';
        } else {
            $table->cellclass[][2] = 'action_buttons';
            $data[2] = '<a href="index.php?sec=gmodules&sec2=godmode/groups/modu_group_list&id_group='.$id_group['id_mg'].'&delete_group=1" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image('images/cross.png', true, ['border' => '0']).'</a>';
        }

        array_push($table->data, $data);
    }

    ui_pagination($total_groups, $url, $offset);
    html_print_table($table);
    ui_pagination($total_groups, $url, $offset, 0, false, 'offset', true, 'pagination-bottom');
} else {
    ui_print_info_message(
        [
            'no_close' => true,
            'message'  => __('There are no defined module groups'),
        ]
    );
}

echo '<form method="post" action="index.php?sec=gmodules&sec2=godmode/groups/configure_modu_group">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button(
    __('Create module group'),
    'crt',
    false,
    'class="sub next"'
);
echo '</div>';
echo '</form>';
