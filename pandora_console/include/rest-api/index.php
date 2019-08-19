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


    // if ($search_agents && (!is_metaconsole() || $force_local)) {
        // $id_agent = (int) get_parameter('id_agent');
       $string = $params['value'];
        $id_group = (int) get_parameter('id_group', -1);

        $filter = [];

    if ($id_group != -1) {
        if ($id_group == 0) {
            $user_groups = users_get_groups($config['id_user'], 'AR', true);
            $filter['id_grupo'] = array_keys($user_groups);
        } else {
            $filter['id_grupo'] = $id_group;
        }
    }

    $filter['disabled'] = 0;

    $data = [];
    // Get agents for only the alias.
    $filter_alias = $filter;
    $filter_alias[] = '(alias LIKE "%'.$string.'%")';

    $agents = agents_get_agents($filter_alias, ['id_agente', 'nombre', 'direccion', 'alias']);
    if ($agents !== false) {
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

    // Get agents for only the name.
    $filter_alias = $filter;
    $filter_agents[] = '(alias NOT LIKE "%'.$string.'%" AND nombre COLLATE utf8_general_ci LIKE "%'.$string.'%")';

    $agents = agents_get_agents(
        $filter_agents,
        [
            'id_agente',
            'nombre',
            'direccion',
            'alias',
        ]
    );
    if ($agents !== false) {
        foreach ($agents as $agent) {
            $data[] = [
                'id'     => $agent['id_agente'],
                'name'   => io_safe_output($agent['nombre']),
                'alias'  => io_safe_output($agent['alias']),
                'ip'     => io_safe_output($agent['direccion']),
                'filter' => 'agent',
            ];
        }
    }

    // Get agents for only the address.
    $filter_alias = $filter;
    $filter_address[] = '(alias NOT LIKE "%'.$string.'%" AND nombre COLLATE utf8_general_ci NOT LIKE "%'.$string.'%" AND direccion LIKE "%'.$string.'%")';

    $agents = agents_get_agents($filter_address, ['id_agente', 'nombre', 'direccion', 'alias']);
    if ($agents !== false) {
        foreach ($agents as $agent) {
            $data[] = [
                'id'     => $agent['id_agente'],
                'name'   => io_safe_output($agent['nombre']),
                'alias'  => io_safe_output($agent['alias']),
                'ip'     => io_safe_output($agent['direccion']),
                'filter' => 'address',
            ];
        }
    }

    // Get agents for only the description.
    $filter_alias = $filter;
    $filter_description[] = '(alias NOT LIKE "%'.$string.'%" AND nombre COLLATE utf8_general_ci NOT LIKE "%'.$string.'%" AND direccion NOT LIKE "%'.$string.'%" AND comentarios LIKE "%'.$string.'%")';

    $agents = agents_get_agents($filter_description, ['id_agente', 'nombre', 'direccion', 'alias']);
    if ($agents !== false) {
        foreach ($agents as $agent) {
            $data[] = [
                'id'     => $agent['id_agente'],
                'name'   => io_safe_output($agent['nombre']),
                'alias'  => io_safe_output($agent['alias']),
                'ip'     => io_safe_output($agent['direccion']),
                'filter' => 'description',
            ];
        }
    }

        echo json_encode($data);
        return;
    /*
        } else if ($search_agents && is_metaconsole()) {
        $id_agent = (int) get_parameter('id_agent');
        $string = (string) get_parameter('q');
        // q is what autocomplete plugin gives
        $id_group = (int) get_parameter('id_group', -1);
        $addedItems = html_entity_decode((string) get_parameter('add'));
        $addedItems = json_decode($addedItems);
        $all = (string) get_parameter('all', 'all');

        if ($addedItems != null) {
            foreach ($addedItems as $item) {
                echo $item."|\n";
            }
        }

        $data = [];

        $fields = [
            'id_tagente AS id_agente',
            'nombre',
            'alias',
            'direccion',
            'id_tmetaconsole_setup AS id_server',
        ];

        $filter = [];

        if ($id_group != -1) {
            if ($id_group == 0) {
                $user_groups = users_get_groups($config['id_user'], 'AR', true);

                $filter['id_grupo'] = array_keys($user_groups);
            } else {
                $filter['id_grupo'] = $id_group;
            }
        }

        switch ($all) {
            case 'enabled':
                $filter['disabled'] = 0;
            break;
        }

        if (!empty($id_agent)) {
            $filter['id_agente'] = $id_agent;
        }

        if (!empty($string)) {
            // Get agents for only the alias.
            $filter_alias = $filter;
            switch ($config['dbtype']) {
                case 'mysql':
                    $filter_alias[] = '(alias COLLATE utf8_general_ci LIKE "%'.$string.'%")';
                break;

                case 'postgresql':
                    $filter_alias[] = '(alias LIKE \'%'.$string.'%\')';
                break;

                case 'oracle':
                    $filter_alias[] = '(UPPER(alias) LIKE UPPER(\'%'.$string.'%\'))';
                break;
            }

            $agents = db_get_all_rows_filter('tmetaconsole_agent', $filter_alias, $fields);
            if ($agents !== false) {
                foreach ($agents as $agent) {
                    $data[] = [
                        'id'        => $agent['id_agente'],
                        'name'      => io_safe_output($agent['nombre']),
                        'alias'     => io_safe_output($agent['alias']),
                        'ip'        => io_safe_output($agent['direccion']),
                        'id_server' => $agent['id_server'],
                        'filter'    => 'alias',
                    ];
                }
            }

            // Get agents for only the name.
            $filter_agents = $filter;
            switch ($config['dbtype']) {
                case 'mysql':
                    $filter_agents[] = '(alias COLLATE utf8_general_ci NOT LIKE "%'.$string.'%" AND nombre COLLATE utf8_general_ci LIKE "%'.$string.'%")';
                break;

                case 'postgresql':
                    $filter_agents[] = '(alias NOT LIKE \'%'.$string.'%\' AND nombre LIKE \'%'.$string.'%\')';
                break;

                case 'oracle':
                    $filter_agents[] = '(UPPER(alias) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(nombre) LIKE UPPER(\'%'.$string.'%\'))';
                break;
            }

            $agents = db_get_all_rows_filter('tmetaconsole_agent', $filter_agents, $fields);
            if ($agents !== false) {
                foreach ($agents as $agent) {
                    $data[] = [
                        'id'        => $agent['id_agente'],
                        'name'      => io_safe_output($agent['nombre']),
                        'alias'     => io_safe_output($agent['alias']),
                        'ip'        => io_safe_output($agent['direccion']),
                        'id_server' => $agent['id_server'],
                        'filter'    => 'agent',
                    ];
                }
            }

            // Get agents for only the address
            $filter_address = $filter;
            switch ($config['dbtype']) {
                case 'mysql':
                    $filter_address[] = '(alias COLLATE utf8_general_ci NOT LIKE "%'.$string.'%" AND nombre COLLATE utf8_general_ci NOT LIKE "%'.$string.'%" AND direccion LIKE "%'.$string.'%")';
                break;

                case 'postgresql':
                    $filter_address[] = '(alias NOT LIKE \'%'.$string.'%\' AND nombre NOT LIKE \'%'.$string.'%\' AND direccion LIKE \'%'.$string.'%\')';
                break;

                case 'oracle':
                    $filter_address[] = '(UPPER(alias) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(nombre) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(direccion) LIKE UPPER(\'%'.$string.'%\'))';
                break;
            }

            $agents = db_get_all_rows_filter('tmetaconsole_agent', $filter_address, $fields);
            if ($agents !== false) {
                foreach ($agents as $agent) {
                    $data[] = [
                        'id'        => $agent['id_agente'],
                        'name'      => io_safe_output($agent['nombre']),
                        'alias'     => io_safe_output($agent['alias']),
                        'ip'        => io_safe_output($agent['direccion']),
                        'id_server' => $agent['id_server'],
                        'filter'    => 'address',
                    ];
                }
            }

            // Get agents for only the description
            $filter_description = $filter;
            switch ($config['dbtype']) {
                case 'mysql':
                    $filter_description[] = '(alias COLLATE utf8_general_ci NOT LIKE "%'.$string.'%" AND nombre COLLATE utf8_general_ci NOT LIKE "%'.$string.'%" AND direccion NOT LIKE "%'.$string.'%" AND comentarios LIKE "%'.$string.'%")';
                break;

                case 'postgresql':
                    $filter_description[] = '(alias NOT LIKE \'%'.$string.'%\' AND nombre NOT LIKE \'%'.$string.'%\' AND direccion NOT LIKE \'%'.$string.'%\' AND comentarios LIKE \'%'.$string.'%\')';
                break;

                case 'oracle':
                    $filter_description[] = '(UPPER(alias) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(nombre) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(direccion) NOT LIKE UPPER(\'%'.$string.'%\') AND UPPER(comentarios) LIKE UPPER(\'%'.$string.'%\'))';
                break;
            }

            $agents = db_get_all_rows_filter('tmetaconsole_agent', $filter_description, $fields);
            if ($agents !== false) {
                foreach ($agents as $agent) {
                    $data[] = [
                        'id'        => $agent['id_agente'],
                        'name'      => io_safe_output($agent['nombre']),
                        'alias'     => io_safe_output($agent['alias']),
                        'ip'        => io_safe_output($agent['direccion']),
                        'id_server' => $agent['id_server'],
                        'filter'    => 'description',
                    ];
                }
            }
        }

        echo json_encode($data);
        return;
        }
    */
}

exit;
