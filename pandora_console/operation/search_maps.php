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

$maps = false;
if ($searchMaps) {
	$sql = "SELECT t1.id, t1.name, t1.id_group,
			(SELECT COUNT(*) FROM tlayout_data AS t2 WHERE t2.id_layout = t1.id) AS count 
		FROM tlayout AS t1 WHERE t1.name LIKE '%" . $stringSearchSQL . "%'
		LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	$maps = db_process_sql($sql);
	
	if($maps !== false) {
		$maps_id = array();
		foreach($maps as $key => $map) {
			if (!check_acl ($config["id_user"], $map["id_group"], "AR")) {
				unset($maps[$key]);
			}
			else {
				$maps_id[] = $map['id'];
			}
		}
		
		if(!$maps_id) {
			$maps_condition = "";
		}
		else {
			// Condition with the visible agents
			$maps_condition = " AND id IN (\"".implode('","',$maps_id)."\")";
		}
		
		$sql = "SELECT COUNT(id) AS count FROM tlayout WHERE name LIKE '%" . $stringSearchSQL . "%'".$maps_condition;
		$totalMaps = db_get_row_sql($sql);
		$totalMaps = $totalMaps['count'];
	}
}

if ($maps === false) {
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
