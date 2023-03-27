<?php
/**
 * Pandora FMS- http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Load global vars
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Visual Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_gis.php';

ui_require_javascript_file('openlayers.pandora');

$action = get_parameter('action');

switch ($action) {
    case 'save_edit_map_connection':
        if (!$errorfill) {
            ui_print_success_message(__('Successfully updated'));
        } else {
            ui_print_error_message(__('Could not be updated'));
        }
    break;

    case 'save_map_connection':
        if (!$errorfill) {
            ui_print_success_message(__('Successfully created'));
        } else {
            ui_print_error_message(__('Could not be created'));
        }
    break;

    case 'delete_connection':
        $idConnectionMap = get_parameter('id_connection_map');

        $result = gis_delete_map_connection($idConnectionMap);

        if ($result === false) {
            ui_print_error_message(__('Could not be deleted'));
        } else {
            ui_print_success_message(__('Successfully deleted'));
        }
    break;
}

$table = new stdClass();
$table->class = 'info_table';
$table->width = '100%';
$table->head[0] = __('Map connection name');
$table->head[1] = __('Group');
$table->head[3] = __('Delete');

$table->align[1] = 'left';
$table->align[2] = 'left';
$table->align[3] = 'left';

$mapsConnections = db_get_all_rows_in_table('tgis_map_connection', 'conection_name');

$table->data = [];

if ($mapsConnections !== false) {
    foreach ($mapsConnections as $mapsConnection) {
        $table->data[] = [
            '<a href="index.php?sec=gsetup&sec2=godmode/setup/gis_step_2&amp;action=edit_connection_map&amp;id_connection_map='.$mapsConnection['id_tmap_connection'].'">'.$mapsConnection['conection_name'].'</a>',
            ui_print_group_icon($mapsConnection['group_id'], true),
            '<a href="index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=gis&amp;id_connection_map='.$mapsConnection['id_tmap_connection'].'&amp;action=delete_connection"
				onClick="javascript: if (!confirm(\''.__('Do you wan delete this connection?').'\')) return false;">'.html_print_image('images/delete.svg', true, ['class' => 'invert_filter main_menu_icon']).'</a>',
        ];
        $table->cellclass[][2] = 'table_action_buttons';
    }
}

html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form action="index.php?sec=gsetup&sec2=godmode/setup/gis_step_2" method="post">';
html_print_input_hidden('action', 'create_connection_map');
html_print_action_buttons(
    html_print_submit_button(
        __('Create'),
        '',
        false,
        ['icon' => 'wand'],
        true
    )
);
echo '</form>';
echo '</div>';
