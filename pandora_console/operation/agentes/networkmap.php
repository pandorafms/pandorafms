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


// Load global vars
global $config;

check_login ();

require_once ('include/functions_networkmap.php');
require_once ('include/functions_clippy.php');

$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

$name = '';
$pure = (int) get_parameter ('pure', 0);
$activeTab = get_parameter ('tab', 'topology');
$id_networkmap = get_parameter ('id_networkmap', 0);
$save_networkmap = get_parameter ('save_networkmap', 0);
$delete_networkmap = get_parameter ('delete_networkmap', 0);
$add_networkmap = get_parameter ('add_networkmap', 0);
$update_networkmap = get_parameter ('update_networkmap', 0);
$recenter_networkmap = get_parameter ('recenter_networkmap', 0);
$hidden_options = get_parameter ('hidden_options', 1);

// ACL checks //
// New networkmap.
if ($add_networkmap) {
	// ACL for the new network map
	// $networkmap_read = check_acl ($config['id_user'], 0, "MR");
	$networkmap_write = check_acl ($config['id_user'], 0, "MW");
	$networkmap_manage = check_acl ($config['id_user'], 0, "MM");
	
	if (!$networkmap_write && !$networkmap_manage) {
		db_pandora_audit("ACL Violation",
			"Trying to accessnode graph builder");
		require ("general/noaccess.php");
		exit;
	}
}
// The networkmap exist. Should have id and store goup.
else {
	// Networkmap id required
	if (empty($id_networkmap)) {
		db_pandora_audit("ACL Violation",
			"Trying to access node graph builder");
		require ("general/noaccess.php");
		exit;
	}
	
	// Get the group for ACL
	$store_group = db_get_value("store_group", "tnetwork_map", "id_networkmap", $id_networkmap);
	if ($store_group === false) {
		db_pandora_audit("ACL Violation",
			"Trying to accessnode graph builder");
		require ("general/noaccess.php");
		exit;
	}
	
	// ACL for the general permission
	$networkmap_read = check_acl ($config['id_user'], $store_group, "MR");
	$networkmap_write = check_acl ($config['id_user'], $store_group, "MW");
	$networkmap_manage = check_acl ($config['id_user'], $store_group, "MM");
	
	if (!$networkmap_read && !$networkmap_write && !$networkmap_manage) {
		db_pandora_audit("ACL Violation",
			"Trying to access node graph builder");
		include ("general/noaccess.php");
		exit;
	}
}

// Create
if ($add_networkmap) {
	// Load variables
	$layout = 'radial';
	$depth = 'all';
	$nooverlap = 0;
	$modwithalerts = 0;
	$hidepolicymodules = 0;
	$zoom = 1;
	$ranksep = 2.5;
	$simple = 0;
	$regen = 1;
	$font_size = 12;
	$text_filter = '';
	$dont_show_subgroups = false;
	$store_group = 0;
	$group = 0;
	$module_group = 0;
	$center = 0;
	$name = $activeTab;
	$show_snmp_modules = 0;
	$l2_network = 0;
	$check = db_get_value('name', 'tnetwork_map', 'name', $name);
	$sql = db_get_value_filter('COUNT(name)', 'tnetwork_map',
		array('name' => "%$name"));
	
	$values = array(
			'name' => ($check ? "($sql) $name" : $name),
			'type' => $activeTab,
			'layout' => $layout,
			'nooverlap' => $nooverlap,
			'simple' => $simple,
			'regenerate' => $regen,
			'font_size' => $font_size,
			'store_group' => $store_group,
			'id_group' => $group,
			'id_module_group' => $module_group,
			'depth' => $depth,
			'only_modules_with_alerts' => $modwithalerts,
			'hide_policy_modules' => $hidepolicymodules,
			'zoom' => $zoom,
			'distance_nodes' => $ranksep,
			'text_filter' => $text_filter,
			'dont_show_subgroups' => $dont_show_subgroups,
			'center' => $center,
			'show_snmp_modules' => $show_snmp_modules,
			'l2_network' => $l2_network
		);
	$id_networkmap = networkmap_create_networkmap($values);
	
	$message = ui_print_result_message ($id_networkmap,
		__('Network map created successfully'),
		__('Could not create network map'), '', true);
	
	// Exit when the networkmap was not created
	if ($id_networkmap === false) {
		return;
	}
}
// Action in existing networkmap
else if ($delete_networkmap || $save_networkmap || $update_networkmap) {
	
	// ACL for the network map
	// if (!isset($networkmap_read))
	// 	$networkmap_read = check_acl ($config['id_user'], $store_group, "MR");
	if (!isset($networkmap_write))
		$networkmap_write = check_acl ($config['id_user'], $store_group, "MW");
	if (!isset($networkmap_manage))
		$networkmap_manage = check_acl ($config['id_user'], $store_group, "MM");
	
	if (!$networkmap_write && !$networkmap_manage) {
		db_pandora_audit("ACL Violation",
			"Trying to accessnode graph builder");
		require ("general/noaccess.php");
		exit;
	}
	
	// Actions //
	
	// Not used now. The new behaviour is delete the map posting to the list.
	if ($delete_networkmap) {
		$result = networkmap_delete_networkmap($id_networkmap);
		$message = ui_print_result_message ($result,
			__('Network map deleted successfully'),
			__('Could not delete network map'), '', true);
		
		return;
	}
	
	// Save updates the db data, update only updates the view.
	if ($save_networkmap || $update_networkmap) {
		// Load variables
		$layout = (string) get_parameter ('layout', 'radial');
		$depth = (string) get_parameter ('depth', 'all');
		$nooverlap = (bool) get_parameter ('nooverlap', 0);
		$modwithalerts = (int) get_parameter ('modwithalerts', 0);
		$hidepolicymodules = (int) get_parameter ('hidepolicymodules', 0);
		$zoom = (float) get_parameter ('zoom', 1);
		$ranksep = (float) get_parameter ('ranksep', 2.5);
		$simple = (int) get_parameter ('simple', 0);
		$regen = (int) get_parameter ('regen', 0);
		$show_snmp_modules = (int) get_parameter ('show_snmp_modules', 0);
		$font_size = (int) get_parameter ('font_size', 12);
		$text_filter = get_parameter ('text_filter', '');
		$dont_show_subgroups = (bool)get_parameter ('dont_show_subgroups', 0);
		$store_group = (int) get_parameter ('store_group', 0);
		$group = (int) get_parameter ('group', 0);
		$module_group = (int) get_parameter ('module_group', 0);
		$center = (int) get_parameter ('center', 0);
		$name = (string) get_parameter ('name', $activeTab);
		$l2_network = (int) get_parameter ('l2_network', 0);
		
		if ($save_networkmap) {
			// ACL for the new network map
			$networkmap_read_new = check_acl ($config['id_user'], $store_group, "MR");
			$networkmap_write_new = check_acl ($config['id_user'], $store_group, "MW");
			$networkmap_manage_new = check_acl ($config['id_user'], $store_group, "MM");

			if (!$networkmap_write_new && !$networkmap_manage_new) {
				db_pandora_audit("ACL Violation",
					"Trying to accessnode graph builder");
				require ("general/noaccess.php");
				exit;
			}
			
			$result = networkmap_update_networkmap($id_networkmap,
				array('name' => $name,
					'type' => $activeTab,
					'layout' => $layout, 
					'nooverlap' => $nooverlap,
					'simple' => $simple,
					'regenerate' => $regen,
					'font_size' => $font_size,
					'store_group' => $store_group,
					'id_group' => $group,
					'id_module_group' => $module_group,
					'depth' => $depth,
					'only_modules_with_alerts' => $modwithalerts, 
					'hide_policy_modules' => $hidepolicymodules,
					'zoom' => $zoom,
					'distance_nodes' => $ranksep,
					'text_filter' => $text_filter,
					'dont_show_subgroups' => $dont_show_subgroups,
					'center' => $center, 
					'show_snmp_modules' => (int)$show_snmp_modules,
					'l2_network' => (int)$l2_network));
			
			$message = ui_print_result_message ($result,
				__('Network map saved successfully'),
				__('Could not save network map'), '', true);
			
			if ($result) {
				// Save the new ACL permisison
				$networkmap_read = $networkmap_read_new;
				$networkmap_write = $networkmap_write_new;
				$networkmap_manage = $networkmap_manage_new;
			}
		}
	}
}

if (!$update_networkmap && !$save_networkmap) {
	$networkmap_data = networkmap_get_networkmap($id_networkmap);
	if (empty($networkmap_data)) {
		ui_print_error_message(__('There was an error loading the network map'));
		return;
	}
	
	// Load variables
	$layout = $networkmap_data['layout'];
	$depth = $networkmap_data['depth'];
	$nooverlap = (bool)$networkmap_data['nooverlap'];
	$modwithalerts = $networkmap_data['only_modules_with_alerts'];
	$hidepolicymodules = $networkmap_data['hide_policy_modules'];
	$zoom = $networkmap_data['zoom'];
	$ranksep = $networkmap_data['distance_nodes'];
	$simple = $networkmap_data['simple'];
	$regen = $networkmap_data['regenerate'];
	$show_snmp_modules = $networkmap_data['show_snmp_modules'];
	$font_size = $networkmap_data['font_size'];
	$text_filter = $networkmap_data['text_filter'];
	$dont_show_subgroups = $networkmap_data['dont_show_subgroups'];
	$store_group = $networkmap_data['store_group'];
	$group = $networkmap_data['id_group'];
	$module_group = $networkmap_data['id_module_group'];
	$center = $networkmap_data['center'];
	$name = io_safe_output($networkmap_data['name']);
	$activeTab = $networkmap_data['type'];
	$l2_network = $networkmap_data['l2_network'];
}

if ($recenter_networkmap) {
	$center = (int) get_parameter ('center', 0);
}

/* Main code */

$qs = http_build_query(array(
		"sec" => "network",
		"sec2" => "operation/agentes/networkmap_list"
	));
$href = "index.php?$qs";

$buttons['list'] = array('active' => false, 'text' => "<a href=\"$href\">" . 
	html_print_image("images/list.png", true, array ("title" => __('List'))) ."</a>");

if ($pure == 1) {
	$qs = http_build_query(array(
			"sec" => "network",
			"sec2" => "operation/agentes/networkmap",
			"id_networkmap" => $id_networkmap,
			"tab" => $activeTab
		));
	$href = "index.php?$qs";
	
	$buttons['screen'] = array('active' => false, 'text' => "<a href=\"$href\">" . 
		html_print_image("images/normal_screen.png", true, array ('title' => __('Normal screen'))) ."</a>");
}
else {
	$qs = http_build_query(array(
			"sec" => "network",
			"sec2" => "operation/agentes/networkmap",
			"id_networkmap" => $id_networkmap,
			"tab" => $activeTab,
			"pure" => 1
		));
	$href = "index.php?$qs";
	
	$buttons['screen'] = array('active' => false, 'text' => "<a href=\"$href\">" . 
		html_print_image("images/full_screen.png", true, array ('title' => __('Full screen'))) ."</a>");
}

if ($networkmap_write || $networkmap_manage) {
	
	$qs = http_build_query(array(
			"sec" => "network",
			"sec2" => "operation/agentes/networkmap_list",
			"id_networkmap" => $id_networkmap,
			"delete_networkmap" => 1
		));
	$href = "index.php?$qs";
	
	$buttons['deletemap'] = array('active' => false, 'text' => "<a href=\"$href\">" . 
		html_print_image("images/delete_mc.png", true, array ("title" => __('Delete map'))) ."</a>");
	
	$qs = http_build_query(array(
			"sec" => "network",
			"sec2" => "operation/agentes/networkmap",
			"id_networkmap" => $id_networkmap,
			"save_networkmap" => 1,
			"tab" => $activeTab,
			"name" => $name,
			"store_group" => $store_group,
			"group" => $group,
			"layout" => $layout,
			"nooverlap" => $nooverlap,
			"simple" => $simple,
			"regen" => $regen,
			"zoom" => $zoom,
			"ranksep" => $ranksep,
			"font_size" => $font_size,
			"depth" => $depth,
			"modwithalerts" => $modwithalerts,
			"text_filter" => $text_filter,
			"dont_show_subgroups" => $dont_show_subgroups,
			"hidepolicymodules" => $hidepolicymodules,
			"module_group" => $module_group,
			"hidden_options" => (int)$hidden_options,
			"show_snmp_modules" => (int)$show_snmp_modules,
			"l2_network" => (int)$l2_network,
			"pure" => $pure
		));
	$href = "index.php?$qs";
	
	$buttons['savemap'] = array('active' => false, 'text' => "<a href=\"$href\">" . 
		html_print_image("images/save_mc.png", true, array ("title" => __('Save map'))) .'</a>');
}

// Disabled. It's a waste of resources to check the ACL of every networkmap
// for only provide a shorthand feature.
// $combolist = '<form name="query_sel" method="post" action="index.php?sec=network&sec2=operation/agentes/networkmap">';

// $networkmaps = networkmap_get_networkmaps('','', true, $strict_user);
// if (empty($networkmaps))
// 	$networkmaps = array();

// $combolist .= html_print_select($networkmaps, 'id_networkmap', $id_networkmap,
// 	'onchange:this.form.submit()', '', 0, true, false, false,
// 	'', false, 'margin-top:4px; margin-left:3px; width:150px;');

// $combolist .= html_print_input_hidden('hidden_options',$hidden_options, true);

// $combolist .= '</form>';

// $buttons['combolist'] = $combolist;

$title = '';
$icon = "images/op_network.png";
switch ($activeTab) {
	case 'topology':
		$title = __('Topology view');
		$icon = "images/op_network.png";
		break;
	case 'groups':
		$title = __('Groups view');
		$icon = "images/group.png";
		break;
	case 'policies':
		$title = __('Policies view');
		$icon = "images/policies_mc.png";
		break;
	case 'dynamic':
		$title = __('Dynamic view');
		$icon = "images/dynamic_network_icon.png";
		break;
	case 'radial_dynamic':
		$title = __('Radial dynamic view');
		$icon = "images/radial_dynamic_network_icon.png";
		break;
}

if (!empty($name)) {
	$title .= " &raquo; ". mb_substr($name, 0, 25);
}

ui_print_page_header (__('Network map') . " - " . $title,
	$icon, false, "network_map", false, $buttons);

if ((tags_has_user_acl_tags()) && (!$strict_user)) {
	ui_print_tags_warning();
}

if ($delete_networkmap || $add_networkmap || $save_networkmap) {
	echo $message;
}

// CONFIGURATION FORM

// Layout selection
$layout_array = array (
	'circular' => 'circular',
	'radial' => 'radial',
	'spring1' => 'spring 1',
	'spring2' => 'spring 2',
	'flat' => 'flat');

$options_form = '<form action="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;id_networkmap='.$id_networkmap.'&amp;tab='.$activeTab.'&amp;pure='.$pure.'&amp;center='.$center.'" method="post">';

// Fill an array with the form inputs
$form_elems = array();

// Name
$element = __('Name') . '&nbsp;' .
	html_print_input_text ('name', io_safe_output($name), '', 25, 50, true);
if ($activeTab == 'groups')
	$element .= clippy_context_help("topology_group");
$form_elems[] = $element;

// Store group
$form_elems[] = __('Store group') . '&nbsp;' .
	html_print_select_groups(false, 'AR', false, 'store_group', $store_group, '', 'All', 0, true);

// Group
$form_elems[] = __('Group') . '&nbsp;' .
	html_print_select_groups(false, 'AR', false, 'group', $group, '', 'All', 0, true);

// Free text
if ($activeTab != 'radial_dynamic') {
	$form_elems[] = __('Free text for search (*):') . '&nbsp;' .
		html_print_input_text('text_filter', $text_filter, '', 25, 100, true);
}

// Module group
if ($activeTab == 'groups' || $activeTab == 'policies' || $activeTab == 'radial_dynamic') {
	$form_elems[] = __('Module group') . '&nbsp;' .
		html_print_select_from_sql ('
			SELECT id_mg, name
			FROM tmodule_group', 'module_group', $module_group, '', 'All', 0, true);
}

// Layout
if ($activeTab != 'dynamic' && $activeTab != 'radial_dynamic') {
	$form_elems[] = __('Layout') . '&nbsp;' .
		html_print_select ($layout_array, 'layout', $layout, '', '', '', true);
}

// Depth
if ($activeTab == 'groups') {
	$depth_levels = array(
		'all' => __('All'),
		'agent' => __('Agents'),
		'group' => __('Groups'));
	$form_elems[] = __('Depth') . '&nbsp;' .
		html_print_select ($depth_levels, 'depth', $depth, '', '', '', true, false, false);
}

// Interfaces
//if ($activeTab == 'topology') {
//	$form_elems[] = __('Show interfaces') . '&nbsp;' .
//		html_print_checkbox ('show_snmp_modules', '1', $show_snmp_modules, true);
//}

// No overlap
if ($activeTab != 'dynamic' && $activeTab != 'radial_dynamic') {
	$form_elems[] = __('No Overlap') . '&nbsp;' .
		html_print_checkbox ('nooverlap', '1', $nooverlap, true);
}

// Modules with alerts
if (($activeTab == 'groups' || $activeTab == 'policies') && $depth == 'all') {
	$form_elems[] = __('Only modules with alerts') . '&nbsp;' .
		html_print_checkbox ('modwithalerts', '1', $modwithalerts, true);
}

// Hide policy modules
if ($activeTab == 'groups') {
	if ($config['enterprise_installed']) {
		$form_elems[] = __('Hide policy modules') . '&nbsp;' .
			html_print_checkbox ('hidepolicymodules', '1', $hidepolicymodules, true);
	}
}

// Simple
if ($activeTab != 'dynamic' && $activeTab != 'radial_dynamic') {
	$form_elems[] = __('Simple') . '&nbsp;' .
		html_print_checkbox ('simple', '1', $simple, true);
}

// Regenerate
if ($activeTab != 'dynamic' && $activeTab != 'radial_dynamic') {
	$form_elems[] = __('Regenerate') . '&nbsp;' .
		html_print_checkbox ('regen', '1', $regen, true);
}

// Zoom
if ($pure == "1") {
	$zoom_array = array (
		'1' => 'x1',
		'1.2' => 'x2',
		'1.6' => 'x3',
		'2' => 'x4',
		'2.5' => 'x5',
		'5' => 'x10',
	);
	
	$form_elems[] = __('Zoom') . '&nbsp;' .
		html_print_select ($zoom_array, 'zoom', $zoom, '', '', '', true, false, false, false);
	
}

// Font
if ($activeTab != 'dynamic' && $activeTab != 'radial_dynamic') {
	$form_elems[] = __('Font') . '&nbsp;' .
		html_print_input_text ('font_size', $font_size, $alt = 'Font size (in pt)', 2, 4, true);
}

// Don't show subgroups
if (($activeTab == 'groups') || ($activeTab == 'topology')) {
	$form_elems[] = __('Don\'t show subgroups:') .
		ui_print_help_tip(__('Only run with it is filter for any group'), true) .
		'&nbsp;' .
		html_print_checkbox ('dont_show_subgroups', '1', $dont_show_subgroups, true);
}

// L2 network
//if ($activeTab == 'topology') {
//	$form_elems[] = __('L2 network interfaces') . '&nbsp;' .
//		html_print_checkbox ('l2_network', '1', $l2_network, true);
//}

// Distance between nodes
if ($nooverlap == 1) {
	$form_elems[] = __('Distance between nodes') . '&nbsp;' .
		html_print_input_text ('ranksep', $ranksep, __('Separation between elements in the map (in Non-overlap mode)'), 3, 4, true);
}

unset($table);
$table->width = '100%';
$table->class = 'databox filters';
$table->data = array();

$max_col = 4;
$col = 0;
$row = 0;

foreach ($form_elems as $key => $element) {
	if ($col >= $max_col) {
		$col = 0;
		$row++;
	}
	$table->size[] = "25%";
	$table->data[$row][$col] = $element;
	$col++;
}

$options_form .= html_print_input_hidden('update_networkmap',1, true) .
	html_print_input_hidden('hidden_options',0, true);
$options_form .= html_print_table ($table, true);
$options_form .= "<div style='width: " . $table->width . "; text-align: right;'>" .
	html_print_submit_button (__('Refresh'), "updbutton", false, 'class="sub next"', true) .
	"</div>";
$options_form .= '</form>';

ui_toggle($options_form, __('Map options'), '', $hidden_options);

switch ($activeTab) {
	case 'groups':
		require_once('operation/agentes/networkmap.groups.php');
		break;
	case 'policies':
		require_once(ENTERPRISE_DIR . '/operation/policies/networkmap.policies.php');
		break;
	case 'dynamic':
		require_once('operation/agentes/networkmap.dinamic.php');
		break;
	case 'radial_dynamic':
		require_once('operation/agentes/networkmap.dinamic.php');
		break;
	default:
	case 'topology':
		require_once('operation/agentes/networkmap.topology.php');
		break;
}
?>
