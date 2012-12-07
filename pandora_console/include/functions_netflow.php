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
	$table->size = array ('50%');
	$table->class = 'databox';
	$table->cellspacing = 0;
	$table->data = array();
	
	$table->head = array();
	$table->head[0] = '<b>'.__('Timestamp').'</b>';
	$table->style[0] = 'border: 1px solid black;padding: 4px';
	
	$j = 0;
	$source_index = array ();
	$source_count = 0;
	
	if (isset ($data['sources'])) {
		foreach ($data['sources'] as $source => $null) {
			$table->style[$j+1] = 'border: 1px solid black;padding: 4px';
			$table->align[$j+1] = "right";
			$table->head[$j+1] = $source;
			$source_index[$j] = $source;
			$source_count++;
			$j++;
		}
	} else {
		$table->style[1] = 'border: 1px solid black;padding: 4px';
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
	$table->data[1][0] = '<b>'.__('Total megabytes').'</b>';
	$table->data[1][1] = format_numeric ((int)($data['totalbytes'] / 1048576));
	$table->data[2][0] = '<b>'.__('Total packets').'</b>';
	$table->data[2][1] = format_numeric ($data['totalpackets']);
	$table->data[3][0] = '<b>'.__('Average bits per second'). '</b>';
	$table->data[3][1] = format_numeric ($data['avgbps']);
	$table->data[4][0] = '<b>'.__('Average packets per second').'</b>';
	$table->data[4][1] = format_numeric ($data['avgpps']);
	$table->data[5][0] = '<b>'.__('Average bytes per packet').'</b>';
	$table->data[5][1] = format_numeric ($data['avgbpp']);
	
	return html_print_table ($table, true);
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
 * @param string unique_id A unique number that is used to generate a cache file.
 * @param string aggregate Aggregate field.
 * @param int max Maximum number of aggregates.
 * @param string unit Unit to show.
 *
 * @return An array with netflow stats.
 *
 */
function netflow_get_data ($start_date, $end_date, $interval_length, $filter, $unique_id, $aggregate, $max, $unit, $connection_name = '') {
	global $nfdump_date_format;
	global $config;

	// Requesting remote data
	if (defined ('METACONSOLE') && $connection_name != '') {
		$data = metaconsole_call_remote_api ($connection_name, 'netflow_get_data', "$start_date|$end_date|$interval_length|" . base64_encode(json_encode($filter)) . "|$unique_id|$aggregate|$max|$unit");
		return json_decode ($data, true);
	}
	
	// Get the command to call nfdump
	$command = netflow_get_command ($filter);

	// Suppress the header line and the statistics at the bottom and configure piped output
	$command .= ' -q -o csv';

	// If there is aggregation calculate the top n
	if ($aggregate != 'none') {
		$values['data'] = array ();
		$values['sources'] = array ();
		$agg_command = $command . " -s $aggregate/bytes -n $max -t ".date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
		exec($agg_command, $string);

		// Reamove the first line
		$string[0] = '';
		
		foreach($string as $line){
			if ($line=='') {
				continue;
			}
			$val = explode(',',$line);

			if ($aggregate == 'proto') {
				$values['sources'][$val[3]] = 1;
			} else {
				$values['sources'][$val[4]] = 1;
			}	
		}
	}
	else {
		$values = array ();
	}
	
	// Load cache
	if ($unique_id != '') {
		$cache_file = $config['attachment_store'] . '/netflow_' . $unique_id . '.cache';
		$last_timestamp = netflow_load_cache ($values, $cache_file, $start_date, $end_date, $interval_length, $aggregate);
	} else {
		$last_timestamp = $start_date;
	}
	
	if ($last_timestamp < $end_date) {
		
		$last_timestamp++;
		
		// Execute nfdump and save its output in a temporary file
		$temp_file = tempnam($config['attachment_store'], 'netflow_tmp_');
		$command .= ' -t '.date($nfdump_date_format, $last_timestamp).'-'.date($nfdump_date_format, $end_date);
		exec("$command > $temp_file");

		// Parse data file
		// We must parse from $start_date to avoid creating new intervals!
		netflow_parse_file ($start_date, $end_date, $interval_length, $temp_file, $values, $aggregate, $unit);

		unlink ($temp_file);
	}
	
	// Save cache
	if ($unique_id != '') {
		netflow_save_cache ($values, $cache_file);
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
		} else {
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
				$values[$i]['data'] = $val[9];
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
 * @param string unique_id A unique number that is used to generate a cache file.
 *
 * @return An array with netflow stats.
 */
function netflow_get_summary ($start_date, $end_date, $filter, $unique_id, $connection_name = '') {
	global $nfdump_date_format;
	global $config;

	// Requesting remote data
	if (defined ('METACONSOLE') && $connection_name != '') {
		$data = metaconsole_call_remote_api ($connection_name, 'netflow_get_summary', "$start_date|$end_date|" . base64_encode(json_encode($filter)) . "|$unique_id");
		return json_decode ($data, true);
	}

	// Get the command to call nfdump
	$command = netflow_get_command ($filter);

	// Execute nfdump and save its output in a temporary file
	$temp_file = $config['attachment_store'] . '/netflow_' . $unique_id . '.tmp';
	$command .= " -o \"fmt: \" -t " .date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
	exec("$command > $temp_file");
	
	// Parse data file
	// We must parse from $start_date to avoid creating new intervals!
	$values = array ();
	netflow_parse_file ($start_date, $end_date, 0, $temp_file, $values);
	unlink ($temp_file);
	
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
	$command = $config['netflow_nfdump'] . ' -N -Otstart';

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
		$val_ipdst = explode(',', $filter['ip_dst']);
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
		$val_ipsrc = explode(',', $filter['ip_src']);
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
	if ($filter['dst_port'] != 0) {
		if ($filter_args == '') {
			$filter_args .= ' "(';
		}
		else {
			$filter_args .= ' and (';
		}
		$val_dstport = explode(',', $filter['dst_port']);
		for ($i = 0; $i < count ($val_dstport); $i++) {
			if ($i > 0) {
				$filter_args .= ' or ';
			}
			$filter_args .= 'dst port '.$val_dstport[$i];
		}
		$filter_args .=  ')';
	}
	if ($filter['src_port'] != 0) {
		if ($filter_args == '') {
			$filter_args .= ' "(';
		}
		else {
			$filter_args .= ' and (';
		}
		$val_srcport = explode(',', $filter['src_port']);
		for ($i = 0; $i < count ($val_srcport); $i++) {
			if ($i > 0) {
				$filter_args .= ' or ';
			}
			$filter_args .= 'src port '.$val_srcport[$i];
		}
		$filter_args .=  ')';
	}
	if ($filter_args != '') {
		$filter_args .= '"';
	}
	
	return $filter_args;
}


/**
 * Parses netflow data from the given file.
 *
 * @param string start_date Period start date.
 * @param string end_date Period end date.
 * @param string file File that contains netflow data.
 * @param array values Array where netflow data will be placed.
 * @param string aggregate Aggregate field.
 * @param string unit Unit to show.
 * @param string interval_length Interval length in seconds (num_intervals * interval_length = start_date - end_date).
 *
 * @return Timestamp of the last data read.
 *
 */
function netflow_parse_file ($start_date, $end_date, $interval_length, $file, &$values, $aggregate = '', $unit = '') {
	global $config;
	
	// Last timestamp read
	$last_timestamp = $start_date;
	
	// Open the data file
	$fh = @fopen ($file, "r");
	if ($fh === FALSE) {
		return $last_timestamp;
	}
	
	// Calculate the number of intervals
	if ($interval_length <= 0) {
		$num_intervals = $config['graph_res'] * 50;
		$period = $end_date - $start_date;
		$interval_length = (int) ($period / $num_intervals);
	} else {
		$period = $end_date - $start_date;
		$num_intervals = (int) ($period / $interval_length);
	}
	
	// Parse the summary and exit
	if ($aggregate == '' && $unit == '') {
		while ($line = fgets ($fh)) {
			$line = preg_replace('/\s+/','',$line);
			$idx = strpos ($line, 'Summary:');
			if ($idx === FALSE) {
				continue;
			}
			
			// Parse summary items
			$idx += strlen ('Summary:');
			$line = substr ($line, $idx);
			$summary = explode(',', $line);
			foreach ($summary as $item) {
				$val = explode (':', $item);
				if (! isset ($val[1])) {
					continue;
				}
				$values[$val[0]] = $val[1];
			}
		}

		fclose ($fh);
		return;
	}

	// Parse flow data
	$read_flag = 1;
	$flow = array ();
	for ($i = 0; $i < $num_intervals; $i++) {
		$timestamp = $start_date + ($interval_length * $i);
		
		if ($aggregate != 'none') {
			$interval_total = array ();
			$interval_count = array ();
		}
		else {
			$interval_total = 0;
			$interval_count = 0;
		}
		
		do {
			if ($read_flag == 1) {
				$read_flag = 0;
				$line = fgets($fh, 4096);
				if ($line === false) {
					$read_flag = 1;
					break;
				}
				
				$val = explode(',',$line);;
				if (! isset ($val[12])) {
					$read_flag = 1;
					break;
				}
				
				$flow['datetime'] = $val[0];
				
				switch ($aggregate) {
					case "proto":
						$flow['agg'] = $val[7];
						break;
					case "srcip":
						$flow['agg'] = $val[3];
						break;
					case "srcport":
						$flow['agg'] = $val[5];
						break;
					case "dstip":
						$flow['agg'] = $val[4];
						break;
					case "dstport":
						$flow['agg'] = $val[6];
						break;
				}
				
				$flow['data'] = $val[12];
				$flow['timestamp'] = strtotime ($flow['datetime']);
				$last_timestamp = $flow['timestamp'];
			}
			
			if ($flow['timestamp'] >= $timestamp && $flow['timestamp'] <= $timestamp + $interval_length) {
				$read_flag = 1;
				if ($aggregate != 'none') {
					if (isset ($values['sources'][$flow['agg']])) {
						if (! isset ($interval_total[$flow['agg']])) {
							$interval_total[$flow['agg']] = 0;
							$interval_count[$flow['agg']] = 0;
						}
						$interval_total[$flow['agg']] += $flow['data'];
						$interval_count[$flow['agg']] += 1;
					}
				}
				else {
					$interval_total += $flow['data'];
					$interval_count += 1;
				}
			}
		}
		while ($read_flag == 1);
		
		$no_data = 1;
		if ($aggregate != 'none') {
			foreach ($interval_total as $agg => $val) {
				
				// No data for this interval/aggregate
				if ($interval_count[$agg] == 0) {
					continue;
				}
				
				// Calculate interval data
				switch ($unit) {
					case 'megabytes': 
						$values['data'][$timestamp][$agg] = $interval_total[$agg] / 1048576;
						break;
					case 'megabytespersecond': 
						$values['data'][$timestamp][$agg] = $interval_total[$agg] / 1048576 / $interval_length;
						break;
					case 'kilobytes': 
						$values['data'][$timestamp][$agg] = $interval_total[$agg] / 1024;
						break;
					case 'kilobytespersecond': 
						$values['data'][$timestamp][$agg] = $interval_total[$agg] / 1024 / $interval_length;
						break;
					default:
						$values['data'][$timestamp][$agg] = $interval_total[$agg];
						break;
				}
				
				$no_data = 0;				
			}
		}
		else {
			
			// No data for this interval
			if ($interval_count == 0) {
				continue;
			}
			
			// Calculate interval data
			switch ($unit) {
				case 'megabytes': 
					$values[$timestamp]['data'] = $interval_total / 1048576;
					break;
				case 'megabytespersecond': 
					$values[$timestamp]['data'] = $interval_total / 1048576 / $interval_length;
					break;
				case 'kilobytes': 
					$values[$timestamp]['data'] = $interval_total / 1024;
					break;
				case 'kilobytespersecond': 
					$values[$timestamp]['data'] = $interval_total / 1024 / $interval_length;
					break;
				default:
					$values[$timestamp]['data'] = $interval_total;
					break;
			}
						
			$no_data = 0;			
		}
	}
	
	fclose ($fh);

	// No data!
	if ($no_data == 1) {
		$values = array ();
	}

	return $last_timestamp;
}

/**
 * Save data to the specified cache file.
 *
 * @param array data Data array.
 * @param string cache_file Cache file name.
 *
 */
function netflow_save_cache ($data, $cache_file) {
	
	//@file_put_contents ($cache_file, serialize ($data));
	
	return;
}

/**
 * Load data from the specified cache file.
 *
 * @param string data Array were cache data will be stored.
 * @param string cache_file Cache file name.
 * @param string start_date Period start date.
 * @param string end_date Period end date.
 * @param string interval_length Interval length in seconds (num_intervals * interval_length = start_date - end_date).
 *
 * @return Timestamp of the last data read from cache.
 *
 */
function netflow_load_cache (&$data, $cache_file, $start_date, $end_date, $interval_length, $aggregate) {
	global $config;
	
	// Open cache file
	$cache_data = @file_get_contents ($cache_file);
	$cache_data = @unserialize ($cache_data);
	
	// Calculate the number of intervals
	if ($interval_length <= 0) {
		$num_intervals = $config['graph_res'] * 50;
		$period = $end_date - $start_date;
		$interval_length = (int) ($period / $num_intervals);
	} else {
		$period = $end_date - $start_date;
		$num_intervals = (int) ($period / $interval_length);
	}
	$last_timestamp = $start_date;
	
	// Initializa chart data
	if ($aggregate == 'none') {
		if ($cache_data === FALSE) {
			$cache_data = array ();
		}
		for ($i = 0; $i < $num_intervals; $i++) {
			$timestamp = $start_date + ($interval_length * $i);
			$interval_count = 0;
			$interval_total = 0;
			foreach ($cache_data as $cache_timestamp => $cache_value) {
				if ($cache_timestamp < $timestamp + $interval_length) {
					if ($cache_timestamp >= $timestamp) {
						$interval_count++;
						$interval_total += $cache_value['data'];
						$last_timestamp = $cache_timestamp;
					}
					unset ($cache_data[$cache_timestamp]);
				}
				else {
					break;
				}
			}
			
			if ($interval_count > 0) {
				$data[$timestamp]['data'] = (int) ($interval_total / $interval_count);
			}
			else {
				$data[$timestamp]['data'] = 0;
			}
		}
	}
	else {
		for ($i = 0; $i < $num_intervals; $i++) {
			$timestamp = $start_date + ($interval_length * $i);
			$interval_count = array ();
			$interval_total = array ();
			
			foreach ($data['sources'] as $source => $null) {
				$data['data'][$timestamp][$source] = 0;
			}
		}
	}
	
	return $last_timestamp;
}

/**
 * Get the types of netflow charts.
 *
 * @return Array of types.
 *
 */
function netflow_get_chart_types () {
	
	return array(
		__('Area graph'),
		__('Pie graph'),
		__('Data table'),
		__('Statistics table'),
		__('Summary table'));
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
 * @param string unique_id A unique number that is used to generate a cache file.
 * @param string output Output format. Only HTML and XML are supported.
 *
 * @return The netflow report in the appropriate format.
 */
function netflow_draw_item ($start_date, $end_date, $interval_length, $type, $filter, $max_aggregates, $unique_id, $connection_name = '', $output = 'HTML', $only_image = false) {
	$aggregate = $filter['aggregate'];
	$unit = $filter['output'];
	$interval = $end_date - $start_date;

	// Process item
	switch ($type) {
		case '0':
		case 'netflow_area':
			$data = netflow_get_data ($start_date, $end_date, $interval_length, $filter, $unique_id, $aggregate, $max_aggregates, $unit, $connection_name);
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
					$html .= graph_netflow_aggregate_area ($data, $interval, 660, 320, netflow_format_unit ($unit));
					return $html;
				} else if ($output == 'PDF') {
					$html = "<b>" . __('Unit') . ":</b> " . netflow_format_unit ($unit);
					$html .= "&nbsp;<b>" . __('Aggregate') . ":</b> " . netflow_format_aggregate ($aggregate);
					if ($interval_length != 0) {
						$html .= "&nbsp;<b>" . _('Resolution') . ":</b> $interval_length " . __('seconds');
					}
					$html .= graph_netflow_aggregate_area ($data, $interval, 660, 320, netflow_format_unit ($unit), 2, true);
					return $html;
				} else if ($output == 'XML') {
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
				} else if ($output == 'PDF') {
					$html = "<b>" . __('Unit') . ":</b> " . netflow_format_unit ($unit);
					if ($interval_length != 0) {
						$html .= "&nbsp;<b>" . _('Resolution') . ":</b> $interval_length " . __('seconds');
					}
					$html .= graph_netflow_total_area ($data, $interval, 660, 320, netflow_format_unit ($unit), 2, true);
					return $html;
				} else if ($output == 'XML') {
					$xml = "<unit>$unit</unit>\n";
					$xml .= "<resolution>$interval_length</resolution>\n";
					$xml .= netflow_total_area_xml ($data);
					return $xml;
				}
			}
			break;
		case '1':
		case 'netflow_pie':
			$data = netflow_get_stats ($start_date, $end_date, $filter, $aggregate, $max_aggregates, $unit, $connection_name);
			if (empty ($data)) {
				break;
			}
			if ($output == 'HTML') {
				$html = "<b>" . __('Unit') . ":</b> " . netflow_format_unit ($unit);
				$html .= "&nbsp;<b>" . __('Aggregate') . ":</b> " . netflow_format_aggregate ($aggregate);
				$html .= graph_netflow_aggregate_pie ($data, netflow_format_aggregate ($aggregate));
				return $html;
			} else if ($output == 'PDF') {
				$html = "<b>" . __('Unit') . ":</b> " . netflow_format_unit ($unit);
				$html .= "&nbsp;<b>" . __('Aggregate') . ":</b> $aggregate";
				$html .= graph_netflow_aggregate_pie ($data, netflow_format_aggregate ($aggregate), 2, true);
				return $html;
			} else if ($output == 'XML') {
				$xml = "<unit>$unit</unit>\n";
				$xml .= "<aggregate>$aggregate</aggregate>\n";
				$xml .= netflow_aggregate_pie_xml ($data);
				return $xml;
			}
			break;
		case '2':
		case 'netflow_data':
			$data = netflow_get_data ($start_date, $end_date, $interval_length, $filter, $unique_id, $aggregate, $max_aggregates, $unit, $connection_name);
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
			} else if ($output == 'XML') {
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
			$data = netflow_get_stats ($start_date, $end_date, $filter, $aggregate, $max_aggregates, $unit, $connection_name);
			if (empty ($data)) {
				break;
			}
			if ($output == 'HTML' || $output == 'PDF') {
				$html = netflow_stat_table ($data, $start_date, $end_date, $aggregate, $unit);
				return $html;
			} else if ($output == 'XML') {
				return netflow_stat_xml ($data);
			}
			break;
		case '4':
		case 'netflow_summary':
		
			$data = netflow_get_summary ($start_date, $end_date, $filter, $unique_id, $connection_name);
			if (empty ($data)) {
				break;
			}
			if ($output == 'HTML' || $output == 'PDF') {
				return netflow_summary_table ($data);
			} else if ($output == 'XML') {
				return netflow_summary_xml ($data);
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
	$report_contents = db_get_all_rows_sql ("SELECT * FROM tnetflow_report_content WHERE id_report='" . $report['id_report'] . "' ORDER BY `order`");
	foreach ($report_contents as $content) {

        // Get item filters
        $filter = db_get_row_sql("SELECT * FROM tnetflow_filter WHERE id_sg = '" . io_safe_input ($content['id_filter']) . "'", false, true);
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

		// Build a unique id for the cache
		$unique_id = netflow_generate_unique_id ($content["type"], $filter, $start_date);

		echo netflow_draw_item ($start_date, $end_date, $interval_length, $content['show_graph'], $filter, $content['max'], $unique_id, $report['server_name'], 'XML');

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
 * Generate a unique id for the netflow cache.
 *
 * @param string Chart type.
 * @param string Netflow filter.
 * @param string Chart start date.
 */
function netflow_generate_unique_id ($type, $filter, $start_date) {

	switch ($type) {
		case '0':
		case 'netflow_area':
		case '2':
		case 'netflow_data':
			$source = 'data';
			break;
		case '1':
		case 'netflow_pie':
		case '3':
		case 'netflow_statistics':
			$source = 'stats';
			break;
		case '4':
		case 'netflow_summary':		
			$source = 'summary';
			break;
		default:
			return '';
	}
	
	return $source . '_' . $filter['id_sg'] . '_' . $start_date;
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
