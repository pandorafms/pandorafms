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
		$disabledBecauseInPolicy = isModuleInPolicy($id_agent_module) && isModuleLinked($id_agent_module);
	else
		$disabledBecauseInPolicy = false;
	if ($disabledBecauseInPolicy)
		$disabledTextBecauseInPolicy = 'disabled = "disabled"';
}

define ('ID_NETWORK_COMPONENT_TYPE', 4);

if (empty ($update_module_id)) {
	/* Function in module_manager_editor_common.php */
	add_component_selection (ID_NETWORK_COMPONENT_TYPE);
} else {
	/* TODO: Print network component if available */
}

$extra_title = __('Plugin server module');

$data = array ();
$data[0] = __('Plugin');
$data[1] = print_select_from_sql ('SELECT id, name FROM tplugin ORDER BY name',
	'id_plugin', $id_plugin, 'changePluginSelect();', __('None'), 0, true, false, false, $disabledBecauseInPolicy);
$table_simple->colspan['plugin_1'][2] = 2;
$data[2] = '<span style="font-weight: normal;" id="plugin_description"></span>';

push_table_simple ($data, 'plugin_1');

$data = array ();
$data[0] = __('Target IP');
$data[1] = print_input_text ('ip_target', $ip_target, '', 15, 60, true);
$data[2] = __('Port');
$data[3] = print_input_text ('tcp_port', $tcp_port, '', 5, 20, true);

push_table_simple ($data, 'target_ip');

$data = array ();
$data[0] = __('Username');
$data[1] = print_input_text ('plugin_user', $plugin_user, '', 15, 60, true);
$data[2] = __('Password');
$data[3] = print_input_password ('plugin_pass', $plugin_pass, '', 15, 60, true);

push_table_simple ($data, 'plugin_2');

$data = array ();
$data[0] = __('Plugin parameters');
$data[0] .= print_help_icon ('plugin_parameters', true);
$data[1] = print_input_text ('plugin_parameter', $plugin_parameter, '', 255, '', true, $disabledBecauseInPolicy);
$table_simple->colspan['plugin_3'][1] = 3;

push_table_simple ($data, 'plugin_3');
?>
<script type="text/javascript">
function changePluginSelect() {
//	alert($("#id_plugin").val());
	jQuery.post ("ajax.php",
		{"page" : "godmode/servers/plugin",
		"get_plugin_description" : 1,
		"id_plugin" : $("#id_plugin").val()
		},
		function (data, status) {
			$("#plugin_description").html(data);
		}
	);
}
</script>
