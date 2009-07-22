<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

require ("include/config.php");

$searchGraphs = $searchAgents = (check_acl ($config['id_user'], 0, "AW") || check_acl ($config['id_user'], 0, "AR"));
$linkEditUser = check_acl ($config['id_user'], 0, "UM");
$searchMaps = give_acl ($config["id_user"], 0, "AR");

$arrayKeywords = explode(' ', $config['search_keywords']);
$temp = array();
foreach($arrayKeywords as $keyword)
	array_push($temp, "%" . $keyword . "%");
$stringSearchSQL = implode(" ",$temp);

if ($config['search_category'] == "all") $searchTab = "agents";
else $searchTab = $config['search_category'];

//INI SECURITY ACL
if ((!$searchAgents) && ($searchTab == 'agents')) $searchTab = "users";

if ((!$searchGraphs) && ($searchTab == 'graphs')) $searchTab = "users"; 
if ((!$searchMaps) && ($searchTab == 'maps')) $searchTab = "users";
//END SECURITY ACL

$agents = false;
if ($searchTab == 'agents') {
	if ($searchAgents) {
		$sql = "SELECT id_agente, tagente.nombre
			FROM tagente
				INNER JOIN tgrupo
					ON tgrupo.id_grupo = tagente.id_grupo
			WHERE tagente.nombre LIKE '%" . $stringSearchSQL . "%' OR
				tgrupo.nombre LIKE '%" . $stringSearchSQL . "%'";
		$agents = process_sql($sql);
	}
}

$users = false;
if ($searchTab == 'users') {
	$sql = "SELECT id_user, fullname, firstname, lastname, middlename, email FROM tusuario
		WHERE fullname LIKE '%" . $stringSearchSQL . "%' OR
			firstname LIKE '%" . $stringSearchSQL . "%' OR
			lastname LIKE '%" . $stringSearchSQL . "%' OR
			middlename LIKE '%" . $stringSearchSQL . "%' OR
			email LIKE '%" . $stringSearchSQL . "%'";
	$users = process_sql($sql);
}

$alerts = false;
if ($searchTab == 'alerts') {
	//TODO: NOT IS CORRECT QUERY...TUNE AND CLEAN
	$sql = "SELECT *
		FROM
			(SELECT id,
				(SELECT t.nombre 
					FROM tagente_modulo AS t WHERE t.id_agente_modulo = id_agent_module AND t.nombre LIKE '%" . $stringSearchSQL . "%') AS agent_name ,
				(SELECT t.name FROM talert_templates AS t WHERE t.id = id_alert_template AND t.name LIKE '%" . $stringSearchSQL . "%') AS template_name,
				(SELECT (SELECT t1.name FROM talert_actions AS t1 WHERE t1.id = t2.id_alert_action AND t1.name LIKE '%" . $stringSearchSQL . "%') 
					FROM talert_template_module_actions AS t2
					WHERE t2.id_alert_template_module = id) AS action_name
				FROM talert_template_modules
			) AS t
		WHERE t.agent_name IS NOT NULL
			OR t.template_name IS NOT NULL
			OR t.action_name IS NOT NULL";
	$alerts = process_sql($sql);
}

$graphs = false;
if ($searchTab == 'graphs') {
	if ($searchGraphs) {
		$sql = "SELECT id_graph, name FROM tgraph WHERE name LIKE '%" . $stringSearchSQL . "%'";
		$graphs = process_sql($sql);
	}
}

$reports = false;
if (($config['search_category'] == 'all') || ($config['search_category'] == 'reports')) {
	$sql = "SELECT id_report, name FROM treport WHERE name LIKE '%" . $stringSearchSQL . "%'";
	$reports = process_sql($sql);
}

$maps = false;
if (($config['search_category'] == 'all') || ($config['search_category'] == 'maps')) {
	if ($searchMaps) {
		$sql = "SELECT id, name FROM tlayout WHERE name LIKE '%" . $stringSearchSQL . "%'";
		$maps = process_sql($sql);
	}
}

echo "
<div id='menu_tab_frame_view'>
	<div id='menu_tab_left'>
		<ul class='mn'>
			<li class='view'>" . __('Search') . ": \"" . $config['search_keywords'] . "\"</li>
		</ul>
	</div>
	<div id='menu_tab'>
		<ul class='mn'>";
		
if ($searchAgents)
{
	if ($searchTab == "agents")
		echo "<li class='nomn_high'>";
	else
		echo "<li class='nomn'>";
	echo "<a href='?search_category=agents&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".__('Agents')." </a>";
	echo "</li>";
}

if ($searchTab == "users")
	echo "<li class='nomn_high'>";
else
	echo "<li class='nomn'>";
echo "<a href='?search_category=users&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".__('Users')." </a>";
echo "</li>";

if ($searchTab == "alerts")
	echo "<li class='nomn_high'>";
else
	echo "<li class='nomn'>";
echo "<a href='?search_category=alerts&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".__('Alerts')." </a>";
echo "</li>";

if ($searchGraphs)
{
	if ($searchTab == "graphs")
		echo "<li class='nomn_high'>";
	else
		echo "<li class='nomn'>";
	echo "<a href='?search_category=graphs&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".__('Graphs')." </a>";
	echo "</li>";
}


if ($searchTab == "reports")
	echo "<li class='nomn_high'>";
else
	echo "<li class='nomn'>";
echo "<a href='?search_category=reports&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".__('Reports')." </a>";
echo "</li>";

if ($searchMaps)
{
	if ($searchTab == "maps")
		echo "<li class='nomn_high'>";
	else
		echo "<li class='nomn'>";
	echo "<a href='?search_category=maps&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".__('Maps')." </a>";
	echo "</li>";
}

echo "</ul>
</div>
</div>";
echo "<div style='height: 25px'> </div>";

if (($agents === false) && ($users === false) && ($alerts === false) && ($graphs === false)
	&& ($reports === false) && ($maps === false)) {
		echo "<h2>" . __("None results") . "</h2>\n";
}
else {
	if ($agents !== false) {
		echo "<ul>\n";
		foreach ($agents as $agent) {
			echo "<li><a href='?sec=estado&sec2=operation/agentes/ver_agente&id_agente=" . $agent['id_agente'] . "'>" . $agent['nombre'] . "</a></li>\n";
		}
		echo "</ul>\n";
	}
	
	if ($users !== false) {
		echo "<ul>\n";
		foreach ($users as $user) {
			if ($linkEditUser)
				echo "<li><a href='?sec=gusuarios&sec2=godmode/users/configure_user&id=" .
					$user['id_user'] . "'>" . $user['fullname'] . "</a> (<a href='mailto:" . $user['email'] . "'>" . $user['email'] . "</a>)</li>\n";
			else
				echo "<li>" . $user['fullname'] . " (<a href='mailto:" . $user['email'] . "'>" . $user['email'] . "</a>)</li>\n";
		}
		echo "</ul>\n";
	}
	
	if ($alerts !== false) {
		echo "<ul>\n";
		foreach ($alerts as $alert) {
			echo "<li>" . $alert['agent_name'] . " - " . $alert['template_name'] . " - " . $alert['action_name'] . "</li>\n";
		}
		echo "</ul>\n";
	}

	if ($graphs !== false) {
		echo "<ul>\n";
		foreach ($graphs as $graph) {
			echo "<li><a href='?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id=" .
				$graph['id_graph'] . "'>" . $graph['name'] . "</a></li>\n";
		}
		echo "</ul>\n";
	}
	
	if ($reports !== false) {
		echo "<ul>\n";
		foreach ($reports as $report) {
			echo "<li>" . $report['name'] . "</li>\n";
		}
		echo "</ul>\n";
	}
		
	if ($maps !== false) {
		//echo "<h3 style='border-bottom: 1px #778866 solid;'>" . __("Maps") . "</h3>\n";
		echo "<ul>\n";
		foreach ($maps as $map) {
			echo "<li><a href='?sec=visualc&sec2=operation/visual_console/render_view&id=" .
				$map['id'] . "'>" . $map['name'] . "</a></li>\n";
		}
		echo "</ul>\n";
	}	
}
?>
