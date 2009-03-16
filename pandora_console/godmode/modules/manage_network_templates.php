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
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Network Profile Management");
	require ("general/noaccess.php");
	exit;
}
  
if (isset ($_POST["delete_profile"])) { // if delete
	$id_np = (int) get_parameter_post ("delete_profile", 0);
	$sql = sprintf ("DELETE FROM tnetwork_profile WHERE id_np = %d", $id_np);
	$result = process_sql ($sql);
	print_error_message ($result, __('Template successfully deleted'), __('Error deleting template'));
}

if (isset ($_POST["export_profile"])) {
	$id_np = (int) get_parameter_post ("export_profile", 0);
	$profile_info = get_db_row ("tnetwork_profile", "id_np", $id_np);
	
	if (empty ($profile_info)) {
		print_error_message (false,'', __('This template does not exist'));
		return;
	}	
	
	//It's important to keep the structure and order in the same way for backwards compatibility.
	$sql = sprintf ("SELECT components.name, components.description, components.type, components.max, components.min, components.module_interval, 
					components.tcp_port, components.tcp_send, components.tcp_rcv, components.snmp_community, components.snmp_oid, 
					components.id_module_group, components.id_modulo, components.plugin_user, components.plugin_pass, components.plugin_parameter,
					components.max_timeout, components.history_data, components.min_warning, components.max_warning, components.min_critical, 
					components.max_critical, components.min_ff_event, comp_group.name AS group_name
					FROM `tnetwork_component` AS components, tnetwork_profile_component AS tpc, tnetwork_component_group AS comp_group
					WHERE tpc.id_nc = components.id_nc AND components.id_group = comp_group.id_sg AND tpc.id_np = %d", $id_np);
	
	$components = get_db_all_rows_sql ($sql);
	
	$row_names = array ();
	$inv_names = array ();
	//Find the names of the rows that we are getting and throw away the duplicate numeric keys
	foreach ($components[0] as $row_name => $detail) {
		if (is_numeric ($row_name)) {
			$inv_names[] = $row_name;
		} else {
			$row_names[] = $row_name;
		}
	}
	while (@ob_end_clean()); //Clean up output buffering
	
	//Send headers to tell the browser we're sending a file	
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=".preg_replace ('/\s/', '_', $profile_info["name"]).".csv");
	header("Pragma: no-cache");
	header("Expires: 0");
	
	//Then print the first line (row names)
	echo '"'.implode ('","', $row_names).'"';
	echo "\n";
	
	//Then print the rest of the data. Encapsulate in quotes in case we have comma's in any of the descriptions
	foreach ($components as $row) {
		foreach ($inv_names as $bad_key) {
			unset ($row[$bad_key]);
		}
		echo '"'.implode ('","', $row).'"';
		echo "\n";
	}
	exit; //We're done here. The original page will still be there.
}

echo "<h2>".__('Module management')." &gt; ".__('Module template management')."</h2>";

$result = get_db_all_rows_in_table ("tnetwork_profile", "name");

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = "95%";
$table->class = "databox";

$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Description');
$table->head[2] = __('Action');

$table->align = array ();
$table->align[2] = "center";

$table->data = array ();

foreach ($result as $row) {
	$data = array ();
	$data[0] = '<a href="index.php?sec=gmodules&amp;sec2=godmode/modules/manage_network_templates_form&amp;id_np='.$row["id_np"].'">'.safe_input ($row["name"]).'</a>';
	$data[1] = safe_input ($row["description"]);
	$data[2] = print_input_image ("delete_profile", "images/cross.png", $row["id_np"],'', true, array ('onclick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;', 'border' => 0));
	$data[2] .= print_input_image ("export_profile", "images/lightning_go.png", $row["id_np"], '', true, array ('border' => 0));
	
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	echo '<form method="post" action="index.php?sec=gmodules&amp;sec2=godmode/modules/manage_network_templates">';
	print_table ($table);
	echo '</form>';
} else {
	echo '<div class="nf" style="width:90%">'.__('There are no defined network profiles').'</div>';	
}
unset ($table);

echo '<form method="post" action="index.php?sec=gmodules&amp;sec2=godmode/modules/manage_network_templates_form&amp;id_np=-1">';
echo '<div style="width:90%; text-align:right;">';
print_submit_button (__('Create'), "crt", '', 'class="sub next"'); 
echo '</div></form>';

?>
