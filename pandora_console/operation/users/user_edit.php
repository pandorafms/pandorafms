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


// Load global vars
require_once ("include/config.php");

check_login ();

$id = get_parameter_get ("id", $config["id_user"]); // ID given as parameter
$user_info = get_user_info ($id);
$id = $user_info["id_user"]; //This is done in case there are problems with uppercase/lowercase (MySQL auth has that problem)


if (!give_acl ($config["id_user"], get_user_groups ($id), "UM")){
	audit_db ($config["id_user"], $config["remote_addr"], "ACL Violation","Trying to view a user without privileges");
	require ("general/noaccess.php");
	exit;
}

//If current user is editing himself or if the user has UM (User Management) rights on any groups the user is part of AND the authorization scheme allows for users/admins to update info
if (($config["id_user"] == $id || give_acl ($config["id_user"], get_user_groups ($id), "UM")) && $config["user_can_update_info"]) {
	$view_mode = false;
} else {
	$view_mode = true;
}

if (isset ($_GET["modified"]) && !$view_mode) {
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
	
	//If User can update password and the new password is not the same as the old one, it's not the default and it's not empty and the new password is the same as the confirmed one
	if ($config["user_can_update_password"] && $password_old !== $password_new && $password_new !== "-" && !empty ($password_new) && $password_confirm == $password_new) {
		$return = process_user_password ($id, $pass);
		print_result_message ($return,
			__('Password successfully updated'),
			__('Error updating passwords: %s', $config['auth_error']));
	} elseif ($password_new !== "-") {
		print_error_message (__('Passwords didn\'t match or other problem encountered while updating passwords'));
	}
	
	$return = update_user ($id, $upd_info);
	print_result_message ($return,
		__('User info successfully updated'),
		__('Error updating user info'));
	$user_info = $upd_info;
}

echo "<h2>".__('Pandora users')." &raquo; ".__('User detail editor')."</h2>";

echo '<form name="user_mod" method="post" action="index.php?sec=usuarios&amp;sec2=operation/users/user_edit&amp;modified=1&amp;id='.$id.'">';

echo '<table cellpadding="4" cellspacing="4" class="databox_color" width="600">';

echo '<tr><td class="datos">'.__('User ID').'</td>';
echo '<td class="datos">';
print_input_text_extended ("id_user", $id, '', '', '', '', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos2">'.__('Full (display) name').'</td><td class="datos2">';
print_input_text_extended ("fullname", $user_info["fullname"], '', '', '', '', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos">'.__('First name').'</td><td class="datos">';
print_input_text_extended ("firstname", $user_info["firstname"], '', '', '', '', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos2">'.__('Last name').'</td><td class="datos2">';
print_input_text_extended ("lastname", $user_info["lastname"], '', '', '', '', $view_mode, '', 'class="input"');

if ($view_mode === false) {
	echo '</td></tr><tr><td class="datos">'.__('Current password').'</td><td class="datos">';
	if ($config["user_can_update_password"]) {
		print_input_text_extended ("password_old", "-", '', '', '', '', $view_mode, '', 'class="input"', false, true);
		echo '</td></tr><tr><td class="datos">'.__('New Password').'</td><td class="datos">';
		print_input_text_extended ("password_new", "-", '', '', '', '', $view_mode, '', 'class="input"', false, true);
		echo '</td></tr><tr><td class="datos">'.__('Password confirmation').'</td><td class="datos">';
		print_input_text_extended ("password_conf", "-", '', '', '', '', $view_mode, '', 'class="input"', false, true);
	} else {
		echo '<i>'.__('You can not change your password from Pandora FMS under the current authentication scheme').'</i>';
	}
}

echo '</td></tr><tr><td class="datos2">'.__('E-mail').'</td><td class="datos2">';
print_input_text_extended ("email", $user_info["email"], '', '', '', '', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos">'.__('Phone number').'</td><td class="datos">';
print_input_text_extended ("phone", $user_info["phone"], '', '', '', '', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos2">'.__('Comments').'</td><td class="datos2">';
print_textarea ("comments", 4, 55, $user_info["comments"], ($view_mode ? 'readonly="readonly"' : ''));
 
echo '</td></tr></table>';

echo '<div style="width:600px; text-align:right;">';
if (!$config["user_can_update_info"]) {
	echo '<i>'.__('You can not change your user info from Pandora FMS under the current authentication scheme').'</i>';
} else {
	print_submit_button (__('Update'), 'uptbutton', $view_mode, 'class="sub upd"');
}
echo '</div></form><br />';


echo '<h3>'.__('Profiles/Groups assigned to this user').'</h3>';

$table->width = 500;
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";

$table->data = array ();

$result = get_db_all_rows_field_filter ("tusuario_perfil", "id_usuario", $id);
if ($result === false) {
	$result = array ();
}

foreach ($result as $profile) {
	$data[0] = '<b>'.get_profile_name ($profile["id_perfil"]).'</b>';
	$data[1] = '<b>'.get_group_name ($profile["id_grupo"]).'</b>';
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	print_table ($table);
} else {
	echo '<div class="nf">'.__('This user doesn\'t have any assigned profile/group').'</div>'; 
}
?>
