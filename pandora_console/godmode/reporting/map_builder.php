<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

print_page_header (__('Visual console builder'), "", false, "map_builder", true);

$table->width = '500px';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Map name');
$table->head[1] = __('Group');
$table->head[2] = __('Items');
$table->head[3] = __('Delete');
$table->align = array ();
$table->align[3] = 'center';

$maps = get_db_all_rows_in_table ('tlayout','name');
if (!$maps) {
	echo '<div class="nf">'.('No maps defined').'</div>';
} else {
	foreach ($maps as $map) {			
		if (give_acl ($config['id_user'], $map['id_group'], "AW")){
			$data = array ();
			$data[0] = '<a href="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=data&amp;action=edit&amp;id_visual_console='.$map['id'].'">'.$map['name'].'</a>';
		
			$data[1] = print_group_icon ($map['id_group'], true).'&nbsp;';
			$data[1] .= get_group_name ($map['id_group']);
			$data[2] = get_db_sql ("SELECT COUNT(*) FROM tlayout_data WHERE id_layout = ".$map['id']);
		
			$data[3] = '<a href="index.php?sec=gmap&amp;sec2=godmode/reporting/map_builder&amp;id_layout='.$map['id'].'&amp;delete_layout=1">'.print_image ("images/cross.png", true).'</a>';
			array_push ($table->data, $data);
		}
	}
	print_table ($table);
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form action="index.php?sec=gmap&amp;sec2=godmode/reporting/visual_console_builder" method="post">';
print_input_hidden ('edit_layout', 1);
print_submit_button (__('Create'), '', false, 'class="sub next"');
echo '</form>';
echo '</div>';
?>