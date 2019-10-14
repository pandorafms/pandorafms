#!/usr/bin/env php
<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/WSProxy.php';

$wsproxy = new WSProxy('0.0.0.0', '8081', '127.0.0.1', '8080');

try {
    echo "Server running \n";
    $wsproxy->run();
} catch (Exception $e) {
    $wsproxy->stdout($e->getMessage());
}
