<?php

global $config;

ob_start();
if (file_exists($config['homedir'].'/'.ENTERPRISE_DIR.'/load_enterprise.php') === true) {
    $config['return_api_mode'] = true;
    include_once $config['homedir'].'/'.ENTERPRISE_DIR.'/load_enterprise.php';
    include_once $config['homedir'].'/'.ENTERPRISE_DIR.'/include/functions_login.php';
}

$error = ob_get_clean();
if (empty($error) === false) {
    throw new Exception($error);
}
