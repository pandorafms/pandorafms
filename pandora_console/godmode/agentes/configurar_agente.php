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
include_once($config['homedir'] . "/include/functions_cron.php");
ui_require_javascript_file('encode_decode_base64');

check_login ();

//Get tab parameter to check ACL in each tabs
$tab = get_parameter ('tab', 'main');

//See if id_agente is set (either POST or GET, otherwise -1
$id_agente = (int) get_parameter ("id_agente");
$group = 0;
if ($id_agente)
	$group = agents_get_agent_group ($id_agente);

if (!check_acl ($config["id_user"], $group, "AW", $id_agente)) {
	$access_granted = false;
	switch ($tab) {
		case 'alert':
		case 'module':
			if (check_acl ($config["id_user"], $group, "AD", $id_agente)) {
				$access_granted = true;
			}
			break;
		default:
			break;
	}
	
	if (!$access_granted) {
		db_pandora_audit("ACL Violation",
			"Trying to access agent manager");
		require ("general/noaccess.php");
		return;
	}
}

require_once ('include/functions_modules.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_reporting.php');

// Get passed variables
$alerttype = get_parameter('alerttype');
$id_agent_module = (int)get_parameter('id_agent_module');

// Init vars
$descripcion = "";
$comentarios = "";
$campo_1 = "";
$campo_2 = "";
$campo_3 = "";
$maximo = 0;
$minimo = 0;
$nombre_agente = "";
$direccion_agente = get_parameter('direccion', '');
$direccion_agente = trim(io_safe_output($direccion_agente));
$direccion_agente = io_safe_input($direccion_agente);
$intervalo = SECONDS_5MINUTES;
$ff_interval = 0;
$quiet_module = 0;
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
$module_ff_interval = "";
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
$url_description = '';
$quiet = 0;
$macros = '';

$create_agent = (bool)get_parameter('create_agent');
$module_macros = array ();

// Create agent
if ($create_agent) {
	$nombre_agente = (string) get_parameter_post("agente",'');
	$direccion_agente = (string) get_parameter_post("direccion",'');
	$direccion_agente = trim(io_safe_output($direccion_agente));
	$direccion_agente = io_safe_input($direccion_agente);
	$grupo = (int) get_parameter_post ("grupo");
	$intervalo = (string) get_parameter_post ("intervalo", SECONDS_5MINUTES);
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
	$url_description = (string) get_parameter("url_description");
	$quiet = (int) get_parameter("quiet", 0);
	
	$fields = db_get_all_fields_in_table('tagent_custom_fields');
	
	if ($fields === false) $fields = array();
	
	$field_values = array();
	
	foreach ($fields as $field) {
		$field_values[$field['id_field']] = (string) get_parameter_post ('customvalue_'.$field['id_field'], '');
	}
	
	// Check if agent exists (BUG WC-50518-2)
	if ($nombre_agente == "") {
		$agent_creation_error = __('No agent name specified');
		$agent_created_ok = 0;
	}
	elseif (agents_get_agent_id ($nombre_agente)) {
		$agent_creation_error =
			__('There is already an agent in the database with this name');
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
				'update_gis_data' => $update_gis_data,
				'url_address' => $url_description,
				'quiet' => $quiet));
		enterprise_hook ('update_agent', array ($id_agente));
		if ($id_agente !== false) {
			// Create custom fields for this agent
			foreach ($field_values as $key => $value) {
				db_process_sql_insert ('tagent_custom_data',
				 array('id_field' => $key,'id_agent' => $id_agente, 'description' => $value));
			}
			// Create address for this agent in taddress
			if ( $direccion_agente != ''){
				agents_add_address ($id_agente, $direccion_agente);
			}
			
			$agent_created_ok = true;
			
			$info = 'Name: ' . $nombre_agente .
				' IP: ' . $direccion_agente .
				' Group: ' . $grupo .
				' Interval: ' . $intervalo .
				' Comments: ' . $comentarios .
				' Mode: ' . $modo .
				' ID_parent: ' . $id_parent .
				' Server: ' . $server_name .
				' ID os: ' . $id_os .
				' Disabled: ' . $disabled .
				' Custom ID: ' . $custom_id .
				' Cascade protection: '  . $cascade_protection . 
				' Icon path: ' . $icon_path .
				' Update GIS data: ' . $update_gis_data . 
				' Url description: ' . $url_description .
				' Quiet: ' . (int)$quiet;
			
			db_pandora_audit("Agent management",
				"Created agent $nombre_agente", false, true, $info);
		}
		else {
			$id_agente = 0;
			$agent_creation_error = __('Could not be created');
		}
	}
}

// Show tabs
$img_style = array ("class" => "top", "width" => 16);

if ($id_agente) {
	
	/* View tab */
	$viewtab['text'] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'">' 
		. html_print_image ("images/operation.png", true, array ("title" =>__('View')))
		. '</a>';
	
	if ($tab == 'view')
		$viewtab['active'] = true;
	else
		$viewtab['active'] = false;
	
	$viewtab['operation'] = 1;
	
	/* Main tab */
	$maintab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=main&amp;id_agente='.$id_agente.'">' 
		. html_print_image ("images/gm_setup.png", true, array ("title" =>__('Setup')))
		. '</a>';
	if ($tab == 'main')
		$maintab['active'] = true;
	else
		$maintab['active'] = false;
		
	/* Module tab */
	$moduletab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=module&amp;id_agente='.$id_agente.'">' 
		. html_print_image ("images/gm_modules.png", true, array ("title" =>__('Modules')))
		. '</a>';
	
	if ($tab == 'module')
		$moduletab['active'] = true;
	else
		$moduletab['active'] = false;
	
	/* Alert tab */
	$alerttab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=alert&amp;id_agente='.$id_agente.'">' 
		. html_print_image ("images/gm_alerts.png", true, array ("title" =>__('Alerts')))
		. '</a>';
	
	if ($tab == 'alert')
		$alerttab['active'] = true;
	else
		$alerttab['active'] = false;
	
	/* Template tab */
	$templatetab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=template&amp;id_agente='.$id_agente.'">' 
		. html_print_image ("images/templates.png", true, array ("title" =>__('Module templates')))
		. '</a>';
	
	if ($tab == 'template')
		$templatetab['active'] = true;
	else
		$templatetab['active'] = false;
	
	
	/* Inventory */
	$inventorytab = enterprise_hook ('inventory_tab');
	
	if ($inventorytab == -1)
		$inventorytab = "";
	
	
	$has_remote_conf = enterprise_hook('config_agents_has_remote_configuration',
		array($id_agente));
	
	if ($has_remote_conf === true) {
		/* Plugins */
		$pluginstab = enterprise_hook ('plugins_tab');
		if ($pluginstab == -1)
			$pluginstab = "";
	}
	else {
		$pluginstab = "";
	}
	
	/* Collection */
	$collectiontab = enterprise_hook('collection_tab');
	
	if ($collectiontab == -1)
		$collectiontab = "";
	
	/* Group tab */
	
	$grouptab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&ag_group='.$group.'">'
		. html_print_image ("images/group.png", true, array( "title" => __('Group')))
		. '</a>';
	
	$grouptab['active'] = false;
	
	$gistab = "";
	
	/* GIS tab */
	if ($config['activate_gis']) {
		
		$gistab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=gis&id_agente='.$id_agente.'">'
			. html_print_image ("images/gm_gis.png", true, array ( "title" => __('GIS data')))
			. '</a>';
		
		if ($tab == "gis")
			$gistab['active'] = true;
		else
			$gistab['active'] = false;
	}
	
	/* Agent wizard tab */
	$agent_wizard['text'] = '<a href="javascript:" class="agent_wizard_tab">'
		. html_print_image ("images/wand_agent.png", true, array ( "title" => __('Agent wizard')))
		. '</a>';
	
	if ($tab == "agent_wizard")
		$agent_wizard['active'] = true;
	else
		$agent_wizard['active'] = false;
	
	$total_incidents = agents_get_count_incidents($id_agente);
	
	/* Incident tab */
	if ($config['integria_enabled'] == 0 and $total_incidents > 0) {
		$incidenttab['text'] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=incident&amp;id_agente='.$id_agente.'">' 
			. html_print_image ("images/book_edit.png", true, array ("title" =>__('Incidents')))
			. '</a>';
		
		if ($tab == 'incident')
			$incidenttab['active'] = true;
		else
			$incidenttab['active'] = false;
	}
	
	if (check_acl ($config["id_user"], $group, "AW", $id_agente)) {
		if ($has_remote_conf) {
			$agent_name = agents_get_name($id_agente);
			$agent_name = io_safe_output($agent_name);
			$agent_md5 = md5 ($agent_name, false);
			
			$remote_configuration_tab['text'] =
				'<a href="index.php?' .
					'sec=gagente&amp;' .
					'sec2=godmode/agentes/configurar_agente&amp;' .
					'tab=remote_configuration&amp;' .
					'id_agente=' . $id_agente . '&amp;' .
					'disk_conf=' . $agent_md5 . '">' 
				. html_print_image ("images/remote_configuration.png", true,
					array("title" =>__('Remote configuration')))
				. '</a>';
			if ($tab == 'remote_configuration')
				$remote_configuration_tab['active'] = true;
			else
				$remote_configuration_tab['active'] = false;
			
			
			$onheader = array('view' => $viewtab,
				'separator' => "",
				'main' => $maintab,
				'remote_configuration' => $remote_configuration_tab,
				'module' => $moduletab,
				'alert' => $alerttab,
				'template' => $templatetab,
				'inventory' => $inventorytab,
				'pluginstab' => $pluginstab,
				'collection'=> $collectiontab,
				'group' => $grouptab,
				'gis' => $gistab,
				'agent_wizard' => $agent_wizard);
		}
		else {
			$onheader = array('view' => $viewtab,
				'separator' => "",
				'main' => $maintab,
				'module' => $moduletab,
				'alert' => $alerttab,
				'template' => $templatetab,
				'inventory' => $inventorytab,
				'pluginstab' => $pluginstab,
				'collection'=> $collectiontab,
				'group' => $grouptab,
				'gis' => $gistab,
				'agent_wizard' => $agent_wizard);
		}
		
		// Only if the agent has incidents associated show incidents tab
		if ($total_incidents) {
			$onheader['incident'] = $incidenttab;
		}
	}
	else {
		$onheader = array('view' => $viewtab,
			'separator' => "",
			'module' => $moduletab,
			'alert' => $alerttab);
	}
	
	//Extensions tabs
	foreach ($config['extensions'] as $extension) {
		if (isset($extension['extension_god_tab']) && check_acl ($config["id_user"], $group, "AW", $id_agente)) {
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
	
	$help_header = '';
	// This add information to the header 
	switch ($tab) {
		case "main":
			$tab_description = '- '. __('Setup'); 
			break;
		case "collection":
			$tab_description = '- ' . __('Collection') ;
			$help_header = 'collection_tab';
			break;
		case "inventory":
			$tab_description = '- ' . __('Inventory');
			$help_header = 'inventory_tab';
			break;
		case "plugins":
			$tab_description = '- ' . __('Agent plugins');
			$help_header = 'plugins_tab';
			break;
		case "module":
			$tab_description = '- '. __('Modules');
			break;
		case "alert":
			$tab_description = '- ' . __('Alert');
			$help_header = 'manage_alert_list';
			break;
		case "template":
			$tab_description = '- ' . __('Templates');
			$help_header = 'template_tab';
			break;
		case "gis":
			$tab_description = '- ' . __('Gis');
			$help_header = 'gis_tab';
			break;
		case "incident":
			$tab_description = '- ' . __('Incidents');
			break;
		case "remote_configuration":
			$tab_description = '- ' . __('Remote configuration');
			break;
		case "agent_wizard":
			switch(get_parameter('wizard_section')) {
				case 'snmp_explorer':
					$tab_description = '- ' . __('SNMP Wizard');
					break;
				case 'snmp_interfaces_explorer':
					$tab_description = '- ' . __('SNMP Interfaces wizard');
					break;
				case 'wmi_explorer':
					$tab_description = '- ' . __('WMI Wizard');
					break;
			}
			break;
		case "extension":
			$id_extension = get_parameter('id_extension', '');
			switch ($id_extension) {
				case "snmp_explorer":
					$tab_description = '- ' . __('SNMP explorer');
					$help_header = 'snmp_explorer';
					break;
			}
			break;
		default:
			break;
	}
	
	ui_print_page_header (
		agents_get_name ($id_agente) . ' ' . $tab_description,
		"images/setup.png", false, $help_header , true, $onheader);
}
else {
	// Create agent 
	ui_print_page_header (__('Agent manager'), "images/bricks.png",
		false, "create_agent", true);
}

$delete_conf_file = (bool) get_parameter('delete_conf_file');

if ($delete_conf_file) {
	$correct = false;
	// Delete remote configuration
	if (isset ($config["remote_config"])) {
		$agent_md5 = md5(io_safe_output(agents_get_name ($id_agente,'none')), FALSE);
		
		if (file_exists ($config["remote_config"] . "/md5/" . $agent_md5 . ".md5")) {
			// Agent remote configuration editor
			$file_name = $config["remote_config"] . "/conf/" . $agent_md5 . ".conf";
			$correct = @unlink ($file_name);
			
			$file_name = $config["remote_config"] . "/md5/" . $agent_md5 . ".md5";
			$correct = @unlink ($file_name);
		}
	}
	
	ui_print_result_message ($correct,
		__('Conf file deleted successfully'),
		__('Could not delete conf file'));
}


// Show agent creation results
if ($create_agent) {
	if (!isset($agent_creation_error)) {
		$agent_creation_error = __('Could not be created');
	}
	
	ui_print_result_message ($agent_created_ok,
		__('Successfully created'),
		$agent_creation_error);
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
		$error = " - " . __('No data to normalize');
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
	$direccion_agente = trim(io_safe_output($direccion_agente));
	$direccion_agente = io_safe_input($direccion_agente);
	$address_list = (string) get_parameter_post ("address_list", '');
	
	if ($address_list != $direccion_agente &&
		$direccion_agente == agents_get_address ($id_agente) &&
		$address_list != agents_get_address ($id_agente)) {
		
		//If we selected another IP in the drop down list to be 'primary': 
		// a) field is not the same as selectbox
		// b) field has not changed from current IP
		// c) selectbox is not the current IP
		
		if (!empty($address_list)) {
			$direccion_agente = $address_list;
		}
	}
	$grupo = (int) get_parameter_post ("grupo", 0);
	$intervalo = (int) get_parameter_post ("intervalo", SECONDS_5MINUTES);
	$comentarios = str_replace('`','&lsquo;',(string) get_parameter_post ("comentarios", ""));
	$modo = (bool) get_parameter_post ("modo", 0); //Mode: Learning or Normal
	$id_os = (int) get_parameter_post ("id_os");
	$disabled = (bool) get_parameter_post ("disabled");
	$server_name = (string) get_parameter_post ("server_name", "");
	$parent_name = (string) get_parameter_post ("id_parent");
	$id_parent = (int) agents_get_agent_id ($parent_name);
	$custom_id = (string) get_parameter_post ("custom_id", "");
	$cascade_protection = (int) get_parameter_post ("cascade_protection", 0);
	$icon_path = (string) get_parameter_post ("icon_path",'');
	$update_gis_data = (int) get_parameter_post("update_gis_data", 0);
	$url_description = (string) get_parameter("url_description");
	$quiet = (int) get_parameter("quiet", 0);
	
	$old_interval = db_get_value('intervalo', 'tagente', 'id_agente', $id_agente);
	$fields = db_get_all_fields_in_table('tagent_custom_fields');
	
	if ($fields === false) $fields = array();
	
	$field_values = array();
	
	foreach ($fields as $field) {
		$field_values[$field['id_field']] = (string) get_parameter_post ('customvalue_'.$field['id_field'], '');
	}
	
	
	foreach ($field_values as $key => $value) {
		$old_value = db_get_all_rows_filter('tagent_custom_data',
			array('id_agent' => $id_agente, 'id_field' => $key));
		
		if ($old_value === false) {
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
		ui_print_error_message(__('No agent name specified'));
		//If there is an agent with the same name, but a different ID
	}
	elseif (agents_get_agent_id ($nombre_agente) > 0 &&
		agents_get_agent_id ($nombre_agente) != $id_agente) {
		ui_print_error_message(__('There is already an agent in the database with this name'));
	}
	else {
		//If different IP is specified than previous, add the IP
		if ($direccion_agente != '' &&
			$direccion_agente != agents_get_address ($id_agente)) {
			agents_add_address ($id_agente, $direccion_agente);
		}
		
		$action_delete_ip = (bool)get_parameter('delete_ip', false);
		//If IP is set for deletion, delete first
		if ($action_delete_ip) {
			$delete_ip = get_parameter_post ("address_list");
			
			$direccion_agente = agents_delete_address($id_agente, $delete_ip);
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
				'update_gis_data' => $update_gis_data,
				'url_address' => $url_description,
				'quiet' => $quiet),
			array ('id_agente' => $id_agente));
			
		if ($result === false) {
			ui_print_error_message(
				__('There was a problem updating the agent'));
		}
		else {
			
			if ($old_interval != $intervalo) {
				enterprise_hook('config_agents_update_config_interval', array($id_agente, $intervalo));
			}
			
			$info = 'Group: ' . $grupo . ' Interval: ' . $intervalo .
				' Comments: ' . $comentarios . ' Mode: ' . $modo . 
				' ID OS: ' . $id_os . ' Disabled: ' . $disabled . 
				' Server Name: ' . $server_name . ' ID parent: ' . $id_parent .
				' Custom ID: ' . $custom_id . ' Cascade Protection: ' . $cascade_protection .
				' Icon Path: ' . $icon_path . 'Update GIS data: ' .$update_gis_data .
				' Url description: ' . $url_description .
				' Quiet: ' . (int)$quiet;
			
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
	if (!check_acl ($config["id_user"], $id_grupo, "AW") && !check_acl ($config["id_user"], $id_grupo, "AD")) {
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
	$url_description = $agent["url_address"];
	$quiet = $agent["quiet"];
}

$update_module = (bool) get_parameter ('update_module');
$create_module = (bool) get_parameter ('create_module');
$delete_module = (bool) get_parameter ('delete_module');
$enable_module = (int) get_parameter ('enable_module');
$disable_module = (int) get_parameter ('disable_module');
//It is the id_agent_module to duplicate
$duplicate_module = (int) get_parameter ('duplicate_module');
$edit_module = (bool) get_parameter ('edit_module');

// GET DATA for MODULE UPDATE OR MODULE INSERT
if ($update_module || $create_module) {
	$id_grupo = agents_get_agent_group ($id_agente);
	
	if (!check_acl ($config["id_user"], $id_grupo, "AW")) {
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
	//$prediction_module = 0;

	$max_timeout = (int) get_parameter ('max_timeout');
	$max_retries = (int) get_parameter ('max_retries');
	$min = (int) get_parameter_post ("min");
	$max = (int) get_parameter ('max');
	$interval = (int) get_parameter ('module_interval', $intervalo);
	$ff_interval = (int) get_parameter ('module_ff_interval');
	$quiet_module = (int) get_parameter ('quiet_module');
	$id_plugin = (int) get_parameter ('id_plugin');
	$id_export = (int) get_parameter ('id_export');
	$disabled = (bool) get_parameter ('disabled');
	$tcp_send = (string) get_parameter ('tcp_send');
	$tcp_rcv = (string) get_parameter ('tcp_rcv');
	$tcp_port = (int) get_parameter ('tcp_port');
	// Correction in order to not insert 0 as port
	$is_port_empty = get_parameter ('tcp_port', '');
	if ($is_port_empty === '')
		$tcp_port = NULL;
	$configuration_data = (string) get_parameter ('configuration_data');
	$old_configuration_data = (string) get_parameter ('old_configuration_data');
	$new_configuration_data = '';
	
	$custom_string_1_default = '';
	$custom_string_2_default = '';
	$custom_string_3_default = '';
	$custom_integer_1_default = 0;
	$custom_integer_2_default = 0;
	if ($update_module) {
		$module = modules_get_agentmodule ($id_agent_module);
		
		$custom_string_1_default = $module['custom_string_1'];
		$custom_string_2_default = $module['custom_string_2'];
		$custom_string_3_default = $module['custom_string_3'];
		$custom_integer_1_default = $module['custom_integer_1'];
		$custom_integer_2_default = $module['custom_integer_2'];
	}
	$custom_string_1 = (string) get_parameter ('custom_string_1', $custom_string_1_default);
	$custom_string_2 = (string) get_parameter ('custom_string_2', $custom_string_2_default);
	$custom_string_3 = (string) get_parameter ('custom_string_3', $custom_string_3_default);
	$custom_integer_1 = (int) get_parameter ('prediction_module', $custom_integer_1_default);
	$custom_integer_2 = (int) get_parameter ('custom_integer_2', $custom_integer_2_default);
	
	// Get macros
	$macros = (string) get_parameter ('macros');
	
	if (!empty($macros)) {
		$macros = json_decode(base64_decode($macros), true);
		
		foreach($macros as $k => $m) {
			$macros[$k]['value'] = get_parameter($m['macro'], '');
		}
		
		$macros = io_json_mb_encode($macros);
		
		$conf_array = explode("\n",$configuration_data);
		foreach($conf_array as $line) {
			if(preg_match("/^module_name\s*(.*)/", $line, $match)) {
				$new_configuration_data .= "module_name $name\n";
			}
			// We delete from conf all the module macros starting with _field
			else if(!preg_match("/^module_macro_field.*/", $line, $match)) {
				$new_configuration_data .= "$line\n";
			}
		}
		
		$macros_for_data = enterprise_hook('config_agents_get_macros_data_conf', array($_POST));
		
		if($macros_for_data !== ENTERPRISE_NOT_HOOK && $macros_for_data != '') {
			// Add macros to configuration file
			$new_configuration_data = str_replace('module_end', $macros_for_data."module_end", $new_configuration_data);
		}
		
		$configuration_data = $new_configuration_data;
	}
	
	// Services are an enterprise feature, 
	// so we got the parameters using this function.
	
	enterprise_hook ('get_service_synthetic_parameters');
	
	$agent_name = (string) get_parameter('agent_name',
		agents_get_name($id_agente));
	
	$snmp_community = (string) get_parameter ('snmp_community');
	$snmp_oid = (string) get_parameter ('snmp_oid');
	//Change double quotes by single
	$snmp_oid = preg_replace('/&quot;/', '&#039;', $snmp_oid);
	
	if (empty ($snmp_oid)) {
		/* The user did not set any OID manually but did a SNMP walk */
		$snmp_oid = (string) get_parameter ('select_snmp_oid');
	}
	
	if ($id_module_type >= 15 && $id_module_type <= 18) {
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
	$ff_event_normal = (int) get_parameter ('ff_event_normal');
	$ff_event_warning = (int) get_parameter ('ff_event_warning');
	$ff_event_critical = (int) get_parameter ('ff_event_critical');
	$each_ff = (int) get_parameter ('each_ff');
	$ff_timeout = (int) get_parameter ('ff_timeout');
	$unit = (string) get_parameter('unit');
	$id_tag = (array) get_parameter('id_tag_selected');
	$serialize_ops = (string) get_parameter('serialize_ops');
	$critical_instructions = (string) get_parameter('critical_instructions');
	$warning_instructions = (string) get_parameter('warning_instructions');
	$unknown_instructions = (string) get_parameter('unknown_instructions');
	$critical_inverse = (int) get_parameter('critical_inverse');
	$warning_inverse = (int) get_parameter('warning_inverse');
	
	$id_category = (int) get_parameter('id_category');
	
	$hour = get_parameter('hour');
	$minute = get_parameter('minute');
	$mday = get_parameter('mday');
	$month = get_parameter('month');
	$wday = get_parameter('wday');
	$cron_interval = "$minute $hour $mday $month $wday";
	
	if ($prediction_module != 3) {
		unset($serialize_ops);
		enterprise_hook('modules_delete_synthetic_operations', array($id_agent_module));
	}
	
	$active_snmp_v3 = get_parameter('active_snmp_v3');
	if ($active_snmp_v3) {
		//LOST CODE?
	}
	
	$throw_unknown_events = (bool)get_parameter('throw_unknown_events', false);
	//Set the event type that can show.
	$disabled_types_event = array(EVENTS_GOING_UNKNOWN => (int)!$throw_unknown_events);
	$disabled_types_event = io_json_mb_encode($disabled_types_event);
	
	$module_macro_names = (array) get_parameter('module_macro_names', array());
	$module_macro_values = (array) get_parameter('module_macro_values', array());
	$module_macros = modules_get_module_macros_json ($module_macro_names, $module_macro_values);
	
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
		'module_ff_interval' => $ff_interval,
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
		'max_retries' => $max_retries,
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
		'min_ff_event_normal' => $ff_event_normal,
		'min_ff_event_warning' => $ff_event_warning,
		'min_ff_event_critical' => $ff_event_critical,
		'each_ff' => $each_ff,
		'ff_timeout' => $ff_timeout,
		'unit' => $unit,
		'macros' => $macros,
		'quiet' => $quiet_module,
		'critical_instructions' => $critical_instructions,
		'warning_instructions' => $warning_instructions,
		'unknown_instructions' => $unknown_instructions,
		'critical_inverse' => $critical_inverse,
		'warning_inverse' => $warning_inverse,
		'cron_interval' => $cron_interval,
		'id_category' => $id_category,
		'disabled_types_event' => $disabled_types_event,
		'module_macros' => $module_macros);
	
	// In local modules, the interval is updated by agent
	$module_kind = (int) get_parameter ('moduletype');
	if ($module_kind == MODULE_DATA) {
		unset($values['module_interval']);
	}

	if ($prediction_module == 3 && $serialize_ops == '') {
		$result = false;
	}
	else {
		$result = modules_update_agent_module ($id_agent_module,
			$values, false, $id_tag);
	}
	
	if (is_error($result)) {
		switch($result) {
			case ERR_EXIST:
				$msg = __('There was a problem updating module. Another module already exists with the same name.');
				break;
			case ERR_INCOMPLETE:
				$msg = __('There was a problem updating module. Some required fields are missed: (name)');
				break;
			case ERR_NOCHANGES:
				$msg = __('There was a problem updating module. "No change"');
				break;
			case ERR_DB:
			case ERR_GENERIC:
			default:
				$msg = __('There was a problem updating module. Processing error');
				break;
		}
		$result = false;
		ui_print_error_message($msg);
		
		$edit_module = true;
		
		db_pandora_audit("Agent management",
			"Fail to try update module '$name' for agent ".$agent["nombre"]);
	}
	else {
		if ($prediction_module == 3) {
			enterprise_hook('modules_create_synthetic_operations',
				array($id_agent_module, $serialize_ops));
		}
		
		// Update the module interval
		cron_update_module_interval ($id_agent_module, $cron_interval);
		
		ui_print_success_message(__('Module successfully updated'));
		$id_agent_module = false;
		$edit_module = false;
		
		$agent = db_get_row ('tagente', 'id_agente', $id_agente);
		
		db_pandora_audit("Agent management",
			"Updated module '$name' for agent ".$agent["nombre"], false, false, io_json_mb_encode($values));
	}
}

// MODULE INSERT
// =================
if ($create_module) {
	if (isset ($_POST["combo_snmp_oid"])) {
		$combo_snmp_oid = get_parameter_post ("combo_snmp_oid");
	}
	if ($snmp_oid == "") {
		$snmp_oid = $combo_snmp_oid;
	}
	
	$id_module = (int) get_parameter ('id_module');
	//Commented because can't create prediction modules
/*
	if ($id_module == 5) {
		$prediction_module = 1;
	}
*/
	
	switch ($config["dbtype"]) {
		case "oracle":
			if (empty($description) || !isset($description)) {
				$description = ' ';
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
		'module_ff_interval' => $ff_interval,
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
		'max_retries' => $max_retries,
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
		'min_ff_event_normal' => $ff_event_normal,
		'min_ff_event_warning' => $ff_event_warning,
		'min_ff_event_critical' => $ff_event_critical,
		'each_ff' => $each_ff,
		'ff_timeout' => $ff_timeout,
		'unit' => $unit,
		'macros' => $macros,
		'quiet' => $quiet_module,
		'critical_instructions' => $critical_instructions,
		'warning_instructions' => $warning_instructions,
		'unknown_instructions' => $unknown_instructions,
		'critical_inverse' => $critical_inverse,
		'warning_inverse' => $warning_inverse,
		'cron_interval' => $cron_interval,
		'id_category' => $id_category,
		'disabled_types_event' => $disabled_types_event,
		'module_macros' => $module_macros);
	
	if ($prediction_module == 3 && $serialize_ops == '') {
		$id_agent_module = false;
	}
	else {
		$id_agent_module = modules_create_agent_module (
			$id_agente, $name, $values, false, $id_tag);
	}
	
	if (is_error($id_agent_module)) {
		switch ($id_agent_module) {
			case ERR_EXIST:
				$msg = __('There was a problem adding module. Another module already exists with the same name.');
				break;
			case ERR_INCOMPLETE:
				$msg = __('There was a problem adding module. Some required fields are missed : (name)');
				break;
			case ERR_DB:
			case ERR_GENERIC:
			default:
				$msg = __('There was a problem adding module. Processing error');
				break;
		}
		$id_agent_module = false;
		ui_print_error_message($msg);
		$edit_module = true;
		$moduletype = $id_module;
		db_pandora_audit("Agent management",
			"Fail to try added module '$name' for agent ".$agent["nombre"]);
	}
	else {
		if ($prediction_module == 3) {
			enterprise_hook('modules_create_synthetic_operations', array($id_agent_module, $serialize_ops));
		}
		
		// Update the module interval
		cron_update_module_interval ($id_agent_module, $cron_interval);
		
		ui_print_success_message(__('Module added successfully'));
		$id_agent_module = false;
		$edit_module = false;
		
		$info = '';
		
		$agent = db_get_row ('tagente', 'id_agente', $id_agente);
		db_pandora_audit("Agent management",
			"Added module '$name' for agent ".$agent["nombre"], false, true, io_json_mb_encode($values));
	}
}

// MODULE DELETION
// =================
if ($delete_module) { // DELETE agent module !
	$id_borrar_modulo = (int) get_parameter_get ("delete_module",0);
	$module_data = db_get_row_sql ('SELECT *
		FROM tagente_modulo, tagente_estado
		WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
			AND tagente_modulo.id_agente_modulo=' . $id_borrar_modulo);
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
	
	// First delete from tagente_modulo -> if not successful, increment
	// error. NOTICE that we don't delete all data here, just marking for deletion
	// and delete some simple data.
	
	$values = array(
		'nombre' => 'pendingdelete',
		'disabled' => 1,
		'delete_pending' => 1);
	$result = db_process_sql_update('tagente_modulo',
		$values, array('id_agente_modulo' => $id_borrar_modulo));
	if ($result === false) {
		$error++;
	}
	else {
		// Set flag to update module status count
		db_process_sql ('UPDATE tagente
			SET update_module_count = 1, update_alert_count = 1
			WHERE id_agente = ' . $module_data['id_agente']);
	}
	
	$result = db_process_sql_delete('tagente_estado',
		array('id_agente_modulo' => $id_borrar_modulo));
	if ($result === false)
		$error++;
	
	$result = db_process_sql_delete('tagente_datos_inc',
		array('id_agente_modulo' => $id_borrar_modulo));
	if ($result === false)
		$error++;
	
	if (alerts_delete_alert_agent_module($id_borrar_modulo) === false)
		$error++;
	
	$result = db_process_delete_temp('ttag_module', 'id_agente_modulo',
		$id_borrar_modulo);
	if ($result === false)
		$error++;
	
	// Trick to detect if we are deleting a synthetic module (avg or arithmetic)
	// If result is empty then module doesn't have this type of submodules
	$ops_json = enterprise_hook('modules_get_synthetic_operations', array($id_borrar_modulo));
	$result_ops_synthetic = json_decode($ops_json);
	if (!empty($result_ops_synthetic)) {
		$result = enterprise_hook('modules_delete_synthetic_operations', array($id_borrar_modulo));
		if ($result === false)
			$error++;
	} // Trick to detect if we are deleting components of synthetics modules (avg or arithmetic)
	else {
		$result_components = enterprise_hook('modules_get_synthetic_components', array($id_borrar_modulo));
		$count_components = 1;
		if (!empty($result_components)) {
			// Get number of components pending to delete to know when it's needed to update orders 
			$num_components = count($result_components);
			$last_target_module = 0;
			foreach ($result_components as $id_target_module) {
				// Detects change of component or last component to update orders
				if (($count_components == $num_components) or
					($last_target_module != $id_target_module)) {
					$update_orders = true;
				}
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
		ui_print_error_message(__('There was a problem deleting the module'));
	}
	else {
		ui_print_success_message(__('Module deleted succesfully'));
		
		$agent = db_get_row ('tagente', 'id_agente', $id_agente);
		db_pandora_audit("Agent management",
			"Deleted module '".$module_data["nombre"]."' for agent ".$agent["nombre"]);
	}
}

// MODULE DUPLICATION
// ==================
if (!empty($duplicate_module)) { // DUPLICATE agent module !
	$id_duplicate_module = $duplicate_module;
	
	$original_name = modules_get_agentmodule_name($id_duplicate_module);
	$copy_name = io_safe_input(sprintf(__('copy of %s'), io_safe_output($original_name)));
	
	$cont = 0;
	$exists = true;
	while($exists) {
		$exists = (bool)db_get_value ('id_agente_modulo', 'tagente_modulo',
			'nombre', $copy_name);
		if ($exists) {
			$cont++;
			$copy_name = io_safe_input(
				sprintf(__('copy of %s (%d)'), io_safe_output($original_name), $cont));
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

// MODULE ENABLE/DISABLE
// =====================
if ($enable_module) {
	$result = modules_change_disabled($enable_module, 0);
	
	if ($result === NOERR) {	
		enterprise_hook('config_agents_enable_module_conf', array($id_agente, $enable_module));
		db_pandora_audit("Module management", 'Enable  ' . $enable_module);
	}
	else {
		db_pandora_audit("Module management", 'Fail to enable ' . $enable_module);
	}
	
	ui_print_result_message ($result,
		__('Successfully enabled'), __('Could not be enabled'));
}

if ($disable_module) {
	$result = modules_change_disabled($disable_module, 1);
	
	if ($result === NOERR) {
		enterprise_hook('config_agents_disable_module_conf', array($id_agente, $disable_module));
		db_pandora_audit("Module management", 'Disable  ' . $disable_module);
	}
	else {
		db_pandora_audit("Module management", 'Fail to disable ' . $disable_module);
	}
	
	ui_print_result_message ($result,
		__('Successfully disabled'), __('Could not be disabled'));
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
	
	$previusAgentGISData = db_get_row_sql("
		SELECT *
		FROM tgis_data_status
		WHERE tagente_id_agente = " . $idAgente);
	
	db_process_sql_update('tagente', array('update_gis_data' => $updateGisData),
		array('id_agente' => $idAgente));
		
	if ($previusAgentGISData !== false) {
		db_process_sql_insert('tgis_data_history', array(
			"longitude" => $previusAgentGISData['stored_longitude'],
			"latitude" => $previusAgentGISData['stored_latitude'],
			"altitude" => $previusAgentGISData['stored_altitude'],
			"start_timestamp" => $previusAgentGISData['start_timestamp'],
			"end_timestamp" => date( 'Y-m-d H:i:s'),
			"description" => __('Save by Pandora Console'),
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
			"description" => __('Update by Pandora Console')),
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
			"description" => __('Insert by Pandora Console')
		));
	}
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
	case "incident":
		require("agent_incidents.php");
		break;
	case "remote_configuration":
		enterprise_include("godmode/agentes/agent_disk_conf_editor.php");
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
			ui_print_error_message (__('Invalid tab specified'));
		}
		break;
	case "agent_wizard":
		require("agent_wizard.php");
		break;
	default:
		if (enterprise_hook ('switch_agent_tab', array ($tab))) {
			//This will make sure that blank pages will have at least some
			//debug info in them - do not translate debug
			ui_print_error_message (__('Invalid tab specified'));
		}
		break;
}

// Hidden subtab layer
echo '<div id="agent_wizard_subtabs">';
echo '<ul class="mn subsubmenu" style="background: #555555;">';

echo '<li class="nomn tab_godmode" style="text-align: center;">';
echo '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=snmp_explorer&id_agente='.$id_agente.'">'
		. html_print_image ("images/wand_snmp.png", true, array ( "title" => __('SNMP Wizard')))
		. '</a>';
echo '<li>';

echo '<li class="nomn tab_godmode" style="text-align: center;">';
echo '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=snmp_interfaces_explorer&id_agente='.$id_agente.'">'
		. html_print_image ("images/wand_interfaces.png", true, array ( "title" => __('SNMP Interfaces wizard')))
		. '</a>';
echo '<li>';

echo '<li class="nomn tab_godmode" style="text-align: center;">';
echo '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=wmi_explorer&id_agente='.$id_agente.'">'
		. html_print_image ("images/wand_wmi.png", true, array ( "title" => __('WMI Wizard')))
		. '</a>';
echo '<li>';

echo '</ul>';
echo '</div>';
?>
<script type="text/javascript">
	/* <![CDATA[ */
	var wizard_tab_showed = 0;
	
	$(document).ready (function () {
		// Control the tab and subtab hover. When mouse leave one, 
		// check if is hover the other before hide the subtab
		$('.agent_wizard_tab').hover(agent_wizard_tab_show, agent_wizard_tab_hide);
		
		$('#agent_wizard_subtabs').hover(agent_wizard_tab_show, agent_wizard_tab_hide);
	});
	
	// Set the position and width of the subtab
	function agent_wizard_tab_setup() {		
		$('#agent_wizard_subtabs').css('left', $('.agent_wizard_tab').offset().left)
		$('#agent_wizard_subtabs').css('top', $('.agent_wizard_tab').offset().top + $('.agent_wizard_tab').height() + 4)
		$('#agent_wizard_subtabs').css('width', $('.agent_wizard_tab').width() + 6)
	}
	
	function agent_wizard_tab_show() {
		agent_wizard_tab_setup();
		wizard_tab_showed = wizard_tab_showed + 1;
		
		if(wizard_tab_showed == 1) {
			$('#agent_wizard_subtabs').show("fast");
		}
	}
	
	function agent_wizard_tab_hide() {
		wizard_tab_showed = wizard_tab_showed - 1;
		
		setTimeout(function() {
			if(wizard_tab_showed <= 0) {
				$('#agent_wizard_subtabs').hide("fast");
			}
		},500);
	}
	
	$(window).resize(function() {
		agent_wizard_tab_setup();
	});
	
	/* ]]> */
</script>
