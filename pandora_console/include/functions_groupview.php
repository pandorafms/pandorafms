<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_tags.php';
require_once $config['homedir'].'/include/class/Tree.class.php';
require_once $config['homedir'].'/include/class/TreeGroup.class.php';


function groupview_plain_groups($groups)
{
    $group_result = [];
    foreach ($groups as $group) {
        $plain_child = [];
        if (!empty($group['children'])) {
            $plain_child = groupview_plain_groups($group['children']);
            unset($group['children']);
        }

        $group_result[] = $group;
        $group_result = array_merge($group_result, $plain_child);
    }

    return $group_result;
}


function groupview_get_modules_counters($groups_ids=false)
{
    if (empty($groups_ids)) {
        return [];
    }

    $groups_ids = implode(',', $groups_ids);
    $table = is_metaconsole() ? 'tmetaconsole_agent' : 'tagente';
    $table_sec = is_metaconsole() ? 'tmetaconsole_agent_secondary_group' : 'tagent_secondary_group';

    $fields = [
        'g',
        'SUM(module_normal) AS total_module_normal',
        'SUM(module_critical) AS total_module_critical',
        'SUM(module_warning) AS total_module_warning',
        'SUM(module_unknown) AS total_module_unknown',
        'SUM(module_not_init) AS total_module_not_init',
        'SUM(module_alerts) AS total_module_alerts',
        'SUM(module_total) AS total_module',
    ];

    $fields_impl = implode(',', $fields);
    $sql = "SELECT $fields_impl FROM
	(
		SELECT SUM(ta.normal_count) AS module_normal,
			SUM(ta.critical_count) AS module_critical,
			SUM(ta.warning_count) AS module_warning,
			SUM(ta.unknown_count) AS module_unknown,
			SUM(ta.notinit_count) AS module_not_init,
			SUM(ta.fired_count) AS module_alerts,
			SUM(ta.total_count) AS module_total,
			ta.id_grupo AS g
		FROM $table ta
		WHERE ta.id_grupo IN ($groups_ids)
		AND ta.disabled = 0
		GROUP BY ta.id_grupo
		UNION ALL
		SELECT SUM(ta.normal_count) AS module_normal,
			SUM(ta.critical_count) AS module_critical,
			SUM(ta.warning_count) AS module_warning,
			SUM(ta.unknown_count) AS module_unknown,
			SUM(ta.notinit_count) AS module_not_init,
			SUM(ta.fired_count) AS module_alerts,
			SUM(ta.total_count) AS module_total,
			tasg.id_group AS g
		FROM $table ta
		INNER JOIN $table_sec tasg
			ON ta.id_agente = tasg.id_agent
		WHERE tasg.id_group IN ($groups_ids)
		GROUP BY tasg.id_group
	) x GROUP BY g";
    $data = db_get_all_rows_sql($sql);
    return $data;
}


function groupview_get_all_counters($tree_group)
{
    $all_name = __('All');
    $group_acl = $tree_group->getGroupAclCondition();
    $table = is_metaconsole() ? 'tmetaconsole_agent' : 'tagente';
    $table_sec = is_metaconsole() ? 'tmetaconsole_agent_secondary_group' : 'tagent_secondary_group';
    $sql = "SELECT SUM(ta.critical_count) AS _monitors_critical_,
			SUM(ta.warning_count) AS _monitors_warning_,
			SUM(ta.unknown_count) AS _monitors_unknown_,
			SUM(ta.notinit_count) AS _monitors_not_init_,
			SUM(ta.normal_count) AS _monitors_ok_,
			SUM(ta.total_count) AS _monitor_checks_,
			SUM(ta.fired_count) AS _monitors_alerts_fired_,
			SUM(IF(ta.critical_count > 0, 1, 0)) AS _agents_critical_,
			SUM(IF(ta.critical_count = 0 AND ta.warning_count > 0, 1, 0)) AS _agents_warning_,
			SUM(IF(ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count > 0, 1, 0)) AS _agents_unknown_,
			SUM(IF(ta.total_count = ta.notinit_count, 1, 0)) AS _agents_not_init_,
			SUM(IF(ta.total_count = ta.normal_count AND ta.total_count <> ta.notinit_count, 1, 0)) AS _agents_ok_,
			COUNT(ta.id_agente) AS _total_agents_,
			'$all_name' AS _name_,
			0 AS _id_,
			'' AS _icon_
		FROM $table ta
		WHERE ta.disabled = 0
			AND ta.id_agente IN (
				SELECT ta.id_agente FROM $table ta
				LEFT JOIN $table_sec tasg
					ON ta.id_agente = tasg.id_agent
				WHERE ta.disabled = 0 
					$group_acl
				GROUP BY ta.id_agente
			)
	";
    $data = db_get_row_sql($sql);
    $data['_monitor_not_normal_'] = ($data['_monitor_checks_'] - $data['_monitors_ok_']);
    return $data;
}


function groupview_get_groups_list($id_user=false, $access='AR', $is_not_paginated=false)
{
    global $config;
    if ($id_user == false) {
        $id_user = $config['id_user'];
    }

    $tree_group = new TreeGroup('group', 'group');
    $tree_group->setPropagateCounters(false);
    $tree_group->setFilter(
        [
            'searchAgent'           => '',
            'statusAgent'           => AGENT_STATUS_ALL,
            'searchModule'          => '',
            'statusModule'          => -1,
            'groupID'               => 0,
            'tagID'                 => 0,
            'show_not_init_agents'  => 1,
            'show_not_init_modules' => 1,
        ]
    );
    $info = $tree_group->getArray();
    $info = groupview_plain_groups($info);
    $counter = count($info);

    $offset = get_parameter('offset', 0);
    $groups_view = $is_not_paginated ? $info : array_slice($info, $offset, $config['block_size']);
    $agents_counters = array_reduce(
        $groups_view,
        function ($carry, $item) {
            $carry[$item['id']] = $item;
            return $carry;
        },
        []
    );

    $modules_counters = groupview_get_modules_counters(array_keys($agents_counters));
    $modules_counters = array_reduce(
        $modules_counters,
        function ($carry, $item) {
            $carry[$item['g']] = $item;
            return $carry;
        },
        []
    );

    $list = [];

    foreach ($agents_counters as $id_group => $agent_counter) {
        $list[$id_group]['_name_'] = $agent_counter['name'];
        $list[$id_group]['_id_'] = $agent_counter['id'];
        $list[$id_group]['_iconImg_'] = $agent_counter['icon'];

        $list[$id_group]['_agents_critical_'] = $agent_counter['counters']['critical'];
        $list[$id_group]['_agents_warning_'] = $agent_counter['counters']['warning'];
        $list[$id_group]['_agents_unknown_'] = $agent_counter['counters']['unknown'];
        $list[$id_group]['_agents_not_init_'] = $agent_counter['counters']['not_init'];
        $list[$id_group]['_agents_ok_'] = $agent_counter['counters']['ok'];
        $list[$id_group]['_total_agents_'] = $agent_counter['counters']['total'];

        $list[$id_group]['_monitors_critical_'] = (int) $modules_counters[$id_group]['total_module_critical'];
        $list[$id_group]['_monitors_warning_'] = (int) $modules_counters[$id_group]['total_module_warning'];
        $list[$id_group]['_monitors_unknown_'] = (int) $modules_counters[$id_group]['total_module_unknown'];
        $list[$id_group]['_monitors_not_init_'] = (int) $modules_counters[$id_group]['total_module_not_init'];
        $list[$id_group]['_monitors_ok_'] = (int) $modules_counters[$id_group]['total_module_normal'];
        $list[$id_group]['_monitor_checks_'] = (int) $modules_counters[$id_group]['total_module'];
        $list[$id_group]['_monitor_not_normal_'] = ($list[$group['id_grupo']]['_monitor_checks_'] - $list[$group['id_grupo']]['_monitors_ok_']);
        $list[$id_group]['_monitors_alerts_fired_'] = (int) $modules_counters[$id_group]['total_module_alerts'];
    }

    array_unshift($list, groupview_get_all_counters($tree_group));
    return [
        'groups'  => $list,
        'counter' => $counter,
    ];
}
