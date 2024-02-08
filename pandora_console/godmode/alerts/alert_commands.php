<?php
/**
 * Alerts commands.
 *
 * @category   Alerts
 * @package    Pandora FMS
 * @subpackage Opensource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

use PandoraFMS\ITSM\ITSM;

// Load global vars.
global $config;

require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_reports.php';
enterprise_include_once('meta/include/functions_alerts_meta.php');

check_login();

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
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
$content_type = (string) get_parameter('content_type', 'text/plain');

$url = 'index.php?sec='.$sec.'&sec2=godmode/alerts/alert_commands';

if (is_ajax()) {
    $get_alert_command = (bool) get_parameter('get_alert_command');
    if ($get_alert_command) {
        $id = (int) get_parameter('id', 0);
        $get_recovery_fields = (int) get_parameter('get_recovery_fields', 1);

        // Snmp alerts are not in the metaconsole so they cannot be centralized.
        $management_is_not_allowed = false;
        if ($get_recovery_fields !== 0) {
            $management_is_not_allowed = !is_management_allowed();
        }

        // If command ID is not provided, check for action id.
        if ($id == 0) {
            $id_action = (int) get_parameter('id_action');
            $id = alerts_get_alert_action_alert_command_id($id_action);
        }

        $command = alerts_get_alert_command($id);

        // If a description is set, change the carriage return by <br> tags.
        if (isset($command['description'])) {
            $command['description'] = str_replace(
                [
                    '<',
                    '>',
                    "\r\n",
                ],
                [
                    '',
                    '',
                    '<br>',
                ],
                io_safe_output($command['description'])
            );
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
                if (($i > 5) && ($command['id'] === 3)) {
                    $fdesc = $field_description.' <br><span class="normal xx-small">'.sprintf(
                        __('Field %s'),
                        ($i - 1)
                    ).'</span>';
                } else {
                    $fdesc = $field_description.' <br><span class="normal xx-small">'.sprintf(
                        __('Field %s'),
                        $i
                    ).'</span>';
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
                if (($i > 5) && ($command['id'] === 3)) {
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

            $style = ((int) $field_hidden === 1) ? '-webkit-text-security: disc; font-family: text-security-disc;' : '';

            $recovery_disabled = 0;
            if (empty($command) === false && $command['name'] === io_safe_input('Pandora ITSM Ticket')) {
                if ($management_is_not_allowed == 0) {
                    if (preg_match('/^_html_editor_$/i', $field_value) || $field_description === 'Ticket status') {
                        $recovery_disabled = 0;
                    } else {
                        $recovery_disabled = 1;
                    }
                }
            }

            if (!empty($field_value)) {
                $field_value = io_safe_output($field_value);
                // HTML type.
                if (preg_match('/^_html_editor_$/i', $field_value)) {
                    $editor_type_chkbx = '<div id="command_div"><b><small>';
                    $editor_type_chkbx .= __('Basic');
                    $editor_type_chkbx .= ui_print_help_tip(
                        __('For sending emails, text must be HTML format, if you want to use plain text, type it between the following labels: <pre></pre>'),
                        true
                    );
                    $editor_type_chkbx .= html_print_radio_button_extended(
                        'editor_type_value_'.$i,
                        0,
                        '',
                        false,
                        $management_is_not_allowed,
                        "UndefineTinyMCE('#textarea_field".$i."_value')",
                        '',
                        true
                    );
                    $editor_type_chkbx .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    $editor_type_chkbx .= __('Advanced').'&nbsp;&nbsp;';
                    $editor_type_chkbx .= html_print_radio_button_extended(
                        'editor_type_value_'.$i,
                        0,
                        '',
                        true,
                        $management_is_not_allowed,
                        "defineTinyMCE('#textarea_field".$i."_value')",
                        '',
                        true
                    );
                    $editor_type_chkbx .= '</small></b></div>';
                    $ffield = $editor_type_chkbx;
                    $ffield .= html_print_textarea(
                        'field'.$i.'_value',
                        5,
                        1,
                        '',
                        'class="fields w100p"',
                        true,
                        '',
                        $management_is_not_allowed
                    );

                    $editor_type_chkbx = '<div id="command_div"><b><small>';
                    $editor_type_chkbx .= __('Basic').'&nbsp;&nbsp;';
                    $editor_type_chkbx .= html_print_radio_button_extended(
                        'editor_type_recovery_value_'.$i,
                        0,
                        '',
                        false,
                        $management_is_not_allowed,
                        "UndefineTinyMCE('#textarea_field".$i."_recovery_value')",
                        '',
                        true
                    );
                    $editor_type_chkbx .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    $editor_type_chkbx .= __('Advanced').'&nbsp;&nbsp;';
                    $editor_type_chkbx .= html_print_radio_button_extended(
                        'editor_type_recovery_value_'.$i,
                        0,
                        '',
                        true,
                        $management_is_not_allowed,
                        "defineTinyMCE('#textarea_field".$i."_recovery_value')",
                        '',
                        true
                    );
                    $editor_type_chkbx .= '</small></b></div>';
                    $rfield = $editor_type_chkbx;
                    $rfield .= html_print_textarea(
                        'field'.$i.'_recovery_value',
                        5,
                        1,
                        '',
                        'class="fields_recovery"',
                        true,
                        '',
                        $management_is_not_allowed || $recovery_disabled
                    );
                } else if (preg_match('/^_content_type_$/i', $field_value)) {
                    $editor_type_chkbx = '<div id="command_div"><b><small>';
                    $editor_type_chkbx .= __('Text/plain');
                    $editor_type_chkbx .= ui_print_help_tip(
                        __('For sending emails only text plain'),
                        true
                    );
                    $editor_type_chkbx .= html_print_radio_button_extended(
                        'field'.$i.'_value',
                        'text/plain',
                        '',
                        '',
                        $management_is_not_allowed,
                        '',
                        '',
                        true
                    );
                    $editor_type_chkbx .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    $editor_type_chkbx .= __('Text/html').'&nbsp;&nbsp;';
                    $editor_type_chkbx .= html_print_radio_button_extended(
                        'field'.$i.'_value',
                        'text/html',
                        '',
                        'text/html',
                        $management_is_not_allowed,
                        '',
                        '',
                        true
                    );
                    $editor_type_chkbx .= '</small></b></div>';
                    $ffield = $editor_type_chkbx;

                    $editor_type_chkbx = '<div id="command_div"><b><small>';
                    $editor_type_chkbx .= __('Text/plain');
                    $editor_type_chkbx .= ui_print_help_tip(
                        __('For sending emails only text plain'),
                        true
                    );
                    $editor_type_chkbx .= html_print_radio_button_extended(
                        'field'.$i.'_recovery_value',
                        'text/plain',
                        '',
                        '',
                        $management_is_not_allowed,
                        '',
                        '',
                        true
                    );
                    $editor_type_chkbx .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    $editor_type_chkbx .= __('Text/html').'&nbsp;&nbsp;';
                    $editor_type_chkbx .= html_print_radio_button_extended(
                        'field'.$i.'_recovery_value',
                        'text/html',
                        '',
                        'text/html',
                        $management_is_not_allowed,
                        '',
                        '',
                        true
                    );
                    $editor_type_chkbx .= '</small></b></div>';
                    $rfield = $editor_type_chkbx;
                    // Select type.
                } else if (preg_match('/^_custom_field_ITSM_$/i', $field_value)) {
                    $ffield = '';
                    $rfield = '';

                    $ffield .= '<div name="field'.$i.'_value_container">'.html_print_switch(
                        [
                            'name'  => 'field'.$i.'_value[]',
                            'value' => '',
                        ]
                    ).'</div>';
                    $rfield .= '<div name="field'.$i.'_recovery_value_container">'.html_print_switch(
                        [
                            'name'     => 'field'.$i.'_recovery_value[]',
                            'value'    => '',
                            'disabled' => $management_is_not_allowed || $recovery_disabled,
                        ]
                    ).'</div>';

                    $ffield .= html_print_select(
                        '',
                        'field'.$i.'_value[]',
                        '',
                        '',
                        __('None'),
                        '',
                        true,
                        false,
                        false,
                        'fields',
                        $management_is_not_allowed,
                        'width: 100%;',
                        false,
                        false,
                        false,
                        '',
                        false,
                        false,
                        false,
                        false,
                        false
                    );

                    $rfield .= html_print_select(
                        '',
                        'field'.$i.'_recovery_value[]',
                        '',
                        '',
                        __('None'),
                        '',
                        true,
                        false,
                        false,
                        'fields',
                        $management_is_not_allowed || $recovery_disabled,
                        'width: 100%;',
                        false,
                        false,
                        false,
                        '',
                        false,
                        false,
                        false,
                        false,
                        false
                    );

                    $ffield .= html_print_input_text(
                        'field'.$i.'_value[]',
                        '',
                        '',
                        50,
                        50,
                        true,
                        false,
                        false,
                        '',
                        'datepicker',
                        '',
                        'off',
                        false,
                        '',
                        '',
                        '',
                        $management_is_not_allowed
                    );
                    $rfield .= html_print_input_text(
                        'field'.$i.'_recovery_value[]',
                        '',
                        '',
                        50,
                        50,
                        true,
                        false,
                        false,
                        '',
                        'datepicker',
                        '',
                        'off',
                        false,
                        '',
                        '',
                        '',
                        $management_is_not_allowed || $recovery_disabled
                    );

                    $ffield .= html_print_textarea(
                        'field'.$i.'_value[]',
                        5,
                        1,
                        '',
                        'style="min-height:40px; '.$style.'" class="fields"',
                        true,
                        '',
                        $management_is_not_allowed
                    );

                    $rfield .= html_print_textarea(
                        'field'.$i.'_recovery_value[]',
                        5,
                        1,
                        '',
                        'style="min-height:40px; '.$style.'" class="fields_recovery',
                        true,
                        '',
                        $management_is_not_allowed || $recovery_disabled
                    );

                    $values_input_number = [
                        'name'   => 'field'.$i.'_value[]',
                        'value'  => 0,
                        'id'     => 'field'.$i.'_value',
                        'return' => true,
                    ];

                    if ($management_is_not_allowed === true) {
                        $values_input_number['disabled'] = true;
                    }

                    $ffield .= html_print_input_number($values_input_number);

                    $values_input_number_recovery = [
                        'name'   => 'field'.$i.'_recovery_value[]',
                        'value'  => 0,
                        'id'     => 'field'.$i.'_recovery_value',
                        'return' => true,
                    ];

                    if ($management_is_not_allowed || $recovery_disabled) {
                        $values_input_number_recovery['disabled'] = true;
                    }

                    $rfield .= html_print_input_number($values_input_number_recovery);

                    $ffield .= html_print_input_text(
                        'field'.$i.'_value[]',
                        '',
                        '',
                        50,
                        255,
                        true,
                        false,
                        false,
                        '',
                        'normal w98p',
                        '',
                        'off',
                        false,
                        false,
                        '',
                        '',
                        $management_is_not_allowed
                    );
                    $rfield .= html_print_input_text(
                        'field'.$i.'_recovery_value[]',
                        '',
                        '',
                        50,
                        255,
                        true,
                        false,
                        false,
                        '',
                        'normal w98p',
                        '',
                        'off',
                        false,
                        false,
                        '',
                        '',
                        $management_is_not_allowed || $recovery_disabled
                    );
                } else if (str_starts_with($field_value, '_ITSM_')) {
                    $nothing = '';
                    $nothing_value = 0;
                    $mode = 'select';
                    switch ($field_value) {
                        case '_ITSM_groups_':
                            $fields_array = [];
                            try {
                                $ITSM = new ITSM();
                                $fields_array = $ITSM->getGroups();
                            } catch (\Throwable $th) {
                                $error = $th->getMessage();
                                $fields_array = [];
                            }
                        break;

                        case '_ITSM_priorities_':
                            $fields_array = [];
                            try {
                                $ITSM = new ITSM();
                                $fields_array = $ITSM->getPriorities();
                            } catch (\Throwable $th) {
                                $error = $th->getMessage();
                                $fields_array = [];
                            }
                        break;

                        case '_ITSM_types_':
                            $fields_array = [];
                            try {
                                $ITSM = new ITSM();
                                $fields_array = $ITSM->getObjectypes();
                            } catch (\Throwable $th) {
                                $error = $th->getMessage();
                                $fields_array = [];
                            }

                            $nothing = __('None');
                            $nothing_value = 0;
                        break;

                        case '_ITSM_status_':
                            $fields_array = [];
                            try {
                                $ITSM = new ITSM();
                                $fields_array = $ITSM->getStatus();
                            } catch (\Throwable $th) {
                                $error = $th->getMessage();
                                $fields_array = [];
                            }
                        break;

                        default:
                            // Nothing.
                            $mode = '';
                        break;
                    }

                    if ($mode === 'select') {
                        $ffield = html_print_select(
                            $fields_array,
                            'field'.$i.'_value',
                            '',
                            '',
                            $nothing,
                            $nothing_value,
                            true,
                            false,
                            false,
                            'fields',
                            $management_is_not_allowed
                        );

                        $rfield = html_print_select(
                            $fields_array,
                            'field'.$i.'_recovery_value',
                            '',
                            '',
                            $nothing,
                            $nothing_value,
                            true,
                            false,
                            false,
                            'fields_recovery',
                            $management_is_not_allowed || $recovery_disabled
                        );
                    } else {
                        $ffield = html_print_autocomplete_users_from_pandora_itsm(
                            'field'.$i.'_value',
                            '',
                            true,
                            0,
                            $management_is_not_allowed,
                            false,
                            'ITSM_users'
                        );

                        $rfield = html_print_autocomplete_users_from_pandora_itsm(
                            'field'.$i.'_recovery_value',
                            '',
                            true,
                            0,
                            $management_is_not_allowed || $recovery_disabled,
                            false,
                            'ITSM_users'
                        );
                    }
                } else {
                    $fields_value_select = [];
                    $force_print_select = false;

                    // Exception for dynamically filled select boxes.
                    if (preg_match('/^_reports_$/i', $field_value)) {
                        // Filter normal and metaconsole reports.
                        if (is_metaconsole() === true) {
                            $filter['metaconsole'] = 1;
                        } else {
                            $filter['metaconsole'] = 0;
                        }

                        $own_info = get_user_info($config['id_user']);
                        if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'RM') || check_acl($config['id_user'], 0, 'RR')) {
                            $return_all_group = true;
                        } else {
                            $return_all_group = false;
                        }

                        if (is_user_admin($config['id_user']) === false) {
                            $filter[] = sprintf(
                                'private = 0 OR (private = 1 AND id_user = "%s")',
                                $config['id_user']
                            );
                        }

                        $reports = reports_get_reports(
                            $filter,
                            [
                                'name',
                                'id_report',
                            ],
                            $return_all_group,
                            'RR'
                        );

                        $fv = array_map(
                            function ($report) {
                                return $report['id_report'].','.$report['name'];
                            },
                            $reports
                        );

                        $force_print_select = true;
                    } else if (preg_match('/^_report_templates_$/i', $field_value)) {
                        // Filter normal and metaconsole reports.
                        if (is_metaconsole() === true) {
                            $filter['metaconsole'] = 1;
                        } else {
                            $filter['metaconsole'] = 0;
                        }

                        $own_info = get_user_info($config['id_user']);
                        if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'RM') || check_acl($config['id_user'], 0, 'RR')) {
                            $return_all_group = true;
                        } else {
                            $return_all_group = false;
                        }

                        if (is_user_admin($config['id_user']) === false) {
                            $filter[] = sprintf(
                                'private = 0 OR (private = 1 AND id_user = "%s")',
                                $config['id_user']
                            );
                        }

                        $templates = reports_get_report_templates(
                            $filter,
                            [
                                'name',
                                'id_report',
                            ],
                            $return_all_group,
                            'RR'
                        );

                        $fv = array_map(
                            function ($template) {
                                return $template['id_report'].','.$template['name'];
                            },
                            $templates
                        );

                        $force_print_select = true;
                    } else {
                        $fv = explode(';', $field_value);
                    }

                    if (count($fv) > 1 || $force_print_select === true) {
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
                            __('None'),
                            '',
                            true,
                            false,
                            false,
                            'fields',
                            $management_is_not_allowed
                        );
                        $rfield = html_print_select(
                            $fields_value_select,
                            'field'.$i.'_recovery_value',
                            '',
                            '',
                            __('None'),
                            0,
                            true,
                            false,
                            false,
                            'fields_recovery',
                            $management_is_not_allowed || $recovery_disabled
                        );
                    } else {
                        $ffield = html_print_textarea(
                            'field'.$i.'_value',
                            5,
                            1,
                            $fv[0],
                            'style="'.$style.'" class="fields min-height-40px w100p"',
                            true,
                            '',
                            $management_is_not_allowed
                        );
                        $rfield = html_print_textarea(
                            'field'.$i.'_recovery_value',
                            5,
                            1,
                            $fv[0],
                            'style="'.$style.'" class="fields_recovery min-height-40px w100p',
                            true,
                            '',
                            $management_is_not_allowed || $recovery_disabled
                        );
                    }
                }
            } else {
                $ffield = html_print_textarea(
                    'field'.$i.'_value',
                    5,
                    1,
                    '',
                    'style="'.$style.'" class="fields min-height-40px w100p"',
                    true,
                    '',
                    $management_is_not_allowed
                );
                $rfield = html_print_textarea(
                    'field'.$i.'_recovery_value',
                    5,
                    1,
                    '',
                    'style="'.$style.'" class="fields_recovery min-height-40px w100p"',
                    true,
                    '',
                    $management_is_not_allowed || $recovery_disabled
                );
            }


            // The empty descriptions will be ignored.
            if ($fdesc == '') {
                $fields_rows[$i] = '';
            } else {
                $fields_rows[$i] = '<tr id="table_macros-field'.$i.'" class="datos">';
                $fields_rows[$i] .= '<td class="datos bolder w20p" style="font-size: 13px;">'.$fdesc.'</td>';
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

// This check should be after ajax. Because, ajax will be called from configure_alert_action.
if (!check_acl($config['id_user'], 0, 'PM') && !is_user_admin(
    $config['id_user
']
)
) {
    echo "<div id='message_permissions'  title='".__('Permissions warning')."' s
tyle='display:none;'>";
    echo "<p style='text-align: center;font-weight: bold; margin: 15px'>".__(
        'Command management is limited to administrator users or user profiles with permissions PM'
    ).'</p>';
    echo '</div>';
}

if ($update_command) {
    include_once 'configure_alert_command.php';
    return;
}

// Header.
if (defined('METACONSOLE')) {
    alerts_meta_print_header();
} else {
    ui_print_standard_header(
        __('Alerts'),
        'images/gm_alerts.png',
        false,
        '',
        true,
        [],
        [
            [
                'link'  => '',
                'label' => __('Alert commands'),
            ],
        ]
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
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Create alert command #'.$result,
            false,
            false,
            $info
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Fail try to create alert command',
            false,
            false
        );
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
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Alert Management'
        );
        include 'general/noaccess.php';
        return;
    }

    $result = alerts_delete_alert_command($id);

    $auditMessage = ((bool) $result === true) ? sprintf('Delete alert command #%s', $id) : sprintf('Fail try to delete alert command #%s', $id);

    db_pandora_audit(
        AUDIT_LOG_ALERT_MANAGEMENT,
        $auditMessage
    );

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

$is_management_allowed = is_management_allowed();
if ($is_management_allowed === false) {
    if (is_metaconsole() === false) {
        $url_redirect = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=godmode/alerts/alert_commands&tab=command&pure=0'
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url_redirect = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All alert commands information is read only. Go to %s to manage it.',
            $url_redirect
        )
    );
}

$table = new stdClass;
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

// Pagination.
$total_commands = count($commands);
$offset = (int) get_parameter('offset');
$limit = (int) $config['block_size'];
$commands = array_slice($commands, $offset, $limit);

foreach ($commands as $command) {
    $data = [];

    if ((isset($config['ITSM_enabled']) === false || (bool) $config['ITSM_enabled'] === false)
        && $command['name'] === 'Pandora&#x20;ITSM&#x20;Ticket'
    ) {
        continue;
    }

    $data['name'] = '<span>';

    // (IMPORTANT, DO NOT CHANGE!) only users with permissions over "All" group have access to edition of commands belonging to "All" group.
    if (!$command['internal'] && check_acl_restricted_all($config['id_user'], $command['id_group'], 'PM')) {
        $data['name'] .= '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_command&id='.$command['id'].'&pure='.$pure.'">'.$command['name'].'</a>';
    } else {
        $data['name'] .= $command['name'];
    }

    $data['name'] .= '</span>';
    $data['id'] = $command['id'];
    $data['group'] = ui_print_group_icon($command['id_group'], true);
    $data['description'] = str_replace(
        [
            '<',
            '>',
            "\r\n",
        ],
        [
            '',
            '',
            '<br>',
        ],
        io_safe_output($command['description'])
    );
    $data['action'] = '';
    $table->cellclass[]['action'] = 'table_action_buttons';
    $offset_delete = ($offset >= ($total_commands - 1)) ? ($offset - $limit) : $offset;

    // (IMPORTANT, DO NOT CHANGE!) only users with permissions over "All" group have access to edition of commands belonging to "All" group.
    if ($is_management_allowed === true && !$command['internal'] && check_acl_restricted_all($config['id_user'], $command['id_group'], 'LM')) {
        if (is_user_admin($config['id_user']) === true) {
            $data['action'] = '<span class="inline_flex">';
            $data['action'] .= '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_commands&amp;copy_command=1&id='.$command['id'].'&pure='.$pure.'&offset='.$offset.'"
            onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.html_print_image('images/copy.svg', true, ['class' => 'main_menu_icon invert_filter ', 'title' => 'Duplicate']).'</a>';

            $data['action'] .= '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_commands&delete_command=1&id='.$command['id'].'&pure='.$pure.'&offset='.$offset_delete.'"
			onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.html_print_image('images/delete.svg', true, ['class' => 'main_menu_icon invert_filter', 'title' => 'Delete']).'</a>';
            $data['action'] .= '</span>';
        }
    }

    array_push($table->data, $data);
}

if (isset($data) === true && count($table->data) > 0) {
    html_print_table($table);
    $show_count = false;
    if (is_metaconsole() === true) {
        $show_count = true;
    }

    $pagination = ui_pagination($total_commands, $url, 0, 0, true, 'offset', $show_count, '');
} else {
    ui_print_info_message(
        [
            'no_close' => true,
            'message'  => __('No alert commands configured'),
        ]
    );
}

// Commands can only be created by the super administrator.
if (users_is_admin() === true) {
    echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_command&pure='.$pure.'">';
    $buttonSubmit = html_print_submit_button(
        __('Create'),
        'create',
        false,
        ['icon' => 'wand'],
        true
    );
    html_print_input_hidden('create_alert', 1);
    html_print_action_buttons($buttonSubmit, ['right_content' => $pagination]);
    echo '</form>';
}

?>

<script type="text/javascript">
    $(document).ready(function () {
        dialog_message("#message_permissions");
    });

    function dialog_message(message) {
    $(message)
        .css("display", "inline")
        .dialog({
            modal: true,
            width: "400px",
            buttons: {
                Close: function() {
                $(this).dialog("close");
                }
            }
        });
    }

</script>
