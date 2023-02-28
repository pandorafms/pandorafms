<?php
/**
 * Module Groups.
 *
 * @category   Module groups
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

global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Group Management'
    );
    include 'general/noaccess.php';
    return;
}

if (is_ajax() === true) {
    $get_group_json = (bool) get_parameter('get_group_json');
    $get_group_agents = (bool) get_parameter('get_group_agents');

    if ($get_group_json === true) {
        $id_group = (int) get_parameter('id_group');

        if (! check_acl($config['id_user'], $id_group, 'AR')) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
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

if (is_metaconsole() === false) {
    // Header.
    ui_print_standard_header(
        __('Module groups list'),
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

$is_management_allowed = true;
if (is_management_allowed() === false) {
    $is_management_allowed = false;
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=advanced/component_management&tab=module_group'
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All module groups information is read only. Go to %s to manage it.',
            $url
        )
    );
}

$create_group = (bool) get_parameter('create_group');
$update_group = (bool) get_parameter('update_group');
$delete_group = (bool) get_parameter('delete_group');

// Create group.
if ($is_management_allowed === true && $create_group === true) {
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
if ($is_management_allowed === true && $update_group === true) {
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
if ($is_management_allowed === true && $delete_group === true) {
    $id_group = (int) get_parameter('id_group');

    $result = db_process_sql_delete('tmodule_group', ['id_mg' => $id_group]);

    if ((bool) $result === true) {
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

    ui_print_result_message(
        $result,
        __('Group successfully deleted'),
        __('There was a problem deleting group')
    );
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
$table->class = 'info_table';

if (empty($groups) === false) {
    $table->head = [];
    $table->head[0] = __('ID');
    $table->head[1] = __('Name');
    if ($is_management_allowed === true) {
        $table->head[2] = __('Delete');
    }

    $table->size[0] = '5%';

    $table->align = [];
    $table->align[1] = 'left';
    if ($is_management_allowed === true) {
        $table->align[2] = 'left';
        $table->size[2] = '5%';
    }

    $table->data = [];
    $offset_delete = ($offset >= $total_groups - 1) ? ($offset - $config['block_size']) : $offset;
    foreach ($groups as $id_group) {
        $data = [];
        $data[0] = $id_group['id_mg'];

        if ($is_management_allowed === true) {
            $data[1] = '<strong><a href="index.php?sec=gmodules&sec2=godmode/groups/configure_modu_group&id_group='.$id_group['id_mg'].'&offset='.$offset.'">'.ui_print_truncate_text($id_group['name'], GENERIC_SIZE_TEXT).'</a></strong>';
            if (is_metaconsole() === true) {
                $data[2] = '<a href="index.php?sec=advanced&sec2=advanced/component_management&tab=module_group&id_group='.$id_group['id_mg'].'&delete_group=1&offset='.$offset_delete.'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image('images/delete.svg', true, ['class' => 'main_menu_icon invert_filter']).'</a>';
            } else {
                $table->cellclass[][2] = 'table_action_buttons';
                $data[2] = '<a href="index.php?sec=gmodules&sec2=godmode/groups/modu_group_list&id_group='.$id_group['id_mg'].'&delete_group=1&offset='.$offset_delete.'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image('images/delete.svg', true, ['class' => 'main_menu_icon invert_filter']).'</a>';
            }
        } else {
            $data[1] = '<strong>';
            $data[1] .= ui_print_truncate_text($id_group['name'], GENERIC_SIZE_TEXT);
            $data[1] .= '</strong>';
        }

        array_push($table->data, $data);
    }

    html_print_table($table);
    $tablePagination = ui_pagination($total_groups, $url, $offset, 0, true, 'offset', false);
} else {
    ui_print_info_message(
        [
            'no_close' => true,
            'message'  => __('There are no defined module groups'),
        ]
    );
}

if ($is_management_allowed === true) {
    echo '<form method="post" action="index.php?sec=gmodules&sec2=godmode/groups/configure_modu_group">';
    html_print_action_buttons(
        html_print_submit_button(
            __('Create module group'),
            'crt',
            false,
            [ 'icon' => 'next' ],
            true
        ),
        [
            'type'          => 'form_action',
            'right_content' => $tablePagination,
        ]
    );
    echo '</form>';
}
