<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

/**
 * Creates an agent
 *
 * @param string Agent name.
 * @param string Group to be included.
 * @param int Agent interval
 * @param string Agent IP
 *
 * @return int New agent id if created. False if it could not be created.
 */
function create_agent ($name, $id_group, $interval, $ip_address, $values = false) {
	if (empty ($name))
		return false;
	if (empty ($name))
		return false;
	if (empty ($ip_address))
		return false;
	$interval = safe_int ($interval, 300);
	if (empty ($interval))
		return false;
	if (! is_array ($values))
		$values = array ();
	$values['nombre'] = $name;
	$values['id_grupo'] = $id_group;
	$values['intervalo'] = $interval;
	$values['direccion'] = $ip_address;
	
	process_sql_begin ();
	
	$id_agent = process_sql_insert ('tagente', $values);
	if ($id_agent === false) {
		process_sql_rollback ();
		return false;
	}
	
	// Create address for this agent in taddress
	agent_add_address ($id_agent, $ip_address);
	
	// Create special module agent_keepalive
	$id_agent_module = process_sql_insert ('tagente_modulo', 
		array ('nombre' => 'agent_keepalive',
			'id_agente' => $id_agent,
			'id_tipo_modulo' => 100,
			'descripcion' => __('Agent keepalive monitor'),
			'id_modulo' => 1,
			'min_warning' => 0,
			'max_warning' => 1));
	
	if ($id_agent_module === false) {
		process_sql_rollback ();
		return false;
	}
	
	$result = process_sql_insert ('tagente_estado', 
			array ('id_agente_modulo' => $id_agent_module,
				'datos' => '',
				'timestamp' => 0,
				'estado' => 0,
				'id_agente' => $id_agent,
				'last_try' => 0,
				'utimestamp' => 0,
				'current_interval' => 0,
				'running_by' => 0,
				'last_execution_try' => 0));
	
	if ($result === false) {
		process_sql_rollback ();
		return false;
	}
	
	process_sql_commit ();
	
	return $id_agent;
}

/**
 * Get all the simple alerts of an agent.
 *
 * @param int Agent id
 * @param string Filter on "fired", "notfired" or "disabled". Any other value
 * will not do any filter.
 * @param array Extra filter options in an indexed array. See
 * format_array_to_where_clause_sql()
 *
 * @return array All simple alerts defined for an agent. Empty array if no
 * alerts found.
 */
function get_agent_alerts_simple ($id_agent, $filter = '', $options = false) {
	switch ($filter) {
	case "notfired":
		$filter = ' AND times_fired = 0 AND disabled = 0';
		break;
	case "fired":
		$filter = ' AND times_fired > 0 AND disabled = 0';
		break;
	case "disabled":
		$filter = ' AND disabled = 1';
		break;
	default:
		$filter = '';
	}
	
	$id_agent = (array) $id_agent;
	$id_modules = array_keys (get_agent_modules ($id_agent));
	if (empty ($id_modules))
		return array ();
	
	if (is_array ($options)) {
		$filter .= format_array_to_where_clause_sql ($options);
	}
	
	$sql = sprintf ("SELECT talert_template_modules.*
		FROM talert_template_modules
		WHERE id_agent_module in (%s)%s",
		implode (",", $id_modules), $filter);
	
	$alerts = get_db_all_rows_sql ($sql);
	
	if ($alerts === false)
		return array ();
	return $alerts;
}

/**
 * Get all the combined alerts of an agent.
 *
 * @param int $id_agent Agent id
 * @param string Special filter. Can be: "notfired", "fired" or "disabled".
 * @param array Extra filter options in an indexed array. See
 * format_array_to_where_clause_sql()
 *
 * @return array An array with all combined alerts defined for an agent.
 */
function get_agent_alerts_compound ($id_agent, $filter = '', $options = false) {
	switch ($filter) {
	case "notfired":
		$filter = ' AND times_fired = 0 AND disabled = 0';
		break;
	case "fired":
		$filter = ' AND times_fired > 0 AND disabled = 0';
		break;
	case "disabled":
		$filter = ' AND disabled = 1';
		break;
	default:
		$filter = '';
	}
	
	if (is_array ($options)) {
		$filter .= format_array_to_where_clause_sql ($options);
	}
	
	$id_agent = array ($id_agent);
	
	$sql = sprintf ("SELECT * FROM talert_compound
		WHERE id_agent in (%s)%s",
		implode (',', $id_agent), $filter);
	
	$alerts = get_db_all_rows_sql ($sql);
	
	if ($alerts === false)
		return array ();
	return $alerts;
}

/**
 * Get a list of agents.
 *
 * By default, it will return all the agents where the user has reading access.
 * 
 * @param array ilter options in an indexed array. See
 * format_array_to_where_clause_sql()
 * @param array Fields to get.
 * @param string Access needed in the agents groups.
 * 
 * @return array An array with all alerts defined for an agent.
 */
function get_agents ($filter = false, $fields = false, $access = 'AR') {
	if (! is_array ($filter))
		$filter = array ();
	
	if (! isset ($filter['id_grupo']))
		$filter['id_grupo'] = array_keys (get_user_groups (false, $access));
	else
		if (! is_array ($filter['id_grupo'])) {
			if (! in_array ($filter['id_grupo'], array_keys (get_user_groups (false, $access))))
				return false;
		} else {
			$user_groups = get_user_groups (false, $access);
			foreach ($filter['id_grupo'] as $i => $id_group)
				if (! isset ($user_groups[$id_group]))
					unset ($filter['id_grupo'][$id]);
			if (count ($filter['id_grupo']) == 0)
				$filter['id_grupo'] = $user_groups;
		}
	
	return @get_db_all_rows_filter ('tagente', $filter, $fields);
}

/**
 * Get all the alerts of an agent, simple and combined.
 *
 * @param int $id_agent Agent id
 * @param string Special filter. Can be: "notfired", "fired" or "disabled".
 * @param array Extra filter options in an indexed array. See
 * format_array_to_where_clause_sql()
 *
 * @return array An array with all alerts defined for an agent.
 */
function get_agent_alerts ($id_agent, $filter = false, $options = false) {
	$simple_alerts = get_agent_alerts_simple ($id_agent, $filter, $options);
	$combined_alerts = get_agent_alerts_compound ($id_agent, $filter, $options);
	
	return array ('simple' => $simple_alerts, 'compounds' => $combined_alerts);
}

/**
 * Copy the agents config from one agent to the other
 *
 * @param int Agent id
 * @param mixed Agent id or id's (array) to copy to
 * @param bool Whether to copy modules as well (defaults to $_REQUEST['copy_modules'])
 * @param bool Whether to copy alerts as well
 * @param array Which modules to copy.
 * @param array Which alerts to copy. Only will be used if target_modules is empty.
 *
 * @return bool True in case of good, false in case of bad
 */
function process_manage_config ($source_id_agent, $destiny_id_agents, $copy_modules = false, $copy_alerts = false, $target_modules = false, $target_alerts = false) {
	if (empty ($source_id_agent)) {
		echo '<h3 class="error">'.__('No source agent to copy').'</h3>';
		return false;
	}
	
	if (empty ($destiny_id_agents)) {
		echo '<h3 class="error">'.__('No destiny agent(s) to copy').'</h3>';
		return false;
	}
	
	if ($copy_modules == false) {
		$copy_modules = (bool) get_parameter ('copy_modules', $copy_modules);
	}
	
	if ($copy_alerts == false) {
		$copy_alerts = (bool) get_parameter ('copy_alerts', $copy_alerts);
	}
	
	if (! $copy_modules && ! $copy_alerts)
		return;
	
	if (empty ($target_modules)) {
		$target_modules = (array) get_parameter ('target_modules', array ());
	}
	
	if (empty ($target_alerts)) {
		$target_alerts = (array) get_parameter ('target_alerts', array ());
	}
	
	if (empty ($target_modules)) {
		if (! $copy_alerts) {
			echo '<h3 class="error">'.__('No modules have been selected').'</h3>';
			return false;
		}
		$target_modules = array ();
		
		foreach ($target_alerts as $id_alert) {
			$alert = get_alert_agent_module ($id_alert);
			if ($alert === false)
				continue;
			/* Check if some alerts which doesn't belong to the agent was given */
			if (get_agentmodule_agent ($alert['id_agent_module']) != $source_id_agent)
				continue;
			array_push ($target_modules, $alert['id_agent_module']);
		}
	}
	
	process_sql ('SET AUTOCOMMIT = 0');
	process_sql ('START TRANSACTION');
	$error = false;
	
	foreach ($destiny_id_agents as $id_destiny_agent) {
		foreach ($target_modules as $id_agent_module) {
			$result = copy_agent_module_to_agent ($id_agent_module,
				$id_destiny_agent);
		
			if ($result === false) {
				$error = true;
				break;
			}
			
			$id_destiny_module = $result;
			
			if (! $copy_alerts)
				continue;
			
			/* If the alerts were given, copy afterwards. Otherwise, all the
			alerts for the module will be copied */
			if (! empty ($target_alerts)) {
				foreach ($target_alerts as $id_alert) {
					$alert = get_alert_agent_module ($id_alert);
					if ($alert === false)
						continue;
					if ($alert['id_agent_module'] != $id_agent_module)
						continue;
					$result = copy_alert_agent_module_to_agent_module ($alert['id'],
						$id_destiny_module);
					if ($result === false) {
						$error = true;
						break;
					}
				}
				continue;
			}
			
			$alerts = get_alerts_agent_module ($id_agent_module, true);
			
			if ($alerts === false)
				continue;
			
			foreach ($alerts as $alert) {
				$result = copy_alert_agent_module_to_agent_module ($alert['id'],
					$id_destiny_module);
				if ($result === false) {
					$error = true;
					break;
				}
			}
		}
		if ($error)
			break;
	}
	
	if ($error) {
		echo '<h3 class="error">'.__('There was an error copying the agent configuration, the copy has been cancelled').'</h3>';
		process_sql ('ROLLBACK');
	} else {
		echo '<h3 class="suc">'.__('Successfully copied').'</h3>';
		process_sql ('COMMIT');
	}
	process_sql ('SET AUTOCOMMIT = 1');
}
?>
