// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.


// https://github.com/fzaninotto/DependencyWheel
function chordDiagram (recipient, elements, matrix, unit, width) {

	d3.chart = d3.chart || {};
	d3.chart.chordWheel = function(options) {
		
		// Default values
		var width = 700;
		var margin = 150;
		var padding = 0.02;

		function chart(selection) {
			selection.each(function(data) {

				var matrix = data.matrix;
				var elements = data.elements;
				var radius = width / 2 - margin;

				// create the layout
				var chord = d3.layout.chord()
					.padding(padding)
					.sortSubgroups(d3.descending);

				// Select the svg element, if it exists.
				var svg = d3.select(this).selectAll("svg").data([data]);

				// Otherwise, create the skeletal chart.
				var gEnter = svg.enter().append("svg:svg")
					.attr("width", width)
					.attr("height", width)
					.attr("class", "dependencyWheel")
					.append("g")
					.attr("transform", "translate(" + (width / 2) + "," + (width / 2) + ")");

				var arc = d3.svg.arc()
					.innerRadius(radius)
					.outerRadius(radius + 20);

				var fill = function(d) {
					if (d.index === 0) return '#ccc';
					return "hsl(" + parseInt(((elements[d.index][0].charCodeAt() - 97) / 26) * 360, 10) + ",90%,70%)";
				};

				// Returns an event handler for fading a given chord group.
				var fade = function(opacity) {
					return function(g, i) {
						svg.selectAll(".chord")
								.filter(function(d) {
									return d.source.index != i && d.target.index != i;
								})
							.transition()
								.style("opacity", opacity);
						var groups = [];
						svg.selectAll(".chord")
								.filter(function(d) {
									if (d.source.index == i) {
										groups.push(d.target.index);
									}
									if (d.target.index == i) {
										groups.push(d.source.index);
									}
								});
						groups.push(i);
						var length = groups.length;
						svg.selectAll('.group')
								.filter(function(d) {
									for (var i = 0; i < length; i++) {
										if(groups[i] == d.index) return false;
									}
									return true;
								})
								.transition()
								.style("opacity", opacity);
					};
				};

				chord.matrix(matrix);

				var rootGroup = chord.groups()[0];
				var rotation = - (rootGroup.endAngle - rootGroup.startAngle) / 2 * (180 / Math.PI);

				var g = gEnter.selectAll("g.group")
					.data(chord.groups)
					.enter().append("svg:g")
					.attr("class", "group")
					.attr("transform", function(d) {
						return "rotate(" + rotation + ")";
					});

				g.append("svg:path")
					.style("fill", fill)
					.style("stroke", fill)
					.attr("d", arc)
					.on("mouseover", fade(0.1))
					.on("mouseout", fade(1));

				g.append("svg:text")
					.each(function(d) { d.angle = (d.startAngle + d.endAngle) / 2; })
					.attr("dy", ".35em")
					.attr("text-anchor", function(d) { return d.angle > Math.PI ? "end" : null; })
					.attr("transform", function(d) {
						return "rotate(" + (d.angle * 180 / Math.PI - 90) + ")" +
							"translate(" + (radius + 26) + ")" +
							(d.angle > Math.PI ? "rotate(180)" : "");
					})
					.text(function(d) { return elements[d.index]; });

				gEnter.selectAll("path.chord")
					.data(chord.chords)
					.enter().append("svg:path")
					.attr("class", "chord")
					.style("stroke", function(d) { return d3.rgb(fill(d.source)).darker(); })
					.style("fill", function(d) { return fill(d.source); })
					.attr("d", d3.svg.chord().radius(radius))
					.attr("transform", function(d) {
						return "rotate(" + rotation + ")";
					})
					.style("opacity", 1);

				// Add an elaborate mouseover title for each chord.
				gEnter.selectAll("path.chord").append("title").text(function(d) {
					return elements[d.source.index]
						+ " → " + elements[d.target.index]
						+ ": " + d.source.value + " " + unit
						+ "\n" + elements[d.target.index]
						+ " → " + elements[d.source.index]
						+ ": " + d.target.value + " " + unit;
				});
			});
		}

		chart.width = function(value) {
			if (!arguments.length) return width;
			width = value;
			return chart;
		};

		chart.margin = function(value) {
			if (!arguments.length) return margin;
			margin = value;
			return chart;
		};

		chart.padding = function(value) {
			if (!arguments.length) return padding;
			padding = value;
			return chart;
		};

		return chart;
	};

	var chart = d3.chart.chordWheel()
				.width(width)
				.margin(150)
				.padding(.02);

	d3.select(recipient)
		.datum({
			elements: elements,
			matrix: matrix
		})
		.call(chart);
}