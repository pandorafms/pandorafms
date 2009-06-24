<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

$extra_title = __('WMI server module');

define ('ID_NETWORK_COMPONENT_TYPE', 6);

if (empty ($update_module_id)) {
	/* Function in module_manager_editor_common.php */
	add_component_selection (ID_NETWORK_COMPONENT_TYPE);
} else {
	/* TODO: Print network component if available */
}

$data = array ();
$data[0] = __('Target IP');
$data[1] = print_input_text ('ip_target', $ip_target, '', 15, 60, true);
$data[2] = _('Namespace');
$data[2] .= print_help_icon ('wminamespace', true);
$data[3] = print_input_text ('tcp_send', $tcp_send, '', 5, 20, true);

push_table_simple ($data, 'target_ip');

$data = array ();
$data[0] = __('Username');
$data[1] = print_input_text ('plugin_user', $plugin_user, '', 15, 60, true);
$data[2] = _('Password');
$data[3] = print_input_password ('plugin_pass', $plugin_pass, '', 15, 60, true);

push_table_simple ($data, 'user_pass');

$data = array ();
$data[0] = __('WMI query');
$data[0] .= print_help_icon ('wmiquery', true);
$data[1] = print_input_text ('snmp_oid', $snmp_oid, '', 35, 255, true);
$table_simple->colspan['wmi_query'][1] = 3;

push_table_simple ($data, 'wmi_query');

$data = array ();
$data[0] = __('Key string');
$data[0] .= print_help_icon ('wmikey', true);
$data[1] = print_input_text ('snmp_community', $snmp_community, '', 20, 60, true);
$data[2] = __('Field number');
$data[2] .= print_help_icon ('wmifield', true);
$data[3] = print_input_text ('tcp_port', $tcp_port, '', 5, 15, true);

push_table_simple ($data, 'key_field');
?>
