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

/*-----------------------------------------------*/
/*-------------------Constants-------------------*/
/*-----------------------------------------------*/
var MAX_ZOOM_LEVEL = 50;

/*-----------------------------------------------*/
/*------------------Constructor------------------*/
/*-----------------------------------------------*/
var MapController = function(target) {
	this._target = target;
	
	this._id = $(target).data('id');
}

/*-----------------------------------------------*/
/*------------------Atributes--------------------*/
/*-----------------------------------------------*/
MapController.prototype._id = null;
MapController.prototype._viewport = null;
MapController.prototype._zoomManager = null;
MapController.prototype._slider = null;

/*-----------------------------------------------*/
/*--------------------Methods--------------------*/
/*-----------------------------------------------*/
/**
Function init_map
Return void
This function init the map
*/
MapController.prototype.init_map = function() {
	var self = this;
	
	var svg = d3.select(this._target + " svg");
	
	self._zoomManager =
		d3.behavior.zoom().scaleExtent([1/MAX_ZOOM_LEVEL, MAX_ZOOM_LEVEL]).on("zoom", zoom);
	
	self._viewport = svg
		.call(self._zoomManager)
		.append("g")
			.attr("class", "viewport");
	
	/**
	Function zoom
	Return void
	This function manages the zoom
	*/
	function zoom() {
		self.close_all_tooltips();
		
		var zoom_level = d3.event.scale;
		
		self._slider.property("value", Math.log(zoom_level));
		
		self._viewport
			.attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
	}
	
	/**
	Function zoom_in
	Return void
	This function zoom with "+" button
	*/
	function zoom_in(d) {
		var step = parseFloat(self._slider.property("step"));
		var slider_value = parseFloat(self._slider.property("value"));
		
		slider_value += step;
		
		var zoom_level = Math.exp(slider_value);
		
		self._slider.property("value", Math.log(zoom_level));
		self._slider.on("input")();
	}
	
	/**
	Function zoom_out
	Return void
	This function zoom with "-" button
	*/
	function zoom_out(d) {
		var step = parseFloat(self._slider.property("step"));
		var slider_value = parseFloat(self._slider.property("value"));
		
		slider_value -= step;
		
		var zoom_level = Math.exp(slider_value);
		
		self._slider.property("value", Math.log(zoom_level));
		self._slider.on("input")();
	}
	
	/**
	Function home_zoom
	Return void
	This function zoom with "H" button (reset zoom)
	*/
	function home_zoom(d) {
		self._zoomManager.scale(1).translate([0, 0]).event(self._viewport);
	}
	
	/**
	Function slided
	Return void
	This function manages the slide (zoom system)
	*/
	function slided(d) {
		var slider_value = parseFloat(self._slider.property("value"))
		
		zoom_level = Math.exp(slider_value);
		
		/*----------------------------------------------------------------*/
		/*-Code to translate the map with the zoom for to hold the center-*/
		/*----------------------------------------------------------------*/
		var center = [
			parseFloat(d3.select("#map").style('width')) / 2,
			parseFloat(d3.select("#map").style('height')) / 2];
		
		var old_translate = self._zoomManager.translate();
		var old_scale = self._zoomManager.scale();
		
		var temp1 = [(center[0] - old_translate[0]) / old_scale,
			(center[1] - old_translate[1]) / old_scale];
		
		var temp2 = [temp1[0] * zoom_level + old_translate[0],
			temp1[1] * zoom_level + old_translate[1]];
		
		var new_translation = [
			old_translate[0] + center[0] - temp2[0],
			old_translate[1] + center[1] - temp2[1]]
		
		self._zoomManager.scale(zoom_level)
			.translate(new_translation)
			.event(self._viewport);
	}
	
	self._slider = d3.select("#map .zoom_controller .vertical_range")
		.property("value", 0)
		.property("min", -Math.log(MAX_ZOOM_LEVEL))
		.property("max", Math.log(MAX_ZOOM_LEVEL))
		.property("step", Math.log(MAX_ZOOM_LEVEL) * 2 / MAX_ZOOM_LEVEL)
		.on("input", slided);
	
	
	d3.select("#map .zoom_box .home_zoom")
		.on("click", home_zoom);
	
	d3.select("#map .zoom_box .zoom_in")
		.on("click", zoom_in);
	
	d3.select("#map .zoom_box .zoom_out")
		.on("click", zoom_out);
	
	
	self.paint_nodes();
	
	this.init_events();
};

/**
Function paint_nodes
Return void
This function paint the nodes
*/
MapController.prototype.paint_nodes = function() {
	this._viewport.selectAll(".node")
		.data(nodes)
			.enter()
				.append("g")
					.attr("transform",
						function(d) { return "translate(" + d['x'] + " " + d['y'] + ")";})
					.attr("class", "draggable node")
					.attr("id", function(d) { return "node_" + d['graph_id'];})
					.attr("data-id", function(d) { return d['id'];})
					.attr("data-graph_id", function(d) { return d['graph_id'];})
					.attr("data-type", function(d) { return d['type'];})
					.append("circle")
						.attr("style", "fill: rgb(50, 50, 128);")
						.attr("r", "6");
}

/**
Function init_events
Return boolean
This function init click events in the map
*/
MapController.prototype.init_events = function(principalObject) {
	self = this;
	
	$(this._target + " svg *, " + this._target + " svg")
		.off("mousedown", {map: this}, this.click_event);

	d3.selectAll(".node")
		.on("mouseover", function(d) {
			d3.select("#node_" + d['graph_id'])
				.select("circle")
				.attr("style", "fill: rgb(128, 50, 50);");
		})
		.on("mouseout", function(d) {
			d3.select("#node_" + d['graph_id'])
				.select("circle")
				.attr("style", "fill: rgb(50, 50, 128);");
		})
		.on("click", function(d) {
			if (d3.event.defaultPrevented) return;
			
			self.tooltip_map_create(self, this);
		});
	
	var drag = d3.behavior.drag()
		.origin(function(d) { return d; })
		.on("dragstart", dragstarted)
		.on("drag", dragged)
		.on("dragend", dragended);
	
	d3.selectAll(".draggable").call(drag);
	
	function dragstarted(d) {
		d3.event.sourceEvent.stopPropagation();
		
		if ($("#node_" + d['graph_id']).hasClass("tooltipstered")) {
			$("#node_" + d['graph_id']).tooltipster('destroy');
		}
		
		d3.select(this).classed("dragging", true);
	}
	
	function dragged(d) {
		var delta_x = d3.event.dx;
		var delta_y = d3.event.dy;
		
		var translation = d3.transform(d3.select(this).attr("transform"));
		scale = 1;
		var x = translation.translate[0] + delta_x;
		var y = translation.translate[1] + delta_y;
		
		d3.select(this).attr("transform",
			"translate(" + x + " " + y + ")");
	}
	
	function dragended(d) {
		d3.select(this).classed("dragging", false);
		
		console.log("#node_" + d['graph_id']);
		
		if ($("#node_" + d['graph_id']).hasClass("tooltipstered")) {
			$("#node_" + d['graph_id']).tooltipster('destroy');
		}
	}
}

/**
Function tooltip_map_create
Return void
This function manages nodes tooltips
*/
MapController.prototype.tooltip_map_create = function(self, target) {
	var nodeTarget = $(target);
	var spinner = $('#spinner_tooltip').html();
	
	var nodeR = parseInt($("circle", nodeTarget).attr("r"));
	nodeR = nodeR * self._zoomManager.scale(); // Apply zoom
	var node_id = nodeTarget.attr("id");
	
	var type = parseInt(nodeTarget.data("type"));
	var data_id = parseInt(nodeTarget.data("id"));
	var data_graph_id = parseInt(nodeTarget.data("graph_id"));
	
	nodeTarget.tooltipster({
		arrow: true,
		trigger: 'click',
		contentAsHTML: true,
		autoClose: false,
		offsetX: nodeR,
		theme: 'tooltipster-noir',
		multiple: true,
		interactive: true,
		content: spinner,
		restoration: 'none',
		functionBefore: function(origin, continueTooltip) {
			continueTooltip();
			self.nodeData(data_id, type, self._id, data_graph_id, origin, node_id);
		}
	});
	
	nodeTarget.tooltipster("show");
}

/**
Function close_all_tooltips
Return void
This function hide nodes tooltips
*/
MapController.prototype.close_all_tooltips = function() {
	$("svg .tooltipstered").tooltipster("destroy");
}

/**
Function nodeData
Return array(data)
This function returns the data of the node
*/
MapController.prototype.nodeData = function(data_id, type, id_map, data_graph_id, origin, node_id) {
	var params = {};
	params["getNodeData"] = 1;
	params["id_node_data"] = data_id;
	params["type"] = type;
	params["id_map"] = id_map;
	params["data_graph_id"] = data_graph_id;
	params["node_id"] = node_id;
	params["page"] = "include/ajax/map.ajax";
	
	jQuery.ajax ({
		data: params,
		dataType: "json",
		type: "POST",
		url: "ajax.php",
		success: function (data) {
			if ($(origin).hasClass("tooltipstered")) {
				origin.tooltipster('content', data);
			}
		}
	});
}

/*-----------------------------------------------*/
/*-------------------Functions-------------------*/
/*-----------------------------------------------*/

/**
Function open_in_another_window
Return void
This function open the node in extra window
*/
function open_in_another_window(link) {
	window.open(link);
}

function close_button_tooltip(node_id) {
	$("#" + node_id).tooltipster("destroy");
}
