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
// Load global vars.
global $config;

require_once $config['homedir'].'/include/functions_alerts.php';
enterprise_include_once('meta/include/functions_alerts_meta.php');

check_login();

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

if (is_metaconsole()) {
    $sec = 'advanced';
} else {
    $sec = 'galertas';
}

$pure = (int) get_parameter('pure', 0);
$update_command = (bool) get_parameter('update_command');
$create_command = (bool) get_parameter('create_command');
$delete_command = (bool) get_parameter('delete_command');
$copy_command = (bool) get_parameter('copy_command');

if (is_ajax()) {
    $get_alert_command = (bool) get_parameter('get_alert_command');
    if ($get_alert_command) {
        $id = (int) get_parameter('id', 0);
        $get_recovery_fields = (int) get_parameter('get_recovery_fields', 1);

        // If command ID is not provided, check for action id.
        if ($id == 0) {
            $id_action = (int) get_parameter('id_action');
            $id = alerts_get_alert_action_alert_command_id($id_action);
        }

        $command = alerts_get_alert_command($id);

        // If is setted a description, we change the carriage return by <br> tags
        if (isset($command['description'])) {
            $command['description'] = io_safe_input(str_replace("\r\n", '<br>', io_safe_output($command['description'])));
        }

        // Descriptions are stored in json.
        $fields_descriptions = empty($command['fields_descriptions']) ? '' : json_decode(io_safe_output($command['fields_descriptions']), true);
        // Fields values are stored in json.
        $fields_values = empty($command['fields_values']) ? '' : io_safe_output(json_decode($command['fields_values'], true));
        // Fields hidden conditions are stored in json.
        $fields_hidden_checked = empty($command['fields_hidden']) ? '' : io_safe_output(json_decode($command['fields_hidden'], true));

        $fields_rows = [];
        for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
            $field_description = $fields_descriptions[($i - 1)];
            $field_value = $fields_values[($i - 1)];
            $field_hidden = $fields_hidden_checked[($i - 1)];


            if (!empty($field_description)) {
                // If the value is 5,  this because severity in snmp alerts is not permit to show.
                if (($i > 5) && ($command['id'] == 3)) {
                    $fdesc = $field_description.' <br><span style="font-size:xx-small; font-weight:normal;">'.sprintf(__('Field %s'), ($i - 1)).'</span>';
                } else {
                    $fdesc = $field_description.' <br><span style="font-size:xx-small; font-weight:normal;">'.sprintf(__('Field %s'), $i).'</span>';
                }

                // If the field is the number one, print the help message.
                if ($i == 1) {
                    // If our context is snmpconsole, show snmp_alert helps.
                    if ((!isset($_SERVER['HTTP_REFERER'])) && ( preg_match('/snmp_alert/', $_SERVER['HTTP_REFERER']) > 0 )) {
                        $fdesc .= ui_print_help_icon('alert_config', true);
                    }
                }
            } else {
                // If the macro hasn't description and doesnt appear in command, set with empty description to dont show it.
                if (($i > 5) && ($command['id'] == 3)) {
                    if (substr_count($command['command'], '_field'.($i - 1).'_') > 0) {
                        $fdesc = sprintf(__('Field %s'), ($i - 1));
                    } else {
                        $fdesc = '';
                    }
                } else {
                    if (substr_count($command['command'], '_field'.$i.'_') > 0) {
                        $fdesc = sprintf(__('Field %s'), $i);
                    } else {
                        $fdesc = '';
                    }
                }
            }

            $style = ((int) $field_hidden === 1) ? '-webkit-text-security: disc;' : '';

            if (!empty($field_value)) {
                $field_value = io_safe_output($field_value);
                // HTML type.
                if (preg_match('/^_html_editor_$/i', $field_value)) {
                    $editor_type_chkbx = '<div style="padding: 4px 0px;"><b><small>';
                    $editor_type_chkbx .= __('Basic').ui_print_help_tip(__('For sending emails, text must be HTML format, if you want to use plain text, type it between the following labels: <pre></pre>'), true);
                    $editor_type_chkbx .= html_print_radio_button_extended('editor_type_value_'.$i, 0, '', false, false, "removeTinyMCE('textarea_field".$i."_value')", '', true);
                    $editor_type_chkbx .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    $editor_type_chkbx .= __('Advanced').'&nbsp;&nbsp;';
                    $editor_type_chkbx .= html_print_radio_button_extended('editor_type_value_'.$i, 0, '', true, false, "addTinyMCE('textarea_field".$i."_value')", '', true);
                    $editor_type_chkbx .= '</small></b></div>';
                    $ffield = $editor_type_chkbx;
                    $ffield .= html_print_textarea('field'.$i.'_value', 1, 1, '', 'class="fields"', true);

                    $editor_type_chkbx = '<div style="padding: 4px 0px;"><b><small>';
                    $editor_type_chkbx .= __('Basic').'&nbsp;&nbsp;';
                    $editor_type_chkbx .= html_print_radio_button_extended('editor_type_recovery_value_'.$i, 0, '', false, false, "removeTinyMCE('textarea_field".$i."_recovery_value')", '', true);
                    $editor_type_chkbx .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    $editor_type_chkbx .= __('Advanced').'&nbsp;&nbsp;';
                    $editor_type_chkbx .= html_print_radio_button_extended('editor_type_recovery_value_'.$i, 0, '', true, false, "addTinyMCE('textarea_field".$i."_recovery_value')", '', true);
                    $editor_type_chkbx .= '</small></b></div>';
                    $rfield = $editor_type_chkbx;
                    $rfield .= html_print_textarea('field'.$i.'_recovery_value', 1, 1, '', 'class="fields_recovery"', true);
                } else if (preg_match('/^_content_type_$/i', $field_value)) {
                    $editor_type_chkbx = '<div style="padding: 4px 0px;"><b><small>';
                    $editor_type_chkbx .= __('Text/plain').ui_print_help_tip(__('For sending emails only text plain'), true);
                    $editor_type_chkbx .= html_print_radio_button_extended('field'.$i.'_value', 'text/plain', '', '', false, '', '', true);
                    $editor_type_chkbx .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    $editor_type_chkbx .= __('Text/html').'&nbsp;&nbsp;';
                    $editor_type_chkbx .= html_print_radio_button_extended('field'.$i.'_value', 'text/html', '', 'text/html', false, '', '', true);
                    $editor_type_chkbx .= '</small></b></div>';
                    $ffield = $editor_type_chkbx;

                    $editor_type_chkbx = '<div style="padding: 4px 0px;"><b><small>';
                    $editor_type_chkbx .= __('Text/plain').ui_print_help_tip(__('For sending emails only text plain'), true);
                    $editor_type_chkbx .= html_print_radio_button_extended('field'.$i.'_recovery_value', 'text/plain', '', '', false, '', '', true);
                    $editor_type_chkbx .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    $editor_type_chkbx .= __('Text/html').'&nbsp;&nbsp;';
                    $editor_type_chkbx .= html_print_radio_button_extended('field'.$i.'_recovery_value', 'text/html', '', 'text/html', false, '', '', true);
                    $editor_type_chkbx .= '</small></b></div>';
                    $rfield = $editor_type_chkbx;
                    // Select type.
                } else {
                    $fields_value_select = [];
                    $fv = explode(';', $field_value);

                    if (count($fv) > 1) {
                        if (!empty($fv)) {
                            foreach ($fv as $fv_option) {
                                $fv_option = explode(',', $fv_option);

                                if (empty($fv_option)) {
                                    continue;
                                }

                                if (!isset($fv_option[1])) {
                                    $fv_option[1] = $fv_option[0];
                                }

                                $fields_value_select[$fv_option[0]] = $fv_option[1];
                            }
                        }

                        $ffield = html_print_select(
                            $fields_value_select,
                            'field'.$i.'_value',
                            '',
                            '',
                            '',
                            0,
                            true,
                            false,
                            false,
                            'fields'
                        );
                        $rfield = html_print_select(
                            $fields_value_select,
                            'field'.$i.'_recovery_value',
                            '',
                            '',
                            '',
                            0,
                            true,
                            false,
                            false,
                            'fields_recovery'
                        );
                    } else {
                        $ffield = html_print_textarea(
                            'field'.$i.'_value',
                            1,
                            1,
                            $fv[0],
                            'style="min-height:40px; '.$style.'" class="fields"',
                            true
                        );
                        $rfield = html_print_textarea(
                            'field'.$i.'_recovery_value',
                            1,
                            1,
                            $fv[0],
                            'style="min-height:40px; '.$style.'" class="fields_recovery',
                            true
                        );
                    }
                }
            } else {
                $ffield = html_print_textarea(
                    'field'.$i.'_value',
                    1,
                    1,
                    '',
                    'style="min-height:40px; '.$style.'" class="fields"',
                    true
                );
                $rfield = html_print_textarea(
                    'field'.$i.'_recovery_value',
                    1,
                    1,
                    '',
                    'style="min-height:40px; '.$style.'" class="fields_recovery"',
                    true
                );
            }


            // The empty descriptions will be ignored.
            if ($fdesc == '') {
                $fields_rows[$i] = '';
            } else {
                $fields_rows[$i] = '<tr id="table_macros-field'.$i.'" class="datos">';
                $fields_rows[$i] .= '<td style="font-weight:bold;width:20%" class="datos">'.$fdesc.'</td>';
                $fields_rows[$i] .= '<td class="datos">'.$ffield.'</td>';
                if ($get_recovery_fields) {
                    $fields_rows[$i] .= '<td class="datos recovery_col">'.$rfield.'</td>';
                }

                $fields_rows[$i] .= '</tr>';
            }
        }

        // If command is PandoraFMS event, field 5 must be empty because "severity" must be set by the alert.
        $command['fields_rows'] = $fields_rows;

        echo json_encode($command);
    }

    return;
}

enterprise_hook('open_meta_frame');

if ($update_command) {
    include_once 'configure_alert_command.php';
    return;
}

// Header.
if (defined('METACONSOLE')) {
    alerts_meta_print_header();
} else {
    ui_print_page_header(
        __('Alerts').' &raquo; '.__('Alert commands'),
        'images/gm_alerts.png',
        false,
        'alerts_command_tab',
        true
    );
}

if ($create_command) {
    $name = (string) get_parameter('name');
    $command = (string) get_parameter('command');
    $description = (string) get_parameter('description');
    $id_group = (string) get_parameter('id_group', 0);

    $fields_descriptions = [];
    $fields_values = [];
    $fields_hidden = [];
    $info_fields = '';
    $values = [];
    for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
        $fields_descriptions[] = (string) get_parameter('field'.$i.'_description');
        $fields_values[] = (string) get_parameter('field'.$i.'_values');
        $fields_hidden[] = get_parameter('field'.$i.'_hide');
        $info_fields .= ' Field'.$i.': '.$fields_values[($i - 1)];
    }

    $values['fields_values'] = io_json_mb_encode($fields_values);
    $values['fields_descriptions'] = io_json_mb_encode($fields_descriptions);
    $values['fields_hidden'] = io_json_mb_encode($fields_hidden);
    $values['description'] = $description;
    $values['id_group'] = $id_group;

    $name_check = db_get_value('name', 'talert_commands', 'name', $name);

    if (!$name_check) {
        $result = alerts_create_alert_command(
            $name,
            $command,
            $values
        );

        $info = '{"Name":"'.$name.'","Command":"'.$command.'","Description":"'.$description.' '.$info_fields.'"}';
    } else {
        $result = '';
    }

    if ($result) {
        db_pandora_audit('Command management', 'Create alert command #'.$result, false, false, $info);
    } else {
        db_pandora_audit('Command management', 'Fail try to create alert command', false, false);
    }

    // Show errors.
    if (!isset($messageAction)) {
        $messageAction = __('Could not be created');
    }

    if ($name == '') {
        $messageAction = __('No name specified');
    }

    if ($command == '') {
        $messageAction = __('No command specified');
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully created'),
        $messageAction
    );
}


if ($delete_command) {
    $id = (int) get_parameter('id');

    // Internal commands cannot be deleted.
    if (alerts_get_alert_command_internal($id)) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access Alert Management'
        );
        include 'general/noaccess.php';
        return;
    }

    $result = alerts_delete_alert_command($id);

    if ($result) {
        db_pandora_audit('Command management', 'Delete alert command #'.$id);
    } else {
        db_pandora_audit('Command management', 'Fail try to delete alert command #'.$id);
    }

    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Could not be deleted')
    );
}

if ($copy_command) {
    $id = (int) get_parameter('id');

    // Get the info from the source command.
    $command_to_copy = db_get_row('talert_commands', 'id', $id);
    if ($command_to_copy === false) {
        ui_print_error_message(__("Command with id $id does not found."));
    } else {
        // Prepare to insert the copy with same values.
        unset($command_to_copy['id']);
        $command_to_copy['name'] .= __(' (copy)');
        $result = db_process_sql_insert('talert_commands', $command_to_copy);

        // Print the result.
        ui_print_result_message(
            $result,
            __('Successfully copied'),
            __('Could not be copied')
        );
    }
}

$table->width = '100%';
$table->class = 'info_table';

$table->data = [];
$table->head = [];
$table->head['name'] = __('Name');
$table->head['id'] = __('ID');
$table->head['group'] = __('Group');
$table->head['description'] = __('Description');
$table->head['action'] = __('Actions');
$table->style = [];
$table->style['name'] = 'font-weight: bold';
$table->size = [];
$table->size['action'] = '40px';
$table->align = [];
$table->align['action'] = 'left';

$commands = db_get_all_rows_filter(
    'talert_commands',
    ['id_group' => array_keys(users_get_groups(false, 'LM'))]
);
if ($commands === false) {
    $commands = [];
}

foreach ($commands as $command) {
    $data = [];

    $data['name'] = '<span style="font-size: 7.5pt">';
    if (! $command['internal']) {
        $data['name'] .= '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_command&id='.$command['id'].'&pure='.$pure.'">'.$command['name'].'</a>';
    } else {
        $data['name'] .= $command['name'];
    }

    $data['name'] .= '</span>';
    $data['id'] = $command['id'];
    $data['group'] = ui_print_group_icon($command['id_group'], true);
    $data['description'] = str_replace(
        "\r\n",
        '<br>',
        io_safe_output($command['description'])
    );
    $data['action'] = '';
    $table->cellclass[]['action'] = 'action_buttons';
    if (! $command['internal']) {
        $data['action'] = '<span style="display: inline-flex">';
        $data['action'] .= '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_commands&amp;copy_command=1&id='.$command['id'].'&pure='.$pure.'"
			onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.html_print_image('images/copy.png', true).'</a>';
        $data['action'] .= '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_commands&delete_command=1&id='.$command['id'].'&pure='.$pure.'"
			onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.html_print_image('images/cross.png', true).'</a>';
        $data['action'] .= '</span>';
    }

    array_push($table->data, $data);
}

if (count($table->data) > 0) {
    html_print_table($table);
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('No alert commands configured') ]);
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_command&pure='.$pure.'">';
html_print_submit_button(__('Create'), 'create', false, 'class="sub next"');
html_print_input_hidden('create_alert', 1);
echo '</form>';
echo '</div>';

enterprise_hook('close_meta_frame');
