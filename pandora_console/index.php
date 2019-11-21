<?php
/**
 * Index.
 *
 * @category   Main entrypoint.
 * @package    Pandora FMS
 * @subpackage Opensource.
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
if (!defined('__PAN_XHPROF__')) {
    define('__PAN_XHPROF__', 0);
}

if (__PAN_XHPROF__ === 1) {
    if (function_exists('tideways_xhprof_enable')) {
        tideways_xhprof_enable();
    } else {
        error_log('Cannot find tideways_xhprof_enable function');
    }
}

// Set character encoding to UTF-8
// fixes a lot of multibyte character issues.
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// Set to 1 to do not check for installer or config file (for development!).
// Activate gives more error information, not useful for production sites.
$develop_bypass = 0;

if ($develop_bypass != 1) {
    // If no config file, automatically try to install.
    if (! file_exists('include/config.php')) {
        if (! file_exists('install.php')) {
            $url = explode('/', $_SERVER['REQUEST_URI']);
            $flag_url = 0;
            foreach ($url as $key => $value) {
                if (strpos($value, 'index.php') !== false || $flag_url) {
                    $flag_url = 1;
                    unset($url[$key]);
                } else if (strpos($value, 'enterprise') !== false || $flag_url) {
                    $flag_url = 1;
                    unset($url[$key]);
                }
            }

            $config['homeurl'] = rtrim(join('/', $url), '/');
            $config['homeurl_static'] = $config['homeurl'];
            $login_screen = 'error_noconfig';
            $ownDir = dirname(__FILE__).DIRECTORY_SEPARATOR;
            $config['homedir'] = $ownDir;
            include 'general/error_screen.php';
            exit;
        } else {
            include 'install.php';
            exit;
        }
    }

    if (filesize('include/config.php') == 0) {
        include 'install.php';
        exit;
    }

    if (isset($_POST['rename_file'])) {
        $rename_file_install = (bool) $_POST['rename_file'];
        if ($rename_file_install) {
            $salida_rename = rename('install.php', 'install_old.php');
        }
    }

    // Check installer presence.
    if (file_exists('install.php')) {
        $login_screen = 'error_install';
        include 'general/error_screen.php';
        exit;
    }

    // Check perms for config.php.
    if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
        if ((substr(sprintf('%o', fileperms('include/config.php')), -4) != '0600')
            && (substr(sprintf('%o', fileperms('include/config.php')), -4) != '0660')
            && (substr(sprintf('%o', fileperms('include/config.php')), -4) != '0640')
        ) {
            $url = explode('/', $_SERVER['REQUEST_URI']);
            $flag_url = 0;
            foreach ($url as $key => $value) {
                if (strpos($value, 'index.php') !== false || $flag_url) {
                    $flag_url = 1;
                    unset($url[$key]);
                } else if (strpos($value, 'enterprise') !== false || $flag_url) {
                    $flag_url = 1;
                    unset($url[$key]);
                }
            }

            $config['homeurl'] = rtrim(join('/', $url), '/');
            $config['homeurl_static'] = $config['homeurl'];
            $ownDir = dirname(__FILE__).DIRECTORY_SEPARATOR;
            $config['homedir'] = $ownDir;
            $login_screen = 'error_perms';
            include 'general/error_screen.php';
            exit;
        }
    }
}

if ((! file_exists('include/config.php'))
    || (! is_readable('include/config.php'))
) {
    $login_screen = 'error_noconfig';
    include 'general/error_screen.php';
    exit;
}

/*
 * DO NOT CHANGE ORDER OF FOLLOWING REQUIRES.
 */

require_once 'include/config.php';
require_once 'include/functions_config.php';

if (isset($config['error'])) {
    $login_screen = $config['error'];
    include 'general/error_screen.php';
    exit;
}

// If metaconsole activated, redirect to it.
if (is_metaconsole()) {
    header('Location: '.ui_get_full_url('index.php'));
    // Always exit after sending location headers.
    exit;
}

if (file_exists(ENTERPRISE_DIR.'/include/functions_login.php')) {
    include_once ENTERPRISE_DIR.'/include/functions_login.php';
}

if (!empty($config['https']) && empty($_SERVER['HTTPS'])) {
    $query = '';
    if (count($_REQUEST)) {
        // Some (old) browsers don't like the ?&key=var.
        $query .= '?1=1';
    }

    // We don't clean these variables up as they're only being passed along.
    foreach ($_GET as $key => $value) {
        if ($key == 1) {
            continue;
        }

        $query .= '&'.$key.'='.$value;
    }

    foreach ($_POST as $key => $value) {
        $query .= '&'.$key.'='.$value;
    }

    $url = ui_get_full_url($query);

    // Prevent HTTP response splitting attacks
    // http://en.wikipedia.org/wiki/HTTP_response_splitting.
    $url = str_replace("\n", '', $url);

    header('Location: '.$url);
    // Always exit after sending location headers.
    exit;
}

// Pure mode (without menu, header and footer).
$config['pure'] = (bool) get_parameter('pure');

// Auto Refresh page (can now be disabled anywhere in the script).
if (get_parameter('refr') != null) {
    $config['refr'] = (int) get_parameter('refr');
}

$delete_file = get_parameter('del_file');
if ($delete_file == 'yes_delete') {
    $salida_delete = shell_exec('rm /var/www/html/pandora_console/install.php');
}

ob_start();
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>'."\n";

// This starts the page head. In the callback function,
// $page['head'] array content will be processed into the head.
ob_start('ui_process_page_head');

// Enterprise main.
enterprise_include('index.php');

echo '<script type="text/javascript">';
    echo 'var dispositivo = navigator.userAgent.toLowerCase();';
    echo 'if( dispositivo.search(/iphone|ipod|ipad|android/) > -1 ){';
        echo 'document.location = "'.ui_get_full_url('/mobile').'";  }';
echo '</script>';

// This tag is included in the buffer passed to ui_process_page_head so
// technically it can be stripped.
echo '</head>'."\n";

require_once 'include/functions_themes.php';
ob_start('ui_process_page_body');

$config['remote_addr'] = $_SERVER['REMOTE_ADDR'];

$sec2 = get_parameter_get('sec2');
$sec2 = safe_url_extraclean($sec2);
$page = $sec2;
// Reference variable for old time sake.
$sec = get_parameter_get('sec');
$sec = safe_url_extraclean($sec);

$process_login = false;

// Update user password.
$change_pass = get_parameter_post('renew_password', 0);

if ($change_pass == 1) {
    $password_old = (string) get_parameter_post('old_password', '');
    $password_new = (string) get_parameter_post('new_password', '');
    $password_confirm = (string) get_parameter_post('confirm_new_password', '');
    $id = (string) get_parameter_post('login', '');

    $changed_pass = login_update_password_check($password_old, $password_new, $password_confirm, $id);
}

$minor_release_message = false;
$searchPage = false;
$search = get_parameter_get('head_search_keywords');
if (strlen($search) > 0) {
    $config['search_keywords'] = io_safe_input(trim(io_safe_output(get_parameter('keywords'))));
    // If not search category providad, we'll use an agent search.
    $config['search_category'] = get_parameter('search_category', 'all');
    if (($config['search_keywords'] != 'Enter keywords to search') && (strlen($config['search_keywords']) > 0)) {
        $searchPage = true;
    }
}

// Login process.
if (! isset($config['id_user'])) {
    // Clear error messages.
    unset($_COOKIE['errormsg']);
    setcookie('errormsg', null, -1);

    if (isset($_GET['login'])) {
        include_once 'include/functions_db.php';
        // Include it to use escape_string_sql function.
        $config['auth_error'] = '';
        // Set this to the error message from the authorization mechanism.
        $nick = get_parameter_post('nick');
        // This is the variable with the login.
        $pass = get_parameter_post('pass');
        // This is the variable with the password.
        $nick = db_escape_string_sql($nick);
        $pass = db_escape_string_sql($pass);

        // Since now, only the $pass variable are needed.
        unset($_GET['pass'], $_POST['pass'], $_REQUEST['pass']);

        // If the auth_code exists, we assume the user has come from
        // double authorization page.
        if (isset($_POST['auth_code'])) {
            $double_auth_success = false;

            // The double authentication is activated and the user has
            // surpassed the first step (the login).
            // Now the authentication code provided will be checked.
            if (isset($_SESSION['prepared_login_da'])) {
                if (isset($_SESSION['prepared_login_da']['id_user'])
                    && isset($_SESSION['prepared_login_da']['timestamp'])
                ) {
                    // The user has a maximum of 5 minutes to introduce
                    // the double auth code.
                    $dauth_period = SECONDS_2MINUTES;
                    $now = time();
                    $dauth_time = $_SESSION['prepared_login_da']['timestamp'];

                    if (($now - $dauth_period) < $dauth_time) {
                        // Nick.
                        $nick = $_SESSION['prepared_login_da']['id_user'];
                        // Code.
                        $code = (string) get_parameter_post('auth_code');

                        if (!empty($code)) {
                            $result = validate_double_auth_code($nick, $code);

                            if ($result === true) {
                                // Double auth success.
                                $double_auth_success = true;
                            } else {
                                // Screen.
                                $login_screen = 'double_auth';
                                // Error message.
                                $config['auth_error'] = __('Invalid code');

                                if (!isset($_SESSION['prepared_login_da']['attempts'])) {
                                    $_SESSION['prepared_login_da']['attempts'] = 0;
                                }

                                $_SESSION['prepared_login_da']['attempts']++;
                            }
                        } else {
                            // Screen.
                            $login_screen = 'double_auth';
                            // Error message.
                            $config['auth_error'] = __("The code shouldn't be empty");

                            if (!isset($_SESSION['prepared_login_da']['attempts'])) {
                                $_SESSION['prepared_login_da']['attempts'] = 0;
                            }

                            $_SESSION['prepared_login_da']['attempts']++;
                        }
                    } else {
                        // Expired login.
                        unset($_SESSION['prepared_login_da']);

                        // Error message.
                        $config['auth_error'] = __('Expired login');
                    }
                } else {
                    // If the code doesn't exist, remove the prepared login.
                    unset($_SESSION['prepared_login_da']);

                    // Error message.
                    $config['auth_error'] = __('Login error');
                }
            } else {
                // If $_SESSION['prepared_login_da'] doesn't exist, the user
                // must login again.
                // Error message.
                $config['auth_error'] = __('Login error');
            }

            // Remove the authenticator code.
            unset($_POST['auth_code'], $code);

            if (!$double_auth_success) {
                $login_failed = true;
                include_once 'general/login_page.php';
                db_pandora_audit(
                    'Logon Failed',
                    'Invalid double auth login: '.$_SERVER['REMOTE_ADDR'],
                    $_SERVER['REMOTE_ADDR']
                );
                while (@ob_end_flush()) {
                    // Dumping...
                    continue;
                }

                exit('</html>');
            }
        }

        $login_button_saml = get_parameter('login_button_saml', false);
        if (isset($double_auth_success) && $double_auth_success) {
            // This values are true cause there are checked before complete
            // the 2nd auth step.
            $nick_in_db = $_SESSION['prepared_login_da']['id_user'];
            $expired_pass = false;
        } else if (($config['auth'] == 'saml') && ($login_button_saml)) {
            $saml_configured = include_once $config['homedir'].'/'.ENTERPRISE_DIR.'/include/auth/saml.php';

            if (!$saml_configured) {
                include_once 'general/noaccesssaml.php';
            }

            $saml_user_id = saml_process_user_login();

            if (!$saml_user_id) {
                include_once 'general/noaccesssaml.php';
            }


            $nick_in_db = $saml_user_id;
            if (!$nick_in_db) {
                include_once $config['saml_path'].'simplesamlphp/lib/_autoload.php';
                $as = new SimpleSAML_Auth_Simple($config['saml_source']);
                $as->logout();
            }
        } else {
            // process_user_login is a virtual function which should be defined in each auth file.
            // It accepts username and password. The rest should be internal to the auth file.
            // The auth file can set $config["auth_error"] to an informative error output or reference their internal error messages to it
            // process_user_login should return false in case of errors or invalid login, the nickname if correct
            $nick_in_db = process_user_login($nick, $pass);

            $expired_pass = false;

            if (($nick_in_db != false) && ((!is_user_admin($nick)
                || $config['enable_pass_policy_admin']))
                && (file_exists(ENTERPRISE_DIR.'/load_enterprise.php'))
                && ($config['enable_pass_policy'])
            ) {
                include_once ENTERPRISE_DIR.'/include/auth/mysql.php';

                $blocked = login_check_blocked($nick);

                if ($blocked) {
                    include_once 'general/login_page.php';
                    db_pandora_audit('Password expired', 'Password expired: '.$nick, $nick);
                    while (@ob_end_flush()) {
                        // Dumping...
                        continue;
                    }

                    exit('</html>');
                }

                // Checks if password has expired.
                $check_status = check_pass_status($nick, $pass);

                switch ($check_status) {
                    case PASSSWORD_POLICIES_FIRST_CHANGE:
                        // First change.
                    case PASSSWORD_POLICIES_EXPIRED:
                        // Pass expired.
                        $expired_pass = true;
                        login_change_password($nick, '', $check_status);
                    break;

                    default:
                        // Ignore.
                    break;
                }
            }
        }

        if (($nick_in_db !== false) && $expired_pass) {
            // Login ok and password has expired.
            include_once 'general/login_page.php';
            db_pandora_audit(
                'Password expired',
                'Password expired: '.$nick,
                $nick
            );
            while (@ob_end_flush()) {
                // Dumping...
                continue;
            }

            exit('</html>');
        } else if (($nick_in_db !== false) && (!$expired_pass)) {
            // Login ok and password has not expired.
            // Double auth check.
            if ((!isset($double_auth_success)
                || !$double_auth_success)
                && is_double_auth_enabled($nick_in_db)
            ) {
                // Store this values in the session to know if the user login
                // was correct.
                $_SESSION['prepared_login_da'] = [
                    'id_user'   => $nick_in_db,
                    'timestamp' => time(),
                    'attempts'  => 0,
                ];

                // Load the page to introduce the double auth code.
                $login_screen = 'double_auth';
                include_once 'general/login_page.php';
                while (@ob_end_flush()) {
                    // Dumping...
                    continue;
                }

                exit('</html>');
            }

            // Login ok and password has not expired.
            $process_login = true;

            if (is_user_admin($nick)) {
                echo "<script type='text/javascript'>var process_login_ok = 1;</script>";
            } else {
                echo "<script type='text/javascript'>var process_login_ok = 0;</script>";
            }

            if (!isset($_GET['sec2']) && !isset($_GET['sec'])) {
                // Avoid the show homepage when the user go to
                // a specific section of pandora
                // for example when timeout the sesion.
                unset($_GET['sec2']);
                $_GET['sec'] = 'general/logon_ok';
                $home_page = '';
                if (isset($nick)) {
                    $user_info = users_get_user_by_id($nick);
                    $home_page = io_safe_output($user_info['section']);
                    $home_url = $user_info['data_section'];
                    if ($home_page != '') {
                        switch ($home_page) {
                            case 'Event list':
                                $_GET['sec'] = 'eventos';
                                $_GET['sec2'] = 'operation/events/events';
                            break;

                            case 'Group view':
                                $_GET['sec'] = 'estado';
                                $_GET['sec2'] = 'operation/agentes/group_view';
                            break;

                            case 'Alert detail':
                                $_GET['sec'] = 'estado';
                                $_GET['sec2'] = 'operation/agentes/alerts_status';
                            break;

                            case 'Tactical view':
                                $_GET['sec'] = 'estado';
                                $_GET['sec2'] = 'operation/agentes/tactical';
                            break;

                            case 'Default':
                            default:
                                $_GET['sec'] = 'general/logon_ok';
                            break;

                            case 'Dashboard':
                                $_GET['sec'] = 'reporting';
                                $_GET['sec2'] = ENTERPRISE_DIR.'/dashboard/main_dashboard';
                                $id_dashboard_select = db_get_value('id', 'tdashboard', 'name', $home_url);
                                $_GET['id_dashboard_select'] = $id_dashboard_select;
                                $_GET['d_from_main_page'] = 1;
                            break;

                            case 'Visual console':
                                $_GET['sec'] = 'network';
                                $_GET['sec2'] = 'operation/visual_console/index';
                            break;

                            case 'Other':
                                $home_url = io_safe_output($home_url);
                                $url_array = parse_url($home_url);
                                parse_str($url_array['query'], $res);
                                foreach ($res as $key => $param) {
                                    $_GET[$key] = $param;
                                }
                            break;
                        }
                    } else {
                        $_GET['sec'] = 'general/logon_ok';
                    }
                }
            }

            db_logon($nick_in_db, $_SERVER['REMOTE_ADDR']);
            $_SESSION['id_usuario'] = $nick_in_db;
            $config['id_user'] = $nick_in_db;

            // Check if connection goes through F5 balancer. If it does, then
            // don't call config_prepare_session() or user will be back to login
            // all the time.
            $prepare_session = true;
            foreach ($_COOKIE as $key => $value) {
                if (preg_match('/BIGipServer*/', $key)) {
                    $prepare_session = false;
                    break;
                }
            }

            if ($prepare_session) {
                 config_prepare_session();
            }

            if (is_user_admin($config['id_user'])) {
                // PHP configuration values.
                $PHPupload_max_filesize = config_return_in_bytes(
                    ini_get('upload_max_filesize')
                );
                $PHPmemory_limit = config_return_in_bytes(
                    ini_get('memory_limit')
                );
                $PHPmax_execution_time = ini_get('max_execution_time');

                if ($PHPmax_execution_time !== '0') {
                    set_time_limit(0);
                }

                $PHPupload_max_filesize_min = config_return_in_bytes('800M');

                if ($PHPupload_max_filesize < $PHPupload_max_filesize_min) {
                    ini_set('upload_max_filesize', config_return_in_bytes('800M'));
                }

                $PHPmemory_limit_min = config_return_in_bytes('500M');

                if ($PHPmemory_limit < $PHPmemory_limit_min && $PHPmemory_limit !== '-1') {
                    ini_set('memory_limit', config_return_in_bytes('500M'));
                }

                set_time_limit((int) $PHPmax_execution_time);
                ini_set('upload_max_filesize', $PHPupload_max_filesize);
                ini_set('memory_limit', $PHPmemory_limit);
            }

            // ==========================================================
            // -------- SET THE CUSTOM CONFIGS OF USER ------------------
            config_user_set_custom_config();
            // ==========================================================
            // Remove everything that might have to do with people's passwords or logins
            unset($pass, $login_good);

            $user_language = get_user_language($config['id_user']);

            $l10n = null;
            if (file_exists('./include/languages/'.$user_language.'.mo')) {
                $cacheFileReader = new CachedFileReader(
                    './include/languages/'.$user_language.'.mo'
                );
                $l10n = new gettext_reader($cacheFileReader);
                $l10n->load_tables();
            }
        } else {
            // Login wrong.
            $blocked = false;

            if ((!is_user_admin($nick) || $config['enable_pass_policy_admin'])
                && file_exists(ENTERPRISE_DIR.'/load_enterprise.php')
            ) {
                $blocked = login_check_blocked($nick);
            }

            if (!$blocked) {
                if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php')) {
                    // Checks failed attempts.
                    login_check_failed($nick);
                }

                $login_failed = true;
                include_once 'general/login_page.php';
                db_pandora_audit(
                    'Logon Failed',
                    'Invalid login: '.$nick,
                    $nick
                );
                while (@ob_end_flush()) {
                    // Dumping...
                    continue;
                }

                exit('</html>');
            } else {
                include_once 'general/login_page.php';
                db_pandora_audit(
                    'Logon Failed',
                    'Invalid login: '.$nick,
                    $nick
                );
                while (@ob_end_flush()) {
                    // Dumping...
                    continue;
                }

                exit('</html>');
            }
        }

        // Form the url.
        $query_params_redirect = $_GET;
        // Visual console do not want sec2.
        if ($home_page == 'Visual console') {
            unset($query_params_redirect['sec2']);
        }

        $redirect_url = '?logged=1';
        foreach ($query_params_redirect as $key => $value) {
            if ($key == 'login') {
                continue;
            }

            $redirect_url .= '&'.safe_url_extraclean($key).'='.safe_url_extraclean($value);
        }

        header('Location: '.ui_get_full_url('index.php'.$redirect_url));
        exit;
        // Always exit after sending location headers.
    } else if (isset($_GET['loginhash'])) {
        // Hash login process.
        $loginhash_data = get_parameter('loginhash_data', '');
        $loginhash_user = str_rot13(get_parameter('loginhash_user', ''));

        if ($config['loginhash_pwd'] != ''
            && $loginhash_data == md5(
                $loginhash_user.io_output_password($config['loginhash_pwd'])
            )
        ) {
            db_logon($loginhash_user, $_SERVER['REMOTE_ADDR']);
            $_SESSION['id_usuario'] = $loginhash_user;
            $config['id_user'] = $loginhash_user;
        } else {
            include_once 'general/login_page.php';
            db_pandora_audit('Logon Failed (loginhash', '', 'system');
            while (@ob_end_flush()) {
                // Dumping...
                    continue;
            }

            exit('</html>');
        }
    } else {
        // There is no user connected.
        if ($config['enterprise_installed']) {
            enterprise_include_once('include/functions_reset_pass.php');
        }

        $correct_pass_change = (boolean) get_parameter('correct_pass_change', 0);
        $reset = (boolean) get_parameter('reset', 0);
        $first = (boolean) get_parameter('first', 0);
        $reset_hash = get_parameter('reset_hash', '');

        if ($correct_pass_change) {
            $correct_reset_pass_process = '';
            $process_error_message = '';
            $pass1 = get_parameter('pass1');
            $pass2 = get_parameter('pass2');
            $id_user = get_parameter('id_user');

            if ($pass1 == $pass2) {
                $res = update_user_password($id_user, $pass1);
                if ($res) {
                    db_process_sql_insert(
                        'tsesion',
                        [
                            'id_sesion'   => '',
                            'id_usuario'  => $id_user,
                            'ip_origen'   => $_SERVER['REMOTE_ADDR'],
                            'accion'      => 'Reset&#x20;change',
                            'descripcion' => 'Successful reset password process ',
                            'fecha'       => date('Y-m-d H:i:s'),
                            'utimestamp'  => time(),
                        ]
                    );

                    $correct_reset_pass_process = __('Password changed successfully');

                    register_pass_change_try($id_user, 1);
                } else {
                    register_pass_change_try($id_user, 0);

                    $process_error_message = __('Failed to change password');
                }
            } else {
                register_pass_change_try($id_user, 0);

                $process_error_message = __('Passwords must be the same');
            }

            include_once 'general/login_page.php';
        } else {
            if ($reset_hash != '') {
                $hash_data = explode(':::', $reset_hash);
                $id_user = $hash_data[0];
                $codified_hash = $hash_data[1];

                $db_reset_pass_entry = db_get_value_filter('reset_time', 'treset_pass', ['id_user' => $id_user, 'cod_hash' => $id_user.':::'.$codified_hash]);
                $process_error_message = '';

                if ($db_reset_pass_entry) {
                    if (($db_reset_pass_entry + SECONDS_2HOUR) < time()) {
                        register_pass_change_try($id_user, 0);
                        $process_error_message = __('Too much time since password change request');
                        delete_reset_pass_entry($id_user);
                        include_once 'general/login_page.php';
                    } else {
                        delete_reset_pass_entry($id_user);
                        include_once 'enterprise/include/process_reset_pass.php';
                    }
                } else {
                    register_pass_change_try($id_user, 0);
                    $process_error_message = __('This user has not requested a password change');
                    include_once 'general/login_page.php';
                }
            } else {
                if (!$reset) {
                    include_once 'general/login_page.php';
                } else {
                    $user_reset_pass = get_parameter('user_reset_pass', '');
                    $error = '';
                    $mail = '';
                    $show_error = false;

                    if (!$first) {
                        if ($user_reset_pass == '') {
                            $reset = false;
                            $error = __('Id user cannot be empty');
                            $show_error = true;
                        } else {
                            $check_user = check_user_id($user_reset_pass);

                            if (!$check_user) {
                                $reset = false;
                                register_pass_change_try($user_reset_pass, 0);
                                $error = __('Error in reset password request');
                                $show_error = true;
                            } else {
                                $check_mail = check_user_have_mail($user_reset_pass);

                                if (!$check_mail) {
                                    $reset = false;
                                    register_pass_change_try($user_reset_pass, 0);
                                    $error = __('This user doesn\'t have a valid email address');
                                    $show_error = true;
                                } else {
                                    $mail = $check_mail;
                                }
                            }
                        }

                        $cod_hash = $user_reset_pass.'::::'.md5(rand(10, 1000000).rand(10, 1000000).rand(10, 1000000));

                        $subject = '['.io_safe_output(get_product_name()).'] '.__('Reset password');
                        $body = __('This is an automatically sent message for user ');
                        $body .= ' "<strong>'.$user_reset_pass.'"</strong>';
                        $body .= '<p />';
                        $body .= __('Please click the link below to reset your password');
                        $body .= '<p />';
                        $body .= '<a href="'.ui_get_full_url('index.php?reset_hash='.$cod_hash).'">'.__('Reset your password').'</a>';
                        $body .= '<p />';
                        $body .= get_product_name();
                        $body .= '<p />';
                        $body .= '<em>'.__('Please do not reply to this email.').'</em>';

                        $result = send_email_to_user($mail, $body, $subject);

                        if (!$result) {
                            $process_error_message = __('Error at sending the email');
                        } else {
                            send_token_to_db($user_reset_pass, $cod_hash);
                        }

                        include_once 'general/login_page.php';
                    } else {
                        include_once 'enterprise/include/reset_pass.php';
                    }
                }
            }
        }

        while (@ob_end_flush()) {
            // Dumping...
            continue;
        }

        exit('</html>');
    }
} else {
    if (isset($_GET['loginhash_data'])) {
        $loginhash_data = get_parameter('loginhash_data', '');
        $loginhash_user = str_rot13(get_parameter('loginhash_user', ''));
        $iduser = $_SESSION['id_usuario'];
        unset($_SESSION['id_usuario']);
        unset($iduser);

        if ($config['loginhash_pwd'] != ''
            && $loginhash_data == md5(
                $loginhash_user.io_output_password($config['loginhash_pwd'])
            )
        ) {
            db_logon($loginhash_user, $_SERVER['REMOTE_ADDR']);
            $_SESSION['id_usuario'] = $loginhash_user;
            $config['id_user'] = $loginhash_user;
        } else {
            include_once 'general/login_page.php';
            db_pandora_audit('Logon Failed (loginhash', '', 'system');
            while (@ob_end_flush()) {
                // Dumping...
                continue;
            }

            exit('</html>');
        }
    }

    $user_in_db = db_get_row_filter(
        'tusuario',
        ['id_user' => $config['id_user']],
        '*'
    );
    if ($user_in_db == false) {
        // Logout.
        $_REQUEST = [];
        $_GET = [];
        $_POST = [];
        $config['auth_error'] = __("User doesn\'t exist.");
        $iduser = $_SESSION['id_usuario'];
        unset($_SESSION['id_usuario']);
        unset($iduser);
        include_once 'general/login_page.php';
        while (@ob_end_flush()) {
            // Dumping...
            continue;
        }

        exit('</html>');
    } else {
        if (((bool) $user_in_db['is_admin'] === false)
            && ((bool) $user_in_db['not_login'] === true)
        ) {
            // Logout.
            $_REQUEST = [];
            $_GET = [];
            $_POST = [];
            $config['auth_error'] = __('User only can use the API.');
            $iduser = $_SESSION['id_usuario'];
            unset($_SESSION['id_usuario']);
            unset($iduser);
            include_once 'general/login_page.php';
            while (@ob_end_flush()) {
                // Dumping...
                continue;
            }

            exit('</html>');
        }
    }
}

// Enterprise support.
if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php')) {
    include_once ENTERPRISE_DIR.'/load_enterprise.php';
}

// Log off.
if (isset($_GET['bye'])) {
    include 'general/logoff.php';
    $iduser = $_SESSION['id_usuario'];

    $_SESSION = [];
    session_destroy();
    header_remove('Set-Cookie');
    setcookie(session_name(), $_COOKIE[session_name()], (time() - 4800), '/');

    if ($config['auth'] == 'saml') {
        include_once $config['saml_path'].'simplesamlphp/lib/_autoload.php';
        $as = new SimpleSAML_Auth_Simple('PandoraFMS');
        $as->logout();
    }

    while (@ob_end_flush()) {
        // Dumping...
        continue;
    }

    exit('</html>');
}

clear_pandora_error_for_header();

/*
 * ----------------------------------------------------------------------
 *  EXTENSIONS
 * ----------------------------------------------------------------------
 *
 * Load the basic configurations of extension and add extensions into menu.
 * Load here, because if not, some extensions not load well, I don't why.
 */

$config['logged'] = false;
extensions_load_extensions($process_login);

if ($process_login) {
    // Call all extensions login function.
    extensions_call_login_function();

    unset($_SESSION['new_update']);

    include_once 'include/functions_update_manager.php';
    enterprise_include_once('include/functions_update_manager.php');

    if ($config['autoupdate'] == 1) {
        if (enterprise_installed()) {
            $result = update_manager_check_online_enterprise_packages_available();
        } else {
            $result = update_manager_check_online_free_packages_available();
        }

        if ($result) {
            $_SESSION['new_update'] = 'new';
        }
    }

    // Set the initial global counter for chat.
    users_get_last_global_counter('session');

    $config['logged'] = true;
}

// ----------------------------------------------------------------------
// Get old parameters before navigation.
$old_sec = '';
$old_sec2 = '';
$old_page = '';
if (isset($_SERVER['HTTP_REFERER'])) {
    $old_page = $_SERVER['HTTP_REFERER'];
}

$chunks = explode('?', $old_page);
if (count($chunks) == 2) {
    $chunks = explode('&', $chunks[1]);

    foreach ($chunks as $chunk) {
        if (strstr($chunk, 'sec=') !== false) {
            $old_sec = str_replace('sec=', '', $chunk);
        }

        if (strstr($chunk, 'sec2=') !== false) {
            $old_sec = str_replace('sec2=', '', $chunk);
        }
    }
}

$_SESSION['new_chat'] = false;
if ($old_sec2 == 'operation/users/webchat') {
    users_get_last_global_counter('session');
}

if ($page == 'operation/users/webchat') {
    // Reload the global counter.
    users_get_last_global_counter('session');
}

if (isset($_SESSION['global_counter_chat'])) {
    $old_global_counter_chat = $_SESSION['global_counter_chat'];
} else {
    $old_global_counter_chat = users_get_last_global_counter('return');
}

$now_global_counter_chat = users_get_last_global_counter('return');

if ($old_global_counter_chat != $now_global_counter_chat) {
    if (!users_is_last_system_message()) {
        $_SESSION['new_chat'] = true;
    }
}

require_once 'general/register.php';

if (get_parameter('login', 0) !== 0) {
    if ((!isset($config['skip_login_help_dialog']) || $config['skip_login_help_dialog'] == 0)
        && $display_previous_popup === false
        && $config['initial_wizard'] == 1
    ) {
        include_once 'general/login_help_dialog.php';
    }

    $php_version = phpversion();
    $php_version_array = explode('.', $php_version);
    if ($php_version_array[0] < 7) {
        include_once 'general/php7_message.php';
    }
}

// Header.
if ($config['pure'] == 0) {
    echo '<div id="container"><div id="head">';
    include 'general/header.php';

    if ($config['menu_type'] == 'classic') {
        echo '</div><div id="page" class="page_classic"><div id="menu">';
    } else {
        echo '</div><div id="page" class="page_collapsed"><div id="menu">';
    }

    include 'general/main_menu.php';
    echo '</div>';
    echo '<button onclick="topFunction()" id="top_btn" title="Go to top"></button>';
} else {
    echo '<div id="main_pure">';
    // Require menu only to build structure to use it in ACLs.
    include 'operation/menu.php';
    include 'godmode/menu.php';
}

/*
 * Session locking concurrency speedup!
 * http://es2.php.net/manual/en/ref.session.php#64525
 */

session_write_close();


// Main block of content.
if ($config['pure'] == 0) {
    echo '<div id="main">';
}



// Page loader / selector.
if ($searchPage) {
    include 'operation/search_results.php';
} else {
    if ($page != '') {
        $main_sec = get_sec($sec);
        if ($main_sec == false) {
            if ($sec == 'extensions') {
                $main_sec = get_parameter('extension_in_menu');
                if (empty($main_sec) === true) {
                    $main_sec = $sec;
                }
            } else if ($sec == 'gextensions') {
                    $main_sec = get_parameter('extension_in_menu');
            } else {
                $main_sec = $sec;
            }

            $sec = $sec2;
            $sec2 = '';
        }

        $page .= '.php';

        // Enterprise ACL check.
        if (enterprise_hook(
            'enterprise_acl',
            [
                $config['id_user'],
                $main_sec,
                $sec,
                true,
                $sec2,
            ]
        ) == false
        ) {
            include 'general/noaccess.php';
        } else {
            $sec = $main_sec;
            if (file_exists($page)) {
                if (! extensions_is_extension($page)) {
                    include_once $page;
                } else {
                    if ($sec[0] == 'g') {
                        extensions_call_godmode_function(basename($page));
                    } else {
                        extensions_call_main_function(basename($page));
                    }
                }
            } else {
                ui_print_error_message(__('Sorry! I can\'t find the page!'));
            }
        }
    } else {
        // Home screen chosen by the user.
        $home_page = '';
        if (isset($config['id_user'])) {
            $user_info = users_get_user_by_id($config['id_user']);
            $home_page = io_safe_output($user_info['section']);
            $home_url = $user_info['data_section'];
        }

        if ($home_page != '') {
            switch ($home_page) {
                case 'Event list':
                    $_GET['sec'] = 'eventos';
                    $_GET['sec2'] = 'operation/events/events';
                break;

                case 'Group view':
                    $_GET['sec'] = 'view';
                    $_GET['sec2'] = 'operation/agentes/group_view';
                break;

                case 'Alert detail':
                    $_GET['sec'] = 'view';
                    $_GET['sec2'] = 'operation/agentes/alerts_status';
                break;

                case 'Tactical view':
                    $_GET['sec'] = 'view';
                    $_GET['sec2'] = 'operation/agentes/tactical';
                break;

                case 'Default':
                default:
                    $_GET['sec2'] = 'general/logon_ok';
                break;

                case 'Dashboard':
                    $id_dashboard = db_get_value('id', 'tdashboard', 'name', $home_url);
                    $str = 'sec=reporting&sec2='.ENTERPRISE_DIR.'/dashboard/main_dashboard&id='.$id_dashboard.'&d_from_main_page=1';
                    parse_str($str, $res);
                    foreach ($res as $key => $param) {
                        $_GET[$key] = $param;
                    }
                break;

                case 'Visual console':
                    $id_visualc = db_get_value('id', 'tlayout', 'name', $home_url);
                    if (($home_url == '') || ($id_visualc == false)) {
                        $str = 'sec=network&sec2=operation/visual_console/index&refr=60';
                    } else {
                        $str = 'sec=network&sec2=operation/visual_console/render_view&id='.$id_visualc.'&refr=60';
                    }

                    parse_str($str, $res);
                    foreach ($res as $key => $param) {
                        $_GET[$key] = $param;
                    }
                break;

                case 'Other':
                    $home_url = io_safe_output($home_url);
                    $url_array = parse_url($home_url);
                    parse_str($url_array['query'], $res);
                    foreach ($res as $key => $param) {
                        $_GET[$key] = $param;
                    }
                break;
            }

            if (isset($_GET['sec2'])) {
                $file = $_GET['sec2'].'.php';
                // Translate some secs.
                $main_sec = get_sec($_GET['sec']);
                $_GET['sec'] = ($main_sec == false) ? $_GET['sec'] : $main_sec;
                if (!file_exists($file)
                    || ($_GET['sec2'] != 'general/logon_ok' && enterprise_hook(
                        'enterprise_acl',
                        [
                            $config['id_user'],
                            $_GET['sec'],
                            $_GET['sec2'],
                            true,
                            isset($_GET['sec3']) ? $_GET['sec3'] : '',
                        ]
                    ) == false)
                ) {
                    unset($_GET['sec2']);
                    include 'general/noaccess.php';
                } else {
                    include $file;
                }
            } else {
                include 'general/noaccess.php';
            }
        } else {
            include 'general/logon_ok.php';
        }
    }
}

if ($config['pure'] == 0) {
    echo '<div style="clear:both"></div>';
    echo '</div>';
    // Main.
    echo '<div style="clear:both">&nbsp;</div>';
    echo '</div>';
    // Page (id = page).
} else {
    echo '</div>';
    // Main pure.
}

echo '<div id="wiz_container">';
echo '</div>';

echo '<div id="um_msg_receiver">';
echo '</div>';

if ($config['pure'] == 0) {
    echo '</div>';
    // Container div.
    echo '</div>';
    echo '<div style="clear:both"></div>';

    echo '<div id="foot">';
    include 'general/footer.php';
}

// Clippy function.
require_once 'include/functions_clippy.php';
clippy_start($sec2);

while (@ob_end_flush()) {
    // Dumping...
    continue;
}

db_print_database_debug();
echo '</html>';

$run_time = format_numeric((microtime(true) - $config['start_time']), 3);
echo "\n<!-- Page generated in ".$run_time." seconds -->\n";

// Values from PHP to be recovered from JAVASCRIPT.
require 'include/php_to_js_values.php';


?>

<script type="text/javascript" language="javascript">

       // When there are less than 5 rows, all rows must be white
       var theme = "<?php echo $config['style']; ?>";
        if(theme === 'pandora'){
        if($('table.info_table tr').length < 5){
            $('table.info_table tbody > tr').css('background-color', '#fff');
        }
    }

    // When the user scrolls down 400px from the top of the document, show the
    // button.
    window.onscroll = function() {scrollFunction()};

    function scrollFunction() {
        if (document.body.scrollTop > 400 || document.documentElement.scrollTop > 400) {
            document.getElementById("top_btn").style.display = "block";
        } else {
            document.getElementById("top_btn").style.display = "none";
        }
    }

    // When the user clicks on the button, scroll to the top of the document.
    function topFunction() {

        /*
         * Safari.
         * document.body.scrollTop = 0;
         * For Chrome, Firefox, IE and Opera.
         * document.documentElement.scrollTop = 0; 
         */

        $("HTML, BODY").animate({ scrollTop: 0 }, 500);
    }

    // Initial load of page.
    $(document).ready(adjustFooter);
    
    // Every resize of window.
    $(window).resize(adjustFooter);
    
    // Every show/hide call may need footer re-layout.
    (function() {
        var oShow = jQuery.fn.show;
        var oHide = jQuery.fn.hide;
        
        jQuery.fn.show = function () {
            var rv = oShow.apply(this, arguments);
            adjustFooter();
            return rv;
        };
        jQuery.fn.hide = function () {
            var rv = oHide.apply(this, arguments);
            adjustFooter();
            return rv;
        };
    })();
    
    function force_run_register () {
        jQuery.post ("ajax.php",
            {
                "page": "general/register",
                "load_wizards": 'registration'
            },
            function (data) {
                $('#wiz_container').empty ()
                    .html (data);
                show_registration_wizard();
            },
            "html"
        );
    }

    function force_run_newsletter () {
        jQuery.post ("ajax.php",
            {
                "page": "general/register",
                "load_wizards": 'newsletter'
            },
            function (data) {
                $('#wiz_container').empty ()
                    .html (data);
                show_newsletter_wizard ();
            },
            "html"
        );
    }

    function first_time_identification () {
        jQuery.post ("ajax.php",
            {
                "page": "general/register",
                "load_wizards": 'initial'
            },
            function (data) {
                $('#wiz_container').empty ()
                    .html (data);
                run_configuration_wizard ();
            },
            "html"
        );

    }

    function show_modal(id) {
        var match = /notification-(.*)-id-([0-9]+)/.exec(id);
        if (!match) {
            console.error(
                "Cannot handle toast click event. Id not valid: ",
                event.target.id
            );
            return;
        }
        jQuery.post ("ajax.php",
            {
                "page": "godmode/setup/setup_notifications",
                "get_notification": 1,
                "id": match[2]
            },
            function (data) {
                notifications_hide();
                try {
                    var json = JSON.parse(data);
                    $('#um_msg_receiver')
                        .empty ()
                        .html (json.mensaje);

                    $('#um_msg_receiver').prop('title', json.subject);
                    
                    // Launch modal.
                    $("#um_msg_receiver").dialog({
                        resizable: true,
                        draggable: true,
                        modal: true,
                        width: 800,
                        buttons: [
                            {
                                text: "OK",
                                click: function() {
                                    $( this ).dialog( "close" );
                                }
                            }
                        ],
                        overlay: {
                                opacity: 0.5,
                                background: "black"
                            },
                        closeOnEscape: false,
                        open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); }
                    });

                    $(".ui-widget-overlay").css("background", "#000");
                    $(".ui-widget-overlay").css("opacity", 0.6);
                    $(".ui-draggable").css("cursor", "inherit");

                } catch (error) {
                    console.log(error);
                }

            },
            "html"
        );
    }

    //Dynamically assign footer position and width.
    function adjustFooter() {
        /*
        if (document.readyState !== 'complete' || $('#container').position() == undefined) {
            return;
        }
        // minimum top value (upper limit) for div#foot
        var ulim = $('#container').position().top + $('#container').outerHeight(true);
        // window height. $(window).height() returns wrong value on Opera and Google Chrome.
        var wh = document.documentElement.clientHeight;
        // save div#foot's height for latter use
        var h = $('#foot').height();
        // new top value for div#foot
        var t = (ulim + $('#foot').outerHeight() > wh) ? ulim : wh - $('#foot').outerHeight();
        /*
        if ($('#foot').position().top != t) {
            $('#foot').css({ position: "absolute", top: t, left: $('#foot').offset().left});
            $('#foot').height(h);
        }
        if ($('#foot').width() !=  $(window).width()) {
            $('#foot').width($(window).width());
        }
        */
    }
</script>
<?php
if (__PAN_XHPROF__ === 1) {
    pandora_xhprof_display_result('node_index');
}
