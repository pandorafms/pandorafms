<?php
/**
 * Groups element for tactical view.
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
 * Groups, this class contain all logic for this section.
 */
class Groups extends Element
{

    /**
     * Total groups.
     *
     * @var integer
     */
    public $total;


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = __('Groups');
        $this->total = $this->calculateTotalGroups();
    }


    /**
     * Return the total groups.
     *
     * @return integer
     */
    public function calculateTotalGroups():int
    {
        $total = db_get_num_rows('SELECT * FROM tgrupo;');
        return $total;
    }


    /**
     * Return the status groups in heat map.
     *
     * @return string
     */
    public function getStatusHeatMap():string
    {
        ui_require_css_file('heatmap');
        $width = 350;
        $height = 275;
        $sql = 'SELECT * FROM tagente a
                LEFT JOIN tagent_secondary_group g ON g.id_agent = a.id_agente';

        $all_agents = db_get_all_rows_sql($sql);
        if (empty($all_agents)) {
            return null;
        }

        $total_agents = count($all_agents);

        // Best square.
        $high = (float) max($width, $height);
        $low = 0.0;

        while (abs($high - $low) > 0.000001) {
            $mid = (($high + $low) / 2.0);
            $midval = (floor($width / $mid) * floor($height / $mid));
            if ($midval >= $total_agents) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        $square_length = min(($width / floor($width / $low)), ($height / floor($height / $low)));
        // Print starmap.
        $heatmap = sprintf(
            '<svg id="svg" style="width: %spx; height: %spx;">',
            $width,
            $height
        );

        $heatmap .= '<g>';
        $row = 0;
        $column = 0;
        $x = 0;
        $y = 0;
        $cont = 1;

        foreach ($all_agents as $key => $value) {
            // Colour by status.
            $status = agents_get_status_from_counts($value);

            switch ($status) {
                case 5:
                    // Not init status.
                    $status = 'notinit';
                break;

                case 1:
                    // Critical status.
                    $status = 'critical';
                break;

                case 2:
                    // Warning status.
                    $status = 'warning';
                break;

                case 0:
                    // Normal status.
                    $status = 'normal';
                break;

                case 3:
                case -1:
                default:
                    // Unknown status.
                    $status = 'unknown';
                break;
            }

            $heatmap .= sprintf(
                '<rect id="%s" x="%s" style="stroke-width:1;stroke:#ffffff" y="%s" row="%s" rx="3" ry="3" col="%s" width="%s" height="%s" class="scuare-status %s_%s"></rect>',
                'rect_'.$cont,
                $x,
                $y,
                $row,
                $column,
                $square_length,
                $square_length,
                $status,
                random_int(1, 10)
            );

            $y += $square_length;
            $row++;
            if ((int) ($y + $square_length) > (int) $height) {
                $y = 0;
                $x += $square_length;
                $row = 0;
                $column++;
            }

            if ((int) ($x + $square_length) > (int) $width) {
                $x = 0;
                $y += $square_length;
                $column = 0;
                $row++;
            }

            $cont++;
        }

        $heatmap .= '<script type="text/javascript">
                    $(document).ready(function() {
                        const total_agents = "'.$total_agents.'";

                        function getRandomInteger(min, max) {
                            return Math.floor(Math.random() * max) + min;
                        }

                        function oneSquare(solid, time) {
                            var randomPoint = getRandomInteger(1, total_agents);
                            let target = $(`#rect_${randomPoint}`);
                            let class_name = target.attr("class");
                            class_name = class_name.split("_")[0];
                            setTimeout(function() {
                                target.removeClass();
                                target.addClass(`${class_name}_${solid}`);
                                oneSquare(getRandomInteger(1, 10), getRandomInteger(100, 900));
                            }, time);
                        }

                        let cont = 0;
                        while (cont < Math.ceil(total_agents / 3)) {
                            oneSquare(getRandomInteger(1, 10), getRandomInteger(100, 900));
                            cont ++;
                        }
                    });
                </script>';
        $heatmap .= '</g>';
        $heatmap .= '</svg>';

        return html_print_div(
            [
                'content' => $heatmap,
                'style'   => 'margin: 0 auto; width: fit-content; min-height: 285px;',
            ],
            true
        );
    }


}
