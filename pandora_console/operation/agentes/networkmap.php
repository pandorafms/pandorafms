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

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access node graph builder");
	include ("general/noaccess.php");
	exit;
}

require_once ('include/functions_networkmap.php');	

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

if ($delete_networkmap) {
	$result = networkmap_delete_networkmap($id_networkmap);
	$message = ui_print_result_message ($result,
		__('Network map deleted successfully'),
		__('Could not delete network map'), '', true);
	
	
	$id_networkmap = 0;
}

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
	$group = 0;
	$module_group = 0;
	$center = 0;
	$name = $activeTab;
	$check = db_get_value('name', 'tnetwork_map', 'name', $name);
	$sql = db_get_value_filter('COUNT(name)', 'tnetwork_map',
		array('name' => "%$name"));
	
	if ($check) {
		$id_networkmap = networkmap_create_networkmap("($sql) ".$name,
			$activeTab, $layout, $nooverlap, $simple, $regen,
			$font_size, $group, $module_group, $depth, $modwithalerts,
			$hidepolicymodules, $zoom, $ranksep, $center, $text_filter,
			$dont_show_subgroups);
		
		$message = ui_print_result_message ($id_networkmap,
			__('Network map created successfully'),
			__('Could not create network map'), '', true);
	}
	else {
		$id_networkmap = networkmap_create_networkmap($name, $activeTab,
			$layout, $nooverlap, $simple, $regen, $font_size, $group,
			$module_group, $depth, $modwithalerts, $hidepolicymodules,
			$zoom, $ranksep, $center, $text_filter, $dont_show_subgroups);
		
		$message = ui_print_result_message ($id_networkmap,
			__('Network map created successfully'),
			__('Could not create network map'), '', true);
	}
}

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
	$group = (int) get_parameter ('group', 0);
	$module_group = (int) get_parameter ('module_group', 0);
	$center = (int) get_parameter ('center', 0);
	$name = (string) get_parameter ('name', $activeTab);
	$l2_network = (int) get_parameter ('l2_network', 0);
	
	if ($save_networkmap) {
		$result = networkmap_update_networkmap($id_networkmap,
			array('name' => $name,
				'type' => $activeTab,
				'layout' => $layout, 
				'nooverlap' => $nooverlap,
				'simple' => $simple,
				'regenerate' => $regen,
				'font_size' => $font_size, 
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
	}
}

$networkmaps = networkmap_get_networkmaps('','', true, $strict_user);

$nomaps = false;
if ($networkmaps === false) {
	$nomaps = true;
}

// If the map id is not defined, we set the first id of the active type
if (!$nomaps && $id_networkmap == 0) {
	$networkmaps_of_type = networkmap_get_networkmaps('', $activeTab);
	if ($networkmaps_of_type !== false) {
		$id_networkmap = reset(array_keys($networkmaps_of_type));
	}
}

if (!$update_networkmap && !$save_networkmap && $id_networkmap != 0) {
	$networkmap_data = networkmap_get_networkmap($id_networkmap);
	
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
	$group = $networkmap_data['id_group'];
	$module_group = $networkmap_data['id_module_group'];
	$center = $networkmap_data['center'];
	$name = $networkmap_data['name'];
	$activeTab = $networkmap_data['type'];
	$l2_network = $networkmap_data['l2_network'];
}

if ($recenter_networkmap) {
	$center = (int) get_parameter ('center', 0);
}

/* Main code */
if ($pure == 1) {
	$buttons['screen'] = array('active' => false,
		'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;tab='.$activeTab.'">' . 
			html_print_image("images/normal_screen.png", true, array ('title' => __('Normal screen'))) .'</a>');
}
else {
	$buttons['screen'] = array('active' => false,
		'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;pure=1&amp;tab='.$activeTab.'">' . 
			html_print_image("images/full_screen.png", true, array ('title' => __('Full screen'))) .'</a>');
}
if (($config['enterprise_installed']) && (!$strict_user)) {
	$buttons['policies'] = array('active' => $activeTab == 'policies',
		'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;tab=policies&amp;pure='.$pure.'">' . 
			html_print_image("images/policies_mc.png", true, array ("title" => __('Policies view'))) .'</a>');
}

$buttons['groups'] = array('active' => $activeTab == 'groups',
	'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;tab=groups&amp;pure='.$pure.'">' . 
		html_print_image("images/group.png", true, array ("title" => __('Groups view'))) .'</a>');

$buttons['topology'] = array('active' => $activeTab == 'topology',
	'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;tab=topology&amp;pure='.$pure.'">' . 
		html_print_image("images/op_network.png", true, array ("title" => __('Topology view'))) .'</a>');

$buttons['dinamic'] = array('active' => $activeTab == 'dinamic',
	'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;tab=dinamic&amp;pure='.$pure.'">' . 
		html_print_image("images/dynamic_network_icon.png", true, array ("title" => __('Dynamic view'))) .'</a>');

if (!$strict_user) {
	$buttons['radial_dinamic'] = array('active' => $activeTab == 'radial_dynamic',
		'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;tab=radial_dynamic&amp;pure='.$pure.'">' . 
			html_print_image("images/radial_dynamic_network_icon.png", true, array ("title" => __('Radial dynamic view'))) .'</a>');
}

$combolist = '<form name="query_sel" method="post" action="index.php?sec=network&sec2=operation/agentes/networkmap">';

$combolist .= html_print_select($networkmaps, 'id_networkmap', $id_networkmap, 'onchange:this.form.submit()', __('No selected'), 0, true, false, false, '', false, 'margin-top:4px; margin-left:3px; width:150px;');

$combolist .= html_print_input_hidden('hidden_options',$hidden_options, true);

$combolist .= '</form>';

$buttons['combolist'] = $combolist;

if (check_acl ($config['id_user'], 0, "RW") || check_acl ($config['id_user'], 0, "RM")) {
	$buttons['addmap'] = array('active' => $activeTab == false,
	'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;add_networkmap=1&amp;tab='.$activeTab.'&amp;pure='.$pure.'">' . 
		html_print_image("images/add_mc.png", true, array ("title" => __('Add map'))) .'</a>');
	
	if (!$nomaps && $id_networkmap != 0) {
		$buttons['deletemap'] = array('active' => $activeTab == false,
		'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;id_networkmap='.$id_networkmap.'&amp;delete_networkmap=1&amp;tab='.$activeTab.'&amp;pure='.$pure.'">' . 
			html_print_image("images/delete_mc.png", true, array ("title" => __('Delete map'))) .'</a>');
		
		$buttons['savemap'] = array('active' => $activeTab == false,
			'text' => '<a href="index.php?sec=network&amp;' .
			'sec2=operation/agentes/networkmap&amp;' .
			'id_networkmap=' . $id_networkmap . '&amp;' .
			'save_networkmap=1&amp;' .
			'tab=' . $activeTab . '&amp;' .
			'save_networkmap=1&amp;' .
			'name=' . $name . '&amp;' .
			'group=' . $group . '&amp;' .
			'layout=' . $layout . '&amp;' .
			'nooverlap=' . $nooverlap . '&amp;' .
			'simple=' . $simple . '&amp;' .
			'regen=' . $regen . '&amp;' .
			'zoom=' . $zoom . '&amp;' .
			'ranksep=' . $ranksep . '&amp;' .
			'font_size=' . $font_size . '&amp;' .
			'depth=' . $depth . '&amp;' .
			'modwithalerts=' . $modwithalerts . '&amp;' .
			'text_filter=' . $text_filter . '&amp;' .
			'dont_show_subgroups=' . $dont_show_subgroups . '&amp;' .
			'hidepolicymodules=' . $hidepolicymodules . '&amp;' .
			'module_group=' . $module_group . '&amp;' .
			'pure=' . $pure . '&amp;' .
			'hidden_options=' . (int)$hidden_options . '&amp;' .
			'show_snmp_modules=' . (int)$show_snmp_modules . '&amp;' .
			'l2_network=' . (int)$l2_network . '">' . 
			html_print_image("images/save_mc.png", true, array ("title" => __('Save map'))) .'</a>');
	}
}

$title = '';
switch ($activeTab) {
	case 'topology':
		$title = __('Topology view');
		break;
	case 'groups':
		$title = __('Groups view');
		break;
	case 'policies':
		$title = __('Policies view');
		break;
	case 'dinamic':
		$title = __('Dynamic view');
		break;
	case 'radial_dinamic':
		$title = __('Radial dynamic view');
		break;
}

if (!empty($name)) {
	$title .= " &raquo; ". mb_substr($name, 0, 25);
}

ui_print_page_header (__('Network map') . " - " . $title,
	"images/op_network.png", false, "network_map", false, $buttons);

if ((tags_has_user_acl_tags()) && (!$strict_user)) {
	ui_print_tags_warning();
}

if ($delete_networkmap || $add_networkmap || $save_networkmap) {
	echo $message;
}

if ($id_networkmap == 0) {
	echo "<div class='nf'>" .
		__('There are no defined maps in this view') . "</div>";
	return;
}

// CONFIGURATION FORM

echo "<br>";

// Layout selection
$layout_array = array (
	'circular' => 'circular',
	'radial' => 'radial',
	'spring1' => 'spring 1',
	'spring2' => 'spring 2',
	'flat' => 'flat');

$options_form = '<form action="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;id_networkmap='.$id_networkmap.'&amp;tab='.$activeTab.'&amp;pure='.$pure.'&amp;center='.$center.'" method="post">';



unset($table);
$table->width = '98%';
$table->class = 'databox';
$table->data = array();
$table->data[0][] = __('Name:') . '&nbsp;' .
	html_print_input_text ('name', $name, '', 25, 50, true);
if ($activeTab == 'groups'){
	$table->data[0][0] .= clippy_context_help("topology_group");
}
$table->data[0][] = __('Group:') . '&nbsp;' .
	html_print_select_groups(false, 'AR', false, 'group', $group, '', 'All', 0, true);
if ($activeTab == 'groups' || $activeTab == 'policies' || $activeTab == 'radial_dynamic') {
	$table->data[0][] = __('Module group') . '&nbsp;' .
		html_print_select_from_sql ('
			SELECT id_mg, name
			FROM tmodule_group', 'module_group', $module_group, '', 'All', 0, true);
}

if ($activeTab == 'topology') {
	$table->data[0][] = __('Show interfaces') . '&nbsp;' .
		html_print_checkbox ('show_snmp_modules', '1', $show_snmp_modules, true);
}

if ($activeTab != 'dinamic' && $activeTab != 'radial_dynamic') {
	$table->data[0][] = __('Layout') . '&nbsp;' .
		html_print_select ($layout_array, 'layout', $layout, '', '', '', true);
}

if ($activeTab == 'groups') {
	$depth_levels = array(
		'all' => __('All'),
		'agent' => __('Agents'),
		'group' => __('Groups'));
	$table->data[0][] = __('Depth') . '&nbsp;' .
		html_print_select ($depth_levels, 'depth', $depth, '', '', '', true, false, false);
}

if ($activeTab == 'policies') {
	$depth_levels = array(
		'all' => __('All'),
		'agent' => __('Agents'),
		'policy' => __('Policies'));
	$table->data[0][] = __('Depth') . '&nbsp;' .
		html_print_select ($depth_levels, 'depth', $depth, '', '', '', true, false, false);
}

if ($activeTab != 'dinamic' && $activeTab != 'radial_dynamic') {
	$table->data[1][] = __('No Overlap') . '&nbsp;' .
		html_print_checkbox ('nooverlap', '1', $nooverlap, true);
}

if (($activeTab == 'groups' || $activeTab == 'policies') &&
	$depth == 'all') {
	$table->data[1][] = __('Only modules with alerts') . '&nbsp;' .
		html_print_checkbox ('modwithalerts', '1', $modwithalerts, true);
	
	if ($activeTab == 'groups') {
		if ($config['enterprise_installed']) {
			$table->data[1][] = __('Hide policy modules') . '&nbsp;' .
				html_print_checkbox ('hidepolicymodules', '1', $hidepolicymodules, true);
		}
	}
}

if ($activeTab != 'dinamic' && $activeTab != 'radial_dynamic') {
	$table->data[1][] = __('Simple') . '&nbsp;' .
		html_print_checkbox ('simple', '1', $simple, true);
}

if ($activeTab != 'dinamic' && $activeTab != 'radial_dynamic') {
	$table->data[1][] = __('Regenerate') . '&nbsp;' .
		html_print_checkbox ('regen', '1', $regen, true);
}

if ($pure == "1") {
	// Zoom
	$zoom_array = array (
		'1' => 'x1',
		'1.2' => 'x2',
		'1.6' => 'x3',
		'2' => 'x4',
		'2.5' => 'x5',
		'5' => 'x10',
	);
	
	$table->data[1][] = __('Zoom') . '&nbsp;' .
		html_print_select ($zoom_array, 'zoom', $zoom, '', '', '', true, false, false, false);
	
}

if ($activeTab != 'dinamic' && $activeTab != 'radial_dynamic') {
	$table->data[1][] = __('Font') . '&nbsp;' .
		html_print_input_text ('font_size', $font_size, $alt = 'Font size (in pt)', 2, 4, true);
}

if ($activeTab != 'radial_dynamic') {
	$table->data[2][] = __('Free text for search (*):') . '&nbsp;' .
		html_print_input_text('text_filter', $text_filter, '', 30, 100, true);
}

if (($activeTab == 'groups') || ($activeTab == 'topology')) {
	$table->data[2][] = __('Don\'t show subgroups:') .
		ui_print_help_tip(__('Only run with it is filter for any group'), true) .
		'&nbsp;' .
		html_print_checkbox ('dont_show_subgroups', '1', $dont_show_subgroups, true);
}

if ($activeTab == 'topology') {
	$table->data[2][] = __('L2 network interfaces') . '&nbsp;' .
		html_print_checkbox ('l2_network', '1', $l2_network, true);
}

if ($nooverlap == 1) {
	$table->data[2][] = __('Distance between nodes') . '&nbsp;' .
		html_print_input_text ('ranksep', $ranksep, __('Separation between elements in the map (in Non-overlap mode)'), 3, 4, true);
}

$options_form .= html_print_input_hidden('update_networkmap',1, true) .
	html_print_input_hidden('hidden_options',0, true);
$options_form .= html_print_table ($table, true);
$options_form .= "<div style='width: " . $table->width . "; text-align: right;'>" .
	html_print_submit_button (__('Refresh'), "updbutton", false, 'class="sub next"', true) .
	"</div>";
$options_form .= '</form>';

ui_toggle($options_form, __('Map options'), '', $hidden_options);

if ($id_networkmap != 0) {
	switch ($activeTab) {
		case 'groups':
			require_once('operation/agentes/networkmap.groups.php');
			break;
		case 'policies':
			require_once(ENTERPRISE_DIR . '/operation/policies/networkmap.policies.php');
			break;
		case 'dinamic':
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
}
?>
