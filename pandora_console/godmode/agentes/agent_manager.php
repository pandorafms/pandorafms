<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

if (is_ajax ()) {
	$search_parents = (bool) get_parameter ('search_parents');
	
	if ($search_parents) {
		require_once ('include/functions_agents.php');
		
		$id_agent = (int) get_parameter ('id_agent');
		$string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
		
		$filter = array ();
		$filter[] = 'nombre LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%"';
		$filter[] = 'id_agente != '.$id_agent;
		
		$agents = get_agents ($filter, array ('nombre', 'direccion'));
		
		foreach ($agents as $agent) {
			echo $agent['nombre']."|".$agent['direccion']."\n";
		}
		
		return;
 	}
 	
 	return;
}
// Load global vars
enterprise_include ('godmode/agentes/agent_manager.php');

require_once ('include/functions_servers.php');

$new_agent = (bool) get_parameter ('new_agent');

if (! isset ($id_agente) && ! $new_agent) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access agent manager witout an agent");
	require ("general/noaccess.php");
	return;
}

if ($new_agent) {
	if (! empty ($direccion_agente) && empty ($nombre_agente))
		$nombre_agente = $direccion_agente;
}

echo "<h2>".__('Agent configuration')." &raquo; ";

if ($id_agente) {
	echo __('Update agent');
} else {
	echo __('Create agent');
}
echo "</h2>";
echo '<div style="height: 5px">&nbsp;</div>';

// Agent remote configuration editor
$agent_md5 = md5 ($nombre_agente, false);
$filename['md5'] = $config["remote_config"]."/".$agent_md5.".md5";
$filename['conf'] = $config["remote_config"]."/".$agent_md5.".conf"; 

$disk_conf = (bool) get_parameter ('disk_conf');

if ($disk_conf) {
	require ("agent_disk_conf_editor.php");
	return;
}

$disk_conf_delete = (bool) get_parameter ('disk_conf_delete');
// Agent remote configuration DELETE
if ($disk_conf_delete) {
	//TODO: Get this working on computers where the Pandora server(s) are not on the webserver
	//TODO: Get a remote_config editor working in the open version
	@unlink ($filename['md5']);
	@unlink ($filename['conf']);
}

echo '<form name="conf_agent" method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente">';

$table->width = '95%';
$table->class = "databox_color";

$table->head = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->data = array ();

$table->data[0][0] = __('Agent name').print_help_tip (__("The agent's name must be the same as the one defined at the console"), true);
$table->data[0][1] = print_input_text ('agente', $nombre_agente, '', 30, 100,true); 

if ($id_agente) {
	$table->data[0][1] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'">';
	$table->data[0][1] .= print_image ("images/lupa.png", true, array ("border" => 0, "title" => __('Agent detail')));
	$table->data[0][1] .= '</a>';
}

// Remote configuration available
if (file_exists ($filename['md5'])) {
	$table->data[0][1] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=main&amp;id_agente='.$id_agente.'&amp;disk_conf='.$agent_md5.'">';
	$table->data[0][1] .= print_image ("images/application_edit.png", true, array ("border" => 0, "title" => __('This agent can be remotely configured')));
	$table->data[0][1] .= '</a>'.print_help_tip (__('You can remotely edit this agent configuration'), true);
}

$table->data[1][0] = __('IP Address');
$table->data[1][1] = print_input_text ('direccion', $direccion_agente, '', 16, 100, true);

if ($id_agente) {
	$table->data[1][1] .= '&nbsp;&nbsp;&nbsp;&nbsp;';
	
	$ip_all = get_agent_addresses ($id_agente);
		
	$table->data[1][1] .= print_select ($ip_all, "address_list", $direccion_agente, '', '', 0, true);
	$table->data[1][1] .= print_checkbox ("delete_ip", 1, false, true).__('Delete selected');	
}

$groups = get_user_groups ($config["id_user"]);
$agents = get_group_agents (array_keys ($groups));

$table->data[2][0] = __('Parent');
$table->data[2][1] = print_input_text ('id_parent', get_agent_name ($id_parent),
	'', 30, 100, true);


$table->data[3][0] = __('Group');
$table->data[3][1] = print_select_from_sql ('SELECT id_grupo, nombre FROM tgrupo WHERE id_grupo > 1 ORDER BY nombre', 'grupo', $grupo, '', '', 0, true);

$table->data[4][0] = __('Interval');
$table->data[4][1] = print_input_text ('intervalo', $intervalo, '', 16, 100, true);

$table->data[5][0] = __('OS');
$table->data[5][1] = print_select_from_sql ('SELECT id_os, name FROM tconfig_os',
	'id_os', $id_os, '', '', '0', true);
$table->data[5][1] .= ' <span id="os_preview">';
$table->data[5][1] .= print_os_icon ($id_os, false, true);
$table->data[5][1] .= '</span>';

// Network server
$none = '';
if ($server_name == '' && $id_agente)
	$none = __('None');
$table->data[6][0] = __('Server');
$table->data[6][1] = print_select (get_server_names (),
	'server_name', $server_name, '', $none, 0, true);

// Custom ID
$table->data[7][0] = __('Custom ID');
$table->data[7][1] = print_input_text ('custom_id', $custom_id, '', 16, 255, true);

// Description
$table->data[8][0] = __('Description');
$table->data[8][1] = print_input_text ('comentarios', $comentarios, '', 45, 255, true);

// Learn mode / Normal mode
$table->data[9][0] = __('Module definition').print_help_icon("module_definition", true);
$table->data[9][1] = __('Learning mode').' '.print_radio_button_extended ("modo", 1, '', $modo, false, '', 'style="margin-right: 40px;"', true);
$table->data[9][1] .= __('Normal mode').' '.print_radio_button_extended ("modo", 0, '', $modo, false, '', 'style="margin-right: 40px;"', true);

// Status (Disabled / Enabled)
$table->data[10][0] = __('Status');
$table->data[10][1] = __('Disabled').' '.print_radio_button_extended ("disabled", 1, '', $disabled, false, '', 'style="margin-right: 40px;"', true);
$table->data[10][1] .= __('Active').' '.print_radio_button_extended ("disabled", 0, '', $disabled, false, '', 'style="margin-right: 40px;"', true);

// Remote configuration
$table->data[11][0] = __('Remote configuration');

if (file_exists ($filename['md5'])) {
	$table->data[11][1] = date ("F d Y H:i:s", fileatime ($filename['md5']));
	// Delete remote configuration
	$table->data[11][1] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=main&amp;disk_conf_delete=1&amp;id_agente='.$id_agente.'">';
	$table->data[11][1] .= print_image ("images/cross.png", true).'</a>';
} else {
	$table->data[11][1] = '<em>'.__('Not available').'</em>';
}

print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id_agente) {
	print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
	print_input_hidden ('update_agent', 1);
	print_input_hidden ('id_agente', $id_agente);
} else {
	print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
	print_input_hidden ('create_agent', 1);
}
echo '</div></form>';

require_jquery_file ('pandora.controls');
require_jquery_file ('ajaxqueue');
require_jquery_file ('bgiframe');
require_jquery_file ('autocomplete');
?>
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("select#id_os").pandoraSelectOS ();
	$("#text-id_parent").autocomplete ("ajax.php",
		{
			scroll: true,
			minChars: 2,
			extraParams: {
				page: "godmode/agentes/agent_manager",
				search_parents: 1,
				id_group: function() { return $("#grupo").val(); },
				id_agent: <?php echo $id_agente ?>
			},
			formatItem: function (data, i, total) {
				if (total == 0)
					$("#text-id_parent").css ('background-color', '#cc0000');
				else
					$("#text-id_parent").css ('background-color', 'none');
				if (data == "")
					return false;
				return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
			},
			delay: 200
		}
	);
});
/* ]]> */
</script>
