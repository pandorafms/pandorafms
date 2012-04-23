<?PHP

// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es
// Copyright (c) 2007-2011 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

if (!class_exists("FusionCharts")) {
	require_once ("FusionCharts/FusionCharts_Gen.php");
}



///////////////////////////////
///////////////////////////////
///////////////////////////////
function fs_stacked_graph($chart_data, $width, $height, $color, $legend, $long_index) {
	global $config;
	
	$graph_type = "StackedArea2D";
	
	$chart = new FusionCharts($graph_type, $width, $height);
	
	$pixels_between_xdata = 25;
	$max_xdata_display = round($width / $pixels_between_xdata);
	$ndata = count($chart_data);
	if($max_xdata_display > $ndata) {
		$xdata_display = $ndata;
	}
	else {
		$xdata_display = $max_xdata_display;
	}
	
	$step = round($ndata/$xdata_display);
	
	
	if(is_array(reset($chart_data))) {
	 	$data2 = array();
	 	$count = 0;
		foreach($chart_data as $i =>$values) {
			$count++;
			$show_name = '0';
			if (($count % $step) == 0) {
				$show_name = '1';
			}
			
			if (isset($long_index[$i])) {
				$chart->addCategory($i, //'');
						'hoverText=' . $long_index[$i] .  
						';showName=' . $show_name);
			}
			else {
				$chart->addCategory($i, 'showName=' . $show_name);
			}
			
			$c = 0;
			foreach($values as $i2 => $value) {
				$data2[$i2][$i] = $value;
				$c++;
			}
		}
		$data = $data2;
	 }
	 else {
		$data = array($chart_data);
	 }
	
	 $a = 0;
	 
	$empty = 1;
	foreach ($data as $i => $value) {	
		
		$legend_text = '';
		if (isset($legend[$i])) {
			$legend_text = $legend[$i];
		}
		
		$alpha = '';
		$areaBorderColor = '';
		$color = '';
		$showAreaBorder = 1; //0 old default
		if (isset($color[$i])) {
			if (!isset($color[$i]['border'])) {
				$showAreaBorder = 1;
			}
			
			if (isset($color[$i]['alpha'])) {
				$alpha = 'alpha=' . $color[$i]['alpha'] . ';';
			}
			
			if (isset($color[$i]['border'])) {
				$areaBorderColor = 'areaBorderColor=' . $color[$i]['border'] . ';';
			}
			
			if (isset($color[$i]['color'])) {
				$color = 'color=#' . $color[$i]['color'];
			}
		}
		
		$chart->addDataSet($legend_text, $alpha . 
			'showAreaBorder=' . $showAreaBorder . ';' .
			$areaBorderColor .
			$color);
			
		$count = 0;
		$step = 10;
		$num_vlines = 0;
		
		foreach ($value as $i2 => $v) {
			if ($count++ % $step == 0) {
				$show_name = '1';
				$num_vlines++;
			}
			else {
				$show_name = '0';
			}
			
			$empty = 0;
			
			if ($a < 3) {
				$a++;
//			$chart->addCategory(date('G:i', $i2), //'');
//				'hoverText=' . date (html_entity_decode ($config['date_format'], ENT_QUOTES, "UTF-8"), $i2) .  
//				';showName=' . $show_name);
			}

			//Add data
			$chart->addChartData($v);
		}
	}
	
	$chart->setChartParams('animation=0;numVDivLines=' . $num_vlines . 
		';showShadow=0;showAlternateVGridColor=1;showNames=1;rotateNames=1;' . 
		'lineThickness=0.1;anchorRadius=0.5;showValues=0;baseFontSize=9;showLimits=0;' .
		'showAreaBorder=1;areaBorderThickness=0.1;areaBorderColor=000000' . ($empty == 1 ? ';yAxisMinValue=0;yAxisMaxValue=1' : ''));
	
	$random_number = uniqid();
	
	$div_id = 'chart_div_' . $random_number;
	$chart_id = 'chart_' . $random_number;
		
	$output = '<div id="' . $div_id. '" style="z-index:1;"></div>';
	//$output .= '<script language="JavaScript" src="include/graphs/FusionCharts/FusionCharts.js"></script>';
	$output .= '<script type="text/javascript">
			<!--
			function pie_' . $chart_id . ' () {
				var myChart = new FusionCharts("include/graphs/FusionCharts/FCF_'.$graph_type.'.swf", "' . $chart_id . '", "' . $width. '", "' . $height. '", "0", "1");
				myChart.setDataXML("' . addslashes($chart->getXML ()) . '");
				myChart.addParam("WMode", "Transparent");
				myChart.render("' . $div_id . '");
			}
					pie_' . $chart_id . ' ();
			-->
		</script>';
	
	return $output;	
}

function fs_line_graph($chart_data, $width, $height, $color, $legend, $long_index) {
	global $config;
	
	$graph_type = "MSLine";
	
	$chart = new FusionCharts($graph_type, $width, $height);
	
	
	$pixels_between_xdata = 25;
	$max_xdata_display = round($width / $pixels_between_xdata);
	$ndata = count($chart_data);
	if($max_xdata_display > $ndata) {
		$xdata_display = $ndata;
	}
	else {
		$xdata_display = $max_xdata_display;
	}
	
	$step = round($ndata/$xdata_display);
	
	
	if(is_array(reset($chart_data))) {
	 	$data2 = array();
	 	$count = 0;
		foreach($chart_data as $i =>$values) {
			$count++;
			$show_name = '0';
			if (($count % $step) == 0) {
				$show_name = '1';
			}
			
			if (isset($long_index[$i])) {
				$chart->addCategory($i, //'');
						'hoverText=' . $long_index[$i] .  
						';showName=' . $show_name);
			}
			else {
				$chart->addCategory($i, 'showName=' . $show_name);
			}
			
			$c = 0;
			foreach($values as $i2 => $value) {
				$data2[$i2][$i] = $value;
				$c++;
			}
		}
		$data = $data2;
	 }
	 else {
		$data = array($chart_data);
	 }
	
	 $a = 0;
	 
	$empty = 1;
	foreach ($data as $i => $value) {	
		
		$legend_text = '';
		if (isset($legend[$i])) {
			$legend_text = $legend[$i];
		}
		
		$alpha = '';
		$areaBorderColor = '';
		$color = '';
		$showAreaBorder = 1; //0 old default
		if (isset($color[$i])) {
			if (!isset($color[$i]['border'])) {
				$showAreaBorder = 1;
			}
			
			if (isset($color[$i]['alpha'])) {
				$alpha = 'alpha=' . $color[$i]['alpha'] . ';';
			}
			
			if (isset($color[$i]['border'])) {
				$areaBorderColor = 'areaBorderColor=' . $color[$i]['border'] . ';';
			}
			
			if (isset($color[$i]['color'])) {
				$color = 'color=#' . $color[$i]['color'];
			}
		}
		
		$chart->addDataSet($legend_text, $alpha . 
			'showAreaBorder=' . $showAreaBorder . ';' .
			$areaBorderColor .
			$color);
			
		$count = 0;
		$step = 10;
		$num_vlines = 0;
		
		foreach ($value as $i2 => $v) {
			if ($count++ % $step == 0) {
				$show_name = '1';
				$num_vlines++;
			}
			else {
				$show_name = '0';
			}
			
			$empty = 0;
			
			if ($a < 3) {
				$a++;
//			$chart->addCategory(date('G:i', $i2), //'');
//				'hoverText=' . date (html_entity_decode ($config['date_format'], ENT_QUOTES, "UTF-8"), $i2) .  
//				';showName=' . $show_name);
			}

			//Add data
			$chart->addChartData($v);
		}
	}
	
	$chart->setChartParams('animation=0;numVDivLines=' . $num_vlines . 
		';showShadow=0;showAlternateVGridColor=1;showNames=1;rotateNames=1;' . 
		'lineThickness=3;anchorRadius=0.5;showValues=0;baseFontSize=9;showLimits=0;' .
		'showAreaBorder=1;areaBorderThickness=0.1;areaBorderColor=000000' . ($empty == 1 ? ';yAxisMinValue=0;yAxisMaxValue=1' : ''));
	
	$random_number = uniqid();
	
	$div_id = 'chart_div_' . $random_number;
	$chart_id = 'chart_' . $random_number;
	
	
	$output = '<div id="' . $div_id. '" style="z-index:1;"></div>';
	//$output .= '<script language="JavaScript" src="include/graphs/FusionCharts/FusionCharts.js"></script>';
	$output .= '<script type="text/javascript">
			<!--
			function pie_' . $chart_id . ' () {
				var myChart = new FusionCharts("include/graphs/FusionCharts/FCF_'.$graph_type.'.swf", "' . $chart_id . '", "' . $width. '", "' . $height. '", "0", "1");
				myChart.setDataXML("' . addslashes($chart->getXML ()) . '");
				myChart.addParam("WMode", "Transparent");
				myChart.render("' . $div_id . '");
			}
					pie_' . $chart_id . ' ();
			-->
		</script>';
	
	return $output;	
}


function fs_area_graph($chart_data, $width, $height, $color, $legend, $long_index, $homeurl = '', $unit) {
	global $config;

	$graph_type = "MSArea2D"; //MSLine is possible also
	
	$chart = new FusionCharts($graph_type, $width, $height);

	$pixels_between_xdata = 25;
	$max_xdata_display = round($width / $pixels_between_xdata);
	$ndata = count($chart_data);
	if($max_xdata_display > $ndata) {
		$xdata_display = $ndata;
	}
	else {
		$xdata_display = $max_xdata_display;
	}
	
	$step = round($ndata/$xdata_display);
	
	if(is_array(reset($chart_data))) {
	 	$data2 = array();
	 	$count = 0;
		foreach($chart_data as $i =>$values) {
			$count++;
			$show_name = '0';
			if (($count % $step) == 0) {
				$show_name = '1';
			}
			
			if (isset($long_index[$i])) {
				$chart->addCategory($i, //'');
						'hoverText=' . $long_index[$i] .  
						';showName=' . $show_name);
			}
			else {
				$chart->addCategory($i, 'showName=' . $show_name);
			}

			$c = 0;
			foreach($values as $i2 => $value) {
				$data2[$i2][$i] = $value;
				$c++;
			}
		}
		$data = $data2;
	 }
	 else {
		$data = array($chart_data);
	 }
	
	 $a = 0;
	 	
	$empty = 1;
	foreach ($data as $i => $value) {	
		$legend_text = '';
		if (isset($legend[$i])) {
			$legend_text = $legend[$i];
		}
		
		$alpha = '';
		$areaBorderColor = '';
		$color = '';
		$showAreaBorder = 1; //0 old default
		
		if (isset($colors[$i])) {
			if (!isset($colors[$i]['border'])) {
				$showAreaBorder = 1;
			}
			
			if (isset($colors[$i]['alpha'])) {
				$alpha = 'alpha=' . $colors[$i]['alpha'] . ';';
			}
			
			if (isset($colors[$i]['border'])) {
				$areaBorderColor = 'areaBorderColor=' . $colors[$i]['border'] . ';';
			}
			
			if (isset($colors[$i]['color'])) {
				$color = 'color=#' . $colors[$i]['color'];
			}
		}
	
		$chart->addDataSet($legend_text, $alpha . 
			'showAreaBorder=' . $showAreaBorder . ';' .
			$areaBorderColor .
			$color);
			
		$count = 0;
		$step = 10;
		$num_vlines = 0;
		
		foreach ($value as $i2 => $v) {
			if ($count++ % $step == 0) {
				$show_name = '1';
				$num_vlines++;
			}
			else {
				$show_name = '0';
			}
			
			if ($v != 0) {
				$empty = 0;
			}
			
			if ($a < 3) {
				$a++;
//			$chart->addCategory(date('G:i', $i2), //'');
//				'hoverText=' . date (html_entity_decode ($config['date_format'], ENT_QUOTES, "UTF-8"), $i2) .  
//				';showName=' . $show_name);
			}

			//Add data
			$chart->addChartData($v);
		}
	}
	
	$chart->setChartParams('yAxisName=' . $unit . ';'.'animation=0;numVDivLines=' . $num_vlines . 
		';showShadow=0;showAlternateVGridColor=1;showNames=1;rotateNames=1;' . 
		'lineThickness=0.1;anchorRadius=0.5;showValues=0;baseFontSize=9;showLimits=0;' .
		'showAreaBorder=1;areaBorderThickness=0.1;areaBorderColor=000000' . ($empty == 1 ? ';yAxisMinValue=0;yAxisMaxValue=1' : ''));
	
	$random_number = uniqid();
	
	$div_id = 'chart_div_' . $random_number;
	$chart_id = 'chart_' . $random_number;
	
	
	$output = '<div id="' . $div_id. '" style="z-index:1;"></div>';
	//$output .= '<script language="JavaScript" src="include/graphs/FusionCharts/FusionCharts.js"></script>';
	$output .= '<script type="text/javascript">
			<!--
			function pie_' . $chart_id . ' () {
				var myChart = new FusionCharts("' . $homeurl . 'include/graphs/FusionCharts/FCF_'.$graph_type.'.swf", "' . $chart_id . '", "' . $width. '", "' . $height. '", "0", "1");
				myChart.setDataXML("' . addslashes($chart->getXML ()) . '");
				myChart.addParam("WMode", "Transparent");
				myChart.render("' . $div_id . '");
			}
					pie_' . $chart_id . ' ();
			-->
		</script>';

	return $output;	
}
///////////////////////////////
///////////////////////////////
///////////////////////////////


// Returns the number of seconds since the Epoch for a date in the format dd/mm/yyyy
function date_to_epoch ($date) {
	$date_array = explode ('/', $date);
	return mktime (0, 0, 0, $date_array [1], $date_array [0], $date_array [2]);
}

// Returns the code needed to display the chart
function get_chart_code ($chart, $width, $height, $swf) {
	$random_number = rand ();
	$div_id = 'chart_div_' . $random_number;
	$chart_id = 'chart_' . $random_number;
    $output = '<div id="' . $div_id. '"></div>'; 
    $output .= '<script type="text/javascript">
    			<!--
        			$(document).ready(function pie_' . $chart_id . ' () {
        				var myChart = new FusionCharts("' . $swf . '", "' . $chart_id . '", "' . $width. '", "' . $height. '", "0", "1");
        				myChart.setDataXML("' . addslashes($chart->getXML ()) . '");
        				myChart.addParam("WMode", "Transparent");
        				myChart.render("' . $div_id . '");
        			})
        		-->
    			</script>';
    return $output;
}

// Prints a 3D pie chart
function fs_3d_pie_chart ($data, $names, $width, $height, $background = "FFFFFF") {
	if ((sizeof ($data) != sizeof ($names)) OR (sizeof($data) == 0) ){
		return;
	}

	// Generate the XML
	$chart = new FusionCharts("Pie3D", $width, $height);
	$chart->setSWFPath("include/graphs/FusionCharts/");
  	$params="showNames=1;showValues=0;showPercentageValues=0;baseFontSize=9;bgColor=$background;bgAlpha=100;canvasBgAlpha=100;";
  	$chart->setChartParams($params);

	for ($i = 0; $i < sizeof ($data); $i++) {
		$chart->addChartData($data[$i], 'name=' . clean_flash_string($names[$i]));
	}

	// Return the code
	return get_chart_code ($chart, $width, $height, 'include/graphs/FusionCharts/FCF_Pie3D.swf');
}

// Prints a 2D pie chart
function fs_2d_pie_chart ($data, $names, $width, $height, $background = "FFFFFF") {
	if ((sizeof ($data) != sizeof ($names)) OR (sizeof($data) == 0) ){
		return;
	}

	// Generate the XML
	$chart = new FusionCharts("Pie3D", $width, $height);
	$chart->setSWFPath("include/graphs/FusionCharts/");
  	$params="showNames=1;showValues=0;showPercentageValues=0;baseFontSize=9;bgColor=$background;bgAlpha=100;canvasBgAlpha=100;";
  	$chart->setChartParams($params);

	for ($i = 0; $i < sizeof ($data); $i++) {
		$chart->addChartData($data[$i], 'name=' . clean_flash_string($names[$i]));
	}

	// Return the code
	return get_chart_code ($chart, $width, $height, 'include/graphs/FusionCharts/FCF_Pie2D.swf');
}

// Returns a 2D column chart
function fs_2d_column_chart ($data, $width, $height, $homeurl = '', $reduce_data_columns = false, $yaxisname = '') {
	if (sizeof ($data) == 0) {
		return;
	}

	// Generate the XML
	$chart = new FusionCharts('Column2D', $width, $height);

	$pixels_between_xdata = 25;
	$max_xdata_display = round($width / $pixels_between_xdata);
	$ndata = count($data);
	if($max_xdata_display > $ndata) {
		$xdata_display = $ndata;
	}
	else {
		$xdata_display = $max_xdata_display;
	}
	
	$step = round($ndata/$xdata_display);
	
	if(is_array(reset($data))) {
	 	$data2 = array();
	 	$count = 0;
		foreach($data as $i =>$values) {
			$count++;
			$show_name = '0';
			if (($count % $step) == 0) {
				$show_name = '1';
			}
			
			$chart->addCategory($i, //'');
					'hoverText=' . $i .  
					';showName=' . $show_name);
			
			$c = 0;
			$previous = false;
			foreach($values as $i2 => $value) {
				if ($reduce_data_columns) {
					if ($previous !== false) {
						if ($previous == $value) continue;
					}
				}
				$data2[$i2][$i] = $value;
				$c++;
				
				$previous = $value;
			}
		}
		$data = $data2;
	 }
	 else {
		$data = array($data);
	 }
	 
    $empty = 0;
    $num_vlines = 0;
    $count = 0;

	foreach ($data as $legend_value => $values) {

		foreach($values as $name => $value) {
			if (($count++ % $step) == 0) {
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
	}

    $chart->setChartParams('yAxisName=' . $yaxisname . ';decimalPrecision=0;showAlternateVGridColor=1; numVDivLines='.$num_vlines.';showNames=1;rotateNames=1;showValues=0;showPercentageValues=0;showLimits=0;baseFontSize=9;' 
. ($empty == 1 ? ';yAxisMinValue=0;yAxisMaxValue=1' : ''));

	// Return the code
	return get_chart_code ($chart, $width, $height, $homeurl . 'include/graphs/FusionCharts/FCF_Column2D.swf');
}

// Returns a BAR Horizontalchart
function fs_2d_hcolumn_chart ($data, $width, $height) {
	if (sizeof ($data) == 0) {
		return;
	}

	// Generate the XML
	$chart = new FusionCharts('Bar2D', $width, $height);

	$pixels_between_xdata = 25;
	$max_xdata_display = round($width / $pixels_between_xdata);
	$ndata = count($data);
	if($max_xdata_display > $ndata) {
		$xdata_display = $ndata;
	}
	else {
		$xdata_display = $max_xdata_display;
	}
	
	$step = round($ndata/$xdata_display);
	
	
	if(is_array(reset($data))) {
	 	$data2 = array();
	 	$count = 0;
		foreach($data as $i =>$values) {
			$count++;
			$show_name = '0';
			if (($count % $step) == 0) {
				$show_name = '1';
			}

			$chart->addCategory($i, //'');
					'hoverText=' . $i .  
					';showName=' . $show_name);
			
			$c = 0;
			foreach($values as $i2 => $value) {
				$data2[$i2][$i] = $value;
				$c++;
			}
		}
		$data = $data2;
	 }
	 else {
		$data = array($data);
	 }
	 
    $empty = 0;
    $num_vlines = 0;
    $count = 0;

	foreach ($data as $legend_value => $values) {

		foreach($values as $name => $value) {
			if (($count++ % $step) == 0) {
				$show_name = '1';
				$num_vlines++;
			} else {
				$show_name = '0';
			}
			if ($value > 0) {
				$empty = 0;
			}
			$chart->addChartData($value, 'name=' . clean_flash_string($name) . ';showName=' . $show_name/* . ';color=95BB04'*/);
		}
	}

  	$params='showNames=1;showValues=0;showPercentageValues=0;baseFontSize=9;rotateNames=1;chartLeftMargin=0;chartRightMargin=0;chartBottomMargin=0;chartTopMargin=0;showBarShadow=1;showLimits=1';

    $chart->setChartParams($params.';numVDivLines='.$num_vlines.($empty == 1 ? ';yAxisMinValue=0;yAxisMaxValue=1' : ''));

	// Return the code
	return get_chart_code ($chart, $width, $height, 'include/graphs/FusionCharts/FCF_Bar2D.swf');
}

// Returns a 3D column chart
function fs_3d_column_chart ($data, $width, $height) {
	if (sizeof ($data) == 0) {
		return;
	}

	// Generate the XML
	$chart = new FusionCharts('Column2D', $width, $height);


    $empty = 0;
    $num_vlines = 0;
    $count = 0;
    $step = 3;

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

    $chart->setChartParams('decimalPrecision=0;showAlternateVGridColor=1; numVDivLines='.$num_vlines.';showNames=1;rotateNames=1;showValues=0;showPercentageValues=0;showLimits=0;baseFontSize=9;' 
. ($empty == 1 ? ';yAxisMinValue=0;yAxisMaxValue=1' : ''));

	// Return the code
	return get_chart_code ($chart, $width, $height, 'include/graphs/FusionCharts/FCF_Column2D.swf');
}

// Prints a Gantt chart
function fs_gantt_chart ($title, $from, $to, $tasks, $milestones, $width, $height) {
	
	// Generate the XML
	$chart = new FusionCharts("Gantt", $width, $height, "1", "0");
	$chart->setSWFPath("include/graphs/FusionCharts/");
	$chart->setChartParams('dateFormat=dd/mm/yyyy;hoverCapBorderColor=2222ff;hoverCapBgColor=e1f5ff;ganttLineAlpha=80;canvasBorderColor=024455;canvasBorderThickness=0;gridBorderColor=2179b1;gridBorderAlpha=20;ganttWidthPercent=80');
	$chart->setGanttProcessesParams('headerText=' . __('Task') . ';fontColor=ffffff;fontSize=9;isBold=1;isAnimated=1;bgColor=2179b1;headerbgColor=2179b1;headerFontColor=ffffff;headerFontSize=12;align=left');
	$chart->setGanttTasksParams('');

	$start_date = explode ('/', $from);
	$start_day = $start_date[0];
	$start_month = $start_date[1];
	$start_year = $start_date[2];
	$end_date = explode ('/', $to);
	$end_day = $end_date[0];
	$end_month = $end_date[1];
	$end_year = $end_date[2];
	$time_span = date_to_epoch ($to) - date_to_epoch ($from);

	// Years
	$chart->addGanttCategorySet ('bgColor=2179b1;fontColor=ff0000');
	for ($i = $start_year; $i <= $end_year; $i++) {
		if ($i == $start_year) {
			$start = sprintf ('%02d/%02d/%04d', $start_day, $start_month, $start_year);
		} else {
			$start = sprintf ('%02d/%02d/%04d', 1, 1, $i);
		}
		if ($i == $end_year) {
			$end = sprintf ('%02d/%02d/%04d', $end_day, $end_month, $end_year);
		} else {
			$end = sprintf ('%02d/%02d/%04d', cal_days_in_month (CAL_GREGORIAN, 12, $i), 12, $i);
		}
		$chart->addGanttCategory ($i, ';start=' . $start . ';end=' . $end . ';align=center;fontColor=ffffff;isBold=1;fontSize=16');
	}

	// Months
	$chart->addGanttCategorySet ('bgColor=ffffff;fontColor=1288dd;fontSize=10');
	for ($i = $start_year ; $i <= $end_year; $i++) {
		for ($j = 1 ; $j <= 12; $j++) {
			if ($i == $start_year && $j < $start_month) {
				continue;
			} else if ($i == $end_year && $j > $end_month) {
				break;
			}
			if ($i == $start_year && $j == $start_month) {
				$start = sprintf ('%02d/%02d/%04d', $start_day, $start_month, $start_year);
			} else {
				$start = sprintf ('%02d/%02d/%04d', 1, $j, $i);
			}
			if ($i == $end_year && $j == $end_month) {
				$end = sprintf ('%02d/%02d/%04d', $end_day, $end_month, $end_year);
			} else {
				$end = sprintf ('%02d/%02d/%04d', cal_days_in_month (CAL_GREGORIAN, $j, $i), $j, $i);
			}
			$chart->addGanttCategory (date('F', mktime(0,0,0,$j,1)), ';start=' . $start . ';end=' . $end . ';align=center;isBold=1');
		}
	}

	// Days
	if ($time_span < 2592000) {
		$chart->addGanttCategorySet ();
		for ($i = $start_year ; $i <= $end_year; $i++) {
			for ($j = 1 ; $j <= 12; $j++) {
				if ($i == $start_year && $j < $start_month) {
					continue;
				} else if ($i == $end_year && $j > $end_month) {
					break;
				}
				$num_days = cal_days_in_month (CAL_GREGORIAN, $j, $i);
				for ($k = 1 ; $k <= $num_days; $k++) {
					if ($i == $start_year && $j == $start_month && $k < $start_day) {
						continue;
					} else if ($i == $end_year && $j == $end_month && $k > $end_day) {
						break;
					}
					$start = sprintf ('%02d/%02d/%04d', $k, $j, $i);
					$end = sprintf ('%02d/%02d/%04d', $k, $j, $i);
					$chart->addGanttCategory ($k, ';start=' . $start . ';end=' . $end . ';fontSize=8;isBold=0');
				}
			}
		}
	}
	// Weeks
	else if ($time_span < 10368000) {
		$chart->addGanttCategorySet ();
		for ($i = $start_year ; $i <= $end_year; $i++) {
			for ($j = 1 ; $j <= 12; $j++) {
				if ($i == $start_year && $j < $start_month) {
					continue;
				} else if ($i == $end_year && $j > $end_month) {
					break;
				}
				$num_days = cal_days_in_month (CAL_GREGORIAN, $j, $i);
				for ($k = 1, $l = 1; $k <= $num_days; $k += 8, $l++) {
					if ($i == $start_year && $j == $start_month && $k + 7 < $start_day) {
						continue;
					}
					if ($i == $end_year && $j == $end_month && $k > $end_day) {
						break;
					}

					if ($i == $start_year && $j == $start_month && $k < $start_day) {
						$start = sprintf ('%02d/%02d/%04d', $start_day, $j, $i);
					} else {
						$start = sprintf ('%02d/%02d/%04d', $k, $j, $i);
					}
					if ($i == $end_year && $j == $end_month && $k + 7 > $end_day) {
						$end = sprintf ('%02d/%02d/%04d', $end_day, $j, $i);
					} else if ($k + 7 > $num_days) {
						$end = sprintf ('%02d/%02d/%04d', $num_days, $j, $i);
					} else {
						$end = sprintf ('%02d/%02d/%04d', $k + 7, $j, $i);
					}

					$chart->addGanttCategory (__('Week') . " $l", ';start=' . $start . ';end=' . $end . ';fontSize=8;isBold=0');
				}
			}
		}
	}

	// Tasks
	foreach ($tasks as $task) {
		$chart->addGanttProcess (clean_flash_string($task['name']), 'id=' . $task['id'] . ';link=' . urlencode($task['link']));

		$chart->addGanttTask (__('Planned'), 'start=' . $task['start'] . ';end=' . $task['end'] . ';id=' . $task['id'] . ';processId=' . $task['id'] . ';color=4b3cff;height=5;topPadding=10;animation=0');

		if ($task['real_start'] !== false && $task['real_end']) {
			$chart->addGanttTask (__('Actual'), 'start=' . $task['real_start'] . ';end=' . $task['real_end'] . ';processId=' . $task['id'] . ';color=ff3c4b;alpha=100;topPadding=15;height=5');
		}
		if ($task['completion'] != 0) {
			$task_span = date_to_epoch ($task['end']) - date_to_epoch ($task['start']);
			$end = date ('d/m/Y', date_to_epoch ($task['start']) + $task_span * $task['completion'] / 100.0);
			$chart->addGanttTask (__('Completion')." (".$task['completion'].")", 'start=' . $task['start'] . ';end=' . $end . ';processId=' . $task['id'] . ';color=32cd32;alpha=100;topPadding=20;height=5');
		}
		if ($task['parent'] != 0) {
			$chart->addGanttConnector ($task['parent'], $task['id'], 'color=2179b1;thickness=2;fromTaskConnectStart=1');
		}
	}

	// Milestones
	if ($milestones !== '') {
		$chart->addGanttProcess (__('Milestones'), 'id=0');
		foreach ($milestones as $milestone) {
			$chart->addGanttTask (clean_flash_string($milestone['name']), 'start=' . $milestone['date'] . ';end=' . $milestone['date'] . ';id=ms-' . $milestone['id'] . ';processId=0;color=ffffff;alpha=0;height=60;topPadding=0;animation=0');
			$chart->addGanttMilestone ('ms-' . $milestone['id'], 'date=' . $milestone['date'] . ';radius=8;color=efbb07;shape=star;numSides=3;borderThickness=1');
		}
	}

	// Today
	$chart->addTrendLine ('start=' . date ('d/m/Y') . ';displayValue='. __('Today') . ';color=666666;isTrendZone=1;alpha=20');

	// Return the code
	return get_chart_code ($chart, $width, $height, 'include/graphs/FusionCharts/FCF_Gantt.swf');
}

?>
