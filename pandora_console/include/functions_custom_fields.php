<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * Returns custom field all or 1.
 *
 * @param integer custom_field_id id.
 * @param bool Prepare for select or return all rows.
 *
 * @return array custom fields data.
 */
function get_custom_fields ($custom_field_id = false, $select = true, $display_on_front = false) {
	$fields = ($select)
		? ' tcf.id_field, tcf.name '
		: ' tcf.* ';

	$where = ($custom_field_id)
		? ' WHERE tcf.id_field ='.$custom_field_id
		: ' WHERE 1=1';

	$display = ($display_on_front)
		? ' AND tcf.display_on_front = 1'
		: '';

	$result_array=array();
	if(!is_metaconsole()){
		$sql = sprintf("SELECT
			%s
			FROM tagent_custom_fields tcf
			%s
			%s
			",
			$fields,
			$where,
			$display
		);

		$result = db_get_all_rows_sql($sql);

		if(isset($result) && is_array($result)){
			foreach ($result as $key => $value) {
				if($select){
					$result_array[$value['name']]= $value['name'];
				}
				else{
					$result_array[$value['name']]= $value;
				}
			}
		}
	}
	else{
		$metaconsole_connections = metaconsole_get_connection_names();
		// For all nodes
		if(isset($metaconsole_connections) && is_array($metaconsole_connections)){
			$result_meta = array();
			foreach ($metaconsole_connections as $metaconsole) {
				// Get server connection data
				$server_data = metaconsole_get_connection($metaconsole);

				// Establishes connection
				if (metaconsole_load_external_db ($server_data) !== NOERR) continue;

				$sql = sprintf("SELECT
					%s
					FROM tagent_custom_fields tcf
					%s
					%s
					",
					$fields,
					$where,
					$display
				);

				$result[]= db_get_all_rows_sql($sql);

				// Restore connection to root node
				metaconsole_restore_db();

				if(isset($result) && is_array($result)){
					foreach ($result as $custom) {
						foreach ($custom as $key => $value) {
							if($select){
								$result_array[$value['name']]= $value['name'];
							}
							else{
								$result_array[$value['name']]= $value;
							}
						}
					}
				}
			}

		}
		else{
			$result_array = false;
		}
	}


	return $result_array;
}

/**
 * Returns custom field data.
 *
 * @param integer custom_field_id id.
 *
 * @return array custom fields data.
 */
function get_custom_fields_data ($custom_field_name) {
	if(!isset($custom_field_name)){
		return false;
	}

	if(!is_metaconsole()){
		$sql = sprintf("SELECT tcf.id_field, tcf.name, tcd.description
			FROM tagent_custom_fields tcf
			INNER JOIN tagent_custom_data tcd
				ON tcf.id_field = tcd.id_field
			INNER JOIN tagente ta
				ON tcd.id_agent = ta.id_agente
			WHERE tcd.description <> ''
				AND tcf.name = '%s'
			GROUP BY tcf.id_field, tcd.description",
			$custom_field_name
		);

		$result = db_get_all_rows_sql($sql);
		if(isset($result) && is_array($result)){
			foreach ($result as $k => $v) {
				$array_result[$v['description']] = $v['description'];
			}
		}
	}
	else{
		$metaconsole_connections = metaconsole_get_connection_names();
		// For all nodes
		if(isset($metaconsole_connections) && is_array($metaconsole_connections)){
			$result_meta = array();
			foreach ($metaconsole_connections as $metaconsole) {
				// Get server connection data
				$server_data = metaconsole_get_connection($metaconsole);

				// Establishes connection
				if (metaconsole_load_external_db ($server_data) !== NOERR) continue;

				$sql = sprintf("SELECT tcf.id_field, tcf.name, tcd.description
					FROM tagent_custom_fields tcf
					INNER JOIN tagent_custom_data tcd
						ON tcf.id_field = tcd.id_field
					INNER JOIN tagente ta
						ON tcd.id_agent = ta.id_agente
					WHERE tcd.description <> ''
						AND tcf.name = '%s'
					GROUP BY tcf.id_field, tcd.description", $custom_field_name
				);

				$result_meta[]= db_get_all_rows_sql($sql);

				// Restore connection to root node
				metaconsole_restore_db();
			}

			$array_result = array();
			if(isset($result_meta) && is_array($result_meta)){
				foreach ($result_meta as $result) {
					foreach ($result as $k => $v) {
						$array_result[$v['description']] = $v['description'];
					}
				}
			}
		}
		else{
			$array_result = false;
		}
	}
	return $array_result;
}

function agent_counters_custom_fields($filters){
	//filter by status
	$and_status = "";
	if(is_array($filters['id_status'])){
		if(!in_array(-1, $filters['id_status'])){
			if(!in_array(AGENT_MODULE_STATUS_NOT_NORMAL, $filters['id_status'])){
				if(count($filters['id_status']) > 0){
					$and_status = " AND ( ";
					foreach ($filters['id_status'] as $key => $value) {
						$and_status .= ($key != 0)
							? " OR ("
							: " (";
						switch ($value) {
							default:
							case AGENT_STATUS_NORMAL:
								$and_status .= " ta.critical_count = 0
									AND ta.warning_count = 0
									AND ta.unknown_count = 0
									AND ta.total_count <> ta.notinit_count ) ";
								break;
							case AGENT_STATUS_CRITICAL:
								$and_status .= " ta.critical_count > 0 ) ";
								break;
							case AGENT_STATUS_WARNING:
								$and_status .= " ta.critical_count = 0
									AND ta.warning_count > 0 ) ";
								break;
							case AGENT_STATUS_UNKNOWN:
								$and_status .= " ta.critical_count = 0
									AND ta.warning_count = 0
									AND ta.unknown_count > 0 ) ";
								break;
							case AGENT_STATUS_NOT_INIT:
								$and_status .= " ta.total_count = ta.notinit_count ) ";
								break;
						}
					}
					$and_status .= " ) ";
				}
			}
			else{
				$and_status = " AND (
					( ta.critical_count > 0 )
					OR ( ta.critical_count = 0 AND ta.warning_count > 0 )
					OR ( ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count > 0 )
					OR ( ta.total_count = ta.notinit_count )
				) ";
			}
		}
	}

	//filter group and check ACL groups
	$groups_and = "";
	if (!users_can_manage_group_all("AR")) {
		if(!$filters['group']){
			$id_groups = explode(", ", array_keys(users_get_groups()));
			$groups_and = " AND (ta.id_grupo IN ($id_groups) OR tasg.id_group IN($id_groups))";
		}
	}

	if($filters['group']){
		$groups_and = " AND (ta.id_grupo =". $filters['group']." OR tasg.id_group =". $filters['group'].")";
	}

	//filter custom data
	$custom_data_and = '';
	if(!in_array(-1, $filters['id_custom_fields_data'])){
		$custom_data_array = implode("', '", $filters['id_custom_fields_data']);
		$custom_data_and = "AND tcd.description IN ('" . $custom_data_array . "')";
	}

	//filter custom name
	$custom_field_name = $filters['id_custom_fields'];

	//filters module
	$module_filter = "";
	if($filters['module_search']){
		$module_filter = ' AND (
			SELECT count(*) AS n
			FROM tagente_modulo
			WHERE nombre LIKE "%' . $filters['module_search'] . '%"
				AND id_agente=ta.id_agente
		) > 0 ';
	}

	if(is_metaconsole()){
		$metaconsole_connections = metaconsole_get_connection_names();
		// For all nodes
		if(isset($metaconsole_connections) && is_array($metaconsole_connections)){
			$result_meta = array();
			$data = array();
			foreach ($metaconsole_connections as $metaconsole) {
				// Get server connection data
				$server_data = metaconsole_get_connection($metaconsole);
				// Establishes connection
				if (metaconsole_load_external_db ($server_data) !== NOERR) continue;

				$query = sprintf("SELECT
						tcd.description as name_data,
						SUM(ta.normal_count) AS m_normal,
						SUM(ta.critical_count) AS m_critical,
						SUM(ta.warning_count) AS m_warning,
						SUM(ta.unknown_count) AS m_unknown,
						SUM(ta.notinit_count) AS m_not_init,
						SUM(ta.fired_count) AS m_alerts,
						SUM(ta.total_count) AS m_total,
						SUM(IF(ta.critical_count > 0, 1, 0)) AS a_critical,
						SUM(IF(ta.critical_count = 0 AND ta.warning_count > 0, 1, 0)) AS a_warning,
						SUM(IF(ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count > 0, 1, 0)) AS a_unknown,
						SUM(IF(ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count = 0 AND ta.notinit_count <> ta.total_count, 1, 0)) AS a_normal,
						SUM(IF(ta.total_count = ta.notinit_count, 1, 0)) AS a_not_init,
						COUNT(ta.id_agente) AS a_agents,
						GROUP_CONCAT(DISTINCT(ta.id_agente) SEPARATOR ',') as ids
					FROM tagente ta
					INNER JOIN tagent_custom_data tcd
						ON tcd.id_agent = ta.id_agente
					INNER JOIN tagent_custom_fields tcf
						ON tcd.id_field = tcf.id_field
					LEFT JOIN tagent_secondary_group tasg
						ON ta.id_agente = tasg.id_agent
					WHERE ta.disabled = 0
						AND tcf.name = '%s'
						AND tcd.description <> ''
						%s
						%s
						%s
						%s
					GROUP BY tcd.description",
					$custom_field_name,
					$custom_data_and,
					$groups_and,
					$and_status,
					$module_filter
				);

				$result_meta[$server_data['id']] = db_get_all_rows_sql($query);

				$query_data = sprintf("SELECT
						tcd.description,
						ta.id_agente,
						%d AS id_server
					FROM tagente ta
					LEFT JOIN tagent_secondary_group tasg
						ON ta.id_agente = tasg.id_agent
					INNER JOIN tagent_custom_data tcd
						ON tcd.id_agent = ta.id_agente
					INNER JOIN tagent_custom_fields tcf
						ON tcd.id_field = tcf.id_field
					WHERE ta.disabled = 0
						AND tcf.name = '%s'
						AND tcd.description <> ''
						%s
						%s
						%s
						%s
					",
					$server_data['id'],
					$custom_field_name,
					$custom_data_and,
					$groups_and,
					$and_status,
					$module_filter
				);

				$node_result = db_get_all_rows_sql($query_data);
				if (empty($node_result)) $node_result = array();

				$data = array_merge($data, $node_result);
				// Restore connection to root node
				metaconsole_restore_db();
			}
		}

		$final_result = array();
		$array_data = array();

		if(isset($result_meta) && is_array($result_meta)){
			//initialize counters
			$final_result['counters_total'] = array(
				't_m_normal' => 0,
				't_m_critical' => 0,
				't_m_warning' => 0,
				't_m_unknown' => 0,
				't_m_not_init' => 0,
				't_m_alerts' => 0,
				't_m_total' => 0,
				't_a_critical' => 0,
				't_a_warning' => 0,
				't_a_unknown' => 0,
				't_a_normal' => 0,
				't_a_not_init' => 0,
				't_a_agents' => 0
			);

			foreach ($result_meta as $k => $nodo) {
				foreach ($nodo as $key => $value) {
					//Sum counters total
					$final_result['counters_total']['t_m_normal'] += $value['m_normal'];
					$final_result['counters_total']['t_m_critical'] += $value['m_critical'];
					$final_result['counters_total']['t_m_warning'] += $value['m_warning'];
					$final_result['counters_total']['t_m_unknown'] += $value['m_unknown'];
					$final_result['counters_total']['t_m_not_init'] += $value['m_not_init'];
					$final_result['counters_total']['t_m_alerts'] += $value['m_alerts'];
					$final_result['counters_total']['t_m_total'] += $value['m_total'];
					$final_result['counters_total']['t_a_critical'] += $value['a_critical'];
					$final_result['counters_total']['t_a_warning'] += $value['a_warning'];
					$final_result['counters_total']['t_a_unknown'] += $value['a_unknown'];
					$final_result['counters_total']['t_a_normal'] += $value['a_normal'];
					$final_result['counters_total']['t_a_not_init'] += $value['a_not_init'];
					$final_result['counters_total']['t_a_agents'] += $value['a_agents'];

					//Sum counters for data
					$array_data[$value['name_data']]['m_normal'] += $value['m_normal'];
					$array_data[$value['name_data']]['m_critical'] += $value['m_critical'];
					$array_data[$value['name_data']]['m_warning'] += $value['m_warning'];
					$array_data[$value['name_data']]['m_unknown'] += $value['m_unknown'];
					$array_data[$value['name_data']]['m_not_init'] += $value['m_not_init'];
					$array_data[$value['name_data']]['m_alerts'] += $value['m_alerts'];
					$array_data[$value['name_data']]['m_total'] += $value['m_total'];
					$array_data[$value['name_data']]['a_critical'] += $value['a_critical'];
					$array_data[$value['name_data']]['a_warning'] += $value['a_warning'];
					$array_data[$value['name_data']]['a_unknown'] += $value['a_unknown'];
					$array_data[$value['name_data']]['a_normal'] += $value['a_normal'];
					$array_data[$value['name_data']]['a_not_init'] += $value['a_not_init'];
					$array_data[$value['name_data']]['a_agents'] += $value['a_agents'];
				}
			}

			$final_result['counters_name'] = $array_data;
		}

		$final_result['indexed_descriptions'] = $data;
	}
	else{
		//TODO
		$final_result = false;
	}

	return $final_result;
}

function get_filters_custom_fields_view($id = 0, $for_select = false, $name = ""){
	if($for_select){
		$query = "SELECT id, `name` FROM tagent_custom_fields_filter";
		$rs = db_get_all_rows_sql($query);
		if(isset($rs) && is_array($rs)){
			foreach ($rs as $key => $value) {
				$result[$value['id']] = $value['name'];
			}
		}
		else{
			$result = false;
		}
	}
	else{
		$query = "SELECT * FROM tagent_custom_fields_filter WHERE 1=1";

		if($id){
			$query .= " AND id = " . $id;
		}

		if($name){
			$query .= " AND `name` = '" . $name . "'";
		}

		$result = db_get_all_rows_sql($query);
	}
	return $result;
}
?>