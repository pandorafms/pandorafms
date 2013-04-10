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
global $config;

// Login check
check_login ();

require_once ('include/functions_gis.php');

ui_require_javascript_file('openlayers.pandora');

ui_print_page_header(__('GIS Maps')." &raquo; ".__('Summary'), "images/op_gis.png", false, "");

$maps = gis_get_maps();

$table->width = "98%";
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Group');
$table->align = array ();
$table->align[1] = 'center';

$rowPair = true;
$iterator = 0;

if ($maps !== false) {
	foreach ($maps as $map) {
		if (!check_acl ($config["id_user"], $map["group_id"], "IR", 0, true)) {
			continue;
		}
		
		if ($rowPair)
			$table->rowclass[$iterator] = 'rowPair';
		else
			$table->rowclass[$iterator] = 'rowOdd';
		$rowPair = !$rowPair;
		$iterator++;
		
		$data = array ();
		
		$data[0] = '<a href="index.php?sec=gismaps&amp;sec2=operation/gis_maps/render_view&amp;map_id='.
		$map['id_tgis_map'] . '">' . $map['map_name'].'</a> ';
		$data[1] = ui_print_group_icon ($map["group_id"], true);
		
		array_push ($table->data, $data);
	}
}

if (!empty ($table->data)) {
	html_print_table ($table);
}
else {
	echo '<div class="nf">' . __('No maps found') . '</div>';
}
unset ($table);
?>
