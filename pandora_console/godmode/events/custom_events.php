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

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Custom events Management'
    );
    include 'general/noaccess.php';
    return;
}

$update = get_parameter('upd_button', '');
$default = (int) get_parameter('default', 0);


if ($default != 0) {
    // $event_fields = io_safe_input('evento,id_agente,estado,timestamp');
    $fields_selected = explode(',', $config['event_fields']);
} else if ($update != '') {
    $fields_selected = (array) get_parameter('fields_selected');

    if ($fields_selected[0] == '') {
        // $event_fields = io_safe_input('evento,id_agente,estado,timestamp');
        $fields_selected = explode(',', $config['event_fields']);
    } else {
        $event_fields = implode(',', $fields_selected);
    }

    $values = [
        'token' => 'event_fields',
        'value' => $event_fields,
    ];
    // Update 'event_fields' in tconfig table to keep the value at update.
    $result = db_process_sql_update(
        'tconfig',
        $values,
        ['token' => 'event_fields']
    );
    $config['event_fields'] = $event_fields;
}

$fields_selected = [];
$event_fields = '';
$fields_selected = explode(',', $config['event_fields']);

$result_selected = [];

// Show list of fields selected.
if ($fields_selected[0] != '') {
    foreach ($fields_selected as $field_selected) {
        $result_selected[$field_selected] = events_get_column_name(
            $field_selected
        );
    }
}

$event = [];

echo '<h3>'.__('Show event fields');
echo '&nbsp;<a href="index.php?sec=geventos&sec2=godmode/events/events&section=fields&default=1">';
html_print_image('images/clean.png', false, ['title' => __('Load the fields from previous events'), 'onclick' => "if (! confirm ('".__('Event fields will be loaded. Do you want to continue?')."')) return false"]);
echo '</a></h3>';

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->size = [];
$table->size[1] = '10px';
$table->style[0] = 'text-align:center;';
$table->style[2] = 'text-align:center;';

$table->data = [];

$fields_available = [];

$fields_available['id_evento'] = __('Event Id');
$fields_available['evento'] = __('Event Name');
$fields_available['id_agente'] = __('Agent ID');
$fields_available['agent_name'] = __('Agent Name');
$fields_available['direccion'] = __('Agent IP');
$fields_available['id_usuario'] = __('User');
$fields_available['id_grupo'] = __('Group');
$fields_available['estado'] = __('Status');
$fields_available['timestamp'] = __('Timestamp');
$fields_available['event_type'] = __('Event Type');
$fields_available['id_agentmodule'] = __('Module Name');
$fields_available['id_alert_am'] = __('Alert');
$fields_available['criticity'] = __('Severity');
$fields_available['user_comment'] = __('Comment');
$fields_available['tags'] = __('Tags');
$fields_available['source'] = __('Source');
$fields_available['id_extra'] = __('Extra Id');
$fields_available['owner_user'] = __('Owner');
$fields_available['ack_utimestamp'] = __('ACK Timestamp');
$fields_available['instructions'] = __('Instructions');
$fields_available['server_name'] = __('Server Name');
$fields_available['data'] = __('Data');
$fields_available['module_status'] = __('Module Status');
$fields_available['mini_severity'] = __('Severity mini');
$fields_available['module_custom_id'] = __('Module custom ID');
$fields_available['custom_data'] = __('Custom data');


// Remove fields already selected.
foreach ($fields_available as $key => $available) {
    if (isset($result_selected[$key])) {
        unset($fields_available[$key]);
    }
}

$table->data[0][0] = '<b>'.__('Fields available').'</b>';
$table->data[1][0] = html_print_select($fields_available, 'fields_available[]', true, '', '', 0, true, true, false, '', false, 'width: 300px');
$table->data[1][1] = '<a href="javascript:">'.html_print_image(
    'images/arrow@svg.svg',
    true,
    [
        'id'    => 'right',
        'title' => __('Add fields to select'),
        'style' => 'rotate: 180deg;',
        'class' => 'main_menu_icon invert_filter',
    ]
).'</a>';
$table->data[1][1] .= '<br><br><br><br><a href="javascript:">'.html_print_image(
    'images/arrow@svg.svg',
    true,
    [
        'id'    => 'left',
        'title' => __('Delete fields to select'),
        'style' => '',
    ]
).'</a>';

$table->data[0][1] = '';
$table->data[0][2] = '<b>'.__('Fields selected').'</b>';
$table->data[1][2] = '<div class="flex_justify">'.html_print_select(
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

$table->data[1][2] .= '<div id="sort_arrows" class="flex-column">';
$table->data[1][2] .= '<a href="javascript:">'.html_print_image(
    'images/darrowup.png',
    true,
    [
        'onclick' => 'sortUpDown(\'up\');',
        'title'   => __('Move up selected fields'),
        'class'   => 'main_menu_icon invert_filter',
    ]
).'</a>';
$table->data[1][2] .= '<a href="javascript:">'.html_print_image(
    'images/darrowdown.png',
    true,
    [
        'onclick' => 'sortUpDown(\'down\');',
        'title'   => __('Move down selected fields'),
        'class'   => 'main_menu_icon invert_filter',
    ]
).'</a>';
$table->data[1][2] .= '</div></div>';

echo '<form id="custom_events" method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=fields&amp;pure='.$config['pure'].'">';
html_print_table($table);

html_print_action_buttons(
    html_print_submit_button(
        __('Update'),
        'upd_button',
        false,
        [ 'icon' => 'update' ],
        true
    ),
    [ 'type' => 'form_action' ]
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
                "<?php echo '<span class=transform_none font_9pt>'.__('There must be at least one custom field. Timestamp will be set by default').'</span>'; ?>",
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

    $("#button-upd_button").click(function () {
        $("#fields_selected").find("option[value='0']").remove();
        $('#fields_selected option').map(function() {
            $(this).prop('selected', true);
        });
    });
});

function move_left() {
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

// Change the order (to up or down).
function sortUpDown(mode) {
    $("#fields_selected option:selected").each(function() {
        const field = $(this);

        if (field.length) {
            (mode === 'up') ? field.first().prev().before(field): field.last().next().after(field);
        }
    });
}

</script>
