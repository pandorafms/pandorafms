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
include_once ($config['homedir'] . '/include/functions_visual_map.php');

$meta = false;
if(enterprise_installed() && defined("METACONSOLE")) {
	$meta = true;
}

$id = get_parameter_get ("id", $config["id_user"]); // ID given as parameter
$status = get_parameter ("status", -1); // Flag to print action status message

$user_info = get_user_info ($id);
$id = $user_info["id_user"]; //This is done in case there are problems with uppercase/lowercase (MySQL auth has that problem)

if ((!check_acl ($config["id_user"], users_get_groups ($id), "UM")) AND ($id != $config["id_user"])){
	db_pandora_audit("ACL Violation","Trying to view a user without privileges");
	require ("general/noaccess.php");
	exit;
}

//If current user is editing himself or if the user has UM (User Management) rights on any groups the user is part of AND the authorization scheme allows for users/admins to update info
if (($config["id_user"] == $id || check_acl ($config["id_user"], users_get_groups ($id), "UM")) && $config["user_can_update_info"]) {
	$view_mode = false;
}
else {
	$view_mode = true;
}

if (is_ajax ()){
	
	$shortcut_update = get_parameter("shortcut_update", 0);
	
	// Update of user to show/don't show shortcut bar	
	if ($shortcut_update) {
		
		// First we get the actual state
		$shortcut_value = db_get_value_filter('shortcut', 'tusuario', array('id_user' => $id));
		
		//Deactivate shorcut var
		if ($shortcut_value == 1) {
			db_process_sql_update('tusuario', array('shortcut' => 0), array('id_user' => $id));
		}
		// Activate shortcut var
		else {
			db_process_sql_update('tusuario', array('shortcut' => 1), array('id_user' => $id));
		}
	
	}
	
	return;
}

// Header
if($meta) {
	user_meta_print_header();
	$url = 'index.php?sec=advanced&amp;sec2=advanced/users_setup&amp;tab=user_edit';
}
else {
	ui_print_page_header (__('User detail editor'), "images/op_workspace.png", false, "", false, "");
	$url = 'index.php?sec=workspace&amp;sec2=operation/users/user_edit';
}

// Update user info
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
	$upd_info["id_skin"] = get_parameter ("skin", $user_info["id_skin"]);
	$upd_info["block_size"] = get_parameter ("block_size", $config["block_size"]);
	$default_block_size = get_parameter ("default_block_size", 0);
	if($default_block_size) {
		$upd_info["block_size"] = 0;
	}
	
	$upd_info["flash_chart"] = get_parameter ("flash_charts", $config["flash_charts"]);
	$upd_info["shortcut"] = get_parameter ("shortcut_bar", 0);
	$upd_info["section"] = get_parameter ("section", $user_info["section"]);
	$upd_info["data_section"] = get_parameter ("data_section", '');
	$dashboard = get_parameter('dashboard', '');
	$visual_console = get_parameter('visual_console', '');
	
	$is_admin = db_get_value('is_admin', 'tusuario', 'id_user', $id);
	
	$section = io_safe_output($upd_info["section"]);
	if (($section == 'Event list') || ($section == 'Group view') || ($section == 'Alert detail') || ($section == 'Tactical view') || ($section == 'Default')) {
		$upd_info["data_section"] = '';
	}
	else if ($section == 'Dashboard') {
		$upd_info["data_section"] = $dashboard;
	}
	else if ($section == 'Visual console') {
		$upd_info["data_section"] = $visual_console;
	}
	
	if ( !empty ($password_new)) {
		if ($config["user_can_update_password"] && $password_confirm == $password_new) {
			if ((!$is_admin || $config['enable_pass_policy_admin']) && $config['enable_pass_policy']) {
				$pass_ok = login_validate_pass($password_new, $id, true);
				if ($pass_ok != 1) {
					ui_print_error_message($pass_ok);
				}
				else {
					$return = update_user_password ($id, $password_new);
					if ($return) {
						$return2 = save_pass_history($id, $password_new);
					}
					ui_print_result_message ($return,
					__('Password successfully updated'),
					__('Error updating passwords: %s', $config['auth_error']));
				}
			}
			else {
				$return = update_user_password ($id, $password_new);
				ui_print_result_message ($return,
					__('Password successfully updated'),
					__('Error updating passwords: %s', $config['auth_error']));
			}
			
		}
		elseif ($password_new !== "NON-INIT") {
			ui_print_error_message (__('Passwords didn\'t match or other problem encountered while updating passwords'));
		}
	}
	
	// No need to display "error" here, because when no update is needed (no changes in data) 
	// SQL function returns	0 (FALSE), but is not an error, just no change. Previous error
	// message could be confussing to the user.
	
	$return = update_user ($id, $upd_info);
	if ($return > 0) {
		ui_print_result_message ($return,
			__('User info successfully updated'),
			__('Error updating user info'));
	}
	
	$user_info = $upd_info;
}

// Prints action status for current message 
if ($status != -1) {
	ui_print_result_message ($status,
		__('User info successfully updated'),
		__('Error updating user info'));
}

$table->id = 'user_form';
$table->width = '98%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = '';

$data = array();
$data[0] = '<b>' . __('User ID') . '</b>';
$data[1] = '<b>' . __('Full (display) name') . '</b>';
// Show "Picture" (in future versions, why not, allow users to upload it's own avatar here.
if (is_user_admin ($id)) {
	$data[2] = html_print_image('images/people_1.png', true); 
} 
else {
	$data[2] = html_print_image('images/people_2.png', true); 
}
if ($view_mode === false) {
	$table->rowspan[0][2] = 6;
}
else {
	$table->rowspan[0][2] = 4;
}

$table->cellstyle[0][2] = 'text-align:center;';
$table->rowclass[] = '';
$table->rowstyle[] = 'font-weight: bold;';
$table->data[] = $data;

$data = array();
$data[0] = $id;
$data[1] = html_print_input_text_extended ("fullname", $user_info["fullname"], '', '', 40, 100, $view_mode, '', 'class="input"', true);
$table->rowclass[] = '';
$table->rowstyle[] = '';
$table->data[] = $data;

$data = array();
$data[0] = __('E-mail');
$data[1] = __('Phone number');
$table->rowclass[] = '';
$table->rowstyle[] = 'font-weight: bold;';
$table->data[] = $data;

$data = array();
$data[0] = html_print_input_text_extended ("email", $user_info["email"], '', '', '40', '100', $view_mode, '', 'class="input"', true);
$data[1] = html_print_input_text_extended ("phone", $user_info["phone"], '', '', '40', '30', $view_mode, '', 'class="input"', true);
$table->rowclass[] = '';
$table->rowstyle[] = '';
$table->data[] = $data;

if ($view_mode === false) {
	if ($config["user_can_update_password"]) {
		$data = array();
		$data[0] = __('New Password');
		$data[1] = __('Password confirmation');
		$table->rowclass[] = '';
		$table->rowstyle[] = 'font-weight: bold;';
		$table->data[] = $data;
		
		$data = array();
		$data[0] = html_print_input_text_extended ("password_new", "", '', '', '40', '25', $view_mode, '', 'class="input"', true, true);
		$data[1] = html_print_input_text_extended ("password_conf", "", '', '', '40', '25', $view_mode, '', 'class="input"', true, true);
		$table->rowclass[] = '';
		$table->rowstyle[] = '';
		$table->data[] = $data;
	}
	else {
		$data = array();
		$data[0] = '<i>'.__('You can not change your password from Pandora FMS under the current authentication scheme').'</i>';
		$table->rowclass[] = '';
		$table->rowstyle[] = '';
		$table->colspan[count($table-data)][0] = 2;
		$table->data[] = $data;
	}
}

$data = array();
$data[0] = __('Block size for pagination') . ui_print_help_tip(__('If checkbox is clicked then block size global configuration is used'), true);
$data[1] = __('Interactive charts') . ui_print_help_tip(__('Whether to use Javascript or static PNG graphs'), true);
$data[2] = __('Language');
$table->rowclass[] = '';
$table->rowstyle[] = 'font-weight: bold;';
$table->data[] = $data;

$data = array();
if ($user_info["block_size"] == 0) {
	$block_size = $config["global_block_size"];
}
else {
	$block_size = $user_info["block_size"];
}
$data[0] = html_print_input_text ('block_size', $block_size, '', 5, 5, true);
$data[0] .= html_print_checkbox('default_block_size', 1, $user_info["block_size"] == 0, true);
$data[0] .= __('Default').' ('.$config["global_block_size"].')';
$values = array(-1 => __('Default'),1 => __('Yes'),0 => __('No'));
$data[1] = html_print_select($values, 'flash_charts', $user_info["flash_chart"], '', '', -1, true, false, false);
$data[2] = html_print_select_from_sql ('SELECT id_language, name FROM tlanguage',
	'language', $user_info["language"], '', __('Default'), 'default', true);
$table->rowclass[] = '';
$table->rowstyle[] = '';
$table->data[] = $data;

$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$display_all_group = true;
else
	$display_all_group = false;

$usr_groups = (users_get_groups($config['id_user'], 'AR', $display_all_group));
$id_usr = $config['id_user'];

if(!$meta) {
	$data = array();
	$data[0] = __('Shortcut bar') . ui_print_help_tip(__('This will activate a shortcut bar with alerts, events, messages... information'), true);
	$data[1] = __('Home screen'). ui_print_help_tip(__('User can customize the home page. By default, will display \'Agent Detail\'. Example: Select \'Other\' and type sec=estado&sec2=operation/agentes/estado_agente to show agent detail view'), true);
	// User only can change skins if has more than one group 
	if (count($usr_groups) > 1) {
		$data[2] = __('Skin');
	}
	else {
		$data[2] = '';
	}
	$table->rowclass[] = '';
	$table->rowstyle[] = 'font-weight: bold;';
	$table->data[] = $data;

	$data = array();
	$data[0] = html_print_checkbox('shortcut_bar', 1, $user_info["shortcut"], true);
	$values = array ('Default' =>__('Default'), 'Dashboard'=>__('Dashboard'), 'Visual console'=>__('Visual console'), 'Event list'=>__('Event list'),
		'Group view'=>__('Group view'), 'Tactical view'=>__('Tactical view'), 'Alert detail' => __('Alert detail'), 'Other'=>__('Other'));
	$data[1] = html_print_select($values, 'section', io_safe_output($user_info["section"]), 'show_data_section();', '', -1, true, false, false);
	// User only can change skins if has more than one group 
	if (count($usr_groups) > 1) {
		$data[2] = skins_print_select($id_usr,'skin', $user_info['id_skin'], '', __('None'), 0, true);
	}
	else {
		$data[2] = '';
	}
	$table->rowclass[] = '';
	$table->rowstyle[] = '';
	$table->data[] = $data;
}

$data = array();
$data[0] = __('Comments');
$table->colspan[count($table->data)][0] = 3;
$table->rowclass[] = '';
$table->rowstyle[] = 'font-weight: bold;';
$table->data[] = $data;

$data = array();
$data[0] = html_print_textarea("comments", 2, 60, $user_info["comments"], ($view_mode ? 'readonly="readonly"' : ''), true);
$data[0] .= html_print_input_hidden('quick_language_change', 1, true);
$table->colspan[count($table->data)][0] = 3;
$table->rowclass[] = '';
$table->rowstyle[] = '';
$table->data[] = $data;

echo '<form name="user_mod" method="post" action="'.$url.'&amp;modified=1&amp;id='.$id.'&amp;pure='.$config['pure'].'">';

html_print_table($table);


echo '<div style="width:' . $table->width . '; text-align:right;">';
if (!$config["user_can_update_info"]) {
	echo '<i>'.__('You can not change your user info from Pandora FMS under the current authentication scheme').'</i>';
}
else {
	html_print_submit_button (__('Update'), 'uptbutton', $view_mode, 'class="sub upd"');
}
echo '</div></form>';

unset($table);

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
$table->head[2] = __('Tags');
$table->align = array();
$table->align[1] = 'center';

$table->data = array ();

$result = db_get_all_rows_field_filter ("tusuario_perfil", "id_usuario", $id);
if ($result === false) {
	$result = array ();
}

foreach ($result as $profile) {
	$data[0] = '<b>'.profile_get_name ($profile["id_perfil"]).'</b>';
	$data[1] = ui_print_group_icon ($profile["id_grupo"], true).' <a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$profile['id_grupo'].'"></a>';
		
	$tags_ids = explode(',',$profile["tags"]);
	$tags = tags_get_tags($tags_ids);
		
	$data[2] = tags_get_tags_formatted($tags);
	
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	html_print_table ($table);
}
else {
	echo '<div class="nf">'.__('This user doesn\'t have any assigned profile/group').'</div>'; 
}
?>

<script language="javascript" type="text/javascript">
$(document).ready (function () {
	check_default_block_size()
	$("#checkbox-default_block_size").change(function() {
		check_default_block_size();
	});
	
	function check_default_block_size() {
		if($("#checkbox-default_block_size").is(':checked')) {
			$("#text-block_size").attr('disabled', true);
		}
		else {
			$("#text-block_size").removeAttr('disabled');
		}
	}
	show_data_section();
});

function show_data_section () {
	section=$("#section").val();
	switch (section) {
		case <?php echo "'" . __('Dashboard') . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . __('Visual console') . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "");
			break;
		case <?php echo "'" . __('Event list') . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . __('Group view') . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . __('Tactical view') . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . __('Alert detail') . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . __('Other') . "'"; ?>:
			$("#text-data_section").css("display", "");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . __('Default') . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
	}
}
</script>
