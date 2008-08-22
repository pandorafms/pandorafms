<?php
// Pandora FMS - the Flexible monitoring system
// ============================================
// Copyright (c) 2004-2008 Sancho Lerena, <slerena@gmail.com>
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
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

// Profile deletion
if (isset ($_GET["delete_profile"])){ // if any parameter is modified
	$id_profile = safe_input ($_GET["delete_profile"]);
	
	// Delete profile
	$query = "DELETE FROM tperfil WHERE id_perfil = '".$id_profile."'";
	$ret = process_sql ($query);
	if ($ret === false) {
		echo '<h3 class="error">'.__('There was a problem deleting the profile').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Profile successfully deleted').'</h3>';
	}
	
	//Delete profile from user data
	$query = "DELETE FROM tusuario_perfil WHERE id_perfil = '".$id_profile."'";
	process_sql ($query);
	
	unset($id_profile); // forget it to show list
} elseif (isset ($_GET["new_profile"])) { // create a new profile
	$id_perfil = -1;
	$name = "";
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
} elseif (isset ($_GET["edit_profile"])) { // Edit profile (read data to show in form)
	$id_perfil = safe_input ($_GET["edit_profile"]);
	$row = get_db_row_sql("SELECT * FROM tperfil WHERE id_perfil = '".$id_perfil."'");
	
	if ($row === false) {
		echo '<h3 class="error">'.__('There was a problem loading profile').'</h3></table>'; //Error and close open table
		include ("general/footer.php"); 
		exit;
	} else {
		$name = $row["name"];
		$incident_view = $row["incident_view"];
		$incident_edit = $row["incident_edit"];
		$incident_management = $row["incident_management"];
		$agent_view = $row["agent_view"];
		$agent_edit =$row["agent_edit"];
		$alert_edit = $row["alert_edit"];
		$user_management = $row["user_management"];
		$db_management = $row["db_management"];
		$alert_management = $row["alert_management"];
		$pandora_management = $row["pandora_management"];
		unset ($row); //clean up variables
	}
	
	$page_title = __('Update profile');
	
} elseif (isset ($_GET["update_data"])) { // Update or Create a new record (writes on DB)
	// Profile edit
	$id_profile = (int) get_parameter_post ("id_perfil",-1);
	$name = get_parameter_post ("name");
	
	$incident_view = (bool) get_parameter_post ("incident_view",0);
	$incident_edit = (bool) get_parameter_post ("incident_edit",0);
	$incident_management = (bool) get_parameter_post ("incident_management",0);
	$agent_view = (bool) get_parameter_post ("agent_view",0);
	$agent_edit = (bool) get_parameter_post ("agent_edit",0);
	$alert_edit = (bool) get_parameter_post ("alert_edit",0);	
	$user_management = (bool) get_parameter_post ("user_management",0);
	$db_management = (bool) get_parameter_post ("db_management",0);
	$alert_management = (bool) get_parameter_post ("alert_management",0);
	$pandora_management = (bool) get_parameter_post ("pandora_management",0);
	
	// update or insert ??
	
	if ($id_profile == -1) { // INSERT
		$query = "INSERT INTO tperfil 
		(name,incident_view,incident_edit,incident_management,agent_view,agent_edit,alert_edit,user_management,db_management,alert_management,pandora_management) 
		VALUES 
		('".$name."','".$incident_view."','".$incident_edit."','".$incident_management."','".$agent_view."','".$agent_edit."','".$alert_edit."','".$user_management."','".$db_management."','".$alert_management."','".$pandora_management."')";
		// echo "DEBUG: ".$query;
		$ret = process_sql ($query);
		if ($ret !== false) {
			echo '<h3 class="suc">'.__('Profile successfully created').'</h3>';
		} else {
			echo '<h3 class="error">'.__('There was a problem creating this profile').'</h3>';
		}
	} else { // UPDATE
		$query = "UPDATE tperfil SET 
		name = '".$name."',
		incident_view = '".$incident_view."',
		incident_edit = '".$incident_edit."',
		incident_management = '".$incident_management."',
		agent_view = '".$agent_view."',
		agent_edit = '".$agent_edit."',
		alert_edit = '".$alert_edit."',
		user_management = '".$user_management."',
		db_management = '".$db_management."',
		alert_management = '".$alert_management."',
		pandora_management = '".$pandora_management."' 
		WHERE id_perfil = '".$id_profile."'";
		// echo "DEBUG: ".$query;
		$ret = process_sql ($query);
		if ($ret !== false) {
			echo '<h3 class="suc">'.__('Profile successfully updated').'</h3>';
		} else {
			echo '<h3 class="error"'.__('There was a problem updating this profile').'</h3>';
		}
	}
 	unset ($id_profile);
}

echo '<h2>'.__('Profile management').' &gt; '.$page_title.'</h2>';

// Form to manage date
if (isset ($id_perfil)){ // There are values defined, let's show form with data for INSERT or UPDATE
	echo '<table width="400" cellpadding="4" cellspacing="4" class="databox">
		<form method="POST" action="index.php?sec=gperfiles&sec2=godmode/profiles/profile_list&update_data">
		<input type="hidden" name="id_perfil" value="'.$id_perfil.'" />
		<tr>
			<td class="datos">'.__('Profile name').'</td>
			<td class="datos"><input name="name" type="text" size="27" value="'.$name.'" /></td>
		</tr>
		<tr>
			<td class="datos2">'.__('View incidents').'</td>
			<td class="datos2"><input name="incident_view" type="checkbox" class="chk" value="1" '.(($incident_view == 1) ? 'checked' : '').' /></td>
		</tr>
		<tr>
			<td class="datos">'.__('Edit incidents').'</td>
			<td class="datos"><input name="incident_edit" type="checkbox" class="chk" value="1" '.(($incident_edit == 1) ? 'checked' : '').' /></td>
		</tr>
		<tr>
			<td class="datos2">'.__('Manage incidents').'</td>
			<td class="datos2"><input name="incident_management" type="checkbox" class="chk" value="1" '.(($incident_management == 1) ? 'checked' : '').'/></td>
		</tr>
		<tr>
			<td class="datos">'.__('View agents').'</td>
			<td class="datos"><input name="agent_view" type="checkbox" class="chk" value="1" '.(($agent_view == 1) ? 'checked' : '').' /></td>
		</tr>
		<tr>
			<td class="datos2">'.__('Edit agents').'</td>
			<td class="datos2"><input name="agent_edit" type="checkbox" class="chk" value="1" '.(($agent_edit == 1) ? 'checked' : '').' /></td>
		</tr>
		<tr>
			<td class="datos">'.__('Edit alerts').'</td>
			<td class="datos"><input name="alert_edit" type="checkbox" class="chk" value="1" '.(($alert_edit == 1) ? 'checked' : '').' /></td>
		</tr>
		<tr>
			<td class="datos2">'.__('Manage users').'</td>
			<td class="datos2"><input name="user_management" class="chk" type="checkbox" value="1" '.(($user_management == 1) ? 'checked' : '').' /></td>
		</tr>
		<tr>
			<td class="datos">'.__('Manage Database').'</td>
			<td class="datos"><input name="db_management" class="chk" type="checkbox" value="1" '.(($db_management == 1) ? 'checked' : '').' /></td>
		</tr>
		<tr>
			<td class="datos2">'.__('Manage alerts').'</td>
			<td class="datos2"><input name="alert_management" class="chk" type="checkbox" value="1" '.(($alert_management == 1) ? 'checked' : '').' /></td>
		</tr>
		<tr>
			<td class="datos">'.__('Pandora management').'</td>
			<td class="datos"><input name="pandora_management" class="chk" type="checkbox" value="1" '.(($pandora_management == 1) ? 'checked' : '').' /></td>
		</tr>
		</form>
	</table>';

	echo '<div class="action-buttons" style="width: 400px">';
	
	if (isset ($_GET["new_profile"])) {
		echo '<input name="crtbutton" type="submit" class="sub wand" value="'.__('Create').'" />';
	} elseif (isset ($_GET["edit_profile"])) {
		echo '<input name="uptbutton" type="submit" class="sub upd" value="'.__('Update').'" />';
	}
	echo "</div>";

} else { // View list data
	$result = get_db_all_rows_in_table ("tperfil");
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->class = "databox";
	$table->width = 750;
	$table->size = array (180,40,40,40,40,40,40,40,40,40,40);
	$table->head = array (
				__('Profiles'),
				'IR'.print_help_tip (__('Read Incidents'),true),
				'IW'.print_help_tip (__('Create Incidents'),true),
				'IM'.print_help_tip (__('Manage Incidents'),true),
				'AR'.print_help_tip (__('Read Agent Information'),true),
				'AW'.print_help_tip (__('Manage Agents'),true),
				'LW'.print_help_tip (__('Edit Alerts'),true),
				'UM'.print_help_tip (__('Manage User Rights'),true),
				'DM'.print_help_tip (__('Database Management'),true),
				'LM'.print_help_tip (__('Alerts Management'),true),
				'PM'.print_help_tip (__('Pandora System Management'),true),
				__('Delete')
			);	
	$table->align = array ('',"center","center","center","center","center","center","center","center","center","center","center");
	foreach ($result as $row) {
		$table->data[] = array (
					'<a href="index.php?sec=gperfiles&amp;sec2=godmode/profiles/profile_list&amp;edit_profile='.$row["id_perfil"].'"><b>'.$row["name"].'</b></a>',
		      			(($row["incident_view"] == 1) ? '<img src="images/ok.png" border="0">' : ''),
					(($row["incident_edit"] == 1) ? '<img src="images/ok.png" border="0">' : ''),
					(($row["incident_management"] == 1) ? '<img src="images/ok.png" border="0">' : ''),
					(($row["agent_view"] == 1) ? '<img src="images/ok.png" border="0">' : ''),
					(($row["agent_edit"] == 1) ? '<img src="images/ok.png" border="0">' : ''),
		      			(($row["alert_edit"] == 1) ? '<img src="images/ok.png" border="0">' : ''),
		      			(($row["user_management"] == 1) ? '<img src="images/ok.png" border="0">' : ''),
		      			(($row["db_management"] == 1) ? '<img src="images/ok.png" border="0">' : ''),
		      			(($row["alert_management"] == 1) ? '<img src="images/ok.png" border="0">' : ''),
					(($row["pandora_management"] == 1) ? '<img src="images/ok.png" border="0">' : ''),
		      			'<a href="index.php?sec=gagente&sec2=godmode/profiles/profile_list&delete_profile='.$row["id_perfil"].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;"><img border="0" src="images/cross.png"></a>'
				);
	}
	print_table ($table);
	echo '<div class="action-buttons" style="width: 750px">';
	echo '<form method="POST" action="index.php?sec=gperfiles&sec2=godmode/profiles/profile_list&new_profile=1">'.print_submit_button(__('Create profile'),"crt",false,'class="sub next"',true).'</form>';
	echo "</div>";
}
?>
