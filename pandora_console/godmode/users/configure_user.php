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
require_once ('include/config.php');

check_login ();
$id = get_parameter ('id', $config['id_user']); // ID given as parameter
$user_info = get_user_info ($id);
$id = $user_info['id_user'];

if (! give_acl ($config['id_user'], 0, "UM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access User Management");
	require ("general/noaccess.php");
	return;
}

if ($config['user_can_update_info']) {
	$view_mode = false;
} else {
	$view_mode = true;
}

$new_user = (bool) get_parameter ('new_user');
$create_user = (bool) get_parameter ('create_user');
$add_profile = (bool) get_parameter ('add_profile');
$delete_profile = (bool) get_parameter ('delete_profile');
$update_user = (bool) get_parameter ('update_user');

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
}

if ($create_user) {
	if (! $config['admin_can_add_user']) {
		print_error_message (__('The current authentication scheme doesn\'t support creating users from Pandora FMS'));
		return;
	}
	
	$values = array ();
	$id = (string) get_parameter ('id_user');
	$values['fullname'] = (string) get_parameter ('fullname');
	$values['firstname'] = (string) get_parameter ('firstname');
	$values['lastname'] = (string) get_parameter ('lastname');
	$password_new = (string) get_parameter ('password_new');
	$password_confirm = (string) get_parameter ('password_confirm');
	$values['email'] = (string) get_parameter ('email');
	$values['phone'] = (string) get_parameter ('phone');
	$values['comments'] = (string) get_parameter ('comments');
	$is_admin = (bool) get_parameter ('is_admin', 0);
	
	if ($password_new == '') {
		print_error_message (__('Passwords cannot be empty'));
		$user_info = $values;
		$password_new = '';
		$password_confirm = '';
		$new_user = true;
	} elseif ($password_new != $password_confirm) {
		print_error_message (__('Passwords didn\'t match'));
		$user_info = $values;
		$password_new = '';
		$password_confirm = '';
		$new_user = true;
	} else {
		$result = create_user ($id, $password_new, $values);
		print_result_message ($result,
			__('Successfully created'),
			__('Could not be created'));
		$user_info = get_user_info ($id);
		$password_new = '';
		$password_confirm = '';
	}
	
	$user_info['is_admin'] = $is_admin;
}

if ($update_user) {
	$values = array ();
	$values['fullname'] = (string) get_parameter ('fullname');
	$values['firstname'] = (string) get_parameter ('firstname');
	$values['lastname'] = (string) get_parameter ('lastname');
	$values['email'] = (string) get_parameter ('email');
	$values['phone'] = (string) get_parameter ('phone');
	$values['comments'] = (string) get_parameter ('comments');
	$values['is_admin'] = (bool) get_parameter ('is_admin');
	
	$res1 = update_user ($id, $values);
	
	if ($config['user_can_update_password']) {
		$password_new = (string) get_parameter ('password_new');
		$password_confirm = (string) get_parameter ('password_confirm');
		if ($password_new !== '') {
			if ($password_confirm == $password_new) {
				$res2 = update_user_password ($id, $password_new);
				print_result_message ($res1 || $res2,
					__('User info successfully updated'),
					__('Error updating user info (no change?)'));
			} else {
				print_error_message (__('Passwords does not match'));
			}
		} else {
			print_result_message ($res1,
				__('User info successfully updated'),
				__('Error updating user info (no change?)'));
		}
	} else {
		print_result_message ($res1,
			__('User info successfully updated'),
			__('Error updating user info (no change?)'));
	}
	
	$user_info = $values;
}

if ($add_profile) {
	$id = (string) get_parameter ('id_user');
	$group = (int) get_parameter ('assign_group');
	$profile = (int) get_parameter ('assign_profile');
	
	$return = create_user_profile ($id, $profile, $group);
	print_result_message ($return,
		__('Succesfully created'),
		__('Could not be created'));
}

if ($delete_profile) {
	$id = (string) get_parameter ('id_user');
	$id_up = (int) get_parameter ('id_user_profile');
	
	$return = delete_user_profile ($id, $id_up);
	print_result_message ($return,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

echo "<h2>".__('Pandora users')." &raquo; ".__('User detail editor')."</h2>";

$table->width = '50%';
$table->data = array ();
$table->colspan = array ();
$table->size = array ();
$table->size[0] = '35%';
$table->size[1] = '65%';
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align: top';

$table->data[0][0] = __('User ID');
$table->data[0][1] = print_input_text_extended ('id_user', $id, '', '', 20, 60,
	$view_mode, '', '', true);

$table->data[1][0] = __('Full (display) name');
$table->data[1][1] = print_input_text_extended ('fullname', $user_info['fullname'],
	'', '', 30, 255, $view_mode, '', '', true);

$table->data[2][0] = __('First name');
$table->data[2][1] = print_input_text_extended ('firstname', $user_info['firstname'],
	'', '', 30, 255, $view_mode, '', '', true);

$table->data[3][0] = __('Last name');
$table->data[3][1] = print_input_text_extended ('lastname', $user_info['lastname'],
	'', '', 30, 255, $view_mode, '', '', true);

if ($config['user_can_update_password']) {
	$table->data[4][0] = __('Password');
	$table->data[4][1] = print_input_text_extended ('password_new', '', '', '',
		15, 255, $view_mode, '', '', true, true);
	$table->data[5][0] = __('Password confirmation');
	$table->data[5][1] = print_input_text_extended ('password_confirm', '', '',
		'', 15, 255, $view_mode, '', '', true, true);
}

if ($config['admin_can_make_admin']) {
	$table->data[6][0] = __('Global Profile');
	
	$table->data[6][1] = print_radio_button ('is_admin', 1, '', $user_info['is_admin'], true);
	$table->data[6][1] .= __('Administrator');
	$table->data[6][1] .= print_help_tip (__("This user has permissions to manage all. This is admin user and overwrites all permissions given in profiles/groups"), true);
	$table->data[6][1] .= '<br />';
	
	$table->data[6][1] .= print_radio_button ('is_admin', 0, '', $user_info['is_admin'], true);
	$table->data[6][1] .= __('Standard user');
	$table->data[6][1] .= print_help_tip (__("This user has separated permissions to view data in his group agents, create incidents belong to his groups, add notes in another incidents, create personal assignments or reviews and other tasks, on different profiles"), true);
}

$table->data[7][0] = __('E-mail');
$table->data[7][1] = print_input_text_extended ("email", $user_info['email'],
	'', '', 20, 100, $view_mode, '', '', true);

$table->data[8][0] = __('Phone number');
$table->data[8][1] = print_input_text_extended ("phone", $user_info['phone'],
	'', '', 10, 30, $view_mode, '', '', true);

$table->data[9][0] = __('Comments');
$table->data[9][1] = print_textarea ("comments", 5, 55, $user_info['comments'],
	($view_mode ? 'readonly="readonly"' : ''), true);

echo '<form method="post">';

print_table ($table);

echo '<div style="width: '.$table->width.'" class="action-buttons">';
if ($new_user) {
	if ($config['admin_can_add_user']){
		print_input_hidden ('create_user', 1);
		print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
	}
} else {
	if ($config['user_can_update_info']) {
		print_input_hidden ('update_user', 1);
		print_submit_button (__('Update'), 'uptbutton', false, 'class="sub upd"');
	}
}
echo '</div>';
echo '</form>';
echo '<br />';

/* Don't show anything else if we're creating an user */
if (empty ($id) || $new_user)
	return;

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
$table->head[2] = '';
$table->align[2] = 'center';

$result = get_db_all_rows_field_filter ("tusuario_perfil", "id_usuario", $id);
if ($result === false) {
	$result = array ();
}

foreach ($result as $profile) {
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=gperfiles&sec2=godmode/profiles/profile_list&id='.$profile['id_perfil'].'">'.get_profile_name ($profile['id_perfil']).'</a>';
	$data[1] = '<a href="index.php?sec=gagente&sec2=godmode/groups/group_list&id_group='.$profile['id_grupo'].'">'.get_group_name ($profile['id_grupo']).'</a>';
	$data[2] = '<form method="post" onsubmit="if (!confirm (\''.__('Are you sure?').'\')) return false">';
	$data[2] .= print_input_hidden ('delete_profile', 1, true);
	$data[2] .= print_input_hidden ('id_user_profile', $profile['id_up'], true);
	$data[2] .= print_input_hidden ('id_user', $id, true);
	$data[2] .= print_input_image ('del', 'images/cross.png', 1, '', true);
	$data[2] .= '</form>';
	
	array_push ($table->data, $data);
}

$data = array ();
$data[0] = '<form method="post">';
$data[0] .= print_select (get_profiles (), 'assign_profile', 0, '', __('None'),
	0, true, false, false);
$data[1] = print_select (get_user_groups ($config['id_user'], 'UM'),
	'assign_group', 0, '', __('None'), 0, true, false, false);
$data[2] = print_input_image ('add', 'images/add.png', 1, '', true);
$data[2] .= print_input_hidden ('id_user', $id, true);
$data[2] .= print_input_hidden ('add_profile', 1, true);
$data[2] .= '</form>';

array_push ($table->data, $data);

print_table ($table);
echo '</form>';

unset ($table);
?>
