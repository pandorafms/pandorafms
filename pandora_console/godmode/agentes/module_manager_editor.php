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

require_once ('include/functions_network_components.php');
enterprise_include_once('include/functions_local_components.php');

if (is_ajax ()) {
	$get_network_component = (bool) get_parameter ('get_network_component');
	$snmp_walk = (bool) get_parameter ('snmp_walk');
	$get_module_component = (bool) get_parameter ('get_module_component');
	$get_module_components = (bool) get_parameter ('get_module_components');
	$get_module_local_components = (bool) get_parameter('get_module_local_components');
	$get_module_local_component = (bool) get_parameter('get_module_local_component');
	
	if ($get_module_component) {
		$id_component = (int) get_parameter ('id_module_component');
		
		$component = db_get_row ('tnetwork_component', 'id_nc', $id_component);
		
		echo json_encode ($component);
		return;
	}
	
	if ($get_module_components) {
		require_once ('include/functions_modules.php');
		$id_module_group = (int) get_parameter ('id_module_component_group');
		$id_module_component = (int) get_parameter ('id_module_component_type');
		
		$components = network_components_get_network_components ($id_module_component,
			array ('id_group' => $id_module_group,
				'order' => 'name ASC'),
			array ('id_nc', 'name'));
		
		echo json_encode ($components);
		return;
	}
	
	if ($get_module_local_components) {
		require_once ($config['homedir'].'/'.ENTERPRISE_DIR.'/include/functions_local_components.php');
		
		$id_module_group = (int) get_parameter ('id_module_component_group');
		$localComponents = local_components_get_local_components(array('id_network_component_group' => $id_module_group), array('id', 'name'));
		
		echo json_encode($localComponents);
		return;
	}
	
	if ($get_module_local_component) {
		$id_component = (int) get_parameter ('id_module_component');
		
		$component = db_get_row ('tlocal_component', 'id', $id_component);
		foreach ($component as $index => $element) {
			$component[$index] = html_entity_decode($element, ENT_QUOTES, "UTF-8");
		}
		
		$typeName = local_components_parse_module_extract_value('module_type',$component['data']);
		
		switch ($config["dbtype"]) {
			case "mysql":
						$component['type'] = db_get_value_sql('SELECT id_tipo FROM ttipo_modulo WHERE nombre LIKE "' . $typeName . '"');
				break;
			case "postgresql":
			case "oracle":
						$component['type'] = db_get_value_sql('SELECT id_tipo FROM ttipo_modulo WHERE nombre LIKE \'' . $typeName . '\'');
				break;
		}
		
		echo json_encode ($component);
		return;
	}
	
	if ($snmp_walk) {
		$ip_target = (string) get_parameter ('ip_target');
		$snmp_community = (string) get_parameter ('snmp_community');
		$snmp_version = get_parameter('snmp_version');
		$snmp3_auth_user = get_parameter('snmp3_auth_user');
		$snmp3_security_level = get_parameter('snmp3_security_level');
		$snmp3_auth_method = get_parameter('snmp3_auth_method');
		$snmp3_auth_pass = get_parameter('snmp3_auth_pass');
		$snmp3_privacy_method = get_parameter('snmp3_privacy_method');
		$snmp3_privacy_pass = get_parameter('snmp3_privacy_pass');
		$snmp_port = get_parameter('snmp_port');

		$snmpwalk = get_snmpwalk($ip_target, $snmp_version, $snmp_community, $snmp3_auth_user,
					$snmp3_security_level, $snmp3_auth_method, $snmp3_auth_pass,
					$snmp3_privacy_method, $snmp3_privacy_pass, 1, "", $snmp_port);

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
require_once($config['homedir'] . "/include/functions_modules.php");
require_once($config['homedir'] . "/include/functions_agents.php");

// Using network component to fill some fields
if ($id_agent_module) {
	$module = modules_get_agentmodule ($id_agent_module);
	$moduletype = $module['id_modulo'];
	$name = $module['nombre'];
	$description = $module['descripcion'];
	$id_module_group = $module['id_module_group'];
	$id_module_type = $module['id_tipo_modulo'];
	$max = $module['max'];
	$min = $module['min'];
	$interval = $module['module_interval'];
	if ($interval == 0) {
		$interval = agents_get_interval ($id_agente);
	}
	$unit = $module['unit'];
	$tcp_port = $module['tcp_port'];
	$tcp_send = $module['tcp_send'];
	$tcp_rcv = $module['tcp_rcv'];
	$snmp_community = $module['snmp_community'];
	$snmp_oid = $module['snmp_oid'];

	// New support for snmp v3
	$snmp_version = $module['tcp_send'];
	$snmp3_auth_user = $module["plugin_user"];
	$snmp3_auth_pass = $module["plugin_pass"];

	// Auth method could be MD5 or SHA
	$snmp3_auth_method = $module["plugin_parameter"];

	// Privacy method could be DES or AES
	$snmp3_privacy_method = $module["custom_string_1"];
	$snmp3_privacy_pass = $module["custom_string_2"];

	// Security level Could be noAuthNoPriv | authNoPriv | authPriv
	$snmp3_security_level = $module["custom_string_3"];
	
	$ip_target = $module['ip_target'];
	if (empty ($ip_target)) {
		$ip_target = agents_get_address ($id_agente);
	}
	$disabled = $module['disabled'];
	$id_export = $module['id_export'];
	$plugin_user = $module['plugin_user'];
	$plugin_pass = $module['plugin_pass'];
	$plugin_parameter = $module['plugin_parameter'];
	$id_plugin = $module['id_plugin'];
	$post_process = $module['post_process'];
	$prediction_module = $module['prediction_module'];
	$custom_integer_2 = $module ['custom_integer_2'];
	$max_timeout = $module['max_timeout'];
	$custom_id = $module['custom_id'];
	$history_data = $module['history_data'];
	$min_warning = $module['min_warning'];
	$max_warning = $module['max_warning'];
	$str_warning = $module['str_warning'];
	$min_critical = $module['min_critical'];
	$max_critical = $module['max_critical'];
	$str_critical = $module['str_critical'];
	$ff_event = $module['min_ff_event'];
	// Select tag info.
	$id_tag = tags_get_module_tags ($id_agent_module);
}
else {
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
		$unit = '';
		$prediction_module = '';
		$custom_integer_2 = 0;
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
		$ip_target = agents_get_address ($id_agente);
		$plugin_user = '';
		$plugin_pass = '';
		$plugin_parameter = '';
		$custom_id = '';
		$history_data = 1;
		$min_warning = 0;
		$max_warning = 0;
		$str_warning = '';
		$min_critical = 0;
		$max_critical = 0;
		$str_critical = '';
		$ff_event = 0;

		// New support for snmp v3
		$snmp_version = 1;
		$snmp3_auth_user = "";
		$snmp3_auth_pass = "";
		$snmp3_auth_method = "";
		$snmp3_privacy_method = "";
		$snmp3_privacy_pass = "";
		$snmp3_security_level = "";
	}
}

$is_function_policies = enterprise_include_once('include/functions_policies.php');

if($is_function_policies !== ENTERPRISE_NOT_HOOK) {
	$relink_policy = get_parameter('relink_policy', 0);
	$unlink_policy = get_parameter('unlink_policy', 0);

	if($relink_policy) {
		$policy_info = policies_info_module_policy($id_agent_module);
		$policy_id = $policy_info['id_policy'];

		if($relink_policy && policies_get_policy_queue_status ($policy_id) == STATUS_IN_QUEUE_APPLYING) {
			ui_print_error_message(__('This policy is applying and cannot be modified'));
		}
		else {
			$result = policies_relink_module($id_agent_module);
			ui_print_result_message($result, __('Module will be linked in the next application'));
			
			db_pandora_audit("Agent management", "Re-link module " . $id_agent_module);
		}
	}

	if($unlink_policy) {
		$result = policies_unlink_module($id_agent_module);
		ui_print_result_message($result, __('Module will be unlinked in the next application'));
		
		db_pandora_audit("Agent management", "Unlink module " . $id_agent_module);
	}

}
global $__code_from;
$__code_from = 'modules';
$remote_conf = false;
switch ($moduletype) {
	case "dataserver":
	case 1:
		$moduletype = 1;
		// Has remote configuration ?
		$agent_md5 = md5 (agents_get_name($id_agente), false);
		$remote_conf = file_exists ($config["remote_config"]."/md5/".$agent_md5.".md5");
		
		/* Categories is an array containing the allowed module types
		 (generic_data, generic_string, etc) from ttipo_modulo (field categoria) */
		$categories = array (0, 1, 2, 6, 7, 8, -1);
		require ('module_manager_editor_common.php');
		require ('module_manager_editor_data.php');
		if ($config['enterprise_installed'] && $remote_conf) {
			if($id_agent_module) {
				enterprise_include_once('include/functions_config_agents.php');
				$configuration_data = enterprise_hook('config_agents_get_module_from_conf',
					array($id_agente, io_safe_output(modules_get_agentmodule_name($id_agent_module))));
			}
			enterprise_include ('godmode/agentes/module_manager_editor_data.php');
		}
		break;
	case "networkserver":
	case 2:
		$moduletype = 2;
		$categories = array (3, 4, 5);
		require ('module_manager_editor_common.php');
		require ('module_manager_editor_network.php');
		break;
	case "pluginserver":
	case 4:
		$moduletype = 4;
		
		$categories = array (0, 1, 2);
		require ('module_manager_editor_common.php');
		require ('module_manager_editor_plugin.php');
		break;
	case "predictionserver":
	case 5:
		$moduletype = 5;
		
		$categories = array (0, 1);
		require ('module_manager_editor_common.php');
		require ('module_manager_editor_prediction.php');
		break;
	case "wmiserver":
	case 6:
		$moduletype = 6;
		
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
		break;
}


if($config['enterprise_installed'] && $id_agent_module) {
	if (policies_is_module_in_policy($id_agent_module)) {
		policies_add_policy_linkation($id_agent_module);
	}
}

echo '<h3 id="message" class="error invisible"></h3>';

echo '<form method="post" id="module_form">';
html_print_table ($table_simple);

echo '<b><a href="#" id="show_advanced" onclick="$(\'div#advanced\').show ();$(this).remove (); return false">';
echo __('Advanced options').'&nbsp;' .html_print_image ("images/down.png", true, array ("title" => __('Advanced options')));
echo '</a></b>';

echo '<div id="advanced" style="display: none">';
html_print_table ($table_advanced);
echo '</div>';

// Submit
echo '<div class="action-buttons" style="width: '.$table_simple->width.'">';
if ($id_agent_module) {
	html_print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
	html_print_input_hidden ('update_module', 1);
	html_print_input_hidden ('id_agent_module', $id_agent_module);
	html_print_input_hidden ('id_module_type', $id_module_type);
	
	if ($config['enterprise_installed'] && $remote_conf) {
		?>
		<script type="text/javascript">
		var check_remote_conf = true;
		</script>
		<?php
	}
}
else {
	html_print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
	html_print_input_hidden ('id_module', $moduletype);
	html_print_input_hidden ('create_module', 1);
	
	if ($config['enterprise_installed'] && $remote_conf) {
		?>
		<script type="text/javascript">
		var check_remote_conf = true;
		</script>
		<?php
	}
}
echo '</div>';
echo '</form>';

ui_require_jquery_file ('ui');
ui_require_jquery_file ('form');
ui_require_jquery_file ('pandora');
ui_require_jquery_file ('pandora.controls');

ui_require_javascript_file ('pandora_modules');
?>
<script language="javascript">
/* <![CDATA[ */
var no_name_lang = "<?php echo __('No module name provided') ?>";
var no_target_lang = "<?php echo __('No target IP provided') ?>";
var no_oid_lang = "<?php echo __('No SNMP OID provided') ?>";
var no_prediction_module_lang = "<?php echo __('No module to predict') ?>";
var no_plugin_lang = "<?php echo __('No plug-in provided') ?>";

$(document).ready (function () {
	configure_modules_form ();

	$("#module_form").submit(function() {
		if (check_remote_conf) {
			//Check the name
			name = $("#text-name").val();
			remote_config = $("#textarea_configuration_data").val();

			regexp_name = new RegExp('module_name\\s*' + name.replace(/([^0-9A-Za-z_])/g, "\\$1") +"\n");

			regexp_plugin = new RegExp('^module_plugin\\s*');

			if (remote_config == '' || remote_config.match(regexp_name) || remote_config.match(regexp_plugin) || $("#id_module_type").val()==100 || $("#hidden-id_module_type_hidden").val()==100) return true;
			else {
				alert("<?php echo __("Error, The field name and name in module_name in data configuration are different.");?>");
				return false;
			}
		}

		return true;
	});
	
	function checkKeepaliveModule() {
		// keepalive modules have id = 100
		if($("#id_module_type").val()==100 || $("#hidden-id_module_type_hidden").val()==100) {
				$("#simple-configuration_data").hide();
		}
		else {
				$("#simple-configuration_data").show();
		}

	}
	
	checkKeepaliveModule();
	
	$("#id_module_type").change (function () {
		checkKeepaliveModule();
	});
});
/* ]]> */
</script>
