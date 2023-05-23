<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


/**
 * @package    Include
 * @subpackage Reporting
 */
function visual_map_editor_print_item_palette($visualConsole_id, $background)
{
    global $config;

    $images_list = [];
    $all_images = list_files($config['homedir'].'/images/console/icons/', 'png', 1, 0);

    foreach ($all_images as $image_file) {
        if (strpos($image_file, '_bad')) {
            continue;
        }

        if (strpos($image_file, '_ok')) {
            continue;
        }

        if (strpos($image_file, '_warning')) {
            continue;
        }

        $image_file = substr($image_file, 0, (strlen($image_file) - 4));
        $images_list[$image_file] = $image_file;
    }

    // Arrays for select box.
    $backgrounds_list = list_files($config['homedir'].'/images/console/background/', 'jpg', 1, 0);
    $backgrounds_list = array_merge($backgrounds_list, list_files($config['homedir'].'/images/console/background/', 'png', 1, 0));

    echo '<div id="properties_panel" class="propierties_panel_class">';
    // ----------------------------Hiden Form----------------------------
    ?>
    <table class="databox filters" border="0" cellpadding="4" cellspacing="4" width="350">
        <caption>
            <?php
            $titles = [
                'background'      => __('Background'),
                'static_graph'    => __('Static Graph'),
                'percentile_item' => __('Percentile Item'),
                'module_graph'    => __('Graph'),
                'auto_sla_graph'  => __('Event history graph'),
                'simple_value'    => __('Simple value').ui_print_help_tip(
                    __(
                        "To use 'label'field, you should write
					a text to replace '(_VALUE_)' and the value of the module will be printed at the end."
                    ),
                    true
                ),
                'label'           => __('Label'),
                'icon'            => __('Icon'),
                'clock'           => __('Clock'),
                'group_item'      => __('Group'),
                'box_item'        => __('Box'),
                'line_item'       => __('Line'),
                'color_cloud'     => __('Color cloud'),
            ];

            if (enterprise_installed()) {
                enterprise_visual_map_editor_add_title_palette($titles);
            }

            foreach ($titles as $item => $title) {
                echo '<span id="title_panel_span_'.$item.'"
					class="title_panel_span bolder invisible">'.$title.'</span>';
            }
            ?>
        </caption>
        <tbody>
            <?php
            $form_items = [];

            $form_items['line_width_row'] = [];
            $form_items['line_width_row']['items'] = [
                'datos',
                'line_item',
                'handler_start',
                'handler_end',
            ];
            $form_items['line_width_row']['html'] = '<td align="left">'.__('Width').'</td>
				<td align="left">'.html_print_input_text('line_width', 3, '', 3, 5, true).'</td>';

            $form_items['line_color_row'] = [];
            $form_items['line_color_row']['items'] = [
                'datos',
                'line_item',
                'handler_start',
                'handler_end',
            ];
            $form_items['line_color_row']['html'] = '<td align="left" valign="top"  >'.__('Border color').'</td>'.'<td align="left"  >'.html_print_input_text_extended(
                'line_color',
                '#000000',
                'text-line_color',
                '',
                7,
                7,
                false,
                '',
                'class="line_color"',
                true
            ).'</td>';

            $form_items['box_size_row'] = [];
            $form_items['box_size_row']['items'] = [
                'datos',
                'box_item',
            ];
            $form_items['box_size_row']['html'] = '<td align="left">'.__('Size').'</td>
				<td align="left">'.html_print_input_text('width_box', 300, '', 3, 5, true).' X '.html_print_input_text('height_box', 180, '', 3, 5, true).'</td>';

            $form_items['border_color_row'] = [];
            $form_items['border_color_row']['items'] = [
                'datos',
                'box_item',
            ];
            $form_items['border_color_row']['html'] = '<td align="left" valign="top"  >'.__('Border color').'</td>';
            $form_items['border_color_row']['html'] .= '<td align="left"  >';
            $form_items['border_color_row']['html'] .= html_print_input_color(
                'border_color',
                '#000000',
                'text-border_color',
                '',
                true
            );
            $form_items['border_color_row']['html'] .= '</td>';

            $form_items['border_width_row'] = [];
            $form_items['border_width_row']['items'] = [
                'datos',
                'box_item',
            ];
            $form_items['border_width_row']['html'] = '<td align="left">'.__('Border width').'</td>
				<td align="left">'.html_print_input_text('border_width', 3, '', 3, 5, true).'</td>';

            $form_items['fill_color_row'] = [];
            $form_items['fill_color_row']['items'] = [
                'datos',
                'box_item',
                'clock',
            ];
            $form_items['fill_color_row']['html'] = '<td align="left" valign="top"  >'.__('Fill color').'</td>';
            $form_items['fill_color_row']['html'] .= '<td align="left"  >';
            $form_items['fill_color_row']['html'] .= html_print_input_color(
                'fill_color',
                '#000000',
                'text-fill_color',
                '',
                true
            );
            $form_items['fill_color_row']['html'] .= '</td>';

            $form_items['module_graph_size_row'] = [];
            $form_items['module_graph_size_row']['items'] = [
                'module_graph',
                'datos',
            ];
            $form_items['module_graph_size_row']['html'] = '<td align="left">'.__('Size').'</td>
				<td align="left">'.html_print_input_text('width_module_graph', 300, '', 3, 5, true).' X '.html_print_input_text('height_module_graph', 180, '', 3, 5, true).'</td>';

            $form_items['label_row'] = [];
            $form_items['label_row']['items'] = [
                'label',
                'static_graph',
                'module_graph',
                'simple_value',
                'datos',
                'group_item',
                'auto_sla_graph',
                'bars_graph',
                'clock',
            ];
            $form_items['label_row']['html'] = '<td align="left" valign="top"  >'.__('Label').ui_print_help_icon('macros_visual_maps', true).'
				
				<div id="label_box_arrow">
					<span>Label position</span>
					<div class="labelpos" id="labelposup" position="up">
						'.html_print_image(
                'images/label_up.png',
                true,
                ['class' => 'height_100p w100p']
            ).'
					</div>
					<div class="labelpos" id="labelposleft" position="left">
						'.html_print_image(
                'images/label_left.png',
                true,
                ['class' => 'height_100p w100p']
            ).'
					</div>
					<div class="vsmap_div_label">
						<span id="obj_label">Object</span>
					</div>
					<div class="labelpos" id="labelposright" position="right">
						'.html_print_image(
                'images/label_right.png',
                true,
                ['class' => 'height_100p w100p']
            ).'
					</div>
					<div class="labelpos" sel="yes" id="labelposdown" position="down">
						'.html_print_image(
                'images/label_down_2.png',
                true,
                ['class' => 'height_100p w100p']
            ).'
					</div>
				</div>
				</td>
				<td align="left"  >'.html_print_input_text(
                'label',
                '',
                '',
                20,
                200,
                true
            ).'
				<span id="advice_label">
				'.__('Scroll the mouse wheel over the label editor to change the background color').'
				</span>
				</td>';

            $form_items['image_row'] = [];
            $form_items['image_row']['items'] = [
                'static_graph',
                'icon',
                'datos',
                'group_item',
            ];
            $form_items['image_row']['html'] = '<td align="left">'.__('Image').'</td>
				<td align="left">'.html_print_select($images_list, 'image', '', 'showPreview(this.value);', 'None', '', true).'</td>';

            $form_items['clock_animation_row'] = [];
            $form_items['clock_animation_row']['items'] = ['clock'];
            $form_items['clock_animation_row']['html'] = '<td align="left"><span>'.__('Clock animation').'</span></td>
				<td align="left">'.html_print_select(
                [
                    'analogic_1' => __('Simple analogic'),
                    'digital_1'  => __('Simple digital'),
                ],
                'clock_animation',
                '',
                '',
                0,
                'analogic_1',
                true,
                false,
                false
            ).'</td>';

            $form_items['timeformat_row'] = [];
            $form_items['timeformat_row']['items'] = ['clock'];
            $form_items['timeformat_row']['html'] = '<td align="left"><span>'.__('Time format').'</span></td>
				<td align="left">'.html_print_select(
                [
                    'time'     => __('Only time'),
                    'timedate' => __('Time and date'),
                ],
                'time_format',
                '',
                '',
                0,
                'time',
                true,
                false,
                false
            ).'</td>';

            $zone_name = [
                'Africa'     => __('Africa'),
                'America'    => __('America'),
                'Antarctica' => __('Antarctica'),
                'Arctic'     => __('Arctic'),
                'Asia'       => __('Asia'),
                'Atlantic'   => __('Atlantic'),
                'Australia'  => __('Australia'),
                'Europe'     => __('Europe'),
                'Indian'     => __('Indian'),
                'Pacific'    => __('Pacific'),
                'UTC'        => __('UTC'),
            ];
            $zone_selected = 'Europe';

            $timezones = timezone_identifiers_list();
            foreach ($timezones as $timezone) {
                if (strpos($timezone, $zone_selected) !== false) {
                    $timezone_n[$timezone] = $timezone;
                }
            }

            $form_items['timezone_row'] = [];
            $form_items['timezone_row']['items'] = ['clock'];
            $form_items['timezone_row']['html'] = '<td align="left"><span>'.__('Time zone').'</span></td>
				<td align="left">'.html_print_select($zone_name, 'zone', $zone_selected, 'show_timezone();', '', '', true).'&nbsp;&nbsp;'.html_print_select($timezone_n, 'timezone', '', '', '', '', true).'</td>';

            $form_items['enable_link_row'] = [];
            $form_items['enable_link_row']['items'] = [
                'static_graph',
                'percentile_bar',
                'percentile_item',
                'module_graph',
                'simple_value',
                'datos',
                'icon',
                'bars_graph',
                'group_item',
            ];

            $form_items['enable_link_row']['html'] = '<td align="left"  >'.__('Enable link').'</td>
				<td align="left"  >'.html_print_checkbox('enable_link', '', !is_metaconsole(), true).'</td>';

            $form_items['preview_row'] = [];
            $form_items['preview_row']['items'] = [
                'static_graph',
                'datos',
                'icon',
                'group_item',
            ];
            $form_items['preview_row']['html'] = '<td align="left" colspan="2">'.'<div id="preview" class="right"></div></td>';

            $form_items['background_color'] = [];
            $form_items['background_color']['items'] = [
                'module_graph',
                'datos',
                'bars_graph',
            ];
            $form_items['background_color']['html'] = '<td align="left"><span>'.__('Background color').'</span></td>
				<td align="left">'.html_print_select(
                [
                    'white'       => __('White'),
                    'black'       => __('Black'),
                    'transparent' => __('Transparent'),
                ],
                'background_color',
                '',
                '',
                0,
                'white',
                true,
                false,
                false,
                '',
                false,
                '',
                false,
                false,
                false,
                '',
                false,
                false,
                false,
                false,
                false
            ).'</td>';

            $form_items['grid_color_row'] = [];
            $form_items['grid_color_row']['items'] = ['bars_graph'];
            $form_items['grid_color_row']['html'] = '<td align="left" valign="top">'.__('Grid color').'</td>';
            $form_items['grid_color_row']['html'] .= '<td align="left"  >';
            $form_items['grid_color_row']['html'] .= html_print_input_color(
                'grid_color',
                '#000000',
                'text-grid_color',
                '',
                true
            );
            $form_items['grid_color_row']['html'] .= '</td>';

            $form_items['radio_choice_graph'] = [];
            $form_items['radio_choice_graph']['items'] = [
                'module_graph',
                'datos',
            ];
            $form_items['radio_choice_graph']['html'] = '<td align="left"  ></td>
				<td align="left"  >'.__('Module graph').'&nbsp;&nbsp;'.html_print_radio_button('radio_choice', 'module_graph', '', 'module_graph', true).'&nbsp;&nbsp;&nbsp;&nbsp;'.__('Custom graph').'&nbsp;&nbsp;'.html_print_radio_button('radio_choice', 'custom_graph', '', 'module_graph', true).'</td>';

            $form_items['custom_graph_row'] = [];
            $form_items['custom_graph_row']['html'] = '<td align="left"  >'.__('Custom graph').'</td><td align="left"  >';
            if (is_metaconsole()) {
                $graphs = [];
                $graphs = metaconsole_get_custom_graphs(true);
                $form_items['custom_graph_row']['html'] .= html_print_select($graphs, 'custom_graph', '', '', __('None'), 0, true);
            } else {
                $form_items['custom_graph_row']['html'] .= html_print_select_from_sql('SELECT id_graph, name FROM tgraph', 'custom_graph', '', '', __('None'), 0, true);
            }

            $form_items['custom_graph_row']['html'] .= '</td>';

            $form_items['agent_row'] = [];
            $form_items['agent_row']['items'] = [
                'static_graph',
                'percentile_bar',
                'percentile_item',
                'module_graph',
                'simple_value',
                'datos',
                'auto_sla_graph',
                'color_cloud',
            ];
            $form_items['agent_row']['html'] = '<td align="left">'.__('Agent').'</td>';
            $params = [];
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

            $form_items['agent_row']['html'] .= '<td align="left">'.ui_print_agent_autocomplete_input($params).'</td>';

            $form_items['agent_row_string'] = [];
            $form_items['agent_row_string']['items'] = [
                'donut_graph',
                'bars_graph',
            ];
            $form_items['agent_row_string']['html'] = '<td align="left">'.__('Agent').'</td>';
            $params = [];
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

            $form_items['agent_row_string']['html'] .= '<td align="left">'.ui_print_agent_autocomplete_input($params).'</td>';

            $form_items['module_row'] = [];
            $form_items['module_row']['items'] = [
                'static_graph',
                'percentile_bar',
                'percentile_item',
                'module_graph',
                'simple_value',
                'datos',
                'auto_sla_graph',
                'donut_graph',
                'bars_graph',
                'color_cloud',
            ];
            $form_items['module_row']['html'] = '<td align="left">'.__('Module').'</td>
				<td align="left">'.html_print_select([], 'module', '', '', __('Any'), 0, true).'<div id="data_image_container" class="invisible"><span id="data_image_check_label" class="mrgn_lft_20px">'.__('Data image').': </span><span id="data_image_check">Off</span><span id="data_image_width_label"> - Width: </span><input class="mrgn_lft_5px w40px" type="number" min="0" id="data_image_width" value="100"></input></div>
				</td>';

            $form_items['resume_color_row'] = [];
            $form_items['resume_color_row']['items'] = ['donut_graph'];
            $form_items['resume_color_row']['html'] = '<td align="left" valign="top"  >';
            $form_items['resume_color_row']['html'] .= __('Resume data color');
            $form_items['resume_color_row']['html'] .= '</td>';
            $form_items['resume_color_row']['html'] .= '<td align="left"  >';
            $form_items['resume_color_row']['html'] .= html_print_input_color(
                'resume_color',
                '#000000',
                'text-resume_color',
                '',
                true
            );
            $form_items['resume_color_row']['html'] .= '</td>';

            $event_times = [
                86400 => __('24h'),
                28800 => __('8h'),
                7200  => __('2h'),
                3600  => __('1h'),
            ];
            $form_items['event_max_time_row'] = [];
            $form_items['event_max_time_row']['items'] = ['auto_sla_graph'];
            $form_items['event_max_time_row']['html'] = '<td align="left">'.__('Max. Time').'</td>
				<td align="left">'.html_print_select(
                $event_times,
                'event_max_time_row',
                '',
                '',
                0,
                86400,
                true,
                false,
                false,
                '',
                false,
                '',
                false,
                false,
                false,
                '',
                false,
                false,
                false,
                false,
                false
            ).'</td>';

            $form_items['type_graph'] = [];
            $form_items['type_graph']['items'] = [
                'Line',
                'Area',
            ];
            $form_items['type_graph']['html'] = '<td align="left"><span>'.__('Type of graph').'</span></td>
				<td align="left">'.html_print_select(
                [
                    'line' => __('Line'),
                    'area' => __('Area'),
                ],
                'type_graph',
                '',
                '',
                0,
                'area',
                true,
                false,
                false,
                '',
                false,
                '',
                false,
                false,
                false,
                '',
                false,
                false,
                false,
                false,
                false
            ).'</td>';

            $own_info = get_user_info($config['id_user']);
            if (!$own_info['is_admin'] && !check_acl($config['id_user'], 0, 'PM')) {
                $return_all_group = false;
            } else {
                $return_all_group = true;
            }

            $form_items['group_row'] = [];
            $form_items['group_row']['items'] = [
                'group_item',
                'datos',
            ];
            $form_items['group_row']['html'] = '<td align="left">'.__('Group').'</td>
				<td align="left">'.html_print_select_groups(
                false,
                'AR',
                $return_all_group,
                'group',
                '',
                '',
                '',
                0,
                true
            ).'</td>';

            $form_items['process_value_row'] = [];
            $form_items['process_value_row']['items'] = [
                'simple_value',
                'datos',
            ];
            $form_items['process_value_row']['html'] = '<td align="left"><span>'.__('Process').'</span></td>
				<td align="left">'.html_print_select(
                [
                    PROCESS_VALUE_MIN => __('Min value'),
                    PROCESS_VALUE_MAX => __('Max value'),
                    PROCESS_VALUE_AVG => __('Avg value'),
                ],
                'process_value',
                '',
                '',
                __('None'),
                PROCESS_VALUE_NONE,
                true
            ).'</td>';

            $form_items['background_row_1'] = [];
            $form_items['background_row_1']['items'] = [
                'background',
                'datos',
            ];
            $form_items['background_row_1']['html'] = '<td align="left">'.__('Background').'</td>
				<td align="left">'.html_print_select($backgrounds_list, 'background_image', $background, '', 'None', '', true).'</td>';

            $form_items['background_row_2'] = [];
            $form_items['background_row_2']['items'] = [
                'background',
                'datos',
            ];
            $form_items['background_row_2']['html'] = '<td align="left">'.__('Original Size').'</td>';
            $form_items['background_row_2']['html'] .= '<td align="left">';
            $form_items['background_row_2']['html'] .= html_print_button(
                __('Apply'),
                'original_false',
                false,
                'setAspectRatioBackground("original", '.$visualConsole_id.')',
                [
                    'icon' => 'cog',
                    'mode' => 'mini secondary',
                ],
                true
            );
            $form_items['background_row_2']['html'] .= '</td>';

            $form_items['background_row_3'] = [];
            $form_items['background_row_3']['items'] = [
                'background',
                'datos',
            ];
            $form_items['background_row_3']['html'] = '<td align="left">'.__('Aspect ratio').'</td>';
            $form_items['background_row_3']['html'] .= '<td align="left">';
            $form_items['background_row_3']['html'] .= html_print_button(
                __('Proportional Width'),
                'original_false',
                false,
                'setAspectRatioBackground("width", '.$visualConsole_id.')',
                [
                    'icon' => 'cog',
                    'mode' => 'mini secondary',
                ],
                true
            );
            $form_items['background_row_3']['html'] .= '</td>';

            $form_items['background_row_4'] = [];
            $form_items['background_row_4']['items'] = [
                'background',
                'datos',
            ];
            $form_items['background_row_4']['html'] = '<td align="left"></td>';
            $form_items['background_row_4']['html'] .= '<td align="left">';
            $form_items['background_row_4']['html'] .= html_print_button(
                __('Height proportional'),
                'original_false',
                false,
                'setAspectRatioBackground("height", '.$visualConsole_id.')',
                [
                    'icon' => 'cog',
                    'mode' => 'mini secondary',
                ],
                true
            );
            $form_items['background_row_4']['html'] .= '</td>';

            $form_items['percentile_bar_row_1'] = [];
            $form_items['percentile_bar_row_1']['items'] = [
                'percentile_bar',
                'percentile_item',
                'datos',
                'donut_graph',
                'bars_graph',
                'clock',
            ];
            $form_items['percentile_bar_row_1']['html'] = '<td align="left">'.__('Width').'</td>';
            $form_items['percentile_bar_row_1']['html'] .= '<td align="left">';
            $form_items['percentile_bar_row_1']['html'] .= html_print_input_text(
                'width_percentile',
                0,
                '',
                3,
                5,
                true
            );
            $form_items['percentile_bar_row_1']['html'] .= '</td>';

            $form_items['height_bars_graph_row'] = [];
            $form_items['height_bars_graph_row']['items'] = ['bars_graph'];
            $form_items['height_bars_graph_row']['html'] = '<td align="left">'.__('Height').'</td>';
            $form_items['height_bars_graph_row']['html'] .= '<td align="left">';
            $form_items['height_bars_graph_row']['html'] .= html_print_input_text(
                'bars_graph_height',
                0,
                '',
                3,
                5,
                true
            );
            $form_items['height_bars_graph_row']['html'] .= '</td>';

            $form_items['percentile_bar_row_2'] = [];
            $form_items['percentile_bar_row_2']['items'] = [
                'percentile_bar',
                'percentile_item',
                'datos',
            ];
            $form_items['percentile_bar_row_2']['html'] = '<td align="left">'.__('Max value').'</td>
				<td align="left">'.html_print_input_text('max_percentile', 0, '', 3, 5, true).'</td>';

            $percentile_type = [
                'percentile'                     => __('Percentile'),
                'bubble'                         => __('Bubble'),
                'circular_progress_bar'          => __('Circular progress bar'),
                'interior_circular_progress_bar' => __('Circular progress bar (interior)'),
            ];
            $percentile_value = [
                'percent' => __('Percent'),
                'value'   => __('Value'),
            ];
            if (is_metaconsole()) {
                $form_items['percentile_item_row_3'] = [];
                $form_items['percentile_item_row_3']['items'] = [
                    'percentile_bar',
                    'percentile_item',
                    'datos',
                ];
                $form_items['percentile_item_row_3']['html'] = '<td align="left">'.__('Type').'</td>
					<td align="left">'.html_print_select($percentile_type, 'type_percentile', 'percentile', '', '', '', true, false, false, '', false, 'class="float-left"').'</td>';

                $form_items['percentile_item_row_4'] = [];
                $form_items['percentile_item_row_4']['items'] = [
                    'percentile_bar',
                    'percentile_item',
                    'datos',
                ];
                $form_items['percentile_item_row_4']['html'] = '<td align="left">'.__('Value to show').'</td>
					<td align="left">'.html_print_select($percentile_value, 'value_show', 'percent', '', '', '', true, false, false, '', false, 'class="float-left"').'</td>';
            } else {
                $form_items['percentile_item_row_3'] = [];
                $form_items['percentile_item_row_3']['items'] = [
                    'percentile_bar',
                    'percentile_item',
                    'datos',
                ];
                $form_items['percentile_item_row_3']['html'] = '<td align="left">'.__('Type').'</td>
					<td align="left">'.html_print_select($percentile_type, 'type_percentile', 'percentile', '', '', '', true, false, false).'</td>';

                $form_items['percentile_item_row_4'] = [];
                $form_items['percentile_item_row_4']['items'] = [
                    'percentile_bar',
                    'percentile_item',
                    'datos',
                ];
                $form_items['percentile_item_row_4']['html'] = '<td align="left">'.__('Value to show').'</td>
					<td align="left">'.html_print_select($percentile_value, 'value_show', 'percent', '', '', '', true, false, false).'</td>';
            }

            $form_items['percentile_item_row_5'] = [];
            $form_items['percentile_item_row_5']['items'] = [
                'percentile_bar',
                'percentile_item',
                'datos',
            ];
            $form_items['percentile_item_row_5']['html'] = '<td align="left">'.__('Element color').'</td>
				<td align="left">'.html_print_input_color(
                'percentile_color',
                '#ffffff',
                'text-percentile_color',
                '',
                true
            ).'</td>';

            $form_items['percentile_item_row_6'] = [];
            $form_items['percentile_item_row_6']['items'] = [
                'percentile_bar',
                'percentile_item',
                'datos',
            ];
            $form_items['percentile_item_row_6']['html'] = '<td align="left">'.__('Value color').'</td>
				<td align="left">'.html_print_input_color(
                'percentile_label_color',
                '#ffffff',
                'text-percentile_label_color',
                '',
                true
            ).'</td>';

            $form_items['percentile_bar_row_7'] = [];
            $form_items['percentile_bar_row_7']['items'] = [
                'percentile_bar',
                'percentile_item',
                'datos',
            ];
            $form_items['percentile_bar_row_7']['html'] = '<td align="left">'.__('Label').'</td>
				<td align="left">'.html_print_input_text('percentile_label', '', '', 30, 100, true).'</td>';

            $form_items['period_row'] = [];
            $form_items['period_row']['items'] = [
                'module_graph',
                'simple_value',
                'datos',
            ];
            $form_items['period_row']['html'] = '<td align="left">'.__('Period').'</td>
				<td align="left">'.html_print_extended_select_for_time('period', SECONDS_5MINUTES, '', '', '', false, true).'</td>';

            $form_items['show_statistics_row'] = [];
            $form_items['show_statistics_row']['items'] = ['group_item'];
            $form_items['show_statistics_row']['html'] = '<td align="left"  >'.__('Show statistics').'</td>
				<td align="left"  >'.html_print_checkbox('show_statistics', 1, '', true).'</td>';

            // Start of Color Cloud rows
            // Diameter
            $default_diameter = 100;
            $form_items['color_cloud_diameter_row'] = [];
            $form_items['color_cloud_diameter_row']['items'] = ['color_cloud'];
            $form_items['color_cloud_diameter_row']['html'] = '<td align="left">'.__('Diameter').'</td>
				<td align="left">'.html_print_input_text('diameter', $default_diameter, '', 3, 5, true).'</td>';

            // Default color
            $default_color = '#FFFFFF';
            $form_items['color_cloud_def_color_row'] = [];
            $form_items['color_cloud_def_color_row']['items'] = ['color_cloud'];
            $form_items['color_cloud_def_color_row']['html'] = '<td align="left">'.__('Default color').'</td>
				<td align="left">'.html_print_input_color('default_color', $default_color, '', false, true).'</td>';

            // Color ranges
            $color_range_tip = __('The color of the element will be the one selected in the first range created in which the value of the module is found (with the initial and final values of the range included)').'.';
            $form_items['color_cloud_color_ranges_row'] = [];
            $form_items['color_cloud_color_ranges_row']['items'] = ['color_cloud'];
            $form_items['color_cloud_color_ranges_row']['html'] = '<td align="left">'.__('Ranges').ui_print_help_tip($color_range_tip, true).'</td>'.'<td align="left">'.'<table id="new-color-range" class="databox color-range color-range-creation">'.'<tr>'.'<td>'.__('From value').'</td>'.'<td>'.html_print_input_text('from_value_new', '', '', 5, 255, true).'</td>'.'<td rowspan="4">'.'<a class="color-range-add" href="#">'.html_print_image('images/add.png', true).'</a>'.'</td>'.'</tr>'.'<td>'.__('To value').'</td>'.'<td>'.html_print_input_text('to_value_new', '', '', 5, 255, true).'</td>'.'<td></td>'.'<tr>'.'</tr>'.'<tr>'.'<td>'.__('Color').'</td>'.'<td>'.html_print_input_color('color_new', $default_color, '', false, true).'</td>'.'<td></td>'.'</tr>'.'</table>'.'</td>';

            // End of Color Cloud rows
            $form_items['show_on_top_row'] = [];
            $form_items['show_on_top_row']['items'] = ['group_item'];
            $form_items['show_on_top_row']['html'] = '<td align="left"  >';
            $form_items['show_on_top_row']['html'] .= __('Always show on top');
            $form_items['show_on_top_row']['html'] .= ui_print_help_tip(
                __('It allows the element to be superimposed to the rest of items of the visual console'),
                true
            );
            $form_items['show_on_top_row']['html'] .= '</td>';
            $form_items['show_on_top_row']['html'] .= '<td align="left"  >';
            $form_items['show_on_top_row']['html'] .= html_print_checkbox('show_on_top', 1, '', true);
            $form_items['show_on_top_row']['html'] .= '</td>';

            $show_last_value = [
                '0' => __('Hide last value on boolean modules'),
                '1' => __('Enabled'),
                '2' => __('Disabled'),
            ];
            $form_items['show_last_value_row'] = [];
            $form_items['show_last_value_row']['items'] = ['static_graph'];
            $form_items['show_last_value_row']['html'] = '<td align="left"  >'.__('Show last value').'</td>
				<td align="left">'.html_print_select($show_last_value, 'last_value', 0, '', '', '', true).'</td>';

            $form_items['module_graph_size_row'] = [];
            $form_items['module_graph_size_row']['items'] = [
                'module_graph',
                'datos',
            ];
            $form_items['module_graph_size_row']['html'] = '<td align="left">'.__('Size').'</td>
				<td align="left">'.html_print_input_text('width_module_graph', 300, '', 3, 5, true).' X '.html_print_input_text('height_module_graph', 180, '', 3, 5, true).' X '.'<span id="count_items">1</span> '.'<span id="dir_items"></span> item/s				
				</td>';

            $bars_graph_types = [
                'vertical'   => __('Vertical'),
                'horizontal' => __('Horizontal'),
            ];
            $form_items['bars_graph_type'] = [];
            $form_items['bars_graph_type']['items'] = ['bars_graph'];
            $form_items['bars_graph_type']['html'] = '<td align="left">'.__('Type').'</td>
				<td align="left">'.html_print_select(
                $bars_graph_types,
                'bars_graph_type',
                'vertical',
                '',
                '',
                '',
                true,
                false,
                true,
                '',
                false,
                '',
                false,
                false,
                false,
                '',
                false,
                false,
                false,
                false,
                false
            ).'</td>';

            // Insert and modify before the buttons to create or update.
            if (enterprise_installed()) {
                enterprise_visual_map_editor_modify_form_items_palette($form_items);
            }

            $form_items['button_update_row'] = [];
            $form_items['button_update_row']['items'] = ['datos'];
            $form_items['button_update_row']['html'] = '<td align="left" colspan="2"><div class="flex flex-end">'.html_print_button(__('Cancel'), 'cancel_button', false, 'cancel_button_palette_callback();', [ 'icon' => 'delete', 'mode' => 'secondary'], true).'<span ="margin-right:10px;">&nbsp</span>'.html_print_button(__('Update'), 'update_button', false, 'update_button_palette_callback();', [ 'icon' => 'upd'], true).'</div></td>';

            $form_items['button_create_row'] = [];
            $form_items['button_create_row']['items'] = ['datos'];
            $form_items['button_create_row']['html'] = '<td align="left" colspan="2" ><div class="flex flex-end">'.html_print_button(__('Cancel'), 'cancel_button', false, 'cancel_button_palette_callback();', [ 'icon' => 'delete', 'mode' => 'secondary'], true).'<span ="margin-right:10px;">&nbsp</span>'.html_print_button(__('Create'), 'create_button', false, 'create_button_palette_callback();', [ 'icon' => 'wand'], true).'</div></td>';

            foreach ($form_items as $item => $item_options) {
                echo '<tr id="'.$item.'"   class="'.implode(' ', (array) $item_options['items']).'">';
                echo $item_options['html'];
                echo '</tr>';
            }
            ?>
            <tr id="advance_options_link" class="datos">
                <td colspan="2" class="center">
                    <a href="javascript: toggle_advance_options_palette()">
                        <?php echo __('Advanced options'); ?>
                    </a>
                </td>
            </tr>
        </tbody>
        <tbody id="advance_options" style="display: none">
            <?php
            $form_items_advance = [];

            $form_items_advance['position_row'] = [];
            $form_items_advance['position_row']['items'] = [
                'static_graph',
                'percentile_bar',
                'percentile_item',
                'module_graph',
                'simple_value',
                'label',
                'icon',
                'datos',
                'box_item',
                'auto_sla_graph',
                'bars_graph',
                'clock',
                'donut_graph',
                'color_cloud',
            ];
            $form_items_advance['position_row']['html'] = '
				<td align="left">'.__('Position').'</td>
				<td align="left">('.html_print_input_text('left', '0', '', 3, 5, true).' , '.html_print_input_text('top', '0', '', 3, 5, true).')</td>';

            $form_items_advance['size_row'] = [];
            $form_items_advance['size_row']['items'] = [
                'group_item',
                'background',
                'static_graph',
                'icon datos',
                'auto_sla_graph',
            ];
            $form_items_advance['size_row']['html'] = '<td align="left">'.__('Size').ui_print_help_tip(
                __('For use the original image file size, set 0 width and 0 height.'),
                true
            ).'</td>
				<td align="left">'.html_print_input_text('width', 0, '', 3, 5, true).' X '.html_print_input_text('height', 0, '', 3, 5, true).'</td>';

            $parents = visual_map_get_items_parents($visualConsole_id);

            $form_items_advance['parent_row'] = [];
            $form_items_advance['parent_row']['items'] = [
                'group_item',
                'static_graph',
                'percentile_bar',
                'percentile_item',
                'module_graph',
                'simple_value',
                'label',
                'icon',
                'datos',
                'auto_sla_graph',
                'bars_graph',
                'donut_graph',
            ];
            $form_items_advance['parent_row']['html'] = '<td align="left">'.__('Parent').'</td>
				<td align="left">'.html_print_input_hidden('parents_load', base64_encode(json_encode($parents)), true).html_print_select($parents, 'parent', 0, '', __('None'), 0, true).'</td>';

            $form_items_advance['map_linked_row'] = [];
            $form_items_advance['map_linked_row']['items'] = [
                'group_item',
                'static_graph',
                'percentile_bar',
                'percentile_item',
                'module_graph',
                'simple_value',
                'icon',
                'label',
                'datos',
                'donut_graph',
            ];
            $visual_maps = db_get_all_rows_filter('tlayout', 'id != '.(int) $visualConsole_id, ['id', 'name']);

            $form_items_advance['map_linked_row']['html'] = '<td align="left">'.__('Linked visual console').'</td>'.'<td align="left">';

            if (is_metaconsole()) {
                $meta_servers = metaconsole_get_servers();
                foreach ($meta_servers as $server) {
                    if (metaconsole_load_external_db($server) !== NOERR) {
                        metaconsole_restore_db();
                        continue;
                    }

                    $node_visual_maps = db_get_all_rows_filter('tlayout', [], ['id', 'name']);

                    if (isset($node_visual_maps) && is_array($node_visual_maps)) {
                        foreach ($node_visual_maps as $node_visual_map) {
                            $node_visual_map['node_id'] = (int) $server['id'];
                            $visual_maps[] = $node_visual_map;
                        }
                    }

                    metaconsole_restore_db();
                }

                $meta_servers_by_id = array_reduce(
                    $meta_servers,
                    function ($arr, $item) {
                        $arr[$item['id']] = $item;
                        return $arr;
                    },
                    []
                );

                $form_items_advance['map_linked_row']['html'] .= html_print_select(
                    [],
                    'map_linked',
                    0,
                    'onLinkedMapChange(event)',
                    __('None'),
                    0,
                    true
                );
                $form_items_advance['map_linked_row']['html'] .= html_print_input_hidden(
                    'linked_map_node_id',
                    0,
                    true
                );

                ob_start();
                ?>
                <script type="text/javascript">
                    (function () {
                        var $mapLinkedSelect = $("select#map_linked");
                        var $linkedMapNodeIDInput = $("input#hidden-linked_map_node_id");
                        var visualMaps = <?php echo json_encode($visual_maps); ?>;
                        if (!(visualMaps instanceof Array)) visualMaps = [];
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
            } else {
                $visual_maps = [];
                if (empty($visual_maps) === false) {
                    $visual_maps = array_reduce(
                        $visual_maps,
                        function ($all, $item) {
                            $all[$item['id']] = $item['name'];
                            return $all;
                        },
                        []
                    );
                }

                $form_items_advance['map_linked_row']['html'] .= html_print_select(
                    $visual_maps,
                    'map_linked',
                    0,
                    'onLinkedMapChange(event)',
                    __('None'),
                    0,
                    true
                );
            }

            $form_items_advance['map_linked_row']['html'] .= '</td>';

            $status_type_select_items = [
                'weight'  => __('By status weight'),
                'service' => __('By critical elements'),
            ];
            $form_items_advance['linked_map_status_calculation_row'] = [];
            $form_items_advance['linked_map_status_calculation_row']['items'] = [
                'group_item',
                'static_graph',
                'percentile_bar',
                'percentile_item',
                'module_graph',
                'simple_value',
                'icon',
                'label',
                'datos',
                'donut_graph',
            ];
            $form_items_advance['linked_map_status_calculation_row']['html'] = '<td align="left">'.__('Type of the status calculation of the linked visual console').'</td>'.'<td align="left">'.html_print_select(
                $status_type_select_items,
                'linked_map_status_calculation_type',
                'default',
                'onLinkedMapStatusCalculationTypeChange(event)',
                __('By default'),
                'default',
                true,
                false,
                false
            ).ui_print_help_icon('linked_map_status_calc', true).'</td>';

            $form_items_advance['map_linked_weight'] = [];
            $form_items_advance['map_linked_weight']['items'] = [
                'group_item',
                'static_graph',
                'percentile_bar',
                'percentile_item',
                'module_graph',
                'simple_value',
                'icon',
                'label',
                'datos',
                'donut_graph',
            ];
            $form_items_advance['map_linked_weight']['html'] = '<td align="left">'.__('Linked visual console weight').'</td>'.'<td align="left">'.html_print_input_text(
                'map_linked_weight',
                80,
                '',
                5,
                5,
                true,
                false,
                false,
                '',
                'type_number percentage'
            ).'<span>%</span>'.'</td>';

            $form_items_advance['linked_map_status_service_critical_row'] = [];
            $form_items_advance['linked_map_status_service_critical_row']['items'] = [
                'group_item',
                'static_graph',
                'percentile_bar',
                'percentile_item',
                'module_graph',
                'simple_value',
                'icon',
                'label',
                'datos',
                'donut_graph',
            ];
            $form_items_advance['linked_map_status_service_critical_row']['html'] = '<td align="left">'.__('Critical weight').'</td>'.'<td align="left">'.html_print_input_text(
                'linked_map_status_service_critical',
                80,
                '',
                5,
                5,
                true,
                false,
                false,
                '',
                'type_number percentage'
            ).'<span>%</span>'.'</td>';

            $form_items_advance['linked_map_status_service_warning_row'] = [];
            $form_items_advance['linked_map_status_service_warning_row']['items'] = [
                'group_item',
                'static_graph',
                'percentile_bar',
                'percentile_item',
                'module_graph',
                'simple_value',
                'icon',
                'label',
                'datos',
                'donut_graph',
            ];
            $form_items_advance['linked_map_status_service_warning_row']['html'] = '<td align="left">'.__('Warning weight').'</td>'.'<td align="left">'.html_print_input_text(
                'linked_map_status_service_warning',
                50,
                '',
                5,
                5,
                true,
                false,
                false,
                '',
                'type_number percentage'
            ).'<span>%</span>'.'</td>';

            $form_items_advance['line_case']['items'] = ['line_item'];
            $form_items_advance['line_case']['html'] = '
				<td align="left">'.__('Lines haven\'t advanced options').'</td>';

            $user_groups = users_get_groups($config['id_user']);
            $form_items_advance['element_group_row'] = [];
            $form_items_advance['element_group_row']['items'] = [
                'group_item',
                'static_graph',
                'percentile_bar',
                'percentile_item',
                'module_graph',
                'simple_value',
                'icon',
                'label',
                'datos',
                'donut_graph',
                'color_cloud',
            ];
            $form_items_advance['element_group_row']['html'] = '<td align="left">';
            $form_items_advance['element_group_row']['html'] .= __('Restrict access to group');
            $form_items_advance['element_group_row']['html'] .= ui_print_help_tip(
                __('If selected, restrict visualization of this item in the visual console to users who have access to selected group. This is also used on calculating child visual consoles.'),
                true
            );
            $form_items_advance['element_group_row']['html'] .= '</td>';
            $form_items_advance['element_group_row']['html'] .= '<td align="left">';
            $form_items_advance['element_group_row']['html'] .= html_print_select_groups(
                $config['id_user'],
                'VR',
                true,
                'element_group',
                0,
                '',
                '',
                '',
                true
            );
            $form_items_advance['element_group_row']['html'] .= '</td>';

    if (!$config['legacy_vc']) {
        $intervals = [
            10   => '10 '.__('seconds'),
            30   => '30 '.__('seconds'),
            60   => '1 '.__('minutes'),
            300  => '5 '.__('minutes'),
            900  => '15 '.__('minutes'),
            1800 => '30 '.__('minutes'),
            3600 => '1 '.__('hour'),
        ];

        $form_items_advance['cache_expiration_row'] = [];
        $form_items_advance['cache_expiration_row']['items'] = [
            'static_graph',
            'percentile_bar',
            'percentile_item',
            'module_graph',
            'simple_value',
            'datos',
            'auto_sla_graph',
            'group_item',
            'bars_graph',
            'donut_graph',
            'color_cloud',
            'service',
        ];
        $form_items_advance['cache_expiration_row']['html'] = '<td align="left">';
        $form_items_advance['cache_expiration_row']['html'] .= __('Cache expiration');
        $form_items_advance['cache_expiration_row']['html'] .= '</td>';
        $form_items_advance['cache_expiration_row']['html'] .= '<td align="left">';
        $form_items_advance['cache_expiration_row']['html'] .= html_print_extended_select_for_time(
            'cache_expiration',
            $config['vc_default_cache_expiration'],
            '',
            __('No cache'),
            0,
            false,
            true,
            false,
            true,
            '',
            false,
            $intervals
        );
        $form_items_advance['cache_expiration_row']['html'] .= '</td>';
    }

    // Insert and modify before the buttons to create or update.
    if (enterprise_installed()) {
        enterprise_visual_map_editor_modify_form_items_advance_palette(
            $form_items_advance
        );
    }

    foreach ($form_items_advance as $item => $item_options) {
        echo '<tr id="'.$item.'"   class="'.implode(' ', $item_options['items']).'">';
        echo $item_options['html'];
        echo '</tr>';
    }
    ?>
        </tbody>
    </table>
    <?php
    echo '</div>';

    echo '<div id="div_step_1" class="forced_title_layer steps_vsmap"
		>'.__('Click start point<br />of the line').'</div>';

    echo '<div id="div_step_2" class="forced_title_layer steps_vsmap"
		>'.__('Click end point<br />of the line').'</div>';

    ui_require_css_file('color-picker', 'include/styles/js/');

    ui_require_jquery_file('colorpicker');
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


function visual_map_editor_print_toolbox()
{
    global $config;

    if (defined('METACONSOLE')) {
        echo '<div id="editor"  >';
    } else {
        echo '<div id="editor" class="mrgn_top-10px">';
    }

    echo '<div id="toolbox">';
        visual_map_print_button_editor('static_graph', __('Static Image'), 'left', false, 'camera_min', true);
        visual_map_print_button_editor('percentile_item', __('Percentile Item'), 'left', false, 'percentile_item_min', true);
        visual_map_print_button_editor('module_graph', __('Module Graph'), 'left', false, 'graph_min', true);
        visual_map_print_button_editor('donut_graph', __('Serialized pie graph'), 'left', false, 'donut_graph_min', true);
        visual_map_print_button_editor('bars_graph', __('Bars Graph'), 'left', false, 'bars_graph_min', true);
        visual_map_print_button_editor('auto_sla_graph', __('Event history graph'), 'left', false, 'auto_sla_graph_min', true);
        visual_map_print_button_editor('simple_value', __('Simple Value'), 'left', false, 'binary_min', true);
        visual_map_print_button_editor('label', __('Label'), 'left', false, 'label_min', true);
        visual_map_print_button_editor('icon', __('Icon'), 'left', false, 'icon_min', true);
        visual_map_print_button_editor('clock', __('Clock'), 'left', false, 'clock_min', true);
        visual_map_print_button_editor('group_item', __('Group'), 'left', false, 'group_item_min', true);
        visual_map_print_button_editor('box_item', __('Box'), 'left', false, 'box_item', true);
        visual_map_print_button_editor('line_item', __('Line'), 'left', false, 'line_item', true);
        visual_map_print_button_editor('color_cloud', __('Color cloud'), 'left', false, 'color_cloud_min', true);
    if (isset($config['legacy_vc']) === false
        || (bool) $config['legacy_vc'] === false
    ) {
        // Applies only on modern VC.
        visual_map_print_button_editor('network_link', __('Network link'), 'left', false, 'network_link_min', true);
    }

    if (defined('METACONSOLE')) {
        echo '<a href="javascript:" class="tip"><img src="'.$config['homeurl_static'].'/images/tip.png" data-title="The data displayed in editor mode is not real" data-use_title_for_force_title="1" 
			class="forced_title" alt="The data displayed in editor mode is not real"></a>';
    } else {
        echo '<a href="javascript:" class="tip"><img src="'.$config['homeurl'].'/images/tip.png" data-title="The data displayed in editor mode is not real" data-use_title_for_force_title="1" 
			class="forced_title" alt="The data displayed in editor mode is not real"></a>';
    }

        enterprise_hook('enterprise_visual_map_editor_print_toolbox');

        $text_autosave = html_print_input_hidden('auto_save', true, true);
        visual_map_print_item_toolbox('auto_save', $text_autosave, 'right');
        visual_map_print_button_editor('show_grid', __('Show grid'), 'right', true, 'show_grid', true);
        visual_map_print_button_editor('edit_item', __('Update item'), 'right', true, 'edit_item', true);
        visual_map_print_button_editor('delete_item', __('Delete item'), 'right', true, 'delete_item', true);
        visual_map_print_button_editor('copy_item', __('Copy item'), 'right', true, 'copy_item', true);
    echo '</div>';
    echo '</div>';
    echo '<div class="clear_right mrgn_btn_10px"></div>';
}


function visual_map_print_button_editor(
    $idDiv,
    $label,
    $float='left',
    $disabled=false,
    $class='',
    $imageButton=false
) {
    html_print_button(
        $label,
        $idDiv,
        $disabled,
        'click_button_toolbox("'.$idDiv.'");',
        [
            'class' => $class.' float-'.$float,
            'mode'  => 'onlyIcon',
        ],
        false,
        $imageButton
    );
}


function visual_map_editor_print_hack_translate_strings()
{
    // Trick for it have a traduct text for javascript.
    echo '<span id="any_text" class="invisible">'.__('Any').'</span>';
    echo '<span id="ip_text"  class="invisible">'.__('IP').'</span>';

    // Hack to translate messages in javascript.
    echo "<div id='message_min_allowed_size'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('Min allowed size is 1024x768.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_custom_graph'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No custom graph defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_label_no_image'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No image or name defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_label'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No label defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_service'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No service defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_image'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No image defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_process'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No process defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_max_percentile'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No Max value defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_width'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No width defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_height'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No height defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_max_width'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('The width must not exceed the size of the visual console container.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_max_height'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('The height must not exceed the size of the visual console container.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_period'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No period defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_agent'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No agent defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_module'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No module defined.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_module_string_type'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No module defined. This module must be string type.').'</p>';
    echo '</div>';

    echo "<div id='hack_translation_correct_save'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('Successfully save the changes.').'</p>';
    echo '</div>';

    echo "<div id='hack_translation_incorrect_save'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('Could not be save.').'</p>';
    echo '</div>';

    echo "<div id='message_alert_no_custom_graph'  title='".__('Visual Console Builder Information')."' class='invisible'>";
    echo "<p class='center bolder'>".__('No custom graph defined.').'</p>';
    echo '</div>';

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
