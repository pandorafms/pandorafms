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
$copyVisualConsoleItem = (bool) get_parameter('copyVisualConsoleItem');
$getGroupsVisualConsoleItem = (bool) get_parameter('getGroupsVisualConsoleItem');
$getAllVisualConsole = (bool) get_parameter('getAllVisualConsole');
$getImagesVisualConsole = (bool) get_parameter('getImagesVisualConsole');

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
        http_response_code(400);
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
        $itemGroupId = $itemData['groupId'];
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
        $data['id'] = $itemId;
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
        http_response_code(404);
        return;
    }

    $itemData = $item->toArray();
    $itemAclGroupId = $itemData['aclGroupId'];

    $aclWrite = check_acl($config['id_user'], $itemAclGroupId, 'VW');
    $aclManage = check_acl($config['id_user'], $itemAclGroupId, 'VM');

    // ACL.
    if (!$aclWrite && !$aclManage) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to delete visual console item without group access'
        );
        http_response_code(403);
        return;
    }

    $data = get_parameter('data');
    $result = $item::delete($itemId);
    echo $result;
    return;
} else if ($copyVisualConsoleItem === true) {
    $itemId = (int) get_parameter('visualConsoleItemId');

    // Get a copy of the item.
    $item = VisualConsole::getItemFromDB($itemId);
    $data = $item->toArray();
    $data['id_layout'] = $visualConsoleId;
    $data['x'] = ($data['x'] + 20);
    $data['y'] = ($data['y'] + 20);
    unset($data['id']);

    $class = VisualConsole::getItemClass((int) $data['type']);
    try {
        // Save the new item.
        $result = $class::save($data);
    } catch (\Throwable $th) {
        // There is no item in the database.
        echo false;
        return;
    }

    echo $result;
    return;
} else if ($getGroupsVisualConsoleItem === true) {
    $data = users_get_groups_for_select(
        $config['id_user'],
        'AR',
        true,
        true
    );

    $result = array_map(
        function ($id) use ($data) {
            return [
                'value' => $id,
                'text'  => $data[$id],
            ];
        },
        array_keys($data)
    );

    echo json_encode($result);
    return;
} else if ($getAllVisualConsole === true) {
    // Extract all VC except own.
    $result = db_get_all_rows_filter(
        'tlayout',
        'id != '.(int) $visualConsole,
        [
            'id',
            'name',
        ]
    );

    // Extract all VC for each node.
    if (is_metaconsole() === true) {
        enterprise_include_once('include/functions_metaconsole.php');
        $meta_servers = metaconsole_get_servers();
        foreach ($meta_servers as $server) {
            if (metaconsole_load_external_db($server) !== NOERR) {
                metaconsole_restore_db();
                continue;
            }

            $node_visual_maps = db_get_all_rows_filter(
                'tlayout',
                [],
                [
                    'id',
                    'name',
                ]
            );

            if (isset($node_visual_maps) === true
                && is_array($node_visual_maps) === true
            ) {
                foreach ($node_visual_maps as $node_visual_map) {
                    // Add nodeID.
                    $node_visual_map['nodeId'] = (int) $server['id'];

                    // Name = vc_name - (node).
                    $node_visual_map['name'] = $node_visual_map['name'];
                    $node_visual_map['name'] .= ' - (';
                    $node_visual_map['name'] .= $server['server_name'].')';

                    $result[] = $node_visual_map;
                }
            }

            metaconsole_restore_db();
        }
    }

    echo json_encode(io_safe_output($result));
    return;
} else if ($getImagesVisualConsole) {
    $result = [];

    // Extract images.
    $all_images = list_files(
        $config['homedir'].'/images/console/icons/',
        'png',
        1,
        0
    );

    if (isset($all_images) === true && is_array($all_images) === true) {
        $base_url = ui_get_full_url(
            '/images/console/icons/',
            false,
            false,
            false
        );

        foreach ($all_images as $image_file) {
            $image_file = substr($image_file, 0, (strlen($image_file) - 4));

            if (strpos($image_file, '_bad') !== false) {
                continue;
            }

            if (strpos($image_file, '_ok') !== false) {
                continue;
            }

            if (strpos($image_file, '_warning') !== false) {
                continue;
            }

            $result[] = [
                'name' => $image_file,
                'src'  => $base_url.$image_file,
            ];
        }
    }

    echo json_encode(io_safe_output($result));
    return;
}

exit;
