<?php
/**
 * Update manager.
 *
 * @category   Update Manager
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
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

// Begin.
global $config;

check_login();
// The ajax is in include/ajax/update_manager.php.
if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/vendor/autoload.php';

$php_version = phpversion();
$php_version_array = explode('.', $php_version);
if ($php_version_array[0] < 7) {
    include_once 'general/php_message.php';
}

$tab = get_parameter('tab', 'online');

$buttons['setup'] = [
    'active' => ($tab === 'setup') ? true : false,
    'text'   => '<a href="'.ui_get_full_url(
        'index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=setup'
    ).'">'.html_print_image('images/gm_setup.png', true, ['title' => __('Setup'), 'class' => 'invert_filter']).'</a>',
];

$buttons['history'] = [
    'active' => ($tab === 'history') ? true : false,
    'text'   => '<a href="'.ui_get_full_url(
        'index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=histo'
    ).'ry">'.html_print_image('images/gm_db.png', true, ['title' => __('Journal'), 'class' => 'invert_filter']).'</a>',
];

$buttons['offline'] = [
    'active' => ($tab === 'offline') ? true : false,
    'text'   => '<a href="'.ui_get_full_url(
        'index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=offli'
    ).'ne">'.html_print_image('images/box.png', true, ['title' => __('Offline update'), 'class' => 'invert_filter']).'</a>',
];

$buttons['online'] = [
    'active' => ($tab === 'online') ? true : false,
    'text'   => '<a href="'.ui_get_full_url(
        'index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=onlin'
    ).'e">'.html_print_image('images/op_gis.png', true, ['title' => __('Online update'), 'class' => 'invert_filter']).'</a>',
];

switch ($tab) {
    case 'history':
        $title = __('Journal');
    break;

    case 'setup':
        $title = __('Setup');
    break;

    case 'offline':
        $title = __('Offline');
    break;

    case 'online':
    default:
        $title = __('Online');
    break;
}

ui_print_standard_header(
    $title,
    'images/gm_setup.png',
    false,
    '',
    true,
    $buttons,
    [
        [
            'link'  => '',
            'label' => 'Warp Update',
        ],
    ]
);

switch ($tab) {
    case 'history':
        include $config['homedir'].'/godmode/update_manager/update_manager.history.php';
    break;

    case 'setup':
        include $config['homedir'].'/godmode/update_manager/update_manager.setup.php';
    break;

    case 'offline':
        $mode = \UpdateManager\UI\Manager::MODE_OFFLINE;
        include $config['homedir'].'/godmode/um_client/index.php';
    break;

    case 'online':
    default:
        if (is_metaconsole() === false && has_metaconsole() === true) {
            $meta_puid = null;

            $server_id = $config['metaconsole_node_id'];
            $dbh = (object) $config['dbconnection'];

            // Connect to metaconsole.
            $result_code = metaconsole_load_external_db(
                [
                    'dbhost' => $config['replication_dbhost'],
                    'dbuser' => $config['replication_dbuser'],
                    'dbpass' => io_output_password($config['replication_dbpass']),
                    'dbname' => $config['replication_dbname'],
                ]
            );

            if ($result_code < 0) {
                break;
            }

            $value = db_get_value('value', 'tconfig', 'token', 'pandora_uid');

            $meta_puid = $value;

            // Return connection to node.
            metaconsole_restore_db();

            if ($meta_puid === false || $meta_puid === null) {
                ui_print_warning_message(__('Please register on metaconsole first.'));
                break;
            }
        }

        $mode = \UpdateManager\UI\Manager::MODE_ONLINE;
        include $config['homedir'].'/godmode/um_client/index.php';
    break;
}
