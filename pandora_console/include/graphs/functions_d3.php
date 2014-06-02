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

		$output .= '<script type="text/javascript" src="' . $config['homeurl'] . 'include/javascript/d3.v3.js" charset="utf-8"></script>';
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

?>
