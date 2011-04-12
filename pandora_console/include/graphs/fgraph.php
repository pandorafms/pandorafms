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


// If is called from index
if(file_exists('include/functions.php')) {
	include_once('include/functions.php');
	include_once('include/graphs/functions_fsgraph.php');
	include_once('include/graphs/functions_utils.php');
} // If is called through url
else if(file_exists('../functions.php')) {
	include_once('../functions.php');
	include_once('../functions_html.php');
	include_once('functions_fsgraph.php');
	include_once('functions_gd.php');
	include_once('functions_utils.php');
}

$graph_type = get_parameter('graph_type', '');

switch($graph_type) {
	case 'histogram': 
				$width = get_parameter('width');
				$height = get_parameter('height');
				$font = get_parameter('font');
				$data = json_decode(safe_output(get_parameter('data')), true);

				$max = get_parameter('max');
				$title = get_parameter('title');
				$mode = get_parameter ('mode', 1);
				gd_histogram ($width, $height, $mode, $data, $max, $font, $title);
				break;
	case 'progressbar':
				$width = get_parameter('width');
				$height = get_parameter('height');
				$progress = get_parameter('progress');
				
				$out_of_lim_str = get_parameter('out_of_lim_str', false);
				$out_of_lim_image = get_parameter('out_of_lim_image', false);

				$font = get_parameter('font');
				$title = get_parameter('title');
				gd_progress_bar ($width, $height, $progress, $title, $font, $out_of_lim_str, $out_of_lim_image);
				break;
}

function vbar_graph($flash_chart, $chart_data, $width, $height, $color = array(), $legend = array(), $xaxisname = "", $yaxisname = "") {
	if($flash_chart) {
		echo fs_2d_column_chart ($chart_data, $width, $height);
	}
	else {
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		$graph['xaxisname'] = $xaxisname;
		$graph['yaxisname'] = $yaxisname;

		$id_graph = serialize_in_temp($graph);
	
		echo "<img src='include/graphs/functions_pchart.php?graph_type=vbar&id_graph=".$id_graph."'>";
	}
}

function threshold_graph($flash_chart, $chart_data, $width, $height) {
	if($flash_chart) {
		echo fs_2d_column_chart ($chart_data, $width, $height);
	}
	else {
		echo "<img src='include/graphs/functions_pchart.php?graph_type=threshold&data=".json_encode($chart_data)."&width=".$width."&height=".$height."'>";
	}
}

function area_graph($flash_chart, $chart_data, $width, $height, $color, $legend, $long_index, $no_data_image, $xaxisname = "", $yaxisname = "") {
	if (empty($chart_data)) {
		return '<img src="' . $no_data_image . '" />';
	}
	
	if($flash_chart) {
		return fs_area_graph($chart_data, $width, $height, $color, $legend, $long_index);
	}
	else {
		$id_graph = uniqid();
		
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		$graph['xaxisname'] = $xaxisname;
		$graph['yaxisname'] = $yaxisname;
				
		serialize_in_temp($graph, $id_graph);

		return "<img src='include/graphs/functions_pchart.php?graph_type=area&id_graph=" . $id_graph . "'>";
	}	
}

function stacked_area_graph($flash_chart, $chart_data, $width, $height, $color, $legend, $long_index, $no_data_image) {
	
	if (empty($chart_data)) {
		return '<img src="' . $no_data_image . '" />';
	}
	
	if($flash_chart) {
		return fs_stacked_graph($chart_data, $width, $height, $color, $legend, $long_index);
	}
	else {
		$id_graph = uniqid();
		
		//Stack the data
		stack_data($chart_data, $legend, $color);
		
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		
		serialize_in_temp($graph, $id_graph);
		
		return "<img src='http://127.0.0.1/pandora_console/include/graphs/functions_pchart.php?graph_type=stacked_area&id_graph=" . $id_graph . "' />";
	}	
}

function stacked_line_graph($flash_chart, $chart_data, $width, $height, $color, $legend, $long_index, $no_data_image) {
	if (empty($chart_data)) {
		return '<img src="' . $no_data_image . '" />';
	}
	
	//Stack the data
	stack_data($chart_data, $legend, $color);
	
	if($flash_chart) {
		return fs_line_graph($chart_data, $width, $height, $color, $legend, $long_index);
	}
	else {
		$id_graph = uniqid();
		
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		
		serialize_in_temp($graph, $id_graph);
		
		return "<img src='http://127.0.0.1/pandora_console/include/graphs/functions_pchart.php?graph_type=line&id_graph=" . $id_graph . "' />";
	}
}

function line_graph($flash_chart, $chart_data, $width, $height, $color, $legend, $long_index, $no_data_image) {
	if (empty($chart_data)) {
		return '<img src="' . $no_data_image . '" />';
	}
	
	if($flash_chart) {
		return fs_line_graph($chart_data, $width, $height, $color, $legend, $long_index);
	}
	else {
		$id_graph = uniqid();
		
		
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		
		serialize_in_temp($graph, $id_graph);
		
		return "<img src='http://127.0.0.1/pandora_console/include/graphs/functions_pchart.php?graph_type=line&id_graph=" . $id_graph . "' />";
	}	
}

function hbar_graph($flash_chart, $chart_data, $width, $height, $color = array(), $legend = array(), $xaxisname = "", $yaxisname = "") {
	if($flash_chart) {
		echo fs_hbar_chart (array_values($chart_data), array_keys($chart_data), $width, $height);
	}
	else {
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		$graph['xaxisname'] = $xaxisname;
		$graph['yaxisname'] = $yaxisname;

		$id_graph = serialize_in_temp($graph);
	
		echo "<img src='include/graphs/functions_pchart.php?graph_type=hbar&id_graph=".$id_graph."'>";
	}
}

function pie3d_graph($flash_chart, $chart_data, $width, $height, $others_str = "other") {
	return pie_graph('3d', $flash_chart, $chart_data, $width, $height, $others_str);
}

function pie2d_graph($flash_chart, $chart_data, $width, $height, $others_str = "other") {
	return pie_graph('2d', $flash_chart, $chart_data, $width, $height, $others_str);
}

function pie_graph($graph_type, $flash_chart, $chart_data, $width, $height, $others_str) {
	// This library allows only 9 colors
	$max_values = 9;

	if(count($chart_data) > $max_values) {
		$chart_data_trunc = array();
		$n = 1;
		foreach($chart_data as $key => $value) {
			if($n < $max_values) {
				$chart_data_trunc[$key] = $value;
			}
			else {
				$chart_data_trunc[$others_str] += $value;
			}
			$n++;
		}
		$chart_data = $chart_data_trunc;
	}
	
	if($flash_chart) {
		switch($graph_type) {
			case "2d":
					return fs_2d_pie_chart (array_values($chart_data), array_keys($chart_data), $width, $height);
				break;
			case "3d":				
					return fs_3d_pie_chart (array_values($chart_data), array_keys($chart_data), $width, $height);
				break;
		}
	}
	else {
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;

		$id_graph = serialize_in_temp($graph);
	
		switch($graph_type) {
			case "2d":
					return "<img src='include/graphs/functions_pchart.php?graph_type=pie2d&id_graph=".$id_graph."'>";
				break;
			case "3d":				
					return "<img src='include/graphs/functions_pchart.php?graph_type=pie3d&id_graph=".$id_graph."'>";
				break;
		}
	}
}

function gantt_graph($project_name, $from, $to, $tasks, $milestones, $width, $height) {
	return fs_gantt_chart ($project_name, $from, $to, $tasks, $milestones, $width, $height);
}

function include_flash_chart_script() {
	echo '<script language="JavaScript" src="include/graphs/FusionCharts/FusionCharts.js"></script>';
}

?>
