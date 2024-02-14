<?php
/**
 * Schedule tasks on Pandora FMS Console
 *
 * @category   library
 * @package    Pandora FMS
 * @subpackage cron
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


/**
 * Generate a lock system to avoid multiple executions.
 *
 * @param string $lockfile Filename to use as lock.
 *
 * @return boolean
 */
function cron_lock(string $lockfile)
{
    global $config;

    if (empty($lockfile) === true) {
        $lockfile = 'cron.lock';
    }

    $ignore_lock = 0;
    // Lock to prevent multiple instances of the cron extension.
    $lock = $config['attachment_store'].'/'.$lockfile;
    if (file_exists($lock) === true) {
        // Lock file exists.
        $read_PID = file_get_contents($lock);
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows check.
            $processes = explode("\n", shell_exec('tasklist.exe'));
            $ignore_lock = 1;
            foreach ($processes as $process) {
                if (empty($process) === true
                    || strpos('===', $process) === 0
                ) {
                    continue;
                }

                $matches = false;
                preg_match('/(.*?)\s+(\d+).*$/', $process, $matches);
                $pid = $matches[2];

                if ((int) $pid === (int) $read_PID) {
                    $ignore_lock = 0;
                    break;
                }
            }
        } else {
            // Linux check.
            if (file_exists('/proc/'.$read_PID) === true) {
                // Process with a pid = $pid is running.
                // CRON already running: [$read_PID].
                $ignore_lock = 0;
            } else {
                // CRON process [$read_PID] does not exist.
                // process not found, ignore $lock.
                $ignore_lock = 1;
            }
        }

        // The lock automatically expires after 24 hours.
        $lock_mtime = filemtime($lock);
        if (($ignore_lock === 0 )
            && ($lock_mtime !== false && $lock_mtime + SECONDS_1DAY > time())
        ) {
            // Locked!
            return false;
        }
    }

    // Try to get a lock from DB.
    $dblock = db_get_lock($config['dbname'].'.'.$lockfile);
    if ($dblock !== 1) {
        // Locked!
        return false;
    }

    // Store PID in lock file.
    $PID = getmypid();
    echo 'CRON running ['.$PID."]\n";
    file_put_contents($lock, $PID);
    return true;
}


/**
 * Check if CRON.task is available to start.
 *
 * @return boolean True, available. False not available.
 */
function cron_task_lock()
{
    return cron_lock('cron.lock');
}


/**
 * Release CRON.task lock
 *
 * @return void
 */
function cron_task_release_lock()
{
    global $config;

    // Release DB lock.
    $dblock = db_release_lock($config['dbname'].'.cron.lock');
    unlink($config['attachment_store'].'/cron.lock');
}


/**
 * Calculates target schedule time
 *
 * @param string       $scheduled_time Desired scheduled time.
 * @param integer      $custom_data    Custom scheduled time.
 * @param integer|null $timestamp      Custom timestamp.
 *
 * @return integer amount of time.
 */
function cron_get_scheduled_time(
    string $scheduled_time,
    int $custom_data=0,
    $timestamp=null
) {
    if ($scheduled_time == 'no') {
        return 0;
    }

    if ($scheduled_time == 'hourly') {
        return SECONDS_1HOUR;
    }

    if ($scheduled_time == 'daily') {
        return SECONDS_1DAY;
    }

    if ($scheduled_time == 'weekly') {
        return SECONDS_1WEEK;
    }

    if ($scheduled_time == 'monthly') {
        $month = (($timestamp === null) ? date('m') : date('m', $timestamp));
        $year = (($timestamp === null) ? date('Y') : date('Y', $timestamp));

        $days_month = (cal_days_in_month(
            CAL_GREGORIAN,
            $month,
            $year
        ) * SECONDS_1DAY);

        return $days_month;
    }

    if ($scheduled_time == 'yearly') {
        return SECONDS_1YEAR;
    }

    if ($scheduled_time == 'custom') {
        return $custom_data;
    }

    return 0;
}


/**
 * Run scheduled task.
 *
 * @param integer $id_user_task Task to be run.
 * @param boolean $force_run    Force run.
 *
 * @return void
 */
function cron_task_run(
    int $id_user_task,
    bool $force_run=false
) {
    global $config;

    if (isset($config['id_console']) === true && $config['id_console'] > 0) {
        $sql = sprintf(
            'SELECT *
            FROM tuser_task_scheduled
            WHERE id=%d AND id_console IN (0, %d)',
            $id_user_task,
            $config['id_console']
        );

        $task_scheduled = db_get_row_sql($sql);

        if ($task_scheduled !== false) {
            db_process_sql_update(
                'tconsole',
                ['last_execution' => time()],
                ['id_console' => $config['id_console']]
            );
        }
    } else {
        $filter = [
            'id'         => $id_user_task,
            'id_console' => 0,
        ];

        $task_scheduled = db_get_row_filter('tuser_task_scheduled', $filter, false);
    }

    $args = unserialize($task_scheduled['args']);

    if ((bool) $config['enterprise_installed'] === false
        && isset($args['function_name']) === true
        && $args['function_name'] !== 'cron_task_start_gotty'
    ) {
        // Only cron_task_start_gotty is allowed to run in non enterprise environments.
        return;
    }

    if ((bool) $config['enterprise_installed'] === true) {
        $task = db_get_row('tuser_task', 'id', $task_scheduled['id_user_task']);
    } else {
        $task = [
            'name'          => 'Call PHP function',
            'function_name' => 'cron_task_call_user_function',
        ];
    }

    // Register shutdown function in case of fatal error, like.
    register_shutdown_function('cron_task_handle_error', $task_scheduled, $task, $force_run);

    if (is_metaconsole() && !defined('METACONSOLE')) {
        define('METACONSOLE', 1);
    }

    if (! function_exists($task['function_name'])) {
        return;
    }

    // If the task is disable, not run.
    if ((bool) $task_scheduled['enabled'] === false) {
        return;
    }

    if (session_status() === PHP_SESSION_DISABLED) {
        return;
    }

    $old_user = '';
    if (isset($config['id_user']) === false) {
        $config['id_user'] = $task_scheduled['id_usuario'];
    }

    $old_user = $config['id_user'];

    $old_session_id = session_id();
    $new_session_id = 'cron-'.uniqid();

    // Simulate user login.
    session_id($new_session_id);
    session_start();
    $_SESSION['id_usuario'] = $config['id_user'];
    session_write_close();

    set_time_limit(0);

    if ($task['function_name'] == 'cron_task_generate_report_by_template'
        || $task['function_name'] == 'cron_task_generate_report'
    ) {
        // If empty agent position, add it.
        if (!isset($args[1])) {
            array_splice($args, 1, 0, '');
        }

        $args[] = $task_scheduled['scheduled'];
    }

    call_user_func_array(
        $task['function_name'],
        array_merge(array_values(($args ?? [])), [$id_user_task])
    );

    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_destroy();
    }

    session_id($old_session_id);
    session_start();

    $config['id_user'] = $old_user;
    $sql = '';
    $sql2 = '';

    if (!$force_run) {
        $period = cron_get_scheduled_time(
            $task_scheduled['scheduled'],
            $task_scheduled['custom_data']
        );
        $old_args = unserialize($task_scheduled['args']);
        if ($period > 3600) {
            $array_explode = explode(
                ':',
                date('H:i', $old_args['first_execution'])
            );
            $hora_en_segundos = (($array_explode[0] * 3600 ) + ($array_explode[1] * 60));

            $array_explode_period = explode(
                ':',
                date('H:i', ($old_args['first_execution'] + $period))
            );
            $hora_en_segundos2 = (($array_explode_period[0] * 3600 ) + ($array_explode_period[1] * 60));

            if ($hora_en_segundos !== $hora_en_segundos2) {
                $period = ($period + ($hora_en_segundos - $hora_en_segundos2));
            }
        }

        try {
            /*
                Calculate the number of periods between last execution and
                current time.
            */

            $num_of_periods = 0;
            if ($period !== 0) {
                $num_of_periods = ceil(
                    (time() - $old_args['first_execution']) / $period
                );
            }

            if ($task_scheduled['scheduled'] == 'monthly') {
                $updated_time = $old_args['first_execution'];

                // Update updated_time adding the period for each month individually since it is a variable value depending on the number of days a month has.
                while ($num_of_periods > 0) {
                    // Get days of current month.
                    $monthly_period = cron_get_scheduled_time(
                        'monthly',
                        $task_scheduled['custom_data'],
                        $updated_time
                    );
                    $updated_time += $monthly_period;
                    $num_of_periods--;
                }

                $old_args['first_execution'] = $updated_time;
            } else if ($task_scheduled['scheduled'] == 'weekly') {
                $weekly_schedule = json_decode(io_safe_output($old_args['weekly_schedule']), true);
                if (empty($weekly_schedule) !== true) {
                    $datetime = new DateTime('tomorrow');
                    $nameday = strtolower($datetime->format('l'));
                    $continue = true;
                    while ($continue === true) {
                        if (isset($weekly_schedule[$nameday]) === true) {
                            $weekly_date = $datetime->format('Y-m-d');
                            $weekly_time = $weekly_schedule[$nameday][0]['start'];
                            $old_args['first_execution'] = strtotime($weekly_date.' '.$weekly_time);

                            $continue = false;
                        } else {
                            $datetime->modify('+1 day');
                            $nameday = strtolower($datetime->format('l'));
                        }
                    }
                } else if (empty($old_args['first_execution']) === false) {
                    $datetime = new DateTime();
                    $datetime->setTimestamp($old_args['first_execution']);
                    $datetime->modify('+7 day');
                    $weekly_date = $datetime->format('Y-m-d');
                    $weekly_time = $datetime->format('H:i:s');
                    $old_args['first_execution'] = strtotime($weekly_date.' '.$weekly_time);
                }
            } else {
                // Add it to next execution.
                $old_args['first_execution'] += ($period * $num_of_periods);
            }
        } catch (Exception $e) {
            // If some error (ex $period=0) next execution=current time+period.
            $old_args['first_execution'] = (time() + $period);
        }

        $new_args = serialize($old_args);
    }

    if ($config['timesource'] == 'sql') {
        $sql = sprintf(
            'UPDATE tuser_task_scheduled
            SET last_run=UNIX_TIMESTAMP()
            WHERE id=%d',
            $id_user_task
        );
    } else {
        $sql = sprintf(
            'UPDATE tuser_task_scheduled
						SET last_run= %d
						WHERE id=%d',
            time(),
            $id_user_task
        );
    }

    if (!$force_run) {
        $sql2 = "UPDATE tuser_task_scheduled
			SET args = '".$new_args."'
			WHERE id=".$id_user_task;
    }

    db_pandora_audit(
        AUDIT_LOG_CRON_TASK,
        'Executed cron task: '.$task['name'].' #'.$task['id'],
        false,
        false,
        ''
    );

    db_process_sql($sql);
    db_process_sql($sql2);
}


/**
 * Execuytes custom function defined in PHP.
 *
 * @param string $function_name Name to execute.
 *
 * @return void
 */
function cron_task_call_user_function(string $function_name)
{
    global $config;

    include_once $config['homedir'].'/vendor/autoload.php';

    call_user_func($function_name);
}


/**
 * Check whether GoTTY SSH and Telnet processes are running and start otherwise.
 *
 * @param boolean $restart_mode Restart the processes if running.
 *
 * @return void
 */
function cron_task_start_gotty(bool $restart_mode=true)
{
    global $config;

    include_once $config['homedir'].'/include/functions_config.php';

    $gotty_ssh_enabled = (bool) $config['gotty_ssh_enabled'];
    $gotty_telnet_enabled = (bool) $config['gotty_telnet_enabled'];

    // Check prev process running and kill it (only if port changed in setup params).
    if (empty($config['restart_gotty_next_cron_port']) === false) {
        $prevProcessRunning = shell_exec("pgrep -af 'pandora_gotty.*-p ".$config['restart_gotty_next_cron_port']."' | grep -v 'pgrep'");

        if (empty($prevProcessRunning) === false) {
            shell_exec("pkill -f 'pandora_gotty.*-p ".$config['restart_gotty_next_cron_port']."'");
        }

        config_update_value('restart_gotty_next_cron_port', '');
    }

    // Check if gotty is running on the configured port.
    $processRunning = shell_exec("pgrep -af 'pandora_gotty.*-p ".$config['gotty_port']."' | grep -v 'pgrep'");

    $start_proc = true;

    // If both methods are disabled, do not start process.
    if ($gotty_ssh_enabled === false && $gotty_telnet_enabled === false) {
        $start_proc = false;
    }

    if (empty($processRunning) === false) {
        // Process is running.
        if ($restart_mode === true || $start_proc === false) {
            // Stop the process for restarting or in case GoTTY method is disabled in this iteration.
            shell_exec("pkill -f 'pandora_gotty.*-p ".$config['gotty_port']."'");
        } else {
            // Prevent starting if already running and must not be restarted or terminated.
            return;
        }
    }

    if ($start_proc === true && file_exists('/usr/bin/pandora_gotty') === true) {
        $logFilePath = $config['homedir'].'/log/gotty_cron_tmp.log';
        shell_exec('touch '.$logFilePath);

        // Start gotty process and capture the output.
        $command = '/usr/bin/nohup /usr/bin/pandora_gotty --config /etc/pandora_gotty/pandora_gotty.conf -p '.$config['gotty_port'].' /usr/bin/pandora_gotty_exec > '.$logFilePath.' 2>&1 &';
        shell_exec($command);
    } else {
        return;
    }

    $hash_read = false;

    // Maximum wait time to read asynchronously the output of the executed commands (seconds).
    $maxWaitTime = 10;

    // Wait for content to appear in the log file.
    $startTime = time();

    // Workaround to wait until process inputs data in the log.
    while ((time() - $startTime) < $maxWaitTime) {
        if ($start_proc === true) {
            // Read command output.
            $log_content = @file_get_contents($logFilePath);
        }

        if ($start_proc === true
            && !empty($log_content)
            && $hash_read === false
        ) {
            // Extract the URL from the output.
            if (preg_match('/.*?HTTP server is listening at:\s+(\S+)/', $log_content, $matches)) {
                $url = $matches[1];

                // Extract the hash.
                $parts = explode('/', $url);
                $hash = array_slice($parts, -2, 1)[0];

                config_update_value('gotty_connection_hash', $hash);
                $hash_read = true;
            }

            unlink($logFilePath);
        }

        if ($start_proc === false || $hash_read === true) {
            // As soon as the read has completed, the timing loop will terminate.
            break;
        }

        // Sleep for a short interval before checking again.
        usleep(100000);
    }
}
