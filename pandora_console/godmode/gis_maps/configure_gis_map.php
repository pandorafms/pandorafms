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

if (! give_acl ($config['id_user'], 0, "IW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access map builder");
	require ("general/noaccess.php");
	return;
}
//debugPrint($_POST);

$action = get_parameter('action');

switch ($action) {
	case 'save_new':
		$conf['name'] = get_parameter('name');
		$conf['group'] = get_parameter('group');
		$conf['numLevelsZoom'] = get_parameter('num_levels_zoom');
		$conf['initial_zoom'] = get_parameter('initial_zoom');
		
		$baselayersID = get_parameter('order_baselayer');
		$baselayersID = explode(',', $baselayersID);
		
		$defaultMap = get_parameter('default_map');
		$baselayers = array();
		foreach ($baselayersID as $id) {
			if (strlen($id) == 0)
				continue;
			$temp = array();
			
			$temp['type'] = $_POST['type_' . $id];
			
			if ($defaultMap == $id)
				$temp['default'] = true;
			else
				$temp['default'] = false;
			
			switch ($temp['type']) {
				case 'osm':
					$temp['url'] = $_POST['url_' . $temp['type'] . '_' . $id];
					break;
			}
			$baselayers[] = $temp;
		}
		
		$layersID = get_parameter('order_layer');
		$layersID = explode(',', $layersID);
		$layers = array();
		foreach ($layersID as $index => $id) {
			if (strlen($id) == 0)
				continue;
			
			$temp = array();
			$temp['group'] = $_POST['layer_group_id_' . $id];
			$temp['name'] = $_POST['layer_name_' . $id];
			$temp['visible'] = $_POST['layer_visible_' . $id];
			
			$layers[$index] = $temp;
		}
		
		saveMap($conf, $baselayers, $layers);
		
		return;
		break;
}

echo "<h2>" . __('GIS Maps') . " &raquo; " . __('Builder') . "</h2>";

$map_config = array();
$map_config["name"] = '';
$map_config["group"] = '';
$map_config["type"] = '';
$map_config["url"] = '';
$map_config["num_levels_zoom"] = '';
$map_config["initial_zoom"] = '';

$table->width = '90%';
$table->data = array ();

echo '<form id="form_setup" method="post" onSubmit="fillOrderField();">';
print_input_hidden('action', 'save_new');
echo "<h3>" . __('Basic configuration') . "</h3>";

$table->data = array();
$table->data[0][0] = __('Name') . ":";
$table->data[0][1] = print_input_text ('name', $map_config["name"], '', 30, 60, true);

$table->data[1][0] = __("Group") . ":";
$table->data[1][1] = print_select_from_sql('SELECT id_grupo, nombre FROM tgrupo', 'group', $map_config["group"], '', '', '0', true);
$table->data[2][0] = __('Num levels zoom') . ":";
$table->data[2][1] = print_input_text ('num_levels_zoom', $map_config["num_levels_zoom"], '', 2, 10, true);
$table->data[3][0] = __('Initial zoom level') . ":";
$table->data[3][1] = print_input_text ('initial_zoom', $map_config["initial_zoom"], '', 2, 10, true);

print_table ($table);

$table->width = '80%';
echo "<h3>" . __('Maps') . "</h3>";
$table->data = array();
$table->id = "maps";
$types["OSM"] = __('Open Street Maps');
$table->data[0][0] = __('Type') . ":";
$table->data[0][1] = print_select ($types, 'sel_type', $map_config["type"], '', '', __('Select your type map'), true);
$table->data[0][2] = '<a href="javascript: addMap();">' . print_image ("images/add.png", true) . '</a>';

print_table ($table);

echo "<input type='hidden' id='order_baselayer' name='order_baselayer' value='' />";

echo "<h3>" . __('Layers') . "</h3>";
$table->width = '50%';
$table->data = array();
$table->id = "layers";
$table->data[0][0] = _('Group') . ':';
$table->data[0][1] = print_select_from_sql('SELECT id_grupo, nombre FROM tgrupo', 'group', $map_config["group"], '', '', '0', true);
$table->data[0][2] = '<a href="javascript: addLayer();">' . print_image ("images/add.png", true) . '</a>';
print_table($table);

echo "<input type='hidden' id='order_layer' name='order_layer' value='' />";

echo "<h3>" . __('Center') . "</h3>";
echo "<div id='center_map'><p style='font-style: italic;'>" . __('Please create a map before selecting the center.') . "<b>TODO</b></p></div>";


echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Create'), 'create_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';

//Chunks of table maps for when add maps to form
//-------------------------INI OSM---------------------------------------
$table->width = '80%';
$table->data = array();
$table->rowclass[0] = 'OSM';
$table->rowclass[1] = 'OSM';
$table->rowclass[2] = 'OSM';
$table->rowclass[3] = 'OSM';
$table->rowstyle[0] = 'visibility: hidden;';
$table->rowstyle[1] = 'visibility: hidden;';
$table->rowstyle[2] = 'visibility: hidden;';
$table->rowstyle[3] = 'visibility: hidden;';
$table->colspan[0][0] = 3;
$table->data[0][0] = '<div style="border: 1px solid #CACFD1;"></div>';
$table->data[1][0] = "<b>" . __('OSM') . "</b><input id='type' type='hidden' name='type' value='osm' />";
$table->data[1][1] = '';
$table->data[1][2] = '<a id="delete_row" href="none">' . print_image ("images/cross.png", true) . '</a>';
$table->data[2][0] = __('URL_OSM') . ":";
$table->data[2][1] = print_input_text ('url_osm', $map_config["url"], '', 50, 100, true);
$table->data[2][2] = '';
$table->data[3][0] = __('Default') . ':';
$table->data[3][1] = print_radio_button ('default_map', '2', '', true, true);
$table->data[3][2] = '';

print_table($table);
unset($table->rowclass);
unset($table->rowstyle);
unset($table->colspan);
//-------------------------INI OSM---------------------------------------

//Chunks of table layer for when add layers to form
//-------------------------INI LAYERS---------------------------------------
?>

<table class="databox" border="0" cellpadding="4" cellspacing="4" width="50%" style="visibility: hidden;">
	<tbody id="chunk_layer">
		<tr class="row_0">
			<td colspan="3" class="layer"><div style="border: 1px solid rgb(202, 207, 209);"></div></td>
		</tr>
		<tr class="row_1">
			<td>Group:</td>
			<td>
				<input id="layer_group_id" name="layer_group_id" value="" type="hidden">
				<input id="layer_group_text" name="layer_group_text" value="" disabled="disabled" type="text">
			</td>
			<td class="up_arrow"><a id="up_arrow" href="javascript: upLayer();"><img src="images/up.png" alt=""></a></td>
		</tr>
		<tr class="row_2">
			<td>Name:</td>
			<td><input name="layer_name" id="text-layer_name" size="30" maxlength="60" type="text"></td>
			<td><a id="delete_row" href="none"><img src="images/cross.png" alt=""></a></td>
		</tr>
		<tr class="row_3">
			<td>Visible:</td>
			<td><input name="layer_visible" value="1" id="checkbox-layer_visible" type="checkbox" checked="checked"></td>
			<td class="down_arrow"><a id="down_arrow" href="javascript: downLayer();"><img src="images/down.png" alt=""></a></td>
		</tr>
	</tbody>
</table>


<?php
//-------------------------END LAYERS---------------------------------------
?>
<script type="text/javascript">
	var numRowMap = 0;
	var numRowLayer = 0;
	var posLayers = new Array(); //array content idLayer
	var posMaps = new Array();

	function isInt(x) {
		var y=parseInt(x);
		if (isNaN(y)) return false;
		return x==y && x.toString()==y.toString();
	}

	function fillOrderField() {
		$('#order_layer').val(posLayers.toString());
		$('#order_baselayer').val(posMaps.toString());
	}

	function addMap() {
		tableRows = $("tr." + $("#sel_type :selected").val()).clone();

		tableRows.attr("class", "map_" + numRowMap);
		$("#delete_row", tableRows).attr("href", "javascript: deleteMap(" + numRowMap + ")");
		$("#type", tableRows).attr("name", "type_" + numRowMap);
		$("#text-url_osm", tableRows).attr("name", $("#text-url_osm", tableRows).attr("name") + "_" + numRowMap);
		$("#radiobtn0001", tableRows).attr("value", numRowMap);
		if (numRowMap != 0) $("#radiobtn0001", tableRows).removeAttr("checked");
		tableRows.css("visibility", "visible");
		
		$("#maps").append(tableRows);

		posMaps.push(numRowMap);
		
		numRowMap++;
	}

	function deleteMap(subId) {
		$("tr.map_" + subId).remove();

		for (var index in posMaps) {

			//int because in the object array there are method as string
			if (isInt(index)) {
				if (posMaps[index] == subId) {
					posMaps(index);
				}
			}
		}
	}

	function updateArrowLayers() {
		var count = 0;
		var lastIndex = null;
		
		for (var index in posLayers) {

			//int because in the object array there are method as string
			if (isInt(index)) {
				numLayer = posLayers[index];
				layerObj = $("#layer_" + numLayer);
				
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
			numLayer = posLayers[lastIndex];
			layerObj = $("#layer_" + numLayer);
			
			$('.down_arrow', layerObj).html('');
		}
	}

	function upLayer(idLayer) {
		var toUpIndex = null
		var toDownIndex = null;
		
		for (var index in posLayers) {

			//int because in the object array there are method as string
			if (isInt(index)) {
				toUpIndex = index;
				if (posLayers[index] == idLayer)
					break;
				toDownIndex = index;
			}
		}
		
		if (toDownIndex != null) {
			layerToUp = "#layer_" + posLayers[toUpIndex];
			layerToDown = "#layer_" + posLayers[toDownIndex];
			//alert(layerToDown + " : " + layerToUp);
			$(layerToDown).insertAfter(layerToUp);
			
			temp = posLayers[toUpIndex];
			posLayers[toUpIndex] = posLayers[toDownIndex];
			posLayers[toDownIndex] = temp;

			updateArrowLayers();
		}
	}

	function downLayer(idLayer) {
		var toUpIndex = null
		var toDownIndex = null;
		var found = false

		for (var index in posLayers) {

			//int because in the object array there are method as string
			if (isInt(index)) {
				if (posLayers[index] == idLayer) {
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
			layerToUp = "#layer_" + posLayers[toUpIndex];
			layerToDown = "#layer_" + posLayers[toDownIndex];
			$(layerToDown).insertAfter(layerToUp);
			
			temp = posLayers[toUpIndex];
			posLayers[toUpIndex] = posLayers[toDownIndex];
			posLayers[toDownIndex] = temp;

			updateArrowLayers();
		}
	}

	function deleteLayer(idLayer) {
		$( "#layer_" + idLayer).remove();
		
		for (var index in posLayers) {

			//int because in the object array there are method as string
			if (isInt(index)) {
				if (posLayers[index] == idLayer) {
					posLayers.splice(index);
				}
			}
		}
	}

	function addLayer() {
		tableRows = $("#chunk_layer").clone();
		
		tableRows.attr("id", "layer_" + numRowLayer);
		$("#layer_group_text", tableRows).attr('value', $("#group1 :selected").text());
		$("#layer_group_id", tableRows).attr('value', $("#group1 :selected").val());
		$("#layer_group_text", tableRows).attr('name', 'layer_group_text_' + numRowLayer);
		$("#layer_group_id", tableRows).attr('name', 'layer_group_id_' + numRowLayer);
		$("#text-layer_name", tableRows).attr('name', 'layer_name_' + numRowLayer);
		$("#checkbox-layer_visible", tableRows).attr('name', 'layer_visible_' + numRowLayer);
		$("#delete_row", tableRows).attr("href", "javascript: deleteLayer(" + numRowLayer + ");");

		posLayers.push(numRowLayer);

		$("#layers").append(tableRows);

		updateArrowLayers();

		numRowLayer++;
	}
</script>