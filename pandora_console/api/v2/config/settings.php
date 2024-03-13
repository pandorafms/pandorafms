<?php

require __DIR__.'/../../../include/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', $config['homedir'].'/log/console.log');

return [];
