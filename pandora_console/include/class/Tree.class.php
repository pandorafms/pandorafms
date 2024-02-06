<?php
/**
 * Tree view.
 *
 * @category   Tree
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */
class Tree
{

    protected $type = null;

    protected $rootType = null;

    protected $idGroup = null;

    protected $id = -1;

    protected $rootID = -1;

    protected $serverID = false;

    protected $serverName = '';

    protected $tree = [];

    protected $filter = [];

    protected $childrenMethod = 'on_demand';

    protected $userGroupsACL;

    protected $userGroups;

    protected $userGroupsArray;

    protected $access = false;

    protected $L1fieldName = '';

    protected $L1fieldNameSql = '';

    protected $L1extraFields = [];

    protected $L1inner = '';

    protected $L1innerInside = '';

    protected $L1orderByFinal = '';

    protected $L2condition = '';

    protected $L2conditionInside = '';

    protected $L2inner = '';

    protected $avoid_condition = false;

    protected $L3forceTagCondition = false;

    const TV_DEFAULT_AGENT_STATUS = -1;


    public function __construct(
        $type,
        $rootType='',
        $id=-1,
        $rootID=-1,
        $serverID=false,
        $childrenMethod='on_demand',
        $access='AR',
        $id_meta_server=0,
        $id_group=0
    ) {
        $this->type = $type;
        $this->rootType = !empty($rootType) ? $rootType : $type;
        $this->id = $id;
        $this->rootID = !empty($rootID) ? $rootID : $id;
        $this->serverID = $serverID;
        $this->idGroup = $id_group;
        if (is_metaconsole() && $id_meta_server == 0) {
            $this->serverName = metaconsole_get_server_by_id($serverID);
        }

        $this->childrenMethod = $childrenMethod;
        $this->access = $access;

        $userGroupsACL = users_get_groups(false, $this->access);

        $this->userGroupsACL = empty($userGroupsACL) ? false : $userGroupsACL;
        $this->userGroups = $this->userGroupsACL;
        $this->userGroupsArray = array_keys($this->userGroups);

        global $config;
        include_once $config['homedir'].'/include/functions_servers.php';
        include_once $config['homedir'].'/include/functions_modules.php';
        include_once $config['homedir'].'/include/functions_tags.php';
        enterprise_include_once('include/functions_agents.php');

        if (is_metaconsole() && $id_meta_server == 0) {
            enterprise_include_once('meta/include/functions_ui_meta.php');
        }
    }


    public function setFilter($filter)
    {
        // There is not module filter in metaconsole.
        if (is_metaconsole()) {
            $filter['searchMetaconsoleModule'] = $filter['searchModule'];
            $filter['searchModule'] = '';
            $filter['statusMetaconsoleModule'] = $filter['statusModule'];
            $filter['statusModule'] = self::TV_DEFAULT_AGENT_STATUS;
        }

        $this->filter = $filter;
    }


    protected function getEmptyModuleFilterStatus()
    {
        if ($this->filter['statusModule'] === 'fired') {
            $this->filter['statusModuleOriginal'] = $this->filter['statusModule'];
            $this->filter['statusModule'] = -1;
        }

        return (
            !isset($this->filter['statusModule']) ||
            $this->filter['statusModule'] == -1
        );
    }


    protected function getModuleSearchFilter()
    {
        if (empty($this->filter['searchModule'])) {
            return '';
        }

        return " AND tam.nombre LIKE '%%".str_replace('%', '%%', $this->filter['searchModule'])."%%' ";
    }


    protected function getAgentSearchFilter()
    {
        if (empty($this->filter['searchAgent'])) {
            return '';
        }

        return " AND LOWER(ta.alias) LIKE LOWER('%%".str_replace('%', '%%', $this->filter['searchAgent'])."%%')";
    }


    /**
     * Show disabled modules
     *
     * @return string Sql disabled.
     */
    protected function getDisabledFilter()
    {
        $only_disabled = (is_metaconsole() === true) ? (int) $this->filter['show_disabled'] : 0;

        if (empty($this->filter['showDisabled'])) {
            return ' tam.disabled = 0 AND ta.disabled = '.$only_disabled;
        }

        return ' 1 = 1';
    }


    protected function getAgentStatusFilter($status=self::TV_DEFAULT_AGENT_STATUS)
    {
        if ($status == self::TV_DEFAULT_AGENT_STATUS) {
            $status = $this->filter['statusAgent'];
        }

        $agent_status_filter = '';
        switch ($status) {
            case AGENT_STATUS_ALL:
            break;

            case AGENT_STATUS_NOT_INIT:
                $agent_status_filter = ' AND (ta.total_count = 0
											OR ta.total_count = ta.notinit_count) ';
            break;

            case AGENT_STATUS_CRITICAL:
                $agent_status_filter = ' AND ta.critical_count > 0 ';
            break;

            case AGENT_STATUS_WARNING:
                $agent_status_filter = ' AND (ta.critical_count = 0
											AND ta.warning_count > 0) ';
            break;

            case AGENT_STATUS_UNKNOWN:
                $agent_status_filter = ' AND (ta.critical_count = 0
											AND ta.warning_count = 0
											AND ta.unknown_count > 0) ';
            break;

            case AGENT_STATUS_NORMAL:
                $agent_status_filter = ' AND (ta.critical_count = 0
											AND ta.warning_count = 0
											AND ta.unknown_count = 0
											AND ta.normal_count > 0) ';
            break;

            case AGENT_STATUS_NOT_NORMAL:
                $agent_status_filter = ' AND (ta.critical_count > 0
											OR ta.warning_count > 0) ';
            break;

            case AGENT_STATUS_ALERT_FIRED:
                $agent_status_filter = ' AND ta.fired_count > 0 ';
            break;
        }

        return $agent_status_filter;
    }


    protected function getFirstLevelFields()
    {
        $fields = [
            'g AS '.$this->L1fieldName,
            'SUM(x_critical) AS total_critical_count',
            'SUM(x_warning) AS total_warning_count',
            'SUM(x_normal) AS total_normal_count',
            'SUM(x_unknown) AS total_unknown_count',
            'SUM(x_not_init) AS total_not_init_count',
            'SUM(x_alerts) AS total_alerts_count',
            'SUM(x_total) AS total_count',
        ];
        return implode(',', array_merge($fields, $this->L1extraFields));
    }


    protected function getFirstLevelFieldsInside()
    {
        return [
            'warning'  => [
                'header'    => '0 AS x_critical, SUM(total) AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g',
                'condition' => 'AND '.agents_get_status_clause(AGENT_STATUS_WARNING, $this->filter['show_not_init_agents']),
            ],
            'critical' => [
                'header'    => 'SUM(total) AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g',
                'condition' => 'AND '.agents_get_status_clause(AGENT_STATUS_CRITICAL, $this->filter['show_not_init_agents']),
            ],
            'normal'   => [
                'header'    => '0 AS x_critical, 0 AS x_warning, SUM(total) AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g',
                'condition' => 'AND '.agents_get_status_clause(AGENT_STATUS_NORMAL, $this->filter['show_not_init_agents']),
            ],
            'unknown'  => [
                'header'    => '0 AS x_critical, 0 AS x_warning, 0 AS x_normal, SUM(total) AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g',
                'condition' => 'AND '.agents_get_status_clause(AGENT_STATUS_UNKNOWN, $this->filter['show_not_init_agents']),
            ],
            'not_init' => [
                'header'    => '0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, SUM(total) AS x_not_init, 0 AS x_alerts, 0 AS x_total, g',
                'condition' => 'AND '.agents_get_status_clause(AGENT_STATUS_NOT_INIT, $this->filter['show_not_init_agents']),
            ],
            'alerts'   => [
                'header'    => '0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, SUM(total) AS x_alerts, 0 AS x_total, g',
                'condition' => 'AND ta.fired_count > 0',
            ],
            'total'    => [
                'header'    => '0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, SUM(total) AS x_total, g',
                'condition' => 'AND '.agents_get_status_clause(AGENT_STATUS_ALL, $this->filter['show_not_init_agents']),
            ],
        ];
    }


    protected function getInnerOrLeftJoin()
    {
        return $this->filter['show_not_init_agents'] ? 'LEFT' : 'INNER';
    }


    protected function getModuleStatusFilter()
    {
        if ($this->filter['statusModule'] === 'fired') {
            $this->filter['statusModuleOriginal'] = $this->filter['statusModule'];
            $this->filter['statusModule'] = -1;
        }

        $filter_status = '';
        if ((int) $this->filter['statusModule'] !== -1 && ($this->type === 'module' || $this->type === 'module_group' || $this->type === 'tag')) {
            $filter_status = ' AND tae.estado = '.$this->filter['statusModule'];
        }

        $show_init_condition = ($this->filter['show_not_init_agents']) ? '' : ' AND ta.notinit_count <> ta.total_count';

        if ($this->getEmptyModuleFilterStatus()) {
            return $show_init_condition.$filter_status;
        }

        if ((int) $this->filter['statusModule'] === 6) {
            return ' AND (ta.warning_count > 0 OR ta.critical_count > 0)'.$filter_status;
        }

        if ($this->filter['statusModule'] === 'fired') {
            return ' AND ta.fired_count > 0'.$filter_status;
        }

        $field_filter = modules_get_counter_by_states($this->filter['statusModule']);
        if ($field_filter === false) {
            return ' AND 1=0';
        }

        return "AND ta.$field_filter > 0".$show_init_condition.$filter_status;
    }


    protected function getTagJoin()
    {
        return 'INNER JOIN tagente_modulo tam 
                    ON ta.id_agente = tam.id_agente
                INNER JOIN ttag_module ttm
			        ON tam.id_agente_modulo = ttm.id_agente_modulo';
    }


    protected function getTagCondition()
    {
        $tags = tags_get_user_applied_agent_tags($this->id, 'AR');
        // All tags permision, returns no condition
        if ($tags === true) {
            return '';
        }

        // No permision, do not show anything
        if ($tags === false) {
            return ' AND 1=0';
        }

        $tags_sql = implode(',', $tags);
        return "AND ttm.id_tag IN ($tags_sql)";
        ;
    }


    protected function getModuleStatusFilterFromTestado($state=false, $without_ands=false)
    {
        if ($this->filter['statusModule'] === 'fired') {
            $this->filter['statusModuleOriginal'] = $this->filter['statusModule'];
            $this->filter['statusModule'] = -1;
        }

        $selected_status = ($state !== false && $state !== self::TV_DEFAULT_AGENT_STATUS) ? $state : $this->filter['statusModule'];

        $filter = [modules_get_state_condition($selected_status)];
        if (!$this->filter['show_not_init_modules'] && $state === false) {
            if (!empty($filter)) {
                $filter[] = '(
				tae.estado <> '.AGENT_MODULE_STATUS_NO_DATA.'
				AND tae.estado <> '.AGENT_MODULE_STATUS_NOT_INIT.'
			)';
            }
        }

        $filter = implode(' AND ', $filter);
        return ($without_ands) ? $filter : " AND $filter ";
    }


    public function getGroupAclCondition()
    {
        if (users_can_manage_group_all('AR')) {
            return '';
        }

        $groups_str = implode(',', $this->userGroupsArray);
        return " AND (
			ta.id_grupo IN ($groups_str)
			OR tasg.id_group IN ($groups_str)
		)";
    }


    protected function getGroupSearchInner()
    {
        if (empty($this->filter['searchGroup'])) {
            return '';
        }

        return 'INNER JOIN tgrupo tg
			ON ta.id_grupo = tg.id_grupo
			OR tasg.id_group = tg.id_grupo';
    }


    protected function getGroupSearchFilter()
    {
        if (empty($this->filter['searchGroup'])) {
            return '';
        }

        return " AND tg.nombre LIKE '%%".str_replace('%', '%%', $this->filter['searchGroup'])."%%'";
    }


    static function cmpSortNames($a, $b)
    {
        return strcmp($a['name'], $b['name']);
    }


    protected function getProcessedItem($item, $server=false)
    {
        if (isset($processed_item['is_processed']) && $processed_item['is_processed']) {
            return $item;
        }

        $processed_item = [];
        $processed_item['id'] = $item['id'];
        $processed_item['name'] = $item['name'];
        $processed_item['rootID'] = $item['id'];
        $processed_item['rootType'] = $this->rootType;
        $processed_item['searchChildren'] = 1;

        if (isset($item['type']) === true) {
            $processed_item['type'] = $item['type'];
        } else {
            $processed_item['type'] = $this->type;
        }

        if (isset($item['rootType']) === true) {
            $processed_item['rootType'] = $item['rootType'];
        } else {
            $processed_item['rootType'] = $this->rootType;
        }

        if ($processed_item['type'] == 'group') {
            $processed_item['parent'] = $item['parent'];

            $processed_item['icon'] = empty($item['icon']) === true ? 'unknown@groups.svg' : $item['icon'];
        }

        if (isset($item['iconHTML']) === true) {
            $processed_item['icon'] = $item['iconHTML'];
        }

        if (is_metaconsole() === true && empty($server) === false) {
            $processed_item['serverID'] = $server['id'];
        }

        $counters = [];
        if (isset($item['total_unknown_count']) === true) {
            $counters['unknown'] = $item['total_unknown_count'];
        }

        if (isset($item['total_critical_count']) === true) {
            $counters['critical'] = $item['total_critical_count'];
        }

        if (isset($item['total_warning_count'])) {
            $counters['warning'] = $item['total_warning_count'];
        }

        if (isset($item['total_not_init_count'])) {
            $counters['not_init'] = $item['total_not_init_count'];
        }

        if (isset($item['total_normal_count'])) {
            $counters['ok'] = $item['total_normal_count'];
        }

        if (isset($item['total_count'])) {
            $counters['total'] = $item['total_count'];
        }

        if (isset($item['total_fired_count'])) {
            $counters['alerts'] = $item['total_fired_count'];
        }

        if (!empty($counters)) {
            $processed_item['counters'] = $counters;
        }

        if (!empty($processed_item)) {
            $processed_item['is_processed'] = true;
        }

        return $processed_item;
    }


    // This function should be used only when retrieving the data of the metaconsole's nodes
    protected function getMergedItems($items)
    {
        // This variable holds the result
        $mergedItems = [];

        foreach ($items as $key => $item) {
            // Avoid the deleted items
            if (!isset($items[$key]) || empty($item)) {
                continue;
            }

            // Store the item in a temporary element
            $resultItem = $item;

            // The 'id' parameter will be stored as 'server_id' => 'id'
            $resultItem['id'] = [];
            $resultItem['id'][$item['serverID']] = $item['id'];
            $resultItem['rootID'] = [];
            $resultItem['rootID'][$item['serverID']] = $item['rootID'];
            $resultItem['serverID'] = [];
            $resultItem['serverID'][$item['serverID']] = $item['rootID'];

            // Initialize counters if any of it don't exist
            if (!isset($resultItem['counters'])) {
                $resultItem['counters'] = [];
            }

            if (!isset($resultItem['counters']['unknown'])) {
                $resultItem['counters']['unknown'] = 0;
            }

            if (!isset($resultItem['counters']['critical'])) {
                $resultItem['counters']['critical'] = 0;
            }

            if (!isset($resultItem['counters']['warning'])) {
                $resultItem['counters']['warning'] = 0;
            }

            if (!isset($resultItem['counters']['not_init'])) {
                $resultItem['counters']['not_init'] = 0;
            }

            if (!isset($resultItem['counters']['ok'])) {
                $resultItem['counters']['ok'] = 0;
            }

            if (!isset($resultItem['counters']['total'])) {
                $resultItem['counters']['total'] = 0;
            }

            if (!isset($resultItem['counters']['alerts'])) {
                $resultItem['counters']['alerts'] = 0;
            }

            if ($item['type'] == 'group') {
                // Add the children
                if (!isset($resultItem['children'])) {
                    $resultItem['children'] = [];
                }
            }

            // Iterate over the list to search items that match the actual item
            foreach ($items as $key2 => $item2) {
                // Skip the actual or empty items
                if ($key == $key2 || !isset($items[$key2])) {
                    continue;
                }

                // Match with the name and type
                if ($item['name'] == $item2['name'] && $item['type'] == $item2['type']) {
                    // Add the matched ids
                    $resultItem['id'][$item2['serverID']] = $item2['id'];
                    $resultItem['rootID'][$item2['serverID']] = $item2['rootID'];
                    $resultItem['serverID'][$item2['serverID']] = $item2['rootID'];

                    // Add the matched counters
                    if (isset($item2['counters']) && !empty($item2['counters'])) {
                        foreach ($item2['counters'] as $type => $value) {
                            if (isset($resultItem['counters'][$type])) {
                                $resultItem['counters'][$type] += $value;
                            }
                        }
                    }

                    if ($item['type'] == 'group') {
                        // Add the matched children
                        if (isset($item2['children'])) {
                            $resultItem['children'] = array_merge($resultItem['children'], $item2['children']);
                        }
                    }

                    // Remove the item
                    unset($items[$key2]);
                }
            }

            if ($item['type'] == 'group') {
                // Get the merged children (recursion)
                if (!empty($resultItem['children'])) {
                    $resultItem['children'] = $this->getMergedItems($resultItem['children']);
                }
            }

            // Add the resulting item
            if (!empty($resultItem) && !empty($resultItem['counters']['total'])) {
                $mergedItems[] = $resultItem;
            }

            // Remove the item
            unset($items[$key]);
        }

        usort($mergedItems, ['Tree', 'cmpSortNames']);

        return $mergedItems;
    }


    protected function processModule(&$module, $server, $all_groups)
    {
        global $config;

        $server = ($server ?? false);

        if (isset($module['children'])) {
            foreach ($module['children'] as $i => $children) {
                $this->processModule($module['children'][$i], $server, $all_groups);
            }
        }

        $module['type'] = 'module';
        $module['id'] = (int) $module['id'];
        $module['name'] = $module['name'];
        $module['id_module_type'] = (int) $module['id_tipo_modulo'];
        $module['server_type'] = (int) $module['id_modulo'];
        $module['status'] = $module['estado'];

        if (is_metaconsole()) {
            $module['serverID'] = $this->serverID;
            $module['serverName'] = empty($this->serverName) === false ? $this->serverName : servers_get_name($this->serverID);
        } else {
            $module['serverName'] = false;
            $module['serverID'] = false;
        }

        $module['value'] = modules_get_agentmodule_data_for_humans($module);

        if (!isset($module['value'])) {
            $module['value'] = modules_get_last_value($module['id']);
        }

        // Status
        switch ($module['status']) {
            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                $module['alert'] = 1;
            case AGENT_MODULE_STATUS_CRITICAL_BAD:
                $statusType = STATUS_MODULE_CRITICAL_BALL;
                $statusTitle = __('CRITICAL');
                $module['statusText'] = 'critical';
            break;

            case AGENT_MODULE_STATUS_WARNING_ALERT:
                $module['alert'] = 1;
            case AGENT_MODULE_STATUS_WARNING:
                $statusType = STATUS_MODULE_WARNING_BALL;
                $statusTitle = __('WARNING');
                $module['statusText'] = 'warning';
            break;

            case AGENT_MODULE_STATUS_UNKNOWN:
                $statusType = STATUS_MODULE_UNKNOWN_BALL;
                $statusTitle = __('UNKNOWN');
                $module['statusText'] = 'unknown';
            break;

            case AGENT_MODULE_STATUS_NO_DATA:
            case AGENT_MODULE_STATUS_NOT_INIT:
                $statusType = STATUS_MODULE_NO_DATA_BALL;
                $statusTitle = __('NO DATA');
                $module['statusText'] = 'not_init';
            break;

            case AGENT_MODULE_STATUS_NORMAL_ALERT:
                $module['alert'] = 1;
            case AGENT_MODULE_STATUS_NORMAL:
            default:
                $statusType = STATUS_MODULE_OK_BALL;
                $statusTitle = __('NORMAL');
                $module['statusText'] = 'ok';
            break;
        }

        if ($statusType !== STATUS_MODULE_UNKNOWN_BALL
            && $statusType !== STATUS_MODULE_NO_DATA_BALL
        ) {
            if (is_numeric($module['value'])) {
                $divisor = get_data_multiplier($module['unit']);
                $statusTitle .= ' : '.format_for_graph($module['value'], 1, '.', ',', $divisor);
            } else {
                $statusTitle .= ' : '.substr(io_safe_output($module['value']), 0, 42);
            }
        }

        $module['statusImageHTML'] = ui_print_status_image($statusType, htmlspecialchars($statusTitle), true, ['is_tree_view' => true]);
        // HTML of the server type image.
        $module['serverTypeHTML'] = ui_print_servertype_icon((int) $module['server_type']);

        // Link to the Module graph.
        // ACL.
        $acl_graphs = false;
        $module['showGraphs'] = 0;

        // Avoid the check on the metaconsole.
        // Too slow to show/hide an icon depending on the permissions.
        if (empty($group_id) === false && is_metaconsole() === false) {
            $acl_graphs = check_acl_one_of_groups(
                $config['id_user'],
                $all_groups,
                'RR'
            );
        } else if (empty($all_groups) === false) {
            $acl_graphs = true;
        }

        if ($acl_graphs) {
            $module['showGraphs'] = 1;
        }

        if ($module['showGraphs']) {
            $tresholds = true;
            if (empty((float) $module['min_warning']) === true
                && empty((float) $module['max_warning']) === true
                && empty($module['warning_inverse']) === true
                && empty((float) $module['min_critical']) === true
                && empty((float) $module['max_critical']) === true
                && empty($module['critical_inverse']) === true
            ) {
                $tresholds = false;
            }

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

            $module['moduleGraph'] = [
                'url'    => $moduleGraphURL,
                'handle' => $winHandle,
            ];

            // Info to be able to open the snapshot image new page.
            $module['snapshot'] = ui_get_snapshot_link(
                [
                    'id_module'   => ($module['id'] ?? null),
                    'interval'    => ($module['current_interval'] ?? null),
                    'module_name' => ($module['name'] ?? null),
                    'id_node'     => ((isset($module['serverID']) === true) ? $module['serverID'] : 0),
                ],
                true
            );

            if ($tresholds === true || $graphType === 'boolean') {
                $graph_params['histogram'] = 1;
                $graph_params_str_th = http_build_query($graph_params);
                $moduleGraphURLTh = $url.'?'.$graph_params_str_th;
                $module['histogramGraph'] = [
                    'url'    => $moduleGraphURLTh,
                    'handle' => $winHandle,
                ];
            }
        }

        $module_alerts = alerts_get_alerts_agent_module($module['id']);
        $module_alert_triggered = false;

        if (is_array($module_alerts) === true) {
            foreach ($module_alerts as $module_alert) {
                if ($module_alert['times_fired'] > 0) {
                    $module_alert_triggered = true;
                }
            }
        }

        // Module has alerts.
        if ((bool) $module['alerts']) {
            // Module has alerts triggered.
            if ($module_alert_triggered === true) {
                $colorAlertButton = COL_ALERTFIRED;
            } else {
                $colorAlertButton = COL_NORMAL;
            }

            $module['alertsImageHTML'] = html_print_div(
                [
                    'title' => __('Module alerts'),
                    'class' => 'alert_background_state main_menu_icon module-button',
                    'style' => 'background-color: '.$colorAlertButton,
                ],
                true
            );
        }
    }


    protected function processModules(&$modules, $server=false)
    {
        if (!empty($modules)) {
            $all_groups = modules_get_agent_groups($modules[0]['id']);
        }

        foreach ($modules as $iterator => $module) {
            $this->processModule($modules[$iterator], $server, $all_groups);
        }
    }


    protected function processAgent(&$agent, $server=false)
    {
        if ($this->filter['statusModule'] === 'fired') {
            $this->filter['statusModuleOriginal'] = $this->filter['statusModule'];
            $this->filter['statusModule'] = -1;
        }

        global $config;

        $agent['type'] = 'agent';
        $agent['id'] = (int) $agent['id'];
        $agent['name'] = $agent['name'];

        $agent['rootID'] = $this->rootID;
        $agent['rootType'] = $this->rootType;

        if (is_metaconsole()) {
            if (isset($agent['server_id'])) {
                $agent['serverID'] = $agent['server_id'];
            } else if (!empty($server)) {
                $agent['serverID'] = $server['id'];
            }
        }

        // Counters.
        if (empty($agent['counters'])) {
            $agent['counters'] = [];

            $agent['counters']['unknown'] = isset($agent['unknown_count']) ? $agent['unknown_count'] : 0;
            $agent['counters']['critical'] = isset($agent['critical_count']) ? $agent['critical_count'] : 0;
            $agent['counters']['warning'] = isset($agent['warning_count']) ? $agent['warning_count'] : 0;
            $agent['counters']['not_init'] = isset($agent['notinit_count']) ? $agent['notinit_count'] : 0;
            $agent['counters']['ok'] = isset($agent['normal_count']) ? $agent['normal_count'] : 0;
            $agent['counters']['total'] = isset($agent['total_count']) ? $agent['total_count'] : 0;
            $agent['counters']['alerts'] = isset($agent['fired_count']) ? $agent['fired_count'] : 0;
        }

        // Status image.
        $agent['statusImageHTML'] = agents_tree_view_status_img_ball(
            $agent['counters']['critical'],
            $agent['counters']['warning'],
            $agent['counters']['unknown'],
            $agent['counters']['total'],
            $agent['counters']['not_init'],
            $agent['counters']['alerts']
        );

        $agent['agentStatus'] = -1;
        if ((bool) $this->filter['show_not_init_agents'] === true) {
            if ($agent['total_count'] === 0 || $agent['total_count'] === $agent['notinit_count']) {
                $agent['agentStatus'] = AGENT_STATUS_NOT_INIT;
            }
        }

        // Search module recalculate counters.
        if (array_key_exists('state_normal', $agent)) {
            $agent['counters']['unknown'] = $agent['state_unknown'];
            $agent['counters']['critical'] = $agent['state_critical'];
            $agent['counters']['warning'] = $agent['state_warning'];
            $agent['counters']['not_init'] = $agent['state_notinit'];
            $agent['counters']['ok'] = $agent['state_normal'];
            $agent['counters']['total'] = $agent['state_total'];

            $agent['critical_count'] = $agent['counters']['critical'];
            $agent['warning_count'] = $agent['counters']['warning'];
            $agent['unknown_count'] = $agent['counters']['unknown'];
            $agent['notinit_count'] = $agent['counters']['not_init'];
            $agent['normal_count'] = $agent['counters']['ok'];
            $agent['total_count'] = $agent['counters']['total'];
        }

        if (!$this->getEmptyModuleFilterStatus()) {
            $agent['counters']['unknown'] = 0;
            $agent['counters']['critical'] = 0;
            $agent['counters']['warning'] = 0;
            $agent['counters']['not_init'] = 0;
            $agent['counters']['ok'] = 0;
            $agent['counters']['total'] = 0;
            switch ($this->filter['statusModule']) {
                case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                case AGENT_MODULE_STATUS_CRITICAL_BAD:
                    $agent['counters']['critical'] = $agent['critical_count'];
                    $agent['counters']['total'] = $agent['critical_count'];
                break;

                case AGENT_MODULE_STATUS_WARNING_ALERT:
                case AGENT_MODULE_STATUS_WARNING:
                    $agent['counters']['warning'] = $agent['warning_count'];
                    $agent['counters']['total'] = $agent['warning_count'];
                break;

                case AGENT_MODULE_STATUS_UNKNOWN:
                    $agent['counters']['unknown'] = $agent['unknown_count'];
                    $agent['counters']['total'] = $agent['unknown_count'];
                break;

                case AGENT_MODULE_STATUS_NO_DATA:
                case AGENT_MODULE_STATUS_NOT_INIT:
                    $agent['counters']['not_init'] = $agent['notinit_count'];
                    $agent['counters']['total'] = $agent['notinit_count'];
                break;

                case AGENT_MODULE_STATUS_NORMAL_ALERT:
                case AGENT_MODULE_STATUS_NORMAL:
                    $agent['counters']['ok'] = $agent['normal_count'];
                    $agent['counters']['total'] = $agent['normal_count'];
                break;

                case AGENT_MODULE_STATUS_NOT_NORMAL:
                    if (empty($agent['critical_count']) === false) {
                        $agent['counters']['critical'] = $agent['critical_count'];
                    }

                    if (empty($agent['warning_count']) === false) {
                        $agent['counters']['warning'] = $agent['warning_count'];
                    }

                    $agent['counters']['total'] = ($agent['warning_count'] + $agent['critical_count']);
                break;
            }
        }

        if (!$this->filter['show_not_init_modules']) {
            $agent['counters']['total'] -= $agent['counters']['not_init'];
            $agent['counters']['not_init'] = 0;
        }

        // Children
        if (empty($agent['children'])) {
            $agent['children'] = [];
            if ($agent['counters']['total'] > 0) {
                switch ($this->childrenMethod) {
                    case 'on_demand':
                        $agent['searchChildren'] = 1;
                    break;

                    case 'live':
                        $agent['searchChildren'] = 0;
                    break;
                }
            } else {
                switch ($this->childrenMethod) {
                    case 'on_demand':
                        $agent['searchChildren'] = 0;
                    break;

                    case 'live':
                        $agent['searchChildren'] = 0;
                    break;
                }
            }
        }

        // Quiet name on agent.
        if (isset($agent['quiet']) && $agent['quiet']) {
            $agent['alias'] .= ' '.__('(Quiet)');
        }
    }


    protected function processAgents(&$agents, $server=false)
    {
        if (!empty($agents)) {
            $agents_aux = [];
            foreach ($agents as $iterator => $agent) {
                $this->processAgent($agents[$iterator], $server);
                if ($agents[$iterator]['counters']['total'] !== '0'
                    || ((bool) $this->filter['show_not_init_agents'] === true
                    && $agents[$iterator]['agentStatus'] === AGENT_STATUS_NOT_INIT)
                ) {
                    $agents_aux[] = $agents[$iterator];
                }
            }

            $agents = $agents_aux;
        }
    }


    protected function getData()
    {

    }


    protected function getFirstLevel()
    {
        $sql = $this->getFirstLevelSql();
        $items = db_get_all_rows_sql($sql);
        if ($items === false) {
            $items = [];
        }

        $this->tree = $this->getProcessedItemsFirstLevel($items);
    }


    protected function getProcessedItemsFirstLevel($items)
    {
        $processed_items = [];
        foreach ($items as $key => $item) {
            $processed_item = $this->getProcessedItem($item);
            $processed_items[] = $processed_item;
        }

        return $processed_items;
    }


    protected function getFirstLevelSql()
    {
        $fields = $this->getFirstLevelFields();
        $field_name_sql = $this->L1fieldNameSql;
        $inside_fields = $this->getFirstLevelFieldsInside();
        $inner = $this->L1inner;
        $inner_inside = $this->L1innerInside;
        $order_by_final = $this->L1orderByFinal;

        $group_inner = $this->getGroupSearchInner();
        $group_acl = $this->getGroupAclCondition();
        $group_search_filter = $this->getGroupSearchFilter();
        $agent_search_filter = $this->getAgentSearchFilter();
        $agent_status_filter = $this->getAgentStatusFilter();
        $module_search_filter = $this->getModuleSearchFilter();
        $module_status_filter = $this->getModuleStatusFilter();
        $module_status_inner = '';
        $module_search_inner = '';
        $module_search_filter = '';

        if (!empty($this->filter['searchModule'])) {
            $module_search_inner = '';
            $module_search_filter = "AND tam.disabled = 0
                AND tam.nombre LIKE '%%".$this->filter['searchModule']."%%' ".$this->getModuleStatusFilterFromTestado();
        }

        $sql_model = "SELECT %s FROM
			(
				SELECT COUNT(DISTINCT(ta.id_agente)) AS total, $field_name_sql AS g
					FROM tagente ta
					LEFT JOIN tagent_secondary_group tasg
						ON ta.id_agente = tasg.id_agent
					$inner_inside
					$module_status_inner
					$group_inner
                    $module_search_inner
					WHERE ta.disabled = 0
						%s
						$agent_search_filter
						$agent_status_filter
						$module_search_filter
						$module_status_filter
						$group_search_filter
						$group_acl
					GROUP BY $field_name_sql
			) x GROUP BY g";
        $sql_array = [];
        foreach ($inside_fields as $inside_field) {
            $sql_array[] = sprintf(
                $sql_model,
                $inside_field['header'],
                $inside_field['condition']
            );
        }

        $sql = "SELECT $fields FROM (".implode(' UNION ALL ', $sql_array).") x2
			$inner
			GROUP BY g
			ORDER BY $order_by_final";
        return $sql;
    }


    protected function getSecondLevel()
    {
        $sql = $this->getSecondLevelSql();
        $data = db_process_sql($sql);
        if (empty($data)) {
            $this->tree = [];
            return;
        }

        $this->processAgents($data);
        $this->tree = $data;
    }


    protected function getSecondLevelSql()
    {
        $columns = sprintf(
            'ta.id_agente AS id, ta.nombre AS name, ta.alias,
				ta.fired_count, ta.normal_count, ta.warning_count,
				ta.critical_count, ta.unknown_count, ta.notinit_count,
				ta.total_count, ta.quiet,
				SUM(if(%s, 1, 0)) as state_critical,
				SUM(if(%s, 1, 0)) as state_warning,
				SUM(if(%s, 1, 0)) as state_unknown,
				SUM(if(%s, 1, 0)) as state_notinit,
				SUM(if(%s, 1, 0)) as state_normal,
				SUM(if(%s AND tae.estado IS NOT NULL, 1, 0)) as state_total
			',
            $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_CRITICAL_ALERT, true),
            $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_WARNING_ALERT, true),
            $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_UNKNOWN, true),
            $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_NO_DATA, true),
            $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_NORMAL, true),
            $this->getModuleStatusFilterFromTestado(self::TV_DEFAULT_AGENT_STATUS, true)
        );

        $inner_or_left = $this->getInnerOrLeftJoin();
        $group_inner = $this->getGroupSearchInner();
        $group_acl = $this->getGroupAclCondition();
        $group_search_filter = $this->getGroupSearchFilter();
        $agent_search_filter = $this->getAgentSearchFilter();
        $agent_status_filter = $this->getAgentStatusFilter();
        $module_search_filter = $this->getModuleSearchFilter();
        $module_status_filter = $this->getModuleStatusFilter();

        $condition = $this->L2condition;
        $condition_inside = $this->L2conditionInside;
        $inner = $this->L2inner;

        $sql = "SELECT $columns
			FROM tagente ta
			$inner_or_left JOIN tagente_modulo tam
				ON ta.id_agente = tam.id_agente
				AND tam.disabled = 0
			$inner_or_left JOIN tagente_estado tae
				ON tae.id_agente_modulo = tam.id_agente_modulo
			$inner
			WHERE ta.id_agente IN
				(
					SELECT ta.id_agente
					FROM tagente ta
					LEFT JOIN tagent_secondary_group tasg
						ON tasg.id_agent = ta.id_agente
					$group_inner
					WHERE ta.disabled = 0
						$group_acl
						$group_search_filter
						$condition_inside
				)
				AND ta.disabled = 0
				$condition
				$agent_search_filter
				$agent_status_filter
				$module_search_filter
				$module_status_filter
			GROUP BY ta.id_agente
			ORDER BY ta.alias ASC, ta.id_agente ASC
		";

        return $sql;
    }


    protected function getThirdLevel()
    {
        $sql = $this->getThirdLevelSql();
        $data = db_process_sql($sql);
        if (empty($data)) {
            $this->tree = [];
            return;
        }

        $data = $this->getProcessedModules($data);
        $this->processModules($data);
        $this->tree = $data;
    }


    protected function getThirdLevelSql()
    {
        // Get the server id.
        $serverID = $this->serverID;

        $group_acl = $this->getGroupAclCondition();
        $agent_search_filter = $this->getAgentSearchFilter();
        $agent_status_filter = $this->getAgentStatusFilter();
        $module_search_filter = $this->getModuleSearchFilter();
        $module_status_filter = $this->getModuleStatusFilterFromTestado();
        $agent_filter = 'AND ta.id_agente = '.$this->id;
        $tag_condition = $this->getTagCondition();
        $tag_join = empty($tag_condition) && (!$this->L3forceTagCondition) ? '' : $this->getTagJoin();
        $show_disabled = $this->getDisabledFilter();

        if ($this->avoid_condition === true) {
            $condition = '';
            $inner = '';
        } else {
            $condition = $this->L2condition;
            $inner = $this->L2inner;
        }

        $columns = 'DISTINCT(tam.id_agente_modulo) AS id, tam.nombre AS name,
			tam.id_tipo_modulo, tam.id_modulo, tae.estado, tae.datos,
			tam.parent_module_id AS parent, tatm.id AS alerts, tam.unit';

        if ($show_disabled) {
            $columns .= ', tam.disabled';
        }

        $sql = "SELECT $columns
			FROM tagente_modulo tam
			$tag_join
			INNER JOIN tagente_estado tae
				ON tam.id_agente_modulo = tae.id_agente_modulo
			INNER JOIN tagente ta
				ON tam.id_agente = ta.id_agente
			LEFT JOIN tagent_secondary_group tasg
				ON ta.id_agente = tasg.id_agent
			LEFT JOIN talert_template_modules tatm
				ON tatm.id_agent_module = tam.id_agente_modulo
			$inner
            WHERE
			$show_disabled
				$condition
				$agent_filter
				$group_acl
				$agent_search_filter
				$agent_status_filter
				$module_search_filter
                $module_status_filter
				$tag_condition
            GROUP BY tam.id_agente_modulo
            ORDER BY tam.nombre ASC, tam.id_agente_modulo ASC";

        return $sql;
    }


    public function getJSON()
    {
        $this->getData();

        return json_encode($this->tree);
    }


    public function getArray()
    {
        $this->getData();

        return $this->tree;
    }


    static function name2symbol($name)
    {
        return str_replace(
            [
                ' ',
                '#',
                '/',
                '.',
                '(',
                ')',
                '¿',
                '?',
                '¡',
                '!',
            ],
            [
                '_articapandora_'.ord(' ').'_pandoraartica_',
                '_articapandora_'.ord('#').'_pandoraartica_',
                '_articapandora_'.ord('/').'_pandoraartica_',
                '_articapandora_'.ord('.').'_pandoraartica_',
                '_articapandora_'.ord('(').'_pandoraartica_',
                '_articapandora_'.ord(')').'_pandoraartica_',
                '_articapandora_'.ord('¿').'_pandoraartica_',
                '_articapandora_'.ord('?').'_pandoraartica_',
                '_articapandora_'.ord('¡').'_pandoraartica_',
                '_articapandora_'.ord('!').'_pandoraartica_',
            ],
            io_safe_output($name)
        );
    }


    static function symbol2name($name)
    {
        $symbols = ' !"#$%&\'()*+,./:;<=>?@[\\]^{|}~';
        for ($i = 0; $i < strlen($symbols); $i++) {
            $name = str_replace(
                '_articapandora_'.ord(substr($symbols, $i, 1)).'_pandoraartica_',
                substr($symbols, $i, 1),
                $name
            );
        }

        return io_safe_input($name);
    }


    protected function getProcessedModules($modules_tree)
    {
        return $modules_tree;
    }


}
