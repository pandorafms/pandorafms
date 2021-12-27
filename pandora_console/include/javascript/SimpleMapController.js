// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/* globals d3 */
/* globals jQuery */
/* globals $ */

/*-----------------------------------------------*/
/*-------------------Constants-------------------*/
/*-----------------------------------------------*/
var MAX_ZOOM_LEVEL = 5;

/*-----------------------------------------------*/
/*------------------Constructor------------------*/
/*-----------------------------------------------*/
var SimpleMapController = function(params) {
  if (!params) {
    console.log("[SimpleMapController]: No params received");
    return this;
  }
  this._target = params["target"];

  if (typeof params["map_width"] == "undefined") {
    this.map_width = 0;
  } else {
    this.map_width = params["map_width"];
  }

  if (typeof params["map_height"] == "undefined") {
    this.map_height = 0;
  } else {
    this.map_height = params["map_height"];
  }

  if (typeof params["font_size"] == "undefined") {
    this.font_size = 20;
  } else {
    this.font_size = params["font_size"];
  }

  if (typeof params["homedir"] == "undefined") {
    this.homedir = "";
  } else {
    this.homedir = params["homedir"];
  }

  if (typeof params["custom_params"] == "undefined") {
    this.custom_params = "";
  } else {
    this.custom_params = params["custom_params"];
  }

  if (typeof params["center_x"] == "undefined") {
    this.center_x = 0;
  } else {
    this.center_x = params["center_x"];
  }

  if (typeof params["center_y"] == "undefined") {
    this.center_y = 0;
  } else {
    this.center_y = params["center_y"];
  }

  if (typeof params["z_dash"] == "undefined") {
    this.z_dash = 0.5;
  } else {
    this.z_dash = params["z_dash"];
  }

  if (typeof params["nodes"] == "undefined") {
    this.nodes = [];
  } else {
    this.nodes = params["nodes"];
  }

  if (typeof params["arrows"] == "undefined") {
    this.arrows = [];
  } else {
    this.arrows = params["arrows"];
  }

  var factor = $(this._target).width() / $(this._target).height();

  // Center is about complete SVG map not only central node.
  // Calculus is to leave same space on left-right (width)
  // and top-bottom (height).
  this.center_x = ($(this._target).width() - this.map_width * factor) / 2;
  this.center_y = ($(this._target).height() - this.map_height * factor) / 2;
};

/*-----------------------------------------------*/
/*------------------Atributes--------------------*/
/*-----------------------------------------------*/
SimpleMapController.prototype._viewport = null;
SimpleMapController.prototype._zoomManager = null;

/*-----------------------------------------------*/
/*--------------------Methods--------------------*/
/*-----------------------------------------------*/
/**
 * Function init_trans_map
 * Return void
 * This function init the transactional map
 */
SimpleMapController.prototype.init_map = function() {
  var self = this;

  var svg = d3.select(self._target + " svg");

  self._zoomManager = d3.behavior
    .zoom()
    .scale(self.z_dash)
    .scaleExtent([1 / MAX_ZOOM_LEVEL, MAX_ZOOM_LEVEL])
    .on("zoom", zoom);

  self._viewport = svg
    .call(self._zoomManager)
    .append("g")
    .attr("class", "viewport")
    .attr("transform", "translate(0, 0) scale(" + self.z_dash + ")");

  self._slider = d3
    .select(self._target + " .zoom_controller .vertical_range")
    .property("value", 0)
    .property("min", -Math.log(MAX_ZOOM_LEVEL))
    .property("max", Math.log(MAX_ZOOM_LEVEL))
    .property("step", (Math.log(MAX_ZOOM_LEVEL) * 2) / MAX_ZOOM_LEVEL)
    .on("input", slided);

  /**
   * Function zoom
   * Return void
   * This function manages the zoom
   */
  function zoom() {
    self.last_event = "zoom";

    var zoom_level = d3.event.scale;

    self._slider.property("value", Math.log(zoom_level));

    self._viewport.attr(
      "transform",
      "translate(" + d3.event.translate + ") scale(" + zoom_level + ")"
    );
  }

  /**
   * Function slided
   * Return void
   * This function manages the slide (zoom system)
   */
  function slided() {
    var slider_value = parseFloat(self._slider.property("value"));

    var zoom_level = Math.exp(slider_value);

    /*----------------------------------------------------------------*/
    /*-Code to translate the map with the zoom for to hold the center-*/
    /*----------------------------------------------------------------*/
    var center = [
      parseFloat(d3.select(self._target).style("width")) / 2,
      parseFloat(d3.select(self._target).style("height")) / 2
    ];

    var old_translate = self._zoomManager.translate();
    var old_scale = self._zoomManager.scale();

    var temp1 = [
      (center[0] - old_translate[0]) / old_scale,
      (center[1] - old_translate[1]) / old_scale
    ];

    var temp2 = [
      temp1[0] * zoom_level + old_translate[0],
      temp1[1] * zoom_level + old_translate[1]
    ];

    var new_translation = [
      old_translate[0] + center[0] - temp2[0],
      old_translate[1] + center[1] - temp2[1]
    ];

    self._zoomManager
      .scale(zoom_level)
      .translate(new_translation)
      .event(self._viewport);
  }

  self.paint_arrows();
  self.paint_nodes();
};

SimpleMapController.prototype.paint_nodes = function() {
  var self = this;
  if (self.nodes != null) {
    // Initialize objects.
    var circle_elem = self._viewport
      .selectAll(".node")
      .data(self.nodes)
      .enter()
      .append("g")
      .attr("id", function(d) {
        return "node_" + d["id"];
      })
      .attr("transform", function(d) {
        return "translate(" + d["x"] + ", " + d["y"] + ")";
      })
      .attr("class", "draggable node")
      .attr("image", function(d) {
        return d["image"];
      })
      .attr("style", function(d) {
        return (
          "fill: " + d["color"] + "; " + "stroke: " + d["stroke-color"] + ";"
        );
      })
      .attr("stroke-width", function(d) {
        return d["stroke-width"];
      })
      .style("cursor", function(d) {
        if (d["id"] === "0") {
          return "default";
        } else {
          return "pointer";
        }
      });

    // Node size in map.
    circle_elem
      .append("circle")
      .attr("cx", self.center_x)
      .attr("cy", function(d) {
        return self.center_y + d["radius"];
      })
      .attr("r", function(d) {
        return d["radius"];
      });

    circle_elem.each(function(node, index) {
      if (Array.isArray(node["label"])) {
        node["label"].forEach(function(value, index2) {
          d3.selectAll("#node_" + index)
            .append("text")
            .attr("dx", function(d) {
              if (typeof d["label_x_offset"] == "undefined") {
                d["label_x_offset"] = 0;
              }
              return self.center_x + d["label_x_offset"];
            })
            .attr("dy", function(d) {
              if (typeof d["font_size"] == "undefined") {
                d["font_size"] = self.font_size;
              }
              if (typeof d["label_y_offset"] == "undefined") {
                d["label_y_offset"] = d["radius"] + d["font_size"];
              }
              return (
                self.center_y +
                d["radius"] +
                d["label_y_offset"] +
                index2 * d["font_size"]
              );
            })
            .style("text-anchor", "middle")
            .style("font-size", function(d) {
              if (typeof d["font_size"] == "undefined") {
                d["font_size"] = self.font_size;
              }
              return d["font_size"] + "px";
            })
            .style("stroke-width", 0)
            .attr("fill", "black")
            .text(value);
        });
      } else {
        circle_elem
          .append("text")
          .attr("dx", function(d) {
            if (typeof d["label_x_offset"] == "undefined") {
              d["label_x_offset"] = 0;
            }
            return self.center_x + d["label_x_offset"];
          })
          .attr("dy", function(d) {
            if (typeof d["font_size"] == "undefined") {
              d["font_size"] = self.font_size;
            }
            if (typeof d["label_y_offset"] == "undefined") {
              d["label_y_offset"] = d["radius"] + d["font_size"];
            }
            return self.center_y + d["radius"] + d["label_y_offset"];
          })
          .style("text-anchor", "middle")
          .style("font-size", function(d) {
            if (typeof d["font_size"] == "undefined") {
              d["font_size"] = self.font_size;
            }
            return d["font_size"] + "px";
          })
          .style("stroke-width", 0)
          .attr("fill", "black")
          .text(function(d) {
            return d["label"];
          });
      }
    });
  }

  // Node image.
  circle_elem
    .append("svg:image")
    .attr("class", "node_image")
    .attr("xlink:href", function(d) {
      return d["image"];
    })
    .attr("x", function(d) {
      if (typeof d["size_image"] != "undefined") {
        return self.center_x - d["size_image"] / 2;
      } else {
        return self.center_x - 52 / 2;
      }
    })
    .attr("y", function(d) {
      if (typeof size_image != "undefined") {
        return self.center_y + d["radius"] - d["size_image"] / 2;
      } else {
        return self.center_y + d["radius"] - 52 / 2;
      }
    })
    .attr("width", function(d) {
      return d["image_width"];
    })
    .attr("height", function(d) {
      return d["image_height"];
    });

  // Tooltipster. This could be dynamic.
  self.nodes.forEach(function(node) {
    if (node["id_agent"] != 0) {
      $("#node_" + node["id"]).tooltipster({
        contentAsHTML: true,
        onlyOne: true,
        updateAnimation: null,
        interactive: true,
        trigger: "click",
        content: $('<img src="' + self.homedir + '/images/spinner.gif"/>'),
        functionReady: function() {
          $("#node_" + node["id"]).tooltipster("open");
          $(".tooltipster-content").css("background", "#FFF");
          $(".tooltipster-content").css("color", "#000");

          var params = self.custom_params;

          // Add data node click.
          params.node_data = node;

          params["id_agent"] = node["id_agent"];
          jQuery.ajax({
            data: params,
            dataType: "html",
            type: "POST",
            url: self.homedir + "/ajax.php",
            success: function(data) {
              $(".tooltipster-content").css("min-height", "330px");
              $(".tooltipster-content").css("max-height", "500px");
              $("#node_" + node["id"]).tooltipster("content", data);
            }
          });
        }
      });

      if (
        typeof node["type_net"] !== "undefined" &&
        node["type_net"] === "supernet"
      ) {
        var items_list = {};
        items_list["details"] = {
          name: "Show/hide subnets",
          icon: "show",
          disabled: false,
          callback: function(key, options) {
            self.nodes.forEach(function(subnode) {
              if (
                subnode.id != node["id"] &&
                subnode.id_parent != null &&
                subnode.id_parent == node["id"]
              ) {
                if ($("#node_" + subnode.id).css("display") == "none") {
                  $("#node_" + subnode.id).show();
                } else {
                  $("#node_" + subnode.id).hide();
                }
              }
            });

            self.arrows.forEach(function(arrow) {
              if (arrow.source == node["id"] || arrow.target == node["id"]) {
                if (
                  $("#arrow_" + arrow.source + "_" + arrow.target).css(
                    "display"
                  ) == "none"
                ) {
                  $("#arrow_" + arrow.source + "_" + arrow.target).show();
                } else {
                  $("#arrow_" + arrow.source + "_" + arrow.target).hide();
                }
              }
            });
          }
        };

        $.contextMenu({
          selector: "#node_" + node["id"],
          items: items_list
        });
      }
    }
  });
};

SimpleMapController.prototype.paint_arrows = function() {
  var self = this;

  if (self.arrows != null) {
    self._viewport
      .selectAll(".arrow")
      .data(self.arrows)
      .enter()
      .append("g")
      .attr("class", "arrow")
      .attr("to", function(d) {
        return d["dest"];
      })
      .attr("from", function(d) {
        return d["orig"];
      })
      .attr("style", "fill: rgb(50, 50, 128);")
      .append("line")
      .attr("stroke", "#373737")
      .attr("stroke-width", 3)
      .attr("x1", function(d) {
        return self.center_x + self.getFirstPoint(d["orig"], "x");
      })
      .attr("y1", function(d) {
        return self.center_y + self.getFirstPoint(d["orig"], "y");
      })
      .attr("x2", function(d) {
        return self.center_x + self.getSecondPoint(d["dest"], "x");
      })
      .attr("y2", function(d) {
        return self.center_y + self.getSecondPoint(d["dest"], "y");
      })
      .attr("id", function(d) {
        return "arrow_" + d["source"] + "_" + d["target"];
      });
  }
};

SimpleMapController.prototype.getFirstPoint = function(orig, coord) {
  var self = this;
  var point = 0;

  self.nodes.forEach(function(node) {
    if (node["id"] === orig) {
      if (coord == "x") {
        point = parseFloat(node["x"]);
        return;
      } else {
        point = parseFloat(node["y"]) + node["radius"];
        return;
      }
    }
  });

  return point;
};

SimpleMapController.prototype.getSecondPoint = function(dest, coord) {
  var self = this;
  var point = 0;

  self.nodes.forEach(function(node) {
    if (node["id"] === dest) {
      if (coord == "x") {
        point = parseFloat(node["x"]);
        return;
      } else {
        point = parseFloat(node["y"]) + node["radius"];
        return;
      }
    }
  });

  return point;
};
