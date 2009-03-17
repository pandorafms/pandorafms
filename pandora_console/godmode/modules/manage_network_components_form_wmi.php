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


// Load global variables
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	exit;
}

// Update an existing component
if (isset($_GET["update"])){
	$id_nc = entrada_limpia ($_GET["id_nc"]);
	$sql1 = "SELECT * FROM tnetwork_component where id_nc = $id_nc ORDER BY name";
	$result=mysql_query($sql1);
	$row=mysql_fetch_array($result);
	$name = $row["name"];
	$type = $row["type"];
	$description = $row["description"];
	$modulo_max = $row["max"];
	$modulo_min = $row["min"];
	$module_interval = $row["module_interval"];
	$tcp_port = $row["tcp_port"];
	$tcp_rcv = $row["tcp_rcv"];
	$tcp_send = $row["tcp_send"];
	$snmp_community = $row["snmp_community"];
	$snmp_oid = $row["snmp_oid"];
	$id_module_group = $row["id_module_group"];
	$id_group = $row["id_group"];
	$plugin_user = $row["plugin_user"];
	$plugin_pass = $row["plugin_pass"];
	$plugin_parameter = $row["plugin_parameter"];
	$max_timeout = $row["max_timeout"];
}
// Add a new component
elseif (isset($_GET["create"])){
	$id_nc = -1;
	$name = "";
	$snmp_oid = "";
	$description = "";
	$id_group = 1;
	$oid = "";
	$modulo_max = "0";
	$modulo_min = "0";
	$module_interval = "300";
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
}

echo '<h2>' . __('WMI component management') . '</h2>';
if ($id_nc != -1) {
	// Update
	echo '<form name="modulo" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_components&update=1&id_nc=' . $id_nc . '">';
} else {
	// Add
	echo '<form name="modulo" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_components&create=1">';
}
echo '<table width="700" cellspacing="4" cellpadding="4" class="databox_color">';
echo '<tr>';

// Name
echo '<tr><td class="datos2">' . __('Module name') . '</td>';
echo '<td class="datos2"><input type="text" name="name" size="25" value="' . $name . '"></td>';

// Type
echo '<td class="datos2">' . __('Module type') . '</td>';
echo '<td class="datos2">';
echo '<select name="tipo">';
echo '<option value="' . $type . '">' . get_moduletype_name ($type);
$result = mysql_query('SELECT id_tipo, nombre FROM ttipo_modulo WHERE categoria IN (0,1,2) ORDER BY nombre;');
while ($row = mysql_fetch_array($result)){
	echo '<option value="' . $row['id_tipo'] . '">' . $row['nombre'] . '</option>';
}
echo '</select>';
echo '</td></tr>';
echo '<tr>';

// Component group
echo '<td class="datos">' . __('Group') . '</td>';
echo '<td class="datos">';
echo '<select name="id_group">';
echo '<option value="' . $id_group . '">' . give_network_component_group_name($id_group) . '</option>';
$result = mysql_query('SELECT * FROM tnetwork_component_group where id_sg != \'' . $id_group . '\';');
while ($row = mysql_fetch_array($result)) {
	echo '<option value="' . $row['id_sg'] . '">' . give_network_component_group_name($row['id_sg']) . '</option>';
}
echo '</select>';

// Module group
echo '<td class="datos">' . __('Module group') . '</td>';
echo '<td class="datos">';
echo '<select name="id_module_group">';
if ($id_nc != -1 ) {
	echo '<option value="' . $id_module_group . '">' . get_modulegroup_name($id_module_group);
}
$result = mysql_query('SELECT * FROM tmodule_group');
while ($row = mysql_fetch_array($result))
	echo '<option value="' . $row['id_mg'] . '">' . $row['name'] . '</option>';
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

// WMI Query
echo '<tr><td class="datos">' . __('WMI Query') ;
pandora_help("wmiquery");
echo '</td>';
echo '<td class="datos">';
echo 	'<input type="text" name="snmp_oid" size="25" value="' . $snmp_oid . '">';
echo '</td>';

// Key string
echo '<td class="datos">' . __('Key string');
pandora_help("wmikey");
echo '</td>';
echo '<td class="datos">';
echo 	'<input type="text" name="snmp_community" size="25" value="' . $snmp_community . '">';
echo '</td></tr>';

// Field
echo '<td class="datos2">' . __('Field number');
pandora_help("wmifield");
echo '</td>';
echo '<td class="datos2">';
echo	'<input type="text" name="tcp_port" size="5" value="' . $tcp_port . '">';
echo '</td>';

// Namespace
echo '<td class="datos2">' . __('Namespace');
pandora_help("wminamespace");
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

// Module type, hidden
echo '<input type="hidden" name="id_modulo" value="6">';

// Update/Add buttons
echo '<table width="700px">';
echo '</tr><td align="right">';
if ($id_nc != '-1')
	echo '<input name="updbutton" type="submit" class="sub upd" value="'.__('Update').'">';
else
	echo '<input name="crtbutton" type="submit" class="sub wand" value="'.__('Add').'">';
echo '</td></tr></table>';
echo '</form>';

?>
