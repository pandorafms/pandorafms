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
$networkmap = get_parameter('networkmap', false);

global $config;

require_once $config['homedir'].'/include/class/NetworkMap.class.php';


if ((bool) is_metaconsole() === true) {
    $node = get_parameter('node', 0);
    if ($node > 0) {
        metaconsole_connect(null, $node);
    }
}

if ($networkmap) {
    $networkmap_id = get_parameter('networkmap_id', 0);
    $x_offset = get_parameter('x_offset', 0);
    $y_offset = get_parameter('y_offset', 0);
    $zoom_dash = get_parameter('zoom_dash', 0.5);

    // Dashboard mode.
    $ignore_acl = (bool) get_parameter('ignore_acl', 0);

    $networkmap = db_get_row_filter('tmap', ['id' => $networkmap_id]);

    if ($ignore_acl === false) {
        // ACL for the network map.
        $networkmap_read = check_acl($config['id_user'], $networkmap['id_group'], 'MR');
        $networkmap_write = check_acl($config['id_user'], $networkmap['id_group'], 'MW');
        $networkmap_manage = check_acl($config['id_user'], $networkmap['id_group'], 'MM');

        if (!$networkmap_read && !$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access networkmap'
            );
            include 'general/noaccess.php';

            if ($node > 0) {
                metaconsole_restore_db();
            }

            return;
        }
    }

    ob_start();

    if ($networkmap['generation_method'] == LAYOUT_RADIAL_DYNAMIC) {
        $data['name'] = '<a href="index.php?'.'sec=network&'.'sec2=operation/agentes/networkmap.dinamic&'.'activeTab=radial_dynamic&'.'id_networkmap='.$networkmap['id'].'">'.$networkmap['name'].'</a>';
        global $id_networkmap;
        $id_networkmap = $networkmap['id'];
        $tab = 'radial_dynamic';

        include_once 'operation/agentes/networkmap.dinamic.php';
    } else {
        $map = new NetworkMap(
            [
                'id_map'      => $networkmap_id,
                'widget'      => 1,
                'pure'        => 1,
                'no_popup'    => 1,
                'map_options' => [
                    'x_offs' => $x_offset,
                    'y_offs' => $y_offset,
                    'z_dash' => $zoom_dash,
                ],


            ]
        );

        $map->printMap(false, $ignore_acl);
    }

    $return = ob_get_clean();

    echo $return;


    if ($node > 0) {
        metaconsole_restore_db();
    }

    return;
}


if ($node > 0) {
    metaconsole_restore_db();
}
