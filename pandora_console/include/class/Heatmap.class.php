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
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

namespace PandoraFMS;

use PandoraFMS\Enterprise\Metaconsole\Node;

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
     * Heatmap group.
     *
     * @var integer
     */
    protected $group = null;

    /**
     * Heatmap dashboard.
     *
     * @var boolean
     */
    protected $dashboard = null;

    /**
     * Public hash.
     *
     * @var boolean
     */
    protected $hash = null;

    /**
     * Public user.
     *
     * @var boolean
     */
    protected $publicUser = null;


    /**
     * Constructor function
     *
     * @param integer $type      Heatmap type.
     * @param array   $filter    Heatmap filter.
     * @param string  $randomId  Heatmap random id.
     * @param integer $refresh   Heatmap refresh.
     * @param integer $width     Width.
     * @param integer $height    Height.
     * @param string  $search    Heatmap search.
     * @param integer $group     Heatmap group.
     * @param boolean $dashboard Dashboard widget.
     */
    public function __construct(
        int $type=0,
        array $filter=[],
        string $randomId=null,
        int $refresh=300,
        int $width=0,
        int $height=0,
        string $search=null,
        int $group=1,
        bool $dashboard=false,
        string $hash='',
        string $publicUser=''
    ) {
        $this->type = $type;
        $this->filter = $filter;
        (empty($randomId) === true) ? $this->randomId = uniqid() : $this->randomId = $randomId;
        $this->refresh = $refresh;
        $this->width = $width;
        $this->height = $height;
        $this->search = $search;
        $this->group = $group;
        $this->dashboard = $dashboard;
        $this->hash = $hash;
        $this->publicUser = $publicUser;
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
            'type'     => 'GET',
            'dataType' => 'html',
            'url'      => ui_get_full_url(
                'ajax.php',
                false,
                false,
                false
            ),
            'data'     => [
                'page'       => 'operation/heatmap',
                'method'     => 'showHeatmap',
                'randomId'   => $this->randomId,
                'type'       => $this->type,
                'filter'     => $this->filter,
                'refresh'    => $this->refresh,
                'search'     => $this->search,
                'group'      => $this->group,
                'dashboard'  => (int) $this->dashboard,
                'auth_hash'  => $this->hash,
                'auth_class' => 'PandoraFMS\Dashboard\Manager',
                'id_user'    => $this->publicUser,
            ],
        ];

        $style_dashboard = '';
        if ($this->dashboard === true) {
            $style_dashboard = 'min-height: 0px';
        }

        echo '<div id="div_'.$this->randomId.'" class="mainDiv" style="width: 100%;height: 100%;'.$style_dashboard.'">';
        ?>
        <script type="text/javascript">
            $(document).ready(function() {
                const randomId = '<?php echo $this->randomId; ?>';
                const refresh = '<?php echo $this->refresh; ?>';
                const dashboard = '<?php echo $this->dashboard; ?>';

                let setting = <?php echo json_encode($settings); ?>;
                setting['data']['height'] = $(`#div_${randomId}`).height() + 10;
                setting['data']['width'] = $(`#div_${randomId}`).width();

                if (dashboard === '1') {
                    setting['data']['width'] -= 10;
                    setting['data']['height'] -= 10;
                }

                var totalModules = 0;

                // Initial charge.
                $.ajax({
                    type: setting.type,
                    dataType: setting.dataType,
                    url: setting.url,
                    data: setting.data,
                    success: function(data) {
                        $(`#div_${randomId}`).append(data);
                        totalModules = $('rect').length;
                        let cont = 0;
                        while (cont < Math.ceil(totalModules / 10)) {
                            oneSquare(getRandomInteger(1, 10), getRandomInteger(100, 900));
                            cont++;
                        }
                    }
                });

                function getRandomInteger(min, max) {
                    return Math.floor(Math.random() * max) + min;
                }

                function oneSquare(solid, time) {
                    var randomPoint = getRandomInteger(1, totalModules);
                    let target = $(`#${randomId}_${randomPoint}`);
                    setTimeout(function() {
                        let class_name = target.attr('class');
                        if (typeof class_name !== 'undefined') {
                            class_name = class_name.split(' ')[0];
                            const newClassName = class_name.split('_')[0];
                            target.removeClass(`${class_name} hover`);
                            target.addClass(`${newClassName}_${solid} hover`);
                            oneSquare(getRandomInteger(1, 10), getRandomInteger(100, 900));
                        }
                    }, time);
                }

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
                            type: setting['data']['type'],
                            refresh: setting['data']['refresh'],
                            filter: setting['data']['filter'],
                            search: setting['data']['search'],
                            group: setting['data']['group']
                        },
                        dataType: 'json',
                        success: function(data) {
                            const total = Object.keys(data).length;
                            if (total === $(`#svg_${randomId} rect`).length) {
                                // Object to array.
                                let lista = Object.values(data);
                                // randomly sort.
                                lista = lista.sort(function() {
                                    return Math.random() - 0.5
                                });

                                let countPerSecond = total / refresh;
                                if (countPerSecond < 1) {
                                    countPerSecond = 1;
                                }

                                let cont = 0;
                                let limit = countPerSecond - 1;

                                const timer = setInterval(
                                    function() {
                                        while (cont <= limit) {
                                            if (typeof lista[cont] !== 'undefined') {
                                                const rect = document.getElementsByName(`${lista[cont]['id']}`);
                                                $(`#${rect[0].id}`).removeClass();
                                                $(`#${rect[0].id}`).addClass(`${lista[cont]['status']} hover`);
                                            }

                                            cont++;
                                        }
                                        limit = limit + countPerSecond;
                                    },
                                    1000
                                );

                                setTimeout(
                                    function() {
                                        clearInterval(timer);
                                    },
                                    (refresh * 1000)
                                );
                            } else {
                                location.reload();
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
        global $config;

        $filter['disabled'] = 0;

        $alias = '';
        if (empty($this->search) === false) {
            $alias = ' AND alias LIKE "%'.$this->search.'%"';
        }

        $id_user_groups = '';
        if (users_is_admin() === false) {
            $user_groups = array_keys(users_get_groups($config['user'], 'AR', false));
            if (empty($user_groups) === false) {
                $id_user_groups = ' AND id_grupo IN ('.implode(',', $user_groups).')';
            }
        }

        $id_grupo = '';
        if (empty($this->filter) === false && empty(current($this->filter)) === false) {
            $id_grupo = ' AND id_grupo IN ('.implode(',', $this->filter).')';
        }

        // All agents.
        $sql = sprintf(
            'SELECT DISTINCT id_agente as id,alias,id_grupo,normal_count,warning_count,critical_count,
            unknown_count,notinit_count,total_count,fired_count,
            (SELECT last_status_change FROM tagente_estado WHERE id_agente = tagente.id_agente
            ORDER BY last_status_change DESC LIMIT 1) AS last_status_change
            FROM tagente WHERE `disabled` = 0 %s %s %s ORDER BY id_grupo,id_agente ASC',
            $alias,
            $id_user_groups,
            $id_grupo
        );

        $agents = [];
        if (is_metaconsole() === true) {
            $nodes = metaconsole_get_connections();
            $cont = 0;
            foreach ($nodes as $node) {
                try {
                    $nd = new Node($node['id']);
                    $nd->connect();

                    $result = db_get_all_rows_sql($sql);
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

                        if ($agent['last_status_change'] != 0) {
                            $seconds = (time() - $agent['last_status_change']);

                            if ($seconds >= SECONDS_1DAY) {
                                $status .= '_10';
                            } else if ($seconds >= 77760) {
                                $status .= '_9';
                            } else if ($seconds >= 69120) {
                                $status .= '_8';
                            } else if ($seconds >= 60480) {
                                $status .= '_7';
                            } else if ($seconds >= 51840) {
                                $status .= '_6';
                            } else if ($seconds >= 43200) {
                                $status .= '_5';
                            } else if ($seconds >= 34560) {
                                $status .= '_4';
                            } else if ($seconds >= 25920) {
                                $status .= '_3';
                            } else if ($seconds >= 17280) {
                                $status .= '_2';
                            } else if ($seconds >= 8640) {
                                $status .= '_1';
                            }
                        }

                        $agents[$cont] = $agent;
                        $agents[$cont]['status'] = $status;
                        $agents[$cont]['server'] = $node['id'];

                        ++$cont;
                    }
                } catch (\Exception $e) {
                    $nd->disconnect();
                    $agents = [];
                } finally {
                    $nd->disconnect();
                }
            }
        } else {
            $result = db_get_all_rows_sql($sql);

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

                if ($agent['last_status_change'] != 0) {
                    $seconds = (time() - $agent['last_status_change']);

                    if ($seconds >= SECONDS_1DAY) {
                        $status .= '_10';
                    } else if ($seconds >= 77760) {
                        $status .= '_9';
                    } else if ($seconds >= 69120) {
                        $status .= '_8';
                    } else if ($seconds >= 60480) {
                        $status .= '_7';
                    } else if ($seconds >= 51840) {
                        $status .= '_6';
                    } else if ($seconds >= 43200) {
                        $status .= '_5';
                    } else if ($seconds >= 34560) {
                        $status .= '_4';
                    } else if ($seconds >= 25920) {
                        $status .= '_3';
                    } else if ($seconds >= 17280) {
                        $status .= '_2';
                    } else if ($seconds >= 8640) {
                        $status .= '_1';
                    }
                }

                $agents[$key] = $agent;
                $agents[$key]['status'] = $status;
            }
        }

        return $agents;
    }


    /**
     * Get all modules
     *
     * @return array
     */
    protected function getAllModulesByGroup()
    {
        global $config;

        $filter_group = '';
        if (empty($this->filter) === false && current($this->filter) != -1) {
            $filter_group = 'AND am.id_module_group IN ('.implode(',', $this->filter).')';
        }

        $filter_name = '';
        if (empty($this->search) === false) {
            $filter_name = 'AND nombre LIKE "%'.$this->search.'%"';
        }

        $id_user_groups = '';
        if (users_is_admin() === false) {
            $user_groups = array_keys(users_get_groups($config['user'], 'AR', false));
            if (empty($user_groups) === false) {
                $id_user_groups = sprintf(
                    'INNER JOIN tagente a ON a.id_agente = ae.id_agente
                    AND a.id_grupo IN (%s)',
                    implode(',', $user_groups)
                );
            }
        }

        // All modules.
        $sql = sprintf(
            'SELECT am.id_agente_modulo AS id, ae.estado AS `status`, am.id_module_group AS id_grupo,
            ae.last_status_change FROM tagente_modulo am
            INNER JOIN tagente_estado ae ON am.id_agente_modulo = ae.id_agente_modulo
            %s
            WHERE am.disabled = 0 %s %s GROUP BY am.id_module_group, am.id_agente_modulo',
            $id_user_groups,
            $filter_group,
            $filter_name
        );

        if (is_metaconsole() === true) {
            $nodes = metaconsole_get_connections();
            $cont = 0;
            $result = [];
            foreach ($nodes as $node) {
                try {
                    $nd = new Node($node['id']);
                    $nd->connect();

                    $modules = db_get_all_rows_sql($sql);

                    // Module status.
                    foreach ($modules as $key => $module) {
                        $status = '';
                        switch ($module['status']) {
                            case AGENT_MODULE_STATUS_CRITICAL_BAD:
                            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                            case 1:
                            case 100:
                                $status = 'critical';
                            break;

                            case AGENT_MODULE_STATUS_NORMAL:
                            case AGENT_MODULE_STATUS_NORMAL_ALERT:
                            case 0:
                            case 300:
                                $status = 'normal';
                            break;

                            case AGENT_MODULE_STATUS_WARNING:
                            case AGENT_MODULE_STATUS_WARNING_ALERT:
                            case 2:
                            case 200:
                                $status = 'warning';
                            break;

                            default:
                            case AGENT_MODULE_STATUS_UNKNOWN:
                            case 3:
                                $status = 'unknown';
                            break;
                            case AGENT_MODULE_STATUS_NOT_INIT:
                            case 5:
                                $status = 'notinit';
                            break;
                        }

                        if ($module['last_status_change'] != 0) {
                            $seconds = (time() - $module['last_status_change']);

                            if ($seconds >= SECONDS_1DAY) {
                                $status .= '_10';
                            } else if ($seconds >= 77760) {
                                $status .= '_9';
                            } else if ($seconds >= 69120) {
                                $status .= '_8';
                            } else if ($seconds >= 60480) {
                                $status .= '_7';
                            } else if ($seconds >= 51840) {
                                $status .= '_6';
                            } else if ($seconds >= 43200) {
                                $status .= '_5';
                            } else if ($seconds >= 34560) {
                                $status .= '_4';
                            } else if ($seconds >= 25920) {
                                $status .= '_3';
                            } else if ($seconds >= 17280) {
                                $status .= '_2';
                            } else if ($seconds >= 8640) {
                                $status .= '_1';
                            }
                        }

                        $result[$cont] = $module;
                        $result[$cont]['status'] = $status;
                        $result[$cont]['server'] = $node['id'];
                        ++$cont;
                    }
                } catch (\Exception $e) {
                    $nd->disconnect();
                } finally {
                    $nd->disconnect();
                }
            }
        } else {
            $result = db_get_all_rows_sql($sql);

            // Module status.
            foreach ($result as $key => $module) {
                $status = '';
                switch ($module['status']) {
                    case AGENT_MODULE_STATUS_CRITICAL_BAD:
                    case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                    case 1:
                    case 100:
                        $status = 'critical';
                    break;

                    case AGENT_MODULE_STATUS_NORMAL:
                    case AGENT_MODULE_STATUS_NORMAL_ALERT:
                    case 0:
                    case 300:
                        $status = 'normal';
                    break;

                    case AGENT_MODULE_STATUS_WARNING:
                    case AGENT_MODULE_STATUS_WARNING_ALERT:
                    case 2:
                    case 200:
                        $status = 'warning';
                    break;

                    default:
                    case AGENT_MODULE_STATUS_UNKNOWN:
                    case 3:
                        $status = 'unknown';
                    break;
                    case AGENT_MODULE_STATUS_NOT_INIT:
                    case 5:
                        $status = 'notinit';
                    break;
                }

                if ($module['last_status_change'] != 0) {
                    $seconds = (time() - $module['last_status_change']);

                    if ($seconds >= SECONDS_1DAY) {
                        $status .= '_10';
                    } else if ($seconds >= 77760) {
                        $status .= '_9';
                    } else if ($seconds >= 69120) {
                        $status .= '_8';
                    } else if ($seconds >= 60480) {
                        $status .= '_7';
                    } else if ($seconds >= 51840) {
                        $status .= '_6';
                    } else if ($seconds >= 43200) {
                        $status .= '_5';
                    } else if ($seconds >= 34560) {
                        $status .= '_4';
                    } else if ($seconds >= 25920) {
                        $status .= '_3';
                    } else if ($seconds >= 17280) {
                        $status .= '_2';
                    } else if ($seconds >= 8640) {
                        $status .= '_1';
                    }
                }

                $result[$key]['status'] = $status;
            }
        }

        return $result;
    }


    /**
     * Get all modules
     *
     * @return array
     */
    protected function getAllModulesByTag()
    {
        global $config;

        $filter_tag = '';
        if (empty($this->filter) === false && $this->filter[0] !== '0') {
            $tags = implode(',', $this->filter);
            $filter_tag .= ' AND tm.id_tag IN ('.$tags.')';
        }

        $filter_name = '';
        if (empty($this->search) === false) {
            $filter_name = 'AND nombre LIKE "%'.$this->search.'%"';
        }

        $id_user_groups = '';
        if (users_is_admin() === false) {
            $user_groups = array_keys(users_get_groups($config['user'], 'AR', false));
            if (empty($user_groups) === false) {
                $id_user_groups = sprintf(
                    'INNER JOIN tagente a ON a.id_agente = ae.id_agente
                    AND a.id_grupo IN (%s)',
                    implode(',', $user_groups)
                );
            }
        }

        // All modules.
        $sql = sprintf(
            'SELECT ae.id_agente_modulo AS id, ae.estado AS `status`, tm.id_tag AS id_grupo,
            ae.last_status_change FROM tagente_estado ae
            %s
            INNER JOIN ttag_module tm ON tm.id_agente_modulo = ae.id_agente_modulo
            WHERE 1=1 %s %s GROUP BY ae.id_agente_modulo',
            $id_user_groups,
            $filter_tag,
            $filter_name
        );

        if (is_metaconsole() === true) {
            $nodes = metaconsole_get_connections();
            $result = [];
            $cont = 0;
            foreach ($nodes as $node) {
                try {
                    $nd = new Node($node['id']);
                    $nd->connect();

                    $modules = db_get_all_rows_sql($sql);

                    // Module status.
                    foreach ($modules as $key => $module) {
                        $status = '';
                        switch ($module['status']) {
                            case AGENT_MODULE_STATUS_CRITICAL_BAD:
                            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                            case 1:
                            case 100:
                                $status = 'critical';
                            break;

                            case AGENT_MODULE_STATUS_NORMAL:
                            case AGENT_MODULE_STATUS_NORMAL_ALERT:
                            case 0:
                            case 300:
                                $status = 'normal';
                            break;

                            case AGENT_MODULE_STATUS_WARNING:
                            case AGENT_MODULE_STATUS_WARNING_ALERT:
                            case 2:
                            case 200:
                                $status = 'warning';
                            break;

                            default:
                            case AGENT_MODULE_STATUS_UNKNOWN:
                            case 3:
                                $status = 'unknown';
                            break;
                            case AGENT_MODULE_STATUS_NOT_INIT:
                            case 5:
                                $status = 'notinit';
                            break;
                        }

                        if ($module['last_status_change'] != 0) {
                            $seconds = (time() - $module['last_status_change']);

                            if ($seconds >= SECONDS_1DAY) {
                                $status .= '_10';
                            } else if ($seconds >= 77760) {
                                $status .= '_9';
                            } else if ($seconds >= 69120) {
                                $status .= '_8';
                            } else if ($seconds >= 60480) {
                                $status .= '_7';
                            } else if ($seconds >= 51840) {
                                $status .= '_6';
                            } else if ($seconds >= 43200) {
                                $status .= '_5';
                            } else if ($seconds >= 34560) {
                                $status .= '_4';
                            } else if ($seconds >= 25920) {
                                $status .= '_3';
                            } else if ($seconds >= 17280) {
                                $status .= '_2';
                            } else if ($seconds >= 8640) {
                                $status .= '_1';
                            }
                        }

                        $result[$cont] = $module;
                        $result[$cont]['status'] = $status;
                        $result[$cont]['server'] = $node['id'];
                        ++$cont;
                    }
                } catch (\Exception $e) {
                    $nd->disconnect();
                } finally {
                    $nd->disconnect();
                }
            }
        } else {
            $result = db_get_all_rows_sql($sql);

            // Module status.
            foreach ($result as $key => $module) {
                $status = '';
                switch ($module['status']) {
                    case AGENT_MODULE_STATUS_CRITICAL_BAD:
                    case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                    case 1:
                    case 100:
                        $status = 'critical';
                    break;

                    case AGENT_MODULE_STATUS_NORMAL:
                    case AGENT_MODULE_STATUS_NORMAL_ALERT:
                    case 0:
                    case 300:
                        $status = 'normal';
                    break;

                    case AGENT_MODULE_STATUS_WARNING:
                    case AGENT_MODULE_STATUS_WARNING_ALERT:
                    case 2:
                    case 200:
                        $status = 'warning';
                    break;

                    default:
                    case AGENT_MODULE_STATUS_UNKNOWN:
                    case 3:
                        $status = 'unknown';
                    break;
                    case AGENT_MODULE_STATUS_NOT_INIT:
                    case 5:
                        $status = 'notinit';
                    break;
                }

                if ($module['last_status_change'] != 0) {
                    $seconds = (time() - $module['last_status_change']);

                    if ($seconds >= SECONDS_1DAY) {
                        $status .= '_10';
                    } else if ($seconds >= 77760) {
                        $status .= '_9';
                    } else if ($seconds >= 69120) {
                        $status .= '_8';
                    } else if ($seconds >= 60480) {
                        $status .= '_7';
                    } else if ($seconds >= 51840) {
                        $status .= '_6';
                    } else if ($seconds >= 43200) {
                        $status .= '_5';
                    } else if ($seconds >= 34560) {
                        $status .= '_4';
                    } else if ($seconds >= 25920) {
                        $status .= '_3';
                    } else if ($seconds >= 17280) {
                        $status .= '_2';
                    } else if ($seconds >= 8640) {
                        $status .= '_1';
                    }
                }

                $result[$key]['status'] = $status;
            }
        }

        return $result;
    }


    /**
     * Get all modules group by agents
     *
     * @return array
     */
    protected function getAllModulesByAgents()
    {
        global $config;

        $filter_name = '';
        if (empty($this->search) === false) {
            $filter_name = 'AND nombre LIKE "%'.$this->search.'%"';
        }

        $id_user_groups = '';
        if (users_is_admin() === false) {
            $user_groups = array_keys(users_get_groups($config['user'], 'AR', false));
            if (empty($user_groups) === false) {
                if (empty($this->filter) === false && empty(current($this->filter)) === false) {
                    $user_groups = array_intersect($this->filter, $user_groups);
                    $id_user_groups = sprintf(
                        'INNER JOIN tagente a ON a.id_agente = ae.id_agente
                        AND a.id_grupo IN (%s)',
                        implode(',', $user_groups)
                    );
                } else {
                    $id_user_groups = sprintf(
                        'INNER JOIN tagente a ON a.id_agente = ae.id_agente
                        AND a.id_grupo IN (%s)',
                        implode(',', $user_groups)
                    );
                }
            }
        } else {
            if (empty($this->filter) === false && empty(current($this->filter)) === false) {
                $id_user_groups = sprintf(
                    'INNER JOIN tagente a ON a.id_agente = ae.id_agente
                    AND a.id_grupo IN (%s)',
                    implode(',', $this->filter)
                );
            }
        }

        // All modules.
        $sql = sprintf(
            'SELECT am.id_agente_modulo AS id, ae.estado AS `status`, am.id_agente AS id_grupo,
            ae.last_status_change FROM tagente_modulo am
            INNER JOIN tagente_estado ae ON am.id_agente_modulo = ae.id_agente_modulo
            %s
            WHERE am.disabled = 0 %s GROUP BY ae.id_agente_modulo ORDER BY id_grupo',
            $id_user_groups,
            $filter_name
        );

        if (is_metaconsole() === true) {
            $result = [];
            $nodes = metaconsole_get_connections();
            $cont = 0;
            foreach ($nodes as $node) {
                try {
                    $nd = new Node($node['id']);
                    $nd->connect();

                    $modules = db_get_all_rows_sql($sql);
                    // Module status.
                    foreach ($modules as $key => $module) {
                        $status = '';
                        switch ($module['status']) {
                            case AGENT_MODULE_STATUS_CRITICAL_BAD:
                            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                            case 1:
                            case 100:
                                $status = 'critical';
                            break;

                            case AGENT_MODULE_STATUS_NORMAL:
                            case AGENT_MODULE_STATUS_NORMAL_ALERT:
                            case 0:
                            case 300:
                                $status = 'normal';
                            break;

                            case AGENT_MODULE_STATUS_WARNING:
                            case AGENT_MODULE_STATUS_WARNING_ALERT:
                            case 2:
                            case 200:
                                $status = 'warning';
                            break;

                            default:
                            case AGENT_MODULE_STATUS_UNKNOWN:
                            case 3:
                                $status = 'unknown';
                            break;
                            case AGENT_MODULE_STATUS_NOT_INIT:
                            case 5:
                                $status = 'notinit';
                            break;
                        }

                        if ($module['last_status_change'] != 0) {
                            $seconds = (time() - $module['last_status_change']);

                            if ($seconds >= SECONDS_1DAY) {
                                $status .= '_10';
                            } else if ($seconds >= 77760) {
                                $status .= '_9';
                            } else if ($seconds >= 69120) {
                                $status .= '_8';
                            } else if ($seconds >= 60480) {
                                $status .= '_7';
                            } else if ($seconds >= 51840) {
                                $status .= '_6';
                            } else if ($seconds >= 43200) {
                                $status .= '_5';
                            } else if ($seconds >= 34560) {
                                $status .= '_4';
                            } else if ($seconds >= 25920) {
                                $status .= '_3';
                            } else if ($seconds >= 17280) {
                                $status .= '_2';
                            } else if ($seconds >= 8640) {
                                $status .= '_1';
                            }
                        }

                        $result[$cont] = $module;
                        $result[$cont]['status'] = $status;
                        $result[$cont]['server'] = $node['id'];
                        ++$cont;
                    }
                } catch (\Exception $e) {
                    $nd->disconnect();
                    $result = [];
                } finally {
                    $nd->disconnect();
                }
            }
        } else {
            $result = db_get_all_rows_sql($sql);

            // Module status.
            foreach ($result as $key => $module) {
                $status = '';
                switch ($module['status']) {
                    case AGENT_MODULE_STATUS_CRITICAL_BAD:
                    case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                    case 1:
                    case 100:
                        $status = 'critical';
                    break;

                    case AGENT_MODULE_STATUS_NORMAL:
                    case AGENT_MODULE_STATUS_NORMAL_ALERT:
                    case 0:
                    case 300:
                        $status = 'normal';
                    break;

                    case AGENT_MODULE_STATUS_WARNING:
                    case AGENT_MODULE_STATUS_WARNING_ALERT:
                    case 2:
                    case 200:
                        $status = 'warning';
                    break;

                    default:
                    case AGENT_MODULE_STATUS_UNKNOWN:
                    case 3:
                        $status = 'unknown';
                    break;
                    case AGENT_MODULE_STATUS_NO_DATA:
                    case AGENT_MODULE_STATUS_NOT_INIT:
                    case 5:
                        $status = 'notinit';
                    break;
                }

                if ($module['last_status_change'] != 0) {
                    $seconds = (time() - $module['last_status_change']);

                    if ($seconds >= SECONDS_1DAY) {
                        $status .= '_10';
                    } else if ($seconds >= 77760) {
                        $status .= '_9';
                    } else if ($seconds >= 69120) {
                        $status .= '_8';
                    } else if ($seconds >= 60480) {
                        $status .= '_7';
                    } else if ($seconds >= 51840) {
                        $status .= '_6';
                    } else if ($seconds >= 43200) {
                        $status .= '_5';
                    } else if ($seconds >= 34560) {
                        $status .= '_4';
                    } else if ($seconds >= 25920) {
                        $status .= '_3';
                    } else if ($seconds >= 17280) {
                        $status .= '_2';
                    } else if ($seconds >= 8640) {
                        $status .= '_1';
                    }
                }

                $result[$key]['status'] = $status;
            }
        }

        return $result;
    }


    /**
     * GetData
     *
     * @return array
     */
    public function getData()
    {
        switch ($this->type) {
            case 3:
                $data = $this->getAllModulesByAgents();
            break;

            case 2:
                $data = $this->getAllModulesByGroup();
            break;

            case 1:
                $data = $this->getAllModulesByTag();
            break;

            case 0:
            default:
                $data = $this->getAllAgents();
            break;
        }

        return $data;
    }


    /**
     * GetDataJson
     *
     * @return json
     */
    public function getDataJson()
    {
        $return = $this->getData();
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
    public function ajaxMethod(string $method): bool
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
        $result = $this->getData();

        if (empty($result) === true) {
            echo '<div style="position: absolute; top:70px; left:20px">'.__('No data found').'</div>';
            return;
        }

        $count_result = count($result);

        $scale = ($this->width / $this->height);
        $Yaxis = $this->getYAxis($count_result, $scale);
        if ($count_result <= 3) {
            $Xaxis = $count_result;
            $Yaxis = 1;
        } else {
            $Xaxis = (int) ceil($Yaxis * $scale);
            $Yaxis = ceil($Yaxis);
        }

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
        $cont = 1;
        foreach ($result as $value) {
            $name = $value['id'];
            if (empty($value['server']) === false) {
                $name .= '|'.$value['server'];
            }

            echo '<rect id="'.$this->randomId.'_'.$cont.'" class="'.$value['status'].' hover"
                width="1" height="1" x ="'.$contX.' "y="'.$contY.'" name="'.$name.'" />';

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

            $cont++;
        }

        ?>
        <script type="text/javascript">
            $('rect').click(function() {
                const type = <?php echo $this->type; ?>;
                const name = $(`#${this.id}`).attr("name");
                const id = name.split('|')[0];
                const server = name.split('|')[1];

                let height = 400;
                let width = 530;
                switch (type) {
                    case 0:
                        height = 670;
                        width = 460;
                        break;

                    case 2:
                    case 3:
                        height = 450;
                        width = 460;
                        break;

                    default:
                        break;
                }

                $("#info_dialog").dialog({
                    resizable: true,
                    draggable: true,
                    modal: true,
                    closeOnEscape: true,
                    height: height,
                    width: width,
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
                                id_server: server,
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
        if (count($groups) > 1 && $this->group === 1 && $this->dashboard === false) {
            $x_back = 0;
            $y_back = 0;
            $x_text_correction = 0.25;

            if ($count_result <= 10) {
                $fontSize = 'small-size';
                $stroke = 'small-stroke';
            } else if ($count_result > 10 && $count_result <= 100) {
                $fontSize = 'tiny-size';
                $stroke = 'tiny-stroke';
            } else if ($count_result > 100 && $count_result <= 1000) {
                $fontSize = 'medium-size';
                $stroke = 'medium-stroke';
            } else if ($count_result > 1000 && $count_result <= 10000) {
                $fontSize = 'big-size';
                $stroke = 'big-stroke';
            } else {
                $fontSize = 'huge-size';
                $stroke = 'huge-stroke';
            }

            echo '<polyline points="0,0 '.$Xaxis.',0" class="polyline '.$stroke.'" />';
            foreach ($groups as $key => $group) {
                $name = '';
                switch ($this->type) {
                    case 3:
                        $name = agents_get_alias($key);
                    break;

                    case 2:
                        $name = modules_get_modulegroup_name($key);
                    break;

                    case 1:
                        $name = tags_get_name($key);
                    break;

                    case 0:
                    default:
                        $name = groups_get_name($key);
                    break;
                }

                if (($x_back + $group) <= $Xaxis) {
                    $x_position = ($x_back + $group);
                    $y_position = $y_back;

                    if ($y_back === 0 && $x_back === 0) {
                        $points = sprintf(
                            '%d,%d %d,%d',
                            $x_back,
                            $y_back,
                            $x_back,
                            ($y_back + 1)
                        );

                        echo '<polyline points="'.$points.'" class="polyline '.$stroke.'" />';
                    }

                    $points = sprintf(
                        '%d,%d %d,%d %d,%d',
                        $x_back,
                        ($y_position + 1),
                        $x_position,
                        ($y_position + 1),
                        $x_position,
                        $y_back
                    );

                    echo '<polyline points="'.$points.'" class="polyline '.$stroke.'" />';

                    // Name.
                    echo '<text x="'.((($x_position - $x_back) / 2) + $x_back - $x_text_correction).'" y="'.($y_position + 1 - 0.01).'"
                        class="'.$fontSize.'">'.$name.'</text>';

                    $x_back = $x_position;
                    if ($x_position === $Xaxis) {
                        $points = sprintf(
                            '%d,%d %d,%d',
                            $x_position,
                            $y_back,
                            $x_position,
                            ($y_back + 1)
                        );

                        echo '<polyline points="'.$points.'" class="polyline '.$stroke.'" />';

                        $y_back++;
                        $x_back = 0;
                    }
                } else {
                    $round = (int) floor(($x_back + $group) / $Xaxis);
                    $y_position = ($round + $y_back);

                    if ($round === 1) {
                        // One line.
                        $x_position = (($x_back + $group) - $Xaxis);

                        if ($x_position <= $x_back) {
                            // Bottom line.
                            $points = sprintf(
                                '%d,%d %d,%d',
                                $x_back,
                                $y_position,
                                $Xaxis,
                                ($y_position)
                            );

                            echo '<polyline points="'.$points.'" class="polyline '.$stroke.'" />';
                        }

                        // Bottom of last line.
                        $points = sprintf(
                            '%d,%d %d,%d',
                            0,
                            ($y_position + 1),
                            $x_position,
                            ($y_position + 1)
                        );

                        echo '<polyline points="'.$points.'" class="polyline '.$stroke.'" />';

                        // Name.
                        echo '<text x="'.(($x_position) / 2 - $x_text_correction).'" y="'.($y_position + 1 - 0.01).'"
                            class="'.$fontSize.'">'.$name.'</text>';

                        // Bottom-right of last line.
                        $points = sprintf(
                            '%d,%d %d,%d',
                            $x_position,
                            ($y_position),
                            $x_position,
                            ($y_position + 1)
                        );

                        echo '<polyline points="'.$points.'" class="polyline '.$stroke.'" />';

                        if ($x_position > $x_back) {
                            // Bottom-top of last line.
                            $points = sprintf(
                                '%d,%d %d,%d',
                                $x_position,
                                ($y_position),
                                $Xaxis,
                                ($y_position)
                            );

                            echo '<polyline points="'.$points.'" class="polyline '.$stroke.'" />';
                        }
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

                        echo '<polyline points="'.$points.'" class="polyline '.$stroke.'" />';

                        // Bottom-right of last line.
                        $points = sprintf(
                            '%d,%d %d,%d',
                            $x_position,
                            ($y_position),
                            $x_position,
                            ($y_position + 1)
                        );

                        echo '<polyline points="'.$points.'" class="polyline '.$stroke.'" />';

                        // Name.
                        echo '<text x="'.(($x_position) / 2 - $x_text_correction).'" y="'.($y_position + 1 - 0.02).'"
                            class="'.$fontSize.'">'.$name.'</text>';

                        // Bottom-top of last line.
                        $points = sprintf(
                            '%d,%d %d,%d',
                            $x_position,
                            ($y_position),
                            $Xaxis,
                            ($y_position)
                        );

                        echo '<polyline points="'.$points.'" class="polyline '.$stroke.'" />';
                    }

                    if ($x_position === $Xaxis) {
                        $x_position = 0;
                    }

                    $x_back = $x_position;
                    $y_back = $y_position;
                }
            }
        }

        echo '</svg>';

        // Dialog.
        echo '<div id="info_dialog"></div>';
    }


}
