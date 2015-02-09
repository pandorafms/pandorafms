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


// Load global variables
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	return;
}

include_once($config['homedir'] . "/include/functions_modules.php");

$data = array ();
$data[0] = __('WMI query');
$data[1] = html_print_input_text ('snmp_oid', $snmp_oid, '', 25, 255, true);
$data[2] = __('Key string') . ' ' . ui_print_help_icon ('wmikey', true, ui_get_full_url(false, false, false, false));
$data[3] = html_print_input_text ('snmp_community', $snmp_community, '', 25, 255, true);

push_table_row ($data, 'wmi_1');

$data = array ();
$data[0] = __('Field number') . ' ' . ui_print_help_icon ('wmifield', true, ui_get_full_url(false, false, false, false));
$data[1] = html_print_input_text ('tcp_port', $tcp_port, '', 5, 25, true);
$data[2] = __('Namespace') . ' ' . ui_print_help_icon ('wminamespace', true, ui_get_full_url(false, false, false, false));
$data[3] = html_print_input_text ('tcp_send', $tcp_send, '', 25, 255, true);

push_table_row ($data, 'wmi_2');

$data = array ();
$data[0] = __('Username');
$data[1] = html_print_input_text ('plugin_user', $plugin_user, '', 15, 255, true);
$data[2] = __('Password');
$data[3] = html_print_input_password ('plugin_pass', $plugin_pass, '', 25, 255, true);

push_table_row ($data, 'wmi_3');

$data = array();
$data[0] = __('Post process') . ' ' . ui_print_help_icon ('postprocess', true, ui_get_full_url(false, false, false, false));
$data[1] = html_print_extended_select_for_post_process('post_process',
	$post_process, '', __('Empty'), '0', false, true, false, true);
$data[2] = $data[3] = '';
push_table_row($data, 'field_process');
?>
