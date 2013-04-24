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

require_once ("include/functions_servers.php");

check_login();

if (! check_acl ($config["id_user"], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Server Management");
	require ("general/noaccess.php");
	exit;
}

if (isset($_GET["server"])) {
	$id_server= get_parameter_get ("server");
	// Headers
	ui_print_page_header (__('Update Server'), "images/gm_servers.png", false, "servers", true);
	$sql = sprintf("SELECT name, ip_address, description FROM tserver WHERE id_server = %d",$id_server);
	$row = db_get_row_sql ($sql);
	echo '<form name="servers" method="POST" action="index.php?sec=gservers&sec2=godmode/servers/modificar_server&update=1">';
	html_print_input_hidden ("server",$id_server);
	
	$table->cellpadding=4;
	$table->cellspacing=4;
	$table->width='98%';
	$table->class="databox_color";
	
	$table->data[] = array (__('Name'),$row["name"]);
	$table->data[] = array (__('IP Address'),html_print_input_text ('address',$row["ip_address"],'',50,0,true));
	$table->data[] = array (__('Description'),html_print_input_text ('description',$row["description"],'',50,0,true));
	html_print_table ($table);
	
	
	echo '<div class="action-buttons" style="width: 98%">';
	echo '<input type="submit" class="sub upd" value="'.__('Update').'">';
	echo "</div>";

}
else {
	// Header
	ui_print_page_header (__('Pandora servers'), "images/gm_servers.png", false, "servers", true);

	// Move SNMP modules back to the enterprise server
	if (isset($_GET["server_reset_snmp_enterprise"])) {
		$result = db_process_sql ("UPDATE tagente_estado SET last_error=0");
	
		if($result === false) {
			ui_print_error_message(__('Unsuccessfull action'));
		}
		else {
			ui_print_success_message(__('Successfully action'));
		}
	}
	
	// Move SNMP modules back to the enterprise server
	if (isset($_GET["server_reset_counts"])) {
		$reslt = db_process_sql ("UPDATE tagente SET update_module_count=1, update_alert_count=1");
		
		if($result === false) {
			ui_print_error_message(__('Unsuccessfull action'));
		}
		else {
			ui_print_success_message(__('Successfully action'));
		}
	}

	if (isset ($_GET["delete"])) {
		$id_server = get_parameter_get ("server_del");
		
		$result = db_process_sql_delete('tserver', array('id_server' => $id_server));
		
		if ($result !== false) {
			 echo '<h3 class="suc">'.__('Server deleted successfully').'</h3>';
		}
		else { 
			echo '<h3 class="error">'.__('There was a problem deleting the server').'</h3>';
		}
	}
	elseif (isset($_GET["update"])) {
		$address = get_parameter_post ("address");
		$description = get_parameter_post ("description");
		$id_server = get_parameter_post ("server");
		
		$values = array('ip_address' => $address, 'description' => $description);
		$result = db_process_sql_update('tserver', $values, array('id_server' => $id_server));
		if ($result !== false) {
			echo '<h3 class="suc">'.__('Server updated successfully').'</h3>';
		}
		else { 
			echo '<h3 class="error">'.__('There was a problem updating the server').'</h3>';
		}
	}
	
	$tiny = false;
	require($config['homedir'] . '/godmode/servers/servers.build_table.php');
}
?>
