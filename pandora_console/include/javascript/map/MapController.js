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
	this._tooltipsID = [];
}

/*-----------------------------------------------*/
/*------------------Atributes--------------------*/
/*-----------------------------------------------*/
MapController.prototype._id = null;
MapController.prototype._tooltipsID = null;
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
		self.tooltip_map_close();

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
					.append("circle")
						.attr("id", function(d) { return "node_" + d['id'];})
						.attr("class", "node")
						.attr("style", "fill: rgb(50, 50, 128);")
						.attr("r", "6");
}

/**
Function init_events
Return boolean
This function init click events in the map
*/
MapController.prototype.init_events = function(principalObject) {
	$(this._target + " svg *, " + this._target + " svg").on("mousedown", {map: this}, this.click_event);
}

/**
Function click_event
Return void
This function manages mouse clicks and run events in consecuence
*/
MapController.prototype.click_event = function(event) {
	var self = event.data.map;
	event.preventDefault();
	event.stopPropagation();
	switch (event.which) {
        case 1:
			if ($(event.currentTarget).hasClass("node")) {
				self.tooltip_map_create(self, event);
			}
			else {
				self.tooltip_map_close();
			}
            break;
        case 2:
            break;
        case 3:
            break;
		default:
			break;
    }
}

/**
Function tooltip_map_create
Return void
This function manages nodes tooltips
*/
MapController.prototype.tooltip_map_create = function(self, event) {
	var nodeR = parseInt($(event.currentTarget).attr("r"));
	nodeR = nodeR * self._zoomManager.scale(); // Apply zoom
	var node_id = $(event.currentTarget).attr("id");

	//Always changes the content because this may be change
	var nodeContent = this.nodeData(node_id/*, type, id_map*/);

	/*----------------------FOR TEST--------------------*/
	nodeContent = '<span>I\'M A FUCKING TOOLTIP!!</span>';
	/*--------------------------------------------------*/

	if (this.containsTooltipId(node_id)) {
		$(event.currentTarget).tooltipster("option", "offsetX", nodeR);
		$(event.currentTarget).tooltipster('content', $(nodeContent));
		$(event.currentTarget).tooltipster("show");
	}
	else {
		$(event.currentTarget).tooltipster({
	        arrow: true,
	        trigger: 'click',
			contentAsHTML: true,
	        autoClose: false,
			offsetX: nodeR,
			theme: 'tooltipster-noir',
	        multiple: true,
	        content: nodeContent
	    });

		this._tooltipsID.push(node_id);

		$(event.currentTarget).tooltipster("show");
	}
}

/**
Function tooltip_map_close
Return void
This function hide nodes tooltips
*/
MapController.prototype.tooltip_map_close = function() {
	for (i = 0; i < this._tooltipsID.length; i++) {
		$('#' + this._tooltipsID[i]).tooltipster("hide");
	}
}

/**
Function containsTooltipId
Return boolean
This function returns true if the element is in the array
*/
MapController.prototype.containsTooltipId = function(element) {
	for (i = 0; i < this._tooltipsID.length; i++) {
		if (this._tooltipsID[i] == element) {
			return true;
		}
	}
	return false;
}

/**
Function nodeData
Return array(data)
This function returns the data of the node
*/
MapController.prototype.nodeData = function(id/*, type, id_map*/) {
	var params = {};
	params["getNodeData"] = 1;
	params["id_node"] = id;
	/*params["type"] = type;
	params["id_map"] = id_map;*/
	params["page"] = "include/ajax/map.ajax";

	jQuery.ajax ({
		data: params,
		dataType: "json",
		type: "POST",
		url: "ajax.php",
		success: function (data) {
			return data;
		}
	});
}
