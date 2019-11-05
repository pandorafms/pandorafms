<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
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
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
enterprise_include_once('include/functions_dashboard.php');
require_once 'include/functions_modules.php';

$public_hash = get_parameter('hash', false);

// Try to authenticate by hash on public dashboards.
if ($public_hash === false) {
    // Login check.
    check_login();
} else {
    $validate_hash = enterprise_hook(
        'dasboard_validate_public_hash',
        [
            $public_hash,
            get_parameter('networkmap_id'),
            'network_map',
        ]
    );
    if ($validate_hash === false || $validate_hash === ENTERPRISE_NOT_HOOK) {
        db_pandora_audit(
            'Invalid public hash',
            'Trying to access report builder'
        );
        include 'general/noaccess.php';
        exit;
    }
}

// --------------INIT AJAX-----------------------------------------------
if (is_ajax()) {
    $update_refresh_state = (bool) get_parameter('update_refresh_state', false);
    $set_center = (bool) get_parameter('set_center', false);
    $erase_relation = (bool) get_parameter('erase_relation', false);
    $search_agents = (bool) get_parameter('search_agents');
    $get_agent_pos_search = (bool) get_parameter('get_agent_pos_search', false);
    $get_shape_node = (bool) get_parameter('get_shape_node', false);
    $set_shape_node = (bool) get_parameter('set_shape_node', false);
    $get_info_module = (bool) get_parameter('get_info_module', false);
    $get_tooltip_content = (bool) get_parameter('get_tooltip_content', false);
    $add_several_agents = (bool) get_parameter('add_several_agents', false);
    $update_fictional_point = (bool) get_parameter('update_fictional_point', false);
    $update_z = (bool) get_parameter('update_z', false);
    $module_get_status = (bool) get_parameter('module_get_status', false);
    $update_node_alert = (bool) get_parameter('update_node_alert', false);
    $process_migration = (bool) get_parameter('process_migration', false);

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

        $return['content'] = '<div style="border: 1px solid black;">
			<div style="width: 100%; text-align: right;"><a style="text-decoration: none; color: black;" href="javascript: hide_tooltip();">X</a></div>
			<div style="margin: 5px;">
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
                    $img = 'images/policies.png';
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
            $array_filter->z_dash = number_format($scale, 2);
        }

        $filter = json_encode($array_filter);

        // ACL for the network map.
        $networkmap_write = check_acl($config['id_user'], $networkmap['id_group'], 'MW');
        $networkmap_manage = check_acl($config['id_user'], $networkmap['id_group'], 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                'ACL Violation',
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
    include_once 'enterprise/dashboard/widgets/network_map.php';
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
    $networkmap_read = check_acl($config['id_user'], $networkmap['id_group'], 'MR');
    $networkmap_write = check_acl($config['id_user'], $networkmap['id_group'], 'MW');
    $networkmap_manage = check_acl($config['id_user'], $networkmap['id_group'], 'MM');

    if (!$networkmap_read && !$networkmap_write && !$networkmap_manage) {
        db_pandora_audit(
            'ACL Violation',
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
                'images/normal_screen.png',
                true,
                ['title' => __('Normal screen')]
            ).'</a>',
        ];
    } else {
        if (!$dash_mode) {
            $buttons['screen'] = [
                'active' => false,
                'text'   => '<a href="index.php?sec=networkmapconsole&amp;sec2=operation/agentes/pandora_networkmap&amp;pure=1&amp;tab=view&amp;id_networkmap='.$id.'">'.html_print_image(
                    'images/full_screen.png',
                    true,
                    ['title' => __('Full screen')]
                ).'</a>',
            ];
            $buttons['list'] = [
                'active' => false,
                'text'   => '<a href="index.php?sec=networkmapconsole&amp;sec2=operation/agentes/pandora_networkmap">'.html_print_image(
                    'images/list.png',
                    true,
                    ['title' => __('List of networkmap')]
                ).'</a>',
            ];
        }
    }

    if (!$dash_mode) {
        ui_print_page_header(
            $networkmap['name'],
            'images/bricks.png',
            false,
            'network_map_enterprise_view',
            false,
            $buttons,
            false,
            '',
            $config['item_title_size_text']
        );
    }

    include_once $config['homedir'].'/include/class/NetworkMap.class.php';

    $map_manager = new NetworkMap(
        [ 'id_map' => $networkmap['id']]
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
