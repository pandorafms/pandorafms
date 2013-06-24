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
 * @subpackage Reporting
 */

global $config;

//Fix ajax include this file.
global $ajax;

if (!isset($ajax)) {
	require_once ('functions_graph.php');
}
require_once ($config['homedir'].'/include/functions_agents.php');
require_once ($config['homedir'].'/include/functions_modules.php');
require_once ($config['homedir'].'/include/functions_users.php');

function visual_map_print_button_editor($idDiv, $label, $float = 'left', $disabled = false, $class= '', $imageButton = false) {
	if ($float == 'left') {
		$margin = 'margin-right';
	}
	else {
		$margin = 'margin-left';
	}
	
	html_print_button($label, 'button_toolbox2', $disabled, "click2('" . $idDiv . "');", 'class="sub ' . $idDiv . ' ' . $class . '" style="float: ' . $float . ';"', false, $imageButton);
	return;
	
	if (!$disabled) $disableClass = '';
	else $disableClass = 'disabled';
	
	echo '<div class="button_toolbox ' . $disableClass . '" id="' . $idDiv . '"
		style="font-weight: bolder; text-align: center; float: ' . $float . ';' .
		'width: 80px; height: 50px; background: #e5e5e5; border: 4px outset black; ' . $margin . ': 5px;">';
	if ($disabled) {
		echo '<span class="label" style="color: #aaaaaa;">';
	}
	else {
		echo '<span class="label" style="color: #000000;">';
	}
	echo $label;
	echo '</span>';
	echo '</div>';
}

function visual_map_print_item($layoutData) {
	global $config;
	
	require_once ($config["homedir"] . '/include/functions_graph.php');
	
	$width = $layoutData['width'];
	$height = $max_percentile = $layoutData['height'];
	$top = $layoutData['pos_y'];
	$left = $layoutData['pos_x'];
	$id = $layoutData['id'];
	$color = $layoutData['label_color'];
	$label = $layoutData['label'];
	$id_module = $layoutData['id_agente_modulo'];
	$type = $layoutData['type'];
	$period = $layoutData['period'];
	
	$sizeStyle = '';
	$borderStyle = '';
	$imageSize = '';
	
	$text = '<span id="text_' . $id . '" class="text">' . $label . '</span>';
	
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
		default:
			//Unknown
			// Default is Blue (Other)
			$colorStatus = "#5A5AFF";
			break;
	}
	
	switch ($type) {
		case STATIC_GRAPH:
			if ($layoutData['image'] != null) {
				$img = visual_map_get_image_status_element($layoutData);
				if (substr($img,0,1) == '4') {
					$borderStyle ='border: 2px solid #ffa300;';
					$img = substr_replace($img, '', 0,1);
				}
				$imgSizes = getimagesize($img);
			}
			if (($width != 0) && ($height != 0)) {
				$sizeStyle = 'width: ' . $width . 'px; height: ' . $height . 'px;';
				$imageSize = 'width="' . $width . '" height="' . $height . '"';
			}
			echo '<div id="' . $id . '" class="item static_graph" style="left: 0px; top: 0px; text-align: center; color: ' . $color . '; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top . 'px; margin-left: ' . $left . 'px;">';
			if ($layoutData['image'] != null) {
				if (($width != 0) && ($height != 0)) 
					echo html_print_image($img, true, array("class" => "image", "id" => "image_" . $id, "width" => "$width", "height" => "$height", "style" => $borderStyle));
				else
					echo html_print_image($img, true, array("class" => "image", "id" => "image_" . $id, "style" => $borderStyle));
				echo '<br />';
			}
			echo $text;
			echo "</div>";
			break;
		case PERCENTILE_BAR:
		case PERCENTILE_BUBBLE:
			$module_value = db_get_sql ('SELECT datos FROM tagente_estado WHERE id_agente_modulo = ' . $id_module);
			$value_text = false;
			if ($layoutData['image'] == 'percent') {
				$value_text = false;
			}
			elseif ($layoutData['image'] == 'value') {
				$unit_text = db_get_sql ('SELECT unit FROM tagente_modulo WHERE id_agente_modulo = ' . $id_module);
				$unit_text = trim(io_safe_output($unit_text));
				
				$value_text = format_for_graph($module_value, 2);
				if (!empty($unit_text))
					$value_text .= " " . $unit_text;
			}
			
			if ( $max_percentile > 0)
				$percentile = format_numeric($module_value / $max_percentile * 100, 0);
			else
				$percentile = 100;
			
			echo '<div id="' . $id . '" class="item percentile_item" style="left: 0px; top: 0px; color: ' . $color . '; text-align: center; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top .  'px; margin-left: ' . $left .  'px;">';
			echo $text . '<br />';
			
			ob_start();
			if ($type == PERCENTILE_BUBBLE) {
				echo progress_bubble($percentile, $width, $width, '', 1, $value_text, $colorStatus);
			}
			else {
				echo progress_bar($percentile, $width, 15, '', 1, $value_text, $colorStatus);
			}
			$img = ob_get_clean();
			$img = str_replace('>', 'class="image" id="image_' . $id . '" />', $img);
			echo $img;
			echo '</div>';
			
			break;
		case MODULE_GRAPH:
			$img = grafico_modulo_sparse($id_module, $period, 0, $width,
				$height, '', null, false, 1, false, 0, '', 0, 0, true, true);
			$img = str_replace('>', 'class="image" id="image_' . $id . '" />', $img);
			
			echo '<div id="' . $id . '" class="item module_graph" style="left: 0px; top: 0px; color: ' . $color . '; text-align: center; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top .  'px; margin-left: ' . $left .  'px;">';
			echo $text . '<br />'; 
			echo $img;
			echo '</div>';
			break;
		case SIMPLE_VALUE:
		case SIMPLE_VALUE_MAX:
		case SIMPLE_VALUE_MIN:
		case SIMPLE_VALUE_AVG:
			echo '<div id="' . $id . '" class="item simple_value" style="left: 0px; top: 0px; color: ' . $color . '; text-align: center; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top .  'px; margin-left: ' . $left .  'px;">';
			echo $text;
			$value = visual_map_get_simple_value($type, $id_module, $period);
			echo ' <span id="simplevalue_' . $id . '" style="font-weight:bold;">' . $value . '</span>';
			echo '</div>';
			break;
		case LABEL:
			echo '<div id="' . $id . '" class="item label" style="left: 0px; top: 0px; text-align: center; color: ' . $color . '; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top . 'px; margin-left: ' . $left . 'px;">';
			echo $text;
			echo "</div>";
			break;
		case ICON:
			if ($layoutData['image'] != null) {
				$img = visual_map_get_image_status_element($layoutData);
				$imgSizes = getimagesize($img);
			}
			
			if (($width != 0) && ($height != 0)) {
				$sizeStyle = 'width: ' . $width . 'px; height: ' . $height . 'px;';
				$imageSize = 'width="' . $width . '" height="' . $height . '"';
			}
			echo '<div id="' . $id . '" class="item icon" style="left: 0px; top: 0px; text-align: center; color: ' . $color . '; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top . 'px; margin-left: ' . $left . 'px;">';
			if ($layoutData['image'] != null) {
				// If match with protocol://direction 
				if (preg_match('/^(http:\/\/)((.)+)$/i', $text)){
					echo '<a href="' . $label . '">' . '</a>' . '<br />';
				}
				
				if (($width != 0) && ($height != 0)) 
					echo html_print_image($img, true, array("class" => "image", "id" => "image_" . $id, "width" => "$width", "height" => "$height"));
				else
					echo html_print_image($img, true, array("class" => "image", "id" => "image_" . $id));
				echo '<br />';
			}
			echo "</div>";
			break;
	}
	
	//Add the line between elements.
	if ($layoutData['parent_item'] != 0) {
		echo '<script type="text/javascript">';
		echo '$(document).ready (function() {
			lines.push({"id": "' . $id . '" , "node_begin":"' . $layoutData['parent_item'] . '","node_end":"' . $id . '","color":"' . visual_map_get_color_line_status($layoutData) . '"});
		});';
		echo '</script>';
	}
}

/**
 * The function to get simple value type from the value of process type in the form
 * 
 * @param int process simple value from form
 * 
 * @return int type among the constants:
 * SIMPLE_VALUE, SIMPLE_VALUE_MAX, SIMPLE_VALUE_MIN, SIMPLE_VALUE_AVG
 */
function visual_map_get_simple_value_type($process_simple_value) {
	switch ($process_simple_value) {
		case PROCESS_VALUE_NONE:
			return SIMPLE_VALUE;
			break;
		case PROCESS_VALUE_MIN:
			return SIMPLE_VALUE_MIN;
			break;
		case PROCESS_VALUE_MAX:
			return SIMPLE_VALUE_MAX;
			break;
		case PROCESS_VALUE_AVG:
			return SIMPLE_VALUE_AVG;
			break;
	}
}

/**
 * The function to get the simple value of a module 
 * 
 * @param int type of the retrieving choosed among the constants:
 * SIMPLE_VALUE, SIMPLE_VALUE_MAX, SIMPLE_VALUE_MIN, SIMPLE_VALUE_AVG
 * @param int id agent module
 * @param int period The period in seconds for calculate the avg or min or max value.
 * 
 * @return string value retrieved with units
 */
function visual_map_get_simple_value($type, $id_module, $period = SECONDS_1DAY) {
	$unit_text = db_get_sql ('SELECT unit
		FROM tagente_modulo WHERE id_agente_modulo = ' . $id_module);
	$unit_text = trim(io_safe_output($unit_text));
	
	switch ($type) {
		case SIMPLE_VALUE:
			$value = db_get_value ('datos', 'tagente_estado',
				'id_agente_modulo', $id_module);
			$value = format_for_graph($value, 2);
			if (!empty($unit_text))
				$value .= " " . $unit_text;
			return $value;
			break;
		case SIMPLE_VALUE_MAX:
			$value = reporting_get_agentmodule_data_max ($id_module, $period, 0);
			if ($value === false) {
				$value = __('Unknown');
			}
			else {
				$value = format_for_graph($value, 2);
				if (!empty($unit_text))
					$value .= " " . $unit_text;
			}
			return $value;
			break;
		case SIMPLE_VALUE_MIN:
			$value = reporting_get_agentmodule_data_min ($id_module, $period, 0);
			if ($value === false) {
				$value = __('Unknown');
			}
			else {
				$value = format_for_graph($value, 2);
				if (!empty($unit_text))
					$value .= " " . $unit_text;
			}
			return $value;
			break;
		case SIMPLE_VALUE_AVG:
			$value = reporting_get_agentmodule_data_average ($id_module, $period, 0);
			if ($value === false) {
				$value = __('Unknown');
			}
			else {
				$value = format_for_graph($value, 2);
				if (!empty($unit_text))
					$value .= " " . $unit_text;
			}
			return $value;
			break;
	}
}

/**
 * The function to save the new elements of agents make as wizard.
 * 
 * @param array $id_agents The list of id of agents.
 * @param string $image The image to set the elements.
 * @param integer $id_layout The id of visual console to insert the elements.
 * @param integer $range The distance between elements.
 * @param integer $width Width of image.
 * @param integer $height Height of image.
 * 
 * @return string Return the message status to insert DB.
 */
function visual_map_process_wizard_add ($id_agents, $image, $id_layout, $range,
	$width = 0, $height = 0, $period, $process_value, $percentileitem_width,
	$max_value, $type_percentile, $value_show, $type) {
	if (empty ($id_agents)) {
		print_error_message (__('No agents selected'));
		return false;
	}
	
	$id_agents = (array) $id_agents;
	
	$error = false;
	$pos_y = 10;
	$pos_x = 10;
	foreach ($id_agents as $id_agent) {
		if ($pos_x > 600) {
			$pos_x = 10;
			$pos_y = $pos_y + $range;
		}
		
		$value_height = $height;
		$value_image = $image;
		$value_type = $type;
		switch ($type) {
			case PERCENTILE_BAR:
			case PERCENTILE_BUBBLE:
				$value_height = $max_value;
				$value_image = $value_show;
				if ($type_percentile == 'percentile') {
					$value_type = PERCENTILE_BAR;
				}
				else {
					$value_type = PERCENTILE_BUBBLE;
				}
				break;
			case SIMPLE_VALUE:
				$value_type = $process_value;
				break;
		}
		
		$values = array ('type' => $value_type,
			'id_layout' => $id_layout,
			'pos_x' => $pos_x,
			'pos_y' => $pos_y,
			'label' => agents_get_name ($id_agent),
			'image' => $value_image,
			'id_agent' => $id_agent,
			'width' => $width,
			'period' => $period,
			'height' => $value_height,
			'label_color' => '#000000');
		
		db_process_sql_insert ('tlayout_data', $values);
		
		$pos_x = $pos_x + $range;
	}
	
	$return = ui_print_success_message (__('Agent successfully added to layout'), '', true);
	
	return $return;
}

/**
 * The function to save the new elements of modules make as wizard.
 * 
 * @param array $id_modules The list of id of modules.
 * @param string $image The image to set the elements.
 * @param integer $id_layout The id of visual console to insert the elements.
 * @param integer $range The distance between elements.
 * @param integer $width Width of image.
 * @param integer $height Height of image.
 * 
 * @return string Return the message status to insert DB.
 */
function visual_map_process_wizard_add_modules ($id_modules, $image, $id_layout,
	$range, $width = 0, $height = 0, $period, $process_value, $percentileitem_width,
	$max_value, $type_percentile, $value_show, $label_type, $type) {
	if (empty ($id_modules)) {
		$return = ui_print_error_message (__('No modules selected'), '', true);
		return $return;
	}
	
	$id_modules = (array) $id_modules;
	
	$error = false;
	$pos_y = 10;
	$pos_x = 10;
	
	foreach ($id_modules as $id_module) {
		if ($pos_x > 600) {
			$pos_x = 10;
			$pos_y = $pos_y + $range;
		}
		
		$id_agent = modules_get_agentmodule_agent ($id_module);
		
		
		$value_height = $height;
		$value_image = $image;
		$value_type = $type;
		$value_width = $width;
		switch ($type) {
			case PERCENTILE_BAR:
			case PERCENTILE_BUBBLE:
				$value_height = $max_value;
				$value_width = $percentileitem_width;
				$value_image = $value_show;
				if ($type_percentile == 'percentile') {
					$value_type = PERCENTILE_BAR;
				}
				else {
					$value_type = PERCENTILE_BUBBLE;
				}
				break;
			case SIMPLE_VALUE:
				$value_image = '';
				switch ($process_value) {
					case PROCESS_VALUE_NONE:
						$value_type = SIMPLE_VALUE;
						break;
					case PROCESS_VALUE_MIN:
						$value_type = SIMPLE_VALUE_MIN;
						break;
					case PROCESS_VALUE_MAX:
						$value_type = SIMPLE_VALUE_MAX;
						break;
					case PROCESS_VALUE_AVG:
						$value_type = SIMPLE_VALUE_AVG;
						break;
				}
				break;
		}
		
		switch ($label_type) {
			case 'agent_module':
			default:
				$agent_label = ui_print_truncate_text(agents_get_name ($id_agent), 'agent_small', false, true, false, '…', false);
				$module_label = ui_print_truncate_text(modules_get_agentmodule_name($id_module), 'module_small', false, true, false, '…', false);
				$label = $agent_label . " - " . $module_label;
				break;
			case 'module':
				$module_label = ui_print_truncate_text(modules_get_agentmodule_name($id_module), 'module_small', false, true, false, '…', false);
				$label = $module_label;
				break;
			case 'agent':
				$agent_label = ui_print_truncate_text(agents_get_name ($id_agent), 'agent_small', false, true, false, '…', false);
				$label = $agent_label;
				break;
			case 'none':
				$label = '';
				break;
		}
		$label = io_safe_input($label);
		
		$values = array ('type' => $value_type,
			'id_layout' => $id_layout,
			'pos_x' => $pos_x,
			'pos_y' => $pos_y,
			'label' => $label,
			'image' => $value_image,
			'id_agent' => $id_agent,
			'id_agente_modulo' => $id_module,
			'width' => $value_width,
			'period' => $period,
			'height' => $value_height,
			'label_color' => '#000000');
		
		db_process_sql_insert ('tlayout_data', $values);
		
		$pos_x = $pos_x + $range;
	}
	
	$return = ui_print_success_message (__('Modules successfully added to layout'), '', true);
	
	return $return;
}

/**
 * The function to save the new elements of agents make as wizard.
 * 
 * @param array $id_agents The list of id of agents.
 * @param string $image The image to set the elements.
 * @param integer $id_layout The id of visual console to insert the elements.
 * @param integer $range The distance between elements.
 * @param integer $width Width of image.
 * @param integer $height Height of image.
 * 
 * @return string Return the message status to insert DB.
 */
function visual_map_process_wizard_add_agents ($id_agents, $image, $id_layout,
	$range, $width = 0, $height = 0, $period, $process_value, $percentileitem_width,
	$max_value, $type_percentile, $value_show, $label_type, $type) {
	
	if (empty ($id_agents)) {
		$return = ui_print_error_message (__('No agents selected'), '', true);
		return $return;
	}
	
	$id_agents = (array) $id_agents;
	
	$error = false;
	$pos_y = 10;
	$pos_x = 10;
	
	foreach ($id_agents as $id_agent) {
		if ($pos_x > 600) {
			$pos_x = 10;
			$pos_y = $pos_y + $range;
		}
		
		$value_height = $height;
		$value_image = $image;
		$value_type = $type;
		$value_width = $width;
		
		switch ($type) {
			case PERCENTILE_BAR:
			case PERCENTILE_BUBBLE:
				$value_height = $max_value;
				$value_width = $percentileitem_width;
				$value_image = $value_show;
				if ($type_percentile == 'percentile') {
					$value_type = PERCENTILE_BAR;
				}
				else {
					$value_type = PERCENTILE_BUBBLE;
				}
				break;
			case SIMPLE_VALUE:
				$value_image = '';
				switch ($process_value) {
					case PROCESS_VALUE_NONE:
						$value_type = SIMPLE_VALUE;
						break;
					case PROCESS_VALUE_MIN:
						$value_type = SIMPLE_VALUE_MIN;
						break;
					case PROCESS_VALUE_MAX:
						$value_type = SIMPLE_VALUE_MAX;
						break;
					case PROCESS_VALUE_AVG:
						$value_type = SIMPLE_VALUE_AVG;
						break;
				}
				break;
		}
		
		switch ($label_type) {
			case 'agent':
				$agent_label = ui_print_truncate_text(agents_get_name ($id_agent), 'agent_small', false, true, false, '…', false);
				$label = $agent_label;
				break;
			case 'none':
				$label = '';
				break;
		}
		$label = io_safe_input($label);
		
		$values = array ('type' => $value_type,
			'id_layout' => $id_layout,
			'pos_x' => $pos_x,
			'pos_y' => $pos_y,
			'label' => $label,
			'image' => $value_image,
			'id_agent' => $id_agent,
			'id_agente_modulo' => 0,
			'width' => $value_width,
			'period' => $period,
			'height' => $value_height,
			'label_color' => '#000000');
		
		db_process_sql_insert ('tlayout_data', $values);
		
		$pos_x = $pos_x + $range;
	}
	
	$return = ui_print_success_message (__('Agents successfully added to layout'), '', true);
	
	return $return;
}


/**
 * Get the color of line between elements in the visual map.
 * 
 * @param array $layoutData The row of element in DB.
 * 
 * @return string The color as hexadecimal color in html.
 */
function visual_map_get_color_line_status($layoutData) {
	if (($layoutData['type'] == 5) || ($layoutData['type'] == 4)) {
		//ICON ELEMENT OR LABEL ELEMENT
		$color = "#cccccc";
	}
	else {
		switch (visual_map_get_status_element($layoutData)) {
			case 3:
				$color = "#cccccc"; // Gray
				break;
			case 2:
				$color = "#20f6f6"; // Yellow
				break;
			case 0:
				$color = "#00ff00"; // Green
				break;
			case 4:
			case 1:
				$color = "#ff0000"; // Red
				break;
		}
	}
	
	return $color;
}

/**
 * Get image of element in the visual console with status.
 * 
 * @param array $layoutData The row of element in DB.
 * 
 * @return string The image with the relative path to pandora console directory.
 */
function visual_map_get_image_status_element($layoutData) {
	$img = "images/console/icons/" . $layoutData["image"];
	
	if ($layoutData['type'] == 5) {
		//ICON ELEMENT
		$img .= ".png";
	}
	else {
		switch (visual_map_get_status_element($layoutData)) {
			case 1:
				//Critical (BAD)
				$img .= "_bad.png";
				break;
			case 4:
				//Critical (ALERT)
				$img .= "_bad.png";
				break;
			case 0:
				//Normal (OK)
				$img .= "_ok.png";
				break;
			case 2:
				//Warning
				$img .= "_warning.png";
				break;
			case 3:
				//Unknown
			default:
				$img .= ".png";
				// Default is Grey (Other)
		}
	}
	
	return $img;
}

/**
 * Get the status of element in visual console. Check the agent state or
 * module or layout linked.
 * 
 * @param array $layoutData The row of element in DB.
 * 
 * @return integer 
 */
function visual_map_get_status_element($layoutData) {
	//Linked to other layout ?? - Only if not module defined
	if ($layoutData['id_layout_linked'] != 0) {
		$status = visual_map_get_layout_status ($layoutData['id_layout_linked']);
	}
	else if ($layoutData["type"] == 0) {
		//Single object
		
		//Status for a simple module
		if ($layoutData['id_agente_modulo'] != 0) {
			$status = modules_get_agentmodule_status ($layoutData['id_agente_modulo']);
		
		//Status for a whole agent, if agente_modulo was == 0
		}
		else if ($layoutData['id_agent'] != 0) {
			$status = agents_get_status ($layoutData["id_agent"]);
			if ($status == -1) // agents_get_status return -1 for unknown!
				$status = 3;
		}
		else {
			$status = 3;
			$id_agent = 0;
		}
	}
	else {
		//If it's a graph, a progress bar or a data tag, ALWAYS report status OK
		//(=0) to avoid confussions here.
		$status = 0;
	}
	
	return $status;
}

/**
 * Prints visual map
 *
 * @param int $id_layout Layout id
 * @param bool $show_links
 * @param bool $draw_lines
 */
function visual_map_print_visual_map ($id_layout, $show_links = true, $draw_lines = true, $width = null, $height = null, $home_url = '') {
	//TODO: USE THE SAME CODE THAT THE VISUAL MAP EDITOR
	global $config;
	
	$layout = db_get_row ('tlayout', 'id', $id_layout);
	
	$resizedMap = false;
	$proportion = 1;
	if (!is_null($width)) {
		$resizedMap = true;
		if (!is_null($height)) {
			$mapWidth = $width;
			$mapHeight = $height;
		}
		else {
			$mapWidth = $width;
			$proportion = $width / $layout["width"];
			$mapHeight = $proportion * $layout["height"];
		}
		$backgroundImage = $config['homeurl'].'/include/Image/image_functions.php?getFile=1&thumb=1&thumb_size=' . $mapWidth . 'x' . $mapHeight . '&file=' .
			$config['homeurl'] . '/' . 'images/console/background/'.io_safe_input ($layout["background"]);
	}
	else {
		$mapWidth = $layout["width"];
		$mapHeight = $layout["height"];
		$backgroundImage =  $config['homeurl'].'/images/console/background/'.io_safe_input ($layout["background"]);
	}
	
	echo '<div id="layout_map"
		style="z-index: 0; position:relative; width:'.$mapWidth.'px; height:'.$mapHeight.'px;">';
	echo "<img src='" . $backgroundImage . "' width='100%' height='100%' />";
	$layout_datas = db_get_all_rows_field_filter ('tlayout_data', 'id_layout', $id_layout);
	$lines = array ();
	
	if ($layout_datas !== false) {
		foreach ($layout_datas as $layout_data) {
			$id_agent = 0;
			$layout_data['label'] = io_safe_output($layout_data['label']);
			// ****************************************************************
			// Get parent status (Could be an agent, module, map, others doesnt have parent info)
			// ****************************************************************
			
			// Pending delete and disable modules must be ignored
			$delete_pending_module = db_get_value ("delete_pending", "tagente_modulo", "id_agente_modulo", $layout_data["id_agente_modulo"]);
			$disabled_module = db_get_value ("disabled", "tagente_modulo", "id_agente_modulo", $layout_data["id_agente_modulo"]);
			
			if($delete_pending_module == 1 || $disabled_module == 1)
				continue;
				
			if ($layout_data["parent_item"] != 0){
				$id_agent_module_parent = db_get_value ("id_agente_modulo", "tlayout_data", "id", $layout_data["parent_item"]);
				$id_agent_parent = db_get_value ("id_agent", "tlayout_data", "id", $layout_data["parent_item"]);
				$id_layout_linked = db_get_value ("id_layout_linked", "tlayout_data", "id", $layout_data["parent_item"]); 
				
				// Module
				if ($id_agent_module_parent != 0) {
					$status_parent = modules_get_agentmodule_status ($id_agent_module_parent);
				// Agent
				} 
				elseif ($id_agent_parent  != 0) {
					$status_parent = agents_get_status ($id_agent_parent);
				}
				// Another layout/map
				elseif ($id_layout_linked != 0) {
					$status_parent = visual_map_get_layout_status ($id_layout_linked);
				}
				
				else { 
					$status_parent = 3;
				}
				
			}
			else {
				$id_agent_module_parent = 0;
				$status_parent = 3;
			}
			
			
			// ****************************************************************	
			// Get STATUS of current object
			// ****************************************************************
			
			// Linked to other layout ?? - Only if not module defined
			if ($layout_data['id_layout_linked'] != 0) {
				$status = visual_map_get_layout_status ($layout_data['id_layout_linked']);
			
			// Single object
			}
			elseif (($layout_data["type"] == 0)
				|| ($layout_data["type"] == 3)
				|| ($layout_data["type"] == 9)
				|| ($layout_data["type"] == 4)) {
				// Status for a simple module
				if ($layout_data['id_agente_modulo'] != 0) {
					$status = modules_get_agentmodule_status ($layout_data['id_agente_modulo']);
					$id_agent = db_get_value ("id_agente", "tagente_estado", "id_agente_modulo", $layout_data['id_agente_modulo']);
					//We need to get the diference between warning and critical alerts!!!
					$real_status = db_get_row ("tagente_estado", "id_agente_modulo", $layout_data["id_agente_modulo"]);	
					if ($real_status['estado'] == 2) {
						//This module has an alert fired and warning status
						$status = 10;
					}
				// Status for a whole agent, if agente_modulo was == 0
				}
				elseif ($layout_data['id_agent'] != 0) {
					$status = agents_get_status ($layout_data["id_agent"]);
					if ($status == -1) // agents_get_status return -1 for unknown!
						$status = 3; 
					$id_agent = $layout_data["id_agent"];
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
			
			// ****************************************************************
			// STATIC IMAGE (type = 0)
			// ****************************************************************
			if ($layout_data['type'] == 0) {
				// Link image
				//index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=1
				if ($status == 0) // Bad monitor
					$z_index = 3;
				elseif ($status == 2) // Warning
					$z_index = 2;
				elseif ($status == 4) // Alert
					$z_index = 4;
				else
					$z_index = 1; // Print BAD over good
				
				// Draw image
				if ($resizedMap)
					echo '<div style="left: 0px; top: 0px; text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.((integer)($proportion * $layout_data['pos_x'])).'px; margin-top:'.((integer)($proportion * $layout_data['pos_y'])).'px;" id="layout-data-' . $layout_data['id'] . '" class="layout-data">';
				else
					echo '<div style="left: 0px; top: 0px; text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">'; 
				
				if (!isset ($id_agent))
					$id_agent = 0;
				
				if ($show_links) {
					if (($id_agent > 0) && ($layout_data['id_layout_linked'] == "" || $layout_data['id_layout_linked'] == 0)) {
						
						//Extract id service if it is a prediction module.
						$id_service = db_get_value_filter('custom_integer_1',
							'tagente_modulo',
							array('id_agente_modulo' => $layout_data['id_agente_modulo'],
								'prediction_module' => 1));
						
						if ($id_service === false) {
							$id_service = 0;
						}
						
						if ($id_service != 0) {
							//Link to an service page
							echo '<a href="'.$config['homeurl'].'/index.php?sec=services&sec2=enterprise/operation/services/services&id_service=' . 
								$id_service . '&offset=0">';
						}
						else {
							// Link to an agent
							echo '<a href="'.$config['homeurl'].'/index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent.'">';
						}
					}
					elseif ($layout_data['id_layout_linked'] > 0) {
					
						// Link to a map
						echo '<a href="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data["id_layout_linked"].'">';
					
					}
					else {
						// A void object
						echo '<a href="#">';
					}
				}
				
				$img_style = array ();
				$img_style["title"] = $layout_data["label"];
				
				if (!empty ($layout_data["width"])) {
					$img_style["width"] = $layout_data["width"];
				} 
				if (!empty ($layout_data["height"])) {
					$img_style["height"] = $layout_data["height"];
				}
				
				$img = "images/console/icons/".$layout_data["image"];
				
				switch ($status) {
					case 1:
						//Critical (BAD)
						$img .= "_bad.png";
						break;
					case 4:
						//Critical (ALERT)
						$img = "4" . $img . "_bad.png";
						break;
					case 10:
						//Warning (with ALERT)
						$img = "4" . $img . "_warning.png";
						break;
					case 0:
						//Normal (OK)
						$img .= "_ok.png";
						break;
					case 2:
						//Warning
						$img .= "_warning.png";
						break;
					case 3:
						//Unknown
					default:
						$img .= ".png";
						// Default is Grey (Other)
						break;
				}
				
				$borderStyle = '';
				if (substr($img,0,1) == '4') {
					$img_style['border'] ='2px solid #ffa300;';
					$img = substr_replace($img, '', 0,1);
				}
				
				if (is_file($img))
					$infoImage = getimagesize($img);
				
				if (!empty ($layout_data["width"])) {
					if ($resizedMap)
						$img_style["width"] = (integer)($proportion * $layout_data["width"]);
					else
						$img_style["width"] = $layout_data["width"];
				}
				else
					$img_style["width"] = (integer)($proportion * $infoImage[0]);
				
				if (!empty ($layout_data["height"])) {
					if ($resizedMap)
						$img_style["height"] = (integer)($proportion * $img_style["height"]);
					else
						$img_style["height"] = $layout_data["height"];
				}
				else
					$img_style["height"] = (integer)($proportion * $infoImage[1]);
				
				html_print_image ($img, false, $img_style);
				
				echo "</a>";
				
				// Print label if valid label_color (only testing for starting with #) otherwise print nothing
				if ($layout_data['label_color'][0] == '#') {
					echo "<br />";
					echo $layout_data['label'];	
				}
				echo "</div>";
			}
			
			switch ($layout_data['type']) {
				case 4:
					// ****************************************************************
					// LABEL (type = 4)
					// ****************************************************************
					$z_index = 4;
					if ($resizedMap)
						echo '<div style="left: 0px; top: 0px; text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.((integer)($proportion * $layout_data['pos_x'])).'px; margin-top:'.((integer)($proportion * $layout_data['pos_y'])).'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					else
						echo '<div style="left: 0px; top: 0px; text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					
					$endTagA = false;
					if ($show_links) {
						if (!isset($id_agent)) $id_agent = 0;
						if (($id_agent > 0) && ($layout_data['id_layout_linked'] == "" || $layout_data['id_layout_linked'] == 0)) {
							// Link to an agent
							echo '<a style="' . ($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '') . '" href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent.'">';
							$endTagA = true;
						} 
						elseif ($layout_data['id_layout_linked'] > 0) {
							// Link to a map
							echo '<a style="' . ($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '') . '" href="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data["id_layout_linked"].'">';
							$endTagA = true;
						}
					}
					if ($layout_data['label_color'][0] == '#') {
						echo "<br />";
						echo $layout_data['label'];
					}
					if ($endTagA) echo "</a>";
					echo "</div>";
					break;
				case 5:
					// ****************************************************************
					// ICON (type = 5)
					// ****************************************************************
					$z_index = 4;
					if ($resizedMap)
						echo '<div style="left: 0px; top: 0px; text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.((integer)($proportion * $layout_data['pos_x'])).'px; margin-top:'.((integer)($proportion * $layout_data['pos_y'])).'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					else
						echo '<div style="left: 0px; top: 0px; text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					
					$endTagA = false;
					if ($show_links) {
						if (!isset($id_agent)) $id_agent = 0;
						if (($id_agent > 0) && ($layout_data['id_layout_linked'] == "" || $layout_data['id_layout_linked'] == 0)) {
							// Link to an agent
							echo '<a style="' . ($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '') . '" href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent.'">';
							$endTagA = true;
						} 
						elseif ($layout_data['id_layout_linked'] > 0) {
							// Link to a map
							echo '<a style="' . ($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '') . '" href="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data["id_layout_linked"].'">';
							$endTagA = true;
						}
						elseif (preg_match('/^(http:\/\/)((.)+)$/i', $layout_data['label'])){
							// Link to an URL
							echo '<a style="' . ($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '') . '" href="' . $layout_data['label'] .'">';
							$endTagA = true;
						}
					}
					
					$img_style = array ();
					$img_style["title"] = $layout_data["label"];
					
					if (!empty ($layout_data["width"])) {
						$img_style["width"] = $layout_data["width"];
					} 
					if (!empty ($layout_data["height"])) {
						$img_style["height"] = $layout_data["height"];
					}
					
					$img = "images/console/icons/".$layout_data["image"] . ".png";
					
					if (is_file($img))
						$infoImage = getimagesize($img);
					
					if (!empty ($layout_data["width"])) {
						if ($resizedMap)
							$img_style["width"] = (integer)($proportion * $layout_data["width"]);
						else
							$img_style["width"] = $layout_data["width"];
					}
					else
						$img_style["width"] = (integer)($proportion * $infoImage[0]);
					
					if (!empty ($layout_data["height"])) {
						if ($resizedMap)
							$img_style["height"] = (integer)($proportion * $img_style["height"]);
						else
							$img_style["height"] = $layout_data["height"];
					}
					else
						$img_style["height"] = (integer)($proportion * $infoImage[1]);
					
					html_print_image ($img, false, $img_style);
					
					if ($endTagA) echo "</a>";
					
					echo "</div>";
					break;
				case 2:
				case 6:
				case 7:
				case 8:
					// ****************************************************************
					// SIMPLE DATA VALUE (type = 2)
					// ****************************************************************
					$unit_text = db_get_sql ('SELECT unit
						FROM tagente_modulo
						WHERE id_agente_modulo = ' . $layout_data['id_agente_modulo']);
					$unit_text = trim(io_safe_output($unit_text));
					
					if ($resizedMap)
						echo '<div style="left: 0px; top: 0px; z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.((integer)($proportion *$layout_data['pos_x'])).'px; margin-top:'.((integer)($proportion *$layout_data['pos_y'])).'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					else
						echo '<div style="left: 0px; top: 0px; z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					
					$endTagA = false;
					
					if ($show_links) {
						//Extract id service if it is a prediction module.
						$id_service = db_get_value_filter('custom_integer_1',
							'tagente_modulo',
							array('id_agente_modulo' => $layout_data['id_agente_modulo'],
								'prediction_module' => 1));
						
						if ($id_service === false) {
							$id_service = 0;
						}
						
						if ($id_service != 0) { 
							//Link to an service page
							echo '<a href="index.php?sec=services&sec2=enterprise/operation/services/services&id_service=' . 
								$id_service . '&offset=0">';
							$endTagA = true;
						}
						elseif ($layout_data['id_layout_linked'] > 0) {
						
							// Link to a map
							echo '<a href="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data["id_layout_linked"].'">';
							$endTagA = true;
						}
					}
					
					echo '<strong>'.$layout_data['label']. ' ';
					//TODO: change interface to add a period parameter, now is set to 1 day
					switch ($layout_data['type']) {
						case 2:
							$value = db_get_value ('datos', 'tagente_estado', 'id_agente_modulo', $layout_data['id_agente_modulo']);
							$value = format_for_graph($value, 2);
							if (!empty($unit_text))
								$value .= " " . $unit_text;
							echo $value;
							break;
						case 6:
							$value = reporting_get_agentmodule_data_max ($layout_data['id_agente_modulo'], $layout_data['period'], 0);
							if ($value === false) {
								$value = __('Unknown');
							}
							else {
								$value = format_for_graph($value, 2);
								if (!empty($unit_text))
									$value .= " " . $unit_text;
							}
							echo $value;
							break;
						case 7:
							$value = reporting_get_agentmodule_data_min ($layout_data['id_agente_modulo'], $layout_data['period'], 0);
							if ($value === false) {
								$value = __('Unknown');
							}
							else {
								$value = format_for_graph($value, 2);
								if (!empty($unit_text))
									$value .= " " . $unit_text;
							}
							echo $value;
							break;
						case 8:
							$value = reporting_get_agentmodule_data_average($layout_data['id_agente_modulo'], $layout_data['period'], 0);
							if ($value === false) {
								$value = __('Unknown');
							}
							else {
								$value = format_for_graph($value, 2);
								if (!empty($unit_text))
									$value .= " " . $unit_text;
							}
							echo $value;
							break;
					}	
					echo '</strong>';
					
					if ($endTagA) echo '</a>';
					
					echo '</div>';
					break;
				case 3:
					// ****************************************************************
					// Progress bar
					// ****************************************************************	
				case 9:
					// ****************************************************************
					// Progress bubble
					// ****************************************************************	
					
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
					
					if ($resizedMap)
						echo '<div style="left: 0px; top: 0px; text-align: center; z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.((integer)($proportion *$layout_data['pos_x'])).'px; margin-top:'.((integer)($proportion *$layout_data['pos_y'])).'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					else
						echo '<div style="left: 0px; top: 0px; text-align: center; z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					$valor = db_get_sql ('SELECT datos FROM tagente_estado WHERE id_agente_modulo = '.$layout_data['id_agente_modulo']);
					$width = $layout_data['width'];
					if ( $layout_data['height'] > 0)
						$percentile = $valor / $layout_data['height'] * 100;
					else
						$percentile = 100;
					
					$percentile = round($percentile);
					
					$endTagA = false;
					
					echo $layout_data['label'];
					echo "<br>";
					
					if ($show_links) {
						if (($id_agent > 0) && ($layout_data['id_layout_linked'] == "" || $layout_data['id_layout_linked'] == 0)) {
							
							//Extract id service if it is a prediction module.
							$id_service = db_get_value_filter('custom_integer_1',
								'tagente_modulo',
								array('id_agente_modulo' => $layout_data['id_agente_modulo'],
									'prediction_module' => 1));
							
							if ($id_service === false) {
								$id_service = 0;
							}
							
							if ($id_service != 0) {
								//Link to an service page
								echo '<a href="index.php?sec=services&sec2=enterprise/operation/services/services&id_service=' . 
									$id_service . '&offset=0">';
								$endTagA = true;
							}
							else {
								// Link to an agent
								echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent.'">';
								$endTagA = true;
							}
						}
						elseif ($layout_data['id_layout_linked'] > 0) {
						
							// Link to a map
							echo '<a href="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data["id_layout_linked"].'">';
							$endTagA = true;
						
						}
						else {
							// A void object
							echo '<a href="#">';
							$endTagA = true;
						}
					}
					
					$value_text = false;
					if ($layout_data['image'] == 'percent') {
						$value_text = false;
					}
					elseif ($layout_data['image'] == 'value') {
						$unit_text = db_get_sql ('SELECT unit FROM tagente_modulo WHERE id_agente_modulo = ' . $layout_data['id_agente_modulo']);
						$unit_text = trim(io_safe_output($unit_text));
						
						$value_text = format_for_graph($valor, 2);
						if (!empty($unit_text))
							$value_text .= " " . $unit_text;
					}
					
					if ($layout_data['type'] == 9) {
						if ($resizedMap)
							echo progress_bubble($percentile, ((integer)($proportion * $width)), $width, '', 1, $value_text, $colorStatus);
						else
							echo progress_bubble($percentile, $width, $width, '', 1, $value_text, $colorStatus);
					}
					else {
						if ($resizedMap)
							echo progress_bar($percentile, ((integer)($proportion * $width)), 15, '', 1, $value_text, $colorStatus);
						else
							echo progress_bar($percentile, $width, 15, '', 1, $value_text, $colorStatus);
					}
					
					if ($endTagA) echo '</a>';
					
					echo '</div>';
					break;
				case 1;
					// ****************************************************************
					// Single module graph
					// ****************************************************************
					// SINGLE GRAPH (type = 1)
					
					if ($resizedMap) {
						$layout_data['width'] = ((integer)($proportion * $layout_data['width']));
						$layout_data['height'] = ((integer)($proportion * $layout_data['height']));
						$layout_data['pos_x'] = ((integer)($proportion * $layout_data['pos_x']));
						$layout_data['pos_y'] = ((integer)($proportion * $layout_data['pos_y']));
					}
					
					echo '<div style="left: 0px; top: 0px; text-align: center; z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					
					echo $layout_data['label'];
					echo "<br>";
					
					$endTagA = false;
					
					if ($show_links) {
						if (($layout_data['id_layout_linked'] == "") || ($layout_data['id_layout_linked'] == 0)) {
							
							//Extract id service if it is a prediction module.
							$id_service = db_get_value_filter('custom_integer_1',
								'tagente_modulo',
								array('id_agente_modulo' => $layout_data['id_agente_modulo'],
									'prediction_module' => 1));
							
							if ($id_service === false) {
								$id_service = 0;
							}
							
							if ($id_service != 0) { 
								//Link to an service page
								echo '<a href="index.php?sec=services&sec2=enterprise/operation/services/services&id_service=' . 
									$id_service . '&offset=0">';
							}
							else {
								echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$layout_data["id_agent"].'&amp;tab=data">';
							}
						}
						else {
							echo '<a href="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data['id_layout_linked'].'">';
						}
					}
					
					// ATTENTION: DO NOT USE &amp; here because is bad-translated and doesnt work
					// resulting fault image links :(
					echo grafico_modulo_sparse ($layout_data['id_agente_modulo'], $layout_data['period'],
						false, $layout_data['width'], $layout_data['height'],
						'', null, false, 1, false, 0, '', 0, 0, true, true, $home_url, 1);
					
					echo "</a>";
					echo "</div>";
					break;
			}
			
			// ****************************************************************
			// Lines joining objects
			// ****************************************************************
			// Get parent relationship - Create line data
			if ($layout_data["parent_item"] != "" && $layout_data["parent_item"] != 0) {
				$line['id'] = $layout_data['id'];
				$line['node_begin'] = 'layout-data-'.$layout_data["parent_item"];
				$line['node_end'] = 'layout-data-'.$layout_data["id"];
				switch ($status_parent) {
					default:
					case 3:
						$line["color"] = "#ccc"; // Gray
						break;
					case 2:
						$line["color"] = "#20f6f6"; // Yellow
						break;
					case 0:
						$line["color"] = "#00ff00"; // Green
						break;
					case 4:
					case 1:
						$line["color"] = "#ff0000"; // Red
						break;
				}
				array_push ($lines, $line);
			}
		}
	}
	
	if ($draw_lines) {
		/* If you want lines in the map, call using Javascript:
		 draw_lines (lines, id_div);
		 on body load, where id_div is the id of the div which holds the map */
		echo '<script type="text/javascript">/* <![CDATA[ */'."\n";
		
		if ($resizedMap) {
			echo 'var resize_map = 1;'."\n";
		}
		else {
			echo 'var resize_map = 0;'."\n";
		}
		
		echo 'var lines = Array ();'."\n";
		
		foreach ($lines as $line) {
			echo 'lines.push (eval (' . json_encode ($line) . '));' . "\n";
		}
		echo '/* ]]> */</script>';
	}
	// End main div
	echo "</div>";
}

/**
 * @return array Layout data types
 */
function visual_map_get_layout_data_types () {
	$types = array ();
	$types[0] = __('Static graph');
	$types[1] = __('Module graph');
	$types[2] = __('Simple value');
	$types[3] = __('Percentile bar');
	
	return $types;
}

/**
 * Get a list with the layouts for a user.
 *
 * @param int User id.
 * @param bool Wheter to return all the fields or only the name (to use in
 * html_print_select() directly)
 * @param array Additional filters to filter the layouts.
 * @param bool Whether to return All group or not.
 *
 * @return array A list of layouts the user can see.
 */
function visual_map_get_user_layouts ($id_user = 0, $only_names = false, $filter = false, $returnAllGroup = true) {
	if (! is_array ($filter))
		$filter = array ();
	
	$where = db_format_array_where_clause_sql ($filter);
	
	if ($returnAllGroup)
		$groups = users_get_groups ($id_user);
	else
		$groups = users_get_groups ($id_user, 'IR', true);
	
	if(!empty($groups)) {
		if ($where != '') {
			$where .= ' AND ';
		}
		$where .= sprintf ('id_group IN (%s)', implode (",", array_keys ($groups)));
	}
	
	if($where == '') {
		$where = array();
	}
	
	$layouts = db_get_all_rows_filter ('tlayout', $where);
	
	if ($layouts == false)
		return array ();
	
	$retval = array ();
	foreach ($layouts as $layout) {
		if ($only_names)
			$retval[$layout['id']] = $layout['name'];
		else
			$retval[$layout['id']] = $layout;
	}
	
	return $retval;
}


/** 
 * Get the status of a layout.
 *
 * It gets all the data of the contained elements (including nested
 * layouts), and makes an AND operation to be sure that all the items
 * are OK. If any of them is down, then result is down (0)
 * 
 * @param int Id of the layout
 * @param int Depth (for recursion control)
 * 
 * @return bool The status of the given layout. True if it's OK, false if not.
 */
function visual_map_get_layout_status ($id_layout = 0, $depth = 0) {
	$temp_status = 0;
	$temp_total = 0;
	$depth++; // For recursion depth checking
	
	// TODO: Implement this limit as a configurable item in setup
	if ($depth > 10) {
		return 3; // No status data if we need to exit by a excesive recursion
	}
	
	$id_layout = (int) $id_layout;
	
	$result = db_get_all_rows_filter ('tlayout_data', array ('id_layout' => $id_layout),
		array ('id_agente_modulo', 'parent_item', 'id_layout_linked', 'id_agent', 'type'));
	if ($result === false)
		return 0;
	
	foreach ($result as $rownum => $data) {
		if (($data["id_layout_linked"] == 0 && $data["id_agente_modulo"] == 0 && $data["id_agent"] == 0) || $data['type'] != 0)
			continue;
		// Other Layout (Recursive!)
		if (($data["id_layout_linked"] != 0) && ($data["id_agente_modulo"] == 0)) {
			$status = visual_map_get_layout_status ($data["id_layout_linked"], $depth);
		}
		// Module
		elseif ($data["id_agente_modulo"] != 0) {
			$status = modules_get_agentmodule_status ($data["id_agente_modulo"]);
		
		}
		// Agent
		else {
			$status = agents_get_status ($data["id_agent"]);
		}
		if ($status == 1)
			return 1;
		if ($status > $temp_total)
			$temp_total = $status;
	}
	
	return $temp_total;
}

/**
 * Make a text for the parent select, when the label is not empty put this for
 * the return text. Instead for the empty labels make the text with next form
 * (<Type>) - <name_image> ( <agent_name> - <module_name> ) (<id item>) 
 * 
 * @param string $label The label of item in visual map.
 * @param string $type The label of type in visual map.
 * @param string $image The image of item in visual map.
 * @param string $agent The agent name of item in visual map.
 * @param string $id_module The module name of item in visual map.
 * @param int $idData The id of item in visual map.
 * 
 * @return string The text for the parent.
 */
function visual_map_create_internal_name_item($label = null, $type, $image, $agent = null, $id_module, $idData) {
	$text = '';
	
	if (empty($label))
	{
		switch ($type) {
			case 'module_graph':
			case MODULE_GRAPH:
				$text = __('Module graph');
				break;
			case 'percentile_bar':
			case PERCENTILE_BAR:
				$text = __('Percentile bar');
				break;
			case 'static_graph':
			case STATIC_GRAPH:
				$text = __('Static graph') . " - " .
					$image;
				break;
			case 'simple_value':
			case SIMPLE_VALUE:
				$text = __('Simple Value');
				break;
			case 'label':
			case LABEL:
				$text = __('Label');
				break;
			case 'icon':
			case ICON:
				$text = __('Icon') . " - " .
					$image;
				break;
		}
		
		if (!empty($agent)) {
			$text .= " (" . ui_print_truncate_text($agent, 'agent_small', false);
			
			$moduleName = io_safe_output(db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $id_module));
			if (!empty($moduleName)) {
				$text .= " - " . ui_print_truncate_text($moduleName, 'module_small', false);
			}
			
			$text .= ")"; 
		}
		$text .= ' (' . $idData . ')'; 
	}
	else {
		$text = $label;
	}
	
	return $text;
}

function visual_map_get_items_parents($idVisual) {
	$items = db_get_all_rows_filter('tlayout_data',array('id_layout' => $idVisual));
	if ($items == false) {
		$items = array();
	}
	
	$return = array();
	foreach ($items as $item) {
		$agent = null;
		if ($item['id_agent'] != 0) {
			$agent = io_safe_output(agents_get_name($item['id_agent']));
		}
		
		$return[$item['id']] = visual_map_create_internal_name_item($item['label'],
			$item['type'], $item['image'], $agent, $item['id_agente_modulo'],
			$item['id']);
	}
	
	return $return;
}

/**
 * Get the X axis coordinate of a layout item
 *
 * @param int Id of the layout to get.
 *
 * @return int The X axis coordinate value.
 */
function visual_map_get_layoutdata_x ($id_layoutdata) {
	return (float) db_get_value ('pos_x', 'tlayout_data', 'id', (int) $id_layoutdata);
}

/**
 * Get the Y axis coordinate of a layout item
 *
 * @param int Id of the layout to get.
 *
 * @return int The Y axis coordinate value.
 */
function visual_map_get_layoutdata_y ($id_layoutdata){
	return (float) db_get_value ('pos_y', 'tlayout_data', 'id', (int) $id_layoutdata);
}

?>
