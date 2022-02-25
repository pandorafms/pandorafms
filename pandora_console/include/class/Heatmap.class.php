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
     * Heatmap width.
     *
     * @var integer
     */
    protected $width = null;

    /**
     * Heatmap height.
     *
     * @var integer
     */
    protected $height = null;

    /**
     * Heatmap search.
     *
     * @var string
     */
    protected $search = null;


    /**
     * Constructor function
     *
     * @param integer $type     Heatmap type.
     * @param array   $filter   Heatmap filter.
     * @param string  $randomId Heatmap random id.
     * @param integer $refresh  Heatmap refresh.
     * @param integer $width    Width.
     * @param integer $height   Height.
     * @param string  $search   Heatmap search.
     */
    public function __construct(
        int $type=0,
        array $filter=[],
        string $randomId=null,
        int $refresh=300,
        int $width=0,
        int $height=0,
        string $search=null
    ) {
        $this->type = $type;
        $this->filter = $filter;
        (empty($randomId) === true) ? $this->randomId = uniqid() : $this->randomId = $randomId;
        $this->refresh = $refresh;
        $this->width = $width;
        $this->height = $height;
        $this->search = $search;
    }


    /**
     * Run.
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
                'search'   => $this->search,
            ],
        ];

        echo '<div id="div_'.$this->randomId.'" class="mainDiv">';
        ?>
            <script type="text/javascript">
                $(document).ready(function() {
                    const randomId = '<?php echo $this->randomId; ?>';
                    const refresh = '<?php echo $this->refresh; ?>';
                    let setting = <?php echo json_encode($settings); ?>;
                    setting['data']['height'] = $(`#div_${randomId}`).height();
                    setting['data']['width'] = $(`#div_${randomId}`).width();

                    // Initial charge.
                    ajaxRequest(
                        `div_${randomId}`,
                        setting
                    );

                    // Refresh.
                    setInterval(
                        function() {
                            refreshMap();
                        },
                        (refresh * 1000)
                    );

                    function refreshMap() {
                        $.ajax({
                            type: 'GET',
                            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                            data: {
                                page: "operation/heatmap",
                                method: 'getDataJson',
                                randomId: randomId,
                                type: '<?php echo $this->type; ?>',
                                refresh: '<?php echo $this->refresh; ?>'
                            },
                            dataType: 'json',
                            success: function(data) {
                                const total = Object.keys(data).length;
                                if (total === $(`#svg_${randomId} rect`).length) {
                                    // Object to array.
                                    let lista = Object.values(data);
                                    // randomly sort.
                                    lista = lista.sort(function() {return Math.random() - 0.5});

                                    const countPerSecond = total / refresh;

                                    let cont = 0;
                                    let limit = countPerSecond - 1;

                                    const timer = setInterval(
                                        function() {
                                            while (cont <= limit) {
                                                if ($(`#${randomId}_${lista[cont]['id']}`).hasClass(`${lista[cont]['status']}`)) {
                                                    let test = $(`#${randomId}_${lista[cont]['id']}`).css("filter");
                                                    if (test !== 'none') {
                                                        // console.log(test)
                                                        // console.log(test.match('/(\d+\.\d|\d)/'));
                                                    } else {
                                                        $(`#${randomId}_${lista[cont]['id']}`).css("filter", "brightness(1.1)");
                                                    }
                                                } else {
                                                    $(`#${randomId}_${lista[cont]['id']}`).removeClass("normal critical warning unknown");
                                                    $(`#${randomId}_${lista[cont]['id']}`).addClass(`${lista[cont]['status']}`);
                                                    $(`#${randomId}_${lista[cont]['id']}`).css("filter", "brightness(1)");
                                                }

                                                cont++;
                                            }
                                            limit = limit + countPerSecond;
                                        },
                                        1000
                                    );

                                    setTimeout(
                                        function(){
                                            clearInterval(timer);
                                        },
                                        (refresh * 1000)
                                    );
                                }
                            }
                        });
                    }
                });
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
     * Getter for randomId
     *
     * @return string
     */
    public function getRandomId()
    {
        return $this->randomId;
    }


    /**
     * Get all agents
     *
     * @return array
     */
    protected function getAllAgents()
    {
        $filter['disabled'] = 0;

        if (empty($this->search) === false) {
            $filter['search'] = ' AND alias LIKE "%'.$this->search.'%"';
        }

        if (empty($this->filter) === false) {
            $filter['id_grupo'] = current($this->filter);
        }

        // All agents.
        $result = agents_get_agents(
            $filter,
            [
                'id_agente as id',
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
        foreach ($result as $key => $agent) {
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

            $agents[$key] = $agent;
            $agents[$key]['status'] = $status;
        }

        // -------------------Agent generator--------------------
        $a = 1;
        $agents = [];
        $total = 1010;
        while ($a <= $total) {
            $agents[$a]['id'] = $a;
            $agents[$a]['status'] = $this->statusColour(rand(4, 0));
            $agents[$a]['id_grupo'] = ceil($a / 20);
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
        return '';
    }


    /**
     * Get class by status
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
     * @param integer $total    Total.
     * @param float   $relation Aspect relation.
     *
     * @return integer
     */
    protected function getYAxis(int $total, float $relation)
    {
        $yAxis = sqrt(($total / $relation));
        return $yAxis;

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

        $scale = ($this->width / $this->height);

        $Yaxis = $this->getYAxis(count($result), $scale);
        $Xaxis = (int) ceil($Yaxis * $scale);
        $Yaxis = ceil($Yaxis);

        $viewBox = sprintf(
            '0 0 %d %d',
            $Xaxis,
            $Yaxis
        );

        echo '<svg id="svg_'.$this->randomId.'" width="'.$this->width.'" 
            height="'.$this->height.'" viewBox="'.$viewBox.'">';

        $groups = [];
        $contX = 0;
        $contY = 0;
        foreach ($result as $value) {
            echo '<rect id="'.$this->randomId.'_'.$value['id'].'" class="'.$value['status'].' hover"
                width="1" height="1" x ="'.$contX.' "y="'.$contY.'" />';

            $contX++;
            if ($contX >= $Xaxis) {
                $contY++;
                $contX = 0;
            }

            if (empty($groups[$value['id_grupo']]) === true) {
                $groups[$value['id_grupo']] = 1;
            } else {
                $groups[$value['id_grupo']] += 1;
            }
        }

        ?>
            <script type="text/javascript">
                $('rect').click(function() {
                    const type = <?php echo $this->type; ?>;
                    const hash = '<?php echo $this->randomId; ?>';
                    const id = this.id.replace(`${hash}_`, '');

                    $("#info_dialog").dialog({
                        resizable: true,
                        draggable: true,
                        modal: true,
                        closeOnEscape: true,
                        height: 400,
                        width: 430,
                        title: '<?php echo __('Info'); ?>',
                        open: function() {
                            $.ajax({
                                type: 'GET',
                                url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                                data: {
                                    page: "include/ajax/heatmap.ajax",
                                    getInfo: 1,
                                    type: type,
                                    id: id,
                                },
                                dataType: 'html',
                                success: function(data) {
                                    $('#info_dialog').empty();
                                    $('#info_dialog').append(data);
                                }
                            });
                        },
                    });
                });
            </script>
        <?php
        $x_back = 0;
        $y_back = 0;
        echo '<polyline points="0,0 0,1" class="polyline" />';
        echo '<polyline points="'.$contX.','.$contY.' '.$contX.','.($contY + 1).'" class="polyline" />';
        echo '<polyline points="'.$contX.','.$contY.' '.$Xaxis.','.$contY.'" class="polyline" />';
        foreach ($groups as $group) {
            if (($x_back + $group) <= $Xaxis) {
                $x_position = ($x_back + $group);
                $y_position = $y_back;

                $points = sprintf(
                    '%d,%d %d,%d %d,%d %d,%d',
                    $x_position,
                    $y_back,
                    $x_back,
                    $y_back,
                    $x_back,
                    ($y_position + 1),
                    $x_position,
                    ($y_position + 1)
                );

                echo '<polyline points="'.$points.'" class="polyline" />';

                $x_back = $x_position;
                if ($x_position === $Xaxis) {
                    $points = sprintf(
                        '%d,%d %d,%d',
                        $x_position,
                        $y_back,
                        $x_position,
                        ($y_back + 1)
                    );

                    echo '<polyline points="'.$points.'" class="polyline" />';

                    $y_back ++;
                    $x_back = 0;
                }
            } else {
                $round = (int) floor(($x_back + $group) / $Xaxis);
                $y_position = ($round + $y_back);

                // Top of the first line.
                $points = sprintf(
                    '%d,%d %d,%d %d,%d',
                    $x_back,
                    ($y_back + 1),
                    $x_back,
                    $y_back,
                    $Xaxis,
                    $y_back
                );

                echo '<polyline points="'.$points.'" class="polyline" />';

                if ($round === 1) {
                    // One line.
                    $x_position = (($x_back + $group) - $Xaxis);

                    // Bottom of last line.
                    $points = sprintf(
                        '%d,%d %d,%d',
                        0,
                        ($y_position + 1),
                        $x_position,
                        ($y_position + 1)
                    );

                    echo '<polyline points="'.$points.'" class="polyline" />';
                } else {
                    // Two or more lines.
                    $x_position = (($x_back + $group) - ($Xaxis * $round));
                    if ($x_position === 0) {
                        $x_position = $Xaxis;
                    }

                    // Bottom of last line.
                    $points = sprintf(
                        '%d,%d %d,%d',
                        0,
                        ($y_position + 1),
                        $x_position,
                        ($y_position + 1)
                    );

                    echo '<polyline points="'.$points.'" class="polyline" />';
                }

                if ($x_position === $Xaxis) {
                    $x_position = 0;
                }

                $x_back = $x_position;
                $y_back = $y_position;
            }
        }

        echo '</svg>';

        // Dialog.
        echo '<div id="info_dialog" style="padding:15px" class="invisible"></div>';
    }


}
