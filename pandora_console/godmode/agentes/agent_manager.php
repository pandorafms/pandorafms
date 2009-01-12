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
enterprise_include ('godmode/agentes/agent_manager.php');

if (!isset ($id_agente)) {
	die ("Not Authorized");
}

// ========================
// AGENT GENERAL DATA FORM 
// ========================

echo "<h2>".__('Agent configuration');
if (isset($_GET["create_agent"])) {
	$create_agent = 1;
	echo " &gt; ".__('Create agent');
} else {
	echo " &gt; ".__('Update agent');
}
echo "</h2>";
echo '<div style="height: 5px">&nbsp;</div>';

// Agent remote configuration editor
$agent_md5 = md5 ($nombre_agente, FALSE);
$filename['md5'] = $config["remote_config"] . "/" . $agent_md5 . ".md5";
$filename['conf'] = $config["remote_config"] . "/" . $agent_md5 . ".conf"; 

if (isset ($_GET["disk_conf"])) {
	require ("agent_disk_conf_editor.php");
	exit;
}

// Agent remote configuration DELETE
if (isset($_GET["disk_conf_delete"])) {
	//TODO: Get this working on computers where the Pandora server(s) are not on the webserver
	//TODO: Get a remote_config editor working in the open version
	unlink ($filename['md5']);
	unlink ($filename['conf']);
}

echo '<form name="conf_agent" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente">';

$table->width = 650;
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox_color";

$table->head = array ();
$table->data = array ();

$table->data[0][0] = '<b>'.__('Agent name').'</b>'.print_help_tip (__("The agent's name must be the same as the one defined at the console"), true);
$table->data[0][1] = print_input_text ('agente', $nombre_agente, '', 30, 100,true); 

if (isset ($id_agente) && $id_agente != "") {
	$table->data[0][1] .= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'">
	<img src="images/lupa.png" border="0" title="'.__('Agent detail').'"></a>';
}

// Remote configuration available
if (file_exists ($filename['md5'])) {
	$table->data[0][1] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente='.$id_agente.'&disk_conf='.$agent_md5.'">
	<img src="images/application_edit.png" border="0" title="'.__('This agent can be remotely configured').'"></a>'.print_help_tip (__('You can remotely edit this agent configuration'), true);
}

$table->data[1][0] = '<b>'.__('IP Address').'</b>';
$table->data[1][1] = print_input_text ('direccion', $direccion_agente, '', 16, 100, true);

if ($create_agent != 1) {
	$table->data[1][1] .= '&nbsp;&nbsp;&nbsp;&nbsp;';
	
	$ip_all = get_agent_addresses ($id_agente);
		
	$table->data[1][1] .= print_select ($ip_all, "address_list", $direccion_agente, '', '', 0, true);
	$table->data[1][1] .= print_checkbox ("delete_ip", 1, false, true).__('Delete selected');	
}

$groups = get_user_groups ($config["id_user"]);
$agents = get_group_agents (array_keys ($groups));

$table->data[2][0] = '<b>'.__('Parent').'</b>';
$table->data[2][1] = print_select ($agents, 'id_parent', $id_parent, '', __('None'), 0, true, false, false); //I use get_agent_name because the user might not have rights to the current parent

$table->data[3][0] = '<b>'.__('Group').'</b>';
$table->data[3][1] = print_select_from_sql ('SELECT id_grupo, nombre FROM tgrupo WHERE id_grupo > 1 ORDER BY nombre', 'grupo', $grupo, '', '', 0, true);

$table->data[4][0] = '<b>'.__('Interval').'</b>';
$table->data[4][1] = print_input_text ('intervalo', $intervalo, '', 16, 100, true);

$table->data[5][0] = '<b>'.__('OS').'</b>';
$table->data[5][1] = print_select_from_sql ('SELECT id_os, name FROM tconfig_os ORDER BY name', 'id_os', $id_os, '', '', '0', true);

// Network server
$table->data[6][0] = '<b>'.__('Network Server').'</b>'.print_help_tip (__('You must select a Network Server for the Agent, so it can work properly with this kind of modules'), true);
$table->data[6][1] = print_select_from_sql ('SELECT id_server, name FROM tserver WHERE network_server = 1 ORDER BY name', 'network_server', $id_network_server, '', '', 0, true);

// Plugin server
$table->data[7][0] = '<b>'.__('Plugin Server').'</b>'.print_help_tip (__('You must select a Plugin Server for the Agent, so it can work properly with this kind of modules'), true);
$table->data[7][1] = print_select_from_sql ('SELECT id_server, name FROM tserver WHERE plugin_server = 1 ORDER BY name', 'plugin_server', $id_plugin_server, '', '', 0, true);

// WMI Server
$table->data[8][0] = '<b>'.__('WMI Server').'</b>'.print_help_tip (__('You must select a WMI Server for the Agent, so it can work properly with this kind of modules'), true);
$table->data[8][1] = print_select_from_sql ('SELECT id_server, name FROM tserver WHERE wmi_server = 1 ORDER BY name', 'wmi_server', $id_wmi_server, '', '', 0, true);

// Prediction Server
$table->data[9][0] = '<b>'.__('Prediction Server').'</b>'.print_help_tip (__('You must select a Prediction Server for the Agent, so it can work properly with this kind of modules'), true);
$table->data[9][1] = print_select_from_sql ('SELECT id_server, name FROM tserver WHERE prediction_server = 1 ORDER BY name', 'prediction_server', $id_prediction_server, '', '', 0, true);

enterprise_hook ('inventory_server');

// Custom ID
$table->data[10][0] = '<b>'.__('Custom ID').'</b>';
$table->data[10][1] = print_input_text ('custom_id', $custom_id, '', 16, 255, true);

// Description
$table->data[11][0] = '<b>'.__('Description').'</b>';
$table->data[11][1] = print_input_text ('comentarios', $comentarios, '', 45, 255, true);

// Learn mode / Normal mode
$table->data[12][0] = '<b>'.__('Module definition').'</b>'.pandora_help("module_definition", true);
$table->data[12][1] = __('Learning mode').' '.print_radio_button_extended ("modo", 1, '', $modo, false, '', 'style="margin-right: 40px;"', true);
$table->data[12][1] .= __('Normal mode').' '.print_radio_button_extended ("modo", 0, '', $modo, false, '', 'style="margin-right: 40px;"', true);

// Status (Disabled / Enabled)
$table->data[13][0] = '<b>'.__('Status').'</b>';
$table->data[13][1] = __('Disabled').' '.print_radio_button_extended ("disabled", 1, '', $disabled, false, '', 'style="margin-right: 40px;"', true);
$table->data[13][1] .= __('Active').' '.print_radio_button_extended ("disabled", 0, '', $disabled, false, '', 'style="margin-right: 40px;"', true);

// Remote configuration
$table->data[14][0] = '<b>'.__('Remote configuration').'</b>';


if (file_exists ($filename['md5'])) {
	$table->data[14][1] = date ("F d Y H:i:s.", fileatime ($filename['md5']));
	// Delete remote configuration
	$table->data[14][1] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&disk_conf_delete=1&id_agente=$id_agente"><img src="images/cross.png" /></a>';
} else {
	$table->data[14][1] = '<i>'.__('Not available').'</i>';
}

print_table ($table);
unset ($table);

echo '<div style="width: 650px; text-align: right;">';
if ($create_agent == 1) {
	print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
	print_input_hidden ('create_agent', 1);
} else {
	print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
	print_input_hidden ('update_agent', 1);
	print_input_hidden ('id_agente', $id_agente);
}
echo '</div>';
?>
