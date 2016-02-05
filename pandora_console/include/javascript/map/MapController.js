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

var MapController = function(target) {
	this._target = target;
}

MapController.prototype.init_map = function() {
	$("#" + this._target + " svg").append(
		$("<g>").append(
			$("<circle>")
				.attr("id", "node_10")
				.attr("class", "node")
				.attr("cx", "100")
				.attr("cy", "100")
				.attr("style", "fill: rgb(128, 186, 39);")
				.attr("r", "5")
		)
	);
};

MapController.prototype.test333 = function(aaa) {
	console.log(aaa);
}