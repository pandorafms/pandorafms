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
// Load global vars
global $config;

check_login();

$event_w = check_acl($config['id_user'], 0, 'EW');
$event_m = check_acl($config['id_user'], 0, 'EM');
$access = ($event_w == true) ? 'EW' : (($event_m == true) ? 'EM' : 'EW');

if (!$event_w && !$event_m) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access events filter editor'
    );
    include 'general/noaccess.php';
    return;
}



$delete = (bool) get_parameter('delete', 0);
$multiple_delete = (bool) get_parameter('multiple_delete', 0);

if ($delete) {
    $id = (int) get_parameter('id');

    $filter_group = (int) db_get_value('id_group', 'tevent_filter', 'id_filter', $id);

    if (!check_acl_restricted_all($config['id_user'], $filter_group, 'EW')
        && !check_acl_restricted_all($config['id_user'], $filter_group, 'EM')
    ) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access events filter editor'
        );
        include 'general/noaccess.php';
        return;
    }

    $id_filter = db_get_value('id_filter', 'tevent_filter', 'id_filter', $id);

    if ($id_filter === false) {
        $result = false;
    } else {
        $result = db_process_sql_delete('tevent_filter', ['id_filter' => $id]);
    }

    if ($result !== false) {
        db_process_sql_delete(
            'tfavmenu_user',
            [
                'id_element' => $id_filter,
                'section'    => 'Events',
                'id_user'    => $config['id_user'],
            ]
        );
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
        $result = db_process_sql_delete(
            'tevent_filter',
            ['id_filter' => $id]
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

$strict_acl = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

$own_info = get_user_info($config['id_user']);
// Get group list that user has access
if ($strict_acl) {
    $groups_user = users_get_strict_mode_groups(
        $config['id_user'],
        users_can_manage_group_all()
    );
} else {
    // All users should see the filters with the All group.
    $groups_user = users_get_groups(
        $config['id_user'],
        $access,
        true,
        true
    );
}

$sql = '
	SELECT *
	FROM tevent_filter
	WHERE id_group_filter IN ('.implode(',', array_keys($groups_user)).')';
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
$table->head[3] = __('Event type');
$table->head[4] = __('Event status');
$table->head[5] = __('Severity');
$table->head[6] = __('Action');
$table->style = [];
$table->style[1] = 'font-weight: bold';
$table->align = [];
$table->align[2] = 'left';
$table->align[3] = 'left';
$table->align[4] = 'left';

$table->align[5] = 'left';
$table->align[6] = 'left';
$table->size = [];
$table->size[0] = '20px';
$table->size[1] = '50%';
$table->size[2] = '5px';
$table->size[3] = '80px';
$table->size[4] = '80px';
$table->size[5] = '80px';
$table->size[6] = '40px';
$table->data = [];

$total_filters = db_get_all_rows_filter('tevent_filter', false, 'COUNT(*) AS total');
$total_filters = $total_filters[0]['total'];

// ui_pagination ($total_filters, $url);
foreach ($filters as $filter) {
    $data = [];

    $data[0] = html_print_checkbox_extended('delete_multiple[]', $filter['id_filter'], false, false, '', 'class="check_delete"', true);

    if (!check_acl_restricted_all($config['id_user'], $filter['id_group'], 'EW')
        && !check_acl_restricted_all($config['id_user'], $filter['id_group'], 'EM')
    ) {
        $data[1] = $filter['id_name'];
    } else {
        $data[1] = '<a href="index.php?sec=geventos&sec2=godmode/events/events&section=edit_filter&id='.$filter['id_filter'].'&pure='.$config['pure'].'">'.$filter['id_name'].'</a>';
    }

    $data[2] = ui_print_group_icon($filter['id_group_filter'], true);
    $data[3] = events_get_event_types($filter['event_type']);
    $data[4] = events_get_status($filter['status']);
    $data[5] = events_get_severity_types($filter['severity']);
    $data[6] = '';

    if (check_acl_restricted_all($config['id_user'], $filter['id_group'], 'EW')
        || check_acl_restricted_all($config['id_user'], $filter['id_group'], 'EM')
    ) {
        $table->cellclass[][6] = 'table_action_buttons';
        $data[6] = "<a onclick='if(confirm(\"".__('Are you sure?')."\")) return true; else return false;'href='index.php?sec=geventos&sec2=godmode/events/events&section=filter&delete=1&id=".$filter['id_filter'].'&offset=0&pure='.$config['pure']."'>".html_print_image(
            'images/delete.svg',
            true,
            [
                'title' => __('Delete'),
                'class' => 'invert_filter',
            ]
        ).'</a>';
    }

    array_push($table->data, $data);
}


if (isset($data)) {
    echo "<form id='form-delete-filters' method='post' action='index.php?sec=geventos&sec2=godmode/events/events&amp;pure=".$config['pure']."'>";
        html_print_input_hidden('multiple_delete', 1);
        html_print_table($table);
    if (is_metaconsole() === true) {
        echo "<div class='right'>";
    }

    if (is_metaconsole() === true) {
        echo '</div>';
    }

    echo '</form>';
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('There are no defined filters') ]);
}


$submitButtons = '<form method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=edit_filter&amp;pure='.$config['pure'].'">';
$submitButtons .= html_print_submit_button(
    __('Create filter'),
    'crt',
    false,
    [
        'icon'  => 'wand',
        'class' => 'mrgn_lft_15px',
    ],
    true
);
$submitButtons .= '</form>';

$submitButtons .= html_print_submit_button(
    __('Delete'),
    'delete_btn',
    false,
    [
        'icon'  => 'delete',
        'class' => 'secondary',
        'id'    => 'delete-event-filters',
    ],
    true
);

html_print_action_buttons(
    $submitButtons
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
                $("[id^=checkbox-delete_multiple]").prop("checked", true);
            }
            else{
                $('[id^=checkbox-delete_multiple]').parent().parent().removeClass('checkselected');
                $("[id^=checkbox-delete_multiple]").prop("checked", false);
            }    
        });
        
        $('#button-delete_btn').click(function (e) { 
            $('#form-delete-filters').submit();
        });

    });
    
</script>

