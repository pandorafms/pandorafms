<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

include_once($config['homedir'] . "/include/functions_graph.php");
include_once($config['homedir'] . "/include/functions_ui.php");
include_once($config['homedir'] . "/include/functions_netflow.php");

ui_require_javascript_file ('calendar');

// ACL
check_login ();
if (! check_acl ($config["id_user"], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$pure = get_parameter('pure', 0);

// Ajax callbacks
if (is_ajax()) {
	$get_filter_type = get_parameter('get_filter_type', 0);
	$get_filter_values = get_parameter('get_filter_values', 0);
	
	// Get filter of the current netflow filter
	if ($get_filter_type) {
		$id = get_parameter('id');
		
		$advanced_filter = db_get_value_filter('advanced_filter', 'tnetflow_filter', array('id_sg' => $id));
		
		if (empty($advanced_filter))
			$type = 0;
		else
			$type = 1;
		
		echo $type;
	}
	
	// Get values of the current netflow filter
	if ($get_filter_values) {
		$id = get_parameter('id');
		
		$filter_values = db_get_row_filter ('tnetflow_filter', array('id_sg' => $id));
		
		// Decode HTML entities
		$filter_values['advanced_filter'] = io_safe_output ($filter_values['advanced_filter']);
		
		
		echo json_encode($filter_values);
	}
	
	return;
}

// Read filter configuration
$filter_id = (int) get_parameter ('filter_id', 0);
$filter['id_name'] = get_parameter ('name', '');
$filter['id_group'] = (int) get_parameter ('assign_group', 0);
$filter['aggregate'] = get_parameter('aggregate','');
$filter['output'] = get_parameter('output','bytes');
$filter['ip_dst'] = get_parameter('ip_dst','');
$filter['ip_src'] = get_parameter('ip_src','');
$filter['dst_port'] = get_parameter('dst_port','');
$filter['src_port'] = get_parameter('src_port','');
$filter['advanced_filter'] = get_parameter('advanced_filter','');
$filter['advanced_filter'] = get_parameter('advanced_filter','');

// Read chart configuration
$chart_type = (int) get_parameter('chart_type', 0);
$max_aggregates = (int) get_parameter('max_aggregates', 0);
$period = (int) get_parameter('period', '86400');
$update_date = (int) get_parameter('update_date', 0);
$date = get_parameter_post ('date', date ("Y/m/d", get_system_time ()));
$time = get_parameter_post ('time', date ("H:i:s", get_system_time ()));
$connection_name = get_parameter('connection_name', '');
$interval_length = (int) get_parameter('interval_length', 300);

// Read buttons
$draw = get_parameter('draw_button', '');
$save = get_parameter('save_button', '');
$update = get_parameter('update_button', '');


// Calculate start and end dates
$end_date = strtotime ($date . " " . $time);
$start_date = $end_date - $period;

if (! defined ('METACONSOLE')) {
	//Header
	ui_print_page_header (__('Netflow live view'), "images/networkmap/so_cisco_new.png", false, "", false, array ());
	if (! is_executable ($config['netflow_nfdump'])) {
		ui_print_error_message(__('nfdump binary not found!'));
	}
}
else {
	$nav_bar = array(array('link' => 'index.php?sec=main', 'text' => __('Main')),
		array('link' => 'index.php?sec=netf&sec2=operation/netflow/nf_live_view', 'text' => __('Netflow live view')));
	
	ui_meta_print_page_header($nav_bar);
	
	ui_meta_print_header(__("Netflow live view"));
}

// Save user defined filter
if ($save != '' && check_acl ($config["id_user"], 0, "AW")) {
	
	// Save filter args
	$filter['filter_args'] = netflow_get_filter_arguments ($filter);
	
	$filter_id = db_process_sql_insert ('tnetflow_filter', $filter);
	if ($filter_id === false) {
		$filter_id = 0;
		echo '<h3 class="error">'.__ ('Error creating filter').'</h3>';
	}
	else {
		echo '<h3 class="suc">'.__ ('Filter created successfully').'</h3>';
	}
}
// Update current filter
else if ($update != '' && check_acl ($config["id_user"], 0, "AW")) {
	// Do not update the filter name and group
	$filter_copy = $filter;
	unset ($filter_copy['id_name']);
	unset ($filter_copy['id_group']);
	
	// Save filter args
	$filter_copy['filter_args'] = netflow_get_filter_arguments ($filter_copy);
	
	$result = db_process_sql_update ('tnetflow_filter', $filter_copy, array ('id_sg' => $filter_id));
	ui_print_result_message ($result, __('Filter updated successfully'), __('Error updating filter'));
} 


// The filter name will not be needed anymore
$filter['id_name'] = '';

echo '<form method="post" action="' . $config['homeurl'] . 'index.php?sec=netf&sec2=operation/netflow/nf_live_view&pure='.$pure.'">';
	
	if (defined ('METACONSOLE')) {
		echo "<table class='databox' width='800'>";
	}
	else {
		echo "<table class='databox' width='90%'>";
	}
	echo "<tr>";
	
	echo "<td>" .
		'<b>'.__('Date').'</b>' .
		"</td>";
	echo "<td>" .
		html_print_input_text ('date', $date, false, 10, 10, true) .
		html_print_image ("images/calendar_view_day.png", true,
			array("alt" => "calendar",
				"onclick" => "scwShow(scwID('text-date'),this);")) .
		html_print_input_text ('time', $time, false, 10, 5, true) .
		"</td>";
	
	echo "<td>" . '<b>'.__('Interval').'</b>' . "</td>";
	echo "<td>" . html_print_select (netflow_get_valid_intervals (), 'period', $period, '', '', 0, true, false, false) . "</td>";
	
	echo "<td>" . '<b>'.__('Resolution') . ui_print_help_tip (__("The interval will be divided in chunks the length of the resolution."), true) . '</b>' . "</td>";
	echo "<td>" . html_print_select (netflow_get_valid_subintervals (), 'interval_length', $interval_length, '', '', 0, true, false, false) ."</td>";
	
	echo "</tr>";
	echo "<tr>";
	
	echo "<td>" . '<b>'.__('Type').'</b>' . "</td>";
	echo "<td>" . html_print_select (netflow_get_chart_types (), 'chart_type', $chart_type,'','',0,true) . "</td>";
	
	echo "<td>" . '<b>'.__('Max. values').'</b>' . "</td>";
	$max_values = array ('2' => '2',
		'5' => '5',
		'10' => '10',
		'15' => '15',
		'20' => '20',
		'25' => '25',
		'50' => '50');
	echo "<td>" . html_print_select ($max_values, 'max_aggregates', $max_aggregates, '', '', 0, true) . "</td>";
	
	if (defined ('METACONSOLE')) {
		$list_servers = array();
		
		$servers = db_get_all_rows_sql ("SELECT *
			FROM tmetaconsole_setup");
		if ($servers === false)
			$servers = array();
		foreach ($servers as $server) {
			// If connection was good then retrieve all data server
			if (metaconsole_load_external_db ($server)) {
				$connection = true;
			}
			else {
				$connection = false;
			}
			
			$row = db_get_row('tconfig', 'token', 'activate_netflow');
			
			
			if ($row['value']) {
				$list_servers[$server['server_name']] = $server['server_name'];
			}
			
			metaconsole_restore_db();
		}
		
		
		
		echo "<td>" . '<b>'.__('Connection').'</b>' . "</td>";
		echo "<td>" . html_print_select($list_servers, 'connection_name', $connection_name, '', '', 0, true, false, false) . "</td>";
	}
	
	echo "</tr>";
	
	// Read filter type
	if ($filter['advanced_filter'] != '') {
		$filter_type = 1;
	}
	else {
		$filter_type = 0;
	}
	
	echo "<tr class='filter_save' style='display: none;'>";
	
	echo "<td colspan='6'>" .
		ui_print_error_message ('Define a name for the filter and click on Save as new filter again', '', true) . "</td>";
		
	echo "</tr>";
	echo "<tr class='filter_save' style='display: none;'>";
	
	echo "<td>" . '<span id="filter_name_color"><b>'.__('Name').'</b></span>' . "</td>";
	echo "<td colspan='2'>" . html_print_input_text ('name', $filter['id_name'], false, 20, 80, true) . "</td>";
	$own_info = get_user_info ($config['id_user']);
	echo "<td>" . '<span id="filter_group_color"><b>'.__('Group').'</b></span>' . "</td>";
	echo "<td colspan='2'>" . html_print_select_groups($config['id_user'], "IW", $own_info['is_admin'], 'assign_group', $filter['id_group'], '', '', -1, true, false, false) . "</td>";
	
	echo "</tr>";
	echo "<tr>";
	
	echo "<td>" . '<b>'.__('Filter').'</b>' . "</td>";
	echo "<td>" .
		__('Normal') . ' ' . html_print_radio_button_extended ('filter_type', 0, '', $filter_type, false, 'displayNormalFilter();', 'style="margin-right: 40px;"', true) .
		__('Advanced') . ' ' . html_print_radio_button_extended ('filter_type', 1, '', $filter_type, false, 'displayAdvancedFilter();', 'style="margin-right: 40px;"', true) .
		"</td>";
	
	echo "<td>" . '<b>'.__('Load filter').'</b>' . "</td>";
	$user_groups = users_get_groups ($config['id_user'], "AR", $own_info['is_admin'], true);
	$sql = "SELECT * FROM tnetflow_filter WHERE id_group IN (".implode(',', array_keys ($user_groups)).")";
	echo "<td colspan='3'>" . html_print_select_from_sql ($sql, 'filter_id', $filter_id, '', __('none'), 0, true) . "</td>";
	
	echo "</tr>";
	
	echo "<tr class='filter_normal'>";
	
	echo "<td>" . __('Dst Ip'). ui_print_help_tip (__("Destination IP. A comma separated list of destination ip. If we leave the field blank, will show all ip. Example filter by ip:<br>25.46.157.214,160.253.135.249"), true) . "</td>";
	echo "<td colspan='2'>" . html_print_input_text ('ip_dst', $filter['ip_dst'], false, 30, 80, true) . "</td>";
	
	echo "<td>" . __('Src Ip'). ui_print_help_tip (__("Source IP. A comma separated list of source ip. If we leave the field blank, will show all ip. Example filter by ip:<br>25.46.157.214,160.253.135.249"), true) . "</td>";
	echo "<td colspan='2'>" . html_print_input_text ('ip_src', $filter['ip_src'], false, 30, 80, true) . "</td>";
	
	echo "</tr>";
	echo "<tr class='filter_normal'>";
	
	echo "<td>" . __('Dst Port'). ui_print_help_tip (__("Destination port. A comma separated list of destination ports. If we leave the field blank, will show all ports. Example filter by ports 80 and 22:<br>80,22"), true) . "</td>";
	echo "<td colspan='2'>" . html_print_input_text ('dst_port', $filter['dst_port'], false, 30, 80, true) . "</td>";
	 
	echo "<td>" . __('Src Port'). ui_print_help_tip (__("Source port. A comma separated list of source ports. If we leave the field blank, will show all ports. Example filter by ports 80 and 22:<br>80,22"), true) . "</td>";
	echo "<td colspan='2'>" . html_print_input_text ('src_port', $filter['src_port'], false, 30, 80, true) . "</td>";
	
	echo "</tr>";
	echo "<tr class='filter_advance' style='display: none;'>";
	
	echo "<td>" . ui_print_help_icon ('pcap_filter', true, ui_get_full_url(false, false, false, false)) . "</td>";
	echo "<td colspan='5'>" . html_print_textarea ('advanced_filter', 4, 40, $filter['advanced_filter'], "style='min-height: 0px; width: 90%;'", true) . "</td>";
	
	echo "</tr>";
	echo "<tr>";
	
	echo "<td>" . '<b>'.__('Aggregate by').'</b>'. ui_print_help_icon ('aggregate_by', true, ui_get_full_url(false, false, false, false)) . "</td>";
	$aggregate_list = array();
	$aggregate_list = array ('none' => __('None'), 'proto' => __('Protocol'), 'srcip' =>__('Src Ip Address'), 'dstip' =>__('Dst Ip Address'), 'srcport' =>__('Src Port'), 'dstport' =>__('Dst Port') );
	echo "<td colspan='2'>" . html_print_select ($aggregate_list, "aggregate", $filter['aggregate'], '', '', 0, true, false, true, '', false) . "</td>";
	
	echo "<td>" . '<b>'.__('Output format').'</b>' . "</td>";
	$show_output = array ('bytes' => __('Bytes'), 'bytespersecond' => __('Bytes per second'), 'kilobytes' => __('Kilobytes'), 'megabytes' => __('Megabytes'), 'kilobytespersecond' => __('Kilobytes per second'), 'megabytespersecond' => __('Megabytes per second'));
	echo "<td colspan='2'>" . html_print_select ($show_output, 'output', $filter['output'], '', '', 0, true, false, true, '', false) . "</td>";
	
	echo "</tr>";
	
	echo "</table>";
	
	echo "<br />";
	
	if (defined ('METACONSOLE')) {
		echo "<table class='databox' width='800' style='border: 0px;'><tr><td>";
	}
	
	html_print_submit_button (__('Draw'), 'draw_button', false, 'class="sub upd"');
	if (check_acl ($config["id_user"], 0, "AW")) {
		html_print_submit_button (__('Save as new filter'), 'save_button', false, 'class="sub upd" onClick="return defineFilterName();"');
		
		html_print_submit_button (__('Update current filter'), 'update_button', false, 'class="sub upd"');
	}
	
	if (defined ('METACONSOLE')) {
		echo "</td></tr></table>";
	}
echo'</form>';

if ($draw != '') {
	// Get the command to call nfdump
	$command = netflow_get_command ($filter);
	
	// Draw
	echo "<br/>";
	echo netflow_draw_item ($start_date, $end_date, $interval_length, $chart_type, $filter, $max_aggregates, $connection_name);
}
?>

<script type="text/javascript">
	// Hide the normal filter and display the advanced filter
	function displayAdvancedFilter () {
		// Erase the normal filter
		$("#text-ip_dst").value = '';
		$("#text-ip_src").value = '';
		$("#text-dst_port").value = '';
		$("#text-src_port").value = '';
		
		// Hide the normal filter
		$(".filter_normal").css('display', 'none');
		
		// Show the advanced filter
		$(".filter_advance").css('display',  '');
	};
	
	// Hide the advanced filter and display the normal filter
	function displayNormalFilter () {
		// Erase the advanced filter
		$("#textarea_advanced_filter").val('');
		
		// Hide the advanced filter
		$(".filter_advance").css('display', 'none');
		
		// Show the normal filter
		$(".filter_normal").css('display', '');
	};
	
	// Ask the user to define a name for the filter in order to save it
	function defineFilterName () {
		if ($("#text-name").val() == '') {
			$(".filter_save").css('display', '');
			
			return false;
		}
		
		return true;
	};
	
	// Display the appropriate filter
	var filter_type = <?php echo $filter_type ?>;
	if (filter_type == 0) {
		displayNormalFilter ();
	}
	else {
		displayAdvancedFilter ();
	}
	
	$("#filter_id").change(function () {
		var filter_type;
		
		// Hide information and name/group row
		$(".filter_save").css('display', 'none');
		$(".filter_save").css('display', 'none');
		
		// Clean fields
		if ($("#filter_id").val() == 0) {
			//displayNormalFilter ();
			$(".filter_normal").css('display', '');
			$(".filter_advance").css('display', 'none');
			
			// Check right filter type
			$("#radiobtn0001").attr("checked", "checked");
			
			$("#text-ip_dst").val('');
			$("#text-ip_src").val('');
			$("#text-dst_port").val('');
			$("#text-src_port").val('');
			$("#textarea_advanced_filter").val('');
			$("#aggregate").val('');
			$("#output").val('');
			
			// Hide update filter button
			$("#submit-update_button").css("visibility", "hidden");
			
		}
		else {
			// Load fields from DB
			
			// Get filter type
			<?php
			if (! defined ('METACONSOLE')) {
				echo 'jQuery.post ("ajax.php",';
			}
			else {
				echo 'jQuery.post ("' . $config['homeurl'] . '../../ajax.php",';
			}
			?>
				{"page" : "operation/netflow/nf_live_view",
				"get_filter_type" : 1,
				"id" : $("#filter_id").val()
				},
				function (data) {
					filter_type = data;
					// Display the appropriate filter
					if (filter_type == 0) {
						$(".filter_normal").css('display', '');
						$(".filter_advance").css('display', 'none');
						
						// Check right filter type
						$("#radiobtn0001").attr("checked", "checked");
					}
					else {
						$(".filter_normal").css('display', 'none');
						$(".filter_advance").css('display', '');
						
						// Check right filter type
						$("#radiobtn0002").attr("checked", "checked");
					}
				});
			
			// Shows update filter button
			$("#submit-update_button").css("visibility", "");
			
			// Get filter values from DB
			<?php
			if (! defined ('METACONSOLE')) {
				echo 'jQuery.post ("ajax.php",';
			}
			else {
				echo 'jQuery.post ("' . $config['homeurl'] . '../../ajax.php",';
			}
			?>
				{"page" : "operation/netflow/nf_live_view",
				"get_filter_values" : 1,
				"id" : $("#filter_id").val()
				},
				function (data) {
					jQuery.each (data, function (i, val) {
						if (i == 'ip_dst')
							$("#text-ip_dst").val(val);
						if (i == 'ip_src')
							$("#text-ip_src").val(val);
						if (i == 'dst_port')
							$("#text-dst_port").val(val);
						if (i == 'src_port')
							$("#text-src_port").val(val);
						if (i == 'advanced_filter')
							$("#textarea_advanced_filter").val(val);
						if (i == 'aggregate')
							$("#aggregate").val(val);
						if (i == 'output')
							$("#output").val(val);
					});
				},
				"json");
		}
		
	});
	
	$(document).ready( function() {
		// Hide update filter button
		if ($("#filter_id").val() == 0) {
			$("#submit-update_button").css("visibility", "hidden");
		}
		else {
			$("#submit-update_button").css("visibility", "");
		}
		
		// Change color of name and group if save button has been pushed
		$("#submit-save_button").click(function () {
			if ($("#text-name").val() == "") {
				$('#filter_name_color').css('color', '#CC0000');
				$('#filter_group_color').css('color', '#CC0000');
			}
			else {
				$('#filter_name_color').css('color', '#000000');
				$('#filter_group_color').css('color', '#000000');
			}
		});
	});
</script>
