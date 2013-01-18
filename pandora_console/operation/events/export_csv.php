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

if (! check_acl ($config["id_user"], 0, "ER") && ! check_acl ($config["id_user"], 0, "EW")) {
	exit;
}

global $config;

// loading l10n tables, because of being invoked not through index.php.
$l10n = NULL;
if (file_exists ($config['homedir'].'/include/languages/'.$user_language.'.mo')) {
	$l10n = new gettext_reader (new CachedFileReader ($config['homedir'].'/include/languages/'.$user_language.'.mo'));
	$l10n->load_tables();
}

$offset = (int) get_parameter ("offset");
$ev_group = (int) get_parameter ("ev_group"); // group
$event_type = (string) get_parameter ("event_type", "all"); // 0 all
$severity = (int) get_parameter ("severity", -1); // -1 all
$status = (int) get_parameter ("status", -1); // -1 all, 0 only red, 1 only green
$id_agent = (int) get_parameter ("id_agent", -1);

$id_event = (int) get_parameter ("id_event", -1);
$event_view_hr = (int) get_parameter ("event_view_hr", $config["event_view_hr"]);
$id_user_ack = get_parameter ("id_user_ack", 0);
$search = io_safe_output(preg_replace ("/&([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/", "&", rawurldecode (get_parameter ("search"))));
$text_agent = (string)get_parameter('text_agent', __("All"));

$tag_with_json = base64_decode(get_parameter("tag_with", '')) ;
$tag_with_json_clean = io_safe_output($tag_with_json);
$tag_with_base64 = base64_encode($tag_with_json_clean);
$tag_with = json_decode($tag_with_json_clean, true);
if (empty($tag_with)) $tag_with = array();
$tag_with = array_diff($tag_with, array(0 => 0));

$tag_without_json = base64_decode(get_parameter("tag_without", ''));
$tag_without_json_clean = io_safe_output($tag_without_json);
$tag_without_base64 = base64_encode($tag_without_json_clean);
$tag_without = json_decode($tag_without_json_clean, true);
if (empty($tag_without)) $tag_without = array();
$tag_without = array_diff($tag_without, array(0 => 0));	

$filter_only_alert = (int)get_parameter('filter_only_alert', -1);

/////////////////////////////////////////////
// Build the condition of the events query

$sql_post = "";
$meta = false;

$id_user = $config['id_user'];

require('events.build_query.php');

// Now $sql_post have all the where condition
/////////////////////////////////////////////

switch ($config["dbtype"]) {
	case "mysql":
	case "postgresql":
	case "oracle":
		$sql = "SELECT *
			FROM tevento
			WHERE 1=1 ".$sql_post."
			ORDER BY utimestamp DESC";
		break;
}

$now = date ("Y-m-d");

// Show contentype header	
Header ("Content-type: text/txt");
header ('Content-Disposition: attachment; filename="pandora_export_event'.$now.'.txt"');

echo "timestamp, agent, group, event, status, user, event_type, severity";
echo chr (13);

$new = true;
while ($event = db_get_all_row_by_steps_sql($new, $result, $sql)) {
	$new = false;
	if (!check_acl($config["id_user"], $event["id_grupo"], "ER") ||
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

