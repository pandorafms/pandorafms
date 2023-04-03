<?php
/**
 * Consoles manager.
 *
 * @category   Tools
 * @package    Pandora FMS
 * @subpackage Enterprise
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

use PandoraFMS\Console;
use PandoraFMS\View;

// Begin.
global $config;
check_login();

if (check_acl($config['id_user'], 0, 'PM') === false
    && is_user_admin($config['id_user']) === false
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Consoles Management'
    );
    include 'general/noaccess.php';
    exit;
}

$ajax_url = ui_get_full_url('ajax.php');

$message = '';
$error = false;

// Check is any consoles are registered.
$results = db_get_all_rows_in_table('tconsole');

$message = '';

View::render(
    'consoles/list',
    [
        'ajax_url' => $ajax_url,
        'message'  => $message,
    ]
);

if ($results === false) {
    $message = ui_print_info_message(
        __('If you want to have your consoles registered, you must define them by editing config.php in each individual console and wait for cron to run in order to be registered.')
    );
}
