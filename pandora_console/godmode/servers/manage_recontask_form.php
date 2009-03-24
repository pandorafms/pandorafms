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

echo '<h2>'.__('Pandora servers').' &gt; '.__('Manage recontask');
print_help_icon ("recontask");
echo '</h2>';

$table->width=700;
$table->cellspacing=4;
$table->cellpadding=4;
$table->class="databox_color";

// Different Form url if it's a create or if it's a update form
echo '<form name="modulo" method="POST" action="index.php?sec=gservers&sec2=godmode/servers/manage_recontask&'.(($id_rt != -1) ? 'update='.$id_rt : 'create=1').'">';

// Name
$table->data[] = array (__('Task name'),print_input_text ('name',$name,'',25,0,true));

// Recon server
$sql = "SELECT id_server, name FROM tserver WHERE recon_server = 1 ORDER BY name";
$result = get_db_all_rows_sql ($sql);
foreach ($result as $row) {
	$selectbox[$row["id_server"]] = $row["name"];
}
$table->data[] = array (__('Recon Server').'<a href="#" class="tip">&nbsp;<span>'.__('You must select a Recon Server for the Task, otherwise the Recon Task will never run').'</span></a>',
			print_select ($selectbox, "id_recon_server", $id_recon_server,'','','',true));
unset ($selectbox);

// Network 
$table->data[] = array (__('Network'),print_input_text ('network',$network,'',25,0,true));

// Interval
$selectbox = array (
		3600 => '1 '.__('hour'),
		7200 => '2 '.__('hours'),
		21600 => '6 '.__('hours'),
		43200 => '12 '.__('hours'),
		86400 => '1 '.__('day'),
		432000 => '5 '.__('days'),
		604800 => '1 '.__('week'),
		1209600 => '2 '.__('weeks'),
		2592000 => '1 '.__('month')
	);

$table->data[] = array (__('Interval'),print_select ($selectbox, "interval", $interval,'','','',true));
unset ($selectbox);

// Network profile
$sql = sprintf("SELECT id_np, name FROM tnetwork_profile");
$result = get_db_all_rows_sql ($sql);
foreach($result as $row) {
	$selectbox[$row["id_np"]] = $row["name"];
}

$table->data[] = array (__('Network profile'),print_select ($selectbox, "id_network_profile", $id_network_profile,'','','',true));
unset ($selectbox);

// OS
$sql = "SELECT id_os, name FROM tconfig_os ORDER BY name";
$result = get_db_all_rows_sql ($sql);
$selectbox[-1] = __('Any');
foreach ($result as $row) {
	$selectbox[$row["id_os"]] = $row["name"];
}

$table->data[] = array (__('OS'),print_select ($selectbox, "id_os", $id_os,'','','',true));
unset ($selectbox);

// Group
$sql = "SELECT id_grupo, nombre FROM tgrupo WHERE id_grupo > 1";
$result = get_db_all_rows_sql ($sql);
foreach ($result as $row) {
	$selectbox[$row["id_grupo"]] = $row["nombre"];
}
$table->data[] = array (__('Group'),print_select ($selectbox, "id_group", $id_group,'','','',true));
unset ($selectbox);

// Incident
$selectbox = array ( 0 => __('No'), 1 => __('Yes') );
$table->data[] = array (__('Incident'),print_select ($selectbox, "create_incident", $create_incident,'','','',true));

// Comments
$table->data[] = array (__('Comments'),print_textarea ("description", 2, 70, $description,'',true));
print_table ($table);
unset ($table);

echo '<div class="action-buttons" style="width: 700px">';
if ($id_rt != "-1") 
	echo print_submit_button (__('Update'),"crt",false,'class="sub upd"',true);
else
	echo print_submit_button (__('Add'),"crt",false,'class="sub wand"',true);
echo '</form>';
echo "</div>";


echo "</form>";

?>
