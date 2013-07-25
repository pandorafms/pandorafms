<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access node graph builder");
	include ("general/noaccess.php");
	exit;
}

require_once ('include/functions_networkmap.php');

// Set filter
$filter = networkmap_get_filter ($layout);

if (!isset($text_filter)) {
	$text_filter = '';
}

// Generate dot file
$graph = networkmap_generate_hash(__('Pandora FMS'), $group, $simple,
	$font_size, $layout, $nooverlap, $zoom, $ranksep, $center, $regen,
	$pure, $id_networkmap, $show_snmp_modules, true, true,
	$text_filter);
html_debug_print($graph, true);
networkmap_print_jsdata($graph);


//html_debug_print($graph);

echo '<script type="text/javascript" src="' . $config['homeurl'] . 'include/javascript/d3.v3.js" charset="utf-8"></script>';
echo '<div id="dinamic_networkmap"></div>';
?>
<style>

.node {
  stroke: #fff;
  stroke-width: 1.5px;
}

.select_node {
  stroke: #000;
  stroke-width: 1.5px;
}

.link {
  stroke: #999;
  stroke-opacity: 1;
  stroke-width: 1;
}

.select_link {
  stroke: #000;
  stroke-opacity: 1;
  stroke-width: 1;
}

</style>
<script>
var width = $("#dinamic_networkmap").width(),
    height = $("#main").height();

var color = d3.scale.category20();

var force = d3.layout.force()
    .charge(-60)
    .linkDistance(20)
    .friction(0.9)
    //.gravity(0.2)
    .size([width, height]);

var zoom_obj = d3.behavior.zoom();
zoom_obj.scaleExtent([0.3, 3]).on("zoom", zoom);

var svg = d3.select("#dinamic_networkmap").append("svg")
    .attr("width", width)
	.attr("height", height)
	.attr("pointer-events", "all")
    .call(zoom_obj)
    .append('svg:g');

force
  .nodes(graph.nodes)
  .links(graph.links)
  .start();

var link = svg.selectAll(".link")
  .data(graph.links)
  .enter().append("line")
  .attr("id", function(d) {
		var id_text = 'link_'
			+ d.source.id
			+ "_" + d.target.id;
		
		return id_text;
	})
  .attr("class", function(d) {
		var class_text = 'link';
		
		class_text += " source_" + d.source.id;
		class_text += " target_" + d.target.id;
		
		return class_text;
	});

var node = svg.selectAll(".node")
  .data(graph.nodes)
  .enter().append("circle")
  .attr("id", function(d) { return "node_" + d.id})
  .attr("class", "node")
  .attr("r", 5)
  .style("fill", function(d) { return d.color; })
  .call(force.drag)
  .on("mouseover", over)
  .on("mouseout", out)
  .on("click", click);

node.append("title")
  .text(function(d) { return d.name; });

 svg.style("opacity", 1e-6)
    .transition()
      .duration(1000)
      .style("opacity", 1);

force.on("tick", function() {
link.attr("x1", function(d) { return d.source.x; })
	.attr("y1", function(d) { return d.source.y; })
	.attr("x2", function(d) { return d.target.x; })
	.attr("y2", function(d) { return d.target.y; });

node.attr("cx", function(d) { return d.x; })
	.attr("cy", function(d) { return d.y; });
});

function over(d) {
	$("#node_" + d.id).attr('class', 'select_node');
	$.each($(".source_" + d.id), function(i, line) {
		class_txt = $(line).attr('class');
		id_txt = $(line).attr('id');
		
		$("#" + id_txt).attr('class',
			class_txt.replace("link", "select_link"));
	});
}

function out(d) {
	$("#node_" + d.id).attr('class', 'node');
	
	$.each($(".source_" + d.id), function(i, line) {
		class_txt = $(line).attr('class');
		id_txt = $(line).attr('id');
		
		$("#" + id_txt).attr('class',
			class_txt.replace("select_link", "link"));
	});
}

function click(d) {
	window.location = d.url;
}

function zoom(translate_param, scale_param) {
	var scale;
	var translate;
	
	if (typeof(translate_param) == "undefined") {
		scale = d3.event.scale;
		translate = d3.event.translate;
	}
	
	svg.attr("transform", "translate(" + translate + ")scale(" + scale + ")");
}
</script>