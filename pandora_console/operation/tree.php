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

global $config;

if (is_ajax ())
{
	
	function printTable($id_agente) {
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
		echo '<table cellspacing="4" cellpadding="4" border="0" class="databox" style="width:15%">';
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
		if ($agent['url_address'] != ''){
			echo '<tr><td class="datos"><b>'.__('Url address').'</b></td>';	
			echo '<td class="datos2" colspan="2"><a href='.$agent["url_address"].'>' . $agent["url_address"] . '</a></td></tr>';
		}

		// Last contact
		echo '<tr><td class="datos2"><b>'.__('Last contact')." / ".__('Remote').'</b></td><td class="datos2 f9" colspan="2">';
		ui_print_timestamp ($agent["ultimo_contacto"]);

		echo " / ";

		if ($agent["ultimo_contacto_remoto"] == "01-01-1970 00:00:00") { 
			echo __('Never');
		} else {
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
		if ($fields)
		foreach($fields as $field) {
			echo '<tr><td class="datos"><b>'.$field['name'] . ui_print_help_tip (__('Custom field'), true).'</b></td>';
			$custom_value = db_get_value_filter('description', 'tagent_custom_data', array('id_field' => $field['id_field'], 'id_agent' => $id_agente));
			if($custom_value === false || $custom_value == '') {
				$custom_value = '<i>-'.__('empty').'-</i>';
			}
			echo '<td class="datos f9" colspan="2">'.$custom_value.'</td></tr>';
		}

		//End of table
		echo '</table></div>';
		
		// Blank space below title, DONT remove this, this
		// Breaks the layout when Flash charts are enabled :-o
		echo '<div id="id_div" style="height: 10px">&nbsp;</div>';	
			
			//Floating div
			echo '<div id="agent_access" width:35%; padding-top:11px;">';

		if ($config["agentaccess"]){
			echo '<b>'.__('Agent access rate (24h)').'</b><br />';

			graphic_agentaccess($id_agente, 280, 110, 86400);
		}

		echo '<br>';
		echo graphic_agentevents ($id_agente, 290, 60, 86400, '');
		
		echo '</div>';
			
		echo '<form id="agent_detail" method="post" action="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'">';
			echo '<div class="action-buttons" style="width: '.$table->width.'">';
				html_print_submit_button (__('Go to agent detail'), 'upd_button', false, 'class="sub upd"');
			echo '</div>';
		echo '</form>';
			
		return;
}
	
	require_once ('include/functions_reporting.php');
	require_once ('include/functions_users.php');
	require_once ('include/functions_servers.php');

	global $config;
	
	$enterpriseEnable = false;
	if (enterprise_include_once('include/functions_policies.php') !== ENTERPRISE_NOT_HOOK) {
		$enterpriseEnable = true;
		require_once ('enterprise/include/functions_policies.php');
	}
	
	$type = get_parameter('type');
	$id = get_parameter('id');
	$id_father = get_parameter('id_father');
	$statusSel = get_parameter('status');
	$search_free = get_parameter('search_free', '');
	$printTable = get_parameter('printTable', 0);
		
	if ($printTable) {
		$id_agente = get_parameter('id_agente');
		printTable($id_agente);
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
			
			$avariableGroups = users_get_groups();
			$avariableGroupsIds = array_keys($avariableGroups);
			
			$countRows = 0;
			
			if (!empty($avariableGroupsIds)) {
				$extra_sql = enterprise_hook('policies_get_agents_sql_condition');
				if($extra_sql != '') {
					$extra_sql .= ' OR';
				}
				$groups_sql = implode(', ', $avariableGroupsIds);
				
				if ($search_free != '') {
					$search_sql = " AND nombre COLLATE utf8_general_ci LIKE '%$search_free%'";
				} else {
					$search_sql = '';
				}
				
				//Extract all rows of data for each type
				switch ($type) {
					case 'group':
						$sql = sprintf('SELECT * FROM tagente 
								WHERE id_grupo = %s AND (%s id_grupo IN (%s))', $id, $extra_sql, $groups_sql);
						break;
					case 'os':
						$sql = sprintf('SELECT * FROM tagente 
								WHERE id_os = %s AND (%s id_grupo IN (%s))', $id, $extra_sql, $groups_sql);
						break;
					case 'module_group':
						$extra_sql = substr($extra_sql,1);
						$extra_sql = "tagente_modulo.".$extra_sql;  
						$sql = sprintf('SELECT * FROM tagente 
								WHERE id_agente IN (SELECT tagente_modulo.id_agente FROM tagente_modulo, tagente_estado
								WHERE tagente_modulo.id_agente = tagente_estado.id_agente AND tagente_estado.utimestamp !=0 AND 
								tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo 
								AND id_module_group = %s 
								AND ((%s id_grupo IN (%s)))', $id, $extra_sql, $groups_sql);					
						break;
					case 'policies':
						if ($id == 0)
							$queryWhere = 'id_agente NOT IN (SELECT id_agent FROM tpolicy_agents)';
						else
							$queryWhere = sprintf(' id_agente IN (SELECT id_agent FROM tpolicy_agents WHERE id_policy = %s)',$id);
							
						$sql = sprintf('SELECT * FROM tagente 
								WHERE %s AND ( %s id_grupo IN (%s))', $queryWhere, $extra_sql, $groups_sql);
						break;
					case 'module':
						//Replace separator token "articapandora_32_pandoraartica_" for " "
						//example:
						//			"Load_articapandora_32_pandoraartica_Average"
						//result -> "Load Average"
						$name = str_replace(array('_articapandora_'.ord(' ').'_pandoraartica_', '_articapandora_'.ord('#').'_pandoraartica_'),array(' ','#'),$id);

						$sql = sprintf('SELECT *
							FROM tagente
							WHERE id_agente IN (
									SELECT id_agente
									FROM tagente_modulo
									WHERE nombre COLLATE utf8_general_ci LIKE \'%s\'
								)
								AND (%s id_grupo IN (%s))', $name, $extra_sql, $groups_sql);
						break;
				}
				
				$sql .= ' AND disabled = 0'. $search_sql;

				$countRows = db_get_num_rows($sql);
			}
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
			
			$new = true;
			$count = 0;
			echo "<ul style='margin: 0; padding: 0;'>\n";

			while($row = db_get_all_row_by_steps_sql($new, $result, $sql)) {
				$new = false;
				$count++;
				switch ($type) {
					case 'group':
					case 'os':
					case 'policies':
						$agent_info["monitor_alertsfired"] = agents_get_alerts_fired ($row["id_agente"]);
						
						$agent_info["monitor_critical"] = agents_monitor_critical ($row["id_agente"]);
						$agent_info["monitor_warning"] = agents_monitor_warning ($row["id_agente"]);
						$agent_info["monitor_unknown"] = agents_monitor_unknown ($row["id_agente"]);
						$agent_info["monitor_normal"] = agents_monitor_ok ($row["id_agente"]);
						
						$agent_info["alert_img"] = agents_tree_view_alert_img ($agent_info["monitor_alertsfired"]);
						
						$agent_info["status_img"] = agetns_tree_view_status_img ($agent_info["monitor_critical"],
																				$agent_info["monitor_warning"],
																				$agent_info["monitor_unknown"]);
												
						//Count all modules
						$agent_info["modules"] = $agent_info["monitor_critical"] + $agent_info["monitor_warning"] + $agent_info["monitor_unknown"] + $agent_info["monitor_normal"];
						break;
					case 'module_group':
						$agent_info["monitor_alertsfired"] = agents_get_alerts_fired ($row["id_agente"], "id_module_group = $id");
						
						$agent_info["monitor_critical"] = agents_monitor_critical($row["id_agente"], "tagente_modulo.id_module_group = $id");
						$agent_info["monitor_warning"] = agents_monitor_warning ($row["id_agente"], "tagente_modulo.id_module_group = $id");
						$agent_info["monitor_unknown"] = agents_monitor_unknown ($row["id_agente"], "tagente_modulo.id_module_group = $id");
						$agent_info["monitor_normal"] = agents_monitor_ok ($row["id_agente"], "tagente_modulo.id_module_group = $id");
						
						$agent_info["alert_img"] = agents_tree_view_alert_img ($agent_info["monitor_alertsfired"]);
						
						$agent_info["status_img"] = agetns_tree_view_status_img ($agent_info["monitor_critical"],
																				$agent_info["monitor_warning"],
																				$agent_info["monitor_unknown"]);
												
						//Count all modules
						$agent_info["modules"] = $agent_info["monitor_critical"] + $agent_info["monitor_warning"] + $agent_info["monitor_unknown"] + $agent_info["monitor_normal"];
						
						break;
					case 'module':
						switch ($config["dbtype"]) {
							case "mysql":
								$agent_info = reporting_get_agent_module_info ($row["id_agente"], ' nombre COLLATE utf8_general_ci LIKE "' . $name . '"');
								break;
							case "postgresql":
							case "oracle":
								$agent_info = reporting_get_agent_module_info ($row["id_agente"], ' nombre COLLATE utf8_general_ci LIKE \'' . $name . '\'');
								break;
						}
						break;
				}

				if ($statusSel == ALL) {
				}
				else if ($statusSel == NORMAL) {
					if ($agent_info['status'] != 'agent_ok.png')
						continue;
				} else if ($statusSel == WARNING) {
					if ($agent_info['status'] != 'agent_warning.png')
						continue;
				} else if ($statusSel  == CRITICAL) {
					if ($agent_info['status'] != 'agent_critical.png')
						continue;
				} else if ($statusSel  == UNKNOWN) {
					if ($agent_info['status'] != 'agent_down.png')
						continue;
				}
			
				$less = $lessBranchs;
				if ($count != $countRows)
					$img = html_print_image ("operation/tree/closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $id . "_agent_" . $type . "_" . $row["id_agente"], "pos_tree" => "2"));
				else {
					$less = $less + 2; // $less = $less or 0b10
					$img = html_print_image ("operation/tree/last_closed.png", true, array ("style" => 'vertical-align: middle;', "id" => "tree_image" . $id . "_agent_" . $row["id_agente"], "pos_tree" => "3"));
				}
				echo "<li style='margin: 0; padding: 0;'>";
				echo "<a onfocus='JavaScript: this.blur()'
					href='javascript: loadSubTree(\"agent_" . $type . "\"," . $row["id_agente"] . ", " . $less . ", \"" . $id . "\")'>";
				
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
					href='javascript: loadTable(\"agent_" . $type . "\"," . $row["id_agente"] . ", " . $less . ", \"" . $id . "\")'>";
				echo " ";
					
				echo $row["nombre"];

				echo " (";
				echo '<b>';
				echo $agent_info["modules"];
				echo '</b>';
				if ($agent_info["monitor_alertsfired"] > 0)
					echo ' : <span class="orange">'.$agent_info["monitor_alertsfired"].'</span>';
				if ($agent_info["monitor_critical"] > 0)
					echo ' : <span class="red">'.$agent_info["monitor_critical"].'</span>';
				if ($agent_info["monitor_warning"] > 0)
					echo ' : <span class="yellow">'.$agent_info["monitor_warning"].'</span>';
				if ($agent_info["monitor_unknown"] > 0)
					echo ' : <span class="grey">'.$agent_info["monitor_unknown"].'</span>';
				if ($agent_info["monitor_normal"] > 0)
					echo ' : <span class="green">'.$agent_info["monitor_normal"].'</span>';
				echo ")";
				if ($agent_info["last_contact"]!='') {
					echo " (";
					ui_print_timestamp ($agent_info["last_contact"]);
					echo ")";
				}
				echo "</a>";
				echo "<div hiddenDiv='1' loadDiv='0' style='margin: 0px; padding: 0px;' class='tree_view' id='tree_div" . $id . "_agent_" . $type . "_" . $row["id_agente"] . "'></div>";
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
			$fatherType = str_replace('agent_', '', $type);
			
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
					
					$sql = 'SELECT * 
						FROM tagente_modulo AS t1 
						INNER JOIN tagente_estado AS t2 ON t1.id_agente_modulo = t2.id_agente_modulo
						WHERE t1.id_agente = ' . $id . $whereQuery;
					break;
				case 'module':
					$name = str_replace(array('_articapandora_'.ord(' ').'_pandoraartica_', '_articapandora_'.ord('#').'_pandoraartica_'),array(' ','#'),$id_father);
					switch ($config["dbtype"]) {
						case "mysql":
							$sql = 'SELECT * 
								FROM tagente_modulo AS t1 
								INNER JOIN tagente_estado AS t2 ON t1.id_agente_modulo = t2.id_agente_modulo
								WHERE t1.id_agente = ' . $id . ' AND nombre COLLATE utf8_general_ci LIKE \'' . io_safe_input($name) . '\'';
							break;
						case "postgresql":
						case "oracle":
							$sql = 'SELECT * 
								FROM tagente_modulo AS t1 
								INNER JOIN tagente_estado AS t2 ON t1.id_agente_modulo = t2.id_agente_modulo
								WHERE t1.id_agente = ' . $id . ' AND nombre  COLLATE utf8_general_ciLIKE \'' . io_safe_input($name) . '\'';
							break;
					}
					break;
			}
			// This line checks for initializated modules or (non-initialized) asyncronous modules	
			$sql .= ' AND disabled = 0 AND (utimestamp > 0 OR id_tipo_modulo IN (21,22,23))';
			$countRows = db_get_num_rows($sql);
			
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
			
			$new = true;
			$count = 0;
			echo "<ul style='margin: 0; padding: 0;'>\n";
			while($row = db_get_all_row_by_steps_sql($new, $result, $sql)) {
				$new = false;
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
		
				// This line checks for (non-initialized) asyncronous modules
				if ($row["estado"] == 0 AND $row["utimestamp"] == 0 AND ($row["id_tipo_modulo"] >= 21 AND $row["id_tipo_modulo"] <= 23)){
					$status = STATUS_MODULE_NO_DATA;
                                        $title = __('UNKNOWN');

				} // Else checks module status				
				elseif ($row["estado"] == 1) {
					$status = STATUS_MODULE_CRITICAL;
					$title = __('CRITICAL');
				}
				elseif ($row["estado"] == 2) {
					$status = STATUS_MODULE_WARNING;
					$title = __('WARNING');
				}
				elseif ($row["estado"] == 3) {
					$status = STATUS_MODULE_NO_DATA;
					$title = __('UNKNOWN');
				}
				else {
					$status = STATUS_MODULE_OK;
					$title = __('NORMAL');
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
				$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&period=86400&id=".$row["id_agente_modulo"]."&label=".base64_encode($row["nombre"])."&refresh=600','day_".$win_handle."')";
				echo '<a href="javascript:'.$link.'">' . html_print_image ("images/chart_curve.png", true, array ("style" => 'vertical-align: middle;', "border" => "0" )) . '</a>';
				echo " ";
				echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=" . $row['id_agente'] . "&tab=data_view&period=86400&id=".$row["id_agente_modulo"]."'>" . html_print_image ("images/binary.png", true, array ("style" => 'vertical-align: middle;', "border" => "0" )) . "</a>";
				echo " ";
				echo io_safe_output($row['nombre']);
				
				if (is_numeric($row["datos"]))
					$data = format_numeric($row["datos"]);
				else
					$data = "<span title='".$row['datos']."' style='white-space: nowrap;'>".substr(io_safe_output($row["datos"]),0,12)."</span>";
				
				echo "</span><span style='margin-left: 20px;'>";
					echo $data;
					if ($row['unit'] != '') {
						echo "&nbsp;";
						echo '('.$row['unit'].')';
					}
					if ($row['utimestamp'] != '') {
						echo "&nbsp;";
						ui_print_help_tip ($row["timestamp"], '', 'images/clock2.png');
					}
				echo "</span></li>";
			}
			echo "</ul>\n";
			break;
	}

	
	return;

}


include_once($config['homedir'] . "/include/functions_groups.php");
include_once($config['homedir'] . "/include/functions_os.php");
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_servers.php");
include_once($config['homedir'] . "/include/functions_reporting.php");
include_once($config['homedir'] . "/include/functions_ui.php");


function printTree_($type) {
	global $config;
	
	$search_free = get_parameter('search_free', '');
	$select_status = get_parameter('status', -1);

	echo '<table class="databox" style="width:98%">';
	echo '<tr><td style="width:60%" valign="top">';
	
	//Get all groups
	$avariableGroups = users_get_groups (); //db_get_all_rows_in_table('tgrupo', 'nombre');	
	
	//Get all groups with agents
	$full_groups = db_get_all_rows_sql("SELECT DISTINCT id_grupo FROM tagente");
	
	$fgroups = array();
	
	foreach ($full_groups as $fg) {
		$fgroups[$fg['id_grupo']] = "";
	}

	//We only want groups with agents, so we need the intesect of both arrays.
	$avariableGroups = array_intersect_key($avariableGroups, $fgroups);
	
	$avariableGroupsIds = implode(',',array_keys($avariableGroups));
	if($avariableGroupsIds == ''){
		$avariableGroupsIds == -1;
	}
	switch ($type) {
		default:
		case 'os':
			if ($search_free != '') {
				$sql = "SELECT * FROM tconfig_os 
						WHERE id_os IN (SELECT id_os FROM tagente 
									WHERE nombre COLLATE utf8_general_ci LIKE '%$search_free%')";
				$list = db_get_all_rows_sql($sql);
			} else {
				$list = db_get_all_rows_sql("SELECT DISTINCT (tagente.id_os), tconfig_os.name FROM tagente, tconfig_os WHERE tagente.id_os = tconfig_os.id_os");
			}
			break;
		case 'group':
			$stringAvariableGroups = (
					implode(', ',
						array_map(
							create_function('&$itemA', '{ return "\'" . $itemA . "\'"; }'), $avariableGroups
							)
						)
				);
				if ($search_free != '') {
					$sql_search = " AND id_grupo IN (SELECT id_grupo FROM tagente
									WHERE nombre COLLATE utf8_general_ci LIKE '%$search_free%')";
				} else {
					$sql_search ='';
				}
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
			if ($search_free != '') {
				$sql = "SELECT * FROM tmodule_group
						WHERE id_mg IN (SELECT id_module_group FROM tagente_modulo 
										WHERE id_agente IN (SELECT id_agente FROM tagente
														WHERE nombre COLLATE utf8_general_ci LIKE '%$search_free%'))";
				$list = db_get_all_rows_sql($sql);
			} else {
				$list = db_get_all_rows_sql("SELECT distinct id_mg, name FROM tmodule_group, tagente_modulo WHERE tmodule_group.id_mg = tagente_modulo.id_module_group");
				if ($list !== false)
					array_push($list, array('id_mg' => 0, 'name' => 'Not assigned'));
			}
			break;
		case 'policies':
			$groups_id = array_keys($avariableGroups);
			$groups = implode(',',$groups_id);
			
			if ($search_free != '') {

				$sql = "SELECT * FROM tpolicies
						WHERE id_group IN ($groups) 
						AND id IN (SELECT id_policy FROM tpolicy_agents
								WHERE id_agent IN (SELECT id_agente FROM tagente
											WHERE nombre COLLATE utf8_general_ci LIKE '%$search_free%'))";
				$list = db_get_all_rows_sql($sql);
			} else {
				
				$list = db_get_all_rows_sql("SELECT id, name FROM tpolicies WHERE id_group IN  ($groups) AND id IN (SELECT DISTINCT id_policy FROM tpolicy_agents)");
				if ($list !== false)
					array_push($list, array('id' => 0, 'name' => 'No policy'));
			}
			break;
		case 'module':
			if ($search_free != '') {
					$sql_search = " AND t1.id_agente IN (SELECT id_agente FROM tagente
									WHERE nombre COLLATE utf8_general_ci LIKE '%$search_free%')";
				} else {
					$sql_search ='';
				}
			switch ($config["dbtype"]) {
				case "mysql":
				case "postgresql":
					$list = db_get_all_rows_sql('SELECT t1.nombre
						FROM tagente_modulo t1, tagente t2, tagente_estado t3
						WHERE t1.id_agente = t2.id_agente AND t1.id_agente_modulo = t3.id_agente_modulo
						AND t3.utimestamp !=0 AND t2.id_grupo in (' . $avariableGroupsIds . ')' .$sql_search.'
						GROUP BY t1.nombre ORDER BY t1.nombre');
					break;
				case "oracle":	
					$list = db_get_all_rows_sql('SELECT dbms_lob.substr(t1.nombre,4000,1) as nombre
						FROM tagente_modulo t1, tagente t2, tagente_estado t3
						WHERE t1.id_agente = t2.id_agente AND t2.id_grupo in (' . $avariableGroupsIds . ') AND t1.id_agente_modulo = t3.id_agente_modulo
						AND t3.utimestamp !=0 GROUP BY dbms_lob.substr(t1.nombre,4000,1) ORDER BY dbms_lob.substr(t1.nombre,4000,1) ASC');
					break;
			}		
			break;
	}
	
	if ($list === false) {
		ui_print_error_message("There aren't agents in this agrupation");

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
					$id = str_replace(array(' ','#'), array('_articapandora_'.ord(' ').'_pandoraartica_', '_articapandora_'.ord('#').'_pandoraartica_'),io_safe_output($item['nombre']));
					$id = str_replace ("/", "_", $id);
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
			echo '<div id="cont">';
				echo '&nbsp;';
			echo'</div>';
		echo '</td></tr>';
	echo '</table>';

	}

}


global $config;

$enterpriseEnable = false;
if (enterprise_include_once('include/functions_policies.php') !== ENTERPRISE_NOT_HOOK) {
	$enterpriseEnable = true;
}

/////////	INI MENU AND TABS /////////////
$img_style = array ("class" => "top", "width" => 16);
$activeTab = get_parameter('sort_by','group');

$os_tab = array('text' => "<a href='index.php?sec=estado&sec2=operation/tree&refr=0&sort_by=os'>"
			. html_print_image ("images/computer.png", true, array ("title" => __('OS'))) . "</a>", 'active' => $activeTab == "os");

$group_tab = array('text' => "<a href='index.php?sec=estado&sec2=operation/tree&refr=0&sort_by=group'>"
			. html_print_image ("images/group.png", true, array ("title" => __('Groups'))) . "</a>", 'active' => $activeTab == "group");

$module_group_tab = array('text' => "<a href='index.php?sec=estado&sec2=operation/tree&refr=0&sort_by=module_group'>"
			. html_print_image ("images/agents_group.png", true, array ("title" => __('Module groups'))) . "</a>", 'active' => $activeTab == "module_group");

if ($enterpriseEnable) {
	$policies_tab = array('text' => "<a href='index.php?sec=estado&sec2=operation/tree&refr=0&sort_by=policies'>"
				. html_print_image ("images/policies.png", true, array ("title" => __('Policies'))) . "</a>", 'active' => $activeTab == "policies");
} else {
	$policies_tab = '';
}

$module_tab = array('text' => "<a href='index.php?extension_in_menu=estado&sec=estado&sec2=operation/tree&refr=0&sort_by=module'>"
			. html_print_image ("images/brick.png", true, array ("title" => __('Modules'))) . "</a>", 'active' => $activeTab == "module");

$onheader = array('os' => $os_tab, 'group' => $group_tab, 'module_group' => $module_group_tab, 'policies' => $policies_tab, 'module' => $module_tab);
		
ui_print_page_header (__('Tree view')." - ".__('Sort the agents by'), "images/extensions.png", false, "", false, $onheader);


echo "<br>";
echo '<form id="tree_search" method="post" action="index.php??extension_in_menu=estado&sec=estado&sec2=operation/tree&refr=0&sort_by='.$activeTab.'">';
echo "<b>" . __('Monitor status') . "</b>";

$search_free = get_parameter('search_free', '');
$select_status = get_parameter('status', -1);

$fields = array ();
$fields[ALL] = __('All'); //default
$fields[NORMAL] = __('Normal'); 
$fields[WARNING] = __('Warning');
$fields[CRITICAL] = __('Critical');
$fields[UNKNOWN] = __('Unknown');

html_print_select ($fields, "status", $select_status);

echo "&nbsp;&nbsp;&nbsp;";
echo "<b>" . __('Search agent') .  ui_print_help_tip (__('Case sensitive'))."</b>";
echo "&nbsp;";
html_print_input_text ("search_free", $search_free, '', 40,30, false);
echo "&nbsp;&nbsp;&nbsp;";
html_print_submit_button (__('Show'), "uptbutton", false, 'class="sub search"');
echo "</form>";
echo "<div class='pepito' id='a'></div>";
echo "<div class='pepito' id='b'></div>";
echo "<div class='pepito' id='c'></div>";
/////////	END MENU AND TABS /////////////
printTree_($activeTab);

?>

<script language="javascript" type="text/javascript">
	
	status = $('#status').val();
	search_free = $('#text-search_free').val();
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
		 function loadSubTree(type, div_id, less_branchs, id_father) {
			hiddenDiv = $('#tree_div'+id_father+'_'+type+'_'+div_id).attr('hiddenDiv');
			loadDiv = $('#tree_div'+id_father+'_'+type+'_'+div_id).attr('loadDiv');
			pos = parseInt($('#tree_image'+id_father+'_'+type+'_'+div_id).attr('pos_tree'));	
			
			//If has yet ajax request running
			if (loadDiv == 2)
				return;
			
			if (loadDiv == 0) {
				
				//Put an spinner to simulate loading process
				$('#tree_div'+id_father+'_'+type+'_'+div_id).html("<img style='padding-top:10px;padding-bottom:10px;padding-left:20px;' src=images/spinner.gif>");
				$('#tree_div'+id_father+'_'+type+'_'+div_id).show('normal');
				
				$('#tree_div'+id_father+'_'+type+'_'+div_id).attr('loadDiv', 2);
				$.ajax({
					type: "POST",
					url: "ajax.php",
					data: "page=<?php echo $_GET['sec2']; ?>&ajax_treeview=1&type=" + 
						type + "&id=" + div_id + "&less_branchs=" + less_branchs + "&id_father=" + id_father + "&status=" + status + "&search_free=" + search_free,
					success: function(msg){
						if (msg.length != 0) {
							$('#tree_div'+id_father+'_'+type+'_'+div_id).hide();
							$('#tree_div'+id_father+'_'+type+'_'+div_id).html(msg);
							$('#tree_div'+id_father+'_'+type+'_'+div_id).show('normal');
							
							//change image of tree [+] to [-]
							switch (pos) {
								case 0:
									$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/first_expanded.png');
									break;
								case 1:
									$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/one_expanded.png');
									break;
								case 2:
									$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/expanded.png');
									break;
								case 3:
									$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/last_expanded.png');
									break;
							}
							$('#tree_div'+id_father+'_'+type+'_'+div_id).attr('hiddendiv',0);
							$('#tree_div'+id_father+'_'+type+'_'+div_id).attr('loadDiv', 1);
						}
					}
				});
			}
			else {
				if (hiddenDiv == 0) {
					$('#tree_div'+id_father+'_'+type+'_'+div_id).hide('normal');
					$('#tree_div'+id_father+'_'+type+'_'+div_id).attr('hiddenDiv',1);
					
					//change image of tree [-] to [+]
					switch (pos) {
						case 0:
							$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/first_closed.png');
							break;
						case 1:
							$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/one_closed.png');
							break;
						case 2:
							$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/closed.png');
							break;
						case 3:
							$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/last_closed.png');
							break;
					}
				}
				else {
					//change image of tree [+] to [-]
					switch (pos) {
						case 0:
							$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/first_expanded.png');
							break;
						case 1:
							$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/one_expanded.png');
							break;
						case 2:
							$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/expanded.png');
							break;
						case 3:
							$('#tree_image'+id_father+'_'+type+'_'+div_id).attr('src','operation/tree/last_expanded.png');
							break;
					}
					
					$('#tree_div'+id_father+'_'+type+'_'+div_id).show('normal');
					$('#tree_div'+id_father+'_'+type+'_'+div_id).attr('hiddenDiv',0);
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
		
		function loadTable(type, div_id, less_branchs, id_father) {
			id_agent = div_id;				
			$.ajax({
				type: "POST",
				url: "ajax.php",
				data: "page=<?php echo $_GET['sec2']; ?>&printTable=1&id_agente=" + 
				id_agent, success: function(data){
					$('#cont').html(data);	
				}
			});
			
			loadSubTree(type, div_id, less_branchs, id_father);		
	}
</script>

