<?PHP

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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


// ========================
// AGENT GENERAL DATA FORM 
// ========================
// Load global vars
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access agent manager");
	require ("general/noaccess.php");
	return;
}

echo "<h2>".__('Agent configuration');
if (isset($_GET["create_agent"])){
	$create_agent = 1;
	echo " &gt; ".__('Create agent');
} else {
	echo " &gt; ".__('Update agent');
}
echo "</h2>";
echo "<div style='height: 5px'> </div>";

// Agent remote configuration editor
$agent_md5 = md5($nombre_agente, FALSE);
if (isset($_GET["disk_conf"])){
	require ("agent_disk_conf_editor.php");
	exit;
}

// Agent remote configuration DELETE
if (isset($_GET["disk_conf_delete"])){
	$agent_md5 = md5($nombre_agente, FALSE);
	$file_name = $config["remote_config"] . "/" . $agent_md5 . ".conf";
	unlink ($file_name);
	$file_name = $config["remote_config"] . "/" . $agent_md5 . ".md5";
	unlink ($file_name);
}

echo '<form name="conf_agent" method="post" action="index.php?sec=gagente&
sec2=godmode/agentes/configurar_agente">';
echo '<table width="650" id="table-agent-configuration" cellpadding="4" cellspacing="4" class="databox_color">';
echo "<tr>";
echo '<td class="datos"><b>'.__('Agent name').'</b><a href="#" class="tip">&nbsp;<span>'.__('The Agent\'s name must be the same as the one defined at the Console').'</span></a></td><td class="datos">';
print_input_text ('agente', $nombre_agente, '', 30, 100);

if (isset ($id_agente) && $id_agente != "") {
	echo "
	<a href='index.php?sec=estado&
	sec2=operation/agentes/ver_agente&id_agente=".$id_agente."'>
	<img src='images/lupa.png' border='0' align='middle' title='".__('Agent detail')."'></a>";
} 
// Remote configuration available
if (file_exists ($config["remote_config"] . "/" . $agent_md5 . ".md5")) {
	echo "
	<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente=".$id_agente."&disk_conf=" . $agent_md5 . "'>
	<img src='images/application_edit.png' border='0' align='middle' title='".__('This agent can be remotely configured')."'></a>";	
	echo '<a href="#" class="tip">&nbsp;<span>'.__('You can remotely edit this agent configuration').'</span></a>';
}

echo '<tr><td class="datos2">';
echo '<b>'.__('IP Address').'</b>';
echo '<td class="datos2">';
print_input_text ('direccion', $direccion_agente, '', 16, 100);

if ($create_agent != 1) {
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";

	echo '<select name="address_list">';
	$sql1 = "SELECT * FROM taddress, taddress_agent
		WHERE taddress.id_a = taddress_agent.id_a
		AND   taddress_agent.id_agent = $id_agente";
	if ($result=mysql_query($sql1))
		while ($row=mysql_fetch_array($result)){
			echo "<option value='".salida_limpia($row["ip"])."'>".salida_limpia($row["ip"])."&nbsp;&nbsp;";
		}
	echo "</select>";

	echo "<input name='delete_ip' type=checkbox value='1'> ".__('Delete selected');
	echo "</td>";
}

echo '<tr><td class="datos"><b>'.__('Parent').'</b>';
echo '<td class="datos">';
print_select_from_sql ('SELECT id_agente, nombre FROM tagente ORDER BY nombre',
				'id_parent', $id_parent, '', 'None', '0');

echo '<tr><td class="datos"><b>'.__('Group').'</b>';
echo '<td class="datos">';
print_select_from_sql ('SELECT id_grupo, nombre FROM tgrupo ORDER BY nombre',
			'grupo', $grupo, '', '', '');

echo "<tr><td class='datos2'>";
echo "<b>".__('Interval')."</b></td>";
echo '<td class="datos2">';

echo '<input type="text" name="intervalo" size="15" value="'.$intervalo.'"></td>';
echo '<tr><td class="datos"><b>'.__('OS').'</b></td>';
echo '<td class="datos">';
print_select_from_sql ('SELECT id_os, name FROM tconfig_os ORDER BY name',
			'id_os', $id_os, '', '', '');

// Network server
echo '<tr><td class="datos2"><b>'.__('Network Server').'</b>';
echo '<a href="#" class="tip">&nbsp;<span>'.__('You must select a Network Server for the Agent, so it can work properly with this kind of modules').'</span></a>';
echo '</td><td class="datos2">';
$none = '';
$none_value = '';
if ($id_network_server == 0) {
	$none = __('None');
	$none_value = 0;
}
print_select_from_sql ('SELECT id_server, name FROM tserver WHERE network_server = 1 ORDER BY name',
			'network_server', $id_network_server, '', $none, $none_value);

// Plugin Server
echo '<tr><td class="datos"><b>'.__('Plugin Server').'</b>';
echo '<a href="#" class="tip">&nbsp;<span>'.__('You must select a Plugin Server for the Agent, so it can work properly with this kind of modules').'</span></a>';
echo '</td><td class="datos">';
$none_str = __('None');
$none = '';
$none_value = '';
if ($id_plugin_server == 0) {
	$none = $none_str;
	$none_value = 0;
}
print_select_from_sql ('SELECT id_server, name FROM tserver WHERE plugin_server = 1 ORDER BY name',
			'plugin_server', $id_plugin_server, '', $none, $none_value);

// WMI Server
echo '<tr><td class="datos2"><b>'.__('WMI Server').'</b>';
echo '<a href="#" class="tip">&nbsp;<span>'.__('You must select a WMI Server for the Agent, so it can work properly with this kind of modules').'</span></a>';
echo '</td><td class="datos2">';
$none = '';
$none_value = '';
if ($id_plugin_server == 0) {
	$none = $none_str;
	$none_value = 0;
}
print_select_from_sql ('SELECT id_server, name FROM tserver WHERE wmi_server = 1 ORDER BY name',
			'wmi_server', $id_wmi_server, '', $none, $none_value);

// Prediction Server
echo '<tr><td class="datos"><b>'.__('Prediction Server').'</b>';
echo '<a href="#" class="tip">&nbsp;<span>'.__('You must select a Prediction Server for the Agent, so it can work properly with this kind of modules').'</span></a>';
echo '</td><td class="datos">';
$none = '';
$none_value = '';
if ($id_prediction_server == 0) {
	$none = $none_str;
	$none_value = 0;
}
print_select_from_sql ('SELECT id_server, name FROM tserver WHERE prediction_server = 1 ORDER BY name',
			'prediction_server', $id_prediction_server, '', $none, $none_value);

// Description
echo '<tr><td class="datos2"><b>';
echo __('Description');
echo '</b><td class="datos2">';
print_input_text ('comentarios', $comentarios, '', 45, 255);

// Learn mode / Normal mode 
echo '<tr><td class="datos"><b>';
echo __('Module definition');
pandora_help("module_definition");
echo '</b><td class="datos">';
echo __('Learning mode');
print_radio_button_extended ("modo", 1, '', $modo, false, '', 'style="margin-right: 40px;"');
echo __('Normal mode');
print_radio_button_extended ("modo", 0, '', $modo, false, '', 'style="margin-right: 40px;"');

// Status (Disabled / Enabled)
echo '<tr><td class="datos2"><b>'.__('Status').'</b>';
echo '<td class="datos2">';
echo __('Disabled');
print_radio_button_extended ("disabled", 1, '', $disabled, false, '', 'style="margin-right: 40px;"');
echo __('Active');
print_radio_button_extended ("disabled", 0, '', $disabled, false, '', 'style="margin-right: 40px;"');

// Remote configuration
echo '<tr><td class="datos"><b>'.__('Remote configuration').'</b>';
echo '<td class="datos">';
$filename = $config["remote_config"] . "/" . $agent_md5 . ".md5";
if (file_exists($filename)){
	echo date("F d Y H:i:s.", fileatime($filename));
	// Delete remote configuration
	echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&disk_conf_delete=1&id_agente=$id_agente'><img src='images/cross.png'></A>";
} else {
	echo '<i>'.__('Not available').'</i>';
}

echo '</table><table width="650"><tr><td  align="right">';
if ($create_agent == 1) {
	print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
	print_input_hidden ('create_agent', 1);
} else {
	print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
	print_input_hidden ('update_agent', 1);
	print_input_hidden ('id_agente', $id_agente);
}

echo "</td></form></table>";

?>
