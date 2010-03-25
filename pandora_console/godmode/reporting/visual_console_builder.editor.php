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

echo '<div id="editor">';
	echo '<div id="toolbox">';
		printButtonEditorVisualConsole('static_graph', __('Static Graph'));
		printButtonEditorVisualConsole('percentile_bar', __('Percentile Bar'));
		printButtonEditorVisualConsole('module_graph', __('Module Graph'));
		printButtonEditorVisualConsole('simple_value', __('Simple Value'));
		
		printButtonEditorVisualConsole('edit_item', __('Edit item'), 'right', true);
		printButtonEditorVisualConsole('delete_item', __('Delete item'), 'right', true);
	echo '</div>';
echo '</div>';
echo '<div style="clear:both;"></div>';

echo "<form id='form_visual_map' method='post' action='index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab . "&id_visual_console=" . $idVisualConsole . "'>";
print_input_hidden('action', 'update');
$background = $visualConsole['background'];
$widthBackground = $visualConsole['width'];
$heightBackground = $visualConsole['height'];

if (($widthBackground == 0) && ($heightBackground == 0)) {
	$backgroundSizes = getimagesize('images/console/background/' . $background);
	$widthBackground = $backgroundSizes[0];
	$heightBackground = $backgroundSizes[1]; 
}

$layoutDatas = get_db_all_rows_field_filter ('tlayout_data', 'id_layout', $idVisualConsole);
if ($layoutDatas === false)
	$layoutDatas = array();
	
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

echo '<div id="properties_panel" style="display: none; position: absolute; border: 2px solid #114105; padding: 5px; background: white; z-index: 99;">';
//----------------------------Hiden Form----------------------------------------
echo '<div id="hidden_panel_properties"">';
	echo '<div id="basic_options" style="width: 300px">';
	
		echo '<span id="tittle_panel_span_background" class="tittle_panel_span" style="display: none; font-weight: bolder;">' . __('Background') . '</span><br /><br />';
		echo '<span id="tittle_panel_span_static_graph" class="tittle_panel_span" style="display: none; font-weight: bolder;">' . __('Static Graph') . '</span><br /><br />';
		
		echo '<div id="label_div" class="static_graph" style="display: block; margin-bottom: 5px;">';
			echo __('Label') . ':';
			print_input_text ('label', '', '', 20, 200);
		echo '</div>';
		
		echo '<div id="image_div" class="static_graph" style="display: block; margin-bottom: 5px;">';
			echo __('Image') . ':';
			print_select ($images_list, 'image', '', 'showPreviewStaticGraph(this.value);', 'None', '');
			echo '<div id="preview"></div>';
		echo '</div>';
		
		echo '<div id="position_div" class="static_graph" style="display: block; margin-bottom: 5px;">';
		echo __('Position') . ':';
			echo '(';
			print_input_text('left', '0', '', 3, 5);
			echo ' , ';
			print_input_text('top', '0', '', 3, 5);
			echo ')';
			echo '<br />';
		echo '</div>';
		
		echo '<div id="agent_div" class="static_graph" style="display: block; margin-bottom: 5px;">';
			echo __('Agent') . '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>' . ':';
			print_input_text_extended ('agent', '', 'text-agent', '', 25, 100, false, '',
			array('style' => 'background: #ffffff url(images/lightning.png) no-repeat right;'), false);
			echo '<br />';
		echo '</div>';
		
		echo '<div id="module_div" class="static_graph" style="display: block; margin-bottom: 5px;">';
			echo __('Module') . ':';
			print_select (array (), 'module', '', '', __('Any'), 0);
			echo '<br />';
		echo '</div>';
		
		echo '<div id="background_div" class="background" style="display: block; margin-bottom: 5px;">';
			echo __('Background') . ':';
			print_select($backgrounds_list, 'background_image', $background, '', 'None', '');
		echo '</div>';
		
		echo '<div id="button_update_div" style="text-align: right; margin-top: 20px;">';
			print_button(__('Update'), 'update_button', false, 'updateAction();', 'class="sub"');
		echo '</div>';
		
		echo '<div id="button_create_div" style="text-align: right; margin-top: 20px;">';
			print_button(__('Create'), 'create_button', false, 'createAction();', 'class="create sub"');
		echo '</div>';
	
	echo '</div>';
	echo '<div id="line" style="width: 300px; border-bottom: 1px solid black; text-align: right; margin-top: 20px;">';
		echo '<a href="javascript: showAdvanceOptions()">' . __('Advance options') . '</a>';
	echo '</div>';
	echo '<div id="advance_options" style="width: 300px; display: none;">';
		echo '<div style="margin-top: 20px;"></div>';
	
		echo '<div id="period_div" class="" style="display: block; margin-bottom: 5px;">';
			echo __('Period') . ':';
			print_select ($intervals, 'period', '', '', '--', 0);
		echo '</div>';
	
		echo '<div id="size_div" class="background static_graph" style="display: block; margin-bottom: 5px;">';
			echo __('Size') . ':';
			print_input_text('width', 0, '', 3, 5);
			echo ' X ';
			print_input_text('height', 0, '', 3, 5);
			echo '<br />';
		echo '</div>';
		
		echo '<div id="parent_div" class="static_graph" style="display: block; margin-bottom: 5px;">';
			echo __('Parent') . ':';
			print_select_from_sql('SELECT id, label FROM tlayout_data WHERE id_layout = ' . $visualConsole['id'], 'parent', '', '', __('None'), 0);
			echo '<br />';
		echo '</div>';
		
		echo '<div id="map_linked_div" class="static_graph" style="display: block; margin-bottom: 5px;">';
			echo __('Map linked') . ':';
			print_select_from_sql ('SELECT id, name FROM tlayout WHERE id != ' . $idVisualConsole,
				'map_linked', '', '', 'None', '0');
		echo '</div>';
		
		echo '<div id="label_color_div" class="static_graph" style="display: block; margin-bottom: 5px;">';
			echo __('Label color') . ':';
			print_input_text_extended ('label_color', '#000000', 'text-'.'label_color',
				'', 7, 7, false, '', 'class="label_color"', false);
		echo '</div>';
		//Trick for it have a traduct "any" text.
		echo '<span id="any_text" style="display: none;">' . __('Any') . '</span>';
		echo '<span id="ip_text" style="display: none;">' . __('IP') . '</span>';
	echo '</div>';
echo "</div>";
//------------------------------------------------------------------------------
echo '</div>';
echo '<div id="frame_view" style="width: 100%; height: 500px; overflow: scroll;">';
echo '<div id="background" class="ui-widget-content" style="background: url(images/console/background/' . $background . ');
	border: 2px black solid; width: ' . $widthBackground . 'px; height: ' . $heightBackground . 'px;">';

foreach ($layoutDatas as $layoutData) {
	printItemInVisualConsole($layoutData);
}

echo '</div>';
echo '</div>';

print_input_hidden('background_width', $widthBackground);
print_input_hidden('background_height', $heightBackground);
echo "</form>";

require_css_file ('color-picker');

require_jquery_file('ui.core');
require_jquery_file('ui.resizable');
require_jquery_file('colorpicker');
require_jquery_file('ui.draggable');
require_javascript_file('wz_jsgraphics');
require_javascript_file('pandora_visual_console');
require_javascript_file('visual_console_builder.editor', 'godmode/reporting/');

function printButtonEditorVisualConsole($idDiv, $label, $float = 'left', $disabled = false) {
	if (!$disabled) $disableClass = '';
	else $disableClass = 'disabled';
	
	if ($float == 'left') {
		$margin = 'margin-right';
	}
	else {
		$margin = 'margin-left';
	}
	
	echo '<div class="button_toolbox ' . $disableClass . '" id="' . $idDiv . '"
		style="font-weight: bolder; text-align: center; float: ' . $float . ';' .
			'width: 80px; height: 50px; background: #e5e5e5; border: 4px outset black; ' . $margin . ': 5px;">';
	if ($disabled) {
		echo '<span class="label" style="color: #aaaaaa;">';
	}
	else {
		echo '<span class="label" style="color: #000000;">';
	}
	echo $label;
	echo '</span>';
	echo '</div>';
}

function printItemInVisualConsole($layoutData) {
	$width = $layoutData['width'];
	$height = $layoutData['height'];
	$top = $layoutData['pos_y'];
	$left = $layoutData['pos_x'];
	$id = $layoutData['id'];
	$color = $layoutData['label_color'];
	$label = $layoutData['label'];
	
	$img = getImageStatusElement($layoutData);
	$imgSizes = getimagesize($img);
	//debugPrint($imgSizes);
	
	if (($width == 0) && ($height == 0)) {
		$sizeStyle = '';
		$imageSize = '';
	}
	else {
		$sizeStyle = 'width: ' . $width . 'px; height: ' . $height . 'px;';
		$imageSize = 'width="' . $width . '" height="' . $height . '"';
	}
	
	echo '<div id="' . $id . '" class="item static_graph" style="text-align: center; color: ' . $color . '; position: absolute; ' . $sizeStyle . ' margin-top: ' . $top . 'px; margin-left: ' . $left . 'px;">';
	echo '<img class="image" id="image_' . $id . '" src="' . $img . '" ' . $imageSize . ' /><br />';
	echo '<span id="text_' . $id . '" class="text">' . $label . '</span>';
	echo "</div>";
	if ($layoutData['parent_item'] != 0) {
		echo '<script type="text/javascript">';
		echo '$(document).ready (function() {
			lines.push({"id": "' . $id . '" , "node_begin":"' . $layoutData['parent_item'] . '","node_end":"' . $id . '","color":"' . getColorLineStatus($layoutData) . '"});
		});';
		echo '</script>';
	}
}
?>
<style type="text/css">
.ui-resizable-handle {
	background: transparent !important;
	border: transparent !important;
}
</style>
<script type="text/javascript">
	id_visual_console = <?php echo $visualConsole['id']; ?>;
	$(document).ready (editorMain2);
</script>