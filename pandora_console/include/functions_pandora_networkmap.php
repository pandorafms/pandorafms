<?php
// ______                 __                     _______ _______ _______
//|   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
//|    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
//|___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2010 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================

function networkmap_get_policies($id_group) {
	enterprise_include_once("include/functions_policies.php");
	
	$filter_policy = array();
	$filter_policy['id_group'] = $id_group;
	
	$policies = policies_get_policies($filter_policy);
	if ($policies === false) {
		$policies = array();
	}
	
	return $policies;
}

function networkmap_filter_agents_policies($policies, $agents) {
	enterprise_include_once("include/functions_policies.php");
	
	$temp = array();
	foreach ($policies as $policy) {
		foreach ($agents as $i => $agent) {
			$exists = (bool)db_get_value_filter(
				'id', 'tpolicy_agents',
				array('id_agent' => $agent['id_agente'],
					'id_policy' => $policy['id']));
			
			if ($exists) {
				$temp[] = $agent;
			}
		}
	}
	
	return $temp;
}

function networkmap_delete_networkmap($id = 0) {
	///TODO delete in the table tnetworkmap_enterprise_item
	
	$result = db_process_sql_delete('tnetworkmap_enterprise', array('id' => $id));
	
	return $result;
}

function networkmap_process_networkmap($id = 0) {
	global $config;
	
	require_once ('include/functions_os.php');
	
	$networkmap = db_get_row_filter('tnetworkmap_enterprise',
		array('id' => $id));
	$options = json_decode($networkmap['options'], true);
	
	$pure = (int)get_parameter('pure', 0);
	
	//TODO: Params by default at default.
	$simple = 0;
	$font_size = 12;
	$layout = $options['method'];
	$nooverlap = false;
	$zoom = 1;
	$ranksep = 2.5;
	$center = 0;
	$regen = 1;
	$show_snmp_modules = false;
	$filter = $options['method'];
	$l2_network_interfaces = false;
	if (isset($options['l2_network_interfaces']))
		$l2_network_interfaces = (bool)$options['l2_network_interfaces'];
	
	// --------- DEPRECATED --------------------------------------------
	$old_mode = false;
	if (isset($options['old_mode']))
		$old_mode = (bool)$options['old_mode'];
	// --------- END DEPRECATED ----------------------------------------
	
	$dont_show_subgroups = false;
	if (isset($options['dont_show_subgroups']))
		$dont_show_subgroups = (bool)$options['dont_show_subgroups'];
	
	$id_group = $networkmap['id_group'];
	if ($options['generation_process'] == 'empty') {
		$id_group = -666;
	}
	
	$ip_mask = '';
	if ($options['recon_task_id'] != 0) {
		$recon_task = db_get_row_filter('trecon_task',
			array('id_rt' => $options['recon_task_id']));
		
		$ip_mask = $recon_task['field1'];
	}
	if (!empty($options['ip_mask'])) {
		$ip_mask = $options['ip_mask'];
	}
	
	// Generate dot file
	$graph = networkmap_generate_dot (__('Pandora FMS'),
		$id_group,
		$simple,
		$font_size,
		$layout,
		$nooverlap,
		$zoom,
		$ranksep,
		$center,
		$regen,
		$pure,
		$id,
		$show_snmp_modules,
		false, //cut_names
		true, // relative
		'',
		$l2_network_interfaces,
		$ip_mask,
		$dont_show_subgroups,
		false,
		null,
		$old_mode);
	
	
	$filename_dot = sys_get_temp_dir() . "/networkmap_" . $filter;
	if ($simple) {
		$filename_dot .= "_simple";
	}
	if ($nooverlap) {
		$filename_dot .= "_nooverlap";
	}
	$filename_dot .= "_" . $id . ".dot";
	
	file_put_contents($filename_dot, $graph);
	
	$filename_plain = sys_get_temp_dir() . "/plain.txt";
	
	$cmd = "$filter -Tplain -o " . $filename_plain . " " .
		$filename_dot;
	
	system ($cmd);
	
	unlink($filename_dot);
	
	$nodes = networkmap_loadfile($id, $filename_plain,
		$relation_nodes, $graph, $l2_network_interfaces);
	
	
	
	if ($l2_network_interfaces) {
		//Set the position of modules
		foreach ($nodes as $key => $node) {
			if ($node['type'] == 'module') {
				//Search the agent of this module for to get the
				//position
				foreach ($nodes as $key2 => $node2) {
					if ($node2['id_agent'] != 0 && $node2['type'] == 'agent') {
						if ($node2['id_agent'] == $node['id_agent']) {
							$nodes[$key]['coords'][0] =
								$nodes[$key2]['coords'][0] + $node['height'] / 2;
							$nodes[$key]['coords'][1] =
								$nodes[$key2]['coords'][1] + $node['width'] / 2;
						}
					}
				}
			}
		}
	}
	
	
	unlink($filename_plain);
	
	$array_key_to_db_id = array();
	foreach ($nodes as $key => $node) {
		$values = array();
		$values['id_networkmap_enterprise'] = $id;
		$values['x'] = (int)$node['coords'][0];
		$values['y'] = (int)$node['coords'][1];
		$values['parent'] = 0;
			$options = array();
			$options['type'] = '';
			if ($l2_network_interfaces) {
				$options['type'] = $node['type'];
				if ($node['type'] == 'agent') {
					switch (os_get_name(agents_get_os($node['id_agent']))) {
						case 'Router':
							$options['shape'] = 'circle';
							break;
						case 'Switch':
							$options['shape'] = 'circle'; //Sancho says
							//$options['shape'] = 'rhombus';
							break;
						default:
							$options['shape'] = 'circle'; //Sancho says
							//$options['shape'] = 'square';
							break;
					}
				}
				else {
					$options['shape'] = 'arrowhead';
				}
			}
			else {
				$options['shape'] = 'circle';
			}
			$options['image'] = $node['image'];
			$options['width'] = $node['width'];
			$options['height'] = $node['height'];
			$options['text'] = $node['text'];
		$values['options'] = json_encode($options);
		$values['id_agent'] = $node['id_agent'];
		$values['id_module'] = $node['id_module'];
		
		$id_or_result = db_process_sql_insert(
			'tnetworkmap_enterprise_nodes', $values);
		
		if ($id_or_result !== false) {
			$id_node = $id_or_result;
			$array_key_to_db_id[$key] = $id_node;
			
		}
	}
	
	foreach ($relation_nodes as $relation) {
		$values = array();
		
		$values['parent'] = $array_key_to_db_id[$relation['parent']];
		$values['child'] = $array_key_to_db_id[$relation['child']];
		$values['parent_type'] = $relation['parent_type']; 
		$values['child_type'] = $relation['child_type'];
		$values['id_networkmap_enterprise'] = $id;
		db_process_sql_insert('tnetworkmap_ent_rel_nodes',
			$values);
	}
	
	//-------Set center map---------------------------------------------
	if ($l2_network_interfaces) {
		$center = db_get_row('tnetworkmap_enterprise_nodes',
			'id_networkmap_enterprise', $id);
	}
	else {
		$center = db_get_row_filter('tnetworkmap_enterprise_nodes',
			array('id_networkmap_enterprise' => $id, 'id_agent' => -1));
	}
	
	$networkmap = db_get_row('tnetworkmap_enterprise', 'id', $id);
	$options = json_decode($networkmap['options'], true);
	$options['center_x'] = $center['x'];
	$options['center_y'] = $center['y'];
	$networkmap['options'] = json_encode($options);
	db_process_sql_update('tnetworkmap_enterprise',
		array('options' => $networkmap['options']),
		array('id' => $id));
}

function get_networkmaps($id) {
	$groups = array_keys(users_get_groups(null, "IW"));
	
	$filter = array();
	$filter['id_group'] = $groups;
	$filter['id'] = '<>' . $id;
	$networkmaps = db_get_all_rows_filter('tnetworkmap_enterprise',$filter);
	if ($networkmaps === false)
		$networkmaps = array();
	
	$return = array();
	$return[0] = __('None');
	foreach ($networkmaps as $networkmap) {
		$return[$networkmap['id']] = $networkmap['name'];
	}
	
	return $return;
}

function networkmap_db_node_to_js_node($node, &$count, &$count_item_holding_area) {
	global $config;
	
	
	$networkmap = db_get_row(
		'tnetworkmap_enterprise',
		'id', $node['id_networkmap_enterprise']);
	$networkmap['options'] = json_decode($networkmap['options'], true);
	
	//Hardcoded
	$networkmap['options']['holding_area'] = array(500, 500);
	
	
	//40 = DEFAULT NODE RADIUS
	//30 = for to align
	$holding_area_max_y = $networkmap['options']['height'] +
		30 + 40 * 2 - $networkmap['options']['holding_area'][1]
		+ 10 * 40;
	
	
	$item = array();
	$item['id'] = $count;
	$item['id_db'] = (int)$node['id'];
	$item['id_agent'] = (int)$node['id_agent'];
	$item['id_module'] = (int)$node['id_module'];
	$item['fixed'] = true;
	$item['x'] = (int)$node['x'];
	$item['y'] = (int)$node['y'];
	$item['z'] = (int)$node['z'];
	$item['state'] = $node['state'];
	if ($item['state'] == 'holding_area') {
		//40 = DEFAULT NODE RADIUS
		//30 = for to align
		$holding_area_x = $networkmap['options']['width'] +
			30 + 40 * 2 - $networkmap['options']['holding_area'][0]
			+ ($count_item_holding_area % 11) * 40;
		$holding_area_y = $networkmap['options']['height'] +
			30 + 40 * 2 - $networkmap['options']['holding_area'][1]
			+ (int)(($count_item_holding_area / 11)) * 40;
		if ($holding_area_max_y <= $holding_area_y) {
			$holding_area_y = $holding_area_max_y;
		}
		
		$item['x'] = $holding_area_x;
		$item['y'] = $holding_area_y;
		
		
		//Increment for the next node in holding area
		$count_item_holding_area++;
		
	}
	
	$item['image_url'] = "";
	$item['image_width'] = 0;
	$item['image_height'] = 0;
	if (!empty($node['options']['image'])) {
		$item['image_url'] = html_print_image(
			$node['options']['image'], true, false, true);
		$image_size = getimagesize(
			$config['homedir'] . '/' . $node['options']['image']);
		$item['image_width'] = (int)$image_size[0];
		$item['image_height'] = (int)$image_size[1];
	}
	$item['text'] = io_safe_output($node['options']['text']);
	$item['shape'] = $node['options']['shape'];
	switch ($node['options']['type']) {
		case 'agent':
			$color = get_status_color_networkmap($node['id_agent']);
			break;
		default:
			//Old code
			if ($node['id_agent'] == -1) {
				$color = "#364D1F";
			}
			else if ($node['id_agent'] == -2) {
				$color = $node['options']['color'];
			}
			else {
				$color = get_status_color_networkmap($node['id_agent']);
			}
			break;
	}
	$item['color'] = $color;
	$item['networkmap_id'] = 0;
	if (isset($node['options']['networkmap'])) {
		$item['networkmap_id'] = $node['options']['networkmap'];
	}
	
	$count++;
	
	return $item;
}

function networkmap_clean_relations_for_js(&$relations) {
	do {
		$cleaned = true;
		
		foreach ($relations as $key => $relation) {
			if ($relation['id_agent_parent'] == $relation['id_agent_child']) {
				$cleaned = false;
				
				if ($relation['parent_type'] == 'module') {
					$to_find = $relation['parent'];
					$to_replace = $relation['child'];
				}
				elseif ($relation['child_type'] == 'module') {
					$to_find = $relation['child'];
					$to_replace = $relation['parent'];
				}
				
				
				
				
				
				//Replace and erase the links
				foreach ($relations as $key2 => $relation2) {
					if ($relation2['parent'] == $to_find) {
						
						$relations[$key2]['parent'] = $to_replace;
					}
					elseif ($relation2['child'] == $to_find) {
						
						$relations[$key2]['child'] = $to_replace;
					}
				}
				
				unset($relations[$key]);
				
				break;
			}
		}
	}
	while (!$cleaned);
}

function networkmap_links_to_js_links($relations, $nodes_graph) {
	$return = array();
	
	foreach ($relations as $relation) {
		$item = array();
		$item['id_db'] = $relation['id'];
		$item['arrow_start'] = '';
		$item['arrow_end'] = '';
		$item['status_start'] = '';
		$item['status_end'] = '';
		$item['id_module_start'] = 0;
		$item['id_agent_start'] = $relation['id_agent_child'];
		$item['id_module_end'] = 0;
		$item['id_agent_end'] = $relation['id_agent_parent'];
		$item['target'] = -1;
		$item['source'] = -1;
		$item['target_id_db'] = $relation['parent'];
		$item['source_id_db'] = $relation['child'];
		$item['text_end'] = "";
		$item['text_start'] = "";
		
		if ($relation['parent_type'] == 'module') {
			$item['arrow_end'] = 'module';
			$item['status_end'] = modules_get_agentmodule_status(
				$relation['id_module_parent'], false, false, null);
			$item['id_module_end'] = $relation['id_module_parent'];
			$item['text_end'] = io_safe_output(
				modules_get_agentmodule_name(
					$item['id_module_end']));
		}
		if ($relation['child_type'] == 'module') {
			$item['arrow_start'] = 'module';
			$item['status_start'] = modules_get_agentmodule_status(
				$relation['id_module_child'], false, false, null);
			$item['id_module_start'] = $relation['id_module_child'];
			$item['text_start'] = io_safe_output(
				modules_get_agentmodule_name(
					$item['id_module_start']));
		}
		
		foreach ($nodes_graph as $node) {
			if ($node['id_db'] == $relation['parent']) {
				$item['target'] = $node['id'];
			}
			if ($node['id_db'] == $relation['child']) {
				$item['source'] = $node['id'];
			}
		}
		
		$return[] = $item;
	}
	
	return $return;
}

function networkmap_write_js_array($id) {
	global $config;
	
	db_clean_cache();
	
	$networkmap = db_get_row(
		'tnetworkmap_enterprise',
		'id', $id);
	$networkmap['options'] = json_decode($networkmap['options'], true);
	
	//Hardcoded
	$networkmap['options']['holding_area'] = array(500, 500);
	
	echo "\n";
	echo "////////////////////////////////////////////////////////////////////\n";
	echo "// VARS FROM THE DB\n";
	echo "////////////////////////////////////////////////////////////////////\n";
	echo "\n";
	echo "var url_background_grid = '" . ui_get_full_url(
		'images/background_grid.png') . "'\n";
	echo "var url_popup_pandora = '" . ui_get_full_url(
		'operation/agentes/networkmap_enterprise.popup.php') . "'\n";
	echo "var networkmap_id = " . $id . ";\n";
	echo "var networkmap_refresh_time = 1000 * " .
		$networkmap['options']['refresh_state'] . ";\n";
	echo "var networkmap_center = [ " .
		$networkmap['options']['center_x'] . ", " .
		$networkmap['options']['center_y'] . "];\n";
	echo "var networkmap_dimensions = [ " .
		$networkmap['options']['width'] . ", " .
		$networkmap['options']['height'] . "];\n";
	
	echo "var networkmap_holding_area_dimensions = " .
		json_encode($networkmap['options']['holding_area']) . ";\n";
	
	echo "var networkmap = {'nodes': [], 'links':  []};\n";
	
	$nodes = db_get_all_rows_filter('tnetworkmap_enterprise_nodes',
		array('id_networkmap_enterprise' => $id, 'deleted' => 0));
	if (empty($nodes))
		$nodes = array();
	
	
	
	$count_item_holding_area = 0;
	$count = 0;
	$nodes_graph = array();
	foreach ($nodes as $key => $node) {
		$options = json_decode($node['options'], true);
		$node['options'] = json_decode($node['options'], true);
		
		if (isset($node['options']['type'])) {
			if ($node['options']['type'] == 'module')
				continue;
		}
		else {
			$node['options']['type'] = '';
		}
		
		
		$item = networkmap_db_node_to_js_node(
			$node, $count, $count_item_holding_area);
		
		echo "networkmap.nodes.push(" .
			json_encode($item) . ");\n";
		$nodes_graph[$item['id']] = $item;
	}
	
	//~ $relations = db_get_all_rows_field_filter(
		//~ 'tnetworkmap_ent_rel_nodes',
		//~ 'id_networkmap_enterprise', $id);
	//~ 
	
	$relations = db_get_all_rows_sql("
		SELECT t1.*,
			
			(SELECT t2.id_agent
			FROM tnetworkmap_enterprise_nodes t2
			WHERE t2.id_networkmap_enterprise = " . $id . "
				AND t2.id = t1.parent) AS id_agent_parent,
			
			(SELECT t2.id_agent
			FROM tnetworkmap_enterprise_nodes t2
			WHERE t2.id_networkmap_enterprise = " . $id . "
				AND t2.id = t1.child) AS id_agent_child,
			
			(SELECT t2.id_module
			FROM tnetworkmap_enterprise_nodes t2
			WHERE t2.id_networkmap_enterprise = " . $id . "
				AND t2.id = t1.parent) AS id_module_parent,
			
			(SELECT t2.id_module
			FROM tnetworkmap_enterprise_nodes t2
			WHERE t2.id_networkmap_enterprise = " . $id . "
				AND t2.id = t1.child) AS id_module_child
		
		FROM tnetworkmap_ent_rel_nodes t1
		WHERE t1.id_networkmap_enterprise = " . $id . "
			AND t1.deleted = 0");
	if ($relations === false) $relations = array();
	
	
	
	
	//Clean the relations and transform the module relations into
	//interfaces
	networkmap_clean_relations_for_js($relations);
	
	
	
	
	
	$links_js = networkmap_links_to_js_links(
		$relations, $nodes_graph);
	
	
	
	
	foreach ($links_js as $link_js) {
		if ($link_js['target'] == -1)
			continue;
		if ($link_js['source'] == -1)
			continue;
		
		echo "networkmap.links.push(" . json_encode($link_js) . ");\n";
	}
	
	echo "\n";
	echo "\n";
	
	echo "////////////////////////////////////////////////////////////////////\n";
	echo "// INTERFACE STATUS COLORS\n";
	echo "////////////////////////////////////////////////////////////////////\n";
	
	$module_color_status = array();
	$module_color_status[] = array(
		'status_code' => AGENT_MODULE_STATUS_NORMAL,
		'color' => COL_NORMAL);
	$module_color_status[] = array(
		'status_code' => AGENT_MODULE_STATUS_CRITICAL_BAD,
		'color' => COL_CRITICAL);
	$module_color_status[] = array(
		'status_code' => AGENT_MODULE_STATUS_WARNING,
		'color' => COL_WARNING);
	$module_color_status[] = array(
		'status_code' => AGENT_STATUS_ALERT_FIRED,
		'color' => COL_ALERTFIRED);
	$module_color_status_unknown = COL_UNKNOWN;
	
	echo "var module_color_status = " .
		json_encode($module_color_status) . ";\n";
	echo "var module_color_status_unknown = '" .
		$module_color_status_unknown . "';\n";
	
	echo "\n";
	echo "\n";
	
	echo "////////////////////////////////////////////////////////////////////\n";
	echo "// Other vars\n";
	echo "////////////////////////////////////////////////////////////////////\n";
	
	echo "var translation_none = '" . __('None') . "';\n";
	echo "var dialog_node_edit_title = '" . __('Edit node %s') . "';\n";
	echo "var holding_area_title = '" . __('Holding Area') . "';\n";
	echo "var show_details_menu = '" . __('Show details') . "';\n";
	echo "var edit_menu = '" . __('Edit') . "';\n";
	echo "var set_as_children_menu = '" . __('Set as children') . "';\n";
	echo "var set_parent_menu = '" . __('Set parent') . "';\n";
	echo "var abort_relationship_menu = '" . __('Abort the action of set relationship') . "';\n";
	echo "var delete_menu = '" . __('Delete') . "';\n";
	echo "var add_node_menu = '" . __('Add node') . "';\n";
	echo "var set_center_menu = '" . __('Set center') . "';\n";
	echo "var refresh_menu = '" . __('Refresh') . "';\n";
	echo "var refresh_holding_area_menu = '" . __('Refresh Holding area') . "';\n";
	echo "var abort_relationship_menu = '" . __('Abort the action of set relationship') . "';\n";
	
	echo "\n";
	echo "\n";
}


function networkmap_loadfile($id = 0, $file = '',
	&$relations_param, $graph, $l2_network_interfaces) {
	global $config;
	
	$options = db_get_value('options', 'tnetworkmap_enterprise', 'id',
		$id);
	$options = json_decode($options, true);
	
	$networkmap_nodes = array();
	
	$relations = array();
	
	$other_file = file($file);
	
	//Remove the graph head
	$graph = preg_replace('/^graph .*/', '', $graph);
	//Cut in nodes the graph
	$graph = explode("]", $graph);
	
	$ids = array();
	foreach ($graph as $node) {
		$line = str_replace("\n", " ", $node);
		
		if (preg_match('/([0-9]+) \[.*tooltip.*id_module=([0-9]+)/', $line, $match) != 0) {
			$ids[$match[1]] = array(
				'type' => 'module',
				'id_module' => $match[2]
				);
		}
		else if (preg_match('/([0-9]+) \[.*tooltip.*id_agent=([0-9]+)/', $line, $match) != 0) {
			$ids[$match[1]] = array(
				'type' => 'agent',
				'id_agent' => $match[2]
				);
		}
	}
	
	foreach ($other_file as $key => $line) {
		//clean line a long spaces for one space caracter
		$line = preg_replace('/[ ]+/', ' ', $line);
		
		$data = array();
		
		
		if (preg_match('/^node.*$/', $line) != 0) {
			$items = explode(' ', $line);
			$node_id = $items[1];
			$node_x = $items[2] * 100; //200 is for show more big
			$node_y = $options['height'] - $items[3] * 100; //200 is for show more big
			
			$data['text'] = '';
			$data['image'] = '';
			$data['width'] = 10;
			$data['height'] = 10;
			$data['id_agent'] = 0;
			
			if (preg_match('/<img src=\"([^\"]*)\"/', $line, $match) == 1) {
				$image = $match[1];
				
				$data['shape'] = 'image';
				$data['image'] = $image;
				$size = getimagesize($config['homedir'] . '/' . $image);
				$data['width'] = $size[0];
				$data['height'] = $size[1];
				
				$data['id_agent'] = 0;
				$data['id_module'] = 0;
				$data['type'] = '';
				if (preg_match('/Pandora FMS/', $line) != 0) {
					$data['text'] = 'Pandora FMS';
					$data['id_agent'] = -1;
				}
				else {
					$data['type'] = $ids[$node_id]['type'];
					switch ($ids[$node_id]['type']) {
						case 'module':
							$data['id_module'] = $ids[$node_id]['id_module'];
							$data['id_agent'] =
								modules_get_agentmodule_agent($ids[$node_id]['id_module']);
							
							$text = modules_get_agentmodule_name($data['id_module']);
							$text = io_safe_output($text);
							$text = ui_print_truncate_text($text,
								'agent_medium', false, true, false,
								'...', false);
							$data['text'] = $text;
							break;
						case 'agent':
							$data['id_agent'] = $ids[$node_id]['id_agent'];
							
							$text = agents_get_name($ids[$node_id]['id_agent']);
							$text = io_safe_output($text);
							$text = ui_print_truncate_text($text,
								'agent_medium', false, true, false,
								'...', false);
							$data['text'] = $text;
							break;
					}
				}
			}
			else {
				$data['shape'] = 'wtf';
			}
			
			$data['coords'] = array($node_x, $node_y);
			$data['parent'] = -1;
			
			
			if (strpos($node_id, "transp_") !== false) {
				//removed the transparent nodes
			}
			else {
				$networkmap_nodes[$node_id] = $data;
			}
		}
		else if (preg_match('/^edge.*$/', $line) != 0) {
			$items = explode(' ', $line);
			$line_orig = $items[2];
			$line_dest = $items[1];
			
			//$relations[$line_dest] = $line_orig;
			$relations[] = array('orig' => $line_orig, 'dest' => $line_dest);
		}
	}
	
	
	$relations_param = array();
	
	foreach ($relations as $rel) {
		if (strpos($rel['orig'], "transp_") !== false) {
			//removed the transparent nodes
			continue;
		}
		if (strpos($rel['dest'], "transp_") !== false) {
			//removed the transparent nodes
			continue;
		}
		
		$row = array(
			'child' => $rel['orig'],
			'child_type' => $networkmap_nodes[$rel['orig']]['type'],
			'parent' => $rel['dest'],
			'parent_type' => $networkmap_nodes[$rel['dest']]['type']);
		$relations_param[] = $row;
	}
	
	return $networkmap_nodes;
}

function update_node($node) {
	$return = array();
	$return['correct'] = true;
	$return['state'] = "";
	
	$values = array();
	$values['x'] = $node['x'];
	$values['y'] = $node['y'];
	
	if ($node['state'] == 'holding_area') {
		$return['state'] = "holding_area";
		
		$networkmap_node = db_get_row_filter('tnetworkmap_enterprise_nodes',
			array('id' => $node['id_db']));
		$networkmap = db_get_row_filter('tnetworkmap_enterprise',
			array('id' => $networkmap_node['id_networkmap_enterprise']));
		$networkmap['options'] = json_decode($networkmap['options'], true);
		//Hardcoded
		$networkmap['options']['holding_area'] = array(500, 500);
		
		$holding_area_x = $networkmap['options']['width'] +
			30 + 40 * 2 - $networkmap['options']['holding_area'][0];
		$holding_area_y = $networkmap['options']['height'] +
			30 + 40 * 2 - $networkmap['options']['holding_area'][1];
		
		if (($holding_area_x  > $node['x']) ||
			($holding_area_y > $node['y'])) {
			
			//The user move the node out the holding area
			db_process_sql_update('tnetworkmap_enterprise_nodes',
				array('state' => ""),
				array('id' => $node['id_db']));
			
			$return['state'] = "";
		}
	}
	
	db_process_sql_update('tnetworkmap_enterprise_nodes', $values,
		array('id' => $node['id_db']));
	
	return $return;
}

function erase_node($id) {
	$node = db_get_row('tnetworkmap_enterprise_nodes', 'id', $id['id']);
	
	
	//For networkmaps of Level 2
	$nodes = db_get_all_rows_filter('tnetworkmap_enterprise_nodes',
		array(
			'id_networkmap_enterprise' => $node['id_networkmap_enterprise'],
			'id_agent' => $node['id_agent'],
			'id' => "<>" . $id['id']
			));
	
	foreach ($nodes as $node) {
		db_process_sql_update('tnetworkmap_enterprise_nodes',
			array('deleted' => 1), array('id' => $node['id']));
		
		db_process_sql_update('tnetworkmap_ent_rel_nodes',
			array('deleted' => 1), array('parent' => $node['id']));
		db_process_sql_update('tnetworkmap_ent_rel_nodes',
			array('deleted' => 1), array('child' => $node['id']));
	}
	
	
	db_process_sql_update('tnetworkmap_ent_rel_nodes',
		array('deleted' => 1), array('parent' => $id));
	db_process_sql_update('tnetworkmap_ent_rel_nodes',
		array('deleted' => 1), array('child' => $id));
	
	return (bool)db_process_sql_update('tnetworkmap_enterprise_nodes',
		array('deleted' => 1), array('id' => $id));
}

function networkmap_delete_nodes_by_agent($id_agent) {
	$rows = db_get_all_rows_filter('tnetworkmap_enterprise_nodes',
		array('id_agent' => $id_agent));
	if (empty($rows))
		$rows = array();
	
	foreach ($rows as $row) {
		db_process_sql_delete('tnetworkmap_ent_rel_nodes',
			array('parent' => $row['id']));
		db_process_sql_delete('tnetworkmap_ent_rel_nodes',
			array('child' => $row['id']));
	}
	
	db_process_sql_delete('tnetworkmap_enterprise_nodes',
		array('id_agent' => $id_agent));
}

function get_status_color_networkmap_fictional_point($id_networkmap, $parent = null) {
	$last_status = 0;
	
	if ($id_networkmap != 0) {
		$agents = db_get_all_rows_filter('tnetworkmap_enterprise_nodes',
			array('id_networkmap_enterprise' => $id_networkmap));
		if ($agents == false)
			$agents = array();
		
		$exit = false;
		foreach ($agents as $agent) {
			if ($agent['id_agent'] == -1) continue;
			if ($agent['id_agent'] == -2) {
				if (empty($parent)) {
					$option = json_decode($agent, true);
					if ($option['networkmap'] == 0) {
						$status = 0;
					}
					else {
						$status = get_status_color_networkmap($option['networkmap'], true);
					}
				}
				else {
					//TODO Calculate next levels.
					$status = 0;
				}
			}
			else {
				$status = get_status_color_networkmap($agent['id_agent'], false);
			}
			
			switch($status) {
				case 0: 
					// Normal monitor
					break;
				case 1:
					// Critical monitor
					$last_status = 1;
					$exit = true;
					break;
				case 2:
					// Warning monitor
					$last_status = 2;
					break;
				case 4:
					if ($last_status != 2) {
						$last_status = 4;
					}
					break;
				default:
					// Unknown monitor
					if (($last_status != 2) && ($last_status != 4)) {
						$last_status = $status;
					}
					break;
			}
			
			if ($exit) break;
		}
	}
	
	if (empty($parent)) {
		switch($last_status) {
			case 0: 
				$status_color = COL_NORMAL; // Normal monitor
				break;
			case 1:
				$status_color = COL_CRITICAL; // Critical monitor
				break;
			case 2:
				$status_color = COL_WARNING; // Warning monitor
				break;
			case 4:
				$status_color = COL_ALERTFIRED; // Alert fired
				break;
			default:
				$status_color = COL_UNKNOWN; // Unknown monitor
				break;
		}
		
		return $status_color;
	}
	else  {
		return $last_status;
	}
}

function get_status_color_networkmap($id, $color = true) {
	$status = agents_get_status($id);
	
	if (!$color) {
		return $status;
	}
	
	// Set node status
	switch($status) {
		case 0: 
			$status_color = COL_NORMAL; // Normal monitor
			break;
		case 1:
			$status_color = COL_CRITICAL; // Critical monitor
			break;
		case 2:
			$status_color = COL_WARNING; // Warning monitor
			break;
		case 4:
			$status_color = COL_ALERTFIRED; // Alert fired
			break;
		default:
			$status_color = COL_UNKNOWN; // Unknown monitor
			break;
	}
	
	return $status_color;
}

function add_agent_networkmap($id, $agent_name_param, $x, $y,
	$id_agent_param = false, $other_values = array()) {
	
	global $config;
	
	if ($id_agent_param !== false) {
		$agent_name = io_safe_output(agents_get_name($id_agent_param));
		
		$id_agent = $id_agent_param;
	}
	else {
		$id_agent = agents_get_agent_id($agent_name_param);
		$agent_name = io_safe_output($agent_name_param);
	}
	
	if ($id_agent == false)
		return false;
	
	$agent = db_get_row('tagente', 'id_agente', $id_agent);
	
	$img_node = ui_print_os_icon ($agent['id_os'], false, true, true,
		true, true, true);
	$img_node_dir = str_replace($config['homeurl'], $config['homedir'],
		$img_node);
	
	$size = getimagesize($img_node_dir);
	$width = $size[0];
	$height = $size[1];
	
	$data = array();
	$data['id_networkmap_enterprise'] = $id;
	$data['x'] = $x;
	$data['y'] = $y;
	$data['id_agent'] = $id_agent;
	$data['parent'] = 0;
	$options = array();
	$options['shape'] = 'circle';
	$options['image'] = $img_node;
	$options['width'] = $width;
	$options['height'] = $height;
	$options['type'] = 'agent';
	//WORK AROUND FOR THE JSON ENCODE WITH FOR EXAMPLE Ñ OR Á
	$options['text'] = 'json_encode_crash_with_ut8_chars';
	
	if (isset($other_values['state'])) {
		$data['state'] = $other_values['state'];
	}
	
	if (isset($other_values['text'])) {
		$agent_name = $other_values['text'];
	}
	
	if (isset($other_values['id_module'])) {
		$data['id_module'] = $other_values['id_module'];
		$options['shape'] = 'arrowhead';
	}
	
	if (isset($other_values['type'])) {
		$options['type'] = $other_values['type'];
	}
	
	$data['options'] = json_encode($options);
	$data['options'] = str_replace('json_encode_crash_with_ut8_chars',
		$agent_name, $data['options']);
	
	return db_process_sql_insert('tnetworkmap_enterprise_nodes', $data);
}

function show_node_info($id_node, $refresh_state, $user_readonly) {
	global $config;
	
	echo "<script type='text/javascript' src='/include/javascript/functions_pandora_networkmap.js'></script>";
	
	$row = db_get_row('tnetworkmap_enterprise_nodes', 'id', $id_node);
	
	$options = json_decode($row['options'], true);
	
	if ($row['id_agent'] == -2) {
		//Show the dialog to edit the fictional point.
		if ($user_readonly) {
			require ($config["homedir"]."/general/noaccess.php");
			return;
		}
		else {
			$networkmaps = get_networkmaps(
				$row['id_networkmap_enterprise']);
				
			$selectNetworkmaps = html_print_select($networkmaps,
				'networmaps_enterprise', $options['networkmap'], '', '', 0, true);
			
			$shapes = array(
				'circle' => __('Circle'),
				'square' => __('Square'),
				'rhombus' => __('Rhombus'));
			
			$mini_form_fictional_point = "<table cellpadding='2'>
				<tr>" .
					"<td>" . __('Name') ."<td>". html_print_input_text('fictional_name', $options['text'], '', 25, 255, true) . 
				'<td>' .__('Shape') . "<td>". html_print_select($shapes, 'fictional_shape', 0, '', '', 0, true) . "</td></tr><tr><td>".
				__('Radius') . "<td>". '<input type="text" size="3" maxlength="3" value="' . ($options['width'] / 2) . '" id="fictional_radious" />' . "<td>" .
				__('Color') . "<td>" .
				'<input type="text" size="7" value="' . $options['color'] . '" id="fictional_color" class="fictional_color"/> <tr />' 
				."<tr><td>".__("Network map linked"). "<td>".$selectNetworkmaps.
				"<td align=right>". html_print_button(__('Update'), 'update_fictional', false, 'update_fictional_node_popup(' . $id_node . ');', 'class="sub next"', true) . "</tr></table>";
			
			echo $mini_form_fictional_point;
			
			
			echo '
				<script type="text/javascript">
					$(document).ready(function () {
						$(".fictional_color").attachColorPicker();
					});
				</script>';
			
			return;
		}
	} 
	else {
		//Show the view of node.
		$url_agent = ui_get_full_url(false);
		$url_agent .= 'index.php?' .
			'sec=estado&' .
			'sec2=operation/agentes/ver_agente&' .
			'id_agente=' . $row['id_agent'];
		
		$modules = agents_get_modules($row['id_agent'],
			array('nombre', 'id_tipo_modulo'), array('disabled' => 0),
			true, false);
		if ($modules == false) {
			$modules = array();
		}
		$count_module = count($modules);
		
		$snmp_modules = agents_get_modules($row['id_agent'],
			array('nombre', 'id_tipo_modulo'),
			array('id_tipo_modulo' => 18, 'disabled' => 0), true, false);
		$count_snmp_modules = count($snmp_modules);
		
		echo "<script type='text/javascript'>";
		echo "var node_info_height = 0;";
		echo "var node_info_width = 0;";
		echo "var count_snmp_modules = " . $count_snmp_modules . ";";
		echo "var module_count = " . $count_module . ";";
		echo "var modules = [];";
		foreach ($modules as $id_agent_module => $module) {
			$text = io_safe_output($module['nombre']);
			$sort_text = ui_print_truncate_text($text, 'module_small', false, true, false, '...');
			//$text = $sort_text;
			
			$color_status = get_status_color_module_networkmap($id_agent_module);
			
			echo "modules[" . $id_agent_module . "] = {
					'pos_x': null,
					'pos_y': null ,
					'text': '" . $text . "',
					'short_text': '" . $sort_text . "',
					'type': " . $module['id_tipo_modulo'] . ",
					'status_color': '" . $color_status . "'
					};";
		}
		
		echo "var color_status_node = '" . get_status_color_networkmap($row['id_agent']) . "';";
		echo "</script>";
		
		$mode_show = get_parameter('mode_show', 'all');
		echo "<script type='text/javascript'>
			var mode_show = '$mode_show';
		</script>";
		
		echo '<div style="text-align: center;">';
		echo '<b><a target="_blank" style="text-decoration: none;" href="' . $url_agent . '">' . agents_get_name($row['id_agent']) . '</a></b><br />';
		$modes_show = array('status_module' => 'Only status', 'all' => 'All');
		echo __('Show modules:');
		html_print_select($modes_show, 'modes_show', $mode_show, 'refresh_window();');
		echo " ";
		html_print_button('Refresh', 'refresh_button', false, 'refresh_window();',
			'style="padding-left: 10px; padding-right: 10px;"');
		echo '</div>';
		echo '<div id="content_node_info" style="width: 100%; height: 90%;
			overflow: auto; text-align: center;">
			<canvas id="node_info" style="background: #fff;">
					Use a browser that support HTML5.
			</canvas>';
		
		echo '
			<script type="text/javascript">
				function refresh_window() {
					url = location.href
					
					mode = $("#modes_show option:selected").val();
					
					url = url + "&mode_show=" + mode;
					
					window.location.replace(url);
				}
				
				$(document).ready(function () {
					node_info_height = $("#content_node_info").height();
					node_info_width = $("#content_node_info").width();
					
					//Set the first size for the canvas
					//$("#node_info").attr("height", $(window).height());
					//$("#node_info").attr("width", $(window).width());
					show_networkmap_node(' . $row['id_agent'] . ', ' . $refresh_state . ');
				});
			</script>
			</div>
		';
		echo "<div id='tooltip' style='border: 1px solid black; background: white; position: absolute; display:none;'></div>";
	}
}

// Returns a module node definition
function get_status_color_module_networkmap($id_agente_modulo) {
	$status = modules_get_agentmodule_status($id_agente_modulo);
	
	// Set node status
	switch($status) {
		case 0:
		//At the moment the networkmap enterprise does not show the
		//alerts.
		case AGENT_MODULE_STATUS_NORMAL_ALERT:
			$status_color = COL_NORMAL; // Normal monitor
			break;
		case 1:
			$status_color = COL_CRITICAL; // Critical monitor
			break;
		case 2:
			$status_color = COL_WARNING; // Warning monitor
			break;
		case 4:
			$status_color = COL_ALERTFIRED; // Alert fired
			break;
		default:
			$status_color = COL_UNKNOWN; // Unknown monitor
			break;
	}
	
	return $status_color;
}

function duplicate_networkmap($id) {
	$return = true;
	
	$values = db_get_row('tnetworkmap_enterprise', 'id', $id);
	unset($values['id']);
	$free_name = false;
	$values['name'] = io_safe_input(__('Copy of ') . io_safe_output($values['name']));
	$count = 1;
	while (!$free_name) {
		$exist = db_get_row_filter('tnetworkmap_enterprise', array('name' => $values['name']));
		if ($exist === false) {
			$free_name = true;
		}
		else {
			$values['name'] = $values['name'] . io_safe_input(' ' . $count);
		}
	}
	
	$correct_or_id = db_process_sql_insert('tnetworkmap_enterprise', $values);
	if ($correct_or_id === false) {
		$return = false;
	}
	else {
		$new_id = $correct_or_id;
		
		$relations = array();
		
		$nodes = db_get_all_rows_filter('tnetworkmap_enterprise_nodes',
			array('id_networkmap_enterprise' => $id));
		if ($nodes === false) $nodes = array();
		
		$relations = db_get_all_rows_filter('tnetworkmap_ent_rel_nodes',
			array('id_networkmap_enterprise' => $id));
		if ($relations === false) $relations = array();
		
		foreach ($nodes as $node) {
			$values = $node;
			$old_id_node = $values['id'];
			unset($values['id']);
			$values['id_networkmap_enterprise'] = $new_id;
			$result_or_id = db_process_sql_insert('tnetworkmap_enterprise_nodes', $values);
			
			if ($result_or_id === false) {
				$return = false;
				break;
			}
			else {
				//Translate the node relations.
				$new_id_node = $result_or_id;
				$old_id_node;
				
				foreach ($relations as $key => $relation) {
					if (isset($relation['parent'])) {
						if ($relation['parent'] == $old_id_node)
						{
							unset($relations[$key]['parent']);
							$relations[$key]['parent_new'] = $new_id_node;
						}
					}
					
					if (isset($relation['child'])) {
						if ($relation['child'] == $old_id_node)
						{
							unset($relations[$key]['child']);
							$relations[$key]['child_new'] = $new_id_node;
						}
					}
				}	
			}
		}
		
		//Insert the new relations.
		if ($return) {
			foreach ($relations as $relation) {
				$values = array();
				$values['id_networkmap_enterprise'] = $new_id;
				$values['parent'] = $relation['parent_new'];
				$values['child'] = $relation['child_new'];
				
				$result = db_process_sql_insert('tnetworkmap_ent_rel_nodes', $values);
				
				if ($result === false) {
					$return = false;
					break;
				}
			}
		}
	}
	
	if ($return) {
		return true;
	}
	else {
		//Clean DB.
		db_process_sql_delete('tnetworkmap_ent_rel_nodes', array('id_networkmap_enterprise' => $new_id));
		db_process_sql_delete('tnetworkmap_enterprise_nodes', array('id_networkmap_enterprise' => $new_id));
		db_process_sql_delete('tnetworkmap_enterprise', array('id' => $new_id));
		
		return false;
	}
}


function networkmap_clean_duplicate_links($id) {
	global $config;
	
	//Clean (for migrations of older Pandoras)
	// - duplicated links
	// - duplicate links
	//          (parent) node 1 - (child) node 2
	//          (parent) node 2 - (child) node 1
	//          
	//          and erase the last, only the first row alive
	
	$sql_duplicate_links = "SELECT id, parent, child
		FROM tnetworkmap_ent_rel_nodes t1
		WHERE t1.child IN (
				SELECT t2.child
				FROM tnetworkmap_ent_rel_nodes t2
				WHERE t1.id != t2.id
					AND t1.child = t2.child
					AND t1.parent = t2.parent
					AND t2.id_networkmap_enterprise = " . $id . ")
			AND t1.id_networkmap_enterprise = " . $id . "
		ORDER BY parent, child";
	
	$rows = db_get_all_rows_sql($sql_duplicate_links);
	if (empty($rows))
		$rows = array();
	
	$pre_parent = -1;
	$pre_child = -1;
	foreach ($rows as $row) {
		if (($pre_parent == $row['parent']) &&
			($pre_child == $row['child'])) {
			
			//Delete the duplicate row
			
			db_process_sql_delete('tnetworkmap_ent_rel_nodes',
				array('id' => $row['id']));
			
		}
		else {
			$pre_parent = $row['parent'];
			$pre_child = $row['child'];
		}
	}
	
	db_process_sql($sql_duplicate_links);
	
	
	
	do {
		db_clean_cache();
		
		$sql_duplicate_links_parent_as_children = "
			SELECT id, parent, child
			FROM tnetworkmap_ent_rel_nodes t1
			WHERE t1.child IN (
				SELECT t2.parent
				FROM tnetworkmap_ent_rel_nodes t2
				WHERE t1.parent = t2.child
					AND t1.child = t2.parent
					AND t2.id_networkmap_enterprise = " . $id . ")
				AND t1.id_networkmap_enterprise = " . $id . "
			ORDER BY parent, child";
		$rows = db_get_all_rows_sql($sql_duplicate_links_parent_as_children);
		
		if (empty($rows))
			$rows = array();
		
		$found = false;
		
		foreach ($rows as $row) {
			foreach ($rows as $row2) {
				if (($row['id'] != $row2['id'])
					&& ($row['child'] == $row2['parent'])
					&& ($row['parent'] == $row2['child'])
					) {
					
					db_process_sql_delete('tnetworkmap_ent_rel_nodes',
						array('id' => $row2['id']));
					
					$found = true;
					break;
					
				}
			}
			
			if ($found)
				break;
		}
	}
	while ($found);
}


function show_networkmap($id = 0, $user_readonly = false) {
	global $config;
	
	//Clean (for migrations of older Pandoras)
	// - duplicated links
	// - duplicate links
	//          (parent) node 1 - (child) node 2
	//          (parent) node 2 - (child) node 1
	//          
	//          and erase the last, only the first row alive
	networkmap_clean_duplicate_links($id);
	
	$networkmap = db_get_row(
		'tnetworkmap_enterprise',
		'id', $id);
	$networkmap['options'] = json_decode($networkmap['options'], true);
	
	if (!isset($networkmap['options']['l2_network_interfaces']))
		$networkmap['options']['l2_network_interfaces'] = 1;
	
	echo '<script type="text/javascript" src="' . $config['homeurl'] . 'include/javascript/d3.3.5.14.js" charset="utf-8"></script>';
	ui_require_css_file("jquery.contextMenu", 'include/javascript/');
	echo '<script type="text/javascript" src="' . $config['homeurl'] . 'include/javascript/jquery.contextMenu.js"></script>';
	echo '<script type="text/javascript" src="' . $config['homeurl'] . 'include/javascript/functions_pandora_networkmap.js"></script>';
	echo '<div id="networkconsole" style="position: relative; overflow: hidden; background: #FAFAFA">';
		
		echo '<canvas id="minimap"
			style="position: absolute; left: 0px; top: 0px; border: 1px solid #3a4a70;">
			</canvas>';
		
		echo '<div id="arrow_minimap" style="position: absolute; left: 0px; top: 0px;">
				<a title="' . __('Open Minimap') . '" href="javascript: toggle_minimap();">
					<img id="image_arrow_minimap" src="images/minimap_open_arrow.png" />
				</a>
			</div>';
		
	echo '</div>';
	
	?>
<style type="text/css">
	.node {
		stroke: #fff;
		stroke-width: 1px;
	}
	
	.node_over {
		stroke: #999;
	}
	
	.node_selected {
		stroke:#000096;
		stroke-width:3;
	}
	
	.node_children {
		stroke: #00f;
	}
	
	.link {
		stroke: #999;
		stroke-opacity: .6;
	}
	
	.link_over {
		stroke: #000;
		stroke-opacity: .6;
	}
	
	.holding_area {
		stroke: #0f0;
		stroke-dasharray: 12,3;
	}
	
	.holding_area_link {
		stroke-dasharray: 12,3;
	}
</style>

<script type="text/javascript">
	<?php
	networkmap_write_js_array($id);
	
	?>
	////////////////////////////////////////////////////////////////////////
	// document ready
	////////////////////////////////////////////////////////////////////////
	$(document).ready(function() {
		init_graph({
			refesh_period: networkmap_refresh_time,
			graph: networkmap,
			networkmap_center: networkmap_center,
			url_popup: url_popup_pandora,
			networkmap_dimensions: networkmap_dimensions,
			holding_area_dimensions: networkmap_holding_area_dimensions,
			url_background_grid: url_background_grid
		});
		init_drag_and_drop();
		init_minimap();
		function_open_minimap();
		
		window.interval_obj =
			setInterval(update_networkmap, networkmap_refresh_time);
		
		$(document.body).on("mouseleave",
			".context-menu-list",
			function(e) {
				try {
					$("#networkconsole").contextMenu("hide");
				}
				catch(err) {
				}
			}
		);
	});
</script>
<?php
$list_networkmaps = get_networkmaps($id);
if (empty($list_networkmaps))
	$list_networkmaps = array();
?>

<div id="dialog_node_edit" style="display: none;" title="<?php echo __('Edit node');?>">
	<div style="text-align: left; width: 100%;">
	<?php
	
	$table = null;
	$table->id = 'node_options';
	$table->width = "100%";
	
	$table->data = array();
	$table->data[0][0] = __('Shape');
	$table->data[0][1] = html_print_select(array(
		'circle' => __('Circle'),
		'square' => __('Square'),
		'rhombus' => __('Rhombus')), 'shape', '',
		'javascript:', '', 0, true) . '&nbsp;' .
		'<span id="shape_icon_in_progress" style="display: none;">' . 
			html_print_image('images/spinner.gif', true) . '</span>' .
		'<span id="shape_icon_correct" style="display: none;">' .
			html_print_image('images/dot_green.png', true) . '</span>' .
		'<span id="shape_icon_fail" style="display: none;">' .
			html_print_image('images/dot_red.png', true) . '</span>';
	$table->data["fictional_node_name"][0] = __('Name');
	$table->data["fictional_node_name"][1] = html_print_input_text('edit_name_fictional_node',
		'', __('name fictional node'), '20', '50', true);
	$table->data["fictional_node_networkmap_link"][0] = __('Networkmap to link');
	$table->data["fictional_node_networkmap_link"][1] =
		html_print_select($list_networkmaps, 'edit_networkmap_to_link',
			'', '', '', 0, true);
	$table->data["fictional_node_update_button"][0] = '';
	$table->data["fictional_node_update_button"][1] =
		html_print_button(__('Update fictional node'), '', false,
			'add_fictional_node();', 'class="sub"', true);
	
	ui_toggle(html_print_table($table, true), __('Node options'),
		__('Node options'), true);
	
	
	
	$table = null;
	$table->id = 'relations_table';
	$table->width = "100%";
	
	$table->head = array();
	$table->head['node_source'] = __('Node source');
	if ($networkmap['options']['l2_network_interfaces']) {
		$table->head['interface_source'] = __('Interface source');
		$table->head['interface_target'] = __('Interface Target');
	}
	$table->head['node_target'] = __('Node target');
	$table->head['edit'] = '<span title="' . __('Edit') . '">' . __('E.') . '</span>';
	
	
	$table->data = array();
	$table->rowstyle['template_row'] = 'display: none;';
	$table->data['template_row']['node_source'] = '';
	if ($networkmap['options']['l2_network_interfaces']) {
		$table->data['template_row']['interface_source'] = 
			html_print_select(array(), 'interface_source', '', '',
				__('None'), 0, true);
		$table->data['template_row']['interface_target'] =
			html_print_select(array(), 'interface_target', '', '',
				__('None'), 0, true);
	}
	$table->data['template_row']['node_target'] = '';
	$table->data['template_row']['edit'] = "";
	
	$table->data['template_row']['edit'] = '';
	
	if ($networkmap['options']['l2_network_interfaces']) {
		$table->data['template_row']['edit'] .=
			'<span class="edit_icon_correct" style="display: none;">' . 
				html_print_image('images/dot_green.png', true) . '</span>' .
			'<span class="edit_icon_fail" style="display: none;">' . 
				html_print_image('images/dot_red.png', true) . '</span>' .
			'<span class="edit_icon_progress" style="display: none;">' . 
				html_print_image('images/spinner.gif', true) . '</span>' .
			'<span class="edit_icon"><a class="edit_icon_link" title="' . __('Update') . '" href="#">' .
			html_print_image('images/config.png', true) . '</a></span>';
	}
	
	$table->data['template_row']['edit'] .=
		'<a class="delete_icon" href="#">' .
		html_print_image('images/delete.png', true) . '</a>';
	
	$table->colspan['no_relations']['0'] = 5;
	$table->cellstyle['no_relations']['0'] = 'text-align: center;';
	$table->data['no_relations']['0'] = __('There are not relations');
	
	$table->colspan['loading']['0'] = 5;
	$table->cellstyle['loading']['0'] = 'text-align: center;';
	$table->data['loading']['0'] = html_print_image(
		'images/wait.gif', true);
	
	
	ui_toggle(html_print_table($table, true), __('Relations'),
		__('Relations'), false);
	?>
	</div>
</div>

<div id="dialog_node_add" style="display: none;" title="<?php echo __('Add node');?>">
	<div style="text-align: left; width: 100%;">
		<?php
		$table = null;
		$table->width = "100%";
		$table->data = array();
		
		$table->data[0][0] = __('Agent');
		$params = array();
		$params['return'] = true;
		$params['show_helptip'] = true;
		$params['input_name'] = 'agent_name';
		$params['input_id'] = 'agent_name';
		$params['print_hidden_input_idagent'] = true;
		$params['hidden_input_idagent_name'] = 'id_agent';
		$params['disabled_javascript_on_blur_function'] = true;
		$table->data[0][1] = ui_print_agent_autocomplete_input($params);
		$table->data[1][0] = '';
		$table->data[1][1] =
			html_print_button(__('Add agent node'), '', false,
				'add_agent_node();', 'class="sub"', true);
		
		
		$add_agent_node_html = html_print_table($table, true);
		ui_toggle($add_agent_node_html, __('Add agent node'),
			__('Add agent node'), false);
		
		
		
		
		$table = null;
		$table->width = "100%";
		$table->data = array();
		$table->data[0][0] = __('Group');
		$table->data[0][1] = html_print_select_groups(false, "IW",
			false,
			'group_for_show_agents',
			-1,
			'choose_group_for_show_agents()',
			__('None'),
			-1,
			true);
		$table->data[1][0] = __('Agents');
		$table->data[1][1] = html_print_select(
			array(-1 => __('None')), 'agents_filter_group', -1, '', '',
			0, true, true, true, '', false, "width: 170px;", false, 5);
		$table->data[2][0] = '';
		$table->data[2][1] =
			html_print_button(__('Add agent node'), '', false,
				'add_agent_node_from_the_filter_group();', 'class="sub"', true);
		
		$add_agent_node_html = html_print_table($table, true);
		ui_toggle($add_agent_node_html, __('Add agent node (filter by group)'),
			__('Add agent node'), true);
		
		
		
		
		$table = null;
		$table->width = "100%";
		$table->data = array();
		$table->data[0][0] = __('Name');
		$table->data[0][1] = html_print_input_text('name_fictional_node',
			'', __('name fictional node'), '20', '50', true);
		$table->data[1][0] = __('Networkmap to link');
		$table->data[1][1] =
			html_print_select($list_networkmaps, 'networkmap_to_link',
				'', '', '', 0, true);
		$table->data[2][0] = '';
		$table->data[2][1] =
			html_print_button(__('Add fictional node'), '', false,
				'add_fictional_node();', 'class="sub"', true);
		$add_agent_node_html = html_print_table($table, true);
		ui_toggle($add_agent_node_html, __('Add fictional point'),
			__('Add agent node'), true);
		?>
	</div>
</div>
	<?php
}

function networkmap_update_link($networkmap_id, $id_link, $interface_source, $interface_target) {
	$link = db_get_row_filter('tnetworkmap_ent_rel_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => $id_link));
	
	$id_link_change = 0;
	
	
	//Child
	if (($link['child_type'] == 'module') && ($interface_source === 0)) {
		//Delete the relation and node and connect directly with the agent node
		
		$node_to_delete = db_get_row_filter('tnetworkmap_enterprise_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => $link['child']));
		
		$other_relation = 'child';
		$link_to_delete = db_get_row_filter(
			'tnetworkmap_ent_rel_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => '<>' . $id_link,
				'parent' => $link['child']));
		if (empty($link_to_delete)) {
			$other_relation = 'parent';
			$link_to_delete = db_get_row_filter(
			'tnetworkmap_ent_rel_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => '<>' . $id_link,
				'child' => $link['child']));
		}
		
		
		db_process_sql_delete('tnetworkmap_enterprise_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => $node_to_delete['id']));
		
		db_process_sql_delete('tnetworkmap_ent_rel_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => $link_to_delete['id']));
		
		$link['child'] = $link_to_delete[$other_relation];
		$link["child_type"] = 'agent';
		$result = db_process_sql_update('tnetworkmap_ent_rel_nodes',
				$link,
				array('id' => $link['id']));
	}
	elseif ($link['child_type'] == 'module') {
		//Change the id_agent_module in the node and name
		$node = db_get_row_filter('tnetworkmap_enterprise_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => $link['child']));
		
		if ($node['id_module'] == $interface_source) {
			$result = true;
		}
		else {
			$node['options'] = json_decode($node['options'], true);
			$node['options']['text'] = modules_get_agentmodule_name(
				$interface_source);
			$node['options'] = json_encode($node['options']);
			$node['id_module'] = $interface_source;
			
			$result = db_process_sql_update('tnetworkmap_enterprise_nodes',
				$node,
				array('id' => $link['child']));
		}
	}
	elseif ($interface_source > 0) {
		//Create a new relation with the relation and node
		
		$new_node = array();
		$new_node['id_networkmap_enterprise'] = $networkmap_id;
		$new_node['x'] = 666;
		$new_node['y'] = 666;
		$new_node['z'] = 0;
		$new_node['id_agent'] = modules_get_agentmodule_agent($interface_source);
		$new_node['id_agent_module'] = 0;
		$new_node['parent'] = 0;
		$new_node['options'] = array();
		$new_node['options']['type'] = "module";
		$new_node['options']['shape'] = "arrowhead";
		$new_node['options']['image'] = "images/mod_snmp_proc.png";
		$new_node['options']['width'] = 50;
		$new_node['options']['height'] = 16;
		$new_node['options']['text'] = modules_get_agentmodule_name($interface_source);
		$new_node['options'] = json_encode($new_node['options']);
		$new_node['id_module'] = $interface_source;
		$new_node['id'] = db_process_sql_insert('tnetworkmap_enterprise_nodes',
			$new_node);
		
		if ($new_node['id'] === false)
			return false;
		
		$new_link = array();
		$new_link['id_networkmap_enterprise'] = $networkmap_id;
		$new_link['parent'] = $link['child'];
		$new_link['child'] = $new_node['id'];
		$new_link['parent_type'] = 'agent';
		$new_link['child_type'] = 'module';
		$new_link['id'] = db_process_sql_insert(
			'tnetworkmap_ent_rel_nodes',
			$new_link);
		
		$id_link_change = $new_link['id'];
		
		if ($new_link['id'] === false)
			return false;
		
		$link['child'] = $new_node['id'];
		$link['child_type'] = 'module';
		
		$result = db_process_sql_update('tnetworkmap_ent_rel_nodes',
			$link,
			array('id' => $link['id']));
	}
	
	
	if (!$result)
		return false;
	
	
	//Parent
	if (($link['parent_type'] == 'module') && ($interface_target === 0)) {
		//Delete the relation and node and connect directly with the agent node
		
		$node_to_delete = db_get_row_filter('tnetworkmap_enterprise_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => $link['parent']));
		
		$other_relation = 'child';
		$link_to_delete = db_get_row_filter(
			'tnetworkmap_ent_rel_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => '<>' . $id_link,
				'parent' => $link['parent']));
		if (empty($link_to_delete)) {
			$other_relation = 'parent';
			$link_to_delete = db_get_row_filter(
			'tnetworkmap_ent_rel_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => '<>' . $id_link,
				'child' => $link['parent']));
		}
		
		
		db_process_sql_delete('tnetworkmap_enterprise_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => $node_to_delete['id']));
		
		db_process_sql_delete('tnetworkmap_ent_rel_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => $link_to_delete['id']));
		
		$link['parent'] = $link_to_delete[$other_relation];
		$link["parent_type"] = 'agent';
		$result = db_process_sql_update('tnetworkmap_ent_rel_nodes',
				$link,
				array('id' => $link['id']));
	}
	elseif ($link['parent_type'] == 'module') {
		//Change the id_agent_module in the node
		$node = db_get_row_filter('tnetworkmap_enterprise_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => $link['parent']));
		
		if ($node['id_module'] == $interface_target) {
			$result = true;
		}
		else {
			$result = db_process_sql_update('tnetworkmap_enterprise_nodes',
				array('id_module' => $interface_target),
				array('id' => $link['parent']));
		}
	}
	elseif ($interface_target > 0) {
		//Create a new relation with the interface and node
		
		$new_node = array();
		$new_node['id_networkmap_enterprise'] = $networkmap_id;
		$new_node['x'] = 666;
		$new_node['y'] = 666;
		$new_node['z'] = 0;
		$new_node['id_agent'] = modules_get_agentmodule_agent($interface_target);
		$new_node['id_agent_module'] = 0;
		$new_node['parent'] = 0;
		$new_node['options'] = array();
		$new_node['options']['type'] = "module";
		$new_node['options']['shape'] = "arrowhead";
		$new_node['options']['image'] = "images/mod_snmp_proc.png";
		$new_node['options']['width'] = 50;
		$new_node['options']['height'] = 16;
		$new_node['options']['text'] = modules_get_agentmodule_name($interface_target);
		$new_node['options'] = json_encode($new_node['options']);
		$new_node['id_module'] = $interface_target;
		$new_node['id'] = db_process_sql_insert('tnetworkmap_enterprise_nodes',
			$new_node);
		
		if ($new_node['id'] === false)
			return false;
		
		$new_link = array();
		$new_link['id_networkmap_enterprise'] = $networkmap_id;
		$new_link['parent'] = $new_node['id'];
		$new_link['child'] = $link['parent'];
		$new_link['parent_type'] = 'module';
		$new_link['child_type'] = 'agent';
		$new_link['id'] = db_process_sql_insert(
			'tnetworkmap_ent_rel_nodes',
			$new_link);
		
		$id_link_change = $new_link['id'];
		
		if ($new_link['id'] === false)
			return false;
		
		$link['parent'] = $new_node['id'];
		$link['parent_type'] = 'module';
		
		$result = db_process_sql_update('tnetworkmap_ent_rel_nodes',
			$link,
			array('id' => $link['id']));
	}
	
	
	if (!$result)
		return array('correct' => false, 'id_link_change' => $id_link_change);
	else
		return array('correct' => true, 'id_link_change' => $id_link_change);
}

function networkmap_delete_link($networkmap_id, $source_id,
	$source_module_id, $target_id, $target_module_id, $id_link) {
	
	
	
	$flag_delete_level2 = false;
	
	if ($source_module_id != 0) {
		$flag_delete_level2 = true;
	}
	
	if ($target_module_id != 0) {
		$flag_delete_level2 = true;
	}
	
	if ($flag_delete_level2) {
		$link = db_get_row_filter('tnetworkmap_ent_rel_nodes',
			array('id_networkmap_enterprise' => $networkmap_id,
				'id' => $id_link));
		
		if (($link['parent_type'] === 'agent') &&
			($link['child_type'] === 'agent')) {
			
			//Delete normaly
			
			$result = db_process_sql_update(
				'tnetworkmap_ent_rel_nodes',
				array('deleted' => 1),
				array('id' => $link['id']));
		}
		else {
			
			//Delete modules nodes and the relation module node with agent node
			
			if ($link['parent_type'] === 'module') {
				$result = db_process_sql_update(
					'tnetworkmap_enterprise_nodes',
					array('deleted' => 1),
					array('id' => $link['parent']));
				
				db_process_sql_update(
					'tnetworkmap_ent_rel_nodes',
					array('deleted' => 1),
					array('parent' => $link['parent']));
				
				db_process_sql_update(
					'tnetworkmap_ent_rel_nodes',
					array('deleted' => 1),
					array('child' => $link['parent']));
			}
			
			if (!empty($result)) {
				if (!$result)
					return $result;
			}
			
			if ($link['child_type'] === 'module') {
				$result = db_process_sql_update(
					'tnetworkmap_enterprise_nodes',
					array('deleted' => 1),
					array('id' => $link['child']));
				
				db_process_sql_update(
					'tnetworkmap_ent_rel_nodes',
					array('deleted' => 1),
					array('parent' => $link['child']));
				
				db_process_sql_update(
					'tnetworkmap_ent_rel_nodes',
					array('deleted' => 1),
					array('child' => $link['child']));
			}
		}
	}
	else {
		
		$result = db_process_sql_update(
			'tnetworkmap_ent_rel_nodes',
			array('deleted' => 1),
			array('id_networkmap_enterprise' => $networkmap_id,
				'parent' => $target_id,
				'child' => $source_id));
	}
	
	return $result;
}

function networkmap_get_new_nodes_and_links($id_networkmap) {
	
	$networkmap = db_get_row_filter('tnetworkmap_enterprise',
		array('id' => $id_networkmap));
	
	if (empty($networkmap))
		return $return;
	
	$networkmap['options'] = json_decode($networkmap['options'], true);
	
	
	
	////////////////////////////////////////////////////////////////////
	// Nodes
	////////////////////////////////////////////////////////////////////
	if ($networkmap['options']['recon_task_id'] != 0) {
		
		//get from the recon task IP mask
		
		$recon_task = db_get_row_filter('trecon_task',
			array('id_rt' => $networkmap['options']['recon_task_id']));
		
		$ip_mask = $recon_task['field1'];
		
		$agents = networkmap_get_new_nodes_from_ip_mask(
			$ip_mask);
		
		$new_agents = array();
		if (!empty($agents)) {
			
			$sql = "
				SELECT t1.id_agente
				FROM tagente t1
				WHERE t1.id_agente IN (" . implode(',', $agents) . ")
					AND t1.disabled = 0
					AND t1.id_agente NOT IN (
						SELECT id_agent
						FROM tnetworkmap_enterprise_nodes
						WHERE id_networkmap_enterprise = " . $id_networkmap . ")
				";
			$new_agents = db_get_all_rows_sql($sql);
			if (empty($new_agents))
				$new_agents = array();
		}
	}
	else if (!empty($networkmap['options']['ip_mask'])) {
		$agents = networkmap_get_new_nodes_from_ip_mask(
			$networkmap['options']['ip_mask']);
		
		$new_agents = array();
		if (!empty($agents)) {
			
			$sql = "
				SELECT t1.id_agente
				FROM tagente t1
				WHERE t1.id_agente IN (" . implode(',', $agents) . ")
					AND t1.disabled = 0
					AND t1.id_agente NOT IN (
						SELECT id_agent
						FROM tnetworkmap_enterprise_nodes
						WHERE id_networkmap_enterprise = " . $id_networkmap . ")
				";
			$new_agents = db_get_all_rows_sql($sql);
			if (empty($new_agents))
				$new_agents = array();
		}
	}
	else {
		
		//get from the group of the networkmap
		
		$id_group = $networkmap['id_group'];
		
		$sql_group = '1 = 1';
		if ($id_group > 0) {
			$dont_show_subgroups = false;
			if (isset($networkmap['options']['dont_show_subgroups']))
				$dont_show_subgroups = $networkmap['options']['dont_show_subgroups'];
			
			if ($dont_show_subgroups) {
				$sql_group = " t1.id_grupo = " . $networkmap['id_group'];
			}
			else {
				$childrens = groups_get_childrens($networkmap['id_group'], null, true);
				if (!empty($childrens)) {
					$childrens = array_keys($childrens);
					
					$id_groups = $childrens;
					$id_groups[] = $networkmap['id_group'];
					
					$sql_group = " t1.id_grupo IN ( " .
						implode(',', $id_groups) .
						") ";
				}
				else {
					$sql_group = " t1.id_grupo = " . $networkmap['id_group'];
				}
			}
		}
		
		$sql = "
			SELECT t1.id_agente
			FROM tagente t1
			WHERE " . $sql_group . "
				AND t1.disabled = 0
				AND t1.id_agente NOT IN (
					SELECT id_agent
					FROM tnetworkmap_enterprise_nodes
					WHERE id_networkmap_enterprise = " . $id_networkmap . ")
			";
		
		$new_agents = db_get_all_rows_sql($sql);
		
		if (empty($new_agents))
			$new_agents = array();
	}
	
	
	
	//Insert the new nodes
	foreach ($new_agents as $new_agent) {
		add_agent_networkmap(
			$id_networkmap,
			'', //empty because the function fill with the id
			666,
			666,
			$new_agent['id_agente'],
			array('state' => 'pending_holding_area')
			);
	}
	
	
	////////////////////////////////////////////////////////////////////
	// Links
	////////////////////////////////////////////////////////////////////
	$sql = "SELECT id_agent
		FROM tnetworkmap_enterprise_nodes
		WHERE id_networkmap_enterprise = " . $id_networkmap . "
			AND deleted = 0
		GROUP BY id_agent";
	$nodes = db_get_all_rows_sql($sql);
	foreach ($nodes as $node) {
		
		//First the relation parents without l2 interfaces
		$parent = db_get_value_filter('id_parent', 'tagente',
			array('id_agente' => $node['id_agent']));
		
		$child_node = db_get_value_filter('id',
			'tnetworkmap_enterprise_nodes',
			array('id_agent' => $node['id_agent'],
				'id_networkmap_enterprise' => $id_networkmap,
				'deleted' => 0));
		$parent_node = db_get_value_filter('id',
			'tnetworkmap_enterprise_nodes',
			array('id_agent' => $parent,
				'id_networkmap_enterprise' => $id_networkmap,
				'deleted' => 0));
		
		if (!empty($child_node) && !empty($parent_node)) {
			$exist = db_get_row_filter('tnetworkmap_ent_rel_nodes',
				array('id_networkmap_enterprise' => $id_networkmap,
					'parent' => $parent_node,
					'child' => $child_node,
					'parent_type' => 'agent',
					'child_type' => 'agent'));
			
			if (empty($exist)) {
				db_process_sql_insert(
					'tnetworkmap_ent_rel_nodes',
					array('id_networkmap_enterprise' => $id_networkmap,
						'parent' => $parent_node,
						'child' => $child_node,
						'parent_type' => 'agent',
						'child_type' => 'agent'));
			}
		}
		
		
		
		//Get L2 interface relations
		$interfaces = modules_get_interfaces($node['id_agent'],
			array('id_agente', 'id_agente_modulo'));
		if (empty($interfaces))
			$interfaces = array();
		
		
		foreach ($interfaces as $interface) {
			$relations = modules_get_relations(
				array('id_module' => $interface['id_agente_modulo']));
			if (empty($relations))
				$relations = array();
			
			
			foreach ($relations as $relation) {
				//Get the links althought they are deleted (for to
				// avoid to add)
				
				//Check if the module is ping
				if (modules_get_agentmodule_type($relation['module_a']) == 6) {
					//the pings modules are not exist as interface
					//the link is with the agent
					
					$node_a = db_get_value_filter('id',
						'tnetworkmap_enterprise_nodes',
						array(
							'id_agent' => modules_get_agentmodule_agent(
								$relation['module_a']),
							'id_networkmap_enterprise' => $id_networkmap));
				}
				else {
					$node_a = db_get_value_filter('id',
						'tnetworkmap_enterprise_nodes',
						array(
							'id_module' => $relation['module_a'],
							'id_networkmap_enterprise' => $id_networkmap));
				}
				
				//Check if the module is ping
				if (modules_get_agentmodule_type($relation['module_b']) == 6) {
					//the pings modules are not exist as interface
					//the link is with the agent
					
					$node_b = db_get_value_filter('id',
						'tnetworkmap_enterprise_nodes',
						array(
							'id_agent' => modules_get_agentmodule_agent(
								$relation['module_b']),
							'id_networkmap_enterprise' => $id_networkmap));
				}
				else {
					$node_b = db_get_value_filter('id',
						'tnetworkmap_enterprise_nodes',
						array(
							'id_module' => $relation['module_b'],
							'id_networkmap_enterprise' => $id_networkmap));
				}
				
				
				
				
				$exist = db_get_row_filter(
					'tnetworkmap_ent_rel_nodes',
					array('id_networkmap_enterprise' => $id_networkmap,
						'parent' => $node_a,
						'child' => $node_b));
				$exist_reverse = db_get_row_filter(
					'tnetworkmap_ent_rel_nodes',
					array('id_networkmap_enterprise' => $id_networkmap,
						'parent' => $node_b,
						'child' => $node_a));
				
				
				
				
				if (empty($exist) && empty($exist_reverse)) {
					
					
					//Create the nodes for interfaces
					// Ag1 ----- I1 ------ I2 ----- Ag2
					// * 2 interfaces nodes
					// * 3 relations
					//   * I1 between I2
					//   * Ag1 between I1
					//   * Ag2 between I2
					//
					//But check if it exists the relations
					// agent between interface
					
					
					if ($interface['id_agente_modulo'] == $relation['module_a']) {
						$agent_a = $interface['id_agente'];
						$agent_b = modules_get_agentmodule_agent(
							$relation['module_b']);
					}
					else {
						$agent_a = modules_get_agentmodule_agent(
							$relation['module_a']);
						$agent_b = $interface['id_agente'];
					}
					
					
					$exist_node_interface1 = db_get_row_filter(
						'tnetworkmap_enterprise_nodes',
						array('id_networkmap_enterprise' => $id_networkmap,
							'id_module' => $relation['module_a']));
					
					if (empty($exist_node_interface1)) {
						//Crete the interface node
						//
						//and create the relation between agent and
						//interface
						
						$node_interface1 =
							add_agent_networkmap(
								$id_networkmap,
								'',
								666,
								666,
								$agent_a,
								array('state' => 'pending_holding_area',
									'text' => modules_get_agentmodule_name($relation['module_a']),
									'id_module' => $relation['module_a'],
									'type' => 'module'
									)
								);
						
						$node_agent1 = db_get_value('id',
							'tnetworkmap_enterprise_nodes', 'id_agent',
							$agent_a);
						
						db_process_sql_insert(
							'tnetworkmap_ent_rel_nodes',
							array('id_networkmap_enterprise' => $id_networkmap,
								'parent' => $node_agent1,
								'child' => $node_interface1,
								'parent_type' => 'agent',
								'child_type' => 'module'));
					}
					else {
						$node_interface1 = $exist_node_interface1;
					}
					
					
					$exist_node_interface2 = db_get_row_filter(
						'tnetworkmap_enterprise_nodes',
						array('id_networkmap_enterprise' => $id_networkmap,
							'id_module' => $relation['module_b']));
					
					if (empty($exist_node_interface2)) {
						//Crete the interface node
						//
						//and create the relation between agent and
						//interface
						
						$node_interface2 = 
							add_agent_networkmap(
								$id_networkmap,
								'',
								666,
								666,
								$agent_b,
								array('state' => 'pending_holding_area',
									'text' => modules_get_agentmodule_name($relation['module_b']),
									'id_module' => $relation['module_b'],
									'type' => 'module'
									)
								);
						
						$node_agent1 = db_get_value('id',
							'tnetworkmap_enterprise_nodes', 'id_agent',
							$agent_a);
						
						db_process_sql_insert(
							'tnetworkmap_ent_rel_nodes',
							array('id_networkmap_enterprise' => $id_networkmap,
								'parent' => $node_agent1,
								'child' => $node_interface1,
								'parent_type' => 'agent',
								'child_type' => 'module'));
					}
					else {
						$node_interface2 = $exist_node_interface2;
					}
					
					if (!empty($node_interface1) &&
						!empty($node_interface2)) {
						
						if (is_array($node_interface1)) {
							$node_interface1 = $node_interface1['id'];
						}
						if (is_array($node_interface2)) {
							$node_interface2 = $node_interface2['id'];
						}
						
						
						
						db_process_sql_insert(
							'tnetworkmap_ent_rel_nodes',
							array('id_networkmap_enterprise' => $id_networkmap,
								'parent' => $node_interface2,
								'child' => $node_interface1,
								'parent_type' => 'module',
								'child_type' => 'module'));
					}
				}
			}
		}
		
		
	}
}



function networkmap_refresh_holding_area($id_networkmap) {
	
	
	networkmap_get_new_nodes_and_links($id_networkmap);
	networkmap_clean_duplicate_links($id_networkmap);
	
	
	
	$rows = db_get_all_rows_filter('tnetworkmap_enterprise_nodes',
		array('id_networkmap_enterprise' => $id_networkmap,
			'state' => 'pending_holding_area', 'deleted' => 0));
	if (empty($rows))
		$rows = array();
	
	$nodes = array();
	
	$count = 0;
	$count_item_holding_area = 0;
	foreach ($rows as $row) {
		$row['options'] = json_decode($row['options'], true);
		
		if (isset($row['options']['type'])) {
			if ($row['options']['type'] == 'module')
				continue;
		}
		else {
			$row['options']['type'] = '';
		}
		
		$row['state'] = 'holding_area';
		db_process_sql_update('tnetworkmap_enterprise_nodes',
			array('state' => $row['state']),
			array('id' => $row['id']));
		
		$node = networkmap_db_node_to_js_node($row, $count,
			$count_item_holding_area);
		
		$nodes[$node['id']] = $node;
	}
	
	//Get all links of actual nodes
	//but in the javascript code filter the links and only add the
	//new links
	
	$relations = db_get_all_rows_sql("
		SELECT t1.*,
			
			(SELECT t2.id_agent
			FROM tnetworkmap_enterprise_nodes t2
			WHERE t2.id_networkmap_enterprise = " . $id_networkmap . "
				AND t2.id = t1.parent) AS id_agent_parent,
			
			(SELECT t2.id_agent
			FROM tnetworkmap_enterprise_nodes t2
			WHERE t2.id_networkmap_enterprise = " . $id_networkmap . "
				AND t2.id = t1.child) AS id_agent_child,
			
			(SELECT t2.id_module
			FROM tnetworkmap_enterprise_nodes t2
			WHERE t2.id_networkmap_enterprise = " . $id_networkmap . "
				AND t2.id = t1.parent) AS id_module_parent,
			
			(SELECT t2.id_module
			FROM tnetworkmap_enterprise_nodes t2
			WHERE t2.id_networkmap_enterprise = " . $id_networkmap . "
				AND t2.id = t1.child) AS id_module_child
		
		FROM tnetworkmap_ent_rel_nodes t1
		WHERE t1.id_networkmap_enterprise = " . $id_networkmap . "
			AND t1.deleted = 0");
	if ($relations === false) $relations = array();
	networkmap_clean_relations_for_js($relations);
	
	
	$links_js = networkmap_links_to_js_links(
		$relations, $nodes);
	
	return array('nodes' => $nodes, 'links' => $links_js);
}

?>
