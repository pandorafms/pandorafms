<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
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

check_login();

if (! check_acl ($config["id_user"], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Server Management");
	require ("general/noaccess.php");
	exit;
}

global $tiny;

$servers = servers_get_info ();
if ($servers === false) {
	echo "<div class='nf'>".__('There are no servers configured into the database')."</div>";
	return;
}

$table->width = '98%';
$table->size = array ();

$table->style = array ();
$table->style[0] = 'font-weight: bold';

$table->align = array ();
$table->align[1] = 'center';
$table->align[3] = 'center';
$table->align[4] = 'center';
$table->align[5] = 'center';
$table->align[8] = 'center';

$table->title = __('Tactical server information');
$table->titleclass = 'tabletitle';
$table->titlestyle = 'text-transform:uppercase;';

$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Status');
$table->head[2] = __('Type');
$table->head[3] = __('Load') . ui_print_help_tip (__("Modules running on this server / Total modules of this type"), true);
$table->head[4] = __('Modules');
$table->head[5] = __('Lag') . ui_print_help_tip (__("Modules delayed / Max. Delay (sec)"), true);
$table->head[6] = __('T/Q') . ui_print_help_tip (__("Threads / Queued modules currently"), true);
// This will have a column of data such as "6 hours"
$table->head[7] = __('Updated');

//Only Pandora Administrator can delete servers
if (check_acl ($config["id_user"], 0, "PM")) {
	$table->head[8] = '<span title="Operations">' . __('Op.') . '</span>';
}

$table->data = array ();

foreach ($servers as $server) {
	$data = array ();
	
	$data[0] = '<span title="' . $server['version'] . '">' .
		$server['name'] . '</span>';
	
	if ($server['status'] == 0) {
		$data[1] = ui_print_status_image (STATUS_SERVER_DOWN, '', true);
	}
	else {
		$data[1] = ui_print_status_image (STATUS_SERVER_OK, '', true);
	}
	
	// Type
	$data[2] = '<span style="white-space:nowrap;">'.$server["img"].'</span> ('.ucfirst($server["type"]).")";
	if ($server["master"] == 1)
		$data[2] .= ui_print_help_tip (__("This is a master server"), true);
	
	switch ($server['type']) {
		case "snmp":
		case "event":
			$data[3] = 'N/A';
			$data[4] = 'N/A';
			$data[5] = 'N/A';
			break;
		case "export":
			$data[3] = progress_bar($server["load"], 60, 20, $server["lag_txt"], 0);
			$data[4] = $server["modules"] . " ".__('of')." ". $server["modules_total"];
			$data[5] = 'N/A';
			break;
		default:
			$data[3] = progress_bar($server["load"], 60, 20, $server["lag_txt"], 0);
			$data[4] = $server["modules"] . " ".__('of')." ". $server["modules_total"];
			$data[5] = '<span style="white-space:nowrap;">'.$server["lag_txt"].'</span>';
			break;
	}
	
	$data[6] = $server['threads'].' : '.$server['queued_modules'];
	$data[7] = ui_print_timestamp ($server['keepalive'], true);
	
	//Only Pandora Administrator can delete servers	 
	if (check_acl ($config["id_user"], 0, "PM")) {	 
		 $data[8] = '';	 
		 if ($server['type'] == 'data') {	 
				 $data[8] .= '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=0&server_reset_counts='.$server["id_server"].'">';	 
				 $data[8] .= html_print_image ('images/target.png', true,	 
						 array('title' => __('Reset module status and fired alert counts')));	 
				 $data[8] .= '</a>&nbsp;&nbsp;';	 
		 } else if ($server['type'] == 'enterprise snmp') {	 
				 $data[8] .= '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=0&server_reset_snmp_enterprise='.$server["id_server"].'">';	 
				 $data[8] .= html_print_image ('images/target.png', true,	 
						 array('title' => __('Claim back SNMP modules')));	 
				 $data[8] .= '</a>&nbsp;&nbsp;';	 
		 }	 

		 $data[8] .= '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server='.$server["id_server"].'">';	 
		 $data[8] .= html_print_image ('images/config.png', true,	 
				 array('title' => __('Edit')));	 
		 $data[8] .= '</a>';	 

		 $data[8] .= '&nbsp;&nbsp;<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_del='.$server["id_server"].'&amp;delete=1">';	 
		 $data[8] .= html_print_image ('images/cross.png', true,	 
				 array('title' => __('Delete'),	 
						 'onclick' => "if (! confirm ('" . __('Modules run by this server will stop working. Do you want to continue?') ."')) return false"));	 
		 $data[8] .= '</a>';	 
	}
	
	if($tiny) {
		unset($data[4]);
		unset($data[6]);
		unset($data[7]);
		unset($data[8]);
	}
	array_push ($table->data, $data);
}

if($tiny) {
	unset($table->head[4]);
	unset($table->head[6]);
	unset($table->head[7]);
	unset($table->head[8]);
}
	
html_print_table ($table);
?>
