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


include ("../../include/config.php");
include ("../../include/functions.php");
include ("../../include/functions_db.php");

session_start();

$id_user = $_SESSION["id_usuario"];
if ( (give_acl($id_user, 0, "AR")==0) AND (give_acl($id_user, 0, "AW")==0) ){
	require ("../../general/noaccess.php");
	exit;
}

if ( isset ($_GET["agentmodule"]) && isset ($_GET["agent"]) ){
	$id_agentmodule = $_GET["agentmodule"];
	$id_agent = $_GET["agent"];
	$agentmodule_name = dame_nombre_modulo_agentemodulo($id_agentmodule);
	if (give_acl($id_user,dame_id_grupo($id_agent),"AR")!=1) {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent Export Data");
		require ("../../general/noaccess.php");
		exit;
	}

	$now = date("Y/m/d H:i:s");
	
	// Show contentype header	
	Header("Content-type: text/txt");
	header('Content-Disposition: attachment; filename="pandora_export_'.$agentmodule_name.'.txt"');

	if (isset($_GET["from_date"]))
		$from_date = $_GET["from_date"];
	else
		$from_date = $now;
	
	if (isset($_GET["to_date"]))
		$to_date = $_GET["to_date"];
	else
		$to_date = $now;
	
	// Make the query
	$sql1="SELECT * FROM tdatos WHERE id_agente = $id_agent AND id_agente_modulo = $id_agentmodule";
	$tipo = dame_nombre_tipo_modulo(dame_id_tipo_modulo_agentemodulo($id_agentmodule));
	if ($tipo == "generic_data_string")
		$sql1='SELECT * FROM tagente_datos_string WHERE timestamp > "'.$from_date.'" AND timestamp < "'.$to_date.'" AND id_agente_modulo ='.$id_agentmodule.' ORDER BY timestamp DESC';
	else
		$sql1='SELECT * FROM tagente_datos WHERE timestamp > "'.$from_date.'" AND timestamp < "'.$to_date.'" AND id_agente_modulo ='.$id_agentmodule.' ORDER BY timestamp DESC';
	$result1=mysql_query($sql1);
	
	// Render data
	while ($row=mysql_fetch_array($result1)){
		echo $agentmodule_name;
		echo ",";
		echo $row["datos"];
		echo ",";
		echo $row["timestamp"];
		echo chr(13);
	}
}
?>

