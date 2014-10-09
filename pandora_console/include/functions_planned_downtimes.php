<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Planned downtimes
 */

// Load global vars
global $config;

/**
 * Include the usual functions
 */
// require_once($config["homedir"] . "/include/functions.php");
// enterprise_include_once('include/functions_inventory.php');

function planned_downtimes_check_dates ($type_execution = 'once', $type_periodicity = '', $datetime_from = false, $datetime_to = false, $periodically_time_from = false, $periodically_time_to = false, $periodically_day_from = false, $periodically_day_to = false) {
	global $config;

	$now = time();

	$result = array(
			'result' => false,
			'message' => ''
		);

	if ($type_execution == 'once' && !$config["past_planned_downtimes"] && $datetime_from < $now) {
		$result['message'] = ui_print_error_message(__('Not created. Error inserting data. Start time must be higher than the current time' ), '', true);
	}
	else if ($type_execution == 'once' && $datetime_from >= $datetime_to) {
		$result['message'] = ui_print_error_message(__('Not created. Error inserting data') . ". " .__('The end date must be higher than the start date'), '', true);
	}
	else if ($type_execution == 'periodically'
			&& (($type_periodicity == 'weekly' && $periodically_time_from >= $periodically_time_to)
				|| ($type_periodicity == 'monthly' && $periodically_day_from == $periodically_day_to && $periodically_time_from >= $periodically_time_to))) {
		$result['message'] = ui_print_error_message(__('Not created. Error inserting data') . ". " .__('The end time must be higher than the start time'), '', true);
	}
	else if ($type_execution == 'periodically' && $type_periodicity == 'monthly' && $periodically_day_from > $periodically_day_to) {
		$result['message'] = ui_print_error_message(__('Not created. Error inserting data') . ". " .__('The end day must be higher than the start day'), '', true);
	}
	else {
		$result['result'] = true;
	}

	return $result;
}

/** 
 * Update or create a planned downtime.
 *
 * @param array Values of the planned downtime.
 * @param int Id of the planned downtime. Empty to create a new downtime.
 * 
 * @return array Id of the updated/created planned downtime, result of the operation and result message.
 */
function planned_downtimes_update ($values, $downtime_id = 0, $check_dates = true) {
	$result = array(
			'id' => $downtime_id,
			'result' => false,
			'message' => ''
		);

	if ($check_dates) {
		$dates_check = planned_downtimes_check_dates($values['type_execution'], $values['type_periodicity'],
			$values['date_from'], $values['date_to'], $values['periodically_time_from'], $values['periodically_time_to'],
			$values['periodically_day_from'], $values['periodically_day_to']);

		if (!$dates_check['result']) {
			$result['message'] = $dates_check['message'];

			return $result;
		}
	}

	$name_trimmed = trim(io_safe_output($values['name']));
	if (!empty($name_trimmed)) {
		$name_exists = (bool) db_get_value('id', 'tplanned_downtime', 'name', $values['name']);

		if ($name_exists) {
			$result['message'] = ui_print_error_message(__('Each planned downtime must have a different name'), '', true);

			return $result;
		}
	}
	else {
		$result['message'] = ui_print_error_message(__('Planned downtime must have a name'), '', true);

		return $result;
	}
	

	if (empty($downtime_id)) {
		$res = db_process_sql_insert('tplanned_downtime', $values);

		if ($res === false) {
			$result['message'] = ui_print_error_message(__('Could not be created'), '', true);
		}
		else {
			$result['message'] = ui_print_success_message(__('Successfully created'), '', true);
			$result['result'] = true;
			$result['id'] = $res;
		}
	}
	else {
		$res = db_process_sql_update('tplanned_downtime', $values, array('id' => $downtime_id));

		if (empty($res)) {
			$result['message'] = ui_print_error_message(__('Could not be updated'), '', true);
		}
		else {
			$result['message'] = ui_print_success_message(__('Successfully updated'), '', true);
			$result['result'] = true;
		}
	}
	

	return $result;
}

/** 
 * Add new agents and modules to the planned downtime.
 *
 * @param int Id of the planned downtime.
 * @param array IDs of the agents to add.
 * @param bool Add all modules of the agents or not.
 * @param array Names of the modules to add. Empty to add all the modules.
 * 
 * @return array The status will be false id any insertion fails.
 * The failed insertions will be added to bad_agents and bad_modules.
 */
function planned_downtimes_add_items ($downtime_id, $agents, $all_modules = true, $module_names = array()) {
	global $config;

	include_once($config['homedir'] . "/include/functions_modules.php");

	$result = array(
			'status' => true,
			'bad_agents' => array(),
			'bad_modules' => array()
		);

	if (empty($module_names)) {
		$all_modules = true;
	}
	else {
		//It is empty.
		if ($module_names[0] == "0")
			$all_modules = true;
	}

	if (empty($agents)) {
		$agents = array();
		$result['status'] = false;
	}
	
	foreach ($agents as $agent_id) {
		$values = array(
				'id_downtime' => $downtime_id,
				'id_agent' => $agent_id,
				'all_modules' => $all_modules,
				'id_user' => $config['id_user']
			);
		$result = db_process_sql_insert('tplanned_downtime_agents', $values);

		if (empty($result)) {
			$result['bad_agents'][] = $agent_id;
		}
		else if (!$all_modules) {
			foreach ($module_names as $module_name) {
				$module = modules_get_agentmodule_id($module_name, $agent_id);
				$module_id = $module["id_agente_modulo"];

				$values = array(
						'id_downtime' => $downtime_id,
						'id_agent' => $agent_id,
						'id_agent_module' => $module_id
					);
				$result = db_process_sql_insert('tplanned_downtime_modules', $values);

				if (empty($result)) {
					$result['bad_modules'][] = $module_id;
				}
			}
		}
	}

	return $result;
}

/** 
 * Delete the agents and modules asociated to a planned downtime.
 *
 * @param int Id of the planned downtime.
 * @param int ID of the agent to delete.
 * 
 * @return bool The result of the operation.
 */
function planned_downtimes_delete_items ($downtime_id, $agent_id) {
	$filter = array(
				'id_downtime' => $downtime_id,
				'id_agent' => $agent_id
			);
	$downtime_agent_row = db_get_row('tplanned_downtime_agents', $filter);
	
	$result = db_process_sql_delete('tplanned_downtime_agents', array('id' => $downtime_agent_row['id']));
	
	if (!empty($result) && $downtime_agent_row['all_modules'] == 0) {
		//Delete modules in downtime
		$filter = array(
				'id_downtime' => $downtime_id,
				'id_agent' => $agent_id
			);
		db_process_sql_delete('tplanned_downtime_modules', $filter);
	}

	return $result;
}

/** 
 * Get the planned downtimes that don't comply the actual rules of dates
 * 
 * @return array List of planned downtimes
 */
function planned_downtimes_get_malformed () {

	$sql = "SELECT *
			FROM tplanned_downtime
			WHERE type_execution = 'periodically'
				AND ((type_periodicity = 'monthly'
						AND (periodically_day_from > periodically_day_to
							OR (periodically_day_from = periodically_day_to
								AND periodically_time_from >= periodically_time_to)))
					OR (type_periodicity = 'weekly'
						AND periodically_time_from >= periodically_time_to))";
	$malformed_downtimes = db_get_all_rows_sql($sql);

	if ($malformed_downtimes === false) {
		return false;
	}

	$malformed_downtimes_aux = array();
	foreach ($malformed_downtimes as $malformed_downtime) {
		$malformed_downtimes_aux[$malformed_downtime['id']] = $malformed_downtime;
	}
	$malformed_downtimes = $malformed_downtimes_aux;

	return $malformed_downtimes;
}

/** 
 * Create new downtimes with the correct format and delete the malformed
 *
 * @param array List with the malformed downtimes
 * 
 * @return bool The result of the migration
 */
function planned_downtimes_migrate_malformed_downtimes ($malformed_downtimes = array()) {
	global $config;

	$migration_result = array(
			'status' => true,
			'bad_downtimes' => array()
		);

	if (empty($malformed_downtimes))
		$malformed_downtimes = planned_downtimes_get_malformed();

	foreach ($malformed_downtimes as $key => $downtime) {
		$downtime_type = $downtime['type_downtime'];
		$downtime_execution = $downtime['type_execution'];
		$downtime_periodicity = $downtime['type_periodicity'];

		$downtime_time_from = $downtime['periodically_time_from'];
		$downtime_time_to = $downtime['periodically_time_to'];

		if ($downtime_execution == 'periodically') {
			if ($downtime_periodicity == 'monthly') {
				$downtime_day_from = $downtime['periodically_day_from'];
				$downtime_day_to = $downtime['periodically_day_to'];

				if (($downtime_day_from > $downtime_day_to) || ($downtime_day_from == $downtime_day_to && $downtime_time_from > $downtime_time_to)) {
					$values_first = array(
							'name' => $downtime['name'] . "&nbsp;[1/2]",
							'description' => $downtime['description'],
							'executed' => $downtime['executed'],
							'id_group' => $downtime['id_group'],
							'only_alerts' => $downtime['only_alerts'],
							'periodically_time_from' => $downtime_time_from,
							'periodically_time_to' => "23:59:59",
							'periodically_day_from' => $downtime_day_from,
							'periodically_day_to' => 31,
							'type_downtime' => $downtime_type,
							'type_execution' => $downtime_execution,
							'type_periodicity' => $downtime_periodicity,
							'id_user' => $config['id_user']
						);
					$values_second = $values_first;
					$values_second['name'] = $downtime['name'] . "&nbsp;[2/2]";
					$values_second['periodically_day_from'] = 1;
					$values_second['periodically_time_from'] = "00:00:00";
					$values_second['periodically_day_to'] = $downtime_day_to;
					$values_second['periodically_time_to'] = $downtime_time_to;
					
					$result_first = planned_downtimes_update($values_first, 0, false);

					if ($result_first['result']) {
						$result_second = planned_downtimes_update($values_second, 0, false);

						if (!$result_second['result']) {
							db_process_sql_delete('tplanned_downtime', array('id' => $result_first['id']));
						}
					}

					if ($result_first['result'] && $result_second['result']) {
						$result_copy = planned_downtimes_migrate_malformed_downtimes_copy_items($downtime['id'], $result_first['id'], $result_second['id']);
						
						if (!$result_copy) {
							$migration_result['status'] = false;
							$migration_result['bad_downtimes'][] = $downtime['id'];
						}
					}
					else {
						$migration_result['status'] = false;
						$migration_result['bad_downtimes'][] = $downtime['id'];
					}
				}
				else if ($downtime_day_from == $downtime_day_to && $downtime_time_from == $downtime_time_to) {

					$utimestamp = strtotime("1-1-1970 $downtime_time_to");
					$time = date('H:i:s', $utimestamp + 1);

					if ($time != '00:00:00') {
						$values = array('periodically_time_to' => $time);
					}
					else {
						$utimestamp = strtotime("1-1-1970 $downtime_time_from");
						$time = date('H:i:s', $utimestamp - 1);
						$values = array('periodically_time_from' => $time);
					}

					$filter = array('id' => $downtime['id']);
					$result_update = db_process_sql_update('tplanned_downtime', $values, $filter);

					if (!$result_update) {
						$migration_result['status'] = false;
						$migration_result['bad_downtimes'][] = $downtime['id'];
					}
				}
			}
			else if ($downtime_periodicity == 'weekly') {
				if ($downtime_time_from > $downtime_time_to) {
					$days = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");

					$values_first = array(
							'name' => $downtime['name'] . "&nbsp;[1/2]",
							'description' => $downtime['description'],
							'executed' => $downtime['executed'],
							'id_group' => $downtime['id_group'],
							'only_alerts' => $downtime['only_alerts'],
							'monday' => $downtime['monday'],
							'tuesday' => $downtime['tuesday'],
							'wednesday' => $downtime['wednesday'],
							'thursday' => $downtime['thursday'],
							'friday' => $downtime['friday'],
							'saturday' => $downtime['saturday'],
							'sunday' => $downtime['sunday'],
							'periodically_time_from' => $downtime_time_from,
							'periodically_time_to' => "23:59:59",
							'type_downtime' => $downtime_type,
							'type_execution' => $downtime_execution,
							'type_periodicity' => $downtime_periodicity,
							'id_user' => $config['id_user']
						);

					$values_second = $values_first;
					$values_second['name'] = $downtime['name'] . "&nbsp;[2/2]";
					$values_second['periodically_time_from'] = "00:00:00";
					$values_second['periodically_time_to'] = $downtime_time_to;
					$values_second['tuesday'] = $downtime['monday'] ? 1 : 0;
					$values_second['wednesday'] = $downtime['tuesday'] ? 1 : 0;
					$values_second['thursday'] = $downtime['wednesday'] ? 1 : 0;
					$values_second['friday'] = $downtime['thursday'] ? 1 : 0;
					$values_second['saturday'] = $downtime['friday'] ? 1 : 0;
					$values_second['sunday'] = $downtime['saturday'] ? 1 : 0;
					$values_second['monday'] = $downtime['sunday'] ? 1 : 0;

					$result_first = planned_downtimes_update($values_first, 0, false);

					if ($result_first['result']) {
						$result_second = planned_downtimes_update($values_second, 0, false);

						if (!$result_second['result']) {
							db_process_sql_delete('tplanned_downtime', array('id' => $result_first['id']));
						}
					}

					if ($result_first['result'] && $result_second['result']) {
						$result_copy = planned_downtimes_migrate_malformed_downtimes_copy_items($downtime['id'], $result_first['id'], $result_second['id']);

						if (!$result_copy) {
							$migration_result['status'] = false;
							$migration_result['bad_downtimes'][] = $downtime['id'];
						}
					}
					else {
						$migration_result['status'] = false;
						$migration_result['bad_downtimes'][] = $downtime['id'];
					}
				}
				else if ($downtime_time_from == $downtime_time_to) {
					$utimestamp = strtotime("1-1-1970 $downtime_time_to");
					$time = date('H:i:s', $utimestamp + 1);

					if ($time != '00:00:00') {
						$values = array('periodically_time_to' => $time);
					}
					else {
						$utimestamp = strtotime("1-1-1970 $downtime_time_from");
						$time = date('H:i:s', $utimestamp - 1);
						$values = array('periodically_time_from' => $time);
					}

					$filter = array('id' => $downtime['id']);
					$result_update = db_process_sql_update('tplanned_downtime', $values, $filter);

					if (!$result_update) {
						$migration_result['status'] = false;
						$migration_result['bad_downtimes'][] = $downtime['id'];
					}
				}
			}
		}
	}

	return $migration_result;
}

/** 
 * Aux function to copy the items of the selected downtime to the new downtimes.
 * Deletes the new downtimes if the items are not copied.
 */
function planned_downtimes_migrate_malformed_downtimes_copy_items ($original_downtime_id, $new_downtime_first_id, $new_downtime_second_id) {
	$sql = "SELECT *
			FROM tplanned_downtime_agents
			WHERE id_downtime = " . $original_downtime_id;
	$downtime_agents_rows = db_get_all_rows_sql($sql);
	$sql = "SELECT *
			FROM tplanned_downtime_modules
			WHERE id_downtime = " . $original_downtime_id;
	$downtime_modules_rows = db_get_all_rows_sql($sql);

	if (!empty($downtime_agents_rows)) {
		$result_agents = true;

		foreach ($downtime_agents_rows as $downtime_agents_row) {
			$values_agent = array(
					'id_agent' => $downtime_agents_row['id_agent'],
					'all_modules' => $downtime_agents_row['all_modules']
				);
			$values_agent['id_downtime'] = $new_downtime_first_id;
			$result_agent_first = db_process_sql_insert('tplanned_downtime_agents', $values_agent);
			$values_agent['id_downtime'] = $new_downtime_second_id;
			$result_agent_second = db_process_sql_insert('tplanned_downtime_agents', $values_agent);

			if (empty($result_agent_first) || empty($result_agent_second)) {
				$result_agents = false;
				db_process_sql_delete('tplanned_downtime', array('id' => $new_downtime_first_id));
				db_process_sql_delete('tplanned_downtime', array('id' => $new_downtime_second_id));
				break;
			}
		}

		if ($result_agents) {
			if (!empty($downtime_modules_rows)) {
				foreach ($downtime_modules_rows as $downtime_modules_row) {
					$values_module = array(
							'id_agent' => $downtime_modules_row['id_agent'],
							'id_agent_module' => $downtime_modules_row['id_agent_module']
						);
					$values_module['id_downtime'] = $new_downtime_first_id;
					$result_module_first = db_process_sql_insert('tplanned_downtime_modules', $values_module);
					$values_module['id_downtime'] = $new_downtime_second_id;
					$result_module_second = db_process_sql_insert('tplanned_downtime_modules', $values_module);

					if (empty($result_module_first) || empty($result_module_second)) {
						db_process_sql_delete('tplanned_downtime', array('id' => $new_downtime_first_id));
						db_process_sql_delete('tplanned_downtime', array('id' => $new_downtime_second_id));
						break;
					}
				}
			}
		}
	}

	// The new downtimes are created
	$new_planned_downtimes_exists = (bool) db_get_value('id', 'tplanned_downtime', 'id', $new_downtime_first_id)
		&& (bool) db_get_value('id', 'tplanned_downtime', 'id', $new_downtime_second_id);

	if ($new_planned_downtimes_exists) {
		// Delete the migrated downtime and its items
		db_process_sql_delete('tplanned_downtime', array('id' => $original_downtime_id));
	}

	return $new_planned_downtimes_exists;
}

?>