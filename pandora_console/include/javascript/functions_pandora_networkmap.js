
function draw_minimap() {
	//Clean the canvas
	context_minimap.clearRect(0, 0, minimap_w, minimap_h);
	
	context_minimap.beginPath();
	context_minimap.globalAlpha = 0.8;
	context_minimap.fillStyle = "#ddd";
	context_minimap.fillRect(0, 0, minimap_w, minimap_h);
	
	var relation_min_nodes = minimap_relation;
	var relation_minimap_w = 2;
	var relation_minimap_h = 2;
	if (graph.nodes.length > 100) {
		relation_min_nodes = 0.01;
		relation_minimap_w = (graph.nodes.length / 100) * 2.5;
		relation_minimap_h = 1.5;
	}
	
	//Draw the items and lines
	jQuery.each(graph.nodes, function (key, value) {
		if (typeof(value) == 'undefined') return;
		
		context_minimap.beginPath();
		//Paint the item
		if (graph.nodes.length > 100) {
			center_orig_x = (value.x + value.image_width / 4) * relation_min_nodes + minimap_w / relation_minimap_w;
			center_orig_y = (value.y + value.image_height / 4) * relation_min_nodes + minimap_h / relation_minimap_h;
		}
		else {
			center_orig_x = (value.x + value.image_width / 2) * relation_min_nodes;
			center_orig_y = (value.y + value.image_height / 2) * relation_min_nodes;
		}
		
		context_minimap.arc(center_orig_x,
			center_orig_y, 2, 0, Math.PI * 2, false);
		//Check if the pandora point
		if (value.id_agent == -1) {
			context_minimap.fillStyle = "#364D1F";
		}
		else {
			context_minimap.fillStyle = "#000";
		}
		context_minimap.fill();
	});
	
	if (graph.nodes.length > 100) {
		//Draw the rect of viewport
		context_minimap.beginPath();
		context_minimap.strokeStyle = "#3f3f3f";
		context_minimap.strokeRect(
			(-translation[0] / scale) * relation_min_nodes + minimap_w / relation_minimap_w,
			(-translation[1] / scale) * relation_min_nodes + minimap_h / relation_minimap_h,
			width_svg * relation_min_nodes / scale,
			height_svg * relation_min_nodes / scale);
	}
	else {
		//Draw the rect of viewport
		context_minimap.beginPath();
		context_minimap.strokeStyle = "#f00";
		context_minimap.strokeRect(
			(-translation[0] / scale) * relation_min_nodes,
			(-translation[1] / scale) * relation_min_nodes,
			width_svg * relation_min_nodes / scale,
			height_svg * relation_min_nodes / scale);
	}
		
	context_minimap.beginPath();
	context_minimap.strokeStyle = "#82B92E";
	context_minimap.strokeRect(
		(networkmap_dimensions[0] + node_radius - holding_area_dimensions[0]) * minimap_relation,
		(networkmap_dimensions[1] + node_radius - holding_area_dimensions[1]) * minimap_relation,
		holding_area_dimensions[0] * minimap_relation,
		holding_area_dimensions[1] * minimap_relation)
	
	context_minimap.globalAlpha = 1;
}

function inner_minimap_box(param_x, param_y) {
	if ((param_x + translation[0] * minimap_relation >= 0)
		&& (param_x + translation[0] * minimap_relation <= width_svg * minimap_relation)
		&& (param_y + translation[1] * minimap_relation >= 0)
		&& (param_y + translation[1] * minimap_relation <= height_svg * minimap_relation)) {
		return true;
	}
	
	return false;
}

function set_center(id) {
	pos_x = (width_svg / 2) - translation[0];
	pos_y = (height_svg / 2) - translation[1];
	
	var params = [];
	params.push("set_center=1");
	params.push("id=" + id);
	params.push("x=" + pos_x);
	params.push("y=" + pos_y);
	params.push("page=operation/agentes/pandora_networkmap.view");
	jQuery.ajax ({
		data: params.join ("&"),
		dataType: 'json',
		type: 'POST',
		url: action="ajax.php",
		success: function (data) {
			if (data['correct']) {
			}
		}
	});
}

function get_relations(node_param) {
	var return_links = [];
	jQuery.each(graph.links, function(i, link_each) {
		if (node_param.id == link_each.source.id) {
			return_links.push(link_each);
		}
		else if (node_param.id == link_each.target.id) {
			return_links.push(link_each);
		}
	});
	
	return return_links;
}

function delete_link(source_id, source_module_id, target_id, target_module_id, id_link) {
	if (enterprise_installed) {
		var params = [];
		params.push("delete_link=1");
		params.push("networkmap_id=" + networkmap_id);
		params.push("source_id=" + source_id);
		params.push("source_module_id=" + source_module_id);
		params.push("target_id=" + target_id);
		params.push("target_module_id=" + target_module_id);
		params.push("id_link=" + id_link);
		params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
		jQuery.ajax ({
			data: params.join ("&"),
			dataType: 'json',
			type: 'POST',
			url: action="ajax.php",
			success: function (data) {
				if (data['correct']) {
					
					do {
						found = -1;
						
						jQuery.each(graph.links, function(i, element) {
							if ((element.source.id_db == source_id)
								&& (element.target.id_db == target_id)) {
								found = i;
							}
						});
						if (found != -1)
							graph.links.splice(found, 1);
					}
					while (found != -1);
					
					draw_elements_graph();
					set_positions_graph();
				}
				
				$("#dialog_node_edit").dialog("close");
			}
		});
	}
	else {
		do {
			found = -1;
			
			jQuery.each(graph.links, function(i, element) {
				if ((element.source.id_db == source_id)
					&& (element.target.id_db == target_id)) {
					found = i;
				}
			});
			if (found != -1)
				graph.links.splice(found, 1);
		}
		while (found != -1);
		
		draw_elements_graph();
		set_positions_graph();
	}
	
}

function update_fictional_node(id_db_node) {
	if (enterprise_installed) {
		var name = $("input[name='edit_name_fictional_node']").val();
		var networkmap_to_link = $("#edit_networkmap_to_link").val();
		
		var params = [];
		params.push("update_fictional_node=1");
		params.push("networkmap_id=" + networkmap_id);
		params.push("node_id=" + id_db_node);
		params.push("name=" + name);
		params.push("networkmap_to_link=" + networkmap_to_link);
		params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
		
		jQuery.ajax ({
			data: params.join ("&"),
			dataType: 'json',
			type: 'POST',
			url: action="ajax.php",
			success: function (data) {
				if (data['correct']) {
					$("#dialog_node_edit").dialog("close");
					
					jQuery.each(graph.nodes, function(i, element) {
						if (element.id_db == id_db_node) {
							graph.nodes[i].text = name;
							graph.nodes[i].networkmap_id = networkmap_to_link;
							
							$("#id_node_" + i + networkmap_id + " title").html(name);
							$("#id_node_" + i + networkmap_id + " tspan").html(name);
						}
					});
					
					draw_elements_graph();
					set_positions_graph();
				}
			}
		});
	}
}

function change_shape(id_db_node) {
	if (enterprise_installed) {
		var shape = $("select[name='shape']").val();
		
		var params = [];
		params.push("change_shape=1");
		params.push("networkmap_id=" + networkmap_id);
		params.push("id=" + id_db_node);
		params.push("shape=" + shape);
		params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
		
		$("#shape_icon_correct").css("display", "none");
		$("#shape_icon_fail").css("display", "none");
		$("#shape_icon_in_progress").css("display", "");
		
		jQuery.ajax ({
			data: params.join ("&"),
			dataType: 'json',
			type: 'POST',
			url: action="ajax.php",
			success: function (data) {
				$("#shape_icon_in_progress").css("display", "none");
				if (data['correct']) {
					$("#shape_icon_correct").css("display", "");
					
					count = graph.nodes.length;
					
					jQuery.each(graph.nodes, function(i, element) {
						if (element.id_db == id_db_node) {
							graph.nodes[i].shape = shape;
							
							$("#id_node_" + element.id + networkmap_id + " rect").remove();
							$("#id_node_" + element.id + networkmap_id + " circle").remove();
							$("#id_node_" + element.id + networkmap_id + " image").remove();
							
							if (shape == 'circle') {
								d3.select("#id_node_" + element.id + networkmap_id)
								.insert("circle", "title")
									.attr("r", node_radius)
									.attr("class", "node_shape node_shape_circle")
									.style("fill", function(d) {
											return d.color;
										})
									.classed('dragable_node', true) //own dragable
									.on("mouseover", function(d) {
										myMouseoverCircleFunction(d.id)
										})
									.on("mouseout", function(d) {
										myMouseoutCircleFunction(d.id)
										})
									.on("click", selected_node)
									.on("dblclick", function(d) {
											edit_node(d, true);
										})
									.on("contextmenu", function(d) { show_menu("node", d);});
									
								d3.select("#id_node_" + element.id + networkmap_id)
									.append("image")
									.attr("class", "node_image")
									.attr("xlink:href", function(d) {
											return d.image_url;
										})
									.attr("x", function(d) {
											return d.x - (d.image_width / 2);
										})
									.attr("y", function(d) {
											return d.y - (d.image_height / 2);
										})
									.attr("width", function(d) {
											return (node_radius / 0.8);
										})
									.attr("height", function(d) {
											return (node_radius / 0.8);
										})
									.attr("node_id", function(d) {
											return d.id;
										})
									.attr("id", "image2995")
									.classed('dragable_node', true) //own dragable
									.on("mouseover", function(d) {
										myMouseoverCircleFunction(d.id)
										})
									.on("mouseout", function(d) {
										myMouseoutCircleFunction(d.id)
										})
									.on("click", selected_node)
									.on("dblclick", function(d) {
											edit_node(d, true);
										})
									.on("contextmenu", function(d) { show_menu("node", d);});
								
							}
							else if (shape == 'square') {
								d3.select("#id_node_" + element.id + networkmap_id)
									.insert("rect", "title")
										.attr("width", node_radius * 2)
										.attr("height", node_radius * 2)
										.attr("class", "node_shape node_shape_square")
										.style("fill", function(d) {
												return d.color;
											})
										.classed('dragable_node', true) //own dragable
										.on("mouseover", function(d) {
											myMouseoverSquareFunction(d.id)
											})
										.on("mouseout", function(d) {
											myMouseoutSquareFunction(d.id)
											})
										.on("click", selected_node)
										.on("dblclick", function(d) {
												edit_node(d, true);
											})
										.on("contextmenu", function(d) { show_menu("node", d);});
										
								d3.select("#id_node_" + element.id + networkmap_id)
									.append("image")
									.attr("class", "node_image")
									.attr("xlink:href", function(d) {
											return d.image_url;
										})
									.attr("x", function(d) {
											return d.x - (d.image_width / 2);
										})
									.attr("y", function(d) {
											return d.y - (d.image_height / 2);
										})
									.attr("width", function(d) {
											return (node_radius / 0.8);
										})
									.attr("height", function(d) {
											return (node_radius / 0.8);
										})
									.attr("node_id", function(d) {
											return d.id;
										})
									.attr("id", "image2995")
									.classed('dragable_node', true) //own dragable
									.on("mouseover", function(d) {
										myMouseoverSquareFunction(d.id)
										})
									.on("mouseout", function(d) {
										myMouseoutSquareFunction(d.id)
										})
									.on("click", selected_node)
									.on("dblclick", function(d) {
											edit_node(d, true);
										})
									.on("contextmenu", function(d) { show_menu("node", d);});
								
							}
							else if (shape == 'rhombus') {
								d3.select("#id_node_" + element.id + networkmap_id)
									.insert("rect", "title")
										.attr("transform",
											"")
										.attr("width", node_radius * 1.5)
										.attr("height", node_radius * 1.5)
										.attr("class", "node_shape node_shape_rhombus")
										.style("fill", function(d) {
												return d.color;
											})
										.classed('dragable_node', true) //own dragable
										.on("mouseover", function(d) {
											myMouseoverRhombusFunction(d.id)
											})
										.on("mouseout", function(d) {
											myMouseoutRhombusFunction(d.id)
											})
										.on("click", selected_node)
										.on("dblclick", function(d) {
												edit_node(d, true);
											})
										.on("contextmenu", function(d) { show_menu("node", d);});
										
								d3.select("#id_node_" + element.id + networkmap_id)
									.append("image")
									.attr("class", "node_image")
									.attr("xlink:href", function(d) {
											return d.image_url;
										})
									.attr("x", function(d) {
											return d.x - (d.image_width / 2);
										})
									.attr("y", function(d) {
											return d.y - (d.image_height / 2);
										})
									.attr("width", function(d) {
											return (node_radius / 0.8);
										})
									.attr("height", function(d) {
											return (node_radius / 0.8);
										})
									.attr("node_id", function(d) {
											return d.id;
										})
									.attr("id", "image2995")
									.classed('dragable_node', true) //own dragable
									.on("mouseover", function(d) {
										myMouseoverRhombusFunction(d.id)
										})
									.on("mouseout", function(d) {
										myMouseoutRhombusFunction(d.id)
										})
									.on("click", selected_node)
									.on("dblclick", function(d) {
											edit_node(d, true);
										})
									.on("contextmenu", function(d) { show_menu("node", d);});	
							}
							
						}
						
						count = count - 1;
						if (count == 0) {
							draw_elements_graph();
							set_positions_graph();
						}
					});
				}
				else {
					$("#shape_icon_fail").css("display", "");
				}
			}
		});
	}
}

function update_link(row_index, id_link) {
	var interface_source = parseInt(
		$("select[name='interface_source_" + row_index + "']")
			.val()
		);
	
	var text_source_interface = "";
	if (interface_source != 0) {
		text_source_interface = $("select[name='interface_source_" +
			row_index + "'] option:selected").text();
	}
	
	var interface_target = parseInt(
		$("select[name='interface_target_" + row_index + "']")
			.val()
		);
	
	var text_target_interface = "";
	if (interface_source != 0) {
		text_source_interface = $("select[name='interface_source_" +
			row_index + "'] option:selected").text();
	}
	
	$(".edit_icon_progress_" + row_index).css("display", "");
	$(".edit_icon_" + row_index).css("display", "none");
	
	var params = [];
	params.push("update_link=1");
	params.push("networkmap_id=" + networkmap_id);
	params.push("id_link=" + id_link);
	params.push("interface_source=" + interface_source);
	params.push("interface_target=" + interface_target);
	params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
	
	jQuery.ajax ({
		data: params.join ("&"),
		dataType: 'json',
		type: 'POST',
		url: action="ajax.php",
		success: function (data) {
			$(".edit_icon_progress_" + row_index).css("display", "none");
			
			if (data['correct']) {
				$(".edit_icon_correct_" + row_index).css("display", "");
				
				$("select[name='interface_source_" + row_index + networkmap_id + "'] option[value='" + interface_source + "']")
					.prop("selected", true);
				$("select[name='interface_target_" + row_index + networkmap_id + "'] option[value='" + interface_target + "']")
					.prop("selected", true);
				
				
				
				if (interface_source == 0) {
					jQuery.each(graph.links, function(i, link_each) {
						if (link_each.id_db == id_link) {
							//Found
							graph.links[i].arrow_start = "";
							graph.links[i].arrow_start = "";
							graph.links[i].text_start = text_source_interface;
							
							//Remove the arrow
							$("#link_id_" + id_link)
								.attr("marker-start", "");
							
							$("tspan")
								.filter(function() {
									var textPath = $(this).parent();
									if ($(textPath).attr('href') == "#link_id_" + id_link)
										return true;
									else return false;
								})
								.html(Array(25).join(" ") + text_source_interface);
						}
					});
				}
				else {
					jQuery.each(graph.links, function(i, link_each) {
						if (link_each.id_db == id_link) {
							//Found
							if (link_each.arrow_start == "") {
								graph.links[i].id_db = data['id_link_change'];
							}
							
							graph.links[i].arrow_start = "module";
							graph.links[i].id_module_start = interface_source;
							graph.links[i].text_start = text_source_interface;
							
							//Added th arrow
							$("#link_id_" + id_link)
								.attr("marker-start",
									"url(#interface_start_1)");
							
							$("tspan")
								.filter(function() {
									var textPath = $(this).parent();
									
									if ($(textPath).attr('href') == "#link_id_" + id_link)
										return true;
									else return false;
								})
								.html(Array(25).join(" ") + text_source_interface);
						}
					});
				}
				
				if (interface_target == 0) {
					jQuery.each(graph.links, function(i, link_each) {
						if (link_each.id_db == id_link) {
							//Found
							graph.links[i].arrow_end = "";
							graph.links[i].id_module_end = 0;
							graph.links[i].text_end = text_target_interface;
							
							//Remove the arrow
							$("#link_id_" + id_link)
								.attr("marker-end", "");
							
							$("tspan")
								.filter(function() {
									var textPath = $(this).parent();
									
									if ($(textPath).attr('href') == "#link_reverse_id_" + id_link)
										return true;
									else return false;
								})
								.html(Array(25).join(" ") + text_target_interface);
						}
					});
				}
				else {
					jQuery.each(graph.links, function(i, link_each) {
						if (link_each.id_db == id_link) {
							
							//Found
							
							if (link_each.arrow_end == "") {
								graph.links[i].id_db = data['id_link_change'];
							}
							
							graph.links[i].arrow_end = "module";
							graph.links[i].id_module_end = interface_target;
							graph.links[i].text_end = text_target_interface;
							
							//Added th arrow
							$("#link_id_" + id_link)
								.attr("marker-end",
									"url(#interface_end_1)");
							
							$("tspan")
								.filter(function() {
									var textPath = $(this).parent();
									
									if ($(textPath).attr('href') == "#link_reverse_id_" + id_link)
										return true;
									else return false;
								})
								.html(Array(25).join(" ") + text_target_interface);
						}
					});
				}
				
				draw_elements_graph();
				set_positions_graph();
			}
			else {
				$(".edit_icon_fail_" + row_index).css("display", "");
			}
		}
	});
}

function edit_node(data, dblClick) {
	if (enterprise_installed) {
		var flag_edit_node = true;
		var edit_node = null
		
		//Only select one node
		var selection = d3.selectAll('.node_selected');
		
		if (selection[0].length == 1) {
			edit_node = selection[0].pop();
		}
		else if (selection[0].length > 1) {
			edit_node = selection[0].pop();
		}
		else if (dblClick){
			edit_node = d3.select("#id_node_" + data['id'] + networkmap_id);
			edit_node = edit_node[0][0];
		}
		else {
			flag_edit_node = false;
		}
		
		if (flag_edit_node) {
			d3.selectAll('.node_selected')
				.classed("node_selected", false);
			d3.select(edit_node)
				.classed("node_selected", true);
			
			id = d3.select(edit_node).attr("id").replace("id_node_", "");
			id = id.replace(networkmap_id, "");
			node_selected = graph.nodes[id];
			
			selected_links = get_relations(node_selected);
			
			$("select[name='shape'] option[value='" + data.shape + "']")
				.prop("selected", true);
			$("select[name='shape']").attr("onchange",
				"javascript: change_shape(" + data.id_db + ");");
			$("#node_options-fictional_node_update_button-1 input")
				.attr("onclick", "update_fictional_node(" + data.id_db + ");");
			
			$("#node_details-0-1").html(data["text"]);
			var params = [];
			params.push("get_agent_info=1");
			params.push("id_agent=" + data["id_agent"]);
			params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
			jQuery.ajax ({
				data: params.join ("&"),
				dataType: 'json',
				type: 'POST',
				url: action="ajax.php",
				async: false,
				success: function (data) {
					var adressess = "";
					for (adress in data['adressess']) {
						adressess += adress + "<br>";
					}
					$("#node_details-1-1").html(adressess);
					$("#node_details-2-1").html(data["os"]);
					$("#node_details-3-1").html(data["group"]);
				}
			});
			
			$("#interface_information").find("tr:gt(0)").remove();
			
			var params = [];
			params.push("get_interface_info=1");
			params.push("id_agent=" + data["id_agent"]);
			params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
			jQuery.ajax ({
				data: params.join ("&"),
				dataType: 'json',
				type: 'POST',
				url: action="ajax.php",
				async: false,
				success: function (data) {
					if (data.length == 0) {
						$("#interface_information").find('tbody')
							.append($('<tr>').html("<p style=\"text-align: center;\">It has no interface to display</p>"));
					}
					else {
						jQuery.each(data, function(j, interface) {
							$("#interface_information").find('tbody')
								.append($('<tr>')
									.append($('<td>')
										.html(interface['name'])
									)
									.append($('<td>')
										.html(interface['status'])
									)
									.append($('<td>')
										.html(interface['graph'])
									)
									.append($('<td>')
										.html(interface['ip'])
									)
									.append($('<td>')
										.html(interface['mac'])
									)
								);
						});
					}
				}
			});
			
			$("#dialog_node_edit" )
				.dialog( "option", "title",
					dialog_node_edit_title.replace("%s", data.text));
			$("#dialog_node_edit").dialog("open");
			
			if (data.id_agent == -2) {
				//Fictional node
				$("#node_options-fictional_node_name")
					.css("display", "");
				$("input[name='edit_name_fictional_node']")
					.val(data.text);
				$("#node_options-fictional_node_networkmap_link")
					.css("display", "");
				$("#edit_networkmap_to_link")
					.val(data.networkmap_id);
				$("#node_options-fictional_node_update_button")
					.css("display", "");
			}
			else {
				$("#node_options-fictional_node_name")
					.css("display", "none");
				$("#node_options-fictional_node_networkmap_link")
					.css("display", "none");
				$("#node_options-fictional_node_update_button")
					.css("display", "none");
			}
			
			//Clean
			$("#relations_table .relation_link_row").remove();
			//Show the no relations
			$("#relations_table-loading").css('display', 'none');
			$("#relations_table-no_relations").css('display', '');
			
			
			jQuery.each(selected_links, function(i, link_each) {
				
				$("#relations_table-no_relations").css('display', 'none');
				$("#relations_table-loading").css('display', '');
				
				var template_relation_row = $("#relations_table-template_row")
					.clone();
				
				$(template_relation_row).css('display', '');
				$(template_relation_row).attr('class', 'relation_link_row');
				
				$("select[name='interface_source']", template_relation_row)
					.attr('name', "interface_source_" + i)
					.attr('id', "interface_source_" + i + networkmap_id);
				$("select[name='interface_target']", template_relation_row)
					.attr('name', "interface_target_" + i)
					.attr('id', "interface_target_" + i + networkmap_id);
				$(".edit_icon_progress", template_relation_row)
					.attr('class', "edit_icon_progress_" + i);
				$(".edit_icon", template_relation_row)
					.attr('class', "edit_icon_" + i);
				$(".edit_icon_correct", template_relation_row)
					.attr('class', "edit_icon_correct_" + i);
				$(".edit_icon_fail", template_relation_row)
					.attr('class', "edit_icon_fail_" + i);
				$(".edit_icon_link", template_relation_row)
					.attr('class', "edit_icon_link_" + i)
					.attr('href', 'javascript: update_link(' + i + "," + link_each.id_db + ');');
				
				
				var params = [];
				params.push("get_intefaces=1");
				params.push("id_agent=" + link_each.source.id_agent);
				params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
				
				jQuery.ajax ({
					data: params.join ("&"),
					dataType: 'json',
					type: 'POST',
					url: action="ajax.php",
					async: false,
					success: function (data) {
						if (data['correct']) {
							jQuery.each(data['interfaces'], function(j, interface) {
								
								$("select[name='interface_source_" + i + "']", template_relation_row)
									.append($("<option>")
										.attr("value", interface['id_agente_modulo'])
										.html(interface['nombre']));
								
								if (interface.id_agente_modulo == link_each.id_module_start) {
									$("select[name='interface_source_" + i + "'] option[value='" + interface['id_agente_modulo'] + "']", template_relation_row)
										.prop("selected", true);
								}
							});
						}
					}
				});
				
				var params = [];
				params.push("get_intefaces=1");
				params.push("id_agent=" + link_each.target.id_agent);
				params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
				
				jQuery.ajax ({
					data: params.join ("&"),
					dataType: 'json',
					type: 'POST',
					url: action="ajax.php",
					async: false,
					success: function (data) {
						if (data['correct']) {
							jQuery.each(data['interfaces'], function(j, interface) {
								
								$("select[name='interface_target_" + i + "']", template_relation_row)
									.append($("<option>")
										.attr("value", interface['id_agente_modulo'])
										.html(interface['nombre']));
								
								if (interface.id_agente_modulo == link_each.id_module_end) {
									$("select[name='interface_target_" + i + "'] option[value='" + interface['id_agente_modulo'] + "']", template_relation_row)
										.prop("selected", true);
								}
							});
							
						}
					}
				});
				
				$("#relations_table-template_row-node_source", template_relation_row)
					.html(link_each.source.text);
				$("#relations_table-template_row-node_target", template_relation_row)
					.html(link_each.target.text);
				$("#relations_table-template_row-edit", template_relation_row)
					.attr("align", "center");
				$("#relations_table-template_row-edit .delete_icon", template_relation_row)
					.attr("href", "javascript: " +
						"delete_link(" +
							link_each.source.id_db + "," +
							link_each.id_module_start + "," +
							link_each.target.id_db + "," +
							link_each.id_module_end + "," +
							link_each.id_db + ");");
				$("#relations_table tbody").append(template_relation_row);
				
				template_relation_row = null;
			});
			
			$("#relations_table-loading").css('display', 'none');	
		}
	}
}

function add_node() {
	$("#agent_name").val("");
	
	$("input[name='name_fictional_node']").val("");
	$("#networkmap_to_link").val(0);
	
	$("#dialog_node_add").dialog("open");
}

function add_agent_node_from_the_filter_group() {
	agents = $("select[name='agents_filter_group']").val();
	
	add_agent_node(agents);
}

function add_agent_node(agents) {
	var id_agents = [];
	
	if (typeof(agents) == 'undefined') {
		id_agents.push($("input[name='id_agent']").val());
	}
	else {
		if (typeof(agents) == "object") {
			//Several agents
			if (agents.length == 1) {
				id_agents.push(agents.pop());
			}
			else if (agents.length == 0) {
				//empty list
			}
			else {
				id_agents = agents;
			}
		}
		else if (typeof(agents) == "string") {
			id_agents.push(agents);
		}
		else if (typeof(agents) == "number") {
			id_agents.push(agents);
		}
		else {
			id_agents = agents;
		}
	}
	var x = (click_menu_position_svg[0] - translation[0]) / scale;
	var y = (click_menu_position_svg[1] - translation[1]) / scale;
	
	if (enterprise_installed) {
		jQuery.each(id_agents, function(i, id_agent) {
			x = x + (i * 20);
			y = y + (i * 20);
			
			var params = [];
			params.push("add_agent=1");
			params.push("id=" + networkmap_id);
			params.push("id_agent=" + id_agent);
			params.push("x=" + x);
			params.push("y=" + y);
			params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
			jQuery.ajax ({
				data: params.join ("&"),
				dataType: 'json',
				type: 'POST',
				url: action="ajax.php",
				success: function (data) {
					if (data['correct']) {
						$("#agent_name").val('');
						$("#dialog_node_add").dialog("close");
						
						var temp_node = {};
						temp_node['id'] = graph.nodes.length;
						temp_node['id_db'] = data['id_node'];
						temp_node['id_agent'] = data['id_agent'];
						temp_node['id_module'] = "";
						temp_node['px'] = data['x'];
						temp_node['py'] = data['y'];
						temp_node['x'] = data['x'];
						temp_node['y'] = data['y'];
						temp_node['z'] = 0;
						temp_node['fixed'] = true;
						temp_node['type'] = 0;
						temp_node['color'] = data['status'];
						temp_node['shape'] = data['shape'];
						temp_node['text'] = data['text'];
						temp_node['image_url'] = data['image_url'];
						temp_node['image_width'] = data['width'];
						temp_node['image_height'] = data['height'];
						temp_node['map_id'] = data['map_id'];
						temp_node['state'] = data['state'];
						
						graph.nodes.push(temp_node);
						/* FLECHAS EMPEZADO PARA MEJORAR
						jQuery.each(data['rel'], function(i, relation) {
							var temp_link = {};
							temp_link['id_db'] = String(relation['id_db']);
							temp_link['id_agent_end'] = String(relation['id_agent_end']);
							temp_link['id_agent_start'] = String(relation['id_agent_start']);
							temp_link['id_module_end'] = relation['id_module_end'];
							temp_link['id_module_start'] = relation['id_module_start'];
							temp_link['source'] = relation['source'];
							temp_link['target'] = relation['target'];
							temp_link['source_in_db'] = String(relation['source_in_db']);
							temp_link['target_in_db'] = String(relation['target_in_db']);
							temp_link['arrow_end'] = relation['arrow_end'];
							temp_link['arrow_start'] = relation['arrow_start'];
							temp_link['status_end'] = relation['status_end'];
							temp_link['status_start'] = relation['status_start'];
							temp_link['text_end'] = relation['text_end'];
							temp_link['text_start'] = relation['text_start'];
							
							graph.links.push(temp_link);
						});
						*/
						
						draw_elements_graph();
						init_drag_and_drop();
						set_positions_graph();
					}
				}
			});
		});
	}
	else {
		$("#agent_name").val('');
		$("#dialog_node_add").dialog("close");
		
		var temp_node = {};
		temp_node['id'] = graph.nodes.length;
		temp_node['id_db'] = data['id_node'];
		temp_node['id_agent'] = data['id_agent'];
		temp_node['id_module'] = "";
		temp_node['px'] = data['x'];
		temp_node['py'] = data['y'];
		temp_node['x'] = data['x'];
		temp_node['y'] = data['y'];
		temp_node['z'] = 0;
		temp_node['fixed'] = true;
		temp_node['type'] = 0;
		temp_node['color'] = data['status'];
		temp_node['shape'] = data['shape'];
		temp_node['text'] = data['text'];
		temp_node['image_url'] = data['image_url'];
		temp_node['image_width'] = data['width'];
		temp_node['image_height'] = data['height'];
		temp_node['map_id'] = data['map_id'];
		temp_node['state'] = data['state'];
		
		graph.nodes.push(temp_node);
	}
}

function hide_labels() {
	if (show_labels) {
		hide_labels_function();
	}
	else {
		show_labels_function();
	}
}

function hide_labels_function() {
	show_labels = false;
	
	//Change the image arrow
	$("#hide_labels_" + networkmap_id + " > a").attr("title", "Show Labels");
	$("#image_hide_show_labels" + networkmap_id).attr("src", "images/icono_refresh_networkmaps.png");
	
	d3.selectAll(".node_text").style("display", "none");
}

function show_labels_function() {
	show_labels = true;
	
	//Change the image arrow
	$("#hide_labels_" + networkmap_id + " > a").attr("title", "Hide Labels");
	$("#image_hide_show_labels" + networkmap_id).attr("src", "images/icono_delete_networkmaps.png");
	
	d3.selectAll(".node_text").style("display", "");
}

function toggle_minimap() {	
	if (show_minimap) {
		function_close_minimap();
	}
	else {
		function_open_minimap();
	}
}

function function_open_minimap() {
	show_minimap = true;
	
	//Change the image arrow
	$("#arrow_minimap_" + networkmap_id + " > a").attr("title", "Close Minimap");
	$("#image_arrow_minimap_" + networkmap_id).attr("src", "images/minimap_close_arrow.png");
	
	$("#minimap_" + networkmap_id).show();
	
	draw_minimap();
}

function function_close_minimap() {
	show_minimap = false;
	
	//Change the image arrow
	$("#arrow_minimap_" + networkmap_id + " > a").attr("title", "Open Minimap");
	$("#image_arrow_minimap_" + networkmap_id).attr("src", "images/minimap_open_arrow.png");
	
	$("#minimap_" + networkmap_id).hide();
}

function delete_nodes() {
	if (enterprise_installed) {
		var selection = d3.selectAll('.node_selected');
		selection
			.each(function(d) {
				//Avoid to delete pandora node center
				if (d.id_agent == -1) {
					return;
				}
				
				var params = [];
				params.push("id=" + d.id_db);
				params.push("delete_node=1");
				params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
				jQuery.ajax ({
					data: params.join ("&"),
					dataType: 'json',
					type: 'POST',
					url: action="ajax.php",
					success: function (data) {
						if (data['correct']) {
							do {
								found = -1;
								jQuery.each(graph.links, function(i, element) {
									if (element.target.id == d.id) {
										found = i;
									}
								});
								if (found != -1)
									graph.links.splice(found, 1);
							}
							while (found != -1);
							
							do {
								found = -1;
								jQuery.each(graph.links, function(i, element) {
									if (element.source.id == d.id) {
										found = i;
									}
								});
								if (found != -1)
									graph.links.splice(found, 1);
							}
							while (found != -1);
							
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
}

function zoom(manual) {
	if (typeof(manual) == 'undefined') {
		manual = false;
	}
	
	if (manual) {
		layer_graph.attr("transform",
			"translate(" + translation + ")scale(" + scale + ")");
		
		zoom_obj.translate(translation);
		zoom_obj.scale(scale);
		
		draw_minimap();
	}
	else {
		if (!disabled_drag_zoom) {
			translation[0] = d3.event.translate[0];
			translation[1] = d3.event.translate[1];
			scale = d3.event.scale;
			
			zoom_obj.translate(translation);
			zoom_obj.scale(scale);
			
			layer_graph.attr("transform",
				"translate(" + translation + ")scale(" + scale + ")");
			
			draw_minimap();
		}
		else {
			//Keep the translation before to start to dragging
			zoom_obj.translate(translation);
		}
	}
}

function set_positions_graph() {
	link.selectAll("path.link")
		.attr("d", function(d) {
			
			return "M " + d.source.x + " " + d.source.y +
				" L " + d.target.x + " " + d.target.y;
		});
	
	link.selectAll("path.link_reverse")
		.attr("d", function(d) {
			
			return "M " + d.target.x + " " + d.target.y +
				" L " + d.source.x + " " + d.source.y;
		});
	
	node.selectAll(".node_shape_circle")
		.attr("cx", function(d) {
			return d.x;
		})
		.attr("cy", function(d) {
			return d.y;
		});
	
	node.selectAll(".node_shape_square")
		.attr("x", function(d) {
			return d.x - node_radius;
		})
		.attr("y", function(d) {
			return d.y - node_radius;
		});
	
	node.selectAll(".node_shape_rhombus")
		.attr("x", function(d) {
			return d.x - node_radius / 1.25;
		})
		.attr("y", function(d) {
			return d.y - node_radius / 1.25;
		})
		.attr("transform", function(d) {
			return "rotate(45 " + d.x + " " + d.y + ")";
		});
	
	node.selectAll(".node_image")
		.attr("x", function(d) {
			return d.x - ((node_radius / 0.8) / 2);
		})
		.attr("y", function(d) {
			return d.y - ((node_radius / 0.8) / 2);
		});
	
	var position_text = node_radius * 0.6;
	
	node.selectAll(".node_text")
		.attr("x", function(d) {
			return d.x;
		})
		.attr("y", function(d) {
			return d.y + node_radius  + position_text;
		});
	
	draw_minimap();
}

function over_node(d) {
	over = d3.select("#id_node_" + d.id + networkmap_id)
		.classed("node_over");
	
	in_a_node = !in_a_node;
	
	d3.select("#id_node_" + d.id + networkmap_id)
		.classed("node_over", !over);
}

function selected_node(d, selected_param, hold_other_selections) {
	if (typeof(selected_param) == "boolean") {
		selected = !selected_param; //because the next negate
	}
	else {
		selected = d3.select("#id_node_" + d.id + networkmap_id)
			.classed("node_selected");
	}
	
	if (typeof(hold_other_selections) != "boolean") {
		deselect_others = !flag_multiple_selection;
	}
	else {
		deselect_others = !hold_other_selections;
	}
	
	
	if (deselect_others) {
		d3.selectAll(".node_selected")
			.classed("node_selected", false);
	}
	
	d3.select("#id_node_" + d.id + networkmap_id)
		.classed("node_selected", !selected);
	
	d3.event.stopPropagation();
	d3.event.preventDefault();
}

function clear_selection() {
	
	if (!flag_multiple_selection && !in_a_node) {
		d3.selectAll(".node_selected")
			.classed("node_selected", false);
	}
}

function update_networkmap() {
	if (enterprise_installed) {
		node
			.each(function(d) {
				if (d.id_agent != -1 ) {
					
					var params = [];
					params.push("update_node_color=1");
					params.push("id=" + d.id_db);
					params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
					
					jQuery.ajax ({
						data: params.join ("&"),
						dataType: 'json',
						type: 'POST',
						url: action="ajax.php",
						success: function (data) {
							d3.select("#id_node_" + d.id + networkmap_id + " .node_shape")
								.style("fill", data['color']);
						}
					});
				}
			});
		
		link
			.each(function(d) {
				if ((d.id_module_start != 0) || (d.id_module_end != 0)) {
					
					if (d.id_module_start != 0) {
						var params = [];
						params.push("module_get_status=1");
						params.push("page=operation/agentes/pandora_networkmap.view");
						params.push("id=" + d.id_module_start);
						jQuery.ajax ({
							data: params.join ("&"),
							dataType: 'json',
							type: 'POST',
							url: action="ajax.php",
							success: function (data) {
								d3.selectAll(".id_module_start_" + d.id_module_start)
									.attr('marker-start',  function(d) {
										if (typeof(module_color_status[data.status]) == "undefined")
											return "url(#interface_start)";
										else
											return "url(#interface_start_" + data.status + ")";
								});
							}
						});
					}
					
					if (d.id_module_end != 0) {
						var params = [];
						params.push("module_get_status=1");
						params.push("page=operation/agentes/pandora_networkmap.view");
						params.push("id=" + d.id_module_end);
						jQuery.ajax ({
							data: params.join ("&"),
							dataType: 'json',
							type: 'POST',
							url: action="ajax.php",
							success: function (data) {
								d3.selectAll(".id_module_end_" + d.id_module_end)
									.attr('marker-end',  function(d) {
										if (typeof(module_color_status[data.status]) == "undefined")
											return "url(#interface_end)";
										else
											return "url(#interface_end_" + data.status + ")";
								});
							}
						});
					}
				}
			});
		
		draw_minimap();
	}
}

////////////////////////////////////////////////////////////////////////
// Minimap
////////////////////////////////////////////////////////////////////////
function init_minimap() {
	var relation_min_nodes = minimap_relation;
	var relation_minimap_w = 2;
	var relation_minimap_h = 2;
	
	if (graph.nodes.length > 100) {
		relation_min_nodes = 0.01;
		relation_minimap_w = (graph.nodes.length / 100) * 2.5;
		relation_minimap_h = 1.5;
	}
	
	$("#minimap_" + networkmap_id).bind("mousemove", function(event) {
		if (graph.nodes.length > 100) {
			var x = event.pageX - $("#minimap_" + networkmap_id).offset().left - minimap_w / relation_minimap_w;
			var y = event.pageY - $("#minimap_" + networkmap_id).offset().top - minimap_h / relation_minimap_h;
		}
		else {
			var x = event.pageX - $("#minimap_" + networkmap_id).offset().left;
			var y = event.pageY - $("#minimap_" + networkmap_id).offset().top;
		}
		
		if (inner_minimap_box(x, y)) {
			document.body.style.cursor = "pointer";
		}
		else {
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
			var x = event.pageX - $("#minimap_" + networkmap_id).offset().left - minimap_w / relation_minimap_w;
			var y = event.pageY - $("#minimap_" + networkmap_id).offset().top - minimap_h / relation_minimap_h;
		}
		else {
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
		case 'node':
			selected_node(data, true, true);
			
			var items_list = {};
			items_list["details"] = {
					name: edit_menu,
					icon: "details",
					disabled : function() {
						if (enterprise_installed) {
							return false;
						}
						else {
							return true;
						}
					},
					"callback": function(key, options) {
						edit_node(data, false);
					}
				};
			items_list["children"] = {
					name: set_as_children_menu,
					icon: "children",
					disabled : function() {
						if (enterprise_installed) {
							return false;
						}
						else {
							return true;
						}
					},
					"callback": function(key, options) {
						var selection = d3.selectAll('.node_children');
						selection
							.each(function(d) {
								d3.select("#id_node_" + d.id + networkmap_id)
									.classed("node_children", false);
							}
						);
						
						selection = d3.selectAll('.node_selected');
						selection
							.each(function(d) {
								d3.select("#id_node_" + d.id + networkmap_id)
									.classed("node_selected", false)
									.classed("node_children", true);
							}
						);
						
						flag_setting_relationship_running = true;
					}
				};
			
			if (flag_setting_relationship_running) {
				if (d3.select("#id_node_" + data.id + networkmap_id).attr("class").search("node_children") == -1) {
					items_list["set_parent"] = {
						name: set_parent_menu,
						icon: "set_parent",
						disabled : function() {
							if (enterprise_installed) {
								return false;
							}
							else {
								return true;
							}
						},
						"callback": function(key, options) {
							var selection = d3.selectAll('.node_selected');
							selection = selection[0];
							if (selection.length > 1) {
								alert("Yo no tengo dedo, por eso no poido trabajo");
							}
							else {
								set_parent(data);
							}
						}
					};
				}
				
				items_list["cancel_set_parent"] = {
					name: abort_relationship_menu,
					icon: "cancel_set_parent",
					disabled : function() {
						if (enterprise_installed) {
							return false;
						}
						else {
							return true;
						}
					},
					"callback": function(key, options) {
						cancel_set_parent();
					}
				};
			}
			
			if (data.id_agent != -1) {
				items_list["delete"] = {
					name: delete_menu,
					icon: "delete",
					disabled : function() {
						if (enterprise_installed) {
							return false;
						}
						else {
							return true;
						}
					},
					"callback": function(key, options) {
						delete_nodes();
					}
				};
			}
			
			$.contextMenu('destroy');
			$.contextMenu({
				disabled: false,
				selector: "#networkconsole_" + networkmap_id,
				// define the elements of the menu
				items: items_list
			});
			break;
		
		
		case 'background':
			var items_list = {};
			items_list["add_node"] = {
				name: add_node_menu,
				icon: "add_node",
				disabled : function() {
					if (enterprise_installed) {
						return false;
					}
					else {
						return true;
					}
				},
				"callback": function(key, options) {
					add_node();
				}
			};
			items_list["center"] = {
				name: set_center_menu,
				icon: "center",
				"callback": function(key, options) {
					set_center(networkmap_id);
				}
			};
			items_list["refresh"] = {
				name: refresh_menu,
				icon: "refresh",
				disabled : function() {
					if (enterprise_installed) {
						return false;
					}
					else {
						return true;
					}
				},
				"callback": function(key, options) {
					update_networkmap();
				}
			};
			items_list["refresh_holding_area"] = {
				name: refresh_holding_area_menu,
				icon: "refresh_holding_area",
				disabled : function() {
					if (enterprise_installed) {
						return false;
					}
					else {
						return true;
					}
				},
				"callback": function(key, options) {
					refresh_holding_area();
				}
			};
			
			if (flag_setting_relationship_running) {
				items_list["cancel_set_parent"] = {
					name: abort_relationship_menu,
					icon: "cancel_set_parent",
					disabled : function() {
						if (enterprise_installed) {
							return false;
						}
						else {
							return true;
						}
					},
					"callback": function(key, options) {
						cancel_set_parent();
					}
				};
			}
			
			
			$.contextMenu('destroy');
			$.contextMenu({
				disabled: false,
				selector: "#networkconsole_" + networkmap_id,
				// define the elements of the menu
				items: items_list
			});
			break;
	}
	
	//Force to show in the mouse position
	$("#networkconsole_" + networkmap_id).contextMenu({
		x: mouse[0],
		y: mouse[1]
	});
}

function refresh_holding_area() {
	if (enterprise_installed) {
		var params = [];
		params.push("refresh_holding_area=1");
		params.push("id=" + networkmap_id);
		params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
		jQuery.ajax ({
			data: params.join ("&"),
			dataType: 'json',
			type: 'POST',
			url: action="ajax.php",
			success: function (data) {
				if (data['correct']) {
					window.holding_area = data['holding_area'];
					
					var length_nodes = graph.nodes.length;
					
					jQuery.each(holding_area.nodes, function(i, node) {
						var temp_node = {};
						
						temp_node['id'] = length_nodes + node['id'];
						holding_area.nodes[i]['id'] = temp_node['id'];
						
						temp_node['id_db'] = node['id_db'];
						temp_node['id_agent'] = node['id_agent'];
						temp_node['id_module'] = 0;
						temp_node['x'] = node['x'];
						temp_node['y'] = node['y'];
						temp_node['z'] = 0;
						temp_node['fixed'] = true;
						temp_node['state'] = node['state'];
						temp_node['type'] = 0;
						temp_node['color'] = node['color'];
						temp_node['shape'] = node['shape'];
						temp_node['text'] = node['text'];
						temp_node['image_url'] = node['image_url'];
						temp_node['image_width'] = node['image_width'];
						temp_node['image_height'] = node['image_width'];
						
						graph.nodes.push(temp_node);
					});
					
					jQuery.each(graph.links, function(j, g_link) {
						
						for(var i = 0; i < holding_area.links.length; i++) {
							if (g_link['id_db'] == holding_area.links[i]['id_db']) {
								holding_area.links.splice(i, 1);
							}
						}
						
					});
					
					jQuery.each(holding_area.links, function(i, link) {
						var temp_link = {};
						temp_link['id_db'] = link['id_db'];
						temp_link['arrow_start'] = link['arrow_start'];
						temp_link['arrow_end'] = link['arrow_end'];
						temp_link['status_start'] = link['status_start'];
						temp_link['status_end'] = link['status_end'];
						temp_link['id_module_start'] = link['id_module_start'];
						temp_link['id_module_end'] = link['id_module_end'];
						temp_link['text_start'] = link['text_start'];
						temp_link['text_end'] = link['text_end'];
						
						//Re-hook the links to nodes
						jQuery.each(graph.nodes, function(j, node) {
							if (node['id_agent'] == link['id_agent_end']) {
								temp_link['target'] = graph.nodes[j];
							}
							if (node['id_agent'] == link['id_agent_start']) {
								temp_link['source'] = graph.nodes[j];
							}
						});
						
						graph.links.push(temp_link);
					});
					
					draw_elements_graph();
					init_drag_and_drop();
					set_positions_graph();
				}
			}
		});
	}
}

function set_parent(parent_data) {
	if (enterprise_installed) {
		var selection = d3.selectAll('.node_children');
		
		count = selection.size();
		
		selection
			.each(function(child_data) {
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
					
					if ((link_item.source_id_db == child_data.id_db) &&
						(link_item.target_id_db == parent_data.id_db)){
						
						repeat = true;
					}
					
					if ((link_item.source_id_db == parent_data.id_db) &&
						(link_item.target_id_db == child_data.id_db)){
						
						repeat = true;
					}
					
				});
				
				if (repeat) {
					count = count - 1;
					if (count == 0) {
						draw_elements_graph();
						set_positions_graph();
						
						cancel_set_parent();
					}
					
					return; //Break
				}
				
				var params = [];
				params.push("set_relationship=1");
				params.push("id=" + networkmap_id);
				params.push("child=" + child_data.id_db);
				params.push("parent=" + parent_data.id_db);
				params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
				jQuery.ajax ({
					data: params.join ("&"),
					dataType: 'json',
					type: 'POST',
					url: action="ajax.php",
					success: function (data) {
						if (data['correct']) {
							//Add the relationship and paint
							item = {};
							item['arrow_start'] = '';
							item['arrow_end'] = '';
							item['status_start'] = '';
							item['status_end'] = '';
							item['text_start'] = '';
							item['text_end'] = '';
							item['id_module_start'] = 0;
							item['id_module_end'] = 0;
							item['id_db'] = data['id'];
							item['source_id_db'] = child_data.id_db;
							item['target_id_db'] = parent_data.id;
							item['id_agent_start'] = graph.nodes[child_data.id]['id_agent'];
							item['id_agent_end'] = graph.nodes[parent_data.id]['id_agent'];
							item['target'] = graph.nodes[parent_data.id];
							item['source'] = graph.nodes[child_data.id];
							
							graph.links.push(item);
						}
						//update_networkmap();
						count = count - 1;
						if (count == 0) {
							draw_elements_graph();
							set_positions_graph();
							
							cancel_set_parent();
						}
					}
				});
			}
		);
	}
}

function cancel_set_parent() {
	var selection = d3.selectAll('.node_children');
	
	selection
		.each(function(d) {
			d3.select("#id_node_" + d.id + networkmap_id)
				.classed("node_selected", true)
				.classed("node_children", false);
		}
	);
	
	flag_setting_relationship_running = false;
}

////////////////////////////////////////////////////////////////////////
// OWN CODE FOR TO DRAG
////////////////////////////////////////////////////////////////////////
function init_drag_and_drop() {
	window.dragables = svg.selectAll(".dragable_node");
	
	window.drag_start = [0, 0];
	window.drag_end = [0, 0];
	window.drag = d3.behavior.drag()
		.on("dragstart", function() {
			if (d3.event.sourceEvent.button == 2)
				return;
			
			mouse_coords = d3.mouse(this);
			drag_start[0] = drag_end[0] = mouse_coords[0];
			drag_start[1] = drag_end[1] = mouse_coords[1];
			
			flag_drag_running = true;
			
			d3.event.sourceEvent.stopPropagation();
		})
		.on("dragend", function(d, i) {
			if (d3.event.sourceEvent.button == 2)
				return;
			
			flag_drag_running = false;
			
			var selection = d3.selectAll('.node_selected');
			
			if (enterprise_installed) {
				selection
					.each(function(d) {
						var params = [];
						params.push("update_node=1");
						params.push("node=" + JSON.stringify(d));
						params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
						jQuery.ajax ({
							data: params.join ("&"),
							dataType: 'json',
							type: 'POST',
							url: action="ajax.php",
							success: function (data) {
								if (d.state == 'holding_area') {
									//It is out the holding area
									if (data['state'] == "") {
										//Remove the style of nodes and links
										//in holding area
										
										d3.select("#id_node_" + d.id + networkmap_id)
											.classed("holding_area", false);
										
										d3.select(".source_" + d.id + networkmap_id)
											.classed("holding_area_link", false);
										d3.select(".target_" + d.id + networkmap_id)
											.classed("holding_area_link", false);
									}
								}
							}
						});
					});
			}
			else {
				var params = [];
				params.push("update_node_alert=1");
				params.push("map_id=" + networkmap_id);
				params.push("page=operation/agentes/pandora_networkmap.view");
				jQuery.ajax ({
					data: params.join ("&"),
					dataType: 'json',
					type: 'POST',
					url: action="ajax.php",
					success: function (data) {
						if (data['correct']) {
							alert("In the Open version of PandoraFMS can not be edited nodes or map");
						}
					}
				});
			}
			
			d3.event.sourceEvent.stopPropagation();
		})
		.on("drag", function( d, i) {
			if (d3.event.sourceEvent.button == 2)
				return;
			
			mouse_coords = d3.mouse(this);
			
			delta = [0, 0];
			delta[0] = mouse_coords[0] - drag_end[0];
			delta[1] = mouse_coords[1] - drag_end[1];
			
			drag_end[0] = mouse_coords[0];
			drag_end[1] = mouse_coords[1];
			
			var selection = d3.selectAll('.node_selected');
			
			selection
				.each(function(d) {
					graph.nodes[d.id].x = d.x + delta[0];
					graph.nodes[d.id].y = d.y + delta[1];
					graph.nodes[d.id].px = d.px + delta[0];
					graph.nodes[d.id].py = d.py + delta[1];
				});
			
			set_positions_graph();
			
			d3.event.sourceEvent.stopPropagation();
		});
	dragables.call( drag);
}

function add_fictional_node() {
	var name = $("input[name='name_fictional_node']").val();
	var networkmap_to_link = $("#networkmap_to_link").val();
	
	var x = (click_menu_position_svg[0] - translation[0]) / scale;
	var y = (click_menu_position_svg[1] - translation[1]) / scale;
	
	if (enterprise_installed) {
		var params = [];
		params.push("create_fictional_point=1");
		params.push("id=" + networkmap_id);
		params.push("name=" + name);
		params.push("networkmap=" + networkmap_to_link);
		params.push("color=" + module_color_status[0]['color']);
		params.push("radious=" + node_radius);
		params.push("shape=circle");
		params.push("x=" + x);
		params.push("y=" + y);
		params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
		jQuery.ajax ({
			data: params.join ("&"),
			dataType: 'json',
			type: 'POST',
			url: action="ajax.php",
			success: function (data) {
				if (data['correct']) {
					$("#dialog_node_add").dialog("close");
					
					var temp_node = {};
					temp_node['id'] = graph.nodes.length;
					temp_node['id_db'] = data['id_node'];
					temp_node['id_agent'] = data['id_agent'];
					temp_node['id_module'] = 0;
					temp_node['x'] = x;
					temp_node['y'] = y;
					temp_node['z'] = 0;
					temp_node['fixed'] = true;
					temp_node['type'] = 3;
					temp_node['color'] = data['color'];
					temp_node['shape'] = data['shape'];
					temp_node['text'] = data['text'];
					temp_node['image_url'] = "";
					temp_node['image_width'] = 0;
					temp_node['image_height'] = 0;
					temp_node['networkmap_id'] = networkmap_to_link;
					
					graph.nodes.push(temp_node);
					
					draw_elements_graph();
					init_drag_and_drop();
					set_positions_graph();
				}
			}
		});
	}
	else {
		$("#dialog_node_add").dialog("close");
		
		var temp_node = {};
		temp_node['id'] = graph.nodes.length;
		temp_node['id_db'] = data['id_node'];
		temp_node['id_agent'] = data['id_agent'];
		temp_node['id_module'] = 0;
		temp_node['x'] = x;
		temp_node['y'] = y;
		temp_node['z'] = 0;
		temp_node['fixed'] = true;
		temp_node['type'] = 3;
		temp_node['color'] = data['color'];
		temp_node['shape'] = data['shape'];
		temp_node['text'] = data['text'];
		temp_node['image_url'] = "";
		temp_node['image_width'] = 0;
		temp_node['image_height'] = 0;
		temp_node['networkmap_id'] = networkmap_to_link;
		
		graph.nodes.push(temp_node);
		
		draw_elements_graph();
		init_drag_and_drop();
		set_positions_graph();
	}
}

function init_graph(parameter_object) {	
	window.width_svg = $("#networkconsole_" + networkmap_id).width();
	if ($("#main").height()) {
		window.height_svg = $("#main").height();
	}
	else {
		//Set the height in the pure view (fullscreen).
		window.height_svg = $(window).height() -
			$("#menu_tab_frame_view").height() -
			20; // 20 of margin
	}
	
	window.refresh_period = 5 * 1000; //milliseconds
	if (typeof(parameter_object.refresh_period) != "undefined") {
		window.refresh_period = parameter_object.refresh_period;
	}
	window.scale_minimap = 4.2;
	window.translation = [0, 0];
	window.scale = 0.5;
	window.node_radius = 40;
	if (typeof(parameter_object.node_radius) != "undefined") {
		window.node_radius = parameter_object.node_radius;
	}
	window.interface_radius = 5;
	window.disabled_drag_zoom = false;
	window.key_multiple_selection = 17; //CTRL key
	window.flag_multiple_selection = false;
	window.flag_multiple_selection_running = false;
	window.selection_rectangle = [0, 0, 0, 0];
	window.flag_drag_running = false;
	window.in_a_node = false;
	window.enterprise_installed = false;
	window.flag_setting_relationship_running = false;
	
	window.minimap_w = 0;
	window.minimap_h = 0;
	window.show_minimap = false;
	window.show_labels = true;
	window.context_minimap;
	
	window.holding_area_dimensions = [200, 200];
	if (typeof(parameter_object.holding_area_dimensions) != "undefined") {
		window.holding_area_dimensions = parameter_object.holding_area_dimensions;
	}
	
	window.graph = null;
	if (typeof(parameter_object.graph) != "undefined") {
		window.graph = parameter_object.graph;
	}
	
	window.networkmap_center = [];
	if (typeof(parameter_object.networkmap_center) != "undefined") {
		window.networkmap_center = parameter_object.networkmap_center;
	}
	
	if (typeof(networkmap_center[0]) == "undefined") {
		networkmap_center = [0, 0];
	}
	
	translation[0] = (width_svg / 2) - networkmap_center[0];
	translation[1] = (height_svg / 2) - networkmap_center[1];
	
	translation[0] = translation[0] * scale;
	translation[1] = translation[1] * scale;
	
	window.enterprise_installed = '';
	if (typeof(parameter_object.enterprise_installed) != "undefined") {
		window.enterprise_installed = parameter_object.enterprise_installed;
	}
	
	window.networkmap_dimensions = [];
	if (typeof(parameter_object.networkmap_dimensions) != "undefined") {
		window.networkmap_dimensions = parameter_object.networkmap_dimensions;
	}
	window.max = Math.max(networkmap_dimensions[0], networkmap_dimensions[1]);
	window.min = Math.min(width_svg / scale_minimap,
		height_svg / scale_minimap);
	
	window.minimap_relation = min / max;
	minimap_w = networkmap_dimensions[0] * minimap_relation;
	minimap_h = networkmap_dimensions[1] * minimap_relation;
	
	$("#minimap_" + networkmap_id).attr('width', minimap_w);
	$("#minimap_" + networkmap_id).attr('height', minimap_h);
	
	window.canvas_minimap = $("#minimap_" + networkmap_id);
	window.context_minimap = canvas_minimap[0].getContext('2d');
	window.minimap_drag = false;
	
	window.url_background_grid = '';
	if (typeof(parameter_object.url_background_grid) != "undefined") {
		// GRID
		window.url_background_grid = "";
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
				
				d3.select("#selection_rectangle")
					.style("display", "none");
			}
		});
	
	window.force = d3.layout.force()
		.charge(10)
		.linkDistance(0)
		.size([width_svg, height_svg]);
	
	if (x_offs != null) {
		translation[0] = x_offs;
	}
	if (y_offs != null) {
		translation[1] = y_offs;
	}
	if (z_dash != null) {
		scale = z_dash;
	}
	
	window.zoom_obj = d3.behavior.zoom();
	zoom_obj.scaleExtent([0.05, 1])
		.on("zoom", zoom)
		.translate(translation)
		.scale(scale);
	
	window.svg = d3.select("#networkconsole_" + networkmap_id)
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
			}
			else {
				clear_selection();
			}
		})
		.on("mouseup", function() {
			flag_multiple_selection_running = false;
			d3.select("#selection_rectangle")
				.style("display", "none");
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
				
				d3.selectAll('.node').each(function(data, i) {
					
					item_x1 = ((data.x - (node_radius / 2)) * scale + translation[0]);
					item_x2 = ((data.x + (node_radius / 2)) * scale + translation[0]);
					item_y1 = ((data.y - (node_radius / 2)) * scale + translation[1]);
					item_y2 = ((data.y + (node_radius / 2)) * scale + translation[1]);
					
					if (
						!d3.select(this).classed("node_selected") && 
						// inner circle inside selection frame
						item_x1 >= sel_rec_x1 &&
						item_x2 <= sel_rec_x2 && 
						item_y1 >= sel_rec_y1 &&
						item_y1 <= sel_rec_y2
					) {
						
						d3.select("#id_node_" + data.id + networkmap_id)
							.classed("node_selected", true);
					}
				});
			}
		})
		.on("contextmenu", function(d) { show_menu("background", d);});
	
	
	window.defs = svg.append("defs");
	defs.selectAll("defs")
		.data(module_color_status)
		.enter()
		.append("marker")
			.attr("id", function(d) { return "interface_start_" + d.status_code; })
			.attr("refX", 0)
			.attr("refY", interface_radius)
			.attr("markerWidth", (node_radius / 2) + interface_radius)
			.attr("markerHeight", (node_radius / 2) + interface_radius)
			.attr("orient", "auto")
			.append("circle")
				.attr("cx", (node_radius / 2) - (interface_radius / 2))
				.attr("cy", interface_radius)
				.attr("r", interface_radius)
				.attr("style", function(d) {
					return "fill: " + d.color + ";";
				});
	
	defs.selectAll("defs")
		.data(module_color_status)
		.enter()
		.append("marker")
		.attr("id", function(d) { return "interface_end_" + d.status_code; })
		.attr("refX", (node_radius / 2) + (interface_radius / 2))
		.attr("refY", interface_radius)
		.attr("markerWidth", (node_radius / 2) + interface_radius)
		.attr("markerHeight", (node_radius / 2) + interface_radius)
		.attr("orient", "auto")
		.append("circle")
			.attr("cx", interface_radius)
			.attr("cy", interface_radius)
			.attr("r", interface_radius)
			.attr("style", function(d) {
				return "fill: " + d.color + ";";
			});
			
	defs.append("marker")
		.attr("id", "interface_start")
		.attr("refX", 0)
		.attr("refY", interface_radius)
		.attr("markerWidth", (node_radius / 2) + interface_radius)
		.attr("markerHeight", (node_radius / 2) + interface_radius)
		.attr("orient", "auto")
		.append("circle")
			.attr("cx", (node_radius / 2) - (interface_radius / 2))
			.attr("cy", interface_radius)
			.attr("r", interface_radius)
			.attr("style", "fill:" + module_color_status_unknown + ";");
			
	defs.append("marker")
		.attr("id", "interface_end")
		.attr("refX", (node_radius / 2) + (interface_radius / 2))
		.attr("refY", interface_radius)
		.attr("markerWidth", (node_radius / 2) + interface_radius)
		.attr("markerHeight", (node_radius / 2) + interface_radius)
		.attr("orient", "auto")
		.append("circle")
			.attr("cx", interface_radius)
			.attr("cy", interface_radius)
			.attr("r", interface_radius)
			.attr("style", "fill:" + module_color_status_unknown + ";");
	
	//Added pattern for the background grid
	svg.append("pattern")
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
			.attr("transform",
				"translate(" + translation + ")scale(" + scale + ")");
	
	if (enterprise_installed) {
		window.layer_graph.append("rect")
			.attr("id", "holding_area_" + networkmap_id)
			.attr("width", holding_area_dimensions[0])
			.attr("height", holding_area_dimensions[1])
			.attr("x",
				networkmap_dimensions[0] + node_radius - holding_area_dimensions[0])
			.attr("y",
				networkmap_dimensions[1] + node_radius - holding_area_dimensions[1])
			.attr("style", "fill: #e6e6e6; " +
				"fill-opacity: 0.75; " +
				"stroke: #dedede; " +
				"stroke-width: 1; " +
				"stroke-miterlimit: 4; " +
				"stroke-opacity: 0.75; " +
				"stroke-dasharray: none; " + 
				"stroke-dashoffset: 0");
	
		window.layer_graph.append("text")
			.append("tspan")
				.attr("xml:space", "preserve")
				.attr("style", "font-size: 32px; " +
					"font-style: normal; " +
					"font-weight: normal; " +
					"text-align: start; " +
					"line-height: 125%; " +
					"letter-spacing: 0px; " +
					"word-spacing: 0px; " +
					"text-anchor: start; " +
					"fill: #000000; " +
					"fill-opacity: 1; " +
					"stroke: none; " +
					"font-family: Verdana")
				.attr("x", networkmap_dimensions[0] + node_radius - holding_area_dimensions[0])
				.attr("y", networkmap_dimensions[1] + node_radius - holding_area_dimensions[1] - 10)
				.text(holding_area_title);
	}
	
	window.layer_graph_links = window.layer_graph
		.append("g")
			.attr("id", "layer_graph_links_" + networkmap_id);
	window.layer_graph_nodes = window.layer_graph
		.append("g")
			.attr("id", "layer_graph_nodes_" + networkmap_id);
	
	window.layer_selection_rectangle = svg
		.append("g")
			.attr("id", "layer_selection_rectangle");
	
	force.nodes(graph.nodes)
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
			.attr("style", "display: none; fill:#e6e6e6; " +
				"fill-opacity:0.46153846; " +
				"stroke:#e6e6e6; " +
				"stroke-width:1; " +
				"stroke-miterlimit:4; " +
				"stroke-opacity:1; " +
				"stroke-dasharray:none;");
	
	$("#dialog_node_edit").dialog({
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
	
	circle.transition().duration(400)
		.attr("r", node_radius + 10);
}
function myMouseoutCircleFunction(node_id) {
	var circle = d3.select("#id_node_" + node_id + networkmap_id + " circle");
	
	over = circle.classed("node_over");
	
	in_a_node = !in_a_node;
	
	circle.classed("node_over", !over);
	
	circle.transition().duration(400)
		.attr("r", node_radius);
}

function myMouseoverSquareFunction(node_id) {
	var square = d3.select("#id_node_" + node_id + networkmap_id + " rect");
	
	over = square.classed("node_over");
	
	in_a_node = !in_a_node;
	
	square.classed("node_over", !over);
	
	square.transition().duration(400)
		.attr("width", (node_radius * 2) + 10)
		.attr("height", (node_radius * 2) + 10)
		.attr("transform", "translate(" + (-5) + "," + (-5) + ")");
}
function myMouseoutSquareFunction(node_id) {
	var square = d3.select("#id_node_" + node_id + networkmap_id + " rect");
	
	over = square.classed("node_over");
	
	in_a_node = !in_a_node;
	
	square.classed("node_over", !over);
	
	square.transition().duration(400)
		.attr("width", (node_radius * 2))
		.attr("height", (node_radius * 2))
		.attr("transform", "translate(" + 0 + "," + 0 + ")");
}

function myMouseoverRhombusFunction(node_id) {
	var rhombus = d3.select("#id_node_" + node_id + networkmap_id + " rect");
	
	over = rhombus.classed("node_over");
	
	in_a_node = !in_a_node;
	
	rhombus.classed("node_over", !over);
	
	rhombus.transition().duration(400)
		.attr("width", (node_radius * 1.5) + 10)
		.attr("height", (node_radius * 1.5) + 10);
}
function myMouseoutRhombusFunction(node_id) {
	var rhombus = d3.select("#id_node_" + node_id + networkmap_id + " rect");
	
	over = rhombus.classed("node_over");
	
	in_a_node = !in_a_node;
	
	rhombus.classed("node_over", !over);
	
	rhombus.transition().duration(400)
		.attr("width", (node_radius * 1.5))
		.attr("height", (node_radius * 1.5));
}

function draw_elements_graph() {
	link = link.data(force.links(), function(d) {
		return d.source.id + networkmap_id + "-" + d.target.id + networkmap_id;
	});
	
	link_temp = link.enter()
		.append("g");
	link.exit().remove();
	
	link_temp.append("path")
		.attr("id", function(d) {
			return "link_id_" + d.id_db + networkmap_id;
		})
		.attr("class",  function(d) {
			var holding_area_text = "";
			if ((d.source.state == 'holding_area') ||
				(d.target.state == 'holding_area')) {
				
				holding_area_text = " holding_area_link ";
			}
			
			return "link " +
				"source_" + d.source.id + networkmap_id + " " +
				"target_" + d.target.id + networkmap_id + " " +
				holding_area_text +
				"id_module_start_" + d.id_module_start + " " +
				"id_module_end_" + d.id_module_end;
		})
		.attr("stroke-width", 3)
		.attr("d", null)
		.attr('marker-start',  function(d) {
			if (d.arrow_start == "") {
				return "";
			}
			else if (d.arrow_start == "module") {
				if (typeof(module_color_status[d.status_start]) == "undefined")
					return "url(#interface_start)";
				else
					return "url(#interface_start_" + d.status_start + ")";
			}
		})
		.attr('marker-end',  function(d) {
			if (d.arrow_end == "") {
				return "";
			}
			else if (d.arrow_end == "module") {
				if (typeof(module_color_status[d.status_end]) == "undefined")
					return "url(#interface_end)";
				else
					return "url(#interface_end_" + d.status_end + ")";
			}
		})
		.on("mouseover", function(d) {
			d3.select(this)
				.classed("link_over", true);
		})
		.on("mouseout", function(d) {
			d3.select(this)
				.classed("link_over", false);
		});
	
	//Add the reverse line for the end marker, it is invisible
	 link_temp.append("path")
			.attr("id", function(d) {
				return "link_reverse_id_" + d.id_db;
			})
		.attr("stroke-width", 0)
		.attr("d", null)
		.attr("class",  function(d) {
				return "link_reverse";
		});
	
	link_temp.append("text")
		.attr("xml:space", "preserve")
		.append("textPath")
			.attr("xlink:href", function(d) {
					return "#link_id_" + d.id_db;
			})
			.append("tspan")
				.attr("style", "font-size: 8px; " +
					"font-style:normal; " +
					"font-weight:normal; " +
					"line-height: 100%; " +
					"letter-spacing:0px; " +
					"word-spacing:0px; " +
					"fill:#000000; " +
					"fill-opacity:1; " +
					"stroke:none; " +
					"text-align:end; ")
				.text(function(d) {
					var text_link = "";
					if (d.text_start) {
						text_link = d.text_start;
					}
					
					return (Array(25).join(" ")) + text_link;
				});
	
	link_temp.append("text")
		.attr("xml:space", "preserve")
		.append("textPath")
			.attr("xlink:href", function(d) {
				return "#link_reverse_id_" + d.id_db;
			})
			.append("tspan")
				.attr("style", "font-size: 8px; " +
					"font-style:normal; " +
					"font-weight:normal; " +
					"line-height: 100%; " +
					"letter-spacing:0px; " +
					"word-spacing:0px; " +
					"fill:#000000; " +
					"fill-opacity:1; " +
					"stroke:none; " +
					"text-align:end; ")
				.text(function(d) {
					var text_link = "";
					if (d.text_end) {
						text_link = d.text_end;
					}
					
					return (Array(25).join(" ")) + text_link;
				});
	
	node = node.data(force.nodes(), function(d) { return d.id;});
	node_temp = node.enter()
		.append("g")
			.attr("id", function(d) {
				return "id_node_" + d.id + networkmap_id;
			})
			.attr("class", function(d) {
				if (d.state == 'holding_area')
					return "node holding_area";
				else
					return "node";
			});
			
	node.exit().remove();
	
	//Shape circle
	node_temp.filter(function(d) {
			if (d.shape == 'circle') {return true;}
			else return false;
		})
		.append("circle")
			.attr("r", node_radius)
			.attr("class", "node_shape node_shape_circle")
			.attr("node_id", function(d) {
					return d.id  + networkmap_id;
				})
			.style("fill", function(d) {
					return d.color;
				})
			.classed('dragable_node', true) //own dragable
			.on("mouseover", function(d) {
				myMouseoverCircleFunction(d.id)
				})
			.on("mouseout", function(d) {
				myMouseoutCircleFunction(d.id)
				})
			.on("click", selected_node)
			.on("dblclick", function(d) {
					edit_node(d, true);
				})
			.on("contextmenu", function(d) { show_menu("node", d);});
	
	node_temp.append("image")
		.attr("class", "node_image")
		.attr("xlink:href", function(d) {
				return d.image_url;
			})
		.attr("x", function(d) {
				return d.x - (d.image_width / 2);
			})
		.attr("y", function(d) {
				return d.y - (d.image_height / 2);
			})
		.attr("width", function(d) {
				return (node_radius / 0.8);
			})
		.attr("height", function(d) {
				return (node_radius / 0.8);
			})
		.attr("node_id", function(d) {
				return d.id + networkmap_id;
			})
		.attr("id", "image2995")
		.classed('dragable_node', true) //own dragable
		.on("mouseover", function(d) {
			myMouseoverCircleFunction(d.id)
			})
		.on("mouseout", function(d) {
			myMouseoutCircleFunction(d.id)
			})
		.on("click", selected_node)
		.on("dblclick", function(d) {
				edit_node(d, true);
			})
		.on("contextmenu", function(d) { show_menu("node", d);});
	
	//Shape square
	node_temp.filter(function(d) {
			if (d.shape == 'square') {return true;}
			else return false;
		})
		.append("rect")
			.attr("width", node_radius * 2)
			.attr("height", node_radius * 2)
			.attr("class", "node_shape node_shape_square")
			.attr("node_id", function(d) {
					return d.id  + networkmap_id;
				})
			.style("fill", function(d) {
					return d.color;
				})
			.classed('dragable_node', true) //own dragable
			.on("mouseover", function(d) {
				myMouseoverSquareFunction(d.id)
				})
			.on("mouseout", function(d) {
				myMouseoutSquareFunction(d.id)
				})
			.on("click", selected_node)
			.on("dblclick", function(d) {
					edit_node(d, true);
				})
			.on("contextmenu", function(d) { show_menu("node", d);});
			
	node_temp.filter(function(d) {
			if (d.shape == 'square') {return true;}
			else return false;
		})
		.append("image")
		.attr("class", "node_image")
		.attr("xlink:href", function(d) {
				return d.image_url;
			})
		.attr("x", function(d) {
				return d.x - (d.image_width / 2);
			})
		.attr("y", function(d) {
				return d.y - (d.image_height / 2);
			})
		.attr("width", function(d) {
				return (node_radius / 0.8);
			})
		.attr("height", function(d) {
				return (node_radius / 0.8);
			})
		.attr("node_id", function(d) {
				return d.id + networkmap_id;
			})
		.attr("id", "image2995")
		.classed('dragable_node', true) //own dragable
		.on("mouseover", function(d) {
			myMouseoverSquareFunction(d.id)
			})
		.on("mouseout", function(d) {
			myMouseoutSquareFunction(d.id)
			})
		.on("click", selected_node)
		.on("dblclick", function(d) {
				edit_node(d, true);
			})
		.on("contextmenu", function(d) { show_menu("node", d);});
	
	//Shape rhombus
	node_temp.filter(function(d) {
			if (d.shape == 'rhombus') {return true;}
			else return false;
		})
		.append("rect")
			.attr("transform",
				"")
			.attr("width", node_radius * 1.5)
			.attr("height", node_radius * 1.5)
			.attr("class", "node_shape node_shape_rhombus")
			.attr("node_id", function(d) {
					return d.id + networkmap_id;
				})
			.style("fill", function(d) {
					return d.color;
				})
			.classed('dragable_node', true) //own dragable
			.on("mouseover", function(d) {
				myMouseoverRhombusFunction(d.id)
				})
			.on("mouseout", function(d) {
				myMouseoutRhombusFunction(d.id)
				})
			.on("click", selected_node)
			.on("dblclick", function(d) {
					edit_node(d, true);
				})
			.on("contextmenu", function(d) { show_menu("node", d);});
			
	node_temp.filter(function(d) {
			if (d.shape == 'rhombus') {return true;}
			else return false;
		})
		.append("image")
		.attr("class", "node_image")
		.attr("xlink:href", function(d) {
				return d.image_url;
			})
		.attr("x", function(d) {
				return d.x - (d.image_width / 2);
			})
		.attr("y", function(d) {
				return d.y - (d.image_height / 2);
			})
		.attr("width", function(d) {
				return (node_radius / 0.8);
			})
		.attr("height", function(d) {
				return (node_radius / 0.8);
			})
		.attr("node_id", function(d) {
				return d.id + networkmap_id;
			})
		.attr("id", "image2995")
		.classed('dragable_node', true) //own dragable
		.on("mouseover", function(d) {
			myMouseoverRhombusFunction(d.id)
			})
		.on("mouseout", function(d) {
			myMouseoutRhombusFunction(d.id)
			})
		.on("click", selected_node)
		.on("dblclick", function(d) {
				edit_node(d, true);
			})
		.on("contextmenu", function(d) { show_menu("node", d);});
	
	node_temp.append("title")
		.text(function(d) {return d.text; });
	
	var font_size = (node_radius / 2.8);
	
	node_temp.append("text")
		.attr("class", "node_text")
		.attr("id", "node_text_" + networkmap_id)
		.attr("style", "font-style:normal; font-weight:normal; line-height:125%;letter-spacing:0px;word-spacing:0px;fill:#000000;fill-opacity:1;stroke:none;")
		.attr("x", function(d) {
				return d.x;
			})
		.attr("y", function(d) {
				return d.y + node_radius + 12;
			})
		.append("tspan")
			.attr("style", "font-size: " + font_size + "px !important; font-family:Verdana; text-align:center; text-anchor:middle; fill:#000000")
			.text(function(d) {
					return d.text;
				})
		.classed('dragable_node', true) //own dragable
		.on("click", selected_node)
		.on("contextmenu", function(d) { show_menu("node", d);});
		
	node.exit().remove();
}

function choose_group_for_show_agents() {
	if (enterprise_installed) {
		group = $("#group_for_show_agents option:selected").val();
		
		$("#agents_filter_group").attr('disabled', true);
		$("#spinner_group").css('display', '');
		if (group == -1) {
			$("#agents_filter_group").html('<option value="-1">' + $("#hack_translation_none").html() + '</option>');
			$("#spinner_group").css('display', 'none');
		}
		else {
			$("#group_for_show_agents").attr('disabled', true);
			
			var params = [];
			params.push("get_agents_in_group=1");
			params.push("id=" + networkmap_id);
			params.push("group=" + group);
			params.push("page=enterprise/operation/agentes/pandora_networkmap.view");
			jQuery.ajax ({
				data: params.join ("&"),
				dataType: 'json',
				type: 'POST',
				url: action="ajax.php",
				success: function (data) {
					if (data['correct']) {
						$("#agents_filter_group").html('');
						jQuery.each(data['agents'], function (id, name) {
							if (typeof(name) == 'undefined') return;
							
							$("#agents_filter_group").append('<option value="' + id + '">' + name + '</option>');
						});
						
						$("#agents_filter_group").removeAttr('disabled');
						$("#group_for_show_agents").removeAttr('disabled');
						$("#spinner_group").css('display', 'none');
						$("input[name=add_agent_group_button]").removeAttr('disabled');
					}
					else {
						$("#group_for_show_agents").removeAttr('disabled');
						$("#agents_filter_group").html('<option value="-1">' +
							translation_none + '</option>');
						$("#spinner_group").css('display', 'none');function show_networkmap_node(id_agent_param, refresh_state) {
		id_agent = id_agent_param;
		
		canvas = $("#node_info");
		context_popup = canvas[0].getContext('2d');
		
		dirty_popup = true;
		self.setInterval("check_popup_modification()", 1000/30);
		
		$("#node_info").mousemove(function(event) {
			var x = event.pageX - $("#node_info").offset().left;
			var y = event.pageY - $("#node_info").offset().top;
			
			module_inner = inner_module(x, y);
			
			if (module_inner != null) {
				document.body.style.cursor = "pointer";
			}
			else {
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
			function show_networkmap_node(id_agent_param, refresh_state) {
		id_agent = id_agent_param;
		
		canvas = $("#node_info");
		context_popup = canvas[0].getContext('2d');
		
		dirty_popup = true;
		self.setInterval("check_popup_modification()", 1000/30);
		
		$("#node_info").mousemove(function(event) {
			var x = event.pageX - $("#node_info").offset().left;
			var y = event.pageY - $("#node_info").offset().top;
			
			module_inner = inner_module(x, y);
			
			if (module_inner != null) {
				document.body.style.cursor = "pointer";
			}
			else {
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
			pos_scroll = Math.floor($("#content_node_info").width() / 2);
			
			$("#content_node_info").scrollLeft(pos_scroll);
			
			dirty_popup = true;
			check_popup_modification();
		});
	}
					}
				}
			});
		}
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
	jQuery.ajax ({
		data: params.join ("&"),
		dataType: 'json',
		type: 'POST',
		url: action="../../../ajax.php",
		success: function (data) {
			if (data['correct']) {
				color_status_node = data['status_agent'];
				dirty_popup = true;
			}
		}
	});
}

function get_status_module() {
	jQuery.each(modules, function (id, module) {
		if (typeof(module) == 'undefined') return;
		
		
		var params = [];
		params.push("get_status_module=1");
		params.push("id=" + id);
		params.push("page=operation/agentes/pandora_networkmap.view");
		jQuery.ajax ({
			data: params.join ("&"),
			dataType: 'json',
			type: 'POST',
			url: action="../../../ajax.php",
			success: function (data) {
				if (data['correct']) {
					modules[data['id']].status_color = data['status_color'];
					dirty_popup = true;
				}
			}
		});
		
		
	});
}

function check_changes_num_modules() {
	var params = [];
	params.push("check_changes_num_modules=1");
	params.push("id=" + id_agent);
	params.push("page=operation/agentes/pandora_networkmap.view");
	jQuery.ajax ({
		data: params.join ("&"),
		dataType: 'json',
		type: 'POST',
		url: action="../../../ajax.php",
		success: function (data) {
			if (data['correct']) {
				if (module_count != data['count']) {
					//location.reload(true);
				}
			}
		}
	});
}

function show_networkmap_node(id_agent_param, refresh_state) {
	id_agent = id_agent_param;
	
	canvas = $("#node_info");
	context_popup = canvas[0].getContext('2d');
	
	dirty_popup = true;
	self.setInterval("check_popup_modification()", 1000/30);
	
	$("#node_info").mousemove(function(event) {
		var x = event.pageX - $("#node_info").offset().left;
		var y = event.pageY - $("#node_info").offset().top;
		
		module_inner = inner_module(x, y);
		
		if (module_inner != null) {
			document.body.style.cursor = "pointer";
		}
		else {
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
	jQuery.ajax ({
		data: params.join ("&"),
		dataType: 'json',
		type: 'POST',
		url: action="../../../ajax.php",
		success: function (data) {
			if (data['correct']) {
				$("#tooltip").html(data['content']);
			}
		}
	});
}

function show_tooltip(id, x, y) {
	$("#tooltip").css('top', y + 'px');
	$("#tooltip").css('left', x + 'px');
	
	var params1 = [];
	params1.push("get_image_path=1");
	params1.push("img_src=" + "images/spinner.gif");
	params1.push("page=include/ajax/skins.ajax");
	jQuery.ajax ({
		data: params1.join ("&"),
		type: 'POST',
		url: action="../../../ajax.php",
		success: function (data) {
			$("#tooltip").html(data);
			$("#tooltip").css('display', '');
			
			show_tooltip_content(id);
		}
	});
}

function hide_tooltip() {
	$("#tooltip").css('display', 'none');
}

function inner_module(x, y) {
	var return_var = null;
	
	jQuery.each(modules, function (key, module) {
		if (typeof(module) == 'undefined') return;
		
		if ((x >= module.pos_x) && (x < (module.pos_x + SIZE_MODULE)) &&
				(y >= module.pos_y) && (y < (module.pos_y + SIZE_MODULE))) {
				
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
		box_height = Math.ceil(count_no_snmp / 2) * VERTICAL_SPACE_MODULES
			+ VERTICAL_SPACING_BETWEEN_MODULES;
	}
	else {
		box_height = Math.ceil(count_snmp_modules / 2) * VERTICAL_SPACE_MODULES
			+ VERTICAL_SPACING_BETWEEN_MODULES;
	}
	
	//Draw the agent box.
	// 2 columns of HORIZONTAL_SPACE_MODULES px for each modules
	// + 15 * 2 half each snmp module 
	box_width = HORIZONTAL_SPACE_MODULES * 2 + SIZE_MODULE;
	
	
	//Resize the canvas if the box is bigger before of paint.
	if ((box_height + 50) != $("#node_info").attr("height")) {
		node_info_height = box_height + 50;
		$("#node_info").attr("height", node_info_height);
		//$("#node_info").attr("width", node_info_width);
	}
	
	if ((box_width + 400) != $("#node_info").attr("width")) {
		node_info_width = box_width + 400;
		$("#node_info").attr("width", node_info_width);
	}
	
	//Clean the canvas
	context_popup.clearRect(0, 0, node_info_width, node_info_height);
	context_popup.beginPath(); //Erase lines?
	
	
	
	pos_x = (node_info_width - box_width) / 2 + offset_x;
	
	context_popup.beginPath();
	context_popup.rect(pos_x, VERTICAL_SPACING_BETWEEN_MODULES + offset_y, box_width, box_height);
	context_popup.fillStyle = "#ccc";
	context_popup.fill();
	
	//Draw the global status of agent into the box's border color.
	context_popup.lineWidth = BORDER_SIZE_AGENT_BOX;
	context_popup.strokeStyle = color_status_node;
	context_popup.stroke();
	
	if (mode_show == 'all') {
		draw_snmp_modules();
		draw_modules();
	}
	else if (mode_show == 'status_module') {
		draw_snmp_modules();
	}
}

function draw_snmp_modules() {
	module_pos_y = MARGIN_BETWEEN_AGENT_MODULE;
	
	count = 0;
	reset_column = true;
	
	jQuery.each(modules, function (key, module) {
		if (typeof(module) == 'undefined') return;
		
		if (module.type != 18) return;
		
		if (count < (count_snmp_modules / 2)) {
			module_pos_x = pos_x - 15;
			text_align = 'right';
			margin_text = 5;
		}
		else {
			if (reset_column) {
				module_pos_y = MARGIN_BETWEEN_AGENT_MODULE;
				reset_column = false;
			}
			module_pos_x = pos_x + box_width - 15;
			text_align = 'left';
			margin_text = SIZE_MODULE - 5;
		}
		count++;
		
		context_popup.beginPath();
		context_popup.rect(module_pos_x, module_pos_y + offset_y,
			SIZE_MODULE, SIZE_MODULE);
		context_popup.fillStyle = module.status_color;
		context_popup.fill();
		context_popup.lineWidth = 1;
		context_popup.strokeStyle = "#000";
		context_popup.stroke();
		
		modules[key].pos_x = module_pos_x;
		modules[key].pos_y = module_pos_y + offset_y;
		
		context_popup.fillStyle = "rgb(0,0,0)";
		context_popup.font = 'bold 10px sans-serif';
		context_popup.textBaseline = 'middle';
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
	
	jQuery.each(modules, function (key, module) {
		if (typeof(module) == 'undefined') return;
		
		if (module.type == 18) return;
		
		if (count < (count_no_snmp / 2)) {
			module_pos_x = pos_x + (HORIZONTAL_SPACE_MODULES - SIZE_MODULE) / 2;
			text_pos_x = pos_x + (HORIZONTAL_SPACE_MODULES / 2);
		}
		else {
			if (reset_column) {
				module_pos_y = MARGIN_BETWEEN_AGENT_MODULE;
				reset_column = false;
			}
			module_pos_x = pos_x + (box_width  - HORIZONTAL_SPACE_MODULES)
				+ (HORIZONTAL_SPACE_MODULES - SIZE_MODULE) / 2;
			text_pos_x = pos_x + (box_width  - HORIZONTAL_SPACE_MODULES) +
				(HORIZONTAL_SPACE_MODULES / 2)
		}
		count++;
		
		context_popup.beginPath();
		center_orig_x = module_pos_x + (SIZE_MODULE / 2);
		center_orig_y = module_pos_y + offset_y + (SIZE_MODULE / 2);
		radius = SIZE_MODULE / 2;
		context_popup.arc(center_orig_x, center_orig_y, radius, 0, Math.PI * 2, false);
		//context_popup.rect(module_pos_x, module_pos_y + offset_y, SIZE_MODULE, SIZE_MODULE);
		context_popup.fillStyle = module.status_color;
		context_popup.fill();
		context_popup.lineWidth = 1;
		context_popup.strokeStyle = "#000";
		context_popup.stroke();
		
		
		modules[key].pos_x = module_pos_x;
		modules[key].pos_y = module_pos_y + offset_y;
		
		context_popup.fillStyle = "rgb(0,0,0)";
		context_popup.font = 'bold 10px sans-serif';
		context_popup.textBaseline = 'middle';
		context_popup.textAlign = 'center';
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
	
	window.opener.update_fictional_node(id, name, shape, networmap, radious, color);
}
