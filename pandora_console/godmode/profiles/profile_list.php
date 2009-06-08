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

// Global variables
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Profile Management");
	require ("general/noaccess.php");
	return;
}

//Page title definitation. Will be overridden by Edit and Create Profile
$page_title = __('Profiles defined in Pandora');

$new_profile = (bool) get_parameter ('new_profile');
$create_profile = (bool) get_parameter ('create_profile');
$delete_profile = (bool) get_parameter ('delete_profile');
$update_profile = (bool) get_parameter ('update_profile');
$id_profile = (int) get_parameter ('id');

// Profile deletion
if ($delete_profile) {
	// Delete profile
	$sql = sprintf ('DELETE FROM tperfil WHERE id_perfil = %d', $id_profile);
	$ret = process_sql ($sql);
	if ($ret === false) {
		echo '<h3 class="error">'.__('There was a problem deleting the profile').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Profile successfully deleted').'</h3>';
	}
	
	//Delete profile from user data
	$sql = sprintf ('DELETE FROM tusuario_perfil WHERE id_perfil = %d', $id_profile);
	process_sql ($sql);
	
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
	
	$sql = sprintf ('UPDATE tperfil SET 
		name = "%s", incident_view = %d, incident_edit = %d,
		incident_management = %d, agent_view = %d, agent_edit = %d,
		alert_edit = %d, user_management = %d, db_management = %d,
		alert_management = %d, pandora_management = %d 	WHERE id_perfil = %d',
		$name, $incident_view, $incident_edit, $incident_management,
		$agent_view, $agent_edit, $alert_edit, $user_management,
		$db_management, $alert_management, $pandora_management,
		$id_profile);
	$ret = process_sql ($sql);
	if ($ret !== false) {
		echo '<h3 class="suc">'.__('Profile successfully updated').'</h3>';
	} else {
		echo '<h3 class="error"'.__('There was a problem updating this profile').'</h3>';
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
	
	$sql = sprintf ('INSERT INTO tperfil 
		(name, incident_view, incident_edit, incident_management, agent_view,
		agent_edit, alert_edit, user_management, db_management,
		alert_management, pandora_management) 
		VALUES ("%s", %d, %d, %d, %d, %d, %d, %d, %d, %d, %d)',
		$name, $incident_view, $incident_edit, $incident_management,
		$agent_view, $agent_edit, $alert_edit, $user_management,
		$db_management, $alert_management, $pandora_management);
	
	$ret = process_sql ($sql, 'insert_id');
	if ($ret !== false) {
		echo '<h3 class="suc">'.__('Profile successfully created').'</h3>';
	} else {
		echo '<h3 class="error">'.__('There was a problem creating this profile').'</h3>';
	}
	$id_profile = 0;
}

echo '<h2>'.__('Profile management').' &raquo; '.$page_title.'</h2>';

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
	} else {
		$profile = get_db_row ('tperfil', 'id_perfil', $id_profile);
	
		if ($profile === false) {
			echo '<h3 class="error">'.__('There was a problem loading profile').'</h3></table>';
			include ("general/footer.php"); 
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
		
		$page_title = __('Update profile');
	}
	
	$table->width = '400px';
	$table->class = 'databox';
	$table->size = array ();
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->data = array ();
	
	$table->data[0][0] = __('Profile name');
	$table->data[0][1] = print_input_text ('name', $name, '', 30, 60, true);
	$table->data[1][0] = __('View incidents');
	$table->data[1][1] = print_checkbox ('incident_view', 1, $incident_view, true);
	$table->data[2][0] = __('Edit incidents');
	$table->data[2][1] = print_checkbox ('incident_edit', 1, $incident_edit, true);
	$table->data[3][0] = __('Manage incidents');
	$table->data[3][1] = print_checkbox ('incident_management', 1, $incident_management, true);
	$table->data[4][0] = __('View agents');
	$table->data[4][1] = print_checkbox ('agent_view', 1, $agent_view, true);
	$table->data[5][0] = __('Edit agents');
	$table->data[5][1] = print_checkbox ('agent_edit', 1, $agent_edit, true);
	$table->data[6][0] = __('Edit alerts');
	$table->data[6][1] = print_checkbox ('alert_edit', 1, $alert_edit, true);
	$table->data[7][0] = __('Manage alerts');
	$table->data[7][1] = print_checkbox ('alert_management', 1, $alert_management, true);
	$table->data[8][0] = __('Manage users');
	$table->data[8][1] = print_checkbox ('user_management', 1, $user_management, true);
	$table->data[9][0] = __('Manage Database');
	$table->data[9][1] = print_checkbox ('db_management', 1, $db_management, true);
	$table->data[10][0] = __('Pandora management');
	$table->data[10][1] = print_checkbox ('pandora_management', 1, $pandora_management, true);
	
	echo '<form method="post" action="index.php?sec=gperfiles&sec2=godmode/profiles/profile_list">';
	
	print_table ($table);
	
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	if ($new_profile) {
		print_submit_button (__('Create'), "crt", false, 'class="sub next"');
		print_input_hidden ('create_profile', 1);
	} else {
		print_input_hidden ('id', $id_profile);
		print_input_hidden ('update_profile', 1);
		print_submit_button (__('Update'), "upd", false, 'class="sub upd"');
	}
	echo "</div></form>";

} else {
	// View list data
	$table->class = "databox";
	$table->width = '750px';
	$table->data = array ();
	$table->size = array ();
	$table->size[0] = '180px';
	$table->size[1] = '40px';
	$table->size[2] = '40px';
	$table->size[3] = '40px';
	$table->size[4] = '40px';
	$table->size[5] = '40px';
	$table->size[6] = '40px';
	$table->size[7] = '40px';
	$table->size[8] = '40px';
	$table->size[9] = '40px';
	$table->size[10] = '40px';
	$table->size[11] = '40px';
	
	$table->head = array ();
	$table->head[0] = __('Profiles');
	$table->head[1] = 'IR'.print_help_tip (__('Read Incidents'), true);
	$table->head[2] = 'IW'.print_help_tip (__('Create Incidents'), true);
	$table->head[3] = 'IM'.print_help_tip (__('Manage Incidents'), true);
	$table->head[4] = 'AR'.print_help_tip (__('Read Agent Information'), true);
	$table->head[5] = 'AW'.print_help_tip (__('Manage Agents'), true);
	$table->head[6] = 'LW'.print_help_tip (__('Edit Alerts'), true);
	$table->head[7] = 'UM'.print_help_tip (__('Manage User Rights'), true);
	$table->head[8] = 'DM'.print_help_tip (__('Database Management'), true);
	$table->head[9] = 'LM'.print_help_tip (__('Alerts Management'), true);
	$table->head[10] = 'PM'.print_help_tip (__('Pandora System Management'), true);
	$table->head[11] = __('Delete');
	
	$table->align = array ();
	$table->align[1] = 'center';
	$table->align[2] = 'center';
	$table->align[3] = 'center';
	$table->align[4] = 'center';
	$table->align[5] = 'center';
	$table->align[6] = 'center';
	$table->align[7] = 'center';
	$table->align[8] = 'center';
	$table->align[9] = 'center';
	$table->align[10] = 'center';
	$table->align[11] = 'center';
	
	$profiles = get_db_all_rows_in_table ('tperfil');
	if ($profiles === false)
		$profiles = array ();
	
	foreach ($profiles as $profile) {
		$data = array ();
		
		$data[0] = '<a href="index.php?sec=gperfiles&amp;sec2=godmode/profiles/profile_list&id='.$profile["id_perfil"].'"><b>'.$profile["name"].'</b></a>';
		$data[1] = $profile["incident_view"] ? '<img src="images/ok.png">' : '';
		$data[2] = $profile["incident_edit"] ? '<img src="images/ok.png">' : '';
		$data[3] = $profile["incident_management"] ? '<img src="images/ok.png">' : '';
		$data[4] = $profile["agent_view"] ? '<img src="images/ok.png">' : '';
		$data[5] = $profile["agent_edit"] ? '<img src="images/ok.png">' : '';
		$data[6] = $profile["alert_edit"] ? '<img src="images/ok.png">' : '';
		$data[7] = $profile["user_management"] ? '<img src="images/ok.png">' : '';
		$data[8] = $profile["db_management"] ? '<img src="images/ok.png">' : '';
		$data[9] = $profile["alert_management"] ? '<img src="images/ok.png">' : '';
		$data[10] = $profile["pandora_management"] ? '<img src="images/ok.png">' : '';
		$data[11] = '<a href="index.php?sec=gagente&sec2=godmode/profiles/profile_list&delete_profile=1&id='.$profile["id_perfil"].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;"><img src="images/cross.png"></a>';
		
		array_push ($table->data, $data);
	}
	
	echo '<form method="post" action="index.php?sec=gperfiles&sec2=godmode/profiles/profile_list">';
	print_table ($table);
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	print_input_hidden ('new_profile', 1);
	print_submit_button (__('Create profile'), "crt", false, 'class="sub next"');
	echo "</div>";
	echo '</form>';
}
?>
