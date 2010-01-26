<?php
/**
 * Pandora FMS- http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Visual Setup Management");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_gis.php');

echo "<h2>".__('Pandora Setup')." &raquo; ";
echo __('Map conections GIS')."</h2>";

$action = get_parameter('action');

if ($action == 'delete_connection') {
	$idConnectionMap = get_parameter('id_connection_map');
	
	deleteMapConnection($idConnectionMap);
}

$table->width = '500px';
$table->head[0] = __('Map connection name');
$table->head[1] = __('Group');
$table->head[3] = __('Delete');

$table->align[1] = 'center';
$table->align[2] = 'center';
$table->align[3] = 'center';

$mapsConnections = get_db_all_rows_in_table ('tgis_map_connection','conection_name');

$table->data = array();

if ($mapsConnections !== false) {
	foreach ($mapsConnections as $mapsConnection) {
	$table->data[] = array('<a href="index.php?sec=gsetup&sec2=godmode/setup/gis_step_2&amp;action=edit_connection_map&amp;id_connection_map=' . 
				$mapsConnection['id_tmap_connection'] .'">'
				. $mapsConnection['conection_name'] . '</a>',
			print_group_icon ($mapsConnection['group_id'], true),
			'<a href="index.php?sec=gsetup&sec2=godmode/setup/gis&amp;id_connection_map=' . 
				$mapsConnection['id_tmap_connection'].'&amp;action=delete_connection"
				onClick="javascript: if (!confirm(\'' . __('Do you wan delete this connection?') . '\')) return false;">' . print_image ("images/cross.png", true).'</a>'); 
	}
}

print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form action="index.php?sec=gsetup&sec2=godmode/setup/gis_step_2" method="post">';
print_input_hidden ('action','create_connection_map');
print_submit_button (__('Create'), '', false, 'class="sub next"');
echo '</form>';
echo '</div>';
?>