<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

define('ALL', -1);
define('NORMAL', 0);
define('WARNING', 2);
define('CRITICAL', 1);
define('UNKNOWN', 3);
define('NOT_INIT', 5);


global $config;

require_once ($config['homedir'] . '/include/functions_treeview.php');

if (defined ('METACONSOLE')) {
	// For each server defined:
	$servers = db_get_all_rows_sql ("SELECT *
		FROM tmetaconsole_setup
		WHERE disabled = 0");
	if ($servers === false) {
		$servers = array();
	}
}

if (is_ajax ()) {
	require_once ($config['homedir'] . '/include/functions_reporting.php');
	require_once ($config['homedir'] . '/include/functions_users.php');
	require_once ($config['homedir'] . '/include/functions_servers.php');
	
	global $config;
	
	$enterpriseEnable = false;
	if (enterprise_include_once('include/functions_policies.php') !== ENTERPRISE_NOT_HOOK) {
		$enterpriseEnable = true;
		require_once ('enterprise/include/functions_policies.php');
		require_once ('enterprise/meta/include/functions_ui_meta.php');
	}
	
	$type = get_parameter('type');
	$id = get_parameter('id');
	$id_father = get_parameter('id_father');
	$statusSel = get_parameter('status');
	$search_free = get_parameter('search_free', '');
	$printTable = get_parameter('printTable', 0);
	$printAlertsTable = get_parameter('printAlertsTable', 0);
	$printModuleTable = get_parameter('printModuleTable', 0);
	$server_name = get_parameter('server_name', '');
	$server = array();
	if ($printTable) {
		$id_agente = get_parameter('id_agente');
		if (defined ('METACONSOLE')) {
			$server = metaconsole_get_connection ($server_name);
			metaconsole_connect($server);
		}
		
		treeview_printTable($id_agente, $server);
		
		if (defined ('METACONSOLE')) {
			metaconsole_restore_db();
		}
	}
	if ($printAlertsTable) {
		$id_module = get_parameter('id_module');
		
		if (defined ('METACONSOLE')) {
			$server = metaconsole_get_connection ($server_name);
			metaconsole_connect($server);
		}
		
		treeview_printAlertsTable($id_module, $server);
		
		if (defined ('METACONSOLE')) {
			metaconsole_restore_db();
		}
	}
	if ($printModuleTable) {
		$id_module = get_parameter('id_module');
		$id_agent = get_parameter('id_agent');
		
		if (defined ('METACONSOLE')) {
			$server = metaconsole_get_connection ($server_name);
			metaconsole_connect($server);
		}
		
		treeview_printTable($id_agent, $server);
		treeview_printModuleTable($id_module, $server);
		
		
		if (defined ('METACONSOLE')) {
			metaconsole_restore_db();
		}
	}
	
	/*
	 * It's a binary for branch (0 show - 1 hide)
	 * and there are 2 position
	 * 0 0 - show 2 branch
	 * 0 1 - hide the 2º branch
	 * 1 0 - hide the 1º branch
	 * 1 1 - hide 2 branch
	*/
	$lessBranchs = get_parameter('less_branchs');
	
	switch ($type) {
		case 'group':
		case 'os':
		case 'module_group':
		case 'policies':
		case 'module':
		case 'tag':
			
			$countRows = 0;
			if (! defined ('METACONSOLE')) {
				$avariableGroups = users_get_groups();
				$avariableGroupsIds = array_keys($avariableGroups);
				$sql = treeview_getFirstBranchSQL ($type, $id, $avariableGroupsIds, $statusSel, $search_free);
				
				if ($sql === false) {
					$rows = array ();
				}
				else {
					$rows = db_get_all_rows_sql($sql);
				}
			}
			else {
				$rows = array ();
				foreach ($servers as $server) {
					if (metaconsole_connect($server) != NOERR) {
						continue;
					}
					$avariableGroups = users_get_groups();
					$avariableGroupsIds = array_keys($avariableGroups);
					$sql = treeview_getFirstBranchSQL ($type, $id,
						$avariableGroupsIds, $statusSel, $search_free);
					if ($sql === false) {
						$server_rows = array ();
					}
					else {
						$server_rows = db_get_all_rows_sql($sql);
						if ($server_rows === false) {
							$server_rows = array ();
						}
					}
					// Add the server name
					foreach ($server_rows as $key => $row) {
						$server_rows[$key]['server_name'] = $server['server_name'];
					}
					$rows = array_merge($rows, $server_rows);
				}
				metaconsole_restore_db();
			}
			$countRows = count ($rows);
			
			//Empty Branch
			if ($countRows === 0) {
				echo "<ul style='margin: 0; padding: 0;'>\n";
				echo "<li style='margin: 0; padding: 0;'>";
				if ($lessBranchs == 1)
					echo html_print_image ("operation/tree/no_branch.png", true, array ("style" => 'vertical-align: middle;'));
				else
					echo html_print_image ("operation/tree/branch.png", true, array ("style" => 'vertical-align: middle;'));
				echo "<i>" . __("Empty") . "</i>";
				echo "</li>";
				echo "</ul>";
				return;
			}
			
			//Branch with items
			$count = 0;
			echo "<ul style='margin: 0; padding: 0;'>\n";
			
			foreach ($rows as $row) {
				$count++;
				
				$agent_info["monitor_alertsfired"] = $row["fired_count"];
				$agent_info["monitor_critical"] = $row["critical_count"];
				$agent_info["monitor_warning"] = $row["warning_count"];
				$agent_info["monitor_unknown"] = $row["unknown_count"];
				$agent_info["monitor_normal"] = $row["normal_count"];
				$agent_info["monitor_notinit"] = $row["notinit_count"];
				$agent_info["modules"] = $row["total_count"];
				
				$agent_info["alert_img"] = agents_tree_view_alert_img ($agent_info["monitor_alertsfired"]);
				$agent_info["status_img"] = agents_tree_view_status_img(
					$agent_info["monitor_critical"],
					$agent_info["monitor_warning"],
					$agent_info["monitor_unknown"],
					$agent_info["modules"],
					$agent_info["monitor_notinit"]);
				
				// Filter by status (only in policy view)
				if ($type == 'policies') {
					
					if ($statusSel == NORMAL) {
						if (strpos($agent_info["status_img"], 'ok') === false)
							continue;
					}
					else if ($statusSel == WARNING) {
						if (strpos($agent_info["status_img"], 'warning') === false)
							continue;
					}
					else if ($statusSel == CRITICAL) {
						if (strpos($agent_info["status_img"], 'critical') === false)
							continue;
					}
					else if ($statusSel == UNKNOWN) {
						if (strpos($agent_info["status_img"], 'down') === false)
							continue;
					}
				}
				
				$less = $lessBranchs;
				$tree_img_id = "tree_image" . $id . "_agent_" . $type . "_" . $row["server_name"] . "_" . $row["id_agente"];
				if ($count != $countRows)
					$img = html_print_image ("operation/tree/closed.png", true, array ("style" => 'vertical-align: middle;', "id" => $tree_img_id, "pos_tree" => "2"));
				else {
					$less = $less + 2; // $less = $less or 0b10
					$img = html_print_image ("operation/tree/last_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => $tree_img_id, "pos_tree" => "3"));
				}
				echo "<li style='margin: 0; padding: 0;'>";
				echo "<a onfocus='JavaScript: this.blur()'
					href='javascript: loadSubTree(\"agent_" . $type . "\"," . $row["id_agente"] . ", " . $less . ", \"" . $id . "\", \"" . $row["server_name"] . "\")'>";
				
				if ($lessBranchs == 1)
					html_print_image ("operation/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
				else
					html_print_image ("operation/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
				
				echo $img;
				echo "</a>";
				echo " ";
				echo str_replace('.png' ,'_ball.png',
						str_replace('img', 'img style="vertical-align: middle;"', $agent_info["status_img"])
					);
				echo " ";
				echo str_replace('.png' ,'_ball.png', 
						str_replace('img', 'img style="vertical-align: middle;"', $agent_info["alert_img"])
					);
				echo "<a onfocus='JavaScript: this.blur()'
					href='javascript: loadTable(\"agent_" . $type . "\"," . $row["id_agente"] . ", " . $less . ", \"" . $id . "\", \"" . $row['server_name'] . "\")'>";
				echo " ";
				
				echo ui_print_truncate_text($row["nombre"], 40, true);
				
				echo " (" . reporting_tiny_stats($row, true) . ")";
				
				if ($row['quiet']) {
					echo "&nbsp;";
					html_print_image("images/dot_green.disabled.png", false, array("border" => '0', "title" => __('Quiet'), "alt" => ""));
				}
				echo "</a>";
				echo "<div hiddenDiv='1' loadDiv='0' style='margin: 0px; padding: 0px;' class='tree_view' id='tree_div" . $id . "_agent_" . $type . "_" . $row["server_name"] . "_" . $row["id_agente"] . "'></div>";
				echo "</li>";
			}
			
			echo "</ul>\n";
			break;
		
		//also aknolegment as second subtree/branch
		case 'agent_group': 
		case 'agent_module_group':  
		case 'agent_os':
		case 'agent_policies':
		case 'agent_module':
		case 'agent_tag':
			$fatherType = str_replace('agent_', '', $type);
			
			if (defined ('METACONSOLE')) {
				$server = metaconsole_get_connection ($server_name);
				if (metaconsole_connect($server) != NOERR) {
					continue;
				}
			}
			
			$sql = treeview_getSecondBranchSQL ($fatherType, $id, $id_father);
			$rows = db_get_all_rows_sql($sql);
			if (empty($rows)) {
				$rows = array();
			}
			$countRows = count ($rows);
			
			if ($countRows === 0) {
				echo "<ul style='margin: 0; padding: 0;'>\n";
				echo "<li style='margin: 0; padding: 0;'>";
				switch ($lessBranchs) {
					case 0:
						html_print_image ("operation/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
						html_print_image ("operation/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
						break;
					case 1:
						html_print_image ("operation/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
						html_print_image ("operation/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
						break;
					case 2:
						html_print_image ("operation/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
						html_print_image ("operation/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
						break;
					case 3:
						html_print_image ("operation/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
						html_print_image ("operation/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
						break;
				}
				echo "<i>" . __("Empty") . "</i>";
				echo "</li>";
				echo "</ul>";
				return;
			}
			
			$count = 0;
			echo "<ul style='margin: 0; padding: 0;'>\n";
			foreach ($rows as $row) {
				$count++;
				echo "<li style='margin: 0; padding: 0;'><span style='min-width: 300px; display: inline-block;'>";
				
				switch ($lessBranchs) {
					case 0:
						html_print_image ("operation/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
						html_print_image ("operation/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
						break;
					case 1:
						html_print_image ("operation/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
						html_print_image ("operation/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
						break;
					case 2:
						html_print_image ("operation/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
						html_print_image ("operation/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
						break;
					case 3:
						html_print_image ("operation/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
						html_print_image ("operation/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
						break;
				}
				
				if ($countRows != $count)
					html_print_image ("operation/tree/leaf.png", false, array ("style" => 'vertical-align: middle;', "id" => "tree_image_os_" . $row["id_agente"], "pos_tree" => "1" ));
				else
					html_print_image ("operation/tree/last_leaf.png", false, array ("style" => 'vertical-align: middle;', "id" => "tree_image_os_" . $row["id_agente"], "pos_tree" => "2" ));
				
				// Assign image and status depend on the status data
				switch ($row["estado"]) {
					case AGENT_MODULE_STATUS_NO_DATA:
					case AGENT_MODULE_STATUS_UNKNOWN:
						$status = STATUS_MODULE_NO_DATA;
						$title = __('UNKNOWN');
						break;
					case AGENT_MODULE_STATUS_CRITICAL_BAD:
						$status = STATUS_MODULE_CRITICAL;
						$title = __('CRITICAL');
						break;
					case AGENT_MODULE_STATUS_WARNING:
						$status = STATUS_MODULE_WARNING;
						$title = __('WARNING');
						break;
					default:
						$status = STATUS_MODULE_OK;
						$title = __('NORMAL');
						break;
				}
				
				if (is_numeric($row["datos"])) {
					$title .= " : " . format_for_graph($row["datos"]);
				}
				else {
					$title .= " : " . substr(io_safe_output($row["datos"]),0,42);
				}
				
				echo str_replace('.png' ,'_ball.png', 
					str_replace('img', 'img style="vertical-align: middle;"', ui_print_status_image($status, $title,true))
					);
				echo " ";
				echo str_replace('img', 'img style="vertical-align: middle;"', servers_show_type ($row['id_modulo']));
				echo " ";
				$graph_type = return_graphtype ($row["id_tipo_modulo"]);
				$win_handle=dechex(crc32($row["id_agente_modulo"] . $row["nombre"]));
				
				if (defined ('METACONSOLE')) {
					$console_url = $server['server_url'] . '/';
				}
				else {
					$console_url = '';
				}
				
				
				//Icon and link to the Module graph.
				if (defined('METACONSOLE')) {
					$url_module_graph = ui_meta_get_url_console_child(
						$server, null, null, null, null,
						"operation/agentes/stat_win.php?" .
						"type=$graph_type&" .
						"period=86400&" .
						"id=" . $row["id_agente_modulo"] . "&" .
						"label=" . rawurlencode(urlencode(base64_encode($row["nombre"]))) . "&" .
						"refresh=600");
				}
				else {
					$url_module_graph = $console_url .
						"operation/agentes/stat_win.php?" .
						"type=$graph_type&" .
						"period=86400&" .
						"id=" . $row["id_agente_modulo"] . "&" .
						"label=" . rawurlencode(urlencode(base64_encode($row["nombre"]))) . "&" .
						"refresh=600";
				}
				$link ="winopeng('" . $url_module_graph . "','day_".$win_handle."')";
				echo '<a href="javascript: '.$link.'">' . html_print_image ("images/chart_curve.png", true, array ("style" => 'vertical-align: middle;', "border" => "0" )) . '</a>';
				
				
				echo " ";
				
				
				//Icon and link to the Module data.
				if (defined('METACONSOLE')) {
					
					$url_module_data =  ui_meta_get_url_console_child(
						$server,
						"estado", "operation/agentes/ver_agente",
						"id_agente=" . $row['id_agente'] . "&" .
						"tab=data_view&" .
						"period=86400&" .
						"id=" . $row["id_agente_modulo"]);
				}
				else {
					$url_module_data = $console_url .
						"index.php?" .
						"sec=estado&" .
						"sec2=operation/agentes/ver_agente&" .
						"id_agente=" . $row['id_agente'] . "&" .
						"tab=data_view&" .
						"period=86400&" .
						"id=" . $row["id_agente_modulo"];
				}
				echo "<a href='javascript: show_module_detail_dialog(" . $row["id_agente_modulo"] . ", ". $row['id_agente'].", \"" . $server_name . "\", 0, 86400)'>". html_print_image ("images/binary.png", true, array ("style" => 'vertical-align: middle;', "border" => "0" )) . "</a>";
				
				echo " ";
				
				$nmodule_alerts = db_get_value_sql(sprintf("SELECT count(*) FROM talert_template_modules WHERE id_agent_module = %s", $row["id_agente_modulo"]));
				
				if($nmodule_alerts > 0) {
					echo "<a onfocus='JavaScript: this.blur()' href='javascript: loadAlertsTable(" . $row["id_agente_modulo"] . ", \"" . $server_name . "\")'>";
					echo html_print_image ("images/bell.png", true, array ("style" => 'vertical-align: middle;', "border" => "0", "title" => __('Module alerts') ));
					echo "</a>";
					
					echo " ";
				}
				
				echo "<a style='vertical-align: middle;' onfocus='JavaScript: this.blur()' href='javascript: loadModuleTable(" . $row["id_agente_modulo"] . ", \"" . $server_name . "\"". "," . $id.")'>";
				echo ui_print_truncate_text(io_safe_output($row['nombre']), 40, true);
				echo "</a>";
				if ($row['quiet']) {
					echo "&nbsp;";
					html_print_image("images/dot_green.disabled.png", false, array("border" => '0', "title" => __('Quiet'), "alt" => ""));
				}
				
				/*
				if (is_numeric($row["datos"]))
					$data = format_numeric($row["datos"]);
				else
					$data = "<span title='".$row['datos']."' style='white-space: nowrap;'>".substr(io_safe_output($row["datos"]),0,12)."</span>";
				
				echo "</span><span style='margin-left: 20px;'>";
					if ($row['utimestamp'] != '') {
						ui_print_help_tip ($row["timestamp"], '', 'images/clock2.png');
						echo "&nbsp;";
					}
					echo $data;
					if ($row['unit'] != '') {
						echo "&nbsp;";
						echo '('.$row['unit'].')';
					}
					* */
				echo "</span></li>";
			}
			echo "</ul>\n";
			if (defined ('METACONSOLE')) {
				metaconsole_restore_db_force();
			}
			break;
	}
	
	return;
}
//End of AJAX code.

include_once($config['homedir'] . "/include/functions_groups.php");
include_once($config['homedir'] . "/include/functions_os.php");
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_servers.php");
include_once($config['homedir'] . "/include/functions_reporting.php");
include_once($config['homedir'] . "/include/functions_ui.php");

global $config;
$pure = get_parameter('pure', 0);

$enterpriseEnable = false;
if (enterprise_include_once('include/functions_policies.php') !== ENTERPRISE_NOT_HOOK) {
	$enterpriseEnable = true;
}

///////// INI MENU AND TABS /////////////
$img_style = array ("class" => "top", "width" => 16);
$activeTab = get_parameter('sort_by','group');

$os_tab = array('text' => "<a href='index.php?sec=estado&sec2=operation/tree&refr=0&sort_by=os&pure=$pure'>"
	. html_print_image ("images/operating_system.png", true, array ("title" => __('OS'))) . "</a>", 'active' => $activeTab == "os");

$group_tab = array('text' => "<a href='index.php?sec=estado&sec2=operation/tree&refr=0&sort_by=group&pure=$pure'>"
	. html_print_image ("images/group.png", true, array ("title" => __('Groups'))) . "</a>", 'active' => $activeTab == "group");

$module_group_tab = array('text' => "<a href='index.php?sec=estado&sec2=operation/tree&refr=0&sort_by=module_group&pure=$pure'>"
	. html_print_image ("images/module_group.png", true, array ("title" => __('Module groups'))) . "</a>", 'active' => $activeTab == "module_group");

if ($enterpriseEnable) {
	$policies_tab = array('text' => "<a href='index.php?sec=estado&sec2=operation/tree&refr=0&sort_by=policies&pure=$pure'>"
		. html_print_image ("images/policies_mc.png", true, array ("title" => __('Policies'))) . "</a>", 'active' => $activeTab == "policies");
}
else {
	$policies_tab = '';
}

$module_tab = array('text' => "<a href='index.php?extension_in_menu=estado&sec=estado&sec2=operation/tree&refr=0&sort_by=module&pure=$pure'>"
	. html_print_image("images/brick.png",
		true,
		array("title" => __('Modules'))) . "</a>",
		'active' => $activeTab == "module");

$tags_tab = array('text' => "<a href='index.php?&sec=monitoring&sec2=operation/tree&refr=0&sort_by=tag&pure=$pure'>"
	. html_print_image("images/tag.png",
		true,
		array("title" => __('Tags'))) . "</a>",
		'active' => $activeTab == "tag");

switch ($activeTab) {
	case 'group':
		$order = __('groups');
		break;
	case 'module_group':
		$order = __('module groups');
		break;
	case 'policies':
		$order = __('policies');
		break;
	case 'module':
		$order = __('modules');
		break;
	case 'os':
		$order = __('OS');
		break;
	case 'tag':
		$order = __('tags');
		break;
}

if (! defined ('METACONSOLE')) {
	$onheader = array('tag' => $tags_tab,
		'os' => $os_tab,
		'group' => $group_tab,
		'module_group' => $module_group_tab,
		'policies' => $policies_tab,
		'module' => $module_tab);
	
	ui_print_page_header(
		__('Tree view') . " - " . __('Sort the agents by ') . $order,
		"images/extensions.png",
		false, "", false, $onheader);
}
else {
	
	ui_meta_add_breadcrumb(array(
		'link' => 'index.php?sec=monitoring&sec2=operation/tree',
		'text' => __('Tree View')));
	ui_meta_print_page_header($nav_bar);
	
	$img_style = array ("class" => "top", "width" => 16);
	$activeTab = get_parameter('tab','group');
	
	// Check if the loaded tab is allowed or not
	$allowed_tabs = array('group');
	
	if ($config['enable_tags_tree']) {
		$allowed_tabs[] = 'tag';
	}
	
	if (!in_array($activeTab, $allowed_tabs)) {
		db_pandora_audit("HACK Attempt",
			"Trying to access to not allowed tab on tree view");
		include ("general/noaccess.php");
		
		exit;
	}
	// End of tab check
	
	$group_tab = array('text' => "<a href='index.php?sec=monitoring&sec2=operation/tree&refr=0&tab=group&pure=$pure'>"
		. html_print_image ("images/group.png", true,
		array ("title" => __('Groups'))) . "</a>",
		'active' => $activeTab == "group");
	
	$subsections['group'] = $group_tab; 
	
	if ($config['enable_tags_tree']) {
		$tags_tab = array(
			'text' => "<a href='index.php?&sec=monitoring&sec2=operation/tree&refr=0&tab=tag&pure=$pure'>" .
			html_print_image ("images/tag.png", true,
			array ("title" => __('Tags'))) . "</a>",
			'active' => $activeTab == "tag");
		
		$subsections['tag'] = $tags_tab;
	}
	
	switch ($activeTab) {
		case 'group':
			$subsection = __('Groups');
			$tab = 'group';
			break;
		case 'tag':
			$subsection =  __('Tags');
			$tab = 'tag';
			break;
	}
	ui_meta_print_header(__("Tree view"), $subsection, $subsections);
}

enterprise_hook('open_meta_frame');

if (tags_has_user_acl_tags()) {
	ui_print_tags_warning();
}

echo "<br>";
if (! defined ('METACONSOLE')) {
	echo '<form id="tree_search" method="post" action="index.php?extension_in_menu=estado&sec=estado&sec2=operation/tree&refr=0&sort_by='.$activeTab.'&pure='.$pure.'">';
}
else {
	echo '<form id="tree_search" method="post" action="index.php?sec=monitoring&sec2=operation/tree&refr=0&tab='.$activeTab.'&pure='.$pure.'">';
}

echo "<b>" . __('Agent status') . "</b>";

$search_free = get_parameter('search_free', '');
$select_status = get_parameter('status', -1);

$fields = array ();
$fields[ALL] = __('All'); //default
$fields[NORMAL] = __('Normal'); 
$fields[WARNING] = __('Warning');
$fields[CRITICAL] = __('Critical');
$fields[UNKNOWN] = __('Unknown');
$fields[NOT_INIT] = __('Not init');

html_print_select ($fields, "status", $select_status);

echo "&nbsp;&nbsp;&nbsp;";
echo "<b>" . __('Search agent') . "</b>";
echo "&nbsp;";
html_print_input_text ("search_free", $search_free, '', 40,30, false);
echo "&nbsp;&nbsp;&nbsp;";
html_print_submit_button (__('Show'), "uptbutton", false, 'class="sub search"');
echo "</form>";
echo "<div class='pepito' id='a'></div>";
echo "<div class='pepito' id='b'></div>";
echo "<div class='pepito' id='c'></div>";
///////// END MENU AND TABS /////////////

echo "<div id='module_details_window'></div>";
ui_require_javascript_file('pandora_modules');

treeview_printTree($activeTab);


enterprise_hook('close_meta_frame');

ui_include_time_picker();
ui_require_jquery_file("ui.datepicker-" . get_user_language(), "include/javascript/i18n/");

?>

<script language="javascript" type="text/javascript">
	
	var status = $('#status').val();
	var search_free = $('#text-search_free').val();
	
	/**
	 * loadSubTree asincronous load ajax the agents or modules (pass type, id to search and binary structure of branch),
	 * change the [+] or [-] image (with same more or less div id) of tree and anime (for show or hide)
	 * the div with id "div[id_father]_[type]_[div_id]"
	 *
	 * type string use in js and ajax php
	 * div_id int use in js and ajax php
	 * less_branchs int use in ajax php as binary structure 0b00, 0b01, 0b10 and 0b11
	 * id_father int use in js and ajax php, its useful when you have a two subtrees with same agent for diferent each one
	 */
	function loadSubTree(type, div_id, less_branchs, id_father, server_name) {
		var id = id_father + '_' + type + '_' + server_name + '_' + div_id;
		var hiddenDiv = $('#tree_div' + id).attr('hiddenDiv');
		var loadDiv = $('#tree_div' + id).attr('loadDiv');
		
		var pos = parseInt($('#tree_image' + id).attr('pos_tree'));
		
		//If has yet ajax request running
		if (loadDiv == 2)
			return;
		
		if (loadDiv == 0) {
			
			//Put an spinner to simulate loading process
			$('#tree_div' + id)
				.html("<img style='padding-top:10px;padding-bottom:10px;padding-left:20px;' src=images/spinner.gif>");
			$('#tree_div' + id)
				.show('normal');
			
			$('#tree_div'+id).attr('loadDiv', 2);
			$.ajax({
				type: "POST",
				url: <?php echo '"' . ui_get_full_url("ajax.php", false, false, false) . '"'; ?>,
				data: {
					"page": "operation/tree",
					"ajax_treeview": 1,
					"type": type,
					"id": div_id,
					"less_branchs": less_branchs,
					"id_father": id_father,
					"status": status,
					"search_free": search_free,
					"server_name": server_name
				},
				success: function(msg) {
					if (msg.length != 0) {
						$('#tree_div'+id).hide();
						$('#tree_div'+id).html(msg);
						$('#tree_div'+id).show('normal');
						
						//change image of tree [+] to [-]
						<?php if (! defined ('METACONSOLE')) {
							echo 'var icon_path = \'operation/tree\';';
						}
						else {
							echo 'var icon_path = \'../../operation/tree\';';
						}
						?>
						switch (pos) {
							case 0:
								$('#tree_image'+id).attr('src',icon_path+'/first_expanded.png');
								break;
							case 1:
								$('#tree_image'+id).attr('src',icon_path+'/one_expanded.png');
								break;
							case 2:
								$('#tree_image'+id).attr('src',icon_path+'/expanded.png');
								break;
							case 3:
								$('#tree_image'+id).attr('src',icon_path+'/last_expanded.png');
								break;
						}
						$('#tree_div'+id).attr('hiddendiv',0);
						$('#tree_div'+id).attr('loadDiv', 1);
					}
					
					// Refresh forced title callback to work with html code created dinamicly
					forced_title_callback();
				}
			});
		}
		else {
			<?php
			if (! defined ('METACONSOLE')) {
				echo 'var icon_path = \'operation/tree\';';
			}
			else {
				echo 'var icon_path = \'../../operation/tree\';';
			}
			?>
			if (hiddenDiv == 0) {
				$('#tree_div'+id).hide('normal');
				$('#tree_div'+id).attr('hiddenDiv',1);
				
				//change image of tree [-] to [+]
				switch (pos) {
					case 0:
						$('#tree_image'+id).attr('src',icon_path+'/first_closed.png');
						break;
					case 1:
						$('#tree_image'+id).attr('src',icon_path+'/one_closed.png');
						break;
					case 2:
						$('#tree_image'+id).attr('src',icon_path+'/closed.png');
						break;
					case 3:
						$('#tree_image'+id).attr('src',icon_path+'/last_closed.png');
						break;
				}
			}
			else {
				//change image of tree [+] to [-]
				switch (pos) {
					case 0:
						$('#tree_image'+id).attr('src',icon_path+'/first_expanded.png');
						break;
					case 1:
						$('#tree_image'+id).attr('src',icon_path+'/one_expanded.png');
						break;
					case 2:
						$('#tree_image'+id).attr('src',icon_path+'/expanded.png');
						break;
					case 3:
						$('#tree_image'+id).attr('src',icon_path+'/last_expanded.png');
						break;
				}
				
				$('#tree_div'+id).show('normal');
				$('#tree_div'+id).attr('hiddenDiv',0);
			}
		}
	}
	
	function changeStatus(newStatus) {
		status = newStatus;
		
		//reset all subtree
		$(".tree_view").each(
			function(i) {
				$(this).attr('loadDiv', 0);
				$(this).attr('hiddenDiv',1);
				$(this).hide();
			}
		);
		
		//clean all subtree
		$(".tree_view").each(
			function(i) {
				$(this).html('');
			}
		);
	}
	
	function loadTable(type, div_id, less_branchs, id_father, server_name) {
		id_agent = div_id;
		$.ajax({
			type: "POST",
			url: <?php echo '"' . ui_get_full_url("ajax.php", false, false, false) . '"'; ?>,
			data: "page=<?php echo $_GET['sec2']; ?>&printTable=1&id_agente=" + id_agent + "&server_name=" + server_name,
			success: function(data) {
				$('#cont').html(data);
				forced_title_callback();
			}
		});
		
		loadSubTree(type, div_id, less_branchs, id_father, server_name);
	}
	
	function loadAlertsTable(id_module, server_name) {
		$.ajax({
			type: "POST",
			url: <?php echo '"' . ui_get_full_url("ajax.php", false, false, false) . '"'; ?>,
			data: "page=<?php echo $_GET['sec2']; ?>&printAlertsTable=1&id_module=" + id_module + "&server_name=" + server_name,
			success: function(data) {
				$('#cont').html(data);
				forced_title_callback();
			}
		});
	}
	
	function loadModuleTable(id_module, server_name, id_agent) {
		$.ajax({
			type: "POST",
			url: <?php echo '"' . ui_get_full_url("ajax.php", false, false, false) . '"'; ?>,
			data: "page=<?php echo $_GET['sec2']; ?>&printModuleTable=1&id_module=" + id_module + "&server_name=" + server_name +"&id_agent="+id_agent,
			success: function(data) {
				$('#cont').html(data);
				forced_title_callback();
			}
		});
	}
	
	// Show the modal window of an module
	function show_module_detail_dialog(module_id, id_agent, server_name, offset, period) {
		var extra_parameters = '';
		if (period == -1) {
			period = $('#period').val();
			var selection_mode = $('input[name=selection_mode]:checked').val();
			var date_from = $('#text-date_from').val();
			var time_from = $('#text-time_from').val();
			var date_to = $('#text-date_to').val();
			var time_to = $('#text-time_to').val();
			
			extra_parameters = '&selection_mode=' + selection_mode + '&date_from=' + date_from + '&date_to=' + date_to + '&time_from=' + time_from + '&time_to=' + time_to;
		}
		
		$.ajax({
			type: "POST",
			url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
			data: "page=include/ajax/module&get_module_detail=1&server_name="+server_name+"&id_agent="+id_agent+"&id_module=" + module_id+"&offset="+offset+"&period="+period + extra_parameters,
			dataType: "html",
			success: function(data) {
				$("#module_details_window").hide ()
					.empty ()
					.append (data)
					.dialog ({
						resizable: true,
						draggable: true,
						modal: true,
						overlay: {
							opacity: 0.5,
							background: "black"
						},
						width: 650,
						height: 500
					})
					.show ();
					refresh_pagination_callback (module_id, id_agent, server_name);
					datetime_picker_callback();
					forced_title_callback();
			}
		});
	}
	
	function datetime_picker_callback() {
		$("#text-time_from, #text-time_to").timepicker({
			showSecond: true,
			timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
			timeOnlyTitle: '<?php echo __('Choose time');?>',
			timeText: '<?php echo __('Time');?>',
			hourText: '<?php echo __('Hour');?>',
			minuteText: '<?php echo __('Minute');?>',
			secondText: '<?php echo __('Second');?>',
			currentText: '<?php echo __('Now');?>',
			closeText: '<?php echo __('Close');?>'});
			
		$("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
		
		$.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
	}
	datetime_picker_callback();
	
	function refresh_pagination_callback (module_id, id_agent, server_name) {
		
		$(".binary_dialog").click( function() {
			
			var classes = $(this).attr('class');
			classes = classes.split(' ');
			var offset_class = classes[2];
			offset_class = offset_class.split('_');
			var offset = offset_class[1];
			
			var period = $('#period').val();
			
			show_module_detail_dialog(module_id, id_agent, server_name, offset, period);
			return false;
		});
	}
</script>
