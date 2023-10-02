<?php
/**
 * Events element for tactical view.
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
 * Events, this class contain all logic for this section.
 */
class Events extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = __('Events');
        $this->ajaxMethods = [
            'getEventsGraph',
            'getEventsCriticalityGraph',
            'getEventsStatusValidateGraph',
        ];
    }


    /**
     * Return the html graph of events in last 24h.
     *
     * @return string
     */
    public function getEventsGraph():string
    {
        $interval24h = (time() - 86400);
        $sql = 'SELECT
                utimestamp,
                DATE_FORMAT(FROM_UNIXTIME(utimestamp), "%Y-%m-%d %H:00:00") AS hour,
                COUNT(*) AS number_of_events
                FROM tevento
                WHERE utimestamp >= '.$interval24h.'
                GROUP BY hour
                ORDER BY hour
                LIMIT 24;';

        $sqlTest = 'SELECT
                    utimestamp,
                    DATE_FORMAT(FROM_UNIXTIME(utimestamp), "%Y-%m-%d %H:00:00") AS hour,
                    COUNT(*) AS number_of_events
                    FROM tevento
                    WHERE utimestamp >= 1693296001
                    GROUP BY hour
                    ORDER BY hour
                    LIMIT 24;';

        $rows = db_process_sql($sql);

        $graph_values = [];
        $colors = [];
        $max_value = 0;
        foreach ($rows as $key => $row) {
            if ($max_value < $row['number_of_events']) {
                $max_value = $row['number_of_events'];
            }

            $graph_values[] = [
                'y' => $row['number_of_events'],
                'x' => date('d-m-Y H:00:00', $row['utimestamp']),
            ];
        }

        $danger = $max_value;
        $ok = ($max_value / 3);

        foreach ($graph_values as $key => $value) {
            if ($value['y'] >= $danger) {
                $colors[] = '#EC7176';
            }

            if ($value['y'] >= $ok && $value['y'] < $danger) {
                $colors[] = '#FCAB10';
            }

            if ($value['y'] < $ok) {
                $colors[] = '#82B92E';
            }
        }

        $options = [
            'height'       => 237,
            'legend'       => ['display' => false],
            'scales'       => [
                'x' => [
                    'bounds'  => 'data',
                    'grid'    => ['display' => false],
                    'display' => false,
                ],
                'y' => [
                    'grid' => ['display' => false],
                ],
            ],
            'colors'       => $colors,
            'borderColors' => ['#ffffff'],
        ];

        $bar = vbar_graph($graph_values, $options);

        $output = html_print_div(
            [
                'content' => $bar,
                'class'   => 'margin-top-5 w100p relative',
                'style'   => 'max-height: 250px;',
            ],
            true
        );

        return $output;
    }


    /**
     * Return the html graph of events in last 8h grouped by criticity.
     *
     * @return string
     */
    public function getEventsCriticalityGraph():string
    {
        $interval8h = (time() - 86400);
        $sql = 'SELECT criticity, count(*)  AS total
        FROM tevento
        WHERE utimestamp >= '.$interval8h.'
        group by criticity';

        $sqlTest = 'SELECT criticity, count(*)  AS total
        FROM tevento
        group by criticity';

        $rows = db_process_sql($sql);

        $labels = [];
        $data = [];
        $colors = [];
        foreach ($rows as $key => $row) {
            switch ($row['criticity']) {
                case EVENT_CRIT_CRITICAL:
                    $label = __('CRITICAL');
                    $colors[] = COL_CRITICAL;
                break;

                case EVENT_CRIT_MAINTENANCE:
                    $label = __('MAINTENANCE');
                    $colors[] = COL_MAINTENANCE;
                break;

                case EVENT_CRIT_INFORMATIONAL:
                    $label = __('INFORMATIONAL');
                    $colors[] = COL_INFORMATIONAL;
                break;

                case EVENT_CRIT_MAJOR:
                    $label = __('MAJOR');
                    $colors[] = COL_MAJOR;
                break;

                case EVENT_CRIT_MINOR:
                    $label = __('MINOR');
                    $colors[] = COL_MINOR;
                break;

                case EVENT_CRIT_NORMAL:
                    $label = __('NORMAL');
                    $colors[] = COL_NORMAL;
                break;

                case EVENT_CRIT_WARNING:
                    $label = __('WARNING');
                    $colors[] = COL_WARNING;
                break;

                default:
                    $colors[] = COL_UNKNOWN;
                    $label = __('UNKNOWN');
                break;
            }

            $labels[] = $this->controlSizeText($label);
            $data[] = $row['total'];
        }

        $options = [
            'labels'       => $labels,
            'legend'       => ['display' => false],
            'cutout'       => 80,
            'nodata_image' => ['width' => '100%'],
            'colors'       => $colors,
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
     * Return the html graph of events in last 8h grouped by status validate.
     *
     * @return string
     */
    public function getEventsStatusValidateGraph():string
    {
        $interval8h = (time() - 86400);
        $sql = 'SELECT estado, count(*)  AS total
        FROM tevento
        WHERE utimestamp >= '.$interval8h.'
        group by estado';

        $sqlTest = 'SELECT estado, count(*)  AS total
        FROM tevento
        WHERE utimestamp <= 1688981702
        group by estado';

        $rows = db_process_sql($sql);

        $labels = [];
        $data = [];
        foreach ($rows as $key => $row) {
            switch ($row['estado']) {
                case '2':
                    $label = _('In process');
                break;

                case '0':
                    $label = _('New events');
                break;

                case '3':
                    $label = _('Not validated');
                break;

                case '1':
                    $label = _('Validated events');
                break;

                default:
                    $label = __('Unknow');
                break;
            }

            $labels[] = $label;
            $data[] = $row['total'];
        }

        $options = [
            'labels'       => $labels,
            'legend'       => ['display' => false],
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
     * Return the datatable events in last 8 hours.
     *
     * @return string
     */
    public function getDataTableEvents()
    {
        $column_names = [
            __('S'),
            __('Event'),
            __('Date'),
        ];

        $fields = [
            'mini_severity',
            'evento',
            'timestamp',
        ];
        return ui_print_datatable(
            [
                'id'                             => 'datatable_events',
                'class'                          => 'info_table events',
                'style'                          => 'width: 90%;',
                'ajax_url'                       => 'operation/events/events',
                'ajax_data'                      => [
                    'get_events'   => 1,
                    'compact_date' => 1,
                ],
                'order'                          => [
                    'field'     => 'timestamp',
                    'direction' => 'desc',
                ],
                'column_names'                   => $column_names,
                'columns'                        => $fields,
                'ajax_return_operation'          => 'buffers',
                'ajax_return_operation_function' => 'process_buffers',
                'return'                         => true,
                'csv'                            => 0,
                'dom_elements'                   => 'tfp',
                'default_pagination'             => 8,
            ]
        );
    }


}
