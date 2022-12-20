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
require_once 'include/functions_custom_graphs.php';
require_once 'include/functions_graph.php';

$save_custom_graph = (bool) get_parameter('save_custom_graph');
$print_custom_graph = (bool) get_parameter('print_custom_graph', false);
$print_sparse_graph = (bool) get_parameter('print_sparse_graph');
$get_graphs = (bool) get_parameter('get_graphs_container');

if ($save_custom_graph) {
    $return = [];

    $id_modules = (array) get_parameter('id_modules', []);
    $name = get_parameter('name', '');
    $description = get_parameter('description', '');
    $stacked = get_parameter('stacked', CUSTOM_GRAPH_LINE);
    $width = get_parameter('width', 0);
    $height = get_parameter('height', 0);
    $events = get_parameter('events', 0);
    $period = get_parameter('period', 0);
    $fullscale = get_parameter('fullscale', 0);

    $result = (bool) custom_graphs_create(
        $id_modules,
        $name,
        $description,
        $stacked,
        $width,
        $height,
        $events,
        $period,
        0,
        0,
        false,
        $fullscale
    );

    $return['correct'] = $result;

    echo json_encode($return);
    return;
}

if ($print_custom_graph === true) {
    ob_clean();

    $width_value = (int) get_parameter('width', CHART_DEFAULT_WIDTH);

    if ($width_value === -1) {
        $width_value = '95%';
    }

    $params = [
        'period'          => (int) get_parameter('period', SECONDS_5MINUTES),
        'width'           => $width_value,
        'height'          => (int) get_parameter('height', CHART_DEFAULT_HEIGHT),
        'unit_name'       => get_parameter('unit_list', []),
        'date'            => (int) get_parameter('date', time()),
        'only_image'      => (bool) get_parameter('only_image', false),
        'homeurl'         => (string) get_parameter('homeurl', ''),
        'ttl'             => (int) get_parameter('ttl', 1),
        'vconsole'        => (bool) get_parameter('vconsole', false),
        'fullscale'       => (bool) get_parameter('fullscale', false),
        'backgroundColor' => (string) get_parameter('background_color', 'white'),
        'show_alerts'     => (bool) get_parameter('show_alerts'),
        'show_events'     => (bool) get_parameter('show_events'),
        'type_graph'      => get_parameter('type_g', $config['type_module_charts']),
    ];

    $params_combined = [
        'stacked'        => (int) get_parameter('stacked', CUSTOM_GRAPH_LINE),
        'labels'         => get_parameter('name_list', []),
        'modules_series' => get_parameter('modules_param', []),
        'id_graph'       => (int) get_parameter('id_graph', 0),
        'return'         => 1,
    ];

    echo graphic_combined_module(
        get_parameter('modules_param', []),
        $params,
        $params_combined
    );
    return;
}

if ($print_sparse_graph) {
    ob_clean();
    $params = [
        'agent_module_id'     => (int) get_parameter('agent_module_id'),
        'period'              => (int) get_parameter('period', SECONDS_5MINUTES),
        'show_events'         => (bool) get_parameter('show_events'),
        'title'               => (string) get_parameter('title'),
        'unit_name'           => (string) get_parameter('unit_name'),
        'show_alerts'         => (bool) get_parameter('show_alerts'),
        'pure'                => (bool) get_parameter('pure'),
        'date'                => (int) get_parameter('date', time()),
        'unit'                => (string) get_parameter('unit'),
        'baseline'            => (int) get_parameter('baseline'),
        'return_data'         => (int) get_parameter('return_data'),
        'show_title'          => (bool) get_parameter('show_title', true),
        'only_image'          => (bool) get_parameter('only_image'),
        'homeurl'             => (string) get_parameter('homeurl'),
        'ttl'                 => (int) get_parameter('ttl', 1),
        'projection'          => (bool) get_parameter('projection'),
        'adapt_key'           => (string) get_parameter('adapt_key'),
        'compare'             => (bool) get_parameter('compare'),
        'show_unknown'        => (bool) get_parameter('show_unknown'),
        'menu'                => (bool) get_parameter('menu', true),
        'backgroundColor'     => (string) get_parameter('background_color', 'white'),
        'percentil'           => get_parameter('percentil', null),
        'vconsole'            => (bool) get_parameter('vconsole'),
        'type_graph'          => get_parameter('type_g', $config['type_module_charts']),
        'fullscale'           => get_parameter('fullscale', 0),
        'id_widget_dashboard' => false,
        'force_interval'      => '',
        'time_interval'       => 300,
        'array_data_create'   => 0,
    ];

    echo grafico_modulo_sparse($params);
    return;
}

if ($get_graphs) {
    $id_container = get_parameter('id_container', 0);
    // config token max_graph
    $max_graph = $config['max_graph_container'];
    $result_items = db_get_all_rows_sql('SELECT * FROM tcontainer_item WHERE id_container = '.$id_container);
    if (!empty($result_items)) {
        $hash = get_parameter('hash', 0);
        $period = get_parameter('time', 0);

        $periods = [];
        $periods[1] = __('none');
        $periods[SECONDS_1HOUR] = __('1 hour');
        $periods[SECONDS_2HOUR] = sprintf(__('%s hours'), '2 ');
        $periods[SECONDS_6HOURS] = sprintf(__('%s hours'), '6 ');
        $periods[SECONDS_12HOURS] = sprintf(__('%s hours'), '12 ');
        $periods[SECONDS_1DAY] = __('1 day');
        $periods[SECONDS_2DAY] = sprintf(__('%s days'), '2 ');
        $periods[SECONDS_5DAY] = sprintf(__('%s days'), '5 ');
        $periods[SECONDS_1WEEK] = __('1 week');
        $periods[SECONDS_15DAYS] = __('15 days');
        $periods[SECONDS_1MONTH] = __('1 month');

        $table = '';
        $single_table = "<table width='100%' cellpadding=4 cellspacing=4>";
            $single_table .= "<tr id='row_time_lapse' class='datos'>";
                $single_table .= "<td class='bolder w12p'>";
                    $single_table .= __('Time container lapse');
                    // $single_table .= ui_print_help_tip(__('This is the range, or period of time over which the report renders the information for this report type. For example, a week means data from a week ago from now. '),true);
                $single_table .= '</td>';
                $single_table .= '<td>';
                    $single_table .= html_print_extended_select_for_time(
                        'period_container_'.$hash,
                        $period,
                        '',
                        '',
                        '0',
                        10,
                        true,
                        'font-size: 9pt;width: 130px;',
                        true,
                        '',
                        false,
                        $periods,
                        'vertical-align: middle; margin-bottom:0;'
                    );
                    $single_table .= html_print_image('images/refresh_mc.png', true, ['id' => 'refresh_custom_time', 'title' => 'Refresh graph', 'style' => 'cursor:pointer; vertical-align: middle; display:none;']);
                $single_table .= '</td>';
            $single_table .= '</tr>';
        $single_table .= '</table>';

        $table .= $single_table;
        $contador = $config['max_graph_container'];
        foreach ($result_items as $key => $value) {
            $table .= '</br>';
            if ($period > 1) {
                $value['time_lapse'] = $period;
            }

            $type_graph = ($value['type_graph']) ? 'line' : 'area';

            switch ($value['type']) {
                case 'simple_graph':
                    if ($contador > 0) {
                        $sql_modulo = db_get_all_rows_sql(
                            'SELECT nombre, id_agente FROM 
							tagente_modulo WHERE id_agente_modulo = '.$value['id_agent_module']
                        );
                        $sql_alias = db_get_all_rows_sql(
                            'SELECT alias from tagente 
							WHERE id_agente = '.$sql_modulo[0]['id_agente']
                        );
                        $table .= "<div class='w90p'><h4>AGENT ".$sql_alias[0]['alias'].' MODULE '.$sql_modulo[0]['nombre'].'</h4><hr></div>';

                        $params = [
                            'agent_module_id' => $value['id_agent_module'],
                            'period'          => $value['time_lapse'],
                            'homeurl'         => ui_get_full_url(false, false, false, false),
                            'type_graph'      => $type_graph,
                            'fullscale'       => $value['fullscale'],
                        ];

                        $table .= grafico_modulo_sparse($params);
                        $contador--;
                    }

                    // $table .= "</br>";
                break;

                case 'custom_graph':
                    if ($contador > 0) {
                        $graph_conf = db_get_row('tgraph', 'id_graph', $value['id_graph']);

                        if ($graph_conf['stacked'] == 4 || $graph_conf['stacked'] == 9) {
                            $height = 50;
                        } else if ($graph_conf['stacked'] == 5) {
                            $height = 200;
                        } else {
                            $height = 300;
                        }

                        $table .= "<div class='w90p'><h4>".$graph[0]['name'].'</h4><hr></div>';

                        $params = [
                            'period'    => $value['time_lapse'],
                            'width'     => 1000,
                            'height'    => $height,
                            'percentil' => $graph_conf['percentil'] == 1,
                            'fullscale' => $value['fullscale'],
                        ];

                        $params_combined = [
                            'stacked'  => $graph_conf['stacked'],
                            'id_graph' => $value['id_graph'],
                        ];

                        $table .= graphic_combined_module(
                            false,
                            $params,
                            $params_combined
                        );

                        $contador--;
                    }
                break;

                case 'dynamic_graph':
                    if ($value['agent'] != '') {
                        if (@preg_match($value['agent'], '') !== false) {
                            $alias = " AND alias REGEXP '".$value['agent']."'";
                        } else {
                            // Not a valid REGEXP.
                            $alias = " AND alias LIKE '".$value['agent']."'";
                        }
                    }

                    if ($value['id_group'] === '0') {
                        $id_group = '';
                    } else {
                        $id_group = ' AND id_grupo = '.$value['id_group'];
                    }

                    if ($value['id_module_group'] === '0') {
                        $id_module_group = '';
                    } else {
                        $id_module_group = ' AND id_module_group = '.$value['id_module_group'];
                    }

                    if ($value['id_tag'] === '0') {
                        $tag = '';
                        $id_tag = '';
                    } else {
                        $tag = ' INNER JOIN ttag_module ON ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo ';
                        $id_tag = ' AND ttag_module.id_tag = '.$value['id_tag'];
                    }

                    if ($value['module'] != '') {
                        if (@preg_match($value['module'], '') !== false) {
                            $module_name = " AND nombre REGEXP '".$value['module']."'";
                        } else {
                            $module_name = " AND nombre LIKE '".$value['module']."'";
                        }
                    }

                    $id_agent_module = db_get_all_rows_sql(
                        'SELECT tagente_modulo.id_agente_modulo FROM tagente_modulo 
						'.$tag.'WHERE  1=1'.$id_module_group.$module_name.' AND id_agente IN (SELECT id_agente FROM tagente WHERE 1=1'.$alias.$id_group.')'.$id_tag
                    );

                    foreach ($id_agent_module as $key2 => $value2) {
                        if ($contador > 0) {
                            $sql_modulo2 = db_get_all_rows_sql(
                                'SELECT nombre, id_agente FROM 
								tagente_modulo WHERE id_agente_modulo = '.$value2['id_agente_modulo']
                            );

                            $sql_alias2 = db_get_all_rows_sql(
                                'SELECT alias from tagente 
								WHERE id_agente = '.$sql_modulo2[0]['id_agente']
                            );

                            $table .= "<div class='w90p'><h4>AGENT ".$sql_alias2[0]['alias'].' MODULE '.$sql_modulo2[0]['nombre'].'</h4><hr></div>';

                            $params = [
                                'agent_module_id' => $value2['id_agente_modulo'],
                                'period'          => $value['time_lapse'],
                                'homeurl'         => ui_get_full_url(false, false, false, false),
                                'type_graph'      => $type_graph,
                                'fullscale'       => $value['fullscale'],
                            ];

                            $table .= grafico_modulo_sparse($params);
                            $contador--;
                        }
                    }
                break;
            }
        }

        $table .= '</br>';
        echo $table;
        return;
    }
}
