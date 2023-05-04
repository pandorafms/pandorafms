<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

check_login();

$get_image_path_status = get_parameter('get_image_path_status', 0);
if ($get_image_path_status) {
    $img_src = get_parameter('img_src');
    $only_src = get_parameter('only_src', 0);

    $result = [];

    $result['bad'] = html_print_image($img_src.'_bad.png', true, '', $only_src);
    $result['ok'] = html_print_image($img_src.'_ok.png', true, '', $only_src);
    $result['warning'] = html_print_image($img_src.'_warning.png', true, '', $only_src);
    $result['normal'] = html_print_image($img_src.'.png', true, '', $only_src);

    echo json_encode($result);
    return;
}

$id_visual_console = get_parameter('id_visual_console', null);

// WARNING: CHECK THE ENTIRE FUNCTIONALITY
// Visual console id required.
if (empty($id_visual_console)) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access report builder'
    );
    include 'general/noaccess.php';
    exit;
}

// Get the group id for the ACL checks
$group_id = db_get_value('id_group', 'tlayout', 'id', $id_visual_console);
if ($group_id === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access report builder'
    );
    include 'general/noaccess.php';
    exit;
}

// ACL for the existing visual console
// if (!isset($vconsole_read))
// $vconsole_read = check_acl ($config['id_user'], $group_id, "VR");
if (!isset($vconsole_write)) {
    $vconsole_write = check_acl($config['id_user'], $group_id, 'VW');
}

if (!isset($vconsole_manage)) {
    $vconsole_manage = check_acl($config['id_user'], $group_id, 'VM');
}

if (!$vconsole_write && !$vconsole_manage) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access report builder'
    );
    include 'general/noaccess.php';
    exit;
}

// Fix ajax to avoid include the file, 'functions_graph.php'.
$ajax = true;

require_once $config['homedir'].'/include/functions_visual_map.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_graph.php';
require_once $config['homedir'].'/include/functions_custom_graphs.php';
enterprise_include_once('include/functions_visual_map.php');

$action = get_parameter('action');
$type = get_parameter('type');

$id_element = get_parameter('id_element', null);

if (!is_numeric($id_element)) {
    $id_element = intval(preg_replace('/[^0-9]+/', '', $id_element), 10);
}

$image = get_parameter('image', null);
$background = get_parameter('background', null);
$background_color = get_parameter('background_color', null);
$label = get_parameter('label', '');
$left = get_parameter('left', null);
$top = get_parameter('top', null);
$agent = get_parameter('agent', null);
$id_module = get_parameter('module', null);
$period = get_parameter('period', null);
$event_max_time_row = get_parameter('event_max_time_row', null);
$width = get_parameter('width', null);
$height = get_parameter('height', null);
$parent = get_parameter('parent', null);
$map_linked = get_parameter('map_linked', null);
$linked_map_node_id = get_parameter('linked_map_node_id', null);
$linked_map_status_calculation_type = get_parameter('linked_map_status_calculation_type', null);

$map_linked_weight = get_parameter('map_linked_weight', null);
if ($map_linked_weight !== null) {
    $map_linked_weight = (int) $map_linked_weight;
}

$linked_map_status_service_critical = get_parameter('linked_map_status_service_critical', null);
if ($linked_map_status_service_critical !== null) {
    $linked_map_status_service_critical = (float) $linked_map_status_service_critical;
}

$linked_map_status_service_warning = get_parameter('linked_map_status_service_warning', null);
if ($linked_map_status_service_warning !== null) {
    $linked_map_status_service_warning = (float) $linked_map_status_service_warning;
}

$element_group = get_parameter('element_group', null);
$width_percentile = get_parameter('width_percentile', 0);
$bars_graph_height = get_parameter('bars_graph_height', null);
$max_percentile = get_parameter('max_percentile', null);
$height_module_graph = get_parameter('height_module_graph', null);
$width_module_graph = get_parameter('width_module_graph', null);
$bars_graph_type = get_parameter('bars_graph_type', null);
$id_agent_module = get_parameter('id_agent_module', 0);
$process_simple_value = get_parameter('process_simple_value', PROCESS_VALUE_NONE);
$type_percentile = get_parameter('type_percentile', 'percentile');
$value_show = get_parameter('value_show', 'percent');
$metaconsole = get_parameter('metaconsole', 0);
$server_name = get_parameter('server_name', null);
$server_id = (int) get_parameter('server_id', 0);
$id_agent = get_parameter('id_agent', null);
$id_agent_string = get_parameter('id_agent_string', null);
$id_metaconsole = get_parameter('id_metaconsole', null);
$id_group = (int) get_parameter('id_group', 0);
$id_custom_graph = get_parameter('id_custom_graph', null);
$border_width = (int) get_parameter('border_width', 0);
$border_color = get_parameter('border_color', '');
$grid_color = get_parameter('grid_color', '');
$resume_color = get_parameter('resume_color', '');
$fill_color = get_parameter('fill_color', '');
$percentile_color = get_parameter('percentile_color', '');
$percentile_label = io_safe_output(get_parameter('percentile_label', ''));
$percentile_label_color = get_parameter('percentile_label_color', '');
$width_box = (int) get_parameter('width_box', 0);
$height_box = (int) get_parameter('height_box', 0);
$line_start_x = (int) get_parameter('line_start_x', 0);
$line_start_y = (int) get_parameter('line_start_y', 0);
$line_end_x = (int) get_parameter('line_end_x', 0);
$line_end_y = (int) get_parameter('line_end_y', 0);
$line_width = (int) get_parameter('line_width', 0);
$line_color = get_parameter('line_color', '');

$get_element_status = get_parameter('get_element_status', 0);

$enable_link = get_parameter('enable_link', 1);
$show_on_top = get_parameter('show_on_top', 0);
$type_graph = get_parameter('type_graph', 'area');
$label_position = get_parameter('label_position', 'down');
$show_statistics = get_parameter('show_statistics', 0);

$clock_animation = get_parameter('clock_animation', 'analogic_1');
$time_format = get_parameter('time_format', 'time');
$timezone = get_parameter('timezone', 'Europe/Madrid');

$show_last_value = get_parameter('show_last_value', null);

$diameter = (int) get_parameter('diameter', $width);
$default_color = get_parameter('default_color', '#FFFFFF');
$color_range_from_values = get_parameter('color_range_from_values', []);
$color_range_to_values = get_parameter('color_range_to_values', []);
$color_range_colors = get_parameter('color_range_colors', []);
$cache_expiration = (int) get_parameter('cache_expiration');

switch ($action) {
    case 'get_font':
        $return = [];
        $return['font'] = $config['fontpath'];
        echo json_encode($return);
    break;

    case 'get_image_from_module':
        $layoutData = db_get_row_filter('tlayout_data', ['id' => $id_element]);
        $sql = 'SELECT datos FROM tagente_estado WHERE id_agente_modulo = '.$layoutData['id_agente_modulo'];
        ob_clean();
        $result = db_get_sql($sql);
        $image = strpos($result, 'data:image');

        if ($image === false) {
            $return['correct'] = false;
        } else {
            $return['correct'] = true;
        }

        echo json_encode($return);

    break;

    case 'get_module_type_string':
        $data = [];

        $layoutData = db_get_row_filter('tlayout_data', ['id' => $id_element]);

        if ($layoutData['id_metaconsole'] != 0) {
            $connection = db_get_row_filter('tmetaconsole_setup', $layoutData['id_metaconsole']);

            if (metaconsole_load_external_db($connection) != NOERR) {
                continue;
            }
        }

        $is_string = db_get_value_filter(
            'id_tipo_modulo',
            'tagente_modulo',
            [
                'id_agente'        => $id_agent,
                'id_agente_modulo' => $id_module,
            ]
        );

        if ($layoutData['id_metaconsole'] != 0) {
            metaconsole_restore_db();
        }

        $return = [];
        if (($is_string == 17) || ($is_string == 23) || ($is_string == 3)
            || ($is_string == 10) || ($is_string == 33)
        ) {
            $return['no_data'] = false;
        } else {
            $return['no_data'] = true;
        }

        echo json_encode($return);
    break;

    case 'get_module_events':
        $data = [];

        $date = get_system_time();
        $datelimit = ($date - $event_max_time_row);

        $events = db_get_row_filter(
            'tevento',
            ['id_agente' => $id_agent,
                'id_agentmodule' => $id_module,
                'utimestamp > '.$datelimit,
                'utimestamp < '.$date
            ],
            'criticity, utimestamp'
        );

        $return = [];
        if (!$events) {
            $return['no_data'] = true;
            if (!empty($id_metaconsole)) {
                $connection = db_get_row_filter(
                    'tmetaconsole_setup',
                    $id_metaconsole
                );
                if (metaconsole_load_external_db($connection) != NOERR) {
                    continue;
                }
            }

            $return['url'] = true;
            if (!empty($id_metaconsole)) {
                metaconsole_restore_db();
            }
        } else {
            $return['no_data'] = false;
        }

        echo json_encode($return);
    break;

    case 'get_image_sparse':
        // Metaconsole db connection
        if (!empty($id_metaconsole)) {
            $connection = db_get_row_filter(
                'tmetaconsole_setup',
                $id_metaconsole
            );
            if (metaconsole_load_external_db($connection) != NOERR) {
                // ui_print_error_message ("Error connecting to ".$server_name);
                continue;
            }
        }

        $params = [
            'period'          => $period,
            'width'           => $width,
            'height'          => $height,
            'vconsole'        => true,
            'backgroundColor' => $background_color,
        ];

        $params_combined = ['id_graph' => $id_custom_graph];

        if ($id_custom_graph != 0) {
            $img = graphic_combined_module(
                false,
                $params,
                $params_combined
            );
        } else {
            $params = [
                'agent_module_id' => $id_agent_module,
                'period'          => $period,
                'show_events'     => false,
                'width'           => $width,
                'height'          => $height,
                'menu'            => false,
                'backgroundColor' => $background_color,
                'vconsole'        => true,
                'type_graph'      => $config['type_module_charts'],
            ];
            $img = grafico_modulo_sparse($params);
        }

        // Restore db connection
        if (!empty($id_metaconsole)) {
            metaconsole_restore_db();
        }

        $data_image = [];
        preg_match("/src=[\'\"](.*)[\'\"]/", $img, $matches);
        $url = $matches[1];

        if (empty($url) && ($type == 'module_graph')) {
            $data_image['url'] = $img;
            $data_image['no_data'] = true;
            $data_image['message'] = __('No data to show');
        } else {
            $data_image['url'] = $matches[1];
            $data_image['no_data'] = false;
        }

        echo json_encode($data_image);
    break;

    case 'get_layout_data':
        if (is_metaconsole()) {
            $server_data = metaconsole_get_connection_by_id($server_id);
            // Establishes connection
            if (metaconsole_load_external_db($server_data) !== NOERR) {
                continue;
            }
        }

        $layoutData = db_get_row_filter(
            'tlayout_data',
            ['id' => $id_element]
        );

        if (is_metaconsole()) {
            metaconsole_restore_db();
        }

        $layoutData['height'] = $layoutData['height'];
        $layoutData['width']  = $layoutData['width'];
        echo json_encode($layoutData);
    break;

    case 'get_module_value':
        global $config;

        $unit_text = false;
        $layoutData = db_get_row_filter('tlayout_data', ['id' => $id_element]);
        switch ($layoutData['type']) {
            case SIMPLE_VALUE:
            case SIMPLE_VALUE_MAX:
            case SIMPLE_VALUE_MIN:
            case SIMPLE_VALUE_AVG:
                $type = visual_map_get_simple_value_type($process_simple_value);


                // Metaconsole db connection
                if ($layoutData['id_metaconsole'] != 0) {
                    $connection = db_get_row_filter(
                        'tmetaconsole_setup',
                        ['id' => $layoutData['id_metaconsole']]
                    );
                    if (metaconsole_load_external_db($connection) != NOERR) {
                        // ui_print_error_message ("Error connecting to ".$server_name);
                        continue;
                    }
                }

                $returnValue = visual_map_get_simple_value(
                    $type,
                    $layoutData['id_agente_modulo'],
                    $period
                );

                // Restore db connection
                if ($layoutData['id_metaconsole'] != 0) {
                    metaconsole_restore_db();
                }
            break;

            case PERCENTILE_BAR:
            case PERCENTILE_BUBBLE:
            default:
                // Metaconsole db connection
                if ($layoutData['id_metaconsole'] != 0) {
                    $connection = db_get_row_filter(
                        'tmetaconsole_setup',
                        ['id' => $layoutData['id_metaconsole']]
                    );
                    if (metaconsole_load_external_db($connection) != NOERR) {
                        // ui_print_error_message ("Error connecting to ".$server_name);
                        continue;
                    }
                }

                $returnValue = db_get_sql(
                    'SELECT datos
					FROM tagente_estado
					WHERE id_agente_modulo = '.$layoutData['id_agente_modulo']
                );
                $no_data = false;
                $status_no_data = '';
                if ((!$returnValue || $returnValue == 0)
                    && ($layoutData['type'] == PERCENTILE_BUBBLE || $layoutData['type'] == PERCENTILE_BAR)
                ) {
                    $status_no_data = COL_UNKNOWN;
                    $no_data = true;
                }

                if (($layoutData['type'] == PERCENTILE_BAR)
                    || ($layoutData['type'] == PERCENTILE_BUBBLE)
                ) {
                    if ($value_show == 'value') {
                        $unit_text_db = db_get_sql(
                            'SELECT unit
                            FROM tagente_modulo
                            WHERE id_agente_modulo = '.$layoutData['id_agente_modulo']
                        );
                        $unit_text_db = trim(io_safe_output($unit_text_db));

                        $divisor = get_data_multiplier($unit_text_db);

                        $returnValue = format_for_graph($returnValue, 2, '.', ',', $divisor);

                        if (!empty($unit_text_db)) {
                            $unit_text = $unit_text_db;
                        }
                    }
                }

                // Restore db connection
                if ($layoutData['id_metaconsole'] != 0) {
                    metaconsole_restore_db();
                }
            break;
        }

        // Linked to other layout ?? - Only if not module defined
        if ($layoutData['id_layout_linked'] != 0) {
            $status = visual_map_get_layout_status($layoutData['id_layout_linked'], $layoutData);

            // Single object
        } else if (($layoutData['type'] == STATIC_GRAPH)
            || ($layoutData['type'] == PERCENTILE_BAR)
            || ($layoutData['type'] == LABEL)
        ) {
            // Status for a simple module
            if ($layoutData['id_agente_modulo'] != 0) {
                $status = modules_get_agentmodule_status($layoutData['id_agente_modulo']);
                $id_agent = db_get_value('id_agente', 'tagente_estado', 'id_agente_modulo', $layoutData['id_agente_modulo']);

                // Status for a whole agent, if agente_modulo was == 0
            } else if ($layoutData['id_agent'] != 0) {
                $status = agents_get_status($layoutData['id_agent']);
                if ($status == -1) {
                    // agents_get_status return -1 for unknown!
                    $status = 3;
                }

                $id_agent = $layoutData['id_agent'];
            } else {
                $status = 3;
                $id_agent = 0;
            }
        } else {
            // If it's a graph, a progress bar or a data tag, ALWAYS report
            // status OK (=0) to avoid confussions here.
            $status = 0;
        }

        switch ($status) {
            case VISUAL_MAP_STATUS_CRITICAL_BAD:
                // Critical (BAD)
                $colorStatus = COL_CRITICAL;
            break;

            case VISUAL_MAP_STATUS_CRITICAL_ALERT:
                // Critical (ALERT)
                $colorStatus = COL_ALERTFIRED;
            break;

            case VISUAL_MAP_STATUS_NORMAL:
                // Normal (OK)
                $colorStatus = COL_NORMAL;
            break;

            case VISUAL_MAP_STATUS_WARNING:
                // Warning
                $colorStatus = COL_WARNING;
            break;

            case VISUAL_MAP_STATUS_UNKNOWN:
                // Unknown
            default:
                $colorStatus = COL_UNKNOWN;
                // Default is Grey (Other)
            break;
        }

        // ~ $returnValue_value = explode('&nbsp;', $returnValue);
        $return = [];
        if ($returnValue_value[1] != '') {
            // ~ $return['value'] = remove_right_zeros(number_format($returnValue_value[0], $config['graph_precision'])) . " " . $returnValue_value[1];
        } else {
            // ~ $return['value'] = remove_right_zeros(number_format($returnValue_value[0], $config['graph_precision']));
        }

        $return['value'] = $returnValue;
        $return['max_percentile'] = $layoutData['height'];
        $return['width_percentile'] = $layoutData['width'];
        $return['unit_text'] = $unit_text;
        if ($no_data) {
            $return['colorRGB'] = implode('|', html_html2rgb($status_no_data));
        } else {
            $return['colorRGB'] = implode('|', html_html2rgb($colorStatus));
        }

        echo json_encode($return);
    break;

    case 'get_color_line':
        $layoutData = db_get_row_filter('tlayout_data', ['id' => $id_element]);
        $parent = db_get_row_filter('tlayout_data', ['id' => $layoutData['parent_item']]);

        $return = [];
        $return['color_line'] = visual_map_get_color_line_status($parent);
        echo json_encode($return);
    break;

    case 'get_image':
        $layoutData = db_get_row_filter('tlayout_data', ['id' => $id_element]);

        $return = [];
        $return['image'] = visual_map_get_image_status_element($layoutData);
        if (substr($return['image'], 0, 1) == '4') {
            $return['image'] = substr_replace($return['image'], '', 0, 1);
        }

        echo json_encode($return);
    break;

    case 'get_color_cloud':
        $layoutData = db_get_row_filter('tlayout_data', ['id' => $id_element]);
        echo visual_map_get_color_cloud_element($layoutData);
    break;

    case 'update':
    case 'move':
        $values = [];

        $values['label_position'] = $label_position;
        $values['show_on_top'] = $show_on_top;

        switch ($type) {
            case 'line_item':
            case 'box_item':
            case 'clock':
            case 'icon':
            case 'label':
                $values['cache_expiration'] = 0;
            break;

            default:
                $values['cache_expiration'] = $cache_expiration;
            break;
        }

        // In Graphs, background color is stored in column image (sorry).
        if ($type == 'module_graph') {
            $values['image'] = $background_color;
            $values['type_graph'] = $type_graph;
        }

        switch ($type) {
            case 'background':
                $values = [];
                if ($background !== null) {
                    $values['background'] = $background;
                }

                if ($width !== null) {
                    $values['width'] = $width;
                }

                if ($height !== null) {
                    $values['height'] = $height;
                }

                $result = db_process_sql_update(
                    'tlayout',
                    $values,
                    ['id' => $id_visual_console]
                );
                echo (int) $result;
            break;

            case 'simple_value':
            case 'percentile_bar':
            case 'percentile_item':
            case 'static_graph':
            case 'module_graph':
            case 'label':
            case 'icon':
            case 'auto_sla_graph':
            case 'bars_graph':
            case 'donut_graph':
            default:
                if ($type == 'label') {
                    $values['type'] = LABEL;
                    $values['label'] = $label;
                }

                if ($enable_link !== null) {
                    $values['enable_link'] = $enable_link;
                }

                if ($show_on_top !== null) {
                    $values['show_on_top'] = $show_on_top;
                }

                if ($label !== null) {
                    $values['label'] = $label;
                }

                switch ($type) {
                    // -- line_item --
                    case 'handler_end':
                        // ---------------
                        if ($left !== null) {
                            $values['width'] = $left;
                        }

                        if ($top !== null) {
                            $values['height'] = $top;
                        }
                    break;

                    default:
                        if ($left !== null) {
                            $values['pos_x'] = $left;
                        }

                        if ($top !== null) {
                            $values['pos_y'] = $top;
                        }
                    break;
                }

                if (defined('METACONSOLE') && $metaconsole) {
                    if ($server_name !== null) {
                        $values['id_metaconsole'] = db_get_value(
                            'id',
                            'tmetaconsole_setup',
                            'server_name',
                            $server_name
                        );
                    }

                    if ($server_id > 0) {
                        $values['id_metaconsole'] = $server_id;
                    }


                    if ($id_agent !== null) {
                        $values['id_agent'] = $id_agent;
                    }

                    if ($linked_map_node_id !== null) {
                        $values['linked_layout_node_id'] = (int) $linked_map_node_id;
                    }
                } else if ($id_agent == 0) {
                    $values['id_agent'] = 0;
                } else if (!empty($id_agent)) {
                    $values['id_agent'] = $id_agent;
                } else if ($agent !== null) {
                    $id_agent = agents_get_agent_id($agent);
                    $values['id_agent'] = $id_agent;
                }

                if ($id_module !== null) {
                    $values['id_agente_modulo'] = $id_module;
                }

                if ($parent !== null) {
                    $values['parent_item'] = $parent;
                }

                if ($map_linked !== null) {
                    $values['id_layout_linked'] = $map_linked;
                }

                if ($linked_map_status_calculation_type !== null) {
                    $values['linked_layout_status_type'] = $linked_map_status_calculation_type;
                }

                if ($map_linked_weight !== null) {
                    if ($map_linked_weight > 100) {
                        $map_linked_weight = 100;
                    }

                    if ($map_linked_weight < 0) {
                        $map_linked_weight = 0;
                    }

                    $values['id_layout_linked_weight'] = $map_linked_weight;
                }

                if ($linked_map_status_service_critical !== null) {
                    if ($linked_map_status_service_critical > 100) {
                        $linked_map_status_service_critical = 100;
                    }

                    if ($linked_map_status_service_critical < 0) {
                        $linked_map_status_service_critical = 0;
                    }

                    $values['linked_layout_status_as_service_critical'] = $linked_map_status_service_critical;
                }

                if ($linked_map_status_service_warning !== null) {
                    if ($linked_map_status_service_warning > 100) {
                        $linked_map_status_service_warning = 100;
                    }

                    if ($linked_map_status_service_warning < 0) {
                        $linked_map_status_service_warning = 0;
                    }

                    $values['linked_layout_status_as_service_warning'] = $linked_map_status_service_warning;
                }

                if ($element_group !== null) {
                    $values['element_group'] = $element_group;
                }

                switch ($type) {
                    // -- line_item ------------------------------------
                    case 'handler_start':
                    case 'handler_end':
                        $values['border_width'] = $line_width;
                        $values['border_color'] = $line_color;
                    break;

                    // -------------------------------------------------
                    case 'auto_sla_graph':
                        $values['type'] = AUTO_SLA_GRAPH;
                        if ($event_max_time_row !== null) {
                            $values['period'] = $event_max_time_row;
                        }

                        if ($width !== null) {
                            $values['width'] = $width;
                        }

                        if ($height !== null) {
                            $values['height'] = $height;
                        }
                    break;

                    case 'donut_graph':
                        if ($width_percentile !== null) {
                            $values['width'] = $width_percentile;
                            $values['height'] = $width_percentile;
                        }

                        $values['border_color'] = $resume_color;
                        $values['type'] = DONUT_GRAPH;
                        $values['id_agent'] = $id_agent_string;
                    break;

                    case 'box_item':
                        $values['border_width'] = $border_width;
                        $values['border_color'] = $border_color;
                        $values['fill_color'] = $fill_color;
                        $values['period'] = $period;
                        $values['width'] = $width_box;
                        $values['height'] = $height_box;
                    break;

                    case 'group_item':
                        $values['id_group'] = $id_group;
                        if ($image !== null) {
                            $values['image'] = $image;
                        }

                        if ($width !== null) {
                            $values['width'] = $width;
                        }

                        if ($height !== null) {
                            $values['height'] = $height;
                        }

                        if ($show_statistics !== null) {
                            $values['show_statistics'] = $show_statistics;
                        }
                    break;

                    case 'module_graph':
                        if ($height_module_graph !== null) {
                            $values['height'] = $height_module_graph;
                        }

                        if ($width_module_graph !== null) {
                            $values['width'] = $width_module_graph;
                        }

                        if ($period !== null) {
                            $values['period'] = $period;
                        }

                        if ($id_custom_graph !== null) {
                            $values['id_custom_graph'] = $id_custom_graph;
                        }

                        if (is_metaconsole()) {
                            if ($values['id_custom_graph'] != 0) {
                                $explode_id = explode('|', $values['id_custom_graph']);
                                $values['id_custom_graph'] = $explode_id[0];
                                $values['id_metaconsole'] = $explode_id[1];
                            }
                        }
                    break;

                    case 'bars_graph':
                        if ($width_percentile !== null) {
                            $values['width'] = $width_percentile;
                        }

                        if ($bars_graph_height !== null) {
                            $values['height'] = $bars_graph_height;
                        }

                        if ($bars_graph_type !== null) {
                            $values['type_graph'] = $bars_graph_type;
                        }

                        if ($background_color !== null) {
                            $values['image'] = $background_color;
                        }

                        if ($grid_color !== null) {
                            $values['border_color'] = $grid_color;
                        }

                        $values['id_agent'] = $id_agent_string;
                    break;

                    case 'percentile_item':
                    case 'percentile_bar':
                        if ($action == 'update') {
                            if ($width_percentile !== null) {
                                $values['width'] = $width_percentile;
                            }

                            if ($max_percentile !== null) {
                                $values['height'] = $max_percentile;
                            }

                            $values['type'] = PERCENTILE_BAR;
                            if ($type_percentile == 'percentile') {
                                $values['type'] = PERCENTILE_BAR;
                            } else if ($type_percentile == 'circular_progress_bar') {
                                $values['type'] = CIRCULAR_PROGRESS_BAR;
                            } else if ($type_percentile == 'interior_circular_progress_bar') {
                                $values['type'] = CIRCULAR_INTERIOR_PROGRESS_BAR;
                            } else if ($type_percentile == 'bubble') {
                                $values['type'] = PERCENTILE_BUBBLE;
                            }

                            $values['image'] = $value_show;

                            $values['border_color'] = $percentile_color;
                            $values['fill_color'] = $percentile_label_color;
                            $values['label'] = $percentile_label;
                        }
                    break;

                    case 'icon':
                    case 'static_graph':
                        if ($image !== null) {
                            $values['image'] = $image;
                        }

                        if ($width !== null) {
                            $values['width'] = $width;
                        }

                        if ($height !== null) {
                            $values['height'] = $height;
                        }

                        if ($show_last_value !== null) {
                            $values['show_last_value'] = $show_last_value;
                        }
                    break;

                    case 'simple_value':
                        if ($action == 'update') {
                            $values['type'] = visual_map_get_simple_value_type(
                                $process_simple_value
                            );
                            $values['period'] = $period;
                            $values['width'] = $width;
                        }
                    break;

                    case 'clock':
                        if ($clock_animation !== null) {
                            $values['clock_animation'] = $clock_animation;
                        }

                        if ($time_format !== null) {
                            $values['time_format'] = $time_format;
                        }

                        if ($timezone !== null) {
                            $values['timezone'] = $timezone;
                        }

                        if ($width !== null) {
                            $values['width'] = $width_percentile;
                        }

                        if ($fill_color !== null) {
                            $values['fill_color'] = $fill_color;
                        }
                    break;

                    case 'color_cloud':
                        $values['width'] = $diameter;
                        $values['height'] = $diameter;
                        // Fill Color Cloud values
                        $extra = [
                            'default_color' => $default_color,
                            'color_ranges'  => [],
                        ];

                        $num_ranges = count($color_range_colors);
                        for ($i = 0; $i < $num_ranges; $i++) {
                            if (!isset($color_range_from_values[$i])
                                || !isset($color_range_to_values[$i])
                                || !isset($color_range_colors[$i])
                            ) {
                                return;
                            }

                            $extra['color_ranges'][] = [
                                'from_value' => (float) $color_range_from_values[$i],
                                'to_value'   => (float) $color_range_to_values[$i],
                                'color'      => $color_range_colors[$i],
                            // already html encoded
                            ];
                        }

                        // Yes, we are using the label to store the extra info.
                        // Sorry not sorry.
                        $values['label'] = json_encode($extra);
                    break;

                    default:
                        if (enterprise_installed()) {
                            if ($image !== null) {
                                $values['image'] = $image;
                            }

                            enterprise_ajax_update_values($action, $type, $values);
                        }
                    break;
                }

                if ($action == 'move') {
                    // Don't change the label because only change the positions
                    unset($values['label']);
                    unset($values['label_position']);
                    // Don't change this values when move
                    unset($values['id_agent']);
                    unset($values['id_agente_modulo']);
                    unset($values['enable_link']);
                    unset($values['show_on_top']);
                    unset($values['id_layout_linked']);
                    unset($values['element_group']);
                    unset($values['id_layout_linked_weight']);
                    unset($values['cache_expiration']);
                    // Don't change background color in graphs when move
                    switch ($type) {
                        case 'group_item':
                            unset($values['id_group']);
                            unset($values['show_statistics']);
                        break;

                        case 'module_graph':
                            unset($values['image']);
                            unset($values['type_graph']);
                        break;

                        case 'bars_graph':
                            unset($values['image']);
                            unset($values['type_graph']);
                            unset($values['border_color']);
                            unset($values['width']);
                            unset($values['id_agent']);
                            unset($values['height']);
                        break;

                        case 'donut_graph':
                            unset($values['border_color']);
                            unset($values['width']);
                            unset($values['id_agent']);
                        break;

                        case 'clock':
                            unset($values['clock_animation']);
                            unset($values['time_format']);
                            unset($values['timezone']);
                            unset($values['fill_color']);
                            unset($values['width']);
                        break;

                        case 'box_item':
                            unset($values['border_width']);
                            unset($values['border_color']);
                            unset($values['fill_color']);
                            unset($values['period']);
                            unset($values['width']);
                            unset($values['height']);
                        break;

                        case 'color_cloud':
                            unset($values['width']);
                            unset($values['height']);
                            unset($values['diameter']);
                            unset($values['label']);
                        break;

                        // -- line_item --
                        case 'handler_start':
                        case 'handler_end':
                            // ---------------
                            unset($values['border_width']);
                            unset($values['border_color']);
                        break;
                    }
                }

                $item_in_db = db_get_row_filter('tlayout_data', ['id' => $id_element]);

                if (($item_in_db['parent_item'] == 0) && ($values['parent_item'] != 0)) {
                    $new_line = 1;
                }

                $result = db_process_sql_update(
                    'tlayout_data',
                    $values,
                    ['id' => $id_element]
                );

                // Invalidate the item's cache.
                if ($result !== false && $result > 0) {
                    db_process_sql_delete(
                        'tvisual_console_elements_cache',
                        [
                            'vc_item_id' => (int) $id_element,
                        ]
                    );
                }

                $return_val = [];
                $return_val['correct'] = (int) $result;
                $return_val['new_line'] = $new_line;

                echo json_encode($return_val);
            break;
        }
    break;

    case 'load':
        switch ($type) {
            case 'background':
                $backgroundFields = db_get_row_filter(
                    'tlayout',
                    ['id' => $id_visual_console],
                    [
                        'background',
                        'height',
                        'width',
                    ]
                );
                echo json_encode($backgroundFields);
            break;

            // -- line_item --
            case 'handler_start':
            case 'handler_end':
                // ---------------
            case 'box_item':
            case 'percentile_bar':
            case 'percentile_item':
            case 'static_graph':
            case 'group_item':
            case 'module_graph':
            case 'bars_graph':
            case 'simple_value':
            case 'label':
            case 'icon':
            case 'clock':
            case 'auto_sla_graph':
            case 'donut_graph':
            case 'color_cloud':
                $elementFields = db_get_row_filter(
                    'tlayout_data',
                    ['id' => $id_element]
                );

                // Metaconsole db connection
                if ($elementFields['id_metaconsole'] != 0) {
                    $connection = db_get_row_filter(
                        'tmetaconsole_setup',
                        ['id' => $elementFields['id_metaconsole']]
                    );

                    $elementFields['id_server_name'] = $connection['server_name'];

                    if (metaconsole_load_external_db($connection) != NOERR) {
                        // ui_print_error_message ("Error connecting to ".$server_name);
                        continue;
                    }
                }


                if (!empty($connection['server_name'])) {
                    $elementFields['agent_name'] = io_safe_output(agents_get_alias($elementFields['id_agent'])).' ('.io_safe_output($connection['server_name']).')';
                } else {
                    $elementFields['agent_name'] = io_safe_output(agents_get_alias($elementFields['id_agent']));
                }

                // Make the html of select box of modules about id_agent.
                if (($elementFields['id_agent'] != 0)
                    && ($elementFields['id_layout_linked'] == 0)
                ) {
                    $modules = agents_get_modules(
                        $elementFields['id_agent'],
                        false,
                        [
                            'disabled'  => 0,
                            'id_agente' => $elementFields['id_agent'],
                        ]
                    );

                    $elementFields['modules_html'] = '<option value="0">--</option>';
                    foreach ($modules as $id => $name) {
                        $elementFields['modules_html'] .= '<option value="'.$id.'">'.io_safe_output($name).'</option>';
                    }
                } else {
                    $elementFields['modules_html'] = '<option value="0">'.__('Any').'</option>';
                }

                // Restore db connection
                if ($elementFields['id_metaconsole'] != 0) {
                    metaconsole_restore_db();
                }

                if (isset($elementFields['id_layout_linked_weight'])) {
                    $elementFields['id_layout_linked_weight'] = (int) $elementFields['id_layout_linked_weight'];
                }

                if (isset($elementFields['linked_layout_status_as_service_critical'])) {
                    $elementFields['linked_layout_status_as_service_critical'] = (float) $elementFields['linked_layout_status_as_service_critical'];
                }

                if (isset($elementFields['linked_layout_status_as_service_warning'])) {
                    $elementFields['linked_layout_status_as_service_warning'] = (float) $elementFields['linked_layout_status_as_service_warning'];
                }

                switch ($type) {
                    case 'auto_sla_graph':
                        $elementFields['event_max_time_row'] = $elementFields['period'];
                    break;

                    case 'percentile_item':
                    case 'percentile_bar':
                        $elementFields['width_percentile'] = $elementFields['width'];
                        $elementFields['max_percentile'] = $elementFields['height'];

                        $elementFields['value_show'] = $elementFields['image'];

                        $elementFields['type_percentile'] = 'percentile';
                        if ($elementFields['type'] == PERCENTILE_BAR) {
                            $elementFields['type_percentile'] = 'percentile';
                        } else if ($elementFields['type'] == PERCENTILE_BUBBLE) {
                            $elementFields['type_percentile'] = 'bubble';
                        } else if ($elementFields['type'] == CIRCULAR_PROGRESS_BAR) {
                            $elementFields['type_percentile'] = 'circular_progress_bar';
                        } else if ($elementFields['type'] == CIRCULAR_INTERIOR_PROGRESS_BAR) {
                            $elementFields['type_percentile'] = 'interior_circular_progress_bar';
                        }

                        $elementFields['percentile_color'] = $elementFields['border_color'];
                        $elementFields['percentile_label_color'] = $elementFields['fill_color'];
                        $elementFields['percentile_label'] = $elementFields['label'];
                    break;

                    case 'donut_graph':
                        $elementFields['width_percentile'] = $elementFields['width'];
                        $elementFields['resume_color'] = $elementFields['border_color'];
                        $elementFields['id_agent_string'] = $elementFields['id_agent'];
                        if (($elementFields['id_agent_string'] != 0)
                            && ($elementFields['id_layout_linked'] == 0)
                        ) {
                            $modules = agents_get_modules(
                                $elementFields['id_agent'],
                                false,
                                [
                                    'disabled'                         => 0,
                                    'id_agente'                        => $elementFields['id_agent'],
                                    'tagente_modulo.id_tipo_modulo IN' => '(17,23,3,10,33)',
                                ]
                            );

                            $elementFields['modules_html'] = '<option value="0">--</option>';
                            foreach ($modules as $id => $name) {
                                $elementFields['modules_html'] .= '<option value="'.$id.'">'.io_safe_output($name).'</option>';
                            }
                        }
                    break;

                    case 'module_graph':
                        $elementFields['width_module_graph'] = $elementFields['width'];
                        $elementFields['height_module_graph'] = $elementFields['height'];
                    break;

                    case 'clock':
                        $elementFields['width_percentile'] = $elementFields['width'];
                    break;

                    case 'bars_graph':
                        $elementFields['width_percentile'] = $elementFields['width'];
                        $elementFields['bars_graph_height'] = $elementFields['height'];
                        $elementFields['bars_graph_type'] = $elementFields['type_graph'];
                        $elementFields['grid_color'] = $elementFields['border_color'];
                        $elementFields['id_agent_string'] = $elementFields['id_agent'];
                        if (($elementFields['id_agent_string'] != 0)
                            && ($elementFields['id_layout_linked'] == 0)
                        ) {
                            $modules = agents_get_modules(
                                $elementFields['id_agent'],
                                false,
                                [
                                    'disabled'                         => 0,
                                    'id_agente'                        => $elementFields['id_agent'],
                                    'tagente_modulo.id_tipo_modulo IN' => '(17,23,3,10,33)',
                                ]
                            );

                            $elementFields['modules_html'] = '<option value="0">--</option>';
                            foreach ($modules as $id => $name) {
                                $elementFields['modules_html'] .= '<option value="'.$id.'">'.io_safe_output($name).'</option>';
                            }
                        }
                    break;

                    case 'box_item':
                        $elementFields['width_box'] = $elementFields['width'];
                        $elementFields['height_box'] = $elementFields['height'];
                        $elementFields['border_color'] = $elementFields['border_color'];
                        $elementFields['border_width'] = $elementFields['border_width'];
                        $elementFields['fill_color'] = $elementFields['fill_color'];
                    break;

                    case 'color_cloud':
                        $elementFields['diameter'] = $elementFields['width'];
                        $elementFields['dynamic_data'] = null;
                        try {
                            // Yes, it's using the label field to store the extra data
                            $elementFields['dynamic_data'] = json_decode($elementFields['label'], true);
                        } catch (Exception $ex) {
                        }

                        $elementFields['label'] = '';
                    break;

                    // -- line_item --
                    case 'handler_start':
                    case 'handler_end':
                        // ---------------
                        $elementFields['line_width'] = $elementFields['border_width'];
                        $elementFields['line_color'] = $elementFields['border_color'];
                    break;
                }

                // Support for max, min and svg process on simple value items
                if ($type == 'simple_value') {
                    switch ($elementFields['type']) {
                        case SIMPLE_VALUE:
                            $elementFields['process_value'] = 0;
                        break;

                        case SIMPLE_VALUE_MAX:
                            $elementFields['process_value'] = 2;
                        break;

                        case SIMPLE_VALUE_MIN:
                            $elementFields['process_value'] = 1;
                        break;

                        case SIMPLE_VALUE_AVG:
                            $elementFields['process_value'] = 3;
                        break;
                    }
                }

                $elementFields['label'] = io_safe_output($elementFields['label']);
                echo json_encode($elementFields);
            break;

            default:
                enterprise_hook('enterprise_ajax_load_values', [$type, $id_element]);
            break;
        }
    break;

    case 'insert':
        $values = [];
        $values['id_layout'] = $id_visual_console;
        $values['label'] = $label;
        $values['pos_x'] = $left;
        $values['pos_y'] = $top;
        $values['label_position'] = $label_position;

        if (defined('METACONSOLE') && $metaconsole) {
            if ($server_id > 0) {
                $values['id_metaconsole'] = $server_id;
            } else {
                $values['id_metaconsole'] = db_get_value(
                    'id',
                    'tmetaconsole_setup',
                    'server_name',
                    $server_name
                );
            }

            $values['id_agent'] = $id_agent;
        } else {
            if (!empty($id_agent)) {
                $values['id_agent'] = $id_agent;
            } else if (!empty($agent)) {
                $values['id_agent'] = agents_get_agent_id($agent);
            } else {
                $values['id_agent'] = 0;
            }
        }

        $values['id_agente_modulo'] = $id_module;
        $values['id_layout_linked'] = $map_linked;

        if (defined('METACONSOLE') && $metaconsole) {
            $values['linked_layout_node_id'] = (int) $linked_map_node_id;
        }

        $values['linked_layout_status_type'] = $linked_map_status_calculation_type;

        if ($map_linked_weight !== null) {
            if ($map_linked_weight > 100) {
                $map_linked_weight = 100;
            }

            if ($map_linked_weight < 0) {
                $map_linked_weight = 0;
            }

            $values['id_layout_linked_weight'] = $map_linked_weight;
        }

        if ($linked_map_status_service_critical !== null) {
            if ($linked_map_status_service_critical > 100) {
                $linked_map_status_service_critical = 100;
            }

            if ($linked_map_status_service_critical < 0) {
                $linked_map_status_service_critical = 0;
            }

            $values['linked_layout_status_as_service_critical'] = $linked_map_status_service_critical;
        }

        if ($linked_map_status_service_warning !== null) {
            if ($linked_map_status_service_warning > 100) {
                $linked_map_status_service_warning = 100;
            }

            if ($linked_map_status_service_warning < 0) {
                $linked_map_status_service_warning = 0;
            }

            $values['linked_layout_status_as_service_warning'] = $linked_map_status_service_warning;
        }

        $values['element_group'] = $element_group;
        $values['parent_item'] = $parent;
        $values['enable_link'] = $enable_link;
        $values['show_on_top'] = $show_on_top;
        $values['image'] = $background_color;
        $values['type_graph'] = $type_graph;
        $values['id_custom_graph'] = $id_custom_graph;

        switch ($type) {
            case 'line_item':
            case 'box_item':
            case 'clock':
            case 'icon':
            case 'label':
                $values['cache_expiration'] = 0;
            break;

            default:
                $values['cache_expiration'] = $cache_expiration;
            break;
        }

        switch ($type) {
            case 'network_link':
                $values['type'] = NETWORK_LINK;
                $values['border_width'] = $line_width;
                $values['border_color'] = $line_color;
                $values['pos_x'] = $line_start_x;
                $values['pos_y'] = $line_start_y;
                $values['width'] = $line_end_x;
                $values['height'] = $line_end_y;
            break;

            case 'line_item':
                $values['type'] = LINE_ITEM;
                $values['border_width'] = $line_width;
                $values['border_color'] = $line_color;
                $values['pos_x'] = $line_start_x;
                $values['pos_y'] = $line_start_y;
                $values['width'] = $line_end_x;
                $values['height'] = $line_end_y;
            break;

            case 'box_item':
                $values['type'] = BOX_ITEM;
                $values['border_width'] = $border_width;
                $values['border_color'] = $border_color;
                $values['fill_color'] = $fill_color;
                $values['period'] = $period;
                $values['width'] = $width_box;
                $values['height'] = $height_box;
            break;

            case 'donut_graph':
                $values['type'] = DONUT_GRAPH;
                $values['width'] = $width_percentile;
                $values['height'] = $width_percentile;
                $values['border_color'] = $resume_color;
                $values['id_agent'] = $id_agent_string;
            break;

            case 'module_graph':
                $values['type'] = MODULE_GRAPH;

                if (is_metaconsole()) {
                    if ($values['id_custom_graph'] != 0) {
                        $explode_id = explode('|', $values['id_custom_graph']);
                        $values['id_custom_graph'] = $explode_id[0];
                        $values['id_metaconsole'] = $explode_id[1];
                    }
                }

                if ($values['id_custom_graph'] > 0) {
                    $values['height'] = $height_module_graph;
                    $values['width'] = $width_module_graph;

                    if (is_metaconsole()) {
                        $server_data = metaconsole_get_connection_by_id($values['id_metaconsole']);
                        // Establishes connection
                        if (metaconsole_load_external_db($server_data) !== NOERR) {
                            continue;
                        }
                    }

                    $graph_conf = db_get_row('tgraph', 'id_graph', $values['id_custom_graph']);

                    if (is_metaconsole()) {
                        metaconsole_restore_db();
                    }

                    $graph_stacked = $graph_conf['stacked'];
                    if ($graph_stacked == CUSTOM_GRAPH_BULLET_CHART) {
                        $values['height'] = 50;
                    } else if ($graph_stacked == CUSTOM_GRAPH_GAUGE) {
                        if ($height_module_graph < 150) {
                            $values['height'] = 150;
                        } else if (($height_module_graph >= 150)
                            && ($height_module_graph < 250)
                        ) {
                                $values['height'] = $graph_conf['height'];
                        } else if ($height_module_graph >= 250) {
                            $values['height'] = 200;
                        }
                    }
                } else {
                    $values['height'] = $height_module_graph;
                    $values['width'] = $width_module_graph;
                }

                $values['period'] = $period;
            break;

            case 'bars_graph':
                $values['type'] = BARS_GRAPH;
                $values['width'] = $width_percentile;
                $values['height'] = $bars_graph_height;
                $values['type_graph'] = $bars_graph_type;
                $values['image'] = $background_color;
                $values['border_color'] = $grid_color;
                $values['id_agent'] = $id_agent_string;
            break;

            case 'clock':
                $values['type'] = CLOCK;
                $values['width'] = $width_percentile;
                $values['clock_animation'] = $clock_animation;
                $values['fill_color'] = $fill_color;
                $values['time_format'] = $time_format;
                $values['timezone'] = $timezone;
            break;

            case 'auto_sla_graph':
                $values['type'] = AUTO_SLA_GRAPH;
                $values['period'] = $event_max_time_row;
                $values['width'] = $width;
                $values['height'] = $height;
            break;

            case 'percentile_item':
            case 'percentile_bar':
                if ($type_percentile == 'percentile') {
                    $values['type'] = PERCENTILE_BAR;
                } else if ($type_percentile == 'circular_progress_bar') {
                    $values['type'] = CIRCULAR_PROGRESS_BAR;
                } else if ($type_percentile == 'interior_circular_progress_bar') {
                    $values['type'] = CIRCULAR_INTERIOR_PROGRESS_BAR;
                } else {
                    $values['type'] = PERCENTILE_BUBBLE;
                }

                $values['border_color'] = $percentile_color;
                $values['image'] = $value_show;
                // Hack to save it show percent o value.
                $values['width'] = $width_percentile;
                $values['height'] = $max_percentile;
                $values['fill_color'] = $percentile_label_color;
                $values['label'] = $percentile_label;
            break;

            case 'static_graph':
                $values['type'] = STATIC_GRAPH;
                $values['image'] = $image;
                $values['width'] = $width;
                $values['height'] = $height;
                $values['show_last_value'] = $show_last_value;
            break;

            case 'group_item':
                $values['type'] = GROUP_ITEM;
                $values['image'] = $image;
                $values['width'] = $width;
                $values['height'] = $height;
                $values['id_group'] = $id_group;
                $values['show_statistics'] = $show_statistics;
            break;

            case 'simple_value':
                // This allows min, max and avg process in a simple value
                $values['type'] = visual_map_get_simple_value_type($process_simple_value);
                $values['period'] = $period;
                $values['width'] = $width;
            break;

            case 'label':
                $values['type'] = LABEL;
                $values['label'] = $label;
            break;

            case 'icon':
                $values['type'] = ICON;
                $values['image'] = $image;
                $values['width'] = $width;
                $values['height'] = $height;
            break;

            case 'color_cloud':
                $values['type'] = COLOR_CLOUD;
                $values['width'] = $diameter;
                $values['height'] = $diameter;

                $extra = [
                    'default_color' => $default_color,
                    'color_ranges'  => [],
                ];

                $num_ranges = count($color_range_colors);
                for ($i = 0; $i < $num_ranges; $i++) {
                    if (!isset($color_range_from_values[$i])
                        || !isset($color_range_to_values[$i])
                        || !isset($color_range_colors[$i])
                    ) {
                        return;
                    }

                    $extra['color_ranges'][] = [
                        'from_value' => (int) $color_range_from_values[$i],
                        'to_value'   => (int) $color_range_to_values[$i],
                        'color'      => $color_range_colors[$i],
                    // already html encoded
                    ];
                }

                // Yes, we are using the label to store the extra info.
                // Sorry not sorry.
                $values['label'] = json_encode($extra);
            break;

            default:
                if (enterprise_installed()) {
                    enterprise_ajax_insert_fill_values_insert($type, $values);
                }
            break;
        }

        $idData = db_process_sql_insert('tlayout_data', $values);

        $return = [];
        if ($idData === false) {
            $return['correct'] = 0;
        } else {
            $text = visual_map_create_internal_name_item($label, $type, $image, $agent, $id_module, $idData);

            $return['correct'] = 1;
            $return['id_data'] = $idData;
            $return['text'] = $text;
        }

        echo json_encode($return);
    break;

    case 'copy':

        $values = db_get_row_filter(
            'tlayout_data',
            ['id' => $id_element]
        );

        unset($values['id']);
        $values['pos_x'] = ($values['pos_x'] + 20);
        $values['pos_y'] = ($values['pos_y'] + 20);

        $idData = db_process_sql_insert('tlayout_data', $values);

        $return = [];
        if ($idData === false) {
            $return['correct'] = 0;
        } else {
            $text = visual_map_create_internal_name_item($label, $type, $image, $agent, $id_module, $idData);

            $values['left'] = $values['pos_x'];
            $values['top'] = $values['pos_y'];
            $values['parent'] = $values['parent_item'];
            $return['values'] = $values;
            $return['correct'] = 1;
            $return['id_data'] = $idData;
            $return['text'] = $text;
            $return['type'] = visual_map_type_in_js($values['type']);

            switch ($values['type']) {
                case BOX_ITEM:
                    $return['values']['width_box'] = $values['width'];
                    $return['values']['height_box'] = $values['height'];
                break;

                case CIRCULAR_PROGRESS_BAR:
                    $return['values']['type_percentile'] = 'bubble';
                break;

                case CIRCULAR_INTERIOR_PROGRESS_BAR:
                    $return['values']['type_percentile'] = 'circular_progress_bar';
                break;

                case PERCENTILE_BUBBLE:
                    $return['values']['type_percentile'] = 'interior_circular_progress_bar';
                break;

                case PERCENTILE_BAR:
                    $return['values']['type_percentile'] = 'percentile';
                break;

                case COLOR_CLOUD:
                    $return['values']['diameter'] = $values['width'];

                    try {
                        // Yes, it's using the label field to store the extra data
                        $return['values']['dynamic_data'] = json_decode($values['label'], true);
                    } catch (Exception $ex) {
                        $return['values']['dynamic_data'] = [];
                    }

                    $values['label'] = '';
                break;
            }
        }

        // Don't move this piece of code
        $return['values']['label'] = io_safe_output($values['label']);

        echo json_encode($return);
    break;

    case 'delete':
        if (db_process_sql_delete('tlayout_data', ['id' => $id_element, 'id_layout' => $id_visual_console]) === false) {
            $return['correct'] = 0;
        } else {
            $return['correct'] = 1;
        }

        echo json_encode($return);
    break;

    case 'get_original_size_background':
        $replace = strlen($config['homeurl'].'/');

        if (substr($background, 0, $replace) == $config['homeurl'].'/') {
            $size = getimagesize(substr($background, $replace));
        } else {
            $size = getimagesize($background);
        }

        echo json_encode($size);
    break;

    default:
        enterprise_hook('enterprise_visualmap_ajax');
    break;
}

// Visual map element status check.
if ($get_element_status) {
    $layoutData = db_get_row_filter(
        'tlayout_data',
        ['id' => $id_element]
    );

    $res = visual_map_get_status_element($layoutData);
    echo $res;

    return;
}
