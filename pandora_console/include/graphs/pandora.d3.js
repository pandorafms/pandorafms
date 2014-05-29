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


// The recipient is the selector of the html element
// The elements is an array with the names of the wheel elements
// The matrix must be a 2 dimensional array with a row and a column for each element
// Ex:
// elements = ["a", "b", "c"];
// matrix = [[0, 0, 2],		// a[a => a, a => b, a => c]
//			 [5, 0, 1],		// b[b => a, b => b, b => c]
// 			 [2, 3, 0]];	// c[c => a, c => b, c => c]
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
					return "hsl(" + parseInt((d.index / 26) * 360, 10) + ",80%,70%)";
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
				gEnter.selectAll("path.chord")
					.on("mouseover", over_user)
					.on("mouseout", out_user)
					.on("mousemove", move_tooltip);

				function move_tooltip(d) {
					x = d3.event.pageX + 10;
					y = d3.event.pageY + 10;
					
					$("#tooltip").css('left', x + 'px');
					$("#tooltip").css('top', y + 'px');
				}
				
				function over_user(d) {
					id = d.id;
					
					$("#" + id).css('border', '1px solid black');
					$("#" + id).css('z-index', '1');
					
					show_tooltip(d);
				}
				
				function out_user(d) {
					id = d.id;
					
					$("#" + id).css('border', '');
					$("#" + id).css('z-index', '');
					
					hide_tooltip();
				}

				function create_tooltip(d, x, y) {
					if ($("#tooltip").length == 0) {
						$(recipient)
							.append($("<div></div>")
							.attr('id', 'tooltip')
							.html(
								elements[d.source.index]
								+ " → "
								+ elements[d.target.index]
								+ ": <b>" + d.source.value.toFixed(2) + " " + unit + "</b>"
								+ "<br>"
								+ elements[d.target.index]
								+ " → "
								+ elements[d.source.index]
								+ ": <b>" + d.target.value.toFixed(2) + " " + unit + "</b>"
							));
					}
					else {
						$("#tooltip").html(
							elements[d.source.index]
							+ " → "
							+ elements[d.target.index]
							+ ": <b>" + d.source.value.toFixed(2) + " " + unit + "</b>"
							+ "<br>"
							+ elements[d.target.index]
							+ " → "
							+ elements[d.source.index]
							+ ": <b>" + d.target.value.toFixed(2) + " " + unit + "</b>"
						);
					}
					
					$("#tooltip").attr('style', 'background: #fff;' +
						'position: absolute;' +
						'display: inline-block;' +
						'width: auto;' +
						'max-width: 500px;' +
						'text-align: left;' +
						'padding: 10px 10px 10px 10px;' +
						'z-index: 2;' +
						"-webkit-box-shadow: 7px 7px 5px rgba(50, 50, 50, 0.75);" +
						"-moz-box-shadow:    7px 7px 5px rgba(50, 50, 50, 0.75);" +
						"box-shadow:         7px 7px 5px rgba(50, 50, 50, 0.75);" +
						'left: ' + x + 'px;' +
						'top: ' + y + 'px;');
				}
				
				function show_tooltip(d) {
					x = d3.event.pageX + 10;
					y = d3.event.pageY + 10;
					
					create_tooltip(d, x, y);
				}
				
				function hide_tooltip() {
					$("#tooltip").hide();
				}
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

// The recipient is the selector of the html element
// The data must be a bunch of associative arrays like this
// data = {
// 	"name": "IP Traffic",
//	"id": 0,
// 	"children": [
// 		{
// 			"name": "192.168.1.1",
//			"id": 1,
// 			"children": [
// 				{
// 					"name": "HTTP",
//					"id": 2,
// 					"value": 33938
// 				}
// 			]
// 		},
// 		{
// 			"name": "192.168.1.2",
//			"id": 3,
// 			"children": [
// 				{
// 					"name": "HTTP",
//					"id": 4,
// 					"value": 3938
// 				},
// 				{
// 					"name": "FTP",
//					"id": 5,
// 					"value": 1312
// 				}
// 			]
// 		}
// 	]
// };
function treeMap(recipient, data, width, height) {

	//var isIE = BrowserDetect.browser == 'Explorer';
	var isIE = true;
	var chartWidth = width;
	var chartHeight = height;
	if (width === 'auto') {
		chartWidth = $(recipient).innerWidth();
	}
	if (height === 'auto') {
		chartHeight = $(recipient).innerHeight();
	}
	var xscale = d3.scale.linear().range([0, chartWidth]);
	var yscale = d3.scale.linear().range([0, chartHeight]);
	var color = d3.scale.category10();
	var headerHeight = 20;
	var headerColor = "#555555";
	var transitionDuration = 500;
	var root;
	var node;

	var treemap = d3.layout.treemap()
		.round(false)
		.size([chartWidth, chartHeight])
		.sticky(true)
		.value(function(d) {
			return d.value;
		});

	var chart = d3.select(recipient)
		.append("svg:svg")
		.attr("width", chartWidth)
		.attr("height", chartHeight)
		.append("svg:g");

	node = root = data;
	var nodes = treemap.nodes(root);

	var children = nodes.filter(function(d) {
            return !d.children;
        });
        var parents = nodes.filter(function(d) {
            return d.children;
        });

        // create parent cells
        var parentCells = chart.selectAll("g.cell.parent")
            .data(parents, function(d) {
                return "p-" + d.name;
            });
        var parentEnterTransition = parentCells.enter()
            .append("g")
            .attr("class", "cell parent")
            .on("click", function(d) {
                zoom(d);
            })
            .append("svg")
            .attr("class", "clip")
            .attr("width", function(d) {
                return Math.max(0.01, d.dx);
            })
            .attr("height", headerHeight);
        parentEnterTransition.append("rect")
            .attr("width", function(d) {
                return Math.max(0.01, d.dx);
            })
            .attr("height", headerHeight)
            .style("fill", headerColor);
        parentEnterTransition.append('text')
            .attr("class", "label")
            .attr("fill", "white")
            .attr("transform", "translate(3, 13)")
            .attr("width", function(d) {
                return Math.max(0.01, d.dx);
            })
            .attr("height", headerHeight)
            .text(function(d) {
                return d.name;
            });
        // update transition
        var parentUpdateTransition = parentCells.transition().duration(transitionDuration);
        parentUpdateTransition.select(".cell")
            .attr("transform", function(d) {
                return "translate(" + d.dx + "," + d.y + ")";
            });
        parentUpdateTransition.select("rect")
            .attr("width", function(d) {
                return Math.max(0.01, d.dx);
            })
            .attr("height", headerHeight)
            .style("fill", headerColor);
        parentUpdateTransition.select(".label")
            .attr("transform", "translate(3, 13)")
            .attr("width", function(d) {
                return Math.max(0.01, d.dx);
            })
            .attr("height", headerHeight)
            .text(function(d) {
                return d.name;
            });
        // remove transition
        parentCells.exit()
            .remove();

        // create children cells
        var childrenCells = chart.selectAll("g.cell.child")
            .data(children, function(d) {
                return "c-" + d.name;
            });
        // enter transition
        var childEnterTransition = childrenCells.enter()
            .append("g")
            .attr("class", "cell child")
            .on("click", function(d) {
                zoom(node === d.parent ? root : d.parent);
            })
            .on("mouseover", over_user)
			.on("mouseout", out_user)
			.on("mousemove", move_tooltip)
            .append("svg")
            .attr("class", "clip");
        childEnterTransition.append("rect")
            .classed("background", true)
            .style("fill", function(d) {
                return color(d.parent.name);
            });
        childEnterTransition.append('text')
            .attr("class", "label")
            .attr('x', function(d) {
                return d.dx / 2;
            })
            .attr('y', function(d) {
                return d.dy / 2;
            })
            .attr("dy", ".35em")
            .attr("text-anchor", "middle")
            .style("display", "none")
            .text(function(d) {
                return d.name;
            });
        // update transition
        var childUpdateTransition = childrenCells.transition().duration(transitionDuration);
        childUpdateTransition.select(".cell")
            .attr("transform", function(d) {
                return "translate(" + d.x + "," + d.y + ")";
            });
        childUpdateTransition.select("rect")
            .attr("width", function(d) {
                return Math.max(0.01, d.dx);
            })
            .attr("height", function(d) {
                return d.dy;
            })
            .style("fill", function(d) {
                return color(d.parent.name);
            });
        childUpdateTransition.select(".label")
            .attr('x', function(d) {
                return d.dx / 2;
            })
            .attr('y', function(d) {
                return d.dy / 2;
            })
            .attr("dy", ".35em")
            .attr("text-anchor", "middle")
            .style("display", "none")
            .text(function(d) {
                return d.name;
            });

        // exit transition
        childrenCells.exit()
            .remove();

        d3.select("select").on("change", function() {
            console.log("select zoom(node)");
            treemap.value(this.value == "size" ? size : count)
                .nodes(root);
            zoom(node);
        });

        zoom(node);

	function size(d) {
		return d.size;
	}

	function count(d) {
		return 1;
	}

	//and another one
	function textHeight(d) {
		var ky = chartHeight / d.dy;
		yscale.domain([d.y, d.y + d.dy]);
		return (ky * d.dy) / headerHeight;
	}

	function getRGBComponents (color) {
		var r = color.substring(1, 3);
		var g = color.substring(3, 5);
		var b = color.substring(5, 7);
		return {
			R: parseInt(r, 16),
			G: parseInt(g, 16),
			B: parseInt(b, 16)
		};
	}

	function idealTextColor (bgColor) {
		var nThreshold = 105;
		var components = getRGBComponents(bgColor);
		var bgDelta = (components.R * 0.299) + (components.G * 0.587) + (components.B * 0.114);
		return ((255 - bgDelta) < nThreshold) ? "#000000" : "#ffffff";
	}
function zoom(d) {
        treemap
            .padding([headerHeight / (chartHeight / d.dy), 0, 0, 0])
            .nodes(d);

        // moving the next two lines above treemap layout messes up padding of zoom result
        var kx = chartWidth / d.dx;
        var ky = chartHeight / d.dy;
        var level = d;

        xscale.domain([d.x, d.x + d.dx]);
        yscale.domain([d.y, d.y + d.dy]);

        if (node != level) {
            chart.selectAll(".cell.child .label")
                .style("display", "none");
        }

        var zoomTransition = chart.selectAll("g.cell").transition().duration(transitionDuration)
            .attr("transform", function(d) {
                return "translate(" + xscale(d.x) + "," + yscale(d.y) + ")";
            })
            .each("start", function() {
                d3.select(this).select("label")
                    .style("display", "none");
            })
            .each("end", function(d, i) {
                if (!i && (level !== self.root)) {
                    chart.selectAll(".cell.child")
                        .filter(function(d) {
                            return d.parent === self.node; // only get the children for selected group
                        })
                        .select(".label")
                        .style("display", "")
                        .style("fill", function(d) {
                            return idealTextColor(color(d.parent.name));
                        });
                }
            });

        zoomTransition.select(".clip")
            .attr("width", function(d) {
                return Math.max(0.01, (kx * d.dx));
            })
            .attr("height", function(d) {
                return d.children ? headerHeight : Math.max(0.01, (ky * d.dy));
            });

        zoomTransition.select(".label")
            .attr("width", function(d) {
                return Math.max(0.01, (kx * d.dx));
            })
            .attr("height", function(d) {
                return d.children ? headerHeight : Math.max(0.01, (ky * d.dy));
            })
            .text(function(d) {
                return d.name;
            });

        zoomTransition.select(".child .label")
            .attr("x", function(d) {
                return kx * d.dx / 2;
            })
            .attr("y", function(d) {
                return ky * d.dy / 2;
            });

        zoomTransition.select("rect")
            .attr("width", function(d) {
                return Math.max(0.01, (kx * d.dx));
            })
            .attr("height", function(d) {
                return d.children ? headerHeight : Math.max(0.01, (ky * d.dy));
            })
            .style("fill", function(d) {
                return d.children ? headerColor : color(d.parent.name);
            });

        node = d;

        if (d3.event) {
            d3.event.stopPropagation();
        }
    }


	function position() {
		this.style("left", function(d) { return d.x + "px"; })
			.style("top", function(d) { return d.y + "px"; })
			.style("width", function(d) { return Math.max(0, d.dx - 1) + "px"; })
			.style("height", function(d) { return Math.max(0, d.dy - 1) + "px"; });
	}
	
	function move_tooltip(d) {
		x = d3.event.pageX + 10;
		y = d3.event.pageY + 10;
		
		$("#tooltip").css('left', x + 'px');
		$("#tooltip").css('top', y + 'px');
	}
	
	function over_user(d) {
		id = d.id;
		
		$("#" + id).css('border', '1px solid black');
		$("#" + id).css('z-index', '1');
		
		show_tooltip(d);
	}
	
	function out_user(d) {
		id = d.id;
		
		$("#" + id).css('border', '');
		$("#" + id).css('z-index', '');
		
		hide_tooltip();
	}

	function create_tooltip(d, x, y) {
		if ($("#tooltip").length == 0) {
			$(recipient)
				.append($("<div></div>")
				.attr('id', 'tooltip')
				.html(d.tooltip_content));
		}
		else {
			$("#tooltip").html(d.tooltip_content);
		}
		
		$("#tooltip").attr('style', 'background: #fff;' +
			'position: absolute;' +
			'display: block;' +
			'width: 200px;' +
			'text-align: left;' +
			'padding: 10px 10px 10px 10px;' +
			'z-index: 2;' +
			"-webkit-box-shadow: 7px 7px 5px rgba(50, 50, 50, 0.75);" +
			"-moz-box-shadow:    7px 7px 5px rgba(50, 50, 50, 0.75);" +
			"box-shadow:         7px 7px 5px rgba(50, 50, 50, 0.75);" +
			'left: ' + x + 'px;' +
			'top: ' + y + 'px;');
	}
	
	function show_tooltip(d) {
		x = d3.event.pageX + 10;
		y = d3.event.pageY + 10;
		
		create_tooltip(d, x, y);
	}
	
	function hide_tooltip() {
		$("#tooltip").hide();
	}
}