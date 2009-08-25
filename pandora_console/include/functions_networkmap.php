<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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
function generate_dot ($pandora_name, $group = 0, $simple = 0, $font_size = 12, $layout = 'radial', $nooverlap = 0, $zoom = 1, $ranksep = 2.5, $center = 0, $regen = 1, $pure = 0) {
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

		$graph .= create_node ($node , $simple, $font_size)."\n\t\t";
	}

	// Define edges
	foreach ($parents as $node => $parent_id) {
		// Verify that the parent is in the graph
		if (isset ($nodes[$parent_id])) {
			$graph .= create_edge ($node, $parent_id, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group);
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
		$graph .= create_edge ('0', $node, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group);
	}
	
	// Close graph
	$graph .= close_graph ();
	
	return $graph;
}

// Returns an edge definition
function create_edge ($head, $tail, $layout, $nooverlap, $pure, $zoom, $ranksep, $simple, $regen, $font_size, $group) {

	// edgeURL allows node navigation
	$edge = $head.' -- '.$tail.'[color="#BDBDBD", headclip=false, tailclip=false,
	edgeURL="index.php?sec=estado&sec2=operation/agentes/networkmap&center='.$head.
	'&layout='.$layout.'&nooverlap=' .$nooverlap.'&pure='.$pure.
	'&zoom='.$zoom.'&ranksep='.$ranksep.'&simple='.$simple.'&regen=1'.
	'&font_size='.$font_size.'&group='.$group.'"];';

	return $edge;
}

// Returns a node definition
function create_node ($agent, $simple = 0, $font_size = 10) {
	$sql = sprintf ('SELECT COUNT(tagente_modulo.id_agente)
			FROM tagente_estado, tagente_modulo
			WHERE tagente_modulo.id_agente = %d
			AND tagente_modulo.id_tipo_modulo in (2, 6, 9, 18, 21, 100)
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND tagente_modulo.disabled = 0 
			AND tagente_estado.estado = 1',
			$agent['id_agente']);
	$bad_modules = get_db_sql ($sql);
	
	// Set node status
	if ($bad_modules) {
		$status_color = '#FF1D1D';
	} else {
		$status_color = '#8DFF1D';
	}

	// Check for alert
	$sql = sprintf ('SELECT COUNT(talert_template_modules.id)
		FROM talert_template_modules, tagente_modulo, tagente
		WHERE tagente.id_agente = %d
		AND tagente.disabled = 0
		AND tagente.id_agente = tagente_modulo.id_agente
		AND tagente_modulo.disabled = 0
		AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
		AND talert_template_modules.times_fired > 0 ',
		$agent['id_agente']);
	$alert_modules = get_db_sql ($sql);
	if ($alert_modules) 
		$status_color = '#FFE308';

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

		$node = $agent['id_agente'].' [ color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.40, height=0.40, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD><IMG SRC="'.$img_node.'"/></TD></TR>
		 <TR><TD>'.$name.'</TD></TR></TABLE>>,
		 shape="ellipse", URL="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'",
		 tooltip="ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'].'"];';
	} else {
		$node = $agent['id_agente'] . ' [ color="' . $status_color . '", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.20, height=0.20, label="", tooltip="ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'].'"];';
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
		shape="ellipse", URL="index.php?sec=estado&sec2=operation/agentes/estado_grupo" ];';

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

	if ($layout == 'radial') 
		$overlap = 'true';
	
	if ($layout == 'flat' || $layout == 'radial' || $layout == 'spring1' || $layout == "spring2")
		if ($nooverlap != '')
			$overlap = 'scalexy';

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

?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("area[title!='<?php echo __('Pandora FMS'); ?>']").cluetip ({
		arrows: true,
		attribute: 'title',
		cluetipClass: 'default'
	});
});
/* ]]> */
</script>
