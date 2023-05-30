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

global $config;

// Fix ajax include this file.
global $ajax;

if (!isset($ajax)) {
    include_once 'functions_graph.php';
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/graphs/functions_d3.php';
enterprise_include_once('include/functions_visual_map.php');


function visual_map_print_item_toolbox($idDiv, $text, $float)
{
    if ($float == 'left') {
        $margin = 'margin-right';
    } else {
        $margin = 'margin-left';
    }

    echo '<div class="button_toolbox bolder" id="'.$idDiv.'"
        style="float: '.$float.';'.$margin.': 5px;">';
    echo $text;
    echo '</span>';
    echo '</div>';
}


function visual_map_print_user_line_handles($layoutData)
{
    $id = $layoutData['id'];

    $start_x = $layoutData['pos_x'];
    $start_y = $layoutData['pos_y'];
    $end_x = $layoutData['width'];
    $end_y = $layoutData['height'];
    $z_index = 2;

    $sizeStyle = '';

    $radious_handle = (12 / 2);

    // Handle of start
    echo '<div id="handler_start_'.$id.'" class="item handler_start" '.'style="z-index: '.$z_index.';'.'position: absolute; top: '.($start_y - $radious_handle).'px; '.'left: '.($start_x - $radious_handle).'px;'.'text-align: ;'.'display: inline-block; '.$sizeStyle.'">';

    html_print_image('images/dot_red.png');

    echo '</div>';

    // Handle of end
    echo '<div id="handler_end_'.$id.'" class="item handler_end" '.'style="z-index: '.$z_index.';'.'position: absolute; top: '.($end_y - $radious_handle).'px; '.'left: '.($end_x - $radious_handle).'px;'.'text-align: ;'.'display: inline-block; '.$sizeStyle.'">';

    html_print_image('images/dot_green.png');

    echo '</div>';
}


function visual_map_print_item(
    $mode='read',
    $layoutData=[],
    $proportion=null,
    $show_links=true,
    $isExternalLink=false,
    $graph_javascript=true
) {
    global $config;

    include_once $config['homedir'].'/include/functions_graph.php';
    include_once $config['homedir'].'/include/functions_custom_graphs.php';

    // add 60 px for visual console map
    $width = $layoutData['width'];
    $height = $layoutData['height'];
    $max_percentile = $layoutData['height'];
    $top = $layoutData['pos_y'];
    $left = $layoutData['pos_x'];
    $id = $layoutData['id'];
    $label = io_safe_output($layoutData['label']);
    $id_module = $layoutData['id_agente_modulo'];
    $type = $layoutData['type'];
    $period = $layoutData['period'];
    $type_graph = $layoutData['type_graph'];
    $border_width = $layoutData['border_width'];
    $border_color = $layoutData['border_color'];
    $fill_color = $layoutData['fill_color'];
    $label_position = $layoutData['label_position'];
    $show_on_top = $layoutData['show_on_top'];
    $clock_animation = $layoutData['clock_animation'];
    $time_format = $layoutData['time_format'];
    $timezone = $layoutData['timezone'];

    if ($show_on_top) {
        $show_on_top_index = 10;
    } else {
        $show_on_top_index = '';
    }

    $sizeStyle = '';
    $borderStyle = '';
    $imageSize = '';

    if (!empty($proportion)) {
        $top = ($top * $proportion['proportion_height']);
        $left = ($left * $proportion['proportion_width']);
    }

    $text = '<span id="text_'.$id.'" class="text">'.$label.'</span>';

    if ($height == 0) {
        switch ($type) {
            case 0:
            case 11:
                $tableheight0 = '70';
            break;

            case 3:
                $tableheight0 = '30';
            break;

            case 9:
                $tableheight0 = '130';
            break;

            case 1:
                $tableheight0 = '180';
            break;

            case SERVICE:
                $tableheight0 = '50';
            break;
        }
    } else {
        $tableheight0 = $height;
    }

    if ($layoutData['width'] == 0) {
        switch ($type) {
            case 19:
                if ($layoutData['clock_animation'] == 'analogic_1') {
                    $himg = '200';
                    $wimg = '200';
                } else {
                    $himg = '60';
                    $wimg = '200';
                }
            break;
        }
    } else {
        switch ($type) {
            case 19:
                if ($layoutData['clock_animation'] == 'analogic_1') {
                    $himg = $width;
                    $wimg = $width;
                } else {
                    $himg = ($width / 3.9);
                    $wimg = $width;
                }
            break;
        }
    }

    if ($layoutData['width'] == 0 || $layoutData['height'] == 0) {
        switch ($type) {
            case 0:
            case 11:
                $himg = '70';
                $wimg = '70';
            break;

            case 3:
            case 14:
                if (get_parameter('action') == 'edit') {
                    $himg = '30';
                    $wimg = '150';
                } else {
                    $himg = '15';
                    $wimg = '150';
                }
            break;

            case 9:
                $himg = '130';
                $wimg = '130';
            break;

            case 1:
                $himg = '180';
                $wimg = '300';
            break;

            case SERVICE:
                $himg = '50';
                $wimg = '150';
            break;
        }
    } else {
        $wimg = $layoutData['width'];
        $himg = $layoutData['height'];

        if ($type == 3) {
            if (get_parameter('action') == 'edit') {
                $himg = '30';
            } else {
                $himg = '15';
            }
        }

        if ($type == 9) {
            $himg = $wimg;
        }
    }

    if ($label_position == 'left') {
        $text = '<table style="float:left;height:'.$himg.'px;"><tr><td></td></tr><tr><td><span id="text_'.$id.'" class="text">'.$label.'</span></td></tr><tr><td></td></tr></table>';
    } else if ($label_position == 'right') {
        $text = '<table style="float:right;height:'.$himg.'px;"><tr><td></td></tr><tr><td><span   id="text_'.$id.'" class="text">'.$label.'</span></td></tr><tr><td></td></tr></table>';
    } else {
        $text = '<table style="text-align:center ;width:'.$wimg.'px;"><tr><td></td></tr><tr><td><span   id="text_'.$id.'" class="text">'.$label.'</span></td></tr><tr><td></td></tr></table>';
    }

    if (!isset($layoutData['status_calculated'])) {
        $layoutData['status_calculated'] = visual_map_get_status_element($layoutData);
    }

    $status = $layoutData['status_calculated'];

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
        default:
            // Unknown
            // Default is Blue (Other)
            $colorStatus = COL_UNKNOWN;
        break;
    }

    $element_enterprise = [];
    if (enterprise_installed()) {
        $element_enterprise = enterprise_visual_map_print_item(
            $mode,
            $layoutData,
            $proportion,
            $show_links,
            $isExternalLink
        );
    }

    $link = false;
    $url = '#';

    if ($show_links && ($mode == 'read')) {
        switch ($type) {
            case STATIC_GRAPH:
            case GROUP_ITEM:
                if ($layoutData['enable_link']
                    && can_user_access_node()
                ) {
                    $link = true;
                }
            break;

            case LABEL:
                if ($layoutData['id_layout_linked'] != 0) {
                    $link = true;
                }
            break;

            case ICON:
                if ($layoutData['id_layout_linked'] > 0) {
                    $link = true;
                } else if (preg_match('/<a.*href=["\'](.*)["\']>/', $layoutData['label'], $matches)) {
                    // Link to an URL
                    if ($layoutData['enable_link']) {
                        $link = true;
                    }
                } else if (preg_match('/^.*(http:\/\/)((.)+).*$/i', $layoutData['label'])) {
                    // Link to an URL
                    if ($layoutData['enable_link']) {
                        $link = true;
                    }
                }
            break;

            case SIMPLE_VALUE:
            case SIMPLE_VALUE_MAX:
            case SIMPLE_VALUE_MIN:
            case SIMPLE_VALUE_AVG:
                // Extract id service if it is a prediction module.
                $id_service = db_get_value_filter(
                    'custom_integer_1',
                    'tagente_modulo',
                    [
                        'id_agente_modulo'  => $layoutData['id_agente_modulo'],
                        'prediction_module' => 1,
                    ]
                );

                if (!empty($id_service) && can_user_access_node()) {
                    if ($layoutData['enable_link']) {
                        $link = true;
                    }
                } else if ($layoutData['id_layout_linked'] > 0) {
                    $link = true;
                } else if ($layoutData['enable_link'] && can_user_access_node()) {
                    $link = true;
                }
            break;

            case PERCENTILE_BAR:
            case PERCENTILE_BUBBLE:
            case CIRCULAR_PROGRESS_BAR:
            case CIRCULAR_INTERIOR_PROGRESS_BAR:
                if (!empty($layoutData['id_agent'])
                    && empty($layoutData['id_layout_linked'])
                ) {
                    if ($layoutData['enable_link']
                        && can_user_access_node()
                    ) {
                        // Extract id service if it is a prediction module.
                        $id_service = db_get_value_filter(
                            'custom_integer_1',
                            'tagente_modulo',
                            [
                                'id_agente_modulo'  => $layoutData['id_agente_modulo'],
                                'prediction_module' => 1,
                            ]
                        );

                        if (!empty($id_service)) {
                            // Link to an service page
                            $link = true;
                        } else if ($layoutData['id_agente_modulo'] != 0) {
                            // Link to an module
                            $link = true;
                        } else {
                            // Link to an agent
                            $link = true;
                        }
                    }
                } else if ($layoutData['id_layout_linked'] > 0) {
                    // Link to a map
                    $link = true;
                }
            break;

            case MODULE_GRAPH:
                if ((($layoutData['id_layout_linked'] == '')
                    || ($layoutData['id_layout_linked'] == 0))
                    && can_user_access_node()
                ) {
                    if ($layoutData['enable_link']) {
                        // Extract id service if it is a prediction module.
                        $id_service = db_get_value_filter(
                            'custom_integer_1',
                            'tagente_modulo',
                            [
                                'id_agente_modulo'  => $layoutData['id_agente_modulo'],
                                'prediction_module' => 1,
                            ]
                        );

                        if ($id_service === false) {
                            $id_service = 0;
                        }

                        if ($id_service != 0) {
                            // Link to an service page
                            if (!empty($layoutData['id_metaconsole'])) {
                                $link = true;
                            } else {
                                $link = true;
                            }
                        } else {
                            $link = true;
                        }
                    }
                } else {
                    // Link to a map
                    $link = true;
                }
            break;

            case BARS_GRAPH:
                $link = true;
            break;

            case AUTO_SLA_GRAPH:
                $link = true;
            break;

            case DONUT_GRAPH:
                $link = true;
            break;

            default:
                if (!empty($element_enterprise)) {
                    $link = $element_enterprise['link'];
                }
            break;
        }
    }

    if ($link) {
        switch ($type) {
            case STATIC_GRAPH:
                $is_a_service = false;
                $is_a_link_to_other_visualconsole = false;

                if (enterprise_installed()) {
                    $id_service = services_service_from_module($layoutData['id_agente_modulo']);

                    if (!empty($id_service)) {
                        $is_a_service = true;
                    }
                }

                if ($layoutData['id_layout_linked'] != 0) {
                    $is_a_link_to_other_visualconsole = true;
                }

                if ($is_a_service) {
                    if (empty($layoutData['id_metaconsole'])) {
                        $url = $config['homeurl'].'index.php?sec=services&sec2=enterprise/operation/services/services&id_service='.$id_service.'&offset=0';
                    } else {
                        $server = db_get_row(
                            'tmetaconsole_setup',
                            'id',
                            $layoutData['id_metaconsole']
                        );

                        $url = $server['server_url'].'/'.'index.php?sec=services&sec2=enterprise/operation/services/services&id_service='.$id_service.'&offset=0';
                    }
                } else if ($is_a_link_to_other_visualconsole) {
                    if (!is_metaconsole()) {
                        $url = $config['homeurl'].'index.php?sec=network&amp;sec2=operation/visual_console/render_view&amp;pure='.$config['pure'].'&amp;id='.$layoutData['id_layout_linked'];
                    } else {
                        $url = 'index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=0&id='.$layoutData['id_layout_linked'].'&refr=0';
                    }
                } else {
                    if ($layoutData['id_agente_modulo'] != 0) {
                        // Link to an module
                        if (empty($layoutData['id_metaconsole'])) {
                            $url = $config['homeurl'].'index.php?sec=view&amp;sec2=operation/agentes/status_monitor&amp;id_module='.$layoutData['id_agente_modulo'];
                        } else {
                            $url = ui_meta_get_url_console_child(
                                $layoutData['id_metaconsole'],
                                'view',
                                'operation/agentes/status_monitor&amp;id_module='.$layoutData['id_agente_modulo'],
                                null,
                                null,
                                null,
                                $isExternalLink
                            );
                        }
                    } else {
                        // Link to an agent
                        if (empty($layoutData['id_metaconsole'])) {
                            $url = $config['homeurl'].'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$layoutData['id_agent'];
                        } else {
                            $url = ui_meta_get_url_console_child(
                                $layoutData['id_metaconsole'],
                                'estado',
                                'operation/agentes/ver_agente&amp;id_agente='.$layoutData['id_agent'],
                                null,
                                null,
                                null,
                                $isExternalLink
                            );
                        }
                    }
                }
            break;

            case AUTO_SLA_GRAPH:
                $e_period = $layoutData['period'];
                $date = get_system_time();
                $datelimit = ($date - $e_period);

                $time_format = 'Y/m/d H:i:s';

                $timestamp_init = date($time_format, $datelimit);
                $timestamp_end = date($time_format, $date);

                $timestamp_init_aux = explode(' ', $timestamp_init);
                $timestamp_end_aux = explode(' ', $timestamp_end);

                $date_from = $timestamp_init_aux[0];
                $time_from = $timestamp_init_aux[1];

                $date_to = $timestamp_end_aux[0];
                $time_to = $timestamp_end_aux[1];

                if (empty($layout_data['id_metaconsole'])) {
                    $url = $config['homeurl'].'index.php?sec=eventos&sec2=operation/events/events&id_agent='.$layoutData['id_agent'].'&module_search_hidden='.$layoutData['id_agente_modulo'].'&date_from='.$date_from.'&time_from='.$time_from.'&date_to='.$date_to.'&time_to='.$time_to.'&status=-1';
                } else {
                    $url = 'index.php?sec=eventos&sec2=operation/events/events&id_agent='.$layoutData['id_agent'].'&module_search_hidden='.$layoutData['id_agente_modulo'].'&date_from='.$date_from.'&time_from='.$time_from.'&date_to='.$date_to.'&time_to='.$time_to.'&status=-1';
                }
            break;

            case DONUT_GRAPH:
                if (empty($layout_data['id_metaconsole'])) {
                    $url = $config['homeurl'].'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$layoutData['id_agent'].'&tab=module&edit_module=1&id_agent_module='.$layoutData['id_agente_modulo'];
                } else {
                    $url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$layoutData['id_agent'].'&tab=module&edit_module=1&id_agent_module='.$layoutData['id_agente_modulo'];
                }
            break;

            case BARS_GRAPH:
                if (empty($layout_data['id_metaconsole'])) {
                    $url = $config['homeurl'].'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$layoutData['id_agent'].'&tab=module&edit_module=1&id_agent_module='.$layoutData['id_agente_modulo'];
                } else {
                    $url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$layoutData['id_agent'].'&tab=module&edit_module=1&id_agent_module='.$layoutData['id_agente_modulo'];
                }
            break;

            case GROUP_ITEM:
                    $is_a_link_to_other_visualconsole = false;
                if ($layoutData['id_layout_linked'] != 0) {
                    $is_a_link_to_other_visualconsole = true;
                }

                if ($is_a_link_to_other_visualconsole) {
                    if (is_metaconsole() === true) {
                        $url = $config['homeurl'].'index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=0&id='.$layoutData['id_layout_linked'].'&refr=300';
                    } else {
                        $url = $config['homeurl'].'index.php?sec=network&amp;sec2=operation/visual_console/render_view&amp;pure='.$config['pure'].'&amp;id='.$layoutData['id_layout_linked'];
                    }
                } else {
                    if (is_metaconsole() === true) {
                        $url = $config['homeurl'].'index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=0&ag_group='.$layoutData['id_group'].'&ag_freestring=&module_option=1&ag_modulename=&moduletype=&datatype=&status=-1&sort_field=&sort=none&pure=';
                    } else {
                        $url = $config['homeurl'].'index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id='.$layoutData['id_group'];
                    }
                }
            break;

            case LABEL:
                if ($layoutData['id_layout_linked'] != 0) {
                    // Link to a map
                    $url = $config['homeurl'].'index.php?sec=network&amp;sec2=operation/visual_console/render_view&amp;pure='.$config['pure'].'&amp;id='.$layoutData['id_layout_linked'];
                }
            break;

            case ICON:
                $url_icon = '';
                if ($layoutData['id_layout_linked'] != 0) {
                    // Link to a map
                    if (empty($layoutData['id_metaconsole'])) {
                        $url = 'index.php?sec=network&amp;sec2=operation/visual_console/render_view&amp;pure='.$config['pure'].'&amp;id='.$layoutData['id_layout_linked'];
                    } else {
                        $pure = get_parameter('pure', 0);
                        $url = 'index.php?sec=screen&sec2=screens/screens&action=visualmap&pure='.$pure.'&id='.$layoutData['id_layout_linked'].'&refr=0';
                    }
                } else if (preg_match('/<a.*href=["\'](.*)["\']>/', $layoutData['label'], $matches)) {
                    // Link to an URL
                    if ($layoutData['enable_link']) {
                        $url = strip_tags($matches[1]);
                    }
                } else if (preg_match('/^.*(http:\/\/)((.)+).*$/i', $layoutData['label'])) {
                    // Link to an URL
                    if ($layoutData['enable_link']) {
                        $url = strip_tags($layoutData['label']);
                    }
                }
            break;

            case SIMPLE_VALUE:
            case SIMPLE_VALUE_MAX:
            case SIMPLE_VALUE_MIN:
            case SIMPLE_VALUE_AVG:
                // Extract id service if it is a prediction module.
                $id_service = db_get_value_filter(
                    'custom_integer_1',
                    'tagente_modulo',
                    [
                        'id_agente_modulo'  => $layoutData['id_agente_modulo'],
                        'prediction_module' => 1,
                    ]
                );

                if (!empty($id_service) && can_user_access_node()) {
                    // Link to an service page
                    if (!empty($layoutData['id_metaconsole'])) {
                        $server = db_get_row(
                            'tmetaconsole_setup',
                            'id',
                            $layoutData['id_metaconsole']
                        );

                        $url = $server['server_url'].'/'.'index.php?sec=services&sec2=enterprise/operation/services/services&id_service='.$id_service.'&offset=0';
                    } else {
                        $url = 'index.php?sec=services&sec2=enterprise/operation/services/services&id_service='.$id_service.'&offset=0';
                    }
                } else if ($layoutData['id_layout_linked'] > 0) {
                    // Link to a map
                    if (empty($layoutData['id_metaconsole'])) {
                        $url = 'index.php?sec=network&amp;sec2=operation/visual_console/render_view&amp;pure='.$config['pure'].'&amp;id='.$layoutData['id_layout_linked'];
                    } else {
                        $pure = get_parameter('pure', 0);
                        $url = 'index.php?sec=screen&sec2=screens/screens&action=visualmap&pure='.$pure.'&id='.$layoutData['id_layout_linked'].'&refr=0';
                    }
                } else if ($layoutData['id_agente_modulo'] != 0) {
                        // Link to an module
                    if (empty($layoutData['id_metaconsole'])) {
                        $url = $config['homeurl'].'index.php?sec=view&amp;sec2=operation/agentes/status_monitor&amp;id_module='.$layoutData['id_agente_modulo'];
                    } else {
                        $url = ui_meta_get_url_console_child(
                            $layoutData['id_metaconsole'],
                            'estado',
                            'operation/agentes/ver_agente&amp;id_agente='.$layoutData['id_agent'],
                            null,
                            null,
                            null,
                            $isExternalLink
                        );
                    }
                }
            break;

            case PERCENTILE_BAR:
            case PERCENTILE_BUBBLE:
            case CIRCULAR_PROGRESS_BAR:
            case CIRCULAR_INTERIOR_PROGRESS_BAR:
                if (!empty($layoutData['id_agent'])) {
                    // Extract id service if it is a prediction module.
                    $id_service = db_get_value_filter(
                        'custom_integer_1',
                        'tagente_modulo',
                        [
                            'id_agente_modulo'  => $layoutData['id_agente_modulo'],
                            'prediction_module' => 1,
                        ]
                    );

                    if (!empty($id_service)) {
                        // Link to an service page
                        if (!empty($layoutData['id_metaconsole'])) {
                            $server = db_get_row(
                                'tmetaconsole_setup',
                                'id',
                                $layoutData['id_metaconsole']
                            );

                            $url = $server['server_url'].'/'.'index.php?sec=services&sec2=enterprise/operation/services/services&id_service='.$id_service.'&offset=0';
                        } else {
                            $url = 'index.php?sec=services&sec2=enterprise/operation/services/services&id_service='.$id_service.'&offset=0';
                        }
                    } else if ($layoutData['id_agente_modulo'] != 0) {
                        // Link to an module
                        if (!empty($layoutData['id_metaconsole'])) {
                            $server = db_get_row(
                                'tmetaconsole_setup',
                                'id',
                                $layoutData['id_metaconsole']
                            );

                            $url = $server['server_url'].'/index.php?sec=view&amp;sec2=operation/agentes/status_monitor&amp;id_module='.$layoutData['id_agente_modulo'];
                        } else {
                            $url = $config['homeurl'].'/index.php?sec=view&amp;sec2=operation/agentes/status_monitor&amp;id_module='.$layoutData['id_agente_modulo'];
                        }
                    } else {
                        // Link to an agent
                        if (empty($layoutData['id_metaconsole'])) {
                            $url = $config['homeurl'].'index.php?'.'sec=estado&amp;'.'sec2=operation/agentes/ver_agente&amp;id_agente='.$layoutData['id_agent'];
                        } else {
                            $url = ui_meta_get_url_console_child(
                                $layoutData['id_metaconsole'],
                                'estado',
                                'operation/agentes/ver_agente&amp;id_agente='.$layoutData['id_agent'],
                                null,
                                null,
                                null,
                                $isExternalLink
                            );
                        }
                    }
                } else if ($layoutData['id_layout_linked'] > 0) {
                    // Link to a map
                    if (empty($layoutData['id_metaconsole'])) {
                        $url = 'index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;pure='.$config['pure'].'&amp;id='.$layoutData['id_layout_linked'];
                    } else {
                        $pure = get_parameter('pure', 0);
                        $url = 'index.php?sec=screen&sec2=screens/screens&action=visualmap&pure='.$pure.'&id='.$layoutData['id_layout_linked'].'&refr=0';
                    }
                }
            break;

            case MODULE_GRAPH:
                if ((($layoutData['id_layout_linked'] == '')
                    || ($layoutData['id_layout_linked'] == 0))
                    && can_user_access_node()
                ) {
                    if ($layoutData['enable_link']) {
                        // Extract id service if it is a prediction module.
                        $id_service = db_get_value_filter(
                            'custom_integer_1',
                            'tagente_modulo',
                            [
                                'id_agente_modulo'  => $layoutData['id_agente_modulo'],
                                'prediction_module' => 1,
                            ]
                        );

                        if (!empty($id_service)) {
                            // Link to an service page
                            if (!empty($layoutData['id_metaconsole'])) {
                                $server = db_get_row(
                                    'tmetaconsole_setup',
                                    'id',
                                    $layoutData['id_metaconsole']
                                );

                                $url = $server['server_url'].'/index.php?sec=services&sec2=enterprise/operation/services/services&id_service='.$id_service.'&offset=0';
                            } else {
                                $url = $config['homeurl'].'/index.php?sec=services&sec2=enterprise/operation/services/services&id_service='.$id_service.'&offset=0';
                            }
                        } else {
                            if (empty($layoutData['id_metaconsole'])) {
                                $url = $config['homeurl'].'/index.php?sec=view&amp;sec2=operation/agentes/status_monitor&amp;id_module='.$layoutData['id_agente_modulo'];
                            } else {
                                $url = ui_meta_get_url_console_child(
                                    $layoutData['id_metaconsole'],
                                    'estado',
                                    'operation/agentes/ver_agente&amp;id_agente='.$layoutData['id_agent'].'&amp;tab=data',
                                    null,
                                    null,
                                    null,
                                    $isExternalLink
                                );
                            }
                        }
                    }
                } else {
                    // Link to a map
                    if (empty($layoutData['id_metaconsole'])) {
                        $url = $config['homeurl'].'/index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;pure='.$config['pure'].'&amp;id='.$layoutData['id_layout_linked'];
                    } else {
                        $pure = get_parameter('pure', 0);
                        $url = $config['homeurl'].'/index.php?sec=screen&sec2=screens/screens&action=visualmap&pure='.$pure.'&id='.$layoutData['id_layout_linked'].'&refr=0';
                    }
                }
            break;

            default:
                if (!empty($element_enterprise)) {
                    $url = $element_enterprise['url'];
                }
            break;
        }

        // Override url
        if (is_metaconsole()
            && !empty($layoutData['id_layout_linked'])
            && !empty($layoutData['linked_layout_node_id'])
        ) {
            $url = ui_meta_get_url_console_child(
                $layoutData['linked_layout_node_id'],
                'network',
                'operation/visual_console/render_view&amp;id='.(int) $layoutData['id_layout_linked']
            );
        }
    }

    // + 1 for to avoid the box and lines items are on the top of
    // others
    $z_index = (1 + 1);

    switch ($type) {
        case STATIC_GRAPH:
        case GROUP_ITEM:
            if ($layoutData['image'] != null) {
                $img = visual_map_get_image_status_element(
                    $layoutData,
                    $layoutData['status_calculated']
                );
                if (substr($img, 0, 1) == '4') {
                    $borderStyle = 'border: 2px solid '.COL_ALERTFIRED.';';
                    $img = substr_replace($img, '', 0, 1);
                }
            }

            if ($status == VISUAL_MAP_STATUS_CRITICAL_BAD) {
                $z_index = (3 + 1);
            } else if ($status == VISUAL_MAP_STATUS_WARNING) {
                $z_index = (2 + 1);
            } else if ($status == VISUAL_MAP_STATUS_CRITICAL_ALERT) {
                $z_index = (4 + 1);
            } else {
                $z_index = (1 + 1);
            }
        break;

        case ICON:
            if ($layoutData['image'] != null) {
                $img = visual_map_get_image_status_element(
                    $layoutData,
                    $layoutData['status_calculated']
                );
            }

            if (($width != 0) && ($height != 0)) {
                $sizeStyle = 'width: '.$width.'px; height: '.$height.'px;';
                $imageSize = 'width="'.$width.'" height="'.$height.'"';
            }

            $z_index = (4 + 1);
        break;

        case PERCENTILE_BAR:
        case PERCENTILE_BUBBLE:
        case CIRCULAR_PROGRESS_BAR:
        case CIRCULAR_INTERIOR_PROGRESS_BAR:
            // Metaconsole db connection
            if ($layoutData['id_metaconsole'] != 0) {
                $connection = db_get_row_filter(
                    'tmetaconsole_setup',
                    ['id' => $layoutData['id_metaconsole']]
                );
                if (metaconsole_load_external_db($connection) != NOERR) {
                    // ui_print_error_message ("Error connecting to ".$server_name);
                    break;
                }
            }

            // data
            $module_value = db_get_sql(
                'SELECT datos
                FROM tagente_estado
                WHERE id_agente_modulo = '.$id_module
            );
            // state
            $module_status = db_get_sql(
                'SELECT estado
                FROM tagente_estado
                WHERE id_agente_modulo = '.$id_module
            );

            if (empty($module_value) || $module_value == 0) {
                $colorStatus = COL_UNKNOWN;
            } else {
                switch ($module_status) {
                    case 0:
                        // Normal
                        $colorStatus = COL_NORMAL;
                    break;

                    case 1:
                        // Critical
                        $colorStatus = COL_CRITICAL;
                    break;

                    case 2:
                        // Warning
                        $colorStatus = COL_WARNING;
                    break;

                    case 4:
                        // Not_INIT
                        $colorStatus = COL_NOTINIT;
                    break;

                    case 3:
                        // Unknown
                    default:
                        $colorStatus = COL_UNKNOWN;
                    break;
                }
            }

            $value_text = false;
            if ($layoutData['image'] == 'percent') {
                $value_text = false;
            } else if ($layoutData['image'] == 'value') {
                $unit_text = db_get_sql(
                    'SELECT unit
                    FROM tagente_modulo
                    WHERE id_agente_modulo = '.$id_module
                );
                $unit_text = trim(io_safe_output($unit_text));

                $value_text = format_for_graph($module_value, 2);
                if ($value_text <= 0) {
                    $value_text = remove_right_zeros(number_format($module_value, $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator']));
                }

                if (!empty($unit_text)) {
                    $value_text .= ' '.$unit_text;
                }
            }

            // Restore db connection
            if ($layoutData['id_metaconsole'] != 0) {
                metaconsole_restore_db();
            }

            if ($max_percentile > 0) {
                $percentile = format_numeric(($module_value / $max_percentile * 100), 0);
            } else {
                $percentile = 100;
            }
        break;

        case MODULE_GRAPH:

            $imgpos = '';

            if ($layoutData['label_position'] == 'left') {
                $imgpos = 'float:right';
            } else if ($layoutData['label_position'] == 'right') {
                $imgpos = 'float:left';
            }

            if (!empty($proportion)) {
                $width = ((integer) ($proportion['proportion_width'] * $width));
                $height = ((integer) ($proportion['proportion_height'] * $height));
            }

            // Metaconsole db connection
            if ($layoutData['id_metaconsole'] != 0) {
                $connection = db_get_row_filter(
                    'tmetaconsole_setup',
                    ['id' => $layoutData['id_metaconsole']]
                );
                if (metaconsole_load_external_db($connection) != NOERR) {
                    // ui_print_error_message ("Error connecting to ".$server_name);
                    break;
                }
            }

            $only_image = !$graph_javascript && $isExternalLink;

            if ($layoutData['id_custom_graph'] != 0) {
                // Show only avg on the visual console
                if (get_parameter('action') == 'edit') {
                    if ($width == 0 || $height == 0) {
                        $img = '<img src="images/console/signes/custom_graph.png" style="width:300px;height:180px;'.$imgpos.'">';
                    } else {
                        $img = '<img src="images/console/signes/custom_graph.png" style="width:'.$width.'px;height:'.$height.'px;'.$imgpos.'">';
                    }
                } else {
                    if ($width == 0) {
                        $width = 180;
                    }

                    if ($height == 0) {
                        $height = 480;
                    }

                    $graphs = db_get_all_rows_field_filter('tgraph', 'id_graph', $layoutData['id_custom_graph']);

                    $params = [
                        'period'          => $period,
                        'width'           => $width,
                        'height'          => $height,
                        'title'           => '',
                        'unit_name'       => null,
                        'show_alerts'     => false,
                        'only_image'      => $only_image,
                        'vconsole'        => true,
                        'backgroundColor' => $layoutData['image'],
                    ];

                    $params_combined = [
                        'id_graph'       => $layoutData['id_custom_graph'],
                        'stacked'        => $graphs[0]['stacked'],
                        'summatory'      => $graphs[0]['summatory_series'],
                        'average'        => $graphs[0]['average_series'],
                        'modules_series' => $graphs[0]['modules_series'],
                    ];

                    if ($layoutData['label_position'] == 'left') {
                        $img = '<div style="z-index:'.$show_on_top_index.';float:right;height:'.$himg.'px;">'.graphic_combined_module(
                            false,
                            $params,
                            $params_combined
                        ).'</div>';
                    } else if ($layoutData['label_position'] == 'right') {
                        $img = '<div style="z-index:'.$show_on_top_index.';float:left;height:'.$himg.'px;">'.graphic_combined_module(
                            false,
                            $params,
                            $params_combined
                        ).'</div>';
                    } else {
                        $img = '<div style="width:'.$width.'px; height:'.$height.'px;">';
                        $img .= graphic_combined_module(
                            false,
                            $params,
                            $params_combined
                        );
                        $img .= '</div>';
                    }
                }
            } else {
                if ($isExternalLink) {
                    $homeurl = $config['homeurl'];
                } else {
                    $homeurl = '';
                }

                if ((get_parameter('action') == 'edit') || (get_parameter('operation') == 'edit_visualmap')) {
                    if ($width == 0 || $height == 0) {
                        if ($layoutData['id_metaconsole'] != 0) {
                            $img = '<img src="../../images/console/signes/module_graph.png" style="width:300px;height:180px;'.$imgpos.'">';
                        } else {
                            $img = '<img src="images/console/signes/module_graph.png" style="width:300px;height:180px;'.$imgpos.'">';
                        }
                    } else {
                        if ($layoutData['id_metaconsole'] != 0) {
                            $img = '<img src="../../images/console/signes/module_graph.png" style="width:'.$width.'px;height:'.$height.'px;'.$imgpos.'">';
                        } else {
                            $img = '<img src="images/console/signes/module_graph.png" style="width:'.$width.'px;height:'.$height.'px;'.$imgpos.'">';
                        }
                    }
                } else {
                    if ($width == 0 || $height == 0) {
                        $width = 300;
                        $height = 180;
                    }

                    $params = [
                        'agent_module_id' => $id_module,
                        'period'          => $period,
                        'show_events'     => false,
                        'width'           => $width,
                        'height'          => $height,
                        'title'           => modules_get_agentmodule_name($id_module),
                        'unit'            => modules_get_unit($id_module),
                        'only_image'      => $only_image,
                        'menu'            => false,
                        'backgroundColor' => $layoutData['image'],
                        'type_graph'      => $type_graph,
                        'vconsole'        => true,
                    ];

                    if ($layoutData['label_position'] == 'left') {
                        $img = '<div style="z-index:'.$show_on_top_index.';float:right;height:'.$himg.'px;">'.grafico_modulo_sparse($params).'</div>';
                    } else if ($layoutData['label_position'] == 'right') {
                        $img = '<div style="z-index:'.$show_on_top_index.';float:left;height:'.$himg.'px;">'.grafico_modulo_sparse($params).'</div>';
                    } else {
                        $img = grafico_modulo_sparse($params);
                    }
                }
            }

            // Restore db connection
            if ($layoutData['id_metaconsole'] != 0) {
                metaconsole_restore_db();
            }
        break;

        case BARS_GRAPH:
            $imgpos = '';

            if ($layoutData['label_position'] == 'left') {
                $imgpos = 'float:right';
            } else if ($layoutData['label_position'] == 'right') {
                $imgpos = 'float:left';
            }

            if (!empty($proportion)) {
                $width = ((integer) ($proportion['proportion_width'] * $width));
                $height = ((integer) ($proportion['proportion_height'] * $height));
            }

            // Metaconsole db connection
            if ($layoutData['id_metaconsole'] != 0) {
                $connection = db_get_row_filter(
                    'tmetaconsole_setup',
                    ['id' => $layoutData['id_metaconsole']]
                );
                if (metaconsole_load_external_db($connection) != NOERR) {
                    break;
                }
            }

            if ($isExternalLink) {
                $homeurl = $config['homeurl'];
            } else {
                $homeurl = '';
            }

            $is_string = db_get_value_filter(
                'id_tipo_modulo',
                'tagente_modulo',
                [
                    'id_agente'        => $layoutData['id_agent'],
                    'id_agente_modulo' => $id_module,
                ]
            );

            if ((get_parameter('action') == 'edit') || (get_parameter('operation') == 'edit_visualmap')) {
                if ($width == 0) {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../'.'images/console/signes/barras.png'.'" style="width:400px;height:400px;'.$imgpos.'">';
                    } else {
                        $img = '<img src="'.'images/console/signes/barras.png'.'" style="width:400px;height:400px;'.$imgpos.'">';
                    }
                } else {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../'.'images/console/signes/barras.png'.'" style="width:'.$width.'px;height:'.$height.'px;'.$imgpos.'">';
                    } else {
                        $img = '<img src="'.'images/console/signes/barras.png'.'" style="width:'.$width.'px;height:'.$height.'px;'.$imgpos.'">';
                    }
                }
            } else {
                $color = [];

                $color[0] = [
                    'border' => '#000000',
                    'color'  => $config['graph_color1'],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[1] = [
                    'border' => '#000000',
                    'color'  => $config['graph_color2'],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[2] = [
                    'border' => '#000000',
                    'color'  => $config['graph_color3'],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[3] = [
                    'border' => '#000000',
                    'color'  => $config['graph_color4'],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[4] = [
                    'border' => '#000000',
                    'color'  => $config['graph_color5'],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[5] = [
                    'border' => '#000000',
                    'color'  => $config['graph_color6'],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[6] = [
                    'border' => '#000000',
                    'color'  => $config['graph_color7'],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[7] = [
                    'border' => '#000000',
                    'color'  => $config['graph_color8'],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[8] = [
                    'border' => '#000000',
                    'color'  => $config['graph_color9'],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[9] = [
                    'border' => '#000000',
                    'color'  => $config['graph_color10'],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[11] = [
                    'border' => '#000000',
                    'color'  => COL_GRAPH9,
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[12] = [
                    'border' => '#000000',
                    'color'  => COL_GRAPH10,
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[13] = [
                    'border' => '#000000',
                    'color'  => COL_GRAPH11,
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[14] = [
                    'border' => '#000000',
                    'color'  => COL_GRAPH12,
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
                $color[15] = [
                    'border' => '#000000',
                    'color'  => COL_GRAPH13,
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];

                $module_data = get_bars_module_data(
                    $id_module,
                    $layoutData['type_graph']
                );
                $options = [];
                $options['generals']['rotate'] = true;
                $options['generals']['forceTicks'] = true;
                $options['generals']['arrayColors'] = $color;
                $options['x']['labelWidth'] = 60;

                $water_mark = [
                    'file' => '/var/www/html/pandora_console/images/logo_vertical_water.png',
                    'url'  => 'http://localhost/pandora_console/images/logo_vertical_water.png',
                ];

                if ($width == 0 && $height == 0) {
                    if ($layoutData['label_position'] == 'left') {
                        if ($layoutData['type_graph'] == 'horizontal') {
                            $img = '<div style="float:right;height:'.$himg.'px;">'.hbar_graph(
                                $module_data,
                                400,
                                400,
                                $color,
                                [],
                                [],
                                ui_get_full_url('images/image_problem_area.png', false, false, false),
                                '',
                                '',
                                $water_mark,
                                $config['fontpath'],
                                6,
                                '',
                                0,
                                $config['homeurl'],
                                $layoutData['image'],
                                $layoutData['border_color']
                            ).'</div>';
                        } else {
                            $img = '<div style="float:right;height:'.$himg.'px;">';
                            $img .= vbar_graph($module_data, $options, 1);
                            $img .= '</div>';
                        }
                    } else if ($layoutData['label_position'] == 'right') {
                        if ($layoutData['type_graph'] == 'horizontal') {
                            $img = '<div style="float:left;height:'.$himg.'px;">'.hbar_graph(
                                $module_data,
                                400,
                                400,
                                $color,
                                [],
                                [],
                                ui_get_full_url('images/image_problem_area.png', false, false, false),
                                '',
                                '',
                                $water_mark,
                                $config['fontpath'],
                                6,
                                '',
                                0,
                                $config['homeurl'],
                                $layoutData['image'],
                                $layoutData['border_color']
                            ).'</div>';
                        } else {
                            $img = '<div style="float:left;height:'.$himg.'px;">';
                            $img .= vbar_graph($module_data, $options, 1);
                            $img .= '</div>';
                        }
                    } else {
                        if ($layoutData['type_graph'] == 'horizontal') {
                            $img = hbar_graph(
                                $module_data,
                                400,
                                400,
                                $color,
                                [],
                                [],
                                ui_get_full_url('images/image_problem_area.png', false, false, false),
                                '',
                                '',
                                $water_mark,
                                $config['fontpath'],
                                6,
                                '',
                                0,
                                $config['homeurl'],
                                $layoutData['image'],
                                $layoutData['border_color']
                            );
                        } else {
                            $img = vbar_graph($module_data, $options, 1);
                        }
                    }
                } else {
                    if ($layoutData['label_position'] == 'left') {
                        if ($layoutData['type_graph'] == 'horizontal') {
                            $img = '<div style="float:right;height:'.$himg.'px;">'.hbar_graph(
                                $module_data,
                                $width,
                                $height,
                                $color,
                                [],
                                [],
                                ui_get_full_url('images/image_problem_area.png', false, false, false),
                                '',
                                '',
                                $water_mark,
                                $config['fontpath'],
                                6,
                                '',
                                0,
                                $config['homeurl'],
                                $layoutData['image'],
                                $layoutData['border_color']
                            ).'</div>';
                        } else {
                            $img = '<div style="float:right;height:'.$himg.'px;">';
                            $img .= vbar_graph($module_data, $options, 1);
                            $img .= '</div>';
                        }
                    } else if ($layoutData['label_position'] == 'right') {
                        if ($layoutData['type_graph'] == 'horizontal') {
                            $img = '<div style="float:left;height:'.$himg.'px;">'.hbar_graph(
                                $module_data,
                                $width,
                                $height,
                                $color,
                                [],
                                [],
                                ui_get_full_url('images/image_problem_area.png', false, false, false),
                                '',
                                '',
                                $water_mark,
                                $config['fontpath'],
                                6,
                                '',
                                0,
                                $config['homeurl'],
                                $layoutData['image'],
                                $layoutData['border_color']
                            ).'</div>';
                        } else {
                            $img = '<div style="float:left;height:'.$himg.'px;">';
                            $img .= vbar_graph($module_data, $options, 1);
                            $img .= '</div>';
                        }
                    } else {
                        if ($layoutData['type_graph'] == 'horizontal') {
                            $img = hbar_graph(
                                $module_data,
                                $width,
                                $height,
                                $color,
                                [],
                                [],
                                ui_get_full_url('images/image_problem_area.png', false, false, false),
                                '',
                                '',
                                $water_mark,
                                $config['fontpath'],
                                6,
                                '',
                                0,
                                $config['homeurl'],
                                $layoutData['image'],
                                $layoutData['border_color']
                            );
                        } else {
                            $img = '<div style="width:'.$width.'px; height:'.$height.'px;">';
                            $img .= vbar_graph($module_data, $options, 1);
                            $img .= '</div>';
                        }
                    }
                }
            }

            // Restore db connection
            if ($layoutData['id_metaconsole'] != 0) {
                metaconsole_restore_db();
            }
        break;

        case DONUT_GRAPH:
            if (!empty($id_metaconsole)) {
                $connection = db_get_row_filter('tmetaconsole_setup', $id_metaconsole);
                if (metaconsole_load_external_db($connection) != NOERR) {
                    break;
                }
            }

            $is_string = db_get_value_filter(
                'id_tipo_modulo',
                'tagente_modulo',
                [
                    'id_agente'        => $layoutData['id_agent'],
                    'id_agente_modulo' => $id_module,
                ]
            );

            if (!empty($id_metaconsole)) {
                metaconsole_restore_db();
            }

            if (($is_string == 17) || ($is_string == 23) || ($is_string == 3)
                || ($is_string == 10) || ($is_string == 33)
            ) {
                $no_data = false;
            } else {
                $no_data = true;
            }

            if ($no_data) {
                if ($width == 0) {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../images/console/signes/wrong_donut_graph.png">';
                    } else {
                        $img = '<img src="images/console/signes/wrong_donut_graph.png">';
                    }
                } else {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../images/console/signes/wrong_donut_graph.png" style="width:'.$width.'px;height:'.$height.'px;">';
                    } else {
                        $img = '<img src="images/console/signes/wrong_donut_graph.png" style="width:'.$width.'px;height:'.$height.'px;">';
                    }
                }
            } else {
                $donut_data = get_donut_module_data($layoutData['id_agente_modulo']);

                if ((get_parameter('action') == 'edit') || (get_parameter('operation') == 'edit_visualmap')) {
                    if ($width == 0) {
                        if ($layoutData['id_metaconsole'] != 0) {
                            $img = '<img src="../../images/console/signes/donut-graph.png">';
                        } else {
                            $img = '<img src="images/console/signes/donut-graph.png">';
                        }
                    } else {
                        if ($layoutData['id_metaconsole'] != 0) {
                            $img = '<img src="../../images/console/signes/donut-graph.png" style="width:'.$width.'px;height:'.$height.'px;">';
                        } else {
                            $img = '<img src="images/console/signes/donut-graph.png" style="width:'.$width.'px;height:'.$height.'px;">';
                        }
                    }
                } else {
                    if ($width == 0) {
                        $img = d3_donut_graph($layoutData['id'], 300, 300, $donut_data, $layoutData['border_color']);
                    } else {
                        $img = d3_donut_graph($layoutData['id'], $width, $width, $donut_data, $layoutData['border_color']);
                    }
                }
            }

            // Restore db connection
            if ($layoutData['id_metaconsole'] != 0) {
                metaconsole_restore_db();
            }

            $z_index = (2 + 1);
        break;

        case LABEL:
            $z_index = (4 + 1);
        break;

        case BOX_ITEM:
            $z_index = 1;
        break;

        case CLOCK:
            if ((get_parameter('action') == 'edit') || (get_parameter('operation') == 'edit_visualmap')) {
                $image_prefix = (is_metaconsole()) ? '../../' : '';
                if ($width == 0) {
                    if ($layoutData['id_metaconsole'] != 0) {
                        if ($layoutData['clock_animation'] == 'analogic_1') {
                            $img = '<img src="../../images/console/signes/clock.png" class="w200px height_240px">';
                        } else {
                            if ($layoutData['time_format'] = 'time') {
                                $img = '<img src="../../images/console/signes/digital-clock.png" class="w200px height_71px">';
                            } else {
                                $img = '<img src="../../images/console/signes/digital-clock.png" class="w200px height_91px">';
                            }
                        }
                    } else {
                        if ($layoutData['clock_animation'] == 'analogic_1') {
                            $img = '<img src="'.$image_prefix.'images/console/signes/clock.png" class="w200px height_240px">';
                        } else {
                            if ($layoutData['time_format'] == 'time') {
                                $img = '<img src="'.$image_prefix.'images/console/signes/digital-clock.png" class="w200px height_71px">';
                            } else {
                                $img = '<img src="'.$image_prefix.'images/console/signes/digital-clock.png" class="w200px height_91px">';
                            }
                        }
                    }
                } else {
                    if ($layoutData['clock_animation'] == 'analogic_1') {
                        $img = '<img src="'.$image_prefix.'images/console/signes/clock.png" style="width:'.$width.'px;height:'.($width + 40).'px;">';
                    } else {
                        $height_offset = ($layoutData['time_format'] == 'time') ? 20 : 40;
                        $img = '<img src="'.$image_prefix.'images/console/signes/digital-clock.png" style="width:'.$width.'px;height:'.(($width / 3.9) + $height_offset).'px;">';
                    }
                }
            } else {
                if ($layoutData['clock_animation'] == 'analogic_1') {
                    if ($layoutData['label_position'] == 'left') {
                        $img = '<div id="clock_'.$layoutData['id'].'" style="z-index:'.$show_on_top_index.';float:right;height:'.$himg.'px;margin-bottom:15px;">'.print_clock_analogic_1($layoutData['time_format'], $layoutData['timezone'], $layoutData['clock_animation'], $layoutData['width'], $layoutData['height'], $layoutData['id'], $layoutData['fill_color']).'</div>';
                    } else if ($layoutData['label_position'] == 'right') {
                        $img = '<div id="clock_'.$layoutData['id'].'" style="z-index:'.$show_on_top_index.';float:left;height:'.$himg.'px;margin-bottom:15px;">'.print_clock_analogic_1($layoutData['time_format'], $layoutData['timezone'], $layoutData['clock_animation'], $layoutData['width'], $layoutData['height'], $layoutData['id'], $layoutData['fill_color']).'</div>';
                    } else {
                        $img = '<div id="clock_'.$layoutData['id'].'" style="z-index:'.$show_on_top_index.';height:'.$himg.'px;margin-bottom:15px;">'.print_clock_analogic_1($layoutData['time_format'], $layoutData['timezone'], $layoutData['clock_animation'], $layoutData['width'], $layoutData['height'], $layoutData['id'], $layoutData['fill_color']).'</div>';
                    }
                } else if ($layoutData['clock_animation'] == 'digital_1') {
                    if ($layoutData['label_position'] == 'left') {
                        $img = '<div id="clock_'.$layoutData['id'].'" style="z-index:'.$show_on_top_index.';float:right;height:'.$himg.'px;">'.print_clock_digital_1($layoutData['time_format'], $layoutData['timezone'], $layoutData['clock_animation'], $layoutData['width'], $layoutData['height'], $layoutData['id'], $layoutData['fill_color']).'</div>';
                    } else if ($layoutData['label_position'] == 'right') {
                        $img = '<div id="clock_'.$layoutData['id'].'" style="z-index:'.$show_on_top_index.';float:left;height:'.$himg.'px;">'.print_clock_digital_1($layoutData['time_format'], $layoutData['timezone'], $layoutData['clock_animation'], $layoutData['width'], $layoutData['height'], $layoutData['id'], $layoutData['fill_color']).'</div>';
                    } else {
                        $img = '<div id="clock_'.$layoutData['id'].'" style="z-index:'.$show_on_top_index.';height:'.$himg.'px;">'.print_clock_digital_1($layoutData['time_format'], $layoutData['timezone'], $layoutData['clock_animation'], $layoutData['width'], $layoutData['height'], $layoutData['id'], $layoutData['fill_color']).'</div>';
                    }
                }
            }
        break;

        case AUTO_SLA_GRAPH:
            if ((get_parameter('action') == 'edit') || (get_parameter('operation') == 'edit_visualmap')) {
                if ($width == 0 || $height == 0) {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../'.'images/console/signes/module-events.png'.'">';
                    } else {
                        $img = '<img src="'.'images/console/signes/module-events.png'.'">';
                    }
                } else {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../'.'images/console/signes/module-events.png'.'" style="width:'.$width.'px;height:'.$height.'px;">';
                    } else {
                        $img = '<img src="'.'images/console/signes/module-events.png'.'" style="width:'.$width.'px;height:'.$height.'px;">';
                    }
                }
            } else {
                if ($width == 0 || $height == 0) {
                    if ($layoutData['label_position'] == 'left') {
                        $img = '<div style="z-index:'.$show_on_top_index.';float:left;height:'.$himg.'px;width:500px;">'.graph_graphic_moduleevents($layoutData['id_agent'], $layoutData['id_agente_modulo'], 500, 50, $layoutData['period'], '', true).'</div>';
                    } else if ($layoutData['label_position'] == 'right') {
                        $img = '<div style="z-index:'.$show_on_top_index.';float:right;height:'.$himg.'px;width:500px;">'.graph_graphic_moduleevents($layoutData['id_agent'], $layoutData['id_agente_modulo'], 500, 50, $layoutData['period'], '', true).'</div>';
                    } else {
                        $img = '<div class="w500px">'.graph_graphic_moduleevents($layoutData['id_agent'], $layoutData['id_agente_modulo'], 500, 50, $layoutData['period'], '', true).'</div>';
                    }
                } else {
                    if ($layoutData['label_position'] == 'left') {
                        $img = '<div style="z-index:'.$show_on_top_index.';float:left;height:'.$himg.'px;width:500px;">'.graph_graphic_moduleevents($layoutData['id_agent'], $layoutData['id_agente_modulo'], $width, $height, $layoutData['period'], '', true).'</div>';
                    } else if ($layoutData['label_position'] == 'right') {
                        $img = '<div style="z-index:'.$show_on_top_index.';float:right;height:'.$himg.'px;width:500px;">'.graph_graphic_moduleevents($layoutData['id_agent'], $layoutData['id_agente_modulo'], $width, $height, $layoutData['period'], '', true).'</div>';
                    } else {
                        $img = '<div class="w500px">'.graph_graphic_moduleevents($layoutData['id_agent'], $layoutData['id_agente_modulo'], $width, $height, $layoutData['period'], '', true).'</div>';
                    }
                }
            }

            // Restore db connection
            if ($layoutData['id_metaconsole'] != 0) {
                metaconsole_restore_db();
            }

            $z_index = (2 + 1);
        break;
    }

    $class = 'item ';
    switch ($type) {
        case STATIC_GRAPH:
            $class .= 'static_graph';
        break;

        case AUTO_SLA_GRAPH:
            $class .= 'auto_sla_graph';
        break;

        case GROUP_ITEM:
            $class .= 'group_item';
        break;

        case DONUT_GRAPH:
            $class .= 'donut_graph';
        break;

        case PERCENTILE_BAR:
        case PERCENTILE_BUBBLE:
        case CIRCULAR_PROGRESS_BAR:
        case CIRCULAR_INTERIOR_PROGRESS_BAR:
            $class .= 'percentile_item';
        break;

        case MODULE_GRAPH:
            $class .= 'module_graph';
        break;

        case BARS_GRAPH:
            $class .= 'bars_graph';
        break;

        case SIMPLE_VALUE:
        case SIMPLE_VALUE_MAX:
        case SIMPLE_VALUE_MIN:
        case SIMPLE_VALUE_AVG:
            $class .= 'simple_value';
        break;

        case LABEL:
            $class .= 'label';
        break;

        case ICON:
            $class .= 'icon';
        break;

        case CLOCK:
            $class .= 'clock';
        break;

        case BOX_ITEM:
            $class .= 'box_item';
        break;

        case COLOR_CLOUD:
            $class .= 'color_cloud';
        break;

        default:
            if (!empty($element_enterprise)) {
                $class .= $element_enterprise['class'];
            }
        break;
    }

    if ($show_on_top) {
        $z_index = 10;
    }

    echo '<div id="'.$id.'" class="'.$class.'" '.'style="z-index: '.$z_index.';'.'position: absolute; '.'top: '.$top.'px; '.'left: '.$left.'px;'.'display: inline-block; '.$sizeStyle.'">';

    if ($link) {
        echo "<a href=\"$url\">";
    }

    // for clean link text from bbdd only edit_visualmap
    if (get_parameter('action') == 'edit' || get_parameter('operation') == 'edit_visualmap') {
        $text = preg_replace('/<\/*a.*?>/', '', $text);
    }

    switch ($type) {
        case BOX_ITEM:
            if ($width == 0 || $width == 0) {
                $style = '';
                $style .= 'width: 300px; ';
                $style .= 'height: 180px; ';
                $style .= 'border-style: solid; ';
                $style .= 'border-width: '.$border_width.'px; ';
                $style .= 'border-color: '.$border_color.'; ';
                $style .= 'background-color: '.$fill_color.'; ';
                echo "<div style='z-index:".$show_on_top_index.';'.$style."'></div>";
            } else {
                if (!empty($proportion)) {
                    $style = '';
                    $style .= 'width: '.($width * $proportion['proportion_width']).'px; ';
                    $style .= 'height: '.($height * $proportion['proportion_height']).'px; ';
                    $style .= 'border-style: solid; ';
                    $style .= 'border-width: '.$border_width.'px; ';
                    $style .= 'border-color: '.$border_color.'; ';
                    $style .= 'background-color: '.$fill_color.'; ';
                    echo "<div style='z-index:".$show_on_top_index.';'.$style."'></div>";
                } else {
                    $style = '';
                    $style .= 'width: '.$width.'px; ';
                    $style .= 'height: '.$height.'px; ';
                    $style .= 'border-style: solid; ';
                    $style .= 'border-width: '.$border_width.'px; ';
                    $style .= 'border-color: '.$border_color.'; ';
                    $style .= 'background-color: '.$fill_color.'; ';
                    echo "<div style='z-index:".$show_on_top_index.';'.$style."'></div>";
                }
            }
        break;

        case STATIC_GRAPH:
        case GROUP_ITEM:
            if (($layoutData['image'] != null && $layoutData['image'] != 'none') || $layoutData['show_statistics'] == 1) {
                $img_style_title = strip_tags($label);
                if ($layoutData['type'] == STATIC_GRAPH) {
                    if ($layoutData['id_agente_modulo'] != 0) {
                        if (is_metaconsole() && $layoutData['id_metaconsole'] != 0) {
                            // Metaconsole db connection
                            $connection = db_get_row_filter(
                                'tmetaconsole_setup',
                                ['id' => $layoutData['id_metaconsole']]
                            );
                            if (metaconsole_load_external_db($connection) != NOERR) {
                                break;
                            }
                        }

                        $unit_text = trim(
                            io_safe_output(
                                modules_get_unit($layoutData['id_agente_modulo'])
                            )
                        );

                        $value = modules_get_last_value(
                            $layoutData['id_agente_modulo']
                        );

                        if (!is_string($value)) {
                            $value = format_for_graph($value, 2);
                        }

                        // Hide value on boolean modules
                        if ($layoutData['show_last_value'] != 2) {
                            if ((!modules_is_boolean($layoutData['id_agente_modulo']))
                                || (modules_is_boolean($layoutData['id_agente_modulo']) && $layoutData['show_last_value'] != 0)
                            ) {
                                if (is_numeric($value)) {
                                    $img_style_title .= ' <br>'.__('Last value: ').remove_right_zeros(number_format($value, $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator']));
                                } else {
                                    $img_style_title .= ' <br>'.__('Last value: ').$value;
                                }
                            }
                        }

                        if (!empty($unit_text)) {
                            $img_style_title .= ' '.$unit_text;
                        }

                        if ($layoutData['id_metaconsole'] != 0) {
                            // Restore db connection
                            metaconsole_restore_db();
                        }
                    }

                    if (get_parameter('action') == 'edit') {
                        $img_style_title = '';
                    }
                }

                if (!empty($proportion)) {
                    if (is_file($config['homedir'].'/'.$img)) {
                        $infoImage = getimagesize($config['homedir'].'/'.$img);
                    }

                    if ($height == 0 || $height == 0) {
                        $height = '70px';
                        $width = '70px';
                    } else {
                        $height = (integer) ($proportion['proportion_height'] * $height);
                        $width = (integer) ($proportion['proportion_width'] * $width);
                    }
                }

                $imgpos = '';

                if ($layoutData['label_position'] == 'up') {
                    echo io_safe_output($text);
                }

                if ($layoutData['label_position'] == 'left') {
                    $imgpos = 'float:right';
                } else if ($layoutData['label_position'] == 'right') {
                    $imgpos = 'float:left';
                }

                $varsize = getimagesize($config['homedir'].'/'.$img);

                if ($layoutData['show_statistics'] == 1) {
                    if (get_parameter('action') == 'edit') {
                        if ($width == 0 || $height == 0) {
                            echo '<img id="image_'.$id.'" src="'.'images/console/signes/group_status.png'.'" style="width:520px;height:80px;'.$imgpos.'">';
                        } else {
                            echo '<img id="image_'.$id.'" src="'.'images/console/signes/group_status.png'.'" style="width:'.$width.'px;height:'.$height.'px;'.$imgpos.'">';
                        }
                    } else {
                        $is_meta = is_metaconsole();

                        $agents_critical = agents_get_agents(
                            [
                                'disabled' => 0,
                                'id_grupo' => $layoutData['id_group'],
                                'status'   => AGENT_STATUS_CRITICAL,
                            ],
                            ['COUNT(*) as total'],
                            'AR',
                            false,
                            false,
                            0,
                            $is_meta
                        );
                        $agents_warning = agents_get_agents(
                            [
                                'disabled' => 0,
                                'id_grupo' => $layoutData['id_group'],
                                'status'   => AGENT_STATUS_WARNING,
                            ],
                            ['COUNT(*) as total'],
                            'AR',
                            false,
                            false,
                            0,
                            $is_meta
                        );
                        $agents_unknown = agents_get_agents(
                            [
                                'disabled' => 0,
                                'id_grupo' => $layoutData['id_group'],
                                'status'   => AGENT_STATUS_UNKNOWN,
                            ],
                            ['COUNT(*) as total'],
                            'AR',
                            false,
                            false,
                            0,
                            $is_meta
                        );
                        $agents_ok = agents_get_agents(
                            [
                                'disabled' => 0,
                                'id_grupo' => $layoutData['id_group'],
                                'status'   => AGENT_STATUS_OK,
                            ],
                            ['COUNT(*) as total'],
                            'AR',
                            false,
                            false,
                            0,
                            $is_meta
                        );
                        $total_agents = ($agents_critical[0]['total'] + $agents_warning[0]['total'] + $agents_unknown[0]['total'] + $agents_ok[0]['total']);
                        $stat_agent_ok = ($agents_ok[0]['total'] / $total_agents * 100);
                        $stat_agent_wa = ($agents_warning[0]['total'] / $total_agents * 100);
                        $stat_agent_cr = ($agents_critical[0]['total'] / $total_agents * 100);
                        $stat_agent_un = ($agents_unknown[0]['total'] / $total_agents * 100);
                        if ($width == 0 || $height == 0) {
                            $dyn_width = 520;
                            $dyn_height = 80;
                        } else {
                            $dyn_width = $width;
                            $dyn_height = $height;
                        }

                        echo '<table cellpadding="0" cellspacing="0" border="0" class="databox" style="width:'.$dyn_width.'px;height:'.$dyn_height.'px;text-align:center;';
                        if ($layoutData['label_position'] == 'left') {
                            echo 'float:right;';
                        } else if ($layoutData['label_position'] == 'right') {
                            echo 'float:left;';
                        }

                        echo '">';
                            echo "<tr class='height_10p'>";
                                echo "<th class='tr_vm_colors'>".groups_get_name($layoutData['id_group'], true).'</th>';
                            echo '</tr>';
                            echo "<tr class='bg_whitesmoke height_90p'>";
                                echo '<td>';
                                    echo "<div class='critical_zeros'>".remove_right_zeros(number_format($stat_agent_cr, 2, $config['decimal_separator'], $config['thousand_separator'])).'%</div>';
                                    echo "<div class='critical_vm'>Critical</div>";
                                    echo "<div class='warning_zeros'>".remove_right_zeros(number_format($stat_agent_wa, 2, $config['decimal_separator'], $config['thousand_separator'])).'%</div>';
                                    echo "<div class='warning_vm'>Warning</div>";
                                    echo "<div class='normal_zeros'>".remove_right_zeros(number_format($stat_agent_ok, 2, $config['decimal_separator'], $config['thousand_separator'])).'%</div>';
                                    echo "<div class='normal_vm'>Normal</div>";
                                    echo "<div class='unknown_zeros'>".remove_right_zeros(number_format($stat_agent_un, 2, $config['decimal_separator'], $config['thousand_separator'])).'%</div>';
                                    echo "<div class='unknown_vm'>Unknown</div>";
                                echo '</td>';
                            echo '</tr>';
                        echo '</table>';
                    }
                } else {
                    $options = [
                        'class' => 'image',
                        'id'    => 'image_'.$id,
                        'title' => $img_style_title,
                        'style' => $borderStyle.$imgpos,
                    ];
                    if ($width == 0 || $height == 0) {
                        if ($varsize[0] > 150 || $varsize[1] > 150) {
                            $options['width'] = '70px';
                            $options['height'] = '70px';
                        }
                    } else {
                        $options['width'] = $width;
                        $options['height'] = $height;
                    }

                    echo html_print_image(
                        $img,
                        true,
                        $options,
                        false,
                        false,
                        false,
                        $isExternalLink
                    );
                }
            }

            if ($layoutData['label_position'] != 'up') {
                echo io_safe_output($text);
            }

            if (is_metaconsole()) {
                metaconsole_restore_db();
            }
        break;

        case PERCENTILE_BAR:
            if (($layoutData['image'] == 'value') && ($value_text !== false)) {
                $unit_text = db_get_sql(
                    'SELECT unit
                    FROM tagente_modulo
                    WHERE id_agente_modulo = '.$id_module
                );
                $unit_text = trim(io_safe_output($unit_text));

                $percentile = $value_text;
            } else {
                $unit_text = '%';
            }

            if (get_parameter('action') == 'edit' || (get_parameter('operation') == 'edit_visualmap')) {
                if ($width == 0) {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../'.'images/console/signes/percentil.png'.'" style="width:130px;height:30px;'.$imgpos.'">';
                    } else {
                        $img = '<img src="'.'images/console/signes/percentil.png'.'" style="width:130px;height:30px;'.$imgpos.'">';
                    }
                } else {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img "../../'.'images/console/signes/percentil.png'.'" style="width:'.$width.'px;height:30px;'.$imgpos.'">';
                    } else {
                        $img = '<img src="'.'images/console/signes/percentil.png'.'"  style="width:'.$width.'px;height:30px;'.$imgpos.'">';
                    }
                }
            } else {
                $img = d3_progress_bar($id, $percentile, $width, 50, $border_color, $unit_text, io_safe_output($label), $fill_color);
            }

            echo $img;

            if (get_parameter('tab') == 'editor') {
                echo "<span style='color:".$fill_color.";'>".io_safe_output($text).'</span>';
            }
        break;

        case PERCENTILE_BUBBLE:
            if (($layoutData['image'] == 'value') && ($value_text !== false)) {
                $unit_text = db_get_sql(
                    'SELECT unit
                    FROM tagente_modulo
                    WHERE id_agente_modulo = '.$id_module
                );
                $unit_text = trim(io_safe_output($unit_text));

                $percentile = $value_text;
            } else {
                $unit_text = '%';
            }

            if (get_parameter('action') == 'edit' || (get_parameter('operation') == 'edit_visualmap')) {
                if ($width == 0) {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../'.'images/console/signes/percentil_bubble.png'.'">';
                    } else {
                        $img = '<img src="'.'images/console/signes/percentil_bubble.png'.'">';
                    }
                } else {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../'.'images/console/signes/percentil_bubble.png'.'" style="width:'.$width.'px;height:'.$width.'px;'.$imgpos.'">';
                    } else {
                        $img = '<img src="'.'images/console/signes/percentil_bubble.png'.'"  style="width:'.$width.'px;height:'.$width.'px;'.$imgpos.'">';
                    }
                }
            } else {
                if ($width == 0) {
                    $img = d3_progress_bubble($id, $percentile, 200, 200, $border_color, $unit_text, io_safe_output($label), $fill_color);
                } else {
                    $img = d3_progress_bubble($id, $percentile, $width, $width, $border_color, $unit_text, io_safe_output($label), $fill_color);
                }
            }

            echo $img;

            if (get_parameter('tab') == 'editor') {
                echo "<span style='color:".$fill_color.";'>".io_safe_output($text).'</span>';
            }
        break;

        case CIRCULAR_PROGRESS_BAR:
            if (get_parameter('action') == 'edit' || (get_parameter('operation') == 'edit_visualmap')) {
                if ($width == 0) {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../'.'images/console/signes/circular-progress-bar.png'.'">';
                    } else {
                        $img = '<img src="'.'images/console/signes/circular-progress-bar.png'.'">';
                    }
                } else {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../'.'images/console/signes/circular-progress-bar.png'.'" style="width:'.$width.'px;height:'.$width.'px;'.$imgpos.'">';
                    } else {
                        $img = '<img src="'.'images/console/signes/circular-progress-bar.png'.'"  style="width:'.$width.'px;height:'.$width.'px;'.$imgpos.'">';
                    }
                }
            } else {
                if (($layoutData['image'] == 'value') && ($value_text !== false)) {
                    $unit_text = db_get_sql(
                        'SELECT unit
                        FROM tagente_modulo
                        WHERE id_agente_modulo = '.$id_module
                    );
                    $unit_text = trim(io_safe_output($unit_text));

                    $percentile = $value_text;
                } else {
                    $unit_text = '%';
                }

                if ($width == 0) {
                    $img = progress_circular_bar($id, $percentile, 200, 200, $border_color, $unit_text, io_safe_output($label), $fill_color);
                } else {
                    $img = progress_circular_bar($id, $percentile, $width, $width, $border_color, $unit_text, io_safe_output($label), $fill_color);
                }
            }

            echo $img;

            if (get_parameter('tab') == 'editor') {
                echo "<span style='color:".$fill_color.";'>".io_safe_output($text).'</span>';
            }
        break;

        case CIRCULAR_INTERIOR_PROGRESS_BAR:
            if (get_parameter('action') == 'edit' || (get_parameter('operation') == 'edit_visualmap')) {
                if ($width == 0) {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../'.'images/console/signes/circular-progress-bar-interior.png'.'">';
                    } else {
                        $img = '<img src="'.'images/console/signes/circular-progress-bar-interior.png'.'">';
                    }
                } else {
                    if ($layoutData['id_metaconsole'] != 0) {
                        $img = '<img src="../../'.'images/console/signes/circular-progress-bar-interior.png'.'" style="width:'.$width.'px;height:'.$width.'px;'.$imgpos.'">';
                    } else {
                        $img = '<img src="'.'images/console/signes/circular-progress-bar-interior.png'.'"  style="width:'.$width.'px;height:'.$width.'px;'.$imgpos.'">';
                    }
                }
            } else {
                if (($layoutData['image'] == 'value') && ($value_text !== false)) {
                    $unit_text = db_get_sql(
                        'SELECT unit
                        FROM tagente_modulo
                        WHERE id_agente_modulo = '.$id_module
                    );
                    $unit_text = trim(io_safe_output($unit_text));

                    $percentile = $value_text;
                } else {
                    $unit_text = '%';
                }

                if ($width == 0) {
                    $img = progress_circular_bar_interior($id, $percentile, 200, 200, $border_color, $unit_text, io_safe_output($label), $fill_color);
                } else {
                    $img = progress_circular_bar_interior($id, $percentile, $width, $width, $border_color, $unit_text, io_safe_output($label), $fill_color);
                }
            }

            echo $img;

            if (get_parameter('tab') == 'editor') {
                echo "<span style='color:".$fill_color.";'>".io_safe_output($text).'</span>';
            }
        break;

        case MODULE_GRAPH:
            if ($layoutData['label_position'] == 'up') {
                echo io_safe_output($text);
            }

            echo $img;

            if ($layoutData['label_position'] == 'down') {
                echo io_safe_output($text);
            } else if ($layoutData['label_position'] == 'left' || $layoutData['label_position'] == 'right') {
                echo io_safe_output($text);
            }
        break;

        case CLOCK:
            if ($layoutData['label_position'] == 'up') {
                echo io_safe_output($text);
            }

            echo $img;

            if ($layoutData['label_position'] == 'down') {
                echo io_safe_output($text);
            } else if ($layoutData['label_position'] == 'left' || $layoutData['label_position'] == 'right') {
                echo io_safe_output($text);
            }
        break;

        case BARS_GRAPH:
            if ($layoutData['label_position'] == 'up') {
                echo io_safe_output($text);
            }

            echo $img;

            if ($layoutData['label_position'] == 'down') {
                echo io_safe_output($text);
            } else if ($layoutData['label_position'] == 'left' || $layoutData['label_position'] == 'right') {
                echo io_safe_output($text);
            }
        break;

        case AUTO_SLA_GRAPH:
            if ($layoutData['label_position'] == 'up') {
                echo io_safe_output($text);
            }

            echo $img;

            if ($layoutData['label_position'] == 'down') {
                echo io_safe_output($text);
            } else if ($layoutData['label_position'] == 'left' || $layoutData['label_position'] == 'right') {
                echo io_safe_output($text);
            }
        break;

        case DONUT_GRAPH:
            echo $img;
        break;

        case SIMPLE_VALUE:
        case SIMPLE_VALUE_MAX:
        case SIMPLE_VALUE_MIN:
        case SIMPLE_VALUE_AVG:
            $io_safe_output_text = io_safe_output($text);

            // Metaconsole db connection
            if ($layoutData['id_metaconsole'] != 0) {
                $connection = db_get_row_filter(
                    'tmetaconsole_setup',
                    ['id' => $layoutData['id_metaconsole']]
                );
                if (metaconsole_load_external_db($connection) != NOERR) {
                    // ui_print_error_message ("Error connecting to ".$server_name);
                    break;
                }
            }

            $unit_text = db_get_sql(
                'SELECT unit
                    FROM tagente_modulo
                    WHERE id_agente_modulo = '.$layoutData['id_agente_modulo']
            );
            $unit_text = trim(io_safe_output($unit_text));

            // $value = db_get_value ('datos',
                // 'tagente_estado', 'id_agente_modulo', $layoutData['id_agente_modulo']);
            $value = visual_map_get_simple_value(
                $type,
                $layoutData['id_agente_modulo'],
                $period
            );

            global $config;

            $is_image = get_if_module_is_image($layoutData['id_agente_modulo']);
            if (get_parameter('action') == 'edit') {
                if (!$is_image) {
                    echo $io_safe_output_text;
                } else {
                    echo "<img style='width:".$layoutData['width']."px;' src='images/console/signes/data_image.png'>";
                }
            } else {
                if (!$is_image) {
                    $new_text = str_replace(['(_VALUE_)', '(_value_)'], $value, $io_safe_output_text);
                    $new_text = str_replace(['_VALUE_', '_value_'], $value, $new_text);

                    echo $new_text;
                } else {
                    $simple_value_img = str_replace('>', ' style="width:'.$layoutData['width'].'px">', $value);
                    echo $simple_value_img;
                }
            }

            // Restore db connection
            if ($layoutData['id_metaconsole'] != 0) {
                metaconsole_restore_db();
            }
        break;

        case LABEL:
            echo io_safe_output($text);
        break;

        case ICON:
            if ($layoutData['image'] != null) {
                // If match with protocol://direction
                if (preg_match('/^(http:\/\/)((.)+)$/i', $text)) {
                    echo '<a href="'.$label.'">'.'</a>'.'<br />';
                }

                if (!empty($proportion)) {
                    if (is_file($config['homedir'].'/'.$img)) {
                        $infoImage = getimagesize($config['homedir'].'/'.$img);
                    }

                    if ($width != 0) {
                        $width = (integer) ($proportion['proportion_width'] * $width);
                    } else {
                        $width = (integer) ($proportion['proportion_width'] * $infoImage[0]);
                    }

                    if ($height != 0) {
                        $height = (integer) ($proportion['proportion_height'] * $height);
                    } else {
                        $height = (integer) ($proportion['proportion_height'] * $infoImage[1]);
                    }
                }

                $varsize = getimagesize($img);
                if (($width != 0) && ($height != 0)) {
                    echo html_print_image(
                        $img,
                        true,
                        [
                            'class'  => 'image',
                            'id'     => 'image_'.$id,
                            'width'  => "$width",
                            'height' => "$height",
                        ],
                        false,
                        false,
                        false,
                        $isExternalLink
                    );
                } else {
                    if ($varsize[0] > 150 || $varsize[0] > 150) {
                        echo html_print_image(
                            $img,
                            true,
                            [
                                'class' => 'image',
                                'id'    => 'image_'.$id,
                                'width' => '70px',
                                '70px'  => "$height",
                            ],
                            false,
                            false,
                            false,
                            $isExternalLink
                        );
                    } else {
                        echo html_print_image(
                            $img,
                            true,
                            [
                                'class' => 'image',
                                'id'    => 'image_'.$id,
                            ],
                            false,
                            false,
                            false,
                            $isExternalLink
                        );
                    }
                }
            }
        break;

        case COLOR_CLOUD:
            echo visual_map_get_color_cloud_element($layoutData);
        break;

        default:
            if (!empty($element_enterprise)) {
                echo $element_enterprise['item'];
            }
        break;
    }

    if ($link) {
        echo '</a>';
    }

    echo '</div>';

    // Add the line between elements.
    if ($layoutData['parent_item'] != 0) {
        $parent = db_get_row_filter(
            'tlayout_data',
            ['id' => $layoutData['parent_item']]
        );

        echo '<script type="text/javascript">';
        echo 'lines.push({"id": "'.$id.'" , "node_begin":"'.$layoutData['parent_item'].'","node_end":"'.$id.'","color":"'.visual_map_get_color_line_status($parent).'","thickness":"'.(empty($config['vc_line_thickness']) ? 2 : $config['vc_line_thickness']).'"});';
        echo '</script>';
    }
}


function get_if_module_is_image($id_module)
{
    $sql = 'SELECT datos FROM tagente_estado WHERE id_agente_modulo = '.$id_module;

    $result = db_get_sql($sql);
    $image = strpos($result, 'data:image');

    if ($image === false) {
        return false;
    } else {
        return true;
    }
}


function get_bars_module_data($id_module, $typeGraph='horizontal')
{
    // This charts is only serialize graphs.
    // In other string show image no data to show.
    $mod_values = db_get_value_filter(
        'datos',
        'tagente_estado',
        ['id_agente_modulo' => $id_module]
    );

    $values = false;
    // Avoid showing the image type modules. WUX.
    if (strpos($mod_values, 'data:image/png;base64') !== 0) {
        if (preg_match("/\r\n/", $mod_values)) {
            $values = explode("\r\n", $mod_values);
        } else if (preg_match("/\n/", $mod_values)) {
            $values = explode("\n", $mod_values);
        }
    }

    if (!$values) {
        return false;
    }

    $values_to_return = [];
    foreach ($values as $val) {
        $data = explode(',', $val);

        if ($data[0] === $val) {
            continue;
        }

        $values_to_return['labels'][] = io_safe_output($data[0]);
        if ($typeGraph === 'horizontal') {
            $values_to_return['data'][] = [
                'y' => io_safe_output($data[0]),
                'x' => $data[1],
            ];
        } else {
            $values_to_return['data'][] = [
                'x' => io_safe_output($data[0]),
                'y' => $data[1],
            ];
        }
    }

    return $values_to_return;
}


/**
 * The function to get simple value type from the value of process type in the form
 *
 * @param int process simple value from form
 *
 * @return integer type among the constants:
 * SIMPLE_VALUE, SIMPLE_VALUE_MAX, SIMPLE_VALUE_MIN, SIMPLE_VALUE_AVG
 */
function visual_map_get_simple_value_type($process_simple_value)
{
    switch ($process_simple_value) {
        case PROCESS_VALUE_NONE:
        return SIMPLE_VALUE;

            break;
        case PROCESS_VALUE_MIN:
        return SIMPLE_VALUE_MIN;

            break;
        case PROCESS_VALUE_MAX:
        return SIMPLE_VALUE_MAX;

            break;
        case PROCESS_VALUE_AVG:
        return SIMPLE_VALUE_AVG;

            break;
    }
}


/**
 * The function to get the simple value of a module
 *
 * @param int type of the retrieving choosed among the constants:
 * SIMPLE_VALUE, SIMPLE_VALUE_MAX, SIMPLE_VALUE_MIN, SIMPLE_VALUE_AVG
 * @param int id agent module
 * @param int period The period in seconds for calculate the avg or min or max value.
 *
 * @return string value retrieved with units
 */
function visual_map_get_simple_value($type, $id_module, $period=SECONDS_1DAY)
{
    global $config;

    $unit_text = db_get_sql(
        'SELECT unit
        FROM tagente_modulo WHERE id_agente_modulo = '.$id_module
    );
    $unit_text = trim(io_safe_output($unit_text));

    switch ($type) {
        case SIMPLE_VALUE:
            $value = db_get_value(
                'datos',
                'tagente_estado',
                'id_agente_modulo',
                $id_module
            );
            if ($value === false) {
                $value = __('Unknown');

                $value = preg_replace('/\n/i', '<br>', $value);
                $value = preg_replace('/\s/i', '&nbsp;', $value);
            } else {
                if (strpos($value, 'data:image') !== false) {
                    $value = '<img class="b64img" src="'.$value.'">';
                } else {
                    if (is_numeric($value)) {
                        if ($config['simple_module_value']) {
                            $value = remove_right_zeros(number_format($value, $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator']));
                        }
                    }

                    if (!empty($unit_text)) {
                        $value .= ' '.$unit_text;
                    }

                    $value = preg_replace('/\n/i', '<br>', $value);
                    $value = preg_replace('/\s/i', '&nbsp;', $value);
                }
            }
        return $value;

            break;
        case SIMPLE_VALUE_MAX:
            $value = reporting_get_agentmodule_data_max($id_module, $period, 0);
            if ($value === false) {
                $value = __('Unknown');
            } else {
                if (is_numeric($value)) {
                    if ($config['simple_module_value']) {
                        $value = format_for_graph($value, $config['graph_precision']);
                    }
                }

                if (!empty($unit_text)) {
                    $value .= ' '.$unit_text;
                }
            }
        return $value;

            break;
        case SIMPLE_VALUE_MIN:
            $value = reporting_get_agentmodule_data_min($id_module, $period, 0);
            if ($value === false) {
                $value = __('Unknown');
            } else {
                if (is_numeric($value)) {
                    if ($config['simple_module_value']) {
                        $value = format_for_graph($value, $config['graph_precision']);
                    }
                }

                if (!empty($unit_text)) {
                    $value .= ' '.$unit_text;
                }
            }
        return $value;

            break;
        case SIMPLE_VALUE_AVG:
            $value = reporting_get_agentmodule_data_average($id_module, $period, 0);
            if ($value === false) {
                $value = __('Unknown');
            } else {
                if (is_numeric($value)) {
                    if ($config['simple_module_value']) {
                        $value = format_for_graph($value, $config['graph_precision']);
                    }
                }

                if (!empty($unit_text)) {
                    $value .= ' '.$unit_text;
                }
            }
        return $value;

            break;
    }
}


/**
 * The function to save the new elements of agents make as wizard.
 *
 * @param array   $id_agents The list of id of agents.
 * @param string  $image     The image to set the elements.
 * @param integer $id_layout The id of visual console to insert the elements.
 * @param integer $range     The distance between elements.
 * @param integer $width     Width of image.
 * @param integer $height    Height of image.
 *
 * @return string Return the message status to insert DB.
 */
function visual_map_process_wizard_add(
    $id_agents,
    $image,
    $id_layout,
    $range,
    $width=0,
    $height=0,
    $period='',
    $process_value='',
    $percentileitem_width='',
    $max_value=0,
    $type_percentile='',
    $value_show='',
    $type=''
) {
    if (empty($id_agents)) {
        print_error_message(__('No agents selected'));
        return false;
    }

    $id_agents = (array) $id_agents;

    $error = false;
    $pos_y = 10;
    $pos_x = 10;
    foreach ($id_agents as $id_agent) {
        if ($pos_x > 600) {
            $pos_x = 10;
            $pos_y = ($pos_y + $range);
        }

        $value_height = $height;
        $value_image = $image;
        $value_type = $type;
        switch ($type) {
            case PERCENTILE_BAR:
            case PERCENTILE_BUBBLE:
            case CIRCULAR_PROGRESS_BAR:
            case CIRCULAR_INTERIOR_PROGRESS_BAR:
                $value_height = $max_value;
                $value_image = $value_show;
                if ($type_percentile == 'percentile') {
                    $value_type = PERCENTILE_BAR;
                } else if ($type_percentile == 'interior_circular_progress_bar') {
                    $value_type = CIRCULAR_INTERIOR_PROGRESS_BAR;
                } else if ($type_percentile == 'circular_progress_bar') {
                    $value_type = CIRCULAR_PROGRESS_BAR;
                } else {
                    $value_type = PERCENTILE_BUBBLE;
                }
            break;

            case SIMPLE_VALUE:
                $value_type = $process_value;
            break;
        }

        $label = agents_get_alias($id_agent);

        $value_label = '(_VALUE_)';
        if ($type === SIMPLE_VALUE) {
            $label .= ' '.$value_label;
        }

        $values = [
            'type'      => $value_type,
            'id_layout' => $id_layout,
            'pos_x'     => $pos_x,
            'pos_y'     => $pos_y,
            'label'     => $label,
            'image'     => $value_image,
            'id_agent'  => $id_agent,
            'width'     => $width,
            'period'    => $period,
            'height'    => $value_height,
        ];

        db_process_sql_insert('tlayout_data', $values);

        $pos_x = ($pos_x + $range);
    }

    $return = ui_print_success_message(__('Agent successfully added to layout'), '', true);

    return $return;
}


/**
 * The function to save the new elements of modules make as wizard.
 *
 * @param array   $id_modules The list of id of modules.
 * @param string  $image      The image to set the elements.
 * @param integer $id_layout  The id of visual console to insert the elements.
 * @param integer $range      The distance between elements.
 * @param integer $width      Width of image.
 * @param integer $height     Height of image.
 *
 * @return string Return the message status to insert DB.
 */
function visual_map_process_wizard_add_modules(
    $id_modules,
    $image,
    $id_layout,
    $range,
    $width,
    $height,
    $period,
    $process_value,
    $percentileitem_width,
    $max_value,
    $type_percentile,
    $value_show,
    $label_type,
    $type,
    $enable_link=true,
    $id_server=0,
    $kind_relationship=VISUAL_MAP_WIZARD_PARENTS_NONE,
    $item_in_the_map=0,
    $fontf='lato',
    $fonts='12pt'
) {
    if (empty($width) === true) {
        $width = 0;
    }

    if (empty($height) === true) {
        $height = 0;
    }

    if (empty($id_modules)) {
        $return = ui_print_error_message(
            __('No modules selected'),
            '',
            true
        );
        return $return;
    }

    $id_modules = (array) $id_modules;

    $error = false;
    $pos_y = 10;
    $pos_x = 10;

    foreach ($id_modules as $id_module) {
        if ($pos_x > 600) {
            $pos_x = 10;
            $pos_y = ($pos_y + $range);
        }

        if ($id_server != 0) {
            $connection = db_get_row_filter(
                'tmetaconsole_setup',
                ['id' => $id_server]
            );
            if (metaconsole_load_external_db($connection) != NOERR) {
                $return = ui_print_error_message(
                    'Error connecting to '.$server_name,
                    '',
                    true
                );

                return $return;
            }
        }

        $id_agent = modules_get_agentmodule_agent($id_module);

        switch ($label_type) {
            case 'agent_module':
            default:
                $agent_label = agents_get_alias($id_agent);
                $module_label = modules_get_agentmodule_name($id_module);
                $label = '<p><span class="visual_font_size_'.$fonts.'" style="font-family:'.$fontf.';">'.$agent_label.' - '.$module_label.'</span></p>';
            break;

            case 'module':
                $module_label = modules_get_agentmodule_name($id_module);
                $label = '<p><span class="visual_font_size_'.$fonts.'" style="font-family:'.$fontf.';">'.$module_label.'</span></p>';
            break;

            case 'agent':
                $agent_label = agents_get_alias($id_agent);
                $label = '<p><span class="visual_font_size_'.$fonts.'" style="font-family:'.$fontf.';">'.$agent_label.'</span></p>';
            break;

            case 'none':
                $label = '';
            break;
        }

        $label = io_safe_input($label);

        // Restore db connection
        if ($id_server != 0) {
            metaconsole_restore_db();
        }

        $value_height = $height;
        $value_image = $image;
        $value_type = $type;
        $value_width = $width;
        switch ($type) {
            case PERCENTILE_BAR:
            case PERCENTILE_BUBBLE:
            case CIRCULAR_PROGRESS_BAR:
            case CIRCULAR_INTERIOR_PROGRESS_BAR:
                $value_height = $max_value;
                $value_width = $percentileitem_width;
                $value_image = $value_show;
                if ($type_percentile == 'percentile') {
                    $value_type = PERCENTILE_BAR;
                } else if ($type_percentile == 'interior_circular_progress_bar') {
                    $value_type = CIRCULAR_INTERIOR_PROGRESS_BAR;
                } else if ($type_percentile == 'circular_progress_bar') {
                    $value_type = CIRCULAR_PROGRESS_BAR;
                } else {
                    $value_type = PERCENTILE_BUBBLE;
                }
            break;

            case SIMPLE_VALUE:
                $label = !empty($label) ? $label.' (_VALUE_)' : '(_VALUE_)';
                $value_image = '';
                switch ($process_value) {
                    case PROCESS_VALUE_NONE:
                        $value_type = SIMPLE_VALUE;
                    break;

                    case PROCESS_VALUE_MIN:
                        $value_type = SIMPLE_VALUE_MIN;
                    break;

                    case PROCESS_VALUE_MAX:
                        $value_type = SIMPLE_VALUE_MAX;
                    break;

                    case PROCESS_VALUE_AVG:
                        $value_type = SIMPLE_VALUE_AVG;
                    break;
                }
            break;
        }

        $parent_item = 0;
        switch ($kind_relationship) {
            case VISUAL_MAP_WIZARD_PARENTS_ITEM_MAP:
                $parent_item = $item_in_the_map;
            break;
        }

        $values = [
            'type'             => $value_type,
            'id_layout'        => $id_layout,
            'pos_x'            => $pos_x,
            'pos_y'            => $pos_y,
            'label'            => $label,
            'image'            => $value_image,
            'id_agent'         => $id_agent,
            'id_agente_modulo' => $id_module,
            'width'            => $value_width,
            'period'           => $period,
            'height'           => $value_height,
            'enable_link'      => $enable_link,
            'id_metaconsole'   => $id_server,
            'parent_item'      => $parent_item,
        ];

        db_process_sql_insert('tlayout_data', $values);

        $pos_x = ($pos_x + $range);
    }

    $return = ui_print_success_message(__('Modules successfully added to layout'), '', true);

    return $return;
}


function get_donut_module_data($id_module)
{
    $mod_values = db_get_value_filter('datos', 'tagente_estado', ['id_agente_modulo' => $id_module]);

    $no_data_to_show = false;

    if (preg_match("/\r\n/", $mod_values)) {
        $values = explode("\r\n", $mod_values);
    } else if (preg_match("/\n/", $mod_values)) {
        $values = explode("\n", $mod_values);
    } else {
        $values = [__('No data to show').',1'];
        $no_data_to_show = true;
    }

    $values_to_return = [];
    foreach ($values as $val) {
        if (empty($val) === true) {
            continue;
        }

        $data = explode(',', $val);
        if ($data[0] === $val) {
            continue;
        }

        if ($no_data_to_show) {
            $values_to_return['labels'][] = $data[0];
        } else {
            $values_to_return['labels'][] = $data[0].': '.$data[1];
        }

        $values_to_return['data'][] = (int) $data[1];
    }

    return $values_to_return;
}


/**
 * The function to save the new elements of agents make as wizard.
 *
 * @param array   $id_agents The list of id of agents.
 * @param string  $image     The image to set the elements.
 * @param integer $id_layout The id of visual console to insert the elements.
 * @param integer $range     The distance between elements.
 * @param integer $width     Width of image.
 * @param integer $height    Height of image.
 *
 * @return string Return the message status to insert DB.
 */
function visual_map_process_wizard_add_agents(
    $id_agents,
    $image,
    $id_layout,
    $range,
    $width,
    $height,
    $period,
    $process_value,
    $percentileitem_width,
    $max_value,
    $type_percentile,
    $value_show,
    $label_type,
    $type,
    $enable_link=1,
    $id_server=0,
    $kind_relationship=VISUAL_MAP_WIZARD_PARENTS_NONE,
    $item_in_the_map=0,
    $fontf='lato',
    $fonts='12pt'
) {
    global $config;

    if (empty($width) === true) {
        $width = 0;
    }

    if (empty($height) === true) {
        $height = 0;
    }

    if (empty($id_agents)) {
        $return = ui_print_error_message(
            __('No agents selected'),
            '',
            true
        );

        return $return;
    }

    $id_agents = (array) $id_agents;

    $error = false;
    $pos_y = 10;
    $pos_x = 10;

    $relationship = true;
    $relationships_agents = [];
    // Check if the set a none relationship
    if (($kind_relationship == VISUAL_MAP_WIZARD_PARENTS_NONE)
        || ($kind_relationship == VISUAL_MAP_WIZARD_PARENTS_AGENT_RELANTIONSHIP
        && $item_in_the_map = 0)
    ) {
        $relationship = false;
    }

    foreach ($id_agents as $id_agent) {
        if (is_array($id_agent)) {
            $id_a = $id_agent['id_agent'];
            $id_server = $id_agent['id_server'];
            $id_agent = $id_a;
        }

        if ($pos_x > 600) {
            $pos_x = 10;
            $pos_y = ($pos_y + $range);
        }

        $value_height = $height;
        $value_image = $image;
        $value_type = $type;
        $value_width = $width;

        switch ($type) {
            case PERCENTILE_BAR:
            case PERCENTILE_BUBBLE:
            case CIRCULAR_PROGRESS_BAR:
            case CIRCULAR_INTERIOR_PROGRESS_BAR:
                $value_height = $max_value;
                $value_width = $percentileitem_width;
                $value_image = $value_show;
                if ($type_percentile == 'percentile') {
                    $value_type = PERCENTILE_BAR;
                } else if ($type_percentile == 'interior_circular_progress_bar') {
                    $value_type = CIRCULAR_INTERIOR_PROGRESS_BAR;
                } else if ($type_percentile == 'circular_progress_bar') {
                    $value_type = CIRCULAR_PROGRESS_BAR;
                } else {
                    $value_type = PERCENTILE_BUBBLE;
                }
            break;

            case SIMPLE_VALUE:
                $value_image = '';
                switch ($process_value) {
                    case PROCESS_VALUE_NONE:
                        $value_type = SIMPLE_VALUE;
                    break;

                    case PROCESS_VALUE_MIN:
                        $value_type = SIMPLE_VALUE_MIN;
                    break;

                    case PROCESS_VALUE_MAX:
                        $value_type = SIMPLE_VALUE_MAX;
                    break;

                    case PROCESS_VALUE_AVG:
                        $value_type = SIMPLE_VALUE_AVG;
                    break;
                }
            break;
        }

        if ($id_server != 0) {
            $connection = db_get_row_filter(
                'tmetaconsole_setup',
                ['id' => $id_server]
            );
            if (metaconsole_load_external_db($connection) != NOERR) {
                $return = ui_print_error_message(
                    'Error connecting to '.$server_name,
                    '',
                    true
                );

                return $return;
            }
        }

        switch ($label_type) {
            case 'agent':
                $label = agents_get_alias($id_agent);
            break;

            case 'none':
                $label = '';
            break;
        }

        $label = io_safe_input($label);

        if ($type === SIMPLE_VALUE) {
            $label = !empty($label) ? $label.' (_VALUE_)' : '(_VALUE_)';
        }

        // Restore db connection
        if ($id_server != 0) {
            metaconsole_restore_db();
        }

        $parent_item = 0;
        if ($relationship) {
            switch ($kind_relationship) {
                case VISUAL_MAP_WIZARD_PARENTS_ITEM_MAP:
                    $parent_item = $item_in_the_map;
                break;
            }
        }

        $values = [
            'type'             => $value_type,
            'id_layout'        => $id_layout,
            'pos_x'            => $pos_x,
            'pos_y'            => $pos_y,
            'label'            => $label,
            'image'            => $value_image,
            'id_agent'         => $id_agent,
            'id_agente_modulo' => 0,
            'width'            => $value_width,
            'period'           => $period,
            'height'           => $value_height,
            'enable_link'      => $enable_link,
            'id_metaconsole'   => $id_server,
            'parent_item'      => $parent_item,
        ];

        $id_item = db_process_sql_insert('tlayout_data', $values);

        if ($relationship) {
            switch ($kind_relationship) {
                case VISUAL_MAP_WIZARD_PARENTS_AGENT_RELANTIONSHIP:

                    if (!isset($relationships_agents[$id_agent])) {
                        $relationships_agents[$id_agent]['id_layout_data_parent'] = $id_item;
                        $relationships_agents[$id_agent]['id_layout_data_children'] = [];
                    } else {
                        $relationships_agents[$id_agent]['id_layout_data_parent'] = $id_item;
                    }

                    $agent_id_parent = db_get_value(
                        'id_parent',
                        'tagente',
                        'id_agente',
                        $id_agent
                    );

                    // Check in the group of new items is the father
                    if (array_search($agent_id_parent, $id_agents) !== false) {
                        if (isset($relationships_agents[$agent_id_parent])) {
                            $relationships_agents[$agent_id_parent]['id_layout_data_children'][] = $id_item;
                        } else {
                            $relationships_agents[$agent_id_parent] = [];
                            $relationships_agents[$agent_id_parent]['id_layout_data_parent'] = null;
                            $relationships_agents[$agent_id_parent]['id_layout_data_children'] = [];
                            $relationships_agents[$agent_id_parent]['id_layout_data_children'][] = $id_item;
                        }
                    }
                break;
            }
        }

        $pos_x = ($pos_x + $range);
    }

    foreach ($relationships_agents as $relationship_item) {
        foreach ($relationship_item['id_layout_data_children'] as $children) {
            db_process_sql_update(
                'tlayout_data',
                ['parent_item' => $relationship_item['id_layout_data_parent']],
                ['id' => $children]
            );
        }
    }

    $return = ui_print_success_message(
        __('Agents successfully added to layout'),
        '',
        true
    );

    return $return;
}


/**
 * Get the color of line between elements in the visual map.
 *
 * @param array $layoutData The row of element in DB.
 *
 * @return string The color as hexadecimal color in html.
 */
function visual_map_get_color_line_status($layoutData)
{
    if (($layoutData['type'] == 5) || ($layoutData['type'] == 4)) {
        // ICON ELEMENT OR LABEL ELEMENT
        $color = '#cccccc';
    } else {
        switch (visual_map_get_status_element($layoutData)) {
            case 3:
                $color = '#cccccc';
                // Gray
            break;

            case 2:
                $color = '#20f6f6';
                // Yellow
            break;

            case 0:
                $color = '#00ff00';
                // Green
            break;

            case 4:
            case 1:
                $color = '#ff0000';
                // Red
            break;
        }
    }

    return $color;
}


/**
 * Get image of element in the visual console with status.
 *
 * @param array   $layoutData The row of element in DB.
 * @param boolean $status     Status.
 *
 * @return string The image with the relative path to pandora console directory.
 */
function visual_map_get_image_status_element($layoutData, $status=false)
{
    $img = 'images/console/icons/'.$layoutData['image'];

    if (empty($layoutData['image'])) {
        return false;
    }

    if ($layoutData['type'] == 5) {
        // ICON ELEMENT.
        $url = parse_url($layoutData['image']);
        if (isset($url['scheme']) === false) {
            $img .= '.png';
        } else {
            $img = $layoutData['image'];
        }
    } else {
        if ($status === false) {
            $status = visual_map_get_status_element($layoutData);
        }

        switch ($status) {
            case 1:
            case 4:
                // Critical or critical alert (BAD).
                $img .= '_bad.png';
            break;

            case 0:
                // Normal (OK).
                $img .= '_ok.png';
            break;

            case 2:
            case 10:
                // Warning or warning alert.
                $img .= '_warning.png';
            break;

            case 3:
                // Unknown.
            default:
                $img .= '.png';
                // Default is Grey (Other).
            break;
        }
    }

    return $img;
}


/**
 * Get the status of element in visual console. Check the agent state or
 * module or layout linked.
 *
 * @param array $layoutData The row of element in DB.
 *
 * @return integer
 */
function visual_map_get_status_element($layoutData)
{
    global $config;

    enterprise_include_once('include/functions_visual_map.php');
    if (enterprise_installed()) {
        $status = enterprise_visual_map_get_status_element($layoutData);

        // The function return value.
        if ($status !== false) {
            // Return this value as call of open function.
            return $status;
        }
    }

    // Linked to other layout ?? - Only if not module defined
    if (!empty($layoutData['id_layout_linked'])) {
        if (!empty($layoutData['linked_layout_node_id'])) {
            // Metaconsole db connection
            $connection = db_get_row_filter(
                'tmetaconsole_setup',
                ['id' => $layoutData['linked_layout_node_id']]
            );
            if (metaconsole_load_external_db($connection) != NOERR) {
                return VISUAL_MAP_STATUS_UNKNOWN;
            }
        }

        $status = visual_map_get_layout_status($layoutData['id_layout_linked'], $layoutData);

        if (!empty($layoutData['linked_layout_node_id'])) {
            // Restore db connection
            metaconsole_restore_db();
        }

        return $status;
    } else {
        switch ($layoutData['type']) {
            case STATIC_GRAPH:
                // Open metaconsole connection
                if ($layoutData['id_metaconsole'] != 0) {
                    // Metaconsole db connection
                    $connection = db_get_row_filter(
                        'tmetaconsole_setup',
                        ['id' => $layoutData['id_metaconsole']]
                    );
                    if (metaconsole_load_external_db($connection) != NOERR) {
                        // ui_print_error_message ("Error connecting to ".$server_name);
                        break;
                    }
                }

                // Enter the correct img if the graph has a module selected or not
                // Module
                if ($layoutData['id_agente_modulo'] != 0) {
                    $module_status = db_get_sql(
                        'SELECT estado
                        FROM tagente_estado
                        WHERE id_agente_modulo = '.$layoutData['id_agente_modulo']
                    );

                    switch ($module_status) {
                        case AGENT_STATUS_NORMAL:
                        case AGENT_MODULE_STATUS_NORMAL_ALERT:
                            $layoutData['status_calculated'] = VISUAL_MAP_STATUS_NORMAL;
                        break;

                        case AGENT_MODULE_STATUS_WARNING:
                        case AGENT_MODULE_STATUS_WARNING_ALERT:
                            $layoutData['status_calculated'] = VISUAL_MAP_STATUS_WARNING;
                        break;

                        case AGENT_STATUS_CRITICAL:
                        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                            $layoutData['status_calculated'] = VISUAL_MAP_STATUS_CRITICAL_BAD;
                        break;

                        case AGENT_MODULE_STATUS_NO_DATA:
                        default:
                            $layoutData['status_calculated'] = VISUAL_MAP_STATUS_UNKNOWN;
                        break;
                    }
                }
                // No module
                else if ($layoutData['id_agent'] != 0) {
                    $agent = db_get_row('tagente', 'id_agente', $layoutData['id_agent']);
                    if ($agent['total_count'] == 0 || $agent['total_count'] == $agent['notinit_count']) {
                        $layoutData['status_calculated'] = VISUAL_MAP_STATUS_UNKNOWN;
                    } else if ($agent['critical_count'] > 0) {
                        $layoutData['status_calculated'] = VISUAL_MAP_STATUS_CRITICAL_BAD;
                    } else if ($agent['warning_count'] > 0) {
                        $layoutData['status_calculated'] = VISUAL_MAP_STATUS_WARNING;
                    } else if ($agent['unknown_count'] > 0) {
                        $layoutData['status_calculated'] = VISUAL_MAP_STATUS_UNKNOWN;
                    } else {
                        $layoutData['status_calculated'] = VISUAL_MAP_STATUS_NORMAL;
                    }
                }
                // In other case
                else {
                    $layoutData['status_calculated'] = VISUAL_MAP_STATUS_UNKNOWN;
                }

                $status = $layoutData['status_calculated'];

                // Close metaconsole connection
                if ($layoutData['id_metaconsole'] != 0) {
                    // Restore db connection
                    metaconsole_restore_db();
                }
            break;

            case PERCENTILE_BAR:
            case PERCENTILE_BUBBLE:
            case CIRCULAR_PROGRESS_BAR:
            case CIRCULAR_INTERIOR_PROGRESS_BAR:
                $module_value = db_get_sql(
                    'SELECT datos
                    FROM tagente_estado
                    WHERE id_agente_modulo = '.$layoutData['id_agente_modulo']
                );

                if (empty($module_value) || $module_value == '') {
                    return VISUAL_MAP_STATUS_UNKNOWN;
                }

                if ($layoutData['id_metaconsole'] != 0) {
                    // Metaconsole db connection
                    $connection = db_get_row_filter(
                        'tmetaconsole_setup',
                        ['id' => $layoutData['id_metaconsole']]
                    );
                    if (metaconsole_load_external_db($connection) != NOERR) {
                        // ui_print_error_message ("Error connecting to ".$server_name);
                        break;
                    }
                }

                // Status for a simple module
                if ($layoutData['id_agente_modulo'] != 0) {
                    $status = modules_get_agentmodule_status($layoutData['id_agente_modulo']);

                    // We need to get the diference between warning and critical alerts!!!
                    $real_status = db_get_row('tagente_estado', 'id_agente_modulo', $layoutData['id_agente_modulo']);

                    // Status for a whole agent, if agente_modulo was == 0
                } else if ($layoutData['id_agent'] != 0) {
                    // --------------------------------------------------
                    // ADDED NO CHECK ACL FOR AVOID CHECK TAGS THAT
                    // MAKE VERY SLOW THE VISUALMAPS WITH ACL TAGS
                    // --------------------------------------------------
                    $status = agents_get_status($layoutData['id_agent'], true);

                    if ($status == -1) {
                        // agents_get_status return -1 for unknown!
                        $status = VISUAL_MAP_STATUS_UNKNOWN;
                    }
                } else {
                    $status = VISUAL_MAP_STATUS_UNKNOWN;
                    $id_agent = 0;
                }

                if ($layoutData['id_metaconsole'] != 0) {
                    // Restore db connection
                    metaconsole_restore_db();
                }
            break;

            case GROUP_ITEM:
                $group_status = groups_get_status($layoutData['id_group'], true);

                switch ($group_status) {
                    case AGENT_STATUS_ALERT_FIRED:
                    return VISUAL_MAP_STATUS_CRITICAL_ALERT;

                        break;
                    case AGENT_STATUS_CRITICAL:
                    return VISUAL_MAP_STATUS_CRITICAL_BAD;

                        break;
                    case AGENT_STATUS_WARNING:
                    return VISUAL_MAP_STATUS_WARNING;

                        break;
                    case AGENT_STATUS_UNKNOWN:
                    return VISUAL_MAP_STATUS_UNKNOWN;

                        break;
                    case AGENT_STATUS_NORMAL:
                    default:
                    return VISUAL_MAP_STATUS_NORMAL;

                        break;
                }
            break;

            default:
                // If it's a graph, a progress bar or a data tag, ALWAYS report status OK
                // (=0) to avoid confussions here.
                $status = VISUAL_MAP_STATUS_NORMAL;
            break;
        }
    }

    switch ($status) {
        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
            $status = VISUAL_MAP_STATUS_CRITICAL_ALERT;
        break;

        case AGENT_MODULE_STATUS_WARNING_ALERT:
            $status = VISUAL_MAP_STATUS_WARNING_ALERT;
        break;
    }

    return $status;
}


function visual_map_print_user_lines($layout_data, $proportion=null)
{
    if (empty($proportion)) {
        $line = [];
        $line['id'] = $layout_data['id'];
        $line['start_x'] = $layout_data['pos_x'];
        $line['start_y'] = $layout_data['pos_y'];
        $line['end_x'] = $layout_data['width'];
        $line['end_y'] = $layout_data['height'];
        $line['line_width'] = $layout_data['border_width'];
        $line['line_color'] = $layout_data['border_color'];
    } else {
        $proportion_width = $proportion['proportion_width'];
        $proportion_height = $proportion['proportion_height'];

        $proportion_line = $proportion_height;
        if ($proportion_width > $proportion_height) {
            $proportion_line = $proportion_width;
        }

        $line = [];
        $line['id'] = $layout_data['id'];
        $line['start_x'] = ($layout_data['pos_x'] * $proportion_width);
        $line['start_y'] = ($layout_data['pos_y'] * $proportion_height);
        $line['end_x'] = ($layout_data['width'] * $proportion_width);
        $line['end_y'] = ($layout_data['height'] * $proportion_height);
        $line['line_width'] = ($layout_data['border_width'] * $proportion_line);
        $line['line_color'] = $layout_data['border_color'];
    }

    echo '<script type="text/javascript">';
    echo 'user_lines.push('.json_encode($line).');';
    echo '</script>';
}


/**
 * Prints visual map
 *
 * @param integer $id_layout  Layout id
 * @param boolean $show_links
 * @param boolean $draw_lines
 */
function visual_map_print_visual_map(
    $id_layout,
    $show_links=true,
    $draw_lines=true,
    $width=null,
    $height=null,
    $home_url='',
    $isExternalLink=false,
    $graph_javascript=true,
    $keep_aspect_ratio=false
) {
    enterprise_include_once('include/functions_visual_map.php');

    global $config;

    enterprise_include_once('meta/include/functions_ui_meta.php');

    include_once $config['homedir'].'/include/functions_custom_graphs.php';

    $layout = db_get_row('tlayout', 'id', $id_layout);

    if (empty($layout)) {
        ui_print_error_message(__('Cannot load the visualmap'));
        return;
    }

    ?>
    <script language="javascript" type="text/javascript">
        /* <![CDATA[ */
        var id_layout = <?php echo $id_layout; ?>;
        var lines = Array();
        
        var user_lines = Array();
        
        //Fixed to wait the load of images.
        $(window).on('load', function () {
            draw_lines(lines, 'background_' + id_layout);
            draw_user_lines_read('background_' + id_layout);
            //center_labels();
        });
        /* ]]> */
    </script>
    <?php
    $resizedMap = false;

    $dif_height = 0;
    $dif_width = 0;
    $proportion_height = 0;
    $proportion_width = 0;

    if (!is_null($height) && !is_null($width)) {
        $resizedMap = true;

        if ($keep_aspect_ratio) {
            $ratio = min(($width / $layout['width']), ($height / $layout['height']));
            $mapWidth = ($ratio * $layout['width']);
            $mapHeight = ($ratio * $layout['height']);
        } else {
            $mapWidth = $width;
            $mapHeight = $height;
        }

        $dif_height = ($layout['height'] - $mapHeight);
        $dif_width = ($layout['width'] - $mapWidth);

        $proportion_height = ($mapHeight / $layout['height']);
        $proportion_width = ($mapWidth / $layout['width']);

        if ($layout['background'] != 'None.png') {
                $backgroundImage = '/include/Image/image_functions.php?getFile=1&thumb=1&thumb_size='.$mapWidth.'x'.$mapHeight.'&file='.$config['homedir'].'/images/console/background/'.($layout['background']);
        }
    } else {
        $mapWidth = $layout['width'];
        $mapHeight = $layout['height'];
        $backgroundImage = '';
        if ($layout['background'] != 'None.png') {
            $backgroundImage = 'images/console/background/'.$layout['background'];
        }
    }

    if (is_metaconsole()) {
        echo "<div class='vm_meta'>";
    }

    echo '<div id="background_'.$id_layout.'"
        style="margin:0px auto;text-align:
        z-index: 0;
        position:relative;
        width:'.$mapWidth.'px;
        height:'.$mapHeight.'px;
        background-color:'.$layout['background_color'].';
        z-index:0;">';

    if ($layout['background'] != 'None.png') {
        echo "<img src='".ui_get_full_url($backgroundImage, false, false, false)."' width='100%' height='100%' />";
    }

    $layout_datas = db_get_all_rows_field_filter(
        'tlayout_data',
        'id_layout',
        $id_layout
    );
    if (empty($layout_datas)) {
        $layout_datas = [];
    }

    $lines = [];

    foreach ($layout_datas as $layout_data) {
        $layout_group = $layout_data['element_group'];
        if (!check_acl($config['id_user'], $layout_group, 'VR')) {
            continue;
        }

        // Check the items are from disabled or pending delete modules
        if ($layout_data['id_agente_modulo'] != 0
            && (($layout_data['type'] != LABEL)
            || ($layout_data['type'] != ICON)
            || ($layout_data['type'] != SERVICE))
        ) {
            $delete_pending_module = db_get_value(
                'delete_pending',
                'tagente_modulo',
                'id_agente_modulo',
                $layout_data['id_agente_modulo']
            );
            $disabled_module = db_get_value(
                'disabled',
                'tagente_modulo',
                'id_agente_modulo',
                $layout_data['id_agente_modulo']
            );

            if ($delete_pending_module == 1 || $disabled_module == 1) {
                continue;
            }
        }

        if (($dif_height === 0) && ($dif_width === 0)) {
            $proportion = null;
        } else {
            $proportion = [
                'dif_height'        => $dif_height,
                'dif_width'         => $dif_width,
                'proportion_height' => $proportion_height,
                'proportion_width'  => $proportion_width,
            ];
        }

        $layout_data['label'] = visual_map_macro($layout_data['label'], $layout_data['id_agente_modulo']);

        switch ($layout_data['type']) {
            case NETWORK_LINK:
            case LINE_ITEM:
                visual_map_print_user_lines($layout_data, $proportion);
            break;

            default:
                visual_map_print_item(
                    'read',
                    $layout_data,
                    $proportion,
                    $show_links,
                    $isExternalLink,
                    $graph_javascript
                );
            break;
        }
    }

    // End main div
    echo '</div></div>';

    if (is_metaconsole()) {
        echo '</div>';
    }
}


// End function
// Start function


/**
 * Get a list with the layouts for a user.
 *
 * @param int User id.
 * @param bool Wheter to return all the fields or only the name (to use in
 * html_print_select() directly)
 * @param array Additional filters to filter the layouts.
 * @param bool Whether to return All group or not.
 *
 * @return array A list of layouts the user can see.
 */
function visual_map_get_user_layouts(
    $id_user=0,
    $only_names=false,
    $filter=false,
    $returnAllGroup=true,
    $favourite=false,
    $check_user_groups=true
) {
    $where = '';
    if (! is_array($filter)) {
        $filter = [];
    } else {
        if (!empty($filter['name'])) {
            $where .= sprintf(
                "name LIKE '%%%s%%'",
                db_escape_string_sql(io_safe_output($filter['name']))
            );

            unset($filter['name']);
        }
    }

    if ($favourite) {
        if (empty($where)) {
            $where = '';
        }

        if ($where != '') {
            $where .= ' AND ';
        }

        $where .= 'is_favourite = 1';
    }

    if ($returnAllGroup) {
        $groups = users_get_groups($id_user, 'VR', true, true);
    } else {
        if (!empty($filter['group'])) {
            $permissions_group = users_get_groups($id_user, 'VR', false, true);
            if (empty($permissions_group)) {
                $permissions_group = users_get_groups($id_user, 'VM', false, true);
            }

            $groups = array_intersect_key($filter['group'], $permissions_group);
        } else {
            $groups = users_get_groups($id_user, 'VR', true, true);
            if (empty($groups)) {
                $groups = users_get_groups($id_user, 'VM', true, true);
            }
        }

        unset($filter['group']);
    }

    $return_all_group = false;

    if (users_can_manage_group_all()) {
        $return_all_group = true;
    }

    if (isset($filter['can_manage_group_all'])) {
        if ($filter['can_manage_group_all'] === false) {
            unset($groups[0]);
        }

        unset($filter['can_manage_group_all']);
    }

    if ($check_user_groups === true && !empty($groups)) {
        if (empty($where)) {
            $where = '';
        }

        if ($where != '') {
            $where .= ' AND ';
        }

        $where .= sprintf('id_group IN (%s)', implode(',', array_keys($groups)));
    }

    $where .= db_format_array_where_clause_sql($filter);

    if ($where == '') {
        $where = [];
    }

    $layouts = db_get_all_rows_filter('tlayout', $where);
    if ($layouts == false) {
        return [];
    }

    $retval = [];
    foreach ($layouts as $layout) {
        if ($only_names) {
            $retval[$layout['id']] = $layout['name'];
        } else {
            $retval[$layout['id']] = $layout;
        }

        if ($only_names === false) {
            // Aad_perms.
            if (isset($groups[$layout['id_group']]['vconsole_view'])) {
                $retval[$layout['id']]['vr'] = $groups[$layout['id_group']]['vconsole_view'];
            }

            if (isset($groups[$layout['id_group']]['vconsole_edit'])) {
                $retval[$layout['id']]['vw'] = $groups[$layout['id_group']]['vconsole_edit'];
            }

            if (isset($groups[$layout['id_group']]['vconsole_management'])) {
                $retval[$layout['id']]['vm'] = $groups[$layout['id_group']]['vconsole_management'];
            }
        }
    }

    return $retval;
}


function visual_map_translate_agent_status($agent_status)
{
    switch ($agent_status) {
        case AGENT_STATUS_NORMAL:
        case AGENT_MODULE_STATUS_NORMAL_ALERT:
        default:
        return VISUAL_MAP_STATUS_NORMAL;

        case AGENT_STATUS_CRITICAL:
        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
        return VISUAL_MAP_STATUS_CRITICAL_BAD;

        case AGENT_STATUS_WARNING:
        case AGENT_MODULE_STATUS_WARNING_ALERT:
        return VISUAL_MAP_STATUS_WARNING;

        case AGENT_STATUS_NOT_INIT:
        case AGENT_STATUS_UNKNOWN:
        case -1:
        return VISUAL_MAP_STATUS_UNKNOWN;

        case AGENT_STATUS_ALERT_FIRED:
        return VISUAL_MAP_STATUS_CRITICAL_ALERT;
    }
}


function visual_map_translate_module_status($module_status)
{
    switch ($module_status) {
        case AGENT_MODULE_STATUS_NORMAL:
        case AGENT_MODULE_STATUS_NORMAL_ALERT:
        default:
        return VISUAL_MAP_STATUS_NORMAL;

        case AGENT_MODULE_STATUS_CRITICAL_BAD:
        return VISUAL_MAP_STATUS_CRITICAL_BAD;

        case AGENT_MODULE_STATUS_WARNING:
        return VISUAL_MAP_STATUS_WARNING;

        case AGENT_MODULE_STATUS_UNKNOWN:
        case AGENT_MODULE_STATUS_NOT_INIT:
        case AGENT_MODULE_STATUS_NO_DATA:
        case -1:
        return VISUAL_MAP_STATUS_UNKNOWN;

        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
        return VISUAL_MAP_STATUS_CRITICAL_ALERT;

        case AGENT_MODULE_STATUS_WARNING_ALERT:
        return VISUAL_MAP_STATUS_WARNING_ALERT;
    }
}


/**
 * Get the status of a layout.
 *
 * It gets all the data of the contained elements (including nested
 * layouts), and makes an AND operation to be sure that all the items
 * are OK. If any of them is down, then result is down (0)
 *
 * @param integer $layout_id   Id of the layout.
 * @param array   $status_data Information about the status calculation of the
 * item.
 * @param integer $depth       Depth (for recursion control).
 *
 * @return integer The status of the given layout.
 */
function visual_map_get_layout_status($layout_id, $status_data=[], $depth=0)
{
    global $config;

    // TODO: Implement this limit into the setup.
    if ($depth > 10) {
        return VISUAL_MAP_STATUS_UNKNOWN;
    }

    $layout_items = db_get_all_rows_sql(
        sprintf(
            'SELECT tld.*
            FROM tlayout_data tld
            LEFT JOIN tagente ta
                ON ta.id_agente=tld.id_agent
            LEFT JOIN tagente_modulo tam
                ON tam.id_agente_modulo = tld.id_agente_modulo
            WHERE tld.id_layout = %d
                AND (ta.disabled = 0 OR ta.disabled is null)
                AND (tam.disabled = 0 OR tam.disabled is null)',
            $layout_id
        )
    );

    if ($layout_items === false) {
        return VISUAL_MAP_STATUS_UNKNOWN;
    }

    // Check for valid items to retrieve the status for.
    $valid_layout_items = [];
    foreach ($layout_items as $layout_item_data) {
        if (($layout_item_data['type'] == GROUP_ITEM
            && !empty($layout_item_data['id_group'])
            && check_acl(
                $config['id_user'],
                $layout_item_data['id_group'],
                'VR'
            )
            && check_acl(
                $config['id_user'],
                $layout_item_data['element_group'],
                'VR'
            ))
            || ((!empty($layout_item_data['id_layout_linked'])
            || !empty($layout_item_data['id_agente_modulo'])
            || !empty($layout_item_data['id_agent']))
            && check_acl(
                $config['id_user'],
                $layout_item_data['element_group'],
                'VR'
            ))
        ) {
            $valid_layout_items[] = $layout_item_data;
        }
    }

    if (empty($valid_layout_items)) {
        return VISUAL_MAP_STATUS_UNKNOWN;
    }

    // Sort by node id to reduce the number of connections.
    if (is_metaconsole()) {
        sort_by_column($valid_layout_items, 'id_metaconsole');
    }

    $num_elements_by_status = [
        VISUAL_MAP_STATUS_CRITICAL_BAD   => 0,
        VISUAL_MAP_STATUS_CRITICAL_ALERT => 0,
        VISUAL_MAP_STATUS_NORMAL         => 0,
        VISUAL_MAP_STATUS_WARNING        => 0,
        VISUAL_MAP_STATUS_UNKNOWN        => 0,
        VISUAL_MAP_STATUS_WARNING_ALERT  => 0,
    ];

    $meta_connected_to = null;

    foreach ($valid_layout_items as $layout_item_data) {
        $node_id = null;

        if (is_metaconsole()) {
            $node_id = (
                !empty($layout_item_data['id_layout_linked']) &&
                !empty($layout_item_data['linked_layout_node_id'])
            ) ? $layout_item_data['linked_layout_node_id'] : $layout_item_data['id_metaconsole'];

            if (empty($node_id) && $meta_connected_to) {
                metaconsole_restore_db();
                // Restore db connection.
                $meta_connected_to = null;
            } else if (!empty($node_id)
                && (empty($meta_connected_to) || $meta_connected_to != $node_id)
            ) {
                if (!empty($meta_connected_to)) {
                    metaconsole_restore_db();
                    // Restore db connection.
                }

                $connection = metaconsole_get_connection_by_id($node_id);
                if (metaconsole_load_external_db($connection) != NOERR) {
                    continue;
                }

                $meta_connected_to = $node_id;
            }
        }

        $status = VISUAL_MAP_STATUS_NORMAL;

        $ent_element_status = enterprise_hook(
            'enterprise_visual_map_get_status_element',
            [$layout_item_data]
        );
        if ($ent_element_status === ENTERPRISE_NOT_HOOK) {
            $ent_element_status = false;
        }

        if ($ent_element_status !== false) {
            // Enterprise element.
            $status = $ent_element_status;
        } else {
            // Other.
            switch ($layout_item_data['type']) {
                case STATIC_GRAPH:
                case PERCENTILE_BAR:
                case PERCENTILE_BUBBLE:
                case CIRCULAR_PROGRESS_BAR:
                case CIRCULAR_INTERIOR_PROGRESS_BAR:
                    if (!empty($layout_item_data['id_layout_linked'])) {
                        // Linked layout.
                        $status = visual_map_get_layout_status(
                            $layout_item_data['id_layout_linked'],
                            $layout_item_data,
                            ($depth + 1)
                        );
                    } else if (!empty($layout_item_data['id_agente_modulo'])) {
                        // Module.
                        $module_status = modules_get_agentmodule_status(
                            $layout_item_data['id_agente_modulo']
                        );
                        $status = visual_map_translate_module_status(
                            $module_status
                        );
                    } else if (!empty($layout_item_data['id_agent'])) {
                        // Agent.
                        $agent = db_get_row('tagente', 'id_agente', $layout_item_data['id_agent']);
                        $agent_status = agents_get_status_from_counts($agent);
                        $status = visual_map_translate_agent_status(
                            $agent_status
                        );
                    } else {
                        // Unknown.
                        $status = VISUAL_MAP_STATUS_UNKNOWN;
                    }
                break;

                case GROUP_ITEM:
                    $group_status = groups_get_status(
                        $layout_item_data['id_group']
                    );
                    $status = visual_map_translate_agent_status($group_status);
                break;

                default:
                    // If it's a graph, a progress bar or a data tag,
                    // ALWAYS report status OK (=0) to avoid confussions here.
                    $status = VISUAL_MAP_STATUS_NORMAL;
                break;
            }
        }

        // When the status calculation type is 'default', only one critical
        // element is required to set the layout status as critical, so we can
        // return the critical status right now.
        if ((isset($status_data['linked_layout_status_type']) === true && $status_data['linked_layout_status_type'] === 'default')
            && ($status == VISUAL_MAP_STATUS_CRITICAL_BAD
            || $status == VISUAL_MAP_STATUS_CRITICAL_ALERT)
        ) {
            if (is_metaconsole() && $meta_connected_to) {
                // Restore db connection.
                metaconsole_restore_db();
            }

            return $status;
        } else {
            if (!isset($num_elements_by_status[$status])) {
                $num_elements_by_status[$status] = 0;
            }

            $num_elements_by_status[$status]++;
        }
    }

    if (is_metaconsole() && $meta_connected_to) {
        // Restore db connection.
        metaconsole_restore_db();
    }

    if (isset($status_data['linked_layout_status_type']) === true) {
        // Status calculation.
        switch ($status_data['linked_layout_status_type']) {
            default:
            case 'default':
                $num_items_critical_alert = $num_elements_by_status[VISUAL_MAP_STATUS_CRITICAL_ALERT];
                $num_items_critical = $num_elements_by_status[VISUAL_MAP_STATUS_CRITICAL_BAD];
                $num_items_warning_alert = $num_elements_by_status[VISUAL_MAP_STATUS_WARNING_ALERT];
                $num_items_warning = $num_elements_by_status[VISUAL_MAP_STATUS_WARNING];
                $num_items_unknown = $num_elements_by_status[VISUAL_MAP_STATUS_UNKNOWN];

                if ($num_items_critical_alert > 0) {
                    return VISUAL_MAP_STATUS_CRITICAL_ALERT;
                } else if ($num_items_critical > 0) {
                    return VISUAL_MAP_STATUS_CRITICAL_BAD;
                } else if ($num_items_warning_alert > 0) {
                    return VISUAL_MAP_STATUS_WARNING_ALERT;
                } else if ($num_items_warning > 0) {
                    return VISUAL_MAP_STATUS_WARNING;
                } else if ($num_items_unknown > 0) {
                    return VISUAL_MAP_STATUS_UNKNOWN;
                } else {
                    return VISUAL_MAP_STATUS_NORMAL;
                }
            break;
            case 'weight':
                $weight = $status_data['id_layout_linked_weight'];
                $num_items = count($valid_layout_items);
                $num_items_critical_alert = $num_elements_by_status[VISUAL_MAP_STATUS_CRITICAL_ALERT];
                $num_items_critical = $num_elements_by_status[VISUAL_MAP_STATUS_CRITICAL_BAD];
                $num_items_warning_alert = $num_elements_by_status[VISUAL_MAP_STATUS_WARNING_ALERT];
                $num_items_warning = $num_elements_by_status[VISUAL_MAP_STATUS_WARNING];
                $num_items_unknown = $num_elements_by_status[VISUAL_MAP_STATUS_UNKNOWN];

                if (($num_items_critical > 0 || $num_items_critical_alert > 0)
                    && ((($num_items_critical_alert + $num_items_critical) * 100) / $num_items) >= $weight
                ) {
                    return ($num_items_critical_alert > 0) ? VISUAL_MAP_STATUS_CRITICAL_ALERT : VISUAL_MAP_STATUS_CRITICAL_BAD;
                } else if (($num_items_warning > 0 || $num_items_warning_alert > 0)
                    && (($num_items_warning_alert + $num_items_warning * 100) / $num_items) >= $weight
                ) {
                    return ($num_items_warning_alert > 0) ? VISUAL_MAP_STATUS_WARNING_ALERT : VISUAL_MAP_STATUS_WARNING;
                } else if ($num_items_unknown > 0
                    && (($num_items_unknown * 100) / $num_items) >= $weight
                ) {
                    return VISUAL_MAP_STATUS_UNKNOWN;
                } else {
                    return VISUAL_MAP_STATUS_NORMAL;
                }
            break;

            case 'service':
                $num_items_critical = ($num_elements_by_status[VISUAL_MAP_STATUS_CRITICAL_BAD] + $num_elements_by_status[VISUAL_MAP_STATUS_CRITICAL_ALERT]);
                $critical_percentage = (($num_items_critical * 100) / count($valid_layout_items));

                $num_items_warning = ($num_elements_by_status[VISUAL_MAP_STATUS_WARNING] + $num_elements_by_status[VISUAL_MAP_STATUS_WARNING_ALERT]);
                $warning_percentage = (($num_items_warning * 100) / count($valid_layout_items));

                if ($critical_percentage >= $status_data['linked_layout_status_as_service_critical'] && $critical_percentage !== 0) {
                    return VISUAL_MAP_STATUS_CRITICAL_BAD;
                } else if ($warning_percentage >= $status_data['linked_layout_status_as_service_warning'] && $warning_percentage !== 0) {
                    return VISUAL_MAP_STATUS_WARNING;
                } else {
                    return VISUAL_MAP_STATUS_NORMAL;
                }
            break;
        }
    }
}


/**
 * Make a text for the parent select, when the label is not empty put this for
 * the return text. Instead for the empty labels make the text with next form
 * (<Type>) - <name_image> ( <agent_name> - <module_name> ) (<id item>)
 *
 * @param string  $label     The label of item in visual map.
 * @param string  $type      The label of type in visual map.
 * @param string  $image     The image of item in visual map.
 * @param string  $agent     The agent name of item in visual map.
 * @param string  $id_module The module name of item in visual map.
 * @param integer $idData    The id of item in visual map.
 *
 * @return string The text for the parent.
 */
function visual_map_create_internal_name_item(
    $label=null,
    $type='',
    $image='',
    $agent=null,
    $id_module=0,
    $idData=''
) {
    $text = '';

    if (empty($label)) {
        switch ($type) {
            case 'box_item':
            case BOX_ITEM:
                $text = __('Box');
            break;

            case 'module_graph':
            case MODULE_GRAPH:
                $text = __('Module graph');
            break;

            case 'clock':
            case CLOCK:
                $text = __('Clock');
            break;

            case 'bars_graph':
            case BARS_GRAPH:
                $text = __('Bars graph');
            break;

            case 'auto_sla_graph':
            case AUTO_SLA_GRAPH:
                $text = __('Event history graph');
            break;

            case 'percentile_bar':
            case PERCENTILE_BAR:
                $text = __('Percentile bar');
            break;

            case 'circular_progress_bar':
            case CIRCULAR_PROGRESS_BAR:
                $text = __('Circular progress bar');
            break;

            case 'interior_circular_progress_bar':
            case CIRCULAR_INTERIOR_PROGRESS_BAR:
                $text = __('Circular progress bar (interior)');
            break;

            case 'static_graph':
            case STATIC_GRAPH:
                $text = __('Static Image').' - '.$image;
            break;

            case 'simple_value':
            case SIMPLE_VALUE:
                $text = __('Simple Value');
            break;

            case 'label':
            case LABEL:
                $text = __('Label');
            break;

            case GROUP_ITEM:
            case 'group_item':
                $text = __('Group').' - ';
            break;

            case COLOR_CLOUD:
            case 'color_cloud':
                $text = __('Color cloud').' - ';
            break;

            case 'icon':
            case ICON:
                $text = __('Icon').' - '.$image;
            break;
        }

        if (!empty($agent)) {
            $text .= ' ('.ui_print_truncate_text($agent, 'agent_small', false);

            $moduleName = io_safe_output(db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $id_module));
            if (!empty($moduleName)) {
                $text .= ' - '.ui_print_truncate_text($moduleName, 'module_small', false);
            }

            $text .= ')';
        }

        $text .= ' ('.$idData.')';
    } else {
        $text = $label;
    }

    return io_safe_output($text);
}


function visual_map_get_items_parents($idVisual)
{
    // Avoid the sort by 'label' in the query cause oracle cannot sort by columns with CLOB type
    $items = db_get_all_rows_filter('tlayout_data', ['id_layout' => $idVisual]);
    if ($items == false) {
        $items = [];
    } else {
        // Sort by label
        sort_by_column($items, 'label');
    }

    $return = [];
    foreach ($items as $item) {
        $agent = null;
        if ($item['id_agent'] != 0) {
            $agent = io_safe_output(agents_get_alias($item['id_agent']));
        }

        $return[$item['id']] = visual_map_create_internal_name_item(
            $item['label'],
            $item['type'],
            $item['image'],
            $agent,
            $item['id_agente_modulo'],
            $item['id']
        );
    }

    return $return;
}


/**
 * Get the X axis coordinate of a layout item
 *
 * @param int Id of the layout to get.
 *
 * @return integer The X axis coordinate value.
 */
function visual_map_get_layoutdata_x($id_layoutdata)
{
    return (float) db_get_value('pos_x', 'tlayout_data', 'id', (int) $id_layoutdata);
}


/**
 * Get the Y axis coordinate of a layout item
 *
 * @param int Id of the layout to get.
 *
 * @return integer The Y axis coordinate value.
 */
function visual_map_get_layoutdata_y($id_layoutdata)
{
    return (float) db_get_value(
        'pos_y',
        'tlayout_data',
        'id',
        (int) $id_layoutdata
    );
}


function visual_map_type_in_js($type)
{
    switch ($type) {
        case STATIC_GRAPH:
        return 'static_graph';

            break;
        case PERCENTILE_BAR:
        return 'percentile_item';

            break;
        case CIRCULAR_PROGRESS_BAR:
        return 'percentile_item';

            break;
        case CIRCULAR_INTERIOR_PROGRESS_BAR:
        return 'percentile_item';

            break;
        case MODULE_GRAPH:
        return 'module_graph';

            break;
        case BARS_GRAPH:
        return 'bars_graph';

            break;
        case AUTO_SLA_GRAPH:
        return 'auto_sla_graph';

            break;
        case SIMPLE_VALUE:
        return 'simple_value';

            break;
        case LABEL:
        return 'label';

            break;
        case ICON:
        return 'icon';

            break;
        case CLOCK:
        return 'clock';

            break;
        case SIMPLE_VALUE_MAX:
        return 'simple_value';

            break;
        case SIMPLE_VALUE_MIN:
        return 'simple_value';

            break;
        case SIMPLE_VALUE_AVG:
        return 'simple_value';

            break;
        case PERCENTILE_BUBBLE:
        return 'percentile_item';

            break;
        case SERVICE:
        return 'service';

            break;
        case GROUP_ITEM:
        return 'group_item';

            break;
        case BOX_ITEM:
        return 'box_item';

            break;
        case LINE_ITEM:
        return 'line_item';

        case NETWORK_LINK:
        return 'network_link';

        case COLOR_CLOUD:
        return 'color_cloud';

            break;
    }
}


function visual_map_macro($label, $module)
{
    $label = str_replace('_date_', strftime('%x'), $label);
    $label = str_replace('_time_', strftime('%T'), $label);
    $label = str_replace('_agent_', agents_get_alias(modules_get_agentmodule_agent($module)), $label);
    $label = str_replace('_module_', modules_get_agentmodule_name($module), $label);
    $label = str_replace('_agentdescription_', agents_get_description(modules_get_agentmodule_agent($module)), $label);
    $label = str_replace('_address_', agents_get_address(modules_get_agentmodule_agent($module)), $label);
    $label = str_replace('_moduledescription_', modules_get_agentmodule_descripcion($module), $label);
    return $label;
}


function visual_map_get_color_cloud_element($data)
{
    $id = (int) $data['id'];
    $diameter = (int) $data['width'];
    $dynamic_fields = [];

    try {
        // Yes, the dynamic fields object is stored into the label field. ¯\_(ツ)_/¯
        if (!empty($data['label'])) {
            $dynamic_fields = json_decode($data['label'], true);
        }
    } catch (Exception $ex) {
    }

    $default_color = !empty($dynamic_fields['default_color']) ? $dynamic_fields['default_color'] : '#FFFFFF';
    $color = $default_color;
    // The svg gradient needs a unique identifier
    $gradient_id = 'grad_'.$id;

    // Color ranges
    if (!empty($dynamic_fields['color_ranges'])
        && !empty($data['id_agente_modulo'])
    ) {
        $node_id = null;
        $node_connected = false;
        // Connect to node
        if (is_metaconsole() && !empty($data['id_metaconsole'])) {
            $node_id = (int) $data['id_metaconsole'];
            if (metaconsole_connect(null, $node_id) === NOERR) {
                $node_connected = true;
            }
        }

        // Fetch module value
        $value = (!$node_id || ($node_id && $node_connected)) ? modules_get_last_value($data['id_agente_modulo']) : false;

        // Restore connection
        if ($node_connected) {
            metaconsole_restore_db();
        }

        if ($value !== false) {
            /*
                TODO: It would be ok to give support to string values in the future?
             * It can be done by matching the range value with the value if it is a
             * string. I think the function to retrieve the value only supports
             * numeric values.
             */
            $value = (float) $value;
            foreach ($dynamic_fields['color_ranges'] as $range) {
                if ($range['from_value'] <= $value && $range['to_value'] >= $value) {
                    $color = $range['color'];
                    break;
                }
            }
        }
    }

    ob_start();
    ?>
    <svg height="<?php echo $diameter; ?>" width="<?php echo $diameter; ?>">
        <defs>
            <radialGradient id="<?php echo $gradient_id; ?>" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
                <stop offset="0%" style="stop-color:<?php echo $color; ?>;stop-opacity:0.9" />
                <!-- <stop offset="50%" style="stop-color:<?php echo $color; ?>;stop-opacity:0.6" /> -->
                <stop offset="100%" style="stop-color:<?php echo $color; ?>;stop-opacity:0" />
            </radialGradient>
        </defs>
        <circle cx="50%" cy="50%" r="50%" fill="url(#<?php echo $gradient_id; ?>)" />
    </svg>
    <?php
    return ob_get_clean();
}


/**
 * Load the Visual Console Client files (js & css).
 *
 * @return void
 */
function visual_map_load_client_resources()
{
    global $config;

    $baseUrl = ui_get_full_url(false, false, false, false);
    $vcClientPath = 'include/visual-console-client';
    $dir = $config['homedir'].'/'.$vcClientPath;
    if (is_dir($dir)) {
        $dh = opendir($dir);
        if ($dh) {
            while (($file = readdir($dh)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                preg_match('/.*.js$/', $file, $match, PREG_OFFSET_CAPTURE);
                if (empty($match) === false) {
                    $url = $baseUrl.$vcClientPath.'/'.$match[0][0];
                    echo '<script type="text/javascript" src="'.$url.'"></script>';
                    continue;
                }

                preg_match('/.*.css$/', $file, $match, PREG_OFFSET_CAPTURE);
                if (empty($match) === false) {
                    $url = $baseUrl.$vcClientPath.'/'.$match[0][0];
                    echo '<link type="text/css" rel="stylesheet" href="'.$url.'" />';
                }
            }

            closedir($dh);
        }
    }
}


/**
 * Labels styles visual console.
 *
 * @param string  $uniq  Uniq str.
 * @param integer $ratio Ratio.
 *
 * @return string Css output.
 */
function css_label_styles_visual_console($uniq, $ratio=1)
{
    global $config;
    $output = '';
    // Horrible trick! due to the use of tinyMCE
    // it is necessary to modify specific classes of each
    // of the visual consoles.
    $output .= '.c-'.$uniq.' a {color: #3f3f3f } ';
    $output .= '.c-'.$uniq.' .label p strong span {display: inline-block !important; line-height: normal !important} ';
    $output .= '.c-'.$uniq.' *:not(.parent_graph p table tr td span) { font-size: '.(8 * $ratio).'pt; line-height:'.(8 * ($ratio)).'pt; }';
    $output .= '.c-'.$uniq.' .visual-console-item-label table tr td { padding: 0; margin: 0; white-space: pre-wrap; }';
    $output .= '.c-'.$uniq.' .visual_font_size_4pt, .c-'.$uniq.' .visual_font_size_4pt * { font-size: '.(4 * $ratio).'pt !important; line-height:'.(4 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_6pt, .c-'.$uniq.' .visual_font_size_6pt * { font-size: '.(6 * $ratio).'pt !important; line-height:'.(6 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_8pt, .c-'.$uniq.' .visual_font_size_8pt * { font-size: '.(8 * $ratio).'pt !important; line-height:'.(8 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_10pt, .c-'.$uniq.' .visual_font_size_10pt * { font-size: '.(10 * $ratio).'pt !important; line-height:'.(10 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_12pt, .c-'.$uniq.' .visual_font_size_12pt * { font-size: '.(12 * $ratio).'pt !important; line-height:'.(12 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_14pt, .c-'.$uniq.' .visual_font_size_14pt * { font-size: '.(14 * $ratio).'pt !important; line-height:'.(14 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_18pt, .c-'.$uniq.' .visual_font_size_18pt * { font-size: '.(18 * $ratio).'pt !important; line-height:'.(18 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_24pt, .c-'.$uniq.' .visual_font_size_24pt * { font-size: '.(24 * $ratio).'pt !important; line-height:'.(24 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_28pt, .c-'.$uniq.' .visual_font_size_28pt * { font-size: '.(28 * $ratio).'pt !important; line-height:'.(28 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_36pt, .c-'.$uniq.' .visual_font_size_36pt * { font-size: '.(36 * $ratio).'pt !important; line-height:'.(36 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_48pt, .c-'.$uniq.' .visual_font_size_48pt * { font-size: '.(48 * $ratio).'pt !important; line-height:'.(48 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_60pt, .c-'.$uniq.' .visual_font_size_60pt * { font-size: '.(60 * $ratio).'pt !important; line-height:'.(60 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_72pt, .c-'.$uniq.' .visual_font_size_72pt * { font-size: '.(72 * $ratio).'pt !important; line-height:'.(72 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_84pt, .c-'.$uniq.' .visual_font_size_84pt * { font-size: '.(84 * $ratio).'pt !important; line-height:'.(84 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_96pt, .c-'.$uniq.' .visual_font_size_96pt * { font-size: '.(96 * $ratio).'pt !important; line-height:'.(96 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_116pt, .c-'.$uniq.' .visual_font_size_116pt * { font-size: '.(116 * $ratio).'pt !important; line-height:'.(116 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_128pt, .c-'.$uniq.' .visual_font_size_128pt * { font-size: '.(128 * $ratio).'pt !important; line-height:'.(128 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_140pt, .c-'.$uniq.' .visual_font_size_140pt * { font-size: '.(140 * $ratio).'pt !important; line-height:'.(140 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_154pt, .c-'.$uniq.' .visual_font_size_154pt * { font-size: '.(154 * $ratio).'pt !important; line-height:'.(154 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual_font_size_196pt, .c-'.$uniq.' .visual_font_size_196pt * { font-size: '.(196 * $ratio).'pt !important; line-height:'.(196 * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .flot-text, .c-'.$uniq.' .flot-text * { font-size: '.(($config['font_size'] - 2) * $ratio).'pt !important; line-height:'.(($config['font_size'] - 2) * ($ratio)).'pt !important; }';
    $output .= '.c-'.$uniq.' .visual-console-item .digital-clock span.time {font-size: '.(50 * $ratio).'px !important; line-height: '.(50 * $ratio).'px !important;}';
    $output .= '.c-'.$uniq.' .visual-console-item .digital-clock span.date {font-size: '.(25 * $ratio).'px !important; line-height: '.(25 * $ratio).'px !important;}';
    $output .= '.c-'.$uniq.' .visual-console-item .digital-clock span.timezone {font-size: '.(25 * $ratio).'px !important; line-height: '.(25 * $ratio).'px !important;}';
    $output .= '.c-'.$uniq.' .visual-console-item .donut-graph * {font-size: '.(8 * $ratio).'px !important; line-height: '.(8 * $ratio).'px !important;}';
    $output .= '.c-'.$uniq.' .visual-console-item .donut-graph g rect {width:'.(25 * $ratio).' !important; height: '.(15 * $ratio).' !important;}';

    return $output;
}
