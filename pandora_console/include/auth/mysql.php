<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include/auth
 */

if (!isset($config)) {
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
		<div id="main" style="float:left; margin-left: 100px">
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
// currently not implemented
$config['admin_can_make_admin'] = true;


/**
 * process_user_login accepts $login and $pass and handles it according to current authentication scheme
 *
 * @param string  $login
 * @param string  $pass
 * @param boolean $api
 *
 * @return mixed False in case of error or invalid credentials, the username in case it's correct.
 */
function process_user_login($login, $pass, $api=false)
{
    global $config, $mysql_cache;

    // Always authenticate admins against the local database
    if (strtolower($config['auth']) == 'mysql' || is_user_admin($login)) {
        return process_user_login_local($login, $pass, $api);
    } else {
        $login_remote = process_user_login_remote($login, io_safe_output($pass), $api);
        if ($login_remote == false) {
            return process_user_login_local($login, $pass, $api);
        } else {
            return $login_remote;
        }
    }

    return false;
}


function process_user_login_local($login, $pass, $api=false)
{
    global $config, $mysql_cache;

    // Connect to Database
    switch ($config['dbtype']) {
        case 'mysql':
            if (!$api) {
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
        break;

        case 'postgresql':
            if (!$api) {
                $sql = sprintf(
                    'SELECT "id_user", "password"
					FROM "tusuario"
					WHERE "id_user" = \'%s\' AND "not_login" = 0
						AND "disabled" = 0',
                    $login
                );
            } else {
                $sql = sprintf(
                    'SELECT "id_user", "password"
					FROM "tusuario"
					WHERE "id_user" = \'%s\'
						AND "disabled" = 0',
                    $login
                );
            }
        break;

        case 'oracle':
            if (!$api) {
                $sql = sprintf(
                    'SELECT id_user, password
					FROM tusuario
					WHERE id_user = \'%s\' AND not_login = 0
						AND disabled = 0',
                    $login
                );
            } else {
                $sql = sprintf(
                    'SELECT id_user, password
					FROM tusuario
					WHERE id_user = \'%s\'
						AND disabled = 0',
                    $login
                );
            }
        break;
    }

    $row = db_get_row_sql($sql);

    // Check that row exists, that password is not empty and that password is the same hash
    if ($row !== false && $row['password'] !== md5('')
        && $row['password'] == md5($pass)
    ) {
        // Login OK
        // Nick could be uppercase or lowercase (select in MySQL
        // is not case sensitive)
        // We get DB nick to put in PHP Session variable,
        // to avoid problems with case-sensitive usernames.
        // Thanks to David Muñiz for Bug discovery :)
        $filter = ['id_usuario' => $login];
        $user_profile = db_get_row_filter('tusuario_perfil', $filter);
        if (!users_is_admin($login) && !$user_profile) {
            $mysql_cache['auth_error'] = 'User does not have any profile';
            $config['auth_error'] = 'User does not have any profile';
            return false;
        }

        return $row['id_user'];
    } else {
        if (!user_can_login($login)) {
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

    // Remote authentication
    switch ($config['auth']) {
        // LDAP
        case 'ldap':
            $sr = ldap_process_user_login($login, $pass);

            if (!$sr) {
                return false;
            }
        break;

        // Active Directory
        case 'ad':
            if (enterprise_hook('ad_process_user_login', [$login, $pass]) === false) {
                $config['auth_error'] = 'User not found in database or incorrect password';
                return false;
            }
        break;

        // Remote Pandora FMS
        case 'pandora':
            if (enterprise_hook('remote_pandora_process_user_login', [$login, $pass]) === false) {
                $config['auth_error'] = 'User not found in database or incorrect password';
                return false;
            }
        break;

        // Remote Integria
        case 'integria':
            if (enterprise_hook('remote_integria_process_user_login', [$login, $pass]) === false) {
                $config['auth_error'] = 'User not found in database or incorrect password';
                return false;
            }
        break;

        // Unknown authentication method
        default:
            $config['auth_error'] = 'User not found in database 
					or incorrect password';
        return false;
            break;
    }

    if ($config['auth'] === 'ldap') {
        $login_user_attribute = $login;
        if ($config['ldap_login_user_attr'] == 'mail') {
            $login = $sr['mail'][0];
        }
    }

    // Authentication ok, check if the user exists in the local database
    if (is_user($login)) {
        if (!user_can_login($login)) {
            return false;
        }

        if (($config['auth'] === 'ad')
            && (isset($config['ad_advanced_config']) && $config['ad_advanced_config'])
        ) {
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
            } else {
                if ($return === 'permissions_changed') {
                    $config['auth_error'] = __('Your permissions have changed. Please, login again.');
                    return false;
                }
            }
        } else if ($config['auth'] === 'ldap') {
            // Check if autocreate  remote users is active.
            if ($config['autocreate_remote_users'] == 1) {
                if ($config['ldap_save_password']) {
                    $update_credentials = change_local_user_pass_ldap($login, $pass);

                    if ($update_credentials) {
                        $config['auth_error'] = __('Your permissions have changed. Please, login again.');
                        return false;
                    }
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

                    if ($return === 'error_permissions') {
                        $config['auth_error'] = __('Problems with configuration permissions. Please contact with Administrator');
                        return false;
                    } else {
                        if ($return === 'permissions_changed') {
                            $config['auth_error'] = __('Your permissions have changed. Please, login again.');
                            return false;
                        }
                    }
                }
            }
        }

        return $login;
    }

    // The user does not exist and can not be created
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
        if (defined('METACONSOLE')) {
            enterprise_include_once('include/functions_metaconsole.php');
            enterprise_include_once('meta/include/functions_groups_meta.php');

            $return = groups_meta_synchronizing();

            if ($return['group_create_err'] > 0 || $return['group_update_err'] > 0) {
                $config['auth_error'] = __('Fail the group synchronizing');
                return false;
            }

            $return = meta_tags_synchronizing();
            if ($return['tag_create_err'] > 0 || $return['tag_update_err'] > 0) {
                $config['auth_error'] = __('Fail the tag synchronizing');
                return false;
            }
        }

        // Create the user
        if (enterprise_hook(
            'prepare_permissions_groups_of_user_ad',
            [
                $login,
                $pass,
                [
                    'fullname' => $login,
                    'comments' => 'Imported from '.$config['auth'],
                ],
                false,
                defined('METACONSOLE'),
            ]
        ) === false
        ) {
            $config['auth_error'] = __(
                'User not found in database 
					or incorrect password'
            );

            return false;
        }
    } else if ($config['auth'] === 'ldap') {
        if (defined('METACONSOLE')) {
            enterprise_include_once('include/functions_metaconsole.php');
            enterprise_include_once('meta/include/functions_groups_meta.php');

            $return = groups_meta_synchronizing();

            if ($return['group_create_err'] > 0 || $return['group_update_err'] > 0) {
                $config['auth_error'] = __('Fail the group synchronizing');
                return false;
            }

            $return = meta_tags_synchronizing();
            if ($return['tag_create_err'] > 0 || $return['tag_update_err'] > 0) {
                $config['auth_error'] = __('Fail the tag synchronizing');
                return false;
            }
        }

        $permissions = fill_permissions_ldap($sr);
        if (empty($permissions)) {
            $config['auth_error'] = __('User not found in database or incorrect password');
            return false;
        } else {
            $user_info['fullname'] = $sr['cn'][0];
            $user_info['email'] = $sr['mail'][0];

            // Create the user
            $create_user = create_user_and_permisions_ldap($login, $pass, $user_info, $permissions, defined('METACONSOLE'));
        }
    } else {
        $user_info = [
            'fullname' => $login,
            'comments' => 'Imported from '.$config['auth'],
        ];
        if (is_metaconsole() && $config['auth'] === 'ad') {
            $user_info['metaconsole_access_node'] = $config['ad_adv_user_node'];
        }

        // Create the user in the local database
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
        // TODO: Check the creation in the nodes
        if (is_metaconsole()) {
            enterprise_include_once('include/functions_metaconsole.php');
            enterprise_include_once('meta/include/functions_groups_meta.php');

            $return = groups_meta_synchronizing();

            if ($return['group_create_err'] > 0 || $return['group_update_err'] > 0) {
                $config['auth_error'] = __('Fail the group synchronizing');
                return false;
            }

            $return = meta_tags_synchronizing();
            if ($return['tag_create_err'] > 0 || $return['tag_update_err'] > 0) {
                $config['auth_error'] = __('Fail the tag synchronizing');
                return false;
            }

            $servers = metaconsole_get_servers();
            foreach ($servers as $server) {
                $perfil_maestro = db_get_row(
                    'tperfil',
                    'id_perfil',
                    $config['default_remote_profile']
                );

                if (metaconsole_connect($server) == NOERR) {
                    if (!profile_exist($perfil_maestro['name'])) {
                        unset($perfil_maestro['id_perfil']);
                        $id_profile = db_process_sql_insert('tperfil', $perfil_maestro);
                    } else {
                        $id_profile = db_get_value('id_perfil', 'tperfil', 'name', $perfil_maestro['name']);
                    }

                    if ($config['auth'] === 'ad') {
                        unset($user_info['metaconsole_access_node']);
                        $user_info['not_login'] = (int) !$config['ad_adv_user_node'];
                    }

                    if (create_user($login, $pass, $user_info) === false) {
                        continue;
                    }

                    profile_create_user_profile(
                        $login,
                        $id_profile,
                        $config['default_remote_group'],
                        false,
                        $config['default_assign_tags']
                    );
                }

                metaconsole_restore_db();
            }
        }
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
 * @param mixed User id.
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
 * @param mixed User id
 *
 * @return mixed An array of users
 */
function get_user_info($user)
{
    static $cache_user_info = [];
    if (array_key_exists($user, $cache_user_info)) {
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
 * @param string Field to order by (id_user, fullname or registered)
 *
 * @return array An array of user information
 */
function get_users($order='fullname', $filter=false, $fields=false)
{
    if (is_array($order)) {
        $filter['order'] = $order['field'].' '.$order['order'];
    } else {
        switch ($order) {
            case 'registered':
            case 'last_connect':
            case 'fullname':
            break;

            default:
                $order = 'fullname';
            break;
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
 * @param string User id
 */
function process_user_contact($id_user)
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
 * @return boolean false
 */
function create_user($id_user, $password, $user_info)
{
    $values = $user_info;
    $values['id_user'] = $id_user;
    $values['password'] = md5($password);
    $values['last_connect'] = 0;
    $values['registered'] = get_system_time();

    return (@db_process_sql_insert('tusuario', $values)) !== false;
}


/**
 * Save password history
 *
 * @return boolean false
 */
function save_pass_history($id_user, $password)
{
    $values['id_user'] = $id_user;
    $values['password'] = md5($password);
    $values['date_begin'] = date('Y/m/d H:i:s', get_system_time());

    return (@db_process_sql_insert('tpassword_history', $values)) !== false;
}


/**
 * Deletes the user
 *
 * @param string User id
 */
function delete_user($id_user)
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

    return true;
}


/**
 * Update the password in MD5 for user pass as id_user with
 * password in plain text.
 *
 * @param string user User ID
 * @param string password Password in plain text.
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function update_user_password($user, $password_new)
{
    global $config;
    if (isset($config['auth']) && $config['auth'] == 'pandora') {
        $sql = sprintf(
            "UPDATE tusuario SET password = '".md5($password_new)."', last_pass_change = '".date('Y-m-d H:i:s', get_system_time())."' WHERE id_user = '".$user."'"
        );

        $connection = mysql_connect_db(
            $config['rpandora_server'],
            $config['rpandora_dbname'],
            $config['rpandora_user'],
            $config['rpandora_pass']
        );
        $remote_pass_update = db_process_sql($sql, 'affected_rows', $connection);

        if (!$remote_pass_update) {
            $config['auth_error'] = __('Could not changes password on remote pandora');
            return false;
        }
    }

    return db_process_sql_update(
        'tusuario',
        [
            'password'         => md5($password_new),
            'last_pass_change' => date('Y/m/d H:i:s', get_system_time()),
        ],
        ['id_user' => $user]
    );
}


/**
 * Update the data of a user that user is choose with
 * id_user.
 *
 * @param string user User ID
 * @param array values Associative array with index as name of field and content.
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function update_user($id_user, $values)
{
    if (! is_array($values)) {
        return false;
    }

    return db_process_sql_update('tusuario', $values, ['id_user' => $id_user]);
}


/**
 * Authenticate against an LDAP server.
 *
 * @param string User login
 * @param string User password (plain text)
 *
 * @return boolean True if the login is correct, false in other case
 */
function ldap_process_user_login($login, $password)
{
    global $config;

    if (! function_exists('ldap_connect')) {
        $config['auth_error'] = __('Your installation of PHP does not support LDAP');

        return false;
    }

    // Connect to the LDAP server
    $ds = @ldap_connect($config['ldap_server'], $config['ldap_port']);

    if (!$ds) {
        $config['auth_error'] = 'Error connecting to LDAP server';

        return false;
    }

    // Set the LDAP version
    ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, $config['ldap_version']);

    if ($config['ldap_start_tls']) {
        if (!@ldap_start_tls($ds)) {
            $config['auth_error'] = 'Could not start TLS for LDAP connection';
            @ldap_close($ds);

            return false;
        }
    }

    if ($config['ldap_function'] == 'local') {
        $sr = local_ldap_search(
            $config['ldap_server'],
            $config['ldap_port'],
            $config['ldap_version'],
            io_safe_output($config['ldap_base_dn']),
            $config['ldap_login_attr'],
            io_safe_output($config['ldap_admin_login']),
            $config['ldap_admin_pass'],
            io_safe_output($login)
        );

        if ($sr) {
            $user_dn = $sr['dn'][0];

            $ldap_base_dn = !empty($config['ldap_base_dn']) ? ','.io_safe_output($config['ldap_base_dn']) : '';

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
        if ($config['ldap_admin_login'] != '' && $config['ldap_admin_pass'] != '') {
            if (!@ldap_bind($ds, io_safe_output($config['ldap_admin_login']), $config['ldap_admin_pass'])) {
                $config['auth_error'] = 'Admin ldap connection fail';
                @ldap_close($ds);
                return false;
            }
        }

        $filter = '('.$config['ldap_login_attr'].'='.io_safe_output($login).')';

        $sr = ldap_search($ds, io_safe_output($config['ldap_base_dn']), $filter);

        $memberof = ldap_get_entries($ds, $sr);

        if ($memberof['count'] == 0 && !isset($memberof[0]['memberof'])) {
            @ldap_close($ds);
            return false;
        } else {
            $memberof = $memberof[0];
        }

        unset($memberof['count']);
        $ldap_base_dn = !empty($config['ldap_base_dn']) ? ','.io_safe_output($config['ldap_base_dn']) : '';

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

    if ($config['ldap_save_password']) {
        $values['password'] = md5($password);
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
    if (md5($password) !== $local_user_pass) {
        $values_update = [];
        $values_update['password'] = md5($password);

        $return = db_process_sql_update('tusuario', $values_update, ['id_user' => $id_user]);
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


function local_ldap_search($ldap_host, $ldap_port=389, $ldap_version=3, $dn, $access_attr, $ldap_admin_user, $ldap_admin_pass, $user)
{
    global $config;

    $filter = '';
    if (!empty($access_attr) && !empty($user)) {
        $filter = " -s sub '(".$access_attr.'='.$user.")' ";
    }

    $tls = '';
    if ($config['ldap_start_tls']) {
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
        $ldap_admin_pass = ' -w '.$ldap_admin_pass;
    }

    $dn = " -b '".$dn."'";

    $shell_ldap_search = explode("\n", shell_exec('ldapsearch -LLL -o ldif-wrap=no -x'.$ldap_host.$ldap_version.' -E pr=10000/noprompt '.$ldap_admin_user.$ldap_admin_pass.$dn.$filter.$tls.' | grep -v "^#\|^$" | sed "s/:\+ /=>/g"'));
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
