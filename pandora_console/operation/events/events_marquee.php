<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

error_reporting(1);

// Local settings for marquee extension

$MAX_MARQUEE_EVENTS=10;
$MARQUEE_INTERVAL=90;
$MARQUEE_FONT_SIZE="32px";
$MARQUEE_SPEED=12;

$output = "";
require_once "../../include/config.php";
require_once "../../include/functions.php";
require_once "../../include/functions_db.php";
require_once "../../include/functions_api.php";
require_once ('../../include/functions_users.php');

global $config;

session_start ();

$config["id_user"] = $_SESSION["id_usuario"];

// http://es2.php.net/manual/en/ref.session.php#64525
// Session locking concurrency speedup!
check_login ();

session_write_close (); 


if(!isInACL($_SERVER['REMOTE_ADDR'])){
    db_pandora_audit("ACL Violation",
		"Trying to access marquee without ACL Access");
	require ("../../general/noaccess.php");
	exit;
}

$groups = users_get_groups ($config["id_user"], "AR");
//Otherwise select all groups the user has rights to.
if(!empty($groups)) {
	$sql_group_filter = " AND id_grupo IN (".implode (",", array_keys ($groups)).")";
}
else {
	$sql_group_filter = "";
}

// Skip system messages if user is not PM
if (!check_acl ($config["id_user"], 0, "PM")) {
    $sql_group_filter .= " AND id_grupo != 0";
}

switch ($config["dbtype"]) {
	case "mysql":
		$sql = "SELECT evento, timestamp, id_agente FROM tevento WHERE 1=1 $sql_group_filter ORDER BY utimestamp DESC LIMIT 0 , $MAX_MARQUEE_EVENTS";
		break;
	case "postgresql":
		$sql = "SELECT evento, timestamp, id_agente FROM tevento WHERE 1=1 $sql_group_filter ORDER BY utimestamp DESC LIMIT $MAX_MARQUEE_EVENTS OFFSET 0";
		break;
	case "oracle":
		$sql = "SELECT evento, timestamp, id_agente FROM tevento WHERE (1=1 $sql_group_filter ) AND rownum <= $MAX_MARQUEE_EVENTS ORDER BY utimestamp DESC";
		break;
}

$result = db_get_all_rows_sql ($sql);
foreach ($result as $row) {
	$agente = "";
	if ($row["id_agente"] != 0){
		$agente = db_get_sql ("SELECT nombre FROM tagente WHERE id_agente = ". $row["id_agente"]);
		$agente = $agente . " : ";
	}
	$output .= strtoupper($agente) . $row["evento"]. " , ". human_time_comparation($row["timestamp"]);
	$output .= ".&nbsp;&nbsp;&nbsp;&nbsp;.&nbsp;&nbsp;&nbsp;&nbsp;.&nbsp;&nbsp;&nbsp;&nbsp;.&nbsp;&nbsp;&nbsp;&nbsp;.&nbsp;&nbsp;&nbsp;&nbsp;.&nbsp;&nbsp;&nbsp;&nbsp;. ";
}


echo "<html>";
echo "<head>";
echo "<title>Pandora FMS - Latest events </title>";

$query = ui_get_full_url();
echo '<meta http-equiv="refresh" content="' . $MARQUEE_INTERVAL . '; URL=' . $query . '">';
echo '<link rel="icon" href="../../images/pandora.ico" type="image/ico">';
echo "</head>";

echo "<body bgcolor='#000000' >";
echo "<br><br>";
echo "<center>";
echo "<div style='font-size:$MARQUEE_FONT_SIZE; color: #fff'>";
echo "<marquee width=95% scrollamount=$MARQUEE_SPEED>$output</marquee>";
echo "</center>";
echo "</div>";
echo "</body>";

?>
