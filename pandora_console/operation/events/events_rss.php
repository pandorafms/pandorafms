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

ini_set ('display_errors', 0); //Don't display other errors, messes up XML

require_once "../../include/config.php";
require_once "../../include/functions.php";
require_once "../../include/functions_db.php";
require_once "../../include/functions_api.php";
require_once "../../include/functions_agents.php";
require_once "../../include/functions_users.php";
require_once "../../include/functions_tags.php";

ini_set ('display_errors', 0); //Don't display other errors, messes up XML

$ipOrigin = $_SERVER['REMOTE_ADDR'];

// Uncoment this to activate ACL on RSS Events
if (!isInACL($ipOrigin)) {
    exit;
}
    
// Check user credentials
$user = get_parameter('user');
$hashup = get_parameter('hashup');

$pss = get_user_info($user);
$hashup2 = md5($user.$pss['password']);

if ($hashup != $hashup2) {
	exit;
}

header("Content-Type: application/xml; charset=UTF-8"); //Send header before starting to output

function rss_error_handler ($errno, $errstr, $errfile, $errline) {	
	$url = ui_get_full_url(false);
	$selfurl = ui_get_full_url('?' . $_SERVER['QUERY_STRING']);
	
	$rss_feed = '<?xml version="1.0" encoding="utf-8" ?>'; //' Fixes certain highlighters freaking out on the PHP closing tag
	$rss_feed .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'; 
	$rss_feed .= '<channel><title>Pandora RSS Feed</title><description>Latest events on Pandora</description>';
	$rss_feed .= '<lastBuildDate>'.date (DATE_RFC822, 0).'</lastBuildDate>';
	$rss_feed .= '<link>'.$url.'</link>'; //Link back to the main Pandora page
	$rss_feed .= '<atom:link href="'.io_safe_input ($selfurl).'" rel="self" type="application/rss+xml" />'; //Alternative for Atom feeds. It's the same.
	
	$rss_feed .= '<item><guid>'.$url.'/index.php?sec=eventos&sec2=operation/events/events</guid><title>Error creating feed</title>';
	$rss_feed .= '<description>There was an error creating the feed: '.$errno.' - '.$errstr.' in '.$errfile.' on line '.$errline.'</description>';
	$rss_feed .= '<link>'.$url.'/index.php?sec=eventos&sec2=operation/events/events</link></item>';
	
	exit ($rss_feed); //Exit by displaying the feed
}

set_error_handler ('rss_error_handler', E_ALL); //Errors output as RSS

$ev_group = get_parameter ("ev_group", 0); // group
$event_type = get_parameter ("event_type", ''); // 0 all
$severity = (int) get_parameter ("severity", -1); // -1 all
$status = (int) get_parameter ("status", 0); // -1 all, 0 only red, 1 only green
$id_agent = (int) get_parameter ("id_agent", -1);

$id_event = (int) get_parameter ("id_event", -1); //This will allow to select only 1 event (eg. RSS)
$event_view_hr = (int) get_parameter ("event_view_hr", 0);
$id_user_ack = get_parameter ("id_user_ack", 0);
$search = io_safe_output(preg_replace ("/&([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/", "&", rawurldecode (get_parameter ("search"))));
$text_agent = (string) get_parameter("text_agent", __("All"));

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

$groups = users_get_groups($user, 'IR');

//Group selection
if ($ev_group > 0 && in_array ($ev_group, array_keys ($groups))) {
	//If a group is selected and it's in the groups allowed
	$sql_post = " AND id_grupo = $ev_group";
}
else {
	if (is_user_admin ($user)) {
		//Do nothing if you're admin, you get full access
		$sql_post = "";
	}
	else {
		//Otherwise select all groups the user has rights to.
		$sql_post = " AND id_grupo IN (" .
			implode (",", array_keys ($groups)) . ")";
	}
}

// Skip system messages if user is not PM
if (!check_acl ($user, 0, "PM")) {
	$sql_post .= " AND id_grupo != 0";
}

switch ($status) {
	case 0:
	case 1:
	case 2:
		$sql_post .= " AND estado = " . $status;
		break;
	case 3:
		$sql_post .= " AND (estado = 0 OR estado = 2)";
		break;
}

if ($search != "") {
	$sql_post .= " AND evento LIKE '%" . io_safe_input($search) . "%'";
}

if ($event_type != "") {
	// If normal, warning, could be several (going_up_warning, going_down_warning... too complex 
	// for the user so for him is presented only "warning, critical and normal"
	if ($event_type == "warning" || $event_type == "critical"
		|| $event_type == "normal") {
		$sql_post .= " AND event_type LIKE '%$event_type%' ";
	}
	elseif ($event_type == "not_normal") {
		$sql_post .= " AND event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ";
	}
	elseif ($event_type != "all") {
		$sql_post .= " AND event_type = '" . $event_type."'";
	}

}

if ($severity != -1) {
	switch($severity) {
		case EVENT_CRIT_WARNING_OR_CRITICAL:
			$sql_post .= " AND (criticity = " . EVENT_CRIT_WARNING . " OR 
								criticity = " . EVENT_CRIT_CRITICAL . ")";
			break;
		case EVENT_CRIT_NOT_NORMAL:
			$sql_post .= " AND criticity != " . EVENT_CRIT_NORMAL;
			break;
		default:
			$sql_post .= " AND criticity = $severity";
			break;
	}
}

if ($id_agent > 0)
	$sql_post .= " AND id_agente = " . $id_agent;
	
if ($id_event != -1)
	$sql_post .= " AND id_evento = " . $id_event;

if ($id_user_ack != "0")
	$sql_post .= " AND id_usuario = '" . $id_user_ack . "'";


if ($event_view_hr > 0) {
	$unixtime = get_system_time () - ($event_view_hr * 3600); //Put hours in seconds
	$sql_post .= " AND (utimestamp > " . $unixtime . " OR estado = 2)";
}
	
//Search by tag
if (!empty($tag_with)) {
	$sql_post .= ' AND ( ';
	$first = true;
	foreach ($tag_with as $id_tag) {
		if ($first) $first = false;
		else $sql_post .= " OR ";
		$sql_post .= "tags LIKE '%" . tags_get_name($id_tag) . "%'";
	}
	$sql_post .= ' ) ';
}

if (!empty($tag_without)) {
	$sql_post .= ' AND ( ';
	$first = true;
	foreach ($tag_without as $id_tag) {
		if ($first) $first = false;
		else $sql_post .= " OR ";
		$sql_post .= "tags NOT LIKE '%" . tags_get_name($id_tag) . "%'";
	}
	$sql_post .= ' ) ';
}

// Filter/Only alerts
if (isset($filter_only_alert)) {
	if ($filter_only_alert == 0)
		$sql_post .= " AND event_type NOT LIKE '%alert%'";
	else if ($filter_only_alert == 1)
		$sql_post .= " AND event_type LIKE '%alert%'";
}

$sql = "SELECT *
	FROM tevento
	WHERE 1=1 ".$sql_post."
	ORDER BY utimestamp DESC";

$result = db_get_all_rows_sql ($sql);

$url = ui_get_full_url(false);
$selfurl = ui_get_full_url('?' . $_SERVER['QUERY_STRING']);

if (empty ($result)) {
	$lastbuild = 0; //Last build in 1970
}
else {
	$lastbuild = (int) $result[0]['utimestamp'];
}

$rss_feed = '<?xml version="1.0" encoding="utf-8" ?>' . "\n"; // ' <?php ' -- Fixes highlighters thinking that the closing tag is PHP
$rss_feed .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n"; 
$rss_feed .= '<channel>'. "\n";
$rss_feed .= '<title>Pandora RSS Feed</title>'. "\n";
$rss_feed .= '<description>Latest events on Pandora</description>' . "\n";
$rss_feed .= '<lastBuildDate>'.date (DATE_RFC822, $lastbuild).'</lastBuildDate>'. "\n"; //Last build date is the last event - that way readers won't mark it as having new posts
$rss_feed .= '<link>'.$url.'</link>'. "\n"; //Link back to the main Pandora page
$rss_feed .= '<atom:link href="'.io_safe_input ($selfurl).'" rel="self" type="application/rss+xml" />'. "\n";; //Alternative for Atom feeds. It's the same.

if (empty ($result)) {
	$result = array();
	$rss_feed .= '<item><guid>'.io_safe_input ($url.'/index.php?sec=eventos&sec2=operation/events/events').'</guid><title>No results</title>';
	$rss_feed .= '<description>There are no results. Click on the link to see all Pending events</description>';
	$rss_feed .= '<link>'.io_safe_input ($url.'/index.php?sec=eventos&sec2=operation/events/events').'</link></item>'. "\n";
}

foreach ($result as $row) {
	if (!check_acl($user, $row["id_grupo"], "AR")) {
		continue;
	}
	if ($row["event_type"] == "system") {
		$agent_name = __('System');
	}
	elseif ($row["id_agente"] > 0) {
		// Agent name
		$agent_name = agents_get_name ($row["id_agente"]);
	}
	else {
		$agent_name = __('Alert').__('SNMP');
	}
	
//This is mandatory
	$rss_feed .= '<item><guid>';
	$rss_feed .= io_safe_input($url . "/index.php?sec=eventos&sec2=operation/events/events&id_event=" . $row['id_evento']);
	$rss_feed .= '</guid><title>';
	$rss_feed .= $agent_name;
	$rss_feed .= '</title><description>';
	$rss_feed .= $row['evento'];
	if($row['estado'] == 1) {
		$rss_feed .= io_safe_input('<br /><br />'.'Validated by ' . $row['id_usuario']);
	}
	$rss_feed .= '</description><link>';
	$rss_feed .= io_safe_input($url . "/index.php?sec=eventos&sec2=operation/events/events&id_event=" . $row["id_evento"]);
	$rss_feed .= '</link>';

//The rest is optional
	$rss_feed .= '<pubDate>' . date(DATE_RFC822, $row['utimestamp']) . '</pubDate>';
	
//This is mandatory again
	$rss_feed .= '</item>' . "\n";
}

$rss_feed .= "</channel>\n</rss>\n";

echo $rss_feed;
?>
