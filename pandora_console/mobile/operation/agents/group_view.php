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

class GroupView {
	private $system;
	
	public function __construct() {
		global $system;
		
		$this->system = $system;
	}
	
	public function show() {
		$groups = get_user_groups ($this->system->getConfig('id_user'));
		
		$table = null;
		$table->width = '100%';
		
		$table->align = array();
		for($i = 0; $i <= 8; $i++) {
			$table->align[$i] = 'center';
		}
		
		$table->head = array();
		$table->head[0] = '&nbsp;';
//		$table->head[1] = '<span title="' . __('Total Agents') . '" alt="' . __('Total Agents') . '">' . __('T') . '</span>';
//		$table->head[2] = '<span title="' . __('Agent unknown') . '" alt="' . __('Agent unknown') . '">' . __('A') . '</span>';
		$table->head[3] = '<span title="' . __('Unknown') . '" alt="' . __('Unknown') . '">' . __('Unk') . '</span>';
//		$table->head[4] = '<span title="' . __('Not Init') . '" alt="' . __('Not Init') . '">' . __('N') . '</span>';
		$table->head[5] = '<span title="' . __('Normal') . '" alt="' . __('Normal') . '">' . __('Nor') . '</span>';
//		$table->head[6] = '<span title="' . __('Warning') . '" alt="' . __('Warning') . '">' . __('W') . '</span>';
//		$table->head[7] = '<span title="' . __('Critical') . '" alt="' . __('Critical') . '">' . __('C') . '</span>';
		$table->head[8] = '<span title="' . __('Alert fired') . '" alt="' . __('Alert fired') . '">' . __('Aler') . '</span>';
		
		$rowPair = false;
		$iterator = 0;
		foreach ($groups as $idGroup => $group) {
			if ($rowPair)
				$table->rowclass[$iterator] = 'rowPair';
			else
				$table->rowclass[$iterator] = 'rowOdd';
			$rowPair = !$rowPair;
			$iterator++;
			
			if ($idGroup == 0) continue; //avoid the all group
			$groupData = get_group_stats($idGroup);
			
			if ($groupData['total_agents'] == 0) continue; //avoid the empty groups
			
			$data = array();
			
			$groupName = get_group_name($idGroup);
			
			$data[] = '<a href="index.php?page=agents&filter_group=' . $idGroup . '">' . $groupName . '</a>';
//			$data[] = $groupData['total_agents'];
//			$data[] = $groupData['agents_unknown'];
			$data[] = $groupData['monitor_unknown'];
//			$data[] = $groupData['monitor_not_init'];
			$data[] = $groupData["monitor_ok"];
//			$data[] = $groupData["monitor_warning"];
//			$data[] = $groupData["monitor_critical"];
			$data[] = $groupData["monitor_alerts_fired"];
			
			$table->data[] = $data;
		}
		
		print_table($table);
	}
}
?>