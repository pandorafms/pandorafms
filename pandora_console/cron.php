<?php

require_once 'include/config.php';
require_once 'include/auth/mysql.php';
require_once 'include/functions.php';
require_once 'include/functions_db.php';

global $config;

if ((bool) $config['enterprise_installed'] === true) {
    return;
}

// Load classes.
require_once 'include/class/DiscoveryConsoleTask.php';
require_once 'include/class/ConsoleSupervisor.php';

db_process_sql_update(
    'tconfig',
    ['value' => get_system_time()],
    ['token' => 'cron_last_run']
);

$tasks = new DiscoveryConsoleTask();

$tasks->run();

if (is_reporting_console_node() === true) {
    $supervisor = new ConsoleSupervisor();
    $supervisor->run();
}
