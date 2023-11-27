<?php
/**
 * Extension to self monitor Pandora FMS Console
 *
 * @category   Main page
 * @package    Pandora FMS
 * @subpackage Introduction
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

use PandoraFMS\TacticalView\GeneralTacticalView;

 // Config functions.
 require_once 'include/config.php';

 // This solves problems in enterprise load.
 global $config;

 check_login();
 // ACL Check.
if (check_acl($config['id_user'], 0, 'AR') === 0) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Default view'
    );
    include 'general/noaccess.php';
    exit;
}

$tacticalView = new GeneralTacticalView();
$tacticalView->render();
