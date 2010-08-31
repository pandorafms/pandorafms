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

$selectDisabledUp = '';
$selectDisabledDown = '';
$selectAgentUp = '';
$selectAgentDown = '';
$selectModuleUp = '';
$selectModuleDown = '';
$selectTemplateUp = '';
$selectTemplateDown = '';

switch ($sortField) {
	case 'disabled':
		switch ($sort) {
			case 'up':
				$selectAgentUp = $selected;
				$order = array('field' => 'disabled', 'order' => 'ASC');
				break;
			case 'down':
				$selectAgentDown = $selected;
				$order = array('field' => 'disabled', 'order' => 'DESC');
				break;
		}
		break;
	case 'agent':
		switch ($sort) {
			case 'up':
				$selectAgentUp = $selected;
				$order = array('field' => 'agent_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectAgentDown = $selected;
				$order = array('field' => 'agent_name', 'order' => 'DESC');
				break;
		}
		break;
	case 'module':
		switch ($sort) {
			case 'up':
				$selectModuleUp = $selected;
				$order = array('field' => 'module_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectModuleDown = $selected;
				$order = array('field' => 'module_name', 'order' => 'DESC');
				break;
		}
		break;
	case 'template':
		switch ($sort) {
			case 'up':
				$selectTemplateUp = $selected;
				$order = array('field' => 'template_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectTemplateDown = $selected;
				$order = array('field' => 'template_name', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectDisabledUp = '';
		$selectDisabledDown = '';
		$selectAgentUp = $selected;
		$selectAgentDown = '';
		$selectModuleUp = '';
		$selectModuleDown = '';
		$selectTemplateUp = '';
		$selectTemplateDown = '';
		
		$order = array('field' => 'agent_name', 'order' => 'ASC');
		break;
}

$alerts = false;

if($searchAlerts) {
	$agents = array_keys(get_group_agents(array_keys(get_user_groups($config["id_user"], 'AR', false))));
	
	/*$whereAlerts = ' AND (t2.nombre LIKE "%'.$stringSearchSQL.'%" OR t3.nombre LIKE "%'.$stringSearchSQL.'%"
					OR t4.name LIKE "%'.$stringSearchSQL.'%") ';*/
		
	$whereAlerts = false;			
	$alertsraw = get_agent_alerts_simple ($agents, "all_enabled", array('offset' => get_parameter ('offset',0), 'limit' => $config['block_size'], 'order' => $order['field'] . " " . $order['order']), $whereAlerts);

	$stringSearchPHP = substr($stringSearchSQL,1,strlen($stringSearchSQL)-2);
    
	$alerts = array();
	foreach($alertsraw as $key => $alert){
		$finded = false;
		$alerts[$key]['disabled'] = $alert['disabled'];
		$alerts[$key]['id_agente'] = get_agentmodule_agent($alert['id_agent_module']);
		$alerts[$key]['agent_name'] = $alert['agent_name'];
		$alerts[$key]['module_name'] = $alert['agent_module_name'];
		$alerts[$key]['template_name'] = $alert['template_name'];
		$actions = get_alert_agent_module_actions($alert['id']);
		
		// Check substring into agent, module, template and action names
		if(strpos($alert['agent_name'], $stringSearchPHP) !== false) {
			$finded = true;
		}
		
		if(!$finded) {
			if(strpos($alert['agent_module_name'], $stringSearchPHP) !== false)	{
				$finded = true;
			}
		}
		
		if(!$finded) {
			if(strpos($alert['template_name'], $stringSearchPHP) !== false)	{
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
	$table->head[0] = '' . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=disabled&sort=up"><img src="images/sort_up.png" style="' . $selectDisabledUp . '" /></a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=disabled&sort=down"><img src="images/sort_down.png" style="' . $selectDisabledDown . '" /></a>';
	$table->head[1] = __('Agent') . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=agent&sort=up"><img src="images/sort_up.png" style="' . $selectAgentUp . '" /></a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=agent&sort=down"><img src="images/sort_down.png" style="' . $selectAgentDown . '" /></a>';
	$table->head[2] = __('Module') . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=module&sort=up"><img src="images/sort_up.png" style="' . $selectModuleUp . '" /></a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=module&sort=down"><img src="images/sort_down.png" style="' . $selectModuleDown . '" /></a>';
	$table->head[3] = __('Template') . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=template&sort=up"><img src="images/sort_up.png" style="' . $selectTemplateUp . '" /></a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=template&sort=down"><img src="images/sort_down.png" style="' . $selectTemplateDown . '" /></a>';
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
