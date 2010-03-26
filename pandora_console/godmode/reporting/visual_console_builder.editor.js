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
//	var lines = Array ();
//	lines.push (eval ({"id":"116","node_begin":"layout-data-115","node_end":"layout-data-116","color":"#ccc"})); 
	
	//$("#background").append($('<div id="pepito"></div>'));
//	draw_line({"node_begin":"112","node_end":"110","color":"#000"}, 'background');
	//$(".map-line").css('z-index', '0');
	
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
				});
			}
		});	
}

function hiddenFields(item) {
	$(".tittle_panel_span").css('display', 'none');
	$("#tittle_panel_span_"  + item).css('display', 'inline'); 
	
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
}

function cleanFields() {
	$("input[name=label]").val('');
	$("select[name=image]").val('');
	$("input[name=left]").val(0);
	$("input[name=top]").val(0);
	$("input[name=agent]").val('');
	$("select[name=module]").val('');
	$("select[name=background_image]").val('');
	$("select[name=period]").val('');
	$("input[name=width]").val(0);
	$("input[name=height]").val(0);
	$("select[name=parent]").val('');
	$("select[name=map_linked]").val('');
	$("input[name=label_color]").val('#000000');
	$("#preview").empty();
	var anyText = $("#any_text").html();
	$("#module").empty().append($('<option value="0" selected="selected">' + anyText + '</option></select>'));
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
	if ((values['width'] == 0) && (values['height'] == 0)) {
		var sizeStyle = '';
		var imageSize = '';
	}
	else {
		var sizeStyle = 'width: ' + values['width']  + 'px; height: ' + values['height'] + 'px;';
		var imageSize = 'width="' + values['width']  + '" height="' + values['height'] + '"';
	}
	
	switch (type) {
		case 'static_graph':
			var item = $('<div id="' + id_data + '" class="item static_graph" style="color: ' + values['label_color'] + '; text-align: center; position: absolute; ' + sizeStyle + ' margin-top: ' + values['top'] + 'px; margin-left: ' + values['left'] + 'px;">' +
				'<img id="image_' + id_data + '" class="image" src="' + getImageElement(id_data) + '" ' + imageSize + ' /><br />' +
				'<span id="text_' + id_data + '" class="text">' + values['label'] + '</span>' + 
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
					case 'static_graph':
						if ((typeof(values['mov_left']) != 'undefined') &&
								(typeof(values['mov_top']) != 'undefined')) {
							$("#" + idElement).css('top', '0px').css('margin-top', top + 'px');
							$("#" + idElement).css('left', '0px').css('margin-left', left + 'px');
							refresh_lines(lines, 'background');
						}
						$("#" + idElement).css('color', values['label_color']);
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
						if (line['id'] == idElement) {
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
					break;
				case 'module_graph':
					break;
				case 'simple_value':
					break;
				case 'save_visual_console':
					break;
				case 'edit_item':
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