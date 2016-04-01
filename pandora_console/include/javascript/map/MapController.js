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
var RELATION_MINIMAP = 4;
var CONTROL_KEY = 17;

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
MapController.prototype._minimap = null;
MapController.prototype._zoomManager = null;
MapController.prototype._slider = null;
MapController.prototype._relation = null;
MapController.prototype._flag_multiple_selection = false;
MapController.prototype._cache_files = {};
MapController.prototype._last_event = null;
MapController.prototype._last_mouse_position = null;
MapController.prototype._relationship_in_progress = false;
MapController.prototype._relationship_in_progress_type = null;

/*-----------------------------------------------*/
/*--------------------Methods--------------------*/
/*-----------------------------------------------*/
/**
* Function init_map
* Return void
* This function init the map
*/
MapController.prototype.init_map = function() {
	var self = this;
	
	var svg = d3.select(self._target + " svg");
	
	self._zoomManager =
		d3.behavior.zoom()
			.scaleExtent([1/MAX_ZOOM_LEVEL, MAX_ZOOM_LEVEL]).on("zoom", zoom);
	
	self._viewport = svg
		.call(self._zoomManager)
		.append("g")
			.attr("class", "viewport");
	
	self._minimap = svg
		.append("g")
			.attr("class", "minimap");
	
	/**
	* Function zoom
	* Return void
	* This function manages the zoom
	*/
	function zoom() {
		self.close_all_tooltips();
		self.remove_resize_square();
		
		if (!self._flag_multiple_selection) {
			
			self.last_event = "zoom";
			
			var zoom_level = d3.event.scale;
			
			self._slider.property("value", Math.log(zoom_level));
			
			self._viewport
				.attr("transform",
					"translate(" + d3.event.translate + ") scale(" + d3.event.scale + ")");
			
			self.zoom_minimap();
			
			
		}
		else {
			
			self.last_event = null;
			
			// Reset the zoom and panning actual
			var viewport_transform = d3.transform(
				d3.select(self._target + " .viewport").attr("transform"));
			
			
			self._zoomManager
				.scale(viewport_transform.scale[0])
				.translate(viewport_transform.translate);
		}
	}
	
	/**
	* Function zoom_in
	* Return void
	* This function zoom with "+" button
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
	* Function zoom_out
	* Return void
	* This function zoom with "-" button
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
	* Function home_zoom
	* Return void
	* This function zoom with "H" button (reset zoom)
	*/
	function home_zoom(d) {
		self._zoomManager.scale(1).translate([0, 0]).event(self._viewport);
	}
	
	/**
	* Function slided
	* Return void
	* This function manages the slide (zoom system)
	*/
	function slided(d) {
		var slider_value = parseFloat(self._slider.property("value"))
		
		var zoom_level = Math.exp(slider_value);
		
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
			old_translate[1] + center[1] - temp2[1]];
		
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
	self.paint_arrows();
	
	self.paint_minimap();
	
	self.ini_selection_rectangle();
	
	self.init_events();
}

/**
* Function ini_selection_rectangle
* Return void
* This function init the rectangle selection
*/
MapController.prototype.ini_selection_rectangle = function() {
	var self = this;
	
	d3.select(self._target + " svg")
		.append("g")
			.attr("id", "layer_selection_rectangle");
}

/**
* Function minimap_get_size
* Return [width, height]
* This function returns the minimap size
*/
MapController.prototype.minimap_get_size = function() {
	var self = this;
	
	var map_size = d3.select(self._target + " .minimap").node().getBBox();
	
	var minimap_size = [];
	minimap_size[0] = map_size.width;
	minimap_size[1] = map_size.height;
	
	return minimap_size;
}

/**
* Function get_node
* Return node
* This function returns a node
*/
MapController.prototype.get_node = function(id_graph) {
	var return_node = null;
	
	$.each(nodes, function(i, node) {
		if (node['graph_id'] == id_graph) {
			return_node = node;
			return false;
		}
	});
	
	return return_node;
}

/**
* Function get_node_type
* Return node type
* This function returns a node type (module, agent or edge)
*/
MapController.prototype.get_node_type = function(id_graph) {
	var self = this;
	
	var node = self.get_node(id_graph);
	
	if (node !== null) {
		return node['type'];
	}
	
	return null;
}

/**
* Function get_edges_from_node
* Return array[edge]
* This function returns the edges of a node
*/
MapController.prototype.get_edges_from_node = function(id_graph) {
	var return_edges = [];
	
	$.each(edges, function(i, edge) {
		if ((edge['to'] == id_graph) || (edge['from'] == id_graph)) {
			return_edges.push(edge);
		}
	});
	
	return return_edges;
}

/**
* Function update_edges_from_clean_arrows
* Return void
* This function updates the graph id of the edges
*/
MapController.prototype.update_edges_from_clean_arrows = function(clean_arrows) {
	newEdges = [];
	clean_arrows.forEach(function(arrow, index) {
		newEdges[index] = [];
		newEdges[index]['from'] = arrow['from']['graph_id'];
		newEdges[index]['to'] = arrow['to']['graph_id'];
		newEdges[index]['graph_id'] = arrow['graph_id'];
	});
	edges = newEdges;
}

/**
* Function get_arrow_from_id
* Return  void
* This function return an arrow from a specific id
*/
MapController.prototype.get_arrow_from_id = function(id) {
	var self = this;
	
	var arrow = $.grep(edges, function(e) {
		if (e['graph_id'] == id) {
			return true;
		}
	});
	
	
	if (arrow.length == 0) {
		return null;
	}
	else {
		arrow = arrow[0];
		
		return self.get_arrow(arrow['to'], arrow['from']);
	}

}

/**
* Function get_arrows_from_edges
* Return  array[]
* This function returns a collection of arrows from edges (array)
*/
MapController.prototype.get_arrows_from_edges = function() {
	var self = this;
	
	var return_var = [];
	
	$.each(edges, function(i, e) {
		return_var.push(self.get_arrow_from_id(e['graph_id']));
	});
	
	return return_var;
}

/**
* Function get_arrow
* Return  void
* This function return a specific arrow
*/
MapController.prototype.get_arrow = function(id_to, id_from) {
	var arrow = {};
	
	arrow['nodes'] = {};
	
	var count_nodes = 0;
	$.each(nodes, function(i, node) {
		if (parseInt(node['graph_id']) == parseInt(id_to)) {
			arrow['nodes']['to'] = node;
			count_nodes++;
		}
		else if (parseInt(node['graph_id']) == parseInt(id_from)) {
			arrow['nodes']['from'] = node;
			count_nodes++;
		}
	});
	
	if (count_nodes == 2) {
		$.each(edges, function(i, edge) {
			if (edge['to'] == arrow['nodes']['to']['graph_id'] &&
				edge['from'] == arrow['nodes']['from']['graph_id']) {
				
				arrow['arrow'] = edge;
				
				return false; // Break
			}
		});
		
		return arrow;
	}
	else {
		return null;
	}
}

/**
* Function paint_toggle_button
* Return  void
* This function paints the hide/show button (minimap)
*/
MapController.prototype.paint_toggle_button = function(wait) {
	var self = this;
	
	if (typeof(wait) === "undefined")
		wait = 1;
	
	var count_files = 2;
	function wait_load(callback) {
		count_files--;
		
		if (count_files == 0) {
			callback();
		}
	}
	
	var map = d3.select(self._target + " svg");
	var minimap = d3.select(self._target + " .minimap");
	
	var transform = d3.transform();
	
	var minimap_transform = d3.transform(minimap.attr("transform"));
	
	switch (wait) {
		case 1:
			var toggle_minimap_button_layer = map.append("g")
				.attr("id", "toggle_minimap_button");
			
			if (is_buggy_firefox) {
				toggle_minimap_button_layer.append("g")
					.attr("class", "toggle_minimap_on")
					.append("use")
						.attr("xlink:href", "#toggle_minimap_on");
				
				arrow_layout.append("g")
					.attr("class", "toggle_minimap_off")
					.style("opacity", 0)
					.append("use")
						.attr("xlink:href", "#toggle_minimap_off");
				
				self.paint_toggle_button(0);
			}
			else {
				toggle_minimap_button_layer.append("g")
					.attr("class", "toggle_minimap_on")
					.append("use")
						.attr("xlink:href", "images/maps/toggle_minimap_on.svg#toggle_minimap_on")
						.on("load", function() {
							wait_load(function() {
								self.paint_toggle_button(0);
							});
						});
				
				toggle_minimap_button_layer.append("g")
					.attr("class", "toggle_minimap_off")
					.style("opacity", 0)
					.append("use")
						.attr("xlink:href", "images/maps/toggle_minimap_off.svg#toggle_minimap_off")
						.on("load", function() {
							wait_load(function() {
								self.paint_toggle_button(0);
							});
						});
			}
			break;
		case 0:
			var toggle_minimap_button_layer =
				d3.select(self._target + " #toggle_minimap_button");
		
			var toggle_minimap_button_layer_bbox =
				toggle_minimap_button_layer.node().getBBox();
			
			transform.translate[0] = minimap_transform.translate[0];
			transform.translate[1] = self.minimap_get_size()[1] - toggle_minimap_button_layer_bbox.height;
			
			toggle_minimap_button_layer.attr("transform", transform.toString());
			
			toggle_minimap_button_layer.on("click",
				function() {
					self.event_toggle_minimap();
				});
			break;
	}
}

/**
* Function event_toggle_minimap
* Return  void
* This function captures the minimap events
*/
MapController.prototype.event_toggle_minimap = function() {
	var self = this;
	
	var map_size = d3.select(self._target).node().getBoundingClientRect();
	
	var toggle_minimap_on = parseInt(d3
		.select(self._target + " .toggle_minimap_on").style("opacity"));
	
	var toggle_minimap_button_layer =
				d3.select(self._target + " #toggle_minimap_button");
	
	var transform_toggle_minimap_button = d3
		.transform(toggle_minimap_button_layer.attr("transform"));
	
	var toggle_minimap_button_layer_bbox =
				toggle_minimap_button_layer.node().getBBox();
	
	
	var minimap = d3.select(self._target + " .minimap");
	var minimap_transform = d3.transform(minimap.attr("transform"));
	
	switch (toggle_minimap_on) {
		case 0:
			transform_toggle_minimap_button
				.translate[0] = minimap_transform.translate[0];
			transform_toggle_minimap_button
				.translate[1] = self.minimap_get_size()[1] - toggle_minimap_button_layer_bbox.height;
			
			toggle_minimap_button_layer.attr("transform",
				transform_toggle_minimap_button);
			
			
			d3.select(self._target + " .minimap")
				.style("opacity", 1);
			
			d3.select(self._target + " .toggle_minimap_off")
				.style("opacity", 0);
			d3.select(self._target + " .toggle_minimap_on")
				.style("opacity", 1);
			break;
		case 1:
			transform_toggle_minimap_button.translate[0] =
				map_size.width - toggle_minimap_button_layer_bbox.width;
			transform_toggle_minimap_button.translate[1] = 0;
			
			toggle_minimap_button_layer.attr("transform",
				transform_toggle_minimap_button);
			
			
			d3.select(self._target + " .minimap")
				.style("opacity", 0);
			
			d3.select(self._target + " .toggle_minimap_off")
				.style("opacity", 1);
			d3.select(self._target + " .toggle_minimap_on")
				.style("opacity", 0);
			break;
	}
}

/**
* Function paint_minimap
* Return  void
* This function paints the minimap
*/
MapController.prototype.paint_minimap = function() {
	var self = this;
	
	var screen_size = d3.select(self._target).node().getBoundingClientRect();
	var map_size = d3.select(self._target + " .viewport").node().getBBox();
	
	var real_width = map_size.width + map_size.x;
	var real_height = map_size.height + map_size.y;
	
	var max_map = real_height;
	if (real_width > real_height)
		max_map = real_width;
	
	var max_screen = screen_size.height;
	if (screen_size.width > screen_size.height)
		max_screen = screen_size.width;
	
	self._relation = RELATION_MINIMAP * max_map / max_screen;
	
	var minimap_map_width = (map_size.width + map_size.x) / self._relation;
	var minimap_map_height = (map_size.height + map_size.y) / self._relation;
	
	var minimap = d3.select(self._target + " .minimap");
	var svg = d3.select(self._target + " svg");
	
	var transform = d3.transform();
	
	// Move the minimap to the right upper corner
	transform.translate[0] = screen_size.width - minimap_map_width;
	transform.translate[1] = 0;
	minimap.attr("transform", transform.toString());
	
	svg.append("defs")
		.append("clipPath")
			.attr("id", "clip_minimap")
			.append("rect")
				.attr("x", 0)
				.attr("y", 0)
				.attr("width", minimap_map_width)
				.attr("height", minimap_map_height);
	
	minimap
		.append("rect")
			.attr("x", 0)
			.attr("y", 0)
			.attr("width", minimap_map_width)
			.attr("height", minimap_map_height)
			.attr("style", "fill: #ffffff; stroke: #000000; stroke-width: 1;");
	
	transform = d3.transform();
	transform.scale[0] = 1 / self._relation;
	transform.scale[1] = 1 / self._relation;
	
	var minimap_layer = minimap
		.append("g")
			.attr("class", "clip_minimap")
			.attr("clip-path", "url(#clip_minimap)")
			.append("g")
				.attr("class", "map")
				.attr("transform", transform.toString());
	
	minimap_layer.append("rect")
		.attr("class", "viewport")
		.attr("style", "fill: #dddddd; stroke: #aaaaaa; stroke-width: 1;")
		.attr("x", 0)
		.attr("y", 0)
		.attr("height", screen_size.height)
		.attr("width", screen_size.width);
	
	self.paint_toggle_button();
	
	self.paint_items_minimap();
	
	self.zoom_minimap();
}

/**
* Function paint_items_minimap
* Return  void
* This function paints the minimap items
*/
MapController.prototype.paint_items_minimap = function() {
	var self = this;
	var minimap_viewport =  d3.select(self._target + " .minimap .map");
	
	minimap_viewport.selectAll(".node")
		.data(
			nodes
				.filter(function(d, i) {
					return self.filter_only_agents(d);
				}))
			.enter()
				.append("g")
					.attr("transform",
						function(d) {
							var x = d['x'];
							var y = d['y'];
							
							return "translate(" + x + " " + y + ")";
						})
					.attr("class", "node")
					.attr("id", function(d) { return "node_" + d['graph_id'];})
					.attr("data-id", function(d) { return d['id'];})
					.attr("data-graph_id", function(d) { return d['graph_id'];})
					.attr("data-type", function(d) { return d['type'];})
					.append("circle")
						.attr("style", "fill: rgb(50, 50, 128);")
						.attr("x", 0)
						.attr("y", 0)
						.attr("r", 30);
}

/**
* Function zoom_minimap
* Return  void
* This function apply zoom in minimap
*/
MapController.prototype.zoom_minimap = function() {
	var self = this;
	
	var viewport_transform = d3.transform(
		d3.select(self._target + " .viewport").attr("transform"));
	
	var transform = d3.transform();
	
	var minimap_viewport =  d3.select(self._target + " .minimap .viewport");
	
	transform.translate[0] = -viewport_transform.translate[0];
	transform.translate[1] = -viewport_transform.translate[1];
	
	transform.scale[0] = 1 / viewport_transform.scale[0];
	transform.scale[1] = 1 / viewport_transform.scale[1];
	
	minimap_viewport
		.attr("transform", transform.toString());
}

/**
* Function node_from_edge
* Return node
* This function returns the node with the specific id_graph
*/
MapController.prototype.node_from_edge = function(id_graph) {
	var exists = null;
	
	$.each(edges, function(i, e) {
		if (e.graph_id == id_graph) {
			exists = i;
			return false; // jquery.each break;
		}
	});
	
	if (exists !== null)
		return edges[exists];
	else
		return null;
}

/**
* Function exists_edge
* Return bool
* This function returns if the node exist
*/
MapController.prototype.exists_edge = function(id_graph) {
	var exists = false;
	
	$.each(edges, function(i, e) {
		if (e.graph_id == id_graph) {
			exists = true;
			return false; // jquery.each break;
		}
	});
	
	return exists; 
}

/**
* Function paint_arrows
* Return  void
* This function paints the arrows
*/
MapController.prototype.paint_arrows = function() {
	var self = this;
	
	var arrow_layouts = self._viewport.selectAll(".arrow")
		.data(
			nodes
				.filter(function(d, i) {
					if (d.type == ITEM_TYPE_EDGE_NETWORKMAP) {
						if (self.exists_edge(d['graph_id']))
							return true;
						else
							return false;
					}
					else
						return false;
				}))
		.enter()
			.append("g")
				.attr("class", "arrow")
				.attr("id", function(d) { return "arrow_" + d['graph_id'];})
				
				.attr("data-id", function(d) { return d['id'];})
				.attr("data-to", function(d) {
					return self.node_from_edge(d['graph_id'])["to"];})
				.attr("data-from", function(d) {
					return self.node_from_edge(d['graph_id'])["from"];});
	
	create_arrow(arrow_layouts);
	
	/**
	* Function create_arrow
	* Return void
	* This function creates the arrow
	*/
	function create_arrow(arrow_layouts) {
		
		arrow_layouts.each(function(d) {
			var arrow_layout = this;
			
			var node_arrow = edges.filter(function(d2) {
				if (d2['graph_id'] == d['graph_id'])
					return true;
				else
					return false;
			})[0];
			
			var to_node = nodes.filter(function(d2) {
				if (d2['graph_id'] == node_arrow['to'])
					return true;
				else
					return false;
			})[0];
			
			var from_node = nodes.filter(function(d2) {
				if (d2['graph_id'] == node_arrow['from'])
					return true;
				else
					return false;
			})[0];
			
			var id_arrow = d3.select(arrow_layout).attr("id");
			var id_node_to = "node_" + to_node['graph_id'];
			var id_node_from = "node_" + from_node['graph_id'];
			
			
			self.arrow_by_pieces(self._target + " svg", id_arrow,
				id_node_to, id_node_from);
		});
	}
}

/**
* Function paint_nodes
* Return void
* This function paint the nodes
*/
MapController.prototype.paint_nodes = function() {
	var self = this;
	
	self._viewport.selectAll(".node")
		.data(
			nodes
				.filter(function(d, i) {
					return self.filter_only_agents(d);
				}))
			.enter()
				.append("g")
					.attr("transform",
						function(d) { return "translate(" + d['x'] + " " + d['y'] + ")";})
					.attr("class", "draggable node")
					.attr("id", function(d) { return "node_" + d['graph_id'];})
					.attr("style", "fill: rgb(50, 50, 128);")
					.attr("data-id", function(d) { return d['id'];})
					.attr("data-graph_id", function(d) { return d['graph_id'];})
					.attr("data-type", function(d) { return d['type'];})
					.append("rect")
						.attr("style", "")
						.attr("x", 0)
						.attr("y", 0)
						.attr("height", 30)
						.attr("width", 30);
}

/**
* Function move_arrow
* Return void
* This function moves the arrow
*/
MapController.prototype.move_arrow = function (id_from_any_point_arrow) {
	var self = this;
	
	var arrows = d3.selectAll(self._target + " .arrow").filter(function(d2) {
		if (
			(d3.select(this).attr("data-to") == id_from_any_point_arrow) ||
			(d3.select(this).attr("data-from") == id_from_any_point_arrow)
			) {
			
			return true;
		}
		return false;
	});
	
	arrows.each(function(d) {
		self.arrow_by_pieces(self._target + " svg", d, 0);
	});
}

/**
* Function remove_resize_square
* Return void
* This function removes squares resize
*/
MapController.prototype.remove_resize_square = function(item, wait) {
	var self = this;
	
	d3.select(self._target + " svg #resize_square").remove();
}

/**
* Function positioning_resize_square
* Return void
* This function positioning the square to resize the element
*/
MapController.prototype.positioning_resize_square = function(item) {
	var self = this;
	
	var resize_square = d3.select(self._target + " #resize_square");
	var item_d3 = d3.select(self._target + " #node_" + item['graph_id']);
	
	var bbox_item = item_d3.node().getBBox();
	var bbox_square = resize_square.node().getBBox();
	var transform_item = d3.transform(item_d3.attr("transform"));
	var transform_viewport = d3
		.transform(d3.select(self._target + " .viewport").attr("transform"));
	
	var transform = d3.transform();
	
	var x = (bbox_item.x +
			transform_item.translate[0] +
			transform_viewport.translate[0]
		) * transform_viewport.scale[0];
	var y = (bbox_item.y +
			transform_item.translate[1] +
			transform_viewport.translate[1]
		) * transform_viewport.scale[1];
	
	x = (bbox_item.x +
		transform_item.translate[0]) * transform_viewport.scale[0] +
		transform_viewport.translate[0];
	y = (bbox_item.y +
		transform_item.translate[1]) * transform_viewport.scale[1] +
		transform_viewport.translate[1];
	
	transform.translate[0] = x;
	transform.translate[1] = y;
	
	resize_square
		.attr("transform", transform.toString());
	
	var real_item_width = (bbox_item.width * transform_item.scale[0]);
	var real_item_height = (bbox_item.height * transform_item.scale[1]);
	
	d3.select("#resize_square .square rect")
		.attr("width",
			(real_item_width * transform_viewport.scale[0]));
	d3.select("#resize_square .square rect")
		.attr("height",
			(real_item_height * transform_viewport.scale[1]));
	
	// Set the handlers
	var bbox_handler = d3
		.select(self._target + " .handler").node().getBBox();
	
	var handler_positions = {};
	handler_positions['N'] = [];
	handler_positions['N'][0] = (real_item_width  * transform_viewport.scale[0] / 2)
		- (bbox_handler.width / 2);
	handler_positions['N'][1] = 0 - (bbox_handler.height / 2);
	
	handler_positions['NE'] = [];
	handler_positions['NE'][0] = (real_item_width  * transform_viewport.scale[0])
		- (bbox_handler.width / 2);
	handler_positions['NE'][1] = handler_positions['N'][1];
	
	handler_positions['E'] = [];
	handler_positions['E'][0] = handler_positions['NE'][0];
	handler_positions['E'][1] = (real_item_height  * transform_viewport.scale[1] / 2)
		- (bbox_handler.height / 2);
	
	handler_positions['SE'] = [];
	handler_positions['SE'][0] = handler_positions['NE'][0];
	handler_positions['SE'][1] = (real_item_height  * transform_viewport.scale[1])
		- (bbox_handler.height / 2);
	
	handler_positions['S'] = [];
	handler_positions['S'][0] = handler_positions['N'][0];
	handler_positions['S'][1] = handler_positions['SE'][1];
	
	handler_positions['SW'] = [];
	handler_positions['SW'][0] = 0 - (bbox_handler.width / 2);
	handler_positions['SW'][1] = handler_positions['SE'][1];
	
	handler_positions['W'] = [];
	handler_positions['W'][0] = handler_positions['SW'][0];
	handler_positions['W'][1] = handler_positions['E'][1];
	
	handler_positions['NW'] = [];
	handler_positions['NW'][0] = handler_positions['SW'][0];
	handler_positions['NW'][1] = handler_positions['N'][1];
	
	d3.selectAll(" .handler").each(function(d) {
		var transform = d3.transform();
		
		transform.translate[0] = handler_positions[d][0];
		transform.translate[1] = handler_positions[d][1];
		
		d3.select(self._target + " .handler_" + d)
			.attr("transform", transform.toString());
	});
}

/**
* Function paint_resize_square
* Return void
* This function paints a square to resize the elements
*/
MapController.prototype.paint_resize_square = function(item, wait) {
	var self = this;
	
	if (typeof(wait) === "undefined")
		wait = 1;
	
	var count_files = 16;
	function wait_load(callback) {
		count_files--;
		
		if (count_files == 0) {
			callback();
		}
	}
	
	switch (wait) {
		/*---------------------------------------------------*/
		/*--------------- Prepare the square ----------------*/
		/*---------------------------------------------------*/
		case 1:
			var resize_square = d3
				.select(self._target + " svg")
					.append("g").attr("id", "resize_square")
					.style("opacity", 0);
			
			d3.xml("images/maps/square_selection.svg", "application/xml", function(xml) {
				var nodes = xml
					.evaluate("//*[@id='square_selection']/*", xml, null, XPathResult.ANY_TYPE, null);
				var result = nodes.iterateNext();
				
				resize_square
					.append("g").attr("class", "square_selection")
					.append(function() {return result});
				
				if (is_buggy_firefox) {
					resize_square
						.append("g").attr("class", "handles")
							.selectAll(".handle")
							.data(["N", "NE", "E", "SE", "S", "SW", "W", "NW"])
							.enter()
								.append("g")
									.attr("class", function(d) { return "handler handler_" + d;})
									.append("use")
										.attr("xlink:href", "images/maps/resize_handle.svg#resize_handle")
										.attr("class", function(d) { return "handler " + d;});
					
					self.paint_resize_square(item, 0);
				}
				else {
					var handles = resize_square
						.append("g").attr("class", "handles");
						
					handles.selectAll(".handle")
						.data(["N", "NE", "E", "SE", "S", "SW", "W", "NW"])
						.enter()
							.append("g")
							.attr("class", function(d) { return "handler handler_" + d;});
					
					handles.selectAll(".handler").each(function(d) {
						d3.select(this)
							.append("use")
								.style("opacity", 1)
								.attr("class", "default")
								.attr("xlink:href", "images/maps/resize_handle.svg#resize_handle")
								.on("load", function() {
									wait_load(function() {
										self.paint_resize_square(
											item, 0);
									});
								});
						
						d3.select(this)
							.append("use")
								.style("opacity", 0)
								.attr("class", "over")
								.attr("xlink:href", "images/maps/resize_handle.over.svg#resize_handle_over")
								.on("load", function() {
									wait_load(function() {
										self.paint_resize_square(
											item, 0);
									});
								});
					});
				}
				
			});
			break;
		/*---------------------------------------------------*/
		/*-------- Paint and positioning the square ---------*/
		/*---------------------------------------------------*/
		case 0:
			self.positioning_resize_square(item);
			
			d3.selectAll(" .handler").each(function(d) {
				var drag = d3.behavior.drag()
					.origin(function(d) { return d; })
					.on("dragstart", function(d) {
						self.event_resize("dragstart", item, d);
					})
					.on("drag", function(d) {
						self.event_resize("drag", item, d);
					})
					.on("dragend", function(d) {
						self.event_resize("dragend", item, d);
					});
				
				d3.select(this).call(drag);
				
				d3.select(this)
					.on("mouseover", function(d) {
						self.change_handler_image("mouseover", d);
					})
					.on("mouseout", function(d) {
						self.change_handler_image("mouseout", d);
					});
			});
			
			var resize_square = d3.select(self._target + " #resize_square");
			
			resize_square.style("opacity", 1);
			break;
	}
}

/**
* Function change_handler_image
* Return void
* This function changes the handler image
*/
MapController.prototype.change_handler_image = function(action, handler, wait) {
	var self = this;
	
	var handlers_d3 = d3.select(self._target + " .handles");
	var handler_d3 = d3.select(self._target + " .handler_" + handler);
	
	switch (action) {
		case "mouseover":
			handler_d3.select(".default").style("opacity", 0);
			handler_d3.select(".over").style("opacity", 1);
			break;
		case "mouseout":
			handler_d3.select(".default").style("opacity", 1);
			handler_d3.select(".over").style("opacity", 0);
			break;
	}
}

/**
* Function event_resize
* Return void
* This function manages the resize event
*/
MapController.prototype.event_resize = function(action, item, handler) {
	var self = this;
	
	d3.event.sourceEvent.stopPropagation();
	d3.event.sourceEvent.preventDefault();
	
	var self = this;
	
	var handler_d3 = d3.select(self._target + " .handler_" + handler);
	
	switch (action) {
		case "dragstart":
			handler_d3.classed("dragging", true);
			
			self.save_size_item(item);
			break;
		case "drag":
			var delta_x = d3.event.dx;
			var delta_y = d3.event.dy;
			
			switch (handler) {
				case "N":
				case "S":
					delta_x = 0;
					break;
				case "E":
				case "W":
					delta_y = 0;
					break;
			}
			
			self.resize_node(item, handler, delta_x, delta_y);
			
			self.positioning_resize_square(item);
			break;
		case "dragend":
			handler_d3.classed("dragging", false);
			break;
	}
}

/**
* Function save_size_item
* Return void
* This function saves the actual size of the element (node)
*/
MapController.prototype.save_size_item = function(item) {
	var self = this;
	
	var item_d3 = d3.select(self._target + " #node_" + item['graph_id']);
	var item_transform = d3.transform(item_d3.attr("transform"));
	var item_bbox = item_d3.node().getBBox();
	
	var width = item_bbox.width * item_transform.scale[0];
	var height = item_bbox.height * item_transform.scale[1];
	
	item_d3.attr("data-original_width", parseFloat(width));
	item_d3.attr("data-original_height", parseFloat(height));
}

/**
* Function resize_node
* Return void
* This function do the process to resize the element (node)
*/
MapController.prototype.resize_node = function(item, handler, delta_x, delta_y) {
	var self = this;
	
	var item_d3 = d3.select(self._target + " #node_" + item['graph_id']);
	var item_transform = d3.transform(item_d3.attr("transform"));
	var item_bbox = item_d3.node().getBBox();
	var transform_viewport =
		d3.transform(d3.select(self._target + " .viewport").attr("transform"));
	
	var inc_w = delta_x * transform_viewport.scale[0];
	var inc_h = delta_y * transform_viewport.scale[1];
	
	var width = item_d3.attr("data-width");
	var height = item_d3.attr("data-height");

	if (width == null) {
		width = old_width = item_d3.attr("data-original_width");
		height = old_height = item_d3.attr("data-original_height");
	}
	
	old_width = parseFloat(old_width);
	old_height = parseFloat(old_height);
	width = parseFloat(width);
	height = parseFloat(height);
	
	var new_width;
	var new_height;
	
	var scale_x;
	var scale_y;

	switch (handler) {
		case "SE":
		case "E":
		case "S":
			new_width = width + inc_w;
			new_height = height + inc_h;
			if ((new_width < 0) || (new_height < 0)) {
				if ((new_width < 0) && (new_height < 0)) {
					new_width = Math.abs(new_width);
					new_height = Math.abs(new_height);
				}
				else if (new_width < 0) {
					new_width = Math.abs(new_width);
				}
				else {
					new_height = Math.abs(new_height);
				}
			}
			break;
		case "N":
		case "NE":
			new_width = width + inc_w;
			new_height = height - inc_h;
			if ((new_width < 0) || (new_height < 0)) {
				if ((new_width < 0) && (new_height < 0)) {
					new_width = Math.abs(new_width);
					new_height = Math.abs(new_height);
				}
				else if (new_width < 0) {
					new_width = Math.abs(new_width);
				}
				else {
					new_height = Math.abs(new_height);
				}
			}
			else {
				item_transform.translate[1] += inc_h;
			}
			break;
		case "NW":
		case "W":
			new_width = width - inc_w;
			new_height = height - inc_h;
			if ((new_width < 0) || (new_height < 0)) {
				if ((new_width < 0) && (new_height < 0)) {
					new_width = Math.abs(new_width);
					new_height = Math.abs(new_height);
				}
				else if (new_width < 0) {
					new_width = Math.abs(new_width);
				}
				else {
					new_height = Math.abs(new_height);
				}
			}
			else {
				item_transform.translate[0] += inc_w;
				item_transform.translate[1] += inc_h;
			}
			break;
		case "SW":
			new_width = width - inc_w;
			new_height = height + inc_h;
			if ((new_width < 0) || (new_height < 0)) {
				if ((new_width < 0) && (new_height < 0)) {
					new_width = Math.abs(new_width);
					new_height = Math.abs(new_height);
				}
				else if (new_width < 0) {
					new_width = Math.abs(new_width);
				}
				else {
					new_height = Math.abs(new_height);
				}
			}
			else {
				item_transform.translate[0] += inc_w;
			}
			break;
	}
	
	scale_x = new_width / old_width;
	scale_y = new_height / old_height;
	
	item_transform.scale[0] = scale_x;
	item_transform.scale[1] = scale_y;
	
	item_d3.attr("transform", item_transform.toString());
	
	item_d3.attr("data-width", parseFloat(new_width));
	item_d3.attr("data-height", parseFloat(new_height));
	
	
	self.move_arrow(item["graph_id"]);
}

/**
* Function init_events
* Return boolean
* This function init click events in the map
*/
MapController.prototype.init_events = function(principalObject) {
	var self = this;
	
	d3.select("body")
		.on("keydown", function() {
			// ctrl key
			if (d3.event.keyCode === CONTROL_KEY) {
				self.multiple_selection_start();
			}
		})
		.on("keyup", function() {
			if (d3.event.keyCode === CONTROL_KEY) {
				self.multiple_selection_end();
			}
		});
	
	var node_menu = [
		{
			title: 'Show details',
			action: function(elm, d, i) {
				var nodeTarget = $(elm);
				var type = parseInt(nodeTarget.data("type"));
				if (type == 0) {
					self.nodeGetDetails(self, elm);
				}
			}
		},
		{
			title: 'Resize',
			action: function(elm, d, i) {
				self.paint_resize_square(d);
			}
		},
		{
			title: 'Edit',
			action: function(elm, d, i) {
				self.editNode(self, elm);
			}
		},
		{
			title: 'Set as children',
			action: function(elm, d, i) {
				self._last_event = null;
				self._relationship_in_progress_type = "children";
				self.set_as_children();
			}
		},
		{
			title: 'Set as parent',
			action: function(elm, d, i) {
				self._last_event = null;
				self._relationship_in_progress_type = "parent";
				self.set_as_parent();
			}
		},
		{
			title: 'Delete',
			action: function(elm, d, i) {
				self.deleteNode(self, elm);
			}
		}
	];
	
	var map_menu = [
		{
			title: 'Edit map',
			action: function(elm, d, i) {
				self.editMap(self, elm);
			}
		},
		{
			title: 'Save map',
			action: function(elm, d, i) {
				console.log('Save map!!');
			}
		}
	];
	
	d3.select("#map").on("contextmenu", d3.contextMenu(map_menu));
	
	d3.selectAll(".node")
		.on("mouseover", function(d) {
			self.select_node(d['graph_id'], "over");
		})
		.on("mouseout", function(d) {
			if (self.last_event != "drag") {
				var status_selection = self.get_status_selection_node(d['graph_id']);
				
				self.select_node(d['graph_id'], "off");
				if (status_selection.indexOf("select") != -1) {
					self.select_node(d['graph_id'], "select");
				}
			}
			
			self.last_event = null;
		})
		.on("click", function(d) {
			if (self.last_event == "relationship") {
				self.last_event = null;
				return;
			}
			
			if (d3.event.button != 0) {
				d3.event.stopPropagation();
				d3.event.preventDefault();
			}
			
			if (d3.event.defaultPrevented) return;
			
			self.tooltip_map_create(self, this);
		})
		.on("contextmenu", d3.contextMenu(node_menu, function(node) {
			self._last_event = "contextmenu";
			self.select_node(node['graph_id'], "select");
		}));
	
	var drag = d3.behavior.drag()
		.origin(function(d) { return d; })
		.on("dragstart", dragstarted)
		.on("drag", dragged)
		.on("dragend", dragended);
	
	d3.selectAll(".draggable").call(drag);
	
	
	d3.select(self._target + " svg").on("mousedown",
		function() {
			if (self._flag_multiple_selection) {
				
				self.multiple_selection_dragging(
					d3.event.offsetX,
					d3.event.offsetY, true);
			}
			
			d3.event.stopPropagation();
			d3.event.preventDefault();
		});
	
	d3.select(self._target + " svg").on("mouseup",
		function(d) {
			if (!self._flag_multiple_selection) {
				if (self.last_event != "zoom") {
					self.last_event = null;
					
					if (self._relationship_in_progress) {
						
						var found_id = null;
						for (i in d3.event.path) {
							var item = d3.event.path[i];
							
							if (item == document)
								continue;
							if (typeof(d3.select(item).node().tagName) == "undefined")
								continue;
							
							if (d3.select(item).classed("node")) {
								found_id = d3.select(item).attr("data-graph_id");
							}
						}
						
						if (found_id !== null) {
							self.apply_temp_arrows(found_id);
						}
						else {
							self.remove_temp_arrows();
						}
						
						
						self.last_event = "relationship";
					}
					
					self.remove_selection_nodes();
				}
				else {
					self.last_event = null;
				}
			}
		});
	
	d3.select(self._target + " svg").on("mousemove",
		function() {
			if (self._flag_multiple_selection && self._start_multiple_selection) {
				self.multiple_selection_dragging(
					d3.event.offsetX,
					d3.event.offsetY, false);
			}
		});
	
	d3.select(document).on("mousemove",
		function() {
			var map_pos = d3.select(self._target).node().getBoundingClientRect();
			
			var x = d3.event.pageX - map_pos.left;
			var y = d3.event.pageY - map_pos.top;;
			
			self._last_mouse_position = [x, y];
			
			if (self._relationship_in_progress) {
				$.each(nodes, function(i, node) {
					if (node.type != ITEM_TYPE_AGENT_NETWORKMAP)
						return 1; // Continue
					
					var status_selection =
						self.get_status_selection_node(node.graph_id);
					
					if (status_selection.indexOf("select") == -1) {
						return 1; // Continue
					}
					
					self._relationship_in_progress = true;
					self.move_temp_arrows(node,
						self._relationship_in_progress_type);
				});
			}
		});
	
	/**
	* Function dragstarted
	* Return void
	*/
	function dragstarted(d) {
		if (d3.event.sourceEvent.button == 0) {
			d3.event.sourceEvent.stopPropagation();
			d3.event.sourceEvent.preventDefault();
		}
		
		if (self._relationship_in_progress) {
			return;
		}
		
		if ($("#node_" + d['graph_id']).hasClass("tooltipstered")) {
			$("#node_" + d['graph_id']).tooltipster('destroy');
		}
		
		self.remove_resize_square();
		
		var status_selection = self.get_status_selection_node(d['graph_id']);
		
		if (status_selection.indexOf("select") == -1) {
			self.remove_selection_nodes();
		}
		
		self.select_node(d['graph_id'], "select");
	}
	
	/**
	* Function dragged
	* Return void
	*/
	function dragged(d) {
		var delta_x = d3.event.dx;
		var delta_y = d3.event.dy;
		
		self.last_event = "drag";
		
		$.each(nodes, function(i, node) {
			if (node.type != ITEM_TYPE_AGENT_NETWORKMAP)
				return 1; // Continue
			
			var status_selection =
				self.get_status_selection_node(node.graph_id);
			
			if (status_selection.indexOf("select") == -1) {
				return 1; // Continue
			}
			
			var d3_node = d3.select(self._target + " #node_" + node.graph_id);
			
			var transform = d3.transform(d3_node.attr("transform"));
			
			transform.translate[0] += delta_x;
			transform.translate[1] += delta_y;
			
			nodes[i].x = transform.translate[0];
			nodes[i].y = transform.translate[1];
			
			d3.select(".minimap #node_" + node.graph_id)
				.attr("transform", transform.toString());
			
			d3_node.attr("transform", transform.toString());
			
			self.move_arrow(node.graph_id);
		});
	}
	
	/**
	* Function dragended
	* Return void
	*/
	function dragended(d) {
		if (self._last_event != "contextmenu") {
			self._last_event = null;
			
			self.select_node(d['graph_id'], "off");
			
			if ($("#node_" + d['graph_id']).hasClass("tooltipstered")) {
				$("#node_" + d['graph_id']).tooltipster('destroy');
			}
			
			self.remove_resize_square();
		}
	}
}

/**
* Function set_as_parent
* Return void
* This function sets a node as a parent
*/
MapController.prototype.set_as_parent = function() {
	var self = this;
	
	self.start_relationship_nodes("parent");
}

/**
* Function set_as_children
* Return void
* This function sets a node as a children
*/
MapController.prototype.set_as_children = function() {
	var self = this;
	
	self.start_relationship_nodes("children");
}

/**
* Function start_relationship_nodes
* Return void
* This function starts the relation nodes function
*/
MapController.prototype.start_relationship_nodes = function(type) {
	var self = this;
	
	$.each(nodes, function(i, node) {
		if (node.type != ITEM_TYPE_AGENT_NETWORKMAP)
			return 1; // Continue
		
		var status_selection =
			self.get_status_selection_node(node.graph_id);
		
		if (status_selection.indexOf("select") == -1) {
			return 1; // Continue
		}
		
		self._relationship_in_progress = true;
		self.show_temp_arrows(node, type);
	});
}

/**
* Function show_temp_arrows
* Return void
* This function shows temporal arrows to parent-children relation
*/
MapController.prototype.show_temp_arrows = function(node, type) {
	var self = this;
	
	// Apply the zoom and panning
	var zoom = d3.transform(
		d3.select(self._target + " .viewport").attr("transform"));
	
	var x = self._last_mouse_position[0]/ zoom.scale[0]
		- zoom.translate[0] / zoom.scale[0];
		
	var y = self._last_mouse_position[1]/ zoom.scale[1]
		- zoom.translate[1] / zoom.scale[1];
	
	var temp_arrow = {};
	temp_arrow['graph_id'] = "temp_" + node.graph_id;
	temp_arrow['mouse'] = [x, y];
	temp_arrow['temp'] = 1;
	temp_arrow['type'] = type;
	temp_arrow['from'] = {};
	temp_arrow['to'] = {};
	
	if (d3.select(self._target + " #arrow_temp_" + node.graph_id).empty()) {
		self._viewport
		.append("g")
			.attr("class", "arrow")
			.attr("id", function(d) {
				return "arrow_temp_" + node.graph_id;})
			.attr("data-id", function(d) { return node.graph_id;})
			.attr("data-temp", 1);
	}
	
	
	
	
	switch (type) {
		case 'parent':
			temp_arrow['from']['graph_id'] = node.graph_id;
			temp_arrow['to']['graph_id'] = null;
			break;
		case 'children':
			temp_arrow['from']['graph_id'] = null;
			temp_arrow['to']['graph_id'] = node.graph_id;
			break;
	}
	
	
	self.arrow_by_pieces(self._target + " svg", temp_arrow);
}

MapController.prototype.move_temp_arrows = function(node, type) {
	var self = this;
	
	// Apply the zoom and panning
	var zoom = d3.transform(
		d3.select(self._target + " .viewport").attr("transform"));
	
	var x = self._last_mouse_position[0]/ zoom.scale[0]
		- zoom.translate[0] / zoom.scale[0];
		
	var y = self._last_mouse_position[1]/ zoom.scale[1]
		- zoom.translate[1] / zoom.scale[1];
	
	var temp_arrow = {};
	temp_arrow['graph_id'] = "temp_" + node.graph_id;
	temp_arrow['mouse'] = [x, y];
	temp_arrow['temp'] = 1;
	temp_arrow['type'] = type;
	temp_arrow['from'] = {};
	temp_arrow['to'] = {};
	
	switch (type) {
		case 'parent':
			temp_arrow['from']['graph_id'] = node.graph_id;
			temp_arrow['to']['graph_id'] = null;
			break;
		case 'children':
			temp_arrow['from']['graph_id'] = null;
			temp_arrow['to']['graph_id'] = node.graph_id;
			break;
	}
	
	
	self.arrow_by_pieces(self._target + " svg", temp_arrow, 0);
}

MapController.prototype.remove_temp_arrows = function() {
	var self = this;
	
	d3.selectAll(self._target + " .arrow")
		.filter(function(d, i) {
			if (d3.select(this).attr("data-temp") == 1) return true;
			else return false;
		})
		.remove();
	
	self._relationship_in_progress = false;
	self._relationship_in_progress_type = null;
}

MapController.prototype.apply_temp_arrows = function(target_id) {
	var self = this;
	
	$.each(nodes, function(i, node) {
		if (node.type != ITEM_TYPE_AGENT_NETWORKMAP)
			return 1; // Continue
		
		var status_selection =
			self.get_status_selection_node(node.graph_id);
		
		if (status_selection.indexOf("select") == -1) {
			return 1; // Continue
		}
		
		switch (self._relationship_in_progress_type) {
			case 'parent':
				self.make_arrow(node.graph_id, target_id);
				break;
			case 'children':
				self.make_arrow(target_id, node.graph_id);
				break;
		}
	});
	
	self.remove_temp_arrows();
	self.paint_arrows();
}

MapController.prototype.make_arrow = function(from_id, to_id) {
}

/**
* Function get_status_selection_node
* Return node status
* This function returns the status of a node
*/
MapController.prototype.get_status_selection_node = function(id_node) {
	var self = this;
	
	var status = d3.select(self._target + " #node_" + id_node)
		.attr("data-select");
	
	if (status == null) {
		return [];
	}
	else {
		return status.split(" ");
	}
}

/**
* Function multiple_selection_start
* Return void
* This function init multiple selection mode
*/
MapController.prototype.multiple_selection_start = function() {
	var self = this;
	
	self._flag_multiple_selection = true;
	
	if (!self._cache_files.hasOwnProperty("selection_box")) {
		var selection_box = d3
			.select(self._target + " svg")
				.append("g").attr("id", "selection_box")
				.style("opacity", 0);
		
		d3.xml("images/maps/selection_box.svg", "application/xml", function(xml) {
			var nodes = xml
				.evaluate("//*[@id='selection_box']/*", xml, null,
					XPathResult.ANY_TYPE, null);
			
			self._cache_files["selection_box"] = nodes.iterateNext();
			
			self.multiple_selection_start();
		});
	}
	else {
		var selection_box = d3
			.select(self._target + " #selection_box");
		
		selection_box
			.append(function() {
				return self._cache_files["selection_box"];
			});
	}
}

/**
* Function multiple_selection_dragging
* Return void
* This function init multiple selection drag
*/
MapController.prototype.multiple_selection_dragging = function(x, y, first) {
	var self = this;
	
	var selection_box = d3
		.select(self._target + " #selection_box");
	
	var transform = d3.transform();
	
	if (first) {
		transform.translate[0] = x;
		transform.translate[1] = y;
		
		selection_box.attr("transform", transform.toString());
		selection_box.style("opacity", 1);
		
		selection_box.select("rect")
			.attr("width", 0);
		selection_box.select("rect")
			.attr("height", 0);
		
		selection_box.attr("data-ini_x", x);
		selection_box.attr("data-ini_y", y);
	}
	else {
		var delta_x = x - parseInt(selection_box.attr("data-ini_x"));
		var delta_y = y - parseInt(selection_box.attr("data-ini_y"));
		
		if (delta_x < 0) {
			transform = d3.transform(selection_box.attr("transform"));
			
			transform.translate[0] = x;
			
			selection_box.attr("transform", transform.toString());
			
			selection_box.select("rect")
				.attr("width", -delta_x);
		}
		else {
			selection_box.select("rect")
				.attr("width", delta_x);
		}
		
		if (delta_y < 0) {
			transform = d3.transform(selection_box.attr("transform"));
			
			transform.translate[1] = y;
			
			selection_box.attr("transform", transform.toString());
			
			selection_box.select("rect")
				.attr("height", -delta_y);
		}
		else {
			selection_box.select("rect")
				.attr("height", delta_y);
		}
		
		self.multiple_selection_select_nodes();
	}
}

/**
* Function multiple_selection_end
* Return void
* This function ends multiple selection mode
*/
MapController.prototype.multiple_selection_end = function() {
	var self = this;
	
	self._flag_multiple_selection = false;
	
	var selection_box = d3
		.select(self._target + " #selection_box");
	
	selection_box.style("opacity", 0);
	selection_box.select("rect")
		.attr("width", 0);
	selection_box.select("rect")
		.attr("height", 0);
}

/**
* Function multiple_selection_select_nodes
* Return void
* This function gets the nodes in the selection zone
*/
MapController.prototype.multiple_selection_select_nodes = function() {
	var self = this;
	
	var selection_box = d3
		.select(self._target + " #selection_box");
	var transform = d3.transform(selection_box.attr("transform"));
	var selection_box_dimensions = {};
	selection_box_dimensions["x"] = transform.translate[0];
	selection_box_dimensions["y"] = transform.translate[1];
	selection_box_dimensions["width"] = selection_box.select("rect")
		.attr("width");
	selection_box_dimensions["height"] = selection_box.select("rect")
		.attr("height");
	
	// Apply the zoom and panning
	var zoom = d3.transform(
		d3.select(self._target + " .viewport").attr("transform"));
	
	
	selection_box_dimensions["x"] = (selection_box_dimensions["x"] / zoom.scale[0]
		- zoom.translate[0] / zoom.scale[0]);
	selection_box_dimensions["y"] = (selection_box_dimensions["y"] / zoom.scale[1]
		- zoom.translate[1] / zoom.scale[1]);
	
	selection_box_dimensions["width"] =
		selection_box_dimensions["width"] / zoom.scale[0];
	selection_box_dimensions["height"] =
		selection_box_dimensions["height"] / zoom.scale[1];
	
	$.each(nodes, function(i, node) {
		if (node.type != ITEM_TYPE_AGENT_NETWORKMAP)
			return 1; // Continue
		
		var x = node.x;
		var y = node.y;
		
		var node_bbox = null;
		
		var width;
		if (!node.hasOwnProperty("width")) {
			node_bbox =
				d3.select(self._target + " #node_" + node.graph_id).node().getBBox()
			
			width = node_bbox['x'] + node_bbox['width'];
			
			nodes[i].width = width;
		}
		else {
			width = node.width;
		}
		
		var height;
		if (!node.hasOwnProperty("height")) {
			if (node_bbox === null) {
				node_bbox =
					d3.select(self._target + " #node_" + node.graph_id).node().getBBox()
			}
			
			height = node_bbox['y'] + node_bbox['height'];
			
			nodes[i].height = height;
		}
		else {
			height = node.height;
		}
		
		if (
			(x >= selection_box_dimensions["x"]) &&
			(y >= selection_box_dimensions["y"]) &&
			((x + width) <= (selection_box_dimensions["x"] + selection_box_dimensions["width"])) &&
			((y + height) <= (selection_box_dimensions["y"] + selection_box_dimensions["height"]))
			) {
			
			self.select_node(node.graph_id, "select");
		}
	});
}

/**
* Function remove_selection_nodes
* Return void
* This function removes the selection
*/
MapController.prototype.remove_selection_nodes = function() {
	var self = this;
	
	$.each(nodes, function(i, node) {
		if (node.type != ITEM_TYPE_AGENT_NETWORKMAP)
			return 1; // Continue
		
		self.select_node(node.graph_id, "off");
	});
}

/**
* Function select_node
* Return void
* This function gets the node eith selection mode
*/
MapController.prototype.select_node = function(node_id, type) {
	var self = this;
	
	d3.select(self._target + " #node_" + node_id)
		.classed("over", false);
	
	var data = "";
	switch (type) {
		case 'select':
			d3.select(self._target + " #node_" + node_id)
				.classed("select", true);
			d3.select(self._target + " #node_" + node_id)
				.attr("style", "fill: rgb(50, 128, 50);");
			data = "select";
			break;
		case 'over':
			d3.select(self._target + " #node_" + node_id)
				.classed("over", true);
			if (d3.select(self._target + " #node_" + node_id)
				.classed("select")) {
				
				d3.select(self._target + " #node_" + node_id)
					.attr("style", "fill: rgb(128, 128, 50);");
				
				data = "select over";
			}
			else {
				d3.select(self._target + " #node_" + node_id)
					.attr("style", "fill: rgb(128, 50, 50);");
				
				data = "over";
			}
			break;
		case 'off':
			d3.select(self._target + " #node_" + node_id)
				.classed("select", false);
			
			d3.select(self._target + " #node_" + node_id)
				.attr("style", "fill: rgb(50, 50, 128);");
			break;
	}
	
	d3.select(self._target + " #node_" + node_id)
		.attr("data-select", data);
}

/**
* Function tooltip_map_create
* Return void
* This function manages nodes tooltips
*/
MapController.prototype.tooltip_map_create = function(self, target) {
	var nodeTarget = $(target);
	var spinner = $('#spinner_tooltip').html();
	var nodeSize = get_size_element("#" + $(nodeTarget).attr("id"));
	nodeSize[0] = nodeSize[0] * self._zoomManager.scale(); // Apply zoom
	var node_id = nodeTarget.attr("id");
	
	var type = parseInt(nodeTarget.data("type"));
	var data_id = parseInt(nodeTarget.data("id"));
	var data_graph_id = parseInt(nodeTarget.data("graph_id"));
	
	nodeTarget.tooltipster({
		arrow: true,
		trigger: 'click',
		contentAsHTML: true,
		autoClose: false,
		offsetX: nodeSize[0] / 2,
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
* Function editMap
* Return void
* This function prints the map edition table
*/
MapController.prototype.editMap = function(self, target) {
	var id_map = self._id;
	
	var params = {};
	params["printEditMapTable"] = 1;
	params["id_map"] = id_map;
	params["page"] = "include/ajax/map.ajax";
	
	jQuery.ajax ({
		data: params,
		dataType: "html",
		type: "POST",
		url: "ajax.php",
		success: function (data) {
			$(target).append("<div id='edit_map_dialog' style='display: none;'></div>");
			$("#edit_map_dialog").append(data);
			$("#edit_map_dialog").dialog({ autoOpen: false });
			$("#edit_map_dialog").dialog("open");
		}
	});
}

/**
* Function nodeGetDetails
* Return link
* This function returns a link with node details
*/
MapController.prototype.nodeGetDetails = function(self, target) {
	var nodeTarget = $(target);
	var node_id = nodeTarget.attr("id");
	var data_id = parseInt(nodeTarget.data("id"));
	var data_graph_id = parseInt(nodeTarget.data("graph_id"));

	var params = {};
	params["getNodeDetails"] = 1;
	params["id_node_data"] = data_id;
	params["data_graph_id"] = data_graph_id;
	params["node_id"] = node_id;
	params["page"] = "include/ajax/map.ajax";
	
	jQuery.ajax ({
		data: params,
		dataType: "JSON",
		type: "POST",
		url: "ajax.php",
		success: function (data) {
			var window_popup = window.open("", "window_" + data_graph_id,
				'title=DETAILS, width=300, height=300, toolbar=no, location=no, directories=no, status=no, menubar=no');
			
			$(window_popup.document.body).html(data);
		}
	});
}

/**
* Function editNode
* Return void
* This function prints the node edition table
*/
MapController.prototype.editNode = function(self, target) {
	var nodeTarget = $(target);
	
	var id_map = self._id;
	var type = parseInt(nodeTarget.data("type"));
	var data_id = parseInt(nodeTarget.data("id"));
	var data_graph_id = parseInt(nodeTarget.data("graph_id"));
	var node_id = nodeTarget.attr("id");
	
	var params = {};
	params["printEditNodeTable"] = 1;
	params["id_node_data"] = data_id;
	params["type"] = type;
	params["id_map"] = id_map;
	params["data_graph_id"] = data_graph_id;
	params["node_id"] = node_id;
	params["page"] = "include/ajax/map.ajax";
	
	jQuery.ajax ({
		data: params,
		dataType: "html",
		type: "POST",
		url: "ajax.php",
		success: function (data) {
			$(target).append("<div id='edit_node_dialog' style='display: none;'></div>");
			$("#edit_node_dialog").append(data);
			$("#edit_node_dialog").dialog({ autoOpen: false });
			$("#edit_node_dialog").dialog("open");
		}
	});
}

/**
* Function deleteNode
* Return void
* This function delete a node and the arrows that use it
*/
MapController.prototype.deleteNode = function(self, target) {
	var self = this;
	
	$.each(nodes, function(i, node) {
		if (node.type != ITEM_TYPE_AGENT_NETWORKMAP)
			return 1; // Continue
		
		var status_selection =
			self.get_status_selection_node(node.graph_id);
		
		if (status_selection.indexOf("select") == -1) {
			return 1; // Continue
		}
		
		var id_node = "node_" + node.graph_id;
		var arrowsToDelete = self.getArrows(self, id_node);
		
		// Delete edges and nodes in "nodes" and "edges" arrays
		self.deleteEdgesAndNode(arrowsToDelete, id_node);
		
		// Delete visual edges and nodes
		arrowsToDelete.forEach(function(arrow) {
			d3.select("#arrow_" + arrow).remove();
		});
		d3.select(self._target + " #" + id_node).remove();
	});
}

/**
* Function deleteEdges
* Return void
* This function delete the edges of a node in the edges array
*/
MapController.prototype.deleteEdgesAndNode = function(arrowsToDelete, id_node) {
	var newEdges = [];

	arrowsToDelete.forEach(function(arrow) {
		edges.forEach(function(edge) {
			if (edge["graph_id"] != arrow) {
				newEdges.push(edge);
			}
		});
		edges = newEdges;
		newEdges = [];
	});
	
	arrowsToDelete.forEach(function(arrow) {
		nodes.forEach(function(nodeOrEdge) {
			var nodeToDel = "node_" + nodeOrEdge["graph_id"];
			if ((nodeOrEdge["graph_id"] != arrow) && (nodeToDel != id_node)) {
				newEdges.push(nodeOrEdge);
			}
		});
		nodes = newEdges;
		newEdges = [];
	});
}

/**
* Function getArrows
* Return array[id_arrow]
* This function returns the arrows of a node
*/
MapController.prototype.getArrows = function(self, id_node) {
	var edgesToDel = [];
	var j = 0;

	edges.forEach(function(edge, index) {
		var nodeTo = "node_" + edge["to"];
		var nodeFrom = "node_" + edge["from"];
		if (nodeTo == id_node || nodeFrom == id_node) {
			edgesToDel[index] = edge["graph_id"];
		}
	});

	return edgesToDel;
}

/**
* Function close_all_tooltips
* Return void
* This function hide nodes tooltips
*/
MapController.prototype.close_all_tooltips = function() {
	$("svg .tooltipstered").tooltipster("destroy");
}

/**
* Function nodeData
* Return array(data)
* This function returns the data of the node
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


/**
* Function arrow_by_pieces
* Return void
* This function print the arrow by pieces (3 steps)
*/
//~ MapController.prototype.arrow_by_pieces = function(target, id_arrow, id_node_to, id_node_from, wait) {
MapController.prototype.arrow_by_pieces = function(target, arrow_data, wait) {
	var self = this;
	
	if (typeof(wait) === "undefined")
		wait = 1;
	
	var count_files = 2;
	function wait_load(callback) {
		count_files--;
		
		if (count_files == 0) {
			callback();
		}
	}
	
	var arrow_layout = d3
		.select(target +" #arrow_" + arrow_data['graph_id']);
	
	switch (wait) {
		/*---------------------------------------------*/
		/*-------- Preload head and body arrow --------*/
		/*---------------------------------------------*/
		case 1:
			arrow_layout = arrow_layout.append("g")
				.attr("class", "arrow_position_rotation")
				.append("g")
					.attr("class", "arrow_translation")
					.append("g")
						.attr("class", "arrow_container");
			
			if (is_buggy_firefox) {
				arrow_layout.append("g")
					.attr("class", "body")
					.append("use")
						.attr("xlink:href", "#body_arrow");
				
				arrow_layout.append("g")
					.attr("class", "head")
					.append("use")
						.attr("xlink:href", "#head_arrow");
				
				self.arrow_by_pieces(target, arrow_data, 0);
			}
			else {
				arrow_layout.append("g")
					.attr("class", "body")
					.append("use")
						.attr("xlink:href", "images/maps/body_arrow.svg#body_arrow")
						.on("load", function() {
							wait_load(function() {
								self.arrow_by_pieces(target, arrow_data, 0);
							});
						});
				
				arrow_layout.append("g")
					.attr("class", "head")
					.append("use")
						.attr("xlink:href", "images/maps/head_arrow.svg#head_arrow")
						.on("load", function() {
							wait_load(function() {
								self.arrow_by_pieces(target, arrow_data, 0);
							});
						});
			}
			break;
		/*---------------------------------------------*/
		/*---- Print head and body arrow by steps -----*/
		/*---------------------------------------------*/
		case 0:
			if (arrow_data['temp']) {
				switch (arrow_data['type']) {
					case 'parent':
						var id_node_to = null;
						var id_node_from = "node_" + arrow_data['from']['graph_id'];
						
						var c_elem2 = arrow_data['mouse'];
						var c_elem1 = get_center_element(self._target +" #" + id_node_from);
						
						var radius_to = 5;
						var radius_from = parseFloat(get_radius_element("#" + id_node_from));
						break;
					case 'children':
						var id_node_to = "node_" + arrow_data['to']['graph_id'];
						var id_node_from = null;
						
						var c_elem2 = get_center_element(self._target +" #" + id_node_to);
						var c_elem1 = arrow_data['mouse'];
						
						var radius_to = parseFloat(get_radius_element("#" + id_node_to));
						var radius_from = 5;
						break;
				}
				
			}
			else {
				var id_node_to = "node_" + arrow_data['to']['graph_id'];
				var id_node_from = "node_" + arrow_data['from']['graph_id'];
				
				var c_elem2 = get_center_element(self._target +" #" + id_node_to);
				var c_elem1 = get_center_element(self._target +" #" + id_node_from);
				
				var radius_to = parseFloat(get_radius_element("#" + id_node_to));
				var radius_from = parseFloat(get_radius_element("#" + id_node_from));
			}
			
			var distance = get_distance_between_point(c_elem1, c_elem2);
			
			var transform = d3.transform();
			
			/*---------------------------------------------*/
			/*--- Position of layer arrow (body + head) ---*/
			/*---------------------------------------------*/
			var arrow_body = arrow_layout.select(".body");
			var arrow_body_b = arrow_body.node().getBBox();
			var arrow_body_height = (arrow_body_b['height'] + arrow_body_b['y']);
			var arrow_body_width = (arrow_body_b['width'] + arrow_body_b['x']);
			
			transform.translate[0] = c_elem1[0];
			transform.translate[1] = c_elem1[1];
			transform.rotate = get_angle_of_line(c_elem1, c_elem2);
			
			arrow_layout.select(".arrow_position_rotation").attr("transform", transform.toString());
			transform = d3.transform();
			transform.translate[0] = radius_from;
			transform.translate[1] = - (arrow_body_height / 2);
			arrow_layout.select(".arrow_translation").attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*-------- Resize the body arrow width --------*/
			/*---------------------------------------------*/
			var arrow_head = arrow_layout.select(".head");
			var arrow_head_b = arrow_head.node().getBBox();
			var arrow_head_height = (arrow_head_b['height'] + arrow_head_b['y']);
			var arrow_head_width = (arrow_head_b['width'] + arrow_head_b['x']);
			
			var body_width = distance - arrow_head_width - radius_to - radius_from;
			
			transform = d3.transform();
			transform.scale[0] = body_width / arrow_body_width;
			
			arrow_body.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*---------- Position of head arrow -----------*/
			/*---------------------------------------------*/
			transform = d3.transform();
			
			var arrow_body_t = d3.transform(arrow_body.attr("transform"));
			
			var scale = arrow_body_t.scale[0];
			var x = 0 + arrow_body_width * scale;
			var y = 0 + (arrow_body_height / 2  - arrow_head_height / 2);
			
			transform.translate[0] = x;
			transform.translate[1] = y;
			
			arrow_head.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*------- Show the result in one time ---------*/
			/*---------------------------------------------*/
			arrow_layout.attr("style", "opacity: 1");
			break;
	}
}

/*-----------------------------------------------*/
/*-------------------Functions-------------------*/
/*-----------------------------------------------*/
/**
* Function open_in_another_window
* Return void
* This function open the node in extra window
*/
function open_in_another_window(link) {
	window.open(link);
}

/**
* Function close_button_tooltip
* Return void
* This function manages "close" button from tooltips
*/
function close_button_tooltip(data_graph_id) {
	$("#node_" + data_graph_id).tooltipster("destroy");
}

/**
* Function tooltip_to_new_window
* Return void
* This function manages "open in new wondow" button from tooltips
*/
function tooltip_to_new_window(data_graph_id) {
	var content = $("#tooltip_" + data_graph_id + " .body").html();
	
	$("#node_" + data_graph_id).tooltipster("destroy");
	
	var window_popup = window.open("", "window_" + data_graph_id,
		'title=NEW_WINDOW, width=300, height=300, toolbar=no, location=no, directories=no, status=no, menubar=no');
	
	$(window_popup.document.body).html(content);
}

/**
* Function get_distance_between_point
* Return float
* This function returns the distance between two nodes
*/
function get_distance_between_point(point1, point2) {
	var delta_x = Math.abs(point1[0] - point2[0]);
	var delta_y = Math.abs(point1[1] - point2[1]);
	
	return Math.sqrt(
		Math.pow(delta_x, 2) + Math.pow(delta_y, 2));
}

/**
* Function get_center_element
* Return array[x, y]
* This function returns the center of the node
*/
function get_center_element(element) {
	var element_t = d3.transform(d3.select(element).attr("transform"));
	var element_t_scale = parseFloat(element_t['scale']);
	var element_b = d3.select(element).node().getBBox();
	
	var box_x = parseFloat(element_t.translate[0]) +
		parseFloat(element_b['x']) * element_t_scale;
	var box_y = parseFloat(element_t.translate[1]) +
		parseFloat(element_b['y']) * element_t_scale;
	
	var width = (element_t_scale * element_b['width']);
	var height = (element_t_scale * element_b['height']);
	
	var c_x = box_x + (width / 2);
	var c_y = box_y + (height / 2);
	
	return [c_x, c_y];
}

/**
* Function get_angle_of_line
* Return float
* This function returns the angle between two lines (center node to another node)
*/
function get_angle_of_line(point1, point2) {
	return Math.atan2(point2[1] - point1[1], point2[0] - point1[0]) * 180 / Math.PI;
}

/**
* Function getBBox_Symbol
* Return BBox
* This function returns bbox of the symbol
*/
function getBBox_Symbol(target, symbol) {
	var base64symbol = btoa(symbol).replace(/=/g, "");
	
	return d3.select(target + " #" + base64symbol).node().getBBox();
}

/**
* Function get_size_element
* Return array[width, height]
* This function returns the size of the element
*/
function get_size_element(element) {
	var transform = d3.transform(d3.select(element).attr("transform"));
	var element_b = d3.select(element).node().getBBox();
	
	
	return [
		element_b['width'] * transform.scale[0],
		element_b['height'] * transform.scale[1]];
}

/**
* Function get_radius_element
* Return void
* This function returns the element radius
*/
function get_radius_element(element) {
	var size = get_size_element(element);
	
	return Math.sqrt(
		Math.pow(size[0] / 2, 2) + Math.pow(size[1] / 2, 2));
}
