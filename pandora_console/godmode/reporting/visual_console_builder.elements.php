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

// Login check
global $config;

check_login ();

if (! give_acl ($config['id_user'], 0, "IW")) {
	pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once('godmode/reporting/visual_console_builder.constans.php');
require_once ('include/functions_visual_map.php');

//Arrays for select box.
$backgrounds_list = list_files('images/console/background/', "jpg", 1, 0);
$backgrounds_list = array_merge($backgrounds_list, list_files ('images/console/background/', "png", 1, 0));

$images_list = array ();
$all_images = list_files ('images/console/icons/', "png", 1, 0);
foreach ($all_images as $image_file) {
	if (strpos ($image_file, "_bad"))
		continue;
	if (strpos ($image_file, "_ok"))
		continue;
	if (strpos ($image_file, "_warning"))
		continue;	
	$image_file = substr ($image_file, 0, strlen ($image_file) - 4);
	$images_list[$image_file] = $image_file;
}

$layoutDataTypes = get_layout_data_types();

/* Layout_data editor form */
$intervals = array ();
$intervals[3600] = "1 ".__('hour');
$intervals[7200] = "2 ".__('hours');
$intervals[10800] = "3 ".__('hours');
$intervals[21600] = "6 ".__('hours');
$intervals[43200] = "12 ".__('hours');
$intervals[86400] = __('Last day');
$intervals[172800] = "2 ". __('days');
$intervals[604800] = __('Last week');
$intervals[1209600] = "14 ".__('days');
$intervals[2592000] = __('Last month');
$intervals[5184000] = "2 ".__('months');
$intervals[15552000] = "6 ".__('months');

$table->width = '100%';
$table->head = array ();
$table->head['icon'] = '';
$table->head[0] = __('Label') . ' / ' . __('Agent');
$table->head[1] = __('Image') . ' / ' . __('Module');
$table->head[2] = __('Width x Height<br>Max value');
$table->head[3] = __('Period') . ' / ' . __('Position');
$table->head[4] = __('Parent') . ' / ' . __('Map linked');
$table->head[5] = __('Action');

$table->align[0] = "center";
$table->align[1] = "center";
$table->align[2] = "center";
$table->align[3] = "center";
$table->align[4] = "center";
$table->align[5] = "center";

$table->data = array();

//Background
$table->data[0]['icon'] = '';
$table->data[0][0] = __('Background');
$table->data[0][1] = print_select($backgrounds_list, 'background', $visualConsole['background'], '', 'None', '', true, false, true, '', false, 'width: 100px;');
$table->data[0][2] = print_input_text('width', $visualConsole['width'], '', 3, 5, true) .
	'x' .
	print_input_text('height', $visualConsole['height'], '', 3, 5, true);
$table->data[0][3] = $table->data[0][4] = $table->data[0][5] = '';

$i = 1;
$layoutDatas = get_db_all_rows_field_filter ('tlayout_data', 'id_layout', $idVisualConsole);
if ($layoutDatas === false)
	$layoutDatas = array();

$alternativeStyle = true;
foreach ($layoutDatas as $layoutData) {
	$idLayoutData = $layoutData['id'];
	
	//line between rows
	$table->data[$i][0] = '<hr>';
	$table->colspan[$i][0] = '8';
	
	switch ($layoutData['type']) {
		case STATIC_GRAPH:
			$table->data[$i + 1]['icon'] = print_image('images/camera.png', true);
			break;
		case PERCENTILE_BAR:
			$table->data[$i + 1]['icon'] = print_image('images/chart_bar.png', true);
			break;
		case MODULE_GRAPH:
			$table->data[$i + 1]['icon'] = print_image('images/chart_curve.png', true);
			break;
		case SIMPLE_VALUE:
			$table->data[$i + 1]['icon'] = print_image('images/binary.png', true);
			break;
		default:
			$table->data[$i + 1]['icon'] = '';
			break;
	}
	
	$table->data[$i + 1][0] = '<span style="width: 130px; display: block;">' .
		print_input_text ('label_' . $idLayoutData, $layoutData['label'], '', 10, 200, true) . 
		print_input_text_extended ('label_color_' . $idLayoutData, $layoutData['label_color'], 'text-'.'label_color_' . $idLayoutData, '', 7, 7, false, '', 'style="visibility: hidden; width: 0px;" class="label_color"', true) . 
		'</span>';
	if ($layoutData['type'] == STATIC_GRAPH) {
		$table->data[$i + 1][1] = print_select ($images_list, 'image_' . $idLayoutData, $layoutData['image'], '', 'None', '', true);
	}
	else {
		$table->data[$i + 1][1] = '';
	}
	$table->data[$i + 1][2] = print_input_text('width_' . $idLayoutData, $layoutData['width'], '', 3, 5, true) .
		'x' .
		print_input_text('height_' . $idLayoutData, $layoutData['height'], '', 3, 5, true);
	$table->data[$i + 1][3] = '(' . print_input_text('left_' . $idLayoutData, $layoutData['pos_x'], '', 3, 5, true) .
		',' . print_input_text('top_' . $idLayoutData, $layoutData['pos_y'], '', 3, 5, true) .
		')';
	$table->data[$i + 1][4] = print_select_from_sql ('SELECT id, label FROM tlayout_data WHERE id_layout = '. $idVisualConsole . ' AND id !=' . $idLayoutData,
		'parent_' . $idLayoutData, $layoutData['parent_item'], '', 'None', 0, true);
	$table->data[$i + 1][5] = '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=' .
		$activeTab  . '&action=delete&id_visual_console=' . $visualConsole["id"] . '&id_element=' . $idLayoutData . '" ' . 
		'onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;"><img src="images/cross.png" /></a>';
	
	$table->data[$i + 2]['icon'] = '';
	$table->data[$i + 2][0] = '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search.") . '</span></a>' . print_input_text_extended ('agent_' . $idLayoutData, get_agent_name($layoutData['id_agent']), 'text-agent_' . $idLayoutData, '', 15, 100, false, '',
		array('class' => 'text-agent', 'style' => 'background: #ffffff url(images/lightning.png) no-repeat right;'), true);
	$sql = 'SELECT id_agente_modulo, nombre FROM tagente_modulo WHERE disabled = 0 AND id_agente = ' . $layoutData['id_agent'];
	$table->data[$i + 2][1] = print_select_from_sql($sql,
		'module_' . $idLayoutData, $layoutData['id_agente_modulo'], '', '---', 0, true);
	$table->data[$i + 2][2] = '';	
	if ($layoutData['type'] == MODULE_GRAPH) {
		$table->data[$i + 2][3] = print_select ($intervals, 'period_' . $idLayoutData, $layoutData['period'], '', '--', 0, true);
	}
	else {
		$table->data[$i + 2][3] = '';
	}
	$table->data[$i + 2][4] = print_select_from_sql ('SELECT id, name FROM tlayout WHERE id != ' . $idVisualConsole,
					'map_linked_' . $idLayoutData, $layoutData['id_layout_linked'], '', 'None', '', true);
	$table->data[$i + 2][5] = '';
	
	if ($alternativeStyle) {
		$table->rowclass[$i + 1] = 'rowOdd';
		$table->rowclass[$i + 2] = 'rowOdd';
	}
	else {
		$table->rowclass[$i + 1] = 'rowPair';
		$table->rowclass[$i + 2] = 'rowPair';
	}
	$alternativeStyle = !$alternativeStyle; 
	
	$i = $i + 3;
}


echo '<form method="post" action="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=' . $activeTab  . '&id_visual_console=' . $visualConsole["id"] . '">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_input_hidden ('action', 'update');
print_input_hidden ('id_visual_console', $visualConsole["id"]);
print_submit_button (__('Update'), 'go', false, 'class="sub next"');
echo '</div>';
print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Update'), 'go', false, 'class="sub next"');
echo '</div>';
echo '</form>';

//Trick for it have a traduct text for javascript.
echo '<span id="ip_text" style="display: none;">' . __('IP') . '</span>';

require_css_file ('color-picker');

require_jquery_file ('ui.core');
require_jquery_file ('ui.draggable');
require_jquery_file ('ui.droppable');
require_jquery_file ('colorpicker');
require_jquery_file ('pandora.controls');
require_javascript_file ('wz_jsgraphics');
require_javascript_file ('pandora_visual_console');
require_jquery_file('ajaxqueue');
require_jquery_file('bgiframe');
require_jquery_file('autocomplete');
?>
<script type="text/javascript">
$(document).ready (function () {
	$(".label_color").attachColorPicker();
});

var idText = $("#ip_text").html();

$(".text-agent").autocomplete(
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
				$(".text-agent").css ('background-color', '#cc0000');
			else
				$(".text-agent").css ('background-color', '');
			if (data == "")
				return false;
			return data[0]+'<br><span class="ac_extra_field">' + idText + ': '+data[1]+'</span>';
		},
		delay: 200
	}
);


$(".text-agent").result (
	function (event, data, formatted) {
		var id = $(this).attr('id').replace('text-agent_', '');
		
		selectAgent = true;
		var agent_name = this.value;
		$('#module_' + id).fadeOut ('normal', function () {
			$('#module_' + id).empty ();
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
					$('#module_' + id).append ($('<option></option>').attr ('value', 0).text ("--"));
					jQuery.each (data, function (i, val) {
						s = js_html_entity_decode (val['nombre']);
						$('#module_' + id).append ($('<option></option>').attr ('value', val['id_agente_modulo']).text (s));
					});
					$('#module_' + id).fadeIn ('normal');
				}
			});
		});

		
	}
);
</script>
