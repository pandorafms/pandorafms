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


// Load global variables
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	return;
}

include_once($config['homedir'] . "/include/functions_modules.php");

$data = array ();
$data[0] = __('WMI query');
$data[1] = html_print_input_text ('snmp_oid', $snmp_oid, '', 25, 255, true);
$data[2] = __('Key string') . ' ' . ui_print_help_icon ('wmikey', true, ui_get_full_url(false, false, false, false));
$data[3] = html_print_input_text ('snmp_community', $snmp_community, '', 25, 255, true);

push_table_row ($data, 'wmi_1');

$data = array ();
$data[0] = __('Field number') . ' ' . ui_print_help_icon ('wmifield', true, ui_get_full_url(false, false, false, false));
$data[1] = html_print_input_text ('tcp_port', $tcp_port, '', 5, 25, true);
$data[2] = __('Namespace') . ' ' . ui_print_help_icon ('wminamespace', true, ui_get_full_url(false, false, false, false));
$data[3] = html_print_input_text ('tcp_send', $tcp_send, '', 25, 255, true);

push_table_row ($data, 'wmi_2');

$data = array ();
$data[0] = __('Username');
$data[1] = html_print_input_text ('plugin_user', $plugin_user, '', 15, 255, true);
$data[2] = __('Password');
$data[3] = html_print_input_password ('plugin_pass', $plugin_pass, '', 25, 255, true);

push_table_row ($data, 'wmi_3');

$data = array();
$data[0] = __('Post process') . ' ' . ui_print_help_icon ('postprocess', true, ui_get_full_url(false, false, false, false));
$data[1] = html_print_input_text ('post_process', $post_process, '', 12, 25, true);
$data[2] = $data[3] = '';
push_table_row($data, 'field_process');

return;
// Update an existing component
if (! $id) {
	$module_interval = SECONDS_5MINUTES;
	$tcp_port = "";
	$tcp_rcv = "";
	$tcp_send = "";
	$snmp_community = "";
	$id_module_group = "";
	$id_group = "";
	$type = 0;
	$plugin_user = "Administrator";
	$plugin_pass = "";
	$plugin_parameter = "";
	$max_timeout = 10;
	$max_retries = 1;
}

echo '<form method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_components">';

echo '<table width="98%" cellspacing="4" cellpadding="4" class="databox_color">';
echo '<tr>';

// Name
echo '<tr><td class="datos2">' . __('Module name') . '</td>';
echo '<td class="datos2"><input type="text" name="name" size="25" value="' . $name . '"></td>';

// Type
echo '<td class="datos2">' . __('Module type') . '</td>';
echo '<td class="datos2">';
echo '<select name="tipo">';
echo '<option value="' . $type . '">' . modules_get_moduletype_name ($type);

$rows = db_get_all_rows_sql('SELECT id_tipo, nombre
	FROM ttipo_modulo WHERE categoria IN (0,1,2) ORDER BY nombre;');
if ($rows === false) {
	$rows = array();
}

foreach ($rows as $row) {
	echo '<option value="' . $component['id_tipo'] . '">' . $component['nombre'] . '</option>';
}
echo '</select>';
echo '</td></tr>';
echo '<tr>';

// Component group
echo '<td class="datos">' . __('Group') . '</td>';
echo '<td class="datos">';
html_print_select (network_components_get_groups (),
	'id_group', $id_group, '', '', '', false, false, false);

// Module group
echo '<td class="datos">' . __('Module group') . '</td>';
echo '<td class="datos">';
echo '<select name="id_module_group">';
if ($id) {
	echo '<option value="' . $id_module_group . '">' . modules_get_modulegroup_name($id_module_group);
}

$rows = db_get_all_rows_in_table('tmodule_group');
if ($rows === false) {
	$rows = array();
}

foreach ($rows as $row) {
	echo '<option value="' . $component['id_mg'] . '">' . $component['name'] . '</option>';
}
echo '</select>';
echo '<tr>';

// Interval
echo '<td class="datos2">' . __('Module Interval') . '</td>';
echo '<td class="datos2">';
echo '<input type="text" name="module_interval" size="5" value="'.$module_interval.'">';

// Timeout
echo '<td class="datos2">' . __('Max. timeout') . '</td>';
echo '<td class="datos2">';
echo	'<input type="text" name="max_timeout" size="5" value="' . $max_timeout . '">';
echo '</td></tr>';

// Timeout
echo '<td class="datos2">' . __('Max. retries') . '</td>';
echo '<td class="datos2">';
echo	'<input type="text" name="max_retries" size="5" value="' . $max_retries . '">';
echo '</td></tr>';

// WMI Query
echo '<tr><td class="datos">' . __('WMI query') ;
ui_print_help_icon("wmiquery", true, ui_get_full_url(false, false, false, false));
echo '</td>';
echo '<td class="datos">';
echo 	'<input type="text" name="snmp_oid" size="25" value="' . $snmp_oid . '">';
echo '</td>';

// Key string
echo '<td class="datos">' . __('Key string');
ui_print_help_icon("wmikey", true, ui_get_full_url(false, false, false, false));
echo '</td>';
echo '<td class="datos">';
echo 	'<input type="text" name="snmp_community" size="25" value="' . $snmp_community . '">';
echo '</td></tr>';

// Field
echo '<td class="datos2">' . __('Field number');
ui_print_help_icon("wmifield", true, ui_get_full_url(false, false, false, false));
echo '</td>';
echo '<td class="datos2">';
echo	'<input type="text" name="tcp_port" size="5" value="' . $tcp_port . '">';
echo '</td>';

// Namespace
echo '<td class="datos2">' . __('Namespace');
ui_print_help_icon("wminamespace", true, ui_get_full_url(false, false, false, false));
echo '</td>';
echo '<td class="datos2">';
echo	'<input type="text" name="tcp_send" size="25" value="' . $tcp_send . '">';
echo '</td></tr>';

// Username
echo '<tr><td class="datos">' . __('Username') . '</td>';
echo '<td class="datos">';
echo 	'<input type="text" name="plugin_user" size="25" value="' . $plugin_user . '">';
echo '</td>';

// Password
echo '<td class="datos">' . __('Password') . '</td>';
echo '<td class="datos">';
echo 	'<input type="password" name="plugin_pass" size="25" value="' . $plugin_pass . '">';
echo '</td></tr>';

// Min data
echo '<tr><td class="datos2">' . __('Minimum Data') . '</td>';
echo '<td class="datos2">';
echo '<input type="text" name="modulo_min" size="5" value="' . $modulo_min . '">';
echo '</td>';
echo '<td class="datos2">' . __('Maximum Data') . '</td>';
echo '<td class="datos2">';

// Max data
echo '<input type="text" name="modulo_max" size="5" value="' . $modulo_max . '">';
echo '</td></tr>';

// Comments
echo '<tr><td class="datos">'.__('Comments') . '</td>';
echo '<td class="datos" colspan=3>';
echo '<textarea name="descripcion" cols=70 rows=2>';
echo $description;
echo '</textarea>';
echo '</td></tr>';
echo '</table>';

html_print_input_hidden ('id_modulo', $id_component_type);

// Update/Add buttons
echo '<div class="action-buttons" style="width: 95%">';
if ($id) {
	html_print_input_hidden ('update_component', 1);
	html_print_input_hidden ('id', $id);
	html_print_submit_button (__('Update'), 'crt', false, 'class="sub upd"');
}
else {
	html_print_input_hidden ('create_component', 1);
	html_print_submit_button (__('Create'), 'crt', false, 'class="sub next"');
}
echo '</div>';
echo '</form>';

?>
