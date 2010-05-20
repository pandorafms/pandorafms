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

$id = get_parameter_get ("id", $config["id_user"]); // ID given as parameter
$user_info = get_user_info ($id);
if ($user_info["language"] == ""){
	$user_info["language"] = $config["language"];
}

$id = $user_info["id_user"]; //This is done in case there are problems with uppercase/lowercase (MySQL auth has that problem)

if ((!give_acl ($config["id_user"], get_user_groups ($id), "UM")) AND ($id != $config["id_user"])){
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
	$password_new = get_parameter_post ("password_new", "");
	$password_confirm = get_parameter_post ("password_conf", "");
	$upd_info["email"] = get_parameter_post ("email", $user_info["email"]);
	$upd_info["phone"] = get_parameter_post ("phone", $user_info["phone"]);
	$upd_info["comments"] = get_parameter_post ("comments", $user_info["comments"]);
	$upd_info["language"] = get_parameter_post ("language", $user_info["language"]);
	
	if ( !empty ($password_new)) {
		if ($config["user_can_update_password"] && $password_confirm == $password_new) {
			$return = update_user_password ($id, $password_new);
			print_result_message ($return,
				__('Password successfully updated'),
				__('Error updating passwords: %s', $config['auth_error']));
		} elseif ($password_new !== "NON-INIT") {
			print_error_message (__('Passwords didn\'t match or other problem encountered while updating passwords'));
		}
	}

	// No need to display "error" here, because when no update is needed (no changes in data) 
	// SQL function returns	0 (FALSE), but is not an error, just no change. Previous error
	// message could be confussing to the user.

	$return = update_user ($id, $upd_info);
	if ($return > 0) {
		print_result_message ($return,
			__('User info successfully updated'),
			__('Error updating user info'));
	}

	$user_info = $upd_info;
}

// Header
print_page_header (__('User detail editor'), "images/group.png", false, "", false, "");

echo '<form name="user_mod" method="post" action="index.php?sec=usuarios&amp;sec2=operation/users/user_edit&amp;modified=1&amp;id='.$id.'">';

echo '<table cellpadding="4" cellspacing="4" class="databox" width="90%">';

echo '<tr><td class="datos">'.__('User ID').'</td>';
echo '<td class="datos">';
echo "<b>$id</b>";
echo "</td>";

// Show "Picture" (in future versions, why not, allow users to upload it's own avatar here.
echo "<td rowspan=4>";
if (is_user_admin ($id)) {
	echo "<img src='images/people_1.png'>";
} 
else {
	echo "<img src='images/people_2.png'>";
}

echo '</td></tr><tr><td class="datos2">'.__('Full (display) name').'</td><td class="datos2">';
print_input_text_extended ("fullname", $user_info["fullname"], '', '', 35, 100, $view_mode, '', 'class="input"');

// Not used anymore. In 3.0 database schema continues storing it, but will be removed in the future, or we will 'reuse'
// the database fields for anything more useful.

/*
echo '</td></tr><tr><td class="datos">'.__('First name').'</td><td class="datos">';
print_input_text_extended ("firstname", $user_info["firstname"], '', '', 25, 100, $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos2">'.__('Last name').'</td><td class="datos2">';
print_input_text_extended ("lastname", $user_info["lastname"], '', '', 25, 100, $view_mode, '', 'class="input"');

*/
if ($view_mode === false) {
	if ($config["user_can_update_password"]) {
		echo '</td></tr><tr><td class="datos">'.__('New Password').'</td><td class="datos">';
		print_input_text_extended ("password_new", "", '', '', '15', '25', $view_mode, '', 'class="input"', false, true);
		echo '</td></tr><tr><td class="datos">'.__('Password confirmation').'</td><td class="datos">';
		print_input_text_extended ("password_conf", "", '', '', '15', '25', $view_mode, '', 'class="input"', false, true);
	} else {
		echo '<i>'.__('You can not change your password from Pandora FMS under the current authentication scheme').'</i>';
	}
}

echo '</td></tr><tr><td class="datos2">'.__('E-mail').'</td><td class="datos2">';
print_input_text_extended ("email", $user_info["email"], '', '', '40', '100', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos">'.__('Phone number').'</td><td class="datos">';
print_input_text_extended ("phone", $user_info["phone"], '', '', '10', '30', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos">'.__('Language').'</td><td class="datos2">';
echo print_select_from_sql ('SELECT id_language, name FROM tlanguage',
	'language', $user_info["language"], '', '', '', true);

echo '</td></tr><tr><td class="datos2">'.__('Comments').'</td><td class="datos">';
print_textarea ("comments", 2, 60, $user_info["comments"], ($view_mode ? 'readonly="readonly"' : ''));
 
echo '</td></tr></table>';

echo '<div style="width:90%; text-align:right;">';
if (!$config["user_can_update_info"]) {
	echo '<i>'.__('You can not change your user info from Pandora FMS under the current authentication scheme').'</i>';
} else {
	print_submit_button (__('Update'), 'uptbutton', $view_mode, 'class="sub upd"');
}
echo '</div></form>';

echo '<h3>'.__('Profiles/Groups assigned to this user').'</h3>';

$table->width = '50%';
$table->data = array ();
$table->head = array ();
$table->align = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[1] = 'font-weight: bold';
$table->head[0] = __('Profile name');
$table->head[1] = __('Group name');

$table->data = array ();

$result = get_db_all_rows_field_filter ("tusuario_perfil", "id_usuario", $id);
if ($result === false) {
	$result = array ();
}

foreach ($result as $profile) {
	$data[0] = '<b>'.get_profile_name ($profile["id_perfil"]).'</b>';
	$data[1] = '<b>'.get_group_name ($profile["id_grupo"], true).'</b>';
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	print_table ($table);
} else {
	echo '<div class="nf">'.__('This user doesn\'t have any assigned profile/group').'</div>'; 
}
?>
