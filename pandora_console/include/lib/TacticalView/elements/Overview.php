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
        global $config;
        parent::__construct();
        include_once $config['homedir'].'/include/graphs/fgraph.php';
        if (is_ajax() === true) {
            include_once $config['homedir'].'/include/functions_servers.php';
        }

        $this->title = __('General overview');
        $this->ajaxMethods = [
            'getLogSizeStatus',
            'getServerStatus',
            'getCPULoadGraph',
        ];
        $this->interval = 300000;
        $this->refreshConfig = [
            'logSizeStatus' => [
                'id'     => 'status-log-size',
                'method' => 'getLogSizeStatus',
            ],
            'ServerStatus'  => [
                'id'     => 'status-servers',
                'method' => 'getServerStatus',
            ],
            'cpuStatus'     => [
                'id'     => 'status-cpu',
                'method' => 'getCPULoadGraph',
            ],
        ];
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

        return html_print_div(
            [
                'content' => $output,
                'class'   => 'margin-top-5 flex_center',
                'id'      => 'status-log-size',
            ],
            true
        );

    }


    /**
     * Return the html Servers status.
     *
     * @return string
     */
    public function getServerStatus():string
    {
        $status = check_all_servers_up();

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

        $output = '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server" class="flex_center">'.$image_status.$text.'</a>';

        return html_print_div(
            [
                'content' => $output,
                'class'   => 'margin-top-5',
                'id'      => 'status-servers',
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
        if (enterprise_installed() === true) {
            $info = license_get_info();
            if ($info['limit'] > $info['count']) {
                $used = round(($info['count'] / $info['limit']) * 100);
                $free = (100 - $used);
            } else if ($info['limit'] <= $info['count']) {
                $free = 0;
                $used = 100;
            } else {
                $free = 100;
                $used = 0;
            }

            if ($used > 0) {
                $data['agents_used'] = [
                    'label' => __('% Agents used'),
                    'perc'  => $used,
                    'color' => '#1C4E6B',
                ];
            }

            if ($free > 0) {
                $data['free_agents'] = [
                    'label' => __('% Free agents'),
                    'perc'  => $free,
                    'color' => '#5C63A2',
                ];
            }
        } else {
            $agents = agents_get_agents();
            $enabled_agents = agents_get_agents(
                false,
                false,
                'AR',
                [
                    'field' => 'nombre',
                    'order' => 'ASC',
                ],
                false,
                1
            );
            if (is_array($agents) === true) {
                $total = count($agents);
            } else {
                $total = 0;
            }

            if ($total > 0 && is_array($enabled_agents) === true) {
                $total_disabled_agents = round((($total - count($enabled_agents)) * 100) / $total);
                $total_enabled_agents = round((count($enabled_agents) * 100) / $total);
            } else {
                $total_disabled_agents = 100;
                $total_enabled_agents = 0;
            }

            if ($total_enabled_agents > 0) {
                $data['agents_enabled'] = [
                    'label' => __('% Agents enabled'),
                    'perc'  => $total_enabled_agents,
                    'color' => '#1C4E6B',
                ];
            }

            if ($total_disabled_agents > 0) {
                $data['agents_disabled'] = [
                    'label' => __('% Agents disabled'),
                    'perc'  => $total_disabled_agents,
                    'color' => '#5C63A2',
                ];
            }
        }

        $bar = $this->printHorizontalBar($data);
        $output = html_print_div(
            [
                'content' => $bar,
                'style'   => 'margin: 0 auto;',
            ],
            true
        );

        return $output;
    }


    /**
     * Print horizontal bar divided by percentage.
     *
     * @param array $data Required [perc, color, label].
     *
     * @return string
     */
    private function printHorizontalBar(array $data):string
    {
        $output = '<div id="horizontalBar">';
        $output .= '<div class="labels">';
        foreach ($data as $key => $value) {
            $output .= html_print_div(
                [
                    'content' => '<div style="background: '.$value['color'].'"></div><span>'.$value['label'].'</span>',
                    'class'   => 'label',
                ],
                true
            );
        }

        $output .= '</div>';
        $output .= '<div class="bar">';
        foreach ($data as $key => $value) {
            $output .= html_print_div(
                [
                    'content' => $value['perc'].' %',
                    'style'   => 'width: '.$value['perc'].'%; background-color: '.$value['color'].';',
                ],
                true
            );
        }

        $output .= '</div>';
        $output .= '
            <div class="marks">
            <div class="mark"><div class="line mark0"></div><span class="number">0 %</span></div>
            <div class="mark"><div class="line mark20"></div><span class="number number20">20 %</span></div>
            <div class="mark"><div class="line mark40"></div><span class="number number40">40 %</span></div>
            <div class="mark"><div class="line mark60"></div><span class="number number60">60 %</span></div>
            <div class="mark"><div class="line mark80"></div><span class="number number80">80 %</span></div>
            <div class="mark"><div class="line mark100"></div><span class="number number100">100 %</span></div>
            </div>';
        $output .= '</div>';

        return $output;
    }


    /**
     * Returns the html of a graph with the cpu load.
     *
     * @return string
     */
    public function getCPULoadGraph():string
    {
        $data_last24h = $this->valueMonitoring('CPU Load', (time() - 86400), time());
        $dates = [];
        $cpu_load = [];
        foreach ($data_last24h as $key => $raw_data) {
            $dates[] = date('H:m:s', $raw_data['utimestamp']);
            $cpu_load[] = $raw_data['datos'];
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
                'data'                  => $cpu_load,
            ],
        ];

        $graph_area = html_print_div(
            [
                'content' => line_graph($data, $options),
                'class'   => 'margin-top-5 w100p h100p',
                'style'   => 'max-height: 50px;',
                'id'      => 'status-cpu',
            ],
            true
        );

        $output = $graph_area;

        return $output;
    }


}
