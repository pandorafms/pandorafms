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



/**
 * @package Include
 * @subpackage Network_Map
 */

/**
 * Include agents function
 */
require_once ('functions_agents.php');
require_once($config['homedir'] . "/include/functions_modules.php");
require_once($config['homedir'] . "/include/functions_groups.php");
ui_require_css_file ('cluetip');

// Check if a node descends from a given node
function networkmap_is_descendant ($node, $ascendant, $parents) {
	if (! isset ($parents[$node])) {
		return false;
	}
	
	if ($node == $ascendant) {
		return true;
	}
	
	return networkmap_is_descendant ($parents[$node], $ascendant, $parents);
}

function networkmap_print_jsdata($graph, $js_tags = true) {
	if ($js_tags) {
		echo "<script type='text/javascript'>";
		
		if (empty($graph)) {
			echo "var graph = null;\n";
			return;
		}
		else {
			echo "var graph = \n";
		}
	}
	
	echo "{\n";
	echo "'nodes' : \n";
	echo "[\n";
	$first = true;
	foreach ($graph['nodes'] as $id => $node) {
		if (!$first) {
			echo ",\n";
		}
		$first = false;
		
		echo "{
			'id' : " . $id . ",
			'name' : '" . $node['label'] . "',
			'url' : '" . $node['url'] . "',
			'tooltip' : '" . $node['tooltip'] . "',
			'default_tooltip' : 1,
			'tooltip_content' : ' " . html_print_image('images/spinner.gif',true) . "',
			'color' : '" . $node['color'] . "'}\n";
	}
	echo "],\n";
	
	echo "'links' : \n";
	echo "[\n";
	$first = true;
	foreach ($graph['lines'] as $line) {
		if (!$first) {
			echo ",\n";
		}
		$first = false;
		
		echo "{
			'source' : " . $line['source'] . ",
			'target' : " . $line['target'] . "}\n";
	}
	echo "]\n";
	
	echo "}\n";
	
	if ($js_tags) {
		echo ";\n";
		echo "</script>";
	}
}

function networkmap_generate_hash($pandora_name, $group = 0,
	$simple = 0, $font_size = 12, $layout = 'radial', $nooverlap = 0,
	$zoom = 1, $ranksep = 2.5, $center = 0, $regen = 1, $pure = 0,
	$id_networkmap = 0, $show_snmp_modules = 0, $cut_names = true,
	$relative = false, $text_filter = '') {
	
	$graph = networkmap_generate_dot($pandora_name, $group,
		$simple, $font_size, $layout, $nooverlap, $zoom, $ranksep,
		$center, $regen, $pure, $id_networkmap, $show_snmp_modules,
		$cut_names, $relative, $text_filter, false, null, false, $strict_user);
	
	$return = array();
	if (!empty($graph)) {
		$graph = str_replace("\r", "\n", $graph);
		$graph = str_replace("\n", " ", $graph);
		
		//Removed the head
		preg_match("/graph networkmap {(.*)}/", $graph, $matches);
		$graph = $matches[1];
		
		
		//Get the lines and nodes
		$tokens = preg_split("/; /", $graph);
		foreach ($tokens as $token) {
			if (empty($token)) {
				continue;
			}
			
			//Ignore the head rests.
			if (preg_match("/(.+)\s*\[(.*)\]/", $token) != 0) {
				$items[] = $token; 
			}
		}
		
		$lines = $nodes = array();
		foreach ($items as $item) {
			$matches = null;
			preg_match("/(.+)\s*\[(.*)\]/", $item, $matches);
			if (empty($matches))
				continue;
			
			$id_item = trim($matches[1]);
			$content_item = trim($matches[2]);
			
			//Check if is a edge or node
			if (strstr($id_item, "--") !== false) {
				//edge
				$lines[$id_item] = $content_item;
			}
			else {
				//node
				$id_item = (int)$id_item;
				$nodes[$id_item] = $content_item;
			}
		}
		
		
		foreach($nodes as $key => $node) {
			if ($key != 0) {
				//Get label
				$matches = null;
				preg_match("/label=(.*),/", $node, $matches);
				$label = $matches[1];
				$matches = null;
				preg_match("/\<TR\>\<TD\>(.*?)\<\/TD\>\<\/TR\>/",
					$label, $matches);
				$label = str_replace($matches[0], '', $label);
				$matches = null;
				preg_match("/\<TR\>\<TD\>(.*?)\<\/TD\>\<\/TR\>/",
					$label, $matches);
				$label = $matches[1];
				
				//Get color
				$matches = null;
				preg_match("/color=\"([^\"]*)/", $node, $matches);
				$color = $matches[1];
				
				//Get tooltip
				$matches = null;
				preg_match("/tooltip=\"([^\"]*)/", $node, $matches);
				$tooltip = $matches[1];
				
				//Get URL
				$matches = null;
				preg_match("/URL=\"([^\"]*)/", $node, $matches);
				$url = $matches[1];
				
				$return['nodes'][$key]['label'] = $label;
				$return['nodes'][$key]['color'] = $color;
				$return['nodes'][$key]['tooltip'] = $tooltip;
				$return['nodes'][$key]['url'] = $url;
			}
			else {
				//Get tooltip
				$matches = null;
				preg_match("/tooltip=\"([^\"]*)/", $node, $matches);
				$tooltip = $matches[1];
				
				//Get URL
				$matches = null;
				preg_match("/URL=\"([^\"]*)/", $node, $matches);
				$url = $matches[1];
				
				$return['nodes'][$key]['label'] = "Pandora FMS";
				$return['nodes'][$key]['color'] = "#7EBE3F";
				$return['nodes'][$key]['tooltip'] = $tooltip;
				$return['nodes'][$key]['url'] = $url;
			}
		}
		ksort($return['nodes']);
		
		foreach($lines as $key => $line) {
			$data = array();
			
			$points = explode(' -- ', $key);
			$data['source'] = (int) $points[0];
			$data['target'] = (int) $points[1];
			$return['lines'][] = $data;
		}
	}
	
	return $return;
}

function networkmap_check_exists_edge_between_nodes($edges, $node_a, $node_b) {
	$relation = false;
	
	if (is_array($edges[$node_a])) {
		if (array_search($node_b, $edges[$node_a]) !== false)
			$relation = true;
	}
	else {
		if ($edges[$node_a] == $node_b)
			$relation = true;
	}
	
	if (is_array($edges[$node_b])) {
		if (array_search($node_a, $edges[$node_b]) !== false)
			$relation = true;
	}
	else {
		if ($edges[$node_b] == $node_a)
			$relation = true;
	}
	
	return $relation;
}


function networkmap_generate_dot ($pandora_name, $group = 0,
	$simple = 0, $font_size = 12, $layout = 'radial', $nooverlap = 0,
	$zoom = 1, $ranksep = 2.5, $center = 0, $regen = 1, $pure = 0,
	$id_networkmap = 0, $show_snmp_modules = 0, $cut_names = true,
	$relative = false, $text_filter = '', $ip_mask = null,
	$dont_show_subgroups = false, $strict_user = false, $size_canvas = null,
	$old_mode = false) {
	
	global $config;
	$nooverlap = 1;
	
	$parents = array();
	$orphans = array();
	
	$filter = array ();
	$filter['disabled'] = 0;
	
	if (!empty($text_filter)) {
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				$filter[] =
					'(nombre COLLATE utf8_general_ci LIKE "%' . $text_filter . '%")';
				break;
			case "oracle":
				$filter[] =
					'(upper(nombre) LIKE upper(\'%' . $text_filter . '%\'))';
				break;
		}
	}
	
	if ($group >= 1) {
		if ($dont_show_subgroups)
			$filter['id_grupo'] = $group;
		else {
			$childrens = groups_get_childrens($group, null, true);
			if (!empty($childrens)) {
				$childrens = array_keys($childrens);
				
				$filter['id_grupo'] = $childrens;
				$filter['id_grupo'][] = $group;
			}
			else {
				$filter['id_grupo'] = $group;
			}
		}
		
		//Order by id_parent ascendant for to avoid the bugs
		//because the first agents to process in the next
		//foreach loop are without parent (id_parent = 0)
		
		// Get agents data
		if ($strict_user) {
			if ($dont_show_subgroups)
				$filter['id_group'] = $group;
			else {
				if (!empty($childrens)) {
					foreach ($childrens as $children) {
						$filter_id_groups[$children] = $children;
					}
				} 
				$filter_id_groups[$group] = $group;
				$filter['id_group'] = implode(',', $filter_id_groups);
			}
			
			$filter['group_by'] = 'tagente.id_agente';
			$fields = array ('tagente.id_grupo, tagente.nombre, tagente.id_os, tagente.id_parent, tagente.id_agente, 
						tagente.normal_count, tagente.warning_count, tagente.critical_count,
						tagente.unknown_count, tagente.total_count, tagente.notinit_count');
			$acltags = tags_get_user_module_and_tags ($config['id_user'],'AR', $strict_user);
			$agents = tags_get_all_user_agents (false, $config['id_user'], $acltags, $filter, $fields, false, $strict_user, true);
		}
		else {
			$agents = agents_get_agents ($filter,
				array ('id_grupo, nombre, id_os, id_parent, id_agente,
					normal_count, warning_count, critical_count,
					unknown_count, total_count, notinit_count'), 'AR',
					array('field' => 'id_parent', 'order' => 'ASC'));
		}
	}
	else if ($group == -666) {
		$agents = false;
	}
	else if (!empty($ip_mask)) {
		$agents = networkmap_get_new_nodes_from_ip_mask($ip_mask,
			array ('id_grupo, nombre, id_os, id_parent, id_agente,
				normal_count, warning_count, critical_count,
				unknown_count, total_count, notinit_count'), $strict_user);
	}
	else {
		if ($strict_user) {
			$filter['group_by'] = 'tagente.id_agente';
			$fields = array ('tagente.id_grupo, tagente.nombre, tagente.id_os, tagente.id_parent, tagente.id_agente, 
						tagente.normal_count, tagente.warning_count, tagente.critical_count,
						tagente.unknown_count, tagente.total_count, tagente.notinit_count');
			$acltags = tags_get_user_module_and_tags ($config['id_user'],'AR', $strict_user);
			$agents = tags_get_all_user_agents (false, $config['id_user'], $acltags, $filter, $fields, false, $strict_user, true);
		}
		else {
			$agents = agents_get_agents ($filter,
				array ('id_grupo, nombre, id_os, id_parent, id_agente,
					normal_count, warning_count, critical_count,
					unknown_count, total_count, notinit_count'), 'AR',
					array('field' => 'id_parent', 'order' => 'ASC'));
		}
	}
	
	if ($agents === false)
		//return false;
		$agents = array();
	
	// Open Graph
	$graph = networkmap_open_graph ($layout, $nooverlap, $pure, $zoom,
		$ranksep, $font_size, $size_canvas);
	
	// Parse agents
	$nodes = array ();
	
	// Add node refs
	$node_ref = array();
	$modules_node_ref = array();
	
	$node_count = 0;
	
	foreach ($agents as $agent) {
		$node_count++;
		
		$node_ref[$agent['id_agente']] = $node_count;
		
		$agent['id_node'] = $node_count;
		$agent['type'] = 'agent';
		
		// Add node
		$nodes[$node_count] = $agent;
			
		$filter = array();
		$filter['disabled'] = 0;
		
		// Get agent modules data
		if ($strict_user) {
			$modules = tags_get_agent_modules ($agent['id_agente'], false, $acltags, false, $filter, false);
		}
		else {
			$modules = agents_get_modules($agent['id_agente'], '*', $filter, true, true);
		}
		
		if ($modules === false)
			$modules = array();
		
		// Parse modules
		foreach ($modules as $key => $module) {
			
			if ($module['id_tipo_modulo'] != 18 && $module['id_tipo_modulo'] != 6) {
				continue;
			}
			
			$node_count ++;
			$modules_node_ref[$module['id_agente_modulo']] = $node_count;
			$module['id_node'] = $node_count;
			$module['type'] = 'module';
			
			// Try to get the interface name
			if (preg_match ("/_(.+)$/" , (string)$module['nombre'], $matches)) {
				if ($matches[1]) {
					$module['nombre'] = $matches[1];
				}
			}
			
			// Save node parent information to define edges later
			$parents[$node_count] = $module['parent'] = $agent['id_node'];
			
			// Add node
			$nodes[$node_count] = $module;
		}
	}
	
	foreach ($modules_node_ref as $id_module => $node_count) {
		$module_type = modules_get_agentmodule_type($id_module);
		if ($module_type != 18) {
			unset($nodes[$node_count]);
			unset($orphans[$node_count]);
			unset($parents[$node_count]);
		}
	}
	
	// Addded the relationship of parents of agents
	foreach ($agents as $agent) {
		if ($agent['id_parent'] != "0" && array_key_exists($agent['id_parent'], $node_ref)) {
			
			$parents[$node_ref[$agent['id_agente']]] = $node_ref[$agent['id_parent']];
		}
		else {
			$orphans[$node_ref[$agent['id_agente']]] = 1;
		}
	}
	
	// Create a central node if orphan nodes exist
	if (count ($orphans) || empty ($nodes)) {
		$graph .= networkmap_create_pandora_node ($pandora_name, $font_size, $simple, $stats);
	}
	
	// Define edges for orphan nodes
	foreach (array_keys($orphans) as $node) {
		$graph .= networkmap_create_edge ('0', $node, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, 'operation/agentes/networkmap', 'topology', $id_networkmap);
	}
	
	// Create void statistics array
	$stats = array();
	
	$count = 0;
	$group_nodes = 10;
	$graph .= networkmap_create_transparent_node($count);
	foreach (array_keys($orphans) as $node) {
		if ($group_nodes == 0) {
			$count++;
			$graph .= networkmap_create_transparent_node($count);
			
			$group_nodes = 10;
		}
		
		$graph .= networkmap_create_transparent_edge('transp_' . $count,
			$node);
		
		$group_nodes--;
	}
	
	// Create nodes
	foreach ($nodes as $node_id => $node) {
		if ($center > 0 && ! networkmap_is_descendant ($node_id, $center, $parents)) {
			unset ($parents[$node_id]);
			unset ($orphans[$node_id]);
			unset ($nodes[$node_id]);
			continue;
		}
		
		switch ($node['type']) {
			case 'agent':
				$graph .= networkmap_create_agent_node($node, $simple,
					$font_size, $cut_names, $relative) . "\n\t\t";
				$stats['agents'][] = $node['id_agente'];
				break;
			case 'module':
				$graph .= networkmap_create_module_node($node, $simple,
					$font_size) . "\n\t\t";
				$stats['modules'][] = $node['id_agente_modulo'];
				break;
		}
	}
	
	// Define edges
	foreach ($parents as $node => $parent_id) {
		// Verify that the parent is in the graph
		if (isset ($nodes[$parent_id])) {
			$graph .= networkmap_create_edge ($parent_id,
				$node,
				$layout,
				$nooverlap,
				$pure,
				$zoom,
				$ranksep,
				$simple,
				$regen,
				$font_size,
				$group,
				'operation/agentes/networkmap',
				'topology',
				$id_networkmap);
		}
		else {
			$orphans[$node] = 1;
		}
	}
	
	// Define edges for the module interfaces relations
	// Get the remote_snmp_proc relations
	$relations = modules_get_relations();
	if ($relations === false)
		$relations = array();
	foreach ($relations as $key => $relation) {
		$module_a = $relation['module_a'];
		$module_a_type = modules_get_agentmodule_type($module_a);
		$agent_a = modules_get_agentmodule_agent($module_a);
		$module_b = $relation['module_b'];
		$module_b_type = modules_get_agentmodule_type($module_b);
		$agent_b = modules_get_agentmodule_agent($module_b);
		
		if ($module_a_type == 18 && $module_b_type == 18) {
			if (isset($modules_node_ref[$module_a]) &&
				isset($modules_node_ref[$module_b])) {
				$graph .= networkmap_create_edge(
					$modules_node_ref[$module_a],
					$modules_node_ref[$module_b],
					$layout,
					$nooverlap,
					$pure,
					$zoom,
					$ranksep,
					$simple,
					$regen,
					$font_size,
					$group,
					'operation/agentes/networkmap',
					'topology',
					$id_networkmap);
			}
		}
		elseif ($module_a_type == 6 && $module_b_type == 6) {
			if (isset($node_ref[$agent_a]) &&
				isset($node_ref[$agent_b])) {
				$graph .= networkmap_create_edge(
					$node_ref[$agent_a],
					$node_ref[$agent_b],
					$layout,
					$nooverlap,
					$pure,
					$zoom,
					$ranksep,
					$simple,
					$regen,
					$font_size,
					$group,
					'operation/agentes/networkmap',
					'topology',
					$id_networkmap);
			}
		
		}
		elseif ($module_a_type == 6 && $module_b_type == 18) {
			if (isset($node_ref[$agent_a]) &&
				isset($modules_node_ref[$module_b])) {
				$graph .= networkmap_create_edge(
					$node_ref[$agent_a],
					$modules_node_ref[$module_b],
					$layout,
					$nooverlap,
					$pure,
					$zoom,
					$ranksep,
					$simple,
					$regen,
					$font_size,
					$group,
					'operation/agentes/networkmap',
					'topology',
					$id_networkmap);
			}
		}
		elseif ($module_b_type == 6 && $module_a_type == 18) {
			if (isset($node_ref[$agent_b]) &&
				isset($modules_node_ref[$module_a])) {
				$graph .= networkmap_create_edge(
					$node_ref[$agent_b],
					$modules_node_ref[$module_a],
					$layout,
					$nooverlap,
					$pure,
					$zoom,
					$ranksep,
					$simple,
					$regen,
					$font_size,
					$group,
					'operation/agentes/networkmap',
					'topology',
					$id_networkmap);
			}
		}
	}
	
	// Close graph
	$graph .= networkmap_close_graph ();
	return $graph;
}

// Generate a dot graph definition for graphviz with groups
function networkmap_generate_dot_groups ($pandora_name, $group = 0,
	$simple = 0, $font_size = 12, $layout = 'radial', $nooverlap = 0,
	$zoom = 1, $ranksep = 2.5, $center = 0, $regen = 1, $pure = 0,
	$modwithalerts = 0, $module_group = 0, $hidepolicymodules = 0,
	$depth = 'all', $id_networkmap = 0, $dont_show_subgroups = 0,
	$text_filter = '', $strict_user = false, $size_canvas = null) {
	
	global $config;

	if ($strict_user) {
		$acltags = tags_get_user_module_and_tags ($config['id_user'],'AR', $strict_user);
	}
	$parents = array();
	$orphans = array();
	
	$filter = array ();
	$filter['disabled'] = 0;
	
	if (!empty($text_filter)) {
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				$filter[] =
					'(nombre COLLATE utf8_general_ci LIKE "%' . $text_filter . '%")';
				break;
			case "oracle":
				$filter[] =
					'(upper(nombre) LIKE upper(\'%' . $text_filter . '%\'))';
				break;
		}
	}
	
	// Get groups data
	if ($group > 0) {
		$groups = array();
		$id_groups = groups_get_id_recursive($group, true);
		
		foreach($id_groups as $id_group) {
			$add = false;
			if (check_acl($config["id_user"], $id_group, 'AR')) {
				$add = true;
			}
			
			if ($add) {
				$groups[] = db_get_row ('tgrupo', 'id_grupo', $id_group);
			}
		}
		
		$filter['id_grupo'] = $id_groups;
	}
	else {
		if ($strict_user) {
			$groups = users_get_groups ($config['id_user'],"AR", false, true);
		}
		else {
			$groups = db_get_all_rows_in_table ('tgrupo');
		}
		if ($groups === false) {
			$groups = array();
		}
	}
	
	// Open Graph
	$graph = networkmap_open_graph ($layout, $nooverlap, $pure, $zoom,
		$ranksep, $font_size, $size_canvas);
	
	$node_count = 0;
	
	// Parse groups
	$nodes = array ();
	$nodes_groups = array();
	foreach ($groups as $group2) {
		$node_count ++;
		$group2['type'] = 'group';
		$group2['id_node'] = $node_count;
		
		// Add node
		$nodes_groups[$group2['id_grupo']] = $group2;
	}
	
	$node_count = 0;
	
	$groups_hiden = array();
	foreach ($nodes_groups as $node_group) {
		
		$node_count++;
		
		// Save node parent information to define edges later
		if ($node_group['parent'] != "0" && $node_group['id_grupo'] != $group) {
			if ((!$dont_show_subgroups) || ($group == 0)) {
				$parents[$node_count] = $nodes_groups[$node_group['parent']]['id_node'];
			}
			else {
				$groups_hiden[$node_group['id_grupo']] = 1;
				continue;
			}
		}
		else {
			$orphans[$node_count] = 1;
		}
		
		$nodes[$node_count] = $node_group;
	}
	
	if ($depth != 'group') {
		if ($strict_user) {
			$filter['group_by'] = 'tagente.nombre';
			$filter['id_group'] = $filter['id_grupo'];
			$fields = array ('tagente.id_grupo, tagente.nombre, tagente.id_os, tagente.id_agente, 
						tagente.normal_count, tagente.warning_count, tagente.critical_count,
						tagente.unknown_count, tagente.total_count, tagente.notinit_count');
			$agents = tags_get_all_user_agents (false, $config['id_user'], $acltags, $filter, $fields, false, $strict_user, true);
			unset($filter['id_group']);
		}
		else {
		// Get agents data
		$agents = agents_get_agents ($filter,
			array ('id_grupo, nombre, id_os, id_agente, 
				normal_count, warning_count, critical_count,
				unknown_count, total_count, notinit_count'));
		}
		if ($agents === false)
			$agents = array();
		
		// Parse agents
		$nodes_agents = array();
		foreach ($agents as $agent) {
			if ($dont_show_subgroups) {
				if (!empty($groups_hiden[$agent['id_grupo']])) {
					continue;
				}
			}
			
			// If only agents with alerts => agents without alerts discarded
			$alert_agent = agents_get_alerts($agent['id_agente']); 
			
			if ($modwithalerts and empty($alert_agent['simple'])) {
				continue;
			}
			
			$node_count ++;
			// Save node parent information to define edges later
			$parents[$node_count] = $agent['parent'] = $nodes_groups[$agent['id_grupo']]['id_node'];
			
			$agent['id_node'] = $node_count;
			$agent['type'] = 'agent';
			// Add node
			$nodes[$node_count] = $nodes_agents[$agent['id_agente']] = $agent;
			
			if ($depth == 'agent') {
				continue;
			}
			
			// Get agent modules data
			if ($strict_user) {
				$filter['disabled'] = 0;
				$modules = tags_get_agent_modules ($agent['id_agente'], false, $acltags, false, $filter, false);
			} else {
				$modules = agents_get_modules ($agent['id_agente'], false, array('disabled' => 0), true, false);
			}

			// Parse modules
			foreach ($modules as $key => $module) {
				$node_count ++;
				$agent_module = modules_get_agentmodule($key);
				$alerts_module = db_get_sql('SELECT count(*) AS num
					FROM talert_template_modules
					WHERE id_agent_module = ' . $key);
				
				if ($alerts_module == 0 && $modwithalerts) {
					continue;
				}
				
				if ($agent_module['id_module_group'] != $module_group &&
					$module_group != 0) {
					continue;
				}
				
				if ($hidepolicymodules && $config['enterprise_installed']) {
					enterprise_include_once('include/functions_policies.php');
					if (policies_is_module_in_policy($key)) {
						continue;
					}
				}
				
				// Save node parent information to define edges later
				$parents[$node_count] = $agent_module['parent'] = $agent['id_node'];
				
				$agent_module['id_node'] = $node_count;
				
				$agent_module['type'] = 'module';
				// Add node
				$nodes[$node_count] = $agent_module;
			}
		}
	}
	
	if (empty ($nodes)) {
		return false;
	}
	
	// Create void statistics array
	$stats = array();

	// Create nodes
	foreach ($nodes as $node_id => $node) {
		if ($center > 0 && ! networkmap_is_descendant ($node_id, $center, $parents)) {
			unset ($parents[$node_id]);
			unset ($orphans[$node_id]);
			unset ($nodes[$node_id]);
			continue;
		}
		switch ($node['type']) {
			case 'group':
				$graph .= networkmap_create_group_node ($node , $simple, $font_size, $metaconsole = false, null, $strict_user) .
					"\n\t\t";
				$stats['groups'][] = $node['id_grupo'];
				break;
			case 'agent':
				$graph .= networkmap_create_agent_node ($node , $simple, $font_size, true, true) .
					"\n\t\t";
				$stats['agents'][] = $node['id_agente'];
				break;
			case 'module':
				$graph .= networkmap_create_module_node ($node , $simple, $font_size) .
					"\n\t\t";
				$stats['modules'][] = $node['id_agente_modulo'];
				break;
		}
	}
	// Define edges
	foreach ($parents as $node => $parent_id) {
		// Verify that the parent is in the graph
		if (isset ($nodes[$parent_id])) {
			$graph .= networkmap_create_edge ($node, $parent_id, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, 'operation/agentes/networkmap', 'groups', $id_networkmap);
		}
		else {
			$orphans[$node] = 1;
		}
	}
	
	// Create a central node if orphan nodes exist
	if (count ($orphans)) {
		$graph .= networkmap_create_pandora_node ($pandora_name, $font_size, $simple, $stats);
	}
	
	// Define edges for orphan nodes
	foreach (array_keys($orphans) as $node) {
		$graph .= networkmap_create_edge ('0', $node, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, 'operation/agentes/networkmap', 'groups', $id_networkmap);
	}
	
	// Close graph
	$graph .= networkmap_close_graph ();
	
	return $graph;
}

// Returns an edge definition
function networkmap_create_edge ($head, $tail, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, $sec2 = 'operation/agentes/networkmap', $tab = 'topology', $id_networkmap = 0) {
	if (defined("METACONSOLE")) {
		$url = '';
	}
	else {
		$url = 'index.php?sec=estado&' .
			'sec2=' . $sec2 .'&' .
			'tab=' . $tab . '&' .
			'recenter_networkmap=1&' .
			'center=' . $head . '&' .
			'layout=' . $layout . '&' .
			'nooverlap=' . $nooverlap . '&' .
			'pure=' . $pure . '&' .
			'zoom=' . $zoom . '&' .
			'ranksep=' . $ranksep . '&' .
			'simple=' . $simple . '&' .
			'regen=1'. '&' .
			'font_size=' . $font_size . '&' .
			'group=' . $group . '&' .
			'id_networkmap=' . $id_networkmap;
	}
	
	// edgeURL allows node navigation
	$edge = "\n" . $head . ' -- ' . $tail .
		'[color="#BDBDBD", headclip=false, tailclip=false, edgeURL=""];' . "\n";
	
	return $edge;
}

function networkmap_create_transparent_edge($head, $tail) {
	// edgeURL allows node navigation
	$edge = "\n" . $head . ' -- ' . $tail .
		'[color="#00000000", headclip=false, tailclip=false, edgeURL=""];' . "\n";
	
	return $edge;
}

// Returns a group node definition
function networkmap_create_group_node ($group, $simple = 0, $font_size = 10, $metaconsole = false, $id_server = null, $strict_user = false) {
	global $config;
	global $hack_networkmap_mobile;
	
	$status = groups_get_status ($group['id_grupo'], $strict_user);

	// Set node status
	switch ($status) {
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
	
	
	
	$icon = groups_get_icon($group['id_grupo']);
	
	if ($simple == 0) {
		// Set node icon
		if ($hack_networkmap_mobile) {
			$img_node = $config['homedir'] . "/images/groups_small/" . $icon . ".png";
			
			if (!file_exists($img_node)) {
				$img_node = '-';
			}
			$img_node = '<img src="' . $img_node . '" />';
		}
		else if (file_exists (html_print_image("images/groups_small/" . $icon . ".png", true, false, true, true))) { 
			$img_node = html_print_image("images/groups_small/" . $icon . ".png", true, false, false, true);
		}
		else {
			$img_node = '-';
		}
		
		if (strlen(groups_get_name($group['id_grupo'])) > 40) {
			$name = substr(groups_get_name($group['id_grupo']), 0, 40) . '...';
		}
		else {
			$name = groups_get_name($group['id_grupo']);
		}
		
		if (defined("METACONSOLE")) {
			$url = '';
			$url_tooltip = '';
		}
		else {
			$url = 'index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$group['id_grupo'];
			$url_tooltip = 'ajax.php?page=operation/agentes/ver_agente&get_group_status_tooltip=1&id_group='.$group['id_grupo'];
		}
		
		$node = "\n" . $group['id_node'].' [ color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.30, height=0.30, ' .
		'label=<<TABLE CELLPADDING="0" data-status="' . $status . '" CELLSPACING="0" BORDER="0"><TR><TD>'.$img_node.'</TD></TR>
		 <TR><TD>'.io_safe_output($name).'</TD></TR></TABLE>>,
		 shape="invtrapezium", URL="' . $url . '",
		 tooltip="' . $url_tooltip . '"];' . "\n";
	}
	else {
		if (defined("METACONSOLE")) {
			$url = '';
			$url_tooltip = '';
		}
		else {
			$url = 'index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$group['id_grupo'];
			$url_tooltip = 'ajax.php?page=operation/agentes/ver_agente&get_group_status_tooltip=1&id_group='.$group['id_grupo'];
		}
		
		$node = "\n" . $group['id_node'] . ' [ color="'.$status_color.'", fontsize='.$font_size.', shape="invtrapezium",
			URL="' . $url . '", style="filled", fixedsize=true, width=0.20, height=0.20, label="",
			tooltip="' . $url_tooltip . '"];' . "\n";
	}
	return $node;
}

// Returns a node definition
function networkmap_create_agent_node ($agent, $simple = 0, $font_size = 10, $cut_names = true, $relative = false, $metaconsole = false, $id_server = null, $strict_user = false) {
	global $config;
	global $hack_networkmap_mobile;
	
	if ($strict_user) {
		require_once($config['homedir']."/include/functions_tags.php");
		$acltags = tags_get_user_module_and_tags ($config["id_user"], 'AR', $strict_user);
		
		$agent_filter = array("id" => $agent["id_agente"]);
		$strict_data['normal_count'] = (int) groups_get_normal_monitors ($agent['id_grupo'], $agent_filter, array(), $strict_user, $acltags);
		$strict_data['warning_count'] = (int) groups_get_warning_monitors ($agent['id_grupo'], $agent_filter, array(), $strict_user, $acltags);
		$strict_data['critical_count'] = (int) groups_get_critical_monitors ($agent['id_grupo'], $agent_filter, array(), $strict_user, $acltags);
		$strict_data['unknown_count'] = (int) groups_get_unknown_monitors ($agent['id_grupo'], $agent_filter, array(), $strict_user, $acltags);
		$strict_data['notinit_count'] = (int) groups_get_not_init_monitors ($agent['id_grupo'], $agent_filter, array(), $strict_user, $acltags);
		$strict_data['total_count'] = (int) groups_get_total_monitors ($agent['id_grupo'], $agent_filter, array(), $strict_user, $acltags);
		$status = agents_get_status_from_counts($strict_data);
	} else {
		$status = agents_get_status_from_counts($agent);
	}
	
	if (defined('METACONSOLE')) {
		$server_data = db_get_row('tmetaconsole_setup', 'id',
			$agent['id_server']);
	}
	
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
	
	// Set node status
	switch ($status) {
		case AGENT_STATUS_NORMAL: 
			$status_color = COL_NORMAL;
			break;
		case AGENT_STATUS_CRITICAL:
			$status_color = COL_CRITICAL;
			break;
		case AGENT_STATUS_WARNING:
			$status_color = COL_WARNING;
			break;
		case AGENT_STATUS_ALERT_FIRED:
			$status_color = COL_ALERTFIRED;
			break;
		# Juanma (05/05/2014) Fix: Correct color for not init agents!
		case AGENT_STATUS_NOT_INIT:
			$status_color = COL_NOTINIT;
			break;
		default:
			//Unknown monitor
			$status_color = COL_UNKNOWN;
			break;
	}
	
	// Short name
	$name = io_safe_output($agent["nombre"]);
	if ((strlen ($name) > 16) && ($cut_names)) {
		$name = ui_print_truncate_text($name, 16, false, true, false);
	}
	
	if ($simple == 0) {
		if ($hack_networkmap_mobile) {
			$img_node = ui_print_os_icon($agent['id_os'], false, true, true, true, true, true);
			
			$img_node = $config['homedir'] . '/' . $img_node;
			$img_node = '<img src="' . $img_node . '" />';
		}
		else {
			// Set node icon
			$img_node = ui_print_os_icon ($agent['id_os'], false, true, true, true, true, $relative);
			$img_node = str_replace($config['homeurl'] . '/', '', $img_node);
			$img_node = str_replace($config['homeurl'], '', $img_node);
		
			if (defined('METACONSOLE')) {
				$img_node = str_replace('../../', '', $img_node);
			}
		
			if ($relative) {
				$img_node = html_print_image($img_node, true, false, false, true);
			}
			else {
				$img_node = html_print_image($img_node, true, false, false, false);
			}
		}
		
		if (defined("METACONSOLE")) {
			if (can_user_access_node ()) {
				$url = ui_meta_get_url_console_child($id_server,
					"estado", "operation/agentes/ver_agente&id_agente=" . $agent['id_agente']);
			}
			else {
				$url = '';
			}
			$url_tooltip = '../../ajax.php?' .
				'page=operation/agentes/ver_agente&' .
				'get_agent_status_tooltip=1&' .
				'id_agent='.$agent['id_agente'] . '&' .
				'metaconsole=1&' .
				'id_server=' . $agent['id_server'];
		}
		else {
			$url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'];
			$url_tooltip = 'ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'];
		}
		
		$node = "\n" . $agent['id_node'].' [ parent="' . $agent['id_parent'] . '", color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.40, height=0.40, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>' . $img_node . '</TD></TR>
		 <TR><TD>'.io_safe_output($name).'</TD></TR></TABLE>>,
		 shape="doublecircle", URL="'.$url.'",
		 tooltip="' . $url_tooltip . '"];' . "\n";
	}
	else {
		$ajax_prefix = '';
		$meta_params = '';
		
		if (defined('METACONSOLE')) {
			$ajax_prefix = '../../';
			$meta_params = '&metaconsole=1&id_server=' . $id_server;
		}
		
		if (can_user_access_node ()) {
			$url_node_link = ', URL="' . $console_url . 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $agent['id_agente'] . $url_hash . '"';
		}
		else {
			$url_node_link = '';
		}
		$node = $agent['id_node'] . ' [ parent="' . $agent['id_parent'] . '", color="' . $status_color . '", fontsize=' . $font_size . ', shape="doublecircle"' . $url_node_link . ', style="filled", fixedsize=true, width=0.20, height=0.20, label="", tooltip="' . $ajax_prefix . 'ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent=' . $agent['id_agente'] . $meta_params . '"];' . "\n";
	}
	
	return $node;
}

function networkmap_create_module_group_node ($module_group, $simple = 0, $font_size = 10, $metaconsole = false, $id_server = null) {
	global $config;
	global $hack_networkmap_mobile;
	
	
	// Set node status
	switch ($module_group['status']) {
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
	
	if ($simple == 0) {
		if (defined("METACONSOLE")) {
			$url = '';
			$url_tooltip = '';
		}
		else {
			$url = '';
			$url_tooltip = '';
		}
		
		$node = $module_group['id_node'].' [ color="' . $status_color .
			'", fontsize='.$font_size.', style="filled", ' .
			'fixedsize=true, width=0.30, height=0.30, ' .
			'label=<<TABLE data-id_agent="' . $module_group['id_agent'] . '" data-status="' . $module_group['status'] . '" CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>' .
			io_safe_output($module_group['name']) . '</TD></TR></TABLE>>,
			shape="square", URL="' . $url . '",
			tooltip="' . $url_tooltip . '"];';
	}
	else {
		if ($hack_networkmap_mobile) {
			$img_node = ui_print_moduletype_icon($module['id_tipo_modulo'], true, true, false, true);
		
			$img_node = $config['homedir'] . '/' . $img_node;
			$img_node = '<img src="' . $img_node . '" />';
		}
		else {
			$img_node = ui_print_moduletype_icon ($module['id_tipo_modulo'], true, true, false);
		}
		if (defined("METACONSOLE")) {
			$url = '';
			$url_tooltip = '';
		}
		else {
			$url = '';
			$url_tooltip = '';
		}
		
		$node = $module_group['id_node'] . ' [ color="'.$status_color .
			'", fontsize='.$font_size.', shape="square", URL="' . $url . '", ' .
			'style="filled", fixedsize=true, width=0.20, ' .
			'height=0.20, label="", tooltip="' . $url_tooltip . '"];';
	}
	return $node;
}

// Returns a module node definition
function networkmap_create_module_node ($module, $simple = 0, $font_size = 10, $metaconsole = false, $id_server = null) {
	global $config;
	global $hack_networkmap_mobile;
	
	if (isset($module['status'])) {
		$status = $module['status'];
	}
	else {
		$status = modules_get_agentmodule_status($module['id_agente_modulo'],
			false, $metaconsole, $id_server);
	}
	
	// Set node status
	switch ($status) {
		case AGENT_MODULE_STATUS_NORMAL:
			$status_color = COL_NORMAL; // Normal monitor
			break;
		case AGENT_MODULE_STATUS_CRITICAL_BAD:
			$status_color = COL_CRITICAL; // Critical monitor
			break;
		case AGENT_MODULE_STATUS_WARNING:
			$status_color = COL_WARNING; // Warning monitor
			break;
		case AGENT_STATUS_ALERT_FIRED:
			$status_color = COL_ALERTFIRED; // Alert fired
			break;
		default:
			$status_color = COL_UNKNOWN; // Unknown monitor
			break;
	}
	
	if ($hack_networkmap_mobile) {
		$img_node = ui_print_moduletype_icon($module['id_tipo_modulo'], true, true, false, true);
		
		$img_node = $config['homedir'] . '/' . $img_node;
		$img_node = '<img src="' . $img_node . '" />';
	}
	else {
		$img_node = ui_print_moduletype_icon ($module['id_tipo_modulo'], true, true, false);
	}
	
	
	if ($simple == 0) {
		if (defined("METACONSOLE")) {
			$url = '';
			$url_tooltip = '../../ajax.php?' .
				'page=operation/agentes/ver_agente&' .
				'get_agentmodule_status_tooltip=1&' .
				'id_module='.$module['id_agente_modulo'] .
				'&metaconsole=1' .
				'&id_server=' . $module['id_server'];
		}
		else {
			$url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$module['id_agente'];
			$url_tooltip = 'ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'];
		}
		
		$node = $module['id_node'].' [ id_agent="' . $module['id_agente'] . '", color="' . $status_color .
			'", fontsize='.$font_size.', style="filled", ' .
			'fixedsize=true, width=0.30, height=0.30, ' .
			'label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>' .
			$img_node . '</TD></TR>
			<TR><TD>' . io_safe_output($module['nombre']) . '</TD></TR></TABLE>>,
			shape="circle", URL="' . $url . '",
			tooltip="' . $url_tooltip . '"];';
	}
	else {
		if (defined("METACONSOLE")) {
			$url = 'TODO';
			$url_tooltip = '../../ajax.php?page=operation/agentes/ver_agente' .
				'&get_agentmodule_status_tooltip=1' .
				'&id_module=' . $module['id_agente_modulo'] .
				'&metaconsole=1' .
				'&id_server=' . $module['id_server'];
		}
		else {
			$url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$module['id_agente'];
			$url_tooltip = 'ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'];
		}
		
		$node = $module['id_node'] . ' [ ' .
			'id_agent="' . $module['id_agente'] . '", ' .
			'color="' . $status_color .'", ' .
			'fontsize='.$font_size.', ' .
			'shape="circle", ' .
			'URL="' . $url . '", ' .
			'style="filled", ' .
			'fixedsize=true, ' .
			'width=0.20, ' .
			'height=0.20, ' .
			'label="", ' .
			'tooltip="' . $url_tooltip . '"' .
			'];';
	}
	return $node;
}

// Returns the definition of the central module
function networkmap_create_pandora_node ($name, $font_size = 10, $simple = 0, $stats = array()) {
	global $hack_networkmap_mobile;
	global $config;
	
	//$stats_json = base64_encode(json_encode($stats));
	$summary = array();
	if (isset($stats['policies'])) {
			$summary['policies'] = count($stats['policies']);
	}
	if (isset($stats['groups'])) {
		// TODO: GET STATUS OF THE GROUPS AND ADD IT TO SUMMARY
		$summary['groups'] = count($stats['groups']);
	}
	if (isset($stats['agents'])) {
		// TODO: GET STATUS OF THE AGENTS AND ADD IT TO SUMMARY
		$summary['agents'] = count($stats['agents']);
	}
	if (isset($stats['modules'])) {
		// TODO: GET STATUS OF THE MODULES AND ADD IT TO SUMMARY
		$summary['modules'] = count($stats['modules']) ;
	}
	$stats_json = base64_encode(json_encode($summary));
	
	$img_src = "images/networkmap/bola_pandora_network_maps.png";
	if (defined('METACONSOLE')) {
		
		$url_tooltip = '../../ajax.php?' .
			'page=include/ajax/networkmap.ajax&' .
			'action=get_networkmap_summary&' .
			'stats='.$stats_json . '&' .
			'metaconsole=1';
		$url = '';
		$color = '#052938';
	}
	else {
		$url_tooltip = 'ajax.php?page=include/ajax/networkmap.ajax&action=get_networkmap_summary&stats='.$stats_json.'", URL="index.php?sec=estado&sec2=operation/agentes/group_view';
		$url = 'index.php?sec=estado&sec2=operation/agentes/group_view';
		$color = '#373737';
	}
	
	if ($hack_networkmap_mobile) {
		$img = '<TR><TD>' .
			"<img src='" . $config['homedir'] . '/' . "images/networkmap/bola_pandora_network_maps.png' />" .
			'</TD></TR>';
	}
	else {
		$image = html_print_image("images/networkmap/bola_pandora_network_maps.png", true, false, false, true);
		//$image = str_replace('"',"'",$image);
		$img = '<TR><TD>' . $image . '</TD></TR>';
	}
	$name = "<TR><TD BGCOLOR='#FFFFFF'>" . $name . '</TD></TR>';
	$label = "<TABLE BORDER='0'>" . $img.$name . '</TABLE>';
	if ($simple == 1) {
		$label = '';
	}
	
	$node = '0 [ color="' . $color . '", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.8, height=0.6, label=<'.$label.'>,
		shape="ellipse", tooltip="' . $url_tooltip . '", URL="' . $url . '" ];';
	
	return $node;
}

function networkmap_create_transparent_node($count = 0) {
	
	$node = 'transp_' .$count  . ' [ color="#00000000", style="filled", fixedsize=true, width=0.8, height=0.6, label=<>,
		shape="ellipse"];';
	
	return $node;
}

// Opens a group definition
function networkmap_open_group ($id) {
	$img = 'images/'.groups_get_icon ($id).'.png';
	$name = groups_get_name ($id);
	
	$group = 'subgraph cluster_' . $id . 
		' { style=filled; color=darkolivegreen3; label=<<TABLE BORDER=\'0\'>
		<TR><TD>' . html_print_image($img, true) . '</TD><TD>'.$name.'</TD></TR>
		</TABLE>>; tooltip="'.$name.'";
		URL="index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id='
		. $id . '";';
	
	return $group;
}

// Closes a group definition
function networkmap_close_group () {
	return '}';
}

// Opens a graph definition
function networkmap_open_graph ($layout, $nooverlap, $pure, $zoom,
	$ranksep, $font_size, $size_canvas) {
	
	global $config;
	
	$overlap = 'compress';
	
	if (isset($config['networkmap_max_width'])) {
		$size_x = $config['networkmap_max_width'] / 100;
		$size_y = $size_x * 0.8;
	}
	else {
		$size_x = 8;
		$size_y = 5.4;
		$size = '';
	}
	
	
	if ($layout == 'radial') {
		$overlap = 'true';
	}
	
	if ($layout == 'flat' || $layout == 'radial' || $layout == 'spring1' || $layout == "spring2") {
		if ($nooverlap != '') {
			$overlap = 'scalexy';
		}
	}
	
	if ($zoom > 0) {
		$size_x *= $zoom;
		$size_y *= $zoom;
	}
	$size = $size_x . ',' . $size_y;
	
	if (!is_null($size_canvas)) {
		$size = ($size_canvas['x'] / 100) . "," . ($size_canvas['y'] / 100);
	}
	
	// BEWARE: graphwiz DONT use single ('), you need double (")
	$head = "graph networkmap { dpi=100; bgcolor=\"transparent\"; labeljust=l; margin=0; pad=\"0.75,0.75\";";
	if ($nooverlap != '') {
		$head .= "overlap=\"$overlap\";";
		$head .= "ranksep=\"$ranksep\";";
		$head .= "outputorder=edgesfirst;";
	}
	
	$head .= "ratio=fill;";
	$head .= "root=0;";
	$head .= "nodesep=\"0.02\";";
	$head .= "size=\"$size\";";
	
	$head .= "\n";
	
	return $head;
}

// Closes a graph definition
function networkmap_close_graph () {
	return '}';
}

// Returns the filter used to achieve the desired layout
function networkmap_get_filter ($layout) {
	switch ($layout) {
		case 'flat':
			return 'dot';
			break;
		case 'radial':
			return 'twopi';
			break;
		case 'circular':
			return 'circo';
			break;
		case 'spring1':
			return 'neato';
			break;
		case 'spring2':
			return 'fdp';
			break;
		default:
			return 'twopi';
			break;
	}
}

/**
 * Creates a networkmap.
 *
 * @param string Network map name.
 * @param string Network map type (topology, groups or policies).
 * @param layout Network map layout (circular, flat, radial, spring1 or spring2).
 * @param bool overlapping activate flag.
 * @param bool simple view activate flag.
 * @param bool regenerate file activate flag.
 * @param int font size.
 * @param int group id filter (0 for all).
 * @param int module group id filter (0 for all).
 * @param int policy id filter (0 for all).
 * @param string depth level.
 * @param bool only modules with alerts flag.
 * @param bool hide policy modules flag
 * @param float zoom factor
 * 
 * @return mixed New networkmap id if created. False if it could not be created.
 */
function networkmap_create_networkmap ($values) {
	global $config;
	
	// The name is required
	if (! isset($values['name']))
		return false;
	
	// Set defaults for the empty values
	set_unless_defined ($values['type'], 'topology');
	set_unless_defined ($values['layout'], 'radial');
	set_unless_defined ($values['nooverlap'], true);
	set_unless_defined ($values['simple'], false);
	set_unless_defined ($values['regenerate'], true);
	set_unless_defined ($values['font_size'], 12);
	set_unless_defined ($values['store_group'], 0);
	set_unless_defined ($values['id_group'], 0);
	set_unless_defined ($values['regenerate'], true);
	set_unless_defined ($values['id_module_group'], 0);
	set_unless_defined ($values['depth'], 'all');
	set_unless_defined ($values['only_modules_with_alerts'], false);
	set_unless_defined ($values['hide_policy_modules'], false);
	set_unless_defined ($values['zoom'], 1);
	set_unless_defined ($values['distance_nodes'], 2.5);
	set_unless_defined ($values['center'], 0);
	set_unless_defined ($values['id_user'], $config['id_user']);
	set_unless_defined ($values['text_filter'], '');
	set_unless_defined ($values['regenerate'], true);
	set_unless_defined ($values['dont_show_subgroups'], 0);
	set_unless_defined ($values['show_groups'], false);
	set_unless_defined ($values['pandoras_children'], false);
	set_unless_defined ($values['show_modules'], false);
	set_unless_defined ($values['show_snmp_modules'], 0);
	set_unless_defined ($values['l2_network'], 0);
	set_unless_defined ($values['server_name'], '');
	
	return @db_process_sql_insert('tnetwork_map', $values);
}

/**
 * Get a network map report.
 *
 * @param int Networkmap id to get.
 * @param array Extra filter.
 * @param array Fields to get.
 * @param bool Get only the map if is of the user ($config['id_user'])
 *
 * @return Networkmap with the given id. False if not available or readable.
 */
function networkmap_get_networkmap ($id_networkmap, $filter = false, $fields = false, $check_user = true) {
	global $config;
	
	$id_networkmap = safe_int ($id_networkmap);
	if (empty ($id_networkmap))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	
	$filter['id_networkmap'] = $id_networkmap;
	
	if ($check_user) {
		//If hte user has admin flag don't filter by user	
		$user_info = users_get_user_by_id($config['id_user']);
		
		if (!$user_info['is_admin']) {
			//$filter['id_user'] = $config['id_user'];
		}
	}
	
	$networkmap = db_get_row_filter ('tnetwork_map', $filter, $fields);
	
	return $networkmap;
}

/**
 * Get a user networkmaps.
 *
 * @param int Networkmap id to get.
 * @param array Extra filter.
 * @param array Fields to get.
 *
 * @return Networkmap with the given id. False if not available or readable.
 */
function networkmap_get_networkmaps ($id_user = null, $type = null,
	$optgrouped = true, $strict_user = false) {
	
	global $config;
	
	if (empty($id_user)) {
		$id_user = $config['id_user'];
	}
	
	// Configure filters
	$where = array ();
	$where['type'] = MAP_TYPE_NETWORKMAP;
	$where['id_group'] = array_keys (users_get_groups($id_user));
	if (!empty($type)) {
		$where['subtype'] = $type;
	}
	
	$where['order'][0]['field'] = 'type';
	$where['order'][0]['order'] = 'DESC';
	$where['order'][1]['field'] = 'name';
	$where['order'][1]['order'] = 'ASC';
	
	$networkmaps_raw =  db_get_all_rows_filter('tmap', $where);
	if (empty($networkmaps_raw)) {
		return array();
	}
	
	$networkmaps = array();
	foreach ($networkmaps_raw as $networkmapitem) {
		if ($optgrouped) {
			$networkmaps[$networkmapitem['id']] = 
				array('name' => $networkmapitem['name'], 
					'optgroup' =>
						networkmap_type_to_str_type($networkmapitem['subtype']));
		}
		else {
			$networkmaps[$networkmapitem['id']] =
				$networkmapitem['name'];
		}
	}
	
	return $networkmaps;
}

function networkmap_type_to_str_type($type) {
	switch ($type) {
		case MAP_SUBTYPE_GROUPS:
			return __("Groups");
			break;
		case MAP_SUBTYPE_POLICIES:
			return __("Policies");
			break;
		case MAP_SUBTYPE_RADIAL_DYNAMIC:
			return __("Radial dynamic");
			break;
		case MAP_SUBTYPE_TOPOLOGY:
			return __("Topology");
			break;
	}
}

/**
 * Deletes a network map if the property is that user.
 * 
 * @param string User id that call this funtion. 
 * @param int Map id to be deleted.
 *
 * @return bool True if the map was deleted, false the map is not yours.
 */
function networkmap_delete_user_networkmap ($id_user = '', $id_networkmap) {
	if ($id_user == '') {
		$id_user = $config['id_user'];
	}
	$id_networkmap = safe_int ($id_networkmap);
	if (empty ($id_networkmap))
		return false;
	$networkmap = networkmap_get_networkmap ($id_networkmap);
	if ($networkmap === false)
		return false;
	return @db_process_sql_delete ('tnetwork_map', array ('id_networkmap' => $id_networkmap, 'id_user' => $id_user));
}

/**
 * Updates a network map.
 *
 * @param int Map id.
 * @param array Extra values to be set.
 * 
 * @return bool True if the map was updated. False otherwise.
 */
function networkmap_update_networkmap ($id_networkmap, $values) {
	$networkmap = networkmap_get_networkmap ($id_networkmap);
	if ($networkmap === false)
		return false;
	return (db_process_sql_update ('tnetwork_map',
		$values,
		array ('id_networkmap' => $id_networkmap))) !== false;
}

/**
 * Get different networkmaps types for creation.
 * 
 * @return Array Networkmap diferent types.
 */
function networkmap_get_types ($strict_user = false) {
	$networkmap_types = array();
	
	$is_enterprise = enterprise_include_once('include/functions_policies.php');
	
	$networkmap_types['topology'] = __('Create a new topology map');
	$networkmap_types['groups'] = __('Create a new group map');
	$networkmap_types['dynamic'] = __('Create a new dynamic map');
	if (!$strict_user) {
		$networkmap_types['radial_dynamic'] = __('Create a new radial dynamic map');
	}
	
	if (($is_enterprise !== ENTERPRISE_NOT_HOOK) && (!$strict_user)) {
		$enterprise_types = enterprise_hook('policies_get_networkmap_types');
		
		$networkmap_types = array_merge($networkmap_types, $enterprise_types);
	}
	
	return $networkmap_types;
}

/**
 * Get networkmaps types.
 * 
 * @return Array Networkmap diferent types.
 */
function networkmap_get_filter_types ($strict_user = false) {
	$networkmap_types = array();
	
	$is_enterprise = enterprise_include_once('include/functions_policies.php');
	
	$networkmap_types['topology'] = __('Topology');
	$networkmap_types['groups'] = __('Group');
	$networkmap_types['dynamic'] = __('Dynamic');
	if (!$strict_user) {
		$networkmap_types['radial_dynamic'] = __('Radial dynamic');
	}
	
	if (($is_enterprise !== ENTERPRISE_NOT_HOOK) && (!$strict_user)) {
		$enterprise_types = enterprise_hook('policies_get_networkmap_filter_types');
		
		$networkmap_types = array_merge($networkmap_types, $enterprise_types);
	}
	
	return $networkmap_types;
}

function networkmap_cidr_match($ip, $cidr_mask) {
	//copy from open source code
	// https://gist.github.com/linickx/1309388
	
	$chunks = explode("/", $cidr_mask);
	$subnet = $chunks[0];
	$bits = $chunks[1];
	
	$ip = ip2long($ip);
	$subnet = ip2long($subnet);
	$mask = -1 << (32 - $bits);
	$subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
	
	return ($ip & $mask) == $subnet;
}

function networkmap_get_new_nodes_from_ip_mask($ip_mask,
	$fields = array(), $strict_user = false) {
	
	$list_ip_masks = explode(",", $ip_mask);
	
	$list_address = db_get_all_rows_in_table('taddress');
	if (empty($address))
		$address = array();
	
	if ($strict_user) {
		$filter['group_by'] = 'tagente.id_agente';
		$fields = array ('tagente.id_agente');
		$acltags = tags_get_user_module_and_tags ($config['id_user'],'AR', $strict_user);
		$user_agents = tags_get_all_user_agents (false, $config['id_user'], $acltags, $filter, $fields, false, $strict_user, true);
		
		foreach ($all_user_agents as $agent) {
			$user_agents[$agent['id_agente']] = $agent['id_agente'];
		}
	}
	
	$agents = array();
	foreach ($list_address as $address) {
		foreach ($list_ip_masks as $ip_mask) {
			if (networkmap_cidr_match($address['ip'], $ip_mask)) {
				$id_agent = db_get_value_filter('id_agent', 'taddress_agent',
					array('id_a' => $address['id_a']));
				
				if (empty($id_agent)) {
					continue;
				}
				
				if (empty($fields)) {
					if ($strict_user) {
						if (array_key_exists($id_agent, $user_agents)) {
							$agents[] = db_get_value_filter('id_agent', 'taddress_agent', array('id_a' => $address['id_a']));
						}
					}
					else {
						$agents[] = db_get_value_filter('id_agent', 'taddress_agent', array('id_a' => $address['id_a']));
					}
				
				}
				else {
					if ($strict_user) {
						if (array_key_exists($id_agent, $user_agents)) {
							$agents[] = db_get_row('tagente', 'id_agente', $id_agent, $fields);
						}
					}
					else {
						$agents[] = db_get_row('tagente', 'id_agente', $id_agent, $fields);
					}
				}
			}
		}
	}
	
	return $agents;
}

?>
<script language="javascript" type="text/javascript">
	$(document).ready (function () {
		// TODO: Implement the jquery tooltip functionality everywhere
		// and remove the cluetip code.
		$("area[title!='<?php echo 'Pandora FMS'; ?>']")
			.each(function (index, element) {
				// Store the title.
				// The title stores the url into a data property
				$(element).data('uri', $(element).prop('title'));
			})
			.tooltip({
				track: true,
				content: '<?php html_print_image("images/spinner.gif"); ?>',
				open: function (evt, ui) {
					var elem = $(this);
					var uri = elem.data('uri');
					
					if (typeof uri != 'undefined' && uri.length > 0) {
						var jqXHR = $.ajax(uri).done(function(data) {
							elem.tooltip('option', 'content', data);
						});
						// Store the connection handler
						elem.data('jqXHR', jqXHR);
					}
					
					$(".ui-tooltip>.ui-tooltip-content:not(.cluetip-default)")
						.addClass("cluetip-default");
				},
				close: function (evt, ui) {
					var elem = $(this);
					var jqXHR = elem.data('jqXHR');
					
					// Close the connection handler
					if (typeof jqXHR != 'undefined')
						jqXHR.abort();
				}
			});
	});
</script>
