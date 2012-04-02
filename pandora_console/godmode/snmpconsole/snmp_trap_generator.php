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

// Check ACL
if (! check_acl ($config['id_user'], 0, "LW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access SNMP Filter Management");
	require ("general/noaccess.php");
	return;
}

include "include/functions_snmp.php";
$snmp_host_address = (string) get_parameter ("snmp_host_address", 'localhost');
$snmp_community = (string) get_parameter ("snmp_community", 'public');
$snmp_oid = (string) get_parameter ("snmp_oid", '');
$snmp_agent = (string) get_parameter ("snmp_agent", '');
$snmp_type = (int) get_parameter ("snmp_type", 0);
$snmp_value = (string) get_parameter ("snmp_value", '');
$generate_trap = (bool) get_parameter ("generate_trap", 0);

ui_print_page_header (__("SNMP Trap generator"), "images/computer_error.png", false, "", false);

if($generate_trap) {
	$result = true;
	if($snmp_host_address != '' && $snmp_community != '' && $snmp_oid != '' && $snmp_agent != '' && $snmp_value != '' && $snmp_type != -1) {
		snmp_generate_trap($snmp_host_address, $snmp_community, $snmp_oid, $snmp_agent, $snmp_value, $snmp_type);
	}
	else {
		$result = false;
	}
	
	ui_print_result_message ($result,
	__('Successfully generated'),
	__('Could not be generated'));
}

$traps_generator = '<form method="POST" action="index.php?sec=estado&sec2=godmode/snmpconsole/snmp_trap_generator">';
$table->width = '90%';
$table->size = array ();
$table->data = array ();

$table->data[0][0] = __('Host address');
$table->data[0][1] = html_print_input_text('snmp_host_address', $snmp_host_address, '', 50, 255, true);

$table->data[1][0] = __('Community');
$table->data[1][1] = html_print_input_text('snmp_community', $snmp_community, '', 50, 255, true);

$table->data[2][0] = __('OID');
$table->data[2][1] = html_print_input_text('snmp_oid', $snmp_oid, '', 50, 255, true);

$table->data[3][0] = __('SNMP Agent');
$table->data[3][1] = html_print_input_text('snmp_agent', $snmp_agent, '', 50, 255, true);

$table->data[4][0] = __('SNMP Type').' '.ui_print_help_icon ("snmp_trap_types", true);
$table->data[4][1] = html_print_input_text('snmp_type', $snmp_type, '', 50, 255, true);

$types = array(0 => 'Cold start (0)', 1 => 'Warm start (1)', 2 => 'Link down (2)', 3 => 'Link up (3)', 4 => 'Authentication failure (4)', 5 => 'EGP neighbor loss (5)', 6 => 'Enterprise (6)');
$table->data[4][1] = html_print_select($types, 'snmp_type', $snmp_type, '', __('Select'), -1, true, false, false);

$table->data[5][0] = __('Value');
$table->data[5][1] = html_print_input_text('snmp_value', $snmp_value, '', 50, 255, true);


$traps_generator .= html_print_table($table, true);
$traps_generator .= '<div style="width:'.$table->width.'; text-align: right;">'.html_print_submit_button(__('Generate trap'), 'btn_generate_trap', false, 'class="sub cog"', true).'</div>';
$traps_generator .= html_print_input_hidden('generate_trap', 1, true);

unset($table);
$traps_generator .= '</form>';

echo $traps_generator;
?>
