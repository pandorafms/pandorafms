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

// Constructor
var MapController = function(target) {
	this._target = target;
	
	this._id = $(target).data('id');
}

// Atributes
MapController.prototype._id = null;


// Methods
MapController.prototype.init_map = function() {
	var svg = d3.select("#map svg");
	
	svg.append("g").append("circle")
		.attr("id", "node_10")
		.attr("class", "node")
		.attr("cx", "100")
		.attr("cy", "100")
		.attr("style", "fill: rgb(128, 186, 39);")
		.attr("r", "5");
	
	svg.append("g").append("circle")
		.attr("id", "node_11")
		.attr("class", "node")
		.attr("cx", "200")
		.attr("cy", "200")
		.attr("style", "fill: rgb(255, 0, 0);")
		.attr("r", "10");
	
	this.init_events();
};

MapController.prototype.init_events = function(principalObject) {
	$(this._target + " svg *, " + this._target + " svg").on("mousedown", {map: this}, this.click_event);
}

MapController.prototype.click_event = function(event) {
	var self = event.data.map;
	event.preventDefault();
	event.stopPropagation();
	switch (event.which) {
        case 1:
			if ($(event.currentTarget).attr("class") == "node") {
				self.popup_map();
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

MapController.prototype.popup_map = function() {
	//POP-UP
}
