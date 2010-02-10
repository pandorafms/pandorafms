<?php
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

// Load global vars
require_once ("include/config.php");

check_login ();

require_once ('include/functions_gis.php');

require_javascript_file('openlayers.pandora');

if (! give_acl ($config['id_user'], 0, "IW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access map builder");
	require ("general/noaccess.php");
	return;
}
//debugPrint($_POST);

$action = get_parameter('action', 'new_map');

echo '<form id="form_setup" method="post" onSubmit="fillOrderField();">';

switch ($action) {
	case 'save_new':
		$map_name = get_parameter('map_name');
		$map_initial_longitude = get_parameter('map_initial_longitude');
		$map_initial_latitude = get_parameter('map_initial_latitude');
		$map_initial_altitude = get_parameter('map_initial_altitude');
		$map_zoom_level = get_parameter('map_zoom_level');
		$map_background = ''; //TODO
		$map_default_longitude = get_parameter('map_default_longitude');
		$map_default_latitude = get_parameter('map_default_latitude');
		$map_default_altitude = get_parameter('map_default_altitude');
		$map_group_id = get_parameter('map_group_id');
		
		$map_connection_list_temp = explode(",",get_parameter('map_connection_list'));
		$layer_list = explode(",",get_parameter('layer_list'));
		
		$map_connection_default = get_parameter('map_connection_default');
		
		$map_connection_list = array();
		foreach ($map_connection_list_temp as $idMapConnection) {
			$default = false;
			if ($map_connection_default == $idMapConnection)
				$default = true;
			
			$map_connection_list[] = array('id_conection' => $idMapConnection, 'default' => $default);
		}
		
		$arrayLayers = array();
		
		foreach ($layer_list as $layerID) {
			$arrayLayers[] = JSON_decode($_POST['layer_values_' . $layerID], true);
		}
		
		saveMap($map_name, $map_initial_longitude, $map_initial_latitude,
			$map_initial_altitude, $map_zoom_level, $map_background,
			$map_default_longitude, $map_default_latitude, $map_default_altitude,
			$map_group_id, $map_connection_list, $arrayLayers);
		break;
	case 'new_map':
		print_input_hidden('action', 'save_new');
		
		echo "<h2>" . __('GIS Maps') . " &raquo; " . __('Builder') . "</h2>";
		$map_name = '';
		$map_initial_longitude = '';
		$map_initial_latitude = '';
		$map_initial_altitude = '';
		$map_zoom_level = '';
		$map_background = '';
		$map_default_longitude = '';
		$map_default_latitude = '';
		$map_default_altitude = '';
		$map_group_id = '';
		break;
}

$table->width = '90%';
$table->data = array ();

$table->data[0][0] = __('Name') . ':';
$table->data[0][1] = print_input_text ('map_name', $map_name, '', 30, 60, true);
$table->rowspan[0][2] = 9;
$table->data[0][2] = "<table class='databox' border='0' id='map_connection'>
	<tr>
		<td colspan='3'><div id='map' style='width: 300px; height: 300px; border: 1px solid black;'></div></td>
	</tr>
	<tr>
		<td colspan='3'><a href=''>" . __("Refresh map view") . "</a></td>
	</tr>
	<tr>
		<td>" . __("Add Map connection") . ":</td>
		<td>
			" . print_select_from_sql('SELECT id_tmap_connection, conection_name FROM tgis_map_connection', 'map_connection', '', '', '', '0', true) ."
		</td>
		<td>
			<a href='javascript: addConnectionMap();'>" . print_image ("images/add.png", true) . "</a>
			<input type='hidden' name='map_connection_list' value='' id='map_connection_list' />
			<input type='hidden' name='layer_list' value='' id='layer_list' />
		</td>
	</tr>
</table>";

$table->data[1][0] = __('Group') . ':';
$table->data[1][1] = print_select_from_sql('SELECT id_grupo, nombre FROM tgrupo', 'map_group_id', $map_group_id, '', '', '0', true);

$table->data[2][0] = __('Zoom level') . ':';
$table->data[2][1] = print_input_text ('map_zoom_level', $map_zoom_level, '', 2, 4, true);

$table->data[3][0] = __('Initial Longitude') . ':';
$table->data[3][1] = print_input_text ('map_initial_longitude', $map_initial_longitude, '', 4, 8, true);

$table->data[4][0] = __('Initial Latitude') . ':';
$table->data[4][1] = print_input_text ('map_initial_latitude', $map_initial_latitude, '', 4, 8, true);

$table->data[5][0] = __('Initial Altitude') . ':';
$table->data[5][1] = print_input_text ('map_initial_altitude', $map_initial_altitude, '', 4, 8, true);

$table->data[6][0] = __('Default Longitude') . ':';
$table->data[6][1] = print_input_text ('map_default_longitude', $map_default_longitude, '', 4, 8, true);

$table->data[7][0] = __('Default Latitude') . ':';
$table->data[7][1] = print_input_text ('map_default_latitude', $map_default_latitude, '', 4, 8, true);

$table->data[8][0] = __('Default Altitude') . ':';
$table->data[8][1] = print_input_text ('map_default_altitude', $map_default_altitude, '', 4, 8, true);

print_table($table);

echo "<h3>" . __('Layers') . "</h3>";

$table->width = '90%';
$table->data = array ();
$table->valign[0] = 'top';
$table->valign[1] = 'top';

$table->data[0][0] = print_button(__('New layer'), 'new_layer', false, 'newLayer();', 'class="sub new"', true);
$table->data[0][1] = "<h4>List of layers</h4>";

$table->data[1][0] = '<div id="form_layer">
		<table id="form_layer_table" class="databox" border="0" cellpadding="4" cellspacing="4" style="visibility: hidden;">
			<tr>
				<td>' . __('Layer name') . ':</td>
				<td>' . print_input_text ('layer_name_form', '', '', 20, 40, true) . '</td>
				<td>' . __('Visible') . ':</td>
				<td>' . print_checkbox('layer_visible_form', 1, true, true) . '</td>
			</tr>
			<tr>
				<td>' . __('Group') . ':</td>
				<td colspan="3">' . print_select_from_sql('SELECT id_grupo, nombre FROM tgrupo', 'layer_group_form', '', '', __('None'), '0', true) . '</td>
			</tr>
			<tr>
				<td>' . __('Agent') . ':</td>
				<td colspan="3">
					' . print_input_text_extended ('id_agent', __('Select'), 'text_id_agent', '', 30, 100, false, '',
					array('style' => 'background: url(images/lightning.png) no-repeat right;'), true)
					. '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>&nbsp;' . 
					print_button(__('Add agent'), 'add_agent', true, 'addAgentLayer();', 'class="sub"', true) .'
				</td>
			</tr>
			<tr>
				<td colspan="4">
					<h4>' . __('List of Agents') . '</h4>
					<table class="databox" border="0" cellpadding="4" cellspacing="4" id="list_agents">
					</table>
				</td>
			</tr>
			<tr>
				<td align="right" colspan="4">' . 
					print_button(__('Save Layer'), 'save_layer', false, 'saveLayer();', 'class="sub"', true) . '
					' . print_input_hidden('layer_edit_id_form', '', true) . '
				</td>
			</tr>
		</table>
	</div>';
$table->data[1][1] = '<table class="databox" border="0" cellpadding="4" cellspacing="4" id="list_layers">
	</table>';

print_table($table);


echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button(_('Save map'), 'save_button', false, 'class="sub save"');
echo '</div>';

echo "</form>";


//-------------------------INI CHUNKS---------------------------------------
?>

<table style="visibility: hidden;">
	<tbody id="chunk_map_connection">
		<tr class="row_0">
			<td><?php print_input_text ('map_connection_name', $map_name, '', 20, 40, false, true); ?></td>
			<td><?php print_radio_button_extended('map_connection_default', '', '', true, false, 'changeDefaultConection(this.value)', '');?></td>
			<td><a id="delete_row" href="none"><img src="images/cross.png" alt=""></a></td>
		</tr>
	</tbody>
</table>

<table style="visibility: hidden;">
	<tbody id="chuck_agent">
		<tr>
			<td class="col1">XXXX</td>
			<td class="col2">
				<input type="hidden" id="name_agent" name="name_agent" value="" />
				<a id="delete_row" href="none"><img src="images/cross.png" alt=""></a>
			</td>
		</tr>
	</tbody>
</table>

<table style="visibility: hidden;">
		<tbody id="chuck_layer_item">
			<tr>
				<td class="col1"><a href='javascript: editLayer(none);'>XXXXXXXXXXXXXXXXXX</a></td>
				<td class="up_arrow"><a id="up_arrow" href="javascript: upLayer();"><img src="images/up.png" alt=""></a></td>
				<td class="down_arrow"><a id="down_arrow" href="javascript: downLayer();"><img src="images/down.png" alt=""></a></td>
				<td class="col3">
					<input type="hidden" name="layer_values" id="layer_values" />
					<a id="delete_row" href="none"><img src="images/cross.png" alt=""></a>
				</td>
			</tr>
		</tbody>
</table>
<?php
//-------------------------END CHUNKS---------------------------------------

require_css_file ('cluetip');
require_jquery_file ('cluetip');
require_jquery_file ('pandora.controls');
require_jquery_file ('bgiframe');
require_jquery_file ('autocomplete');
require_jquery_file ('json');
?>
<script type="text/javascript">
var connectionMaps = Array();
var agentList = Array();
var countAgentList = 0;
var countLayer = 0;
var layerList = Array();


$("#text_id_agent").autocomplete(
	"ajax.php",
	{
		minChars: 2,
		scroll:true,
		extraParams: {
			page: "operation/agentes/exportdata",
			search_agents: 1,
			id_group: function() { return $("#id_group").val(); }
		},
		formatItem: function (data, i, total) {
			if (total == 0)
				$("#text_id_agent").css ('background-color', '#cc0000');
			else
				$("#text_id_agent").css ('background-color', '');
			if (data == "")
				return false;
			
			return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
		},
		delay: 200
	}
);

$("#text_id_agent").result (
	function () {
		$("#button-add_agent").attr('disabled', false);
	}
);

function isInt(x) {
	var y=parseInt(x);
	if (isNaN(y)) return false;
	return x==y && x.toString()==y.toString();
}

function loadAgents(agent_list) {
	if (agent_list != null) {
		for (index in agent_list) {
			if (isInt(index)) {
				addAgentLayer(agent_list[index]);
			}
		}
	}
}

function setFieldsFormLayer(layer_name,layer_group, layer_visible_form, agent_list) {
	$("#text-layer_name_form").val(layer_name);
	$("#layer_group_form [value="+layer_group+"]").attr("selected",true);
	$("#text_id_agent").val('<?php echo __('Select'); ?>');
	$("#checkbox-layer_visible_form").attr("checked", layer_visible_form);
	$("#list_agents").empty(); //Clean list agents
	
	loadAgents(agent_list);
}

function deleteLayer(idRow) {
	$("#layer_item_" + idRow).remove();
	$("#hidden-layer_edit_id_form").val('');
	
	for (var index in layerList) {

		//int because in the object array there are method as string
		if (isInt(index)) {
			if (layerList[index] == idRow) {
				layerList.splice(index, 1);
			}
		}
	}

	updateArrowLayers();
}

function newLayer() {
	agentList = Array();
	countAgentList = 0;
	
	setFieldsFormLayer('', 0, true, null);
	$("#form_layer_table").css('visibility', 'visible');
	$("#hidden-layer_edit_id_form").val('');
}

function serializeForm() {
	layer = {};
	layer.layer_name = $("#text-layer_name_form").val();
	layer.layer_group = $("#layer_group_form :selected").val();
	if ($("#checkbox-layer_visible_form:checked").val() == 1)
		layer.layer_visible = 1;
	else
		layer.layer_visible = 0;
	layer.layer_agent_list = Array();
	
	for (var index2 in agentList) {
		if (isInt(index2)) {
			layer.layer_agent_list[index2] = $("#name_agent_" + index2).val();
		}
	}

	return $.toJSON(layer);
}

function editLayer(indexLayer) {
	agentList = Array();
	countAgentList = 0;

	stringValuesLayer = $("#layer_values_" + indexLayer).val();
	layer = $.evalJSON(stringValuesLayer);
	
	setFieldsFormLayer(layer.layer_name, layer.layer_group, layer.layer_visible, layer.layer_agent_list);
	$("#hidden-layer_edit_id_form").val(indexLayer);
	$("#form_layer_table").css('visibility', 'visible');
}

function saveLayer() {
	layer_id = $("#hidden-layer_edit_id_form").val();

	if (layer_id == '') {
		id = countLayer;
		tableRow = $("#chuck_layer_item").clone();
		tableRow.attr('id', "layer_item_" + id);
		$("#layer_values", tableRow).attr("name", "layer_values_" + id);
		$("#layer_values", tableRow).attr("id", "layer_values_" + id);
	}
	else {
		id = layer_id;
		tableRow = $("#layer_item_" + id);
	}
	
	$(".col1", tableRow).html("<a href='javascript: editLayer(" + id + ");'>" + $("#text-layer_name_form").val() + "</a>");
	$("#delete_row", tableRow).attr("href", "javascript: deleteLayer(" + id + ")");
	$("#up_arrow", tableRow).attr("href", "javascript: upLayer(" + id + ")");
	$("#down_arrow", tableRow).attr("href", "javascript: downLayer(" + id + ")");

	
	$("#layer_values_" + id, tableRow).val(serializeForm());

	if (layer_id == '') {
		$("#list_layers").append(tableRow);
		layerList.push(countLayer);
		
		countLayer++;
	}

	updateArrowLayers();
	$("#form_layer_table").css('visibility', 'hidden');
}

function deleteAgentLayer(idRow) {
	$("#agent_" + idRow).remove();
	
	for (var index in agentList) {

		//int because in the object array there are method as string
		if (isInt(index)) {
			if (agentList[index] == idRow) {
				agentList.splice(index, 1);
			}
		}
	}
}

function addAgentLayer(agent_name) {
	agent_name = typeof(agent_name) != 'undefined' ? agent_name : null; //default value
	
	tableRow = $("#chuck_agent").clone();

	tableRow.attr('id','agent_' + countAgentList);
	agentList.push(countAgentList);

	if (agent_name == null)
		$(".col1", tableRow).html($("#text_id_agent").val());
	else
		$(".col1", tableRow).html(agent_name);
	
	$("#delete_row", tableRow).attr("href", 'javascript: deleteAgentLayer(' + countAgentList + ')');
	$("#name_agent", tableRow).val($("#text_id_agent").val());
	$("#name_agent", tableRow).attr("name", "name_agent_" + countAgentList);
	$("#name_agent", tableRow).attr("id", "name_agent_" + countAgentList);
	
	countAgentList++;
	
	$("#list_agents").append(tableRow);
}

function deleteConnectionMap(idConnectionMap) {
	for (var index in connectionMaps) {

		//int because in the object array there are method as string
		if (isInt(index)) {
			if (connectionMaps[index] == idConnectionMap) {
				connectionMaps.splice(index, 1);
			}
		}
	}

	checked = $("#radiobtn0001", $("#map_connection_" + idConnectionMap)).attr('checked')
	$("#map_connection_" + idConnectionMap).remove();

	if (checked) {
		//Checked first, but not is index = 0 maybe.
		
		for (var index in connectionMaps) {

			//int because in the object array there are method as string
			if (isInt(index)) {
				$("#radiobtn0001", $("#map_connection_" + connectionMaps[index])).attr('checked', 'checked');
				break;
			}
		}
	}
}

function setFieldsRequestAjax(id_conexion) {
	if (confirm('<?php echo __('Do you want to set default data of conexion in fields?');?>')) {
		jQuery.ajax ({
			data: "page=operation/gis_maps/ajax&opt=get_data_conexion&id_conection="  + idConnectionMap,
			type: "GET",
			dataType: 'json',
			url: "ajax.php",
			timeout: 10000,
			success: function (data) {                 		
				if (data.correct) {
					$("input[name=map_initial_longitude]").val(data.content.initial_longitude);
					$("input[name=map_initial_latitude]").val(data.content.initial_latitude);
					$("input[name=map_initial_altitude]").val(data.content.initial_altitude);
					$("input[name=map_default_longitude]").val(data.content.default_longitude);
					$("input[name=map_default_latitude]").val(data.content.default_latitude);
					$("input[name=map_default_altitude]").val(data.content.default_altitude);
				}
			}
		});
	}
}

function changeDefaultConection(id) {
	
	setFieldsRequestAjax(id);
}

function addConnectionMap() {
	idConnectionMap = $("#map_connection :selected").val();
	connectionMapName = $("#map_connection :selected").text();

	//Test if before just added
	for (var index in connectionMaps) {
		if (isInt(index)) {
			if (connectionMaps[index] == idConnectionMap) {
				alert('<?php echo __("The connection"); ?> "' + connectionMapName + '" <?php echo __("just added previously."); ?>');
				
				return;
			}
		}
	}
	
	tableRows = $("#chunk_map_connection").clone();
	tableRows.attr('id','map_connection_' + idConnectionMap);
	$("input[name=map_connection_default]",tableRows).val(idConnectionMap);
	
	if (connectionMaps.length == 0) {
		//The first is checked
		$("#radiobtn0001", tableRows).attr('checked', 'checked');

		//Set the fields with conexion data (in ajax)
		setFieldsRequestAjax(idConnectionMap);
	}

	connectionMaps.push(idConnectionMap);
	
	$("#text-map_connection_name", tableRows).val(connectionMapName);
	$("#text-map_connection_name", tableRows).attr('name', 'map_connection_name_' + idConnectionMap);
	$("#delete_row", tableRows).attr('href', "javascript: deleteConnectionMap(" + idConnectionMap + ")");
	
	$("#map_connection").append(tableRows);
}

function fillOrderField() {
	$('#map_connection_list').val(connectionMaps.toString());
	$('#layer_list').val(layerList.toString());
}

function updateArrowLayers() {
	var count = 0;
	var lastIndex = null;
	
	for (var index in layerList) {

		//int because in the object array there are method as string
		if (isInt(index)) {
			numLayer = layerList[index];
			layerObj = $("#layer_item_" + numLayer);
			
			//First element
			if (count == 0) {
				$('.up_arrow', layerObj).html('');
			}
			else {
				$('.up_arrow', layerObj).html('<a class="up_arrow" href="javascript: upLayer(' + numLayer + ');"><?php print_image ("images/up.png"); ?></a>');
			}

			$('.down_arrow', layerObj).html('<a class="down_arrow" href="javascript: downLayer(' + numLayer + ');"><?php print_image ("images/down.png"); ?></a>');

			
			count++;
			lastIndex = index;
		}
	}

	//Last element
	if (lastIndex != null) {
		numLayer = layerList[lastIndex];
		layerObj = $("#layer_item_" + numLayer);
		
		$('.down_arrow', layerObj).html('');
	}
}


function upLayer(idLayer) {
	var toUpIndex = null
	var toDownIndex = null;
	
	for (var index in layerList) {

		//int because in the object array there are method as string
		if (isInt(index)) {
			toUpIndex = index;
			if (layerList[index] == idLayer)
				break;
			toDownIndex = index;
		}
	}
	
	if (toDownIndex != null) {
		layerToUp = "#layer_item_" + layerList[toUpIndex];
		layerToDown = "#layer_item_" + layerList[toDownIndex];
		$(layerToDown).insertAfter(layerToUp);
		
		temp = layerList[toUpIndex];
		layerList[toUpIndex] = layerList[toDownIndex];
		layerList[toDownIndex] = temp;

		updateArrowLayers();
	}
}

function downLayer(idLayer) {
	var toUpIndex = null
	var toDownIndex = null;
	var found = false

	for (var index in layerList) {

		//int because in the object array there are method as string
		if (isInt(index)) {
			if (layerList[index] == idLayer) {
				toDownIndex = index;
				found = true;
			}
			else {
				if (found) {
					toUpIndex = index;
					break;
				}
			}
		}
	}

	if (toUpIndex != null) {
		layerToUp = "#layer_item_" + layerList[toUpIndex];
		layerToDown = "#layer_item_" + layerList[toDownIndex];
		$(layerToDown).insertAfter(layerToUp);
		
		temp = layerList[toUpIndex];
		layerList[toUpIndex] = layerList[toDownIndex];
		layerList[toDownIndex] = temp;

		updateArrowLayers();
	}
}
</script>