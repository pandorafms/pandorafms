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
* Function get_arrow
* Return  void
* This function return a specific arrow
*/
NetworkmapController.prototype.get_arrow = function(id_to, id_from) {
	var arrow = [];
	
	var i = 0;
	$.each(nodes, function(i, node) {
		
		if ((node['graph_id'] == id_to) || (node['graph_id'] == id_from)) {
			
			arrow[i] = node;
			i++;
		}
	});
	
	if (i == 2)	{
		return arrow;
	}
	else {
		return null;
	}
}

/**
* Function is_arrow_module_to_module
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
* Function is_arrow_AM
* Return  void
* This function return if an arrow is module to agent arrow
*/
NetworkmapController.prototype.is_arrow_AM = function(id_to, id_from) {
	var self = this;
	
	var return_var = false;
	
	var arrow = self.get_arrow(id_to, id_from);
	
	if (arrow === null) {
		return false;
	}
	
	if (arrow[0]['type'] == arrow[1]['type']) {
		return false;
	}
	
	return true;
}

/**
* Function is_arrow_AMA
* Return  void
* This function return if an arrow is agent to module to agent arrow
*/
NetworkmapController.prototype.is_arrow_AMA = function(id_to, id_from) {
	var self = this;
	
	if (self.is_arrow_AM(id_to, id_from)) {
		var arrow = self.get_arrow(id_to, id_from);
		
		var module = null;
		if (arrow[0]['type'] == ITEM_TYPE_MODULE_NETWORKMAP) {
			module = arrow[0];
		}
		else {
			module = arrow[1];
		}
		
		$.each(edges, function(i, edge) {
			if (edge['to'] == module['graph_id']) {
				if (self.is_arrow_AM(module['graph_id'], edge['from'])) {
					
				}
			}
			else if (edge['from'] == module['graph_id']) {
				if (self.is_arrow_AM(module['graph_id'], edge['to'])) {
					
				}
			}
		});
	}
	
	return false;
}

/**
* Function paint_arrows
* Return  void
* This function paints the arrows
*/
NetworkmapController.prototype.paint_arrows = function() {
	var self = this;
	
	var arrow_layouts = self._viewport.selectAll(".arrow")
		.data(
			edges
				.filter(function(d, i) {
					if (self.is_arrow_module_to_module(d['to'], d['from'])) {
						return true;
					}
					//is_arrow_agent_to_module_to_agent
					else if (self.is_arrow_AMA(d['to'], d['from'])) {
						if (self.is_arrow_in_map(d['to'], d['from']))
							return false;
						else
							return true;
					}
					
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
}
