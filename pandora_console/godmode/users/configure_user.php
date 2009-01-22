<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require_once ("include/config.php");

check_login ();

$id = get_parameter_get ("id", $config["id_user"]); // ID given as parameter
$user_info = get_user_info ($id);
$id = $user_info["id_user"];

if (! give_acl ($config['id_user'], 0, "UM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access User Management");
	require ("general/noaccess.php");
	exit;
}

if ($config["user_can_update_info"]) {
	$view_mode = false;
} else {
	$view_mode = true;
}

if (isset ($_GET["create"]) && $config["admin_can_add_user"]) {
	$user_info = array ();
	$id = '';
	$user_info["fullname"] = '';
	$user_info["firstname"] = '';
	$user_info["lastname"] = '';
	$user_info["email"] = '';
	$user_info["phone"] = '';
	$user_info["comments"] = '';
} elseif (isset ($_GET["create"])) {
	print_error_message (false, '', __('The current authentication scheme doesn\'t support creating users from Pandora FMS'));
} elseif (isset ($_GET["user_mod"])) {
	$mod = get_parameter_get ("user_mod", 0); //0 is no user info modify (can modify passwords and admin status), 1 is modify, 2 is create
	
	$upd_info = array ();
	$upd_info["fullname"] = get_parameter_post ("fullname", $user_info["fullname"]);
	$upd_info["firstname"] = get_parameter_post ("firstname", $user_info["firstname"]);
	$upd_info["lastname"] = get_parameter_post ("lastname", $user_info["lastname"]);
	$password_old = get_parameter_post ("password_old", "-");
	$password_new = get_parameter_post ("password_new", "-");
	$password_confirm = get_parameter_post ("password_confirm", "-");
	$upd_info["email"] = get_parameter_post ("email", $user_info["email"]);
	$upd_info["phone"] = get_parameter_post ("phone", $user_info["phone"]);
	$upd_info["comments"] = get_parameter_post ("comments", $user_info["comments"]);
	$is_admin = get_parameter_post ("is_admin", $user_info["is_admin"]);
	$group = get_parameter_post ("assign_group", 0);
	$profile = get_parameter_post ("assign_profile", 0);
	
	
	if ($config["admin_can_add_user"] && $mod == 2) {
		if ($password_new !== $password_confirm) {
			print_error_message (false, '', __('Passwords didn\t match'));
			$user_info = $upd_info; //Fill in the blanks again
		} else {
			$id = get_parameter_post ("id_user");
			$return = create_user ($id, $password_new, $upd_info);
			print_error_message ($return, __('User successfully created'), __('Error creating user'));
			$user_info = get_user_info ($id);
			$id = $user_info["id_user"];
			$_GET["create"] = 1; //Set create mode back on
		}
	} elseif ($config["user_can_update_info"] && mod == 1) {
		$return = process_user_info ($id, $upd_info);
		print_error_message ($return, __('User info successfully updated'), __('Error updating user info'));
		$user_info = get_user_info ($id);
		$id = $user_info["id_user"];
	}
	
	//If User can update password and the new password is not the same as the old one, it's not the default and it's not empty and the new password is the same as the confirmed one
	if ($config["user_can_update_password"] && $password_old !== $password_new && $password_new !== "-" && !empty ($password_new) && $password_confirm == $password_new) {
		$return = process_user_password ($id, $password_old, $password_new);
		print_error_message ($return, __('Password successfully updated'), __('Error updating passwords').": ".$config["auth_error"]);
	} elseif ($password_new !== "-") {
		print_error_message (false, '', __('Passwords didn\'t match or other problem encountered while updating passwords'));
	}
	
	if ($is_admin != $user_info["is_admin"]) {
		$return = process_user_isadmin ($id, $is_admin);
		print_error_message ($return, __('User admin status succesfully update'), __('Error updating admin status'));
	}
	
	if ($group != 0 && $profile != 0) {
		$return = create_user_profile ($id, $profile, $group);
		print_error_message ($return, __('User profile succesfully created'), __('Error creating user profile'));
	}
} elseif (isset ($_GET["profile_mod"])) {
	$id_up = (int) get_parameter_post ("delete_profile", 0);
	$return = delete_user_profile ($id, $id_up);
	print_error_message ($return, __('Profile successfully deleted'), __('Error deleting profile'));
}

echo "<h2>".__('Pandora users')." &gt; ".__('User detail editor')."</h2>";

if (!empty ($id)) {
	echo '<form name="user_mod" method="post" action="index.php?sec=usuarios&sec2=godmode/users/configure_user&id='.$id.'&user_mod=1">';
} else {
	echo '<form name="user_create" method="post" action="index.php?sec=usuarios&sec2=godmode/users/configure_user&user_mod=2">';
}

echo '<table cellpadding="4" cellspacing="4" class="databox_color" width="600px">';

echo '<tr><td class="datos">'.__('User ID').'</td>';
echo '<td class="datos">';
print_input_text_extended ("id_user", $id, '', '', '', '', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos2">'.__('Full (display) name').'</td><td class="datos2">';
print_input_text_extended ("fullname", $user_info["fullname"], '', '', '', '', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos">'.__('First name').'</td><td class="datos">';
print_input_text_extended ("firstname", $user_info["firstname"], '', '', '', '', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos2">'.__('Last name').'</td><td class="datos2">';
print_input_text_extended ("lastname", $user_info["lastname"], '', '', '', '', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos">'.__('Password').'</td><td class="datos">';
if ($config["user_can_update_password"]) {
	if (!isset ($_GET["create"])) {
		print_input_text_extended ("password_old", "", '', '', '', '', $view_mode, '', 'class="input"', false, true);
	}
	echo '</td></tr><tr><td class="datos">'.__('New Password').'</td><td class="datos">';
	print_input_text_extended ("password_new", "", '', '', '', '', $view_mode, '', 'class="input"', false, true);
	echo '</td></tr><tr><td class="datos">'.__('Password confirmation').'</td><td class="datos">';
	print_input_text_extended ("password_conf", "", '', '', '', '', $view_mode, '', 'class="input"', false, true);
} else {
	echo '<i>'.__('You can not change passwords from Pandora FMS under the current authentication scheme').'</i>';
}

echo '</td></tr><tr><td class="datos2">'.__('Global Profile').'</td><td class="datos2">';
if ($config["admin_can_make_admin"]) {
	echo __('Administrator');
	print_radio_button ('is_admin', '1', '', $user_info["is_admin"]);
	print_help_tip (__("This user has permissions to manage all. This is admin user and overwrites all permissions given in profiles/groups"));
	print __('Standard user');
	print_radio_button ('is_admin', '0', '', $user_info["is_admin"]);
	print_help_tip (__("This user has separated permissions to view data in his group agents, create incidents belong to his groups, add notes in another incidents, create personal assignments or reviews and other tasks, on different profiles"));
} else {
	echo '<i>'.__('You can not change admin status from Pandora FMS under the current authentication scheme').'</i>';
}

echo '</td></tr><tr><td class="datos">'.__('E-mail').'</td><td class="datos">';
print_input_text_extended ("email", $user_info["email"], '', '', '', '', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos2">'.__('Phone number').'</td><td class="datos2">';
print_input_text_extended ("phone", $user_info["phone"], '', '', '', '', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos">'.__('Comments').'</td><td class="datos">';
print_textarea ("comments", 4, 55, $user_info["comments"], ($view_mode ? 'readonly' : ''));

echo '<tr><td class="datos2">'.__('Group(s) available').'</td><td class="datos2">';

$groups = get_user_groups ($config["id_user"], "UM");
print_select ($groups, "assign_group", 0, '', __('None'), 0, false, false, false, 'w155');

echo '</td></tr><tr><td class="datos">'.__('Profiles').'</td><td class="datos">';
$profiles = get_profiles ();
print_select ($profiles, "assign_profile", 0, '', __('None'), 0, false, false, false, 'w155');
echo '</td></tr></table>';

echo '<div style="width:600px; text-align:right;">';
print_submit_button (__('Update'), 'uptbutton', false, 'class="sub upd"');
echo '</div></form><br />';


echo '<h3>'.__('Profiles/Groups assigned to this user').'</h3>';

$table->width = 600;
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";

$table->data = array ();
$table->head = array ();
$table->align = array ();

$table->head[0] = __('Profile name');
$table->head[1] = __('Group name');
$table->head[2] = '';

$table->align[0] = 'center';
$table->align[1] = 'center';
$table->align[2] = 'center';


$result = get_db_all_rows_field_filter ("tusuario_perfil", "id_usuario", $user_info["id_user"]);
if ($result === false) {
	$result = array ();
}

foreach ($result as $profile) {
	$data[0] = '<b><a href="index.php?sec=gperfiles&sec2=godmode/profiles/profile_list&id='.$profile["id_perfil"].'">'.get_profile_name ($profile["id_perfil"]).'</a></b>';
	$data[1] = '<b><a href="index.php?sec=gagente&sec2=godmode/groups/group_list&id_group='.$profile["id_grupo"].'">'.get_group_name ($profile["id_grupo"]).'</a></b>';
	$data[2] = print_input_image ("delete_profile", "images/delete.png", $profile["id_up"], 'border:0px;', true);
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	echo '<form name="profile_mod" method="post" action="index.php?sec=usuarios&sec2=godmode/users/configure_user&id='.$id.'&profile_mod=1">';
	print_table ($table);
	echo '</form>';
} else {
	echo '<div class="nf">'.__('This user doesn\'t have any assigned profile/group').'</div>'; 
}
unset ($table);
?>