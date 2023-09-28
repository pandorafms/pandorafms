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
        parent::__construct();
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
        // TODO connect to automonitorization.
        return html_print_div(
            [
                'content' => '9.999.999',
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
        // TODO connect to automonitorization.
        return html_print_div(
            [
                'content' => '9.999.999',
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
                'no-filtered'         => [-1],
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
     * @return void
     */
    public function getGroups():void
    {
        global $config;

        $start  = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $pagination = '';
        $order = '';

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
                    GROUP BY a.id_grupo ORDER BY total DESC LIMIT 20
                ) top_groups ON top_groups.id_grupo = gr.id_grupo
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
                                GROUP BY a.id_grupo ORDER BY total DESC LIMIT 20
                            ) top_groups ON top_groups.id_grupo = gr.id_grupo
                          GROUP BY a.id_grupo
                          ORDER BY total DESC';

            $total = db_get_num_rows($sql_count);

            echo json_encode(
                [
                    'data'            => $rows,
                    'recordsTotal'    => $total,
                    'recordsFiltered' => $total,
                ]
            );

            // Capture output.
            $response = ob_get_clean();
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo $response;
        } else {
            echo json_encode(
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
        $sql = 'SELECT name, count(*) AS total
                FROM tagente a
                LEFT JOIN tconfig_os os ON os.id_os = a.id_os
                GROUP BY a.id_os
                ORDER BY total DESC';
        $rows = db_process_sql($sql);

        $labels = [];
        $data = [];
        foreach ($rows as $key => $row) {
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
        // TODO Find the method for calculate status in agents.
        $labels = [];
        $data = [];
        foreach ([] as $key => $row) {
            $labels[] = $this->controlSizeText($row['alias']);
            $data[] = $row['status'];
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


}
