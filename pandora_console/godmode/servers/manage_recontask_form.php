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

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	return;
}

if (isset ($_GET["update"])) { // Edit mode
	$id_rt = (int) get_parameter_get ("update");
	$row = get_db_row ("trecon_task","id_rt",$id_rt);
	$name = $row["name"];
	$network = $row["subnet"];
	$id_recon_server = $row["id_recon_server"];
	$description = $row["description"];
	$interval = $row["interval_sweep"];
	$id_group = $row["id_group"];
	$create_incident = $row["create_incident"];
	$id_network_profile = $row["id_network_profile"];
	$id_os = $row["id_os"];
	$recon_ports = $row["recon_ports"];
	$snmp_community = $row["snmp_community"];
	$id_recon_script = $row["id_recon_script"];
	$field1 = $row["field1"];
	$field2 = $row["field2"];
	$field3 = $row["field3"];
	$field4 = $row["field4"];
	if ($id_recon_script == 0)
		$mode = "network_sweep";
	else
		$mode = "recon_script";
		
} elseif (isset ($_GET["create"])) {
	$id_rt = -1;
	$name = "";
	$network = "";
	$description = "";
	$id_recon_server = 0;
	$interval = 43200;
	$id_group = 0;
	$create_incident = 1;
    $snmp_community = "public";
	$id_network_profile = 1;
	$id_os = -1; // Any
	$recon_ports = ""; // Any
	$field1 = "";
	$field2 = "";
	$field3 = "";
	$field4 = "";
	$id_recon_script = 0;
	$mode = "network_sweep";
}

// Headers
print_page_header (__('Manage recontask')." ".print_help_icon ("recontask", true), "", false, "", true);


$table->width=600;
$table->cellspacing=4;
$table->cellpadding=4;
$table->class="databox_color";

// Name
$table->data[0][0] = "<b>".__('Task name')."</b>";
$table->data[0][1] = print_input_text ('name', $name, '', 25, 0, true);

// Recon server
$table->data[1][0] = "<b>".__('Recon server').'<a href="#" class="tip">&nbsp;<span>'.__('You must select a Recon Server for the Task, otherwise the Recon Task will never run').'</span></a>';

$table->data[1][1] = print_select_from_sql ('SELECT id_server, name FROM tserver WHERE server_type = 3 ORDER BY name', "id_recon_server", $id_recon_server, '', '', '', true);


$fields['network_sweep'] = __("Network sweep");
$fields['recon_script'] = __("Custom script");


$table->data[2][0] = "<b>".__('Mode')."</b>";
$table->data[2][1] = print_select ($fields, "mode", $mode, '', '', 0, true);

		
// Network 
$table->data[3][0] = "<b>".__('Network');
$table->data[3][1] = print_input_text ('network', $network, '', 25, 0, true);

// Interval
$values = array ();
$values[3600] = __('%d hour', 1);
$values[7200] = __('%d hours', 2);
$values[21600] = __('%d hours', 6);
$values[43200] = __('%d hours', 12);
$values[86400] = __('%d day', 1);
$values[432000] = __('%d days', 5);
$values[604800] = __('%d week', 1);
$values[1209600] = __('%d weeks', 2);
$values[2592000] = __('%d month', 1);

$table->data[4][0] = "<b>".__('Interval');
$table->data[4][1] = print_select ($values, "interval", $interval, '', '', '', true);

// Module template
$table->data[5][0] = "<b>".__('Module template');
$table->data[5][1] = print_select_from_sql ('SELECT id_np, name FROM tnetwork_profile',
	"id_network_profile", $id_network_profile, '', '', '', true);

// Recon script
$table->data[6][0] = "<b>".__('Recon script');
$table->data[6][1] = print_select_from_sql ('SELECT id_recon_script, name FROM trecon_script', "id_recon_script", $id_recon_script, '', '', '', true);


// OS
$table->data[7][0] = "<b>".__('OS');
$table->data[7][1] = print_select_from_sql ('SELECT id_os, name FROM tconfig_os ORDER BY name',
	"id_os", $id_os, '', __('Any'), -1, true);

// Recon ports
$table->data[8][0] = "<b>".__('Ports');
$table->data[8][1] =  print_input_text ('recon_ports', $recon_ports, '', 25, 0, true);
$table->data[8][1] .= '<a href="#" class="tip">&nbsp;<span>'.__('Ports defined like: 80 or 80,443,512 or even 0-1024 (Like Nmap command line format). If dont want to do a sweep using portscan, left it in blank').'</span></a>';

// Group
$table->data[9][0] = "<b>".__('Group');
$groups = get_user_groups (false, "AR", false);
$table->data[9][1] = print_select_groups(false, "AR", false, 'id_group', $id_group, '', '', 0, true);

// Incident
$values = array (0 => __('No'), 1 => __('Yes'));
$table->data[10][0] = "<b>".__('Incident');
$table->data[10][1] = print_select ($values, "create_incident", $create_incident,
	'','','',true);

// SNMP default community
$table->data[11][0] = "<b>".__('SNMP Default community');
$table->data[11][1] =  print_input_text ('snmp_community', $snmp_community, '', 35, 0, true);

// Field1
$table->data[12][0] = "<b>".__('Script field #1');
$table->data[12][1] =  print_input_text ('field1', $field1, '', 40, 0, true);

// Field2
$table->data[13][0] = "<b>".__('Script field #2');
$table->data[13][1] =  print_input_text ('field2', $field2, '', 40, 0, true);

// Field3
$table->data[14][0] = "<b>".__('Script field #3');
$table->data[14][1] =  print_input_text ('field3', $field3, '', 40, 0, true);

// Field4
$table->data[15][0] = "<b>".__('Script field #4');
$table->data[15][1] =  print_input_text ('field4', $field4, '', 40, 0, true);


// Comments
$table->data[16][0] = "<b>".__('Comments');
$table->data[16][1] =  print_input_text ('description', $description, '', 45, 0, true);


// Different Form url if it's a create or if it's a update form
echo '<form name="modulo" method="post" action="index.php?sec=gservers&sec2=godmode/servers/manage_recontask&'.(($id_rt != -1) ? 'update='.$id_rt : 'create=1').'">';

print_table ($table);
echo '<div class="action-buttons" style="width: 620px">';
if ($id_rt != -1) 
	print_submit_button (__('Update'), "crt", false, 'class="sub upd"');
else
	print_submit_button (__('Add'), "crt", false, 'class="sub wand"');
echo "</div>";

echo "</form>";

?>
