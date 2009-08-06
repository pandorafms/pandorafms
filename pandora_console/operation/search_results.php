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
require_once ("include/functions_reporting.php");

// Load enterprise extensions
enterprise_include ('operation/reporting/custom_reporting.php');

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
		$sql = "SELECT id_agente, tagente.nombre, tagente.id_os, tagente.intervalo, tagente.id_grupo
			FROM tagente
				INNER JOIN tgrupo
					ON tgrupo.id_grupo = tagente.id_grupo
			WHERE tagente.nombre LIKE '%" . $stringSearchSQL . "%' OR
				tgrupo.nombre LIKE '%" . $stringSearchSQL . "%'
			LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
		$agents = process_sql($sql);
		
		$sql = "SELECT count(id_agente)
			FROM tagente
				INNER JOIN tgrupo
					ON tgrupo.id_grupo = tagente.id_grupo
			WHERE tagente.nombre LIKE '%" . $stringSearchSQL . "%' OR
				tgrupo.nombre LIKE '%" . $stringSearchSQL . "%'";
		$totalAgents = get_db_row_sql($sql);
		$totalAgents = $totalAgents[0];
	}
}

$users = false;
if ($searchTab == 'users') {
	$sql = "SELECT id_user, fullname, firstname, lastname, middlename, email, last_connect, is_admin, comments FROM tusuario
		WHERE fullname LIKE '%" . $stringSearchSQL . "%' OR
			firstname LIKE '%" . $stringSearchSQL . "%' OR
			lastname LIKE '%" . $stringSearchSQL . "%' OR
			middlename LIKE '%" . $stringSearchSQL . "%' OR
			email LIKE '%" . $stringSearchSQL . "%'
		LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	$users = process_sql($sql);
	
	$sql = "SELECT COUNT(id_user) FROM tusuario
		WHERE fullname LIKE '%" . $stringSearchSQL . "%' OR
			firstname LIKE '%" . $stringSearchSQL . "%' OR
			lastname LIKE '%" . $stringSearchSQL . "%' OR
			middlename LIKE '%" . $stringSearchSQL . "%' OR
			email LIKE '%" . $stringSearchSQL . "%'";	
	$totalUsers = get_db_row_sql($sql);
	$totalUsers = $totalUsers[0];
}

$alerts = false;
if ($searchTab == 'alerts') {			
	$sql = "SELECT t1.disabled, t3.id_agente, t3.nombre AS agent_name, t2.nombre AS module_name, t4.name AS template_name,
				(SELECT GROUP_CONCAT(t6.name) 
					FROM talert_template_module_actions AS t5 
						INNER JOIN talert_actions AS t6 ON t6.id = t5.id_alert_action 
						WHERE t5.id_alert_template_module = t1.id) AS actions
			FROM talert_template_modules AS t1
				INNER JOIN tagente_modulo AS t2
				ON t1.id_agent_module = t2.id_agente_modulo
				INNER JOIN tagente AS t3
				ON t2.id_agente = t3.id_agente
				INNER JOIN talert_templates AS t4
				ON t1.id_alert_template = t4.id
				LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	$alerts = process_sql($sql);
	
	$sql = "SELECT COUNT(t1.id)
			FROM talert_template_modules AS t1
				INNER JOIN tagente_modulo AS t2
				ON t1.id_agent_module = t2.id_agente_modulo
				INNER JOIN tagente AS t3
				ON t2.id_agente = t3.id_agente
				INNER JOIN talert_templates AS t4
				ON t1.id_alert_template = t4.id";
	$totalAlerts = get_db_row_sql($sql);
	$totalAlerts = $totalAlerts[0];
	
}

$graphs = false;
if ($searchTab == 'graphs') {
	if ($searchGraphs) {
		$sql = "SELECT id_graph, name, description FROM tgraph WHERE name LIKE '%" . $stringSearchSQL . "%' OR description LIKE '%" . $stringSearchSQL . "%'
			LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
		$graphs = process_sql($sql);
		
		$sql = "SELECT COUNT(id_graph) FROM tgraph WHERE name LIKE '%" . $stringSearchSQL . "%' OR description LIKE '%" . $stringSearchSQL . "%'";
		$totalGraphs = get_db_row_sql($sql);
		$totalGraphs = $totalGraphs[0];
	}
}

$reports = false;
if (($config['search_category'] == 'all') || ($config['search_category'] == 'reports')) {
	$sql = "SELECT id_report, name, description FROM treport WHERE name LIKE '%" . $stringSearchSQL . "%'
		LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	$reports = process_sql($sql);
	
	$sql = "SELECT COUNT(id_report) FROM treport WHERE name LIKE '%" . $stringSearchSQL . "%'";
	$totalReports = get_db_row_sql($sql);
	$totalReports = $totalReports[0];
}

$maps = false;
if (($config['search_category'] == 'all') || ($config['search_category'] == 'maps')) {
	if ($searchMaps) {
		$sql = "SELECT t1.id, t1.name, t1.id_group,
				(SELECT COUNT(*) FROM tlayout_data AS t2 WHERE t2.id_layout = t1.id) AS count 
			FROM tlayout AS t1 WHERE t1.name LIKE '%" . $stringSearchSQL . "%'
			LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
		$maps = process_sql($sql);
		
		$sql = "SELECT COUNT(id) FROM tlayout WHERE name LIKE '%" . $stringSearchSQL . "%'";
		$totalMaps = get_db_row_sql($sql);
		$totalMaps = $totalMaps[0];
	}
}

/////////	INI MENU AND TABS /////////////

$img_style = array ("class" => "top", "width" => 16);

/*
echo '<div id="menu_tab_frame"><div id="menu_tab_left"><ul class="mn">';
	echo '<li class="nomn"><a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$id_agente.'">';
	print_image ("images/setup.png", false, $img_style);
	echo '&nbsp; '.mb_substr (get_agent_name ($id_agente), 0, 21).'</a>';
	echo "</li></ul></div>";

	echo '<div id="menu_tab"><ul class="mn"><li class="nomn">';
	echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'">';
	print_image ("images/zoom.png", false, $img_style);
	echo '&nbsp;'.__('View').'</a></li>';
*/




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
	echo "<a href='?search_category=agents&keywords=".$config['search_keywords']."&head_search_keywords=Search'>";
	print_image ("images/bricks.png", false, $img_style);
	echo "&nbsp;".__('Agents')."</a>";
	echo "</li>";
}

if ($searchTab == "users")
	echo "<li class='nomn_high'>";
else
	echo "<li class='nomn'>";
echo "<a href='?search_category=users&keywords=".$config['search_keywords']. "&head_search_keywords=Search'> ";
print_image ("images/group.png", false, $img_style);
echo "&nbsp;".__('Users')."</a>";
echo "</li>";

if ($searchTab == "alerts")
	echo "<li class='nomn_high'>";
else
	echo "<li class='nomn'>";
echo "<a href='?search_category=alerts&keywords=".$config['search_keywords']."&head_search_keywords=Search'> ";
print_image ("images/god2.png", false, $img_style);
echo "&nbsp;".__('Alerts')."</a>";
echo "</li>";

if ($searchGraphs)
{
	if ($searchTab == "graphs")
		echo "<li class='nomn_high'>";
	else
		echo "<li class='nomn'>";
	echo "<a href='?search_category=graphs&keywords=".$config['search_keywords']."&head_search_keywords=Search'> ";
	print_image ("images/chart_curve.png", false, $img_style);
	echo "&nbsp;".__('Graphs'). "</a>";
	echo "</li>";
}


if ($searchTab == "reports")
	echo "<li class='nomn_high'>";
else
	echo "<li class='nomn'>";
echo "<a href='?search_category=reports&keywords=".$config['search_keywords']."&head_search_keywords=Search'> ";
print_image ("images/reporting.png", false, $img_style);
echo "&nbsp;".__('Reports')."</a>";
echo "</li>";

if ($searchMaps)
{
	if ($searchTab == "maps")
		echo "<li class='nomn_high'>";
	else
		echo "<li class='nomn'>";
	echo "<a href='?search_category=maps&keywords=".$config['search_keywords']."&head_search_keywords=Search'> ";
	print_image ("images/camera.png", false, $img_style);
	echo "&nbsp;".__('Maps')."</a>";
	echo "</li>";
}

echo "</ul>
</div>
</div>";
echo "<div style='height: 25px'> </div>";

/////////	END MENU AND TABS /////////////

if (($agents === false) && ($users === false) && ($alerts === false) && ($graphs === false)
	&& ($reports === false) && ($maps === false)) {
		echo "<h2>" . __("None results") . "</h2>\n";
}
else {
	if ($agents !== false) {
		
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = "98%";
		$table->class = "databox";
		
		$table->head = array ();
		$table->head[0] = __('Agent');
		$table->head[1] = __('OS');
		$table->head[2] = __('Interval');
		$table->head[3] = __('Group');
		$table->head[4] = __('Modules');
		$table->head[5] = __('Status');
		$table->head[6] = __('Alerts');
		$table->head[7] = __('Last contact');
		
		$table->align = array ();
		$table->align[0] = "left";
		$table->align[1] = "center";
		$table->align[2] = "center";
		$table->align[3] = "center";
		$table->align[4] = "center";
		$table->align[5] = "center";
		$table->align[6] = "center";
		$table->align[7] = "right";
		
		$table->data = array ();
		
		foreach ($agents as $agent) {
			$agent_info = get_agent_module_info ($agent["id_agente"]);
			
			$modulesCell = '<b>'. $agent_info["modules"] . '</b>';
			if ($agent_info["monitor_normal"] > 0)
				$modulesCell .= '</b> : <span class="green">'.$agent_info["monitor_normal"].'</span>';
			if ($agent_info["monitor_warning"] > 0)
				$modulesCell .= ' : <span class="yellow">'.$agent_info["monitor_warning"].'</span>';
			if ($agent_info["monitor_critical"] > 0)
				$modulesCell .= ' : <span class="red">'.$agent_info["monitor_critical"].'</span>';
			if ($agent_info["monitor_down"] > 0)
				$modulesCell .= ' : <span class="grey">'.$agent_info["monitor_down"].'</span>';
			
			array_push($table->data, array(
				print_agent_name ($agent["id_agente"], true, "upper"),
				print_os_icon ($agent["id_os"], false, true),
				(($agent_info["interval"] > $agent["intervalo"]) ? $agent_info["interval"]  : $agent['intervalo']),
				print_group_icon ($agent["id_grupo"], true),
				$modulesCell,
				$agent_info["status_img"],
				$agent_info["alert_img"],
				print_timestamp ($agent_info["last_contact"], true)));
		}
		
		echo "<br />";pagination ($totalAgents);
		print_table ($table); unset($table);
		pagination ($totalAgents);
	}
	
	if ($users !== false) {
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = "98%";
		$table->class = "databox";
		
		$table->head = array ();
		$table->head[0] = __('User ID');
		$table->head[1] = __('Name');
		$table->head[2] = __('Email');
		$table->head[3] = __('Last contact');
		$table->head[4] = __('Profile');
		$table->head[5] = __('Description');

		$table->data = array ();
		
		foreach ($users as $user) {
			if ($linkEditUser)
				$userIDCell = "<a href='?sec=gusuarios&sec2=godmode/users/configure_user&id=" .
					$user['id_user'] . "'>" . $user['id_user'] . "</a>";
			else
				$userIDCell = $user['id_user'];
			
			if ($user["is_admin"]) {
				$profileCell = print_image ("images/user_suit.png", true,
				array ("alt" => __('Admin'),
					"title" => __('Administrator'))).'&nbsp;';
			} else {
				$profileCell = print_image ("images/user_green.png", true,
				array ("alt" => __('User'),
					"title" => __('Standard User'))).'&nbsp;';
			}
			$profileCell .= '<a href="#" class="tip"><span>';
			$result = get_db_all_rows_field_filter ("tusuario_perfil", "id_usuario", $user['id_user']);
			if ($result !== false) {
				foreach ($result as $row) {
					$profileCell .= get_profile_name ($row["id_perfil"]);
					$profileCell .= " / ";
					$profileCell .= get_group_name ($row["id_grupo"]);
					$profileCell .= "<br />";
				}
			} else {
				$profileCell .= __('The user doesn\'t have any assigned profile/group');
			}
			$profileCell .= "</span></a>";
			
			array_push($table->data, array(
				$userIDCell,
				$user['fullname'],
				"<a href='mailto:" . $user['email'] . "'>" . $user['email'] . "</a>",
				print_timestamp ($user["last_connect"], true),
				$profileCell,
				$user['comments']));
		}

		echo "<br />";pagination ($totalUsers);
		print_table ($table); unset($table);
		pagination ($totalUsers);
	}
	
	if ($alerts !== false) {
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = "98%";
		$table->class = "databox";
		
		$table->head = array ();
		$table->head[0] = '';
		$table->head[1] = __('Agent');
		$table->head[2] = __('Module');
		$table->head[3] = __('Template');
		$table->head[4] = __('Action');
		
		$table->align = array ();
		$table->align[0] = "center";
		$table->align[1] = "left";
		$table->align[2] = "left";
		$table->align[3] = "left";
		$table->align[4] = "left";
		
		$table->valign = array ();
		$table->valign[0] = "top";
		$table->valign[1] = "top";
		$table->valign[2] = "top";
		$table->valign[3] = "top";
		$table->valign[4] = "top";
		
		$table->data = array ();
		foreach ($alerts as $alert) {
			if ($alert['disabled'])
				$disabledCell = print_image ('images/lightbulb_off.png', true, array('title' => 'disable', 'alt' => 'disable'));
			else
				$disabledCell = print_image ('images/lightbulb.png', true, array('alt' => 'enable', 'title' => 'enable'));
			
			$actionCell = '';
			if (strlen($alert["actions"]) > 0) {
				$arrayActions = explode(',', $alert["actions"]);
				$actionCell = '<ul class="action_list">';
				foreach ($arrayActions as $action)
					$actionCell .= '<li><div><span class="action_name">' . $action . '</span></div><br /></li>';
				$actionCell .= '</ul>';
			}
			
			
			array_push($table->data, array(
			$disabledCell,
			print_agent_name ($alert["id_agente"], true, "upper"),
			$alert["module_name"],
			$alert["template_name"],$actionCell
			));
		}
		
		echo "<br />";pagination ($totalAlerts);
		print_table ($table); unset($table);
		pagination ($totalAlerts);
	}

	if ($graphs !== false) {
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = "98%";
		$table->class = "databox";
		
		$table->head = array ();
		$table->head[0] = __('Graph name');
		$table->head[1] = __('Description');

		
		$table->align = array ();
		$table->align[1] = "center";
		$table->align[2] = "center";
		
		$table->data = array ();
		foreach ($graphs as $graph) {
			array_push($table->data, array(
				"<a href='?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id=" .
					$graph['id_graph'] . "'>" . $graph['name'] . "</a>",
				$graph['description']
			));
		}
		
		echo "<br />";pagination ($totalGraphs);
		print_table ($table); unset($table);
		pagination ($totalGraphs);
	}
	
	if ($reports !== false) {
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = "98%";
		$table->class = "databox";
		
		$table->head = array ();
		$table->head[0] = __('Report name');
		$table->head[1] = __('Description');
		$table->head[2] = __('HTML');
		$table->head[3] = __('XML');
		enterprise_hook ('load_custom_reporting_1');
		
		$table->align = array ();
		$table->align[0] = "center";
		$table->align[1] = "center";
		$table->align[2] = "center";
		$table->align[3] = "center";
		
		$table->data = array ();
		foreach ($reports as $report) {
			
			$data = array(
				"<a href='?sec=greporting&sec2=godmode/reporting/reporting_builder&edit_report=1&id_report=" . $report['id_report'] . "' title='" . __("Edit") . "'>" . 
					$report['name'] . "</a>",
				$report['description'],
				'<a href="index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$report['id_report'].'"><img src="images/reporting.png" /></a>',
				'<a href="ajax.php?page=operation/reporting/reporting_xml&id='.$report['id_report'].'"><img src="images/database_lightning.png" /></a>'
			);
			enterprise_hook ('load_custom_reporting_2');
			
			array_push($table->data, $data);
		}
			
		echo "<br />";pagination ($totalReports);
		print_table ($table); unset($table);
		pagination ($totalReports);
	}
		
	if ($maps !== false) {
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = "98%";
		$table->class = "databox";
		
		$table->head = array ();
		$table->head[0] = __('Name');
		$table->head[1] = __('Group');
		$table->head[2] = __('Elements');
		
		$table->align = array ();
		$table->align[0] = "center";
		$table->align[1] = "center";
		$table->align[2] = "center";
		
		$table->data = array ();
		foreach ($maps as $map) {
			array_push($table->data, array(
				"<a href='?sec=visualc&sec2=operation/visual_console/render_view&id=" .
				$map['id'] . "'>" . $map['name'] . "</a>",
				print_group_icon ($layout["id_group"], true) . "&nbsp;" . get_group_name ($layout["id_group"]),
				$map['count']
			));
		}
		
		echo "<br />";pagination ($totalMaps);
		print_table ($table); unset($table);
		pagination ($totalMaps);
	}	
}
?>
