<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Login check
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

$ajax = true;


require_once('godmode/reporting/visual_console_builder.constans.php');
require_once('include/functions_visual_map.php');
require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . '/include/functions_graph.php');

$action = get_parameter('action');
$type = get_parameter('type');

$id_visual_console = get_parameter('id_visual_console', null);

$id_element = get_parameter('id_element', null);

$image = get_parameter('image', null);
$background = get_parameter('background', null);
$label = get_parameter('label', '');
$left = get_parameter('left', null);
$top = get_parameter('top', null);
$agent = get_parameter('agent', null);
$id_module = get_parameter('module', null);
$period = get_parameter('period', null);
$width = get_parameter('width', null);
$height = get_parameter('height', null);
$parent = get_parameter('parent', null);
$map_linked = get_parameter('map_linked', null);
$label_color = get_parameter('label_color', null);
$width_percentile = get_parameter('width_percentile', null);
$max_percentile = get_parameter('max_percentile', null);
$height_module_graph = get_parameter('height_module_graph', null);
$width_module_graph = get_parameter('width_module_graph', null);
$id_agent_module = get_parameter('id_agent_module', 0);
$process_simple_value = get_parameter('process_simple_value', PROCESS_VALUE_NONE);
$type_percentile = get_parameter('type_percentile', 'percentile');
$value_show = get_parameter('value_show', 'percent');

$get_element_status = get_parameter('get_element_status', 0);
$get_image_path_status = get_parameter('get_image_path_status', 0);


switch ($action) {
	case 'get_font':
		$return = array();
		$return['font'] = $config['fontpath'];
		echo json_encode($return);
		break;
	
	
	
	case 'get_image_sparse':
		$img = grafico_modulo_sparse($id_agent_module,
			$period, false, $width, $height, '', null, false, 1, false, 0, '', 0, 0,
			true, true);
		
		preg_match("/src=[\'\"](.*)[\'\"]/", $img, $matches);
		$url = $matches[1];
		
		echo $url;
		break;
	
	
	
	case 'get_layout_data':
		$layoutData = db_get_row_filter('tlayout_data',
			array('id' => $id_element));
		
		echo json_encode($layoutData);
		break;
	
	
	
	case 'get_module_value':
		$unit_text = false;
		$layoutData = db_get_row_filter('tlayout_data', array('id' => $id_element));
		switch ($layoutData['type']) {
			case SIMPLE_VALUE:
			case SIMPLE_VALUE_MAX:
			case SIMPLE_VALUE_MIN:
			case SIMPLE_VALUE_AVG:
				$type = visual_map_get_simple_value_type($process_simple_value);
				$returnValue = visual_map_get_simple_value($type,
					$layoutData['id_agente_modulo'], $period);
				break;
			
			case PERCENTILE_BAR:
			case PERCENTILE_BUBBLE:
			default:
				$returnValue = db_get_sql ('SELECT datos
					FROM tagente_estado
					WHERE id_agente_modulo = ' . $layoutData['id_agente_modulo']);
				
				//html_debug_print($value_show);
				//html_debug_print($layoutData);
				
				if (($layoutData['type'] == PERCENTILE_BAR) ||
					($layoutData['type'] == PERCENTILE_BUBBLE)) {
					if ($value_show == 'value') {
						$returnValue = format_for_graph($returnValue, 2);
						
						$unit_text_db = db_get_sql ('SELECT unit
							FROM tagente_modulo
							WHERE id_agente_modulo = ' . $layoutData['id_agente_modulo']);
						$unit_text_db = trim(io_safe_output($unit_text_db));
						
						if ($value_show == 'value') {
							//Set empty string unit at the moment
							//and change for old false value
							
							$unit_text = '';
						}
						
						if (!empty($unit_text_db))
							$unit_text = $unit_text_db;
					}
				}
				break;
				
		}
		
		// Linked to other layout ?? - Only if not module defined
		if ($layoutData['id_layout_linked'] != 0) {
			$status = visual_map_get_layout_status ($layoutData['id_layout_linked']);
		
		// Single object
		}
		elseif (($layoutData["type"] == 0)
					|| ($layoutData["type"] == 3)
					|| ($layoutData["type"] == 4)) {
			// Status for a simple module
			if ($layoutData['id_agente_modulo'] != 0) {
				$status = modules_get_agentmodule_status ($layoutData['id_agente_modulo']);
				$id_agent = db_get_value ("id_agente", "tagente_estado", "id_agente_modulo", $layoutData['id_agente_modulo']);
			
			// Status for a whole agent, if agente_modulo was == 0
			}
			elseif ($layoutData['id_agent'] != 0) {
				$status = agents_get_status ($layoutData["id_agent"]);
				if ($status == -1) // agents_get_status return -1 for unknown!
					$status = 3; 
				$id_agent = $layoutData["id_agent"];
			}
			else {
				$status = 3;
				$id_agent = 0;
			}
		}
		else {
			// If it's a graph, a progress bar or a data tag, ALWAYS report
			// status OK (=0) to avoid confussions here.
			$status = 0;
		}
		
		switch ($status) {
			case 1:
				//Critical (BAD)
				$colorStatus = "#ff0000";
				break;
			case 4:
				//Critical (ALERT)
				$colorStatus = "#ff8800";
				break;
			case 0:
				//Normal (OK)
				$colorStatus = "#00ff00";
				break;
			case 2:
				//Warning
				$colorStatus = "#ffff00";
				break;
			case 3:
				//Unknown
			default:
				$colorStatus = "#5A5AFF";
				// Default is Grey (Other)
				break;
		}
		
		$return = array();
		$return['value'] = $returnValue;
		$return['max_percentile'] = $layoutData['height'];
		$return['width_percentile'] = $layoutData['width'];
		$return['unit_text'] = $unit_text;
		$return['colorRGB'] = implode('|', html_html2rgb($colorStatus));
		
		echo json_encode($return);
		break;
	
	
	
	case 'get_color_line':
		$layoutData = db_get_row_filter('tlayout_data', array('id' => $id_element));
		$parent = db_get_row_filter('tlayout_data', array('id' => $layoutData['parent_item']));
		
		$return = array();
		$return['color_line'] = visual_map_get_color_line_status($parent);
		echo json_encode($return);
		break;
	
	
	
	case 'get_image':
		$layoutData = db_get_row_filter('tlayout_data', array('id' => $id_element));
		
		$return = array();
		$return['image'] = visual_map_get_image_status_element($layoutData);
		echo json_encode($return);
		break;
	
	
	
	case 'update':
	case 'move':
		$values = array();
		switch ($type) {
			case 'background':
				if ($background !== null)
					$values['background'] = $background;
				if ($width !== null)
					$values['width'] = $width;
				if ($height !== null)
					$values['height'] = $height;
				db_process_sql_update('tlayout', $values, array('id' => $id_visual_console));
				break;
			case 'simple_value':
				if ($action == 'update') {
					$values['type'] = visual_map_get_simple_value_type($process_simple_value);
					$values['period'] = $period;
				}
			case 'percentile_bar':
			case 'percentile_item':
			case 'static_graph':
			case 'module_graph':
			case 'label':
			case 'icon':
				if ($label !== null) {
					$values['label'] = $label;
				}
				if ($left !== null) {
					$values['pos_x'] = $left;
				}
				if ($top !== null) { 
					$values['pos_y'] = $top;
				}
				if ($agent !== null) {
					$id_agent = agents_get_agent_id($agent);
					$values['id_agent'] = $id_agent;
				}
				if ($id_module !== null) {
					$values['id_agente_modulo'] = $id_module;
				}
				if ($parent !== null) {
					$values['parent_item'] = $parent;
				}
				if ($map_linked !== null) {
					$values['id_layout_linked'] = $map_linked;
				}
				if ($label_color !== null) {
					$values['label_color'] = $label_color;
				}
				switch($type) {
					case 'module_graph':
						if ($height_module_graph !== null) {
							$values['height'] = $height_module_graph;
						}
						if ($width_module_graph !== null) {
							$values['width'] = $width_module_graph;
						}
						if ($period !== null) {
							$values['period'] = $period;
						}
						break;
					case 'percentile_item':
					case 'percentile_bar':
						if ($action == 'update') {
							if ($width_percentile !== null) {
								$values['width'] = $width_percentile;
							}
							if ($max_percentile !== null) {
								$values['height'] = $max_percentile;
							}
							
							$values['type'] = PERCENTILE_BAR;
							if ($type_percentile == 'percentile') {
								$values['type'] = PERCENTILE_BAR;
							}
							elseif ($type_percentile == 'bubble') {
								$values['type'] = PERCENTILE_BUBBLE;
							}
							
							//Check the field's value for back compatibility
							$values['image'] = 'percent';
							if (($value_show == 'percent') ||
								($value_show == 'value'))
								$values['image'] = $value_show;
						}
						break;
					case 'icon':
					case 'static_graph':
						if ($image !== null) {
							$values['image'] = $image;
						}
						if ($width !== null) {
							$values['width'] = $width;
						}
						if ($height !== null) {
							$values['height'] = $height;
						}
						break;
				}
				
				if ($action == 'move') {
					//Don't change the label because only change the positions
					unset($values['label']);
				}
				
				$result = db_process_sql_update('tlayout_data', $values, array('id' => $id_element));
				echo (int)$result;
				break;
		}
		break;
	
	
	
	case 'load':
		switch ($type) {
			case 'background':
				$backgroundFields = db_get_row_filter('tlayout', array('id' => $id_visual_console), array('background', 'height', 'width'));
				echo json_encode($backgroundFields);
				break;
			case 'percentile_bar':
			case 'percentile_item':
			case 'static_graph':
			case 'module_graph':
			case 'simple_value':
			case 'label':
			case 'icon':
				$elementFields = db_get_row_filter('tlayout_data', array('id' => $id_element));
				$elementFields['agent_name'] = io_safe_output(agents_get_name($elementFields['id_agent']));
				//Make the html of select box of modules about id_agent.
				if ($elementFields['id_agent'] != 0) {
					$modules = agents_get_modules($elementFields['id_agent'], false, array('disabled' => 0, 'id_agente' => $elementFields['id_agent']));
					
					$elementFields['modules_html'] = '<option value="0">--</option>';
					foreach ($modules as $id => $name) {
						$elementFields['modules_html'] .= '<option value="' . $id . '">' . io_safe_output($name) . '</option>';
					}
				}
				else  {
					$elementFields['modules_html'] = '<option value="0">' . __('Any') . '</option>';
				}
				switch ($type) {
					case 'percentile_item':
					case 'percentile_bar':
						$elementFields['width_percentile'] = $elementFields['width'];
						$elementFields['max_percentile'] = $elementFields['height'];
						//Check the field's value for back compatibility
						$elementFields['value_show'] = 'percent';
						if (($elementFields['image'] == 'percent') ||
							($elementFields['image'] == 'value'))
							$elementFields['value_show'] = $elementFields['image'];
						
						$elementFields['type_percentile'] = 'percentile';
						if ($elementFields['type'] == PERCENTILE_BAR) {
							$elementFields['type_percentile'] = 'percentile';
						}
						elseif ($elementFields['type'] == PERCENTILE_BUBBLE) {
							$elementFields['type_percentile'] = 'bubble';
						}
						break;
					
					case 'module_graph':
						$elementFields['width_module_graph'] = $elementFields['width'];
						$elementFields['height_module_graph'] = $elementFields['height'];
						break;
					
				}
				//Support for max, min and svg process on simple value items
				if ($type == 'simple_value') {
					switch ($elementFields['type']) {
						case SIMPLE_VALUE:
							$elementFields['process_value'] = 0;
							break;
						case SIMPLE_VALUE_MAX:
							$elementFields['process_value'] = 2;
							break;
						case SIMPLE_VALUE_MIN:
							$elementFields['process_value'] = 1;
							break;
						case SIMPLE_VALUE_AVG:
							$elementFields['process_value'] = 3;
							break;
					}
				}
				
				$elementFields['label'] = io_safe_output($elementFields['label']);
				echo json_encode($elementFields);
				break;
		}
		break;
	
	
	
	case 'insert':
		$values = array();
		$values['id_layout'] = $id_visual_console;
		$values['label'] = $label;
		$values['pos_x'] = $left;
		$values['pos_y'] = $top;
		
		if ($agent != '')
			$values['id_agent'] = agents_get_agent_id($agent);
		else
			$values['id_agent'] = 0;
		$values['id_agente_modulo'] = $id_module;
		$values['id_layout_linked'] = $map_linked;
		$values['label_color'] = $label_color;
		$values['parent_item'] = $parent;
		$values['no_link_color'] = 1;
		
		switch ($type) {
			case 'module_graph':
				$values['type'] = MODULE_GRAPH;
				$values['height'] = $height_module_graph;
				$values['width'] = $width_module_graph;
				$values['period'] = $period;
				break;
			case 'percentile_item':
			case 'percentile_bar': 
				if ($type_percentile == 'percentile') {
					$values['type'] = PERCENTILE_BAR;
				}
				else {
					$values['type'] = PERCENTILE_BUBBLE;
				}
				$values['image'] = $value_show; //Hack to save it show percent o value.
				$values['width'] = $width_percentile;
				$values['height'] = $max_percentile;
				break;
			case 'static_graph':
				$values['type'] = STATIC_GRAPH;
				$values['image'] = $image;
				$values['width'] = $width;
				$values['height'] = $height;
				break;
			case 'simple_value':
				//This allows min, max and avg process in a simple value
				$values['type'] = visual_map_get_simple_value_type($process_simple_value);
				$values['period'] = $period;
				break;
			case 'label':
				$values['type'] = LABEL;
				break;
			case 'icon':
				$values['type'] = ICON;
				$values['image'] = $image;
				$values['width'] = $width;
				$values['height'] = $height;
				break;
		}
		$idData = db_process_sql_insert('tlayout_data', $values);
		
		$return = array();
		if ($idData === false) {
			$return['correct'] = 0;
		}
		else {
			$text = visual_map_create_internal_name_item($label, $type, $image, $agent, $id_module, $idData);
			
			$return['correct'] = 1;
			$return['id_data'] = $idData;
			$return['text'] = $text;
		}
		echo json_encode($return);
		break;
	
	
	
	case 'delete':
		if (db_process_sql_delete('tlayout_data', array('id' => $id_element, 'id_layout' => $id_visual_console)) === false) {
			$return['correct'] = 0;
		}
		else {
			$return['correct'] = 1;
		}
		
		echo json_encode($return);
		break;
}

/* visual map element status check  */
if ($get_element_status) {
	$layoutData = db_get_row_filter('tlayout_data', array('id' => $id_element));
	
	$res = visual_map_get_status_element($layoutData);
	
	echo $res;
	
	return;
}

if ($get_image_path_status){
	$img_src = get_parameter("img_src");
	$only_src = get_parameter("only_src", 0);
	
	$result = array();
	
	$result['bad'] = html_print_image($img_src . '_bad.png', true, '', $only_src);
	$result['ok'] = html_print_image($img_src . '_ok.png', true, '', $only_src);
	$result['warning'] = html_print_image($img_src . '_warning.png', true, '', $only_src);
	$result['ok'] = html_print_image($img_src . '_ok.png', true, '', $only_src);
	$result['normal'] = html_print_image($img_src . '.png', true, '', $only_src);
	
	echo json_encode($result);
}

?>
