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
 * Check if login session variables are set.
 *
 * It will stop the execution if those variables were not set
 * 
 * @return bool 0 on success exit() on no success
 */

function check_login () {
	global $config;
	if (! isset ($config["homedir"])) {
		// No exists $config. Exit inmediatly
		include("general/noaccess.php");
		exit;
	}
	if ((isset($_SESSION["id_usuario"])) AND ($_SESSION["id_usuario"] != "")) {
		$id = get_db_value("id_usuario","tusuario","id_usuario",$_SESSION["id_usuario"]);
		if ( $_SESSION["id_usuario"] == $id) {
			return 0;
		}
	}
	audit_db ("N/A", getenv ("REMOTE_ADDR"), "No session", "Trying to access without a valid session");
	include ($config["homedir"]."/general/noaccess.php");
	exit;
}
	
/**
 * Check access privileges to resources
 *
 * Access can be:
 * IR - Incident Read
 * IW - Incident Write
 * IM - Incident Management
 * AR - Agent Read
 * AW - Agent Write
 * LW - Alert Write
 * UM - User Management
 * DM - DB Management
 * LM - Alert Management
 * PM - Pandora Management
 *
 * @param int $id_user User id
 * @param int $id_group Agents group id
 * @param string $access Access privilege
 *
 * @return bool 1 if the user has privileges, 0 if not.
 */
function give_acl ($id_user, $id_group, $access) {
	// IF user is level = 1 then always return 1

	global $config;
	$nivel = get_db_value("nivel","tusuario","id_usuario",$id_user);
	if ($nivel == 1) {
		return 1;
		//Apparently nivel is 1 if user has full admin access
	} 
	
	//Joined multiple queries into one. That saves on the query overhead and query cache.
	if ($id_group == 0) {
		$query1=sprintf("SELECT tperfil.incident_view,tperfil.incident_edit,tperfil.incident_management,tperfil.agent_view,tperfil.agent_edit,tperfil.alert_edit,tperfil.alert_management,tperfil.pandora_management,tperfil.db_management,tperfil.user_management FROM tusuario_perfil,tperfil WHERE tusuario_perfil.id_perfil = tperfil.id_perfil AND tusuario_perfil.id_usuario = '%s'", $id_user);
		//GroupID = 0, access doesnt matter (use with caution!) - Any user gets access to group 0
	} else {
		$query1=sprintf("SELECT tperfil.incident_view,tperfil.incident_edit,tperfil.incident_management,tperfil.agent_view,tperfil.agent_edit,tperfil.alert_edit,tperfil.alert_management,tperfil.pandora_management,tperfil.db_management,tperfil.user_management FROM tusuario_perfil,tperfil WHERE tusuario_perfil.id_perfil = tperfil.id_perfil 
AND tusuario_perfil.id_usuario = '%s' AND (tusuario_perfil.id_grupo = %d OR tusuario_perfil.id_grupo= 1)", $id_user, $id_group);
	}
	
	$rowdup = get_db_all_rows_sql ($query1);
	$result = 0;

	if (!$rowdup)
		return $result;

	foreach($rowdup as $row) {
		// For each profile for this pair of group and user do...
		switch ($access) {
		case "IR":
			$result += $row["incident_view"];
			break;
		case "IW":
			$result += $row["incident_edit"];
			break;
		case "IM":
			$result += $row["incident_management"];
			break;
		case "AR":
			$result += $row["agent_view"];
			break;
		case "AW":
			$result += $row["agent_edit"];
			break;
		case "LW":
			$result += $row["alert_edit"];
			break;
		case "LM":
			$result += $row["alert_management"];
			break;
		case "PM":
			$result += $row["pandora_management"];
			break;
		case "DM":
			$result += $row["db_management"];
			break;
		case "UM":
			$result += $row["user_management"];
			break;
		}
	}
	if ($result > 1)
		$result = 1;
	return $result; 
} 

/** 
 * Adds an audit log entry.
 * 
 * @param string $id User id
 * @param string $ip Client IP
 * @param string $accion Action description
 * @param string $descripcion Long action description
 */
function audit_db ($id, $ip, $accion, $descripcion){
	$accion = safe_input($accion);
	$descripcion = safe_input($descripcion);
	$sql = sprintf ("INSERT INTO tsesion (ID_usuario, accion, fecha, IP_origen,descripcion, utimestamp) VALUES ('%s','%s',NOW(),'%s','%s',UNIX_TIMESTAMP(NOW()))",$id,$accion,$ip,$descripcion);
	process_sql ($sql);
}

/**
 * Log in a user into Pandora.
 *
 * @param string $id_user User id
 * @param string $ip Client user IP address.
 */
function logon_db ($id_user, $ip) {
	audit_db ($id_user, $ip, "Logon", "Logged in");
	// Update last registry of user to set last logon. How do we audit when the user was created then?
	$sql = sprintf ("UPDATE tusuario SET fecha_registro = NOW() WHERE id_usuario = '%s'", $id_user);
	process_sql ($sql);
}

/**
 * Log out a user into Pandora.
 *
 * @param string $id_user User id
 * @param string $ip Client user IP address.
 */
function logoff_db ($id_user, $ip) {
	audit_db ($id_user, $ip, "Logoff", "Logged out");
}

/**
 * Get profile name from id.
 * 
 * @param int $id_profile Id profile in tperfil
 * 
 * @return string Profile name of the given id
 */
function dame_perfil ($id_profile) {
	return (string) get_db_value ('name', 'tperfil', 'id_perfil', (int) $id_profile);
}

/** 
 * Get disabled field of a group
 * 
 * @param int id_group Group id
 * 
 * @return bool Disabled field of given group
 */
function give_disabled_group ($id_group) {
	return (bool) get_db_value ('disabled', 'tgrupo', 'id_grupo', (int) $id_group);
}

/**
 * Get all the agents within a group(s). For non-godmode usage get_user_groups should be used.
 *
 * @param mixed $id_group Group id or an array of ID's. If nothing is selected, it will select all
 * 
 * @param bool $disabled Add disabled agents to agents. Default: False.
 * 
 * @param string $case Which case to return the agentname as (lower, upper, none)
 *
 * @return array An array with all agents in the group or an empty array
 */
function get_group_agents ($id_group = 0, $disabled = false, $case = "lower") {
	$id_group = safe_int ($id_group, 1);
	
	//If id_group is an array, then 
	if (empty ($id_group) || in_array (1, (array) $id_group)) {
		//If All is included in the group list, just select All
		$id_group = 1;
	} else {
		//If All is not included, select what we need
		$id_group = implode (",", (array) $id_group);
	}
	
	/* 'All' group must return all agents */
	$search = '';
	if (!empty ($id_group) && $id_group > 1) {
		$search .= sprintf (' WHERE id_grupo IN (%s)', $id_group);
	}
	if ($disabled !== false) {
		$search .= (($search == '') ? ' WHERE' : ' AND' ).' disabled = 0';
	}
	
	$sql = sprintf ("SELECT id_agente, nombre FROM tagente%s ORDER BY nombre", $search);
	$result = get_db_all_rows_sql ($sql);
	
	if ($result === false)
		return array (); //Return an empty array
	
	$agents = array ();
	foreach ($result as $row) {
		switch ($case) {
		case "lower":
			$agents[$row["id_agente"]] = mb_strtolower ($row["nombre"],"UTF-8");
		break;	
		case "upper":
			$agents[$row["id_agente"]] = mb_strtoupper ($row["nombre"],"UTF-8");
		break;
		default:
			$agents[$row["id_agente"]] = $row["nombre"];
		}
	}
	return ($agents);
}

/**
 * Get all the modules in an agent. If an empty list is passed it will select all
 *
 * @param mixed Agent id to get modules. It can also be an array of agent id's.
 * @param mixed Array, comma delimited list or singular value of rows to
 * select. If nothing is specified, nombre will be selected.
 *
 * @return array An array with all modules in the agent.
 * If multiple rows are selected, they will be in an array
 */
function get_agent_modules ($id_agent, $details = false) {
	$id_agent = safe_int ($id_agent, 1);
	
	if (empty ($id_agent)) {
		$filter = '';
	} else {
		$filter = sprintf (' WHERE id_agente IN (%s)', implode (",", (array) $id_agent));
	}
	
	if (empty ($details)) {
		$details = "nombre";
	} else {
		$details = safe_input ($details);
	}
	
	$sql = sprintf ('SELECT id_agente_modulo,%s
		FROM tagente_modulo
		%s
		ORDER BY nombre',
		implode (",", (array) $details),
		$filter);
	$result = get_db_all_rows_sql ($sql);
	
	if (empty ($result)) {
		return array ();
	}
	
	$modules = array ();
	foreach ($result as $row) {
		if (is_array ($details)) {
			 //Just stack the information in array by ID
			$modules[$row['id_agente_modulo']] = $row;
		} else {
			$modules[$row['id_agente_modulo']] = $row[$details];
		}
	}
	return $modules;
}
/**
 * Get a list of the reports the user can view.
 *
 * A user can view a report by two ways:
 *  - The user created the report (id_user field in treport)
 *  - The report is not private and the user has reading privileges on 
 *	the group associated to the report
 *
 * @param string $id_user User id
 *
 * @return array An array with all the reports the user can view.
 */
function get_reports ($id_user) {
	$user_reports = array ();
	$all_reports = get_db_all_rows_in_table ('treport', 'name');
	if ($all_reports === false) {
		return $user_reports;
	}
	foreach ($all_reports as $report) {
		/* The report is private and it does not belong to the user */
		if ($report['private'] && $report['id_user'] != $id_user)
			continue;
		/* Check ACL privileges on report group */
		if (! give_acl ($id_user, $report['id_group'], 'AR'))
			continue;
		array_push ($user_reports, $report);
	}
	return $user_reports;
}

/** 
 * Get group icon from group.
 * 
 * @param int id_group Id group to get the icon
 * 
 * @return string Icon path of the given group
 */
function dame_grupo_icono ($id_group) {
	return (string) get_db_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
}

/** 
 * Get agent id from an agent name.
 * 
 * @param string $agent_name Agent name to get its id.
 * 
 * @return int Id from the agent of the given name.
 */
function dame_agente_id ($agent_name) {
	return (int) get_db_value ('id_agente', 'tagente', 'nombre', $agent_name);
}

/** 
 * Get name of an agent.
 * 
 * @param int $id_agent Agent id.
 * @param string $case Case (upper, lower, none)
 * 
 * @return string Name of the given agent.
 */
function get_agent_name ($id_agent, $case = "upper") {
	$agent = (string) get_db_value ('nombre', 'tagente', 'id_agente', (int) $id_agent);
	switch ($case) {
		case "upper":
			return mb_strtoupper ($agent,"UTF-8");
			break;
		case "lower":
			return mb_strtolower ($agent,"UTF-8");
			break;
		default:
			return ($agent);
	}
}

/** 
 * Get MD5 encrypted password of a user.
 * 
 * @param string id_usuario User id.
 * 
 * @return string Password of a user (should be compared to MD5 string)
 */
function get_user_password ($id_user) {
	return (string) get_db_value ('password', 'tusuario', 'id_usuario', $id_user);
}

/** 
 * Get type name for alerts (e-mail, text, internal, ...) based on type number
 * 
 * @param int id_alert Alert type id.
 * 
 * @return string Type name of the alert.
 */
function get_alert_type ($id_type) {
	return (string) get_db_value ('nombre', 'talerta', 'id_alerta', (int) $id_type);
}

/** 
 * Get name of a module group.
 * 
 * @param int $id_module_group Module group id.
 * 
 * @return string Name of the given module group.
 */
function dame_nombre_grupomodulo ($id_module_group) {
	return (string) get_db_value ('name', 'tmodule_group', 'id_mg', (int) $id_module_group);
}

/** 
 * Get the name of an exporting server
 * 
 * @param int $id_server Server id
 * 
 * @return string The name of given server.
 */
function dame_nombre_servidorexportacion ($id_server) {
	return (string) get_db_value ('name', 'tserver_export', 'id', (int) $id_server);
}

/** 
 * Get the name of a plugin
 * 
 * @param int id_plugin Plugin id.
 * 
 * @return string The name of the given plugin
 */
function dame_nombre_pluginid ($id_plugin) {
	return (string) get_db_value ('name', 'tplugin', 'id', (int) $id_plugin);
}

/** 
 * Get the name of a module type
 * 
 * @param int $id_type Type id
 * 
 * @return string The name of the given type.
 */
function giveme_module_type ($id_type) {
	return (string) get_db_value ('nombre', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

/** 
 * Get agent id of an agent module.
 * 
 * @param int $id_agentmodule Agent module id.
 * 
 * @return int The id of the agent of given agent module
 */
function get_agentmodule_agent ($id_agentmodule) {
	return (int) get_db_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', (int) $id_agentmodule);
}

/** 
 * Get agent name of an agent module.
 * 
 * @param int $id_agente_modulo Agent module id.
 * 
 * @return string The name of the given agent module.
 */
function get_agentmodule_agent_name ($id_agentmodule) {
	// Since this is a helper function we don't need to do casting
	return (string) get_agent_name (get_agentmodule_agent ($id_agentmodule));
}

/** 
 * Get the module name of an agent module.
 * 
 * @param int $id_agente_modulo Agent module id.
 * 
 * @return string Name of the given agent module.
 */
function get_agentmodule_name ($id_agente_modulo) {
	return (string) get_db_value ('nombre', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
}

/** 
 * Get the module type of an agent module.
 * 
 * @param int $id_agentmodule Agent module id.
 * 
 * @return string Module type of the given agent module.
 */
function get_agentmodule_type ($id_agentmodule) {
	return (int) get_db_value ('id_tipo_modulo', 'tagente_modulo', 'id_agente_modulo', (int) $id_agentmodule);
}

/** 
 * Get the real name of an user.
 * 
 * @param string $id_user User id
 * 
 * @return string Real name of given user.
 */
function dame_nombre_real ($id_user) {
	return (string) get_db_value ('nombre_real', 'tusuario', 'id_usuario', $id_user);
}

/**
 * Get all the times a monitor went down during a period.
 * 
 * @param int $id_agent_module Agent module of the monitor.
 * @param int $period Period timed to check from date
 * @param int $date Date to check (now by default)
 *
 * @return int The number of times a monitor went down.
 */
function get_monitor_downs_in_period ($id_agent_module, $period, $date = 0) {
	if ($date == 0) {
		$date = get_system_time ();
	}
	$datelimit = $date - $period;
	$sql = sprintf ("SELECT COUNT(`id_agentmodule`) FROM `tevento` WHERE 
			`event_type` = 'monitor_down' 
			AND `id_agentmodule` = %d 
			AND `utimestamp` > %d 
			AND `utimestamp` <= %d",
			$id_agent_module, $datelimit, $date);
	 
	return get_db_sql ($sql);
}

/**
 * Get the last time a monitor went down during a period.
 * 
 * @param int $id_agent_module Agent module of the monitor.
 * @param int $period Period timed to check from date
 * @param int $date Date to check (now by default)
 *
 * @return int The last time a monitor went down.
 */
function get_monitor_last_down_timestamp_in_period ($id_agent_module, $period, $date = 0) {
	if ($date == 0) {
		$date = get_system_time ();
	}
	$datelimit = $date - $period;
	$sql = sprintf ("SELECT MAX(`timestamp`) FROM `tevento` WHERE 
			event_type = 'monitor_down' 
			AND `id_agentmodule` = %d 
			AND `utimestamp` > %d 
			AND `utimestamp` <= %d",
			$id_agent_module, $datelimit, $date);
	
	return get_db_sql ($sql);
}

/**
 * Get all the monitors defined in an group.
 * 
 * @param int $id_group Group id to get all the monitors.
 * 
 * @return array An array with all the monitors defined in the group (tagente_modulo).
 */
function get_monitors_in_group ($id_group) {
	if ($id_group <= 1) {
		//We select all groups the user has access  to if it's 0, -1 or 1
		global $config;
		$id_group = array_keys (get_user_groups ($config['id_user']));
	}
	
	if (is_array ($id_group)) {
		$id_group = implode (",",$id_group);
	}
	
	$sql = sprintf ("SELECT `tagente_modulo`.* FROM `tagente_modulo`, `ttipo_modulo`, `tagente` WHERE 
			`id_tipo_modulo` = `id_tipo` 
			AND `tagente`.`id_agente` = `tagente_modulo`.`id_agente` 
			AND `ttipo_modulo`.`nombre` LIKE '%%_proc' 
			AND `tagente`.`id_grupo` IN (%s) ORDER BY `tagente`.`nombre`", $id_group);
	return get_db_all_rows_sql ($sql);
}

/** 
 * Get all the events happened in a group during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 * 
 * @param int $id_group Group id to get events.
 * @param int $period Period of time in seconds to get events.
 * @param int $date Beginning date to get events.
 * 
 * @return array An array with all the events happened.
 */
function get_group_events ($id_group, $period, $date) {
	$datelimit = $date - $period;
	
	if ($id_group == 1) {
		$sql = sprintf ('SELECT * FROM tevento 
			WHERE utimestamp > %d AND utimestamp <= %d
			ORDER BY utimestamp ASC',
			$datelimit, $date);
	} else {
		$sql = sprintf ('SELECT * FROM tevento 
			WHERE utimestamp > %d AND utimestamp <= %d
			AND id_grupo = %d
			ORDER BY utimestamp ASC',
			$datelimit, $date, $id_group);
	}
	
	return get_db_all_rows_sql ($sql);
}

/** 
 * Get all the monitors defined in an agent.
 * 
 * @param int $id_agent Agent id to get all the monitors.
 * 
 * @return array An array with all the monitors defined (tagente_modulo).
 */
function get_monitors_in_agent ($id_agent) {
	$sql = sprintf ("SELECT `tagente_modulo`.*
			FROM `tagente_modulo`, `ttipo_modulo`, `tagente`
			WHERE `id_tipo_modulo` = `id_tipo`
			AND `tagente`.`id_agente` = `tagente_modulo`.`id_agente`
			AND `ttipo_modulo`.`nombre` LIKE '%%_proc'
			AND `tagente`.`id_agente` = %d", $id_agent);
	return get_db_all_rows_sql ($sql);
}

/** 
 * Get all the monitors down during a period of time.
 * 
 * @param array $monitors An array with all the monitors to check. Each
 * element of the array must be a dictionary.
 * @param int $period Period of time to check the monitors.
 * @param int $date Beginning date to check the monitors.
 * 
 * @return array An array with all the monitors that went down in that
 * period of time.
 */
function get_monitors_down ($monitors, $period = 0, $date = 0) {
	$monitors_down = array ();
	if (empty ($monitors))
		return $monitors_down;

	foreach ($monitors as $monitor) {
		$down = get_monitor_downs_in_period ($monitor['id_agente_modulo'], $period, $date);
		if ($down > 0)
			array_push ($monitors_down, $monitor);
	}
	return $monitors_down;
}

/**
 * Get all the times an alerts fired during a period.
 * 
 * @param int $id_agent_module Agent module of the alert.
 * @param int $period Period timed to check from date
 * @param int $date Date to check (now by default)
 *
 * @return int The number of times an alert fired.
 */
function get_alert_fires_in_period ($id_agent_module, $period, $date = 0) {
	if (!$date)
		$date = get_system_time ();
	$datelimit = $date - $period;
	$sql = sprintf ("SELECT COUNT(`id_agentmodule`) FROM `tevento` WHERE
			`event_type` = 'alert_fired'
			AND `id_agentmodule` = %d
			AND `utimestamp` > %d 
			AND `utimestamp` <= %d",
			$id_agent_module, $datelimit, $date);
	return (int) get_db_sql ($sql);
}

/** 
 * Get all the alerts defined in a group.
 *
 * It gets all the alerts of all the agents on a given group.
 * 
 * @param int $id_group Group id to check.
 * 
 * @return array An array with alerts dictionaries defined in a group.
 */
function get_group_alerts ($id_group) {
	$alerts = array ();
	$agents = get_group_agents ($id_group, false, "none");
	
	foreach ($agents as $agent_id => $agent_name) {
		$agent_alerts = get_agent_alerts ($agent_id);
		$alerts = array_merge ($alerts, $agent_alerts);
	}
	
	return $alerts;
}

/** 
 * Get all the alerts fired during a period, given a list of alerts.
 * 
 * @param array $alerts A list of alerts to check. See get_alerts_in_group()
 * @param int $period Period of time to check fired alerts.
 * @param int $date Beginning date to check fired alerts in UNIX format (current date by default)
 * 
 * @return array An array with the alert id as key and the number of times
 * the alert was fired (only included if it was fired).
 */
function get_alerts_fired ($alerts, $period = 0, $date = 0) {
	if (! $date)
		$date = get_system_time ();
	$datelimit = $date - $period;

	$alerts_fired = array ();
	$agents = array ();
	foreach ($alerts as $alert) {
		$fires = get_alert_fires_in_period ($alert['id_agente_modulo'], $period, $date);
		if (! $fires) {
			continue;
		}
		$alerts_fired[$alert['id_aam']] = $fires;
	}
	return $alerts_fired;
}

/**
 * Get the last time an alert fired during a period.
 * 
 * @param int $id_agent_module Agent module of the monitor.
 * @param int $period Period timed to check from date
 * @param int $date Date to check (now by default)
 *
 * @return int The last time an alert fired.
 */
function get_alert_last_fire_timestamp_in_period ($id_agent_module, $period, $date = 0) {
	if ($date == 0) {
		$date = get_system_time ();
	}
	$datelimit = $date - $period;
	$sql = sprintf ("SELECT MAX(`timestamp`) FROM `tevento` WHERE
			`event_type` = 'alert_fired'
			AND `id_agentmodule` = %d
			AND `utimestamp` > %d 
			AND `utimestamp` <= %d",
			$id_agent_module, $datelimit, $date);
	return get_db_sql ($sql);
}

/** 
 * Get the server name.
 * 
 * @param int $id_server Server id.
 * 
 * @return string Name of the given server
 */
function get_server_name ($id_server) {
	return (string) get_db_value ('name', 'tserver', 'id_server', (int) $id_server);
}

/** 
 * Get the module type name (type = generic_data, remote_snmp, ...)
 * 
 * @param int $id_type Type id
 * 
 * @return string Name of the given type.
 */
function get_moduletype_name ($id_type) {
	return (string) get_db_value ('nombre', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

/**
 * Returns an array with all module types (default) or if "remote" or "agent" 
 * is passed it will return only remote (ICMP, SNMP, TCP...) module types 
 * otherwise the full list + the column you specify 
 *
 * @param string Specifies which type to return (will return an array with id's)
 * @param string Which rows to select (defaults to nombre)
 *
 * @return array Either the full table or if a type is specified, an array with id's
 */
function get_moduletypes ($type = "all", $rows = "nombre") {
	$return = array ();
	$rows = (array) $rows; //Cast as array
	$row_cnt = count ($rows);
	if ($type == "remote") {
		return array_merge (range (6,18), (array) 100);
	} elseif ($type == "agent") {
		return array_merge (range (1,4), range (19,24));
	}
	
	$sql = sprintf ("SELECT id_tipo,%s FROM ttipo_modulo", implode (",", $rows));
	$result = get_db_all_rows_sql ($sql);
	if ($result === false) {
		return $return;
	}
	
	foreach ($result as $type) {
		if ($row_cnt > 1) {
			$return[$type["id_tipo"]] = $type;
		} else {
			$return[$type["id_tipo"]] = $type[reset ($rows)];
		}
	}
	return $return;
}


/** 
 * @deprecated Use get_agent_group ($id) now (fully compatible)
 */
function dame_id_grupo ($id_agent) {
	return get_agent_group ($id_agent);
}


/** 
 * Get the number of pandora data packets in the database. 
 *
 * In case an array is passed, it will have a value for every agent passed 
 * incl. a total otherwise it will just return the total
 * 
 * @param mixed $id_agent Agent id or array of agent id's, 0 for all
 *
 * @return mixed The number of data in the database
 */
function get_agent_modules_data_count ($id_agent = 0) {
	$id_agent = safe_int ($id_agent, 1);
	
	if (empty ($id_agent)) {
		$id_agent = array ();
	} else {
		$id_agent = (array) $id_agent;
	}
	
	$count = array ();
	$count["total"] = 0;
	
	$query[0] = "SELECT COUNT(*) FROM tagente_datos";
	//$query[1] = "SELECT COUNT(*) FROM tagente_datos_inc";
	//$query[2] = "SELECT COUNT(*) FROM tagente_datos_string";
	
	foreach ($id_agent as $agent_id) {
		//Init value
		$count[$agent_id] = 0;
		$modules = array_keys (get_agent_modules ($agent_id));
		foreach ($query as $sql) {
			//Add up each table's data
			$count[$agent_id] += (int) get_db_sql ($sql." WHERE id_agente_modulo IN (".implode (",", $modules).")");
		}
		//Add total agent count to total count
		$count["total"] += $count[$agent_id];
	}
	
	if ($count["total"] == 0) {
		foreach ($query as $sql) {
			$count["total"] += (int) get_db_sql ($sql);
		}
	}
	
	if (!isset ($agent_id)) {
		//If agent_id is not set, it didn't loop through any agents
		return $count["total"];
	}
	return $count; //Return the array
}

/** 
 * Get the operating system name.
 * 
 * @param int id_os Operating system id.
 * 
 * @return string Name of the given operating system.
 */
function get_os_name ($id_os) {
	return (string) get_db_value ('name', 'tconfig_os', 'id_os', (int) $id_os);
}

/** 
 * Update user last login timestamp.
 * 
 * @param string $id_user User id
 */
function update_user_contact ($id_user) {
	global $config;
	
	$sql = sprintf ("UPDATE `tusuario` set `fecha_registro` = NOW() WHERE 'id_usuario' = %d",$id_user);
	process_sql ($sql);
}

/** 
 * Get the user email
 * 
 * @param string $id_user User id.
 * 
 * @return string Get the email address of an user
 */
function dame_email ($id_user) {
	return (string) get_db_value ('direccion', 'tusuario', 'id_usuario', $id_user);
}

/** 
 * Checks if a user is administrator.
 * 
 * @param string $id_user User id.
 * 
 * @return bool True is the user is admin
 */
function dame_admin ($id_user) {
	$level = get_db_value ('nivel', 'tusuario', 'id_usuario', $id_user);
	if ($level == 1) {
		return true;
	} else {
		return false;
	}
}

/** 
 * @deprecated Use check_login () instead
 */
function comprueba_login () {
	return check_login ();
}

/** 
 * Check if an agent has alerts fired.
 * 
 * @param int $id_agent Agent id.
 * 
 * @return bool True if the agent has fired alerts.
 */
function check_alert_fired ($id_agent) {
	$sql = "SELECT COUNT(*) FROM talerta_agente_modulo, tagente_modulo
		WHERE talerta_agente_modulo.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND times_fired > 0 AND id_agente = ".$id_agent;
	
	$value = get_db_sql ($sql);
	if ($value > 0)
		return true;
	return false;
}

/** 
 * Check is a user exists in the system
 * 
 * @param string $id_user User id.
 * 
 * @return bool True if the user exists.
 */
function existe ($id_user) {
	$user = get_db_row ('tusuario', 'id_usuario', $id_user);
	if (! $user)
		return false;
	return true;
}

/** 
 * Get the interval value of an agent module.
 *
 * If the module interval is not set, the agent interval is returned
 * 
 * @param int $id_agent_module Id agent module to get the interval value.
 * 
 * @return int Module interval or agent interval if no module interval
 */
function get_module_interval ($id_agent_module) {
	$interval = (int) get_db_value ('module_interval', 'tagente_modulo', 'id_agente_modulo', (int) $id_agent_module);
	if ($interval > 0)
		return $interval;
		
	$id_agent = give_agent_id_from_module_id ($id_agent_module);
	return (int) get_agent_interval ($id_agent);
}

/** 
 * Get the interval of an agent.
 * 
 * @param int $id_agent Agent id.
 * 
 * @return int The interval value of a given agent
 */
function get_agent_interval ($id_agent) {
	return (int) get_db_value ('intervalo', 'tagente', 'id_agente', $id_agent);
}

/** 
 * Get the flag value of an agent module.
 * 
 * @param int $id_agent_module Agent module id.
 * 
 * @return bool The flag value of an agent module.
 */
function give_agentmodule_flag ($id_agent_module) {
	return get_db_value ('flag', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
}

/** 
 * Prints a list of <options> HTML tags with the groups the user has
 * reading privileges.
 *
 * @deprecated Use get_user_groups () in combination with print_select ()
 * instead
 * 
 * @param string $id_user User id
 * @param bool $show_all Flag to show all the groups or not. True by default.
 * 
 * @return array An array with all the groups
 */
function list_group ($id_user, $show_all = 1){
	$mis_grupos = array (); // Define array mis_grupos to put here all groups with Agent Read permission
	$sql = 'SELECT id_grupo, nombre FROM tgrupo ORDER BY nombre';
	$result = get_db_all_rows_sql ($sql);
	if (!$result)
		return $mis_grupos;
	foreach ($result as $row) {
		if (($row["id_grupo"] != 1 || $show_all == 1) && $row["id_grupo"] != 0 && give_acl($id_user,$row["id_grupo"], "AR") == 1) {
			//Put in  an array all the groups the user belongs to
			array_push ($mis_grupos, $row["id_grupo"]);
			echo '<option value="'.$row["id_grupo"].'">'.$row["nombre"].'</option>';
		}
	}
	return ($mis_grupos);
}

/** 
 * Get a list of the groups a user has reading privileges to.
 *
 * @deprecated Use get_user_groups () instead
 * 
 * @param int $id_user User id
 * 
 * @return array A list of the groupid => groups the user has reading privileges.
 */
function list_group2 ($id_user) {
	$mis_grupos = array (); // Define array mis_grupos to put here all groups with Agent Read permission
	$result = get_db_all_fields_in_table ('tgrupo', 'id_grupo');
	if (!$result)
		return $mis_grupos;
	foreach ($result as $row) {
		if (give_acl ($id_user, $row["id_grupo"], "AR") == 1) {
			array_push ($mis_grupos, $row["id_grupo"]); //Put in array all the groups the user belongs to
		}
	}	
	
	return ($mis_grupos);
}

/**
 * Get a list of all users in an array [username] => real name
 * 
 * @param string $order by (id_usuario, nombre_real or fecha_registro)
 *
 * @return array An array of users
 */
function list_users ($order = "nombre_real") {
	switch ($order) {
	case "id_usuario":
	case "fecha_registro":
	case "nombre_real":
		break;
	default:
		$order = "nombre_real";
	}
	
	$output = array();
	
	$result = get_db_all_rows_sql ("SELECT id_usuario, nombre_real FROM tusuario ORDER BY ".$order);
	if ($result !== false) {
		foreach ($result as $row) {
			$output[$row["id_usuario"]] = $row["nombre_real"];
		}
	}
	
	return $output;
}
 
/** 
 * Get all the groups a user has reading privileges.
 * 
 * @param string $id_user User id
 * @param string $privilege The privilege to evaluate
 * @return array A list of the groups the user has certain privileges.
 */
function get_user_groups ($id_user = 0, $privilege = "AR") {
	if ($id_user == 0) {
		global $config;
		$id_user = $config['id_user'];
	}
	
	$user_groups = array ();
	$groups = get_db_all_rows_in_table ('tgrupo', 'nombre');

	if (!$groups)
		return $user_groups;

	foreach ($groups as $group) {
		if (! give_acl ($id_user, $group["id_grupo"], $privilege))
			continue;
		$user_groups[$group['id_grupo']] = $group['nombre'];
	}
	
	return $user_groups;
}

/** 
 * Get module type icon.
 *
 * TODO: Create print_moduletype_icon and print the full tag including hover etc.
 * @deprecated Use print_moduletype_icon instead
 * 
 * @param int $id_type Module type id
 * 
 * @return string Icon filename of the given group
 */
function show_icon_type ($id_type) { 
	return (string) get_db_value ('icon', 'ttipo_modulo', 'id_tipo', $id_type);
}

/**
 * Return a string containing image tag for a given target id (server)
 * TODO: Make this print_servertype_icon and move to functions_ui.php. Make XHTML compatible. Make string translatable
 *
 * @deprecated Use print_servertype_icon instead
 *
 * @param int $id Server type id
 *
 * @return string Fully formatted  IMG HTML tag with icon
 */
function show_server_type ($id) {
	global $config;
	switch ($id) {
	case 1:
		return '<img src="images/data.png" title="Pandora FMS Data server">';
		break;
	case 2:
		return '<img src="images/network.png" title="Pandora FMS Network server">';
		break;
	case 4:
		return '<img src="images/plugin.png" title="Pandora FMS Plugin server">';
		break;
	case 5:
		return '<img src="images/chart_bar.png" title="Pandora FMS Prediction server">';
		break;
	case 6:
		return '<img src="images/wmi.png" title="Pandora FMS WMI server">';
		break;
	default: return "--";
	}
}

/** 
 * Get a module category name
 * 
 * @param id_category Id category
 * 
 * @return Name of the given category
 */
function give_modulecategory_name ($id_category) {
	switch ($id_category) {
	case 0: 
		return __('Software agent data');
		break;
	case 1: 
		return __('Software agent monitor');
		break;
	case 2: 
		return __('Network agent data');
		break;
	case 3: 
		return __('Network agent monitor');
		break;
	}
	return __('Unknown');
}

/** 
 * Get a network component group name
 * 
 * @param int $id_network_component_group Id network component group.
 * 
 * @return string Name of the given network component group
 */
function give_network_component_group_name ($id_network_component_group) {
	return (string) get_db_value ('name', 'tnetwork_component_group', 'id_sg', $id_network_component_group);
}

/** 
 * Get a network profile name.
 * 
 * @param int $id_network_profile Id network profile
 * 
 * @return string Name of the given network profile.
 */
function get_networkprofile_name ($id_network_profile) {
	return (string) get_db_value ('name', 'tnetwork_profile', 'id_np', $id_network_profile);
}

/** 
 * Assign an IP address to an agent.
 * 
 * @param int id_agent Agent id
 * @param string ip_address IP address to assign
 */
function agent_add_address ($id_agent, $ip_address) {
	// Check if already is attached to agent
	$sql = sprintf ("SELECT COUNT(`ip`) FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
		AND ip = '%s' AND id_agent = %d",$ip_address,$id_agent);
	$current_address = get_db_sql ($sql);
	if ($current_address > 0)
		return;
	
	// Look for a record with this IP Address
	$id_address = (int) get_db_value ('id_a', 'taddress', 'ip', $ip_address);
	
	if ($id_address === 0) {
		// Create IP address in tadress table
		$sql = sprintf("INSERT INTO taddress (ip) VALUES ('%s')",$ip_address);
		$id_address = process_sql ($sql, "insert_id");
	}
	
	// Add address to agent
	$sql = sprintf("INSERT INTO taddress_agent
			(id_a, id_agent) VALUES
			(%d, %d)",$id_address, $id_agent);
	process_sql ($sql);
}

/** 
 * Unassign an IP address from an agent.
 * 
 * @param int $id_agent Agent id
 * @param string $ip_address IP address to unassign
 */
function agent_delete_address ($id_agent, $ip_address) {
	$sql = sprintf ("SELECT id_ag FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a AND ip = '%s'
		AND id_agent = %d",$ip_address, $id_agent);
	$id_ag = get_db_sql ($sql);
	if ($id_ag !== false) {
		$sql = sprintf ("DELETE FROM taddress_agent WHERE id_ag = %d",$id_ag);	
		process_sql ($sql);
	}
	// Need to change main address?
	if (get_agent_address ($id_agent) == $ip_address) {
		$new_ips = get_agent_addresses ($id_agent);
		// Change main address in agent to first one in the list
		$query = sprintf ("UPDATE tagente SET `direccion` = '%s' WHERE id_agente = %d", current ($new_ips), $id_agent);
		process_sql ($query);
	}
}

/** 
 * Get address of an agent.
 * 
 * @param int $id_agent Agent id
 * 
 * @return string The address of the given agent 
 */
function get_agent_address ($id_agent) {
	return (string) get_db_value ('direccion', 'tagente', 'id_agente', (int) $id_agent);
}

/**
 * Get the agent that matches an IP address
 *
 * @param string $ip_address IP address to get the agents.
 *
 * @return mixed The agent that has the IP address given. False if none were found.
 */
function get_agent_with_ip ($ip_address) {
	$sql = sprintf ('SELECT tagente.*
		FROM tagente, taddress, taddress_agent
		WHERE tagente.id_agente = taddress_agent.id_agent
		AND taddress_agent.id_a = taddress.id_a
		AND ip = "%s"', $ip_address);
	return get_db_row_sql ($sql);
}

/** 
 * Get all IP addresses of an agent
 * 
 * @param int $id_agent Agent id
 * 
 * @return array Array with the IP address of the given agent or an empty array.
 */
function get_agent_addresses ($id_agent) {
	$sql = sprintf ("SELECT ip FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
		AND id_agent = %d", $id_agent);
	
	$ips = get_db_all_rows_sql ($sql);
	
	if ($ips === false) {
		$ips = array ();
	}
	
	$ret_arr = array ();
	foreach ($ips as $row) {
		$ret_arr[$row["ip"]] = $row["ip"];
	}
	
	return $ret_arr;
}

/** 
 * Get agent id from an agent module.
 * 
 * @param int id_agent_module Id of the agent module.
 * 
 * @return int The agent if of the given module.
 */
function give_agent_id_from_module_id ($id_agent_module) {
	return (int) get_db_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
}

$sql_cache = array ('saved' => 0);

/** 
 * Get the first value of the first row of a table in the database.
 * 
 * @param string $field Field name to get
 * @param string $table Table to retrieve the data
 * @param string $field_search Field to filter elements
 * @param string $condition Condition the field must have
 *
 * @return mixed Value of first column of the first row. False if there were no row.
 */  
function get_db_value ($field, $table, $field_search = 1, $condition = 1) {
	if (is_int ($condition)) {
		$sql = sprintf ("SELECT %s FROM %s WHERE %s = %d LIMIT 1",
				$field, $table, $field_search, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ("SELECT %s FROM %s WHERE %s = %f LIMIT 1",
				$field, $table, $field_search, $condition);
	} else {
		$sql = sprintf ("SELECT %s FROM %s WHERE %s = '%s' LIMIT 1",
				$field, $table, $field_search, $condition);
	}
	$result = get_db_all_rows_sql ($sql);
	
	if ($result === false)
		return false;
	
	return $result[0][$field];
}

/** 
 * Get the first row of an SQL database query.
 * 
 * @param string $sql SQL select statement to execute.
 * 
 * @return mixed The first row of the result or false
 */
function get_db_row_sql ($sql) {
	$sql .= " LIMIT 1";
	$result = get_db_all_rows_sql ($sql);
	
	if($result === false) 
		return false;
	
	return $result[0];
}

/** 
 * Get the first row of a database query into a table.
 *
 * The SQL statement executed would be something like:
 * "SELECT * FROM $table WHERE $field_search = $condition"
 *
 * @param string $table Table to get the row
 * @param string $field_search Field to filter elementes
 * @param string $condition Condition the field must have.
 * 
 * @return mixed The first row of a database query or false.
 */
function get_db_row ($table, $field_search, $condition) {
	
	if (is_int ($condition)) {
		$sql = sprintf ("SELECT * FROM `%s` WHERE `%s` = %d LIMIT 1", $table, $field_search, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ("SELECT * FROM `%s` WHERE `%s` = %f LIMIT 1", $table, $field_search, $condition);
	} else {
		$sql = sprintf ("SELECT * FROM `%s` WHERE `%s` = '%s' LIMIT 1", $table, $field_search, $condition);
	}
	$result = get_db_all_rows_sql ($sql);
		
	if ($result === false) 
		return false;
	
	return $result[0];
}

/** 
 * Get a single field in the databse from a SQL query.
 *
 * @param string $sql SQL statement to execute
 * @param mixed $field Field number or row to get, beggining by 0. Default: 0
 *
 * @return mixed The selected field of the first row in a select statement.
 */
function get_db_sql ($sql, $field = 0) {
	$result = get_db_all_rows_sql ($sql);
	if($result === false)
		return false;

	return $result[0][$field];
}

/**
 * Get all the result rows using an SQL statement.
 * 
 * @param string $sql SQL statement to execute.
 *
 * @return mixed A matrix with all the values returned from the SQL statement or
 * false in case of empty result
 */
function get_db_all_rows_sql ($sql) {
	$return = process_sql ($sql);
	
	if (! empty ($return))
		return $return;
	//Return false, check with === or !==
	return false;
}

/**
 * Error handler function when an SQL error is triggered.
 * 
 * @param int $errno Level of the error raised (not used, but required by set_error_handler()).
 * @param string $errstr Contains the error message.
 *
 * @return bool
 */
function sql_error_handler ($errno, $errstr) {
	if (error_reporting () <= $errno)
		return false;
	echo "<strong>SQL error</strong>: ".$errstr."<br />\n";
	return true;
}

/**
 * This function comes back with an array in case of SELECT
 * in case of UPDATE, DELETE etc. with affected rows
 * an empty array in case of SELECT without results
 * Queries that return data will be cached so queries don't get repeated
 *
 * @param string $sql SQL statement to execute
 *
 * @param string $rettype (optional) What type of info to return in case of INSERT/UPDATE.
 *		'affected_rows' will return mysql_affected_rows (default value)
 *		'insert_id' will return the ID of an autoincrement value
 *		'info' will return the full (debug) information of a query
 *
 * @return mixed An array with the rows, columns and values in a multidimensional array or false in error
 */
function process_sql ($sql, $rettype = "affected_rows") {
	global $config;
	global $sql_cache;
	
	$retval = array();
	
	if ($sql == '')
		return false;
	
	if (! empty ($sql_cache[$sql])) {
		$retval = $sql_cache[$sql];
		$sql_cache['saved']++;
	} else {
		$result = mysql_query ($sql);
		if ($result === false) {
			$backtrace = debug_backtrace ();
			$error = sprintf ('%s (\'%s\') in <strong>%s</strong> on line %d',
				mysql_error (), $sql, $backtrace[0]['file'], $backtrace[0]['line']);
			set_error_handler ('sql_error_handler');
			trigger_error ($error);
			restore_error_handler ();
			return false;
		} elseif ($result === true) {
			if ($rettype == "insert_id") {
				return mysql_insert_id ();
			} elseif ($rettype == "info") {
				return mysql_info ();
			}
			return mysql_affected_rows (); //This happens in case the statement was executed but didn't need a resource
		} else {
			while ($row = mysql_fetch_array ($result)) {
				array_push ($retval, $row);
			}
			$sql_cache[$sql] = $retval;
			mysql_free_result ($result);
		}
	}
	if (! empty ($retval))
		return $retval;
	//Return false, check with === or !==
	return false;
}

/**
 * Get all the rows in a table of the database.
 * 
 * @param string $table Database table name.
 * @param string $order_field Field to order by.
 *
 * @return mixed A matrix with all the values in the table
 */
function get_db_all_rows_in_table ($table, $order_field = "") {
	if ($order_field != "") {
		return get_db_all_rows_sql ("SELECT * FROM `".$table."` ORDER BY ".$order_field);
	} else {	
		return get_db_all_rows_sql ("SELECT * FROM `".$table."`");
	}
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 * 
 * @param string $table Database table name.
 * @param string $field Field of the table.
 * @param string $condition Condition the field must have to be selected.
 * @param string $order_field Field to order by.
 *
 * @return mixed A matrix with all the values in the table that matches the condition in the field or false
 */
function get_db_all_rows_field_filter ($table, $field, $condition, $order_field = "") {
	if (is_int ($condition) || is_bool ($condition)) {
		$sql = sprintf ("SELECT * FROM `%s` WHERE `%s` = %d", $table, $field, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ("SELECT * FROM `%s` WHERE `%s` = %f", $table, $field, $condition);
	} else {
		$sql = sprintf ("SELECT * FROM `%s` WHERE `%s` = '%s'", $table, $field, $condition);
	}

	if ($order_field != "")
		$sql .= sprintf (" ORDER BY %s", $order_field);
	return get_db_all_rows_sql ($sql);
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 * 
 * @param string $table Database table name.
 * @param string $field Field of the table.
 *
 * @return mixed A matrix with all the values in the table that matches the condition in the field
 */
function get_db_all_fields_in_table ($table, $field = '', $condition = '', $order_field = '') {
	$sql = sprintf ("SELECT * FROM `%s`", $table);
	if ($condition != '') {
		$sql .= sprintf (" WHERE `%s` = '%s'", $field, $condition);
	}
	
	if ($order_field != "")
		$sql .= sprintf (" ORDER BY %s", $order_field);
	
	return get_db_all_rows_sql ($sql);
}

/**
 * Formats an array of values into a SQL string.
 *
 * This function is useful to generate an UPDATE SQL sentence from a list of
 * values. Example code:
 *
 * <code>
 * $values = array ();
 * $values['name'] = "Name";
 * $values['description'] = "Long description";
 * $sql = 'UPDATE table SET '.format_array_to_update_sql ($values).' WHERE id=1';
 * echo $sql;
 * </code>
 * Will return:
 * <code>
 * UPDATE table SET `name` = "Name", `description` = "Long description" WHERE id=1
 * </code>
 *
 * @param array Values to be formatted in an array indexed by the field name.
 *
 * @return string Values joined into an SQL string that can fits into an UPDATE
 * sentence.
 */
function format_array_to_update_sql ($values) {
	$fields = array ();
	
	foreach ($values as $field => $value) {
		if (! is_string ($field))
			continue;
		
		if ($value === NULL) {
			$sql = sprintf ("`%s` = NULL", $field);
		} elseif (is_int ($value) || is_bool ($value)) {
			$sql = sprintf ("`%s` = %d", $field, $value);
		} else if (is_float ($value) || is_double ($value)) {
			$sql = sprintf ("`%s` = %f", $field, $value);
		} else {
			$sql = sprintf ("`%s` = '%s'", $field, $value);
		}
		array_push ($fields, $sql);
	}
	
	return implode (", ", $fields);
}

/** 
 * Get the status of an alert assigned to an agent module.
 * 
 * @param int id_agentmodule Id agent module to check.
 * 
 * @return bool True if there were alerts fired.
 */
function return_status_agent_module ($id_agentmodule = 0) {
	$status = get_db_value ('estado', 'tagente_estado', 'id_agente_modulo', $id_agentmodule);
	
	if ($status == 100) {
		// We need to check if there are any alert on this item
		$times_fired = get_db_value ('SUM(times_fired)', 'talerta_agente_modulo',
			'id_agente_modulo', $id_agentmodule);
		if ($times_fired > 0) {
			return 0;
		}
		// No alerts fired for this agent module
		return 1;
	} elseif ($status == 0) { // 0 is ok for estado field
		return 1;
	} else {
		return 0;
	}
}

/** 
 * Get the status of a layout.
 *
 * It gets all the data of the contained elements (including nested
 * layouts), and makes an AND operation to be sure that all the items
 * are OK. If any of them is down, then result is down (0)
 * 
 * @param int $id_layout Id of the layout
 * 
 * @return bool The status of the given layout.
 */
function return_status_layout ($id_layout = 0) {
	$temp_status = 0;
	$temp_total = 0;
	$sql = sprintf ('SELECT id_agente_modulo, parent_item, id_layout_linked FROM `tlayout_data` WHERE `id_layout` = %d', $id_layout);
	$result = get_db_all_rows_sql ($sql);
	if ($result === false)
		return 0;
	
	foreach ($result as $rownum => $data) {
		if (($data["id_layout_linked"] != 0) && ($data["id_agente_modulo"] == 0)) {
			$temp_status += return_status_layout ($data["id_layout_linked"]);
			$temp_total++;
		} else {
			$temp_status += return_status_agent_module ($data["id_agente_modulo"]);
			$temp_total++;
		}
	}
	if ($temp_status == $temp_total) {
		return 1;
	}
	
	return 0;
}

/** 
 * Get the current value of an agent module.
 * 
 * @param int $id_agentmodule 
 * 
 * @return int a numerically formatted value 
 */
function return_value_agent_module ($id_agentmodule) {
	return format_numeric (get_db_value ('datos', 'tagente_estado', 
		'id_agente_modulo', $id_agentmodule));
}

/** 
 * Get the X axis coordinate of a layout item
 * 
 * @param int $id_layoutdata Id of the layout to get.
 * 
 * @return int The X axis coordinate value.
 */
function get_layoutdata_x ($id_layoutdata) {
	return (float) get_db_value ('pos_x', 'tlayout_data', 'id', (int) $id_layoutdata);
}

/** 
 * Get the Y axis coordinate of a layout item
 * 
 * @param int $id_layoutdata Id of the layout to get.
 * 
 * @return int The Y axis coordinate value.
 */
function get_layoutdata_y ($id_layoutdata){
	return (float) get_db_value ('pos_y', 'tlayout_data', 'id', (int) $id_layoutdata);
}

/**
 * Get the previous data to the timestamp provided.
 *
 * It's useful to know the first value of a module in an interval, 
 * since it will be the last value in the table which has a timestamp 
 * before the beginning of the interval. All this calculation is due
 * to the data compression algorithm.
 *
 * @param int $id_agent_module Agent module id
 * @param int $utimestamp The timestamp to look backwards from and get the data.
 *
 * @return mixed The row of tagente_datos of the last period. False if there were no data.
 */
function get_previous_data ($id_agent_module, $utimestamp = 0) {
	if (empty ($utimestamp))
		$utimestamp = time ();
	
	$interval = get_module_interval ($id_agent_module);
	$sql = sprintf ('SELECT * FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp <= %d 
			AND utimestamp > %d
			ORDER BY utimestamp DESC',
			$id_agent_module, $utimestamp, $utimestamp - $interval);
	
	return get_db_row_sql ($sql);
}

/** 
 * Get the average value of an agent module in a period of time.
 * 
 * @param int $id_agent_module Agent module id
 * @param int $period Period of time to check (in seconds)
 * @param int $date Top date to check the values. Default current time.
 * 
 * @return int The average module value in the interval.
 */
function get_agentmodule_data_average ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = get_system_time ();
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT SUM(datos), COUNT(*) FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d 
			ORDER BY utimestamp ASC",
			$id_agent_module, $datelimit, $date);
	$values = get_db_row_sql ($sql);
	$sum = (float) $values[0];
	$total = (int) $values[1];
	
	/* Get also the previous data before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data)
		return ($previous_data['datos'] + $sum) / ($total + 1);
	if ($total > 0)
		return $sum / $total;
	return 0;
}

/** 
 * Get the maximum value of an agent module in a period of time.
 * 
 * @param int $id_agent_module Agent module id to get the maximum value.
 * @param int $period Period of time to check (in seconds)
 * @param int $date Top date to check the values. Default current time.
 * 
 * @return float The maximum module value in the interval.
 */
function get_agentmodule_data_max ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = get_system_time ();
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT MAX(datos) FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d  AND utimestamp <= %d",
			$id_agent_module, $datelimit, $date);
	$max = (float) get_db_sql ($sql);
	
	/* Get also the previous report before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data !== false)
		return max ($previous_data['datos'], $max);
	
	return max ((float) $previous_data, $max);
}

/** 
 * Get the minimum value of an agent module in a period of time.
 * 
 * @param int Agent module id to get the minimum value.
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values in Unix time. Default current time.
 * 
 * @return float The minimum module value of the module
 */
function get_agentmodule_data_min ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = get_system_time ();
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT MIN(datos) FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d",
			$id_agent_module, $datelimit, $date);
	$min = (float) get_db_sql ($sql);
	
	/* Get also the previous data before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data)
		return min ($previous_data['datos'], $min);
	return $min;
}

/** 
 * Get the sum of values of an agent module in a period of time.
 * 
 * @param int $id_agent_module Agent module id to get the sumatory.
 * @param int $period Period of time to check (in seconds)
 * @param int $date Top date to check the values. Default current time.
 * 
 * @return int The sumatory of the module values in the interval.
 */
function get_agentmodule_data_sum ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = get_system_time ();
	$datelimit = $date - $period; // limit date
	$id_module_type = get_db_value ('id_tipo_modulo', 'tagente_modulo','id_agente_modulo', $id_agent_module);
	$module_name = get_db_value ('nombre', 'ttipo_modulo', 'id_tipo', $id_module_type);
	
	if (is_module_data_string ($module_name)) {
		return __('Wrong module type');
	}
	
	// Get the whole interval of data
	$sql = sprintf ('SELECT utimestamp, datos FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d 
			ORDER BY utimestamp ASC',
			$id_agent_module, $datelimit, $date);
	$datas = get_db_all_rows_sql ($sql);
	
	/* Get also the previous data before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data) {
		/* Add data to the beginning */
		array_unshift ($datas, $previous_data);
	}
	if ($datas === false) {
		return 0;
	}
	
	$last_data = "";
	$total_badtime = 0;
	$module_interval = get_module_interval ($id_agent_module);
	$timestamp_begin = $datelimit + $module_interval;
	$timestamp_end = 0;
	$sum = 0;
	$data_value = 0;
	
	foreach ($datas as $data) {
		$timestamp_end = $data["utimestamp"];
		$elapsed = $timestamp_end - $timestamp_begin;
		$times = intval ($elapsed / $module_interval);
			
		if (is_module_inc ($module_name)) {
			$data_value = $data['datos'] * $module_interval;
		} else {
			$data_value = $data['datos'];
		}
		
		$sum += $times * $data_value;
		$timestamp_begin = $data["utimestamp"];
	}

	/* The last value must be get from tagente_estado, but
	   it will count only if it's not older than date demanded
	*/
	$timestamp_end = get_db_value ('utimestamp', 'tagente_estado', 'id_agente_modulo', $id_agent_module);
	if ($timestamp_end <= $datelimit) {
		$elapsed = $timestamp_end - $timestamp_begin;
		$times = intval ($elapsed / $module_interval);
		if (is_module_inc ($module_name)) {
			$data_value = $data['datos'] * $module_interval;
		} else {
			$data_value = $data['datos'];
		}
		$sum += $times * $data_value;
	}
	
	return (float) $sum;
}

/** 
 * Get a translated string
 * 
 * @param string $string String to translate
 * 
 * @return string The translated string. If not defined, the same string will be returned
 */
function __ ($string) {
	global $l10n;

	if (is_null ($l10n))
		return $string;

	return $l10n->translate ($string);
}

/** 
 * Get the numbers of servers up.
 *
 * This check assumes that server_keepalive should be at least 15 minutes.
 * 
 * @return int The number of servers alive.
 */
function check_server_status () {
	$sql = "SELECT COUNT(id_server) FROM tserver WHERE status = 1 AND keepalive > NOW() - INTERVAL 15 MINUTE";
	$status = (int) get_db_sql ($sql); //Cast as int will assure a number value
	// Set servers to down
	if ($status == 0){ 
		process_sql ("UPDATE tserver SET status = 0");
	}
	return $status;
}

/** 
 * @deprecated Will show a small HTML table with some compound alert information
 * 
 * @param int $id_combined_alert 
 * 
 * @return string HTML block
 */
function show_alert_row_mini ($id_combined_alert) {
	$color=1;
	$sql = sprintf ("SELECT talerta_agente_modulo.*,tcompound_alert.operation
			FROM talerta_agente_modulo, tcompound_alert
			WHERE tcompound_alert.id_aam = talerta_agente_modulo.id_aam
			AND tcompound_alert.id = %d", $id_combined_alert);
	$result = get_db_all_rows_sql ($sql);
	
	if ($result === false)
		return;

	echo "<table width=400 cellpadding=2 cellspacing=2 class='databox'>";
	echo "<th>".__('Name')."</th>";
	echo "<th>".__('Oper')."</th>";
	/* Translators: Abbrevation for Time threshold */
	echo "<th>".__('Tt')."</th>";
	echo "<th>".__('Firing')."</th>";
	echo "<th>".__('Time')."</th>";
	/* Translators: Abbrevation for Description */
	echo "<th>".__('Desc')."</th>";
	echo "<th>".__('Recovery')."</th>";
	echo "<th>".__('MinMax.Al')."</th>";
	echo "<th>".__('Days')."</th>";
	echo "<th>".__('Fired')."</th>";

	foreach ($result as $row2) {
		if ($color == 1) {
			$tdcolor = "datos";
			$color = 0;
		} else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr>";
		if ($row2["disable"] == 1) {
			$tdcolor = "datos3";
		}
		echo "<td class=$tdcolor>".get_db_sql ("SELECT nombre FROM tagente_modulo WHERE id_agente_modulo =".$row2["id_agente_modulo"])."</td>";
		echo "<td class=$tdcolor>".$row2["operation"]."</td>";

		echo "<td class='$tdcolor'>".human_time_description ($row2["time_threshold"])."</td>";

		if ($row2["dis_min"]!=0) {
			$mytempdata = fmod ($row2["dis_min"], 1);
			if ($mytempdata == 0) {
				$mymin = intval ($row2["dis_min"]);
			} else {
				$mymin = format_for_graph ($row2["dis_min"]);
			}
		} else {
			$mymin = 0;
		}

		if ($row2["dis_max"]!=0) {
			$mytempdata = fmod ($row2["dis_max"], 1);
			if ($mytempdata == 0) {
				$mymax = intval ($row2["dis_max"]);
			} else {
				$mymax = format_for_graph ($row2["dis_max"]);
			}
		} else {
			$mymax = 0;
		}

		if (($mymin == 0) && ($mymax == 0)) {
			$mymin = __('N/A');
			$mymax = $mymin;
		}

		// We have alert text ?
		if ($row2["alert_text"]!= "") {
			echo "<td class='$tdcolor'>".__('Text')."</td>";
		} else {
			echo "<td class='$tdcolor'>".$mymin."/".$mymax."</td>";
		}

		// Alert times
		echo "<td class='$tdcolor'>";
		echo get_alert_times ($row2);

		// Description
		echo "</td><td class='$tdcolor'>".substr ($row2["descripcion"],0,20);

		// Has recovery notify activated ?
		if ($row2["recovery_notify"] > 0) {
			$recovery_notify = __('Yes');
		} else {
			$recovery_notify = __('No');
		}
		echo "</td><td class='$tdcolor'>".$recovery_notify;

		// calculare firing conditions
		if ($row2["alert_text"] != ""){
			$firing_cond = __('Text')."(".substr ($row2["alert_text"],0,8).")";
		} else {
			$firing_cond = $row2["min_alerts"]." / ".$row2["max_alerts"];
		}
		echo "</td><td class='$tdcolor'>".$firing_cond;

		// calculate days
		$firing_days = get_alert_days ( $row2 );
		echo "</td><td class='$tdcolor'>".$firing_days;

		// Fired ?
		if ($row2["times_fired"]>0) {
			echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_red.png' title='".__('Alert fired')."'></td>";
		} else {
			echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_green.png' title='".__('Alert not fired')."'></td>";
		}
	}
	echo "</table>";
}

/** 
 * @deprecated use get_server_info instead 
 * Get statistical information for a given server
 *
 * @param int Server id to get status
 * @return array Server info array
*/
function server_status ($id_server) {
	$serverinfo = get_server_info ($id_server);
	return $serverinfo[$id_server];
}

/**
 * Delete an agent from the database.
 *
 * @param mixed An array of agents ids or a single integer id to be erased
 *
 * @return bool False if error, true if success.
*/
function delete_agent ($id_agents) {
	//Init vars
	$errors = 0;
	
	//Subfunciton for less typing
	/**
	 * @ignore
	 */
	function temp_sql_delete ($table, $row, $value) {
		global $errors; //Globalize the errors variable
		$sql = sprintf ("DELETE FROM %s WHERE %s = %s", $table, $row, $value);
		$result = process_sql ($sql);
		if ($result === false)
			$errors++;
	}

	//Convert single values to an array
	if (!is_array ($id_agents)) {
		$id_agents[0] = (int) $id_agents;
	}

	//Start transaction
	process_sql ("SET AUTOCOMMIT = 0;");
	$trerr = process_sql ("START TRANSACTION;");
	
	if ($trerr === false) {
		echo "Error starting transaction";
		return false;
	}

	foreach ($id_agents as $id_agent) {
		$id_agent = (int) $id_agent; //Cast as integer
		$agent_name = get_agent_name ($id_agent);
		if ($id_agent < 1)
			continue; //If an agent is not an integer or invalid, don't process it 
	
		//A variable where we store that long subquery thing for
		//modules
		$tmodbase = "ANY(SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ".$id_agent.")";	
		
		//IP address
		$sql = sprintf ("SELECT id_ag FROM taddress_agent, taddress WHERE taddress_agent.id_a = taddress.id_a AND id_agent = %d", $id_agent);
		$result = get_db_all_rows_sql ($sql);
		
		if ($result)
		foreach ($result as $row) {
			temp_sql_delete ("taddress_agent", "id_ag", $row["id_ag"]);
		}
		
		// We cannot delete tagente_datos and tagente_datos_string here
		// because it's a huge ammount of time. tagente_module has a special
		// field to mark for delete each module of agent deleted and in 
		// daily maintance process, all data for that modules are deleted
		
		//Alert
		temp_sql_delete ("tcompound_alert", "id_aam", "ANY(SELECT id_aam FROM talerta_agente_modulo WHERE id_agent = ".$id_agent.")");
		temp_sql_delete ("talerta_agente_modulo", "id_agente_modulo", $tmodbase);
		temp_sql_delete ("talerta_agente_modulo", "id_agent", $id_agent);
		
		//Events (up/down monitors)
		temp_sql_delete ("tevento", "id_agente", $id_agent);

		//Graphs, layouts & reports
		temp_sql_delete ("tgraph_source", "id_agent_module", $tmodbase);
		temp_sql_delete ("tlayout_data", "id_agente_modulo", $tmodbase);
		temp_sql_delete ("treport_content", "id_agent_module", $tmodbase);
		
		//Planned Downtime
		temp_sql_delete ("tplanned_downtime_agents", "id_agent", $id_agent);
		
		//The status of the module
		temp_sql_delete ("tagente_estado", "id_agente_modulo", $tmodbase);
		
		//The actual modules, don't put anything based on
		//tagente_modulo after this
		temp_sql_delete ("tagente_modulo", "id_agente", $id_agent);
		
		process_sql ('UPDATE tagente_modulo SET delete_pending = 1, disabled = 1 WHERE id_agente = '. $id_agent);
		
		//Access entries
		temp_sql_delete ("tagent_access", "id_agent", $id_agent);

		//tagente_datos_inc
                temp_sql_delete ("tagente_datos_inc", "id_agente_modulo", $tmodbase);

		//And at long last, the agent
		temp_sql_delete ("tagente", "id_agente", $id_agent);
		
		// Delete remote configuration
		$agent_md5 = md5($agent_name, FALSE);

		if (isset($config["remote_config"]))
			if (file_exists($config["remote_config"] . "/" . $agent_md5 . ".md5")) {
				// Agent remote configuration editor
				$file_name = $config["remote_config"] . "/" . $agent_md5 . ".conf";
				unlink ($file_name);
				$file_name = $config["remote_config"] . "/" . $agent_md5 . ".md5";
				unlink ($file_name);
			}
	}

	if ($errors > 0) {
		process_sql ("ROLLBACK;");
		process_sql ("SET AUTOCOMMIT = 1;");
		return false;
	} else {
		process_sql ("COMMIT;");
		process_sql ("SET AUTOCOMMIT = 1;");
		return true;
	}
}

/**
 * This function will get all the server information in an array or a specific server
 *
 * @param mixed An optional integer or array of integers to select specific servers
 *
 * @return mixed False in case the server doesn't exist or an array with info.
 */
function get_server_info ($id_server = -1) {
	if (is_array ($id_server)) {
		$select_id = " WHERE id_server IN (".implode (",", $id_server).")";
	} elseif ($id_server > 0) {
		$select_id = " WHERE id_server IN (".(int) $id_server.")";
	} else {
		$select_id = "";
	}
	
	$sql = "SELECT * FROM tserver".$select_id;
	$result = get_db_all_rows_sql ($sql);
	
	if (empty ($result)) {
		return false;
	}
	
	$return = array ();
	foreach ($result as $server) {
		if ($server["network_server"] == 1) {
			$server["type"] = "network";
		} elseif ($server["data_server"] == 1) {
			$server["type"] = "data";
		} elseif ($server["plugin_server"] == 1) {
			$server["type"] = "plugin";
		} elseif ($server["wmi_server"] == 1) {
			$server["type"] = "wmi";
		} elseif ($server["recon_server"] == 1) {
			$server["type"] = "recon";
		} elseif ($server["snmp_server"] == 1) {
			$server["type"] = "snmp";
		} elseif ($server["prediction_server"] == 1) {
			$server["type"] = "prediction";
		} else {
			$server["type"] = "unknown";
		}
		
		$server["modules"] = get_db_sql ("SELECT COUNT(*) FROM tagente_estado, tagente_modulo 
			 WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
			 AND tagente_modulo.disabled = 0
			 AND tagente_estado.running_by = ".$server["id_server"]);
			
		$server["module_lag"] = get_db_sql ("SELECT COUNT(*) FROM tagente_estado, tagente_modulo, tagente
			WHERE tagente_estado.last_execution_try > 0
			AND tagente_estado.running_by = ".$server["id_server"]."
			AND tagente_modulo.id_agente = tagente.id_agente
			AND tagente.disabled = 0
			AND tagente_modulo.disabled = 0
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND (UNIX_TIMESTAMP() - tagente_estado.last_execution_try - tagente_estado.current_interval < 1200)");
			
		// Lag over 1200 seconds is not lag, is module without contacting data in several time.or with a 
		// 1200 sec is 20 min
		$server["lag"] = get_db_sql ("SELECT MAX(tagente_estado.last_execution_try - tagente_estado.current_interval)
			 FROM tagente_estado, tagente_modulo, tagente
			 WHERE tagente_estado.last_execution_try > 0
			 AND tagente_estado.running_by = ".$server["id_server"]."
			 AND tagente_modulo.id_agente = tagente.id_agente
			 AND tagente.disabled = 0
			 AND tagente_modulo.disabled = 0
			 AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			 AND (UNIX_TIMESTAMP() - tagente_estado.last_execution_try - tagente_estado.current_interval < 1200)");
			
		if (empty ($server["lag"])) {
			$server["lag"] = 0;
		} else {
			$server["lag"] = get_system_time () - $server["lag"];
		}
		
		//Push the raw data on the return stack
		$return[$server["id_server"]] = $server;
	}
	return $return;
}

/**
 * Get the number of all agent modules in the database
 *
 * @param mixed Array of integers with agent(s) id or a single agent id. Default
 * value will select all.
 *
 * @return int The number of agent modules
 */
function get_agent_modules_count ($id_agent = 0) {
	//Make sure we're all int's and filter out bad stuff
	$id_agent = safe_int ($id_agent, 1);
	
	if (empty ($id_agent)) {
		//If the array proved empty or the agent is less than 1 (eg. -1)
		$filter = '';
	} else {
		$filter = sprintf (" WHERE id_agente IN (%s)", implode (",", (array) $id_agent));
	}
	
	return (int) get_db_sql ("SELECT COUNT(*) FROM tagente_modulo".$filter);
}

/**
 * This function gets the agent group for a given agent module
 *
 * @param int The agent module id
 *
 * @return int The group id
 */
function get_agentmodule_group ($id_module) {
	$agent = (int) get_agentmodule_agent ((int) $id_module);
	return (int) get_agent_group ($agent);
}

/**
 * This function gets the group for a given agent
 *
 * @param int The agent id
 *
 * @return int The group id
 */
function get_agent_group ($id_agent) {
	return (int) get_db_value ('id_grupo', 'tagente', 'id_agente', (int) $id_agent);
}

/**
 * This function gets the group name for a given group id
 *
 * @param int The group id
 *
 * @return string The group name
 */
function get_group_name ($id_group) {
	return (string) get_db_value ('nombre', 'tgrupo', 'id_grupo', (int) $id_group);
}

/**
 * Gets all module groups. (General, Networking, System).
 *
 * Module groups are merely for sorting frontend
 *
 * @return array All module groups
 */
function get_modulegroups () {
	$result = get_db_all_fields_in_table ("tmodule_group");
	$return = array ();
	
	if (empty ($result)) {
		return $return;
	}
	
	foreach ($result as $modulegroup) {
		$return[$modulegroup["id_mg"]] = $modulegroup["name"];
	}
	
	return $return;
}

/**
 * Gets a modulegroup name based on the id
 *
 * @param int The id of the modulegroup
 *
 * @return string The modulegroup name
 */	
function get_modulegroup_name ($modulegroup_id) {
	return (string) get_db_value ('name', 'tmodule_group', 'id_mg', (int) $modulegroup_id);
}

/**
 * Inserts strings into database
 *
 * The number of values should be the same or a positive integer multiple as the number of rows
 * If you have an associate array (eg. array ("row1" => "value1")) you can use this function with ($table, array_keys ($array), $array) in it's options
 * All arrays and values should have been cleaned before passing. It's not neccessary to add quotes.
 *
 * @param string Table to insert into
 * @param mixed A single row or array of rows to insert to￼
 * @param mixed A single value or array of values to insert (can be a multiple amount of rows)
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function process_sql_insert ($table, $rows, $values) {
	if (empty ($rows) || empty ($values)) { //Empty rows or values not processed
		return false;
	}	
	
	$rows = (array) $rows; //Convert single strings to array
	$values = (array) $values;
	$row_count = count ($rows); //We're reusing so we put it in a variable
	$value_count = count ($values);

	if (($value_count % $row_count) != 0) { 
		//If values are not a clean multiple of rows, don't process
		return false;
	}
	
	$query = sprintf ("INSERT INTO `%s` ", $table);
	
	foreach ($rows as $idx => $row) { //Add ` to each row name
		if ($row[0] != '`') {
			$rows[$idx] = '`'.$row.'`';
		}
	}
	
	foreach ($values as $idx => $value) { //Add the correct escaping to values
		if ($value[0] == "'") {
			continue; //The value is already escaped
		}
		if ($value === NULL) {
			$values[$idx] = (string) "NULL";
		} elseif (is_numeric ($value)) {
			continue; //The value doesn't need esaped.
		} elseif (is_bool ($value)) {
			$values[$idx] = (int) $value; //SQL doesn't know boolean so we convert
		} else {
			$values[$idx] = sprintf ("'%s'", $value);
		}
	}
	
	$query .= "(".implode (", ", $rows).")";
	$query .= " VALUES ";
	
	for ($i = 0; $i < ($value_count / $row_count); $i++) {
		//For the times the values are multiplied
		$array = array_slice ($values, $i * $row_count, ($i+1) * $row_count);
		$query .= "(".implode (",", $array).")";
		if ($i != ($value_count / $row_count) - 1) {
			$query .= ",";
		}
	}
	
	return process_sql ($query);
}

/**
 * Inserts strings into database
 *
 * All values should be cleaned before passing. Quoting isn't necessary.
 * Examples:
 *
 * <code>
process_sql_update ('table', array ('field' => 1), array ('id' => $id));
process_sql_update ('table', array ('field' => 1), array ('id' => $id, 'name' => $name));
process_sql_update ('table', array ('field' => 1), array ('id' => $id, 'name' => $name), 'OR');
process_sql_update ('table', array ('field' => 2), 'id in (1, 2, 3) OR id > 10');
 * <code>
 *
 * @param string Table to insert into
 * @param array An associative array of values to update
 * @param mixed An associative array of field and value matches. Will be joined
 * with operator specified by $where_join. A custom string can also be provided.
 * If nothing is provided, the update will affect all rows.
 * @param string When a $where parameter is given, this will work as the glue
 * between the fields. "AND" operator will be use by default. Other values might
 * be "OR", "AND NOT", "XOR"
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function process_sql_update ($table, $values, $where = false, $where_join = 'AND') {
	$query = sprintf ("UPDATE `%s` SET ", $table);
	
	$i = 1;
	$max = count ($values);
	foreach ($values as $field => $value) {
		if ($field[0] != "`") {
			$field = "`".$field."`";
		}
		
		if (is_null ($value)) {
			$query .= sprintf ("%s = NULL", $field);
		} elseif (is_int ($value) || is_bool ($value)) {
			$query .= sprintf ("%s = %d", $field, $value);
		} else if (is_float ($value) || is_double ($value)) {
			$query .= sprintf ("%s = %f", $field, $value);
		} else {
			$query .= sprintf ("%s = '%s'", $field, $value);
		}
		
		if ($i < $max) {
			$query .= ",";
		}
		$i++;
	}
	
	if ($where) {
		$query .= ' WHERE ';
		if (is_string ($where)) {
			/* FIXME: Should we clean the string for sanity? */
			$query .= $where;
		} else if (is_array ($where)) {
			$i = 1;
			$max = count ($where);
			foreach ($where as $field => $value) {
				if ($field[0] != "`") {
					$field = "`".$field."`";
				}
				
				if (is_null ($value)) {
					$query .= sprintf ("%s IS NULL", $field);
				} elseif (is_int ($value) || is_bool ($value)) {
					$query .= sprintf ("%s = %d", $field, $value);
				} else if (is_float ($value) || is_double ($value)) {
					$query .= sprintf ("%s = %f", $field, $value);
				} else {
					$query .= sprintf ("%s = '%s'", $field, $value);
				}
		
				if ($i < $max) {
					$query .= $where_join;
				}
				$i++;
			}
		}
	}
	
	return process_sql ($query);
}
?>
