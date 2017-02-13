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

enterprise_hook('open_meta_frame');

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
if (!is_metaconsole()) {
	$buttons = array(
		'user' => array(
			'active' => false,
			'text' => '<a href="index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure='.$pure.'">' . 
				html_print_image ("images/gm_users.png", true, array ("title" => __('User management'))) .'</a>'),
		'profile' => array(
			'active' => false,
			'text' => '<a href="index.php?sec=gusuarios&sec2=godmode/users/profile_list&tab=profile&pure='.$pure.'">' . 
				html_print_image ("images/profiles.png", true, array ("title" => __('Profile management'))) .'</a>'));

	$buttons[$tab]['active'] = true;

	ui_print_page_header (__('User management').' &raquo; '.__('Profiles defined in Pandora'), "images/gm_users.png", false, "", true, $buttons);
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
		// Name
		$name = '';
		
		// Incidents
		$incident_view = 0;
		$incident_edit = 0;
		$incident_management = 0;
		
		// Agents
		$agent_view = 0;
		$agent_edit = 0;
		$agent_disable = 0;
		
		// Alerts
		$alert_edit = 0;
		$alert_management = 0;
		
		// Users
		$user_management = 0;
		
		// DB
		$db_management = 0;
		
		// Pandora
		$pandora_management = 0;
		
		// Events
		$event_view = 0;
		$event_edit = 0;
		$event_management = 0;
		
		// Reports
		$report_view = 0;
		$report_edit = 0;
		$report_management = 0;
		
		// Network maps
		$map_view = 0;
		$map_edit = 0;
		$map_management = 0;
		
		// Visual console
		$vconsole_view = 0;
		$vconsole_edit = 0;
		$vconsole_management = 0;
		
		$page_title = __('Create profile');
	}
	else {
		$profile = db_get_row ('tperfil', 'id_perfil', $id_profile);
		
		if ($profile === false) {
			ui_print_error_message(__('There was a problem loading profile')) . '</table>';
			echo '</div>';
			echo '<div style="clear:both">&nbsp;</div>';
			echo '</div>';
			echo '<div id="foot">';
			require ("general/footer.php");
			echo '</div>';
			echo '</div>';
			
			exit;
		}
		
		// Name
		$name = $profile["name"];
		
		// Incidents
		$incident_view = (bool) $profile["incident_view"];
		$incident_edit = (bool) $profile["incident_edit"];
		$incident_management = (bool) $profile["incident_management"];
		
		// Agents
		$agent_view = (bool) $profile["agent_view"];
		$agent_edit = (bool) $profile["agent_edit"];
		$agent_disable = (bool) $profile["agent_disable"];
		
		// Alerts
		$alert_edit = (bool) $profile["alert_edit"];
		$alert_management = (bool) $profile["alert_management"];
		
		// Users
		$user_management = (bool) $profile["user_management"];
		
		// DB
		$db_management = (bool) $profile["db_management"];
		
		// Pandora
		$pandora_management = (bool) $profile["pandora_management"];
		
		// Events
		$event_view = (bool) $profile["event_view"];
		$event_edit = (bool) $profile["event_edit"];
		$event_management = (bool) $profile["event_management"];
		
		// Reports
		$report_view = (bool) $profile["report_view"];
		$report_edit = (bool) $profile["report_edit"];
		$report_management = (bool) $profile["report_management"];
		
		// Network maps
		$map_view = (bool) $profile["map_view"];
		$map_edit = (bool) $profile["map_edit"];
		$map_management = (bool) $profile["map_management"];
		
		// Visual console
		$vconsole_view = (bool) $profile["vconsole_view"];
		$vconsole_edit = (bool) $profile["vconsole_edit"];
		$vconsole_management = (bool) $profile["vconsole_management"];
		
		$id_audit = db_pandora_audit("User management",
			"Edit profile ". $name);
		enterprise_include_once('include/functions_audit.php');
		
		$info = 'Name: ' . $name .
				
				' Incident view: ' . $incident_view .
				' Incident edit: ' . $incident_edit .
				' Incident management: ' . $incident_management .
				
				' Agent view: ' . $agent_view .
				' Agent edit: ' . $agent_edit .
				' Agent disable: ' . $agent_disable .
				
				' Alert edit: ' . $alert_edit .
				' Alert management: ' . $alert_management .
				
				' User management: ' . $user_management .
				
				' DB management: ' . $db_management .
				
				' Event view: ' . $event_view .
				' Event edit: ' . $event_edit .
				' Event management: ' . $event_management .
				
				' Report view: ' . $report_view .
				' Report edit: ' . $report_edit .
				' Report management: ' . $report_management .
				
				' Network map view: ' . $map_view .
				' Network map edit: ' . $map_edit .
				' Network map management: ' . $map_management .
				
				' Visual console view: ' . $vconsole_view .
				' Visual console edit: ' . $vconsole_edit .
				' Visual console management: ' . $vconsole_management .
				
				' Pandora Management: ' . $pandora_management;
		
		enterprise_hook('audit_pandora_enterprise', array($id_audit, $info));
		
		
		$page_title = __('Update profile');
	}
	
	$table = new stdClass();
	$table->width = '100%';
	$table->class = 'databox filters';
	if (is_metaconsole()) {
		$table->width = '100%';
		$table->class = 'databox data';
		if ($id_profile)
			$table->head[0] = __('Update Profile');
		else
			$table->head[0] = __('Create Profile');
		$table->head_colspan[0] = 4;
		$table->headstyle[0] = 'text-align: center';
	}
	$table->size = array ();
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->data = array ();
	
	// Name
	$row = array();
	$row['name'] = __('Profile name');
	$row['input'] = html_print_input_text ('name', $name, '', 30, 60, true);
	$table->data['name'] = $row;
	$table->data[] = '<hr>';
	
	// Agents
	$row = array();
	$row['name'] = __('View agents');
	$row['input'] = html_print_checkbox ('agent_view', 1, $agent_view, true);
	$table->data['AR'] = $row;
	$row = array();
	$row['name'] = __('Disable agents');
	$row['input'] = html_print_checkbox ('agent_disable', 1, $agent_disable, true);
	$table->data['AD'] = $row;
	$row = array();
	$row['name'] = __('Edit agents');
	$row['input'] = html_print_checkbox ('agent_edit', 1, $agent_edit, true, false, 'autoclick_profile_users(\'agent_edit\',\'agent_view\', \'agent_disable\')');
	$table->data['AW'] = $row;
	$table->data[] = '<hr>';
	
	// Alerts
	$row = array();
	$row['name'] = __('Edit alerts');
	$row['input'] = html_print_checkbox ('alert_edit', 1, $alert_edit, true);
	$table->data['LW'] = $row;
	$row = array();
	$row['name'] = __('Manage alerts');
	$row['input'] = html_print_checkbox ('alert_management', 1, $alert_management, true, false, 'autoclick_profile_users(\'alert_management\', \'alert_edit\', \'false\')');
	$table->data['LM'] = $row;
	$table->data[] = '<hr>';
	
	// Events
	$row = array();
	$row['name'] = __('View events');
	$row['input'] = html_print_checkbox ('event_view', 1, $event_view, true);
	$table->data['ER'] = $row;
	$row = array();
	$row['name'] = __('Edit events');
	$row['input'] = html_print_checkbox ('event_edit', 1, $event_edit, true, false, 'autoclick_profile_users(\'event_edit\', \'event_view\', \'false\')');
	$table->data['EW'] = $row;
	$row = array();
	$row['name'] = __('Manage events');
	$row['input'] = html_print_checkbox ('event_management', 1, $event_management, true, false, 'autoclick_profile_users(\'event_management\', \'event_view\', \'event_edit\')');
	$table->data['EM'] = $row;
	$table->data[] = '<hr>';
		
	// Reports
	$row = array();
	$row['name'] = __('View reports');
	$row['input'] = html_print_checkbox ('report_view', 1, $report_view, true);
	$table->data['RR'] = $row;
	$row = array();
	$row['name'] = __('Edit reports');
	$row['input'] = html_print_checkbox ('report_edit', 1, $report_edit, true, false, 'autoclick_profile_users(\'report_edit\', \'report_view\', \'false\')');
	$table->data['RW'] = $row;
	$row = array();
	$row['name'] = __('Manage reports');
	$row['input'] = html_print_checkbox ('report_management', 1, $report_management, true, false, 'autoclick_profile_users(\'report_management\', \'report_view\', \'report_edit\')');
	$table->data['RM'] = $row;
	$table->data[] = '<hr>';
	
	// Network maps
	$row = array();
	$row['name'] = __('View network maps');
	$row['input'] = html_print_checkbox ('map_view', 1, $map_view, true);
	$table->data['MR'] = $row;
	$row = array();
	$row['name'] = __('Edit network maps');
	$row['input'] = html_print_checkbox ('map_edit', 1, $map_edit, true, false, 'autoclick_profile_users(\'map_edit\', \'map_view\', \'false\')');
	$table->data['MW'] = $row;
	$row = array();
	$row['name'] = __('Manage network maps');
	$row['input'] = html_print_checkbox ('map_management', 1, $map_management, true, false, 'autoclick_profile_users(\'map_management\', \'map_view\', \'map_edit\')');
	$table->data['MM'] = $row;
	$table->data[] = '<hr>';
	
	// Visual console
	$row = array();
	$row['name'] = __('View visual console');
	$row['input'] = html_print_checkbox ('vconsole_view', 1, $vconsole_view, true);
	$table->data['VR'] = $row;
	$row = array();
	$row['name'] = __('Edit visual console');
	$row['input'] = html_print_checkbox ('vconsole_edit', 1, $vconsole_edit, true, false, 'autoclick_profile_users(\'vconsole_edit\', \'vconsole_view\', \'false\')');
	$table->data['VW'] = $row;
	$row = array();
	$row['name'] = __('Manage visual console');
	$row['input'] = html_print_checkbox ('vconsole_management', 1, $vconsole_management, true, false, 'autoclick_profile_users(\'vconsole_management\', \'vconsole_view\', \'vconsole_edit\')');
	$table->data['VM'] = $row;
	$table->data[] = '<hr>';
	
	// Incidents
	$row = array();
	$row['name'] = __('View incidents');
	$row['input'] = html_print_checkbox ('incident_view', 1, $incident_view, true);
	$table->data['IR'] = $row;
	$row = array();
	$row['name'] = __('Edit incidents');
	$row['input'] = html_print_checkbox ('incident_edit', 1, $incident_edit, true, false, 'autoclick_profile_users(\'incident_edit\', \'incident_view\', \'false\')');
	$table->data['IW'] = $row;
	$row = array();
	$row['name'] = __('Manage incidents');
	$row['input'] = html_print_checkbox ('incident_management', 1, $incident_management, true, false, 'autoclick_profile_users(\'incident_management\', \'incident_view\', \'incident_edit\');');
	$table->data['IM'] = $row;
	$table->data[] = '<hr>';
	
	// Users
	$row = array();
	$row['name'] = __('Manage users');
	$row['input'] = html_print_checkbox ('user_management', 1, $user_management, true);
	$table->data['UM'] = $row;
	$table->data[] = '<hr>';
	
	// DB
	$row = array();
	$row['name'] = __('Manage database');
	$row['input'] = html_print_checkbox ('db_management', 1, $db_management, true);
	$table->data['DM'] = $row;
	$table->data[] = '<hr>';
	
	// Pandora
	$row = array();
	$row['name'] = __('Pandora management');
	$row['input'] = html_print_checkbox ('pandora_management', 1, $pandora_management, true);
	$table->data['PM'] = $row;
	$table->data[] = '<hr>';
	
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

enterprise_hook('close_meta_frame');
?>
