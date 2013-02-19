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

//JQuery 1.6.1 library addition

global $config;


function include_javascript_dependencies_flot_graph($return = false) {
	global $config;
	
	static $is_include_javascript = false;
	
	if (!$is_include_javascript) {
		$is_include_javascript = true;
		
		$metaconsole_hack = '';
		if (defined('METACONSOLE')) {
			$metaconsole_hack = '../../';
		}
		
		// NOTE: jquery.flot.threshold is not te original file. Is patched to allow multiple thresholds and filled area
		$output = '
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack  . '/include/graphs/flot/jquery.flot.pie.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.crosshair.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.stack.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.selection.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.resize.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.threshold.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/jquery.flot.symbol.min.js') .'"></script>
			<script language="javascript" type="text/javascript" src="'.
				ui_get_full_url($metaconsole_hack . '/include/graphs/flot/pandora.flot.js') .'"></script>
		';
		$output .= "
		<script type='text/javascript'>
		function pieHover(event, pos, obj) 
		{
			if (!obj)
				return;
			percent = parseFloat(obj.series.percent).toFixed(2);
			$('#hover').html('<span style=\'font-weight: bold; color: '+obj.series.color+'\'>'+obj.series.label+' ('+percent+'%)</span>');
			$('.legendLabel').each(function() {
				if($(this).html() == obj.series.label) {
					$(this).css('font-weight','bold');
				}
				else {
					$(this).css('font-weight','');
				}
			});
		}
		
		function pieClick(event, pos, obj) 
		{
			if (!obj)
				return;
			percent = parseFloat(obj.series.percent).toFixed(2);
			alert(''+obj.series.label+': '+percent+'%');
		}
		</script>
		"
		;
		
		if (!$return)
			echo $output;
		
		return $output;
	}
}

///////////////////////////////
////////// AREA GRAPHS ////////
///////////////////////////////
function flot_area_stacked_graph($chart_data, $width, $height, $color, $legend, $long_index, $homeurl = '', $unit = '', $water_mark = '', $serie_types = array(), $chart_extra_data = array(), $yellow_threshold = 0, $red_threshold = 0, $adapt_key= '', $force_integer = false, $series_suffix_str = '') {
	global $config;
	
	return flot_area_graph($chart_data, $width, $height, $color, $legend, $long_index, $homeurl, $unit, 'area_stacked', $water_mark, $serie_types, $chart_extra_data, $yellow_threshold, $red_threshold, $adapt_key, $force_integer, $series_suffix_str);
}

function flot_area_simple_graph($chart_data, $width, $height, $color, $legend, $long_index, $homeurl = '', $unit = '', $water_mark = '', $serie_types = array(), $chart_extra_data = array(), $yellow_threshold = 0, $red_threshold = 0, $adapt_key= '', $force_integer = false, $series_suffix_str = '') {
	global $config;
	
	return flot_area_graph($chart_data, $width, $height, $color, $legend, $long_index, $homeurl, $unit, 'area_simple', $water_mark, $serie_types, $chart_extra_data, $yellow_threshold, $red_threshold, $adapt_key, $force_integer, $series_suffix_str);
}

function flot_line_stacked_graph($chart_data, $width, $height, $color, $legend, $long_index, $homeurl = '', $unit = '', $water_mark = '', $serie_types = array(), $chart_extra_data = array(), $yellow_threshold = 0, $red_threshold = 0, $adapt_key= '', $force_integer = false, $series_suffix_str = '') {
	global $config;
	
	return flot_area_graph($chart_data, $width, $height, $color, $legend, $long_index, $homeurl, $unit, 'line_stacked', $water_mark, $serie_types, $chart_extra_data, $yellow_threshold, $red_threshold, $adapt_key, $force_integer, $series_suffix_str);
}

function flot_line_simple_graph($chart_data, $width, $height, $color, $legend, $long_index, $homeurl = '', $unit = '', $water_mark = '', $serie_types = array(), $chart_extra_data = array(), $yellow_threshold = 0, $red_threshold = 0, $adapt_key= '', $force_integer = false, $series_suffix_str = '') {
	global $config;
	
	return flot_area_graph($chart_data, $width, $height, $color, $legend, $long_index, $homeurl, $unit, 'line_simple', $water_mark, $serie_types, $chart_extra_data, $yellow_threshold, $red_threshold, $adapt_key, $force_integer, $series_suffix_str);
}

function flot_area_graph($chart_data, $width, $height, $color, $legend, $long_index, $homeurl, $unit, $type, $water_mark, $serie_types, $chart_extra_data, $yellow_threshold, $red_threshold, $adapt_key, $force_integer, $series_suffix_str = '') {
	global $config;
	
	include_javascript_dependencies_flot_graph();
	
	$menu = true;
	$font_size = '7';
	
	// Get a unique identifier to graph
	$graph_id = uniqid('graph_');
	
	// Parent layer
	$return = "<div>";
	// Set some containers to legend, graph, timestamp tooltip, etc.
	$return .= "<p id='legend_$graph_id' style='font-size:".$font_size."pt'></p>";
	$return .= "<div id='timestamp_$graph_id' style='font-size:".$font_size."pt;display:none; position:absolute; background:#fff; border: solid 1px #aaa; padding: 2px; z-index:1000;'></div>";
	$return .= "<div id='$graph_id' class='graph $adapt_key' style='width: ".$width."px; height: ".$height."px;'></div>";
	$return .= "<div id='overview_$graph_id' style='display:none; margin-left:0px; margin-top:20px; width: ".$width."px; height:50px;'></div>";
	
	if ($water_mark != '') {
		$return .= "<div id='watermark_$graph_id' style='display:none; position:absolute;'><img id='watermark_image_$graph_id' src='$water_mark'></div>";
		$watermark = 'true';
	}
	else {
		$watermark = 'false';
	}
	
	// Set a weird separator to serialize and unserialize passing data from php to javascript
	$separator = ';;::;;';
	$separator2 = ':,:,,,:,:';
	
	// Transform data from our format to library format
	$legend2 = array();
	$labels = array();
	$a = array();
	$vars = array();
	$serie_types2 = array();
	
	$colors = array();
	
	$index = array_keys(reset($chart_data));
	foreach ($index as $serie_key) {
		if (isset($color[$serie_key])) {
			$colors[] = $color[$serie_key]['color'];
		}
		else {
			$colors[] = '';
		}
	}
		
	foreach($chart_data as $label => $values) {
		$labels[] = io_safe_output($label);
			
		foreach($values as $key => $value) {
			$jsvar = "data_".$graph_id."_".$key;
			
			if (!isset($serie_types[$key])) {
				$serie_types2[$jsvar] = 'line';
			}
			else {
				$serie_types2[$jsvar] = $serie_types[$key];
			}
			
			if ($serie_types2[$jsvar] == 'points' && $value == 0) {
				$data[$jsvar][] = 'null';
			}
			else {
				$data[$jsvar][] = $value;
			}
			
			if (!isset($legend[$key])) {
				$legend2[$jsvar] = 'null';
			}
			else {
				$legend2[$jsvar] = $legend[$key];
			}
		}
	}
	
	// Store data series in javascript format
	$jsvars = '';
	$jsseries = array();
	$values2 = array();
	$i = 0;
	$max_x = 0;
	foreach($data as $jsvar => $values) {
		$n_values = count($values);
		if($n_values > $max_x) {
			$max_x = $n_values;
		}
		
		$values2[] = implode($separator,$values);
		$i ++;
	}
	
	$values = implode($separator2, $values2);
	
	// Max is "n-1" because start with 0 
	$max_x--;
	
	if ($menu) {
		$threshold = false;
		if ($yellow_threshold != 0 || $red_threshold != 0) {
			$threshold = true;
		}
		
		$nbuttons = 2;
		
		if ($threshold) {
			$nbuttons++;
		}
		
		$menu_width = 18 * $nbuttons + 8;
		$return .= "<div id='menu_$graph_id' style='display:none; text-align:center; width:".$menu_width."px; position:relative; border: solid 1px #666; border-bottom: 0px; padding: 4px 4px 4px 4px'>
			<a href='javascript:'><img id='menu_cancelzoom_$graph_id' src='".$homeurl."images/zoom_cross.disabled.png' alt='".__('Cancel zoom')."' title='".__('Cancel zoom')."'></a>";
		if ($threshold) {
			$return .= "<a href='javascript:'><img id='menu_threshold_$graph_id' src='".$homeurl."images/chart_curve_threshold.png' alt='".__('Warning and Critical thresholds')."' title='".__('Warning and Critical thresholds')."'></a>";
		}
		$return .= "<a href='javascript:'><img id='menu_overview_$graph_id' src='".$homeurl."images/chart_curve_overview.png' alt='".__('Overview graph')."' title='".__('Overview graph')."'></a>
			</div>";
	}
	$extra_height = $height - 50;
	$extra_width = (int)($width / 3);
	
	$return .= "<div id='extra_$graph_id' style='font-size: ".$font_size."pt; display:none; position:absolute; overflow: auto; height: ".$extra_height."px; width: ".$extra_width."px; background:#fff; padding: 2px 2px 2px 2px; border: solid #000 1px;'></div>";
	
	// Process extra data
	$events = array();
	$event_ids = array();
	$alerts = array();
	$alert_ids = array();
	$legend_events = '';
	$legend_alerts = '';
	
	foreach ($chart_extra_data as $i => $data) {
		switch ($i) {
			case 'legend_alerts':
				$legend_alerts = $data;
				break;
			case 'legend_events':
				$legend_events = $data;
				break;
			default:
				if(isset($data['events'])) {
					$event_ids[] = $i;
					$events[$i] = $data['events'];
				}
				if(isset($data['alerts'])) {
					$alert_ids[] = $i;
					$alerts[$i] = $data['alerts'];
				}
				break;
		}
	}
	
	// Store serialized data to use it from javascript
	$events = implode($separator,$events);
	$event_ids = implode($separator,$event_ids);
	$alerts = implode($separator,$alerts);
	$alert_ids = implode($separator,$alert_ids);
	$labels = implode($separator,$labels);
	if (!empty($long_index)) {
		$labels_long = implode($separator, $long_index);
	}
	else {
		$labels_long = $labels;
	}
	if (!empty($legend)) {
		$legend = io_safe_output(implode($separator,$legend));
	}
	$serie_types  = implode($separator, $serie_types2);
	$colors  = implode($separator, $colors);
	
	// transform into string to pass to javascript
	if ($force_integer) {
		$force_integer = 'true';
	}
	else {
		$force_integer = 'false';
	}

	// Trick to get translated string from javascript
	$return .= html_print_input_hidden('unknown_text', __('Unknown'), true);
	
	// Javascript code
	$return .= "<script type='text/javascript'>";
	$return .= "//<![CDATA[\n";
	$return .= "pandoraFlotArea('$graph_id', '$values', '$labels', '$labels_long', '$legend', '$colors', '$type', '$serie_types', $watermark, $width, $max_x, '".$config['homeurl']."', '$unit', $font_size, $menu, '$events', '$event_ids', '$legend_events', '$alerts', '$alert_ids', '$legend_alerts', '$yellow_threshold', '$red_threshold', $force_integer, '$separator', '$separator2', '$series_suffix_str');";
	$return .= "\n//]]>";
	$return .= "</script>";
	
	// Parent layer
	$return .= "</div>";
	
	return $return;	
}

///////////////////////////////
///////////////////////////////
///////////////////////////////

// Prints a FLOT pie chart
function flot_pie_chart ($values, $labels, $width, $height, $water_mark,
	$font = '', $font_size = 8, $legend_position = '') {
	
	include_javascript_dependencies_flot_graph();
	
	$series = sizeof($values);
	if (($series != sizeof ($labels)) || ($series == 0) ) {
		return;
	}
	
	$graph_id = uniqid('graph_');
	
	switch ($legend_position) {
		case 'bottom':
			$height = $height + (count($values) * 24);
			break;
		case 'right':
		default:
			//TODO FOR TOP OR LEFT OR RIGHT
			break;
	}
	
	$return = "<div id='$graph_id' class='graph' style='width: ".$width."px; height: ".$height."px;'></div>";
	
	if($water_mark != '') {
		$return .= "<div id='watermark_$graph_id' style='display:none; position:absolute;'><img id='watermark_image_$graph_id' src='$water_mark'></div>";
		$water_mark = 'true';
	}
	else {
		$water_mark = 'false';
	}
	
	$separator = ';;::;;';
	
	$labels = implode($separator, $labels);
	$values = implode($separator, $values);
	
	$return .= "<script type='text/javascript'>";
	
	$return .= "pandoraFlotPie('$graph_id', '$values', '$labels',
		'$series', '$width', $font_size, $water_mark,
		'$separator', '$legend_position', '$height')";
	
	$return .= "</script>";
	
	return $return;
}

// Returns a 3D column chart
function flot_hcolumn_chart ($graph_data, $width, $height, $water_mark) {
	global $config;
	
	include_javascript_dependencies_flot_graph();
	
	$return = '';
	
	$stacked_str = '';
	$multicolor = true;
	
	// Get a unique identifier to graph
	$graph_id = uniqid('graph_');
	$graph_id2 = uniqid('graph_');
	
	// Set some containers to legend, graph, timestamp tooltip, etc.
	$return .= "<div id='$graph_id' class='graph' style='width: ".$width."px; height: ".$height."px;'></div>";
	$return .= "<div id='value_$graph_id' style='display:none; position:absolute; background:#fff; border: solid 1px #aaa; padding: 2px'></div>";
	
	if ($water_mark != '') {
		$return .= "<div id='watermark_$graph_id' style='display:none; position:absolute;'><img id='watermark_image_$graph_id' src='$water_mark'></div>";
		$watermark = 'true';
	}
	else {
		$watermark = 'false';
	}
	
	// Set a weird separator to serialize and unserialize passing data from php to javascript
	$separator = ';;::;;';
	$separator2 = ':,:,,,:,:';
	
	// Transform data from our format to library format
	$labels = array();
	$a = array();
	$vars = array();
	
	$max = 0;
	$i = count($graph_data);
	foreach ($graph_data as $label => $values) {
		$labels[] = io_safe_output($label);
		$i--;
		
		foreach ($values as $key => $value) {
			$jsvar = "data_".$graph_id."_".$key;
			
			if ($multicolor) {
				for($j = count($graph_data) - 1; $j>=0; $j--) {
					if($j == $i) {
						$data[$jsvar.$i][$j] = $value;
					}
					else {
						$data[$jsvar.$i][$j] = 0;
					}
				}
			}
			else {
				$data[$jsvar][] = $value;
			}
			
			$return .= "<div id='value_".$i."_$graph_id' class='values_$graph_id' style='color: #000; position:absolute;'>$value</div>";
			if($value > $max) {
				$max = $value;
			}
		}
	}
	
	// Store serialized data to use it from javascript
	$labels = implode($separator,$labels);
	
	// Store data series in javascript format
	$jsvars = '';
	$jsseries = array();
	
	$i = 0;
	
	$values2 = array();
	
	foreach ($data as $jsvar => $values) {
		$values2[] = implode($separator,$values);
	}
	
	$values = implode($separator2, $values2);
	
	$jsseries = implode(',', $jsseries);
	
	
	// Javascript code
	$return .= "<script type='text/javascript'>";
	
	$return .= "pandoraFlotHBars('$graph_id', '$values', '$labels', false, $max, '$water_mark', '$separator', '$separator2')";
	
	$return .= "</script>";
	
	return $return;	
}

// Returns a 3D column chart
function flot_vcolumn_chart ($graph_data, $width, $height, $color, $legend, $long_index, $homeurl, $unit, $water_mark, $homedir, $reduce_data_columns, $adapt_key) {
	global $config;
	
	include_javascript_dependencies_flot_graph();
	
	$stacked_str = '';
	$multicolor = false;
	
	// Get a unique identifier to graph
	$graph_id = uniqid('graph_');
	$graph_id2 = uniqid('graph_');
	
	// Set some containers to legend, graph, timestamp tooltip, etc.
	$return .= "<div id='$graph_id' class='graph $adapt_key' style='width: ".$width."px; height: ".$height."px;'></div>";
	$return .= "<div id='value_$graph_id' style='display:none; position:absolute; background:#fff; border: solid 1px #aaa; padding: 2px'></div>";
	
	if ($water_mark != '') {
		$return .= "<div id='watermark_$graph_id' style='display:none; position:absolute;'><img id='watermark_image_$graph_id' src='$water_mark'></div>";
		$watermark = 'true';
	}
	else {
		$watermark = 'false';
	}
	
	$colors = array();
	$index = array_keys(reset($graph_data));
	
	foreach ($index as $serie_key) {
		if (isset($color[$serie_key])) {
			$colors[] = $color[$serie_key]['color'];
		}
		else {
			$colors[] = '';
		}
	}
	
	// Set a weird separator to serialize and unserialize passing data from php to javascript
	$separator = ';;::;;';
	$separator2 = ':,:,,,:,:';
	
	// Transform data from our format to library format
	$labels = array();
	$a = array();
	$vars = array();
	
	$max = 0;
	$i = count($graph_data);
	foreach ($graph_data as $label => $values) {
		$labels[] = io_safe_output($label);
		$i--;
		
		foreach ($values as $key => $value) {
			$jsvar = "data_".$graph_id."_".$key;
			
			if($multicolor) {
				for($j = count($graph_data) - 1; $j>=0; $j--) {
					if($j == $i) {
						$data[$jsvar.$i][$j] = $value;
					}
					else {
						$data[$jsvar.$i][$j] = 0;
					}
				}
			}
			else {
				$data[$jsvar][] = $value;
			}
			
			//$return .= "<div id='value_".$i."_$graph_id' class='values_$graph_id' style='color: #000; position:absolute;'>$value</div>";
			if ($value > $max) {
				$max = $value;
			}
		}
	}
	
	// Store serialized data to use it from javascript
	$labels = implode($separator,$labels);
	$colors  = implode($separator, $colors);
	
	// Store data series in javascript format
	$jsvars = '';
	$jsseries = array();
	
	$i = 0;
	
	$values2 = array();
	
	foreach($data as $jsvar => $values) {
		$values2[] = implode($separator,$values);
	}
	
	$values = implode($separator2, $values2);
	
	$jsseries = implode(',', $jsseries);
	
	// Javascript code
	$return .= "<script type='text/javascript'>";
	
	$return .= "pandoraFlotVBars('$graph_id', '$values', '$labels', '$labels', '$legend', '$colors', false, $max, '$water_mark', '$separator', '$separator2')";
	
	$return .= "</script>";
	
	return $return;	
}

function flot_slicesbar_graph ($graph_data, $period, $width, $height, $legend, $colors, $fontpath, $round_corner, $homeurl, $watermark = '', $adapt_key = '') {
	global $config;

	include_javascript_dependencies_flot_graph();
	
	$height+= 20;
	
	$stacked_str = 'stack: stack,';
	
	// Get a unique identifier to graph
	$graph_id = uniqid('graph_');
	
	// Set some containers to legend, graph, timestamp tooltip, etc.
	$return = "<div id='$graph_id' class='graph $adapt_key' style='width: ".$width."px; height: ".$height."px;'></div>";
	$return .= "<div id='value_$graph_id' style='display:none; position:absolute; background:#fff; border: solid 1px #aaa; padding: 2px'></div>";
	
	// Set a weird separator to serialize and unserialize passing data from php to javascript
	$separator = ';;::;;';
	$separator2 = ':,:,,,:,:';
	
	// Transform data from our format to library format
	$labels = array();
	$a = array();
	$vars = array();
	
	$datacolor = array();
	
	$max = 0;
	
	$i = count($graph_data);
	
	$intervaltick = $period / $i;
	
	$leg_max_length = 0;
	foreach($legend as $l) {
		if(strlen($l) > $leg_max_length) {
			$leg_max_length = strlen($l);
		}
	}
	
	$fontsize = 7;
	
	$extra_height = 15;
	
	$return .= "<div id='extra_$graph_id' style='font-size: ".$fontsize."pt; display:none; position:absolute; overflow: auto; height: ".$extra_height."px; background:#fff; padding: 2px 2px 2px 2px; border: solid #000 1px;'></div>";
	
	$maxticks = (int) ($width / ($fontsize * $leg_max_length));
	
	$i_aux = $i;
	while(1) {
		if($i_aux <= $maxticks ) {
			break;
		}
		
		$intervaltick*= 2;
		
		$i_aux /= 2;
	}
	
	$intervaltick = (int) $intervaltick;
	$acumulate = 0;
	$c = 0;
	$acumulate_data = array();
	foreach($graph_data as $label => $values) {
		$labels[] = io_safe_output($label);
		$i--;
		
		foreach($values as $key => $value) {
			$jsvar = "d_".$graph_id."_".$i;
			if($key == 'data') {
				$datacolor[$jsvar] = $colors[$value];
				continue;
			}
			$data[$jsvar][] = $value;
			
			$acumulate_data[$c] = $acumulate;
			$acumulate += $value;
			$c++;
			
			//$return .= "<div id='value_".$i."_$graph_id' class='values_$graph_id' style='color: #000; position:absolute;'>$value</div>";
			if($value > $max) {
				$max = $value;
			}
		}
	}
	
	// Store serialized data to use it from javascript
	$labels = implode($separator,$labels);
	$datacolor = implode($separator,$datacolor);
	$legend = io_safe_output(implode($separator,$legend));
	$acumulate_data = io_safe_output(implode($separator,$acumulate_data));
	
	// Store data series in javascript format
	$jsvars = '';
	$jsseries = array();
	
	$date = get_system_time ();
	$datelimit = ($date - $period) * 1000;
	
	$i = 0;
	
	$values2 = array();
	
	foreach($data as $jsvar => $values) {
		$values2[] = implode($separator,$values);
		$i ++;
	}
	
	$values = implode($separator2, $values2);	
	
	// Javascript code
	$return .= "<script type='text/javascript'>";
	$return .= "//<![CDATA[\n";
	$return .= "pandoraFlotSlicebar('$graph_id', '$values', '$datacolor', '$labels', '$legend', '$acumulate_data', $intervaltick, false, $max, '$separator', '$separator2')";
	$return .= "\n//]]>";
	$return .= "</script>";
	
	return $return;	
}
?>