<?php
/**
 * Alerts element for tactical view.
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
 * Alerts, this class contain all logic for this section.
 */
class Alerts extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = __('Alerts');
        $this->ajaxMethods = ['getUsers'];
        $this->ajaxMethods = [
            'getUsers',
            'getCurrentlyTriggered',
            'getActiveCorrelation',
        ];
        $this->interval = 300000;
        $this->refreshConfig = [
            'triggered'          => [
                'id'     => 'currently-triggered',
                'method' => 'getCurrentlyTriggered',
            ],
            'active-correlation' => [
                'id'     => 'active-correlation',
                'method' => 'getActiveCorrelation',
            ],
        ];
    }


    /**
     * Returns the html of currently triggered.
     *
     * @return string
     */
    public function getCurrentlyTriggered():string
    {
        $value = $this->valueMonitoring('triggered_alerts');
        $total = round($value[0]['datos']);
        return html_print_div(
            [
                'content' => $total,
                'class'   => 'text-l',
                'id'      => 'currently-triggered',
                'style'   => 'margin: 0px 10px 10px 10px;',
            ],
            true
        );
    }


    /**
     * Returns the html of active correlation.
     *
     * @return string
     */
    public function getActiveCorrelation():string
    {
        $value = $this->valueMonitoring('triggered_correlative_alerts');
        $total = round($value[0]['datos']);
        return html_print_div(
            [
                'content' => $total,
                'class'   => 'text-l',
                'id'      => 'active-correlation',
                'style'   => 'margin: 0px 10px 10px 10px;',
            ],
            true
        );
    }


    /**
     * Return a datatable with de users lists.
     *
     * @return string
     */
    public function getDataTableUsers():string
    {
        $columns = [
            'id_user',
            'is_admin',
            'last_connect',
        ];

        $columnNames = [
            __('User'),
            __('Role'),
            __('Last seen'),
        ];

        return ui_print_datatable(
            [
                'id'                  => 'list_users',
                'class'               => 'info_table',
                'style'               => 'width: 90%',
                'dom_elements'        => 'tfp',
                'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
                'columns'             => $columns,
                'column_names'        => $columnNames,
                'ajax_url'            => $this->ajaxController,
                'ajax_data'           => [
                    'method' => 'getUsers',
                    'class'  => static::class,
                ],
                'order'               => [
                    'field'     => 'title',
                    'direction' => 'asc',
                ],
                'default_pagination'  => 10,
                'search_button_class' => 'sub filter float-right',
                'return'              => true,
            ]
        );
    }


    /**
     * Return all users for ajax.
     *
     * @return string
     */
    public function getUsers():string
    {
        global $config;

        $start  = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $orderDatatable = get_datatable_order(true);
        $pagination = '';
        $order = '';

        try {
            ob_start();
            if (isset($orderDatatable)) {
                $order = sprintf(
                    ' ORDER BY %s %s',
                    $orderDatatable['field'],
                    $orderDatatable['direction']
                );
            }

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
                'SELECT id_user, is_admin ,last_connect
                FROM tusuario u %s %s',
                $order,
                $pagination
            );

            $rows = db_process_sql($sql);

            foreach ($rows as $key => $row) {
                if ((bool) $row['is_admin'] === true) {
                    $rows[$key]['is_admin'] = '<span class="admin">'.__('Admin').'</span>';
                } else {
                    $rows[$key]['is_admin'] = '<span class="user">'.__('User').'</span>';
                }

                if ($row['last_connect'] > 0) {
                    $rows[$key]['last_connect'] = ui_print_timestamp($row['last_connect'], true, ['prominent' => 'compact']);
                } else {
                    $rows[$key]['last_connect'] = __('Unknown');
                }
            }

            $sql_count = sprintf(
                'SELECT count(*) as total FROM tusuario %s',
                $order,
            );

            $total = db_process_sql($sql_count);

            // Capture output.
            $response = ob_get_clean();

            return json_encode(
                [
                    'data'            => $rows,
                    'recordsTotal'    => $total[0]['total'],
                    'recordsFiltered' => $total[0]['total'],
                ]
            );
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }

        json_decode($response);
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


}
