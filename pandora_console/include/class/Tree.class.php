<?php
//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

class Tree {
	private $type = null;
	private $tree = array();
	private $filter = array();
	private $root = null;
	private $childrenMethod = "on_demand";
	private $countModuleStatusMethod = "on_demand";
	private $countAgentStatusMethod = "on_demand";
	
	public function  __construct($type, $root = null,
		$childrenMethod = "on_demand",
		$countModuleStatusMethod = "on_demand",
		$countAgentStatusMethod = "on_demand") {
		
		$this->type = $type;
		$this->root = $root;
		$this->childrenMethod = $childrenMethod;
		$this->countModuleStatusMethod = $countModuleStatusMethod;
		$this->countAgentStatusMethod = $countAgentStatusMethod;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function setFilter($filter) {
		$this->filter = $filter;
	}
	
	public function getData() {
		switch ($this->type) {
			case 'os':
				$this->getDataOS();
				break;
			case 'group':
				$this->getDataGroup();
				break;
			case 'module_group':
				$this->getDataModuleGroup();
				break;
			case 'agent':
				$this->getDataModules();
				break;
			case 'tag':
				$this->getDataTag();
				break;
		}
	}
	
	public function getDataOS() {
		$list_os = os_get_os();
		
		html_debug_print($list_os);
	}
	
	private function getGroupsRecursive($parent, $limit = null, $get_agents = true) {
		$filter = array();
		$filter['parent'] = $parent;
		
		if (!empty($this->filter['search'])) {
			$filter['nombre'] = "%" . $this->filter['search'] . "%";
		}
		
		// First filter by name and father
		$groups = db_get_all_rows_filter('tgrupo', $filter, array('id_grupo', 'nombre'));
		if (empty($groups))
			$groups = array();
		
		// Filter by status
		$filter_status = AGENT_STATUS_ALL;
		if (!empty($this->filter['status'])) {
			$filter_status = $this->filter['status'];
		}
		
		foreach ($groups as $iterator => $group) {
			// Counters
			$group_stats = reporting_get_group_stats($group['id_grupo']);

			$groups[$iterator]['counters'] = array();
			if (!empty($group_stats)) {
				$groups[$iterator]['counters']['unknown'] = $group_stats['agents_unknown'];
				$groups[$iterator]['counters']['critical'] = $group_stats['agent_critical'];
				$groups[$iterator]['counters']['warning'] = $group_stats['agent_warning'];
				$groups[$iterator]['counters']['not_init'] = $group_stats['agent_not_init'];
				$groups[$iterator]['counters']['ok'] = $group_stats['agent_ok'];
				$groups[$iterator]['counters']['total'] = $group_stats['total_agents'];
			}

			$groups[$iterator]['status'] = $group_stats['status'];
			$groups[$iterator]['icon'] = groups_get_icon($group['id_grupo']) . '.png';
			
			// Filter by status
			if ($filter_status != AGENT_STATUS_ALL) {
				$remove_group = true;
				switch ($filter_status) {
					case AGENT_STATUS_NORMAL:
						if ($groups[$iterator]['status'] === "ok")
							$remove_group = false;
						break;
					case AGENT_STATUS_WARNING:
						if ($groups[$iterator]['status'] === "warning")
							$remove_group = false;
						break;
					case AGENT_STATUS_CRITICAL:
						if ($groups[$iterator]['status'] === "critical")
							$remove_group = false;
						break;
					case AGENT_STATUS_UNKNOWN:
						if ($groups[$iterator]['status'] === "unknown")
							$remove_group = false;
						break;
					case AGENT_STATUS_NOT_INIT:
						if ($groups[$iterator]['status'] === "not_init")
							$remove_group = false;
						break;
				}
				
				if ($remove_group) {
					unset($groups[$iterator]);
					continue;
				}
			}
			
			if (is_null($limit)) {
				$groups[$iterator]['children'] =
					$this->getGroupsRecursive($group['id_grupo']);
			}
			else if ($limit >= 1) {
				$groups[$iterator]['children'] =
					$this->getGroupsRecursive(
						$group['id_grupo'],
						($limit - 1));
			}

			switch ($this->countAgentStatusMethod) {
				case 'on_demand':
					$groups[$iterator]['searchCounters'] = 1;
					break;
				case 'live':
					$groups[$iterator]['searchCounters'] = 0;
					break;
			}
			switch ($this->childrenMethod) {
				case 'on_demand':
					// if (!empty($groups[$iterator]['children'])) {
					// 	$groups[$iterator]['searchChildren'] = 1;
					// }
					// else {
					// 	$groups[$iterator]['searchChildren'] = 0;
					// }
					$groups[$iterator]['searchChildren'] = 0;
					break;
				case 'live':
					$groups[$iterator]['searchChildren'] = 0;
					break;
			}
			
			$groups[$iterator]['type'] = 'group';
			$groups[$iterator]['name'] = $groups[$iterator]['nombre'];
			$groups[$iterator]['id'] = $groups[$iterator]['id_grupo'];
		}
		
		if (!empty($parent) && $get_agents) {
			$agents = $this->getAgents($parent, 'group');

			if (!empty($agents))
				$groups = array_merge($groups, $agents);
		}

		return $groups;
	}
	
	public function getDataGroup() {
		// Get the parent
		if (empty($this->root))
			$parent = 0;
		else
			$parent = $this->root;

		$groups = $this->getGroupsRecursive($parent);

		if (empty($groups))
			$groups = array();

		$this->tree = $groups;
	}

	public function getModules ($parent = 0) {
		$modules = array();

		$modules_aux = agents_get_modules($parent, array('nombre', 'id_tipo_modulo'));
		
		if (empty($modules_aux))
			$modules_aux = array();
		
		// Process the modules
		foreach ($modules_aux as $id => $module) {
			$module['type'] = 'module';
			$module['id'] = $id;
			$module['name'] = $module['nombre'];
			$module['icon'] = modules_get_type_icon($module['id_tipo_modulo']);
			$module['value'] = modules_get_last_value($id);

			// Status
			switch (modules_get_status($id)) {
				case AGENT_MODULE_STATUS_CRITICAL_BAD:
				case AGENT_MODULE_STATUS_CRITICAL_ALERT:
					$module['status'] = "critical";
					break;
				case AGENT_MODULE_STATUS_WARNING:
				case AGENT_MODULE_STATUS_WARNING_ALERT:
					$module['status'] = "warning";
					break;
				case AGENT_MODULE_STATUS_UNKNOWN:
					$module['status'] = "unknown";
					break;
				case AGENT_MODULE_STATUS_NO_DATA:
				case AGENT_MODULE_STATUS_NOT_INIT:
					$module['status'] = "not_init";
					break;
				case AGENT_MODULE_STATUS_NORMAL:
				case AGENT_MODULE_STATUS_NORMAL_ALERT:
				default:
					$module['status'] = "ok";
					break;
			}

			$modules[] = $module;
		}

		return $modules;
	}
	
	public function getDataModules() {
		// Get the parent
		if (empty($this->root))
			$parent = 0;
		else
			$parent = $this->root;

		//$modules = $this->getModules($parent);

		if (empty($modules))
			$modules = array();

		$this->tree = $modules;
	}

	public function getAgents ($parent = 0, $parentType = '') {
		switch ($parentType) {
			case 'group':
				$filter = array(
					'id_grupo' => $parent,
					'status' => $this->filter['status'],
					'nombre' => "%" . $this->filter['search'] . "%"
					);
				$agents = agents_get_agents($filter,
					array('id_agente', 'nombre', 'id_os'));
				if (empty($agents)) {
					$agents = array();
				}
				break;
			default:
				return array();
				break;
		}
		
		foreach ($agents as $iterator => $agent) {
			$agents[$iterator]['type'] = 'agent';
			$agents[$iterator]['id'] = $agents[$iterator]['id_agente'];
			$agents[$iterator]['name'] = $agents[$iterator]['nombre'];
			$agents[$iterator]['icon'] = ui_print_os_icon( $agents[$iterator]["id_os"],
				false, true, true, false, true, true);
			
			// Counters
			$agents[$iterator]['counters'] = array();
			$agents[$iterator]['counters']['unknown'] =
				agents_monitor_unknown($agents[$iterator]['id']);
			$agents[$iterator]['counters']['critical'] =
				agents_monitor_critical($agents[$iterator]['id']);
			$agents[$iterator]['counters']['warning'] =
				agents_monitor_warning($agents[$iterator]['id']);
			$agents[$iterator]['counters']['not_init'] =
				agents_monitor_notinit($agents[$iterator]['id']);
			$agents[$iterator]['counters']['ok'] =
				agents_monitor_ok($agents[$iterator]['id']);
			$agents[$iterator]['counters']['total'] =
				agents_monitor_total($agents[$iterator]['id']);
			$agents[$iterator]['counters']['alerts'] =
				agents_get_alerts_fired($agents[$iterator]['id']);

			// Status
			switch (agents_get_status($agents[$iterator]['id'])) {
				case AGENT_STATUS_NORMAL:
					$agents[$iterator]['status'] = "ok";
					break;
				case AGENT_STATUS_WARNING:
					$agents[$iterator]['status'] = "warning";
					break;
				case AGENT_STATUS_CRITICAL:
					$agents[$iterator]['status'] = "critical";
					break;
				case AGENT_STATUS_UNKNOWN:
					$agents[$iterator]['status'] = "unknown";
					break;
				case AGENT_STATUS_NOT_INIT:
					$agents[$iterator]['status'] = "not_init";
					break;
				default:
					$agents[$iterator]['status'] = "none";
					break;
			}
			
			// Children
			$agents[$iterator]['children'] = array();
			if ($agents[$iterator]['counters']['total'] > 0) {
				switch ($this->childrenMethod) {
					case 'on_demand':
						$agents[$iterator]['searchChildren'] = 1;
						break;
					case 'live':
						$agents[$iterator]['searchChildren'] = 0;
						$agents[$iterator]['children'] = $this->getModules($agents[$iterator]['id']);
						break;
				}
			}
			else {
				switch ($this->childrenMethod) {
					case 'on_demand':
						$agents[$iterator]['searchChildren'] = 0;
						break;
					case 'live':
						$agents[$iterator]['searchChildren'] = 0;
						break;
				}
			}
			
		}
		
		return $agents;
	}
	
	public function getDataAgents($type, $id) {
		
	}
	
	public function getDataModuleGroup() {
	}
	
	public function getDataTag() {
	}
	
	public function getJSON() {
		$this->getData();
		
		return json_encode($this->tree);
	}
	
	public function getArray() {
		$this->getData();
		
		return $this->tree;
	}
}
?>
