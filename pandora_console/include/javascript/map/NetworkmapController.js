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
var NetworkmapController = function(target, refresh_time) {
	MapController.call(this, target, refresh_time);
}

/*-----------------------------------------------*/
/*------------------Atributes--------------------*/
/*-----------------------------------------------*/
NetworkmapController.prototype = Object.create(MapController.prototype);
NetworkmapController.prototype.constructor = NetworkmapController;

NetworkmapController.prototype._first_paint_arrows = true;
NetworkmapController.prototype._cache_elements = {};

/*-----------------------------------------------*/
/*--------------------Methods--------------------*/
/*-----------------------------------------------*/
/**
* Function init_map
* Return  void
* This function init the map
*/
NetworkmapController.prototype.init_map = function() {
	var self = this;
	var clean_arrows = [];
	
	console.log(self.get_nodes_map());
	console.log(self.get_edges_map());
	
	$.each(self.get_edges_map(), function(i, edge) {
		var arrow_AF_or_FF = self.get_arrow_AF_or_FF(edge['to'], edge['from']);
		if (arrow_AF_or_FF !== null) {
			if (!self.exists_arrow(clean_arrows, arrow_AF_or_FF)) {
				clean_arrows.push(arrow_AF_or_FF);
			}
		}
		
		var arrow_AA = self.get_arrow_AA(edge['graph_id'], edge['to'], edge['from']);
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
		
		if (self.get_subtype_map() == MAP_SUBTYPE_GROUPS) {
			var arrow_AG = self.get_arrow_AG(edge['to'], edge['from']);
			if (arrow_AG !== null) {
				if (!self.exists_arrow(clean_arrows, arrow_AG)) {
					clean_arrows.push(arrow_AG);
				}
			}
			var arrow_GG = self.get_arrow_GG(edge['to'], edge['from']);
			if (arrow_GG !== null) {
				if (!self.exists_arrow(clean_arrows, arrow_GG)) {
					clean_arrows.push(arrow_GG);
				}
			}
		}
		
		if (self.get_subtype_map() == MAP_SUBTYPE_POLICIES) {
			var arrow_PA = self.get_arrow_PA(edge['to'], edge['from']);
			if (arrow_PA !== null) {
				if (!self.exists_arrow(clean_arrows, arrow_PA)) {
					clean_arrows.push(arrow_PA);
				}
			}
		}
		
		if (self.get_filter_map()['show_modules']) {
			if (self.get_filter_map()['show_module_group']) {
				var arrow_GMA = self.get_arrow_GMA(edge['to'], edge['from']);
				if (arrow_GMA !== null) {
					if (!self.exists_arrow(clean_arrows, arrow_GMA)) {
						clean_arrows.push(arrow_GMA);
					}
				}
				var arrow_GMM = self.get_arrow_GMM(edge['to'], edge['from']);
				if (arrow_GMM !== null) {
					if (!self.exists_arrow(clean_arrows, arrow_GMM)) {
						clean_arrows.push(arrow_GMM);
					}
				}
			}
			else {
				var arrow_AM = self.get_arrow_AM(edge['to'], edge['from']);
				if (arrow_AM !== null) {
					if (!self.exists_arrow(clean_arrows, arrow_AM)) {
						if (!self.fake_arrow_AM(arrow_AM)) {
							clean_arrows.push(arrow_AM);
						}
					}
				}
			}
		}
	});
	
	clean_arrows.forEach(function(arrow, index) {
		for (var i = index + 1; i < clean_arrows.length; i++) {
			if (((arrow['to']['graph_id'] == clean_arrows[i]['to']['graph_id']) && 
				(arrow['from']['graph_id'] == clean_arrows[i]['from']['graph_id'])) || 
				((arrow['to']['graph_id'] == clean_arrows[i]['from']['graph_id']) &&
				(arrow['from']['graph_id'] == clean_arrows[i]['to']['graph_id']))) {
				if (arrow['type'] == 'AMMA') {
					delete clean_arrows[i];
				}
				else if (clean_arrows[i]['type'] == 'AMMA') {
					delete clean_arrows[index];
				}
				else if (arrow['type'] == 'AMA') {
					delete clean_arrows[i];
				}
				else if (clean_arrows[i]['type'] == 'AMA') {
					delete clean_arrows[index];
				}
				else {
					delete clean_arrows[i];
				}
			}
		}
	});
	var new_clean_arrows = [];
	var j = 0;
	clean_arrows.forEach(function(arrow, index) {
		new_clean_arrows[j] = arrow;
		j++;
	});
	clean_arrows = new_clean_arrows;
	
	self.update_edges_from_clean_arrows(clean_arrows);
	
	MapController.prototype.init_map.call(this);
};

/**
* Function fake_arrow_AM
* Return  bool
* This function returns if the AM arrow is a fake arrow
*/
NetworkmapController.prototype.fake_arrow_AM = function(arrow_AM) {
	var agent_to = parseInt(arrow_AM['to']['id_agent']);
	var agent_from = parseInt(arrow_AM['from']['id_agent']);
	if (agent_to == agent_from) {
		return false;
	}
	else {
		return true;
	}
}

/**
* Function filter_only_agents
* Return  void
* This function return if a node is an agent
*/
NetworkmapController.prototype.filter_only_agents = function(node) {
	var self = this;
	
	switch (node.type) {
		case ITEM_TYPE_EDGE_NETWORKMAP:
			return false;
			break;
		case ITEM_TYPE_MODULE_NETWORKMAP:
			if (self.get_filter_map()['show_modules']) {
				return true;
			}
			else {
				return false;
			}
			break;
		case ITEM_TYPE_MODULEGROUP_NETWORKMAP:
			if (self.get_filter_map()['show_modules']) {
				if (self.get_filter_map()['show_module_group']) {
					return true;
				}
				else {
					return false;
				}
			}
			else {
				return false;
			}
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
		
		//--------------------------------------------------------------
		//------ INIT CODE -- get arrows A-M from M-M ------------------
		//--------------------------------------------------------------
		var arrow_to = null;
		$.each(arrows_to, function(i, arw) {
			if (arw['graph_id'] != arrow_MM['arrow']['graph_id']) {
				node = null;
				
				if (arw['to'] == arrow_MM['arrow']['to']) {
					node = self.get_node(arw['from']);
				}
				else if (arw['from'] == arrow_MM['arrow']['to']) {
					node = self.get_node(arw['to']);
				}
				
				if (node['type'] == ITEM_TYPE_AGENT_NETWORKMAP &&
					node['id'] == arrow_MM['nodes']['to']['id_agent']) {
					
					arrow_to = arw;
				}
			}
		});
		
		var arrow_from = null;
		$.each(arrows_from, function(i, arw) {
			if (arw['graph_id'] != arrow_MM['arrow']['graph_id']) {
				node = null;
				
				if (arw['to'] == arrow_MM['arrow']['from']) {
					node = self.get_node(arw['from']);
				}
				else if (arw['from'] == arrow_MM['arrow']['from']) {
					node = self.get_node(arw['to']);
				}
				
				if (node['type'] == ITEM_TYPE_AGENT_NETWORKMAP &&
					node['id'] == arrow_MM['nodes']['from']['id_agent']) {
					
					arrow_from = arw;
				}
			}
		});
		//--------------------------------------------------------------
		//------ END CODE --- get arrows A-M from M-M ------------------
		//--------------------------------------------------------------
		
		if (arrow_to !== null && arrow_from !== null) {
			// There is one arrow for A-M-M-A
			
			
			
			// Get arrow with full data (nodes and arrow)
			arrow_to = self.get_arrow_from_id(arrow_to['graph_id']);
			arrow_to = self.get_arrow(
				arrow_to['to'], arrow_to['from']);
			
			arrow_from = self.get_arrow_from_id(arrow_from['graph_id']);
			arrow_from = self.get_arrow(
				arrow_from['to'], arrow_from['from']);
			
			// Make the new id with concatenate the id_to + id_mm + id_from
			arrow['graph_id'] = arrow_to['arrow']['graph_id'] + "" +
				arrow_MM['arrow']['graph_id'] + "" +
				arrow_from['arrow']['graph_id'];
			
			if (arrow_to['arrow']['from'] == arrow_MM['arrow']['to']) {
				arrow['to'] = arrow_to['nodes']['to'];
			}
			else {
				arrow['to'] = arrow_to['nodes']['from'];
			}
			arrow['to_module'] = arrow_MM['nodes']['to']['id'];
			arrow['to_status'] = arrow_MM['nodes']['to']['status'];
			arrow['to_title'] = arrow_MM['nodes']['to']['title'];
			
			if (arrow_from['arrow']['to'] == arrow_MM['arrow']['from']) {
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
						arrow = self.get_arrow(
							arrow['to'], arrow['from']);
						
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
* Function get_arrow_AF_or_FF
* Return arrow
* This function returns an arrow AF or FF (fictional-fictional or agent-fictional)
*/
NetworkmapController.prototype.get_arrow_AF_or_FF = function(id_to, id_from) {
	var self = this;
	var arrow_AF_or_FF;
	var found = false;
	
	$.each(self.get_edges_map(), function(i, edge) {
		
		if (self.get_node_type(id_to) == ITEM_TYPE_FICTIONAL_NODE ||
			self.get_node_type(id_from) == ITEM_TYPE_FICTIONAL_NODE) {
			var arrow = self.get_arrow(id_to, id_from);
			
			arrow_AF_or_FF = {};
			arrow_AF_or_FF['type'] = 'AF_or_FF';
			arrow_AF_or_FF['graph_id'] = arrow['arrow']['graph_id'];
			arrow_AF_or_FF['to'] = arrow['nodes']['to'];
			arrow_AF_or_FF['to_module'] = null;
			arrow_AF_or_FF['to_status'] = null;
			arrow_AF_or_FF['to_title'] = null;
			arrow_AF_or_FF['from'] = arrow['nodes']['from'];
			arrow_AF_or_FF['from_module'] = null;
			arrow_AF_or_FF['from_status'] = null;
			arrow_AF_or_FF['from_title'] = null;
			
			found = true;
		}
	});
	
	if (found) {
		return arrow_AF_or_FF;
	}
	else {
		return null;
	}
}

/**
* Function get_arrow_AA
* Return  array (arrow)
* This function returns an AA arrow
*/
NetworkmapController.prototype.get_arrow_AA = function(graph_id, id_to, id_from) {
	var self = this;
	var arrow_AA;
	
	if (self.get_node_type(id_to) == self.get_node_type(id_from)) {
		if (self.get_node_type(id_to) == ITEM_TYPE_AGENT_NETWORKMAP) {
			
			arrow_AA = {};
			arrow_AA['type'] = 'AA';
			arrow_AA['graph_id'] = graph_id;
			arrow_AA['to'] = self.get_node(id_to);
			arrow_AA['to_module'] = null;
			arrow_AA['to_status'] = null;
			arrow_AA['to_title'] = null;
			arrow_AA['from'] = self.get_node(id_from);
			arrow_AA['from_module'] = null;
			arrow_AA['from_status'] = null;
			arrow_AA['from_title'] = null;
			
			return arrow_AA;
		}
	}
	
	return null;
}

/**
* Function get_arrow_AG
* Return  array (arrow)
* This function returns an AG arrow
*/
NetworkmapController.prototype.get_arrow_AG = function(id_to, id_from) {
	var self = this;
	
	var arrow_AG;
	var found = false;
	
	$.each(self.get_edges_map(), function(i, edge) {
		if (self.get_node_type(id_to) == ITEM_TYPE_AGENT_NETWORKMAP ||
			self.get_node_type(id_from) == ITEM_TYPE_AGENT_NETWORKMAP) {
			if (self.get_node_type(id_to) == ITEM_TYPE_GROUP_NETWORKMAP ||
				self.get_node_type(id_from) == ITEM_TYPE_GROUP_NETWORKMAP) {
			
				var arrow = self.get_arrow(id_to, id_from);
				
				arrow_AG = {};
				arrow_AG['type'] = 'AG';
				arrow_AG['graph_id'] = arrow['arrow']['graph_id'];
				arrow_AG['to'] = arrow['nodes']['to'];
				arrow_AG['to_module'] = null;
				arrow_AG['to_status'] = null;
				arrow_AG['to_title'] = null;
				arrow_AG['from'] = arrow['nodes']['from'];
				arrow_AG['from_module'] = null;
				arrow_AG['from_status'] = null;
				arrow_AG['from_title'] = null;
				
				found = true;
			}
		}
	});
	
	if (found) {
		return arrow_AG;
	}
	else {
		return null;
	}
}

/**
* Function get_arrow_PA
* Return  array (arrow)
* This function returns an AG arrow
*/
NetworkmapController.prototype.get_arrow_PA = function(id_to, id_from) {
	var self = this;
	
	var arrow_PA;
	var found = false;
	
	$.each(self.get_edges_map(), function(i, edge) {
		if (self.get_node_type(id_to) == ITEM_TYPE_AGENT_NETWORKMAP ||
			self.get_node_type(id_from) == ITEM_TYPE_AGENT_NETWORKMAP) {
			if (self.get_node_type(id_to) == ITEM_TYPE_POLICY_NETWORKMAP ||
				self.get_node_type(id_from) == ITEM_TYPE_POLICY_NETWORKMAP) {
			
				var arrow = self.get_arrow(id_to, id_from);
				
				arrow_PA = {};
				arrow_PA['type'] = 'PA';
				arrow_PA['graph_id'] = arrow['arrow']['graph_id'];
				arrow_PA['to'] = arrow['nodes']['to'];
				arrow_PA['to_module'] = null;
				arrow_PA['to_status'] = null;
				arrow_PA['to_title'] = null;
				arrow_PA['from'] = arrow['nodes']['from'];
				arrow_PA['from_module'] = null;
				arrow_PA['from_status'] = null;
				arrow_PA['from_title'] = null;
				
				found = true;
			}
		}
	});
	
	if (found) {
		return arrow_PA;
	}
	else {
		return null;
	}
}

/**
* Function get_arrow_GG
* Return  array (arrow)
* This function returns an AG arrow
*/
NetworkmapController.prototype.get_arrow_GG = function(id_to, id_from) {
	var self = this;
	
	var arrow_GG;
	var found = false;
	
	$.each(self.get_edges_map(), function(i, edge) {
		if (self.get_node_type(id_to) == ITEM_TYPE_GROUP_NETWORKMAP &&
			self.get_node_type(id_from) == ITEM_TYPE_GROUP_NETWORKMAP) {
			
			var arrow = self.get_arrow(id_to, id_from);
			
			arrow_GG = {};
			arrow_GG['type'] = 'GG';
			arrow_GG['graph_id'] = arrow['arrow']['graph_id'];
			arrow_GG['to'] = arrow['nodes']['to'];
			arrow_GG['to_module'] = null;
			arrow_GG['to_status'] = null;
			arrow_GG['to_title'] = null;
			arrow_GG['from'] = arrow['nodes']['from'];
			arrow_GG['from_module'] = null;
			arrow_GG['from_status'] = null;
			arrow_GG['from_title'] = null;
			
			found = true;
		}
	});
	
	if (found) {
		return arrow_GG;
	}
	else {
		return null;
	}
}

/**
* Function get_arrow_GMM
* Return  array (arrow)
* This function returns an GMM arrow
*/
NetworkmapController.prototype.get_arrow_GMM = function(id_to, id_from) {
	var self = this;
	
	var arrow_GMM;
	var found = false;
	
	$.each(self.get_edges_map(), function(i, edge) {
		if (self.get_node_type(id_to) == ITEM_TYPE_MODULEGROUP_NETWORKMAP ||
			self.get_node_type(id_from) == ITEM_TYPE_MODULEGROUP_NETWORKMAP) {
			if (self.get_node_type(id_to) == ITEM_TYPE_MODULE_NETWORKMAP ||
				self.get_node_type(id_from) == ITEM_TYPE_MODULE_NETWORKMAP) {
				
				var arrow = self.get_arrow(id_to, id_from);
				
				arrow_GMM = {};
				arrow_GMM['type'] = 'GMM';
				arrow_GMM['graph_id'] = arrow['arrow']['graph_id'];
				arrow_GMM['to'] = arrow['nodes']['to'];
				arrow_GMM['to_module'] = null;
				arrow_GMM['to_status'] = null;
				arrow_GMM['to_title'] = null;
				arrow_GMM['from'] = arrow['nodes']['from'];
				arrow_GMM['from_module'] = null;
				arrow_GMM['from_status'] = null;
				arrow_GMM['from_title'] = null;
				
				found = true;
			}
		}
	});
	
	if (found) {
		return arrow_GMM;
	}
	else {
		return null;
	}
}

/**
* Function get_arrow_GMA
* Return  array (arrow)
* This function returns an GMA arrow
*/
NetworkmapController.prototype.get_arrow_GMA = function(id_to, id_from) {
	var self = this;
	
	var arrow_GMA;
	var found = false;
	
	$.each(self.get_edges_map(), function(i, edge) {
		if (self.get_node_type(id_to) == ITEM_TYPE_MODULEGROUP_NETWORKMAP ||
			self.get_node_type(id_from) == ITEM_TYPE_MODULEGROUP_NETWORKMAP) {
			if (self.get_node_type(id_to) == ITEM_TYPE_AGENT_NETWORKMAP ||
				self.get_node_type(id_from) == ITEM_TYPE_AGENT_NETWORKMAP) {
				
				var arrow = self.get_arrow(id_to, id_from);
				
				arrow_GMA = {};
				arrow_GMA['type'] = 'GMA';
				arrow_GMA['graph_id'] = arrow['arrow']['graph_id'];
				arrow_GMA['to'] = arrow['nodes']['to'];
				arrow_GMA['to_module'] = null;
				arrow_GMA['to_status'] = null;
				arrow_GMA['to_title'] = null;
				arrow_GMA['from'] = arrow['nodes']['from'];
				arrow_GMA['from_module'] = null;
				arrow_GMA['from_status'] = null;
				arrow_GMA['from_title'] = null;
				
				found = true;
			}
		}
	});
	
	if (found) {
		return arrow_GMA;
	}
	else {
		return null;
	}
}

/**
* Function get_arrow_AM
* Return  array (arrow)
* This function returns an AM arrow
*/
NetworkmapController.prototype.get_arrow_AM = function(id_to, id_from) {
	var self = this;
	
	var arrow_AM;
	var found = false;
	
	$.each(self.get_edges_map(), function(i, edge) {
		if (self.get_node_type(id_to) == ITEM_TYPE_AGENT_NETWORKMAP ||
			self.get_node_type(id_from) == ITEM_TYPE_AGENT_NETWORKMAP) {
			if (self.get_node_type(id_to) == ITEM_TYPE_MODULE_NETWORKMAP ||
				self.get_node_type(id_from) == ITEM_TYPE_MODULE_NETWORKMAP) {
				
				var arrow = self.get_arrow(id_to, id_from);
				
				arrow_AM = {};
				arrow_AM['type'] = 'AM';
				arrow_AM['graph_id'] = arrow['arrow']['graph_id'];
				arrow_AM['to'] = arrow['nodes']['to'];
				arrow_AM['to_module'] = null;
				arrow_AM['to_status'] = null;
				arrow_AM['to_title'] = null;
				arrow_AM['from'] = arrow['nodes']['from'];
				arrow_AM['from_module'] = null;
				arrow_AM['from_status'] = null;
				arrow_AM['from_title'] = null;
				
				found = true;
			}
		}
	});
	
	if (found) {
		return arrow_AM;
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
				case 'AF_or_FF':
				case 'GMA':
				case 'AG':
				case 'GMM':
				case 'GG':
				case 'PA':
				case 'AM':
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
* Function update_node
* Return void
* This function updates the node status
*/
NetworkmapController.prototype.update_node = function(id) {
	var self = this;
	
	var node = self.get_node_filter('id', id);
	
	if (d3.select(self._target + " #node_" + node['graph_id']).node() == null)
		return;
	
	d3.select(self._target + " #node_" + node['graph_id'])
		.attr("data-status", node['status'])
		.attr("data-status_color", node['color'])
		.style("fill", node['color']);
}

/**
* Function update_arrow
* Return void
* This function updates the interfaces status
*/
NetworkmapController.prototype.update_arrow = function(graph_id) {
	var self = this;
	
	var arrow = self.get_arrow_from_id(graph_id);
	
	if ((arrow['type'] == "AMA") || (arrow['type'] == "AMMA")) {
		self.update_interfaces_status(arrow);
	}
}

/**
* Function get_type_arrow
* Return void
* This function returns the arrow type
*/
NetworkmapController.prototype.get_type_arrow = function(graph_id) {
	var self = this;
	
	var return_var = null;
	
	$.each(self.get_edges_map(), function(i, edge) {
		if (edge['graph_id'] == graph_id) {
			return_var = edge['type'];
			return false;
		}
	});
	
	return return_var;
}

/**
* Function paint_node
* Return void
* This function paints the node
*/
NetworkmapController.prototype.paint_node = function(g_node, node) {
	var self = this;
	
	var d3_node = d3.select(g_node)
		.attr("transform", "translate(" + node['x'] + " " + node['y'] + ")")
		.attr("class", "draggable node")
		.attr("id", "node_" + node['graph_id'])
		.attr("style", "fill: rgb(50, 50, 128);")
		.attr("data-id", node['id'])
		.attr("data-graph_id", node['graph_id'])
		.attr("data-type", node['type'])
		.attr("data-status", node['status'])
		.attr("data-status_color", node['color']);
	
	var d3_node_icon = d3_node.append("g")
		.attr("class", "icon")
		.attr("class", "layout_size_node");
	var d3_node_title_layout = d3_node.append("g")
		.attr("class", "title");
	
	switch (node['shape']) {
		case 'square':
			d3_node_icon.append("rect")
				.attr("height", node['height'])
				.attr("width", node['width'])
				.attr("x", 0)
				.attr("y", 0)
				.style("stroke", "#000000")
				.style("stroke-width", 1);
			break;
		case 'circle':
			d3_node_icon.append("circle")
				.attr("r", node['width'] / 2)
				.attr("transform", "translate(" +
					node['width'] / 2 + " " +
					node['height'] / 2 + ")")
				.style("stroke", "#000000")
				.style("stroke-width", 1);
			break;
		case 'rhombus':
			d3_node_icon.append("rect")
				.attr("transform",
					"rotate(45 " + (node['width'] / 2) + " " + (node['height'] / 2) + " )")
				.attr("height", node['height'])
				.attr("width", node['width'])
				.attr("x", 0)
				.attr("y", 0)
				.style("stroke", "#000000")
				.style("stroke-width", 1);
			break;
	}
	
	// Title
	var d3_node_title = d3_node_title_layout.append("text");
	
	d3_node_title
		.text(node['title'])
		.style("fill", "#000000")
		.style("font-size", "9px")
		.style("font-style", "normal")
		.style("font-weight", "normal")
		.style("line-height", "125%")
		.style("letter-spacing", "0px")
		.style("word-spacing", "0px")
		.style("font-family", "Sans");
	
	//Title position
	var title_bbox = d3_node_title.node().getBBox();
	
	var x = node['width'] / 2 - title_bbox.width / 2;
	var y = node['height'] - title_bbox.y;
	
	d3_node_title
		.attr("transform", "translate(" + x + " " + y + ")");
	
	d3_node
		.style("fill", node['color']);
	
	d3_node_icon
		.append("image")
		.attr("xlink:href", node['image'])
		.attr("x", NODE_IMAGE_PADDING)
		.attr("y", NODE_IMAGE_PADDING)
		.attr("height", node['height'] - NODE_IMAGE_PADDING * 2)
		.attr("width", node['width'] - NODE_IMAGE_PADDING * 2);
}

/**
* Function paint_nodes
* Return void
* This function paint the nodes
*/
NetworkmapController.prototype.paint_nodes = function() {
	var self = this;
	
	self._viewport.selectAll(".node")
		.data(
			self.get_nodes_map()
				.filter(function(d, i) {
					return self.filter_only_agents(d);
				}))
			.enter()
				.append("g")
					.each(function(node) {self.paint_node(this, node);});
}

/**
* Function paint_arrows
* Return  void
* This function paints the arrows
*/
NetworkmapController.prototype.paint_arrows = function() {
	var self = this;
	
	var arrow_layouts = self._viewport.selectAll(".arrow")
		.data(self.get_edges_map())
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
	
	arrow_layouts.each(function(d) {
		self.arrow_by_pieces(self._target + " svg", d);
	});
	
	self._first_paint_arrows = false;
}

/**
* Function arrow_by_pieces
* Return  void
* This function creates the arrow by pieces
*/
NetworkmapController.prototype.arrow_by_pieces = function (target, arrow_data, wait) {
	var self = this;
	
	if (typeof(wait) === "undefined") {
		wait = 1;
	}
	
	if (arrow_data['temp']) {
		self.arrow_by_pieces_AA(
			self._target + " svg", arrow_data, wait);
	}
	else {
		switch (arrow_data['type']) {
			case 'AF_or_FF':
			case 'AG':
			case 'GMA':
			case 'GMM':
			case 'GG':
			case 'PA':
			case 'AA':
			case 'AM':
				self.arrow_by_pieces_AA(self._target + " svg", arrow_data, wait);
				break;
			case 'AMMA':
				self.arrow_by_pieces_AMMA(self._target + " svg", arrow_data, wait);
				break;
			case 'AMA':
				self.arrow_by_pieces_AMA(self._target + " svg", arrow_data, wait);
				break;
		}
	}
}

/**
* Function make_arrow
* Return void
* This function makes AA arrows
*/
NetworkmapController.prototype.make_arrow = function(from_id, to_id) {
	var self = this;
	
	var edge = {};
	
	edge = {};
	edge['type'] = 'AA';
	edge['graph_id'] = from_id + "" + to_id;
	edge['to'] = self.get_node(to_id);
	edge['to_module'] = null;
	edge['to_status'] = null;
	edge['to_title'] = null;
	edge['from'] = self.get_node(from_id);
	edge['from_module'] = null;
	edge['from_status'] = null;
	edge['from_title'] = null;
	
	self.get_edges_map().push(edge);
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
			
			var x = radius_from;
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
			
			self.re_rotate_interfaces_title(arrow_data);
			self.truncate_interfaces_title(arrow_data);
			
			/*---------------------------------------------*/
			/*------- Show the result in one time ---------*/
			/*---------------------------------------------*/
			arrow_layout.attr("style", "opacity: 1");
			break;
	}
}

/**
* Function arrow_by_pieces_AA
* Return void
* This function print the arrow by pieces (3 steps)
*/
NetworkmapController.prototype.arrow_by_pieces_AA = function(target, arrow_data, wait) {
	var self = this;
	
	if (typeof(wait) === "undefined")
		wait = 1;
	
	var count_files = 1;
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
				if (self._first_paint_arrows) {
					if (self.cache_is_element("center" + id_node_to)) {
						var c_elem2 = self._cache_elements["center" + id_node_to];
					}
					else {
						var c_elem2 = get_center_element(self._target +" #" + id_node_to);
						self._cache_elements["center" + id_node_to] = c_elem2;
					}
				}
				else {
					var c_elem2 = get_center_element(self._target +" #" + id_node_to);
				}
				
				if (self._first_paint_arrows) {
					if (self.cache_is_element("center" + id_node_from)) {
						var c_elem1 = self._cache_elements["center" + id_node_from];
					}
					else {
						var c_elem1 = get_center_element(self._target +" #" + id_node_from);
						self._cache_elements["center" + id_node_from] = c_elem1;
					}
				}
				else {
					var c_elem1 = get_center_element(self._target +" #" + id_node_from);
				}
				
				if (self._first_paint_arrows) {
					if (self.cache_is_element("radius" + id_node_to)) {
						var radius_to = self._cache_elements["radius" + id_node_to];
					}
					else {
						var radius_to = parseFloat(get_radius_element("#" + id_node_to));
						self._cache_elements["radius" + id_node_to] = radius_to;
					}
				}
				else {
					var radius_to = parseFloat(get_radius_element("#" + id_node_to));
				}
				
				
				if (self._first_paint_arrows) {
					if (self.cache_is_element("radius" + id_node_from)) {
						var radius_from = self._cache_elements["radius" + id_node_from];
					}
					else {
						var radius_from = parseFloat(get_radius_element("#" + id_node_from));
						self._cache_elements["radius" + id_node_from] = radius_from;
					}
				}
				else {
					var radius_from = parseFloat(get_radius_element("#" + id_node_from));
				}
			}
			
			var distance = get_distance_between_point(c_elem1, c_elem2);
			
			var transform = d3.transform();
			
			/*---------------------------------------------*/
			/*--- Position of layer arrow (body + head) ---*/
			/*---------------------------------------------*/
			var arrow_body = arrow_layout.select(".body");
			
			if (self._first_paint_arrows) {
				if (self.cache_is_element("arrow_body_b")) {
					var arrow_body_b = self._cache_elements["arrow_body_b"];
				}
				else {
					var arrow_body_b = arrow_body.node().getBBox();
					self._cache_elements["arrow_body_b"] = arrow_body_b;
				}
			}
			else {
				var arrow_body_b = arrow_body.node().getBBox();
			}
			
			var arrow_body_height = (arrow_body_b['height'] + arrow_body_b['y']);
			var arrow_body_width = (arrow_body_b['width'] + arrow_body_b['x']);
			
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
			var arrow_head_width = 0;
			
			var body_width = distance - arrow_head_width - radius_to - radius_from;
			
			transform = d3.transform();
			transform.scale[0] = body_width / arrow_body_width;
			
			arrow_body.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*------- Show the result in one time ---------*/
			/*---------------------------------------------*/
			arrow_layout.attr("style", "opacity: 1");
			break;
	}
}

/**
* Function arrow_by_pieces_AMA
* Return void
* This function print the arrow by pieces (3 steps)
*/
NetworkmapController.prototype.arrow_by_pieces_AMA = function(target, arrow_data, wait) {
	var self = this;
	
	var A_is_tail = false;
	var AM_is_tail = false;
	if (arrow_data['from_module'] === null) {
		A_is_tail = true;
	}
	else {
		AM_is_tail = true;
	}

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
			if (A_is_tail) {
				var arrow_head = arrow_layout.append("g")
					.attr("class", "head");
				var arrow_head_title = arrow_layout.append("g")
					.attr("class", "head_title");
				arrow_head_title.append("text").text(arrow_data['to_title']);
			}
			else {
				var arrow_tail = arrow_layout.append("g")
					.attr("class", "tail");
				var arrow_tail_title = arrow_layout.append("g")
					.attr("class", "tail_title");
				arrow_tail_title.append("text").text(arrow_data['from_title']);
			}
			
			$.each(symbols, function (i, s) {
				if (is_buggy_firefox) {
					switch (s['target']) {
						case 'body':
							arrow_body.append("use")
								.attr("xlink:href", s['id_symbol']);
							break;
						case 'head_tail':
							if (A_is_tail) {
								arrow_head.append("use")
									.style("opacity", 0)
									.attr("data-status", s['status'])
									.attr("xlink:href", s['id_symbol']);
							}
							else {
								arrow_tail.append("use")
									.style("opacity", 0)
									.attr("data-status", s['status'])
									.attr("xlink:href", s['id_symbol']);
							}
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
										self.arrow_by_pieces_AMA(
											target, arrow_data, 0);
									});
								});
							break;
						case 'head_tail':
							if (A_is_tail) {
								arrow_head.append("use")
									.style("opacity", 0)
									.attr("data-status", s['status'])
									.attr("xlink:href", i + s['id_symbol'])
									.on("load", function() {
										wait_load(function() {
											self.arrow_by_pieces_AMA(
												target, arrow_data, 0);
										});
									});
							}
							else {
								arrow_tail.append("use")
									.style("opacity", 0)
									.attr("data-status", s['status'])
									.attr("xlink:href", i + s['id_symbol'])
									.on("load", function() {
										wait_load(function() {
											self.arrow_by_pieces_AMA(
												target, arrow_data, 0);
										});
									});
							}
							break;
					}
				}
			});
			
			if (is_buggy_firefox) {
				self.arrow_by_pieces_AMA(target, arrow_data, 0);
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
			
			if (A_is_tail) {
				var arrow_head = arrow_layout.select(".head");
				var arrow_head_b = arrow_head.node().getBBox();
				var arrow_head_height = (arrow_head_b['height'] + arrow_head_b['y']);
				var arrow_head_width = (arrow_head_b['width'] + arrow_head_b['x']);
			}
			else {
				var arrow_tail = arrow_layout.select(".tail");
				var arrow_tail_b = arrow_tail.node().getBBox();
				var arrow_tail_height = (arrow_tail_b['height'] + arrow_tail_b['y']);
				var arrow_tail_width = (arrow_tail_b['width'] + arrow_tail_b['x']);
			}
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
			
			if (A_is_tail) {
				transform.translate[0] = radius_from;
			}
			else {
				transform.translate[0] = radius_from + arrow_tail_width;
			}
			transform.translate[1] = - (arrow_body_height / 2);
			arrow_layout.select(".arrow_translation")
				.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*-------- Resize the body arrow width --------*/
			/*---------------------------------------------*/
			if (A_is_tail) {
				var body_width = distance - arrow_head_width - radius_to - radius_from;
			}
			else {
				var body_width = distance - arrow_tail_width - radius_to - radius_from;
			}
			
			transform = d3.transform();
			transform.scale[0] = body_width / arrow_body_width;
			
			arrow_body.attr("transform", transform.toString());
			
			/*---------------------------------------------*/
			/*---------- Position of head arrow -----------*/
			/*---------------------------------------------*/
			if (A_is_tail) {
				transform = d3.transform();
				
				var arrow_body_t = d3.transform(arrow_body.attr("transform"));
				
				var scale = arrow_body_t.scale[0];
				var x = 0 + arrow_body_width * scale;
				var y = 0 + (arrow_body_height / 2  - arrow_head_height / 2);
				
				transform.translate[0] = x;
				transform.translate[1] = y;
				
				arrow_head.attr("transform", transform.toString());
			}
			else {
				transform = d3.transform();
				
				var arrow_body_t = d3.transform(arrow_body.attr("transform"));
				
				var scale = arrow_body_t.scale[0];
				
				x = 0 - arrow_tail_width;
				y = 0 + (arrow_body_height / 2  - arrow_tail_height / 2);
				
				transform.translate[0] = x;
				transform.translate[1] = y;
				
				arrow_tail.attr("transform", transform.toString());
			}
			self.update_interfaces_status(arrow_data);
			
			if (A_is_tail) {
				/*---------------------------------------------*/
				/*---------- Position the title of head -------*/
				/*---------------------------------------------*/
				var arrow_head_title = arrow_layout.select(".head_title");
				
				var arrow_head_title_b = arrow_head_title.node().getBBox();
				var arrow_head_title_height =
					(arrow_head_title_b['height'] + arrow_head_title_b['y']);
				
				transform = d3.transform();
				
				var x = body_width - arrow_head_title_height - arrow_head_width - radius_to - 10;
				var y = 0 + (arrow_body_height / 2  - arrow_head_title_height / 2);
				
				transform.translate[0] = x;
				transform.translate[1] = y;
				
				arrow_head_title.attr("transform", transform.toString());
			}
			else {
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
				var y = 0 + (arrow_body_height / 2);
				
				transform.translate[0] = x;
				transform.translate[1] = y;

				arrow_tail_title.attr("transform", transform.toString());
			}

			self.re_rotate_interfaces_title(arrow_data);
			self.truncate_interfaces_title(arrow_data);
			
			/*---------------------------------------------*/
			/*------- Show the result in one time ---------*/
			/*---------------------------------------------*/
			arrow_layout.attr("style", "opacity: 1");
			break;
	}
}

/**
* Function truncate_interfaces_title
* Return  void
* This function truncates the interface text title
*/
NetworkmapController.prototype.truncate_interfaces_title = function(arrow_data) {
	var self = this;
	
	var A_is_tail = false;
	var AM_is_tail = false;
	if (arrow_data["type"] == "AMA") {
		if (arrow_data['from_module'] === null) {
			A_is_tail = true;
		}
		else {
			AM_is_tail = true;
		}
	}
	
	var arrow_layout = d3.select(self._target +" #arrow_" + arrow_data['graph_id']);
	
	if (AM_is_tail) {
		var arrow_tail_title = arrow_layout.select(".tail_title");
		var arrow_tail_title_text = arrow_tail_title.select("text");
		var arrow_tail_title_b = arrow_tail_title.node().getBBox();
		var arrow_tail_title_width =
			(arrow_tail_title_b['width'] + arrow_tail_title_b['x']);

		var arrow_body = arrow_layout.select(".body");
		var arrow_body_b = arrow_body.node().getBBox();
		var arrow_body_width = (arrow_body_b['width'] + arrow_body_b['x']);
		
		var arrow_body_t = d3.transform(arrow_body.attr("transform"));
		
		var scale = arrow_body_t.scale[0];
		
		var total = arrow_tail_title_width + 10 + 10;
		
		if (total >= (scale * arrow_body_width)) {
			// Truncate
			arrow_tail_title_text.text("");
		}
		else {
			arrow_tail_title_text.text(arrow_data['from_title']);
		}
	}
	else if (A_is_tail) {
		var arrow_head_title = arrow_layout.select(".head_title");
		var arrow_head_title_text = arrow_head_title.select("text");
		var arrow_head_title_b = arrow_head_title.node().getBBox();
		var arrow_head_title_width =
			(arrow_head_title_b['width'] + arrow_head_title_b['x']);

		var arrow_body = arrow_layout.select(".body");
		var arrow_body_b = arrow_body.node().getBBox();
		var arrow_body_width = (arrow_body_b['width'] + arrow_body_b['x']);

		var arrow_body_t = d3.transform(arrow_body.attr("transform"));

		var scale = arrow_body_t.scale[0];

		var total = arrow_head_title_width + 10 + 10;

		if (total >= (scale * arrow_body_width)) {
			// Truncate
			arrow_head_title_text.text("");
		}
		else {
			arrow_head_title_text.text(arrow_data['to_title']);
		}
	}
	else {
		var arrow_tail_title = arrow_layout.select(".tail_title");
		var arrow_tail_title_text = arrow_tail_title.select("text");
		var arrow_tail_title_b = arrow_tail_title.node().getBBox();
		var arrow_tail_title_width =
			(arrow_tail_title_b['width'] + arrow_tail_title_b['x']);
		
		var arrow_head_title = arrow_layout.select(".head_title");
		var arrow_head_title_text = arrow_head_title.select("text");
		var arrow_head_title_b = arrow_head_title.node().getBBox();
		var arrow_head_title_width =
			(arrow_head_title_b['width'] + arrow_head_title_b['x']);

		var arrow_body = arrow_layout.select(".body");
		var arrow_body_b = arrow_body.node().getBBox();
		var arrow_body_width = (arrow_body_b['width'] + arrow_body_b['x']);
		
		var arrow_body_t = d3.transform(arrow_body.attr("transform"));
		
		var scale = arrow_body_t.scale[0];
		
		var total = arrow_tail_title_width + 10 + 10;
		
		if (total >= (scale * arrow_body_width)) {
			// Truncate
			arrow_head_title_text.text("");
			arrow_tail_title_text.text("");
		}
		else {
			arrow_head_title_text.text(arrow_data['to_title']);
			arrow_tail_title_text.text(arrow_data['from_title']);
		}
	}
}

/**
* Function re_rotate_interfaces_title
* Return  void
* This function rotate the interface text title
*/
NetworkmapController.prototype.re_rotate_interfaces_title = function(arrow_data) {
	var self = this;
	
	var A_is_tail = false;
	var AM_is_tail = false;
	if (arrow_data["type"] == "AMA") {
		if (arrow_data['from_module'] === null) {
			A_is_tail = true;
		}
		else {
			AM_is_tail = true;
		}
	}
	
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
	
	var arrow_layout = d3.select(self._target +" #arrow_" + arrow_data['graph_id']);
	
	if (AM_is_tail) {
		var arrow_tail_title = arrow_layout.select(".tail_title");
		var arrow_tail_title_text = arrow_tail_title.select("text");
	}
	else if (A_is_tail) {
		var arrow_head_title = arrow_layout.select(".head_title");
		var arrow_head_title_text = arrow_head_title.select("text");
	}
	else {
		var arrow_tail_title = arrow_layout.select(".tail_title");
		var arrow_tail_title_text = arrow_tail_title.select("text");

		var arrow_head_title = arrow_layout.select(".head_title");
		var arrow_head_title_text = arrow_head_title.select("text");
	}
	var transform;
	
	if (rotate) {
		if (AM_is_tail) {
			var arrow_tail_title_b = arrow_tail_title.node().getBBox();
			
			transform = d3.transform();
			
			var center_tail_x = arrow_tail_title_b['width'] / 2 + arrow_tail_title_b['x'];
			var center_tail_y = arrow_tail_title_b['height'] / 2 + arrow_tail_title_b['y'];
			
			transform.rotate = "180 " + center_tail_x + " " + center_tail_y;
			arrow_tail_title_text.attr("transform", transform.toString());
		}
		else if (A_is_tail) {
			var arrow_head_title_b = arrow_head_title.node().getBBox();
			
			transform = d3.transform();
			
			var center_head_x = arrow_head_title_b['width'] / 2 + arrow_head_title_b['x'];
			var center_head_y = arrow_head_title_b['height'] / 2 + arrow_head_title_b['y'];
			
			transform.rotate = "180 " + center_head_x + " " + center_head_y;
			arrow_head_title_text.attr("transform", transform.toString());
		}
		else {
			var arrow_tail_title_b = arrow_tail_title.node().getBBox();
			
			transform = d3.transform();
			var center_tail_x = (arrow_tail_title_b['width'] / 2);
			var center_tail_y = (arrow_tail_title_b['height'] / 2) + 10;
			
			transform.rotate = "180 " + center_tail_x + " " + center_tail_y;
			arrow_tail_title_text.attr("transform", transform.toString());

			var arrow_head_title_b = arrow_head_title.node().getBBox();
			
			transform = d3.transform();
			var center_head_x = (arrow_head_title_b['width'] / 2);
			var center_head_y = (arrow_head_title_b['height'] / 2) + 10;
			
			transform.rotate = "180 " + center_head_x + " " + center_head_y;
			arrow_head_title_text.attr("transform", transform.toString());
		}
	}
	else {
		if (AM_is_tail) {
			transform = d3.transform();;
			arrow_tail_title_text.attr("transform", transform.toString());
		}
		else if (A_is_tail) {
			transform = d3.transform();;
			arrow_head_title_text.attr("transform", transform.toString());
		}
		else {
			transform = d3.transform();;
			arrow_tail_title_text.attr("transform", transform.toString());

			transform = d3.transform();;
			arrow_head_title_text.attr("transform", transform.toString());
		}
	}
}

/**
* Function update_interfaces_status
* Return  void
* This function updates the interfaces status
*/
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
* Function refresh_nodes
* Return void
* This function refresh the nodes
*/
NetworkmapController.prototype.refresh_nodes = function() {
	var self = this;
	
	var params = {};
	params["refresh_nodes_open"] = 1;
	params["id_map"] = self._id;
	params["page"] = "include/ajax/map.ajax";
	var agent_nodes = $.grep(self.get_nodes_map(),
		function(node) {
			if (node['type'] == 0) {
				return true;
			}
			else {
				return false;
			}
	});
	
	params['nodes'] = [];
	$.each(agent_nodes, function(i, node) {
		params['nodes'].push(node['id']);
	});
	
	jQuery.ajax ({
		data: params,
		dataType: "JSON",
		type: "POST",
		url: "ajax.php",
		success: function (data) {
			$.each(self.get_nodes_map(), function(i, node) {
				if (node['type'] != ITEM_TYPE_AGENT_NETWORKMAP)
					return true;
				
				if (typeof(data[node['id']]) != "undefined") {
					self.get_nodes_map()[i]['status'] = data[node['id']]['status'];
					self.get_nodes_map()[i]['color'] = data[node['id']]['color'];
					
					self.update_node(node['id']);
				}
			});
		}
	});
}

NetworkmapController.prototype.refresh_map = function() {
	MapController.prototype.refresh_map.call(this);
}

/**
* Function refresh_arrows
* Return void
* This function refresh the arrows
*/
NetworkmapController.prototype.refresh_arrows = function() {
	var self = this;
	
	var params = {};
	params["refresh_arrows_open"] = 1;
	params["id_map"] = self._id;
	params["page"] = "include/ajax/map.ajax";
	
	var arrows_AMA_or_AMMA = $.grep(self.get_edges_map(),
		function(edge) {
			if ((edge['type'] == "AMA") || (edge['type'] == "AMMA")) {
				return true;
			}
			else {
				return false;
			}
	});
	
	params['arrows'] = [];
	$.each(arrows_AMA_or_AMMA, function(i, arrow) {
		var a = {};
		a['graph_id'] = arrow['graph_id'];
		a['to_module'] = arrow['to_module'];
		a['from_module'] = arrow['from_module'];
		params['arrows'].push(a);
	});
	
	jQuery.ajax ({
		data: params,
		dataType: "JSON",
		type: "POST",
		url: "ajax.php",
		success: function (data) {
			$.each(self.get_edges_map(), function(i, edge) {
				if (typeof(data[edge['graph_id']]) != "undefined") {
					self.get_edges_map()[i]['to_status'] = data[edge['graph_id']]['to_status'];
					self.get_edges_map()[i]['from_status'] = data[edge['graph_id']]['from_status'];
					
					self.update_arrow(edge['graph_id']);
				}
			});
		}
	});
}

/**
* Function cache_is_element
* Return void
* This function checks if the element is in cache
*/
NetworkmapController.prototype.cache_is_element = function(element) {
	var self = this;
	
	if (typeof(self._cache_elements[element]) == "undefined") {
		return false;
	}
	else {
		return true;
	}
}

/**
* Function getArrows
* Return array[id_arrow]
* This function returns the arrows of a node
*/
NetworkmapController.prototype.getArrows = function(id_node) {
	var self = this;
	
	var return_var = [];
	
	self.get_edges_map().forEach(function(edge, index) {
		if (("node_" + edge['to']['graph_id']) == id_node
			|| ("node_" + edge['from']['graph_id']) == id_node) {
			return_var[index] = edge["graph_id"];
		}
	});
	
	return return_var;
}

/**
* Function get_menu_nodes
* Return menu
* This function returns the node menus
*/
NetworkmapController.prototype.get_menu_nodes = function() {
	var self = this;
	
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
				self.editNode(elm);
			}
		},
		{
			title: 'Relationship with other',
			action: function(elm, d, i) {
				self._last_event = null;
				self._relationship_in_progress_type = "children";
				self.set_as_children();
			}
		},
		{
			title: 'Delete',
			action: function(elm, d, i) {
				self.deleteNode(self, elm);
			}
		}
	];
	
	return node_menu;
}

/**
* Function resize_node_save
* Return menu
* Declaration
*/
NetworkmapController.prototype.resize_node_save = function(graph_id) {
	
}

/**
* Function move_node
* Return menu
* Declaration
*/
NetworkmapController.prototype.move_node = function(node) {
}

/**
* Function apply_temp_arrows
* Return menu
* Calls father functions
*/
NetworkmapController.prototype.apply_temp_arrows = function(target_id) {
	MapController.prototype.apply_temp_arrows.call(this, target_id);
}

/**
* Function deleteNode
* Return menu
* Calls father functions
*/
NetworkmapController.prototype.deleteNode = function(self, target) {
	MapController.prototype.deleteNode.call(this, self, target);
}

/**
* Function editNode
* Return void
* This function prints the node edition table
*/
NetworkmapController.prototype.editNode = function(target) {
	var self = this;
	
	var nodeTarget = $(target);
	
	var type = parseInt(nodeTarget.data("type"));
	var data_id = parseInt(nodeTarget.data("id"));
	var data_graph_id = parseInt(nodeTarget.data("graph_id"));
	var node_id = nodeTarget.attr("id");
	
	var params = {};
	params["printEditNodeTable"] = 1;
	params["id_node_data"] = data_id;
	params["type"] = type;
	params["data_graph_id"] = data_graph_id;
	params["node_id"] = node_id;
	params["page"] = "include/ajax/map.ajax";
	
	jQuery.ajax ({
		data: params,
		dataType: "html",
		type: "POST",
		url: "ajax.php",
		success: function (data) {
			$(target).append("<div id='edit_node_dialog_" + node_id + "' style='display: none;'></div>");
			$("#edit_node_dialog_" + node_id).append(data);
			$("#edit_node_dialog_" + node_id).dialog({ 
				autoOpen: false,
				closeOnEscape: true
			});
			$("#edit_node_dialog_" + node_id).dialog("open");
			
			$(".edit_node input").on("click", function () {
				self.apply_edit_node(node_id);
			});
			
			forced_title_callback();
		}
	});
}

/**
* Function apply_edit_node
* Return menu
* This function aplies the new data to the node
*/
NetworkmapController.prototype.apply_edit_node = function(data_graph_id) {
	var self = this;
	
	var node_id = data_graph_id;
	
	var new_label = $("#edit_node_dialog_" + node_id + " input[id='text-label']").val();
	var new_shape = $("#edit_node_dialog_" + node_id + " select[id='shape']").val();
	
	var id_graph = node_id.replace(/node_/, "");
	
	var node = self.get_node(id_graph);
	
	node['title'] = truncate_title(new_label);
	node['shape'] = new_shape;
	
	self.get_nodes_map()[node['index_node']] = node;
	
	d3.selectAll(self._target + " #" + node_id + " *")
		.remove();
	
	self.paint_node(d3.select(self._target + " #" + node_id).node(), node);
	
	self.move_arrow(id_graph);
	
	$("#edit_node_dialog_" + node_id).dialog("close");
	
	// Enterprise save the data into the database
	if (typeof(self.apply_edit_node_save) != "undefined") {
		self.apply_edit_node_save(id_graph, new_label, new_shape);
	}
}

// ---------------------------------------------------------------------
// --- Functions -------------------------------------------------------
// ---------------------------------------------------------------------
function truncate_title(text) {
	
	if (text.length > GENERIC_SIZE_TEXT) {
		var half_length = parseInt((GENERIC_SIZE_TEXT - 3) / 2);
		var return_var;
		
		var truncate_text = text.substring(0, half_length);
		var truncate_text2 = text.substring(text.length - half_length);
		
		return_var = truncate_text + "..." + truncate_text2;
		
		return return_var;
	}
	else {
		return text;
	}
}
// ---------------------------------------------------------------------
