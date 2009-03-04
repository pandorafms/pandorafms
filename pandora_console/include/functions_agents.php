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
 * @param int $source_id_agent Agent id
 * @param mixed $destiny_id_agents Agent id or id's (array) to copy to
 * @param bool $modules Whether to copy modules as well (defaults to get_parameter ('copy_modules'))
 * @param bool $alerts Whether to copy alerts as well
 * @param array $target_modules Which modules to copy
 *
 * @return bool True in case of good, false in case of bad
 */
function process_manage_config ($source_id_agent, $destiny_id_agents, $copy_modules = false, $copy_alerts = false, $target_modules = false) {
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
	
	if ($copy_modules) {
		if (empty ($target_modules)) {
			$target_modules = (array) get_parameter ('target_modules', $target_modules);
		}
		
		if (empty ($target_modules)) {
			echo '<h3 class="error">'.__('No modules have been selected').'</h3>';
			return false;
		}
		
		process_sql ('SET AUTOCOMMIT = 0');
		process_sql ('START TRANSACTION');
		$error = false;
		$alerts = array ();
		foreach ($destiny_id_agents as $id_destiny_agent) {
			foreach ($target_modules as $id_agent_module) {
				$result = copy_agent_module_to_agent ($id_agent_module,
					$id_destiny_agent);
				
				if ($result === false) {
					$error = true;
					break;
				}
				
				$id_destiny_module = $result;
				if (! isset ($alerts[$id_agent_module]))
					$alerts[$id_agent_module] = get_alerts_agent_module ($id_agent_module,
						true);
				
				if ($alerts[$id_agent_module] === false)
					continue;
				
				if (! $copy_alerts)
					continue;
				
				foreach ($alerts[$id_agent_module] as $alert) {
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
}
	
?>
