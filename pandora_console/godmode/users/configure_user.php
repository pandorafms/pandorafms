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

include_once($config['homedir'] . "/include/functions_profile.php");
include_once($config['homedir'] . '/include/functions_users.php');
include_once ($config['homedir'] . '/include/functions_groups.php');

$isFunctionSkins = enterprise_include_once ('include/functions_skins.php');

//Add the columns for the enterprise Pandora edition.
$enterprise_include = false;
if (ENTERPRISE_NOT_HOOK !== enterprise_include('include/functions_policies.php')) {
	$enterprise_include = true;
}

// This defines the working user. Beware with this, old code get confusses
// and operates with current logged user (dangerous).

$id = get_parameter ('id', get_parameter ('id_user', '')); // ID given as parameter

$user_info = get_user_info ($id);

if (! check_acl ($config['id_user'], 0, "UM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access User Management");
	require ("general/noaccess.php");
	
	return;
}

if (!check_refererer()) {
	require ("general/noaccess.php");
	
	return;	
}

$tab = get_parameter('tab', 'user');

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
ui_print_page_header (__('User detail editor'), "images/god3.png", false, "", true, $buttons);


if ($config['user_can_update_info']) {
	$view_mode = false;
} else {
	$view_mode = true;
}

$new_user = (bool) get_parameter ('new_user');
$create_user = (bool) get_parameter ('create_user');
$add_profile = (bool) get_parameter ('add_profile');
$add_profile_policy = (bool) get_parameter ('add_profile_policy');
$delete_profile = (bool) get_parameter ('delete_profile');
$update_user = (bool) get_parameter ('update_user');
$status = get_parameter ('status', -1);

// Reset status var if current action is not update_user
if ($new_user || $create_user || $add_profile || $add_profile_policy || $delete_profile || $update_user){
	$status = -1;
}

if ($new_user && $config['admin_can_add_user']) {
	$user_info = array ();
	$id = '';
	$user_info['fullname'] = '';
	$user_info['firstname'] = '';
	$user_info['lastname'] = '';
	$user_info['email'] = '';
	$user_info['phone'] = '';
	$user_info['comments'] = '';
	$user_info['is_admin'] = 0;
	$user_info['language'] = 'default';
	if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
		$user_info['id_skin'] = '';
	}
	//This attributes are inherited from global configuration
	$user_info['block_size'] = $config["block_size"];
	$user_info['flash_chart'] = $config["flash_charts"];
}

if ($create_user) {
	if (! $config['admin_can_add_user']) {
		ui_print_error_message (__('The current authentication scheme doesn\'t support creating users from Pandora FMS'));
		return;
	}
	
	$values = array ();
	$id = (string) get_parameter ('id_user');
	$values['fullname'] = (string) get_parameter ('fullname');
	$values['firstname'] = (string) get_parameter ('firstname');
	$values['lastname'] = (string) get_parameter ('lastname');
	$password_new = (string) get_parameter ('password_new', '');
	$password_confirm = (string) get_parameter ('password_confirm', '');
	$values['email'] = (string) get_parameter ('email');
	$values['phone'] = (string) get_parameter ('phone');
	$values['comments'] = (string) get_parameter ('comments');
	$values['is_admin'] = (int) get_parameter ('is_admin', 0);
	$values['language'] = get_parameter ('language', 'default');
	if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
		$values['id_skin'] = (int) get_parameter ('skin', 0);
	}
	$values['block_size'] = (int) get_parameter ('block_size', $config["block_size"]);
	$values['flash_chart'] = (int) get_parameter ('flash_charts', $config["flash_charts"]);
	
	if ($id == '') {
		ui_print_error_message (__('User ID cannot be empty'));
		$user_info = $values;
		$password_new = '';
		$password_confirm = '';
		$new_user = true;
	}
	elseif ($password_new == '') {
		ui_print_error_message (__('Passwords cannot be empty'));
		$user_info = $values;
		$password_new = '';
		$password_confirm = '';
		$new_user = true;
	}
	elseif ($password_new != $password_confirm) {
		ui_print_error_message (__('Passwords didn\'t match'));
		$user_info = $values;
		$password_new = '';
		$password_confirm = '';
		$new_user = true;
	}
	else {
		$info = 'FullName: ' . $values['fullname'] . ' Firstname: ' . $values['firstname'] .
			' Lastname: ' . $values['lastname'] . ' Email: ' . $values['email'] . 
			' Phone: ' . $values['phone'] . ' Comments: ' . $values['comments'] .
			' Is_admin: ' . $values['is_admin'] .
			' Language: ' . $values['language'] . 
			' Block size: ' . $values['block_size'] . ' Flash Chats: ' . $values['flash_chart'];
		
		if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
			$info .= ' Skin: ' . $values['id_skin'];
		}
		
		switch ($config['dbtype']){
			case "mysql":
			case "postgresql":
				$result = create_user($id, $password_new, $values);
				break;
			case "oracle":
				$result = db_process_sql('/INSERT INTO tusuario (fullname, firstname, lastname, email, phone, comments, is_admin, language, id_skin, block_size, flash_chart, id_user, password, last_connect, registered) VALUES (\'' . $values['fullname'] . '\',\'\',\'\',\'\',\'\',\'\',' . $values['is_admin'] . ',\'' . $values['language'] .'\',' . $values['id_skin'] . ',' . $values['block_size'] . ',' . $values['flash_chart'] . ',\'' . $id . '\',\'' . $password_new . '\',0,\'' . get_system_time () . '\')');		
				break;		
		}
			

		db_pandora_audit("User management",
			"Created user ".io_safe_input($id), false, false, $info);

		ui_print_result_message ($result,
			__('Successfully created'),
			__('Could not be created'));
			
		$password_new = '';
		$password_confirm = '';
		
		if($result) {
			$user_info = get_user_info ($id);
			$new_user = false;
		}
		else {
			$user_info = $values;
			$new_user = true;
		}
	}
	
}

if ($update_user) {
	$values = array ();
	$values['fullname'] = (string) get_parameter ('fullname');
	$values['firstname'] = (string) get_parameter ('firstname');
	$values['lastname'] = (string) get_parameter ('lastname');
	$values['email'] = (string) get_parameter ('email');
	$values['phone'] = (string) get_parameter ('phone');
	$values['comments'] = (string) get_parameter ('comments');
	$values['is_admin'] = get_parameter ('is_admin', 0 );
	$values['language'] = (string) get_parameter ('language');
	if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
		$values['id_skin'] = get_parameter ('skin', 0);
	}
	$values['block_size'] = get_parameter ('block_size', $config["block_size"]);
	$values['flash_chart'] = get_parameter ('flash_charts', $config["flash_charts"]);

	$res1 = update_user ($id, $values);
	
	if ($config['user_can_update_password']) {
		$password_new = (string) get_parameter ('password_new', '');
		$password_confirm = (string) get_parameter ('password_confirm', '');
		if ($password_new != '') {
			if ($password_confirm == $password_new) {
				$res2 = update_user_password ($id, $password_new);
				ui_print_result_message ($res1 || $res2,
					__('User info successfully updated'),
					__('Error updating user info (no change?)'));
			}
			else {
				ui_print_error_message (__('Passwords does not match'));
			}
		}
		else {
			$info = 'FullName: ' . $values['fullname'] . ' Firstname: ' . $values['firstname'] .
				' Lastname: ' . $values['lastname'] . ' Email: ' . $values['email'] . 
				' Phone: ' . $values['phone'] . ' Comments: ' . $values['comments'] .
				' Is_admin: ' . $values['is_admin'] .
				' Language: ' . $values['language'] . 
				' Block size: ' . $values['block_size'] . ' Flash Chats: ' . $values['flash_chart'];
			
			if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
				$info .= ' Skin: ' . $values['id_skin'];
			}
			
			db_pandora_audit("User management", "Updated user ".io_safe_input($id),
				false, false, $info);
		
			ui_print_result_message ($res1,
				__('User info successfully updated'),
				__('Error updating user info (no change?)'));
		}
	}
	else {
		ui_print_result_message ($res1,
			__('User info successfully updated'),
			__('Error updating user info (no change?)'));
	}
	
	$user_info = $values;
}

if ($status != -1){
	ui_print_result_message ($status,
		__('User info successfully updated'),
		__('Error updating user info (no change?)'));	
}

if ($add_profile) {
	$id2 = (string) get_parameter ('id');
	$group2 = (int) get_parameter ('assign_group');
	$profile2 = (int) get_parameter ('assign_profile');
	db_pandora_audit("User management",
		"Added profile for user ".io_safe_input($id2), false, false, 'Profile: ' . $profile2 . ' Group: ' . $group2);
	$return = profile_create_user_profile($id2, $profile2, $group2);
	
	ui_print_result_message ($return,
		__('Profile added successfully'),
		__('Profile cannot be added'));
}

if ($add_profile_policy && $enterprise_include) {
	$id2 = (string) get_parameter ('id');
	$profile2 = (int) get_parameter ('assign_profile');
	$id_policy = (int) get_parameter ('policy');

	if($id_policy != 0) {
		$return = policies_create_user_policy_profile($id2, $profile2, $id_policy);
	}
	else {
		$return = false;
	}
	
	if($return === false) {
		db_pandora_audit("User management",
			"Added extra policy profile for user ".io_safe_input($id2), false, false, ' Policy: ' . $id_policy);
	}
	else {
		db_pandora_audit("User management",
			"Problem adding extra policy profile for user ".io_safe_input($id2), false, false, ' Policy: ' . $id_policy);
	}
		
	ui_print_result_message ($return,
		__('Extra policy profile added successfully'),
		__('Extra policy profile cannot be added'));
}

if ($delete_profile) {
	$id2 = (string) get_parameter ('id_user');
	$id_up = (int) get_parameter ('id_user_profile');
	
	$perfilUser = db_get_row('tusuario_perfil', 'id_up', $id_up);
	$id_perfil = $perfilUser['id_perfil'];
	$perfil = db_get_row('tperfil', 'id_perfil', $id_perfil);
		
	db_pandora_audit("User management",
		"Deleted profile for user ".io_safe_input($id2), false, false, 'The profile with id ' . $id_perfil . ' in the group ' . $perfilUser['id_grupo']);

	$return = profile_delete_user_profile ($id2, $id_up);
	ui_print_result_message ($return,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

$table->width = '98%';
$table->data = array ();
$table->colspan = array ();
$table->size = array ();
$table->size[0] = '35%';
$table->size[1] = '65%';
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align: top';

$table->data[0][0] = __('User ID');
$table->data[0][1] = html_print_input_text_extended ('id_user', $id, '', '', 20, 60,
	!$new_user || $view_mode, '', '', true);

$table->data[1][0] = __('Full (display) name');
$table->data[1][1] = html_print_input_text_extended ('fullname', $user_info['fullname'],
	'', '', 30, 255, $view_mode, '', '', true);

$table->data[2][0] = __('Language');
$table->data[2][1] = html_print_select_from_sql ('SELECT id_language, name FROM tlanguage',
	'language', $user_info['language'], '', __('Default'), 'default', true);

if ($config['user_can_update_password']) {
	$table->data[4][0] = __('Password');
	$table->data[4][1] = html_print_input_text_extended ('password_new', '', '', '',
		15, 255, $view_mode, '', '', true, true);
	$table->data[5][0] = __('Password confirmation');
	$table->data[5][1] = html_print_input_text_extended ('password_confirm', '', '',
		'', 15, 255, $view_mode, '', '', true, true);
}

$own_info = get_user_info ($config['id_user']);
if ($config['admin_can_make_admin']) {
	$table->data[6][0] = __('Global Profile');
	$table->data[6][1] = '';
	if ($own_info['is_admin'] || $user_info['is_admin']){
		$table->data[6][1] = html_print_radio_button ('is_admin', 1, '', $user_info['is_admin'], true);
		$table->data[6][1] .= __('Administrator');
		$table->data[6][1] .= ui_print_help_tip (__("This user has permissions to manage all. This is admin user and overwrites all permissions given in profiles/groups"), true);
		$table->data[6][1] .= '<br />';
	}
	$table->data[6][1] .= html_print_radio_button ('is_admin', 0, '', $user_info['is_admin'], true);
	$table->data[6][1] .= __('Standard User');
	$table->data[6][1] .= ui_print_help_tip (__("This user has separated permissions to view data in his group agents, create incidents belong to his groups, add notes in another incidents, create personal assignments or reviews and other tasks, on different profiles"), true);
}

$table->data[7][0] = __('E-mail');
$table->data[7][1] = html_print_input_text_extended ("email", $user_info['email'],
	'', '', 20, 100, $view_mode, '', '', true);

$table->data[8][0] = __('Phone number');
$table->data[8][1] = html_print_input_text_extended ("phone", $user_info['phone'],
	'', '', 10, 30, $view_mode, '', '', true);

$table->data[9][0] = __('Comments');
$table->data[9][1] = html_print_textarea ("comments", 2, 65, $user_info['comments'],
	($view_mode ? 'readonly="readonly"' : ''), true);

// If we want to create a new user, skins displayed are the skins of the creator's group. If we want to update, skins displayed are the skins of the modified user.  
$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$display_all_group = true;
else
	$display_all_group = false;

if ($new_user){		
	$usr_groups = (users_get_groups($config['id_user'], 'AR', $display_all_group));
	$id_usr = $config['id_user'];
}else{
	$usr_groups = (users_get_groups($id, 'AR', $display_all_group));
	$id_usr = $id;
}

// User only can change skins if has more than one group 
if (count($usr_groups) > 1){
	if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
		$table->data[10][0] = __('Skin');
		$table->data[10][1] = skins_print_select($id_usr,'skin', $user_info['id_skin'], '', __('None'), 0, true);
	}
}

$table->data[11][0] = __('Flash charts');
$values = array(-1 => __('Use global conf'), 1 => __('Yes'), 0 => __('No'));
$table->data[11][1] = html_print_select($values, 'flash_charts', $user_info["flash_chart"], '', '', -1, true, false, false); 
$table->data[12][0] = __('Block size for pagination');
$table->data[12][1] = html_print_input_text ('block_size', $user_info["block_size"], '', 5, 5, true);

if($id == $config['id_user']) {
	$table->data[12][1] .= html_print_input_hidden('quick_language_change', 1, true);
}

echo '<form method="post" autocomplete="off">';

html_print_table ($table);

echo '<div style="width: '.$table->width.'" class="action-buttons">';
if ($new_user) {
	if ($config['admin_can_add_user']){
		html_print_input_hidden ('create_user', 1);
		html_print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
	}
} else {
	if ($config['user_can_update_info']) {
		html_print_input_hidden ('update_user', 1);
		html_print_submit_button (__('Update'), 'uptbutton', false, 'class="sub upd"');
	}
}
echo '</div>';
echo '</form>';
echo '<br />';

/* Don't show anything else if we're creating an user */
if (empty ($id) || $new_user)
	return;

echo '<h4>'.__('Profiles/Groups assigned to this user').'</h4>';

$table->width = '98%';
$table->data = array ();
$table->head = array ();
$table->align = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[1] = 'font-weight: bold';
$table->head[0] = __('Profile name');
$table->head[1] = __('Group');
$table->head[2] = __('Action');
$table->align[2] = 'center';

/*
if ($enterprise_include) {
	add_enterprise_column_user_profile_form($table);
}
*/

$result = db_get_all_rows_field_filter ("tusuario_perfil", "id_usuario", $id);
if ($result === false) {
	$result = array ();
}

foreach ($result as $profile) {
	if($profile["id_grupo"] == -1) {
		continue;
	}
	
	$data = array ();

	$data[0] = '<a href="index.php?sec=gusaurios&amp;sec2=godmode/users/configure_profile&id='.$profile['id_perfil'].'">'.profile_get_name ($profile['id_perfil']).'</a>';
	$data[1] = ui_print_group_icon($profile["id_grupo"],true).' <a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$profile['id_grupo'].'">' . ui_print_truncate_text(groups_get_name ($profile['id_grupo'], True), 35).'</a>';
	$data[2] = '<form method="post" onsubmit="if (!confirm (\''.__('Are you sure?').'\')) return false">';
	$data[2] .= html_print_input_hidden ('delete_profile', 1, true);
	$data[2] .= html_print_input_hidden ('id_user_profile', $profile['id_up'], true);
	$data[2] .= html_print_input_hidden ('id_user', $id, true);
	$data[2] .= html_print_input_image ('del', 'images/cross.png', 1, '', true);
	$data[2] .= '</form>';
	
	array_push ($table->data, $data);
}

	$data = array ();
	
	$data[0] = '<form method="post">';
	if (check_acl ($config['id_user'], 0, "PM")) {
		$data[0] .= html_print_select (profile_get_profiles (), 'assign_profile', 0, '',
		 	__('None'), 0, true, false, false);
	}
	else {
		$data[0] .= html_print_select (profile_get_profiles (array ('pandora_management' => '<> 1',
			'db_management' => '<> 1')), 'assign_profile', 0, '', __('None'), 0,
			true, false, false);
	}
	
	$data[1] = html_print_select_groups($config['id_user'], "UM",
		$own_info['is_admin'], 'assign_group', -1, '', __('None'), -1, true,
		false, false);
	
	$data[2] = html_print_input_image ('add', 'images/add.png', 1, '', true);
	$data[2] .= html_print_input_hidden ('id', $id, true);
	$data[2] .= html_print_input_hidden ('add_profile', 1, true);
	$data[2] .= '</form>';

array_push ($table->data, $data);

html_print_table ($table);


unset ($table);

/*
if ($enterprise_include) {
	policies_profile_form($id);
}
*/
?>
