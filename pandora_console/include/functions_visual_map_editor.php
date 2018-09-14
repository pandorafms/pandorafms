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
	$all_images = list_files ($config['homedir'] . '/images/console/icons/', "png", 1, 0);
	
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
	$backgrounds_list = list_files($config['homedir'] . '/images/console/background/', "jpg", 1, 0);
	$backgrounds_list = array_merge($backgrounds_list, list_files ($config['homedir'] . '/images/console/background/', "png", 1, 0));
	
	
	
	echo '<div id="properties_panel" style="display: none; position: absolute; border: 1px solid lightgray; padding: 5px; background: white; z-index: 90;">';
	//----------------------------Hiden Form----------------------------
	?>
	<table class="databox filters" border="0" cellpadding="4" cellspacing="4" width="350">
		<caption>
			<?php
			$titles = array(
				'background' => __('Background'),
				'static_graph' => __('Static Graph'),
				'percentile_item' => __('Percentile Item'),
				'module_graph' => __('Graph'),
				'auto_sla_graph' => __('Auto SLA Graph'),
				'simple_value' => __('Simple value') . ui_print_help_tip(__("To use 'label'field, you should write
					a text to replace '(_VALUE_)' and the value of the module will be printed at the end."), true),
				'label' => __('Label'),
				'icon' => __('Icon'),
				'clock' => __('Clock'),
				'group_item' => __('Group'),
				'box_item' => __('Box'),
				'line_item' => __('Line'));
			
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
			
			$form_items['line_width_row'] = array();
			$form_items['line_width_row']['items'] =
				array('datos', 'line_item', 'handler_start', 'handler_end');
			$form_items['line_width_row']['html'] = '<td align="left">' . __('Width') . '</td>
				<td align="left">' .
				html_print_input_text('line_width', 3, '', 3, 5, true) .
				'</td>';
			
			
			$form_items['line_color_row'] = array();
			$form_items['line_color_row']['items'] =
				array('datos', 'line_item', 'handler_start', 'handler_end');
			$form_items['line_color_row']['html'] = 
				'<td align="left" valign="top" style="">' .
					__('Border color') .
				'</td>' .
				'<td align="left" style="">' .
					html_print_input_text_extended ('line_color',
						'#000000', 'text-line_color', '', 7, 7, false,
						'', 'class="line_color"', true) .
				'</td>';
			
			
			$form_items['box_size_row'] = array();
			$form_items['box_size_row']['items'] = array('datos', 'box_item');
			$form_items['box_size_row']['html'] =
				'<td align="left">' . __('Size') . '</td>
				<td align="left">' .
				html_print_input_text('width_box', 300, '', 3, 5, true) . 
				' X ' .
				html_print_input_text('height_box', 180, '', 3, 5, true) .
				'</td>';
			
			
			$form_items['border_color_row'] = array();
			$form_items['border_color_row']['items'] = array('datos', 'box_item');
			$form_items['border_color_row']['html'] = 
				'<td align="left" valign="top" style="">' .
					__('Border color') .
				'</td>' .
				'<td align="left" style="">' .
					html_print_input_text_extended ('border_color',
						'#000000', 'text-border_color', '', 7, 7, false,
						'', 'class="border_color"', true) .
				'</td>';
			
			
			$form_items['border_width_row'] = array();
			$form_items['border_width_row']['items'] = array('datos', 'box_item');
			$form_items['border_width_row']['html'] =
				'<td align="left">' . __('Border width') . '</td>
				<td align="left">' .
				html_print_input_text('border_width', 3, '', 3, 5, true) . 
				'</td>';
			
			
			$form_items['fill_color_row'] = array();
			$form_items['fill_color_row']['items'] = array('datos', 'box_item','clock');
			$form_items['fill_color_row']['html'] = 
				'<td align="left" valign="top" style="">' . __('Fill color') . '</td>' .
				'<td align="left" style="">' .
				html_print_input_text_extended ('fill_color', '#000000',
					'text-fill_color', '', 7, 7, false, '',
					'class="fill_color"', true) .
				'</td>';
			
			$form_items['module_graph_size_row'] = array();
			$form_items['module_graph_size_row']['items'] = array('module_graph', 'datos');
			$form_items['module_graph_size_row']['html'] = '<td align="left">' . __('Size') . '</td>
				<td align="left">' .
				html_print_input_text('width_module_graph', 300, '', 3, 5, true) . 
				' X ' .
				html_print_input_text('height_module_graph', 180, '', 3, 5, true) .
				'</td>';
			
			$form_items['label_row'] = array();
			$form_items['label_row']['items'] = array('label',
				'static_graph',
				'module_graph',
				'simple_value',
				'datos',
				'group_item',
				'auto_sla_graph',
				'bars_graph',
				'clock');
			$form_items['label_row']['html'] =
				'<td align="left" valign="top" style="">' . __('Label') . ui_print_help_icon ('macros_visual_maps', true) . '
				
				<div id="label_box_arrow" style="text-align:center;width:120px;height:110px;margin-top:50px;">
					<span>Label position</span>
					<div class="labelpos" id="labelposup" position="up" style="width:20px;height:20px;margin-top:10px;margin-left:45px;cursor: pointer;">
						'. html_print_image ('images/label_up.png', true, array('style'=>'height:100%;width:100%;')). '
					</div>
					<div class="labelpos" id="labelposleft" position="left" style="position:relative;top:-5px;width:20px;height:20px;margin-top:15px;cursor: pointer;">
						'. html_print_image ('images/label_left.png', true, array('style'=>'height:100%;width:100%;')). '
					</div>
					<div style="font-weight:bold;width:40px;height:20px;position:relative;margin-left:35px;margin-top:-24px;cursor: default;">
						<span style="float:left;margin-top:3px;margin-left:5px;">Object</span>
					</div>
					<div class="labelpos" id="labelposright" position="right" style="top:2px;width:20px;height:20px;position:relative;margin-left:90px;margin-top:-24px;cursor: pointer;">
						'. html_print_image ('images/label_right.png', true, array('style'=>'height:100%;width:100%;')). '
					</div>
					<div class="labelpos" sel="yes" id="labelposdown" position="down" style="width:20px;height:20px;position:relative;margin-left:45px;margin-top:10px;cursor: pointer;">
						'. html_print_image ('images/label_down_2.png', true, array('style'=>'height:100%;width:100%;')). '
					</div>
				</div>
				</td>
				<td align="left" style="">' .
				html_print_input_text('label', '', '', 20, 200, true) . '
				<span id="advice_label" style="font-style:italic;z-index:3;display:inline;margin-top:0px;float:right;margin-right:100px;">
				'.__("Scroll the mouse wheel over the label editor to change the background color").'
				</span>
				</td>';
			
			
			$form_items['image_row'] = array();
			$form_items['image_row']['items'] = array('static_graph',
				'icon',
				'datos',
				'group_item');
			$form_items['image_row']['html'] =
				'<td align="left">' . __('Image') . '</td>
				<td align="left">' .
				html_print_select ($images_list, 'image', '', 'showPreview(this.value);', 'None', 'none', true) .
				'</td>';
				
			$form_items['clock_animation_row'] = array();
			$form_items['clock_animation_row']['items'] =	array('clock');
			$form_items['clock_animation_row']['html'] = '<td align="left"><span>' .
				__('Clock animation') . '</span></td>
				<td align="left">'. html_print_select (
					array ('analogic_1' => __('Simple analogic'), 
					'digital_1' => __('Simple digital')),
					'clock_animation', '', '', 0, 'analogic_1', true, false, false) . '</td>';
				
			$form_items['timeformat_row'] = array();
			$form_items['timeformat_row']['items'] =	array('clock');
			$form_items['timeformat_row']['html'] = '<td align="left"><span>' .
				__('Time format') . '</span></td>
				<td align="left">'. html_print_select (
					array ('time' => __('Only time'),
					'timedate' => __('Time and date')),
					'time_format', '', '', 0, 'time', true, false, false) . '</td>';
			
			$zone_name = array('Africa' => __('Africa'), 'America' => __('America'), 'Antarctica' => __('Antarctica'), 'Arctic' => __('Arctic'), 'Asia' => __('Asia'), 'Atlantic' => __('Atlantic'), 'Australia' => __('Australia'), 'Europe' => __('Europe'), 'Indian' => __('Indian'), 'Pacific' => __('Pacific'), 'UTC' => __('UTC'));
			$zone_selected = 'Europe';
			
			$timezones = timezone_identifiers_list();
			foreach ($timezones as $timezone) {
				if (strpos($timezone, $zone_selected) !== false) { 
					$timezone_n[$timezone] = $timezone;
				}
			}

			
			$form_items['timezone_row'] = array();
			$form_items['timezone_row']['items'] =	array('clock');
			$form_items['timezone_row']['html'] = '<td align="left"><span>' .
				__('Time zone') . '</span></td>
				<td align="left">'.
				html_print_select($zone_name, 'zone', $zone_selected, 'show_timezone();', '', '', true).
				"&nbsp;&nbsp;". html_print_select($timezone_n, 'timezone','', '', '', '', true).
				'</td>';
			
			
			
			$form_items['enable_link_row'] = array();
			$form_items['enable_link_row']['items'] = array(
				'static_graph',
				'percentile_bar',
				'percentile_item',
				'module_graph',
				'simple_value',
				'datos',
				'icon',
				'bars_graph',
				'group_item');
				
				
			$form_items['enable_link_row']['html'] =
				'<td align="left" style="">' . __('Enable link') . '</td>
				<td align="left" style="">' .
				html_print_checkbox('enable_link', '', !is_metaconsole(), true) . '</td>';
			
			
			$form_items['preview_row'] = array();
			$form_items['preview_row']['items'] = array('static_graph',
				'datos',
				'icon',
				'group_item');
			$form_items['preview_row']['html'] =
				'<td align="left" colspan="2" style="text-align: right;">' .
				'<div id="preview" style="text-align: right;"></div></td>';
			
			$form_items['background_color'] = array();
			$form_items['background_color']['items'] = array(
				'module_graph',
				'datos',
				'bars_graph');
			$form_items['background_color']['html'] = '<td align="left"><span>' .
				__('Background color') . '</span></td>
				<td align="left">'. html_print_select (
					array ('white' => __('White'), 
					'black' => __('Black'),
					'transparent' => __('Transparent')),
					'background_color', '', '', 0, 'white', true, false, false) . '</td>';

			$form_items['grid_color_row'] = array();
			$form_items['grid_color_row']['items'] = array('bars_graph');
			$form_items['grid_color_row']['html'] = 
				'<td align="left" valign="top" style="">' .
					__('Grid color') .
				'</td>' .
				'<td align="left" style="">' .
					html_print_input_text_extended ('grid_color',
						'#000000', 'text-grid_color', '', 7, 7, false,
						'', 'class="grid_color"', true) .
				'</td>';
					
			$form_items['radio_choice_graph'] = array();
			$form_items['radio_choice_graph']['items'] = array(
				'module_graph',
				'datos');
			$form_items['radio_choice_graph']['html'] =
				'<td align="left" style=""></td>
				<td align="left" style="">'
				. __('Module graph') . "&nbsp;&nbsp;" .
				html_print_radio_button('radio_choice', 'module_graph', '', 'module_graph', true)
				. "&nbsp;&nbsp;&nbsp;&nbsp;"
				. __('Custom graph') . "&nbsp;&nbsp;" .
				html_print_radio_button('radio_choice', 'custom_graph', '', 'module_graph', true) .
				'</td>';
			
			
			$form_items['custom_graph_row'] = array();
			$form_items['custom_graph_row']['items'] = array(
				'module_graph',
				'datos');
			$form_items['custom_graph_row']['html'] =
				'<td align="left" style="">' . __('Custom graph') . '</td>
				<td align="left" style="">' .
				html_print_select_from_sql(
					"SELECT id_graph, name FROM tgraph", 'custom_graph',
					'', '', __('None'), 0, true) .
				'</td>';
			
			
			$form_items['agent_row'] = array();
			$form_items['agent_row']['items'] = array('static_graph',
				'percentile_bar', 'percentile_item', 'module_graph',
				'simple_value', 'datos', 'auto_sla_graph');
			$form_items['agent_row']['html'] = '<td align="left">' .
				__('Agent') . '</td>';			
			$params = array();
			$params['return'] = true;
			$params['show_helptip'] = true;
			$params['input_name'] = 'agent';
			$params['size'] = 30;
			$params['selectbox_id'] = 'module';
			$params['javascript_is_function_select'] = true;
			$params['use_hidden_input_idagent'] = true;
			$params['print_hidden_input_idagent'] = true;
			$params['hidden_input_idagent_name'] = 'id_agent';
			$params['get_order_json'] = true;
			if (defined('METACONSOLE')) {
				$params['javascript_ajax_page'] = '../../ajax.php';
				$params['disabled_javascript_on_blur_function'] = true;
				
				$params['print_input_server'] = true;
				$params['print_input_id_server'] = true;
				$params['input_server_id'] = 'id_server_name';
				$params['input_id_server_name'] = 'id_server_metaconsole';
				$params['input_server_value'] = '';
				$params['use_input_id_server'] = true;
				$params['metaconsole_enabled'] = true;
				$params['print_hidden_input_idagent'] = true;
			}
			$form_items['agent_row']['html'] .= '<td align="left">' .
					ui_print_agent_autocomplete_input($params) .
				'</td>';

			$form_items['agent_row_string'] = array();
			$form_items['agent_row_string']['items'] = array('donut_graph', 'bars_graph');
			$form_items['agent_row_string']['html'] = '<td align="left">' .
				__('Agent') . '</td>';			
			$params = array();
			$params['return'] = true;
			$params['show_helptip'] = true;
			$params['input_name'] = 'agent_string';
			$params['size'] = 30;
			$params['selectbox_id'] = 'module';
			$params['javascript_is_function_select'] = true;
			$params['use_hidden_input_idagent'] = true;
			$params['print_hidden_input_idagent'] = true;
			$params['hidden_input_idagent_name'] = 'id_agent_string';
			$params['get_order_json'] = true;
			$params['get_only_string_modules'] = true;
			if (defined('METACONSOLE')) {
				$params['javascript_ajax_page'] = '../../ajax.php';
				$params['disabled_javascript_on_blur_function'] = true;
				
				$params['print_input_server'] = true;
				$params['print_input_id_server'] = true;
				$params['input_server_id'] = 'id_server_name';
				$params['input_id_server_name'] = 'id_server_metaconsole';
				$params['input_server_value'] = '';
				$params['use_input_id_server'] = true;
				$params['metaconsole_enabled'] = true;
				$params['print_hidden_input_idagent'] = true;
			}
			$form_items['agent_row_string']['html'] .= '<td align="left">' .
					ui_print_agent_autocomplete_input($params) .
				'</td>';
			
			$form_items['module_row'] = array();
			$form_items['module_row']['items'] = array('static_graph',
				'percentile_bar', 'percentile_item', 'module_graph',
				'simple_value', 'datos', 'auto_sla_graph', 'donut_graph', 'bars_graph');
			$form_items['module_row']['html'] = '<td align="left">' .
				__('Module') . '</td>
				<td align="left">' .
				html_print_select(array(), 'module', '', '', __('Any'), 0, true) . '<div id="data_image_container" style="display:none;"><span id="data_image_check_label" style="margin-left:20px;">'.__("Data image").': </span><span id="data_image_check">Off</span><span id="data_image_width_label"> - Width: </span><input style="margin-left:5px;width:40px;" type="number" min="0" id="data_image_width" value="100"></input></div>
				</td>';

			$form_items['resume_color_row'] = array();
			$form_items['resume_color_row']['items'] = array('donut_graph');
			$form_items['resume_color_row']['html'] = 
				'<td align="left" valign="top" style="">' .
					__('Resume data color') .
				'</td>' .
				'<td align="left" style="">' .
					html_print_input_text_extended ('resume_color',
						'#000000', 'text-resume_color', '', 7, 7, false,
						'', 'class="resume_color"', true) .
				'</td>';

			$event_times = array(86400 => __('24h'),
								28800 => __('8h'),
								7200 => __('2h'),
								3600 => __('1h'),);
			$form_items['event_max_time_row'] = array();
			$form_items['event_max_time_row']['items'] = array('auto_sla_graph');
			$form_items['event_max_time_row']['html'] = '<td align="left">' .
				__('Max. Time') . '</td>
				<td align="left">' .
				html_print_select($event_times, 'event_max_time_row', '', '', 0, 86400, true, false, false) .
				'</td>';
				
			$form_items['type_graph'] = array();
			$form_items['type_graph']['items'] = array(
				'Line',
				'Area');
			$form_items['type_graph']['html'] = '<td align="left"><span>' .
				__('Type of graph') . '</span></td>
				<td align="left">'. html_print_select (
					array ('line' => __('Line'), 
					'area' => __('Area')),
					'type_graph', '', '', 0, 'area', true, false, false) . '</td>';
			
			$own_info = get_user_info($config['id_user']);
			if (!$own_info['is_admin'] && !check_acl ($config['id_user'], 0, "PM"))
				$return_all_group = false;
			else
				$return_all_group = true;
			$form_items['group_row'] = array();
			$form_items['group_row']['items'] = array('group_item', 'datos');
			$form_items['group_row']['html'] = '<td align="left">' .
					__('Group') .
				'</td>
				<td align="left">' .
					html_print_select_groups(false, "AR",
						$return_all_group, 'group', '', '', '', 0,
						true) .
				'</td>';
			
			
			$form_items['process_value_row'] = array();
			$form_items['process_value_row']['items'] = array('simple_value', 'datos');
			$form_items['process_value_row']['html'] = '<td align="left"><span>' .
				__('Process') . '</span></td>
				<td align="left">'. html_print_select (
					array (PROCESS_VALUE_MIN => __('Min value'), 
					PROCESS_VALUE_MAX => __('Max value'),
					PROCESS_VALUE_AVG => __('Avg value')),
					'process_value', '', '', __('None'), PROCESS_VALUE_NONE, true) . '</td>';
			
			
			$form_items['background_row_1'] = array();
			$form_items['background_row_1']['items'] = array('background', 'datos');
			$form_items['background_row_1']['html'] = '<td align="left">' .
				__('Background') . '</td>
				<td align="left">' . html_print_select($backgrounds_list, 'background_image', $background, '', 'None', '', true) . '</td>';
			
			
			$form_items['background_row_2'] = array();
			$form_items['background_row_2']['items'] = array('background', 'datos');
			$form_items['background_row_2']['html'] = '<td align="left">' .
				__('Original Size') . '</td>
				<td align="left">' . html_print_button(__('Apply'), 'original_false', false, "setAspectRatioBackground('original')", 'class="sub"', true) . '</td>';
			
			
			$form_items['background_row_3'] = array();
			$form_items['background_row_3']['items'] = array('background', 'datos');
			$form_items['background_row_3']['html'] = '<td align="left">' .
				__('Aspect ratio') . '</td>
				<td align="left">' . html_print_button(__('Width proportional'), 'original_false', false, "setAspectRatioBackground('width')", 'class="sub"', true) . '</td>';
			
			
			$form_items['background_row_4'] = array();
			$form_items['background_row_4']['items'] = array('background', 'datos');
			$form_items['background_row_4']['html'] = '<td align="left"></td>
				<td align="left">' . html_print_button(__('Height proportional'), 'original_false', false, "setAspectRatioBackground('height')", 'class="sub"', true) . '</td>';
			
			
			$form_items['percentile_bar_row_1'] = array();
			$form_items['percentile_bar_row_1']['items'] = array('percentile_bar', 'percentile_item', 'datos', 'donut_graph', 'bars_graph','clock');
			$form_items['percentile_bar_row_1']['html'] = '<td align="left">' .
				__('Width') . '</td>
				<td align="left">' . html_print_input_text('width_percentile', 0, '', 3, 5, true) . '</td>';

			$form_items['height_bars_graph_row'] = array();
			$form_items['height_bars_graph_row']['items'] = array('bars_graph');
			$form_items['height_bars_graph_row']['html'] = '<td align="left">' .
				__('Height') . '</td>
				<td align="left">' . html_print_input_text('bars_graph_height', 0, '', 3, 5, true) . '</td>';
			
			$form_items['percentile_bar_row_2'] = array();
			$form_items['percentile_bar_row_2']['items'] = array('percentile_bar', 'percentile_item', 'datos');
			$form_items['percentile_bar_row_2']['html'] = '<td align="left">' .
				__('Max value') . '</td>
				<td align="left">' . html_print_input_text('max_percentile', 0, '', 3, 5, true) . '</td>';

			$percentile_type = array('percentile' => __('Percentile'), 'bubble' => __('Bubble'), 'circular_progress_bar' => __('Circular porgress bar'), 'interior_circular_progress_bar' => __('Circular progress bar (interior)'));
			$percentile_value = array('percent' => __('Percent'), 'value' => __('Value'));
			if (is_metaconsole()){
				$form_items['percentile_item_row_3'] = array();
				$form_items['percentile_item_row_3']['items'] = array('percentile_bar', 'percentile_item', 'datos');
				$form_items['percentile_item_row_3']['html'] = '<td align="left">' .
					__('Type') . '</td>
					<td align="left">' .
					html_print_select($percentile_type, 'type_percentile', 'percentile', '', '', '', true, false, false, '', false, 'style="float: left;"') .
					'</td>';

				$form_items['percentile_item_row_4'] = array();
				$form_items['percentile_item_row_4']['items'] = array('percentile_bar', 'percentile_item', 'datos');
				$form_items['percentile_item_row_4']['html'] = '<td align="left">' . __('Value to show') . '</td>
					<td align="left">' .
					html_print_select($percentile_value, 'value_show', 'percent', '', '', '', true, false, false, '', false, 'style="float: left;"') .
					'</td>';
			}
			else{
				$form_items['percentile_item_row_3'] = array();
				$form_items['percentile_item_row_3']['items'] = array('percentile_bar', 'percentile_item', 'datos');
				$form_items['percentile_item_row_3']['html'] = '<td align="left">' .
					__('Type') . '</td>
					<td align="left">' .
					html_print_select($percentile_type, 'type_percentile', 'percentile', '', '', '', true) .
					'</td>';

				$form_items['percentile_item_row_4'] = array();
				$form_items['percentile_item_row_4']['items'] = array('percentile_bar', 'percentile_item', 'datos');
				$form_items['percentile_item_row_4']['html'] = '<td align="left">' . __('Value to show') . '</td>
					<td align="left">' .
					html_print_select($percentile_value, 'value_show', 'percent', '', '', '', true) .
					'</td>';
			}

			$form_items['percentile_item_row_5'] = array();
			$form_items['percentile_item_row_5']['items'] = array('percentile_bar', 'percentile_item', 'datos');
			$form_items['percentile_item_row_5']['html'] = '<td align="left">' . __('Element color') . '</td>
				<td align="left">' .
				html_print_input_text_extended ('percentile_color', '#ffffff',
					'text-percentile_color', '', 7, 7, false, '',
					'class="percentile_color"', true) .
				'</td>';

			$form_items['percentile_item_row_6'] = array();
			$form_items['percentile_item_row_6']['items'] = array('percentile_bar', 'percentile_item', 'datos');
			$form_items['percentile_item_row_6']['html'] = '<td align="left">' . __('Label color') . '</td>
				<td align="left">' .
				html_print_input_text_extended ('percentile_label_color', '#ffffff',
					'text-percentile_label_color', '', 7, 7, false, '',
					'class="percentile_label_color"', true) .
				'</td>';

			$form_items['percentile_bar_row_7'] = array();
			$form_items['percentile_bar_row_7']['items'] = array('percentile_bar', 'percentile_item', 'datos');
			$form_items['percentile_bar_row_7']['html'] = '<td align="left">' .
				__('Label') . '</td>
				<td align="left">' . html_print_input_text('percentile_label', '', '', 30, 100, true) . '</td>';

			$form_items['period_row'] = array();
			$form_items['period_row']['items'] = array('module_graph', 'simple_value', 'datos');
			$form_items['period_row']['html'] = '<td align="left">' . __('Period') . '</td>
				<td align="left">' .  html_print_extended_select_for_time ('period', SECONDS_5MINUTES, '', '', '', false, true) . '</td>';
			
			$form_items['show_statistics_row'] = array();
			$form_items['show_statistics_row']['items'] = array('group_item');
			$form_items['show_statistics_row']['html'] = 
				'<td align="left" style="">' . __('Show statistics') . '</td>
				<td align="left" style="">' .
				html_print_checkbox('show_statistics', 1, '', true) . '</td>';
				
			$form_items['show_on_top_row'] = array();
			$form_items['show_on_top_row']['items'] = array('group_item');
			$form_items['show_on_top_row']['html'] = 
				'<td align="left" style="">' . __('Always show on top') . '</td>
				<td align="left" style="">' .
				html_print_checkbox('show_on_top', 1, '', true) . '</td>';
			
			$form_items['module_graph_size_row'] = array();
			$form_items['module_graph_size_row']['items'] = array('module_graph', 'datos');
			$form_items['module_graph_size_row']['html'] = '<td align="left">' . __('Size') . '</td>
				<td align="left">' .
				html_print_input_text('width_module_graph', 300, '', 3, 5, true) . 
				' X ' .
				html_print_input_text('height_module_graph', 180, '', 3, 5, true) .
				' X ' .
				'<span id="count_items">1</span> '.
				'<span id="dir_items"></span> item/s				
				</td>';

			$bars_graph_types = array('vertical' => __('Vertical'), 'horizontal' => __('Horizontal'));
			$form_items['bars_graph_type'] = array();
			$form_items['bars_graph_type']['items'] = array('bars_graph');
			$form_items['bars_graph_type']['html'] = '<td align="left">' .
				__('Type') . '</td>
				<td align="left">' . html_print_select($bars_graph_types, 'bars_graph_type', 'vertical', '', '', '', true) . '</td>';
			
			
			//Insert and modify before the buttons to create or update.
			if (enterprise_installed()) {
				enterprise_visual_map_editor_modify_form_items_palette($form_items);
			}
			
			
			$form_items['button_update_row'] = array();
			$form_items['button_update_row']['items'] = array('datos');
			$form_items['button_update_row']['html'] = '<td align="left" colspan="2" style="text-align: right;">' .
				html_print_button(__('Cancel'), 'cancel_button', false, 'cancel_button_palette_callback();', 'class="sub cancel"', true) . '<span ="margin-right:10px;">&nbsp</span>' .
				html_print_button(__('Update'), 'update_button', false, 'update_button_palette_callback();', 'class="sub upd"', true) .
				'</td>';
			
			
			$form_items['button_create_row'] = array();
			$form_items['button_create_row']['items'] = array('datos');
			$form_items['button_create_row']['html'] = '<td align="left" colspan="2" style="text-align: right;">' .
				html_print_button(__('Cancel'), 'cancel_button', false, 'cancel_button_palette_callback();', 'class="sub cancel"', true)  . '<span ="margin-right:10px;">&nbsp</span>' .
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
					<a href="javascript: toggle_advance_options_palette()">
						<?php echo __('Advanced options');?>
					</a>
				</td>
			</tr>
		</tbody>
		<tbody id="advance_options" style="display: none;">
			<?php
			$form_items_advance = array();
			
			$form_items_advance['position_row'] = array();
			$form_items_advance['position_row']['items'] = array('static_graph',
				'percentile_bar', 'percentile_item', 'module_graph',
				'simple_value', 'label', 'icon', 'datos', 'box_item',
				'auto_sla_graph', 'bars_graph','clock', 'donut_graph');
			$form_items_advance['position_row']['html'] = '
				<td align="left">' . __('Position') . '</td>
				<td align="left">(' . html_print_input_text('left', '0', '', 3, 5, true) .
				' , ' .
				html_print_input_text('top', '0', '', 3, 5, true) . 
				')</td>';
			
			$form_items_advance['size_row'] = array();
			$form_items_advance['size_row']['items'] = array(
				'group_item', 'background',
				'static_graph', 'icon datos',
				'auto_sla_graph');
			$form_items_advance['size_row']['html'] = '<td align="left">' .
				__('Size') .
				ui_print_help_tip (
					__("For use the original image file size, set 0 width and 0 height."), true) .
				'</td>
				<td align="left">' . html_print_input_text('width', 0, '', 3, 5, true) .
				' X ' .
				html_print_input_text('height', 0, '', 3, 5, true) .
				'</td>';
			
			$parents = visual_map_get_items_parents($visualConsole_id);
			
			$form_items_advance['parent_row'] = array();
			$form_items_advance['parent_row']['items'] = array(
				'group_item', 'static_graph',
				'percentile_bar', 'percentile_item', 'module_graph',
				'simple_value', 'label', 'icon', 'datos', 'auto_sla_graph',
				'bars_graph', 'donut_graph');
			$form_items_advance['parent_row']['html'] = '<td align="left">' .
				__('Parent') . '</td>
				<td align="left">' .
				html_print_input_hidden('parents_load', base64_encode(json_encode($parents)), true) .
				html_print_select($parents, 'parent', '', '', __('None'), 0, true) .
				'</td>';
			
			$form_items_advance['map_linked_row'] = array();
			$form_items_advance['map_linked_row']['items'] = array(
				'group_item', 'static_graph', 'percentile_bar',
				'percentile_item', 'module_graph', 'simple_value',
				'icon', 'label', 'datos', 'donut_graph');
			$visual_maps = db_get_all_rows_filter("tlayout", "id != " . (int) $visualConsole_id, array("id", "name"));

			$form_items_advance['map_linked_row']['html'] = '<td align="left">'
				. __('Linked map')
				. '</td>'
				. '<td align="left">';

			if (is_metaconsole()) {
				$meta_servers = metaconsole_get_servers();
				foreach ($meta_servers as $server) {
					if (metaconsole_load_external_db($server) !== NOERR) {
						metaconsole_restore_db();
						continue;
					}

					$node_visual_maps = db_get_all_rows_filter("tlayout", array(), array("id", "name"));

					foreach ($node_visual_maps as $node_visual_map) {
						$node_visual_map["node_id"] = (int) $server["id"];
						$visual_maps[] = $node_visual_map;
					}

					metaconsole_restore_db();
				}

				$meta_servers_by_id = array_reduce($meta_servers, function ($arr, $item) {
					$arr[$item["id"]] = $item;
					return $arr;
				}, array());

				$form_items_advance['map_linked_row']['html'] .= html_print_select_from_sql(
					array(), 'map_linked', 0, 'onLinkedMapChange(event)', __('None'), 0, true
				);
				$form_items_advance['map_linked_row']['html'] .= html_print_input_hidden(
					"linked_map_node_id", 0, true
				);

				ob_start();
				?>
				<script type="text/javascript">
					(function () {
						var $mapLinkedSelect = $("select#map_linked");
						var $linkedMapNodeIDInput = $("input#hidden-linked_map_node_id");
						var visualMaps = <?php echo json_encode($visual_maps); ?>;
						var nodesById = <?php echo json_encode($meta_servers_by_id); ?>;

						visualMaps.forEach(function (vMap) {
							$mapLinkedSelect.append(
								'<option data-node-id="' + (vMap["node_id"] || 0) + '" value="' + vMap["id"] + '">'
								+ vMap["name"]
								+ (
									nodesById[vMap["node_id"]]
										? ' (' + nodesById[vMap["node_id"]]["server_name"] + ')'
										: ''
								)
								+ '</option>'
							);
						});

						$mapLinkedSelect.change(function (event) {
							var mapLinkedID = Number.parseInt(event.target.value);
							var itemSelected = $(event.target).children("option:selected");

							if (itemSelected.length === 0) {
								$linkedMapNodeIDInput.val(0);
							} else {
								var nodeId = itemSelected.data("node-id");
								$linkedMapNodeIDInput.val(nodeId != null ? nodeId : 0);
							}
						});
					})();
				</script>
				<?php
				$form_items_advance['map_linked_row']['html'] .= ob_get_clean();
			}
			else {
				$form_items_advance['map_linked_row']['html'] .= html_print_select_from_sql(
					$visual_maps, 'map_linked', 0, 'onLinkedMapChange(event)', __('None'), 0, true
				);
			}

			$form_items_advance['map_linked_row']['html'] .= '</td>';

			$status_type_select_items = array(
				"weight" => __("By status weight"),
				"service" => __("By critical elements")
			);
			$form_items_advance['linked_map_status_calculation_row'] = array();
			$form_items_advance['linked_map_status_calculation_row']['items'] = array(
				'group_item', 'static_graph', 'percentile_bar',
				'percentile_item', 'module_graph', 'simple_value',
				'icon', 'label', 'datos', 'donut_graph');
			$form_items_advance['linked_map_status_calculation_row']['html'] = '<td align="left">'.
				__('Type of the status calculation of the linked map') . '</td>'
				. '<td align="left">'
				. html_print_select(
					$status_type_select_items, 
					'linked_map_status_calculation_type',
					'default',
					'onLinkedMapStatusCalculationTypeChange(event)',
					__('By default'),
					'default',
					true,
					false,
					false
				)
				. '</td>';

			$form_items_advance['map_linked_weight'] = array();
			$form_items_advance['map_linked_weight']['items'] = array(
				'group_item', 'static_graph', 'percentile_bar',
				'percentile_item', 'module_graph', 'simple_value',
				'icon', 'label', 'datos', 'donut_graph');
			$form_items_advance['map_linked_weight']['html'] = '<td align="left">'
				. __('Linked map weight') . '</td>'
				. '<td align="left">'
				. html_print_input_text(
					'map_linked_weight', 80, '', 5, 5, true, false, false, "", "type_number percentage"
				)
				. '<span>%</span>'
				. ui_print_help_icon("linked_map_weight", true)
				. '</td>';

			$form_items_advance['linked_map_status_service_critical_row'] = array();
			$form_items_advance['linked_map_status_service_critical_row']['items'] = array(
				'group_item', 'static_graph', 'percentile_bar',
				'percentile_item', 'module_graph', 'simple_value',
				'icon', 'label', 'datos', 'donut_graph');
			$form_items_advance['linked_map_status_service_critical_row']['html'] = '<td align="left">'
				. __('Critical weight') . '</td>'
				. '<td align="left">'
				. html_print_input_text(
					'linked_map_status_service_critical', 80, '', 5, 5, true, false, false, "", "type_number percentage"
				)
				. '<span>%</span>'
				. '</td>';
			
			$form_items_advance['linked_map_status_service_warning_row'] = array();
			$form_items_advance['linked_map_status_service_warning_row']['items'] = array(
				'group_item', 'static_graph', 'percentile_bar',
				'percentile_item', 'module_graph', 'simple_value',
				'icon', 'label', 'datos', 'donut_graph');
			$form_items_advance['linked_map_status_service_warning_row']['html'] = '<td align="left">'
				. __('Warning weight') . '</td>'
				. '<td align="left">'
				. html_print_input_text(
					'linked_map_status_service_warning', 50, '', 5, 5, true, false, false, "", "type_number percentage"
				)
				. '<span>%</span>'
				. '</td>';

			$form_items_advance['line_case']['items'] = array('line_item');
			$form_items_advance['line_case']['html'] = '
				<td align="left">' . __('Lines haven\'t advanced options') . '</td>';

			$user_groups = users_get_groups($config['id_user']);
			$form_items_advance['element_group_row'] = array();
			$form_items_advance['element_group_row']['items'] = array(
				'group_item', 'static_graph', 'percentile_bar',
				'percentile_item', 'module_graph', 'simple_value',
				'icon', 'label', 'datos', 'donut_graph');
			$form_items_advance['element_group_row']['html'] = '<td align="left">'.
				__('Restrict access to group') . '</td>' .
				'<td align="left">' .
				html_print_select_groups(
					$config['id_user'],
					"VR",
					true,
					'element_group',
					__('All'),
					'',
					'',
					0,
					true) .
				ui_print_help_tip (
					__("If selected, restrict visualization of this item in the visual console to users who have access to selected group. This is also used on calculating child visual consoles."), true) . 
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
	
	echo '<div id="div_step_1" class="forced_title_layer"
		style="display: none; position: absolute; z-index: 99;">' .
			__('Click start point<br />of the line') .
		'</div>';
	
	echo '<div id="div_step_2" class="forced_title_layer"
		style="display: none; position: absolute; z-index: 99;">' .
			__('Click end point<br />of the line') .
		'</div>';
	
	ui_require_css_file ('color-picker');
	
	ui_require_jquery_file ('colorpicker');
	?>
	<script type="text/javascript">
		$(document).ready (function () {
			$("input.type_number[type=text]").prop("type", "number");
			$("input.percentage").prop("max", 100).prop("min", 0);

			$(".border_color").attachColorPicker();
			$(".fill_color").attachColorPicker();
			$(".line_color").attachColorPicker();
			$(".percentile_color").attachColorPicker();
			$(".percentile_label_color").attachColorPicker();
			$(".resume_color").attachColorPicker();
			$(".grid_color").attachColorPicker();
			
			$("input[name=radio_choice]").change(function(){
				$('#count_items').html(1);
			});
			
			$("#custom_graph").click(function(){
			$('#count_items').html(1);	
				jQuery.get ("ajax.php",
					{"page": "general/cg_items","data": $(this).val()},
						function (data, status) {
							if(data.split(",")[0] == 8){
								size = 400+(data.split(",")[1] * 50);
								if(data.split(",")[1]>3){
									size = 400+(3 * 50);
								}
								$('#text-width_module_graph').val(size);
								$('#text-height_module_graph').val(140);
								
							}
							else if (data.split(",")[0] == 4) {
								size = data.split(",")[1];
								if(data.split(",")[1] > 1){
									$('#count_items').html(data.split(",")[1]);
									$('#dir_items').html('vertical');
								}			
								$('#text-width_module_graph').val(300);
								$('#text-height_module_graph').val(50);
							}
							else if (data.split(",")[0] == 5) {
								size = data.split(",")[1];
								if(data.split(",")[1] > 1){
									$('#count_items').html(data.split(",")[1]);
									$('#dir_items').html('horizontal');
								}
								$('#text-width_module_graph').val(100);
								$('#text-height_module_graph').val(100);
							}
					
						});
								
				});
			
			
		});
		
		function show_timezone () {
			zone = $("#zone").val();
			
			$.ajax({
				type: "POST",
				url: "ajax.php",
				data: "page=godmode/setup/setup&select_timezone=1&zone=" + zone,
				dataType: "json",
				success: function(data) {
					$("#timezone").empty();
					jQuery.each (data, function (id, value) {
						timezone = value;
						var timezone_country = timezone.replace (/^.*\//g, "");
						$("select[name='timezone']").append($("<option>").val(timezone).html(timezone_country));
					});
				}
			});
		}		
	</script>
	<?php
}

function visual_map_editor_print_toolbox() {
	global $config;
	
	if (defined("METACONSOLE"))
		echo '<div id="editor" style="">';
	else
		echo '<div id="editor" style="margin-top: -10px;">';
	
	echo '<div id="toolbox">';
		visual_map_print_button_editor('static_graph', __('Static Graph'), 'left', false, 'camera_min', true);
		visual_map_print_button_editor('percentile_item', __('Percentile Item'), 'left', false, 'percentile_item_min', true);
		visual_map_print_button_editor('module_graph', __('Module Graph'), 'left', false, 'graph_min', true);
		visual_map_print_button_editor('donut_graph', __('Serialized pie graph'), 'left', false, 'donut_graph_min', true);
		visual_map_print_button_editor('bars_graph', __('Bars Graph'), 'left', false, 'bars_graph_min', true);
		visual_map_print_button_editor('auto_sla_graph', __('Auto SLA Graph'), 'left', false, 'auto_sla_graph_min', true);
		visual_map_print_button_editor('simple_value', __('Simple Value'), 'left', false, 'binary_min', true);
		visual_map_print_button_editor('label', __('Label'), 'left', false, 'label_min', true);
		visual_map_print_button_editor('icon', __('Icon'), 'left', false, 'icon_min', true);
		visual_map_print_button_editor('clock', __('Clock'), 'left', false, 'clock_min', true);
		visual_map_print_button_editor('group_item', __('Group'), 'left', false, 'group_item_min', true);
		visual_map_print_button_editor('box_item', __('Box'), 'left', false, 'box_item_min', true);
		visual_map_print_button_editor('line_item', __('Line'), 'left', false, 'line_item_min', true);
  		if(defined("METACONSOLE")){
 		 echo '<a href="javascript:" class="tip"><img src="'.$config['homeurl_static'].'/images/tip.png" data-title="The data displayed in editor mode is not real" data-use_title_for_force_title="1" 
			class="forced_title" alt="The data displayed in editor mode is not real"></a>';
		}
		else{
			echo '<a href="javascript:" class="tip"><img src="'.$config['homeurl'].'/images/tip.png" data-title="The data displayed in editor mode is not real" data-use_title_for_force_title="1" 
			class="forced_title" alt="The data displayed in editor mode is not real"></a>';
		}
		enterprise_hook("enterprise_visual_map_editor_print_toolbox");
		
		$text_autosave = html_print_input_hidden ('auto_save', true, true);
		visual_map_print_item_toolbox('auto_save', $text_autosave, 'right');
		visual_map_print_button_editor('show_grid', __('Show grid'), 'right', true, 'grid_min', true);
		visual_map_print_button_editor('edit_item', __('Update item'), 'right', true, 'config_min', true);
		visual_map_print_button_editor('delete_item', __('Delete item'), 'right', true, 'delete_min', true);
		visual_map_print_button_editor('copy_item', __('Copy item'), 'right', true, 'copy_item', true);
	echo '</div>';
	echo '</div>';
	echo '<div style="clear: right; margin-bottom: 10px;"></div>';
}

function visual_map_print_button_editor($idDiv, $label, $float = 'left',
	$disabled = false, $class= '', $imageButton = false) {
	
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
	echo '<span style="display: none" id="message_alert_no_label_no_image">' .
		__('No image or name defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_label">' .
		__('No label defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_image">' .
		__('No image defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_process">' .
		__('No process defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_max_percentile">' .
		__('No Max value defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_width_percentile">' .
		__('No width defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_bars_graph_height">' .
		__('No height defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_period">' .
		__('No period defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_agent">' .
		__('No agent defined.') .'</span>';
	echo '<span style="display: none" id="message_alert_no_module">' .
		__('No module defined.') .'</span>';
	
	echo '<span style="display: none" id="hack_translation_correct_save">' .
		__('Successfully save the changes.') .'</span>';
	echo '<span style="display: none" id="hack_translation_incorrect_save">' .
		__('Could not be save') .'</span>';
}
?>

<script type="text/javascript">
$(document).ready (function () {
	$("#map_linked").change(function () {
		$("#text-agent").val("");
		$("input[name=id_agent]").val(0);
		$("#module").empty();
		$("#module")
			.append($("<option>")
				.attr("value", 0)
				.html("<?php echo __('Any'); ?>"));
	})
});
</script>
