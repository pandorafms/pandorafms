<?php
/**
 * Overview element for tactical view.
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
 * Overview, this class contain all logic for this section.
 */
class Overview extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = __('General overview');
        $this->ajaxMethods = [
            'getLogSizeStatus',
            'getWuxServerStatus',
        ];
        $this->interval = 300000;
        $this->refreshConfig = [
            'logSizeStatus'   => [
                'id'     => 'status-log-size',
                'method' => 'getLogSizeStatus',
            ],
            'wuxServerStatus' => [
                'id'     => 'status-wux',
                'method' => 'getWuxServerStatus',
            ],
        ];
    }


    /**
     * Check if module WUX connection exist.
     *
     * @return boolean
     */
    public function wuxIsEnabled():bool
    {
        if (empty($this->monitoringAgent) === true) {
            return false;
        }

        $existModule = modules_get_agentmodule_id(io_safe_input('WUX connection'), $this->monitoringAgent['id_agente']);
        if ($existModule === false) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Return the html log size status.
     *
     * @return string
     */
    public function getLogSizeStatus():string
    {
        $size = $this->valueMonitoring('console_log_size');
        $status = ($size[0]['datos'] < 1000) ? true : false;

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
                    'content' => __('Too size log size'),
                    'class'   => 'status-text',
                ],
                true
            );
        }

        $output = $image_status.$text;

        $align = ($this->wuxIsEnabled() === true) ? 'flex_center' : 'flex_justify';
        return html_print_div(
            [
                'content' => $output,
                'class'   => 'margin-top-5 '.$align,
                'id'      => 'status-log-size',
            ],
            true
        );

    }


    /**
     * Return the html Wix server status.
     *
     * @return string
     */
    public function getWuxServerStatus():string
    {
        $wux = $this->valueMonitoring('WUX connection');
        $status = ($wux[0]['datos'] > 0) ? true : false;

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
                'id'      => 'status-wux',
            ],
            true
        );

    }


    /**
     * Returns the html of the used licenses.
     *
     * @return string
     */
    public function getLicenseUsageGraph():string
    {
        // TODO connect to automonitorization.
        $options = [
            'labels' => [
                'Agents used',
                'Free agents',
            ],
            'colors' => [
                '#1C4E6B',
                '#5C63A2',
            ],
            'legend' => [
                'position' => 'bottom',
                'align'    => 'right',
            ],
            'cutout' => 80,
        ];
        $pie = ring_graph([60, 40], $options);
        $output = html_print_div(
            [
                'content' => $pie,
                'style'   => 'margin: 0 auto; max-width: 320px',
            ],
            true
        );

        return $output;
    }


    /**
     * Returns the html of a graph with the processed xmls
     *
     * @return string
     */
    public function getXmlProcessedGraph():string
    {
        $sql = 'SELECT
                utimestamp,
                DATE_FORMAT(FROM_UNIXTIME(utimestamp), "%Y-%m-%d %H:00:00") AS hour,
                COUNT(*) AS xml_proccessed
                FROM tagent_access
                WHERE FROM_UNIXTIME(utimestamp) >= NOW() - INTERVAL 24 HOUR
                GROUP BY hour
                ORDER BY hour;';

        $rows = db_process_sql($sql);

        $dates = [];
        $xml_proccessed = [];
        $total = 0;
        foreach ($rows as $key => $raw_data) {
            $dates[] = date('H:00:00', $raw_data['utimestamp']);
            $total += $raw_data['xml_proccessed'];
            $xml_proccessed[] = $raw_data['xml_proccessed'];
        }

        $options = [
            'labels'   => $dates,
            'legend'   => [ 'display' => false ],
            'tooltips' => [ 'display' => false ],
            'scales'   => [
                'y' => [
                    'grid'  => ['display' => false],
                    'ticks' => ['display' => false],
                ],
                'x' => [
                    'grid'    => ['display' => false],
                    'display' => false,
                ],
            ],
        ];

        $data = [
            [
                'backgroundColor'       => '#009D9E',
                'borderColor'           => '#009D9E',
                'pointBackgroundColor'  => '#009D9E',
                'pointHoverBorderColor' => '#009D9E',
                'data'                  => $xml_proccessed,
            ],
        ];

        $graph_area = html_print_div(
            [
                'content' => line_graph($data, $options),
                'class'   => 'margin-top-5 w100p h100p',
                'style'   => 'max-height: 330px;',
            ],
            true
        );

        $total = html_print_div(
            [
                'content' => $total,
                'class'   => 'text-xl',
            ],
            true
        );

        $output = $total.$graph_area;

        return $output;
    }


}
