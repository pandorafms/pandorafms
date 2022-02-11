<?php
/**
 * Heatmap class.
 *
 * @category   Heatmap
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */
class Heatmap
{

    /**
     * Heatmap type.
     *
     * @var integer
     */
    protected $type = null;

    /**
     * Heatmap filter.
     *
     * @var array
     */
    protected $filter = null;

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    protected $AJAXMethods = [
        'showHeatmap',
        'updateHeatmap',
        'getDataJson',
    ];

    /**
     * Heatmap random id.
     *
     * @var string
     */
    protected $randomId = null;

    /**
     * Heatmap refresh.
     *
     * @var integer
     */
    protected $refresh = null;


    /**
     * Constructor function
     *
     * @param integer $type     Heatmap type.
     * @param array   $filter   Heatmap filter.
     * @param string  $randomId Heatmap random id.
     * @param integer $refresh  Heatmap refresh.
     */
    public function __construct(
        int $type=0,
        array $filter=[],
        string $randomId=null,
        int $refresh=300
    ) {
        $this->type = $type;
        $this->filter = $filter;
        (empty($randomId) === true) ? $this->randomId = uniqid() : $this->randomId = $randomId;
        $this->refresh = $refresh;
    }


    /**
     * Show .
     *
     * @return void
     */
    public function run()
    {
        ui_require_css_file('heatmap');

        $settings = [
            'type'     => 'POST',
            'dataType' => 'html',
            'url'      => ui_get_full_url(
                'ajax.php',
                false,
                false,
                false
            ),
            'data'     => [
                'page'     => 'operation/heatmap',
                'method'   => 'showHeatmap',
                'randomId' => $this->randomId,
                'type'     => $this->type,
                'filter'   => $this->filter,
                'refresh'  => $this->refresh,
            ],
        ];

        echo '<div id="div_'.$this->randomId.'" class="mainDiv">';
        ?>
            <script type="text/javascript">
                $(document).ready(function() {
                    const randomId = '<?php echo 'div_'.$this->randomId; ?>';
                    const refresh = '<?php echo $this->refresh; ?>';

                    // Initial charge.
                    ajaxRequest(
                        randomId,
                        <?php echo json_encode($settings); ?>
                    );

                    // Refresh.
                    setInterval(
                        function() {
                            refreshMap();
                        },
                        (refresh * 10)
                    );
                });


                function refreshMap() {
                    $.ajax({
                        type: 'GET',
                        url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                        data: {
                            page: "operation/heatmap",
                            method: 'getDataJson',
                            randomId: '<?php echo $this->randomId; ?>',
                            type: '<?php echo $this->type; ?>',
                            refresh: '<?php echo $this->refresh; ?>'
                        },
                        dataType: 'json',
                        success: function(data) {
                            console.log(data);
                        }
                    });
                };
            </script>
        <?php
        echo '</div>';
    }


    /**
     * Setter for filter
     *
     * @param array $filter Filter.
     *
     * @return void
     */
    public function setFilter(array $filter)
    {
        $this->filter = $filter;
    }


    /**
     * Setter for type
     *
     * @param integer $type Type.
     *
     * @return void
     */
    public function setType(int $type)
    {
        $this->type = $type;
    }


    /**
     * Setter for refresh
     *
     * @param integer $refresh Refresh.
     *
     * @return void
     */
    public function setRefresh(int $refresh)
    {
        $this->refresh = $refresh;
    }


    /**
     * Get all agents
     *
     * @return array
     */
    protected function getAllAgents()
    {
        // All agents.
        $result = agents_get_agents(
            [
                'disabled' => 0,
                // 'search_custom' => $search_sql_custom,
                // 'search'        => $search_sql,
            ],
            [
                'id_agente',
                'alias',
                'id_grupo',
                'normal_count',
                'warning_count',
                'critical_count',
                'unknown_count',
                'notinit_count',
                'total_count',
                'fired_count',
            ],
            'AR',
            [
                'field' => 'id_grupo,id_agente',
                'order' => 'ASC',
            ]
        );

        $agents = [];
        // Agent status.
        foreach ($result as $agent) {
            if ($agent['total_count'] === 0 || $agent['total_count'] === $agent['notinit_count']) {
                $status = 'notinit';
            } else if ($agent['critical_count'] > 0) {
                $status = 'critical';
            } else if ($agent['warning_count'] > 0) {
                $status = 'warning';
            } else if ($agent['unknown_count'] > 0) {
                $status = 'unknown';
            } else {
                $status = 'normal';
            }

            $agents[$agent['id_agente']] = $agent;
            $agents[$agent['id_agente']]['status'] = $status;
        }

        $status = [
            'normal',
            'critical',
            'warning',
            'unknown',
            'normal',
        ];

        // -------------------Agent generator--------------------
        $a = 1;
        $agents = [];
        $total = 1000;
        while ($a <= $total) {
            $agents[$a]['id_agente'] = $a;
            $agents[$a]['status'] = $this->statusColour(rand(4, 0));
            $agents[$a]['id_grupo'] = ceil($a / 10);
            $a++;
        }

        // -------------------------------------------
        return $agents;
    }


    /**
     * GetDataJson
     *
     * @return json
     */
    public function getDataJson()
    {
        $return = $this->getAllAgents();
        echo json_encode($return);
        return;
    }


    /**
     * Get colour by status
     *
     * @param integer $status Status.
     *
     * @return string
     */
    protected function statusColour(int $status)
    {
        switch ($status) {
            case AGENT_STATUS_CRITICAL:
                $return = 'critical';
            break;

            case AGENT_STATUS_WARNING:
                $return = 'warning';
            break;

            case AGENT_STATUS_UNKNOWN:
                $return = 'unknown';
            break;

            case AGENT_STATUS_NOT_INIT:
                $return = 'notinit';
            break;

            case AGENT_STATUS_NORMAL:
            default:
                $return = 'normal';
            break;
        }

        return $return;
    }


    /**
     * Get max. number of y-axis
     *
     * @param integer $total Total.
     *
     * @return integer
     */
    protected function getYAxis(int $total)
    {
        $yAxis = ceil(sqrt(($total / 2)));
        return (integer) $yAxis;

    }


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public function ajaxMethod(string $method):bool
    {
        return in_array($method, $this->AJAXMethods);
    }


    /**
     * ShowHeatmap
     *
     * @return void
     */
    public function showHeatmap()
    {
        switch ($this->type) {
            case 0:
            default:
                $result = $this->getAllAgents();
            break;
        }

        $Yaxis = $this->getYAxis(count($result));
        $Xaxis = ($Yaxis * 2);
        $viewBox = sprintf(
            '0 0 %d %d',
            $Xaxis,
            $Yaxis
        );

        echo '<svg id="svg_'.$this->randomId.'" width=95% viewBox="'.$viewBox.'">';

        $contX = 0;
        $contY = 0;
        // $auxdata = 0;
        // $auxY = 0;
        foreach ($result as $key => $value) {
            echo '<rect id="'.$value['id_agente'].'" 
                class="'.$value['status'].' hover" 
                width="1" height="1" x ="'.$contX.' "y="'.$contY.'" />';

            // Top.
            // if ($auxdata !== $value['id_grupo'] || $contY === 0) {
            // if ($auxdata !== $value['id_grupo']) {
            // $auxdata = $value['id_grupo'];
            // $auxY = 1;
            // }
            // $point = sprintf(
            // '%d,%d %d,%d',
            // $contX,
            // $contY,
            // ($contX + 1),
            // $contY
            // );
            // echo '<polygon class="group" points="'.$point.'" />';
            // }
            // Left.
            // if ($contX === 0 || $auxY === 1) {
            // $point = sprintf(
            // '%d,%d %d,%d',
            // $contX,
            // $contY,
            // $contX,
            // ($contY + 1)
            // );
            // echo '<polygon class="group" points="'.$point.'" />';
            // }
            // Bottom.
            // if (($contY + 1) === $Yaxis) {
            // $point = sprintf(
            // '%d,%d %d,%d',
            // $contX,
            // ($contY + 1),
            // ($contX + 1),
            // ($contY + 1)
            // );
            // echo '<polygon class="group" points="'.$point.'" />';
            // }
            // Right.
            // if (($contX + 1) === $Xaxis) {
            // hd('entra');
            // $point = sprintf(
            // '%d,%d %d,%d',
            // ($contX + 1),
            // $contY,
            // ($contX + 1),
            // ($contY + 1)
            // );
            // echo '<polygon class="group" points="'.$point.'" />';
            // }
            $contY++;
            if ($contY === $Yaxis) {
                $contX++;
                $contY = 0;
                $auxY = 0;
            }
        }

        echo '</svg>';
    }


}
