<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
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

session_start();

include ("../../include/config.php");
include ("../../include/functions.php");
include_once ("../../include/functions_db.php");

session_write_close(); 

$config["id_user"] = $_SESSION["id_usuario"];

if ( (give_acl($config["id_user"], 0, "AR")==0) AND (give_acl($config["id_user"], 0, "AW")==0) ){
	require ("../../general/noaccess.php");
	exit;
}


$offset = get_parameter ( "offset",0);
$ev_group = get_parameter ("ev_group", 0); // group
$search = get_parameter ("search", ""); // free search
$event_type = get_parameter ("event_type", "all"); // 0 all
$severity = get_parameter ("severity", -1); // -1 all
$status = get_parameter ("status", -1); // -1 all, 0 only red, 1 only green
$id_agent = get_parameter ("id_agent", -1);

$sql_post = "";
if ($ev_group > 1)
    $sql_post .= " AND id_grupo = $ev_group";
if ($status == 1)
    $sql_post .= " AND estado = 1";
if ($status == 0)
    $sql_post .= " AND estado = 0";
if ($search != "")
    $sql_post .= " AND evento LIKE '%$search%'";
if (($event_type != "all") AND ($event_type != 0))
    $sql_post .= " AND event_type = '$event_type'";
if ($severity != -1)
    $sql_post .= " AND criticity >= $severity";
if ($id_agent != -1)
    $sql_post .= " AND id_agente = $id_agent";

$sql2 = "SELECT * FROM tevento WHERE 1=1 ";
$sql2 .= $sql_post . " ORDER BY timestamp DESC";
$now = date("Y-m-d");

// Show contentype header	
Header("Content-type: text/txt");
header('Content-Disposition: attachment; filename="pandora_export_event'.$now.'.txt"');

echo "timestamp, agent, group, event, status, user, event_type, severity";
echo chr(13);

$result=mysql_query($sql2);
while ($row=mysql_fetch_array($result)){
	$id_grupo = $row["id_grupo"];
	if (give_acl($config["id_user"], $id_grupo, "AR") == 1){ // Only incident read access to view data !
		echo $row["timestamp"];
		echo ", ";
		echo get_db_sql("SELECT nombre FROM tagente WHERE id_agente = '".$row["id_agente"]."'");
		echo ", ";
		echo get_db_sql("SELECT nombre FROM tgrupo WHERE id_grupo = '".$row["id_grupo"]."'");
		echo ", ";
		echo $row["evento"];
		echo ", ";
		echo $row["estado"];
		echo ", ";
		echo $row["id_usuario"];
		echo ", ";
		echo $row["event_type"];
		echo ", ";
		echo $row["criticity"];		
		echo chr(13);
	}
}
?>

