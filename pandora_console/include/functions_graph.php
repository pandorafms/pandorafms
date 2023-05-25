<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Graphs
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
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

require_once $config['homedir'].'/include/graphs/fgraph.php';
require_once $config['homedir'].'/include/functions_reporting.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_integriaims.php';


/**
 * Function for retun data extract aand uncompressed bbdd.
 *
 * @param integer $agent_module_id   ID.
 * @param array   $date_array        Date stasrt finish and period.
 * @param array   $data_module_graph Data module.
 * @param array   $params            Params graphs.
 * @param integer $series_suffix     Int.
 *
 * @return array Return data module.
 */
function grafico_modulo_sparse_data_chart(
    $agent_module_id,
    $date_array,
    $data_module_graph,
    $params,
    $series_suffix
) {
    global $config;

    // To avoid showing all the data at the same time which can be reloaded,
    // a system of boxes is made starting from a constant = 250
    // and the selected period of time in addition to being able to reduce
    // the level of boxes, that is, increasing the level of detail of the graph
    // until the full option that would show all the points (data)
    // that that period contains.
    $data_slice = ($date_array['period'] / (250 * $params['zoom']));

    if ($data_module_graph['id_module_type'] == 23
        || $data_module_graph['id_module_type'] == 3
        || $data_module_graph['id_module_type'] == 17
        || $data_module_graph['id_module_type'] == 10
        || $data_module_graph['id_module_type'] == 33
    ) {
        $data = db_get_all_rows_filter(
            'tagente_datos_string',
            ['id_agente_modulo' => (int) $agent_module_id,
                "utimestamp > '".$date_array['start_date']."'",
                "utimestamp < '".$date_array['final_date']."'",
                'group' => "ROUND(utimestamp / $data_slice)",
                'order' => 'utimestamp ASC'
            ],
            [
                'count(*) as datos',
                'min(utimestamp) as utimestamp',
            ],
            'AND',
            $data_module_graph['history_db']
        );
    } else {
        // all points(data) and boolean
        if ($data_module_graph['id_module_type'] == 2
            || $data_module_graph['id_module_type'] == 6
            || $data_module_graph['id_module_type'] == 21
            || $data_module_graph['id_module_type'] == 18
            || $data_module_graph['id_module_type'] == 9
            || $data_module_graph['id_module_type'] == 31
            || $data_module_graph['id_module_type'] == 100
        ) {
            $data = db_get_all_rows_filter(
                'tagente_datos',
                ['id_agente_modulo' => (int) $agent_module_id,
                    "utimestamp > '".$date_array['start_date']."'",
                    "utimestamp < '".$date_array['final_date']."'",
                    'order' => 'utimestamp ASC'
                ],
                [
                    'datos',
                    'utimestamp',
                ],
                'AND',
                $data_module_graph['history_db']
            );
        }
    }

    if ($data === false) {
        $data = [];
    }

    // Get previous data.
    $previous_data = modules_get_previous_data(
        $agent_module_id,
        $date_array['start_date']
    );

    if ($previous_data !== false) {
        $previous_data['utimestamp'] = $date_array['start_date'];
        unset($previous_data['id_agente_modulo']);
        array_unshift($data, $previous_data);
    }

    // Get next data.
    $nextData = modules_get_next_data(
        $agent_module_id,
        $date_array['final_date']
    );

    if ($nextData !== false) {
        unset($nextData['id_agente_modulo']);
        array_push($data, $nextData);
    } else if (count($data) > 0) {
        // Propagate the last known data to the end of the interval.
        $nextData = [
            'datos'      => $data[(count($data) - 1)]['datos'],
            'utimestamp' => $date_array['final_date'],
        ];
        array_push($data, $nextData);
    }

    // Check available data.
    if (count($data) < 1) {
        return false;
    }

    $array_data = [];
    $min_value = (PHP_INT_MAX - 1);
    $max_value = (PHP_INT_MIN + 1);
    $array_percentil = [];

    foreach ($data as $k => $v) {
        // Convert array.
        if ($params['flag_overlapped']) {
            $array_data['sum'.$series_suffix]['data'][$k] = [
                (($v['utimestamp'] + $date_array['period']  ) * 1000),
                $v['datos'],
            ];
        } else {
            $array_data['sum'.$series_suffix]['data'][$k] = [
                ($v['utimestamp'] * 1000),
                $v['datos'],
            ];
        }

        // Min.
        if ($min_value > $v['datos']) {
            $min_value = $v['datos'];
        }

        // Max.
        if ($max_value < $v['datos']) {
            $max_value = $v['datos'];
        }

        // Avg.
        $sum_data += $v['datos'];
        $count_data++;

        // Percentil.
        if (!isset($params['percentil']) && $params['percentil']) {
            $array_percentil[] = $v['datos'];
        }
    }

    $array_data['sum'.$series_suffix]['min'] = $min_value;
    $array_data['sum'.$series_suffix]['max'] = $max_value;
    $array_data['sum'.$series_suffix]['avg'] = ($sum_data / $count_data);
    $array_data['sum'.$series_suffix]['agent_module_id'] = $agent_module_id;
    $array_data['sum'.$series_suffix]['id_module_type'] = $data_module_graph['id_module_type'];
    $array_data['sum'.$series_suffix]['agent_name'] = $data_module_graph['agent_name'];
    $array_data['sum'.$series_suffix]['module_name'] = $data_module_graph['module_name'];
    $array_data['sum'.$series_suffix]['agent_alias'] = $data_module_graph['agent_alias'];

    if (!isset($params['percentil'])
        && $params['percentil']
        && !$params['flag_overlapped']
    ) {
        $percentil_result = get_percentile(
            $params['percentil'],
            $array_percentil
        );
        $array_data['percentil'.$series_suffix]['data'][0] = [
            ($date_array['start_date'] * 1000),
            $percentil_result,
        ];
        $array_data['percentil'.$series_suffix]['data'][1] = [
            ($date_array['final_date'] * 1000),
            $percentil_result,
        ];
        $array_data['percentil'.$series_suffix]['agent_module_id'] = $agent_module_id;
    }

    return $array_data;
}


/**
 * Prepare data for send to function js paint charts.
 *
 * @param integer $agent_module_id   ID.
 * @param array   $date_array        Date stasrt finish and period.
 * @param array   $data_module_graph Data module.
 * @param array   $params            Params graphs.
 * @param integer $series_suffix     Int.
 *
 * @return array Prepare data to paint js.
 */
function grafico_modulo_sparse_data(
    $agent_module_id,
    $date_array,
    $data_module_graph,
    $params,
    $series_suffix
) {
    global $config;
    global $array_events_alerts;

    if ($params['fullscale']) {
        $array_data = fullscale_data(
            $agent_module_id,
            $date_array,
            $params['show_unknown'],
            $params['percentil'],
            $series_suffix,
            $params['flag_overlapped'],
            false,
            $params['type_mode_graph']
        );
    } else {
        // Uncompress data except boolean and string.
        if ($data_module_graph['id_module_type'] == 23
            || $data_module_graph['id_module_type'] == 3
            || $data_module_graph['id_module_type'] == 17
            || $data_module_graph['id_module_type'] == 10
            || $data_module_graph['id_module_type'] == 33
            || $data_module_graph['id_module_type'] == 2
            || $data_module_graph['id_module_type'] == 6
            || $data_module_graph['id_module_type'] == 21
            || $data_module_graph['id_module_type'] == 18
            || $data_module_graph['id_module_type'] == 9
            || $data_module_graph['id_module_type'] == 31
            || $data_module_graph['id_module_type'] == 100
        ) {
            $array_data = grafico_modulo_sparse_data_chart(
                $agent_module_id,
                $date_array,
                $data_module_graph,
                $params,
                $series_suffix
            );
        } else {
            $data_slice = ($date_array['period'] / (250 * $params['zoom']) + 100);
            $array_data = fullscale_data(
                $agent_module_id,
                $date_array,
                $params['show_unknown'],
                $params['percentil'],
                $series_suffix,
                $params['flag_overlapped'],
                $data_slice,
                $params['type_mode_graph']
            );
        }
    }

    if (empty($array_data) === true) {
        return [];
    }

    if ($array_data === false && (!$params['graph_combined']
        && !isset($array_data['sum1']['data'][0][1]) && !$params['baseline'])
    ) {
        return false;
    }

    if ((int) $params['type_mode_graph'] !== 2
        && (int) $params['type_mode_graph'] !== 3
    ) {
        $array_data = series_suffix_leyend(
            'sum',
            $series_suffix,
            $agent_module_id,
            $data_module_graph,
            $array_data
        );
    }

    if ($params['percentil']) {
        $array_data = series_suffix_leyend(
            'percentil',
            $series_suffix,
            $agent_module_id,
            $data_module_graph,
            $array_data
        );
    }

    if ($params['type_mode_graph'] > 0) {
        if ((int) $params['type_mode_graph'] === 1
            || (int) $params['type_mode_graph'] === 3
        ) {
            $array_data = series_suffix_leyend(
                'min',
                $series_suffix,
                $agent_module_id,
                $data_module_graph,
                $array_data
            );
        }

        if ((int) $params['type_mode_graph'] === 1
            || (int) $params['type_mode_graph'] === 2
        ) {
            $array_data = series_suffix_leyend(
                'max',
                $series_suffix,
                $agent_module_id,
                $data_module_graph,
                $array_data
            );
        }
    }

    // This is for a specific type of report that consists in passing
    // an interval and doing the average sum and avg.
    if ($params['force_interval'] != '') {
        $period_time_interval = ($date_array['period'] * 1000);
        $start_period = ($date_array['start_date'] * 1000);
        $i = 0;

        $sum_data = 0;
        $count_data = 0;
        $data_last_acum = $array_data['sum1']['data'][0][1];

        $array_data_only = [];
        while ($period_time_interval > 0) {
            foreach ($array_data['sum1']['data'] as $key => $value) {
                if ($value[0] >= $start_period
                    && $value[0] < ($start_period + $params['time_interval'] * 1000)
                ) {
                    $sum_data = $value[1];
                    $array_data_only[] = $value[1];
                    $count_data++;
                    unset($array_data['sum1']['data'][$key]);
                } else {
                    if ($params['force_interval'] == 'max_only') {
                        $acum_array_data[$i][0] = $start_period;
                        if (is_array($array_data_only)
                            && count($array_data_only) > 0
                        ) {
                            $acum_array_data[$i][1] = max($array_data_only);
                            $data_last_acum = $array_data_only[(count($array_data_only) - 1)];
                        } else {
                            $acum_array_data[$i][1] = $data_last_acum;
                        }
                    }

                    if ($params['force_interval'] == 'min_only') {
                        $acum_array_data[$i][0] = $start_period;
                        if (is_array($array_data_only)
                            && count($array_data_only) > 0
                        ) {
                            $acum_array_data[$i][1] = min($array_data_only);
                            $data_last_acum = $array_data_only[(count($array_data_only) - 1)];
                        } else {
                            $acum_array_data[$i][1] = $data_last_acum;
                        }
                    }

                    if ($params['force_interval'] == 'avg_only') {
                        $acum_array_data[$i][0] = $start_period;
                        if (is_array($array_data_only)
                            && count($array_data_only) > 0
                        ) {
                            $acum_array_data[$i][1] = ($sum_data / $count_data);
                        } else {
                            $acum_array_data[$i][1] = $data_last_acum;
                        }
                    }

                    $start_period = ($start_period + $params['time_interval'] * 1000);
                    $array_data_only = [];
                    $sum_data = 0;
                    $count_data = 0;
                    $i++;
                    break;
                }
            }

            $period_time_interval = ($period_time_interval - $params['time_interval']);
        }

        // Drag the last value to paint the graph correctly.
        $acum_array_data[] = [
            0 => $start_period,
            1 => $acum_array_data[($i - 1)][1],
        ];
        $array_data['sum1']['data'] = $acum_array_data;
    }

    $events = [];
    if (isset($array_data['sum'.$series_suffix]['max'])) {
        $max = $array_data['sum'.$series_suffix]['max'];
        $min = $array_data['sum'.$series_suffix]['min'];
        $avg = $array_data['sum'.$series_suffix]['avg'];
    }

    if (!$params['flag_overlapped']) {
        if ($params['fullscale']) {
            if ($params['show_unknown']
                && isset($array_data['unknown'.$series_suffix])
                && is_array($array_data['unknown'.$series_suffix]['data'])
            ) {
                foreach ($array_data['unknown'.$series_suffix]['data'] as $key => $s_date) {
                    if ($s_date[1] == 1) {
                        $array_data['unknown'.$series_suffix]['data'][$key] = [
                            $s_date[0],
                            ($max * 1.05),
                        ];
                    }
                }
            }
        } else {
            if ($params['show_unknown']) {
                $unknown_events = db_get_module_ranges_unknown(
                    $agent_module_id,
                    $date_array['start_date'],
                    $date_array['final_date'],
                    $data_module_graph['history_db'],
                    1
                );

                if ($unknown_events !== false) {
                    foreach ($unknown_events as $key => $s_date) {
                        if (isset($s_date['time_from'])) {
                            $array_data['unknown'.$series_suffix]['data'][] = [
                                (($s_date['time_from'] - 1) * 1000),
                                0,
                            ];

                            $array_data['unknown'.$series_suffix]['data'][] = [
                                ($s_date['time_from'] * 1000),
                                ($max * 1.05),
                            ];
                        } else {
                            $array_data['unknown'.$series_suffix]['data'][] = [
                                ($date_array['start_date'] * 1000),
                                ($max * 1.05),
                            ];
                        }

                        if (isset($s_date['time_to'])) {
                            $array_data['unknown'.$series_suffix]['data'][] = [
                                ($s_date['time_to'] * 1000),
                                ($max * 1.05),
                            ];

                            $array_data['unknown'.$series_suffix]['data'][] = [
                                (($s_date['time_to'] + 1) * 1000),
                                0,
                            ];
                        } else {
                            $array_data['unknown'.$series_suffix]['data'][] = [
                                ($date_array['final_date'] * 1000),
                                ($max * 1.05),
                            ];
                        }
                    }
                }
            }
        }

        if ($params['show_events']
            || $params['show_alerts']
        ) {
            $events = db_get_all_rows_filter(
                'tevento',
                ['id_agentmodule' => $agent_module_id,
                    'utimestamp > '.$date_array['start_date'],
                    'utimestamp < '.$date_array['final_date'],
                    'order' => 'utimestamp ASC'
                ],
                false,
                'AND',
                $data_module_graph['history_db']
            );

            $alerts_array = [];
            $events_array = [];

            if ($events && is_array($events)) {
                $count_events = 0;
                $count_alerts = 0;
                foreach ($events as $k => $v) {
                    if (strpos($v['event_type'], 'alert') !== false) {
                        if ($params['flag_overlapped']) {
                            $alerts_array['data'][$count_alerts] = [
                                ($v['utimestamp'] + $date_array['period'] * 1000),
                                ($max * 1.10),
                            ];
                        } else {
                            $alerts_array['data'][$count_alerts] = [
                                ($v['utimestamp'] * 1000),
                                ($max * 1.10),
                            ];
                        }

                        $count_alerts++;
                    } else {
                        if ($params['flag_overlapped']) {
                            if (( strstr($v['event_type'], 'going_up') )
                                || ( strstr($v['event_type'], 'going_down') )
                            ) {
                                $events_array['data'][$count_events] = [
                                    (($v['utimestamp'] + 1 + $date_array['period']) * 1000),
                                    ($max * 1.15),
                                ];
                            } else {
                                $events_array['data'][$count_events] = [
                                    ($v['utimestamp'] + $date_array['period'] * 1000),
                                    ($max * 1.15),
                                ];
                            }
                        } else {
                            if (( strstr($v['event_type'], 'going_up') )
                                || ( strstr($v['event_type'], 'going_down') )
                            ) {
                                $events_array['data'][$count_events] = [
                                    (($v['utimestamp'] + 1) * 1000),
                                    ($max * 1.15),
                                ];
                            } else {
                                $events_array['data'][$count_events] = [
                                    ($v['utimestamp'] * 1000),
                                    ($max * 1.15),
                                ];
                            }
                        }

                        $count_events++;
                    }
                }
            }
        }

        if ($params['show_events']) {
            $array_data['event'.$series_suffix] = $events_array;
        }

        if ($params['show_alerts']) {
            $array_data['alert'.$series_suffix] = $alerts_array;
        }
    }

    if ($params['return_data'] == 1) {
        return $array_data;
    }

    $array_events_alerts[$series_suffix] = $events;

    return $array_data;
}


/**
 * Functions to create graphs.
 *
 * @param array $params Details builds graphs. For example:
 * 'agent_module_id'     => $agent_module_id,
 * 'period'              => $period,
 * 'show_events'         => false,
 * 'width'               => $width,
 * 'height'              => $height,
 * 'title'               => '',
 * 'unit_name'           => null,
 * 'show_alerts'         => false,
 * 'date'                => 0,
 * 'unit'                => '',
 * 'baseline'            => 0,
 * 'return_data'         => 0,
 * 'show_title'          => true,
 * 'only_image'          => false,
 * 'homeurl'             => $config['homeurl'],
 * 'ttl'                 => 1,
 * 'adapt_key'           => '',
 * 'compare'             => false,
 * 'show_unknown'        => false,
 * 'menu'                => true,
 * 'backgroundColor'     => 'white',
 * 'percentil'           => null,
 * 'dashboard'           => false,
 * 'vconsole'            => false,
 * 'type_graph'          => 'area',
 * 'fullscale'           => false,
 * 'id_widget_dashboard' => false,
 * 'force_interval'      => '',
 * 'time_interval'       => 300,
 * 'array_data_create'   => 0,
 * 'show_legend'         => true,
 * 'show_overview'       => true,
 * 'return_img_base_64'  => false,
 * 'image_threshold'      => false,
 * 'graph_combined'      => false,
 * 'graph_render'        => 0,
 * 'zoom'                => 1,
 * 'server_id'           => null,
 * 'stacked'             => 0
 * 'maximum_y_axis'      => 0.
 *
 * @return string html Content graphs.
 */
function grafico_modulo_sparse($params)
{
    global $config;

    if (isset($params) === false || is_array($params) === false) {
        return false;
    }

    if (isset($params['period']) === false) {
        return false;
    }

    if (isset($params['show_events']) === false) {
        $params['show_events'] = false;
    }

    if (isset($params['width']) === false) {
        $params['width'] = '90%';
    }

    if (isset($params['height']) === false) {
        $params['height'] = 450;
    }

    if (isset($params['title']) === false) {
        $params['title'] = '';
    }

    if (isset($params['unit_name']) === false) {
        $params['unit_name'] = null;
    }

    if (isset($params['show_alerts']) === false) {
        $params['show_alerts'] = false;
    }

    if (isset($params['date']) === false || !$params['date']) {
        $params['date'] = get_system_time();
    }

    if (isset($params['unit']) === false) {
        $params['unit'] = '';
    }

    if (isset($params['baseline']) === false) {
        $params['baseline'] = 0;
    }

    if (isset($params['return_data']) === false) {
        $params['return_data'] = 0;
    }

    if (isset($params['show_title']) === false) {
        $show_title = true;
    }

    if (isset($params['only_image']) === false) {
        $params['only_image'] = false;
    }

    if (isset($params['homeurl']) === false) {
        $params['homeurl'] = $config['homeurl'];
    }

    if (isset($params['ttl']) === false) {
        $params['ttl'] = 1;
    }

    if (isset($params['adapt_key']) === false) {
        $params['adapt_key'] = '';
    }

    if (isset($params['compare']) === false) {
        $params['compare'] = false;
    }

    if (isset($params['show_unknown']) === false) {
        $params['show_unknown'] = false;
    }

    if (isset($params['menu']) === false) {
        $params['menu'] = true;
    }

    if (isset($params['show_legend']) === false) {
        $params['show_legend'] = true;
    }

    if (isset($params['show_overview']) === false) {
        $params['show_overview'] = true;
    }

    if (isset($params['show_export_csv']) === false) {
        $params['show_export_csv'] = true;
    }

    if (isset($params['backgroundColor']) === false) {
        $params['backgroundColor'] = 'white';
    }

    if (isset($params['only_image']) === true && $params['vconsole'] !== true) {
        $params['backgroundColor'] = 'transparent';
    }

    if (isset($params['percentil']) === false) {
        $params['percentil'] = null;
    }

    if (isset($params['dashboard']) === false) {
        $params['dashboard'] = false;
    }

    if (isset($params['vconsole']) === false || $params['vconsole'] == false) {
        $params['vconsole'] = false;
    } else {
        $params['menu'] = false;
    }

    if (isset($params['type_graph']) === false) {
        $params['type_graph'] = $config['type_module_charts'];
    }

    if (isset($params['fullscale']) === false) {
        $params['fullscale'] = false;
    }

    if (isset($params['id_widget_dashboard']) === false) {
        $params['id_widget_dashboard'] = false;
    }

    if (isset($params['force_interval']) === false) {
        $params['force_interval'] = '';
    }

    if (isset($params['time_interval']) === false) {
        $params['time_interval'] = 300;
    }

    if (isset($params['array_data_create']) === false) {
        $params['array_data_create'] = 0;
    }

    if (isset($params['return_img_base_64']) === false) {
        $params['return_img_base_64'] = false;
    }

    if (isset($params['image_threshold']) === false) {
        $params['image_threshold'] = false;
    }

    if (isset($params['graph_combined']) === false) {
        $params['graph_combined'] = false;
    }

    if (isset($params['zoom']) === false) {
        $params['zoom'] = ($config['zoom_graph']) ? $config['zoom_graph'] : 1;
    }

    if (isset($params['type_mode_graph']) === false) {
        $params['type_mode_graph'] = ($config['type_mode_graph'] ?? null);
        if (isset($params['graph_render']) === true) {
            $params['type_mode_graph'] = $params['graph_render'];
        }
    }

    if (isset($params['maximum_y_axis']) === false) {
        $params['maximum_y_axis'] = $config['maximum_y_axis'];
    }

    if (isset($params['projection']) === false) {
        $params['projection'] = false;
    }

    if (isset($params['pdf']) === false) {
        $params['pdf'] = false;
    }

    if (isset($params['agent_module_id']) === false) {
        return graph_nodata_image($params);
    } else {
        $agent_module_id = $params['agent_module_id'];
    }

    if (isset($params['stacked']) === false) {
        $params['stacked'] = 0;
    }

    if (isset($params['graph_font_size']) === true) {
        $font_size = $params['graph_font_size'];
    } else {
        $font_size = $config['font_size'];
    }

    if (isset($params['basic_chart']) === false) {
        $params['basic_chart'] = false;
    }

    if (isset($params['array_colors']) === false) {
        $params['array_colors'] = false;
    }

    // If is metaconsole set 10pt size value.
    if (is_metaconsole()) {
        $font_size = '10';
    }

    $params['grid_color'] = '#C1C1C1';
    $params['legend_color'] = '#636363';
    $params['font'] = 'lato';
    $params['font_size']  = $font_size;
    $params['short_data'] = $config['short_module_graph_data'];

    if ($params['only_image']) {
        return generator_chart_to_pdf('sparse', $params);
    }

    global $graphic_type;
    global $array_events_alerts;

    $array_data = [];
    $legend = [];
    $array_events_alerts = [];

    $date_array = [];
    $date_array['period']     = $params['period'];
    $date_array['final_date'] = $params['date'];
    $date_array['start_date'] = ($params['date'] - $params['period']);

    if ($agent_module_id) {
        $module_data = db_get_row_sql(
            'SELECT * FROM tagente_modulo
            WHERE id_agente_modulo = '.$agent_module_id
        );

        $data_module_graph = [];
        $data_module_graph['history_db'] = db_search_in_history_db(
            $date_array['start_date']
        );
        $data_module_graph['agent_name'] = modules_get_agentmodule_agent_name(
            $agent_module_id
        );
        $data_module_graph['agent_alias'] = modules_get_agentmodule_agent_alias(
            $agent_module_id
        );
        $data_module_graph['agent_id'] = $module_data['id_agente'];
        $data_module_graph['module_name'] = $module_data['nombre'];
        $data_module_graph['id_module_type'] = $module_data['id_tipo_modulo'];
        $data_module_graph['module_type'] = modules_get_moduletype_name(
            $data_module_graph['id_module_type']
        );
        $data_module_graph['uncompressed'] = is_module_uncompressed(
            $data_module_graph['module_type']
        );
        $data_module_graph['w_min'] = $module_data['min_warning'];
        $data_module_graph['w_max'] = $module_data['max_warning'];
        $data_module_graph['w_inv'] = $module_data['warning_inverse'];
        $data_module_graph['c_min'] = $module_data['min_critical'];
        $data_module_graph['c_max'] = $module_data['max_critical'];
        $data_module_graph['c_inv'] = $module_data['critical_inverse'];
        $data_module_graph['unit'] = $module_data['unit'];
    } else {
        $data_module_graph = false;
    }

    // Format of the graph.
    if (empty($params['unit']) === true) {
        $params['unit'] = $module_data['unit'];
        if (modules_is_unit_macro($params['unit'])) {
            $params['unit'] = '';
        }
    }

    if (empty($params['divisor']) === true) {
        $params['divisor'] = get_data_multiplier($params['unit']);
    }

    if (!$params['array_data_create']) {
        if ($params['baseline']) {
            $array_data = get_baseline_data(
                $agent_module_id,
                $date_array,
                $data_module_graph,
                $params
            );
        } else {
            if ($params['compare'] !== false) {
                $series_suffix = 2;

                $date_array_prev['final_date'] = $date_array['start_date'];
                $date_array_prev['start_date'] = ($date_array['start_date'] - $date_array['period']);
                $date_array_prev['period']     = $date_array['period'];

                if ($params['compare'] === 'overlapped') {
                    $params['flag_overlapped'] = 1;
                } else {
                    $params['flag_overlapped'] = 0;
                }

                $array_data = grafico_modulo_sparse_data(
                    $agent_module_id,
                    $date_array_prev,
                    $data_module_graph,
                    $params,
                    $series_suffix
                );

                switch ($params['compare']) {
                    case 'separated':
                    case 'overlapped':
                        // Store the chart calculated.
                        $array_data_prev = $array_data;
                        $legend_prev     = $legend;
                    break;

                    default:
                        // Not defined.
                    break;
                }
            }

            $series_suffix = 1;
            $params['flag_overlapped'] = 0;

            $array_data = grafico_modulo_sparse_data(
                $agent_module_id,
                $date_array,
                $data_module_graph,
                $params,
                $series_suffix
            );

            if ($params['compare']) {
                if ($params['compare'] === 'overlapped') {
                    $array_data = array_merge($array_data, $array_data_prev);
                    $legend     = array_merge($legend, $legend_prev);
                }
            }
        }
    } else {
        $array_data = $params['array_data_create'];
    }

    if ($params['return_data']) {
        return $array_data;
    }

    $series_type_array = series_type_graph_array(
        $array_data,
        $params
    );

    $series_type = $series_type_array['series_type'];
    $legend      = $series_type_array['legend'];
    $color       = $series_type_array['color'];

    if ($config['fixed_graph'] == false) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url(
                '/images/logo_vertical_water.png',
                false,
                false,
                false
            ),
        ];
    }

    $data_module_graph['series_suffix'] = $series_suffix;

    // Check available data.
    if ($params['compare'] === 'separated') {
        if (empty($array_data) === false) {
            $return = area_graph(
                $agent_module_id,
                $array_data,
                $legend,
                $series_type,
                $color,
                $date_array,
                $data_module_graph,
                $params,
                $water_mark,
                $array_events_alerts
            );
        } else {
            $return = graph_nodata_image($params);
        }

        $return .= '<br>';
        if (empty($array_data_prev) === false) {
            $series_type_array = series_type_graph_array(
                $array_data_prev,
                $params
            );

            $series_type = $series_type_array['series_type'];
            $legend      = $series_type_array['legend'];
            $color       = $series_type_array['color'];

            $return .= area_graph(
                $agent_module_id,
                $array_data_prev,
                $legend,
                $series_type,
                $color,
                $date_array_prev,
                $data_module_graph,
                $params,
                $water_mark,
                $array_events_alerts
            );
        } else {
            $return = graph_nodata_image($params);
        }
    } else {
        if (empty($array_data) === false) {
            $return = area_graph(
                $agent_module_id,
                $array_data,
                $legend,
                $series_type,
                $color,
                $date_array,
                $data_module_graph,
                $params,
                $water_mark,
                $array_events_alerts
            );
        } else {
            $return = graph_nodata_image($params);
        }
    }

    return $return;
}


/**
 * Functions tu create graphs.
 *
 * @param array $module_list     Array modules.
 * @param array $params          Details builds graphs. For example:
 * 'period'              => $period,
 * 'show_events'         => false,
 * 'width'               => $width,
 * 'height'              => $height,
 * 'title'               => '',
 * 'unit_name'           => null,
 * 'show_alerts'         => false,
 * 'date'                => 0,
 * 'unit'                => '',
 * 'only_image'          => false,
 * 'homeurl'             => '',
 * 'ttl'                 => 1,
 * 'percentil'           => null,
 * 'dashboard'           => false,
 * 'vconsole'            => false,
 * 'fullscale'           => false,
 * 'id_widget_dashboard' => false.
 * @param array $params_combined Details builds graphs. For example:
 * 'weight_list'    => array(),
 * 'stacked'        => 0,
 * 'projection'     => false,
 * 'labels'         => array(),
 * 'from_interface' => false,
 * 'summatory'      => 0,
 * 'average'        => 0,
 * 'modules_series' => 0,
 * 'id_graph'       => 0,
 * 'return'         => 1.
 *
 * @return string html Content graphs.
 */
function graphic_combined_module(
    $module_list,
    $params,
    $params_combined
) {
    global $config;

    if (isset($params_combined['from_interface']) === false) {
        $params_combined['from_interface'] = false;
    }

    if (isset($params_combined['stacked']) === false) {
        if ($params_combined['from_interface']) {
            if ($config['type_interface_charts'] == 'line') {
                $params_combined['stacked'] = CUSTOM_GRAPH_LINE;
            } else {
                $params_combined['stacked'] = CUSTOM_GRAPH_AREA;
            }
        } else {
            if ($params_combined['id_graph'] == 0) {
                $params_combined['stacked'] = CUSTOM_GRAPH_AREA;
            } else {
                $params_combined['stacked'] = db_get_row(
                    'tgraph',
                    'id_graph',
                    $params_combined['id_graph']
                );
            }
        }
    }

    $params['stacked'] = $params_combined['stacked'];

    if (isset($params_combined['projection']) === false
        || $params_combined['projection'] == false
    ) {
        $params_combined['projection'] = false;
    } else {
        $params['stacked'] = 'area';
        $params['projection'] = true;
    }

    if (isset($params_combined['labels']) === false) {
        $params_combined['labels'] = [];
    }

    if (isset($params_combined['summatory']) === false) {
        $params_combined['summatory'] = 0;
    }

    if (isset($params_combined['average']) === false) {
        $params_combined['average'] = 0;
    }

    if (isset($params_combined['modules_series']) === false) {
        $params_combined['modules_series'] = 0;
    }

    if (isset($params_combined['return']) === false) {
        $params_combined['return'] = 1;
    }

    if (isset($params_combined['id_graph']) === false) {
        $params_combined['id_graph'] = 0;
    }

    if (isset($params_combined['type_report']) === false) {
        $params_combined['type_report'] = '';
    }

    if (isset($params['percentil']) === false) {
        $params_combined['percentil'] = null;
    } else {
        $params_combined['percentil'] = $params['percentil'];
    }

    if (isset($params['period']) === false) {
        return false;
    }

    if (isset($params['width']) === false) {
        $params['width'] = '90%';
    }

    if (isset($params['height']) === false) {
        $params['height'] = 450;
    }

    if (isset($params['title']) === false) {
        $params['title'] = '';
    }

    if (isset($params['unit_name']) === false) {
        $params['unit_name'] = null;
    }

    if (isset($params['show_alerts']) === false) {
        $params['show_alerts'] = false;
    }

    if (isset($params['date']) === false || !$params['date']) {
        $params['date'] = get_system_time();
    }

    if (isset($params['only_image']) === false) {
        $params['only_image'] = false;
    }

    if (isset($params['ttl']) === false) {
        $params['ttl'] = 1;
    }

    if (isset($params['backgroundColor']) === false) {
        $params['backgroundColor'] = 'white';
    }

    if (isset($params['dashboard']) === false) {
        $params['dashboard'] = false;
    }

    if (isset($params['menu']) === false
        || $params['only_image']
    ) {
        $params['menu'] = true;
    } else {
        $params['menu'] = false;
    }

    if (isset($params['vconsole']) === false
        || $params['vconsole'] == false
    ) {
        $params['vconsole'] = false;
    } else {
        $params['menu'] = false;
    }

    if (isset($params['type_graph']) === false) {
        $params['type_graph'] = $config['type_module_charts'];
    }

    if (isset($params['percentil']) === false) {
        $params['percentil'] = null;
    }

    if (isset($params['fullscale']) === false) {
        $params['fullscale'] = false;
    }

    if (isset($params['id_widget_dashboard']) === false) {
        $params['id_widget_dashboard'] = false;
    }

    if (isset($params['homeurl']) === false) {
        $params['homeurl'] = ui_get_full_url(false, false, false, false);
    }

    if (isset($params['show_legend']) === false) {
        $params['show_legend'] = true;
    }

    if (isset($params['show_overview']) === false) {
        $params['show_overview'] = true;
    }

    if (isset($params['show_export_csv']) === false) {
        $params['show_export_csv'] = true;
    }

    if (isset($params['return_img_base_64']) === false) {
        $params['return_img_base_64'] = false;
    }

    if (isset($params['image_threshold']) === false) {
        $params['image_threshold'] = false;
    }

    if (isset($params['show_unknown']) === false) {
        $params['show_unknown'] = false;
    }

    if (isset($params['type_mode_graph']) === false) {
        $params['type_mode_graph'] = 0;
        if (isset($params['graph_render']) === true) {
            $params['type_mode_graph'] = $params['graph_render'];
            $params_combined['type_mode_graph'] = $params['graph_render'];
        }
    }

    if (isset($params['fullscale']) === false) {
        $params_combined['fullscale'] = false;
    } else {
        $params_combined['fullscale'] = $params['fullscale'];
    }

    if (isset($params['maximum_y_axis']) === false) {
        $params['maximum_y_axis'] = $config['maximum_y_axis'];
    }

    $params['graph_combined'] = true;
    $params_combined['graph_combined'] = true;

    if ($params['only_image']) {
        return generator_chart_to_pdf(
            'combined',
            $params,
            $params_combined,
            $module_list
        );
    }

    if (isset($params['zoom']) === false) {
        $params['zoom'] = 1;
    }

    $params['grid_color']   = '#C1C1C1';
    $params['legend_color'] = '#636363';

    $params['font'] = 'lato';
    $params['font_size'] = $config['font_size'];

    $params['short_data'] = $config['short_module_graph_data'];

    global $config;
    global $graphic_type;

    $sources = false;

    if ((int) $params_combined['id_graph'] === 0) {
        $count_modules = count($module_list);

        if (!$params_combined['weight_list']) {
            $weights = array_fill(0, $count_modules, 1);
        }

        if ($count_modules > 0) {
            foreach ($module_list as $key => $value) {
                $sources[$key]['id_server'] = (isset($value['id_server']) === true) ? $value['id_server'] : $params['server_id'];
                $sources[$key]['id_agent_module'] = (isset($value['module']) === true) ? $value['module'] : $value;
                $sources[$key]['weight'] = $weights[$key];
                $sources[$key]['label'] = $params_combined['labels'];
            }
        }
    } else {
        if (is_metaconsole()) {
            metaconsole_restore_db();
            $server  = metaconsole_get_connection_by_id($params['server_id']);
            if (metaconsole_connect($server) != NOERR) {
                return false;
            }
        }

        $sources = db_get_all_rows_field_filter(
            'tgraph_source',
            'id_graph',
            $params_combined['id_graph'],
            'field_order'
        );

        if (is_metaconsole()) {
            if (isset($sources) && is_array($sources)) {
                foreach ($sources as $key => $value) {
                    $sources[$key]['id_server'] = $params['server_id'];
                }
            }
        }

        $series = db_get_all_rows_sql(
            'SELECT summatory_series,
                average_series,
                modules_series
            FROM tgraph
            WHERE id_graph = '.$params_combined['id_graph']
        );

        $summatory      = $series[0]['summatory_series'];
        $average        = $series[0]['average_series'];
        $modules_series = $series[0]['modules_series'];

        if (is_metaconsole()) {
            metaconsole_restore_db();
        }
    }

    if (isset($sources) === true && is_array($sources) === true) {
        $weights = [];
        $labels  = [];
        $modules = [];
        foreach ($sources as $source) {
            if (is_metaconsole() === true) {
                metaconsole_restore_db();
                $server = metaconsole_get_connection_by_id($source['id_server']);
                if (metaconsole_connect($server) != NOERR) {
                    continue;
                }
            }

            $id_agent = agents_get_module_id(
                $source['id_agent_module']
            );

            if (!$id_agent) {
                continue;
            }

            $modulepush = [
                'server' => (isset($source['id_server']) === true) ? $source['id_server'] : 0,
                'module' => $source['id_agent_module'],
            ];

            array_push($modules, $modulepush);
            array_push($weights, $source['weight']);
            if (empty($source['label']) === false || $params_combined['labels']) {
                $agent_description = agents_get_description($id_agent);
                $agent_group = agents_get_agent_group($id_agent);
                $agent_address = agents_get_address($id_agent);
                $agent_alias = agents_get_alias($id_agent);
                $module_name = modules_get_agentmodule_name(
                    $source['id_agent_module']
                );

                $module_description = modules_get_agentmodule_descripcion(
                    $source['id_agent_module']
                );

                $items_label = [
                    'type'               => 'custom_graph',
                    'id_agent'           => $id_agent,
                    'id_agent_module'    => $source['id_agent_module'],
                    'agent_description'  => $agent_description,
                    'agent_group'        => $agent_group,
                    'agent_address'      => $agent_address,
                    'agent_alias'        => $agent_alias,
                    'module_name'        => $module_name,
                    'module_description' => $module_description,
                ];

                if (is_array($source['label']) === true) {
                    $lab = '';
                    foreach ($source['label'] as $label) {
                        $lab .= reporting_label_macro(
                            $items_label,
                            ($label ?? '')
                        );
                    }
                } else if ($source['label'] != '') {
                    $lab = reporting_label_macro(
                        $items_label,
                        ($source['label'] ?? '')
                    );
                } else {
                    $lab = reporting_label_macro(
                        $items_label,
                        ($params_combined['labels'] ?? '')
                    );
                }

                $labels[$source['id_agent_module']] = $lab;
            }

            if (is_metaconsole() === true) {
                metaconsole_restore_db();
            }
        }
    }

    if ((bool) $params_combined['from_interface'] === true) {
        $labels  = [];
    }

    if ($module_list) {
        $params_combined['modules_id'] = $module_list;
    } else {
        $params_combined['modules_id'] = $modules;
    }

    if (isset($summatory) === true) {
        $params_combined['summatory'] = $summatory;
    }

    if (isset($average) === true) {
        $params_combined['average'] = $average;
    }

    if (isset($modules_series) === true) {
        $params_combined['modules_series'] = $modules_series;
    }

    if (isset($labels) === true) {
        $params_combined['labels'] = $labels;
    }

    if (isset($weights) === true) {
        $params_combined['weight_list'] = $weights;
    }

    if (!$module_list) {
        $module_list = $modules;
    }

    if ($sources === false) {
        if ($params_combined['return']) {
            return false;
        } else {
            ui_print_info_message(
                [
                    'no_close' => true,
                    'message'  => __('No items.'),
                ]
            );
            return;
        }
    }

    $width = $params['width'];
    $height = $params['height'];
    $homeurl = $params['homeurl'];
    $ttl = $params['ttl'];

    $date_array = [];
    $date_array['period']     = $params['period'];
    $date_array['final_date'] = $params['date'];
    $date_array['start_date'] = ($params['date'] - $params['period']);

    $background_color = $params['backgroundColor'];
    $datelimit = $date_array['start_date'];
    $fixed_font_size = $config['font_size'];

    if ($config['fixed_graph'] == false) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url(
                '/images/logo_vertical_water.png',
                false,
                false,
                false
            ),
        ];
    }

    $long_index = '';

    if (($config['style'] === 'pandora_black' && !is_metaconsole()) && ($params['pdf'] === false || $params['pdf'] === null )
    ) {
        $background_color = '#222';
        $params['legend_color'] = '#fff';
    } else if ($params['pdf']) {
        $params['legend_color'] = '#000';
    }

    switch ($params_combined['stacked']) {
        default:
        case CUSTOM_GRAPH_STACKED_LINE:
        case CUSTOM_GRAPH_STACKED_AREA:
        case CUSTOM_GRAPH_AREA:
        case CUSTOM_GRAPH_LINE:
            $date_array = [];
            $date_array['period']     = $params['period'];
            $date_array['final_date'] = $params['date'];
            $date_array['start_date'] = ($params['date'] - $params['period']);

            if (is_metaconsole()) {
                $server_name = metaconsole_get_server_by_id($modules[0]['server']);
            }

            if ($params_combined['projection']) {
                $output_projection = forecast_projection_graph(
                    $module_list[0],
                    $params['period'],
                    $params_combined['projection'],
                    false,
                    false,
                    false,
                    $server_name
                );
            }

            $i = 0;
            $array_data = [];

            foreach ($module_list as $key => $agent_module_id) {
                if ((bool) $agent_module_id === false) {
                    continue;
                }

                // Only 10 item for chart.
                if ($i >= $config['items_combined_charts']) {
                    break;
                }

                if (is_metaconsole()) {
                    metaconsole_restore_db();
                    if (isset($agent_module_id['server'])) {
                        $id_server = $agent_module_id['server'];
                    } else if (isset($agent_module_id['id_server'])) {
                        $id_server = $agent_module_id['id_server'];
                    } else {
                        $id_server = $source['id_server'];
                    }

                    $server = metaconsole_get_connection_by_id($id_server);
                    if (metaconsole_connect($server) != NOERR) {
                        continue;
                    }
                }

                if (is_array($agent_module_id)) {
                     $agent_module_id = $agent_module_id['module'];
                }

                $module_data = db_get_row_sql(
                    'SELECT * FROM tagente_modulo
                    WHERE id_agente_modulo = '.$agent_module_id
                );

                $data_module_graph = [];
                $data_module_graph['history_db'] = db_search_in_history_db(
                    $date_array['start_date']
                );
                $data_module_graph['agent_name'] = modules_get_agentmodule_agent_name(
                    $agent_module_id
                );

                if (is_metaconsole()) {
                    $data_module_graph['agent_alias'] = db_get_value(
                        'alias',
                        'tagente',
                        'id_agente',
                        (int) $module_data['id_agente']
                    );
                } else {
                    $data_module_graph['agent_alias'] = modules_get_agentmodule_agent_alias(
                        $agent_module_id
                    );
                }

                $data_module_graph['agent_id'] = $module_data['id_agente'];
                $data_module_graph['module_name'] = $module_data['nombre'];
                $data_module_graph['id_module_type'] = $module_data['id_tipo_modulo'];
                $data_module_graph['module_type'] = modules_get_moduletype_name(
                    $data_module_graph['id_module_type']
                );
                $data_module_graph['uncompressed'] = is_module_uncompressed(
                    $data_module_graph['module_type']
                );
                $data_module_graph['w_min'] = $module_data['min_warning'];
                $data_module_graph['w_max'] = $module_data['max_warning'];
                $data_module_graph['w_inv'] = $module_data['warning_inverse'];
                $data_module_graph['c_min'] = $module_data['min_critical'];
                $data_module_graph['c_max'] = $module_data['max_critical'];
                $data_module_graph['c_inv'] = $module_data['critical_inverse'];
                $data_module_graph['module_id'] = $agent_module_id;
                $data_module_graph['unit'] = $module_data['unit'];

                $params['unit'] = $module_data['unit'];

                $params['divisor'] = get_data_multiplier($params['unit']);

                // Stract data.
                $array_data_module = grafico_modulo_sparse_data(
                    $agent_module_id,
                    $date_array,
                    $data_module_graph,
                    $params,
                    $i
                );

                $series_suffix = $i;

                // Convert to array graph and weight.
                foreach ($array_data_module as $key => $value) {
                    $array_data[$key] = $value;
                    if ($params_combined['weight_list'][$i] != 1) {
                        foreach ($value['data'] as $k => $v) {
                            if ($v[1] != false) {
                                $array_data[$key]['data'][$k][1] = ($v[1] * $params_combined['weight_list'][$i]);
                                $array_data[$key]['slice_data'][$v[0]]['avg'] *= $params_combined['weight_list'][$i];
                                $array_data[$key]['slice_data'][$v[0]]['min'] *= $params_combined['weight_list'][$i];
                                $array_data[$key]['slice_data'][$v[0]]['max'] *= $params_combined['weight_list'][$i];
                            }
                        }

                        $array_data[$key]['max'] *= $params_combined['weight_list'][$i];
                        $array_data[$key]['min'] *= $params_combined['weight_list'][$i];
                        $array_data[$key]['avg'] *= $params_combined['weight_list'][$i];
                        $array_data[$key]['weight'] = $params_combined['weight_list'][$i];
                    }
                }

                if ($config['fixed_graph'] == false) {
                    $water_mark = [
                        'file' => $config['homedir'].'/images/logo_vertical_water.png',
                        'url'  => ui_get_full_url(
                            'images/logo_vertical_water.png',
                            false,
                            false,
                            false
                        ),
                    ];
                }

                // Work around for fixed the agents name with huge size chars.
                $fixed_font_size = $config['font_size'];

                $i++;

                if (is_metaconsole()) {
                    metaconsole_restore_db();
                }
            }

            if (empty($array_data) === true) {
                if ($params_combined['return']) {
                    return graph_nodata_image($params);
                }

                echo graph_nodata_image($params);
                return false;
            }

            if ($params_combined['projection']) {
                // If projection doesn't have data then don't draw graph.
                if ($output_projection != null) {
                    $date_array_projection = max($output_projection);
                    $date_array['final_date'] = ($date_array_projection[0] / 1000);
                    $array_data['projection']['data'] = $output_projection;
                }
            }

            // Summatory and average series.
            if ($params_combined['stacked'] == CUSTOM_GRAPH_AREA
                || $params_combined['stacked'] == CUSTOM_GRAPH_LINE
            ) {
                if ($params_combined['summatory']
                    || $params_combined['average']
                ) {
                    $array_data = combined_graph_summatory_average(
                        $array_data,
                        $params_combined['average'],
                        $params_combined['summatory'],
                        $params_combined['modules_series'],
                        $date_array
                    );
                }
            }

            $series_type_array = series_type_graph_array(
                $array_data,
                $params_combined
            );

            $series_type = $series_type_array['series_type'];
            $legend      = $series_type_array['legend'];
            $color       = $series_type_array['color'];

            $threshold_data = [];
            if ($params_combined['from_interface']) {
                $yellow_threshold = 0;
                $red_threshold = 0;

                $yellow_up = 0;
                $red_up = 0;

                $yellow_inverse = 0;
                $red_inverse = 0;

                $compare_warning = false;
                $compare_critical = false;

                $do_it_warning_min = true;
                $do_it_critical_min = true;

                $do_it_warning_max = true;
                $do_it_critical_max = true;

                $do_it_warning_inverse = true;
                $do_it_critical_inverse = true;

                foreach ($module_list as $index => $id_module) {
                    // Get module warning_min and critical_min.
                    $warning_min  = db_get_value(
                        'min_warning',
                        'tagente_modulo',
                        'id_agente_modulo',
                        $id_module
                    );
                    $critical_min = db_get_value(
                        'min_critical',
                        'tagente_modulo',
                        'id_agente_modulo',
                        $id_module
                    );

                    if ($index == 0) {
                        $compare_warning = $warning_min;
                    } else {
                        if ($compare_warning != $warning_min) {
                            $do_it_warning_min = false;
                        }
                    }

                    if ($index == 0) {
                        $compare_critical = $critical_min;
                    } else {
                        if ($compare_critical != $critical_min) {
                            $do_it_critical_min = false;
                        }
                    }
                }

                if ($do_it_warning_min || $do_it_critical_min) {
                    foreach ($module_list as $index => $id_module) {
                        $warning_max  = db_get_value(
                            'max_warning',
                            'tagente_modulo',
                            'id_agente_modulo',
                            $id_module
                        );
                        $critical_max = db_get_value(
                            'max_critical',
                            'tagente_modulo',
                            'id_agente_modulo',
                            $id_module
                        );

                        if ($index == 0) {
                            $yellow_up = $warning_max;
                        } else {
                            if ($yellow_up != $warning_max) {
                                $do_it_warning_max = false;
                            }
                        }

                        if ($index == 0) {
                            $red_up = $critical_max;
                        } else {
                            if ($red_up != $critical_max) {
                                $do_it_critical_max = false;
                            }
                        }
                    }
                }

                if ($do_it_warning_min || $do_it_critical_min) {
                    foreach ($module_list as $index => $id_module) {
                        $warning_inverse  = db_get_value(
                            'warning_inverse',
                            'tagente_modulo',
                            'id_agente_modulo',
                            $id_module
                        );
                        $critical_inverse = db_get_value(
                            'critical_inverse',
                            'tagente_modulo',
                            'id_agente_modulo',
                            $id_module
                        );

                        if ($index == 0) {
                            $yellow_inverse = $warning_inverse;
                        } else {
                            if ($yellow_inverse != $warning_inverse) {
                                $do_it_warning_inverse = false;
                            }
                        }

                        if ($index == 0) {
                            $red_inverse = $critical_inverse;
                        } else {
                            if ($red_inverse != $critical_inverse) {
                                $do_it_critical_inverse = false;
                            }
                        }
                    }
                }

                if ($do_it_warning_min
                    && $do_it_warning_max
                    && $do_it_warning_inverse
                ) {
                    $yellow_threshold = $compare_warning;
                    $threshold_data['yellow_threshold'] = $compare_warning;
                    $threshold_data['yellow_up'] = $yellow_up;
                    $threshold_data['yellow_inverse'] = (bool) $yellow_inverse;
                }

                if ($do_it_critical_min
                    && $do_it_critical_max
                    && $do_it_critical_inverse
                ) {
                    $red_threshold = $compare_critical;
                    $threshold_data['red_threshold'] = $compare_critical;
                    $threshold_data['red_up'] = $red_up;
                    $threshold_data['red_inverse'] = (bool) $red_inverse;
                }

                $params['threshold_data'] = $threshold_data;
            }

            if ($params['vconsole'] === true) {
                $water_mark = false;
            }

            $output = area_graph(
                $agent_module_id,
                $array_data,
                $legend,
                $series_type,
                $color,
                $date_array,
                $data_module_graph,
                $params,
                $water_mark,
                $array_events_alerts
            );
        break;
        case CUSTOM_GRAPH_BULLET_CHART_THRESHOLD:
        case CUSTOM_GRAPH_BULLET_CHART:
            $number_elements = count($module_list);

            if ($params_combined['stacked'] == CUSTOM_GRAPH_BULLET_CHART_THRESHOLD) {
                $acumulador = 0;
                foreach ($module_list as $module_item) {
                    $module = $module_item;
                    $query_last_value = sprintf(
                        '
                        SELECT datos
                        FROM tagente_datos
                        WHERE id_agente_modulo = %d
                            AND utimestamp < %d
                            ORDER BY utimestamp DESC',
                        $module,
                        $params['date']
                    );
                    $temp_data = db_get_value_sql($query_last_value);
                    if ($acumulador < $temp_data) {
                        $acumulador = $temp_data;
                    }
                }
            }

            foreach ($module_list as $module_item) {
                if (is_metaconsole() === true) {
                    // Automatic custom graph from the report
                    // template in metaconsole.
                    $server = metaconsole_get_connection_by_id(
                        $module_item['server']
                    );

                    metaconsole_connect($server);
                }

                $module = $module_item['module'];
                $search_in_history_db = db_search_in_history_db($datelimit);

                $temp[$module] = io_safe_output(
                    modules_get_agentmodule($module)
                );

                $query_last_value = sprintf(
                    '
                    SELECT datos
                    FROM tagente_datos
                    WHERE id_agente_modulo = %d
                        AND utimestamp < %d
                        ORDER BY utimestamp DESC',
                    $module,
                    $params['date']
                );
                $temp_data = db_get_value_sql($query_last_value);

                if ($temp_data) {
                    if (is_numeric($temp_data)) {
                        $value = $temp_data;
                    } else {
                        $value = count($value);
                    }
                } else {
                    $value = false;
                }

                if (!empty($params_combined['labels'])
                    && isset($params_combined['labels'][$module])
                ) {
                    $label = io_safe_output($params_combined['labels'][$module]);
                } else {
                    $alias = db_get_value(
                        'alias',
                        'tagente',
                        'id_agente',
                        $temp[$module]['id_agente']
                    );
                    $label = $alias.': '.$temp[$module]['nombre'];
                }

                $temp[$module]['label'] = $label;
                $temp[$module]['value'] = $value;
                $temp_max = reporting_get_agentmodule_data_max(
                    $module,
                    $params['period'],
                    $params['date']
                );
                if ($temp_max < 0) {
                    $temp_max = 0;
                }

                if (isset($acumulador)) {
                    $temp[$module]['max'] = $acumulador;
                } else {
                    $temp[$module]['max'] = ($temp_max === false) ? 0 : $temp_max;
                }

                $temp_min = reporting_get_agentmodule_data_min(
                    $module,
                    $params['period'],
                    $params['date']
                );
                if ($temp_min < 0) {
                    $temp_min = 0;
                }

                $temp[$module]['min'] = ($temp_min === false) ? 0 : $temp_min;

                if (is_metaconsole() === true) {
                    metaconsole_restore_db();
                }
            }

            $graph_values = $temp;

            if (!$params['vconsole']) {
                $width = 1024;
                $height = 50;
            } else {
                $height = ($height / $number_elements);
                $water_mark = false;
            }

            $color = color_graph_array();

            $output = stacked_bullet_chart(
                $graph_values,
                $width,
                $height,
                $color,
                [],
                $long_index,
                ui_get_full_url(
                    'images/image_problem_area_small.png',
                    false,
                    false,
                    false
                ),
                '',
                '',
                $water_mark,
                $config['fontpath'],
                ($config['font_size'] + 1),
                '',
                $ttl,
                $homeurl,
                $background_color
            );
        break;

        case CUSTOM_GRAPH_GAUGE:
            $i = 0;
            $number_elements = count($module_list);
            foreach ($module_list as $module_item) {
                if (is_metaconsole() === true) {
                    $server = metaconsole_get_connection_by_id(
                        $module_item['server']
                    );

                    metaconsole_connect($server);
                }

                $module = $module_item['module'];
                $temp[$module] = modules_get_agentmodule($module);
                $query_last_value = sprintf(
                    '
                    SELECT datos
                    FROM tagente_datos
                    WHERE id_agente_modulo = %d
                        AND utimestamp < %d
                        ORDER BY utimestamp DESC',
                    $module,
                    $params['date']
                );
                $temp_data = db_get_value_sql($query_last_value);
                if ($temp_data) {
                    if (is_numeric($temp_data)) {
                        $value = $temp_data;
                    } else {
                        $value = count($value);
                    }
                } else {
                    $value = false;
                }

                $temp[$module]['label'] = ($params_combined['labels'][$module] != '') ? $params_combined['labels'][$module] : $temp[$module]['nombre'];

                $temp[$module]['value'] = $value;
                $temp[$module]['label'] = ui_print_truncate_text(
                    $temp[$module]['label'],
                    'module_small',
                    false,
                    true,
                    false,
                    '..'
                );

                if ($temp[$module]['unit'] == '%') {
                    $temp[$module]['min'] = 0;
                    $temp[$module]['max'] = 100;
                } else {
                    $min = $temp[$module]['min'];
                    if ($temp[$module]['max'] == 0) {
                        $max = reporting_get_agentmodule_data_max(
                            $module,
                            $params['period'],
                            $params['date']
                        );
                    } else {
                        $max = $temp[$module]['max'];
                    }

                    $temp[$module]['min'] = ($min == 0 ) ? 0 : $min;
                    $temp[$module]['max'] = ($max == 0 ) ? 100 : $max;
                }

                $temp[$module]['gauge'] = uniqid('gauge_');

                if (is_metaconsole() === true) {
                    metaconsole_restore_db();
                }

                $i++;
            }

            $graph_values = $temp;

            $color = color_graph_array();

            if ($params['vconsole'] === false) {
                $new_width = 200;
                $new_height = 200;
            } else {
                $ratio = ((200 * ( $height / (200 * $number_elements) )) / (200 * ( $width / (200 * $number_elements))));

                $new_width = ( 200 * ( $width / (200 * $number_elements) ) );
                $new_height = ( 200 * ( $height / (200 * $number_elements) / $ratio ) );

                if ($height > $width) {
                    $new_height = (200 * ($height / (200 * $number_elements)));
                    $new_width = (200 * ($width / (200 * $number_elements)) / $ratio);
                }
            }

            if (isset($params['pdf']) === true && $params['pdf'] === true) {
                $transitionDuration = 0;
            } else {
                $transitionDuration = 500;
            }

            $output = stacked_gauge(
                $graph_values,
                $new_width,
                $new_height,
                $color,
                $module_name_list,
                ui_get_full_url(
                    'images/image_problem_area_small.png',
                    false,
                    false,
                    false
                ),
                $config['fontpath'],
                $fixed_font_size,
                '',
                $homeurl,
                $transitionDuration
            );
        break;

        case CUSTOM_GRAPH_HBARS:
        case CUSTOM_GRAPH_VBARS:
            $label = '';
            $i = 0;
            foreach ($module_list as $module_item) {
                if ($i >= $config['items_combined_charts']) {
                    break;
                }

                if (is_metaconsole() === true) {
                    $server = metaconsole_get_connection_by_id(
                        $module_item['server']
                    );

                    metaconsole_connect($server);
                }

                $module = $module_item['module'];
                $module_data = modules_get_agentmodule($module);
                $query_last_value = sprintf(
                    'SELECT datos
                    FROM tagente_datos
                    WHERE id_agente_modulo = %d
                        AND utimestamp < %d
                        ORDER BY utimestamp DESC',
                    $module,
                    $params['date']
                );
                $temp_data = db_get_value_sql($query_last_value);

                if (empty($params_combined['labels']) === false
                    && isset($params_combined['labels'][$module]) === true
                ) {
                    $label = $params_combined['labels'][$module];
                } else {
                    $alias = db_get_value(
                        'alias',
                        'tagente',
                        'id_agente',
                        $module_data['id_agente']
                    );

                    $label = $alias.' - '.$module_data['nombre'];
                }

                $graph_labels[] = io_safe_output($label);
                if ($params_combined['stacked'] == CUSTOM_GRAPH_HBARS) {
                    $graph_values[] = [
                        'y' => io_safe_output($label),
                        'x' => round($temp_data, 4),
                    ];
                } else {
                    $graph_values[] = [
                        'x' => io_safe_output($label),
                        'y' => round($temp_data, 4),
                    ];
                }

                if (is_metaconsole() === true) {
                    metaconsole_restore_db();
                }

                $i++;
            }

            $color = color_graph_array();

            if ($params['vconsole'] === true) {
                $water_mark = '';
            }

            $options = [
                'height'    => $height,
                'waterMark' => $water_mark,
                'ttl'       => $ttl,
                'pdf'       => $params['pdf'],
                'legend'    => ['display' => false],
                'scales'    => [
                    'x' => [
                        'bounds' => 'data',
                        'grid'   => ['display' => false],
                    ],
                    'y' => [
                        'grid' => ['display' => false],
                    ],
                ],
                'labels'    => $graph_labels,
            ];

            if ($params_combined['stacked'] == CUSTOM_GRAPH_HBARS) {
                $options['axis'] = 'y';
            }

            if ((bool) $params['pdf'] === true) {
                $options['dataLabel'] = ['display' => 'auto'];
                if ($params_combined['stacked'] == CUSTOM_GRAPH_HBARS) {
                    $options['layout'] = [
                        'padding' => ['right' => 35],
                    ];
                } else {
                    $options['layout'] = [
                        'padding' => ['top' => 35],
                    ];
                }
            }

            $output = '<div style="display: flex; flex-direction:row; justify-content: center; align-items: center; align-content: center; width:100%; height:100%;">';
            $output .= '<div style="flex: 0 0 auto; width:99%; height:100%; background-color:'.$background_color.'">';
            $output .= vbar_graph($graph_values, $options);
            $output .= '</div>';
            $output .= '</div>';
        break;

        case CUSTOM_GRAPH_PIE:
            $total_modules = 0;
            foreach ($module_list as $module_item) {
                if (is_metaconsole() === true) {
                    $server = metaconsole_get_connection_by_id(
                        $module_item['server']
                    );

                    metaconsole_connect($server);
                }

                $module = $module_item['module'];
                $data_module = modules_get_agentmodule($module);
                $query_last_value = sprintf(
                    'SELECT datos
                    FROM tagente_datos
                    WHERE id_agente_modulo = %d
                        AND utimestamp < %d
                        ORDER BY utimestamp DESC',
                    $module,
                    $params['date']
                );

                $temp_data = db_get_value_sql($query_last_value);
                if ($temp_data !== false) {
                    if (is_numeric($temp_data) === true) {
                        $value = $temp_data;
                    } else {
                        $value = count($value);
                    }
                } else {
                    $value = false;
                }

                $total_modules += $value;

                if (empty($params_combined['labels']) === false
                    && isset($params_combined['labels'][$module]) === true
                ) {
                    $label = io_safe_output(
                        $params_combined['labels'][$module]
                    );
                } else {
                    $alias = db_get_value(
                        'alias',
                        'tagente',
                        'id_agente',
                        $data_module['id_agente']
                    );
                    $label = io_safe_output($alias.': '.$data_module['nombre']);
                }

                if ((bool) $params['pdf'] === true) {
                    $value = (empty($value) === false) ? $value : 0;
                    $label .= ' ('.$value.')';
                }

                $graph_labels[] = io_safe_output($label);
                $graph_values[] = round($temp_data, 4);

                if (is_metaconsole() === true) {
                    metaconsole_restore_db();
                }
            }

            if ($params['vconsole'] === true) {
                $water_mark = false;
            }

            $options = [
                'waterMark' => $water_mark,
                'ttl'       => $ttl,
                'pdf'       => $params['pdf'],
                'legend'    => [
                    'display'  => (bool) $params['show_legend'],
                    'position' => 'right',
                    'align'    => 'center',
                ],
                'labels'    => $graph_labels,
            ];

            if ((bool) $params['pdf'] === true) {
                $options['dataLabel'] = ['display' => 'auto'];
                $options['layout'] = [
                    'padding' => [
                        'top'    => 20,
                        'bottom' => 20,
                    ],
                ];
            }

            $output = '<div style="display: flex; flex-direction:row; justify-content: center; align-items: center; align-content: center; width:100%; height:100%;">';
            $output .= '<div style="flex: 0 0 auto; width:99%; height:100%; background-color:'.$background_color.'">';
            $output .= ring_graph($graph_values, $options);
            $output .= '</div>';
            $output .= '</div>';
        break;
    }

    if ($params_combined['return']) {
        return $output;
    }

    echo $output;
}


/**
 * Function for convert data summatory.
 *
 * @param array   $array_data     Data array.
 * @param boolean $average        Average.
 * @param boolean $summatory      Summatory.
 * @param boolean $modules_series Series module.
 * @param array   $date_array     Date data.
 *
 * @return array Data.
 */
function combined_graph_summatory_average(
    $array_data,
    $average=false,
    $summatory=false,
    $modules_series=false,
    $date_array=[]
) {
    if (isset($array_data) && is_array($array_data)) {
        $reduce_array = [];
        foreach ($array_data as $key => $value) {
            if (strpos($key, 'sum') !== false) {
                $last = $date_array['start_date'];
                $reduce_array = array_reduce(
                    $value['data'],
                    function ($carry, $item) use ($date_array, $last, $reduce_array) {
                        $slice_start = $date_array['start_date'];
                        $iterator = $last;

                        // JS to PHP timestamp format.
                        $item[0] /= 1000;
                        while ($iterator <= $date_array['final_date']) {
                            if ($item[0] >= $slice_start && $item[0] < $iterator) {
                                $array = [];
                                $val = 0;
                                $n = 0;

                                if (is_array($reduce_array[$slice_start])) {
                                    $val = $reduce_array[$slice_start]['value'];
                                    $n = ($reduce_array[$slice_start]['n'] + 1);
                                }

                                $array['value'] = ($item[1] + $val);
                                $array['n'] = $n;
                                $array['t'] = ($slice_start * 1000);

                                $carry[$slice_start] = $array;
                                $last = $iterator;
                                break;
                            } else {
                                $slice_start = $iterator;
                                $iterator += 300;
                            }
                        }

                        $i++;
                        return $carry;
                    },
                    $reduce_array
                );
            }

            if (!$modules_series) {
                unset($array_data[$key]);
            }
        }

        $reduce_array_summatory = [];
        $reduce_array_average = [];
        $i = 0;
        foreach ($reduce_array as $item) {
            $reduce_array_summatory[$i][0] = $item['t'];
            $reduce_array_summatory[$i][1] = $item['value'];

            $reduce_array_average[$i][0] = $item['t'];
            $reduce_array_average[$i][1] = ($item['value'] / ($item['n'] + 1));

            $i++;
        }

        if ($summatory && isset($reduce_array_summatory)
            && is_array($reduce_array_summatory)
            && count($reduce_array_summatory) > 0
        ) {
            $array_data['summatory']['data'] = $reduce_array_summatory;
        }

        if ($average && isset($reduce_array_average)
            && is_array($reduce_array_average)
            && count($reduce_array_average) > 0
        ) {
            $array_data['average']['data'] = $reduce_array_average;
        }

        return $array_data;
    } else {
        return false;
    }
}


/**
 * Print a graph with access data of agents.
 *
 * @param integer      $id_agent Agent Id.
 * @param integer      $period   Timestamp period graph.
 * @param boolean|null $return   Type return.
 *
 * @return string
 */
function graphic_agentaccess(
    int $id_agent,
    int $period=0,
    ?bool $return=false,
    ?bool $agent_view=false
) {
    global $config;

    // Dates.
    $date = get_system_time();
    $datelimit = ($date - $period);
    $interval = 3600;

    // Query.
    $sql = sprintf(
        'SELECT utimestamp, count(*) as data
         FROM tagent_access
         WHERE id_agent = %d
         AND utimestamp >= %d
         AND utimestamp <= %d
         GROUP BY TRUNCATE(utimestamp/%d,0)',
        $id_agent,
        $datelimit,
        $date,
        $interval
    );

    $data = db_get_all_rows_sql($sql);

    // Array data.
    $data_array = [];
    $colors = [];
    if (isset($data) === true && is_array($data) === true) {
        foreach ($data as $value) {
            $time = io_safe_output(date('H:m', $value['utimestamp']));
            $labels[] = $time;
            $data_array[] = [
                'y' => (int) $value['data'],
                'x' => $time,
            ];

            $colors[] = '#82b92f';
        }
    }

    $options = [];
    $options['grid']['hoverable'] = true;

    if ($agent_view === true) {
        $options['agent_view'] = true;
    }

    $options = [
        'height' => 125,
        'colors' => $colors,
        'legend' => ['display' => false],
        'scales' => [
            'x' => [
                'grid'  => ['display' => false],
                'ticks' => [
                    'fonts' => ['size' => 8],
                ],
            ],
            'y' => [
                'grid'  => ['display' => false],
                'ticks' => [
                    'fonts' => ['size' => 8],
                ],
            ],
        ],
        'labels' => $labels,
    ];

    return vbar_graph($data_array, $options);
}


/**
 * Print a pie graph with alerts defined/fired data
 *
 * @param integer Number of defined alerts
 * @param integer Number of fired alerts
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param bool return or echo flag
 */
function graph_alert_status($defined_alerts, $fired_alerts, $width=300, $height=200, $return=false)
{
    global $config;

    $labels = [
        __('Not fired alerts'),
        __('Fired alerts'),
    ];
    $data = [
        ($defined_alerts - $fired_alerts),
        $fired_alerts,
    ];
    $colors = [
        COL_NORMAL,
        COL_ALERTFIRED,
    ];

    $options = [
        'width'  => $width,
        'height' => $height,
        'colors' => $colors,
        'legend' => ['display' => false],
        'labels' => $labels,
    ];

    $out = pie_graph(
        $data,
        $options
    );

    if ($return === true) {
        return $out;
    } else {
        echo $out;
    }
}


// If any value is negative, truncate it to 0
function truncate_negatives(&$element)
{
    if ($element < 0) {
        $element = 0;
    }
}


/**
 * Print a pie graph with events
 * data of agent or all agents (if id_agent = false).
 *
 * @param integer $id_agent           Agent ID.
 * @param integer $width              Pie graph width.
 * @param integer $height             Pie graph height.
 * @param boolean $return             Flag.
 * @param boolean $show_not_init      Flag.
 * @param array   $data_agents        Data.
 * @param boolean $donut_narrow_graph Flag type graph.
 *
 * @return string Html chart.
 */
function graph_agent_status(
    $id_agent=false,
    $width=300,
    $height=200,
    $return=false,
    $show_not_init=false,
    $data_agents=false,
    $donut_narrow_graph=false
) {
    global $config;

    if ($data_agents == false) {
        $groups = implode(
            ',',
            array_keys(users_get_groups(false, 'AR', false))
        );
        $p_table = 'tagente';
        $s_table = 'tagent_secondary_group';
        if (is_metaconsole()) {
            $p_table = 'tmetaconsole_agent';
            $s_table = 'tmetaconsole_agent_secondary_group';
        }

        $sql = sprintf(
            'SELECT SUM(critical_count) AS Critical,
                SUM(warning_count) AS Warning,
                SUM(normal_count) AS Normal,
                SUM(unknown_count) AS Unknown
                %s
            FROM %s ta LEFT JOIN %s tasg
                ON ta.id_agente = tasg.id_agent
            WHERE
                ta.disabled = 0 AND
                %s
                (ta.id_grupo IN (%s) OR tasg.id_group IN (%s))',
            $show_not_init ? ', SUM(notinit_count) "Not init"' : '',
            $p_table,
            $s_table,
            (empty($id_agent) === true) ? '' : 'ta.id_agente = '.$id_agent.' AND',
            $groups,
            $groups
        );

        $data = db_get_row_sql($sql);
    } else {
        $data = $data_agents;
    }

    if (empty($data) === true) {
        $data = [];
    }

    array_walk($data, 'truncate_negatives');

    if ($config['fixed_graph'] == false) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url(
                'images/logo_vertical_water.png',
                false,
                false,
                false
            ),
        ];
    }

    $colors['Critical'] = COL_CRITICAL;
    $colors['Warning'] = COL_WARNING;
    $colors['Normal'] = COL_NORMAL;
    $colors['Unknown'] = COL_UNKNOWN;

    if ($show_not_init) {
        $colors['Not init'] = COL_NOTINIT;
    }

    if (array_sum($data) == 0) {
        $data = [];
    }

    $options = [
        'width'  => $width,
        'height' => $height,
        'colors' => array_values($colors),
        'legend' => ['display' => false],
    ];

    if ($donut_narrow_graph == true) {
        $out = ring_graph(
            $data,
            $options
        );
        return $out;
    } else {
        $out = pie_graph(
            $data,
            $options
        );

        if ($return) {
            return $out;
        } else {
            echo $out;
        }
    }
}


function progress_bar($progress, $width, $height, $title='', $mode=1, $value_text=false, $color=false, $options=false)
{
    global $config;

    $out_of_lim_str = io_safe_output(__('Out of limits'));

    $title = '';

    if ($value_text === false) {
        $value_text = $progress.'%';
    }

    $colorRGB = '';
    if ($color !== false) {
        $colorRGB = html_html2rgb($color);
        $colorRGB = implode('|', $colorRGB);
    }

    $class_tag = '';
    $id_tag = '';
    if ($options !== false) {
        foreach ($options as $option_type => $option_value) {
            if ($option_type == 'class') {
                $class_tag = ' class="'.$option_value.'" ';
            } else if ($option_type == 'id') {
                $id_tag = ' id="'.$option_value.'" ';
            }
        }
    }

    include_once 'include_graph_dependencies.php';
    include_graphs_dependencies($config['homedir'].'/');
    $src = ui_get_full_url(
        '/include/graphs/fgraph.php?graph_type=progressbar'.'&width='.$width.'&height='.$height.'&progress='.$progress.'&mode='.$mode.'&out_of_lim_str='.$out_of_lim_str.'&title='.$title.'&value_text='.$value_text.'&colorRGB='.$colorRGB,
        false,
        false,
        false
    );

    return "<img title='".$title."' alt='".$title."'".$class_tag.$id_tag." src='".$src."' />";
}


function progress_bubble($progress, $width, $height, $title='', $mode=1, $value_text=false, $color=false)
{
    global $config;

    $hack_metaconsole = '';
    if (defined('METACONSOLE')) {
        $hack_metaconsole = '../../';
    }

    $out_of_lim_str = io_safe_output(__('Out of limits'));
    $title = '';

    if ($value_text === false) {
        $value_text = $progress.'%';
    }

    $colorRGB = '';
    if ($color !== false) {
        $colorRGB = html_html2rgb($color);
        $colorRGB = implode('|', $colorRGB);
    }

    include_once 'include_graph_dependencies.php';
    include_graphs_dependencies($config['homedir'].'/');

    return "<img title='".$title."' alt='".$title."'"." src='".$config['homeurl'].$hack_metaconsole.'/include/graphs/fgraph.php?graph_type=progressbubble'.'&width='.$width.'&height='.$height.'&progress='.$progress.'&mode='.$mode.'&out_of_lim_str='.$out_of_lim_str.'&title='.$title.'&value_text='.$value_text.'&colorRGB='.$colorRGB."' />";
}


function graph_sla_slicebar(
    $id,
    $period,
    $sla_min,
    $sla_max,
    $date,
    $daysWeek,
    $time_from,
    $time_to,
    $width,
    $height,
    $home_url,
    $ttl=1,
    $data=false,
    $round_corner=null
) {
    global $config;

    if ($round_corner === null) {
        $round_corner = $config['round_corner'];
    }

    $col_planned_downtime = '#20973F';

    $colors = [
        1 => COL_NORMAL,
        2 => COL_WARNING,
        3 => COL_CRITICAL,
        4 => COL_UNKNOWN,
        5 => COL_DOWNTIME,
        6 => COL_NOTINIT,
        7 => COL_IGNORED,
    ];

    return $return['chart'] = flot_slicesbar_graph(
        $data,
        $period,
        $width,
        $height,
        '',
        $colors,
        $config['fontpath'],
        $round_corner,
        $home_url,
        '',
        '',
        false,
        0,
        [],
        true,
        $ttl,
        false,
        true,
        $date
    );
}


/**
 * Print a pie graph with priodity incident
 */
function grafico_incidente_prioridad()
{
    global $config;

    $integria_ticket_count_by_priority_json = integria_api_call(null, null, null, null, 'get_tickets_count', ['prioridad', 30], false, '', '|;|');

    $integria_priorities_map_json = integria_api_call(null, null, null, null, 'get_incident_priorities', '', false, 'json');

    $integria_ticket_count_by_priority = json_decode($integria_ticket_count_by_priority_json, true);
    $integria_priorities_map = json_decode($integria_priorities_map_json, true);

    $integria_priorities_map_ids = array_column($integria_priorities_map, 'id');
    $integria_priorities_map_names = array_column($integria_priorities_map, 'name');
    $integria_priorities_map_indexed_by_id = array_combine($integria_priorities_map_ids, $integria_priorities_map_names);

    $data = [];
    $labels = [];
    foreach ($integria_ticket_count_by_priority as $item) {
        $priority_name = $integria_priorities_map_indexed_by_id[$item['prioridad']];
        $labels[] = io_safe_output($priority_name);
        $data[] = $item['n_incidents'];
    }

    if ($config['fixed_graph'] == false) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url('images/logo_vertical_water.png', false, false, false),
        ];
    }

    $options = [
        'width'     => 320,
        'height'    => 200,
        'waterMark' => $water_mark,
        'legend'    => [
            'display'  => true,
            'position' => 'right',
            'align'    => 'center',
        ],
        'labels'    => $labels,
    ];

    $output = '<div style="width:inherit;margin: 0 auto;">';
    $output .= pie_graph(
        $data,
        $options
    );
    $output .= '</div>';

    return $output;
}


/**
 * Print a pie graph with incidents data
 */
function graph_incidents_status()
{
    global $config;

    $integria_ticket_count_by_status_json = integria_api_call(null, null, null, null, 'get_tickets_count', ['estado', 30], false, '', '|;|');

    $integria_status_map_json = integria_api_call(null, null, null, null, 'get_incidents_status', '', false, 'json');

    $integria_ticket_count_by_status = json_decode($integria_ticket_count_by_status_json, true);
    $integria_status_map = json_decode($integria_status_map_json, true);

    $integria_status_map_ids = array_column($integria_status_map, 'id');
    $integria_status_map_names = array_column($integria_status_map, 'name');
    $integria_status_map_indexed_by_id = array_combine($integria_status_map_ids, $integria_status_map_names);

    $data = [];
    $labels = [];
    foreach ($integria_ticket_count_by_status as $item) {
        $status_name = $integria_status_map_indexed_by_id[$item['estado']];
        $labels[] = io_safe_output($status_name);
        $data[] = $item['n_incidents'];
    }

    if ($config['fixed_graph'] == false) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url('images/logo_vertical_water.png', false, false, false),
        ];
    }

    $options = [
        'width'     => 320,
        'height'    => 200,
        'waterMark' => $water_mark,
        'legend'    => [
            'display'  => true,
            'position' => 'right',
            'align'    => 'center',
        ],
        'labels'    => $labels,
    ];

    $output = '<div style="width:inherit;margin: 0 auto;">';
    $output .= pie_graph(
        $data,
        $options
    );
    $output .= '</div>';

    return $output;
}


/**
 * Print a pie graph with incident data by group
 */
function graphic_incident_group()
{
    global $config;

    $integria_ticket_count_by_group_json = integria_api_call(null, null, null, null, 'get_tickets_count', ['id_grupo', 30], false, '', '|;|');

    $integria_group_map_json = integria_api_call(null, null, null, null, 'get_groups', '', false, 'json');

    $integria_ticket_count_by_group = json_decode($integria_ticket_count_by_group_json, true);
    $integria_group_map = json_decode($integria_group_map_json, true);

    $data = [];
    $labels = [];
    foreach ($integria_ticket_count_by_group as $item) {
        $group_name = $integria_group_map[$item['id_grupo']];
        $labels[] = io_safe_output($group_name);
        $data[] = $item['n_incidents'];
    }

    if ($config['fixed_graph'] == false) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url('images/logo_vertical_water.png', false, false, false),
        ];
    }

    $options = [
        'width'     => 320,
        'height'    => 200,
        'waterMark' => $water_mark,
        'legend'    => [
            'display'  => true,
            'position' => 'right',
            'align'    => 'center',
        ],
        'labels'    => $labels,
    ];

    $output = '<div style="width:inherit;margin: 0 auto;">';
    $output .= pie_graph(
        $data,
        $options
    );
    $output .= '</div>';

    return $output;
}


/**
 * Print a graph with access data of agents
 *
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 */
function graphic_incident_user()
{
    global $config;

    $integria_ticket_count_by_user_json = integria_api_call(null, null, null, null, 'get_tickets_count', ['id_usuario', 30], false, '', '|;|');

    $integria_ticket_count_by_user = json_decode($integria_ticket_count_by_user_json, true);

    $data = [];
    $labels = [];
    foreach ($integria_ticket_count_by_user as $item) {
        $labels[] = (empty($item['id_usuario']) === false) ? io_safe_output($item['id_usuario']) : '--';
        $data[] = $item['n_incidents'];
    }

    if ($config['fixed_graph'] == false) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url('images/logo_vertical_water.png', false, false, false),
        ];
    }

    $options = [
        'width'     => 320,
        'height'    => 200,
        'waterMark' => $water_mark,
        'legend'    => [
            'display'  => true,
            'position' => 'right',
            'align'    => 'center',
        ],
        'labels'    => $labels,
    ];

    $output = '<div style="width:inherit;margin: 0 auto;">';
    $output .= pie_graph(
        $data,
        $options
    );
    $output .= '</div>';

    return $output;
}


function series_suffix_leyend($series_name, $series_suffix, $id_agent, $data_module_graph, $array_data)
{
        global $config;

        $array_data[$series_name.$series_suffix]['agent_module_id'] = $id_agent;
        $array_data[$series_name.$series_suffix]['id_module_type'] = $data_module_graph['id_module_type'];
        $array_data[$series_name.$series_suffix]['agent_name'] = $data_module_graph['agent_name'];
        $array_data[$series_name.$series_suffix]['module_name'] = $data_module_graph['module_name'];
        $array_data[$series_name.$series_suffix]['agent_alias'] = $data_module_graph['agent_alias'];
        $array_data[$series_name.$series_suffix]['unit'] = $data_module_graph['unit'];

        return $array_data;
}


/**
 * Print a pie graph with events data of group
 *
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param string url
 */
function grafico_eventos_grupo($width=300, $height=200, $url='', $noWaterMark=true, $time_limit=false)
{
    global $config;
    global $graphic_type;

    // It was urlencoded, so we urldecode it.
    $url = html_entity_decode(rawurldecode($url), ENT_QUOTES);
    $data = [];
    $labels = [];
    $loop = 0;
    define('NUM_PIECES_PIE', 6);

    // Hotfix for the id_agente_modulo.
    $url = str_replace(
        'SELECT id_agente_modulo',
        'SELECT_id_agente_modulo',
        $url
    );

    $badstrings = [
        ';',
        'SELECT ',
        'DELETE ',
        'UPDATE ',
        'INSERT ',
        'EXEC',
    ];
    // remove bad strings from the query so queries like ; DELETE FROM  don't pass.
    $url = str_ireplace($badstrings, '', $url);

    // Hotfix for the id_agente_modulo.
    $url = str_replace(
        'SELECT_id_agente_modulo',
        'SELECT id_agente_modulo',
        $url
    );

    // Add tags condition to filter.
    $tags_condition = tags_get_acl_tags(
        $config['id_user'],
        0,
        'ER',
        'event_condition',
        'AND'
    );

    if ($time_limit && $config['event_view_hr']) {
        $tags_condition .= ' AND utimestamp > (UNIX_TIMESTAMP(NOW()) - '.($config['event_view_hr'] * SECONDS_1HOUR).')';
    }

    // This will give the distinct id_agente, give the id_grupo that goes
    // with it and then the number of times it occured. GROUP BY statement
    // is required if both DISTINCT() and COUNT() are in the statement.
    $sql = sprintf(
        'SELECT DISTINCT(id_agente) AS id_agente,
                    COUNT(id_agente) AS count
                FROM tevento te LEFT JOIN tagent_secondary_group tasg
                    ON te.id_grupo = tasg.id_group
                WHERE 1=1 %s %s
                GROUP BY id_agente
                ORDER BY count DESC LIMIT 8',
        $url,
        $tags_condition
    );

    $result = db_get_all_rows_sql($sql, false, false);
    if ($result === false) {
        $result = [];
    }

    $system_events = 0;
    $other_events = 0;

    foreach ($result as $row) {
        $row['id_grupo'] = agents_get_agent_group($row['id_agente']);
        if (!check_acl($config['id_user'], $row['id_grupo'], 'ER') == 1) {
            continue;
        }

        if ($loop >= NUM_PIECES_PIE) {
            $other_events += $row['count'];
        } else {
            if ($row['id_agente'] == 0) {
                $system_events += $row['count'];
            } else {
                $alias = agents_get_alias($row['id_agente']);
                $name = mb_substr($alias, 0, 25).' #'.$row['id_agente'].' ('.$row['count'].')';
                $labels[] = io_safe_output($name);
                $data[] = $row['count'];
            }
        }

        $loop++;
    }

    if ($system_events > 0) {
        $name = __('SYSTEM').' ('.$system_events.')';
        $labels[] = io_safe_output($name);
        $data[] = $system_events;
    }

    // Sort the data.
    arsort($data);
    if ($noWaterMark) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url('images/logo_vertical_water.png', false, false, false),
        ];
    } else {
        $water_mark = [];
    }

    $options = [
        'width'     => $width,
        'height'    => $height,
        'waterMark' => $water_mark,
        'legend'    => [
            'display'  => true,
            'position' => 'right',
            'align'    => 'center',
        ],
        'labels'    => $labels,
    ];

    return pie_graph(
        $data,
        $options
    );
}


/**
 * Print a pie graph with events data in 320x200 size
 *
 * @param string filter Filter for query in DB
 */
function grafico_eventos_total($filter='', $width=320, $height=200, $noWaterMark=true, $time_limit=false)
{
    global $config;
    global $graphic_type;

    $filter = str_replace('\\', '', $filter);

    // Add tags condition to filter.
    $tags_condition = tags_get_acl_tags($config['id_user'], 0, 'ER', 'event_condition', 'AND');
    $filter .= $tags_condition;
    if ($time_limit && $config['event_view_hr']) {
        $filter .= ' AND utimestamp > (UNIX_TIMESTAMP(NOW()) - '.($config['event_view_hr'] * SECONDS_1HOUR).')';
    }

    $data = [];
    $labels = [];

    $where = 'WHERE 1=1';
    if (!users_is_admin()) {
        $where = 'WHERE event_type NOT IN (\'recon_host_detected\', \'system\',\'error\', \'new_agent\', \'configuration_change\')';
    }

    $sql = sprintf(
        'SELECT criticity, COUNT(id_evento) events
        FROM tevento 
        LEFT JOIN tagent_secondary_group tasg 
        ON tevento.id_agente = tasg.id_agent
        %s %s
        GROUP BY criticity ORDER BY events DESC',
        $where,
        $filter
    );
    $criticities = db_get_all_rows_sql($sql, false, false);

    if (empty($criticities) === true) {
        $criticities = [];
        $colors = [];
    }

    foreach ($criticities as $cr) {
        switch ($cr['criticity']) {
            case EVENT_CRIT_MAINTENANCE:
                $labels[] = __('Maintenance').' ('.$cr['events'].')';
                $data[] = $cr['events'];
                $colors[__('Maintenance')] = COL_MAINTENANCE;
            break;

            case EVENT_CRIT_INFORMATIONAL:
                $labels[] = __('Informational').' ('.$cr['events'].')';
                $data[] = $cr['events'];
                $colors[__('Informational')] = COL_INFORMATIONAL;
            break;

            case EVENT_CRIT_NORMAL:
                $labels[] = __('Normal').' ('.$cr['events'].')';
                $data[] = $cr['events'];
                $colors[__('Normal')] = COL_NORMAL;
            break;

            case EVENT_CRIT_MINOR:
                $labels[] = __('Minor').' ('.$cr['events'].')';
                $data[] = $cr['events'];
                $colors[__('Minor')] = COL_MINOR;
            break;

            case EVENT_CRIT_WARNING:
                $labels[] = __('Warning').' ('.$cr['events'].')';
                $data[] = $cr['events'];
                $colors[__('Warning')] = COL_WARNING;
            break;

            case EVENT_CRIT_MAJOR:
                $labels[] = __('Major').' ('.$cr['events'].')';
                $data[] = $cr['events'];
                $colors[__('Major')] = COL_MAJOR;
            break;

            case EVENT_CRIT_CRITICAL:
                $labels[] = __('Critical').' ('.$cr['events'].')';
                $data[] = $cr['events'];
                $colors[__('Critical')] = COL_CRITICAL;
            break;

            default:
                // Not possible.
            break;
        }
    }

    $water_mark = [];
    if ($noWaterMark === true) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url('/images/logo_vertical_water.png', false, false, false),
        ];
    }

    $options = [
        'width'     => $width,
        'height'    => $height,
        'waterMark' => $water_mark,
        'colors'    => array_values($colors),
        'legend'    => [
            'display'  => true,
            'position' => 'right',
            'align'    => 'center',
        ],
        'labels'    => $labels,
    ];

    return pie_graph(
        $data,
        $options
    );
}


/**
 * Undocumented function
 *
 * @param array   $content          ID of report content
 *            used to get SQL code to get information for graph.
 * @param integer $width            Graph width.
 * @param integer $height           Graph height.
 * @param string  $type             Graph type 1 vbar, 2 hbar, 3 pie.
 * @param boolean $only_image       Only image.
 * @param string  $homeurl          Url.
 * @param integer $ttl              Ttl.
 * @param integer $max_num_elements Max elements.
 *
 * @return string Graph.
 */
function graph_custom_sql_graph(
    $content,
    $width,
    $height,
    $type='sql_graph_vbar',
    $only_image=false,
    $homeurl='',
    $ttl=1,
    $max_num_elements=8
) {
    global $config;

    $SQL_GRAPH_MAX_LABEL_SIZE = 20;
    if (is_metaconsole() === true
        && empty($content['server_name']) === false
    ) {
        $connection = metaconsole_get_connection($content['server_name']);
        metaconsole_connect($connection);
    }

    $report_content = db_get_row(
        'treport_content',
        'id_rc',
        $content['id_rc']
    );

    if ($report_content == false || $report_content == '') {
        $report_content = db_get_row(
            'treport_content_template',
            'id_rc',
            $content['id_rc']
        );
    }

    if ($report_content == false || $report_content == '') {
        if (is_metaconsole() === true
            && empty($content['server_name']) === false
        ) {
            enterprise_hook('metaconsole_restore_db');
        }

        $report_content = db_get_row(
            'treport_content',
            'id_rc',
            $content['id_rc']
        );
        if ($report_content == false || $report_content == '') {
            $report_content = db_get_row(
                'treport_content_template',
                'id_rc',
                $content['id_rc']
            );
        }

        if (is_metaconsole() === true
            && empty($content['server_name']) === false
        ) {
            $connection = metaconsole_get_connection($content['server_name']);
            metaconsole_connect($connection);
        }
    }

    $historical_db = $content['historical_db'];
    if ($content['external_source'] != '') {
        $sql = io_safe_output($content['external_source']);
    } else {
        $sql = db_get_row(
            'treport_custom_sql',
            'id',
            $report_content['treport_custom_sql_id']
        );
        $sql = io_safe_output($sql['sql']);
    }

    $data_result = db_get_all_rows_sql($sql, $historical_db);

    if (is_metaconsole() === true && empty($content['server_name']) === false) {
        enterprise_hook('metaconsole_restore_db');
    }

    if ($data_result === false) {
        $data_result = [];
    }

    $data_bar = [];
    $labels_pie = [];
    $data_pie = [];
    $count = 0;
    $other = 0;
    foreach ($data_result as $data_item) {
        $count++;
        $value = 0;
        if (empty($data_item['value']) === false) {
            $value = $data_item['value'];
        }

        if ($count <= $max_num_elements) {
            $label = __('Data');
            if (empty($data_item['label']) === false) {
                $label = io_safe_output($data_item['label']);
                if (strlen($label) > $SQL_GRAPH_MAX_LABEL_SIZE) {
                    $first_label = $label;
                    $label = substr(
                        $first_label,
                        0,
                        floor($SQL_GRAPH_MAX_LABEL_SIZE / 2)
                    );
                }
            }

            $labels_bar[] = $label;
            if ($type === 'sql_graph_hbar') {
                $data_bar[] = [
                    'y' => $label,
                    'x' => $value,
                ];
            } else {
                $data_bar[] = [
                    'x' => $label,
                    'y' => $value,
                ];
            }

            if ((int) $ttl === 2 && $type === 'sql_graph_pie') {
                $labels_pie[] = $label.'_'.$count.' ('.$value.')';
            } else {
                $labels_pie[] = $label.'_'.$count;
            }

            $data_pie[] = $value;
        } else {
            $other += $value;
        }
    }

    if (empty($other) === false) {
        $label = __('Other');
        $labels_bar[] = $label;
        if ($type === 'sql_graph_hbar') {
            $data_bar[] = [
                'y' => $label,
                'x' => $other,
            ];
        } else {
            $data_bar[] = [
                'x' => $label,
                'y' => $other,
            ];
        }

        if ((int) $ttl === 2 && $type === 'sql_graph_pie') {
            $label .= ' ('.$other.')';
        }

        $labels_pie[] = $label;
        $data_pie[] = $other;
    }

    if ($config['fixed_graph'] == false) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url(
                'images/logo_vertical_water.png',
                false,
                false,
                false
            ),
        ];
    }

    $output = '';
    $output .= '<div style="height:'.($height).'px; width: 99%; margin: 0 auto;">';
    if ((int) $ttl === 2) {
        $output .= '<img src="data:image/png;base64,';
    }

    switch ($type) {
        case 'sql_graph_vbar':
        case 'sql_graph_hbar':
        default:
            $options = [
                'height'    => $height,
                'waterMark' => $water_mark,
                'ttl'       => $ttl,
                'legend'    => ['display' => false],
                'scales'    => [
                    'x' => [
                        'grid' => ['display' => false],
                    ],
                    'y' => [
                        'grid' => ['display' => false],
                    ],
                ],
                'labels'    => $labels_bar,
            ];

            if ($type === 'sql_graph_hbar') {
                $options['axis'] = 'y';
            }

            if ((int) $ttl === 2) {
                $options['dataLabel'] = ['display' => 'auto'];
            }

            $output .= vbar_graph(
                $data_bar,
                $options
            );
        break;

        case 'sql_graph_pie':
            $options = [
                'height'    => $height,
                'waterMark' => $water_mark,
                'ttl'       => $ttl,
                'legend'    => [
                    'display'  => true,
                    'position' => 'right',
                    'align'    => 'center',
                ],
                'labels'    => $labels_pie,
            ];

            if ((int) $ttl === 2) {
                $options['dataLabel'] = ['display' => 'auto'];
                $options['layout'] = [
                    'padding' => [
                        'top'    => 20,
                        'bottom' => 20,
                    ],
                ];
            }

            // Pie.
            $output .= pie_graph(
                $data_pie,
                $options
            );
        break;
    }

    if ((int) $ttl === 2) {
        $output .= '" />';
    }

    $output .= '</div>';

    return $output;
}


/**
 * Print a static graph with event data of agents
 *
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 * @param string homeurl
 * @param bool return or echo the result
 */
function graph_graphic_agentevents(
    $id_agent,
    $width,
    $height,
    $period=0,
    $homeurl='',
    $return=false,
    $from_agent_view=false,
    $widgets=false,
    $not_interactive=0,
    $server_id=''
) {
    global $config;
    global $graphic_type;

    $data = [];

    // TODO interval
    $interval = 24;
    $date = get_system_time();
    $datelimit = ($date - $period);
    $periodtime = floor($period / $interval);
    $time = [];
    $data = [];
    $legend = [];
    $full_legend = [];
    $full_legend_date = [];

    $cont = 0;
    for ($i = 0; $i < $interval; $i++) {
        $bottom = ($datelimit + ($periodtime * $i));
        if (! $graphic_type) {
            $name = date('H:i', $bottom);
        } else {
            $name = $bottom;
        }

        // Show less values in legend
        if ($cont == 0 or ($cont % 2)) {
            $legend[$cont] = $name;
        }

        if ($from_agent_view) {
            $full_date = date('Y/m/d', $bottom);
            $full_legend_date[$cont] = $full_date;
        }

        $full_legend[$cont] = $name;

        $top = ($datelimit + ($periodtime * ($i + 1)));

        $events = db_get_all_rows_filter(
            'tevento',
            ['id_agente' => $id_agent,
                'utimestamp > '.$bottom,
                'utimestamp < '.$top,
            ],
            'criticity, utimestamp'
        );

        if (!empty($events)) {
            $data[$cont]['utimestamp'] = $periodtime;
            $event_criticity = array_column($events, 'criticity');
            if (array_search(EVENT_CRIT_CRITICAL, $event_criticity) !== false) {
                $data[$cont]['data'] = EVENT_CRIT_CRITICAL;
            } else if (array_search(EVENT_CRIT_WARNING, $event_criticity) !== false) {
                $data[$cont]['data'] = EVENT_CRIT_WARNING;
            } else {
                $data[$cont]['data'] = EVENT_CRIT_NORMAL;
            }
        } else {
            $data[$cont]['utimestamp'] = $periodtime;
            $data[$cont]['data'] = EVENT_CRIT_NORMAL;
        }

        $cont++;
    }

    $colors = [
        1                   => COL_UNKNOWN,
        EVENT_CRIT_NORMAL   => COL_NORMAL,
        EVENT_CRIT_WARNING  => COL_WARNING,
        EVENT_CRIT_CRITICAL => COL_CRITICAL,
    ];

    // Draw slicebar graph.
    $out = flot_slicesbar_graph(
        $data,
        $period,
        $width,
        $height,
        $full_legend,
        $colors,
        $config['fontpath'],
        $config['round_corner'],
        $homeurl,
        '',
        '',
        false,
        $id_agent,
        $full_legend_date,
        $not_interactive,
        1,
        $widgets,
        true,
        $server_id
    );

    if ($return) {
        return $out;
    } else {
        echo $out;
    }
}


/**
 * Print a static graph with event data of agents
 *
 * @param integer id_agent Agent ID
 * @param integer width pie graph width
 * @param integer height pie graph height
 * @param integer period time period
 * @param string homeurl
 * @param bool return or echo the result
 */
function graph_graphic_moduleevents(
    $id_agent,
    $id_module,
    $width,
    $height,
    $period=0,
    $homeurl='',
    $return=false,
    $ttl=1,
    $widthForTicks=false
) {
    global $config;
    global $graphic_type;

    $data = [];

    $interval = 24;
    $date = get_system_time();
    $datelimit = ($date - $period);
    $periodtime = floor($period / $interval);
    $data = [];
    $legend = [];
    $full_legend = [];

    $cont = 0;
    for ($i = 0; $i < $interval; $i++) {
        $bottom = ($datelimit + ($periodtime * $i));
        if (! $graphic_type) {
            $name = date('H\h', $bottom);
        } else {
            $name = $bottom;
        }

        // Show less values in legend
        if ($cont == 0 or ($cont % 2)) {
            $legend[$cont] = $name;
        }

        $full_legend[$cont] = $name;

        $top = ($datelimit + ($periodtime * ($i + 1)));

        $event_filter = ['id_agente' => $id_agent,
            'utimestamp > '.$bottom,
            'utimestamp < '.$top
        ];
        if ((int) $id_module !== 0) {
            $event_filter['id_agentmodule'] = $id_module;
        }

        $event = db_get_row_filter('tevento', $event_filter, 'criticity, utimestamp');

        if (!empty($event['utimestamp'])) {
            $data[$cont]['utimestamp'] = $periodtime;
            switch ($event['criticity']) {
                case EVENT_CRIT_WARNING:
                    $data[$cont]['data'] = 2;
                break;

                case EVENT_CRIT_CRITICAL:
                    $data[$cont]['data'] = 3;
                break;

                default:
                    $data[$cont]['data'] = 1;
                break;
            }
        } else {
            $data[$cont]['utimestamp'] = $periodtime;
            $data[$cont]['data'] = 1;
        }

        $cont++;
    }

    $colors = [
        1 => COL_NORMAL,
        2 => COL_WARNING,
        3 => COL_CRITICAL,
        4 => COL_UNKNOWN,
    ];

    $out = flot_slicesbar_graph(
        $data,
        $period,
        $width,
        $height,
        $full_legend,
        $colors,
        $config['fontpath'],
        $config['round_corner'],
        $homeurl,
        '',
        '',
        false,
        $id_agent,
        [],
        true,
        $ttl,
        $widthForTicks
    );

    if ($return) {
        return $out;
    } else {
        echo $out;
    }
}


/**
 * Function for retun image no data.
 *
 * @param integer $width  Width.
 * @param integer $height Height.
 *
 * @return string Image.
 */
function fs_error_image($width=300, $height=110)
{
    return graph_nodata_image(['height' => $height]);
}


/**
 * Function for uncompressed data module for cherts.
 *
 * @param integer $agent_module_id Id modulo.
 * @param array   $date_array      Date start, finish, period.
 * @param integer $show_unknown    Show Unknown.
 * @param integer $show_percentil  Show Percentil.
 * @param integer $series_suffix   Series.
 * @param boolean $compare         Type compare.
 * @param boolean $data_slice      Size slice.
 * @param string  $type_mode_graph Type.
 *
 * @return array Return array data uncompresess.
 */
function fullscale_data(
    $agent_module_id,
    $date_array,
    $show_unknown=0,
    $show_percentil=0,
    $series_suffix=0,
    $compare=false,
    $data_slice=false,
    $type_mode_graph=''
) {
    global $config;
    $data_uncompress = db_uncompress_module_data(
        $agent_module_id,
        $date_array['start_date'],
        $date_array['final_date'],
        $data_slice
    );

    if ($data_uncompress === false) {
        return [];
    }

    $data = [];
    $previous_data = 0;
    // Normal.
    $min_value_total = PHP_INT_MAX;
    $max_value_total = (-PHP_INT_MAX);
    // Max.
    $max_value_min = PHP_INT_MAX;
    $max_value_max = (-PHP_INT_MAX);
    // Min.
    $min_value_min = PHP_INT_MAX;
    $min_value_max = (-PHP_INT_MAX);
    // Avg.
    $avg_value_min = PHP_INT_MAX;
    $avg_value_max = (-PHP_INT_MAX);

    $flag_unknown  = 0;
    $array_percentil = [];

    // Missing initializations.
    $params = ['baseline' => false];
    $sum_data_total = 0;
    $count_data_total = 0;
    $sum_data_min = 0;
    $sum_data_max = 0;
    $sum_data_avg = 0;

    if ($data_slice) {
        if (isset($data_uncompress) === true
            && is_array($data_uncompress) === true
        ) {
            foreach ($data_uncompress as $k) {
                $sum_data   = 0;
                $count_data = 0;
                $min_value  = PHP_INT_MAX;
                $max_value  = (-PHP_INT_MAX);
                $flag_virtual_data = 0;
                foreach ($k['data'] as $v) {
                    if (isset($v['type']) && $v['type'] == 1) {
                        // Skip unnecesary virtual data.
                        continue;
                        $flag_virtual_data = 1;
                    }

                    if ($compare) {
                        // Data * 1000 need js utimestam mlsecond.
                        $real_date = (($v['utimestamp'] + $date_array['period']) * 1000);
                    } else {
                        $real_date = ($v['utimestamp'] * 1000);
                    }

                    if ($v['datos'] === null) {
                        // Unknown.
                        if ($show_unknown) {
                            if (!$compare) {
                                if ($flag_unknown) {
                                    $data['unknown'.$series_suffix]['data'][] = [
                                        $real_date,
                                        1,
                                    ];
                                } else {
                                    $data['unknown'.$series_suffix]['data'][] = [
                                        ($real_date - 1),
                                        0,
                                    ];
                                    $data['unknown'.$series_suffix]['data'][] = [
                                        $real_date,
                                        1,
                                    ];
                                    $flag_unknown = 1;
                                }
                            }
                        }

                        $v['datos'] = $previous_data;
                    } else {
                        // Normal.
                        $previous_data = $v['datos'];
                        if ($show_unknown) {
                            if (!$compare) {
                                if ($flag_unknown) {
                                    $data['unknown'.$series_suffix]['data'][] = [
                                        $real_date,
                                        0,
                                    ];
                                    $flag_unknown = 0;
                                }
                            }
                        }
                    }

                    // Max.
                    if ($v['datos'] === false || $v['datos'] >= $max_value) {
                        if ($v['datos'] === false) {
                            $max_value = 0;
                        } else {
                            $max_value = $v['datos'];
                        }
                    }

                    // Min.
                    if ($v['datos'] <= $min_value) {
                        $min_value = $v['datos'];
                    }

                    // Avg sum.
                    $sum_data += $v['datos'];

                    // Avg count.
                    $count_data++;

                    if ($show_percentil && !$compare) {
                        $array_percentil[] = $v['datos'];
                    }

                    $last_data = $v['datos'];
                }

                if (!$flag_virtual_data) {
                    if ($compare) {
                        // Data * 1000 need js utimestam mlsecond.
                        $real_date = (($k['data'][0]['utimestamp'] + $date_array['period']) * 1000);
                    } else {
                        $real_date = ($k['data'][0]['utimestamp'] * 1000);
                    }

                    if ($type_mode_graph <= 1) {
                        $data['sum'.$series_suffix]['data'][] = [
                            $real_date,
                            ($sum_data / $count_data),
                        ];
                    }

                    if ($type_mode_graph && !$params['baseline']) {
                        if ((int) $type_mode_graph === 1 || (int) $type_mode_graph === 3) {
                            if ($min_value != PHP_INT_MAX) {
                                $data['min'.$series_suffix]['data'][] = [
                                    $real_date,
                                    $min_value,
                                ];
                            }
                        }

                        if ((int) $type_mode_graph === 1 || (int) $type_mode_graph === 2) {
                            if ($max_value != (-PHP_INT_MAX)) {
                                $data['max'.$series_suffix]['data'][] = [
                                    $real_date,
                                    $max_value,
                                ];
                            }
                        }
                    } else {
                        if ($min_value != PHP_INT_MAX) {
                            $data['sum'.$series_suffix]['slice_data'][$real_date]['min'] = $min_value;
                        }

                        $data['sum'.$series_suffix]['slice_data'][$real_date]['avg'] = ($sum_data / $count_data);

                        if ($max_value != (-PHP_INT_MAX)) {
                            $data['sum'.$series_suffix]['slice_data'][$real_date]['max'] = $max_value;
                        }
                    }

                    // Max total.
                    if ($max_value >= $max_value_total
                        && $max_value != (-PHP_INT_MAX)
                    ) {
                        $max_value_total = $max_value;
                    }

                    // Min total.
                    if ($min_value <= $min_value_total
                        && $min_value != PHP_INT_MAX
                    ) {
                        $min_value_total = $min_value;
                    }

                    // Avg sum total.
                    $sum_data_total += ($sum_data / $count_data);

                    // Avg count total.
                    $count_data_total++;

                    if (!$params['baseline']) {
                        // MIN.
                        // max min.
                        if ($min_value >= $min_value_max
                            && $min_value != PHP_INT_MAX
                        ) {
                            $min_value_max = $min_value;
                        }

                        // Min min.
                        if ($min_value <= $min_value_min
                            && $min_value != PHP_INT_MAX
                        ) {
                            $min_value_min = $min_value;
                        }

                        // Avg sum min.
                        if ($min_value != PHP_INT_MAX) {
                            $sum_data_min += $min_value;
                        }

                        // MAX.
                        // Max max.
                        if ($max_value >= $max_value_max
                            && $max_value != (-PHP_INT_MAX)
                        ) {
                            $max_value_max = $max_value;
                        }

                        // Min max.
                        if ($max_value <= $max_value_min
                            && $max_value != (-PHP_INT_MAX)
                        ) {
                            $max_value_min = $max_value;
                        }

                        // Avg Sum max.
                        if ($max_value != (-PHP_INT_MAX)) {
                            $sum_data_max += $max_value;
                        }

                        // AVG.
                        // Max max.
                        if (($sum_data / $count_data) >= $avg_value_max) {
                            $avg_value_max = ($sum_data / $count_data);
                        }

                        // Min max.
                        if (($sum_data / $count_data) <= $avg_value_min) {
                            $avg_value_min = ($sum_data / $count_data);
                        }

                        // Avg sum max.
                        $sum_data_avg += ($sum_data / $count_data);
                    }
                }
            }
        }

        if ($type_mode_graph <= 1) {
            $data['sum'.$series_suffix]['min'] = $min_value_total;
            $data['sum'.$series_suffix]['max'] = $max_value_total;
            $data['sum'.$series_suffix]['avg'] = 0;
            if (isset($count_data_total) === true) {
                $data['sum'.$series_suffix]['avg'] = ($sum_data_total / $count_data_total);
            }
        }

        if (!$params['baseline']) {
            if ((int) $type_mode_graph === 1 || (int) $type_mode_graph === 3) {
                $data['min'.$series_suffix]['min'] = $min_value_min;
                $data['min'.$series_suffix]['max'] = $min_value_max;
                $data['min'.$series_suffix]['avg'] = ($sum_data_min / $count_data_total);
            }

            if ((int) $type_mode_graph === 1 || (int) $type_mode_graph === 2) {
                $data['max'.$series_suffix]['min'] = $max_value_min;
                $data['max'.$series_suffix]['max'] = $max_value_max;
                $data['max'.$series_suffix]['avg'] = ($sum_data_max / $count_data_total);
            }

            if ($type_mode_graph <= 1) {
                $data['sum'.$series_suffix]['min'] = $avg_value_min;
                $data['sum'.$series_suffix]['max'] = $avg_value_max;
                $data['sum'.$series_suffix]['avg'] = ($sum_data_avg / $count_data_total);
            }
        }
    } else {
        $sum_data = 0;
        $count_data = 0;
        foreach ($data_uncompress as $k) {
            foreach ($k['data'] as $v) {
                if (isset($v['type']) && $v['type'] == 1) {
                    // Skip unnecesary virtual data.
                    continue;
                }

                if ($compare) {
                    // Data * 1000 need js utimestam mlsecond.
                    $real_date = (($v['utimestamp'] + $date_array['period']) * 1000);
                } else {
                    $real_date = ($v['utimestamp'] * 1000);
                }

                if ($v['datos'] === null) {
                    // Unknown.
                    if ($show_unknown) {
                        if (!$compare) {
                            if ($flag_unknown) {
                                $data['unknown'.$series_suffix]['data'][] = [
                                    $real_date,
                                    1,
                                ];
                            } else {
                                $data['unknown'.$series_suffix]['data'][] = [
                                    ($real_date - 1),
                                    0,
                                ];
                                $data['unknown'.$series_suffix]['data'][] = [
                                    $real_date,
                                    1,
                                ];
                                $flag_unknown = 1;
                            }
                        }
                    }

                    $data['sum'.$series_suffix]['data'][] = [
                        $real_date,
                        $previous_data,
                    ];
                } else {
                    // Normal.
                    $previous_data = $v['datos'];
                    $data['sum'.$series_suffix]['data'][] = [
                        $real_date,
                        $v['datos'],
                    ];
                    if ($show_unknown) {
                        if (!$compare) {
                            if ($flag_unknown) {
                                $data['unknown'.$series_suffix]['data'][] = [
                                    $real_date,
                                    0,
                                ];
                                $flag_unknown = 0;
                            }
                        }
                    }
                }

                if (isset($v['datos']) && $v['datos']) {
                    // Max.
                    if ((float) $v['datos'] >= $max_value_max) {
                        $max_value_max = $v['datos'];
                    }

                    // Min.
                    if ((float) $v['datos'] <= $min_value_min) {
                        $min_value_min = $v['datos'];
                    }

                    // Avg sum.
                    $sum_data += $v['datos'];
                }

                // Avg count.
                $count_data++;

                if ($show_percentil && !$compare) {
                    $array_percentil[] = $v['datos'];
                }

                $last_data = $v['datos'];
            }
        }

        $data['sum'.$series_suffix]['min'] = $min_value_min;
        $data['sum'.$series_suffix]['max'] = $max_value_max;
        $data['sum'.$series_suffix]['avg'] = ($count_data == 0) ? 0 : ($sum_data / $count_data);
    }

    if ($show_percentil && !$compare) {
        $percentil_result = get_percentile($show_percentil, $array_percentil);
        if ($compare) {
            $data['percentil'.$series_suffix]['data'][] = [
                (($date_array['start_date'] + $date_array['period']) * 1000),
                $percentil_result,
            ];
            $data['percentil'.$series_suffix]['data'][] = [
                (($date_array['final_date'] + $date_array['period']) * 1000),
                $percentil_result,
            ];
        } else {
            $data['percentil'.$series_suffix]['data'][] = [
                ($date_array['start_date'] * 1000),
                $percentil_result,
            ];
            $data['percentil'.$series_suffix]['data'][] = [
                ($date_array['final_date'] * 1000),
                $percentil_result,
            ];
        }
    }

    // Add missed last data.
    if ($compare) {
        $data['sum'.$series_suffix]['data'][] = [
            (($date_array['final_date'] + $date_array['period']) * 1000),
            $last_data,
        ];
    } else {
        if ($type_mode_graph <= 1) {
            $data['sum'.$series_suffix]['data'][] = [
                ($date_array['final_date'] * 1000),
                $last_data,
            ];
        }

        if ($data_slice) {
            if ($type_mode_graph && !$params['baseline']) {
                if ((int) $type_mode_graph === 1 || (int) $type_mode_graph === 3) {
                    $data['min'.$series_suffix]['data'][] = [
                        ($date_array['final_date'] * 1000),
                        $min_value,
                    ];
                }

                if ((int) $type_mode_graph === 1 || (int) $type_mode_graph === 2) {
                    $data['max'.$series_suffix]['data'][] = [
                        ($date_array['final_date'] * 1000),
                        $max_value,
                    ];
                }
            } else {
                $data['sum'.$series_suffix]['slice_data'][($date_array['final_date'] * 1000)]['min'] = $min_value;
                $data['sum'.$series_suffix]['slice_data'][($date_array['final_date'] * 1000)]['avg'] = 0;
                if (isset($count_data) === true) {
                    $data['sum'.$series_suffix]['slice_data'][($date_array['final_date'] * 1000)]['avg'] = ($sum_data / $count_data);
                }

                $data['sum'.$series_suffix]['slice_data'][($date_array['final_date'] * 1000)]['max'] = $max_value;
            }
        }
    }

    return $data;
}


/**
 * Print an area graph with netflow aggregated
 */
function graph_netflow_aggregate_area($data, $period, $width, $height, $ttl=1, $only_image=false, $date=null)
{
    global $config;
    global $graphic_type;

    if (empty($data)) {
        echo fs_error_image();
        return;
    }

    // Calculate source indexes.
    foreach ($data['sources'] as $key => $value) {
        $i = 0;
        foreach ($data['data'] as $k => $v) {
            $chart['netflow_'.$key]['data'][$i][0] = ($k * 1000);
            $chart['netflow_'.$key]['data'][$i][1] = $v[$key];
            $i++;
        }
    }

    if ($config['homeurl'] != '') {
        $homeurl = $config['homeurl'];
    } else {
        $homeurl = '';
    }

    if ($config['fixed_graph'] == false) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url(
                'images/logo_vertical_water.png',
                false,
                false,
                false
            ),
        ];

            $water_mark = $config['homedir'].'/images/logo_vertical_water.png';
    }

    if ($ttl >= 2) {
        $only_image = true;
    } else {
        $only_image = false;
    }

    $params = [
        'agent_module_id'   => false,
        'period'            => $period,
        'width'             => '90%',
        'height'            => 450,
        'unit'              => 'bytes',
        'only_image'        => $only_image,
        'homeurl'           => $homeurl,
        'menu'              => true,
        'backgroundColor'   => 'white',
        'type_graph'        => 'area',
        'font'              => $config['fontpath'],
        'font_size'         => $config['font_size'],
        'array_data_create' => $chart,
        'stacked'           => 1,
        'date'              => $date,
        'show_export_csv'   => false,
        'show_overview'     => false,
    ];

    return grafico_modulo_sparse($params);
}


/**
 * Print an area graph with netflow total
 */
function graph_netflow_total_area($data, $period, $width, $height, $unit='', $ttl=1, $only_image=false)
{
    global $config;
    global $graphic_type;

    if (empty($data)) {
        echo fs_error_image();
        return;
    }

    // Calculate source indexes
    $i = 0;
    foreach ($data as $key => $value) {
        $chart['netflow']['data'][$i][0] = ($key * 1000);
        $chart['netflow']['data'][$i][1] = $value['data'];
        $i++;
    }

    if ($config['homeurl'] != '') {
        $homeurl = $config['homeurl'];
    } else {
        $homeurl = '';
    }

    if ($config['fixed_graph'] == false) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url('images/logo_vertical_water.png', false, false, false),
        ];

            $water_mark = $config['homedir'].'/images/logo_vertical_water.png';
    }

    if ($ttl >= 2) {
        $only_image = true;
    } else {
        $only_image = false;
    }

    $params = [
        'agent_module_id'   => false,
        'period'            => $period,
        'width'             => '90%',
        'height'            => 450,
        'unit'              => $unit,
        'only_image'        => $only_image,
        'homeurl'           => $homeurl,
        'menu'              => true,
        'backgroundColor'   => 'white',
        'type_graph'        => 'area',
        'font'              => $config['fontpath'],
        'font_size'         => $config['font_size'],
        'array_data_create' => $chart,
    ];

    return grafico_modulo_sparse($params);
}


/**
 * Print a pie graph with netflow aggregated
 */
function graph_netflow_aggregate_pie($data, $aggregate, $ttl=1, $only_image=false)
{
    global $config;
    global $graphic_type;

    if (empty($data)) {
        return fs_error_image();
    }

    $date_array = [];
    $date_array['period']     = 300;
    $date_array['final_date'] = time();
    $date_array['start_date'] = (time() - 300);

    $i = 0;
    $values = [];
    $agg = '';
    while (isset($data[$i])) {
        $agg = $data[$i]['agg'];
        if (!isset($values[$agg])) {
            $values[$agg] = $data[$i]['data'];
        } else {
            $values[$agg] += $data[$i]['data'];
        }

        $i++;
    }

    $labels = array_keys($values);
    $values = array_values($values);

    if ($config['fixed_graph'] == false) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url('images/logo_vertical_water.png', false, false, false),
        ];
    }

    $options = [
        'height'    => 230,
        'waterMark' => $water_mark,
        'ttl'       => $ttl,
        'legend'    => [
            'display'  => true,
            'position' => 'right',
            'align'    => 'center',
        ],
        'labels'    => $labels,
    ];

    $output = '';
    if ((int) $ttl === 2) {
        $output .= '<img src="data:image/png;base64,';
    } else {
        $output .= '<div style="margin: 0 auto; width:500px;">';
    }

    // Pie.
    $output .= pie_graph(
        $values,
        $options
    );

    if ((int) $ttl === 2) {
        $output .= '" />';
    } else {
        $output .= '</div>';
    }

    return $output;
}


/**
 * Print a circular mesh array.
 *
 * @param array $data Array with properly data structure. Array with two
 *      elements required:
 *          'elements': Non-associative array with all the relationships.
 *          'matrix': Array of arrays with value of the relationship.
 *
 * @return string HTML data.
 */
function graph_netflow_circular_mesh($data)
{
    global $config;

    if (empty($data) || empty($data['elements']) || empty($data['matrix'])) {
        return fs_error_image();
    }

    include_once $config['homedir'].'/include/graphs/functions_d3.php';

    return d3_relationship_graph($data['elements'], $data['matrix'], 900, true);
}


/**
 * Print a rectangular graph with the traffic of the ports for each IP
 */
function graph_netflow_host_traffic($data, $width=700, $height=700)
{
    global $config;

    if (empty($data)) {
        return fs_error_image();
    }

    include_once $config['homedir'].'/include/graphs/functions_d3.php';

    return d3_tree_map_graph($data, $width, $height, true);
}


/**
 * Print a graph with event data of module
 *
 * @param integer id_module Module ID
 * @param integer width graph width
 * @param integer height graph height
 * @param integer period time period
 * @param string homeurl Home url if the complete path is needed
 * @param int Zoom factor over the graph
 * @param string adaptation width and margin left key (could be adapter_[something] or adapted_[something])
 * @param int date limit of the period
 */
function graphic_module_events($id_module, $width, $height, $period=0, $homeurl='', $zoom=0, $adapt_key='', $date=false, $stat_win=false)
{
    global $config;
    global $graphic_type;

    $data = [];

    // $resolution = $config['graph_res'] * ($period * 2 / $width); // Number of "slices" we want in graph
    $resolution = (5 * ($period * 2 / $width));
    // Number of "slices" we want in graph
    $interval = (int) ($period / $resolution);
    if ($date === false) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);
    $periodtime = floor($period / $interval);
    $time = [];
    $data = [];

    // Set the title and time format
    if ($period <= SECONDS_6HOURS) {
        $time_format = 'H:i:s';
    } else if ($period < SECONDS_1DAY) {
        $time_format = 'H:i';
    } else if ($period < SECONDS_15DAYS) {
        $time_format = 'M d H:i';
    } else if ($period < SECONDS_1MONTH) {
        $time_format = 'M d H\h';
    } else if ($period < SECONDS_6MONTHS) {
        $time_format = 'M d H\h';
    } else {
        $time_format = 'Y M d H\h';
    }

    $legend = [];
    $cont = 0;
    for ($i = 0; $i < $interval; $i++) {
        $bottom = ($datelimit + ($periodtime * $i));
        if (! $graphic_type) {
            $name = date($time_format, $bottom);
            // $name = date('H\h', $bottom);
        } else {
            $name = $bottom;
        }

        $top = ($datelimit + ($periodtime * ($i + 1)));

        $events = db_get_all_rows_filter(
            'tevento',
            ['id_agentmodule' => $id_module,
                'utimestamp > '.$bottom,
                'utimestamp < '.$top
            ],
            'event_type, utimestamp'
        );

        if (!empty($events)) {
            $status = 'normal';
            foreach ($events as $event) {
                if (empty($event['utimestamp'])) {
                    continue;
                }

                switch ($event['event_type']) {
                    case 'going_down_normal':
                    case 'going_up_normal':
                        // The default status is normal. Do nothing
                    break;

                    case 'going_unknown':
                        if ($status == 'normal') {
                            $status = 'unknown';
                        }
                    break;

                    case 'going_up_warning':
                    case 'going_down_warning':
                        if ($status == 'normal' || $status == 'unknown') {
                            $status = 'warning';
                        }
                    break;

                    case 'going_up_critical':
                    case 'going_down_critical':
                        $status = 'critical';
                    break;
                }
            }
        }

        $data[$cont]['utimestamp'] = $periodtime;

        if (!empty($events)) {
            switch ($status) {
                case 'warning':
                    $data[$cont]['data'] = 2;
                break;

                case 'critical':
                    $data[$cont]['data'] = 3;
                break;

                case 'unknown':
                    $data[$cont]['data'] = 4;
                break;

                default:
                    $data[$cont]['data'] = 1;
                break;
            }
        } else {
            $data[$cont]['data'] = 1;
        }

        $current_timestamp = $bottom;

        $legend[] = date($time_format, $current_timestamp);
        $cont++;
    }

    $pixels_between_xdata = 25;
    $max_xdata_display = round($width / $pixels_between_xdata);
    $ndata = count($data);
    if ($max_xdata_display > $ndata) {
        $xdata_display = $ndata;
    } else {
        $xdata_display = $max_xdata_display;
    }

    $step = round($ndata / $xdata_display);

    $colors = [
        1 => '#38B800',
        2 => '#FFFF00',
        3 => '#FF0000',
        4 => '#C3C3C3',
    ];

    // Draw slicebar graph
    echo flot_slicesbar_graph(
        $data,
        $period,
        $width,
        50,
        $legend,
        $colors,
        $config['fontpath'],
        $config['round_corner'],
        $homeurl,
        '',
        $adapt_key,
        $stat_win
    );
}


function graph_nodata_image($options)
{
    global $config;

    if ($options['base64'] === true) {
        $dataImg = file_get_contents(
            $config['homedir'].'/images/image_problem_area_150.png'
        );
        return base64_encode($dataImg);
    }

    return html_print_image(
        'images/image_problem_area.png',
        true,
        [
            'title' => __('No data'),
            'style' => 'width: 200px;',
        ]
    );
}


function get_criticity_pie_colors($data_graph)
{
    $colors = [];
    foreach (array_keys($data_graph) as $crit) {
        switch ($crit) {
            case __('Maintenance'):
                $colors[$crit] = COL_MAINTENANCE;
            break;

            case __('Informational'):
                $colors[$crit] = COL_INFORMATIONAL;
            break;

            case __('Normal'):
                $colors[$crit] = COL_NORMAL;
            break;

            case __('Warning'):
                $colors[$crit] = COL_WARNING;
            break;

            case __('Critical'):
                $colors[$crit] = COL_CRITICAL;
            break;

            case __('Minor'):
                $colors[$crit] = COL_MINOR;
            break;

            case __('Major'):
                $colors[$crit] = COL_MAJOR;
            break;
        }
    }

    return $colors;
}


/**
 * Print a rectangular graph with the snmptraps received
 */
function graph_snmp_traps_treemap($data, $width=700, $height=700)
{
    global $config;

    if (empty($data)) {
        return fs_error_image();
    }

    include_once $config['homedir'].'/include/graphs/functions_d3.php';

    return d3_tree_map_graph($data, $width, $height, true);
}


function extract_agents_with_group_id(&$agents, $group_id)
{
    $valid_agents = [];
    foreach ($agents as $id => $agent) {
        if (isset($agent['group']) && $agent['group'] == $group_id) {
            $valid_agents[$id] = $agent;
            unset($agents[$id]);
        }
    }

    if (!empty($valid_agents)) {
        return $valid_agents;
    } else {
        return false;
    }
}


function iterate_group_array($groups, &$data_agents)
{
    $data = [];

    foreach ($groups as $id => $group) {
        $group_aux = [];
        $group_aux['id'] = (int) $id;
        $group_aux['name'] = io_safe_output($group['nombre']);
        $group_aux['show_name'] = true;
        $group_aux['parent'] = (int) $group['parent'];
        $group_aux['type'] = 'group';
        $group_aux['size'] = 100;
        $group_aux['status'] = groups_get_status($id);

        switch ($group_aux['status']) {
            case AGENT_STATUS_CRITICAL:
                $group_aux['color'] = COL_CRITICAL;
            break;

            case AGENT_STATUS_WARNING:
            case AGENT_STATUS_ALERT_FIRED:
                $group_aux['color'] = COL_WARNING;
            break;

            case AGENT_STATUS_NORMAL:
                $group_aux['color'] = COL_NORMAL;
            break;

            case AGENT_STATUS_UNKNOWN:
            default:
                $group_aux['color'] = COL_UNKNOWN;
            break;
        }

        $tooltip_content = html_print_image('images/'.$group['icon'], true).'&nbsp;'.__('Group').': <b>'.$group_aux['name'].'</b>';
        $group_aux['tooltip_content'] = $tooltip_content;

        $group_aux['children'] = [];

        if (!empty($group['children'])) {
            $group_aux['children'] = iterate_group_array($group['children'], $data_agents);
        }

        $agents = extract_agents_with_group_id($data_agents, (int) $id);

        if (!empty($agents)) {
            $group_aux['children'] = array_merge($group_aux['children'], $agents);
        }

        $data[] = $group_aux;
    }

    return $data;
}


/**
 * Print a solarburst graph with a representation of all the groups, agents, module groups and modules grouped
 */
function graph_monitor_wheel($width=550, $height=600, $filter=false)
{
    global $config;

    include_once $config['homedir'].'/include/functions_users.php';
    include_once $config['homedir'].'/include/functions_groups.php';
    include_once $config['homedir'].'/include/functions_agents.php';
    include_once $config['homedir'].'/include/functions_modules.php';

    $graph_data = [];

    $filter_module_group = (!empty($filter) && !empty($filter['module_group'])) ? $filter['module_group'] : false;

    if ($filter['group'] != 0) {
        $filter_subgroups = '';
        if (!$filter['dont_show_subgroups']) {
            $filter_subgroups = ' || parent IN ('.$filter['group'].')';
        }

        $groups = db_get_all_rows_sql('SELECT * FROM tgrupo where id_grupo IN ('.$filter['group'].') '.$filter_subgroups);

        $groups_ax = [];
        foreach ($groups as $g) {
            $groups_ax[$g['id_grupo']] = $g;
        }

        $groups = $groups_ax;
    } else {
        $groups = users_get_groups(false, 'AR', false, true, (!empty($filter) && isset($filter['group']) ? $filter['group'] : null));
    }

    $data_groups = [];
    if (!empty($groups)) {
        $groups_aux = $groups;

        $childrens = [];
        $data_groups = groups_get_tree_good($groups, false, $childrens);

        // When i want only one group
        if (count($data_groups) > 1) {
            foreach ($childrens as $id_c) {
                unset($data_groups[$id_c]);
            }
        }

        $data_groups_keys = [];
        groups_get_tree_keys($data_groups, $data_groups_keys);

        $groups_aux = null;
    }

    if (!empty($data_groups)) {
        $filter = ['id_grupo' => array_keys($data_groups_keys)];

        $fields = [
            'id_agente',
            'id_parent',
            'id_grupo',
            'alias',
        ];
        $agents = agents_get_agents($filter, $fields);

        if (!empty($agents)) {
            $agents_id = [];
            $agents_aux = [];
            foreach ($agents as $key => $agent) {
                $agents_aux[$agent['id_agente']] = $agent;
            }

            $agents = $agents_aux;
            $agents_aux = null;

            $module_groups = modules_get_modulegroups();
            $module_groups[0] = __('Not assigned');
            $modules = agents_get_modules(array_keys($agents), '*');

            $data_agents = [];
            if (!empty($modules)) {
                foreach ($modules as $key => $module) {
                    $module_id = (int) $module['id_agente_modulo'];
                    $agent_id = (int) $module['id_agente'];
                    $module_group_id = (int) $module['id_module_group'];
                    $module_name = io_safe_output($module['nombre']);
                    $module_status = modules_get_agentmodule_status($module_id);
                    $module_value = modules_get_last_value($module_id);

                    if ($filter_module_group && $filter_module_group != $module_group_id) {
                        continue;
                    }

                    if (!isset($data_agents[$agent_id])) {
                        $data_agents[$agent_id] = [];
                        $data_agents[$agent_id]['id'] = $agent_id;
                        $data_agents[$agent_id]['name'] = io_safe_output($agents[$agent_id]['alias']);
                        $data_agents[$agent_id]['group'] = (int) $agents[$agent_id]['id_grupo'];
                        $data_agents[$agent_id]['type'] = 'agent';
                        $data_agents[$agent_id]['size'] = 30;
                        $data_agents[$agent_id]['show_name'] = true;
                        $data_agents[$agent_id]['children'] = [];

                        $tooltip_content = __('Agent').': <b>'.$data_agents[$agent_id]['name'].'</b>';
                        $data_agents[$agent_id]['tooltip_content'] = io_safe_output($tooltip_content);

                        $data_agents[$agent_id]['modules_critical'] = 0;
                        $data_agents[$agent_id]['modules_warning'] = 0;
                        $data_agents[$agent_id]['modules_normal'] = 0;
                        $data_agents[$agent_id]['modules_not_init'] = 0;
                        $data_agents[$agent_id]['modules_not_normal'] = 0;
                        $data_agents[$agent_id]['modules_unknown'] = 0;

                        $data_agents[$agent_id]['color'] = COL_UNKNOWN;

                        unset($agents[$agent_id]);
                    }

                    if (!isset($data_agents[$agent_id]['children'][$module_group_id])) {
                        $data_agents[$agent_id]['children'][$module_group_id] = [];
                        $data_agents[$agent_id]['children'][$module_group_id]['id'] = $module_group_id;
                        $data_agents[$agent_id]['children'][$module_group_id]['name'] = io_safe_output($module_groups[$module_group_id]);
                        $data_agents[$agent_id]['children'][$module_group_id]['type'] = 'module_group';
                        $data_agents[$agent_id]['children'][$module_group_id]['size'] = 10;
                        $data_agents[$agent_id]['children'][$module_group_id]['children'] = [];

                        $tooltip_content = __('Module group').': <b>'.$data_agents[$agent_id]['children'][$module_group_id]['name'].'</b>';
                        $data_agents[$agent_id]['children'][$module_group_id]['tooltip_content'] = $tooltip_content;

                        $data_agents[$agent_id]['children'][$module_group_id]['modules_critical'] = 0;
                        $data_agents[$agent_id]['children'][$module_group_id]['modules_warning'] = 0;
                        $data_agents[$agent_id]['children'][$module_group_id]['modules_normal'] = 0;
                        $data_agents[$agent_id]['children'][$module_group_id]['modules_not_init'] = 0;
                        $data_agents[$agent_id]['children'][$module_group_id]['modules_not_normal'] = 0;
                        $data_agents[$agent_id]['children'][$module_group_id]['modules_unknown'] = 0;

                        $data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_UNKNOWN;
                    }

                    switch ($module_status) {
                        case AGENT_MODULE_STATUS_CRITICAL_BAD:
                        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                            $data_agents[$agent_id]['modules_critical']++;
                            $data_agents[$agent_id]['children'][$module_group_id]['modules_critical']++;
                        break;

                        case AGENT_MODULE_STATUS_WARNING:
                        case AGENT_MODULE_STATUS_WARNING_ALERT:
                            $data_agents[$agent_id]['modules_warning']++;
                            $data_agents[$agent_id]['children'][$module_group_id]['modules_warning']++;
                        break;

                        case AGENT_MODULE_STATUS_NORMAL:
                        case AGENT_MODULE_STATUS_NORMAL_ALERT:
                            $data_agents[$agent_id]['modules_normal']++;
                            $data_agents[$agent_id]['children'][$module_group_id]['modules_normal']++;
                        break;

                        case AGENT_MODULE_STATUS_NOT_INIT:
                            $data_agents[$agent_id]['modules_not_init']++;
                            $data_agents[$agent_id]['children'][$module_group_id]['modules_not_init']++;
                        break;

                        case AGENT_MODULE_STATUS_NOT_NORMAL:
                            $data_agents[$agent_id]['modules_not_normal']++;
                            $data_agents[$agent_id]['children'][$module_group_id]['modules_not_normal']++;
                        break;

                        case AGENT_MODULE_STATUS_NO_DATA:
                        case AGENT_MODULE_STATUS_UNKNOWN:
                            $data_agents[$agent_id]['modules_unknown']++;
                            $data_agents[$agent_id]['children'][$module_group_id]['modules_unknown']++;
                        break;
                    }

                    if ($data_agents[$agent_id]['modules_critical'] > 0) {
                        $data_agents[$agent_id]['color'] = COL_CRITICAL;
                    } else if ($data_agents[$agent_id]['modules_warning'] > 0) {
                        $data_agents[$agent_id]['color'] = COL_WARNING;
                    } else if ($data_agents[$agent_id]['modules_not_normal'] > 0) {
                        $data_agents[$agent_id]['color'] = COL_WARNING;
                    } else if ($data_agents[$agent_id]['modules_unknown'] > 0) {
                        $data_agents[$agent_id]['color'] = COL_UNKNOWN;
                    } else if ($data_agents[$agent_id]['modules_normal'] > 0) {
                        $data_agents[$agent_id]['color'] = COL_NORMAL;
                    } else {
                        $data_agents[$agent_id]['color'] = COL_NOTINIT;
                    }

                    if ($data_agents[$agent_id]['children'][$module_group_id]['modules_critical'] > 0) {
                        $data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_CRITICAL;
                    } else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_warning'] > 0) {
                        $data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_WARNING;
                    } else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_not_normal'] > 0) {
                        $data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_WARNING;
                    } else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_unknown'] > 0) {
                        $data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_UNKNOWN;
                    } else if ($data_agents[$agent_id]['children'][$module_group_id]['modules_normal'] > 0) {
                        $data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_NORMAL;
                    } else {
                        $data_agents[$agent_id]['children'][$module_group_id]['color'] = COL_NOTINIT;
                    }

                    $data_module = [];
                    $data_module['id'] = $module_id;
                    $data_module['name'] = $module_name;
                    $data_module['type'] = 'module';
                    $data_module['size'] = 10;
                    $data_module['link'] = ui_get_full_url("index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$agent_id");

                    $tooltip_content = __('Module').': <b>'.$module_name.'</b>';
                    if (isset($module_value) && $module_value !== false) {
                        $tooltip_content .= '<br>';
                        $tooltip_content .= __('Value').': <b>'.io_safe_output($module_value).'</b>';
                    }

                    $data_module['tooltip_content'] = $tooltip_content;

                    switch ($module_status) {
                        case AGENT_MODULE_STATUS_CRITICAL_BAD:
                        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                            $data_module['color'] = COL_CRITICAL;
                        break;

                        case AGENT_MODULE_STATUS_WARNING:
                        case AGENT_MODULE_STATUS_WARNING_ALERT:
                            $data_module['color'] = COL_WARNING;
                        break;

                        case AGENT_MODULE_STATUS_NORMAL:
                        case AGENT_MODULE_STATUS_NORMAL_ALERT:
                            $data_module['color'] = COL_NORMAL;
                        break;

                        case AGENT_MODULE_STATUS_NOT_INIT:
                            $data_module['color'] = COL_NOTINIT;
                        break;

                        case AGENT_MODULE_STATUS_NOT_NORMAL:
                            $data_module['color'] = COL_WARNING;
                        break;

                        case AGENT_MODULE_STATUS_NO_DATA:
                        case AGENT_MODULE_STATUS_UNKNOWN:
                        default:
                            $data_module['color'] = COL_UNKNOWN;
                        break;
                    }

                    $data_agents[$agent_id]['children'][$module_group_id]['children'][] = $data_module;
                    unset($modules[$module_id]);
                }

                $data_agents = array_map(
                    function ($value) {
                        $value['children'] = array_merge($value['children']);
                        return $value;
                    },
                    $data_agents
                );
            }

            foreach ($agents as $id => $agent) {
                if (!isset($data_agents[$id])) {
                    $data_agents[$id] = [];
                    $data_agents[$id]['id'] = (int) $id;
                    $data_agents[$id]['name'] = io_safe_output($agent['alias']);
                    $data_agents[$id]['type'] = 'agent';
                    $data_agents[$id]['color'] = COL_NOTINIT;
                    $data_agents[$id]['show_name'] = true;
                }
            }

            $agents = null;
        }
    }

    $graph_data = [
        'name'     => __('Main node'),
        'type'     => 'center_node',
        'children' => iterate_group_array($data_groups, $data_agents),
        'color'    => ($config['style'] === 'pandora_black') ? '#111' : '#FFF',
    ];

    if (empty($graph_data['children'])) {
        return fs_error_image();
    }

    include_once $config['homedir'].'/include/graphs/functions_d3.php';

    return d3_sunburst_graph($graph_data, $width, $height, true);
}


/**
 * Function that on a date requests 3 times that period and takes an average.
 *
 * @param integer $agent_module_id   ID module.
 * @param array   $date_array        Date array start finish period.
 * @param array   $data_module_graph Data module.
 * @param array   $params            Params.
 *
 * @return array Data baseline graph.
 */
function get_baseline_data(
    $agent_module_id,
    $date_array,
    $data_module_graph,
    $params
) {
    $period = $date_array['period'];
    $date = $date_array['final_date'];
    $array_data = [];

    for ($i = 0; $i < 4; $i++) {
        $date_array = [];
        $date_array['period']     = $period;
        $date_array['final_date'] = ($date - ($period * $i));
        $date_array['start_date'] = ($date - ($period * ($i + 1)));
        $array_data[] = grafico_modulo_sparse_data(
            $agent_module_id,
            $date_array,
            $data_module_graph,
            $params,
            $i
        );
    }

    $result = [];
    $array_data[1] = array_reverse($array_data[1]['sum1']['slice_data']);
    $array_data[2] = array_reverse($array_data[2]['sum2']['slice_data']);
    $array_data[3] = array_reverse($array_data[3]['sum3']['slice_data']);
    foreach ($array_data[0]['sum0']['slice_data'] as $key => $value) {
        $data1 = array_pop($array_data[1]);
        $data2 = array_pop($array_data[2]);
        $data3 = array_pop($array_data[3]);

        $result['slice_data'][$key]['min'] = (($data1['min'] + $data2['min'] + $data3['min'] + $value['min']) / 4);
        $result['slice_data'][$key]['avg'] = (($data1['avg'] + $data2['avg'] + $data3['avg'] + $value['avg']) / 4);
        $result['slice_data'][$key]['max'] = (($data1['max'] + $data2['max'] + $data3['max'] + $value['max']) / 4);

        $result['data'][] = [
            $key,
            $result['slice_data'][$key]['avg'],
        ];
    }

    $result['avg'] = (($array_data[0]['sum0']['avg'] + $array_data[1]['sum1']['avg'] + $array_data[2]['sum2']['avg'] + $array_data[3]['sum3']['avg']) / 4);
    $result['max'] = max(
        $array_data[0]['sum0']['max'],
        $array_data[1]['sum1']['max'],
        $array_data[2]['sum2']['max'],
        $array_data[3]['sum3']['max']
    );
    $result['min'] = min(
        $array_data[0]['sum0']['min'],
        $array_data[1]['sum1']['min'],
        $array_data[2]['sum2']['min'],
        $array_data[3]['sum3']['min']
    );

    $result['agent_module_id'] = $array_data[0]['sum0']['agent_module_id'];
    $result['id_module_type'] = $array_data[0]['sum0']['id_module_type'];
    $result['agent_name'] = $array_data[0]['sum0']['agent_name'];
    $result['module_name'] = $array_data[0]['sum0']['module_name'];
    $result['agent_alias'] = $array_data[0]['sum0']['agent_alias'];
    return ['sum0' => $result];
}


/**
 * Draw graph SO agents by group.
 *
 * @param  [type]  $id_group
 * @param  integer $width
 * @param  integer $height
 * @param  boolean $recursive
 * @param  boolean $noWaterMark
 * @return string Graph
 */
function graph_so_by_group($id_group, $width=300, $height=200, $recursive=true, $noWaterMark=true)
{
    global $config;

    $id_groups = [$id_group];

    if ($recursive == true) {
        $groups = groups_get_children($id_group);
        if (count($groups) > 0) {
            $id_groups = [];
            foreach ($groups as $key => $value) {
                $id_groups[] = $value['id_grupo'];
            }
        }
    }

    $sql = sprintf(
        'SELECT COUNT(id_agente) AS count,
        os.name
        FROM tagente a
        LEFT JOIN tconfig_os os ON a.id_os = os.id_os
        WHERE a.id_grupo IN (%s)
        GROUP BY os.id_os',
        implode(',', $id_groups)
    );

    $result = db_get_all_rows_sql($sql, false, false);
    if ($result === false) {
        $result = [];
    }

    $labels = [];
    $data = [];
    foreach ($result as $key => $row) {
        $labels[] = $row['name'];
        $data[] = $row['count'];
    }

    if ($noWaterMark === true) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url('images/logo_vertical_water.png', false, false, false),
        ];
    } else {
        $water_mark = [];
    }

    $options = [
        'width'     => $width,
        'height'    => $height,
        'waterMark' => $water_mark,
        'legend'    => [
            'display'  => true,
            'position' => 'right',
            'align'    => 'center',
        ],
        'labels'    => $labels,
    ];

    return pie_graph(
        $data,
        $options
    );

}


/**
 * Draw graph events by group
 *
 * @param  [type]  $id_group
 * @param  integer $width
 * @param  integer $height
 * @param  boolean $noWaterMark
 * @param  boolean $time_limit
 * @param  boolean $recursive
 * @return string Graph
 */
function graph_events_agent_by_group($id_group, $width=300, $height=200, $noWaterMark=true, $time_limit=false, $recursive=true)
{
    global $config;

    $data = [];
    $labels = [];
    $loop = 0;
    define('NUM_PIECES_PIE_2', 6);

    // Add tags condition to filter.
    $tags_condition = '';
    if ($time_limit && $config['event_view_hr']) {
        $tags_condition .= ' AND utimestamp > (UNIX_TIMESTAMP(NOW()) - '.($config['event_view_hr'] * SECONDS_1HOUR).')';
    }

    $id_groups = [$id_group];
    if ($recursive === true) {
        $groups = groups_get_children($id_group);
        if (count($groups) > 0) {
            $id_groups = [];
            foreach ($groups as $key => $value) {
                $id_groups[] = $value['id_grupo'];
            }
        }
    }

    $filter_groups = ' AND te.id_grupo IN ('.implode(',', $id_groups).') ';

    // This will give the distinct id_agente, give the id_grupo that goes
    // with it and then the number of times it occured. GROUP BY statement
    // is required if both DISTINCT() and COUNT() are in the statement.
    $sql = sprintf(
        'SELECT DISTINCT(id_agente) AS id_agente,
                COUNT(id_agente) AS count
            FROM tevento te
            WHERE 1=1  AND estado = 0
            %s %s
            GROUP BY id_agente
            ORDER BY count DESC LIMIT 8',
        $tags_condition,
        $filter_groups
    );
    $result = db_get_all_rows_sql($sql, false, false);
    if ($result === false) {
        $result = [];
    }

    $system_events = 0;
    $other_events = 0;

    foreach ($result as $row) {
        $row['id_grupo'] = agents_get_agent_group($row['id_agente']);
        if (!check_acl($config['id_user'], $row['id_grupo'], 'ER') == 1) {
            continue;
        }

        if ($loop >= NUM_PIECES_PIE_2) {
            $other_events += $row['count'];
        } else {
            if ($row['id_agente'] == 0) {
                $system_events += $row['count'];
            } else {
                $alias = agents_get_alias($row['id_agente']);
                $name = mb_substr($alias, 0, 25).' #'.$row['id_agente'].' ('.$row['count'].')';
                $labels[] = io_safe_output($name);
                $data[] = $row['count'];
            }
        }

        $loop++;
    }

    if ($system_events > 0) {
        $name = __('SYSTEM').' ('.$system_events.')';
        $labels[] = io_safe_output($name);
        $data[] = $system_events;
    }

    // Sort the data.
    arsort($data);
    if ($noWaterMark === true) {
        $water_mark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => ui_get_full_url('images/logo_vertical_water.png', false, false, false),
        ];
    } else {
        $water_mark = [];
    }

    $options = [
        'width'     => $width,
        'height'    => $height,
        'waterMark' => $water_mark,
        'legend'    => [
            'display'  => true,
            'position' => 'right',
            'align'    => 'center',
        ],
        'labels'    => $labels,
    ];

    return pie_graph(
        $data,
        $options
    );
}
