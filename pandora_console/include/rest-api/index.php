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
$updateVisualConsoleItem = (bool) get_parameter('updateVisualConsoleItem');

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
} else if ($updateVisualConsoleItem === true) {
    $visualConsoleId = (integer) get_parameter('visualConsoleId');
    $visualConsoleItemId = (integer) get_parameter('visualConsoleItemId');
    $data = get_parameter('data');

    $class = VisualConsole::getItemClass($data['type']);

    $item_data = [];
    $item_data['id'] = $visualConsoleItemId;
    $item_data['id_layout'] = $visualConsoleId;

    $item = $class::fromDB($item_data);
    $result = $item->save($data);

    echo json_encode($result);
}

exit;
