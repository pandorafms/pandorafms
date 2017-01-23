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

enterprise_hook('open_meta_frame');

include_once($config['homedir'] . "/include/functions_profile.php");
include_once($config['homedir'] . '/include/functions_users.php');
include_once ($config['homedir'] . '/include/functions_groups.php');
include_once ($config['homedir'] . '/include/functions_visual_map.php');

$meta = false;
if (enterprise_installed() && defined("METACONSOLE")) {
	$meta = true;
}

$id = get_parameter_get ("id", $config["id_user"]); // ID given as parameter
$status = get_parameter ("status", -1); // Flag to print action status message

$user_info = get_user_info ($id);
$id = $user_info["id_user"]; //This is done in case there are problems with uppercase/lowercase (MySQL auth has that problem)

if ((!check_acl ($config["id_user"], users_get_groups ($id), "UM"))
	AND ($id != $config["id_user"])) {
	
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

if (is_ajax ()) {
	
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
if ($meta) {
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
	$upd_info["firstname"] = get_parameter ("newsletter_reminder", $user_info["first_name"]);
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

	//save autorefresh list
	$autorefresh_list = get_parameter_post ("autorefresh_list");
	if(($autorefresh_list[0] === '') || ($autorefresh_list[0] === '0')){
		db_process_sql("UPDATE tconfig SET value ='' WHERE token='autorefresh_white_list'");
	}else{
		db_process_sql("UPDATE tconfig SET value ='".json_encode($autorefresh_list)."' WHERE token='autorefresh_white_list'");
	}


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
	
	if (!empty ($password_new)) {
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
$jump = "&nbsp;&nbsp;";
$table = new stdClass();
$table->id = 'user_form';
$table->width = '100%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox filters';
if (defined('METACONSOLE')) {
	$table->head[0] = __('Edit my User');
	$table->head_colspan[0] = 5;
	$table->headstyle[0] = 'text-align: center';
}
$table->style[0] = 'min-width: 320px;width: 320px;margin-right:0px;padding-right:0px;';
$table->style[1] = 'min-width: 280px;width: 280px;margin-right:0px;padding-right:0px;';
$table->style[2] = 'min-width: 150px;width: 150px;margin-right:0px;margin-left:0px;padding-left:0px;padding-right:0px;';

$data = array();
$data[0] = '<span style="width:50%;float:left;"><b>' . __('User ID') . '</b></span>';
$data[0] .= $jump . '<span style="font-weight: normal;width:20%;float:left;">' . $id . '</span>';
$data[1] = '<span style="width:40%;float:left;line-height:20px;"><b>' . __('Full (display) name') . '</b></span>';
$data[1] .= $jump . '<span style="width:20%;float:left;line-height:20px;">' . html_print_input_text_extended ("fullname", $user_info["fullname"], '', '', 20, 100, $view_mode, '', 'class="input"', true).'</span>';
// Show "Picture" (in future versions, why not, allow users to upload it's own avatar here.

if (is_user_admin ($id)) {
	$data[2] = html_print_image('images/people_1.png', true); 
} 
else {
	$data[2] = html_print_image('images/people_2.png', true); 
}

if ($view_mode === false) {
	$table->rowspan[0][2] = 3;
}
else {
	$table->rowspan[0][2] = 2;
}
$table->rowclass[] = '';
$table->rowstyle[] = 'font-weight: bold;';
$table->data[] = $data;

$data = array();
$data[0] = '<span style="width:50%;float:left;">'.__('E-mail').'</span>';
$data[0] .= $jump .'<span style="width:20%;float:left;line-height:20px;">'. html_print_input_text_extended ("email", $user_info["email"], '', '', '25', '100', $view_mode, '', 'class="input"', true).'</span>';
$data[1] = '<span style="width:40%;float:left;">'.__('Phone number').'</span>';
$data[1] .= $jump . '<div style="width:20%;float:left;line-height:50px;">'.html_print_input_text_extended ("phone", $user_info["phone"], '', '', '20', '30', $view_mode, '', 'class="input"', true).'</div>';
$table->rowclass[] = '';
$table->rowstyle[] = 'font-weight: bold;';
$table->data[] = $data;

if ($view_mode === false) {
	if ($config["user_can_update_password"]) {
		$data = array();
		$data[0] = '<span style="width:50%;float:left;">'.__('New Password').'</span>';
		$data[0] .=  $jump .'<span style="width:20%;float:left;line-height:20px;">'.html_print_input_text_extended ("password_new", "", '', '', '25', '45', $view_mode, '', 'class="input"', true, true).'</span>';
		$data[1] = '<span style="width:40%;float:left;">'.__('Password confirmation').'</span>';
		$data[1] .= $jump . '<span style="width:20%;float:left;line-height:20px;">'.html_print_input_text_extended ("password_conf", "", '', '', '20', '45', $view_mode, '', 'class="input"', true, true).'</span>';
		$table->rowclass[] = '';
		$table->rowstyle[] = 'font-weight: bold;';
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
$data[0] = '<span style="width:50%;float:left;">'.__('Block size for pagination') . ui_print_help_tip(__('If checkbox is clicked then block size global configuration is used'), true).'</span>';
if ($user_info["block_size"] == 0) {
	$block_size = $config["global_block_size"];
}
else {
	$block_size = $user_info["block_size"];
}
$data[0] .= $jump .'<span style="font-weight: normal;width:15%;float:left;line-height:20px;">'. html_print_input_text ('block_size', $block_size, '', 5, 5, true).'</span>';
$data[0] .= $jump . '<span style="width:2%;float:left;line-height:20px;margin-right:5px;">'.html_print_checkbox('default_block_size', 1, $user_info["block_size"] == 0, true).'</span>';
$data[0] .= __('Default').' ('.$config["global_block_size"].')';

$values = array(-1 => __('Default'),1 => __('Yes'),0 => __('No'));

$data[1] = '<span style="width:40%;float:left;">'.__('Interactive charts') . ui_print_help_tip(__('Whether to use Javascript or static PNG graphs'), true).'</span>';
$data[1] .= $jump . '<span style="width:20%;float:left;line-height:20px;">'. html_print_select($values, 'flash_charts', $user_info["flash_chart"], '', '', -1, true, false, false).'</span>';


$data[2] = '<span style="width:30%;float:left;">'.__('Language').'</span>';
$data[2] .= $jump . html_print_select_from_sql ('SELECT id_language, name FROM tlanguage',
	'language', $user_info["language"], '', __('Default'), 'default', true,'','','','','',10);

$table->rowclass[] = '';
$table->rowstyle[] = 'font-weight: bold;';
$table->data[] = $data;

$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$display_all_group = true;
else
	$display_all_group = false;

$usr_groups = (users_get_groups($config['id_user'], 'AR', $display_all_group));
$id_usr = $config['id_user'];

if (!$meta) {
	$data = array();
	$data[0] = '<span style="width:50%;float:left;">'.__('Shortcut bar') . ui_print_help_tip(__('This will activate a shortcut bar with alerts, events, messages... information'), true).'</span>';
	$data[0] .= $jump . '<span style="width:20%;float:left;line-height:20px;">'.html_print_checkbox('shortcut_bar', 1, $user_info["shortcut"], true).'</span>';

	$data[1] = '<span style="width:40%;float:left;">'.__('Home screen'). ui_print_help_tip(__('User can customize the home page. By default, will display \'Agent Detail\'. Example: Select \'Other\' and type sec=estado&sec2=operation/agentes/estado_agente to show agent detail view'), true).'</span>';
	$values = array (
		'Default' =>__('Default'),
		'Visual console'=>__('Visual console'),
		'Event list'=>__('Event list'),
		'Group view'=>__('Group view'),
		'Tactical view'=>__('Tactical view'),
		'Alert detail' => __('Alert detail'),
		'Other'=>__('Other'));
	if (enterprise_installed()) {
		$values['Dashboard'] = __('Dashboard');
	}

	$data[1] .= $jump . '<span style="width:20%;float:left;line-height:20px;">'.html_print_select($values, 'section', io_safe_output($user_info["section"]), 'show_data_section();', '', -1, true, false, false).'</span>';

	if (enterprise_installed()) {
		$dashboards = get_user_dashboards ($config['id_user']);
		$dashboards_aux = array();
		if ($dashboards === false) {
			$dashboards = array('None'=>'None');
		}
		else {
			foreach ($dashboards as $key => $dashboard) {
				$dashboards_aux[$dashboard['name']] = $dashboard['name'];
			}
		}
		$data[1] .= html_print_select ($dashboards_aux, 'dashboard', $user_info["data_section"], '', '', '', true);
	}
	
	$layouts = visual_map_get_user_layouts ($config['id_user'], true);
	$layouts_aux = array();
	if ($layouts === false) {
		$layouts_aux = array('None'=>'None');
	}
	else {
		foreach ($layouts as $layout) {
			$layouts_aux[$layout] = $layout;
		}
	}
	$data[1] .=  html_print_select ($layouts_aux, 'visual_console', $user_info["data_section"], '', '', '', true);
	$data[1] .=  html_print_input_text ('data_section', $user_info["data_section"], '', 60, 255, true, false);
	
	
	
	// User only can change skins if has more than one group 
	$data[2] = '';
	if (function_exists('skins_print_select')) {
		if (count($usr_groups) > 1) {
			$data[2] = '<span style="width:30%;float:left;">'.__('Skin').'</span>';
			$data[2] .= $jump . skins_print_select($id_usr,'skin', $user_info['id_skin'], '', __('None'), 0, true);
		}
	}
	$table->rowclass[] = '';
	$table->rowstyle[] = 'font-weight: bold;';
	$table->data[] = $data;
}

// Double auth
$double_auth_enabled = (bool) db_get_value('id', 'tuser_double_auth', 'id_user', $config['id_user']);
$data = array();

if (license_free()) {
$data[0] = '<span style="width:50%;float:left;">'.__('Double authentication').'</span>';
}
else{
$data[0] = '<span style="width:21%;float:left;">'.__('Double authentication').'</span>';	
}


$data[0] .= $jump;
$data[0] .= '<span style="width:20%;float:left;line-height:20px;">'.html_print_checkbox('double_auth', 1, $double_auth_enabled, true).'</span>';
if ($double_auth_enabled) {
	$data[0] .= $jump;
	$data[0] .= html_print_button(__('Show information'), 'show_info', false, 'javascript:show_double_auth_info();', '', true);
}
// Dialog
$data[0] .= '<div id="dialog-double_auth"><div id="dialog-double_auth-container"></div></div>';

if (license_free()) {
	$data[1] = __('Newsletter Subscribed') . ':';
	if ($user_info["middlename"]) {
		$data[1] .= $jump . '<span style="font-weight:initial;">' . __('Already subscribed to Pandora FMS newsletter') . "</span>";
	}
	else {
		$data[1] .= $jump . '<span style="font-weight:initial;"><a style="text-decoration:underline;" href="javascript: force_run_newsletter();">' . __('Subscribe to our newsletter') . "</a></span>";
	}
	
	$data[2] = __('Newsletter Reminder') . ' ';
	if ($user_info["firstname"] != 0) $user_info["firstname"] = 1;
	$data[2] .= html_print_checkbox('newsletter_reminder', 1, $user_info["firstname"], true);
} 
else {
	$table->colspan[count($table->data)][0] = 3;
}
$table->rowclass[] = '';
$table->rowstyle[] = 'font-weight: bold;';
$table->data[] = $data;

$data = array();
$data[0] = __('Comments');
$table->colspan[count($table->data)][0] = 3;
$table->rowclass[] = '';
$table->rowstyle[] = 'font-weight: bold;';
$table->data[] = $data;

$data = array();
$data[0] = '<div style="width:98%">'.html_print_textarea("comments", 2, 60, $user_info["comments"], ($view_mode ? 'readonly="readonly"' : ''), true).'</div>';
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

if (!defined('METACONSOLE'))
	echo '<h4>'.__('Profiles/Groups assigned to this user').'</h4>';

$table->width = '100%';
$table->class = 'databox data';
if (defined('METACONSOLE')) {
	$table->width = '100%';
	$table->class = 'databox data';
	$table->title = __('Profiles/Groups assigned to this user');
	$table->head_colspan[0] = 0;
	$table->headstyle[] = "background-color: #82B93C";
	$table->headstyle[] = "background-color: #82B93C";
	$table->headstyle[] = "background-color: #82B93C";
}

$table->data = array ();
$table->head = array ();
$table->align = array ();
$table->style = array ();

if (!defined('METACONSOLE')) {
	$table->style[0] = 'font-weight: bold';
	$table->style[1] = 'font-weight: bold';
}

$table->head[0] = __('Profile name');
$table->head[1] = __('Group');
$table->head[2] = __('Tags');
$table->align = array();
$table->align[1] = 'left';

$table->data = array ();

$result = db_get_all_rows_field_filter ("tusuario_perfil", "id_usuario", $id);
if ($result === false) {
	$result = array ();
}

foreach ($result as $profile) {
	$data[0] = '<b>'.profile_get_name ($profile["id_perfil"]).'</b>';
	if ($config["show_group_name"])
		$data[1] = ui_print_group_icon ($profile["id_grupo"], true) .
			'<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id=' . $profile['id_grupo'] . '">' .
			'&nbsp;' . '</a>';
	else
		$data[1] = ui_print_group_icon ($profile["id_grupo"], true) .
			'<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id=' . $profile['id_grupo'] . '">' .
			'&nbsp;' . ui_print_truncate_text(groups_get_name ($profile['id_grupo'], True), GENERIC_SIZE_TEXT) .
			'</a>';
	
	$tags_ids = explode(',',$profile["tags"]);
	$tags = tags_get_tags($tags_ids);
		
	$data[2] = tags_get_tags_formatted($tags);
	
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	html_print_table ($table);
}
else {
	ui_print_info_message ( array('no_close'=>true, 'message'=>  __('This user doesn\'t have any assigned profile/group.') ) );
}

enterprise_hook('close_meta_frame');

?>

<script language="javascript" type="text/javascript">
$(document).ready (function () {
	
	check_default_block_size()
	$("#checkbox-default_block_size").change(function() {
		check_default_block_size();
	});
	
	function check_default_block_size() {
		if ($("#checkbox-default_block_size").is(':checked')) {
			$("#text-block_size").attr('disabled', true);
		}
		else {
			$("#text-block_size").removeAttr('disabled');
		}
	}

	$("input#checkbox-double_auth").change(function (e) {
		e.preventDefault();

		if (this.checked) {
			show_double_auth_activation();
		}
		else {
			show_double_auth_deactivation();
		}
	});
	
	show_data_section();
});

function show_data_section () {
	section = $("#section").val();
	
	switch (section) {
		case <?php echo "'" . 'Dashboard' . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . 'Visual console' . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "");
			break;
		case <?php echo "'" . 'Event list' . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . 'Group view' . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . 'Tactical view' . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . 'Alert detail' . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . 'Other' . "'"; ?>:
			$("#text-data_section").css("display", "");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
		case <?php echo "'" . 'Default' . "'"; ?>:
			$("#text-data_section").css("display", "none");
			$("#dashboard").css("display", "none");
			$("#visual_console").css("display", "none");
			break;
	}
}

function show_double_auth_info () {
	var userID = "<?php echo $config['id_user']; ?>";

	var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
	var $dialogContainer = $("div#dialog-double_auth-container");

	$dialogContainer.html($loadingSpinner);

	// Load the info page
	var request = $.ajax({
		url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
		type: 'POST',
		dataType: 'html',
		data: {
			page: 'include/ajax/double_auth.ajax',
			id_user: userID,
			get_double_auth_data_page: 1,
			containerID: $dialogContainer.prop('id')
		},
		complete: function(xhr, textStatus) {
			
		},
		success: function(data, textStatus, xhr) {
			// isNaN = is not a number
			if (isNaN(data)) {
				$dialogContainer.html(data);
			}
			// data is a number, convert it to integer to do the compare
			else if (Number(data) === -1) {
				$dialogContainer.html("<?php echo '<b><div class=\"red\">' . __('Authentication error') . '</div></b>'; ?>");
			}
			else {
				$dialogContainer.html("<?php echo '<b><div class=\"red\">' . __('Error') . '</div></b>'; ?>");
			}
		},
		error: function(xhr, textStatus, errorThrown) {
			$dialogContainer.html("<?php echo '<b><div class=\"red\">' . __('There was an error loading the data') . '</div></b>'; ?>");
		}
	});

	$("div#dialog-double_auth")
		.append($dialogContainer)
		.dialog({
			resizable: true,
			draggable: true,
			modal: true,
			title: "<?php echo __('Double autentication information'); ?>",
			overlay: {
				opacity: 0.5,
				background: "black"
			},
			width: 400,
			height: 375,
			close: function(event, ui) {
				// Abort the ajax request
				if (typeof request != 'undefined')
					request.abort();
				// Remove the contained html
				$dialogContainer.empty();
			}
		})
		.show();

}

function show_double_auth_activation () {
	var userID = "<?php echo $config['id_user']; ?>";

	var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
	var $dialogContainer = $("div#dialog-double_auth-container");

	$dialogContainer.html($loadingSpinner);

	// Load the info page
	var request = $.ajax({
		url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
		type: 'POST',
		dataType: 'html',
		data: {
			page: 'include/ajax/double_auth.ajax',
			id_user: userID,
			get_double_auth_info_page: 1,
			containerID: $dialogContainer.prop('id')
		},
		complete: function(xhr, textStatus) {
			
		},
		success: function(data, textStatus, xhr) {
			// isNaN = is not a number
			if (isNaN(data)) {
				$dialogContainer.html(data);
			}
			// data is a number, convert it to integer to do the compare
			else if (Number(data) === -1) {
				$dialogContainer.html("<?php echo '<b><div class=\"red\">' . __('Authentication error') . '</div></b>'; ?>");
			}
			else {
				$dialogContainer.html("<?php echo '<b><div class=\"red\">' . __('Error') . '</div></b>'; ?>");
			}
		},
		error: function(xhr, textStatus, errorThrown) {
			$dialogContainer.html("<?php echo '<b><div class=\"red\">' . __('There was an error loading the data') . '</div></b>'; ?>");
		}
	});

	$("div#dialog-double_auth").dialog({
			resizable: true,
			draggable: true,
			modal: true,
			title: "<?php echo __('Double autentication activation'); ?>",
			overlay: {
				opacity: 0.5,
				background: "black"
			},
			width: 500,
			height: 400,
			close: function(event, ui) {
				// Abort the ajax request
				if (typeof request != 'undefined')
					request.abort();
				// Remove the contained html
				$dialogContainer.empty();

				document.location.reload();
			}
		})
		.show();
}

function show_double_auth_deactivation () {
	var userID = "<?php echo $config['id_user']; ?>";

	var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
	var $dialogContainer = $("div#dialog-double_auth-container");

	var message = "<p><?php echo __('Are you sure?') . '<br>' . __('The double authentication will be deactivated'); ?></p>";
	var $button = $("<input type=\"button\" value=\"<?php echo __('Deactivate'); ?>\" />");

	$dialogContainer
		.empty()
		.append(message)
		.append($button);

	var request;

	$button.click(function(e) {
		e.preventDefault();

		$dialogContainer.html($loadingSpinner);

		// Deactivate the double auth
		request = $.ajax({
			url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
			type: 'POST',
			dataType: 'json',
			data: {
				page: 'include/ajax/double_auth.ajax',
				id_user: userID,
				deactivate_double_auth: 1
			},
			complete: function(xhr, textStatus) {
				
			},
			success: function(data, textStatus, xhr) {
				if (data === -1) {
					$dialogContainer.html("<?php echo '<b><div class=\"red\">' . __('Authentication error') . '</div></b>'; ?>");
				}
				else if (data) {
					$dialogContainer.html("<?php echo '<b><div class=\"green\">' . __('The double autentication was deactivated successfully') . '</div></b>'; ?>");
				}
				else {
					$dialogContainer.html("<?php echo '<b><div class=\"red\">' . __('There was an error deactivating the double autentication') . '</div></b>'; ?>");
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$dialogContainer.html("<?php echo '<b><div class=\"red\">' . __('There was an error deactivating the double autentication') . '</div></b>'; ?>");
			}
		});
	});
	

	$("div#dialog-double_auth").dialog({
			resizable: true,
			draggable: true,
			modal: true,
			title: "<?php echo __('Double autentication activation'); ?>",
			overlay: {
				opacity: 0.5,
				background: "black"
			},
			width: 300,
			height: 150,
			close: function(event, ui) {
				// Abort the ajax request
				if (typeof request != 'undefined')
					request.abort();
				// Remove the contained html
				$dialogContainer.empty();

				document.location.reload();
			}
		})
		.show();
}
</script>
