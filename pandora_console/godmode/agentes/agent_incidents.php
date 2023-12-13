<?php
/**
 * ITSM.
 *
 * @category   ITSM view
 * @package    Pandora FMS
 * @subpackage Opensource
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

use PandoraFMS\ITSM\ITSM;

global $config;

check_login();

if (!$config['ITSM_enabled']) {
    ui_print_error_message(__('In order to access ticket management system, integration with ITSM must be enabled and properly configured'));
    return;
}

if (! check_acl($config['id_user'], $id_grupo, 'AW', $id_agente)) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access agent manager'
    );
    include 'general/noaccess.php';
    return;
}

try {
    $ITSM = new ITSM();
    echo $ITSM->getTableIncidencesForAgent($id_agente);
} catch (Exception $e) {
    echo $e->getMessage();
}

html_print_action_buttons('');
