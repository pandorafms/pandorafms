<?php

// Pandora FMS- http://pandorafms.com
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
require_once $config['homedir'].'/include/functions_io.php';
require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_groups.php';

ob_clean();

$get_modules_downtime = (bool) get_parameter('get_modules_downtime', 0);
$delete_module_from_downtime = (bool) get_parameter('delete_module_from_downtime', 0);
$add_module_into_downtime = (bool) get_parameter('add_module_into_downtime', 0);

// User groups with AW permission for ACL checks
$user_groups_aw = array_keys(users_get_groups($config['id_user'], 'AW'));

if ($get_modules_downtime) {
    $return = [];
    $return['correct'] = 1;
    $return['in_agent'] = [];
    $return['in_downtime'] = [];

    $id_agent = (int) get_parameter('id_agent', 0);
    $id_downtime = (int) get_parameter('id_downtime', 0);
    $none_value = (bool) get_parameter('none_value', false);

    // Check AW permission on downtime
    $downtime_group = db_get_value('id_group', 'tplanned_downtime', 'id', $id_downtime);

    if ($downtime_group === false || !in_array($downtime_group, $user_groups_aw)) {
        $return['correct'] = 0;
        echo json_encode($return);
        return;
    }

    // Check AW permission on agent
    $agent_group = db_get_value('id_grupo', 'tagente', 'id_agente', $id_agent);

    if ($agent_group === false || !in_array($agent_group, $user_groups_aw)) {
        $return['correct'] = 0;
        echo json_encode($return);
        return;
    }

    $filter = [
        'id_agent'    => $id_agent,
        'id_downtime' => $id_downtime,
    ];
    $downtime_modules = db_get_all_rows_filter('tplanned_downtime_modules', $filter);
    if (empty($downtime_modules)) {
        $downtime_modules = [];
    }

    $downtime_module_ids = extract_column($downtime_modules, 'id_agent_module');
    $downtime_modules = array_fill_keys($downtime_module_ids, true);

    $filter = [
        'id_agente'      => $id_agent,
        'delete_pending' => 0,
    ];
    $modules = db_get_all_rows_filter('tagente_modulo', $filter);
    if (empty($modules)) {
        $modules = [];
    }

    $module_ids = extract_column($modules, 'id_agente_modulo');
    $module_names = extract_column($modules, 'nombre');
    $modules = array_combine($module_ids, $module_names);

    $return['in_downtime'] = array_intersect_key($modules, $downtime_modules);
    $return['in_agent'] = array_diff($modules, $return['in_downtime']);

    if ($none_value) {
        $return['in_agent'][0] = __('None');
    }

    echo json_encode($return);
    return;
}

if ($delete_module_from_downtime) {
    $return = [];
    $return['correct'] = 0;
    $return['all_modules'] = 0;
    $return['id_agent'] = 0;

    $id_module = (int) get_parameter('id_module', 0);
    $id_downtime = (int) get_parameter('id_downtime', 0);
    $id_agent = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);

    // Check AW permission on downtime
    $downtime_group = db_get_value('id_group', 'tplanned_downtime', 'id', $id_downtime);

    if ($downtime_group === false || !in_array($downtime_group, $user_groups_aw)) {
        $return['correct'] = 0;
        echo json_encode($return);
        return;
    }

    // Check AW permission on agent
    $agent_group = db_get_value('id_grupo', 'tagente', 'id_agente', $id_agent);

    if ($id_agent === false || $agent_group === false || !in_array($agent_group, $user_groups_aw)) {
        $return['correct'] = 0;
        echo json_encode($return);
        return;
    }

    $is_running = db_get_value('executed', 'tplanned_downtime', 'id', $id_downtime);
    if ($is_running) {
        $return['executed'] = 1;
        echo json_encode($return);
        return;
    }

    $return['id_agent'] = $id_agent;

    $filter = [
        'id_agent_module' => $id_module,
        'id_downtime'     => $id_downtime,
    ];
    $result = db_process_sql_delete('tplanned_downtime_modules', $filter);

    if ($result) {
        db_clean_cache();

        $filter = [
            'id_agent'    => $id_agent,
            'id_downtime' => $id_downtime,
        ];
        $rows = db_get_all_rows_filter('tplanned_downtime_modules', $filter);

        if (empty($rows)) {
            $values = ['all_modules' => 1];
            db_process_sql_update('tplanned_downtime_agents', $values, $filter);

            $return['all_modules'] = 1;
            $return['id_agent'] = $id_agent;
        }

        $return['correct'] = 1;
    }

    echo json_encode($return);
    return;
}

if ($add_module_into_downtime) {
    $return = [];
    $return['correct'] = 0;
    $return['name'] = '';

    $id_agent = (int) get_parameter('id_agent', 0);
    $id_module = (int) get_parameter('id_module', 0);
    $id_downtime = (int) get_parameter('id_downtime', 0);

    // Check AW permission on downtime
    $downtime_group = db_get_value('id_group', 'tplanned_downtime', 'id', $id_downtime);

    if ($downtime_group === false || !in_array($downtime_group, $user_groups_aw)) {
        $return['correct'] = 0;
        echo json_encode($return);
        return;
    }

    // Check AW permission on agent
    $agent_group = db_get_value('id_grupo', 'tagente', 'id_agente', $id_agent);

    if ($agent_group === false || !in_array($agent_group, $user_groups_aw)) {
        $return['correct'] = 0;
        echo json_encode($return);
        return;
    }

    $is_running = db_get_value('executed', 'tplanned_downtime', 'id', $id_downtime);
    if ($is_running) {
        $return['executed'] = 1;
        echo json_encode($return);
        return;
    }

    $values = [];
    $values['id_agent'] = $id_agent;
    $values['id_agent_module'] = $id_module;
    $values['id_downtime'] = $id_downtime;

    $correct = db_process_sql_insert('tplanned_downtime_modules', $values);

    if ($correct) {
        db_process_sql_update(
            'tplanned_downtime_agents',
            ['all_modules' => 0],
            [
                'id_agent'    => $id_agent,
                'id_downtime' => $id_downtime,
            ]
        );
        $return['correct'] = 1;

        $return['name'] = db_get_value(
            'nombre',
            'tagente_modulo',
            'id_agente_modulo',
            $id_module
        );
    }

    echo json_encode($return);
    return;
}
