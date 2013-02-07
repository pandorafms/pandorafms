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

// AJAX call
if (is_ajax()) {
	
	// Read the action to perform
	$action = (string) get_parameter ("action", "");
	$target_ip = (string) get_parameter ("target_ip", '');
	$community = (string) get_parameter ("community", '');
	$starting_oid = (string) get_parameter ("starting_oid", '.');
	$target_oid = htmlspecialchars_decode (get_parameter ("oid", ""));
	
	// SNMP browser
	if ($action == "snmptree") {
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
		$oid = snmp_browser_get_oid ($target_ip, $community, $target_oid);
		snmp_browser_print_oid ($oid);
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

// Read parameters
//$target_ip = (string) get_parameter ("target_ip", '');
//$community = (string) get_parameter ("community", '');

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

// Target selection
$table->width = '90%';
$table->size = array ();
$table->data = array ();

// String search_string
$table->data[0][0] = '<strong>'.__('Target IP').'</strong>';
$table->data[0][1] = html_print_input_text ('target_ip', '', '', 50, 0, true);
$table->data[0][2] = '<strong>'.__('Community').'</strong>';
$table->data[0][3] = html_print_input_text ('community', '', '', 50, 0, true);
$table->data[1][0] = '<strong>'.__('Starting OID').'</strong>';
$table->data[1][1] = html_print_input_text ('starting_oid', '', '', 50, 0, true);

echo '<div>';
echo html_print_table($table, true);
echo '</div>';
echo '<div>';
echo html_print_button(__('Browse'), 'browse', false, 'snmpBrowse()', 'class="sub upd"', true);
echo '</div>';

// SNMP tree
$table->width = '100%';
$table->size = array ();
$table->data = array ();
$table->data[0][0] = '<div style="height: 500px; position: relative">';
$table->data[0][0] .= '<div id="spinner" style="display:none;">' . html_print_image ("images/spinner.gif", true, array ("style" => 'vertical-align: middle;'), false) . '</div>';
$table->data[0][0] .= '<div id="snmp_browser" style="height: 500px; overflow: auto;"></div>';
$table->data[0][0] .=   '<div id="snmp_data" style="width: 40%; position: absolute; top:0; right:20px"></div>';
$table->data[0][0] .= '</div>';
html_print_table($table, false);

?>

<script language="JavaScript" type="text/javascript">
<!--

// Load the SNMP tree via AJAX
function snmpBrowse () {

	// Empty the SNMP tree
	$("#snmp_browser").html('');

	// Show the spinner
	$("#spinner").css('display', '');

	// Read the target IP and community
	var target_ip = $('#text-target_ip').val();
	var community = $('#text-community').val();
	var starting_oid = $('#text-starting_oid').val();
	
	// Prepare the AJAX call
	var params = [
		"target_ip=" + target_ip,
		"community=" + community,
		"starting_oid=" + starting_oid,
		"action=" + "snmptree",
		"page=operation/snmpconsole/snmp_browser"
	];

	// Browse!
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
		async: true,
		timeout: 60000,
		success: function (data) {
			
			// Hide the spinner
			$("#spinner").css('display', 'none');
			
			// Load the SNMP tree
			$("#snmp_browser").html(data);
		}
	});
}

// Expand an SNMP tree node
function expandTreeNode(node) {

	var display = $("#" + node).css('display');
	var src = $("#anchor_" + node).children("img").attr('src');
	
	// Show the expanded or collapsed square
	if (display == "none") {
		src = src.replace("closed", "expanded");
	} else {
		src = src.replace("expanded", "closed");
	}
	$("#anchor_" + node).children("img").attr('src', src);
	
	// Hide or show leaves
	$("#" + node).toggle();
}

// Perform an SNMP get request via AJAX
function snmpGet (oid) {

	// Empty previous OID data
	$("#snmp_data").html()

	// Read the target IP and community
	var target_ip = $('#text-target_ip').val();
	var community = $('#text-community').val();
	
	// Prepare the AJAX call
	var params = [
		"target_ip=" + target_ip,
		"community=" + community,
		"oid=" + oid,
		"action=" + "snmpget",
		"page=operation/snmpconsole/snmp_browser"
	];

	// SNMP get!
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
		async: true,
		timeout: 60000,
		success: function (data) {
			$("#snmp_data").html(data);
		}
	});
	
	// Show the data div
	showOIDData();
}

// Show the div that displays OID data
function showOIDData() {
	$("#snmp_data").css('display', '');
}

// Hide the div that displays OID data
function hideOIDData() {
	$("#snmp_data").css('display', 'none');
}

//-->
</script>
