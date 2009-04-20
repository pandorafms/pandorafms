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

// Load global vars
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
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
} elseif (isset ($_GET["create"])) {
	$id_rt = -1;
	$name = "";
	$network = "";
	$description = "";
	$id_recon_server = 0;
	$interval = 43200;
	$id_group = 1;
	$create_incident = 1;
	$id_network_profile = 1;
	$id_os = -1; // Any
}

echo '<h2>'.__('Pandora servers').' &raquo; '.__('Manage recontask');
print_help_icon ("recontask");
echo '</h2>';

$table->width=700;
$table->cellspacing=4;
$table->cellpadding=4;
$table->class="databox_color";

// Name
$table->data[0][0] = __('Task name');
$table->data[0][1] = print_input_text ('name', $name, '', 25, 0, true);

// Recon server
$table->data[1][0] = __('Recon Server').'<a href="#" class="tip">&nbsp;<span>'.__('You must select a Recon Server for the Task, otherwise the Recon Task will never run').'</span></a>';
$table->data[1][1] = print_select_from_sql ('SELECT id_server, name FROM tserver WHERE server_type = 3 ORDER BY name',
	"id_recon_server", $id_recon_server, '', '', '', true);

// Network 
$table->data[2][0] = __('Network');
$table->data[2][1] = print_input_text ('network', $network, '', 25, 0, true);

// Interval
$values = array ();
$values[3600] = __('%d hour', 1);
$values[7200] = __('%d hours', 2);
$values[21600] = __('%d hours', 12);
$values[43200] = __('%d day', 1);
$values[86400] = __('%d day', 1);
$values[432000] = __('%d days', 5);
$values[604800] = __('%d week', 1);
$values[1209600] = __('%d weeks', 2);
$values[2592000] = __('%d month', 1);

$table->data[3][0] = __('Interval');
$table->data[3][1] = print_select ($values, "interval", $interval, '', '', '', true);

// Network profile
$table->data[4][0] = __('Network profile');
$table->data[4][1] = print_select_from_sql ('SELECT id_np, name FROM tnetwork_profile',
	"id_network_profile", $id_network_profile, '', '', '', true);

// OS
$table->data[5][0] = __('OS');
$table->data[5][1] = print_select_from_sql ('SELECT id_os, name FROM tconfig_os ORDER BY name',
	"id_os", $id_os, '', '', '', true);

// Group
$table->data[6][0] = __('Group');
$table->data[6][1] = print_select (get_user_groups (), "id_group",
	$id_group, '', '', '', true);

// Incident
$values = array (0 => __('No'), 1 => __('Yes'));
$table->data[7][0] = __('Incident');
$table->data[7][1] = print_select ($values, "create_incident", $create_incident,
	'','','',true);

// Comments
$table->data[8][0] = __('Comments');
$table->data[8][1] = print_textarea ("description", 2, 70, $description, '', true);

print_table ($table);

// Different Form url if it's a create or if it's a update form
echo '<form name="modulo" method="post" action="index.php?sec=gservers&sec2=godmode/servers/manage_recontask&'.(($id_rt != -1) ? 'update='.$id_rt : 'create=1').'">';

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id_rt != -1) 
	print_submit_button (__('Update'), "crt", false, 'class="sub upd"');
else
	print_submit_button (__('Add'), "crt", false, 'class="sub wand"');
echo '</form>';
echo "</div>";

echo "</form>";

?>
