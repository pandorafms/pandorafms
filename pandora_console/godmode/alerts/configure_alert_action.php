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

require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_integriaims.php';
enterprise_include_once('meta/include/functions_alerts_meta.php');

check_login();

enterprise_hook('open_meta_frame');

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

$id = (int) get_parameter('id');

$al_action = alerts_get_alert_action($id);
$pure = get_parameter('pure', 0);

if (is_ajax()) {
    $get_integria_ticket_custom_types = (bool) get_parameter('get_integria_ticket_custom_types');

    if ($get_integria_ticket_custom_types) {
        $ticket_type_id = get_parameter('ticket_type_id');

        $api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incident_fields', $ticket_type_id, false, 'json');

        echo $api_call;
        return;
    }
}

if (defined('METACONSOLE')) {
    $sec = 'advanced';
} else {
    $sec = 'galertas';
}

if ($al_action !== false) {
    $own_info = get_user_info($config['id_user']);
    if ($own_info['is_admin'] || check_acl_restricted_all($config['id_user'], 0, 'LM')) {
        $own_groups = array_keys(users_get_groups($config['id_user'], 'LM'));
    } else {
        $own_groups = array_keys(users_get_groups($config['id_user'], 'LM', false));
    }

    $is_in_group = in_array($al_action['id_group'], $own_groups);

    // Header.
    if (defined('METACONSOLE')) {
        alerts_meta_print_header();
    } else {
        ui_print_page_header(
            __('Alerts').' &raquo; '.__('Configure alert action'),
            'images/gm_alerts.png',
            false,
            'alert_config',
            true
        );
    }
} else {
    // Header.
    if (defined('METACONSOLE')) {
        alerts_meta_print_header();
    } else {
        ui_print_page_header(
            __('Alerts').' &raquo; '.__('Configure alert action'),
            'images/gm_alerts.png',
            false,
            'alert_config',
            true
        );
    }

    $is_in_group = true;
}

if (!$is_in_group && $al_action['id_group'] != 0) {
    db_pandora_audit('ACL Violation', 'Trying to access unauthorized alert action configuration');
    include 'general/noaccess.php';
    exit;
}

$is_central_policies_on_node = is_central_policies_on_node();

if ($is_central_policies_on_node === true) {
    ui_print_warning_message(
        __('This node is configured with centralized mode. All alerts templates information is read only. Go to metaconsole to manage it.')
    );
}

$disabled = !$is_in_group;
$disabled_attr = '';
if ($disabled) {
    $disabled_attr = 'disabled="disabled"';
}

$name = '';
$id_command = '';
$group = 0;
$action_threshold = 0;
// All group is 0.
if ($id) {
    $action = alerts_get_alert_action($id);
    $name = $action['name'];
    $id_command = $action['id_alert_command'];

    $group = $action['id_group'];
    $action_threshold = $action['action_threshold'];
    $create_wu_integria = $action['create_wu_integria'];
}

// Hidden div with help hint to fill with javascript.
html_print_div(
    [
        'id'      => 'help_alert_macros_hint',
        'content' => ui_print_help_icon('alert_macros', true),
        'hidden'  => true,
    ]
);

$table = new stdClass();
$table->id = 'table_macros';
$table->width = '100%';
$table->class = 'databox filters';

if (defined('METACONSOLE')) {
    if ($id) {
        $table->head[0] = __('Update Action');
    } else {
        $table->head[0] = __('Create Action');
    }

    $table->head_colspan[0] = 4;
    $table->headstyle[0] = 'text-align: center';
}

$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->size = [];
$table->size[0] = '20%';
$table->data = [];
$table->data[0][0] = __('Name');
$table->data[0][1] = html_print_input_text(
    'name',
    $name,
    '',
    35,
    255,
    true,
    false,
    false,
    '',
    '',
    '',
    '',
    false,
    '',
    '',
    '',
    ($is_central_policies_on_node | $disabled)
);

if (io_safe_output($name) == 'Monitoring Event') {
    $table->data[0][1] .= '&nbsp;&nbsp;'.ui_print_help_tip(
        __('This action may stop working, if you change its name.'),
        true,
        'images/header_yellow.png'
    );
}

$table->colspan[0][1] = 2;

$table->data[1][0] = __('Group');

$own_info = get_user_info($config['id_user']);

$return_all_group = false;

if (users_can_manage_group_all('LW') === true || $disabled) {
    $return_all_group = true;
}

$table->data[1][1] = '<div class="w250px inline">'.html_print_select_groups(
    false,
    'LW',
    $return_all_group,
    'group',
    $group,
    '',
    '',
    0,
    true,
    false,
    true,
    '',
    ($is_central_policies_on_node | $disabled)
).'</div>';
$table->colspan[1][1] = 2;

$create_ticket_command_id = db_get_value('id', 'talert_commands', 'name', io_safe_input('Integria IMS Ticket'));

$sql_exclude_command_id = '';

if ($config['integria_enabled'] == 0 && $create_ticket_command_id !== false) {
    $sql_exclude_command_id = ' AND id <> '.$create_ticket_command_id;
}

$table->data[2][0] = __('Command');
$commands_sql = db_get_all_rows_filter(
    'talert_commands',
    'id_group IN ('.implode(',', array_keys(users_get_groups(false, 'LW'))).')'.$sql_exclude_command_id,
    [
        'id',
        'name',
    ],
    'AND',
    false,
    true
);
$table->data[2][1] = html_print_select_from_sql(
    $commands_sql,
    'id_command',
    $id_command,
    '',
    __('None'),
    0,
    true,
    false,
    false,
    ($is_central_policies_on_node | $disabled)
);
$table->data[2][1] .= ' ';
if ($is_central_policies_on_node === false
    && check_acl($config['id_user'], 0, 'PM') && !$disabled
) {
    $table->data[2][1] .= __('Create Command');
    $table->data[2][1] .= '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_command&pure='.$pure.'">';
    $table->data[2][1] .= html_print_image('images/add.png', true);
    $table->data[2][1] .= '</a>';
}

$table->data[2][1] .= '<div id="command_description"  ></div>';
$table->colspan[2][1] = 2;

$table->data[3][0] = __('Threshold');
$table->data[3][1] = html_print_extended_select_for_time(
    'action_threshold',
    $action_threshold,
    '',
    '',
    '',
    false,
    true,
    false,
    true,
    '',
    ($is_central_policies_on_node | $disabled),
    false,
    '',
    false,
    true
);
$table->colspan[3][1] = 2;

$table->data[4][0] = '';
$table->data[4][1] = __('Firing');
$table->data[4][2] = __('Recovery');
$table->cellstyle[4][1] = 'font-weight: bold;';
$table->cellstyle[4][2] = 'font-weight: bold;';

$table->data[5][0] = __('Command preview');
$table->data[5][1] = html_print_textarea(
    'command_preview',
    5,
    30,
    '',
    'disabled="disabled"',
    true
);
$table->data[5][2] = html_print_textarea(
    'command_recovery_preview',
    5,
    30,
    '',
    'disabled="disabled"',
    true
);

// Selector will work only with Integria activated.
$integriaIdName = 'integria_wu';
$table->data[$integriaIdName][0] = __('Create workunit on recovery').ui_print_help_tip(
    __('If closed status is set on recovery, a workunit will be added to the ticket in Integria IMS rather that closing the ticket.'),
    true
);
$table->data[$integriaIdName][1] = html_print_checkbox_switch_extended(
    'create_wu_integria',
    1,
    $create_wu_integria,
    false,
    '',
    $disabled_attr,
    true
);

for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
    $table->data['field'.$i][0] = html_print_image(
        'images/spinner.gif',
        true
    );
    $table->data['field'.$i][1] = html_print_image(
        'images/spinner.gif',
        true
    );
    $table->data['field'.$i][2] = html_print_image(
        'images/spinner.gif',
        true
    );

    // Store the value in a hidden to keep it on first execution
    $table->data['field'.$i][1] .= html_print_input_hidden(
        'field'.$i.'_value',
        (!empty($action['field'.$i]) || $action['field'.$i] == 0) ? $action['field'.$i] : '',
        true,
        '',
        $disabled_attr
    );
    $table->data['field'.$i][2] .= html_print_input_hidden(
        'field'.$i.'_recovery_value',
        (!empty($action['field'.$i.'_recovery']) || $action['field'.$i] == 0) ? $action['field'.$i.'_recovery'] : '',
        true,
        '',
        $disabled_attr
    );
}


echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_actions&pure='.$pure.'">';
$table_html = html_print_table($table, true);

echo $table_html;
if ($is_central_policies_on_node === false) {
    echo '<div class="action-buttons" style="width: '.$table->width.'">';
    if ($id) {
        html_print_input_hidden('id', $id);
        if (!$disabled) {
            html_print_input_hidden('update_action', 1);
            html_print_submit_button(
                __('Update'),
                'create',
                false,
                'class="sub upd"'
            );
        } else {
            echo '<div class="action-buttons" style="width: '.$table->width.'">';
            echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_actions">';
            html_print_submit_button(__('Back'), 'back', false, 'class="sub upd"');
            echo '</form>';
            echo '</div>';
        }
    } else {
        html_print_input_hidden('create_action', 1);
        html_print_submit_button(
            __('Create'),
            'create',
            false,
            'class="sub wand"'
        );
    }

    echo '</div>';
}

echo '</form>';

enterprise_hook('close_meta_frame');

ui_require_javascript_file('pandora_alerts');
ui_require_javascript_file('tiny_mce', 'include/javascript/tiny_mce/');
?>

<script type="text/javascript">
$(document).ready (function () {
    var original_command;
    var origicommand_descriptionnal_command;
    var integriaWorkUnitName = "<?php echo $integriaIdName; ?>";

    if (<?php echo (int) $id_command; ?>) {
        original_command = "<?php echo str_replace("\r\n", '<br>', addslashes(io_safe_output(alerts_get_alert_command_command($id_command)))); ?>";
        render_command_preview(original_command);
        command_description = "<?php echo str_replace("\r\n", '<br>', addslashes(io_safe_output(alerts_get_alert_command_description($id_command)))); ?>";
        
        render_command_description(command_description);
    }

    function ajax_get_integria_custom_fields(ticket_type_id, values, recovery_values) {
        var values = values || [];
        var recovery_values = recovery_values || [];
        var max_macro_fields = <?php echo $config['max_macro_fields']; ?>;

        if (ticket_type_id === null || ticket_type_id === '' || (Array.isArray(values) && values.length === 0 && Array.isArray(recovery_values) && recovery_values.length === 0)) {
            for (var i=8; i <= max_macro_fields; i++) {
                $('[name=field'+i+'_value\\[\\]').val('');
                $('[name=field'+i+'_recovery_value\\[\\]').val('');
            }
        }

        // On ticket type change, hide all table rows and inputs corresponding to custom fields, regardless of what its type is.
        for (var i=8; i <= max_macro_fields; i++) {
            $('[name=field'+i+'_value\\[\\]').hide();
            $('[name=field'+i+'_recovery_value\\[\\]').hide();
            $('#table_macros-field'+i).hide();
            $('[name=field'+i+'_value_container').hide();
            $('[name=field'+i+'_recovery_value_container').hide();
        }

        jQuery.post(
          "ajax.php",
          {
            page: "godmode/alerts/configure_alert_action",
            get_integria_ticket_custom_types: 1,
            ticket_type_id: ticket_type_id
          },
          function(data) {
            var max_macro_fields = <?php echo $config['max_macro_fields']; ?>;

            data.forEach(function(custom_field, key) {
                var custom_field_key = key+8; // Custom fields start from field 8.

                if (custom_field_key > max_macro_fields) {
                    return;
                }

                // Display field row for current input.
                var custom_field_row = $('#table_macros-field'+custom_field_key);
                custom_field_row.show();

                // Replace label text of field row for current input.
                var label_html = $('#table_macros-field'+custom_field_key+' td').first().html();
                var label_name = label_html.split('<br>')[0];
                var new_html_content = custom_field_row.html().replace(label_name, custom_field.label);
                custom_field_row.html(new_html_content);

                switch (custom_field.type) {
                    case 'checkbox':
                        var checkbox_selector = $('input:not(.datepicker)[name=field'+custom_field_key+'_value\\[\\]]');
                        var checkbox_recovery_selector = $('input:not(.datepicker)[name=field'+custom_field_key+'_recovery_value\\[\\]]');

                        checkbox_selector.on('change', function() {
                            if (checkbox_selector.prop('checked')) {
                                checkbox_selector.attr('value', "1");
                            } else {
                                checkbox_selector.attr('value', "0");
                            }
                        });

                        checkbox_recovery_selector.on('change', function() {
                            if (checkbox_recovery_selector.prop('checked')) {
                                checkbox_recovery_selector.attr('value', "1");
                            } else {
                                checkbox_recovery_selector.attr('value', "0");
                            }
                        });

                        if (typeof values[key] !== "undefined") {
                            if (values[key] == 1) {
                                checkbox_selector.prop('checked', true);
                                checkbox_selector.attr('value', "1");
                            } else {
                                checkbox_selector.prop('checked', false);
                                checkbox_selector.attr('value', "0");
                            }
                        }

                        if (typeof recovery_values[key] !== "undefined") {
                            if (recovery_values[key] == 1) {
                                checkbox_recovery_selector.prop('checked', true);
                                checkbox_recovery_selector.attr('value', "1");
                            } else {
                                checkbox_recovery_selector.prop('checked', false);
                                checkbox_recovery_selector.attr('value', "0");
                            }
                        }

                        $('[name=field'+custom_field_key+'_value_container]').show();
                        $('[name=field'+custom_field_key+'_recovery_value_container]').show();
                        $('input:not(.datepicker)[name=field'+custom_field_key+'_value\\[\\]]').show();
                        $('input:not(.datepicker)[name=field'+custom_field_key+'_recovery_value\\[\\]]').show();
                    break;
                    case 'combo':
                        var combo_input = $('select[name=field'+custom_field_key+'_value\\[\\]]');
                        var combo_input_recovery = $('select[name=field'+custom_field_key+'_recovery_value\\[\\]]');

                        combo_input.find('option').remove();
                        combo_input_recovery.find('option').remove();

                        var combo_values_array = custom_field.combo_value.split(',');
                        
                        combo_values_array.forEach(function(value) {
                            combo_input.append($('<option>', {
                                value: value,
                                text: value
                            }));

                            combo_input_recovery.append($('<option>', {
                                value: value,
                                text: value
                            }));
                        });

                        if (typeof values[key] !== "undefined") {
                            combo_input.val(values[key]);
                        }

                        if (typeof recovery_values[key] !== "undefined") {
                            combo_input_recovery.val(recovery_values[key]);
                        }

                        combo_input.show();
                        combo_input_recovery.show();
                    break;
                    case 'date':
                        $('input.datepicker[type="text"][name=field'+custom_field_key+'_value\\[\\]]').removeClass("hasDatepicker");
                        $('input.datepicker[type="text"][name=field'+custom_field_key+'_recovery_value\\[\\]]').removeClass("hasDatepicker");
                        $('input.datepicker[type="text"][name=field'+custom_field_key+'_value\\[\\]]').datepicker("destroy");
                        $('input.datepicker[type="text"][name=field'+custom_field_key+'_recovery_value\\[\\]]').datepicker("destroy");

                        $('input.datepicker[type="text"][name=field'+custom_field_key+'_value\\[\\]]').show();
                        $('input.datepicker[type="text"][name=field'+custom_field_key+'_recovery_value\\[\\]]').show();
                        $('input.datepicker[type="text"][name=field'+custom_field_key+'_value\\[\\]]').datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
                        $('input.datepicker[type="text"][name=field'+custom_field_key+'_recovery_value\\[\\]]').datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
                        $.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);

                        if (typeof values[key] !== "undefined") {
                            $('input.datepicker[type="text"][name=field'+custom_field_key+'_value\\[\\]]').val(values[key]);
                        }

                        if (typeof recovery_values[key] !== "undefined") {
                            $('input.datepicker[type="text"][name=field'+custom_field_key+'_recovery_value\\[\\]]').val(recovery_values[key]);
                        }
                    break;
                    case 'text':
                    case 'textarea':
                    case 'numeric':
                        if (typeof values[key] !== "undefined") {
                            $('textarea[name=field'+custom_field_key+'_value\\[\\]]').val(values[key]);
                        }

                        if (typeof recovery_values[key] !== "undefined") {
                            $('textarea[name=field'+custom_field_key+'_recovery_value\\[\\]]').val(recovery_values[key]);
                        }

                        $('textarea[name=field'+custom_field_key+'_value\\[\\]]').show();
                        $('textarea[name=field'+custom_field_key+'_recovery_value\\[\\]]').show();
                    break;
                }
            });
          },
          "json"
        );
    }

    $("#id_command").change (function () {
        values = Array ();
        values.push({
            name: "page",
            value: "godmode/alerts/alert_commands"});
        values.push({
            name: "get_alert_command",
            value: "1"});
        values.push({
            name: "id",
            value: this.value});
        
        jQuery.post (<?php echo "'".ui_get_full_url('ajax.php', false, false, false)."'"; ?>,
            values,
            function (data, status) {
                original_command = data["command"];
                render_command_preview (original_command);
                command_description = data["description"];
                if (command_description != undefined) {
                    render_command_description(command_description);
                } else {
                    render_command_description('');

                }
                
                // Allow create workunit if Integria IMS Ticket is selected.
                if (data['id'] == '14') {
                    $("#table_macros-"+integriaWorkUnitName).css('display', 'table-row');
                } else {
                    $("#table_macros-"+integriaWorkUnitName).css('display', 'none');
                }

                var max_fields = parseInt('<?php echo $config['max_macro_fields']; ?>');
                
                // Change the selected group
                $("#group option").each(function(index, value) {
                    var current_group = $(value).val();
                });
                if (data.id_group != 0 && $("#group").val() != data.id_group) {
                    $("#group").val(0);
                }

                var integria_custom_fields_values = [];
                var integria_custom_fields_rvalues = [];

                for (i = 1; i <= max_fields; i++) {
                    var old_value = '';
                    var old_recovery_value = '';
                    var disabled = '';
                    var field_row = data["fields_rows"][i];
                    var $table_macros_field = $('#table_macros-field' + i);
                    
                    // If the row is empty, hide it
                    if (field_row == '') {
                        $table_macros_field.hide();
                        continue;
                    }
                    old_value = '';
                    old_recovery_value = '';
                    // Only keep the value if is provided from hidden (first time)
                    if (($("[name=field" + i + "_value]").attr('id'))
                        == ("hidden-field" + i + "_value")) {
                        
                        old_value = $("[name=field" + i + "_value]").val();
                        disabled = $("[name=field" + i + "_value]").attr('disabled');
                    }
                    
                    if (($("[name=field" + i + "_recovery_value]").attr('id'))
                        == ("hidden-field" + i + "_recovery_value")) {
                        
                        old_recovery_value =
                            $("[name=field" + i + "_recovery_value]").val();
                    }
                    
                    // Replace the old column with the new
                    $table_macros_field.replaceWith(field_row);
                    if (old_value != '' || old_recovery_value != '') {
                        var inputType = $("[name=field" + i + "_value]").attr('type')
                        if (inputType == 'radio') {
                            if(old_value == 'text/plain'){
                                if ($("[name=field" + i + "_value]").val() == 'text/plain') {
                                    $("[name=field" + i + "_value]").attr('checked','checked');
                                }
                            }
                            else{
                                if($("[name=field" + i + "_value]").val() == 'text/html') {
                                    $("[name=field" + i + "_value]").attr('checked','checked');
                                }
                            }
                            if(old_recovery_value == 'text/plain'){
                                if ($("[name=field" + i + "_recovery_value]").val() == 'text/plain') {
                                    $("[name=field" + i + "_recovery_value]").attr('checked','checked');
                                }
                            }
                            else{
                                if ($("[name=field" + i + "_recovery_value]").val() == 'text/html') {
                                    $("[name=field" + i + "_recovery_value]").attr('checked','checked');
                                }
                            }
                        }
                        else {
                            $("[name=field" + i + "_value]").val(old_value);
                            $("[name=field" + i + "_recovery_value]").val(old_recovery_value);
                        }
                    }
                    else {
                        if ($("[name=field" + i + "_value]").val() != 'text/plain') {
                            $("[name=field" + i + "_value]")
                                .val($("[name=field" + i + "_value]")
                                .val());
                            $("[name=field" + i + "_recovery_value]")
                                .val($("[name=field" + i + "_recovery_value]")
                                .val());
                        }
                    }

                    if ($("#id_command option:selected").text() === "Integria IMS Ticket" && i > 7) {
                        integria_custom_fields_values.push(old_value);
                        integria_custom_fields_rvalues.push(old_recovery_value);
                    }

                    // Add help hint only in first field
                    if (i == 1) {
                        var td_content = $table_macros_field.find('td').eq(0);
                        
                        $(td_content)
                            .html(
                                $(td_content).html() +
                            $('#help_alert_macros_hint').html());
                    }
                    
                    if (disabled) {
                        $("[name=field" + i + "_value]").attr('disabled','disabled');
                        $("[name=field" + i + "_recovery_value]").attr('disabled','disabled');
                    }
                    $table_macros_field.show();
                }

                // Ad-hoc solution for Integria IMS command: get Integia IMS Ticket custom fields only when this command is selected and we selected a ticket type to retrieve fields from.
                // Check command by name since it is unvariable in any case, unlike its ID.
                if ($("#id_command option:selected").text() === "Integria IMS Ticket") {
                    var max_macro_fields = <?php echo $config['max_macro_fields']; ?>;

                    // At start hide all rows and inputs corresponding to custom fields, regardless of what its type is.
                    for (var i=8; i <= max_macro_fields; i++) {
                        $('[name=field'+i+'_value\\[\\]').hide();
                        $('[name=field'+i+'_recovery_value\\[\\]').hide();
                        $('#table_macros-field'+i).hide();
                        $('[name=field'+i+'_value_container').hide();
                        $('[name=field'+i+'_recovery_value_container').hide();
                    }

                    if ($('#field5_value').val() !== '') {
                        ajax_get_integria_custom_fields($('#field5_value').val(), integria_custom_fields_values, integria_custom_fields_rvalues);
                    }

                    $('#field5_value').on('change', function() {
                        ajax_get_integria_custom_fields($(this).val());
                    }); 
                }

                var added_config = {
                    "selector": "textarea.tiny-mce-editor",
                    "plugins": "preview, print, table, searchreplace, nonbreaking, xhtmlxtras, noneditable",
                    "theme_advanced_buttons1": "bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect",
                    "theme_advanced_buttons2": "search,replace,|,bullist,numlist,|,undo,redo,|,link,unlink,image,|,cleanup,code,preview,|,forecolor,backcolor",
                    "valid_children": "+body[style]",
                    "width": "90%",
                }
                defineTinyMCE(added_config);

                render_command_preview(original_command);
                render_command_recovery_preview(original_command);
                
                $(".fields").keyup(function() {
                    render_command_preview(original_command);
                });
                $(".fields_recovery").keyup(function() {
                    render_command_recovery_preview(original_command);
                });
                $("select.fields").change(function() {
                    render_command_preview(original_command);
                });
                $("select.fields_recovery").change(function() {
                    render_command_recovery_preview(original_command);
                });
            },
            "json"
        );
    }).change();
});

</script>
