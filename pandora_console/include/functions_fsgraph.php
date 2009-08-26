<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Graphs
 */

/**
 * Include the FusionCharts class
 */
require_once ('FusionCharts/FusionCharts_Gen.php');

// Returns the code needed to display the chart
function get_chart_code ($chart, $width, $height, $swf) {
	$random_number = rand ();
	$div_id = 'chart_div_' . $random_number;
	$chart_id = 'chart_' . $random_number;
    $output = '<div id="' . $div_id. '"></div>';
    $output .= '<script type="text/javascript">
    			<!--
        			$(document).ready(function pie_' . $chart_id . ' () {
        				var myChart = new FusionCharts("' . $config['homeurl'] . $swf . '", "' . $chart_id . '", "' . $width. '", "' . $height. '", "0", "1");
        				myChart.setDataXML("' . addslashes($chart->getXML ()) . '");
						myChart.addParam("WMode", "Transparent");
        				myChart.render("' . $div_id . '");
        			})
        		-->
    			</script>';
    return $output;
}

// Returns a 3D bar chart
function fs_3d_bar_chart ($data, $width, $height) {
	global $config;

	if (sizeof ($data) == 0) {
		return fs_error_image ();
	}

	// Generate the XML
	$chart = new FusionCharts('Column3D', $width, $height);

	$empty = 1;
	foreach ($data as $name => $value) {
		if ($value > 0) {
			$empty = 0;
		}
		$chart->addChartData($value, 'name=' . clean_flash_string($name) . ';color=95BB04');
	}

  	$chart->setChartParams('showNames=1;rotateNames=1;showValues=0;showPercentageValues=0;showLimits=0;baseFontSize=9;' . ($empty == 1 ? ';yAxisMinValue=0;yAxisMaxValue=1' : ''));

	// Return the code
	return get_chart_code ($chart, $width, $height, 'include/FusionCharts/FCF_Column3D.swf');
}

// Returns a 3D pie chart
function fs_3d_pie_chart ($data, $width, $height) {
	global $config;

	if (sizeof ($data) == 0) {
		return fs_error_image ();
	}

	// Generate the XML
	$chart = new FusionCharts('Pie3D', $width, $height);
  	$chart->setChartParams('showNames=1;showValues=0;showPercentageValues=0;baseFontSize=9');

	$empty = 1;
	foreach ($data as $name => $value) {
		if ($value > 0) {
			$empty = 0;
		}
		$chart->addChartData($value, 'name=' . clean_flash_string($name));
	}

	// Chart is not empty, but all elements are 0
	if ($empty == 1) {
		return fs_error_image ();
	}

	// Return the code
	return get_chart_code ($chart, $width, $height, 'include/FusionCharts/FCF_Pie3D.swf');
}

// Returns a 2D area chart
function fs_2d_area_chart ($data, $width, $height, $step = 1, $params = '') {
	global $config;

	if (sizeof ($data) == 0) {
		return fs_error_image ();
	}

	// Generate the XML
	$chart = new FusionCharts('Area2D', $width, $height);

	$count = 0;
	$num_vlines = 0;
	$empty = 1;
	foreach ($data as $name => $value) {
		if ($count++ % $step == 0) {
			$show_name = '1';
			$num_vlines++;
		} else {
			$show_name = '0';
		}
		if ($value > 0) {
			$empty = 0;
		}
		$chart->addChartData($value, 'name=' . clean_flash_string($name) . ';showName=' . $show_name . ';color=95BB04');
	}

  	$chart->setChartParams('numVDivLines=' . $num_vlines . ';showAlternateVGridColor=1;showNames=1;rotateNames=1;showValues=0;baseFontSize=9;showLimits=0;showAreaBorder=1;areaBorderThickness=1;areaBorderColor=000000' . ($empty == 1 ? ';yAxisMinValue=0;yAxisMaxValue=1' : '') . $params);

	// Return the code
	return get_chart_code ($chart, $width, $height, 'include/FusionCharts/FCF_Area2D.swf');
}

// Returns a Pandora FMS module chart
function fs_module_chart ($data, $width, $height, $avg_only = 1, $step = 10, $time_format = 'G:i') {
	global $config;

	// Generate the XML
	$chart = new FusionCharts('MSArea2D', $width, $height);
	$num_vlines = 0;
	$count = 0;

	// Create categories
	foreach ($data as $value) {
		if ($count++ % $step == 0) {
			$show_name = '1';
			$num_vlines++;
		} else {
			$show_name = '0';
		}
		$chart->addCategory(date($time_format, $value['timestamp_bottom']), 'hoverText=' . date ($config['date_format'], $value['timestamp_bottom']) . ';showName=' . $show_name);
	}

	// Max chart
	if ($avg_only == 0) {
		$chart->addDataSet('Max', 'color=' . $config['graph_color3']);
		foreach ($data as $value) {
			$chart->addChartData($value['max']);
		}
	}

	// Avg chart
	$empty = 1;
	$chart->addDataSet('Avg', 'color=' . $config['graph_color2']);
	foreach ($data as $value) {
		if ($value['sum'] > 0) {
			$empty = 0;
		}
		$chart->addChartData($value['sum']);
	}

	// Min chart
	if ($avg_only == 0) {
		$chart->addDataSet('Min', 'color=' . $config['graph_color1']);
		foreach ($data as $value) {
			$chart->addChartData($value['min']);
		}
	}

  	$chart->setChartParams('animation=0;numVDivLines=' . $num_vlines . ';showAlternateVGridColor=1;showNames=1;rotateNames=1;showValues=0;baseFontSize=9;showLimits=0;showAreaBorder=1;areaBorderThickness=1;areaBorderColor=000000' . ($empty == 1 ? ';yAxisMinValue=0;yAxisMaxValue=1' : ''));

	$random_number = rand ();
	$div_id = 'chart_div_' . $random_number;
	$chart_id = 'chart_' . $random_number;
    $output = '<div id="' . $div_id. '" style="z-index:1;"></div>'; 
	$output .= '<script language="JavaScript" src="' . $config['homeurl'] . '/include/FusionCharts/FusionCharts.js"></script>';
    $output .= '<script type="text/javascript">
    			<!--
        			function pie_' . $chart_id . ' () {
        				var myChart = new FusionCharts("' . $config['homeurl'] . '/include/FusionCharts/FCF_MSArea2D.swf", "' . $chart_id . '", "' . $width. '", "' . $height. '", "0", "1");
        				myChart.setDataXML("' . addslashes($chart->getXML ()) . '");
						myChart.addParam("WMode", "Transparent");
        				myChart.render("' . $div_id . '");
        			}
					pie_' . $chart_id . ' ();
        		-->
    			</script>';
    return $output;
}

// Returns a Pandora FMS combined chart
function fs_combined_chart ($data, $categories, $sets, $width, $height, $type = 1, $step = 10, $time_format = 'G:i') {
	global $config;

	if (sizeof ($data) == 0) {
		return fs_error_image ();
	}

	// Generate the XML
	switch ($type) {
		case 0: $chart_type = 'MSArea2D';
				break;
		case 1: $chart_type = 'StackedArea2D';
				break;
		case 2: $chart_type = 'MSLine';
				break;
		default: $chart_type = 'StackedArea2D';
	}
	
	$chart = new FusionCharts($chart_type, $width, $height);

	// Create categories
	$count = 0;
	$num_vlines = 0;
	foreach ($categories as $category) {
		if ($count++ % $step == 0) {
			$show_name = '1';
			$num_vlines++;
		} else {
			$show_name = '0';
		}

		$chart->addCategory(date($time_format, $category['timestamp_bottom']), 'hoverText=' . date ($config['date_format'], $category['timestamp_bottom']) . ';showName=' . $show_name);
	}

	// Stack charts
	$empty = 1;	
	for ($i = 0; $i < sizeof ($data); $i++) {
		$chart->addDataSet ($sets[$i]);
		foreach ($data[$i] as $value) {
			if ($value > 0) {
				$empty = 0;
			}
			$chart->addChartData($value);
		}
	}

  	$chart->setChartParams('animation=0;numVDivLines=' . $num_vlines . ';showAlternateVGridColor=1;showNames=1;rotateNames=1;showValues=0;baseFontSize=9;showLimits=0;showAreaBorder=1;areaBorderThickness=1;areaBorderColor=000000' . ($empty == 1 ? ';yAxisMinValue=0;yAxisMaxValue=1' : ''));

	// Return the code
	return get_chart_code ($chart, $width, $height, 'include/FusionCharts/FCF_' . $chart_type . '.swf');
}

// Returns a Pandora FMS agent event chart
function fs_agent_event_chart ($data, $width, $height, $step = 1) {
	global $config;

	if (sizeof ($data) == 0) {
		return fs_error_image ();
	}

	// Generate the XML
	$chart = new FusionCharts('Area2D', $width, $height);

	$count = 0;
	$num_vlines = 0;
	foreach ($data as $name => $value) {
		if ($count++ % $step == 0) {
			$show_name = '1';
			$num_vlines++;
		} else {
			$show_name = '0';
		}
		$chart->addChartData(1, 'name=' . clean_flash_string($name) . ';showName=' . $show_name . ';color=' . $value);
	}

  	$chart->setChartParams('numDivLines=0;numVDivLines=0;showNames=1;rotateNames=0;showValues=0;baseFontSize=9;showLimits=0;showAreaBorder=0;areaBorderThickness=1;canvasBgColor=9ABD18');

	// Return the code
	return get_chart_code ($chart, $width, $height, 'include/FusionCharts/FCF_Area2D.swf');
}

// Clean FLASH string strips non-valid characters for flashchart
function clean_flash_string ($string) {
	$string = html_entity_decode ($string, ENT_QUOTES, "UTF-8");
	$string = str_replace('&', '', $string);
	$string = str_replace(' ', '', $string);
	$string = str_replace ('"', '', $string);
	return substr ($string, 0, 20);
}

// Prints an error image
function fs_error_image () {
	global $config;

	return '<img border="0" src="' . $config['homeurl'] . '/images/image_problem.png" />';
}

?>
