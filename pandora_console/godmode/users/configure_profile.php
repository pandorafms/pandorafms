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

// Global variables
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Profile Management");
	require ("general/noaccess.php");
	return;
}

enterprise_include_once ('meta/include/functions_users_meta.php');

$tab = get_parameter('tab', 'profile');
$pure = get_parameter('pure', 0);

// Header
if (!defined('METACONSOLE')) {
	$buttons = array(
		'user' => array(
			'active' => false,
			'text' => '<a href="index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure='.$pure.'">' . 
				html_print_image ("images/god3.png", true, array ("title" => __('User management'))) .'</a>'),
		'profile' => array(
			'active' => false,
			'text' => '<a href="index.php?sec=gusuarios&sec2=godmode/users/profile_list&tab=profile&pure='.$pure.'">' . 
				html_print_image ("images/profiles.png", true, array ("title" => __('Profile management'))) .'</a>'));

	$buttons[$tab]['active'] = true;

	ui_print_page_header (__('User management').' &raquo; '.__('Profiles defined in Pandora'), "images/god3.png", false, "", true, $buttons);
	$sec2 = 'gusuarios';
}
else {
	
	user_meta_print_header();	
	$sec2 = 'advanced';	
	
}

$new_profile = (bool) get_parameter ('new_profile');
$id_profile = (int) get_parameter ('id');

// Edit profile
if ($id_profile || $new_profile) {
	
	if ($new_profile) {
		$name = '';
		$incident_view = 0;
		$incident_edit = 0;
		$incident_management = 0;
		$agent_view = 0;
		$agent_edit = 0;
		$alert_edit = 0;
		$user_management = 0;
		$db_management = 0;
		$alert_management = 0;
		$pandora_management = 0;
		
		$page_title = __('Create profile');
	}
	else {
		$profile = db_get_row ('tperfil', 'id_perfil', $id_profile);
		
		if ($profile === false) {
			echo '<h3 class="error">'.__('There was a problem loading profile').'</h3></table>';
			echo '</div>';
			echo '<div style="clear:both">&nbsp;</div>';
			echo '</div>';
			echo '<div id="foot">';
			require ("general/footer.php");
			echo '</div>';
			echo '</div>';
			
			exit;
		}
		
		$name = $profile["name"];
		$incident_view = (bool) $profile["incident_view"];
		$incident_edit = (bool) $profile["incident_edit"];
		$incident_management = (bool) $profile["incident_management"];
		$agent_view = (bool) $profile["agent_view"];
		$agent_edit = (bool) $profile["agent_edit"];
		$alert_edit = (bool) $profile["alert_edit"];
		$user_management = (bool) $profile["user_management"];
		$db_management = (bool) $profile["db_management"];
		$alert_management = (bool) $profile["alert_management"];
		$pandora_management = (bool) $profile["pandora_management"];
		
		$id_audit = db_pandora_audit("User management",
			"Edit profile ". $name);
		enterprise_include_once('include/functions_audit.php');
		$info = 'Name: ' . $name . ' Incident view: ' . $incident_view .
			' Incident edit: ' . $incident_edit . ' Incident management: ' . $incident_management .
			' Agent view: ' . $agent_view . ' Agent edit: ' . $agent_edit .
			' Alert edit: ' . $alert_edit . ' User management: ' . $user_management .
			' DB management: ' . $db_management . ' Alert management: ' . $alert_management .
			' Pandora Management: ' . $pandora_management;
		enterprise_hook('audit_pandora_enterprise', array($id_audit, $info));
		
		
		$page_title = __('Update profile');
	}
	
	$table->width = '98%';
	$table->class = 'databox';
	$table->size = array ();
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->data = array ();
	
	$table->data[0][0] = __('Profile name');
	$table->data[0][1] = html_print_input_text ('name', $name, '', 30, 60, true);
	$table->data[1][0] = __('View incidents');
	$table->data[1][1] = html_print_checkbox ('incident_view', 1, $incident_view, true);
	$table->data[2][0] = __('Edit incidents');
	$table->data[2][1] = html_print_checkbox ('incident_edit', 1, $incident_edit, true);
	$table->data[3][0] = __('Manage incidents');
	$table->data[3][1] = html_print_checkbox ('incident_management', 1, $incident_management, true);
	$table->data[4][0] = __('View agents');
	$table->data[4][1] = html_print_checkbox ('agent_view', 1, $agent_view, true);
	$table->data[5][0] = __('Edit agents');
	$table->data[5][1] = html_print_checkbox ('agent_edit', 1, $agent_edit, true);
	$table->data[6][0] = __('Edit alerts');
	$table->data[6][1] = html_print_checkbox ('alert_edit', 1, $alert_edit, true);
	$table->data[7][0] = __('Manage users');
	$table->data[7][1] = html_print_checkbox ('user_management', 1, $user_management, true);
	$table->data[8][0] = __('Manage Database');
	$table->data[8][1] = html_print_checkbox ('db_management', 1, $db_management, true);
	$table->data[9][0] = __('Manage alerts');
	$table->data[9][1] = html_print_checkbox ('alert_management', 1, $alert_management, true);
	$table->data[10][0] = __('Pandora management');
	$table->data[10][1] = html_print_checkbox ('pandora_management', 1, $pandora_management, true);
	
	echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/users/profile_list&pure='.$pure.'">';
	
	html_print_table ($table);
	
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	if ($new_profile) {
		html_print_submit_button (__('Add'), "crt", false, 'class="sub wand"');
		html_print_input_hidden ('create_profile', 1);
	}
	else {
		html_print_input_hidden ('id', $id_profile);
		html_print_input_hidden ('update_profile', 1);
		html_print_submit_button (__('Update'), "upd", false, 'class="sub upd"');
	}
	echo "</div></form>";
}
?>