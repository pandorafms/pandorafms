<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, <slerena@gmail.com>
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


/** 
 * Check if login session variables are set.
 *
 * It will stop the execution if those variables were not set
 * 
 * @return 0 on success
 */
function check_login () { 
	global $config;
	if (!isset($config["homedir"])){
		// No exists $config. Exit inmediatly
		include ("general/noaccess.php");
		exit;
	}
	if ((isset($_SESSION["id_usuario"])) AND ($_SESSION["id_usuario"] != "")) { 
		$id = $_SESSION["id_usuario"];
		$query1="SELECT id_usuario FROM tusuario WHERE id_usuario= '$id'";
		$resq1 = mysql_query($query1);
		$rowdup = mysql_fetch_array($resq1);
		$nombre = $rowdup[0];
		if ( $id == $nombre ){
			return 0;
		}
	}
	audit_db("N/A", getenv("REMOTE_ADDR"), "No session", "Trying to access without a valid session");
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
 * @param id_user User id to check
 * @param id_group Agents group id to check access
 * @param access Access privilege to check
 * 
 * @return 1 if the user has privileges, 0 if not.
 */
function give_acl ($id_user, $id_group, $access) {
	// IF user is level = 1 then always return 1
	// Access can be:
	/*	
		IR - Incident Read
		IW - Incident Write
		IM - Incident Management
		AR - Agent Read
		AW - Agent Write
		LW - Alert Write
		UM - User Management
		DM - DB Management
		LM - Alert Management
		PM - Pandora Management
	*/
	
	// Conexion con la base Datos 
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id_user."'";
	$res=mysql_query($query1);
	$row=mysql_fetch_array($res);
	if ($row["nivel"] == 1)
		return 1;
	if ($id_group == 0) // Group doesnt matter, any group, for check permission to do at least an action in a group
		$query1="SELECT * FROM tusuario_perfil WHERE id_usuario = '".$id_user."'";	// GroupID = 0, group doesnt matter (use with caution!)
	else
		$query1="SELECT * FROM tusuario_perfil WHERE id_usuario = '".$id_user."' and ( id_grupo =".$id_group." OR id_grupo = 1)";	// GroupID = 1 ALL groups      
	$resq1=mysql_query($query1);  
	$result = 0; 
	while ($rowdup=mysql_fetch_array($resq1)){
		$id_perfil=$rowdup["id_perfil"];
		// For each profile for this pair of group and user do...
		$query2="SELECT * FROM tperfil WHERE id_perfil = ".$id_perfil;    
		$resq2=mysql_query($query2);  
		if ($rowq2=mysql_fetch_array($resq2)){
			switch ($access) {
			case "IR":
				$result = $result + $rowq2["incident_view"];
				
				break;
			case "IW":
				$result = $result + $rowq2["incident_edit"];
				
				break;
			case "IM":
				$result = $result + $rowq2["incident_management"];
				
				break;
			case "AR":
				$result = $result + $rowq2["agent_view"];
				
				break;
			case "AW":
				$result = $result + $rowq2["agent_edit"];
				
				break;
			case "LW":
				$result = $result + $rowq2["alert_edit"];
				
				break;
			case "LM":
				$result = $result + $rowq2["alert_management"];
				
				break;
			case "PM":
				$result = $result + $rowq2["pandora_management"];
				
				break;
			case "DM":
				$result = $result + $rowq2["db_management"];
				
				break;
			case "UM":
				$result = $result + $rowq2["user_management"];
				
				break;
			}
		} 
	}
	if ($result > 1)
		$result = 1;
        return $result; 
} 

/** 
 * Adds an audit log entry.
 * 
 * @param id User id that makes the incident
 * @param ip Client IP who makes the incident
 * @param accion Action description
 * @param descripcion Long action description
 */
function audit_db ($id, $ip, $accion, $descripcion){
	require("config.php");
	$today=date('Y-m-d H:i:s');
	$utimestamp = time();
	$sql1='INSERT INTO tsesion (ID_usuario, accion, fecha, IP_origen,descripcion, utimestamp) VALUES ("'.$id.'","'.$accion.'","'.$today.'","'.$ip.'","'.$descripcion.'", '.$utimestamp.')';
	$result=mysql_query($sql1);
}

/**
 * Log in a user into Pandora.
 *
 * @param id_user User id
 * @param ip Client user IP address.
 */
function logon_db ($id_user, $ip) {
	require  ("config.php");
	audit_db ($id_user, $ip, "Logon", "Logged in");
	// Update last registry of user to get last logon
	$sql = 'UPDATE tusuario fecha_registro = $today WHERE id_usuario = "$id_user"';
	$result = mysql_query ($sql);
}

/**
 * Log out a user into Pandora.
 *
 * @param id_user User id
 * @param ip Client user IP address.
 */
function logoff_db ($id_user, $ip) {
	require ("config.php");
	audit_db ($id_user, $ip, "Logoff", "Logged out");
}

/**
 * Get profile name from id.
 * 
 * @param id_profile Id profile in tperfil
 * 
 * @return Profile name of the given id
 */
function dame_perfil ($id_profile) {
	return (string) get_db_value ('name', 'tperfil', 'id_perfil', (int) $id_profile);
}

// ---------------------------------------------------------------
// Returns disabled from a given group_id
// ---------------------------------------------------------------

function give_disabled_group ($id_group) {
	return (bool) get_db_value ('disabled', 'tgrupo', 'id_grupo', (int) $id_group);
}

/**
 * Get all the agents in a group.
 *
 * @param $id_group Group id to get all agents.
 *
 * @return An array with all agents in the group.
 */
function get_agents_in_group ($id_group) {
	return get_db_all_rows_field_filter ('tagente', 'id_grupo', (int) $id_group);
}

/**
 * Get all the modules in an agent.
 *
 * @param $id_agent Agent id to get all modules.
 *
 * @return An array with all modules in the agent.
 */
function get_modules_in_agent ($id_agent) {
	return get_db_all_rows_field_filter ('tagente_modulo', 'id_agente', (int) $id_agent);
}

/**
 * Get all the simple alerts of an agent.
 *
 * @param $id_agent Agent id to get all simple alerts.
 *
 * @return An array with all simple alerts defined for an agent.
 */
function get_simple_alerts_in_agent ($id_agent) {
	$sql = sprintf ('SELECT talerta_agente_modulo.*
			FROM talerta_agente_modulo, tagente_modulo
			WHERE talerta_agente_modulo.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND tagente_modulo.id_agente = %d', $id_agent);
	return get_db_all_rows_sqlfree ($sql);
}

/**
 * Get all the combined alerts of an agent.
 *
 * @param $id_agent Agent id to get all combined alerts.
 *
 * @return An array with all combined alerts defined for an agent.
 */
function get_combined_alerts_in_agent ($id_agent) {
	return get_db_all_rows_field_filter ('talerta_agente_modulo', 'id_agent', (int) $id_agent);
}

/**
 * Get all the alerts of an agent, simple and combined.
 *
 * @param $id_agent Agent id to get all alerts.
 *
 * @return An array with all alerts defined for an agent.
 */
function get_alerts_in_agent ($id_agent) {
	$simple_alerts = get_simple_alerts_in_agent ($id_agent);
	$combined_alerts = get_combined_alerts_in_agent ($id_agent);
	
	return array_merge ($simple_alerts, $combined_alerts);
}

/**
 * Get a list of the reports the user can view.
 *
 * A user can view a report by two ways:
 *  - The user created the report (id_user field in treport)
 *  - The report is not private and the user has reading privileges on 
 *    the group associated to the report
 *
 * @param $id_user User id to get the reports.
 *
 * @return An array with all the reports the user can view.
 */
function get_reports ($id_user) {
	$user_reports = array ();
	$all_reports = get_db_all_rows_in_table ('treport');
	if (sizeof ($all_reports) == 0) {
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
 * Get group name from group.
 * 
 * @param id_group Id group to get the name.
 * 
 * @return The name of the given group
 */
function dame_grupo ($id_group) {
	return (string) get_db_value ('nombre', 'tgrupo', 'id_grupo', (int) $id_group);
}

/** 
 * Get group icon from group.
 * 
 * @param id_group Id group to get the icon
 * 
 * @return Icon path of the given group
 */
function dame_grupo_icono ($id_group) {
	return (string) get_db_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
}

/** 
 * Get agent id from an agent name.
 * 
 * @param agent_name Agent name to get its id.
 * 
 * @return Id from the agent of the given name.
 */
function dame_agente_id ($agent_name) {
	return (int) get_db_value ('id_agente', 'tagente', 'nombre', $agent_name);
}

/** 
 * Get user id of a note.
 * 
 * @param id_note Note id.
 * 
 * @return User id of the given note.
 */
function give_note_author ($id_note) {
	return (int) get_db_value ('id_usuario', 'tnota', 'id_nota', (int) $id_note);
}

/** 
 * Get description of an event.
 * 
 * @param id_event Event id.
 * 
 * @return Description of the given event.
 */
function return_event_description ($id_event) {
	return (string) get_db_value ('evento', 'tevento', 'id_evento', (int) $id_event);
}

/** 
 * Get group id of an event.
 * 
 * @param id_event Event id
 * 
 * @return Group id of the given event.
 */
function gime_idgroup_from_idevent ($id_event) {
	return (int) get_db_value ('id_grupo', 'tevento', 'id_evento', (int) $id_event);
}

/** 
 * Get name of an agent.
 * 
 * @param id_agente Agent id.
 * 
 * @return Name of the given agent.
 */
function dame_nombre_agente ($id_agente) {
	return (string) get_db_value ('nombre', 'tagente', 'id_agente', (int) $id_agente);
}

/** 
 * Get password of an user.
 * 
 * @param id_usuario User id.
 * 
 * @return Password of an user.
 */
function get_user_password ($id_usuario) {
	return (string) get_db_value ('password', 'tusuario', 'id_usuario', (int) $id_usuario);
}

/** 
 * Get name of an alert
 * 
 * @param id_alert Alert id.
 * 
 * @return Name of the alert.
 */
function dame_nombre_alerta ($id_alert) {
	return (string) get_db_value ('nombre', 'talerta', 'id_alerta', (int) $id_alert);
}

/** 
 * Get name of a module group.
 * 
 * @param id_module_group Module group id.
 * 
 * @return Name of the given module group.
 */
function dame_nombre_grupomodulo ($id_module_group) {
	return (string) get_db_value ('name', 'tmodule_group', 'id_mg', (int) $id_module_group);
}

// --------------------------------------------------------------- 
// Returns name of a export server
// --------------------------------------------------------------- 

function dame_nombre_servidorexportacion ($id_server) {
	return (string) get_db_value ('name', 'tserver_export', 'id', (int) $id_server);
}

// --------------------------------------------------------------- 
// Returns name of a plugin module
// --------------------------------------------------------------- 

function dame_nombre_pluginid ($id_plugin) {
	return (string) get_db_value ('name', 'tplugin', 'id', (int) $id_plugin);
}

// --------------------------------------------------------------- 
// Returns id of a moduletype
// --------------------------------------------------------------- 

function giveme_module_type ($id_type) {
	return (string) get_db_value ('nombre', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

// --------------------------------------------------------------- 
// Returns agent name, given a ID of agente_module table
// --------------------------------------------------------------- 

function dame_nombre_agente_agentemodulo ($id_agente_modulo) {
	$id_agent = get_db_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agente_modulo);
	if ($id_agent)
		return dame_nombre_agente ($id_agent);
	return '';
}

// --------------------------------------------------------------- 
// Return agent module name, given a ID of agente_module table
// --------------------------------------------------------------- 
function dame_nombre_modulo_agentemodulo ($id_agente_modulo) {
	return (string) get_db_value ('nombre', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
}


// --------------------------------------------------------------- 
// Return agent module, given a ID of agente_module table
// --------------------------------------------------------------- 

function dame_id_tipo_modulo_agentemodulo ($id_agente_modulo) {
	return (int) get_db_value ('id_tipo_modulo', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
}

// --------------------------------------------------------------- 
// Returns name of the user when given ID
// --------------------------------------------------------------- 

function dame_nombre_real ($id_user) {
	return (string) get_db_value ('nombre_real', 'tusuario', 'id_usuario', (int) $id_user);
}

/**
 * Get all the times a monitor went down during a period.
 * 
 * @param $id_agent_module Agent module of the monitor.
 * @param $period Period timed to check from date
 * @param $date Date to check (now by default)
 *
 * @return The number of times a monitor went down.
 */
function get_monitor_downs_in_period ($id_agent_module, $period, $date = 0) {
	if (!$date)
		$date = time ();
	$datelimit = $date - $period;
	$sql = sprintf ('SELECT COUNT(*) FROM tevento WHERE
			event_type = "monitor_down" 
			AND id_agentmodule = %d
			AND utimestamp > %d AND utimestamp <= %d',
			$id_agent_module, $datelimit, $date);
	$down = get_db_sql ($sql);
	return $down;
}

/**
 * Get the last time a monitor went down during a period.
 * 
 * @param $id_agent_module Agent module of the monitor.
 * @param $period Period timed to check from date
 * @param $date Date to check (now by default)
 *
 * @return The last time a monitor went down.
 */
function get_monitor_last_down_timestamp_in_period ($id_agent_module, $period, $date = 0) {
	if (!$date)
		$date = time ();
	$datelimit = $date - $period;
	$sql = sprintf ('SELECT MAX(timestamp) FROM tevento WHERE
			event_type = "monitor_down" 
			AND id_agentmodule = %d
			AND utimestamp > %d AND utimestamp <= %d',
			$id_agent_module, $datelimit, $date);
	$timestamp = get_db_sql ($sql);
	return $timestamp;
}

/**
 * Get all the times an alerts fired during a period.
 * 
 * @param $id_agent_module Agent module of the alert.
 * @param $period Period timed to check from date
 * @param $date Date to check (now by default)
 *
 * @return The number of times an alert fired.
 */
function get_alert_fires_in_period ($id_agent_module, $period, $date = 0) {
	if (!$date)
		$date = time ();
	$datelimit = $date - $period;
	$sql = sprintf ('SELECT COUNT(*) FROM tevento WHERE
			event_type = "alert_fired" 
			AND id_agentmodule = %d
			AND utimestamp > %d AND utimestamp <= %d',
			$id_agent_module, $datelimit, $date);
	$down = get_db_sql ($sql);
	return (int) $down;
}

/**
 * Get the last time an alert fired during a period.
 * 
 * @param $id_agent_module Agent module of the monitor.
 * @param $period Period timed to check from date
 * @param $date Date to check (now by default)
 *
 * @return The last time an alert fired.
 */
function get_alert_last_fire_timestamp_in_period ($id_agent_module, $period, $date = 0) {
	if (!$date)
		$date = time ();
	$datelimit = $date - $period;
	$sql = sprintf ('SELECT MAX(timestamp) FROM tevento WHERE
			event_type = "alert_fired" 
			AND id_agentmodule = %d
			AND utimestamp > %d AND utimestamp <= %d',
			$id_agent_module, $datelimit, $date);
	$timestamp = get_db_sql ($sql);
	return $timestamp;
}

// --------------------------------------------------------------- 
// This function returns ID of user who has created incident
// --------------------------------------------------------------- 

function give_incident_author ($id_incident) {
	return (string) get_db_value ('id_usuario', 'tincidencia', 'id_incidencia', (int) $id_incident);
}

// --------------------------------------------------------------- 
// This function returns name of server
// --------------------------------------------------------------- 

function give_server_name($id_server){
	require("include/config.php");
	$query1="SELECT * FROM tserver WHERE id_server  = '".$id_server."'";
	$resq1=mysql_query($query1);  
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["name"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Return name of a module type when given ID
// --------------------------------------------------------------- 

function dame_nombre_tipo_modulo ($id){
	require("config.php");
	$query1="SELECT * FROM ttipo_modulo WHERE id_tipo =".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$pro=$rowdup["nombre"];
	}
	else $pro = "";
	return $pro;
} 

// --------------------------------------------------------------- 
// Return name of a group when given ID
// --------------------------------------------------------------- 

function dame_nombre_grupo ($id){
	require ("config.php");
	$query1 = "SELECT * FROM tgrupo WHERE id_grupo = ".$id;
	$resq1 = mysql_query($query1);
	if ($rowdup = mysql_fetch_array ($resq1))
		$pro = $rowdup["nombre"];
	else
		$pro = "";
	return $pro;
} 

// --------------------------------------------------------------- 
// This function return group_id given an agent_id
// --------------------------------------------------------------- 

function dame_id_grupo($id_agente){
	require("config.php");
	$query1="SELECT * FROM tagente WHERE id_agente =".$id_agente;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$pro=$rowdup["id_grupo"];
	}
	else $pro = "";
	return $pro;
} 


// --------------------------------------------------------------- 
// Returns number of notes from a given incident
// --------------------------------------------------------------- 

function dame_numero_notas($id){
	require("config.php"); 
	$query1="select COUNT(*) from tnota_inc WHERE id_incidencia =".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$pro=$rowdup["COUNT(*)"]; 
	}
	else $pro = "0";
	return $pro;
}


// --------------------------------------------------------------- 
// Returns number of registries from table of data agents
// --------------------------------------------------------------- 

function dame_numero_datos(){
	require("config.php");
	$query1="select COUNT(*) from tagente_datos";
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$pro=$rowdup["COUNT(*)"];
	}
	else $pro = "0";
	return $pro; 
}


// --------------------------------------------------------------- 
// Returns string packet type given ID
// --------------------------------------------------------------- 

function dame_generic_string_data($id){ 
	// Conexion con la base Datos 
	require("config.php");
	$query1="SELECT * FROM tagente_datos_string WHERE id_tagente_datos_string = ".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$pro=$rowdup["datos"];
	}
	return $pro;
}

// --------------------------------------------------------------- 
// Delete incident given its id and all its notes
// --------------------------------------------------------------- 


function borrar_incidencia($id_inc){
	require("config.php");
	$sql1="DELETE FROM tincidencia WHERE id_incidencia = ".$id_inc;
	$result=mysql_query($sql1);
	$sql3="SELECT * FROM tnota_inc WHERE id_incidencia = ".$id_inc;
	$res2=mysql_query($sql3);
	while ($row2=mysql_fetch_array($res2)){
		// Delete all note ID related in table
		$sql4 = "DELETE FROM tnota WHERE id_nota = ".$row2["id_nota"];
		$result4 = mysql_query($sql4);
	}
	$sql6="DELETE FROM tnota_inc WHERE id_incidencia = ".$id_inc;
	$result6=mysql_query($sql6);
	// Delete attachments
	$sql1="SELECT * FROM tattachment WHERE id_incidencia = ".$id_inc;
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		// Unlink all attached files for this incident
		$file_id = $row["id_attachment"];
		$filename = $row["filename"];
		unlink ($attachment_store."attachment/pand".$file_id."_".$filename);
	}
	$sql1="DELETE FROM tattachment WHERE id_incidencia = ".$id_inc;
	$result=mysql_query($sql1);
}

// --------------------------------------------------------------- 
// Return SO name given its ID
// --------------------------------------------------------------- 

function dame_so_name($id){
	require("config.php");
	$query1="SELECT * FROM tconfig_os WHERE id_os = ".$id;
	$resq1=mysql_query($query1);  
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["name"];
	else
		$pro = "";
	return $pro;
}
// --------------------------------------------------------------- 
//  Update "contact" field in User table for username $nick
// --------------------------------------------------------------- 

function update_user_contact($nick){	// Sophus simply insist too much in this function... ;)
	require("config.php");
	$today=date("Y-m-d H:i:s",time());
	$query1="UPDATE tusuario set fecha_registro ='".$today."' WHERE id_usuario = '".$nick."'";
	$resq1=mysql_query($query1);
}

// --------------------------------------------------------------- 
// Return SO iconname given its ID
// --------------------------------------------------------------- 

function dame_so_icon($id){ 
	require("config.php");
	$query1="SELECT * FROM tconfig_os WHERE id_os = ".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["icon_name"];
	else
		$pro = "";
	return $pro;
}


// --------------------------------------------------------------- 
// Return email of a user given ID 
// --------------------------------------------------------------- 

function dame_email($id){ 
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario =".$id;
	$resq1=mysql_query($query1);
	$rowdup=mysql_fetch_array($resq1);
	$nombre=$rowdup["direccion"];
	return $nombre;
} 


// ---------------------------------------------------------------
// Returns Admin value (0 no admin, 1 admin)
// ---------------------------------------------------------------

function dame_admin($id){
        $admin = get_db_sql ("SELECT * FROM tusuario WHERE id_usuario ='$id'", "nivel");
	return $admin;
}


// Wrapper function since we change all functions to english
function comprueba_login() { 
	return check_login ();
}

// --------------------------------------------------------------- 
// Gives error message and stops execution if user 
//doesn't have an open session and this session is from an administrator
// --------------------------------------------------------------- 

function check_admin () {
	if (isset($_SESSION["id_usuario"])){
		$iduser=$_SESSION['id_usuario'];
		if (dame_admin($iduser)==1){
			$id = $_SESSION["id_usuario"];
			require("config.php");
			$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";
			$resq1=mysql_query($query1);
			$rowdup=mysql_fetch_array($resq1);
			$nombre=$rowdup["id_usuario"];
			$nivel=$rowdup["nivel"];
			if (( $id == $nombre) and ($nivel ==1))
				return 0;
		}
	}
	require("../general/no_access.php");
	return 1;
}

function comprueba_admin() {
	return check_admin ();
}

// ---------------------------------------------------------------
// Returns number of alerts fired by this agent
// ---------------------------------------------------------------

function check_alert_fired($id_agente){
	require("config.php");
	$query1="SELECT * FROM tagente_modulo WHERE id_agente ='".$id_agente."'";   
	$rowdup=mysql_query($query1);
	while ($data=mysql_fetch_array($rowdup)){
		$query2="SELECT COUNT(*) FROM talerta_agente_modulo WHERE times_fired > 0 AND id_agente_modulo =".$data["id_agente_modulo"];
		$rowdup2=mysql_query($query2);
		$data2=mysql_fetch_array($rowdup2);
		if ($data2[0] > 0)
			return 1;
	}
	return 0;
}

// ---------------------------------------------------------------
// 0 if it doesn't exist, 1 if it does, when given email
// ---------------------------------------------------------------

function existe($id){
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";   
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		if ($rowdup=mysql_fetch_array($resq1)){ 
			return 1; 
		}
		else {
			return 0; 
		}
	} else { return 0 ; }
}

// --------------------------------------------------------------- 
// event_insert - Insert generic event in eventable
// --------------------------------------------------------------- 

function event_insert($evento, $id_grupo, $id_agente, $status=0, $id_usuario='', $event_type = "unknown", $priority = 0, $id_agent_module, $id_aam){
	require("config.php");
	$today=date('Y-m-d H:i:s');
	$utimestamp = time();

	$sql1='INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, estado, utimestamp, id_usuario, event_type, criticity, id_agentmodule, id_alert_am) VALUES ('.$id_agente.','.$id_grupo.',"'.$evento.'","'.$today.'",'.$status.', '.$utimestamp.', "'.$id_usuario.'", "'.$event_type.'", '.$priority.', '.$id_agent_module.', '.$id_aam.')';

	$result=mysql_query($sql1);

}

// --------------------------------------------------------------- 
// Return module interval or agent interval if first not defined
// ---------------------------------------------------------------

function give_moduleinterval($id_agentmodule){ 
	require("config.php");
	$query1="SELECT * FROM tagente_modulo WHERE id_agente_modulo = ".$id_agentmodule;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		if ($rowdup["module_interval"] == 0){ // no module interval defined
			$query2="SELECT * FROM tagente WHERE id_agente = ".$rowdup["id_agente"];
			$resq2=mysql_query($query2);
			if ($rowdup2=mysql_fetch_array($resq2)){
				$interval=$rowdup2["intervalo"];
			}
		} else {
			$interval=$rowdup["module_interval"];
		}
	}
	return $interval;
}

// --------------------------------------------------------------- 
// Return agent interval 
// ---------------------------------------------------------------

function give_agentinterval($id_agent){ 
	require("config.php");
	$query1="SELECT * FROM tagente WHERE id_agente = ".$id_agent;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$interval=$rowdup["intervalo"];
	}
	return $interval;
}

// --------------------------------------------------------------- 
// Return agent_module flag (for network push modules)
// ---------------------------------------------------------------

function give_agentmodule_flag($id_agent_module){ 
	require("config.php");
	$query1="SELECT * FROM tagente_modulo WHERE id_agente_modulo = ".$id_agent_module;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$interval=$rowdup["flag"];
	}
	return $interval;
}

// ---------------------------------------------------------------------- 
// Returns a combo with the groups and defines an array 
// to put all groups with Agent Read permission
// ----------------------------------------------------------------------
function list_group ($id_user, $show_all = 1){
	$mis_grupos=array (); // Define array mis_grupos to put here all groups with Agent Read permission
	$sql='SELECT id_grupo, nombre FROM tgrupo';
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		if ($row["id_grupo"] != 0){
			if (give_acl($id_user,$row["id_grupo"], "AR") == 1){
				if (($row["id_grupo"] != 1) OR ($show_all == 1)){
					array_push ($mis_grupos, $row["id_grupo"]); //Put in  an array all the groups the user belongs
					echo "<option value='".$row["id_grupo"]."'>".
					$row["nombre"]."</option>";
				}
			}
		}
	}
	return ($mis_grupos);
}

// ---------------------------------------------------------------------- 
// Defines an array 
// to put all groups with Agent Read permission
// ----------------------------------------------------------------------

function list_group2 ($id_user){
	$mis_grupos[]=""; // Define array mis_grupos to put here all groups with Agent Read permission
	$sql='SELECT id_grupo FROM tgrupo';
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		if (give_acl($id_user,$row["id_grupo"], "AR") == 1){
			$mis_grupos[]=$row["id_grupo"]; //Put in  an array all the groups the user belongs
		}
	}
	return ($mis_grupos);
}

// --------------------------------------------------------------- 
// Return Group iconname given its name
// --------------------------------------------------------------- 

function show_icon_group($id_group){ 
	$sql="SELECT icon FROM tgrupo WHERE id_grupo='$id_group'";
	$result=mysql_query($sql);
	if ($row=mysql_fetch_array($result))
		$pro=$row["icon"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Return Type iconname given its name
// --------------------------------------------------------------- 

function show_icon_type($id_tipo){ 
	$sql="SELECT id_tipo, icon FROM ttipo_modulo WHERE id_tipo='$id_tipo'";
	$result=mysql_query($sql);
	if ($row=mysql_fetch_array($result))
		$pro=$row["icon"];
	else
		$pro = "";
	return $pro;
}

/**
 * Return a string containing image tag for a given target id (server)
 *
 * @param int Server type id
 * @return string Fully formatted  IMG HTML tag with icon
 */

function show_server_type ($id){ 
    global $config;
    switch ($id) {
        case 1: return '<img src="'.$config["homeurl"].'/images/data.png" title="Pandora FMS Data server">';
                break;
        case 2: return '<img src="'.$config["homeurl"].'/images/network.png" title="Pandora FMS Network server">';
                break;
        case 4: return '<img src="'.$config["homeurl"].'/images/plugin.png" title="Pandora FMS Plugin server">';
                break;
        case 5: return '<img src="'.$config["homeurl"].'/images/chart_bar.png" title="Pandora FMS Prediction server">';
                break;
        case 6: return '<img src="'.$config["homeurl"].'/images/wmi.png" title="Pandora FMS WMI server">';
                break;
        default: return "--";
    }
}

// ---------------------------------------------------------------
// Return all childs groups of a given id_group inside array $child
// ---------------------------------------------------------------

function give_groupchild($id_group, &$child){
        // Conexion con la base Datos 
        $query1="select * from tgrupo where parent = ".$id_group;
        $resq1=mysql_query($query1);  
        while ($resq1 != NULL && $rowdup=mysql_fetch_array($resq1)){
        	$child[]=$rowdup["id_grupo"];
        }
}

// ---------------------------------------------------------------
// Return true (1) if agent belongs to given group or one of this childs
// ---------------------------------------------------------------

function agent_belong_group($id_agent, $id_group){ 
        // Conexion con la base Datos 
	$child[] = "";
	$child[] = $id_group;
	give_groupchild ($id_group, $child);
	$id_agent_group = give_group_id ($id_agent);
	return in_array ($child, $id_agent_group);
}

// ---------------------------------------------------------------
// Return true (1) if given group (a) belongs to given groupset
// ---------------------------------------------------------------

function group_belong_group($id_group_a, $id_groupset){
        // Conexion con la base Datos 
	$childgroup[] = "";
	if ($id_group_a == $id_groupset)
		return 1;
	give_groupchild($id_groupset, $childgroup);
	foreach ($childgroup as $key => $value){
		if (($value != $id_groupset) AND
		    (group_belong_group($id_group_a, $value) == 1))
			return 1;
  	}
	return in_array ($childgroup, $id_group_a);
}

// ---------------------------------------------------------------
// Return category name
// ---------------------------------------------------------------
function give_modulecategory_name ($value) {
	require("config.php");
	require ("include/languages/language_".$config["language"].".php");
	switch ($value) {
	   case 0: return $lang_label["cat_0"];
	   	break;
	   case 1: return $lang_label["cat_1"];
	   	break;
	   case 2: return $lang_label["cat_2"];
	   	break;
	   case 3: return $lang_label["cat_3"];
	   	break;
	}
	return $lang_label["unknown"]; 
}

// --------------------------------------------------------------- 
// Return network component group name given its ID
// --------------------------------------------------------------- 

function give_network_component_group_name ($id){
	require("config.php");
	$query1="SELECT * FROM tnetwork_component_group WHERE id_sg= ".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["name"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Return network profile name name given its ID
// --------------------------------------------------------------- 

function give_network_profile_name ($id_np){
	require("config.php");
	$query1="SELECT * FROM tnetwork_profile WHERE id_np= ".$id_np;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["name"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Associate IP address to an agent
// --------------------------------------------------------------- 

function agent_add_address ($id_agent, $ip_address) {
	require("config.php");
	$address_exist = 0;
	$id_address =-1;
	$address_attached = 0;

	// Check if already is attached to agent
	$query1="SELECT * FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
			AND ip = '$ip_address'
			AND id_agent = $id_agent";
	if ($resq1=mysql_query($query1)){
		if ($rowdup=mysql_fetch_array($resq1)){
			$address_attached = 1;
		}
	}
	if ($address_attached == 1)
		return;
	// Look for a record with this IP Address
	$query1="SELECT * FROM taddress WHERE ip = '$ip_address'";
	if ($resq1=mysql_query($query1)){
		if ($rowdup=mysql_fetch_array($resq1)){
			$id_address = $rowdup["id_a"];
			$address_exist = 1;
		}
	}

	if ($address_exist == 0){
		// Create IP address in tadress table
		$query = "INSERT INTO taddress
			  	(ip) VALUES
			  	('$ip_address')";
		$res = mysql_query ($query);
		$id_address = mysql_insert_id ();
	}
	// Add address to agent
	$query = "INSERT INTO taddress_agent
			(id_a, id_agent) VALUES
			($id_address,$id_agent)";
	$res = mysql_query ($query);
	
	// Change main address in agent to whis one
	/* Not needed, configurar_agente does automatically on every update
	$query = "UPDATE tagente 
		  	(direccion) VALUES
			($ip_address)
			WHERE id_agente = $id_agent ";
	$res = mysql_query ($query);
	*/
}

// --------------------------------------------------------------- 
// De-associate IP address to an agent (delete)
// --------------------------------------------------------------- 

function agent_delete_address ($id_agent, $ip_address) {
	$address_exist = 0;
	$id_address =-1;
	$query1 = "SELECT * FROM taddress_agent, taddress
            WHERE taddress_agent.id_a = taddress.id_a
            AND ip = '$ip_address'
            AND id_agent = $id_agent";
	if ($resq1 = mysql_query($query1)){
		$rowdup = mysql_fetch_array($resq1);
		$id_ag = $rowdup["id_ag"];
		$id_a = $rowdup["id_a"];
		$sql_3 = "DELETE FROM taddress_agent WHERE id_ag = $id_ag";	
		$result_3 = mysql_query($sql_3);
	}
	// Need to change main address ? 
	if (give_agent_address ($id_agent) == $ip_address){
		$new_ip = give_agent_address_from_list ($id_agent);
		// Change main address in agent to whis one
		$query = "UPDATE tagente 
				(direccion) VALUES
				($new_ip)
				WHERE id_agente = $id_agent ";
		$res = mysql_query ($query);
	}

}

// --------------------------------------------------------------- 
// Returns (main) agent address given id
// --------------------------------------------------------------- 

function give_agent_address ($id_agent){
	$query1 = "SELECT * FROM tagente WHERE id_agente = $id_agent";
	$resq1 = mysql_query($query1);
	if ($rowdup = mysql_fetch_array($resq1))
		$pro = $rowdup["direccion"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Returns the first agent address given id taken from associated addresses
// --------------------------------------------------------------- 

function give_agent_address_from_list ($id_agent){
	$query1="SELECT * FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
		AND id_agent = $id_agent";
	if ($resq1=mysql_query($query1)){
		$rowdup=mysql_fetch_array($resq1);
		$pro=$rowdup["ip"];
	}
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Returns agent id given name of agent
// --------------------------------------------------------------- 

function give_agent_id_from_module_id ($id_module){
	$query1="SELECT * FROM tagente_modulo WHERE id_agente_modulo = $id_module";
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["id_agente"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Generic access to a field ($field) given a table
// --------------------------------------------------------------- 

function get_db_value ($field, $table, $field_search, $condition){
	if (is_int ($condition)) {
		$sql = sprintf ('SELECT %s FROM %s WHERE %s = %d', $field, $table, $field_search, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ('SELECT %s FROM %s WHERE %s = %f', $field, $table, $field_search, $condition);
	} else {
		$sql = sprintf ('SELECT %s FROM %s WHERE %s = "%s"', $field, $table, $field_search, $condition);
	}
	
	$result = mysql_query ($sql);
	if (! $result) {
		echo '<strong>Error:</strong> get_db_value("'.$sql.'") :'. mysql_error ().'<br />';
		return NULL;
	}
	if ($row = mysql_fetch_array ($result))
		return $row[0];
	
	return NULL;
}

// --------------------------------------------------------------- 
// Wrapper for old function name. Should be upgraded/renamed in next versions
// --------------------------------------------------------------- 
function give_db_value ($field, $table, $field_search, $condition) {
    return get_db_value ($field, $table, $field_search, $condition);
}

function get_db_row_sql ($sql) {
	$result = mysql_query ($sql);
	if (! $result) {
		echo '<strong>Error:</strong> get_db_row("'.$sql.'") :'. mysql_error ().'<br />';
		return NULL;
	}
	if ($row = mysql_fetch_array ($result))
		return $row;
	
	return NULL;
}


function get_db_row ($table, $field_search, $condition) {
	global $config;
	
	if (is_int ($condition)) {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = %d', $table, $field_search, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = %f', $table, $field_search, $condition);
	} else {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = "%s"', $table, $field_search, $condition);
	}
	
	return get_db_row_sql ($sql);
}

// --------------------------------------------------------------- 
// Generic access to single field using a free SQL sentence
// --------------------------------------------------------------- 

function get_db_sql ($sql, $field = 0){
	global $config;
	
	$result = mysql_query ($sql);
	if (! $result) {
		echo '<strong>Error:</strong> get_db_sql ("'.$sql.'") :'. mysql_error ().'<br />';
		return NULL;
	}
	if ($row = mysql_fetch_array ($result))
		return $row[$field];
	
	return NULL;
}

/**
 * Get all the result rows using an SQL statement.
 * 
 * @param $sql SQL statement to execute.
 *
 * @return A matrix with all the values returned from the SQL statement
 */
function get_db_all_rows_sqlfree ($sql) {
	global $config;
	$retval = array ();
	$result = mysql_query ($sql);
	
	if (! $result) {
		echo mysql_error ();
		return array();
	}
	while ($row = mysql_fetch_array ($result)) {
		array_push ($retval, $row);
	}
	return $retval;
}

/**
 * Get all the rows in a table of the database.
 * 
 * @param $table Database table name.
 *
 * @return A matrix with all the values in the table
 */
function get_db_all_rows_in_table ($table) {
	return get_db_all_rows_sqlfree ('SELECT * FROM '.$table);
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 * 
 * @param $table Database table name.
 * @param $field Field of the table.
 * @param $condition Condition the field must have to be selected.
 *
 * @return A matrix with all the values in the table that matches the condition in the field
 */
function get_db_all_rows_field_filter ($table, $field, $condition) {
	if (is_int ($condition)) {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = %d', $table, $field, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = %f', $table, $field, $condition);
	} else {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = "%s"', $table, $field, $condition);
	}
	
	return get_db_all_rows_sqlfree ($sql);
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 * 
 * @param $table Database table name.
 * @param $field Field of the table.
 * @param $condition Condition the field must have to be selected.
 *
 * @return A matrix with all the values in the table that matches the condition in the field
 */
function get_db_all_fields_in_table ($table, $field) {
	return get_db_all_rows_sqlfree ('SELECT '.$field.' FROM '. $table);
}



// ---------------------------------------------------------------
// Return current status from a given agent module (1 alive, 0 down)
// ---------------------------------------------------------------

function return_status_agent_module ($id_agentmodule = 0){
	$query1 = "SELECT estado FROM tagente_estado WHERE id_agente_modulo = " . $id_agentmodule; 
	$resq1 = mysql_query ($query1);
	if ($resq1 != 0) {
		$rowdup = mysql_fetch_array($resq1);
		if ($rowdup[0] == 100){
			// We need to check if there are any alert on this item
			$query2 = "SELECT SUM(times_fired) FROM talerta_agente_modulo WHERE id_agente_modulo = " . $id_agentmodule;
			$resq2 = mysql_query($query2);
			if ($resq2 != 0) {
		                $rowdup2 = mysql_fetch_array ($resq2);
				if ($rowdup2[0] > 0){
					return 0;
				}
			}
			// No alerts fired for this agent module
			return 1;
		} elseif ($rowdup[0] == 0) // 0 is ok for estado field
			return 1;
		else 
			return 0;
	} else // asking for unknown module ?
		return 0; 
}

// ---------------------------------------------------------------
// Return current status from a given layout
// ---------------------------------------------------------------

// This get's all data from it contained elements (including recursive calls to another nested 
// layouts, and makes and AND to be sure that ALL items are OK. If any of them is down, then
// result is down (0)

function return_status_layout ($id_layout = 0){
	$temp_status = 0;
	$temp_total = 0;
	$sql="SELECT * FROM tlayout_data WHERE id_layout = $id_layout";
	$res=mysql_query($sql);
	while ($row = mysql_fetch_array($res)){
		$id_agentmodule = $row["id_agente_modulo"];
		$type = $row["type"];
		$parent_item = $row["parent_item"];
		$link_layout = $row["id_layout_linked"];
		if (($link_layout != 0) && ($id_agentmodule == 0)) {
			$temp_status += return_status_layout ($link_layout);
			$temp_total++;
		} else {
			$temp_status += return_status_agent_module ($id_agentmodule);
			$temp_total++;
		}
	}
	if ($temp_status == $temp_total)
		return 1;
	else
		return 0;
}


// ---------------------------------------------------------------
// Return current value from a given agent module 
// ---------------------------------------------------------------

function return_value_agent_module ($id_agentmodule = 0){
	$query1="SELECT datos FROM tagente_estado WHERE id_agente_modulo = ".$id_agentmodule; 
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		$rowdup=mysql_fetch_array($resq1);
			return format_numeric($rowdup[0]);
		
	} else 
		return 0; 
}

// ---------------------------------------------------------------
// Return coordinate X from a layout item
// ---------------------------------------------------------------

function return_coordinate_X_layoutdata ($id_layoutdata){
	$query1="SELECT pos_x FROM tlayout_data WHERE id = ".$id_layoutdata; 
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		$rowdup=mysql_fetch_array($resq1);
			return ($rowdup[0]);
	} else 
		return (0);
}

// ---------------------------------------------------------------
// Return coordinate X from a layout item
// ---------------------------------------------------------------

function return_coordinate_y_layoutdata ($id_layoutdata){
	$query1="SELECT pos_y FROM tlayout_data WHERE id = ".$id_layoutdata; 
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		$rowdup=mysql_fetch_array($resq1);
			return ($rowdup[0]);
	} else 
		return (0);
}

/**
 * Get the previous data to the timestamp provided.
 *
 * It's useful to know the first value of a module in an interval, 
 * since it will be the last value in the 
 *
 * @param $id_agent_module Agent module id to look.
 * @param $utimestamp The timestamp to look backwards from and get the data.
 *
 * @return The row of tagente_datos of the last period. NULL if there were no data.
 */
function get_previous_data ($id_agent_module, $utimestamp) {
	$sql = sprintf ('SELECT * FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp <= %d 
			ORDER by utimestamp DESC LIMIT 1',
			$id_agent_module, $utimestamp);
	return get_db_row_sql ($sql);
}

function return_moduledata_avg_value ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = time ();
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
	return $sum / $total;
}


function return_moduledata_max_value ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = time ();
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT MAX(datos) FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d  AND utimestamp <= %d 
			ORDER BY utimestamp ASC",
			$id_agent_module, $datelimit, $date);
	$max = (float) get_db_sql ($sql);
	
	/* Get also the previous report before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data)
		return max ($previous_data['datos'], $max);
	
	return max ($previous_data, $max);
}

function return_moduledata_min_value ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = time ();
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT MIN(datos) FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d 
			ORDER BY utimestamp ASC",
			$id_agent_module, $datelimit, $date);
	$min = (float) get_db_sql ($sql);
	
	/* Get also the previous data before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data)
		return min ($previous_data['datos'], $min);
	return $min;
}

function return_moduledata_sum_value ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = time ();
	$datelimit = $date - $period; // limit date
	$module_name = get_db_value ('nombre', 'ttipo_modulo', 'id_tipo', $agent_module['id_tipo_modulo']);
	
	if (is_module_data_string ($module_name)) {
		return lang_string ('wrong_module_type');
	}
	$interval = get_db_value ('current_interval', 'tagente_estado', 'id_agente_modulo', $id_agent_module);
	
	// Get the whole interval of data
	$sql = sprintf ('SELECT * FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d',
			$id_agent_module, $datelimit, $date);
	$datas = get_db_all_rows_sqlfree ($sql);
	
	/* Get also the previous data before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data) {
		/* Add data to the beginning */
		array_unshift ($datas, $previous_data);
	}
	$last_data = "";
	$total_badtime = 0;
	$interval_begin = 0;
	$interval_last = 0;

	if (sizeof ($datas) == 0) {
		return 0;
	}
	$sum = 0;
	$previous_data = 0;
	foreach ($datas as $data) {
		if ($interval_begin != 0) {
			$interval_last = $data["utimestamp"];
			$elapsed = $interval_last - $interval_begin;
			$times = intval ($elapsed / $interval);
		} else {
			$times = 1;
		}
		if (is_module_proc ($module_name)) {
			$previous_data = $data['datos'] * $interval;
		} else {
			$previous_data = $data['datos'];
		}
		
		$interval_begin = $data["utimestamp"];
	}

	/* The last interval value must be get from tagente_estado, but
	   it will count only if it's not older than date demanded
	*/
	$interval_last = give_db_value ('utimestamp', 'tagente_estado', 'id_agente_modulo', $id_agent_module);
	if ($interval_last <= $datelimit) {
		$elapsed = $interval_last - $interval_begin;
		$times = intval ($elapsed / $interval);
		$sum += $times * $previous_data;
	}
	
	return (float) $sum;
}

function lang_string ($string) {
	global $config;
	require ($config["homedir"]."/include/languages/language_".$config["language"].".php");
	if (isset ($lang_label[$string]))
		return $lang_label[$string];
	return $string;
}

function check_server_status () {
	global $config;
	// This check assumes that server_keepalive should be AT LEAST 15 MIN
	$sql = "SELECT COUNT(id_server) FROM tserver WHERE status = 1 AND keepalive > NOW() - INTERVAL 15 MINUTE";
	$res = get_db_sql ($sql);
	// Set servers to down
	if ($res == 0){ 
		$res2 = mysql_query ("UPDATE tserver SET status = 0");
	}
	return $res;
}

function show_alert_row_mini ($id_combined_alert) {
	global $config;
	global $lang_label;

	$color=1;
	$sql = "SELECT talerta_agente_modulo.*, tcompound_alert.operation FROM talerta_agente_modulo, tcompound_alert WHERE tcompound_alert.id_aam = talerta_agente_modulo.id_aam AND tcompound_alert.id = ".$id_combined_alert;
	$result = mysql_query ($sql);
	echo "<table width=400 cellpadding=2 cellspacing=2 class='databox'>";
	echo "<th>".lang_string("Name");
	echo "<th>".lang_string("Oper");
	echo "<th>".lang_string("Tt");
	echo "<th>".lang_string("Firing");
	echo "<th>".lang_string("Time");
	echo "<th>".lang_string("Desc");
	echo "<th>".lang_string("Recovery");
	echo "<th>".lang_string("MinMax.Al");
	echo "<th>".lang_string("Days");
	echo "<th>".lang_string("Fired");
	while ($row2 = mysql_fetch_array ($result)) {

		if ($color == 1) {
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr>";    

		if ($row2["disable"] == 1){
			$tdcolor = "datos3";
		}
		echo "<td class=$tdcolor>".get_db_sql("SELECT nombre FROM tagente_modulo WHERE id_agente_modulo =".$row2["id_agente_modulo"]);
		echo "<td class=$tdcolor>".$row2["operation"];

		echo "<td class='$tdcolor'>".human_time_description($row2["time_threshold"]);

		if ($row2["dis_min"]!=0){
			$mytempdata = fmod($row2["dis_min"], 1);
		if ($mytempdata == 0)
			$mymin = intval($row2["dis_min"]);
		else
			$mymin = $row2["dis_min"];
			$mymin = format_for_graph($mymin );
		} else {
			$mymin = 0;
		}

		if ($row2["dis_max"]!=0){
			$mytempdata = fmod($row2["dis_max"], 1);
		if ($mytempdata == 0)
			$mymax = intval($row2["dis_max"]);
		else
			$mymax = $row2["dis_max"];
			$mymax =  format_for_graph($mymax );
		} else {
			$mymax = 0;
		}

		if (($mymin == 0) && ($mymax == 0)){
			$mymin = lang_string ("N/A");
			$mymax = $mymin;
		}

		// We have alert text ?
		if ($row2["alert_text"]!= "") {
			echo "<td class='$tdcolor'>".$lang_label["text"]."</td>";
		} else {
			echo "<td class='$tdcolor'>".$mymin."/".$mymax."</td>";
		}

		// Alert times
		echo "<td class='$tdcolor'>";
		echo get_alert_times ($row2);

		// Description
		echo "</td><td class='$tdcolor'>".substr($row2["descripcion"],0,20);

		// Has recovery notify activated ?
		if ($row2["recovery_notify"] > 0)
			$recovery_notify = lang_string("Yes");
		else
			$recovery_notify = lang_string("No");

		echo "</td><td class='$tdcolor'>".$recovery_notify;

		// calculare firing conditions
		if ($row2["alert_text"] != ""){
			$firing_cond = lang_string("text")."(".substr($row2["alert_text"],0,8).")";
		} else {
			$firing_cond = $row2["min_alerts"]." / ".$row2["max_alerts"];
		}
		echo "</td><td class='$tdcolor'>".$firing_cond;

		// calculate days
		$firing_days = get_alert_days ( $row2 );
		echo "</td><td class='$tdcolor'>".$firing_days;

		// Fired ?
		if ($row2["times_fired"]>0)
		echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_red.png' title='".lang_string("fired")."'></td>";
		else
		echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_green.png' title='".$lang_label["not_fired"]."'></td>";

	}
	echo "</table>";
}

function smal_event_table ($filter = "", $limit = 10, $width = 440) {
	global $config;
	global $lang_label;

	$sql2 = "SELECT * FROM tevento $filter ORDER BY timestamp DESC LIMIT $limit";
	echo "<table cellpadding='4' cellspacing='4' width='$width' border=0 class='databox'>";
	echo "<tr>";
	echo "<th colspan=6>".lang_string("Latest events");
	echo "<tr>";
	echo "<th class='datos3 f9'>".lang_string ("St")."</th>";
	echo "<th class='datos3 f9'>".lang_string ("Type")."</th>";
	echo "<th class='datos3 f9'>".$lang_label["event_name"]."</th>";
	echo "<th class='datos3 f9'>".$lang_label["agent_name"]."</th>";
	echo "<th class='datos3 f9'>".$lang_label["id_user"]."</th>";
	echo "<th class='datos3 f9'>".$lang_label["timestamp"]."</th>";
	$result2=mysql_query($sql2);
	while ($row2=mysql_fetch_array($result2)){
		$id_grupo = $row2["id_grupo"];
		if (give_acl($config["id_user"], $id_grupo, "AR") == 1){ // Only incident read access to view data !
			switch ($row2["criticity"]) {
			case 0: 
				$tdclass = "datos_blue";
				break;
			case 1: 
				$tdclass = "datos_grey";
				break;
			case 2: 
				$tdclass = "datos_green";
				break;
			case 3: 
				$tdclass = "datos_yellow";
				break;
			case 4: 
				$tdclass = "datos_red";
				break;
			default:
				$tdclass = "datos_grey";
			}
			$criticity_label = return_priority ($row2["criticity"]);
			// Colored box 
			echo "<tr><td class='$tdclass' title='$criticity_label' align='center'>";
			if ($row2["estado"] == 0)
				echo "<img src='images/pixel_red.png' width=20 height=20>";
			else
				echo "<img src='images/pixel_green.png' width=20 height=20>";
		
			// Event type
			echo "<td class='".$tdclass."' title='".$row2["event_type"]."'>";
			switch ($row2["event_type"]){
			case "unknown": 
				echo "<img src='images/err.png'>";
				break;
			case "alert_recovered": 
				echo "<img src='images/error.png'>";
				break;
			case "alert_manual_validation": 
				echo "<img src='images/eye.png'>";
				break;
			case "monitor_up":
				echo "<img src='images/lightbulb.png'>";
				break;
			case "monitor_down":
				echo "<img src='images/lightbulb_off.png'>";
				break;
			case "alert_fired":
				echo "<img src='images/bell.png'>";
				break;
			case "system";
			echo "<img src='images/cog.png'>";
			break;
			case "recon_host_detected";
			echo "<img src='images/network.png'>";
			break;
			}
		
			// Event description
			echo "<td class='".$tdclass."f9' title='".$row2["evento"]."'>";
			echo substr($row2["evento"],0,45);
			if (strlen($row2["evento"]) > 45)
				echo "..";
			if ($row2["id_agente"] > 0){
				// Agent name
				$agent_name = dame_nombre_agente($row2["id_agente"]);
				echo "<td class='".$tdclass."f9' title='$agent_name'><a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$row2["id_agente"]."'><b>";
				echo substr($agent_name, 0, 14);
				if (strlen($agent_name) > 14)
					echo "..";
				echo "</b></a>";
			
				// for System or SNMP generated alerts
			} else { 
				if ($row2["event_type"] == "system"){
					echo "<td class='$tdclass'>".lang_string("System");
				} else {
					echo "<td class='$tdclass'>".$lang_label["alert"]."SNMP";
				}
			}
		
			// User who validated event
			echo "<td class='$tdclass'>";
			if ($row2["estado"] <> 0)
				echo "<a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$row2["id_usuario"]."'>".substr($row2["id_usuario"],0,8)."<a href='#' class='tip'> <span>".dame_nombre_real($row2["id_usuario"])."</span></a></a>";
		
			// Timestamp
			echo "<td class='".$tdclass."f9' title='".$row2["timestamp"]."'>";
			echo human_time_comparation($row2["timestamp"]);
		
		}
	}
	echo "</table>";
}
?>
