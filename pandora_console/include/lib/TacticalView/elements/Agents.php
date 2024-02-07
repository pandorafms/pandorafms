<?php
/**
 * Agents element for tactical view.
 *
 * @category   General
 * @package    Pandora FMS
 * @subpackage TacticalView
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

use PandoraFMS\TacticalView\Element;

/**
 * Agents, this class contain all logic for this section.
 */
class Agents extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        global $config;
        parent::__construct();
        include_once $config['homedir'].'/include/graphs/fgraph.php';
        include_once $config['homedir'].'/include/functions_graph.php';
        $this->title = __('Agents');
        $this->ajaxMethods = ['getGroups'];
    }


    /**
     * Get total number of agents.
     *
     * @return string
     */
    public function getTotalAgents():string
    {
        $agents = agents_get_agents();
        if (is_array($agents) === true) {
            $total = count($agents);
        } else {
            $total = 0;
        }

        return html_print_div(
            [
                'content' => format_numeric($total, 0),
                'class'   => 'text-l',
                'style'   => 'margin: 0px 10px 10px 10px;',
            ],
            true
        );
    }


    /**
     * Get total alerts of agents.
     *
     * @return string
     */
    public function getAlerts():string
    {
        global $config;
        $id_groups = array_keys(users_get_groups($config['id_user'], 'AR', false));
        if (in_array(0, $id_groups) === false) {
            foreach ($id_groups as $key => $id_group) {
                if ((bool) check_acl_restricted_all($config['id_user'], $id_group, 'AR') === false) {
                    unset($id_groups[$key]);
                }
            }
        }

        if (users_can_manage_group_all() === true) {
            $id_groups[] = 0;
        }

        $id_groups = implode(',', $id_groups);

        $group_query = ' AND (
            t3.id_grupo IN ('.$id_groups.')
            OR tasg.id_group IN ('.$id_groups.')
        )';
        $sql = 'SELECT count(t0.id)
		FROM talert_template_modules t0
        INNER JOIN talert_templates t1
			ON t0.id_alert_template = t1.id
		INNER JOIN tagente_modulo t2
			ON t0.id_agent_module = t2.id_agente_modulo
		INNER JOIN tagente t3
			ON t2.id_agente = t3.id_agente
		LEFT JOIN tagent_secondary_group tasg
			ON tasg.id_agent = t3.id_agente
		WHERE last_fired >=UNIX_TIMESTAMP(NOW() - INTERVAL 1 DAY) '.$group_query;

        $total = db_get_value_sql($sql);
        return html_print_div(
            [
                'content' => format_numeric($total, 0),
                'class'   => 'text-l',
                'style'   => 'margin: 0px 10px 10px 10px;',
            ],
            true
        );
    }


    /**
     * Get a datatable with the top groups with more agents.
     *
     * @return string
     */
    public function getDataTableGroups():string
    {
        $columns = [
            'nombre',
            'total',
        ];

        $columnNames = [
            __('Group alias'),
            __('Agents'),
        ];

        return ui_print_datatable(
            [
                'id'                  => 'list_groups',
                'class'               => 'info_table',
                'style'               => 'width: 90%',
                'dom_elements'        => 'tfp',
                'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
                'columns'             => $columns,
                'column_names'        => $columnNames,
                'ajax_url'            => $this->ajaxController,
                'no_sortable_columns' => [
                    0,
                    1,
                ],
                'ajax_data'           => [
                    'method' => 'getGroups',
                    'class'  => static::class,
                ],
                'order'               => [
                    'field'     => 'title',
                    'direction' => 'asc',
                ],
                'default_pagination'  => 8,
                'search_button_class' => 'sub filter float-right',
                'return'              => true,
            ]
        );
    }


    /**
     * Return top 20 groups with more agents for ajax datatable.
     *
     * @return string
     */
    public function getGroups():string
    {
        global $config;

        $start  = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $pagination = '';

        $id_groups = array_keys(users_get_groups($config['id_user'], 'AR', false));

        if (in_array(0, $id_groups) === false) {
            foreach ($id_groups as $key => $id_group) {
                if ((bool) check_acl_restricted_all($config['id_user'], $id_group, 'AR') === false) {
                    unset($id_groups[$key]);
                }
            }
        }

        $id_groups = implode(',', $id_groups);

        try {
            ob_start();

            if (isset($length) && $length > 0
                && isset($start) && $start >= 0
            ) {
                $pagination = sprintf(
                    ' LIMIT %d OFFSET %d ',
                    $length,
                    $start
                );
            }

            $sql = sprintf(
                'SELECT gr.nombre, count(*) +
                IFNULL((SELECT count(*) AS total
                        FROM tagente second_a
                        LEFT JOIN tagent_secondary_group second_g ON second_g.id_agent = second_a.id_agente
                        WHERE a.id_grupo = second_g.id_group
                        GROUP BY second_g.id_group
                        ), 0) AS total
                FROM tagente a
                LEFT JOIN tagent_secondary_group g ON g.id_agent = a.id_agente
                LEFT JOIN tgrupo gr ON gr.id_grupo = a.id_grupo
                INNER JOIN(
                    SELECT gr.id_grupo, count(*) AS total 
                    FROM tagente a LEFT JOIN tagent_secondary_group g ON g.id_agent = a.id_agente 
                    LEFT JOIN tgrupo gr ON gr.id_grupo = a.id_grupo 
                    WHERE a.id_grupo IN ('.$id_groups.') OR g.id_group IN ('.$id_groups.')
                    GROUP BY a.id_grupo ORDER BY total DESC LIMIT 20
                ) top_groups ON top_groups.id_grupo = gr.id_grupo
                WHERE a.id_grupo IN ('.$id_groups.') OR g.id_group IN ('.$id_groups.')
                GROUP BY a.id_grupo
                ORDER BY total DESC
                %s',
                $pagination
            );

            $rows = db_process_sql($sql);

            $sql_count = 'SELECT gr.nombre, 
                            IFNULL((SELECT count(*) AS total
                            FROM tagente second_a
                            LEFT JOIN tagent_secondary_group second_g ON second_g.id_agent = second_a.id_agente
                            WHERE a.id_grupo = second_g.id_group
                            GROUP BY second_g.id_group
                            ), 0) AS total
                          FROM tagente a
                          LEFT JOIN tagent_secondary_group g ON g.id_agent = a.id_agente
                          LEFT JOIN tgrupo gr ON gr.id_grupo = a.id_grupo
                          INNER JOIN(
                                SELECT gr.id_grupo, count(*) AS total
                                FROM tagente a LEFT JOIN tagent_secondary_group g ON g.id_agent = a.id_agente
                                LEFT JOIN tgrupo gr ON gr.id_grupo = a.id_grupo
                                WHERE a.id_grupo IN ('.$id_groups.') OR g.id_group IN ('.$id_groups.')
                                GROUP BY a.id_grupo ORDER BY total DESC LIMIT 20
                            ) top_groups ON top_groups.id_grupo = gr.id_grupo
                          WHERE a.id_grupo IN ('.$id_groups.') OR g.id_group IN ('.$id_groups.')
                          GROUP BY a.id_grupo
                          ORDER BY total DESC';

            $total = db_get_num_rows($sql_count);

            // Capture output.
            $response = ob_get_clean();

            return json_encode(
                [
                    'data'            => $rows,
                    'recordsTotal'    => $total,
                    'recordsFiltered' => $total,
                ]
            );
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }

        return json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $response;
        } else {
            return json_encode(
                [
                    'success' => false,
                    'error'   => $response,
                ]
            );
        }
    }


    /**
     * Return the html graph of number agents by os.
     *
     * @return string
     */
    public function getOperatingSystemGraph():string
    {
        global $config;
        $id_groups = array_keys(users_get_groups($config['id_user'], 'AR', false));

        if (in_array(0, $id_groups) === false) {
            foreach ($id_groups as $key => $id_group) {
                if ((bool) check_acl_restricted_all($config['id_user'], $id_group, 'AR') === false) {
                    unset($id_groups[$key]);
                }
            }
        }

        $id_groups = implode(',', $id_groups);

        $sql = 'SELECT name, count(*) AS total
                FROM tagente a
                LEFT JOIN tagent_secondary_group g ON g.id_agent = a.id_agente
                LEFT JOIN tgrupo gr ON gr.id_grupo = a.id_grupo
                LEFT JOIN tconfig_os os ON os.id_os = a.id_os
                WHERE a.id_grupo IN ('.$id_groups.') OR g.id_group IN ('.$id_groups.')
                GROUP BY a.id_os
                ORDER BY total DESC';
        $rows = db_process_sql($sql);

        $labels = [];
        $data = [];
        foreach ($rows as $key => $row) {
            if (empty($row['name']) === true) {
                continue;
            }

            $labels[] = $this->controlSizeText($row['name']);
            $data[] = $row['total'];
        }

        $options = [
            'labels'       => $labels,
            'legend'       => [
                'position' => 'bottom',
                'align'    => 'right',
                'display'  => false,
            ],
            'cutout'       => 80,
            'nodata_image' => ['width' => '100%'],
        ];
        $pie = ring_graph($data, $options);
        $output = html_print_div(
            [
                'content' => $pie,
                'style'   => 'margin: 0 auto; max-width: 80%; max-height: 220px;',
            ],
            true
        );

        return $output;
    }


    /**
     * Return the html graph of number agents by status.
     *
     * @return string
     */
    public function getStatusGraph():string
    {
        $data = [];
        $agents = agents_get_agents(
            false,
            [
                'id_agente',
                'id_grupo',
                'nombre',
                'alias',
                'id_os',
                'ultimo_contacto',
                'intervalo',
                'comentarios description',
                'quiet',
                'normal_count',
                'warning_count',
                'critical_count',
                'unknown_count',
                'notinit_count',
                'total_count',
                'fired_count',
                'ultimo_contacto_remoto',
                'remote',
                'agent_version',
            ]
        );
        $labels = [
            __('No Monitors'),
            __('CRITICAL'),
            __('WARNING'),
            __('UKNOWN'),
            __('NORMAL'),
        ];
        $totals = [
            'no_monitors' => 0,
            'critical'    => 0,
            'warning'     => 0,
            'unknown'     => 0,
            'ok'          => 0,
        ];

        $colors = [
            COL_NOTINIT,
            COL_CRITICAL,
            COL_WARNING,
            COL_UNKNOWN,
            COL_NORMAL,
        ];

        foreach ($agents as $key => $agent) {
            if ($agent['total_count'] == 0 || $agent['total_count'] == $agent['notinit_count']) {
                $totals['no_monitors']++;
            }

            if ($agent['critical_count'] > 0) {
                $totals['critical']++;
            } else if ($agent['warning_count'] > 0) {
                $totals['warning']++;
            } else if ($agent['unknown_count'] > 0) {
                $totals['unknown']++;
            } else {
                $totals['ok']++;
            }
        }

        foreach ($totals as $key => $total) {
            $data[] = $total;
        }

        $options = [
            'labels'       => $labels,
            'legend'       => [
                'position' => 'bottom',
                'align'    => 'right',
                'display'  => false,
            ],
            'cutout'       => 80,
            'nodata_image' => ['width' => '80%'],
            'colors'       => $colors,
            'onClick'      => 'redirectAgentStatus',
        ];

        // To avoid that if a value is too small it is not seen.
        $percentages = [];
        $total = array_sum($data);
        foreach ($data as $key => $value) {
            if ($total > 0) {
                $percentage = (($value / $total) * 100);
                if ($percentage < 1 && $percentage > 0) {
                    $percentage = 1;
                }

                $percentages[$key] = format_numeric($percentage, 0);
            } else {
                $percentages[$key] = '0%';
            }
        }

        $data = $percentages;

        $pie = ring_graph($data, $options);
        $output = html_print_div(
            [
                'content' => $pie,
                'style'   => 'margin: 0 auto; max-width: 80%; max-height: 220px;',
                'class'   => 'clickable',
            ],
            true
        );

        return $output;
    }


}
