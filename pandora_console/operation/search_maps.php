<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

$searchMaps = check_acl($config["id_user"], 0, "IR");

if ($maps === false || !$searchMaps) {
		echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class = "databox";
	
	$table->head = array ();
	$table->head[0] = __('Name');
	$table->head[1] = __('Group');
	$table->head[2] = __('Elements');
	
	$table->align = array ();
	$table->align[1] = "center";
	$table->align[2] = "center";
	
	$table->data = array ();
	foreach ($maps as $map) {
		array_push($table->data, array(
			"<a href='?sec=reporting&sec2=operation/visual_console/render_view&id=" .
			$map['id'] . "'>" . $map['name'] . "</a>",
			ui_print_group_icon ($map["id_group"], true),
			$map['count']
		));
	}
	
	echo "<br />";ui_pagination ($totalMaps);
	html_print_table ($table); unset($table);
	ui_pagination ($totalMaps);
}
?>
