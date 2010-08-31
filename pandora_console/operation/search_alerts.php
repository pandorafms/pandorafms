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

include_once('include/functions_alerts.php');

$searchAlerts = check_acl($config['id_user'], 0, "AR");

$alerts = false;

if($searchAlerts) {
	$agents = array_keys(get_group_agents(array_keys(get_user_groups($config["id_user"], 'AR', false))));
	
	/*$whereAlerts = ' AND (t2.nombre LIKE "%'.$stringSearchSQL.'%" OR t3.nombre LIKE "%'.$stringSearchSQL.'%"
					OR t4.name LIKE "%'.$stringSearchSQL.'%") ';*/
		
	$whereAlerts = false;			
	$alertsraw = get_agent_alerts_simple ($agents, "all_enabled", array('offset' => get_parameter ('offset',0), 'limit' => $config['block_size']), $whereAlerts);

	$stringSearchPHP = substr($stringSearchSQL,1,strlen($stringSearchSQL)-2);
    
	$alerts = array();
	foreach($alertsraw as $key => $alert){
		$finded = false;
		$alerts[$key]['disabled'] = $alert['disabled'];
		$alerts[$key]['id_agente'] = get_agentmodule_agent($alert['id_agent_module']);
		$alerts[$key]['agent_name'] = get_agent_name($alerts[$key]['id_agente']);
		$alerts[$key]['module_name'] = $alert['agent_module_name'];
		$alerts[$key]['template_name'] = get_alert_template_name($alert['id_alert_template']);
		$actions = get_alert_agent_module_actions($alert['id']);
		
		// Check substring into agent, module, template and action names
		if(strpos($alerts[$key]['agent_name'], $stringSearchPHP) !== false) {
			$finded = true;
		}
		
		if(!$finded) {
			if(strpos($alert['agent_module_name'], $stringSearchPHP) !== false)	{
				$finded = true;
			}
		}
		
		if(!$finded) {
			if(strpos($alerts[$key]['template_name'], $stringSearchPHP) !== false)	{
				$finded = true;
			}
		}
		
		foreach($actions as $action) {
			$actions_name[] = $action['name'];
			
			if(!$finded) {
				if(strpos($action['name'], $stringSearchPHP) !== false)	{
					$finded = true;
				}			
			}
		}
		
		$alerts[$key]['actions'] = implode(',',$actions_name);
		
		if(!$finded) {
			unset($alerts[$key]);
		}
	}

	$totalAlerts = count($alerts);
}

if ($alerts === false || $totalAlerts == 0) {
	echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class = "databox";
	
	$table->head = array ();
	$table->head[0] = '';
	$table->head[1] = __('Agent');
	$table->head[2] = __('Module');
	$table->head[3] = __('Template');
	$table->head[4] = __('Action');
	
	$table->align = array ();
	$table->align[0] = "center";
	$table->align[1] = "left";
	$table->align[2] = "left";
	$table->align[3] = "left";
	$table->align[4] = "left";
	
	$table->valign = array ();
	$table->valign[0] = "top";
	$table->valign[1] = "top";
	$table->valign[2] = "top";
	$table->valign[3] = "top";
	$table->valign[4] = "top";
	
	$table->data = array ();
	foreach ($alerts as $alert) {
		if ($alert['disabled'])
			$disabledCell = print_image ('images/lightbulb_off.png', true, array('title' => 'disable', 'alt' => 'disable'));
		else
			$disabledCell = print_image ('images/lightbulb.png', true, array('alt' => 'enable', 'title' => 'enable'));
		
		$actionCell = '';
		if (strlen($alert["actions"]) > 0) {
			$arrayActions = explode(',', $alert["actions"]);
			$actionCell = '<ul class="action_list">';
			foreach ($arrayActions as $action)
				$actionCell .= '<li><div><span class="action_name">' . $action . '</span></div><br /></li>';
			$actionCell .= '</ul>';
		}
		
		
		array_push($table->data, array(
		$disabledCell,
		print_agent_name ($alert["id_agente"], true, "upper"),
		$alert["module_name"],
		$alert["template_name"],$actionCell
		));
	}
	
	echo "<br />";pagination ($totalAlerts);
	print_table ($table); unset($table);
	pagination ($totalAlerts);
}
?>
