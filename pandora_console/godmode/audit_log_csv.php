<?php
// ______                 __                     _______ _______ _______
// |   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
// |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
// |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2021 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================
$ownDir = dirname(__FILE__).'/';
$ownDir = str_replace('\\', '/', $ownDir);

// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once $ownDir.'../include/config.php';

require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/auth/mysql.php';

global $config;

// Login check
if (!isset($_SESSION['id_usuario'])) {
    $config['id_user'] = null;
} else {
    $config['id_user'] = $_SESSION['id_usuario'];
}

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access audit CSV export'
    );
    include 'general/noaccess.php';
    exit;
}

$filter_type = (string) get_parameter('filter_type');
$filter_user = (string) get_parameter('filter_user');
$filter_text = (string) get_parameter('filter_text');
$filter_period = get_parameter('filter_period', null);
$filter_period = ($filter_period !== null) ? (int) $filter_period : 24;
$filter_ip = (string) get_parameter('filter_ip');

$filter = '1=1';

if (!empty($filter_type)) {
    $filter .= sprintf(" AND accion = '%s'", $filter_type);
}

if (!empty($filter_user)) {
    $filter .= sprintf(" AND id_usuario = '%s'", $filter_user);
}

if (!empty($filter_text)) {
    $filter .= sprintf(" AND (accion LIKE '%%%s%%' OR descripcion LIKE '%%%s%%')", $filter_text, $filter_text);
}

if (!empty($filter_ip)) {
    $filter .= sprintf(" AND ip_origen LIKE '%%%s%%'", $filter_ip);
}

if (!empty($filter_period)) {
    switch ($config['dbtype']) {
        case 'mysql':
            $filter .= ' AND fecha >= DATE_ADD(NOW(), INTERVAL -'.$filter_period.' HOUR)';
        break;

        case 'postgresql':
            $filter .= ' AND fecha >= NOW() - INTERVAL \''.$filter_period.' HOUR \'';
        break;

        case 'oracle':
            $filter .= ' AND fecha >= (SYSTIMESTAMP - INTERVAL \''.$filter_period.'\' HOUR)';
        break;
    }
}

$sql = sprintf('SELECT * FROM tsesion WHERE %s ORDER BY fecha DESC', $filter);
$result = db_get_all_rows_sql($sql);

print_audit_csv($result);
