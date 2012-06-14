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

enterprise_include ('godmode/agentes/configurar_agente.php');
enterprise_include ('include/functions_policies.php');
enterprise_include ('include/functions_modules.php');
include_once($config['homedir'] . "/include/functions_agents.php");

check_login ();

//See if id_agente is set (either POST or GET, otherwise -1
$id_agente = (int) get_parameter ("id_agente");
$group = 0;
if ($id_agente)
	$group = agents_get_agent_group ($id_agente);

$is_extra = enterprise_hook('policies_is_agent_extra_policy', array($id_agente));

if($is_extra === ENTERPRISE_NOT_HOOK) {
	$is_extra = false;
}

if (! check_acl ($config["id_user"], $group, "AW", $id_agente) && !$is_extra) {
	db_pandora_audit("ACL Violation",
		"Trying to access agent manager");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_modules.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_reporting.php');

// Get passed variables
$tab = get_parameter ('tab', 'main');
$alerttype = get_parameter ('alerttype');
$id_agent_module = (int) get_parameter ('id_agent_module');

// Init vars
$descripcion = "";
$comentarios = "";
$campo_1 = "";
$campo_2 = "";
$campo_3 = "";
$maximo = 0;
$minimo = 0;
$nombre_agente = "";
$direccion_agente = get_parameter ('direccion', '');
$intervalo = 300;
$id_server = "";
$max_alerts = 0;
$modo = 1;
$update_module = 0;
$modulo_id_agente = "";
$modulo_id_tipo_modulo = "";
$modulo_nombre = "";
$modulo_descripcion = "";
$alerta_id_aam = "";
$alerta_campo1 = "";
$alerta_campo2 = "";
$alerta_campo3 = "";
$alerta_dis_max = "";
$alerta_dis_min = "";
$alerta_min_alerts = 0;
$alerta_max_alerts = 1;
$alerta_time_threshold = "";
$alerta_descripcion = "";
$disabled = "";
$id_parent = 0;
$modulo_max = "";
$modulo_min = "";
$module_interval = "";
$tcp_port = "";
$tcp_send = "";
$tcp_rcv = "";
$snmp_oid = "";
$ip_target = "";
$snmp_community = "";
$combo_snmp_oid = "";
$agent_created_ok = 0;
$create_agent = 0;
$alert_text = "";
$time_from= "";
$time_to = "";
$alerta_campo2_rec = "";
$alerta_campo3_rec = "";
$alert_id_agent = "";
$alert_d1 = 1;
$alert_d2 = 1;
$alert_d3 = 1;
$alert_d4 = 1;
$alert_d5 = 1;
$alert_d6 = 1;
$alert_d7 = 1;
$alert_recovery = 0;
$alert_priority = 0;
$server_name = '';
$grupo = 0;
$id_os = 9; // Windows
$custom_id = "";
$cascade_protection = 0;
$icon_path = '';
$update_gis_data = 0;
$unit = "";
$id_tag = array();
$tab_description = '';

$create_agent = (bool) get_parameter ('create_agent');

// Create agent
if ($create_agent) {
	$nombre_agente = (string) get_parameter_post ("agente",'');
	$direccion_agente = (string) get_parameter_post ("direccion",'');
	$grupo = (int) get_parameter_post ("grupo");
	$intervalo = (string) get_parameter_post ("intervalo", 300);
	$comentarios = (string) get_parameter_post ("comentarios", '');
	$modo = (int) get_parameter_post ("modo");
	$id_parent = (string) get_parameter_post ("id_parent",'');
	$id_parent = (int) agents_get_agent_id ($id_parent);
	$server_name = (string) get_parameter_post ("server_name");
	$id_os = (int) get_parameter_post ("id_os");
	$disabled = (int) get_parameter_post ("disabled");
	$custom_id = (string) get_parameter_post ("custom_id",'');
	$cascade_protection = (int) get_parameter_post ("cascade_protection", 0);
	$icon_path = (string) get_parameter_post ("icon_path",'');
	$update_gis_data = (int) get_parameter_post("update_gis_data", 0);


	$fields = db_get_all_fields_in_table('tagent_custom_fields');
	
	if($fields === false) $fields = array();
	
	$field_values = array();
	
	foreach($fields as $field) {
		$field_values[$field['id_field']] = (string) get_parameter_post ('customvalue_'.$field['id_field'], '');
	}

	// Check if agent exists (BUG WC-50518-2)
	if ($nombre_agente == "") {
		$agent_creation_error = __('No agent name specified');
		$agent_created_ok = 0;
	}
	elseif (agents_get_agent_id ($nombre_agente)) {
		$agent_creation_error = __('There is already an agent in the database with this name');
		$agent_created_ok = 0;
	}
	else {
		$id_agente = db_process_sql_insert ('tagente', 
			array ('nombre' => $nombre_agente,
				'direccion' => $direccion_agente,
				'id_grupo' => $grupo, 'intervalo' => $intervalo,
				'comentarios' => $comentarios, 'modo' => $modo,
				'id_os' => $id_os, 'disabled' => $disabled,
				'cascade_protection' => $cascade_protection,
				'server_name' => $server_name,
				'id_parent' => $id_parent, 'custom_id' => $custom_id,
				'icon_path' => $icon_path,
				'update_gis_data' => $update_gis_data));
		enterprise_hook ('update_agent', array ($id_agente));
		if ($id_agente !== false) {
			// Create custom fields for this agent
			foreach($field_values as $key => $value) {
				db_process_sql_insert ('tagent_custom_data',
				 array('id_field' => $key,'id_agent' => $id_agente, 'description' => $value));
			}
			// Create address for this agent in taddress
			agents_add_address ($id_agente, $direccion_agente);
			
			$agent_created_ok = true;

			$info = 'Name: ' . $nombre_agente . ' IP: ' . $direccion_agente .
				' Group: ' . $grupo . ' Interval: ' . $intervalo .
				' Comments: ' . $comentarios . ' Mode: ' . $modo .
				' ID_parent: ' . $id_parent . ' Server: ' . $server_name .
				' ID os: ' . $id_os . ' Disabled: ' . $disabled .
				' Custom ID: ' . $custom_id . ' Cascade protection: '  . $cascade_protection . 
				' Icon path: ' . $icon_path . ' Update GIS data: ' . $update_gis_data;
			
			db_pandora_audit("Agent management",
				"Created agent $nombre_agente", false, false, $info);
		}
		else {
			$id_agente = 0;
			$agent_creation_error = __('Could not be created');
		}
	}
}

// Show tabs
$img_style = array ("class" => "top", "width" => 16);

// TODO: Change to use ui_print_page_header
if ($id_agente) {
	
	/* View tab */
	$viewtab['text'] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'">' 
			. html_print_image ("images/zoom.png", true, array ("title" =>__('View')))
			. '</a>';
			
	if($tab == 'view')
		$viewtab['active'] = true;
	else
		$viewtab['active'] = false;
	
	/* Main tab */
	$maintab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=main&amp;id_agente='.$id_agente.'">' 
			. html_print_image ("images/cog.png", true, array ("title" =>__('Setup')))
			. '</a>';
	if($tab == 'main')
	
		$maintab['active'] = true;
	else
		$maintab['active'] = false;
		
	/* Module tab */
	$moduletab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=module&amp;id_agente='.$id_agente.'">' 
			. html_print_image ("images/brick.png", true, array ("title" =>__('Modules')))
			. '</a>';
	
	if($tab == 'module')
		$moduletab['active'] = true;
	else
		$moduletab['active'] = false;
		
	/* Alert tab */
	$alerttab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=alert&amp;id_agente='.$id_agente.'">' 
			. html_print_image ("images/bell.png", true, array ("title" =>__('Alerts')))
			. '</a>';
	
	if($tab == 'alert')
		$alerttab['active'] = true;
	else
		$alerttab['active'] = false;
		
	/* Template tab */
	$templatetab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=template&amp;id_agente='.$id_agente.'">' 
			. html_print_image ("images/network.png", true, array ("title" =>__('Module templates')))
			. '</a>';
	
	if($tab == 'template')
		$templatetab['active'] = true;
	else
		$templatetab['active'] = false;		
	
	
	/* Inventory */
	$inventorytab = enterprise_hook ('inventory_tab');

	if ($inventorytab == -1)
		$inventorytab = "";

	/* Collection */
	$collectiontab = enterprise_hook('collection_tab');

	if ($collectiontab == -1)
		$collectiontab = "";
	
	/* Group tab */
	
	$grouptab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&ag_group='.$group.'">'
			. html_print_image ("images/agents_group.png", true, array( "title" => __('Group')))
			. '</a>';
	
	$grouptab['active'] = false;
	
	$gistab = "";
	
	/* GIS tab */
	if ($config['activate_gis']) {
		
		$gistab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=gis&id_agente='.$id_agente.'">'
			. html_print_image ("images/world.png", true, array ( "title" => __('GIS data')))
			. '</a>';

		if ($tab == "gis")
			$gistab['active'] = true;
		else
			$gistab['active'] = false;
	}
	
	$onheader = array('view' => $viewtab, 'separator' => "", 'main' => $maintab,
		'module' => $moduletab, 'alert' => $alerttab, 'template' => $templatetab,
		'inventory' => $inventorytab, 'collection'=> $collectiontab, 'group' => $grouptab, 'gis' => $gistab);
	
	foreach($config['extensions'] as $extension) {
		if (isset($extension['extension_god_tab'])) {
			$image = $extension['extension_god_tab']['icon'];
			$name = $extension['extension_god_tab']['name'];
			$id = $extension['extension_god_tab']['id'];
			
			$id_extension = get_parameter('id_extension', '');
			
			if ($id_extension == $id) {
				$active = true;
			}
			else {
				$active = false;
			}
			
			$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=extension&id_agente='.$id_agente . '&id_extension=' . $id;
			
			$extension_tab = array('text' => '<a href="' . $url .'">' . html_print_image ($image, true, array ( "title" => $name)) . '</a>', 'active' => $active);
			
			$onheader = $onheader + array($id => $extension_tab);	
		}
	}
	
	// This add information to the header 
	switch ($tab) {
		case "main":
			$tab_description = '- '. __('Setup'); 
			break;
		case "collection":
			$tab_description = '- ' . __('Collection') . ui_print_help_icon('collection_tab', true);
			break;
		case "inventory":
			$tab_description = '- ' . __('Inventory') . ui_print_help_icon('inventory_tab', true);
			break;	
		case "module":
			$tab_description = '- '. __('Modules');
			break;
		case "alert":
			$tab_description = '- ' . __('Alert') . ui_print_help_icon('manage_alert_list', true);
			break;
		case "template":
			$tab_description = '- ' . __('Templates') . ui_print_help_icon('template_tab', true);
			break;	
		case "gis":
			$tab_description = '- ' . __('Gis') . ui_print_help_icon('gis_tab', true);
			break;
		case "extension":
			$id_extension = get_parameter('id_extension', '');
			switch ($id_extension){
				case "snmp_explorer":
					$tab_description = '- ' . __('SNMP explorer') . ui_print_help_icon('snmp_explorer', true);
				}
			break;
		default:
			break;
	}
	
	ui_print_page_header (__('Agent configuration') . ' -&nbsp;' .
		ui_print_truncate_text(agents_get_name ($id_agente), 'agent_small', false) .
		' ' . $tab_description, "images/setup.png", false, "", true, $onheader);
}
else {
	// Create agent 
	ui_print_page_header (__('Agent manager'), "images/bricks.png", false, "create_agent", true);
}

$delete_conf_file = (bool) get_parameter('delete_conf_file');

if ($delete_conf_file) {
	$correct = false;
	// Delete remote configuration
	if (isset ($config["remote_config"])) {
		$agent_md5 = md5 (agents_get_name ($id_agente,'none'), FALSE);
		
		if (file_exists ($config["remote_config"]."/md5/".$agent_md5.".md5")) {
			// Agent remote configuration editor
			$file_name = $config["remote_config"]."/conf/".$agent_md5.".conf";
			$correct = @unlink ($file_name);
			
			$file_name = $config["remote_config"]."/md5/".$agent_md5.".md5";
			$correct = @unlink ($file_name);
		}
	}
	
	ui_print_result_message ($correct,
		__('Conf file deleted successfully'),
		__('Could not delete conf file'));
}


// Show agent creation results
if ($create_agent) {
	ui_print_result_message ($agent_created_ok,
		__('Successfully created'),
		__('Could not be created'));
}

// Fix / Normalize module data
if (isset( $_GET["fix_module"])) { 
	$id_module = get_parameter_get ("fix_module",0);
	// get info about this module
	$media = reporting_get_agentmodule_data_average ($id_module, 30758400); //Get average over the year
	$media *= 1.3;
	$error = "";
	//If the value of media is 0 or something went wrong, don't delete
	if (!empty ($media)) {
		$where = array(
			'datos' => '>' . $media,
			'id_agente_modulo' => $id_module);
		db_process_sql_delete('tagente_datos', $where);
	}
	else {
		$result = false;
		$error = " - ".__('No data to normalize');
	}
	
	ui_print_result_message ($result,
		__('Deleted data above %d', $media),
		__('Error normalizing module %s', $error));
}

$update_agent = (bool) get_parameter ('update_agent');

// Update AGENT
if ($update_agent) { // if modified some agent paramenter
	$id_agente = (int) get_parameter_post ("id_agente");
	$nombre_agente = str_replace('`','&lsquo;',(string) get_parameter_post ("agente", ""));
	$direccion_agente = (string) get_parameter_post ("direccion", '');
	$address_list = (string) get_parameter_post ("address_list", '');
	if ($address_list != $direccion_agente && $direccion_agente == agents_get_address ($id_agente) && $address_list != agents_get_address ($id_agente)) {
		//If we selected another IP in the drop down list to be 'primary': 
		// a) field is not the same as selectbox
		// b) field has not changed from current IP
		// c) selectbox is not the current IP
		if ($address_list != 0)
			$direccion_agente = $address_list;
	}
	$grupo = (int) get_parameter_post ("grupo", 0);
	$intervalo = (int) get_parameter_post ("intervalo", 300);
	$comentarios = str_replace('`','&lsquo;',(string) get_parameter_post ("comentarios", ""));
	$modo = (bool) get_parameter_post ("modo", 0); //Mode: Learning or Normal
	$id_os = (int) get_parameter_post ("id_os");
	$disabled = (bool) get_parameter_post ("disabled");
	$server_name = (string) get_parameter_post ("server_name", "");
	$id_parent = (string) get_parameter_post ("id_parent");
	$id_parent = (int) agents_get_agent_id ($id_parent);
	$custom_id = (string) get_parameter_post ("custom_id", "");
	$cascade_protection = (int) get_parameter_post ("cascade_protection", 0);
	$icon_path = (string) get_parameter_post ("icon_path",'');
	$update_gis_data = (int) get_parameter_post("update_gis_data", 0);
	
	$fields = db_get_all_fields_in_table('tagent_custom_fields');
	
	if($fields === false) $fields = array();
	
	$field_values = array();
	
	foreach($fields as $field) {
		$field_values[$field['id_field']] = (string) get_parameter_post ('customvalue_'.$field['id_field'], '');
	}
	
	
	foreach($field_values as $key => $value) {
		$old_value = db_get_all_rows_filter('tagent_custom_data', array('id_agent' => $id_agente, 'id_field' => $key));
	
		if($old_value === false) {
			// Create custom field if not exist
			db_process_sql_insert ('tagent_custom_data',
				 array('id_field' => $key,'id_agent' => $id_agente, 'description' => $value));
		}
		else {		
			db_process_sql_update ('tagent_custom_data',
				 array('description' => $value),
				 array('id_field' => $key,'id_agent' => $id_agente));
		}
	}
	
	//Verify if there is another agent with the same name but different ID
	if ($nombre_agente == "") { 
		echo '<h3 class="error">'.__('No agent name specified').'</h3>';	
	//If there is an agent with the same name, but a different ID
	}
	elseif (agents_get_agent_id ($nombre_agente) > 0 && agents_get_agent_id ($nombre_agente) != $id_agente) {
		echo '<h3 class="error">'.__('There is already an agent in the database with this name').'</h3>';
	}
	else {
		//If different IP is specified than previous, add the IP
		if ($direccion_agente != '' && $direccion_agente != agents_get_address ($id_agente))
			agents_add_address ($id_agente, $direccion_agente);
		
		//If IP is set for deletion, delete first
		if (isset ($_POST["delete_ip"])) {
			$delete_ip = get_parameter_post ("address_list");
			agents_delete_address ($id_agente, $delete_ip);
		}
	
		$result = db_process_sql_update ('tagente', 
			array ('disabled' => $disabled,
				'id_parent' => $id_parent,
				'id_os' => $id_os,
				'modo' => $modo,
				'nombre' => $nombre_agente,
				'direccion' => $direccion_agente,
				'id_grupo' => $grupo,
				'intervalo' => $intervalo,
				'comentarios' => $comentarios,
				'cascade_protection' => $cascade_protection,
				'server_name' => $server_name,
				'custom_id' => $custom_id,
				'icon_path' => $icon_path,
				'update_gis_data' => $update_gis_data),
			array ('id_agente' => $id_agente));
			
		if ($result === false) {
			ui_print_error_message (__('There was a problem updating the agent'));
		}
		else {
			$info = 'Group: ' . $grupo . ' Interval: ' . $intervalo .
				' Comments: ' . $comentarios . ' Mode: ' . $modo . 
				' ID OS: ' . $id_os . ' Disabled: ' . $disabled . 
				' Server Name: ' . $server_name . ' ID parent: ' . $id_parent .
				' Custom ID: ' . $custom_id . ' Cascade Protection: ' . $cascade_protection .
				' Icon Path: ' . $icon_path . 'Update GIS data: ' .$update_gis_data;
			
			enterprise_hook ('update_agent', array ($id_agente));
			ui_print_success_message (__('Successfully updated'));
			db_pandora_audit("Agent management",
				"Updated agent $nombre_agente", false, false, $info);

		}
	}
}

// Read agent data
// This should be at the end of all operation checks, to read the changes - $id_agente doesn't have to be retrieved
if ($id_agente) {
	//This has been done in the beginning of the page, but if an agent was created, this id might change
	$id_grupo = agents_get_agent_group ($id_agente);
	$is_extra = enterprise_hook('policies_is_agent_extra_policy', array($id_agente));

	if($is_extra === ENTERPRISE_NOT_HOOK) {
		$is_extra = false;
	}
	if (!check_acl ($config["id_user"], $id_grupo, "AW") && !$is_extra) {
		db_pandora_audit("ACL Violation","Trying to admin an agent without access");
		require ("general/noaccess.php");
		exit;
	}
	
	$agent = db_get_row ('tagente', 'id_agente', $id_agente);
	if (empty ($agent)) {
		//Close out the page
		ui_print_error_message (__('There was a problem loading the agent'));
		return;
	}
	
	$intervalo = $agent["intervalo"]; // Define interval in seconds
	$nombre_agente = $agent["nombre"];
	$direccion_agente = $agent["direccion"];
	$grupo = $agent["id_grupo"];
	$ultima_act = $agent["ultimo_contacto"];
	$comentarios = $agent["comentarios"];
	$server_name = $agent["server_name"];
	$modo = $agent["modo"];
	$id_os = $agent["id_os"];
	$disabled = $agent["disabled"];
	$id_parent = $agent["id_parent"];
	$custom_id = $agent["custom_id"];
	$cascade_protection = $agent["cascade_protection"];
	$icon_path = $agent["icon_path"];
	$update_gis_data = $agent["update_gis_data"];
}

$update_module = (bool) get_parameter ('update_module');
$create_module = (bool) get_parameter ('create_module');
$delete_module = (bool) get_parameter ('delete_module');
//It is the id_agent_module to duplicate
$duplicate_module = (int) get_parameter ('duplicate_module');
$edit_module = (bool) get_parameter ('edit_module');

// GET DATA for MODULE UPDATE OR MODULE INSERT
if ($update_module || $create_module) {
	$id_grupo = agents_get_agent_group ($id_agente);
	
	$is_extra = enterprise_hook('policies_is_agent_extra_policy', array($id_agente));

	if($is_extra === ENTERPRISE_NOT_HOOK) {
		$is_extra = false;
	}
	
	if (!check_acl ($config["id_user"], $id_grupo, "AW") && !$is_extra) {
		db_pandora_audit("ACL Violation",
			"Trying to create a module without admin rights");
		require ("general/noaccess.php");
		exit;
	}
	$id_module_type = (int) get_parameter ('id_module_type');
	$name = (string) get_parameter ('name');
	$description = (string) get_parameter ('description');
	$id_module_group = (int) get_parameter ('id_module_group');
	$flag = (bool) get_parameter ('flag');

	// Don't read as (float) because it lost it's decimals when put into MySQL
	// where are very big and PHP uses scientific notation, p.e:
	// 1.23E-10 is 0.000000000123
	
	$post_process = (string) get_parameter ('post_process', 0.0);
	$prediction_module = 1;
	$max_timeout = (int) get_parameter ('max_timeout');
	$min = (int) get_parameter_post ("min");
	$max = (int) get_parameter ('max');
	$interval = (int) get_parameter ('module_interval', $intervalo);
	$id_plugin = (int) get_parameter ('id_plugin');
	$id_export = (int) get_parameter ('id_export');
	$disabled = (bool) get_parameter ('disabled');
	$tcp_send = (string) get_parameter ('tcp_send');
	$tcp_rcv = (string) get_parameter ('tcp_rcv');
	$tcp_port = (int) get_parameter ('tcp_port');
	$configuration_data = (string) get_parameter ('configuration_data');
	$old_configuration_data = (string) get_parameter ('old_configuration_data');

	$custom_string_1 = (string) get_parameter ('custom_string_1');
	$custom_string_2 = (string) get_parameter ('custom_string_2');
	$custom_string_3 = (string) get_parameter ('custom_string_3');
	$custom_integer_1 = (int) get_parameter ('prediction_module');
	$custom_integer_2 = (int) get_parameter ('custom_integer_2');

	// Services are an enterprise feature, 
    // so we got the parameters using this function.

	enterprise_hook ('get_service_synthetic_parameters');
	
	$agent_name = (string) get_parameter('agent_name',agents_get_name ($id_agente));

	$snmp_community = (string) get_parameter ('snmp_community');
	$snmp_oid = (string) get_parameter ('snmp_oid');

	if (empty ($snmp_oid)) {
		/* The user did not set any OID manually but did a SNMP walk */
		$snmp_oid = (string) get_parameter ('select_snmp_oid');
	}

	if ($id_module_type >= 15 && $id_module_type <= 18){
		// New support for snmp v3
		$tcp_send = (string) get_parameter ('snmp_version');
		$plugin_user = (string) get_parameter ('snmp3_auth_user');
		$plugin_pass = (string) get_parameter ('snmp3_auth_pass');
		$plugin_parameter = (string) get_parameter ('snmp3_auth_method');

		$custom_string_1 = (string) get_parameter ('snmp3_privacy_method');
		$custom_string_2 = (string) get_parameter ('snmp3_privacy_pass');
		$custom_string_3 = (string) get_parameter ('snmp3_security_level');
	}
	else {
		$plugin_user = (string) get_parameter ('plugin_user');
		if (get_parameter('id_module_component_type') == 7)
			$plugin_pass = (int) get_parameter ('plugin_pass');
		else
			$plugin_pass = (string) get_parameter ('plugin_pass');
			
		$plugin_parameter = (string) get_parameter ('plugin_parameter');
	}
		
	$ip_target = (string) get_parameter ('ip_target');
	$custom_id = (string) get_parameter ('custom_id');
	$history_data = (int) get_parameter('history_data');
	$min_warning = (float) get_parameter ('min_warning');
	$max_warning = (float) get_parameter ('max_warning');
	$str_warning = (string) get_parameter ('str_warning');
	$min_critical = (float) get_parameter ('min_critical');
	$max_critical = (float) get_parameter ('max_critical');
	$str_critical = (string) get_parameter ('str_critical');
	$ff_event = (int) get_parameter ('ff_event');
	$unit = (string) get_parameter('unit');
	$id_tag = (array) get_parameter('id_tag_selected');
	$serialize_ops = (string) get_parameter('serialize_ops');
	
	if($prediction_module < 3) {
		unset($serialize_ops);
		enterprise_hook('modules_delete_synthetic_operations', array($id_agent_module));
	}
	
	$active_snmp_v3 = get_parameter('active_snmp_v3');
	if ($active_snmp_v3) {
	//
	}
	
	// Make changes in the conf file if necessary
	enterprise_include_once('include/functions_config_agents.php');
	enterprise_hook('config_agents_write_module_in_conf',
		array($id_agente, io_safe_output($old_configuration_data), io_safe_output($configuration_data), $disabled));
}

// MODULE UPDATE
if ($update_module) {
	$id_agent_module = (int) get_parameter ('id_agent_module');
	
	$values = array ('descripcion' => $description,
			'id_module_group' => $id_module_group,
			'nombre' => $name,
			'max' => $max,
			'min' => $min,
			'module_interval' => $interval,
			'tcp_port' => $tcp_port,
			'tcp_send' => $tcp_send,
			'tcp_rcv' => $tcp_rcv,
			'snmp_community' => $snmp_community,
			'snmp_oid' => $snmp_oid,
			'ip_target' => $ip_target,
			'flag' => $flag,
			'disabled' => $disabled,
			'id_export' => $id_export,
			'plugin_user' => $plugin_user,
			'plugin_pass' => $plugin_pass,
			'plugin_parameter' => $plugin_parameter,
			'id_plugin' => $id_plugin,
			'post_process' => $post_process,
			'prediction_module' => $prediction_module,
			'max_timeout' => $max_timeout,
			'custom_id' => $custom_id,
			'history_data' => $history_data,
			'min_warning' => $min_warning,
			'max_warning' => $max_warning,
			'str_warning' => $str_warning,
			'min_critical' => $min_critical,
			'max_critical' => $max_critical,
			'str_critical' => $str_critical,
			'custom_string_1' => $custom_string_1,
			'custom_string_2' => $custom_string_2,
			'custom_string_3' => $custom_string_3,
			'custom_integer_1' => $custom_integer_1,
			'custom_integer_2' => $custom_integer_2,
			'min_ff_event' => $ff_event,
			'unit' => $unit);
	
	if($prediction_module == 3 && $serialize_ops == '') {
		$result = false;
	}
	else {
		$result = modules_update_agent_module ($id_agent_module,
			$values, false, $id_tag);
	}
	
	if (is_error($result)) {
		$msg = __('There was a problem updating module').'. ';
		
		switch($result) {
			case ERR_EXIST:
				$msg .= __('Another module already exists with the same name').'.';
				break;
			case ERR_INCOMPLETE:
				$msg .= __('Some required fields are missed').': ('.__('name').')';
				break;
			case ERR_NOCHANGES:
				$msg .= __('"No change"');
				break;
			case ERR_DB:
			case ERR_GENERIC:
			default:
				$msg .= __('Processing error');
				break;
		}
		$result = false;
		echo '<h3 class="error">'.$msg.'</h3>';
		
		$edit_module = true;
		
		db_pandora_audit("Agent management",
			"Fail to try update module '$name' for agent ".$agent["nombre"]);
	}
	else {
		if($prediction_module == 3) {
			enterprise_hook('modules_create_synthetic_operations', array($id_agent_module, $serialize_ops));
		}
		echo '<h3 class="suc">'.__('Module successfully updated').'</h3>';
		$id_agent_module = false;
		$edit_module = false;

		$agent = db_get_row ('tagente', 'id_agente', $id_agente);

		db_pandora_audit("Agent management",
			"Updated module '$name' for agent ".$agent["nombre"], false, false, json_encode($values));
	}
}

// MODULE INSERT
// =================
if ($create_module) {
	if (isset ($_POST["combo_snmp_oid"])) {
		$combo_snmp_oid = get_parameter_post ("combo_snmp_oid");
	}
	if ($snmp_oid == ""){
		$snmp_oid = $combo_snmp_oid;
	}
	
	$id_module = (int) get_parameter ('id_module');
	
	switch ($config["dbtype"]) {
		case "oracle":
			if (empty($description) || !isset($description)) {
				$description=' ';			
			}	
			break;
	}

	$values = array ('id_tipo_modulo' => $id_module_type,
			'descripcion' => $description, 
			'max' => $max,
			'min' => $min, 
			'snmp_oid' => $snmp_oid,
			'snmp_community' => $snmp_community,
			'id_module_group' => $id_module_group, 
			'module_interval' => $interval,
			'ip_target' => $ip_target,
			'tcp_port' => $tcp_port,
			'tcp_rcv' => $tcp_rcv, 
			'tcp_send' => $tcp_send,
			'id_export' => $id_export, 
			'plugin_user' => $plugin_user,
			'plugin_pass' => $plugin_pass, 
			'plugin_parameter' => $plugin_parameter,
			'id_plugin' => $id_plugin, 
			'post_process' => $post_process,
			'prediction_module' => $prediction_module,
			'max_timeout' => $max_timeout, 
			'disabled' => $disabled,
			'id_modulo' => $id_module,
			'custom_id' => $custom_id,
			'history_data' => $history_data,
			'min_warning' => $min_warning,
			'max_warning' => $max_warning,
			'str_warning' => $str_warning,
			'min_critical' => $min_critical,
			'max_critical' => $max_critical,
			'str_critical' => $str_critical,
			'custom_string_1' => $custom_string_1,
			'custom_string_2' => $custom_string_2,
			'custom_string_3' => $custom_string_3,
			'custom_integer_1' => $custom_integer_1,
			'custom_integer_2' => $custom_integer_2,
			'min_ff_event' => $ff_event,
			'unit' => $unit
		);
	if($prediction_module == 3 && $serialize_ops == '') {
		$id_agent_module = false;
	}
	else {
		$id_agent_module = modules_create_agent_module ($id_agente, $name, $values, false, $id_tag);
	}

	if (is_error($id_agent_module)) {
		$msg = __('There was a problem adding module').'. ';
		switch($id_agent_module) {
			case ERR_EXIST:
				$msg .= __('Another module already exists with the same name').'.';
				break;
			case ERR_INCOMPLETE:
				$msg .= __('Some required fields are missed').': ('.__('name').')';
				break;
			case ERR_DB:
			case ERR_GENERIC:
			default:
				$msg .= __('Processing error');
				break;
		}
		$id_agent_module = false;
		echo '<h3 class="error">'.$msg.'</h3>';
		$edit_module = true;
		$moduletype = $id_module;
		db_pandora_audit("Agent management",
			"Fail to try added module '$name' for agent ".$agent["nombre"]);
	}
	else {
		if($prediction_module == 3) {
			enterprise_hook('modules_create_synthetic_operations', array($id_agent_module, $serialize_ops));
		}
		
		echo '<h3 class="suc">'.__('Module added successfully').'</h3>';
		$id_agent_module = false;
		$edit_module = false;
		
		$info = '';

		$agent = db_get_row ('tagente', 'id_agente', $id_agente);
		db_pandora_audit("Agent management",
			"Added module '$name' for agent ".$agent["nombre"], false, false, json_encode($values));
	}
}

// MODULE DELETION
// =================
if ($delete_module) { // DELETE agent module !
	$id_borrar_modulo = (int) get_parameter_get ("delete_module",0);
	$module_data = db_get_row ('tagente_modulo', 'id_agente_modulo', $id_borrar_modulo);
	$id_grupo = (int) agents_get_agent_group($id_agente);
	
	if (! check_acl ($config["id_user"], $id_grupo, "AW")) {
		db_pandora_audit("ACL Violation",
		"Trying to delete a module without admin rights");
		require ("general/noaccess.php");
		exit;
	}
	
	if ($id_borrar_modulo < 1) {
		db_pandora_audit("HACK Attempt",
		"Expected variable from form is not correct");
		require ("general/noaccess.php");
		exit;
	}
	
	enterprise_include_once('include/functions_config_agents.php');
	enterprise_hook('config_agents_delete_module_in_conf', array(modules_get_agentmodule_agent($id_borrar_modulo), modules_get_agentmodule_name($id_borrar_modulo)));
	
	//Init transaction
	$error = 0;
	db_process_sql_begin ();
	
	// First delete from tagente_modulo -> if not successful, increment
	// error. NOTICE that we don't delete all data here, just marking for deletion
	// and delete some simple data.
	
	$values = array(
		'nombre' => 'pendingdelete',
		'disabled' => 1,
		'delete_pending' => 1);
	$result = db_process_sql_update('tagente_modulo', $values, array('id_agente_modulo' => $id_borrar_modulo));
	if ($result === false)
		$error++;
	
	$result = db_process_sql_delete('tagente_estado', array('id_agente_modulo' => $id_borrar_modulo));
	if ($result === false)
		$error++;
	
	$result = db_process_sql_delete('tagente_datos_inc', array('id_agente_modulo' => $id_borrar_modulo));	
	if ($result === false)
		$error++;

	if (alerts_delete_alert_agent_module($id_borrar_modulo) === false)
		$error++;
	
	$result = db_process_delete_temp('ttag_module', 'id_agente_modulo', $id_borrar_modulo);	
	if ($result === false)
		$error++;

	// Trick to detect if we are deleting a synthetic module (avg or arithmetic)
	// If result is empty then module doesn't have this type of submodules
	$ops_json = enterprise_hook('modules_get_synthetic_operations', array($id_borrar_modulo));
	$result_ops_synthetic = json_decode($ops_json);
	if (!empty($result_ops_synthetic)){
		$result = enterprise_hook('modules_delete_synthetic_operations', array($id_borrar_modulo));
		if ($result === false)
			$error++;
	} // Trick to detect if we are deleting components of synthetics modules (avg or arithmetic)
	else{
		$result_components = enterprise_hook('modules_get_synthetic_components', array($id_borrar_modulo));
		$count_components = 1;
		if (!empty($result_components)){
			// Get number of components pending to delete to know when it's needed to update orders 
			$num_components = count($result_components);
			$last_target_module = 0;
			foreach ($result_components as $id_target_module){
				// Detects change of component or last component to update orders
				if (($count_components == $num_components) or ($last_target_module != $id_target_module))
					$update_orders = true;
				else
					$update_orders = false;
				$result = enterprise_hook('modules_delete_synthetic_operations', array($id_target_module, $id_borrar_modulo, $update_orders));
			
				if ($result === false)
					$error++;				
				$count_components++;
				$last_target_module = $id_target_module;
			}
		}
	}

	//Check for errors
	if ($error != 0) {
		db_process_sql_rollback ();
		ui_print_error_message (__('There was a problem deleting the module'));
	}
	else {
		db_process_sql_commit ();
		ui_print_success_message (__('Module deleted succesfully'));

		$agent = db_get_row ('tagente', 'id_agente', $id_agente);
		db_pandora_audit("Agent management",
			"Deleted module '".$module_data["nombre"]."' for agent ".$agent["nombre"]);
	}
}

// MODULE DUPLICATION
// =================
if (!empty($duplicate_module)) { // DUPLICATE agent module !
	$id_duplicate_module = $duplicate_module;
	
	$original_name = modules_get_agentmodule_name($id_duplicate_module);
	$copy_name = io_safe_input(__('copy of') . ' ') . $original_name;
	
	$cont = 0;
	$exists = true;
	while($exists) {
		$exists = (bool)db_get_value ('id_agente_modulo', 'tagente_modulo',
			'nombre', $copy_name);
		if ($exists) {
			$cont++;
			$copy_name = io_safe_input(__('copy of') . ' ') . $original_name
				. io_safe_input(' (' . $cont . ')');
		}
	}
	
	$result = modules_copy_agent_module_to_agent ($id_duplicate_module,
		modules_get_agentmodule_agent($id_duplicate_module), $copy_name);
	
	$agent = db_get_row ('tagente', 'id_agente', $id_agente);
	
	if ($result) {
		db_pandora_audit("Agent management",
			"Duplicate module '".$id_duplicate_module."' for agent " . $agent["nombre"] . " with the new id for clon " . $result);
	}
	else {
		db_pandora_audit("Agent management",
			"Fail to try duplicate module '".$id_duplicate_module."' for agent " . $agent["nombre"]);
	}
}

// UPDATE GIS
// ==========
$updateGIS = get_parameter('update_gis', 0);
if ($updateGIS) {
	$updateGisData = get_parameter("update_gis_data");
	$lastLatitude = get_parameter("latitude");
	$lastLongitude = get_parameter("longitude");
	$lastAltitude = get_parameter("altitude");
	$idAgente = get_parameter("id_agente");
	
	$previusAgentGISData = db_get_row_sql("SELECT *
		FROM tgis_data_status WHERE tagente_id_agente = " . $idAgente);
	
	db_process_sql_begin();
	
	db_process_sql_update('tagente', array('update_gis_data' => $updateGisData),
		array('id_agente' => $idAgente));
		
	if ($previusAgentGISData !== false) {
		db_process_sql_insert('tgis_data_history', array(
			"longitude" => $previusAgentGISData['stored_longitude'],
			"latitude" => $previusAgentGISData['stored_latitude'],
			"altitude" => $previusAgentGISData['stored_altitude'],
			"start_timestamp" => $previusAgentGISData['start_timestamp'],
			"end_timestamp" => date( 'Y-m-d H:i:s'),
			"description" => "Save by Pandora Console",
			"manual_placement" => $previusAgentGISData['manual_placement'],
			"number_of_packages" => $previusAgentGISData['number_of_packages'],
			"tagente_id_agente" => $previusAgentGISData['tagente_id_agente']
		));
		db_process_sql_update('tgis_data_status', array(
			"tagente_id_agente" => $idAgente,
			"current_longitude" => $lastLongitude,
			"current_latitude" => $lastLatitude,
			"current_altitude" => $lastAltitude,
			"stored_longitude" => $lastLongitude,
			"stored_latitude" => $lastLatitude,
			"stored_altitude" => $lastAltitude,
			"start_timestamp" => date( 'Y-m-d H:i:s'),
			"manual_placement" => 1,
			"description" => "Update by Pandora Console"),
			array("tagente_id_agente" => $idAgente));
	}
	else {
		db_process_sql_insert('tgis_data_status', array(
			"tagente_id_agente" => $idAgente,
			"current_longitude" => $lastLongitude,
			"current_latitude" => $lastLatitude,
			"current_altitude" => $lastAltitude,
			"stored_longitude" => $lastLongitude,
			"stored_latitude" => $lastLatitude,
			"stored_altitude" => $lastAltitude,
			"manual_placement" => 1,
			"description" => "Insert by Pandora Console"
		));
	}
	db_process_sql_commit();
}

// -----------------------------------
// Load page depending on tab selected
// -----------------------------------
switch ($tab) {
	case "main":
		require ("agent_manager.php");
		break;
	case "module":
		if ($id_agent_module || $edit_module) {
			require ("module_manager_editor.php");
		}
		else {
			require ("module_manager.php");
		}
		break;
	case "alert":
		/* Because $id_agente is set, it will show only agent alerts */
		/* This var is for not display create button on alert list */
		$dont_display_alert_create_bttn = true;
		require ("godmode/alerts/alert_list.php");
		break;
	case "template":
		require ("agent_template.php");
		break;
	case "gis":
		require("agent_conf_gis.php");
		break;
	case "extension":
		$found = false;
		foreach($config['extensions'] as $extension) {
			if (isset($extension['extension_god_tab'])) {
				$id = $extension['extension_god_tab']['id'];
				$function = $extension['extension_god_tab']['function'];
				
				$id_extension = get_parameter('id_extension', '');
				
				if ($id_extension == $id) {
					call_user_func_array($function, array());
					$found = true;
				}
			}
		}
		if (!$found) {
			ui_print_error_message ("Invalid tab specified");
		}
		break;
	default:
		if (enterprise_hook ('switch_agent_tab', array ($tab)))
			//This will make sure that blank pages will have at least some
			//debug info in them - do not translate debug
			ui_print_error_message ("Invalid tab specified");
}
?>
