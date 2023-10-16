<?php
/**
 * Extension to schedule tasks on Pandora FMS Console
 *
 * @category   Extensions
 * @package    Pandora FMS
 * @subpackage Enterprise
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

if ((bool) $config['enterprise_installed'] === true) {
    enterprise_include_once('/include/functions_cron.php');
} else {
    include_once $config['homedir'].'/include/functions_cron_task.php';
}

/**
 * Base class to run scheduled tasks in cron extension
 */
class DiscoveryConsoleTask
{
    public const SCHEDULES = [
        'no',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'custom',
    ];


    /**
     * Retrieve scheduled tasks given filters.
     *
     * @param array $filter Tasks filtered.
     *
     * @return array List of scheduled tasks.
     */
    public function list(array $filter)
    {
        $tasks = db_get_all_rows_filter(
            'tuser_task_scheduled INNER JOIN tuser_task ON tuser_task.id = tuser_task_scheduled.id_user_task',
            $filter,
            'tuser_task_scheduled.*'
        );

        if ($tasks === false) {
            return [];
        }

        $tasks = array_map(
            function ($item) {
                $item['args'] = unserialize($item['args']);
                return $item;
            },
            $tasks
        );

        return $tasks;
    }


    /**
     * Should execute task.
     *
     * @param array $task Info task.
     *
     * @return boolean
     */
    private function shouldTaskRun($task)
    {
        global $config;

        if (isset($config['reporting_console_enable']) === true
            && (bool) $config['reporting_console_enable'] === true
        ) {
            $task_info = db_get_row('tuser_task', 'id', $task['id_user_task']);

            if (isset($config['reporting_console_node']) === true
                && (bool) $config['reporting_console_node'] === true
            ) {
                if (($task_info['function_name'] !== 'cron_task_generate_report_by_template'
                    && $task_info['function_name'] !== 'cron_task_generate_report'
                    && $task_info['function_name'] !== 'cron_task_save_report_to_disk')
                ) {
                    return false;
                }
            } else {
                if (($task_info['function_name'] === 'cron_task_generate_report_by_template'
                    || $task_info['function_name'] === 'cron_task_generate_report'
                    || $task_info['function_name'] === 'cron_task_save_report_to_disk')
                ) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Manage scheduled tasks.
     *
     * @return void
     */
    public function run()
    {
        global $config;
        global $pandora_version;

        // Maintenance tasks for tconsole table.
        // Must do at every Cron execution.
        if (isset($config['id_console']) === true && $config['id_console'] > 0) {
            $console_exists = db_get_row('tconsole', 'id_console', $config['id_console']);
            if ($console_exists === false) {
                db_process_sql_insert(
                    'tconsole',
                    [
                        'id_console'   => $config['id_console'],
                        'description'  => $config['console_description'],
                        'version'      => $pandora_version,
                        'console_type' => ($config['reporting_console_node'] === true) ? 1 : 0,
                        'timezone'     => $config['timezone'],
                        'public_url'   => $config['public_url'],
                    ]
                );
            } else {
                db_process_sql_update(
                    'tconsole',
                    [
                        'description'  => $config['console_description'],
                        'timezone'     => $config['timezone'],
                        'public_url'   => $config['public_url'],
                        'console_type' => (int) $config['reporting_console_node'],
                        'version'      => $pandora_version,
                    ],
                    [
                        'id_console' => $config['id_console'],
                    ]
                );
            }
        }

        // Maintenance task: schedule daily task to manage GoTTY processes if not defined yet.
        // Must do at every Cron execution.
        $gotty_ssh_enabled = (bool) $config['gotty_ssh_enabled'];
        $gotty_telnet_enabled = (bool) $config['gotty_telnet_enabled'];

        if ($gotty_ssh_enabled  === true || $gotty_telnet_enabled === true) {
            // Create necessary data in task tables when some method of GoTTY is enabled in setup.
            if ((bool) $config['enterprise_installed'] === false) {
                $call_func_user_task_id = db_get_value_sql('SELECT id FROM `tuser_task` WHERE `function_name` = "cron_task_call_user_function"');
                if ($call_func_user_task_id === false) {
                    db_process_sql("INSERT INTO `tuser_task` (`function_name`, `parameters`, `name`) VALUES ('cron_task_call_user_function','a:1:{i:0;a:2:{s:11:\"description\";s:13:\"Function name\";s:4:\"type\";s:4:\"text\";}}','Call PHP function')");
                }
            }

            $user_function_task_id = db_get_value_sql('SELECT id FROM `tuser_task_scheduled` WHERE `args` LIKE "%cron_task_start_gotty%"');

            if ($user_function_task_id === false) {
                // Schedule task to manage GoTTY processes daily if it is not scheduled yet.
                $this->schedule(
                    'cron_task_call_user_function',
                    [
                        0               => 'cron_task_start_gotty',
                        'function_name' => 'cron_task_start_gotty',
                        'internal'      => 1,
                    ],
                    'daily',
                    0,
                    0,
                    strtotime('tomorrow')
                );
            }
        }

        // Maintenance task: check whether start GoTTY SSH and Telnet processes are running and start otherwise.
        // Must do at every Cron execution.
        cron_task_start_gotty(false);

        // Do not output anything until is completed. There're session
        // operations inside cron_task_run function.
        ob_start();

        if (cron_task_lock() === false) {
            // Cannot continue. Locked.
            echo ob_get_clean();
            exit;
        }

        $time = get_system_time();
        $scheduled_tasks = db_get_all_rows_in_table('tuser_task_scheduled');
        if (!$scheduled_tasks) {
            $scheduled_tasks = [];
        }

        /*
            Watch out! First_execution corresponds to next_execution the name
            of the bbdd is maintained to ensure integrity.
        */

        foreach ($scheduled_tasks as $task) {
            $params = unserialize($task['args']);
            if ($this->shouldTaskRun($task) === false) {
                continue;
            }

            if ($task['scheduled'] == 'no') {
                if (($params['first_execution']) < $time) {
                    echo date('Y/m/d H:i:s').' Execute once time cron task: ';
                    echo $task['id'];
                    echo "\n\n";
                    cron_task_run($task['id']);
                    // The task was not scheduled and was executed once.
                    db_process_sql_delete(
                        'tuser_task_scheduled',
                        ['id' => $task['id']]
                    );
                }
            } else {
                if (($params['first_execution']) < $time) {
                    echo date('Y/m/d H:i:s').' EXECUTED CRON TASK: '.$task['id'];
                    echo "\n";
                    echo "\n";
                    cron_task_run($task['id']);
                }
            }
        }

        // Dump to output.
        echo ob_get_clean();

        // Release the lock.
        cron_task_release_lock();

    }


    /**
     * Schedules a discovery console task to be executed by cron.
     *
     * @param string       $function_name Name of the function:
     *        cron_task_generate_report
     *        cron_task_generate_report_by_template
     *        cron_task_save_report_to_disk
     *        cron_task_do_backup
     *        cron_task_execute_custom_script
     *        cron_task_save_xml_report_to_disk
     *        cron_task_feedback_send_mail
     *        cron_task_generate_csv_log.
     * @param array        $arguments     Task execution arguments (if needed).
     * @param string       $schedule      Task schedule options:
     *        'no',
     *        'hourly',
     *        'daily',
     *        'weekly',
     *        'monthly',
     *        'yearly',
     *        'custom'.
     * @param integer      $group_id      Group id (0 => all).
     * @param string|null  $id_user       User id, if null, current user.
     * @param integer|null $time_start    When to start, if null, now.
     *
     * @return boolean Sucessfully scheduled or not.
     */
    public function schedule(
        string $function_name,
        array $arguments=[],
        string $schedule='no',
        int $group_id=0,
        ?string $id_user=null,
        ?int $time_start=null
    ) {
        global $config;

        if ($id_user === null) {
            $id_user = $config['id_user'];
        }

        $idUserTask = db_get_value(
            'id',
            'tuser_task',
            'function_name',
            $function_name
        );

        if ($idUserTask === false) {
            // Failed to identify function.
            return false;
        }

        if (in_array($schedule, self::SCHEDULES) === false) {
            // Failed to schedule. Not a valid schedule option.
            return false;
        }

        if ($time_start === null) {
            $time_start = strtotime('now');
        }

        // Params for send mail with cron.
        $parameters = array_merge(
            $arguments,
            [ 'first_execution' => $time_start ]
        );

        // Values insert task cron.
        $task = [
            'id_usuario'   => $id_user,
            'id_user_task' => $idUserTask,
            'args'         => serialize($parameters),
            'scheduled'    => $schedule,
            'id_grupo'     => $group_id,
        ];

        $result = db_process_sql_insert(
            'tuser_task_scheduled',
            $task
        );

        return ($result !== false);
    }


}
