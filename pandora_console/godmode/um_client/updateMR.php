<?php

if ($argv === null) {
    header('Location: /');
    exit(0);
}

chdir(__DIR__.'/../../');

// UMC dependencies.
require_once 'vendor/autoload.php';
// Config file.
$cnf_file = 'include/config.php';

if (file_exists($cnf_file) === false) {
    exit(0);
}

ini_set('display_errors', 1);

require_once $cnf_file;

use PandoraFMS\Core\Config;
use PandoraFMS\Core\DBMaintainer;

global $config;

error_reporting(E_ALL ^ E_NOTICE);

try {
    $historical_dbh = null;
    if (isset($config['history_db_enabled']) === true
        && (bool) $config['history_db_enabled'] === true
    ) {
        $dbm = new DBMaintainer(
            [
                'host' => $config['history_db_host'],
                'port' => $config['history_db_port'],
                'name' => $config['history_db_name'],
                'user' => $config['history_db_user'],
                'pass' => $config['history_db_pass'],
            ]
        );

        $historical_dbh = $dbm->getDBH();
    }

    $current_mr = db_get_value('value', 'tconfig', 'token', 'MR');

    echo 'MR: '.$current_mr."\n";

    if ((bool) $historical_dbh === true) {
        echo 'current historyDB MR: '.Config::get('MR', 'unknown', true)."\n";
    }

    $umc = new UpdateManager\Client(
        [
            'homedir'      => $config['homedir'],
            'dbconnection' => $config['dbconnection'],
            'historydb'    => $historical_dbh,
            'MR'           => (int) $current_mr,
        ]
    );

    if ($umc->applyAllMRPending() !== true) {
        echo ($umc->getMR() + 1).': '.$umc->getLastError();
    }

    $current_mr = $umc->getMR();

    echo 'current MR: '.$current_mr."\n";

    if ((bool) $historical_dbh === true) {
        echo 'current historyDB MR: '.Config::get('MR', 'unknown', true)."\n";
    }
} catch (Exception $e) {
    echo $e->getMessage().' in '.$e->getFile().':'.$e->getLine()."\n";
}
