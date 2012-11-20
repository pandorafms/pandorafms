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

function treeview_printTable($id_agente) {
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
			echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente='.$id_agente.'">';
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
	
	
	echo '<form id="agent_detail" method="post" action="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'">';
		echo '<div class="action-buttons" style="width: '.$table->width.'">';
			html_print_submit_button (__('Go to agent detail'), 'upd_button', false, 'class="sub upd"');
		echo '</div>';
	echo '</form>';
	
	return;
}

function treeview_printTree($type) {
	global $config;
	
	$search_free = get_parameter('search_free', '');
	$select_status = get_parameter('status', -1);
	
	echo '<table class="databox" style="width:98%">';
	echo '<tr><td style="width:60%" valign="top">';
	
	//Get all groups
	$avariableGroups = users_get_groups (); //db_get_all_rows_in_table('tgrupo', 'nombre');	
	
	//Get all groups with agents
	$full_groups = db_get_all_rows_sql("SELECT DISTINCT id_grupo FROM tagente WHERE total_count > 0");
	
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
			ui_print_error_message("There aren't agents in this agrupation");
			echo '</td></tr>';
			echo '</table>';
			return;
		}
	}
	
	if ($search_free != '') {
		$sql_search = " AND id_grupo IN (SELECT id_grupo FROM tagente
			WHERE nombre COLLATE utf8_general_ci LIKE '%$search_free%')";
	}
	else {
		$sql_search ='';
	}
	
	
	switch ($type) {
		default:
		case 'os':
			//Skip agent with all modules in not init status
			$sql_search .= " AND total_count<>notinit_count";
			
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
			//Skip agents which only have not init modules
			$sql_search .= " AND total_count<>notinit_count";	
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
			
			$iconImg = '';
			switch ($type) {
				default:
				case 'os':
					$id = $item['id_os'];
					$name = $item['name'];
					$iconImg = html_print_image(str_replace('.png' ,'_small.png', ui_print_os_icon ($item['id_os'], false, true, false)) . " ", true);
					$num_ok = os_agents_ok($id);
					$num_critical = os_agents_critical($id);
					$num_warning = os_agents_warning($id);
					$num_unknown = os_agents_unknown($id);
					break;
				case 'group':
					$id = $item['id_grupo'];
					$name = $item['nombre'];
					$iconImg = html_print_image ("images/groups_small/" . groups_get_icon($item['id_grupo']).".png", true, array ("style" => 'vertical-align: middle; width: 16px; height: 16px;'));
					$num_ok = groups_agent_ok($id);
					$num_critical = groups_agent_critical($id);
					$num_warning = groups_agent_warning($id);
					$num_unknown = groups_agent_unknown ($id);
					break;
				case 'module_group':
					$id = $item['id_mg'];
					$name = $item['name'];
					$num_ok = modules_group_agent_ok($id);
					$num_critical = modules_group_agent_critical ($id);
					$num_warning = modules_group_agent_warning($id);
					$num_unknown = modules_group_agent_unknown($id);
					break;
				case 'policies':
					$id = $item['id'];
					$name = $item['name'];
					$num_ok = policies_agents_ok($id);
					$num_critical = policies_agents_critical($id);
					$num_warning = policies_agents_warning($id);
					$num_unknown = policies_agents_unknown($id);
					break;
				case 'module':
					$id = str_replace(array(' ','#','/'), array('_articapandora_'.ord(' ').'_pandoraartica_', '_articapandora_'.ord('#').'_pandoraartica_', '_articapandora_'.ord('/').'_pandoraartica_'),io_safe_output($item['nombre']));
					$name = io_safe_output($item['nombre']);
					$module_name = $item['nombre'];
					$num_ok = modules_agents_ok($module_name);
					$num_critical = modules_agents_critical($module_name);
					$num_warning = modules_agents_warning($module_name);
					$num_unknown = modules_agents_unknown($module_name);
					break;
			}
			
			$lessBranchs = 0;
			if ($first) {
				if ($item != end($list)) {
					$img = html_print_image ("operation/tree/first_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image_" . $type . "_" . $id, "pos_tree" => "0"));
					$first = false;
				}
				else {
					$lessBranchs = 1;
					$img = html_print_image ("operation/tree/one_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image_" . $type . "_" . $id, "pos_tree" => "1"));
				}
			}
			else {
				if ($item != end($list))
					$img = html_print_image ("operation/tree/closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image_" . $type . "_" . $id, "pos_tree" => "2"));
				else
				{
					$lessBranchs = 1;
					$img = html_print_image ("operation/tree/last_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image_" . $type . "_" . $id, "pos_tree" => "3"));
				}
			}
			
			echo "<li style='margin: 0px 0px 0px 0px;'>
				<a onfocus='JavaScript: this.blur()' href='javascript: loadSubTree(\"" . $type . "\",\"" . $id . "\", " . $lessBranchs . ", \"\")'>" .
				$img . $iconImg ."&nbsp;" . __($name) . ' ('.
				'<span class="green">'.'<b>'.$num_ok.'</b>'.'</span>'. 
				' : <span class="red">'.$num_critical.'</span>' .
				' : <span class="yellow">'.$num_warning.'</span>'.
				' : <span class="grey">'.$num_unknown.'</span>'.') '. "</a>";
			
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
?>