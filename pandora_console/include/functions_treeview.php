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

function treeview_printTable($id_agente, $console_url = '') {
	global $config;
	
	require_once ("include/functions_agents.php");
	require_once ($config["homedir"] . '/include/functions_graph.php');
	include_graphs_dependencies();
	require_once ($config['homedir'] . '/include/functions_groups.php');
	require_once ($config['homedir'] . '/include/functions_gis.php');
	
	$agent = db_get_row ("tagente", "id_agente", $id_agente);
	
	if ($agent === false) {
		echo '<h3 class="error">'.__('There was a problem loading agent').'</h3>';
		return;
	}
	
	$is_extra = enterprise_hook('policies_is_agent_extra_policy', array($id_agente));
	
	if($is_extra === ENTERPRISE_NOT_HOOK) {
		$is_extra = false;
	}
	
	if (! check_acl ($config["id_user"], $agent["id_grupo"], "AR") && !$is_extra) {
		db_pandora_audit("ACL Violation", 
			"Trying to access Agent General Information");
		require_once ("general/noaccess.php");
		return;
	}
	
	echo '<div id="id_div3" width="450px">';
	echo '<table cellspacing="4" cellpadding="4" border="0" class="databox" style="width:70%">';
	//Agent name
	echo '<tr><td class="datos"><b>'.__('Agent name').'</b></td>';
	if ($agent['disabled']) {
		$cellName = "<em>" . ui_print_agent_name ($agent["id_agente"], true, 500, "text-transform: uppercase;", true) . ui_print_help_tip(__('Disabled'), true) . "</em>";
	}
	else {
		$cellName = ui_print_agent_name ($agent["id_agente"], true, 500, "text-transform: uppercase;", true);
	}
	echo '<td class="datos"><b>'.$cellName.'</b></td>';
	
	//Addresses
	echo '<tr><td class="datos2"><b>'.__('IP Address').'</b></td>';
	echo '<td class="datos2" colspan="2">';
	$ips = array();
	$addresses = agents_get_addresses ($id_agente);
	$address = agents_get_address($id_agente);
	
	foreach($addresses as $k => $add) {
		if($add == $address) {
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
	echo '<tr><td class="datos2"><b>'.__('Description').'</b></td>';
	echo '<td class="datos2" colspan="2">'.$agent["comentarios"].'</td></tr>';
	
	// Agent version
	echo '<tr><td class="datos2"><b>'.__('Agent Version'). '</b></td>';
	echo '<td class="datos2" colspan="2">'.$agent["agent_version"].'</td></tr>';
	
	// Position Information
	if ($config['activate_gis']) {
		$dataPositionAgent = gis_get_data_last_position_agent($agent['id_agente']);
		
		echo '<tr><td class="datos2"><b>'.__('Position (Long, Lat)'). '</b></td>';
		echo '<td class="datos2" colspan="2">';
		
		if ($dataPositionAgent === false) {
			echo __('There is no GIS data.');
		}
		else {
			echo '<a href="' . $console_url . 'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente='.$id_agente.'">';
			if ($dataPositionAgent['description'] != "")
				echo $dataPositionAgent['description'];
			else
				echo $dataPositionAgent['stored_longitude'].', '.$dataPositionAgent['stored_latitude'];
			echo "</a>";
		}
		
		echo '</td></tr>';
	}
	
	// If the url description is setted
	if ($agent['url_address'] != '') {
		echo '<tr><td class="datos"><b>'.__('Url address').'</b></td>';	
		echo '<td class="datos2" colspan="2"><a href='.$agent["url_address"].'>' . $agent["url_address"] . '</a></td></tr>';
	}
	
	// Last contact
	echo '<tr><td class="datos2"><b>'.__('Last contact')." / ".__('Remote').'</b></td><td class="datos2 f9" colspan="2">';
	ui_print_timestamp ($agent["ultimo_contacto"]);
	
	echo " / ";
	
	if ($agent["ultimo_contacto_remoto"] == "01-01-1970 00:00:00") { 
		echo __('Never');
	}
	else {
		echo $agent["ultimo_contacto_remoto"];
	}
	echo '</td></tr>';
	
	// Timezone Offset
	if ($agent['timezone_offset'] != 0) {
		echo '<tr><td class="datos2"><b>'.__('Timezone Offset'). '</b></td>';
		echo '<td class="datos2" colspan="2">'.$agent["timezone_offset"].'</td></tr>';
	}
	// Next contact (agent)
	$progress = agents_get_next_contact($id_agente);
	
	echo '<tr><td class="datos"><b>'.__('Next agent contact').'</b></td>';
	echo '<td class="datos f9" colspan="2">' . progress_bar($progress, 200, 20) . '</td></tr>';
	
	// Custom fields
	$fields = db_get_all_rows_filter('tagent_custom_fields', array('display_on_front' => 1));
	if ($fields === false) {
		$fields = array ();
	}
	if ($fields) {
		foreach ($fields as $field) {
			echo '<tr><td class="datos"><b>'.$field['name'] . ui_print_help_tip (__('Custom field'), true).'</b></td>';
			$custom_value = db_get_value_filter('description', 'tagent_custom_data', array('id_field' => $field['id_field'], 'id_agent' => $id_agente));
			if ($custom_value === false || $custom_value == '') {
				$custom_value = '<i>-'.__('empty').'-</i>';
			}
			echo '<td class="datos f9" colspan="2">'.$custom_value.'</td></tr>';
		}
	}
	
	//End of table
	echo '</table></div>';
	
	// Blank space below title, DONT remove this, this
	// Breaks the layout when Flash charts are enabled :-o
	echo '<div id="id_div" style="height: 10px">&nbsp;</div>';	
		
		//Floating div
		echo '<div id="agent_access" width:35%; padding-top:11px;">';
	
	if ($config["agentaccess"]) {
		echo '<b>'.__('Agent access rate (24h)').'</b><br />';
		
		graphic_agentaccess($id_agente, 280, 110, 86400);
	}
	
	echo '<br>';
	graph_graphic_agentevents ($id_agente, 290, 15, 86400, '');
	
	echo '</div>';
	
	
	echo '<form id="agent_detail" method="post" action="' . $console_url . 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'">';
		echo '<div class="action-buttons" style="width: '.$table->width.'">';
			html_print_submit_button (__('Go to agent detail'), 'upd_button', false, 'class="sub upd"');
		echo '</div>';
	echo '</form>';
	
	return;
}

function treeview_printTree($type) {
	global $config;
	
	echo '<table class="databox" style="width:98%">';
	echo '<tr><td style="width:60%" valign="top">';
	
	if (! defined ('METACONSOLE')) {
		$list = treeview_getData ($type);
	}
	else {
		$servers = db_get_all_rows_sql ("SELECT * FROM tmetaconsole_setup WHERE disabled = 0");
		if ($servers === false) {
			$servers = array();
		}
		
		$list = array ();
		foreach ($servers as $server) {
			if (metaconsole_connect($server) != NOERR) {
				continue;
			}
			$server_list = treeview_getData ($type, $server);
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
			echo "<br/>";
		}
		
		metaconsole_restore_db();
	}

	if ($list === false) {
		ui_print_error_message("There aren't agents in this agrupation");
		echo '</td></tr>';
		echo '</table>';
	}
	else {
		echo "<ul style='margin: 0; margin-top: 20px; padding: 0;'>\n";
		
		$first = true;
		foreach ($list as $item) {		
			$lessBranchs = 0;
			if ($first) {
				if ($item != end($list)) {
					$img = html_print_image ("operation/tree/first_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image_" . $type . "_" . $item['_id_'], "pos_tree" => "0"));
					$first = false;
				}
				else {
					$lessBranchs = 1;
					$img = html_print_image ("operation/tree/one_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image_" . $type . "_" . $item['_id_'], "pos_tree" => "1"));
				}
			}
			else {
				if ($item != end($list))
					$img = html_print_image ("operation/tree/closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image_" . $type . "_" . $item['_id_'], "pos_tree" => "2"));
				else
				{
					$lessBranchs = 1;
					$img = html_print_image ("operation/tree/last_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image_" . $type . "_" . $item['_id_'], "pos_tree" => "3"));
				}
			}
			
			echo "<li style='margin: 0px 0px 0px 0px;'>";

			// Convert the id to hexadecimal, since it will be used as a div id
			$hex_id = unpack ('H*', $item['_id_']);
			$hex_id = $hex_id[1];
			echo "<a onfocus='JavaScript: this.blur()' href='javascript: loadSubTree(\"" . $type . "\",\"" . $hex_id . "\", " . $lessBranchs . ", \"\", \"\")'>";
			
			echo $img . $item['_iconImg_'] ."&nbsp;" . __($item['_name_']) . ' ('.
				'<span class="green">'.'<b>'.$item['_num_ok_'].'</b>'.'</span>'. 
				' : <span class="red">'.$item['_num_critical_'].'</span>' .
				' : <span class="yellow">'.$item['_num_warning_'].'</span>'.
				' : <span class="grey">'.$item['_num_unknown_'].'</span>'.') '. "</a>";
			
			echo "<div hiddenDiv='1' loadDiv='0' style='margin: 0px; padding: 0px;' class='tree_view' id='tree_div_" . $type . "_" . $hex_id . "'></div>";
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
function treeview_getData ($type, $server=false) {
	global $config;
	
	if ($server !== false) {
		if (metaconsole_connect ($server) != NOERR) {
			return array ();
		}
	}
	
	$search_free = get_parameter('search_free', '');
	$select_status = get_parameter('status', -1);
	
	//Get all groups
	$avariableGroups = users_get_groups (); //db_get_all_rows_in_table('tgrupo', 'nombre');	
	
	//Get all groups with agents
	$full_groups = db_get_all_rows_sql("SELECT DISTINCT id_grupo FROM tagente WHERE total_count > 0");
	if ($full_groups === false) {
		return array ();
	}
	
	$fgroups = array();
	
	foreach ($full_groups as $fg) {
		$fgroups[$fg['id_grupo']] = "";
	}
	
	// We only want groups with agents, so we need the intesect of both arrays.
	// Not for policies, we need all groups
	if ($type != 'policies')
		$avariableGroups = array_intersect_key($avariableGroups, $fgroups);
	
	$avariableGroupsIds = implode(',',array_keys($avariableGroups));
	if($avariableGroupsIds == '') {
		$avariableGroupsIds == -1;
	}
	
	if ($type !== 'policies') {
		// Filter groups by agent status
		switch ($select_status) {
			case NORMAL:
				foreach ($avariableGroups as $group_name) {
					$id_group = db_get_value_sql('SELECT id_grupo FROM tgrupo where nombre ="' . $group_name . '"');
					
					$num_ok = groups_agent_ok($id_group);	
					
					if ($num_ok <= 0)
						unset($avariableGroups[$id_group]);
				
				}
				
				break;
			case WARNING:
				foreach ($avariableGroups as $group_name) {
					$id_group = db_get_value_sql('SELECT id_grupo FROM tgrupo where nombre ="' . $group_name . '"');
					
					$num_warning = groups_agent_warning($id_group);
					
					if ($num_warning <= 0)
						unset($avariableGroups[$id_group]);
				}
				break;
			case CRITICAL:
				foreach ($avariableGroups as $group_name) {
					$id_group = db_get_value_sql('SELECT id_grupo FROM tgrupo where nombre ="' . $group_name . '"');
					
					$num_critical = groups_agent_critical($id_group);
					
					if ($num_critical <= 0)
						unset($avariableGroups[$id_group]);
				}
				break;
			case UNKNOWN:
				foreach ($avariableGroups as $group_name) {
					$id_group = db_get_value_sql('SELECT id_grupo FROM tgrupo where nombre ="' . $group_name . '"');
					
					$num_unknown = groups_agent_unknown($id_group);
					
					if ($num_unknown <= 0)
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
		$sql_search = " AND id_grupo IN (SELECT id_grupo FROM tagente
			WHERE tagente.nombre COLLATE utf8_general_ci LIKE '%$search_free%')";
	}
	else {
		$sql_search ='';
	}
	
	
	switch ($type) {
		case 'os':		
			$sql = agents_get_agents(array (
				'order' => 'nombre COLLATE utf8_general_ci ASC',
				'disabled' => 0,
				'status' => $select_status,
				'search' => $sql_search),
				
				array ('tagente.id_os'),
				'AR',
				false,
				true);
			
			$sql_os = sprintf("SELECT * FROM tconfig_os WHERE id_os IN (%s)", $sql);
			
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
				case "postgresql":
					$list = db_get_all_rows_sql("SELECT * FROM tgrupo WHERE nombre IN (" . $stringAvariableGroups . ") $sql_search");
					break;
				case "oracle":
					$list = db_get_all_rows_sql("SELECT * FROM tgrupo WHERE dbms_lob.substr(nombre,4000,1) IN (" . $stringAvariableGroups . ")");
					break;
			}
			break;
		case 'module_group':
			$sql = agents_get_agents(array (
				'order' => 'nombre COLLATE utf8_general_ci ASC',
				'disabled' => 0,
				'status' => $select_status,
				'search' => $sql_search),
				array ('id_agente'),
				'AR',
				false,
				true);
			
			// Skip agents without modules
			$sql .= ' AND tagente.total_count>0';
			
			$sql_module_groups = sprintf("SELECT * FROM tmodule_group
				WHERE id_mg IN (SELECT id_module_group FROM tagente_modulo WHERE id_agente IN (%s))", $sql);			
			
			
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
				$sql = "SELECT DISTINCT tpolicies.id, tpolicies.name
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
						tagente_modulo.disabled = 0";
				
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
				$list = db_get_all_rows_sql("SELECT DISTINCT tpolicies.id,
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
						tagente_modulo.disabled = 0");
				
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
			break;
		default:
		case 'module':
			$avariableGroupsIds = implode(',',array_keys($avariableGroups));
			if($avariableGroupsIds == ''){
				$avariableGroupsIds == -1;
			}
			
			if ($search_free != '') {
				$sql_search = " AND t1.id_agente IN (SELECT id_agente FROM tagente
					WHERE nombre COLLATE utf8_general_ci LIKE '%$search_free%')";
			}
			else {
				$sql_search = '';
			}
			
			if ($select_status != -1)
				$sql_search .= " AND estado = " . $select_status . " ";
			
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
							t3.utimestamp !=0
							GROUP BY dbms_lob.substr(t1.nombre,4000,1)
							ORDER BY dbms_lob.substr(t1.nombre,4000,1) ASC');
					break;
			}
			
			break;
		case 'tag':
				$sql = 'SELECT DISTINCT ttag.name 
						FROM ttag, ttag_module, tagente, tagente_modulo 
						WHERE ttag.id_tag = ttag_module.id_tag
						AND tagente.id_agente = tagente_modulo.id_agente
						AND tagente.disabled = 0
						AND ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo';
				if ($search_free != '') {
					$sql = "SELECT DISTINCT ttag.name 
							FROM ttag, ttag_module, tagente, tagente_modulo 
							WHERE ttag.id_tag = ttag_module.id_tag
							AND tagente.id_agente = tagente_modulo.id_agente
							AND tagente.disabled = 0
							AND ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente.nombre COLLATE utf8_general_ci LIKE '%$search_free%'";
				}
				$list = db_get_all_rows_sql($sql);
			break;
	}

	if($list == false) {
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
				$list[$key]['_iconImg_'] = html_print_image ("images/groups_small/" . groups_get_icon($item['id_grupo']).".png", true, array ("style" => 'vertical-align: middle; width: 16px; height: 16px;'));
				$list[$key]['_num_ok_'] = groups_agent_ok($id);
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
				$id = str_replace(array(' ','#','/'), array('_articapandora_'.ord(' ').'_pandoraartica_', '_articapandora_'.ord('#').'_pandoraartica_', '_articapandora_'.ord('/').'_pandoraartica_'),io_safe_output($item['nombre']));
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
				$list[$key]['_iconImg_'] = html_print_image ("images/tag_red.png", true, array ("style" => 'vertical-align: middle; width: 16px; height: 16px;'));
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

	if (empty($avariableGroupsIds)) {
		return false;
	}
		
	//TODO CHANGE POLICY ACL FOR TAG ACL
	$extra_sql = '';
	if($extra_sql != '') {
		$extra_sql .= ' OR';
	}
	$groups_sql = implode(', ', $avariableGroupsIds);
	
	if ($search_free != '') {
		$search_sql = " AND tagente.nombre COLLATE utf8_general_ci LIKE '%$search_free%'";
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
				'order' => 'nombre COLLATE utf8_general_ci ASC',
				'id_grupo' => $id,
				'disabled' => 0,
				'status' => $statusSel,
				'search' => $search_sql),
				array ('*'),
				'AR',
				false,
				true);
			break;
		case 'os':		
			
			$sql = agents_get_agents(array (
				'order' => 'nombre COLLATE utf8_general_ci ASC',
				'id_os' => $id,
				'disabled' => 0,
				'status' => $statusSel,
				'search' => $search_sql),
				array ('*'),
				'AR',
				false,
				true);
			break;
		case 'module_group':

			$sql = agents_get_agents(array (
				'order' => 'nombre COLLATE utf8_general_ci ASC',
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
			break;
		case 'policies':
			
			$sql = agents_get_agents(array (
				'order' => 'nombre COLLATE utf8_general_ci ASC',
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
					group by tagente.id_agente
					having COUNT(*) > 0)';
			}
			else if ($statusSel == 0) {
				
				// If status filter is NORMAL add void agents
				$sql .= " UNION SELECT * FROM tagente 
					WHERE tagente.disabled = 0
					AND tagente.id_agente NOT IN (SELECT tagente_estado.id_agente 
						FROM tagente_estado)";
			}
			break;
		case 'module':
			//Replace separator token "articapandora_32_pandoraartica_" for " "
			//example:
			//"Load_articapandora_32_pandoraartica_Average"
			//result -> "Load Average"
			$name = str_replace(array('_articapandora_'.ord(' ').'_pandoraartica_', '_articapandora_'.ord('#').'_pandoraartica_','_articapandora_'.ord('/').'_pandoraartica_'),array(' ','#','/'),$id);
			
			$name = io_safe_input($name);
			
			
			$sql = agents_get_agents(array (
				'order' => 'nombre COLLATE utf8_general_ci ASC',
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
			break;
		case 'tag':
			$id = tags_get_id (pack ('H*', $id));
			if ($id === false) {
				return false;
			}
	
			$sql = "SELECT tagente.* 
						FROM tagente, tagente_modulo, ttag_module 
						WHERE tagente.id_agente = tagente_modulo.id_agente
						AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
						AND ttag_module.id_tag = " . $id;
			break;
	}
		
	$sql .= ' AND tagente.disabled = 0'. $search_sql;
	return $sql;
}

// Get SQL for the second tree branch
function treeview_getSecondBranchSQL ($fatherType, $id, $id_father) {
	global $config;

	switch ($fatherType) {
		case 'group':
			$sql = 'SELECT * 
				FROM tagente_modulo AS t1 
				INNER JOIN tagente_estado AS t2 ON t1.id_agente_modulo = t2.id_agente_modulo
				WHERE t1.id_agente = ' . $id;
			break;
		case 'os':
			$sql = 'SELECT * 
				FROM tagente_modulo AS t1 
				INNER JOIN tagente_estado AS t2 ON t1.id_agente_modulo = t2.id_agente_modulo
				WHERE t1.id_agente = ' . $id;
			break;
		case 'module_group':
			$sql = 'SELECT * 
				FROM tagente_modulo AS t1 
				INNER JOIN tagente_estado AS t2 ON t1.id_agente_modulo = t2.id_agente_modulo
				WHERE t1.id_agente = ' . $id . ' AND id_module_group = ' . $id_father;
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
			break;
		default:
		case 'module':
			$name = str_replace(array('_articapandora_'.ord(' ').'_pandoraartica_', '_articapandora_'.ord('#').'_pandoraartica_','_articapandora_'.ord('/').'_pandoraartica_'),array(' ','#','/'),$id_father);
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
						INNER JOIN tagente_estado AS t2 ON t1.id_agente_modulo = t2.id_agente_modulo
						WHERE t1.id_agente = ' . $id . ' AND nombre = \'' . io_safe_input($name) . '\'';
					break;
			}
			break;
		case 'tag':
			$id_father = tags_get_id ($id_father);
			if ($id_father === false) {
				return false;
			}
			$sql = 'SELECT * FROM tagente_modulo, tagente_estado, ttag_module 
					WHERE tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
					AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
					AND tagente_modulo.id_agente=' . $id . ' AND ttag_module.id_tag = ' . $id_father;
			break;
		}
	
		// This line checks for initializated modules or (non-initialized) asyncronous modules	
		$sql .= ' AND disabled = 0 AND (utimestamp > 0 OR id_tipo_modulo IN (21,22,23))';
		return $sql;
}

?>
