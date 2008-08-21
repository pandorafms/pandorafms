<?php
// Pandora FMS - the Flexible monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require("include/config.php");

check_login();

if (! give_acl ($config["id_user"], 0, "AR") && ! give_acl($config['id_user'], 0, "AW")) {
	audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Server Management");
	require ("general/noaccess.php");
	exit;
}

if (isset ($_GET["delete"])) {
	$id_server = get_parameter_get ("server_del");
	$sql = sprintf ("DELETE FROM tserver WHERE id_server='%d'",$id_server);
	$result = process_sql ($sql);
	if ($result !== false) {
		 echo '<h3 class="suc">'.__('Server deleted successfully').'</h3>';
	} else { 
		echo '<h3 class="error">'.__('There was a problem deleting the server').'</h3>';
	}
} elseif (isset($_GET["update"])) {
	$name = get_parameter_post ("name");
	$address = get_parameter_post ("address");
	$description = get_parameter_post ("description");
	$id_server = get_parameter_post ("server");
	$sql = sprintf ("UPDATE tserver SET name = '%s', ip_address = '%s', description = '%s' WHERE id_server = %d",$name,$address,$description,$server);
	$result = process_sql ($sql);
	if ($result !== false) {
		echo '<h3 class="suc">'.__('Server updated successfully').'</h3>';
	} else { 
		echo '<h3 class="error">'.__('There was a problem updating the server').'</h3>';
	}
}

if (isset($_GET["server"])) {
	$id_server= get_parameter_get ("server");
	echo "<h2>".__('Pandora servers')." &gt; ".__('Update Server')."</h2>";
	$sql = sprintf("SELECT name, ip_address, description FROM tserver WHERE id_server = %d",$id_server);
	$row = get_db_row_sql ($sql);
	echo '<form name="servers" method="POST" action="index.php?sec=gservers&sec2=godmode/servers/modificar_server&update=1">';
	print_input_hidden ("server",$id_server);
	
	$table->cellpadding=4;
	$table->cellspacing=4;
	$table->width=450;
	$table->class="databox_color";
	
	$table->data[] = array (__('Name'),print_input_text ('name',$row["name"],'',50,0,true));
	$table->data[] = array (__('IP Address'),print_input_text ('address',$row["ip_address"],'',50,0,true));
	$table->data[] = array (__('Description'),print_input_text ('description',$row["description"],'',50,0,true));
	
	print_table($table);
	unset ($table->data, $table->class);

	$table->align=array ("right");
	$table->data[] = array ('<input type="submit" class="sub upd" value="'.__('Update').'">');
	print_table($table);
	unset ($table);
} else {
	$result = get_db_all_rows_in_table ("tserver");
	echo "<h2>".__('Pandora servers')." &gt; ".__('Manage servers')."</h2>";

	if ($result !== false) {
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = "90%";
		$table->class = "databox";
		$table->align = array ('',"center","center","center","center","center","center","center");
		$table->head = array (__('Name'),__('Status'),__('IP Address'),__('Description'),__('Type'),__('Started at'),__('Updated at'),__('Delete'));
		
		foreach ($result as $row) {
			$server = "";
			if($row["network_server"] == 1) {
				$server .= '<img src="images/network.png" />&nbsp;';
			}
			if ($row["data_server"] == 1) {
				$server .= '<img src="images/data.png" />&nbsp;';
			}
			if ($row["snmp_server"] == 1) {
				$server .= '<img src="images/snmp.png" />&nbsp;';
			}
			if ($row["master"] == 1) {
				$server .= '<img src="images/master.png" />&nbsp;';
			}
			if ($row["checksum"] == 1) {
				$server .= '<img src="images/binary.png" />&nbsp;';
			}
				
			$table->data[] = array (
						'<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server='.$row["id_server"].'"><b>'.$row["name"].'</b></a>',
						'<img src="images/dot_'.(($row["status"] == 0) ? 'red' : 'green').'">',
						$row["ip_address"],
						substr($row["description"],0,25),
						$server,
						$LOCALE->fmt_time($row["laststart"],"MYSQL","DATE").' '.$LOCALE->fmt_time($row["laststart"],"MYSQL","LONGTIME"),
						$LOCALE->fmt_time($row["keepalive"],"MYSQL","DATE").' '.$LOCALE->fmt_time($row["keepalive"],"MYSQL","LONGTIME"),
						'<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_del='.$row["id_server"].'&delete"><img src="images/cross.png" border="0">'
					);
		
		}
		print_table ($table);
		unset ($table);
		
		//Lagend
		$table->cellpadding = 2;
		$table->cellspacing = 0;
		$table->data[] = array (
		  			'<span class="net">'.__('Network Server').'</span>',
		  			'<span class="master">'.__('Master').'</span>',
		  			'<span class="data">'.__('Data Server').'</span>',
		  			'<span class="binary">'.__('MD5 Check').'</span>',
		  			'<span class="snmp">'.__('SNMP Console').'</span>'
				);
		print_table ($table);
		unset ($table);
	} else {
		echo "<div class='nf'>".__('There are no servers configured into the database')."</div>";
	}
}

?>
