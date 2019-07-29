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
$getVisualConsoleItem = (bool) get_parameter('getVisualConsoleItem');
$removeVisualConsoleItem = (bool) get_parameter('removeVisualConsoleItem');

ob_clean();

// Retrieve the visual console.
$visualConsole = VisualConsole::fromDB(['id' => $visualConsoleId]);
$visualConsoleData = $visualConsole->toArray();
$vcGroupId = $visualConsoleData['groupId'];

// ACL.
$aclRead = check_acl($config['id_user'], $vcGroupId, 'VR');
$aclWrite = check_acl($config['id_user'], $vcGroupId, 'VW');
$aclManage = check_acl($config['id_user'], $vcGroupId, 'VM');

if (!$aclRead && !$aclWrite && !$aclManage) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access visual console without group access'
    );
    http_response_code(403);
    return;
}

if ($getVisualConsole === true) {
    echo $visualConsole;
    return;
} else if ($getVisualConsoleItems === true) {
    // Check groups can access user.
    $aclUserGroups = [];
    if (!users_can_manage_group_all('AR')) {
        $aclUserGroups = array_keys(users_get_groups(false, 'AR'));
    }

    $vcItems = VisualConsole::getItemsFromDB($visualConsoleId, $aclUserGroups);
    echo '['.implode($vcItems, ',').']';
    return;
} else if ($getVisualConsoleItem === true
    || $updateVisualConsoleItem === true
) {
    $itemId = (int) get_parameter('visualConsoleItemId');

    try {
        $item = VisualConsole::getItemFromDB($itemId);
    } catch (Throwable $e) {
        // Bad params.
        http_response_code(409);
        return;
    }

    $itemData = $item->toArray();
    $itemType = $itemData['type'];
    $itemAclGroupId = $itemData['aclGroupId'];

    // ACL.
    $aclRead = check_acl($config['id_user'], $itemAclGroupId, 'VR');
    $aclWrite = check_acl($config['id_user'], $itemAclGroupId, 'VW');
    $aclManage = check_acl($config['id_user'], $itemAclGroupId, 'VM');

    if (!$aclRead && !$aclWrite && !$aclManage) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access visual console without group access'
        );
        http_response_code(403);
        return;
    }

    // Check also the group Id for the group item.
    if ($itemType === GROUP_ITEM) {
        $itemGroupId = $itemData['aclGroupId'];
        // ACL.
        $aclRead = check_acl($config['id_user'], $itemGroupId, 'VR');
        $aclWrite = check_acl($config['id_user'], $itemGroupId, 'VW');
        $aclManage = check_acl($config['id_user'], $itemGroupId, 'VM');

        if (!$aclRead && !$aclWrite && !$aclManage) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access visual console without group access'
            );
            http_response_code(403);
            return;
        }
    }

    if ($getVisualConsoleItem === true) {
        echo $item;
        return;
    } else if ($updateVisualConsoleItem === true) {
        $data = get_parameter('data');
        $result = $item->save($data);

        echo $item;
        return;
    }
} else if ($removeVisualConsoleItem === true) {
    $itemId = (int) get_parameter('visualConsoleItemId');

    try {
        $item = VisualConsole::getItemFromDB($itemId);
    } catch (\Throwable $th) {
        // There is no item in the database.
        echo true;
        return;
    }

    $data = get_parameter('data');
    $class = VisualConsole::getItemClass((int) $data['type']);
    $result = $class::delete($itemId);
    echo $result;
    return;
}

exit;
