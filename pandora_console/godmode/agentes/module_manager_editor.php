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

require_once ('include/functions_network_components.php');

if (is_ajax ()) {
	$get_network_component = (bool) get_parameter ('get_network_component');
	$snmp_walk = (bool) get_parameter ('snmp_walk');
	$get_module_component = (bool) get_parameter ('get_module_component');
	$get_module_components = (bool) get_parameter ('get_module_components');
	
	if ($get_module_component) {
		$id_component = (int) get_parameter ('id_module_component');
		
		$component = get_db_row ('tnetwork_component', 'id_nc', $id_component);
		
		echo json_encode ($component);
		return;
	}
	
	if ($get_module_components) {
		require_once ('include/functions_modules.php');
		$id_module_group = (int) get_parameter ('id_module_component_group');
		$id_module_component = (int) get_parameter ('id_module_component_type');
		
		$components = get_network_components ($id_module_component,
			array ('id_group' => $id_module_group,
				'order' => 'name ASC'),
			array ('id_nc', 'name'));
		
		echo json_encode ($components);
		return;
	}
	
	if ($snmp_walk) {
		$ip_target = (string) get_parameter ('ip_target');
		$snmp_community = (string) get_parameter ('snmp_community');
		
		snmp_set_quick_print (1);
		$snmpwalk = @snmprealwalk ($ip_target, $snmp_community, NULL);
		if ($snmpwalk === false) {
			echo json_encode ($snmpwalk);
			return;
		}
		
		$result = array ();
		foreach ($snmpwalk as $id => $value) {
			$value = substr ($id, 0, 35)." - ".substr ($value, 0, 20);
			$result[$id] = substr ($value, 0, 55);
		}
		asort ($result);
		echo json_encode ($result);
		return;
	}
	
	return;
}

require_once ("include/functions_exportserver.php");

// Using network component to fill some fields
if ($id_agent_module) {
	$module = get_agentmodule ($id_agent_module);
	$moduletype = $module['id_modulo'];
	$name = $module['nombre'];
	$description = $module['descripcion'];
	$id_module_group = $module['id_module_group'];
	$id_module_type = $module['id_tipo_modulo'];
	$max = $module['max'];
	$min = $module['min'];
	$interval = $module['module_interval'];
	if ($interval == 0) {
		$interval = get_agent_interval ($id_agente);
	}
	$tcp_port = $module['tcp_port'];
	$tcp_send = $module['tcp_send'];
	$tcp_rcv = $module['tcp_rcv'];
	$snmp_community = $module['snmp_community'];
	$snmp_oid = $module['snmp_oid'];
	$ip_target = $module['ip_target'];
	if (empty ($ip_target)) {
		$ip_target = get_agent_address ($id_agente);
	}
	$disabled = $module['disabled'];
	$id_export = $module['id_export'];
	$plugin_user = $module['plugin_user'];
	$plugin_pass = $module['plugin_pass'];
	$plugin_parameter = $module['plugin_parameter'];
	$id_plugin = $module['id_plugin'];
	$post_process = $module['post_process'];
	$prediction_module = $module['prediction_module'];
	$max_timeout = $module['max_timeout'];
	$custom_id = $module['custom_id'];
	$history_data = $module['history_data'];
	$min_warning = $module['min_warning'];
	$max_warning = $module['max_warning'];
	$min_critical = $module['min_critical'];
	$max_critical = $module['max_critical'];
	$ff_event = $module['min_ff_event'];
} else {
	if (!isset ($moduletype)) {
		$moduletype = (string) get_parameter ('moduletype');
		
		// Clean up specific network modules fields
		$name = '';
		$description = '';
		$id_module_group = 1;
		$id_module_type = 1;
		$post_process = '';
		$max_timeout = '';
		$min = '';
		$max = '';
		$interval = '';
		$prediction_module = '';
		$id_plugin = '';
		$id_export = '';
		$disabled = "0";
		$tcp_send = '';
		$tcp_rcv = '';
		$tcp_port = '';
	
		if ($moduletype == "wmiserver")
			$snmp_community = '';
		else
			$snmp_community = "public";
		$snmp_oid = '';
		$ip_target = get_agent_address ($id_agente);
		$plugin_user = '';
		$plugin_pass = '';
		$plugin_parameter = '';
		$custom_id = '';
		$history_data = 1;
		$min_warning = 0;
		$max_warning = 0;
		$min_critical = 0;
		$max_critical = 0;
		$ff_event = 0;
	}
}

switch ($moduletype) {
case "dataserver":
	$moduletype = 1;
case 1:
	/* Categories is an array containing the allowed module types
	 (generic_data, generic_string, etc) from ttipo_modulo (field categoria) */
	$categories = array (0, 1, 2, 6, 7, 8, -1);
	require ('module_manager_editor_common.php');
	require ('module_manager_editor_data.php');
	break;
case "networkserver":
	$moduletype = 2;
case 2:
	$categories = array (3, 4, 5);
	require ('module_manager_editor_common.php');
	require ('module_manager_editor_network.php');
	break;
case "pluginserver":
	$moduletype = 4;
case 4:
	$categories = array (0, 1, 2);
	require ('module_manager_editor_common.php');
	require ('module_manager_editor_plugin.php');
	break;
case "predictionserver":
	$moduletype = 5;
case 5:
	$categories = array (0, 1);
	require ('module_manager_editor_common.php');
	require ('module_manager_editor_prediction.php');
	break;
case "wmiserver":
	$moduletype = 6;
case 6:
	$categories = array (0, 1, 2);
	require ('module_manager_editor_common.php');
	require ('module_manager_editor_wmi.php');
	break;
/* WARNING: type 7 is reserved on enterprise */
default:
	if (enterprise_include ('godmode/agentes/module_manager_editor.php') === ENTERPRISE_NOT_HOOK) {
		echo '<h3 class="error">DEBUG: Invalid module type specified in '.__FILE__.':'.__LINE__.'</h3>';
		echo 'Most likely you have recently upgraded from an earlier version of Pandora and either <br />
			1) forgot to use the database converter<br />
			2) used a bad version of the database converter (see Bugreport #2124706 for the solution)<br />
			3) found a new bug - please report a way to duplicate this error';
		
		return;
	}
}

echo '<h3>'.__('Module assignment');
if (isset ($extra_title))
	echo ' &raquo; '.$extra_title;
echo '</h3>';

echo '<h3 id="message" class="error invisible"></h3>';

echo '<form method="post" id="module_form">';
print_table ($table_simple);

echo '<a href="#" id="show_advanced" onclick="$(\'div#advanced\').show ();$(this).remove (); return false">';
echo __('Advanced options').' &raquo;';
echo '</a>';

echo '<div id="advanced" style="display: none">';
print_table ($table_advanced);
echo '</div>';

// Submit
echo '<div class="action-buttons" style="width: '.$table_simple->width.'">';
if ($id_agent_module) {
	print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
	print_input_hidden ('update_module', 1);
	print_input_hidden ('id_agent_module', $id_agent_module);
} else {
	print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
	print_input_hidden ('id_module', $moduletype);
	print_input_hidden ('create_module', 1);
}
echo '</div>';
echo '</form>';

require_jquery_file ('ui');
require_jquery_file ('form');
require_jquery_file ('pandora');

require_javascript_file ('pandora_modules');
?>
<script language="javascript">
/* <![CDATA[ */
var no_name_lang = "<?php echo __('No module name provided') ?>";
var no_target_lang = "<?php echo __('No target IP provided') ?>";
var no_oid_lang = "<?php echo __('No SNMP OID provided') ?>";

$(document).ready (function () {
	configure_modules_form ();
});
/* ]]> */
</script>
