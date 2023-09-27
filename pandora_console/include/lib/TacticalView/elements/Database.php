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
        parent::__construct();
        $this->title = __('Database');
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
     * Returns the html of total events.
     *
     * @return string
     */
    public function getEvents():string
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
     * Returns the html of total records.
     *
     * @return string
     */
    public function getStringRecords():string
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
     * Returns the html of total reads database in a graph.
     *
     * @return string
     */
    public function getReadsGraph():string
    {
        // TODO connect to automonitorization.
        $dates = [
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
            9,
            10,
        ];
        $string_reads = [
            1,
            0.5,
            2,
            1.5,
            3,
            2.5,
            4,
            3.5,
            5,
            4.5,
            6,
        ];
        $total = '9.999.999';
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
                'class'   => 'w100p h100p',
                'style'   => 'max-height: 83px;',
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


    /**
     * Returns the html of total writes database in a graph.
     *
     * @return string
     */
    public function getWritesGraph():string
    {
        // TODO connect to automonitorization.
        $dates = [
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
            9,
            10,
        ];
        $string_writes = [
            1,
            0.5,
            2,
            1.5,
            3,
            2.5,
            4,
            3.5,
            5,
            4.5,
            6,
        ];
        $total = '9.999.999';
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
                'class'   => 'w100p h100p',
                'style'   => 'max-height: 83px;',
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
