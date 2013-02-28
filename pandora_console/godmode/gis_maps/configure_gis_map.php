<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
global $config;

check_login ();

ui_print_page_header (__('GIS Maps builder'), "images/server_web.png", false, "configure_gis_map", true);


require_once ('include/functions_gis.php');

$magicQuotesFlag = (boolean)ini_get('magic_quotes_gpc');

ui_require_javascript_file('openlayers.pandora');
//Global vars for javascript and scripts.
?>
<script type="text/javascript">
var connectionMaps = Array();
var agentList = Array();
var countAgentList = 0;
var countLayer = 0;
var layerList = Array();

function isInt(x) {
	var y=parseInt(x);
	if (isNaN(y)) return false;
	return x==y && x.toString()==y.toString();
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
				$('.up_arrow', layerObj).html('<a class="up_arrow" href="javascript: upLayer(' + numLayer + ');"><?php html_print_image ("images/up.png"); ?></a>');
			}
			
			$('.down_arrow', layerObj).html('<a class="down_arrow" href="javascript: downLayer(' + numLayer + ');"><?php html_print_image ("images/down.png"); ?></a>');
			
			
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
</script>
<?php

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation", "Trying to access map builder");
	require ("general/noaccess.php");
	return;
}

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
		$map_levels_zoom = get_parameter('map_levels_zoom');
		
		$map_connection_list_temp = explode(",",get_parameter('map_connection_list'));
		
		
		foreach ($map_connection_list_temp as $index => $value) {
			$cleanValue = trim($value);
			if ($cleanValue == '') {
				unset($map_connection_list_temp[$index]);
			}
		}
		$layer_list = explode(",",get_parameter('layer_list'));
		foreach ($layer_list as $index => $value) {
			$cleanValue = trim($value);
			if ($cleanValue == '') {
				unset($layer_list[$index]);
			}
		}
		
		$map_connection_default = get_parameter('map_connection_default');
		
		$map_connection_list = array();
		foreach ($map_connection_list_temp as $idMapConnection) {
			$default = 0;
			if ($map_connection_default == $idMapConnection)
				$default = 1;
			
			$map_connection_list[] = array('id_conection' => $idMapConnection, 'default' => $default);
		}
		
		$arrayLayers = array();
		foreach ($layer_list as $layerID) {
			if ($magicQuotesFlag) {
				$layer = stripslashes($_POST['layer_values_' . $layerID]);
			}
			else {
				$layer = $_POST['layer_values_' . $layerID];
			}
			$arrayLayers[] = JSON_decode($layer, true);
		}
		
		$invalidFields = gis_validate_map_data($map_name, $map_zoom_level,
			$map_initial_longitude, $map_initial_latitude, $map_initial_altitude,
			$map_default_longitude, $map_default_latitude, $map_default_altitude,
			$map_connection_list, $map_levels_zoom);
		
		if (empty($invalidFields) && get_parameter('map_connection_list') != "") {
			gis_save_map($map_name, $map_initial_longitude, $map_initial_latitude,
				$map_initial_altitude, $map_zoom_level, $map_background,
				$map_default_longitude, $map_default_latitude, $map_default_altitude,
				$map_group_id, $map_connection_list, $arrayLayers);
			$mapCreatedOk = true;
		}
		else {
			html_print_input_hidden('action', 'save_new');
			$mapCreatedOk = false;
		}
		$layer_list = $arrayLayers;
		
		ui_print_result_message ($mapCreatedOk, __('Map successfully created'),
			__('Map could not be created'));
		break;
	case 'new_map':
		html_print_input_hidden('action', 'save_new');
		
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
		$map_connection_list = Array();
		$layer_list = Array();
		$map_levels_zoom = 0;
		break;
	case 'edit_map':
		$idMap = get_parameter('map_id');
		
		html_print_input_hidden('action', 'update_saved');
		html_print_input_hidden('map_id', $idMap);
		
		
		break;
	case 'update_saved':
		$idMap = get_parameter('map_id');
		
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
		$map_levels_zoom = get_parameter('map_levels_zoom');
		
		$map_connection_list_temp = explode(",",get_parameter('map_connection_list'));
		foreach ($map_connection_list_temp as $index => $value) {
			$cleanValue = trim($value);
			if ($cleanValue == '') {
				unset($map_connection_list_temp[$index]);
			}
		}
		$layer_list = explode(",", get_parameter('layer_list'));
		foreach ($layer_list as $index => $value) {
			$cleanValue = trim($value);
			if ($cleanValue == '') {
				unset($layer_list[$index]);
			}
		}
		
		$map_connection_default = get_parameter('map_connection_default');
		
		$map_connection_list = array();
		foreach ($map_connection_list_temp as $idMapConnection) {
			$default = 0;
			if ($map_connection_default == $idMapConnection)
				$default = 1;
			
			$map_connection_list[] = array('id_conection' => $idMapConnection, 'default' => $default);
		}
		
		$arrayLayers = array();
		foreach ($layer_list as $layerID) {
			if ($magicQuotesFlag) {
				$layer = stripslashes($_POST['layer_values_' . $layerID]);
			}
			else {
				$layer = $_POST['layer_values_' . $layerID];
			}
			$arrayLayers[] = JSON_decode($layer, true);
		}
		
		$invalidFields = gis_validate_map_data($map_name, $map_zoom_level,
			$map_initial_longitude, $map_initial_latitude, $map_initial_altitude,
			$map_default_longitude, $map_default_latitude, $map_default_altitude,
			$map_connection_list, $map_levels_zoom);
			
		if (empty($invalidFields) && get_parameter('map_connection_list') != "") {
			//TODO
			gis_update_map($idMap, $map_name, $map_initial_longitude, $map_initial_latitude,
				$map_initial_altitude, $map_zoom_level, $map_background,
				$map_default_longitude, $map_default_latitude, $map_default_altitude,
				$map_group_id, $map_connection_list, $arrayLayers);
			$mapCreatedOk = true;
		}
		else {
			
			html_print_input_hidden('action', 'update_saved');
			$mapCreatedOk = false;
		}
		
		ui_print_result_message ($mapCreatedOk, __('Map successfully update'),
			__('Map could not be update'));
		
		html_print_input_hidden('action', 'update_saved');
		html_print_input_hidden('map_id', $idMap);
		break;
}

//Load the data in edit or reload in update.
switch ($action) {
	case 'edit_map':
	case 'update_saved':
		$mapData = gis_get_map_data($idMap);
		
		$map_name = $mapData['map']['map_name'];
		$map_group_id = $mapData['map']['group_id'];
		$map_zoom_level = $mapData['map']['zoom_level'];
		$map_background = $mapData['map']['map_background'];
		$map_initial_longitude = $mapData['map']['initial_longitude'];
		$map_initial_latitude = $mapData['map']['initial_latitude'];
		$map_initial_altitude = $mapData['map']['initial_altitude'];
		$map_default_longitude = $mapData['map']['default_longitude'];
		$map_default_latitude = $mapData['map']['default_latitude'];
		$map_default_altitude = $mapData['map']['default_altitude'];
		
		$map_connection_list = $mapData['connections'];
		$map_levels_zoom = gis_get_num_zoom_levels_connection_default($map_connection_list);
		
		//$map_levels_zoom = get_parameter('map_levels_zoom');
		
		$layer_list = array();
		foreach ($mapData['layers'] as $layer) {
			$layerAgentList = array();
			foreach($layer['layer_agent_list'] as $layerAgent) {
				$layerAgentList[] = $layerAgent['nombre'];
			}
			$layer_list[] = array(
				'id' => $layer['id_tmap_layer'],
				'layer_name' => $layer['layer_name'],
				'layer_group' => $layer['layer_group'],
				'layer_visible' => $layer['layer_visible'],
				'layer_agent_list' => $layerAgentList
				);
		}
		break;
}


$table->width = '98%';

$table->data = array ();
$table->valign[0] = 'top';

$table->data[0][0] = __('Map Name') . ui_print_help_tip (__('Descriptive name for the map'), true). ':';
$table->data[0][1] = html_print_input_text ('map_name', $map_name, '', 30, 60, true);
$table->rowspan[0][2] = 9;

$iconError = '';
if (isset($invalidFields['map_connection_list'])) {
	if ($invalidFields['map_connection_list']) {
		$iconError = html_print_image("images/dot_red.png", true);
	}
}

$listConnectionTemp = db_get_all_rows_sql("SELECT id_tmap_connection, conection_name, group_id FROM tgis_map_connection");
$listConnection = array();
foreach ($listConnectionTemp as $connectionTemp) {
	if (check_acl ($config["id_user"], $connectionTemp['group_id'], "IW")) {
		$listConnection[$connectionTemp['id_tmap_connection']] = $connectionTemp['conection_name'];
	}
}

$table->data[1][0] = __("Add Map connection") . ui_print_help_tip (__('At least one map connection must be defined, it will be possible to change between the connections in the map'), true). ": " . $iconError;
$table->data[1][1] = "<table class='databox' border='0' id='map_connection'>
	<tr>
		<td>
			" . html_print_select($listConnection, 'map_connection', '', '', '', '0', true) ."
		</td>
		<td>
			<a href='javascript: addConnectionMap();'>" . html_print_image ("images/add.png", true) . "</a>
			<input type='hidden' name='map_connection_list' value='' id='map_connection_list' />
			<input type='hidden' name='layer_list' value='' id='layer_list' />
		</td>
	</tr> " . gis_add_conection_maps_in_form($map_connection_list) . "
</table>";
$own_info = get_user_info($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$display_all_group = true;
else
	$display_all_group = false;
$table->data[2][0] = __('Group') . ui_print_help_tip (__('Group that owns the map'), true). ':';
$table->data[2][1] = html_print_select_groups(false, 'IW', $display_all_group, 'map_group_id', $map_group_id, '', '', '', true);

$table->data[3][0] = __('Default zoom') . ui_print_help_tip (__('Default zoom level when opening the map'), true). ':';
$table->data[3][1] = html_print_input_text ('map_zoom_level', $map_zoom_level, '', 2, 4, true) . html_print_input_hidden('map_levels_zoom', $map_levels_zoom, true);

$table->data[4][0] = __('Center Latitude') . ':';
$table->data[4][1] = html_print_input_text ('map_initial_latitude', $map_initial_latitude, '', 4, 8, true);

$table->data[5][0] = __('Center Longitude') . ':';
$table->data[5][1] = html_print_input_text ('map_initial_longitude', $map_initial_longitude, '', 4, 8, true);

$table->data[6][0] = __('Center Altitude') . ':';
$table->data[6][1] = html_print_input_text ('map_initial_altitude', $map_initial_altitude, '', 4, 8, true);

$table->data[7][0] = __('Default Latitude') . ':';
$table->data[7][1] = html_print_input_text ('map_default_latitude', $map_default_latitude, '', 4, 8, true);

$table->data[8][0] = __('Default Longitude') . ':';
$table->data[8][1] = html_print_input_text ('map_default_longitude', $map_default_longitude, '', 4, 8, true);

$table->data[9][0] = __('Default Altitude') . ':';
$table->data[9][1] = html_print_input_text ('map_default_altitude', $map_default_altitude, '', 4, 8, true);

echo '<div class="action-buttons" style="margin-top: 20px; width: '.$table->width.'">';
switch ($action) {
	case 'save_new':
	case 'edit_map':
	case 'update_saved':
		if (!empty($invalidFields)) {
			html_print_submit_button(_('Save map'), 'save_button', false, 'class="sub wand"');
		}
		else {
			html_print_submit_button(_('Update map'), 'update_button', false, 'class="sub upd"');
		}
		break;
	case 'new_map':
		html_print_submit_button(_('Save map'), 'save_button', false, 'class="sub wand"');
		break;
}
echo '</div>';

html_print_table($table);

echo "<h3>" . __('Layers') . ui_print_help_tip (__('Each layer can show agents from one group or the agents added to that layer or both.'), true). "</h3>";

$table->width = '98%';
$table->data = array ();
$table->valign[0] = 'top';
$table->valign[1] = 'top';

$table->data[0][0] = "<h4>" .__('List of layers') . ui_print_help_tip (__('It is possible to edit, delete and reorder the layers.'), true) . "</h4>";
$table->data[0][1] = '<div style="text-align: right;">' . html_print_button(__('New layer'), 'new_layer', false, 'newLayer();', 'class="sub add"', true) . '</div>';

$table->data[1][0] = '<table class="databox" border="0" cellpadding="4" cellspacing="4" id="list_layers">' .
	gis_add_layer_list($layer_list) . 
	'</table>';
$table->data[1][1] = '<div id="form_layer">
		<table id="form_layer_table" class="databox" border="0" cellpadding="4" cellspacing="4" style="visibility: hidden;">
			<tr>
				<td>' . __('Layer name') . ':</td>
				<td>' . html_print_input_text ('layer_name_form', '', '', 20, 40, true) . '</td>
				<td>' . __('Visible') . ':</td>
				<td>' . html_print_checkbox('layer_visible_form', 1, true, true) . '</td>
			</tr>
			<tr>
				<td>' . __('Show agents from group') . ':</td>
				<td colspan="3">' . html_print_select_groups(false, 'IW', $display_all_group, 'layer_group_form', '-1', '', __('None'), '-1', true) . '</td>
			</tr>
			<tr>
				<td colspan="4"><hr /></td>
			</tr>
			<tr>
				<td>' . __('Agent') . ':</td>
				<td colspan="3">
					' . html_print_input_text_extended ('id_agent', __('Select'), 'text_id_agent', '', 30, 100, false, '',
					array('style' => 'background: url(images/lightning.png) no-repeat right;'), true)
					. '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>&nbsp;' . 
					html_print_button(__('Add agent'), 'add_agent', true, 'addAgentLayer();', 'class="sub add"', true) .'
				</td>
			</tr>
			<tr>
				<td colspan="4">
					<h4>' . __('List of Agents to be shown in the layer') . '</h4>
					<table class="databox" border="0" cellpadding="4" cellspacing="4" id="list_agents">
					</table>
				</td>
			</tr>
			<tr>
				<td align="right" colspan="4">' . 
					html_print_button(__('Save Layer'), 'save_layer', false, 'saveLayer();', 'class="sub wand"', true) . '
					' . html_print_input_hidden('layer_edit_id_form', '', true) . '
				</td>
			</tr>
		</table>
	</div>';

html_print_table($table);


echo '<div class="action-buttons" style="width: '.$table->width.'">';
switch ($action) {
	case 'save_new':
	case 'edit_map':
	case 'update_saved':
		if (!empty($invalidFields)) {
			html_print_submit_button(_('Save map'), 'save_button', false, 'class="sub wand"');
		}
		else {
			html_print_submit_button(_('Update map'), 'update_button', false, 'class="sub upd"');
		}
		break;
	case 'new_map':
		html_print_submit_button(_('Save map'), 'save_button', false, 'class="sub wand"');
		break;
}
echo '</div>';

echo "</form>";


//-------------------------INI CHUNKS---------------------------------------
?>

<table style="visibility: hidden;">
	<tbody id="chunk_map_connection">
		<tr class="row_0">
			<td><?php html_print_input_text ('map_connection_name', $map_name, '', 20, 40, false, true); ?></td>
			<td><?php html_print_radio_button_extended('map_connection_default', '', '', true, false, 'changeDefaultConection(this.value)', '');?></td>
			<td><a id="delete_row" href="none"><?php html_print_image("images/cross.png", false, array("alt" => ""));?></a></td>
		</tr>
	</tbody>
</table>

<table style="visibility: hidden;">
	<tbody id="chuck_agent">
		<tr>
			<td class="col1">XXXX</td>
			<td class="col2">
				<input type="hidden" id="name_agent" name="name_agent" value="" />
				<a id="delete_row" href="none"><?php html_print_image("images/cross.png", false, array("alt" => ""));?></a>
			</td>
		</tr>
	</tbody>
</table>

<table style="visibility: hidden;">
		<tbody id="chuck_layer_item">
			<tr>
				<td class="col1">XXXXXXXXXXXXXXXXXX</td>
				<td class="up_arrow"><a id="up_arrow" href="javascript: upLayer();"><?php html_print_image("images/up.png", false, array("alt" => ""));?></a></td>
				<td class="down_arrow"><a id="down_arrow" href="javascript: downLayer();"><?php html_print_image("images/down.png", false, array("alt" => ""));?></a></td>
				<td class="col3">
					<a id="edit_layer" href="javascript: editLayer(none);"><?php html_print_image("images/config.png", false, array("alt" => ""));?></a>
				</td>
				<td class="col4">
					<input type="hidden" name="layer_values" id="layer_values" />
					<a id="delete_row" href="none"><?php html_print_image("images/cross.png", false, array("alt" => ""));?></a>
				</td>
			</tr>
		</tbody>
</table>
<?php
//-------------------------END CHUNKS---------------------------------------

ui_require_css_file ('cluetip');
ui_require_jquery_file ('cluetip');
ui_require_jquery_file ('pandora.controls');
ui_require_jquery_file ('bgiframe');
ui_require_jquery_file ('autocomplete');
ui_require_jquery_file ('json');
?>
<script type="text/javascript">
function refreshMapView() {
	map = null;
	$("#map").html('');
	
	id_connection_default = $("input[name=map_connection_default]:checked").val();
	
	jQuery.ajax ({
		data: "page=operation/gis_maps/ajax&opt=get_map_connection_data&id_connection=" + id_connection_default,
		type: "GET",
		dataType: 'json',
		url: "ajax.php",
		timeout: 10000,
		success: function (data) {
			if (data.correct) {
				mapConnection = data.content;
				
				arrayControls = null;
				arrayControls = Array('Navigation', 'PanZoom', 'MousePosition');
				
				
				//TODO read too from field forms user.
				inital_zoom = mapConnection['default_zoom_level'];
				num_levels_zoom = mapConnection['num_zoom_levels'];
				center_latitude = mapConnection['initial_latitude'];
				center_longitude = mapConnection['initial_longitude'];
				center_altitude = mapConnection['initial_altitude'];
				
				baseLayer = jQuery.evalJSON(mapConnection['conection_data']);
				
				var objBaseLayers = Array();
				objBaseLayers[0] = Array();
				objBaseLayers[0]['type'] = baseLayer['type'];
				objBaseLayers[0]['name'] = mapConnection['conection_name'];
				objBaseLayers[0]['url'] = baseLayer['url'];
				
				js_printMap('map', inital_zoom, center_latitude, center_longitude, objBaseLayers, arrayControls);
			}
		}
	});
	
}

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
	if (layer_visible_form == '0') {
		$("#checkbox-layer_visible_form").removeAttr("checked");
	}
	else {
		$("#checkbox-layer_visible_form").attr("checked", 'checked');
	}
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
	
	//If delete the layer in edit progress, must clean the form.
	if ($("#hidden-layer_edit_id_form").val() == idRow) {
		$("#form_layer_table").css('visibility', 'hidden');
		agentList = Array();
		countAgentList = 0;
		
		setFieldsFormLayer('', 0, true, null);
		$("#hidden-layer_edit_id_form").val('');
		$("input[name=save_layer]").val('<?php echo __("Save Layer"); ?>');
	}
}

function newLayer() {
	agentList = Array();
	countAgentList = 0;
	
	setFieldsFormLayer('', -1, true, null);
	$("#form_layer_table").css('visibility', 'visible');
	$("#hidden-layer_edit_id_form").val('');
	$("input[name=save_layer]").val('<?php echo __("Save Layer"); ?>');
}

function serializeForm() {
	layer = {};
	layer.id = 0;
	layer.layer_name = $("#text-layer_name_form").val();
	layer.layer_group = $("#layer_group_form :selected").val();
	if ($("#checkbox-layer_visible_form:checked").val() == 1)
		layer.layer_visible = 1;
	else
		layer.layer_visible = 0;
	layer.layer_agent_list = Array();
	
	for (var index2 in agentList) {
		if (isInt(index2)) {
			layer.layer_agent_list[index2] = $("#name_agent_" + agentList[index2]).val();
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
	
	$("input[name=save_layer]").val('<?php echo __("Update Layer"); ?>');
	
	$("#form_layer_table").css('visibility', 'visible');
	
	hightlightRow(indexLayer);
}

function hightlightRow(idLayer) {
	row = $("#layer_item_" + idLayer);
	
	$(".col1").css('background', '');
	$(".up_arrow").css('background', '');
	$(".down_arrow").css('background', '');
	$(".col3").css('background', '');
	$(".col4").css('background', '');
	$(".col1", row).css('background', '#E9F3D2');
	$(".up_arrow", row).css('background', '#E9F3D2');
	$(".down_arrow", row).css('background', '#E9F3D2');
	$(".col3", row).css('background', '#E9F3D2');
	$(".col4", row).css('background', '#E9F3D2');
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
	
	$(".col1", tableRow).html($("#text-layer_name_form").val());
	$("#edit_layer", tableRow).attr("href", "javascript: editLayer(" + id + ");");
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
	hightlightRow(id);
	
	editLayer(id);
	$("input[name=save_layer]").val('<?php echo __("Update Layer"); ?>');
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
	if (typeof(agent_name) == 'undefined')
		agent_name = $("#text_id_agent").val(); //default value
	
	tableRow = $("#chuck_agent").clone();
	
	tableRow.attr('id','agent_' + countAgentList);
	agentList.push(countAgentList);
	
	$(".col1", tableRow).html(agent_name);
	$("#delete_row", tableRow).attr("href", 'javascript: deleteAgentLayer(' + countAgentList + ')');
	$("#name_agent", tableRow).val(agent_name);
	$("#name_agent", tableRow).attr("name", "name_agent_" + countAgentList);
	$("#name_agent", tableRow).attr("id", "name_agent_" + countAgentList);
	
	countAgentList++;
	
	$("#list_agents").append(tableRow);
	$("#button-add_agent").attr('disabled', true);
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
	if (confirm('<?php echo __('Do you want to use the default data from the connection?');?>')) {
		jQuery.ajax ({
			data: "page=operation/gis_maps/ajax&opt=get_data_conexion&id_conection=" + idConnectionMap,
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
					$("input[name=map_zoom_level]").val(data.content.default_zoom_level);
					$("input[name=map_levels_zoom]").val(data.content.num_zoom_levels);
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
