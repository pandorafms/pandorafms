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

/**
 * @package Include
 * @subpackage Network_Map
 */

/**
 * Include agents function
 */
require_once ('functions_agents.php');
require_css_file ('cluetip');
require_jquery_file ('cluetip');

// Check if a node descends from a given node
function is_descendant ($node, $ascendant, $parents) {
	if (! isset ($parents[$node])) {
		return false;
	}

	if ($node == $ascendant) {
		return true;
	}
	
	return is_descendant ($parents[$node], $ascendant, $parents);
}

// Generate a dot graph definition for graphviz
function generate_dot ($pandora_name, $group = 0, $simple = 0, $font_size = 12, $layout = 'radial', $nooverlap = 0, $zoom = 1, $ranksep = 2.5, $center = 0, $regen = 1, $pure = 0, $id_networkmap = 0) {
	$parents = array();
	$orphans = array();

	$filter = array ();
	$filter['disabled'] = 0;
	if ($group >= 1)
		$filter['id_grupo'] = $group;
	
	// Get agent data
	$agents = get_agents ($filter,
		array ('id_grupo, nombre, id_os, id_parent, id_agente'));
	if ($agents === false)
		return false;
	
	// Open Graph
	$graph = open_graph ($layout, $nooverlap, $pure, $zoom, $ranksep, $font_size);
	
	// Parse agents
	$nodes = array ();
	foreach ($agents as $agent) {
		// Save node parent information to define edges later
		if ($agent['id_parent'] != "0") {
			$parents[$agent['id_agente']] = $agent['id_parent'];
		} else {
			$orphans[$agent['id_agente']] = 1;
		}
		
		$agent['id_node'] = $agent['id_agente'];
	
		// Add node
		$nodes[$agent['id_agente']] = $agent;
	}
	
	if (empty ($nodes)) {
		return false;
	}
	// Create nodes
	foreach ($nodes as $node_id => $node) {
		if ($center > 0 && ! is_descendant ($node_id, $center, $parents)) {
			unset ($parents[$node_id]);
			unset ($orphans[$node_id]);
			unset ($nodes[$node_id]);
			continue;
		}

		$graph .= create_agent_node ($node , $simple, $font_size)."\n\t\t";
	}

	// Define edges
	foreach ($parents as $node => $parent_id) {
		// Verify that the parent is in the graph
		if (isset ($nodes[$parent_id])) {
			$graph .= create_edge ($node, $parent_id, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, 'operation/agentes/networkmap', 'topology', $id_networkmap);
		} else {
			$orphans[$node] = 1;
		}
	}

	// Create a central node if orphan nodes exist
	if (count ($orphans)) {
		$graph .= create_pandora_node ($pandora_name, $font_size, $simple);
	}

	// Define edges for orphan nodes
	foreach (array_keys($orphans) as $node) {
		$graph .= create_edge ('0', $node, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, 'operation/agentes/networkmap', 'topology', $id_networkmap);
	}
	
	// Close graph
	$graph .= close_graph ();
	
	return $graph;
}

// Generate a dot graph definition for graphviz with groups
function generate_dot_groups ($pandora_name, $group = 0, $simple = 0, $font_size = 12, $layout = 'radial', $nooverlap = 0, $zoom = 1, $ranksep = 2.5, $center = 0, $regen = 1, $pure = 0, $modwithalerts = 0, $module_group = 0, $hidepolicymodules = 0, $depth = 'all', $id_networkmap = 0) {
	
	global $config;

	$parents = array();
	$orphans = array();

	$filter = array ();
	$filter['disabled'] = 0;
	
	// Get groups data
	if ($group > 0) {
		$filter['id_grupo'] = $group;
		$groups[0] = get_db_row ('tgrupo', 'id_grupo', $group);
	}
	else {
		$groups = get_db_all_rows_in_table ('tgrupo');
	}
	

	// Open Graph
	$graph = open_graph ($layout, $nooverlap, $pure, $zoom, $ranksep, $font_size);
	
	$node_count = 0;
	
	// Parse groups
	$nodes = array ();
	$nodes_groups = array();
	foreach ($groups as $group) {
		$node_count ++;
		$group['type'] = 'group';
		$group['id_node'] = $node_count;

		// Add node
		$nodes_groups[$group['id_grupo']] = $group;
	}
	
	$node_count = 0;

	foreach ($nodes_groups as $node_group) {
		
		$node_count++;
		
		// Save node parent information to define edges later
		if ($node_group['parent'] != "0") {
			$parents[$node_count] = $nodes_groups[$node_group['parent']]['id_node'];
		} else {
			$orphans[$node_count] = 1;
		}
		
		$nodes[$node_count] = $node_group;	
	}
	
	if($depth != 'group') {
		// Get agents data
		$agents = get_agents ($filter,
			array ('id_grupo, nombre, id_os, id_agente'));
		if ($agents === false)
			return false;
		
		// Parse agents
		$nodes_agents = array();
		foreach ($agents as $agent) {
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
			$modules = get_agent_modules ($agent['id_agente'], false, array('disabled' => 0), true, false);
			// Parse modules
			foreach ($modules as $key => $module) {
				$node_count ++;
				$agent_module = get_agentmodule($key);
				$alerts_module = get_db_sql('SELECT count(*) as num FROM talert_template_modules WHERE id_agent_module = '.$key);
				
				if($alerts_module == 0 && $modwithalerts){
					continue;
				}
				
				if($agent_module['id_module_group'] != $module_group && $module_group != 0){
					continue;
				}
				
				if($hidepolicymodules && $config['enterprise_installed']){
					enterprise_include_once('include/functions_policies.php');
					if(isModuleInPolicy($key)) {
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
	// Create nodes
	foreach ($nodes as $node_id => $node) {
		if ($center > 0 && ! is_descendant ($node_id, $center, $parents)) {
			unset ($parents[$node_id]);
			unset ($orphans[$node_id]);
			unset ($nodes[$node_id]);
			continue;
		}

		switch($node['type']){
			case 'group':
				$graph .= create_group_node ($node , $simple, $font_size)."\n\t\t";
				break;
			case 'agent':
				$graph .= create_agent_node ($node , $simple, $font_size)."\n\t\t";
				break;
			case 'module':
				$graph .= create_module_node ($node , $simple, $font_size)."\n\t\t";
				break;
		}
	}

	// Define edges
	foreach ($parents as $node => $parent_id) {
		// Verify that the parent is in the graph
		if (isset ($nodes[$parent_id])) {
			$graph .= create_edge ($node, $parent_id, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, 'operation/agentes/networkmap', 'groups', $id_networkmap);
		} else {
			$orphans[$node] = 1;
		}
	}

	// Create a central node if orphan nodes exist
	if (count ($orphans)) {
		$graph .= create_pandora_node ($pandora_name, $font_size, $simple);
	}

	// Define edges for orphan nodes
	foreach (array_keys($orphans) as $node) {
		$graph .= create_edge ('0', $node, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, 'operation/agentes/networkmap', 'groups', $id_networkmap);
	}
	
	// Close graph
	$graph .= close_graph ();
	
	return $graph;
}

// Returns an edge definition
function create_edge ($head, $tail, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group, $sec2 = 'operation/agentes/networkmap', $tab = 'topology', $id_networkmap = 0) {

	// edgeURL allows node navigation
	$edge = $head.' -- '.$tail.'[color="#BDBDBD", headclip=false, tailclip=false,
	edgeURL="index.php?sec=estado&sec2='.$sec2.'&tab='.$tab.'&recenter_networkmap=1&center='.$head.
	'&layout='.$layout.'&nooverlap=' .$nooverlap.'&pure='.$pure.
	'&zoom='.$zoom.'&ranksep='.$ranksep.'&simple='.$simple.'&regen=1'.
	'&font_size='.$font_size.'&group='.$group.'&id_networkmap='.$id_networkmap.'"];';

	return $edge;
}

// Returns a group node definition
function create_group_node ($group, $simple = 0, $font_size = 10) {
	$status = get_group_status ($group['id_grupo']);
	
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
	
	$icon = get_group_icon($group['id_grupo']);
	
	if ($simple == 0){
		// Set node icon
		if (file_exists ('images/groups_small/'.$icon.'.png')) { 
			$img_node = '<img src="images/groups_small/'.$icon.'.png"/>';
		} else {
			$img_node = '-';
		}

		$name = get_group_name($group['id_grupo']);
		
		$node = $group['id_node'].' [ color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.30, height=0.30, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>'.$img_node.'</TD></TR>
		 <TR><TD>'.$name.'</TD></TR></TABLE>>,
		 shape="invtrapezium", URL="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$group['id_grupo'].'",
		 tooltip="ajax.php?page=operation/agentes/ver_agente&get_group_status_tooltip=1&id_group='.$group['id_grupo'].'"];';
	} else {
		$node = $group['id_node'] . ' [ color="'.$status_color.'", fontsize='.$font_size.', shape="invtrapezium", style="filled", fixedsize=true, width=0.20, height=0.20, label="", tooltip="ajax.php?page=operation/agentes/ver_agente&get_group_status_tooltip=1&id_group='.$group['id_grupo'].'"];';
	}
	return $node;
}

// Returns a node definition
function create_agent_node ($agent, $simple = 0, $font_size = 10) {
	$status = get_agent_status($agent['id_agente']);

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
	$name = strtolower ($agent["nombre"]);
	if (strlen ($name) > 16)
		$name = substr ($name, 0, 16);

	if ($simple == 0){
		// Set node icon
		if (file_exists ('images/networkmap/'.$agent['id_os'].'.png')) { 
			$img_node = 'images/networkmap/'.$agent['id_os'].'.png';
		} else {
			$img_node = 'images/networkmap/0.png';
		}

		$node = $agent['id_node'].' [ color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.40, height=0.40, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD><IMG SRC="'.$img_node.'"/></TD></TR>
		 <TR><TD>'.$name.'</TD></TR></TABLE>>,
		 shape="doublecircle", URL="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'",
		 tooltip="ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'].'"];';
	} else {
		$node = $agent['id_node'] . ' [ color="' . $status_color . '", fontsize='.$font_size.', shape="doublecircle", style="filled", fixedsize=true, width=0.20, height=0.20, label="", tooltip="ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'].'"];';
	}
	return $node;
}

// Returns a module node definition
function create_module_node ($module, $simple = 0, $font_size = 10) {
	$status = get_agentmodule_status($module['id_agente_modulo']);
				
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
		$node = $module['id_node'].' [ color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.30, height=0.30, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD>'.print_moduletype_icon ($module['id_tipo_modulo'], true).'</TD></TR>
		 <TR><TD>'.$module['nombre'].'</TD></TR></TABLE>>,
		 shape="circle", URL="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$module['id_agente'].'",
		 tooltip="ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'].'"];';
	} else {
		$node = $module['id_node'] . ' [ color="'.$status_color.'", fontsize='.$font_size.', shape="circle", style="filled", fixedsize=true, width=0.20, height=0.20, label="", tooltip="ajax.php?page=operation/agentes/ver_agente&get_agentmodule_status_tooltip=1&id_module='.$module['id_agente_modulo'].'"];';
	}
	return $node;
}

// Returns the definition of the central module
function create_pandora_node ($name, $font_size = 10, $simple = 0) {
	$img = '<TR><TD><IMG SRC="images/networkmap/pandora_node.png"/></TD></TR>';
	$name = '<TR><TD BGCOLOR="#FFFFFF">'.$name.'</TD></TR>';
	$label = '<TABLE BORDER="0">'.$img.$name.'</TABLE>';
	if ($simple == 1){
		$label = '';
	}

	$node = '0 [ color="#364D1F", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.8, height=0.6, label=<'.$label.'>,
		shape="ellipse", URL="index.php?sec=estado&sec2=operation/agentes/group_view" ];';

	return $node;
}

// Opens a group definition
function open_group ($id) {
	$img = 'images/'.get_group_icon ($id).'.png';
	$name = get_group_name ($id);
	
	$group = 'subgraph cluster_' . $id . 
		' { style=filled; color=darkolivegreen3; label=<<TABLE BORDER="0">
		<TR><TD><IMG SRC="'.$img.'"/></TD><TD>'.$name.'</TD></TR>
		</TABLE>>; tooltip="'.$name.'";
		URL="index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id='
		. $id . '";';
	
	return $group;
}

// Closes a group definition
function close_group () {
	return '}';
}

// Opens a graph definition
function open_graph ($layout, $nooverlap, $pure, $zoom, $ranksep, $font_size) {
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
function close_graph () {
	return '}';
}

// Returns the filter used to achieve the desired layout
function get_filter ($layout) {
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
function create_networkmap ($name, $type = 'topology', $layout = 'radial', $nooverlap = true, $simple = false, $regenerate = true, $font_size = 12, $id_group = 0, $id_module_group = 0, $depth = 'all', $only_modules_with_alerts = false, $hide_policy_modules = false, $zoom = 1, $distance_nodes = 2.5, $center = 0) {
	
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
	
	return @process_sql_insert ('tnetwork_map', $values);
}

/**
 * Get a network map report.
 *
 * @param int Networkmap id to get.
 * @param array Extra filter.
 * @param array Fields to get.
 *
 * @return Networkmap with the given id. False if not available or readable.
 */
function get_networkmap ($id_networkmap, $filter = false, $fields = false) {
	global $config;
	
	$id_networkmap = safe_int ($id_networkmap);
	if (empty ($id_networkmap))
		return false;
	if (! is_array ($filter))
		$filter = array ();
		
	$filter['id_networkmap'] = $id_networkmap;
	$filter['id_user'] = $config['id_user'];
	
	$networkmap = get_db_row_filter ('tnetwork_map', $filter, $fields);
		
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
function get_networkmaps ($id_user = '', $type = '', $optgrouped = true) {
	global $config;
	
	if($id_user == '') {
		$id_user = $config['id_user'];
	}
	
	$type_cond = '';
	if($type != '') {
		$type_cond = ' AND type = "'.$type.'"';
	}
	
	$networkmaps_raw =  get_db_all_rows_filter ('tnetwork_map', 'id_user = "'.$id_user.'"'.$type_cond.' ORDER BY type DESC, name ASC', array('id_networkmap','name', 'type'));
	
	if($networkmaps_raw === false){
		return false;
	}
		
	$networkmaps = array();
	foreach($networkmaps_raw as $key => $networkmapitem) {
		if($optgrouped) {
			$networkmaps[$networkmapitem['id_networkmap']] = array('name' => $networkmapitem['name'], 
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
function delete_networkmap ($id_networkmap) {
	$id_networkmap = safe_int ($id_networkmap);
	if (empty ($id_networkmap))
		return false;
	$networkmap = get_networkmap ($id_networkmap);
	if ($networkmap === false)
		return false;
	return @process_sql_delete ('tnetwork_map', array ('id_networkmap' => $id_networkmap));
}

/**
 * Updates a network map.
 *
 * @param int Map id.
 * @param array Extra values to be set.
 * 
 * @return bool True if the map was updated. False otherwise.
 */
function update_networkmap ($id_networkmap, $values) {
	$networkmap = get_networkmap ($id_networkmap);
	if ($networkmap === false)
		return false;
	return (process_sql_update ('tnetwork_map',
		$values,
		array ('id_networkmap' => $id_networkmap))) !== false;
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
