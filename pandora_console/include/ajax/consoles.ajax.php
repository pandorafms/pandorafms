<?php
/**
 * Ajax script for Consoles' List view.
 *
 * @category   Consoles
 * @package    Community
 * @subpackage Software agents repository
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ==========================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnológicas S.L
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

// Begin.
global $config;

// Login check.
check_login();

require_once $config['homedir'].'/include/functions_ui.php';

use PandoraFMS\Console;

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

$get_all_datatables_formatted = (bool) get_parameter('get_all_datatables_formatted');
$delete = (bool) get_parameter('delete');

if ($get_all_datatables_formatted === true) {
    $results = db_get_all_rows_in_table('tconsole', 'id_console');

    if ($results === false) {
        $results = [];
    }

    $count = count($results);

    if ($results) {
        $data = array_reduce(
            $results,
            function ($carry, $item) {
                $item['last_execution'] = ui_print_timestamp($item['last_execution'], true);
                $item['console_type'] = ((int) $item['console_type'] === 1) ? __('Reporting').'&nbsp&nbsp'.html_print_image('images/report_list.png', true) : __('Standard');
                // Transforms array of arrays $data into an array
                // of objects, making a post-process of certain fields.
                $tmp = (object) $item;
                $carry[] = $tmp;
                return $carry;
            }
        );
    }

    // Datatables format: RecordsTotal && recordsfiltered.
    echo json_encode(
        [
            'data'            => $data,
            'recordsTotal'    => $count,
            'recordsFiltered' => $count,
        ]
    );

    return;
}

if ($delete === true) {
    $id = get_parameter('id');

    try {
        $console = new Console($id);
        $console->delete();
        $console->save();
        echo json_encode(['result' => __('Console successfully deleted')]);
    } catch (Exception $e) {
        echo json_encode(['result' => $e->getMessage()]);
    }

    return;
}
