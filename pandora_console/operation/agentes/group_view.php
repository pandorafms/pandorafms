<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
require_once ("include/config.php");
require_once ("include/functions_reporting.php");

check_login ();
// ACL Check
if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation", 
	"Trying to access Agent view (Grouped)");
	require ("general/noaccess.php");
	exit;
}

// Update network modules for this group
// Check for Network FLAG change request
// Made it a subquery, much faster on both the database and server side
if (isset ($_GET["update_netgroup"])) {
	$group = get_parameter_get ("update_netgroup", 0);
	if (give_acl ($config['id_user'], $group, "AW")) {
		$sql = sprintf ("UPDATE tagente_modulo SET `flag` = 1 WHERE `id_agente` = ANY(SELECT id_agente FROM tagente WHERE `id_grupo` = %d)",$group);
		process_sql ($sql);
	} else {
		audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation", "Trying to set flag for groups");
		require ("general/noaccess.php");
		exit;
	}
}

// Get group list that user has access
$groups = get_user_groups ($config['id_user']);


if ($config["realtimestats"] == 0){
	$updated_time = __('Last update'). " : ". print_timestamp (get_db_sql ("SELECT min(utimestamp) FROM tgroup_stat"), true);
} else {
	$updated_time = __("Updated at realtime");
}

// Header
print_page_header (__("Group view"), "images/bricks.png", false, "", false, $updated_time );


// Init vars
$groups_info = array ();
$counter = 1;

// Old style table, we need a lot of special formatting,don't use table function
// Prepare old-style table


foreach ($groups as $id_group) {
	$data = get_group_stats($id_group);

	if ($data["total_agents"] == 0) {
		$agents = 0;
	} else {
		$agents = 1;
	}
}
if ($agents == 1) {

echo '<table cellpadding="0" cellspacing="0" border="0" width="98%">';

echo "<tr>";
echo "<th width=5%>";
echo "<th width='20%'>".__("Group")."</th>";
echo "<th>";
echo "<th width='10%'>".__("Agents")."</th>";
echo "<th width='10%'>".__("Agent unknown")."</th>";
echo "<th width='10%'>".__("Unknown")."</th>";
echo "<th width='10%'>".__("Not Init")."</th>";
echo "<th width='10%'>".__("Normal")."</th>";
echo "<th width='10%'>".__("Warning")."</th>";
echo "<th width='10%'>".__("Critical")."</th>";
echo "<th width='10%'>".__("Alert fired")."</th>";

// For each valid group for this user, take data from agent and modules
foreach ($groups as $id_group => $group_name) {
	if ($id_group < 1) 
		continue; // Skip group 0

	// Get stats for this group
	$data = get_group_stats($id_group);

	if ($data["total_agents"] == 0)
		continue; // Skip empty groups

	// Calculate entire row color
	if ($data["monitor_alerts_fired"] > 0){
		echo "<tr style='background-color: #ffb4e9; height: 35px; '>";
	} elseif ($data["monitor_critical"] > 0) {
		echo "<tr style='background-color: #ffc0b5; height: 35px;'>";
	} elseif ($data["monitor_warning"] > 0) {
		echo "<tr style='background-color: #f4ffbf; height: 35px;'>";
	} elseif (($data["monitor_unknown"] > 0) ||  ($data["agents_unknown"] > 0)) {
		echo "<tr style='background-color: #ddd; height: 35px;'>";
	} elseif ($data["monitor_ok"] > 0)  {
		echo "<tr style='background-color: #bbffa4; height: 35px;'>";
	} else {
		echo "<tr style='height: 35px;'>";
	}

	// Group name
	echo "<td>";
	echo print_group_icon ($id_group, true);
	echo "</td>";
	echo "<td style='font-weight: bold; font-size: 12px;'>";
	echo "<a href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=$id_group'>";
	echo $group_name;
	echo "</a>";
	echo "</td>";
	echo "<td>";
	if (give_acl ($config['id_user'], $id_group, "AW")) {
		echo '&nbsp;<a href="index.php?sec=estado&sec2=operation/agentes/group_view&update_netgroup='.$id_group.'"><img src="images/target.png" align="right"></a>';
	}
	echo "</td>";

	// Total agents
	echo "<td style='font-weight: bold; font-size: 18px;'>";
	echo "&nbsp;&nbsp;";
	if ($data["total_agents"] > 0)
		echo $data["total_agents"];

	// Agents unknown
	if ($data["agents_unknown"] > 0) {
		echo "<td style='font-weight: bold; font-size: 18px; color: #886666;'>";
		echo $data["agents_unknown"];
		echo "</td>";
	} else {
		echo "<td></td>";
	}

	// Monitors Unknown
	if ($data["monitor_unknown"] > 0){
		echo "<td style='font-weight: bold; font-size: 18px; color: #666;'>";
		echo $data["monitor_unknown"];
		echo "</td>";
	} else {
		echo "<td></td>";
	}


	// Monitors NonInit
	if ($data["monitor_non_init"] > 0){
		echo "<td style='font-weight: bold; font-size: 18px; color #666;'>";
		echo $data["monitor_non_init"];
		echo "</td>";
	} else {
		echo "<td></td>";
	}


	// Monitors OK
	echo "<td style='font-weight: bold; font-size: 18px; color: #6ec300;'>";
	if ($data["monitor_ok"] > 0)
		echo $data["monitor_ok"];
	else
		echo "&nbsp;";
	echo "</td>";

	// Monitors Warning
	if ($data["monitor_warning"] > 0){
		echo "<td style='font-weight: bold; font-size: 18px; color: #aba900;'>";
		echo $data["monitor_warning"];
		echo "</td>";
	} else {
		echo "<td></td>";
	}

	// Monitors Critical
	if ($data["monitor_critical"] > 0){
		echo "<td style='font-weight: bold; font-size: 18px; color: #bc0000'>";
		echo $data["monitor_critical"];
		echo "</td>";
	} else {
		echo "<td></td>";
	}
	// Alerts fired
	if ($data["monitor_alerts_fired"] > 0){
		echo "<td style='font-weight: bold; font-size: 18px; color: #8b01ae;'>";
		echo $data["monitor_alerts_fired"];
		echo "</td>";
	} else {
		echo "<td></td>";
	}


	echo "</tr>";
	echo "<tr style='height: 5px;'><td colspan=10> </td></tr>";
}

echo "</table>";

} else {
	echo "<div class='nf'>".__('There are no defined agents')."</div>";
}

?>

