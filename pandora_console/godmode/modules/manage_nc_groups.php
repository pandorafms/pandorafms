<?php
/**
 * Component group Management.
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
        'Trying to access SNMP Group Management'
    );
    include 'general/noaccess.php';
    return;
}

enterprise_include_once('meta/include/functions_components_meta.php');
require_once $config['homedir'].'/include/functions_network_components.php';
require_once $config['homedir'].'/include/functions_component_groups.php';

// Header
if (is_metaconsole() === true) {
    components_meta_print_header();
    $sec = 'advanced';
} else {
    ui_print_standard_header(
        __('Component group management'),
        '',
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
                'label' => __('Component groups'),
            ],
        ]
    );
    $sec = 'gmodules';
}

if (is_management_allowed() === true || is_metaconsole()) {
    $create = (bool) get_parameter('create');
    $update = (bool) get_parameter('update');
    $delete = (bool) get_parameter('delete');
    $new = (bool) get_parameter('new');
    $id = (int) get_parameter('id');
    $multiple_delete = (bool) get_parameter('multiple_delete', 0);
    $pure = get_parameter('pure', 0);
}

if ($create) {
    $name = (string) get_parameter('name');
    $parent = (int) get_parameter('parent');

    if ($name == '') {
        ui_print_error_message(__('Could not be created. Blank name'));
        include_once 'manage_nc_groups_form.php';
        return;
    } else {
        $result = db_process_sql_insert(
            'tnetwork_component_group',
            [
                'name'   => $name,
                'parent' => $parent,
            ]
        );

        $auditMessage = ((bool) $result === true) ? sprintf('Create component group #%s', $result) : 'Fail try to create component group';
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            $auditMessage
        );

        ui_print_result_message(
            $result,
            __('Successfully created'),
            __('Could not be created')
        );
    }
}

if ($update) {
    $name = (string) get_parameter('name');
    $parent = (int) get_parameter('parent');

    if ($name == '') {
        ui_print_error_message(__('Not updated. Blank name'));
    } else {
        $result = db_process_sql_update(
            'tnetwork_component_group',
            [
                'name'   => $name,
                'parent' => $parent,
            ],
            ['id_sg' => $id]
        );

        $auditMessage = ((bool) $result === true) ? 'Update component group' : 'Fail try to update component group';
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            sprintf(
                '%s #%s',
                $auditMessage,
                $id
            )
        );

        ui_print_result_message(
            $result,
            __('Successfully updated'),
            __('Not updated. Error updating data')
        );
    }
}

if ($delete) {
    $parent_id = db_get_value_filter('parent', 'tnetwork_component_group', ['id_sg' => $id]);

    $result1 = db_process_sql_update('tnetwork_component_group', ['parent' => $parent_id], ['parent' => $id]);

    $result = db_process_sql_delete(
        'tnetwork_component_group',
        ['id_sg' => $id]
    );

    if (($result !== false) && ($result1 !== false)) {
        $result = true;
    } else {
        $result = false;
    }

    $auditMessage = ((bool) $result === true) ? 'Delete component group' : 'Fail try to delete component group';
    db_pandora_audit(
        AUDIT_LOG_MODULE_MANAGEMENT,
        sprintf(
            '%s #%s',
            $auditMessage,
            $id
        )
    );

    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Not deleted. Error deleting data')
    );
}

if ($multiple_delete) {
    $ids = (array) get_parameter('delete_multiple', []);

    foreach ($ids as $id) {
        $result = db_process_sql_delete(
            'tnetwork_component_group',
            ['id_sg' => $id]
        );

        $result1 = db_process_sql_update('tnetwork_component_group', ['parent' => 0], ['parent' => $id]);

        if (($result === false) or ($result1 === false)) {
            break;
        }
    }


    if ($result !== false) {
        $result = true;
    } else {
        $result = false;
    }

    $str_ids = implode(',', $ids);

    $auditMessage = ((bool) $result === true) ? 'Multiple delete component group' : 'Fail try to delete multiple component group';
    db_pandora_audit(
        AUDIT_LOG_MODULE_MANAGEMENT,
        sprintf(
            '%s #%s',
            $auditMessage,
            $str_ids
        )
    );

    ui_print_result_message(
        $result,
        __('Successfully multiple deleted'),
        __('Not deleted. Error deleting multiple data')
    );
}

if (($id || $new) && !$delete && !$multiple_delete && (is_management_allowed() === true || is_metaconsole())) {
    include_once 'manage_nc_groups_form.php';
    return;
}

$url = ui_get_url_refresh(
    [
        'offset' => false,
        'create' => false,
        'update' => false,
        'delete' => false,
        'new'    => false,
        'crt'    => false,
        'upd'    => false,
        'id'     => false,
    ]
);

$filter = [];

// $filter['offset'] = (int) get_parameter ('offset');
// $filter['limit'] = (int) $config['block_size'];
$filter['order'] = 'parent';

$groups = db_get_all_rows_filter('tnetwork_component_group', $filter);
if ($groups === false) {
    $groups = [];
}

$groups_clean = [];
foreach ($groups as $group_key => $group_val) {
    $groups_clean[$group_val['id_sg']] = $group_val;
}

// Format component groups in tree form
$groups = component_groups_get_groups_tree_recursive($groups_clean, 0, 0);

$table = new stdClass();
$table->class = 'info_table';
$table->head = [];
$table->head['checkbox'] = html_print_checkbox('all_delete', 0, false, true, false);
$table->head[0] = __('Name');
if (is_management_allowed() === true || is_metaconsole() === true) {
    $table->head[1] = __('Action');
}

$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->align = [];
$table->align[1] = 'left';
$table->size = [];
$table->size['checkbox'] = '20px';
// $table->size[0] = '80%';
$table->size[1] = '60px';
$table->data = [];

$total_groups = db_get_all_rows_filter('tnetwork_component_group', false, 'COUNT(*) AS total');
$total_groups = $total_groups[0]['total'];

// ui_pagination ($total_groups, $url);
foreach ($groups as $group) {
    $data = [];

    $data['checkbox'] = html_print_checkbox_extended('delete_multiple[]', $group['id_sg'], false, false, '', 'class="check_delete"', true);


    $tabulation = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $group['deep']);
    if (is_metaconsole() === true) {
        $data[0] = $tabulation.'<a href="index.php?sec=advanced&sec2=godmode/modules/manage_nc_groups&id='.$group['id_sg'].'">'.$group['name'].'</a>';
    } else {
        $data[0] = $tabulation.'<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&id='.$group['id_sg'].'">'.$group['name'].'</a>';
    }

    $table->cellclass[][1] = 'table_action_buttons';
    if (is_management_allowed() === true || is_metaconsole()) {
        $data[1] = html_print_anchor(
            [
                'onClick' => 'if(confirm(\"'.__('Are you sure?').'\")) return true; else return false;',
                'href'    => 'index.php?sec='.$sec.'&sec2=godmode/modules/manage_nc_groups&delete=1&id='.$group['id_sg'].'&offset=0',
                'content' => html_print_image('images/delete.svg', true, ['title' => __('Delete'), 'class' => 'main_menu_icon invert_filter']),
            ],
            true
        );
    }

    array_push($table->data, $data);
}

if (is_management_allowed() === false && is_metaconsole() === false) {
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=godmode/modules/manage_nc_groups'
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. Component groups are read only. Go to %s to manage it.',
            $url
        )
    );
}

$actionButtons = [];
if (isset($data) === true) {
    echo '<form id="multiple_delete_form" method="POST" action="index.php?sec='.$sec.'&sec2=godmode/modules/manage_nc_groups">';
    html_print_table($table);
    echo '</form>';
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('There are no defined component groups') ]);
}

if (is_management_allowed() === true || is_metaconsole()) {
    // Create form.
    echo '<form id="create_form" method="POST" action="'.$url.'">';
    html_print_input_hidden('new', 1);
    echo '</form>';
    // Create action button.
    $actionButtons[] = html_print_submit_button(
        __('Create'),
        'crt',
        false,
        [
            'icon' => 'wand',
            'form' => 'create_form',
        ],
        true
    );
    // Delete action button.
    if (isset($data) === true) {
        $actionButtons[] = html_print_input_hidden(
            'multiple_delete',
            1,
            false,
            false,
            'form="multiple_delete_form"'
        );
        $actionButtons[] = html_print_submit_button(
            __('Delete'),
            'delete_btn',
            false,
            [
                'icon' => 'delete',
                'mode' => 'secondary',
                'form' => 'multiple_delete_form',
            ],
            true
        );
    }
}

html_print_action_buttons(
    implode('', $actionButtons),
    ['type' => 'form_action']
);

?>
<script type="text/javascript">
    $( document ).ready(function() {

        $('[id^=checkbox-delete_multiple]').change(function(){
            if($(this).parent().parent().hasClass('checkselected')){
                $(this).parent().parent().removeClass('checkselected');
            }
            else{
                $(this).parent().parent().addClass('checkselected');
            }
        });

        $('[id^=checkbox-all_delete]').change(function(){
            if ($("#checkbox-all_delete").prop("checked")) {
                $('[id^=checkbox-delete_multiple]').parent().parent().addClass('checkselected');
                $(".check_delete").prop("checked", true);
            }
            else{
                $('[id^=checkbox-delete_multiple]').parent().parent().removeClass('checkselected');
                $(".check_delete").prop("checked", false);
            }
        });
    });
</script>
