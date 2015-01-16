<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function treeview_printModuleTable($id_module, $server_data = false) {
	global $config;
	
	if (empty($server_data)) {
		$server_name = '';
		$server_id = '';
		$url_hash = '';
		$console_url = '';
	}
	else {
		$server_name = $server_data['server_name'];
		$server_id = $server_data['id'];
		$console_url = $server_data['server_url'] . '/';
		$url_hash = metaconsole_get_servers_url_hash($server_data);
	}
	
	require_once ($config["homedir"] . "/include/functions_agents.php");
	require_once ($config["homedir"] . "/include/functions_graph.php");
	include_graphs_dependencies($config['homedir'].'/');
	require_once ($config['homedir'] . "/include/functions_groups.php");
	require_once ($config['homedir'] . "/include/functions_servers.php");
	enterprise_include_once ('meta/include/functions_modules_meta.php');
	enterprise_include_once ('meta/include/functions_ui_meta.php');
	enterprise_include_once ('meta/include/functions_metaconsole.php');
	
	$filter["id_agente_modulo"] = $id_module;
	
	$module = db_get_row_filter ("tagente_modulo", $filter);
	
	if ($module === false) {
		ui_print_error_message(__('There was a problem loading module'));
		return;
	}
	
	if (! check_acl ($config["id_user"], $module["id_grupo"], "AR")) {
		db_pandora_audit("ACL Violation", 
			"Trying to access Module Information");
		require_once ("general/noaccess.php");
		return;
	}
	
	echo '<div id="id_div3" width="450px">';
	echo '<table cellspacing="4" cellpadding="4" border="0" class="databox alternate" style="width:90%; min-width: 300px;">';
	
	//Module name
	echo '<tr><td class="datos"><b>'.__('Module name').'</b></td>';
	
	if ($module["disabled"])
		$cellName = "<em>" . ui_print_truncate_text ($module["nombre"], GENERIC_SIZE_TEXT, true, true, true, '[&hellip;]',"text-transform: uppercase;") .  ui_print_help_tip(__('Disabled'), true) . "<em>";
	else
		$cellName = ui_print_truncate_text ($module["nombre"], GENERIC_SIZE_TEXT, true, true, true, '[&hellip;]',"text-transform: uppercase;");
	
	echo '<td class="datos"><b>'.$cellName.'</b></td>';
	
	// Interval
	echo '<tr><td class="datos"><b>' . __('Interval') . '</b></td>';
	echo '<td class="datos" colspan="2">' . human_time_description_raw (modules_get_interval($module['id_agente_modulo']), true) . '</td></tr>';
	
	// Warning Min/Max
	echo '<tr><td class="datos"><b>' . __('Warning status') . '</b></td>';
	if (modules_is_string_type($module['id_tipo_modulo'])) {
		echo '<td class="datos" colspan="2">' . __('Str.') . ': ' . $module['str_warning'] . '</td></tr>';
	}
	else {
		echo '<td class="datos" colspan="2">' . __('Min.') . ': ' . $module['min_warning'] . '<br>' . __('Max.') . ': ' . $module['max_warning'] . '</td></tr>';
	}
	
	// Critical Min/Max
	echo '<tr><td class="datos"><b>' . __('Critical status') . '</b></td>';
	if (modules_is_string_type($module['id_tipo_modulo'])) {
		echo '<td class="datos" colspan="2">' . __('Str.') . ': ' . $module['str_warning'] . '</td></tr>';
	}
	else {
		echo '<td class="datos" colspan="2">' . __('Min.') . ': ' . $module['min_critical'] . '<br>' . __('Max.') . ': ' . $module['max_critical'] . '</td></tr>';
	}
	
	// Module group
	echo '<tr><td class="datos2"><b>'.__('Module group').'</b></td>';
	echo '<td class="datos2" colspan="2">';
	$module_group = modules_get_modulegroup_name($module['id_module_group']);
	
	if ($module_group === false)
		echo __('Not assigned');
	else
		echo __("$module_group");
	echo '</td></tr>';
	
	// Description
	echo '<tr><td class="datos"><b>'.__('Description').'</b></td>';
	echo '<td class="datos" colspan="2">'. ui_print_truncate_text ($module['descripcion'], 'description', true, true, true, '[&hellip;]') .'</td></tr>';
	
	// Tags
	$tags = tags_get_module_tags($module['id_agente_modulo']);
	
	if (empty($tags)) {
		$tags = array();
	}
	
	$user_tags = tags_get_user_tags($config["id_user"]);
	
	foreach ($tags as $k => $v) {
		if (!array_key_exists($v, $user_tags)) { //only show user's tags.
			unset($tags[$k]);
		} else {
			$tag_name = tags_get_name($v);
			if (empty($tag_name)) {
				unset($tags[$k]);
			}
			else {
				$tags[$k] = $tag_name;
			}
		}
	}
	
	if (empty($tags)) {
		$tags = '<i>' . __('N/A') . '</i>';
	}
	else {
		$tags = implode(', ' , $tags);
	}
	
	echo '<tr><td class="datos"><b>'.__('Tags').'</b></td>';
	echo '<td class="datos" colspan="2">' . $tags . '</td></tr>';
	
	// Data
	$last_data = db_get_row_filter ('tagente_estado', array('id_agente_modulo' => $module['id_agente_modulo'], 'order' => array('field' => 'id_agente_estado', 'order' => 'DESC')));
	if (is_numeric($last_data["datos"]))
		$data = "<span style='height: 20px; display: inline-table; vertical-align: top;'>" . format_numeric($last_data["datos"]) . "</span>";
	else
		$data = "<span title='" . $last_data["datos"] . "' style='white-space: nowrap;'>" . substr(io_safe_output($last_data['datos']),0,12) . "</span>";
	
	echo '<tr><td class="datos"><b>'.__('Last data').'</b></td>';
	echo '<td class="datos" colspan="2">';
	
	if (!empty($last_data['utimestamp'])) {
		echo $data;
		
		if ($module['unit'] != '') {
			echo "&nbsp;";
			echo '('.$module['unit'].')';
		}
		
		echo "&nbsp;";
		html_print_image('images/clock2.png', false, array('title' => $last_data["timestamp"], 'width' => '18px'));
	}
	else {
		echo '<i>' . __('No data') . '</i>';
	}
	
	echo '</td></tr>';
						
	//End of table
	echo '</table></div>';
	
	$id_group = agents_get_agent_group($module['id_agente']);
	$group_name = db_get_value('nombre', 'tgrupo', 'id_grupo', $id_group);	
	$agent_name = db_get_value('nombre', 'tagente', 'id_agente', $module['id_agente']);	
	
	if (can_user_access_node () && check_acl ($config["id_user"], $id_group, 'AW')) {
		// Actions table
		echo '<div style="width:90%; text-align: right; min-width: 300px;">';
		echo '<form id="module_detail" method="post" action="' . $console_url . 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=' . $module['id_agente'] . '&tab=module&edit_module=1&id_agent_module=' . $module['id_agente_modulo'] . $url_hash . '">';
			html_print_submit_button (__('Go to module edition'), 'upd_button', false, 'class="sub config"');
		echo '</form>';

		echo '</div>';
	}

	//id_module and id_agent hidden
	echo '<div id="ids" style="display:none;">';
		html_print_input_text('id_module', $id_module);
		html_print_input_text('id_agent', $module['id_agente']);
		html_print_input_text('server_name', $server_name);
	echo '</div>';
	
	return;
}

function treeview_printAlertsTable($id_module, $server_data = array()) {
	global $config;
	
	if (empty($server_data)) {
		$server_name = '';
		$server_id = '';
		$url_hash = '';
		$console_url = '';
	}
	else {
		$server_name = $server_data['server_name'];
		$server_id = $server_data['id'];
		$console_url = $server_data['server_url'] . '/';
		$url_hash = metaconsole_get_servers_url_hash($server_data);
	}
	
	$module_alerts = alerts_get_alerts_agent_module($id_module);
	$module_name = db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $id_module);
	$agent_id = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);
	$id_group = agents_get_agent_group($agent_id);

	if ($module_alerts === false) {
		ui_print_error_message(__('There was a problem loading alerts'));
		return;
	}
	
	echo '<div id="id_div3" width="450px">';
	echo '<table cellspacing="4" cellpadding="4" border="0" class="databox alternate" style="width:90%; min-width: 300px;">';
	echo '<tr><th colspan=2 class="datos"><center>' . $module_name . '</center></th></tr>';
	
	echo '<tr><th class="datos" style="background: #B3B3B3;"><b>'.__('Template').'</b></th>';
	echo '<th class="datos" style="background: #B3B3B3;"><b>'.__('Actions').'</b></th>';
	
	foreach($module_alerts as $module_alert) {
		//Template name
		echo '<tr>';
		$template_name = db_get_value('name','talert_templates','id',$module_alert['id_alert_template']);
		echo '<td class="datos">'.$template_name.'</td>';
		$actions = alerts_get_alert_agent_module_actions($module_alert['id']);
		echo '<td class="datos">'; 
		if (empty($actions)) {
			echo '<i>'.__('N/A').'</i>';
		}
		else {
			echo '<ul>';
			foreach($actions as $act) {
				echo '<li>';
					echo $act['name'];
				echo '</li>';
			}
			echo '</ul>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	
	if (can_user_access_node () && check_acl ($config["id_user"], $id_group, 'LW')) {
		// Actions table
		echo '<div style="width:90%; text-align: right; min-width: 300px;">';
		echo '<form id="agent_detail" method="post" action="' . $console_url . 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&search=1&module_name=' . $module_name . '&id_agente=' . $agent_id . $url_hash . '" target="_blank">';
			html_print_submit_button (__('Go to alerts edition'), 'upd_button', false, 'class="sub search"');
		echo '</form>';
		echo '</div>';
	}
}

function treeview_printTable($id_agente, $server_data = array()) {
	global $config;
	
	if (empty($server_data)) {
		$server_name = '';
		$server_id = '';
		$url_hash = '';
		$console_url = '';
	}
	else {
		$server_name = $server_data['server_name'];
		$server_id = $server_data['id'];
		$console_url = $server_data['server_url'] . '/';
		$url_hash = metaconsole_get_servers_url_hash($server_data);
	}
	
	require_once ("include/functions_agents.php");
	require_once ($config["homedir"] . '/include/functions_graph.php');
	include_graphs_dependencies();
	require_once ($config['homedir'] . '/include/functions_groups.php');
	require_once ($config['homedir'] . '/include/functions_gis.php');
	
	$agent = db_get_row ("tagente", "id_agente", $id_agente);
	
	if ($agent === false) {
		ui_print_error_message(__('There was a problem loading agent'));
		return;
	}
	
	$is_extra = enterprise_hook('policies_is_agent_extra_policy', array($id_agente));
	
	if ($is_extra === ENTERPRISE_NOT_HOOK) {
		$is_extra = false;
	}
	
	if (! check_acl ($config["id_user"], $agent["id_grupo"], "AR") && !$is_extra) {
		db_pandora_audit("ACL Violation", 
			"Trying to access Agent General Information");
		require_once ("general/noaccess.php");
		return;
	}
	
	echo '<div id="id_div3" width="450px">';
	echo '<table cellspacing="4" cellpadding="4" border="0" class="databox alternate" style="width:90%; min-width: 300px;">';
	//Agent name
	echo '<tr><td class="datos"><b>'.__('Agent name').'</b></td>';
	
	if ($agent['disabled']) {
		$cellName = "<em>";
	}
	else {
		$cellName = '';
	}
		
	$cellName .= ui_print_agent_name ($agent["id_agente"], true, 500, "text-transform: uppercase;", true, $console_url, $url_hash, false, can_user_access_node ());
	
	if ($agent['disabled']) {
		$cellName .= ui_print_help_tip(__('Disabled'), true) . "</em>";
	}
	
	echo '<td class="datos"><b>'.$cellName.'</b></td>';
	
	//Addresses
	echo '<tr><td class="datos2"><b>'.__('IP Address').'</b></td>';
	echo '<td class="datos2" colspan="2">';
	$ips = array();
	$addresses = agents_get_addresses ($id_agente);
	$address = agents_get_address($id_agente);
	
	foreach ($addresses as $k => $add) {
		if ($add == $address) {
			unset($addresses[$k]);
		}
	}
	
	echo $address;
	
	if (!empty($addresses)) {
		ui_print_help_tip(__('Other IP addresses').': <br>'.implode('<br>',$addresses));
	}
	
	echo '</td></tr>';
	
	// Agent Interval
	echo '<tr><td class="datos"><b>'.__('Interval').'</b></td>';
	echo '<td class="datos" colspan="2">'.human_time_description_raw ($agent["intervalo"]).'</td></tr>';
	
	// Comments
	echo '<tr><td class="datos2"><b>' . __('Description') . '</b></td>';
	echo '<td class="datos2" colspan="2">' . $agent["comentarios"] . '</td></tr>';
	
	
	// Last contact
	echo '<tr><td class="datos2"><b>' . __('Last contact') . " / " . __('Remote') . '</b></td><td class="datos2 f9" colspan="2">';
	
	ui_print_timestamp ($agent["ultimo_contacto"]);
	
	echo " / ";
	
	if ($agent["ultimo_contacto_remoto"] == "01-01-1970 00:00:00") { 
		echo __('Never');
	}
	else {
		ui_print_timestamp ($agent["ultimo_contacto_remoto"]);
	}
	echo '</td></tr>';
	
	// Next contact (agent)
	$progress = agents_get_next_contact($id_agente);
	
	echo '<tr><td class="datos"><b>'.__('Next agent contact').'</b></td>';
	echo '<td class="datos f9" colspan="2">' . progress_bar($progress, 150, 20) . '</td></tr>';
	
	//End of table
	echo '</table></div>';
	
	if (can_user_access_node () && check_acl ($config["id_user"], $agent["id_grupo"], "AW")) {
		echo '<div style="width:90%; text-align: right; min-width: 300px;">';
		echo '<form id="agent_detail" method="post" action="' . $console_url . 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.$url_hash.'">';
				html_print_submit_button (__('Go to agent edition'), 'upd_button', false, 'class="sub config"');
		echo '</form>';
		echo '</div>';
	}
	
	// Advanced data
	$advanced = '<div id="id_div3" width="450px">';
	$advanced .= '<table cellspacing="4" cellpadding="4" border="0" class="databox alternate" style="width:90%;">';
	
	// Agent version
	$advanced .= '<tr><td class="datos2"><b>'.__('Agent Version'). '</b></td>';
	$advanced .= '<td class="datos2" colspan="2">'.$agent["agent_version"].'</td></tr>';
	
	// Position Information
	if ($config['activate_gis']) {
		$dataPositionAgent = gis_get_data_last_position_agent($agent['id_agente']);
		
		$advanced .= '<tr><td class="datos2"><b>'.__('Position (Long, Lat)'). '</b></td>';
		$advanced .= '<td class="datos2" colspan="2">';
		
		if ($dataPositionAgent === false) {
			$advanced .= __('There is no GIS data.');
		}
		else {
			$advanced .= '<a href="' . $console_url . 'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente='.$id_agente.'">';
			if ($dataPositionAgent['description'] != "")
				$advanced .= $dataPositionAgent['description'];
			else
				$advanced .= $dataPositionAgent['stored_longitude'].', '.$dataPositionAgent['stored_latitude'];
			$advanced .= "</a>";
		}
		
		$advanced .= '</td></tr>';
	}
	
	// If the url description is setted
	if ($agent['url_address'] != '') {
		$advanced .= '<tr><td class="datos"><b>'.__('Url address').'</b></td>';	
		$advanced .= '<td class="datos2" colspan="2"><a href='.$agent["url_address"].'>' . $agent["url_address"] . '</a></td></tr>';
	}
	
	// Timezone Offset
	if ($agent['timezone_offset'] != 0) {
		$advanced .= '<tr><td class="datos2"><b>'.__('Timezone Offset'). '</b></td>';
		$advanced .= '<td class="datos2" colspan="2">'.$agent["timezone_offset"].'</td></tr>';
	}
	
	// Custom fields
	$fields = db_get_all_rows_filter('tagent_custom_fields', array('display_on_front' => 1));
	if ($fields === false) {
		$fields = array ();
	}
	if ($fields) {
		foreach ($fields as $field) {
			$advanced .= '<tr><td class="datos"><b>'.$field['name'] . ui_print_help_tip (__('Custom field'), true).'</b></td>';
			$custom_value = db_get_value_filter('description', 'tagent_custom_data', array('id_field' => $field['id_field'], 'id_agent' => $id_agente));
			if ($custom_value === false || $custom_value == '') {
				$custom_value = '<i>-'.__('empty').'-</i>';
			}
			$advanced .= '<td class="datos f9" colspan="2">'.$custom_value.'</td></tr>';
		}
	}
	
	//End of table advanced
	$advanced .= '</table></div><br>';
	
	ui_toggle($advanced, __('Advanced information'));
	
	// Blank space below title, DONT remove this, this
	// Breaks the layout when Flash charts are enabled :-o
	//echo '<div id="id_div" style="height: 10px">&nbsp;</div>';	
			
	if ($config["agentaccess"]) {
		$access_graph = '<div style="width: 290px; margin-left: 30px;">';
		$access_graph .= graphic_agentaccess($id_agente, 290, 110, 86400, true);
		$access_graph .= '</div><br>';
				
		ui_toggle($access_graph, __('Agent access rate (24h)'));
	}
	
	$events_graph = '<div style="width: 290px; height: 15px; margin-left: 30px; position: static;">';
	$events_graph .= graph_graphic_agentevents ($id_agente, 290, 15, 86400, '', true);
	$events_graph .= '</div><br><br>';
	
	ui_toggle($events_graph, __('Events (24h)'));
		
	echo '<br>';
	
	return;
}

function treeview_printTree($type) {
	global $config;
	
	echo '<table class="databox" style="width:98%">';
	echo '<tr><td style="width:50%" valign="top">';
	
	
	
	if (! defined ('METACONSOLE')) {
		$list = treeview_getData ($type);
	}
	else {
		$servers = db_get_all_rows_sql ("
			SELECT *
			FROM tmetaconsole_setup
			WHERE disabled = 0");
		
		if ($servers === false) {
			$servers = array();
		}
		
		$list = array ();
		foreach ($servers as $server) {
			if (metaconsole_connect($server) != NOERR) {
				continue;
			}
			
			$server_list = treeview_getData ($type);
			
			foreach ($server_list as $server_item) {
				if (! isset ($list[$server_item['_name_']])) {
					$list[$server_item['_name_']] = $server_item;
				}
				// Merge!
				else {
					$list[$server_item['_name_']]['_num_ok_'] += $server_item['_num_ok_'];
					$list[$server_item['_name_']]['_num_critical_'] += $server_item['_num_critical_'];
					$list[$server_item['_name_']]['_num_warning_'] += $server_item['_num_warning_'];
					$list[$server_item['_name_']]['_num_unknown_'] += $server_item['_num_unknown_'];
				}
			}
		}
		
		metaconsole_restore_db();
	}
	
	
	
	if ($list === false) {
		ui_print_error_message(__('There aren\'t agents in this agrupation'));
		echo '</td></tr>';
		echo '</table>';
	}
	else {
		echo "<ul style='margin: 0; margin-top: 10px; padding: 0;'>\n";
		
		$first = true;
		foreach ($list as $item) {
			$lessBranchs = 0;
			$tree_img_id = "tree_image_" . $type . "_" . $item['_id_'];
			if ($first) {
				if ($item != end($list)) {
					$img = html_print_image ("operation/tree/first_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => $tree_image_id, "pos_tree" => "0"));
					$first = false;
				}
				else {
					$lessBranchs = 1;
					$img = html_print_image ("operation/tree/one_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => $tree_img_id, "pos_tree" => "1"));
				}
			}
			else {
				if ($item != end($list))
					$img = html_print_image ("operation/tree/closed.png", true, array ("style" => 'vertical-align: middle;', "id" => $tree_img_id, "pos_tree" => "2"));
				else
				{
					$lessBranchs = 1;
					$img = html_print_image ("operation/tree/last_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => $tree_img_id, "pos_tree" => "3"));
				}
			}
			
			echo "<li style='margin: 0px 0px 0px 0px;'>";
			
			// Convert the id to hexadecimal, since it will be used as a div id
			if (defined ('METACONSOLE')) {
				$id = unpack ('H*', $item['_id_']);
				$id = $id[1];
			}
			else {
				$id = $item['_id_'];
			}
			
			echo "<a onfocus='JavaScript: this.blur()' href='javascript: loadSubTree(\"" . $type . "\",\"" . $id . "\", " . $lessBranchs . ", \"\", \"\")'>";
			
			echo $img . $item['_iconImg_'] ."&nbsp;" . __($item['_name_']) . ' (';
			
			$counts_info = array(
				'total_count' => $item['_num_ok_'] +
					$item['_num_critical_'] +
					$item['_num_warning_'] +
					$item['_num_unknown_'] +
					$item['_num_not_init_'],
				'normal_count' => $item['_num_ok_'],
				'critical_count' => $item['_num_critical_'],
				'warning_count' => $item['_num_warning_'],
				'unknown_count' => $item['_num_unknown_'],
				'not_init_count' => $item['_num_not_init_']);
			
			
			reporting_tiny_stats($counts_info, false, $type);
			
			
			echo ') '. "</a>";
			
			echo "<div hiddenDiv='1' loadDiv='0' style='margin: 0px; padding: 0px;' class='tree_view' id='tree_div_" . $type . "_" . $id . "'></div>";
			echo "</li>\n";
		}
		echo "</ul>\n";
		echo '</td>';
		echo '<td style="width:38%" valign="top">';
		echo '<div id="cont">&nbsp;</div>';
		echo '</td></tr>';
		echo '</table>';
	}
}

// Get data for the tree view
function treeview_getData ($type) {
	global $config;
	
	$search_free = get_parameter('search_free', '');
	$select_status = get_parameter('status', -1);
	$search_group = get_parameter('search_group', 0);
	
	if ($search_group) {
		$avariableGroups[$search_group] = groups_get_name($search_group);
	}
	else {
		$avariableGroups = users_get_groups ();
	}
	
	//Get all groups
	//$avariableGroups = users_get_groups ();
	
	//Get all groups with agents
	$full_groups = db_get_all_rows_sql("
		SELECT DISTINCT id_grupo
		FROM tagente");
	
	if ($full_groups === false) {
		return array ();
	}
	
	
	
	$fgroups = array();
	
	foreach ($full_groups as $fg) {
		$fgroups[$fg['id_grupo']] = "";
	}
	
	// We only want groups with agents, so we need the intesect of both arrays.
	// Not for policies, we need all groups
	if ($type != 'policies') {
		$avariableGroups = array_intersect_key($avariableGroups,
			$fgroups);
	}
	
	$avariableGroupsIds = implode(',', array_keys($avariableGroups));
	if ($avariableGroupsIds == '') {
		$avariableGroupsIds == -1;
	}
	
	if ($type !== 'policies') {
		// Filter groups by agent status
		switch ($select_status) {
			case NORMAL:
				foreach ($avariableGroups as $group_name) {
					$id_group = db_get_value_sql('
						SELECT id_grupo
						FROM tgrupo
						WHERE nombre ="' . $group_name . '"');
					
					$num_ok = groups_agent_ok($id_group);
					
					if ($num_ok <= 0)
						unset($avariableGroups[$id_group]);
				
				}
				
				break;
			case WARNING:
				foreach ($avariableGroups as $group_name) {
					$id_group = db_get_value_sql('
						SELECT id_grupo
						FROM tgrupo
						WHERE nombre ="' . $group_name . '"');
					
					$num_warning = groups_agent_warning($id_group);
					
					if ($num_warning <= 0)
						unset($avariableGroups[$id_group]);
				}
				break;
			case CRITICAL:
				foreach ($avariableGroups as $group_name) {
					$id_group = db_get_value_sql('
						SELECT id_grupo
						FROM tgrupo
						WHERE nombre ="' . $group_name . '"');
					
					$num_critical = groups_agent_critical($id_group);
					
					if ($num_critical <= 0)
						unset($avariableGroups[$id_group]);
				}
				break;
			case UNKNOWN:
				foreach ($avariableGroups as $group_name) {
					$id_group = db_get_value_sql('
						SELECT id_grupo
						FROM tgrupo
						WHERE nombre ="' . $group_name . '"');
					
					$num_unknown = groups_agent_unknown($id_group);
					
					if ($num_unknown <= 0)
						unset($avariableGroups[$id_group]);
				}
				break;
			case NOT_INIT:
				foreach ($avariableGroups as $group_name) {
					$id_group = db_get_value_sql('
						SELECT id_grupo
						FROM tgrupo
						WHERE nombre ="' . $group_name . '"');
					
					$num_not_init = groups_agent_not_init($id_group);
					
					if ($num_not_init <= 0)
						unset($avariableGroups[$id_group]);
				}
				break;
		}
		
		// If there are not groups display error and return
		if (empty($avariableGroups)) {
			return array ();
		}
	}
	
	if ($search_free != '') {
		switch ($config['dbtype']) {
			case "mysql":
				$sql_search = " AND id_grupo IN (
					SELECT id_grupo
					FROM tagente
					WHERE tagente.nombre COLLATE utf8_general_ci LIKE '%$search_free%')";
				break;
			case "postgresql":
				$sql_search = " AND id_grupo IN (
					SELECT id_grupo
					FROM tagente
					WHERE tagente.nombre LIKE '%$search_free%')";
				break;
			case "oracle":
				$sql_search = " AND id_grupo IN (
					SELECT id_grupo
					FROM tagente
					WHERE tagente.nombre COLLATE utf8_general_ci LIKE '%$search_free%')";
				break;
		}
	}
	else {
		$sql_search ='';
	}
	
	$order_collate = "";
	switch ($config['dbtype']) {
		case "mysql":
			$order_collate = "COLLATE utf8_general_ci";
			break;
	}
	
	switch ($type) {
		case 'os':
			$sql = agents_get_agents(array (
				'order' => 'nombre ' . $order_collate . ' ASC',
				'disabled' => 0,
				'status' => $select_status,
				'search' => $sql_search),
				
				array ('tagente.id_os'),
				'AR',
				false,
				true);
			
			$sql_os = sprintf("SELECT *
				FROM tconfig_os
				WHERE id_os IN (%s)", $sql);
			
			$list = db_get_all_rows_sql($sql_os);
			
			break;
		case 'group':
			$stringAvariableGroups = (
					implode(', ',
						array_map(
							create_function('&$itemA', '{ return "\'" . $itemA . "\'"; }'), $avariableGroups
							)
						)
				);
			
			switch ($config["dbtype"]) {
				case "mysql":
					$list = db_get_all_rows_sql("
						SELECT *
						FROM tgrupo
						WHERE nombre IN (" . $stringAvariableGroups . ") $sql_search
						ORDER BY nombre COLLATE utf8_general_ci ASC");
					break;
				case "postgresql":
					$list = db_get_all_rows_sql("
						SELECT *
						FROM tgrupo
						WHERE nombre IN (" . $stringAvariableGroups . ") $sql_search
						ORDER BY nombre ASC");
					break;
				case "oracle":
					$list = db_get_all_rows_sql("
						SELECT *
						FROM tgrupo
						WHERE dbms_lob.substr(nombre,4000,1) IN (" . $stringAvariableGroups . ")
						ORDER BY nombre COLLATE utf8_general_ci ASC");
					break;
			}
			break;
		case 'module_group':
			$sql = agents_get_agents(array (
				'order' => 'nombre ' . $order_collate . ' ASC',
				'disabled' => 0,
				'status' => $select_status,
				'search' => $sql_search),
				array ('id_agente'),
				'AR',
				false,
				true);
			
			// Skip agents without modules
			$sql .= ' AND tagente.total_count>0';
			
			$sql_module_groups = sprintf("
				SELECT *
				FROM tmodule_group
				WHERE id_mg IN (
					SELECT id_module_group
					FROM tagente_modulo
					WHERE id_agente IN (%s))", $sql);
			
			
			$list = db_get_all_rows_sql($sql_module_groups);
			
			if ($list == false) {
				$list = array();
			}
			
			array_push($list, array('id_mg' => 0, 'name' => 'Not assigned'));
			
			break;
		case 'policies':
			$avariableGroups = users_get_groups ();
			
			$groups_id = array_keys($avariableGroups);
			$groups = implode(',',$groups_id);
			
			if ($search_free != '') {
				$sql = "
					SELECT DISTINCT tpolicies.id, tpolicies.name
					FROM tpolicies, tpolicy_modules,
						tagente_estado, tagente, tagente_modulo
					WHERE 
						tagente.id_agente = tagente_estado.id_agente AND
						tagente_modulo.id_agente = tagente_estado.id_agente AND
						tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND 
						tagente_estado.utimestamp != 0 AND
						tagente_modulo.id_policy_module != 0 AND
						tpolicy_modules.id = tagente_modulo.id_policy_module AND
						tpolicies.id = tpolicy_modules.id_policy AND
						tagente.id_grupo IN  ($groups) AND
						tagente.nombre LIKE '%$search_free%' AND
						tagente.disabled = 0 AND
						tagente_modulo.disabled = 0
					ORDER BY tpolicies.name COLLATE utf8_general_ci ASC";
				
				$list = db_get_all_rows_sql($sql);
				
				if ($list === false)
					$list = array();
				
				$element = 0;
				switch ($select_status) {
					case NORMAL:
						foreach ($list as $policy_element) {
							
							$policy_agents_ok = policies_agents_ok($policy_element['id']);
							
							if ($policy_agents_ok <= 0)
								unset($list[$element]);
							
							$element++;
						}
						break;
					case CRITICAL:
						foreach ($list as $policy_element) {
							
							$policy_agents_critical = policies_agents_critical($policy_element['id']);
							
							if ($policy_agents_critical <= 0)
								unset($list[$element]);
							
							$element++;
						}
						break;
					case WARNING:
						foreach ($list as $policy_element) {
							
							$policy_agents_warning = policies_agents_warning($policy_element['id']);
							
							if ($policy_agents_warning <= 0)
								unset($list[$element]);
							
							$element++;
						}
						break;
					case UNKNOWN:
						foreach ($list as $policy_element) {
							
							$policy_agents_unknown = policies_agents_unknown($policy_element['id']);
							
							if ($policy_agents_unknown <= 0)
								unset($list[$element]);
							
							$element++;
						}
						break;
				}
				
				if ($list === false)
					$list = array();
				
				array_push($list, array('id' => 0, 'name' => 'No policy'));
			}
			else {
				$sql = "
					SELECT DISTINCT tpolicies.id,
						tpolicies.name
					FROM tpolicies, tpolicy_modules, tagente_estado,
						tagente, tagente_modulo
					WHERE 
						tagente.id_agente = tagente_estado.id_agente AND
						tagente_modulo.id_agente = tagente_estado.id_agente AND
						tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND 
						tagente_estado.utimestamp != 0 AND
						tagente_modulo.id_policy_module != 0 AND
						tpolicy_modules.id = tagente_modulo.id_policy_module AND
						tpolicies.id = tpolicy_modules.id_policy AND
						tagente.id_grupo IN ($groups) AND
						tagente.disabled = 0 AND
						tagente_modulo.disabled = 0
						ORDER BY tpolicies.name " . $order_collate . " ASC";
				
				$list = db_get_all_rows_sql($sql);
				if ($list === false)
					$list = array();
				
				
				$element = 0;
				switch ($select_status) {
					case NORMAL:
						foreach ($list as $policy_element) {
							
							$policy_agents_ok =
								policies_agents_ok($policy_element['id']);
							
							if ($policy_agents_ok <= 0)
								unset($list[$element]);
							
							$element++;
						}
						break;
					case CRITICAL:
						foreach ($list as $policy_element) {
							
							$policy_agents_critical = policies_agents_critical($policy_element['id']);
							
							if ($policy_agents_critical <= 0)
								unset($list[$element]);
							
							$element++;
						}
						break;
					case WARNING:
						foreach ($list as $policy_element) {
							
							$policy_agents_warning = policies_agents_warning($policy_element['id']);
							
							if ($policy_agents_warning <= 0)
								unset($list[$element]);
							
							$element++;
						}
						break;
					case UNKNOWN:
						foreach ($list as $policy_element) {
							
							$policy_agents_unknown = policies_agents_unknown($policy_element['id']);
							
							if ($policy_agents_unknown <= 0)
								unset($list[$element]);
							
							$element++;
						}
						break;
				}
				
				if ($list === false)
					$list = array();
				
				array_push($list, array('id' => 0, 'name' => 'No policy'));
			}
			break;
		default:
		case 'module':
			$avariableGroupsIds = implode(',',array_keys($avariableGroups));
			if ($avariableGroupsIds == ''){
				$avariableGroupsIds == -1;
			}
			
			if ($search_free != '') {
				switch ($config['dbtype']) {
					case "mysql":
						$sql_search = " AND t1.id_agente IN (
							SELECT id_agente
							FROM tagente
							WHERE nombre COLLATE utf8_general_ci LIKE '%$search_free%')";
						break;
					case "postgresql":
						$sql_search = " AND t1.id_agente IN (
							SELECT id_agente
							FROM tagente
							WHERE nombre LIKE '%$search_free%')";
						break;
					case "oracle":
						$sql_search = " AND t1.id_agente IN (
							SELECT id_agente
							FROM tagente
							WHERE nombre COLLATE utf8_general_ci LIKE '%$search_free%')";
						break;
				}
				
				
			}
			else {
				$sql_search = '';
			}
			
			if ($select_status != -1)
				$sql_search .= " AND estado = " . $select_status . " ";
			
			$sql_search .= tags_get_acl_tags(
				$config['id_user'], 0, 'AR', 'module_condition', 'AND', 't1');
			
			switch ($config["dbtype"]) {
				case "mysql":
				case "postgresql":
					$list = db_get_all_rows_sql('SELECT t1.nombre
						FROM tagente_modulo t1, tagente t2,
							tagente_estado t3
						WHERE t1.id_agente = t2.id_agente AND
							t1.id_agente_modulo = t3.id_agente_modulo AND
							t2.disabled = 0 AND t1.disabled = 0 AND
							t3.utimestamp !=0 AND
							t2.id_grupo in (' . $avariableGroupsIds . ')' .
							$sql_search.'
						GROUP BY t1.nombre ORDER BY t1.nombre');
					break;
				case "oracle":
					$list = db_get_all_rows_sql('
						SELECT dbms_lob.substr(t1.nombre,4000,1) as nombre
						FROM tagente_modulo t1, tagente t2,
							tagente_estado t3
						WHERE t1.id_agente = t2.id_agente AND
							t2.id_grupo in (' . $avariableGroupsIds . ') AND
							t1.id_agente_modulo = t3.id_agente_modulo AND
							t2.disabled = 0 AND
							t1.disabled = 0 AND
							t3.utimestamp !=0' .
							$sql_search.'
							GROUP BY dbms_lob.substr(t1.nombre,4000,1)
							ORDER BY dbms_lob.substr(t1.nombre,4000,1) ASC');
					break;
			}
			
			break;
		case 'tag':
				// Restrict the tags showed to the user tags
				$user_tags = tags_get_user_tags();
				if (empty($user_tags)) {
					$user_tags_sql = ' AND 1 = 0';
				}
				else {
					$user_tags_sql = sprintf(' AND ttag.id_tag IN (%s)', implode(',', array_keys($user_tags)));
				}
				
				if ($search_free == '') {
					$search_sql = '';
				}
				else {
					$search_sql = sprintf(" AND tagente.nombre COLLATE utf8_general_ci LIKE '%%%s%%'", $search_free);
				}
				
				$sql = "
					SELECT DISTINCT ttag.name 
					FROM ttag, ttag_module, tagente, tagente_modulo 
					WHERE ttag.id_tag = ttag_module.id_tag
						AND tagente.id_agente = tagente_modulo.id_agente
						AND tagente.disabled = 0
						AND ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo" .
						$search_sql . 
						$user_tags_sql . "
					ORDER BY ttag.name $order_collate ASC";
			
				$list = db_get_all_rows_sql($sql);
			break;
	}
	
	if ($list == false) {
		$list = array();
	}
	
	foreach ($list as $key => $item) {
		switch ($type) {
			case 'os':
				$id = $item['id_os'];
				$list[$key]['_id_'] = $id;
				$list[$key]['_name_'] = $item['name'];
				$list[$key]['_iconImg_'] = html_print_image(str_replace('.png' ,'_small.png', ui_print_os_icon ($item['id_os'], false, true, false)) . " ", true);
				$list[$key]['_num_ok_'] = os_agents_ok($id);
				$list[$key]['_num_critical_'] = os_agents_critical($id);
				$list[$key]['_num_warning_'] = os_agents_warning($id);
				$list[$key]['_num_unknown_'] = os_agents_unknown($id);
				break;
			case 'group':
				$id = $item['id_grupo'];
				$list[$key]['_id_'] = $id;
				$list[$key]['_name_'] = $item['nombre'];
				$list[$key]['_iconImg_'] = html_print_image ("images/groups_small/" . groups_get_icon($item['id_grupo']).".png", true, array ("style" => 'vertical-align: middle;'));
				$list[$key]['_num_total_'] = groups_agent_total($id);
				$list[$key]['_num_ok_'] = groups_agent_ok($id);
				$list[$key]['_num_not_init_'] = groups_agent_not_init($id);
				$list[$key]['_num_critical_'] = groups_agent_critical($id);
				$list[$key]['_num_warning_'] = groups_agent_warning($id);
				$list[$key]['_num_unknown_'] = groups_agent_unknown ($id);
				break;
			case 'module_group':
				$id = $item['id_mg'];
				$list[$key]['_id_'] = $id;
				$list[$key]['_name_'] = $item['name'];
				$list[$key]['_iconImg_'] = '';
				$list[$key]['_num_ok_'] = modules_group_agent_ok($id);
				$list[$key]['_num_critical_'] = modules_group_agent_critical ($id);
				$list[$key]['_num_warning_'] = modules_group_agent_warning($id);
				$list[$key]['_num_unknown_'] = modules_group_agent_unknown($id);
				break;
			case 'policies':
				$id = $item['id'];
				$list[$key]['_id_'] = $id;
				$list[$key]['_name_'] = $item['name'];
				$list[$key]['_iconImg_'] = '';
				$list[$key]['_num_ok_'] = policies_agents_ok($id);
				$list[$key]['_num_critical_'] = policies_agents_critical($id);
				$list[$key]['_num_warning_'] = policies_agents_warning($id);
				$list[$key]['_num_unknown_'] = policies_agents_unknown($id);
				break;
			default:
			case 'module':
				$symbols = ' !"#$%&\'()*+,./:;<=>?@[\\]^{|}~';
				$id = io_safe_output($item['nombre']);
				for ($i = 0; $i < strlen($symbols); $i++) {
					$id = str_replace(substr($symbols, $i, 1), '_articapandora_'.ord(substr($symbols, $i, 1)).'_pandoraartica_', $id);
				}
				$module_name = $item['nombre'];
				$list[$key]['_id_'] = $id;
				$list[$key]['_name_'] = io_safe_output($module_name);
				$list[$key]['_iconImg_'] = '';
				$list[$key]['_num_ok_'] = modules_agents_ok($module_name);
				$list[$key]['_num_critical_'] = modules_agents_critical($module_name);
				$list[$key]['_num_warning_'] = modules_agents_warning($module_name);
				$list[$key]['_num_unknown_'] = modules_agents_unknown($module_name);
				break;
			case 'tag':
				$id = db_get_value('id_tag', 'ttag', 'name', $item['name']);
				$list[$key]['_id_'] = $id;
				$list[$key]['_name_'] = $item['name'];
				$list[$key]['_iconImg_'] = html_print_image ("images/tag_red.png", true, array ("style" => 'vertical-align: middle;'));
				$list[$key]['_num_ok_'] = tags_agent_ok($id);
				$list[$key]['_num_critical_'] = tags_agent_critical($id);
				$list[$key]['_num_warning_'] = tags_agent_warning($id);
				$list[$key]['_num_unknown_'] = tags_agent_unknown($id);
				break;
		}
		
		if (defined ('METACONSOLE')) {
			$list[$key]['_id_'] = $list[$key]['_name_'];
		}
	}
	
	return $list;
}

// Get SQL for the first tree branch
function treeview_getFirstBranchSQL ($type, $id, $avariableGroupsIds, $statusSel, $search_free) {
	global $config;
	
	if (empty($avariableGroupsIds)) {
		return false;
	}
	
	$order_collate = "";
	switch ($config['dbtype']) {
		case "mysql":
			$order_collate = "COLLATE utf8_general_ci";
			break;
	}
	
	//TODO CHANGE POLICY ACL FOR TAG ACL
	$extra_sql = '';
	if ($extra_sql != '') {
		$extra_sql .= ' OR';
	}
	$groups_sql = implode(', ', $avariableGroupsIds);
	
	if ($search_free != '') {
		switch ($config['dbtype']) {
			case "mysql":
				$search_sql = " AND tagente.nombre COLLATE utf8_general_ci LIKE '%$search_free%'";
				break;
			case "postgresql":
				$search_sql = " AND tagente.nombre LIKE '%$search_free%'";
				break;
			case "oracle":
				$search_sql = " AND tagente.nombre COLLATE utf8_general_ci LIKE '%$search_free%'";
				break;
		}
		
	}
	else {
		$search_sql = '';
	}
	
	//Extract all rows of data for each type
	switch ($type) {
		case 'group':
			if (defined ('METACONSOLE')) {
				$id = groups_get_id (pack ('H*', $id));
				if ($id == '') {
					return false;
				}
			}
			
			$sql = agents_get_agents(array (
				'id_grupo' => $id,
				'disabled' => 0,
				'status' => $statusSel,
				'search' => $search_sql),
				array ('*'),
				'AR',
				array('field' => 'nombre ' . $order_collate, 'order' => ' ASC'),
				true);
			break;
		case 'os':
			
			$sql = agents_get_agents(array (
				'id_os' => $id,
				'disabled' => 0,
				'status' => $statusSel,
				'search' => $search_sql),
				array ('*'),
				'AR',
				array('field' => 'nombre ' . $order_collate, 'order' => ' ASC'),
				true);
			break;
		case 'module_group':
			
			$sql = agents_get_agents(array (
				'disabled' => 0,
				'status' => $statusSel,
				'search' => $search_sql),
				array ('*'),
				'AR',
				false,
				true);
			
			// Skip agents without modules
			$sql .= ' AND total_count>0 AND disabled=0 AND id_agente IN
				(SELECT DISTINCT (id_agente)
				FROM tagente_modulo 
				WHERE id_module_group = ' . $id . ')';
			
			$sql .= 'ORDER BY nombre ' . $order_collate . ' ASC';
			
			break;
		case 'policies':
			
			$sql = agents_get_agents(array (
				'disabled' => 0,
				'search' => $search_sql),
				
				array ('*'),
				'AR',
				false,
				true);
			
			if ($id != 0) {
				// Skip agents without modules
				$sql .= ' AND tagente.id_agente IN
					(SELECT tagente.id_agente
					FROM tagente, tagente_modulo, tagente_estado, tpolicy_modules
					WHERE tagente.id_agente = tagente_modulo.id_agente
					AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
					AND tagente_modulo.id_policy_module = tpolicy_modules.id 
					AND tagente.disabled = 0 
					AND tagente_modulo.disabled = 0
					AND tagente_estado.utimestamp != 0
					AND tagente_modulo.id_policy_module != 0
					AND tpolicy_modules.id_policy = ' . $id . '
					GROUP BY tagente.id_agente
					having COUNT(*) > 0)';
			}
			else if ($statusSel == 0) {
				
				// If status filter is NORMAL add void agents
				$sql .= " UNION SELECT * FROM tagente 
					WHERE tagente.disabled = 0
					AND tagente.id_agente NOT IN (
						SELECT tagente_estado.id_agente 
						FROM tagente_estado)";
			}
			
			$sql .= 'ORDER BY nombre ' . $order_collate . ' ASC';
			
			break;
		case 'module':
			//Replace separator token "articapandora_32_pandoraartica_" for " "
			//example:
			//"Load_articapandora_32_pandoraartica_Average"
			//result -> "Load Average"
			$symbols = ' !"#$%&\'()*+,./:;<=>?@[\\]^{|}~';
			$name = $id;
			for ($i = 0; $i < strlen($symbols); $i++) {
				$name = str_replace('_articapandora_'.ord(substr($symbols, $i, 1)).'_pandoraartica_', substr($symbols, $i, 1), $name);
			}
			
			$name = io_safe_input(io_safe_output($name));
			
			$sql = agents_get_agents(array (
				'disabled' => 0,
				'status' => $statusSel,
				'search' => $search_sql),
				
				array ('*'),
				'AR',
				false,
				true);
			$sql .= sprintf('AND  id_agente IN (
					SELECT id_agente
					FROM tagente_modulo
					WHERE nombre = \'%s\' AND disabled = 0
				)
				', $name);
			
			$sql .= 'ORDER BY nombre ' . $order_collate . ' ASC';
			
			break;
		case 'tag':
			if (defined ('METACONSOLE')) {
				$id = tags_get_id (pack ('H*', $id));
				if ($id == '') {
					return false;
				}
			}
			
			if ($id === false) {
				return false;
			}
			
			if (empty($groups_sql)) {
				$groups_condition = ' AND 1 = 0';
			}
			else {
				$groups_condition = sprintf(' AND tagente.id_grupo IN (%s)', $groups_sql);
			}
			
			$sql = "SELECT tagente.* 
				FROM tagente, tagente_modulo, ttag_module 
				WHERE tagente.id_agente = tagente_modulo.id_agente
					AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
					AND ttag_module.id_tag = " . $id . $groups_condition;
			$sql .= tags_get_acl_tags($config['id_user'], 0, 'AR', 'module_condition', 'AND', 'tagente_modulo');
			
			$sql .= ' AND tagente.disabled = 0'. $search_sql;
			
			$sql .= ' GROUP BY tagente.id_agente
				ORDER BY tagente.nombre ' . $order_collate . ' ASC';
			
			break;
	}
	
	if ($sql === false || $sql == '') {
		return false;
	}
	
	return $sql;
}

// Get SQL for the second tree branch
function treeview_getSecondBranchSQL ($fatherType, $id, $id_father) {
	global $config;
	
	$order_collate = "";
	switch ($config['dbtype']) {
		case "mysql":
			$order_collate = "COLLATE utf8_general_ci";
			break;
	}
	
	switch ($fatherType) {
		case 'group':
			$sql = 'SELECT * 
				FROM tagente_modulo AS t1 
				INNER JOIN tagente_estado AS t2
					ON t1.id_agente_modulo = t2.id_agente_modulo
				WHERE t1.id_agente = ' . $id;
			$sql .= tags_get_acl_tags($config['id_user'], 0, 'AR', 'module_condition', 'AND', 't1');
			break;
		case 'os':
			$sql = 'SELECT * 
				FROM tagente_modulo AS t1 
				INNER JOIN tagente_estado AS t2
					ON t1.id_agente_modulo = t2.id_agente_modulo
				WHERE t1.id_agente = ' . $id;
			$sql .= tags_get_acl_tags($config['id_user'], 0, 'AR', 'module_condition', 'AND', 't1');
			break;
		case 'module_group':
			$sql = 'SELECT * 
				FROM tagente_modulo AS t1 
				INNER JOIN tagente_estado AS t2
					ON t1.id_agente_modulo = t2.id_agente_modulo
				WHERE t1.id_agente = ' . $id . ' AND id_module_group = ' . $id_father;
			$sql .= tags_get_acl_tags($config['id_user'], 0, 'AR', 'module_condition', 'AND', 't1');
			break;
		case 'policies':
			$whereQuery = '';
			if ($id_father != 0)
				$whereQuery = ' AND t1.id_policy_module IN 
					(SELECT id FROM tpolicy_modules WHERE id_policy = ' . $id_father . ')';
			else
				$whereQuery = ' AND t1.id_policy_module = 0 '; 
			
			$sql = 'SELECT * 
				FROM tagente_modulo AS t1 
				INNER JOIN tagente_estado AS t2 ON t1.id_agente_modulo = t2.id_agente_modulo
				WHERE t1.id_agente = ' . $id . $whereQuery;
			$sql .= tags_get_acl_tags($config['id_user'], 0, 'AR', 'module_condition', 'AND', 't1');
			break;
		default:
		case 'module':
			$symbols = ' !"#$%&\'()*+,./:;<=>?@[\\]^{|}~';
			$name = $id_father;
			for ($i = 0; $i < strlen($symbols); $i++) {
				$name = str_replace('_articapandora_' .
					ord(substr($symbols, $i, 1)) .'_pandoraartica_',
					substr($symbols, $i, 1), $name);
			}
			switch ($config["dbtype"]) {
				case "mysql":
					$sql = 'SELECT * 
						FROM tagente_modulo AS t1 
						INNER JOIN tagente_estado AS t2 ON t1.id_agente_modulo = t2.id_agente_modulo
						WHERE t1.id_agente = ' . $id . ' AND nombre = \'' . io_safe_input($name) . '\'';
					break;
				case "postgresql":
				case "oracle":
					$sql = 'SELECT * 
						FROM tagente_modulo AS t1 
							INNER JOIN tagente_estado AS t2
							ON t1.id_agente_modulo = t2.id_agente_modulo
						WHERE t1.id_agente = ' . $id . '
							AND nombre = \'' . io_safe_input($name) . '\'';
					break;
			}
			break;
		case 'tag':
			if (defined ('METACONSOLE')) {
				$id_father = tags_get_id (pack ('H*', $id_father));
				if ($id == '') {
					return false;
				}
			}
			
			if ($id_father === false) {
				return false;
			}
			$sql = 'SELECT *
				FROM tagente_modulo, tagente_estado, ttag_module 
				WHERE tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
					AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
					AND tagente_modulo.id_agente=' . $id . '
					AND ttag_module.id_tag = ' . $id_father;
			break;
		}
	
	// This line checks for initializated modules or (non-initialized) asyncronous modules
	$sql .= ' AND disabled = 0 AND (utimestamp > 0 OR id_tipo_modulo IN (21,22,23))
		ORDER BY nombre ' . $order_collate . ' ASC';
	return $sql;
}
?>

<script language="javascript" type="text/javascript">
	$(document).ready (function () {

/*
		module_id = $('#text-id_module').val();
		id_agent = $('#text-id_agent').val();
		server_name = $('#text-server_name').val();	

		$("#submit-upd_button").click( function() {	
			show_module_detail_dialog(module_id, id_agent, server_name, 0, 86400);
		});

		$("#submit-updbutton_period").click( function() {
			//refresh_period_callback();

			var period = $('#period').val();
			console.log(period);
			show_module_detail_dialog(module_id, id_agent, server_name, 0, period);

		});
*/

	});

/*
	// Show the modal window of an module
	function show_module_detail_dialog(module_id, id_agent, server_name, offset, period) {
		$.ajax({
			type: "POST",
			url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
			data: "page=include/ajax/module&get_module_detail=1&server_name="+server_name+"&id_agent="+id_agent+"&id_module=" + module_id+"&offset="+offset+"&period="+period,
			dataType: "html",
			success: function(data){	
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
						width: 620,
						height: 500
					})
					.show ();
					refresh_pagination_callback ();
					
			}
		});
	}
	
	function refresh_pagination_callback () {
		$(".pagination").click( function() {	
			
			var classes = $(this).attr('class');
			classes = classes.split(' ');
			var offset_class = classes[1];
			offset_class = offset_class.split('_');
			var offset = offset_class[1];
			
			var period = $('#period').val();
			//console.log(period);
			
			show_module_detail_dialog(module_id, id_agent, server_name, offset, period);
			return false;
		});
	}
	
	function refresh_period_callback () {

		$("#submit-updbutton_period").click( function() {
			var period = $('#period').val();
			console.log(period);
			show_module_detail_dialog(module_id, id_agent, server_name, 0, period);
			return false;
		});

	}
*/
	

</script>
