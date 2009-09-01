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


// Load global vars
require_once ("include/config.php");
enterprise_include ('godmode/agentes/configurar_agente.php');

check_login ();

//See if id_agente is set (either POST or GET, otherwise -1
$id_agente = (int) get_parameter ("id_agente");
$group = 0;
if ($id_agente)
	$group = get_agent_group ($id_agente);

if (! give_acl ($config["id_user"], $group, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access agent manager");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_modules.php');

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
	$id_parent = (int) get_agent_id ($id_parent);
	$server_name = (string) get_parameter_post ("server_name");
	$id_os = (int) get_parameter_post ("id_os");
	$disabled = (int) get_parameter_post ("disabled");
	$custom_id = (string) get_parameter_post ("custom_id",'');
	$cascade_protection = (int) get_parameter_post ("cascade_protection", 0);

	// Check if agent exists (BUG WC-50518-2)
	if ($nombre_agente == "") {
		$agent_creation_error = __('No agent name specified');
		$agent_created_ok = 0;
	} elseif (get_agent_id ($nombre_agente)) {
		$agent_creation_error = __('There is already an agent in the database with this name');
		$agent_created_ok = 0;
	} else {
		$id_agente = process_sql_insert ('tagente', 
			array ('nombre' => $nombre_agente,
				'direccion' => $direccion_agente,
				'id_grupo' => $grupo, 'intervalo' => $intervalo,
				'comentarios' => $comentarios, 'modo' => $modo,
				'id_os' => $id_os, 'disabled' => $disabled,
				'cascade_protection' => $cascade_protection,
				'server_name' => $server_name,
				'id_parent' => $id_parent, 'custom_id' => $custom_id));
		enterprise_hook ('update_agent', array ($id_agente));
		if ($id_agente !== false) {
			// Create address for this agent in taddress
			agent_add_address ($id_agente, $direccion_agente);
			
			$agent_created_ok = true;
			
			// Create special module agent_keepalive
			$id_agent_module = process_sql_insert ('tagente_modulo', 
				array ('nombre' => 'agent_keepalive',
					'id_agente' => $id_agente,
					'id_tipo_modulo' => 100,
					'descripcion' => __('Agent keepalive monitor'),
					'id_modulo' => 1,
					'min_warning' => 0,
					'max_warning' => 1));
			
			if ($id_agent_module !== false) {
				// Create agent_keepalive in tagente_estado table
				$result = process_sql_insert ('tagente_estado', 
					array ('id_agente_modulo' => $id_agent_module,
						'datos' => '',
						'timestamp' => 0,
						'estado' => 0,
						'id_agente' => $id_agente,
						'last_try' => 0,
						'utimestamp' => 0,
						'current_interval' => 0,
						'running_by' => 0,
						'last_execution_try' => 0));
				if ($result === false)
					$agent_created_ok = false;
			} else {
				$agent_created_ok = false;
			}
		} else {
			$id_agente = 0;
			$agent_creation_error = __('Could not be created');
		}
	}
}

// Show tabs
$img_style = array ("class" => "top", "width" => 16);

if ($id_agente) {
	echo '<div id="menu_tab_frame"><div id="menu_tab_left"><ul class="mn">';
	echo '<li class="nomn"><a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$id_agente.'">';
	print_image ("images/setup.png", false, $img_style);
	echo '&nbsp; '.mb_substr(get_agent_name ($id_agente), 0, 15) .'</a>';
	echo "</li></ul></div>";

	echo '<div id="menu_tab"><ul class="mn"><li class="nomn">';
	echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'">';
	print_image ("images/zoom.png", false, $img_style);
	echo '&nbsp;'.__('View').'</a></li>';

	echo '<li class="'.($tab == "main" ? 'nomn_high' : 'nomn').'">';
	echo '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=main&amp;id_agente='.$id_agente.'">';
	print_image ("images/cog.png", false, $img_style);
	echo '&nbsp; '.__('Setup').'</a></li>';

	echo '<li class="'.($tab == "module" ? 'nomn_high' : 'nomn').'">';
	echo '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=module&amp;id_agente='.$id_agente.'">';
	print_image ("images/lightbulb.png", false, $img_style);
	echo '&nbsp; '.__('Modules').'</a></li>';

	echo '<li class="'.($tab == "alert" ? 'nomn_high' : 'nomn').'">';
	echo '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=alert&amp;id_agente='.$id_agente.'">';
	print_image ("images/bell.png", false, $img_style);
	echo '&nbsp; '.__('Alerts').'</a></li>';

	echo '<li class="'.($tab == "template" ? 'nomn_high' : 'nomn').'">';
	echo '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=template&amp;id_agente='.$id_agente.'">';
	print_image ("images/network.png", false, $img_style);
	echo '&nbsp; '.__('Templates').'</a></li>';

	enterprise_hook ('inventory_tab');

	echo '<li class="nomn">';
	echo '<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&ag_group='.$group.'">';
	print_image ("images/agents_group.png", false, $img_style);
        echo '&nbsp; '.__('Group').'</a></li>';

	echo "</ul></div></div>";
	
	// Make some space between tabs and title
	// IE might not always show an empty div, added space
	echo '<div style="height: 25px;">&nbsp;</div>';
}

// Show agent creation results
if ($create_agent) {
	print_result_message ($agent_created_ok,
		__('Successfully created'),
		__('Could not be created'));
}

// Fix / Normalize module data
if (isset( $_GET["fix_module"])) { 
	$id_module = get_parameter_get ("fix_module",0);
	// get info about this module
	$media = get_agentmodule_data_average ($id_module, 30758400); //Get average over the year
	$media *= 1.3;
	$error = "";
	//If the value of media is 0 or something went wrong, don't delete
	if (!empty ($media)) {
		$sql = sprintf ("DELETE FROM tagente_datos WHERE datos > %f AND id_agente_modulo = %d", $media, $id_module);
		$result = process_sql ($sql);
	} else {
		$result = false;
		$error = " - ".__('No data to normalize');
	}
	
	print_result_message ($result,
		__('Deleted data above %d', $media),
		__('Error normalizing module %s', $error));
}

// Update AGENT
if (isset($_POST["update_agent"])) { // if modified some agent paramenter
	$id_agente = (int) get_parameter_post ("id_agente");
	$nombre_agente = (string) get_parameter_post ("agente", "");
	$direccion_agente = (string) get_parameter_post ("direccion", '');
	$address_list = (string) get_parameter_post ("address_list", '');
	if ($address_list != $direccion_agente && $direccion_agente == get_agent_address ($id_agente) && $address_list != get_agent_address ($id_agente)) {
		//If we selected another IP in the drop down list to be 'primary': 
		// a) field is not the same as selectbox
		// b) field has not changed from current IP
		// c) selectbox is not the current IP
		if ($address_list != 0)
			$direccion_agente = $address_list;
	}
	$grupo = (int) get_parameter_post ("grupo", 0);
	$intervalo = (int) get_parameter_post ("intervalo", 300);
	$comentarios = (string) get_parameter_post ("comentarios", "");
	$modo = (bool) get_parameter_post ("modo", 0); //Mode: Learning or Normal
	$id_os = (int) get_parameter_post ("id_os");
	$disabled = (bool) get_parameter_post ("disabled");
	$server_name = (string) get_parameter_post ("server_name", "");
	$id_parent = (string) get_parameter_post ("id_parent");
	$id_parent = (int) get_agent_id ($id_parent);
	$custom_id = (string) get_parameter_post ("custom_id", "");
	$cascade_protection = (int) get_parameter_post ("cascade_protection", 0);
	
	//Verify if there is another agent with the same name but different ID
	if ($nombre_agente == "") { 
		echo '<h3 class="error">'.__('No agent name specified').'</h3>';	
	//If there is an agent with the same name, but a different ID
	} elseif (get_agent_id ($nombre_agente) > 0 && get_agent_id ($nombre_agente) != $id_agente) {
		echo '<h3 class="error">'.__('There is already an agent in the database with this name').'</h3>';
	} else {
		//If different IP is specified than previous, add the IP
		if ($direccion_agente != '' && $direccion_agente != get_agent_address ($id_agente))
			agent_add_address ($id_agente, $direccion_agente);
		
		//If IP is set for deletion, delete first
		if (isset ($_POST["delete_ip"])) {
			$delete_ip = get_parameter_post ("address_list");
			agent_delete_address ($id_agente, $delete_ip);
		}
	
		$result = process_sql_update ('tagente', 
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
				'custom_id' => $custom_id),
			array ('id_agente' => $id_agente));
			
		if ($result === false) {
			print_error_message (__('There was a problem updating the agent'));
		} else {
			enterprise_hook ('update_agent', array ($id_agente));
			print_success_message (__('Successfully updated'));
		}
	}
}

// Read agent data
// This should be at the end of all operation checks, to read the changes - $id_agente doesn't have to be retrieved
if ($id_agente) {
	//This has been done in the beginning of the page, but if an agent was created, this id might change
	$id_grupo = get_agent_group ($id_agente);
	if (give_acl ($config["id_user"], $id_grupo, "AW") != 1) {
		audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to admin an agent without access");
		require ("general/noaccess.php");
		exit;
	}
	
	$agent = get_db_row ('tagente', 'id_agente', $id_agente);
	if (empty ($agent)) {
		//Close out the page
		print_error_message (__('There was a problem loading the agent'));
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

}

$update_module = (bool) get_parameter ('update_module');
$create_module = (bool) get_parameter ('create_module');
$edit_module = (bool) get_parameter ('edit_module');

// GET DATA for MODULE UPDATE OR MODULE INSERT
if ($update_module || $create_module) {
	$id_grupo = get_agent_group ($id_agente);
	
	if (! give_acl ($config["id_user"], $id_grupo, "AW")) {
		audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation",
			"Trying to create a module without admin rights");
		require ("general/noaccess.php");
		exit;
	}
	$id_module_type = (int) get_parameter ('id_module_type');
	$name = (string) get_parameter ('name');
	$description = (string) get_parameter ('description');
	$id_module_group = (int) get_parameter ('id_module_group');
	$flag = (bool) get_parameter ('flag');
	$post_process = (float) get_parameter ('post_process');
	$prediction_module = (int) get_parameter ('prediction_module');
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
	$snmp_community = (string) get_parameter ('snmp_community');
	$snmp_oid = (string) get_parameter ('snmp_oid');
	if (empty ($snmp_oid)) {
		/* The user did not set any OID manually but did a SNMP walk */
		$snmp_oid = (string) get_parameter ('select_snmp_oid');
	}
	$ip_target = (string) get_parameter ('ip_target');
	$plugin_user = (string) get_parameter ('plugin_user');
	$plugin_pass = (string) get_parameter ('plugin_pass');
	$plugin_parameter = (string) get_parameter ('plugin_parameter');
	$custom_id = (string) get_parameter ('custom_id');
	$history_data = (int) get_parameter('history_data');
	$min_warning = (float) get_parameter ('min_warning');
	$max_warning = (float) get_parameter ('max_warning');
	$min_critical = (float) get_parameter ('min_critical');
	$max_critical = (float) get_parameter ('max_critical');
	$ff_event = (int) get_parameter ('ff_event');
}

// MODULE UPDATE
if ($update_module) {
	$id_agent_module = (int) get_parameter ('id_agent_module');
	
	$result = update_agent_module ($id_agent_module,
		array ('descripcion' => $description,
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
			'min_critical' => $min_critical,
			'max_critical' => $max_critical,
			'min_ff_event' => $ff_event));
	
	if ($result === false) {
		echo '<h3 class="error">'.__('There was a problem updating module').'</h3>';
		$edit_module = true;
	} else {
		echo '<h3 class="suc">'.__('Module successfully updated').'</h3>';
		$id_agent_module = false;
		$edit_module = false;
	}
}

// MODULE INSERT
if ($create_module) {
	if (isset ($_POST["combo_snmp_oid"])) {
		$combo_snmp_oid = get_parameter_post ("combo_snmp_oid");
	}
	if ($snmp_oid == ""){
		$snmp_oid = $combo_snmp_oid;
	}
	
	$id_module = (int) get_parameter ('id_module');
	
	$id_agent_module = create_agent_module ($id_agente, $name,
		array ('id_tipo_modulo' => $id_module_type,
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
			'min_critical' => $min_critical,
			'max_critical' => $max_critical,
			'min_ff_event' => $ff_event
		));
	
	if ($id_agent_module === false) {
		echo '<h3 class="error">'.__('There was a problem adding module').'</h3>';
		$edit_module = true;
		$moduletype = $id_module;
	} else {
		echo '<h3 class="suc">'.__('Module added successfully').'</h3>';
		$id_agent_module = false;
		$edit_module = false;
	}
}

// MODULE DELETION
// =================
if (isset ($_GET["delete_module"])){ // DELETE agent module !
	$id_borrar_modulo = (int) get_parameter_get ("delete_module",0);
	$id_grupo = (int) dame_id_grupo ($id_agente);	
	
	if (! give_acl ($config["id_user"], $id_grupo, "AW")) {
		audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation",
		"Trying to delete a module without admin rights");
		require ("general/noaccess.php");
		exit;
	}
	
	if ($id_borrar_modulo < 1) {
		audit_db ($config["id_user"],$REMOTE_ADDR, "HACK Attempt",
		"Expected variable from form is not correct");
		die ("Nice try buddy");
		exit;
	}
	
	//Init transaction
	$error = 0;
	process_sql_begin ();
	
	// First delete from tagente_modulo -> if not successful, increment
	// error. NOTICE that we don't delete all data here, just marking for deletion
	// and delete some simple data.
	
	if (process_sql ("UPDATE tagente_modulo SET nombre = 'pendingdelete', disabled = 1, delete_pending = 1 WHERE id_agente_modulo = ".$id_borrar_modulo) === false)
		$error++;
	
	if (process_sql ("DELETE FROM tagente_estado WHERE id_agente_modulo = ".$id_borrar_modulo) === false)
		$error++;

	if (process_sql ("DELETE FROM tagente_datos_inc WHERE id_agente_modulo = ".$id_borrar_modulo) === false)
		$error++;
	

	//Check for errors
	if ($error != 0) {
		process_sql_rollback ();
		print_error_message (__('There was a problem deleting the module'));
	} else {
		process_sql_commit ();
		print_success_message (__('Module deleted succesfully'));
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
		} else {
			require ("module_manager.php");
		}
		break;
	case "alert":
		/* Because $id_agente is set, it will show only agent alerts */
		require ("godmode/alerts/alert_list.php");
		break;
	case "template":
		require ("agent_template.php");
		break;
	default:
		if (enterprise_hook ('switch_agent_tab', array ($tab)))
			//This will make sure that blank pages will have at least some
			//debug info in them - do not translate debug
			print_error_message ("DEBUG: Invalid tab specified in ".__FILE__.":".__LINE__);
}
?>
