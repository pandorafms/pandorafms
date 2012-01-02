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
 * Selects all netflow filters (array (id_sg => id_sg)) or filters filtered
 *
 * @param mixed Array with filter conditions to retrieve filters or false.  
 *
 * @return array List of all filters
 */
/*
function netflow_get_filters_id ($filter = false) {
	if ($filter === false) { 
		$filters = db_get_all_rows_in_table ("tnetflow_filter", "id_sg");
	}
	else {
		$filters = db_get_all_rows_filter ("tnetflow_filter", $filter);
	}
	$return = array ();
	if ($filters === false) {
		return $return;
	}
	foreach ($filters as $filter) {
		$return[$filter["id_sg"]] = $filter["id_sg"];
	}
	return $return;
}
*/

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
 * Selects all netflow filters (array (id_name => id_name)) or filters filtered
 *
 * @param mixed Array with filter conditions to retrieve filters or false.  
 *
 * @return array List of all filters
 */
function netflow_get_options ($filter = false) {
	if ($filter === false) { 
		$filters = db_get_all_rows_in_table ("tnetflow_options", "id_name");
	}
	else {
		$filters = db_get_all_rows_filter ("tnetflow_options", $filter);
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
 * Get options.
 *
 * @param int filter id to be fetched.
 * @param array Extra filter.
 * @param array Fields to be fetched.
 *
 * @return array A netflow filter matching id and filter.
 */
function netflow_options_get_options ($id_option, $filter = false, $fields = false) {
	if (empty ($id_option))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_option'] = (int) $id_option;
	
	return db_get_row_filter ('tnetflow_options', $filter, $fields);
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

//sort array multidimensional

function orderMultiDimensionalArray ($toOrderArray, $field, $inverse = false) {
     $position = array(); 
     $newRow = array(); 
     foreach ($toOrderArray as $key => $row) { 
             $position[$key]  = $row[$field]; 
             $newRow[$key] = $row; 
     } 
     if ($inverse) { 
         arsort($position); 
     } 
     else { 
         asort($position); 
     } 
     $returnArray = array(); 
     foreach ($position as $key => $pos) {      
       $returnArray[] = $newRow[$key]; 
     } 
     return $returnArray; 
}

function netflow_show_total_period($data, $date_limit, $date_time){
	$values = array();
	$table->width = '50%';
	$table->class = 'databox';
	$table->data = array();
	$title = "Desde $date_limit hasta $date_time";
	$j = 0;
	$x = 1;
	
	echo"<h4>Suma por periodo</h4>";
	$table->data[0][0] = '<b>'.__('Rango').'</b>';
	$table->data[0][1] = '<b>'.$title.'</b>';
	
	while (isset ($data[$j])) {
		$agg = $data[$j]['agg'];
		if (!isset($values[$agg])){
			$values[$agg] = $data[$j]['data'];
			$table->data[$x][0] = $agg;
			$table->data[$x][1] = $data[$j]['data'];
		} else {
			$values[$agg] += $data[$j]['data'];
			$table->data[$x][0] = $agg;
			$table->data[$x][1] = $data[$j]['data'];
		}
		$j++;
		$x++;
	}
html_print_table($table);
}

function netflow_show_table_values($data, $date_limit, $date_time){
	$values = array();
	$table->width = '50%';
	$table->class = 'databox';
	$table->data = array();
	
	$j = 0;
	$x = 1;
	$y = 1;
	
	echo"<h4>Tabla de valores</h4>";
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

?>
