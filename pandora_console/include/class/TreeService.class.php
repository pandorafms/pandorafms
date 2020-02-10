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

class TreeService extends Tree
{

    protected $propagateCounters = true;

    protected $displayAllGroups = false;


    public function __construct($type, $rootType='', $id=-1, $rootID=-1, $serverID=false, $childrenMethod='on_demand', $access='AR')
    {
        global $config;

        parent::__construct($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);

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
        if ($this->id == -1) {
            $this->getFirstLevel();
        } else if ($this->type == 'services') {
            $this->getSecondLevel();
        } else if ($this->type == 'agent') {
            $this->getThirdLevel();
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
        $fields = $this->getFirstLevelFields();

        if (users_can_manage_group_all('AR')) {
            $groups_acl = '';
        } else {
            $groups_acl = 'AND ts.id_group IN ('.implode(',', $this->userGroupsArray).')';
        }

        $sql = sprintf(
            "SELECT t1.* 
						FROM tservice_element tss
						RIGHT JOIN
						(SELECT ts.id, ts.id_agent_module, ts.name, ts.name AS `alias`, ts.id AS `rootID`,
						'services' AS rootType, 'services' AS type,
						0 AS quiet,
						SUM(if((tse.id_agent<>0), 1, 0)) AS `total_agents`,
						SUM(if((tse.id_agente_modulo<>0), 1, 0)) AS `total_modules`,
						SUM(if((tse.id_service_child<>0), 1, 0)) AS `total_services`
					FROM tservice ts
					LEFT JOIN tservice_element tse
						ON ts.id=tse.id_service
                    WHERE
                        1=1
                        %s
					    GROUP BY id
					) as t1  
					ON tss.id_service_child = t1.id
					WHERE tss.id_service_child IS NULL
					",
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
            $service_stats[$service['id']]['rootType'] = $service['rootType'];
            $service_stats[$service['id']]['type'] = 'services';
            $service_stats[$service['id']]['children'] = [];
            $service_stats[$service['id']]['serviceDetail'] = 'index.php?sec=network&sec2=enterprise/operation/services/services&tab=service_map&id_service='.(int) $service['id'];
            $service_stats[$service['id']]['counters'] = [
                'total_services' => $service['total_services'],
                'total_agents'   => $service['total_agents'],
                'total_modules'  => $service['total_modules'],
            ];
        }

        $own_info = get_user_info($config['id_user']);

        if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
            $display_all_services = true;
        } else {
            $display_all_services = false;
        }

        $services = services_get_services($filter, false, $display_all_services);

        foreach ($services as $row) {
            if (!array_key_exists($row['id'], $service_stats)) {
                continue;
            }

            $status = services_get_status($row, true);

            switch ($status) {
                case SERVICE_STATUS_NORMAL:
                    $service_stats[$row['id']]['statusImageHTML'] = '<img src="images/status_sets/default/agent_ok_ball.png" data-title="NORMAL status." data-use_title_for_force_title="1" class="forced_title" alt="NORMAL status." />';
                break;

                case SERVICE_STATUS_CRITICAL:
                    $service_stats[$row['id']]['statusImageHTML'] = '<img src="images/status_sets/default/agent_critical_ball.png" data-title="CRITICAL status." data-use_title_for_force_title="1" class="forced_title" alt="CRITICAL status." />';
                break;

                case SERVICE_STATUS_WARNING:
                    $service_stats[$row['id']][$key]['statusImageHTML'] = '<img src="images/status_sets/default/agent_warning_ball.png" data-title="WARNING status." data-use_title_for_force_title="1" class="forced_title" alt="WARNING status." />';
                break;

                case SERVICE_STATUS_UNKNOWN:
                default:
                    $service_stats[$row['id']]['statusImageHTML'] = '<img src="images/status_sets/default/agent_no_data_ball.png" data-title="UNKNOWN status." data-use_title_for_force_title="1" class="forced_title" alt="UNKNOWN status." />';
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

        $sql = "SELECT ts.id, ts.name, tse1.id_service AS `rootID`, 'services' AS rootType, 'services' AS type, 0 AS quiet, SUM(if((tse2.id_agent<>0), 1, 0)) AS `total_agents`, SUM(if((tse2.id_agente_modulo<>0), 1, 0)) AS `total_modules`, SUM(if((tse2.id_service_child<>0), 1, 0)) AS `total_services`, 0 AS fired_count, 0 AS normal_count, 0 AS warning_count, 0 AS critical_count, 0 AS unknown_count, 0 AS notinit_count, 0 AS state_critical, 0 AS state_warning, 0 AS state_unknown, 0 AS state_notinit, 0 AS state_normal, 0 AS state_total, '' AS statusImageHTML, '' AS alertImageHTML
		FROM tservice_element tse1
		LEFT JOIN tservice_element tse2 ON tse1.id_service_child=tse2.id_service
		LEFT JOIN tservice ts ON tse1.id_service_child=ts.id
		WHERE tse1.id_service=$this->id AND tse1.id_service_child<>0
		GROUP BY tse1.id_service_child
		";

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


}
