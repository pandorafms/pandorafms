<?php
/**
 * Network explorer
 *
 * @package    Operations.
 * @subpackage Netflow explorer view.
 *
 * Pandora FMS - http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

global $config;

check_login();

// ACL Check.
if (! check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Netflow explorer'
    );
    include 'general/noaccess.php';
    exit;
}

$action = get_parameter('action', 'listeners');
$is_network = false;

ui_print_page_header(__('Netflow explorer'));

if (netflow_print_check_version_error()) {
    include $config['homedir'].'/operation/network/network_report.php';
}
