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
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Recon Task Management");
	require ("general/noaccess.php");
	exit;
}

// --------------------------------
// DELETE A RECON TASKs
// --------------------------------
if (isset ($_GET["delete"])) {
	$id = get_parameter_get ("delete");
	$sql = sprintf("DELETE FROM trecon_task WHERE id_rt = '%d'",$id);
	$result = process_sql ($sql);
	if ($result !== false) {
		echo '<h3 class="suc">'.__('Successfully deleted recon task').'</h3>';
	} else {
		echo '<h3 class="error">'.__('Error deleting recon task').'</h3>';
	}
}

// --------------------------------
// GET PARAMETERS IF UPDATE OR CREATE
// --------------------------------
if ((isset ($_GET["update"])) OR ((isset ($_GET["create"])))) {
	$name = get_parameter_post ("name");
	$network = get_parameter_post ("network");
	$description = get_parameter_post ("description");
	$id_recon_server = get_parameter_post ("id_recon_server");
	$interval = get_parameter_post ("interval");
	$id_group = get_parameter_post ("id_group");
	$create_incident = get_parameter_post ("create_incident");
	$id_network_profile = get_parameter_post ("id_network_profile");
	$id_os = get_parameter_post ("id_os", 10);
}

// --------------------------------
// UPDATE A RECON TASK
// --------------------------------
if (isset($_GET["update"])) {
	$id = get_parameter_get ("update");
	$sql = sprintf ("UPDATE trecon_task SET id_os = %d, name = '%s', subnet = '%s',
				description = '%s', id_recon_server = %d, create_incident = %b, id_group = %d, interval_sweep = %u, 
				id_network_profile = %d WHERE id_rt = %u",$id_os,$name,$network,$description,$id_recon_server,$create_incident,$id_group,$interval,$id_network_profile,$id);
	
	if (process_sql ($sql) !== false) {
		echo '<h3 class="suc">'.__('Successfully updated recon task').'</h3>';
	} else {
		echo '<h3 class="error">'.__('Error updating recon task').'</h3>';
	}
}

// --------------------------------
// CREATE A RECON TASK
// --------------------------------
if (isset($_GET["create"])) {
	$sql = sprintf ("INSERT INTO trecon_task 
			(name, subnet, description, id_recon_server, create_incident, id_group, id_network_profile, interval_sweep, id_os) 
			VALUES ( '%s', '%s', '%s', %u, %b, %d, %d, %u, %d)",$name,$network,$description,$id_recon_server,$create_incident,$id_group,$id_network_profile,$interval,$id_os);
	
	if (process_sql ($sql) !== false) {
		echo '<h3 class="suc">'.__('Successfully created recon task').'</h3>';
	} else {
		echo '<h3 class="error">'.__('Error creating recon task').'</h3>';
	}
}

// --------------------------------
// SHOW TABLE WITH ALL RECON TASKs
// --------------------------------
echo "<h2>".__('Pandora servers')." &raquo; ".__('Manage recontask')."</h2>";

$result = get_db_all_rows_in_table ("trecon_task");
$color=1;
if ($result !== false) {
	$table->head = array  (__('Name'), __('Network'), __('Network profile'), __('Group'), __('Incident'), __('OS'), __('Interval'), __('Action'));
	$table->align = array ("","","","center","","","center","center");
	$table->width = 700;
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->class = "databox";

	foreach ($result as $row) {
		$table->data[] = array (
			'<a href="index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&update='.$row["id_rt"].'"><b>'.$row["name"].'</b></a>',
		// Network (subnet)
			$row["subnet"],
		// Network profile name
			'<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates&id='.$row["id_network_profile"].'">'.get_networkprofile_name ($row["id_network_profile"]).'</a>',
		// GROUP
			print_group_icon ($row["id_group"], true),
		// INCIDENT
			(($row["create_incident"] == 1) ? __('Yes') : __('No')),	
		// OS
			(($row["id_os"] > 0) ? print_os_icon ($row["id_os"], false, true) : __('Any')),
		// INTERVAL
			human_time_description_raw($row["interval_sweep"]),
		// ACTION
			'<a href="index.php?sec=gservers&sec2=godmode/servers/manage_recontask&delete='.$row["id_rt"].'">
			<img src="images/cross.png" border="0" /></a>&nbsp;&nbsp;<a href="index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&update='.$row["id_rt"].'">
			<img src="images/config.png" /></a>'
		);
	}
	print_table ($table);
	unset ($table);
} else {
	echo '<div class="nf">'.__('There are no recon task configured').'</div>';
}

echo '<div class="action-buttons" style="width: 700px">';
echo '<form method="post" action="index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&create">';
echo print_submit_button (__('Create'),"crt",false,'class="sub next"',true);
echo '</form>';
echo "</div>";

?>
