<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.



// Login check
check_login ();

echo "<h2>".__('Visual console')." &gt; ".__('Summary')."</h2>";


$layouts = get_db_all_rows_in_table ('tlayout','name');

if ($layouts === false)
	$layouts = array ();

$table->width = '500px';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Group');
$table->head[2] = __('Elements');
$table->align = array ();
$table->align[2] = 'center';

foreach ($layouts as $layout) {
	if (!give_acl ($config["id_user"], $layout["id_group"], "AR")) {
		continue;
	}
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=visualc&sec2=operation/visual_console/render_view&id='.
		$layout['id'].'">'.$layout['name'].'</a>';
	$data[1] = '<img src="images/'.dame_grupo_icono($layout["id_group"]).'.png" 
		title="'.dame_nombre_grupo ($layout["id_group"]).'"> ';
	$data[1] .= dame_nombre_grupo ($layout["id_group"]);
	$data[2] = get_db_value ('COUNT(*)', 'tlayout_data', 'id_layout', $layout['id']);
		
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	print_table ($table);
} else {
	echo '<div class="nf">'.__('No layouts found').'</div>';
}
unset ($table);

?>
