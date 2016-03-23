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
/*------------------Constructor------------------*/
/*-----------------------------------------------*/
var NetworkmapController = function(target) {
	MapController.call(this, target);
}

/*-----------------------------------------------*/
/*------------------Atributes--------------------*/
/*-----------------------------------------------*/
NetworkmapController.prototype = Object.create(MapController.prototype);
NetworkmapController.prototype.constructor = NetworkmapController;

/*-----------------------------------------------*/
/*--------------------Methods--------------------*/
/*-----------------------------------------------*/
/**
* Function init_map
* Return  void
* This function init the map
*/
NetworkmapController.prototype.init_map = function() {
	MapController.prototype.init_map.call(this);
};

/**
* Function filter_only_agents
* Return  void
* This function return if a node is an agent
*/
NetworkmapController.prototype.filter_only_agents = function(node) {
	switch (node.type) {
		case ITEM_TYPE_EDGE_NETWORKMAP:
		case ITEM_TYPE_MODULE_NETWORKMAP:
			return false;
			break;
		default:
			return true;
			break;
	}
}


/**
* Function get_arrow_AMMA
* Return  array (arrow)
* This function returns an AMMA arrow
*/
NetworkmapController.prototype.get_arrow_AMMA = function(id_to, id_from) {
	var self = this;
	
	var return_var = null;
	
	var arrow = {};
	
	arrow['type'] = 'AMMA';
	
	if ((self.get_node_type(id_to) == ITEM_TYPE_MODULE_NETWORKMAP)
		&& (self.get_node_type(id_from) == ITEM_TYPE_MODULE_NETWORKMAP)) {
			
		var arrow_MM = self.get_arrow(id_to, id_from);
		
		
		var arrows_to = self
			.get_edges_from_node(arrow_MM['nodes']['to']['graph_id']);
		var arrows_from = self
			.get_edges_from_node(arrow_MM['nodes']['from']['graph_id']);
		
		var temp = null;
		$.each(arrows_to, function(i, arrow_to) {
			if (arrow_to['graph_id'] != arrow_MM['arrow']['graph_id']) {
				temp = arrow_to;
				return false;
			}
		});
		var arrow_to = temp;
		
		temp = null;
		$.each(arrows_from, function(i, arrow_from) {
			if (arrow_from['graph_id'] != arrow_MM['arrow']['graph_id']) {
				temp = arrow_from;
				return false;
			}
		});
		var arrow_from = temp;
		
		if (arrow_to !== null && arrow_from !== null) {
			// There is one arrow for A-M-M-A
			arrow_to = self.get_arrow_from_id(arrow_to['graph_id']);
			arrow_from = self.get_arrow_from_id(arrow_from['graph_id']);
			
			arrow['graph_id'] = arrow_to['arrow']['graph_id'] + "" +
				arrow_MM['arrow']['graph_id'] + "" +
				arrow_from['arrow']['graph_id'];
			
			
			if (arrow_to['nodes']['to'] == arrow_MM['arrow']['to']) {
				arrow['to'] = arrow_to['nodes']['from'];
			}
			else {
				arrow['to'] = arrow_to['nodes']['to'];
			}
			arrow['to_module'] = arrow_MM['nodes']['to']['id'];
			arrow['to_status'] = arrow_MM['nodes']['to']['status'];
			arrow['to_title'] = arrow_MM['nodes']['to']['title'];
			
			
			if (arrow_from['nodes']['to'] == arrow_MM['arrow']['from']) {
				arrow['from'] = arrow_from['nodes']['from'];
			}
			else {
				arrow['from'] = arrow_from['nodes']['to'];
			}
			arrow['from_module'] = arrow_MM['nodes']['from']['id'];
			arrow['from_status'] = arrow_MM['nodes']['from']['status'];
			arrow['from_title'] = arrow_MM['nodes']['from']['title'];
			
			return_var = arrow;
		}
	}
	
	return return_var;
}

/**
* Function get_arrow_AMA
* Return  array (arrow)
* This function returns an AMA arrow
*/
NetworkmapController.prototype.get_arrows_AMA = function(id_to, id_from) {
	var self = this;
	
	var arrows = [];
	
	if (self.get_node_type(id_to) != self.get_node_type(id_from)) {
		var arrow_AM = self.get_arrow(id_to, id_from);
		
		
		var edges_temp = null;
		var is_agent = null
		
		if (self.get_node_type(id_to) == ITEM_TYPE_AGENT_NETWORKMAP) {
			if (arrow_AM['nodes']['from']['id_agent'] == arrow_AM['nodes']['to']['id']) {
				edges_temp = self.get_edges_from_node(id_from);
				is_agent = 'to';
			}
		}
		else if (self.get_node_type(id_from) == ITEM_TYPE_AGENT_NETWORKMAP) {
			if (arrow_AM['nodes']['to']['id_agent'] == arrow_AM['nodes']['from']['id']) {
				edges_temp = self.get_edges_from_node(id_to);
				is_agent = 'from';
			}
		}
		
		if (edges_temp != null) {
			$.each(edges_temp, function(i, edge) {
				// Filter same edge
				if ((edge['to'] != id_to) || (edge['from'] != id_from)) {
					
					var type_to = self.get_node_type(edge['to']);
					var type_from = self.get_node_type(edge['from']);
					
					// Filter M-M edge
					if ((type_to != ITEM_TYPE_MODULE_NETWORKMAP)
						|| (type_from != ITEM_TYPE_MODULE_NETWORKMAP)) {
						
						var arrow = self.get_arrow_from_id(edge['graph_id']);
						
						var temp = {};
						
						temp['graph_id'] =
							arrow['arrow']['graph_id'] + "" +
							arrow_AM['arrow']['graph_id'] + "";
						
						temp['type'] = 'AMA';
						
						switch (is_agent) {
							case 'to':
								temp['to'] = arrow_AM['nodes']['to'];
								temp['to_module'] = arrow_AM['nodes']['from']['id'];
								temp['to_status'] = arrow_AM['nodes']['from']['status'];
								temp['to_title'] = arrow_AM['nodes']['from']['title'];
								
								if (type_to == ITEM_TYPE_AGENT_NETWORKMAP) {
									temp['from'] = arrow['nodes']['to'];
								}
								else {
									temp['from'] = arrow['nodes']['from'];
								}
								temp['from_module'] = null;
								temp['from_status'] = null;
								temp['from_title'] = null;
								break;
							case 'from':
								temp['from'] = arrow_AM['nodes']['from'];
								temp['from_module'] = arrow_AM['nodes']['to']['id'];
								temp['from_status'] = arrow_AM['nodes']['to']['status'];
								temp['from_title'] = arrow_AM['nodes']['to']['title'];
								
								if (type_to == ITEM_TYPE_AGENT_NETWORKMAP) {
									temp['to'] = arrow['nodes']['to'];
								}
								else {
									temp['to'] = arrow['nodes']['from'];
								}
								temp['to_module'] = null;
								temp['to_status'] = null;
								temp['to_title'] = null;
								break;
						}
						
						arrows.push(temp);
					}
				}
			});
		}
	}
	
	if (arrows.length == 0) {
		return null;
	}
	else {
		return arrows;
	}
}

/**
* Function get_arrow_AA
* Return  array (arrow)
* This function returns an AA arrow
*/
NetworkmapController.prototype.get_arrow_AA = function(id_to, id_from) {
	var self = this;
	var arrow_AA;
	var found = false;
	
	$.each(edges, function(i, edge) {
		if (self.get_node_type(id_to) == self.get_node_type(id_from)) {
			if (self.get_node_type(id_to) == ITEM_TYPE_AGENT_NETWORKMAP) {
				var arrow = self.get_arrow(id_to, id_from);
				
				arrow_AA = {};
				arrow_AA['type'] = 'AA';
				arrow_AA['graph_id'] = arrow['arrow']['graph_id'];
				arrow_AA['to'] = arrow['nodes']['to'];
				arrow_AA['to_module'] = null;
				arrow_AA['to_status'] = null;
				arrow_AA['to_title'] = null;
				arrow_AA['from'] = arrow['nodes']['from'];
				arrow_AA['from_module'] = null;
				arrow_AA['from_status'] = null;
				arrow_AA['from_title'] = null;
				
				found = true;
			}
		}
	});
	
	if (found) {
		return arrow_AA;
	}
	else {
		return null;
	}
}

/**
* Function exists_arrow
* Return  bool
* This function returns if the arrow exists
*/
NetworkmapController.prototype.exists_arrow = function(arrows, arrow) {
	var var_return = false;
	
	if (arrow === null) {
		return false;
	}
	
	if (arrows.length == 0) {
		return false;
	}
	
	$.each(arrows, function(i, a) {
		if (a === null) {
			return true; // Continue
		}
		
		if (a['type'] == arrow['type']) {
			
			var a_to = a['to']['graph_id'];
			var arrow_to = arrow['to']['graph_id'];
			var a_from = a['from']['graph_id'];
			var arrow_from = arrow['from']['graph_id'];
			
			switch (arrow['type']) {
				case 'AA':
				case 'AMMA':
					if (a_to == arrow_to) {
						if (a_from == arrow_from) {
							
							var_return = true;
							return false; // Break
						}
					}
					break;
				case 'AMA':
					if ((a_to == arrow_to) && (a_from == arrow_from)) {
						var_return = true;
						return false; //Break;
					}
					else if ((a_to == arrow_from) && (a_from == arrow_to)) {
						var_return = true;
						return false; //Break;
					}
					break;
			}
		}
	});
	
	return var_return;
}

/**
* Function paint_arrows
* Return  void
* This function paints the arrows
*/
NetworkmapController.prototype.paint_arrows = function() {
	var self = this;
	
	var clean_arrows = [];
	
	var arrows_AA;
	$.each(edges, function(i, edge) {
		
		var arrow_AA = self.get_arrow_AA(edge['to'], edge['from']);
		if (arrow_AA !== null) {
			if (!self.exists_arrow(clean_arrows, arrow_AA)) {
				clean_arrows.push(arrow_AA);
			}
		}
		
		var arrow_AMMA = self.get_arrow_AMMA(edge['to'], edge['from']);
		if (arrow_AMMA !== null) {
			if (!self.exists_arrow(clean_arrows, arrow_AMMA)) {
				clean_arrows.push(arrow_AMMA);
			}
		}
		
		var arrows_AMA = self.get_arrows_AMA(edge['to'], edge['from']);
		if (arrows_AMA !== null) {
			$.each(arrows_AMA, function(i, arrow_AMA) {
				if (!self.exists_arrow(clean_arrows, arrow_AMA)) {
					clean_arrows.push(arrow_AMA);
				}
			});
		}
	});
	
	console.log(clean_arrows);
	
	var arrow_layouts = self._viewport.selectAll(".arrow")
		.data(clean_arrows)
		.enter()
			.append("g")
				.attr("class", "arrow")
				.attr("id", function(d) { return "arrow_" + d['graph_id'];})
				.attr("data-to", function(d) {
					return d['to']['graph_id'];})
				.attr("data-from", function(d) {
					return d['from']['graph_id'];})
				.attr("data-type", function(d) {
					return d['type'];})
				.attr("data-to_module", function(d) {
					return d['to_module'];})
				.attr("data-from_module", function(d) {
					return d['from_module'];});
	
	create_arrow(arrow_layouts);
	
	/**
	* Function create_arrow
	* Return void
	* This function creates the arrow
	*/
	function create_arrow(arrow_layouts) {
		arrow_layouts.each(function(d) {
			self.arrow_by_pieces(self._target + " svg", d);
		});
	}
	
}

NetworkmapController.prototype.arrow_by_pieces = function (target, arrow_data, wait) {
	var self = this;
	
	if (typeof(wait) === "undefined") {
		wait = 1;
	}
	
	switch (arrow_data['type']) {
		case 'AA':
			MapController.prototype.arrow_by_pieces.call(
				this, self._target + " svg", arrow_data, wait);
			break;
		case 'AMMA':
			self.arrow_by_pieces_AMMA(self._target + " svg", arrow_data, wait);
			break;
		case 'AMA':
			self.arrow_by_pieces_AMA(self._target + " svg", arrow_data, wait);
			break;
	}
}

/**
* Function arrow_by_pieces_AMMA
* Return void
* This function print the arrow by pieces (3 steps)
*/
NetworkmapController.prototype.arrow_by_pieces_AMMA = function (target, arrow_data, wait) {
	var self = this;
	
	if (typeof(wait) === "undefined")
		wait = 1;
	
	var symbols = {};
	
	symbols['images/maps/body_arrow.svg'] = {};
	symbols['images/maps/body_arrow.svg']['target'] = "body";
	symbols['images/maps/body_arrow.svg']['id_symbol'] = "#body_arrow";
	
	symbols['images/maps/head_arrow_module_warning.svg'] = {};
	symbols['images/maps/head_arrow_module_warning.svg']
		['target'] = "head_tail";
	symbols['images/maps/head_arrow_module_warning.svg']
		['id_symbol'] = "#head_arrow_module_warning";
	symbols['images/maps/head_arrow_module_warning.svg']
		['status'] = "module_warning";
	
	symbols['images/maps/head_arrow_module_unknown.svg'] = {};
	symbols['images/maps/head_arrow_module_unknown.svg']
		['target'] = "head_tail";
	symbols['images/maps/head_arrow_module_unknown.svg']
		['id_symbol'] = "#head_arrow_module_unknown";
	symbols['images/maps/head_arrow_module_unknown.svg']
		['status'] = "module_unknown";
		
	symbols['images/maps/head_arrow_module_ok.svg'] = {};
	symbols['images/maps/head_arrow_module_ok.svg']
		['target'] = "head_tail";
	symbols['images/maps/head_arrow_module_ok.svg']
		['id_symbol'] = "#head_arrow_module_ok";
	symbols['images/maps/head_arrow_module_ok.svg']
		['status'] = "module_ok";
	
	symbols['images/maps/head_arrow_module_no_data.svg'] = {};
	symbols['images/maps/head_arrow_module_no_data.svg']
		['target'] = "head_tail";
	symbols['images/maps/head_arrow_module_no_data.svg']
		['id_symbol'] = "#head_arrow_module_no_data";
	symbols['images/maps/head_arrow_module_no_data.svg']
		['status'] = "no_data";
	
	symbols['images/maps/head_arrow_module_critical.svg'] = {};
	symbols['images/maps/head_arrow_module_critical.svg']
		['target'] = "head_tail";
	symbols['images/maps/head_arrow_module_critical.svg']
		['id_symbol'] = "#head_arrow_module_critical";
	symbols['images/maps/head_arrow_module_critical.svg']
		['status'] = "module_critical";
		
	symbols['images/maps/head_arrow_module_alertsfired.svg'] = {};
	symbols['images/maps/head_arrow_module_alertsfired.svg']
		['target'] = "head_tail";
	symbols['images/maps/head_arrow_module_alertsfired.svg']
		['id_symbol'] = "#head_arrow_module_alertsfired";
	symbols['images/maps/head_arrow_module_alertsfired.svg']
		['status'] = "module_alertsfired";
	
	var count_files = Object.keys(symbols).length;
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
			
			var arrow_body = arrow_layout.append("g")
				.attr("class", "body");
			var arrow_head = arrow_layout.append("g")
				.attr("class", "head");
			var arrow_head_title = arrow_layout.append("g")
				.attr("class", "head_title");
			arrow_head_title.append("text").text(arrow_data['to_title']);
			var arrow_tail = arrow_layout.append("g")
				.attr("class", "tail");
			var arrow_tail_title = arrow_layout.append("g")
				.attr("class", "tail_title");
			arrow_tail_title.append("text").text(arrow_data['from_title']);
			
			$.each(symbols, function (i, s) {
				if (is_buggy_firefox) {
					switch (s['target']) {
						case 'body':
							arrow_body.append("use")
								.attr("xlink:href", s['id_symbol']);
							break;
						case 'head_tail':
							arrow_head.append("use")
								.style("opacity", 0)
								.attr("data-status", s['status'])
								.attr("xlink:href", s['id_symbol']);
							
							arrow_tail.append("use")
								.style("opacity", 0)
								.attr("data-status", s['status'])
								.attr("xlink:href", s['id_symbol']);
							break;
					}
				}
				else {
					switch (s['target']) {
						case 'body':
							arrow_body.append("use")
								.attr("xlink:href", i + s['id_symbol'])
								.on("load", function() {
									wait_load(function() {
										self.arrow_by_pieces_AMMA(
											target, arrow_data, 0);
									});
								});
							break;
						case 'head_tail':
							arrow_head.append("use")
								.style("opacity", 0)
								.attr("data-status", s['status'])
								.attr("xlink:href", i + s['id_symbol'])
								.on("load", function() {
									wait_load(function() {
										self.arrow_by_pieces_AMMA(
											target, arrow_data, 0);
									});
								});
							
							arrow_tail.append("use")
								.style("opacity", 0)
								.attr("data-status", s['status'])
								.attr("xlink:href", i + s['id_symbol'])
								.on("load", function() {
									wait_load(function() {
										self.arrow_by_pieces_AMMA(
											target, arrow_data, 0);
									});
								});
							break;
					}
				}
			});
			
			if (is_buggy_firefox) {
				self.arrow_by_pieces_AMMA(target, arrow_data, 0);
			}
			break;
		/*---------------------------------------------*/
		/*---- Print head and body arrow by steps -----*/
		/*---------------------------------------------*/
		case 0:
			var id_node_to = "node_" + arrow_data['to']['graph_id'];
			var id_node_from = "node_" + arrow_data['from']['graph_id'];
			
			var c_elem2 = get_center_element(target +" #" + id_node_to);
			var c_elem1 = get_center_element(target +" #" + id_node_from);
			
			var distance = get_distance_between_point(c_elem1, c_elem2);
			
			var radius_to = parseFloat(get_radius_element("#" + id_node_to));
			var radius_from = parseFloat(get_radius_element("#" + id_node_from));
			
			var transform = d3.transform();
			
			var arrow_head = arrow_layout.select(".head");
			var arrow_head_b = arrow_head.node().getBBox();
			var arrow_head_height = (arrow_head_b['height'] + arrow_head_b['y']);
			var arrow_head_width = (arrow_head_b['width'] + arrow_head_b['x']);
			
			var arrow_tail = arrow_layout.select(".tail");
			var arrow_tail_b = arrow_tail.node().getBBox();
			var arrow_tail_height = (arrow_tail_b['height'] + arrow_tail_b['y']);
			var arrow_tail_width = (arrow_tail_b['width'] + arrow_tail_b['x']);
			
			var arrow_body = arrow_layout.select(".body");
			
			/*---------------------------------------------*/
			/*--- Position of layer arrow (body + head) ---*/
			/*---------------------------------------------*/
			var arrow_body_b = arrow_body.node().getBBox();
			var arrow_body_height = (arrow_body_b['height'] + arrow_body_b['y']);
			var arrow_body_width = (arrow_body_b['width'] + arrow_body_b['x']);
			
			transform.translate[0] = c_elem1[0];
			transform.translate[1] = c_elem1[1];
			transform.rotate = get_angle_of_line(c_elem1, c_elem2);
			
			arrow_layout.select(".arrow_position_rotation")
				.attr("transform", transform.toString());
			transform = d3.transform();
			transform.translate[0] = radius_from + arrow_tail_width;
			transform.translate[1] = - (arrow_body_height / 2);
			arrow_layout.select(".arrow_translation")
				.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*-------- Resize the body arrow width --------*/
			/*---------------------------------------------*/
			var body_width = distance - arrow_head_width - arrow_tail_width - radius_to - radius_from;
			
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
			
			
			transform = d3.transform();
			
			x = 0 - arrow_tail_width;
			y = 0 + (arrow_body_height / 2  - arrow_tail_height / 2);
			
			transform.translate[0] = x;
			transform.translate[1] = y;
			
			arrow_tail.attr("transform", transform.toString());
			
			self.update_interfaces_status(arrow_data);
			
			/*---------------------------------------------*/
			/*---------- Position the title of head -------*/
			/*---------------------------------------------*/
			var arrow_head_title = arrow_layout.select(".head_title");
			
			var arrow_head_title_b = arrow_head_title.node().getBBox();
			var arrow_head_title_height =
				(arrow_head_title_b['height'] + arrow_head_title_b['y']);
			
			transform = d3.transform();
			
			var x = 10;
			var y = 0 + (arrow_body_height / 2  - arrow_head_title_height / 2);
			
			transform.translate[0] = x;
			transform.translate[1] = y;
			
			arrow_head_title.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*---------- Position the title of tail -------*/
			/*---------------------------------------------*/
			var arrow_tail_title = arrow_layout.select(".tail_title");
			
			var arrow_tail_title_b = arrow_tail_title.node().getBBox();
			var arrow_tail_title_height =
				(arrow_tail_title_b['height'] + arrow_tail_title_b['y']);
			var arrow_tail_title_width =
				(arrow_tail_title_b['width'] + arrow_tail_title_b['x']);
			
			transform = d3.transform();
			
			var x = -10 + (arrow_body_width * scale) - arrow_tail_width - radius_from;
			var y = 0 + (arrow_body_height / 2  - arrow_head_title_height / 2);
			
			
			
			transform.translate[0] = x;
			transform.translate[1] = y;
			arrow_tail_title.attr("transform", transform.toString());
			
			self.re_rotate_interface_title(arrow_data);
			
			/*---------------------------------------------*/
			/*------- Show the result in one time ---------*/
			/*---------------------------------------------*/
			arrow_layout.attr("style", "opacity: 1");
			break;
	}
}

NetworkmapController.prototype.re_rotate_interface_title = function(arrow_data) {
	var id_node_to = "node_" + arrow_data['to']['graph_id'];
	var id_node_from = "node_" + arrow_data['from']['graph_id'];
	
	var c_elem2 = get_center_element(self._target +" #" + id_node_to);
	var c_elem1 = get_center_element(self._target +" #" + id_node_from);
	var angle = get_angle_of_line(c_elem1, c_elem2);
	if (angle < 0) angle = 360 + angle;
	
	// The angles in our SVG
	/*
	 *            \   270º    /            
	 *             \    |    /             
	 *              \   |   /              
	 *               \  |  /               
	 *                \ | /                
	 *          225º ( \|/ ) 315º          
	 * 180º ------------+--------------- 0º
	 *          135º ( /|\ ) 45º           
	 *                / | \                
	 *               /  |  \               
	 *              /   |   \              
	 *             /    |    \             
	 *            /     |     \            
	 *           /      |      \           
	 *                 90º                 
	 */
	
	var rotate;
	if (angle >= 0 && angle <= 90)
		rotate = false;
	if (angle > 90 && angle <= 180)
		rotate = true;
	if (angle > 180 && angle <= 270)
		rotate = true;
	if (angle > 270 && angle <= 360)
		rotate = false;
	
	console.log(arrow_data['graph_id'], angle, rotate);
	
	if (rotate) {
		var arrow_layout = d3.select(self._target +" #arrow_" + arrow_data['graph_id']);
		var arrow_tail_title = arrow_layout.select(".tail_title");
		var arrow_tail_title_text = arrow_tail_title.select("text");
		var arrow_tail_title_b = arrow_tail_title.node().getBBox();
		
		var transform = d3.transform();
		
		var center_tail_x = arrow_tail_title_b['width'] / 2 + arrow_tail_title_b['x'];
		var center_tail_y = arrow_tail_title_b['height'] / 2 + arrow_tail_title_b['y'];
		
		transform.rotate = "180 " + center_tail_x + " " + center_tail_y;
		arrow_tail_title_text.attr("transform", transform.toString());
		
		
		
		var arrow_head_title = arrow_layout.select(".head_title");
		var arrow_head_title_text = arrow_head_title.select("text");
		var arrow_head_title_b = arrow_head_title.node().getBBox();
		
		transform = d3.transform();
		
		var center_head_x = arrow_head_title_b['width'] / 2 + arrow_head_title_b['x'];
		var center_head_y = arrow_head_title_b['height'] / 2 + arrow_head_title_b['y'];
		
		transform.rotate = "180 " + center_head_x + " " + center_head_y;
		arrow_head_title_text.attr("transform", transform.toString());
	}
}

NetworkmapController.prototype.update_interfaces_status = function (arrow_data) {
	var self = this;
	
	var arrow_layout = d3
		.select(self._target +" #arrow_" + arrow_data['graph_id']);
	var arrow_head = arrow_layout.select(".head");
	var arrow_tail = arrow_layout.select(".tail");
	
	
	if (arrow_data.from_status !== null) {
		switch (parseInt(arrow_data.from_status)) {
			case AGENT_MODULE_STATUS_ALL:
			case AGENT_MODULE_STATUS_UNKNOWN:
				arrow_tail.selectAll("use")
					.style("opacity", 0);
				arrow_tail.select("use[data-status='module_unknown']")
					.style("opacity", 1);
				break;
			case AGENT_MODULE_STATUS_CRITICAL_BAD:
			case AGENT_MODULE_STATUS_CRITICAL_ALERT:
			case AGENT_MODULE_STATUS_NOT_NORMAL:
				arrow_tail.selectAll("use")
					.style("opacity", 0);
				arrow_tail.select("use[data-status='module_critical']")
					.style("opacity", 1);
				break;
			case AGENT_MODULE_STATUS_NO_DATA:
			case AGENT_MODULE_STATUS_NOT_INIT:
			default:
				arrow_tail.selectAll("use")
					.style("opacity", 0);
				arrow_tail.select("use[data-status='no_data']")
					.style("opacity", 1);
				break;
			case AGENT_MODULE_STATUS_NORMAL:
			case AGENT_MODULE_STATUS_NORMAL_ALERT:
				arrow_tail.selectAll("use")
					.style("opacity", 0);
				arrow_tail.select("use[data-status='module_ok']")
					.style("opacity", 1);
				break;
			case AGENT_MODULE_STATUS_WARNING:
				arrow_tail.selectAll("use")
					.style("opacity", 0);
				arrow_tail.select("use[data-status='module_warning']")
					.style("opacity", 1);
				break;
			case AGENT_MODULE_STATUS_WARNING_ALERT:
				arrow_tail.selectAll("use")
					.style("opacity", 0);
				arrow_tail.select("use[data-status='module_alertsfired']")
					.style("opacity", 1);
				break;
		}
	}
	
	if (arrow_data.to_status !== null) {
		switch (parseInt(arrow_data.to_status)) {
			case AGENT_MODULE_STATUS_ALL:
			case AGENT_MODULE_STATUS_UNKNOWN:
				arrow_head.selectAll("use")
					.style("opacity", 0);
				arrow_head.select("use[data-status='module_unknown']")
					.style("opacity", 1);
				break;
			case AGENT_MODULE_STATUS_CRITICAL_BAD:
			case AGENT_MODULE_STATUS_CRITICAL_ALERT:
			case AGENT_MODULE_STATUS_NOT_NORMAL:
				arrow_head.selectAll("use")
					.style("opacity", 0);
				arrow_head.select("use[data-status='module_critical']")
					.style("opacity", 1);
				break;
			case AGENT_MODULE_STATUS_NO_DATA:
			case AGENT_MODULE_STATUS_NOT_INIT:
			default:
				arrow_head.selectAll("use")
					.style("opacity", 0);
				arrow_head.select("use[data-status='no_data']")
					.style("opacity", 1);
				break;
			case AGENT_MODULE_STATUS_NORMAL:
			case AGENT_MODULE_STATUS_NORMAL_ALERT:
				arrow_head.selectAll("use")
					.style("opacity", 0);
				arrow_head.select("use[data-status='module_ok']")
					.style("opacity", 1);
				break;
			case AGENT_MODULE_STATUS_WARNING:
				arrow_head.selectAll("use")
					.style("opacity", 0);
				arrow_head.select("use[data-status='module_warning']")
					.style("opacity", 1);
				break;
			case AGENT_MODULE_STATUS_WARNING_ALERT:
				arrow_head.selectAll("use")
					.style("opacity", 0);
				arrow_head.select("use[data-status='module_alertsfired']")
					.style("opacity", 1);
				break;
		}
	}
}

/**
* Function arrow_by_pieces_AMA
* Return void
* This function print the arrow by pieces (3 steps)
*/
NetworkmapController.prototype.arrow_by_pieces_AMA = function(target, arrow_data) {
	// if (typeof(wait) === "undefined")
	// 	wait = 1;
	// 
	// var symbols = {};
	// 
	// symbols['images/maps/body_arrow.svg'] = {};
	// symbols['images/maps/body_arrow.svg']['target'] = "body";
	// symbols['images/maps/body_arrow.svg']['id_symbol'] = "#body_arrow";
	// 
	// symbols['images/maps/head_arrow_module_warning.svg'] = {};
	// symbols['images/maps/head_arrow_module_warning.svg']
	// 	['target'] = "tail";
	// symbols['images/maps/head_arrow_module_warning.svg']
	// 	['id_symbol'] = "#head_arrow_module_warning";
	// 
	// symbols['images/maps/head_arrow_module_unknown.svg'] = {};
	// symbols['images/maps/head_arrow_module_unknown.svg']
	// 	['target'] = "tail";
	// symbols['images/maps/head_arrow_module_unknown.svg']
	// 	['id_symbol'] = "#head_arrow_module_unknown";
	// 	
	// symbols['images/maps/head_arrow_module_ok.svg'] = {};
	// symbols['images/maps/head_arrow_module_ok.svg']
	// 	['target'] = "tail";
	// symbols['images/maps/head_arrow_module_ok.svg']
	// 	['id_symbol'] = "#head_arrow_module_ok";
	// 	
	// symbols['images/maps/head_arrow_module_no_data.svg'] = {};
	// symbols['images/maps/head_arrow_module_no_data.svg']
	// 	['target'] = "tail";
	// symbols['images/maps/head_arrow_module_no_data.svg']
	// 	['id_symbol'] = "#head_arrow_module_no_data";
	// 	
	// symbols['images/maps/head_arrow_module_critical.svg'] = {};
	// symbols['images/maps/head_arrow_module_critical.svg']
	// 	['target'] = "tail";
	// symbols['images/maps/head_arrow_module_critical.svg']
	// 	['id_symbol'] = "#head_arrow_module_critical";
	// 	
	// symbols['images/maps/head_arrow_module_alertsfired.svg'] = {};
	// symbols['images/maps/head_arrow_module_alertsfired.svg']
	// 	['target'] = "tail";
	// symbols['images/maps/head_arrow_module_alertsfired.svg']
	// 	['id_symbol'] = "#head_arrow_module_alertsfired";
	// 
	// var count_files = Object.keys(symbols).length;
	// function wait_load(callback) {
	// 	count_files--;
	// 	
	// 	if (count_files == 0) {
	// 		callback();
	// 	}
	// }
	// 
	// var arrow_layout = d3
	// 	.select(target +" #arrow_" + arrow_data['graph_id']);
	// 
	// switch (wait) {
	// 	/*---------------------------------------------*/
	// 	/*-------- Preload head and body arrow --------*/
	// 	/*---------------------------------------------*/
	// 	case 1:
	// 		arrow_layout = arrow_layout.append("g")
	// 			.attr("class", "arrow_position_rotation")
	// 			.append("g")
	// 				.attr("class", "arrow_translation")
	// 				.append("g")
	// 					.attr("class", "arrow_container");
	// 		
	// 		var arrow_body = arrow_layout.append("g")
	// 			.attr("class", "body");
	// 		var arrow_tail = arrow_layout.append("g")
	// 			.attr("class", "tail");
	// 		
	// 		$.each(symbols, function (i, s) {
	// 			if (is_buggy_firefox) {
	// 				switch (s['target']) {
	// 					case 'body':
	// 						arrow_body.append("use")
	// 							.attr("xlink:href", s['id_symbol']);
	// 						break;
	// 					case 'tail':
	// 						arrow_tail.append("use")
	// 							.attr("xlink:href", s['id_symbol']);
	// 						break;
	// 				}
	// 			}
	// 			else {
	// 				switch (s['target']) {
	// 					case 'body':
	// 						arrow_body.append("use")
	// 							.attr("xlink:href", i + s['id_symbol'])
	// 							.on("load", function() {
	// 								wait_load(function() {
	// 									arrow_by_pieces_AMA(
	// 										target, arrow_data, 0);
	// 								});
	// 							});
	// 						break;
	// 					case 'tail':
	// 						arrow_tail.append("use")
	// 							.attr("xlink:href", i + s['id_symbol'])
	// 							.on("load", function() {
	// 								wait_load(function() {
	// 									arrow_by_pieces_AMA(
	// 										target, arrow_data, 0);
	// 								});
	// 							});
	// 						break;
	// 				}
	// 			}
	// 		});
	// 		
	// 		if (is_buggy_firefox) {
	// 			arrow_by_pieces_AMA(target, arrow_data, 0);
	// 		}
	// 		break;
	// 	/*---------------------------------------------*/
	// 	/*---- Print head and body arrow by steps -----*/
	// 	/*---------------------------------------------*/
	// 	case 0:
	// 		var id_node_to = "node_" + arrow_data['to']['graph_id'];
	// 		var id_node_from = "node_" + arrow_data['from']['graph_id'];
	// 		
	// 		var c_elem2 = get_center_element(target +" #" + id_node_to);
	// 		var c_elem1 = get_center_element(target +" #" + id_node_from);
	// 		
	// 		var distance = get_distance_between_point(c_elem1, c_elem2);
	// 		
	// 		var radius_to = parseFloat(get_radius_element("#" + id_node_to));
	// 		var radius_from = parseFloat(get_radius_element("#" + id_node_from));
	// 		
	// 		var transform = d3.transform();
	// 	
	// 		/*---------------------------------------------*/
	// 		/*--- Position of layer arrow (body + head) ---*/
	// 		/*---------------------------------------------*/
	// 		var arrow_body = arrow_layout.select(".body");
	// 		var arrow_body_b = arrow_body.node().getBBox();
	// 		var arrow_body_height = (arrow_body_b['height'] + arrow_body_b['y']);
	// 		var arrow_body_width = (arrow_body_b['width'] + arrow_body_b['x']);
	// 		
	// 		transform.translate[0] = c_elem1[0];
	// 		transform.translate[1] = c_elem1[1];
	// 		transform.rotate = get_angle_of_line(c_elem1, c_elem2);
	// 		
	// 		arrow_layout.select(".arrow_position_rotation")
	// 			.attr("transform", transform.toString());
	// 		transform = d3.transform();
	// 		transform.translate[0] = radius_from;
	// 		transform.translate[1] = - (arrow_body_height / 2);
	// 		arrow_layout.select(".arrow_translation")
	// 			.attr("transform", transform.toString());
	// 		
	// 		/*---------------------------------------------*/
	// 		/*-------- Resize the body arrow width --------*/
	// 		/*---------------------------------------------*/
	// 		arrow_head_width = 0; //WIP
	// 		
	// 		var body_width = distance - arrow_head_width - radius_to - radius_from;
	// 		
	// 		transform = d3.transform();
	// 		transform.scale[0] = body_width / arrow_body_width;
	// 		
	// 		arrow_body.attr("transform", transform.toString());
	// 		
	// 		/*---------------------------------------------*/
	// 		/*---------- Position of head arrow -----------*/
	// 		/*---------------------------------------------*/
	// 		//~ transform = d3.transform();
	// 		//~ 
	// 		//~ var arrow_body_t = d3.transform(arrow_body.attr("transform"));
	// 		//~ 
	// 		//~ var scale = arrow_body_t.scale[0];
	// 		//~ var x = 0 + arrow_body_width * scale;
	// 		//~ var y = 0 + (arrow_body_height / 2  - arrow_head_height / 2);
	// 		//~ 
	// 		//~ transform.translate[0] = x;
	// 		//~ transform.translate[1] = y;
	// 		//~ 
	// 		//~ arrow_head.attr("transform", transform.toString());
	// 		
	// 		/*---------------------------------------------*/
	// 		/*------- Show the result in one time ---------*/
	// 		/*---------------------------------------------*/
	// 		arrow_layout.attr("style", "opacity: 1");
	// 		break;
	// }
}
