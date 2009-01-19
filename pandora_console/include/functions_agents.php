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
 *
 * @return array All simple alerts defined for an agent. Empty array if no
 * alerts found.
 */
function get_agent_alerts_simple ($id_agent, $filter = false) {
	switch ($filter) {
	case "notfired":
		$filter = ' AND times_fired = 0 AND disable = 0';
		break;
	case "fired":
		$filter = ' AND times_fired > 0 AND disable = 0';
		break;
	case "disabled":
		$filter = ' AND disable = 1';
		break;
	default:
		$filter = '';
	}
	
	$id_modules = array_keys (get_agent_modules ($id_agent));
	if (empty ($id_modules))
		return array ();
	
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
 *
 * @return array An array with all combined alerts defined for an agent.
 */
function get_agent_alerts_combined ($id_agent, $filter = false) {
	/* TODO: Combined alerts */
	return array ();
	switch ($filter) {
		case "notfired":
			$filter = ' AND times_fired = 0 AND disable = 0';
			break;
		case "fired":
			$filter = ' AND times_fired > 0 AND disable = 0';
			break;
		case "disabled":
			$filter = ' AND disable = 1';
			break;
		default:
			$filter = '';
	}
	
	$id_modules = array_keys (get_agent_modules ($id_agent));
	if (empty ($id_modules))
		return array ();
	
	$sql = sprintf ("SELECT * FROM talert_template_modules
		WHERE id_agent_module IN (%s)%s",
		implode (',', $id_modules), $filter);
	$alerts = get_db_all_rows_sql ($sql);
	
	if ($alerts === false)
		return array ();
	return $alerts;
}

/**
 * Get all the alerts of an agent, simple and combined.
 *
 * @param int $id_agent Agent id
 *
 * @return array An array with all alerts defined for an agent.
 */
function get_agent_alerts ($id_agent, $filter = false) {
	$simple_alerts = get_agent_alerts_simple ($id_agent, $filter);
	$combined_alerts = get_agent_alerts_combined ($id_agent, $filter);
	
	return array_merge ($simple_alerts, $combined_alerts);
}

?>
