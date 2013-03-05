<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


include_once($config['homedir'] . "/include/functions_users.php");
include_once($config['homedir'] . "/include/functions_io.php");
include_once ($config['homedir'] . "/include/functions_io.php");
enterprise_include_once ($config['homedir'] . '/enterprise/include/pdf_translator.php');
enterprise_include_once ($config['homedir'] . '/enterprise/include/functions_metaconsole.php');

// Date format for nfdump
global $nfdump_date_format;
$nfdump_date_format = 'Y/m/d.H:i:s';

/**
 * Selects all netflow filters (array (id_name => id_name)) or filters filtered
 *
 * @param mixed Array with filter conditions to retrieve filters or false.  
 *
 * @return array List of all filters
 */
function netflow_get_filters ($filter = false) {
	if ($filter === false) { 
		$filters = db_get_all_rows_in_table ("tnetflow_filter", "id_name");
	}
	else {
		$filters = db_get_all_rows_filter ("tnetflow_filter", $filter);
	}
	
	$return = array ();
	if ($filters === false) {
		return $return;
	}
	foreach ($filters as $filter) {
		$return[$filter["id_name"]] = $filter["id_name"];
	}
	return $return;
}


/**
 * Selects all netflow reports (array (id_name => id_name)) or filters filtered
 *
 * @param mixed Array with filter conditions to retrieve filters or false.  
 *
 * @return array List of all filters
 */
function netflow_get_reports ($filter = false) {
	if ($filter === false) { 
		$filters = db_get_all_rows_in_table ("tnetflow_report", "id_name");
	}
	else {
		$filters = db_get_all_rows_filter ("tnetflow_report", $filter);
	}
	
	$return = array ();
	if ($filters === false) {
		return $return;
	}
	foreach ($filters as $filter) {
		$return[$filter["id_name"]] = $filter["id_name"];
	}
	return $return;
}

//permite validar si un filtro pertenece a un grupo permitido para el usuario

function netflow_check_filter_group ($id_sg) {
	global $config;
	
	$id_group = db_get_value('id_group', 'tnetflow_filter', 'id_sg', $id_sg);	
	$own_info = get_user_info ($config['id_user']);
	// Get group list that user has access
	$groups_user = users_get_groups ($config['id_user'], "IW", $own_info['is_admin'], true);
	$groups_id = array();
	$has_permission = false;
	
	foreach($groups_user as $key => $groups){
		if ($groups['id_grupo'] == $id_group)
			return true;
	}
	return false;
}

/* Permite validar si un informe pertenece a un grupo permitido para el usuario.
 * Si mode = false entonces es modo godmode y solo puede ver el grupo All el admin
 * Si es modo operation (mode = true) entonces todos pueden ver el grupo All
 */

function netflow_check_report_group ($id_report, $mode=false) {
	global $config;
	
	if (!$mode) {
		$own_info = get_user_info ($config['id_user']);
		$mode = $own_info['is_admin'];
	}
	$id_group = db_get_value('id_group', 'tnetflow_report', 'id_report', $id_report);
	
	// Get group list that user has access
	$groups_user = users_get_groups ($config['id_user'], "IW", $mode, true);
	$groups_id = array();
	$has_permission = false;
	
	foreach($groups_user as $key => $groups){
		if ($groups['id_grupo'] == $id_group)
			return true;
	}
	return false;
}


/**
 * Get a filter.
 *
 * @param int filter id to be fetched.
 * @param array Extra filter.
 * @param array Fields to be fetched.
 *
 * @return array A netflow filter matching id and filter.
 */
function netflow_filter_get_filter ($id_sg, $filter = false, $fields = false) {
	if (! is_array ($filter))
		$filter = array ();
	
	$filter['id_sg'] = (int) $id_sg;
	
	return db_get_row_filter ('tnetflow_filter', $filter, $fields);
}

/**
 * Get options.
 *
 * @param int filter id to be fetched.
 * @param array Extra filter.
 * @param array Fields to be fetched.
 *
 * @return array A netflow filter matching id and filter.
 */
function netflow_reports_get_reports ($id_report, $filter = false, $fields = false) {
	if (empty ($id_report))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_report'] = (int) $id_report;
	
	return db_get_row_filter ('tnetflow_report', $filter, $fields);
}

function netflow_reports_get_content ($id_rc, $filter = false, $fields = false){
	if (empty ($id_rc))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_rc'] = (int) $id_rc;
	
	return db_get_row_filter ('tnetflow_report_content', $filter, $fields);
}

/**
 * Compare two flows according to the 'data' column.
 *
 * @param array a First flow.
 * @param array b Second flow.
 *
 * @return Result of the comparison.
 */
function compare_flows ($a, $b) {
	return $a['data'] < $b['data'];
}

/**
 * Sort netflow data according to the 'data' column.
 *
 * @param array netflow_data Netflow data array.
 *
 */
function sort_netflow_data (&$netflow_data) {
	usort($netflow_data, "compare_flows");
}

/**
 * Show a table with netflow statistics.
 *
 * @param array data Statistic data.
 * @param string start_date Start date.
 * @param string end_date End date.
 * @param string aggregate Aggregate field.
 * @param string unit Unit to show.
 *
 * @return The statistics table.
 */
function netflow_stat_table ($data, $start_date, $end_date, $aggregate, $unit){
	global $nfdump_date_format;

	$start_date = date ($nfdump_date_format, $start_date);
	$end_date = date ($nfdump_date_format, $end_date);
	$values = array();
	$table->width = '50%';
	$table->cellspacing = 0;
	$table->class = 'databox';
	$table->data = array();
	$j = 0;
	$x = 0;
	
	$table->head = array ();
	$table->head[0] = '<b>' . netflow_format_aggregate ($aggregate) . '</b>';
	$table->head[1] = '<b>' . netflow_format_unit ($unit) . '</b>';
	$table->style[0] = 'border: 1px solid black; padding: 4px';
	$table->style[1] = 'border: 1px solid black; padding: 4px';

	while (isset ($data[$j])) {
		$agg = $data[$j]['agg'];
		if (!isset($values[$agg])){
			$values[$agg] = $data[$j]['data'];
			$table->data[$x][0] = $agg;
			$table->data[$x][1] = format_numeric ($data[$j]['data']) . '&nbsp;' . netflow_format_unit ($unit);
		}
		else {
			$values[$agg] += $data[$j]['data'];
			$table->data[$x][0] = $agg;
			$table->data[$x][1] = format_numeric ($data[$j]['data']) . '&nbsp;' . netflow_format_unit ($unit);
		}
		$j++;
		$x++;
	}
	
	return html_print_table ($table, true);
}

/**
 * Show a table with netflow data.
 *
 * @param array data Netflow data.
 * @param string start_date Start date.
 * @param string end_date End date.
 * @param string aggregate Aggregate field.
 *
 * @return The statistics table.
 */
function netflow_data_table ($data, $start_date, $end_date, $aggregate, $unit) {
	global $nfdump_date_format;
	
	$period = $end_date - $start_date;
	$start_date = date ($nfdump_date_format, $start_date);
	$end_date = date ($nfdump_date_format, $end_date);
	
	// Set the format
	if ($period <= SECONDS_6HOURS) {
		$time_format = 'H:i:s';
	}
	elseif ($period < SECONDS_1DAY) {
		$time_format = 'H:i';
	}
	elseif ($period < SECONDS_15DAYS) {
		$time_format = 'M d H:i';
	}
	elseif ($period < SECONDS_1MONTH) {
		$time_format = 'M d H\h';
	}
	else {  
		$time_format = 'M d H\h';
	}
	
	$values = array();
	$table->size = array ('60%');
	$table->class = 'databox_grid';
	$table->cellspacing = 0;
	$table->data = array();
	
	$table->head = array();
	$table->head[0] = '<b>'.__('Timestamp').'</b>';
	$table->style[0] = 'padding: 4px';
	
	$j = 0;
	$source_index = array ();
	$source_count = 0;
	
	if (isset ($data['sources'])) {
		foreach ($data['sources'] as $source => $null) {
			$table->style[$j+1] = 'padding: 4px';
			$table->align[$j+1] = "right";
			$table->head[$j+1] = $source;
			$source_index[$j] = $source;
			$source_count++;
			$j++;
		}
	}
	else {
		$table->style[1] = 'padding: 4px;';
	}
	
	// No aggregates
	if ($source_count == 0) {
		$table->head[1] = __('Data');
		$table->align[1] = "right";
		$i = 0;
		
		
		foreach ($data as $timestamp => $value) {
			$table->data[$i][0] = date ($time_format, $timestamp);
			$table->data[$i][1] = format_numeric ($value['data']) . '&nbsp;' . netflow_format_unit ($unit);
			$i++;
		}
	}
	// Aggregates
	else {
		$i = 0;
		foreach ($data['data'] as $timestamp => $values) {
			$table->data[$i][0] = date ($time_format, $timestamp);
			for ($j = 0; $j < $source_count; $j++) {
				if (isset ($values[$source_index[$j]])) {
					$table->data[$i][$j+1] = format_numeric ($values[$source_index[$j]]) . '&nbsp;' . netflow_format_unit ($unit);
				}
				else {
					$table->data[$i][$j+1] = 0 . '&nbsp;' . netflow_format_unit ($unit);
				}
			}
			$i++;
		}
	}
	
	return html_print_table($table, true);
}

/**
 * Show a table with a traffic summary.
 *
 * @param array data Summary data.
 *
 * @return The statistics table.
 */
function netflow_summary_table ($data) {
	global $nfdump_date_format;
	
	$values = array();
	$table->size = array ('50%');
	$table->cellspacing = 0;
	$table->class = 'databox';
	$table->data = array();
	
	$table->style[0] = 'border: 1px solid black;padding: 4px';
	$table->style[1] = 'border: 1px solid black;padding: 4px';
	$table->data[0][0] = '<b>'.__('Total flows').'</b>';
	$table->data[0][1] = format_numeric ($data['totalflows']);
	$table->data[1][0] = '<b>'.__('Total bytes').'</b>';
	$table->data[1][1] = format_numeric ($data['totalbytes']);
	$table->data[2][0] = '<b>'.__('Total packets').'</b>';
	$table->data[2][1] = format_numeric ($data['totalpackets']);
	$table->data[3][0] = '<b>'.__('Average bits per second'). '</b>';
	$table->data[3][1] = format_numeric ($data['avgbps']);
	$table->data[4][0] = '<b>'.__('Average packets per second').'</b>';
	$table->data[4][1] = format_numeric ($data['avgpps']);
	$table->data[5][0] = '<b>'.__('Average bytes per packet').'</b>';
	$table->data[5][1] = format_numeric ($data['avgbpp']);
	
	$html = html_print_table ($table, true);
	
	return $html;
}

/**
 * Returns 1 if the given address is a network address.
 *
 * @param string address Host or network address.
 *
 * @return 1 if the address is a network address, 0 otherwise.
 *
 */
function netflow_is_net ($address) {
	if (strpos ($address, '/') !== FALSE) {
		return 1;
	}
	
	return 0;
}

/**
 * Returns netflow data for the given period in an array.
 *
 * @param string start_date Period start date.
 * @param string end_date Period end date.
 * @param string filter Netflow filter.
 * @param string aggregate Aggregate field.
 * @param int max Maximum number of aggregates.
 * @param string unit Unit to show.
 *
 * @return An array with netflow stats.
 *
 */
function netflow_get_data ($start_date, $end_date, $interval_length, $filter, $aggregate, $max, $unit, $connection_name = '') {
	global $nfdump_date_format;
	global $config;
	
	// Requesting remote data
	if (defined ('METACONSOLE') && $connection_name != '') {
		$data = metaconsole_call_remote_api ($connection_name, 'netflow_get_data', "$start_date|$end_date|$interval_length|" . base64_encode(json_encode($filter)) . "|$aggregate|$max|$unit");
		return json_decode ($data, true);
	}
	
	// Calculate the number of intervals
	if ($interval_length <= 0) {
		$num_intervals = $config['graph_res'] * 50;
		$period = $end_date - $start_date;
		$interval_length = (int) ($period / $num_intervals);
	}
	else {
		$period = $end_date - $start_date;
		$num_intervals = (int) ($period / $interval_length);
	}
	
	// Set a max number of intervals
	if ($num_intervals > $config['netflow_max_resolution']) {
		$num_intervals = $config['netflow_max_resolution'];
		$interval_length = (int) ($period / $num_intervals);
	}
	
	// If there is aggregation calculate the top n
	if ($aggregate != 'none') {
		$values['data'] = array ();
		$values['sources'] = array ();
		
		// Get the command to call nfdump
		$command = netflow_get_command ($filter);
		
		// Suppress the header line and the statistics at the bottom and configure piped output
		$command .= ' -q -o csv';
		
		// Call nfdump
		$agg_command = $command . " -s $aggregate/bytes -n $max -t ".date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
		exec($agg_command, $string);
		
		// Reamove the first line
		$string[0] = '';
		
		// Parse aggregates
		foreach($string as $line){
			if ($line=='') {
				continue;
			}
			$val = explode(',',$line);
			if ($aggregate == 'proto') {
				$values['sources'][$val[3]] = 1;
			}
			else {
				$values['sources'][$val[4]] = 1;
			}
		}
		
		// Update the filter
		switch ($aggregate) {
			case "proto":
				$extra_filter = 'proto';
				break;
			default:
			case "srcip":
				$extra_filter = 'ip_src';
				break;
			case "srcport":
				$extra_filter = 'src_port';
				break;
			case "dstip":
				$extra_filter = 'ip_dst';
				break;
			case "dstport":
				$extra_filter = 'dst_port';
				break;
		}
		if (isset ($filter[$extra_filter]) && $filter[$extra_filter] != '') {
			$filter[$extra_filter] .= ',';
		}
		$filter[$extra_filter] = implode (',', array_keys ($values['sources']));
	}
	else {
		$values = array ();
	}
	
	$interval_start = $start_date;
	for ($i = 0; $i < $num_intervals; $i++, $interval_start+=$interval_length+1) {
		$interval_end = $interval_start + $interval_length;
		if ($interval_end > $end_date) {
			$interval_end = $end_date;
		}
		
		if ($aggregate == 'none') {
			$data = netflow_get_summary ($interval_start, $interval_end, $filter, $connection_name);
			if (! isset ($data['totalbytes'])) {
				$values[$interval_start]['data'] = 0;
				continue;
			}
			
			switch ($unit) {
				case 'megabytes': 
					$values[$interval_start]['data'] = $data['totalbytes'] / 1048576;
					break;
				case 'megabytespersecond': 
					$values[$interval_start]['data'] = $data['avgbps'] / 1048576 / 8;
					break;
				case 'kilobytes': 
					$values[$interval_start]['data'] = $data['totalbytes'] / 1024;				
					break;
				case 'kilobytespersecond': 
					$values[$interval_start]['data'] = $data['avgbps'] / 1024 / 8;
					break;
				default:
					$values[$interval_start]['data'] = $data['totalbytes'];
					break;
			}
		}
		else {
			
			// Set default values
			foreach ($values['sources'] as $source => $discard) {
				$values['data'][$interval_start][$source] = 0;
			}
			$data = netflow_get_stats ($interval_start, $interval_end, $filter, $aggregate, $max, $unit, $connection_name);
			foreach ($data as $line) {
				if (! isset ($values['sources'][$line['agg']])) {
					continue;
				}
				
				$values['data'][$interval_start][$line['agg']] = $line['data'];
			}
		}
	}
	
	if (($aggregate != 'none') && (empty($values['data']))) {
		return array();
	}
	
	return $values;
}

/**
 * Returns netflow stats for the given period in an array.
 *
 * @param string start_date Period start date.
 * @param string end_date Period end date.
 * @param string filter Netflow filter.
 * @param string aggregate Aggregate field.
 * @param int max Maximum number of aggregates.
 * @param string unit Unit to show.
 *
 * @return An array with netflow stats.
 */
function netflow_get_stats ($start_date, $end_date, $filter, $aggregate, $max, $unit, $connection_name = '') {
	global $nfdump_date_format;

	// Requesting remote data
	if (defined ('METACONSOLE') && $connection_name != '') {
		$data = metaconsole_call_remote_api ($connection_name, 'netflow_get_stats', "$start_date|$end_date|" . base64_encode(json_encode($filter)) . "|$aggregate|$max|$unit");
		return json_decode ($data, true);
	}

	// Get the command to call nfdump
	$command = netflow_get_command ($filter);

	// Execute nfdump
	$command .= " -o csv -q -s $aggregate/bytes -n $max -t " .date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
	exec($command, $string);

	if (! is_array($string)) {
		return array ();
	}
	
	// Remove the first line
	$string[0] = '';
	
	$i = 0;
	$values = array();
	$interval_length = $end_date - $start_date;
	foreach ($string as $line) {
		if ($line == '') {
			continue;
		}
		
		$val = explode(',',$line);
		
		$values[$i]['date'] = $val[0];
		$values[$i]['time'] = $val[1];
		
		//create field to sort array
		$datetime = $val[0];
		$end_date = strtotime ($datetime);
		$values[$i]['datetime'] = $end_date;
		if ($aggregate == 'proto') {
			$values[$i]['agg'] = $val[3];
		}
		else {
			$values[$i]['agg'] = $val[4];
		}
		if (! isset ($val[9])) {
			return array ();
		}
		
		switch ($unit){
			case "megabytes":
				$values[$i]['data'] = $val[9] / 1048576;
				break;
			case "megabytespersecond":
				$values[$i]['data'] = $val[9] / 1048576 / $interval_length;
				break;
			case "kilobytes":
				$values[$i]['data'] = $val[9] / 1024;
				break;
			case "kilobytespersecond":
				$values[$i]['data'] = $val[9] / 1024 / $interval_length;
				break;
			default:
			case "bytes":
				$values[$i]['data'] = $val[9];
				break;
			case "bytespersecond":
				$values[$i]['data'] = $val[9] / $interval_length;
				break;
		}
		$i++;
	}
	
	sort_netflow_data ($values);
	
	return $values;
}

/**
 * Returns a traffic summary for the given period in an array.
 *
 * @param string start_date Period start date.
 * @param string end_date Period end date.
 * @param string filter Netflow filter.
 *
 * @return An array with netflow stats.
 */
function netflow_get_summary ($start_date, $end_date, $filter, $connection_name = '') {
	global $nfdump_date_format;
	global $config;

	// Requesting remote data
	if (defined ('METACONSOLE') && $connection_name != '') {
		$data = metaconsole_call_remote_api ($connection_name, 'netflow_get_summary', "$start_date|$end_date|" . base64_encode(json_encode($filter)));
		return json_decode ($data, true);
	}

	// Get the command to call nfdump
	$command = netflow_get_command ($filter);

	// Execute nfdump
	$command .= " -o csv -s srcip/bytes -n 1 -t " .date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
	exec($command, $string);

	if (! is_array($string) || ! isset ($string[5])) {
		return array ();
	}
	
	// Read the summary
	$summary = explode (',', $string[5]);
	if (! isset ($summary[5])) {
		return array ();
	}

	$values['totalflows'] = $summary[0];
	$values['totalbytes'] = $summary[1];
	$values['totalpackets'] = $summary[2];
	$values['avgbps'] = $summary[3];
	$values['avgpps'] = $summary[4];
	$values['avgbpp'] = $summary[5];
	
	return $values;
}

/**
 * Returns the command needed to run nfdump for the given filter.
 *
 * @param array filter Netflow filter.
 *
 * @return Command to run.
 *
 */
function netflow_get_command ($filter) {
	global $config;
	
	// Build command
	$command = io_safe_output ($config['netflow_nfdump']) . ' -N -Otstart';

	// Netflow data path
	if (isset($config['netflow_path']) && $config['netflow_path'] != '') {
		$command .= ' -R '.$config['netflow_path'];
	}
	
	// Filter options
	$command .= netflow_get_filter_arguments ($filter);
	
	return $command;
}

/**
 * Returns the nfdump command line arguments that match the given filter.
 *
 * @param array filter Netflow filter.
 *
 * @return Command line argument string.
 *
 */
function netflow_get_filter_arguments ($filter) {
	
	// Advanced filter
	$filter_args = '';
	if ($filter['advanced_filter'] != '') {
		$filter_args = preg_replace('/["\r\n]/','', io_safe_output ($filter['advanced_filter']));
		return ' "(' . $filter_args . ')"';
	}
	
	// Normal filter
	if ($filter['ip_dst'] != '') {
		$filter_args .= ' "(';
		$val_ipdst = explode(',', io_safe_output ($filter['ip_dst']));
		for($i = 0; $i < count ($val_ipdst); $i++){
			if ($i > 0) {
				$filter_args .= ' or ';
			}
			
			if (netflow_is_net ($val_ipdst[$i]) == 0) {
				$filter_args .= 'dst ip '.$val_ipdst[$i];
			}
			else {
				$filter_args .= 'dst net '.$val_ipdst[$i];
			}
		}
		$filter_args .=  ')';
	}
	if ($filter['ip_src'] != '') {
		if ($filter_args == '') {
			$filter_args .= ' "(';
		}
		else {
			$filter_args .= ' and (';
		}
		$val_ipsrc = explode(',', io_safe_output ($filter['ip_src']));
		for ($i = 0; $i < count ($val_ipsrc); $i++) {
			if ($i > 0) {
				$filter_args .= ' or ';
			}
			
			if (netflow_is_net ($val_ipsrc[$i]) == 0) {
				$filter_args .= 'src ip '.$val_ipsrc[$i];
			}
			else {
				$filter_args .= 'src net '.$val_ipsrc[$i];
			}
		}
		$filter_args .=  ')';
	}
	if ($filter['dst_port'] != '') {
		if ($filter_args == '') {
			$filter_args .= ' "(';
		}
		else {
			$filter_args .= ' and (';
		}
		$val_dstport = explode(',', io_safe_output ($filter['dst_port']));
		for ($i = 0; $i < count ($val_dstport); $i++) {
			if ($i > 0) {
				$filter_args .= ' or ';
			}
			$filter_args .= 'dst port '.$val_dstport[$i];
		}
		$filter_args .=  ')';
	}
	if ($filter['src_port'] != '') {
		if ($filter_args == '') {
			$filter_args .= ' "(';
		}
		else {
			$filter_args .= ' and (';
		}
		$val_srcport = explode(',', io_safe_output ($filter['src_port']));
		for ($i = 0; $i < count ($val_srcport); $i++) {
			if ($i > 0) {
				$filter_args .= ' or ';
			}
			$filter_args .= 'src port '.$val_srcport[$i];
		}
		$filter_args .=  ')';
	}
	if (isset ($filter['proto']) && $filter['proto'] != '') {
		if ($filter_args == '') {
			$filter_args .= ' "(';
		}
		else {
			$filter_args .= ' and (';
		}
		$val_proto = explode(',', io_safe_output ($filter['proto']));
		for ($i = 0; $i < count ($val_proto); $i++) {
			if ($i > 0) {
				$filter_args .= ' or ';
			}
			$filter_args .= 'proto '.$val_proto[$i];
		}
		$filter_args .=  ')';
	}
	if ($filter_args != '') {
		$filter_args .= '"';
	}
	
	return $filter_args;
}

/**
 * Get the types of netflow charts.
 *
 * @return Array of types.
 *
 */
function netflow_get_chart_types () {
	return array(
		'netflow_area' => __('Area graph'),
		'netflow_pie_summatory' => __('Pie graph and Summary table'),
		'netflow_statistics' => __('Statistics table'),
		'netflow_data' => __('Data table'));
}

/**
 * Gets valid intervals for a netflow chart in the format:
 *
 * interval_length => interval_description
 *
 * @return Array of valid intervals.
 *
 */
function netflow_get_valid_intervals () {
	return array (
		(string)SECONDS_10MINUTES => __('10 mins'),
		(string)SECONDS_15MINUTES => __('15 mins'),
		(string)SECONDS_30MINUTES => __('30 mins'),
		(string)SECONDS_1HOUR => __('1 hour'),
		(string)SECONDS_2HOUR => __('2 hours'),
		(string)SECONDS_5HOUR => __('5 hours'),
		(string)SECONDS_12HOURS => __('12 hours'),
		(string)SECONDS_1DAY => __('1 day'),
		(string)SECONDS_2DAY => __('2 days'),
		(string)SECONDS_5DAY => __('5 days'),
		(string)SECONDS_15DAYS => __('15 days'),
		(string)SECONDS_1WEEK => __('Last week'),
		(string)SECONDS_1MONTH => __('Last month'),
		(string)SECONDS_2MONTHS => __('2 months'),
		(string)SECONDS_3MONTHS => __('3 months'),
		(string)SECONDS_6MONTHS => __('6 months'),
		(string)SECONDS_1YEAR => __('Last year'),
		(string)SECONDS_2YEARS => __('2 years'));
}

/**
 * Gets valid intervals for a netflow chart in the format:
 *
 * interval_length => interval_description
 *
 * @return Array of valid intervals.
 *
 */
function netflow_get_valid_subintervals () {
	return array (
		(string)SECONDS_1MINUTE => __('1 min'),
		(string)SECONDS_2MINUTES => __('2 mins'),
		(string)SECONDS_5MINUTES => __('5 mins'),
		(string)SECONDS_10MINUTES => __('10 mins'),
		(string)SECONDS_15MINUTES => __('15 mins'),
		(string)SECONDS_30MINUTES => __('30 mins'),
		(string)SECONDS_1HOUR => __('1 hour'),
		(string)SECONDS_2HOUR => __('2 hours'),
		(string)SECONDS_5HOUR => __('5 hours'),
		(string)SECONDS_12HOURS => __('12 hours'),
		(string)SECONDS_1DAY => __('1 day'),
		(string)SECONDS_2DAY => __('2 days'),
		(string)SECONDS_5DAY => __('5 days'),
		(string)SECONDS_15DAYS => __('15 days'),
		(string)SECONDS_1WEEK => __('1 week'),
		(string)SECONDS_1MONTH => __('1 month'));
}

/**
 * Draw a netflow report item.
 *
 * @param string start_date Period start date.
 * @param string end_date Period end date.
 * @param string interval_length Interval length in seconds (num_intervals * interval_length = start_date - end_date).
 * @param string type Chart type.
 * @param array filter Netflow filter.
 * @param int max_aggregates Maximum number of aggregates.
 * @param string output Output format. Only HTML and XML are supported.
 *
 * @return The netflow report in the appropriate format.
 */
function netflow_draw_item ($start_date, $end_date, $interval_length, $type, $filter, $max_aggregates, $connection_name = '', $output = 'HTML', $only_image = false) {
	$aggregate = $filter['aggregate'];
	$unit = $filter['output'];
	$interval = $end_date - $start_date;
	
	// Process item
	switch ($type) {
		case '0':
		case 'netflow_area':
			$data = netflow_get_data ($start_date, $end_date, $interval_length, $filter, $aggregate, $max_aggregates, $unit, $connection_name);
			if (empty ($data)) {
				break;
			}
			if ($aggregate != 'none') {
				if ($output == 'HTML') {
					$html = "<b>" . __('Unit') . ":</b> " . netflow_format_unit ($unit);
					$html .= "&nbsp;<b>" . __('Aggregate') . ":</b> " . netflow_format_aggregate ($aggregate);
					if ($interval_length != 0) {
						$html .= "&nbsp;<b>" . _('Resolution') . ":</b> $interval_length " . __('seconds');
					}
					$html .= graph_netflow_aggregate_area ($data, $interval, 850, 320, netflow_format_unit ($unit));
					return $html;
				}
				else if ($output == 'PDF') {
					$html = "<b>" . __('Unit') . ":</b> " . netflow_format_unit ($unit);
					$html .= "&nbsp;<b>" . __('Aggregate') . ":</b> " . netflow_format_aggregate ($aggregate);
					if ($interval_length != 0) {
						$html .= "&nbsp;<b>" . _('Resolution') . ":</b> $interval_length " . __('seconds');
					}
					$html .= graph_netflow_aggregate_area ($data, $interval, 850, 320, netflow_format_unit ($unit), 2, true);
					return $html;
				}
				else if ($output == 'XML') {
					$xml = "<unit>$unit</unit>\n";
					$xml .= "<aggregate>$aggregate</aggregate>\n";
					$xml .= "<resolution>$interval_length</resolution>\n";
					$xml .= netflow_aggregate_area_xml ($data);
					return $xml;
				}
			}
			else {
				if ($output == 'HTML') {
					$html = "<b>" . __('Unit') . ":</b> " . netflow_format_unit ($unit);
					if ($interval_length != 0) {
						$html .= "&nbsp;<b>" . _('Resolution') . ":</b> $interval_length " . __('seconds');
					}
					$html .= graph_netflow_total_area ($data, $interval, 660, 320, netflow_format_unit ($unit));
					return $html;
				}
				else if ($output == 'PDF') {
					$html = "<b>" . __('Unit') . ":</b> " . netflow_format_unit ($unit);
					if ($interval_length != 0) {
						$html .= "&nbsp;<b>" . _('Resolution') . ":</b> $interval_length " . __('seconds');
					}
					$html .= graph_netflow_total_area ($data, $interval, 660, 320, netflow_format_unit ($unit), 2, true);
					return $html;
				}
				else if ($output == 'XML') {
					$xml = "<unit>$unit</unit>\n";
					$xml .= "<resolution>$interval_length</resolution>\n";
					$xml .= netflow_total_area_xml ($data);
					return $xml;
				}
			}
			break;
		case '2':
		case 'netflow_data':
			$data = netflow_get_data ($start_date, $end_date, $interval_length, $filter, $aggregate, $max_aggregates, $unit, $connection_name);
			
			if (empty ($data)) {
				break;
			}
			if ($output == 'HTML' || $output == 'PDF') {
				$html = "<b>" . __('Unit') . ":</b> " . netflow_format_unit ($unit);
				$html .= "&nbsp;<b>" . __('Aggregate') . ":</b> " . netflow_format_aggregate ($aggregate);
				if ($interval_length != 0) {
					$html .= "&nbsp;<b>" . _('Resolution') . ":</b> $interval_length " . __('seconds');
				}
				$html .= netflow_data_table ($data, $start_date, $end_date, $aggregate, $unit);
				
				return $html;
			}
			else if ($output == 'XML') {
				$xml = "<unit>$unit</unit>\n";
				$xml .= "<aggregate>$aggregate</aggregate>\n";
				$xml .= "<resolution>$interval_length</resolution>\n";
				// Same as netflow_aggregate_area_xml
				$xml .= netflow_aggregate_area_xml ($data);
				return $xml;
			}
			break;
		case '3':
		case 'netflow_statistics':
			$data = netflow_get_stats ($start_date, $end_date, $filter,
				$aggregate, $max_aggregates, $unit, $connection_name);
			if (empty ($data)) {
				break;
			}
			if ($output == 'HTML' || $output == 'PDF') {
				$html = netflow_stat_table ($data, $start_date, $end_date, $aggregate, $unit);
				return $html;
			}
			else if ($output == 'XML') {
				return netflow_stat_xml ($data);
			}
			break;
		case '4':
		case 'netflow_summary':
			$data_summary = netflow_get_summary ($start_date, $end_date,
				$filter, $connection_name);
			if (empty ($data)) {
				break;
			}
			if ($output == 'HTML' || $output == 'PDF') {
				return netflow_summary_table ($data_summary);
			}
			else if ($output == 'XML') {
				return netflow_summary_xml ($data_summary);
			}
			break;
		case '1':
		case 'netflow_pie':
			$data_pie = netflow_get_stats ($start_date, $end_date,
				$filter, $aggregate, $max_aggregates, $unit,
				$connection_name);
			if (empty ($data_pie)) {
				break;
			}
			if ($output == 'HTML') {
				$html = "<b>" . __('Unit') . ":</b> " . netflow_format_unit ($unit);
				$html .= "&nbsp;<b>" . __('Aggregate') . ":</b> " . netflow_format_aggregate ($aggregate);
				$html .= graph_netflow_aggregate_pie ($data_pie, netflow_format_aggregate ($aggregate));
				return $html;
			}
			else if ($output == 'PDF') {
				$html = "<b>" . __('Unit') . ":</b> " . netflow_format_unit ($unit);
				$html .= "&nbsp;<b>" . __('Aggregate') . ":</b> $aggregate";
				$html .= graph_netflow_aggregate_pie ($data_pie, netflow_format_aggregate ($aggregate), 2, true);
				return $html;
			}
			else if ($output == 'XML') {
				$xml = "<unit>$unit</unit>\n";
				$xml .= "<aggregate>$aggregate</aggregate>\n";
				$xml .= netflow_aggregate_pie_xml ($data_pie);
				return $xml;
			}
			break;
		case 'netflow_pie_summatory':
			$data_summary = netflow_get_summary ($start_date, $end_date,
				$filter, $connection_name);
			if (empty ($data_summary)) {
				break;
			}
			
			$data_pie = netflow_get_stats ($start_date, $end_date,
				$filter, $aggregate, $max_aggregates, $unit,
				$connection_name);
			if (empty ($data_pie)) {
				break;
			}
			
			switch ($output) {
				case 'HTML':
					$html = '<table>';
					$html .= '<tr>';
					$html .= '<td>';
					$html .= netflow_summary_table ($data_summary);
					$html .= "<b>" . __('Unit') . ":</b> " .
						netflow_format_unit ($unit);
					$html .= "&nbsp;<b>" . __('Aggregate') . ":</b> " . netflow_format_aggregate ($aggregate);
					$html .= '</td>';
					$html .= '<td>';
					$html .= graph_netflow_aggregate_pie ($data_pie, netflow_format_aggregate ($aggregate));
					$html .= '</td>';
					$html .= '</tr>';
					$html .= '</table>';
					return $html;
					break;
				case 'PDF':
					break;
				case 'XML':
					return netflow_summary_xml ($data_summary);
					break;
			}
			break;
		default:
			break;
	}
	
	if ($output == 'HTML' || $output == 'PDF') {
		return fs_error_image();
	}
}

/**
 * Render a netflow report as an XML.
 *
 * @param int ID of the netflow report.
 * @param string end_date Period start date.
 * @param string end_date Period end date.
 * @param string interval_length Interval length in seconds (num_intervals * interval_length = start_date - end_date).
 *
 */
function netflow_xml_report ($id, $start_date, $end_date, $interval_length = 0) {
	
	// Get report data
	$report = db_get_row_sql ('SELECT * FROM tnetflow_report WHERE id_report =' . (int) $id);
	if ($report === FALSE) {
		echo "<report>" . __('Error generating report') . "</report>\n"; 
		return;
	}
	
	// Print report header
	$time = get_system_time ();
	echo '<?xml version="1.0" encoding="UTF-8" ?>';
	echo "<report>\n";
	echo "  <generated>\n";
	echo "    <unix>" . $time . "</unix>\n"; 
	echo "    <rfc2822>" . date ("r", $time) . "</rfc2822>\n";
	echo "  </generated>\n"; 
	echo "  <name>" . io_safe_output ($report['id_name']) . "</name>\n";
	echo "  <description>" . io_safe_output ($report['description']) . "</description>\n";
	echo "  <start_date>" . date ("r", $start_date) . "</start_date>\n";
	echo "  <end_date>" . date ("r", $end_date) . "</end_date>\n";
	
	// Get netflow item types
	$item_types = netflow_get_chart_types ();
	
	// Print report items
	$report_contents = db_get_all_rows_sql ("SELECT *
		FROM tnetflow_report_content
		WHERE id_report='" . $report['id_report'] . "'
		ORDER BY `order`");
	foreach ($report_contents as $content) {
		
		// Get item filters
		$filter = db_get_row_sql("SELECT *
			FROM tnetflow_filter
			WHERE id_sg = '" . io_safe_input ($content['id_filter']) . "'",
				false, true);
		if ($filter === FALSE) {
			continue;
		}
		
		echo "  <report_item>\n";
		echo "    <description>" . io_safe_output ($content['description']) . "</description>\n";
		echo "    <type>" . io_safe_output ($item_types[$content['show_graph']]) . "</type>\n";
		echo "    <max_aggregates>" . $content['max'] . "</max_aggregates>\n";
		echo "    <filter>\n";
		echo "      <name>" . io_safe_output ($filter['id_name']) . "</name>\n";
		echo "      <src_ip>" . io_safe_output ($filter['ip_src']) . "</src_ip>\n";
		echo "      <dst_ip>" . io_safe_output ($filter['ip_dst']) . "</dst_ip>\n";
		echo "      <src_port>" . io_safe_output ($filter['src_port']) . "</src_port>\n";
		echo "      <dst_port>" . io_safe_output ($filter['src_port']) . "</dst_port>\n";
		echo "      <advanced>" . io_safe_output ($filter['advanced_filter']) . "</advanced>\n";
		echo "      <aggregate>" . io_safe_output ($filter['aggregate']) . "</aggregate>\n";
		echo "      <unit>" . io_safe_output ($filter['output']) . "</unit>\n";
		echo "    </filter>\n";
		
		echo netflow_draw_item ($start_date, $end_date, $interval_length, $content['show_graph'], $filter, $content['max'], $report['server_name'], 'XML');
		
		echo "  </report_item>\n";
	}
	echo "</report>\n"; 
}

/**
 * Render an aggregated area chart as an XML.
 *
 * @param array Netflow data.
 *
 */
function netflow_aggregate_area_xml ($data) {
	
	// Print source information
	if (isset($data['sources'])) {
		echo "<aggregates>\n";
		foreach ($data['sources'] as $source => $discard) {
			echo "<aggregate>$source</aggregate>\n";
		}
		echo "</aggregates>\n";
		
		// Print flow information
		echo "<flows>\n";
		foreach ($data['data'] as $timestamp => $flow) {
			
			echo "<flow>\n";
			echo "  <timestamp>" . $timestamp . "</timestamp>\n";
			echo "  <aggregates>\n";
			foreach ($flow as $source => $data) {
				echo "    <aggregate>$source</aggregate>\n";
				echo "    <data>" . $data . "</data>\n";
			}
			echo "  </aggregates>\n";
			echo "</flow>\n";
		}
		echo "</flows>\n";
	}
	else {
		echo "<flows>\n";
		foreach ($data as $timestamp => $flow) {
			echo "<flow>\n";
			echo "  <timestamp>" . $timestamp . "</timestamp>\n";
			echo "  <data>" . $flow['data'] . "</data>\n";
			echo "</flow>\n";
		}
		echo "</flows>\n";
	}
}

/**
 * Render an area chart as an XML.
 *
 * @param array Netflow data.
 *
 */
function netflow_total_area_xml ($data) {

	// Print flow information
	$xml = "<flows>\n";
	foreach ($data as $timestamp => $flow) {
		$xml .= "<flow>\n";
		$xml .= "  <timestamp>" . $timestamp . "</timestamp>\n";
		$xml .= "  <data>" . $flow['data'] . "</data>\n";
		$xml .= "</flow>\n";
	}
	$xml .= "</flows>\n";
	
	return $xml;
}

/**
 * Render a pie chart as an XML.
 *
 * @param array Netflow data.
 *
 */
function netflow_aggregate_pie_xml ($data) {

	// Calculate total
	$total = 0;
	foreach ($data as $flow) {
		$total += $flow['data'];
	}
	if ($total == 0) {
		return;
	}
	
	// Print percentages
	echo "<pie>\n";
	foreach ($data as $flow) {
		echo "<aggregate>" . $flow['agg'] . "</aggregate>\n";
		echo "<data>" . format_numeric (100 * $flow['data'] / $total, 2) . "%</data>\n";
	}
	echo "</pie>\n";
}

/**
 * Render a stats table as an XML.
 *
 * @param array Netflow data.
 *
 */
function netflow_stat_xml ($data) {
	
	// Print stats
	$xml .= "<stats>\n";
	foreach ($data as $flow) {
		$xml .= "<aggregate>" . $flow['agg'] . "</aggregate>\n";
		$xml .= "<data>" . $flow['data'] . "</data>\n";
	}
	$xml .= "</stats>\n";
	
	return $xml;
}

/**
 * Render a summary table as an XML.
 *
 * @param array Netflow data.
 *
 */
function netflow_summary_xml ($data) {
	
	// Print summary
	$xml = "<summary>\n";
	$xml .= "  <total_flows>" . $data['totalflows'] . "</total_flows>\n";
	$xml .= "  <total_bytes>" . $data['totalbytes'] . "</total_bytes>\n";
	$xml .= "  <total_packets>" . $data['totalbytes'] . "</total_packets>\n";
	$xml .= "  <average_bps>" . $data['avgbps'] . "</average_bps>\n";
	$xml .= "  <average_pps>" . $data['avgpps'] . "</average_pps>\n";
	$xml .= "  <average_bpp>" . $data['avgpps'] . "</average_bpp>\n";
	$xml .= "</summary>\n";
	
	return $xml;
}

/**
 * Return a string describing the given unit.
 *
 * @param string Netflow unit.
 */
function netflow_format_unit ($unit) {
		switch ($unit){
			case 'megabytes':
				return __('MB');
			case 'megabytespersecond':
				return __('MB/s');
			case 'kilobytes':
				return __('kB');
			case 'kilobytespersecond':
				return __('kB/s');
			case 'bytes':
				return __('Bytes');
			case 'bytespersecond':
				return __('B/s');
			default:
				return '';
		}
}

/**
 * Return a string describing the given aggregate.
 *
 * @param string Netflow aggregate.
 */
function netflow_format_aggregate ($aggregate) {
		switch ($aggregate){
			case 'dstport':
				return __('Dst port');
			case 'dstip':
				return __('Dst IP');
			case 'proto':
				return __('Protocol');
			case 'srcip':
				return __('Src IP');
			case 'srcport':
				return __('Src port');
			default:
				return '';
		}
}

?>
