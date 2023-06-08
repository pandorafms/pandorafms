<?php
/**
 * Ajax handler.
 *
 * @category   Ajax handler.
 * @package    Pandora FMS.
 * @subpackage OpenSource.
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

// Begin.
require 'vendor/autoload.php';

define('AJAX', true);

if (defined('__PAN_XHPROF__') === false) {
    define('__PAN_XHPROF__', 0);
}

if (__PAN_XHPROF__ === 1) {
    if (function_exists('tideways_xhprof_enable') === true) {
        tideways_xhprof_enable();
    }
}

if (file_exists('include/config.php') === false
    || is_readable('include/config.php') === false
) {
    exit;
}

// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once 'include/config.php';
require_once 'include/functions.php';
require_once 'include/functions_db.php';
require_once 'include/auth/mysql.php';

if (isset($config['console_log_enabled']) === true
    && $config['console_log_enabled'] == 1
) {
    ini_set('log_errors', true);
    ini_set('error_log', $config['homedir'].'/log/console.log');
} else {
    ini_set('log_errors', false);
    ini_set('error_log', '');
}

// Sometimes input is badly retrieved from caller...
if (empty($_REQUEST) === true) {
    $data = explode('&', urldecode(file_get_contents('php://input')));
    foreach ($data as $d) {
        $r = explode('=', $d, 2);
        $_POST[$r[0]] = $r[1];
        $_GET[$r[0]] = $r[1];
    }
}

// Hash login process.
if (isset($_GET['loginhash']) === true) {
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
        db_pandora_audit(
            AUDIT_LOG_USER_REGISTRATION,
            'Loginhash failed',
            'system'
        );
        while (ob_get_length() > 0) {
            ob_end_flush();
        }

        exit('</html>');
    }
}

// Another auth class example: PandoraFMS\Dashboard\Manager.
$auth_class = io_safe_output(
    get_parameter('auth_class', 'PandoraFMS\User')
);

$page = (string) get_parameter('page');
$page = safe_url_extraclean($page);
$page .= '.php';
$page = realpath($page);
$public_hash = get_parameter('auth_hash', false);
$public_login = false;


if (false === ((bool) get_parameter('doLogin', false) === true
    && $page === realpath('include/rest-api/index.php'))
) {
    // Check user.
    if (class_exists($auth_class) === false || $public_hash === false) {
        check_login();
    } else {
        if ($auth_class::validatePublicHash($public_hash) === false) {
            db_pandora_audit(
                AUDIT_LOG_USER_REGISTRATION,
                'Trying to access public dashboard (Invalid public hash)'
            );
            include 'general/noaccess.php';
            exit;
        }

        // OK. Simulated user log in. If you want to use your own auth_class
        // remember to set $config['force_instant_logout'] to true to avoid
        // persistent user login.
    }
}

ob_start();

// Enterprise support.
if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php') === true) {
    include_once ENTERPRISE_DIR.'/load_enterprise.php';
}

$config['remote_addr'] = $_SERVER['REMOTE_ADDR'];

$config['id_user'] = $_SESSION['id_usuario'];
$isFunctionSkins = enterprise_include_once('include/functions_skins.php');
if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
    $config['relative_path'] = enterprise_hook(
        'skins_set_image_skin_path',
        [$config['id_user']]
    );
}

if (is_metaconsole() === true) {
    // Backward compatibility.
    define('METACONSOLE', true);
}

if (file_exists($page) === true) {
    include_once $page;
} else {
    echo '<br /><b class="error">Sorry! I can\'t find the page '.$page.'!</b>';
}

if (__PAN_XHPROF__ === 1) {
    pandora_xhprof_display_result('ajax', 'console');
}


if (isset($config['force_instant_logout']) === true
    && $config['force_instant_logout'] === true
) {
    // Force user logout.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $iduser = $_SESSION['id_usuario'];
    $_SESSION = [];
    session_destroy();
    header_remove('Set-Cookie');
    setcookie(session_name(), $_COOKIE[session_name()], (time() - 4800), '/');

    if ($config['auth'] === 'saml' && empty($public_hash) === true) {
        include_once $config['saml_path'].'simplesamlphp/lib/_autoload.php';
        $as = new SimpleSAML_Auth_Simple('PandoraFMS');
        $as->logout();
    }
}


while (ob_get_length() > 0) {
    ob_end_flush();
}
