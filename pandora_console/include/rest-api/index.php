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
$autocompleteAgentsVisualConsole = (bool) get_parameter('autocompleteAgentsVisualConsole');
$autocompleteModuleVisualConsole = (bool) get_parameter('autocompleteModuleVisualConsole');

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
        if ($data) {
            $data['id'] = $itemId;
            $result = $item->save($data);

            echo $item;
        }

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
} else if ($autocompleteAgentsVisualConsole) {
    $params = (array) get_parameter('data', []);

    $string = $params['value'];

    // TODO: ACL.
    $id_group = (int) get_parameter('id_group', -1);

    if ($id_group != -1) {
        if ($id_group == 0) {
            $user_groups = users_get_groups(
                $config['id_user'],
                'AR',
                true
            );
            $filter['id_grupo'] = array_keys($user_groups);
        } else {
            $filter['id_grupo'] = $id_group;
        }
    }

    $filter = [];
    $filter['disabled'] = 0;

    $filter[] = sprintf(
        '(alias LIKE "%%%s%%")
        OR (alias NOT LIKE "%%%s%%"
            AND nombre COLLATE utf8_general_ci LIKE "%%%s%%")
        OR (alias NOT LIKE "%%%s%%"
            AND nombre COLLATE utf8_general_ci NOT LIKE "%%%s%%"
            AND direccion LIKE "%%%s%%")
        OR (alias NOT LIKE "%%%s%%"
            AND nombre COLLATE utf8_general_ci NOT LIKE "%%%s%%"
            AND direccion NOT LIKE "%%%s%%"
            AND comentarios LIKE "%%%s%%"
        )',
        $string,
        $string,
        $string,
        $string,
        $string,
        $string,
        $string,
        $string,
        $string,
        $string
    );

    $data = [];
    if (is_metaconsole() === true) {
        enterprise_include_once('include/functions_metaconsole.php');
        $metaconsole_connections = metaconsole_get_connection_names();
        // For all nodes.
        if (isset($metaconsole_connections) === true
            && is_array($metaconsole_connections) === true
        ) {
            foreach ($metaconsole_connections as $metaconsole) {
                // Get server connection data.
                $server_data = metaconsole_get_connection($metaconsole);

                // Establishes connection.
                if (metaconsole_load_external_db($server_data) !== NOERR) {
                    continue;
                }

                $agents = agents_get_agents(
                    $filter,
                    [
                        'id_agente',
                        'nombre',
                        'direccion',
                        'alias',
                    ]
                );

                if (isset($agents) === true && is_array($agents) === true) {
                    foreach ($agents as $agent) {
                        $data[] = [
                            'id'              => $agent['id_agente'],
                            'name'            => io_safe_output(
                                $agent['nombre']
                            ),
                            'alias'           => io_safe_output(
                                $agent['alias']
                            ),
                            'ip'              => io_safe_output(
                                $agent['direccion']
                            ),
                            'filter'          => 'alias',
                            'metaconsoleId'   => $server_data['id'],
                            'metaconsoleName' => $metaconsole,
                        ];
                    }
                }

                metaconsole_restore_db();
            }
        }
    } else {
        $agents = agents_get_agents(
            $filter_alias,
            [
                'id_agente',
                'nombre',
                'direccion',
                'alias',
            ]
        );
        if (isset($agents) === true && is_array($agents) === true) {
            foreach ($agents as $agent) {
                $data[] = [
                    'id'     => $agent['id_agente'],
                    'name'   => io_safe_output($agent['nombre']),
                    'alias'  => io_safe_output($agent['alias']),
                    'ip'     => io_safe_output($agent['direccion']),
                    'filter' => 'alias',
                ];
            }
        }
    }

    echo json_encode($data);
    return;
} else if ($autocompleteModuleVisualConsole) {
    $data = (array) get_parameter('data', []);

    $result = [];
    if (is_metaconsole()) {
        enterprise_include_once('include/functions_metaconsole.php');
        $connection = metaconsole_get_connection_by_id($data['metaconsoleId']);
        if (metaconsole_connect($connection) !== NOERR) {
            echo json_encode($result);
            return;
        }
    }

    $agent_modules = agents_get_modules(
        $data['agentId']
    );

    if (is_metaconsole()) {
        // Restore db connection.
        metaconsole_restore_db();
    }

    if (isset($agent_modules) === true && is_array($agent_modules) === true) {
        $result = array_map(
            function ($id) use ($agent_modules) {
                return [
                    'moduleId'   => $id,
                    'moduleName' => io_safe_output($agent_modules[$id]),
                ];
            },
            array_keys($agent_modules)
        );
    }

    echo json_encode($result);
    return;
}

exit;
