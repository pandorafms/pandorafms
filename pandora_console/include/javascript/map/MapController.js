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
* Function init_map
* Return void
* This function init the map
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
	* Function zoom
	* Return void
	* This function manages the zoom
	*/
	function zoom() {
		self.close_all_tooltips();
		self.remove_resize_square();
		
		var zoom_level = d3.event.scale;
		
		self._slider.property("value", Math.log(zoom_level));
		
		self._viewport
			.attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
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
* Function paint_nodes
* Return void
* This function paint the nodes
*/
MapController.prototype.paint_nodes = function() {
	self = this;
	
	self._viewport.selectAll(".node")
		.data(
			nodes
				.filter(function(d, i) {
						if (d.type != ITEM_TYPE_EDGE_NETWORKMAP) {
							return true;
						}
						else {
							return false;
						}
				}))
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
						.attr("r", "15");
	
	
	
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
			
			
			arrow_by_pieces(self._target + " svg", id_arrow, id_node_to, id_node_from);
		});
	}
	
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
		var id_arrow = d3.select(this).attr("id");
		var id_node_to = "node_" + d3.select(this).attr("data-to");
		var id_node_from = "node_" + d3.select(this).attr("data-from");
		
		arrow_by_pieces(self._target + " svg", id_arrow, id_node_to, id_node_from, 0);
	});
}

MapController.prototype.remove_resize_square = function(item, wait) {
	d3.select(self._target + " svg #resize_square").remove();
}

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
		case 0:
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
			
			d3.select("#resize_square .square rect")
				.attr("width", (bbox_item.width * transform_viewport.scale[0]));
			d3.select("#resize_square .square rect")
				.attr("height", (bbox_item.height * transform_viewport.scale[1]));
			
			// Set the handlers
			
			var bbox_handler = d3
				.select(self._target + " .handler").node().getBBox();
			
			var handler_positions = {};
			handler_positions['N'] = [];
			handler_positions['N'][0] = (bbox_item.width  * transform_viewport.scale[0] / 2)
				- (bbox_handler.width / 2);
			handler_positions['N'][1] = 0 - (bbox_handler.height / 2);
			
			handler_positions['NE'] = [];
			handler_positions['NE'][0] = (bbox_item.width  * transform_viewport.scale[0])
				- (bbox_handler.width / 2);
			handler_positions['NE'][1] = handler_positions['N'][1];
			
			handler_positions['E'] = [];
			handler_positions['E'][0] = handler_positions['NE'][0];
			handler_positions['E'][1] = (bbox_item.height  * transform_viewport.scale[1] / 2)
				- (bbox_handler.height / 2);
			
			handler_positions['SE'] = [];
			handler_positions['SE'][0] = handler_positions['NE'][0];
			handler_positions['SE'][1] = (bbox_item.height  * transform_viewport.scale[1])
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
				
				d3.select(this)
					.on("mouseover", function(d) {
						self.change_handler_image("mouseover", d);
						//~ console.log("mouseover");
					})
					.on("mouseout", function(d) {
						self.change_handler_image("mouseout", d);
						//~ console.log("mouseout");
					})
					.on("click", function(d) {
						console.log("click");
					})
					//~ .on("dragstart", function(d) {
						//~ self.event_resize("dragstart", item, d);
					//~ })
					//~ .on("drag", function(d) {
						//~ self.event_resize("drag", item, d);
					//~ })
					//~ .on("dragend", function(d) {
						//~ self.event_resize("dragend", item, d);
					//~ });
			});
			
			
			resize_square.style("opacity", 1);
			break;
	}
}

MapController.prototype.change_handler_image = function(action, handler, wait) {
	
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

MapController.prototype.event_resize = function(action, item, handler) {
	var self = this;
}

/**
* Function init_events
* Return boolean
* This function init click events in the map
*/
MapController.prototype.init_events = function(principalObject) {
	self = this;
	
	var node_menu = [
		{
			title: 'Show details',
			action: function(elm, d, i) {
				self.openNodeDetais(self, elm);
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
			if (d3.event.button != 0) {
				d3.event.stopPropagation();
				d3.event.preventDefault();
			}
			
			if (d3.event.defaultPrevented) return;
			
			self.tooltip_map_create(self, this);
		})
		.on("contextmenu", d3.contextMenu(node_menu));
	
	var drag = d3.behavior.drag()
		.origin(function(d) { return d; })
		.on("dragstart", dragstarted)
		.on("drag", dragged)
		.on("dragend", dragended);
	
	d3.selectAll(".draggable").call(drag);
	
	/**
	* Function dragstarted
	* Return void
	*/
	function dragstarted(d) {
		if (d3.event.sourceEvent.button == 0) {
			d3.event.sourceEvent.stopPropagation();
			d3.event.sourceEvent.preventDefault();
		}
		
		if ($("#node_" + d['graph_id']).hasClass("tooltipstered")) {
			$("#node_" + d['graph_id']).tooltipster('destroy');
		}
		
		self.remove_resize_square();
		
		d3.select(this).classed("dragging", true);
	}
	
	/**
	* Function dragged
	* Return void
	*/
	function dragged(d) {
		var delta_x = d3.event.dx;
		var delta_y = d3.event.dy;
		
		var translation = d3.transform(d3.select(this).attr("transform"));
		scale = 1;
		var x = translation.translate[0] + delta_x;
		var y = translation.translate[1] + delta_y;
		
		d3.select(this).attr("transform",
			"translate(" + x + " " + y + ")");
		
		self.move_arrow(d3.select(this).attr("data-graph_id"));
	}
	
	/**
	* Function dragended
	* Return void
	*/
	function dragended(d) {
		d3.select(this).classed("dragging", false);
		
		if ($("#node_" + d['graph_id']).hasClass("tooltipstered")) {
			$("#node_" + d['graph_id']).tooltipster('destroy');
		}
		
		self.remove_resize_square();
	}
}

/**
* Function tooltip_map_create
* Return void
* This function manages nodes tooltips
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
* Function openNodeDetais
* Return void
* This function shows node details in extra window
*/
MapController.prototype.openNodeDetais = function(self, target) {
	var nodeTarget = $(target);
	
	var type = parseInt(nodeTarget.data("type"));
	var data_id = parseInt(nodeTarget.data("id"));
	var data_graph_id = parseInt(nodeTarget.data("graph_id"));
	var node_id = nodeTarget.attr("id");

	var link = self.nodeGetDetails(data_id, type, self._id, data_graph_id, node_id);

	//window.open(link);
}

/**
* Function nodeGetDetails
* Return link
* This function returns a link with node details
*/
MapController.prototype.nodeGetDetails = function(data_id, type, id_map, data_graph_id, node_id) {
	var params = {};
	params["getNodeDetails"] = 1;
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
			console.log(data);
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
	var id_node = d3.select(target).attr("id");
	var arrowsToDelete = self.getArrows(self, id_node);

	// Delete edges and nodes in "nodes" and "edges" arrays
	self.deleteEdgesAndNode(arrowsToDelete, id_node);

	// Delete visual edges and nodes
	arrowsToDelete.forEach(function(arrow) {
		d3.select("#arrow_" + arrow).remove();
	});
	d3.select(target).remove();
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
		'title=MIERDACA, width=300, height=300, toolbar=no, location=no, directories=no, status=no, menubar=no');
	
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
	var element_b = d3.select(element).node().getBBox();
	
	
	return [element_b['width'], element_b['height']];
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

/**
* Function arrow_by_pieces
* Return void
* This function print the arrow by pieces (3 steps)
*/
function arrow_by_pieces(target, id_arrow, id_node_to, id_node_from, wait) {
	
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
		.select(target +" #" + id_arrow);
	
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
				
				arrow_by_pieces(target, id_arrow, id_node_to, id_node_from, 0);
			}
			else {
				arrow_layout.append("g")
					.attr("class", "body")
					.append("use")
						.attr("xlink:href", "images/maps/body_arrow.svg#body_arrow")
						.on("load", function() {
							wait_load(function() {
								arrow_by_pieces(
									target, id_arrow, id_node_to, id_node_from, 0);
							});
						});
				
				arrow_layout.append("g")
					.attr("class", "head")
					.append("use")
						.attr("xlink:href", "images/maps/head_arrow.svg#head_arrow")
						.on("load", function() {
							wait_load(function() {
								arrow_by_pieces(
									target, id_arrow, id_node_to, id_node_from, 0);
							});
						});
			}
			break;
		/*---------------------------------------------*/
		/*---- Print head and body arrow by steps -----*/
		/*---------------------------------------------*/
		case 0:
			var c_elem2 = get_center_element(target +" #" + id_node_to);
			var c_elem1 = get_center_element(target +" #" + id_node_from);
			
			var distance = get_distance_between_point(c_elem1, c_elem2);
			
			var radius_to = parseFloat(get_radius_element("#" + id_node_to));
			var radius_from = parseFloat(get_radius_element("#" + id_node_from));
			
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
