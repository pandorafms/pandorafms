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
require_once($config['homedir'] . "/include/functions_snmp_browser.php");
ui_require_javascript_file ('pandora_snmp_browser');

// AJAX call
if (is_ajax()) {
	
	// Read the action to perform
	$action = (string) get_parameter ("action", "");
	$target_ip = (string) get_parameter ("target_ip", '');
	$community = (string) get_parameter ("community", '');

	// SNMP browser
	if ($action == "snmptree") {
		$starting_oid = (string) get_parameter ("starting_oid", '.');
		
		$snmp_tree = snmp_browser_get_tree ($target_ip, $community, $starting_oid);
		if (! is_array ($snmp_tree)) {
			echo $snmp_tree;
		} else {
			snmp_browser_print_tree ($snmp_tree);
		}
		return;
	}
	// SNMP get
	else if ($action == "snmpget") {
		$target_oid = htmlspecialchars_decode (get_parameter ("oid", ""));
		$custom_action = get_parameter ("custom_action", "");
		if ($custom_action != "") {
			$custom_action = urldecode (base64_decode ($custom_action));
		}

		$oid = snmp_browser_get_oid ($target_ip, $community, $target_oid);
		snmp_browser_print_oid ($oid, $custom_action);
		return;
	}
	
	return;
}

// Check login and ACLs
check_login ();
if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access SNMP Console");
	require ("general/noaccess.php");
	exit;
}

// Header
$url = 'index.php?sec=estado&sec2=operation/snmpconsole/snmp_browser&refr=' . $config["refr"] . '&pure=' . $config["pure"];
if ($config["pure"]) {
	// Windowed
	$link = '<a target="_top" href="'.$url.'&pure=0&refr=30">' . html_print_image("images/normalscreen.png", true, array("title" => __('Normal screen')))  . '</a>';
} else {
	// Fullscreen
	$link = '<a target="_top" href="'.$url.'&pure=1&refr=0">' . html_print_image("images/fullscreen.png", true, array("title" => __('Full screen'))) . '</a>';
}
ui_print_page_header (__("SNMP Browser"), "images/computer_error.png", false, "", false, $link);

// SNMP tree container
snmp_browser_print_container ();

?>
