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
			
			
			if (arrow_from['nodes']['to'] == arrow_MM['arrow']['from']) {
				arrow['from'] = arrow_from['nodes']['from'];
			}
			else {
				arrow['from'] = arrow_from['nodes']['to'];
			}
			arrow['from_module'] = arrow_MM['nodes']['from']['id'];
			arrow['from_status'] = arrow_MM['nodes']['from']['status'];
			
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
								
								if (type_to == ITEM_TYPE_AGENT_NETWORKMAP) {
									temp['from'] = arrow['nodes']['to'];
								}
								else {
									temp['from'] = arrow['nodes']['from'];
								}
								temp['from_module'] = null;
								temp['from_status'] = null;
								break;
							case 'from':
								temp['from'] = arrow_AM['nodes']['from'];
								temp['from_module'] = arrow_AM['nodes']['to']['id'];
								temp['from_status'] = arrow_AM['nodes']['to']['status'];
								
								if (type_to == ITEM_TYPE_AGENT_NETWORKMAP) {
									temp['to'] = arrow['nodes']['to'];
								}
								else {
									temp['to'] = arrow['nodes']['from'];
								}
								temp['to_module'] = null;
								temp['to_status'] = null;
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
				arrow_AA['from'] = arrow['nodes']['from'];
				arrow_AA['from_module'] = null;
				
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

	//TO DO
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
			console.log(d['type']);
			switch (d['type']) {
				case 'AA':
					arrow_by_pieces(self._target + " svg",
						"arrow_" + d['graph_id'],
						"node_" + d['to']['graph_id'],
						"node_" + d['from']['graph_id']);
					break;
				case 'AMMA':
					arrow_by_pieces_AMMA(self._target + " svg", d);
					break;
				case 'AMA':
					arrow_by_pieces_AMA(self._target + " svg", d);
					break;
			}
		});
	}
	
}

/**
* Function arrow_by_pieces
* Return void
* This function print the arrow by pieces (3 steps)
*/
function arrow_by_pieces_AMMA(target, arrow_data, wait) {
	
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
				
				//~ arrow_layout.append("g")
					//~ .attr("class", "head")
					//~ .append("use")
						//~ .attr("xlink:href", "#head_arrow");
				
				arrow_by_pieces_AMMA(target, arrow_data, 0);
			}
			else {
				arrow_layout.append("g")
					.attr("class", "body")
					.append("use")
						.attr("xlink:href", "images/maps/body_arrow.svg#body_arrow")
						.on("load", function() {
							wait_load(function() {
								arrow_by_pieces_AMMA(
									target, arrow_data, 0);
							});
						});
				
				//~ arrow_layout.append("g")
					//~ .attr("class", "head")
					//~ .append("use")
						//~ .attr("xlink:href", "images/maps/head_arrow.svg#head_arrow")
						//~ .on("load", function() {
							//~ wait_load(function() {
								//~ arrow_by_pieces_AMMA(
									//~ target, arrow_data, 0);
							//~ });
						//~ });
			}
			break;
		/*---------------------------------------------*/
		/*---- Print head and body arrow by steps -----*/
		/*---------------------------------------------*/
		case 0:
			var id_node_to = arrow_data['to']['graph_id'];
			var id_node_from = arrow_data['from']['graph_id'];
			
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
			
			arrow_layout.select(".arrow_position_rotation")
				.attr("transform", transform.toString());
			transform = d3.transform();
			transform.translate[0] = radius_from;
			transform.translate[1] = - (arrow_body_height / 2);
			arrow_layout.select(".arrow_translation")
				.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*-------- Resize the body arrow width --------*/
			/*---------------------------------------------*/
			//~ var arrow_head = arrow_layout.select(".head");
			//~ var arrow_head_b = arrow_head.node().getBBox();
			//~ var arrow_head_height = (arrow_head_b['height'] + arrow_head_b['y']);
			//~ var arrow_head_width = (arrow_head_b['width'] + arrow_head_b['x']);
			//~ 
			//~ var body_width = distance - arrow_head_width - radius_to - radius_from;
			//~ 
			//~ transform = d3.transform();
			//~ transform.scale[0] = body_width / arrow_body_width;
			//~ 
			//~ arrow_body.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*---------- Position of head arrow -----------*/
			/*---------------------------------------------*/
			//~ transform = d3.transform();
			//~ 
			//~ var arrow_body_t = d3.transform(arrow_body.attr("transform"));
			//~ 
			//~ var scale = arrow_body_t.scale[0];
			//~ var x = 0 + arrow_body_width * scale;
			//~ var y = 0 + (arrow_body_height / 2  - arrow_head_height / 2);
			//~ 
			//~ transform.translate[0] = x;
			//~ transform.translate[1] = y;
			//~ 
			//~ arrow_head.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*------- Show the result in one time ---------*/
			/*---------------------------------------------*/
			arrow_layout.attr("style", "opacity: 1");
			break;
	}
}

function arrow_by_pieces_AMA(target, arrow_data) {
}
