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
$table_agent->width = '500px';
$table_agent->cellspacing = 4;
$table_agent->cellpadding = 4;
$table_agent->class = 'databox_frame';
$table_agent->style[1] = 'width: 100px;';
$table_agent->style[2] = 'width: 16px;';
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

$data[0] = ui_print_group_icon ($agent["id_grupo"], true) . '&nbsp;&nbsp;';
$data[0] .= $agent_name;

$status_img = agents_tree_view_status_img ($agent["critical_count"],
	$agent["warning_count"], $agent["unknown_count"]);
$data[1] = events_tiny_stats ($agent['total_count'], $agent['normal_count'], $agent['critical_count'], $agent['warning_count'], $agent['unknown_count'], true);
$data[2] = str_replace('.png' ,'_ball.png', $status_img);

$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

$data = array();
$addresses = agents_get_addresses($id_agente);
$address = agents_get_address($id_agente);
foreach ($addresses as $k => $add) {
	if($add == $address) {
		unset($addresses[$k]);
	}
}
$data[0] = html_print_image('images/world.png', true, array('title' => __('IP address'))) . '&nbsp;&nbsp;';
$data[0] .= empty($address) ? '<em>' . __('N/A') . '</em>' : $address;
if (!empty($addresses)) {
	$data[0] .= ui_print_help_tip(__('Other IP addresses').': <br>'.implode('<br>',$addresses), true);
}
$table_agent->cellstyle[1][0] = 'padding-left:50px;';

$data[1] = graph_agent_status ($id_agente, 120, 120, true);
$table_agent->rowspan[1][1] = 5;
$table_agent->colspan[1][1] = 2;

$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

$data = array();
$data[0] = html_print_image('images/default_list.png', true, array('title' => __('Description'))) . '&nbsp;&nbsp;';
$data[0] .= empty($agent["comentarios"]) ? '<em>' . __('N/A') . '</em>' : $agent["comentarios"];
$table_agent->cellstyle[][0] = 'padding-left:50px;';
$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

$data = array();
$data[0] = html_print_image('images/clock.png', true, array('title' => __('Interval'))) . '&nbsp;&nbsp;';
$data[0] .= human_time_description_raw ($agent["intervalo"]);
$table_agent->cellstyle[][0] = 'padding-left:50px;';
$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

$data[0] = html_print_image('images/go_next.png', true, array('title' => __('Next contact'))) . '&nbsp;&nbsp;';
$progress = agents_get_next_contact($id_agente);
$data[0] .= progress_bar($progress, 200, 20);
$table_agent->cellstyle[][0] = 'padding-left:50px;';
$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

// END: TABLE AGENT BUILD

$table->id = 'agent_details';
$table->width = '100%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox';

$data = array();
$data[0] = html_print_table($table_agent, true);

$table->colspan[0][0] = 2;
$table->rowspan[0][0] = 5;

$data[2] = '<b>' . __('Parent') . '</b>';
$data[3] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$agent["id_parent"].'">'.agents_get_name ($agent["id_parent"]).'</a>';

$data[4] = '<div style="width:100%; text-align:right">';
$data[4] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;refr=60">' . html_print_image("images/refresh.png", true, array("border" => '0', "title" => __('Refresh data'), "alt" => "")) . '</a>&nbsp;';
$data[4] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;flag_agent=1&amp;id_agente='.$id_agente.'">' . html_print_image("images/target.png", true, array("border" => '0', "title" => __('Force'), "alt" => "")) . '</a>';
$data[4] .= '</div>';

$table->data[] = $data;
$table->rowclass[] = '';

$data = array();

$data[2] = '<b>' . __('OS') . '</b>';
$data[3] = ui_print_os_icon ($agent["id_os"], true, true);

// Want to print last 15 characters of OS version, or start from 0 if smaller
$osversion_offset = strlen($agent["os_version"]);
if ($osversion_offset > 15)
	$osversion_offset = $osversion_offset - 15;
else
	$osversion_offset = 0;


$data[3] .= '&nbsp;<i><span title="'.$agent["os_version"].'">'.$agent["os_version"].' </span></i>';
$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$data[2] = '<b>' . __('Agent Version') . '</b>';
$data[3] = $agent["agent_version"];
$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$data[2] = '<b>' . __('Last contact') . ' / ' . __('Remote') . '</b>';
$data[3] = ui_print_timestamp ($agent["ultimo_contacto"], true);
$data[3] .=  " / ";

if ($agent["ultimo_contacto_remoto"] == "01-01-1970 00:00:00") { 
	$data[3] .= __('Never');
}
else {
	$data[3] .= $agent["ultimo_contacto_remoto"];
}

$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$data[0] = '<b>' . __('Events (24h)') . '</b>';
$data[1] = graph_graphic_agentevents ($id_agente, 290, 15, 86400, '', true);
$table->data[] = $data;
$table->rowclass[] = '';

if ($config['activate_gis'] || $agent['url_address'] != '') {
	$data = array();
	$col = 0;
	// Position Information
	if ($config['activate_gis']) {
		$dataPositionAgent = gis_get_data_last_position_agent($agent['id_agente']);
		
		$data[$col] = '<b>' . __('Position (Long, Lat)') . '</b>';
		$col++;
		
		if ($dataPositionAgent === false) {
			$data[$col] = __('There is no GIS data.');
		}
		else {
			$data[$col] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente='.$id_agente.'">';
			if ($dataPositionAgent['description'] != "")
				$data[$col] .= $dataPositionAgent['description'];
			else
				$data[$col] .= $dataPositionAgent['stored_longitude'].', '.$dataPositionAgent['stored_latitude'];
			$data[$col] .= "</a>";
		}
		$col++;
	}
	
	// If the url description is setted
	if ($agent['url_address'] != '') {
		$data[$col] = '<b>' . __('Url address') . '</b>';
		$col++;
		$data[$col] = '<a href='.$agent["url_address"].'>' . $agent["url_address"] . '</a>';
	}
	
	$table->data[] = $data;
	$table->rowclass[] = '';
}

// ACCESS RATE GRAPH
if ($config["agentaccess"]) {
	$data = array();
	$data[0] = '<b>' . __('Agent access rate (24h)') . '</b>';
	$data[1] = graphic_agentaccess($id_agente, 300, 110, 86400, true);
	$table->data[] = $data;
	$table->rowclass[] = '';
}

// Timezone Offset
if ($agent['timezone_offset'] != 0) {
	$data = array();
	$data[0] = '<b>' . __('Timezone Offset') . '</b>';
	$data[1] = $agent["timezone_offset"];
	$table->data[] = $data;
	$table->rowclass[] = '';
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
		$custom_value = '<i>-'.__('empty').'-</i>';
	}
	$data[1] = $custom_value;
	$table->data[] = $data;
	$table->rowclass[] = '';
}

html_print_table($table);
unset($table);
?>