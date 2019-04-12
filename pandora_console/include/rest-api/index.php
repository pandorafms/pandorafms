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
    $visualConsole = VisualConsole::fromDB(['id' => $visualConsoleId]);
    $visualConsoleData = $visualConsole->toArray();
    $groupId = $visualConsoleData['groupId'];

    // ACL.
    $aclRead = check_acl($config['id_user'], $groupId, 'VR');
    $aclWrite = check_acl($config['id_user'], $groupId, 'VW');
    $aclManage = check_acl($config['id_user'], $groupId, 'VM');

    if (!$aclRead && !$aclWrite && !$aclManage) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access visual console without group access'
        );
        exit;
    }

    echo $visualConsole;
} else if ($getVisualConsoleItems === true) {
    echo '['.implode(VisualConsole::getItemsFromDB($visualConsoleId), ',').']';
}

exit;
