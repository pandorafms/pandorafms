// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

var creationItem = null;
var is_opened_palette = false;
var idItem = 0;
var selectedItem = null;
var lines = Array();
var user_lines = Array();
var toolbuttonActive = null;
var autosave = true;
var list_actions_pending_save = [];
var temp_id_item = 0;
var parents = {};

var obj_js_user_lines = null;


var SIZE_GRID = 16; //Const the size (for width and height) of grid.

var img_handler_start;
var img_handler_end;

var font;

function toggle_advance_options_palette(close) {
	if ($("#advance_options").css('display') == 'none') {
		$("#advance_options").css('display', '');
	}
	else {
		$("#advance_options").css('display', 'none');
	}

	if (close == false) {
		$("#advance_options").css('display', 'none');
	}
}

// Main function, execute in event documentReady
function visual_map_main() {
	img_handler_start = "images/dot_red.png";
	img_handler_end = "images/dot_green.png";
	get_image_url(img_handler_start).success(function (data) {
		img_handler_start = data;
	});
	get_image_url(img_handler_end).success(function (data) {
		img_handler_end = data;
	});

	//Get the actual system font.
	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_font"});
	parameter.push ({name: "id_visual_console",
		value: id_visual_console});
	jQuery.ajax({
		url: get_url_ajax(),
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
		{
			font = data['font'];
		}
	});

	//Get the list of posible parents
	parents = Base64.decode($("input[name='parents_load']").val());
	parents = eval("(" + parents + ")");

	eventsBackground();
	eventsItems();

	//Fixed to wait the load of images.
	$(window).load(function() {
			draw_lines(lines, 'background', true);

			draw_user_lines("", 0, 0, 0 , 0, 0, true);

			//~ center_labels();
		}
	);

	obj_js_user_lines = new jsGraphics("background");

	$("input[name='radio_choice']").on('change', function() {
		var radio_value = $("input[name='radio_choice']:checked").val();

		if ((creationItem == 'module_graph') || (selectedItem == 'module_graph')) {
			if (radio_value == "module_graph") {
				$("#custom_graph_row").css('display', 'none');
				$("#agent_row").css('display', '');
				$("#module_row").css('display', '');
				$("#type_graph").css('display', '');
			}
			else {
				$("#custom_graph_row").css('display', '');
				$("#agent_row").css('display', 'none');
				$("#module_row").css('display', 'none');
				$("#type_graph").css('display', 'none');
			}
		}
	});

	//Resize the view to adapt the screen size.
	if ($("#main").length) {
		//Console
		$("#frame_view").height($("#main").height() - 75);
	}
	else {
		//Metaconsole
		$("#frame_view").height($("#page").height() - 75);
	}
}

function cancel_button_palette_callback() {
	if (is_opened_palette) {
		toggle_item_palette();
	}
}

function get_url_ajax() {
	if (is_metaconsole()) {
		return "../../ajax.php";
	}
	else {
		return "ajax.php";
	}
}

var metaconsole = null;
function is_metaconsole() {
	if (metaconsole === null)
		metaconsole = $("input[name='metaconsole']").val();

	if (metaconsole != 0)
		return true;
	else
		return false;
}

function update_button_palette_callback() {


	var values = {};

	values = readFields();

	// TODO VALIDATE DATA
	switch (selectedItem) {
		case 'background':
			if(values['width'] == 0 && values['height'] == 0) {
				values['width'] =
					$("#hidden-background_original_width").val();
				values['height'] =
					$("#hidden-background_original_height").val();
			}
			$("#background").css('width', values['width']);
			$("#background").css('height', values['height']);

			//$("#background").css('background', 'url(images/console/background/' + values['background'] + ')');
			var image = values['background'];
			$("#background_img").attr('src', "images/spinner.gif");
			set_image("background", null, image);

			idElement = 0;
			break;
		case 'box_item':
			$("#" + idItem + " div").css('background-color', values['fill_color']);
			$("#" + idItem + " div").css('border-color', values['border_color']);
			$("#" + idItem + " div").css('border-width', values['border_width'] + "px");
			$("#" + idItem + " div").css('height', values['height_box'] + "px");
			$("#" + idItem + " div").css('width', values['width_box'] + "px");
			break;
		case 'group_item':
		case 'static_graph':
			$("#text_" + idItem).html(values['label']);

			if ((values['width'] != 0) && (values['height'] != 0)) {
				$("#image_" + idItem).attr('width', values['width']);
				$("#image_" + idItem).attr('height', values['height']);
				$("#" + idItem).css('width', values['width'] + 'px');
				$("#" + idItem).css('height', values['height'] + 'px');
			}
			else {
				$("#image_" + idItem).removeAttr('width');
				$("#image_" + idItem).removeAttr('height');
				$("#" + idItem +" img").css('width', '70px');
				$("#" + idItem +" img").css('height', '70px');
				$("#" + idItem).css('width', '70px');
				$("#" + idItem).css('height', '70px');
			}
			break;
		case 'percentile_bar':
		case 'percentile_item':
			$("#text_" + idItem).html(values['label']);
			$("#image_" + idItem).attr("src", "images/spinner.gif");
			if (values['type_percentile'] == 'bubble') {
				setPercentileBubble(idItem, values);
			}
			else {
				setPercentileBar(idItem, values);
			}

			break;
		case 'module_graph':
			$("#text_" + idItem).html(values['label']);
			$("#image_" + idItem).attr("src", "images/spinner.gif");
			setModuleGraph(idItem);
			break;
		case 'simple_value':
			$("#text_" + idItem).html(values['label']);
			//$("#simplevalue_" + idItem)
				//.html($('<img></img>').attr('src', "images/spinner.gif"));
			setModuleValue(idItem,values['process_simple_value'], values['period']);
			break;
		case 'label':
			$("#text_" + idItem).html(values['label']);
			break;
		case 'icon':
			$("#image_" + idItem).attr('src', "images/spinner.gif");

			if ((values['width'] != 0) && (values['height'] != 0)) {
				$("#image_" + idItem).attr('width', values['width']);
				$("#image_" + idItem).attr('height', values['height']);
				$("#" + idItem).css('width', values['width'] + 'px');
				$("#" + idItem).css('height', values['height'] + 'px');
			}
			else {
				$("#image_" + idItem).removeAttr('width');
				$("#image_" + idItem).removeAttr('height');
				$("#" + idItem).css('width', '70px');
				$("#" + idItem).css('height', '70px');
				
				$("#" + idItem +" img").css('width', '70px');
				$("#" + idItem +" img").css('height', '70px');
			}
			var image = values['image'] + ".png";
			set_image("image", idItem, image);
			break;
		default:
			//Maybe save in any Enterprise item.
			if (typeof(enterprise_update_button_palette_callback) == 'function') {
				enterprise_update_button_palette_callback(values);
			}
			break;
	}

	updateDB(selectedItem, idItem , values);

	toggle_item_palette();
}

function readFields() {
	metaconsole = $("input[name='metaconsole']").val();

	var values = {};

	values['label'] = $("input[name=label]").val();


	var text = tinymce.get('text-label').getContent();
	values['label'] = text;
	values['type_graph'] = $("select[name=type_graph]").val();
	values['image'] = $("select[name=image]").val();
	values['background_color'] = $("select[name=background_color]").val();
	values['left'] = $("input[name=left]").val();
	values['top'] = $("input[name=top]").val();
	values['agent'] = $("input[name=agent]").val();
	values['module'] = $("select[name=module]").val();
	values['process_simple_value'] = $("select[name=process_value]").val();
	values['background'] = $("#background_image").val();
	values['period'] = undefined != $("#hidden-period").val() ? $("#hidden-period").val() : $("#period").val();
	values['width'] = $("input[name=width]").val();
	values['height'] = $("input[name=height]").val();
	values['parent'] = $("select[name=parent]").val();
	values['map_linked'] = $("select[name=map_linked]").val();
	values['width_percentile'] = $("input[name=width_percentile]").val();
	values['max_percentile'] = $("input[name=max_percentile]").val();
	values['width_module_graph'] = $("input[name=width_module_graph]").val();
	values['height_module_graph'] = $("input[name=height_module_graph]").val();
	values['type_percentile'] = $("input[name=type_percentile]:checked").val();
	values['value_show'] = $("input[name=value_show]:checked").val();
	values['enable_link'] = $("input[name=enable_link]").is(':checked') ? 1 : 0;
	values['id_group'] = $("select[name=group]").val();
	values['id_custom_graph'] = parseInt(
		$("#custom_graph option:selected").val());
	values['width_box'] = parseInt(
		$("input[name='width_box']").val());
	values['height_box'] = parseInt(
		$("input[name='height_box']").val());
	values['border_color'] = $("input[name='border_color']").val();
	values['border_width'] = parseInt(
		$("input[name='border_width']").val());
	values['fill_color'] = $("input[name='fill_color']").val();
	values['line_width'] = parseInt(
		$("input[name='line_width']").val());
	values['line_color'] = $("input[name='line_color']").val();

	if (is_metaconsole()) {
		values['metaconsole'] = 1;
		values['id_agent'] = $("#hidden-agent").val();
		values['server_name'] = $("#id_server_name").val();
		values['server_id'] = $("input[name='id_server_metaconsole']").val();
	}
	else {
		values['metaconsole'] = 0;
	}

	if (typeof(enterprise_readFields) == 'function') {
		//The parameter is a object and the function can change or add
		//attributes.
		enterprise_readFields(values);
	}

	return values;
}

function create_button_palette_callback() {
	var values = readFields();

	//VALIDATE DATA
	var validate = true;
	switch (creationItem) {
		case 'box_item':
			break;
		case 'group_item':
		case 'static_graph':
			if ((values['label'] == '') && (values['image'] == '')) {
				alert($("#message_alert_no_label_no_image").html());
				validate = false;
			}
			break;
		case 'label':
			if ((values['label'] == '')) {
				alert($("#message_alert_no_label").html());
				validate = false;
			}
			break;
		case 'icon':
			if ((values['image'] == '')) {
				alert($("#message_alert_no_image").html());
				validate = false;
			}
			break;
		case 'percentile_bar':
		case 'percentile_item':
			if ((values['agent'] == '')) {
				alert($("#message_alert_no_agent").html());
				validate = false;
			}
			if ((values['module'] == 0)) {
				alert($("#message_alert_no_module").html());
				validate = false;
			}
			if ((values['max_percentile'] == '')) {
				alert($("#message_alert_no_max_percentile").html());
				validate = false;
			}
			if ((values['width_percentile'] == '')) {
				alert($("#message_alert_no_width_percentile").html());
				validate = false;
			}
			break;
		case 'module_graph':
			if (values['id_custom_graph'] == 0) {
				if ((values['agent'] == '')) {
					alert($("#message_alert_no_agent").html());
					validate = false;
				}
				if ((values['module'] == 0)) {
					alert($("#message_alert_no_module").html());
					validate = false;
				}
				if ((values['period'] == 0)) {
					alert($("#message_alert_no_period").html());
					validate = false;
				}
			}
			break;
		case 'simple_value':
			if ((values['agent'] == '')) {
				alert($("#message_alert_no_agent").html());
				validate = false;
			}
			if ((values['module'] == 0)) {
				alert($("#message_alert_no_module").html());
				validate = false;
			}
			break;
		default:
			//Maybe save in any Enterprise item.
			if (typeof(enterprise_create_button_palette_callback) == 'function') {
				validate = enterprise_create_button_palette_callback(values);
			}
			break;
	}

	if (validate) {
		switch (creationItem) {
			case 'line_item':
				create_line('step_1', values);
				break;
			default:
				insertDB(creationItem, values);
				break;
		}


		toggle_item_palette();
	}
}

function delete_user_line(idElement) {
	var found = null;

	jQuery.each(user_lines, function(iterator, user_line) {
		if (user_line['id'] == idElement) {
			found = iterator;
			return;
		}
	});

	if (found != null) {
		user_lines.splice(found, 1);
	}
}

function update_user_line(type, idElement, top, left) {
	jQuery.each(user_lines, function(iterator, user_line) {

		if (user_line['id'] != idElement)
			return;

		switch (type) {
			// -- line_item --
			case 'handler_start':
			// ---------------

				user_lines[iterator]['start_x'] = left;
				user_lines[iterator]['start_y'] = top;

				break;
			// -- line_item --
			case 'handler_end':
			// ---------------

				user_lines[iterator]['end_x'] = left;
				user_lines[iterator]['end_y'] = top;

				break;
		}
	});
}

function draw_user_lines(color, thickness, start_x, start_y , end_x,
	end_y, only_defined_lines) {


	obj_js_user_lines.clear();

	// Draw the previous lines
	for (iterator = 0; iterator < user_lines.length; iterator++) {

		obj_js_user_lines.setStroke(user_lines[iterator]['line_width']);
		obj_js_user_lines.setColor(user_lines[iterator]['line_color']);
		obj_js_user_lines.drawLine(
			parseInt(user_lines[iterator]['start_x']),
			parseInt(user_lines[iterator]['start_y']),
			parseInt(user_lines[iterator]['end_x']),
			parseInt(user_lines[iterator]['end_y']));
	}


	if (typeof(only_defined_lines) == "undefined") {
		only_defined_lines = false;
	}

	if (!only_defined_lines) {
		obj_js_user_lines.setStroke(thickness);
		obj_js_user_lines.setColor(color);
		obj_js_user_lines.drawLine(start_x, start_y, end_x, end_y);
	}

	obj_js_user_lines.paint();
}

function create_line(step, values) {

	$('.item').unbind('click');
	$('.item').unbind('dblclick');
	$('.item').unbind('dragstop');
	$('.item').unbind('dragstart');

	$('#background').unbind('click');
	$('#background').unbind('dblclick');

	switch (step) {
		case 'step_1':
			$("#background *").css("cursor", "crosshair");


			$("#background *")
				.on('mousemove', function(e) {

					$('#div_step_1').css({
						left:	e.offsetX,
						top:	e.offsetY
					});
					$('#div_step_1').show();

					// 2 for the black border of background
					values['line_start_x'] = e.offsetX;
					values['line_start_y'] = e.offsetY;

				});


			$("#background *")
				.on('click', function(e) {
					create_line('step_2', values);
				});

			break;
		case 'step_2':
			$('#div_step_1').hide();
			$("#background *").off('mousemove');
			$("#background *").off('click');


			$("#background *")
				.on('mousemove', function(e) {

					$('#div_step_2').css({
						left:	e.offsetX,
						top:	e.offsetY
					});
					$('#div_step_2').show();

					// 2 for the black border of background
					values['line_end_x'] = e.offsetX;
					values['line_end_y'] = e.offsetY;

					draw_user_lines(
						values['line_color'],
						values['line_width'],
						values['line_start_x'],
						values['line_start_y'],
						values['line_end_x'],
						values['line_end_y']);
				});

			$("#background *")
				.on('click', function(e) {
					create_line('step_3', values);
				});
			break;
		case 'step_3':
			$('#div_step_2').hide();
			$("#background *").off('mousemove');
			$("#background *").off('click');

			$("#background *").css("cursor", "");

			insertDB("line_item", values);

			eventsItems();
			eventsBackground();
			break;
	}
}

function toggle_item_palette() {
	var item = null;

	if (is_opened_palette) {
		is_opened_palette = false;

		activeToolboxButton('static_graph', true);
		activeToolboxButton('module_graph', true);
		activeToolboxButton('simple_value', true);
		activeToolboxButton('label', true);
		activeToolboxButton('icon', true);
		activeToolboxButton('percentile_item', true);
		activeToolboxButton('group_item', true);
		activeToolboxButton('box_item', true);
		activeToolboxButton('line_item', true);

		if (typeof(enterprise_activeToolboxButton) == 'function') {
			enterprise_activeToolboxButton(true);
		}

		$(".item").draggable("enable");
		$("#background").resizable('enable');
		$("#properties_panel").hide("fast");

		toggle_advance_options_palette(false);
	}
	else {
		is_opened_palette = true;

		$(".item").draggable("disable");
		$("#background").resizable('disable');

		activeToolboxButton('static_graph', false);
		activeToolboxButton('module_graph', false);
		activeToolboxButton('simple_value', false);
		activeToolboxButton('label', false);
		activeToolboxButton('icon', false);
		activeToolboxButton('percentile_item', false);
		activeToolboxButton('group_item', false);
		activeToolboxButton('box_item', false);
		activeToolboxButton('line_item', false);

		activeToolboxButton('copy_item', false);
		activeToolboxButton('edit_item', false);
		activeToolboxButton('delete_item', false);
		activeToolboxButton('show_grid', false);

		if (typeof(enterprise_activeToolboxButton) == 'function') {
			enterprise_activeToolboxButton(false);
		}




		if (creationItem != null) {
			//Create a item

			activeToolboxButton(creationItem, true);
			item = creationItem;
			$("#button_update_row").css('display', 'none');
			$("#button_create_row").css('display', '');
			cleanFields(item);
			unselectAll();
		}
		else if (selectedItem != null) {
			//Edit a item

			item = selectedItem;
			toolbuttonActive = item;

			switch (item) {
				case 'handler_start':
				case 'handler_end':
					activeToolboxButton('line_item', true);
					break;
				default:
					activeToolboxButton(toolbuttonActive, true);
					break;
			}


			$("#button_create_row").css('display', 'none');
			$("#button_update_row").css('display', '');
			cleanFields();

			loadFieldsFromDB(item);
		}

		hiddenFields(item);



		$("#properties_panel").show("fast");
	}
}

function fill_parent_select(id_item) {
	//Populate the parent widget
	$("#parent option")
		.filter(function() { if ($(this).attr('value') != 0) return true; })
		.remove();
	jQuery.each(parents, function(key, value) {
		if (value == undefined) {
			return;
		}
		if (id_item == key) {
			return; //continue
		}

		$("#parent").append($('<option value="' + key + '">' +
			value + '</option>'));
	});
}

function loadFieldsFromDB(item) {
	$("#loading_in_progress_dialog").dialog({
		resizable: true,
		draggable: true,
		modal: true,
		height: 100,
		width: 200,
		overlay: {
			opacity: 0.5,
			background: "black"
		}
	});


	parameter = Array();
	parameter.push ({name: "page",
		value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "load"});
	parameter.push ({name: "id_visual_console",
		value: id_visual_console});
	parameter.push ({name: "type", value: item});
	parameter.push ({name: "id_element", value: idItem});

	set_label = false;

	jQuery.ajax({
		url: get_url_ajax(),
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data) {
			var moduleId = 0;

			fill_parent_select(idItem);
			
			jQuery.each(data, function(key, val) {
				if (key == 'background')
					$("#background_image").val(val);
				if (key == 'width') $("input[name=width]").val(val);
				if (key == 'height')
					$("input[name=height]").val(val);

				if (key == 'label') {
					tinymce.get('text-label')
						.setContent("");
					$("input[name=label]").val("");

					tinymce.get('text-label').setContent(val);
					$("input[name=label]").val(val);
				}

				if (key == 'enable_link') {
					if (val == "1") {
						$("input[name=enable_link]")
							.prop("checked", true);
					}
					else {
						$("input[name=enable_link]")
							.prop("checked", false);
					}
				}
				
				if (key == 'type_graph') {
					if (val == "area") {
						$("select[name=type_graph]").val(val);
					}
					else {
						$("select[name=type_graph]").val(val);
					}
				}

				if (key == 'image') {
					//Load image preview
					$("select[name=image]").val(val);
					$("select[name=background_color]").val(val);
					showPreview(val);
				}

				if (key == 'pos_x') $("input[name=left]").val(val);
				if (key == 'pos_y') $("input[name=top]").val(val);
				if (key == 'agent_name') {
					$("input[name=agent]").val(val);
					//Reload no-sincrone the select of modules
				}
				if (key == 'modules_html') {
					$("select[name=module]").empty().html(val);
					$("select[name=module]").val(moduleId);
				}
				if (key == 'id_agente_modulo') {
					moduleId = val;
					$("select[name=module]").val(val);
				}
				if (key == 'process_value')
					$("select[name=process_value]").val(val);
				if (key == 'period') {
					var anySelected = false;
					var periodId = $('#hidden-period').attr('class');
					$('#' + periodId + '_select option')
						.each(function() {

						if($(this).val() == val) {
							$(this).prop('selected', true);
							$(this).trigger('change');
							anySelected = true;
						}
					});
					if (anySelected == false) {
						$('#' + periodId + '_select option')
							.eq(0).prop('selected', true);
						$('#' + periodId + '_units option')
							.eq(0).prop('selected', true);
						$('#hidden-period').val(val);
						$('#text-' + periodId + '_text').val(val);
						adjustTextUnits(periodId);
						$('#' + periodId + '_default').hide();
						$('#' + periodId + '_manual').show();
					}
				}
				if (key == 'width')
					$("input[name=width]").val(val);
				if (key == 'height')
					$("input[name=height]").val(val);
				if (key == 'parent_item')
					$("select[name=parent]").val(val);
				if (key == 'id_layout_linked')
					$("select[name=map_linked]").val(val);
				if (key == 'width_percentile')
					$("input[name=width_percentile]").val(val);
				if (key == 'max_percentile')
					$("input[name=max_percentile]").val(val);
				if (key == 'width_module_graph')
					$("input[name=width_module_graph]").val(val);
				if (key == 'height_module_graph')
					$("input[name=height_module_graph]").val(val);

				if (key == 'type_percentile') {
					if (val == 'percentile') {
						$("input[name=type_percentile][value=percentile]")
							.attr("checked", "checked");
					}
					else {
						$("input[name=type_percentile][value=bubble]")
							.attr("checked", "checked");
					}
				}

				if (key == 'value_show') {
					if (val == 'percent') {
						$("input[name=value_show][value=percent]")
							.attr("checked", "checked");
					}
					else {
						$("input[name=value_show][value=value]")
							.attr("checked", "checked");
					}
				}

				if (key == 'id_group') {
					$("select[name=group]").val(val);
				}


				if (is_metaconsole()) {
					if (key == 'id_agent') {
						$("#hidden-agent").val(val);
					}
					if (key == 'id_server_name') {
						$("#id_server_name").val(val);
					}
				}

				if (key == 'width_box')
					$("input[name='width_box']").val(val);
				if (key == 'height_box')
					$("input[name='height_box']").val(val);
				if (key == 'border_color') {
					$("input[name='border_color']").val(val);
					$("#border_color_row .ColorPickerDivSample")
						.css('background-color', val);
				}
				if (key == 'border_width')
					$("input[name='border_width']").val(val);
				if (key == 'fill_color') {
					$("input[name='fill_color']").val(val);
					$("#fill_color_row .ColorPickerDivSample")
						.css('background-color', val);
				}
				if (key == 'line_width')
					$("input[name='line_width']").val(val);
				if (key == 'line_color') {
					$("input[name='line_color']").val(val);
					$("#line_color_row .ColorPickerDivSample")
						.css('background-color', val);
				}

			});

			if (data.type == 1) {
				if (data.id_custom_graph > 0) {
					$("input[name='radio_choice'][value='custom_graph']")
						.prop('checked', true);
					$("input[name='radio_choice']").trigger('change');
				//	$("#custom_graph option[value=" + data.id_custom_graph + "]")
				//		.prop("selected", true);
				}
				else {
					$("input[name='radio_choice'][value='module_graph']")
						.prop('checked', true);
					$("input[name='radio_choice']").trigger('change');
				}
			}

			if (typeof(enterprise_loadFieldsFromDB) == 'function') {
				enterprise_loadFieldsFromDB(data);
			}

			$("#loading_in_progress_dialog").dialog("close");
		}
	});
}

function setAspectRatioBackground(side) {
	toggle_item_palette();

	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_original_size_background"});
	parameter.push ({name: "background", value: $("#background_img").attr('src')});

	jQuery.ajax({
		url: "ajax.php",
		data: parameter,
		type: "POST",
		dataType: "json",
		success: function(data) {
			old_width = parseInt($("#background").css('width').replace('px', ''));
			old_height = parseInt($("#background").css('height').replace('px', ''));

			img_width = data[0];
			img_height = data[1];

			if (side == 'width') {
				ratio = old_width / img_width;

				width = old_width;
				height = img_height * ratio;
			}
			else if (side == 'height') {
				ratio = old_height / img_height;

				width = img_width * ratio;
				height = old_height;
			}
			else if (side == 'original') {
				width = img_width;
				height = img_height;
			}

			var values = {};
			values['width'] = width;
			values['height'] = height;

			updateDB('background', 0, values);

			move_elements_resize(old_width, old_height, width, height);
		}
	});

	toggle_item_palette();
}

function hiddenFields(item) {

	//The method to hidden and show is
	//a row have a id and multiple class
	//then the steps is
	//- hide the row with <tr id="<id>">...</tr>
	//  or hide <tr class="title_panel_span">...</tr>
	//- unhide the row with <tr id="<id>" class="<item> ...">...</tr>
	//  or <tr id="title_panel_span_<item>">...</tr>

	$(".title_panel_span").css('display', 'none');
	$("#title_panel_span_" + item).css('display', 'inline');

	$("#label_row").css('display', 'none');
	$("#label_row." + item).css('display', '');

	$("#image_row").css('display', 'none');
	$("#image_row." + item).css('display', '');

	$("#enable_link_row").css('display', 'none');
	$("#enable_link_row." + item).css('display', '');

	$("#preview_row").css('display', 'none');
	$("#preview_row." + item).css('display', '');

	$("#position_row").css('display', 'none');
	$("#position_row." + item).css('display', '');

	$("#agent_row").css('display', 'none');
	$("#agent_row." + item).css('display', '');

	$("#module_row").css('display', 'none');
	$("#module_row." + item).css('display', '');

	$("#group_row").css('display', 'none');
	$("#group_row." + item).css('display', '');

	$("#process_value_row").css('display', 'none');
	$("#process_value_row." + item).css('display', '');

	$("#background_row_1").css('display', 'none');
	$("#background_row_1." + item).css('display', '');

	$("#background_row_2").css('display', 'none');
	$("#background_row_2." + item).css('display', '');

	$("#background_row_3").css('display', 'none');
	$("#background_row_3." + item).css('display', '');

	$("#background_row_4").css('display', 'none');
	$("#background_row_4." + item).css('display', '');

	$("#percentile_bar_row_1").css('display', 'none');
	$("#percentile_bar_row_1." + item).css('display', '');

	$("#percentile_bar_row_2").css('display', 'none');
	$("#percentile_bar_row_2." + item).css('display', '');

	$("#percentile_item_row_3").css('display', 'none');
	$("#percentile_item_row_3." + item).css('display', '');

	$("#percentile_item_row_4").css('display', 'none');
	$("#percentile_item_row_4." + item).css('display', '');

	$("#period_row").css('display', 'none');
	$("#period_row." + item).css('display', '');

	$("#size_row").css('display', 'none');
	$("#size_row." + item).css('display', '');

	$("#parent_row").css('display', 'none');
	$("#parent_row." + item).css('display', '');

	$("#map_linked_row").css('display', 'none');
	$("#map_linked_row." + item).css('display', '');

	$("#module_graph_size_row").css('display', 'none');
	$("#module_graph_size_row." + item).css('display', '');

	$("#background_color").css('display', 'none');
	$("#background_color." + item).css('display', '');
	
	$("#type_graph").css('display', 'none');
	$("#type_graph." + item).css('display', '');

	$("#radio_choice_graph").css('display', 'none');
	$("#radio_choice_graph." + item).css('display', '');

	$("#custom_graph_row").css('display', 'none');
	$("#custom_graph_row." + item).css('display', '');

	$("#box_size_row").css('display', 'none');
	$("#box_size_row." + item).css('display', '');

	$("#border_color_row").css('display', 'none');
	$("#border_color_row." + item).css('display', '');

	$("#border_width_row").css('display', 'none');
	$("#border_width_row." + item).css('display', '');

	$("#fill_color_row").css('display', 'none');
	$("#fill_color_row." + item).css('display', '');

	$("#line_color_row").css('display', 'none');
	$("#line_color_row." + item).css('display', '');

	$("#line_width_row").css('display', 'none');
	$("#line_width_row." + item).css('display', '');

	$("#line_case").css('display', 'none');
	$("#line_case." + item).css('display', '');





	$("input[name='radio_choice']").trigger('change');

	if (typeof(enterprise_hiddenFields) == 'function') {
		enterprise_hiddenFields(item);
	}

	//~ var code_control = tinyMCE.activeEditor.controlManager.controls['text-label_code'];
	//~ if (item == 'label') {
		//~ code_control.setDisabled(false);
	//~ }
	//~ else {
		//~ code_control.setDisabled(true);
	//~ }
}

function cleanFields(item) {
	$("input[name=label]").val('');
	tinymce.get('text-label').setContent("");
	$("select[name=image]").val('');
	$("input[name=left]").val(0);
	$("input[name=top]").val(0);
	$("input[name=agent]").val('');
	$("select[name=module]").val('');
	$("input[name=process_value]").val('');
	$("select[name=background_image]").val('');
	$("input[name=width_percentile]").val('');
	$("input[name=max_percentile]").val('');
	$("select[name=period]").val('');
	$("input[name=width]").val(0);
	$("input[name=height]").val(0);
	$("select[name=parent]").val('');
	$("select[name=map_linked]").val('');
	$("input[name=width_module_graph]").val(300);
	$("input[name=height_module_graph]").val(180);
	$("input[name='width_box']").val(300);
	$("input[name='height_box']").val(180);
	$("input[name='border_color']").val('#000000');
	$("input[name='border_width']").val(3);
	$("input[name='fill_color']").val('#ffffff');
	$("input[name='line_width']").val(3);
	$("input[name='line_color']").val('#000000');


	$("#preview").empty();


	if (item == "simple_value") {
		$("input[name=label]").val('(_VALUE_)');
		tinymce.get('text-label').setContent("(_VALUE_)");
	}

	//fill_parent_select();

	var anyText = $("#any_text").html(); //Trick for catch the translate text.
	$("#module")
		.empty()
		.append($('<option value="0" selected="selected">' + anyText + '</option></select>'));

	//Code for the graphs
	$("input[name='radio_choice'][value='module_graph']")
		.prop('checked', true);
	$("input[name='radio_choice']").trigger('change');

	//Select none custom graph
	$("#custom_graph option[value=0]")
		.prop('selected', true);

}

function set_static_graph_status(idElement, image, status) {
	$("#image_" + idElement).attr('src', "images/spinner.gif");

	if (typeof(status) == 'undefined') {
		var parameter = Array();
		parameter.push ({
			name: "page",
			value: "include/ajax/visual_console_builder.ajax"});
		parameter.push ({
			name: "get_element_status",
			value: "1"});
		parameter.push ({
			name: "id_element",
			value: idElement});
		parameter.push ({name: "id_visual_console",
			value: id_visual_console});

		if (is_metaconsole()) {
			parameter.push ({name: "metaconsole", value: 1});
		}
		else {
			parameter.push ({name: "metaconsole", value: 0});
		}

		$('#hidden-status_' + idElement).val(3);
		jQuery.ajax ({
			type: 'POST',
			url: get_url_ajax(),
			data: parameter,
			success: function (data) {
				set_static_graph_status(idElement, image, data);
			}
		});

		return;
	}

	switch (status) {
		case '1':
			//Critical (BAD)
			suffix = "_bad.png";
			break;
		case '4':
			//Critical (ALERT)
			suffix = "_bad.png";
			break;
		case '0':
			//Normal (OK)
			suffix = "_ok.png";
			break;
		case '2':
			//Warning
			suffix = "_warning.png";
			break;
		case '3':
		default:
			//Unknown
			suffix = ".png";
			break;
	}

	set_image("image", idElement, image  + suffix);
}

function set_image(type, idElement, image) {
	if (type == "image") {
		item = "#image_" + idElement;
		img_src = "images/console/icons/" + image;
	}
	else if (type == "background") {
		item = "#background_img";
		img_src = "images/console/background/" + image;
	}

	var params = [];
	params.push("get_image_path=1");
	params.push("img_src=" + img_src);
	params.push("page=include/ajax/skins.ajax");
	params.push("only_src=1");
	params.push ({name: "id_visual_console",
		value: id_visual_console});
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: get_url_ajax(),
		success: function (data) {
			$(item).attr('src', data);
		}
	});
}

function setModuleGraph(id_data) {
	var parameter = Array();

	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_layout_data"});
	parameter.push ({name: "id_element", value: id_data});
	parameter.push ({name: "id_visual_console", value: id_visual_console});

	jQuery.ajax({
		url: get_url_ajax(),
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data) {
			id_agente_modulo = data['id_agente_modulo'];
			id_custom_graph = data['id_custom_graph'];
			label = data['label'];
			height = (data['height']);
			width = (data['width']);
			period = data['period'];
			background_color = data['image'];

			if (is_metaconsole()) {
				id_metaconsole = data['id_metaconsole'];
			}

			//Cleaned array
			parameter = Array();

			parameter.push ({name: "page",
				value: "include/ajax/visual_console_builder.ajax"});
			parameter.push ({name: "action", value: "get_image_sparse"});
			parameter.push ({name: "id_agent_module", value: id_agente_modulo});
			parameter.push ({name: "id_custom_graph", value: id_custom_graph});
			if (is_metaconsole()) {
				parameter.push ({name: "id_metaconsole", value: id_metaconsole});
			}
			parameter.push ({name: "type", value: 'module_graph'});
			parameter.push ({name: "height", value: height});
			parameter.push ({name: "width", value: width});
			parameter.push ({name: "period", value: period});
			parameter.push ({name: "background_color", value: background_color});
			parameter.push ({name: "id_visual_console",
					value: id_visual_console});
			jQuery.ajax({
				url: get_url_ajax(),
				data: parameter,
				type: "POST",
				dataType: 'json',
				success: function (data)
				{
					if (data['no_data'] == true) {
						$('#' + id_data).html(data['url']);
					}
					else {
						if($("#module_row").css('display')!='none'){
							$("#" + id_data + " img").attr('src', 'images/console/signes/module_graph.png');
							$("#" + id_data + " img").css('width', $('#text-width_module_graph').val()+'px');
							$("#" + id_data + " img").css('height', $('#text-height_module_graph').val()+'px');
						}else{
							$("#" + id_data + " img").attr('src', 'images/console/signes/custom_graph.png');
							$("#" + id_data + " img").css('width', $('#text-width_module_graph').val()+'px');
							$("#" + id_data + " img").css('height', $('#text-height_module_graph').val()+'px');	
						}
					}
				}
			});
		}
	});


}

function setModuleValue(id_data, process_simple_value, period) {
	var parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_module_value"});
	parameter.push ({name: "id_element", value: id_data});
	parameter.push ({name: "period", value: period});
	parameter.push ({name: "id_visual_console", value: id_visual_console});
	if (process_simple_value != undefined) {
		parameter.push ({name: "process_simple_value", value: process_simple_value});
	}
	
	jQuery.ajax({
		url: get_url_ajax(),
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data) {
			var currentValue = $("#text_" + id_data).html();
			currentValue = currentValue.replace(/_VALUE_/gi, data.value);
			$("#text_" + id_data).html('Data value');
		}
	});
}

function setPercentileBar(id_data, values) {
	metaconsole = $("input[name='metaconsole']").val();

	var url_hack_metaconsole = '';
	if (is_metaconsole()) {
		url_hack_metaconsole = '../../';
	}

	max_percentile = values['max_percentile'];
	width_percentile = values['width_percentile'];

	var parameter = Array();

	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_module_value"});
	parameter.push ({name: "id_element", value: id_data});
	parameter.push ({name: "value_show", value: values['value_show']});
	parameter.push ({name: "id_visual_console",
		value: id_visual_console});
	jQuery.ajax({
		url: get_url_ajax(),
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data) {
			module_value = data['value'];
			//max_percentile = data['max_percentile'];
			//width_percentile = data['width_percentile'];
			unit_text = false;

			if ((data['unit_text'] != false) || typeof(data['unit_text']) != 'boolean') {
				unit_text = data['unit_text'];
			}

			colorRGB = data['colorRGB'];

			if ( max_percentile > 0)
				var percentile = Math.round(module_value / max_percentile * 100);
			else
				var percentile = 100;

			if (unit_text == false && typeof(unit_text) == 'boolean') {
				value_text = percentile + "%";
			}
			else {
				value_text = module_value + " " + unit_text;
			}

			var img = url_hack_metaconsole + 'include/graphs/fgraph.php?homeurl=../../&graph_type=progressbar&height=15&' +
				'width=' + width_percentile + '&mode=1&progress=' + percentile +
				'&font=' + font + '&value_text=' + value_text + '&colorRGB=' + colorRGB;

			$("#"+  id_data).attr('src', img);
			
			$("#" + id_data + " img").attr('src', 'images/console/signes/percentil.png');
			$("#" + id_data + " img").css('width', $('#text-width_percentile').val()+'px');
			$("#" + id_data + " img").css('height', '30px');
		}
	});
}

function setPercentileBubble(id_data, values) {
	metaconsole = $("input[name='metaconsole']").val();

	var url_hack_metaconsole = '';
	if (is_metaconsole()) {
		url_hack_metaconsole = '../../';
	}

	max_percentile = values['max_percentile'];
	width_percentile = values['width_percentile'];

	var parameter = Array();

	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_module_value"});
	parameter.push ({name: "id_element", value: id_data});
	parameter.push ({name: "value_show", value: values['value_show']});
	parameter.push ({name: "id_visual_console",
		value: id_visual_console});
	jQuery.ajax({
		url: get_url_ajax(),
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data) {
			module_value = data['value'];
			//max_percentile = data['max_percentile'];
			//width_percentile = data['width_percentile'];
			unit_text = false
			if ((data['unit_text'] != false) || typeof(data['unit_text']) != 'boolean')
				unit_text = data['unit_text'];
			colorRGB = data['colorRGB'];

			if ( max_percentile > 0)
				var percentile = Math.round(module_value / max_percentile * 100);
			else
				var percentile = 100;

			if (unit_text == false && typeof(unit_text) == 'boolean') {
				value_text = percentile + "%";
			}
			else {
				value_text = module_value + " " + unit_text;
			}

			var img = url_hack_metaconsole + 'include/graphs/fgraph.php?homeurl=../../&graph_type=progressbubble&height=' + width_percentile + '&' +
				'width=' + width_percentile + '&mode=1&progress=' + percentile +
				'&font=' + font + '&value_text=' + value_text + '&colorRGB=' + colorRGB;

			$("#image_" + id_data).attr('src', img);
			
			$("#" + id_data + " img").attr('src', 'images/console/signes/percentil_bubble.png');
			$("#" + id_data + " img").css('width', $('#text-width_percentile').val()+'px');
			$("#" + id_data + " img").css('height', $('#text-width_percentile').val()+'px');
			
		}
	});
}

function get_image_url(img_src) {
	var img_url= null;
	var parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/skins.ajax"});
	parameter.push ({name: "get_image_path", value: true});
	parameter.push ({name: "img_src", value: img_src});
	parameter.push ({name: "only_src", value: true});

	return $.ajax ({
		type: 'GET',
		url: get_url_ajax(),
		cache: false,
		data: parameter
	});
}

function set_color_line_status(lines, line, id_data, values) {
	metaconsole = $("input[name='metaconsole']").val();



	var parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_color_line"});
	parameter.push ({name: "id_element", value: id_data});

	var color = null;

	jQuery.ajax({
		url: get_url_ajax(),
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data) {
			color = data['color_line'];

			var line = {
				"id": id_data,
				"node_begin":  values['parent'],
				"node_end": id_data,
				"color": color };


			lines.push(line);

			refresh_lines(lines, 'background', true);
		}
	});

}




function createItem(type, values, id_data) {
	var sizeStyle = '';
	var imageSize = '';
	var item = null;

	metaconsole = $("input[name='metaconsole']").val();


	switch (type) {
		case 'box_item':
			item = $('<div id="' + id_data + '" '
				+ 'class="item box_item" '
				+ 'style="text-align: center; '
					+ 'position: absolute; '
					+ 'display: inline-block; '
					+ 'z-index: 1; '
					+ 'top: ' + values['top'] + 'px; '
					+ 'left: ' + values['left'] + 'px;">'
					+ '<div '
					+ 'style=" '
					+ 'width: ' + values['width_box'] + 'px;'
					+ 'height: ' + values['height_box'] + 'px;'
					+ 'border-style: solid;'
					+ 'border-width: ' + values['border_width'] + 'px;'
					+ 'border-color: ' + values['border_color'] + ';'
					+ 'background-color: ' + values['fill_color'] + ';'
					+ '">'
					+ '</div>'
				+ '</div>'
				+ '<input id="hidden-status_' + id_data + '" '
					+ 'type="hidden" value="0" '
					+ 'name="status_' + id_data + '">'
			);
			break;
		case 'group_item':
		case 'static_graph':
			switch (type) {
				case 'group_item':
					class_type = "group_item";
					break;
				case 'static_graph':
					class_type = "static_graph";
					break;
			}

			img_src = "images/spinner.gif";

			item = $('<div></div>')
				.attr('id', id_data)
				.attr('class', 'item ' + class_type)
				.css('text-align', 'center')
				.css('position', 'absolute')
				.css('display', 'inline-block')
				.css('top', values['top'] + 'px')
				.css('left', values['left'] + 'px');
			if ((values['width'] == 0) && (values['height'] == 0)) {
				// Do none
			}
			else {
				item.css('width','70'  + 'px')
					.css('height', '70' + 'px');
			}

			var $image = $('<img></img>')
				.attr('id', 'image_' + id_data)
				.attr('class', 'image')
				.attr('src', img_src);
			if ((values['width'] == 0) && (values['height'] == 0)) {
				// Do none
				$image.attr('width', '70')
					.attr('height', '70');
			}
			else {
				$image.attr('width', values['width'])
					.attr('height', values['height']);
			}

			var $span = $('<span></span>')
				.attr('id', 'text_' + id_data)
				.attr('class', 'text')
				.append(values['label']);

			var $input = $('<input></input>')
				.attr('id', 'hidden-status_' + id_data)
				.attr('type', 'hidden')
				.attr('value', -1)
				.attr('name', 'status_' + id_data);

			item
				.append($image)
				.append($image)
				.append($span)
				.append($input);

			set_static_graph_status(id_data, values['image']);

			break;
		case 'percentile_bar':
		case 'percentile_item':
			var sizeStyle = '';
			var imageSize = '';

			if (values['type_percentile'] == 'percentile') {
				item = $('<div id="' + id_data + '" class="item percentile_item" style="text-align: center; position: absolute; display: inline-block; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
						'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' +
						'<img class="image" id="image_' + id_data + '" src="images/spinner.gif" />' +
						'</div>'
				);

				setPercentileBar(id_data, values);
			}
			else {
				item = $('<div id="' + id_data + '" class="item percentile_item" style="text-align: center; position: absolute; display: inline-block; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
						'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' +
						'<img class="image" id="image_' + id_data + '" src="images/spinner.gif" />' +
						'</div>'
				);

				setPercentileBubble(id_data, values);
			}
			break;
		case 'module_graph':
			sizeStyle = '';
			imageSize = '';

			item = $('<div id="' + id_data + '" class="item module_graph" style="text-align: center; position: absolute; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
					'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' +
					'<img class="image" id="image_' + id_data + '" src="images/spinner.gif" style="border:1px solid #808080;" />' +
				'</div>'
			);
			setModuleGraph(id_data);
			break;
		case 'simple_value':
			sizeStyle = '';
			imageSize = '';
			item = $('<div id="' + id_data + '" class="item simple_value" style="position: absolute; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
					'<span id="text_' + id_data + '" class="text"> ' + values['label'] + '</span> ' + '</div>'
			);
			setModuleValue(id_data,values.process_simple_value,values.period);
			break;
		case 'label':
			item = $('<div id="' + id_data + '" ' +
						'class="item label" ' +
						'style="text-align: left; position: absolute; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;"' +
					'>' +
					'<span id="text_' + id_data + '" class="text">' +
						values['label'] +
					'</span>' +
					'</div>'
				);
			break;
		case 'icon':
			if ((values['width'] == 0) && (values['height'] == 0)) {
				sizeStyle = 'width: ' + '70'  + 'px; height: ' + '70' + 'px;';
				imageSize = 'width="' + '70'  + '" height="' + '70' + '"';
			}
			else {
				sizeStyle = 'width: ' + values['width']  + 'px; height: ' + values['height'] + 'px;';
				imageSize = 'width="' + values['width']  + '" height="' + values['height'] + '"';
			}

			item = $('<div id="' + id_data + '" class="item icon" style="text-align: center; position: absolute; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
				'<img id="image_' + id_data + '" class="image" src="images/spinner.gif" ' + imageSize + ' /><br />' +
				'</div>'
			);
			var image = values['image'] + ".png";
			set_image("image", id_data, image);
			break;
		default:
			//Maybe create in any Enterprise item.
			if (typeof(enterprise_createItem) == 'function') {
				values['image'] = 'visualmap.services';
				temp_item = enterprise_createItem(type, values, id_data);
				if (temp_item != false) {
					item = temp_item;
				}
			}
			break;
	}

	$("#background").append(item);
	$(".item").css('z-index', '2');
	$(".box_item").css('z-index', '1');

	if (values['parent'] != 0) {
		var line = {"id": id_data,
			"node_begin":  values['parent'],
			"node_end": id_data,
			"color": '#cccccc' };

		lines.push(line);

		set_color_line_status(lines, line, id_data, values);

		refresh_lines(lines, 'background', true);
	}
}

function addItemSelectParents(id_data, text) {
	parents[id_data] = text;
	//$("#parent").append($('<option value="' + id_data + '" selected="selected">' + text + '</option></select>'));
}

function insertDB(type, values) {
	metaconsole = $("input[name='metaconsole']").val();

	$("#saving_in_progress_dialog").dialog({
		resizable: true,
		draggable: true,
		modal: true,
		height: 100,
		width: 200,
		overlay: {
			opacity: 0.5,
			background: "black"
		}
	});


	var id = null;

	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "insert"});
	parameter.push ({name: "id_visual_console", value: id_visual_console});
	parameter.push ({name: "type", value: type});
	jQuery.each(values, function(key, val) {
		parameter.push ({name: key, value: val});
	});

	jQuery.ajax({
		url: get_url_ajax(),
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data) {
			if (data['correct']) {
				id = data['id_data'];
				createItem(type, values, id);
				addItemSelectParents(id, data['text']);
				//Reload all events for the item and new item.
				eventsItems();

				switch (type) {
					case 'line_item':
						var line = {
							"id": id,
							"start_x":		values['line_start_x'],
							"start_y":		values['line_start_y'],
							"end_x":		values['line_end_x'],
							"end_y":		values['line_end_y'],
							"line_width":	values['line_width'],
							"line_color":	values['line_color']};

						user_lines.push(line);

						// Draw handlers
						radious_handle = 6;

						// Draw handler start
						item = $('<div id="handler_start_' + id + '" ' +
							'class="item handler_start" ' +
							'style="text-align: center; ' +
								'z-index: 1;' +
								'position: absolute; ' +
								'top: ' + (values['line_start_y']  - radious_handle) + 'px; ' +
								'left: ' + (values['line_start_x']  - radious_handle) + 'px;">' +

								'<img src="' + img_handler_start + '" />' +

							'</div>'
						);
						$("#background").append(item);

						// Draw handler stop
						item = $('<div id="handler_end_' + id + '" ' +
							'class="item handler_end" ' +
							'style="text-align: center; ' +
								'z-index: 1;' +
								'position: absolute; ' +
								'top: ' + (values['line_end_y']  - radious_handle) + 'px; ' +
								'left: ' + (values['line_end_x']  - radious_handle) + 'px;">' +

								'<img src="' + img_handler_end + '" />' +

							'</div>'
						);
						$("#background").append(item);
						break;
				}

				$("#saving_in_progress_dialog").dialog("close");
				//Reload all events for the item and new item.
				eventsItems();
			}
			else {
				//TODO
			}
		}
	});
}

function updateDB_visual(type, idElement , values, event, top, left) {
	metaconsole = $("input[name='metaconsole']").val();


	radious_handle = 6;

	switch (type) {
		case 'handler_start':
			$("#handler_start_" + idElement)
				.css('top', (top - radious_handle) + 'px');
			$("#handler_start_" + idElement)
				.css('left', left + 'px');
			break;
		case 'handler_end':

			$("#handler_end_" + idElement).css('top', (top - radious_handle) + 'px');
			$("#handler_end_" + idElement).css('left', (left) + 'px');
			break;
		case 'group_item':
		case 'static_graph':
			if ((event != 'resizestop') && (event != 'show_grid')
				&& (event != 'dragstop')) {

				set_static_graph_status(idElement, values['image']);

			}
		case 'percentile_item':
		case 'simple_value':
		case 'label':
		case 'icon':
		case 'module_graph':

			
			if (type == 'simple_value') {
				setModuleValue(idElement,
					values.process_simple_value,
						values.period);
			}
			
			
			if ((typeof(values['mov_left']) != 'undefined') &&
					(typeof(values['mov_top']) != 'undefined')) {
				$("#" + idElement).css('top', '0px').css('top', top + 'px');
				$("#" + idElement).css('left', '0px').css('left', left + 'px');
			}
			else if ((typeof(values['absolute_left']) != 'undefined') &&
					(typeof(values['absolute_top']) != 'undefined')) {
				$("#" + idElement).css('top', '0px').css('top', top + 'px');
				$("#" + idElement).css('left', '0px').css('left', left + 'px');
			}

			//Update the lines
			end_foreach = false;
			found = false;
			jQuery.each(lines, function(i, line) {
				if (end_foreach) {
					return;
				}

				if (lines[i]['node_end'] == idElement) {
					found = true;
					if (values['parent'] == 0) {
						//Erased the line
						lines.splice(i, 1);
						end_foreach = true;
					}
					else {
						if ((typeof(values['mov_left']) == 'undefined') &&
							(typeof(values['mov_top']) == 'undefined') &&
							(typeof(values['absolute_left']) == 'undefined') &&
							(typeof(values['absolute_top']) == 'undefined')) {
							lines[i]['node_begin'] = values['parent'];
						}
					}
				}
			});
			
			if (typeof(values['parent']) != 'undefined' && values['parent'] > 0 ) {
				if (!found) {
					set_color_line_status(lines, line, idElement, values);
				}
			}
			
			break;
		case 'background':
			if(values['width'] == '0' || values['height'] == '0'){
				$("#background").css('width', $("#hidden-background_width").val() + 'px');
				$("#background").css('height', $("#hidden-background_height").val() + 'px');
			}
			else {
				$("#background").css('width', values['width'] + 'px');
				$("#background").css('height', values['height'] + 'px');
			}
			break;
		case 'service':
			refresh_lines(lines, 'background', true);
			break;
	}
	refresh_lines(lines, 'background', true);
	draw_user_lines("", 0, 0, 0 , 0, 0, true);
}

function updateDB(type, idElement , values, event) {
	metaconsole = $("input[name='metaconsole']").val();



	var top = 0;
	var left = 0;

	action = "update";

	//Check if the event parameter in function is passed in the call.
	if (event != null) {
		switch (event) {
			case 'show_grid':
			case 'resizestop':
			//Force to move action when resize a background, for to avoid
			//lost the label.
			case 'dragstop':

				switch (type) {
					case 'handler_start':
						idElement = idElement.replace("handler_start_", "");
						break;
					case 'handler_end':
						idElement = idElement.replace("handler_end_", "");
						break;
				}

				action = "move";
				break;
		}
	}

	parameter = Array();
	parameter.push({name: "page",
		value: "include/ajax/visual_console_builder.ajax"});
	parameter.push({name: "action", value: action});
	parameter.push({name: "id_visual_console",
		value: id_visual_console});
	parameter.push({name: "type", value: type});
	parameter.push({name: "id_element", value: idElement});

	jQuery.each(values, function(key, val) {
		parameter.push({name: key, value: val});
	});


	switch (type) {
		// -- line_item --
		case 'handler_start':
		// ---------------

			if ((typeof(values['mov_left']) != 'undefined') &&
				(typeof(values['mov_top']) != 'undefined')) {
				top = parseInt($("#handler_start_" + idElement)
					.css('top').replace('px', ''));
				left = parseInt($("#handler_start_" + idElement)
					.css('left').replace('px', ''));
			}
			else if ((typeof(values['absolute_left']) != 'undefined') &&
				(typeof(values['absolute_top']) != 'undefined')) {
				top = values['absolute_top'];
				left = values['absolute_left'];
			}

			//Added the radious of image point of handler
			top = top + 6;
			left = left + 6;

			update_user_line(type, idElement, top, left);
			break;
		// -- line_item --
		case 'handler_end':
		// ---------------
			if ((typeof(values['mov_left']) != 'undefined') &&
				(typeof(values['mov_top']) != 'undefined')) {
				top = parseInt($("#handler_end_" + idElement)
					.css('top').replace('px', ''));
				left = parseInt($("#handler_end_" + idElement)
					.css('left').replace('px', ''));
			}
			else if ((typeof(values['absolute_left']) != 'undefined') &&
				(typeof(values['absolute_top']) != 'undefined')) {
				top = values['absolute_top'];
				left = values['absolute_left'];
			}

			//Added the radious of image point of handler
			top = top + 6;
			left = left + 6;

			update_user_line(type, idElement, top, left);
			break;
		default:

			if ((typeof(values['mov_left']) != 'undefined') &&
				(typeof(values['mov_top']) != 'undefined')) {
				top = parseInt($("#" + idElement)
					.css('top').replace('px', ''));
				left = parseInt($("#" + idElement)
					.css('left').replace('px', ''));
			}
			else if ((typeof(values['absolute_left']) != 'undefined') &&
				(typeof(values['absolute_top']) != 'undefined')) {
				top = values['absolute_top'];
				left = values['absolute_left'];
			}
			break;
	}

	if ((typeof(top) != 'undefined') && (typeof(left) != 'undefined')) {
		if ((typeof(values['top']) == 'undefined') &&
			(typeof(values['left']) == 'undefined')) {
			parameter.push ({name: 'top', value: top});
			parameter.push ({name: 'left', value: left});
		}
		else {
			values['top'] = top;
			values['left'] = left;
		}
	}


	success_update = false;
	if (!autosave) {
		list_actions_pending_save.push(parameter);
		//At the moment for to show correctly.
		updateDB_visual(type, idElement , values, event, top, left);
	}
	else {
		
		jQuery.ajax({
			url: get_url_ajax(),
			data: parameter,
			type: "POST",
			dataType: 'text',
			success: function (data) {
				updateDB_visual(type, idElement , values, event, top, left);
			}
		});
	}
}

function copyDB(idItem) {
	metaconsole = $("input[name='metaconsole']").val();



	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "copy"});
	parameter.push ({name: "id_visual_console", value: id_visual_console});
	parameter.push ({name: "id_element", value: idItem});

	jQuery.ajax({
		url: get_url_ajax(),
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data) {
			if (data['correct']) {
				values = data['values'];
				type = data['type'];
				id = data['id_data'];

				createItem(type, values, id);
				addItemSelectParents(id, data['text']);

				//Reload all events for the item and new item.
				eventsItems();
			}
			else {
				//TODO
			}
		}
	});
}

function deleteDB(idElement) {
	metaconsole = $("input[name='metaconsole']").val();

	$("#delete_in_progress_dialog").dialog({
		resizable: true,
		draggable: true,
		modal: true,
		height: 100,
		width: 200,
		overlay: {
			opacity: 0.5,
			background: "black"
		}
	});

	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "delete"});
	parameter.push ({name: "id_visual_console", value: id_visual_console});
	parameter.push ({name: "id_element", value: idElement});

	jQuery.ajax({
		url: get_url_ajax(),
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
			{
				if (data['correct']) {
					$("#parent > option[value=" + idElement + "]").remove();


					jQuery.each(lines, function(i, line) {
						if (typeof(line) == 'undefined') {
							return; //Continue
						}

						if ((line['id'] == idElement)
							|| (line['node_begin'] == idElement)) {

							lines.splice(i, 1);
						}
					});

					if ($("#handler_start_" + idElement).length ||
						$("#handler_end_" + idElement).length) {

						// Line item

						$("#handler_start_" + idElement).remove();
						$("#handler_end_" + idElement).remove();

						delete_user_line(idElement);
					}


					refresh_lines(lines, 'background', true);

					draw_user_lines("", 0, 0, 0 , 0, 0, true);

					$('#' + idElement).remove();
					activeToolboxButton('delete_item', false);

					$("#delete_in_progress_dialog").dialog("close");
				}
				else {
					//TODO
				}
			}
		});
}

function activeToolboxButton(id, active) {
	if ($("input." + id + "[name=button_toolbox2]").length == 0) {
		return;
	}

	if (active) {
		$("input." + id + "[name=button_toolbox2]")
			.removeAttr('disabled');
	}
	else {
		$("input." + id + "[name=button_toolbox2]")
			.attr('disabled', true);
	}
}

function click_delete_item_callback() {
	activeToolboxButton('edit_item', false);
	deleteDB(idItem);
	idItem = 0;
	selectedItem = null;
}

/**
 * Events in the visual map, click item, double click, drag and
 * drop.
 */
function eventsItems(drag) {
	if (typeof(drag) == 'undefined') {
		drag = false;
	}


	$('.item').unbind('click');
	$('.item').unbind('dragstop');
	$('.item').unbind('dragstart');

	//$(".item").resizable(); //Disable but run in ff and in the waste (aka micro$oft IE) show ungly borders

	$('.item').bind('click', function(event, ui) {

		event.stopPropagation();
		if (!is_opened_palette) {
			var divParent = $(event.target);
			while (!$(divParent).hasClass("item")) {
				divParent = $(divParent).parent();
			}
			unselectAll();
			$(divParent).css('border', '2px blue dotted');

			if ($(divParent).hasClass('box_item')) {
				creationItem = null;
				selectedItem = 'box_item';
				idItem = $(divParent).attr('id');
				activeToolboxButton('copy_item', true);
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('static_graph')) {
				creationItem = null;
				selectedItem = 'static_graph';
				idItem = $(divParent).attr('id');
				activeToolboxButton('copy_item', true);
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('group_item')) {
				creationItem = null;
				selectedItem = 'group_item';
				idItem = $(divParent).attr('id');
				activeToolboxButton('copy_item', true);
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('percentile_item')) {
				creationItem = null;
				selectedItem = 'percentile_item';
				idItem = $(divParent).attr('id');
				activeToolboxButton('copy_item', true);
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('module_graph')) {
				creationItem = null;
				selectedItem = 'module_graph';
				idItem = $(divParent).attr('id');
				activeToolboxButton('copy_item', true);
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('simple_value')) {
				creationItem = null;
				selectedItem = 'simple_value';
				idItem = $(divParent).attr('id');
				activeToolboxButton('copy_item', true);
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('label')) {
				creationItem = null;
				selectedItem = 'label';
				idItem = $(divParent).attr('id');
				activeToolboxButton('copy_item', true);
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('icon')) {
				creationItem = null;
				selectedItem = 'icon';
				idItem = $(divParent).attr('id');
				activeToolboxButton('copy_item', true);
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('handler_start')) {
				idItem = $(divParent).attr('id')
					.replace("handler_start_", "");
				creationItem = null;
				selectedItem = 'handler_start';
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('handler_end')) {
				idItem = $(divParent).attr('id')
					.replace("handler_end_", "");
				creationItem = null;
				selectedItem = 'handler_end';
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}

			//Maybe receive a click event any Enterprise item.
			if (typeof(enterprise_click_item_callback) == 'function') {
				enterprise_click_item_callback(divParent);
			}
		}
	});

	//Double click in the item
	$('.item').bind('dblclick', function(event, ui) {

		event.stopPropagation();
		if ((!is_opened_palette) && (autosave)) {
			toggle_item_palette();
		}
	});

	//Set the limit of draggable in the div with id "background" and set drag
	//by default is false.
	$(".item").draggable({containment: "#background", grid: drag});

	$('.item').bind('dragstart', function(event, ui) {

		event.stopPropagation();
		if (!is_opened_palette) {
			unselectAll();
			$(event.target).css('border', '2px blue dotted');

			selectedItem = null;
			if ($(event.target).hasClass('box_item')) {
				selectedItem = 'box_item';
			}
			if ($(event.target).hasClass('static_graph')) {
				selectedItem = 'static_graph';
			}
			if ($(event.target).hasClass('group_item')) {
				selectedItem = 'group_item';
			}
			if ($(event.target).hasClass('percentile_item')) {
				selectedItem = 'percentile_item';
			}
			if ($(event.target).hasClass('module_graph')) {
				selectedItem = 'module_graph';
			}
			if ($(event.target).hasClass('simple_value')) {
				selectedItem = 'simple_value';
			}
			if ($(event.target).hasClass('label')) {
				selectedItem = 'label';
			}
			if ($(event.target).hasClass('icon')) {
				selectedItem = 'icon';
			}
			if ($(event.target).hasClass('handler_start')) {
				selectedItem = 'handler_start';
			}
			if ($(event.target).hasClass('handler_end')) {
				selectedItem = 'handler_end';
			}

			if (selectedItem == null) {
				//Maybe receive a click event any Enterprise item.
				if (typeof(enterprise_dragstart_item_callback) == 'function') {
					selectedItem = enterprise_dragstart_item_callback(event);
				}
			}

			if (selectedItem != null) {
				creationItem = null;

				switch (selectedItem) {
					// -- line_item --
					case 'handler_start':
					// ---------------
						idItem = $(event.target).attr('id')
							.replace("handler_end_", "");
						idItem = $(event.target).attr('id')
							.replace("handler_start_", "");
						break;
					// -- line_item --
					case 'handler_end':
					// ---------------
						idItem = $(event.target).attr('id')
							.replace("handler_end_", "");
						idItem = $(event.target).attr('id')
							.replace("handler_end_", "");
						break;
					default:
						idItem = $(event.target).attr('id');
						break;
				}
				activeToolboxButton('copy_item', true);
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
		}
	});

	$('.item').bind('dragstop', function(event, ui) {

		event.stopPropagation();

		var values = {};
		values['mov_left'] = ui.position.left;
		values['mov_top'] = ui.position.top;

		updateDB(selectedItem, idItem, values, 'dragstop');
	});

	$('.item').bind('drag', function(event, ui) {
		if ($(event.target).hasClass('handler_start')) {
			selectedItem = 'handler_start';
		}
		if ($(event.target).hasClass('handler_end')) {
			selectedItem = 'handler_end';
		}

		var values = {};
		values['mov_left'] = ui.position.left;
		values['mov_top'] = ui.position.top;

		switch (selectedItem) {
			// -- line_item --
			case 'handler_start':
			// ---------------
				idElement = $(event.target).attr('id')
					.replace("handler_end_", "");
				idElement = $(event.target).attr('id')
					.replace("handler_start_", "");
				break;
			// -- line_item --
			case 'handler_end':
			// ---------------
				idElement = $(event.target).attr('id')
					.replace("handler_end_", "");
				idElement = $(event.target).attr('id')
					.replace("handler_end_", "");
				break;
		}

		switch (selectedItem) {
			// -- line_item --
			case 'handler_start':
			// ---------------
				if ((typeof(values['mov_left']) != 'undefined') &&
					(typeof(values['mov_top']) != 'undefined')) {
					var top = parseInt($("#handler_start_" + idElement)
						.css('top').replace('px', ''));
					var left = parseInt($("#handler_start_" + idElement)
						.css('left').replace('px', ''));
				}
				else if ((typeof(values['absolute_left']) != 'undefined') &&
					(typeof(values['absolute_top']) != 'undefined')) {
					var top = values['absolute_top'];
					var left = values['absolute_left'];
				}

				//Added the radious of image point of handler
				top = top + 6;
				left = left + 6;

				update_user_line('handler_start', idElement, top, left);

				draw_user_lines("", 0, 0, 0 , 0, 0, true);
				break;
			// -- line_item --
			case 'handler_end':
			// ---------------
				if ((typeof(values['mov_left']) != 'undefined') &&
					(typeof(values['mov_top']) != 'undefined')) {
					top = parseInt($("#handler_end_" + idElement)
						.css('top').replace('px', ''));
					left = parseInt($("#handler_end_" + idElement)
						.css('left').replace('px', ''));
				}
				else if ((typeof(values['absolute_left']) != 'undefined') &&
					(typeof(values['absolute_top']) != 'undefined')) {
					top = values['absolute_top'];
					left = values['absolute_left'];
				}

				//Added the radious of image point of handler
				top = top + 6;
				left = left + 6;

				update_user_line('handler_end', idElement, top, left);

				draw_user_lines("", 0, 0, 0 , 0, 0, true);
				break;
		}
	});
}

/**
 * Events for the background (click, resize and doubleclick).
 */
function eventsBackground() {
	$("#background").resizable();

	$('#background').bind('resizestart', function(event, ui) {
		if (!is_opened_palette) {
			$("#background").css('border', '2px red solid');
		}
	});

	$('#background').bind('resizestop', function(event, ui) {
		if (!is_opened_palette) {
			unselectAll();

			var values = {};
			values['width'] = $('#background').css('width').replace('px', '');
			values['height'] = $('#background').css('height').replace('px', '');

			updateDB('background', 0, values, 'resizestop');

			width = ui.size['width'];
			height = ui.size['height'];

			original_width = ui.originalSize['width'];
			original_height = ui.originalSize['height'];

			move_elements_resize(original_width, original_height, width, height);

			$('#background_grid').css('width', width);
			$('#background_grid').css('height', height);
		}
	});

	// Event click for background
	$("#background").click(function(event) {
		event.stopPropagation();
		if (!is_opened_palette) {
			unselectAll();
			$("#background").css('border', '2px blue dotted');
			activeToolboxButton('copy_item', false);
			activeToolboxButton('edit_item', true);
			activeToolboxButton('delete_item', false);
			activeToolboxButton('show_grid', true);

			idItem = 0;
			creationItem = null;
			selectedItem = 'background';
		}
	});

	$('#background').bind('dblclick', function(event, ui) {
		event.stopPropagation();
		if ((!is_opened_palette) && (autosave)) {
			toggle_item_palette();
		}
	});
}

function move_elements_resize(original_width, original_height, width, height) {
	jQuery.each($(".item"), function(key, value) {
		item = value;
		idItem = $(item).attr('id');
		classItem = $(item).attr('class').replace('item', '')
			.replace('ui-draggable', '').replace('ui-draggable-disabled', '')
			.replace(/^\s+/g,'').replace(/\s+$/g,'');

		old_height = parseInt($(item).css('top').replace('px', ''));
		old_width = parseInt($(item).css('left').replace('px', ''));

		ratio_width =  width / original_width;
		ratio_height =  height / original_height;

		new_height = old_height * ratio_height;
		new_width = old_width * ratio_width;

		var values = {};

		values['absolute_left'] = new_width;
		values['absolute_top'] = new_height;

		updateDB(classItem, idItem, values, "resizestop");
	});
}

function unselectAll() {
	$("#background").css('border', '2px black solid');
	$(".item").css('border', '');
}

function click_button_toolbox(id) {
	switch (id) {
		case 'static_graph':
			toolbuttonActive = creationItem = 'static_graph';
			toggle_item_palette();
			break;
		case 'percentile_bar':
		case 'percentile_item':
			toolbuttonActive = creationItem = 'percentile_item';
			toggle_item_palette();
			break;
		case 'module_graph':
			toolbuttonActive = creationItem = 'module_graph';
			toggle_item_palette();
			break;
		case 'simple_value':
			toolbuttonActive = creationItem = 'simple_value';
			toggle_item_palette();
			break;
		case 'label':
			toolbuttonActive = creationItem = 'label';
			toggle_item_palette();
			break;
		case 'icon':
			toolbuttonActive = creationItem = 'icon';
			toggle_item_palette();
			break;
		case 'group_item':
			toolbuttonActive = creationItem = 'group_item';
			toggle_item_palette();
			break;
		case 'box_item':
			toolbuttonActive = creationItem = 'box_item';
			toggle_item_palette();
			break;
		case 'line_item':
			toolbuttonActive = creationItem = 'line_item';
			toggle_item_palette();
			break;



		case 'copy_item':
			click_copy_item_callback();
			break;
		case 'edit_item':
			toggle_item_palette();
			break;
		case 'delete_item':
			click_delete_item_callback();
			break;
		case 'show_grid':
			showGrid();
			break;
		case 'auto_save':
			if (autosave) {
				activeToolboxButton('save_visualmap', true);
				autosave = false;

				//Disable all toolbox buttons.
				//Because when it is not autosave only trace the movements
				//the other actions need to contant with the apache server.
				//And it is necesary to re-code more parts of code to change
				//this method.
				activeToolboxButton('static_graph', false);
				activeToolboxButton('percentile_item', false);
				activeToolboxButton('module_graph', false);
				activeToolboxButton('simple_value', false);
				activeToolboxButton('label', false);
				activeToolboxButton('icon', false);
				activeToolboxButton('service', false);
				activeToolboxButton('group_item', false);

				activeToolboxButton('copy_item', false);
				activeToolboxButton('edit_item', false);
				activeToolboxButton('delete_item', false);
				activeToolboxButton('show_grid', false);
			}
			else {
				activeToolboxButton('save', false);
				autosave = true;

				//Reactive the buttons.

				if ((selectedItem != 'background') && (selectedItem != null)) {
					activeToolboxButton('delete_item', true);
				}
				if (selectedItem == 'background') {
					activeToolboxButton('show_grid', true);
				}
				if (selectedItem != null) {
					activeToolboxButton('copy_item', true);
					activeToolboxButton('edit_item', true);
				}

				activeToolboxButton('static_graph', true);
				activeToolboxButton('percentile_item', true);
				activeToolboxButton('module_graph', true);
				activeToolboxButton('simple_value', true);
				activeToolboxButton('label', true);
				activeToolboxButton('icon', true);
				activeToolboxButton('group_item', true);
			}
			break;
		case 'save_visualmap':
			$("#saving_in_progress_dialog").dialog({
				resizable: true,
				draggable: true,
				modal: true,
				height: 100,
				width: 200,
				overlay: {
					opacity: 0.5,
					background: "black"
				}
			});

			var status = true;
			activeToolboxButton('save', false);
			jQuery.each(list_actions_pending_save, function(key, action_pending_save) {
				jQuery.ajax ({
					type: 'POST',
					url: action="ajax.php",
					data: action_pending_save,
					dataType: 'json',
					success: function (data) {
						if (data == '0') {
							status = false;
						}

						$("#saving_in_progress_dialog").dialog("close");

						if (status) {
							alert($('#hack_translation_correct_save').html());
						}
						else {
							alert($('#hack_translation_incorrect_save').html());
						}
						activeToolboxButton('save', true);
					}
				});
			});


			break;
		default:
			//Maybe click in any Enterprise button in toolbox.
			if (typeof(enterprise_click_button_toolbox) == 'function') {
				enterprise_click_button_toolbox(id);
			}
			break;
	}
}

function showPreview(image) {
	metaconsole = $("input[name='metaconsole']").val();

	switch (toolbuttonActive) {
		case 'group_item':
		case 'static_graph':
			showPreviewStaticGraph(image);
			break;
		case 'icon':
			showPreviewIcon(image);
			break;
		case 'service':
			if (image && image.length > 0) showPreviewIcon(image);
			break;
	}
}

function showPreviewStaticGraph(staticGraph) {
	metaconsole = $("input[name='metaconsole']").val();
	var $spinner = $("<img />");
	$spinner.prop("src", "images/spinner.gif");

	if (is_metaconsole()) {
		$spinner.prop("src", "../../images/spinner.gif");
	}

	$("#preview")
		.empty()
		.css('text-align', 'right')
		.append($spinner);

	if (staticGraph != '') {
		imgBase = "images/console/icons/" + staticGraph;

		var parameter = Array();
		parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
		parameter.push ({name: "get_image_path_status", value: "1"});
		parameter.push ({name: "img_src", value: imgBase });
		parameter.push ({name: "id_visual_console",
			value: id_visual_console});

		jQuery.ajax ({
			type: 'POST',
			url: get_url_ajax(),
			data: parameter,
			dataType: 'json',
			error: function (xhr, textStatus, errorThrown) {
				$("#preview").empty();
			},
			success: function (data) {
				$("#preview").empty();

				jQuery.each(data, function(i, line) {
					$("#preview").append(line);
					$('#preview > img').css({'max-width':'70px','max-height':'70px'});
				});
			}
		});

	}
}

function showPreviewIcon(icon) {
	var metaconsole = $("input[name='metaconsole']").val();
	var $spinner = $("<img />");
	$spinner.prop("src", "images/spinner.gif");

	if (is_metaconsole()) {
		$spinner.prop("src", "../../images/spinner.gif");
	}

	$("#preview")
		.empty()
		.css('text-align', 'left')
		.append($spinner);

	if (icon != '') {
		imgBase = "images/console/icons/" + icon;

		var params = [];
		params.push("get_image_path=1");
		params.push("img_src=" + imgBase + ".png");
		params.push("page=include/ajax/skins.ajax");
		parameter.push ({name: "id_visual_console",
			value: id_visual_console});
		jQuery.ajax ({
			data: params.join ("&"),
			type: 'POST',
			url: get_url_ajax(),
			error: function (xhr, textStatus, errorThrown) {
				$("#preview").empty();
			},
			success: function (data) {
				$("#preview")
					.empty()
					.append(data);
				$('#preview > img').css({'max-width':'70px','max-height':'70px'});
			}
		});
	}
}

function click_copy_item_callback() {
	copyDB(idItem);
}

function showGrid() {
	metaconsole = $("input[name='metaconsole']").val();

	var url_hack_metaconsole = '';
	if (is_metaconsole()) {
		url_hack_metaconsole = '../../';
	}

	var display = $("#background_grid").css('display');
	if (display == 'none') {
		$("#background_grid").css('display', '');
		$("#background_img").css('opacity', '0.55');
		$("#background_img").css('filter', 'alpha(opacity=55)');
		$("#background_grid").css('background',
			'url("' + url_hack_metaconsole + 'images/console/background/white_boxed.jpg")');

		//Snap to grid all elements.
		jQuery.each($(".item"), function(key, value) {
			item = value;
			idItem = $(item).attr('id');
			classItem = $(item).attr('class').replace('item', '')
				.replace('ui-draggable', '').replace('ui-draggable-disabled', '')
				.replace(/^\s+/g,'').replace(/\s+$/g,'');

			pos_y = parseInt($(item).css('top').replace('px', ''));
			pos_x = parseInt($(item).css('left').replace('px', ''));

			pos_y = Math.floor(pos_y / SIZE_GRID) * SIZE_GRID;
			pos_x = Math.floor(pos_x / SIZE_GRID) * SIZE_GRID;

			var values = {};

			values['absolute_left'] = pos_x;
			values['absolute_top'] = pos_y;

			updateDB(classItem, idItem, values, 'show_grid');
		});

		eventsItems([SIZE_GRID, SIZE_GRID]);
	}
	else {
		$("#background_grid").css('display', 'none');
		$("#background_img").css('opacity', '1');
		$("#background_img").css('filter', 'alpha(opacity=100)');

		eventsItems();
	}
}
