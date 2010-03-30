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
function editorMain2() {
	$(".label_color").attachColorPicker();
	
	eventsBackground();
	eventsButtonsToolbox();
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
					search_agents: 1,
					id_group: function() { return $("#group").val(); }
				},
				formatItem: function (data, i, total) {
					if (total == 0)
						$("#text-agent").css ('background-color', '#cc0000');
					else
						$("#text-agent").css ('background-color', '');
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
	
	updateDB(selectedItem, idItem , values);
	
	switch (selectedItem) {
		case 'background':
			$("#background").css('width', values['width']);
			$("#background").css('height', values['height']);
			$("#background").css('background', 'url(images/console/background/' + values['background'] + ')');
			var idElement = 0;
			break;
		case 'static_graph':
			$("#text_" + idItem).html(values['label']);
			$("#image_" + idItem).attr('src', getImageElement(idItem));
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
			$("#text_" + idItem).html(values['label']);
			$("#image_" + idItem).attr('src', getPercentileBar(idItem));
			break;
		case 'module_graph':
			$("#text_" + idItem).html(values['label']);
			$("#image_" + idItem).attr('src', getModuleGraph(idItem));
			break;
		case 'simple_value':
			$("#text_" + idItem).html(values['label']);
			break;
	}
	
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
	values['background'] = $("#background_image").val();
	values['period'] = $("select[name=period]").val();
	values['width'] = $("input[name=width]").val();
	values['height'] = $("input[name=height]").val();
	values['parent'] = $("select[name=parent]").val();
	values['map_linked'] = $("select[name=map_linked]").val();
	values['label_color'] = $("input[name=label_color]").val();
	values['width_percentile'] = $("input[name=width_percentile]").val();
	values['max_percentile'] = $("input[name=max_percentile]").val();
	values['width_module_graph'] = $("input[name=width_module_graph]").val();
	values['height_module_graph'] = $("input[name=height_module_graph]").val();
	
	return values;
}

function createAction() {
	var values = readFields();
	
	// TODO VALIDATE DATA
	
	insertDB(creationItem, values);
	actionClick();
}

function actionClick() {
	var item = null;
	
	if (openPropertiesPanel) {
		activeToolboxButton('static_graph', true);
		activeToolboxButton('percentile_bar', true);
		activeToolboxButton('module_graph', true);
		activeToolboxButton('simple_value', true);
		activeToolboxButton('delete_item', true);
		
		$(".item").draggable("enable");
		$("#background").resizable('enable');
		$("#properties_panel").hide("fast");
		
		showAdvanceOptions(false);
		
		openPropertiesPanel = false
		
		return;
	}
	
	openPropertiesPanel = true;
	
	$(".item").draggable("disable");
	$("#background").resizable('disable');
	
	activeToolboxButton('static_graph', false);
	activeToolboxButton('percentile_bar', false);
	activeToolboxButton('module_graph', false);
	activeToolboxButton('simple_value', false);
	activeToolboxButton('delete_item', false);
	
	if (creationItem != null) {
		activeToolboxButton(creationItem, true);
		item = creationItem;
		$("#button_update_row").css('display', 'none');
		$("#button_create_row").css('display', '');
		cleanFields();
		unselectAll();
	}
	else if (selectedItem != null) {
		item = selectedItem;
		$("#button_create_row").css('display', 'none');
		$("#button_update_row").css('display', '');
		cleanFields();
		
		loadFieldsFromDB(item);
	}
	
	hiddenFields(item);
	
	$("#properties_panel").show("fast");
	
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
				
				jQuery.each(data, function(key, val) {
					if (key == 'background') $("#background_image").val(val);
					if (key == 'width') $("input[name=width]").val(val);
					if (key == 'height') $("input[name=height]").val(val);
					if (key == 'label') $("input[name=label]").val(val);
					if (key == 'image') {
						$("select[name=image]").val(val);
						showPreviewStaticGraph(val);
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
					if (key == 'period') $("select[name=period]").val(val);
					if (key == 'width') $("input[name=width]").val(val);
					if (key == 'height') $("input[name=height]").val(val);
					if (key == 'parent_item') $("select[name=parent]").val(val);
					if (key == 'id_layout_linked') $("select[name=map_linked]").val(val);
					if (key == 'label_color') $("input[name=label_color]").val(val);
					if (key == 'width_percentile') $("input[name=width_percentile]").val(val);
					if (key == 'max_percentile') $("input[name=max_percentile]").val(val);
					if (key == 'width_module_graph') $("input[name=width_module_graph]").val(val);
					if (key == 'height_module_graph') $("input[name=height_module_graph]").val(val);
				});
			}
		});	
}

function hiddenFields(item) {
	$(".title_panel_span").css('display', 'none');
	$("#title_panel_span_"  + item).css('display', 'inline'); 
	
	$("#label_row").css('display', 'none');
	$("#label_row."  + item).css('display', '');
	
	$("#image_row").css('display', 'none');
	$("#image_row."  + item).css('display', ''); 
	
	$("#position_row").css('display', 'none');
	$("#position_row."  + item).css('display', '');
	
	$("#agent_row").css('display', 'none');
	$("#agent_row."  + item).css('display', '');
	
	$("#module_row").css('display', 'none');
	$("#module_row."  + item).css('display', '');
	
	$("#background_row").css('display', 'none');
	$("#background_row."  + item).css('display', '');
	
	$("#percentile_bar_row_1").css('display', 'none');
	$("#percentile_bar_row_1."  + item).css('display', '');

	$("#percentile_bar_row_2").css('display', 'none');
	$("#percentile_bar_row_2."  + item).css('display', '');
	
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
	$("select[name=background_image]").val('');
	$("input[name=width_percentile]").val('');
	$("input[name=max_percentile]").val('');
	$("select[name=period]").val('');
	$("input[name=width]").val(0);
	$("input[name=height]").val(0);
	$("select[name=parent]").val('');
	$("select[name=map_linked]").val('');
	$("input[name=label_color]").val('#000000');
	$("input[name=width_module_graph]").val(0);
	$("input[name=height_module_graph]").val(0);
	$("#preview").empty();
	
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
	
	var img = 'include/fgraph.php?tipo=sparse&id=' + id_agente_modulo + '&label=' + label + '&height=' + height + '&pure=1&width=' + width + '&period=' + period;
	
	return img;
}

function getModuleValue(id_data) {
	var parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_module_value"});
	parameter.push ({name: "id_element", value: id_data});
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

function getPercentileBar(id_data) {
	var parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "get_module_value"});
	parameter.push ({name: "id_element", value: id_data});
	jQuery.ajax({
		async: false,
		url: "ajax.php",
		data: parameter,
		type: "POST",
		dataType: 'json',
		success: function (data)
		{
			module_value = data['value'];
			max_percentile = data['max_percentile'];
			width_percentile = data['width_percentile'];
		}
	});
	
	if ( max_percentile > 0)
		var percentile = module_value / max_percentile * 100;
	else
		var percentile = 100;
	
	var img = 'include/fgraph.php?tipo=progress&height=15&width=' + width_percentile + '&mode=1&percent=' + percentile;
	
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

function getColorLineStatus(id) {
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
	switch (type) {
		case 'static_graph':
			if ((values['width'] == 0) && (values['height'] == 0)) {
				var sizeStyle = '';
				var imageSize = '';
			}
			else {
				var sizeStyle = 'width: ' + values['width']  + 'px; height: ' + values['height'] + 'px;';
				var imageSize = 'width="' + values['width']  + '" height="' + values['height'] + '"';
			}
			var item = $('<div id="' + id_data + '" class="item static_graph" style="color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
				'<img id="image_' + id_data + '" class="image" src="' + getImageElement(id_data) + '" ' + imageSize + ' /><br />' +
				'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span>' + 
				'</div>'
			);
			break;
		case 'percentile_bar':
			var sizeStyle = '';
			var imageSize = '';
			
			var item = $('<div id="' + id_data + '" class="item percentile_bar" style="color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
					'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' + 
					'<img class="image" id="image_' + id_data + '" src="' + getPercentileBar(id_data)  + '" />' +
					'</div>'
			);
			break;
		case 'module_graph':
			var sizeStyle = '';
			var imageSize = '';
			
			var item = $('<div id="' + id_data + '" class="item module_graph" style="color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
					'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' +
					'<img class="image" id="image_' + id_data + '" src="' + getModuleGraph(id_data)  + '" />' +
				'</div>'
			);
			break;
		case 'simple_value':
			var sizeStyle = '';
			var imageSize = '';
			
			var item = $('<div id="' + id_data + '" class="item simple_value" style="color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
					'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span><br />' +
					'<strong>' + getModuleValue(id_data) + '</strong>' +
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
				"color": getColorLineStatus(id_data) };
		lines.push(line);
		
		refresh_lines(lines, 'background');
	}
}

function addItemSelectParents(id_data, text) {
	$("#parent").append($('<option value="' + id_data + '" selected="selected">' + text + '</option></select>'));
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
					createItem(type, values, data['id_data']);
					addItemSelectParents(data['id_data'], data['text']);
					eventsItems();
				}
				else {
					//TODO
				}
			}
		});
}

function updateDB(type, idElement , values) {
	parameter = Array();
	parameter.push ({name: "page", value: "include/ajax/visual_console_builder.ajax"});
	parameter.push ({name: "action", value: "update"});
	parameter.push ({name: "id_visual_console", value: id_visual_console});
	parameter.push ({name: "type", value: type});
	parameter.push ({name: "id_element", value: idElement});
	
	jQuery.each(values, function(key, val) {
		parameter.push ({name: key, value: val});
	});
	
	if ((typeof(values['mov_left']) != 'undefined') &&
		(typeof(values['mov_top']) != 'undefined')) {
		var top = parseInt($("#" + idElement).css('margin-top').replace('px', ''));
		var left = parseInt($("#" + idElement).css('margin-left').replace('px', ''));
		
		top = top + parseInt(values['mov_top']);
		left = left + parseInt(values['mov_left']);
		
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
					case 'module_graph':
					case 'static_graph':
					case 'percentile_bar':
					case 'simple_value':
						if ((typeof(values['mov_left']) != 'undefined') &&
								(typeof(values['mov_top']) != 'undefined')) {
							$("#" + idElement).css('top', '0px').css('margin-top', top + 'px');
							$("#" + idElement).css('left', '0px').css('margin-left', left + 'px');
						}
						$("#" + idElement).css('color', values['label_color']);
						jQuery.each(lines, function(i, line) {
							if (lines[i]['id'] == idElement) {
								if (values['parent'] == 0) {
									lines.splice(i);
								}
								else {
									if ((typeof(values['mov_left']) == 'undefined') &&
											(typeof(values['mov_top']) == 'undefined')) {
										lines[i]['node_begin'] = values['parent'];
									}
								}
							}
						});
						refresh_lines(lines, 'background');
						break;
					case 'background':
						$("#background").css('width', values['width'] + 'px');
						$("#background").css('height', values['height'] + 'px');
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
		$("#" + id).attr('class', 'button_toolbox');
		$(".label", $("#" + id)).css('color','#000000');
	}
	else {
		$("#" + id).attr('class', 'button_toolbox disabled');
		$(".label", $("#" + id)).css('color','#aaaaaa');
	}
}

function deleteItem() {
	activeToolboxButton('edit_item', false);
	deleteDB(idItem);
	idItem = 0;
	selectedItem = null;
}

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
			unselectAll()
			$(divParent).css('border', '2px blue dotted');
			
			if ($(divParent).hasClass('static_graph')) {
				creationItem = null;
				selectedItem = 'static_graph';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('percentile_bar')) {
				creationItem = null;
				selectedItem = 'percentile_bar';
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
		}
	});
	
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
			unselectAll()
			$(divParent).css('border', '2px blue dotted');
			
			if ($(divParent).hasClass('static_graph')) {
				creationItem = null;
				selectedItem = 'static_graph';
				idItem = $(divParent).attr('id');
				activeToolboxButton('edit_item', true);
				activeToolboxButton('delete_item', true);
			}
			if ($(divParent).hasClass('percentile_bar')) {
				creationItem = null;
				selectedItem = 'percentile_bar';
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
		}
	});
	
	$('.item').bind('dragstop', function(event, ui) {
		event.stopPropagation();
		
		var values = {};
		
		values['mov_left'] = ui.position.left; 
		values['mov_top'] = ui.position.top; 
		
		updateDB(selectedItem, idItem, values);
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

function eventsButtonsToolbox() {
	$('.button_toolbox').mouseover(function(event) {
		event.stopPropagation();

		// over label
		if ($(event.target).is('span')) {
			id = $(event.target).parent().attr('id');
		}
		else {
			id = $(event.target).attr('id');
		}

		if ($("#" + id).hasClass('disabled') == false) {
			$("#" + id).css('background', '#f5f5f5');
		}
	});
	
	$('.button_toolbox').mouseout(function(event) {
		event.stopPropagation();
		id = $(event.target).attr('id');
		if ($("#" + id).hasClass('disabled') == false) {				
			$("#" + id).css('background', '#e5e5e5');
		}
		$("#" + id).css('border', '4px outset black');
	});
	
	$('.button_toolbox').mousedown(function(event) {
		event.stopPropagation();

		// over label
		if ($(event.target).is('span')) {
			id = $(event.target).parent().attr('id');
		}
		else {
			id = $(event.target).attr('id');
		}
		
		if ($("#" + id).hasClass('disabled') == false) {
			$("#" + id).css('border', '4px inset black');
		}
	});
	
	$('.button_toolbox').mouseup(function(event) {
		event.stopPropagation();

		// over label
		if ($(event.target).is('span')) {
			id = $(event.target).parent().attr('id');
		}
		else {
			id = $(event.target).attr('id');
		}
		
		$("#" + id).css('border', '4px outset black');
	});
	
	$('.button_toolbox').click(function(event) {
		event.stopPropagation();

		// over label
		if ($(event.target).is('span')) {
			id = $(event.target).parent().attr('id');
		}
		else {
			id = $(event.target).attr('id');
		}

		if ($("#" + id).hasClass('disabled') == false) {
			switch (id) {
				case 'edit_item':
					actionClick();
					break;
				case 'static_graph':
					creationItem = 'static_graph';
					actionClick();
					break;
				case 'percentile_bar':
					creationItem = 'percentile_bar';
					actionClick();
					break;
				case 'module_graph':
					creationItem = 'module_graph';
					actionClick();
					break;
				case 'simple_value':
					creationItem = 'simple_value';
					actionClick();
					break;
				case 'delete_item':
					deleteItem();
					break;
			}
		}
	});
}

function showPreviewStaticGraph(staticGraph) {
	$("#preview").empty();
	
	if (staticGraph != '') {
		imgBase = "images/console/icons/" + staticGraph;
		$("#preview").append("<img src='" + imgBase + "_bad.png' />");
		$("#preview").append("<img src='" + imgBase + "_ok.png' />");
		$("#preview").append("<img src='" + imgBase + "_warning.png' />");
		$("#preview").append("<img src='" + imgBase + ".png' />");
	}
}