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
if (!isset ($config["auth"])) {
	require_once ("../../include/auth/mysql.php");
} else {
	require_once ("../../include/auth/".$config["auth"]["scheme"].".php");
}
require_once ("../../include/functions.php");
require_once ("../../include/functions_db.php");
require_once ("../../include/functions_events.php");

session_write_close ();

$config["id_user"] = $_SESSION["id_usuario"];

if (! give_acl ($config["id_user"], 0, "AR") && ! give_acl ($config["id_user"], 0, "AW")) {
	exit;
}

$offset = (int) get_parameter ("offset");
$ev_group = (int) get_parameter ("ev_group"); // group
$search = (int) get_parameter ("search"); // free search
$event_type = (string) get_parameter ("event_type", "all"); // 0 all
$severity = (int) get_parameter ("severity", -1); // -1 all
$status = (int) get_parameter ("status", -1); // -1 all, 0 only red, 1 only green
$id_agent = (int) get_parameter ("id_agent", -1);

$filter = array ();
if ($ev_group > 1)
	$filter['id_grupo'] = $ev_group;
if ($status == 1)
	$filter['estado'] = 1;
if ($status == 0)
	$filter['estado'] = 0;
if ($search != "")
	$filter[] = 'evento LIKE "%'.$search.'%"';
if (($event_type != "all") AND ($event_type != 0))
	$filter['event_type'] = $event_type;
if ($severity != -1)
	$filter[] = 'criticity >= '.$severity;
if ($id_agent != -1)
	$filter['id_agente'] = $id_agent;

$filter['order'] = 'timestamp DESC';
$now = date ("Y-m-d");

// Show contentype header	
Header ("Content-type: text/txt");
header ('Content-Disposition: attachment; filename="pandora_export_event'.$now.'.txt"');

echo "timestamp, agent, group, event, status, user, event_type, severity";
echo chr (13);

$fields = array ('id_grupo', 'id_agente', 'evento', 'estado', 'id_usuario',
	'event_type', 'criticity', 'timestamp');
$events = get_events ($filter, $fields);
if ($events === false)
	$events = array ();
foreach ($events as $event) {
	if (! give_acl ($config["id_user"], $event["id_grupo"], "AR"))
		continue;
	
	echo $event["timestamp"];
	echo ",";
	echo get_db_value ('nombre', 'tagente', 'id_agente', $event["id_agente"]);
	echo ",";
	echo get_db_value ('nombre', 'tgrupo', 'id_grupo', $event["id_grupo"]);
	echo ",";
	echo $event["evento"];
	echo ",";
	echo $event["estado"];
	echo ",";
	echo $event["id_usuario"];
	echo ",";
	echo $event["event_type"];
	echo ",";
	echo $event["criticity"];
	echo chr (13);
}
?>

