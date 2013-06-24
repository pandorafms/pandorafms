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
var openPropertiesPanel = false;
var idItem = 0;
var selectedItem = null;
var lines = Array();
var toolbuttonActive = null;
var parents = {};

function showAdvanceOptions(close) {
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
function initJavascript() {
	$(".label_color").attachColorPicker();
	
	//Get the list of posible parents
	parents = Base64.decode($("input[name='parents_load']").val());
	parents = eval("(" + parents + ")");
	
	eventsBackground();
	eventsItems();
	eventsTextAgent();
	
	draw_lines(lines, 'background');
	
	$(".item").css('z-index', '1'); //For paint the icons over lines
}

function eventsTextAgent() {
	var idText = $("#ip_text").html();
	
	$("#text-agent").autocomplete(
			"ajax.php",
			{
				minChars: 2,
				scroll:true,
				extraParams: {
					page: "operation/agentes/exportdata",
					all: "enabled",
					search_agents: 1,
					id_group: function() { return $("#group").val(); }
				},
				formatItem: function (data, i, total) {
					if (total == 0)
						$("#text-agent").css('background-color', '#cc0000');
					else
						$("#text-agent").css('background-color', '');
					if (data == "")
						return false;
					return data[0]+'<br><span class="ac_extra_field">' + idText + ': '+data[1]+'</span>';
				},
				delay: 200
			}
		);
	
	$("#text-agent").result (
			function () {
				selectAgent = true;
				var agent_name = this.value;
				$('#module').fadeOut ('normal', function () {
					$('#module').empty ();
					var inputs = [];
					inputs.push ("filter=disabled = 0");
					inputs.push ("agent_name=" + agent_name);
					inputs.push ("get_agent_modules_json=1");
					inputs.push ("page=operation/agentes/ver_agente");
					jQuery.ajax ({
						data: inputs.join ("&"),
						type: 'GET',
						url: action="ajax.php",
						timeout: 10000,
						dataType: 'json',
						success: function (data) {
							$('#module').append ($('<option></option>').attr ('value', 0).text ("--"));
							jQuery.each (data, function (i, val) {
								s = js_html_entity_decode (val['nombre']);
								$('#module').append ($('<option></option>').attr ('value', val['id_agente_modulo']).text (s));
							});
							$('#module').fadeIn ('normal');
						}
					});
				});
			}
		);
}

function cancelAction() {
	 if (openPropertiesPanel) {
		 actionClick();
	 }
}

function updateAction() {
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
				url: action="ajax.php",
				async: false,
				timeout: 10000,
				success: function (data) {
					$("#background_img").attr('src', data);
				}
			});
			
			var idElement = 0;
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
			
			$("#text_" + idItem).html(values['label']);
			break;
		case 'icon':
			var params = [];
			params.push("get_image_path=1");
			params.push("img_src=" + getImageElement(idItem));
			params.push("page=include/ajax/skins.ajax");
			params.push("only_src=1");
			jQuery.ajax ({
				data: params.join ("&"),
				type: 'POST',
				url: action="ajax.php",
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
	}
	
	updateDB(selectedItem, idItem , values);
	
	actionClick();
}

function readFields() {
	
	var values = {};
	
	values['label'] = $("input[name=label]").val();
	
	
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
	
	
	return values;
}

function createAction() {
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
	}
	
	if (validate) {
		insertDB(creationItem, values);
		actionClick();
	}
}

function actionClick() {
	var item = null;
	
	if (openPropertiesPanel) {
		activeToolboxButton('static_graph', true);
		activeToolboxButton('module_graph', true);
		activeToolboxButton('simple_value', true);
		activeToolboxButton('label', true);
		activeToolboxButton('icon', true);
		activeToolboxButton('percentile_item', true);
		
		$(".item").draggable("enable");
		$("#background").resizable('enable');
		$("#properties_panel").hide("fast");
		
		showAdvanceOptions(false);
		
		openPropertiesPanel = false;
		
		return;
	}
	
	openPropertiesPanel = true;
	
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

function fill_parent_select(id_item) {
	//Populate the parent widget
	$("#parent option")
		.filter(function() { if ($(this).attr('value') != 0) return true; })
		.remove();
	jQuery.each(parents, function(key, value) {
		if (id_item == key) {
			return; //continue
		}
		
		$("#parent").append($('<option value="' + key + '">' +
			value + '</option>'));
	});
}

function loadFieldsFromDB(item) {
	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "load"});
	parameter.push ({name: "id_visual_console", value: id_visual_console});
	parameter.push ({name: "type", value: item});
	parameter.push ({name: "id_element", value: idItem});
	
	
	jQuery.ajax({
		async: false,
		url: "ajax.php",
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
			{
				var moduleId = 0;
				
				fill_parent_select(idItem);
				
				jQuery.each(data, function(key, val) {
					if (key == 'background') $("#background_image").val(val);
					if (key == 'width') $("input[name=width]").val(val);
					if (key == 'height') $("input[name=height]").val(val);
					
					if (key == 'label') $("input[name=label]").val(val);
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
					
				});
				
			}
		});
}

function setOriginalSizeBackground() {
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=godmode/reporting/visual_console_builder.editor&get_original_size_background=1&background=" + $("#background_img").attr('src'),
		async: false,
		dataType: "json",
		success: function(data) {
			var values = {};
			values['width'] = data[0];
			values['height'] = data[1];
			
			updateDB('background', 0, values);
		}
	});
	
	actionClick();
}

function setAspectRatioBackground(side) {
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=godmode/reporting/visual_console_builder.editor&get_original_size_background=1&background=" + $("#background_img").attr('src'),
		async: false,
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
			
			var values = {};
			values['width'] = width;
			values['height'] = height;
			
			updateDB('background', 0, values);
		}
	});
	
	actionClick();
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
	
	
	$("#label_row").css('display', 'none');
	$("#label_row."  + item).css('display', '');
	
	$("#image_row").css('display', 'none');
	$("#image_row."  + item).css('display', '');
	
	
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
	
}

function cleanFields() {
	$("input[name=label]").val('');
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
	
	fill_parent_select();
	
	var anyText = $("#any_text").html(); //Trick for catch the translate text.
	$("#module").empty().append($('<option value="0" selected="selected">' + anyText + '</option></select>'));
}

function getModuleGraph(id_data) {
	var parameter = Array();
	
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_layout_data"});
	parameter.push ({name: "id_element", value: id_data});
	jQuery.ajax({
		async: false,
		url: "ajax.php",
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
		}
	});
	
	//Cleaned array
	parameter = Array();
	
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_image_sparse"});
	parameter.push ({name: "id_agent_module", value: id_agente_modulo});
	parameter.push ({name: "height", value: height});
	parameter.push ({name: "width", value: width});
	parameter.push ({name: "period", value: period});
	jQuery.ajax({
		async: false,
		url: "ajax.php",
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
		url: "ajax.php",
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
	max_percentile = values['max_percentile'];
	width_percentile = values['width_percentile'];
	
	var parameter = Array();
	
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_module_value"});
	parameter.push ({name: "id_element", value: id_data});
	parameter.push ({name: "value_show", value: values['value_show']});
	jQuery.ajax({
		async: false,
		url: "ajax.php",
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
		url: "ajax.php",
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
	
	var img = 'include/graphs/fgraph.php?homeurl=../../&graph_type=progressbar&height=15&' + 
		'width=' + width_percentile + '&mode=1&progress=' + percentile +
		'&font=' + font + '&value_text=' + value_text + '&colorRGB=' + colorRGB;
	
	return img;
}

function getPercentileBubble(id_data, values) {
	max_percentile = values['max_percentile'];
	width_percentile = values['width_percentile'];
	
	var parameter = Array();
	
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_module_value"});
	parameter.push ({name: "id_element", value: id_data});
	parameter.push ({name: "value_show", value: values['value_show']});
	jQuery.ajax({
		async: false,
		url: "ajax.php",
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
		url: "ajax.php",
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
	
	var img = 'include/graphs/fgraph.php?homeurl=../../&graph_type=progressbubble&height=' + width_percentile + '&' + 
		'width=' + width_percentile + '&mode=1&progress=' + percentile +
		'&font=' + font + '&value_text=' + value_text + '&colorRGB=' + colorRGB;
	
	
	return img;
}

function getImageElement(id_data) {
	var parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_image"});
	parameter.push ({name: "id_element", value: id_data});
	
	var img = null;
	
	jQuery.ajax({
		async: false,
		url: "ajax.php",
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
	var parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_color_line"});
	parameter.push ({name: "id_element", value: id});
	
	var color = null;
	
	jQuery.ajax({
		async: false,
		url: "ajax.php",
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
			
			jQuery.ajax ({
				type: 'POST',
				url: action="ajax.php",
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
				url: action="ajax.php",
				data: parameter,
				async: false,
				timeout: 10000,
				success: function (data) {
					img_src = data;
				}
			});
			
			var item = $('<div id="' + id_data
				+ '" class="item static_graph" '
				+ 'style="left: 0px; top: 0px; color: ' + values['label_color']
				+ '; text-align: center; position: absolute; '
				+ sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
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
				var item = $('<div id="' + id_data + '" class="item percentile_item" style="left: 0px; top: 0px; color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
						'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' + 
						'<img class="image" id="image_' + id_data + '" src="' + getPercentileBar(id_data, values)  + '" />' +
						'</div>'
				);
			}
			else {
				var item = $('<div id="' + id_data + '" class="item percentile_bar" style="left: 0px; top: 0px; color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
						'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' + 
						'<img class="image" id="image_' + id_data + '" src="' + getPercentileBubble(id_data, values)  + '" />' +
						'</div>'
				);
			}
			break;
		case 'module_graph':
			sizeStyle = '';
			imageSize = '';
			
			var item = $('<div id="' + id_data + '" class="item module_graph" style="left: 0px; top: 0px; color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
					'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' +
					'<img class="image" id="image_' + id_data + '" src="' + getModuleGraph(id_data)  + '" style="border:1px solid #808080;" />' +
				'</div>'
			);
			break;
		case 'simple_value':
			sizeStyle = '';
			imageSize = '';
			
			var item = $('<div id="' + id_data + '" class="item simple_value" style="left: 0px; top: 0px; color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
					'<span id="text_' + id_data + '" class="text"> ' + values['label'] + '</span> ' +
					'<strong>' + getModuleValue(id_data) + '</strong>' +
				'</div>'
			);
			break;
		case 'label':
			var item = $('<div id="' + id_data + '" class="item label" style="left: 0px; top: 0px; color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
					'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span>' + 
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
				url: action="ajax.php",
				data: parameter,
				async: false,
				timeout: 10000,
				success: function (data) {
					img_src = data;
				}
			});
			
			var item = $('<div id="' + id_data + '" class="item icon" style="left: 0px; top: 0px; color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
				'<img id="image_' + id_data + '" class="image" src="' + img_src + '" ' + imageSize + ' /><br />' + 
				'</div>'
			);
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
		
		refresh_lines(lines, 'background');
	}
}

function addItemSelectParents(id_data, text) {
	parents[id_data] = text;
	//$("#parent").append($('<option value="' + id_data + '" selected="selected">' + text + '</option></select>'));
}

function insertDB(type, values) {
	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "insert"});
	parameter.push ({name: "id_visual_console", value: id_visual_console});
	parameter.push ({name: "type", value: type});
	jQuery.each(values, function(key, val) {
		parameter.push ({name: key, value: val});
	});
	
	jQuery.ajax({
		url: "ajax.php",
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
					eventsItems();
				}
				else {
					//TODO
				}
			}
		});
}

function updateDB(type, idElement , values, event) {
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
		var top = parseInt($("#" + idElement).css('margin-top').replace('px', ''));
		var left = parseInt($("#" + idElement).css('margin-left').replace('px', ''));
		
		top = top + parseInt(values['mov_top']);
		left = left + parseInt(values['mov_left']);
	}
	else if ((typeof(values['absolute_left']) != 'undefined') &&
		(typeof(values['absolute_top']) != 'undefined')) {
		var top = values['absolute_top'];
		var left = values['absolute_left'];
	}
	
	if ((typeof(top) != 'undefined') && (typeof(left) != 'undefined')) {
		parameter.push ({name: 'top', value: top});
		parameter.push ({name: 'left', value: left});
	}
	
	jQuery.ajax({
		url: "ajax.php",
		data: parameter,
		type: "POST",
		dataType: 'text',
		success: function (data)
			{
				switch (type) {
					case 'static_graph':
						if ((event != 'dragstop') && (event != 'resizestop')) {
							var element_status= null;
							var parameter = Array();
							parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
							parameter.push ({name: "get_element_status", value: "1"});
							parameter.push ({name: "id_element", value: idElement});
							
							jQuery.ajax ({
								type: 'POST',
								url: action="ajax.php",
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
									//Unknown
								default:
									suffix = ".png";
									// Default is Grey (Other)
							}
							
							var params = [];
							params.push("get_image_path=1");
							params.push("img_src=images/console/icons/" + values['image'] + suffix);
							params.push("page=include/ajax/skins.ajax");
							params.push("only_src=1");
							jQuery.ajax ({
								data: params.join ("&"),
								type: 'POST',
								url: action="ajax.php",
								async: false,
								timeout: 10000,
								success: function (data) {
									$("#image_" + idElement).attr('src', data);
								}
							});
						}
					case 'percentile_item':
					case 'percentile_bar':
					case 'simple_value':
					case 'label':
					case 'icon':
					case 'module_graph':
						
						if (type == 'module_graph')
							$("#image_" + idElement).attr("src", getModuleGraph(idElement));
						
						if ((typeof(values['mov_left']) != 'undefined') &&
								(typeof(values['mov_top']) != 'undefined')) {
							$("#" + idElement).css('top', '0px').css('margin-top', top + 'px');
							$("#" + idElement).css('left', '0px').css('margin-left', left + 'px');
						}
						else if ((typeof(values['absolute_left']) != 'undefined') &&
								(typeof(values['absolute_top']) != 'undefined')) {
							$("#" + idElement).css('top', '0px').css('margin-top', top + 'px');
							$("#" + idElement).css('left', '0px').css('margin-left', left + 'px');
						}
						$("#" + idElement).css('color', values['label_color']);
						
						
						//Update the lines
						end_foreach = false;
						found = false;
						jQuery.each(lines, function(i, line) {
							if (end_foreach) {
								return;
							}
							
							if (lines[i]['id'] == idElement) {
								found = true;
								if (values['parent'] == 0) {
									//Erased the line
									lines.splice(i);
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
						
						if ((!found) && (values['parent'] != 0)) {
							var line = {
								"id": idElement,
								"node_begin":  values['parent'],
								"node_end": idElement,
								"color": visual_map_get_color_line_status(idElement)
							};
							lines.push(line);
						}
						
						refresh_lines(lines, 'background');
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
		});
}

function deleteDB(idElement) {
	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "delete"});
	parameter.push ({name: "id_visual_console", value: id_visual_console});
	parameter.push ({name: "id_element", value: idElement});
	
	jQuery.ajax({
		url: "ajax.php",
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
					refresh_lines(lines, 'background');
					
					
					
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
	if (active) {
		$("input." + id + "[name=button_toolbox2]").removeAttr('disabled');
	}
	else {
		$("input." + id + "[name=button_toolbox2]").attr('disabled', 'disabled');
	}
}

function deleteItem() {
	activeToolboxButton('edit_item', false);
	deleteDB(idItem);
	idItem = 0;
	selectedItem = null;
}

/**
 * All events in the visual map, resize map, click item, double click, drag and
 * drop.
 */
function eventsItems() {
	$('.item').unbind('click');
	$('.item').unbind('dragstop');
	$('.item').unbind('dragstart');
	$(".item").draggable('destroy');
	
	//$(".item").resizable(); //Disable but run in ff and in ie show ungly borders
	
	$('.item').bind('click', function(event, ui) {
		event.stopPropagation();
		if (!openPropertiesPanel) {
			divParent = $(event.target).parent();
			unselectAll();
			$(divParent).css('border', '2px blue dotted');
			
			if ($(divParent).hasClass('static_graph')) {
				creationItem = null;
				selectedItem = 'static_graph';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('percentile_item')) {
				creationItem = null;
				selectedItem = 'percentile_item';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('module_graph')) {
				creationItem = null;
				selectedItem = 'module_graph';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('simple_value')) {
				creationItem = null;
				selectedItem = 'simple_value';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('label')) {
				creationItem = null;
				selectedItem = 'label';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('icon')) {
				creationItem = null;
				selectedItem = 'icon';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
		}
	});
	
	//Double click in the item
	$('.item').bind('dblclick', function(event, ui) {
		event.stopPropagation();
		if (!openPropertiesPanel) {
			actionClick();
		}
	});
	
	$(".item").draggable();
	
	$('.item').bind('dragstart', function(event, ui) {
		event.stopPropagation();
		if (!openPropertiesPanel) {
			divParent = $(event.target).parent();
			unselectAll();
			$(divParent).css('border', '2px blue dotted');
			
			if ($(divParent).hasClass('static_graph')) {
				creationItem = null;
				selectedItem = 'static_graph';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('percentile_bar') ||
				$(divParent).hasClass('percentile_item')) {
				creationItem = null;
				selectedItem = 'percentile_item';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('module_graph')) {
				creationItem = null;
				selectedItem = 'module_graph';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('simple_value')) {
				creationItem = null;
				selectedItem = 'simple_value';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('label')) {
				creationItem = null;
				selectedItem = 'label';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('icon')) {
				creationItem = null;
				selectedItem = 'icon';
				idItem = $(divParent).attr('id');
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

function move_elements_resize(original_width, original_height, width, height) {
	jQuery.each($(".item"), function(key, value) {
		item = value;
		idItem = $(item).attr('id');
		classItem = $(item).attr('class').replace('item', '')
			.replace('ui-draggable', '').replace(/^\s+/g,'').replace(/\s+$/g,'')
		
		old_height = parseInt($(item).css('margin-top').replace('px', ''));
		old_width = parseInt($(item).css('margin-left').replace('px', ''));
		
		ratio_width =  width / original_width;
		ratio_height =  height / original_height;
		
		new_height = old_height * ratio_height;
		new_width = old_width * ratio_width;
		
		//$(item).css('margin-top', new_height);
		//$(item).css('margin-left', new_width);
		
		var values = {};
		
		values['absolute_left'] = new_width; 
		values['absolute_top'] = new_height; 
		
		updateDB(classItem, idItem, values, "resizestop");
	});
}

function eventsBackground() {
	$("#background").resizable();
	
	$('#background').bind('resizestart', function(event, ui) {
		if (!openPropertiesPanel) {
			$("#background").css('border', '2px red solid');
		}
	});
	
	$('#background').bind('resizestop', function(event, ui) {
		if (!openPropertiesPanel) {
			unselectAll();
			
			var values = {};
			values['width'] = $('#background').css('width').replace('px', '');
			values['height'] = $('#background').css('height').replace('px', '');
			
			updateDB('background', 0, values);
			
			width = ui.size['width'];
			height = ui.size['height'];
			
			original_width = ui.originalSize['width'];
			original_height = ui.originalSize['height'];
			
			move_elements_resize(original_width, original_height, width, height);
		}
	});
	
	// Event click for background
	$("#background").click(function(event) {
		event.stopPropagation();
		if (!openPropertiesPanel) {
			unselectAll();
			$("#background").css('border', '2px blue dotted');
			activeToolboxButton('edit_item', true);
			activeToolboxButton('delete_item', false);
			
			idItem = 0;
			creationItem = null;
			selectedItem = 'background';
		}
	});
	
	$('#background').bind('dblclick', function(event, ui) {
		event.stopPropagation();
		if (!openPropertiesPanel) {
			actionClick();
		}
	});
}

function unselectAll() {
	$("#background").css('border', '2px black solid');
	$(".item").css('border', '');
}

function click2(id) {
	switch (id) {
		case 'static_graph':
			toolbuttonActive = creationItem = 'static_graph';
			actionClick();
			break;
		case 'percentile_bar':
		case 'percentile_item':
			toolbuttonActive = creationItem = 'percentile_item';
			actionClick();
			break;
		case 'module_graph':
			toolbuttonActive = creationItem = 'module_graph';
			actionClick();
			break;
		case 'simple_value':
			toolbuttonActive = creationItem = 'simple_value';
			actionClick();
			break;
		case 'label':
			toolbuttonActive = creationItem = 'label';
			actionClick();
			break;
		case 'icon':
			toolbuttonActive = creationItem = 'icon';
			actionClick();
			break;
			
		case 'edit_item':
			actionClick();
			break;
		case 'delete_item':
			deleteItem();
			break;
	}
}

function showPreview(image) {
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
	$("#preview").empty();
	$("#preview").css('text-align', 'right');
	
	if (staticGraph != '') {
		imgBase = "images/console/icons/" + staticGraph;
		
		var img_src= null;
		var parameter = Array();
		parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
		parameter.push ({name: "get_image_path_status", value: "1"});
		parameter.push ({name: "img_src", value: imgBase });
		
		jQuery.ajax ({
			type: 'POST',
			url: action="ajax.php",
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
			url: action="ajax.php",
			async: false,
			timeout: 10000,
			success: function (data) {
				$("#preview").append(data);
			}
		});
	}
}
