<?php
/**
 * Alerts details for agent.
 *
 * @category   Alert
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
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Alert View (In management section)'
    );
    include 'general/noaccess.php';
    exit;
}

enterprise_include_once('include/functions_policies.php');

$id_alert = get_parameter('id_alert', 0);
// ID given as parameter.
$alert = alerts_get_alert_agent_module($id_alert);
$template = alerts_get_alert_template($alert['id_alert_template']);
$actions = alerts_get_alert_agent_module_actions($id_alert);
$agent_alias = modules_get_agentmodule_agent_alias($alert['id_agent_module']);
$agent = modules_get_agentmodule_agent($alert['id_agent_module']);
$module_name = modules_get_agentmodule_name($alert['id_agent_module']);

// Default action.
$default_action = $template['id_alert_action'];
if ($default_action != 0) {
    $default_action = alerts_get_alert_action($default_action);
    $default_action['name'] .= ' ('.__('Default').')';
    $default_action['default'] = 1;
    $default_action['module_action_threshold'] = '0';
}

// Header.
ui_print_standard_header(
    __('Alert details'),
    'images/op_alerts.png',
    false,
    '',
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Alerts'),
        ],
    ]
);

// TABLE DETAILS.
$table_details = new stdClass;
$table_details->class = 'databox';
$table_details->width = '100%';
$table_details->size = [];
$table_details->data = [];
$table_details->style = [];
$table_details->style[0] = 'font-weight: bold;';
$data = [];

$data[0] = __('List alerts');
$data[1] = '<a class="size_7pt" href="index.php?sec=galertas&sec2=godmode/alerts/alert_list" title="'.__('List alerts').'"><b><span>'.__('List alerts').'</span></b></a>';
$table_details->data[] = $data;

$data[0] = __('Agent');
$data[1] = '<a class="size_7pt" href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$agent.'" title="'.$agent_alias.'"><b><span>'.$agent_alias.'</span></b></a>';
$table_details->data[] = $data;

$data[0] = __('Module');
$data[1] = $module_name;
$table_details->data[] = $data;

$data[0] = __('Template');
$data[1] = $template['name'].ui_print_help_tip($template['description'], true);
$table_details->data[] = $data;

$data[0] = __('Last fired');
$data[1] = ui_print_timestamp($alert['last_fired'], true);
$table_details->data[] = $data;

if ($alert['times_fired'] > 0) {
    $status = STATUS_ALERT_FIRED;
    $title = __('Alert fired').' '.$alert['times_fired'].' '.__('time(s)');
} else if ($alert['disabled'] > 0) {
    $status = STATUS_ALERT_DISABLED;
    $title = __('Alert disabled');
} else {
    $status = STATUS_ALERT_NOT_FIRED;
    $title = __('Alert not fired');
}

$data[0] = __('Status');
$data[1] = '<span class="mrg_r_5px">'.ui_print_status_image(
    $status,
    $title,
    true
).'</span>'.$title;
$table_details->data[] = $data;

$priorities = get_priorities();

$data[0] = __('Priority');
$data[1] = '<span title="'.$priorities[$template['priority']].'" class="'.get_priority_class($template['priority']).' span_priority">&nbsp</span>'.$priorities[$template['priority']];
$table_details->data[] = $data;

$data[0] = __('Stand by');
$data[1] = ($alert['standby'] == 1) ? __('Yes') : __('No');
$table_details->data[] = $data;

if (enterprise_installed() && $alert['id_policy_alerts'] != 0) {
    $policyInfo = policies_is_alert_in_policy2($alert['id'], false);
    if ($policyInfo === false) {
        $policy = __('N/A');
    } else {
        $img = 'images/policies_mc.png';

        $policy = '<a href="?sec=gmodules&amp;sec2=enterprise/godmode/policies/policies&amp;id='.$policyInfo['id'].'">';
        $policy .= html_print_image(
            $img,
            true,
            ['title' => $policyInfo['name']]
        );
        $policy .= '</a>';
    }

    $data[0] = __('Policy');
    $data[1] = $policy;
    $table_details->data[] = $data;
}

$table_conditions = new stdClass;
$table_conditions->class = 'databox';
$table_conditions->width = '100%';
$table_conditions->size = [];
$table_conditions->data = [];
$table_conditions->style = [];
$table_conditions->style[0] = 'font-weight: bold; width: 50%;';
$data = [];
$table_conditions->colspan[0][0] = 2;

switch ($template['type']) {
    case 'regex':
        if ($template['matches_value']) {
            $condition = __('The alert would fire when the value matches <span id="value"></span>');
        } else {
            $condition = __('The alert would fire when the value doesn\'t match <span id="value"></span>');
        }

        $condition = str_replace('<span id="value"></span>', $template['value'], $condition);
    break;

    case 'equal':
        $condition = __('The alert would fire when the value is <span id="value"></span>');
        $condition = str_replace('<span id="value"></span>', $template['value'], $condition);
    break;

    case 'not_equal':
        $condition = __('The alert would fire when the value is not <span id="value"></span>');
        $condition = str_replace('<span id="value"></span>', $template['value'], $condition);
    break;

    case 'max_min':
        if ($template['matches_value']) {
            $condition = __(
                'The alert would fire when the value is between <span id="min"></span> and <span id="max"></span>'
            );
        } else {
            $condition = __(
                'The alert would fire when the value is not between <span id="min"></span> and <span id="max"></span>'
            );
        }

        $condition = str_replace('<span id="min"></span>', $template['min_value'], $condition);
        $condition = str_replace('<span id="max"></span>', $template['max_value'], $condition);
    break;

    case 'max':
        $condition = __('The alert would fire when the value is below <span id="min"></span>');
        $condition = str_replace('<span id="min"></span>', $template['min_value'], $condition);
    break;

    case 'min':
        $condition = __('The alert would fire when the value is above <span id="max"></span>');
        $condition = str_replace('<span id="max"></span>', $template['max_value'], $condition);
    break;

    case 'onchange':
        if ($template['matches_value']) {
            $condition = __('The alert would fire when the module value changes');
        } else {
            $condition = __('The alert would fire when the module value does not change');
        }
    break;

    case 'warning':
        $condition = __('The alert would fire when the module is in warning status');
    break;

    case 'critical':
        $condition = __('The alert would fire when the module is in critical status');
    break;

    case 'not_normal':
        $condition = __('The alert would fire when the module is in not normal status');
    break;

    case 'unknown':
        $condition = __('The alert would fire when the module is in unknown status');
    break;

    case 'always':
        $condition = __('Always');
    break;

    default:
        // Not possible.
    break;
}

$data[0] = $condition;

$table_conditions->data[] = $data;

$table_conditions->colspan[1][0] = 2;
$schedule = io_safe_output(
    $template['schedule']
);

$data[0] = '';
$data[0] .= html_print_input_hidden('schedule', $schedule, true);
$data[0] .= '<div id="calendar_map"></div>';

$data[1] = '';
$table_conditions->data[] = $data;

$data[0] = __('Use special days list');
$data[1] = (isset($alert['special_day']) && $alert['special_day'] == 1) ? __('Yes') : __('No');
$table_conditions->data[] = $data;

$data[0] = __('Time threshold');
$data[1] = human_time_description_raw($template['time_threshold'], true);
$table_conditions->data[] = $data;

$data[0] = __('Number of alerts').' ('.__('Min').'/'.__('Max').')';
$data[1] = $template['min_alerts'].'/'.$template['max_alerts'];
$table_conditions->data[] = $data;

// TABLE CONDITIONS END.
$table = new stdClass;
$table->class = 'alert_list databox';
$table->width = '98%';
$table->size = [];
$table->head = [];
$table->data = [];
$table->style = [];
$table->style[0] = 'width: 50%;';

$table->head[0] = __('Alert details');
$table->head[1] = __('Firing conditions');

$table->data[0][0] = html_print_table($table_details, true);
$table->data[0][1] = html_print_table($table_conditions, true);

html_print_table($table);
unset($table);

$actions = alerts_get_actions_escalation($actions, $default_action);

// ESCALATION.
$table = new stdClass;
$table->class = 'alert_list databox alternate alert_escalation';
$table->width = '98%';
$table->size = [];
$table->head = [];
$table->data = [];
$table->styleTable = 'text-align: center;';

echo '<div class="firing_action_all w100p" >';
$table->head[0] = __('Actions');
$table->style[0] = 'font-weight: bold; text-align: left;';

if (count($actions) == 1 && isset($actions[0])) {
    $table->head[1] = __('Every time that the alert is fired');
    $table->data[0][0] = $actions[0]['name'];
    $table->data[0][1] = html_print_image(
        'images/tick.png',
        true,
        ['class' => 'invert_filter']
    );
} else {
    foreach ($actions as $kaction => $action) {
        $table->data[$kaction][0] = $action['name'];
        if ((int) $kaction === 0) {
            $table->data[$kaction][0] .= ui_print_help_tip(
                __('The default actions will be executed every time that the alert is fired and no other action is executed'),
                true
            );
        }

        foreach ($action['escalation'] as $k => $v) {
            if ($v > 0) {
                $table->data[$kaction][$k] = html_print_image(
                    'images/tick.png',
                    true,
                    ['class' => 'invert_filter']
                );
            } else {
                $table->data[$kaction][$k] = html_print_image(
                    'images/blade.png',
                    true
                );
            }

            if (count($table->head) <= count($action['escalation'])) {
                if ($k == count($action['escalation'])) {
                    if ($k == 1) {
                        $table->head[$k] = __('Every time that the alert is fired');
                    } else {
                        $table->head[$k] = '>#'.($k - 1);
                    }
                } else {
                    $table->head[$k] = '#'.$k;
                }
            }
        }

        $action_threshold = ($action['module_action_threshold'] > 0) ? $action['module_action_threshold'] : $action['action_threshold'];

        if ($action_threshold == 0) {
            $table->data[$kaction][($k + 1)] = __('No');
        } else {
            $table->data[$kaction][($k + 1)] = human_time_description_raw(
                $action_threshold,
                true,
                'tiny'
            );
        }

        $table->head[($k + 1)] = __('Threshold');
    }
}

html_print_table($table);
unset($table);
echo '</div>';
// ESCALATION TABLE.
$table = new stdClass;
$table->class = 'alert_list databox';
$table->width = '98%';
$table->size = [];
$table->head = [];
$table->data = [];
$table->rowstyle[1] = 'font-weight: bold;';

if ((int) $default_action != 0) {
    $actions_select[0] = $default_action['name'];
}

foreach ($actions as $kaction => $action) {
    $actions_select[$kaction] = $action['name'];
}

$table->data[0][0] = __('Select the desired action and mode to see the Firing/Recovery fields for this action');
$table->colspan[0][0] = 2;

$table->data[1][0] = __('Action');
$table->data[1][0] .= '<br>';
$table->data[1][0] .= html_print_select(
    $actions_select,
    'firing_action_select',
    -1,
    '',
    __('Select the action'),
    -1,
    true,
    false,
    false
);

$modes = [];
$modes['firing'] = __('Firing');
$modes['recovering'] = __('Recovering');

$table->data[1][1] = '<div class="action_details invisible" >';
$table->data[1][1] .= __('Mode');
$table->data[1][1] .= '<br>';
$table->data[1][1] .= html_print_select(
    $modes,
    'modes',
    'firing',
    '',
    '',
    0,
    true,
    false,
    false
);
$table->data[1][1] .= '</div>';

html_print_table($table);

$table = new stdClass;
$table->class = 'alert_list databox alternate';
$table->width = '98%';
$table->size = [];
$table->head = [];
$table->data = [];
$table->style[0] = 'width: 100px;';
$table->style[1] = 'width: 30%;';
$table->style[2] = 'width: 30%;';
$table->style[3] = 'font-weight: bold; width: 30%;';

$table->title = __('Firing fields');
$table->title .= ui_print_help_tip(
    __('Fields passed to the command executed by this action when the alert is fired'),
    true
);

$table->head[0] = __('Field');
$table->head[0] .= ui_print_help_tip(
    __('Fields configured on the command associated to the action'),
    true
);
$table->head[1] = __('Template fields');
$table->head[1] .= ui_print_help_tip(
    __('Triggering fields configured in template'),
    true
);
$table->head[2] = __('Action fields');
$table->head[2] .= ui_print_help_tip(
    __('Triggering fields configured in action'),
    true
);

$table->head[3] = __('Executed on firing');
$table->head[3] .= ui_print_help_tip(
    __('Fields used on execution when the alert is fired'),
    true
);

$firing_fields = [];

foreach ($actions as $kaction => $action) {
    $command = alerts_get_alert_command($action['id_alert_command']);
    $command_preview = $command['command'];
    $firing_fields[$kaction] = $action;
    $firing_fields[$kaction]['command'] = $command['command'];

    $descriptions = json_decode($command['fields_descriptions'], true);

    foreach ($descriptions as $kdesc => $desc) {
        $field = 'field'.($kdesc + 1);
        $data = [];
        $data[0] = $desc;
        $firing_fields[$kaction]['description'][$field] = $desc;

        if (empty($data[0]) === false) {
            $data[0] = '<b>'.$data[0].'</b><br>';
        }

        $data[0] .= '<br><span class="redi xx-small">('.sprintf(
            __('Field %s'),
            ($kdesc + 1)
        ).')</span>';
        $data[1] = $template[$field];
        $data[2] = $action[$field];
        $data[3] = (empty($action[$field]) === true) ? $template[$field] : $action[$field];

        $firing_fields[$kaction]['value'][$field] = (empty($action[$field]) === true) ? $template[$field] : $action[$field];

        $first_level = $template[$field];
        $second_level = $action[$field];
        if (empty($second_level) === false || empty($first_level) === false) {
            if (empty($second_level) === false) {
                $table->cellclass[count($table->data)][1] = 'used_field';
                $table->cellclass[count($table->data)][2] = 'empty_field';
            } else {
                $table->cellclass[count($table->data)][1] = 'overrided_field';
                $table->cellclass[count($table->data)][2] = 'used_field';
            }
        }

        $table->data[] = $data;

        $table->rowstyle[] = 'display: none;';

        $table->rowclass[] = 'firing_action firing_action_'.$kaction;

        if ($command_preview !== 'Internal type') {
            $command_preview = str_replace('_'.$field.'_', $data[3], $command_preview);
        }
    }

    $firing_fields[$kaction]['command_preview'] = $command_preview;
}

echo '<div class="mode_table mode_table_firing action_details invisible w100p">';

html_print_table($table);

foreach ($actions as $kaction => $action) {
    echo '<div class="firing_action firing_action_'.$kaction.' invisible">';
    ui_print_info_message(
        [
            'title'    => __('Command preview'),
            'message'  => $firing_fields[$kaction]['command_preview'],
            'no_close' => true,
        ]
    );
    echo '</div>';
}

echo '</div>';
// Firing table.
echo '<div class="mode_table mode_table_recovering action_details invisible w100p" >';
if ((int) $template['recovery_notify'] === 0) {
    ui_print_info_message(
        [
            'title'    => __('Disabled'),
            'message'  => __('The alert recovering is disabled on this template.'),
            'no_close' => true,
        ]
    );
} else {
    $table = new stdClass;
    $table->class = 'alert_list databox alternate';
    $table->width = '98%';
    $table->size = [];
    $table->head = [];
    $table->data = [];
    $table->style[0] = 'width: 100px;';
    $table->style[1] = 'width: 25%;';
    $table->style[2] = 'width: 25%;';
    $table->style[3] = 'width: 25%;';
    $table->style[3] = 'font-weight: bold; width: 25%;';
    $table->title = __('Recovering fields');
    $table->title .= ui_print_help_tip(
        __('Fields passed to the command executed by this action when the alert is recovered'),
        true
    );

    $table->head[0] = __('Field');
    $table->head[0] .= ui_print_help_tip(
        __('Fields configured on the command associated to the action'),
        true
    );
    $table->head[1] = __('Firing fields');
    $table->head[1] .= ui_print_help_tip(
        __('Fields used on execution when the alert is fired'),
        true
    );
    $table->head[2] = __('Template recovery fields');
    $table->head[2] .= ui_print_help_tip(
        __('Recovery fields configured in alert template'),
        true
    );
    $table->head[3] = __('Action recovery fields');
    $table->head[3] .= ui_print_help_tip(
        __('Recovery fields configured in alert action'),
        true
    );
    $table->head[4] = __('Executed on recovery');
    $table->head[4] .= ui_print_help_tip(
        __('Fields used on execution when the alert is recovered'),
        true
    );
    $table->style[4] = 'font-weight: bold;';

    foreach ($firing_fields as $kaction => $firing) {
        $data = [];
        $command_preview = $firing_fields[$kaction]['command'];
        $fieldn = 1;
        foreach ($firing['description'] as $field => $desc) {
            $data[0] = $desc;

            if (empty($data[0]) === false) {
                $data[0] = '<b>'.$data[0].'</b><br>';
            }

            $data[0] .= '<br><span class="redi xx-small">('.sprintf(
                __('Field %s'),
                $fieldn
            ).')</span>';
            $data[1] = $firing_fields[$kaction]['value'][$field];
            $data[2] = $template[$field.'_recovery'];
            $data[3] = $firing_fields[$kaction][$field.'_recovery'];
            $data[4] = '';

            $first_level = $data[1];
            $second_level = $data[2];
            $third_level = $data[3];
            if (empty($third_level) === false || empty($second_level) === false || empty($first_level) === false) {
                if (empty($third_level) === false) {
                    $table->cellclass[count($table->data)][1] = 'overrided_field';
                    $table->cellclass[count($table->data)][2] = 'overrided_field';
                    $table->cellclass[count($table->data)][3] = 'used_field';

                    $data[4] = $data[3];
                } else if (empty($second_level) === false) {
                    $table->cellclass[count($table->data)][1] = 'overrided_field';
                    $table->cellclass[count($table->data)][2] = 'used_field';
                    $table->cellclass[count($table->data)][3] = 'empty_field';

                    $data[4] = $data[2];
                } else {
                    $table->cellclass[count($table->data)][1] = 'used_field';
                    $table->cellclass[count($table->data)][2] = 'empty_field';
                    $table->cellclass[count($table->data)][3] = 'empty_field';

                    // All fields but field1 will have [RECOVER] prefix if no recovery fields are configured.
                    $data[4] = ((int) $fieldn === 1) ? $data[1] : '[RECOVER]'.$data[1];
                }
            }

            $table->data[] = $data;
            unset($data);

            $table->rowclass[] = 'firing_action firing_action_'.$kaction;

            if ($command_preview !== 'Internal type') {
                $command_preview = str_replace('_'.$field.'_', $data[4], $command_preview);
            }

            $fieldn++;
        }
    }

    html_print_table($table);
    unset($table);
    ui_print_info_message(
        [
            'title'    => __('Command preview'),
            'message'  => $command_preview,
            'no_close' => true,
        ]
    );
}

echo '</div>';

ui_require_css_file('main.min', 'include/javascript/fullcalendar/');
ui_require_javascript_file('main.min', 'include/javascript/fullcalendar/');
ui_require_javascript_file('pandora_fullcalendar');
?>

<script language="javascript" type="text/javascript">
$(document).ready (function () {
    var calendarEl = document.getElementById('calendar_map');
    if(calendarEl){
        var eventsBBDD = $("#hidden-schedule").val();
        if(eventsBBDD === '' || eventsBBDD === 'Array') {
            eventsBBDD = '';
        }
        var events = loadEventBBDD(eventsBBDD);

        var options = {
            contentHeight: "auto",
            headerToolbar: {
                left: "",
                center: "",
                right: ''
            },
            buttonText: {},
            dayHeaderFormat: { weekday: "short" },
            initialView: "dayGridWeek",
            navLinks: false,
            selectable: true,
            selectMirror: true,
            slotDuration: "01:00:00",
            slotLabelInterval: "02:00:00",
            snapDuration: "01:00:00",
            slotMinTime: "00:00:00",
            slotMaxTime: "24:00:00",
            scrollTime: "01:00:00",
            locale: "en-GB",
            firstDay: 1,
            eventTimeFormat: {
                hour: "numeric",
                minute: "2-digit",
                hour12: false
            },
            eventColor: "#82b92e",
            editable: false,
            dayMaxEvents: 3,
            dayPopoverFormat: { weekday: "long" },
            defaultAllDay: false,
            displayEventTime: true,
            displayEventEnd: true,
            selectOverlap: false,
            eventOverlap: false,
            allDaySlot: true,
            droppable: false,
            select: false,
            selectAllow: false,
            eventAllow: false,
            eventDrop: false,
            eventDragStop: false,
            eventResize: false,
            eventMouseEnter: false,
            eventMouseLeave: false,
            eventClick: false,
        };

        var settings = {}

        var calendar = fullCalendarPandora(calendarEl, options, settings, events);
        calendar.render();
    }
});

$('#firing_action_select').change(function() {
    if($(this).val() == -1) {
        $('.action_details').hide();
        $('#modes').val('firing');
        $('.mode_table_recovering').hide();
    }
    else {
        $('.action_details').show();
    }

    $('.firing_action').hide();
    if($(this).val() != -1) {
        $('.firing_action_' + $(this).val()).show();
        $('#modes').trigger('change');
    }
});

$('#modes').change(function() {
    $('.mode_table').hide();
    $('.mode_table_' + $(this).val()).show();
});
</script>
