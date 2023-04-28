/* global jQuery */
/* global $ */
/* global context_minimap */
/* global minimap_w */
/* global minimap_h */
/* global minimap_relation */
/* global graph */
/* global translation */
/* global scale */
/* global width_svg */
/* global height_svg */
/* global networkmap_dimensions */
/* global node_radius */
/* global holding_area_dimensions */
/* global networkmap_id */
/* global enterprise_installed */
/* global networkmap_write */
/* global force */
/* global layer_graph_nodes */
/* global layer_graph_links */
/* global ellipsize */
/* global d3 */
/* global dialog_node_edit_title */
/* global click_menu_position_svg */
/* global show_labels:true */
/* global show_minimap:true */
/* global layer_graph */
/* global zoom_obj */
/* global disabled_drag_zoom */
/* global scale:true */
/* global link */
/* global siblingCount:true */
/* global xRotation:true */
/* global largeArc:true */
/* global node */

/* exported delete_link */
/* exported update_fictional_node */
/* exported update_node_name */
/* exported change_shape */
/* exported add_agent_node_from_the_filter_group */
/* exported hide_labels */
/* exported toggle_minimap */
/* exported over_node */

function draw_minimap() {
  // Clean the canvas.
  context_minimap.clearRect(0, 0, minimap_w, minimap_h);

  context_minimap.beginPath();
  context_minimap.globalAlpha = 0.8;
  context_minimap.fillStyle = "#ddd";
  context_minimap.fillRect(0, 0, minimap_w, minimap_h);

  var relation_min_nodes = minimap_relation;
  var relation_minimap_w = 2;
  var relation_minimap_h = 2;
  if (graph.nodes.length > 100 && graph.nodes.length < 500) {
    relation_min_nodes = 0.01;
    relation_minimap_w = (graph.nodes.length / 100) * 2.5;
    relation_minimap_h = 1.5;
  } else if (graph.nodes.length >= 500 && graph.nodes.length < 1000) {
    if (typeof method != "undefined" && method == 4) {
      relation_min_nodes = 0.002;
      relation_minimap_w = (graph.nodes.length / 500) * 2.5;
      relation_minimap_h = 3;
    } else {
      relation_min_nodes = 0.008;
      relation_minimap_w = (graph.nodes.length / 500) * 2.5;
      relation_minimap_h = 3;
    }
  } else if (graph.nodes.length >= 1000) {
    if (typeof method != "undefined" && method == 4) {
      relation_min_nodes = 0.001;
      relation_minimap_w = (graph.nodes.length / 1000) * 4.5;
      relation_minimap_h = 4;
    } else {
      relation_min_nodes = 0.0015;
      relation_minimap_w = (graph.nodes.length / 1000) * 3.5;
      relation_minimap_h = 3.5;
    }
  }

  //Draw the items and lines
  jQuery.each(graph.nodes, function(key, value) {
    if (typeof value == "undefined") return;

    var center_orig_x;
    var center_orig_y;

    context_minimap.beginPath();
    //Paint the item
    if (graph.nodes.length > 100) {
      center_orig_x =
        (value.x + value.image_width / 4) * relation_min_nodes +
        minimap_w / relation_minimap_w;
      center_orig_y =
        (value.y + value.image_height / 4) * relation_min_nodes +
        minimap_h / relation_minimap_h;
    } else {
      center_orig_x = (value.x + value.image_width / 2) * relation_min_nodes;
      center_orig_y = (value.y + value.image_height / 2) * relation_min_nodes;
    }

    context_minimap.arc(center_orig_x, center_orig_y, 2, 0, Math.PI * 2, false);
    //Check if the pandora point
    if (value.type == 2) {
      context_minimap.fillStyle = "#364D1F";
    } else {
      context_minimap.fillStyle = "#000";
    }
    context_minimap.fill();
  });

  if (graph.nodes.length > 100) {
    //Draw the rect of viewport
    context_minimap.beginPath();
    context_minimap.strokeStyle = "#3f3f3f";
    context_minimap.strokeRect(
      (-translation[0] / scale) * relation_min_nodes +
        minimap_w / relation_minimap_w,
      (-translation[1] / scale) * relation_min_nodes +
        minimap_h / relation_minimap_h,
      (width_svg * relation_min_nodes) / scale,
      (height_svg * relation_min_nodes) / scale
    );
  } else {
    //Draw the rect of viewport
    context_minimap.beginPath();
    context_minimap.strokeStyle = "#f00";
    context_minimap.strokeRect(
      (-translation[0] / scale) * relation_min_nodes,
      (-translation[1] / scale) * relation_min_nodes,
      (width_svg * relation_min_nodes) / scale,
      (height_svg * relation_min_nodes) / scale
    );
  }

  context_minimap.beginPath();
  context_minimap.strokeStyle = "#82B92E";
  context_minimap.strokeRect(
    (networkmap_dimensions[0] + node_radius - holding_area_dimensions[0]) *
      minimap_relation,
    (networkmap_dimensions[1] + node_radius - holding_area_dimensions[1]) *
      minimap_relation,
    holding_area_dimensions[0] * minimap_relation,
    holding_area_dimensions[1] * minimap_relation
  );

  context_minimap.globalAlpha = 1;
}

function inner_minimap_box(param_x, param_y) {
  if (
    param_x + translation[0] * minimap_relation >= 0 &&
    param_x + translation[0] * minimap_relation <=
      width_svg * minimap_relation &&
    param_y + translation[1] * minimap_relation >= 0 &&
    param_y + translation[1] * minimap_relation <= height_svg * minimap_relation
  ) {
    return true;
  }

  return false;
}

function set_center(id) {
  var pos_x = width_svg / 2 - translation[0] / scale;
  var pos_y = height_svg / 2 - translation[1] / scale;
  var params = [];

  params.push("set_center=1");
  params.push("id=" + id);
  params.push("x=" + pos_x);
  params.push("y=" + pos_y);
  params.push("scale=" + scale);
  params.push("page=operation/agentes/pandora_networkmap.view");
  jQuery.ajax({
    data: params.join("&"),
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php"
  });
}

function get_relations(node_param) {
  var return_links = [];
  var links_id_db = [];

  jQuery.each(graph.links, function(i, link_each) {
    if (
      node_param.id == link_each.source.id ||
      node_param.id == link_each.target.id
    ) {
      if (links_id_db.length > 0) {
        if (links_id_db.indexOf(link_each.id_db) == -1) {
          return_links.push(link_each);
          links_id_db.push(link_each.id_db);
        }
      } else {
        return_links.push(link_each);
        links_id_db.push(link_each.id_db);
      }
    }
  });

  return return_links;
}

function delete_link(
  source_id,
  source_module_id,
  target_id,
  target_module_id,
  id_link,
  table_row = null
) {
  var params = [];
  params.push("delete_link=1");
  params.push("networkmap_id=" + networkmap_id);
  params.push("source_id=" + source_id);
  params.push("source_module_id=" + source_module_id);
  params.push("target_id=" + target_id);
  params.push("target_module_id=" + target_module_id);
  params.push("id_link=" + id_link);
  params.push("page=operation/agentes/pandora_networkmap.view");
  jQuery.ajax({
    data: params.join("&"),
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      if (data["correct"]) {
        var found = -1;
        jQuery.each(graph.links, function(i, element) {
          if (element.id_db == id_link) {
            found = i;
          }
        });
        if (found != -1) {
          graph.links.splice(found, 1);
        }

        $("#layer_graph_links_" + networkmap_id).remove();
        $("#layer_graph_nodes_" + networkmap_id).remove();

        window.layer_graph_links = window.layer_graph
          .append("g")
          .attr("id", "layer_graph_links_" + networkmap_id);
        window.layer_graph_nodes = window.layer_graph
          .append("g")
          .attr("id", "layer_graph_nodes_" + networkmap_id);

        force
          .nodes(graph.nodes)
          .links(graph.links)
          .start();

        window.node = layer_graph_nodes.selectAll(".node");
        window.link = layer_graph_links.selectAll(".link");

        draw_elements_graph();
        init_drag_and_drop();
        set_positions_graph();

        if (typeof table_row !== "undefined" && table_row !== null) {
          $(`#relations_table-template_row_${table_row}`).animate(
            { backgroundColor: "#e6e6e6" },
            500,
            function() {
              $(`#relations_table-template_row_${table_row}`).remove();
              const rowCount = $(".relation_link_row").length;
              if (rowCount === 0) {
                $("#relations_table-no_relations").show();
                $(`#update_relations_button`).remove();
              }
            }
          );
        }
      }
    }
  });
}

function update_fictional_node(id_db_node) {
  var name = $("input[name='edit_name_fictional_node']").val();
  var networkmap_to_link = $("#edit_networkmap_to_link").val();

  var params = [];
  params.push("update_fictional_node=1");
  params.push("networkmap_id=" + networkmap_id);
  params.push("node_id=" + id_db_node);
  params.push("name=" + name);
  params.push("networkmap_to_link=" + networkmap_to_link);
  params.push("page=operation/agentes/pandora_networkmap.view");

  jQuery.ajax({
    data: params.join("&"),
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      if (data["correct"]) {
        $("#dialog_node_edit").dialog("close");

        jQuery.each(graph.nodes, function(i, element) {
          if (element.id_db == id_db_node) {
            graph.nodes[i].text = name;
            graph.nodes[i].networkmap_id = networkmap_to_link;

            $("#id_node_" + i + networkmap_id + " title").html(name);
            $("#id_node_" + i + networkmap_id + " tspan").html(
              ellipsize(name, 30)
            );
          }
        });

        draw_elements_graph();
        set_positions_graph();
      }
    }
  });
}

function update_node_name(id_db_node) {
  var name = $("input[name='edit_name_node']").val();

  var params = [];
  params.push("update_node_name=1");
  params.push("networkmap_id=" + networkmap_id);
  params.push("node_id=" + id_db_node);
  params.push("name=" + name);
  params.push("page=operation/agentes/pandora_networkmap.view");

  jQuery.ajax({
    data: params.join("&"),
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      if (data["correct"]) {
        $("#dialog_node_edit").dialog("close");

        jQuery.each(graph.nodes, function(i, element) {
          if (element.id_db == id_db_node) {
            graph.nodes[i]["text"] = data["text"];
            graph.nodes[i]["raw_text"] = data["raw_text"];

            $("#id_node_" + i + networkmap_id + " title").html(
              data["raw_text"]
            );
            $("#id_node_" + i + networkmap_id + " tspan").html(
              ellipsize(data["raw_text"], 30)
            );
          }
        });

        draw_elements_graph();
        set_positions_graph();
      }
    }
  });
}

function change_shape(id_db_node) {
  var shape = $("select[name='shape']").val();

  var params = [];
  params.push("change_shape=1");
  params.push("networkmap_id=" + networkmap_id);
  params.push("id=" + id_db_node);
  params.push("shape=" + shape);
  params.push("page=operation/agentes/pandora_networkmap.view");

  $("#shape_icon_correct").css("display", "none");
  $("#shape_icon_fail").css("display", "none");
  $("#shape_icon_in_progress").css("display", "");

  jQuery.ajax({
    data: params.join("&"),
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      $("#shape_icon_in_progress").css("display", "none");
      if (data["correct"]) {
        $("#shape_icon_correct").css("display", "");

        var count = graph.nodes.length;

        jQuery.each(graph.nodes, function(i, element) {
          if (element.id_db == id_db_node) {
            graph.nodes[i].shape = shape;

            $("#id_node_" + element.id + networkmap_id + " rect").remove();
            $("#id_node_" + element.id + networkmap_id + " circle").remove();
            $("#id_node_" + element.id + networkmap_id + " image").remove();

            if (shape == "circle") {
              d3.select("#id_node_" + element.id + networkmap_id)
                .insert("circle", "title")
                .attr("r", node_radius)
                .attr("class", "node_shape node_shape_circle")
                .style("fill", function(d) {
                  return d.color;
                })
                .classed("dragable_node", true) //own dragable
                .on("mouseover", function(d) {
                  myMouseoverCircleFunction(d.id);
                })
                .on("mouseout", function(d) {
                  myMouseoutCircleFunction(d.id);
                })
                .on("click", selected_node)
                .on("dblclick", function(d) {
                  edit_node(d, true);
                })
                .on("contextmenu", function(d) {
                  show_menu("node", d);
                });

              d3.select("#id_node_" + element.id + networkmap_id)
                .append("image")
                .attr("class", "node_image")
                .attr("xlink:href", function(d) {
                  return d.image_url;
                })
                .attr("x", function(d) {
                  return d.x - d.image_width / 2;
                })
                .attr("y", function(d) {
                  return d.y - d.image_height / 2;
                })
                .attr("width", function(d) {
                  return node_radius / 0.8;
                })
                .attr("height", function(d) {
                  return node_radius / 0.8;
                })
                .attr("node_id", function(d) {
                  return d.id;
                })
                .attr("id", "image2995")
                .classed("dragable_node", true) //own dragable
                .on("mouseover", function(d) {
                  myMouseoverCircleFunction(d.id);
                })
                .on("mouseout", function(d) {
                  myMouseoutCircleFunction(d.id);
                })
                .on("click", selected_node)
                .on("dblclick", function(d) {
                  edit_node(d, true);
                })
                .on("contextmenu", function(d) {
                  show_menu("node", d);
                });
            } else if (shape == "square") {
              d3.select("#id_node_" + element.id + networkmap_id)
                .insert("rect", "title")
                .attr("width", node_radius * 2)
                .attr("height", node_radius * 2)
                .attr("class", "node_shape node_shape_square")
                .style("fill", function(d) {
                  return d.color;
                })
                .classed("dragable_node", true) //own dragable
                .on("mouseover", function(d) {
                  myMouseoverSquareFunction(d.id);
                })
                .on("mouseout", function(d) {
                  myMouseoutSquareFunction(d.id);
                })
                .on("click", selected_node)
                .on("dblclick", function(d) {
                  edit_node(d, true);
                })
                .on("contextmenu", function(d) {
                  show_menu("node", d);
                });

              d3.select("#id_node_" + element.id + networkmap_id)
                .append("image")
                .attr("class", "node_image")
                .attr("xlink:href", function(d) {
                  return d.image_url;
                })
                .attr("x", function(d) {
                  return d.x - d.image_width / 2;
                })
                .attr("y", function(d) {
                  return d.y - d.image_height / 2;
                })
                .attr("width", function(d) {
                  return node_radius / 0.8;
                })
                .attr("height", function(d) {
                  return node_radius / 0.8;
                })
                .attr("node_id", function(d) {
                  return d.id;
                })
                .attr("id", "image2995")
                .classed("dragable_node", true) //own dragable
                .on("mouseover", function(d) {
                  myMouseoverSquareFunction(d.id);
                })
                .on("mouseout", function(d) {
                  myMouseoutSquareFunction(d.id);
                })
                .on("click", selected_node)
                .on("dblclick", function(d) {
                  edit_node(d, true);
                })
                .on("contextmenu", function(d) {
                  show_menu("node", d);
                });
            } else if (shape == "rhombus") {
              d3.select("#id_node_" + element.id + networkmap_id)
                .insert("rect", "title")
                .attr("transform", "")
                .attr("width", node_radius * 1.5)
                .attr("height", node_radius * 1.5)
                .attr("class", "node_shape node_shape_rhombus")
                .style("fill", function(d) {
                  return d.color;
                })
                .classed("dragable_node", true) //own dragable
                .on("mouseover", function(d) {
                  myMouseoverRhombusFunction(d.id);
                })
                .on("mouseout", function(d) {
                  myMouseoutRhombusFunction(d.id);
                })
                .on("click", selected_node)
                .on("dblclick", function(d) {
                  edit_node(d, true);
                })
                .on("contextmenu", function(d) {
                  show_menu("node", d);
                });

              d3.select("#id_node_" + element.id + networkmap_id)
                .append("image")
                .attr("class", "node_image")
                .attr("xlink:href", function(d) {
                  return d.image_url;
                })
                .attr("x", function(d) {
                  return d.x - d.image_width / 2;
                })
                .attr("y", function(d) {
                  return d.y - d.image_height / 2;
                })
                .attr("width", function(d) {
                  return node_radius / 0.8;
                })
                .attr("height", function(d) {
                  return node_radius / 0.8;
                })
                .attr("node_id", function(d) {
                  return d.id;
                })
                .attr("id", "image2995")
                .classed("dragable_node", true) //own dragable
                .on("mouseover", function(d) {
                  myMouseoverRhombusFunction(d.id);
                })
                .on("mouseout", function(d) {
                  myMouseoutRhombusFunction(d.id);
                })
                .on("click", selected_node)
                .on("dblclick", function(d) {
                  edit_node(d, true);
                })
                .on("contextmenu", function(d) {
                  show_menu("node", d);
                });
            }
          }

          count = count - 1;
          if (count == 0) {
            draw_elements_graph();
            set_positions_graph();
          }
        });
      } else {
        $("#shape_icon_fail").css("display", "");
      }
    }
  });
}

function update_link(row_index, id_link) {
  var interface_source = parseInt(
    $("select[name='interface_source_" + row_index + "']").val()
  );

  var text_source_interface = "";
  if (interface_source != 0) {
    text_source_interface = $(
      "select[name='interface_source_" + row_index + "'] option:selected"
    ).text();
  }

  var interface_target = parseInt(
    $("select[name='interface_target_" + row_index + "']").val()
  );

  var text_target_interface = "";
  if (interface_target != 0) {
    text_target_interface = $(
      "select[name='interface_target_" + row_index + "'] option:selected"
    ).text();
  }

  $(".edit_icon_progress_" + row_index).css("display", "");
  $(".edit_icon_" + row_index).css("display", "none");

  $(".edit_icon_" + row_index).css("display", "none");

  let result = false;

  var params = [];
  params.push("update_link=1");
  params.push("networkmap_id=" + networkmap_id);
  params.push("id_link=" + id_link);
  params.push("interface_source=" + interface_source);
  params.push("interface_target=" + interface_target);
  params.push("source_text=" + text_source_interface);
  params.push("target_text=" + text_target_interface);
  params.push("page=operation/agentes/pandora_networkmap.view");

  jQuery.ajax({
    data: params.join("&"),
    dataType: "json",
    type: "POST",
    async: false,
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      $(".edit_icon_progress_" + row_index).css("display", "none");

      if (data["correct"]) {
        $(".edit_icon_correct_" + row_index).css("display", "");

        $(
          "select[name='interface_source_" +
            row_index +
            "'] option[value='" +
            interface_source +
            "']"
        ).prop("selected", true);
        $(
          "select[name='interface_target_" +
            row_index +
            "'] option[value='" +
            interface_target +
            "']"
        ).prop("selected", true);

        var index = -1;
        $.each(graph.links, function(j, link) {
          if (link["id_db"] == id_link) {
            index = j;
          }
        });

        delete_link_from_id(index);

        var temp_link = {};
        temp_link["status_start"] = "0";
        temp_link["status_end"] = "0";

        temp_link["id_db"] = String(data["id_db_link"]);

        if (data["type_source"] == 1) {
          temp_link["arrow_start"] = "module";
          temp_link["id_module_start"] = interface_source;
          temp_link["status_start"] = data["status"];
          temp_link["link_color"] = data["status"] == "1" ? "#e63c52" : "#999";
        } else {
          temp_link["arrow_start"] = "";
          temp_link["id_agent_start"] = interface_source;
          temp_link["id_module_start"] = 0;
        }
        if (data["type_target"] == 1) {
          temp_link["arrow_end"] = "module";
          temp_link["id_module_end"] = interface_target;
          temp_link["status_end"] = data["status"];
          temp_link["link_color"] = data["status"] == "1" ? "#e63c52" : "#999";
        } else {
          temp_link["arrow_end"] = "";
          temp_link["id_agent_end"] = interface_target;
          temp_link["id_module_end"] = 0;
        }

        temp_link["text_start"] = data["text_start"];
        temp_link["text_end"] = data["text_end"];

        $.each(graph.nodes, function(k, node) {
          if (node["id_db"] == data["id_db_target"]) {
            temp_link["target"] = graph.nodes[k];
          }
          if (node["id_db"] == data["id_db_source"]) {
            temp_link["source"] = graph.nodes[k];
          }
        });

        add_new_link(temp_link);

        $("#layer_graph_links_" + networkmap_id).remove();
        $("#layer_graph_nodes_" + networkmap_id).remove();

        window.layer_graph_links = window.layer_graph
          .append("g")
          .attr("id", "layer_graph_links_" + networkmap_id);
        window.layer_graph_nodes = window.layer_graph
          .append("g")
          .attr("id", "layer_graph_nodes_" + networkmap_id);

        var graph_links_aux = graph.links.filter(function(d, i) {
          if (typeof d["source"] === "undefined") {
            return false;
          }

          if (typeof d["target"] === "undefined") {
            return false;
          }

          return d;
        });

        force
          .nodes(graph.nodes)
          .links(graph_links_aux)
          .start();

        window.node = layer_graph_nodes.selectAll(".node");
        window.link = layer_graph_links.selectAll(".link");

        draw_elements_graph();
        init_drag_and_drop();
        set_positions_graph();
        result = data["id_db_link"];
      } else {
        $(".edit_icon_fail_" + row_index).css("display", "");
      }
    }
  });

  return result;
}

function delete_link_from_id(index) {
  graph.links.splice(index, 1);
}

function add_new_link(new_link) {
  graph.links.push(new_link);
}

function move_to_networkmap(node) {
  // Checks if is widget or not
  var widget = false;
  widget = $("#hidden-widget").val();

  if (widget == true) {
    var id_cell = $(".widget_content").data("id_cell");
    move_to_networkmap_widget(node.networkmap_id, id_cell);
  } else {
    var params = [];
    params.push("get_networkmap_from_fictional=1");
    params.push("id=" + node.id_db);
    params.push("id_map=" + node.map_id);
    params.push("page=operation/agentes/pandora_networkmap.view");

    jQuery.ajax({
      data: params.join("&"),
      dataType: "json",
      type: "POST",
      url: window.base_url_homedir + "/ajax.php",
      success: function(data) {
        if (data["correct"]) {
          window.location =
            "index.php?sec=network&sec2=operation/agentes/pandora_networkmap&tab=view&id_networkmap=" +
            data["id_networkmap"];
        } else {
          edit_node(node, true);
        }
      }
    });
  }
}

function edit_node(data_node, dblClick) {
  var flag_edit_node = true;
  var edit_node = null;

  //Only select one node
  var selection = d3.selectAll(".node_selected");
  var id;

  if (selection[0].length == 1) {
    edit_node = selection[0].pop();
  } else if (selection[0].length > 1) {
    edit_node = selection[0].pop();
  } else if (dblClick) {
    edit_node = d3.select("#id_node_" + data_node["id"] + networkmap_id);
    edit_node = edit_node[0][0];
  } else {
    flag_edit_node = false;
  }

  if (flag_edit_node) {
    d3.selectAll(".node_selected").classed("node_selected", false);
    d3.select(edit_node).classed("node_selected", true);

    id = d3
      .select(edit_node)
      .attr("id")
      .replace("id_node_", "");
    var id_networkmap_lenght = networkmap_id.toString().length;
    var id_node_length = id.length - id_networkmap_lenght;
    id = id.substring(0, id_node_length);
    var index_node = $.inArray(data_node, graph.nodes);
    var node_selected = graph.nodes[index_node];
    var selected_links = get_relations(node_selected);

    $("select[name='shape'] option[value='" + node_selected.shape + "']").prop(
      "selected",
      true
    );
    $("select[name='shape']").attr(
      "onchange",
      "javascript: change_shape(" + node_selected.id_db + ");"
    );
    $("#node_options-fictional_node_update_button-1 input").attr(
      "onclick",
      "update_fictional_node(" + node_selected.id_db + ");"
    );
    $("#button-upd_fictional_node").attr(
      "onclick",
      "update_fictional_node(" + node_selected.id_db + ");"
    );

    $("#button-upd_only_node").attr(
      "onclick",
      "update_node_name(" + node_selected.id_db + ");"
    );

    if (node_selected.type === "3") {
      $("#node_details-0-0").html("Link to map");
      if (node_selected.networkmap_id > 0) {
        $("#node_details-0-1 div").html(
          '<a href="index.php?sec=network&sec2=operation/agentes/pandora_networkmap&tab=view&id_networkmap=' +
            node_selected.networkmap_id +
            '">' +
            $(
              `#edit_networkmap_to_link option[value='${node_selected.networkmap_id}']`
            ).text() +
            "</a>"
        );
      } else {
        $("#node_details-0-1 div").html(
          $(
            `#edit_networkmap_to_link option[value='${node_selected.networkmap_id}']`
          ).text()
        );
      }

      $("#node_details-1").hide();
      $("#node_details-2").hide();
      $("#node_details-3").hide();
    } else {
      //$("#node_details-0-0").html("Agent");
      $("#node_details-1").show();
      $("#node_details-2").show();
      $("#node_details-3").show();

      var params = [];
      params.push("get_agent_info=1");
      params.push("id_agent=" + node_selected["id_agent"]);
      params.push("page=operation/agentes/pandora_networkmap.view");

      jQuery.ajax({
        data: params.join("&"),
        dataType: "json",
        type: "POST",
        url: window.base_url_homedir + "/ajax.php",
        success: function(data) {
          $("#content_node_details-0-1").html(
            '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' +
              node_selected["id_agent"] +
              '">' +
              data["alias"] +
              "</a>"
          );

          var addresses = "";
          if (data["adressess"] instanceof Array) {
            for (var i; i < data["adressess"].length; i++) {
              addresses += data["adressess"][i] + "<br>";
            }
          } else {
            for (var address in data["adressess"]) {
              addresses += address + "<br>";
            }
          }
          $("#content_node_details-1-1").html(addresses);
          $("#content_node_details-2-1").html(data["os"]);
          $("#content_node_details-3-1").html(data["group"]);

          $("[aria-describedby=dialog_node_edit]").css({ top: "200px" });
          $("#foot").css({
            top: parseInt(
              $("[aria-describedby=dialog_node_edit]").css("height") +
                $("[aria-describedby=dialog_node_edit]").css("top")
            ),
            position: "relative"
          });

          get_interface_data_to_table(node_selected, selected_links);
        }
      });
    }

    $("#dialog_node_edit").dialog(
      "option",
      "title",
      dialog_node_edit_title.replace("%s", ellipsize(node_selected["text"], 40))
    ); // It doesn't eval the possible XSS so it's ok
    $("#dialog_node_edit").dialog("open");
    $("#open_version_dialog").dialog();
    $("#open_version").dialog();

    if (node_selected.id_agent == undefined || node_selected.type == 3) {
      //Fictional node
      $("#node_options-fictional_node_name").css("display", "");
      $("input[name='edit_name_fictional_node']").val(node_selected.text); // It doesn't eval the possible XSS so it's ok
      $("#node_options-fictional_node_networkmap_link-0").css("display", "");
      $("#edit_networkmap_to_link").val(node_selected.networkmap_id);
      $("#edit_networkmap_to_link").trigger("change");
      $("#button-upd_fictional_node").css("display", "");
      $("#node_options-node_name").css("display", "none");
      $("#button-upd_only_node").css("display", "none");
    } else {
      $("input[name='edit_name_node']").val(node_selected.text); // It doesn't eval the possible XSS so it's ok
      $("#node_options-fictional_node_name").css("display", "none");
      $("#node_options-fictional_node_networkmap_link-0").css(
        "display",
        "none"
      );
      $("#node_options-node_name").css("display", "");
      $("#button-upd_fictional_node").css("display", "none");
      $("#button-upd_only_node").css("display", "");
    }

    //Clean
    $("#relations_table .relation_link_row").remove();
    //Show the no relations
    $("#relations_table-loading").css("display", "none");
    $("#relations_table-no_relations").css("display", "");
  }
}

function get_interface_data_to_table(node_selected, selected_links) {
  $("#interface_information")
    .find("tr:gt(0)")
    .remove();

  var params = [];
  params.push("get_interface_info=1");
  params.push("id_agent=" + node_selected["id_agent"]);
  params.push("page=operation/agentes/pandora_networkmap.view");
  jQuery.ajax({
    data: params.join("&"),
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      if (data.length == 0) {
        $("#interface_information")
          .find("tbody")
          .append(
            $("<tr>").html(
              '<p style="text-align: center;">It has no interface to display</p>'
            )
          );
      } else {
        jQuery.each(data, function(j, interface) {
          var interf_graph;
          if (interface["graph"] == "") {
            interf_graph = "--";
          } else {
            interf_graph = interface["graph"];
          }
          $("#interface_information")
            .find("tbody")
            .append(
              $("<tr>")
                .append($("<td>").html(interface["name"]))
                .append($("<td>").html(interface["status"]))
                .append($("<td>").html(interf_graph))
                .append($("<td>").html(interface["ip"]))
                .append($("<td>").html(interface["mac"]))
            );
        });
      }
      load_interfaces(selected_links);
    }
  });
}

function load_interfaces(selected_links) {
  //Clean
  $("#relations_table .relation_link_row").remove();
  $("#update_relations_button").remove();
  //Show the no relations
  $("#relations_table-loading").css("display", "none");
  $("#relations_table-no_relations").css("display", "");

  jQuery.each(selected_links, function(i, link_each) {
    $("#relations_table-no_relations").css("display", "none");
    $("#relations_table-loading").css("display", "");

    var template_relation_row = $("#relations_table-template_row").clone();

    $(template_relation_row).css("display", "");
    $(template_relation_row).attr("class", "relation_link_row");
    $(template_relation_row).attr("id", `relations_table-template_row_${i}`);

    $("select[name='interface_source']", template_relation_row)
      .attr("name", "interface_source_" + i)
      .attr("id", "interface_source_" + i);
    $("select[name='interface_target']", template_relation_row)
      .attr("name", "interface_target_" + i)
      .attr("id", "interface_target_" + i);
    $(".edit_icon_progress", template_relation_row).attr(
      "class",
      "edit_icon_progress_" + i
    );
    $(".edit_icon", template_relation_row).attr("class", "edit_icon_" + i);
    $(".edit_icon_correct", template_relation_row).attr(
      "class",
      "edit_icon_correct_" + i
    );
    $(".edit_icon_fail", template_relation_row).attr(
      "class",
      "edit_icon_fail_" + i
    );

    var params3 = [];
    params3.push("get_intefaces=1");
    params3.push("id_agent_target=" + link_each.target.id_agent);
    params3.push("id_agent_source=" + link_each.source.id_agent);
    params3.push("page=operation/agentes/pandora_networkmap.view");

    jQuery.ajax({
      data: params3.join("&"),
      dataType: "json",
      type: "POST",
      async: true,
      cache: false,
      url: window.base_url_homedir + "/ajax.php",
      success: function(data) {
        if (data["correct"]) {
          $(
            "select[name='interface_target_" + i + "']",
            template_relation_row
          ).empty();
          $(
            "select[name='interface_target_" + i + "']",
            template_relation_row
          ).append(
            '<option value="' + link_each.target.id_agent + '">None</option>'
          );

          $(
            "select[name='interface_source_" + i + "']",
            template_relation_row
          ).empty();
          $(
            "select[name='interface_source_" + i + "']",
            template_relation_row
          ).append(
            '<option value="' + link_each.source.id_agent + '">None</option>'
          );
          jQuery.each(data["target_interfaces"], function(j, interface) {
            $(
              "select[name='interface_target_" + i + "']",
              template_relation_row
            ).append(
              $("<option>")
                .attr("value", interface["id_agente_modulo"])
                .html(interface["nombre"])
            );

            if (interface.id_agente_modulo == link_each.id_module_end) {
              $(
                "select[name='interface_target_" +
                  i +
                  "'] option[value='" +
                  interface["id_agente_modulo"] +
                  "']",
                template_relation_row
              ).prop("selected", true);
            }
          });

          jQuery.each(data["source_interfaces"], function(j, interface) {
            $(
              "select[name='interface_source_" + i + "']",
              template_relation_row
            ).append(
              $("<option>")
                .attr("value", interface["id_agente_modulo"])
                .html(interface["nombre"])
            );

            if (interface.id_agente_modulo == link_each.id_module_start) {
              $(
                "select[name='interface_source_" +
                  i +
                  "'] option[value='" +
                  interface["id_agente_modulo"] +
                  "']",
                template_relation_row
              ).prop("selected", true);
            }
          });
          $("#relations_table-loading").css("display", "none");
        }
      }
    });

    $("#relations_table-template_row-node_source", template_relation_row).html(
      link_each.source["raw_text"] != "undefined"
        ? link_each.source["text"]
        : link_each.source["raw_text"]
    );
    $("#relations_table-template_row-node_target", template_relation_row).html(
      link_each.target["raw_text"] != "undefined"
        ? link_each.target["text"]
        : link_each.target["raw_text"]
    );
    $("#relations_table-template_row-edit", template_relation_row).attr(
      "align",
      "center"
    );
    $("#relations_table-template_row-edit", template_relation_row).css({
      display: "flex",
      "align-items": "center",
      "justify-content": "center"
    });

    $(
      "#relations_table-template_row-edit .delete_icon",
      template_relation_row
    ).attr("id", `delete_icon_${i}`);

    $(
      "#relations_table-template_row-edit .delete_icon",
      template_relation_row
    ).attr(
      "href",
      "javascript: " +
        "delete_link(" +
        link_each.source.id_db +
        "," +
        link_each.id_module_start +
        "," +
        link_each.target.id_db +
        "," +
        link_each.id_module_end +
        "," +
        link_each.id_db +
        "," +
        i +
        ");"
    );
    $("#relations_table tbody").append(template_relation_row);

    // Update input for transform in select2.
    $("#interface_source_" + i).select2();
    $("#interface_target_" + i).select2();

    template_relation_row = null;
  });

  if (selected_links.length > 0) {
    $("#relations_table")
      .parent()
      .append(
        `
        <div class='action-buttons w100p'>
        <button id="update_relations_button" type="button" class="buttonButton" value="Update relations">
          <span id="span-button-unnamed" class="font_11">
            Update relations
          </span>
          <div style="" class="subIcon next "></div>
        </button>
        `
      );

    $("#update_relations_button").click(function() {
      jQuery.each(selected_links, function(i, link_each) {
        const new_id_db = update_link(i, link_each.id_db);
        if (new_id_db !== false) {
          selected_links[i]["id_db"] = new_id_db;
          $(`#delete_icon_${i}`).attr(
            "href",
            "javascript: " +
              "delete_link(" +
              link_each.source.id_db +
              "," +
              link_each.id_module_start +
              "," +
              link_each.target.id_db +
              "," +
              link_each.id_module_end +
              "," +
              new_id_db +
              "," +
              i +
              ");"
          );
        }
      });
    });
  }
}

function add_node() {
  $("#agent_name").val("");

  $("input[name='name_fictional_node']").val("");
  $("#networkmap_to_link").val(0);

  $("#dialog_node_add").dialog("open");
}

function add_agent_node_from_the_filter_group() {
  var agents = $("select[name='agents_filter_group']").val();

  add_agent_node(agents);
}

function add_agent_node(agents) {
  var id_agents = [];

  if (typeof agents == "undefined") {
    id_agents.push($("input[name='id_agent']").val());
  } else {
    if (typeof agents == "object") {
      //Several agents
      if (agents.length == 1) {
        id_agents.push(agents.pop());
      } else if (agents.length == 0) {
        //empty list
      } else {
        id_agents = agents;
      }
    } else if (typeof agents == "string") {
      id_agents.push(agents);
    } else if (typeof agents == "number") {
      id_agents.push(agents);
    } else {
      id_agents = agents;
    }
  }
  var x = (click_menu_position_svg[0] - translation[0]) / scale;
  var y = (click_menu_position_svg[1] - translation[1]) / scale;

  jQuery.each(id_agents, function(i, id_agent) {
    x = x + i * 20;
    y = y + i * 20;

    var params = [];
    params.push("add_agent=1");
    params.push("id=" + networkmap_id);
    params.push("id_agent=" + id_agent);
    params.push("x=" + x);
    params.push("y=" + y);
    params.push("page=operation/agentes/pandora_networkmap.view");
    jQuery.ajax({
      data: params.join("&"),
      dataType: "json",
      type: "POST",
      url: window.base_url_homedir + "/ajax.php",
      success: function(data) {
        if (data["correct"]) {
          $("#agent_name").val("");
          $("#dialog_node_add").dialog("close");

          const new_id =
            Math.max.apply(
              Math,
              graph.nodes.map(function(o) {
                return o.id;
              })
            ) + 1;

          var temp_node = {};
          temp_node["id"] = new_id;
          temp_node["id_db"] = data["id_node"];
          temp_node["id_agent"] = data["id_agent"];
          temp_node["id_module"] = "";
          temp_node["px"] = data["x"];
          temp_node["py"] = data["y"];
          temp_node["x"] = data["x"];
          temp_node["y"] = data["y"];
          temp_node["z"] = 0;
          temp_node["fixed"] = true;
          temp_node["type"] = 0;
          temp_node["color"] = data["status"];
          temp_node["shape"] = data["shape"];
          temp_node["text"] = data["text"];
          temp_node["image_url"] = data["image_url"];
          temp_node["image_width"] = data["width"];
          temp_node["image_height"] = data["height"];
          temp_node["map_id"] = data["map_id"];
          temp_node["state"] = data["state"];

          graph.nodes.push(temp_node);

          draw_elements_graph();
          init_drag_and_drop();
          set_positions_graph();
        } else {
          $("#error_red").show();
          $("#error_red").attr(
            "data-title",
            "The agent is already added on the networkmap"
          );
          $("#error_red").attr("data-use_title_for_force_title", "1");
        }
      }
    });
  });
}

function hide_labels() {
  if (show_labels) {
    hide_labels_function();
  } else {
    show_labels_function();
  }
}

function hide_labels_function() {
  show_labels = false;

  //Change the image arrow
  $("#hide_labels_" + networkmap_id + " > a").attr("title", "Show Labels");
  $("#hide_labels_" + networkmap_id + " > a > img").attr(
    "src",
    window.location.origin + "/pandora_console/images/enable.svg"
  );

  d3.selectAll(".node_text").style("display", "none");
}

function show_labels_function() {
  show_labels = true;

  //Change the image arrow
  $("#hide_labels_" + networkmap_id + " > a").attr("title", "Hide Labels");
  $("#hide_labels_" + networkmap_id + " > a > img").attr(
    "src",
    window.location.origin + "/pandora_console/images/disable.svg"
  );

  d3.selectAll(".node_text").style("display", "");
}

function toggle_minimap() {
  if (show_minimap) {
    function_close_minimap();
  } else {
    function_open_minimap();
  }
}

function function_open_minimap() {
  show_minimap = true;

  //Change the image arrow
  $("#arrow_minimap_" + networkmap_id + " > a").attr("title", "Close Minimap");
  $("#image_arrow_minimap_" + networkmap_id).attr(
    "src",
    "images/minimap_close_arrow.png"
  );

  $("#minimap_" + networkmap_id).show();

  draw_minimap();
}

function function_close_minimap() {
  show_minimap = false;

  //Change the image arrow
  $("#arrow_minimap_" + networkmap_id + " > a").attr("title", "Open Minimap");
  $("#image_arrow_minimap_" + networkmap_id).attr(
    "src",
    "images/minimap_open_arrow.png"
  );

  $("#minimap_" + networkmap_id).hide();
}

function delete_nodes() {
  var selection = d3.selectAll(".node_selected");
  selection.each(function(d) {
    var params = [];
    params.push("id=" + d.id_db);
    params.push("delete_node=1");
    params.push("page=operation/agentes/pandora_networkmap.view");
    jQuery.ajax({
      data: params.join("&"),
      dataType: "json",
      type: "POST",
      url: window.base_url_homedir + "/ajax.php",
      success: function(data) {
        if (data["correct"]) {
          var found = -1;
          do {
            found = -1;
            jQuery.each(graph.links, function(i, element) {
              if (element.target.id == d.id) {
                found = i;
              }
            });
            if (found != -1) graph.links.splice(found, 1);
          } while (found != -1);

          do {
            found = -1;
            jQuery.each(graph.links, function(i, element) {
              if (element.source.id == d.id) {
                found = i;
              }
            });
            if (found != -1) graph.links.splice(found, 1);
          } while (found != -1);

          found = -1;
          jQuery.each(graph.nodes, function(i, element) {
            if (element.id == d.id) {
              found = i;
            }
          });
          graph.nodes.splice(found, 1);

          draw_elements_graph();
          set_positions_graph();
        }
      }
    });
  });
}

function zoom(manual) {
  if (typeof manual == "undefined") {
    manual = false;
  }

  if (manual) {
    layer_graph.attr(
      "transform",
      "translate(" + translation + ")scale(" + scale + ")"
    );

    zoom_obj.translate(translation);
    zoom_obj.scale(scale);

    draw_minimap();
  } else {
    if (!disabled_drag_zoom) {
      translation[0] = d3.event.translate[0];
      translation[1] = d3.event.translate[1];
      scale = d3.event.scale;

      zoom_obj.translate(translation);
      zoom_obj.scale(scale);

      layer_graph.attr(
        "transform",
        "translate(" + translation + ")scale(" + scale + ")"
      );

      draw_minimap();
    } else {
      //Keep the translation before to start to dragging
      zoom_obj.translate(translation);
    }
  }
}

function set_positions_graph() {
  link
    .selectAll("path.link")
    .attr("d", function(d) {
      if (d.arrow_end == "module" || d.arrow_start == "module") {
        return arcPath(true, d);
      } else {
        return (
          "M " +
          d.source.x +
          " " +
          d.source.y +
          " L " +
          d.target.x +
          " " +
          d.target.y
        );
      }
    })
    .style("fill", "none");

  link
    .selectAll("path.link_reverse")
    .attr("d", function(d) {
      if (d.arrow_end == "module" || d.arrow_start == "module") {
        return arcPath(false, d);
      } else {
        return (
          "M " +
          d.target.x +
          " " +
          d.target.y +
          " L " +
          d.source.x +
          " " +
          d.source.y
        );
      }
    })
    .style("fill", "none");

  function arcPath(leftHand, d) {
    var x1 = leftHand ? d.source.x : d.target.x,
      y1 = leftHand ? d.source.y : d.target.y,
      x2 = leftHand ? d.target.x : d.source.x,
      y2 = leftHand ? d.target.y : d.source.y,
      dx = x2 - x1,
      dy = y2 - y1,
      dr = Math.sqrt(dx * dx + dy * dy),
      drx = dr,
      dry = dr,
      sweep = leftHand ? 0 : 1;
    siblingCount = countSiblingLinks(d.source, d.target);
    (xRotation = 1), (largeArc = 0);

    if (siblingCount > 1) {
      var siblings = getSiblingLinks(d.source, d.target);
      var arcScale = d3.scale
        .ordinal()
        .domain(siblings)
        .rangePoints([1, siblingCount]);
      drx =
        drx /
        (1 +
          (1 / siblingCount) *
            (arcScale(d.text_start + d.id_db + networkmap_id) - 1));
      dry =
        dry /
        (1 +
          (1 / siblingCount) *
            (arcScale(d.text_start + d.id_db + networkmap_id) - 1));

      return (
        "M" +
        x1 +
        "," +
        y1 +
        "A" +
        drx +
        ", " +
        dry +
        " " +
        xRotation +
        ", " +
        largeArc +
        ", " +
        sweep +
        " " +
        x2 +
        "," +
        y2
      );
    } else {
      if (leftHand) {
        return (
          "M " +
          d.source.x +
          " " +
          d.source.y +
          " L " +
          d.target.x +
          " " +
          d.target.y
        );
      } else {
        return (
          "M " +
          d.target.x +
          " " +
          d.target.y +
          " L " +
          d.source.x +
          " " +
          d.source.y
        );
      }
    }
  }

  function countSiblingLinks(source, target) {
    var count = 0;
    for (var i = 0; i < graph.links.length; ++i) {
      if (
        (graph.links[i].source.id == source.id &&
          graph.links[i].target.id == target.id) ||
        (graph.links[i].source.id == target.id &&
          graph.links[i].target.id == source.id)
      )
        count++;
    }
    return count;
  }

  function getSiblingLinks(source, target) {
    var siblings = [];
    for (var i = 0; i < graph.links.length; ++i) {
      if (
        (graph.links[i].source.id == source.id &&
          graph.links[i].target.id == target.id) ||
        (graph.links[i].source.id == target.id &&
          graph.links[i].target.id == source.id)
      )
        siblings.push(
          graph.links[i].text_start + graph.links[i].id_db + networkmap_id
        );
    }
    return siblings;
  }

  node
    .selectAll(".node_shape_circle")
    .attr("cx", function(d) {
      return d.x;
    })
    .attr("cy", function(d) {
      return d.y;
    });

  node
    .selectAll(".node_shape_square")
    .attr("x", function(d) {
      return d.x - node_radius;
    })
    .attr("y", function(d) {
      return d.y - node_radius;
    });

  node
    .selectAll(".node_shape_rhombus")
    .attr("x", function(d) {
      return d.x - node_radius / 1.25;
    })
    .attr("y", function(d) {
      return d.y - node_radius / 1.25;
    })
    .attr("transform", function(d) {
      return "rotate(45 " + d.x + " " + d.y + ")";
    });

  node
    .selectAll(".node_image")
    .attr("x", function(d) {
      return d.x - node_radius / 0.8 / 2;
    })
    .attr("y", function(d) {
      return d.y - node_radius / 0.8 / 2;
    });

  var position_text = node_radius * 0.8;

  node
    .selectAll(".node_text")
    .attr("x", function(d) {
      return d.x;
    })
    .attr("y", function(d) {
      return d.y + node_radius + position_text;
    });

  draw_minimap();
}

function over_node(d) {
  var over = d3.select("#id_node_" + d.id + networkmap_id).classed("node_over");

  in_a_node = !in_a_node;

  d3.select("#id_node_" + d.id + networkmap_id).classed("node_over", !over);
}

function selected_node(d, selected_param, hold_other_selections) {
  if (typeof selected_param == "boolean") {
    selected = !selected_param; //because the next negate
  } else {
    selected = d3
      .select("#id_node_" + d.id + networkmap_id)
      .classed("node_selected");
  }

  if (typeof hold_other_selections != "boolean") {
    deselect_others = !flag_multiple_selection;
  } else {
    deselect_others = !hold_other_selections;
  }

  if (deselect_others) {
    d3.selectAll(".node_selected").classed("node_selected", false);
  }

  d3.select("#id_node_" + d.id + networkmap_id).classed(
    "node_selected",
    !selected
  );

  d3.event.stopPropagation();
}

function clear_selection() {
  if (!flag_multiple_selection && !in_a_node) {
    d3.selectAll(".node_selected").classed("node_selected", false);
  }
}

function update_networkmap() {
  node.each(function(d) {
    // Do not update Pandora FMS node.
    if (d.type != 2) {
      var params = [];
      params.push("update_node_color=1");
      params.push("id=" + d.id_db);
      params.push("page=operation/agentes/pandora_networkmap.view");

      jQuery.ajax({
        data: params.join("&"),
        dataType: "json",
        type: "POST",
        url: window.base_url_homedir + "/ajax.php",
        success: function(data) {
          d3.select("#id_node_" + d.id + networkmap_id + " .node_shape").style(
            "fill",
            data["color"]
          );
        }
      });
    }
  });

  link.each(function(d) {
    if (d.id_module_start != 0 || d.id_module_end != 0) {
      if (d.id_module_start && d.id_module_start > 0) {
        let params = [];
        params.push("module_get_status=1");
        params.push("page=operation/agentes/pandora_networkmap.view");
        params.push("id=" + d.id_module_start);
        jQuery.ajax({
          data: params.join("&"),
          dataType: "json",
          type: "POST",
          url: window.base_url_homedir + "/ajax.php",
          success: function(data) {
            d3.selectAll(".id_module_start_" + d.id_module_start).attr(
              "marker-start",
              function(d) {
                if (typeof module_color_status[data.status] == "undefined")
                  return "url(#interface_start)";
                else return "url(#interface_start_" + data.status + ")";
              }
            );
          }
        });
      }

      if (d.id_module_end && d.id_module_end > 0) {
        let params = [];
        params.push("module_get_status=1");
        params.push("page=operation/agentes/pandora_networkmap.view");
        params.push("id=" + d.id_module_end);
        jQuery.ajax({
          data: params.join("&"),
          dataType: "json",
          type: "POST",
          url: window.base_url_homedir + "/ajax.php",
          success: function(data) {
            d3.selectAll(".id_module_end_" + d.id_module_end).attr(
              "marker-end",
              function(d) {
                if (typeof module_color_status[data.status] == "undefined")
                  return "url(#interface_end)";
                else return "url(#interface_end_" + data.status + ")";
              }
            );
          }
        });
      }
    }
  });

  draw_minimap();
}

////////////////////////////////////////////////////////////////////////
// Minimap
////////////////////////////////////////////////////////////////////////
function init_minimap() {
  var relation_min_nodes = minimap_relation;
  var relation_minimap_w = 2;
  var relation_minimap_h = 2;

  if (graph.nodes.length > 100 && graph.nodes.length < 500) {
    relation_min_nodes = 0.01;
    relation_minimap_w = (graph.nodes.length / 100) * 2.5;
    relation_minimap_h = 1.5;
  } else if (graph.nodes.length >= 500 && graph.nodes.length < 1000) {
    if (typeof method != "undefined" && method == 4) {
      relation_min_nodes = 0.002;
      relation_minimap_w = (graph.nodes.length / 500) * 2.5;
      relation_minimap_h = 3;
    } else {
      relation_min_nodes = 0.008;
      relation_minimap_w = (graph.nodes.length / 500) * 2.5;
      relation_minimap_h = 3;
    }
  } else if (graph.nodes.length >= 1000) {
    if (typeof method != "undefined" && method == 4) {
      relation_min_nodes = 0.001;
      relation_minimap_w = (graph.nodes.length / 1000) * 4.5;
      relation_minimap_h = 4;
    } else {
      relation_min_nodes = 0.0015;
      relation_minimap_w = (graph.nodes.length / 1000) * 3.5;
      relation_minimap_h = 3.5;
    }
  }

  $("#minimap_" + networkmap_id).bind("mousemove", function(event) {
    if (graph.nodes.length > 100) {
      var x =
        event.pageX -
        $("#minimap_" + networkmap_id).offset().left -
        minimap_w / relation_minimap_w;
      var y =
        event.pageY -
        $("#minimap_" + networkmap_id).offset().top -
        minimap_h / relation_minimap_h;
    } else {
      var x = event.pageX - $("#minimap_" + networkmap_id).offset().left;
      var y = event.pageY - $("#minimap_" + networkmap_id).offset().top;
    }

    if (inner_minimap_box(x, y)) {
      document.body.style.cursor = "pointer";
    } else {
      document.body.style.cursor = "default";
    }

    if (minimap_drag) {
      translation[0] = -(x * scale) / relation_min_nodes + width_svg / 2;
      translation[1] = -(y * scale) / relation_min_nodes + height_svg / 2;

      zoom(true);

      event.stopPropagation();

      return false;
    }
  });

  $("#minimap_" + networkmap_id).mousedown(function(event) {
    minimap_drag = true;

    event.stopPropagation();
    return false;
  });

  $("#minimap_" + networkmap_id).mouseout(function(event) {
    minimap_drag = false;

    document.body.style.cursor = "default";

    event.stopPropagation();

    return false;
  });

  $("#minimap_" + networkmap_id).mouseup(function(event) {
    minimap_drag = false;

    event.stopPropagation();
    return false;
  });

  $("#minimap_" + networkmap_id).bind("contextmenu", function(event) {
    event.stopPropagation();
    return false;
  });

  $("#minimap_" + networkmap_id).click(function(event) {
    if (graph.nodes.length > 100) {
      var x =
        event.pageX -
        $("#minimap_" + networkmap_id).offset().left -
        minimap_w / relation_minimap_w;
      var y =
        event.pageY -
        $("#minimap_" + networkmap_id).offset().top -
        minimap_h / relation_minimap_h;
    } else {
      var x = event.pageX - $("#minimap_" + networkmap_id).offset().left;
      var y = event.pageY - $("#minimap_" + networkmap_id).offset().top;
    }

    translation[0] = -(x * scale) / relation_min_nodes + width_svg / 2;
    translation[1] = -(y * scale) / relation_min_nodes + height_svg / 2;

    zoom(true);

    event.stopPropagation();
    return false;
  });
}

////////////////////////////////////////////////////////////////////////
// Context menu
////////////////////////////////////////////////////////////////////////
function show_menu(item, data) {
  mouse = [];
  mouse[0] = d3.event.pageX;
  mouse[1] = d3.event.pageY;

  window.click_menu_position_svg = [d3.event.layerX, d3.event.layerY];

  //stop showing browser menu
  d3.event.preventDefault();
  d3.event.stopPropagation();

  switch (item) {
    case "node":
      selected_node(data, true, true);

      var items_list = {};
      items_list["details"] = {
        name: edit_menu,
        icon: "details",
        disabled: false,
        callback: function(key, options) {
          edit_node(data, false);
        }
      };
      items_list["interface_link"] = {
        name: interface_link_add,
        icon: "interface_link_children",
        disabled: function() {
          if (data.type == 3 || data.type == 2) {
            return true;
          } else {
            return false;
          }
        },
        callback: function(key, options) {
          var selection = d3.selectAll(".node_children");
          selection.each(function(d) {
            d3.select("#id_node_" + d.id + networkmap_id).classed(
              "node_children",
              false
            );
          });

          selection = d3.selectAll(".node_selected");
          selection.each(function(d) {
            d3.select("#id_node_" + d.id + networkmap_id)
              .classed("node_selected", false)
              .classed("node_children", true);
          });

          flag_setting_interface_link_running = true;
        }
      };
      items_list["children"] = {
        name: set_as_children_menu,
        icon: "children",
        disabled: false,
        callback: function(key, options) {
          var selection = d3.selectAll(".node_children");
          selection.each(function(d) {
            d3.select("#id_node_" + d.id + networkmap_id).classed(
              "node_children",
              false
            );
          });

          selection = d3.selectAll(".node_selected");
          selection.each(function(d) {
            d3.select("#id_node_" + d.id + networkmap_id)
              .classed("node_selected", false)
              .classed("node_children", true);
          });

          flag_setting_relationship_running = true;
        }
      };

      if (flag_setting_interface_link_running) {
        if (
          d3
            .select("#id_node_" + data.id + networkmap_id)
            .attr("class")
            .search("node_children") == -1
        ) {
          items_list["set_parent_interface"] = {
            name: set_parent_link,
            icon: "interface_link_parent",
            disabled: function() {
              if (data.type == 3 || data.type == 2) {
                return true;
              } else {
                return false;
              }
            },
            callback: function(key, options) {
              var selection = d3.selectAll(".node_selected");
              selection = selection[0];
              if (selection.length > 1) {
                alert("Only one-one relations (one father, one son)");
              } else {
                add_interface_link(data);
              }
            }
          };
        }

        items_list["cancel_set_parent_interface"] = {
          name: abort_relationship_interface,
          icon: "interface_link_cancel",
          disabled: function() {
            if (data.type == 3) {
              return true;
            } else {
              return false;
            }
          },
          callback: function(key, options) {
            cancel_set_parent_interface();
          }
        };
      }

      if (flag_setting_relationship_running) {
        if (
          d3
            .select("#id_node_" + data.id + networkmap_id)
            .attr("class")
            .search("node_children") == -1
        ) {
          items_list["set_parent"] = {
            name: set_parent_menu,
            icon: "set_parent",
            disabled: false,
            callback: function(key, options) {
              var selection = d3.selectAll(".node_selected");
              selection = selection[0];
              if (selection.length > 1) {
                alert("Only one-one relations (one father, one son)");
              } else {
                set_parent(data);
              }
            }
          };
        }

        items_list["cancel_set_parent"] = {
          name: abort_relationship_menu,
          icon: "cancel_set_parent",
          disabled: false,
          callback: function(key, options) {
            cancel_set_parent();
          }
        };
      }

      items_list["delete"] = {
        name: delete_menu,
        icon: "delete",
        disabled: false,
        callback: function(key, options) {
          delete_nodes();
        }
      };

      $.contextMenu("destroy");
      $.contextMenu({
        disabled: false,
        selector: "#networkconsole_" + networkmap_id,
        // define the elements of the menu
        items: items_list
      });
      break;

    case "background":
      var items_list = {};
      items_list["add_node"] = {
        name: add_node_menu,
        icon: "add_node",
        disabled: function() {
          if (networkmap_write) {
            return false;
          } else {
            return true;
          }
        },
        callback: function(key, options) {
          add_node();
        }
      };
      items_list["center"] = {
        name: set_center_menu,
        icon: "center",
        disabled: function() {
          // Check if user can write network maps.
          if (networkmap_write) {
            return false;
          } else {
            return true;
          }
        },
        callback: function(key, options) {
          set_center(networkmap_id);
        }
      };
      items_list["refresh"] = {
        name: refresh_menu,
        icon: "refresh",
        disabled: false,
        callback: function(key, options) {
          refresh();
        }
      };
      items_list["restart_map"] = {
        name: restart_map_menu,
        icon: "restart_map",
        disabled: function() {
          if (networkmap_write) {
            return false;
          } else {
            return true;
          }
        },
        callback: function(key, options) {
          restart_map();
        }
      };

      if (flag_setting_relationship_running) {
        items_list["cancel_set_parent"] = {
          name: abort_relationship_menu,
          icon: "cancel_set_parent",
          disabled: false,
          callback: function(key, options) {
            cancel_set_parent();
          }
        };
      }

      if (flag_setting_relationship_running) {
        items_list["cancel_set_parent_interface"] = {
          name: abort_relationship_interface,
          icon: "cancel_set_parent",
          disabled: false,
          callback: function(key, options) {
            cancel_set_parent_interface();
          }
        };
      }

      if (typeof $.contextMenu == "function") {
        $.contextMenu("destroy");
        $.contextMenu({
          disabled: false,
          selector: "#networkconsole_" + networkmap_id,
          // define the elements of the menu
          items: items_list
        });
      }
      break;
  }

  //Force to show in the mouse position
  if (typeof $("#networkconsole_" + networkmap_id).contextMenu == "function") {
    $("#networkconsole_" + networkmap_id).contextMenu({
      x: mouse[0],
      y: mouse[1]
    });
  }
}

function add_interface_link(data_parent) {
  var selection = d3.selectAll(".node_children");

  count = selection.size();

  selection.each(function(child_data) {
    var repeat = false;
    jQuery.each(graph.links, function(i, link_item) {
      if (
        link_item.source_id_db == child_data.id_db &&
        link_item.target_id_db == data_parent.id_db
      ) {
        repeat = true;
      }

      if (
        link_item.source_id_db == data_parent.id_db &&
        link_item.target_id_db == child_data.id_db
      ) {
        repeat = true;
      }
    });

    if (repeat) {
      count = count - 1;
      if (count == 0) {
        draw_elements_graph();
        set_positions_graph();

        cancel_set_parent();
        cancel_set_parent_interface();
      }

      return; //Break
    }

    var params = [];
    params.push("set_relationship_interface=1");
    params.push("id=" + networkmap_id);
    params.push("child=" + child_data.id_db);
    params.push("parent=" + data_parent.id_db);
    params.push("page=operation/agentes/pandora_networkmap.view");

    jQuery.ajax({
      data: params.join("&"),
      dataType: "json",
      type: "POST",
      url: window.base_url_homedir + "/ajax.php",
      success: function(data) {
        var parent_name = data_parent.text;
        var child_name = child_data.text;
        $("#label-node_source_interface").text(child_name);
        $("#label-node_target_interface").text(parent_name);

        $("#interface_target_select").empty();
        $("#interface_target_select").append(
          '<option value="' + data_parent.id_agent + '">None</option>'
        );
        jQuery.each(data.interfaces_parent, function(i, interface) {
          $("#interface_target_select").append(
            '<option value="' +
              interface.id_agente_modulo +
              '">' +
              interface.nombre +
              "</option>"
          );
        });

        $("#interface_source_select").empty();
        $("#interface_source_select").append(
          '<option value="' + child_data.id_agent + '">None</option>'
        );
        jQuery.each(data.interfaces_child, function(i, interface) {
          $("#interface_source_select").append(
            '<option value="' +
              interface.id_agente_modulo +
              '">' +
              interface.nombre +
              "</option>"
          );
        });

        $("#dialog_interface_link").dialog("open");
      }
    });
  });
}

function add_interface_link_js() {
  cancel_set_parent_interface();
  $("#dialog_interface_link").dialog("close");

  var source_value = $("#interface_source_select").val();
  var source_text = $("#interface_source_select")
    .find("option:selected")
    .text();
  var target_value = $("#interface_target_select").val();
  var target_text = $("#interface_target_select")
    .find("option:selected")
    .text();

  jQuery.ajax({
    data: {
      page: "operation/agentes/pandora_networkmap.view",
      add_interface_relation: 1,
      id: networkmap_id,
      source_value: source_value,
      target_value: target_value,
      source_text: source_text,
      target_text: target_text
    },
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      if (data["correct"]) {
        var temp_link = {};
        temp_link["id"] = graph.links.length;
        temp_link["status_start"] = "0";
        temp_link["status_end"] = "0";

        temp_link["id_db"] = data["id_db_link"];

        if (data["type_source"] == 1) {
          temp_link["arrow_start"] = "module";
          temp_link["id_module_start"] = source_value;
          temp_link["status_start"] = data["status_start"];
          temp_link["link_color"] =
            data["status_start"] == "1" ? "#e63c52" : "#999";
        } else {
          temp_link["arrow_start"] = "";
          temp_link["id_agent_start"] = source_value;
          temp_link["id_module_start"] = 0;
        }
        if (data["type_target"] == 1) {
          temp_link["arrow_end"] = "module";
          temp_link["id_module_end"] = target_value;
          temp_link["status_end"] = data["status_end"];
          temp_link["link_color"] =
            data["status_end"] == "1" ? "#e63c52" : "#999";
        } else {
          temp_link["arrow_end"] = "";
          temp_link["id_agent_end"] = target_value;
          temp_link["id_module_end"] = 0;
        }

        temp_link["text_start"] = data["text_start"];
        temp_link["text_end"] = data["text_end"];

        jQuery.each(graph.nodes, function(j, node) {
          if (node["id_agent"] == data["id_db_target"]) {
            temp_link["target"] = graph.nodes[j];
          }
          if (node["id_agent"] == data["id_db_source"]) {
            temp_link["source"] = graph.nodes[j];
          }
        });

        graph.links.push(temp_link);

        draw_elements_graph();
        init_drag_and_drop();
        set_positions_graph();
      }
    }
  });
}

function refresh_holding_area() {
  var holding_pos_x = d3.select("#holding_area_" + networkmap_id).attr("x");
  var holding_pos_y = d3.select("#holding_area_" + networkmap_id).attr("y");

  var pos_x = parseInt(holding_pos_x) + parseInt(node_radius);
  var pos_y = parseInt(holding_pos_y) + parseInt(node_radius);

  $("#holding_spinner_" + networkmap_id).css("display", "");
  var params = [];
  params.push("refresh_holding_area=1");
  params.push("id=" + networkmap_id);
  params.push("x=" + pos_x);
  params.push("y=" + pos_y);
  params.push("page=operation/agentes/pandora_networkmap.view");
  jQuery.ajax({
    data: params.join("&"),
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      if (data["correct"]) {
        window.holding_area = data["holding_area"];

        jQuery.each(holding_area.nodes, function(i, node) {
          var temp_node = {};

          temp_node["id"] =
            Math.max.apply(
              Math,
              graph.nodes.map(function(o) {
                return o.id;
              })
            ) + 1;
          holding_area.nodes[i]["id"] = temp_node["id"];

          temp_node["id_db"] = node["id_db"];
          temp_node["id_agent"] = node["id_agent"];
          temp_node["id_module"] = 0;
          temp_node["x"] = node["x"];
          temp_node["y"] = node["y"];
          temp_node["z"] = 0;
          temp_node["fixed"] = true;
          temp_node["state"] = "holding_area";
          temp_node["type"] = 0;
          temp_node["color"] = node["color"];
          temp_node["shape"] = node["shape"];
          temp_node["text"] = node["text"];
          temp_node["image_url"] = node["image_url"];
          temp_node["image_width"] = node["image_width"];
          temp_node["image_height"] = node["image_width"];
          temp_node["deleted"] = false;

          graph.nodes.push(temp_node);
        });

        jQuery.each(graph.links, function(j, g_link) {
          for (var i = 0; i < holding_area.links.length; i++) {
            if (g_link["id_db"] == holding_area.links[i]["id_db"]) {
              holding_area.links.splice(i, 1);
            }
          }
        });

        jQuery.each(holding_area.links, function(i, link) {
          var temp_link = {};
          temp_link["id_db"] = link["id_db"];
          temp_link["arrow_start"] = link["arrow_start"];
          temp_link["arrow_end"] = link["arrow_end"];
          temp_link["status_start"] = link["status_start"];
          temp_link["status_end"] = link["status_end"];
          temp_link["id_module_start"] = link["id_module_start"];
          temp_link["id_module_end"] = link["id_module_end"];
          temp_link["text_start"] = link["text_start"];
          temp_link["text_end"] = link["text_end"];

          //Re-hook the links to nodes
          jQuery.each(graph.nodes, function(j, node) {
            if (node["id_agent"] == link["id_agent_end"]) {
              temp_link["target"] = graph.nodes[j];
            }
            if (node["id_agent"] == link["id_agent_start"]) {
              temp_link["source"] = graph.nodes[j];
            }
          });

          graph.links.push(temp_link);
        });

        $("#layer_graph_links_" + networkmap_id).remove();
        $("#layer_graph_nodes_" + networkmap_id).remove();

        window.layer_graph_links = window.layer_graph
          .append("g")
          .attr("id", "layer_graph_links_" + networkmap_id);
        window.layer_graph_nodes = window.layer_graph
          .append("g")
          .attr("id", "layer_graph_nodes_" + networkmap_id);

        var graph_links_aux = graph.links.filter(function(d, i) {
          if (typeof d["source"] === "undefined") {
            return false;
          }

          if (typeof d["target"] === "undefined") {
            return false;
          }

          return d;
        });

        force
          .nodes(graph.nodes)
          .links(graph_links_aux)
          .start();

        window.node = layer_graph_nodes.selectAll(".node");
        window.link = layer_graph_links.selectAll(".link");

        draw_elements_graph();
        init_drag_and_drop();
        set_positions_graph();

        $("#holding_spinner_" + networkmap_id).css("display", "none");
      }
    },
    error: function() {
      $("#holding_spinner_" + networkmap_id).css("display", "none");
    }
  });
}

function refresh() {
  $("#spinner_networkmap").css("display", "flex");
  var holding_pos_x = d3.select("#holding_area_" + networkmap_id).attr("x");
  var holding_pos_y = d3.select("#holding_area_" + networkmap_id).attr("y");

  var pos_x = parseInt(holding_pos_x) + parseInt(node_radius);
  var pos_y = parseInt(holding_pos_y) + parseInt(node_radius);

  var params = [];
  params.push("refresh_holding_area=1");
  params.push("id=" + networkmap_id);
  params.push("x=" + pos_x);
  params.push("y=" + pos_y);
  params.push("page=operation/agentes/pandora_networkmap.view");
  $.ajax({
    data: {
      page: "operation/agentes/pandora_networkmap.view",
      refresh_holding_area: 1,
      id: networkmap_id,
      x: pos_x,
      y: pos_y
    },
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      if (data["correct"]) {
        const array_nodes = data["holding_area"]["nodes"];
        let array_links = data["holding_area"]["links"];
        jQuery.each(graph.links, function(j, g_link) {
          for (var i = 0; i < array_links.length; i++) {
            if (g_link["id_db"] == array_links[i]["id_db"]) {
              array_links.splice(i, 1);
            }
          }
        });

        let location = "";
        if ($("#main").height()) {
          location = `index.php?sec=network&sec2=operation/agentes/pandora_networkmap&tab=view&id_networkmap=${networkmap_id}`;
        } else {
          location = `index.php?sec=network&sec2=operation/agentes/pandora_networkmap&tab=view&pure=1&id_networkmap=${networkmap_id}`;
        }

        if (array_nodes.length === 0 && array_links.length === 0) {
          update_networkmap();
          $("#spinner_networkmap").css("display", "none");
          window.location = location;
        } else {
          if (array_nodes.length > 0) {
            $.ajax({
              data: {
                page: "operation/agentes/pandora_networkmap.view",
                refresh_map: 1,
                id: networkmap_id
              },
              dataType: "json",
              type: "POST",
              url: window.base_url_homedir + "/ajax.php",
              success: function(data) {
                $("#spinner_networkmap").css("display", "none");
                window.location = location;
              }
            });
          } else if (array_links.length > 0) {
            $("#spinner_networkmap").css("display", "none");
            window.location = location;
          }
        }
      }
    },
    error: function(e) {
      $("#spinner_networkmap").css("display", "none");
      window.location = location;
    }
  });
}

function startCountDown(duration) {
  $("div.vc-countdown").countdown("destroy");
  if (!duration) return;
  var t = new Date();
  t.setTime(t.getTime() + duration * 1000);
  $("div.vc-countdown").countdown({
    until: t,
    format: "MS",
    layout: "(%M%nn%M:%S%nn%S Until refreshed) ",
    alwaysExpire: true,
    onExpiry: function() {
      refresh();
    }
  });
}

function restart_map() {
  $(
    "<div id='restart_map_confirm' class='dialog ui-dialog-content' title='" +
      restart_map_menu +
      "'></div>"
  ).dialog({
    resizable: true,
    draggable: true,
    modal: true,
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    width: 600,
    height: 250,
    buttons: [
      {
        text: ok_button,
        click: function() {
          $(this).dialog("close");
          proceed_to_restart_map();
        }
      },
      {
        text: cancel_button,
        click: function() {
          $(this).dialog("close");
        }
      }
    ]
  });

  var dialog_confirm_text = "<div>";
  dialog_confirm_text =
    dialog_confirm_text +
    "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_info_mr.png'></div>";
  dialog_confirm_text =
    dialog_confirm_text +
    "<div style='width:75%; float:left;'><h3><strong style='font-size:13pt;'>" +
    warning_message +
    "</strong></h3>";
  dialog_confirm_text =
    dialog_confirm_text +
    "<p style='font-size:12pt;'>" +
    message_to_confirm +
    "</p></div>";
  dialog_confirm_text = dialog_confirm_text + "</div>";

  $("#restart_map_confirm").html(dialog_confirm_text);
  $("#restart_map_confirm").dialog("open");
}

function proceed_to_restart_map() {
  $(
    "<div id='restart_map_form' class='dialog ui-dialog-content' title='" +
      restart_map_menu +
      "'></div>"
  ).dialog({
    resizable: true,
    draggable: true,
    modal: true,
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    width: 600,
    height: 450,
    buttons: [
      {
        text: ok_button,
        click: function() {
          $(this).dialog("close");
          var data = {
            params: {
              name: $("#text-name").val(),
              id_group: $("#id_group").val(),
              node_radius: $("#text-node_radius").val(),
              description: $("#textarea_description").val(),
              source: $("input[name=source]:checked").val(),
              dont_show_subgroups: $("#checkbox-dont_show_subgroups").is(
                ":checked"
              ),
              recon_task_id: $("#recon_task_id").val(),
              ip_mask: $("#text-ip_mask").val(),
              generation_method: $("#method").val(),
              pos_x: $("#text-pos_x").val(),
              pos_y: $("#text-pos_y").val(),
              scale_z: $("#text-scale_z").val(),
              node_sep: $("#text-node_sep").val(),
              mindist: $("#text-mindist").val(),
              rank_sep: $("#text-rank_sep").val(),
              kval: $("#text-kval").val()
            }
          };
          reset_map_from_form(data);
        }
      },
      {
        text: cancel_button,
        click: function() {
          $(this).dialog("close");
        }
      }
    ]
  });

  var params = [];
  params.push("get_reset_map_form=1");
  params.push("map_id=" + networkmap_id);
  params.push("page=operation/agentes/pandora_networkmap.view");
  jQuery.ajax({
    data: params.join("&"),
    dataType: "html",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      $("#restart_map_form")
        .html(data)
        .dialog("open");
    }
  });
}

function reset_map_from_form(new_elements) {
  var data = new_elements;
  data.map_id = networkmap_id;
  data.reset_map = 1;
  data.page = "operation/agentes/pandora_networkmap.view";
  jQuery.ajax({
    data: data,
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(d) {
      window.location =
        "index.php?sec=network&sec2=operation/agentes/pandora_networkmap&tab=view&id_networkmap=" +
        networkmap_id;
    }
  });
}

function set_parent(parent_data) {
  var selection = d3.selectAll(".node_children");

  var count = selection.size();

  selection.each(function(child_data) {
    //Check if exist the link as
    //   repeat:
    //   old link: node1 (parent) - node2 (child)
    //   new link: node1 (parent) - node2 (child)
    //
    //   swapped:
    //   old link: node1 (child) - node2 (parent)
    //   new link: node2 (child) - node1 (parent)

    var repeat = false;
    jQuery.each(graph.links, function(i, link_item) {
      if (
        link_item.source_id_db == child_data.id_db &&
        link_item.target_id_db == parent_data.id_db
      ) {
        repeat = true;
      }

      if (
        link_item.source_id_db == parent_data.id_db &&
        link_item.target_id_db == child_data.id_db
      ) {
        repeat = true;
      }
    });

    if (repeat) {
      count = count - 1;
      if (count == 0) {
        draw_elements_graph();
        set_positions_graph();

        cancel_set_parent();
        cancel_set_parent_interface();
      }

      return; //Break
    }

    var params = [];
    params.push("set_relationship=1");
    params.push("id=" + networkmap_id);
    params.push("child=" + child_data.id_db);
    params.push("parent=" + parent_data.id_db);
    params.push("page=operation/agentes/pandora_networkmap.view");
    jQuery.ajax({
      data: params.join("&"),
      dataType: "json",
      type: "POST",
      url: window.base_url_homedir + "/ajax.php",
      success: function(data) {
        if (data["correct"]) {
          var child_index = -1;
          var parent_index = -1;

          // Get indexes of child and parent nodes.
          $.each(graph.nodes, function(i, d) {
            if (child_data.id == d.id) {
              child_index = i;
            }

            if (parent_data.id == d.id) {
              parent_index = i;
            }
          });

          //Add the relationship and paint
          var item = {};
          item["arrow_start"] = "";
          item["arrow_end"] = "";
          item["status_start"] = "";
          item["status_end"] = "";
          item["text_start"] = "";
          item["text_end"] = "";
          item["id_module_start"] = 0;
          item["id_module_end"] = 0;
          item["id_db"] = data["id"];
          item["source_id_db"] = child_data.id_db;
          item["target_id_db"] = parent_data.id_db;
          item["id_agent_start"] = graph.nodes[child_index]["id_agent"];
          item["id_agent_end"] = graph.nodes[parent_index]["id_agent"];
          item["target"] = graph.nodes[parent_index];
          item["source"] = graph.nodes[child_index];

          graph.links.push(item);
        }
        //update_networkmap();
        count = count - 1;
        if (count == 0) {
          draw_elements_graph();
          set_positions_graph();

          cancel_set_parent();
          cancel_set_parent_interface();
        }
      }
    });
  });
}

function cancel_set_parent_interface() {
  var selection = d3.selectAll(".node_children");

  selection.each(function(d) {
    d3.select("#id_node_" + d.id + networkmap_id)
      .classed("node_selected", true)
      .classed("node_children", false);
  });

  flag_setting_interface_link_running = false;
}

function cancel_set_parent() {
  var selection = d3.selectAll(".node_children");

  selection.each(function(d) {
    d3.select("#id_node_" + d.id + networkmap_id)
      .classed("node_selected", true)
      .classed("node_children", false);
  });

  flag_setting_relationship_running = false;
}

////////////////////////////////////////////////////////////////////////
// OWN CODE FOR TO DRAG
////////////////////////////////////////////////////////////////////////
function init_drag_and_drop() {
  window.dragables = svg.selectAll(".dragable_node");

  window.drag_start = [0, 0];
  window.drag_end = [0, 0];
  window.drag = d3.behavior
    .drag()
    .on("dragstart", function() {
      if (d3.event.sourceEvent.button == 2) return;

      var mouse_coords = d3.mouse(this);
      drag_start[0] = drag_end[0] = mouse_coords[0];
      drag_start[1] = drag_end[1] = mouse_coords[1];

      flag_drag_running = true;

      d3.event.sourceEvent.stopPropagation();
    })
    .on("dragend", function(d, i) {
      if (d3.event.sourceEvent.button == 2) return;

      flag_drag_running = false;

      var selection = d3.selectAll(".node_selected");

      var holding_pos_x = d3.select("#holding_area_" + networkmap_id).attr("x");
      var holding_pos_y = d3.select("#holding_area_" + networkmap_id).attr("y");
      delete d.raw_text;
      selection.each(function(d) {
        jQuery.ajax({
          dataType: "json",
          type: "POST",
          url: window.base_url_homedir + "/ajax.php",
          data: {
            node: JSON.stringify(d),
            x: holding_pos_x,
            y: holding_pos_y,
            update_node: 1,
            page: "operation/agentes/pandora_networkmap.view"
          },
          success: function(data) {
            if (d.state == "holding_area") {
              //It is out the holding area
              if (data["state"] == "") {
                //Remove the style of nodes and links
                //in holding area
                d3.select("#id_node_" + d.id + networkmap_id).classed(
                  "holding_area",
                  false
                );
                d3.select(".source_" + d.id + networkmap_id).classed(
                  "holding_area_link",
                  false
                );
                d3.select(".target_" + d.id + networkmap_id).classed(
                  "holding_area_link",
                  false
                );
                graph.nodes[d.id].state = "";
              }
            }
          }
        });
      });

      d3.event.sourceEvent.stopPropagation();
    })
    .on("drag", function(d, i) {
      if (d3.event.sourceEvent.button == 2) return;

      var mouse_coords = d3.mouse(this);

      var delta = [0, 0];
      delta[0] = mouse_coords[0] - drag_end[0];
      delta[1] = mouse_coords[1] - drag_end[1];

      drag_end[0] = mouse_coords[0];
      drag_end[1] = mouse_coords[1];

      var selection = d3.selectAll(".node_selected");

      selection.each(function(d) {
        // We search the position of this node in the array (index).
        // This is to avoid errors when deleting nodes (id doesn't match index).
        var index_node = $.inArray(d, graph.nodes);
        graph.nodes[index_node].x = d.x + delta[0];
        graph.nodes[index_node].y = d.y + delta[1];
        graph.nodes[index_node].px = d.px + delta[0];
        graph.nodes[index_node].py = d.py + delta[1];
      });

      draw_elements_graph();
      set_positions_graph();

      d3.event.sourceEvent.stopPropagation();
    });
  dragables.call(drag);
}

function add_fictional_node() {
  var name = $("input[name='name_fictional_node']").val();
  var networkmap_to_link = $("#networkmap_to_link").val();

  var x = (click_menu_position_svg[0] - translation[0]) / scale;
  var y = (click_menu_position_svg[1] - translation[1]) / scale;

  var params = [];
  params.push("create_fictional_point=1");
  params.push("id=" + networkmap_id);
  params.push("name=" + name);
  params.push("networkmap=" + networkmap_to_link);
  params.push("color=" + module_color_status[0]["color"]);
  params.push("radious=" + node_radius);
  params.push("shape=circle");
  params.push("x=" + x);
  params.push("y=" + y);
  params.push("page=operation/agentes/pandora_networkmap.view");
  jQuery.ajax({
    data: params.join("&"),
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      if (data["correct"]) {
        $("#dialog_node_add").dialog("close");

        const new_id =
          Math.max.apply(
            Math,
            graph.nodes.map(function(o) {
              return o.id;
            })
          ) + 1;

        var temp_node = {};
        temp_node["id"] = new_id;
        temp_node["id_db"] = data["id_node"];
        temp_node["id_agent"] = data["id_agent"];
        temp_node["id_module"] = 0;
        temp_node["x"] = x;
        temp_node["y"] = y;
        temp_node["z"] = 0;
        temp_node["fixed"] = true;
        temp_node["type"] = 3;
        temp_node["color"] = data["color"];
        temp_node["shape"] = data["shape"];
        temp_node["text"] = data["text"];
        temp_node["image_url"] = "";
        temp_node["image_width"] = 0;
        temp_node["image_height"] = 0;
        temp_node["networkmap_id"] = networkmap_to_link;

        graph.nodes.push(temp_node);

        draw_elements_graph();
        init_drag_and_drop();
        set_positions_graph();
      }
    }
  });
}

function init_graph(parameter_object) {
  window.width_svg = $("#networkconsole_" + networkmap_id).width();
  if ($("#main").height()) {
    window.height_svg = $("#main").height();
  } else {
    //Set the height in the pure view (fullscreen).
    window.height_svg =
      $(window).height() - $("#menu_tab_frame_view").height() - 20; // 20 of margin
  }
  if (!window.height_svg) {
    window.height_svg = $("#networkconsole_" + networkmap_id).height();
  }

  if (typeof parameter_object.font_size != "undefined") {
    window.font_size = parameter_object.font_size;
  }

  window.refresh_period = 5 * 1000; //milliseconds
  if (typeof parameter_object.refresh_period != "undefined") {
    window.refresh_period = parameter_object.refresh_period;
  }
  window.scale_minimap = 4.2;
  window.translation = [0, 0];
  window.scale = z_dash || 0.5;
  window.node_radius = 40;
  if (typeof parameter_object.node_radius != "undefined") {
    window.node_radius = parameter_object.node_radius;
  }
  window.interface_radius = 3;
  window.disabled_drag_zoom = false;
  window.key_multiple_selection = 17; //CTRL key
  window.flag_multiple_selection = false;
  window.flag_multiple_selection_running = false;
  window.selection_rectangle = [0, 0, 0, 0];
  window.flag_drag_running = false;
  window.in_a_node = false;
  window.enterprise_installed = false;
  window.flag_setting_relationship_running = false;
  window.flag_setting_interface_link_running = false;

  window.minimap_w = 0;
  window.minimap_h = 0;
  window.show_minimap = false;
  window.show_labels = true;
  window.context_minimap;
  window.base_url_homedir = parameter_object.base_url_homedir;

  window.holding_area_dimensions = [200, 200];
  if (typeof parameter_object.holding_area_dimensions != "undefined") {
    window.holding_area_dimensions = parameter_object.holding_area_dimensions;
  }

  window.graph = null;
  if (typeof parameter_object.graph != "undefined") {
    window.graph = parameter_object.graph;
  }

  window.networkmap_center = [];
  if (typeof parameter_object.networkmap_center != "undefined") {
    window.networkmap_center = parameter_object.networkmap_center;
  }

  if (typeof networkmap_center[0] == "undefined") {
    networkmap_center = [0, 0];
  }

  translation[0] = width_svg / 2 - networkmap_center[0];
  translation[1] = height_svg / 2 - networkmap_center[1];

  translation[0] = translation[0] * scale;
  translation[1] = translation[1] * scale;

  window.enterprise_installed = "";
  if (typeof parameter_object.enterprise_installed != "undefined") {
    window.enterprise_installed = parameter_object.enterprise_installed;
  }

  window.networkmap_dimensions = [];
  if (typeof parameter_object.networkmap_dimensions != "undefined") {
    window.networkmap_dimensions = parameter_object.networkmap_dimensions;
  }
  window.max = Math.max(networkmap_dimensions[0], networkmap_dimensions[1]);
  window.min = Math.min(width_svg / scale_minimap, height_svg / scale_minimap);

  window.minimap_relation = min / max;
  minimap_w = networkmap_dimensions[0] * minimap_relation;
  minimap_h = networkmap_dimensions[1] * minimap_relation;

  $("#minimap_" + networkmap_id).attr("width", minimap_w);
  $("#minimap_" + networkmap_id).attr("height", minimap_h);

  window.canvas_minimap = $("#minimap_" + networkmap_id);
  window.context_minimap = canvas_minimap[0].getContext("2d");
  window.minimap_drag = false;

  window.url_background_grid = "";
  if (typeof parameter_object.url_background_grid != "undefined") {
    // GRID
    window.url_background_grid = "";
  }

  if (typeof parameter_object.refresh_time != "undefined") {
    window.refresh_time = parameter_object.refresh_time;
  }

  if (typeof parameter_object.method != "undefined") {
    window.method = parameter_object.method;
  }

  var rect_center_x = graph.nodes[0].x;
  var rect_center_y = graph.nodes[0].y;

  //For to catch the keyevent for the ctrl key
  d3.select(document)
    .on("keydown", function() {
      if (d3.event.keyCode == key_multiple_selection) {
        flag_multiple_selection = true;
        disabled_drag_zoom = true;
      }
    })
    .on("keyup", function() {
      if (d3.event.keyCode == key_multiple_selection) {
        flag_multiple_selection = false;
        disabled_drag_zoom = false;
        flag_multiple_selection_running = false;

        d3.select("#selection_rectangle").style("display", "none");
      }
    });

  window.force = d3.layout
    .force()
    .charge(10)
    .linkDistance(0)
    .size([width_svg, height_svg]);

  if (x_offs != null) {
    translation[0] = translation[0] + x_offs;
  }
  if (y_offs != null) {
    translation[1] = translation[1] + y_offs;
  }
  if (z_dash != null) {
    scale = z_dash;
  }

  window.zoom_obj = d3.behavior.zoom();
  zoom_obj
    .scaleExtent([0.05, 1])
    .on("zoom", zoom)
    .translate(translation)
    .scale(scale);

  window.svg = d3
    .select("#networkconsole_" + networkmap_id)
    .append("svg")
    .attr("id", "dinamic_networkmap_svg_" + networkmap_id)
    .attr("width", width_svg)
    .attr("height", height_svg)
    .call(zoom_obj)
    .on("mousedown", function() {
      if (flag_multiple_selection) {
        flag_multiple_selection_running = true;

        mouse_coords = d3.mouse(this);

        selection_rectangle[0] = mouse_coords[0];
        selection_rectangle[1] = mouse_coords[1];
        selection_rectangle[2] = mouse_coords[0];
        selection_rectangle[3] = mouse_coords[1];

        d3.select("#selection_rectangle")
          .style("display", "")
          .attr("x", selection_rectangle[0])
          .attr("y", selection_rectangle[1])
          .attr("width", selection_rectangle[2] - selection_rectangle[0])
          .attr("height", selection_rectangle[3] - selection_rectangle[1]);
      } else {
        clear_selection();
      }
    })
    .on("mouseup", function() {
      flag_multiple_selection_running = false;
      d3.select("#selection_rectangle").style("display", "none");
    })
    .on("mousemove", function() {
      if (flag_multiple_selection_running) {
        mouse_coords = d3.mouse(this);

        selection_rectangle[2] = mouse_coords[0];
        selection_rectangle[3] = mouse_coords[1];

        x = selection_rectangle[0];
        width = selection_rectangle[2] - selection_rectangle[0];
        if (width < 0) {
          x = selection_rectangle[2];
          width = selection_rectangle[0] - selection_rectangle[2];
        }

        y = selection_rectangle[1];
        height = selection_rectangle[3] - selection_rectangle[1];
        if (height < 0) {
          y = selection_rectangle[3];
          height = selection_rectangle[1] - selection_rectangle[3];
        }

        d3.select("#selection_rectangle")
          .attr("x", x)
          .attr("y", y)
          .attr("width", width)
          .attr("height", height);

        sel_rec_x1 = x;
        sel_rec_x2 = x + width;
        sel_rec_y1 = y;
        sel_rec_y2 = y + height;

        d3.selectAll(".node").each(function(data, i) {
          item_x1 = (data.x - node_radius / 2) * scale + translation[0];
          item_x2 = (data.x + node_radius / 2) * scale + translation[0];
          item_y1 = (data.y - node_radius / 2) * scale + translation[1];
          item_y2 = (data.y + node_radius / 2) * scale + translation[1];

          if (
            !d3.select(this).classed("node_selected") &&
            // inner circle inside selection frame
            item_x1 >= sel_rec_x1 &&
            item_x2 <= sel_rec_x2 &&
            item_y1 >= sel_rec_y1 &&
            item_y1 <= sel_rec_y2
          ) {
            d3.select("#id_node_" + data.id + networkmap_id).classed(
              "node_selected",
              true
            );
          }
        });
      }
    })
    .on("contextmenu", function(d) {
      show_menu("background", d);
    });

  window.defs = svg.append("defs");
  defs
    .selectAll("defs")
    .data(module_color_status)
    .enter()
    .append("marker")
    .attr("id", function(d) {
      return "interface_start_" + d.status_code;
    })
    .attr("refX", 0)
    .attr("refY", interface_radius)
    .attr("markerWidth", node_radius / 2 + interface_radius)
    .attr("markerHeight", node_radius / 2 + interface_radius)
    .attr("orient", "auto")
    .append("circle")
    .attr("cx", node_radius / 2.3 - interface_radius / 2.3)
    .attr("cy", interface_radius)
    .attr("r", interface_radius)
    .attr("style", function(d) {
      return "fill: " + d.color + ";";
    });

  defs
    .selectAll("defs")
    .data(module_color_status)
    .enter()
    .append("marker")
    .attr("id", function(d) {
      return "interface_end_" + d.status_code;
    })
    .attr("refX", node_radius / 2.3 + interface_radius / 2.3)
    .attr("refY", interface_radius)
    .attr("markerWidth", node_radius / 2 + interface_radius)
    .attr("markerHeight", node_radius / 2 + interface_radius)
    .attr("orient", "auto")
    .append("circle")
    .attr("cx", interface_radius)
    .attr("cy", interface_radius)
    .attr("r", interface_radius)
    .attr("style", function(d) {
      return "fill: " + d.color + ";";
    });

  defs
    .append("marker")
    .attr("id", "interface_start")
    .attr("refX", 0)
    .attr("refY", interface_radius)
    .attr("markerWidth", node_radius / 2 + interface_radius)
    .attr("markerHeight", node_radius / 2 + interface_radius)
    .attr("orient", "auto")
    .append("circle")
    .attr("cx", node_radius / 2 - interface_radius / 2)
    .attr("cy", interface_radius)
    .attr("r", interface_radius)
    .attr("style", "fill:" + module_color_status_unknown + ";");

  defs
    .append("marker")
    .attr("id", "interface_end")
    .attr("refX", node_radius / 2 + interface_radius / 2)
    .attr("refY", interface_radius)
    .attr("markerWidth", node_radius / 2 + interface_radius)
    .attr("markerHeight", node_radius / 2 + interface_radius)
    .attr("orient", "auto")
    .append("circle")
    .attr("cx", interface_radius)
    .attr("cy", interface_radius)
    .attr("r", interface_radius)
    .attr("style", "fill:" + module_color_status_unknown + ";");

  //Added pattern for the background grid
  svg
    .append("pattern")
    .attr("id", "background_grid")
    .attr("height", 24)
    .attr("width", 25)
    .attr("patternUnits", "userSpaceOnUse")
    .append("image")
    .attr("y", 0)
    .attr("x", 0)
    .attr("xlink:href", url_background_grid)
    .attr("height", 24)
    .attr("width", 25);

  window.layer_graph = svg
    .append("g")
    .attr("id", "layer_graph_" + networkmap_id)
    .attr("transform", "translate(" + translation + ")scale(" + scale + ")");

  window.layer_graph
    .append("rect")
    .attr("id", "holding_area_" + networkmap_id)
    .attr("width", holding_area_dimensions[0])
    .attr("height", holding_area_dimensions[1])
    .attr(
      "x",
      networkmap_dimensions[0] + node_radius - holding_area_dimensions[0]
    )
    .attr(
      "y",
      networkmap_dimensions[1] + node_radius - holding_area_dimensions[1]
    )
    .attr(
      "style",
      "fill: #e6e6e6; " +
        "fill-opacity: 0.75; " +
        "stroke: #dedede; " +
        "stroke-width: 1; " +
        "stroke-miterlimit: 4; " +
        "stroke-opacity: 0.75; " +
        "stroke-dasharray: none; " +
        "stroke-dashoffset: 0"
    )
    .attr("class", "fill_222");
  window.layer_graph
    .append("text")
    .append("tspan")
    .attr("xml:space", "preserve")
    .attr(
      "style",
      "font-size: 32px; " +
        "font-style: normal; " +
        "font-weight: normal; " +
        "text-align: start; " +
        "line-height: 125%; " +
        "letter-spacing: 0px; " +
        "word-spacing: 0px; " +
        "text-anchor: start; " +
        "fill: #000000; " +
        "fill-opacity: 1; " +
        "stroke: none; "
    )
    .attr("class", "fill_fff")
    .attr(
      "x",
      networkmap_dimensions[0] + node_radius - holding_area_dimensions[0]
    )
    .attr(
      "y",
      networkmap_dimensions[1] + node_radius - holding_area_dimensions[1] - 10
    )
    .text(holding_area_title);

  window.layer_graph_links = window.layer_graph
    .append("g")
    .attr("id", "layer_graph_links_" + networkmap_id);
  window.layer_graph_nodes = window.layer_graph
    .append("g")
    .attr("id", "layer_graph_nodes_" + networkmap_id);

  window.layer_selection_rectangle = svg
    .append("g")
    .attr("id", "layer_selection_rectangle");

  force
    .nodes(graph.nodes)
    .links(graph.links)
    .start();

  window.node = layer_graph_nodes.selectAll(".node");
  window.link = layer_graph_links.selectAll(".link");

  draw_elements_graph();
  set_positions_graph();

  layer_selection_rectangle
    .append("rect")
    .attr("width", 666)
    .attr("height", 666)
    .attr("x", 0)
    .attr("y", 0)
    .attr("id", "selection_rectangle")
    .attr(
      "style",
      "display: none; fill:#e6e6e6; " +
        "fill-opacity:0.46153846; " +
        "stroke:#e6e6e6; " +
        "stroke-width:1; " +
        "stroke-miterlimit:4; " +
        "stroke-opacity:1; " +
        "stroke-dasharray:none;"
    );

  $("#dialog_node_edit").dialog({
    autoOpen: false,
    width: 650
  });

  $("#dialog_interface_link").dialog({
    autoOpen: false,
    width: 650
  });

  $("#dialog_node_add").dialog({
    autoOpen: false,
    width: 650
  });
}

function myMouseoverCircleFunction(node_id) {
  var circle = d3.select("#id_node_" + node_id + networkmap_id + " circle");

  over = circle.classed("node_over");

  in_a_node = !in_a_node;

  circle.classed("node_over", !over);

  circle
    .transition()
    .duration(400)
    .attr("r", node_radius + 10);
}
function myMouseoutCircleFunction(node_id) {
  var circle = d3.select("#id_node_" + node_id + networkmap_id + " circle");

  over = circle.classed("node_over");

  in_a_node = !in_a_node;

  circle.classed("node_over", !over);

  circle
    .transition()
    .duration(400)
    .attr("r", node_radius);
}

function myMouseoverSquareFunction(node_id) {
  var square = d3.select("#id_node_" + node_id + networkmap_id + " rect");

  over = square.classed("node_over");

  in_a_node = !in_a_node;

  square.classed("node_over", !over);

  square
    .transition()
    .duration(400)
    .attr("width", node_radius * 2 + 10)
    .attr("height", node_radius * 2 + 10)
    .attr("transform", "translate(" + -5 + "," + -5 + ")");
}
function myMouseoutSquareFunction(node_id) {
  var square = d3.select("#id_node_" + node_id + networkmap_id + " rect");

  over = square.classed("node_over");

  in_a_node = !in_a_node;

  square.classed("node_over", !over);

  square
    .transition()
    .duration(400)
    .attr("width", node_radius * 2)
    .attr("height", node_radius * 2)
    .attr("transform", "translate(" + 0 + "," + 0 + ")");
}

function myMouseoverRhombusFunction(node_id) {
  var rhombus = d3.select("#id_node_" + node_id + networkmap_id + " rect");

  over = rhombus.classed("node_over");

  in_a_node = !in_a_node;

  rhombus.classed("node_over", !over);

  rhombus
    .transition()
    .duration(400)
    .attr("width", node_radius * 1.5 + 10)
    .attr("height", node_radius * 1.5 + 10);
}
function myMouseoutRhombusFunction(node_id) {
  var rhombus = d3.select("#id_node_" + node_id + networkmap_id + " rect");

  over = rhombus.classed("node_over");

  in_a_node = !in_a_node;

  rhombus.classed("node_over", !over);

  rhombus
    .transition()
    .duration(400)
    .attr("width", node_radius * 1.5)
    .attr("height", node_radius * 1.5);
}

function draw_elements_graph() {
  link = link.data(
    force.links().filter(function(d, i) {
      if (d["deleted"]) {
        return false;
      } else {
        return true;
      }
    }),
    function(d) {
      return (
        d.source.id +
        networkmap_id +
        "-" +
        d.target.id +
        networkmap_id +
        Math.random()
      );
    }
  );

  link_temp = link
    .enter()
    .append("g")
    .attr("id", function(d) {
      return "link_id_" + d.id_db + networkmap_id;
    })
    .attr("class", function(d) {
      var holding_area_text = "";
      if (
        d.source.state == "holding_area" ||
        d.target.state == "holding_area"
      ) {
        holding_area_text = " holding_area_link ";
      }

      return (
        "link " +
        "source_" +
        d.source.id +
        networkmap_id +
        " " +
        "target_" +
        d.target.id +
        networkmap_id +
        " " +
        holding_area_text +
        "id_module_start_" +
        d.id_module_start +
        " " +
        "id_module_end_" +
        d.id_module_end
      );
    });

  link.exit().remove();

  link_temp
    .append("path")
    .attr("id", function(d) {
      return "link_id_text_" + d.id_db + networkmap_id;
    })
    .attr("class", function(d) {
      var holding_area_text = "";
      if (
        d.source.state == "holding_area" ||
        d.target.state == "holding_area"
      ) {
        holding_area_text = " holding_area_link ";
      }

      return (
        "link " +
        "source_" +
        d.source.id +
        networkmap_id +
        " " +
        "target_" +
        d.target.id +
        networkmap_id +
        " " +
        holding_area_text +
        "id_module_start_" +
        d.id_module_start +
        " " +
        "id_module_end_" +
        d.id_module_end
      );
    })
    .attr("stroke", function(d) {
      if (d.link_color === undefined) {
        return "#999";
      } else {
        return d.link_color;
      }
    })
    .attr("stroke-width", 3)
    .attr("d", null)
    .attr("marker-start", function(d) {
      if (d.arrow_start == "") {
        return "";
      } else if (d.arrow_start == "module") {
        if (typeof module_color_status[d.status_start] == "undefined")
          return "url(#interface_start)";
        else return "url(#interface_start_" + d.status_start + ")";
      }
    })
    .attr("marker-end", function(d) {
      if (d.arrow_end == "") {
        return "";
      } else if (d.arrow_end == "module") {
        if (typeof module_color_status[d.status_end] == "undefined")
          return "url(#interface_end)";
        else return "url(#interface_end_" + d.status_end + ")";
      }
    })
    .on("mouseover", function(d) {
      d3.select(this).classed("link_over", true);
    })
    .on("mouseout", function(d) {
      d3.select(this).classed("link_over", false);
    });

  //Add the reverse line for the end marker, it is invisible
  link_temp
    .append("path")
    .attr("id", function(d) {
      return "link_reverse_id_" + d.id_db + networkmap_id;
    })
    .attr("stroke-width", 0)
    .attr("d", null)
    .attr("class", function(d) {
      return "link_reverse";
    });

  link_temp
    .append("text")
    .attr("xml:space", "preserve")
    .append("textPath")
    .attr("xlink:href", function(d) {
      if (d.source.x < d.target.x) {
        return "#link_id_text_" + d.id_db + networkmap_id;
      } else {
        return "#link_reverse_id_" + d.id_db + networkmap_id;
      }
    })
    .attr("startOffset", function(d) {
      if (d.source.x < d.target.x) {
        return "0%";
      } else {
        return "85%";
      }
    })
    .attr("text-anchor", function(d) {
      if (d.source.x < d.target.x) {
        return "";
      } else {
        return "end";
      }
    })
    .append("tspan")
    .attr(
      "style",
      "font-size: 12px; " +
        "font-style:normal; " +
        "font-weight:normal; " +
        "line-height: 100%; " +
        "letter-spacing:0px; " +
        "word-spacing:0px; " +
        "fill:#000000; " +
        "fill-opacity:1; " +
        "stroke:none; " +
        "text-align:start; "
    )
    .text(function(d) {
      var text_link = "";
      if (d.text_start) {
        text_link = d.text_start;
      }

      return Array(25).join(" ") + text_link;
    });

  link_temp
    .append("text")
    .attr("xml:space", "preserve")
    .append("textPath")
    .attr("xlink:href", function(d) {
      if (d.source.x < d.target.x) {
        return "#link_id_text_" + d.id_db + networkmap_id;
      } else {
        return "#link_reverse_id_" + d.id_db + networkmap_id;
      }
    })
    .attr("startOffset", function(d) {
      if (d.source.x < d.target.x) {
        return "85%";
      } else {
        return "0%";
      }
    })
    .attr("text-anchor", function(d) {
      if (d.source.x < d.target.x) {
        return "end";
      } else {
        return "";
      }
    })
    .append("tspan")
    .attr(
      "style",
      "font-size: 12px; " +
        "font-style:normal; " +
        "font-weight:normal; " +
        "line-height: 100%; " +
        "letter-spacing:0px; " +
        "word-spacing:0px; " +
        "fill:#000000; " +
        "fill-opacity:1; " +
        "stroke:none; " +
        "text-align:end; "
    )
    .text(function(d) {
      var text_link = "";
      if (d.text_end) {
        text_link = d.text_end;
      }

      return Array(25).join(" ") + text_link;
    });

  node = node.data(
    force.nodes().filter(function(d, i) {
      if (d["deleted"]) {
        return false;
      } else {
        return true;
      }
    }),
    function(d) {
      return d.id;
    }
  );

  node_temp = node
    .enter()
    .append("g")
    .attr("id", function(d) {
      return "id_node_" + d.id + networkmap_id;
    })
    .attr("class", function(d) {
      if (d.state == "holding_area") return "node holding_area";
      else return "node";
    });

  node.exit().remove();

  //Shape circle
  node_temp
    .filter(function(d) {
      if (d.shape == "circle") {
        return true;
      } else return false;
    })
    .append("circle")
    .attr("r", node_radius)
    .attr("class", "node_shape node_shape_circle")
    .attr("node_id", function(d) {
      return d.id + networkmap_id;
    })
    .style("fill", function(d) {
      return d.color;
    })
    .classed("dragable_node", true) //own dragable
    .on("mouseover", function(d) {
      myMouseoverCircleFunction(d.id);
    })
    .on("mouseout", function(d) {
      myMouseoutCircleFunction(d.id);
    })
    .on("click", selected_node)
    .on("dblclick", function(d) {
      edit_node(d, true);
    })
    .on("contextmenu", function(d) {
      show_menu("node", d);
    });

  node_temp
    .append("image")
    .attr("class", "node_image")
    .attr("xlink:href", function(d) {
      return is_central_node(d) ? $("#hidden-center_logo").val() : d.image_url;
    })
    .attr("x", function(d) {
      return d.x - d.image_width / 2;
    })
    .attr("y", function(d) {
      return d.y - d.image_height / 2;
    })
    .attr("width", function(d) {
      return node_radius / 0.8;
    })
    .attr("height", function(d) {
      return node_radius / 0.8;
    })
    .attr("node_id", function(d) {
      return d.id + networkmap_id;
    })
    .attr("style", function(d) {
      const extension = d.image_url.split(".").pop();
      return extension !== "svg" || d.id === 0 ? "filter: invert(0%)" : "";
    })
    .attr("id", "image2995")
    .classed("dragable_node", true) //own dragable
    .on("mouseover", function(d) {
      myMouseoverCircleFunction(d.id);
    })
    .on("mouseout", function(d) {
      myMouseoutCircleFunction(d.id);
    })
    .on("click", selected_node)
    .on("dblclick", function(d) {
      edit_node(d, true);
    })
    .on("contextmenu", function(d) {
      show_menu("node", d);
    });

  //Shape square
  node_temp
    .filter(function(d) {
      if (d.shape == "square") {
        return true;
      } else return false;
    })
    .append("rect")
    .attr("width", node_radius * 2)
    .attr("height", node_radius * 2)
    .attr("class", "node_shape node_shape_square")
    .attr("node_id", function(d) {
      return d.id + networkmap_id;
    })
    .style("fill", function(d) {
      return d.color;
    })
    .classed("dragable_node", true) //own dragable
    .on("mouseover", function(d) {
      myMouseoverSquareFunction(d.id);
    })
    .on("mouseout", function(d) {
      myMouseoutSquareFunction(d.id);
    })
    .on("click", selected_node)
    .on("dblclick", function(d) {
      edit_node(d, true);
    })
    .on("contextmenu", function(d) {
      show_menu("node", d);
    });

  node_temp
    .filter(function(d) {
      if (d.shape == "square") {
        return true;
      } else return false;
    })
    .append("image")
    .attr("class", "node_image")
    .attr("xlink:href", function(d) {
      return d.image_url;
    })
    .attr("x", function(d) {
      return d.x - d.image_width / 2;
    })
    .attr("y", function(d) {
      return d.y - d.image_height / 2;
    })
    .attr("width", function(d) {
      return node_radius / 0.8;
    })
    .attr("height", function(d) {
      return node_radius / 0.8;
    })
    .attr("node_id", function(d) {
      return d.id + networkmap_id;
    })
    .attr("id", "image2995")
    .classed("dragable_node", true) //own dragable
    .on("mouseover", function(d) {
      myMouseoverSquareFunction(d.id);
    })
    .on("mouseout", function(d) {
      myMouseoutSquareFunction(d.id);
    })
    .on("click", selected_node)
    .on("dblclick", function(d) {
      edit_node(d, true);
    })
    .on("contextmenu", function(d) {
      show_menu("node", d);
    });

  //Shape rhombus
  node_temp
    .filter(function(d) {
      if (d.shape == "rhombus") {
        return true;
      } else return false;
    })
    .append("rect")
    .attr("transform", "")
    .attr("width", node_radius * 1.5)
    .attr("height", node_radius * 1.5)
    .attr("class", "node_shape node_shape_rhombus")
    .attr("node_id", function(d) {
      return d.id + networkmap_id;
    })
    .style("fill", function(d) {
      return d.color;
    })
    .classed("dragable_node", true) //own dragable
    .on("mouseover", function(d) {
      myMouseoverRhombusFunction(d.id);
    })
    .on("mouseout", function(d) {
      myMouseoutRhombusFunction(d.id);
    })
    .on("click", selected_node)
    .on("dblclick", function(d) {
      edit_node(d, true);
    })
    .on("contextmenu", function(d) {
      show_menu("node", d);
    });

  node_temp
    .filter(function(d) {
      if (d.shape == "rhombus") {
        return true;
      } else return false;
    })
    .append("image")
    .attr("class", "node_image")
    .attr("xlink:href", function(d) {
      return d.image_url;
    })
    .attr("x", function(d) {
      return d.x - d.image_width / 2;
    })
    .attr("y", function(d) {
      return d.y - d.image_height / 2;
    })
    .attr("width", function(d) {
      return node_radius / 0.8;
    })
    .attr("height", function(d) {
      return node_radius / 0.8;
    })
    .attr("node_id", function(d) {
      return d.id + networkmap_id;
    })
    .attr("id", "image2995")
    .classed("dragable_node", true) //own dragable
    .on("mouseover", function(d) {
      myMouseoverRhombusFunction(d.id);
    })
    .on("mouseout", function(d) {
      myMouseoutRhombusFunction(d.id);
    })
    .on("click", selected_node)
    .on("dblclick", function(d) {
      edit_node(d, true);
    })
    .on("contextmenu", function(d) {
      show_menu("node", d);
    });

  var font_size = node_radius / 1.5;

  if (typeof window.font_size != "undefined") {
    font_size = window.font_size;
  }

  node_temp
    .append("text")
    .attr("class", "node_text fill_fff")
    .attr("id", "node_text_" + networkmap_id)
    .attr(
      "style",
      "font-style:normal; font-weight:normal; line-height:125%;letter-spacing:0px;word-spacing:0px;fill:#000000;fill-opacity:1;stroke:none;"
    )
    .attr("x", function(d) {
      return d.x;
    })
    .attr("y", function(d) {
      return d.y + node_radius + 12;
    })
    .append("tspan")
    .attr(
      "style",
      "font-size: " +
        font_size +
        "px !important; text-align:center; text-anchor:middle; fill:#000000"
    )
    .html(function(d) {
      d.text = ellipsize(d.text, 30);

      return get_node_name_ov(d, true, font_size);
    })
    .classed("dragable_node fill_fff", true) //own dragable
    .on("click", selected_node)
    .on("contextmenu", function(d) {
      show_menu("node", d);
    });

  node_temp.append("title").text(function(d) {
    return get_node_name_ov(d, false);
  });

  node.exit().remove();
}

function is_central_node(data) {
  return data.type == 0 && data.id_agent == 0;
}

function get_node_name_ov(data, generate_link, font_size) {
  font_size = font_size || 20;
  generate_link = generate_link || false;

  var data_text = data.text;

  if (generate_link === true && data.networkmap_id > 0) {
    data_text = `<a href="index.php?sec=network&amp;sec2=operation/agentes/pandora_networkmap&amp;tab=view&amp;id_networkmap=
    ${data.networkmap_id}" style="font-size: ${font_size}px;">${data.text}</a>`;
  }

  // Node central name should be the product name
  return is_central_node(data) ? $("#hidden-product_name").val() : data_text;
}

function choose_group_for_show_agents() {
  var group = $("#group_for_show_agents option:selected").val();
  var group_recursion =
    $("#checkbox-group_recursion").prop("checked") === true ? 1 : 0;

  $("#agents_filter_group").attr("disabled", true);
  $("#spinner_group").css("display", "");
  if (group == -1) {
    $("#agents_filter_group").html(
      '<option value="-1">' + $("#hack_translation_none").html() + "</option>"
    );
    $("#spinner_group").css("display", "none");
  } else {
    $("#group_for_show_agents").attr("disabled", true);

    var params = [];
    params.push("get_agents_in_group=1");
    params.push("id=" + networkmap_id);
    params.push("group=" + group);
    params.push("group_recursion=" + group_recursion);
    params.push("page=operation/agentes/pandora_networkmap.view");
    jQuery.ajax({
      data: params.join("&"),
      dataType: "json",
      type: "POST",
      url: window.base_url_homedir + "/ajax.php",
      success: function(data) {
        if (data["correct"]) {
          $("#agents_filter_group").html("");
          jQuery.each(data["agents"], function(id, name) {
            if (typeof name == "undefined") return;

            $("#agents_filter_group").append(
              '<option value="' + id + '">' + name + "</option>"
            );
          });

          $("#agents_filter_group").removeAttr("disabled");
          $("#group_for_show_agents").removeAttr("disabled");
          $("#spinner_group").css("display", "none");
          $("input[name=add_agent_group_button]").removeAttr("disabled");
        } else {
          $("#group_for_show_agents").removeAttr("disabled");
          $("#agents_filter_group").html(
            '<option value="-1">' + translation_none + "</option>"
          );
          $("#spinner_group").css("display", "none");
        }
      }
    });
  }
}

////////////////////////////////////////////////////////////////////////
// Old code for the details node
////////////////////////////////////////////////////////////////////////
//PSEUDO-CONSTANTS
var VERTICAL_SPACE_MODULES = 55;
var HORIZONTAL_SPACE_MODULES = 150;
var VERTICAL_SPACING_BETWEEN_MODULES = 10;
var BORDER_SIZE_AGENT_BOX = 5;
var SIZE_MODULE = 30;
var MARGIN_BETWEEN_AGENT_MODULE = 20;

var context_popup = null;
var dirty_popup = false;
var id_agent = 0;
var pos_x = 0;
var box_height = 0;
var box_width = 0;
var count_no_snmp = 0;

var drag = false;
var drag_x = 0;
var drag_y = 0;
var drag_x_delta = 0;
var drag_y_delta = 0;
var offset_x = 0;
var offset_y = 0;
var module_inner = null;

function get_status_node() {
  var params = [];
  params.push("get_status_node=1");
  params.push("id=" + id_agent);
  params.push("page=operation/agentes/pandora_networkmap.view");
  jQuery.ajax({
    data: params.join("&"),
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      if (data["correct"]) {
        color_status_node = data["status_agent"];
        dirty_popup = true;
      }
    }
  });
}

function get_status_module() {
  jQuery.each(modules, function(id, module) {
    if (typeof module == "undefined") return;

    var params = [];
    params.push("get_status_module=1");
    params.push("id=" + id);
    params.push("page=operation/agentes/pandora_networkmap.view");
    jQuery.ajax({
      data: params.join("&"),
      dataType: "json",
      type: "POST",
      url: window.base_url_homedir + "/ajax.php",
      success: function(data) {
        if (data["correct"]) {
          modules[data["id"]].status_color = data["status_color"];
          dirty_popup = true;
        }
      }
    });
  });
}

function show_networkmap_node(id_agent_param, refresh_state) {
  id_agent = id_agent_param;

  canvas = $("#node_info");
  context_popup = canvas[0].getContext("2d");

  dirty_popup = true;
  self.setInterval("check_popup_modification()", 1000 / 30);

  $("#node_info").mousemove(function(event) {
    var x = event.pageX - $("#node_info").offset().left;
    var y = event.pageY - $("#node_info").offset().top;

    module_inner = inner_module(x, y);

    if (module_inner != null) {
      document.body.style.cursor = "pointer";
    } else {
      document.body.style.cursor = "default";
    }
  });

  $("#node_info").mousedown(function(event) {
    var x = event.pageX - $("#node_info").offset().left;
    var y = event.pageY - $("#node_info").offset().top;

    if (module_inner != null) {
      show_tooltip(module_inner, x, y);
    }

    event.stopPropagation();
    return false;
  });

  $("#node_info").mouseup(function(event) {
    var x = event.pageX - $("#node_info").offset().left;
    var y = event.pageY - $("#node_info").offset().top;

    drag = false;
    drag_x = 0;
    drag_y = 0;
    dirty_popup = true;

    document.body.style.cursor = "default";

    module_inner = null;

    event.stopPropagation();
    return false;
  });

  $("#node_info").mouseout(function(event) {
    var x = event.pageX - $("#node_info").offset().left;
    var y = event.pageY - $("#node_info").offset().top;

    drag = false;
    drag_x = 0;
    drag_y = 0;
    dirty_popup = true;

    document.body.style.cursor = "default";

    module_inner = null;

    event.stopPropagation();
    return false;
  });

  $(window).resize(function() {
    pos_scroll = Math.floor($("#content_node_info").width() / 2);

    $("#content_node_info").scrollLeft(pos_scroll);

    dirty_popup = true;
    check_popup_modification();
  });
}

function show_tooltip_content(id) {
  var params = [];
  params.push("get_tooltip_content=1");
  params.push("id=" + id);
  params.push("page=operation/agentes/pandora_networkmap.view");
  jQuery.ajax({
    data: params.join("&"),
    dataType: "json",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      if (data["correct"]) {
        $("#tooltip").html(data["content"]);
      }
    }
  });
}

function show_tooltip(id, x, y) {
  $("#tooltip").css("top", y + "px");
  $("#tooltip").css("left", x + "px");

  var params1 = [];
  params1.push("get_image_path=1");
  params1.push("img_src=" + "images/spinner.gif");
  params1.push("page=include/ajax/skins.ajax");
  jQuery.ajax({
    data: params1.join("&"),
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      $("#tooltip").html(data);
      $("#tooltip").css("display", "");

      show_tooltip_content(id);
    }
  });
}

function hide_tooltip() {
  $("#tooltip").css("display", "none");
}

function inner_module(x, y) {
  var return_var = null;

  jQuery.each(modules, function(key, module) {
    if (typeof module == "undefined") return;

    if (
      x >= module.pos_x &&
      x < module.pos_x + SIZE_MODULE &&
      y >= module.pos_y &&
      y < module.pos_y + SIZE_MODULE
    ) {
      return_var = key;
    }
  });

  return return_var;
}

function check_popup_modification() {
  if (dirty_popup) {
    draw_popup();
    dirty_popup = false;
  }
}

function draw_popup() {
  //Calculate the size
  count_no_snmp = module_count - count_snmp_modules;

  if (count_no_snmp > count_snmp_modules) {
    box_height =
      Math.ceil(count_no_snmp / 2) * VERTICAL_SPACE_MODULES +
      VERTICAL_SPACING_BETWEEN_MODULES;
  } else {
    box_height =
      Math.ceil(count_snmp_modules / 2) * VERTICAL_SPACE_MODULES +
      VERTICAL_SPACING_BETWEEN_MODULES;
  }

  //Draw the agent box.
  // 2 columns of HORIZONTAL_SPACE_MODULES px for each modules
  // + 15 * 2 half each snmp module
  box_width = HORIZONTAL_SPACE_MODULES * 2 + SIZE_MODULE;

  //Resize the canvas if the box is bigger before of paint.
  if (box_height + 50 != $("#node_info").attr("height")) {
    node_info_height = box_height + 50;
    $("#node_info").attr("height", node_info_height);
    //$("#node_info").attr("width", node_info_width);
  }

  if (box_width + 400 != $("#node_info").attr("width")) {
    node_info_width = box_width + 400;
    $("#node_info").attr("width", node_info_width);
  }

  //Clean the canvas
  context_popup.clearRect(0, 0, node_info_width, node_info_height);
  context_popup.beginPath(); //Erase lines?

  pos_x = (node_info_width - box_width) / 2 + offset_x;

  context_popup.beginPath();
  context_popup.rect(
    pos_x,
    VERTICAL_SPACING_BETWEEN_MODULES + offset_y,
    box_width,
    box_height
  );
  context_popup.fillStyle = "#ccc";
  context_popup.fill();

  //Draw the global status of agent into the box's border color.
  context_popup.lineWidth = BORDER_SIZE_AGENT_BOX;
  context_popup.strokeStyle = color_status_node;
  context_popup.stroke();

  if (mode_show == "all") {
    draw_snmp_modules();
    draw_modules();
  } else if (mode_show == "status_module") {
    draw_snmp_modules();
  }
}

function draw_snmp_modules() {
  module_pos_y = MARGIN_BETWEEN_AGENT_MODULE;

  count = 0;
  reset_column = true;

  jQuery.each(modules, function(key, module) {
    if (typeof module == "undefined") return;

    if (module.type != 18) return;

    if (count < count_snmp_modules / 2) {
      module_pos_x = pos_x - 15;
      text_align = "right";
      margin_text = 5;
    } else {
      if (reset_column) {
        module_pos_y = MARGIN_BETWEEN_AGENT_MODULE;
        reset_column = false;
      }
      module_pos_x = pos_x + box_width - 15;
      text_align = "left";
      margin_text = SIZE_MODULE - 5;
    }
    count++;

    context_popup.beginPath();
    context_popup.rect(
      module_pos_x,
      module_pos_y + offset_y,
      SIZE_MODULE,
      SIZE_MODULE
    );
    context_popup.fillStyle = module.status_color;
    context_popup.fill();
    context_popup.lineWidth = 1;
    context_popup.strokeStyle = "#000";
    context_popup.stroke();

    modules[key].pos_x = module_pos_x;
    modules[key].pos_y = module_pos_y + offset_y;

    context_popup.fillStyle = "rgb(0,0,0)";
    context_popup.font = "bold 10px sans-serif";
    context_popup.textBaseline = "middle";
    context_popup.textAlign = text_align;
    dimensions = context_popup.measureText(module.text);
    text_pos_x = module_pos_x + margin_text;
    text_pos_y = module_pos_y + 40 + offset_y;
    context_popup.fillText(module.text, text_pos_x, text_pos_y);

    module_pos_y = module_pos_y + VERTICAL_SPACE_MODULES;
  });
}

function draw_modules() {
  module_pos_y = MARGIN_BETWEEN_AGENT_MODULE;

  count = 0;
  reset_column = true;

  jQuery.each(modules, function(key, module) {
    if (typeof module == "undefined") return;

    if (module.type == 18) return;

    if (count < count_no_snmp / 2) {
      module_pos_x = pos_x + (HORIZONTAL_SPACE_MODULES - SIZE_MODULE) / 2;
      text_pos_x = pos_x + HORIZONTAL_SPACE_MODULES / 2;
    } else {
      if (reset_column) {
        module_pos_y = MARGIN_BETWEEN_AGENT_MODULE;
        reset_column = false;
      }
      module_pos_x =
        pos_x +
        (box_width - HORIZONTAL_SPACE_MODULES) +
        (HORIZONTAL_SPACE_MODULES - SIZE_MODULE) / 2;
      text_pos_x =
        pos_x +
        (box_width - HORIZONTAL_SPACE_MODULES) +
        HORIZONTAL_SPACE_MODULES / 2;
    }
    count++;

    context_popup.beginPath();
    center_orig_x = module_pos_x + SIZE_MODULE / 2;
    center_orig_y = module_pos_y + offset_y + SIZE_MODULE / 2;
    radius = SIZE_MODULE / 2;
    context_popup.arc(
      center_orig_x,
      center_orig_y,
      radius,
      0,
      Math.PI * 2,
      false
    );
    //context_popup.rect(module_pos_x, module_pos_y + offset_y, SIZE_MODULE, SIZE_MODULE);
    context_popup.fillStyle = module.status_color;
    context_popup.fill();
    context_popup.lineWidth = 1;
    context_popup.strokeStyle = "#000";
    context_popup.stroke();

    modules[key].pos_x = module_pos_x;
    modules[key].pos_y = module_pos_y + offset_y;

    context_popup.fillStyle = "rgb(0,0,0)";
    context_popup.font = "bold 10px sans-serif";
    context_popup.textBaseline = "middle";
    context_popup.textAlign = "center";
    dimensions = context_popup.measureText(module.short_text);

    text_pos_y = module_pos_y + 40 + offset_y;
    context_popup.fillText(module.short_text, text_pos_x, text_pos_y);

    module_pos_y = module_pos_y + VERTICAL_SPACE_MODULES;
  });

  paint_tooltip_module_one_time = false;
}

function update_fictional_node_popup(id) {
  name = $("#text-fictional_name").val();
  shape = $("#fictional_shape option:selected").val();
  networmap = $("#networmaps_enterprise option:selected").val();
  radious = $("#fictional_radious").val();
  color = $("#fictional_color").val();

  window.close();

  window.opener.update_fictional_node(
    id,
    name,
    shape,
    networmap,
    radious,
    color
  );
}

function move_to_networkmap_widget(networkmap_id, id_cell) {
  var params = [];

  $(".widget_content").each(function(i) {
    $("#body_cell").empty();
  });

  params.push("networkmap=true");
  params.push("networkmap_id=" + networkmap_id);

  params.push("page=include/ajax/map_enterprise.ajax");
  jQuery.ajax({
    data: params.join("&"),
    dataType: "html",
    type: "POST",
    url: window.base_url_homedir + "/ajax.php",
    success: function(data) {
      $(".widget_content").each(function(i) {
        $("#body_cell").append(data);
      });
    }
  });
}
