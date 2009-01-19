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
$tab = get_parameter_get ("tab", "main");
$form_moduletype = get_parameter_post ("form_moduletype");
$form_alerttype = get_parameter ("form_alerttype");
$moduletype = get_parameter_get ("moduletype");

// Init vars
$descripcion = "";
$comentarios = "";
$campo_1 = "";
$campo_2 = "";
$campo_3 = "";
$maximo = 0;
$minimo = 0;
$nombre_agente = "";
$direccion_agente = get_parameter ("direccion", "");
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

// ================================
// Create AGENT
// ================================
// We need to create agent BEFORE showing tabs, because we need to get agent_id
// This is not very clean, but...
if (isset ($_POST["create_agent"])) { // Create a new and shiny agent
	$nombre_agente =  get_parameter_post ("agente", "");
	$direccion_agente = get_parameter_post ("direccion", "");
	$grupo = get_parameter_post ("grupo", 0);
	$intervalo = get_parameter_post ("intervalo", 300);
	$comentarios = get_parameter_post ("comentarios", "");
	$modo = get_parameter_post ("modo", 0);
	$id_parent = get_parameter_post ("id_parent", 0);
	$id_network_server = get_parameter_post ("network_server", 0);
	$id_plugin_server = get_parameter_post ("plugin_server", 0);
	$id_prediction_server = get_parameter_post ("prediction_server", 0);
	$id_wmi_server = get_parameter_post ("wmi_server", 0);
	$id_os = get_parameter_post ("id_os", 0);
	$disabled = get_parameter_post ("disabled", 0);
	$custom_id = get_parameter_post ("custom_id", "");

	// Check if agent exists (BUG WC-50518-2)
	if ($nombre_agente == "") {
		$agent_creation_error = __('No agent name specified');
		$agent_created_ok = 0;
	} elseif (dame_agente_id ($nombre_agente) > 0) {
		$agent_creation_error = __('There is already an agent in the database with this name');
		$agent_created_ok = 0;
	} else {
		$sql = sprintf ("INSERT INTO tagente 
				(nombre, direccion, id_grupo, intervalo, comentarios, modo, id_os, disabled, id_network_server, id_plugin_server, id_wmi_server, id_prediction_server, id_parent, custom_id) 
				VALUES 
				('%s', '%s', %d, %d, '%s', %d, %d, %d, %d, %d, %d, %d, %d, '%s')",
				$nombre_agente, $direccion_agente, $grupo, $intervalo, $comentarios, $modo, $id_os, $disabled, $id_network_server, $id_plugin_server, $id_wmi_server, $id_prediction_server, $id_parent, $custom_id);
		$id_agente = process_sql ($sql, "insert_id");
		enterprise_hook ('update_agent', array ($id_agente));
		if ($id_agente !== false) {
			$agent_created_ok = 1;
			$agent_creation_error = "";
			
			// Create special module agent_keepalive
			$sql = "INSERT INTO tagente_modulo 
					(nombre, id_agente, id_tipo_modulo, descripcion, id_modulo,min_warning, max_warning ) 
					VALUES 
					('agent_keepalive',".$id_agente.",100,'Agent Keepalive monitor',1 ,0,1)";
			$id_agent_module = process_sql ($sql, "insert_id");
			
			if ($id_agent_module !== false) {
				// Create agent_keepalive in tagente_estado table
				$sql = "INSERT INTO tagente_estado 
					(id_agente_modulo, datos, timestamp, estado, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try) 
					VALUES 
					(".$id_agent_module.",'',0,0,".$id_agente.",0,0,0,0,0)";
				$result = process_sql ($sql);
				if ($result === false) {
					$agent_created_ok = 0;
					// Do not translate tagente_estado, is the table name
					$agent_creation_error = __("There was a problem creating record in tagente_estado table");
				}
			} else {
				$agent_created_ok = 0;
				$agent_creation_error = __("There was a problem creating agent_keepalive module");
			}
			
			// Create address for this agent in taddress
			agent_add_address ($id_agente, $direccion_agente);
		} else {
			$id_agente = -1;
			$agent_created_ok = 0;
			$agent_creation_error = __("There was a problem creating the agent");
		}
	}
}

// Show tabs
// -----------------
echo "<div id='menu_tab_frame'>";
echo "<div id='menu_tab_left'><ul class='mn'>";
echo "<li class='nomn'>";
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente'>
<img src='images/setup.png' class='top' border='0'>&nbsp; ".substr(get_agent_name ($id_agente),0,15)." - ".__('Setup mode')."</a>";
echo "</li>";
echo "</ul></div>";

echo "<div id='menu_tab'><ul class='mn'>";

echo "<li class='nomn'>";
echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente'><img src='images/zoom.png' width='16' class='top' border='0'>&nbsp;".__('View')."</a>";
echo "</li>";

if ($tab == "main") {
	echo "<li class='nomn_high'>";
} else {
	echo "<li class='nomn'>";
}
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente=$id_agente'><img src='images/cog.png' width='16' class='top' border='0'>&nbsp; ".__('Setup Agent')."</a>";
echo "</li>";

if ($tab == "module") {
	echo "<li class='nomn_high'>";
} else {
	echo "<li class='nomn'>";
}
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente=$id_agente'><img src='images/lightbulb.png' width='16' class='top' border='0'>&nbsp;".__('Modules')."</a>";
echo "</li>";

if ($tab == "alert") {
	echo "<li class='nomn_high'>";
} else {
	echo "<li class='nomn'>";
}	
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=$id_agente'><img src='images/bell.png' width='16' class='top' border='0'>&nbsp;". __('Alerts')."</a>";
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
echo "<div style='height: 25px'>&nbsp;</div>"; //Some browsers (IE) might not always show an empty div, added space

// Show agent creation results
if (isset ($_POST["create_agent"])) {
	if ($agent_created_ok == 0){
		echo "<h3 class='error'>".__('There was a problem creating agent')."</h3>";
		echo $agent_creation_error;
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
if (isset($_POST["add_alert_combined"])){ // Update an existing alert
	$alerta_id_aam = get_parameter ("update_alert",-1);
	$component_item = get_parameter ("component_item",-1);
	$component_operation = get_parameter ("component_operation","AND");
	$sql = sprintf ("INSERT INTO tcompound_alert (id, id_aam, operation) VALUES (%d, %d, '%s')", $alerta_id_aam, $component_item, $component_operation);
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
if (isset($_GET["id_agente"])) {
	//This has been done in the beginning of the page, but if an agent was created, this id might change
	$id_agente = get_parameter_get ("id_agente");
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
		echo '</table></div><div id="foot">';
		include ("general/footer.php");
		echo "</div>";
		exit;
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

// Read data module if editing module
// ==================================
if ((isset ($_GET["update_module"])) && (!isset ($_POST["oid"])) && (!isset ($_POST["update_module"]))) {
	$update_module = 1;
	$id_agente_modulo = (int) get_parameter_get ("update_module",0);

	$module = get_db_row ('tagente_modulo', 'id_agente_modulo', $id_agente_modulo);

	if ($module === false) {
		echo '<h3 class="error">'.__('There was a problem loading the module').'</h3>';
	} else {
		$modulo_id_agente = $module["id_agente"];
		$modulo_id_tipo_modulo = $module["id_tipo_modulo"];
		$modulo_nombre = $module["nombre"];
		$modulo_descripcion = $module["descripcion"];
		$tcp_send = $module["tcp_send"];
		$tcp_rcv = $module["tcp_rcv"];
		$ip_target = $module["ip_target"];
		$snmp_community = $module["snmp_community"];
		$snmp_oid = $module["snmp_oid"];
		$id_module_group = $module["id_module_group"];
		$module_interval = $module["module_interval"];
		$modulo_max = $module["max"];
		if (empty ($modulo_max))
			$modulo_max = "N/A";
		if (empty ($modulo_min))
			$modulo_min = "N/A";	
		$custom_id = $module["custom_id"];
	}
}

// GET DATA for MODULE UPDATE OR MODULE INSERT
// ===========================================
if ((isset ($_POST["update_module"])) || (isset ($_POST["insert_module"]))) {
	if (isset ($_POST["update_module"])) {
		$update_module = 1;
		$id_agente_modulo = get_parameter_post ("id_agente_modulo",0);
	}
	
	$id_grupo = dame_id_grupo ($id_agente);
	
	if (give_acl ($config["id_user"], $id_grupo, "AW") == 0) {
		audit_db ($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to create a module without admin rights");
		require ("general/noaccess.php");
		exit;
	}
	$form_id_tipo_modulo = (int) get_parameter ("form_id_tipo_modulo",0);
	$form_name = (string) get_parameter ("form_name",0);
	$form_description = (string) get_parameter ("form_description","");
	$form_id_module_group = (int) get_parameter ("form_id_module_group",0);
	$form_flag = (bool) get_parameter ("form_flag",0);
	$form_post_process = (float) get_parameter ("form_post_process",0);
	$form_prediction_module = (int) get_parameter ("form_prediction_module",0);
	$form_max_timeout = (int) get_parameter ("form_max_timeout",0);
	$form_minvalue = (int) get_parameter_post ("form_minvalue",0);
	$form_maxvalue = (int) get_parameter ("form_maxvalue",0);
	$form_interval = (int) get_parameter ("form_interval",300);
	$form_id_prediction_module = (int) get_parameter ("form_id_prediction_module",0);
	$form_id_plugin = (int) get_parameter ("form_id_plugin",0);
	$form_id_export = (int) get_parameter ("form_id_export",0);
	$form_disabled = (bool) get_parameter ("form_disabled",0);
	$form_tcp_send = (string) get_parameter ("form_tcp_send","");
	$form_tcp_rcv = (string) get_parameter ("form_tcp_rcv","");
	$form_tcp_port = (int) get_parameter ("form_tcp_port",0);
	$form_snmp_community = (string) get_parameter ("form_snmp_community","");
	$form_snmp_oid = (string) get_parameter ("form_snmp_oid","");
	$form_ip_target = (string) get_parameter ("form_ip_target","");
	$form_plugin_user = (string) get_parameter ("form_plugin_user","");
	$form_plugin_pass = (string) get_parameter ("form_plugin_pass","");
	$form_plugin_parameter = (string) get_parameter ("form_plugin_parameter","");
	$form_id_modulo = (int) get_parameter ("form_id_modulo",0);
	$form_custom_id = (string) get_parameter ("form_custom_id","");
	$form_history_data = (int) get_parameter('form_history_data',0);
	$form_min_warning = (float) get_parameter ('form_min_warning', 0);
	$form_max_warning = (float) get_parameter ('form_max_warning', 0);
	$form_min_critical = (float) get_parameter ('form_min_critical', 0);
	$form_max_critical = (float) get_parameter ('form_max_critical', 0);
	$form_ff_event = (int) get_parameter ('form_ff_event', 0);
}

// MODULE UPDATE
// =================
if ((isset ($_POST["update_module"])) && (!isset ($_POST["oid"]))) { // if modified something
	if (isset ($_POST["form_combo_snmp_oid"])) {
		$form_combo_snmp_oid = get_parameter_post ("form_combo_snmp_oid");
		if ($snmp_oid == "") {
			$snmp_oid = $form_combo_snmp_oid;
		}
	}
	
	$sql = sprintf ("UPDATE tagente_modulo SET 
			descripcion = '%s', 
			id_module_group = %d,
			nombre = '%s', 
			max = %d, 
			min = %d, 
			module_interval = %d, 
			tcp_port = %d, 
			tcp_send = '%s', 
			tcp_rcv = '%s', 
			snmp_community = '%s', 
			snmp_oid = '%s', 
			ip_target = '%s', 
			flag = %d, 
			id_modulo = %d, 
			disabled = %d, 
			id_export = %d, 
			plugin_user = '%s', 
			plugin_pass = '%s', 
			plugin_parameter = '%s', 
			id_plugin = %d, 
			post_process = %f, 
			prediction_module = %d, 
			max_timeout = %d,
			custom_id = '%s',
			history_data = %d,
			min_warning = %f,
			max_warning = %f,
			min_critical = %f,
			max_critical = %f,
			min_ff_event = %d 
			WHERE id_agente_modulo = %d", $form_description, $form_id_module_group, $form_name, $form_maxvalue, $form_minvalue, $form_interval, $form_tcp_port, $form_tcp_send, $form_tcp_rcv,
			$form_snmp_community, $form_snmp_oid, $form_ip_target, $form_flag, $form_id_modulo, $form_disabled, $form_id_export, $form_plugin_user, $form_plugin_pass,
			$form_plugin_parameter, $form_id_plugin, $form_post_process, $form_prediction_module, $form_max_timeout, $form_custom_id, $form_history_data, $form_min_warning, $form_max_warning, $form_min_critical, $form_max_critical, $form_ff_event, $id_agente_modulo);
	$result = process_sql ($sql);
	
	if ($result === false) {
		echo '<h3 class="error">'.__('There was a problem updating module').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Module successfully updated').'</h3>';
	}

}
// =========================================================
// OID Refresh button to get SNMPWALK from data in form
// This code is also applied when submitting a new module (insert_module = 1)
// =========================================================
if (isset ($_POST["oid"])){
	snmp_set_quick_print (1);
	$snmpwalk = snmprealwalk ($form_ip_target, $form_snmp_community, '');
	
	if (empty ($snmpwalk)) {
		echo '<h3 class="error">'.__('Cannot read from SNMP source').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('SNMP source has been scanned').'</h3>';
	}
}


// =========================================================
// MODULE INSERT
// =========================================================

if (((!isset ($_POST["nc"]) OR ($_POST["nc"] == -1))) && (!isset ($_POST["oid"])) && (isset ($_POST["insert_module"])) && (isset ($_POST['crtbutton']))) {

	if (isset ($_POST["form_combo_snmp_oid"])) {
		$combo_snmp_oid = get_parameter_post ("form_combo_snmp_oid");
	}
	if ($form_snmp_oid == ""){
		$form_snmp_oid = $combo_snmp_oid;
	}
	if ($form_tcp_port == "") {
		$form_tcp_port= "0";
	}
	$sql = sprintf ("INSERT INTO tagente_modulo 
		(id_agente, id_tipo_modulo, nombre, descripcion, max, min, snmp_oid, snmp_community,
		id_module_group, module_interval, ip_target, tcp_port, tcp_rcv, tcp_send, id_export, 
		plugin_user, plugin_pass, plugin_parameter, id_plugin, post_process, prediction_module,
		max_timeout, disabled, id_modulo, custom_id, history_data, min_warning, max_warning, min_critical, max_critical, min_ff_event) 
		VALUES (%d,%d,'%s','%s',%d,%d,'%s','%s',%d,%d,'%s',%d,'%s','%s',%d,'%s','%s','%s',%d,%d,%d,%d,%d,%d,'%s', %d, %f, %f, %f, %f, %d)",
			$id_agente, $form_id_tipo_modulo, $form_name, $form_description, $form_maxvalue, $form_minvalue, $form_snmp_oid, $form_snmp_community, 
			$form_id_module_group, $form_interval, $form_ip_target, $form_tcp_port, $form_tcp_rcv, $form_tcp_send, $form_id_export, $form_plugin_user, $form_plugin_pass, 
			$form_plugin_parameter, $form_id_plugin, $form_post_process, $form_id_prediction_module, $form_max_timeout, $form_disabled, $form_id_modulo, $form_custom_id, $form_history_data, $form_min_warning, $form_max_warning, $form_min_critical, $form_max_critical, $form_ff_event);
	$id_agente_modulo = process_sql ($sql, 'insert_id');

	if ($id_agente_modulo === false){
		echo '<h3 class="error">'.__('There was a problem adding module').'</h3>';
	} else {
		$sql = sprintf ("INSERT INTO tagente_estado 
			(id_agente_modulo,datos,timestamp,estado,id_agente, utimestamp, status_changes, last_status) 
			VALUES (%d, 0,'0000-00-00 00:00:00',0,%d,0,0,0)",$id_agente_modulo,$id_agente);
		
		$result = process_sql ($sql);
		if ($result !== false) {
			echo '<h3 class="suc">'.__('Module added successfully').'</h3>';
		} else {
			echo '<h3 class="error">'.__('Module added successfully').' - '.__('Status init unsuccessful').'</h3>';
		}
	}
}

// MODULE DELETION
// =================
if (isset ($_GET["delete_module"])){ // DELETE agent module !
	$id_borrar_modulo = (int) get_parameter_get ("delete_module",0);
	$id_grupo = (int) dame_id_grupo ($id_agente);	
	
	if (give_acl ($config["id_user"], $id_grupo, "AW") == 0){
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
	if (process_sql ("UPDATE tagente_modulo SET disabled = 1, delete_pending = 1 WHERE id_agente_modulo = ".$id_borrar_modulo) === false)
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
		if (($form_moduletype == "") && ($moduletype == "")) {
			require ("module_manager.php");
		} else {
			require ("module_manager_editor.php");
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
