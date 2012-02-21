<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

include_once ($config['homedir'] . "/include/functions_agents.php");
include_once ($config['homedir'].'/include/functions_modules.php');
include_once ($config['homedir'].'/include/functions_users.php');

class ViewAlerts {
	private $system;
	
	public function __construct() {
		global $system;
		
		$this->system = $system;
	}
	
	public function show() {
		$table = null;
		//$table->width = '100%';
		$table->head = array();
		$table->head[0] = __('Module');
		$table->head[1] = __('Template');
		$table->head[2] = __('Action');
		$table->head[2] = '<span title="' . __('Last fired') . '" alt="' . __('Last fired') . '">' . __('Last') . '</span>';
		$table->head[3] = '<span title="' . __('Status') . '" alt="' . __('Status') . '">' . __('S') . '</span>';
		
		
		$table->align = array();
		$table->align[3] = 'right';
		$table->align[2] = 'center';
		$table->data = array();
		$table->rowclass = array();
		
		$groups = users_get_groups($this->system->getConfig('id_user'));
		$idGroups = array_keys($groups);
		$agents = agents_get_group_agents($idGroups); 
		$idAgents = array_keys($agents);
		
		$alertsSimple = agents_get_alerts_simple($idAgents);
		
		$rowPair = false;
		$iterator = 0;
		foreach ($alertsSimple as $alert) {
			if ($rowPair)
				$table->rowclass[$iterator] = 'rowPair';
			else
				$table->rowclass[$iterator] = 'rowOdd';
			$rowPair = !$rowPair;
			$iterator++;
			
			$data = array();
			
			$idAgent = modules_get_agentmodule_agent($alert["id_agent_module"]);

			$data[] = '<a href="index.php?page=agent&id=' . $idAgent . '">' . ui_print_truncate_text(modules_get_agentmodule_name($alert["id_agent_module"]), 20, true, true) . '</a>';
			$template = io_safe_output(alerts_get_alert_template ($alert['id_alert_template']));
			$data[] = ui_print_truncate_text(io_safe_output($template['name']), 20, true, true);
			$data[] = ui_print_timestamp ($alert["last_fired"], true, array('units' => 'tiny'));
			
			$status = STATUS_ALERT_NOT_FIRED;
			$title = "";
			
			if ($alert["times_fired"] > 0) {
				$status = STATUS_ALERT_FIRED;
				$title = __('Alert fired').' '.$alert["times_fired"].' '.__('times');
			}
			elseif ($alert["disabled"] > 0) {
				$status = STATUS_ALERT_DISABLED;
				$title = __('Alert disabled');
			}
			else {
				$status = STATUS_ALERT_NOT_FIRED;
				$title = __('Alert not fired');
			}
			
			$data[] = str_replace(array('images/status_sets', '<img'), 
				array('images/status_sets', '<img width="15" height="15"'), ui_print_status_image($status, $title, true));
			
			$table->data[] = $data;
		}
		
		html_print_table($table);
	}
}
?>
