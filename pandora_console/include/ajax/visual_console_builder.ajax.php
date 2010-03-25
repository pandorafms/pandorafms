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

if (! give_acl ($config['id_user'], 0, "IW")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_visual_map.php');

$action = get_parameter('action');
$type = get_parameter('type');
$id_visual_console = get_parameter('id_visual_console', null);
$id_element = get_parameter('id_element', null);
$image = get_parameter('image', null);
$background = get_parameter('background', null);
$label = get_parameter('label', null);
$left = get_parameter('left', null);
$top = get_parameter('top', null);
$agent = get_parameter('agent', null);
$module = get_parameter('module', null);
$period = get_parameter('period', null);
$width = get_parameter('width', null);
$height = get_parameter('height', null);
$parent = get_parameter('parent', null);
$map_linked = get_parameter('map_linked', null);
$label_color = get_parameter('label_color', null);

switch ($action) {
	case 'get_color_line':
		$layoutData = get_db_row_filter('tlayout_data', array('id' => $id_element));
		
		$return = array();
		$return['color_line'] = getColorLineStatus($layoutData);
		echo json_encode($return);
		break;
	case 'get_image':
		$layoutData = get_db_row_filter('tlayout_data', array('id' => $id_element));
		
		$return = array();
		$return['image'] = getImageStatusElement($layoutData);
		echo json_encode($return);
		break;
	case 'update':
		switch ($type) {
			case 'background':
				$values = array();
				if ($background !== null)
					$values['background'] = $background;
				if ($width !== null)
					$values['width'] = $width;
				if ($height !== null)
					$values['height'] = $height;
				process_sql_update('tlayout', $values, array('id' => $id_visual_console));
				break;
			case 'static_graph':
				$values = array();
				if ($label !== null) {
					$values['label'] = $label;
				}
				if ($image !== null) {
					$values['image'] = $image;
				}
				if ($left !== null) {
					$values['pos_x'] = $left;
				}
				if ($top !== null) { 
					$values['pos_y'] = $top;
				}
				if ($agent !== null) {
					$id_agent = get_agent_id($agent);
					$values['id_agent'] = $id_agent;
				}
				if ($module !== null) {
					$values['id_agente_modulo'] = $module;
				}
				if ($period !== null) {
					$values['period'] = $period;
				}
				if ($width !== null) {
					$values['width'] = $width;
				}
				if ($height !== null) {
					$values['height'] = $height;
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
				
				$result = process_sql_update('tlayout_data', $values, array('id' => $id_element));
				break;
		}
		break;
	case 'load':
		switch ($type) {
			case 'background':
				$backgroundFields = get_db_row_filter('tlayout', array('id' => $id_visual_console), array('background', 'height', 'width'));
				echo json_encode($backgroundFields);
				break;
			case 'static_graph':
				$elementFields = get_db_row_filter('tlayout_data', array('id' => $id_element));
				$elementFields['agent_name'] = get_agent_name($elementFields['id_agent']);
				
				if ($elementFields['id_agent'] != 0) {
					$modules = get_agent_modules ($elementFields['id_agent'], false, array('disabled' => 0, 'id_agente' => $elementFields['id_agent']));
					
					$elementFields['modules_html'] = '<option value="0">--</option>';
					foreach ($modules as $id => $name) {
						$elementFields['modules_html'] .= '<option value="' . $id . '">' . $name . '</option>';
					}
				}
				else  {
					$elementFields['modules_html'] = '<option value="0">' . __('Any') . '</option>';
				}
				
				echo json_encode($elementFields);
				break;
		}
		break;
	case 'insert':
		switch ($type) {
			case 'static_graph':
				$values = array();
				$values['id_layout'] = $id_visual_console;
				$values['label'] = $label;
				$values['image'] = $image;
				$values['pos_x'] = $left;
				$values['pos_y'] = $top;
				$values['label_color'] = $label_color;
				if ($agent != '')
					$values['id_agent'] = get_agent_id($agent);
				else
					$values['id_agent'] = 0;
				$values['id_agente_modulo'] = $module;
				$values['width'] = $width;
				$values['height'] = $height;
				$values['id_layout_linked'] = $map_linked;
				$values['parent_item'] = $parent;
				
				$idData = process_sql_insert('tlayout_data', $values);
				
				$return = array();
				if ($idData === false) {
					$return['correct'] = 0;
				}
				else {
					$return['correct'] = 1;
					$return['id_data'] = $idData;
					$return['text'] = $label;
				}
				echo json_encode($return);
				break;
		}
		break;
	case 'delete':
		if (process_sql_delete('tlayout_data', array('id' => $id_element, 'id_layout' => $id_visual_console)) === false) {
			$return['correct'] = 0;
		}
		else {
			$return['correct'] = 1;
		}
		
		echo json_encode($return);
		break;
}
?>