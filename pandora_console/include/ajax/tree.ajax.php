<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Only accesible by ajax
if (is_ajax()) {
    global $config;

    enterprise_include_once('include/functions_dashboard.php');

    $public_hash = get_parameter('hash', 0);

    // Try to authenticate by hash on public dashboards
    if ($public_hash == 0) {
        // Login check
        check_login();
    } else {
        $validate_hash = enterprise_hook(
            'dasboard_validate_public_hash',
            [
                $public_hash,
                'tree_view',
            ]
        );
        if ($validate_hash === false || $validate_hash === ENTERPRISE_NOT_HOOK) {
            db_pandora_audit('Invalid public hash', 'Trying to access report builder');
            include 'general/noaccess.php';
            exit;
        }
    }

    include_once $config['homedir'].'/include/class/Tree.class.php';
    include_once $config['homedir'].'/include/class/TreeOS.class.php';
    include_once $config['homedir'].'/include/class/TreeModuleGroup.class.php';
    include_once $config['homedir'].'/include/class/TreeModule.class.php';
    include_once $config['homedir'].'/include/class/TreeTag.class.php';
    include_once $config['homedir'].'/include/class/TreeGroup.class.php';
    include_once $config['homedir'].'/include/class/TreeService.class.php';
    include_once $config['homedir'].'/include/class/TreeGroupEdition.class.php';
    enterprise_include_once('include/class/TreePolicies.class.php');
    enterprise_include_once('include/class/TreeGroupMeta.class.php');
    include_once $config['homedir'].'/include/functions_reporting.php';
    include_once $config['homedir'].'/include/functions_os.php';

    $getChildren = (bool) get_parameter('getChildren', 0);
    $getGroupStatus = (bool) get_parameter('getGroupStatus', 0);
    $getDetail = (bool) get_parameter('getDetail');

    if ($getChildren) {
        $type = get_parameter('type', 'group');
        $rootType = get_parameter('rootType', '');
        $id = get_parameter('id', -1);
        $rootID = get_parameter('rootID', -1);
        $serverID = get_parameter('serverID', false);
        $childrenMethod = get_parameter('childrenMethod', 'on_demand');
        $hash = get_parameter('hash', false);
        if ($hash !== false) {
            enterprise_hook('dasboard_validate_public_hash', [$hash, 'tree_view']);
        }

        $default_filters = [
            'searchAgent'  => '',
            'statusAgent'  => AGENT_STATUS_ALL,
            'searchModule' => '',
            'statusModule' => -1,
            'groupID'      => 0,
            'tagID'        => 0,
        ];
        $filter = get_parameter('filter', $default_filters);

        $agent_a = check_acl($config['id_user'], 0, 'AR');
        $agent_w = check_acl($config['id_user'], 0, 'AW');
        $access = ($agent_a == true) ? 'AR' : (($agent_w == true) ? 'AW' : 'AR');
        $switch_type = !empty($rootType) ? $rootType : $type;
        switch ($switch_type) {
            case 'os':
                $tree = new TreeOS($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);
            break;

            case 'module_group':
                $tree = new TreeModuleGroup($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);
            break;

            case 'module':
                $tree = new TreeModule($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);
            break;

            case 'tag':
                $tree = new TreeTag($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);
            break;

            case 'group':
                if (is_metaconsole()) {
                    if (!class_exists('TreeGroupMeta')) {
                        break;
                    }

                    $tree = new TreeGroupMeta($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);
                } else {
                    $tree = new TreeGroup($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);
                }
            break;

            case 'policies':
                if (!class_exists('TreePolicies')) {
                    break;
                }

                $tree = new TreePolicies($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);
            break;

            case 'group_edition':
                $tree = new TreeGroupEdition($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);
            break;

            case 'services':
                $tree = new TreeService($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);
            break;

            default:
                // FIXME. No error handler
            return;
        }

        $tree->setFilter($filter);
        ob_clean();

        echo json_encode(['success' => 1, 'tree' => $tree->getArray()]);
        return;
    }

    if ($getDetail) {
        include_once $config['homedir'].'/include/functions_treeview.php';

        $id = (int) get_parameter('id');
        $type = (string) get_parameter('type');

        $server = [];
        if (is_metaconsole()) {
            $server_id = (int) get_parameter('serverID');
            $server = metaconsole_get_servers($server_id);
        }

        ob_clean();

        echo '<style type="text/css">';
        include_once __DIR__.'/../styles/progress.css';
        echo '</style>';

        echo '<div class="left_align">';
        if (!empty($id) && !empty($type)) {
            switch ($type) {
                case 'agent':
                    treeview_printTable($id, $server, true);
                break;

                case 'module':
                    treeview_printModuleTable($id, $server, true);
                break;

                case 'alert':
                    treeview_printAlertsTable($id, $server, true);
                break;

                default:
                    // Nothing
                break;
            }
        }

        echo '<br></div>';

        return;
    }

    return;
}
