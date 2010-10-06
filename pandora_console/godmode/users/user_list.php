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

if (! give_acl ($config['id_user'], 0, "UM")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access User Management");
	require ("general/noaccess.php");
	exit;
}

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');

$selected = 'border: 1px solid black;';
$selectUserIDUp = '';
$selectUserIDDown = '';
$selectFullnameUp = '';
$selectFullnameDown = '';
$selectLastConnectUp = '';
$selectLastConnectDown = '';
$order = null;

switch ($sortField) {
	case 'id_user':
		switch ($sort) {
			case 'up':
				$selectUserIDUp = $selected;
				$order = array('field' => 'id_user', 'order' => 'ASC');
				break;
			case 'down':
				$selectUserIDDown = $selected;
				$order = array('field' => 'id_user', 'order' => 'DESC');
				break;
		}
		break;
	case 'fullname':
		switch ($sort) {
			case 'up':
				$selectFullnameUp = $selected;
				$order = array('field' => 'fullname', 'order' => 'ASC');
				break;
			case 'down':
				$selectFullnameDown = $selected;
				$order = array('field' => 'fullname', 'order' => 'DESC');
				break;
		}
		break;
	case 'last_connect':
		switch ($sort) {
			case 'up':
				$selectLastConnectUp = $selected;
				$order = array('field' => 'fullname', 'order' => 'ASC');
				break;
			case 'down':
				$selectLastConnectDown = $selected;
				$order = array('field' => 'fullname', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectUserIDUp = '';
		$selectUserIDDown = '';
		$selectFullnameUp = $selected;
		$selectFullnameDown = '';
		$selectLastConnectUp = '';
		$selectLastConnectDown = '';
		$order = array('field' => 'fullname', 'order' => 'ASC');
		break;
}

// Header
print_page_header (__('User management').' &raquo; '.__('Users defined in Pandora'), "images/god3.png", false, "", true);

if (isset ($_GET["user_del"])) { //delete user
	$id_user = get_parameter ("delete_user", 0);
	// Only allow delete user if is not the actual user
	if($id_user != $config['id_user']){
		$result = delete_user ($id_user);

		audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "User management",
			"Deleted user ".safe_input($id_user));

		print_result_message ($result,
			__('Successfully deleted'),
			__('There was a problem deleting the user'));
	}
	else
		print_error_message (__('There was a problem deleting the user'));
	
} elseif (isset ($_GET["profile_del"])) { //delete profile
	$id_profile = (int) get_parameter_post ("delete_profile");
	$result = delete_profile ($id_profile);
	print_result_message ($result, 
		__('Successfully deleted'),
		__('There was a problem deleting the profile'));
}

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = '700px';
$table->class = "databox";
$table->head = array ();
$table->data = array ();
$table->align = array ();
$table->size = array ();

$table->head[0] = __('User ID') . ' ' .
	'<a href="?sec=gusuarios&sec2=godmode/users/user_list&sort_field=id_user&sort=up"><img src="images/sort_up.png" style="' . $selectUserIDUp . '" /></a>' .
	'<a href="?sec=gusuarios&sec2=godmode/users/user_list&sort_field=id_user&sort=down"><img src="images/sort_down.png" style="' . $selectUserIDDown . '" /></a>';
$table->head[1] = __('Name') . ' ' .
	'<a href="?sec=gusuarios&sec2=godmode/users/user_list&sort_field=fullname&sort=up"><img src="images/sort_up.png" style="' . $selectFullnameUp . '" /></a>' .
	'<a href="?sec=gusuarios&sec2=godmode/users/user_list&sort_field=fullname&sort=down"><img src="images/sort_down.png" style="' . $selectFullnameDown . '" /></a>';
$table->head[2] = __('Last contact') . ' ' . 
	'<a href="?sec=gusuarios&sec2=godmode/users/user_list&sort_field=last_connect&sort=up"><img src="images/sort_up.png" style="' . $selectLastConnectUp . '" /></a>' .
	'<a href="?sec=gusuarios&sec2=godmode/users/user_list&sort_field=last_connect&sort=down"><img src="images/sort_down.png" style="' . $selectLastConnectDown . '" /></a>';
$table->head[3] = __('Profile');
$table->head[4] = __('Description');
$table->head[5] = __('Delete');

$table->align[2] = "center";
$table->align[3] = "center";
$table->align[5] = "center";
$table->size[5] = 40;

$info = array ();

$info = get_users ($order, array ('offset' => (int) get_parameter ('offset'),
			'limit' => (int) $config['block_size']));
			
// Prepare pagination
pagination (count(get_users ()));

$rowPair = true;
$iterator = 0;
foreach ($info as $user_id => $user_info) {
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;
	
	$data[0] = '<a href="index.php?sec=gusuarios&amp;sec2=godmode/users/configure_user&amp;id='.$user_id.'">'.$user_id.'</a>';
	$data[1] = $user_info["fullname"].'<a href="#" class="tip"><span>';
	$data[1] .= __('First name').': '.$user_info["firstname"].'<br />';
	$data[1] .= __('Last name').': '.$user_info["lastname"].'<br />';
	$data[1] .= __('Phone').': '.$user_info["phone"].'<br />';
	$data[1] .= __('E-mail').': '.$user_info["email"].'<br />';
	$data[1] .= '</span></a>';
	$data[2] = print_timestamp ($user_info["last_connect"], true);
	
	if ($user_info["is_admin"]) {
		$data[3] = print_image ("images/user_suit.png", true,
			array ("alt" => __('Admin'),
				"title" => __('Administrator'))).'&nbsp;';
	} else {
		$data[3] = print_image ("images/user_green.png", true,
			array ("alt" => __('User'),
				"title" => __('Standard User'))).'&nbsp;';
	}
	
	$data[3] .= '<a href="#" class="tip"><span>';
	$result = get_db_all_rows_field_filter ("tusuario_perfil", "id_usuario", $user_id);
	if ($result !== false) {
		foreach ($result as $row) {
			$data[3] .= get_profile_name ($row["id_perfil"]);
			$data[3] .= " / ";
			$data[3] .= get_group_name ($row["id_grupo"]);
			$data[3] .= "<br />";
		}
	} else {
		$data[3] .= __('The user doesn\'t have any assigned profile/group');
	}
	$data[3] .= "</span></a>";
	
	$data[4] = print_string_substr ($user_info["comments"], 24, true);

	if ($config["admin_can_delete_user"] && $user_info['id_user'] != $config['id_user']) {
		$data[5] = "<a href='index.php?sec=gusuarios&sec2=godmode/users/user_list&user_del=1&delete_user=".$user_info['id_user']."'>".print_image('images/cross.png', true, array ('title' => __('Delete'), 'onclick' => "if (! confirm ('" .__('Deleting User'). " ". $user_info['id_user'] . ". " . __('Are you sure?') ."')) return false"))."</a>";
	} else {
		$data[5] = ''; //Delete button not in this mode
	}
	array_push ($table->data, $data);
}

print_table ($table);

echo '<div style="width: '.$table->width.'" class="action-buttons">';
unset ($table);
if ($config["admin_can_add_user"] !== false) {
	echo '<form method="post" action="index.php?sec=gusuarios&amp;sec2=godmode/users/configure_user">';
	print_input_hidden ('new_user', 1);
	print_submit_button (__('Create user'), "crt", false, 'class="sub next"');
	echo '</form>';
}
else {
	echo '<i>'.__('The current authentication scheme doesn\'t support creating users from Pandora FMS').'</i>';
}
echo '</div>';

echo '<h3>'.__('Profiles defined in Pandora').'</h3>';

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = 'databox';
$table->width = '700px';

$table->head = array ();
$table->data = array ();
$table->size = array ();
$table->align = array ();

$table->head[0] = __('Profiles');

$table->head[1] = "IR".print_help_tip (__('System incidents reading'), true);
$table->head[2] = "IW".print_help_tip (__('System incidents writing'), true);
$table->head[3] = "IM".print_help_tip (__('System incidents management'), true);
$table->head[4] = "AR".print_help_tip (__('Agents reading'), true);
$table->head[5] = "AW".print_help_tip (__('Agents management'), true);
$table->head[6] = "LW".print_help_tip (__('Alerts editing'), true);
$table->head[7] = "UM".print_help_tip (__('Users management'), true);
$table->head[8] = "DM".print_help_tip (__('Database management'), true);
$table->head[9] = "LM".print_help_tip (__('Alerts management'), true);
$table->head[10] = "PM".print_help_tip (__('Systems management'), true);
$table->head[11] = __('Delete');

$table->align = array_fill (1, 11, "center");
$table->size = array_fill (1, 10, 40);

$profiles = get_db_all_rows_in_table ("tperfil");

$img = print_image ("images/ok.png", true, array ("border" => 0)); 

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
	$data[11] = '<a href="index.php?sec=gagente&sec2=godmode/users/configure_profile&delete_profile=1&id='.$profile["id_perfil"].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;"><img src="images/cross.png"></a>';	
	array_push ($table->data, $data);
}
	
	echo '<form method="post" action="index.php?sec=gusuarios&sec2=godmode/users/configure_profile">';
	print_table ($table);
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	print_input_hidden ('new_profile', 1);
	print_submit_button (__('Create'), "crt", false, 'class="sub next"');
	echo "</div>";
	echo '</form>';
	unset ($table);

?>
