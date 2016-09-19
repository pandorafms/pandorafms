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

$options_alerts = array(
	'add_alerts' => __('Bulk alert add'),
	'delete_alerts' => __('Bulk alert delete'), 
	'add_action_alerts' => __('Bulk alert actions add'),
	'delete_action_alerts' => __('Bulk alert actions delete'),
	'enable_disable_alerts' => __('Bulk alert enable/disable'),
	'standby_alerts' => __('Bulk alert setting standby'));

$options_agents = array(
	'edit_agents' => __('Bulk agent edit'),
	'delete_agents' => __('Bulk agent delete'));

if (check_acl ($config['id_user'], 0, "PM")) {
	$options_users = array(
		'add_profiles' => __('Bulk profile add'),
		'delete_profiles' => __('Bulk profile delete'));
}
else {
	$options_users = array();
}

$options_modules = array(
	'delete_modules' => __('Bulk module delete'),
	'edit_modules' => __('Bulk module edit'), 
	'copy_modules' => __('Bulk module copy'));

$options_plugins = array(
		'edit_plugins' => __('Bulk plugin edit')
	);

if (! check_acl ($config['id_user'], 0, "PM")) {
	unset($options_modules['edit_modules']);
}

$options_policies = array();
$policies_options = enterprise_hook('massive_policies_options');

if ($policies_options != ENTERPRISE_NOT_HOOK) {
	$options_policies =
		array_merge($options_policies, $policies_options);
}

$options_snmp = array();
$snmp_options = enterprise_hook('massive_snmp_options');

if ($snmp_options != ENTERPRISE_NOT_HOOK) {
	$options_snmp =
		array_merge($options_snmp, $snmp_options);
}

$options_satellite = array();
$satellite_options = enterprise_hook('massive_satellite_options');

if ($satellite_options != ENTERPRISE_NOT_HOOK) {
	$options_satellite =
		array_merge($options_satellite, $satellite_options);
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
elseif (in_array($option, array_keys($options_snmp))) {
	$tab = 'massive_snmp';
}
elseif (in_array($option, array_keys($options_satellite))) {
	$tab = 'massive_satellite';
}
elseif (in_array($option, array_keys($options_plugins))) {
	$tab = 'massive_plugins';
}
else {
	$option = '';
}

switch ($tab) {
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
	case 'massive_snmp':
		$options = $options_snmp;
		break;
	case 'massive_satellite':
		$options = $options_satellite;
		break;
	case 'massive_plugins':
		$options = $options_plugins;
		break;
}

// Set the default option of the category
if ($option == '') {
	$option = array_shift(array_keys($options));
}

$alertstab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_alerts">'
	. html_print_image ('images/op_alerts.png', true,
		array ('title' => __('Alerts operations')))
	. '</a>', 'active' => $tab == 'massive_alerts');

$userstab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_users">'
	. html_print_image ('images/op_workspace.png', true,
		array ('title' => __('Users operations')))
	. '</a>', 'active' => $tab == 'massive_users');

$agentstab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_agents">'
	. html_print_image ('images/bricks.png', true,
		array ('title' => __('Agents operations')))
	. '</a>', 'active' => $tab == 'massive_agents');

$modulestab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_modules">'
	. html_print_image ('images/brick.png', true,
		array ('title' => __('Modules operations')))
	. '</a>', 'active' => $tab == 'massive_modules');

$pluginstab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_plugins">'
	. html_print_image ('images/plugin.png', true,
		array ('title' => __('Plugins operations')))
	. '</a>', 'active' => $tab == 'massive_plugins');

$policiestab = enterprise_hook('massive_policies_tab');

if ($policiestab == ENTERPRISE_NOT_HOOK)
	$policiestab = "";

$snmptab = enterprise_hook('massive_snmp_tab');

if ($snmptab == ENTERPRISE_NOT_HOOK)
	$snmptab = "";
	
$satellitetab = enterprise_hook('massive_satellite_tab');

if ($satellitetab == ENTERPRISE_NOT_HOOK)
	$satellitetab = "";


$onheader = array();
$onheader['massive_agents'] = $agentstab;
$onheader['massive_modules'] = $modulestab;
$onheader['massive_plugins'] = $pluginstab;
if (check_acl ($config['id_user'], 0, "PM")) {
	$onheader['user_agents'] = $userstab;
}
$onheader['massive_alerts'] = $alertstab;
$onheader['policies'] = $policiestab;
$onheader['snmp'] = $snmptab;
$onheader['satellite'] = $satellitetab;

ui_print_page_header(
	__('Massive operations') . ' &raquo; '. $options[$option],
	"images/gm_massive_operations.png", false, "", true, $onheader,true, "massive");

// Checks if the PHP configuration is correctly
if ((get_cfg_var("max_execution_time") != 0)
	or (get_cfg_var("max_input_time") != -1)) {
	
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
if ($option == 'edit_agents' || $option == 'edit_modules') 
	ui_print_help_tip(__("The blank fields will not be updated"));
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
	case 'edit_plugins':
		require_once ('godmode/massive/massive_edit_plugins.php');
		break;
	default:
		if (!enterprise_hook('massive_operations', array($option))) {
			require_once ('godmode/massive/massive_config.php');
		}
		break;
}
?>
