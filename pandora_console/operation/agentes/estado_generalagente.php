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
require_once ($config['homedir'] .'/include/functions_incidents.php');

include_once($config['homedir'] . "/include/functions_clippy.php");

check_login ();


$id_agente = get_parameter_get ("id_agente", -1);

$agent = db_get_row ("tagente", "id_agente", $id_agente);

if (empty($agent['server_name'])) {
	ui_print_error_message(
		__('The agent has not assigned server. Maybe agent does not run fine.'));
}

if ($agent === false) {
	ui_print_error_message(__('There was a problem loading agent'));
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
$table_agent->style[0] = 'width: 16px; text-align:center; padding: 0px;';
$table_agent->style[5] = 'width: 16px; text-align:center; padding: 0px;';
$table_agent->styleTable = 'padding:0px;';
$table_agent->data = array();
$data = array();

$agent_name = ui_print_agent_name ($agent["id_agente"], true, 500,
	"font-size: medium;", true);

if ($agent['disabled']) {
	$agent_name = "<em>" . $agent_name . "</em>" . ui_print_help_tip(__('Disabled'), true);
}
else if ($agent['quiet']) {
	$agent_name = "<em'>" . $agent_name . "&nbsp;" . html_print_image("images/dot_green.disabled.png", true, array("border" => '0', "title" => __('Quiet'), "alt" => "")) . "</em>";
}
else {
	$agent_name = $agent_name;
}

$data[0] = ui_print_group_icon ($agent["id_grupo"], true);
$table_agent->cellstyle[count($table_agent->data)][0] =
	'width: 16px; text-align:center; padding: 0px;';

$data[2] = $agent_name;
$table_agent->colspan[count($table_agent->data)][2] = 3;

$table_agent->cellstyle[count($table_agent->data)][2] =
	'width: 100px; word-break: break-all;';


$status_img = agents_detail_view_status_img ($agent["critical_count"],
	$agent["warning_count"], $agent["unknown_count"], $agent["total_count"], 
	$agent["notinit_count"]);
$data[5] = $status_img;

$table_agent->data[] = $data;
$table_agent->rowclass[] = '';


$data = array();

//$data[0] = reporting_tiny_stats ($agent, true, 'agent', '<div style="height: 5px;"></div>');
//$table_agent->rowspan[count($table_agent->data)][0] = 6;

$data[0] = '<div style="margin: 0 auto 6px auto; width: 150px;">';
$data[0] .= graph_agent_status ($id_agente, 150, 120, true);
$data[0] .= '<br>' . reporting_tiny_stats ($agent, true);
$data[0] .= ui_print_help_tip(__('Agent statuses are re-calculated by the server, they are not  shown in real time.'), true);
$data[0] .= '</div>';
$table_agent->rowspan[count($table_agent->data)][0] = 6;
$table_agent->colspan[count($table_agent->data)][0] = 2;
$table_agent->cellstyle[count($table_agent->data)][0] =
	'width: 150px; text-align:center; padding: 0px; vertical-align: top;';


$data[2] = ui_print_os_icon ($agent["id_os"], false, true, true, false, false, false, array('title' => __('OS') . ': ' . get_os_name ($agent["id_os"])));
$table_agent->cellstyle[count($table_agent->data)][2] =
	'width: 16px; text-align: right; padding: 0px; vertical-align: top;';
$data[3] = empty($agent["os_version"]) ? get_os_name ((int) $agent["id_os"]) : $agent["os_version"];
$table_agent->colspan[count($table_agent->data)][3] = 2;

$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

$addresses = agents_get_addresses($id_agente);
$address = agents_get_address($id_agente);

foreach ($addresses as $k => $add) {
	if ($add == $address) {
		unset($addresses[$k]);
	}
}

if (!empty($address)) {
	$data = array();
	$data[2] = html_print_image('images/world.png', true, array('title' => __('IP address')));
	$table_agent->cellstyle[count($table_agent->data)][2] =
		'width: 16px; text-align: right; padding: 0px; vertical-align: top;';
	$data[3] = '<span style="vertical-align:top; display: inline-block;">';
	$data[3] .= empty($address) ? '<em>' . __('N/A') . '</em>' : $address;
	$data[3] .= '</span>';
	$table_agent->colspan[count($table_agent->data)][3] = 2;
	$table_agent->data[] = $data;
	$table_agent->rowclass[] = '';
}

$data = array();
$data[2] = html_print_image('images/version.png', true, array('title' => __('Agent Version')));
$table_agent->cellstyle[count($table_agent->data)][2] =
	'width: 16px; text-align: right; padding: 0px; vertical-align: top;';
$data[3] = '<span style="vertical-align:top; display: inline-block;">';
$data[3] .= empty($agent["agent_version"]) ? '<i>' . __('N/A') . '</i>' : $agent["agent_version"];
$data[3] .= '</span>';
$table_agent->colspan[count($table_agent->data)][3] = 2;
$table_agent->data[] = $data;
$table_agent->rowclass[] = '';

$data = array();
$data[2] = html_print_image('images/default_list.png', true,
	array('title' => __('Description')));
$table_agent->cellstyle[count($table_agent->data)][2] =
	'width: 16px; text-align: right; padding: 0px; vertical-align: top;';
$data[3] = '<span style="vertical-align:top; display: inline-block;">';
$data[3] .= empty($agent["comentarios"]) ?
	'<em>' . __('N/A') . '</em>' :
	ui_bbcode_to_html($agent["comentarios"]);
$data[3] .= '</span>';
$table_agent->colspan[count($table_agent->data)][3] = 2;

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
$data[1] = progress_bar($progress, 200, 20, '', 1, false, "#666666");

if ($progress > 100) {
	$data[1] .= clippy_context_help("agent_out_of_limits");
}

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

$data = array();
$data[0] = '<b>' . __('Group') . '</b>';
$data[1] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$agent["id_grupo"].'">'.groups_get_name ($agent["id_grupo"]).'</a>';

$table_data->data[] = $data;

if (!empty($addresses)) {
	$data = array();
	$data[0] = '<b>' . __('Other IP addresses') . '</b>';
	$data[1] = '<div style="max-height: 150px; overflow-y: auto;">' .
		implode('<br>',$addresses) .
		'</div>';
	//~ $table_data->data[] = '<div style="max-height: 200px; overflow: hidden;>' .
		//~ $data . '</div>';
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

if (enterprise_installed()) {
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
		$dataPositionAgent =
			gis_get_data_last_position_agent($agent['id_agente']);
		
		$data[0] = '<b>' . __('Position (Long, Lat)') . '</b>';
		
		if ($dataPositionAgent === false) {
			$data[1] = __('There is no GIS data.');
		}
		else {
			$data[1] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente='.$id_agente.'">';
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
$fields = db_get_all_rows_filter(
	'tagent_custom_fields',
	array('display_on_front' => 1));
if ($fields === false) {
	$fields = array ();
}

foreach ($fields as $field) {
	$data = array();
	$data[0] = '<b>' . $field['name'] .
		ui_print_help_tip (__('Custom field'), true) . '</b>';
	$custom_value = db_get_value_filter(
		'description', 'tagent_custom_data',
		array('id_field' => $field['id_field'], 'id_agent' => $id_agente));
	if ($custom_value === false || $custom_value == '') {
		$custom_value = '<i>'.__('N/A').'</i>';
	}
	$data[1] = $custom_value;
	$table_data->data[] = $data;
}

// END: TABLE DATA BUILD

// START: TABLE INCIDENTS

$last_incident = db_get_row_sql("
	SELECT * FROM tincidencia
	WHERE estado IN (0,1)
		AND id_agent = $id_agente
	ORDER BY actualizacion DESC");

if ($last_incident != false) {
	
	$table_incident->id = 'agent_incident_main';
	$table_incident->width = '100%';
	$table_incident->cellspacing = 4;
	$table_incident->cellpadding = 4;
	$table_incident->class = 'databox';
	$table_incident->style[0] = 'width: 30%;';
	$table_incident->style[1] = 'width: 70%;';
	
	$table_incident->head[0] = ' <span>' . '<a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident_detail&amp;id='.$last_incident["id_incidencia"].'">' .__('Active incident on this agent') .'</a>'. '</span>';
	$table_incident->head_colspan[0] = 2;
	
	$data = array();
	$data[0] = '<b>' . __('Author') . '</b>';
	$data[1] = $last_incident["id_creator"];
	$table_incident->data[] = $data;
	
	$data = array();
	$data[0] = '<b>' . __('Title') . '</b>';
	$data[1] = '<a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident_detail&amp;id='.$last_incident["id_incidencia"].'">' .$last_incident["titulo"].'</a>';
	$table_incident->data[] = $data;
	
	$data = array();
	$data[0] = '<b>' . __('Timestamp') . '</b>';
	$data[1] = $last_incident["inicio"];
	$table_incident->data[] = $data;
	
	$data = array();
	$data[0] = '<b>' . __('Priority') . '</b>';
	$data[1] = incidents_print_priority_img ($last_incident["prioridad"], true);
	$table_incident->data[] = $data;
	
}
// END: TABLE INCIDENTS

// START: TABLE INTERFACES

$network_interfaces_by_agents = agents_get_network_interfaces(array($agent));

$network_interfaces = array();
if (!empty($network_interfaces_by_agents) && !empty($network_interfaces_by_agents[$id_agente])) {
	$network_interfaces = $network_interfaces_by_agents[$id_agente]['interfaces'];
}

if (!empty($network_interfaces)) {
	$table_interface = new stdClass();
	$table_interface->id = 'agent_interface_info';
	$table_interface->class = 'databox';
	$table_interface->width = '100%';
	$table_interface->style = array();
	$table_interface->style['interface_status'] = 'width: 30px;';
	$table_interface->style['interface_graph'] = 'width: 20px;';
	$table_interface->head = array();
	$options = array(
		"class" => "closed",
		"style" => "vertical-align:middle; cursor:pointer;");
	$table_interface->head[0] = html_print_image("images/go.png", true, $options) . "&nbsp;&nbsp;";
	$table_interface->head[0] .= '<span style="vertical-align: middle;">' . __('Interface information') .' (SNMP)</span>';
	$table_interface->head_colspan = array();
	$table_interface->head_colspan[0] = 5;
	$table_interface->data = array();
	
	foreach ($network_interfaces as $interface_name => $interface) {
		if (!empty($interface['traffic'])) {
			$params = array(
					'interface_name' => $interface_name,
					'agent_id' => $id_agente,
					'traffic_module_in' => $interface['traffic']['in'],
					'traffic_module_out' => $interface['traffic']['out']
				);
			$params_json = json_encode($params);
			$params_encoded = base64_encode($params_json);
			$win_handle = dechex(crc32($interface['status_module_id'].$interface_name));
			$graph_link = "<a href=\"javascript:winopeng('operation/agentes/interface_traffic_graph_win.php?params=$params_encoded','$win_handle')\">" .
				html_print_image("images/chart_curve.png", true, array("title" => __('Interface traffic'))) . "</a>";
		}
		else {
			$graph_link = "";
		}
		
		$data = array();
		$data['interface_name'] = "<strong>" . $interface_name . "</strong>";
		$data['interface_status'] = $interface['status_image'];
		$data['interface_graph'] = $graph_link;
		$data['interface_ip'] = $interface['ip'];
		$data['interface_mac'] = $interface['mac'];
		$table_interface->data[] = $data;
	}
	// This javascript piece of code is used to make expandible the body of the table
?>
	<script type="text/javascript">
		$(document).ready (function () {
			$("#agent_interface_info").find("tbody").hide();
			$("#agent_interface_info").find("thead").click (function () {
					var arrow = $("#agent_interface_info").find("thead").find("img");
					if (arrow.hasClass("closed")) {
						arrow.removeClass("closed");
						arrow.prop("src", "images/down.png");
						$("#agent_interface_info").find("tbody").show();
					} else {
						arrow.addClass("closed");
						arrow.prop("src", "images/go.png");
						$("#agent_interface_info").find("tbody").hide();
					}
				})
				.css('cursor', 'pointer');
		});
	</script>
<?php
}

// END: TABLE INTERFACES

$table = null;
$table->id = 'agent_details';
$table->width = '98%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox';
$table->style = array_fill(0, 3, 'vertical-align: top;');

$data = array();
$data[0] = html_print_table($table_agent, true);
$data[0] .= '<fieldset class="databox" style="position: static;">
				<legend style="text-align:left; color: #666;">' . 
					__('Events (24h)') . 
				'</legend>' . 
				'<div style="margin: auto; text-align:center; width: 300px;">' .
				graph_graphic_agentevents ($id_agente, 300, 15, 86400, '', true) . 
				'</div>' . 
			'</fieldset>';

// ACCESS RATE GRAPH
$access_agent = db_get_value_sql("SELECT COUNT(id_agent)
	FROM tagent_access
	WHERE id_agent = " . $id_agente);

if ($config["agentaccess"] && $access_agent > 0) {
	$data[0] .= '<fieldset class="databox" style="position: static;">
					<legend style="text-align:left; color: #666;">' . 
						__('Agent access rate (24h)') . 
					'</legend>' . 
					'<div style="margin: auto; text-align:center; width: 300px;">' .
					graphic_agentaccess($id_agente, 300, 100, 86400, true) . 
					'</div>' . 
				'</fieldset>';
}

$data[1] = html_print_table($table_contact, true);
$data[1] .= empty($table_data->data) ?
	'' :
	'<br>' . html_print_table($table_data, true);
$data[1] .= !isset($table_incident) ?
	'' :
	'<br>' . html_print_table($table_incident, true);
$data[1] .= !isset($table_interface) ?
	'' :
	'<br>' . html_print_table($table_interface, true);

$table->rowspan[0][1] = 2;

$data[2] = '<div style="width:100%; text-align:right">';
$data[2] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;refr=60">' . html_print_image("images/refresh.png", true, array("border" => '0', "title" => __('Refresh data'), "alt" => "")) . '</a><br>';
$data[2] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;flag_agent=1&amp;id_agente='.$id_agente.'">' . html_print_image("images/target.png", true, array("border" => '0', "title" => __('Force'), "alt" => "")) . '</a>';
$data[2] .= '</div>';

$table->data[] = $data;
$table->rowclass[] = '';

$table->cellstyle[1][0] = 'text-align:center;';

html_print_table($table);
unset($table);
?>
