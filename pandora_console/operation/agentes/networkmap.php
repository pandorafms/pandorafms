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
	
	echo $message;
	
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
			$hidepolicymodules, $zoom, $ranksep, $center);
		
		$message = ui_print_result_message ($id_networkmap,
			__('Network map created successfully'),
			__('Could not create network map'), '', true);
	}
	else {
		$id_networkmap = networkmap_create_networkmap($name, $activeTab,
			$layout, $nooverlap, $simple, $regen, $font_size, $group,
			$module_group, $depth, $modwithalerts, $hidepolicymodules,
			$zoom, $ranksep, $center);
		
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
	$group = (int) get_parameter ('group', 0);
	$module_group = (int) get_parameter ('module_group', 0);
	$center = (int) get_parameter ('center', 0);
	$name = (string) get_parameter ('name', $activeTab);
	
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
				'center' => $center, 
				'show_snmp_modules' => (int)$show_snmp_modules));
		
		$message = ui_print_result_message ($result,
			__('Network map saved successfully'),
			__('Could not save network map'), '', true);
	}
}

$networkmaps = networkmap_get_networkmaps();

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
	$group = $networkmap_data['id_group'];
	$module_group = $networkmap_data['id_module_group'];
	$center = $networkmap_data['center'];
	$name = $networkmap_data['name'];
	$activeTab = $networkmap_data['type'];
}

if ($recenter_networkmap) {
	$center = (int) get_parameter ('center', 0);
}

/* Main code */
if ($pure == 1) {
	$buttons['screen'] = array('active' => false,
		'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;tab='.$activeTab.'">' . 
			html_print_image("images/normalscreen.png", true, array ('title' => __('Normal screen'))) .'</a>');
}
else {
	$buttons['screen'] = array('active' => false,
		'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;pure=1&amp;tab='.$activeTab.'">' . 
			html_print_image("images/fullscreen.png", true, array ('title' => __('Full screen'))) .'</a>');
}
if ($config['enterprise_installed']) {
	$buttons['policies'] = array('active' => $activeTab == 'policies',
		'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;tab=policies&amp;pure='.$pure.'">' . 
			html_print_image("images/policies.png", true, array ("title" => __('Policies view'))) .'</a>');
}

$buttons['groups'] = array('active' => $activeTab == 'groups',
	'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;tab=groups&amp;pure='.$pure.'">' . 
		html_print_image("images/group.png", true, array ("title" => __('Groups view'))) .'</a>');

$buttons['topology'] = array('active' => $activeTab == 'topology',
	'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;tab=topology&amp;pure='.$pure.'">' . 
		html_print_image("images/recon.png", true, array ("title" => __('Topology view'))) .'</a>');

$buttons['separator'] = array('separator' => '');

$combolist = '<form name="query_sel" method="post" action="index.php?sec=network&sec2=operation/agentes/networkmap">';

$combolist .= html_print_select($networkmaps, 'id_networkmap', $id_networkmap, 'onchange:this.form.submit()', __('No selected'), 0, true, false, false, '', false, 'margin-top:4px; margin-left:3px; width:150px;');

$combolist .= html_print_input_hidden('hidden_options',$hidden_options, true);

$combolist .= '</form>';

$buttons['combolist'] = $combolist;

$buttons['addmap'] = array('active' => $activeTab == false,
	'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;add_networkmap=1&amp;tab='.$activeTab.'&amp;pure='.$pure.'">' . 
		html_print_image("images/add.png", true, array ("title" => __('Add map'))) .'</a>');

if (!$nomaps && $id_networkmap != 0) {
	$buttons['deletemap'] = array('active' => $activeTab == false,
		'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;id_networkmap='.$id_networkmap.'&amp;delete_networkmap=1&amp;tab='.$activeTab.'&amp;pure='.$pure.'">' . 
			html_print_image("images/cross.png", true, array ("title" => __('Delete map'))) .'</a>');
	
	$buttons['savemap'] = array('active' => $activeTab == false,
		'text' => '<a href="index.php?sec=network&amp;sec2=operation/agentes/networkmap&amp;id_networkmap='.$id_networkmap.'&amp;save_networkmap=1
			&amp;tab='.$activeTab.'&amp;save_networkmap=1&amp;name='.$name.'&amp;group='.$group.'
			&amp;layout='.$layout.'&amp;nooverlap='.$nooverlap.'&amp;simple='.$simple.'&amp;regen='.$regen.'
			&amp;zoom='.$zoom.'&amp;ranksep='.$ranksep.'&amp;fontsize='.$font_size.'&amp;depth='.$depth.'
			&amp;modwithalerts='.$modwithalerts.'&amp;hidepolicymodules='.$hidepolicymodules.'
			&amp;module_group='.$module_group.'&amp;pure='.$pure.'&amp;hidden_options='.(int)$hidden_options.'
			&amp;show_snmp_modules='.(int)$show_snmp_modules.'">' . 
			html_print_image("images/file.png", true, array ("title" => __('Save map'))) .'</a>');
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
}

if (!empty($name)) {
	$title .= " &raquo; ". mb_substr($name, 0, 25);
}

ui_print_page_header (__('Network map') . " - " . $title,
	"images/bricks.png", false, "network_map", false, $buttons);


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
$options_form .= '<table cellpadding="4" cellspacing="4" class="databox" width="99%">';
$options_form .= '<tr><td>';
$options_form .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
$options_form .= '<tr>';
$options_form .= '<td>';
$options_form .= __('Name') . '<br />';
$options_form .= html_print_input_text ('name', $name, '', 25, 25, true);
$options_form .= '</td>';
$options_form .= '<td valign="top">' . __('Group') . '<br />';
$options_form .= html_print_select_groups(false, 'AR', false, 'group', $group, '', 'All', 0, true);
$options_form .= '</td>';
if ($activeTab == 'groups' || $activeTab == 'policies') {
	$options_form .= '<td valign="top">' . __('Module group') . '<br />';
	$options_form .= html_print_select_from_sql ('SELECT id_mg, name FROM tmodule_group', 'module_group', $module_group, '', 'All', 0, true);
	$options_form .= '</td>';
}

if ($activeTab == 'topology') {
	$options_form .= '<td valign="top">' . __('Show interfaces') . '<br />';
	$options_form .= html_print_checkbox ('show_snmp_modules', '1', $show_snmp_modules, true);
	$options_form .= '</td>';
}

$options_form .= '<td valign="top">' . __('Layout') . '<br />';
$options_form .= html_print_select ($layout_array, 'layout', $layout, '', '', '', true);
$options_form .= '</td>';

if ($activeTab == 'groups') {
	$options_form .= '<td valign="top">' . __('Depth') . '<br />';
	$depth_levels = array('all' => __('All'), 'agent' => __('Agents'), 'group' => __('Groups'));
	$options_form .= html_print_select ($depth_levels, 'depth', $depth, '', '', '', true, false, false);
	$options_form .= '</td>';
}

if ($activeTab == 'policies') {
	$options_form .= '<td valign="top">' . __('Depth') . '<br />';
	$depth_levels = array(
		'all' => __('All'),
		'agent' => __('Agents'),
		'policy' => __('Policies'));
	$options_form .= html_print_select ($depth_levels, 'depth', $depth, '', '', '', true, false, false);
	$options_form .= '</td>';
}

$options_form .= '</tr></table>';
$options_form .= '</td></tr><tr><td>';
$options_form .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
$options_form .= '<tr><td valign="top">' . __('No Overlap') . '<br />';
$options_form .= html_print_checkbox ('nooverlap', '1', $nooverlap, true);
$options_form .= '</td>';

if (($activeTab == 'groups' || $activeTab == 'policies') &&
	$depth == 'all') {
	$options_form .= '<td valign="top">' . __('Only modules with alerts') . '<br />';
	$options_form .= html_print_checkbox ('modwithalerts', '1', $modwithalerts, true);
	$options_form .= '</td>';
	
	if ($activeTab == 'groups') {
		if ($config['enterprise_installed']) {
			$options_form .= '<td valign="top">' . __('Hide policy modules') . '<br />';
			$options_form .= html_print_checkbox ('hidepolicymodules', '1', $hidepolicymodules, true);
			$options_form .= '</td>';
		}
	}
}

$options_form .= '<td valign="top">' . __('Simple') . '<br />';
$options_form .= html_print_checkbox ('simple', '1', $simple, true);
$options_form .= '</td>';

$options_form .= '<td valign="top">' . __('Regenerate') . '<br />';
$options_form .= html_print_checkbox ('regen', '1', $regen, true);
$options_form .= '</td>';

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
	
	$options_form .= '<td valign="top">' . __('Zoom') . '<br />';
	$options_form .= html_print_select ($zoom_array, 'zoom', $zoom, '', '', '', true, false, false, false);
	$options_form .= '</td>';
	
}

if ($nooverlap == 1) {
	$options_form .= "<td>";
	$options_form .= __('Distance between nodes') . '<br />';
	$options_form .= html_print_input_text ('ranksep', $ranksep, __('Separation between elements in the map (in Non-overlap mode)'), 3, 4, true);
	$options_form .= "</td>";
}

$options_form .= "<td>";
$options_form .= __('Font') . '<br />';
$options_form .= html_print_input_text ('font_size', $font_size, $alt = 'Font size (in pt)', 2, 4, true);
$options_form .= "</td>";

$options_form .= '<td>';
$options_form .= html_print_input_hidden('update_networkmap',1, true);
$options_form .= html_print_input_hidden('hidden_options',0, true);
$options_form .= html_print_submit_button (__('Refresh'), "updbutton", false, 'class="sub next"', true);
$options_form .= '</td></tr>';
$options_form .= '</table></table></form>';

ui_toggle($options_form, __('Map options'), '', $hidden_options);

if ($id_networkmap != 0) {
	switch ($activeTab) {
		case 'groups':
			require_once('operation/agentes/networkmap.groups.php');
			break;
		case 'policies':
			require_once(ENTERPRISE_DIR . '/operation/policies/networkmap.policies.php');
			break;
		default:
		case 'topology':
			require_once('operation/agentes/networkmap.topology.php');
			break;
	}
}
?>
