<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP code additions
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to code for Pandora FMS 1.2 graph code and new XML reporting template managemement
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

require ("include/config.php");

check_login ();

if (! give_acl($config['id_user'], 0, "AR") && ! give_acl ($config['id_user'], 0, "AW")){
	require ("../../general/noaccess.php");
	return;
}

if (isset ($_GET["agentmodule"]) && isset ($_GET["agent"]) ){
	$id_agentmodule = $_GET["agentmodule"];
	$id_agent = $_GET["agent"];
	$agentmodule_name = dame_nombre_modulo_agentemodulo ($id_agentmodule);
	if (! give_acl ($config['id_user'], dame_id_grupo ($id_agent), "AR") != 1) {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to access Agent Export Data");
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

