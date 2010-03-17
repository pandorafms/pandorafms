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

$action = get_parameterBetweenListValues('action', array('new', 'save', 'edit', 'update'), 'new');
$activeTab = get_parameterBetweenListValues('tab', array('data', 'list_elements', 'wizard', 'editor'), 'data');
$idVisualConsole = get_parameter('id_visual_console', 0);

//Save/Update data in DB
$statusProcessInDB = null;
switch ($activeTab) {
	case 'data':
		switch ($action) {
			case 'new':
				$idGroup = '';
				$background = '';
				$visualConsoleName = '';
				break;
			case 'update':
			case 'save':
				$idGroup = get_parameter('id_group');
				$background = get_parameter('background');
				$visualConsoleName = get_parameter('name');
				$values = array('name' => $visualConsoleName, 'id_group' => $idGroup, 'background' => $background);
				switch ($action) {
					case 'update':
						$result = process_sql_update('tlayout', $values, array('id' => $idVisualConsole));
						if ($result !== false) {
							$action = 'edit';
							$statusProcessInDB = array('flag' => true, 'message' => '<h3 class="suc">'.__('Successfully update.').'</h3>');
						}
						else {
							$statusProcessInDB = array('flag' => false, 'message' => '<h3 class="error">'.__('Could not be update.').'</h3>');
						}
						break;
					case 'save':
						$idVisualConsole = process_sql_insert('tlayout', $values);
						if ($idVisualConsole !== false) {
							$action = 'edit';
							$statusProcessInDB = array('flag' => true, 'message' => '<h3 class="suc">'.__('Successfully created.').'</h3>');
						}
						else {
							$statusProcessInDB = array('flag' => false, 'message' => '<h3 class="error">'.__('Could not be created.').'</h3>');
						}
						break;
				}
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				break;
			case 'edit':
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				$visualConsoleName = $visualConsole['name'];
				$idGroup = $visualConsole['id_group'];
				$background = $visualConsole['background'];
				break;
		}
		break;
	case 'list_elements':
		switch ($action) {
			case 'edit':
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				$visualConsoleName = $visualConsole['name'];
				break;
		}
		break;
	case 'editor':
		switch ($action) {
			case 'edit':
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				$visualConsoleName = $visualConsole['name'];
				break;
		}
		break;
}

$buttons = array(
	'data' => array('active' => false,
		'text' => '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=data&action=' . $action . '&id_visual_console=' . $idVisualConsole . '">' . 
			print_image ("images/god9.png", true, array ("title" => __('Data'))) .'</a>'),
	'list_elements' => array('active' => false,
		'text' => '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=list_elements&action=' . $action . '&id_visual_console=' . $idVisualConsole . '">' .
			print_image ("images/god6.png", true, array ("title" => __('List elements'))) .'</a>'),
	'wizard' => array('active' => false,
		'text' => '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=wizard&action=' . $action . '&id_visual_console=' . $idVisualConsole . '">' .
			print_image ("images/pill.png", true, array ("title" => __('Wizard'))) .'</a>'),
	'editor' => array('active' => false,
		'text' => '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=editor&action=' . $action . '&id_visual_console=' . $idVisualConsole . '">' .
			print_image ("images/config.png", true, array ("title" => __('Editor'))) .'</a>'));

if ($action == 'new') $buttons = array('data' => $buttons['data']); //Show only the data tab
$buttons[$activeTab]['active'] = true;
print_page_header(__('Visual console builder') . "&nbsp;" . $visualConsoleName, "", false, "visual_console_editor_" . $activeTab . "_tab", false, $buttons);

//The source code for PAINT THE PAGE
if ($statusProcessInDB !== null) {
	echo $statusProcessInDB['message'];
}

switch ($activeTab) {
	case 'data':
		switch ($action) {
			case 'new':
				echo "<form method='post' action='index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "'>";
				print_input_hidden('action', 'save');
				break;
			case 'update':
			case 'save':
				echo "<form method='post' action='index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "&id_visual_console=" . $idVisualConsole . "'>";
				print_input_hidden('action', 'update');
				break;
			case 'edit':		
				echo "<form method='post' action='index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "&id_visual_console=" . $idVisualConsole . "'>";
				print_input_hidden('action', 'update');
				break;
		}
		
		$table->width = '70%';
		$table->data = array ();
		$table->data[0][0] = __('Name:');
		$table->data[0][1] = print_input_text ('name', $visualConsoleName, '', 15, 50, true);
		$table->data[1][0] = __('Group:');
		$groups = get_user_groups ($config['id_user']);
		$table->data[1][1] = print_select ($groups, 'id_group', $idGroup, '', '', '', true);
		$table->data[2][0] = '';
		$backgrounds_list = list_files ('images/console/background/', "jpg", 1, 0);
		$backgrounds_list = array_merge ($backgrounds_list, list_files ('images/console/background/', "png", 1, 0));
		$table->data[3][0] = __('Background');
		$table->data[3][1] = print_select ($backgrounds_list, 'background', $background, '', 'None', '', true);
		if ($action == 'new') {
			$textButtonSubmit = __('Save');
			$classButtonSubmit = 'sub wand';
		}
		else {
			$textButtonSubmit = __('Update');
			$classButtonSubmit = 'sub upd';
		}
		$table->rowstyle[4] = "text-align: right;";
		$table->data[4][0] = '';
		$table->data[4][1] = print_submit_button ($textButtonSubmit, 'update_layout', false, 'class="' . $classButtonSubmit . '"', true);
		
		print_table($table);
		echo "</form>";
		break;
	case 'list_elements':		
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
		$table->head[2] = __('Height') . ' / ' . __('Module') . ' / ' . __('Label color');
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
			if ($layoutData['type'] == 0) {
				$table->data[$i][1] = print_select ($images_list, 'image', $layoutData['image'], '', 'None', '', true);
			}
			else {
				$table->data[$i][1] = '';
			}
			$table->data[$i][2] = print_input_text('width_' . $idLayoutData, $layoutData['width'], '', 3, 5, true);
			$table->data[$i][3] = print_input_text('height_' . $idLayoutData, $layoutData['height'], '', 3, 5, true);
			$table->data[$i][4] = print_input_text('left_' . $idLayoutData, $layoutData['pos_x'], '', 3, 5, true);
			$table->data[$i][5] = print_input_text('top_' . $idLayoutData, $layoutData['pos_y'], '', 3, 5, true);
			$table->data[$i][6] = '<a href=""><img src="images/cross.png" /></a>';
			$table->data[$i + 1][0] = print_select (get_layout_data_types(), 'type_' . $idLayoutData, $layoutData['type'], '', '', 0, true, false, false);
			$table->data[$i + 1][1] = print_input_text_extended ('agent_' . $idLayoutData, get_agent_name($layoutData['id_agent']), 'text-agent', '', 25, 100, false, '',
				array('style' => 'background: #ffffff url(images/lightning.png) no-repeat right;'), true);
			$table->data[$i + 1][2] = print_select_from_sql('SELECT id_agente_modulo, nombre FROM tagente_modulo WHERE id_agente = ' . $layoutData['id_agent'],
				'module_' . $idLayoutData, $layoutData['id_agente_modulo'], '', '---', 0, true);
			$table->data[$i + 1][3] = print_select ($intervals, 'period_' . $idLayoutData, $layoutData['period'], '', '--', 0, true);
			$table->data[$i + 1][4] = '';
			$table->data[$i + 1][5] = '';
			$table->data[$i + 1][6] = '';
			$table->data[$i + 2][0] = print_select_from_sql ('SELECT id, label FROM tlayout_data WHERE id_layout = '. $idVisualConsole,
				'parent_' . $idLayoutData, $layoutData['parent_item'], '', 'None', '', true);
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
			
			//$table->data[5][1] = print_input_text_extended ('agent', '', 'text-agent', '', 30, 100, false, '',
			
			
			$i = $i + 3;
		}
		
		print_table($table);
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
		</script>
		<?php
		break;
	case 'editor':
		//Arrays for select box.
		$backgrounds_list = list_files('images/console/background/', "jpg", 1, 0);
		$backgrounds_list = array_merge($backgrounds_list, list_files ('images/console/background/', "png", 1, 0));
		
		echo "<form method='post' action='index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab . "&id_visual_console=" . $idVisualConsole . "'>";
		debugPrint($_POST);
		debugPrint(get_parameter('action'));
		switch($action) {
			case 'edit':
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				
				print_input_hidden('action', 'update');
				break;
			case 'update':
				$values = array('background' => get_parameter('background_image'),
					'height' => get_parameter('height_background'),
					'width' => get_parameter('width_background'));
				
				$correctUpdate = process_sql_update('tlayout', $values, array('id' => $idVisualConsole));
				
				if ($correctUpdate !== false) {
					echo '<h3 class="suc">'.__('Successfully created').'</h3>';
				}
				else {
					echo '<h3 class="error">'.__('Could not be created').'</h3>';
				}
				
				$visualConsole = get_db_row_filter('tlayout', array('id' => $idVisualConsole));
				print_input_hidden('action', 'update');
				break;
		}
		$background = $visualConsole['background'];
		$widthBackground = $visualConsole['width'];
		$heightBackground = $visualConsole['height'];
		
		$layoutDatas = get_db_all_rows_field_filter ('tlayout_data', 'id_layout', $idVisualConsole);
		if ($layoutDatas === false)
			$layoutDatas = array();
		
		echo '<div id="toolbox" style="width: 100%">';
		print_button(__('New element'), 'new_button', false, 'alert(666);', 'class="sub add"');
		print_button(__('Properties'), 'properties_button', true, 'togglePropertiesPanel();', 'class="sub"');
		print_submit_button(__('Save'), 'save_button', false, 'class="sub next"');
		//<a href="">' . __('New') . '</a> <a href="">' . __('Properties') . '</a>
		echo '</div>';
		echo '<div id="new_panel"></div>';
		echo '<div id="properties_panel" style="display: none; position: absolute; border: 2px solid #114105; padding: 5px; background: white; z-index: 1;">ZZZZ<br />ZZZZZ<br />ZZZZ</div>';
		echo '<div id="frame_view" style="width: 100%; height: 500px; overflow: scroll;">';
		//echo '<div id="test" style="background: red; width: 1000px; height: 50px; border: 2px solid black;"></div>';
		echo '<div id="background" class="ui-widget-content" style="background: url(images/console/background/' . $background . ');
			border: 2px black solid; width: ' . $widthBackground . 'px; height: ' . $heightBackground . 'px;">';
		
		foreach ($layoutDatas as $layoutData) {
			printItemInVisualConsole($layoutData);
		}
		
		echo '</div>';
		echo '</div>';

		echo "<div id='hidden_panel_properties_background' style='display: none'>";
		$table = null;
		$table->width = '300px';
		$table->colspan[0][0] = 2;
		$table->data = array();
		$table->data[0][0] = '<b>' . __('Background') . '</b>';
		$table->data[1][0] = __('Width') . ':';
		$table->data[1][1] = print_input_text('width_background', $widthBackground, '', 3, 5, true);
		$table->data[2][0] =  __('Height') . ':';
		$table->data[2][1] = print_input_text('height_background', $heightBackground, '', 3, 5, true);
		$table->data[3][0] = __('Background') . ':';
		$table->data[3][1] = print_select($backgrounds_list, 'background_image', $background, '', 'None', '', true);
		print_table($table);
		echo "</div>";
		
		print_input_hidden('background_width', $widthBackground);
		print_input_hidden('background_height', $heightBackground);
		echo "</form>";
		debugPrint($layoutDatas);
		echo '<link type="text/css" href="include/javascript/jquery.ui/base/jquery.ui.all.css" rel="stylesheet" />';
		echo "<script type='text/javascript' src='include/javascript/jquery.ui/jquery.ui.core.min.js'></script>";
		echo "<script type='text/javascript' src='include/javascript/jquery.ui/jquery-ui-1.8rc3.custom.min.js'></script>";
		echo "<script type='text/javascript' src='include/javascript/jquery.ui/jquery.ui.mouse.min.js'></script>";
		echo "<script type='text/javascript' src='include/javascript/jquery.ui/jquery.ui.resizable.min.js'></script>";
		echo "<script type='text/javascript' src='include/javascript/jquery.ui/jquery.ui.widget.min.js'></script>";
		
		?>
		<script type="text/javascript">
		var selectedItem = null;
		var selectedItemData = null;
		var openPropertiesPanel = false;
		
		$(document).ready (function () {
			$("#background").resizable();
			//$('.item').resizable();

			$('#background').bind('resizestart', function(event, ui) {
				if (!openPropertiesPanel) {
					$("#background").css('border', '2px red solid');
				}
			});

			$('#background').bind('resizestop', function(event, ui) {
				if (!openPropertiesPanel) {
					unselectAll();
					$("#text-width_background").val($('#background').css('width').replace('px', ''));
					$("#text-height_background").val($('#background').css('height').replace('px', ''));
				}
			});

			//Event click for background
			$("#background").click(function(event) {
				event.stopPropagation();
				if (!openPropertiesPanel) {
					$("#background").css('border', '2px blue dotted');
					$("input[name=properties_button]").removeAttr('disabled');
					selectedItem = 'background';
				}
			});

			$(".item").click(function(event) {
				event.stopPropagation();
				if (!openPropertiesPanel) {
					unselectAll();
					dataItem_serialize = $(event.target).attr('id');
					dataItem = dataItem_serialize.split('_');
					type = dataItem[1];
					indb = dataItem[2];
					id = dataItem[3];

					selectedItem = 'layoutItem';
					selectedItemData = {};
					selectedItemData['id'] = id;
					selectedItemData['type'] = type;
					if (indb == 'indb') {
						selectedItemData['indb'] = true;
					}
					else {
						selectedItemData['indb'] = false;
					}

					$(event.target).css('border', '2px blue dotted');
					$("input[name=properties_button]").removeAttr('disabled');
				}
			});
		});

		//function change style of all selectable items
		function unselectAll() {
			$("#background").css('border', '2px black solid');
//			console.log(321);
//			$(".item").css().each(function(i, val) {
//				console.log(888);
//			});
//			console.log(123);
//			
//			console.log(666);console.log($(".item"));
//			console.log($(".item").css());
//			console.log(999);
			$(".item").css('border', '');
		}

		function togglePropertiesPanel() {
			if (!openPropertiesPanel) {
				$("#submit-save_button").attr('disabled', 'disabled');
				$("#background").resizable('disable');
				
				switch (selectedItem) {
					case 'background':
						chunkPanelPropertiesBackground = $("#hidden_panel_properties_background").clone();
						
						width = $("#text-width_background").val();
						height = $("#text-height_background").val();
						background_image = $("select[name=background_image]").val();

						$("input[name=width_background]", chunkPanelPropertiesBackground).attr('id', 'edition_width_background');
						$("input[name=height_background]", chunkPanelPropertiesBackground).attr('id', 'edition_height_background');
						$("select[name=background_image]", chunkPanelPropertiesBackground).attr('id', 'edition_background_image');

						$("#properties_panel").empty().append(chunkPanelPropertiesBackground.html());
						chunkPanelPropertiesBackground = null;

						$("#edition_width_background").val(width);
						$("#edition_height_background").val(height);
						$("#edition_background_image").val(background_image);

						$("#edition_width_background").change(function() {
							width = $("#edition_width_background").val();
							$("#background").css('width', width + 'px');
							$("input[name=width_background]").val(width);
						});

						$("#edition_height_background").change(function() {
							height = $("#edition_height_background").val();
							$("#background").css('height', height + 'px');
							$("input[name=height_background]").val($("#edition_height_background").val());
						});

						$("#edition_background_image").change(function() {
							background_image = $("#edition_background_image").val();
							$("#background").css('background', 'url(images/console/background/' + background_image + ')');
							$("select[name=background_image]").val(background_image);
						});
						break;
					case 'layoutItem':
						chunkPanelPropertiesBackground = $("#layoutItem_hidden_fields_" + selectedItemData['id']).clone();

						width = $("input[name=width_indb_" + selectedItemData['id'] + "]").val();
						height = $("input[name=height_indb_" + selectedItemData['id'] + "]").val();
						left = $("input[name=left_indb_" + selectedItemData['id'] + "]").val();
						top = $("input[name=top_indb_" + selectedItemData['id'] + "]").val();

						$("input[name=left_indb_" + selectedItemData['id'] + "]", chunkPanelPropertiesBackground).attr('id', 'edition_left_item');
						$("input[name=top_indb_" + selectedItemData['id'] + "]", chunkPanelPropertiesBackground).attr('id', 'edition_top_item');
						$("input[name=width_indb_" + selectedItemData['id'] + "]", chunkPanelPropertiesBackground).attr('id', 'edition_width_item');
						$("input[name=height_indb_" + selectedItemData['id'] + "]", chunkPanelPropertiesBackground).attr('id', 'edition_height_item');
						
						$("#properties_panel").empty().append(chunkPanelPropertiesBackground.html());
						chunkPanelPropertiesBackground = null;

						$("#edition_left_item").val(left);
						$("#edition_top_item").val(top);
						$("#edition_width_item").val(width);
						$("#edition_height_item").val(height);

						$("#edition_left_item").change(function() {
							left = $("#edition_left_item").val();
							$("#DivLayoutItem_image_indb_" +  selectedItemData['id']).css('margin-left', left + 'px');
							$("input[name=left_indb_" + selectedItemData['id'] + "]").val(left);
						});

						$("#edition_top_item").change(function() {
							top = $("#edition_top_item").val();
							$("#DivLayoutItem_image_indb_" +  selectedItemData['id']).css('margin-top', left + 'px');
							$("input[name=top_indb_" + selectedItemData['id'] + "]").val(top);
						});

						$("#edition_width_item").change(function() {
							width = $("#edition_width_item").val();
							alert($("#layoutItem_image_indb_" +  selectedItemData['id']).attr('width'));
							$("#layoutItem_image_indb_" +  selectedItemData['id']).attr('width', width);
							$("input[name=width_indb_" + selectedItemData['id'] + "]").val(width);
						});
						break;
				}
				
				$("#properties_panel").show("fast");
			}
			else {
				$("#background").resizable('enable');
				$("#properties_panel").hide("fast");
				$("input[name=save_button]").removeAttr('disabled');
			}
			openPropertiesPanel = !openPropertiesPanel;
		}
		</script>
		<?php
		break;
}

function printItemInVisualConsole($layoutData) {
	$width = $layoutData['width'];
	$height = $layoutData['height'];
	$top = $layoutData['pos_y'];
	$left = $layoutData['pos_x'];
	$id = $layoutData['id'];
	
	$img = getImageStatusElement($layoutData);
	$imgSizes = getimagesize($img);
	//debugPrint($imgSizes);
	
	//TODO set type now image by default
	echo '<div id="DivLayoutItem_image_indb_' . $id . '" style="position: absolute; width: ' . $width . 'px; height: ' . $height . 'px; margin-top: ' . $top . 'px; margin-left: ' . $left . 'px;">';
	//echo '<div  style="width: ' . $imgSizes[0] . 'px; height: ' . $imgSizes[1] . 'px;">';
	echo '<img class="item" id="layoutItem_image_indb_' . $id . '" src="' . $img . '" />';
	//echo '</div>';
	echo '<div id="layoutItem_hidden_fields_' . $id . '"  style="display: none">';
	
	$table = null;
	$table->width = '300px';
	$table->colspan[0][0] = 2;
	$table->data = array();
	//TODO set type now image by default
	$table->data[0][0] = '<b>' . __('Image') . '</b>';
	$table->data[1][0] = __('left') . ':';
	$table->data[1][1] = print_input_text('left_indb_' . $id, $layoutData['pos_x'], '', 3, 5, true);
	$table->data[2][0] = __('Top') . ':';
	$table->data[2][1] = print_input_text('top_indb_' . $id, $layoutData['pos_y'], '', 3, 5, true);
	$table->data[3][0] = __('Width') . ':';
	$table->data[3][1] = print_input_text('width_indb_' . $id, $layoutData['width'], '', 3, 5, true);
	$table->data[4][0] = __('Height') . ':';
	$table->data[4][1] = print_input_text('height_indb_' . $id, $layoutData['height'], '', 3, 5, true);
	print_table($table);

	//TODO more fields
	echo '</div>';
	echo "</div>";
}

function getImageStatusElement($layoutData) {
	$img = "images/console/icons/" . $layoutData["image"];
	switch (getStatusElement($layoutData)) {
	case 1:
	case 4:
		//Critical (BAD or ALERT)
		$img .= "_bad.png";
		break;
	case 0:
		//Normal (OK)
		$img .= "_ok.png";
		break;
	case 2:
		//Warning
		$img .= "_warning.png";
		break;
	default:
		$img .= ".png";
		// Default is Grey (Other)
	}
	
	return $img;
}

function getStatusElement($layoutData) {
	//Linked to other layout ?? - Only if not module defined
	if ($layoutData['id_layout_linked'] != 0) {
		$status = get_layout_status ($layout_data['id_layout_linked']);
	}
	else if ($layoutData["type"] == 0) { //Single object
		//Status for a simple module
		if ($layoutData['id_agente_modulo'] != 0) {
			$status = get_agentmodule_status ($layoutData['id_agente_modulo']);

		//Status for a whole agent, if agente_modulo was == 0
		}
		else if ($layoutData['id_agent'] != 0) {
			$status = get_agent_status ($layoutData["id_agent"]);
			if ($status == -1) // get_agent_status return -1 for unknown!
				$status = 3;
		}
		else {
			$status = 3;
			$id_agent = 0;
		}
	}
	else {
		//If it's a graph, a progress bar or a data tag, ALWAYS report status OK
		//(=0) to avoid confussions here.
		$status = 0;
	}
	
	return $status;
}

?>


















