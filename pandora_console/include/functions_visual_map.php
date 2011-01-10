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
 * @subpackage Reporting
 */

function printButtonEditorVisualConsole($idDiv, $label, $float = 'left', $disabled = false, $class= '', $imageButton = false) {
	if ($float == 'left') {
		$margin = 'margin-right';
	}
	else {
		$margin = 'margin-left';
	}
	
	print_button($label, 'button_toolbox2', $disabled, "click2('" . $idDiv . "');", 'class="sub ' . $idDiv . ' ' . $class . '" style="float: ' . $float . ';"', false, $imageButton);
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

function printItemInVisualConsole($layoutData) {
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
	
	switch ($type) {
		case STATIC_GRAPH:
			if ($layoutData['image'] != null) {
				$img = getImageStatusElement($layoutData);
				if(substr($img,0,1) == '4') {
					$borderStyle ='border: 2px solid #ffa300;';
					$img = substr_replace($img, '', 0,1);
				}
				$imgSizes = getimagesize($img);
			}
			if (($width != 0) && ($height != 0)) {
				$sizeStyle = 'width: ' . $width . 'px; height: ' . $height . 'px;';
				$imageSize = 'width="' . $width . '" height="' . $height . '"';
			}
			echo '<div id="' . $id . '" class="item static_graph" style="text-align: center; color: ' . $color . '; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top . 'px; margin-left: ' . $left . 'px;">';
			if ($layoutData['image'] != null) {
				echo '<img class="image" id="image_' . $id . '" src="' . $img . '" ' . $imageSize . ' style="'.$borderStyle.'" /><br />';
			}
			echo $text;
			echo "</div>";
			break;
		case PERCENTILE_BAR:
			$module_value = get_db_sql ('SELECT datos FROM tagente_estado WHERE id_agente_modulo = ' . $id_module);
			
			if ( $max_percentile > 0)
				$percentile = $module_value / $max_percentile * 100;
			else
				$percentile = 100;
			
			$img = '<img class="image" id="image_' . $id . '" src="include/fgraph.php?tipo=progress&height=15&width=' . $width . '&mode=1&percent=' . $percentile . '" />';
			
			echo '<div id="' . $id . '" class="item percentile_bar" style="color: ' . $color . '; text-align: center; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top .  'px; margin-left: ' . $left .  'px;">';
			echo $text . '<br />'; 
			echo $img;
			echo '</div>';
			
			break;
		case MODULE_GRAPH:
			$img = '<img class="image" id="image_' . $id . '" src="include/fgraph.php?tipo=sparse&id=' . $id_module . '&label=' . $label . '&height=' . $height . '&pure=1&width=' . $width . '&period=' . $period . '" />';
			
			echo '<div id="' . $id . '" class="item module_graph" style="color: ' . $color . '; text-align: center; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top .  'px; margin-left: ' . $left .  'px;">';
			echo $text . '<br />'; 
			echo $img;
			echo '</div>';
			break;
		case SIMPLE_VALUE:
			echo '<div id="' . $id . '" class="item simple_value" style="color: ' . $color . '; text-align: center; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top .  'px; margin-left: ' . $left .  'px;">';
			echo $text; 
			echo ' <strong>' . get_db_value ('datos', 'tagente_estado', 'id_agente_modulo', $id_module) . '</strong>';
			echo '</div>';
			break;
		case LABEL:
			echo '<div id="' . $id . '" class="item label" style="text-align: center; color: ' . $color . '; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top . 'px; margin-left: ' . $left . 'px;">';
			echo $text;
			echo "</div>";
			break;
		case ICON:
			if ($layoutData['image'] != null) {
				$img = getImageStatusElement($layoutData);
				$imgSizes = getimagesize($img);
			}
			if (($width != 0) && ($height != 0)) {
				$sizeStyle = 'width: ' . $width . 'px; height: ' . $height . 'px;';
				$imageSize = 'width="' . $width . '" height="' . $height . '"';
			}
			echo '<div id="' . $id . '" class="item icon" style="text-align: center; color: ' . $color . '; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top . 'px; margin-left: ' . $left . 'px;">';
			if ($layoutData['image'] != null) {
				echo '<img class="image" id="image_' . $id . '" src="' . $img . '" ' . $imageSize . ' /><br />';
			}
			echo "</div>";
			break;
	}
	
	//Add the line between elements.
	if ($layoutData['parent_item'] != 0) {
		echo '<script type="text/javascript">';
		echo '$(document).ready (function() {
			lines.push({"id": "' . $id . '" , "node_begin":"' . $layoutData['parent_item'] . '","node_end":"' . $id . '","color":"' . getColorLineStatus($layoutData) . '"});
		});';
		echo '</script>';
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
function process_wizard_add ($id_agents, $image, $id_layout, $range, $width = 0, $height = 0) {
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
		
		process_sql_insert ('tlayout_data',
			array ('id_layout' => $id_layout,
			   'pos_x' => $pos_x,
			   'pos_y' => $pos_y,
			   'label' => get_agent_name ($id_agent),
			   'image' => $image,
			   'id_agent' => $id_agent,
			   'width' => $width,
			   'height' => $height,
			   'label_color' => '#000000')
			);
		
		$pos_x = $pos_x + $range;
	}
	
	$return = print_success_message (__('Agent successfully added to layout'), '', true);
	
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
function process_wizard_add_modules ($id_modules, $image, $id_layout, $range, $width = 0, $height = 0) {
	if (empty ($id_modules)) {
		$return = print_error_message (__('No modules selected'), '', true);
		return false;
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
		
		$id_agent = get_agentmodule_agent ($id_module);
		
		process_sql_insert ('tlayout_data',
			array ('id_layout' => $id_layout,
			   'pos_x' => $pos_x,
			   'pos_y' => $pos_y,
			   'label' => get_agentmodule_name ($id_module),
			   'image' => $image,
			   'id_agent' => $id_agent,
			   'id_agente_modulo' => $id_module,
			   'width' => $width,
			   'height' => $height,
			   'label_color' => '#000000')
			);
		
		$pos_x = $pos_x + $range;
	}
	
	$return = print_success_message (__('Modules successfully added to layout'), '', true);
	
	return $return;
}

/**
 * Get the color of line between elements in the visual map.
 * 
 * @param array $layoutData The row of element in DB.
 * 
 * @return string The color as hexadecimal color in html.
 */
function getColorLineStatus($layoutData) {
	if (($layoutData['type'] == 5) || ($layoutData['type'] == 4)) {
		//ICON ELEMENT OR LABEL ELEMENT
		$color = "#cccccc";
	}
	else {
		switch (getStatusElement($layoutData)) {
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
function getImageStatusElement($layoutData) {
	$img = "images/console/icons/" . $layoutData["image"];
	
	if ($layoutData['type'] == 5) {
		//ICON ELEMENT
		$img .= ".png";
	}
	else {
		switch (getStatusElement($layoutData)) {
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
function getStatusElement($layoutData) {
	//Linked to other layout ?? - Only if not module defined
	if ($layoutData['id_layout_linked'] != 0) {
		$status = get_layout_status ($layoutData['id_layout_linked']);
	}
	else if ($layoutData["type"] == 0) { //Single object
		//Status for a simple module
		if ($layoutData['id_agente_modulo'] != 0) {
			$status = get_agentmodule_status ($layoutData['id_agente_modulo']);

		//Status for a whole agent, if agente_modulo was == 0
		}
		else if ($layoutData['id_agent'] != 0) {
			$status = get_agent_status ($layoutData["id_agent"]);
			if ($status == -1) // get_agent_status return -1 for unknown!
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
function print_pandora_visual_map ($id_layout, $show_links = true, $draw_lines = true, $width = null, $height = null) {
	global $config;
	$layout = get_db_row ('tlayout', 'id', $id_layout);
	
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
		$backgroundImage = 'include/Image/image_functions.php?getFile=1&thumb=1&thumb_size=' . $mapWidth . 'x' . $mapHeight . '&file=' .
			$config['homeurl'] . '/' . 'images/console/background/'.safe_input ($layout["background"]);
	}
	else {
		$mapWidth = $layout["width"];
		$mapHeight = $layout["height"];
		$backgroundImage = 'images/console/background/'.safe_input ($layout["background"]);
	}
	
	echo '<div id="layout_map"
		style="z-index: 0; position:relative; background: url(\'' . $backgroundImage .'\'); width:'.$mapWidth.'px; height:'.$mapHeight.'px;">';
	$layout_datas = get_db_all_rows_field_filter ('tlayout_data', 'id_layout', $id_layout);
	$lines = array ();
	
	if ($layout_datas !== false) {
		foreach ($layout_datas as $layout_data) {
			$layout_data['label'] = safe_output($layout_data['label']);
			// ****************************************************************
			// Get parent status (Could be an agent, module, map, others doesnt have parent info)
			// ****************************************************************
			
			// Pending delete and disable modules must be ignored
			$delete_pending_module = get_db_value ("delete_pending", "tagente_modulo", "id_agente_modulo", $layout_data["id_agente_modulo"]);
			$disabled_module = get_db_value ("disabled", "tagente_modulo", "id_agente_modulo", $layout_data["id_agente_modulo"]);
			
			if($delete_pending_module == 1 || $disabled_module == 1)
				continue;
				
			if ($layout_data["parent_item"] != 0){
				$id_agent_module_parent = get_db_value ("id_agente_modulo", "tlayout_data", "id", $layout_data["parent_item"]);
				$id_agent_parent = get_db_value ("id_agent", "tlayout_data", "id", $layout_data["parent_item"]);
				$id_layout_linked = get_db_value ("id_layout_linked", "tlayout_data", "id", $layout_data["parent_item"]); 
				
				// Module
				if ($id_agent_module_parent != 0) {
					$status_parent = get_agentmodule_status ($id_agent_module_parent);
				// Agent
				} 
				elseif ($id_agent_parent  != 0) {
					$status_parent = get_agent_status ($id_agent_parent);
				}
				// Another layout/map
				elseif ($id_layout_linked != 0) {
					$status_parent = get_layout_status ($id_layout_linked);
				}

				else { 
					$status_parent = 3;
				}
				
			} else {
				$id_agent_module_parent = 0;
				$status_parent = 3;
			}


			// ****************************************************************	
			// Get STATUS of current object
			// ****************************************************************

			// Linked to other layout ?? - Only if not module defined
			if ($layout_data['id_layout_linked'] != 0) {
				$status = get_layout_status ($layout_data['id_layout_linked']);

			// Single object
			} elseif ($layout_data["type"] == 0) {
				// Status for a simple module
				if ($layout_data['id_agente_modulo'] != 0) {
					$status = get_agentmodule_status ($layout_data['id_agente_modulo']);
					$id_agent = get_db_value ("id_agente", "tagente_estado", "id_agente_modulo", $layout_data['id_agente_modulo']);

				// Status for a whole agent, if agente_modulo was == 0
				} elseif ($layout_data['id_agent'] != 0) {
					$status = get_agent_status ($layout_data["id_agent"]);
					if ($status == -1) // get_agent_status return -1 for unknown!
						$status = 3; 
					$id_agent = $layout_data["id_agent"];
				} else {
					$status = 3;
					$id_agent = 0;
				}
			} else {
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
					echo '<div style="text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.((integer)($proportion * $layout_data['pos_x'])).'px; margin-top:'.((integer)($proportion * $layout_data['pos_y'])).'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
				else
					echo '<div style="text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">'; 
				
				if (!isset ($id_agent))
					$id_agent = 0;
					
				if ($show_links) {
					if (($id_agent > 0) && ($layout_data['id_layout_linked'] == "" || $layout_data['id_layout_linked'] == 0)) {

						// Link to an agent
						echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent.'">';
					} elseif ($layout_data['id_layout_linked'] > 0) {

						// Link to a map
						echo '<a href="index.php?sec=visualc&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data["id_layout_linked"].'">';

					} else {
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
						$img = "4".$img."_bad.png";
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
				$borderStyle = '';
				if(substr($img,0,1) == '4') {
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
				
				print_image ($img, false, $img_style);
		
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
						echo '<div style="text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.((integer)($proportion * $layout_data['pos_x'])).'px; margin-top:'.((integer)($proportion * $layout_data['pos_y'])).'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					else
						echo '<div style="text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					
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
							echo '<a style="' . ($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '') . '" href="index.php?sec=visualc&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data["id_layout_linked"].'">';
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
						echo '<div style="text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.((integer)($proportion * $layout_data['pos_x'])).'px; margin-top:'.((integer)($proportion * $layout_data['pos_y'])).'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					else
						echo '<div style="text-align: center; z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					
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
							echo '<a style="' . ($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '') . '" href="index.php?sec=visualc&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data["id_layout_linked"].'">';
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
				
					print_image ($img, false, $img_style);
					
					if ($endTagA) echo "</a>";
					
					echo "</div>";
					break;
				case 2:
					// ****************************************************************
					// SIMPLE DATA VALUE (type = 2)
					// ****************************************************************
					if ($resizedMap)
						echo '<div style="z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.((integer)($proportion *$layout_data['pos_x'])).'px; margin-top:'.((integer)($proportion *$layout_data['pos_y'])).'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					else
						echo '<div style="z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					echo '<strong>'.$layout_data['label']. ' ';
					echo get_db_value ('datos', 'tagente_estado', 'id_agente_modulo', $layout_data['id_agente_modulo']);
					echo '</strong></div>';
					break;	
				case 3:
					// ****************************************************************
					// Progress bar
					// ****************************************************************	
					if ($resizedMap)
						echo '<div style="text-align: center; z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.((integer)($proportion *$layout_data['pos_x'])).'px; margin-top:'.((integer)($proportion *$layout_data['pos_y'])).'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					else
						echo '<div style="text-align: center; z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					$valor = get_db_sql ('SELECT datos FROM tagente_estado WHERE id_agente_modulo = '.$layout_data['id_agente_modulo']);
					$width = $layout_data['width'];
					if ( $layout_data['height'] > 0)
						$percentile = $valor / $layout_data['height'] * 100;
					else
						$percentile = 100;
					
					echo $layout_data['label'];
					echo "<br>";

					if ($resizedMap)
						echo "<img src='".$config["homeurl"]."/include/fgraph.php?tipo=progress&height=15&width=".((integer)($proportion * $width))."&mode=1&percent=$percentile'>";
					else	
						echo "<img src='".$config["homeurl"]."/include/fgraph.php?tipo=progress&height=15&width=$width&mode=1&percent=$percentile'>";
	
					echo '</div>';
					break;
				case 1;
					// ****************************************************************
					// Single module graph
					// ****************************************************************
					// SINGLE GRAPH (type = 1)
					if ($resizedMap)
						echo '<div style="text-align: center; z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.((integer)($proportion * $layout_data['pos_x'])).'px; margin-top:'.((integer)($proportion * $layout_data['pos_y'])).'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					else
						echo '<div style="text-align: center; z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
					
					echo $layout_data['label'];
					echo "<br>";

					
					if ($show_links) {
						if (($layout_data['id_layout_linked'] == "") || ($layout_data['id_layout_linked'] == 0)) {
							echo '<div style="border-width:1px; border-style:solid; border-color:#808080"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$layout_data["id_agent"].'&amp;tab=data">';
						} else {
							echo '<div style="border-width:1px; border-style:solid; border-color:#808080"><a href="index.php?sec=visualc&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data['id_layout_linked'].'">';
						}
					}
					if ($resizedMap)

					// ATTENTION: DO NOT USE &amp; here because is bad-translated and doesnt work
					// resulting fault image links :(

						print_image ("include/fgraph.php?tipo=sparse&id=".$layout_data['id_agente_modulo']."&label=".safe_input ($layout_data['label'])."&height=".((integer)($proportion * $layout_data['height']))."&pure=1&width=".((integer)($proportion * $layout_data['width']))."&period=".$layout_data['period'], false, array ("title" => $layout_data['label'], "border" => 0));
					else
						print_image ("include/fgraph.php?tipo=sparse&id=".$layout_data['id_agente_modulo']."&label=".safe_input ($layout_data['label'])."&height=".$layout_data['height']."&pure=1&width=".$layout_data['width']."&period=".$layout_data['period'], false, array ("title" => $layout_data['label'], "border" => 0));
					echo "</a>";
					echo "</div></div>";
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
		
		echo 'var lines = Array ();'."\n";
		
		foreach ($lines as $line) {
			echo 'lines.push (eval ('.json_encode ($line).'));'."\n";
		}
		echo '/* ]]> */</script>';
	}
	// End main div
	echo "</div>";
}

/**
 * @return array Layout data types
 */
function get_layout_data_types () {
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
 * print_select() directly)
 * @param array Additional filters to filter the layouts.
 *
 * @return array A list of layouts the user can see.
 */
function get_user_layouts ($id_user = 0, $only_names = false, $filter = false) {
	if (! is_array ($filter))
		$filter = array ();
	
	$where = format_array_to_where_clause_sql ($filter);
	if ($where != '') {
		$where .= ' AND ';
	}
	$groups = get_user_groups ($id_user);
	$where .= sprintf ('id_group IN (%s)', implode (",", array_keys ($groups)));
	
	$layouts = get_db_all_rows_filter ('tlayout', $where);
	
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
function get_layout_status ($id_layout = 0, $depth = 0) {
	$temp_status = 0;
	$temp_total = 0;
	$depth++; // For recursion depth checking

	// TODO: Implement this limit as a configurable item in setup
	if ($depth > 10){
		return 3; // No status data if we need to exit by a excesive recursion
	}

	$id_layout = (int) $id_layout;
	
	$result = get_db_all_rows_filter ('tlayout_data', array ('id_layout' => $id_layout),
		array ('id_agente_modulo', 'parent_item', 'id_layout_linked', 'id_agent', 'type'));
	if ($result === false)
		return 0;
	
	foreach ($result as $rownum => $data) {
		if (($data["id_layout_linked"] == 0 && $data["id_agente_modulo"] == 0 && $data["id_agent"] == 0) || $data['type'] != 0)
			continue;
		// Other Layout (Recursive!)
		if (($data["id_layout_linked"] != 0) && ($data["id_agente_modulo"] == 0)) {
			$status = get_layout_status ($data["id_layout_linked"], $depth);
		// Module
		} elseif ($data["id_agente_modulo"] != 0) {
			$status = get_agentmodule_status ($data["id_agente_modulo"]);
		// Agent
		} else {
			$status = get_agent_status ($data["id_agent"]);
		}
		if ($status == 1)
			return 1;
		if ($status > $temp_total)
			$temp_total = $status;
	}
	
	return $temp_total;
}
?>
