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

$action = get_parameterBetweenListValues('action', array('new', 'save', 'edit', 'update', 'delete'), 'new');
$activeTab = get_parameterBetweenListValues('tab', array('data', 'list_elements', 'wizard', 'editor', 'preview'), 'data');
$idVisualConsole = get_parameter('id_visual_console', 0);

//Save/Update data in DB
$statusProcessInDB = null;
switch ($activeTab) {
	case 'data':
		switch ($action) {
			case 'new':
				$idGroup = '';
				$background = '';
				$visualConsoleName = '';
				break;
			case 'update':
			case 'save':
				$idGroup = get_parameter('id_group');
				$background = get_parameter('background');
				$visualConsoleName = get_parameter('name');
				
				$values = array('name' => $visualConsoleName, 'id_group' => $idGroup, 'background' => $background);
				switch ($action) {
					case 'update':
						$result = process_sql_update('tlayout', $values, array('id' => $idVisualConsole));
						if ($result !== false) {
							$action = 'edit';
							$statusProcessInDB = array('flag' => true, 'message' => '<h3 class="suc">'.__('Successfully update.').'</h3>');
						}
						else {
							$statusProcessInDB = array('flag' => false, 'message' => '<h3 class="error">'.__('Could not be update.').'</h3>');
						}
						break;
					case 'save':
						$sizeBackground = getimagesize($config['homedir'] . '/images/console/background/' . $background);
						$values['width'] = $sizeBackground[0];
						$values['height'] = $sizeBackground[1];
						
						if($values['name'] != "" && $values['background'])
							$idVisualConsole = process_sql_insert('tlayout', $values);
						else
							$idVisualConsole = false;
							
						if ($idVisualConsole !== false) {
							$action = 'edit';
							$statusProcessInDB = array('flag' => true, 'message' => '<h3 class="suc">'.__('Successfully created.').'</h3>');
						}
						else {
							$statusProcessInDB = array('flag' => false, 'message' => '<h3 class="error">'.__('Could not be created.').'</h3>');
						}
						break;
				}
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				break;
			case 'edit':
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				$visualConsoleName = $visualConsole['name'];
				$idGroup = $visualConsole['id_group'];
				$background = $visualConsole['background'];
				break;
		}
		break;
	case 'list_elements':
		switch ($action) {
			case 'update':
				//Update background
				
				$background = get_parameter('background');
				$width = get_parameter('width');
				$height = get_parameter('height');
				
				if($width == 0 && $height == 0) {
					$sizeBackground = getimagesize($config['homedir'] . '/images/console/background/' . $background);
					$width = $sizeBackground[0];
					$height = $sizeBackground[1];
				}
				
				process_sql_update('tlayout', array('background' => $background,
					'width' => $width, 'height' => $height), array('id' => $idVisualConsole));
				
				//Update elements in visual map
				$idsElements = get_db_all_rows_filter('tlayout_data', array('id_layout' => $idVisualConsole), array('id'));
				foreach ($idsElements as $idElement) {
					$id = $idElement['id'];
					$values = array();
					$values['label'] = get_parameter('label_' . $id, '');
					$values['image'] = get_parameter('image_' . $id, '');
					$values['width'] = get_parameter('width_' . $id, 0);
					$values['height'] = get_parameter('height_' . $id, 0);
					$values['pos_x'] = get_parameter('left_' . $id, 0);
					$values['pos_y'] = get_parameter('top_' . $id, 0);
					$agentName = get_parameter('agent_' .  $id, '');
					$values['id_agent'] = get_agent_id($agentName);
					$values['id_agente_modulo'] = get_parameter('module_' . $id, 0);
					$values['parent_item'] = get_parameter('parent_' . $id, 0);
					$values['id_layout_linked'] = get_parameter('map_linked_' . $id, 0);
					$values['label_color'] = get_parameter('label_color_' . $id, '#000000');
					process_sql_update('tlayout_data', $values, array('id' => $id));
				}
				break;
			case 'delete':
				$id_element = get_parameter('id_element');
				$result = process_sql_delete('tlayout_data', array('id' => $id_element));
				if ($result !== false) {
					$statusProcessInDB = array('flag' => true, 'message' => '<h3 class="suc">'.__('Successfully delete.').'</h3>');
				}
				break;
		}
		$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
		$visualConsoleName = $visualConsole['name'];
		$action = 'edit';
		break;
	case 'wizard':
		$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
		$visualConsoleName = $visualConsole['name'];
		switch ($action) {
			case 'update':
				$id_agents = get_parameter ('id_agents', array ());
				$name_modules = get_parameter ('module', array ());
				
				$image = get_parameter ('image');
				$range = (int) get_parameter ("range", 50);
				$width = (int) get_parameter ("width", 0);
				$height = (int) get_parameter ("height", 0);
				
				if($width == 0 && $height == 0) {
					$sizeBackground = getimagesize($config['homedir'] . '/images/console/background/' . $background);
					$width = $sizeBackground[0];
					$height = $sizeBackground[1];
				}
				
				$message = '';
				if ($name_modules[0] == '0')
					$message .= process_wizard_add ($id_agents, $image, $idVisualConsole, $range, $width, $height);
				else{
					$id_modules = array();
					$cont_dest = 1;
					$cont_mod = 1;
					foreach($name_modules as $mod){
						$cont_ag = 1;
						foreach($id_agents as $ag){
							$sql = "SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 0 AND id_agente = ".$ag." AND nombre = '".$mod."'";
							$result = get_db_row_sql ($sql);
							$id_modules[$cont_dest] = $result['id_agente_modulo'];
							$cont_ag = $cont_ag + 1;
							$cont_dest = $cont_dest + 1;
						}
						$cont_mod = $cont_mod + 1;
					}
					$message .= process_wizard_add_modules ($id_modules, $image, $idVisualConsole, $range, $width, $height);
				}
				$statusProcessInDB = array('flag' => true, 'message' => $message);
				$action = 'edit';
				break;
		}
		break;
	case 'editor':
		switch ($action) {
			case 'update':
			case 'edit':
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				$visualConsoleName = $visualConsole['name'];
				$action = 'edit';
				break;
		}
		break;
	case 'preview':
		$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
		$visualConsoleName = $visualConsole['name'];
		break;
}

$buttons = array(
	'data' => array('active' => false,
		'text' => '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=data&action=' . $action . '&id_visual_console=' . $idVisualConsole . '">' . 
			print_image ("images/god9.png", true, array ("title" => __('Data'))) .'</a>'),
	'list_elements' => array('active' => false,
		'text' => '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=list_elements&action=' . $action . '&id_visual_console=' . $idVisualConsole . '">' .
			print_image ("images/god6.png", true, array ("title" => __('List elements'))) .'</a>'),
	'wizard' => array('active' => false,
		'text' => '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=wizard&action=' . $action . '&id_visual_console=' . $idVisualConsole . '">' .
			print_image ("images/wand.png", true, array ("title" => __('Wizard'))) .'</a>'),
	'editor' => array('active' => false,
		'text' => '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=editor&action=' . $action . '&id_visual_console=' . $idVisualConsole . '">' .
			print_image ("images/config.png", true, array ("title" => __('Editor'))) .'</a>'),
	'preview' => array('active' => false,
		'text' => '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=preview&action=' . $action . '&id_visual_console=' . $idVisualConsole . '">' .
			print_image ("images/eye.png", true, array ("title" => __('Preview'))) .'</a>'),);

if ($action == 'new' || $idVisualConsole === false){
	$buttons = array('data' => $buttons['data']); //Show only the data tab
	// If it is a fail try, reset the values
	$action = 'new';
	$visualConsoleName = "";
}
	
$buttons[$activeTab]['active'] = true;

print_page_header(__('Visual console builder') . "&nbsp;" . $visualConsoleName, "", false, "visual_console_editor_" . $activeTab . "_tab", true, $buttons);

//The source code for PAINT THE PAGE
if ($statusProcessInDB !== null) {
	echo $statusProcessInDB['message'];
}

switch ($activeTab) {
	case 'wizard':
		require_once('godmode/reporting/visual_console_builder.wizard.php');
		break;
	case 'data':
		require_once('godmode/reporting/visual_console_builder.data.php');
		break;
	case 'list_elements':
		require_once('godmode/reporting/visual_console_builder.elements.php');
		break;
	case 'editor':
		require_once('godmode/reporting/visual_console_builder.editor.php');
		break;
	case 'preview':
		require_once('godmode/reporting/visual_console_builder.preview.php');
		break;
}
?>
