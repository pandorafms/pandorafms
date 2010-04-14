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

$id_layout = (int) get_parameter ('id_layout');
$copy_layout = (bool) get_parameter ('copy_layout');
$delete_layout = (bool) get_parameter ('delete_layout');

if ($delete_layout) {
	process_sql_delete ('tlayout_data', array ('id_layout' => $id_layout));
	$result = process_sql_delete ('tlayout', array ('id' => $id_layout));
	if ($result) {
		echo '<h3 class="suc">'.__('Successfully deleted').'</h3>';
		clean_cache();
	} else {
		echo '<h3 class="error">'.__('Not deleted. Error deleting data').'</h3>';
	}
	$id_layout = 0;
}

if ($copy_layout) {	
	// Number of inserts
	$ninsert = (int) 0;
	
	// Return from DB the source layout
	$layout_src = get_db_all_rows_filter ("tlayout","id = " . $id_layout);
	
	// Name of dst
	$name_dst = get_parameter ("name_dst", $layout_src[0]['name'] . " copy");
	
	// Create the new Console
	$idGroup = $layout_src[0]['id_group'];
	$background = $layout_src[0]['background'];
	$visualConsoleName = $name_dst;
	
	$values = array('name' => $visualConsoleName, 'id_group' => $idGroup, 'background' => $background);
	$result = process_sql_insert('tlayout', $values);
	
	$idNewVisualConsole = $result;
	
	if($result) {
		$ninsert = 1;

		// Return from DB the items of the source layout
		$data_layout_src = get_db_all_rows_filter ("tlayout_data", "id_layout = " . $id_layout);
		
		if(!empty($data_layout_src)){
			for ($a=0;$a < count($data_layout_src); $a++) { 
				
				// Changing the source id by the new visual console id
				$data_layout_src[$a]['id_layout'] = $idNewVisualConsole;
				
				// Unsetting the source's id
				unset($data_layout_src[$a]['id']);
			
				// Configure the cloned Console
				$result = process_sql_insert('tlayout_data', $data_layout_src[$a]);
				
				if($result)
					$ninsert++;
			}// for each item of console
				
			$inserts = count($data_layout_src) + 1;
				
			// If the number of inserts is correct, the copy is completed
			if ($ninsert == $inserts) {
				echo '<h3 class="suc">'.__('Successfully copyed').'</h3>';
				clean_cache();
			} else {
				echo '<h3 class="error">'.__('Not copyed. Error copying data').'</h3>';
			}
		}
		else{
			// If the array is empty the copy is completed
			echo '<h3 class="suc">'.__('Successfully copyed').'</h3>';
			clean_cache();
		}
	}
	else {
		echo '<h3 class="error">'.__('Not copyed. Error copying data').'</h3>';
	}
		
}

$table->width = '500px';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Map name');
$table->head[1] = __('Group');
$table->head[2] = __('Items');
$table->head[3] = __('Copy');
$table->head[4] = __('Delete');
$table->align = array ();
$table->align[3] = 'center';
$table->align[4] = 'center';

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
		
			$data[3] = '<a href="index.php?sec=gmap&amp;sec2=godmode/reporting/map_builder&amp;id_layout='.$map['id'].'&amp;copy_layout=1">'.print_image ("images/copy.png", true).'</a>';
			$data[4] = '<a href="index.php?sec=gmap&amp;sec2=godmode/reporting/map_builder&amp;id_layout='.$map['id'].'&amp;delete_layout=1">'.print_image ("images/cross.png", true).'</a>';
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
