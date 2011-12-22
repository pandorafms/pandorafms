<?php
// Copyright (c) 2011-2011 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function serialize_in_temp($array = array(), $serial_id = null, $ttl = 1) {
	$json = json_encode($array);
	
	if ($serial_id === null) {
		$serial_id = uniqid();
	}

	$file_path = sys_get_temp_dir()."/pandora_serialize_".$serial_id."__1__".$ttl;

	if (file_put_contents($file_path, $json) === false) {
		return false;
	}

	return $serial_id;
}

function unserialize_in_temp($serial_id = null, $delete = true, $ttl = 1) {
	if ($serial_id === null) {
		return false;
	}
	
	$volume = -1;
	
	for($i = 1 ; $i <= $ttl ; $i++) {
		$file_path = sys_get_temp_dir()."/pandora_serialize_".$serial_id."__".$i."__".$ttl;
		
		if(file_exists($file_path)) {
			$volume = $i;
			break;
		}
	}

	$content = file_get_contents($file_path);

	if ($content === false) {
		return false;
	}
	
	$array = json_decode($content, true);
	
	if ($delete) {
		if($volume == $ttl) {
			unlink($file_path);
		}
		else {
			$next_volume = $volume + 1;
			rename($file_path, sys_get_temp_dir()."/pandora_serialize_".$serial_id."__".$next_volume."__".$ttl);
		}
	}

	return $array;
}

function delete_unserialize_in_temp($serial_id = null) {
	if ($serial_id === null) {
		return false;
	}
	
	$file_path = sys_get_temp_dir()."/pandora_serialize_".$serial_id;
		
	return unlink($file_path);
}

function reverse_data($array) {
	$array2 = array();
	foreach($array as $index => $values) {
		foreach($values as $index2 => $value) {
				$array2[$index2][$index] = $value;
		}
	}
	
	return $array2;
}

function stack_data(&$chart_data, &$legend = null, &$color = null) {
	foreach ($chart_data as $val_x => $graphs) {
		$prev_val = 0;
		$key = 1000;
		foreach ($graphs as $graph => $val_y) {
			$chart_data[$val_x][$graph] += $prev_val;
			$prev_val = $chart_data[$val_x][$graph];
			$temp_data[$val_x][$key] = $chart_data[$val_x][$graph];
			if (isset($color)) {
				$temp_color[$key] = $color[$graph];
			}
			if (isset($legend)) {
				$temp_legend[$key] = $legend[$graph];
			}
			$key--;
		}
		ksort($temp_data[$val_x]);
	}
	
	$chart_data = $temp_data;
	if (isset($legend)) {
		$legend = $temp_legend;
		ksort($legend);
	}
	if (isset($color)) {
		$color = $temp_color;
		ksort($color);
	}
}

function graph_get_max_index($legend_values) {
	$max_chars = 0;
	foreach ($legend_values as $string_legend) {
		if (empty($string_legend)) continue;

		$string_legend = explode("\n",$string_legend);

		foreach($string_legend as $st_lg) {
			$len = strlen($st_lg);
			if ($len > $max_chars) {
				$max_chars = $len; 
			}
		}
	}
	
	return $max_chars;
}
?>
