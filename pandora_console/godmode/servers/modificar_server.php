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
	ui_print_page_header (__('Update Server'), "", false, "", true);
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
	ui_print_page_header (__('Manage servers'), "", false, "", true);

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
	
	$servers = servers_get_info ();
	if ($servers !== false) {
		$table->width = "98%";
		$table->class = "databox";
		$table->data = array ();
		
		$table->align = array ();
		$table->align[1] = "center";
		$table->align[2] = "center";
		$table->align[3] = "center";
		$table->align[4] = "center";
		$table->align[5] = "center";
		$table->align[6] = "left";
		$table->style = array ();
		$table->style[0] = 'font-weight: bold';
		$table->head = array ();
		$table->head[0] = __('Name');
		$table->head[1] = __('Status');
		$table->head[2] = __('Description');
		$table->head[3] = __('Type');
		$table->head[4] = __('Started');
		$table->head[5] = __('Updated');
		
		$table->size = array();
		$table->size[6] = '50px';
		
		//Only Pandora Administrator can delete servers
		if (check_acl ($config["id_user"], 0, "PM")) {
			$table->head[6] = '<span title="Operations">' . __('Op.') . '</span>';
		}
		
		foreach ($servers as $server) {
			if ($server['status'] == 0) {
				$server_status = ui_print_status_image (STATUS_SERVER_DOWN, '', true);
			} else {
				$server_status = ui_print_status_image (STATUS_SERVER_OK, '', true);
			}
			
			$data = array ();
			
			$data[0] = '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server='.$server["id_server"].'">'.$server["name"].'</a>';
			$data[1] = $server_status;
			$data[2] = substr ($server["description"], 0, 25);
			$data[3] = $server['img'];
			$data[4] = human_time_comparation ($server["laststart"]);
			$data[5] = human_time_comparation ($server["keepalive"]);

			$data[6] = '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server='.$server["id_server"].'">';
			$data[6] .= html_print_image ('images/config.png', true, array ('title' => __('Edit')));
			$data[6] .= '</a>';

			//Only Pandora Administrator can delete servers
			if (check_acl ($config["id_user"], 0, "PM")) {
				$data[6] .= '&nbsp;&nbsp;<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_del='.$server["id_server"].'&amp;delete=1">';
				$data[6] .= html_print_image ('images/cross.png', true, array ('title' => __('Delete'), 'onclick' => "if (! confirm ('" . __('Modules run by this server will stop working. Do you want to continue?') ."')) return false"));
				$data[6] .= '</a>';
			}
			
			array_push ($table->data, $data);
		}
		html_print_table ($table);
		
		//Legend
		echo "<table>";
		echo "<tr><td colspan='5'>" . __('Legend') . "</td></tr>";
		echo "<tr>";
		echo '<td><span class="net">'.__('Network server').'</span></td>';
		echo '<td><span class="master">'.__('Master').'</span></td>';
		echo '<td><span class="data">'.__('Data server').'</span></td>';
		echo '<td><span class="binary">'.__('MD5 check').'</span></td>';
		echo '<td><span class="snmp">'.__('SNMP console').'</span></td>';
		echo "</tr><tr>";
		echo '<td><span class="recon_server">'.__('Recon server').'</span></td>';
		echo '<td><span class="wmi_server">'.__('WMI server').'</span></td>';
		echo '<td><span class="export_server">'.__('Export server').'</span></td>';
		echo '<td><span class="inventory_server">'.__('Inventory server').'</span></td>';
		echo '<td><span class="web_server">'.__('Web server').'</span></td>';
		echo "</tr></table>";
	} else {
		echo "<div class='nf'>".__('There are no servers configured into the database')."</div>";
	}
}

?>
