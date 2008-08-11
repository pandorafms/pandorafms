<?PHP

// Pandora FMS - the Flexible Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Login check
global $REMOTE_ADDR;

check_login ();

echo "<h2>".__('visual_console')." &gt; ";
echo __('summary')."</h2>";

$layouts = get_db_all_rows_in_table ('tlayout','name');

if (sizeof ($layouts) == 0) {
	echo "<div class='nf'>".__('no_layout_def')."</div>";
	return;
}

$table->width = '500px';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('name');
$table->head[1] = __('group');
$table->head[2] = __('elements');
$table->align = array ();
$table->align[2] = 'center';

foreach ($layouts as $layout) {
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=visualc&sec2=operation/visual_console/render_view&id='.
		$layout['id'].'">'.$layout['name'].'</a>';
	$data[1] = '<img src="images/'.dame_grupo_icono($layout["id_group"]).'.png" 
		title="'.dame_nombre_grupo ($layout["id_group"]).'"> ';
	$data[1] .= dame_nombre_grupo ($layout["id_group"]);
	$data[2] = get_db_value ('COUNT(*)', 'tlayout_data', 'id_layout', $layout['id']);
	
	array_push ($table->data, $data);
}

print_table ($table);

?>
