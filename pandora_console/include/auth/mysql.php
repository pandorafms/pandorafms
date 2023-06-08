<?php

/**
 * MySQL Authentication functions.
 *
 * @category   Functions.
 * @package    Pandora FMS
 * @subpackage Login.
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
/**
 * @package Include/auth
 */

if (isset($config) === false) {
    die(
        '
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<title>Pandora FMS - The Flexible Monitoring System - Console error</title>
		<meta http-equiv="expires" content="0">
		<meta http-equiv="content-type" content="text/html; charset=utf8">
		<meta name="resource-type" content="document">
		<meta name="distribution" content="global">
		<meta name="author" content="Artica ST">
		<meta name="copyright" content="(c) Artica ST">
		<meta name="robots" content="index, follow">
		<link rel="icon" href="../../images/pandora.ico" type="image/ico">
		<link rel="stylesheet" href="../styles/pandora.css" type="text/css">
	</head>
	<body>
		<div id="main" class="float-left mrgn_lft_100px">
			<div align="center">
				<div id="login_f">
					<h1 id="log_f" class="error">You cannot access this file</h1>
					<div>
						<img src="../../images/pandora_logo.png" border="0" />
					</div>
					<div class="msg">
						<span class="error">
							<b>ERROR:</b> You can\'t access this file directly!
						</span>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
'
    );
}

require_once $config['homedir'].'/include/functions_profile.php';
enterprise_include('include/auth/mysql.php');

$config['user_can_update_info'] = true;
$config['user_can_update_password'] = true;
$config['admin_can_add_user'] = true;
$config['admin_can_delete_user'] = true;
$config['admin_can_disable_user'] = false;
// Currently not implemented.
$config['admin_can_make_admin'] = true;


/**
 * Process_user_login accepts $login and $pass and handles it according to
 * current authentication scheme.
 *
 * @param string  $login Login.
 * @param string  $pass  Pass.
 * @param boolean $api   Api.
 *
 * @return mixed False in case of error or invalid credentials, the username in
 * case it's correct.
 */
function process_user_login($login, $pass, $api=false, $passAlreadyEncrypted=false)
{
    global $config;

    // 0. Check first is user y set as local user.
    $local_user = (bool) db_get_value_filter(
        'local_user',
        'tusuario',
        ['id_user' => $login]
    );

    // 1. Try remote.
    if ($local_user !== true && strtolower($config['auth']) != 'mysql') {
        $login_remote = process_user_login_remote(
            $login,
            io_safe_output($pass),
            $api
        );
    } else {
        $login_remote = false;
    }

    // 2. Try local.
    if ($login_remote === false) {
        if ($api === true) {
            $user_not_login = db_get_value(
                'not_login',
                'tusuario',
                'id_user',
                $login
            );
        }

        if ($config['fallback_local_auth']
            || is_user_admin($login)
            || $local_user === true
            || strtolower($config['auth']) === 'mysql'
            || (bool) $user_not_login === true
        ) {
            return process_user_login_local($login, $pass, $api, $passAlreadyEncrypted);
        } else {
            return false;
        }
    } else {
        return $login_remote;
    }

}


function process_user_login_local($login, $pass, $api=false, $passAlreadyEncrypted=false)
{
    global $config, $mysql_cache;

    if ((bool) $api === false) {
        $sql = sprintf(
            "SELECT `id_user`, `password`
            FROM `tusuario`
            WHERE `id_user` = '%s' AND `not_login` = 0
                AND `disabled` = 0",
            $login
        );
    } else {
        $sql = sprintf(
            "SELECT `id_user`, `password`
            FROM `tusuario`
            WHERE `id_user` = '%s'
                AND `disabled` = 0",
            $login
        );
    }

    $row = db_get_row_sql($sql);

    if ($passAlreadyEncrypted) {
        $credentials_check = $pass === $row['password'];
    } else {
         // Perform password check whether it is MD5-hashed (old hashing) or Bcrypt-hashed.
        if (strlen($row['password']) === 32) {
            // MD5.
            $credentials_check = $row !== false && $row['password'] !== md5('') && $row['password'] == md5($pass);
        } else {
            // Bcrypt.
            $credentials_check = password_verify($pass, $row['password']);
        }
    }

    if ($credentials_check === true) {
        // Login OK
        // Nick could be uppercase or lowercase (select in MySQL
        // is not case sensitive)
        // We get DB nick to put in PHP Session variable,
        // to avoid problems with case-sensitive usernames.
        // Thanks to David Muñiz for Bug discovery :).
        $filter = ['id_usuario' => $login];
        $user_profile = db_get_row_filter('tusuario_perfil', $filter);
        if ((bool) users_is_admin($login) === false && (bool) $user_profile === false) {
            $mysql_cache['auth_error'] = 'User does not have any profile';
            $config['auth_error'] = 'User does not have any profile';
            return false;
        }

        // Override password to use Bcrypt encryption.
        if (strlen($row['password']) === 32) {
            update_user_password($login, $pass);
        }

        return $row['id_user'];
    } else {
        if (user_can_login($login) === false) {
            $mysql_cache['auth_error'] = 'User only can use the API.';
            $config['auth_error'] = 'User only can use the API.';
        } else {
            $mysql_cache['auth_error'] = 'User not found in database or incorrect password';
            $config['auth_error'] = 'User not found in database or incorrect password';
        }
    }

    return false;
}


function process_user_login_remote($login, $pass, $api=false)
{
    global $config, $mysql_cache;

    // Remote authentication.
    switch ($config['auth']) {
        // LDAP.
        case 'ldap':
            $sr = ldap_process_user_login($login, $pass);
            // Try with secondary server if not login.
            if ($sr === false && (bool) $config['secondary_ldap_enabled'] === true) {
                $sr = ldap_process_user_login($login, $pass, true);
            }

            if (!$sr) {
                return false;
            }
        break;

        // Active Directory.
        case 'ad':
            if (enterprise_hook('ad_process_user_login', [$login, $pass]) === false) {
                $config['auth_error'] = 'User not found in database or incorrect password';
                return false;
            }
        break;

        // Remote Pandora FMS.
        case 'pandora':
            if (enterprise_hook('remote_pandora_process_user_login', [$login, $pass]) === false) {
                $config['auth_error'] = 'User not found in database or incorrect password';
                return false;
            }
        break;

        // Remote Integria.
        case 'integria':
            if (enterprise_hook('remote_integria_process_user_login', [$login, $pass]) === false) {
                $config['auth_error'] = 'User not found in database or incorrect password';
                return false;
            }
        break;

        // Unknown authentication method.
        default:
            $config['auth_error'] = 'User not found in database or incorrect password';
        return false;
            break;
    }

    if ($config['auth'] === 'ldap') {
        $login_user_attribute = $login;
        if ($config['ldap_login_user_attr'] == 'mail') {
            $login = $sr['mail'][0];
        }
    }

    // Authentication ok, check if the user exists in the local database.
    if (is_user($login)) {
        if (!user_can_login($login) && $api === false) {
            return false;
        }

        if (($config['auth'] === 'ad')) {
            // Check if autocreate  remote users is active.
            if ($config['autocreate_remote_users'] == 1) {
                if ($config['ad_save_password']) {
                    $update_credentials = change_local_user_pass_ldap($login, $pass);
                } else {
                    delete_user_pass_ldap($login);
                }
            }

            if (isset($config['ad_advanced_config']) && $config['ad_advanced_config']) {
                $return = enterprise_hook(
                    'prepare_permissions_groups_of_user_ad',
                    [
                        $login,
                        $pass,
                        false,
                        true,
                        defined('METACONSOLE'),
                    ]
                );

                if ($return === 'error_permissions') {
                    $config['auth_error'] = __('Problems with configuration permissions. Please contact with Administrator');
                    return false;
                }
            }
        } else if ($config['auth'] === 'ldap') {
            // Check if autocreate  remote users is active.
            if ($config['autocreate_remote_users'] == 1) {
                if ($config['ldap_save_password']) {
                    $update_credentials = change_local_user_pass_ldap($login, $pass);
                } else {
                    delete_user_pass_ldap($login);
                }

                $permissions = fill_permissions_ldap($sr);
                if (empty($permissions)) {
                    $config['auth_error'] = __('User not found in database or incorrect password');
                    return false;
                } else {
                    // check permissions
                    $result = check_permission_ad(
                        $login,
                        $pass,
                        false,
                        $permissions,
                        defined('METACONSOLE')
                    );

                    if ($result === 'error_permissions') {
                        $config['auth_error'] = __('Problems with configuration permissions. Please contact with Administrator');
                        return false;
                    }
                }
            }
        }

        return $login;
    }

    // The user does not exist and can not be created.
    if ($config['autocreate_remote_users'] == 0 || is_user_blacklisted($login)) {
        $config['auth_error'] = __(
            'Ooops User not found in
				database or incorrect password'
        );

        return false;
    }

    if ($config['auth'] === 'ad'
        && (isset($config['ad_advanced_config'])
        && $config['ad_advanced_config'])
    ) {
        if (is_management_allowed() === false) {
            $config['auth_error'] = __('Please, login into metaconsole first');
            return false;
        }

        $user_info = [
            'fullname' => db_escape_string_sql($login),
            'comments' => 'Imported from '.$config['auth'],
        ];

        if (is_metaconsole() === true) {
            $user_info['metaconsole_access_node'] = $config['ad_adv_user_node'];
        }

        // Create the user.
        if (enterprise_hook(
            'prepare_permissions_groups_of_user_ad',
            [
                $login,
                $pass,
                $user_info,
                false,
                defined('METACONSOLE') && is_centralized() === false,
            ]
        ) === false
        ) {
            $config['auth_error'] = __('User not found in database or incorrect password');
            return false;
        }
    } else if ($config['auth'] === 'ldap') {
        if (is_management_allowed() === false) {
            $config['auth_error'] = __('Please, login into metaconsole first');
            return false;
        }

        if (is_metaconsole() === true) {
            $user_info['metaconsole_access_node'] = $config['ldap_adv_user_node'];
        }

        if (isset($config['timezonevisual']) === true) {
            $user_info['timezone'] = $config['timezonevisual'];
        }

        $permissions = fill_permissions_ldap($sr);
        if (empty($permissions) === true) {
            $config['auth_error'] = __('User not found in database or incorrect password');
            return false;
        } else {
            $user_info['fullname'] = db_escape_string_sql(io_safe_input($sr['cn'][0]));
            $user_info['email'] = io_safe_input($sr['mail'][0]);

            // Create the user.
            $create_user = create_user_and_permisions_ldap(
                $login,
                $pass,
                $user_info,
                $permissions,
                is_metaconsole() && is_centralized() === false
            );
        }
    } else {
        $user_info = [
            'fullname' => $login,
            'comments' => 'Imported from '.$config['auth'],
        ];
        if (is_metaconsole() === true && $config['auth'] === 'ad') {
            $user_info['metaconsole_access_node'] = $config['ad_adv_user_node'];
        }

        if (is_management_allowed() === false) {
            $config['auth_error'] = __('Please, login into metaconsole first');
            return false;
        }

        // Create the user in the local database.
        if (create_user($login, $pass, $user_info) === false) {
            $config['auth_error'] = __('User not found in database or incorrect password');
            return false;
        }

        profile_create_user_profile(
            $login,
            $config['default_remote_profile'],
            $config['default_remote_group'],
            false,
            $config['default_assign_tags']
        );
    }

    return $login;
}


/**
 * Checks if a user is administrator.
 *
 * @param string User id.
 *
 * @return boolean True is the user is admin
 */
function is_user_admin($id_user)
{
    include_once __DIR__.'/../functions_users.php';
    return users_is_admin($id_user);
}


/**
 * Get the user id field on a mixed structure.
 *
 * This function is needed to make auth system more compatible and independant.
 *
 * @param mixed User structure to get id. It might be a row returned from
 * tusuario or tusuario_perfil. If it's not a row, the int value is returned.
 *
 * @return integer User id of the mixed parameter.
 */
function get_user_id($user)
{
    if (is_array($user)) {
        if (isset($user['id_user'])) {
            return $user['id_user'];
        } else if (isset($user['id_usuario'])) {
            return $user['id_usuario'];
        } else {
            return false;
        }
    } else {
        return $user;
    }
}


/**
 * Check is a user exists in the system
 *
 * @param mixed User id.
 *
 * @return boolean True if the user exists.
 */
function is_user($user)
{
    $user = db_get_row('tusuario', 'id_user', get_user_id($user));

    if (! $user) {
        return false;
    }

    return true;
}


function user_can_login($user)
{
    $not_login = db_get_value('not_login', 'tusuario', 'id_user', $user);

    if ($not_login != 0) {
        return false;
    }

    return true;
}


/**
 * Gets the users real name
 *
 * @param mixed User id.
 *
 * @return string The users full name
 */
function get_user_fullname($user)
{
    return (string) db_get_value('fullname', 'tusuario', 'id_user', get_user_id($user));
}


/**
 * Gets the users email
 *
 * @param mixed $user User id.
 *
 * @return string The users email address
 */
function get_user_email($user)
{
    return (string) db_get_value('email', 'tusuario', 'id_user', get_user_id($user));
}


/**
 * Gets a Users info
 *
 * @param mixed $user User id.
 *
 * @return mixed An array of users
 */
function get_user_info($user)
{
    static $cache_user_info = [];
    if (array_key_exists($user, $cache_user_info) === true) {
        return $cache_user_info[$user];
    } else {
        $return = db_get_row('tusuario', 'id_user', get_user_id($user));
        $cache_user_info[$user] = $return;
        return $return;
    }
}


/**
 * Get a list of all users in an array [username] => array (userinfo)
 * We can't simplify this because some auth schemes (like LDAP) automatically (or it's at least cheaper to) return all the information
 * Functions like get_user_info allow selection of specifics (in functions_db)
 *
 * @param mixed  $order  Field to order by (id_user, fullname or registered).
 * @param string $filter Filter.
 * @param string $fields Fields.
 *
 * @return array An array of user information
 */
function get_users($order='fullname', $filter=false, $fields=false)
{
    if (is_array($order) === true) {
        $filter['order'] = $order['field'].' '.$order['order'];
    } else {
        if ($order !== 'registered' || $order !== 'last_connect' || $order !== 'fullname') {
            $order = 'fullname';
        }

        $filter['order'] = $order.' ASC';
    }

    $output = [];

    $result = db_get_all_rows_filter('tusuario', $filter, $fields);
    if ($result !== false) {
        foreach ($result as $row) {
            $output[$row['id_user']] = $row;
        }
    }

    return $output;
}


/**
 * Sets the last login for a user
 *
 * @param string $id_user User id.
 *
 * @return mixed.
 */
function process_user_contact(string $id_user)
{
    return db_process_sql_update(
        'tusuario',
        ['last_connect' => get_system_time()],
        ['id_user' => $id_user]
    );
}


/**
 * Create a new user
 *
 * @param string $id_user   Id User.
 * @param string $password  Password for this user.
 * @param array  $user_info Array with information of the user.
 *
 * @return boolean false
 */
function create_user($id_user, $password, $user_info)
{
    $values = $user_info;

    $column_type = db_get_column_type('tusuario', 'password');
    if (empty($column_type) === false && isset($column_type[0]['COLUMN_TYPE'])) {
        $column_type = ($column_type[0]['COLUMN_TYPE'] === 'varchar(60)');
    } else {
        $column_type = false;
    }

    $values['id_user'] = $id_user;
    $values['password'] = ($column_type === false) ? md5($password) : password_hash($password, PASSWORD_BCRYPT);
    $values['last_connect'] = 0;
    $values['registered'] = get_system_time();

    $output = (@db_process_sql_insert('tusuario', $values)) !== false;

    // Add user to notification system.
    if ($output !== false) {
        if (isset($values['is_admin']) === true && (bool) $values['is_admin'] === true) {
            // Administrator user must be activated in all notifications sections.
            $notificationSources = db_get_all_rows_filter('tnotification_source', [], 'id');
            foreach ($notificationSources as $notification) {
                @db_process_sql_insert(
                    'tnotification_source_user',
                    [
                        'id_source' => $notification['id'],
                        'id_user'   => $id_user,
                    ]
                );
            }
        } else {
            // Other users only will be activated in `Message` notifications.
            $notificationSource = db_get_value('id', 'tnotification_source', 'description', 'Message');
            @db_process_sql_insert(
                'tnotification_source_user',
                [
                    'id_source' => $notificationSource,
                    'id_user'   => $id_user,
                ]
            );
        }
    }

    return $output;
}


/**
 * Save password history
 *
 * @param string $id_user  Id User.
 * @param string $password Password of user.
 *
 * @return boolean false
 */
function save_pass_history(string $id_user, string $password)
{
    $values['id_user'] = $id_user;
    $values['password'] = md5($password);
    $values['date_begin'] = date('Y/m/d H:i:s', get_system_time());

    return (@db_process_sql_insert('tpassword_history', $values)) !== false;
}


/**
 * Deletes the user
 *
 * @param string $id_user User id.
 *
 * @return boolean.
 */
function delete_user(string $id_user)
{
    $result = db_process_sql_delete(
        'tusuario_perfil',
        ['id_usuario' => $id_user]
    );
    if ($result === false) {
        return false;
    }

    $result = db_process_sql_delete(
        'tusuario',
        ['id_user' => $id_user]
    );
    if ($result === false) {
        return false;
    }

    // Remove from notification list as well.
    $result = db_process_sql_delete(
        'tnotification_source_user',
        ['id_user' => $id_user]
    );

    return true;
}


/**
 * Update the password using BCRYPT algorithm for specific id_user passing
 * password in plain text.
 *
 * @param string $user         User ID.
 * @param string $password_new Password in plain text.
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function update_user_password(string $user, string $password_new)
{
    global $config;

    if (enterprise_hook('excludedPassword', [$password_new]) === true) {
        $config['auth_error'] = __('The password provided is not valid. Please, set another one.');
        return false;
    }

    $column_type = db_get_column_type('tusuario', 'password');
    if (empty($column_type) === false && isset($column_type[0]['COLUMN_TYPE'])) {
        $column_type = ($column_type[0]['COLUMN_TYPE'] === 'varchar(60)');
    } else {
        $column_type = false;
    }

    if (isset($config['auth']) === true && $config['auth'] === 'pandora') {
        $sql = sprintf(
            "UPDATE tusuario SET password = '%s', last_pass_change = '%s' WHERE id_user = '%s'",
            ($column_type === false) ? md5($password_new) : password_hash($password_new, PASSWORD_BCRYPT),
            date('Y-m-d H:i:s', get_system_time()),
            $user
        );

        $connection = mysql_connect_db(
            $config['rpandora_server'],
            $config['rpandora_dbname'],
            $config['rpandora_user'],
            $config['rpandora_pass']
        );
        $remote_pass_update = db_process_sql($sql, 'affected_rows', $connection);

        if ((bool) $remote_pass_update === false) {
            $config['auth_error'] = __('Could not changes password on remote pandora');
            return false;
        }
    }

    return db_process_sql_update(
        'tusuario',
        [
            'password'         => ($column_type === false) ? md5($password_new) : password_hash($password_new, PASSWORD_BCRYPT),
            'last_pass_change' => date('Y/m/d H:i:s', get_system_time()),
        ],
        ['id_user' => $user]
    );
}


/**
 * Update the data of a user that user is choose with
 * id_user.
 *
 * @param string $id_user User ID.
 * @param array  $values  Associative array with index as name of field and content.
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function update_user(string $id_user, array $values)
{
    if (is_array($values) === false) {
        return false;
    }

    if (isset($values['section']) === true) {
        $homeScreenValues = [
            HOME_SCREEN_DEFAULT        => __('Default'),
            HOME_SCREEN_VISUAL_CONSOLE => __('Visual console'),
            HOME_SCREEN_EVENT_LIST     => __('Event list'),
            HOME_SCREEN_GROUP_VIEW     => __('Group view'),
            HOME_SCREEN_TACTICAL_VIEW  => __('Tactical view'),
            HOME_SCREEN_ALERT_DETAIL   => __('Alert detail'),
            HOME_SCREEN_EXTERNAL_LINK  => __('External link'),
            HOME_SCREEN_OTHER          => __('Other'),
            HOME_SCREEN_DASHBOARD      => __('Dashboard'),
        ];

        if (array_key_exists($values['section'], $homeScreenValues) === true) {
            $values['section'] = $homeScreenValues[$values['section']];
        }

        if (is_metaconsole() === true) {
            $values['metaconsole_section'] = $values['section'];
            $values['metaconsole_data_section'] = $values['data_section'];
            $values['metaconsole_default_event_filter'] = $values['default_event_filter'];
            unset($values['id_skin']);
            unset($values['section']);
            unset($values['data_section']);
            unset($values['default_event_filter']);
        }
    }

    $output = db_process_sql_update('tusuario', $values, ['id_user' => $id_user]);

    if (isset($values['is_admin']) === true && (bool) $values['is_admin'] === true) {
        // Administrator user must be activated in all notifications sections.
        $notificationSources = db_get_all_rows_filter('tnotification_source', [], 'id');

        foreach ($notificationSources as $notification) {
            $user_source = db_get_value_filter(
                'id_source',
                'tnotification_source_user',
                [
                    'id_source' => $notification['id'],
                    'id_user'   => $id_user,
                ]
            );

            if ($user_source !== false) {
                @db_process_sql_update(
                    'tnotification_source_user',
                    ['enabled' => 1],
                    [
                        'id_source' => $notification['id'],
                        'id_user'   => $id_user,
                    ]
                );
            } else if ((int) $notification['id'] === 1 || (int) $notification['id'] === 5) {
                @db_process_sql_insert(
                    'tnotification_source_user',
                    [
                        'id_source' => $notification['id'],
                        'id_user'   => $id_user,
                    ]
                );
            }
        }
    }

    return $output;
}


/**
 * Authenticate against an LDAP server.
 *
 * @param string User login
 * @param string User password (plain text)
 *
 * @return boolean True if the login is correct, false in other case
 */
function ldap_process_user_login($login, $password, $secondary_server=false)
{
    global $config;

    if (! function_exists('ldap_connect')) {
        $config['auth_error'] = __('Your installation of PHP does not support LDAP');

        return false;
    }

    $ldap_tokens = [
        'ldap_server',
        'ldap_port',
        'ldap_version',
        'ldap_base_dn',
        'ldap_login_attr',
        'ldap_admin_login',
        'ldap_admin_pass',
        'ldap_start_tls',
    ];

    foreach ($ldap_tokens as $token) {
        $ldap[$token] = $secondary_server === true ? $config[$token.'_secondary'] : $config[$token];
    }

    // Remove entities ldap admin pass.
    $ldap['ldap_admin_pass'] = io_safe_output($ldap['ldap_admin_pass']);

    // Connect to the LDAP server
    if (stripos($ldap['ldap_server'], 'ldap://') !== false
        || stripos($ldap['ldap_server'], 'ldaps://') !== false
        || stripos($ldap['ldap_server'], 'ldapi://') !== false
    ) {
        $ds = @ldap_connect($ldap['ldap_server'].':'.$ldap['ldap_port']);
    } else {
        $ds = @ldap_connect($ldap['ldap_server'], $ldap['ldap_port']);
    }

    if (!$ds) {
        $config['auth_error'] = 'Error connecting to LDAP server';

        return false;
    }

    // Set the LDAP version.
    ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, $ldap['ldap_version']);
    ldap_set_option($ds, LDAP_OPT_NETWORK_TIMEOUT, 1);

    // Set ldap search timeout.
    ldap_set_option(
        $ds,
        LDAP_OPT_TIMELIMIT,
        (empty($config['ldap_search_timeout']) === true) ? 5 : ((int) $config['ldap_search_timeout'])
    );

    if ($ldap['ldap_start_tls']) {
        if (!@ldap_start_tls($ds)) {
            $config['auth_error'] = 'Could not start TLS for LDAP connection';
            @ldap_close($ds);

            return false;
        }
    }

    if ($config['ldap_function'] == 'local') {
        $sr = local_ldap_search(
            $ldap['ldap_server'],
            $ldap['ldap_port'],
            $ldap['ldap_version'],
            io_safe_output($ldap['ldap_base_dn']),
            $ldap['ldap_login_attr'],
            io_safe_output($ldap['ldap_admin_login']),
            io_output_password($ldap['ldap_admin_pass']),
            io_safe_output($login),
            $ldap['ldap_start_tls'],
            $config['ldap_search_timeout']
        );

        if ($sr) {
            $user_dn = $sr['dn'][0];

            $ldap_base_dn = !empty($ldap['ldap_base_dn']) ? ','.io_safe_output($ldap['ldap_base_dn']) : '';

            if (!empty($ldap_base_dn)) {
                if (strlen($password) != 0 && @ldap_bind($ds, io_safe_output($user_dn), $password)) {
                    @ldap_close($ds);
                    return $sr;
                }
            } else {
                if (strlen($password) != 0 && @ldap_bind($ds, io_safe_output($login), $password)) {
                    @ldap_close($ds);
                    return $sr;
                }
            }
        }
    } else {
        // PHP LDAP function
        if ($ldap['ldap_admin_login'] != '' && $ldap['ldap_admin_pass'] != '') {
            if (!@ldap_bind($ds, io_safe_output($ldap['ldap_admin_login']), io_output_password($ldap['ldap_admin_pass']))) {
                $config['auth_error'] = 'Admin ldap connection fail';
                @ldap_close($ds);
                return false;
            }
        }

        $filter = '('.$ldap['ldap_login_attr'].'='.io_safe_output($login).')';

        $sr = ldap_search($ds, io_safe_output($ldap['ldap_base_dn']), $filter);

        if (empty($sr) === true) {
            $config['auth_error'] = 'ldap search failed';
            @ldap_close($ds);
            return false;
        }

        $memberof = ldap_get_entries($ds, $sr);

        if ($memberof['count'] == 0 && !isset($memberof[0]['memberof'])) {
            @ldap_close($ds);
            return false;
        } else {
            $memberof = $memberof[0];
        }

        unset($memberof['count']);
        $ldap_base_dn = !empty($ldap['ldap_base_dn']) ? ','.io_safe_output($ldap['ldap_base_dn']) : '';

        if (!empty($ldap_base_dn)) {
            if (strlen($password) != 0 && @ldap_bind($ds, io_safe_output($memberof['dn']), $password)) {
                @ldap_close($ds);
                return $memberof;
            }
        } else {
            if (strlen($password) != 0 && @ldap_bind($ds, io_safe_output($login), $password)) {
                @ldap_close($ds);
                return $memberof;
            }
        }
    }

    @ldap_close($ds);
    $config['auth_error'] = 'User not found in database or incorrect password';
    return false;

}


/**
 * Checks if a user is in the autocreate blacklist.
 *
 * @param string User
 *
 * @return boolean True if the user is in the blacklist, false otherwise.
 */
function is_user_blacklisted($user)
{
    global $config;

    $blisted_users = explode(',', $config['autocreate_blacklist']);
    foreach ($blisted_users as $blisted_user) {
        if ($user == $blisted_user) {
            return true;
        }
    }

    return false;
}


/**
 * Create progile with data obtaint from AD
 *
 * @param string Login
 * @param string Password
 * @param array user_info
 * @param array permiisons
 *
 * @return boolean
 */
function create_user_and_permisions_ldap(
    $id_user,
    $password,
    $user_info,
    $permissions,
    $syncronize=false
) {
    global $config;

    $values = $user_info;
    $values['id_user'] = $id_user;

    if ($config['ldap_save_password'] || $config['ad_save_password']) {
        $column_type = db_get_column_type('tusuario', 'password');
        if (empty($column_type) === false && isset($column_type[0]['COLUMN_TYPE'])) {
            $column_type = ($column_type[0]['COLUMN_TYPE'] === 'varchar(60)');
        } else {
            $column_type = false;
        }

        $values['password'] = ($column_type === false) ? md5($password) : password_hash($password, PASSWORD_BCRYPT);
    }

    $values['last_connect'] = 0;
    $values['registered'] = get_system_time();
    if (defined('METACONSOLE') && $syncronize) {
        $values['metaconsole_access_node'] = $config['ldap_adv_user_node'];
    }

    $user = (@db_process_sql_insert('tusuario', $values)) !== false;

    if ($user) {
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                $id_profile = $permission['profile'];
                $id_groups = $permission['groups'];
                $tags = $permission['tags'];
                $no_hierarchy = (bool) $permission['no_hierarchy'] ? 1 : 0;

                foreach ($id_groups as $id_group) {
                    $profile = profile_create_user_profile(
                        $id_user,
                        $id_profile,
                        $id_group,
                        false,
                        $tags,
                        $no_hierarchy
                    );
                }

                if (defined('METACONSOLE') && $syncronize) {
                    enterprise_include_once('include/functions_metaconsole.php');

                    unset($values['metaconsole_access_node']);
                    $values['not_login'] = (int) !$config['ldap_adv_user_node'];

                    $servers = metaconsole_get_servers();
                    foreach ($servers as $server) {
                        $perfil_maestro = db_get_row(
                            'tperfil',
                            'id_perfil',
                            $permission['profile']
                        );

                        if (metaconsole_connect($server) == NOERR) {
                            if (!profile_exist($perfil_maestro['name'])) {
                                unset($perfil_maestro['id_perfil']);
                                $id_profile = db_process_sql_insert('tperfil', $perfil_maestro);
                            } else {
                                $id_profile = db_get_value('id_perfil', 'tperfil', 'name', $perfil_maestro['name']);
                            }

                            db_process_sql_insert('tusuario', $values);
                            foreach ($id_groups as $id_group) {
                                $profile = profile_create_user_profile(
                                    $id_user,
                                    $id_profile,
                                    $id_group,
                                    false,
                                    $tags,
                                    $no_hierarchy
                                );
                            }
                        }

                        metaconsole_restore_db();
                    }
                }

                if (!$profile) {
                    return false;
                }
            }
        } else {
            $profile = profile_create_user_profile(
                $id_user,
                $config['default_remote_profile'],
                $config['default_remote_group'],
                false,
                $config['default_assign_tags']
            );

            if (!$profile) {
                    return false;
            }
        }
    }

    return true;
}


/**
 * Check if user have right permission in pandora. This
 * permission depend of ldap.
 *
 * @param string Login
 * @param string Password
 *
 * @return string
 */
function check_permission_ldap(
    $id_user,
    $password,
    $user_info,
    $permissions,
    $syncronize=false
) {
    global $config;
    include_once $config['homedir'].'/enterprise/include/functions_metaconsole.php';

    $result_user = users_get_user_by_id($id_user);
    $filter = ['id_usuario' => $id_user];
    $profiles_user = [];
    $user_profiles = db_get_all_rows_filter('tusuario_perfil', $filter);

    foreach ($user_profiles as $user_profile) {
        $profiles_user[$user_profile['id_up']] = $user_profile['id_perfil'];
    }

    $profiles_user_nodes = [];
    $permissions_nodes = [];
    if (is_metaconsole() && $syncronize) {
        $servers = metaconsole_get_servers();
        foreach ($servers as $server) {
            if (metaconsole_connect($server) == NOERR) {
                $user_profiles_nodes = db_get_all_rows_filter('tusuario_perfil', $filter);
                foreach ($user_profiles_nodes as $user_profile_node) {
                    $profiles_user_nodes[$server['server_name']][$user_profile_node['id_up']] = $user_profile_node['id_perfil'];
                }
            }

            metaconsole_restore_db();
        }

        foreach ($permissions as $key => $permission) {
            $perfil_maestro = db_get_row(
                'tperfil',
                'id_perfil',
                $permission['profile']
            );
            foreach ($servers as $server) {
                if (metaconsole_connect($server) == NOERR) {
                    if (profile_exist($perfil_maestro['name'])) {
                        $id_profile = db_get_value('id_perfil', 'tperfil', 'name', $perfil_maestro['name']);
                        $permissions_nodes[$server['server_name']][$key] = $permission;
                        $permissions_nodes[$server['server_name']][$key]['profile'] = $id_profile;
                    }
                }

                metaconsole_restore_db();
            }
        }
    }

    $no_found = [];
    if ($result_user) {
        foreach ($permissions as $permission) {
            $id_profile = $permission['profile'];
            $id_groups = $permission['groups'];
            $tags = $permission['tags'];

            foreach ($id_groups as $id_group) {
                $filter = [
                    'id_usuario' => $id_user,
                    'id_perfil'  => $id_profile,
                    'id_grupo'   => $id_group,
                ];
                // ~ Find perfil with advance permissions in
                // ~ authentication menu. This data depends on
                // ~ groups where this user it belong.
                $result_profiles = db_get_row_filter('tusuario_perfil', $filter);
                if (!$result_profiles) {
                    // If not found save in array.
                    $no_found[] = [
                        'id_perfil' => $id_profile,
                        'id_grupo'  => $id_group,
                        'tags'      => $tags,
                    ];
                } else {
                    // if profile is find, delete from array.
                    db_process_sql_update(
                        'tusuario_perfil',
                        ['tags' => $tags],
                        [
                            'id_usuario' => $id_user,
                            'id_up'      => $profiles_user[$id_profile],
                        ]
                    );

                    unset($profiles_user[$result_profiles['id_up']]);
                }
            }
        }

        if (is_metaconsole() && $syncronize) {
            $servers = metaconsole_get_servers();
            foreach ($servers as $server) {
                foreach ($permissions_nodes[$server['server_name']] as $permission_node) {
                    $id_profile = $permission_node['profile'];
                    $id_groups = $permission_node['groups'];
                    $tags = $permission_node['tags'];

                    foreach ($id_groups as $id_group) {
                        $filter = [
                            'id_usuario' => $id_user,
                            'id_perfil'  => $id_profile,
                            'id_grupo'   => $id_group,
                        ];

                        if (metaconsole_connect($server) == NOERR) {
                            $result_profiles = db_get_row_filter('tusuario_perfil', $filter);

                            if (!$result_profiles) {
                                // If not found save in array.
                                $no_found_server[$server['server_name']][] = [
                                    'id_perfil' => $id_profile,
                                    'id_grupo'  => $id_group,
                                    'tags'      => $tags,
                                ];
                            } else {
                                // if profile is find, delete from array.
                                db_process_sql_update(
                                    'tusuario_perfil',
                                    ['tags' => $tags],
                                    [
                                        'id_usuario' => $id_user,
                                        'id_up'      => $profiles_user_nodes[$server_name][$id_profile],
                                    ]
                                );

                                unset($profiles_user_nodes[$server_name][$result_profiles['id_up']]);
                            }
                        }
                    }
                }

                metaconsole_restore_db();
            }
        }

        if (empty($profiles_user) && empty($no_found)) {
            // The permmisions of user not changed
            return true;
        } else {
            foreach ($profiles_user as $key => $profile_user) {
                // The other profiles are deleted
                profile_delete_user_profile($id_user, $key);
            }

            if (is_metaconsole() && $syncronize) {
                foreach ($profiles_user_nodes as $server_name => $profile_users) {
                    $server = metaconsole_get_connection($server_name);
                    foreach ($profile_users as $key => $profile_user) {
                        if (metaconsole_connect($server) == NOERR) {
                            profile_delete_user_profile($id_user, $key);
                        }
                    }

                    metaconsole_restore_db();
                }
            }

            foreach ($no_found as $new_profiles) {
                // Add the missing permissions
                profile_create_user_profile(
                    $id_user,
                    $new_profiles['id_perfil'],
                    $new_profiles['id_grupo'],
                    false,
                    $new_profiles['tags']
                );
            }

            if (is_metaconsole() && $syncronize) {
                $servers = metaconsole_get_servers();
                foreach ($servers as $server) {
                    if (metaconsole_connect($server) == NOERR) {
                        foreach ($no_found_server[$server['server_name']] as $new_profiles) {
                            profile_create_user_profile(
                                $id_user,
                                $new_profiles['id_perfil'],
                                $new_profiles['id_grupo'],
                                false,
                                $new_profiles['tags']
                            );
                        }
                    }

                    metaconsole_restore_db();
                }
            }

            return 'permissions_changed';
        }
    } else {
        return 'error_permissions';
    }
}


/**
 * Fill permissions array with setup values
 *
 * @param string sr return value from LDAP connection
 *
 * @return array with all permission on LDAP authentication
 */
function fill_permissions_ldap($sr)
{
    global $config;
    $permissions = [];
    $permissions_profile = [];

    if ((bool) $config['ldap_save_profile'] === false && ($config['ldap_advanced_config'] == 0 || $config['ldap_advanced_config'] == '')) {
        $result = 0;
        $result = db_get_all_rows_filter(
            'tusuario_perfil',
            ['id_usuario' => $sr['uid'][0]]
        );
        if ($result == false) {
            $permissions[0]['profile'] = $config['default_remote_profile'];
            $permissions[0]['groups'][] = $config['default_remote_group'];
            $permissions[0]['tags'] = $config['default_assign_tags'];
            $permissions[0]['no_hierarchy'] = $config['default_no_hierarchy'];
            return $permissions;
        }

        foreach ($result as $perms) {
            $permissions_profile[] = [
                'profile'      => $perms['id_perfil'],
                'groups'       => [$perms['id_grupo']],
                'tags'         => $perms['tags'],
                'no_hierarchy' => (bool) $perms['no_hierarchy'] ? 1 : 0,
            ];
        }

        return $permissions_profile;
    }

    if ($config['ldap_advanced_config'] == 1 && $config['ldap_save_profile'] == 1) {
        $ldap_adv_perms = json_decode(io_safe_output($config['ldap_adv_perms']), true);
        return get_advanced_permissions($ldap_adv_perms, $sr);
    }

    if ($config['ldap_advanced_config'] == 1 && $config['ldap_save_profile'] == 0) {
        $result = db_get_all_rows_filter(
            'tusuario_perfil',
            ['id_usuario' => $sr['uid'][0]]
        );
        if ($result == false) {
            $ldap_adv_perms = json_decode(io_safe_output($config['ldap_adv_perms']), true);
            return get_advanced_permissions($ldap_adv_perms, $sr);
        }

        foreach ($result as $perms) {
            $permissions_profile[] = [
                'profile'      => $perms['id_perfil'],
                'groups'       => [$perms['id_grupo']],
                'tags'         => $perms['tags'],
                'no_hierarchy' => (bool) $perms['no_hierarchy'] ? 1 : 0,
            ];
        };

        return $permissions_profile;
    }

    if ($config['autocreate_remote_users'] && $config['ldap_save_profile'] == 1) {
        $permissions[0]['profile'] = $config['default_remote_profile'];
        $permissions[0]['groups'][] = $config['default_remote_group'];
        $permissions[0]['tags'] = $config['default_assign_tags'];
        $permissions[0]['no_hierarchy'] = $config['default_no_hierarchy'];
        return $permissions;
    }

    return $permissions;
}


/**
 * Get permissions in advanced mode.
 *
 * @param array ldap_adv_perms
 *
 * @return array
 */
function get_advanced_permissions($ldap_adv_perms, $sr)
{
    $permissions = [];
    foreach ($ldap_adv_perms as $ldap_adv_perm) {
        $attributes = $ldap_adv_perm['groups_ldap'];
        if (!empty($attributes[0])) {
            foreach ($attributes as $attr) {
                $attr = explode('=', $attr, 2);
                foreach ($sr[$attr[0]] as $s_attr) {
                    if (preg_match('/'.$attr[1].'/', $s_attr)) {
                        $permissions[] = [
                            'profile'      => $ldap_adv_perm['profile'],
                            'groups'       => $ldap_adv_perm['group'],
                            'tags'         => implode(',', $ldap_adv_perm['tags']),
                            'no_hierarchy' => (bool) $ldap_adv_perm['no_hierarchy'] ? 1 : 0,
                        ];
                    }
                }
            }
        } else {
            $permissions[] = [
                'profile'      => $ldap_adv_perm['profile'],
                'groups'       => $ldap_adv_perm['group'],
                'tags'         => implode(',', $ldap_adv_perm['tags']),
                'no_hierarchy' => (bool) $ldap_adv_perm['no_hierarchy'] ? 1 : 0,
            ];
        }
    }

    return $permissions;
}


/**
 * Update local user pass from ldap user
 *
 * @param string Login
 * @param string Password
 *
 * @return boolean
 */
function change_local_user_pass_ldap($id_user, $password)
{
    $local_user_pass = db_get_value_filter('password', 'tusuario', ['id_user' => $id_user]);

    $return = false;

    $column_type = db_get_column_type('tusuario', 'password');
    if (empty($column_type) === false && isset($column_type[0]['COLUMN_TYPE'])) {
        $column_type = ($column_type[0]['COLUMN_TYPE'] === 'varchar(60)');
    } else {
        $column_type = false;
    }

    $values_update = [];

    if ($column_type === false) {
        if (md5($password) !== $local_user_pass) {
            $values_update['password'] = md5($password);
            $return = db_process_sql_update('tusuario', $values_update, ['id_user' => $id_user]);
        }
    } else {
        if (password_hash($password, PASSWORD_BCRYPT) !== $local_user_pass) {
            $values_update['password'] = password_hash($password, PASSWORD_BCRYPT);
            $return = db_process_sql_update('tusuario', $values_update, ['id_user' => $id_user]);
        }
    }

    return $return;
}


function delete_user_pass_ldap($id_user)
{
    $values_update = [];
    $values_update['password'] = null;

    $return = db_process_sql_update('tusuario', $values_update, ['id_user' => $id_user]);

    return;
}


function safe_output_accute($string)
{
    $no_allowed = [
        'á',
        'é',
        'í',
        'ó',
        'ú',
        'Á',
        'É',
        'Í',
        'Ó',
        'Ú',
        'ñ',
        'Ñ',
    ];
    $allowed = [
        'a',
        'e',
        'i',
        'o',
        'u',
        'A',
        'E',
        'I',
        'O',
        'U',
        'n',
        'N',
    ];
    $result = str_replace($no_allowed, $allowed, $string);
    return $result;
}


function local_ldap_search(
    $ldap_host,
    $ldap_port=389,
    $ldap_version=3,
    $dn=null,
    $access_attr=null,
    $ldap_admin_user=null,
    $ldap_admin_pass=null,
    $user=null,
    $ldap_start_tls=null,
    $ldap_search_time=5
) {
    global $config;

    $filter = '';
    if (!empty($access_attr) && !empty($user)) {
        $filter = ' -s sub '.escapeshellarg('('.$access_attr.'='.$user.')');
    }

    $tls = '';
    if ($ldap_start_tls) {
        $tls = ' -ZZ ';
    }

    if (stripos($ldap_host, 'ldap://') !== false
        || stripos($ldap_host, 'ldaps://') !== false
        || stripos($ldap_host, 'ldapi://') !== false
    ) {
        $ldap_host = ' -H '.$ldap_host.':'.$ldap_port;
    } else {
        $ldap_host = ' -h '.$ldap_host.' -p '.$ldap_port;
    }

    $ldap_version = ' -P '.$ldap_version;
    if (!empty($ldap_admin_user)) {
        $ldap_admin_user = " -D '".$ldap_admin_user."'";
    }

    if (!empty($ldap_admin_pass)) {
        $ldap_admin_pass = ' -w '.escapeshellarg($ldap_admin_pass);
    }

    $dn = ' -b '.escapeshellarg($dn);
    $ldapsearch_command = 'timeout '.$ldap_search_time.' ldapsearch -LLL -o ldif-wrap=no -o nettimeout='.$ldap_search_time.' -x'.$ldap_host.$ldap_version.' -E pr=10000/noprompt '.$ldap_admin_user.$ldap_admin_pass.$dn.$filter.$tls.' | grep -v "^#\|^$" | sed "s/:\+ /=>/g"';
    $shell_ldap_search = explode("\n", shell_exec($ldapsearch_command));
    foreach ($shell_ldap_search as $line) {
        $values = explode('=>', $line);
        if (!empty($values[0]) && !empty($values[1])) {
            $user_attr[$values[0]][] = $values[1];
        }
    }

    if (empty($user_attr)) {
        return false;
    }

    $base64 = preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $user_attr['dn'][0]);
    if ($base64) {
        $user_dn = safe_output_accute(base64_decode($user_attr['dn'][0]));
    } else {
        $user_dn = safe_output_accute($user_attr['dn'][0]);
    }

    if (strlen($user_dn) > 0) {
        $user_attr['dn'][0] = $user_dn;
    }

    return $user_attr;

}


// Reference the global use authorization error to last auth error.
$config['auth_error'] = &$mysql_cache['auth_error'];
