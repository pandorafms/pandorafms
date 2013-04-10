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
var toolbuttonActive = null;
var autosave = true;
var list_actions_pending_save = [];
var temp_id_item = 0;

var SIZE_GRID = 16; //Const the size (for width and height) of grid.

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
	$(".label_color").attachColorPicker();
	
	eventsBackground();
	eventsItems();
	
	//Fixed to wait the load of images.
	$(window).load(function() {
			draw_lines(lines, 'background', true);
		}
	);
}

function cancel_button_palette_callback() {
	if (is_opened_palette) {
		toggle_item_palette();
	}
}

function update_button_palette_callback() {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
	var values = {};
	
	values = readFields();
	
	// TODO VALIDATE DATA
	switch (selectedItem) {
		case 'background':
			if(values['width'] == 0 && values['height'] == 0) {
				values['width'] = $("#hidden-background_original_width").val();
				values['height'] = $("#hidden-background_original_height").val();
			}
			$("#background").css('width', values['width']);
			$("#background").css('height', values['height']);
			
			//$("#background").css('background', 'url(images/console/background/' + values['background'] + ')');
			
			var params = [];
			params.push("get_image_path=1");
			params.push("img_src=images/console/background/" + values['background']);
			params.push("page=include/ajax/skins.ajax");
			params.push("only_src=1");
			jQuery.ajax ({
				data: params.join ("&"),
				type: 'POST',
				url: url_ajax,
				async: false,
				timeout: 10000,
				success: function (data) {
					$("#background_img").attr('src', data);
				}
			});
			
			idElement = 0;
			break;
		case 'static_graph':
			$("#text_" + idItem).html(values['label']);
			
			$("#" + idItem).css('color', values['label_color']);
			
			if ((values['width'] != 0) && (values['height'] != 0)) {
				$("#image_" + idItem).attr('width', values['width']);
				$("#image_" + idItem).attr('height', values['height']);
				$("#" + idItem).css('width', values['width'] + 'px');
				$("#" + idItem).css('height', values['height'] + 'px');
			}
			else {
				$("#image_" + idItem).removeAttr('width');
				$("#image_" + idItem).removeAttr('height');
				$("#" + idItem).css('width', '');
				$("#" + idItem).css('height', '');
			}
			break;
		case 'percentile_bar':
		case 'percentile_item':
			$("#text_" + idItem).html(values['label']);
			if (values['type_percentile'] == 'bubble') {
				$("#image_" + idItem).attr('src', getPercentileBubble(idItem, values));
			}
			else {
				$("#image_" + idItem).attr('src', getPercentileBar(idItem, values));
			}
			
			break;
		case 'module_graph':
			$("#text_" + idItem).html(values['label']);
			$("#image_" + idItem).attr('src', getModuleGraph(idItem));
			break;
		case 'simple_value':
			$("#text_" + idItem).html(values['label']);
			$("#simplevalue_" + idItem).html(getModuleValue(idItem,values['process_simple_value'], values['period']));
			break;
		case 'label':
			$("#" + idItem).css('color', values['label_color']);
			
			$("#text_" + idItem).html(values['label2']);
			break;
		case 'icon':
			var params = [];
			params.push("get_image_path=1");
			params.push("img_src=images/console/icons/" + values['image'] + ".png");
			params.push("page=include/ajax/skins.ajax");
			params.push("only_src=1");
			jQuery.ajax ({
				data: params.join ("&"),
				type: 'POST',
				url: url_ajax,
				async: false,
				timeout: 10000,
				success: function (data) {
					$("#image_" + idItem).attr('src', data);
				}
			});
			
			if ((values['width'] != 0) && (values['height'] != 0)) {
				$("#image_" + idItem).attr('width', values['width']);
				$("#image_" + idItem).attr('height', values['height']);
				$("#" + idItem).css('width', values['width'] + 'px');
				$("#" + idItem).css('height', values['height'] + 'px');
			}
			else {
				$("#image_" + idItem).removeAttr('width');
				$("#image_" + idItem).removeAttr('height');
				$("#" + idItem).css('width', '');
				$("#" + idItem).css('height', '');
			}
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
	
	
	var text = tinymce.get('text-label2').getContent();
	values['label2'] = text;
	
	values['image'] = $("select[name=image]").val();
	values['left'] = $("input[name=left]").val(); 
	values['top'] = $("input[name=top]").val(); 
	values['agent'] = $("input[name=agent]").val(); 
	values['module'] = $("select[name=module]").val();
	values['process_simple_value'] = $("select[name=process_value]").val();
	values['background'] = $("#background_image").val();
	values['period'] = $("#hidden-period").val();
	values['width'] = $("input[name=width]").val();
	values['height'] = $("input[name=height]").val();
	values['parent'] = $("select[name=parent]").val();
	values['map_linked'] = $("select[name=map_linked]").val();
	values['label_color'] = $("input[name=label_color]").val();
	values['width_percentile'] = $("input[name=width_percentile]").val();
	values['max_percentile'] = $("input[name=max_percentile]").val();
	values['width_module_graph'] = $("input[name=width_module_graph]").val();
	values['height_module_graph'] = $("input[name=height_module_graph]").val();
	values['type_percentile'] = $("input[name=type_percentile]:checked").val();
	values['value_show'] = $("input[name=value_show]:checked").val();
	values['enable_link'] = $("input[name=enable_link]").is(':checked') ? 1 : 0;
	
	if (metaconsole != 0) {
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
		case 'static_graph':
			if ((values['label'] == '') && (values['image'] == '')) {
				alert($("#message_alert_no_label_no_image").html());
				validate = false;
			}
			break;
		case 'label':
			if ((values['label2'] == '')) {
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
		insertDB(creationItem, values);
		toggle_item_palette();
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
			cleanFields();
			unselectAll();
		}
		else if (selectedItem != null) {
			//Edit a item
			
			item = selectedItem;
			toolbuttonActive = item;
			activeToolboxButton(toolbuttonActive, true);
			$("#button_create_row").css('display', 'none');
			$("#button_update_row").css('display', '');
			cleanFields();
			
			loadFieldsFromDB(item);
		}
		
		hiddenFields(item);
		
		$("#properties_panel").show("fast");
	}
}

function loadFieldsFromDB(item) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "load"});
	parameter.push ({name: "id_visual_console", value: id_visual_console});
	parameter.push ({name: "type", value: item});
	parameter.push ({name: "id_element", value: idItem});
	
	is_label = false;
	set_label = false;
	
	jQuery.ajax({
		async: false,
		url: url_ajax,
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
			{
				var moduleId = 0;
				
				jQuery.each(data, function(key, val) {
					if (key == 'background') $("#background_image").val(val);
					if (key == 'width') $("input[name=width]").val(val);
					if (key == 'height') $("input[name=height]").val(val);
					
					if (key == 'type')
						if (val == 4) { //Label
							is_label = true;
							
							//Sometimes is set previous to know the
							//type
							if (set_label) {
								tinymce.get('text-label2')
									.setContent(set_label);
								
								$("input[name=label]").val("");
							}
					}
					
					if (key == 'label') {
						if (is_label)
							tinymce.get('text-label2').setContent(val);
						else
							$("input[name=label]").val(val);
						set_label = val;
					}
					
					if (key == 'enable_link') $("input[name=enable_link]").val(val);
					
					if (key == 'image') {
						//Load image preview
						$("select[name=image]").val(val);
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
						$('#'+periodId+'_select option').each(function() {
							if($(this).val() == val) {
								$(this).attr('selected',true);
								$(this).trigger('change');
								anySelected = true;
							}
						});
						if(anySelected == false) {
							$('#'+periodId+'_select option').eq(0).attr('selected',true);
							$('#'+periodId+'_units option').eq(0).attr('selected',true);
							$('#hidden-period').val(val);
							$('#text-'+periodId+'_text').val(val);
							adjustTextUnits(periodId);
							$('#'+periodId+'_default').hide();
							$('#'+periodId+'_manual').show();
						}
					}
					if (key == 'width') $("input[name=width]").val(val);
					if (key == 'height') $("input[name=height]").val(val);
					if (key == 'parent_item') $("select[name=parent]").val(val);
					if (key == 'id_layout_linked') $("select[name=map_linked]").val(val);
					if (key == 'label_color') $("input[name=label_color]").val(val);
					if (key == 'width_percentile') $("input[name=width_percentile]").val(val);
					if (key == 'max_percentile') $("input[name=max_percentile]").val(val);
					if (key == 'width_module_graph') $("input[name=width_module_graph]").val(val);
					if (key == 'height_module_graph') $("input[name=height_module_graph]").val(val);
					
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
					
					if (metaconsole != 0) {
						if (key == 'id_agent') {
							$("#hidden-agent").val(val);
						}
						if (key == 'id_server_name') {
							$("#id_server_name").val(val);
						}
					}
				});
				
				if (typeof(enterprise_loadFieldsFromDB) == 'function') {
					enterprise_loadFieldsFromDB(data);
				}
			}
		});
}

function setAspectRatioBackground(side) {
	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_original_size_background"});
	parameter.push ({name: "background", value: $("#background_img").attr('src')});
	
	jQuery.ajax({
		async: false,
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
	$("#title_panel_span_"  + item).css('display', 'inline'); 
	
	$("#label2_row").css('display', 'none');
	$("#label2_row."  + item).css('display', '');
	
	$("#label_row").css('display', 'none');
	$("#label_row."  + item).css('display', '');
	
	$("#image_row").css('display', 'none');
	$("#image_row."  + item).css('display', '');
	
	$("#enable_link_row").css('display', 'none');
	$("#enable_link_row."  + item).css('display', '');
	
	$("#preview_row").css('display', 'none');
	$("#preview_row."  + item).css('display', '');
	
	$("#position_row").css('display', 'none');
	$("#position_row."  + item).css('display', '');
	
	$("#agent_row").css('display', 'none');
	$("#agent_row."  + item).css('display', '');
	
	$("#module_row").css('display', 'none');
	$("#module_row."  + item).css('display', '');
	
	$("#process_value_row").css('display', 'none');
	$("#process_value_row."  + item).css('display', '');
	
	$("#background_row_1").css('display', 'none');
	$("#background_row_1."  + item).css('display', '');
	
	$("#background_row_2").css('display', 'none');
	$("#background_row_2."  + item).css('display', '');
	
	$("#background_row_3").css('display', 'none');
	$("#background_row_3."  + item).css('display', '');
	
	$("#background_row_4").css('display', 'none');
	$("#background_row_4."  + item).css('display', '');
	
	$("#percentile_bar_row_1").css('display', 'none');
	$("#percentile_bar_row_1."  + item).css('display', '');
	
	$("#percentile_bar_row_2").css('display', 'none');
	$("#percentile_bar_row_2."  + item).css('display', '');
	
	$("#percentile_item_row_3").css('display', 'none');
	$("#percentile_item_row_3."  + item).css('display', '');
	
	$("#percentile_item_row_4").css('display', 'none');
	$("#percentile_item_row_4."  + item).css('display', '');
	
	$("#period_row").css('display', 'none');
	$("#period_row."  + item).css('display', '');
	
	$("#size_row").css('display', 'none');
	$("#size_row."  + item).css('display', '');
	
	$("#parent_row").css('display', 'none');
	$("#parent_row."  + item).css('display', '');
	
	$("#map_linked_row").css('display', 'none');
	$("#map_linked_row."  + item).css('display', '');
	
	$("#label_color_row").css('display', 'none');
	$("#label_color_row."  + item).css('display', '');
	
	$("#module_graph_size_row").css('display', 'none');
	$("#module_graph_size_row."  + item).css('display', '');
	
	if (typeof(enterprise_hiddenFields) == 'function') {
		enterprise_hiddenFields(item);
	}
}

function cleanFields() {
	$("input[name=label]").val('');
	tinymce.get('text-label2').setContent("");
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
	$("input[name=label_color]").val('#000000');
	$("input[name=width_module_graph]").val(300);
	$("input[name=height_module_graph]").val(180);
	$("#preview").empty();
	
	var anyText = $("#any_text").html(); //Trick for catch the translate text.
	$("#module").empty().append($('<option value="0" selected="selected">' + anyText + '</option></select>'));
}

function getModuleGraph(id_data) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
	var parameter = Array();
	
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_layout_data"});
	parameter.push ({name: "id_element", value: id_data});
	jQuery.ajax({
		async: false,
		url: url_ajax,
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
		{
			id_agente_modulo = data['id_agente_modulo'];
			label = data['label'];
			height = data['height'];
			width = data['width'];
			period = data['period'];
			if (metaconsole != 0) {
				id_metaconsole = data['id_metaconsole'];
			}
		}
	});
	
	//Cleaned array
	parameter = Array();
	
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_image_sparse"});
	parameter.push ({name: "id_agent_module", value: id_agente_modulo});
	if (metaconsole != 0) {
		parameter.push ({name: "id_metaconsole", value: id_metaconsole});
	}
	parameter.push ({name: "height", value: height});
	parameter.push ({name: "width", value: width});
	parameter.push ({name: "period", value: period});
	jQuery.ajax({
		async: false,
		url: url_ajax,
		data: parameter,
		type: "POST",
		dataType: 'text', //The ajax return the data as text.
		success: function (data)
		{
			img = data;
		}
	});
	
	return img;
}

function getModuleValue(id_data, process_simple_value, period) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
	var parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_module_value"});
	parameter.push ({name: "id_element", value: id_data});
	parameter.push ({name: "period", value: period});
	if(process_simple_value != undefined) {
		parameter.push ({name: "process_simple_value", value: process_simple_value});
	}
	jQuery.ajax({
		async: false,
		url: url_ajax,
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
		{
			module_value = data['value'];
		}
	});
	
	return module_value;
}

function getPercentileBar(id_data, values) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	var url_hack_metaconsole = '';
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
		url_hack_metaconsole = '../../';
	}
	
	max_percentile = values['max_percentile'];
	width_percentile = values['width_percentile'];
	
	var parameter = Array();
	
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_module_value"});
	parameter.push ({name: "id_element", value: id_data});
	parameter.push ({name: "value_show", value: values['value_show']});
	jQuery.ajax({
		async: false,
		url: url_ajax,
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
		{
			module_value = data['value'];
			//max_percentile = data['max_percentile'];
			//width_percentile = data['width_percentile'];
			unit_text = false;
			
			if ((data['unit_text'] != false) || typeof(data['unit_text']) != 'boolean') {
				unit_text = data['unit_text'];
			}
			
			colorRGB = data['colorRGB'];
		}
	});
	
	
	//Get the actual system font.
	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_font"});
	jQuery.ajax({
		async: false,
		url: url_ajax,
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
		{
			font = data['font'];
		}
	});
	
	
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
	
	return img;
}

function getPercentileBubble(id_data, values) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	var url_hack_metaconsole = '';
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
		url_hack_metaconsole = '../../';
	}
	
	max_percentile = values['max_percentile'];
	width_percentile = values['width_percentile'];
	
	var parameter = Array();
	
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_module_value"});
	parameter.push ({name: "id_element", value: id_data});
	parameter.push ({name: "value_show", value: values['value_show']});
	jQuery.ajax({
		async: false,
		url: url_ajax,
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
		{
			module_value = data['value'];
			//max_percentile = data['max_percentile'];
			//width_percentile = data['width_percentile'];
			unit_text = false
			if ((data['unit_text'] != false) || typeof(data['unit_text']) != 'boolean')
				unit_text = data['unit_text'];
			colorRGB = data['colorRGB'];
		}
	});
	
	
	//Get the actual system font.
	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_font"});
	jQuery.ajax({
		async: false,
		url: url_ajax,
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
		{
			font = data['font'];
		}
	});
	
	
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
	
	
	return img;
}

function getImageElement(id_data) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
	var parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_image"});
	parameter.push ({name: "id_element", value: id_data});
	
	var img = null;
	
	jQuery.ajax({
		async: false,
		url: url_ajax,
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
			{
				img = data['image'];
			}
	});
	
	return img;
}

function visual_map_get_color_line_status(id) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
	var parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_color_line"});
	parameter.push ({name: "id_element", value: id});
	
	var color = null;
	
	jQuery.ajax({
		async: false,
		url: url_ajax,
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
			{
				color = data['color_line'];
			}
	});
	
	return color;
}

function createItem(type, values, id_data) {
	var sizeStyle = '';
	var imageSize = '';
	var item = null;
	
	metaconsole = $("input[name='metaconsole']").val();
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
	switch (type) {
		case 'static_graph':
			if ((values['width'] == 0) && (values['height'] == 0)) {
				sizeStyle = '';
				imageSize = '';
			}
			else {
				sizeStyle = 'width: ' + values['width']  + 'px; height: ' + values['height'] + 'px;';
				imageSize = 'width="' + values['width']  + '" height="' + values['height'] + '"';
			}
			
			var element_status= null;
			var parameter = Array();
			parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
			parameter.push ({name: "get_element_status", value: "1"});
			parameter.push ({name: "id_element", value: id_data});
			
			if (metaconsole != 0) {
				parameter.push ({name: "metaconsole", value: 1});
			}
			else {
				parameter.push ({name: "metaconsole", value: 0});
			}
			
			jQuery.ajax ({
				type: 'POST',
				url: url_ajax,
				data: parameter,
				async: false,
				timeout: 10000,
				success: function (data) {
					element_status = data;
				}
			});
			
			var img_src= null;
			var parameter = Array();
			parameter.push ({name: "page", value: "include/ajax/skins.ajax"});
			parameter.push ({name: "get_image_path", value: "1"});
			parameter.push ({name: "img_src", value: getImageElement(id_data)});
			parameter.push ({name: "only_src", value: "1"});
			
			jQuery.ajax ({
				type: 'POST',
				url: url_ajax,
				data: parameter,
				async: false,
				timeout: 10000,
				success: function (data) {
					img_src = data;
				}
			});
			
			item = $('<div id="' + id_data
				+ '" class="item static_graph" '
				+ 'style="color: ' + values['label_color']
				+ '; text-align: center; position: absolute; display: inline-block; '
				+ sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
				'<img id="image_' + id_data + '" class="image" src="' + img_src + '" ' + imageSize + ' /><br />' +
				'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span>' + 
				'</div><input id="hidden-status_' + id_data + '" type="hidden" value="' + element_status + '" name="status_' + id_data + '">'
			);
			break;
		case 'percentile_bar':
		case 'percentile_item':
			var sizeStyle = '';
			var imageSize = '';
			
			if (values['type_percentile'] == 'percentile') {
				item = $('<div id="' + id_data + '" class="item percentile_item" style="color: ' + values['label_color'] + 
						'; text-align: center; position: absolute; display: inline-block; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
						'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' + 
						'<img class="image" id="image_' + id_data + '" src="' + getPercentileBar(id_data, values)  + '" />' +
						'</div>'
				);
			}
			else {
				item = $('<div id="' + id_data + '" class="item percentile_item" style="color: ' + values['label_color'] +
					'; text-align: center; position: absolute; display: inline-block; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
						'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' + 
						'<img class="image" id="image_' + id_data + '" src="' + getPercentileBubble(id_data, values)  + '" />' +
						'</div>'
				);
			}
			break;
		case 'module_graph':
			sizeStyle = '';
			imageSize = '';
			
			item = $('<div id="' + id_data + '" class="item module_graph" style="color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
					'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' +
					'<img class="image" id="image_' + id_data + '" src="' + getModuleGraph(id_data)  + '" style="border:1px solid #808080;" />' +
				'</div>'
			);
			break;
		case 'simple_value':
			sizeStyle = '';
			imageSize = '';
			
			item = $('<div id="' + id_data + '" class="item simple_value" style="color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
					'<span id="text_' + id_data + '" class="text"> ' + values['label'] + '</span> ' +
					'<strong>' + getModuleValue(id_data) + '</strong>' +
				'</div>'
			);
			break;
		case 'label':
			item = $('<div id="' + id_data + '" class="item label" style="color: ' + values['label_color'] + '; text-align: left; position: absolute; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
					'<span id="text_' + id_data + '" class="text">' + values['label2'] + '</span>' + 
					'</div>'
				);
			break;
		case 'icon':
			if ((values['width'] == 0) && (values['height'] == 0)) {
				sizeStyle = '';
				imageSize = '';
			}
			else {
				sizeStyle = 'width: ' + values['width']  + 'px; height: ' + values['height'] + 'px;';
				imageSize = 'width="' + values['width']  + '" height="' + values['height'] + '"';
			}
			
			var img_src= null;
			var parameter = Array();
			parameter.push ({name: "page", value: "include/ajax/skins.ajax"});
			parameter.push ({name: "get_image_path", value: "1"});
			parameter.push ({name: "img_src", value: getImageElement(id_data)});
			parameter.push ({name: "only_src", value: "1"});
			
			jQuery.ajax ({
				type: 'POST',
				url: url_ajax,
				data: parameter,
				async: false,
				timeout: 10000,
				success: function (data) {
					img_src = data;
				}
			});
			
			item = $('<div id="' + id_data + '" class="item icon" style="color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' top: ' + values['top'] + 'px; left: ' + values['left'] + 'px;">' +
				'<img id="image_' + id_data + '" class="image" src="' + img_src + '" ' + imageSize + ' /><br />' + 
				'</div>'
			);
			break;
		default:
			//Maybe create in any Enterprise item.
			if (typeof(enterprise_createItem) == 'function') {
				temp_item = enterprise_createItem(type, values, id_data);
				if (temp_item != false) {
					item = temp_item;
				}
			}
			break;
	}
	
	$("#background").append(item);
	$(".item").css('z-index', '1');
	
	if (values['parent'] != 0) {
		var line = {"id": id_data,
			"node_begin":  values['parent'],
			"node_end": id_data,
			"color": visual_map_get_color_line_status(id_data) };
		lines.push(line);
		
		refresh_lines(lines, 'background', true);
	}
}

function addItemSelectParents(id_data, text) {
	$("#parent").append($('<option value="' + id_data + '" selected="selected">' + text + '</option></select>'));
}

function insertDB(type, values) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
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
		url: url_ajax,
		async: false,
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
			{
				if (data['correct']) {
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

function updateDB_visual(type, idElement , values, event, top, left) {
	metaconsole = $("input[name='metaconsole']").val();
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
	switch (type) {
		case 'module_graph':
			$("#image_" + idElement).attr("src", getModuleGraph(idElement));
		case 'static_graph':
			if ((event != 'resizestop') && (event != 'show_grid')
				&& (event != 'dragstop')) {
				var element_status= null;
				var parameter = Array();
				parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
				parameter.push ({name: "get_element_status", value: "1"});
				parameter.push ({name: "id_element", value: idElement});
				
				if (metaconsole != 0) {
					parameter.push ({name: "metaconsole", value: 1});
				}
				else {
					parameter.push ({name: "metaconsole", value: 0});
				}
				
				jQuery.ajax ({
					type: 'POST',
					url: url_ajax,
					data: parameter,
					async: false,
					timeout: 10000,
					success: function (data) {
						$('#hidden-status_' + idElement).val(data);
					}
				});
				
				switch ($('#hidden-status_' + idElement).val()) {
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
				
				var params = [];
				params.push("get_image_path=1");
				params.push("img_src=images/console/icons/" + values['image'] + suffix);
				params.push("page=include/ajax/skins.ajax");
				params.push("only_src=1");
				jQuery.ajax ({
					data: params.join ("&"),
					type: 'POST',
					url: url_ajax,
					async: false,
					timeout: 10000,
					success: function (data) {
						$("#image_" + idElement).attr('src', data);
					}
				});
			}
		case 'percentile_item':
		case 'simple_value':
		case 'label':
		case 'icon':
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
			$("#" + idElement).css('color', values['label_color']);
			found = false;
			jQuery.each(lines, function(i, line) {
				if (lines[i]['node_begin'] == idElement) {
					found = true;
					if (values['parent'] == 0) {
						lines.splice(i);
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
			
			if (!found) {
				var line = {"id": idElement,
					"node_begin":  values['parent'],
					"node_end": idElement,
					"color": visual_map_get_color_line_status(idElement) };
				lines.push(line);
			}
			
			refresh_lines(lines, 'background', true);
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
	}
}

function updateDB(type, idElement , values, event) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
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
				action = "move";
				break;
		}
	}
	
	parameter = Array();
	parameter.push({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push({name: "action", value: action});
	parameter.push({name: "id_visual_console", value: id_visual_console});
	parameter.push({name: "type", value: type});
	parameter.push({name: "id_element", value: idElement});
	
	jQuery.each(values, function(key, val) {
		parameter.push({name: key, value: val});
	});
	
	if ((typeof(values['mov_left']) != 'undefined') &&
		(typeof(values['mov_top']) != 'undefined')) {
		top = parseInt($("#" + idElement).css('top').replace('px', ''));
		left = parseInt($("#" + idElement).css('left').replace('px', ''));
	}
	else if ((typeof(values['absolute_left']) != 'undefined') &&
		(typeof(values['absolute_top']) != 'undefined')) {
		top = values['absolute_top'];
		left = values['absolute_left'];
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
			url: url_ajax,
			data: parameter,
			type: "POST",
			dataType: 'text',
			success: function (data)
				{
					updateDB_visual(type, idElement , values, event, top, left);
				}
			});
	}
}

function deleteDB(idElement) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "delete"});
	parameter.push ({name: "id_visual_console", value: id_visual_console});
	parameter.push ({name: "id_element", value: idElement});
	
	jQuery.ajax({
		url: url_ajax,
		async: false,
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
			{
				if (data['correct']) {
					$("#parent > option[value=" + idElement + "]").remove();
					
					
					
					jQuery.each(lines, function(i, line) {
						if ((line['id'] == idElement) || (line['node_begin'] == idElement)) {
							lines.splice(i);
						}
					});
					refresh_lines(lines, 'background', true);
					
					
					
					$('#' + idElement).remove();
					activeToolboxButton('delete_item', false);
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
		$("input." + id + "[name=button_toolbox2]").removeAttr('disabled');
	}
	else {
		$("input." + id + "[name=button_toolbox2]").attr('disabled', true);
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
			
			if ($(divParent).hasClass('static_graph')) {
				creationItem = null;
				selectedItem = 'static_graph';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('percentile_item')) {
				creationItem = null;
				selectedItem = 'percentile_item';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('module_graph')) {
				creationItem = null;
				selectedItem = 'module_graph';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('simple_value')) {
				creationItem = null;
				selectedItem = 'simple_value';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('label')) {
				creationItem = null;
				selectedItem = 'label';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
				activeToolboxButton('show_grid', false);
			}
			if ($(divParent).hasClass('icon')) {
				creationItem = null;
				selectedItem = 'icon';
				idItem = $(divParent).attr('id');
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
			if ($(event.target).hasClass('static_graph')) {
				selectedItem = 'static_graph';
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
			
			if (selectedItem == null) {
				//Maybe receive a click event any Enterprise item.
				if (typeof(enterprise_dragstart_item_callback) == 'function') {
					selectedItem = enterprise_dragstart_item_callback(event);
				}
			}
			
			if (selectedItem != null) {
				creationItem = null;
				idItem = $(event.target).attr('id');
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
					activeToolboxButton('edit_item', true);
				}
				
				activeToolboxButton('static_graph', true);
				activeToolboxButton('percentile_item', true);
				activeToolboxButton('module_graph', true);
				activeToolboxButton('simple_value', true);
				activeToolboxButton('label', true);
				activeToolboxButton('icon', true);
			}
			break;
		case 'save_visualmap':
			var status = true;
			activeToolboxButton('save', false);
			jQuery.each(list_actions_pending_save, function(key, action_pending_save) {
				jQuery.ajax ({
					type: 'POST',
					url: action="ajax.php",
					data: action_pending_save,
					async: false,
					dataType: 'json',
					timeout: 10000,
					success: function (data) {
						if (data == '0') {
							status = false;
						}
					}
				});
			});
			
			if (status) {
				alert($('#hack_translation_correct_save').html());
			}
			else {
				alert($('#hack_translation_incorrect_save').html());
			}
			activeToolboxButton('save', true);
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
		case 'static_graph':
			showPreviewStaticGraph(image);
			break;
		case 'icon':
			showPreviewIcon(image);
			break;
	}
}

function showPreviewStaticGraph(staticGraph) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
	$("#preview").empty();
	$("#preview").css('text-align', 'right');
	
	if (staticGraph != '') {
		imgBase = "images/console/icons/" + staticGraph;
		
		var parameter = Array();
		parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
		parameter.push ({name: "get_image_path_status", value: "1"});
		parameter.push ({name: "img_src", value: imgBase });
		
		jQuery.ajax ({
			type: 'POST',
			url: url_ajax,
			data: parameter,
			async: false,
			dataType: 'json',
			timeout: 10000,
			success: function (data) {
				jQuery.each(data, function(i, line) {
					$("#preview").append(line);
				});
			}
		});
	}
}

function showPreviewIcon(icon) {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_ajax = "ajax.php";
	if (metaconsole != 0) {
		url_ajax = "../../ajax.php";
	}
	
	$("#preview").empty();
	$("#preview").css('text-align', 'left');
	
	if (icon != '') {
		imgBase = "images/console/icons/" + icon;
		
		var params = [];
		params.push("get_image_path=1");
		params.push("img_src=" + imgBase + ".png");
		params.push("page=include/ajax/skins.ajax");
		jQuery.ajax ({
			data: params.join ("&"),
			type: 'POST',
			url: url_ajax,
			async: false,
			timeout: 10000,
			success: function (data) {
				$("#preview").append(data);
			}
		});
	}
}

function showGrid() {
	metaconsole = $("input[name='metaconsole']").val();
	
	var url_hack_metaconsole = '';
	if (metaconsole != 0) {
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
