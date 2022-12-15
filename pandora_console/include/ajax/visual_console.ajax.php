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
global $config;

require_once 'include/functions_visual_map.php';
enterprise_include_once('include/functions_visual_map.php');

$id_visual_console = get_parameter('id_visual_console', null);

// Login check.
check_login();

// Fix: IW was the old ACL to check for report editing, now is RW
if (! check_acl($config['id_user'], 0, 'VR')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access report builder'
    );
    include 'general/noaccess.php';
    exit;
}


// Fix ajax to avoid include the file, 'functions_graph.php'.
$ajax = true;

$render_map = (bool) get_parameter('render_map', false);
$graph_javascript = (bool) get_parameter('graph_javascript', false);
$force_remote_check = (bool) get_parameter('force_remote_check', false);
$update_maintanance_mode = (bool) get_parameter('update_maintanance_mode', false);
$load_css_cv = (bool) get_parameter('load_css_cv', false);

if ($render_map) {
    $width = (int) get_parameter('width', '400');
    $height = (int) get_parameter('height', '400');
    $keep_aspect_ratio = (bool) get_parameter('keep_aspect_ratio');

    visual_map_print_visual_map(
        $id_visual_console,
        true,
        true,
        $width,
        $height,
        '',
        false,
        $graph_javascript,
        $keep_aspect_ratio
    );
    return;
}

if ($force_remote_check) {
    $id_layout = (int) get_parameter('id_layout', false);
    $data = db_get_all_rows_sql(
        sprintf(
            'SELECT id_agent FROM tlayout_data WHERE id_layout = %d AND id_agent <> 0',
            $id_layout
        )
    );

    if (empty($data)) {
        echo '0';
    } else {
        $ids = [];
        foreach ($data as $key => $value) {
            $ids[] = $value['id_agent'];
        }

        $sql = sprintf(
            'UPDATE `tagente_modulo` SET flag = 1 WHERE `id_agente` IN (%s)',
            implode(',', $ids)
        );

        $result = db_process_sql($sql);
        if ($result) {
            echo true;
        } else {
            echo '0';
        }
    }

    return;
}

if ($load_css_cv === true) {
    $uniq = get_parameter('uniq', 0);
    $ratio = get_parameter('ratio', 0);

    $output = css_label_styles_visual_console($uniq, $ratio);
    echo $output;
    return;
}

if ($update_maintanance_mode === true) {
    $idVisualConsole = (int) get_parameter('idVisualConsole', 0);
    $mode = (bool) get_parameter('mode', false);

    $values = [];
    if ($mode === true) {
        $values['maintenance_mode'] = json_encode(
            [
                'user'      => $config['id_user'],
                'timestamp' => time(),
            ]
        );
    } else {
        $values['maintenance_mode'] = null;
    }

    $result = db_process_sql_update(
        'tlayout',
        $values,
        ['id' => $idVisualConsole]
    );

    echo json_encode(['result' => $result]);
    return;
}
