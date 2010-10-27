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

if (! give_acl ($config['id_user'], 0, "AW")) {
	pandora_audit("ACL Violation",
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

$options_alerts = array('add_alerts' => __('Massive alerts addition'), 'delete_alerts' => __('Massive alerts deletion'), 
			'add_action_alerts' => __('Massive alert actions addition'), 'delete_action_alerts' => __('Massive alert actions deletion'),
			'enable_disable_alerts' => __('Massive alert enable/disable'), 'standby_alerts' => __('Massive alert setting standby'));
			
$options_agents = array('edit_agents' => __('Massive agents edition'), 'delete_agents' => __('Massive agents deletion'));

$options_users = array('add_profiles' => __('Massive profiles addition'), 'delete_profiles' => __('Massive profiles deletion'));

$options_modules = array('delete_modules' => __('Massive modules deletion'), 'edit_modules' => __('Massive modules edition'), 
				'copy_modules' => __('Massive modules copy'));

$options_policies = array();

$policies_options = enterprise_hook('massive_policies_options');

if($policies_options != -1) {
	$options_policies = array_merge($options_policies, $policies_options);
}

$modules_snmp_options = enterprise_hook('massive_modules_snmp_options');

if($modules_snmp_options != -1) {
	$options_modules = array_merge($options_modules, $modules_snmp_options);
}


if(in_array($option, array_keys($options_alerts))) {
	$tab = 'massive_alerts';
}elseif(in_array($option, array_keys($options_agents))) {
	$tab = 'massive_agents';
}elseif(in_array($option, array_keys($options_users))) {
	$tab = 'massive_users';
}elseif(in_array($option, array_keys($options_modules))) {
	$tab = 'massive_modules';
}elseif(in_array($option, array_keys($options_policies))) {
	$tab = 'massive_policies';
}else {
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
if($option == ''){
	$option = array_shift(array_keys($options));
}

$alertstab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_alerts">'
		. print_image ('images/bell.png', true, array ('title' => __('Alerts operations')))
		. '</a>', 'active' => $tab == 'massive_alerts');
		

$userstab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_users">'
		. print_image ('images/group.png', true, array ('title' => __('Users operations')))
		. '</a>', 'active' => $tab == 'massive_users');
		
$agentstab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_agents">'
		. print_image ('images/bricks.png', true, array ('title' => __('Agents operations')))
		. '</a>', 'active' => $tab == 'massive_agents');
			
$modulestab = array('text' => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_modules">'
		. print_image ('images/brick.png', true, array ('title' => __('Modules operations')))
		. '</a>', 'active' => $tab == 'massive_modules');
	
/* Collection */
$policiestab = enterprise_hook('massive_policies_tab');

if ($policiestab == -1)
	$policiestab = "";
		

$onheader = array('massive_agents' => $agentstab, 'massive_modules' => $modulestab, 
				'user_agents' => $userstab, 'massive_alerts' => $alertstab, 
				'policies' => $policiestab);

print_page_header (__('Massive operations'). ' &raquo; '. $options[$option], "images/sitemap_color.png", false, "", true, $onheader);

echo '<form method="post" id="form_options" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations">';			
echo '<table border="0"><tr><td>';
echo '<h3>'.__('Massive options').':</h3>';
echo '</td><td>';
print_select($options, 'option', $option, 'this.form.submit()', '', 0, false, false, false);
if($option == 'edit_agents' || $option == 'edit_modules') 
	echo '<a href="#" class="tip">&nbsp;<span>' . __("The blank fields will not be updated") . '</span></a>';
echo '</td></tr></table>';
echo '</form>';			

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
