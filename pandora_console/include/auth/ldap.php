<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
		<div id="main" class="float-left mrgn_lft_100px">
			<div align="center">
				<div id="login_f">
					<h1 id="log_f" class="error">You cannot access this file</h1>
					<div>
						<img src="../../images/pandora_logo.png" border="0"></a>
					</div>
					<div class="msg">
						<span class="error"><b>ERROR:</b>
						You can\'t access this file directly!</span>
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

$config['user_can_update_info'] = false;
$config['user_can_update_password'] = false;
$config['admin_can_add_user'] = false;
$config['admin_can_delete_user'] = false;
$config['admin_can_disable_user'] = false;
// Not implemented
$config['admin_can_make_admin'] = false;

// Required and optional keys for this function to work
$req_keys = [
    'ldap_server',
    'ldap_base_dn',
    'ldap_login_attr',
    'ldap_admin_group_name',
    'ldap_admin_group_attr',
    'ldap_admin_group_type',
    'ldap_user_filter',
    'ldap_user_attr',
];
$opt_keys = [
    'ldap_port',
    'ldap_start_tls',
    'ldap_version',
    'ldap_admin_dn',
    'ldap_admin_pwd',
];

global $ldap_cache;
// Needs to be globalized because config_process_config () function calls this file first and the variable would be local and subsequently lost
$ldap_cache = [];
$ldap_cache['error'] = '';
$ldap_cache['ds'] = '';

// Put each required key in a variable.
foreach ($req_keys as $key) {
    if (!isset($config['auth'][$key])) {
        user_error('Required key '.$key.' not set', E_USER_ERROR);
    }
}

// Convert group name to lower case to prevent problems
$config['auth']['ldap_admin_group_attr'] = strtolower($config['auth']['ldap_admin_group_attr']);
$config['auth']['ldap_admin_group_type'] = strtolower($config['auth']['ldap_admin_group_type']);

foreach ($opt_keys as $key) {
    if (!isset($config['auth'][$key])) {
        switch ($key) {
            case 'ldap_start_tls':
                $config['auth'][$key] = false;
            continue;

            case 'ldap_version':
                $config['auth'][$key] = 0;
            continue;

            case 'ldap_admin_dn':
            case 'ldap_admin_pwd':
                $config['auth'][$key] = '';
            continue;

            default:
                // Key not implemented
            continue;
        }
    }
}

// Reference the global use authorization error to last ldap error.
$config['auth_error'] = &$ldap_cache['error'];

unset($req_keys, $opt_keys);


/**
 * process_user_login accepts $login and $pass and handles it according to current authentication scheme
 *
 * @param string $login
 * @param string $pass
 *
 * @return mixed False in case of error or invalid credentials, the username in case it's correct.
 */
function process_user_login($login, $pass)
{
    if (!ldap_valid_login($login, $pass)) {
        return false;
    }

    global $config;

    $profile = db_get_value('id_usuario', 'tusuario_perfil', 'id_usuario', $login);

    if ($profile === false && empty($config['auth']['create_user_undefined'])) {
        $config['auth_error'] = 'No profile';
        // Error message, don't translate
        return false;
        // User doesn't have a profile so doesn't have access
    } else if ($profile === false && !empty($config['auth']['create_user_undefined'])) {
        $ret = profile_create_user_profile($login);
        // User doesn't have a profile but we are asked to create one
        if ($ret === false) {
            $config['auth_error'] = 'Profile creation failed';
            // Error message, don't translate
            return false;
            // We couldn't create the profile for some or another reason
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
function is_user_admin($user_id)
{
    $admins = get_user_admins();

    if (in_array($user_id, $admins)) {
        return true;
    }

    return false;
}


/**
 * Checks if a user exists
 *
 * @param string User id.
 *
 * @return boolean True if the user exists
 */
function is_user($id_user)
{
    $user = get_user_info($id_user);

    if (empty($user)) {
        return false;
    }

    return true;
}


/**
 * Gets the users real name
 *
 * @param string User id.
 *
 * @return string The users full name
 */
function get_user_fullname($id_user)
{
    $info = get_user_info($id_user);
    if (empty($info)) {
        // User doesn't exist
        return '';
    }

    return (string) $info['fullname'];
}


/**
 * Gets the users email
 *
 * @param string User id.
 *
 * @return string The users email address
 */
function get_user_email($id_user)
{
    $info = get_user_info($id_user);

    return (string) $info['email'];
}


/**
 * Get the user id field on a mixed structure.
 *
 * This function is needed to make auth system more compatible and independant.
 *
 * @param mixed User structure to get id. It might be a row returned from
 * tusuario or tusuario_perfil. If it's not a row, the int value is returned.
 */
function get_user_id($user)
{
    if (is_array($user)) {
        // FIXME: Is this right?
        return $user['id_user'];
    }

    return (int) $user;
}


/**
 * Gets the users info
 *
 * @param string User id.
 *
 * @return array User info
 */
function get_user_info($id_user)
{
    global $ldap_cache;

    if (!empty($ldap_cache[$id_user])) {
        return $ldap_cache[$id_user];
    }

    $ldap_cache[$id_user] = ldap_load_user($id_user);

    if ($ldap_cache[$id_user] === false) {
        return [];
    }

    return $ldap_cache[$id_user];
}


/**
 * Get all users that are defined in the admin group in LDAP
 *
 * @return array Array of users or empty array
 */
function get_user_admins()
{
    global $ldap_cache, $config;

    if (! empty($ldap_cache['cached_admins'])) {
        return $ldap_cache['cached_admins'];
    } else {
        $ldap_cache['cached_admins'] = [];
    }

    if (ldap_connect_bind()) {
        $search_filter = '('.$config['auth']['ldap_admin_group_attr'].'=*)';
        $sr = ldap_search($ldap_cache['ds'], $config['auth']['ldap_admin_group_name'], $search_filter, [$config['auth']['ldap_admin_group_attr']]);
        if (!$sr) {
            $ldap_cache['error'] .= 'Error searching LDAP server (get_user_admins): '.ldap_error($ldap_cache['ds']);
        } else {
            $admins = ldap_get_entries($ldap_cache['ds'], $sr);
            for ($x = 0; $x < $admins[0][$config['auth']['ldap_admin_group_attr']]['count']; $x++) {
                if ($config['auth']['ldap_admin_group_type'] != 'posixgroup') {
                    $ldap_cache['cached_admins'][] = stripdn($admins[0][$config['auth']['ldap_admin_group_attr']][$x]);
                } else {
                    $ldap_cache['cached_admins'][] = $admins[0][$config['auth']['ldap_admin_group_attr']][$x];
                }
            }

            @ldap_free_result($sr);
        }

        @ldap_close($ldap_cache['ds']);
    }

    return $ldap_cache['cached_admins'];
}


/**
 * Sets the last login for a user. LDAP doesn't have this (or it's inherent to the login process)
 *
 * @param string User id
 */
function process_user_contact($id_user)
{
    // Empty function
}


/**
 * LDAP user functions based on webcalendar's implementation
 *
 * File from webcalendar (GPL) project:
 * $Id: user-ldap.php,v 1.42.2.1 2007/08/17 14:39:00 umcesrjones Exp $
 *
 * Note: this application assumes that usernames (logins) are unique.
 */


/**
 * Function to search the dn for a given user. Error messages in $ldap_cache["error"];
 *
 * @param string User login
 *
 * @return mixed The DN if the user is found, false in other case
 */
function ldap_search_user($login)
{
    global $ldap_cache, $config;

    $nick = false;
    if (ldap_connect_bind()) {
        $sr = @ldap_search(
            $ldap_cache['ds'],
            io_safe_output($config['auth']['ldap_base_dn']),
            '(&('.io_safe_output($config['auth']['ldap_login_attr']).'='.$login.')'.io_safe_output($config['auth']['ldap_user_filter']).')',
            array_values($config['auth']['ldap_user_attr'])
        );

        if (!$sr) {
            $ldap_cache['error'] .= 'Error searching LDAP server: '.ldap_error($ldap_cache['ds']);
        } else {
            $info = @ldap_get_entries($ldap_cache['ds'], $sr);
            if ($info['count'] != 1) {
                $ldap_cache['error'] .= 'Invalid user';
            } else {
                $nick = $info[0]['dn'];
            }

            @ldap_free_result($sr);
        }

        @ldap_close($ldap_cache['ds']);
    }

    return $nick;
}


/**
 * Function to validate the user and password for a given login. Error messages in $ldap_cache["error"];
 *
 * @param string User login
 * @param string User password (plain text)
 *
 * @return boolean True if the login is correct, false in other case
 */
function ldap_valid_login($login, $password)
{
    global $ldap_cache, $config;

    if (! function_exists('ldap_connect')) {
        die('Your installation of PHP does not support LDAP');
    }

    $ret = false;
    if (!empty($config['auth']['ldap_port'])) {
        $ds = @ldap_connect($config['auth']['ldap_server'], $config['auth']['ldap_port']);
        // Since this is a separate bind, we don't store it global
    } else {
        $ds = @ldap_connect($config['auth']['ldap_server']);
        // Since this is a separate bind we don't store it global
    }

    if ($ds) {
        if ($config['auth']['ldap_version'] > 0) {
            ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, $config['auth']['ldap_version']);
        }

        if ($config['auth']['ldap_start_tls'] && !@ldap_start_tls($ds)) {
            $ldap_cache['error'] .= 'Could not start TLS for LDAP connection';
            return $ret;
        }

        $r = @ldap_bind($ds, io_safe_output($config['auth']['ldap_login_attr']).'='.$login.','.io_safe_output($config['auth']['ldap_base_dn']), $password);
        if (!$r) {
            $ldap_cache['error'] .= 'Invalid login';
        } else {
            $ret = true;
        }

        @ldap_close($ds);
    } else {
        $ldap_cache['error'] .= 'Error connecting to LDAP server';
    }

    return $ret;
}


/**
 * Function to load user information according to PandoraFMS structure. Error messages in $ldap_cache["error"];
 *
 * @param string User login
 *
 * @return mixed Array with the information, false in other case
 */
function ldap_load_user($login)
{
    global $ldap_cache, $config;

    $ret = false;
    $time = get_system_time();
    if (ldap_connect_bind()) {
        $sr = ldap_search(
            $ldap_cache['ds'],
            io_safe_output($config['auth']['ldap_base_dn']),
            '(&('.io_safe_output($config['auth']['ldap_login_attr']).'='.$login.')'.io_safe_output($config['auth']['ldap_user_filter']).')',
            array_values($config['auth']['ldap_user_attr'])
        );

        if (!$sr) {
            $ldap_cache['error'] .= 'Error searching LDAP server (load_user): '.ldap_error($ldap_cache['ds']);
        } else {
            $info = @ldap_get_entries($ldap_cache['ds'], $sr);
            if ($info['count'] != 1) {
                $ldap_cache['error'] .= 'Invalid login';
                // $ldap_cache["error"] .= ', could not load user'; //Uncomment for debugging
            } else {
                $ret = [];
                foreach ($config['auth']['ldap_user_attr'] as $internal_key => $ldap_key) {
                    $ret['last_connect'] = $time;
                    $ret['registered'] = $time;
                    $ret['is_admin'] = is_user_admin($info[0][$config['auth']['ldap_user_attr']['id_user']][0]);
                    if (isset($info[0][$ldap_key])) {
                        $ret[$internal_key] = $info[0][$ldap_key][0];
                    } else {
                        $ret[$internal_key] = '';
                    }
                }
            }

            @ldap_free_result($sr);
        }

        @ldap_close($ldap_cache['ds']);
    } else {
        $ldap_cache['error'] .= 'Could not connect to LDAP server';
    }

    return $ret;
}


/**
 * Function to create a new user. We don't do LDAP admin in Pandora, so not implemented.
 *
 * @return boolean false
 */
function create_user()
{
    global $ldap_cache;

    $ldap_cache['error'] .= 'Creating users not supported.';
    return false;
}


/**
 * Function to update a user. We don't do LDAP admin in Pandora, so not implemented.
 *
 * @return boolean false
 */
function process_user()
{
    global $ldap_cache;

    $ldap_cache['error'] .= 'Updating users not supported.';
    return false;
}


/**
 * Function to update a user password. We don't do LDAP admin in Pandora, so not implemented.
 *
 * @return boolean false
 */
function update_user_password($user, $password_old, $password_new)
{
    global $ldap_cache;

    $ldap_cache['error'] = 'Changing passwords not supported';
    return false;
}


/**
 * Delete a user (preferences etc.) from the pandora database (NOT from LDAP)
 *
 * @param string $user User to delete
 *
 * @return boolean True if successfully deleted, false otherwise
 */
function delete_user($user)
{
    global $ldap_cache;

    $ldap_cache['error'] = 'Deleting users not supported';
    return false;
}


/**
 * Function to get all users (for LDAP this also includes the admin users which you have to get separate)
 *
 * @param string Order currently not done for LDAP
 *
 * @return array List if successful, empty array otherwise
 */
function get_users($order=false)
{
    global $ldap_cache, $config;

    if (!empty($ldap_cache['cached_users'])) {
        return $ldap_cache['cached_users'];
    }

    $ldap_cache['cached_users'] = [];
    $time = get_system_time();

    if (ldap_connect_bind()) {
        $sr = @ldap_search($ldap_cache['ds'], io_safe_output($config['auth']['ldap_base_dn']), io_safe_output($config['auth']['ldap_user_filter']), array_values($config['auth']['ldap_user_attr']));
        if (!$sr) {
            $ldap_cache['error'] .= 'Error searching LDAP server (get_users): '.ldap_error($ldap_cache['ds']);
        } else {
            ldap_sort($ldap_cache['ds'], $sr, $config['auth']['ldap_user_attr']['fullname']);
            $info = @ldap_get_entries($ldap_cache['ds'], $sr);
            for ($i = 0; $i < $info['count']; $i++) {
                foreach ($config['auth']['ldap_user_attr'] as $internal_key => $ldap_key) {
                    $ret[$info[$i][$config['auth']['ldap_user_attr']['id_user']][0]]['last_connect'] = $time;
                    if (isset($info[$i][$ldap_key])) {
                        $ret[$info[$i][$config['auth']['ldap_user_attr']['id_user']][0]][$internal_key] = $info[$i][$ldap_key][0];
                    } else {
                        $ret[$info[$i][$config['auth']['ldap_user_attr']['id_user']][0]][$internal_key] = '';
                    }

                    $ret[$info[$i][$config['auth']['ldap_user_attr']['id_user']][0]]['is_admin'] = is_user_admin($info[$i][$config['auth']['ldap_user_attr']['id_user']][0]);
                }
            }

            @ldap_free_result($sr);
        }

        @ldap_close($ldap_cache['ds']);
    }

    // Admins are also users and since they can be in separate channels in LDAP, we merge them
    $ldap_cache['cached_users'] = $ret;

    return $ldap_cache['cached_users'];
}


/**
 * Strip everything but the username (uid) from a dn.
 * Example: path description
 * stripdn(uid=jeffh,ou=people,dc=example,dc=com) returns jeffh
 *
 * @param  string dn the dn you want to strip the uid from.
 * @return string userid
 */
function stripdn($dn)
{
    $array_explode  = explode(',', $dn, 2);
    $array_explode2 = explode('=', $array_explode[0]);
    return ($$array_explode2[1]);
}


/**
 * Connects and binds to the LDAP server
 * Tries to connect as $config["auth"]["ldap_admin_dn"] if we set it.
 *
 * @return boolean Bind result or false
 */
function ldap_connect_bind()
{
    global $ldap_cache, $config;

    if (! function_exists('ldap_connect')) {
        die('Your installation of PHP does not support LDAP');
    }

    $ret = false;

    if (!empty($config['auth']['ldap_port']) && !is_resource($ldap_cache['ds'])) {
        $ldap_cache['ds'] = @ldap_connect($config['auth']['ldap_server'], $config['auth']['ldap_port']);
    } else if (!is_resource($ldap_cache['ds'])) {
        $ldap_cache['ds'] = @ldap_connect($config['auth']['ldap_server']);
    } else {
        return true;
    }

    if ($ldap_cache['ds']) {
        if (!empty($config['auth']['ldap_version'])) {
            ldap_set_option($ldap_cache['ds'], LDAP_OPT_PROTOCOL_VERSION, $config['auth']['ldap_version']);
        }

        if (!empty($config['auth']['ldap_start_tls'])) {
            if (!ldap_start_tls($ldap_cache['ds'])) {
                $ldap_cache['error'] .= 'Could not start TLS for LDAP connection';
                return $ret;
            }
        }

        if (!empty($config['auth']['ldap_admin_dn'])) {
            $r = @ldap_bind($ldap_cache['ds'], $config['auth']['ldap_admin_dn'], $config['auth']['ldap_admin_pwd']);
        } else {
            $r = @ldap_bind($ldap_cache['ds']);
        }

        if (!$r) {
            $ldap_cache['error'] .= 'Invalid bind login for LDAP Server or (in case of OpenLDAP 2.x) could not connect';
            return $ret;
        }

        return true;
    } else {
        $ldap_cache['error'] .= 'Error connecting to LDAP server';
        return $ret;
    }
}
