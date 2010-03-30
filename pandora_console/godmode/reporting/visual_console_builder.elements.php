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
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
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
$intervals[1209600] = __('Last week');
$intervals[2419200] = "15 ".__('days');
$intervals[4838400] = __('Last month');
$intervals[9676800] = "2 ".__('months');
$intervals[29030400] = "6 ".__('months');

$table->width = '100%';
$table->head = array ();
$table->head[0] = __('Label') . ' / ' . __('Type') . ' / ' . __('Parent');
$table->head[1] = __('Image') . ' / ' . __('Agent') . ' / ' . __('Map linked');
$table->head[2] = __('Height / Max value') . ' / ' . __('Module') . ' / ' . __('Label color');
$table->head[3] = __('Width') . ' / ' . __('Period');
$table->head[4] = __('Left');
$table->head[5] = __('Top');
$table->head[6] = __('Action');

$table->data = array();

//Background
$table->data[0][0] = __('Background');
$table->data[0][1] = print_select($backgrounds_list, 'background', $visualConsole['background'], '', 'None', '', true);
$table->data[0][2] = print_input_text('width', $visualConsole['width'], '', 3, 5, true);
$table->data[0][3] = print_input_text('height', $visualConsole['height'], '', 3, 5, true);
$table->data[0][4] = '';
$table->data[0][5] = '';
$table->data[0][6] = '';

$table->data[1][0] = __('Background');
$table->data[1][1] = '';
$table->data[1][2] = '';
$table->data[1][3] = '';
$table->data[1][4] = '';
$table->data[1][5] = '';
$table->data[1][6] = '';

$table->data[2][0] = '';
$table->data[2][1] = '';
$table->data[2][2] = '';
$table->data[2][3] = '';
$table->data[2][4] = '';
$table->data[2][5] = '';
$table->data[2][6] = '';

$i = 2;
$layoutDatas = get_db_all_rows_field_filter ('tlayout_data', 'id_layout', $idVisualConsole);
if ($layoutDatas === false)
	$layoutDatas = array();

$alternativeStyle = true;
foreach ($layoutDatas as $layoutData) {
	$idLayoutData = $layoutData['id'];
	
	$table->data[$i][0] = print_input_text ('label_' . $idLayoutData, $layoutData['label'], '', 20, 200, true);
	if ($layoutData['type'] == STATIC_GRAPH) {
		$table->data[$i][1] = print_select ($images_list, 'image_' . $idLayoutData, $layoutData['image'], '', 'None', '', true);
	}
	else {
		$table->data[$i][1] = '';
	}
	$table->data[$i][2] = print_input_text('width_' . $idLayoutData, $layoutData['width'], '', 3, 5, true);
	$table->data[$i][3] = print_input_text('height_' . $idLayoutData, $layoutData['height'], '', 3, 5, true);
	$table->data[$i][4] = print_input_text('left_' . $idLayoutData, $layoutData['pos_x'], '', 3, 5, true);
	$table->data[$i][5] = print_input_text('top_' . $idLayoutData, $layoutData['pos_y'], '', 3, 5, true);
	$table->data[$i][6] = '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=' .
		$activeTab  . '&action=delete&id_visual_console=' . $visualConsole["id"] . '&id_element=' . $idLayoutData . '" ' . 
		'onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;"><img src="images/cross.png" /></a>';
	
	$table->data[$i + 1][0] = $layoutDataTypes[$layoutData['type']];
	$table->data[$i + 1][1] = print_input_text_extended ('agent_' . $idLayoutData, get_agent_name($layoutData['id_agent']), 'text-agent_' . $idLayoutData, '', 25, 100, false, '',
		array('class' => 'text-agent', 'style' => 'background: #ffffff url(images/lightning.png) no-repeat right;'), true);
	$table->data[$i + 1][2] = print_select_from_sql('SELECT id_agente_modulo, nombre FROM tagente_modulo WHERE id_agente = ' . $layoutData['id_agent'],
		'module_' . $idLayoutData, $layoutData['id_agente_modulo'], '', '---', 0, true);
	if ($layoutData['type'] == MODULE_GRAPH) {
		$table->data[$i + 1][3] = print_select ($intervals, 'period_' . $idLayoutData, $layoutData['period'], '', '--', 0, true);
	}
	else {
		$table->data[$i + 1][3] = '';
	}
	$table->data[$i + 1][4] = '';
	$table->data[$i + 1][5] = '';
	$table->data[$i + 1][6] = '';
	
	$table->data[$i + 2][0] = print_select_from_sql ('SELECT id, label FROM tlayout_data WHERE id_layout = '. $idVisualConsole . ' AND id !=' . $idLayoutData,
		'parent_' . $idLayoutData, $layoutData['parent_item'], '', 'None', 0, true);
	$table->data[$i + 2][1] = print_select_from_sql ('SELECT id, name FROM tlayout WHERE id != ' . $idVisualConsole,
					'map_linked_' . $idLayoutData, $layoutData['id_layout_linked'], '', 'None', '', true);
	//$table->data[$i + 2][2] = print_input_text ('label_color_' . $idLayoutData, '#000000', $layoutData['label_color'], 7, 7, true);
	$table->data[$i + 2][2] = print_input_text_extended ('label_color_' . $idLayoutData, $layoutData['label_color'], 'text-'.'label_color_' . $idLayoutData
		, '', 7, 7, false, '', 'class="label_color"', true);
	$table->data[$i + 2][3] = '';
	$table->data[$i + 2][4] = '';
	$table->data[$i + 2][5] = '';
	$table->data[$i + 2][6] = '';
	
	if ($alternativeStyle) {
		$table->rowclass[$i] = 'rowOdd';
		$table->rowclass[$i + 1] = 'rowOdd';
		$table->rowclass[$i + 2] = 'rowOdd';
	}
	else {
		$table->rowclass[$i] = 'rowPair';
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