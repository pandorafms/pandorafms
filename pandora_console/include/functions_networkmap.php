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
ui_require_jquery_file ('cluetip');

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

// Generate a dot graph definition for graphviz
function networkmap_generate_dot ($pandora_name, $group = 0,
	$simple = 0, $font_size = 12, $layout = 'radial', $nooverlap = 0,
	$zoom = 1, $ranksep = 2.5, $center = 0, $regen = 1, $pure = 0,
	$id_networkmap = 0, $show_snmp_modules = 0, $cut_names = true,
	$relative = false) {
	
	$parents = array();
	$orphans = array();
	
	$filter = array ();
	$filter['disabled'] = 0;
	if ($group >= 1) {
		$filter['id_grupo'] = $group;
		
		$agents = agents_get_agents ($filter,
			array ('id_grupo, nombre, id_os, id_parent, id_agente'));
	}
	else if ($group == -666) {
		$agents = false;
	}
	else {
		$agents = agents_get_agents ($filter,
			array ('id_grupo, nombre, id_os, id_parent, id_agente'));
	}
	
	if ($agents === false)
		//return false;
		$agents = array();
	
	// Open Graph
	$graph = networkmap_open_graph ($layout, $nooverlap, $pure, $zoom, $ranksep, $font_size);
	
	// Parse agents
	$nodes = array ();
	$node_count = 1;
	
	// Add node
	$node_ref = array();
	foreach ($agents as $agent) {
		// Add node
		$node_ref[$agent['id_agente']] = $node_count;
		$node_count++;
	}
	
	$node_count = 1;
	
	foreach ($agents as $agent) {
		// Save node parent information to define edges later
		if ($agent['id_parent'] != "0" && array_key_exists($agent['id_parent'], $node_ref)) {
			$parents[$node_count] = $node_ref[$agent['id_parent']];
		}
		else {
			$orphans[$node_count] = 1;
		}
		
		$agent['id_node'] = $node_count;
		
		$agent['type'] = 'agent';
		
		// Add node
		$nodes[$node_count] = $agent;
		
		if ($show_snmp_modules) {
			// Get agent modules data of snmp_proc type
			$modules = agents_get_modules ($agent['id_agente'], false, array('disabled' => 0, 'id_tipo_modulo' => 18), true, false);
			// Parse modules
			foreach ($modules as $key => $module) {
				$node_count ++;
				$agent_module = modules_get_agentmodule($key);
				
				$alerts_module = db_get_sql('SELECT count(*) AS num
					FROM talert_template_modules
					WHERE id_agent_module = '.$key);
				
				// Save node parent information to define edges later
				$parents[$node_count] = $agent_module['parent'] = $agent['id_node'];
				
				$agent_module['id_node'] = $node_count;
				
				$agent_module['type'] = 'module';
				// Add node
				$nodes[$node_count] = $agent_module;
			}
		}
		$node_count++;
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
		
		switch($node['type']){
			case 'agent':
				$graph .= networkmap_create_agent_node ($node , $simple, $font_size, $cut_names, $relative)."\n\t\t";
				$stats['agents'][] = $node['id_agente'];
				break;
			case 'module':
				$graph .= networkmap_create_module_node ($node , $simple, $font_size)."\n\t\t";
				$stats['modules'][] = $node['id_agente_modulo'];
				break;
		}
	}
	
	// Define edges
	foreach ($parents as $node => $parent_id) {
		// Verify that the parent is in the graph
		if (isset ($nodes[$parent_id])) {
			$graph .= networkmap_create_edge ($node, $parent_id, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, 'operation/agentes/networkmap', 'topology', $id_networkmap);
		}
		else {
			$orphans[$node] = 1;
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
	
	// Close graph
	$graph .= networkmap_close_graph ();
	
	return $graph;
}

// Generate a dot graph definition for graphviz with groups
function networkmap_generate_dot_groups ($pandora_name, $group = 0, $simple = 0, $font_size = 12, $layout = 'radial', $nooverlap = 0, $zoom = 1, $ranksep = 2.5, $center = 0, $regen = 1, $pure = 0, $modwithalerts = 0, $module_group = 0, $hidepolicymodules = 0, $depth = 'all', $id_networkmap = 0) {
	global $config;
	
	$parents = array();
	$orphans = array();
	
	$filter = array ();
	$filter['disabled'] = 0;
	
	// Get groups data
	if ($group > 0) {
		$groups = array();
		$id_groups = groups_get_id_recursive($group, true);
		
		foreach($id_groups as $id_group) {
			if(check_acl($config["id_user"], $id_group, 'AR')) {
				$groups[] = db_get_row ('tgrupo', 'id_grupo', $id_group);
			}
		}
				
		$filter['id_grupo'] = $id_groups;
	}
	else {
		$groups = db_get_all_rows_in_table ('tgrupo');
		if($groups === false) {
			$groups = array();
		}
	}
	
	// Open Graph
	$graph = networkmap_open_graph ($layout, $nooverlap, $pure, $zoom, $ranksep, $font_size);
	
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
	
	foreach ($nodes_groups as $node_group) {
		
		$node_count++;
		
		// Save node parent information to define edges later
		if ($node_group['parent'] != "0" && $node_group['id_grupo'] != $group) {
			$parents[$node_count] = $nodes_groups[$node_group['parent']]['id_node'];
		} else {
			$orphans[$node_count] = 1;
		}
		
		$nodes[$node_count] = $node_group;	
	}
	
	if($depth != 'group') {
		// Get agents data
		$agents = agents_get_agents ($filter,
			array ('id_grupo, nombre, id_os, id_agente'));
			
		if ($agents === false)
			$agents = array();
		
		// Parse agents
		$nodes_agents = array();
		foreach ($agents as $agent) {
			
			// If only agents with alerts => agents without alerts discarded
			$alert_agent = agents_get_alerts($agent['id_agente']); 
			
			if ($modwithalerts and empty($alert_agent['simple']) and empty($alert_agent['compounds'])){
				continue;
			}
			
			$node_count ++;
			// Save node parent information to define edges later
			$parents[$node_count] = $agent['parent'] = $nodes_groups[$agent['id_grupo']]['id_node'];
		
			$agent['id_node'] = $node_count;
			$agent['type'] = 'agent';
			// Add node
			$nodes[$node_count] = $nodes_agents[$agent['id_agente']] = $agent;
			
			if($depth == 'agent'){
				continue;
			}
			
			// Get agent modules data
			$modules = agents_get_modules ($agent['id_agente'], false, array('disabled' => 0), true, false);
			// Parse modules
			foreach ($modules as $key => $module) {
				$node_count ++;
				$agent_module = modules_get_agentmodule($key);
				$alerts_module = db_get_sql('SELECT count(*) as num
					FROM talert_template_modules
					WHERE id_agent_module = '.$key);
				
				if ($alerts_module == 0 && $modwithalerts) {
					continue;
				}
				
				if ($agent_module['id_module_group'] != $module_group && $module_group != 0) {
					continue;
				}
				
				if ($hidepolicymodules && $config['enterprise_installed']) {
					enterprise_include_once('include/functions_policies.php');
					if(policies_is_module_in_policy($key)) {
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
				$graph .= networkmap_create_group_node ($node , $simple, $font_size) .
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
		} else {
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
	
	// edgeURL allows node navigation
	$edge = $head.' -- '.$tail.'[color="#BDBDBD", headclip=false, tailclip=false,
	edgeURL="index.php?sec=estado&sec2='.$sec2.'&tab='.$tab.'&recenter_networkmap=1&center='.$head.
	'&layout='.$layout.'&nooverlap=' .$nooverlap.'&pure='.$pure.
	'&zoom='.$zoom.'&ranksep='.$ranksep.'&simple='.$simple.'&regen=1'.
	'&font_size='.$font_size.'&group='.$group.'&id_networkmap='.$id_networkmap.'"];';
	
	return $edge;
}

// Returns a group node definition
function networkmap_create_group_node ($group, $simple = 0, $font_size = 10) {
	$status = groups_get_status ($group['id_grupo']);
	
	// Set node status
	switch($status) {
		case 0: 
			$status_color = '#8DFF1D'; // Normal monitor
			break;
		case 1:
			$status_color = '#FF1D1D'; // Critical monitor
			break;
		case 2:
			$status_color = '#FFE308'; // Warning monitor
			break;
		case 4:
			$status_color = '#FFA300'; // Alert fired
			break;
		default:
			$status_color = '#BBBBBB'; // Unknown monitor
			break;
	}
	
	$icon = groups_get_icon($group['id_grupo']);
	
	if ($simple == 0){
		// Set node icon
		if (file_exists (html_print_image("images/groups_small/" . $icon . ".png", true, false, true, true))) { 
			$img_node = html_print_image("images/groups_small/" . $icon . ".png", true, false, false, true);
		}
		else {
			$img_node = '-';
		}
		
		if (strlen(groups_get_name($group['id_grupo'])) > 40){
			$name = substr(groups_get_name($group['id_grupo']), 0, 40) . '...';
		}
		else{
			$name = groups_get_name($group['id_grupo']);
		}
		
		$node = $group['id_node'].' [ color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.30, height=0.30, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>'.$img_node.'</TD></TR>
			<TR><TD>'.io_safe_output($name).'</TD></TR></TABLE>>,
			shape="invtrapezium", URL="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$group['id_grupo'].'",
			tooltip="ajax.php?page=operation/agentes/ver_agente&get_group_status_tooltip=1&id_group='.$group['id_grupo'].'"];';
	}
	else {
		$node = $group['id_node'] . ' [ color="'.$status_color.'", fontsize='.$font_size.', shape="invtrapezium", URL="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$group['id_grupo'].'", style="filled", fixedsize=true, width=0.20, height=0.20, label="", tooltip="ajax.php?page=operation/agentes/ver_agente&get_group_status_tooltip=1&id_group='.$group['id_grupo'].'"];';
	}
	return $node;
}

// Returns a node definition
function networkmap_create_agent_node ($agent, $simple = 0, $font_size = 10, $cut_names = true, $relative = false) {
	global $config;
	
	$status = agents_get_status($agent['id_agente']);
	
	// Set node status
	switch($status) {
		case 0: 
			$status_color = '#8DFF1D'; // Normal monitor
			break;
		case 1:
			$status_color = '#FF1D1D'; // Critical monitor
			break;
		case 2:
			$status_color = '#FFE308'; // Warning monitor
			break;
		case 4:
			$status_color = '#FFA300'; // Alert fired
			break;
		default:
			$status_color = '#BBBBBB'; // Unknown monitor
			break;
	}
	
	// Short name
	$name = io_safe_output($agent["nombre"]);
	if ((strlen ($name) > 16) && ($cut_names)) {
		$name = ui_print_truncate_text($name, 16, false, true, false);
	}
	
	if ($simple == 0){
		// Set node icon
		$img_node = ui_print_os_icon ($agent['id_os'], false, true, true, true, true, $relative);
		$img_node = str_replace($config['homeurl'] . '/', '', $img_node);
		
		if ($relative) {
			$img_node = html_print_image($img_node, true, false, false, true);
		}
		else {
			$img_node = html_print_image($img_node, true, false, false, false);
		}
		
		$node = $agent['id_node'].' [ color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.40, height=0.40, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>' . $img_node . '</TD></TR>
			<TR><TD>'.io_safe_output($name).'</TD></TR></TABLE>>,
			shape="doublecircle", URL="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'",
			tooltip="ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'].'"];';
	}
	else {
		$node = $agent['id_node'] . ' [ color="' . $status_color . '", fontsize='.$font_size.', shape="doublecircle", URL="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'",style="filled", fixedsize=true, width=0.20, height=0.20, label="", tooltip="ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'].'"];';
	}
	
	return $node;
}

// Returns a module node definition
function networkmap_create_module_node ($module, $simple = 0, $font_size = 10) {
	$status = modules_get_agentmodule_status($module['id_agente_modulo']);
	
	// Set node status
	switch($status) {
		case 0:
			$status_color = '#8DFF1D'; // Normal monitor
			break;
		case 1:
			$status_color = '#FF1D1D'; // Critical monitor
			break;
		case 2:
			$status_color = '#FFE308'; // Warning monitor
			break;
		case 4:
			$status_color = '#FFA300'; // Alert fired
			break;
		default:
			$status_color = '#BBBBBB'; // Unknown monitor
			break;
	}
	
	
	if ($simple == 0){
		$node = $module['id_node'].' [ color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.30, height=0.30, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>' . ui_print_moduletype_icon ($module['id_tipo_modulo'], true, true, false). '</TD></TR>
		 <TR><TD>'.io_safe_output($module['nombre']).'</TD></TR></TABLE>>,
		 shape="circle", URL="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$module['id_agente'].'",
		 tooltip="ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'].'"];';
	}
	else {
		$node = $module['id_node'] . ' [ color="'.$status_color.'", fontsize='.$font_size.', shape="circle", URL="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$module['id_agente'].'", style="filled", fixedsize=true, width=0.20, height=0.20, label="", tooltip="ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'].'"];';
	}
	return $node;
}

// Returns the definition of the central module
function networkmap_create_pandora_node ($name, $font_size = 10, $simple = 0, $stats = array()) {
	$img = '<TR><TD>' . html_print_image("images/networkmap/pandora_node.png", true, false, false, true) . '</TD></TR>';
	$name = '<TR><TD BGCOLOR="#FFFFFF">'.$name.'</TD></TR>';
	$label = '<TABLE BORDER="0">'.$img.$name.'</TABLE>';
	if ($simple == 1){
		$label = '';
	}
	
	$stats_json = base64_encode(json_encode($stats));
	
	$node = '0 [ color="#364D1F", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.8, height=0.6, label=<'.$label.'>,
		shape="ellipse", tooltip="ajax.php?page=include/ajax/networkmap.ajax&action=get_networkmap_summary&stats='.$stats_json.'", URL="index.php?sec=estado&sec2=operation/agentes/group_view" ];';
	
	return $node;
}

// Opens a group definition
function networkmap_open_group ($id) {
	$img = 'images/'.groups_get_icon ($id).'.png';
	$name = groups_get_name ($id);
	
	$group = 'subgraph cluster_' . $id . 
		' { style=filled; color=darkolivegreen3; label=<<TABLE BORDER="0">
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
function networkmap_open_graph ($layout, $nooverlap, $pure, $zoom, $ranksep, $font_size) {
	$overlap = 'compress';
	$size_x = 8;
	$size_y = 5.4;
	$size = '';
	
	
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
	
	// BEWARE: graphwiz DONT use single ('), you need double (")
	$head = "graph networkmap { labeljust=l; margin=0; ";
	if ($nooverlap != '') {
		$head .= "overlap=\"$overlap\";";
		$head .= "ranksep=\"$ranksep\";";
		$head .= "outputorder=edgesfirst;";
	} 
	$head .= "ratio=fill;";
	$head .= "root=0;";
	$head .= "size=\"$size\";";
	
	return $head;
}

// Closes a graph definition
function networkmap_close_graph () {
	return '}';
}

// Returns the filter used to achieve the desired layout
function networkmap_get_filter ($layout) {
	switch($layout) {
		case 'flat':
			return 'dot';
		case 'radial':
			return 'twopi';
		case 'circular':
			return 'circo';
		case 'spring1':
			return 'neato';
		case 'spring2':
			return 'fdp';
		default:
			return 'twopi';
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
function networkmap_create_networkmap ($name, $type = 'topology', $layout = 'radial', $nooverlap = true, $simple = false, $regenerate = true, $font_size = 12, $id_group = 0, $id_module_group = 0, $depth = 'all', $only_modules_with_alerts = false, $hide_policy_modules = false, $zoom = 1, $distance_nodes = 2.5, $center = 0) {
	
	global $config;
	
	$values = array();
	
	$values['name'] = $name;
	$values['type'] = $type;
	$values['layout'] = $layout;
	$values['nooverlap'] = $nooverlap;
	$values['simple'] = $simple;
	$values['regenerate'] = $regenerate;
	$values['font_size'] = $font_size;
	$values['id_group'] = $id_group;
	$values['id_module_group'] = $id_module_group;
	$values['depth'] = $depth;
	$values['only_modules_with_alerts'] = $only_modules_with_alerts;
	$values['hide_policy_modules'] = $hide_policy_modules;
	$values['zoom'] = $zoom;
	$values['distance_nodes'] = $distance_nodes;
	$values['center'] = $center;
	$values['id_user'] = $config['id_user'];
	
	return @db_process_sql_insert ('tnetwork_map', $values);
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
			$filter['id_user'] = $config['id_user'];
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
function networkmap_get_networkmaps ($id_user = '', $type = '', $optgrouped = true) {
	global $config;
	
	if ($id_user == '') {
		$id_user = $config['id_user'];
	}
	
	$type_cond = '';
	if ($type != '') {
		switch ($config["dbtype"]) {
			case "mysql":
				$type_cond = ' AND type = "'.$type.'"';
				break;
			case "postgresql":
			case "oracle":
				$type_cond = ' AND type = \''.$type.'\'';
				break;
		}
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			$networkmaps_raw =  db_get_all_rows_filter ('tnetwork_map', 'id_user = "'.$id_user.'"'.$type_cond.' ORDER BY type DESC, name ASC', array('id_networkmap','name', 'type'));
			break;
		case "postgresql":
		case "oracle":
			$networkmaps_raw =  db_get_all_rows_filter ('tnetwork_map', 'id_user = \''.$id_user.'\' '.$type_cond.' ORDER BY type DESC, name ASC', array('id_networkmap','name', 'type'));
			break;
	}
	
	if ($networkmaps_raw === false) {
		return false;
	}
	
	$networkmaps = array();
	foreach ($networkmaps_raw as $key => $networkmapitem) {
		if ($optgrouped) {
			$networkmaps[$networkmapitem['id_networkmap']] = 
				array('name' => $networkmapitem['name'], 
					'optgroup' => $networkmapitem['type']);
		}
		else {
			$networkmaps[$networkmapitem['id_networkmap']] = $networkmapitem['name'];
		}
	}
	
	return $networkmaps;
}

/**
 * Deletes a network map.
 * 
 * @param int Map id to be deleted.
 *
 * @return bool True if the map was deleted, false otherwise.
 */
function networkmap_delete_networkmap ($id_networkmap) {
	$id_networkmap = safe_int ($id_networkmap);
	if (empty ($id_networkmap))
		return false;
	$networkmap = networkmap_get_networkmap ($id_networkmap);
	if ($networkmap === false)
		return false;
	return @db_process_sql_delete ('tnetwork_map', array ('id_networkmap' => $id_networkmap));
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
function networkmap_get_types () {
	$networkmap_types = array();
	
	$is_enterprise = enterprise_include_once('include/functions_policies.php');
	
	$networkmap_types['topology'] = __('Create a new topology map');
	$networkmap_types['groups'] = __('Create a new group map');
	
	if ($is_enterprise !== ENTERPRISE_NOT_HOOK) {
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
function networkmap_get_filter_types () {
	$networkmap_types = array();
	
	$is_enterprise = enterprise_include_once('include/functions_policies.php');
	
	$networkmap_types['topology'] = __('Topology');
	$networkmap_types['groups'] = __('Group');
	
	if ($is_enterprise !== ENTERPRISE_NOT_HOOK) {
		$enterprise_types = enterprise_hook('policies_get_networkmap_filter_types');
		
		$networkmap_types = array_merge($networkmap_types, $enterprise_types);
	}
	
	return $networkmap_types;
}

?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("area[title!='<?php echo 'Pandora FMS'; ?>']").cluetip ({
		arrows: true,
		attribute: 'title',
		cluetipClass: 'default'
	});
});
/* ]]> */
</script>
