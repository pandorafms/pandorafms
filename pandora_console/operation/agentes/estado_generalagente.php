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



// Load global vars
global $config;

require_once ("include/functions_agents.php");

require_once ($config["homedir"] . '/include/functions_graph.php');
include_graphs_dependencies();
require_once ($config['homedir'] . '/include/functions_groups.php');


check_login ();


$id_agente = get_parameter_get ("id_agente", -1);

$agent = db_get_row ("tagente", "id_agente", $id_agente);

if ($agent === false) {
	echo '<h3 class="error">'.__('There was a problem loading agent').'</h3>';
	return;
}

$is_extra = enterprise_hook('policies_is_agent_extra_policy', array($id_agente));

if ($is_extra === ENTERPRISE_NOT_HOOK) {
	$is_extra = false;
}

if (! check_acl ($config["id_user"], $agent["id_grupo"], "AR") && !$is_extra) {
	db_pandora_audit("ACL Violation", 
		"Trying to access Agent General Information");
	require_once ("general/noaccess.php");
	return;
}

// START: TABLE AGENT BUILD
$table_agent->id = 'agent_details_main';
$table_agent->width = '100%';
$table_agent->cellspacing = 4;
$table_agent->cellpadding = 4;
$table_agent->class = 'databox_frame';
$table_agent->style[1] = 'width: 100px; text-align:center;';
$table_agent->style[2] = 'width: 16px; text-align:center;';
$data = array();

$agent_name = ui_print_agent_name ($agent["id_agente"], true, 500, "font-size: medium;", true);

if ($agent['disabled']) {
	$agent_name = "<em>" . $agent_name . "</em>" . ui_print_help_tip(__('Disabled'), true);
}
else if ($agent['quiet']) {
	$agent_name = "<em>" . $agent_name . "&nbsp;" . html_print_image("images/dot_green.disabled.png", true, array("border" => '0', "title" => __('Quiet'), "alt" => "")) . "</em>";
}
else {
	$agent_name = $agent_name;
}

$data[0] = ui_print_group_icon ($agent["id_grupo"], true) . '&nbsp;';
$data[0] .= $agent_name;

$status_img = agents_detail_view_status_img ($agent["critical_count"],
	$agent["warning_count"], $agent["unknown_count"]);
$data[1] = reporting_tiny_stats ($agent, true);
$data[2] = $status_img;

$table_agent->data[] = $data;
$table_agent->rowclass[] = '';
$table_agent->cellstyle[][0] = '';


$data = array();

$data[0] = ui_print_os_icon ($agent["id_os"], true, true, true, false, false, false, array('style' => 'margin:0px 5px 0px 4px;')) . '&nbsp;';
$data[0] .= ui_print_help_tip($agent["os_version"], true);
$table_agent->cellstyle[][0] = 'padding-left:50px;';

$data[1] = graph_agent_status ($id_agente, 160, 120, true);
$table_agent->rowspan[1][1] = 6;

$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

$addresses = agents_get_addresses($id_agente);
$address = agents_get_address($id_agente);
foreach ($addresses as $k => $add) {
	if($add == $address) {
		unset($addresses[$k]);
	}
}

if(!empty($address)) {
	$data = array();
	$data[0] = html_print_image('images/world.png', true, array('title' => __('IP address'))) . '&nbsp;&nbsp;';
	$data[0] .= '<span style="vertical-align:top; padding-top: 6px; display: inline-block;">';
	$data[0] .= empty($address) ? '<em>' . __('N/A') . '</em>' : $address;
	$data[0] .= '</div>';
	$table_agent->cellstyle[][0] = 'padding-left:50px;';
	$table_agent->data[] = $data;
	$table_agent->rowclass[] = '';
}

$data = array();
$data[0] = html_print_image('images/version.png', true, array('title' => __('Agent Version'))) . '&nbsp;&nbsp;';
$data[0] .= '<span style="vertical-align:top; padding-top: 6px; display: inline-block;">';
$data[0] .= $agent["agent_version"];
$data[0] .= '</span>';
$table_agent->cellstyle[][0] = 'padding-left:50px;';
$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

$data = array();
$data[0] = html_print_image('images/default_list.png', true, array('title' => __('Description'))) . '&nbsp;&nbsp;';
$data[0] .= '<span style="vertical-align:top; padding-top: 6px; display: inline-block;">';
$data[0] .= empty($agent["comentarios"]) ? '<em>' . __('N/A') . '</em>' : $agent["comentarios"];
$data[0] .= '</span>';
$table_agent->cellstyle[][0] = 'padding-left:50px;';
$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

// END: TABLE AGENT BUILD

// START: TABLE CONTACT BUILD
$table_contact->id = 'agent_contact_main';
$table_contact->width = '100%';
$table_contact->cellspacing = 4;
$table_contact->cellpadding = 4;
$table_contact->class = 'databox';
$table_contact->style[0] = 'width: 30%;';
$table_contact->style[1] = 'width: 70%;';

$table_contact->head[0] = ' <span>' . __('Agent contact') . '</span>';
$table_contact->head_colspan[0] = 2;

$data = array();
$data[0] = '<b>' . __('Interval') . '</b>';
$data[1] = human_time_description_raw ($agent["intervalo"]);
$table_contact->data[] = $data;

$data = array();
$data[0] = '<b>' . __('Last contact') . ' / ' . __('Remote') . '</b>';
$data[1] = ui_print_timestamp ($agent["ultimo_contacto"], true);
$data[1] .=  " / ";

if ($agent["ultimo_contacto_remoto"] == "01-01-1970 00:00:00") { 
	$data[1] .= __('Never');
}
else {
	$data[1] .= $agent["ultimo_contacto_remoto"];
}

$table_contact->data[] = $data;

$data[0] = '<b>' . __('Next contact') . '</b>';
$progress = agents_get_next_contact($id_agente);
$data[1] = progress_bar($progress, 200, 20);
$table_contact->data[] = $data;

// END: TABLE CONTACT BUILD

// START: TABLE DATA BUILD
$table_data->id = 'agent_data_main';
$table_data->width = '100%';
$table_data->cellspacing = 4;
$table_data->cellpadding = 4;
$table_data->class = 'databox';
$table_data->style[0] = 'width: 30%;';
$table_data->style[1] = 'width: 70%;';

$table_data->head[0] = ' <span>' . __('Agent info') . '</span>';
$table_data->head_colspan[0] = 2;

if (!empty($addresses)) {
	$data = array();
	$data[0] = '<b>' . __('Other IP addresses') . '</b>';
	$data[1] = implode('<br>',$addresses);
	$table_data->data[] = $data;
}

$data = array();
$data[0] = '<b>' . __('Parent') . '</b>';
if ($agent["id_parent"] == 0) {
	$data[1] = '<em>' . __('N/A') . '</em>'; 
}
else {
	$data[1] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$agent["id_parent"].'">'.agents_get_name ($agent["id_parent"]).'</a>';
}

$table_data->data[] = $data;

$has_remote_conf = enterprise_hook('config_agents_has_remote_configuration',array($agent["id_agente"]));

if(enterprise_installed()) {
	$data = array();
	$data[0] = '<b>' . __('Remote configuration') . '</b>';
	if (!$has_remote_conf) {
		$data[1] = __('Disabled'); 
	}
	else {
		$data[1] = __('Enabled');
	}

	$table_data->data[] = $data;
}

if ($config['activate_gis'] || $agent['url_address'] != '') {
	$data = array();
	// Position Information
	if ($config['activate_gis']) {
		$dataPositionAgent = gis_get_data_last_position_agent($agent['id_agente']);
		
		$data[0] = '<b>' . __('Position (Long, Lat)') . '</b>';
		
		if ($dataPositionAgent === false) {
			$data[1] = __('There is no GIS data.');
		}
		else {
			$data[$col] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente='.$id_agente.'">';
			if ($dataPositionAgent['description'] != "")
				$data[1] .= $dataPositionAgent['description'];
			else
				$data[1] .= $dataPositionAgent['stored_longitude'].', '.$dataPositionAgent['stored_latitude'];
			$data[1] .= "</a>";
		}
		$table_data->data[] = $data;
	}
	
	// If the url description is setted
	if ($agent['url_address'] != '') {
		$data = array();
		$data[0] = '<b>' . __('Url address') . '</b>';
		$data[1] = '<a href='.$agent["url_address"].'>' . $agent["url_address"] . '</a>';
		$table_data->data[] = $data;
	}
}

// Timezone Offset
if ($agent['timezone_offset'] != 0) {
	$data = array();
	$data[0] = '<b>' . __('Timezone Offset') . '</b>';
	$data[1] = $agent["timezone_offset"];
	$table->data[] = $data;
}

// Custom fields
$fields = db_get_all_rows_filter('tagent_custom_fields', array('display_on_front' => 1));
if ($fields === false) {
	$fields = array ();
}

foreach ($fields as $field) {
	$data = array();
	$data[0] = '<b>' . $field['name'] . ui_print_help_tip (__('Custom field'), true) . '</b>';
	$custom_value = db_get_value_filter('description', 'tagent_custom_data', array('id_field' => $field['id_field'], 'id_agent' => $id_agente));
	if ($custom_value === false || $custom_value == '') {
		$custom_value = '<i>'.__('N/A').'</i>';
	}
	$data[1] = $custom_value;
	$table_data->data[] = $data;
}

// END: TABLE DATA BUILD

$table = null;
$table->id = 'agent_details';
$table->width = '99%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox';
$table->style = array_fill(0, 3, 'vertical-align: top;');

$data = array();
$data[0] = html_print_table($table_agent, true);
$data[1] = html_print_table($table_contact, true) . '<br>';
$data[1] .= empty($table_data->data) ? '' : html_print_table($table_data, true);

$table->rowspan[0][1] = 2;

$data[2] = '<div style="width:100%; text-align:right">';
$data[2] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;refr=60">' . html_print_image("images/refresh.png", true, array("border" => '0', "title" => __('Refresh data'), "alt" => "")) . '</a><br>';
$data[2] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;flag_agent=1&amp;id_agente='.$id_agente.'">' . html_print_image("images/target.png", true, array("border" => '0', "title" => __('Force'), "alt" => "")) . '</a>';
$data[2] .= '</div>';

$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$data[0] = '<fieldset class="databox" style="width:96%; position: static;">
				<legend style="text-align:left; color: #666;">' . 
					__('Events (24h)') . 
				'</legend>' . 
				'<div style="margin: auto; text-align:center; width: 300px;">' .
				graph_graphic_agentevents ($id_agente, 300, 15, 86400, '', true) . 
				'</div>' . 
				'</fieldset>';

// ACCESS RATE GRAPH
if ($config["agentaccess"]) {
	$data[0] .= '<fieldset class="databox" style="width:96%; position: static;">
					<legend style="text-align:left; color: #666;">' . 
						__('Agent access rate (24h)') . 
					'</legend>' . 
					'<div style="margin: auto; text-align:center; width: 300px;">' .
					graphic_agentaccess($id_agente, 300, 100, 86400, true) . 
					'</div>' . 
					'</fieldset>';
}

$table->cellstyle[1][0] = 'text-align:center;';

$table->data[] = $data;
$table->rowclass[] = '';

html_print_table($table);
unset($table);
?>
