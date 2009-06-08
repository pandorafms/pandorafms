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

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation",
		"Trying to access recon task viewer");
	require ("/general/noaccess.php");
	return;
}

$modules_server = 0;
$total_modules = 0;
$total_modules_data = 0;

// --------------------------------
// FORCE A RECON TASK
// --------------------------------
if (give_acl ($config['id_user'], 0, "PM")) {
	if (isset ($_GET["force"])) {
		$id = (int) get_parameter_get ("force", 0);
		$sql = sprintf ("UPDATE trecon_task SET utimestamp = 0, status = 1 WHERE id_rt = %d", $id);
		
		process_sql ($sql);
	}
}

$id_server = (int) get_parameter ("server_id", -1);
$server_name = get_server_name ($id_server);
$recon_tasks = get_db_all_rows_field_filter ("trecon_task", "id_recon_server", $id_server);

echo "<h2>". __('Configuration detail') . " - ".safe_input ($server_name);
echo '&nbsp;<a href="index.php?sec=estado_server&amp;sec2=operation/servers/view_server_detail&amp;server_id='.$id_server.'">';
print_image ("images/refresh.png");
echo "</a></h2>";


// Show network tasks for Recon Server
if ($recon_tasks === false) {
	$recon_tasks = array ();
}

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = 725;
$table->class = "databox";
$table->head = array ();
$table->data = array ();
$table->align = array ();

$table->head[0] = '';
$table->align[0] = "center";

$table->head[1] = __('Task name');
$table->align[1] = "center";

$table->head[2] = __('Interval');
$table->align[2] = "center";

$table->head[3] = __('Network');
$table->align[3] = "center";

$table->head[4] = __('Status');
$table->align[4] = "center";

$table->head[5] = __('Network profile');
$table->align[5] = "center";

$table->head[6] = __('Group');
$table->align[6] = "center";

$table->head[7] = __('OS');
$table->align[7] = "center";

$table->head[8] = __('Progress');
$table->align[8] = "center";

$table->head[9] = __('Updated at');
$table->align[9] = "center";

$table->head[10] = '';
$table->align[10] = "center";

foreach ($recon_tasks as $task) {
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=estado_server&amp;sec2=operation/servers/view_server_detail&amp;server_id='.$id_server.'&amp;force='.$task["id_rt"].'">';
	$data[0] .= print_image ("images/target.png", true, array ("title" => __('Force')));
	$data[0] .= '</a>';
	
	$data[1] = '<b>'.safe_input ($task["name"]).'</b>';

	$data[2] = human_time_description ($task["interval_sweep"]);

	$data[3] = $task["subnet"];
	
	if ($task["status"] <= 0) {
		$data[4] = __('Done');
	} else {
		$data[4] = __('Pending');
	}

	$data[5] = get_networkprofile_name ($task["id_network_profile"]);
	
	$data[6] = print_group_icon ($task["id_group"], true);
	
	$data[7] = print_os_icon ($task["id_os"], false, true);
	
	if ($task["status"] <= 0 || $task["status"] > 100) {
		$data[8] = "-";
	} else {
		$data[8] = print_image ("reporting/fgraph.php?tipo=progress&percent=".$task['status']."&height=20&width=100", true, array ("title" => __('Progress').':'.$task["status"].'%'));
	}
	
	$data[9] = print_timestamp ($task["utimestamp"], true);

	if (give_acl ($config["id_user"], $task["id_group"], "PM")) {
		$data[10] = '<a href="index.php?sec=gservers&amp;sec2=godmode/servers/manage_recontask_form&amp;update='.$task["id_rt"].'">'.print_image ("images/wrench_orange.png", true).'</a>';
	} else {
		$data[10] = '';
	}
	
	array_push ($table->data, $data);
}

if (empty ($table->data)) {
	echo '<div class="nf">'.__("This server has no recon tasks assigned").'</div>';
} else {
	print_table ($table);
}
unset ($table);
?>
