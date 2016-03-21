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
* Function is_arrow_module_to_module (Module -> Module)
* Return  void
* This function return if an arrow is module to module arrow
*/
NetworkmapController.prototype.is_arrow_module_to_module = function(id_to, id_from) {
	var return_var = false;
	var found_two = 0;
	
	$.each(nodes, function(i, node) {
		
		if ((node['graph_id'] == id_to) || (node['graph_id'] == id_from)) {
			
			found_two++;
			
			if (node['type'] == ITEM_TYPE_MODULE_NETWORKMAP) {
				return_var = true;
			}
			else {
				return_var = false;
				return false;
			}
		}
		
		if (found_two == 2)
			return false; // Break
	});
	
	return return_var;
}

/**
* Function is_arrow_AM (Agent -> Module)
* Return  void
* This function return if an arrow is module to agent arrow
*/
//~ NetworkmapController.prototype.is_arrow_AM = function(id_to, id_from) {
	//~ var self = this;
	//~ 
	//~ var return_var = false;
	//~ 
	//~ var arrow = self.get_arrow(id_to, id_from);
	//~ 
	//~ if (arrow === null) {
		//~ return false;
	//~ }
	//~ 
	//~ if (arrow['nodes'][0]['type'] == arrow['nodes'][1]['type']) {
		//~ return false;
	//~ }
	//~ 
	//~ return true;
//~ }

/**
* Function is_arrow_AMA (Agent -> Module -> Agent)
* Return  void
* This function return if an arrow is agent to module to agent arrow
*/
//~ NetworkmapController.prototype.is_arrow_AMA = function(id_to, id_from) {
	//~ var self = this;
	//~ var var_return = false;
	//~ 
	//~ if (self.is_arrow_AM(id_to, id_from)) {
		//~ var arrow = self.get_arrow(id_to, id_from);
		//~ 
		//~ var module = null;
		//~ if (arrow['nodes'][0]['type'] == ITEM_TYPE_MODULE_NETWORKMAP) {
			//~ module = arrow['nodes'][0];
		//~ }
		//~ else {
			//~ module = arrow['nodes'][1];
		//~ }
		//~ 
		//~ $.each(edges, function(i, edge) {
			//~ if ((edge['to'] == module['graph_id']) ||
				//~ (edge['from'] == module['graph_id'])) {
				//~ if (edge['graph_id'] != arrow['arrow']['graph_id']) {
					//~ var_return = true;
					//~ return false; //Break
				//~ }
			//~ }
		//~ });
	//~ }
	//~ 
	//~ return var_return;
//~ }

/**
* Function get_arrow_AMMA
* Return  array (arrow)
* This function returns an AMMA arrow
*/
NetworkmapController.prototype.get_arrow_AMMA = function(id_to, id_from) {
	var self = this;
	
	var return_var = null;
	
	var arrow = {};
	
	arrow['AMMA'] = 1;
	arrow['AMA'] = 0;
	
	if ((self.get_node_type(id_to) == ITEM_TYPE_MODULE_NETWORKMAP)
		&& (self.get_node_type(id_from) == ITEM_TYPE_MODULE_NETWORKMAP)) {
			
		var arrow_MM = self.get_arrow(id_to, id_from);
		
		var arrows_to = self.get_edges_from_node(id_to);
		var arrows_from = self.get_edges_from_node(id_from);
		
		var temp = null;
		$.each(arrows_to, function(i,arrow_to) {
			if (arrow_to['graph_id'] != arrow_MM['arrow']['graph_id']) {
				temp = arrow_to;
				return false;
			}
		});
		var arrow_to = temp;
		
		temp = null;
		$.each(arrows_from, function(i,arrow_from) {
			if (arrow_from['graph_id'] != arrow_MM['arrow']['graph_id']) {
				temp = arrow_from;
				return false;
			}
		});
		var arrow_from = temp;
		
		if (arrow_to !== null && arrow_from !== null) {
			// There is one arrow for A-M-M-A
			arrow_to = self.get_arrow(arrow_to['id_to'], arrow_to['id_from']);
			arrow_from = self.get_arrow(arrow_from['id_to'], arrow_from['id_from']);
			
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
			
			
			if (arrow_from['nodes']['to'] == arrow_MM['arrow']['from']) {
				arrow['from'] = arrow_from['nodes']['from'];
			}
			else {
				arrow['from'] = arrow_from['nodes']['to'];
			}
			arrow['to_module'] = arrow_MM['nodes']['from']['id'];
			
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
	var var_return = null;
	
	$.each(edges, function(i, edge) {
		if (self.get_node_type(id_to) != self.get_node_type(id_from)) {
			var arrow_AM = self.get_arrow(id_to, id_from);
			
			var edges_temp = null;
			var is_agent = null
			
			if (self.get_node_type(id_to) == ITEM_TYPE_AGENT_NETWORKMAP) {
				edges_temp = self.get_edges_from_node(id_from);
				is_agent = 'to';
			}
			else if (self.get_node_type(id_from) == ITEM_TYPE_AGENT_NETWORKMAP) {
				edges_temp = self.get_edges_from_node(id_to);
				is_agent = 'from';
			}
			
			if (edges_temp != null) {
				$.each(edges_temp, function(i, edge) {
					// Filter same edge
					if ((edge['to'] != id_to) && (edge['from'] != id_from)) {
						
						var type_to = self.get_node_type(edge['to']);
						var type_from = self.get_node_type(edge['from']);
						
						// Filter M-M edge
						if ((type_to != ITEM_TYPE_MODULE_NETWORKMAP)
							|| (type_from != ITEM_TYPE_MODULE_NETWORKMAP)) {
							
							
							var temp = {};
							
							temp['graph_id'] =
								arrow['arrow']['graph_id'] + "" +
								arrow_AM['arrow']['graph_id'] + "";
							
							temp['AMMA'] = 0;
							temp['AMA'] = 1;
							
							
						}
					}
				});
			}
		}
	});
	
	return var_return;
}

/**
* Function exists_arrow
* Return  bool
* This function returns if the arrow exists
*/
NetworkmapController.prototype.exists_arrow = function(arrows, arrow) {
	var var_return = false;
	
	$.each(arrows, function(i, a) {
		if (a['AMMA'] == arrow['AMMA']) {
			
		}
	});
}

/**
* Function paint_arrows
* Return  void
* This function paints the arrows
*/
NetworkmapController.prototype.paint_arrows = function() {
	var self = this;
	
	var clean_arrows = [];
	
	$.each(edges, function(i, edge) {
		
		var arrow_AMMA = self.get_arrow_AMMA(edge['to'], edge['from']);
		var arrows_AMA = self.get_arrows_AMA(edge['to'], edge['from']);
		
		if (arrow_AMMA !== null) {
			clean_arrows.push(arrow_AMMA);
		}
		else  if (arrows_AMA !== null) {
			$.each(arrows_AMA, function(i, arrow) {
				if (!self.exists_arrow(clean_arrows, arrow)) {
					clean_arrows.push(arrow_AMMA);
				}
			});
		}
	});
	
	//TO DO
	//~ var arrow_layouts = self._viewport.selectAll(".arrow")
		//~ .data(
			//~ edges
				//~ .filter(function(d, i) {
					//~ if (self.is_arrow_module_to_module(d['to'], d['from'])) {
						//~ return true;
					//~ }
					//~ else if (self.is_arrow_AMA(d['to'], d['from'])) {
						//~ if (self.is_arrow_in_map(d['to'], d['from']))
							//~ return false;
						//~ else
							//~ return true;
					//~ }
					//~ 
					//~ return false;
				//~ }))
		//~ .enter()
			//~ .append("g")
				//~ .attr("class", "arrow")
				//~ .attr("id", function(d) { return "arrow_" + d['graph_id'];})
				//~ 
				//~ .attr("data-id", function(d) { return d['id'];})
				//~ .attr("data-to", function(d) {
					//~ return self.node_from_edge(d['graph_id'])["to"];})
				//~ .attr("data-from", function(d) {
					//~ return self.node_from_edge(d['graph_id'])["from"];});
}
