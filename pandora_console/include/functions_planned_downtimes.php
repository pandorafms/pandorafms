<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage Planned downtimes
 */

// Load global vars
global $config;

/*
 * Include the usual functions
 */
require_once $config['homedir'].'/include/functions_ui.php';


function planned_downtimes_check_dates($type_execution='once', $type_periodicity='', $datetime_from=false, $datetime_to=false, $periodically_time_from=false, $periodically_time_to=false, $periodically_day_from=false, $periodically_day_to=false)
{
    global $config;

    $now = time();

    $result = [
        'result'  => false,
        'message' => '',
    ];

    if ($type_execution == 'once' && !$config['past_planned_downtimes'] && $datetime_from < $now) {
        $result['message'] = ui_print_error_message(__('Not created. Error inserting data. Start time must be higher than the current time'), '', true);
    } else if ($type_execution == 'once' && $datetime_from >= $datetime_to) {
        $result['message'] = ui_print_error_message(__('Not created. Error inserting data').'. '.__('The end date must be higher than the start date'), '', true);
    } else if ($type_execution == 'periodically'
        && (($type_periodicity == 'weekly' && $periodically_time_from >= $periodically_time_to)
        || ($type_periodicity == 'monthly' && $periodically_day_from == $periodically_day_to && $periodically_time_from >= $periodically_time_to))
    ) {
        $result['message'] = ui_print_error_message(__('Not created. Error inserting data').'. '.__('The end time must be higher than the start time'), '', true);
    } else if ($type_execution == 'periodically' && $type_periodicity == 'monthly' && $periodically_day_from > $periodically_day_to) {
        $result['message'] = ui_print_error_message(__('Not created. Error inserting data').'. '.__('The end day must be higher than the start day'), '', true);
    } else {
        $result['result'] = true;
    }

    return $result;
}


/**
 * Update or create a planned downtime.
 *
 * @param array Values of the planned downtime.
 * @param int Id of the planned downtime. Empty to create a new downtime.
 *
 * @return array Id of the updated/created planned downtime, result of the operation and result message.
 */
function planned_downtimes_update($values, $downtime_id=0, $check_dates=true)
{
    $result = [
        'id'      => $downtime_id,
        'result'  => false,
        'message' => '',
    ];

    if ($check_dates) {
        $dates_check = planned_downtimes_check_dates(
            $values['type_execution'],
            $values['type_periodicity'],
            $values['date_from'],
            $values['date_to'],
            $values['periodically_time_from'],
            $values['periodically_time_to'],
            $values['periodically_day_from'],
            $values['periodically_day_to']
        );

        if (!$dates_check['result']) {
            $result['message'] = $dates_check['message'];

            return $result;
        }
    }

    $name_trimmed = trim(io_safe_output($values['name']));
    if (!empty($name_trimmed)) {
        $name_exists = (bool) db_get_value('id', 'tplanned_downtime', 'name', $values['name']);

        if ($name_exists) {
            $result['message'] = ui_print_error_message(__('Each scheduled downtime must have a different name'), '', true);

            return $result;
        }
    } else {
        $result['message'] = ui_print_error_message(__('Scheduled downtime must have a name'), '', true);

        return $result;
    }

    if (empty($downtime_id)) {
        $res = db_process_sql_insert('tplanned_downtime', $values);

        if ($res === false) {
            $result['message'] = ui_print_error_message(__('Could not be created'), '', true);
        } else {
            $result['message'] = ui_print_success_message(__('Successfully created'), '', true);
            $result['result'] = true;
            $result['id'] = $res;
        }
    } else {
        $res = db_process_sql_update('tplanned_downtime', $values, ['id' => $downtime_id]);

        if (empty($res)) {
            $result['message'] = ui_print_error_message(__('Could not be updated'), '', true);
        } else {
            $result['message'] = ui_print_success_message(__('Successfully updated'), '', true);
            $result['result'] = true;
        }
    }

    return $result;
}


/**
 * Add new agents and modules to the planned downtime.
 *
 * @param int Id of the planned downtime.
 * @param array IDs of the agents to add.
 * @param bool Add all modules of the agents or not.
 * @param array Names of the modules to add. Empty to add all the modules.
 *
 * @return array The status will be false id any insertion fails.
 * The failed insertions will be added to bad_agents and bad_modules.
 */
function planned_downtimes_add_items($downtime_id, $agents, $all_modules=true, $module_names=[])
{
    global $config;

    include_once $config['homedir'].'/include/functions_modules.php';

    $return = [
        'status'      => true,
        'bad_agents'  => [],
        'bad_modules' => [],
    ];

    if (empty($module_names)) {
        $all_modules = true;
    } else {
        // It is empty.
        if ($module_names[0] == '0') {
            $all_modules = true;
        }
    }

    if (empty($agents)) {
        $agents = [];
        $return['status'] = false;
    }

    foreach ($agents as $agent_id) {
        $values = [
            'id_downtime' => $downtime_id,
            'id_agent'    => $agent_id,
            'all_modules' => $all_modules,
        ];

        $result = db_process_sql_insert('tplanned_downtime_agents', $values);

        if (empty($result)) {
            $return['bad_agents'][] = $agent_id;
        } else if (!$all_modules) {
            foreach ($module_names as $module_name) {
                $module = modules_get_agentmodule_id(io_safe_input($module_name), $agent_id);
                $result = false;

                if ($module) {
                    $module_id = $module['id_agente_modulo'];

                    $values = [
                        'id_downtime'     => $downtime_id,
                        'id_agent'        => $agent_id,
                        'id_agent_module' => $module_id,
                    ];
                    $result = db_process_sql_insert('tplanned_downtime_modules', $values);
                }

                if (!$result) {
                    $return['bad_modules'][] = $module_name;
                }
            }
        }
    }

    return $return;
}


/**
 * Delete the agents and modules asociated to a planned downtime.
 *
 * @param int Id of the planned downtime.
 * @param int ID of the agent to delete.
 *
 * @return boolean The result of the operation.
 */
function planned_downtimes_delete_items($downtime_id, $agent_id)
{
    $filter = [
        'id_downtime' => $downtime_id,
        'id_agent'    => $agent_id,
    ];
    $downtime_agent_row = db_get_row('tplanned_downtime_agents', $filter);

    $result = db_process_sql_delete('tplanned_downtime_agents', ['id' => $downtime_agent_row['id']]);

    if (!empty($result) && $downtime_agent_row['all_modules'] == 0) {
        // Delete modules in downtime
        $filter = [
            'id_downtime' => $downtime_id,
            'id_agent'    => $agent_id,
        ];
        db_process_sql_delete('tplanned_downtime_modules', $filter);
    }

    return $result;
}


/**
 * Get the planned downtimes that don't comply the actual rules of dates
 *
 * @return array List of planned downtimes
 */
function planned_downtimes_get_malformed()
{
    $sql = "SELECT *
			FROM tplanned_downtime
			WHERE type_execution = 'periodically'
				AND (type_periodicity = 'monthly'
						AND (periodically_day_from > periodically_day_to
							OR (periodically_day_from = periodically_day_to
								AND periodically_time_from >= periodically_time_to)))";
    $malformed_downtimes = db_get_all_rows_sql($sql);

    if ($malformed_downtimes === false) {
        return false;
    }

    $malformed_downtimes_aux = [];
    foreach ($malformed_downtimes as $malformed_downtime) {
        $malformed_downtimes_aux[$malformed_downtime['id']] = $malformed_downtime;
    }

    $malformed_downtimes = $malformed_downtimes_aux;

    return $malformed_downtimes;
}


/**
 * Create new downtimes with the correct format and delete the malformed
 *
 * @param array List with the malformed downtimes
 *
 * @return boolean The result of the migration
 */
function planned_downtimes_migrate_malformed_downtimes($malformed_downtimes=[])
{
    global $config;

    $migration_result = [
        'status'        => true,
        'bad_downtimes' => [],
    ];

    if (empty($malformed_downtimes)) {
        $malformed_downtimes = planned_downtimes_get_malformed();
    }

    foreach ($malformed_downtimes as $key => $downtime) {
        $downtime_type = $downtime['type_downtime'];
        $downtime_execution = $downtime['type_execution'];
        $downtime_periodicity = $downtime['type_periodicity'];

        $downtime_time_from = $downtime['periodically_time_from'];
        $downtime_time_to = $downtime['periodically_time_to'];

        if ($downtime_execution == 'periodically') {
            if ($downtime_periodicity == 'monthly') {
                $downtime_day_from = $downtime['periodically_day_from'];
                $downtime_day_to = $downtime['periodically_day_to'];

                if (($downtime_day_from > $downtime_day_to) || ($downtime_day_from == $downtime_day_to && $downtime_time_from > $downtime_time_to)) {
                    $values_first = [
                        'name'                   => $downtime['name'].'&nbsp;[1/2]',
                        'description'            => $downtime['description'],
                        'executed'               => $downtime['executed'],
                        'id_group'               => $downtime['id_group'],
                        'only_alerts'            => $downtime['only_alerts'],
                        'periodically_time_from' => $downtime_time_from,
                        'periodically_time_to'   => '23:59:59',
                        'periodically_day_from'  => $downtime_day_from,
                        'periodically_day_to'    => 31,
                        'type_downtime'          => $downtime_type,
                        'type_execution'         => $downtime_execution,
                        'type_periodicity'       => $downtime_periodicity,
                        'id_user'                => $config['id_user'],
                    ];
                    $values_second = $values_first;
                    $values_second['name'] = $downtime['name'].'&nbsp;[2/2]';
                    $values_second['periodically_day_from'] = 1;
                    $values_second['periodically_time_from'] = '00:00:00';
                    $values_second['periodically_day_to'] = $downtime_day_to;
                    $values_second['periodically_time_to'] = $downtime_time_to;

                    $result_first = planned_downtimes_update($values_first, 0, false);

                    if ($result_first['result']) {
                        $result_second = planned_downtimes_update($values_second, 0, false);

                        if (!$result_second['result']) {
                            db_process_sql_delete('tplanned_downtime', ['id' => $result_first['id']]);
                        }
                    }

                    if ($result_first['result'] && $result_second['result']) {
                        $result_copy = planned_downtimes_migrate_malformed_downtimes_copy_items($downtime['id'], $result_first['id'], $result_second['id']);

                        if (!$result_copy) {
                            $migration_result['status'] = false;
                            $migration_result['bad_downtimes'][] = $downtime['id'];
                        }
                    } else {
                        $migration_result['status'] = false;
                        $migration_result['bad_downtimes'][] = $downtime['id'];
                    }
                } else if ($downtime_day_from == $downtime_day_to && $downtime_time_from == $downtime_time_to) {
                    $utimestamp = strtotime("1-1-1970 $downtime_time_to");
                    $time = date('H:i:s', ($utimestamp + 1));

                    if ($time != '00:00:00') {
                        $values = ['periodically_time_to' => $time];
                    } else {
                        $utimestamp = strtotime("1-1-1970 $downtime_time_from");
                        $time = date('H:i:s', ($utimestamp - 1));
                        $values = ['periodically_time_from' => $time];
                    }

                    $filter = ['id' => $downtime['id']];
                    $result_update = db_process_sql_update('tplanned_downtime', $values, $filter);

                    if (!$result_update) {
                        $migration_result['status'] = false;
                        $migration_result['bad_downtimes'][] = $downtime['id'];
                    }
                }
            } else if ($downtime_periodicity == 'weekly') {
                if ($downtime_time_from > $downtime_time_to) {
                    $days = [
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                        'saturday',
                        'sunday',
                    ];

                    $values_first = [
                        'name'                   => $downtime['name'].'&nbsp;[1/2]',
                        'description'            => $downtime['description'],
                        'executed'               => $downtime['executed'],
                        'id_group'               => $downtime['id_group'],
                        'only_alerts'            => $downtime['only_alerts'],
                        'monday'                 => $downtime['monday'],
                        'tuesday'                => $downtime['tuesday'],
                        'wednesday'              => $downtime['wednesday'],
                        'thursday'               => $downtime['thursday'],
                        'friday'                 => $downtime['friday'],
                        'saturday'               => $downtime['saturday'],
                        'sunday'                 => $downtime['sunday'],
                        'periodically_time_from' => $downtime_time_from,
                        'periodically_time_to'   => '23:59:59',
                        'type_downtime'          => $downtime_type,
                        'type_execution'         => $downtime_execution,
                        'type_periodicity'       => $downtime_periodicity,
                        'id_user'                => $config['id_user'],
                    ];

                    $values_second = $values_first;
                    $values_second['name'] = $downtime['name'].'&nbsp;[2/2]';
                    $values_second['periodically_time_from'] = '00:00:00';
                    $values_second['periodically_time_to'] = $downtime_time_to;
                    $values_second['tuesday'] = $downtime['monday'] ? 1 : 0;
                    $values_second['wednesday'] = $downtime['tuesday'] ? 1 : 0;
                    $values_second['thursday'] = $downtime['wednesday'] ? 1 : 0;
                    $values_second['friday'] = $downtime['thursday'] ? 1 : 0;
                    $values_second['saturday'] = $downtime['friday'] ? 1 : 0;
                    $values_second['sunday'] = $downtime['saturday'] ? 1 : 0;
                    $values_second['monday'] = $downtime['sunday'] ? 1 : 0;

                    $result_first = planned_downtimes_update($values_first, 0, false);

                    if ($result_first['result']) {
                        $result_second = planned_downtimes_update($values_second, 0, false);

                        if (!$result_second['result']) {
                            db_process_sql_delete('tplanned_downtime', ['id' => $result_first['id']]);
                        }
                    }

                    if ($result_first['result'] && $result_second['result']) {
                        $result_copy = planned_downtimes_migrate_malformed_downtimes_copy_items($downtime['id'], $result_first['id'], $result_second['id']);

                        if (!$result_copy) {
                            $migration_result['status'] = false;
                            $migration_result['bad_downtimes'][] = $downtime['id'];
                        }
                    } else {
                        $migration_result['status'] = false;
                        $migration_result['bad_downtimes'][] = $downtime['id'];
                    }
                } else if ($downtime_time_from == $downtime_time_to) {
                    $utimestamp = strtotime("1-1-1970 $downtime_time_to");
                    $time = date('H:i:s', ($utimestamp + 1));

                    if ($time != '00:00:00') {
                        $values = ['periodically_time_to' => $time];
                    } else {
                        $utimestamp = strtotime("1-1-1970 $downtime_time_from");
                        $time = date('H:i:s', ($utimestamp - 1));
                        $values = ['periodically_time_from' => $time];
                    }

                    $filter = ['id' => $downtime['id']];
                    $result_update = db_process_sql_update('tplanned_downtime', $values, $filter);

                    if (!$result_update) {
                        $migration_result['status'] = false;
                        $migration_result['bad_downtimes'][] = $downtime['id'];
                    }
                }
            }
        }
    }

    return $migration_result;
}


/**
 * Aux function to copy the items of the selected downtime to the new downtimes.
 * Deletes the new downtimes if the items are not copied.
 */
function planned_downtimes_migrate_malformed_downtimes_copy_items($original_downtime_id, $new_downtime_first_id, $new_downtime_second_id)
{
    $sql = 'SELECT *
			FROM tplanned_downtime_agents
			WHERE id_downtime = '.$original_downtime_id;
    $downtime_agents_rows = db_get_all_rows_sql($sql);
    $sql = 'SELECT *
			FROM tplanned_downtime_modules
			WHERE id_downtime = '.$original_downtime_id;
    $downtime_modules_rows = db_get_all_rows_sql($sql);

    if (!empty($downtime_agents_rows)) {
        $result_agents = true;

        foreach ($downtime_agents_rows as $downtime_agents_row) {
            $values_agent = [
                'id_agent'    => $downtime_agents_row['id_agent'],
                'all_modules' => $downtime_agents_row['all_modules'],
            ];
            $values_agent['id_downtime'] = $new_downtime_first_id;
            $result_agent_first = db_process_sql_insert('tplanned_downtime_agents', $values_agent);
            $values_agent['id_downtime'] = $new_downtime_second_id;
            $result_agent_second = db_process_sql_insert('tplanned_downtime_agents', $values_agent);

            if (empty($result_agent_first) || empty($result_agent_second)) {
                $result_agents = false;
                db_process_sql_delete('tplanned_downtime', ['id' => $new_downtime_first_id]);
                db_process_sql_delete('tplanned_downtime', ['id' => $new_downtime_second_id]);
                break;
            }
        }

        if ($result_agents) {
            if (!empty($downtime_modules_rows)) {
                foreach ($downtime_modules_rows as $downtime_modules_row) {
                    $values_module = [
                        'id_agent'        => $downtime_modules_row['id_agent'],
                        'id_agent_module' => $downtime_modules_row['id_agent_module'],
                    ];
                    $values_module['id_downtime'] = $new_downtime_first_id;
                    $result_module_first = db_process_sql_insert('tplanned_downtime_modules', $values_module);
                    $values_module['id_downtime'] = $new_downtime_second_id;
                    $result_module_second = db_process_sql_insert('tplanned_downtime_modules', $values_module);

                    if (empty($result_module_first) || empty($result_module_second)) {
                        db_process_sql_delete('tplanned_downtime', ['id' => $new_downtime_first_id]);
                        db_process_sql_delete('tplanned_downtime', ['id' => $new_downtime_second_id]);
                        break;
                    }
                }
            }
        }
    }

    // The new downtimes are created
    $new_planned_downtimes_exists = (bool) db_get_value('id', 'tplanned_downtime', 'id', $new_downtime_first_id)
        && (bool) db_get_value('id', 'tplanned_downtime', 'id', $new_downtime_second_id);

    if ($new_planned_downtimes_exists) {
        // Delete the migrated downtime and its items
        db_process_sql_delete('tplanned_downtime', ['id' => $original_downtime_id]);
    }

    return $new_planned_downtimes_exists;
}


/**
 * Stop a planned downtime.
 *
 * @param array $downtime Planned downtime data.
 *
 * @return mixed False on error or an array with the result and a message of
 *      the operation.
 */
function planned_downtimes_stop($downtime)
{
    global $config;

    $result = false;
    $message = '';

    if (empty($downtime)) {
        return false;
    }

    $id_downtime = $downtime['id'];

    switch ($downtime['type_execution']) {
        case 'once':
            $values = [
                'executed' => 0,
                'date_to'  => time(),
            ];

            $result = db_process_sql_update(
                'tplanned_downtime',
                $values,
                ['id' => $id_downtime]
            );
        break;

        case 'periodically':
        return false;

        default:
            // Nothing to do.
        break;
    }

    $message .= ui_print_result_message(
        $result,
        __('Succesful stopped the Downtime'),
        __('Unsuccesful stopped the Downtime'),
        true
    );

    if ($result) {
        events_create_event(
            'Manual stop downtime  '.$downtime['name'].' ('.$downtime['id'].') by '.$config['id_user'],
            0,
            0,
            EVENT_STATUS_NEW,
            $config['id_user'],
            'system',
            1
        );
        db_pandora_audit(
            AUDIT_LOG_SYSTEM,
            'Manual stop downtime '.$downtime['name'].' (ID '.$downtime['id'].')',
            false,
            true
        );

        // Reenabled the Agents or Modules or alerts...depends of type.
        switch ($downtime['type_downtime']) {
            case 'quiet':
                $agents = db_get_all_rows_filter(
                    'tplanned_downtime_agents',
                    ['id_downtime' => $id_downtime]
                );
                if (empty($agents)) {
                    $agents = [];
                }

                $count = 0;
                foreach ($agents as $agent) {
                    if ($agent['all_modules']) {
                        $result = db_process_sql_update(
                            'tagente',
                            ['quiet' => 0],
                            ['id_agente' => $agent['id_agent']]
                        );

                        if ($result) {
                            $count++;
                        }
                    } else {
                        $modules = db_get_all_rows_filter(
                            'tplanned_downtime_modules',
                            [
                                'id_agent'    => $agent['id_agent'],
                                'id_downtime' => $id_downtime,
                            ]
                        );
                        if (empty($modules)) {
                            $modules = [];
                        }

                        foreach ($modules as $module) {
                            $result = db_process_sql_update(
                                'tagente_modulo',
                                ['quiet' => 0],
                                [
                                    'id_agente_modulo' => $module['id_agent_module'],
                                ]
                            );

                            if ($result) {
                                $count++;
                            }
                        }
                    }
                }
            break;

            case 'disable_agents':
                $agents = db_get_all_rows_filter(
                    'tplanned_downtime_agents',
                    ['id_downtime' => $id_downtime]
                );
                if (empty($agents)) {
                    $agents = [];
                }

                $count = 0;
                foreach ($agents as $agent) {
                    $result = db_process_sql_update(
                        'tagente',
                        [
                            'disabled'            => 0,
                            'update_module_count' => 1,
                        ],
                        ['id_agente' => $agent['id_agent']]
                    );

                    if ($result) {
                        $count++;
                    }
                }
            break;

            case 'disable_agent_modules':
                $update_sql = sprintf(
                    'UPDATE tagente_modulo tam, tagente ta, tplanned_downtime_modules tpdm
                    SET tam.disabled = 0, ta.update_module_count = 1
                    WHERE tpdm.id_agent_module = tam.id_agente_modulo AND
                    ta.id_agente = tam.id_agente AND
                    tpdm.id_downtime = %d',
                    $id_downtime
                );

                db_process_sql($update_sql);

                $count = '';
            break;

            case 'disable_agents_alerts':
                $agents = db_get_all_rows_filter(
                    'tplanned_downtime_agents',
                    ['id_downtime' => $id_downtime]
                );
                if (empty($agents)) {
                    $agents = [];
                }

                $count = 0;
                foreach ($agents as $agent) {
                    $modules = db_get_all_rows_filter(
                        'tagente_modulo',
                        ['id_agente' => $agent['id_agent']]
                    );
                    if (empty($modules)) {
                        $modules = [];
                    }

                    foreach ($modules as $module) {
                        $result = db_process_sql_update(
                            'talert_template_modules',
                            ['disabled' => 0],
                            [
                                'id_agent_module' => $module['id_agente_modulo'],
                            ]
                        );

                        if ($result) {
                            $count++;
                        }
                    }
                }
            break;

            default:
                // Nothing to do.
            break;
        }

        $message .= ui_print_info_message(
            sprintf(__('Enabled %s elements from the downtime'), $count),
            true
        );
    }

    return [
        'result'  => $result,
        'message' => $message,
    ];
}


function planned_downtimes_created($values)
{
    global $config;

    $check_id_user = (bool) db_get_value('id_user', 'tusuario', 'id_user', $values['id_user']);
    $check_group = (bool) db_get_value('id_grupo', 'tgrupo', 'id_grupo', $values['id_group']);
    $check = (bool) db_get_value('name', 'tplanned_downtime', 'name', $values['name']);

    $now = time();
    $result = false;

    if ($values['type_execution'] == 'once' && !$config['past_planned_downtimes'] && $values['date_from'] < $now) {
        return [
            'return'  => false,
            'message' => __('Not created. Error inserting data. Start time must be higher than the current time'),
        ];
    } else if ($values['type_execution'] == 'once' && !$config['past_planned_downtimes'] && $values['date_to'] <= $now) {
        return [
            'return'  => false,
            'message' => __('Not created. Error inserting data').'. '.__('The end date must be higher than the current time'),
        ];
    } else if ($values['type_execution'] == 'once' && ($values['date_from'] > $values['date_to'])
        || (($values['date_from'] == $values['date_to']) && ($values['periodically_time_from'] >= $values['periodically_time_to']))
    ) {
        return [
            'return'  => false,
            'message' => __('Not created. Error inserting data').'. '.__('The end date must be higher than the start date'),
        ];
    } else if ($values['type_execution'] == 'periodically'
        && (($values['type_periodicity'] == 'weekly' && $values['periodically_time_from'] >= $values['periodically_time_to'])
        || ($values['type_periodicity'] == 'monthly' && $values['periodically_day_from'] == $values['periodically_day_to'] && $values['periodically_time_from'] >= $values['periodically_time_to']))
    ) {
        return [
            'return'  => false,
            'message' => __('Not created. Error inserting data').'. '.__('The end time must be higher than the start time'),
        ];
    } else if ($values['type_execution'] == 'periodically'
        && $values['type_periodicity'] == 'monthly'
        && $values['periodically_day_from'] > $values['periodically_day_to']
    ) {
        return [
            'return'  => false,
            'message' => __('Not created. Error inserting data').'. '.__('The end day must be higher than the start day'),
        ];
    } else if ($values['type_downtime'] !== 'quiet' && $values['type_downtime'] !== 'disable_agents' && $values['type_downtime'] !== 'disable_agents_alerts') {
        return [
            'return'  => false,
            'message' => __('Not created. Error inserting data').'. '.__('The downtime must be quiet, disable_agents or disable_agents_alerts'),
        ];
    } else if ($values['type_execution'] !== 'periodically' && $values['type_execution'] !== 'once') {
        return [
            'return'  => false,
            'message' => __('Not created. Error inserting data').'. '.__('The execution must be once or periodically'),
        ];
    } else if ($values['type_periodicity'] !== 'weekly' && $values['type_periodicity'] !== 'monthly') {
        return [
            'return'  => false,
            'message' => __('Not created. Error inserting data').'. '.__('The periodicity must be weekly or monthly'),
        ];
    } else if (!$check_id_user) {
        return [
            'return'  => false,
            'message' => __('Not created. Error inserting data').'. '.__('There is no user with such id'),
        ];
    } else if (!$check_group && $values['id_group'] != 0) {
        return [
            'return'  => false,
            'message' => __('Not created. Error inserting data').'. '.__('There is no group with such id'),
        ];
    } else if (!$values['date_from'] || !$values['date_to']) {
        return [
            'return'  => false,
            'message' => __('Not created. Error inserting data').'. '.__('Date is wrong formatted'),
        ];
    } else {
        if (trim(io_safe_output($values['name'])) != '') {
            if (!$check) {
                if ($config['dbtype'] == 'oracle') {
                    $values['periodically_time_from'] = '1970/01/01 '.$values['periodically_time_from'];
                    $values['periodically_time_to'] = '1970/01/01 '.$values['periodically_time_to'];
                }

                $result = db_process_sql_insert('tplanned_downtime', $values);
            } else {
                return [
                    'return'  => false,
                    'message' => __('Each scheduled downtime must have a different name'),
                ];
            }
        } else {
            return [
                'return'  => false,
                'message' => __('Scheduled downtime must have a name'),
            ];
        }

        if ($result === false) {
            return [
                'return'  => false,
                'message' => __('Could not be created'),
            ];
        } else {
            return [
                'return'  => $result,
                'message' => __('Successfully created'),
            ];
        }
    }

    return $return;
}


function all_planned_downtimes($filter)
{
    $fields = 'id, name, description, date_from, date_to, id_group, monday,
		tuesday, wednesday, thursday, friday, saturday, sunday,
		periodically_time_from, periodically_time_to, periodically_day_from, 
		periodically_day_to, type_downtime, type_execution, type_periodicity, id_user';

    $result = db_get_all_rows_filter('tplanned_downtime', $filter, $fields);

    return $result;
}


function planned_downtimes_items($filter)
{
    $downtime_agents = db_get_all_rows_filter('tplanned_downtime_agents', $filter, 'id_agent,id_downtime,all_modules');
    $downtime = db_get_row_filter('tplanned_downtime', ['id' => $filter['id_downtime']], 'type_downtime');

    $return = [
        'id_agents'   => [],
        'id_downtime' => $filter['id_downtime'],
        'all_modules' => 0,
        'modules'     => [],
    ];
    foreach ($downtime_agents as $key => $data) {
        // Do not add the agent information if no permissions
        if (!agents_check_access_agent($data['id_agent'], 'AR')) {
            continue;
        }

        $return['id_agents'][] = $data['id_agent'];
        $return['id_downtime'] = $data['id_downtime'];
        $return['all_modules'] = $data['all_modules'];
        if ($downtime['type_downtime'] === 'quiet') {
            if (!$data['all_modules']) {
                $second_filter = [
                    'id_agent'    => $data['id_agent'],
                    'id_downtime' => $data['id_downtime'],
                ];

                $downtime_modules = db_get_all_rows_filter('tplanned_downtime_modules', $second_filter, 'id_agent_module');
                if ($downtime_modules) {
                    foreach ($downtime_modules as $data2) {
                        $return['modules'][$data2['id_agent_module']] = $data2['id_agent_module'];
                    }
                }
            }
        }
    }

    if (empty($return['id_agents'])) {
        return false;
    }

    // Implode agents and modules
    $return['id_agents'] = implode(',', $return['id_agents']);
    $return['modules'] = implode(',', $return['modules']);
    return $return;
}


function delete_planned_downtimes($filter)
{
    $downtime_execute = db_get_row_filter('tplanned_downtime', ['id' => $filter['id_downtime']], 'execute');

    if ($downtime_execute) {
        $return = __("This scheduled downtime are executed now. Can't delete in this moment.");
    } else {
        $delete = db_process_sql_delete(
            'tplanned_downtime',
            ['id' => $filter['id_downtime']]
        );
        if ($delete) {
            $return = __('Deleted this scheduled downtime successfully.');
        } else {
            $return = __('Problems for deleted this scheduled downtime.');
        }
    }

    return $return;
}


function planned_downtimes_copy($id_downtime)
{
    $planned_downtime = db_get_row_filter(
        'tplanned_downtime',
        ['id' => $id_downtime]
    );

    $planned_agents = db_get_all_rows_filter(
        'tplanned_downtime_agents',
        ['id_downtime' => $id_downtime]
    );

    $planned_modules = db_get_all_rows_filter(
        'tplanned_downtime_modules',
        ['id_downtime' => $id_downtime]
    );

    if ($planned_downtime === false) {
        return false;
    }

    // Unset id.
    unset($planned_downtime['id']);

    // Change copy name.
    $planned_downtime['name'] = __('Copy of ').$planned_downtime['name'];

    // Insert new downtime
    $result['id_downtime'] = db_process_sql_insert(
        'tplanned_downtime',
        $planned_downtime
    );

    if ($result === false) {
        $result['error'] = __('Could not be copied');
        return $result;
    } else {
        $result['success'] = __('Successfully copied');
    }

    if ($planned_agents !== false) {
        foreach ($planned_agents as $planned_agent) {
            // Unset id.
            unset($planned_agent['id']);
            // Set id_planned downtime
            $planned_agent['id_downtime'] = $result['id_downtime'];
            $result['id_agents'][] = db_process_sql_insert(
                'tplanned_downtime_agents',
                $planned_agent
            );

            if ($result === false) {
                $return['error'] = __('Error adding agents to copied downtime');
                break;
            }
        }
    }

    if ($result === false) {
        return false;
    }

    if ((bool) $planned_agents['all_modules'] === false
        && $planned_modules !== false
    ) {
        foreach ($planned_modules as $planned_module) {
            // Unset id.
            unset($planned_module['id']);
            // Set id_planned downtime.
            $planned_module['id_downtime'] = $result['id_downtime'];
            $result['id_modules'][] = db_process_sql_insert(
                'tplanned_downtime_moduless',
                $planned_module
            );
            if ($result === false) {
                $return['error'] = __('Error adding module to copied downtime');
                break;
            }
        }
    }

    return $result;
}


/**
 * Get agentts and modules for planned_downtime.
 *
 * @param [type] $id Id planned.
 *
 * @return array Result array data.
 */
function get_agents_modules_planned_dowtime($id, $options, $count=false)
{
    $result = [];

    $filters_agent = '';
    if (isset($options['filters']['filter_agents']) === true
        && empty($options['filters']['filter_agents']) === false
    ) {
        $filters_agent = sprintf(
            ' AND (tagente.alias LIKE "%%%s%%")',
            $options['filters']['filter_agents']
        );
    }

    $filters_module = '';
    if (isset($options['filters']['filter_modules']) === true
        && empty($options['filters']['filter_modules']) === false
    ) {
        $filters_module = sprintf(
            ' AND (tagente_modulo.nombre LIKE "%%%s%%")',
            $options['filters']['filter_modules']
        );
    }

    if ($count === false) {
        $query = sprintf(
            'SELECT tplanned_downtime_modules.*,
            tagente.alias as agent_name,
            tagente_modulo.nombre as module_name
        FROM tplanned_downtime_modules
        INNER JOIN tagente
            ON tplanned_downtime_modules.id_agent = tagente.id_agente
        INNER JOIN tagente_modulo
            ON tplanned_downtime_modules.id_agent_module = tagente_modulo.id_agente_modulo
        WHERE id_downtime = %d
            %s
            %s
        ORDER BY %s
        LIMIT %d, %d',
            $id,
            $filters_agent,
            $filters_module,
            $options['order'],
            $options['limit'],
            $options['offset']
        );
    } else {
        $query = sprintf(
            'SELECT count(tplanned_downtime_modules.id) as total
        FROM tplanned_downtime_modules
        INNER JOIN tagente
            ON tplanned_downtime_modules.id_agent = tagente.id_agente
        INNER JOIN tagente_modulo
            ON tplanned_downtime_modules.id_agent_module = tagente_modulo.id_agente_modulo
        WHERE id_downtime = %d
            %s
            %s',
            $id,
            $filters_agent,
            $filters_module
        );
    }

    $result = db_get_all_rows_sql($query);
    if ($result === false) {
        $result = [];
    }

    return $result;
}
