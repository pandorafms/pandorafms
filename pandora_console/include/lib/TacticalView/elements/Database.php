<?php
/**
 * Database element for tactical view.
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
 * Database, this class contain all logic for this section.
 */
class Database extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        global $config;
        parent::__construct();
        include_once $config['homedir'].'/include/graphs/fgraph.php';
        $this->title = __('Database');
        $this->ajaxMethods = [
            'getStatus',
            'getDataRecords',
            'getEvents',
            'getStringRecords',
            'getReadsGraph',
            'getWritesGraph',
        ];
        $this->interval = 300000;
        $this->refreshConfig = [
            'status'       => [
                'id'     => 'status-database',
                'method' => 'getStatus',
            ],
            'records'      => [
                'id'     => 'data-records',
                'method' => 'getDataRecords',
            ],
            'events'       => [
                'id'     => 'total-events',
                'method' => 'getEvents',
            ],
            'totalRecords' => [
                'id'     => 'total-records',
                'method' => 'getStringRecords',

            ],
            'reads'        => [
                'id'     => 'database-reads',
                'method' => 'getReadsGraph',
            ],
            'writes'       => [
                'id'     => 'database-writes',
                'method' => 'getWritesGraph',
            ],
        ];
    }


    /**
     * Returns the html status of database.
     *
     * @return string
     */
    public function getStatus():string
    {
        // TODO connect to automonitorization.
        $status = true;

        if ($status === true) {
            $image_status = html_print_image('images/status_check@svg.svg', true);
            $text = html_print_div(
                [
                    'content' => __('Everything’s OK!'),
                    'class'   => 'status-text',
                ],
                true
            );
        } else {
            $image_status = html_print_image('images/status_error@svg.svg', true);
            $text = html_print_div(
                [
                    'content' => __('Something’s wrong'),
                    'class'   => 'status-text',
                ],
                true
            );
        }

        $output = $image_status.$text;

        return html_print_div(
            [
                'content' => $output,
                'class'   => 'flex_center margin-top-5',
                'id'      => 'status-database',
                'style'   => 'margin: 0px 10px 10px 10px;',
            ],
            true
        );
    }


    /**
     * Returns the html records data of database.
     *
     * @return string
     */
    public function getDataRecords():string
    {
        $data = $this->valueMonitoring('mysql_size_of_data');
        $value = format_numeric($data[0]['datos'], 2).' MB';
        return html_print_div(
            [
                'content' => $value,
                'class'   => 'text-l',
                'id'      => 'data-records',
                'style'   => 'margin: 0px 10px 10px 10px;',
            ],
            true
        );
    }


    /**
     * Returns the html of total events.
     *
     * @return string
     */
    public function getEvents():string
    {
        $data = $this->valueMonitoring('last_events_24h');
        $value = format_numeric($data[0]['datos']);
        return html_print_div(
            [
                'content' => $value,
                'class'   => 'text-l',
                'id'      => 'total-events',
                'style'   => 'margin: 0px 10px 10px 10px;',
            ],
            true
        );
    }


    /**
     * Returns the html of total records.
     *
     * @return string
     */
    public function getStringRecords():string
    {
        $data = $this->valueMonitoring('total_string_data');
        $value = format_numeric($data[0]['datos']);
        return html_print_div(
            [
                'content' => $value,
                'class'   => 'text-l',
                'id'      => 'total-records',
                'style'   => 'margin: 0px 10px 10px 10px;',
            ],
            true
        );
    }


    /**
     * Returns the html of total reads database in a graph.
     *
     * @return string
     */
    public function getReadsGraph():string
    {
        $dateInit = (time() - 86400);
        $reads = $this->valueMonitoring('mysql_questions_reads', $dateInit, time());
        $dates = [];
        $string_reads = [];
        $total = 0;
        foreach ($reads as $key => $read) {
            if (isset($read['utimestamp']) === false) {
                $read['utimestamp'] = 0;
            }

            $dates[] = date('d-m-Y H:i:s', $read['utimestamp']);
            $string_reads[] = $read['datos'];
            $total += $read['datos'];
        }

        $options = [
            'labels'   => $dates,
            'legend'   => [ 'display' => false ],
            'tooltips' => [ 'display' => false ],
            'scales'   => [
                'y' => [
                    'grid'    => ['display' => false],
                    'ticks'   => ['display' => false],
                    'display' => false,
                ],
                'x' => [
                    'grid'    => ['display' => false],
                    'display' => false,
                ],
            ],
            'elements' => [ 'point' => [ 'radius' => 0 ] ],
        ];

        $data = [
            [
                'backgroundColor'       => '#EC7176',
                'borderColor'           => '#EC7176',
                'pointBackgroundColor'  => '#EC7176',
                'pointHoverBorderColor' => '#EC7176',
                'data'                  => $string_reads,
            ],
        ];

        $graph_area = html_print_div(
            [
                'content' => line_graph($data, $options),
                'class'   => 'w100p h100p centered',
                'style'   => 'max-height: 83px; max-width: 93%; margin-bottom: 10px;',
            ],
            true
        );

        $total = html_print_div(
            [
                'content' => format_numeric($total),
                'class'   => 'text-xl',
            ],
            true
        );

        $output = html_print_div(
            [
                'content' => $total.$graph_area,
                'id'      => 'database-reads',
            ],
            true
        );

        return $output;
    }


    /**
     * Returns the html of total writes database in a graph.
     *
     * @return string
     */
    public function getWritesGraph():string
    {
        $dateInit = (time() - 86400);
        $writes = $this->valueMonitoring('mysql_questions_writes', $dateInit, time());
        $dates = [];
        $string_writes = [];
        $total = 0;
        foreach ($writes as $key => $write) {
            if (isset($write['utimestamp']) === false) {
                $write['utimestamp'] = 0;
            }

            $dates[] = date('d-m-Y H:i:s', $write['utimestamp']);
            $string_writes[] = $write['datos'];
            $total += $write['datos'];
        }

        $options = [
            'labels'   => $dates,
            'legend'   => [ 'display' => false ],
            'tooltips' => [ 'display' => false ],
            'scales'   => [
                'y' => [
                    'grid'    => ['display' => false],
                    'ticks'   => ['display' => false],
                    'display' => false,
                ],
                'x' => [
                    'grid'    => ['display' => false],
                    'display' => false,
                ],
            ],
            'elements' => [ 'point' => [ 'radius' => 0 ] ],
        ];

        $data = [
            [
                'backgroundColor'       => '#009D9E',
                'borderColor'           => '#009D9E',
                'pointBackgroundColor'  => '#009D9E',
                'pointHoverBorderColor' => '#009D9E',
                'data'                  => $string_writes,
            ],
        ];

        $graph_area = html_print_div(
            [
                'content' => line_graph($data, $options),
                'class'   => 'w100p h100p centered',
                'style'   => 'max-height: 83px; max-width: 93%; margin-bottom: 10px;',
            ],
            true
        );

        $total = html_print_div(
            [
                'content' => format_numeric($total),
                'class'   => 'text-xl',
            ],
            true
        );

        $output = html_print_div(
            [
                'content' => $total.$graph_area,
                'id'      => 'database-writes',
            ],
            true
        );

        return $output;
    }


    /**
     * Check if user can manage database
     *
     * @return boolean
     */
    public function checkAcl():bool
    {
        global $config;
        $db_m = (bool) check_acl($config['id_user'], 0, 'DM');
        return $db_m;
    }


}
