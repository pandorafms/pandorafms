<?php
// Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2018 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/class/Tree.class.php';

use PandoraFMS\Enterprise\Service;

class TreeService extends Tree
{

    protected $propagateCounters = true;

    protected $displayAllGroups = false;

    private $metaID = 0;


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

        $this->L2condition = 'AND tse.id_service='.$this->id;

    }


    public function setPropagateCounters($value)
    {
        $this->propagateCounters = (bool) $value;
    }


    public function setDisplayAllGroups($value)
    {
        $this->displayAllGroups = (bool) $value;
    }


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
        }

        if ($this->id == -1) {
            $this->getFirstLevel();
        } else if ($this->type == 'services') {
            $this->getSecondLevel();
        } else if ($this->type == 'agent') {
            $this->getThirdLevel();
        }

        if (is_metaconsole() === true && $this->metaID > 0) {
            // Restore connection.
            \enterprise_hook('metaconsole_restore_db');
        }
    }


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
                    $processed_items[$row['id']]['statusImageHTML'] = '<img src="images/status_sets/default/agent_ok_ball.png" data-title="NORMAL status." data-use_title_for_force_title="1" class="forced_title" alt="NORMAL status." />';
                break;

                case SERVICE_STATUS_CRITICAL:
                    $processed_items[$row['id']]['statusImageHTML'] = '<img src="images/status_sets/default/agent_critical_ball.png" data-title="CRITICAL status." data-use_title_for_force_title="1" class="forced_title" alt="CRITICAL status." />';
                break;

                case SERVICE_STATUS_WARNING:
                    $processed_items[$row['id']]['statusImageHTML'] = '<img src="images/status_sets/default/agent_warning_ball.png" data-title="WARNING status." data-use_title_for_force_title="1" class="forced_title" alt="WARNING status." />';
                break;

                case SERVICE_STATUS_UNKNOWN:
                default:
                    $processed_items[$row['id']]['statusImageHTML'] = '<img src="images/status_sets/default/agent_no_data_ball.png" data-title="UNKNOWN status." data-use_title_for_force_title="1" class="forced_title" alt="UNKNOWN status." />';
                break;
            }
        }

        $this->tree = $processed_items;
    }


    protected function getProcessedServices()
    {
        $is_favourite = $this->getServiceFavouriteFilter();

        if (users_can_manage_group_all('AR')) {
            $groups_acl = '';
        } else {
            $groups_acl = 'AND ts.id_group IN ('.implode(',', $this->userGroupsArray).')';
        }

        $sql = sprintf(
            'SELECT 
                ts.id,
                ts.id_agent_module,
                ts.name,
                ts.name as `alias`,
                ts.id as `rootID`,
                "services" as `rootType`,
                "services" as `type`,
                ts.quiet,
                SUM(if((tse.id_agent<>0), 1, 0)) AS `total_agents`,
                SUM(if((tse.id_agente_modulo<>0), 1, 0)) AS `total_modules`,
                SUM(if((tse.id_service_child<>0), 1, 0)) AS `total_services`
            FROM tservice ts
            LEFT JOIN tservice_element tse
                ON tse.id_service = ts.id
            WHERE ts.id NOT IN (
                    SELECT DISTINCT id_service_child
                    FROM tservice_element
                    WHERE id_server_meta = 0
                )
                %s
                %s
            GROUP BY ts.id',
            $is_favourite,
            $groups_acl
        );

        $stats = db_get_all_rows_sql($sql);

        $services = [];

        foreach ($stats as $service) {
            $services[$service['id']] = $this->getProcessedItem($services[$service['id']]);
            if (($service['total_services'] + $service['total_agents'] + $service['total_modules']) > 0) {
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
            $services[$service['id']]['serviceDetail'] = 'index.php?sec=network&sec2=enterprise/operation/services/services&tab=service_map&id_service='.(int) $service['id'];
        }

        return $services;
    }


    protected function getFirstLevelFields()
    {
        $fields = [];

        return implode(',', array_merge($fields, $this->L1extraFields));
    }


    protected function getSecondLevel()
    {
        $data = [];
        $data_agents = [];
        $data_modules = [];
        $data_services = [];

        $sql = $this->getSecondLevelSql();
        $data_agents = db_process_sql($sql);

        if (empty($data_agents)) {
            $data_agents = [];
        }

        $this->processAgents($data_agents);

        foreach ($data_agents as $key => $agent) {
                        $data_agents[$key]['showEventsBtn'] = 1;
            $data_agents[$key]['eventAgent'] = $agent['id'];
        }

        $sql = $this->getSecondLevelModulesSql();
        $data_modules = db_process_sql($sql);

        if (empty($data_modules)) {
            $data_modules = [];
        } else {
            foreach ($data_modules as $key => $module) {
                switch ($module['estado']) {
                    case '0':
                        $module_status = 'ok';
                        $module_title = 'NORMAL';
                    break;

                    case '1':
                        $module_status = 'critical';
                        $module_title = 'CRITICAL';
                    break;

                    case '2':
                        $module_status = 'warning';
                        $module_title = 'WARNING';
                    break;

                    case '3':
                        $module_status = 'down';
                        $module_title = 'UNKNOWN';
                    break;

                    case '4':
                        $module_status = 'no_data';
                        $module_title = 'NOT INITIALIZED';
                    break;

                    default:
                        $module_status = 'down';
                        $module_title = 'UNKNOWN';
                    break;
                }

                $data_modules[$key]['statusImageHTML'] = '<img src="images/status_sets/default/agent_'.$module_status.'_ball.png" data-title="'.$module_title.' status." data-use_title_for_force_title="1" class="forced_title" alt="'.$module_title.' status." />';
                $data_modules[$key]['showEventsBtn'] = 1;
                $data_modules[$key]['eventModule'] = $module['id_agente_modulo'];
            }
        }

        $sql = $this->getSecondLevelServicesSql();
        $data_services = db_process_sql($sql);

        $data_services = array_reduce(
            $data_services,
            function ($carry, $item) {
                if ($item['id_server_meta'] > 0
                    && is_metaconsole() === true
                ) {
                    // Impersonate node.
                    \enterprise_include_once('include/functions_metaconsole.php');
                    $r = \enterprise_hook(
                        'metaconsole_connect',
                        [
                            null,
                            $item['id_server_meta'],
                        ]
                    );

                    if ($r === NOERR) {
                        $item = db_get_row_sql(
                            sprintf(
                                'SELECT 
                                    ts.id,
                                    ts.id_agent_module,
                                    ts.name,
                                    ts.name as `alias`,
                                    %d as `rootID`,
                                    "services" as `rootType`,
                                    "services" as `type`,
                                    ts.quiet,
                                    %d as id_server_meta,
                                    SUM(if((tse.id_agent<>0), 1, 0)) AS `total_agents`,
                                    SUM(if((tse.id_agente_modulo<>0), 1, 0)) AS `total_modules`,
                                    SUM(if((tse.id_service_child<>0), 1, 0)) AS `total_services`
                                FROM tservice ts
                                LEFT JOIN tservice_element tse
                                    ON tse.id_service = ts.id
                                WHERE ts.id = %d
                                GROUP BY ts.id',
                                $item['id_server_meta'],
                                $item['rootID'],
                                $item['id']
                            )
                        );
                        $item['obj'] = new Service($item['id']);
                    }

                    // Restore connection.
                    \enterprise_hook('metaconsole_restore_db');
                } else {
                    $item['obj'] = new Service($item['id']);
                }

                $carry[] = $item;
                return $carry;
            },
            []
        );

        $service_stats = [];

        foreach ($data_services as $service) {
            $service_stats[$service['id']]['id'] = (int) $service['id'];
            $service_stats[$service['id']]['name'] = $service['name'];
            $service_stats[$service['id']]['alias'] = $service['name'];
            if (($service['total_services'] + $service['total_agents'] + $service['total_modules']) > 0) {
                $service_stats[$service['id']]['searchChildren'] = 1;
            } else {
                $services[$service['id']]['searchChildren'] = 0;
            }

            $service_stats[$service['id']]['rootID'] = $service['rootID'];
            if ($this->metaID > 0) {
                $service_stats[$service['id']]['metaID'] = $this->metaID;
            } else {
                $service_stats[$service['id']]['metaID'] = $service['id_server_meta'];
            }

            $service_stats[$service['id']]['rootType'] = $service['rootType'];
            $service_stats[$service['id']]['type'] = 'services';
            $service_stats[$service['id']]['children'] = [];
            $service_stats[$service['id']]['serviceDetail'] = 'index.php?sec=network&sec2=enterprise/operation/services/services&tab=service_map&id_service='.(int) $service['id'];
            $service_stats[$service['id']]['counters'] = [
                'total_services' => $service['total_services'],
                'total_agents'   => $service['total_agents'],
                'total_modules'  => $service['total_modules'],
            ];

            switch ($service['obj']->status()) {
                case SERVICE_STATUS_NORMAL:
                    $service_stats[$service['id']]['statusImageHTML'] = '<img src="images/status_sets/default/agent_ok_ball.png" data-title="NORMAL status." data-use_title_for_force_title="1" class="forced_title" alt="NORMAL status." />';
                break;

                case SERVICE_STATUS_CRITICAL:
                    $service_stats[$service['id']]['statusImageHTML'] = '<img src="images/status_sets/default/agent_critical_ball.png" data-title="CRITICAL status." data-use_title_for_force_title="1" class="forced_title" alt="CRITICAL status." />';
                break;

                case SERVICE_STATUS_WARNING:
                    $service_stats[$service['id']]['statusImageHTML'] = '<img src="images/status_sets/default/agent_warning_ball.png" data-title="WARNING status." data-use_title_for_force_title="1" class="forced_title" alt="WARNING status." />';
                break;

                case SERVICE_STATUS_UNKNOWN:
                default:
                    $service_stats[$service['id']]['statusImageHTML'] = '<img src="images/status_sets/default/agent_no_data_ball.png" data-title="UNKNOWN status." data-use_title_for_force_title="1" class="forced_title" alt="UNKNOWN status." />';
                break;
            }
        }

        $data_services = array_values($service_stats);

        $data = array_merge($data_services, $data_agents, $data_modules);

        if (empty($data)) {
            $this->tree = [];
            return;
        }

        $this->tree = $data;
    }


    protected function getSecondLevelServicesSql()
    {
        $group_acl = $this->getGroupAclCondition();

        $sql = sprintf(
            'SELECT 
                ts.id,
                ts.id_agent_module,
                ts.name,
                ts.name as `alias`,
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


    protected function getSecondLevelModulesSql()
    {
        $sql = "SELECT tse.id_agente_modulo, nombre AS `name`, nombre AS `alias`, tse.id_service AS `rootID`, 'services' AS `rootType`, 'modules' AS `type`, estado
				FROM tservice_element tse
				INNER JOIN tagente_modulo tam ON tse.id_agente_modulo=tam.id_agente_modulo
				INNER JOIN tagente_estado tae ON tam.id_agente_modulo=tae.id_agente_estado
				WHERE tse.id_service=$this->id AND tse.id_agente_modulo<>0
		";

        return $sql;
    }


    protected function getAgentStatusFilter($status=self::TV_DEFAULT_AGENT_STATUS)
    {
        return '';
    }


    protected function getServiceFavouriteFilter()
    {
        if (isset($this->filter['is_favourite']) && !empty($this->filter['is_favourite'])) {
            return ' AND is_favourite = 1';
        }

        return '';
    }


}
