<?php
/**
 * Manage AJAX response for Wizard Setup pages.
 *
 * @category   Ajax
 * @package    Pandora FMS
 * @subpackage Wizard Setup
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
global $config;
// File requirements.
require_once 'include/functions_ui.php';
require_once 'include/functions_db.php';
require_once 'include/functions_io.php';
require_once 'include/functions.php';
require_once $config['homedir'].'/include/class/ConfigPEN.class.php';
// Security.
if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access to Wizard Setup Ajax'
    );
    include 'general/noaccess.php';
    return;
}

// Get all paramaters.
$action             = get_parameter('action');
$pen_id_np          = get_parameter('pen_id');
$pen_number         = get_parameter('pen_number');
$pen_manufacturer   = get_parameter('pen_manufacturer');
$pen_description    = get_parameter('pen_description');
// Set the variables needed here.
$configPEN      = new ConfigPEN();
$message        = '';
$output         = '';
// Let's do something.
// First, get the current data.
$actual_pen = db_get_row('tpen', 'pen', $pen_number);
switch ($action) {
    // Add new line.
    case 'add':
        if ($actual_pen === false) {
            $work = db_process_sql_insert(
                'tpen',
                [
                    'id_np'        => $pen_id_np,
                    'pen'          => $pen_number,
                    'manufacturer' => $pen_manufacturer,
                    'description'  => $pen_description,
                ]
            );
            if ($work === false) {
                $message = ui_print_error_message(__('Error inserting new PEN'), '', true);
            } else {
                $message = ui_print_success_message(__('PEN added in DB'), '', true);
            }
        } else {
            $message = ui_print_error_message(sprintf(__('The PEN %s exists already'), $pen_number));
        }
    break;

    // Update one record.
    case 'update':
        if ($actual_pen['pen'] != $pen_number
            || $actual_pen['manufacturer'] != $pen_manufacturer
            || $actual_pen['description'] != $pen_description
        ) {
            $work = db_process_sql_update(
                'tpen',
                [
                    'pen'          => $pen_number,
                    'manufacturer' => $pen_manufacturer,
                    'description'  => $pen_description,
                ],
                ['pen' => $pen_number]
            );

            if ($work === false) {
                $message = ui_print_error_message(__('Error updating data'));
            } else {
                $message = ui_print_success_message(__('PEN updated in DB'));
            }
        } else {
            $message = ui_print_error_message(__('No changes applied'));
        }
    break;

    // Delete one record.
    case 'delete':
        if ($actual_pen != false) {
            db_process_sql_delete(
                'tpen',
                ['pen' => $pen_number]
            );

            $message = ui_print_success_message(__('PEN deleted in DB'), '', true);
        } else {
            $message = ui_print_error_message(__('Something goes wrong. Please, retry'), '', true);
        }
    break;

    default:
        // Nothing to do.
    break;
}

// Create the response data.
$output = $message.$configPEN->createMainTable();
// Return data.
echo $output;
