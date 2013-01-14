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
ui_print_page_header (__('User management').' &raquo; '.__('Profiles defined in Pandora'), "images/god3.png", false, "profile", true, $buttons);

$delete_profile = (bool) get_parameter ('delete_profile');
$create_profile = (bool) get_parameter ('create_profile');
$update_profile = (bool) get_parameter ('update_profile');
$id_profile = (int) get_parameter ('id');

// Profile deletion
if ($delete_profile) {
	// Delete profile
	$profile = db_get_row('tperfil', 'id_perfil', $id_profile);
	$sql = sprintf ('DELETE FROM tperfil WHERE id_perfil = %d', $id_profile);
	$ret = db_process_sql ($sql);
	if ($ret === false) {
		ui_print_error_message(__('There was a problem deleting the profile'));
	}
	else {		
		db_pandora_audit("Profile management",
			"Delete profile ". $profile['name']);
		
		ui_print_success_message(__('Successfully deleted'));
	}
	
	//Delete profile from user data
	$sql = sprintf ('DELETE FROM tusuario_perfil WHERE id_perfil = %d', $id_profile);
	db_process_sql ($sql);
	
	$id_profile = 0;
}

// Update profile
if ($update_profile) {
	$name = get_parameter ("name");
	$incident_view = (bool) get_parameter ("incident_view");
	$incident_edit = (bool) get_parameter ("incident_edit");
	$incident_management = (bool) get_parameter ("incident_management");
	$agent_view = (bool) get_parameter ("agent_view");
	$agent_edit = (bool) get_parameter ("agent_edit");
	$alert_edit = (bool) get_parameter ("alert_edit");	
	$user_management = (bool) get_parameter ("user_management");
	$db_management = (bool) get_parameter ("db_management");
	$alert_management = (bool) get_parameter ("alert_management");
	$pandora_management = (bool) get_parameter ("pandora_management");
	
	if ($name) {
		$sql = sprintf ('UPDATE tperfil SET 
			name = "%s", incident_view = %d, incident_edit = %d,
			incident_management = %d, agent_view = %d, agent_edit = %d,
			alert_edit = %d, user_management = %d, db_management = %d,
			alert_management = %d, pandora_management = %d 	WHERE id_perfil = %d',
			$name, $incident_view, $incident_edit, $incident_management,
			$agent_view, $agent_edit, $alert_edit, $user_management,
			$db_management, $alert_management, $pandora_management,
			$id_profile);
		$ret = db_process_sql ($sql);
		if ($ret !== false) {
			$info = 'Name: ' . $name . ' Incident view: ' . $incident_view .
				' Incident edit: ' . $incident_edit . ' Incident management: ' . $incident_management .
				' Agent view: ' . $agent_view . ' Agent edit: ' . $agent_edit .
				' Alert edit: ' . $alert_edit . ' User management: ' . $user_management .
				' DB management: ' . $db_management . ' Alert management: ' . $alert_management .
				' Pandora Management: ' . $pandora_management;
			db_pandora_audit("User management",
				"Update profile ". $name, false, false, $info);
			
			ui_print_success_message(__('Successfully updated'));
		}
		else {
			ui_print_error_message(__('There was a problem updating this profile'));
		}
	}
	else {
		 ui_print_error_message(__('Profile name cannot be empty'));
	}
	$id_profile = 0;
}

// Create profile
if ($create_profile) {
	$name = get_parameter ("name");
	$incident_view = (bool) get_parameter ("incident_view");
	$incident_edit = (bool) get_parameter ("incident_edit");
	$incident_management = (bool) get_parameter ("incident_management");
	$agent_view = (bool) get_parameter ("agent_view");
	$agent_edit = (bool) get_parameter ("agent_edit");
	$alert_edit = (bool) get_parameter ("alert_edit");	
	$user_management = (bool) get_parameter ("user_management");
	$db_management = (bool) get_parameter ("db_management");
	$alert_management = (bool) get_parameter ("alert_management");
	$pandora_management = (bool) get_parameter ("pandora_management");
	
	$values = array(
		'name' => $name,
		'incident_view' => $incident_view,
		'incident_edit' => $incident_edit,
		'incident_management' => $incident_management,
		'agent_view' => $agent_view,
		'agent_edit' => $agent_edit,
		'alert_edit' => $alert_edit,
		'user_management' => $user_management,
		'db_management' => $db_management,
		'alert_management' => $alert_management,
		'pandora_management' => $pandora_management);

	if ($name) {
		$ret = db_process_sql_insert('tperfil', $values);
	
		if ($ret !== false) {
			ui_print_success_message(__('Successfully created'));
			
			$info = 'Name: ' . $name . ' Incident view: ' . $incident_view .
				' Incident edit: ' . $incident_edit . ' Incident management: ' . $incident_management .
				' Agent view: ' . $agent_view . ' Agent edit: ' . $agent_edit .
				' Alert edit: ' . $alert_edit . ' User management: ' . $user_management .
				' DB management: ' . $db_management . ' Alert management: ' . $alert_management .
				' Pandora Management: ' . $pandora_management;
			db_pandora_audit("User management",
				"Created profile ". $name, false, false, $info);
		}
		else {
			ui_print_error_message(__('There was a problem creating this profile'));
		}
	}
	else {
		 ui_print_error_message(__('There was a problem creating this profile'));
	}
	$id_profile = 0;
}

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
$table->head[11] = '<span title="Operations">' . __('Op.') . '</span>';

$table->align = array_fill (1, 11, "center");
$table->size = array_fill (1, 10, 40);

$profiles = db_get_all_rows_in_table ("tperfil");
if($profiles === false) {
	$profiles = array();
}

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
	$data[11] = '<a href="index.php?sec=gusuarios&amp;sec2=godmode/users/configure_profile&id='.$profile["id_perfil"].'"><b>'. html_print_image('images/config.png', true, array('title' => __('Edit'))) .'</b></a>';
	$data[11] .= '&nbsp;&nbsp;<a href="index.php?sec=gusuarios&sec2=godmode/users/profile_list&delete_profile=1&id='.$profile["id_perfil"].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'. html_print_image("images/cross.png", true) . '</a>';	
	array_push ($table->data, $data);
}
	
	echo '<form method="post" action="index.php?sec=gusuarios&sec2=godmode/users/configure_profile">';
	if(isset($data)) {
		html_print_table ($table);
	}
	else {
		echo "<div class='nf'>".__('There are no defined profiles')."</div>";
	}

	echo '<div class="action-buttons" style="width: '.$table->width.'; margin-top: 5px;">';
	html_print_input_hidden ('new_profile', 1);
	html_print_submit_button (__('Create'), "crt", false, 'class="sub next"');
	echo "</div>";
	echo '</form>';
	unset ($table);

?>
