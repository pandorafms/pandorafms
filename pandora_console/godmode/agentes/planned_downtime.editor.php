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

check_login();

$agent_d = check_acl ($config['id_user'], 0, "AD");
$agent_w = check_acl ($config['id_user'], 0, "AW");
$access = ($agent_d == true) ? 'AD' : ($agent_w == true) ? 'AW' : 'AD';
if (!$agent_d && !$agent_w) {
	db_pandora_audit("ACL Violation",
		"Trying to access downtime scheduler");
	require ("general/noaccess.php");
	return;
}

// Default
set_unless_defined ($config["past_planned_downtimes"], 1);

require_once ('include/functions_users.php');

// Buttons
$buttons = array(
		'text' => "<a href='index.php?sec=extensions&sec2=godmode/agentes/planned_downtime.list'>"
			. html_print_image ("images/list.png", true, array ("title" =>__('List'))) . "</a>"
	);

// Header
ui_print_page_header(
	__("Planned Downtime"),
	"images/gm_monitoring.png",
	false,
	"planned_downtime",
	true,
	$buttons);

//Initialize data
$id_group 				= (int) get_parameter ('id_group');
$name 					= (string) get_parameter ('name');
$description 			= (string) get_parameter ('description');

$type_downtime 			= (string) get_parameter('type_downtime', 'quiet');
$type_execution 		= (string) get_parameter('type_execution', 'once');
$type_periodicity 		= (string) get_parameter('type_periodicity', 'weekly');

$once_date_from 		= (string) get_parameter ('once_date_from', date(DATE_FORMAT));
$once_time_from 		= (string) get_parameter ('once_time_from', date(TIME_FORMAT));
$once_date_to 			= (string) get_parameter ('once_date_to', date(DATE_FORMAT));
$once_time_to 			= (string) get_parameter ('once_time_to', date(TIME_FORMAT, time() + SECONDS_1HOUR));

$periodically_day_from 	= (int) get_parameter ('periodically_day_from', 1);
$periodically_day_to 	= (int) get_parameter ('periodically_day_to', 31);
$periodically_time_from = (string) get_parameter ('periodically_time_from', date(TIME_FORMAT));
$periodically_time_to 	= (string) get_parameter ('periodically_time_to', date(TIME_FORMAT, time() + SECONDS_1HOUR));

$monday 				= (bool) get_parameter ('monday');
$tuesday 				= (bool) get_parameter ('tuesday');
$wednesday 				= (bool) get_parameter ('wednesday');
$thursday 				= (bool) get_parameter ('thursday');
$friday 				= (bool) get_parameter ('friday');
$saturday 				= (bool) get_parameter ('saturday');
$sunday 				= (bool) get_parameter ('sunday');

$first_create 			= (int) get_parameter ('first_create');
$create_downtime 		= (int) get_parameter ('create_downtime');
$update_downtime 		= (int) get_parameter ('update_downtime');
$edit_downtime 			= (int) get_parameter ('edit_downtime');
$id_downtime 			= (int) get_parameter ('id_downtime');

$id_agent 				= (int) get_parameter ('id_agent');
$insert_downtime_agent 	= (int) get_parameter ('insert_downtime_agent');
$delete_downtime_agent 	= (int) get_parameter ('delete_downtime_agent');

// User groups with AD or AW permission for ACL checks
$user_groups_ad = array_keys(users_get_groups($config['id_user'], $access));

// INSERT A NEW DOWNTIME_AGENT ASSOCIATION
if ($insert_downtime_agent === 1) {
	
	// Check AD permission on downtime
	$downtime_group = db_get_value('id_group', 'tplanned_downtime', 'id', $id_downtime);
	
	if ($downtime_group === false || !in_array($downtime_group, $user_groups_ad)) {
		db_pandora_audit("ACL Violation",
			"Trying to access downtime scheduler");
		require ("general/noaccess.php");
		return;
	}
	
	$agents = (array) get_parameter ('id_agents');
	$module_names = (array) get_parameter ('module');
	
	$all_modules = (empty($module_names) || ($module_names[0] === "0"));
	
	// 'Is running' check
	$is_running = (bool) db_get_value ('executed', 'tplanned_downtime', 'id', $id_downtime);
	if ($is_running) {
		ui_print_error_message(__("This elements cannot be modified while the downtime is being executed"));
	}
	else {
		foreach ($agents as $agent_id) {
			
			// Check AD permission on agent
			$agent_group = db_get_value('id_grupo', 'tagente', 'id_agente', $agent_id);
			
			if ($agent_group === false || !in_array($agent_group, $user_groups_ad)) {
				continue;
			}
			
			$values = array(
					'id_downtime' => $id_downtime,
					'id_agent' => $agent_id,
					'all_modules' => $all_modules
				);
			$result = db_process_sql_insert('tplanned_downtime_agents', $values);
			
			if ($result && !$all_modules) {
				foreach ($module_names as $module_name) {
					$module = modules_get_agentmodule_id($module_name, $agent_id);
					
					if (empty($module))
						continue;
					
					$values = array(
							'id_downtime' => $id_downtime,
							'id_agent' => $agent_id,
							'id_agent_module' => $module["id_agente_modulo"]
						);
					$result = db_process_sql_insert('tplanned_downtime_modules', $values);
					
					if ($result) {
						$values = array('id_user' => $config['id_user']);
						$result = db_process_sql_update('tplanned_downtime',
							$values, array('id' => $id_downtime));
					}
				}
			}
		}
	}
}

// DELETE A DOWNTIME_AGENT ASSOCIATION
if ($delete_downtime_agent === 1) {
	
	$id_da = (int) get_parameter ('id_downtime_agent');
	
	// Check AD permission on downtime
	$downtime_group = db_get_value('id_group', 'tplanned_downtime', 'id', $id_downtime);
	
	if ($downtime_group === false || !in_array($downtime_group, $user_groups_ad)) {
		db_pandora_audit("ACL Violation",
			"Trying to access downtime scheduler");
		require ("general/noaccess.php");
		return;
	}
	
	// Check AD permission on agent
	$agent_group = db_get_value('id_grupo', 'tagente', 'id_agente', $id_agent);
	
	if ($agent_group === false || !in_array($agent_group, $user_groups_ad)) {
		db_pandora_audit("ACL Violation",
			"Trying to access downtime scheduler");
		require ("general/noaccess.php");
		return;
	}
	
	// 'Is running' check
	$is_running = (bool) db_get_value ('executed', 'tplanned_downtime', 'id', $id_downtime);
	if ($is_running) {
		ui_print_error_message(__("This elements cannot be modified while the downtime is being executed"));
	}
	else {
		$row_to_delete = db_get_row('tplanned_downtime_agents', 'id', $id_da);
		
		$result = db_process_sql_delete('tplanned_downtime_agents', array('id' => $id_da));
		
		if ($result) {
			//Delete modules in downtime
			db_process_sql_delete('tplanned_downtime_modules',
				array('id_downtime' => $row_to_delete['id_downtime'],
					'id_agent' => $id_agent));
		}
	}
}

// UPDATE OR CREATE A DOWNTIME (MAIN DATA, NOT AGENT ASSOCIATION)
if ($create_downtime || $update_downtime) {
	$check = (bool) db_get_value ('name', 'tplanned_downtime', 'name', $name);
	
	$datetime_from = strtotime ($once_date_from . ' ' . $once_time_from);
	$datetime_to = strtotime ($once_date_to . ' ' . $once_time_to);
	$now = time();
	
	if ($type_execution == 'once' && !$config["past_planned_downtimes"] && $datetime_from < $now) {
		ui_print_error_message(__('Not created. Error inserting data. Start time must be higher than the current time' ));
	}
	else if ($type_execution == 'once' && $datetime_from >= $datetime_to) {
		ui_print_error_message(__('Not created. Error inserting data') . ". " .__('The end date must be higher than the start date'));
	}
	else if ($type_execution == 'once' && $datetime_to <= $now && !$config["past_planned_downtimes"]) {
		ui_print_error_message(__('Not created. Error inserting data') . ". " .__('The end date must be higher than the current time'));
	}
	else if ($type_execution == 'periodically'
			&& (($type_periodicity == 'weekly' && $periodically_time_from >= $periodically_time_to)
				|| ($type_periodicity == 'monthly' && $periodically_day_from == $periodically_day_to && $periodically_time_from >= $periodically_time_to))) {
		ui_print_error_message(__('Not created. Error inserting data') . ". " .__('The end time must be higher than the start time'));
	}
	else if ($type_execution == 'periodically' && $type_periodicity == 'monthly' && $periodically_day_from > $periodically_day_to) {
		ui_print_error_message(__('Not created. Error inserting data') . ". " .__('The end day must be higher than the start day'));
	}
	else {
		$sql = '';
		if ($create_downtime) {
			
			// Check AD permission on new downtime
			if (!in_array($id_group, $user_groups_ad)) {
				db_pandora_audit("ACL Violation",
					"Trying to access downtime scheduler");
				require ("general/noaccess.php");
				return;
			}
			
			if (trim(io_safe_output($name)) != '') {
				if (!$check) {
					$values = array(
							'name' => $name,
							'description' => $description,
							'date_from' => $datetime_from,
							'date_to' => $datetime_to,
							'executed' => 0,
							'id_group' => $id_group,
							'only_alerts' => 0,
							'monday' => $monday,
							'tuesday' => $tuesday,
							'wednesday' => $wednesday,
							'thursday' => $thursday,
							'friday' => $friday,
							'saturday' => $saturday,
							'sunday' => $sunday,
							'periodically_time_from' => $periodically_time_from,
							'periodically_time_to' => $periodically_time_to,
							'periodically_day_from' => $periodically_day_from,
							'periodically_day_to' => $periodically_day_to,
							'type_downtime' => $type_downtime,
							'type_execution' => $type_execution,
							'type_periodicity' => $type_periodicity,
							'id_user' => $config['id_user']
						);
					if ($config["dbtype"] == 'oracle') {
						$values['periodically_time_from'] = '1970/01/01 ' . $values['periodically_time_from'];
						$values['periodically_time_to'] = '1970/01/01 ' . $values['periodically_time_to'];
					}
					
					$result = db_process_sql_insert('tplanned_downtime', $values);
				}
				else {
					ui_print_error_message(
						__('Each planned downtime must have a different name'));
				}
			}
			else {
				ui_print_error_message(
					__('Planned downtime must have a name'));
			}
		}
		else if ($update_downtime) {
			$old_downtime = db_get_row('tplanned_downtime', 'id', $id_downtime);
			
			// Check AD permission on OLD downtime
			if (empty($old_downtime) || !in_array($old_downtime['id_group'], $user_groups_ad)) {
				db_pandora_audit("ACL Violation",
					"Trying to access downtime scheduler");
				require ("general/noaccess.php");
				return;
			}
			
			// Check AD permission on NEW downtime group
			if (!in_array($id_group, $user_groups_ad)) {
				db_pandora_audit("ACL Violation",
					"Trying to access downtime scheduler");
				require ("general/noaccess.php");
				return;
			}
			
			// 'Is running' check
			$is_running = (bool) $old_downtime['executed'];
			
			$values = array();
			if (trim(io_safe_output($name)) == '') {
				ui_print_error_message(__('Planned downtime must have a name'));
			}
			// When running only certain items can be modified for the 'once' type
			else if ($is_running && $type_execution == 'once') {
				$values = array(
					'description' => $description,
					'date_to' => $datetime_to,
					'id_user' => $config['id_user']
				);
			}
			else if ($is_running) {
				ui_print_error_message(__('Cannot be modified while the downtime is being executed'));
			}
			else {
				$values = array(
						'name' => $name,
						'description' => $description,
						'date_from' => $datetime_from,
						'date_to' => $datetime_to,
						'id_group' => $id_group,
						'only_alerts' => 0,
						'monday' => $monday,
						'tuesday' => $tuesday,
						'wednesday' => $wednesday,
						'thursday' => $thursday,
						'friday' => $friday,
						'saturday' => $saturday,
						'sunday' => $sunday,
						'periodically_time_from' => $periodically_time_from,
						'periodically_time_to' => $periodically_time_to,
						'periodically_day_from' => $periodically_day_from,
						'periodically_day_to' => $periodically_day_to,
						'type_downtime' => $type_downtime,
						'type_execution' => $type_execution,
						'type_periodicity' => $type_periodicity,
						'id_user' => $config['id_user']
					);
				if ($config["dbtype"] == 'oracle') {
					$values['periodically_time_from'] = '1970/01/01 ' . $values['periodically_time_from'];
					$values['periodically_time_to'] = '1970/01/01 ' . $values['periodically_time_to'];
				}
			}
			if (!empty($values)) {
				$result = db_process_sql_update('tplanned_downtime', $values, array('id' => $id_downtime));
			}
		}
		
		if ($result === false) {
			if ($create_downtime) {
				ui_print_error_message(__('Could not be created'));
			}
			else {
				ui_print_error_message(__('Could not be updated'));
			}
		}
		else {
			if ($create_downtime && $name && !$check) {
				$id_downtime = $result;
				ui_print_success_message(__('Successfully created'));
			}
			else if ($update_downtime && $name) {
				ui_print_success_message(__('Successfully updated'));
			}
		}
	}
}

// Have any data to show ?
if ($id_downtime > 0) {
	// Columns of the table tplanned_downtime
	$columns = array(
			'id',
			'name',
			'description',
			'date_from',
			'date_to',
			'executed',
			'id_group',
			'only_alerts',
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
			'sunday',
			'periodically_time_from',
			'periodically_time_to',
			'periodically_day_from',
			'periodically_day_to',
			'type_downtime',
			'type_execution',
			'type_periodicity',
			'id_user',
		);
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$columns_str = implode(',', $columns);
			$sql = "SELECT $columns_str
					FROM tplanned_downtime
					WHERE id = $id_downtime";
			break;
		case "oracle":
			// Oracle doesn't have TIME type, so we should transform the DATE value
			$new_time_from = "TO_CHAR(periodically_time_from, 'HH24:MI:SS') AS periodically_time_from";
			$new_time_to = "TO_CHAR(periodically_time_to, 'HH24:MI:SS') AS periodically_time_to";
			
			$time_from_key = array_search('periodically_time_from', $columns);
			$time_to_key = array_search('periodically_time_to', $columns);
			
			if ($time_from_key !== false)
				$columns[$time_from_key] = $new_time_from;
			if ($time_to_key !== false)
				$columns[$time_to_key] = $new_time_to;
			
			$columns_str = implode(',', $columns);
			$sql = "SELECT $columns_str
					FROM tplanned_downtime
					WHERE id = $id_downtime";
			break;
	}
	
	$result = db_get_row_sql ($sql);
	
	// Permission check for the downtime with the AD user groups
	if (empty($result) || !in_array($result['id_group'], $user_groups_ad) ){
		db_pandora_audit("ACL Violation",
		"Trying to access downtime scheduler");
		require ("general/noaccess.php");
		return;
	}
	
	$name 					= (string) $result["name"];
	$id_group 				= (int) $result['id_group'];

	$description 			= (string) $result["description"];
	
	$type_downtime 			= (string) $result['type_downtime'];
	$type_execution 		= (string) $result['type_execution'];
	$type_periodicity 		= (string) $result['type_periodicity'];
	
	$once_date_from 		= date(DATE_FORMAT, $result["date_from"]);
	$once_date_to 			= date(DATE_FORMAT, $result["date_to"]);
	$once_time_from 		= date(TIME_FORMAT, $result["date_from"]);
	$once_time_to 			= date(TIME_FORMAT, $result["date_to"]);
	
	$periodically_time_from = (string) $result['periodically_time_from'];
	$periodically_time_to 	= (string) $result['periodically_time_to'];
	$periodically_day_from 	= (int) $result['periodically_day_from'];
	$periodically_day_to 	= (int) $result['periodically_day_to'];
	
	$monday 				= (bool) $result['monday'];
	$tuesday 				= (bool) $result['tuesday'];
	$wednesday 				= (bool) $result['wednesday'];
	$thursday 				= (bool) $result['thursday'];
	$friday 				= (bool) $result['friday'];
	$saturday 				= (bool) $result['saturday'];
	$sunday 				= (bool) $result['sunday'];
	
	$running 				= (bool) $result['executed'];
}

// when the planned downtime is in execution, only action to postpone on once type is enabled and the other are disabled.
$disabled_in_execution = (int) $running;

$table = new StdClass();
$table->class = 'databox filters';
$table->width = '100%';
$table->data = array ();
$table->data[0][0] = __('Name');
$table->data[0][1] = html_print_input_text ('name', $name, '', 25, 40, true, $disabled_in_execution);
$table->data[1][0] = __('Group');
$table->data[1][1] = html_print_select_groups(false, $access, true, 'id_group', $id_group, '', '', 0, true, false, true, '', $disabled_in_execution);
$table->data[2][0] = __('Description');
$table->data[2][1] = html_print_textarea ('description', 3, 35, $description, '', true);

$table->data[3][0] = __('Type').ui_print_help_tip(__("Quiet: Modules will not generate events or fire alerts.").'<br>'.
	__("Disable Agents: Disables the selected agents.").'<br>'.
	__("Disable Alerts: Disable alerts for the selected agents."), true);
$table->data[3][1] = html_print_select(array('quiet' => __('Quiet'),
	'disable_agents' => __('Disabled Agents'),
	'disable_agents_alerts' => __('Disabled only Alerts')),
	'type_downtime', $type_downtime, 'change_type_downtime()', '', 0, true, false, true,
	'', $disabled_in_execution);
$table->data[4][0] = __('Execution');
$table->data[4][1] = html_print_select(array('once' => __('Once'),
	'periodically' => __('Periodically')),
	'type_execution', $type_execution, 'change_type_execution();', '', 0, true,
	false, true, '', $disabled_in_execution);

$days = array_combine(range(1, 31), range(1, 31));
$table->data[5][0] = __('Configure the time') . "&nbsp;" . ui_print_help_icon ('planned_downtime_time', true);;
$table->data[5][1] = "
	<div id='once_time' style='display: none;'>
		<table>
			<tr>
				<td>" .
					__('From:') .
					"</td>
				<td>".
				html_print_input_text ('once_date_from', $once_date_from, '', 10, 10, true, $disabled_in_execution) .
				ui_print_help_tip(__('Date format in Pandora is year/month/day'), true) .
				html_print_input_text ('once_time_from', $once_time_from, '', 9, 9, true, $disabled_in_execution) .
				ui_print_help_tip(__('Time format in Pandora is hours(24h):minutes:seconds'), true) .
				"</td>
			</tr>
			<tr>
				<td>" .
					__('To:') .
					"</td>
				<td>".
				html_print_input_text ('once_date_to', $once_date_to, '', 10, 10, true) .
				ui_print_help_tip(__('Date format in Pandora is year/month/day'), true) .
				html_print_input_text ('once_time_to', $once_time_to, '', 9, 9, true) .
				ui_print_help_tip(__('Time format in Pandora is hours(24h):minutes:seconds'), true) .
				"</td>
			</tr>
		</table>
	</div>
	<div id='periodically_time' style='display: none;'>
		<table>
			<tr>
				<td>" . __('Type Periodicity:') . "&nbsp;".
					html_print_select(array(
							'weekly' => __('Weekly'),
							'monthly' => __('Monthly')),
						'type_periodicity', $type_periodicity,
						'change_type_periodicity();', '', 0, true,
						false, true, '', $disabled_in_execution) .
				"</td>
			</tr>
			<tr>
				<td colspan='2'>
					<table id='weekly_item' style='display: none;'>
						<tr>
							<td>" . __('Mon') .
							html_print_checkbox ('monday', 1, $monday, true, $disabled_in_execution) .
							"</td>
							<td>" . __('Tue') .
							html_print_checkbox ('tuesday', 1, $tuesday, true, $disabled_in_execution) .
							"</td>
							<td>" . __('Wed') .
							html_print_checkbox ('wednesday', 1, $wednesday, true, $disabled_in_execution) .
							"</td>
							<td>" . __('Thu') .
							html_print_checkbox ('thursday', 1, $thursday, true, $disabled_in_execution) .
							"</td>
							<td>" . __('Fri') .
							html_print_checkbox ('friday', 1, $friday, true, $disabled_in_execution) .
							"</td>
							<td>" . __('Sat') .
							html_print_checkbox ('saturday', 1, $saturday, true, $disabled_in_execution) .
							"</td>
							<td>" . __('Sun') .
							html_print_checkbox ('sunday', 1, $sunday, true, $disabled_in_execution) .
							"</td>
						</tr>
					</table>
					<table id='monthly_item' style='display: none;'>
						<tr>
							<td>" . __('From day:') . "</td>
							<td>".
								html_print_select($days,
									'periodically_day_from', $periodically_day_from, '', '', 0, true,
									false, true, '', $disabled_in_execution) .
							"</td>
							<td>" . __('To day:') . "</td>
							<td>".
								html_print_select($days,
									'periodically_day_to', $periodically_day_to, '', '', 0, true,
									false, true, '', $disabled_in_execution) .
							"</td>
							<td>" . ui_print_help_tip(__('The end day must be higher than the start day'), true) . "</td>
						</tr>
					</table>
					<table>
						<tr>
							<td>" . __('From hour:') . "</td>
							<td>".
							html_print_input_text (
								'periodically_time_from',
								$periodically_time_from, '', 7, 7, true, $disabled_in_execution) . 
							ui_print_help_tip(__('Time format in Pandora is hours(24h):minutes:seconds').
								".<br>".__('The end time must be higher than the start time'), true) .
							"</td>
							<td>" . __('To hour:') . "</td>
							<td>".
							html_print_input_text (
								'periodically_time_to',
								$periodically_time_to, '', 7, 7, true, $disabled_in_execution) .
							ui_print_help_tip(__('Time format in Pandora is hours(24h):minutes:seconds').
								".<br>".__('The end time must be higher than the start time'), true) .
							"</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>";

echo '<form method="POST" action="index.php?sec=estado&amp;sec2=godmode/agentes/planned_downtime.editor">';

if ($id_downtime > 0) {
	echo "<table width=100% border=0 cellpadding=4 >";
	echo "<tr><td width=75% valign='top'>";
}

//Editor form
html_print_table ($table);

html_print_input_hidden ('id_agent', $id_agent);
echo '<div class="action-buttons" style="width: 100%">';
if ($id_downtime > 0) {
	html_print_input_hidden ('update_downtime', 1);
	html_print_input_hidden ('id_downtime', $id_downtime);
	html_print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
}
else {
	html_print_input_hidden ('create_downtime', 1);
	html_print_submit_button (__('Add'), 'crtbutton', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';

if ($id_downtime > 0) {
	
	echo "<td valign=top>";
	// Show available agents to include into downtime
	echo '<h4>' . __('Available agents') . ':</h4>';
	
	$filter_group = (int) get_parameter("filter_group", 0);
	
	// User AD groups to str for the filter
	$id_groups_str = implode(",", $user_groups_ad);
	
	if (empty($id_groups_str)) {
		// Restrictive filter on error. This will filter all the downtimes
		$id_groups_str = '-1';
	}
	
	$filter_cond = '';
	if ($filter_group > 0)
		$filter_cond = " AND id_grupo = $filter_group ";
	$sql = sprintf("SELECT tagente.id_agente, tagente.nombre
					FROM tagente
					WHERE tagente.id_agente NOT IN (
							SELECT tagente.id_agente
							FROM tagente, tplanned_downtime_agents
							WHERE tplanned_downtime_agents.id_agent = tagente.id_agente
								AND tplanned_downtime_agents.id_downtime = %d
						) AND disabled = 0 %s
						AND tagente.id_grupo IN (%s)
					ORDER BY tagente.nombre", $id_downtime, $filter_cond, $id_groups_str);
	$agents = db_get_all_rows_sql ($sql);
	if (empty($agents))
		$agents = array();
	
	$agent_ids = extract_column($agents, 'id_agente');
	$agent_names = extract_column($agents, 'nombre');
	// item[<id>] = <name>;
	$agents = array_combine($agent_ids, $agent_names);
	if ($agents === false)
		$agents = array();
	
	$disabled_add_button = false;
	if (empty($agents) || $disabled_in_execution) {
		$disabled_add_button = true;
	}
	
	echo "<form method=post action='index.php?sec=estado&sec2=godmode/agentes/planned_downtime.editor&id_downtime=$id_downtime'>";
	
	html_print_select_groups(false, $access, true, 'filter_group', $filter_group, '', '', '', false, false, true, '', false, 'width:180px');
	
	echo "<br /><br />";
	html_print_submit_button (__('Filter by group'), '', false, 'class="sub next"',false);
	echo "</form>";
	
	echo "<form method=post action='index.php?sec=estado&sec2=godmode/agentes/planned_downtime.editor&insert_downtime_agent=1&id_downtime=$id_downtime'>";
	
	echo html_print_select ($agents, "id_agents[]", '', '', '', 0, false, true, true, '', false, 'width: 180px;');
	echo '<h4>' . __('Available modules:') . 
		ui_print_help_tip (__('Only for type Quiet for downtimes.'), true) . '</h4>';
	
	if ($type_downtime != 'quiet')
		echo '<div id="available_modules" style="display: none;">';
	else
		echo '<div id="available_modules" style="">';
	echo html_print_select (array(), "module[]", '', '', '', 0, false, true, true, '', false, 'width: 180px;');
	echo "</div>";
	echo "<br /><br /><br />";
	html_print_submit_button (__('Add'), 'add_item', $disabled_add_button, 'class="sub next"',false);
	echo "</form>";
	echo "</table>";
	
	//Start Overview of existing planned downtime
	echo '<h4>'.__('Agents planned for this downtime').':</h4>';
	
	// User the $id_groups_str built before
	$sql = sprintf("SELECT ta.nombre, tpda.id,
						ta.id_os, ta.id_agente, ta.id_grupo,
						ta.ultimo_contacto, tpda.all_modules
					FROM tagente ta
					INNER JOIN tplanned_downtime_agents tpda
						ON ta.id_agente = tpda.id_agent
							AND tpda.id_downtime = %d
					WHERE ta.id_grupo IN (%s)",
					$id_downtime, $id_groups_str);
	$downtimes_agents = db_get_all_rows_sql ($sql);
	
	if (empty($downtimes_agents)) {
		echo '<div class="nf">' . __('There are no agents') . '</div>';
	}
	else {
		$table = new stdClass();
		$table->id = 'list';
		$table->class = 'databox data';
		$table->width = '100%';
		$table->data = array ();
		$table->head = array ();
		$table->head[0] = __('Name');
		$table->head[1] = __('Group');
		$table->head[2] = __('OS');
		$table->head[3] = __('Last contact');
		$table->head['count_modules'] = __('Modules');
		
		if (!$running) {
			$table->head[5] = __('Actions');
			$table->align[5] = "center";
			$table->size[5] = "5%";
		}
		
		foreach ($downtimes_agents as $downtime_agent) {
			$data = array ();
			
			$data[0] = $downtime_agent['nombre'];
			
			$data[1] = db_get_sql ("SELECT nombre
									FROM tgrupo
									WHERE id_grupo = " . $downtime_agent["id_grupo"]);
			
			$data[2] = ui_print_os_icon($downtime_agent["id_os"], true, true);
			
			$data[3] = $downtime_agent["ultimo_contacto"];
			
			if ($type_downtime == 'disable_agents_alerts') {
				$data['count_modules'] = __("All alerts");
			}
			elseif ($type_downtime == 'disable_agents') {
				$data['count_modules'] = __("Entire agent");
			}
			else {
				if ($downtime_agent["all_modules"]) {
					$data['count_modules'] = __("All modules");
				}
				else {
					$data['count_modules'] = __("Some modules");
				}
			}
			
			if (!$running) {
				$data[5] = '';
				if ($type_downtime != 'disable_agents_alerts' && $type_downtime != 'disable_agents') {
					$data[5] = '<a href="javascript:show_editor_module(' . $downtime_agent["id_agente"] . ');">' .
						html_print_image("images/config.png", true, array("border" => '0', "alt" => __('Delete'))) . "</a>";
				}
				
				$data[5] .= '<a href="index.php?sec=estado&amp;sec2=godmode/agentes/planned_downtime.editor&id_agent=' . $downtime_agent["id_agente"] . 
					'&delete_downtime_agent=1&id_downtime_agent=' . $downtime_agent["id"] . '&id_downtime=' . $id_downtime . '">' .
					html_print_image("images/cross.png", true, array("border" => '0', "alt" => __('Delete'))) . "</a>";
			}
			
			$table->data['agent_' . $downtime_agent["id_agente"]] = $data;
		}
		html_print_table ($table);
	}
}

$table = new stdClass();
$table->id = 'loading';
$table->width = '100%';
$table->colspan['loading'][0] = '6';
$table->style[0] = 'text-align: center;';
$table->data = array();
$table->data['loading'] = array();
$table->data['loading'][0] = html_print_image("images/spinner.gif", true);
echo "<div style='display: none;'>";
html_print_table ($table);
echo "</div>";

$table = new stdClass();
$table->id = 'editor';
$table->width = '100%';
$table->colspan['module'][1] = '5';
$table->data = array();
$table->data['module'] = array();
$table->data['module'][0] = '';
$table->data['module'][1] = "<h4>" . __('Modules') . "</h4>";

//List of modules, empty, it is populated by javascript.
$table->data['module'][1] = "
	<table cellspacing='4' cellpadding='4' border='0' width='100%'
		id='modules_in_agent' class='databox_color'>
		<thead>
			<tr>
				<th scope='col' class='header c0'>" . __('Module') . "</th>
				<th scope='col' class='header c1'>" . __('Action') . "</th>
			</tr>
		</thead>
		<tbody>
			<tr class='datos' id='template' style='display: none;'>
				<td class='name_module' style=''></td>
				<td class='cell_delete_button' style='text-align: right; width:10%;' id=''>"
					. '<a class="link_delete"
						onclick="if(!confirm(\'' . __('Are you sure?') . '\')) return false;"
						href="">'
					. html_print_image("images/cross.png", true,
							array("border" => '0', "alt" => __('Delete'))) . "</a>"
				. "</td>
			</tr>
			<tr class='datos2' id='add_modules_row'>
				<td class='datos2' style='' id=''>"
					. __("Add Module:") . '&nbsp;'
					. html_print_select(array(),
						'modules', '', '', '', 0, true)
				. "</td>
				<td class='datos2 button_cell' style='text-align: right; width:10%;' id=''>"
					. '<div id="add_button_div">'
					. '<a class="add_button" href="">'
					. html_print_image("images/add.png", true,
						array("border" => '0', "alt" => __('Add'))) . "</a>"
					. '</div>'
					. "<div id='spinner_add' style='display: none;'>"
					. html_print_image("images/spinner.gif", true)
					. "</div>"
				. "</td>
			</tr>
		</tbody></table>";

echo "<div style='display: none;'>";
html_print_table ($table);
echo "</div>";

echo "<div style='display: none;'>";
echo "<div id='spinner_template'>";
html_print_image("images/spinner.gif");
echo "</div>";
echo "</div>";

echo "<div id='some_modules_text' style='display: none;'>";
echo __('Some modules');
echo "</div>";

echo "<div id='some_modules_text' style='display: none;'>";
echo __('Some modules');
echo "</div>";

echo "<div id='all_modules_text' style='display: none;'>";
echo __("All modules");
echo "</div>";

ui_include_time_picker();
ui_require_jquery_file("ui.datepicker-" . get_user_language(), "include/javascript/i18n/");

?>
<script language="javascript" type="text/javascript">
	var id_downtime = <?php echo $id_downtime?>;
	var action_in_progress = false;
	
	function change_type_downtime() {
		switch ($("#type_downtime").val()) {
			case 'disable_agents_alerts':
			case 'disable_agents':
				$("#available_modules").hide();
				break;
			case 'quiet':
				$("#available_modules").show();
				break;
		}
	}
	
	function change_type_execution() {
		switch ($("#type_execution").val()) {
			case 'once':
				$("#periodically_time").hide();
				$("#once_time").show();
				break;
			case 'periodically':
				$("#once_time").hide();
				$("#periodically_time").show();
				break;
		}
	}
	
	function change_type_periodicity() {
		switch ($("#type_periodicity").val()) {
			case 'weekly':
				$("#monthly_item").hide();
				$("#weekly_item").show();
				break;
			case 'monthly':
				$("#weekly_item").hide();
				$("#monthly_item").show();
				break;
		}
	}
	
	function show_executing_alert () {
		alert('<?php echo __("This elements cannot be modified while the downtime is being executed"); ?>');
	}
	
	function show_editor_module(id_agent) {
		//Avoid freak states.
		if (action_in_progress)
			return;
		
		//Check if the row editor module exists 
		if ($('#loading_' + id_agent).length > 0) {
			//The row exists
			$('#loading_' + id_agent).remove();
		}
		else {
			if ($('#module_editor_' + id_agent).length == 0) {
				$("#list-agent_" + id_agent).after(
					$("#loading-loading").clone().attr('id', 'loading_' + id_agent));
				
				jQuery.post ('ajax.php', 
					{"page": "include/ajax/planned_downtime.ajax",
					"get_modules_downtime": 1,
					"id_agent": id_agent,
					"id_downtime": id_downtime
					},
					function (data) {
						if (data['correct']) {
							//Check if the row editor module exists 
							if ($('#loading_' + id_agent).length > 0) {
								//The row exists
								$('#loading_' + id_agent).remove();
								
								$("#list-agent_" + id_agent).after(
									$("#editor-module").clone()
										.attr('id', 'module_editor_' + id_agent)
										.hide());
								
								fill_row_editor(id_agent, data);
							}
						}
					},
					"json"
				);
			}
			else {
				if ($('#module_editor_' + id_agent).is(':visible')) {
					$('#module_editor_' + id_agent).hide();
				}
				else {
					$('#module_editor_' + id_agent).css('display', '');
				}
			}
		}
	}
	
	function fill_row_editor(id_agent, data) {
		//$("#modules", $('#module_editor_' + id_agent)).empty();
		
		//Fill the select for to add modules
		$.each(data['in_agent'], function(id_module, name) {
			$("#modules", $('#module_editor_' + id_agent))
				.append($("<option value='" + id_module + "'>" + name + "</option>"));
		});
		$(".add_button", $('#module_editor_' + id_agent)).
			attr('href', 'javascript:' +
				'add_module_in_downtime(' + id_agent + ')');
		
		
		//Fill the list of modules
		$.each(data['in_downtime'], function(id_module, name) {
			var template_row = $("#template").clone();
			
			$(template_row).css('display', '');
			$(template_row).attr('id', 'row_module_in_downtime_' + id_module);
			$(".name_module", template_row).html(name);
			$(".link_delete", template_row).attr('href',
				'javascript:' +
				'delete_module_from_downtime(' + id_downtime + ',' + id_module + ');');
			
			$("#add_modules_row", $('#module_editor_' + id_agent))
				.before(template_row);
		});
		
		//.show() is crap, because put a 'display: block;'.
		$('#module_editor_' + id_agent).css('display', '');
	}
	
	function add_row_module(id_downtime, id_agent, id_module, name) {
		var template_row = $("#template").clone();
		
		$(template_row).css('display', '');
		$(template_row).attr('id', 'row_module_in_downtime_' + id_module);
		$(".name_module", template_row).html(name);
		$(".link_delete", template_row).attr('href',
			'javascript:' +
			'delete_module_from_downtime(' + id_downtime + ',' + id_module + ');');
		
		$("#add_modules_row", $('#module_editor_' + id_agent))
			.before(template_row);
		
	}
	
	function fill_selectbox_modules(id_downtime, id_agent) {
		jQuery.post ('ajax.php', 
			{"page": "include/ajax/planned_downtime.ajax",
				"get_modules_downtime": 1,
				"id_agent": id_agent,
				"id_downtime": id_downtime,
				"none_value": 1
			},
			function (data) {
				if (data['correct']) {
					$("#modules", $('#module_editor_' + id_agent)).empty();
					
					//Fill the select for to add modules
					$.each(data['in_agent'], function(id_module, name) {
						$("#modules", $('#module_editor_' + id_agent))
							.append($("<option value='" + id_module + "'>" + name + "</option>"));
					});
					
					$("#modules", $('#module_editor_' + id_agent)).val(0);
				}
			},
			"json"
		);
	}
	
	function add_module_in_downtime(id_agent) {
		var module_sel = $("#modules", $('#module_editor_' + id_agent)).val();
		
		if (module_sel == 0) {
			alert('<?php echo __("Please select a module."); ?>');
		}
		else {
			action_in_progress = true;
			
			$("#add_button_div", $('#module_editor_' + id_agent)).toggle();
			$("#spinner_add", $('#module_editor_' + id_agent)).toggle();
			
			jQuery.post ('ajax.php', 
				{"page": "include/ajax/planned_downtime.ajax",
					"add_module_into_downtime": 1,
					"id_agent": id_agent,
					"id_module": module_sel,
					"id_downtime": id_downtime
				},
				function (data) {
					if (data['correct']) {
						$("#list-agent_"
							+ id_agent
							+ '-count_modules').html(
								$("#some_modules_text").html());
						
						add_row_module(id_downtime, id_agent,
							module_sel, data['name']);
						fill_selectbox_modules(id_downtime, id_agent);
						
						
						$("#add_button_div", $('#module_editor_' + id_agent))
							.toggle();
						$("#spinner_add", $('#module_editor_' + id_agent))
							.toggle();
					}
					else if (data['executed']) {
						show_executing_alert();
					}
					
					action_in_progress = false;
				},
				"json"
			);
		}
	}
	
	function delete_module_from_downtime(id_downtime, id_module) {
		var spinner = $("#spinner_template").clone();
		var old_cell_content =
			$(".cell_delete_button", "#row_module_in_downtime_" + id_module)
			.clone(true);
		
		$(".cell_delete_button", "#row_module_in_downtime_" + id_module)
			.html(spinner);
		
		action_in_progress = true;
		
		jQuery.post ('ajax.php', 
			{"page": "include/ajax/planned_downtime.ajax",
			"delete_module_from_downtime": 1,
			"id_downtime": id_downtime,
			"id_module": id_module
			},
			function (data) {
				if (data['correct']) {
					fill_selectbox_modules(id_downtime, data['id_agent']);
					
					$("#row_module_in_downtime_" + id_module).remove();
					
					if (data['all_modules']) {
						$("#list-agent_"
							+ data['id_agent']
							+ '-count_modules').html(
								$("#all_modules_text").html());
					}
				}
				else if (data['executed']) {
					show_executing_alert();
				}
				else {
					$(".cell_delete_button", "#row_module_in_downtime_" + id_module)
						.html($(old_cell_content));
				}
				
				action_in_progress = false;
			},
			"json"
		);
	}
	
	$(document).ready (function () {
		$("#id_agents").change(agent_changed_by_multiple_agents);
		
		change_type_downtime();
		change_type_execution();
		change_type_periodicity();
		
		$("#text-periodically_time_from, #text-periodically_time_to, #text-once_time_from, #text-once_time_to").timepicker({
			showSecond: true,
			timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
			timeOnlyTitle: '<?php echo __('Choose time');?>',
			timeText: '<?php echo __('Time');?>',
			hourText: '<?php echo __('Hour');?>',
			minuteText: '<?php echo __('Minute');?>',
			secondText: '<?php echo __('Second');?>',
			currentText: '<?php echo __('Now');?>',
			closeText: '<?php echo __('Close');?>'});
		$("#text-once_date_from, #text-once_date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
		
		$.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
		
		
		$("#filter_group").click (function () {
			$(this).css ("width", "auto");
		});
		
		$("#filter_group").blur (function () {
			$(this).css ("width", "180px");
		});
		
		$("#id_agent").click (function () {
			$(this).css ("width", "auto");
		});
		
		$("#id_agent").blur (function () {
			$(this).css ("width", "180px");
		});

		// Warning message about the problems caused updating a past planned downtime
		var type_execution = "<?php echo $type_execution; ?>";
		var datetime_from = <?php echo json_encode(strtotime($once_date_from . ' ' . $once_time_from)); ?>;
		var datetime_now = <?php echo json_encode(strtotime(date(DATE_FORMAT). ' ' . date(TIME_FORMAT))); ?>;
		var create = <?php echo json_encode($create); ?>;
		if (!create && (type_execution == 'periodically' || (type_execution == 'once' && datetime_from < datetime_now))) {
			$("input#submit-updbutton, input#submit-add_item, table#list a").click(function (e) {
				if (!confirm("<?php echo __('WARNING: If you edit this planned downtime, the data of future SLA reports may be altered'); ?>")) {
					e.preventDefault();
				}
			});
		}
		// Disable datepickers when it has readonly attribute
		$('input.hasDatepicker[readonly]').disable();
	});
</script>
