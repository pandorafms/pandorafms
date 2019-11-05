<?php
/**
 * Pandora FMS integration API.
 *
 * @category   API
 * @package    Pandora FMS
 * @subpackage Console
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
require_once 'config.php';
require_once 'functions_api.php';

global $config;

define('DEBUG', 0);
define('VERBOSE', 0);

// TESTING THE UPDATE MANAGER.
enterprise_include_once('include/functions_enterprise_api.php');

$ipOrigin = $_SERVER['REMOTE_ADDR'];

// Get the parameters and parse if necesary.
$op = get_parameter('op');
$op2 = get_parameter('op2');
$ext_name = get_parameter('ext_name');
$ext_function = get_parameter('ext_function');
$id = get_parameter('id');
$id2 = get_parameter('id2');
$otherSerialize = get_parameter('other');
$otherMode = get_parameter('other_mode', 'url_encode');
$returnType = get_parameter('return_type', 'string');
$api_password = get_parameter('apipass', '');
$password = get_parameter('pass', '');
$user = get_parameter('user', '');
$info = get_parameter('info', '');

$other = parseOtherParameter($otherSerialize, $otherMode);

$other = parseOtherParameter($otherSerialize, $otherMode);
$apiPassword = io_output_password(
    db_get_value_filter(
        'value',
        'tconfig',
        ['token' => 'api_password']
    )
);

$correctLogin = false;
$no_login_msg = '';

// Clean unwanted output.
ob_clean();

// READ THIS:
// Special call without checks to retrieve version and build of the Pandora FMS
// This info is avalable from the web console without login
// Don't change the format, it is parsed by applications.
if ($info == 'version') {
    if (!$config['MR']) {
        $config['MR'] = 0;
    }

    echo 'Pandora FMS '.$pandora_version.' - '.$build_version.' MR'.$config['MR'];
    exit;
}

if (isInACL($ipOrigin)) {
    if (empty($apiPassword) || (!empty($apiPassword) && $api_password === $apiPassword)) {
        $user_in_db = process_user_login($user, $password, true);
        if ($user_in_db !== false) {
            $config['id_usuario'] = $user_in_db;
            // Compat.
            $config['id_user'] = $user_in_db;
            $correctLogin = true;

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
                $_SESSION = [];
            }

            $_SESSION['id_usuario'] = $user;

            config_prepare_session();
            session_write_close();
        } else {
            $no_login_msg = 'Incorrect user credentials';
        }
    } else {
        $no_login_msg = 'Incorrect given API password';
    }
} else {
    $no_login_msg = 'IP '.$ipOrigin.' is not in ACL list';
}

if ($correctLogin) {
    if (($op !== 'get') && ($op !== 'set') && ($op !== 'help')) {
        returnError('no_set_no_get_no_help', $returnType);
    } else {
        $function_name = '';

        // Check if is an extension function and get the function name.
        if ($op2 == 'extension') {
            $extension_api_url = $config['homedir'].'/'.EXTENSIONS_DIR.'/'.$ext_name.'/'.$ext_name.'.api.php';
            // The extension API file must exist and the extension must be
            // enabled.
            if (file_exists($extension_api_url)
                && !in_array($ext_name, extensions_get_disabled_extensions())
            ) {
                include_once $extension_api_url;
                $function_name = 'apiextension_'.$op.'_'.$ext_function;
            }
        } else {
            $function_name = 'api_'.$op.'_'.$op2;

            if ($op == 'set' && $id) {
                switch ($op2) {
                    case 'update_agent':
                    case 'add_module_in_conf':
                    case 'update_module_in_conf':
                    case 'delete_module_in_conf':
                        $agent = agents_locate_agent($id);
                        if ($agent !== false) {
                            $id_os = $agent['id_os'];
                            if ($id_os == 100) {
                                returnError(
                                    'not_allowed_operation_cluster',
                                    $returnType
                                );
                                return false;
                            }
                        }
                    break;

                    case 'create_network_module':
                    case 'create_plugin_module':
                    case 'create_data_module':
                    case 'create_synthetic_module':
                    case 'create_snmp_module':
                    case 'delete_module':
                    case 'delete_agent':
                        $agent = agents_locate_agent($id);
                        if ($agent !== false) {
                            $id_os = $agent['id_os'];
                            if ($id_os == 100) {
                                returnError(
                                    'not_allowed_operation_cluster',
                                    $returnType
                                );
                                return false;
                            }
                        }
                    break;

                    case 'update_network_module':
                    case 'update_plugin_module':
                    case 'update_data_module':
                    case 'update_snmp_module':

                        $id_os = db_get_value_sql(
                            sprintf(
                                'SELECT id_os 
                                 FROM tagente 
                                 WHERE id_agente = (
                                     SELECT id_agente 
                                     FROM tagente_modulo 
                                     WHERE id_agente_modulo = %d
                                )',
                                $id
                            )
                        );


                        if ($id_os == 100) {
                            returnError(
                                'not_allowed_operation_cluster',
                                $returnType
                            );
                            return false;
                        }
                    break;

                    case 'delete_user_permission':
                        if ($user_db === '') {
                            returnError(
                                __('User or group not specified'),
                                __('User, group not specified')
                            );
                            return;
                        }

                        $id_os = api_set_delete_user_profiles(
                            $thrash1,
                            $thrash2,
                            $other,
                            $returnType
                        );

                        if ($id_os != 100) {
                            return;
                        }

                        if ($id_os == false) {
                            returnError(
                                'not_allowed_operation_cluster',
                                $returnType
                            );
                            return false;
                        }
                    break;

                    case 'add_permission_user_to_group':
                        if ($user_db == null
                            || $group_db == null
                            || $id_up == null
                        ) {
                            returnError(
                                __('User, group or profile not specified'),
                                __('User, group or profile status not specified')
                            );
                            return;
                        }

                        $id_os = api_set_add_permission_user_to_group(
                            $thrash1,
                            $thrash2,
                            $other,
                            $returnType
                        );

                        if ($id_os != 100) {
                            return;
                        }

                        if ($id_os == false) {
                            returnError(
                                'not_allowed_operation_cluster',
                                $returnType
                            );
                            return false;
                        }
                    break;

                    default:
                        // Ignore.
                    break;
                }
            }
        }

        // Check if the function exists.
        if (function_exists($function_name)) {
            if (!DEBUG) {
                error_reporting(0);
            }

            if (VERBOSE) {
                error_reporting(E_ALL);
                ini_set('display_errors', 1);
            }

            call_user_func(
                $function_name,
                $id,
                $id2,
                $other,
                $returnType,
                $user_in_db
            );
        } else {
            returnError('no_exist_operation', $returnType);
        }
    }
} else {
    /*
     * //TODO: Implement a new switch in config to enable / disable
     * ACL auth failure: if enabled and have lots of traffic can produce
     * millions of records and a considerable OVERHEAD in the system :(
     * db_pandora_audit("API access Failed", $no_login_msg, $user, $ipOrigin);
     */

    sleep(15);

    // Protection on DoS attacks.
    echo 'auth error';
}

// Logout.
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];
    // Could give a warning if no session file is created. Ignore.
    @session_destroy();
    header_remove('Set-Cookie');
    setcookie(session_name(), $_COOKIE[session_name()], (time() - 4800), '/');
}
