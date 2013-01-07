<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

if (is_ajax ()) {
	$search_agents = (bool) get_parameter ('search_agents');
	
	if ($search_agents) {
		
		require_once ('include/functions_agents.php');
		
		$id_agent = (int) get_parameter ('id_agent');
		$string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
		$id_group = (int) get_parameter('id_group');
		
		$filter = array ();
		$filter[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
		$filter['id_grupo'] = $id_group; 
		
		$agents = agents_get_agents ($filter, array ('nombre', 'direccion'));
		if ($agents === false)
			return;
		
		foreach ($agents as $agent) {
			echo $agent['nombre']."|".$agent['direccion']."\n";
		}
		
		return;
 	}
 	
 	return;
}

check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}


unset($name);

$add_module = (bool) get_parameter ('add_module', false);
$delete_module = (bool) get_parameter ('delete_module', false);
$edit_graph = (bool) get_parameter('edit_graph', false);
$active_tab = get_parameter('tab', 'main');
$add_graph = (bool) get_parameter('add_graph', false);
$update_graph = (bool) get_parameter('update_graph', false);
$change_weight = (bool) get_parameter('change_weight', false);
$id_graph = (int) get_parameter('id', 0);

if ($add_graph) {
	$name = get_parameter_post ("name");
	$description = get_parameter_post ("description");
	$module_number = get_parameter_post ("module_number");
	$idGroup = get_parameter_post ('graph_id_group');
	$width = get_parameter_post ("width");
	$height = get_parameter_post ("height");
	$events = get_parameter_post ("events");
	$stacked = get_parameter ("stacked", 0);
	$period = get_parameter_post ("period");

	// Create graph
	$values = array( 'id_user' => $config['id_user'], 'name' => $name, 'description' => $description, 
	'period' => $period, 'width' => $width, 'height' => $height,
	'private' => 0, 'id_group' => $idGroup, 'events' => $events, 
	'stacked' => $stacked);

	if (trim($name) != "") {
		$id_graph = db_process_sql_insert('tgraph', $values);
	} else {
		$id_graph = false;
	}

	if(!$id_graph)
		$edit_graph = false;
}

if ($update_graph) {
	$id_graph = get_parameter('id');
	$name = get_parameter('name');
	$id_group = get_parameter('graph_id_group');
	$description = get_parameter('description');
	$width = get_parameter('width');
	$height = get_parameter('height');
	$period = get_parameter('period');
	$stacked = get_parameter('stacked');
	$events = get_parameter('events');
	$alerts = get_parameter('alerts'); 

	if (trim($name) != "") {

	$success = db_process_sql_update('tgraph', 
		array('name' => $name, 'id_group' => $id_group, 'description' => $description, 'width' => $width, 'height' => $height, 'period' => $period, 'stacked' => $stacked, 'events' => $events), 
		array('id_graph' => $id_graph));
	} else {
		$success = false;
	}
}

function add_quotes($item)
{
	return "'$item'";
}

if ($add_module) {
	$id_graph = get_parameter('id');
	$id_modules = (array) get_parameter('module');
	$id_agents = get_parameter('id_agents');
	$weight = get_parameter('weight');

	// Remove 'None' module option
	if ($id_modules[0] == '0')
		unset($id_modules[0]);	

	if (!empty($id_modules)) {
	
		// If metaconsole is not activated (tgraph and tgraph_source doesn't support in db table)
		//if ($config['metaconsole'] == 0) {
		
				$id_agent_modules = db_get_all_rows_sql("SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente IN (".
					implode(',', $id_agents).
					") AND nombre IN ('".
					implode("','", $id_modules).
					"')");

				if (count($id_agent_modules) > 0 && $id_agent_modules != '') {
					foreach($id_agent_modules as $id_agent_module)
						$result = db_process_sql_insert('tgraph_source', array('id_graph' => $id_graph, 'id_agent_module' => $id_agent_module['id_agente_modulo'], 'weight' => $weight));
					}
				else
					$result = false;
		
		//}
		// Metaconsole activated
		/*else {
		
			foreach ($id_agents as $current_agent_key => $current_agent_name) {
				$split_position = strpos($current_agent_name, '|');
				
				// Deserialize agent and server
				$id_agent = substr($current_agent_name, $split_position + 1);
				$server_name = substr($current_agent_name, 0, $split_position);
				
				$connection_data = enterprise_hook('metaconsole_get_connection', array($server_name));
				
				$connection_result = enterprise_hook('metaconsole_load_external_db', array($connection_data)); 

				$id_agent_modules = db_get_all_rows_sql("SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ".
					$id_agent .
					" AND nombre IN ('".
					implode("','", $id_modules).
					"')");

				if (count($id_agent_modules) > 0 && $id_agent_modules != '') {
					foreach($id_agent_modules as $id_agent_module)
						$result = db_process_sql_insert('tgraph_source', array('id_graph' => $id_graph, 'id_agent_module' => $id_agent_module['id_agente_modulo'], 'weight' => $weight));
					}
				else
					$result = false;				

				enterprise_hook('metaconsole_restore_db');
			}
			
		}*/
	}
	else 
		$result = false;

}

if ($delete_module) {
	$deleteGraph = get_parameter('delete');
	$result = db_process_sql_delete('tgraph_source', array('id_gs' => $deleteGraph));
}

if($change_weight){
	$weight = get_parameter ('weight');
	$id_gs = get_parameter ('graph');
	db_process_sql_update('tgraph_source', 
		array('weight' => $weight), 
		array('id_gs' => $id_gs));
}

if($edit_graph) {
	$buttons = array(
		'main' => array('active' => false,
			'text' => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=main&edit_graph=1&id=' . $id_graph . '">' . 
				html_print_image("images/setup.png", true, array ("title" => __('Setup'))) .'</a>'),
		'graph_editor' => array('active' => false,
			'text' => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=graph_editor&edit_graph=1&id=' . $id_graph . '">' . 
				html_print_image("images/config.png", true, array ("title" => __('Graph editor'))) .'</a>'),
		'view' => array('active' => false,
			'text' => '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id=' . $id_graph . '">' . 
				html_print_image("images/chart_curve.png", true, array ("title" => __('View graph'))) .'</a>')
		);
		
	$buttons[$active_tab]['active'] = true;

	$graphInTgraph = db_get_row_sql("SELECT name FROM tgraph WHERE id_graph = " . $id_graph);
	$name = $graphInTgraph['name'];
}
else {
	$buttons = '';
}

$head = __('Graph builder');

if (isset($name))
	$head .= " - ".$name;

// Header
$tab = get_parameter("tab", ""); 
switch ($tab){
	case "main": 
		ui_print_page_header ($head, "", false, "graph_builder", true, $buttons);
		break;
	case "graph_editor":
		ui_print_page_header ($head, "", false, "graph_editor", true, $buttons);
                break;
	default:
		ui_print_page_header ($head, "", false, "graph_builder", true, $buttons);
		break;
}

if($add_graph)
	ui_print_result_message($id_graph, __('Graph stored successfully'), __('There was a problem storing Graph'));

if($add_module)
	ui_print_result_message($result, __('Module added successfully'), __('There was a problem adding Module'));

if ($update_graph) 
	ui_print_result_message($success, __("Update the graph"), __("Bad update the graph"));

if ($delete_module) {
		ui_print_result_message($result, __('Graph deleted successfully'), __('There was a problem deleting Graph'));
}

// Parse CHUNK information into showable information
// Split id to get all parameters
if (!$delete_module) {
	if (isset($_POST["period"]))
		$period = $_POST["period"];
	if ((isset($chunkdata) )&& ($chunkdata != "")) {
		$module_array = array();
		$weight_array = array();
		$agent_array = array();
		$chunk1 = array();
		$chunk1 = explode ("|", $chunkdata);
		$modules="";$weights="";
		for ($a=0; $a < count($chunk1); $a++){
			$chunk2[$a] = array();
			$chunk2[$a] = explode ( ",", $chunk1[$a]);
			if (strpos($modules, $chunk2[$a][1]) == 0){ // Skip dupes
				$module_array[] = $chunk2[$a][1];
				$agent_array[] = $chunk2[$a][0];
				$weight_array[] = $chunk2[$a][2];
				if ($modules !="")
					$modules = $modules.",".$chunk2[$a][1];
				else
					$modules = $chunk2[$a][1];
				if ($weights !="")
					$weights = $weights.",".$chunk2[$a][2];
				else
					$weights = $chunk2[$a][2];
			}
		}
	}
}

switch ($active_tab) {
	case 'main':
		require_once('godmode/reporting/graph_builder.main.php');
		break;
	case 'graph_editor':
		require_once('godmode/reporting/graph_builder.graph_editor.php');
		break;
}
?>
