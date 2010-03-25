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

$action = get_parameterBetweenListValues('action', array('new', 'save', 'edit', 'update'), 'new');
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
						$idVisualConsole = process_sql_insert('tlayout', $values);
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
			case 'edit':
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				$visualConsoleName = $visualConsole['name'];
				break;
		}
		break;
	case 'editor':
		switch ($action) {
			case 'edit':
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				$visualConsoleName = $visualConsole['name'];
				break;
			case 'update':
				$values = array('background' => get_parameter('background_image'),
					'height' => get_parameter('height_background'),
					'width' => get_parameter('width_background'));
				
				$result = process_sql_update('tlayout', $values, array('id' => $idVisualConsole));
				if ($result !== false) {
					$action = 'edit';
					$statusProcessInDB = array('flag' => true, 'message' => '<h3 class="suc">'.__('Successfully update.').'</h3>');
				}
				else {
					$statusProcessInDB = array('flag' => false, 'message' => '<h3 class="error">'.__('Could not be update.').'</h3>');
				}
				
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				$visualConsoleName = $visualConsole['name'];
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
			print_image ("images/pill.png", true, array ("title" => __('Wizard'))) .'</a>'),
	'editor' => array('active' => false,
		'text' => '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=editor&action=' . $action . '&id_visual_console=' . $idVisualConsole . '">' .
			print_image ("images/config.png", true, array ("title" => __('Editor'))) .'</a>'),
	'preview' => array('active' => false,
		'text' => '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=preview&action=' . $action . '&id_visual_console=' . $idVisualConsole . '">' .
			print_image ("images/eye.png", true, array ("title" => __('Preview'))) .'</a>'),);

if ($action == 'new') $buttons = array('data' => $buttons['data']); //Show only the data tab
$buttons[$activeTab]['active'] = true;
print_page_header(__('Visual console builder') . "&nbsp;" . $visualConsoleName, "", false, "visual_console_editor_" . $activeTab . "_tab", false, $buttons);

//The source code for PAINT THE PAGE
if ($statusProcessInDB !== null) {
	echo $statusProcessInDB['message'];
}

switch ($activeTab) {
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