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
// matrix = [[0, 0, 2],     // a[a => a, a => b, a => c]
//           [5, 0, 1],     // b[b => a, b => b, b => c]
//           [2, 3, 0]];    // c[c => a, c => b, c => c]
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
//  "name": "IP Traffic",
//  "id": 0,
//  "children": [
//      {
//          "name": "192.168.1.1",
//          "id": 1,
//          "children": [
//              {
//                  "name": "HTTP",
//                  "id": 2,
//                  "value": 33938
//              }
//          ]
//      },
//      {
//          "name": "192.168.1.2",
//          "id": 3,
//          "children": [
//              {
//                  "name": "HTTP",
//                  "id": 4,
//                  "value": 3938
//              },
//              {
//                  "name": "FTP",
//                  "id": 5,
//                  "value": 1312
//              }
//          ]
//      }
//  ]
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
			return d.id;
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
			return d.id;
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
			return color(d.name);
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
						.style("color", function(d) {
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


// A sunburst is similar to a treemap, except it uses a radial layout.
// The root node of the tree is at the center, with leaves on the circumference.
// The area (or angle, depending on implementation) of each arc corresponds to its value.
// Sunburst design by John Stasko. Data courtesy Jeff Heer.
// http://bl.ocks.org/mbostock/4348373
function sunburst (recipient, data, width, height) {

	if (width === 'auto') {
		width = $(recipient).innerWidth();
	}
	if (height === 'auto') {
		height = width;
	}
	// var width = 960,
	// 	height = 700;
	var radius = Math.min(width, height) / 2;

	var x = d3.scale.linear()
		.range([0, 2 * Math.PI]);

	var y = d3.scale.sqrt()
		.range([0, radius]);

	var color = d3.scale.category20c();

	var svg = d3.select(recipient).append("svg")
		.attr("width", width)
		.attr("height", height);

	var partition = d3.layout.partition()
		.value(function(d) { return d.size; });

	var arc = d3.svg.arc()
		.startAngle(function(d) { return Math.max(0, Math.min(2 * Math.PI, x(d.x))); })
		.endAngle(function(d) { return Math.max(0, Math.min(2 * Math.PI, x(d.x + d.dx))); })
		.innerRadius(function(d) { return Math.max(0, y(d.y)); })
		.outerRadius(function(d) { return Math.max(0, y(d.y + d.dy)); });

	var g = svg.selectAll("g")
		.data(partition.nodes(data))
		.enter().append("g")
		.attr("transform", "translate(" + width / 2 + "," + (height / 2 + 10) + ")");

	var path = g.append("path")
		.attr("d", arc)
		.style("fill", function(d) { return d.color ? d3.rgb(d.color) : color((d.children ? d : d.parent).name); })
		.style("cursor", "pointer")
		.on("click", click)
		.on("mouseover", over_user)
		.on("mouseout", out_user)
		.on("mousemove", move_tooltip);

	function computeTextRotation(d) {
		var angle = x(d.x + d.dx / 2) - Math.PI / 2;
		return angle / Math.PI * 180;
	}

	var text = g.append("text")
		.attr("x", function(d) { return y(d.y); })
		.attr("dx", "6") // margin
		.attr("dy", ".35em") // vertical-align
		.attr("opacity", function(d) {
			if (typeof d.show_name != "undefined" && d.show_name)
				return 1;
			else
				return 0;
		})
		.text(function(d) {
			return d.name;
		})
		.attr("transform", function(d) { return "rotate(" + computeTextRotation(d) + ")"; })
		.style("font-size", "10px")
		 // Makes svg elements invisible to events
		.style("pointer-events", "none");

	function click(d) {
		if (typeof d.link != "undefined") {
			window.location.href = d.link;
		}
		else {
			// fade out all text elements
			text.transition().attr("opacity", 0);

			path.transition()
				.duration(750)
				.attrTween("d", arcTween(d))
				.each("end", function(e, i) {
					// check if the animated element's data e lies within the visible angle span given in d
					if ((typeof e.type != 'undefined'
							&& (e.type == "group"
								|| ( e.type == "agent" && (d.type == "group" || d.type == "agent" || d.type == "module_group" || d.type == "module") )
								|| ( (e.type == "module_group" || e.type == "module") && (d.type == "agent" || d.type == "module_group") ) ))
							&& e.x >= d.x && e.x < (d.x + d.dx)) {
						// get a selection of the associated text element
						var arcText = d3.select(this.parentNode).select("text");
						// fade in the text element and recalculate positions
						arcText
							.attr("transform", function() { return "rotate(" + computeTextRotation(e) + ")" })
							.attr("x", function(d) { return y(d.y); })
							.transition().duration(250)
								.attr("opacity", 1);
		 			}
				});
		}
	}

	d3.select(self.frameElement).style("height", height + "px");

	// Interpolate the scales!
	function arcTween(d) {
		var xd = d3.interpolate(x.domain(), [d.x, d.x + d.dx]),
			yd = d3.interpolate(y.domain(), [d.y, 1]),
			yr = d3.interpolate(y.range(), [d.y ? 20 : 0, radius]);
		return function(d, i) {
			return i
				? function(t) { return arc(d); }
				: function(t) { x.domain(xd(t)); y.domain(yd(t)).range(yr(t)); return arc(d); };
		};
	}

	function move_tooltip(d) {
		var x = d3.event.pageX + 10;
		var y = d3.event.pageY + 10;
		
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
		var tooltip = (typeof d.tooltip_content != 'undefined') ? d.tooltip_content : d.name;

		if ($("#tooltip").length == 0) {
			$(recipient)
				.append($("<div></div>")
				.attr('id', 'tooltip')
				.html(tooltip));
		}
		else {
			$("#tooltip").html(tooltip);
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
		var x = d3.event.pageX + 10;
		var y = d3.event.pageY + 10;
		
		create_tooltip(d, x, y);
	}
	
	function hide_tooltip() {
		$("#tooltip").hide();
	}
}

function createGauge(name, etiqueta, value, min, max, min_warning,max_warning,min_critical,max_critical,font_size, height)
{
	var gauges;
	
	var config = 
	{
		size: height,
		label: etiqueta,
		min: undefined != min ? min : 0,
		max: undefined != max ? max : 100,
		font_size: font_size
	}
	
	if (value == -1200) {
		config.majorTicks = 1;
		config.minorTicks = 1;
		value = false;
	}
	else {
		config.minorTicks = 10;
	}
	
	//var range = config.max - config.min;
	var range = config.max - config.min;
	if (value != false) {
		if ( min_warning > 0 ) {
			config.yellowZones = [{ from: min_warning, to: max_warning }];
		}
		if ( min_critical > 0 ) {
			config.redZones = [{ from: min_critical, to: max_critical }];
		}
	}
	gauges = new Gauge(name, config, font);
	gauges.render();
	gauges.redraw(value);
	$(".gauge>text").each(function() {	
		label = $(this).text();
		
		if (!isNaN(label)){
			label = parseFloat(label);
			text = label.toLocaleString();
			if ( label >= 1000000)
					text = text.substring(0,3) + "M";
			else if (label >= 100000)
					text = text.substring(0,3) + "K";
				else if (label >= 1000)
					text = text.substring(0,2) + "K";
				
			$(this).text(text);
		}
	});
	$(".pointerContainer>text").each(function() {	
		label = $(this).text();
		
		if (!isNaN(label)){
			label = parseFloat(label);
			text = label.toLocaleString();
			if ( label >= 10000000)
				text = text.substring(0,4) + "M";
			else if ( label >= 1000000)
						text = text.substring(0,3) + "M";
				else if (label >= 100000)
						text = text.substring(0,3) + "K";
					else if (label >= 1000)
						text = text.substring(0,2) + "K";
			$(this).text(text);
		}
	});
	config = false;
}

function createGauges(data, width, height, font_size, no_data_image, font)
{
	var nombre,label,minimun_warning,maximun_warning,minimun_critical,maximun_critical,
		mininum,maxinum,valor;
	
	for (key in data) {
		nombre = data[key].gauge;
		
		label = data[key].label;
		
		label = label.replace(/&#x20;/g,' ');
		label = label.replace(/\(/g,'\(');
		label = label.replace(/\)/g,'\)');
		
		label = label.replace(/&#40;/g,'\(');
		label = label.replace(/&#41;/g,'\)');
		
		minimun_warning 	= Math.round(parseFloat( data[key].min_warning ),2);
		maximun_warning 	= Math.round(parseFloat( data[key].max_warning ),2);
		minimun_critical	= Math.round(parseFloat( data[key].min_critical ),2);
		maximun_critical 	= Math.round(parseFloat( data[key].max_critical ),2);
		mininum = Math.round(parseFloat(data[key].min),2);
		maxinum = Math.round(parseFloat(data[key].max),2);
		valor = Math.round(parseFloat(data[key].value),2);
		if (maxinum == 0)
			maxinum = 100;
		if (mininum == 0.00)
			mininum = 0;
		if (mininum == maxinum)
			mininum = 0;
		
		if (maximun_critical == 0 )
			maximun_critical = maxinum;
		if (maximun_warning == 0 )
			maximun_warning = minimun_critical;
		
		if ( maxinum <= minimun_warning ) {
			minimun_warning = 0;
			maximun_warning = 0;
			minimun_critical = 0;
			maximun_critical = 0;
		}
		if ( maxinum < minimun_critical ) {
			minimun_critical = 0;
			maximun_critical = 0;
		}
		if ( mininum > minimun_warning ) {
			minimun_warning = mininum;
		}
		
		if (isNaN(valor)) 
			valor = (-1200);
		createGauge(nombre, label, valor, mininum, maxinum, 
				minimun_warning, maximun_warning, minimun_critical,
					maximun_critical, font_size, height, font);


	}
	
}


function Gauge(placeholderName, configuration, font)
{

	var font = font.split("/").pop().split(".").shift();
	this.placeholderName = placeholderName;
	
	var self = this; // for internal d3 functions
	
	this.configure = function(configuration)
	{
		this.config = configuration;
		
		this.config.size = this.config.size * 0.9;
		this.config.font_size = this.config.font_size;
		
		this.config.raduis = this.config.size * 0.97 / 2;
		this.config.cx = this.config.size / 2;
		this.config.cy = this.config.size / 2;
		
		this.config.min = undefined != configuration.min ? configuration.min : 0; 
		this.config.max = undefined != configuration.max ? configuration.max : 100; 
		this.config.range = this.config.max - this.config.min;
		
		this.config.majorTicks = configuration.majorTicks || 5;
		this.config.minorTicks = configuration.minorTicks || 2;
		
		this.config.greenColor 	= configuration.greenColor || "#109618";
		this.config.yellowColor = configuration.yellowColor || "#FF9900";
		this.config.redColor 	= configuration.redColor || "#DC3912";
		
		this.config.transitionDuration = configuration.transitionDuration || 500;
	}

	this.render = function()
	{
		this.body = d3.select("#" + this.placeholderName)
							.append("svg:svg")
							.attr("class", "gauge")
							.attr("width", this.config.size)
							.attr("height", this.config.size);
		
		this.body.append("svg:circle")
					.attr("cx", this.config.cx)
					.attr("cy", this.config.cy)
					.attr("r", this.config.raduis)
					.style("fill", "#ccc")
					.style("stroke", "#000")
					.style("stroke-width", "0.5px");
					
		this.body.append("svg:circle")
					.attr("cx", this.config.cx)
					.attr("cy", this.config.cy)
					.attr("r", 0.9 * this.config.raduis)
					.style("fill", "#fff")
					.style("stroke", "#e0e0e0")
					.style("stroke-width", "2px");
					
		for (var index in this.config.greenZones)
		{
			this.drawBand(this.config.greenZones[index].from, this.config.greenZones[index].to, self.config.greenColor);
		}
		
		for (var index in this.config.yellowZones)
		{
			this.drawBand(this.config.yellowZones[index].from, this.config.yellowZones[index].to, self.config.yellowColor);
		}
		
		for (var index in this.config.redZones)
		{
			this.drawBand(this.config.redZones[index].from, this.config.redZones[index].to, self.config.redColor);
		}
		
		if (undefined != this.config.label)
		{
			var fontSize = Math.round(this.config.size / 9);
			this.body.append("svg:text")
						.attr("x", this.config.cx)
						.attr("y", this.config.cy / 2 + fontSize / 2)
						.attr("dy", fontSize / 2)
						.attr("text-anchor", "middle")
						.attr("class", font)
						.text(this.config.label)
						.style("font-size", this.config.font_size+"pt")
						.style("fill", "#333")
						.style("stroke-width", "0px");
		}
		
		var fontSize = Math.round(this.config.size / 16);
		var majorDelta = this.config.range / (this.config.majorTicks - 1);
		for (var major = this.config.min; major <= this.config.max; major += majorDelta)
		{
			var minorDelta = majorDelta / this.config.minorTicks;
			for (var minor = major + minorDelta; minor < Math.min(major + majorDelta, this.config.max); minor += minorDelta)
			{
				var point1 = this.valueToPoint(minor, 0.75);
				var point2 = this.valueToPoint(minor, 0.85);
				
				this.body.append("svg:line")
							.attr("x1", point1.x)
							.attr("y1", point1.y)
							.attr("x2", point2.x)
							.attr("y2", point2.y)
							.style("stroke", "#666")
							.style("stroke-width", "1px");
			}
			
			var point1 = this.valueToPoint(major, 0.7);
			var point2 = this.valueToPoint(major, 0.85);
			
			this.body.append("svg:line")
						.attr("x1", point1.x)
						.attr("y1", point1.y)
						.attr("x2", point2.x)
						.attr("y2", point2.y)
						.style("stroke", "#333")
						.style("stroke-width", "2px");
			
			if (major == this.config.min || major == this.config.max)
			{
				var point = this.valueToPoint(major, 0.63);
				
				this.body.append("svg:text")
				 			.attr("x", point.x)
				 			.attr("y", point.y)
				 			.attr("dy", fontSize / 3)
				 			.attr("text-anchor", major == this.config.min ? "start" : "end")
				 			.text(major)
				 			.style("font-size", this.config.font_size+"pt")
							.style("fill", "#333")
							.style("stroke-width", "0px");
			}
		}
		
		var pointerContainer = this.body.append("svg:g").attr("class", "pointerContainer");
		
		var midValue = (this.config.min + this.config.max) / 2;
		
		var pointerPath = this.buildPointerPath(midValue);
		
		var pointerLine = d3.svg.line()
									.x(function(d) { return d.x })
									.y(function(d) { return d.y })
									.interpolate("basis");
		
		pointerContainer.selectAll("path")
							.data([pointerPath])
							.enter()
								.append("svg:path")
									.attr("d", pointerLine)
									.style("fill", "#dc3912")
									.style("stroke", "#c63310")
									.style("fill-opacity", 0.7)
					
		pointerContainer.append("svg:circle")
							.attr("cx", this.config.cx)
							.attr("cy", this.config.cy)
							.attr("r", 0.12 * this.config.raduis)
							.style("fill", "#4684EE")
							.style("stroke", "#666")
							.style("opacity", 1);
		
		var fontSize = Math.round(this.config.size / 10);
		pointerContainer.selectAll("text")
							.data([midValue])
							.enter()
								.append("svg:text")
									.attr("x", this.config.cx)
									.attr("y", this.config.size - this.config.cy / 4 - fontSize)
									.attr("dy", fontSize / 2)
									.attr("text-anchor", "middle")
									.style("font-size", this.config.font_size+"pt")
									.style("fill", "#000")
									.style("stroke-width", "0px");
		
		this.redraw(this.config.min, 0);
	}
	
	this.buildPointerPath = function(value)
	{
		var delta = this.config.range / 13;
		
		var head = valueToPoint(value, 0.85);
		var head1 = valueToPoint(value - delta, 0.12);
		var head2 = valueToPoint(value + delta, 0.12);
		
		var tailValue = value - (this.config.range * (1/(270/360)) / 2);
		var tail = valueToPoint(tailValue, 0.28);
		var tail1 = valueToPoint(tailValue - delta, 0.12);
		var tail2 = valueToPoint(tailValue + delta, 0.12);
		
		return [head, head1, tail2, tail, tail1, head2, head];
		
		function valueToPoint(value, factor)
		{
			var point = self.valueToPoint(value, factor);
			point.x -= self.config.cx;
			point.y -= self.config.cy;
			return point;
		}
	}
	
	this.drawBand = function(start, end, color)
	{
		if (0 >= end - start) return;
		
		this.body.append("svg:path")
					.style("fill", color)
					.attr("d", d3.svg.arc()
						.startAngle(this.valueToRadians(start))
						.endAngle(this.valueToRadians(end))
						.innerRadius(Math.round(0.65 * this.config.raduis))
						.outerRadius(Math.round(0.85 * this.config.raduis)))
					.attr("transform", function() { return "translate(" + self.config.cx + ", " + self.config.cy + ") rotate(270)" });
	}
	
	this.redraw = function(value, transitionDuration)
	{
		var pointerContainer = this.body.select(".pointerContainer");
		
		pointerContainer.selectAll("text").text(Math.round(value));
		
		var pointer = pointerContainer.selectAll("path");
		pointer.transition()
					.duration(undefined != transitionDuration ? transitionDuration : this.config.transitionDuration)
					//.delay(0)
					//.ease("linear")
					//.attr("transform", function(d) 
					.attrTween("transform", function()
					{
						var pointerValue = value;
						if (value > self.config.max) pointerValue = self.config.max + 0.02*self.config.range;
						else if (value < self.config.min) pointerValue = self.config.min - 0.02*self.config.range;
						var targetRotation = (self.valueToDegrees(pointerValue) - 90);
						var currentRotation = self._currentRotation || targetRotation;
						self._currentRotation = targetRotation;
						
						return function(step) 
						{
							var rotation = currentRotation + (targetRotation-currentRotation)*step;
							return "translate(" + self.config.cx + ", " + self.config.cy + ") rotate(" + rotation + ")"; 
						}
					});
	}
	
	this.valueToDegrees = function(value)
	{
		// thanks @closealert
		//return value / this.config.range * 270 - 45;
		return value / this.config.range * 270 - (this.config.min / this.config.range * 270 + 45);
	}
	
	this.valueToRadians = function(value)
	{
		return this.valueToDegrees(value) * Math.PI / 180;
	}
	
	this.valueToPoint = function(value, factor)
	{
		return { 	x: this.config.cx - this.config.raduis * factor * Math.cos(this.valueToRadians(value)),
					y: this.config.cy - this.config.raduis * factor * Math.sin(this.valueToRadians(value))		};
	}
	
	// initialization
	this.configure(configuration);
}