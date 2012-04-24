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

	$search_parents = (bool) get_parameter ('search_parents');
	
	if ($search_parents) {
		require_once ('include/functions_agents.php');
		
		$id_agent = (int) get_parameter ('id_agent');
		$string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
		
		$filter = array ();
		
		switch ($config['dbtype']){
			case "mysql":
			case "postgresql":
				$filter[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
				break;
			case "oracle":
				$filter[] = '(upper(nombre) LIKE upper("%'.$string.'%") OR upper(direccion) LIKE upper("%'.$string.'%") OR upper(comentarios) LIKE upper("%'.$string.'%"))';
				break;
		}
		$filter[] = 'id_agente != '.$id_agent;
		
		$agents = agents_get_agents ($filter, array ('nombre', 'direccion'));
		if ($agents === false)
			return;
		
		foreach ($agents as $agent) {
			echo io_safe_output($agent['nombre']) . "|" . io_safe_output($agent['direccion'])  . "\n";
		}
		
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
		foreach($idSNMP as $id) {
			foreach($snmp[$id] as $key => $value){
				
				// Check if it has "ifXXXX" syntax and skip it 
				if (! preg_match  ( "/if/", $key)) {
					continue;
				}

				$oid_snmp[$value['oid']] = $key;
			}

			if($out === false){
				$out = $oid_snmp;
			}
			else{
				$out = array_intersect($out,$oid_snmp);
			}

			$oid_snmp = array();
		}

		echo json_encode($out);
	}
	    
 	return;
}
// Load global vars
enterprise_include ('godmode/agentes/agent_manager.php');

require_once ('include/functions_servers.php');
require_once ('include/functions_gis.php');
require_once($config['homedir'] . "/include/functions_agents.php");
require_once ($config['homedir'].'/include/functions_users.php');

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
}

echo '<div style="height: 5px">&nbsp;</div>';

if (!$new_agent) {
	// Agent remote configuration editor
	$agent_md5 = md5 ($nombre_agente, false);
	$filename['md5'] = $config["remote_config"]."/md5/".$agent_md5.".md5";
	$filename['conf'] = $config["remote_config"]."/conf/".$agent_md5.".conf";
} 

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

$table->width = '98%';
$table->class = "databox_color";

$table->head = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; width: 150px;';
$table->data = array ();

$table->data[0][0] = __('Agent name') . 
	ui_print_help_tip (__("The agent's name must be the same as the one defined at the console"), true);
$table->data[0][1] = html_print_input_text ('agente', $nombre_agente, '', 50, 100,true); 

if ($id_agente) {

    $table->data[0][1] .= "&nbsp;<b>".__("ID")."</b>&nbsp; $id_agente &nbsp;";
	$table->data[0][1] .= '&nbsp;&nbsp;<a href="index.php?sec=gagente&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'">';
	$table->data[0][1] .= html_print_image ("images/lupa.png", true, array ("border" => 0, "title" => __('Agent detail')));
	$table->data[0][1] .= '</a>';
}

// Remote configuration available
if (!$new_agent) {
	if (file_exists ($filename['md5'])) {
		$table->data[0][1] .= '&nbsp;&nbsp;<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=main&amp;id_agente='.$id_agente.'&amp;disk_conf='.$agent_md5.'">';
		$table->data[0][1] .= html_print_image ("images/application_edit.png", true, array ("border" => 0, "title" => __('This agent can be remotely configured')));
		$table->data[0][1] .= '</a>'.ui_print_help_tip (__('You can remotely edit this agent configuration'), true);
	}
}

// Delete link from here
if (!$new_agent) {
$table->data[0][1] .= "&nbsp;&nbsp;<span align='right'><a onClick=\"if (!confirm('" . __('Are you sure?') . "')) return false;\"  title='".__("Delete agent")."' href='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&borrar_agente=$id_agente&search=&offset=0&sort_field=&sort=none'><img src='images/cross.png'></a>";
}

$table->data[1][0] = __('IP Address');
$table->data[1][1] = html_print_input_text ('direccion', $direccion_agente, '', 16, 100, true);

if ($id_agente) {
	$table->data[1][1] .= '&nbsp;&nbsp;&nbsp;&nbsp;';
	
	$ip_all = agents_get_addresses ($id_agente);
		
	$table->data[1][1] .= html_print_select ($ip_all, "address_list", $direccion_agente, '', '', 0, true);
	$table->data[1][1] .= "&nbsp;". html_print_checkbox ("delete_ip", 1, false, true).__('Delete selected');	
}

$groups = users_get_groups ($config["id_user"], "AR",false);
$agents = agents_get_group_agents (array_keys ($groups));

$table->data[2][0] = __('Parent');
//Image src with skins
$src_code = html_print_image('images/lightning.png', true, false, true);
$table->data[2][1] = html_print_input_text_extended ('id_parent', agents_get_name ($id_parent), 'text-id_parent', '', 30, 100, false, '',
	array('style' => 'background: url(' . $src_code . ') no-repeat right;'), true)
	. ui_print_help_tip (__("Type at least two characters to search"), true);

$table->data[2][1] .= html_print_checkbox ("cascade_protection", 1, $cascade_protection, true).__('Cascade protection'). "&nbsp;" . ui_print_help_icon("cascade_protection", true);

$table->data[3][0] = __('Group');
$table->data[3][1] = html_print_select_groups(false, "AR", false, 'grupo', $grupo, '', '', 0, true);

$table->data[4][0] = __('Interval');

$table->data[4][1] = html_print_extended_select_for_time ('intervalo', $intervalo, '', '', '0', 10, true);

$table->data[5][0] = __('OS');
$table->data[5][1] = html_print_select_from_sql ('SELECT id_os, name FROM tconfig_os',
	'id_os', $id_os, '', '', '0', true);
$table->data[5][1] .= ' <span id="os_preview">';
$table->data[5][1] .= ui_print_os_icon ($id_os, false, true);
$table->data[5][1] .= '</span>';

// Network server
$none = '';
if ($server_name == '' && $id_agente)
	$none = __('None');
$table->data[6][0] = __('Server');
$table->data[6][1] = html_print_select (servers_get_names (),
	'server_name', $server_name, '', $none, 0, true);

// Description
$table->data[7][0] = __('Description');
$table->data[7][1] = html_print_input_text ('comentarios', $comentarios, '', 45, 255, true);

html_print_table ($table);
unset($table);

$table->width = '98%';
$table->class = "databox_color";

$table->head = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; width: 150px;';
$table->data = array ();

// Custom ID
$table->data[0][0] = __('Custom ID');
$table->data[0][1] = html_print_input_text ('custom_id', $custom_id, '', 16, 255, true);

// Learn mode / Normal mode
$table->data[1][0] = __('Module definition').ui_print_help_icon("module_definition", true);
$table->data[1][1] = __('Learning mode').' '.html_print_radio_button_extended ("modo", 1, '', $modo, false, '', 'style="margin-right: 40px;"', true);
$table->data[1][1] .= __('Normal mode').' '.html_print_radio_button_extended ("modo", 0, '', $modo, false, '', 'style="margin-right: 40px;"', true);

// Status (Disabled / Enabled)
$table->data[2][0] = __('Status');
$table->data[2][1] = __('Disabled').' '.html_print_radio_button_extended ("disabled", 1, '', $disabled, false, '', 'style="margin-right: 40px;"', true);
$table->data[2][1] .= __('Active').' '.html_print_radio_button_extended ("disabled", 0, '', $disabled, false, '', 'style="margin-right: 40px;"', true);

// Remote configuration
$table->data[3][0] = __('Remote configuration');

if (!$new_agent) {
	if (file_exists ($filename['md5'])) {
		$table->data[3][1] = date ("F d Y H:i:s", fileatime ($filename['md5']));
		// Delete remote configuration
		$table->data[3][1] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=main&amp;disk_conf_delete=1&amp;id_agente='.$id_agente.'">';
		$table->data[3][1] .= html_print_image ("images/cross.png", true, array ('title' => __('Delete remote configuration file'), 'style' => 'vertical-align: middle;')).'</a>';
		$table->data[3][1] .= '</a>'.ui_print_help_tip (__('Delete this conf file implies that for restore you must reactive remote config in the local agent.'), true);
	}
	else
		$table->data[3][1] = '<em>'.__('Not available').'</em>';		
}
else
	$table->data[3][1] = '<em>'.__('Not available').'</em>';
	
$listIcons = gis_get_array_list_icons();

$arraySelectIcon = array();
foreach ($listIcons as $index => $value) $arraySelectIcon[$index] = $index;

$path = 'images/gis_map/icons/'; //TODO set better method the path
$table->data[4][0] = __('Agent icon') . ui_print_help_tip(__('Agent icon for GIS Maps.'), true);
if($icon_path == '') {
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

$table->data[4][1] = html_print_select($arraySelectIcon, "icon_path", $icon_path, "changeIcons();", __('None'), '', true) .
	'&nbsp;' . __('Without status') . ': ' . html_print_image($path_without, true, array("id" => "icon_without_status", "style" => "display:".$display_icons.";")) .
	'&nbsp;' . __('Default') . ': ' . html_print_image($path_default, true, array("id" => "icon_default", "style" => "display:".$display_icons.";")) .
	'&nbsp;' . __('Ok') . ': ' . html_print_image($path_ok, true, array("id" => "icon_ok", "style" => "display:".$display_icons.";")) .

	'&nbsp;' . __('Bad') . ': ' . html_print_image($path_bad, true, array("id" => "icon_bad", "style" => "display:".$display_icons.";")) .

	'&nbsp;' . __('Warning') . ': ' . html_print_image($path_warning, true, array("id" => "icon_warning", "style" => "display:".$display_icons.";"));

if ($config['activate_gis']) {
	$table->data[5][0] = __('Ignore new GIS data:');
	$table->data[5][1] = __('Disabled').' '.html_print_radio_button_extended ("update_gis_data", 1, '', $update_gis_data, false, '', 'style="margin-right: 40px;"', true);
	$table->data[5][1] .= __('Enabled').' '.html_print_radio_button_extended ("update_gis_data", 0, '', $update_gis_data, false, '', 'style="margin-right: 40px;"', true);
}

ui_toggle(html_print_table ($table, true), __('Advanced options'));
unset($table);

$table->width = '98%';
$table->class = "databox_color";

$table->head = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; width: 150px;';
$table->data = array ();

$fields = db_get_all_fields_in_table('tagent_custom_fields');

if($fields === false) $fields = array();

foreach ($fields as $field) {
	
	$data[0] = '<b>'.$field['name'].'</b>';
		
	$custom_value = db_get_value_filter('description', 'tagent_custom_data', array('id_field' => $field['id_field'], 'id_agent' => $id_agente));
	
	if($custom_value === false) {
		$custom_value = '';
	}
	
	$data[1] = html_print_textarea ('customvalue_'.$field['id_field'], 2, 65, $custom_value, 'style="min-height: 30px;"', true);
	
	array_push ($table->data, $data);
}

if(!empty($fields)) {
	ui_toggle(html_print_table ($table, true), __('Custom fields'));
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id_agente) {
	html_print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
	html_print_input_hidden ('update_agent', 1);
	html_print_input_hidden ('id_agente', $id_agente);
} else {
	html_print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
	html_print_input_hidden ('create_agent', 1);
}
echo '</div></form>';

ui_require_jquery_file ('pandora.controls');
ui_require_jquery_file ('ajaxqueue');
ui_require_jquery_file ('bgiframe');
ui_require_jquery_file ('autocomplete');
?>
<script type="text/javascript">
/* <![CDATA[ */
	
//Use this function for change 3 icons when change the selectbox
function changeIcons() {
	icon = $("#icon_path :selected").val();

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
		$("#icon_without_status").attr("src", "<?php echo $path; ?>" + icon + ".default.png");
		$("#icon_default").attr("src", "<?php echo $path; ?>" + icon + ".default.png");
		$("#icon_ok").attr("src", "<?php echo $path; ?>" + icon + ".ok.png");
		$("#icon_bad").attr("src", "<?php echo $path; ?>" + icon + ".bad.png");
		$("#icon_warning").attr("src", "<?php echo $path; ?>" + icon + ".warning.png");
		$("#icon_without_status").attr("style", "");
		$("#icon_default").attr("style", "");
		$("#icon_ok").attr("style", "");
		$("#icon_bad").attr("style", "");
		$("#icon_warning").attr("style", "");
	}
	
	//$("#icon_default").attr("src", "<?php echo $path; ?>" + icon +
}

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
					$("#text-id_parent").css ('background-color', '');
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
