<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once '../../include/config.php';

require_once '../../include/functions.php';
require_once '../../include/functions_db.php';
require_once '../../include/functions_users.php';
require_once '../../include/functions_groups.php';
require_once '../../include/functions_reporting.php';

$config['id_user'] = $_SESSION['id_usuario'];
if (! check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access downtime scheduler'
    );
    include 'general/noaccess.php';
    return;
}

// Filter parameters
$offset = (int) get_parameter('offset');
$search_text = (string) get_parameter('search_text');
$date_from = (string) get_parameter('date_from');
$date_to = (string) get_parameter('date_to');
$execution_type = (string) get_parameter('execution_type');
$show_archived = (bool) get_parameter('archived');
$agent_id = (int) get_parameter('agent_id');
$agent_name = !empty($agent_id) ? (string) get_parameter('agent_name') : '';
$module_id = (int) get_parameter('module_name_hidden');
$module_name = !empty($module_id) ? (string) get_parameter('module_name') : '';

$separator = (string) get_parameter('separator', ';');
$items_separator = (string) get_parameter('items_separator', ',');

$groups = users_get_groups();
if (!empty($groups)) {
    // SQL QUERY CREATION
    $where_values = '1=1';

    $groups_string = implode(',', array_keys($groups));
    $where_values .= " AND id_group IN ($groups_string)";

    if (!empty($search_text)) {
        $where_values .= " AND (name LIKE '%$search_text%' OR description LIKE '%$search_text%')";
    }

    if (!empty($execution_type)) {
        $where_values .= " AND type_execution = '$execution_type'";
    }

    if (!empty($date_from)) {
        $where_values .= " AND (type_execution = 'periodically' OR (type_execution = 'once' AND date_from >= '".strtotime("$date_from 00:00:00")."'))";
    }

    if (!empty($date_to)) {
        $periodically_monthly_w = "type_periodicity = 'monthly'
			AND ((periodically_day_from <= '".date('d', strtotime($date_from))."' AND periodically_day_to >= '".date('d', strtotime($date_to))."')
				OR (periodically_day_from > periodically_day_to
					AND (periodically_day_from <= '".date('d', strtotime($date_from))."' OR periodically_day_to >= '".date('d', strtotime($date_to))."')))";

        $periodically_weekly_days = [];
        $date_from_aux = strtotime($date_from);
        $date_end = strtotime($date_to);
        $days_number = 0;

        while ($date_from_aux <= $date_end && $days_number < 7) {
            $weekday_actual = strtolower(date('l', $date_from_aux));

            $periodically_weekly_days[] = "$weekday_actual = 1";

            $date_from_aux = ($date_from_aux + SECONDS_1DAY);
            $days_number++;
        }

        $periodically_weekly_w = "type_periodicity = 'weekly' AND (".implode(' OR ', $periodically_weekly_days).')';

        $periodically_w = "type_execution = 'periodically' AND (($periodically_monthly_w) OR ($periodically_weekly_w))";

        $once_w = "type_execution = 'once' AND date_to <= '".strtotime("$date_to 23:59:59")."'";

        $where_values .= " AND (($periodically_w) OR ($once_w))";
    }

    if (!$show_archived) {
        $where_values .= " AND (type_execution = 'periodically' OR (type_execution = 'once' AND date_to >= '".time()."'))";
    }

    if (!empty($agent_id)) {
        $where_values .= " AND id IN (SELECT id_downtime FROM tplanned_downtime_agents WHERE id_agent = $agent_id)";
    }

    if (!empty($module_id)) {
        $where_values .= " AND (id IN (SELECT id_downtime
									   FROM tplanned_downtime_modules
									   WHERE id_agent_module = $module_id)
							OR id IN (SELECT id_downtime
									  FROM tplanned_downtime_agents tpda, tagente_modulo tam
									  WHERE tpda.id_agent = tam.id_agente
									  	AND tam.id_agente_modulo = $module_id
									  	AND tpda.all_modules = 1))";
    }

    $sql = "SELECT *
			FROM tplanned_downtime
			WHERE $where_values
			ORDER BY type_execution DESC, date_from DESC";
    $downtimes = @db_get_all_rows_sql($sql);
}

if (!empty($downtimes)) {
    ob_clean();
    // Show contentype header
    // Set cookie for download control.
    setDownloadCookieToken();
    header('Content-type: text/csv');
    header('Content-Disposition: attachment; filename="pandora_planned_downtime_'.date('Y/m/d H:i:s').'.csv"');

    $titles = [];
    $titles[] = 'id';
    $titles[] = 'name';
    $titles[] = 'description';
    $titles[] = 'group';
    $titles[] = 'type';
    $titles[] = 'execution_type';
    $titles[] = 'execution_date';
    $titles[] = 'affected_items';

    echo implode($separator, $titles);
    echo chr(13);

    foreach ($downtimes as $downtime) {
        $id = $downtime['id'];
        $name = io_safe_output($downtime['name']);
        $description = io_safe_output($downtime['description']);
        $group = ucfirst(io_safe_output(groups_get_name($downtime['id_group'])));
        $type = ucfirst(io_safe_output($downtime['type_downtime']));
        $execution_type = ucfirst(io_safe_output($downtime['type_execution']));

        $execution_date = io_safe_output(reporting_format_planned_downtime_dates($downtime));

        $affected_items = [];

        $sql_agents = "SELECT tpda.id_agent AS agent_id, tpda.all_modules AS all_modules, ta.nombre AS agent_name, ta.alias
				 		FROM tplanned_downtime_agents tpda, tagente ta
				 		WHERE tpda.id_downtime = $id
				 			AND tpda.id_agent = ta.id_agente";
        $downtime_agents = @db_get_all_rows_sql($sql_agents);

        if (!empty($downtime_agents)) {
            foreach ($downtime_agents as $downtime_agent) {
                $downtime_items = [];
                $downtime_items[] = $downtime_agent['alias'];

                if (!$downtime_agent['all_modules']) {
                    $agent_id = $downtime_agent['agent_id'];
                    $sql_modules = "SELECT tpdm.id_agent_module AS module_id, tam.nombre AS module_name
				 					FROM tplanned_downtime_modules tpdm, tagente_modulo tam
				 					WHERE tpdm.id_downtime = $id
				 						AND tpdm.id_agent = $agent_id
				 						AND tpdm.id_agent_module = tam.id_agente_modulo";
                    $downtime_modules = @db_get_all_rows_sql($sql_modules);

                    if (!empty($downtime_modules)) {
                        foreach ($downtime_modules as $downtime_module) {
                            $downtime_items[] = $downtime_module['module_name'];
                        }
                    }
                }

                $affected_items[] = '['.implode('|', $downtime_items).']';
            }
        }

        $affected_items = implode(',', $affected_items);

        $values = [];
        $values[] = $id;
        $values[] = $name;
        $values[] = $description;
        $values[] = $group;
        $values[] = $type;
        $values[] = $execution_type;
        $values[] = $execution_date;
        $values[] = $affected_items;

        echo implode($separator, $values);
        echo chr(13);
    }
} else {
    echo '<div class="nf">'.__('No scheduled downtime').'</div>';
}
