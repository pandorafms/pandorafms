<?php
/**
 * Sample file to perform offline updates.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
use UpdateManager\UI\Manager;

$umc = new Manager(
    public_url: '/',
    settings: [
        'homedir'             => './test',
        'allowOfflinePatches' => true,
        'endpoint'            => '/pandoraupdate7',
    ],
    mode: Manager::MODE_OFFLINE,
    composer: false,
);
$umc->run();
