<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

session_start();

require_once ("../../include/config.php");
require_once ("../../include/functions.php");
require_once ("../../include/functions_db.php");

$config["id_user"] = $_SESSION["id_usuario"];
if (! give_acl ($config['id_user'], 0, "AR") && ! give_acl ($config['id_user'], 0, "AW")) {
	require ("../../general/noaccess.php");
	return;
}

if (isset ($_GET["agentmodule"]) && isset ($_GET["agent"]) ){
	$id_agentmodule = $_GET["agentmodule"];
	$id_agent = $_GET["agent"];
	$agentmodule_name = get_agentmodule_name ($id_agentmodule);
	if (! give_acl ($config['id_user'], dame_id_grupo ($id_agent), "AR")) {
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

	// Convert to unix date	
	$from_date = date("U", strtotime($from_date));
	$to_date = date("U", strtotime($to_date));

	// Make the query
	$sql1="SELECT * FROM tdatos WHERE id_agente = $id_agent AND id_agente_modulo = $id_agentmodule";
	$tipo = get_moduletype_name (get_agentmodule_type ($id_agentmodule));
	if ($tipo == "generic_data_string")
		$sql1 = "SELECT * FROM tagente_datos_string WHERE utimestamp > $from_date AND utimestamp < $to_date AND id_agente_modulo = $id_agentmodule ORDER BY utimestamp DESC";
	else
		$sql1 = "SELECT * FROM tagente_datos WHERE utimestamp > $from_date AND utimestamp < $to_date AND id_agente_modulo = $id_agentmodule ORDER BY utimestamp DESC";
	$result1=mysql_query($sql1);
	
	// Render data
	while ($row=mysql_fetch_array($result1)){
		echo $agentmodule_name;
		echo ",";
		echo $row["datos"];
		echo ",";
		echo $row["utimestamp"];
		echo chr(13);
	}
}
?>

