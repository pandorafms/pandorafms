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
define('AJAX', true);

if (!defined('__PAN_XHPROF__')) {
    define('__PAN_XHPROF__', 0);
}

if (__PAN_XHPROF__ === 1) {
    if (function_exists('tideways_xhprof_enable')) {
        tideways_xhprof_enable();
    }
}

if ((! file_exists('include/config.php')) || (! is_readable('include/config.php'))) {
    exit;
}

// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once 'include/config.php';
require_once 'include/functions.php';
require_once 'include/functions_db.php';
require_once 'include/auth/mysql.php';

// Hash login process
if (isset($_GET['loginhash'])) {
    $loginhash_data = get_parameter('loginhash_data', '');
    $loginhash_user = str_rot13(get_parameter('loginhash_user', ''));

    if ($config['loginhash_pwd'] != ''
        && $loginhash_data == md5($loginhash_user.io_output_password($config['loginhash_pwd']))
    ) {
        db_logon($loginhash_user, $_SERVER['REMOTE_ADDR']);
        $_SESSION['id_usuario'] = $loginhash_user;
        $config['id_user'] = $loginhash_user;
    } else {
        include_once 'general/login_page.php';
        db_pandora_audit('Logon Failed (loginhash', '', 'system');
        while (@ob_end_flush()) {
        }

        exit('</html>');
    }
}

$public_hash = get_parameter('hash', false);

// Check user.
if ($public_hash == false) {
    check_login();
} else {
    enterprise_include_once('include/functions_dashboard.php');
    if (dashboard_check_public_hash($public_hash) === false) {
        db_pandora_audit('Invalid public hash', 'Trying to access public dashboard');
        include 'general/noaccess.php';
        exit;
    }
}



// Enterprise support.
if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php')) {
    include_once ENTERPRISE_DIR.'/load_enterprise.php';
}

$config['remote_addr'] = $_SERVER['REMOTE_ADDR'];

$page = (string) get_parameter('page');
$page = safe_url_extraclean($page);
$page .= '.php';
$config['id_user'] = $_SESSION['id_usuario'];
$isFunctionSkins = enterprise_include_once('include/functions_skins.php');
if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
    $config['relative_path'] = enterprise_hook('skins_set_image_skin_path', [$config['id_user']]);
}

if (is_metaconsole()) {
    // Backward compatibility.
    define('METACONSOLE', true);
}

if (file_exists($page)) {
    include_once $page;
} else {
    echo '<br /><b class="error">Sorry! I can\'t find the page '.$page.'!</b>';
}

if (__PAN_XHPROF__ === 1) {
    pandora_xhprof_display_result('ajax', 'console');
}
