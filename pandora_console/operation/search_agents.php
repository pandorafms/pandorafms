<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

$searchAgents = check_acl($config['id_user'], 0, "AR");

$agents = false;
if ($searchAgents) {
	$sql = "SELECT id_agente, tagente.nombre, tagente.id_os, tagente.intervalo, tagente.id_grupo, tagente.disabled
		FROM tagente
			INNER JOIN tgrupo
				ON tgrupo.id_grupo = tagente.id_grupo
		WHERE tagente.nombre COLLATE utf8_general_ci LIKE '%" . $stringSearchSQL . "%' OR
			tgrupo.nombre LIKE '%" . $stringSearchSQL . "%'
		LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	$agents = process_sql($sql);
	
	if($agents !== false) {
		// ACLs check
		$agents_id = array();
		foreach($agents as $key => $agent){
				if (!give_acl ($config["id_user"], $agent["id_grupo"], "AR")) {
					unset($agents[$key]);
				} else {
					$agents_id[] = $agent["id_agente"];
				}
		}
		
		if(!$agents_id) {
			$agent_condition = "";
		}else {
			// Condition with the visible agents
			$agent_condition = " AND id_agente IN (".implode(',',$agents_id).")";
		}
		
		$sql = "SELECT count(id_agente) AS count
			FROM tagente
				INNER JOIN tgrupo
					ON tgrupo.id_grupo = tagente.id_grupo
			WHERE (tagente.nombre COLLATE utf8_general_ci LIKE '%" . $stringSearchSQL . "%' OR
				tgrupo.nombre LIKE '%" . $stringSearchSQL . "%')".$agent_condition;
		$totalAgents = get_db_row_sql($sql);
		
		$totalAgents = $totalAgents['count'];
	}
}


if (!$agents) {
		echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {		
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = "98%";
		$table->class = "databox";
		
		$table->head = array ();
		$table->head[0] = __('Agent');
		$table->head[1] = __('OS');
		$table->head[2] = __('Interval');
		$table->head[3] = __('Group');
		$table->head[4] = __('Modules');
		$table->head[5] = __('Status');
		$table->head[6] = __('Alerts');
		$table->head[7] = __('Last contact');
		
		$table->align = array ();
		$table->align[0] = "left";
		$table->align[1] = "center";
		$table->align[2] = "center";
		$table->align[3] = "center";
		$table->align[4] = "center";
		$table->align[5] = "center";
		$table->align[6] = "center";
		$table->align[7] = "right";
		
		$table->data = array ();
		
		foreach ($agents as $agent) {
			$agent_info = get_agent_module_info ($agent["id_agente"]);
			
			$modulesCell = '<b>'. $agent_info["modules"] . '</b>';
			if ($agent_info["monitor_normal"] > 0)
				$modulesCell .= '</b> : <span class="green">'.$agent_info["monitor_normal"].'</span>';
			if ($agent_info["monitor_warning"] > 0)
				$modulesCell .= ' : <span class="yellow">'.$agent_info["monitor_warning"].'</span>';
			if ($agent_info["monitor_critical"] > 0)
				$modulesCell .= ' : <span class="red">'.$agent_info["monitor_critical"].'</span>';
			if ($agent_info["monitor_down"] > 0)
				$modulesCell .= ' : <span class="grey">'.$agent_info["monitor_unknown"].'</span>';
			
			if ($agent['disabled']) {
				$cellName = "<em>" . print_agent_name ($agent["id_agente"], true, "upper") .print_help_tip(__('Disabled'), true) . "</em>";
			}
			else {
				$cellName = print_agent_name ($agent["id_agente"], true, "upper");
			}
				
			array_push($table->data, array(
				$cellName,
				print_os_icon ($agent["id_os"], false, true),
				$agent['intervalo'],
				print_group_icon ($agent["id_grupo"], true),
				$modulesCell,
				$agent_info["status_img"],
				$agent_info["alert_img"],
				print_timestamp ($agent_info["last_contact"], true)));
		}
		
		echo "<br />";pagination ($totalAgents);
		print_table ($table); unset($table);
		pagination ($totalAgents);
}
?>
