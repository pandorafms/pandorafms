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

check_login ();

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert View (In management section)");
	require ("general/noaccess.php");
	exit;
}

enterprise_include_once ('include/functions_policies.php');

$id_alert = get_parameter ("id_alert", 0); // ID given as parameter
$alert = alerts_get_alert_agent_module($id_alert);
$template = alerts_get_alert_template ($alert['id_alert_template']);
$actions = alerts_get_alert_agent_module_actions ($id_alert);
$agent_name = modules_get_agentmodule_agent_name ($alert['id_agent_module']);
$agent = modules_get_agentmodule_agent ($alert['id_agent_module']);
$module_name = modules_get_agentmodule_name ($alert['id_agent_module']);

// Default action
$default_action = $template['id_alert_action'];
if ($default_action != 0) {
	$default_action = alerts_get_alert_action($default_action);
	$default_action['name'] .= '  ' . '(' . __('Default') . ')';
	$default_action['default'] = 1;
	$default_action['module_action_threshold'] = '0';
}

// Header
ui_print_page_header (__('Alert details'), "images/op_alerts.png", false, "", false, "");

// TABLE DETAILS

$table_details->class = 'databox';
$table_details->width = '100%';
$table_details->size = array ();
$table_details->data = array();
$table_details->style = array();
$table_details->style[0] = 'font-weight: bold;';
$data = array();

$data[0] = __('List alerts');
$data[1] ='<a style=" font-size: 7pt;" href="index.php?sec=galertas&sec2=godmode/alerts/alert_list" title="'.__('List alerts').
'"><b><span style=" font-size: 7pt;">'.__('List alerts').'</span></b></a>';
$table_details->data[] = $data;

$data[0] = __('Agent');
$data[1] ='<a style=" font-size: 7pt;" href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$agent.
'" title="'.$agent_name.'"><b><span style=" font-size: 7pt;">'.$agent_name.'</span></b></a>';
$table_details->data[] = $data;

$data[0] = __('Module');
$data[1] = $module_name;
$table_details->data[] = $data;

$data[0] = __('Template');
$data[1] = $template['name'] . ui_print_help_tip($template['description'], true);
$table_details->data[] = $data;

$data[0] = __('Last fired');
$data[1] = ui_print_timestamp ($alert["last_fired"], true);
$table_details->data[] = $data;

if ($alert["times_fired"] > 0) {
	$status = STATUS_ALERT_FIRED;
	$title = __('Alert fired').' '.$alert["times_fired"].' '.__('times');
}
elseif ($alert["disabled"] > 0) {
	$status = STATUS_ALERT_DISABLED;
	$title = __('Alert disabled');
}
else {
	$status = STATUS_ALERT_NOT_FIRED;
	$title = __('Alert not fired');
}
	
$data[0] = __('Status');
$data[1] = '<span style="margin-right: 5px;">' . ui_print_status_image($status, $title, true) . '</span>' . $title;
$table_details->data[] = $data;

$priorities = get_priorities ();

$data[0] = __('Priority');
$data[1] = '<span style="width: 20px; height: 10px; margin-right: 5px; display: inline-block;" title="' . $priorities[$template['priority']] . '" class="' . get_priority_class($template['priority']) . '">&nbsp</span>' . $priorities[$template['priority']];
$table_details->data[] = $data;

$data[0] = __('Stand by');
$data[1] = $alert['standby'] == 1 ? __('Yes') : __('No');
$table_details->data[] = $data;

if (enterprise_installed() && $alert['id_policy_alerts'] != 0) {
	$policyInfo = policies_is_alert_in_policy2($alert['id'], false);
	if ($policyInfo === false) {
		$policy = __('N/A');
	}
	else {
		$img = 'images/policies.png';
		
		$policy = '<a href="?sec=gmodules&amp;sec2=enterprise/godmode/policies/policies&amp;id=' . $policyInfo['id'] . '">' . 
			html_print_image($img, true, array('title' => $policyInfo['name'])) .
			'</a>';
	}
			
	$data[0] = __('Policy');
	$data[1] = $policy;
	$table_details->data[] = $data;
}

// TABLE DETAILS END

// TABLE CONDITIONS

$table_conditions->class = 'databox';
$table_conditions->width = '100%';
$table_conditions->size = array ();
$table_conditions->data = array();
$table_conditions->style = array();
$table_conditions->style[0] = 'font-weight: bold; width: 50%;';
$data = array();
$table_conditions->colspan[0][0] = 2;

switch ($template['type']) {
	case 'regex':
		if ($template['matches_value']) {
			$condition = __('The alert would fire when the value matches <span id="value"></span>');
		}
		else {
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
			$condition = __('The alert would fire when the value is between <span id="min"></span> and <span id="max"></span>');
		}
		else {
			$condition = __('The alert would fire when the value is not between <span id="min"></span> and <span id="max"></span>');
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
		}
		else {
			$condition = __('The alert would fire when the module value does not change');
		}
		break;
	case 'warning':
		$condition = __('The alert would fire when the module is in warning status');
		break;
	case 'critical':
		$condition = __('The alert would fire when the module is in critical status');
		break;
	case 'unknown':
		$condition = __('The alert would fire when the module is in unknown status');
		break;
	case 'always':
		$condition = __('Always');
}
$data[0] = $condition;

$table_conditions->data[] = $data;

//DAYS
$table_days->class = 'databox alert_days';
$table_days->width = '100%';
$table_days->size = array ();
$table_days->data = array();
$table_days->style = array();
$table_days->styleTable = 'padding: 1px; margin: 0px; text-align: center; height: 80px;';
$table_days->head[0] = __('Mon');
$table_days->head[1] = __('Tue');
$table_days->head[2] = __('Wed');
$table_days->head[3] = __('Thu');
$table_days->head[4] = __('Fri');
$table_days->head[5] = __('Sat');
$table_days->head[6] = __('Sun');
$table_days->data[0] = array_fill(0, 7, html_print_image('images/blade.png', true));

$days = array();
if ($template['monday']) {
	$table_days->data[0][0] = html_print_image('images/tick.png', true);
}
if ($template['tuesday']) {
	$table_days->data[0][1] = html_print_image('images/tick.png', true);
}
if ($template['wednesday']) {
	$table_days->data[0][2] = html_print_image('images/tick.png', true);
}
if ($template['thursday']) {
	$table_days->data[0][3] = html_print_image('images/tick.png', true);
}
if ($template['friday']) {
	$table_days->data[0][4] = html_print_image('images/tick.png', true);
}
if ($template['saturday']) {
	$table_days->data[0][5] = html_print_image('images/tick.png', true);
}
if ($template['sunday']) {
	$table_days->data[0][6] = html_print_image('images/tick.png', true);
}

$data[0] = html_print_table($table_days, true);
unset($table_days);

// TIME
$table_time->class = 'databox alert_time';
$table_time->width = '100%';
$table_time->size = array ();
$table_time->data = array();
$table_time->style = array();
$table_time->styleTable = 'padding: 1px; margin: 0px; text-align: center; height: 80px; width: 100%;';

//$data[0] = __('Time from') . ' / ' . __('Time to');
if ($template['time_from'] == $template['time_to']) {
	$table_time->head[0] = '00:00:00<br>-<br>23:59:59';
	$table_time->data[0][0] = html_print_image('images/tick.png', true);
}
else {
	$from_array = explode(':', $template['time_from']);
	$from = $from_array[0] * SECONDS_1HOUR + $from_array[1] * SECONDS_1MINUTE + $from_array[2];
	$to_array = explode(':', $template['time_to']);
	$to = $to_array[0] * SECONDS_1HOUR + $to_array[1] * SECONDS_1MINUTE + $to_array[2];
	if ($to > $from) {
		if ($template['time_from'] != '00:00:00') {
			$table_time->head[0] = '00:00:00<br>-<br>' . $template['time_from'];
			$table_time->data[0][0] = html_print_image('images/blade.png', true);
		}
		
		$table_time->head[1] = $template['time_from'] . '<br>-<br>' . $template['time_to'];
		$table_time->data[0][1] = html_print_image('images/tick.png', true);
		
		if ($template['time_to'] != '23:59:59') {
			$table_time->head[2] = $template['time_to'] . '<br>-<br>23:59:59';
			$table_time->data[0][2] = html_print_image('images/blade.png', true);
		}
	}
	else {
		if ($template['time_to'] != '00:00:00') {
			$table_time->head[0] = '00:00:00<br>-<br>' . $template['time_to'];
			$table_time->data[0][0] = html_print_image('images/tick.png', true);
		}
		
		$table_time->head[1] = $template['time_to'] . '<br>-<br>' . $template['time_from'];
		$table_time->data[0][1] = html_print_image('images/blade.png', true);
		
		if ($template['time_from'] != '23:59:59') {
			$table_time->head[2] = $template['time_from'] . '<br>-<br>23:59:59';
			$table_time->data[0][2] = html_print_image('images/tick.png', true);
		}
		
	}
	$data[1] = $template['time_from'] . ' / ' . $template['time_to'];
}

$data[1] = html_print_table($table_time, true);
unset($table_time);

$table_conditions->data[] = $data;

$data[0] = __('Use special days list');
$data[1] = (isset($alert['special_day']) && $alert['special_day'] == 1)
	?
	__('Yes')
	:
	__('No');
$table_conditions->data[] = $data;

$data[0] = __('Time threshold');
$data[1] = human_time_description_raw ($template['time_threshold'], true);
$table_conditions->data[] = $data;

$data[0] = __('Number of alerts') . ' ('. __('Min') . '/' . __('Max') . ')';
$data[1] = $template['min_alerts'] . '/' . $template['max_alerts'];
$table_conditions->data[] = $data;

// TABLE CONDITIONS END

$table->class = 'alert_list databox';
$table->width = '98%';
$table->size = array();
$table->head = array();
$table->data = array();
$table->style = array();
$table->style[0] = 'width: 50%;';

$table->head[0] = __('Alert details');
$table->head[1] = __('Firing conditions');

$table->data[0][0] = html_print_table($table_details, true);
$table->data[0][1] = html_print_table($table_conditions, true);

html_print_table($table);
unset($table);

$actions = alerts_get_actions_escalation($actions, $default_action);

// ESCALATION
$table->class = 'alert_list databox alternate alert_escalation';
$table->width = '98%';
$table->size = array ();
$table->head = array();
$table->data = array();
$table->styleTable = 'text-align: center;';

echo '<div class="firing_action_all" style="width: 100%;">';
$table->head[0] = __('Actions');
$table->style[0] = 'font-weight: bold; text-align: left;';

if (count($actions) == 1 && isset($actions[0])) {
	$table->head[1] = __('Every time that the alert is fired');
	$table->data[0][0] = $actions[0]['name'];
	$table->data[0][1] = html_print_image('images/tick.png', true);
}
else {
	foreach($actions as $kaction => $action) {
		$table->data[$kaction][0] = $action['name'];
		if($kaction == 0) {
			$table->data[$kaction][0] .= ui_print_help_tip(__('The default actions will be executed every time that the alert is fired and no other action is executed'), true);
		}
		
		foreach($action['escalation'] as $k => $v) {
			if ($v > 0) {
				$table->data[$kaction][$k] = html_print_image('images/tick.png', true);
			}
			else {
				$table->data[$kaction][$k] = html_print_image('images/blade.png', true);
			}
			
			if (count($table->head) <= count($action['escalation'])) {
				if ($k == count($action['escalation'])) {
					if($k == 1) {
						$table->head[$k] = __('Every time that the alert is fired');
					}
					else { 
						$table->head[$k] = '>#' . ($k-1);
					}
				}
				else {
					$table->head[$k] = '#' . $k;
				}
			}
		}
		
		$action_threshold = $action['module_action_threshold'] > 0 ? $action['module_action_threshold'] : $action['action_threshold'];
		
		if ($action_threshold == 0) {
			$table->data[$kaction][$k+1] = __('No');
		}
		else {
			$table->data[$kaction][$k+1] = human_time_description_raw ($action_threshold, true, 'tiny');
		}
		
		$table->head[$k+1] = __('Threshold') .  '<span style="float: right;">' . ui_print_help_icon ('action_threshold', true, '', 'images/header_help.png') . '</span>';
	}
}

html_print_table($table);
unset($table);
echo '</div>'; // ESCALATION TABLE

$table->class = 'alert_list databox';
$table->width = '98%';
$table->size = array ();
$table->head = array();
$table->data = array();
$table->rowstyle[1] = 'font-weight: bold;';

if ($default_action != 0) {
	$actions_select[0] = $default_action['name'];
}

foreach($actions as $kaction => $action) {
	$actions_select[$kaction] = $action['name'];
}

$table->data[0][0] = __('Select the desired action and mode to see the Firing/Recovery fields for this action');
$table->colspan[0][0] = 2;

$table->data[1][0] = __('Action') . '<br>' . html_print_select($actions_select, 'firing_action_select', -1, '', __('Select the action'), -1, true, false, false);

$modes = array();
$modes['firing'] = __('Firing');
$modes['recovering'] = __('Recovering');

$table->data[1][1] = '<div class="action_details" style="display: none;">' . __('Mode') . '<br>' . html_print_select($modes, 'modes', 'firing', '', '', 0, true, false, false) . '</div>';

html_print_table($table);
unset($table);

$table->class = 'alert_list databox alternate';
$table->width = '98%';
$table->size = array ();
$table->head = array();
$table->data = array();
$table->style[0] = 'width: 100px;';
$table->style[1] = 'width: 30%;';
$table->style[2] = 'width: 30%;';
$table->style[3] = 'font-weight: bold; width: 30%;';

$table->title = __('Firing fields') .
	ui_print_help_tip(__('Fields passed to the command executed by this action when the alert is fired'), true);

$table->head[0] = __('Field') .
	ui_print_help_tip(__('Fields configured on the command associated to the action'), true);
$table->head[1] = __('Template fields') .
	ui_print_help_tip(__('Triggering fields configured in template'), true);
$table->head[2] = __('Action fields') .
	ui_print_help_tip(__('Triggering fields configured in action'), true);

$table->head[3] = __('Executed on firing') .
	ui_print_help_tip(__('Fields used on execution when the alert is fired'), true);

$firing_fields = array();

foreach ($actions as $kaction => $action) {
	$command = alerts_get_alert_command($action['id_alert_command']);
	$command_preview = $command['command'];
	$firing_fields[$kaction] = $action;
	$firing_fields[$kaction]['command'] = $command['command'];
	
	$descriptions = json_decode($command['fields_descriptions'], true);
	
	foreach	($descriptions as $kdesc => $desc) {
		if (empty($desc)) {
			//continue;
		}
		$field = "field" . ($kdesc + 1);
		$data = array();
		$data[0] = $firing_fields[$kaction]['description'][$field] = $desc;
		if (!empty($data[0])) {
			$data[0] = '<b>' . $data[0] . '</b><br>';
		}
		$data[0] .= '<br><span style="font-size: xx-small;font-style:italic;">(' . sprintf(__("Field %s"), ($kdesc + 1)) . ')</span>';
		$data[1] = $template[$field];
		$data[2] = $action[$field];
		$data[3] = $firing_fields[$kaction]['value'][$field] = empty($action[$field]) ? $template[$field] : $action[$field];
		
		$first_level = $template[$field];
		$second_level = $action[$field];
		if (!empty($second_level) || !empty($first_level)) {
			if (empty($second_level)) {
				$table->cellclass[count($table->data)][1] = 'used_field';
				$table->cellclass[count($table->data)][2] = 'empty_field';
			}
			else {
				$table->cellclass[count($table->data)][1] = 'overrided_field';
				$table->cellclass[count($table->data)][2] = 'used_field';
			}
		}
		$table->data[] = $data;
		
		$table->rowstyle[] = 'display: none;';
		
		$table->rowclass[] = 'firing_action firing_action_' . $kaction;
		
		if ($command_preview != 'Internal type') {
			$command_preview = str_replace('_'.$field.'_', $data[3], $command_preview);
		}
	}
	$firing_fields[$kaction]['command_preview'] = $command_preview;
}

echo '<div class="mode_table mode_table_firing action_details" style="width: 100%; display: none;">';

html_print_table($table);
unset($table);

foreach($actions as $kaction => $action) {	
	echo '<div class="firing_action firing_action_' . $kaction . '" style="display:none;">';
	ui_print_info_message(array('title' => __('Command preview'), 'message' => $firing_fields[$kaction]['command_preview'], 'no_close' => true));
	echo '</div>';
}

echo '</div>'; // Firing table

echo '<div class="mode_table mode_table_recovering action_details" style="display: none; width: 100%;">';
if ($template['recovery_notify'] == 0) {
	ui_print_info_message(array('title' => __('Disabled'), 'message' => __('The alert recovering is disabled on this template.'), 'no_close' => true));
}
else {
	$table->class = 'alert_list databox alternate';
	$table->width = '98%';
	$table->size = array ();
	$table->head = array();
	$table->data = array();
	$table->style[0] = 'width: 100px;';
	$table->style[1] = 'width: 25%;';
	$table->style[2] = 'width: 25%;';
	$table->style[3] = 'width: 25%;';
	$table->style[3] = 'font-weight: bold; width: 25%;';
	$table->title = __('Recovering fields') . ui_print_help_tip(__('Fields passed to the command executed by this action when the alert is recovered'), true);
	
	$table->head[0] = __('Field') . ui_print_help_tip(__('Fields configured on the command associated to the action'), true);
	$table->head[1] = __('Firing fields') . ui_print_help_tip(__('Fields used on execution when the alert is fired'), true);
	$table->head[2] = __('Template recovery fields') . ui_print_help_tip(__('Recovery fields configured in alert template'), true);
	$table->head[3] = __('Action recovery fields') . ui_print_help_tip(__('Recovery fields configured in alert action'), true);
	$table->head[4] = __('Executed on recovery') . ui_print_help_tip(__('Fields used on execution when the alert is recovered'), true);
	$table->style[4] = 'font-weight: bold;';
	
	foreach($firing_fields as $kaction => $firing) {
		$data = array();
		$command_preview = $firing_fields[$kaction]['command'];
		$fieldn = 1;
		foreach ($firing['description'] as $field => $desc) {
			$data[0] = $desc;
			
			if (!empty($data[0])) {
				$data[0] = '<b>' . $data[0] . '</b><br>';
			}
			$data[0] .= '<br><span style="font-size: xx-small;font-style:italic;">(' . sprintf(__("Field %s"), $fieldn) . ')</span>';
			$data[1] = $firing_fields[$kaction]['value'][$field];
			$data[2] = $template[$field . '_recovery'];
			$data[3] = $firing_fields[$kaction][$field . '_recovery'];
			$data[4] = '';
			
			$first_level = $data[1];
			$second_level = $data[2];
			$third_level = $data[3];
			if (!empty($third_level) || !empty($second_level) || !empty($first_level)) {
				if (!empty($third_level)) {
					$table->cellclass[count($table->data)][1] = 'overrided_field';
					$table->cellclass[count($table->data)][2] = 'overrided_field';
					$table->cellclass[count($table->data)][3] = 'used_field';
					
					$data[4] = $data[3];
				}
				else if (!empty($second_level)) {
					$table->cellclass[count($table->data)][1] = 'overrided_field';
					$table->cellclass[count($table->data)][2] = 'used_field';
					$table->cellclass[count($table->data)][3] = 'empty_field';
					
					$data[4] = $data[2];
				}
				else {
					$table->cellclass[count($table->data)][1] = 'used_field';
					$table->cellclass[count($table->data)][2] = 'empty_field';
					$table->cellclass[count($table->data)][3] = 'empty_field';
					
					// All fields but field1 will have [RECOVER] prefix if no recovery fields are configured
					$data[4] = $fieldn == 1 ? $data[1] : '[RECOVER]' . $data[1];
				}
			}
			$table->data[] = $data;
			unset($data);
			
			$table->rowclass[] = 'firing_action firing_action_' . $kaction;
			
			if ($command_preview != 'Internal type') {
				$command_preview = str_replace('_'.$field.'_', $data[4], $command_preview);
			}
			$fieldn++;
		}
	}

	html_print_table($table);
	unset($table);
	ui_print_info_message(array('title' => __('Command preview'), 'message' => $command_preview, 'no_close' => true));
}
echo '</div>'; // Recovering table

?>

<script language="javascript" type="text/javascript">
$(document).ready (function () {
	
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
