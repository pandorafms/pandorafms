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

// General startup for established session
check_login();

// get the variable form_moduletype
$form_moduletype = get_parameter_post ("form_moduletype");
// get the module to update
$update_module_id = get_parameter_get ("update_module",NULL);
// the variable that checks whether the module is disabled or not must be setcommitedversion
$disabled_status = false;

// Specific ACL check
if (give_acl ($config["id_user"], 0, "AW")!=1) {
	audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
	require ($config["homedir"]."/general/noaccess.php");
	exit;
}

// Check whether we are updataing and get data if so
if ($update_module_id != NULL){
	$row = get_db_row ("tagente_modulo", 'id_agente_modulo', $update_module_id);
	if (empty ($row))
		unmanaged_error("Cannot load tnetwork_component reference from previous page");
		
	$id_agente = $row['id_agente'];
	$form_id_tipo_modulo = $row['id_tipo_modulo']; // It doesn't matter
	$form_description = $row['descripcion'];
	$form_name = $row['nombre'];
	$form_minvalue = $row['min'];
	$form_maxvalue = $row['max'];
	$form_interval = $row['module_interval'];
	$form_tcp_port = $row['tcp_port'];
	$form_tcp_send = $row['tcp_send'];
	$form_tcp_rcv = $row['tcp_rcv'];
	$form_snmp_community = $row['snmp_community'];
	$form_snmp_oid = $row['snmp_oid'];
	$form_ip_target = $row['ip_target'];
	$form_id_module_group = $row['id_module_group'];
	$form_flag = $row['flag'];
	$tbl_id_modulo = $row['id_modulo']; // It doesn't matter
	$tbl_disabled = $row['disabled'];
	$form_id_export = $row['id_export'];
	$form_plugin_user = $row['plugin_user'];
	$form_plugin_pass = $row['plugin_pass'];
	$form_plugin_parameter = $row['plugin_parameter'];
	$form_id_plugin = $row['id_plugin'];
	$form_post_process = $row['post_process'];
	$form_prediction_module = $row['prediction_module'];
	$form_max_timeout = $row['max_timeout'];
	$form_custom_id = $row['custom_id'];

	if ($tbl_disabled == 1) 
		$disabled_status = true;
}

echo "<h3>".__('Module assignment')." - ".__('Data server module')."</h3>";
echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'&form_moduletype='.$form_moduletype.'">';

// Whether in update or insert mode
if ($update_module_id == NULL) {
	print_input_hidden ("insert_module", 1);
} else {
	print_input_hidden ("update_module", 1);
}

//id_agente_module
print_input_hidden ("id_agente_modulo", $update_module_id);

// id_modulo 1 - Dataserver
print_input_hidden ("form_id_modulo", 1);
echo '<table width="600" cellpadding="4" cellspacing="4" class="databox_color">';
echo '<tr><td class="datos2">'. __('Module name').'</td><td class="datos2">';
print_input_text ("form_name", $form_name, '', 35);
echo '</td><td class="datos2">'. __('Disabled').'</td><td class="datos2">';
print_checkbox ("form_disabled", 1, $disabled_status);
echo '</td></tr>';

// module type / max timeout
echo '<tr><td class="datos2">'.__('Module type');
pandora_help("module_type");
echo '</td><td class="datos2" colspan="3">';

if ($update_module_id != NULL){
	//We don't pass it along as hidden anymore because the update query
	//doesn't need that specific value to change.
	echo '<span class="redi">Not available in edition mode</span>';
} else {
	$sql = "SELECT id_tipo, nombre FROM ttipo_modulo WHERE categoria IN (0,1,2,6,7,8,9,-1) ORDER BY categoria, nombre";
	$result = get_db_all_rows_sql ($sql); //This database is always filled

	foreach ($result as $row) {
		$fields[$row["id_tipo"]] = $row["nombre"];
	}
	print_select ($fields, "form_id_tipo_modulo", '', '', '', '', false, false, false);
}
echo '</td></tr>';

// Post process / Export server
echo '<tr>';
echo '<td class="datos2">'.__('Post process');
pandora_help("postprocess");
echo '</td><td class="datos2">';
print_input_text ("form_post_process",$form_post_process, '', 5);
// Export target is a server where the data will be sent
echo '</td><td class="datos2">'.__('Export target').'</td>';
echo '<td class="datos2">';

$fields = array ();
$sql = "SELECT id, name FROM tserver_export ORDER BY name";
$result = get_db_all_rows_sql ($sql);

if ($result === false)
	$result = array ();

foreach ($result as $row) {
	$fields[$row["id"]] = $row["name"];
}

print_select ($fields, "form_id_export", $form_id_export,'',__('None'),'0', false, false, false);

echo '</td></tr>';

// Max / min value
echo '<tr>';
echo '<td class="datos">'.__('Min. Value').'</td>';
echo '<td class="datos">';
print_input_text ("form_minvalue",$form_minvalue,'',5);
echo '</td><td class="datos">'.__('Max. Value').'</td>';
echo '<td class="datos">';
print_input_text ("form_maxvalue",$form_maxvalue,'',5);
echo '</td></tr>';

// Interval & id_module_group
echo '<tr>';
echo '<td class="datos2">'.__('Interval').'</td>';
echo '<td class="datos2"><input type="text" name="form_interval" size="5" value="'.$form_interval.'"></td>';
echo '<td class="datos2">'.__('Module group').'</td>';
echo '<td class="datos2">';

$fields = array ();
$sql = "SELECT id_mg, name FROM tmodule_group";
$result = get_db_all_rows_sql ($sql);

if ($result === false)
	$result = array ();

$fields[0] = __("Not assigned");

foreach ($result as $row) {
	$fields[$row["id_mg"]] = $row["name"];
}


print_select ($fields, "form_id_module_group", $form_id_module_group,'','','',false,false,false);
echo '</td></tr>';

// Description
echo '<tr>';
echo '<td valign="top" class="datos">'.__('Description')."</td>";
echo '<td valign="top" class="datos" colspan="3">';
print_textarea ("form_description", 2, 65, $form_description);
echo '</td></tr>';

// Custom ID
echo '<tr>';
echo '<td class="datos2">'.__('Custom ID')."</td>";
echo '<td class="datos2" colspan="3"><input type="text" name="form_custom_id" size="20" value="'.$form_custom_id.'"></td>';
echo '</tr>';

echo '</table>';

//Submit
echo '<table width="600" cellpadding="4" cellspacing="4">';
echo '<td valign="top" align="right">';
if ($update_module_id == NULL){
	print_submit_button (__('Create'), "crtbutton", false,'class="sub wand"');
} else {
	print_submit_button (__('Update'), "updbutton", false,'class="sub wand"');
}
echo '</td></tr></table>';
?>
