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
if (!isset ($id_agente)) {
	die ("Not Authorized");
}

// get the variable form_moduletype
$form_moduletype = get_parameter_post ("form_moduletype");
// get the module to update
$update_module_id = get_parameter_get ("update_module");
// the variable that checks whether the module is disabled or not must be setcommitedversion
$disabled_status = NULL;

// Specific ACL check
if (give_acl($config["id_user"], 0, "AW") != 1) {
    audit_db($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
    require ($config["homedir"]."/general/noaccess.php");
    exit;
}

// Check whether we are updataing and get data if so
if ($update_module_id != NULL){
    $row = get_db_row ("tagente_modulo", 'id_agente_modulo', $update_module_id);
    if ($row == 0){
        unmanaged_error("Cannot load tnetwork_component reference from previous page");
    }
	else{
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

		if ($tbl_disabled == 1){
			$disabled_status = 'checked="ckecked"';
		} else {
			$disabled_status = NULL;
		}
	}
}

echo "<h3>".__('Module assignment')." - ".__('Prediction server module')."</h3>";
echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'">';
// Whether in update or insert mode
if ($update_module_id == NULL){
	print_input_hidden ("insert_module", 1);
} else {
	print_input_hidden ("update_module", 1);
}

//id_agente_module
print_input_hidden ("id_agente_modulo", $update_module_id);

// id_modulo 5 - Prediction
print_input_hidden ("form_id_modulo", 5);

// name / disabled
echo '<table width="600" cellpadding="4" cellspacing="4" class="databox_color">';
echo '<tr>';
echo '<td class="datos2">'.__('Module name').'</td>';
echo '<td class="datos2">';
print_input_text ("form_name", $form_name, '', 35);
echo '<td class="datos2">'.__('Disabled').'</td>';
echo '<td class="datos2">';
print_checkbox ("form_disabled", 1, $disabled_status);
echo '</td></tr>';

//Source module
echo '<tr>';
echo '<td class="datos">'.__('Source module');
pandora_help ("prediction_source_module");
echo '</td>';
echo '<td class="datos" colspan="3">';

$agents = get_group_agents (array_keys (get_user_groups ($config["id_user"], "AW")));
$fields = array ();

foreach ($agents as $agent_id => $agent_name) {
	$modules = get_agent_modules ($agent_id);
	foreach ($modules as $module_id => $module_name) {
		$fields[$module_id] = $agent_name.' / '.$module_name;
	}
}

print_select ($fields, "form_prediction_module", $form_prediction_module);
echo '</td></tr>';

// module type / interval
echo '<tr><td class="datos2">'. __('Module type') .'</td><td class="datos2">';
if (!empty ($update_module_id)) {
	echo '<span class="redi">Not available in edition mode</span>';
	print_input_hidden ("form_id_tipo_modulo", $form_id_tipo_modulo);
} else {
	$fields = array ();
	$fields[1] = get_moduletype_name (1);
	$fields[2] = get_moduletype_name (2);
	print_select ($fields, "form_id_tipo_modulo");
}

echo '<td class="datos2">'.__('Interval').'</td><td class="datos2">';
print_input_text ("form_interval", $form_interval, '', 5);
echo '</td></tr>';

// Post process / Export server
echo '<tr><td class="datos">'.__('Module group').'</td><td class="datos">';
$fields = get_modulegroups ();
print_select ($fields, "form_id_module_group", $form_id_module_group);

// Export target is a server where the data will be sent
echo '<td class="datos">'.__('Export target').'</td>';
echo '<td class="datos">';

$fields = get_exportservers_info ();
$fields[0] = __('None');

print_select ($fields, "form_id_export", $form_id_export);
echo '</td></tr>';

// Description
echo '<tr>';
echo '<td valign="top" class="datos2">'.__('Description').'</td>';
echo '<td valign="top" class="datos2" colspan="3">';
print_textarea ("form_description", 2, 65, $form_description);
echo '</td></tr>';

// Custom ID
echo '<tr>';
echo '<td class="datos">'.__('Custom ID').'</td>';
echo '<td class="datos" colspan="3">';
print_input_text ("form_custom_id", $form_custom_id, '', 20);
echo '</td></tr>';

echo '</table>';

// Submit
echo '<div style="width:680px; text-align: right">';
if ($update_module_id == NULL){
	print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
} else {
	print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
}
echo '</div>';

?>
