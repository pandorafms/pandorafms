<?php 

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
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

if (is_ajax ()) {
	$get_os_icon = (bool) get_parameter ('get_os_icon');
	$select_timezone = get_parameter ('select_timezone', 0);
	
	if ($get_os_icon) {
		$id_os = (int) get_parameter ('id_os');
		ui_print_os_icon ($id_os, false);
		return;
	}
	
	if ($select_timezone) {
		$zone = get_parameter('zone');
		
		$timezones = timezone_identifiers_list();
		foreach ($timezones as $timezone_key => $timezone) {
			if (strpos($timezone, $zone) === false) {
				unset($timezones[$timezone_key]);
			}
		}
		
		echo json_encode($timezones);
	}
	return;
}


if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}
// Load enterprise extensions
enterprise_include_once ('include/functions_setup.php');
enterprise_include_once ('godmode/setup/setup.php');

/*
 NOTICE FOR DEVELOPERS:
 
 Update operation is done in config_process.php
 This is done in that way so the user can see the changes inmediatly.
 If you added a new token, please check config_update_config() in functions_config.php
 to add it there.
*/

// Gets section to jump to another section
$section = (string) get_parameter ("section", "general");

$buttons = array();

// Draws header
$buttons['general'] = array('active' => false, 
			'text' => '<a href="index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=general">' .
			html_print_image("images/god6.png", true, array ("title" => __('General'))) . '</a>');

if (enterprise_installed()) {
	$buttons = setup_enterprise_add_Tabs($buttons);
}

$buttons['auth'] = array('active' => false, 
			'text' => '<a href="index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=auth">' .
			html_print_image("images/books.png", true, array ("title" => __('Authentication'))) . '</a>');
			
$buttons['perf'] = array('active' => false, 
			'text' => '<a href="index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=perf">' .
			html_print_image("images/up.png", true, array ("title" => __('Performance'))) . '</a>');
			
$buttons['vis'] = array('active' => false, 
			'text' => '<a href="index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=vis">' .
			html_print_image("images/chart_curve.png", true, array ("title" => __('Visual styles'))) . '</a>');

if (check_acl ($config['id_user'], 0, "AW")) {
	if ($config['activate_netflow']) {
		$buttons['net'] = array('active' => false, 
				'text' => '<a href="index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=net">' .
				html_print_image("images/networkmap/so_cisco_new.png", true, array ("title" => __('Netflow'))) . '</a>');
	}
}

if (enterprise_installed()) {
	$subpage = setup_enterprise_add_subsection_main($section, $buttons);
}

switch ($section) {
	case 'general':
		$buttons['general']['active'] = true;
		$subpage = ' &raquo ' . __('General');
		break;
	case 'auth':
		$buttons['auth']['active'] = true;
		$subpage = ' &raquo ' . __('Authentication');
		break;
	case 'perf':
		$buttons['perf']['active'] = true;
		$subpage = ' &raquo ' . __('Performance');
		break;
	case 'vis':
		$buttons['vis']['active'] = true;
		$subpage = ' &raquo ' . __('Visual styles');
		break;
	case 'net':
		$buttons['net']['active'] = true;
		$subpage = ' &raquo ' . __('Netflow');
		break;
}

// Header
ui_print_page_header (__('Configuration') . $subpage, "", false, "", true, $buttons);

if (isset($config['error_config_update_config'])) {
	if ($config['error_config_update_config']['correct'] == false) {
		ui_print_error_message($config['error_config_update_config']['message']);
	}
	else {
		ui_print_success_message(__('Correct update the setup options'));
	}
	
	unset($config['error_config_update_config']);
}

switch ($section) {
	case "general":
			require_once($config['homedir'] . "/godmode/setup/setup_general.php");
			break;
	case "auth":
			require_once($config['homedir'] . "/godmode/setup/setup_auth.php");
			break;
	case "perf":
			require_once($config['homedir'] . "/godmode/setup/performance.php");
			break;
	case "net":
			require_once($config['homedir'] . "/godmode/setup/setup_netflow.php");
			break;
	case "vis":
			require_once($config['homedir'] . "/godmode/setup/setup_visuals.php");
			break;
	default:
			enterprise_hook('setup_enterprise_select_tab', array($section));
			break;
}

?>
