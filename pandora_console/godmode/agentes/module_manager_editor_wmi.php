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
enterprise_include_once('include/functions_policies.php');

$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';
$page = get_parameter('page', '');
if (strstr($page, "policy_modules") === false) {
	if ($config['enterprise_installed'])
		$disabledBecauseInPolicy = policies_is_module_in_policy($id_agent_module) && policies_is_module_linked($id_agent_module);
	else
		$disabledBecauseInPolicy = false;
	if ($disabledBecauseInPolicy)
		$disabledTextBecauseInPolicy = 'disabled = "disabled"';
}

$extra_title = __('WMI server module');

define ('ID_NETWORK_COMPONENT_TYPE', 6);

if (empty ($update_module_id)) {
	/* Function in module_manager_editor_common.php */
	add_component_selection (ID_NETWORK_COMPONENT_TYPE);
}
else {
	/* TODO: Print network component if available */
}

$data = array ();
$data[0] = __('Target IP');
$data[1] = html_print_input_text ('ip_target', $ip_target, '', 15, 60, true);
$data[2] = __('Namespace');
$data[2] .= ui_print_help_icon ('wminamespace', true);
$data[3] = html_print_input_text ('tcp_send', $tcp_send, '', 5, 20, true, $disabledBecauseInPolicy);

push_table_simple ($data, 'target_ip');

$data = array ();
$data[0] = __('Username');
$data[1] = html_print_input_text ('plugin_user', $plugin_user, '', 15, 60, true);
$data[2] = __('Password');
$data[3] = html_print_input_password ('plugin_pass', $plugin_pass, '', 15, 60, true);

push_table_simple ($data, 'user_pass');

$data = array ();
$data[0] = __('WMI query');
$data[0] .= ui_print_help_icon ('wmiquery', true);
$data[1] = html_print_input_text ('snmp_oid', $snmp_oid, '', 35, 255, true, $disabledBecauseInPolicy);
$table_simple->colspan['wmi_query'][1] = 3;

push_table_simple ($data, 'wmi_query');

$data = array ();
$data[0] = __('Key string');
$data[0] .= ui_print_help_icon ('wmikey', true);
$data[1] = html_print_input_text ('snmp_community', $snmp_community, '', 20, 60, true, $disabledBecauseInPolicy);
$data[2] = __('Field number');
$data[2] .= ui_print_help_icon ('wmifield', true);
$data[3] = html_print_input_text ('tcp_port', $tcp_port, '', 5, 15, true, $disabledBecauseInPolicy);

push_table_simple ($data, 'key_field');
?>
