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

if (! check_acl ($config['id_user'], 0, "RW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once ($config['homedir'].'/include/functions_visual_map.php');
require_once ($config['homedir'].'/include/functions_agents.php');
enterprise_include_once('include/functions_visual_map.php');
enterprise_include_once('meta/include/functions_agents_meta.php');
enterprise_include_once('meta/include/functions_users_meta.php');

//Arrays for select box.
$backgrounds_list = list_files($config['homedir'] . '/images/console/background/', "jpg", 1, 0);
$backgrounds_list = array_merge($backgrounds_list, list_files ($config['homedir'] . '/images/console/background/', "png", 1, 0));

$images_list = array ();
$all_images = list_files ($config['homedir'] . '/images/console/icons/', "png", 1, 0);
foreach ($all_images as $image_file) {
	if (strpos ($image_file, "_bad"))
		continue;
	if (strpos ($image_file, "_ok"))
		continue;
	if (strpos ($image_file, "_warning"))
		continue;
	$image_file = substr ($image_file, 0, strlen ($image_file) - 4);
	$images_list[$image_file] = $image_file;
}

if (!defined('METACONSOLE')) {
	$table->width = '100%';
}
else {
	$table->width = '780';
}
$table->head = array();
$table->head['icon'] = '';
$table->head[0] = __('Label') . ' / ' . __('Agent');
$table->head[1] = __('Image') . ' / ' . __('Module');
$table->head[2] = __('Width x Height<br>Max value');
$table->head[3] = __('Period') . ' / ' . __('Position');
$table->head[4] = __('Parent') . ' / ' . __('Map linked');
$table->head[5] = '<span title="' . __('Action') . '">' .
	__('A.') . '</span>';

$table->size = array();
$table->size['icon'] = '1%';
$table->size[0] = '25%';

$table->style = array();
$table->style[1] = 'background-color: #ffffff;';

$table->align = array();
$table->align[0] = "left";
$table->align[1] = "right";
$table->align[2] = "center";
$table->align[3] = "center";
$table->align[4] = "right";
$table->align[5] = "center";

$table->data = array();

//Background
$table->data[0]['icon'] = '';
$table->data[0][0] = __('Background');
$table->data[0][1] = html_print_select($backgrounds_list, 'background', $visualConsole['background'], '', 'None', '', true, false, true, '', false, 'width: 120px;');
$table->data[0][2] = html_print_input_text('width', $visualConsole['width'], '', 3, 5, true) .
	'x' .
	html_print_input_text('height', $visualConsole['height'], '', 3, 5, true);
$table->data[0][3] = $table->data[0][4] = $table->data[0][5] = '';

$i = 1;
$layoutDatas = db_get_all_rows_field_filter ('tlayout_data', 'id_layout', $idVisualConsole);
if ($layoutDatas === false)
	$layoutDatas = array();

$alternativeStyle = true;
foreach ($layoutDatas as $layoutData) {
	$idLayoutData = $layoutData['id'];
	
	//line between rows
	$table->data[$i][0] = '<hr>';
	$table->colspan[$i][0] = '8';
	
	switch ($layoutData['type']) {
		case STATIC_GRAPH:
			$table->data[$i + 1]['icon'] = html_print_image('images/camera.png', true, array('title' => __('Static Graph')));
			break;
		case PERCENTILE_BAR:
			$table->data[$i + 1]['icon'] = html_print_image('images/chart_bar.png', true, array('title' => __('Percentile Bar')));
			break;
		case PERCENTILE_BUBBLE:
			$table->data[$i + 1]['icon'] = html_print_image('images/dot_red.png', true, array('title' => __('Percentile Bubble')));
			break;
		case MODULE_GRAPH:
			$table->data[$i + 1]['icon'] = html_print_image('images/chart_curve.png', true, array('title' => __('Module Graph')));
			break;
		case SIMPLE_VALUE:
			$table->data[$i + 1]['icon'] = html_print_image('images/binary.png', true, array('title' => __('Simple Value')));
			break;
		case SIMPLE_VALUE_MAX:
			$table->data[$i + 1]['icon'] = html_print_image('images/binary.png', true, array('title' => __('Simple Value (Process Max)')));
			break;
		case SIMPLE_VALUE_MIN:
			$table->data[$i + 1]['icon'] = html_print_image('images/binary.png', true, array('title' => __('Simple Value (Process Min)')));
			break;
		case SIMPLE_VALUE_AVG:
			$table->data[$i + 1]['icon'] = html_print_image('images/binary.png', true, array('title' => __('Simple Value (Process Avg)')));
			break;
		case LABEL:
			$table->data[$i + 1]['icon'] = html_print_image('images/tag_red.png', true, array('title' => __('Label')));
			break;
		case ICON:
			$table->data[$i + 1]['icon'] = html_print_image('images/photo.png', true, array('title' => __('Icon')));
			break;
		default:
			if (enterprise_installed()) {
				$table->data[$i + 1]['icon'] = enterprise_visual_map_print_list_element('icon', $layoutData);
			}
			else {
				$table->data[$i + 1]['icon'] = '';
			}
			break;
	}
	
	//First row
	
	//Label and color label
	if ($layoutData['type'] != ICON) {
		$table->data[$i + 1][0] = '<span style="width: 150px; display: block;">' .
			html_print_input_text ('label_' . $idLayoutData, $layoutData['label'], '', 10, 200, true) . 
			html_print_input_text_extended ('label_color_' . $idLayoutData, $layoutData['label_color'], 'text-'.'label_color_' . $idLayoutData, '', 7, 7, false, '', 'style="visibility: hidden; width: 0px;" class="label_color"', true) . 
			'</span>';
	}
	else {
		//Icon haven't the label.
		$table->data[$i + 1][0] = '';
	}
	
	//Image
	if (($layoutData['type'] == STATIC_GRAPH) || ($layoutData['type'] == ICON)) {
		$table->data[$i + 1][1] = html_print_select ($images_list, 'image_' . $idLayoutData, $layoutData['image'], '', 'None', '', true,  false, true, '', false, "width: 120px");
	}
	else {
		$table->data[$i + 1][1] = '';
	}
	
	//Width and height
	$table->data[$i + 1][2] = html_print_input_text('width_' . $idLayoutData, $layoutData['width'], '', 2, 5, true) .
		'x' .
		html_print_input_text('height_' . $idLayoutData, $layoutData['height'], '', 2, 5, true);
	
	//Position
	$table->data[$i + 1][3] = '(' . html_print_input_text('left_' . $idLayoutData, $layoutData['pos_x'], '', 2, 5, true) .
		',' . html_print_input_text('top_' . $idLayoutData, $layoutData['pos_y'], '', 2, 5, true) .
		')';
	
	//Parent
	$table->data[$i + 1][4] = html_print_select_from_sql ('SELECT id, label FROM tlayout_data WHERE id_layout = '. $idVisualConsole . ' AND id !=' . $idLayoutData,
		'parent_' . $idLayoutData, $layoutData['parent_item'], '', 'None', 0, true, false, true, false, 'width: 120px;');
	
	//Delete row button
	if (!defined('METACONSOLE')) {
		$table->data[$i + 1][5] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/visual_console_builder&tab=' .
			$activeTab  . '&action=delete&id_visual_console=' . $visualConsole["id"] . '&id_element=' . $idLayoutData . '" ' . 
			'onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;">' . html_print_image('images/cross.png', true) . '</a>';
	}
	else {
		$pure = get_parameter('pure', 0);
		
		$table->data[$i + 1][5] = '<a href="index.php?operation=edit_visualmap&sec=screen&sec2=screens/screens&action=visualmap' .
			'&pure=' . $pure . '&tab=list_elements&action2=delete&id_visual_console=' . $visualConsole["id"] . '&id_element=' . $idLayoutData . '" ' . 
			'onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;">' . html_print_image('images/cross.png', true) . '</a>';
	}
	
	
	//Second row
	$table->data[$i + 2]['icon'] = '';
	
	//Agent
	switch ($layoutData['type']) {
		case ICON:
		case LABEL:
			$table->data[$i + 2][0] = '';
			break;
		default:
			$cell_content_enterprise = false;
			if (enterprise_installed()) {
				$cell_content_enterprise = enterprise_visual_map_print_list_element('agent', $layoutData);
			}
			if ($cell_content_enterprise === false) {
				$params = array();
				$params['return'] = true;
				$params['show_helptip'] = true;
				$params['size'] = 20;
				$params['input_name'] = 'agent_' . $idLayoutData;
				$params['javascript_is_function_select'] = true;
				$params['selectbox_id'] = 'module_' . $idLayoutData;
				if (defined('METACONSOLE')) {
					$params['javascript_ajax_page'] = '../../ajax.php';
					$params['disabled_javascript_on_blur_function'] = true;
					
					$params['print_input_server'] = true;
					$params['input_server_id'] = 
						$params['input_server_name'] = 'id_server_name_' . $idLayoutData;
					$params['input_server_value'] =
						db_get_value('server_name', 'tmetaconsole_setup', 'id', $layoutData['id_metaconsole']);
					$params['metaconsole_enabled'] = true;
					$params['print_hidden_input_idagent'] = true;
					$params['hidden_input_idagent_name'] = 'id_agent_' . $idLayoutData;
					$params['hidden_input_idagent_value'] = $layoutData['id_agent'];
					
					$params['value'] = agents_meta_get_name($layoutData['id_agent'],
						"none", $layoutData['id_metaconsole'], true);
				}
				else {
					$params['value'] = agents_get_name($layoutData['id_agent']);
				}
				
				$table->data[$i + 2][0] = ui_print_agent_autocomplete_input($params);
			}
			else {
				$table->data[$i + 2][0] = $cell_content_enterprise;
			}
			break;
	}
	
	//Module
	switch ($layoutData['type']) {
		case ICON:
		case LABEL:
			$table->data[$i + 2][1] = '';
			break;
		default:
			$cell_content_enterprise = false;
			if (enterprise_installed()) {
				$cell_content_enterprise = enterprise_visual_map_print_list_element('module', $layoutData);
			}
			if ($cell_content_enterprise === false) {
				if (!defined('METACONSOLE')) {
					$modules = agents_get_modules($layoutData['id_agent']);
				}
				else {
					if ($layoutData['id_agent'] != 0) {
						$modules = agents_meta_get_modules($layoutData['id_metaconsole'],
							$layoutData['id_agent']);
					}
				}
				
				$modules = io_safe_output($modules);
				
				$table->data[$i + 2][1] = html_print_select($modules,
					'module_' . $idLayoutData, $layoutData['id_agente_modulo'], '', '---', 0, true,  false, true, '', false, "width: 120px");
			}
			else {
				$table->data[$i + 2][1] = $cell_content_enterprise;
			}
			break;
	}
	
	//Empty
	$table->data[$i + 2][2] = '';
	
	//Period
	switch ($layoutData['type']) {
		case MODULE_GRAPH:
		case SIMPLE_VALUE_MAX:
		case SIMPLE_VALUE_MIN:
		case SIMPLE_VALUE_AVG:
			$table->data[$i + 2][3] = html_print_extended_select_for_time ('period_' . $idLayoutData, $layoutData['period'], '', '--', '0', 10, true);
			break;
		default:
			$table->data[$i + 2][3] = '';
			break;
	}
	
	//Map linked
	$table->data[$i + 2][4] = html_print_select_from_sql ('SELECT id, name FROM tlayout WHERE id != ' . $idVisualConsole,
		'map_linked_' . $idLayoutData, $layoutData['id_layout_linked'], '', 'None', '', true,  false, true, '', false, "width: 120px");
	$table->data[$i + 2][5] = '';
	
	if ($alternativeStyle) {
		$table->rowclass[$i + 1] = 'rowOdd';
		$table->rowclass[$i + 2] = 'rowOdd';
	}
	else {
		$table->rowclass[$i + 1] = 'rowPair';
		$table->rowclass[$i + 2] = 'rowPair';
	}
	$alternativeStyle = !$alternativeStyle; 
	
	$i = $i + 3;
}

$pure = get_parameter('pure', 0);

if (!defined('METACONSOLE')) {
	echo '<form method="post" action="index.php?sec=reporting&sec2=godmode/reporting/visual_console_builder&tab=' . $activeTab  . '&id_visual_console=' . $visualConsole["id"] . '">';
}
else {
	echo "<form method='post' action='index.php?operation=edit_visualmap&sec=screen&sec2=screens/screens&action=visualmap&pure=0&tab=list_elements&id_visual_console=" . $idVisualConsole . "'>";
}
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if (!defined('METACONSOLE')) {
	html_print_input_hidden ('action', 'update');
}
else {
	html_print_input_hidden ('action2', 'update');
}
html_print_input_hidden ('id_visual_console', $visualConsole["id"]);
html_print_submit_button (__('Update'), 'go', false, 'class="sub next"');
echo '</div>';
html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button (__('Update'), 'go', false, 'class="sub next"');
echo '</div>';
echo '</form>';

//Trick for it have a traduct text for javascript.
echo '<span id="ip_text" style="display: none;">' . __('IP') . '</span>';

ui_require_css_file ('color-picker');

ui_require_jquery_file ('colorpicker');
ui_require_jquery_file ('pandora.controls');
ui_require_javascript_file ('wz_jsgraphics');
ui_require_javascript_file ('pandora_visual_console');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');
?>

<script type="text/javascript">
	$(document).ready (function () {
		$(".label_color").attachColorPicker();
		//$(".ColorPickerDivSample").css('float', 'right');
	});
	
	var idText = $("#ip_text").html();
</script>
