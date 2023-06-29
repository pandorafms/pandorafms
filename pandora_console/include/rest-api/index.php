<?php

global $config;


if (!is_ajax()) {
    return;
}

require_once $config['homedir'].'/vendor/autoload.php';


// Require also some stuff from Pandora FMS.
enterprise_include('include/functions_metaconsole.php');

use Models\VisualConsole\Container as VisualConsole;
use Models\VisualConsole\View as Viewer;
use Models\VisualConsole\Item as Item;
use PandoraFMS\User;

$method = get_parameter('method');
if ($method) {
    $viewer = new Viewer();
    try {
        if (method_exists($viewer, $method) === true) {
            echo $viewer->{$method}();
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        return;
    }

    return;
}

$visualConsoleId = (int) get_parameter('visualConsoleId');
$getVisualConsole = (bool) get_parameter('getVisualConsole');
$getVisualConsoleItems = (bool) get_parameter('getVisualConsoleItems');
$doLogin = (bool) get_parameter('doLogin');
$updateVisualConsoleItem = (bool) get_parameter('updateVisualConsoleItem');
$createVisualConsoleItem = (bool) get_parameter('createVisualConsoleItem');
$getVisualConsoleItem = (bool) get_parameter('getVisualConsoleItem');
$removeVisualConsoleItem = (bool) get_parameter('removeVisualConsoleItem');
$copyVisualConsoleItem = (bool) get_parameter('copyVisualConsoleItem');
$getImagesVisualConsole = (bool) get_parameter('getImagesVisualConsole');
$createColorRangeVisualConsole = (bool) get_parameter(
    'createColorRangeVisualConsole'
);
$getTimeZoneVisualConsole = (bool) get_parameter('getTimeZoneVisualConsole');
$serviceListVisualConsole = (bool) get_parameter(
    'serviceListVisualConsole'
);

$loadtabs = (bool) get_parameter('loadtabs');

ob_clean();

if ($doLogin === true) {
    $id_user = get_parameter('id_user', '');
    $password = get_parameter('password', '');

    if (User::login(
        [
            'id_usuario' => $id_user,
            'password'   => $password,
        ]
    ) === true
    ) {
        $newGeneratedSecret = bin2hex(openssl_random_pseudo_bytes(15));

        $res_update = update_user(
            $id_user,
            ['auth_token_secret' => $newGeneratedSecret]
        );

        if ($res_update === false) {
            http_response_code(404);
            return;
        }

        echo json_encode(['auth_hash' => User::generatePublicHash($newGeneratedSecret)]);
    } else {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to login using invalid credentials with API Rest',
            $id_user
        );
        http_response_code(403);
        return;
    }

    return;
}


if ($visualConsoleId) {
    // Retrieve the visual console.
    $visualConsole = VisualConsole::fromDB(['id' => $visualConsoleId], $ratio);
    $visualConsoleData = $visualConsole->toArray();
    $vcGroupId = $visualConsoleData['groupId'];

    // ACL.
    $aclRead = check_acl($config['id_user'], $vcGroupId, 'VR');
    $aclWrite = check_acl($config['id_user'], $vcGroupId, 'VW');
    $aclManage = check_acl($config['id_user'], $vcGroupId, 'VM');

    if (!$aclRead && !$aclWrite && !$aclManage) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access visual console without group access'
        );
        http_response_code(403);
        return;
    }
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

    $size = get_parameter('size', []);

    $width = get_parameter('widthScreen', 0);

    $mode = get_parameter('mode', '');

    $ratio = 0;
    if (isset($size) === true
        && is_array($size) === true
        && empty($size) === false
    ) {
        $ratio = $visualConsole->adjustToViewport($size, $mode);
        $visualConsoleData = $visualConsole->toArray();
    }

    $widthRatio = 0;
    if ($visualConsoleData['autoAdjust'] === true && $width > 0) {
        $widthRatio = ($width / $visualConsoleData['width']);
    }

    $vcItems = VisualConsole::getItemsFromDB(
        $visualConsoleId,
        $aclUserGroups,
        $ratio,
        $widthRatio
    );

    echo '['.implode(',', $vcItems).']';
    return;
} else if ($getVisualConsoleItem === true
    || $updateVisualConsoleItem === true
) {
    $itemId = (int) get_parameter('visualConsoleItemId');

    try {
        $item = VisualConsole::getItemFromDB($itemId);
    } catch (Throwable $e) {
        // Bad params.
        echo $e->getMessage();
        if (__DEBUG === 1) {
            echo ' at '.$e->getFile().':'.$e->getLine();
        }

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
            AUDIT_LOG_ACL_VIOLATION,
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
                AUDIT_LOG_ACL_VIOLATION,
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

        if (isset($data) === true) {
            $data['id'] = $itemId;
            $data['id_layout'] = $visualConsoleId;
            $result = $item->save($data);

            echo $item;
        }

        return;
    }
} else if ($createVisualConsoleItem === true) {
    // TODO: ACL.
    $data = get_parameter('data');
    if ($data) {
        // Inserted data in new item.
        $class = VisualConsole::getItemClass((int) $data['type']);
        try {
            // Save the new item.
            $data['id_layout'] = $visualConsoleId;
            $result = $class::save($data);
        } catch (\Throwable $th) {
            // There is no item in the database.
            echo false;
            return;
        }

        // Extract data new item inserted.
        try {
            $item = VisualConsole::getItemFromDB($result);
        } catch (Throwable $e) {
            // Bad params.
            echo $e->getMessage();
            if (__DEBUG === 1) {
                echo ' at '.$e->getFile().':'.$e->getLine();
            }

            http_response_code(400);
            return;
        }

        echo $item;
    } else {
        echo false;
    }

    return;
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
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to delete visual console item without group access'
        );
        http_response_code(403);
        return;
    }

    $data = get_parameter('data');
    $result = $item->delete($itemId);
    echo $result;
    return;
} else if ($copyVisualConsoleItem === true) {
    $itemId = (int) get_parameter('visualConsoleItemId');

    // Get a copy of the item.
    $item = VisualConsole::getItemFromDB($itemId);
    $data = $item->toArray();
    $data['id_layout'] = $visualConsoleId;
    if ($data['type'] === LINE_ITEM
        || $data['type'] === NETWORK_LINK
    ) {
        $data['endX'] = ($data['endX'] + 20);
        $data['endY'] = ($data['endY'] + 20);
        $data['startX'] = ($data['startX'] + 20);
        $data['startY'] = ($data['startY'] + 20);
    } else {
        $data['x'] = ($data['x'] + 20);
        $data['y'] = ($data['y'] + 20);
    }

    unset($data['id']);

    $class = VisualConsole::getItemClass((int) $data['type']);
    try {
        // Save the new item.
        $result = $class::create($data);
    } catch (\Throwable $th) {
        // There is no item in the database.
        echo false;
        return;
    }

    echo $result;
    return;
} else if ($getImagesVisualConsole) {
    $img = get_parameter('nameImg', 'appliance');
    $only = (bool) get_parameter('only', 0);
    $count = Item::imagesElementsVC($img, $only);
    echo json_encode($count);
    return;
} else if ($createColorRangeVisualConsole) {
    $uniqId = \uniqid();
    $baseUrl = ui_get_full_url('/', false, false, false);
    $from = get_parameter('from', 0);
    $to = get_parameter('to', 0);
    $color = get_parameter('color', 0);

    $rangeFrom = [
        'name'   => 'rangeFrom[]',
        'type'   => 'number',
        'value'  => $from,
        'return' => true,
    ];

    $rangeTo = [
        'name'   => 'rangeTo[]',
        'type'   => 'number',
        'value'  => $to,
        'return' => true,
    ];

    $rangeColor = [
        'wrapper' => 'div',
        'name'    => 'rangeColor[]',
        'type'    => 'color',
        'value'   => $color,
        'return'  => true,
    ];

    $removeBtn = [
        'name'       => 'Remove',
        'label'      => __('Remove'),
        'type'       => 'button',
        'attributes' => [
            'mode' => 'mini secondary',
            'icon' => 'delete',
        ],
        'return'     => true,
        'script'     => 'removeColorRange("'.$uniqId.'")',
    ];

    $classRangeColor = 'interval-color-ranges flex-row flex-start w100p';
    $liRangeColor = '<li id="li-'.$uniqId.'" class="'.$classRangeColor.'">';
    $liRangeColor .= '<label>'.__('From').'</label>';
    $liRangeColor .= html_print_input($rangeFrom);
    $liRangeColor .= '<label>'.__('To').'</label>';
    $liRangeColor .= html_print_input($rangeTo);
    $liRangeColor .= '<label>'.__('Color').'</label>';
    $liRangeColor .= '<div>';
    $liRangeColor .= html_print_input($rangeColor);
    $liRangeColor .= '</div>';
    $liRangeColor .= html_print_input($removeBtn, 'div', true);
    $liRangeColor .= '<li>';

    echo $liRangeColor;
    return;
} else if ($getTimeZoneVisualConsole) {
    $zone = get_parameter('zone', 'Europe');
    $zones = Item::zonesVC($zone);
    echo json_encode($zones);
    return;
} else if ($serviceListVisualConsole) {
    if (!enterprise_installed()) {
        echo json_encode(false);
        return;
    }

    enterprise_include_once('include/functions_services.php');
    // Services list.
    $services = [];
    $services = enterprise_hook(
        'services_get_services',
        [
            false,
            [
                'id',
                'name',
            ],
        ]
    );

    echo io_safe_output(json_encode($services));
    return;
} else if ($loadtabs) {
    $viewer = new Viewer();
    echo $viewer->loadForm();

    return;
}

exit;
