<?php
/**
 * Tree view.
 *
 * @category   Tree
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

if (is_ajax() === true) {
    global $config;

    // Login check.
    check_login();

    include_once $config['homedir'].'/include/class/Tree.class.php';
    include_once $config['homedir'].'/include/class/TreeOS.class.php';
    include_once $config['homedir'].'/include/class/TreeModuleGroup.class.php';
    include_once $config['homedir'].'/include/class/TreeModule.class.php';
    include_once $config['homedir'].'/include/class/TreeTag.class.php';
    include_once $config['homedir'].'/include/class/TreeGroup.class.php';
    include_once $config['homedir'].'/include/class/TreeService.class.php';
    include_once $config['homedir'].'/include/class/TreeGroupEdition.class.php';
    enterprise_include_once('include/class/TreeIPAMSupernet.class.php');
    enterprise_include_once('include/class/TreePolicies.class.php');
    enterprise_include_once('include/class/TreeGroupMeta.class.php');
    include_once $config['homedir'].'/include/functions_reporting.php';
    include_once $config['homedir'].'/include/functions_os.php';

    $getChildren = (bool) get_parameter('getChildren', 0);
    $getGroupStatus = (bool) get_parameter('getGroupStatus', 0);
    $getDetail = (bool) get_parameter('getDetail');

    if ($getChildren === true) {
        $type = get_parameter('type', 'group');
        $rootType = get_parameter('rootType', '');
        $id = get_parameter('id', -1);
        $rootID = get_parameter('rootID', -1);
        $serverID = get_parameter('serverID', false);
        $metaID = (int) get_parameter('metaID', 0);
        $childrenMethod = get_parameter('childrenMethod', 'on_demand');

        $default_filters = [
            'searchAgent'  => '',
            'statusAgent'  => AGENT_STATUS_ALL,
            'searchModule' => '',
            'statusModule' => AGENT_MODULE_STATUS_ALL,
            'groupID'      => 0,
            'tagID'        => 0,
        ];
        $filter = get_parameter('filter', $default_filters);

        $agent_a = check_acl($config['id_user'], 0, 'AR');
        $agent_w = check_acl($config['id_user'], 0, 'AW');
        $access = ($agent_a === true) ? 'AR' : (($agent_w === true) ? 'AW' : 'AR');
        $switch_type = (empty($rootType) === false) ? $rootType : $type;
        switch ($switch_type) {
            case 'os':
                $tree = new TreeOS(
                    $type,
                    $rootType,
                    $id,
                    $rootID,
                    $serverID,
                    $childrenMethod,
                    $access
                );
            break;

            case 'module_group':
                $tree = new TreeModuleGroup(
                    $type,
                    $rootType,
                    $id,
                    $rootID,
                    $serverID,
                    $childrenMethod,
                    $access
                );
            break;

            case 'module':
                $tree = new TreeModule(
                    $type,
                    $rootType,
                    $id,
                    $rootID,
                    $serverID,
                    $childrenMethod,
                    $access
                );
            break;

            case 'tag':
                $tree = new TreeTag(
                    $type,
                    $rootType,
                    $id,
                    $rootID,
                    $serverID,
                    $childrenMethod,
                    $access
                );
            break;

            case 'group':
                if (is_metaconsole() === true) {
                    if (class_exists('TreeGroupMeta') === false) {
                        break;
                    }

                    $tree = new TreeGroupMeta(
                        $type,
                        $rootType,
                        $id,
                        $rootID,
                        $serverID,
                        $childrenMethod,
                        $access
                    );
                } else {
                    $tree = new TreeGroup(
                        $type,
                        $rootType,
                        $id,
                        $rootID,
                        $serverID,
                        $childrenMethod,
                        $access
                    );
                }
            break;

            case 'policies':
                if (class_exists('TreePolicies') === false) {
                    break;
                }

                $tree = new TreePolicies(
                    $type,
                    $rootType,
                    $id,
                    $rootID,
                    $serverID,
                    $childrenMethod,
                    $access
                );
            break;

            case 'group_edition':
                $tree = new TreeGroupEdition(
                    $type,
                    $rootType,
                    $id,
                    $rootID,
                    $serverID,
                    $childrenMethod,
                    $access
                );
            break;

            case 'services':
                $tree = new TreeService(
                    $type,
                    $rootType,
                    $id,
                    $rootID,
                    $serverID,
                    $childrenMethod,
                    $access,
                    $metaID
                );
            break;

            case 'IPAM_supernets':
                $tree = new TreeIPAMSupernet(
                    $type,
                    $rootType,
                    $id,
                    $rootID,
                    $serverID,
                    $childrenMethod,
                    $access
                );
            break;

            default:
                // No error handler.
            return;
        }

        $tree->setFilter($filter);
        ob_clean();

        echo json_encode(['success' => 1, 'tree' => $tree->getArray()]);
        return;
    }

    if ($getDetail === true) {
        include_once $config['homedir'].'/include/functions_treeview.php';

        $id = (int) get_parameter('id');
        $type = (string) get_parameter('type');

        $server = [];
        if (is_metaconsole() === true) {
            $server_id = (int) get_parameter('serverID');
            $server = metaconsole_get_servers($server_id);
        }

        ob_clean();

        echo '<style type="text/css">';
        include_once __DIR__.'/../styles/progress.css';
        echo '</style>';

        echo '<div class="left_align backgrund_primary_important">';
        if (empty($id) === false && empty($type) === false) {
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
                    // Nothing.
                break;
            }
        }

        echo '<br></div>';

        return;
    }

    return;
}
