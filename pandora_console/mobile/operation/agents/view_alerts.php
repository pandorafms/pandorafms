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
		
		$groups = get_user_groups($this->system->getConfig('id_user'));
		$idGroups = array_keys($groups);
		$agents = get_group_agents($idGroups); 
		
		$alertsSimple = get_agent_alerts_simple($agents);
		
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
			
			$idAgent = get_agentmodule_agent($alert["id_agent_module"]);

			$data[] = '<a href="index.php?page=agent&id=' . $idAgent . '">' . printTruncateText(get_agentmodule_name($alert["id_agent_module"]), 20, true, true) . '</a>';
			$template = safe_output(get_alert_template ($alert['id_alert_template']));
			$data[] = printTruncateText(safe_output($template['name']), 20, true, true);
			$data[] = print_timestamp ($alert["last_fired"], true, array('units' => 'tiny'));
			
			$status = STATUS_ALERT_NOT_FIRED;
			$title = "";
			
			if ($alert["times_fired"] > 0) {
				$status = STATUS_ALERT_FIRED;
				$title = __('Alert fired').' '.$alert["times_fired"].' '.__('times');
			} elseif ($alert["disabled"] > 0) {
				$status = STATUS_ALERT_DISABLED;
				$title = __('Alert disabled');
			} else {
				$status = STATUS_ALERT_NOT_FIRED;
				$title = __('Alert not fired');
			}
			
			$data[] = str_replace(array('images/status_sets', '<img'), 
				array('../images/status_sets', '<img width="15" height="15"'), print_status_image($status, $title, true));
			
			$table->data[] = $data;
		}
		
		print_table($table);
	}
}
?>