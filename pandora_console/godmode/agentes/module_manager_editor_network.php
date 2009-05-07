<?php

// Pandora FMS - the Flexible Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Copyright (c) 2008 Esteban Sanchez <estebans@artica.es>
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

define ('ID_NETWORK_COMPONENT_TYPE', 2);

if (empty ($update_module_id)) {
	/* Function in module_manager_editor_common.php */
	add_component_selection (ID_NETWORK_COMPONENT_TYPE);
} else {
	/* TODO: Print network component if available */
}

$extra_title = __('Network server module');

$data = array ();
$data[0] = __('Target IP');
$data[1] = print_input_text ('ip_target', $ip_target, '', 15, 60, true);
$data[2] = _('Port');
$data[3] = print_input_text ('tcp_port', $tcp_port, '', 5, 20, true);

push_table_simple ($data, 'target_ip');

$snmp_versions['1'] = 'v. 1';
$snmp_versions['2'] = 'v. 2';
$snmp_versions['2c'] = 'v. 2c';
$data = array ();
$data[0] = __('SNMP community');
$data[1] = print_input_text ('snmp_community', $snmp_community, '', 15, 60, true);

$data[2] = _('SNMP version');
$snmp_version = 1;
if ($id_module_type >= 15 && $id_module_type <= 18) {
	$data[3] = print_select ($snmp_versions, 'snmp_version', $snmp_version,
		'', '', '', true);
} else {
	$data[3] = print_select ($snmp_versions, 'snmp_version', 0, '', '',
		'', true);
}

push_table_simple ($data, 'snmp_1');

$data = array ();
$data[0] = __('SNMP OID');
$data[1] = '<span class="left"; style="width: 50%">';
$data[1] .= print_input_text ('snmp_oid', $snmp_oid, '', 30, 120, true);
$data[1] .= '<span class="invisible" id="oid">';
$data[1] .= print_select (array (), 'select_snmp_oid', $snmp_oid, '', '', 0, true);
$data[1] .= '<img src="images/edit.png" class="invisible clickable" id="edit_oid" />';
$data[1] .= '</span>';
$data[1] .= '<span id="no_snmp" class="error invisible">'.__('Unable to do SNMP walk').'</span>';
$data[1] .= '</span> <span class="right" style="width: 50%; text-align: right"><span id="oid_loading" class="invisible">';
$data[1] .= '<img src="images/spinner.gif" />';
$data[1] .= '</span>';
$data[1] .= print_button (__('SNMP walk'), 'snmp_walk', $ip_target == '', '',
	'class="sub next"', true);
$data[1] .= print_help_icon ('snmpwalk', true);
$data[1] .= '</span>';
$table_simple->colspan['snmp_2'][1] = 3;

push_table_simple ($data, 'snmp_2');

/* Advanced stuff */
$data = array ();
$data[0] = __('TCP send').' '.print_help_icon ("tcp_send", true);
$data[1] = print_textarea ('tcp_send', 2, 65, $tcp_send, '', true);
$table_advanced->colspan['tcp_send'][1] = 3;

push_table_advanced ($data, 'tcp_send');

$data[0] = __('TCP receive');
$data[1] = print_textarea ('tcp_rcv', 2, 65, $tcp_rcv, '', true);
$table_advanced->colspan['tcp_receive'][1] = 3;

push_table_advanced ($data, 'tcp_receive');

if ($id_module_type >= 15 && $id_module_type <= 18) {
	/* SNMP */
	$table_advanced->rowstyle['tcp_send'] = 'display: none';
	$table_advanced->rowstyle['tcp_receive'] = 'display: none';
} elseif ($id_module_type >= 8 && $id_module_type <= 11) {
	/* TCP or ICMP */
	$table_simple->rowstyle['snmp_1'] = 'display: none';
	$table_simple->rowstyle['snmp_2'] = 'display: none';
} elseif (empty ($update_module_id)) {
	$table_advanced->rowstyle['tcp_send'] = 'display: none';
	$table_advanced->rowstyle['tcp_receive'] = 'display: none';
	$table_simple->rowstyle['snmp_1'] = 'display: none';
	$table_simple->rowstyle['snmp_2'] = 'display: none';
}
?>
