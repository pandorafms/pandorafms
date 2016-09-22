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

if (is_ajax ()) {
	
	global $config;
	
	$search_parents_2 = (bool) get_parameter ('search_parents_2');
	
	if ($search_parents_2) {
		require_once ('include/functions_agents.php');
		
		$id_agent = (int) get_parameter ('id_agent');
		$string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
		
		$filter = array ();
		
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				$filter[] = '(nombre COLLATE utf8_general_ci LIKE "%' . $string . '%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
				break;
			case "oracle":
				$filter[] = '(upper(nombre) LIKE upper(\'%'.$string.'%\') OR upper(direccion) LIKE upper(\'%'.$string.'%\') OR upper(comentarios) LIKE upper(\'%'.$string.'%\'))';
				break;
		}
		$filter[] = 'id_agente != ' . $id_agent;
		
		$agents = agents_get_agents ($filter, array ('id_agente', 'nombre', 'direccion'));
		if ($agents === false)
			$agents = array();
		
		$data = array();
		foreach ($agents as $agent) {
			$data[] = array('id' => $agent['id_agente'],
				'name' => io_safe_output($agent['nombre']),
				'ip' => io_safe_output($agent['direccion']));
		}
		
		echo io_json_mb_encode($data);
		
		return;
	}
	
	$get_modules_json_for_multiple_snmp = (bool) get_parameter("get_modules_json_for_multiple_snmp", 0);
	if ($get_modules_json_for_multiple_snmp) {
		require_once ('include/graphs/functions_utils.php');
		
		$idSNMP = get_parameter('id_snmp');
		
		$id_snmp_serialize = get_parameter('id_snmp_serialize');
		$snmp = unserialize_in_temp($id_snmp_serialize, false);
		
		$oid_snmp = array();
		$out = false;
		foreach ($idSNMP as $id) {
			foreach ($snmp[$id] as $key => $value) {
				
				// Check if it has "ifXXXX" syntax and skip it
				if (! preg_match  ( "/if/", $key)) {
					continue;
				}
				
				$oid_snmp[$value['oid']] = $key;
			}
			
			if ($out === false) {
				$out = $oid_snmp;
			}
			else {
				$out = array_intersect($out,$oid_snmp);
			}
			
			$oid_snmp = array();
		}
		
		echo io_json_mb_encode($out);
	}
	
	return;
}
// Load global vars
enterprise_include ('godmode/agentes/agent_manager.php');

require_once ('include/functions_clippy.php');
require_once ('include/functions_servers.php');
require_once ('include/functions_gis.php');
require_once($config['homedir'] . "/include/functions_agents.php");
require_once ($config['homedir'] . '/include/functions_users.php');

ui_require_javascript_file('openlayers.pandora');

$new_agent = (bool) get_parameter ('new_agent');

if (! isset ($id_agente) && ! $new_agent) {
	db_pandora_audit("ACL Violation", "Trying to access agent manager witout an agent");
	require ("general/noaccess.php");
	return;
}

if ($new_agent) {
	if (! empty ($direccion_agente) && empty ($nombre_agente))
		$nombre_agente = $direccion_agente;
	
	$servers = servers_get_names();
	if (!empty($servers)) {
		$array_keys_servers = array_keys($servers);
		$server_name = reset($array_keys_servers);
	}
}

if (!$new_agent) {
	// Agent remote configuration editor
	enterprise_include_once('include/functions_config_agents.php');
	if (enterprise_installed()) {
		$filename = config_agents_get_agent_config_filenames($id_agente);
	}
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

$table = new stdClass();
$table->width = '100%';
$table->class = "databox filters";

$table->head = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; width: 150px;';
$table->data = array ();

$table->align[2] = 'center';
html_debug($alias);
if(!$new_agent && $alias != ''){
	$table->data[0][0] = __('Agent name') .
		ui_print_help_tip (__("The agent's name must be the same as the one defined at the console"), true);
	$table->data[0][1] = html_print_input_text ('agente', $nombre_agente, '', 50, 100,true);

	$table->data[0][2] = __('QR Code Agent view');

	if ($id_agente) {

		$table->data[0][1] .= "&nbsp;<b>" . __("ID") . "</b>&nbsp; $id_agente &nbsp;";
		$table->data[0][1] .= '&nbsp;&nbsp;<a href="index.php?sec=gagente&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'">';
		$table->data[0][1] .= html_print_image ("images/zoom.png",
			true, array ("border" => 0, "title" => __('Agent detail')));
		$table->data[0][1] .= '</a>';
	}
}




// Remote configuration available
if (!$new_agent) {
	if (isset($filename)) {
		if (file_exists ($filename['md5'])) {
			$agent_name = agents_get_name($id_agente);
			$agent_name = io_safe_output($agent_name);
			$agent_md5 = md5 ($agent_name, false);
			
			$table->data[0][1] .= '&nbsp;&nbsp;' .
				'<a href="index.php?' .
					'sec=gagente&amp;' .
					'sec2=godmode/agentes/configurar_agente&amp;' .
					'tab=remote_configuration&amp;' .
					'id_agente=' . $id_agente . '&amp;' .
					'disk_conf=' . $agent_md5 . '">';
			$table->data[0][1] .= html_print_image(
				"images/application_edit.png",
				true,
				array("border" => 0,
					"title" => __('This agent can be remotely configured')));
			$table->data[0][1] .= '</a>' .
				ui_print_help_tip (
					__('You can remotely edit this agent configuration'), true);
		}
	}
}

// Delete link from here
if (!$new_agent) {
	$table->data[0][1] .= "&nbsp;&nbsp;<span align='right'><a onClick=\"if (!confirm('" . __('Are you sure?') . "')) return false;\" href='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&borrar_agente=$id_agente&search=&offset=0&sort_field=&sort=none'>" . html_print_image('images/cross.png', true, array('title' => __("Delete agent"))) . "</a>";
}
$table->data[1][0] = __('Alias');
$table->data[1][1] = html_print_input_text ('alias', $alias, '', 50, 100, true);



$table->data[2][0] = __('IP Address');
$table->data[2][1] = html_print_input_text ('direccion', $direccion_agente, '', 16, 100, true);

if ($id_agente) {
	$table->data[2][1] .= '&nbsp;&nbsp;&nbsp;&nbsp;';

	$ip_all = agents_get_addresses ($id_agente);

	$table->data[2][1] .= html_print_select ($ip_all, "address_list", $direccion_agente, '', '', 0, true);
	$table->data[2][1] .= "&nbsp;". html_print_checkbox ("delete_ip", 1, false, true).__('Delete selected');
}

?>
<style type="text/css">
	#qr_code_agent_view img {
		display: inline !important;
	}
</style>
<?php
if(!$new_agent){
	$table->rowspan[2][2] = 3;
	if ($id_agente) {
		$table->data[2][2] =
			"<a id='qr_code_agent_view' href='javascript: show_dialog_qrcode(null, \"" .
				ui_get_full_url('index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $id_agente) . "\" );'></a>";
	}
	else {
		$table->data[2][2] = __("Only it is show when<br />the agent is saved.");
	}
}


$groups = users_get_groups ($config["id_user"], "AR",false);
$agents = agents_get_group_agents (array_keys ($groups));

$table->data[3][0] = __('Parent');
$params = array();
$params['return'] = true;
$params['show_helptip'] = true;
$params['input_name'] = 'id_parent';
$params['value'] = agents_get_name ($id_parent);
$table->data[3][1] = ui_print_agent_autocomplete_input($params);

$table->data[3][1] .= html_print_checkbox ("cascade_protection", 1, $cascade_protection, true).__('Cascade protection'). "&nbsp;" . ui_print_help_icon("cascade_protection", true);

$table->data[4][0] = __('Group');
$table->data[4][1] = html_print_select_groups(false, "AR", false, 'grupo', $grupo, '', '', 0, true);
$table->data[4][1] .= ' <span id="group_preview">';
$table->data[4][1] .= ui_print_group_icon ($grupo, true);
$table->data[4][1] .= '</span>';

$table->data[5][0] = __('Interval');

$table->data[5][1] = html_print_extended_select_for_time ('intervalo', $intervalo, '', '', '0', 10, true);
if ($intervalo < SECONDS_5MINUTES) {
	$table->data[5][1] .= clippy_context_help("interval_agent_min");
}
$table->data[6][0] = __('OS');
$table->data[6][1] = html_print_select_from_sql ('SELECT id_os, name FROM tconfig_os',
	'id_os', $id_os, '', '', '0', true);
$table->data[6][1] .= ' <span id="os_preview">';
$table->data[6][1] .= ui_print_os_icon ($id_os, false, true);
$table->data[6][1] .= '</span>';

// Network server
$servers = servers_get_names();
if (!array_key_exists($server_name, $servers)) {
	$server_Name = 0; //Set the agent have not server.
}
$table->data[7][0] = __('Server');
if ($new_agent) {
	//Set first server by default.
	$servers_get_names = servers_get_names();
	$array_keys_servers_get_names = array_keys($servers_get_names);
	$server_name = reset($array_keys_servers_get_names);
}
$table->data[7][1] = html_print_select (servers_get_names (),
	'server_name', $server_name, '', __('None'), 0, true). ' ' . ui_print_help_icon ('agent_server', true);

// Description
$table->data[8][0] = __('Description');
$table->data[8][1] = html_print_input_text ('comentarios', $comentarios,
	'', 45, 200, true);

html_print_table ($table);
unset($table);

$table = new stdClass();
$table->width = '100%';
$table->class = "databox filters";

$table->head = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; ';
$table->style[2] = 'font-weight: bold;';
$table->data = array ();

// Custom ID
$table->data[0][0] = __('Custom ID');
$table->data[0][1] = html_print_input_text ('custom_id', $custom_id, '', 16, 255, true);

// Learn mode / Normal mode
$table->data[1][0] = __('Module definition') .
	ui_print_help_icon("module_definition", true);
$table->data[1][1] = __('Learning mode') . ' ' .
	html_print_radio_button_extended ("modo", 1, '', $modo, false, 'show_modules_not_learning_mode_context_help();',
		'style="margin-right: 40px;"', true);
$table->data[1][1] .= __('Normal mode') . ' ' .
	html_print_radio_button_extended ("modo", 0, '', $modo, false, 'show_modules_not_learning_mode_context_help();',
		'style="margin-right: 40px;"', true);
$table->data[1][1] .= __('Autodisable mode') . ' ' .
	html_print_radio_button_extended ("modo", 2, '', $modo, false, 'show_modules_not_learning_mode_context_help();',
		'style="margin-right: 40px;"', true);

// Status (Disabled / Enabled)
$table->data[2][0] = __('Status');
$table->data[2][1] = __('Disabled') . ' ' .
	html_print_radio_button_extended ("disabled", 1, '', $disabled, false, '', 'style="margin-right: 40px;"', true);
$table->data[2][1] .= __('Active') . ' ' .
	html_print_radio_button_extended ("disabled", 0, '', $disabled, false, '', 'style="margin-right: 40px;"', true);

// Remote configuration
$table->data[3][0] = __('Remote configuration');

if (!$new_agent) {
	$table->data[3][1] = '<em>' . __('Not available') . '</em>';
	if (isset($filename)) {
		if (file_exists ($filename['md5'])) {
			$table->data[3][1] = date ("F d Y H:i:s", fileatime ($filename['md5']));
			// Delete remote configuration
			$table->data[3][1] .= '<a href="index.php?' .
				'sec=gagente&amp;' .
				'sec2=godmode/agentes/configurar_agente&amp;' .
				'tab=main&amp;' .
				'disk_conf_delete=1&amp;' .
				'id_agente=' . $id_agente . '">';
			$table->data[3][1] .= html_print_image(
				"images/cross.png", true,
				array ('title' => __('Delete remote configuration file'), 'style' => 'vertical-align: middle;')).'</a>';
			$table->data[3][1] .= '</a>' .
				ui_print_help_tip(
					__('Delete this conf file implies that for restore you must reactive remote config in the local agent.'),
					true);
		}
	}
}
else
	$table->data[3][1] = '<em>' . __('Not available') . '</em>';

$listIcons = gis_get_array_list_icons();

$arraySelectIcon = array();
foreach ($listIcons as $index => $value)
	$arraySelectIcon[$index] = $index;

$path = 'images/gis_map/icons/'; //TODO set better method the path
$table->data[0][2] = __('Agent icon') . ui_print_help_tip(__('Agent icon for GIS Maps.'), true);
if ($icon_path == '') {
	$display_icons = 'none';
	// Hack to show no icon. Use any given image to fix not found image errors
	$path_without = "images/spinner.png";
	$path_default = "images/spinner.png";
	$path_ok = "images/spinner.png";
	$path_bad = "images/spinner.png";
	$path_warning = "images/spinner.png";
}
else {
	$display_icons = '';
	$path_without = $path . $icon_path . ".default.png";
	$path_default = $path . $icon_path . ".default.png";
	$path_ok = $path . $icon_path . ".ok.png";
	$path_bad = $path . $icon_path . ".bad.png";
	$path_warning = $path . $icon_path . ".warning.png";
}

$table->data[0][3] = html_print_select($arraySelectIcon, "icon_path",
	$icon_path, "changeIcons();", __('None'), '', true) .
	'&nbsp;' . html_print_image($path_ok, true,
		array("id" => "icon_ok", "style" => "display:".$display_icons.";")) .
	'&nbsp;' . html_print_image($path_bad, true,
		array("id" => "icon_bad", "style" => "display:".$display_icons.";")) .
	'&nbsp;' . html_print_image($path_warning, true,
		array("id" => "icon_warning", "style" => "display:".$display_icons.";"));

if ($config['activate_gis']) {
	$table->data[1][2] = __('Ignore new GIS data:');
	$table->data[1][3] = __('Yes') . ' ' .
		html_print_radio_button_extended ("update_gis_data", 0, '',
			$update_gis_data, false, '', 'style="margin-right: 40px;"', true);
	$table->data[1][3] .= __('No') . ' ' .
		html_print_radio_button_extended ("update_gis_data", 1, '',
			$update_gis_data, false, '', 'style="margin-right: 40px;"', true);
}

$table->data[2][2] = __('Url address');
$table->data[2][3] = html_print_input_text ('url_description',
	$url_description, '', 45, 255, true);

$table->data[3][2] = __('Quiet');
$table->data[3][3] = ui_print_help_tip(
	__('The agent still runs but the alerts and events will be stop'), true);
$table->data[3][3] .= html_print_checkbox('quiet', 1, $quiet, true);

ui_toggle(html_print_table ($table, true), __('Advanced options'));
unset($table);

$table = new stdClass();
$table->width = '100%';
$table->class = "databox filters";

$table->head = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; width: 100px;';
$table->data = array ();

$fields = db_get_all_fields_in_table('tagent_custom_fields');

if ($fields === false) $fields = array();

foreach ($fields as $field) {
	
	$data[0] = '<b>'.$field['name'].'</b>';
	$data[0] .= ui_print_help_tip(
		__('This field allows url insertion using the BBCode\'s url tag')
		. '.<br />'
		. __('The format is: [url=\'url to navigate\']\'text to show\'[/url]')
		. '.<br /><br />'
		. __('e.g.: [url=pandorafms.org]Pandora FMS Community[/url]')
		, true);
	
	$custom_value = db_get_value_filter('description',
		'tagent_custom_data',
		array('id_field' => $field['id_field'], 'id_agent' => $id_agente));
	
	if ($custom_value === false) {
		$custom_value = '';
	}
	
	$data[1] = html_print_textarea ('customvalue_'.$field['id_field'],
		2, 65, $custom_value, 'style="min-height: 30px; width:96%;"', true);
	
	array_push ($table->data, $data);
}

if (!empty($fields)) {
	ui_toggle(html_print_table ($table, true), __('Custom fields'));
}

echo '<div class="action-buttons" style="width: ' . $table->width . '">';


//The context help about the learning mode
if ($modo == 0) {
	echo "<span id='modules_not_learning_mode_context_help' style=''>";
}
else {
	echo "<span id='modules_not_learning_mode_context_help' style='display: none;'>";
}
echo clippy_context_help("modules_not_learning_mode");
echo "</span>";



if ($id_agente) {
	html_print_submit_button (__('Update'), 'updbutton', false,
		'class="sub upd"');
	html_print_input_hidden ('update_agent', 1);
	html_print_input_hidden ('id_agente', $id_agente);
}
else {
	html_print_submit_button (__('Create'), 'crtbutton', false,
		'class="sub wand"');
	html_print_input_hidden ('create_agent', 1);
}
echo '</div></form>';

ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');

?>

<script type="text/javascript">
	//Use this function for change 3 icons when change the selectbox
	function changeIcons() {
		var icon = $("#icon_path :selected").val();
		
		$("#icon_without_status").attr("src", "images/spinner.png");
		$("#icon_default").attr("src", "images/spinner.png");
		$("#icon_ok").attr("src", "images/spinner.png");
		$("#icon_bad").attr("src", "images/spinner.png");
		$("#icon_warning").attr("src", "images/spinner.png");
		
		if (icon.length == 0) {
			$("#icon_without_status").attr("style", "display:none;");
			$("#icon_default").attr("style", "display:none;");
			$("#icon_ok").attr("style", "display:none;");
			$("#icon_bad").attr("style", "display:none;");
			$("#icon_warning").attr("style", "display:none;");
		}
		else {
			$("#icon_without_status").attr("src",
				"<?php echo $path; ?>" + icon + ".default.png");
			$("#icon_default").attr("src",
				"<?php echo $path; ?>" + icon + ".default.png");
			$("#icon_ok").attr("src",
				"<?php echo $path; ?>" + icon + ".ok.png");
			$("#icon_bad").attr("src",
				"<?php echo $path; ?>" + icon + ".bad.png");
			$("#icon_warning").attr("src",
				"<?php echo $path; ?>" + icon + ".warning.png");
			$("#icon_without_status").attr("style", "");
			$("#icon_default").attr("style", "");
			$("#icon_ok").attr("style", "");
			$("#icon_bad").attr("style", "");
			$("#icon_warning").attr("style", "");
		}
	}
	
	function show_modules_not_learning_mode_context_help() {
		if ($("input[name='modo'][value=0]").is(':checked')) {
			$("#modules_not_learning_mode_context_help").show();
		}
		else {
			$("#modules_not_learning_mode_context_help").hide();
		}
	}
	
	$(document).ready (function() {
		$("select#id_os").pandoraSelectOS ();
		
		paint_qrcode(
			"<?php
			echo ui_get_full_url('index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $id_agente);
			?>",
			"#qr_code_agent_view", 128, 128);
		$("#text-agente").prop('disabled', true);

	});
</script>
