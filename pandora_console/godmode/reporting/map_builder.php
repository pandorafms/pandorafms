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

require_once ($config['homedir'] . '/include/functions_visual_map.php');

// ACL for the general permission
$vconsoles_read = check_acl ($config['id_user'], 0, "VR");
$vconsoles_write = check_acl ($config['id_user'], 0, "VW");
$vconsoles_manage = check_acl ($config['id_user'], 0, "VM");

if (!$vconsoles_read && !$vconsoles_write && !$vconsoles_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access map builder");
	require ("general/noaccess.php");
	exit;
}

$pure = (int)get_parameter('pure', 0);
$hack_metaconsole = '';
if (defined('METACONSOLE'))
	$hack_metaconsole = '../../';

if (!defined('METACONSOLE')) {
	ui_print_page_header(
		__('Reporting') .' &raquo; ' . __('Visual Console'),
		"images/op_reporting.png", false, "map_builder");
}

$id_layout = (int) get_parameter ('id_layout');
$copy_layout = (bool) get_parameter ('copy_layout');
$delete_layout = (bool) get_parameter ('delete_layout');
$refr = (int) get_parameter('refr', $config['vc_refr']);
$offset = (int) get_parameter('offset', 0);
$pagination = (int) get_parameter ("pagination", $config["block_size"]);

if ($delete_layout || $copy_layout) {
	// Visual console required
	if (empty($id_layout)) {
		db_pandora_audit("ACL Violation",
			"Trying to access map builder");
		require ("general/noaccess.php");
		exit;
	}
	
	$group_id = db_get_value("id_group", "tlayout", "id", $id_layout);
	if ($group_id === false) {
		db_pandora_audit("ACL Violation",
			"Trying to access map builder");
		require ("general/noaccess.php");
		exit;
	}
	
	// ACL for the visual console
	// $vconsole_read = check_acl ($config['id_user'], $group_id, "VR");
	$vconsole_write = check_acl ($config['id_user'], $group_id, "VW");
	$vconsole_manage = check_acl ($config['id_user'], $group_id, "VM");
	
	if (!$vconsole_write && !$vconsole_manage) {
		db_pandora_audit("ACL Violation",
			"Trying to access map builder");
		require ("general/noaccess.php");
		exit;
	}
	
	if ($delete_layout) {
		db_process_sql_delete('tlayout_data',
			array ('id_layout' => $id_layout));
		$result = db_process_sql_delete('tlayout',
			array ('id' => $id_layout));
		if ($result) {
			db_pandora_audit(
				"Visual console builder", "Delete visual console #$id_layout");
			ui_print_success_message(__('Successfully deleted'));
			db_clean_cache();
		}
		else {
			db_pandora_audit(
				"Visual console builder", "Fail try to delete visual console #$id_layout");
			ui_print_error_message(
				__('Not deleted. Error deleting data'));
		}
		$id_layout = 0;
	}
	
	if ($copy_layout) {	
		// Number of inserts
		$ninsert = (int) 0;
		
		// Return from DB the source layout
		$layout_src = db_get_all_rows_filter("tlayout",
			array("id" => $id_layout));
		
		// Name of dst
		$name_dst = get_parameter ("name_dst",
			$layout_src[0]['name'] . " copy");
		
		// Create the new Console
		$idGroup = $layout_src[0]['id_group'];
		$background = $layout_src[0]['background'];
		$height = $layout_src[0]['height'];
		$width = $layout_src[0]['width'];
		$visualConsoleName = $name_dst;
		
		$values = array(
			'name' => $visualConsoleName,
			'id_group' => $idGroup,
			'background' => $background,
			'height' => $height,
			'width' => $width);
		$result = db_process_sql_insert('tlayout', $values);
		
		$idNewVisualConsole = $result;
		
		if ($result) {
			$ninsert = 1;
			
			// Return from DB the items of the source layout
			$data_layout_src = db_get_all_rows_filter(
				"tlayout_data",
				array("id_layout" => $id_layout));
			
			if (!empty($data_layout_src)) {
				
				//By default the id parent 0 is always 0.
				$id_relations = array(0 => 0);
				
				for ($a = 0; $a < count($data_layout_src); $a++) { 
					
					// Changing the source id by the new visual console id
					$data_layout_src[$a]['id_layout'] = $idNewVisualConsole;
					
					$old_id = $data_layout_src[$a]['id'];
					
					// Unsetting the source's id
					unset($data_layout_src[$a]['id']);
				
					// Configure the cloned Console
					$result = db_process_sql_insert('tlayout_data',
						$data_layout_src[$a]);
					
					$id_relations[$old_id] = 0;
					
					if ($result !== false) {
						$id_relations[$old_id] = $result;
					}
					
					if ($result)
						$ninsert++;
				}// for each item of console
					
				$inserts = count($data_layout_src) + 1;
					
				// If the number of inserts is correct, the copy is completed
				if ($ninsert == $inserts) {
					
					//Update the ids of parents
					$items = db_get_all_rows_filter("tlayout_data",
						array("id_layout" => $idNewVisualConsole));
					
					foreach ($items as $item) {
						$new_parent = $id_relations[$item['parent_item']];
						
						db_process_sql_update('tlayout_data',
							array('parent_item' => $new_parent),
							array('id' => $item['id']));
					}
					
					
					ui_print_success_message(__('Successfully copied'));
					db_clean_cache();
				}
				else {
					ui_print_error_message(__('Not copied. Error copying data'));
				}
			}
			else {
				// If the array is empty the copy is completed
				ui_print_success_message(__('Successfully copied'));
				db_clean_cache();
			}
		}
		else {
			ui_print_error_message(__('Not copied. Error copying data'));
		}
	}
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox data';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Map name');
$table->head[1] = __('Group');
$table->head[2] = __('Items');

// Fix: IW was the old ACL for report editing, now is RW
//Only for RW flag
if ($vconsoles_write || $vconsoles_manage) {
	$table->head[3] = __('Copy');
	$table->head[4] = __('Delete');
	$table->size[3] = "6%";
	$table->size[4] = "6%";
}

$table->align = array ();
$table->align[0] = 'left';
$table->align[1] = 'left';
$table->align[2] = 'left';
$table->align[3] = 'left';
$table->align[4] = 'left';

// Only display maps of "All" group if user is administrator
// or has "VR" privileges, otherwise show only maps of user group
$filters['offset'] = $offset;
$filters['limit'] = $pagination;
$own_info = get_user_info ($config['id_user']);
if (!defined('METACONSOLE')) {
	$url = 'index.php?sec=network&amp;sec2=godmode/reporting/map_builder&pagination='.$pagination;
}
else {
	$url = 'index.php?sec=screen&sec2=screens/screens&action=visualmap&pagination='.$pagination;
}
if ($own_info['is_admin'] || $vconsoles_read) {
	$maps = visual_map_get_user_layouts (0,false,$filters);
	$total_maps = count(visual_map_get_user_layouts());
}
else {
	$maps = visual_map_get_user_layouts ($config['id_user'], false,
		$filters, false);
	$total_maps = count(visual_map_get_user_layouts ($config['id_user'], false,
		false, false));
}
if (!$maps && !is_metaconsole()) {
	require_once ($config['homedir'] . "/general/firts_task/map_builder.php");
}
elseif (!$maps && is_metaconsole()) {
	ui_print_info_message(
		array(
			'no_close'=>true,
			'message'=>  __('There are no visual console defined yet.')));
}
else {
	ui_pagination ($total_maps, $url, $offset, $pagination);
	
	foreach ($maps as $map) {
		// ACL for the visual console permission
		$vconsole_write = check_acl ($config['id_user'],
			$map['id_group'], "VW");
		$vconsole_manage = check_acl ($config['id_user'],
			$map['id_group'], "VM");
		
		$data = array ();
		
		if (!is_metaconsole()) {
			$data[0] = '<a href="index.php?sec=network&amp;sec2=operation/visual_console/render_view&amp;id='.
				$map['id'].'&amp;refr=' . $refr . '">'.$map['name'].'</a>';
		}
		else {
			$data[0] = '<a href="index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=' . $pure .
				'&id_visualmap=' . $map['id'].'&amp;refr=' . $refr . '">'.$map['name'].'</a>';
		}
		
		$data[1] = ui_print_group_icon ($map['id_group'], true);
		$data[2] = db_get_sql ("SELECT COUNT(*) FROM tlayout_data WHERE id_layout = ".$map['id']);
		
		// Fix: IW was the old ACL for report editing, now is RW
		if ($vconsole_write || $vconsole_manage) {
			
			if (!is_metaconsole()) {
				$data[3] = '<a class="copy_visualmap" href="index.php?sec=network&amp;sec2=godmode/reporting/map_builder&amp;id_layout='.$map['id'].'&amp;copy_layout=1">'.html_print_image ("images/copy.png", true).'</a>';
				$data[4] = '<a class="delete_visualmap" href="index.php?sec=network&amp;sec2=godmode/reporting/map_builder&amp;id_layout='.$map['id'].'&amp;delete_layout=1">'.html_print_image ("images/cross.png", true).'</a>';
			}
			else {
				$data[3] = '<a class="copy_visualmap" href="index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=' . $pure . '&id_layout='.$map['id'].'&amp;copy_layout=1">'.html_print_image ("images/copy.png", true).'</a>';
				$data[4] = '<a class="delete_visualmap" href="index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=' . $pure . '&id_layout='.$map['id'].'&amp;delete_layout=1">'.html_print_image ("images/cross.png", true).'</a>';
			}
		}
		array_push ($table->data, $data);
	}
	html_print_table ($table);
}
if ($maps) {
	if (!is_metaconsole())
		echo '<div class="action-buttons" style="width: 0px;">';
	else
		echo '<div class="" style="width: 100%; text-align: right;">';
}
if ($maps || defined("METACONSOLE")) {
	if ($vconsoles_write || $vconsoles_manage) {
		if (!defined('METACONSOLE')) {
			echo '<form action="index.php?sec=network&amp;sec2=godmode/reporting/visual_console_builder" method="post">';
		}
		else {
			echo '<form action="index.php?sec=screen&sec2=screens/screens&action=visualmap&action2=new&operation=new_visualmap&tab=data&pure=' . $pure . '" method="post">';
		}
		html_print_input_hidden ('edit_layout', 1);
		html_print_submit_button (__('Create'), '', false,
			'class="sub next"');
		echo '</form>';
	}
	echo '</div>';
}
?>
