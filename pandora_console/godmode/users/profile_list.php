<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
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

include_once($config['homedir'] . "/include/functions_profile.php");
include_once ($config['homedir'].'/include/functions_users.php');
require_once ($config['homedir'] . '/include/functions_groups.php');

if (! check_acl ($config['id_user'], 0, "UM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access User Management");
	require ("general/noaccess.php");
	exit;
}

$tab = get_parameter('tab', 'profile');

$buttons = array(
	'user' => array(
		'active' => false,
		'text' => '<a href="index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user">' . 
			html_print_image ("images/god3.png", true, array ("title" => __('User management'))) .'</a>'),
	'profile' => array(
		'active' => false,
		'text' => '<a href="index.php?sec=gusuarios&sec2=godmode/users/profile_list&tab=profile">' . 
			html_print_image ("images/profiles.png", true, array ("title" => __('Profile management'))) .'</a>'));
			
$buttons[$tab]['active'] = true;


// Header
ui_print_page_header (__('User management').' &raquo; '.__('Profiles defined in Pandora'), "images/god3.png", false, "", true, $buttons);

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = 'databox';
$table->width = '98%';

$table->head = array ();
$table->data = array ();
$table->size = array ();
$table->align = array ();

$table->head[0] = __('Profiles');

$table->head[1] = "IR" . ui_print_help_tip (__('System incidents reading'), true);
$table->head[2] = "IW" . ui_print_help_tip (__('System incidents writing'), true);
$table->head[3] = "IM" . ui_print_help_tip (__('System incidents management'), true);
$table->head[4] = "AR" . ui_print_help_tip (__('Agents reading'), true);
$table->head[5] = "AW" . ui_print_help_tip (__('Agents management'), true);
$table->head[6] = "LW" . ui_print_help_tip (__('Alerts editing'), true);
$table->head[7] = "UM" . ui_print_help_tip (__('Users management'), true);
$table->head[8] = "DM" . ui_print_help_tip (__('Database management'), true);
$table->head[9] = "LM" . ui_print_help_tip (__('Alerts management'), true);
$table->head[10] = "PM" . ui_print_help_tip (__('Systems management'), true);
$table->head[11] = __('Delete');

$table->align = array_fill (1, 11, "center");
$table->size = array_fill (1, 10, 40);

$profiles = db_get_all_rows_in_table ("tperfil");

$img = html_print_image ("images/ok.png", true, array ("border" => 0)); 

foreach ($profiles as $profile) {
	$data[0] = '<a href="index.php?sec=gusuarios&amp;sec2=godmode/users/configure_profile&id='.$profile["id_perfil"].'"><b>'.$profile["name"].'</b></a>';
	$data[1] = ($profile["incident_view"] ? $img : '');
	$data[2] = ($profile["incident_edit"] ? $img : '');
	$data[3] = ($profile["incident_management"] ? $img : '');
	$data[4] = ($profile["agent_view"] ? $img : '');
	$data[5] = ($profile["agent_edit"] ? $img : '');
	$data[6] = ($profile["alert_edit"] ? $img : '');
	$data[7] = ($profile["user_management"] ? $img : '');
	$data[8] = ($profile["db_management"] ? $img : '');
	$data[9] = ($profile["alert_management"] ? $img : '');
	$data[10] = ($profile["pandora_management"] ? $img : '');
	$data[11] = '<a href="index.php?sec=gagente&sec2=godmode/users/configure_profile&delete_profile=1&id='.$profile["id_perfil"].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'. html_print_image("images/cross.png", true) . '</a>';	
	array_push ($table->data, $data);
}
	
	echo '<form method="post" action="index.php?sec=gusuarios&sec2=godmode/users/configure_profile">';
	html_print_table ($table);
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	html_print_input_hidden ('new_profile', 1);
	html_print_submit_button (__('Create'), "crt", false, 'class="sub next"');
	echo "</div>";
	echo '</form>';
	unset ($table);

?>
