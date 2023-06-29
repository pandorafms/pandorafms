// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/* globals jQuery, d3 */

// The recipient is the selector of the html element
// The elements is an array with the names of the wheel elements
// The matrix must be a 2 dimensional array with a row and a column for each element
// Ex:
// elements = ["a", "b", "c"];
// matrix = [[0, 0, 2],     // a[a => a, a => b, a => c]
//           [5, 0, 1],     // b[b => a, b => b, b => c]
//           [2, 3, 0]];    // c[c => a, c => b, c => c]
function chordDiagram(recipient, elements, matrix, width) {
  d3.chart = d3.chart || {};
  d3.chart.chordWheel = function(options) {
    // Default values
    var width = 700;
    var margin = 150;
    var padding = 0.02;
    var consoleStyle = document.getElementById("hidden-selected_style_theme")
      .value;
    var textColor =
      consoleStyle === "pandora_black" ? "rgb(240, 240, 240)" : "rgb(0, 0, 0)";
    var tooltipColor =
      consoleStyle === "pandora_black" ? "rgb(0, 0, 0)" : "rgb(240, 240, 240)";

    function chart(selection) {
      selection.each(function(data) {
        var matrix = data.matrix;
        var elements = data.elements;
        var radius = width / 2 - margin;

        // create the layout
        var chord = d3.layout
          .chord()
          .padding(padding)
          .sortSubgroups(d3.descending);

        // Select the svg element, if it exists.
        var svg = d3
          .select(this)
          .selectAll("svg")
          .data([data]);

        // Otherwise, create the skeletal chart.
        var gEnter = svg
          .enter()
          .append("svg:svg")
          .attr("width", width)
          .attr("height", width)
          .attr("class", "dependencyWheel")
          .append("g")
          .attr("transform", "translate(" + width / 2 + "," + width / 2 + ")");

        var arc = d3.svg
          .arc()
          .innerRadius(radius)
          .outerRadius(radius + 20);

        var fill = function(d) {
          return "hsl(" + parseInt((d.index / 26) * 360, 10) + ",80%,70%)";
        };

        // Returns an event handler for fading a given chord group.
        var fade = function(opacity) {
          return function(g, i) {
            svg
              .selectAll(".chord")
              .filter(function(d) {
                return d.source.index != i && d.target.index != i;
              })
              .transition()
              .style("opacity", opacity);
            var groups = [];
            svg.selectAll(".chord").filter(function(d) {
              if (d.source.index == i) {
                groups.push(d.target.index);
              }
              if (d.target.index == i) {
                groups.push(d.source.index);
              }
            });
            groups.push(i);
            var length = groups.length;
            svg
              .selectAll(".group")
              .filter(function(d) {
                for (var i = 0; i < length; i++) {
                  if (groups[i] == d.index) return false;
                }
                return true;
              })
              .transition()
              .style("opacity", opacity);

            if (event.type == "mouseover") {
              const chords = chord.chords();
              let aux = 0;
              $.each(chords, function(key, value) {
                if (aux < 5) {
                  if (
                    (value.source.index == i && value.target.subindex == i) ||
                    (value.source.subindex == i && value.target.index == i)
                  ) {
                    if (
                      $("#tooltip").is(":hidden") ||
                      $("#tooltip").length == 0
                    ) {
                      show_tooltip(value);
                    } else {
                      add_tooltip(value);
                      aux++;
                    }
                  }
                }
              });
            } else {
              hide_tooltip();
            }
          };
        };

        chord.matrix(matrix);

        var rootGroup = chord.groups()[0];
        var rotation =
          (-(rootGroup.endAngle - rootGroup.startAngle) / 2) * (180 / Math.PI);

        var g = gEnter
          .selectAll("g.group")
          .data(chord.groups)
          .enter()
          .append("svg:g")
          .attr("class", "group")
          .attr("transform", function(d) {
            return "rotate(" + rotation + ")";
          });

        g.append("svg:path")
          .style("fill", fill)
          .style("stroke", fill)
          .attr("d", arc)
          .on("mouseover", fade(0.1))
          .on("mouseout", fade(1))
          .on("mousemove", move_tooltip);

        g.append("svg:text")
          .each(function(d) {
            d.angle = (d.startAngle + d.endAngle) / 2;
          })
          .attr("dy", ".35em")
          .attr("text-anchor", function(d) {
            return d.angle > Math.PI ? "end" : null;
          })
          .attr("style", "fill: " + textColor)
          .attr("transform", function(d) {
            return (
              "rotate(" +
              ((d.angle * 180) / Math.PI - 90) +
              ")" +
              "translate(" +
              (radius + 26) +
              ")" +
              (d.angle > Math.PI ? "rotate(180)" : "")
            );
          })
          .text(function(d) {
            return elements[d.index];
          });

        gEnter
          .selectAll("path.chord")
          .data(chord.chords)
          .enter()
          .append("svg:path")
          .attr("class", "chord")
          .style("stroke", function(d) {
            return d3.rgb(fill(d.source)).darker();
          })
          .style("fill", function(d) {
            return fill(d.source);
          })
          .attr("d", d3.svg.chord().radius(radius))
          .attr("transform", function(d) {
            return "rotate(" + rotation + ")";
          })
          .style("opacity", 1);

        // Add an elaborate mouseover title for each chord.
        gEnter
          .selectAll("path.chord")
          .on("mouseover", over_user)
          .on("mouseout", out_user)
          .on("mousemove", move_tooltip);

        function move_tooltip(d) {
          x = d3.event.pageX + 10;
          y = d3.event.pageY + 10;

          $("#tooltip").css("left", x + "px");
          $("#tooltip").css("top", y + "px");
        }

        function over_user(d) {
          id = d.id;

          $("#" + id).css("border", "1px solid black");
          $("#" + id).css("z-index", "1");

          show_tooltip(d);
        }

        function out_user(d) {
          id = d.id;

          $("#" + id).css("border", "");
          $("#" + id).css("z-index", "");

          hide_tooltip();
        }

        function create_tooltip(d, x, y) {
          if ($("#tooltip").length == 0) {
            $(recipient).append(
              $("<div></div>")
                .attr("id", "tooltip")
                .html(
                  elements[d.source.index] +
                    " → " +
                    elements[d.target.index] +
                    ": <b>" +
                    valueToBytes(d.source.value) +
                    "</b>" +
                    "<br>" +
                    elements[d.target.index] +
                    " → " +
                    elements[d.source.index] +
                    ": <b>" +
                    valueToBytes(d.target.value) +
                    "</b>"
                )
            );
          } else {
            $("#tooltip").html(
              elements[d.source.index] +
                " → " +
                elements[d.target.index] +
                ": <b>" +
                valueToBytes(d.source.value) +
                "</b>" +
                "<br>" +
                elements[d.target.index] +
                " → " +
                elements[d.source.index] +
                ": <b>" +
                valueToBytes(d.target.value) +
                "</b>"
            );
          }

          $("#tooltip").attr(
            "style",
            "background: " +
              tooltipColor +
              ";" +
              "color: " +
              textColor +
              ";" +
              "position: absolute;" +
              "display: inline-block;" +
              "width: auto;" +
              "max-width: 500px;" +
              "text-align: left;" +
              "padding: 10px 10px 10px 10px;" +
              "z-index: 2;" +
              "-webkit-box-shadow: 7px 7px 5px rgba(50, 50, 50, 0.75);" +
              "-moz-box-shadow:    7px 7px 5px rgba(50, 50, 50, 0.75);" +
              "box-shadow:         7px 7px 5px rgba(50, 50, 50, 0.75);" +
              "left: " +
              x +
              "px;" +
              "top: " +
              y +
              "px;"
          );
        }

        function add_tooltip(d) {
          $("#tooltip").append(
            "</br>" +
              elements[d.source.index] +
              " → " +
              elements[d.target.index] +
              ": <b>" +
              valueToBytes(d.source.value) +
              "</b>" +
              "<br>" +
              elements[d.target.index] +
              " → " +
              elements[d.source.index] +
              ": <b>" +
              valueToBytes(d.target.value) +
              "</b>"
          );
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

  var chart = d3.chart
    .chordWheel()
    .width(width)
    .margin(150)
    .padding(0.02);

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
  var consoleStyle = document.getElementById("hidden-selected_style_theme")
    .value;
  $("#tooltip").css(
    "color",
    consoleStyle === "pandora_black" ? "rgb(240, 240, 240)" : "rgb(0, 0, 0)"
  );
  $("#tooltip").css(
    "background-color",
    consoleStyle === "pandora_black" ? "rgb(0, 0, 0)" : "rgb(240, 240, 240)"
  );
  if (width === "auto") {
    chartWidth = $(recipient).innerWidth();
  }
  if (height === "auto") {
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

  var treemap = d3.layout
    .treemap()
    .round(false)
    .size([chartWidth, chartHeight])
    .sticky(true)
    .value(function(d) {
      return d.value;
    });

  var chart = d3
    .select(recipient)
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
  var parentCells = chart.selectAll("g.cell.parent").data(parents, function(d) {
    return d.id;
  });
  var parentEnterTransition = parentCells
    .enter()
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
  parentEnterTransition
    .append("rect")
    .attr("width", function(d) {
      return Math.max(0.01, d.dx);
    })
    .attr("height", headerHeight)
    .style("fill", headerColor);
  parentEnterTransition
    .append("text")
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
  var parentUpdateTransition = parentCells
    .transition()
    .duration(transitionDuration);
  parentUpdateTransition.select(".cell").attr("transform", function(d) {
    return "translate(" + d.dx + "," + d.y + ")";
  });
  parentUpdateTransition
    .select("rect")
    .attr("width", function(d) {
      return Math.max(0.01, d.dx);
    })
    .attr("height", headerHeight)
    .style("fill", headerColor);
  parentUpdateTransition
    .select(".label")
    .attr("transform", "translate(3, 13)")
    .attr("width", function(d) {
      return Math.max(0.01, d.dx);
    })
    .attr("height", headerHeight)
    .text(function(d) {
      return d.name;
    });
  // remove transition
  parentCells.exit().remove();

  // create children cells
  var childrenCells = chart
    .selectAll("g.cell.child")
    .data(children, function(d) {
      return d.id;
    });

  // enter transition
  var childEnterTransition = childrenCells
    .enter()
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

  childEnterTransition
    .append("rect")
    .classed("background", true)
    .style("fill", function(d) {
      return color(d.name);
    });

  childEnterTransition
    .append("text")
    .attr("class", "label")
    .attr("x", function(d) {
      return d.dx / 2;
    })
    .attr("y", function(d) {
      return d.dy / 2;
    })
    .attr("dy", ".35em")
    .attr("text-anchor", "middle")
    .style("display", "none")
    .text(function(d) {
      return d.name;
    });

  // update transition
  var childUpdateTransition = childrenCells
    .transition()
    .duration(transitionDuration);

  childUpdateTransition.select(".cell").attr("transform", function(d) {
    return "translate(" + d.x + "," + d.y + ")";
  });

  childUpdateTransition
    .select("rect")
    .attr("width", function(d) {
      return Math.max(0.01, d.dx);
    })
    .attr("height", function(d) {
      return d.dy;
    });

  childUpdateTransition
    .select(".label")
    .attr("x", function(d) {
      return d.dx / 2;
    })
    .attr("y", function(d) {
      return d.dy / 2;
    })
    .attr("dy", ".35em")
    .attr("text-anchor", "middle")
    .style("display", "none")
    .text(function(d) {
      return d.name;
    });

  // exit transition
  childrenCells.exit().remove();

  d3.select("select").on("change", function() {
    treemap.value(this.value == "size" ? size : count).nodes(root);
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

  function getRGBComponents(color) {
    var r = color.substring(1, 3);
    var g = color.substring(3, 5);
    var b = color.substring(5, 7);
    return {
      R: parseInt(r, 16),
      G: parseInt(g, 16),
      B: parseInt(b, 16)
    };
  }

  function idealTextColor(bgColor) {
    var nThreshold = 105;
    var components = getRGBComponents(bgColor);
    var bgDelta =
      components.R * 0.299 + components.G * 0.587 + components.B * 0.114;
    return 255 - bgDelta < nThreshold ? "#000000" : "#ffffff";
  }

  function zoom(d) {
    treemap.padding([headerHeight / (chartHeight / d.dy), 0, 0, 0]).nodes(d);

    // moving the next two lines above treemap layout messes up padding of zoom result
    var kx = chartWidth / d.dx;
    var ky = chartHeight / d.dy;
    var level = d;

    xscale.domain([d.x, d.x + d.dx]);
    yscale.domain([d.y, d.y + d.dy]);

    if (node != level) {
      chart.selectAll(".cell.child .label").style("display", "none");
    }

    var zoomTransition = chart
      .selectAll("g.cell")
      .transition()
      .duration(transitionDuration)
      .attr("transform", function(d) {
        return "translate(" + xscale(d.x) + "," + yscale(d.y) + ")";
      })
      .each("start", function() {
        d3.select(this)
          .select("label")
          .style("display", "none");
      })
      .each("end", function(d, i) {
        if (!i && level !== self.root) {
          chart
            .selectAll(".cell.child")
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

    zoomTransition
      .select(".clip")
      .attr("width", function(d) {
        return Math.max(0.01, kx * d.dx);
      })
      .attr("height", function(d) {
        return d.children ? headerHeight : Math.max(0.01, ky * d.dy);
      });

    zoomTransition
      .select(".label")
      .attr("width", function(d) {
        return Math.max(0.01, kx * d.dx);
      })
      .attr("height", function(d) {
        return d.children ? headerHeight : Math.max(0.01, ky * d.dy);
      })
      .text(function(d) {
        return d.name;
      });

    zoomTransition
      .select(".child .label")
      .attr("x", function(d) {
        return (kx * d.dx) / 2;
      })
      .attr("y", function(d) {
        return (ky * d.dy) / 2;
      });

    zoomTransition
      .select("rect")
      .attr("width", function(d) {
        return Math.max(0.01, kx * d.dx);
      })
      .attr("height", function(d) {
        return d.children ? headerHeight : Math.max(0.01, ky * d.dy);
      });

    node = d;

    if (d3.event) {
      d3.event.stopPropagation();
    }
  }

  function position() {
    this.style("left", function(d) {
      return d.x + "px";
    })
      .style("top", function(d) {
        return d.y + "px";
      })
      .style("width", function(d) {
        return Math.max(0, d.dx - 1) + "px";
      })
      .style("height", function(d) {
        return Math.max(0, d.dy - 1) + "px";
      });
  }

  function move_tooltip(d) {
    x = d3.event.pageX + 10;
    y = d3.event.pageY + 10;

    $("#tooltip").css("left", x + "px");
    $("#tooltip").css("top", y + "px");
  }

  function over_user(d) {
    id = d.id;

    $("#" + id).css("border", "1px solid black");
    $("#" + id).css("z-index", "1");

    show_tooltip(d);
  }

  function out_user(d) {
    id = d.id;

    $("#" + id).css("border", "");
    $("#" + id).css("z-index", "");

    hide_tooltip();
  }

  function create_tooltip(d, x, y) {
    if ($("#tooltip").length == 0) {
      $(recipient).append(
        $("<div></div>")
          .attr("id", "tooltip")
          .html(d.tooltip_content)
      );
    } else {
      $("#tooltip").html(d.tooltip_content);
    }

    $("#tooltip").attr(
      "style",
      "background: #fff;" +
        "color: #111;" +
        "position: absolute;" +
        "display: block;" +
        "width: 200px;" +
        "text-align: left;" +
        "padding: 10px 10px 10px 10px;" +
        "z-index: 2;" +
        "-webkit-box-shadow: 7px 7px 5px rgba(50, 50, 50, 0.75);" +
        "-moz-box-shadow:    7px 7px 5px rgba(50, 50, 50, 0.75);" +
        "box-shadow:         7px 7px 5px rgba(50, 50, 50, 0.75);" +
        "left: " +
        x +
        "px;" +
        "top: " +
        y +
        "px;"
    );
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
function sunburst(recipient, data, width, height, tooltip = true) {
  if (width === "auto") {
    width = $(recipient).innerWidth();
  }
  if (height === "auto") {
    height = width;
  }

  var radius = Math.min(width, height) / 2 - 40;

  var x = d3.scale.linear().range([0, 2 * Math.PI]);

  var y = d3.scale.sqrt().range([0, radius]);

  var color = d3.scale.category20c();

  var svg = d3
    .select(recipient)
    .append("svg")
    .attr("width", width)
    .attr("height", height);

  var partition = d3.layout.partition().value(function(d) {
    return d.size;
  });

  var arc = d3.svg
    .arc()
    .startAngle(function(d) {
      return Math.max(0, Math.min(2 * Math.PI, x(d.x)));
    })
    .endAngle(function(d) {
      return Math.max(0, Math.min(2 * Math.PI, x(d.x + d.dx)));
    })
    .innerRadius(function(d) {
      return Math.max(0, y(d.y));
    })
    .outerRadius(function(d) {
      if (d.children || d.depth === 4) {
        return Math.max(0, y(d.y + d.dy));
      } else {
        return Math.max(0, y(d.y + d.dy)) + 20;
      }
    });

  var g = svg
    .selectAll("g")
    .data(partition.nodes(data))
    .enter()
    .append("g")
    .attr(
      "transform",
      "translate(" + width / 2 + "," + (height / 2 + 10) + ")"
    );

  var path = g
    .append("path")
    .attr("d", arc)
    .style("fill", function(d) {
      return d.color
        ? d3.rgb(d.color)
        : color((d.children ? d : d.parent).name);
    })
    .style("cursor", "pointer")
    .style("stroke-width", "0.2")
    .on("click", click)
    .on("mouseover", tooltip === "1" ? over_user : "")
    .on("mouseout", out_user)
    .on("mousemove", move_tooltip);

  function computeTextRotation(d) {
    if (d.type === "central_service") {
      return 0;
    }

    var ang = ((x(d.x + d.dx / 2) - Math.PI / 2) / Math.PI) * 180;
    if (calculate_angle(d) < 20) {
      return ang;
    } else {
      return Math.trunc(ang) == 90 || Math.trunc(ang) == 89
        ? ang - 90
        : 90 + ang;
    }
  }

  var text = g
    .append("text")
    .attr("transform", function(d) {
      if (typeof d.show_name != "undefined" && d.show_name) {
        return (
          "translate(" +
          arc.centroid(d) +
          ")rotate(" +
          computeTextRotation(d) +
          ")"
        );
      }
    })
    .attr("x", function(d) {
      if (typeof d.show_name != "undefined" && d.show_name) {
        if (calculate_angle(d) < 20) {
          return (d.name.length + 15) * -1;
        } else {
          return (d.name.length + 25) * -1;
        }
      }
    })
    .attr("dx", "6") // margin
    .attr("dy", function(d) {
      if (d.type === "central_service") {
        return "-7em";
      }
      return ".35em";
    }) // vertical-align
    .attr("opacity", function(d) {
      if (typeof d.show_name != "undefined" && d.show_name) {
        return 1;
      } else {
        return 0;
      }
    })
    .text(function(d) {
      if (d.name.length > 20) {
        var resta = d.name.length - 12;
        var string = d.name.slice(
          d.name.length / 2 - resta / 2,
          d.name.length / 2 + resta / 2
        );
        var split = d.name.split(`${string}`);
        return `${split[0]}...${split[1]}`;
      }
      return d.name;
    })
    .style("font-size", "11px")
    .style("fill", function(d) {
      if (d.color !== "#82b92e") {
        return "white";
      }
    })
    // Makes svg elements invisible to events
    .style("pointer-events", "none");

  function click(d) {
    if (typeof d.link != "undefined") {
      window.location.href = d.link;
    } else {
      // fade out all text elements
      if (d.type === "central_service") {
        text.transition().attr("opacity", 1);
      } else {
        text.transition().attr("opacity", 0);
      }

      path
        .transition()
        .duration(750)
        .attrTween("d", arcTween(d))
        .each("end", function(e, i) {
          // check if the animated element's data e lies within the visible angle span given in d
          if (
            typeof e.type != "undefined" &&
            (e.type == "group" ||
              (e.type == "agent" &&
                (d.type == "group" ||
                  d.type == "agent" ||
                  d.type == "module_group" ||
                  d.type == "module")) ||
              ((e.type == "module_group" || e.type == "module") &&
                (d.type == "agent" || d.type == "module_group"))) &&
            e.x >= d.x &&
            e.x < d.x + d.dx
          ) {
            // get a selection of the associated text element
            var arcText = d3.select(this.parentNode).select("text");
            // fade in the text element and recalculate positions
            arcText
              .attr("transform", function(d) {
                return (
                  "translate(" +
                  arc.centroid(d) +
                  ")rotate(" +
                  computeTextRotation(d) +
                  ")"
                );
              })
              .attr("x", function(d) {
                if (calculate_angle(d) < 20) {
                  return (d.name.length + 15) * -1;
                } else {
                  return (d.name.length + 25) * -1;
                }
              })
              .transition()
              .duration(250)
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
        ? function(t) {
            return arc(d);
          }
        : function(t) {
            x.domain(xd(t));
            y.domain(yd(t)).range(yr(t));
            return arc(d);
          };
    };
  }

  function move_tooltip(d) {
    var x = d3.event.pageX + 10 - $("#menu_full").width();
    var y = d3.event.pageY - 90;

    $("#tooltip").css("left", x + "px");
    $("#tooltip").css("top", y + "px");
  }

  function over_user(d) {
    id = d.id;

    $("#" + id).css("border", "1px solid black");
    $("#" + id).css("z-index", "1");

    show_tooltip(d);
  }

  function out_user(d) {
    id = d.id;

    $("#" + id).css("border", "");
    $("#" + id).css("z-index", "");

    hide_tooltip();
  }

  function create_tooltip(d, x, y) {
    var tooltip =
      typeof d.tooltip_content != "undefined" ? d.tooltip_content : d.name;

    if ($("#tooltip").length == 0) {
      $(recipient).append(
        $("<div></div>")
          .attr("id", "tooltip")
          .html(tooltip)
      );
    } else {
      $("#tooltip").html(tooltip);
    }

    $("#tooltip").attr(
      "style",
      "background: #fff;" +
        "color: #111;" +
        "position: absolute;" +
        "display: block;" +
        "width: 200px;" +
        "text-align: left;" +
        "padding: 10px 10px 10px 10px;" +
        "z-index: 2;" +
        "-webkit-box-shadow: 7px 7px 5px rgba(50, 50, 50, 0.75);" +
        "-moz-box-shadow:    7px 7px 5px rgba(50, 50, 50, 0.75);" +
        "box-shadow:         7px 7px 5px rgba(50, 50, 50, 0.75);" +
        "left: " +
        100 +
        "px;" +
        "top: " +
        100 +
        "px;"
    );
  }

  function show_tooltip(d) {
    var x = d3.event.pageX + 10;
    var y = d3.event.pageY + 10;

    create_tooltip(d, x, y);
  }

  function hide_tooltip() {
    $("#tooltip").hide();
  }

  function calculate_angle(d) {
    var start_angle = Math.max(0, Math.min(2 * Math.PI, x(d.x)));
    start_angle = (start_angle * 180) / Math.PI;
    var end_angle = Math.max(0, Math.min(2 * Math.PI, x(d.x + d.dx)));
    end_angle = (end_angle * 180) / Math.PI;

    return end_angle - start_angle;
  }
}

function createGauge(
  name,
  etiqueta,
  value,
  min,
  max,
  min_warning,
  max_warning,
  warning_inverse,
  min_critical,
  max_critical,
  critical_inverse,
  font_size,
  height,
  font,
  transitionDuration
) {
  var gauges;

  var config = {
    size: height,
    label: etiqueta,
    min: undefined != min ? min : 0,
    max: undefined != max ? max : 100,
    font_size: font_size,
    transitionDuration: transitionDuration
  };

  if (value == null) {
    config.majorTicks = 1;
    config.minorTicks = 1;
    value = false;
  } else {
    config.minorTicks = 10;
  }

  save_min_critical = min_critical;
  save_max_critical = max_critical;
  save_min_warning = min_warning;
  save_max_warning = max_warning;

  if (max_warning < config.min) {
    config.min = max_warning;
  }
  if (min_warning < config.min) {
    config.min = min_warning;
  }
  if (max_critical < config.min) {
    config.min = max_critical;
  }
  if (min_critical < config.min) {
    config.min = min_critical;
  }

  if (max_warning > config.max) {
    config.max = max_warning;
  }
  if (min_warning > config.max) {
    config.max = min_warning;
  }
  if (max_critical > config.max) {
    config.max = max_critical;
  }
  if (min_critical > config.max) {
    config.max = min_critical;
  }

  if (config.max < value) {
    config.max = value;
  }

  if (config.min > value) {
    config.min = value;
  }

  if (critical_inverse == 1) {
    if (max_critical == 0) {
      max_critical = min_critical;
      min_critical = config.min;
    } else {
      max_critical = save_min_critical;
      min_critical = config.min;
      max_critical2 = config.max;
      min_critical2 = save_max_critical;
    }
  } else {
    if (min_critical > max_critical && max_critical == 0) {
      max_critical = config.max;
    }
  }

  if (warning_inverse == 1) {
    if (max_warning == 0) {
      max_warning = min_warning;
      min_warning = config.min;
    } else {
      max_warning = save_min_warning;
      min_warning = config.min;
      max_warning2 = config.max;
      min_warning2 = save_max_warning;
    }
  } else {
    if (min_warning > max_warning && max_warning == 0) {
      max_warning = config.max;
    }
  }

  if (value !== false) {
    if (typeof max_warning2 !== "undefined") {
      if (min_warning >= 0 && min_warning != max_warning) {
        config.yellowZones = [
          { from: min_warning, to: max_warning },
          { from: min_warning2, to: max_warning2 }
        ];
      }
    } else {
      if (min_warning >= 0 && min_warning != max_warning) {
        config.yellowZones = [{ from: min_warning, to: max_warning }];
      }
    }

    if (typeof max_critical2 !== "undefined") {
      if (min_critical >= 0 && min_critical != max_critical) {
        config.redZones = [
          { from: min_critical, to: max_critical },
          { from: min_critical2, to: max_critical2 }
        ];
      }
    } else {
      if (min_critical >= 0 && min_critical != max_critical) {
        config.redZones = [{ from: min_critical, to: max_critical }];
      }
    }
  }

  var range = config.max - config.min;

  gauges = new Gauge(name, config, font);
  gauges.render();
  gauges.redraw(value, config.transitionDuration);
  $(".gauge>text").each(function() {
    label = $(this).text();

    if (!isNaN(label)) {
      label = parseFloat(label);
      text = label.toLocaleString();
      if (label >= 1000000) text = text.substring(0, 4) + "M";
      else if (label >= 100000) text = text.substring(0, 3) + "K";
      else if (label >= 10000) text = text.substring(0, 3) + "K";

      $(this).text(text);
    }
  });
  $(".pointerContainer>text").each(function() {
    label = $(this).text();

    if (!isNaN(label)) {
      label = parseFloat(label);
      text = label.toLocaleString();
      if (label >= 10000000) text = text.substring(0, 4) + "M";
      else if (label >= 1000000) text = text.substring(0, 4) + "M";
      else if (label >= 100000) text = text.substring(0, 3) + "K";
      else if (label >= 10000) text = text.substring(0, 3) + "K";
      $(this).text(text);
    }
  });
}

function createGauges(
  data,
  width,
  height,
  font_size,
  no_data_image,
  font,
  transitionDuration
) {
  var nombre,
    label,
    minimun_warning,
    maximun_warning,
    minimun_critical,
    maximun_critical,
    mininum,
    maxinum,
    valor;

  for (var key in data) {
    nombre = data[key].gauge;

    label = data[key].label;

    label = label.replace(/&#x20;/g, " ");
    label = label.replace(/\(/g, "(");
    label = label.replace(/\)/g, ")");

    label = label.replace(/&#40;/g, "(");
    label = label.replace(/&#41;/g, ")");

    minimun_warning = round_with_decimals(parseFloat(data[key].min_warning));
    maximun_warning = round_with_decimals(parseFloat(data[key].max_warning));
    minimun_critical = round_with_decimals(parseFloat(data[key].min_critical));
    maximun_critical = round_with_decimals(parseFloat(data[key].max_critical));

    mininum = round_with_decimals(parseFloat(data[key].min));
    maxinum = round_with_decimals(parseFloat(data[key].max));

    var critical_inverse = parseInt(data[key].critical_inverse);
    var warning_inverse = parseInt(data[key].warning_inverse);

    valor = round_with_decimals(data[key].value);

    if (isNaN(valor)) valor = null;
    createGauge(
      nombre,
      label,
      valor,
      mininum,
      maxinum,
      minimun_warning,
      maximun_warning,
      warning_inverse,
      minimun_critical,
      maximun_critical,
      critical_inverse,
      font_size,
      height,
      font,
      transitionDuration
    );
  }
}

function Gauge(placeholderName, configuration, font) {
  var font = font
    .split("/")
    .pop()
    .split(".")
    .shift();
  this.placeholderName = placeholderName;

  var self = this; // for internal d3 functions

  this.configure = function(configuration) {
    this.config = configuration;

    this.config.size = this.config.size * 0.9;
    this.config.font_size = this.config.font_size;

    this.config.raduis = (this.config.size * 0.97) / 2;
    this.config.cx = this.config.size / 2;
    this.config.cy = this.config.size / 2;

    this.config.min = undefined != configuration.min ? configuration.min : 0;
    this.config.max = undefined != configuration.max ? configuration.max : 100;
    this.config.range = this.config.max - this.config.min;

    this.config.majorTicks = configuration.majorTicks || 5;
    this.config.minorTicks = configuration.minorTicks || 2;

    this.config.greenColor = configuration.greenColor || "#109618";
    this.config.yellowColor = configuration.yellowColor || "#FF9900";
    this.config.redColor = configuration.redColor || "#DC3912";

    this.config.transitionDuration = configuration.transitionDuration;
  };

  this.render = function() {
    this.body = d3
      .select("#" + this.placeholderName)
      .append("svg:svg")
      .attr("class", "gauge")
      .attr("width", this.config.size)
      .attr("height", this.config.size);

    this.body
      .append("svg:circle")
      .attr("cx", this.config.cx)
      .attr("cy", this.config.cy)
      .attr("r", this.config.raduis)
      .style("fill", "#ccc")
      .style("stroke", "#000")
      .style("stroke-width", "0.5px");

    this.body
      .append("svg:circle")
      .attr("cx", this.config.cx)
      .attr("cy", this.config.cy)
      .attr("r", 0.9 * this.config.raduis)
      .style("fill", "#fff")
      .style("stroke", "#e0e0e0")
      .style("stroke-width", "2px");

    for (var index in this.config.greenZones) {
      this.drawBand(
        this.config.greenZones[index].from,
        this.config.greenZones[index].to,
        self.config.greenColor
      );
    }

    for (var index in this.config.yellowZones) {
      this.drawBand(
        this.config.yellowZones[index].from,
        this.config.yellowZones[index].to,
        self.config.yellowColor
      );
    }

    for (var index in this.config.redZones) {
      this.drawBand(
        this.config.redZones[index].from,
        this.config.redZones[index].to,
        self.config.redColor
      );
    }

    if (undefined != this.config.label) {
      var fontSize = Math.round(this.config.size / 9);
      this.body
        .append("svg:text")
        .attr("x", this.config.cx)
        .attr("y", this.config.cy / 2 + fontSize / 2)
        .attr("dy", fontSize / 2)
        .attr("text-anchor", "middle")
        .attr("class", font)
        .text(this.config.label)
        .style("font-size", this.config.font_size + "pt")
        .style("fill", "#333")
        .style("stroke-width", "0px");
    }

    var fontSize = Math.round(this.config.size / 16);
    var majorDelta = this.config.range / (this.config.majorTicks - 1);
    for (
      var major = this.config.min;
      major <= this.config.max;
      major += majorDelta
    ) {
      var minorDelta = majorDelta / this.config.minorTicks;
      for (
        var minor = major + minorDelta;
        minor < Math.min(major + majorDelta, this.config.max);
        minor += minorDelta
      ) {
        var point1 = this.valueToPoint(minor, 0.75);
        var point2 = this.valueToPoint(minor, 0.85);

        this.body
          .append("svg:line")
          .attr("x1", point1.x)
          .attr("y1", point1.y)
          .attr("x2", point2.x)
          .attr("y2", point2.y)
          .style("stroke", "#666")
          .style("stroke-width", "1px");
      }

      var point1 = this.valueToPoint(major, 0.7);
      var point2 = this.valueToPoint(major, 0.85);

      this.body
        .append("svg:line")
        .attr("x1", point1.x)
        .attr("y1", point1.y)
        .attr("x2", point2.x)
        .attr("y2", point2.y)
        .style("stroke", "#333")
        .style("stroke-width", "2px");

      if (major == this.config.min || major == this.config.max) {
        var point = this.valueToPoint(major, 0.63);

        this.body
          .append("svg:text")
          .attr("x", point.x)
          .attr("y", point.y)
          .attr("dy", fontSize / 3)
          .attr("text-anchor", major == this.config.min ? "start" : "end")
          .text(major)
          .style("font-size", this.config.font_size + "pt")
          .style("fill", "#333")
          .style("stroke-width", "0px");
      }
    }

    var pointerContainer = this.body
      .append("svg:g")
      .attr("class", "pointerContainer");

    var midValue = (this.config.min + this.config.max) / 2;

    var pointerPath = this.buildPointerPath(midValue);

    var pointerLine = d3.svg
      .line()
      .x(function(d) {
        return d.x;
      })
      .y(function(d) {
        return d.y;
      })
      .interpolate("basis");

    pointerContainer
      .selectAll("path")
      .data([pointerPath])
      .enter()
      .append("svg:path")
      .attr("d", pointerLine)
      .style("fill", "#dc3912")
      .style("stroke", "#c63310")
      .style("fill-opacity", 0.7);

    pointerContainer
      .append("svg:circle")
      .attr("cx", this.config.cx)
      .attr("cy", this.config.cy)
      .attr("r", 0.12 * this.config.raduis)
      .style("fill", "#4684EE")
      .style("stroke", "#666")
      .style("opacity", 1);

    var fontSize = Math.round(this.config.size / 10);
    pointerContainer
      .selectAll("text")
      .data([midValue])
      .enter()
      .append("svg:text")
      .attr("x", this.config.cx)
      .attr("y", this.config.size - this.config.cy / 4 - fontSize)
      .attr("dy", fontSize / 2)
      .attr("text-anchor", "middle")
      .style("font-size", this.config.font_size + "pt")
      .style("fill", "#000")
      .style("stroke-width", "0px");

    this.redraw(this.config.min, 0);
  };

  this.buildPointerPath = function(value) {
    var delta = this.config.range / 13;

    var head = valueToPoint(value, 0.85);
    var head1 = valueToPoint(value - delta, 0.12);
    var head2 = valueToPoint(value + delta, 0.12);

    var tailValue = value - (this.config.range * (1 / (270 / 360))) / 2;
    var tail = valueToPoint(tailValue, 0.28);
    var tail1 = valueToPoint(tailValue - delta, 0.12);
    var tail2 = valueToPoint(tailValue + delta, 0.12);

    return [head, head1, tail2, tail, tail1, head2, head];

    function valueToPoint(value, factor) {
      var point = self.valueToPoint(value, factor);
      point.x -= self.config.cx;
      point.y -= self.config.cy;
      return point;
    }
  };

  this.drawBand = function(start, end, color) {
    if (start === undefined) return;
    if (end === undefined) return;
    if (0 >= end - start) return;

    this.body
      .append("svg:path")
      .style("fill", color)
      .attr(
        "d",
        d3.svg
          .arc()
          .startAngle(this.valueToRadians(start))
          .endAngle(this.valueToRadians(end))
          .innerRadius(Math.round(0.65 * this.config.raduis))
          .outerRadius(Math.round(0.85 * this.config.raduis))
      )
      .attr("transform", function() {
        return (
          "translate(" +
          self.config.cx +
          ", " +
          self.config.cy +
          ") rotate(270)"
        );
      });
  };

  this.redraw = function(value, transitionDuration) {
    var pointerContainer = this.body.select(".pointerContainer");

    pointerContainer.selectAll("text").text(round_with_decimals(value));

    var pointer = pointerContainer.selectAll("path");
    pointer
      .transition()
      .duration(undefined != transitionDuration ? transitionDuration : 0)
      //.delay(0)
      //.ease("linear")
      //.attr("transform", function(d)
      .attrTween("transform", function() {
        var pointerValue = value;
        if (value > self.config.max)
          pointerValue = self.config.max + 0.02 * self.config.range;
        else if (value < self.config.min)
          pointerValue = self.config.min - 0.02 * self.config.range;
        var targetRotation = self.valueToDegrees(pointerValue) - 90;
        var currentRotation = self._currentRotation || targetRotation;
        self._currentRotation = targetRotation;

        return function(step) {
          var rotation =
            currentRotation + (targetRotation - currentRotation) * step;
          return (
            "translate(" +
            self.config.cx +
            ", " +
            self.config.cy +
            ") rotate(" +
            rotation +
            ")"
          );
        };
      });
  };

  this.valueToDegrees = function(value) {
    // thanks @closealert
    //return value / this.config.range * 270 - 45;
    return (
      (value / this.config.range) * 270 -
      ((this.config.min / this.config.range) * 270 + 45)
    );
  };

  this.valueToRadians = function(value) {
    return (this.valueToDegrees(value) * Math.PI) / 180;
  };

  this.valueToPoint = function(value, factor) {
    return {
      x:
        this.config.cx -
        this.config.raduis * factor * Math.cos(this.valueToRadians(value)),
      y:
        this.config.cy -
        this.config.raduis * factor * Math.sin(this.valueToRadians(value))
    };
  };

  // initialization
  this.configure(configuration);
}

function print_phases_donut(recipient, phases, width, height) {
  var svg = d3
    .select(recipient)
    .append("svg")
    .attr("width", width)
    .attr("height", height)
    .append("g");

  svg.append("g").attr("class", "slices");
  svg.append("g").attr("class", "labels");
  svg.append("g").attr("class", "lines");

  var radius = Math.min(width, height) / 2 - 50;

  var pie = d3.layout
    .pie()
    .sort(null)
    .value(function(d) {
      return parseFloat(d.label2);
    });

  var arc = d3.svg
    .arc()
    .outerRadius(radius * 0.8)
    .innerRadius(radius * 0.4);

  var outerArc = d3.svg
    .arc()
    .innerRadius(radius * 0.9)
    .outerRadius(radius * 0.9);

  svg.attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

  var key = function(d) {
    return d.data.label;
  };

  function phasesData() {
    return phases.map(function(phase) {
      return {
        label: phase_name(phase, 1),
        label2: phase_name(phase, 2),
        value: phase.status
      };
    });
  }

  function phase_name(phase, index) {
    if (index == 1) {
      return phase.phase_name;
    } else {
      return phase.phase_time;
    }
  }

  print_phases(phasesData());

  function print_phases(data) {
    /* ------- PIE SLICES -------*/
    var slice = svg
      .select(".slices")
      .selectAll("path.slice")
      .data(pie(data), key);

    slice
      .enter()
      .insert("path")
      .style("fill", function(d) {
        if (d.data.value == 0) {
          return "#82b92e";
        } else {
          return "#e63c52";
        }
      })
      .attr("class", "slice");

    slice
      .transition()
      .duration(0)
      .attrTween("d", function(d) {
        this._current = this._current || d;
        var interpolate = d3.interpolate(this._current, d);
        this._current = interpolate(0);
        return function(t) {
          return arc(interpolate(t));
        };
      });

    slice.exit().remove();

    /* ------- TEXT LABELS -------*/
    var text = svg
      .select(".labels")
      .selectAll("text")
      .data(pie(data), key);

    text
      .enter()
      .append("text")
      .append("tspan")
      .attr("dy", ".1em")
      .text(function(d) {
        return d.data.label;
      })
      .style("font-size", "15px")
      .append("tspan")
      .attr("dy", "1.2em")
      .attr("dx", "-2.8em")
      .text(function(d) {
        return d.data.label2 + "ms";
      })
      .style("font-size", "15px");

    function midAngle(d) {
      return d.startAngle + (d.endAngle - d.startAngle) / 2;
    }

    var ex = 1;
    var sum = 0;
    text
      .transition()
      .duration(0)
      .attrTween("transform", function(d) {
        this._current = this._current || d;
        var interpolate = d3.interpolate(this._current, d);
        this._current = interpolate(0);
        return function(t) {
          var d2 = interpolate(t);

          //fix for labels of a very small portion increase the
          //height of the label so that they do not overlap
          if (d2.endAngle - d2.startAngle < 0.1) {
            var pos = outerArc.centroid(d2);
            if (ex % 2 == 0) {
              pos[0] = 150;
            } else {
              pos[0] = -150;
              sum++;
            }
            pos[1] = pos[1] - 35 * sum;
            ex++;
          } else {
            var pos = outerArc.centroid(d2);
            pos[0] = radius * (midAngle(d2) < Math.PI ? 1 : -1);
          }

          return "translate(" + pos + ")";
        };
      })
      .styleTween("text-anchor", function(d) {
        this._current = this._current || d;
        var interpolate = d3.interpolate(this._current, d);
        this._current = interpolate(0);
        return function(t) {
          var d2 = interpolate(t);

          //fix for labels of a very small portion increase the
          //height of the label so that they do not overlap
          if (d2.endAngle - d2.startAngle < 0.1) {
            if (ex % 2 == 0) {
              return "start";
            } else {
              return "end";
            }
          }
          return midAngle(d2) < Math.PI ? "start" : "end";
        };
      });

    text.exit().remove();

    /* ------- SLICE TO TEXT POLYLINES -------*/
    var polyline = svg
      .select(".lines")
      .selectAll("polyline")
      .data(pie(data), key);

    polyline.enter().append("polyline");

    var ex2 = 1;
    var sum2 = 0;
    polyline
      .transition()
      .duration(0)
      .attrTween("points", function(d) {
        this._current = this._current || d;
        var interpolate = d3.interpolate(this._current, d);
        this._current = interpolate(0);
        return function(t) {
          var d2 = interpolate(t);

          //fix for labels of a very small portion increase the
          //height of the label so that they do not overlap
          if (d2.endAngle - d2.startAngle < 0.1) {
            var pos = outerArc.centroid(d2);
            if (ex2 % 2 == 0) {
              pos[0] = 150 * 0.95;
            } else {
              pos[0] = -150 * 0.95;
              sum2++;
            }
            pos[1] = pos[1] - 30 * sum2;
            ex2++;
          } else {
            var pos = outerArc.centroid(d2);
            pos[0] = radius * 0.95 * (midAngle(d2) < Math.PI ? 1 : -1);
          }
          return [arc.centroid(d2), outerArc.centroid(d2), pos];
        };
      })
      .style("stroke", "black")
      .style("opacity", ".3")
      .style("stroke-width", "2px")
      .style("fill", "none");

    polyline.exit().remove();
  }
}

function progress_bar_d3(
  recipient,
  percentile,
  width,
  height,
  color,
  unit,
  label,
  label_color,
  radiusx,
  radiusy,
  transition
) {
  var startPercent = 0;
  var endPercent = parseInt(percentile) / 100;
  var count = Math.abs((endPercent - startPercent) / 0.01);
  var step = endPercent < startPercent ? -0.01 : 0.01;

  var circle = d3
    .select(recipient)
    .append("svg")
    .attr("width", width)
    .attr("height", height);

  var progress_back = circle
    .append("rect")
    .attr("fill", "#000000")
    .attr("fill-opacity", 0.5)
    .attr("height", height)
    .attr("width", width)
    .attr("rx", radiusx)
    .attr("ry", radiusy)
    .attr("x", 0);

  var progress_front = circle
    .append("rect")
    .attr("fill", color)
    .attr("fill-opacity", 1)
    .attr("height", height)
    .attr("width", 0)
    .attr("rx", radiusx)
    .attr("ry", radiusy)
    .attr("x", 0);

  var labelText = circle
    .append("text")
    .attr("transform", "translate(" + width / 2 + ", " + height / 2 + ")")
    .attr("fill", label_color)
    .style("font-weight", "bold")
    .style("font-size", 20)
    .html(label)
    .attr("dy", "15")
    .attr("text-anchor", "middle");

  var numberText = circle
    .append("text")
    .attr("transform", "translate(" + width / 2 + ", " + height / 2 + ")")
    .attr("fill", "#FFFFFF")
    .style("font-weight", "bold")
    .style("font-size", 14)
    .attr("text-anchor", "middle")
    .attr("dy", (height - height / 2) / 4);

  function updateProgress(bar_progress) {
    var percent_value = Number(bar_progress * 100);
    numberText.text(percent_value.toFixed() + " " + unit);
    progress_front.attr("width", width * bar_progress);
  }

  if (transition == 0) {
    var bar_progress = endPercent;
    updateProgress(bar_progress);
  } else {
    var bar_progress = startPercent;
    (function loops() {
      updateProgress(bar_progress);

      if (count > 0) {
        count--;
        bar_progress += step;
        setTimeout(loops, 30);
      }
    })();
  }
}

function progress_bubble_d3(
  recipient,
  percentile,
  width,
  height,
  color,
  unit,
  label,
  label_color
) {
  var startPercent = 0;
  var endPercent = parseInt(percentile) / 100;
  var count = Math.abs((endPercent - startPercent) / 0.01);
  var step = endPercent < startPercent ? -0.01 : 0.01;

  var numberSize = 0;
  var textSize = 0;
  var unitSize = 0;
  var yPosText = 0;
  var yPosNumber = 0;
  if (width >= 500) {
    numberSize = 100;
    textSize = 50;
    unitSize = 50;
    yPosNumber = "15";
    yPosText = "-100";
  } else if (width >= 400) {
    numberSize = 80;
    textSize = 40;
    unitSize = 40;
    yPosNumber = "15";
    yPosText = "-80";
  } else if (width >= 300) {
    numberSize = 60;
    textSize = 30;
    unitSize = 30;
    yPosNumber = "15";
    yPosText = "-45";
  } else if (width >= 200) {
    numberSize = 40;
    textSize = 20;
    unitSize = 20;
    yPosNumber = "50";
    yPosText = "-30";
  } else if (width >= 100) {
    numberSize = 20;
    textSize = 10;
    unitSize = 10;
    yPosNumber = "5";
    yPosText = "-20";
  } else {
    numberSize = 10;
    textSize = 8;
    unitSize = 8;
    yPosNumber = "5";
    yPosText = "-10";
  }

  var circle = d3
    .select(recipient)
    .append("svg")
    .attr("width", width)
    .attr("height", height);

  var progress_back = circle
    .append("circle")
    .attr("transform", "translate(" + width / 2 + ", " + height / 2 + ")")
    .attr("fill", "#000000")
    .attr("fill-opacity", 0)
    .attr("r", width / 2);

  var progress_front = circle
    .append("circle")
    .attr("transform", "translate(" + width / 2 + ", " + height / 2 + ")")
    .attr("fill", color)
    .attr("fill-opacity", 1)
    .attr("r", 0);

  var labelText = circle
    .append("text")
    .attr("transform", "translate(" + width / 2 + ", " + height / 2 + ")")
    .attr("fill", label_color)
    .style("font-weight", "bold")
    .style("font-size", textSize)
    .html(label)
    .attr("dy", -(width / 3))
    .attr("text-anchor", "middle");

  var numberText = circle
    .append("text")
    .attr("transform", "translate(" + width / 2 + ", " + height / 2 + ")")
    .attr("fill", label_color)
    .style("font-weight", "bold")
    .style("font-size", numberSize)
    .attr("text-anchor", "middle")
    .attr("dy", width / 3);

  function updateProgress(bar_progress) {
    var percent_value = Number(bar_progress * 100);
    numberText.text(percent_value.toFixed() + " " + unit);
    progress_front.attr("r", (width / 2) * bar_progress);
  }

  var bar_progress = startPercent;

  (function loops() {
    updateProgress(bar_progress);

    if (count > 0) {
      count--;
      bar_progress += step;
      setTimeout(loops, 30);
    }
  })();
}

function print_circular_progress_bar(
  recipient,
  percentile,
  width,
  height,
  color,
  unit,
  label,
  label_color,
  transition
) {
  var twoPi = Math.PI * 2;
  var radius = width / 2;
  var border = 20;
  var startPercent = 0;
  var endPercent = parseInt(percentile) / 100;
  var count = Math.abs((endPercent - startPercent) / 0.01);
  var step = endPercent < startPercent ? -0.01 : 0.01;

  var numberSize = 0;
  var textSize = 0;
  var unitSize = 0;
  var yPosText = 0;
  var yPosUnit = 0;
  var yPosNumber = 0;
  if (width >= 500) {
    numberSize = 100;
    textSize = 50;
    unitSize = 50;
    yPosNumber = "15";
    yPosText = "-100";
    yPosUnit = "100";
  } else if (width >= 400) {
    numberSize = 80;
    textSize = 40;
    unitSize = 40;
    yPosNumber = "15";
    yPosText = "-80";
    yPosUnit = "80";
  } else if (width >= 300) {
    numberSize = 60;
    textSize = 30;
    unitSize = 30;
    yPosNumber = "15";
    yPosText = "-45";
    yPosUnit = "60";
  } else if (width >= 200) {
    numberSize = 40;
    textSize = 20;
    unitSize = 20;
    yPosNumber = "10";
    yPosText = "-30";
    yPosUnit = "40";
  } else if (width >= 100) {
    numberSize = 20;
    textSize = 10;
    unitSize = 10;
    yPosNumber = "5";
    yPosText = "-15";
    yPosUnit = "20";
  } else {
    numberSize = 8;
    textSize = 4;
    unitSize = 4;
    yPosNumber = "2";
    yPosText = "-5";
    yPosUnit = "5";
  }

  var arc = d3.svg
    .arc()
    .startAngle(0)
    .innerRadius(radius)
    .outerRadius(radius - border);

  var circle = d3
    .select(recipient)
    .append("svg")
    .attr("width", width)
    .attr("height", height)
    .append("g")
    .attr("transform", "translate(" + width / 2 + ", " + height / 2 + ")");

  var meter = circle.append("g").attr("class", "progress-meter");

  meter
    .append("path")
    .attr("fill", "#000000")
    .attr("fill-opacity", 0.5)
    .attr("d", arc.endAngle(twoPi));

  var foreground = circle
    .append("path")
    .attr("fill", color)
    .attr("fill-opacity", 1)
    .attr("stroke", color)
    .attr("stroke-opacity", 1);

  var front = circle
    .append("path")
    .attr("fill", color)
    .attr("fill-opacity", 1);

  var labelText = circle
    .append("text")
    .attr("fill", label_color)
    .style("font-weight", "bold")
    .style("font-size", textSize)
    .html(label)
    .attr("text-anchor", "middle")
    .attr("dy", yPosText);

  var numberText = circle
    .append("text")
    .attr("fill", "#333333")
    .style("font-weight", "bold")
    .style("font-size", numberSize)
    .attr("text-anchor", "middle")
    .attr("dy", yPosNumber);

  var percentText = circle
    .append("text")
    .attr("fill", "#333333")
    .style("font-weight", "bold")
    .style("font-size", unitSize)
    .text(unit)
    .attr("text-anchor", "middle")
    .attr("dy", yPosUnit);

  function updateProgress(progress) {
    foreground.attr("d", arc.endAngle(twoPi * progress));
    front.attr("d", arc.endAngle(twoPi * progress));
    var percent_value = Number(progress * 100);
    numberText.text(percent_value.toFixed());
  }

  var progress = startPercent;

  if (transition == 0) updateProgress(endPercent);
  else {
    (function loops() {
      updateProgress(progress);

      if (count > 0) {
        count--;
        progress += step;
        setTimeout(loops, 30);
      }
    })();
  }
}

function print_interior_circular_progress_bar(
  recipient,
  percentile,
  width,
  height,
  color,
  unit,
  label,
  label_color
) {
  var twoPi = Math.PI * 2;
  var radius = width / 2 - 20;
  var radius2 = width / 2;
  var border = 20;
  var startPercent = 0;
  var endPercent = parseInt(percentile) / 100;
  var count = Math.abs((endPercent - startPercent) / 0.01);
  var step = endPercent < startPercent ? -0.01 : 0.01;

  var numberSize = 0;
  var textSize = 0;
  var unitSize = 0;
  var yPosText = 0;
  var yPosUnit = 0;
  var yPosNumber = 0;
  if (width >= 500) {
    numberSize = 100;
    textSize = 50;
    unitSize = 50;
    yPosNumber = "15";
    yPosText = "-100";
    yPosUnit = "100";
  } else if (width >= 400) {
    numberSize = 80;
    textSize = 40;
    unitSize = 40;
    yPosNumber = "15";
    yPosText = "-80";
    yPosUnit = "80";
  } else if (width >= 300) {
    numberSize = 60;
    textSize = 30;
    unitSize = 30;
    yPosNumber = "15";
    yPosText = "-45";
    yPosUnit = "60";
  } else if (width >= 200) {
    numberSize = 40;
    textSize = 20;
    unitSize = 20;
    yPosNumber = "10";
    yPosText = "-30";
    yPosUnit = "40";
  } else if (width >= 100) {
    numberSize = 20;
    textSize = 10;
    unitSize = 10;
    yPosNumber = "5";
    yPosText = "-15";
    yPosUnit = "20";
  } else {
    numberSize = 8;
    textSize = 4;
    unitSize = 4;
    yPosNumber = "2";
    yPosText = "-5";
    yPosUnit = "5";
  }

  var arc = d3.svg
    .arc()
    .startAngle(0)
    .innerRadius(radius)
    .outerRadius(radius - border);

  var arc2 = d3.svg
    .arc()
    .startAngle(0)
    .innerRadius(radius2)
    .outerRadius(radius2 - border);

  var circle = d3
    .select(recipient)
    .append("svg")
    .attr("width", width)
    .attr("height", height)
    .append("g")
    .attr("transform", "translate(" + width / 2 + ", " + height / 2 + ")");

  var meter = circle.append("g").attr("class", "progress-meter");

  meter
    .append("path")
    .attr("fill", "#000000")
    .attr("fill-opacity", 0.5)
    .attr("d", arc.endAngle(twoPi));

  var meter = circle.append("g").attr("class", "progress-meter");

  meter
    .append("path")
    .attr("fill", color)
    .attr("fill-opacity", 1)
    .attr("d", arc2.endAngle(twoPi));

  var foreground = circle
    .append("path")
    .attr("fill", color)
    .attr("fill-opacity", 1)
    .attr("stroke", color)
    .attr("stroke-opacity", 1);

  var front = circle
    .append("path")
    .attr("fill", color)
    .attr("fill-opacity", 1);

  var labelText = circle
    .append("text")
    .attr("fill", label_color)
    .style("font-weight", "bold")
    .style("font-size", textSize)
    .html(label)
    .attr("text-anchor", "middle")
    .attr("dy", yPosText);

  var numberText = circle
    .append("text")
    .attr("fill", label_color)
    .style("font-weight", "bold")
    .style("font-size", numberSize)
    .attr("text-anchor", "middle")
    .attr("dy", yPosNumber);

  var percentText = circle
    .append("text")
    .attr("fill", label_color)
    .style("font-weight", "bold")
    .style("font-size", unitSize)
    .text(unit)
    .attr("text-anchor", "middle")
    .attr("dy", yPosUnit);

  function updateProgress(progress) {
    foreground.attr("d", arc.endAngle(twoPi * progress));
    front.attr("d", arc.endAngle(twoPi * progress));
    var percent_value = Number(progress * 100);
    numberText.text(percent_value.toFixed());
  }

  var progress = startPercent;

  (function loops() {
    updateProgress(progress);

    if (count > 0) {
      count--;
      progress += step;
      setTimeout(loops, 30);
    }
  })();
}

// eslint-disable-next-line no-unused-vars
function print_donut_graph(
  recipient,
  width,
  height,
  module_data,
  resume_color
) {
  var svg = d3
    .select(recipient)
    .append("svg")
    .attr("width", width)
    .attr("height", height)
    .append("g");

  svg.append("g").attr("class", "slices");

  var heightLegend = 25 * module_data.length;

  var maxRadius = (height - heightLegend) / 2;

  var radius = maxRadius;
  if (maxRadius > width / 2) {
    radius = width / 2;
  }

  var arc = d3.svg
    .arc()
    .outerRadius(radius * 0.8)
    .innerRadius(radius * 0.4);

  var key = function(d) {
    return d.data.label;
  };

  var pie = d3.layout
    .pie()
    .sort(null)
    .value(function(d) {
      return parseFloat(d.percent);
    });

  jQuery.each(module_data, function(key, m_d) {
    svg
      .append("g")
      .append("rect")
      .attr("fill", m_d.color)
      .attr("x", 20)
      .attr("y", 20 * (key + 1))
      .attr("width", 25)
      .attr("height", 15);

    svg
      .append("g")
      .append("text")
      .attr("fill", resume_color)
      .attr("transform", "translate(" + 40 + "," + 20 * (key + 1) + ")")
      .attr("x", 15)
      .attr("y", 10)
      .text(m_d.tag_name)
      .style("font-size", "7pt");
  });

  function donutData() {
    return module_data.map(function(m_data) {
      return {
        label: m_data.tag_name,
        percent: m_data.percent,
        color: m_data.color
      };
    });
  }

  print_phases(donutData());

  function print_phases(data) {
    var slice = svg
      .select(".slices")
      .selectAll("path.slice")
      .data(pie(data), key);

    slice
      .enter()
      .insert("path")
      .style("fill", function(d) {
        return d.data.color;
      })
      .attr("class", "slice")
      .attr(
        "transform",
        "translate(" + width / 2 + "," + (height + heightLegend) / 2 + ")"
      );

    slice
      .transition()
      .duration(0)
      .attrTween("d", function(d) {
        this._current = this._current || d;
        var interpolate = d3.interpolate(this._current, d);
        this._current = interpolate(0);
        return function(t) {
          return arc(interpolate(t));
        };
      });

    slice.exit().remove();
  }
}

function printClockAnalogic1(
  time_format,
  timezone,
  clock_animation,
  width,
  height,
  id_element,
  color
) {
  if (width != 0) {
    width = width - 20;
    height = width - 20;
  }

  if (width == 0) {
    width = 180;
    height = 180;
  }

  var radians = 0.0174532925,
    clockRadius = width / 2,
    margin = 10,
    width = (clockRadius + margin) * 2,
    height = (clockRadius + margin) * 2,
    hourHandLength = (2 * clockRadius) / 3,
    minuteHandLength = clockRadius,
    secondHandLength = clockRadius - 12,
    secondHandBalance = 30,
    secondTickStart = clockRadius;
  (secondTickLength = -10),
    (hourTickStart = clockRadius),
    (hourTickLength = -18),
    (secondLabelRadius = clockRadius + 16),
    (secondLabelYOffset = 5),
    (hourLabelRadius = clockRadius - 40),
    (hourLabelYOffset = 7);

  var hourScale = d3.scale
    .linear()
    .range([0, 330])
    .domain([0, 11]);

  var minuteScale = (secondScale = d3.scale
    .linear()
    .range([0, 354])
    .domain([0, 59]));

  var handData = [
    {
      type: "hour",
      value: 0,
      length: -hourHandLength,
      scale: hourScale
    },
    {
      type: "minute",
      value: 0,
      length: -minuteHandLength,
      scale: minuteScale
    },
    {
      type: "second",
      value: 0,
      length: -secondHandLength,
      scale: secondScale,
      balance: secondHandBalance
    }
  ];

  function drawClock() {
    //create all the clock elements
    updateData(timezone); //draw them in the correct starting position
    var svg = d3
      .select("#clock_" + id_element)
      .append("svg")
      .attr("width", width)
      .attr("height", height);

    var face = svg
      .append("g")
      .attr("id", "clock-face")
      .attr("class", "invert_filter")
      .attr(
        "transform",
        "translate(" +
          (clockRadius + margin) +
          "," +
          (clockRadius + margin) +
          ")"
      );

    //add marks for seconds
    face
      .selectAll(".second-tick")
      .data(d3.range(0, 60))
      .enter()
      .append("line")
      .attr("class", "second-tick")
      .attr("x1", 0)
      .attr("x2", 0)
      .attr("y1", secondTickStart)
      .attr("y2", secondTickStart + secondTickLength)
      .attr("stroke", color)
      .attr("transform", function(d) {
        return "rotate(" + secondScale(d) + ")";
      });
    //and labels

    // face.selectAll('.second-label')
    // 	.data(d3.range(5,61,5))
    // 		.enter()
    // 		.append('text')
    // 		.attr('class', 'second-label')
    // 		.attr('text-anchor','middle')
    // 		.attr('x',function(d){
    // 			return secondLabelRadius*Math.sin(secondScale(d)*radians);
    // 		})
    // 		.attr('y',function(d){
    // 			return -secondLabelRadius*Math.cos(secondScale(d)*radians) + secondLabelYOffset;
    // 		})
    // 		.text(function(d){
    // 			return d;
    // 		});

    //... and hours
    face
      .selectAll(".hour-tick")
      .data(d3.range(0, 12))
      .enter()
      .append("line")
      .attr("class", "hour-tick")
      .attr("x1", 0)
      .attr("x2", 0)
      .attr("y1", hourTickStart)
      .attr("y2", hourTickStart + hourTickLength)
      .attr("stroke", color)
      .attr("transform", function(d) {
        return "rotate(" + hourScale(d) + ")";
      });

    face
      .selectAll(".hour-label")
      .data(d3.range(3, 13, 3))
      .enter()
      .append("text")
      .attr("class", "hour-label")
      .attr("text-anchor", "middle")
      .attr("stroke", color)
      .attr("x", function(d) {
        return hourLabelRadius * Math.sin(hourScale(d) * radians);
      })
      .attr("y", function(d) {
        return (
          -hourLabelRadius * Math.cos(hourScale(d) * radians) + hourLabelYOffset
        );
      })
      .text(function(d) {
        return d;
      });

    var hands = face.append("g").attr("id", "clock-hands");

    face
      .append("g")
      .attr("id", "face-overlay")
      .append("circle")
      .attr("class", "hands-cover")
      .attr("stroke", color)
      .attr("x", 0)
      .attr("y", 0)
      .attr("r", clockRadius / 20);

    hands
      .selectAll("line")
      .data(handData)
      .enter()
      .append("line")
      .attr("stroke", color)
      .attr("class", function(d) {
        return d.type + "-hand";
      })
      .attr("x1", 0)
      .attr("y1", function(d) {
        return d.balance ? d.balance : 0;
      })
      .attr("x2", 0)
      .attr("y2", function(d) {
        return d.length;
      })
      .attr("transform", function(d) {
        return "rotate(" + d.scale(d.value) + ")";
      });
  }

  function moveHands() {
    d3.select("#clock_" + id_element + " #clock-hands")
      .selectAll("line")
      .data(handData)
      .transition()
      .attr("transform", function(d) {
        return "rotate(" + d.scale(d.value) + ")";
      });
  }

  function updateData(tz) {
    var d = new Date();
    var dt = d.getTime();
    os = d.getTimezoneOffset();
    tz = parseInt(tz) + parseInt(os * 60);
    var t = new Date(dt + tz * 1000);

    handData[0].value = (t.getHours() % 12) + t.getMinutes() / 60;
    handData[1].value = t.getMinutes();
    handData[2].value = t.getSeconds();
  }

  drawClock();

  setInterval(function() {
    updateData(timezone);
    moveHands();
  }, 1000);

  d3.select(self.frameElement).style("height", height + "px");

  $("#clock_" + id_element).css("margin-top", "0");
}

function printClockDigital1(
  time_format,
  timezone,
  clock_animation,
  width,
  height,
  id_element,
  color
) {
  var svgUnderlay = d3.select("#clock_" + id_element + " svg"),
    svgOverlay = d3.select("#clock_" + id_element),
    svg = d3.selectAll("#clock_" + id_element + " svg");

  svgUnderlay.attr("id", "underlay_" + id_element);
  svgUnderlay.attr("class", "invert_filter");

  svgOverlay.attr("id", "overlay_" + id_element);

  var digit = svg.selectAll(".digit"),
    separator = svg.selectAll(".separator circle");

  var digitPattern = [
    [1, 0, 1, 1, 0, 1, 1, 1, 1, 1],
    [1, 0, 0, 0, 1, 1, 1, 0, 1, 1],
    [1, 1, 1, 1, 1, 0, 0, 1, 1, 1],
    [0, 0, 1, 1, 1, 1, 1, 0, 1, 1],
    [1, 0, 1, 0, 0, 0, 1, 0, 1, 0],
    [1, 1, 0, 1, 1, 1, 1, 1, 1, 1],
    [1, 0, 1, 1, 0, 1, 1, 0, 1, 1]
  ];

  (function tick() {
    var tz = timezone;
    var d = new Date();
    var dt = d.getTime();
    os = d.getTimezoneOffset();
    tz = parseInt(tz) + parseInt(os * 60);
    var t = new Date(dt + tz * 1000);

    var now = new Date(),
      hours = t.getHours(),
      minutes = t.getMinutes(),
      seconds = t.getSeconds();

    digit = digit.data([
      (hours / 10) | 0,
      hours % 10,
      (minutes / 10) | 0,
      minutes % 10,
      (seconds / 10) | 0,
      seconds % 10
    ]);
    digit.select("path:nth-child(1)").classed("lit", function(d) {
      return digitPattern[0][d];
    });
    digit.select("path:nth-child(2)").classed("lit", function(d) {
      return digitPattern[1][d];
    });
    digit.select("path:nth-child(3)").classed("lit", function(d) {
      return digitPattern[2][d];
    });
    digit.select("path:nth-child(4)").classed("lit", function(d) {
      return digitPattern[3][d];
    });
    digit.select("path:nth-child(5)").classed("lit", function(d) {
      return digitPattern[4][d];
    });
    digit.select("path:nth-child(6)").classed("lit", function(d) {
      return digitPattern[5][d];
    });
    digit.select("path:nth-child(7)").classed("lit", function(d) {
      return digitPattern[6][d];
    });
    separator.classed("lit", 1);

    setTimeout(tick, 1000 - (now % 1000));
  })();
}

function valueToBytes(value) {
  var shorts = ["", "K", "M", "G", "T", "P", "E", "Z", "Y"];
  var pos = 0;
  while (value >= 1024) {
    // As long as the number can be divided by divider.
    pos++;
    // Position in array starting with 0.
    value = value / 1024;
  }

  // This will actually do the rounding and the decimals.
  return value.toFixed(2) + shorts[pos] + "B";
}
