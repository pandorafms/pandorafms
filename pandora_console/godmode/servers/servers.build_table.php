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

require_once ('include/functions_clippy.php');

global $config;

check_login();

if (! check_acl ($config["id_user"], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Server Management");
	require ("general/noaccess.php");
	exit;
}

global $tiny;
global $hidden_toggle;
$date = time();

$servers = servers_get_info();
if ($servers === false) {
	$server_clippy = clippy_context_help("servers_down");
	echo "<div class='nf'>".__('There are no servers configured into the database').$server_clippy."</div>";
	return;
}

$table = new StdClass();
$table->width = '100%';
$table->class = 'databox data';
$table->size = array ();

$table->style = array ();
$table->style[0] = 'font-weight: bold';

$table->align = array ();
$table->align[1] = 'center';
$table->align[3] = 'center';
$table->align[8] = 'center';

$table->headstyle[1] = 'text-align:center';
$table->headstyle[3] = 'text-align:center';
$table->headstyle[8] = 'text-align:center';

//$table->title = __('Tactical server information');
$table->titleclass = 'tabletitle';
$table->titlestyle = 'text-transform:uppercase;';

$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Status');
$table->head[2] = __('Type');
$table->head[3] = __('Version');
$table->head[4] = __('Modules');
$table->head[5] = __('Lag') . ui_print_help_tip (__("Avg. Delay(sec)/Modules delayed"), true);
$table->head[6] = __('T/Q') . ui_print_help_tip (__("Threads / Queued modules currently"), true);
// This will have a column of data such as "6 hours"
$table->head[7] = __('Updated');

//Only Pandora Administrator can delete servers
if (check_acl ($config["id_user"], 0, "PM")) {
	$table->head[8] = '<span title="Operations">' . __('Op.') . '</span>';
}

$table->data = array ();
$names_servers = array ();

foreach ($servers as $server) {
	$data = array ();
	$table->cellclass[][3] = "progress_bar";
	$data[0] = '<span title="' . $server['version'] . '">' .
		$server['name'] . '</span>';
	
	//Status
	$data[1] = ui_print_status_image (STATUS_SERVER_OK, '', true);
	if (($server['status'] == 0) || (($date - strtotime($server['keepalive'])) > ($server['server_keepalive'])*2)) {
		$data[1] = ui_print_status_image (STATUS_SERVER_DOWN, '', true);
	}
	
	// Type
	$data[2] = '<span style="white-space:nowrap;">' . $server["img"];
		if ($server["master"] == 1) {
			$data[2] .= ui_print_help_tip (__("This is a master server"), true);
		}
	//$data[2] .= '</span> <span style="font-size:8px;"> v' .. '</span>';
	
	switch ($server['type']) {
		case "snmp":
		case "event":
			$data[3] =  $server["version"];
			$data[4] = 'N/A';
			$data[5] = 'N/A';
			break;
		case "export":
			$data[3] = $server["version"];
			$data[4] = $server["modules"] . " ".__('of')." ". $server["modules_total"];
			$data[5] = 'N/A';
			break;
		default:
			$data[3] =  $server["version"];
			$data[4] = $server["modules"] . " ".__('of')." ". $server["modules_total"];
			$data[5] = '<span style="white-space:nowrap;">' .
				$server["lag_txt"] . '</span>';
			break;
	}
	
	$data[6] = $server['threads'].' : '.$server['queued_modules'];
	if ($server['queued_modules'] > 200) {
		$data[6] .= clippy_context_help("server_queued_modules");
	}
	$data[7] = ui_print_timestamp ($server['keepalive'], true);
	
	
	$ext = '_server';
	if ($server['type'] != 'data')
		$ext = '';
	
	$safe_server_name = servers_get_name($server["id_server"]);
	if (($server['type'] == 'data' || $server['type'] == 'enterprise satellite')) {
		if (servers_check_remote_config ($safe_server_name . $ext) && enterprise_installed()) {
			$names_servers[$safe_server_name] = true;
		} else {
			$names_servers[$safe_server_name] = false;
		}
	}
	
	//Only Pandora Administrator can delete servers
	if (check_acl ($config["id_user"], 0, "PM")) {
		 $data[8] = '';
		 if ($server['type'] == 'data') {
			$data[8] .= '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=0&server_reset_counts='.$server["id_server"].'">';
			$data[8] .= html_print_image ('images/target.png', true,
				array('title' => __('Reset module status and fired alert counts')));
			$data[8] .= '</a>&nbsp;&nbsp;';
		}
		else if ($server['type'] == 'enterprise snmp') {
			$data[8] .= '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=0&server_reset_snmp_enterprise='.$server["id_server"].'">';
			$data[8] .= html_print_image ('images/target.png', true,
				array('title' => __('Claim back SNMP modules')));
			$data[8] .= '</a>&nbsp;&nbsp;';
		}
		
		$data[8] .= '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server='.$server["id_server"].'">';
		$data[8] .= html_print_image ('images/config.png', true,
			array('title' => __('Edit')));
		$data[8] .= '</a>';
		
		if (($names_servers[$safe_server_name] === true) && ($server['type'] == 'data' || $server['type'] == 'enterprise satellite')) {
			$data[8] .= '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_remote='.$server["id_server"].'&ext='.$ext.'">';
			$data[8] .= html_print_image ('images/remote_configuration.png', true,
				array('title' => __('Remote configuration')));
			$data[8] .= '</a>';
			$names_servers[$safe_server_name] = false;
		}
		
		$data[8] .= '&nbsp;&nbsp;<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_del='.$server["id_server"].'&amp;delete=1">';
		$data[8] .= html_print_image ('images/cross.png', true,
			array('title' => __('Delete'),
				'onclick' => "if (! confirm ('" . __('Modules run by this server will stop working. Do you want to continue?') ."')) return false"));
		$data[8] .= '</a>';
	}
	
	if ($tiny) {
		unset($data[4]);
		unset($data[6]);
		unset($data[7]);
		unset($data[8]);
	}
	array_push ($table->data, $data);
	
}

if ($tiny) {
	unset($table->head[4]);
	unset($table->head[6]);
	unset($table->head[7]);
	unset($table->head[8]);
}
if ($tiny) {
	ui_toggle(html_print_table ($table,true), __('Tactical server information'),false,$hidden_toggle);
}
else {
	html_print_table ($table);
}
?>
