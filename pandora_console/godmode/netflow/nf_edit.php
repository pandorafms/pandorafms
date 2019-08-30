<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_netflow.php';

check_login();

enterprise_hook('open_meta_frame');

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access event viewer'
    );
    include 'general/noaccess.php';
    return;
}

$pure = get_parameter('pure', 0);

// Header
if (! defined('METACONSOLE')) {
    ui_print_page_header(
        __('Manage Netflow Filter'),
        'images/gm_netflow.png',
        false,
        'pcap_filter',
        true
    );

    $is_windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
    if ($is_windows) {
        ui_print_error_message(__('Not supported in Windows systems'));
    }
} else {
    $nav_bar = [
        [
            'link' => 'index.php?sec=main',
            'text' => __('Main'),
        ],
        [
            'link' => 'index.php?sec=netf&sec2=godmode/netflow/nf_edit',
            'text' => __('Netflow filters'),
        ],
    ];

    ui_meta_print_page_header($nav_bar);

    ui_meta_print_header(__('Netflow filters'));
}

$delete = (bool) get_parameter('delete');
$multiple_delete = (bool) get_parameter('multiple_delete', 0);
$id = (int) get_parameter('id');
$name = (string) get_parameter('name');

if ($delete) {
    $id_filter = db_get_value('id_name', 'tnetflow_filter', 'id_sg', $id);
    $result = db_process_sql_delete(
        'tnetflow_filter',
        ['id_sg' => $id]
    );

    $result2 = db_process_sql_delete(
        'tnetflow_report_content',
        ['id_filter' => $id_filter]
    );

    if ($result !== false) {
        $result = true;
    } else {
        $result = false;
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
        $id_filter = db_get_value('id_name', 'tnetflow_filter', 'id_sg', $id);
        $result = db_process_sql_delete(
            'tnetflow_filter',
            ['id_sg' => $id]
        );

        $result2 = db_process_sql_delete(
            'tnetflow_report_content',
            ['id_filter' => $id_filter]
        );

        if ($result === false) {
            break;
        }
    }

    if ($result !== false) {
        $result = true;
    } else {
        $result = false;
    }

    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Not deleted. Error deleting data')
    );
}

$own_info = get_user_info($config['id_user']);
// Get group list that user has access
$groups_user = users_get_groups($config['id_user'], 'AW', $own_info['is_admin'], true);
$sql = 'SELECT *
	FROM tnetflow_filter
	WHERE id_group IN (0, '.implode(',', array_keys($groups_user)).')';
$filters = db_get_all_rows_sql($sql);
if ($filters === false) {
    $filters = [];
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table';

$table->head = [];
$table->head[0] = html_print_checkbox('all_delete', 0, false, true, false);
$table->head[1] = __('Name');
$table->head[2] = __('Group');
$table->head[3] = __('Action');
$table->style = [];
$table->style[1] = 'font-weight: bold';
$table->align = [];

$table->size = [];
$table->size[0] = '10px';
$table->size[1] = '60%';
$table->size[2] = '30%';
$table->size[3] = '80px';
$table->data = [];

$total_filters = db_get_all_rows_filter('tnetflow_filter', false, 'COUNT(*) AS total');
$total_filters = $total_filters[0]['total'];

// ui_pagination ($total_filters, $url);
foreach ($filters as $filter) {
    $data = [];

    $data[0] = html_print_checkbox_extended('delete_multiple[]', $filter['id_sg'], false, false, '', 'class="check_delete"', true);
    $data[1] = '<a href="'.$config['homeurl'].'index.php?sec=netf&sec2=godmode/netflow/nf_edit_form&id='.$filter['id_sg'].'&pure='.$pure.'">'.$filter['id_name'].'</a>';
    $data[2] = ui_print_group_icon($filter['id_group'], true, 'groups_small', '', !defined('METACONSOLE'));
    $table->cellclass[][3] = 'action_buttons';
    $data[3] = "<a onclick='if(confirm(\"".__('Are you sure?')."\")) return true; else return false;' 
        href='".$config['homeurl'].'index.php?sec=netf&sec2=godmode/netflow/nf_edit&delete=1&id='.$filter['id_sg']."&offset=0&pure=$pure'>".html_print_image('images/cross.png', true, ['title' => __('Delete')]).'</a>';

    array_push($table->data, $data);
}

if (isset($data)) {
    echo "<form method='post' action='".$config['homeurl']."index.php?sec=netf&sec2=godmode/netflow/nf_edit&pure=$pure'>";
    html_print_input_hidden('multiple_delete', 1);
    html_print_table($table);
    echo "<div style=' float: right;'>";

    html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
    echo '</div>';
    echo '</form>';
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('There are no defined filters') ]);
}

echo '<form method="post" action="'.$config['homeurl'].'index.php?sec=netf&sec2=godmode/netflow/nf_edit_form&pure='.$pure.'">';
echo "<div style='margin-right: 5px; float: right;'>";
html_print_submit_button(__('Create filter'), 'crt', false, 'class="sub wand"');
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
