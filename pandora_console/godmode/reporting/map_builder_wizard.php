<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributepd in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars

check_login ();

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access map builder wizard");
	require ("general/noaccess.php");
	exit;
}

$layout_id = (int) get_parameter ('id_layout');
$layout = get_db_row ('tlayout', 'id', $layout_id);

$layout_group = $layout["id_group"];

if (! give_acl ($config['id_user'], $layout_group, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access map builder wizard (forget URL parameter)");
	require ("general/noaccess.php");
	exit;
}

function process_wizard_add ($id_agents, $image, $id_layout, $range) {
	if (empty ($id_agents)) {
		echo '<h3 class="error">'.__('No agents selected').'</h3>';
		return false;
	}
	
	$id_agents = (array) $id_agents;
	
	$error = false;
	$pos_y = 10;
	$pos_x = 10;
	foreach ($id_agents as $id_agent) {
		if ($pos_x > 600) {
			$pos_x = 10;
			$pos_y = $pos_y + $range;
		}
		
		process_sql_insert ('tlayout_data',
			array ('id_layout' => $id_layout,
				'pos_x' => $pos_x,
				'pos_y' => $pos_y,
				'label' => get_agent_name ($id_agent),
				'image' => $image,
				'id_agent' => $id_agent,
				'label_color' => '#000000'));
		
		$pos_x = $pos_x + $range;
	}
	
	echo '<h3 class="suc">'.__('Successfully added').'</h3>';
	echo '<h3><a href="index.php?sec=greporting&sec2=godmode/reporting/map_builder&id_layout='.$id_layout.'">'.__('Map builder').'</a></h3>';
}


echo '<h2>'.__('Visual map wizard').' - '.$layout["name"].'</h2>';

$id_agents = get_parameter ('id_agents');
$image = get_parameter ('image');
$add = (bool) get_parameter ('add', false);
$range = get_parameter ("range", 50);

if ($add) {
	process_wizard_add ($id_agents, $image, $layout["id"], $range);
}

$table->id = 'wizard_table';
$table->width = '65%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->size = array ();
$table->data = array ();

$images_list = array ();
$all_images = list_files ('images/console/icons/', "png", 1, 0);
foreach ($all_images as $image_file) {
	if (strpos ($image_file, "_bad"))
		continue;
	if (strpos ($image_file, "_ok"))
		continue;
	if (strpos ($image_file, "_warning"))
		continue;
	$image_file = substr ($image_file, 0, strlen ($image_file) - 4);
	$images_list[$image_file] = $image_file;
}

$table->data[0][0] = __('Image');
$table->data[0][1] = print_select ($images_list, 'image', '', '', '', '', true);

$table->data[1][0] = __('Image range (px)');
$table->data[1][1] = print_input_text ('range', $range, '', 5, 5, true);

$table->data[2][0] = __('Agents');
$table->data[2][1] = print_select (get_group_agents ($layout_group, false, "none"),
	'id_agents[]', 0, false, '', '', true, true);

echo '<form method="post" onsubmit="if (! confirm(\''.__('Are you sure').'\')) return false;">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
print_input_hidden ('add', 1);
print_input_hidden ('id_layout', $layout["id"]);
print_submit_button (__('Add'), 'go', false, 'class="sub wizard"');
echo '</div>';
echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';
?>

