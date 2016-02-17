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
	this._dialogNodeMargin = 10;
	this._marginConstant = 50;
}

// Atributes
MapController.prototype._id = null;
MapController.prototype._dialogNodeMargin = 0; //To be beauty
MapController.prototype._marginConstant = 0; //To be beauty


// Methods
MapController.prototype.init_map = function() {
	var svg = d3.select("#map svg");
	
	
	
	svg.append("g").append("circle")
		.attr("id", "node_10")
		.attr("class", "node")
		.attr("cx", "20")
		.attr("cy", "20")
		.attr("style", "fill: rgb(128, 186, 39);")
		.attr("r", "5");
	
	svg.append("g").append("circle")
		.attr("id", "node_11")
		.attr("class", "node")
		.attr("cx", "20")
		.attr("cy", "780")
		.attr("style", "fill: rgb(255, 0, 0);")
		.attr("r", "10");
	
	svg.append("g").append("circle")
		.attr("id", "node_12")
		.attr("class", "node")
		.attr("cx", "780")
		.attr("cy", "780")
		.attr("style", "fill: rgb(255, 255, 0);")
		.attr("r", "10");
	
	svg.append("g").append("circle")
		.attr("id", "node_13")
		.attr("class", "node")
		.attr("cx", "780")
		.attr("cy", "30")
		.attr("style", "fill: rgb(255, 0, 255);")
		.attr("r", "10");
	
	svg.append("g").append("circle")
		.attr("id", "node_14")
		.attr("class", "node")
		.attr("cx", "50")
		.attr("cy", "50")
		.attr("style", "fill: rgb(112, 51, 51);")
		.attr("r", "7");

	svg.append("g").append("circle")
		.attr("id", "node_15")
		.attr("class", "node")
		.attr("cx", "600")
		.attr("cy", "600")
		.attr("style", "fill: rgb(98, 149, 54);")
		.attr("r", "8");

	svg.append("g").append("circle")
		.attr("id", "node_16")
		.attr("class", "node")
		.attr("cx", "490")
		.attr("cy", "490")
		.attr("style", "fill: rgb(250, 103, 18);")
		.attr("r", "6");

	svg.append("g").append("circle")
		.attr("id", "node_17")
		.attr("class", "node")
		.attr("cx", "400")
		.attr("cy", "600")
		.attr("style", "fill: rgb(50, 50, 128);")
		.attr("r", "6");

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
				self.popup_map(self, event);
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

MapController.prototype.popup_map = function(self, event) {
	// Node position and radius
	nodeX = parseInt($(event.currentTarget).attr("cx"));
	nodeY = parseInt($(event.currentTarget).attr("cy"));
	nodeR = parseInt($(event.currentTarget).attr("r"));

	// Map dimensions in pixels
	var map_width_with_px = $(self._target + " svg").attr("width");
	var map_height_with_px = $(self._target + " svg").attr("height");

	// Map dimensions in numbers
	var map_width = parseInt(map_width_with_px.slice(0, map_width_with_px.length - 2));
	var map_height = parseInt(map_height_with_px.slice(0, map_height_with_px.length - 2));

	// Dialog dimensions
	var dialog_width = 300;
	var dialog_height = 200;

	//To know the position of the dialog box
	if (self.xOffset(map_width, nodeX, dialog_width) && self.yOffset(map_height, nodeY, dialog_height)) {
		var dialogClass = "nodeDialogBottom";
		var xPos = event.pageX - dialog_width + nodeR;
		var yPos = event.clientY - dialog_height - nodeR - self._dialogNodeMargin;
	}
	else if (self.yOffset(map_height, nodeY, dialog_height)) {
		var dialogClass = "nodeDialogBottom2";
		var xPos = event.pageX - nodeR;
		var yPos = event.clientY + -dialog_height - nodeR - self._dialogNodeMargin;
	}
	else if (self.xOffset(map_width, nodeX, dialog_width)) {
		var dialogClass = "nodeDialogTop2";
		var xPos = event.pageX - dialog_width + nodeR;
		var yPos = event.clientY + nodeR + self._dialogNodeMargin;
	}
	else {
		var dialogClass = "nodeDialogTop";
		var xPos = event.pageX - nodeR;
		var yPos = event.clientY + nodeR + self._dialogNodeMargin;
	}

	$(self._target + " svg").after($("<div>").attr("id", "dialog_popup"));
	$("#dialog_popup").dialog({
		dialogClass: dialogClass,
	  	modal: false,
		closeOnEscape: true,
		show: {effect: 'fade', speed: 1000},
		title: "Node dialog",
		resizable: false,
		position: [xPos,yPos],
		height: dialog_height,
		width: dialog_width
	});

}

/*
Function xOffset
Return boolean
This function returns true if dialog cuts map's x axis
*/
MapController.prototype.xOffset = function(map_w, node_x, dialog_w) {
	if ((map_w - node_x - this._marginConstant) < dialog_w) {
		return true;
	}
	return false;
}

/*
Function yOffset
Return boolean
This function returns true if dialog cuts map's y axis
*/
MapController.prototype.yOffset = function(map_h, node_y, dialog_h) {
	if ((map_h - node_y - this._marginConstant) < dialog_h) {
		return true;
	}
	return false;
}
