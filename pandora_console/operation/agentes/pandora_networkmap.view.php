<?php
/**
 * Network map actions for main view.
 *
 * @category   Extensions
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

global $config;

require_once 'include/functions_networkmap.php';
enterprise_include_once('include/functions_policies.php');
require_once 'include/functions_modules.php';
require_once 'include/functions_db.php';
enterprise_include_once('include/functions_agents.php');

// Login check.
check_login();

// --------------INIT AJAX-----------------------------------------------
if (is_ajax() === true) {
    $update_refresh_state          = (bool) get_parameter('update_refresh_state', false);
    $set_center                    = (bool) get_parameter('set_center', false);
    $erase_relation                = (bool) get_parameter('erase_relation', false);
    $search_agents                 = (bool) get_parameter('search_agents');
    $get_agent_pos_search          = (bool) get_parameter('get_agent_pos_search', false);
    $get_shape_node                = (bool) get_parameter('get_shape_node', false);
    $set_shape_node                = (bool) get_parameter('set_shape_node', false);
    $get_info_module               = (bool) get_parameter('get_info_module', false);
    $get_tooltip_content           = (bool) get_parameter('get_tooltip_content', false);
    $add_several_agents            = (bool) get_parameter('add_several_agents', false);
    $update_fictional_point        = (bool) get_parameter('update_fictional_point', false);
    $update_z                      = (bool) get_parameter('update_z', false);
    $module_get_status             = (bool) get_parameter('module_get_status', false);
    $update_node_alert             = (bool) get_parameter('update_node_alert', false);
    $process_migration             = (bool) get_parameter('process_migration', false);
    $get_agent_info                = (bool) get_parameter('get_agent_info', false);
    $update_node                   = (bool) get_parameter('update_node', false);
    $delete_link                   = (bool) get_parameter('delete_link', false);
    $update_fictional_node         = (bool) get_parameter('update_fictional_node', false);
    $change_shape                  = (bool) get_parameter('change_shape', false);
    $get_intefaces                 = (bool) get_parameter('get_intefaces', false);
    $set_relationship              = (bool) get_parameter('set_relationship', false);
    $delete_node                   = (bool) get_parameter('delete_node', false);
    $create_fictional_point        = (bool) get_parameter('create_fictional_point', false);
    $update_node_color             = (bool) get_parameter('update_node_color', false);
    $refresh_holding_area          = (bool) get_parameter('refresh_holding_area', false);
    $update_node                   = (bool) get_parameter('update_node', false);
    $update_link                   = (bool) get_parameter('update_link', false);
    $add_agent                     = (bool) get_parameter('add_agent', false);
    $get_agents_in_group           = (bool) get_parameter('get_agents_in_group', false);
    $get_interface_info            = (bool) get_parameter('get_interface_info', false);
    $set_relationship_interface    = (bool) get_parameter('set_relationship_interface', false);
    $add_interface_relation        = (bool) get_parameter('add_interface_relation', false);
    $update_node_name              = (bool) get_parameter('update_node_name', false);
    $get_networkmap_from_fictional = (bool) get_parameter('get_networkmap_from_fictional', false);
    $get_reset_map_form            = (bool) get_parameter('get_reset_map_form', false);
    $reset_map                     = (bool) get_parameter('reset_map', false);
    $refresh_map                   = (bool) get_parameter('refresh_map', false);

    if ($refresh_map) {
        $id_map = get_parameter('id');

        include_once $config['homedir'].'/include/class/NetworkMap.class.php';

        $map_manager = new NetworkMap(
            ['id_map' => $id_map]
        );

        $filter = json_decode($map_manager->map['filter'], true);
        $z_dash = $filter['z_dash'];

        $nodes = $map_manager->recalculateCoords();

        foreach ($nodes as $key => $value) {
            if ($value['type'] == 0 || $value['type'] == 2) {
                $node['x'] = ($value['x'] + ($map_manager->map['center_x'] / 2) / $z_dash);
                $node['y'] = ($value['y'] + ($map_manager->map['center_y'] / 2) / $z_dash);
                $node['refresh'] = 0;

                db_process_sql_update(
                    'titem',
                    $node,
                    [
                        'source_data' => $value['source_data'],
                        'id_map'      => $id_map,
                    ]
                );
            }
        }

        echo $id_map;

        return;
    }

    if ($get_reset_map_form) {
        $map_id = get_parameter('map_id');

        $map_info = db_get_row_filter('tmap', ['id' => $map_id]);
        $map_filter = json_decode($map_info['filter'], true);

        $map_form = '';

        $table = new StdClass();
        $table->id = 'form_editor';
        $table->width = '100%';
        $table->class = 'databox_color';
        $table->head = [];
        $table->size = [];
        $table->size[0] = '30%';
        $table->style = [];
        $table->style[0] = 'font-weight: bold; width: 150px;';

        $table->data = [];

        $table->data[0][0] = __('Name');
        $table->data[0][1] = html_print_input_text('name', io_safe_output($map_info['name']), '', 30, 100, true);

        $table->data[1][0] = __('Group');
        $table->data[1][1] = html_print_select_groups(false, 'AR', true, 'id_group', $map_info['id_group'], '', '', 0, true);

        $table->data[2][0] = __('Node radius');
        $table->data[2][1] = html_print_input_text('node_radius', $map_filter['node_radius'], '', 2, 10, true);

        $table->data[3][0] = __('Description');
        $table->data[3][1] = html_print_textarea('description', 7, 25, $map_info['description'], '', true);

        $source = $map_info['source'];
        switch ($source) {
            case 0:
                $source = 'group';
            break;

            case 1:
                $source = 'recon_task';
            break;

            case 2:
                $source = 'ip_mask';
            break;
        }

        $table->data[4][0] = __('Position X');
        $table->data[4][1] = html_print_input_text('pos_x', $map_filter['x_offs'], '', 2, 10, true);
        $table->data[5][0] = __('Position Y');
        $table->data[5][1] = html_print_input_text('pos_y', $map_filter['y_offs'], '', 2, 10, true);

        $table->data[6][0] = __('Zoom scale');

        $table->data[6][1] = html_print_input_text('scale_z', $map_filter['z_dash'], '', 2, 10, true).ui_print_help_tip(__('Introduce zoom level. 1 = Highest resolution. Figures may include decimals'), true);

        $table->data['source'][0] = __('Source');
        $table->data['source'][1] = html_print_select(
            [
                'group'      => __('Group'),
                'recon_task' => __('Discovery task'),
                'ip_mask'    => __('CIDR IP mask'),
            ],
            'source',
            $source,
            '',
            '',
            0,
            true,
            false,
            false,
            '',
            $disabled_source
        );

        if (! check_acl($config['id_user'], 0, 'PM')) {
            $sql = sprintf(
                'SELECT *
				FROM trecon_task RT, tusuario_perfil UP
				WHERE UP.id_usuario = "%s" AND UP.id_grupo = RT.id_group',
                $config['id_user']
            );


            $result = db_get_all_rows_sql($sql);
        } else {
            $sql = sprintf(
                'SELECT *
				FROM trecon_task'
            );
            $result = db_get_all_rows_sql($sql);
        }

        $list_recon_tasks = [];
        if (!empty($result)) {
            foreach ($result as $item) {
                $list_recon_tasks[$item['id_rt']] = io_safe_output($item['name']);
            }
        }

        $table->data['source_data_recon_task'][0] = __('Source from recon task');
        $table->data['source_data_recon_task'][0] .= ui_print_help_tip(
            __('It is setted any recon task, the nodes get from the recontask IP mask instead from the group.'),
            true
        );
        $table->data['source_data_recon_task'][1] = html_print_select(
            $list_recon_tasks,
            'recon_task_id',
            $map_info['source_data'],
            '',
            __('None'),
            0,
            true,
            false,
            true,
            ''
        );
        $table->data['source_data_recon_task'][1] .= ui_print_help_tip(
            __('Show only the task with the recon script "SNMP L2 Recon".'),
            true
        );

        $table->data['source_data_ip_mask'][0] = __('Source from CIDR IP mask');
        $table->data['source_data_ip_mask'][1] = html_print_textarea(
            'ip_mask',
            3,
            5,
            $map_info['source_data'],
            '',
            true,
            '',
            $disabled_source
        );

        $dont_show_subgroups = 0;
        if (isset($map_filter['dont_show_subgroups'])) {
            if ($map_filter['dont_show_subgroups'] === 'true'
                || $map_filter['dont_show_subgroups'] == 1
            ) {
                $dont_show_subgroups = 1;
            }
        }

        $table->data['source_data_dont_show_subgroups'][0] = __('Don\'t show subgroups:');
        $table->data['source_data_dont_show_subgroups'][1] = html_print_checkbox('dont_show_subgroups', '1', $dont_show_subgroups, true);

        $methods = [
            'twopi' => 'radial',
            'dot'   => 'flat',
            'circo' => 'circular',
            'neato' => 'spring1',
            'fdp'   => 'spring2',
        ];

        switch ($map_info['generation_method']) {
            case LAYOUT_CIRCULAR:
                $method = 'circo';
            break;

            case LAYOUT_FLAT:
                $method = 'dot';
            break;

            case LAYOUT_RADIAL:
                $method = 'twopi';
            break;

            case LAYOUT_SPRING1:
            default:
                $method = 'neato';
            break;

            case LAYOUT_SPRING2:
                $method = 'fdp';
            break;
        }

        $table->data[7][0] = __('Method generation networkmap');
        $table->data[7][1] = html_print_select(
            $methods,
            'method',
            $method,
            '',
            '',
            'twopi',
            true,
            false,
            true,
            '',
            $disabled_generation_method_select
        );

        $table->data['nodesep'][0] = __('Node separation');
        $table->data['nodesep'][1] = html_print_input_text('node_sep', $map_filter['node_sep'], '', 5, 10, true).ui_print_help_tip(__('Separation between nodes. By default 0.25'), true);

        $table->data['ranksep'][0] = __('Rank separation');
        $table->data['ranksep'][1] = html_print_input_text('rank_sep', $map_filter['rank_sep'], '', 5, 10, true).ui_print_help_tip(__('Only flat and radial. Separation between arrows. By default 0.5 in flat and 1.0 in radial'), true);

        $table->data['mindist'][0] = __('Min nodes dist');
        $table->data['mindist'][1] = html_print_input_text('mindist', $map_filter['mindist'], '', 5, 10, true).ui_print_help_tip(__('Only circular. Minimum separation between all nodes. By default 1.0'), true);

        $table->data['kval'][0] = __('Default ideal node separation');
        $table->data['kval'][1] = html_print_input_text('kval', $map_filter['kval'], '', 5, 10, true).ui_print_help_tip(__('Only fdp. Default ideal node separation in the layout. By default 0.3'), true);

        $map_form .= html_print_table($table, true);

        $map_form .= '<script>
                        $("#source").change(function() {
							const source = $(this).val();

							if (source == \'recon_task\') {
								$("#form_editor-source_data_ip_mask")
									.css(\'display\', \'none\');
								$("#form_editor-source_data_dont_show_subgroups")
									.css(\'display\', \'none\');
								$("#form_editor-source_data_recon_task")
									.css(\'display\', \'\');
							}
							else if (source == \'ip_mask\') {
								$("#form_editor-source_data_ip_mask")
									.css(\'display\', \'\');
								$("#form_editor-source_data_recon_task")
									.css(\'display\', \'none\');
								$("#form_editor-source_data_dont_show_subgroups")
									.css(\'display\', \'none\');
							}
							else if (source == \'group\') {
								$("#form_editor-source_data_ip_mask")
									.css(\'display\', \'none\');
								$("#form_editor-source_data_recon_task")
									.css(\'display\', \'none\');
								$("#form_editor-source_data_dont_show_subgroups")
									.css(\'display\', \'\');
							}
						});
						$("#method").on(\'change\', function () {
						var method = $("#method").val();

						if (method == \'circo\') {
							$("#form_editor-ranksep")
								.css(\'display\', \'none\');
							$("#form_editor-mindist")
								.css(\'display\', \'\');
							$("#form_editor-kval")
								.css(\'display\', \'none\');
							$("#form_editor-nodesep")
								.css(\'display\', \'\');
						}
						else if (method == \'dot\') {
							$("#form_editor-ranksep")
								.css(\'display\', \'\');
							$("#form_editor-mindist")
								.css(\'display\', \'none\');
							$("#form_editor-kval")
								.css(\'display\', \'none\');
							$("#form_editor-nodesep")
								.css(\'display\', \'\');
						}
						else if (method == \'twopi\') {
							$("#form_editor-ranksep")
								.css(\'display\', \'\');
							$("#form_editor-mindist")
								.css(\'display\', \'none\');
							$("#form_editor-kval")
								.css(\'display\', \'none\');
							$("#form_editor-nodesep")
								.css(\'display\', \'\');
						}
						else if (method == \'neato\') {
							$("#form_editor-ranksep")
								.css(\'display\', \'none\');
							$("#form_editor-mindist")
								.css(\'display\', \'none\');
							$("#form_editor-kval")
								.css(\'display\', \'none\');
							$("#form_editor-nodesep")
								.css(\'display\', \'\');
						}
						else if (method == \'radial_dinamic\') {
							$("#form_editor-ranksep")
								.css(\'display\', \'none\');
							$("#form_editor-mindist")
								.css(\'display\', \'none\');
							$("#form_editor-kval")
								.css(\'display\', \'none\');
							$("#form_editor-nodesep")
								.css(\'display\', \'none\');
						}
						else if (method == \'fdp\') {
							$("#form_editor-ranksep")
								.css(\'display\', \'none\');
							$("#form_editor-mindist")
								.css(\'display\', \'none\');
							$("#form_editor-kval")
								.css(\'display\', \'\');
							$("#form_editor-nodesep")
								.css(\'display\', \'\');
						}
					});

					$("#source").trigger("change");
					$("#method").trigger("change");
			</script>';

        echo $map_form;

        return;
    }

    if ($reset_map) {
        $map_id = get_parameter('map_id');

        $delete_nodes = db_process_sql_delete('titem', ['id_map' => $map_id]);
        $delete_rel = db_process_sql_delete('trel_item', ['id_map' => $map_id]);
        $data = get_parameter('params');

        $items = db_get_value('count(*) as n', 'titem', 'id_map', $map_id);
        $rel_items = db_get_value('count(*) as n', 'trel_item', 'id_map', $map_id);

        if (($items == 0) && ($rel_items == 0)) {
            $data = get_parameter('params');

            $new_values = [];
            $new_values['name'] = $data['name'];
            $new_values['id_group'] = $data['id_group'];
            $new_values['id_user'] = $config['id_user'];

            $filter = [];
            $filter['node_radius'] = $data['node_radius'];
            $filter['x_offs'] = $data['pos_x'];
            $filter['y_offs'] = $data['pos_y'];
            $filter['z_dash'] = $data['scale_z'];
            $filter['node_sep'] = $data['node_sep'];
            $filter['rank_sep'] = $data['rank_sep'];
            $filter['mindist'] = $data['mindist'];
            $filter['kval'] = $data['kval'];

            $new_values['description'] = $data['description'];

            switch ($data['source']) {
                case 'group':
                    $new_values['source'] = SOURCE_GROUP;
                    $filter['dont_show_subgroups'] = $data['dont_show_subgroups'];
                    $new_values['source_data'] = SOURCE_GROUP;
                break;

                case 'recon_task':
                    $new_values['source'] = SOURCE_TASK;
                    $new_values['source_data'] = $data['recon_task_id'];
                break;

                case 'ip_mask':
                    $new_values['source'] = SOURCE_NETWORK;
                    $new_values['source_data'] = $data['ip_mask'];
                break;

                default:
                    // Ignore. Control input.
                break;
            }

            switch ($data['generation_method']) {
                case 'circo':
                    $new_values['generation_method'] = LAYOUT_CIRCULAR;
                break;

                case 'dot':
                    $new_values['generation_method'] = LAYOUT_FLAT;
                break;

                case 'twopi':
                    $new_values['generation_method'] = LAYOUT_RADIAL;
                break;

                case 'neato':
                default:
                    $new_values['generation_method'] = LAYOUT_SPRING1;
                break;

                case 'fdp':
                    $new_values['generation_method'] = LAYOUT_SPRING2;
                break;
            }

            $new_values['filter'] = json_encode($filter);

            // Ensure we have all required data before update.
            if (!isset($new_values['generation_method'])
                || !isset($new_values['source'])
                || !isset($new_values['source_data'])
                || !isset($new_values['name'])
                || !isset($new_values['id_group'])
                || !isset($map_id)
            ) {
                $result_update = false;
            } else {
                $result_update = db_process_sql_update('tmap', $new_values, ['id' => $map_id]);
            }

            if ($result_update) {
                $return['error'] = false;
                error_log('Failed to reset map '.$map_id);
            } else {
                ui_update_name_fav_element($map_id, 'Network_map', $new_values['name']);
                $return['error'] = true;
            }
        } else {
            $return['error'] = true;
            error_log(
                'Failed to reset map '.$map_id.' items:'.$items.', relations:'.$rel_items
            );
        }

        echo json_encode($return);

        return;
    }

    if ($delete_link) {
        $source_id = (int) get_parameter('source_id', 0);
        $source_module_id = (int) get_parameter('source_module_id', 0);
        $target_id = (int) get_parameter('target_id', 0);
        $target_module_id = (int) get_parameter('target_module_id', 0);
        $networkmap_id = (int) get_parameter('networkmap_id', 0);
        $id_link = (int) get_parameter('id_link', 0);

        $return = [];
        $return['correct'] = false;

        // ACL for the network map.
        $id_group = db_get_value('id_group', 'tmap', 'id', $networkmap_id);
        // $networkmap_read = check_acl ($config['id_user'], $id_group, "MR");
        $networkmap_write = check_acl($config['id_user'], $id_group, 'MW');
        $networkmap_manage = check_acl($config['id_user'], $id_group, 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access networkmap'
            );
            echo json_encode($return);
            return;
        }

        $return['correct'] = networkmap_delete_link(
            $networkmap_id,
            $source_module_id,
            $target_module_id,
            $id_link
        );

        echo json_encode($return);

        return;
    }

    if ($get_networkmap_from_fictional) {
        $id_node = (int) get_parameter('id');
        $networkmap_id = (int) get_parameter('id_map');
        $node = db_get_row_filter(
            'titem',
            [
                'id_map' => $networkmap_id,
                'id'     => $id_node,
            ]
        );

        $style = json_decode($node['style'], true);

        $return = [];
        if ($style['networkmap'] != 0) {
            $return['correct'] = true;
            $return['id_networkmap'] = $style['networkmap'];
        } else {
            $return['correct'] = false;
        }

        echo json_encode($return);

        return;
    }

    if ($update_fictional_node) {
        $networkmap_id = (int) get_parameter('networkmap_id', 0);
        $node_id = (int) get_parameter('node_id', 0);
        $name = get_parameter('name', '');
        $networkmap_to_link = (int) get_parameter('networkmap_to_link', 0);

        $return = [];
        $return['correct'] = false;

        // ACL for the network map
        $id_group = db_get_value('id_group', 'tmap', 'id', $networkmap_id);
        // $networkmap_read = check_acl ($config['id_user'], $id_group, "MR");
        $networkmap_write = check_acl($config['id_user'], $id_group, 'MW');
        $networkmap_manage = check_acl($config['id_user'], $id_group, 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access networkmap'
            );
            echo json_encode($return);
            return;
        }

        $node = db_get_row(
            'titem',
            'id',
            $node_id
        );
        $node['style'] = json_decode($node['style'], true);
        $node['style']['label'] = $name;
        $node['style']['networkmap'] = $networkmap_to_link;
        $node['style'] = json_encode($node['style']);

        $return['correct'] = (bool) db_process_sql_update(
            'titem',
            $node,
            ['id' => $node_id]
        );
        echo json_encode($return);

        return;
    }

    if ($update_node_name) {
        $networkmap_id = (int) get_parameter('networkmap_id', 0);
        $node_id = (int) get_parameter('node_id', 0);
        $name = get_parameter('name', '');

        $return = [];
        $return['correct'] = false;
        $return['raw_text'] = $name;
        $return['text'] = io_safe_output($name);

        // ACL for the network map
        $id_group = db_get_value('id_group', 'tmap', 'id', $networkmap_id);
        // $networkmap_read = check_acl ($config['id_user'], $id_group, "MR");
        $networkmap_write = check_acl($config['id_user'], $id_group, 'MW');
        $networkmap_manage = check_acl($config['id_user'], $id_group, 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access networkmap'
            );
            echo json_encode($return);
            return;
        }

        $node = db_get_row(
            'titem',
            'id',
            $node_id
        );
        $node['style'] = json_decode($node['style'], true);
        $node['style']['label'] = $name;
        $node['style'] = json_encode($node['style']);

        $return['correct'] = (bool) db_process_sql_update(
            'titem',
            $node,
            ['id' => $node_id]
        );
        echo json_encode($return);

        return;
    }

    if ($change_shape) {
        $networkmap_id = (int) get_parameter('networkmap_id', 0);
        $id = (int) get_parameter('id', 0);
        $shape = get_parameter('shape', 'circle');

        $return = [];
        $return['correct'] = false;

        // ACL for the network map
        $id_group = db_get_value('id_group', 'tmap', 'id', $networkmap_id);
        // $networkmap_read = check_acl ($config['id_user'], $id_group, "MR");
        $networkmap_write = check_acl($config['id_user'], $id_group, 'MW');
        $networkmap_manage = check_acl($config['id_user'], $id_group, 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access networkmap'
            );
            echo json_encode($return);
            return;
        }

        $node = db_get_row_filter(
            'titem',
            [
                'id_map' => $networkmap_id,
                'id'     => $id,
            ]
        );

        $node['style'] = json_decode($node['style'], true);
        $node['style']['shape'] = $shape;
        $node['style'] = json_encode($node['style']);

        $return['correct'] = db_process_sql_update(
            'titem',
            $node,
            [
                'id_map' => $networkmap_id,
                'id'     => $id,
            ]
        );

        echo json_encode($return);

        return;
    }

    if ($get_intefaces) {
        $id_agent_target = (int) get_parameter('id_agent_target', 0);
        $id_agent_source = (int) get_parameter('id_agent_source', 0);

        $return = [];
        $return['correct'] = true;
        $return['target_interfaces'] = [];
        $return['source_interfaces'] = [];

        $return['source_interfaces'] = modules_get_all_interfaces(
            $id_agent_source,
            [
                'id_agente_modulo',
                'nombre',
            ]
        );
        $return['target_interfaces'] = modules_get_all_interfaces(
            $id_agent_target,
            [
                'id_agente_modulo',
                'nombre',
            ]
        );

        echo json_encode($return);

        return;
    }

    if ($set_relationship) {
        $id = (int) get_parameter('id');
        $child = (int) get_parameter('child');
        $parent = (int) get_parameter('parent');
        $child_source_data = db_get_value('source_data', 'titem', 'id', $child);
        $parent_source_data = db_get_value('source_data', 'titem', 'id', $parent);
        $child_type = db_get_value('type', 'titem', 'id', $child);
        $parent_type = db_get_value('type', 'titem', 'id', $parent);

        $correct = db_process_sql_insert(
            'trel_item',
            [
                'id_map'                => $id,
                'id_parent'             => $parent,
                'id_child'              => $child,
                'parent_type'           => $parent_type,
                'child_type'            => $child_type,
                'id_parent_source_data' => $parent_source_data,
                'id_child_source_data'  => $child_source_data,
            ]
        );

        $return = [];
        $return['correct'] = false;

        if ($correct) {
            $return['correct'] = true;
            $return['id'] = $correct;
            $return['id_child'] = $child;
            $return['id_parent'] = $parent;
        }

        echo json_encode($return);

        return;
    }

    if ($delete_node) {
        $id = (int) get_parameter('id', 0);

        $return = [];
        $return['correct'] = false;

        $return['correct'] = erase_node(['id' => $id]);

        echo json_encode($return);

        return;
    }

    if ($add_agent === true) {
        $id = (int) get_parameter('id', 0);
        $agent = get_parameter('agent', '');
        $x = (int) get_parameter('x', 0);
        $y = (int) get_parameter('y', 0);
        $id_agent = (int) get_parameter('id_agent', -1);
        if ($id_agent == -1) {
            $id_agent = agents_get_agent_id($agent);
        }

        $return = [];
        $return['correct'] = false;

        $item_exist = db_get_value_filter(
            'id',
            'titem',
            [
                'source_data' => $id_agent,
                'type'        => 0,
                'id_map'      => $id,
                'deleted'     => 0,
            ]
        );

        if ($item_exist) {
            echo json_encode($return);
            return;
        }

        $return_data = add_agent_node_in_option($id, $id_agent, $x, $y);

        $relations = db_get_all_rows_filter(
            'trel_item',
            [
                'id_map'  => $id,
                'deleted' => 0,
            ]
        );

        if ($relations === false) {
            $relations = [];
        }

        networkmap_clean_relations_for_js($relations);

        if ($return_data['id'] !== false) {
            $id_node = $return_data['id'];
            $return['correct'] = true;

            $node = db_get_row(
                'titem',
                'id',
                $id_node
            );
            $style = json_decode($node['style'], true);

            $return['id_node'] = $id_node;
            $return['id_agent'] = $node['source_data'];
            $return['parent'] = $node['parent'];
            $return['shape'] = $style['shape'];
            $return['image'] = $style['image'];
            $return['image_url'] = html_print_image(
                $style['image'],
                true,
                false,
                true
            );
            $return['width'] = $style['width'];
            $return['height'] = $style['height'];
            $return['raw_text'] = $style['label'];
            $return['text'] = io_safe_output($style['label']);
            $return['x'] = $x;
            $return['y'] = $y;
            $return['map_id'] = $node['id_map'];
            $return['state'] = $node['state'];
            $return['status'] = get_status_color_networkmap($id_agent);
        }

        if (!empty($return_data['rel'])) {
            $return['rel'] = $return_data['rel'];
        }

        echo json_encode($return);

        return;
    }

    if ($create_fictional_point) {
        $id = (int) get_parameter('id', 0);
        $x = (int) get_parameter('x', 0);
        $y = (int) get_parameter('y', 0);
        $name = get_parameter('name', '');
        $shape = get_parameter('shape', 0);
        $radious = (int) get_parameter('radious', 20);
        $color = get_parameter('color', 0);

        $networkmap = (int) get_parameter('networkmap', 0);
        if (empty($networkmap) === false) {
            $color = get_status_color_networkmap_fictional_point($networkmap);
        }

        $return = [];
        $return['correct'] = false;

        $data = [];
        $data['id_map'] = $id;
        $data['x'] = $x;
        $data['y'] = $y;
        $data['source_data'] = -2;
        // The id for the fictional points.
        $data['deleted'] = 0;
        // Type in db to fictional nodes
        $data['type'] = 3;
        $style = [];
        $style['shape'] = $shape;
        $style['image'] = '';
        $style['width'] = ($radious * 2);
        $style['height'] = ($radious * 2);
        // WORK AROUND FOR THE JSON ENCODE WITH FOR EXAMPLE Ñ OR Á
        $style['label'] = 'json_encode_crash_with_ut8_chars';
        $style['color'] = $color;
        $style['networkmap'] = $networkmap;
        $data['style'] = json_encode($style);
        $data['style'] = str_replace(
            'json_encode_crash_with_ut8_chars',
            $name,
            $data['style']
        );

        $id_node = db_process_sql_insert(
            'titem',
            $data
        );

        $return['correct'] = (bool) $id_node;

        if ($return['correct']) {
            $return['id_node'] = $id_node;
            $return['id_agent'] = -2;
            // The finctional point id
            $return['shape'] = $shape;
            $return['image'] = '';
            $return['width'] = ($radious * 2);
            $return['height'] = ($radious * 2);
            $return['raw_text'] = $name;
            $return['text'] = io_safe_output($name);
            $return['color'] = $color;
            $return['networkmap'] = $networkmap;
        }

        echo json_encode($return);

        return;
    }

    if ($update_node_color) {
        $id = (int) get_parameter('id', 0);

        $id_agent = db_get_value(
            'source_data',
            'titem',
            'id',
            $id
        );

        $return = [];
        $return['correct'] = true;
        if ($id_agent != -2) {
            $return['color'] = get_status_color_networkmap($id_agent);
        } else {
            $style = db_get_value(
                'style',
                'titem',
                'id',
                $id
            );
            $style = json_decode($style, true);
            if ($style['networkmap'] == 0) {
                $return['color'] = $style['color'];
            } else {
                $return['color'] = get_status_color_networkmap_fictional_point(
                    $style['networkmap']
                );
            }
        }

        echo json_encode($return);

        return;
    }

    if ($refresh_holding_area) {
        ob_start();
        $networkmap_id = (int) get_parameter('id', 0);
        $x = (int) get_parameter('x', 666);
        $y = (int) get_parameter('y', 666);
        $return = [];
        $return['correct'] = false;
        $return['holding_area'] = [];

        // ACL for the network map.
        $id_group = db_get_value('id_group', 'tmap', 'id', $networkmap_id);
        // $networkmap_read = check_acl ($config['id_user'], $id_group, "MR");
        $networkmap_write = check_acl($config['id_user'], $id_group, 'MW');
        $networkmap_manage = check_acl($config['id_user'], $id_group, 'MM');
        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access networkmap'
            );
            echo json_encode($return);
            return;
        }

        $data = networkmap_refresh_holding_area($networkmap_id, $x, $y);

        if (!empty($data)) {
            $return['correct'] = true;
            $return['holding_area'] = $data;
        }

        ob_end_clean();

        echo json_encode($return);

        return;
    }

    if ($update_node) {
        $node_json = io_safe_output(get_parameter('node', ''));
        $x = get_parameter('x');
        $y = get_parameter('y');

        $node = json_decode($node_json, true);

        echo json_encode(update_node($node, $x, $y));

        return;
    }

    if ($update_link) {
        // Only real agents/modules are able to have interface relations.
        $networkmap_id = (int) get_parameter('networkmap_id', 0);
        $id_link = (int) get_parameter('id_link', 0);
        $interface_source = (int) get_parameter('interface_source', 0);
        $interface_target = (int) get_parameter('interface_target', 0);
        $source_text = get_parameter('source_text');
        $target_text = get_parameter('target_text');

        // Initialize statuses to NORMAl by default.
        $source_status = AGENT_MODULE_STATUS_NORMAL;
        $target_status = AGENT_MODULE_STATUS_NORMAL;

        $old_link = db_get_row_sql('SELECT id_parent_source_data, id_child_source_data FROM trel_item WHERE id = '.$id_link.' AND deleted = 0');

        // Search source.
        if ($source_text != 'None') {
            // Interface selected.
            // Source_value is id_agente_modulo.
            $source_agent = db_get_value(
                'id_agente',
                'tagente_modulo',
                'id_agente_modulo',
                $interface_source
            );
            $source_type = NODE_MODULE;
            $source_link_value = $interface_source;
            $source_status = db_get_value_filter(
                'estado',
                'tagente_estado',
                ['id_agente_modulo' => $interface_source]
            );

            if (preg_match('/(.+)_ifOperStatus$/', (string) $source_text, $matches)) {
                if ($matches[1]) {
                    $source_text = $matches[1];
                }
            }
        } else {
            // No interface selected.
            // Source_value is id_agente.
            $source_text = '';
            $source_agent = $interface_source;
            $source_type = ($interface_source == 0) ? NODE_PANDORA : NODE_AGENT;
            $source_link_value = $source_agent;
        }

        // Search node id in map.
        $parent_id = db_get_value_filter(
            'id',
            'titem',
            [
                'source_data' => $source_agent,
                'id_map'      => $networkmap_id,
            ]
        );

         // Search target.
        if ($target_text != 'None') {
            // Interface selected.
            // Target value is id_agente_modulo.
            $target_agent = db_get_value(
                'id_agente',
                'tagente_modulo',
                'id_agente_modulo',
                $interface_target
            );
            $target_type = NODE_MODULE;
            $target_link_value = $interface_target;
            $target_status = db_get_value_filter(
                'estado',
                'tagente_estado',
                ['id_agente_modulo' => $interface_target]
            );

            if (preg_match('/(.+)_ifOperStatus$/', (string) $target_text, $matches)) {
                if ($matches[1]) {
                       $target_text = $matches[1];
                }
            }
        } else {
            // No interface selected.
            // Target value is id_agente.
            $target_text = '';
            $target_agent = $interface_target;
            $target_type = NODE_AGENT;
            $target_link_value = $target_agent;
        }

        // Search node id in map.
        $child_id = db_get_value_filter(
            'id',
            'titem',
            [
                'source_data' => $target_agent,
                'id_map'      => $networkmap_id,
            ]
        );

        // Register link in DB.
        $link = [];
        $link['id_parent'] = $parent_id;
        $link['id_child'] = $child_id;
        $link['id_item'] = 0;
        $link['deleted'] = 0;
        $link['id_map'] = $networkmap_id;
        $link['parent_type'] = $source_type;
        $link['id_parent_source_data'] = $source_link_value;
        $link['child_type'] = $target_type;
        $link['id_child_source_data'] = $target_link_value;

        $insert_result = db_process_sql_insert('trel_item', $link);

        // Store module relationship (if req.).
        if ($target_type == NODE_MODULE
            && $source_type == NODE_MODULE
        ) {
            $rel = [];
            $rel['module_a'] = $target_link_value;
            $rel['module_b'] = $source_link_value;
            $rel['disable_update'] = 0;

            $insert_result_relation = db_process_sql_insert(
                'tmodule_relationship',
                $rel
            );
        }

        // Delete old links and send response to controller.
        if ($insert_result !== false) {
            db_process_sql_delete('trel_item', ['id' => $id_link]);
            db_process_sql_delete('tmodule_relationship', ['module_a' => $old_link['id_parent_source_data'], 'module_b' => $old_link['id_child_source_data']]);
            db_process_sql_delete('tmodule_relationship', ['module_a' => $old_link['id_child_source_data'], 'module_b' => $old_link['id_parent_source_data']]);

            $return['correct'] = true;
            $return['status_start'] = $source_status;
            $return['status_end'] = $target_status;
            $return['text_start'] = $source_text;
            $return['text_end'] = $target_text;
            $return['id_db_link'] = $insert_result;
            $return['id_db_source'] = $parent_id;
            $return['id_db_target'] = $child_id;
            $return['type_source'] = $source_type;
            $return['type_target'] = $target_type;
        } else {
            $return['correct'] = false;
        }

        echo json_encode($return);

        return;
    }

    if ($get_agents_in_group) {
        $id = (int) get_parameter('id', 0);
        $group = (int) get_parameter('group', -1);

        $return = [];
        $return['correct'] = false;

        if ($group != -1) {
            $where_id_agente = ' 1=1 ';

            $agents_in_networkmap = db_get_all_rows_filter(
                'titem',
                [
                    'id_map'  => $id,
                    'deleted' => 0,
                ]
            );
            if ($agents_in_networkmap !== false) {
                $ids = [];
                foreach ($agents_in_networkmap as $agent) {
                    if ($agent['type'] == 0) {
                        $ids[] = $agent['source_data'];
                    }
                }

                $where_id_agente = ' id_agente NOT IN ('.implode(',', $ids).')';
            }


            $sql = 'SELECT id_agente, alias
				FROM tagente
				WHERE id_grupo = '.$group.' AND '.$where_id_agente.' 
				ORDER BY alias ASC';

            $agents = db_get_all_rows_sql($sql);

            if ($agents !== false) {
                $return['agents'] = [];
                foreach ($agents as $agent) {
                    $return['agents'][$agent['id_agente']] = $agent['alias'];
                }

                $return['correct'] = true;
            }
        }

        echo json_encode($return);

        return;
    }

    if ($get_agent_info) {
        $id_agent = (int) get_parameter('id_agent');

        $return = [];
        $return['alias'] = agents_get_alias($id_agent);
        $return['adressess'] = agents_get_addresses($id_agent);
        $id_group = agents_get_agent_group($id_agent);
        $return['group'] = db_get_value('nombre', 'tgrupo', 'id_grupo', $id_group);
        $id_os = agents_get_os($id_agent);
        $return['os'] = html_print_div([ 'class' => 'flex main_menu_icon invert_filter', 'content' => ui_print_os_icon($id_os, true, true)], true);

        echo json_encode($return);

        return;
    }

    if ($get_interface_info) {
        $id_agent = get_parameter('id_agent');

        $agent = db_get_row('tagente', 'id_agente', $id_agent);

        $network_interfaces_by_agents = agents_get_network_interfaces([$agent]);

        $network_interfaces = [];
        if (!empty($network_interfaces_by_agents) && !empty($network_interfaces_by_agents[$id_agent])) {
            $network_interfaces = $network_interfaces_by_agents[$id_agent]['interfaces'];
        }

        $return = [];
        $index = 0;
        foreach ($network_interfaces as $interface_name => $interface) {
            if (!empty($interface['traffic'])) {
                $permission = check_acl($config['id_user'], $agent['id_grupo'], 'RR');

                if ($permission) {
                    if ($interface['traffic']['in'] > 0 && $interface['traffic']['out'] > 0) {
                        $params = [
                            'interface_name'     => $interface_name,
                            'agent_id'           => $id_agent,
                            'traffic_module_in'  => $interface['traffic']['in'],
                            'traffic_module_out' => $interface['traffic']['out'],
                        ];
                        $params_json = json_encode($params);
                        $params_encoded = base64_encode($params_json);
                        $win_handle = dechex(crc32($interface['status_module_id'].$interface_name));
                        $graph_link = "<a href=\"javascript:winopeng_var('operation/agentes/interface_traffic_graph_win.php?params=$params_encoded','$win_handle', 800, 480)\">".html_print_image('images/chart_curve.png', true, ['title' => __('Interface traffic')]).'</a>';
                    } else {
                        $graph_link = html_print_image(
                            'images/chart_curve.disabled.png',
                            true,
                            ['title' => __('inOctets and outOctets must be enabled.')]
                        );
                    }
                } else {
                    $graph_link = '';
                }
            } else {
                $graph_link = '';
            }

            $return[$index]['graph'] = $graph_link;
            $return[$index]['name'] = '<strong>'.$interface_name.'</strong>';
            $return[$index]['status'] = $interface['status_image'];
            $return[$index]['ip'] = $interface['ip'];
            $return[$index]['mac'] = $interface['mac'];

            $index++;
        }

        echo json_encode($return);

        return;
    }

    if ($set_relationship_interface) {
        $id_networkmap = get_parameter('id');
        $child = get_parameter('child');
        $parent = get_parameter('parent');

        $child_source_data = db_get_value_filter('source_data', 'titem', ['id' => $child, 'id_map' => $id_networkmap]);
        $parent_source_data = db_get_value_filter('source_data', 'titem', ['id' => $parent, 'id_map' => $id_networkmap]);

        $return = [];

        $return['interfaces_child'] = [];
        $return['interfaces_child'] = modules_get_all_interfaces(
            $child_source_data,
            [
                'id_agente_modulo',
                'nombre',
            ]
        );

        $return['interfaces_parent'] = [];
        $return['interfaces_parent'] = modules_get_all_interfaces(
            $parent_source_data,
            [
                'id_agente_modulo',
                'nombre',
            ]
        );

        echo json_encode($return);

        return;
    }

    if ($add_interface_relation) {
        // Only real agents/modules are able to have interface relations.
        $id_networkmap = (int) get_parameter('id');
        $source_value = (int) get_parameter('source_value');
        $target_value = (int) get_parameter('target_value');
        $source_text = get_parameter('source_text');
        $target_text = get_parameter('target_text');

        // Initialize statuses to NORMAl by default.
        $source_status = AGENT_MODULE_STATUS_NORMAL;
        $target_status = AGENT_MODULE_STATUS_NORMAL;

        // Search source.
        if ($source_text != 'None') {
            // Interface selected.
            // Source_value is id_agente_modulo.
            $source_agent = db_get_value(
                'id_agente',
                'tagente_modulo',
                'id_agente_modulo',
                $source_value
            );
            $source_type = NODE_MODULE;
            $source_link_value = $source_value;
            $source_status = db_get_value_filter(
                'estado',
                'tagente_estado',
                ['id_agente_modulo' => $source_value]
            );

            if (preg_match('/(.+)_ifOperStatus$/', (string) $source_text, $matches)) {
                if ($matches[1]) {
                        $source_text = $matches[1];
                }
            }
        } else {
            // No interface selected.
            // Source_value is id_agente.
            $source_text = '';
            $source_agent = $source_value;
            $source_type = NODE_AGENT;
            $source_link_value = $source_agent;
        }

        // Search node id in map.
        $child_id = db_get_value_filter(
            'id',
            'titem',
            [
                'source_data' => $source_agent,
                'id_map'      => $id_networkmap,
            ]
        );

        // Search target.
        if ($target_text != 'None') {
            // Interface selected.
            // Target value is id_agente_modulo.
            $target_agent = db_get_value(
                'id_agente',
                'tagente_modulo',
                'id_agente_modulo',
                $target_value
            );
            $target_type = NODE_MODULE;
            $target_link_value = $target_value;
            $target_status = db_get_value_filter(
                'estado',
                'tagente_estado',
                ['id_agente_modulo' => $target_value]
            );

            if (preg_match('/(.+)_ifOperStatus$/', (string) $target_text, $matches)) {
                if ($matches[1]) {
                        $target_text = $matches[1];
                }
            }
        } else {
            // No interface selected.
            // Target value is id_agente.
            $target_text = '';
            $target_agent = $target_value;
            $target_type = NODE_AGENT;
            $target_link_value = $target_agent;
        }

        // Search node id in map.
        $parent_id = db_get_value_filter(
            'id',
            'titem',
            [
                'source_data' => $target_agent,
                'id_map'      => $id_networkmap,
            ]
        );

        // Register link in DB.
        $link = [];
        $link['id_parent'] = $parent_id;
        $link['id_child'] = $child_id;
        $link['id_item'] = 0;
        $link['deleted'] = 0;
        $link['id_map'] = $id_networkmap;
        $link['parent_type'] = $target_type;
        $link['id_parent_source_data'] = $target_link_value;
        $link['child_type'] = $source_type;
        $link['id_child_source_data'] = $source_link_value;

        $insert_result = db_process_sql_insert('trel_item', $link);

        // Store module relationship (if req.).
        if ($target_type == NODE_MODULE
            && $source_type == NODE_MODULE
        ) {
            $rel = [];
            $rel['module_a'] = $target_link_value;
            $rel['module_b'] = $source_link_value;
            $rel['disable_update'] = 0;

            $insert_result_relation = db_process_sql_insert(
                'tmodule_relationship',
                $rel
            );
        }


        // Send response to controller.
        if ($insert_result !== false) {
            $return['correct'] = true;
            $return['status_start'] = $source_status;
            $return['status_end'] = $target_status;
            $return['text_start'] = $source_text;
            $return['text_end'] = $target_text;
            $return['id_db_link'] = $insert_result;
            $return['id_db_source'] = $source_agent;
            $return['id_db_target'] = $target_agent;
            $return['type_source'] = $source_type;
            $return['type_target'] = $target_type;
        } else {
            $return['correct'] = false;
        }

        echo json_encode($return);

        return;
    }

    if ($update_node) {
        $node_json = io_safe_output(get_parameter('node', ''));
        $node = json_decode($node_json, true);
        echo json_encode($node);

        return;
    }

    if ($get_agent_info) {
        $id_agent = (int) get_parameter('id_agent');

        $return = [];
        $return['alias'] = agents_get_alias($id_agent);
        $return['adressess'] = agents_get_addresses($id_agent);
        $id_group = agents_get_agent_group($id_agent);
        $return['group'] = db_get_value('nombre', 'tgrupo', 'id_grupo', $id_group);
        $id_os = agents_get_os($id_agent);
        $return['os'] = html_print_div([ 'class' => 'flex main_menu_icon invert_filter', 'content' => ui_print_os_icon($id_os, true, true)], true);

        echo json_encode($return);

        return;
    }


    if ($get_agents_in_group) {
        $id = (int) get_parameter('id', 0);
        $group = (int) get_parameter('group', -1);
        $group_recursion = (int) get_parameter('group_recursion', 0);

        $return = [];
        $return['correct'] = false;

        if ($group != -1) {
            $where_id_agente = ' 1=1 ';

            $agents_in_networkmap = db_get_all_rows_filter(
                'titem',
                [
                    'id_map'  => $id,
                    'deleted' => 0,
                ]
            );
            if ($agents_in_networkmap !== false) {
                $ids = [];
                foreach ($agents_in_networkmap as $agent) {
                    if ($agent['type'] == 0) {
                        $ids[] = $agent['source_data'];
                    }
                }

                if (empty($ids) === false) {
                    $where_id_agente = 'id_agente NOT IN ('.implode(',', $ids).')';
                }
            }

            if ($group_recursion !== 0) {
                $group_tree = groups_get_children_ids($group);
                $group = implode(',', $group_tree);
            }

            $sql = 'SELECT id_agente, alias
				FROM tagente
				WHERE id_grupo IN ('.$group.') AND '.$where_id_agente.' 
				ORDER BY alias ASC';

            $agents = db_get_all_rows_sql($sql);

            if ($agents !== false) {
                $return['agents'] = [];
                foreach ($agents as $agent) {
                    $return['agents'][$agent['id_agente']] = $agent['alias'];
                }

                $return['correct'] = true;
            }
        }

        echo json_encode($return);

        return;
    }

    if ($module_get_status) {
        $id = (int) get_parameter('id', 0);

        if ($id == 0) {
            return;
        }

        $return = [];
        $return['correct'] = true;
        $return['status'] = modules_get_agentmodule_status(
            $id,
            false,
            false,
            null
        );

        echo json_encode($return);
        return;
    }

    if ($update_z) {
        $node = (int) get_parameter('node', 0);

        $return = [];
        $return['correct'] = false;

        $z = db_get_value(
            'z',
            'titem',
            'id',
            $node
        );

        $z++;

        $return['correct'] = (bool) db_process_sql_update(
            'titem',
            ['z' => $z],
            ['id' => $node]
        );

        echo json_encode($return);

        return;
    }

    if ($update_fictional_point) {
        $id_node = (int) get_parameter('id_node', 0);
        $name = get_parameter('name', '');
        $shape = get_parameter('shape', 0);
        $radious = (int) get_parameter('radious', 20);
        $color = get_parameter('color', 0);
        $networkmap = (int) get_parameter('networkmap', 0);

        $return = [];
        $return['correct'] = false;

        $row = db_get_row(
            'titem',
            'id',
            $id_node
        );
        $row['style'] = json_decode($row['style'], true);
        $row['style']['shape'] = $shape;
        // WORK AROUND FOR THE JSON ENCODE WITH FOR EXAMPLE Ñ OR Á.
        $row['style']['label'] = 'json_encode_crash_with_ut8_chars';
        $row['style']['color'] = $color;
        $row['style']['networkmap'] = $networkmap;
        $row['style']['width'] = ($radious * 2);
        $row['style']['height'] = ($radious * 2);
        $row['style'] = json_encode($row['style']);
        $row['style'] = str_replace(
            'json_encode_crash_with_ut8_chars',
            $name,
            $row['style']
        );

        $return['correct'] = (bool) db_process_sql_update(
            'titem',
            $row,
            ['id' => $id_node]
        );

        if ($return['correct']) {
            $return['id_node'] = $id_node;
            $return['shape'] = $shape;
            $return['width'] = ($radious * 2);
            $return['height'] = ($radious * 2);
            $return['text'] = $name;
            $return['color'] = $color;
            $return['networkmap'] = $networkmap;

            $return['message'] = __('Success be updated.');
        } else {
            $return['message'] = __('Could not be updated.');
        }

        echo json_encode($return);

        return;
    }

    if ($add_several_agents) {
        $id = (int) get_parameter('id', 0);
        $x = (int) get_parameter('x', 0);
        $y = (int) get_parameter('y', 0);
        $id_agents = get_parameter('id_agents', '');

        $id_agents = json_decode($id_agents, true);
        if ($id_agents === null) {
            $id_agents = [];
        }

        $return = [];
        $return['correct'] = true;

        $count = 0;
        foreach ($id_agents as $id_agent) {
            $id_node = add_agent_networkmap(
                $id,
                '',
                ($x + ($count * 20)),
                ($y + ($count * 20)),
                $id_agent
            );

            if ($id_node !== false) {
                $node = db_get_row(
                    'titem',
                    'id',
                    $id_node
                );
                $options = json_decode($node['options'], true);

                $data = [];
                $data['id_node'] = $id_node;
                $data['source_data'] = $node['id_agent'];
                $data['parent'] = $node['parent'];
                $data['shape'] = $options['shape'];
                $data['image'] = $options['image'];
                $data['width'] = $options['width'];
                $data['height'] = $options['height'];
                $data['label'] = $options['text'];
                $data['x'] = $node['x'];
                $data['y'] = $node['y'];
                $data['status'] = get_status_color_networkmap(
                    $id_agent
                );
                $return['nodes'][] = $data;
            }

            $count++;
        }

        echo json_encode($return);

        return;
    }

    if ($get_tooltip_content) {
        $id = (int) get_parameter('id', 0);

        $sql = sprintf(
            'SELECT *
            FROM tagente_estado, tagente_modulo
                LEFT JOIN tmodule_group
                ON tmodule_group.id_mg = tagente_modulo.id_module_group
            WHERE tagente_modulo.id_agente_modulo = '.$id.'
                AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
                AND tagente_modulo.disabled = 0
                AND tagente_modulo.delete_pending = 0
                AND tagente_estado.utimestamp != 0'
        );

        $modules = db_get_all_rows_sql($sql);
        if (empty($modules)) {
            $module = [];
        } else {
            $module = $modules[0];
        }

        $return = [];
        $return['correct'] = true;

        $return['content'] = '<div class="border_1px_black">
			<div class="w100p right right_align"><a class="no_decoration black" href="javascript: hide_tooltip();">X</a></div>
			<div class="mrgn_5px">
			';

        $return['content'] .= '<b>'.__('Name: ').'</b>'.ui_print_string_substr($module['nombre'], 30, true).'<br />';

        if ($module['id_policy_module']) {
            $linked = policies_is_module_linked(
                $module['id_agente_modulo']
            );
            $id_policy = db_get_value_sql(
                '
				SELECT id_policy
				FROM tpolicy_modules
				WHERE id = '.$module['id_policy_module']
            );

            if ($id_policy != '') {
                $name_policy = db_get_value_sql(
                    'SELECT name
					FROM tpolicies
					WHERE id = '.$id_policy
                );
            } else {
                $name_policy = __('Unknown');
            }

            $policyInfo = policies_info_module_policy(
                $module['id_policy_module']
            );

            $adopt = false;
            if (policies_is_module_adopt($module['id_agente_modulo'])) {
                $adopt = true;
            }

            if ($linked) {
                if ($adopt) {
                    $img = 'images/policies_brick.png';
                    $title = __('(Adopt) ').$name_policy;
                } else {
                    $img = 'images/policies_mc.png';
                        $title = $name_policy;
                }
            } else {
                if ($adopt) {
                    $img = 'images/policies_not_brick.png';
                    $title = __('(Unlinked) (Adopt) ').$name_policy;
                } else {
                    $img = 'images/unlinkpolicy.png';
                    $title = __('(Unlinked) ').$name_policy;
                }
            }

            $return['content'] .= '<b>'.__('Policy: ').'</b>'.$title.'<br />';
        }

        $status = STATUS_MODULE_WARNING;
        $title = '';

        if ($module['estado'] == 1) {
            $status = STATUS_MODULE_CRITICAL;
            $title = __('CRITICAL');
        } else if ($module['estado'] == 2) {
            $status = STATUS_MODULE_WARNING;
            $title = __('WARNING');
        } else if ($module['estado'] == 0) {
            $status = STATUS_MODULE_OK;
            $title = __('NORMAL');
        } else if ($module['estado'] == 3) {
            $last_status = modules_get_agentmodule_last_status(
                $module['id_agente_modulo']
            );
            switch ($last_status) {
                case 0:
                    $status = STATUS_MODULE_OK;
                    $title = __('UNKNOWN').' - '.__('Last status').' '.__('NORMAL');
                break;

                case 1:
                    $status = STATUS_MODULE_CRITICAL;
                    $title = __('UNKNOWN').' - '.__('Last status').' '.__('CRITICAL');
                break;

                case 2:
                    $status = STATUS_MODULE_WARNING;
                    $title = __('UNKNOWN').' - '.__('Last status').' '.__('WARNING');
                break;
            }
        }

        if (is_numeric($module['datos'])) {
            $title .= ': '.format_for_graph($module['datos']);
        } else {
            $title .= ': '.substr(
                io_safe_output($module['datos']),
                0,
                42
            );
        }

        $return['content'] .= '<b>'.__('Status: ').'</b>'.ui_print_status_image($status, $title, true).'<br />';

        if ($module['id_tipo_modulo'] == 24) {
            // Log4x.
            switch ($module['datos']) {
                case 10:
                    $salida = 'TRACE';
                    $style = 'font-weight:bold; color:darkgreen;';
                break;

                case 20:
                    $salida = 'DEBUG';
                    $style = 'font-weight:bold; color:darkgreen;';
                break;

                case 30:
                    $salida = 'INFO';
                    $style = 'font-weight:bold; color:darkgreen;';
                break;

                case 40:
                    $salida = 'WARN';
                    $style = 'font-weight:bold; color:darkorange;';
                break;

                case 50:
                    $salida = 'ERROR';
                    $style = 'font-weight:bold; color:red;';
                break;

                case 60:
                    $salida = 'FATAL';
                    $style = 'font-weight:bold; color:red;';
                break;
            }

            $salida = "<span style='".$style."'>".$salida.'</span>';
        } else {
            if (is_numeric($module['datos'])) {
                $salida = format_numeric($module['datos']);
            } else {
                $salida = ui_print_module_string_value(
                    $module['datos'],
                    $module['id_agente_modulo'],
                    $module['current_interval'],
                    $module['module_name']
                );
            }
        }

        $return['content'] .= '<b>'.__('Data: ').'</b>'.$salida.'<br />';

        $return['content'] .= '<b>'.__('Last contact: ').'</b>'.ui_print_timestamp(
            $module['utimestamp'],
            true,
            ['style' => 'font-size: 7pt']
        ).'<br />';

        $return['content'] .= '
			</div>
		</div>';

        echo json_encode($return);

        return;
    }

    if ($set_shape_node) {
        $id = (int) get_parameter('id', 0);
        $shape = get_parameter('shape', 'circle');

        $return = [];
        $return['correct'] = false;

        $node = db_get_row_filter(
            'titem',
            ['id' => $id]
        );
        $style = json_decode($node['style'], true);

        $style['shape'] = $shape;
        $style = json_encode($style);

        $return['correct'] = db_process_sql_update(
            'titem',
            ['style' => $style],
            ['id' => $id]
        );

        echo json_encode($return);

        return;
    }

    if ($get_shape_node) {
        $id = (int) get_parameter('id', 0);

        $return = [];
        $return['correct'] = true;

        $node = db_get_row_filter(
            'titem',
            ['id' => $id]
        );
        $node['style'] = json_decode($node['style'], true);

        $return['shape'] = $node['style']['shape'];

        echo json_encode($return);

        return;
    }

    if ($get_agent_pos_search) {
        $id = (int) get_parameter('id', 0);
        $name = (string) get_parameter('name');

        $return = [];
        $return['correct'] = true;

        $node = db_get_row_filter(
            'titem',
            [
                'id_map'  => $id,
                'options' => '%\"label\":\"%'.$name.'%\"%',
            ]
        );
        $return['x'] = $node['x'];
        $return['y'] = $node['y'];

        echo json_encode($return);

        return;
    }

    if ($search_agents) {
        include_once 'include/functions_agents.php';

        $id = (int) get_parameter('id', 0);
        // Q is what autocomplete plugin gives.
        $string = (string) get_parameter('q');

        $agents = db_get_all_rows_filter(
            'titem',
            [
                'id_map'  => $id,
                'options' => '%\"label\":\"%'.$string.'%\"%',
            ]
        );

        if ($agents === false) {
            $agents = [];
        }

        $data = [];
        foreach ($agents as $agent) {
            $style = json_decode($agent['style'], true);
            $data[] = ['name' => $style['label']];
        }

        echo json_encode($data);

        return;
    }

    if ($update_refresh_state) {
        $refresh_state = (int) get_parameter('refresh_state', 60);
        $id = (int) get_parameter('id', 0);

        $filter = db_get_value(
            'filter',
            'tmap',
            'id',
            $id
        );
        $filter = json_decode($filter, true);
        $filter['source_period'] = $refresh_state;
        $filter = json_encode($filter);

        $correct = db_process_sql_update(
            'tmap',
            ['filter' => $filter],
            ['id' => $id]
        );

        $return = [];
        $return['correct'] = false;

        if ($correct) {
            $return['correct'] = true;
        }

        echo json_encode($return);

        return;
    }

    if ($set_center) {
        $id = (int) get_parameter('id', 0);
        $x = (int) get_parameter('x', 0);
        $y = (int) get_parameter('y', 0);
        $scale = (float) get_parameter('scale', 0);

        $networkmap = db_get_row('tmap', 'id', $id);

        $array_filter = json_decode($networkmap['filter']);
        if (isset($array_filter->z_dash)) {
            $array_filter->z_dash = number_format(
                $scale,
                2,
                $config['decimal_separator'],
                $config['thousand_separator']
            );
        }

        $filter = json_encode($array_filter);

        // ACL for the network map.
        $networkmap_write = check_acl($config['id_user'], $networkmap['id_group_map'], 'MW');
        $networkmap_manage = check_acl($config['id_user'], $networkmap['id_group_map'], 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access networkmap'
            );
            echo json_encode($return);
            return;
        }

        $networkmap['center_x'] = $x;
        $networkmap['center_y'] = $y;
        db_process_sql_update(
            'tmap',
            [
                'center_x' => $networkmap['center_x'],
                'center_y' => $networkmap['center_y'],
                'filter'   => $filter,
            ],
            ['id' => $id]
        );

        $return = [];
        $return['correct'] = true;

        echo json_encode($return);

        return;
    }

    if ($erase_relation) {
        $id = (int) get_parameter('id', 0);
        $child = (int) get_parameter('child', 0);
        $parent = (int) get_parameter('parent', 0);

        $where = [];
        $where['id_map'] = $id;
        $where['id_child'] = $child;
        $where['id_parent'] = $parent;

        $return = [];
        $return['correct'] = db_process_sql_delete(
            'trel_item',
            $where
        );

        echo json_encode($return);

        return;
    }

    // Popup.
    $get_status_node = (bool) get_parameter('get_status_node', false);
    $get_status_module = (bool) get_parameter(
        'get_status_module',
        false
    );

    if ($get_status_node) {
        $id = (int) get_parameter('id', 0);

        $return = [];
        $return['correct'] = true;

        $return['status_agent'] = get_status_color_networkmap($id);

        echo json_encode($return);

        return;
    }

    if ($get_status_module) {
        $id = (int) get_parameter('id', 0);

        $return = [];
        $return['correct'] = true;
        $return['id'] = $id;
        $return['status_color'] = get_status_color_module_networkmap(
            $id
        );

        echo json_encode($return);

        return;
    }

    if ($update_node_alert) {
        $map_id = (int) get_parameter('map_id', 0);

        $filter = db_get_value('filter', 'tmap', 'id', $map_id);
        $filter = json_decode($filter, true);

        $return = [];
        $return['correct'] = false;
        if (!isset($filter['alert'])) {
            $return['correct'] = true;
            $filter['alert'] = 1;
            $filter = json_encode($filter);
            $values = ['filter' => $filter];
            db_process_sql_update('tmap', $values, ['id' => $map_id]);
        }

        echo json_encode($return);

        return;
    }

    if ($process_migration) {
        $old_maps_ent = get_parameter('old_maps_ent', true);

        $old_maps_open = get_parameter('old_maps_open', true);

        $return_data = [];

        $return_data['ent'] = true;
        if ($old_maps_ent != 0) {
            $old_maps_ent = explode(',', $old_maps_ent);
            if (enterprise_installed()) {
                foreach ($old_maps_ent as $id_ent_map) {
                    $return = migrate_older_networkmap_enterprise($id_ent_map);

                    if (!$return) {
                        $return_data['ent'] = false;
                        break;
                    } else {
                        $old_networkmap_ent = db_get_row_filter(
                            'tnetworkmap_enterprise',
                            ['id' => $id_ent_map]
                        );

                        $options = json_decode($old_networkmap_ent, true);
                        $options['migrated'] = 'migrated';

                        $values['options'] = json_encode($options);

                        db_process_sql_update('tnetworkmap_enterprise', $values, ['id' => $id_ent_map]);
                    }
                }
            }
        }

        $return_data['open'] = true;
        if ($old_maps_open != 0) {
            $old_maps_open = explode(',', $old_maps_open);
            foreach ($old_maps_open as $id_open_map) {
                $return = migrate_older_open_maps($id_open_map);

                if (!$return) {
                    $return_data['open'] = false;
                    break;
                } else {
                    $values['text_filter'] = 'migrated';

                    db_process_sql_update('tnetwork_map', $values, ['id_networkmap' => $id_open_map]);
                }
            }
        }

        echo json_encode($return_data);

        return;
    }
}

// --------------END AJAX------------------------------------------------
$id = (int) get_parameter('id_networkmap', 0);

// Print some params to handle it in js.
html_print_input_hidden('product_name', get_product_name());
html_print_input_hidden('center_logo', ui_get_full_url(ui_get_logo_to_center_networkmap()));

$dash_mode = 0;
$map_dash_details = [];
$networkmap = db_get_row('tmap', 'id', $id);

if (enterprise_installed()) {
    if ($id_networkmap) {
        $id = $id_networkmap;
        $dash_mode = $dashboard_mode;
        $x_offs = $x_offset;
        $y_offs = $y_offset;
        $z_dash = $zoom_dash;
        $map_dash_details['x_offs'] = $x_offs;
        $map_dash_details['y_offs'] = $y_offs;
        $map_dash_details['z_dash'] = $z_dash;
        $networkmap = db_get_row('tmap', 'id', $id);
    } else {
        $networkmap_filter = json_decode($networkmap['filter'], true);
        if ($networkmap_filter['x_offs'] != null) {
            $map_dash_details['x_offs'] = $networkmap_filter['x_offs'];
        } else {
            $map_dash_details['x_offs'] = 0;
        }

        if ($networkmap_filter['y_offs'] != null) {
            $map_dash_details['y_offs'] = $networkmap_filter['y_offs'];
        } else {
            $map_dash_details['y_offs'] = 0;
        }

        if ($networkmap_filter['z_dash'] != null) {
            $map_dash_details['z_dash'] = $networkmap_filter['z_dash'];
        } else {
            $map_dash_details['z_dash'] = 0;
        }
    }
}

if ($networkmap === false) {
    ui_print_page_header(
        __('Networkmap'),
        'images/bricks.png',
        false,
        'network_map_enterprise_view',
        false
    );
    ui_print_error_message(__('Not found networkmap.'));

    return;
} else {
    // ACL for the network map.
    $networkmap_read = check_acl($config['id_user'], $networkmap['id_group_map'], 'MR');
    $networkmap_write = check_acl($config['id_user'], $networkmap['id_group_map'], 'MW');
    $networkmap_manage = check_acl($config['id_user'], $networkmap['id_group_map'], 'MM');

    if (!$networkmap_read && !$networkmap_write && !$networkmap_manage) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access networkmap'
        );
        include 'general/noaccess.php';
        return;
    }

    $user_readonly = !$networkmap_write && !$networkmap_manage;

    $pure = (int) get_parameter('pure', 0);

    // Main code.
    if ($pure == 1) {
        $buttons['screen'] = [
            'active' => false,
            'text'   => '<a href="index.php?sec=networkmapconsole&amp;sec2=operation/agentes/pandora_networkmap&amp;tab=view&amp;id_networkmap='.$id.'">'.html_print_image(
                'images/exit_fullscreen@svg.svg',
                true,
                [
                    'title' => __('Normal screen'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            ).'</a>',
        ];
        $buttons['test'] = [
            'active' => false,
            'text'   => '<div style="width:100%;height:54px;display:flex;align-items:center"><div class="vc-countdown"></div></div>',
        ];
    } else {
        if (!$dash_mode) {
            $buttons['screen'] = [
                'active' => false,
                'text'   => '<a href="index.php?sec=networkmapconsole&amp;sec2=operation/agentes/pandora_networkmap&amp;pure=1&amp;tab=view&amp;id_networkmap='.$id.'">'.html_print_image(
                    'images/fullscreen@svg.svg',
                    true,
                    [
                        'title' => __('Full screen'),
                        'class' => 'main_menu_icon invert_filter',
                    ]
                ).'</a>',
            ];
            $buttons['list'] = [
                'active' => false,
                'text'   => '<a href="index.php?sec=networkmapconsole&amp;sec2=operation/agentes/pandora_networkmap">'.html_print_image(
                    'images/file-collection@svg.svg',
                    true,
                    [
                        'title' => __('List of networkmap'),
                        'class' => 'main_menu_icon invert_filter',
                    ]
                ).'</a>',
            ];
            $buttons['option'] = [
                'active' => false,
                'text'   => '<a href="index.php?sec=network&sec2=operation/agentes/pandora_networkmap&tab=edit&edit_networkmap=1&id_networkmap='.$id.'">'.html_print_image(
                    'images/edit.svg',
                    true,
                    [
                        'title' => __('Options'),
                        'class' => 'main_menu_icon invert_filter',
                    ]
                ).'</a>',
            ];
            $buttons['test'] = [
                'active' => false,
                'text'   => '<div style="width:100%;height:54px;display:flex;align-items:center"><div class="vc-countdown"></div></div>',
            ];
        }
    }

    if (!$dash_mode) {
        ui_print_standard_header(
            $networkmap['name'],
            'images/bricks.png',
            false,
            'network_map_enterprise_view',
            false,
            $buttons,
            [
                [
                    'link'  => '',
                    'label' => __('Topology maps'),
                ],
                [
                    'link'  => '',
                    'label' => __('Network maps'),
                ],
            ],
            [
                'id_element' => $networkmap['id'],
                'url'        => 'operation/agentes/pandora_networkmap&tab=view&id_networkmap='.$networkmap['id'],
                'label'      => $networkmap['name'],
                'section'    => 'Network_map',
            ]
        );
    }

    include_once $config['homedir'].'/include/class/NetworkMap.class.php';

    $filter = json_decode($networkmap['filter'], true);
    $zoom = $filter['z_dash'];

    $map_manager = new NetworkMap(
        [
            'id_map'   => $networkmap['id'],
            'center_x' => $networkmap['center_x'],
            'center_y' => $networkmap['center_y'],
        ]
    );

    $map_manager->printMap();
}
?>

<script>
$(document).ready(function() {
    $("*").on("click", function(){
    if($("[aria-describedby=dialog_node_edit]").css('display') == 'block'){
        $('#foot').css({'top':parseInt($("[aria-describedby=dialog_node_edit]").css('height')+$("[aria-describedby=dialog_node_edit]").css('top')),'position':'relative'});
    }
    else{
        $('#foot').css({'position':'','top':'0'});
    }
});

$("[aria-describedby=dialog_node_edit]").on('dialogclose', function(event) {
    $('#foot').css({'position':'','top':'0'});
});


});
</script>
