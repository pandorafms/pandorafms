<?php

if (!is_ajax()) {
    return;
}

global $config;

require_once $config['homedir'].'/vendor/autoload.php';

use Models\VisualConsole\Container as VisualConsole;

$visualConsoleId = (int) get_parameter('visualConsoleId');
$getVisualConsole = (bool) get_parameter('getVisualConsole');
$getVisualConsoleItems = (bool) get_parameter('getVisualConsoleItems');

ob_clean();

if ($getVisualConsole === true) {
    echo VisualConsole::fromDB(['id' => $visualConsoleId]);
} else if ($getVisualConsoleItems === true) {
    echo '['.implode(VisualConsole::getItemsFromDB($visualConsoleId), ',').']';
}

exit;
