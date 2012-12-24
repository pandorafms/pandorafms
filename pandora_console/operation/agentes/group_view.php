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
require_once ($config['homedir'] . "/include/functions_agents.php");
require_once ($config['homedir'] . '/include/functions_users.php');

check_login ();
// ACL Check
if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation", 
	"Trying to access Agent view (Grouped)");
	require ("general/noaccess.php");
	exit;
}

// Update network modules for this group
// Check for Network FLAG change request
// Made it a subquery, much faster on both the database and server side
if (isset ($_GET["update_netgroup"])) {
	$group = get_parameter_get ("update_netgroup", 0);
	if (check_acl ($config['id_user'], $group, "AW")) {
		$where = array('id_agente' => 'ANY(SELECT id_agente FROM tagente WHERE id_grupo = ' . $group . ')');
		
		db_process_sql_update('tagente_modulo', array('flag' => 1), $where);
	}
	else {
		db_pandora_audit("ACL Violation", "Trying to set flag for groups");
		require ("general/noaccess.php");
		exit;
	}
}

// Get group list that user has access
$groups_full = users_get_groups ($config['id_user'], "AR", true, true);

$groups = array();
foreach($groups_full as $group) {
	$groups[$group['id_grupo']]['name'] = $group['nombre'];

	if($group['id_grupo'] != 0) {
		$groups[$group['parent']]['childs'][] = $group['id_grupo'];
		$groups[$group['id_grupo']]['prefix'] = $groups[$group['parent']]['prefix'].'&nbsp;&nbsp;&nbsp;';
	}
	else {
		$groups[$group['id_grupo']]['prefix'] = '';
	}
	
	if(!isset($groups[$group['id_grupo']]['childs'])) {
		$groups[$group['id_grupo']]['childs'] = array();
	}
}

if ($config["realtimestats"] == 0){
	$updated_time = __('Last update'). " : ". ui_print_timestamp (db_get_sql ("SELECT min(utimestamp) FROM tgroup_stat"), true);
} else {
	$updated_time = __("Updated at realtime");
}

// Header
ui_print_page_header (__("Group view"), "images/bricks.png", false, "", false, $updated_time );


// Init vars
$groups_info = array ();
$counter = 1;

$agents = agents_get_group_agents(array_keys($groups));

if (count($agents) > 0) {

echo '<table cellpadding="0" style="margin-top:10px" cellspacing="0" border="0" width="98%">';

echo "<tr>";
echo "<th width='25%'>".__("Group")."</th>";
echo "<th>";
echo "<th width='10%'>".__("Agents")."</th>";
echo "<th width='10%'>".__("Agent unknown")."</th>";
echo "<th width='10%'>".__("Unknown")."</th>";
echo "<th width='10%'>".__("Not Init")."</th>";
echo "<th width='10%'>".__("Normal")."</th>";
echo "<th width='10%'>".__("Warning")."</th>";
echo "<th width='10%'>".__("Critical")."</th>";
echo "<th width='10%'>".__("Alert fired")."</th>";

$printed_groups = array();

// For each valid group for this user, take data from agent and modules
foreach ($groups as $id_group => $group) {
	groups_get_group_row($id_group, $groups, $group, $printed_groups);
}

echo "</table>";

}

?>

