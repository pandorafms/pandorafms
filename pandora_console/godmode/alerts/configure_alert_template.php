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
require_once ($config['homedir'] . '/include/functions_alerts.php');
require_once ($config['homedir'] . '/include/functions_users.php');
enterprise_include_once ('meta/include/functions_alerts_meta.php');

check_login ();

enterprise_hook('open_meta_frame');

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}


$duplicate_template = (bool) get_parameter ('duplicate_template');
$id = (int) get_parameter ('id');
$pure = get_parameter('pure', 0);

// If user tries to duplicate/edit a template with group=ALL then must have "PM" access privileges 
if ($duplicate_template) {
	$source_id = (int) get_parameter ('source_id');
	$a_template = alerts_get_alert_template($source_id);
}
else {
	$a_template = alerts_get_alert_template($id);
}

if (defined('METACONSOLE')) {
	$sec = 'advanced';
}
else {
	$sec = 'galertas';
}

if ($a_template !== false) {
	// If user tries to duplicate/edit a template with group=ALL
	if ($a_template['id_group'] == 0) {
		// Header
		if (defined('METACONSOLE')) {
			alerts_meta_print_header();
		}
		else {
			ui_print_page_header (__('Alerts') .
				' &raquo; ' . __('Configure alert template'), "",
				false, "alerts_config", true);
		}
	}
	else {
		// If user tries to duplicate/edit a template of others groups
		$own_info = get_user_info ($config['id_user']);
		if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
			$own_groups = array_keys(users_get_groups($config['id_user'], "LM"));
		else
			$own_groups = array_keys(users_get_groups($config['id_user'], "LM", false));
		$is_in_group = in_array($a_template['id_group'], $own_groups);
		// Then template group have to be in his own groups
		if ($is_in_group) {
			// Header
			if (defined('METACONSOLE')) {
				alerts_meta_print_header();
			}
			else {
				ui_print_page_header (__('Alerts').' &raquo; '.__('Configure alert template'), "images/gm_alerts.png", false, "conf_alert_template", true);
			}
		}
		else {
			db_pandora_audit("ACL Violation",
			"Trying to access Alert Management");
			require ("general/noaccess.php");
			exit;
		}
	}
// This prevents to duplicate the header in case duplicate/edit_template action is performed
}
else {
	// Header
	if (defined('METACONSOLE')) {
		alerts_meta_print_header();
	}
	else {
		ui_print_page_header (__('Alerts').' &raquo; '.__('Configure alert template'), "images/gm_alerts.png", false, "conf_alert_template", true);
	}
}


if ($duplicate_template) {
	$source_id = (int) get_parameter ('source_id');
	
	$id = alerts_duplicate_alert_template ($source_id);
	
	if ($id) {
		db_pandora_audit("Template alert management", "Duplicate alert template " . $source_id . " clone to " . $id);
	}
	else {
		db_pandora_audit("Template alert management", "Fail try to duplicate alert template " . $source_id);
	}
	
	ui_print_result_message ($id,
		__('Successfully created from %s', alerts_get_alert_template_name ($source_id)),
		__('Could not be created'));
}


function print_alert_template_steps ($step, $id) {
	echo '<ol class="steps">';
	
	if (defined('METACONSOLE')) {
		
		$sec = 'advanced';
	}
	else {
		
		$sec = 'galertas';
	}
	
	$pure = get_parameter('pure', 0);
	
	/* Step 1 */
	if ($step == 1)
		echo '<li class="first current">';
	elseif ($step > 1)
		echo '<li class="visited">';
	else
		echo '<li class="first">';
	
	if ($id) {
		echo '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&id='.$id.'&pure='.$pure.'">';
		echo __('Step') . ' 1 &raquo; ';
		echo '<span>' . __('General') . '</span>';
		echo '</a>';
	}
	else {
		echo __('Step') . ' 1 &raquo; ';
		echo '<span>' . __('General') . '</span>';
	}
	echo '</li>';
	
	/* Step 2 */
	if ($step == 2)
		echo '<li class="current">';
	elseif ($step > 2)
		echo '<li class="visited">';
	else
		echo '<li>';
	
	if ($id) {
		echo '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&id='.$id.'&step=2&pure='.$pure.'">';
		echo __('Step').' 2 &raquo; ';
		echo '<span>'.__('Conditions').'</span>';
		echo '</a>';
	}
	else {
		echo __('Step').' 2 &raquo; ';
		echo '<span>'.__('Conditions').'</span>';
	}
	echo '</li>';
	
	/* Step 3 */
	if ($step == 3)
		echo '<li class="last current">';
	elseif ($step > 3)
		echo '<li class="last visited">';
	else
		echo '<li class="last">';
	
	if ($id) {
		echo '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&id='.$id.'&step=3&pure='.$pure.'">';
		echo __('Step').' 3 &raquo; ';
		echo '<span>'.__('Advanced fields').'</span>';
		echo '</a>';
	}
	else {
		echo __('Step').' 3 &raquo; ';
		echo '<span>'.__('Advanced fields').'</span>';
	}
	
	echo '</ol>';
	echo '<div id="steps_clean"> </div>';
}

function update_template ($step) {
	global $config;
	
	$id = (int) get_parameter ('id');
	
	if (empty ($id))
		return false;
	
	if (defined('METACONSOLE')) {
		
		$sec = 'advanced';
	}
	else {
		
		$sec = 'galertas';
	}
	
	if ($step == 1) {
		$name = (string) get_parameter ('name');
		$description = (string) get_parameter ('description');
		$wizard_level = (string) get_parameter ('wizard_level');
		$priority = (int) get_parameter ('priority');
		$id_group = get_parameter ("id_group");
		
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				$name_check = db_get_value ('name', 'talert_templates', 'name', $name);
				break;
			case "oracle":
				$name_check = db_get_value ('name', 'talert_templates', 'to_char(name)', $name);
				break;
		}
		
		$values = array ('name' => $name,
			'description' => $description,
			'id_group' => $id_group,
			'priority' => $priority,
			'wizard_level' => $wizard_level);
		
		$result = alerts_update_alert_template ($id,$values);
	}
	elseif ($step == 2) {
		$monday = (bool) get_parameter ('monday');
		$tuesday = (bool) get_parameter ('tuesday');
		$wednesday = (bool) get_parameter ('wednesday');
		$thursday = (bool) get_parameter ('thursday');
		$friday = (bool) get_parameter ('friday');
		$saturday = (bool) get_parameter ('saturday');
		$sunday = (bool) get_parameter ('sunday');
		$special_day = (bool) get_parameter ('special_day');
		$time_from = (string) get_parameter ('time_from');
		$time_from = date ("H:i:00", strtotime ($time_from));
		$time_to = (string) get_parameter ('time_to');
		$time_to = date ("H:i:00", strtotime ($time_to));
		$threshold = (int) get_parameter ('threshold');
		$max_alerts = (int) get_parameter ('max_alerts');
		$min_alerts = (int) get_parameter ('min_alerts');
		$min_alerts_reset_counter = (int) get_parameter ('min_alerts_reset_counter');
		$type = (string) get_parameter ('type');
		$value = (string) html_entity_decode (get_parameter ('value'));
		$max = (float) get_parameter ('max');
		$min = (float) get_parameter ('min');
		$matches = (bool) get_parameter ('matches_value');
		
		$default_action = (int) get_parameter ('default_action');
		if (empty ($default_action)) {
			$default_action = NULL;
		}
		
		$values = array (
			'monday' => $monday,
			'tuesday' => $tuesday,
			'wednesday' => $wednesday,
			'thursday' => $thursday,
			'friday' => $friday,
			'saturday' => $saturday,
			'sunday' => $sunday,
			'special_day' => $special_day,
			'time_threshold' => $threshold,
			'id_alert_action' => $default_action,
			'max_alerts' => $max_alerts,
			'min_alerts' => $min_alerts,
			'min_alerts_reset_counter' => $min_alerts_reset_counter,
			'type' => $type,
			'value' => $value,
			'max_value' => $max,
			'min_value' => $min,
			'matches_value' => $matches);
		
		// Different datetimes format for oracle
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				$values['time_from'] = $time_from;
				$values['time_to'] = $time_to;
				break;
			case "oracle":
				$values['time_from'] = "#to_date('" . $time_from . "','hh24:mi:ss')";
				$values['time_to'] = "#to_date('" . $time_to . "','hh24:mi:ss')";
				break;
		}
		
		$result = alerts_update_alert_template ($id, $values);
	}
	elseif ($step == 3) {
		$recovery_notify = (bool) get_parameter ('recovery_notify');
		for($i=1;$i<=$config['max_macro_fields'];$i++) {
			$values['field'.$i] = (string) get_parameter ('field'.$i);
			$values['field'.$i.'_recovery'] = $recovery_notify ? (string) get_parameter ('field'.$i.'_recovery') : '';
		}
		
		$values['recovery_notify'] = $recovery_notify;
		
		$result = alerts_update_alert_template ($id, $values);
	}
	else {
		return false;
	}
	
	if ($result) {
		db_pandora_audit("Template alert management", "Update alert template #" . $id, false, false, json_encode($values));
	}
	else {
		db_pandora_audit("Template alert management", "Fail try to update alert template #" . $id, false, false, json_encode($values));
	}
	
	return $result;
}

/* We set here the number of steps */
define ('LAST_STEP', 3);

$step = (int) get_parameter ('step', 1);

$create_alert = (bool) get_parameter ('create_alert');
$create_template = (bool) get_parameter ('create_template');
$update_template = (bool) get_parameter ('update_template');

$name = '';
$description = '';
$type = '';
$value = '';
$max = '';
$min = '';
$time_from = '12:00:00';
$time_to = '12:00:00';
$monday = true;
$tuesday = true;
$wednesday = true;
$thursday = true;
$friday = true;
$saturday = true;
$sunday = true;
$special_day = false;
$default_action = 0;
$fields = array();
for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
	$fields[$i] = '';
}
for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
	$fields_recovery[$i] = '';
}
$priority = 1;
$min_alerts = 0;
$min_alerts_reset_counter = 0;
$max_alerts = 1;
$threshold = SECONDS_1DAY;
$recovery_notify = false;
$field2_recovery = '';
$field3_recovery = '';
$matches = true;
$id_group = 0;
$wizard_level = 'nowizard';

if ($create_template) {
	$name = (string) get_parameter ('name');
	$description = (string) get_parameter ('description');
	$type = (string) get_parameter ('type', 'critical');
	$value = (string) get_parameter ('value');
	$max = (float) get_parameter ('max');
	$min = (float) get_parameter ('min');
	$matches = (bool) get_parameter ('matches_value');
	$priority = (int) get_parameter ('priority');
	$wizard_level = (string) get_parameter ('wizard_level');
	$id_group = get_parameter ("id_group");
	switch ($config["dbtype"]) {
		case "mysql": 
		case "postgresql": 
			$name_check = db_get_value ('name', 'talert_templates', 'name', $name);
			break;
		case "oracle":
			$name_check = db_get_value ('name', 'talert_templates', 'to_char(name)', $name);
			break;
	}
	
	
	$values = array ('description' => $description,
		'value' => $value,
		'max_value' => $max,
		'min_value' => $min,
		'id_group' => $id_group,
		'matches_value' => $matches,
		'priority' => $priority,
		'wizard_level' => $wizard_level);
	
	if ($config['dbtype'] == "oracle") {
		$values['field3'] = ' ';
		$values['field3_recovery'] = ' ';
	}
	
	if (!$name_check) {
		$result = alerts_create_alert_template ($name, $type, $values);
	}
	else {
		$result = '';
	}
	if ($result) {
		//db_pandora_audit("Command management", "Create alert command " . $result, false, false, json_encode($values));
		db_pandora_audit("Template alert management",
			"Create alert template #" . $result, false, false,
			json_encode($values));
	}
	else {
		//db_pandora_audit("Command management", "Fail try to create alert command", false, false, json_encode($values));
		db_pandora_audit("Template alert management",
			"Fail try to create alert template", false, false,
			json_encode($values));
	}
	
	ui_print_result_message ($result,
		__('Successfully created'),
		__('Could not be created'));
	/* Go to previous step in case of error */
	if ($result === false)
		$step = $step - 1;
	else
		$id = $result;
}

if ($update_template) {
	$result = update_template ($step - 1);
	
	ui_print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
	/* Go to previous step in case of error */
	if ($result === false) {
		$step = $step - 1;
	}
}

if ($id && ! $create_template) {
	$template = alerts_get_alert_template ($id);
	$name = $template['name'];
	$description = $template['description'];
	$type = $template['type'];
	$value = $template['value'];
	$max = $template['max_value'];
	$min = $template['min_value'];
	$matches = $template['matches_value'];
	$time_from = $template['time_from'];
	$time_to = $template['time_to'];
	$monday = (bool) $template['monday'];
	$tuesday = (bool) $template['tuesday'];
	$wednesday = (bool) $template['wednesday'];
	$thursday = (bool) $template['thursday'];
	$friday = (bool) $template['friday'];
	$saturday = (bool) $template['saturday'];
	$sunday = (bool) $template['sunday'];
	$special_day = (bool) $template['special_day'];
	$max_alerts = $template['max_alerts'];
	$min_alerts = $template['min_alerts'];
	$min_alerts_reset_counter = $template['min_alerts_reset_counter'];
	$threshold = $template['time_threshold'];
	$fields = array();
	for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
		$fields[$i] = $template['field'.$i];
	}
	$recovery_notify = $template['recovery_notify'];
	
	$fields_recovery = array();
	for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
		$fields_recovery[$i] = $template['field'.$i.'_recovery'];
	}
	
	$default_action = $template['id_alert_action'];
	$priority = $template['priority'];
	$id_group = $template['id_group'];
	$wizard_level = $template['wizard_level'];
}

print_alert_template_steps ($step, $id);

$table = new stdClass();
$table->id = 'template';
$table->width = '100%';
$table->class = 'databox filters';
if(defined("METACONSOLE")) {
	$table->head[0] = __('Create Template');
	$table->head_colspan[0] = 4;
	$table->headstyle[0] = 'text-align: center';
}
$table->style = array ();
$table->style[0] = 'font-weight: bold;';
$table->style[2] = 'font-weight: bold;';

$table->size = array ();
$table->size[0] = '20%';
$table->size[2] = '20%';

if ($step == 2) {
	
	if (!isset($show_matches))
		$show_matches = false;
	
	/* Firing conditions and events */
	$table->colspan = array ();
	$table->colspan[4][1] = 3;
	
	$table->data[0][0] = __('Days of week');
	$table->data[0][1] = __('Mon');
	$table->data[0][1] .= html_print_checkbox ('monday', 1, $monday, true);
	$table->data[0][1] .= __('Tue');
	$table->data[0][1] .= html_print_checkbox ('tuesday', 1, $tuesday, true);
	$table->data[0][1] .= __('Wed');
	$table->data[0][1] .= html_print_checkbox ('wednesday', 1, $wednesday, true);
	$table->data[0][1] .= __('Thu');
	$table->data[0][1] .= html_print_checkbox ('thursday', 1, $thursday, true);
	$table->data[0][1] .= __('Fri');
	$table->data[0][1] .= html_print_checkbox ('friday', 1, $friday, true);
	$table->data[0][1] .= __('Sat');
	$table->data[0][1] .= html_print_checkbox ('saturday', 1, $saturday, true);
	$table->data[0][1] .= __('Sun');
	$table->data[0][1] .= html_print_checkbox ('sunday', 1, $sunday, true);
	
	$table->data[0][2] = __('Use special days list');
	$table->data[0][3] = html_print_checkbox ('special_day', 1, $special_day, true);
	
	$table->data[1][0] = __('Time from') . ' ' .
		ui_print_help_tip(__('Time format in Pandora is hours(24h):minutes:seconds'), true);
	$table->data[1][1] = html_print_input_text ('time_from', $time_from, '', 7, 8,
		true);
	$table->data[1][2] = __('Time to') . ' ' .
		ui_print_help_tip(__('Time format in Pandora is hours(24h):minutes:seconds'), true);
	$table->data[1][3] = html_print_input_text ('time_to', $time_to, '', 7, 8,
		true);
	
	$table->colspan['threshold'][1] = 3;
	$table->data['threshold'][0] = __('Time threshold');
	$table->data['threshold'][1] = html_print_extended_select_for_time ('threshold', $threshold, '', '',
	'', false, true);
	
	$table->data[3][0] = __('Min. number of alerts');
	$table->data[3][1] = html_print_input_text ('min_alerts',
		$min_alerts, '', 5, 7, true);

	$table->data[3][2] = __('Reset counter when alert is not continuously') . ui_print_help_tip(__('Enable this option if you want to reset the counter for minimum number of alerts when the alert state is not continuously even if it\'s in the time threshold.'), true);;
	$table->data[3][3] = html_print_checkbox ('min_alerts_reset_counter', 1, $min_alerts_reset_counter, true);

	$table->data[4][0] = __('Max. number of alerts');
	$table->data[4][1] = html_print_input_text ('max_alerts',
		$max_alerts, '', 5, 7, true);
	
	$table->data[5][0] = __('Default action');
	$usr_groups = implode(',', array_keys(users_get_groups($config['id_user'], 'LM', true)));
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			$sql_query = sprintf('
				SELECT id, name
				FROM talert_actions
				WHERE id_group IN (%s)
				ORDER BY name', $usr_groups);
			break;
		case "oracle":
			$sql_query = sprintf('
				SELECT id,
					dbms_lob.substr(name,4000,1) AS nombre
				FROM talert_actions
				WHERE id_group IN (%s)
				ORDER BY dbms_lob.substr(name,4000,1)', $usr_groups);
			break;
	}
	$table->data[5][1] = html_print_select_from_sql ($sql_query,
			'default_action', $default_action, '', __('None'), 0,
			true, false, false, false, false, false, 0) .
		ui_print_help_tip (
			__('In case you fill any Field 1, Field 2 or Field 3 above, those will replace the corresponding fields of this associated "Default action".'), true);
	
	$table->data[6][0] = __('Condition type');
	$table->data[6][1] = html_print_select (alerts_get_alert_templates_types (), 'type',
		$type, '', __('Select'), 0, true, false, false);
	$table->data[6][1] .= '<span id="matches_value" ' .
		($show_matches ? '' : 'style="display: none"').'>';
	$table->data[6][1] .= '&nbsp;'.html_print_checkbox ('matches_value', 1, $matches, true);
	$table->data[6][1] .= html_print_label(
		__('Trigger when matches the value'),
		'checkbox-matches_value', true);
	$table->data[6][1] .= '</span>';
	$table->colspan[6][1] = 3;
	
	$table->data['value'][0] = __('Value');
	$table->data['value'][1] = html_print_input_text ('value', $value, '',
		35, 255, true);
	$table->data['value'][1] .= '&nbsp;<span id="regex_ok">';
	$table->data['value'][1] .= html_print_image ('images/suc.png', true,
		array ('style' => 'display:none',
			'id' => 'regex_good',
			'title' => __('The regular expression is valid'),
			'width' => '20px'));
	$table->data['value'][1] .= html_print_image ('images/err.png', true,
		array ('style' => 'display:none',
			'id' => 'regex_bad',
			'title' => __('The regular expression is not valid'),
			'width' => '20px'));
	$table->data['value'][1] .= '</span>';
	$table->colspan['value'][1] = 3;
	
	//Min first, then max, that's more logical
	$table->data['min'][0] = __('Min.');
	$table->data['min'][1] = html_print_input_text ('min', $min, '', 5,
		255, true);
	$table->colspan['min'][1] = 3;
	
	$table->data['max'][0] = __('Max.');
	$table->data['max'][1] = html_print_input_text ('max', $max, '', 5,
		255, true);
	$table->colspan['max'][1] = 3;
	
	$table->data['example'][1] = ui_print_alert_template_example($id,
		true, false);
	$table->colspan['example'][1] = 4;
}
else if ($step == 3) {
	$table->style[0] = 'font-weight: bold; vertical-align: middle';
	$table->style[1] = 'font-weight: bold; vertical-align: top';
	$table->style[2] = 'font-weight: bold; vertical-align: top';
	$table->size = array ();
	$table->size[0] = '10%';
	$table->size[1] = '45%';
	$table->size[2] = '45%';
	
	/* Alert recover */
	if (! $recovery_notify) {
		$table->cellstyle['label_fields'][2] = 'display:none;';
		for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
			$table->cellstyle['field' . $i][2] = 'display:none;';
		}
		/*
		$table->cellstyle['field1'][2] = 'display:none;';
		$table->cellstyle['field2'][2] = 'display:none;';
		$table->cellstyle['field3'][2] = 'display:none;';
		$table->cellstyle['field4'][2] = 'display:none;';
		$table->cellstyle['field5'][2] = 'display:none;';
		$table->cellstyle['field6'][2] = 'display:none;';
		$table->cellstyle['field7'][2] = 'display:none;';
		$table->cellstyle['field8'][2] = 'display:none;';
		$table->cellstyle['field9'][2] = 'display:none;';
		$table->cellstyle['field10'][2] = 'display:none;';
		*/
	}
	$table->data[0][0] = __('Alert recovery');
	$values = array (false => __('Disabled'), true => __('Enabled'));
	$table->data[0][1] = html_print_select ($values,
		'recovery_notify', $recovery_notify, '', '', '', true, false,
		false);
	$table->colspan[0][1] = 2;
	
	$table->data['label_fields'][0] = '';
	$table->data['label_fields'][1] = __('Firing fields');
	$table->data['label_fields'][2] = __('Recovery fields');
	
	for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
		if (isset($template[$name])) {
			$value = $template[$name];
		}
		else {
			$value = '';
		}
		
		//$table->rowclass['field'.$i] = 'row_field';
		
		
		$table->data['field'.$i][0] = sprintf(__('Field %s'), $i) . ui_print_help_icon ('alert_macros', true);
		//TinyMCE	
		//triggering fields
			//basic
			$table->data['field'.$i][1] = "<div style=\"padding: 4px 0px;\"><b><small>";
			$table->data['field'.$i][1] .= __('Basic') . "&nbsp;&nbsp;";
			$table->data['field'.$i][1] .= html_print_radio_button_extended ('editor_type_value_'.$i, 0, '', false, false, "removeTinyMCE('textarea_field".$i."')", '', true);
			//Advanced
			$table->data['field'.$i][1] .= "&nbsp;&nbsp;&nbsp;&nbsp;";
			$table->data['field'.$i][1] .= __('Advanced') . "&nbsp;&nbsp;";
			$table->data['field'.$i][1] .= html_print_radio_button_extended ('editor_type_value_'.$i, 0, '', true, false, "addTinyMCE('textarea_field".$i."')", '', true);
			$table->data['field'.$i][1] .= "</small></b></div>";
		
			//Texarea
			$table->data['field'.$i][1] .= html_print_textarea('field'.$i, 1, 1, isset($fields[$i]) ? $fields[$i] : '', 'style="min-height:40px;" class="fields"', true);
		
		// Recovery
			//basic
			$table->data['field'.$i][2] = "<div style=\"padding: 4px 0px;\"><b><small>";
			$table->data['field'.$i][2] .= __('Basic') . "&nbsp;&nbsp;";
			$table->data['field'.$i][2] .= html_print_radio_button_extended ('editor_type_recovery_value_'.$i, 0, '', false, false, "removeTinyMCE('textarea_field".$i."_recovery')", '', true);
			//advanced
			$table->data['field'.$i][2] .= "&nbsp;&nbsp;&nbsp;&nbsp;";
			$table->data['field'.$i][2] .= __('Advanced') . "&nbsp;&nbsp;";
			$table->data['field'.$i][2] .= html_print_radio_button_extended ('editor_type_recovery_value_'.$i, 0, '', true, false, "addTinyMCE('textarea_field".$i."_recovery')", '', true);
			$table->data['field'.$i][2] .= "</small></b></div>";

			//Texarea
			$table->data['field'.$i][2] .= html_print_textarea ('field'.$i.'_recovery', 1, 1, isset($fields_recovery[$i]) ? $fields_recovery[$i] : '', 'style="min-height:40px" class="fields"', true);
	}
}
else {
	/* Step 1 by default */
	$table->size = array ();
	$table->size[0] = '20%';
	$table->data = array ();
	$table->rowstyle = array ();
	$table->rowstyle['value'] = 'display: none';
	$table->rowstyle['max'] = 'display: none';
	$table->rowstyle['min'] = 'display: none';
	
	$show_matches = false;
	switch ($type) {
		case "equal":
		case "not_equal":
		case "regex":
			$show_matches = true;
			$table->rowstyle['value'] = '';
			break;
		case "max_min":
			$show_matches = true;
		case "max":
			$table->rowstyle['max'] = '';
			if ($type == 'max')
				break;
		case "min":
			$table->rowstyle['min'] = '';
			break;
		case "onchange":
			$show_matches = true;
			break;
	}
	
	$table->data[0][0] = __('Name');
	$table->data[0][1] = html_print_input_text ('name', $name, '', 35, 255, true);
	
	
	$table->data[0][1] .= "&nbsp;&nbsp;". __("Group");
	$groups = users_get_groups ();
	$own_info = get_user_info($config['id_user']);
	// Only display group "All" if user is administrator or has "PM" privileges
	if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
		$display_all_group = true;
	else
		$display_all_group = false;
	$table->data[0][1] .= "&nbsp;" .
		html_print_select_groups(false, "AR", $display_all_group, 'id_group', $id_group, '', '', 0, true);
	
	
	$table->data[1][0] = __('Description');
	$table->data[1][1] =  html_print_textarea ('description', 10, 30,
		$description, '', true);
	
	$table->data[2][0] = __('Priority');
	$table->data[2][1] = html_print_select (get_priorities (), 'priority',
		$priority, '', 0, 0, true, false, false);
	
	if(defined('METACONSOLE')) {
		$table->data[3][0] = __('Wizard level');
		$wizard_levels = array(
			'nowizard' => __('No wizard'),
			'basic' => __('Basic'),
			'advanced' => __('Advanced'));
		$table->data[3][1] = html_print_select($wizard_levels,'wizard_level',$wizard_level,'','',-1,true, false, false);
	}
	else {
		$table->data[2][1] .= html_print_input_hidden('wizard_level',$wizard_level,true);
	}
}

/* If it's the last step it will redirect to template lists */
if ($step >= LAST_STEP) {
	echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_templates&pure='.$pure.'">';
}
else {
	echo '<form method="post">';
}
html_print_table ($table);

echo '<div class="action-buttons" style="width: ' . $table->width . '">';
if ($id) {
	html_print_input_hidden ('id', $id);
	html_print_input_hidden ('update_template', 1);
}
else {
	html_print_input_hidden ('create_template', 1);
}

$disabled = false;
if (!$create_alert && !$create_template) {
	if ($a_template['id_group'] == 0) {
		// then must have "PM" access privileges
		if (! check_acl ($config['id_user'], 0, "PM")) {
			$disabled = true;
		}
	}
}

if (!$disabled) {
	if ($step >= LAST_STEP) {
		html_print_submit_button (__('Finish'), 'finish', false, 'class="sub upd"');
	}
	else {
		html_print_input_hidden ('step', $step + 1);
		if ($step == 2) {
			//Javascript onsubmit to avoid min = 0 and max = 0
			
			html_print_submit_button(__('Next'), 'next', false,
				'class="sub next" onclick="return check_fields_step2();"');
		}
		else {
			html_print_submit_button(__('Next'), 'next', false,
				'class="sub next"');
		}
	}
}

echo '</div>';
echo '</form>';

enterprise_hook('close_meta_frame');

ui_require_javascript_file ('pandora_alerts');
ui_include_time_picker();
ui_require_jquery_file("ui.datepicker-" . get_user_language(), "include/javascript/i18n/");
ui_require_javascript_file('tiny_mce', 'include/javascript/tiny_mce/');
?>

<script type="text/javascript">
/* <![CDATA[ */
var matches = <?php echo "'" . __("The alert would fire when the value matches <span id=\"value\"></span>") . "'";?>;
var matches_not = <?php echo "\"" . __("The alert would fire when the value doesn\'t match %s", "<span id='value'></span>") . "\"";?>;
var is = <?php echo "'" . __("The alert would fire when the value is <span id=\"value\"></span>") . "'";?>;
var is_not = <?php echo "'" . __("The alert would fire when the value is not <span id=\"value\"></span>") . "'";?>;
var between = <?php echo "'" . __("The alert would fire when the value is between <span id=\"min\"></span> and <span id=\"max\"></span>") . "'";?>;
var between_not = <?php echo "\"" . __("The alert would fire when the value is not between <span id=\'min\'></span> and <span id=\'max\'></span>") . "\"";?>;
var under = <?php echo "'" . __("The alert would fire when the value is below <span id=\"min\"></span>") . "'";?>;
var over = <?php echo "'" . __("The alert would fire when the value is above <span id=\"max\"></span>") . "'";?>;
var warning = <?php echo "'" . __("The alert would fire when the module is in warning status") . "'";?>;
var critical = <?php echo "'" . __("The alert would fire when the module is in critical status") . "'";?>;
var onchange_msg = <?php echo "\"" . __("The alert would fire when the module value changes") . "\"";?>;
var onchange_not = <?php echo "\"" . __("The alert would fire when the module value does not change") . "\"";?>;
var unknown = <?php echo "'" . __("The alert would fire when the module is in unknown status") . "'";?>;
var error_message_min_max_zero = <?php echo "'" . __("The alert template cannot have the same value for min and max thresholds.") . "'";?>;

function check_fields_step2() {
	var correct = true;
	
	type = $("select[name='type']").val();
	min_v = $("input[name='min']").val();
	max_v = $("input[name='max']").val();
	
	if (type == 'max_min') {
		if ((min_v == 0) && (max_v == 0)) {
			alert(error_message_min_max_zero);
			correct = false;
		}
	}
	
	return correct;
}

function check_regex () {
	if ($("#type").val() != 'regex') {
		$("img#regex_good, img#regex_bad").hide ();
		return;
	}
	
	try {
		re = new RegExp ($("#text-value").val());
	} catch (error) {
		$("img#regex_good").hide ();
		$("img#regex_bad").show ();
		return;
	}
	$("img#regex_bad").hide ();
	$("img#regex_good").show ();
}

function render_example () {
	/* Set max */
	var vmax = parseInt ($("input#text-max").val());
	if (isNaN (vmax) || vmax == "") {
		$("span#max").empty ().append ("0");
	}
	else {
		$("span#max").empty ().append (vmax);
	}
	
	/* Set min */
	var vmin = parseInt ($("input#text-min").val());
	if (isNaN (vmin) || vmin == "") {
		$("span#min").empty ().append ("0");
	}
	else {
		$("span#min").empty ().append (vmin);
	}
	
	/* Set value */
	var vvalue = $("input#text-value").val();
	if (vvalue == "") {
		$("span#value").empty ().append ("<em><?php echo __('Empty');?></em>");
	}
	else {
		$("span#value").empty ().append (vvalue);
	}
}

// Fix for metaconsole toggle
$('.row_field').css("display", "none");
var hided = true;

function toggle_fields() {
	$('.row_field').toggle();
	if (hided) {
		$('.row_field').css("display", "");
		hided = false;
	}
	else {
		$('.row_field').css("display", "none");
		hided = true;
	}
}

//toggle_fields();

$(document).ready (function () {
<?php
if ($step == 2) {
?>
	$("input#text-value").keyup (render_example);
	$("input#text-max").keyup (render_example);
	$("input#text-min").keyup (render_example);
	
	$("#type").change (function () {
		switch (this.value) {
		case "equal":
		case "not_equal":
			$("img#regex_good, img#regex_bad, span#matches_value").hide ();
			$("#template-max, #template-min").hide ();
			$("#template-value, #template-example").show ();
			
			/* Show example */
			if (this.value == "equal")
				$("span#example").empty ().append (is);
			else
				$("span#example").empty ().append (is_not);
			
			break;
		case "regex":
			$("#template-max, #template-min").hide ();
			$("#template-value, #template-example, span#matches_value").show ();
			check_regex ();
			
			/* Show example */
			if ($("#checkbox-matches_value")[0].checked)
				$("span#example").empty ().append (matches);
			else
				$("span#example").empty ().append (matches_not);
			
			break;
		case "max_min":
			$("#template-value").hide ();
			$("#template-max, #template-min, #template-example, span#matches_value").show ();
			
			/* Show example */
			if ($("#checkbox-matches_value")[0].checked)
				$("span#example").empty ().append (between);
			else
				$("span#example").empty ().append (between_not);
			
			break;
		case "max":
			$("#template-value, #template-min, span#matches_value").hide ();
			$("#template-max, #template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (over);
			break;
		case "min":
			$("#template-value, #template-max, span#matches_value").hide ();
			$("#template-min, #template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (under);
			break;
		case "warning":
			$("#template-value, #template-max, span#matches_value, #template-min").hide ();
			$("#template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (warning);
			break;
		case "critical":
			$("#template-value, #template-max, span#matches_value, #template-min").hide ();
			$("#template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (critical);
			break;
		case "onchange":
			$("#template-value, #template-max, #template-min").hide ();
			$("#template-example, span#matches_value").show ();
			
			/* Show example */
			if ($("#checkbox-matches_value")[0].checked)
				$("span#example").empty ().append (onchange_msg);
			else
				$("span#example").empty ().append (onchange_not);
			break;
		case "unknown":
			$("#template-value, #template-max, span#matches_value, #template-min").hide ();
			$("#template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (unknown);
			break;
		default:
			$("#template-value, #template-max, #template-min, #template-example, span#matches_value").hide ();
			break;
		}
		
		render_example ();
	}).change ();
	
	$("#checkbox-matches_value").click (function () {
		enabled = this.checked;
		type = $("#type").val();
		if (type == "regex") {
			if (enabled) {
				$("span#example").empty ().append (matches);
			}
			else {
				$("span#example").empty ().append (matches_not);
			}
		}
		else if (type == "max_min") {
			if (enabled) {
				$("span#example").empty ().append (between);
			}
			else {
				$("span#example").empty ().append (between_not);
			}
		}
		else if (type == "onchange") {
			if (enabled) {
				$("span#example").empty ().append (onchange_msg);
			}
			else {
				$("span#example").empty ().append (onchange_not);
			}
		} 
		render_example ();
	});
	
	$("#text-value").keyup (check_regex);
	
	$('#text-time_from, #text-time_to').timepicker({
		showSecond: true,
		timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
		timeOnlyTitle: '<?php echo __('Choose time');?>',
		timeText: '<?php echo __('Time');?>',
		hourText: '<?php echo __('Hour');?>',
		minuteText: '<?php echo __('Minute');?>',
		secondText: '<?php echo __('Second');?>',
		currentText: '<?php echo __('Now');?>',
		closeText: '<?php echo __('Close');?>'});
	
	$("#threshold").change (function () {
		if (this.value == -1) {
			$("#text-other_threshold").val("");
			$("#template-threshold-other_label").show ();
			$("#template-threshold-other_input").show ();
		}
		else {
			$("#template-threshold-other_label").hide ();
			$("#template-threshold-other_input").hide ();
		}
	});
<?php
}
elseif ($step == 3) {
?>
	$("#recovery_notify").change (function () {
		var max_fields = parseInt('<?php echo $config["max_macro_fields"]; ?>');

		if (this.value == 1) {
			$("#template-label_fields-2").show();
			for (i = 1; i <= max_fields; i++) {
				$("#template-field" + i + "-2").show();
			}
			//$("#template-label_fields-2, #template-field1-2, #template-field2-2, #template-field3-2, #template-field4-2, #template-field5-2, #template-field6-2, #template-field7-2, #template-field8-2, #template-field9-2, #template-field10-2").show ();
		}
		else {
			$("#template-label_fields-2").hide();
			for (i = 1; i <= max_fields; i++) {
				$("#template-field" + i + "-2").hide();
			}
			//$("#template-label_fields-2, #template-field1-2, #template-field2-2, #template-field3-2, #template-field4-2, #template-field5-2, #template-field6-2, #template-field7-2, #template-field8-2, #template-field9-2, #template-field10-2").hide ();
		}
	});

	tinyMCE.init({
		selector: 'textarea.tiny-mce-editor',
		theme : "advanced",
		plugins : "preview, print, table, searchreplace, nonbreaking, xhtmlxtras, noneditable",
		theme_advanced_buttons1 : "bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsize,select",
		theme_advanced_buttons2 : "search,replace,|,bullist,numlist,|,undo,redo,|,link,unlink,image,|,cleanup,code,preview,|,forecolor,backcolor",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_resizing : true,
		theme_advanced_statusbar_location : "bottom",
		force_p_newlines : false,
		forced_root_block : '',
		inline_styles : true,
		valid_children : "+body[style]",
		element_format : "html"
	});
<?php
}
?>
})
/* ]]> */
</script>
