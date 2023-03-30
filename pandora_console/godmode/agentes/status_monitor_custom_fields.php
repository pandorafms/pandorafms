<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'AR')
    && ! check_acl($config['id_user'], 0, 'AW')
    && ! check_acl($config['id_user'], 0, 'AM')
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent Management'
    );
    include 'general/noaccess.php';
    return;
}

$update = get_parameter('upd_button', '');
$default = (int) get_parameter('default', 0);

// Header.
ui_print_standard_header(
    __('Monitor detail').$subpage,
    'images/agent.png',
    false,
    '',
    true,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Monitoring'),
        ],
        [
            'link'  => '',
            'label' => __('Views'),
        ],
    ],
    (empty($fav_menu) === true) ? [] : $fav_menu
);

if ($default != 0) {
    $fields_selected = explode(',', $config['status_monitor_fields']);
} else if ($update != '') {
    $fields_selected = (array) get_parameter('fields_selected');

    if ($fields_selected[0] == '') {
        $fields_selected = explode(',', $config['status_monitor_fields']);
    } else {
        $status_monitor_fields = implode(',', $fields_selected);
    }

    $values = [
        'token' => 'status_monitor_fields',
        'value' => $status_monitor_fields,
    ];

    // Update 'status_monitor_fields' in tconfig table to keep the value at update.
    $result = db_process_sql_update(
        'tconfig',
        $values,
        ['token' => 'status_monitor_fields']
    );

    ui_print_result_message($result, __('Successfully updated'), __('Could not be updated'));

    $config['status_monitor_fields'] = $status_monitor_fields;
}

$fields_selected = [];
$status_monitor_fields = '';
$fields_selected = explode(',', $config['status_monitor_fields']);

$result_selected = [];

// Show list of fields selected.
if ($fields_selected[0] != '') {
    foreach ($fields_selected as $field_selected) {
        switch ($field_selected) {
            case 'policy':
                $result = __('Policy');
            break;

            case 'agent':
                $result = __('Agent');
            break;

            case 'data_type':
                $result = __('Data type');
            break;

            case 'module_name':
                $result = __('Module name');
            break;

            case 'server_type':
                $result = __('Server type');
            break;

            case 'interval':
                $result = __('Interval');
            break;

            case 'status':
                $result = __('Status');
            break;

            case 'last_status_change':
                $result = __('Last status change');
            break;

            case 'graph':
                $result = __('Graph');
            break;

            case 'warn':
                $result = __('Warn');
            break;

            case 'data':
                $result = __('Data');
            break;

            case 'timestamp':
                $result = __('Timestamp');
            break;
        }

        $result_selected[$field_selected] = $result;
    }
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->size = [];
// ~ $table->size[0] = '20%';
$table->size[1] = '10px';
// ~ $table->size[2] = '20%';
$table->style[0] = 'text-align:center;';
$table->style[2] = 'text-align:center;';

$table->data = [];

$fields_available = [];

$fields_available['policy'] = __('Policy');
$fields_available['agent'] = __('Agent');
$fields_available['data_type'] = __('Data type');
$fields_available['module_name'] = __('Module name');
$fields_available['server_type'] = __('Server type');
$fields_available['interval'] = __('Interval');
$fields_available['status'] = __('Status');
$fields_available['last_status_change'] = __('Last status change');
$fields_available['graph'] = __('Graph');
$fields_available['warn'] = __('Warn');
$fields_available['data'] = __('Data');
$fields_available['timestamp'] = __('Timestamp');

// remove fields already selected
foreach ($fields_available as $key => $available) {
    foreach ($result_selected as $selected) {
        if ($selected == $available) {
            unset($fields_available[$key]);
        }
    }
}

// General title.
$generalTitleContent = [];
// $generalTitleContent[] = html_print_div([ 'style' => 'width: 10px; flex: 0 0 auto; margin-right: 5px;}', 'class' => 'section_table_title_line' ], true);
$generalTitleContent[] = html_print_div([ 'class' => 'section_table_title', 'content' => __('Show monitor detail fields')], true);
$titledata[0] = html_print_div(['class' => 'flex-row-center', 'content' => implode('', $generalTitleContent) ], true);
$table->data['general_title'] = $titledata;
$table->data[0][0] = '<span class="font-title-font">'.__('Fields available').'</span>';
$table->data[1][0] = html_print_select($fields_available, 'fields_available[]', true, '', '', 0, true, true, false, '', false, 'width: 300px');
$table->data[1][1] = '<a href="javascript:">'.html_print_image(
    'images/darrowright.png',
    true,
    [
        'id'    => 'right',
        'title' => __('Add fields to select'),
        'class' => 'invert_filter',
    ]
).'</a>';
$table->data[1][1] .= '<br><br><br><br><a href="javascript:">'.html_print_image(
    'images/darrowleft.png',
    true,
    [
        'id'    => 'left',
        'title' => __('Delete fields to select'),
        'class' => 'invert_filter',
    ]
).'</a>';

$table->data[0][1] = '';
$table->data[0][2] = '<span class="font-title-font">'.__('Fields selected').'</span>';
$table->data[1][2] = html_print_select(
    $result_selected,
    'fields_selected[]',
    true,
    '',
    '',
    0,
    true,
    true,
    false,
    '',
    false,
    'width: 300px'
);

echo '<form id="custom_status_monitor" method="post" action="index.php?sec=view&sec2=operation/agentes/status_monitor&section=fields&amp;pure='.$config['pure'].'" class="max_floating_element_size">';
html_print_table($table);

html_print_action_buttons(
    html_print_submit_button(
        __('Update'),
        'update_button',
        false,
        [ 'icon' => 'update' ],
        true
    )
);

echo '</form>';

?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
    
    $("#right").click (function () {
        jQuery.each($("select[name='fields_available[]'] option:selected"), function (key, value) {
            field_name = $(value).html();
            if (field_name != <?php echo "'".__('None')."'"; ?>) {
                id_field = $(value).attr('value');
                $("select[name='fields_selected[]']").append($("<option></option>").html(field_name).attr("value", id_field));
                $("#fields_available").find("option[value='" + id_field + "']").remove();
                $("#fields_selected").find("option[value='0']").remove();
            }
        });
    });
    
    $("#left").click (function () {
        var current_fields_size = ($('#fields_selected option').length);    
        var selected_fields = [];
        var selected_fields_total = '';

        jQuery.each($("select[name='fields_selected[]'] option:selected"), function (key, value) {
            field_name = $(value).html();
             selected_fields.push(field_name);
             selected_fields_total = selected_fields.length;
        });

        if(selected_fields_total === current_fields_size){
            display_confirm_dialog(
                "<?php echo '<span style=text-transform:none;font-size:9.5pt;>'.__('There must be at least one custom field. Timestamp will be set by default').'</span>'; ?>",
                "<?php echo __('Confirm'); ?>",
                "<?php echo __('Cancel'); ?>",
                function () {
                    move_left();
                    $("#fields_available").find("option[value='timestamp']").remove();
                    $("select[name='fields_selected[]']").append($("<option></option>").val('timestamp').html('<i>' + 'Timestamp' + '</i>'));
                }
            );
        }
        else{
            move_left();
        }
    });
    
    $("#submit-upd_button").click(function () {
        $("#fields_selected").find("option[value='0']").remove();
        $('#fields_selected option').map(function() {
            $(this).prop('selected', true);
        });
    });
});

function move_left(){
    jQuery.each($("select[name='fields_selected[]'] option:selected"), function (key, value) {
        field_name = $(value).html();
        if (field_name != <?php echo "'".__('None')."'"; ?>) {
            id_field = $(value).attr('value');
            $("select[name='fields_available[]']").append($("<option></option>").val(id_field).html('<i>' + field_name + '</i>'));
            $("#fields_selected").find("option[value='" + id_field + "']").remove();
            $("#fields_available").find("option[value='0']").remove();
        }
    });
}
</script>
