<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Login check
global $config;

require_once 'include/functions_snmp.php';

$save_snmp_translation = (bool) get_parameter('save_snmp_translation', 0);
$delete_snmp_translation = (bool) get_parameter('delete_snmp_translation', 0);
$update_snmp_translation = (bool) get_parameter('update_snmp_translation', 0);
$delete_snmp_filter = (bool) get_parameter('delete_snmp_filter', 0);

// skins image checks
if ($save_snmp_translation) {
    $oid = get_parameter('oid', '');
    $description = get_parameter('description', '');
    $post_process = get_parameter('post_process', '');

    $result = snmp_save_translation($oid, $description, $post_process);

    echo json_encode(['correct' => $result]);

    return;
}

if ($delete_snmp_translation) {
    $oid = get_parameter('oid', '');

    $result = snmp_delete_translation($oid);

    echo json_encode(['correct' => $result]);

    return;
}

if ($update_snmp_translation) {
    $oid = get_parameter('oid', '');
    $new_oid = get_parameter('new_oid', '');
    $description = get_parameter('description', '');
    $post_process = get_parameter('post_process', '');

    $result = snmp_update_translation($oid, $new_oid, $description, $post_process);

    echo json_encode(['correct' => $result]);

    return;
}

if ($delete_snmp_filter) {
    $filter_id = get_parameter('filter_id');
    db_process_sql_delete('tsnmp_filter', ['id_snmp_filter' => $filter_id]);

    return;
}
