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

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	return;
}

if (!$id && !isset($snmp_community)) {
	$snmp_community = "public";
}

$snmp_versions['1'] = 'v. 1';
$snmp_versions['2'] = 'v. 2';
$snmp_versions['2c'] = 'v. 2c';
$snmp_versions['3'] = 'v. 3';

$data = array ();
$data[0] = __('Port');
$data[1] = html_print_input_text ('tcp_port', $tcp_port, '', 5, 20, true);
$data[2] = __('SNMP version');
$data[3] = html_print_select ($snmp_versions, 'snmp_version', $snmp_version,
		'', '', '', true, false, false, '');

push_table_row ($data, 'snmp_port');

$data = array ();
$data[0] = __('SNMP OID');
$data[1] = html_print_input_text ('snmp_oid', $snmp_oid, '', 30, 400, true);
//$table->colspan['snmp_2'][1] = 3;
$data[2] = __('SNMP community');
$data[3] = html_print_input_text ('snmp_community', $snmp_community, '', 15, 60, true);

push_table_row ($data, 'snmp_2');


$data = array();
$data[0] = __('Auth user');
$data[1] = html_print_input_text ('snmp3_auth_user', $snmp3_auth_user, '', 15, 60, true);
$data[2] = __('Auth password');
$data[3] = html_print_input_text ('snmp3_auth_pass', $snmp3_auth_pass, '', 15, 60, true);
$data[3] .= html_print_input_hidden('active_snmp_v3', 0, true);
push_table_row($data, 'field_snmpv3_row1');

$data = array();
$data[0] = __('Privacy method');
$data[1] = html_print_select(array('DES' => __('DES'), 'AES' => __('AES')), 'snmp3_privacy_method', $snmp3_privacy_method, '', '', '', true);
$data[2] = __('Privacy pass');
$data[3] = html_print_input_text ('snmp3_privacy_pass', $snmp3_privacy_pass, '', 15, 60, true);
push_table_row($data, 'field_snmpv3_row2');

$data = array();
$data[0] = __('Auth method');
$data[1] = html_print_select(array('MD5' => __('MD5'), 'SHA' => __('SHA')), 'snmp3_auth_method', $snmp3_auth_method, '', '', '', true);
$data[2] = __('Security level');
$data[3] = html_print_select(array('noAuthNoPriv' => __('Not auth and not privacy method'),
	'authNoPriv' => __('Auth and not privacy method'), 'authPriv' => __('Auth and privacy method')), 'snmp3_security_level', $snmp3_security_level, '', '', '', true);
push_table_row($data, 'field_snmpv3_row3');

$data = array();
$data[0] = __('Post process') . ' ' . ui_print_help_icon ('postprocess', true );
$data[1] = html_print_input_text ('post_process', $post_process, '', 12, 25, true);
$data[2] = $data[3] = '';
push_table_row($data, 'field_process');



/* Advanced stuff */
$data = array ();
$data[0] = __('TCP send') . ' ' . ui_print_help_icon ("tcp_send", true);
$data[1] = html_print_textarea ('tcp_send', 2, 65, $tcp_send, '', true);
$table->colspan['tcp_send'][1] = 3;

push_table_row ($data, 'tcp_send');

$data = array();
$data[0] = __('TCP receive');
$data[1] = html_print_textarea ('tcp_rcv', 2, 65, $tcp_rcv, '', true);
$table->colspan['tcp_receive'][1] = 3;

push_table_row ($data, 'tcp_receive');
?>

