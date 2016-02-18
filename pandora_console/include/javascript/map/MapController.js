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

/*-------------------Constructor-------------------*/

var MapController = function(target) {
	this._target = target;
	
	this._id = $(target).data('id');
	this._dialogNodeMargin = 10;
	this._marginConstant = 50;
}

/*--------------------Atributes--------------------*/

MapController.prototype._id = null;
MapController.prototype._dialogNodeMargin = 0; //To be beauty
MapController.prototype._marginConstant = 0; //To be beauty


/*--------------------Methods----------------------*/
var svg;
function zoom() {
  svg.attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
}

/*
Function init_map
Return void
This function init the map
*/
MapController.prototype.init_map = function() {
	svg = d3.select("#map svg");
	
	svg
		.call(d3.behavior.zoom().scaleExtent([1/100, 100]).on("zoom", zoom))
		.append("g");
	
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



	//Runs tooltipster plugin
	$(document).ready(function() {
            $('.tooltip').tooltipster();
    });

	this.init_events();
};

/*
Function init_events
Return boolean
This function init click events in the map
*/
MapController.prototype.init_events = function(principalObject) {
	$(this._target + " svg *, " + this._target + " svg").on("mousedown", {map: this}, this.click_event);
}

/*
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
			if ($(event.currentTarget).attr("class") == "node") {
				self.tooltip_map(self, event);
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

/*
Function popup_map
Return void
This function manages nodes tooltips
*/
MapController.prototype.tooltip_map = function(self, event) {
	/*------------------- PRUEBA CON TOOLTIPSTER ----------------------*/
	$(this._target + " svg").after($("<div>").attr("id", "tooltipster_node"));

	$('#tooltipster_node').tooltipster({
		arrow: true,
		autoClose: false,
		multiple: true,
		content: 'I\'M A FUCKING TOOLTIP!!'
	});

	$('#tooltipster_node').tooltipster("show");
	/*-----------------------------------------------------------------*/
}
