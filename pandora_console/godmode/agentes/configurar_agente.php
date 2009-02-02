<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
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


// Load global vars
require_once ("include/config.php");
enterprise_include ('godmode/agentes/configurar_agente.php');

check_login ();

//See if id_agente is set (either POST or GET, otherwise -1
$id_agente = (int) get_parameter ("id_agente", -1);

$group = get_agent_group ($id_agente);

if (! give_acl($config["id_user"], $group, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access agent manager");
	require ("general/noaccess.php");
	exit;
}

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
$direccion_agente = get_parameter ('direccion');
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
$id_network_server = 0;
$id_plugin_server = 0;
$id_prediction_server = 0;
$id_wmi_server = 0;
$grupo = 0;
$id_os = 0;
$custom_id = "";

$create_agent = (bool) get_parameter ('create_agent');

// Create agent
if ($create_agent) {
	$nombre_agente = (string) get_parameter_post ("agente");
	$direccion_agente = (string) get_parameter_post ("direccion");
	$grupo = (int) get_parameter_post ("grupo");
	$intervalo = (string) get_parameter_post ("intervalo", 300);
	$comentarios = (string)get_parameter_post ("comentarios");
	$modo = (int) get_parameter_post ("modo");
	$id_parent = (int) get_parameter_post ("id_parent");
	$id_network_server = (int) get_parameter_post ("network_server");
	$id_plugin_server = (int) get_parameter_post ("plugin_server");
	$id_prediction_server = (int) get_parameter_post ("prediction_server");
	$id_wmi_server = (int) get_parameter_post ("wmi_server");
	$id_os = (int) get_parameter_post ("id_os");
	$disabled = (int) get_parameter_post ("disabled");
	$custom_id = (string) get_parameter_post ("custom_id");

	// Check if agent exists (BUG WC-50518-2)
	if ($nombre_agente == "") {
		$agent_creation_error = __('No agent name specified');
		$agent_created_ok = 0;
	} elseif (dame_agente_id ($nombre_agente) > 0) {
		$agent_creation_error = __('There is already an agent in the database with this name');
		$agent_created_ok = 0;
	} else {
		$id_agente = process_sql_insert ('tagente', 
			array ('nombre' => $nombre_agente,
				'direccion' => $direccion_agente,
				'id_grupo' => $grupo, 'intervalo' => $intervalo,
				'comentarios' => $comentarios, 'modo' => $modo,
				'id_os' => $id_os, 'disabled' => $disabled,
				'id_network_server' => $id_network_server,
				'id_plugin_server' => $id_plugin_server,
				'id_wmi_server' => $id_wmi_server,
				'id_prediction_server' => $id_prediction_server,
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
					'descripcion' => __('Ageng keepalive monitor'),
					'id_modulo' => 1,
					'min_warning' => 0,
					'max_warning' => 1));
			
			if ($id_agent_module !== false) {
				// Create agent_keepalive in tagente_estado table
				$result = process_sql_insert ('tagente_modulo', 
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
			$id_agente = -1;
			$agent_creation_error = __("There was a problem creating the agent");
		}
	}
}

// Show tabs
echo "<div id='menu_tab_frame'>";
echo "<div id='menu_tab_left'><ul class='mn'>";
echo "<li class='nomn'>";
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente'>
<img src='images/setup.png' class='top'>&nbsp; ".substr(get_agent_name ($id_agente),0,21)."</a>";
echo "</li>";
echo "</ul></div>";

echo "<div id='menu_tab'><ul class='mn'>";

echo "<li class='nomn'>";
echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente'><img src='images/zoom.png' width='16' class='top'>&nbsp;".__('View')."</a>";
echo "</li>";

if ($tab == "main") {
	echo "<li class='nomn_high'>";
} else {
	echo "<li class='nomn'>";
}
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente=$id_agente'><img src='images/cog.png' width='16' class='top'>&nbsp; ".__('Setup')."</a>";
echo "</li>";

if ($tab == "module") {
	echo "<li class='nomn_high'>";
} else {
	echo "<li class='nomn'>";
}
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente=$id_agente'><img src='images/lightbulb.png' width='16' class='top'>&nbsp;".__('Modules')."</a>";
echo "</li>";

if ($tab == "alert") {
	echo "<li class='nomn_high'>";
} else {
	echo "<li class='nomn'>";
}	
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=$id_agente'><img src='images/bell.png' width='16' class='top'>&nbsp;". __('Alerts')."</a>";
echo "</li>";

if ($tab == "template") {
	echo "<li class='nomn_high'>";
} else {
	echo "<li class='nomn'>";
}
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=template&id_agente=$id_agente'><img src='images/network.png' width='16' class='top' border=0>&nbsp;".__('Net. Templates')."</a>";
echo "</li>";

enterprise_hook ('inventory_tab');

echo "</ul>";
echo "</div>";
echo "</div>"; // menu_tab_frame

// Make some space between tabs and title
// IE might not always show an empty div, added space
echo "<div style='height: 25px'>&nbsp;</div>";

// Show agent creation results
if ($create_agent) {
	if (! $agent_created_ok) {
		echo "<h3 class='error'>".__('There was a problem creating agent')."</h3>";
		echo __('There was a problem creating agent_keepalive module');
	} else {
		echo "<h3 class='suc'>".__('Agent successfully created')."</h3>";
	}
}

// Fix / Normalize module data
// ===========================
if (isset($_GET["fix_module"])){ 
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
	
	if ($result !== false) {
		echo '<h3 class="suc">'.__('Deleted data above').' '.$media.'</h3>';
	} else {
		echo '<h3 class="error">'.__('Error normalizing module').$error.'</h3>';
	}
}

// Delete Alert component (from a combined)
// ==========================================
if (isset($_GET["delete_alert_comp"])) { // if modified some parameter
	$id_borrar_modulo = get_parameter_get ("delete_alert_comp",0);
	// get info about agent
	$sql = sprintf ("DELETE FROM tcompound_alert WHERE id_aam = %d", $id_borrar_modulo);
	$result = process_sql ($sql);
	
	if ($result === false) {
		echo '<h3 class="error">'.__('There was a problem deleting alert').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Alert successfully deleted').'</h3>';
	}
}

// Combined ALERT - Add component
// ================================
if (isset($_POST["add_alert_combined"])) { // Update an existing alert
	$alerta_id_aam = get_parameter ('update_alert', -1);
	$component_item = get_parameter ('component_item', -1);
	$component_operation = get_parameter ('component_operation', 'AND');
	$sql = sprintf ("INSERT INTO tcompound_alert (id, id_aam, operation) VALUES (%d, %d, '%s')",
		$alerta_id_aam, $component_item, $component_operation);
	$result = process_sql ($sql);
	if ($result === false) {
		echo '<h3 class="error">'.__('There was a problem creating the combined alert').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Combined alert successfully created').'</h3>';
	}

}

// ================
// Update AGENT
// ================
if (isset($_POST["update_agent"])) { // if modified some agent paramenter
	$id_agente = (int) get_parameter_post ("id_agente", 0);
	$nombre_agente = (string) get_parameter_post ("agente");
	$direccion_agente = (string) get_parameter_post ("direccion");
	$address_list = (string) get_parameter_post ("address_list");
	if ($address_list != $direccion_agente && $direccion_agente == get_agent_address ($id_agente) && $address_list != get_agent_address ($id_agente)) {
		//If we selected another IP in the drop down list to be 'primary': 
		// a) field is not the same as selectbox
		// b) field has not changed from current IP
		// c) selectbox is not the current IP
		$direccion_agente = $address_list;
	}
	$grupo = (int) get_parameter_post ("grupo", 0);
	$intervalo = (int) get_parameter_post ("intervalo", 300);
	$comentarios = (string) get_parameter_post ("comentarios");
	$modo = (bool) get_parameter_post ("modo", 0); //Mode: Learning or Normal
	$id_os = (int) get_parameter_post ("id_os");
	$disabled = (bool) get_parameter_post ("disabled");
	$id_network_server = (int) get_parameter_post ("network_server", 0);
	$id_plugin_server = (int) get_parameter_post ("plugin_server", 0);
	$id_wmi_server = (int) get_parameter_post ("wmi_server", 0);
	$id_prediction_server = (int) get_parameter_post ("prediction_server", 0);
	$id_parent = (int) get_parameter_post ("id_parent", 0);
	$custom_id = (string) get_parameter_post ("custom_id", "");

	//Verify if there is another agent with the same name but different ID
	if ($nombre_agente == "") { 
		echo '<h3 class="error">'.__('No agent name specified').'</h3>';	
	//If there is an agent with the same name, but a different ID
	} elseif (dame_agente_id ($nombre_agente) > 0 && dame_agente_id ($nombre_agente) != $id_agente) {
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
	
		//Now update the thing
		$sql = sprintf ("UPDATE tagente
			SET disabled = %d, id_parent = %d, id_os = %d, modo = %d, 
			nombre = '%s', direccion = '%s', id_grupo = %d,
			intervalo = %d, comentarios = '%s', id_network_server = %d,
			id_plugin_server = %d, id_wmi_server = %d, id_prediction_server = %d,
			custom_id = '%s' WHERE id_agente = %d",
			$disabled, $id_parent, $id_os, $modo, $nombre_agente,
			$direccion_agente, $grupo, $intervalo, $comentarios,
			$id_network_server, $id_plugin_server, $id_wmi_server,
			$id_prediction_server, $custom_id, $id_agente);
		$result = process_sql ($sql);
		if ($result === false) {
			echo '<h3 class="error">'.__('There was a problem updating agent').'</h3>';
		} else {
			enterprise_hook ('update_agent', array ($id_agente));
			echo '<h3 class="suc">'.__('Agent successfully updated').'</h3>';
		}
	}
}

if ((isset($agent_created_ok)) && ($agent_created_ok == 1)){
	$_GET["id_agente"] = $id_agente;
}

// Read agent data
// This should be at the end of all operation checks, to read the changess
if (isset($_REQUEST["id_agente"])) {
	//This has been done in the beginning of the page, but if an agent was created, this id might change
	$id_agente = (int) get_parameter ('id_agente');
	$id_grupo = dame_id_grupo ($id_agente);
	if (give_acl ($config["id_user"], $id_grupo, "AW") != 1) {
		audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to admin an agent without access");
		require ("general/noaccess.php");
		exit;
	}
	
	$agent = get_db_row ('tagente', 'id_agente', $id_agente);
	if (empty ($agent)) {
		//Close out the page
		echo '<h3 class="error">'.__('There was a problem loading agent').'</h3>';
		return;
	}
	
	$intervalo = $agent["intervalo"]; // Define interval in seconds
	$nombre_agente = $agent["nombre"];
	$direccion_agente = $agent["direccion"];
	$grupo = $agent["id_grupo"];
	$ultima_act = $agent["ultimo_contacto"];
	$comentarios = $agent["comentarios"];
	$id_plugin_server = $agent["id_plugin_server"];
	$id_network_server = $agent["id_network_server"];
	$id_prediction_server = $agent["id_prediction_server"];
	$id_wmi_server = $agent["id_wmi_server"];
	$modo = $agent["modo"];
	$id_os = $agent["id_os"];
	$disabled = $agent["disabled"];
	$id_parent = $agent["id_parent"];
	$custom_id = $agent["custom_id"];
}

$update_module = (bool) get_parameter ('update_module');
$create_module = (bool) get_parameter ('create_module');
$edit_module = (bool) get_parameter ('edit_module');

// GET DATA for MODULE UPDATE OR MODULE INSERT
if ($update_module || $create_module) {
	$id_grupo = dame_id_grupo ($id_agente);
	
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
	$minvalue = (int) get_parameter_post ("minvalue");
	$maxvalue = (int) get_parameter ('maxvalue');
	$interval = (int) get_parameter ("interval", 300);
	$id_prediction_module = (int) get_parameter ('id_prediction_module');
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
	
	process_sql_update ('tagente_modulo',
		array ('descripcion' => $description,
			'id_module_group' => $id_module_group, 'nombre' => $name,
			'max' => $maxvalue, 'min' => $minvalue, 'module_interval' => $interval,
			'tcp_port' => $tcp_port, 'tcp_send' => $tcp_send,
			'tcp_rcv' => $tcp_rcv, 'snmp_community' => $snmp_community,
			'snmp_oid' => $snmp_oid, 'ip_target' => $ip_target,
			'flag' => $flag, 'disabled' => $disabled,
			'id_export' => $id_export, 'plugin_user' => $plugin_user,
			'plugin_pass' => $plugin_pass, 'plugin_parameter' => $plugin_parameter,
			'id_plugin' => $id_plugin, 'post_process' => $post_process,
			'prediction_module' => $prediction_module,
			'max_timeout' => $max_timeout, 'custom_id' => $custom_id,
			'history_data' => $history_data,
			'min_warning' => $min_warning, 'max_warning' => $max_warning,
			'min_critical' => $min_critical, 'max_critical' => $max_critical,
			'min_ff_event' => $ff_event
		),
		'id_agente_modulo = '.$id_agent_module);
	$result = process_sql ($sql);
	
	if ($result === false) {
		echo '<h3 class="error">'.__('There was a problem updating module').'</h3>';
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
	
	$id_agent_module = process_sql_insert ('tagente_modulo', 
		array ('id_agente' => $id_agente,
			'id_tipo_modulo' => $id_module_type,
			'nombre' => $name, 'descripcion' => $description, 'max' => $maxvalue,
			'min' => $minvalue, 'snmp_oid' => $snmp_oid,
			'snmp_community' => $snmp_community,
			'id_module_group' => $id_module_group, 'module_interval' => $interval,
			'ip_target' => $ip_target, 'tcp_port' => $tcp_port,
			'tcp_rcv' => $tcp_rcv, 'tcp_send' => $tcp_send,
			'id_export' => $id_export, 'plugin_user' => $plugin_user,
			'plugin_pass' => $plugin_pass, 'plugin_parameter' => $plugin_parameter,
			'id_plugin' => $id_plugin, 'post_process' => $post_process,
			'prediction_module' => $id_prediction_module,
			'max_timeout' => $max_timeout, 'disabled' => $disabled,
			'id_modulo' => $id_module, 'custom_id' => $custom_id,
			'history_data' => $history_data, 'min_warning' => $min_warning,
			'max_warning' => $max_warning, 'min_critical' => $min_critical,
			'max_critical' => $max_critical, 'min_ff_event' => $ff_event
		));
	
	if ($id_agent_module === false) {
		echo '<h3 class="error">'.__('There was a problem adding module').'</h3>';
		$edit_module = true;
	} else {
		$result = process_sql_insert ('tagente_estado',
			array ('id_agente_modulo' => $id_agent_module,
				'datos' => 0, 'timestamp' => '0000-00-00 00:00:00',
				'estado' => 0, 'id_agente' => $id_agente,
				'utimestamp' => 0, 'status_changes' => 0,
				'last_status' => 0
			));
		if ($result !== false) {
			echo '<h3 class="suc">'.__('Module added successfully').'</h3>';
		} else {
			echo '<h3 class="error">'.__('Module added successfully').' - '.__('Status init unsuccessful').'</h3>';
		}
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
	process_sql ("SET AUTOCOMMIT=0;");
	process_sql ("START TRANSACTION;");
	
	// First delete from tagente_modulo -> if not successful, increment
	// error
	if (process_sql ("UPDATE tagente_modulo SET nombre = 'pendingdelete', disabled = 1, delete_pending = 1 WHERE id_agente_modulo = ".$id_borrar_modulo) === false)
		$error++;
	
	if (process_sql ("DELETE FROM tagente_estado WHERE id_agente_modulo = ".$id_borrar_modulo) === false)
		$error++;

	if (process_sql ("DELETE FROM tagente_datos_inc WHERE id_agente_modulo = ".$id_borrar_modulo) === false)
		$error++;
	

	//Check for errors
	if ($error != 0) {
		echo '<h3 class="error">'.__('There was a problem deleting the module').'</h3>'; 
		process_sql ("ROLLBACK;");
	} else {
		echo '<h3 class="suc">'.__('Module deleted successfully').'</h3>';
		process_sql ("COMMIT;");
	}

	//End transaction
	process_sql ("SET AUTOCOMMIT=1;");
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
		require ("alert_manager.php");
		break;
	case "template":
		require ("agent_template.php");
		break;
	default:
		if (enterprise_hook ('switch_agent_tab', array ($tab)))
			//This will make sure that blank pages will have at least some
			//debug info in them
			echo '<h3 class="error">DEBUG: Invalid tab specified in '.__FILE__.':'.__LINE__.'</h3>';
}
?>
