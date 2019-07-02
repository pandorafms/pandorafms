<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global vars
global $config;

check_login();

enterprise_hook('open_meta_frame');

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access SNMP Group Management'
    );
    include 'general/noaccess.php';
    return;
}

enterprise_include_once('meta/include/functions_components_meta.php');
require_once $config['homedir'].'/include/functions_network_components.php';
require_once $config['homedir'].'/include/functions_component_groups.php';

// Header
if (defined('METACONSOLE')) {
    components_meta_print_header();
    $sec = 'advanced';
} else {
    ui_print_page_header(
        __('Module management').' &raquo; '.__('Component group management'),
        '',
        false,
        'component_groups',
        true
    );
    $sec = 'gmodules';
}


$create = (bool) get_parameter('create');
$update = (bool) get_parameter('update');
$delete = (bool) get_parameter('delete');
$new = (bool) get_parameter('new');
$id = (int) get_parameter('id');
$multiple_delete = (bool) get_parameter('multiple_delete', 0);
$pure = get_parameter('pure', 0);

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
        if ($result) {
            db_pandora_audit('Module management', "Create component group #$result");
        } else {
            db_pandora_audit('Module management', 'Fail try to create component group');
        }

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
        if ($result) {
            db_pandora_audit('Module management', "Update component group #$id");
        } else {
            db_pandora_audit('Module management', "Fail try to update component group #$id");
        }

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

    if (($result !== false) and ($result1 !== false)) {
        $result = true;
    } else {
        $result = false;
    }

    if ($result) {
        db_pandora_audit('Module management', "Delete component group #$id");
    } else {
        db_pandora_audit('Module management', "Fail try to delete component group #$id");
    }

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
    if ($result) {
        db_pandora_audit(
            'Module management',
            "Multiple delete component group: $str_ids"
        );
    } else {
        db_pandora_audit(
            'Module management',
            "Fail try to delete component group: $str_ids"
        );
    }

    ui_print_result_message(
        $result,
        __('Successfully multiple deleted'),
        __('Not deleted. Error deleting multiple data')
    );
}

if (($id || $new) && !$delete && !$multiple_delete) {
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
$table->width = '100%';
$table->class = 'info_table';
$table->head = [];
$table->head['checkbox'] = html_print_checkbox('all_delete', 0, false, true, false);
$table->head[0] = __('Name');
$table->head[1] = __('Action');
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
    if (defined('METACONSOLE')) {
        $data[0] = $tabulation.'<a href="index.php?sec=advanced&sec2=godmode/modules/manage_nc_groups&id='.$group['id_sg'].'">'.$group['name'].'</a>';
    } else {
        $data[0] = $tabulation.'<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&id='.$group['id_sg'].'">'.$group['name'].'</a>';
    }

    $table->cellclass[][1] = 'action_buttons';
    $data[1] = "<a onclick='if(confirm(\"".__('Are you sure?')."\")) return true; else return false;' 
        href='index.php?sec=".$sec.'&sec2=godmode/modules/manage_nc_groups&delete=1&id='.$group['id_sg']."&offset=0'>".html_print_image('images/cross.png', true, ['title' => __('Delete')]).'</a>';

    array_push($table->data, $data);
}

if (isset($data)) {
    echo "<form method='post' action='index.php?sec=".$sec."&sec2=godmode/modules/manage_nc_groups'>";
    html_print_input_hidden('multiple_delete', 1);
    html_print_table($table);
    echo "<div style='padding-left: 10px; float: right;'>";
    html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
    echo '</div>';
    echo '</form>';
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('There are no defined component groups') ]);
}


echo '<form method="post" action='.$url.'>';
echo '<div class="" style="float:right;">';
html_print_input_hidden('new', 1);
html_print_submit_button(__('Create'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';

enterprise_hook('close_meta_frame');

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
