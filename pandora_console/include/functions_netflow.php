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

// Date format for nfdump
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
	if (empty ($id_sg))
		return false;
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
	return $a['data']>$b['data'];
}

/**
 * Sort netflow data according to the 'data' column.
 *
 * @param array netflow_data Netflow data array.
 *
 */
function sort_netflow_data ($netflow_data) {
     usort($netflow_data, "compare_flows");
}

function netflow_show_total_period($data, $start_date, $end_date, $show){
	global $nfdump_date_format;

	$start_date = date ($nfdump_date_format, $start_date);
	$end_date = date ($nfdump_date_format, $end_date);
	$values = array();
	$table->width = '50%';
	$table->class = 'databox';
	$table->data = array();
	$title = "Desde $start_date hasta $end_date";
	$j = 0;
	$x = 1;

	echo"<h4>Suma por periodo ($show)</h4>";
	$table->data[0][0] = '<b>'.__('Rango').'</b>';
	$table->data[0][1] = '<b>'.$title.'</b>';
	
	while (isset ($data[$j])) {
		$agg = $data[$j]['agg'];
		if (!isset($values[$agg])){
			$values[$agg] = $data[$j]['data'];
			$table->data[$x][0] = $agg;
			$table->data[$x][1] = format_numeric ($data[$j]['data']) .' '.$show;
		} else {
			$values[$agg] += $data[$j]['data'];
			$table->data[$x][0] = $agg;
			$table->data[$x][1] = format_numeric ($data[$j]['data']) .' '.$show;
		}
		$j++;
		$x++;
	}
html_print_table($table);
}

/**
 * Show a table with netflow statistics.
 *
 * @param array data Statistic data.
 * @param string start_date Start date.
 * @param string end_date End date.
 * @param string unit Unit to display.
 *
 * @return The statistics table.
 */
function netflow_stat_table ($data, $start_date, $end_date, $unit){
	global $nfdump_date_format;

	$start_date = date ($nfdump_date_format, $start_date);
	$end_date = date ($nfdump_date_format, $end_date);

	$values = array();
	$table->width = '50%';
	$table->class = 'databox';
	$table->data = array();
	
	$j = 0;
	$x = 1;
	$y = 1;
	
	echo"<h4>Tabla de valores ($unit)</h4>";
	$table->data[0][0] = '<b>'.__('Rango').'</b>';

	$coordx = array();
	$coordy = array();
	
	while (isset ($data[$j])) {
		$date = $data[$j]['date'];
		$time = $data[$j]['time'];
		$agg = $data[$j]['agg'];
		
		if (!isset($values[$agg])){
			$values['data'] = $data[$j]['data'];			
		} else {
			$values['data'] += $data[$j]['data'];
		}
		
		$values['agg'] = $agg;
		$values['datetime'] = $date.'.'.$time;
		
		if(isset($coordy[$agg])) {
			$cy = $coordy[$agg];
		}
		else {
			$cy = $y;
			$coordy[$agg] = $cy;
			$y++;
		}
		
		if(isset($coordx[$date.'.'.$time])) {
			$cx = $coordx[$date.'.'.$time];
		}
		else {
			$cx = $x;
			$coordx[$date.'.'.$time] = $cx;
			$x++;
		}
		
		$table->data[0][$cy] = $agg;
		$table->data[$cx][0] = $date.'.'.$time;
		$table->data[$cx][$cy] = $values['data'];

		$j++;
	}
	//si la coordenada no tiene valor, se rellena con 0
	foreach($coordx as $x) {
		foreach($coordy as $y) {
			if(!isset($table->data[$x][$y])) {
				$table->data[$x][$y] = 0;
			}
		}
	}
	//ordenar los indices
	foreach($coordx as $x) {
		ksort($table->data[$x]);
	}	

	html_print_table($table);
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
 * @param string command Command used to retrieve netflow data.
 * @param string aggregate Aggregate field.
 * @param int max Maximum number of aggregates.
 * @param string unit Unit to show.
 *
 * @return An array with netflow stats.
 *
 */
function netflow_get_data ($start_date, $end_date, $command, $aggregate, $max, $unit){
	global $nfdump_date_format;
	global $config;

	// If there is aggregation calculate the top n
	$sources = array ();
	if ($aggregate != 'none') {
		$agg_command = $command . " -s $aggregate -n $max -t ".date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
		exec($agg_command, $string);
		foreach($string as $line){
			if ($line=='') {
				continue;
			}
			$line = preg_replace('/\(\s*\S+\)/','',$line);
			$line = preg_replace('/\s+/',' ',$line);
			$val = explode(' ',$line);
			$sources[$val[4]] = 1;
		}
	}

	// Execute nfdump and save its output in a temporary file
	$command .= ' -t '.date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
	$temp_file = $config["attachment_store"] . "/netflow_" . rand (0, getrandmax()) . ".data";
	exec("$command > $temp_file");

	// Open the temporary file
	$fh = fopen ($temp_file, "r");
	if ($fh === FALSE) {
		return;
	}

	// Calculate the number of intervals
	$num_intervals = $config['graph_res'] * 50;
	$period = $end_date - $start_date;
	$interval_length = (int) ($period / $num_intervals);

	// Parse flow data
	$read_flag = 1;
	$values = array ();
	$flow = array ();
	for ($i = 0; $i < $num_intervals; $i++) {
		$timestamp = $start_date + ($interval_length * $i);
		
		if ($aggregate != 'none') {
			$interval_total = array ();
			$interval_count = array ();
		} else {
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
				
				$line = preg_replace('/\s+/',' ',$line);
				$val = explode(' ',$line);
				if (! isset ($val[6])) {
					$read_flag = 1;
					break;
				}
				
				$flow['date'] = $val[0];
				$flow['time'] = $val[1];
				
				switch ($aggregate){
					case "proto":
						$flow['agg'] = $val[3];
						break;
					case "srcip":
						$val2 = explode(':', $val[4]);
						$flow['agg'] = $val2[0];
						break;
					case "srcport":
						$val2 = explode(':', $val[4]);
						$flow['agg'] = $val2[1];
						break;
					case "dstip":
						$val2 = explode(':', $val[6]);
						$flow['agg'] = $val2[0];
						break;
					case "dstport":
						$val2 = explode(':', $val[6]);
						$flow['agg'] = $val2[1];
						break;
				}
				
				switch ($unit) {
					case "packets":
						$flow['data'] = $val[7];
						break;
					case "bytes":
						$flow['data'] = $val[8];
						break;
					case "flows":
						$flow['data'] = $val[9];
						break;
				}
				$flow['timestamp'] = strtotime ($flow['date'] . " " . $flow['time']);
			}
			if ($flow['timestamp'] >= $timestamp && $flow['timestamp'] <= $timestamp + $interval_length) {
				$read_flag = 1;
				if ($aggregate != 'none') {
					if (isset ($sources[$flow['agg']])) {
						if (! isset ($interval_total[$flow['agg']])) {
							$interval_total[$flow['agg']] = 0;
							$interval_count[$flow['agg']] = 0;
						}
						$interval_total[$flow['agg']] += $flow['data'];
						$interval_count[$flow['agg']] += 1;
					}
				} else {
					$interval_total += $flow['data'];
					$interval_count += 1;
				}
			}
		} while ($read_flag == 1);
		
		if ($aggregate != 'none') {
			foreach ($interval_total as $agg => $val) {
				if ($interval_count[$agg] != 0) {
					$values['data'][$timestamp][$agg] = (int) ($interval_total[$agg] / $interval_count[$agg]);
					$values['sources'][$agg] = 1;
				}
			}
		} else {
			if ($interval_count == 0) {
				$values[$timestamp]['data'] = 0;
			} else {
				$values[$timestamp]['data'] = (int) ($interval_total / $interval_count);
			}
		}
	}

	fclose ($fh);
	unlink ($temp_file);

	return $values;
}

/**
 * Returns netflow stats for the given period in an array.
 *
 * @param string start_date Period start date.
 * @param string end_date Period end date.
 * @param string command Command used to retrieve netflow data.
 * @param string aggregate Aggregate field.
 * @param int max Maximum number of aggregates.
 * @param string unit Unit to show.
 *
 * @return An array with netflow stats.
 */
function netflow_get_stats ($start_date, $end_date, $command, $aggregate, $max, $unit){
	global $nfdump_date_format;

	$command .= ' -s ' . $aggregate . ' -n ' . $max . ' -t '.date($nfdump_date_format, $start_date).'-'.date($nfdump_date_format, $end_date);
	exec($command, $string);

	if(! is_array($string)){
		return array ();
	}
	
	$i = 0;
	$values = array();
	foreach($string as $line){
		if ($line == '') {
			break;
		}
		$line = preg_replace('/\(\s*\S+\)/','',$line);
		$line = preg_replace('/\s+/',' ',$line);
		$val = explode(' ',$line);

		$values[$i]['date'] = $val[0];
		$values[$i]['time'] = $val[1];
		
		//create field to sort array
		$date = $val[0];
		$time = $val[1];
		$end_date = strtotime ($date." ".$time);
		$values[$i]['datetime'] = $end_date;
		$values[$i]['agg'] = $val[4];
		
		switch ($unit){
			case "packets":
				$values[$i]['data'] = $val[6];
				break;
			case "bps":
				$values[$i]['data'] = $val[9];
				break;
			case "bpp":
				$values[$i]['data'] = $val[10];
				break;
			case "bytes":
			default:
				$values[$i]['data'] = $val[7];
				break;
		}	
		$i++;
	}
	
	sort_netflow_data ($values);
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
	$command = 'nfdump -q -N -m';
	
	// Netflow data path
	if (isset($config['netflow_path']) && $config['netflow_path'] != '') {
		$command .= ' -R '.$config['netflow_path'];
	}
	
	// Filter options
	$filter_args = '';
	if ($filter['ip_dst'] != ''){
		$filter_args .= ' "(';
		$val_ipdst = explode(',', $filter['ip_dst']);
		for($i = 0; $i < count ($val_ipdst); $i++){
			if ($i > 0) {
				$filter_args .= ' or ';
			}
			
			if (netflow_is_net ($val_ipdst[$i]) == 0) {
				$filter_args .= 'dst ip '.$val_ipdst[$i];
			} else {
				$filter_args .= 'dst net '.$val_ipdst[$i];
			}
		}
		$filter_args .=  ')';
	}
	if ($filter['ip_src'] != ''){
		if ($filter_args == '') {
			$filter_args .= ' "(';
		} else {
			$filter_args .= ' and (';
		}
		$val_ipsrc = explode(',', $filter['ip_src']);
		for($i = 0; $i < count ($val_ipsrc); $i++){
			if ($i > 0) {
				$filter_args .= ' or ';
			}
			
			if (netflow_is_net ($val_ipsrc[$i]) == 0) {
				$filter_args .= 'src ip '.$val_ipsrc[$i];
			} else {
				$filter_args .= 'src net '.$val_ipsrc[$i];
			}
		}
		$filter_args .=  ')';
	}
	if ($filter['dst_port'] != 0) {
		if ($filter_args == '') {
			$filter_args .= ' "(';
		} else {
			$filter_args .= ' and (';
		}
		$val_dstport = explode(',', $filter['dst_port']);
		for($i = 0; $i < count ($val_dstport); $i++){
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
		} else {
			$filter_args .= ' and (';
		}
		$val_srcport = explode(',', $filter['src_port']);
		for($i = 0; $i < count ($val_srcport); $i++){
			if ($i > 0) {
				$filter_args .= ' or ';
			}
			$filter_args .= 'src port '.$val_srcport[$i];
		}
		$filter_args .=  ')';
	}
	if ($filter_args != '') {
		$filter_args .=  '"';
		$command .= $filter_args;
	}

	return $command;
}

?>
