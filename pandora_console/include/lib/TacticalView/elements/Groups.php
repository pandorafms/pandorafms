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
        global $config;
        parent::__construct();
        include_once $config['homedir'].'/include/functions_users.php';
        include_once 'include/functions_groupview.php';
        $this->ajaxMethods = ['getStatusHeatMap'];
        ui_require_css_file('heatmap');
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
        $total = db_get_value_sql('SELECT count(*) FROM tgrupo');
        return $total;
    }


    /**
     * Return the status groups in heat map.
     *
     * @return string
     */
    public function getStatusHeatMap():string
    {
        global $config;
        $width = get_parameter('width', 350);
        $height = get_parameter('height', 275);

        // ACL Check.
        $agent_a = check_acl($config['id_user'], 0, 'AR');
        $agent_w = check_acl($config['id_user'], 0, 'AW');

        $groups_list = groupview_get_groups_list(
            $config['id_user'],
            ($agent_a == true) ? 'AR' : (($agent_w == true) ? 'AW' : 'AR'),
            true
        );

        $total_groups = $groups_list['counter'];
        $groups = $groups_list['groups'];
        // Best square.
        $high = (float) max($width, $height);
        $low = 0.0;

        while (abs($high - $low) > 0.000001) {
            $mid = (($high + $low) / 2.0);
            $midval = (floor($width / $mid) * floor($height / $mid));
            if ($midval >= $total_groups) {
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
        foreach ($groups as $key => $value) {
            if ($value['_name_'] === 'All') {
                continue;
            }

            if ($value['_monitors_critical_'] > 0) {
                $status = 'critical';
            } else if ($value['_monitors_warning_'] > 0) {
                $status = 'warning';
            } else if (($value['_monitors_unknown_'] > 0) || ($value['_agents_unknown_'] > 0)) {
                $status = 'unknown';
            } else if ($value['_monitors_ok_'] > 0) {
                $status = 'normal';
            } else {
                $status = 'unknown';
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
                        const total_groups = "'.$total_groups.'";
                        function getRandomInteger(min, max) {
                            return Math.floor(Math.random() * max) + min;
                        }

                        function oneSquare(solid, time) {
                            var randomPoint = getRandomInteger(1, total_groups);
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
                        while (cont < Math.ceil(total_groups / 3)) {
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
