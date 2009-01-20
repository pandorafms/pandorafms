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

if (!isset ($id_agente)) {
	die ("Not Authorized");
}

require_once ("include/functions_exportserver.php");

// Following variables come from module_manager.php -> configurar_agente.php :
//
// $form_moduletype: could be [1] Agent module/Data server, [2] network server, [4] plugin server, [6] wmiserver, or [5] predictionserver
// $moduletype: helper to fix get/post method; copy of $form_moduletype just to edit modules, not to create them

if (($form_moduletype == "") && ($moduletype != "")) {
	switch ($moduletype) {
		case "1":	
			$form_moduletype = "dataserver";
			break;
		case "2":
			$form_moduletype = "networkserver";
			break;
		case "4":
			$form_moduletype = "pluginserver";
			break;
		case "5":
			$form_moduletype = "predictionserver";
			break;
		case "6":
			$form_moduletype = "wmiserver";
			break;
		//This will make sure that blank pages will
		//have at least some debug info in them
		default:
			echo '<h3 class="error">DEBUG: Invalid module type specified in '.__FILE__.':'.__LINE__.'</h3>';
			echo 'Most likely you have recently upgraded from an earlier version of Pandora and either <br />
				1) forgot to use the database converter<br />
				2) used a bad version of the database converter (see Bugreport #2124706 for the solution)<br />
				3) found a new bug - please report a way to duplicate this error';
			return; //We return control to the invoking script so the page finishes rendering
	}
}

// Get form
$form_network_component = get_parameter ("form_network_component", "");

// Using network component to fill some fields
if (($form_moduletype == "networkserver" || $form_moduletype == "wmiserver") && ($form_network_component != "") && (!isset($_POST['crtbutton'])) && (!isset($_POST['oid']))) {
	// Preload data from template
	$row = get_db_row ("tnetwork_component", 'id_nc', $form_network_component);
	if (empty ($row))
		unmanaged_error ("Cannot load tnetwork_component reference from previous page");

	$form_id_tipo_modulo = $row["type"];
	$form_id_module_group = $row["id_module_group"];
	$form_name = $row["name"];
	$form_descripcion = $row["description"];
	$form_tcp_send = $row["tcp_send"];
	$form_tcp_rcv = $row["tcp_rcv"];
	$form_tcp_port = $row["tcp_port"];
	$form_snmp_community = $row["snmp_community"];
	$form_snmp_oid = $row["snmp_oid"];
	$form_id_module_group = $row["id_module_group"];
	$form_interval = $row["module_interval"];
	$form_maxvalue = $row["max"];
	$form_minvalue = $row["min"];
	$form_max_timeout = $row["max_timeout"];
	$form_id_export = 0;
	$form_disabled = 0;
	$form_plugin_user = $row["plugin_user"];
	$form_plugin_pass = $row["plugin_pass"];
	$form_plugin_parameter = $row["plugin_parameter"];
	$form_prediction_module = "";
	$form_id_plugin = "";
	$form_post_process = "";
	$form_custom_id = "";

} elseif (!isset($_POST['oid'])) {
	// Clean up specific network modules fields
	$form_name = "";
	$form_description = "";
	$form_id_module_group = 1;
	$form_id_tipo_modulo = 1;
	$form_post_process = "";
	$form_max_timeout = "";
	$form_minvalue = "";
	$form_maxvalue = "";
	$form_interval = "";
	$form_prediction_module = "";
	$form_id_plugin = "";
	$form_id_export = "";
	$form_disabled= "0";
	$form_tcp_send = "";
	$form_tcp_rcv = "";
	$form_tcp_port = "";
	
	if ($form_moduletype == "wmiserver")
	    $form_snmp_community = "";
	else
    	$form_snmp_community = "public";
	$form_snmp_oid = "";
	$form_ip_target = $direccion_agente; // taken from configurar_agente.php
	$form_plugin_user = "";
	$form_plugin_pass = "";
	$form_plugin_parameter = "";
	$form_custom_id = "";
	$form_history_data = 1;
	$form_min_warning = 0;
	$form_max_warning = 0;
	$form_min_critical = 0;
	$form_max_critical = 0;
	$form_ff_event = 0;
}

switch ($form_moduletype) {
	case "dataserver":
		include $config["homedir"]."/godmode/agentes/module_manager_editor_data.php";
		break;
	case "networkserver":
		include $config["homedir"]."/godmode/agentes/module_manager_editor_network.php";
		break;
	case "pluginserver":
		include $config["homedir"]."/godmode/agentes/module_manager_editor_plugin.php";
		break;
	case "predictionserver":
		include $config["homedir"]."/godmode/agentes/module_manager_editor_prediction.php";
		break;
	case "wmiserver":
		include $config["homedir"]."/godmode/agentes/module_manager_editor_wmi.php";
		break;
	default:
		echo '<h3 class="error">DEBUG: Invalid module type specified in '.__FILE__.':'.__LINE__.'</h3>';
		echo 'Most likely you have recently upgraded from an earlier version of Pandora and either <br />
			1) forgot to use the database converter<br />
			2) used a bad version of the database converter (see Bugreport #2124706 for the solution)<br />
			3) found a new bug - please report a way to duplicate this error';
		return;
																				
}
?>
