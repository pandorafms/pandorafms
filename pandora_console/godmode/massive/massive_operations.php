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
check_login ();

if (! check_acl ($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access massive operation section");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_modules.php');

enterprise_include ('godmode/massive/massive_operations.php');

$tab = (string) get_parameter ('tab', 'massive_agents');
$option = (string) get_parameter ('option', '');

$options_alerts = array('add_alerts' => __('Massive alerts addition'),
	'delete_alerts' => __('Massive alerts deletion'), 
	'add_action_alerts' => __('Massive alert actions addition'),
	'delete_action_alerts' => __('Massive alert actions deletion'),
	'enable_disable_alerts' => __('Massive alert enable/disable'),
	'standby_alerts' => __('Massive alert setting standby'));

$options_agents = array('edit_agents' => __('Massive agents edition'),
	'delete_agents' => __('Massive agents deletion'));

if (check_acl ($config['id_user'], 0, "PM")) {
	$options_users = array(
		'add_profiles' => __('Massive profiles addition'),
		'delete_profiles' => __('Massive profiles deletion'));
}
else {
	$options_users = array();
}

$options_modules = array(
	'delete_modules' => __('Massive modules deletion'),
	'edit_modules' => __('Massive modules edition'), 
	'copy_modules' => __('Massive modules copy'));

$options_policies = array();

$policies_options = enterprise_hook('massive_policies_options');

if($policies_options != -1) {
	$options_policies =
		array_merge($options_policies, $policies_options);
}

if (in_array($option, array_keys($options_alerts))) {
	$tab = 'massive_alerts';
}
elseif (in_array($option, array_keys($options_agents))) {
	$tab = 'massive_agents';
}
elseif (in_array($option, array_keys($options_users))) {
	$tab = 'massive_users';
}
elseif (in_array($option, array_keys($options_modules))) {
	$tab = 'massive_modules';
}
elseif (in_array($option, array_keys($options_policies))) {
	$tab = 'massive_policies';
}
else {
	$option = '';
}

switch($tab) {
	case 'massive_alerts':
		$options = $options_alerts;
		break;
	case 'massive_agents':
		$options = $options_agents;
		break;
	case 'massive_modules':
		$options = $options_modules;
		break;
	case 'massive_users':
		$options = $options_users;
		break;
	case 'massive_policies':
		$options = $options_policies;
		break;
}

// Set the default option of the category
if ($option == '') {
	$option = array_shift(array_keys($options));
}

$alertstab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_alerts">'
	. html_print_image ('images/bell.png', true, array ('title' => __('Alerts operations')))
	. '</a>', 'active' => $tab == 'massive_alerts');

$userstab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_users">'
	. html_print_image ('images/group.png', true, array ('title' => __('Users operations')))
	. '</a>', 'active' => $tab == 'massive_users');

$agentstab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_agents">'
	. html_print_image ('images/bricks.png', true, array ('title' => __('Agents operations')))
	. '</a>', 'active' => $tab == 'massive_agents');

$modulestab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_modules">'
	. html_print_image ('images/brick.png', true, array ('title' => __('Modules operations')))
	. '</a>', 'active' => $tab == 'massive_modules');

/* Collection */
$policiestab = enterprise_hook('massive_policies_tab');

if ($policiestab == -1)
	$policiestab = "";

$onheader = array();
$onheader['massive_agents'] = $agentstab;
$onheader['massive_modules'] = $modulestab;
if (check_acl ($config['id_user'], 0, "PM")) {
	$onheader['user_agents'] = $userstab;
}
$onheader['massive_alerts'] = $alertstab;
$onheader['policies'] = $policiestab;

ui_print_page_header (__('Massive operations'). ' &raquo; '. $options[$option], "images/sitemap_color.png", false, "", true, $onheader);

// Checks if the PHP configuration is correctly
if ((get_cfg_var("max_execution_time") != 0) or (get_cfg_var("max_input_time") != -1)){
	echo '<div id="notify_conf" class="notify">';
	echo __("In order to perform massive operations, PHP needs a correct configuration in timeout parameters. Please, open your PHP configuration file (php.ini) for example: <i>sudo vi /etc/php5/apache2/php.ini;</i><br> And set your timeout parameters to a correct value: <br><i> max_execution_time = 0</i> and <i>max_input_time = -1</i>");
	echo '</div>';
}

// Catch all submit operations in this view to display Wait banner
$submit_action = get_parameter('go');
$submit_update = get_parameter('updbutton');
$submit_del = get_parameter('del');
$submit_template_disabled = get_parameter('id_alert_template_disabled');
$submit_template_enabled = get_parameter('id_alert_template_enabled');
$submit_template_not_standby = get_parameter('id_alert_template_not_standby');
$submit_template_standby = get_parameter('id_alert_template_standby');
$submit_add = get_parameter('crtbutton');

echo '<div id="loading" display="none">';
echo html_print_image("images/wait.gif", true, array("border" => '0')) . '<br />';
echo '<strong>' . __('Please wait...') . '</strong>';
echo '</div>';
?>

<script language="javascript" type="text/javascript">
	$(document).ready (function () {
		$('#manage_config_form').submit( function() {
			confirm_status =
				confirm("<?php echo __('Are you sure?'); ?>");
			if (confirm_status)
				$("#loading").css("display", "");
			else
				return false;
		});
		
		$('#form_edit').submit( function() {
			confirm_status =
				confirm("<?php echo __('Are you sure?'); ?>");
			if (confirm_status)
				$("#loading").css("display", "");
			else
				return false;
		});
		
		$('[id^=form]').submit( function() {
			confirm_status =
				confirm("<?php echo __('Are you sure?'); ?>");
			if (confirm_status)
				$("#loading").css("display", "");
			else
				return false;
		});
		
		$("#loading").css("display", "none");
	});
</script>

<?php

echo "<br />";
echo '<form method="post" id="form_options" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations">';
echo '<table border="0"><tr><td>';
echo __("Action");
echo '</td><td>';
html_print_select($options, 'option', $option, 'this.form.submit()', '',
	0, false, false, false);
if($option == 'edit_agents' || $option == 'edit_modules') 
	echo '<a href="#" class="tip">&nbsp;<span>' .
		__("The blank fields will not be updated") . '</span></a>';
echo '</td></tr></table>';
echo '</form>';
echo "<br />";

switch ($option) {
	case 'delete_alerts':
		require_once ('godmode/massive/massive_delete_alerts.php');
		break;
	case 'add_alerts':
		require_once ('godmode/massive/massive_add_alerts.php');
		break;
	case 'delete_action_alerts':
		require_once ('godmode/massive/massive_delete_action_alerts.php');
		break;
	case 'add_action_alerts':
		require_once ('godmode/massive/massive_add_action_alerts.php');
		break;
	case 'enable_disable_alerts':
		require_once ('godmode/massive/massive_enable_disable_alerts.php');
		break;
	case 'standby_alerts':
		require_once ('godmode/massive/massive_standby_alerts.php');
		break;
	case 'add_profiles':
		require_once ('godmode/massive/massive_add_profiles.php');
		break;
	case 'delete_profiles':
		require_once ('godmode/massive/massive_delete_profiles.php');
		break;
	case 'delete_agents':
		require_once ('godmode/massive/massive_delete_agents.php');
		break;
	case 'edit_agents':
		require_once ('godmode/massive/massive_edit_agents.php');
		break;
	case 'delete_modules':
		require_once ('godmode/massive/massive_delete_modules.php');
		break;
	case 'edit_modules':
		require_once ('godmode/massive/massive_edit_modules.php');
		break;
	case 'copy_modules':
		require_once ('godmode/massive/massive_copy_modules.php');
		break;
	default:
		if (!enterprise_hook('massive_operations', array($option))) {
			require_once ('godmode/massive/massive_config.php');
		}
		break;
}
?>