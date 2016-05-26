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


function include_javascript_d3 ($return = false) {
	global $config;
	
	static $is_include_javascript = false;
	
	$output = '';
	if (!$is_include_javascript) {
		$is_include_javascript = true;

		$output .= '<script type="text/javascript" src="' . $config['homeurl'] . 'include/javascript/d3.3.5.14.js" charset="utf-8"></script>';
		$output .= '<script type="text/javascript" src="' . $config['homeurl'] . 'include/graphs/pandora.d3.js" charset="utf-8"></script>';

	}
	if (!$return)
		echo $output;
	
	return $output;
}

function d3_relationship_graph ($elements, $matrix, $unit, $width = 700, $return = false) {
	global $config;

	if (is_array($elements))
		$elements = json_encode($elements);
	if (is_array($matrix))
		$matrix = json_encode($matrix);

	$output = "<div id=\"chord_diagram\"></div>";
	$output .= include_javascript_d3(true); 
	$output .= "<script language=\"javascript\" type=\"text/javascript\">
					chordDiagram('#chord_diagram', $elements, $matrix, '$unit', $width);
				</script>";

	if (!$return)
		echo $output;
	
	return $output;
}

function d3_tree_map_graph ($data, $width = 700, $height = 700, $return = false) {
	global $config;

	if (is_array($data))
		$data = json_encode($data);
	
	$output = "<div id=\"tree_map\" style='overflow: hidden;'></div>";
	$output .= include_javascript_d3(true);
	$output .= "<style type=\"text/css\">
					.cell>rect {
						pointer-events: all;
						cursor: pointer;
						stroke: #EEEEEE;
					}
					
					.chart {
						display: block;
						margin: auto;
					}
					
					.parent .label {
						color: #FFFFFF;
						text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
						-webkit-text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
						-moz-text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
					}
					
					.labelbody {
						text-align: center;
						background: transparent;
					}
					
					.label {
						margin: 2px;
						white-space: pre;
						overflow: hidden;
						text-overflow: ellipsis;
						text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
						-webkit-text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
						-moz-text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
					}
					
					.child .label {
						white-space: pre-wrap;
						text-align: center;
						text-overflow: ellipsis;
					}
					
					.cell {
						font-size: 11px;
						cursor: pointer
					}
				</style>";
	$output .= "<script language=\"javascript\" type=\"text/javascript\">
					treeMap('#tree_map', $data, '$width', '$height');
				</script>";

	if (!$return)
		echo $output;
	
	return $output;
}

function d3_sunburst_graph ($data, $width = 700, $height = 700, $return = false) {
	global $config;

	if (is_array($data))
		$data = json_encode($data);
	
	$output = "<div id=\"sunburst\" style='overflow: hidden;'></div>";
	$output .= include_javascript_d3(true);
	$output .= "<style type=\"text/css\">
					path {
						stroke: #fff;
						fill-rule: evenodd;
					}
				</style>";
	$output .= "<script language=\"javascript\" type=\"text/javascript\">
					sunburst('#sunburst', $data, '$width', '$height');
				</script>";

	if (!$return)
		echo $output;
	
	return $output;
}

function d3_bullet_chart($chart_data, $width, $height, $color, $legend,
	$homeurl, $unit, $font, $font_size) {
	
	global $config;
	
	$output = '';
	$output .= include_javascript_d3(true);
	
	$id_bullet = uniqid();
	
	$output .= 
		'<div id="bullet_graph_' . $id_bullet . '" class="bullet" style="overflow: hidden; width: '.$width.'px; margin-left: auto; margin-right: auto;"></div>
		<style>
			
			.bullet_graph {
				font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
				margin: auto;
				padding-top: 40px;
				position: relative;
				width: 100%;
			}
			
			.bullet { font: 7px sans-serif; }
			.bullet .marker.s0 { stroke: #FC4444; stroke-width: 2px; }
			.bullet .marker.s1 { stroke: #FAD403; stroke-width: 2px; }
			.bullet .marker.s2 { stroke: steelblue; stroke-width: 2px; }
			.bullet .tick line { stroke: #666; stroke-width: .5px; }
			.bullet .range.s0 { fill: #ddd; }
			.bullet .range.s1 { fill: #ddd; }
			.bullet .range.s2 { fill: #ccc; }
			.bullet .measure.s0 { fill: steelblue; }
			.bullet .measure.s1 { fill: steelblue; }
			.bullet .title { font-size: 7pt; font-weight: bold; text-align:left; }
			.bullet .subtitle { fill: #999; font-size: 7pt;}
			.bullet g text { font-size: 7pt;}
		
		</style>
		<script src="'. $config['homeurl'] . 'include/graphs/bullet.js"></script>
		<script language="javascript" type="text/javascript">
		
		var margin = {top: 5, right: 40, bottom: 20, left: 120};
		
		width = ('.$width.'+10);
		height = '.$height.'- margin.top - margin.bottom;
		
		var chart = d3.bullet()
			.width(width)
			.height(height)
			.orient("left");
		';
	
	$temp = array();
	foreach ($chart_data as $data) {
		if (isset ($data["label"]) ) {
			$name = io_safe_output($data["label"]);
		}
		else
			$name = io_safe_output($data["nombre"]);
		$name = ui_print_truncate_text($name, 15, false, true, false, '...', false);
		$marker = "";
		if ($data['value'] == 0) {
			$marker = ", 0";
		}
		$temp[] = '{"title":"'.$name.'","subtitle":"'.$data["unit"].'",
				"ranges":['.((float)$data['max']) .'],"measures":[' .$data['value']. '],
					"markers":[' .$data['min_warning'].','. $data['min_critical'].$marker.']}';
	}
	$output .= 'var data = ['
	. implode(",",$temp) . '];
	';
	$output .= '
		
		var svg = d3.select("#bullet_graph_' . $id_bullet . '").selectAll("svg")
			.data(data)
			.enter().append("svg")
				.attr("class", "bullet")
				.attr("width", "100%")
				.attr("height", height+ margin.top + margin.bottom)
			.append("g")
				.attr("transform", "translate(" + (margin.left) + "," + margin.top + ")")
				.call(chart);
			 
		
		var title = svg.append("g")
			.style("text-anchor", "end")
			.attr("transform", "translate(-10, 15)");
		
		title.append("text")
			.attr("class", "title")
			.text(function(d) { return d.title; });
		
		title.append("text")
				.attr("class", "subtitle")
				.attr("dy", "1em")
				.text(function(d) { return d.subtitle; });
			
		$(".tick>text").each(function() {
			
			label = $(this).text().replace(/,/g,"");
			label = parseFloat(label);
			text = label.toLocaleString();
			if ( label >= 1000000)
				text = text.substring(0,3) + "M";
			else if (label >= 100000)
				text = text.substring(0,3) + "K";
			else if (label >= 1000)
				text = text.substring(0,2) + "K";
			
			$(this).text(text);
		});
		</script>';
	
	return $output;
	
}

function d3_gauges($chart_data, $width, $height, $color, $legend,
				$homeurl, $unit, $font, $font_size, $no_data_image) {
	global $config;

	if (is_array($chart_data))
		$data = json_encode($chart_data);
	$output = include_javascript_d3(true);
	
	foreach ($chart_data as $module) {
		$output .= "<div id='".$module['gauge']."' style='float:left; overflow: hidden; margin-left: 10px;'></div>";
		
	}
	
	$output .= "<script language=\"javascript\" type=\"text/javascript\">
					var data = $data;
					createGauges(data, '$width', '$height','$font_size','$no_data_image');
				</script>";

	return $output;
}
?>
