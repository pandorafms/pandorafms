<?php

require_once 'include/config.php';
require_once 'include/auth/mysql.php';
require_once 'include/functions.php';
require_once 'include/functions_db.php';

if (enterprise_installed() === true) {
    return;
}
hd("ENTRA EN CRON OPEN", true);
// Load classes.
require_once 'include/class/DiscoveryConsoleTask.php';
require_once 'include/class/ConsoleSupervisor.php';

global $config;

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