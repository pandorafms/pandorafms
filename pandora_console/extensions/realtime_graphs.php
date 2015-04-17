<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2014 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

include_once('include/graphs/fgraph.php');
include_once('include/functions_snmp_browser.php');

function pandora_realtime_graphs () {
	global $config;
	check_login ();

	$id_network = get_parameter('id_network', 0);
	$action = get_parameter('action', 'list');

	$onheader = array();
	
	ui_print_page_header (__("Realtime graphs"), "images/extensions.png", false, "", false, $onheader);

	$chart[time()]['graph'] = '0';
	$interactive_graph = true;
	$color = array();
	$legend = '';
	$long_index = array();
	$no_data_image = '';
	
	$canvas = '<div id="graph_container">';
	$canvas .= '<div id="chartLegend"></div>';
	$canvas .= area_graph($interactive_graph, $chart, 800, 300, $color, $legend, $long_index, $no_data_image, "", "", "",
	"", '', '', '', 1, array(),	array(), 0, 0, '', false, '', false);
	$canvas .= '</div>';
	echo $canvas;
	
	$table->width = '100%';
	$table->id = 'table-form';
	$table->class = 'databox filters';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->style[1] = 'font-weight: bold;';
	$table->style[2] = 'font-weight: bold;';
	$table->data = array ();
	
	$graph_fields['cpu_load'] = __('Pandora Server CPU');
	$graph_fields['pending_packets'] = __('Pandora Server Pending packets');
	$graph_fields['disk_io_wait'] = __('Pandora Server Disk IO Wait');
	$graph_fields['apache_load'] = __('Pandora Server Apache load');
	$graph_fields['mysql_load'] = __('Pandora Server MySQL load');
	$graph_fields['server_load'] = __('Pandora Server load');
	$graph_fields['snmp_interface'] = __('SNMP Interface throughput');
	
	$graph = get_parameter('graph', 'cpu_load');
	$refresh = get_parameter('refresh', '1000');
	
	$data['graph'] = __('Graph') . '&nbsp;&nbsp;' . html_print_select ($graph_fields, 'graph', $graph, '', '', 0, true);
	$data['reset'] = html_print_button(__('Clear graph'), 'reset', false, 'clearGraph()', 'class="sub delete"', true);

	$refresh_fields[1000] = human_time_description_raw(1, true, 'large');
	$refresh_fields[5000] = human_time_description_raw(5, true, 'large');
	$refresh_fields[10000] = human_time_description_raw(10, true, 'large');
	$refresh_fields[30000] = human_time_description_raw(30, true, 'large');
	
	$data['refresh'] = __('Refresh interval') . '&nbsp;&nbsp;' . html_print_select ($refresh_fields, 'refresh', $refresh, '', '', 0, true);
	$data['incremental'] = __('Incremental') . '&nbsp;&nbsp;' . html_print_checkbox ('incremental', 1, 0, true);

	$table->data[] = $data;
	
	
	if ($graph == 'snmp_interface') {
		$snmp_address = '';
		$snmp_community = '';
		$snmp_oid = '';
		$snmp_ver = '1';
		$snmp_inc = false;
		
		$data = array();
		
		$data['snmp_address'] = __('Target IP') . '&nbsp;&nbsp;' . html_print_input_text ('ip_target', $snmp_address, '', 50, 255, true);
		$table->colspan[1]['snmp_address'] = 2;

		$data['snmp_community'] = __('Community') . '&nbsp;&nbsp;' . html_print_input_text ('snmp_community', $snmp_community, '', 50, 255, true);
		$table->colspan[1]['snmp_community'] = 2;

		$table->data[] = $data;

		$snmp_versions = array();
		$snmp_versions['1'] = '1';
		$snmp_versions['2'] = '2';
		$snmp_versions['2c'] = '2c';
		
		$data = array();
		$data['snmp_oid'] = __('OID') . '&nbsp;&nbsp;' . html_print_input_text ('snmp_oid', $snmp_oid, '', 100, 255, true);
		$table->colspan[2]['snmp_oid'] = 2;

		$data['snmp_ver'] = __('Version') . '&nbsp;&nbsp;' . html_print_select ($snmp_versions, 'snmp_version', $snmp_ver, '', '', 0, true);
		$data['snmp_ver'] .= '&nbsp;&nbsp;' . html_print_button (__('SNMP walk'), 'snmp_walk', false, 'snmpBrowserWindow()', 'class="sub next"', true);
		$table->colspan[2]['snmp_ver'] = 2;
		
		$table->data[] = $data;
		
		snmp_browser_print_container (false, '100%', '60%', 'none');
	}
	
	echo '<form id="realgraph" method="post">';
	html_print_table($table);
	echo '</form>';
	
	// Define a custom action to save the OID selected in the SNMP browser to the form
	html_print_input_hidden ('custom_action', urlencode (base64_encode('&nbsp;<a href="javascript:setOID()"><img src="' . ui_get_full_url("images") . '/hand_point.png" title="' . __("Use this OID") . '" style="vertical-align: middle;"></img></a>')), false);
	html_print_input_hidden ('incremental_base', '0');

	echo '<script type="text/javascript" src="extensions/realtime_graphs/realtime_graphs.js"></script>';
	echo '<script type="text/javascript" src="include/javascript/pandora_snmp_browser.js"></script>';
	echo '<link rel="stylesheet" type="text/css" href="extensions/realtime_graphs/realtime_graphs.css"></style>';
	
	// Store servers timezone offset to be retrieved from js
	set_js_value('timezone_offset', date('Z', time()));
}

extensions_add_operation_menu_option (__('Realtime graphs'), "estado", null, "v1r1","view");
extensions_add_main_function ('pandora_realtime_graphs');

$db = NULL;
?>
