<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

//Remember the hard-coded values
/*
-- id_modulo now uses tmodule 
-- ---------------------------
-- 1 - Data server modules (agent related modules)
-- 2 - Network server modules
-- 4 - Plugin server
-- 5 - Predictive server
-- 6 - WMI server
-- 7 - WEB Server (enteprise)

In the xml is the tag "module_source"
*/

function resource_registration_extension_main() {
	global $config;
	
	if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
		pandora_audit("ACL Violation", "Trying to access Setup Management");
		require ("general/noaccess.php");
		return;
	}
	
	require_once($config['homedir'] . '/include/functions_network_components.php');
	require_once($config['homedir'] . '/include/functions_db.php');
	enterprise_include_once('include/functions_local_components.php');
    
	print_page_header (__('Resource registration'), "images/extensions.png", false, "", true, "" );
	
	if (!extension_loaded("libxml")) {
		print_error_message(_("Error, please install the PHP libXML in the system."));
		
		return;
	}
	
	echo "<div class=notify>";
	printf(__("This extension makes registration of resource template more easy. " .
		"Here you can upload a resource template in Pandora FMS 3.x format (.ptr). " .
		"Please refer to documentation on how to obtain and use Pandora FMS resources. " .
		"<br><br>You can get more resurces in our <a href='%s'>Public Resource Library</a>") ,
		"http://pandorafms.org/index.php?sec=community&sec2=repository&lng=en");
	echo "</div>";
	
	echo "<br /><br />";
	
	// Upload form
	echo "<form name='submit_plugin' method='post' enctype='multipart/form-data'>";
	echo '<table class="databox" id="table1" width="50%" border="0" cellpadding="4" cellspacing="4">';
	echo "<tr><td class='datos'><input type='file' name='resource_upload' />";
	echo "<td class='datos'><input type='submit' class='sub next' value='".__('Upload')."' />";
	echo "</form></table>";
	
	if (!isset ($_FILES['resource_upload']['tmp_name'])) {
		return;
	}
	$xml = simplexml_load_file($_FILES['resource_upload']['tmp_name']);
	
	//Extract components
	$components = array();
	foreach ($xml->xpath('//component') as $componentElement) {
		$name = safe_input((string)$componentElement->name);
		$id_os = (int)$componentElement->id_os;
		$os_version = safe_input((string)$componentElement->os_version);
		$data = safe_input((string)$componentElement->data);
		$type = (int)$componentElement->type;
		$group = (int)$componentElement->group;
		$description = safe_input((string)$componentElement->description);
		$module_interval = (int)$componentElement->module_interval;
		$max = (float)$componentElement->max;
		$min = (float)$componentElement->min;
		$tcp_send = safe_input((string)$componentElement->tcp_send);
		$tcp_rcv_text = safe_input((string)$componentElement->tcp_rcv_text);
		$tcp_port = (int)$componentElement->tcp_port;
		$snmp_oid = safe_input((string)$componentElement->snmp_oid);
		$snmp_community = safe_input((string)$componentElement->snmp_community);
		$id_module_group = (int)$componentElement->id_module_group;
		$module_source = (int)$componentElement->module_source;
		$plugin = (int)$componentElement->plugin;
		$plugin_username = safe_input((string)$componentElement->plugin_username);
		$plugin_password = safe_input((string)$componentElement->plugin_password);
		$plugin_parameters = safe_input((string)$componentElement->plugin_parameters);
		$max_timeout = (int)$componentElement->max_timeout;
		$historical_data = (int)$componentElement->historical_data;
		$min_war = (float)$componentElement->min_war;
		$max_war = (float)$componentElement->max_war;
		$min_cri = (float)$componentElement->min_cri;
		$max_cri = (float)$componentElement->max_cri;
		$ff_treshold = (int)$componentElement->ff_treshold;
		$snmp_version = (int)$componentElement->snmp_version;
		$auth_user = safe_input((string)$componentElement->auth_user);
		$auth_password = safe_input((string)$componentElement->auth_password);
		$auth_method = safe_input((string)$componentElement->auth_method);
		$privacy_method = safe_input((string)$componentElement->privacy_method);
		$privacy_pass =  safe_input((string)$componentElement->privacy_pass);
		$security_level =  safe_input((string)$componentElement->security_level);
		$wmi_query = safe_input((string)$componentElement->wmi_query);
		$key_string = safe_input((string)$componentElement->key_string);
		$field_number = (int)$componentElement->field_number;
		$namespace = safe_input((string)$componentElement->namespace);
		$wmi_user = safe_input((string)$componentElement->wmi_user);
		$wmi_password = safe_input((string)$componentElement->wmi_password);
		$post_process = safe_input((float)$componentElement->post_process);
		
		$idComponent = false;
		switch ((int)$componentElement->module_source) {
			case 1: //Local component
				$values = array('description' => $description,
					'id_network_component_group' => $group,
					'os_version' => $os_version);
				$return = enterprise_hook('create_local_component', array($name, $data, $id_os, $values));
				if ($return !== ENTERPRISE_NOT_HOOK) {
					$idComponent = $return;
				}
				break;
			case 2: //Network component
				
				//for modules
				//15 = remote_snmp, 16 = remote_snmp_inc,
				//17 = remote_snmp_string, 18 = remote_snmp_proc
				$custom_string_1 = '';
				$custom_string_2 = '';
				$custom_string_3 = '';
				if ($type >= 15 && $type <= 18) {
					// New support for snmp v3
					$tcp_send = $snmp_version;
					$plugin_user = $auth_user;
					$plugin_pass = $auth_password;
					$plugin_parameter = $auth_method;
					$custom_string_1 = $privacy_method;
					$custom_string_2 = $privacy_pass;
					$custom_string_3 = $security_level;
				}
				
				$idComponent = create_network_component ($name,
					$type, $group, 
					array ('description' => $description,
						'module_interval' => $module_interval,
						'max' => $max,
						'min' => $min,
						'tcp_send' => $tcp_send,
						'tcp_rcv' => $tcp_rcv_text,
						'tcp_port' => $tcp_port,
						'snmp_oid' => $snmp_oid,
						'snmp_community' => $snmp_community,
						'id_module_group' => $id_module_group,
						'id_modulo' => $module_source,
						'id_plugin' => $plugin,
						'plugin_user' => $plugin_username,
						'plugin_pass' => $plugin_password,
						'plugin_parameter' => $plugin_parameters,
						'max_timeout' => $max_timeout,
						'history_data' => $historical_data,
						'min_warning' => $min_war,
						'max_warning' => $max_war,
						'min_critical' => $min_cri,
						'max_critical' => $max_cri,
						'min_ff_event' => $ff_treshold,
						'custom_string_1' => $custom_string_1,
						'custom_string_2' => $custom_string_2,
						'custom_string_3' => $custom_string_3,
						'post_process' => $post_process));
				if ((bool)$idComponent) {
					$components[] = $idComponent; 
				}
				break;
			case 4: //Plugin component
				$idComponent = create_network_component ($name,
					$type, $group, 
					array ('description' => $description,
						'module_interval' => $module_interval,
						'max' => $max,
						'min' => $min,
						'tcp_send' => $tcp_send,
						'tcp_rcv' => $tcp_rcv_text,
						'tcp_port' => $tcp_port,
						'snmp_oid' => $snmp_oid,
						'snmp_community' => $snmp_community,
						'id_module_group' => $id_module_group,
						'id_modulo' => $module_source,
						'id_plugin' => $plugin,
						'plugin_user' => $plugin_username,
						'plugin_pass' => $plugin_password,
						'plugin_parameter' => $plugin_parameters,
						'max_timeout' => $max_timeout,
						'history_data' => $historical_data,
						'min_warning' => $min_war,
						'max_warning' => $max_war,
						'min_critical' => $min_cri,
						'max_critical' => $max_cri,
						'min_ff_event' => $ff_treshold,
						'custom_string_1' => $custom_string_1,
						'custom_string_2' => $custom_string_2,
						'custom_string_3' => $custom_string_3,
						'post_process' => $post_process));
				if ((bool)$idComponent) {
					$components[] = $idComponent; 
				}
				break;
			case 5: //Prediction component
				break;
			case 6: //WMI component
				$idComponent = create_network_component ($name,
					$type, $group, 
					array ('description' => $description,
						'module_interval' => $module_interval,
						'max' => $max,
						'min' => $min,
						'tcp_send' => $namespace, //work around
						'tcp_rcv' => $tcp_rcv_text,
						'tcp_port' => $field_number, //work around
						'snmp_oid' => $wmi_query, //work around
						'snmp_community' => $key_string, //work around
						'id_module_group' => $id_module_group,
						'id_modulo' => $module_source,
						'id_plugin' => $plugin,
						'plugin_user' => $wmi_user, //work around
						'plugin_pass' => $wmi_password, //work around
						'plugin_parameter' => $plugin_parameters,
						'max_timeout' => $max_timeout,
						'history_data' => $historical_data,
						'min_warning' => $min_war,
						'max_warning' => $max_war,
						'min_critical' => $min_cri,
						'max_critical' => $max_cri,
						'min_ff_event' => $ff_treshold,
						'custom_string_1' => $custom_string_1,
						'custom_string_2' => $custom_string_2,
						'custom_string_3' => $custom_string_3,
						'post_process' => $post_process));
				if ((bool)$idComponent) {
					$components[] = $idComponent; 
				}
				break;
			case 7: //Web component
				break;
		}
		
		print_result_message((bool)$idComponent, sprintf(__("Success create '%s' component."), $name),
			sprintf(__("Error create '%s' component."), $name));
	}
	
	//Extract the template
	
	$templateElement = $xml->xpath('//template');
	if (!empty($templateElement)) {
		$templateElement = $templateElement[0];
		
		$templateName = (string)$templateElement->name;
		$templateDescription = (string)$templateElement->description;
		
		$idTemplate = process_sql_insert('tnetwork_profile', array('name' => $templateName, 'description' => $templateDescription));
		
		$result = false;
		if ((bool)$idTemplate) {
			foreach ($components as $idComponent) {
				process_sql_insert("tnetwork_profile_component", array('id_nc' => $idComponent, 'id_np' => $idTemplate));
			}
		}
	}
}

add_godmode_menu_option (__('Resource registration'), 'PM','gservers','');
add_extension_godmode_function('resource_registration_extension_main');
?>