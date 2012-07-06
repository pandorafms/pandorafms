<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Reporting
 */
function visual_map_editor_print_item_palette($visualConsole_id, $background) {
	global $config;
	
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
	
	
	
	//Arrays for select box.
	$backgrounds_list = list_files('images/console/background/', "jpg", 1, 0);
	$backgrounds_list = array_merge($backgrounds_list, list_files ('images/console/background/', "png", 1, 0));
	
	
	
	echo '<div id="properties_panel" style="display: none; position: absolute; border: 2px solid #114105; padding: 5px; background: white; z-index: 90;">';
	//----------------------------Hiden Form----------------------------
	?>
	<table class="databox" border="0" cellpadding="4" cellspacing="4" width="300">
		<caption>
			<?php
			$titles = array(
				'background' => __('Background'),
				'static_graph' => __('Static Graph'),
				'percentile_item' => __('Percentile Item'),
				'module_graph' => __('Module Graph'),
				'module_graph' => __('Module Graph'),
				'simple_value' => __('Simple value'),
				'label' => __('Label'),
				'icon' => __('Icon'));
			
			if (enterprise_installed()) {
				enterprise_visual_map_editor_add_title_palette($titles);
			}
			
			foreach ($titles as $item => $title) {
				echo '<span id="title_panel_span_' . $item . '"
				class="title_panel_span"
				style="display: none; font-weight: bolder;">' .
				$title . '</span>';
			}
			?>
		</caption>
		<tbody>
			<?php
			$form_items = array();
			
			$form_items['label_row'] = array();
			$form_items['label_row']['items'] = array('static_graph',
				'percentile_bar', 'percentile_item', 'module_graph',
				'simple_value', 'label', 'datos'. 'icon');
			$form_items['label_row']['html'] = '<td style="">' . __('Label') .'</td>
				<td style="">'. html_print_input_text ('label', '', '', 20, 200, true) .'</td>';
			
			$form_items['image_row'] = array();
			$form_items['image_row']['items'] = array('static_graph', 'icon', 'datos');
			$form_items['image_row']['html'] = '<td>' . __('Image') . '</td>
				<td>'. html_print_select ($images_list, 'image', '', 'showPreview(this.value);', 'None', '', true) .'</td>';
			
			$form_items['preview_row'] = array();
			$form_items['preview_row']['items'] = array('static_graph', 'datos icon');
			$form_items['preview_row']['html'] = '<td colspan="2" style="text-align: right;"><div id="preview" style="text-align: right;"></div></td>';
			
			$form_items['agent_row'] = array();
			$form_items['agent_row']['items'] = array('static_graph',
				'percentile_bar', 'percentile_item', 'module_graph',
				'simple_value', 'datos');
			$form_items['agent_row']['html'] = '<td>' . __('Agent') .
				'<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search.") . '</span></a>' . '</td>
				<td>' . html_print_input_text_extended ('agent', '', 'text-agent', '', 25, 100, false, '',
					array('style' => 'background: #ffffff url(images/lightning.png) no-repeat right;'), true) . '</td>';
			
			$form_items['module_row'] = array();
			$form_items['module_row']['items'] = array('static_graph',
				'percentile_bar', 'percentile_item', 'module_graph',
				'simple_value', 'datos');
			$form_items['module_row']['html'] = '<td>' . __('Module') . '</td>
				<td>'. html_print_select (array(), 'module', '', '', __('Any'), 0, true) . '</td>';
			
			$form_items['process_value_row'] = array();
			$form_items['process_value_row']['items'] = array('simple_value', 'datos');
			$form_items['process_value_row']['html'] = '<td><span>' . __('Process') . '</span></td>
				<td>'. html_print_select (
					array (PROCESS_VALUE_MIN => __('Min value'), 
					PROCESS_VALUE_MAX => __('Max value'),
					PROCESS_VALUE_AVG => __('Avg value')),
					'process_value', '', '', __('None'), PROCESS_VALUE_NONE, true) . '</td>';
			
			$form_items['background_row_1'] = array();
			$form_items['background_row_1']['items'] = array('background', 'datos');
			$form_items['background_row_1']['html'] = '<td>' . __('Background') . '</td>
				<td>' . html_print_select($backgrounds_list, 'background_image', $background, '', 'None', '', true) . '</td>';
			
			$form_items['background_row_2'] = array();
			$form_items['background_row_2']['items'] = array('background', 'datos');
			$form_items['background_row_2']['html'] = '<td>' . __('Original Size') . '</td>
				<td>' . html_print_button(__('Apply'), 'original_false', false, "setAspectRatioBackground('original')", 'class="sub"', true) . '</td>';
			
			$form_items['background_row_3'] = array();
			$form_items['background_row_3']['items'] = array('background', 'datos');
			$form_items['background_row_3']['html'] = '<td>' . __('Aspect ratio') . '</td>
				<td>' . html_print_button(__('Width proportional'), 'original_false', false, "setAspectRatioBackground('width')", 'class="sub"', true) . '</td>';
			
			$form_items['background_row_4'] = array();
			$form_items['background_row_4']['items'] = array('background', 'datos');
			$form_items['background_row_4']['html'] = '<td></td>
				<td>' . html_print_button(__('Height proportional'), 'original_false', false, "setAspectRatioBackground('height')", 'class="sub"', true) . '</td>';
			
			$form_items['percentile_bar_row_1'] = array();
			$form_items['percentile_bar_row_1']['items'] = array('percentile_bar', 'percentile_item', 'datos');
			$form_items['percentile_bar_row_1']['html'] = '<td>' . __('Width') . '</td>
				<td>' . html_print_input_text('width_percentile', 0, '', 3, 5, true) . '</td>';
			
			$form_items['percentile_bar_row_2'] = array();
			$form_items['percentile_bar_row_2']['items'] = array('percentile_bar', 'percentile_item', 'datos');
			$form_items['percentile_bar_row_2']['html'] = '<td>' . __('Max value') . '</td>
				<td>' . html_print_input_text('max_percentile', 0, '', 3, 5, true) . '</td>';
			
			$form_items['percentile_item_row_3'] = array();
			$form_items['percentile_item_row_3']['items'] = array('percentile_bar', 'percentile_item', 'datos');
			$form_items['percentile_item_row_3']['html'] = '<td>' . __('Type') . '</td>
				<td>' . 
				html_print_radio_button_extended('type_percentile', 'percentile', ('Percentile'), 'percentile', false, '', 'style="float: left;"', true) . 
				html_print_radio_button_extended('type_percentile', 'bubble', ('Bubble'), 'percentile', false, '', 'style="float: left;"', true) . 
				'</td>';
			
			$form_items['percentile_item_row_4'] = array();
			$form_items['percentile_item_row_4']['items'] = array('percentile_bar', 'percentile_item', 'datos');
			$form_items['percentile_item_row_4']['html'] = '<td>' . __('Value to show') . '</td>
				<td>' . 
				html_print_radio_button_extended('value_show', 'percent', ('Percent'), 'value', false, '', 'style="float: left;"', true) .
				html_print_radio_button_extended('value_show', 'value', ('Value'), 'value', false, '', 'style="float: left;"', true) . 
				'</td>';
			
			$form_items['period_row'] = array();
			$form_items['period_row']['items'] = array('module_graph', 'simple_value', 'datos');
			$form_items['period_row']['html'] = '<td>' . __('Period') . '</td>
				<td>' .  html_print_extended_select_for_time ('period', '', '', '', '', false, true) . '</td>';
			
			$form_items['module_graph_size_row'] = array();
			$form_items['module_graph_size_row']['items'] = array('module_graph', 'datos');
			$form_items['module_graph_size_row']['html'] = '<td>' . __('Size') . '</td>
				<td>' .
				html_print_input_text('width_module_graph', 300, '', 3, 5, true) . 
				' X ' .
				html_print_input_text('height_module_graph', 180, '', 3, 5, true) .
				'</td>';
			
			//Insert and modify before the buttons to create or update.
			if (enterprise_installed()) {
				enterprise_visual_map_editor_modify_form_items_palette($form_items);
			}
			
			$form_items['button_update_row'] = array();
			$form_items['button_update_row']['items'] = array('datos');
			$form_items['button_update_row']['html'] = '<td colspan="2" style="text-align: right;">' .
				html_print_button(__('Cancel'), 'cancel_button', false, 'cancel_button_palette_callback();', 'class="sub cancel"', true) .
				html_print_button(__('Update'), 'update_button', false, 'update_button_palette_callback();', 'class="sub upd"', true) .
				'</td>';
			
			$form_items['button_create_row'] = array();
			$form_items['button_create_row']['items'] = array('datos');
			$form_items['button_create_row']['html'] = '<td colspan="2" style="text-align: right;">' .
				html_print_button(__('Cancel'), 'cancel_button', false, 'cancel_button_palette_callback();', 'class="sub cancel"', true) . 
				html_print_button(__('Create'), 'create_button', false, 'create_button_palette_callback();', 'class="sub wand"', true) . 
				'</td>';
			
			foreach ($form_items as $item => $item_options) {
				echo '<tr id="' . $item . '" style="" class="' . implode(' ', $item_options['items']) . '">';
				echo $item_options['html'];
				echo '</tr>';
			}
			?>
			<tr id="advance_options_link" class="datos">
				<td colspan="2" style="text-align: center;">
					<a href="javascript: toggle_advance_options_palette()"><?php echo __('Advanced options');?></a>
				</td>
			</tr>
		</tbody>
		<tbody id="advance_options" style="display: none;">
			<?php
			$form_items_advance = array();
			
			$form_items_advance['position_row'] = array();
			$form_items_advance['position_row']['items'] = array('static_graph',
				'percentile_bar', 'percentile_item', 'module_graph',
				'simple_value', 'label', 'icon', 'datos');
			$form_items_advance['position_row']['html'] = '
				<td>' . __('Position') . '</td>
				<td>(' . html_print_input_text('left', '0', '', 3, 5, true) .
				' , ' .
				html_print_input_text('top', '0', '', 3, 5, true) . 
				')</td>';
			
			$form_items_advance['size_row'] = array();
			$form_items_advance['size_row']['items'] = array('background',
				'static_graph', 'icon datos');
			$form_items_advance['size_row']['html'] = '<td>' .
				__('Size') . '<a href="#" class="tip">&nbsp;<span>' .
				__("For use the original image file size, set 0 width and 0 height.") .
				'</span></a>' . '</td>
				<td>' . html_print_input_text('width', 0, '', 3, 5, true) .
				' X ' .
				html_print_input_text('height', 0, '', 3, 5, true) .
				'</td>';
			
			$parents = visual_map_get_items_parents($visualConsole_id);
			
			$form_items_advance['parent_row'] = array();
			$form_items_advance['parent_row']['items'] = array('static_graph',
				'percentile_bar', 'percentile_item', 'module_graph',
				'simple_value', 'label', 'icon', 'datos');
			$form_items_advance['parent_row']['html'] = '<td>' .
				__('Parent') . '</td>
				<td>' .
				html_print_select($parents, 'parent', '', '', __('None'), 0, true) .
				'</td>';
			
			$form_items_advance['map_linked_row'] = array();
			$form_items_advance['map_linked_row']['items'] = array(
				'static_graph', 'percentile_bar', 'percentile_item',
				'module_graph', 'simple_value', 'icon', 'label', 'datos');
			$form_items_advance['map_linked_row']['html'] = '<td>'.
				__('Map linked') . '</td>' .
				'<td>' . html_print_select_from_sql (
				'SELECT id, name
				FROM tlayout
				WHERE id != ' . $visualConsole_id, 'map_linked', '', '', 'None', '0', true) .
				'</td>';
			
			$form_items_advance['label_color_row'] = array();
			$form_items_advance['label_color_row']['items'] = array(
				'static_graph', 'percentile_bar', 'percentile_item',
				'module_graph', 'simple_value', 'label', 'datos');
			$form_items_advance['label_color_row']['html'] = '<td>' .
				__('Label color') . '</td>
				<td>' . html_print_input_text_extended ('label_color', '#000000', 'text-'.'label_color', '', 7, 7, false, '', 'class="label_color"', true) .
				'</td>';
			
			//Insert and modify before the buttons to create or update.
			if (enterprise_installed()) {
				enterprise_visual_map_editor_modify_form_items_advance_palette($form_items_advance);
			}
			
			foreach ($form_items_advance as $item => $item_options) {
				echo '<tr id="' . $item . '" style="" class="' . implode(' ', $item_options['items']) . '">';
				echo $item_options['html'];
				echo '</tr>';
			}
			?>
		</tbody>
	</table>
	<?php
	//------------------------------------------------------------------------------
	echo '</div>';
}

function visual_map_editor_print_toolbox() {
	global $config;
	
	echo '<div id="editor" style="margin-top: -10px;">';
	echo '<div id="toolbox">';
		visual_map_print_button_editor('static_graph', __('Static Graph'), 'left', false, 'camera_min', true);
		visual_map_print_button_editor('percentile_item', __('Percentile Item'), 'left', false, 'percentile_item_min', true);
		visual_map_print_button_editor('module_graph', __('Module Graph'), 'left', false, 'graph_min', true);
		visual_map_print_button_editor('simple_value', __('Simple Value'), 'left', false, 'binary_min', true);
		visual_map_print_button_editor('label', __('Label'), 'left', false, 'label_min', true);
		visual_map_print_button_editor('icon', __('Icon'), 'left', false, 'icon_min', true);
		
		enterprise_hook("enterprise_visual_map_editor_print_toolbox");
		
		visual_map_print_button_editor('save', __('Save'), 'right', true, 'save_min', true);
		$text_autosave = __('Auto Save') . html_print_checkbox('auto_save', 0, true, true, false, "click_button_toolbox('auto_save');");
		visual_map_print_item_toolbox('auto_save', $text_autosave, 'right');
		visual_map_print_button_editor('show_grid', __('Show grid'), 'right', true, 'grid_min', true);
		visual_map_print_button_editor('edit_item', __('Update item'), 'right', true, 'config_min', true);
		visual_map_print_button_editor('delete_item', __('Delete item'), 'right', true, 'delete_min', true);
	echo '</div>';
	echo '</div>';
	echo '<div style="clear: right; margin-bottom: 10px;"></div>';
}

function visual_map_print_button_editor($idDiv, $label, $float = 'left', $disabled = false, $class= '', $imageButton = false) {
	if ($float == 'left') {
		$margin = 'margin-right';
	}
	else {
		$margin = 'margin-left';
	}
	
	html_print_button($label, 'button_toolbox2', $disabled,
		"click_button_toolbox('" . $idDiv . "');",
		'class="sub visual_editor_button_toolbox ' . $idDiv . ' ' . $class . '" style="float: ' . $float . ';"', false, $imageButton);
}

function visual_map_editor_print_hack_translate_strings() {
	//Trick for it have a traduct text for javascript.
	echo '<span id="any_text" style="display: none;">' . __('Any') . '</span>';
	echo '<span id="ip_text" style="display: none;">' . __('IP') . '</span>';
	
	//Hack to translate messages in javascript
	echo '<span style="display: none" id="message_alert_no_label_no_image">' . __('No image or name defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_label">' . __('No label defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_image">' . __('No image defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_process">' . __('No process defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_max_percentile">' . __('No Max value defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_width_percentile">' . __('No width defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_period">' . __('No period defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_agent">' . __('No agent defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_module">' . __('No module defined.') .'</span>';
	
	echo '<span style="display: none" id="hack_translation_correct_save">' . __('Successfully save the changes.') .'</span>';
	echo '<span style="display: none" id="hack_translation_incorrect_save">' . __('Could not be save') .'</span>';
}
?>