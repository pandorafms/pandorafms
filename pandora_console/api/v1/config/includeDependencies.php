<?php

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../include/config.php';
global $config;

if (file_exists($config['homedir'].'/'.ENTERPRISE_DIR.'/load_enterprise.php') === true) {
    include_once $config['homedir'].'/'.ENTERPRISE_DIR.'/load_enterprise.php';
    include_once $config['homedir'].'/'.ENTERPRISE_DIR.'/include/functions_login.php';
}
