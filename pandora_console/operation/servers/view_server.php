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

check_login ();

if (! give_acl ($config['id_user'], 0, "AR") && ! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Server view");
	require ("general/noaccess.php");
	return;
}

$modules_server = 0;
$total_modules_network = 0;
$total_modules_data = 0;

echo "<h2>".__('Pandora servers')." &raquo; ".__('Configuration detail')."</h2>";

$total_modules = (int) get_db_value ('COUNT(*)', 'tagente_modulo', 'disabled', 0);
$servers = get_server_info ();
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

$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Status');
$table->head[2] = __('Type');
$table->head[3] = __('Load'). print_help_tip (__("Modules running on this server / Total modules of this type"), true);
$table->head[4] = __('Modules');
$table->head[5] = __('Lag'). print_help_tip (__("Modules delayed / Max. Delay (sec)"), true);
$table->head[6] = __('T/Q'). print_help_tip (__("Threads / Queued modules currently"), true);
// This will have a column of data such as "6 hours"
$table->head[7] = __('Updated');
$table->data = array ();

foreach ($servers as $server) {
	$data = array ();
	$data[0] = '<span title="'.$server['version'].'">'.$server['name'].'</span>';
	
	if ($server['status'] == 0) {
		$data[1] = print_status_image (STATUS_SERVER_DOWN, '', true);
	} else {
		$data[1] = print_status_image (STATUS_SERVER_OK, '', true);
	}
	
	// Type
	$data[2] = '<span style="white-space:nowrap;">'.$server["img"].'</span> ('.ucfirst($server["type"]).")";
	if ($server["master"] == 1)
		$data[2] .= print_help_tip (__("This is a master server"), true);

	// Load
	$data[3] = print_image ("reporting/fgraph.php?tipo=progress&percent=".$server["load"]."&height=20&width=60", true, array ("title" => $server["lag_txt"]));
	$data[4] = $server["modules"] . " ".__('of')." ". $server["modules_total"];
	$data[5] = '<span style="white-space:nowrap;">'.$server["lag_txt"].'</span>';
	$data[6] = $server['threads'].' : '.$server['queued_modules'];
	$data[7] = print_timestamp ($server['keepalive'], true);
	
	array_push ($table->data, $data);
}

print_table ($table);	
?>
