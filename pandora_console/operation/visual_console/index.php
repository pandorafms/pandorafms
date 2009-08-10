<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.



// Login check
check_login ();

echo "<h2>".__('Visual console')." &raquo; ".__('Summary')."</h2>";

require_once ('include/functions_visual_map.php');
$layouts = get_user_layouts ();

$table->width = "70%";
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Group');
$table->head[2] = __('Elements');
$table->align = array ();
$table->align[1] = 'center';
$table->align[2] = 'center';

$rowPair = true;
$iterator = 0;
foreach ($layouts as $layout) {
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;
	
	if (!give_acl ($config["id_user"], $layout["id_group"], "AR")) {
		continue;
	}
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=visualc&amp;sec2=operation/visual_console/render_view&amp;id='.
		$layout['id'].'">'.$layout['name'].'</a> ';
	$data[1] = print_group_icon ($layout["id_group"], true);
	$data[1] .= "&nbsp;".get_group_name ($layout["id_group"]);
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
