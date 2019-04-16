<?php

global $config;

if (!is_ajax()) {
    return;
}

require_once $config['homedir'].'/vendor/autoload.php';

use Models\VisualConsole\Container as VisualConsole;

$visualConsoleId = (int) get_parameter('visualConsoleId');
$getVisualConsole = (bool) get_parameter('getVisualConsole');
$getVisualConsoleItems = (bool) get_parameter('getVisualConsoleItems');

// Check groups can access user.
$aclUserGroups = [];
if (!users_can_manage_group_all('AR')) {
    $aclUserGroups = array_keys(users_get_groups(false, 'AR'));
}

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
    $vcItems = VisualConsole::getItemsFromDB($visualConsoleId, $aclUserGroups);
    echo '['.implode($vcItems, ',').']';
}

exit;
