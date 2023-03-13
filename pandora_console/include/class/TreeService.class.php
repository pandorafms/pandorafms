<?php
/**
 * Service tree view.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Enterprise
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

// Begin.
global $config;

require_once $config['homedir'].'/include/class/Tree.class.php';

use PandoraFMS\Enterprise\Service;

/**
 * Class to handle service tree view.
 */
class TreeService extends Tree
{

    /**
     * Some definitions.
     *
     * @var boolean
     */
    protected $propagateCounters = true;

    /**
     * Some definitions.
     *
     * @var boolean
     */
    protected $displayAllGroups = false;

    /**
     * If element is stored on remote node, this value will be greater than 0.
     *
     * @var integer
     */
    public $metaID = 0;

    /**
     * Flag to avoid double connection to node.
     *
     * @var boolean
     */
    private $connectedToNode = false;


    /**
     * Builder.
     *
     * @param mixed   $type           Type.
     * @param string  $rootType       RootType.
     * @param integer $id             Id.
     * @param integer $rootID         RootID.
     * @param boolean $serverID       ServerID.
     * @param string  $childrenMethod ChildrenMethod.
     * @param string  $access         Access.
     * @param integer $id_server_meta Id_server_meta.
     */
    public function __construct(
        $type,
        $rootType='',
        $id=-1,
        $rootID=-1,
        $serverID=false,
        $childrenMethod='on_demand',
        $access='AR',
        $id_server_meta=0
    ) {
        global $config;

        if ($id_server_meta > 0) {
            $this->metaID = $id_server_meta;
            $this->serverID = $id_server_meta;
        }

        parent::__construct(
            $type,
            $rootType,
            $id,
            $rootID,
            $serverID,
            $childrenMethod,
            $access,
            $id_server_meta
        );

        $this->L1fieldName = 'id_group';
        $this->L1extraFields = [
            'ts.name AS `name`',
            'ts.id AS `sid`',
        ];

        $this->filter['statusAgent'] = AGENT_STATUS_ALL;

        $this->avoid_condition = true;

        $this->L2inner = 'LEFT JOIN tservice_element tse
									ON tse.id_agent = ta.id_agente';

        $this->L2condition = sprintf(
            ' AND tse.id_service=%d AND tse.id_server_meta=0 ',
            $this->id
        );

    }


    /**
     * Setter (propagate counters).
     *
     * @param boolean $value Set.
     *
     * @return void
     */
    public function setPropagateCounters($value)
    {
        $this->propagateCounters = (bool) $value;
    }


    /**
     * Set display all groups.
     *
     * @param boolean $value Set.
     *
     * @return void
     */
    public function setDisplayAllGroups($value)
    {
        $this->displayAllGroups = (bool) $value;
    }


    /**
     * Generates tree data.
     *
     * @return void
     */
    protected function getData()
    {
        if (is_metaconsole() === true && $this->metaID > 0) {
            // Impersonate node.
            \enterprise_include_once('include/functions_metaconsole.php');
            \enterprise_hook(
                'metaconsole_connect',
                [
                    null,
                    $this->metaID,
                ]
            );
            $this->connectedToNode = true;
        }

        if ($this->id == -1) {
            $this->getFirstLevel();
        } else if ($this->type == 'services') {
            $this->getSecondLevel();
        } else if ($this->type == 'agent') {
            $this->filter['showDisabled'] = true;
            $this->getThirdLevel();
        }

        if (is_metaconsole() === true && $this->metaID > 0) {
            // Restore connection.
            \enterprise_hook('metaconsole_restore_db');
        }
    }


    /**
     * Generates first level data.
     *
     * @return void
     */
    protected function getFirstLevel()
    {
        global $config;

        $processed_items = $this->getProcessedServices();
        $ids = array_keys($processed_items);

        $filter = ['id' => $ids];

        $own_info = get_user_info($config['id_user']);

        if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
            $display_all_services = true;
        } else {
            $display_all_services = false;
        }

        $this->tree = [];

        $services = services_get_services($filter, false, $display_all_services);

        foreach ($services as $row) {
            $status = services_get_status($row, true);

            switch ($status) {
                case SERVICE_STATUS_NORMAL:
                    $serviceStatusLine = COL_NORMAL;
                break;

                case SERVICE_STATUS_CRITICAL:
                    $serviceStatusLine = COL_CRITICAL;
                break;

                case SERVICE_STATUS_WARNING:
                    $serviceStatusLine = COL_WARNING;
                break;

                case SERVICE_STATUS_UNKNOWN:
                default:
                    $serviceStatusLine = COL_UNKNOWN;
                break;
            }

            $processed_items[$row['id']]['statusImageHTML'] = html_print_div(
                [
                    'class' => 'node-service-status',
                    'style' => 'background-color: '.$serviceStatusLine,
                ],
                true
            );
        }

        $this->tree = $processed_items;
    }


    /**
     * Retrieve root services.
     *
     * @return array Of root services.
     */
    protected function getProcessedServices()
    {
        $is_favourite = $this->getServiceFavouriteFilter();
        $service_search = $this->getServiceNameSearchFilter();

        if (users_can_manage_group_all('AR')) {
            $groups_acl = '';
        } else {
            $groups_acl = 'AND ts.id_group IN ('.implode(',', $this->userGroupsArray).')';
        }

        $exclude_children = 'ts.id NOT IN (
            SELECT DISTINCT id_service_child
            FROM tservice_element
            WHERE id_server_meta = 0
        )';

        if ($service_search !== '') {
            $exclude_children = '1=1';
        }

        $sql = sprintf(
            'SELECT 
                ts.id,
                ts.id_agent_module,
                ts.name,
                ts.name as `alias`,
                ts.description as `description`,
                ts.id as `rootID`,
                "services" as `rootType`,
                "services" as `type`,
                ts.quiet,
                SUM(if((tse.id_agent<>0), 1, 0)) AS `total_agents`,
                SUM(if((tse.id_agente_modulo<>0), 1, 0)) AS `total_modules`,
                SUM(if((tse.id_service_child<>0), 1, 0)) AS `total_services`,
                SUM(if((tse.rules != ""), 1, 0)) AS `total_dynamic`
            FROM tservice ts
            LEFT JOIN tservice_element tse
                ON tse.id_service = ts.id
             WHERE %s
                %s
                %s
                %s
            GROUP BY ts.id',
            $exclude_children,
            $is_favourite,
            $service_search,
            $groups_acl
        );

        $stats = db_get_all_rows_sql($sql);

        $services = [];

        foreach ($stats as $service) {
            $services[$service['id']] = $this->getProcessedItem(
                $services[$service['id']]
            );
            $n_items = ($service['total_services'] + $service['total_agents']);
            $n_items += ($service['total_modules'] + $service['total_dynamic']);

            if ($n_items > 0) {
                $services[$service['id']]['searchChildren'] = 1;
            } else {
                $services[$service['id']]['searchChildren'] = 0;
            }

            $services[$service['id']]['counters'] = [
                'total_services' => $service['total_services'],
                'total_agents'   => $service['total_agents'],
                'total_modules'  => $service['total_modules'],
            ];
            $services[$service['id']]['name'] = $service['name'];
            $services[$service['id']]['id'] = $service['id'];
            $services[$service['id']]['description'] = $service['description'];
            $services[$service['id']]['serviceDetail'] = 'index.php?sec=network&sec2=enterprise/operation/services/services&tab=service_map&id_service='.(int) $service['id'];
        }

        return $services;
    }


    /**
     * Retrieve first level fields.
     *
     * @deprecated 746.
     *
     * @return string With a first level fields.
     */
    protected function getFirstLevelFields()
    {
        $fields = [];

        return implode(',', array_merge($fields, $this->L1extraFields));
    }


    /**
     * Retrieves elements (second level) from selected rootID.
     *
     * @return void
     */
    protected function getSecondLevel()
    {
        global $config;

        $service = new Service($this->id, true);

        $output = [];
        foreach ($service->children() as $item) {
            $tmp = [];

            if ($this->metaID > 0) {
                $tmp['metaID'] = $this->metaID;
            } else if ($item->id_server_meta() !== 0) {
                $tmp['metaID'] = $item->id_server_meta();
            }

            $tmp['serverID'] = $tmp['metaID'];

            switch ($item->type()) {
                case SERVICE_ELEMENT_AGENT:
                    if ($item->agent() === null) {
                        // Skip item.
                        continue 2;
                    }

                    $tmp['id'] = $item->agent()->id_agente();
                    $tmp['name'] = $item->agent()->nombre();
                    $tmp['alias'] = $item->agent()->alias();
                    $tmp['fired_count'] = $item->agent()->fired_count();
                    $tmp['normal_count'] = $item->agent()->normal_count();
                    $tmp['warning_count'] = $item->agent()->warning_count();
                    $tmp['critical_count'] = $item->agent()->critical_count();
                    $tmp['unknown_count'] = $item->agent()->unknown_count();
                    $tmp['notinit_count'] = $item->agent()->notinit_count();
                    $tmp['total_count'] = $item->agent()->total_count();

                    if ($item->agent()->quiet() > 0
                        || $item->agent()->cps() > 0
                    ) {
                        $tmp['quiet'] = 1;
                    } else {
                        $tmp['quiet'] = 0;
                    }

                    $tmp['state_critical'] = $tmp['critical_count'];
                    $tmp['state_warning'] = $tmp['warning_count'];
                    $tmp['state_unknown'] = $tmp['unknown_count'];
                    $tmp['state_notinit'] = $tmp['notinit_count'];
                    $tmp['state_normal'] = $tmp['normal_count'];
                    $tmp['state_total'] = $tmp['total_count'];
                    $tmp['type'] = SERVICE_ELEMENT_AGENT;
                    $tmp['rootID'] = $this->rootID;
                    $tmp['rootType'] = $this->rootType;
                    $tmp['counters'] = [
                        'alerts'   => $item->agent()->fired_count(),
                        'ok'       => $item->agent()->normal_count(),
                        'warning'  => $item->agent()->warning_count(),
                        'critical' => $item->agent()->critical_count(),
                        'unknown'  => $item->agent()->unknown_count(),
                        'not_init' => $item->agent()->notinit_count(),
                        'total'    => $item->agent()->total_count(),
                    ];

                    switch ($item->agent()->lastStatus()) {
                        case AGENT_STATUS_NORMAL:
                            $tmp['statusImageHTML'] = html_print_div(['class' => 'tree-service-status', 'style' => 'background-color: #82b92e', 'title' => __('Normal status') ], true);
                        break;

                        case AGENT_STATUS_CRITICAL:
                        case AGENT_STATUS_ALERT_FIRED:
                            $tmp['statusImageHTML'] = html_print_div(['class' => 'tree-service-status', 'style' => 'background-color: #e63c52', 'title' => __('Critical status') ], true);
                        break;

                        case AGENT_STATUS_WARNING:
                            $tmp['statusImageHTML'] = html_print_div(['class' => 'tree-service-status', 'style' => 'background-color: #f3b200', 'title' => __('Warning status') ], true);
                        break;

                        case AGENT_STATUS_UNKNOWN:
                        default:
                            $tmp['statusImageHTML'] = html_print_div(['class' => 'tree-service-status', 'style' => 'background-color: #B2B2B2', 'title' => __('Unknown status') ], true);
                        break;
                    }

                    $tmp['children'] = [];

                    if (check_acl($config['id_user'], $item->agent()->id_grupo(), 'AR')) {
                        $tmp['searchChildren'] = 1;
                    } else {
                        $tmp['searchChildren'] = 0;
                        $tmp['noAcl'] = 1;
                    }

                    $tmp['showEventsBtn'] = 1;
                    $tmp['eventAgent'] = $item->agent()->id_agente();
                    $tmp['disabled'] = (bool) $item->agent()->disabled();
                break;

                case SERVICE_ELEMENT_MODULE:
                    if ($item->module() === null) {
                        // Skip item.
                        continue 2;
                    }

                    $tmp['id'] = $item->module()->id_agente_modulo();
                    $tmp['name'] = $item->module()->nombre();
                    $tmp['id_tipo_modulo'] = $item->module()->id_tipo_modulo();
                    $tmp['id_modulo'] = $item->module()->id_modulo();
                    $tmp['estado'] = $item->module()->lastStatus();
                    $tmp['datos'] = $item->module()->lastValue();
                    $tmp['parent'] = $item->module()->parent_module_id();
                    $alerts = alerts_get_alerts_module_name(
                        $item->module()->id_agente_modulo()
                    );
                    if ($alerts !== false) {
                        // Seems to be used as 'flag'.
                        $tmp['alerts'] = $alerts[0]['id'];
                    }

                    $tmp['unit'] = $item->module()->unit();
                    $tmp['type'] = SERVICE_ELEMENT_MODULE;
                    $tmp['id_module_type'] = $item->module()->id_tipo_modulo();
                    $tmp['server_type'] = $tmp['id_module_type'];
                    $tmp['status'] = $item->module()->lastStatus();
                    $tmp['value'] = modules_get_agentmodule_data_for_humans(
                        array_merge(
                            $item->module()->toArray(),
                            [ 'datos' => $item->module()->lastValue() ]
                        )
                    );

                    $title = $item->module()->lastStatusTitle();

                    if (is_numeric($item->module()->lastValue())) {
                        $divisor = get_data_multiplier($item->module()->unit());
                        $title .= ' : '.format_for_graph(
                            $item->module()->lastValue(),
                            1,
                            '.',
                            ',',
                            $divisor
                        );
                    } else {
                        $title .= ' : '.substr(
                            io_safe_output(
                                $item->module()->lastValue()
                            ),
                            0,
                            42
                        );
                    }

                    $tmp['serverName'] = $item->module()->agent()->server_name();
                    $tmp['serverID'] = $tmp['metaID'];
                    $tmp['statusText'] = $item->module()->lastStatusText();
                    $tmp['showGraphs'] = 1;
                    $tmp['showEventsBtn'] = 1;
                    $tmp['eventAgent'] = $item->module()->id_agente();
                    $tmp['disabled'] = $item->module()->disabled();

                    $html = html_print_div(
                        [ 'style' => 'width:7px;background-color: '.$item->module()->lastStatusColor() ],
                        true
                    );

                    $tmp['statusImageHTML'] = $html;
                    $tmp = array_merge(
                        $tmp,
                        $this->getModuleGraphLinks(
                            $tmp
                        )
                    );
                break;

                case SERVICE_ELEMENT_SERVICE:
                    if ($item->service() === null) {
                        // Skip item.
                        continue 2;
                    }

                    $title = get_parameter('title', '');
                    if (empty($title) === true) {
                        $tmp['title'] = '';
                    } else {
                        $tmp['title'] = io_safe_output($title).'/';
                    }

                    $tmp['title'] .= $service->name();
                    $tmp['id'] = (int) $item->service()->id();
                    $tmp['name'] = $item->service()->name();
                    $tmp['alias'] = $item->service()->name();
                    $tmp['description'] = $item->service()->description();
                    $tmp['elementDescription'] = $item->description();
                    $tmp['disabled'] = $item->service()->disabled();

                    $counters = [
                        'total_modules'  => 0,
                        'total_agents'   => 0,
                        'total_services' => 0,
                        'total_dynamic'  => 0,
                        'total'          => 0,
                    ];

                    if (is_metaconsole() === false
                        || (isset($config['realtimestats']) === true
                        && $config['realtimestats'] === true
                        && $tmp['metaID'] > 0)
                    ) {
                        // Look for counters.
                        if ($this->connectedToNode === false
                            && is_metaconsole() === true
                            && $tmp['metaID'] > 0
                        ) {
                            // Impersonate node.
                            \enterprise_include_once('include/functions_metaconsole.php');
                            \enterprise_hook(
                                'metaconsole_connect',
                                [
                                    null,
                                    $tmp['metaID'],
                                ]
                            );
                        }

                        if (check_acl($config['id_user'], $item->service()->id_group(), 'AR')) {
                            $grandchildren = $item->service()->children();
                        }

                        if ($this->connectedToNode === false
                            && is_metaconsole() === true
                            && $tmp['metaID'] > 0
                        ) {
                            // Restore connection.
                            \enterprise_hook('metaconsole_restore_db');
                        }

                        if (is_array($grandchildren) === true) {
                            $counters = array_reduce(
                                $grandchildren,
                                function ($carry, $item) {
                                    if ($item->type() === SERVICE_ELEMENT_MODULE) {
                                        $carry['total_modules']++;
                                    } else if ($item->type() === SERVICE_ELEMENT_AGENT) {
                                        $carry['total_agents']++;
                                    } else if ($item->type() === SERVICE_ELEMENT_SERVICE) {
                                        $carry['total_services']++;
                                    } else if ($item->type() === SERVICE_ELEMENT_DYNAMIC) {
                                        $carry['total_dynamic']++;
                                    }

                                    $carry['total']++;

                                    return $carry;
                                },
                                $counters
                            );
                        }

                        if ($counters['total'] > 0) {
                            $tmp['searchChildren'] = 1;
                        }
                    } else {
                        // Always search for.
                        $tmp['searchChildren'] = 1;
                    }

                    $tmp['type'] = 'services';
                    $tmp['rootType'] = 'services';
                    $tmp['children'] = [];
                    $tmp['serviceDetail'] = ui_get_full_url(
                        'index.php?sec=network&sec2=enterprise/operation/services/services&tab=service_map&id_service='.$item->service()->id()
                    );
                    $tmp['counters'] = $counters;
                    $tmp['rootID'] = $this->rootID;
                    switch ($item->service()->lastStatus()) {
                        case SERVICE_STATUS_NORMAL:
                            $tmp['statusImageHTML'] = html_print_div(['class' => 'tree-service-status', 'style' => 'background-color: #82b92e', 'title' => __('Normal status') ], true);
                        break;

                        case SERVICE_STATUS_CRITICAL:
                            $tmp['statusImageHTML'] = html_print_div(['class' => 'tree-service-status', 'style' => 'background-color: #e63c52', 'title' => __('Critical status') ], true);
                        break;

                        case SERVICE_STATUS_WARNING:
                            $tmp['statusImageHTML'] = html_print_div(['class' => 'tree-service-status', 'style' => 'background-color: #f3b200', 'title' => __('Warning status') ], true);
                        break;

                        case SERVICE_STATUS_UNKNOWN:
                        default:
                            $tmp['statusImageHTML'] = html_print_div(['class' => 'tree-service-status', 'style' => 'background-color: #B2B2B2', 'title' => __('Unknown status') ], true);
                        break;
                    }
                break;

                default:
                    // Unknown type.
                continue 2;
            }

            $output[] = $tmp;
        }

        $this->tree = $output;
    }


    /**
     * SQL query to retrieve second level items.
     *
     * @return string SQL.
     */
    protected function getSecondLevelServicesSql()
    {
        $group_acl = $this->getGroupAclCondition();

        $sql = sprintf(
            'SELECT 
                ts.id,
                ts.id_agent_module,
                ts.name,
                ts.name as `alias`,
                ts.description as `description`,
                tse.description as `elementDescription`,
                tse.id_service as `rootID`,
                "services" as `rootType`,
                "services" as `type`,
                ts.quiet,
                tse.id_server_meta,
                SUM(if((tse.id_agent<>0), 1, 0)) AS `total_agents`,
                SUM(if((tse.id_agente_modulo<>0), 1, 0)) AS `total_modules`,
                SUM(if((tse.id_service_child<>0), 1, 0)) AS `total_services`
            FROM tservice ts
            INNER JOIN tservice_element tse
                ON tse.id_service_child = ts.id
            WHERE 
                tse.id_service = %d
                %s
            GROUP BY ts.id',
            $this->id,
            $group_acl
        );

        return $sql;
    }


    /**
     * Retrieve SQL filter for current filte.r
     *
     * @return string SQL filter.
     */
    protected function getServiceFavouriteFilter()
    {
        if (isset($this->filter['is_favourite']) === true
            && empty($this->filter['is_favourite']) === false
        ) {
            return ' AND is_favourite = 1';
        }

        return '';
    }


    /**
     * Retrieve SQL filter for current filter
     *
     * @return string SQL filter.
     */
    protected function getServiceNameSearchFilter()
    {
        if (isset($this->filter['searchService']) === true
            && empty($this->filter['searchService']) === false
        ) {
            return " AND (ts.name LIKE '%".$this->filter['searchService']."%' OR ts.description LIKE '%".$this->filter['searchService']."%')";
        }

        return '';
    }


    /**
     * Overwrites partial functionality of general Tree.class.
     *
     * @param array $module Data of given module.
     *
     * @return array Complementary information.
     */
    protected function getModuleGraphLinks(array $module)
    {
        $graphType = return_graphtype($module['id_module_type']);
        $url = ui_get_full_url(
            'operation/agentes/stat_win.php',
            false,
            false,
            false
        );
        $winHandle = dechex(crc32($module['id'].$module['name']));

        $graph_params = [
            'type'    => $graphType,
            'period'  => SECONDS_1DAY,
            'id'      => $module['id'],
            'refresh' => SECONDS_10MINUTES,
        ];

        if (is_metaconsole() === true) {
            // Set the server id.
            $graph_params['server'] = $module['serverID'];
        }

        $graph_params_str = http_build_query($graph_params);
        $moduleGraphURL = $url.'?'.$graph_params_str;

        return [
            'moduleGraph' => [
                'url'    => $moduleGraphURL,
                'handle' => $winHandle,
            ],
            'snapshot'    => ui_get_snapshot_link(
                [
                    'id_module'   => $module['id'],
                    'interval'    => $module['current_interval'],
                    'module_name' => $module['name'],
                    'id_node'     => (($module['serverID'] > 0) ? $module['serverID'] : 0),
                ],
                true
            ),
        ];

    }


    /**
     * Needs to be defined to maintain Tree view functionality.
     *
     * @param integer $status Status.
     *
     * @return string Fixed string.
     */
    protected function getAgentStatusFilter(
        $status=self::TV_DEFAULT_AGENT_STATUS
    ) {
        return '';
    }


}
