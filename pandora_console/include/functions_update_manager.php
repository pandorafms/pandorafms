<?php
/**
 * Update manager client auxiliary functions.
 *
 * @category   Functions Update Manager
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

use UpdateManager\UI\Manager;

// Begin.
global $config;

require_once $config['homedir'].'/vendor/autoload.php';


/**
 * Verifies registration state.
 *
 * @return boolean Status.
 */
function update_manager_verify_registration()
{
    global $config;

    if (isset($config['pandora_uid']) === true
        && $config['pandora_uid'] != ''
        && $config['pandora_uid'] != 'OFFLINE'
    ) {
        // Verify with UpdateManager.
        return true;
    }

    return false;
}


/**
 * Retrieves current update from DB.
 *
 * @return string Current update.
 */
function update_manager_get_current_package()
{
    global $config;

    $current_update = ($config['current_package'] ?? $config['current_package_enterprise']);

    if ($current_update === null) {
        $current_update = db_get_value(
            db_escape_key_identifier('value'),
            'tupdate_settings',
            db_escape_key_identifier('key'),
            'current_package'
        );

        if ($current_update === false) {
            $current_update = 0;
            if (isset($config['current_package'])) {
                $current_update = $config['current_package'];
            }
        }
    }

    return $current_update;
}


/**
 * Check if a trial license is in use.
 *
 * @return boolean true if a trial license is in use, false otherwise.
 */
function update_manager_verify_trial()
{
    global $config;

    if (isset($config['license_licensed_to'])
        && strstr($config['license_licensed_to'], 'info@pandorafms.com') !== false
    ) {
        return true;
    }

    return false;
}


/**
 * Checks if there are packages available to be installed.
 *
 * @return boolean
 */
function update_manager_check_updates_available()
{
    $settings = update_manager_get_config_values();
    $umc = new \UpdateManager\Client($settings);

    $updates = $umc->listUpdates();
    if (is_array($updates) === false) {
        return false;
    }

    return (count($updates) > 0);
}


/**
 * Returns current update manager url.
 *
 * @return string
 */
function update_manager_get_url()
{
    global $config;

    $url_update_manager = $config['url_update_manager'];
    if ((bool) is_metaconsole() === false) {
        if ((bool) $config['node_metaconsole'] === true) {
            $url_update_manager = $config['metaconsole_base_url'];
            $url_update_manager .= 'godmode/um_client/api.php';
        }
    }

    return $url_update_manager;
}


/**
 * Prepare configuration values.
 *
 * @return array UM Configuration tokens.
 */
function update_manager_get_config_values()
{
    global $config;
    global $build_version;
    global $pandora_version;
    static $historical_dbh;

    enterprise_include_once('include/functions_license.php');

    $license = db_get_value(
        db_escape_key_identifier('value'),
        'tupdate_settings',
        db_escape_key_identifier('key'),
        'customer_key'
    );

    $data = enterprise_hook('license_get_info');

    if ($data === ENTERPRISE_NOT_HOOK) {
        $limit_count = db_get_value_sql('SELECT count(*) FROM tagente');
    } else {
        $limit_count = $data['count_enabled'];
    }

    if ($historical_dbh === null
        && isset($config['history_db_enabled']) === true
        && (bool) $config['history_db_enabled'] === true
    ) {
        $dbm = new \PandoraFMS\Core\DBMaintainer(
            [
                'host' => $config['history_db_host'],
                'port' => $config['history_db_port'],
                'name' => $config['history_db_name'],
                'user' => $config['history_db_user'],
                'pass' => $config['history_db_pass'],
            ]
        );

        $historical_dbh = $dbm->getDBH();
    }

    $insecure = false;
    if ($config['secure_update_manager'] === ''
        || $config['secure_update_manager'] === null
    ) {
        $insecure = false;
    } else {
        // Directive defined.
        $insecure = !$config['secure_update_manager'];
    }

    return [
        'url'               => update_manager_get_url(),
        'insecure'          => $insecure,
        'license'           => $license,
        'current_package'   => update_manager_get_current_package(),
        'MR'                => (int) $config['MR'],
        'limit_count'       => $limit_count,
        'build'             => $build_version,
        'version'           => $pandora_version,
        'registration_code' => $config['pandora_uid'],
        'homedir'           => $config['homedir'],
        'remote_config'     => $config['remote_config'],
        'dbconnection'      => $config['dbconnection'],
        'historydb'         => $historical_dbh,
        'language'          => $config['language'],
        'timezone'          => $config['timezone'],
        'proxy'             => [
            'host'     => ($config['update_manager_proxy_host'] ?? null),
            'port'     => ($config['update_manager_proxy_port'] ?? null),
            'user'     => ($config['update_manager_proxy_user'] ?? null),
            'password' => ($config['update_manager_proxy_password'] ?? null),
        ],
    ];
}


/**
 * Return ad campaigns messages from UMS.
 *
 * @return array|null
 */
function update_manager_get_messages()
{
    $settings = update_manager_get_config_values();
    $umc = new UpdateManager\Client($settings);

    return $umc->getMessages();
}


/**
 * Function to remove dir and files inside.
 *
 * @param string $dir Path to dir.
 *
 * @deprecated 755 Use Files::rmrf.
 *
 * @return void
 */
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);

        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (filetype($dir.'/'.$object) == 'dir') {
                    rrmdir($dir.'/'.$object);
                } else {
                    unlink($dir.'/'.$object);
                }
            }
        }

        reset($objects);
        rmdir($dir);
    } else {
        unlink($dir);
    }
}


/**
 * Keeps an history of upgrades.
 *
 * @param string $version Version installed.
 * @param string $type    Package type (server|console).
 * @param string $mode    Installation style (offline|online).
 *
 * @return void
 */
function register_upgrade($version, $type, $mode)
{
    global $config;

    $origin = 'unknown';
    if ($mode === Manager::MODE_OFFLINE) {
        $origin = 'offline';
    } else if ($mode === Manager::MODE_ONLINE) {
        $origin = 'online';
    }

    db_pandora_audit(
        AUDIT_LOG_UMC,
        'System updated to '.$version.' ('.$type.') from '.$origin
    );

    db_process_sql_insert(
        'tupdate_journal',
        [
            'version'    => $version,
            'type'       => $type,
            'origin'     => $origin,
            'id_user'    => $config['id_user'],
            'utimestamp' => time(),
        ]
    );
}
