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

session_start();

require_once ("../../include/config.php");
require_once ("../../include/auth/mysql.php");
require_once ("../../include/functions.php");
require_once ("../../include/functions_db.php");
require_once ("../../include/functions_events.php");
require_once ("../../include/functions_agents.php");
require_once ('../../include/functions_groups.php');

session_write_close ();

$config["id_user"] = $_SESSION["id_usuario"];

if (! check_acl ($config["id_user"], 0, "AR") && ! check_acl ($config["id_user"], 0, "AW")) {
	exit;
}

global $config;

$offset = (int) get_parameter ("offset");
$ev_group = (int) get_parameter ("ev_group"); // group
//$search = (int) get_parameter ("search"); // free search
$event_type = (string) get_parameter ("event_type", "all"); // 0 all
$severity = (int) get_parameter ("severity", -1); // -1 all
$status = (int) get_parameter ("status", -1); // -1 all, 0 only red, 1 only green
$id_agent = (int) get_parameter ("id_agent", -1);

$id_event = (int) get_parameter ("id_event", -1);
$event_view_hr = (int) get_parameter ("event_view_hr", $config["event_view_hr"]);
$id_user_ack = get_parameter ("id_user_ack", 0);
$search = io_safe_output(preg_replace ("/&([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/", "&", rawurldecode (get_parameter ("search"))));
$text_agent = (string)get_parameter('text_agent', __("All"));
$tag = get_parameter("tag", "");

$filter = array ();
if ($ev_group > 1)
	$filter['id_grupo'] = $ev_group;
/*if ($status == 1)
	$filter['estado'] = 1;
if ($status == 0)
	$filter['estado'] = 0; */
$filter_state = '';
switch($status) {
	case 0:
	case 1:
	case 2:
		$filter_state = " AND estado = " . $status;
		break;
	case 3:
		$filter_state = " AND (estado = 0 OR estado = 2)";
		break;
}		
if ($search != "")
	$filter[] = 'evento LIKE "%'.io_safe_input($search).'%"';
if (($event_type != "all") OR ($event_type != 0))
	$filter['event_type'] = $event_type;	
if ($severity != -1)
	$filter[] = 'criticity >= '.$severity;
	
if ($id_agent == -2) {
	$text_agent = (string) get_parameter("text_agent", __("All"));

	switch ($text_agent)
	{
		case __('All'):
			$id_agent = -1;
			break;
		case __('Server'):
			$id_agent = 0;
			break;
		default:
			$id_agent = agents_get_agent_id($text_agent);
			break;
	}
}
else {
	switch ($id_agent)
	{
		case -1:
			$text_agent = __('All');
			break;
		case 0:
			$text_agent = __('Server');
			break;
		default:
			$text_agent = agents_get_name($id_agent);
			break;
	}
}
	
	
if ($id_agent != -1)
	$filter['id_agente'] = $id_agent;
	
if ($id_event != -1)
	$filter['id_evento'] = $id_event;	
	
$timestamp_filter = '';	
if ($event_view_hr > 0) {
	$unixtime = get_system_time () - ($event_view_hr * 3600); //Put hours in seconds
	$timestamp_filter = " AND (utimestamp > 	$unixtime OR estado = 2)";
}

if ($id_user_ack != "0")
	$filter['id_usuario'] = $id_user_ack;
	
//Search by tag
if ($tag != "") {
	$filter['tags'] = "%".io_safe_input($tag)."%";
}	

//$filter['order'] = 'timestamp DESC';
$now = date ("Y-m-d");

// Show contentype header	
Header ("Content-type: text/txt");
header ('Content-Disposition: attachment; filename="pandora_export_event'.$now.'.txt"');

echo "timestamp, agent, group, event, status, user, event_type, severity";
echo chr (13);

$fields = array ('id_grupo', 'id_agente', 'evento', 'estado', 'id_usuario',
	'event_type', 'criticity', 'timestamp');

$sql = db_get_all_rows_filter ('tevento', $filter, $fields, 'AND', true, true);

// If filter is empty and there are others filters not empty append "WHERE" clause
if (empty($filter) and (!empty($filter_state) or !empty($timestamp_filter)))
	$sql .= ' WHERE 1=1 ';

$sql .= $filter_state . $timestamp_filter . ' ORDER BY timestamp DESC';  

$new = true;
while ($event = db_get_all_row_by_steps_sql($new, $result, $sql)) {
	$new = false;
	if (!check_acl($config["id_user"], $event["id_grupo"], "AR") ||
	(!check_acl($config["id_user"], 0, "PM") && $event["event_type"] == 'system'))
		continue;
	
	echo $event["timestamp"];
	echo ",";
	echo io_safe_output(agents_get_name($event["id_agente"]));
	echo ",";
	echo io_safe_output(groups_get_name($event["id_grupo"]));
	echo ",";
	echo io_safe_output($event["evento"]);
	echo ",";
	echo io_safe_output($event["estado"]);
	echo ",";
	echo io_safe_output($event["id_usuario"]);
	echo ",";
	echo io_safe_output($event["event_type"]);
	echo ",";
	echo $event["criticity"];
	echo chr (13);
}
?>

