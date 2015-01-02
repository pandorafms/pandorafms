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

	private $userGroups;
	
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
			case 'module':
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
		// ACL groups
		if (!empty($this->userGroups))
			$filter['id_grupo'] = $this->userGroups;
		
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

	private function processModule (&$module) {
		$module['type'] = 'module';
		$module['id'] = $module['id_agente_modulo'];
		$module['name'] = $module['nombre'];
		$module['id_module_type'] = $module['id_tipo_modulo'];
		// $module['icon'] = modules_get_type_icon($module['id_tipo_modulo']);
		$module['value'] = modules_get_last_value($module['id']);

		// Status
		switch (modules_get_status($module['id'])) {
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
	}

	private function processModules ($modules_aux, &$modules) {
		if (!empty($modules_aux)) {
			foreach ($modules_aux as $module) {
				$this->processModule($module);
				$modules[] = $module;
			}
		}
	}

	public function getModules ($parent = 0, $filter = array()) {
		$modules = array();

		$modules_aux = agents_get_modules($parent,
			array('id_agente_modulo', 'nombre', 'id_tipo_modulo'), $filter);
		
		if (empty($modules_aux))
			$modules_aux = array();
		
		// Process the modules
		$this->processModules($modules_aux, $modules);

		return $modules;
	}
	
	public function getDataModules() {
		// Get the parent
		if (empty($this->root))
			$parent = 0;
		else
			$parent = $this->root;

		// ACL Group
		$group_acl =  "";
		if (!empty($this->userGroups)) {
			$user_groups_str = implode(",", $this->userGroups);
			$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
		}

		$sql = "SELECT tam.nombre AS module_name, tam.id_agente_modulo, tam.id_tipo_modulo,
					ta.id_agente, ta.nombre AS agent_name
				FROM tagente ta, tagente_modulo tam
				WHERE ta.id_agente = tam.id_agente
					$group_acl
				ORDER BY tam.nombre";
		$data = db_process_sql($sql);

		if (empty($data)) {
			$data = array();
		}

		$modules = array();
		$actual_module_root = array(
				'name' => '',
				'children' => array(),
				'counters' => array()
			);
		foreach ($data as $key => $value) {
			$agent = array();
			$agent['id_agente'] = $value['id_agente'];
			$agent['nombre'] = $value['agent_name'];

			$this->processAgent(&$agent, array(), false);

			$module = array();
			$module['id_agente_modulo'] = $value['id_agente_modulo'];
			$module['nombre'] = $value['module_name'];
			$module['id_tipo_modulo'] = $value['id_tipo_modulo'];

			$this->processModule($module);

			$agent['children'] = array($module);

			if ($actual_module_root['name'] == $module['name']) {
				$actual_module_root['children'][] = $agent;

				// Increase counters
				$actual_module_root['counters']['total']++;

				if (isset($actual_module_root['counters'][$agent['status']]))
					$actual_module_root['counters'][$agent['status']]++;
			}
			else {
				if (!empty($actual_module_root['name']))
					$modules[] = $actual_module_root;

				$actual_module_root = array();
				$actual_module_root['name'] = $module['name'];
				$actual_module_root['children'] = array($agent);

				// Initialize counters
				$actual_module_root['counters'] = array();
				$actual_module_root['counters']['total'] = 0;
				$actual_module_root['counters']['alerts'] = 0;
				$actual_module_root['counters']['critical'] = 0;
				$actual_module_root['counters']['warning'] = 0;
				$actual_module_root['counters']['unknown'] = 0;
				$actual_module_root['counters']['not_init'] = 0;
				$actual_module_root['counters']['ok'] = 0;

				// Increase counters
				$actual_module_root['counters']['total']++;

				if (isset($actual_module_root['counters'][$agent['status']]))
					$actual_module_root['counters'][$agent['status']]++;
			}
		}
		if (!empty($actual_module_root['name'])) {
			$modules[] = $actual_module_root;
		}

		$this->tree = $modules;
	}

	private function processAgent (&$agent, $modulesFilter = array(), $searchChildren = true) {
		$agent['type'] = 'agent';
		$agent['id'] = $agent['id_agente'];
		$agent['name'] = $agent['nombre'];
		
		// Counters
		$agent['counters'] = array();
		$agent['counters']['unknown'] =
			agents_monitor_unknown($agent['id']);
		$agent['counters']['critical'] =
			agents_monitor_critical($agent['id']);
		$agent['counters']['warning'] =
			agents_monitor_warning($agent['id']);
		$agent['counters']['not_init'] =
			agents_monitor_notinit($agent['id']);
		$agent['counters']['ok'] =
			agents_monitor_ok($agent['id']);
		$agent['counters']['total'] =
			agents_monitor_total($agent['id']);
		$agent['counters']['alerts'] =
			agents_get_alerts_fired($agent['id']);

		// Status
		switch (agents_get_status($agent['id'])) {
			case AGENT_STATUS_NORMAL:
				$agent['status'] = "ok";
				break;
			case AGENT_STATUS_WARNING:
				$agent['status'] = "warning";
				break;
			case AGENT_STATUS_CRITICAL:
				$agent['status'] = "critical";
				break;
			case AGENT_STATUS_UNKNOWN:
				$agent['status'] = "unknown";
				break;
			case AGENT_STATUS_NOT_INIT:
				$agent['status'] = "not_init";
				break;
			default:
				$agent['status'] = "none";
				break;
		}
		
		// Children
		$agent['children'] = array();
		if ($agent['counters']['total'] > 0) {
			switch ($this->childrenMethod) {
				case 'on_demand':
					$agent['searchChildren'] = 1;
					break;
				case 'live':
					$agent['searchChildren'] = 0;

					if ($searchChildren)
						$agent['children'] = $this->getModules($agent['id'], $modulesFilter);
					break;
			}
		}
		else {
			switch ($this->childrenMethod) {
				case 'on_demand':
					$agent['searchChildren'] = 0;
					break;
				case 'live':
					$agent['searchChildren'] = 0;
					break;
			}
		}
	}

	private function processAgents (&$agents, $modulesFilter = array()) {
		if (!empty($agents)) {
			foreach ($agents as $iterator => $agent) {
				$this->processAgent($agents[$iterator], $modulesFilter);
			}
		}
	}

	public function getAgents ($parent = 0, $parentType = '') {
		switch ($parentType) {
			case 'group':
				// ACL Groups
				if (!empty($this->userGroups) && !empty($parent)) {
					if (!isset($this->userGroups[$parent]))
						return array();
				}
				$filter = array(
					'id_grupo' => $parent,
					'status' => $this->filter['status'],
					'nombre' => "%" . $this->filter['search'] . "%"
					);
				$agents = agents_get_agents($filter, array('id_agente', 'nombre'));
				if (empty($agents)) {
					$agents = array();
				}
				break;
			default:
				return array();
				break;
		}
		
		$this->processAgents($agents);
		
		return $agents;
	}
	
	public function getDataAgents($type, $id) {
		
	}
	
	public function getDataModuleGroup() {
		// Get the parent
		if (empty($this->root))
			$parent = 0;
		else
			$parent = $this->root;

		// ACL Group
		$group_acl =  "";
		if (!empty($this->userGroups)) {
			$user_groups_str = implode(",", $this->userGroups);
			$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
		}

		$module_groups = modules_get_modulegroups();

		if (!empty($module_groups)) {
			$sql = "SELECT tam.nombre AS module_name, tam.id_agente_modulo,
						tam.id_tipo_modulo, tam.id_module_group,
						ta.id_agente, ta.nombre AS agent_name
					FROM tagente ta, tagente_modulo tam
					WHERE ta.id_agente = tam.id_agente
						$group_acl
					ORDER BY tam.id_module_group ASC, ta.id_agente ASC";
			$data = db_process_sql($sql);
		}

		if (empty($data)) {
			$data = array();
		}

		$nodes = array();
		$actual_module_group_root = array(
				'id' => null,
				'name' => '',
				'children' => array(),
				'counters' => array()
			);
		$actual_agent = array();
		foreach ($data as $key => $value) {

			// Module
			$module = array();
			$module['id_agente_modulo'] = (int) $value['id_agente_modulo'];
			$module['nombre'] = $value['module_name'];
			$module['id_tipo_modulo'] = (int) $value['id_tipo_modulo'];
			$module['id_module_group'] = (int) $value['id_module_group'];

			$this->processModule($module);




			// $agent = array();
			// $agent['id_agente'] = $value['id_agente'];
			// $agent['nombre'] = $value['agent_name'];

			// $this->processAgent(&$agent, array(), false);

			// $agent['children'] = array($module);

			// Module group
			if ($actual_module_group_root['id'] === $module['id_module_group']) {
				// Agent
				if (empty($actual_agent) || $actual_agent['id'] !== (int)$value['id_agente']) {
					// Add the last agent to the agent module
					if (!empty($actual_agent))
						$actual_module_group_root['children'][] = $actual_agent;

					// Create the new agent
					$actual_agent = array();
					$actual_agent['id_agente'] = $value['id_agente'];
					$actual_agent['nombre'] = $value['agent_name'];
					$actual_agent['children'] = array();

					$this->processAgent(&$actual_agent, array(), false);

					// Add the module to the agent
					$actual_agent['children'][] = $module;

					// Increase counters
					$actual_module_group_root['counters']['total']++;

					if (isset($actual_module_group_root['counters'][$actual_agent['status']]))
						$actual_module_group_root['counters'][$actual_agent['status']]++;
				}
				else {
					$actual_agent['children'][] = $module;
				}
			}
			else {
				if ($actual_module_group_root['id'] !== null) {
					$actual_module_group_root['children'][] = $actual_agent;
					$nodes[] = $actual_module_group_root;
				}

				// Create new module group
				$actual_module_group_root = array();
				$actual_module_group_root['id'] = $module['id_module_group'];
				$actual_module_group_root['children'] = array($agent);

				if (isset($module_groups[$module['id_module_group']])) {
					$actual_module_group_root['name'] = $module_groups[$module['id_module_group']];
				}
				else {
					$actual_module_group_root['name'] = __('Not assigned');
				}
				
				// Initialize counters
				$actual_module_group_root['counters'] = array();
				$actual_module_group_root['counters']['total'] = 0;
				$actual_module_group_root['counters']['alerts'] = 0;
				$actual_module_group_root['counters']['critical'] = 0;
				$actual_module_group_root['counters']['warning'] = 0;
				$actual_module_group_root['counters']['unknown'] = 0;
				$actual_module_group_root['counters']['not_init'] = 0;
				$actual_module_group_root['counters']['ok'] = 0;

				// Increase counters
				$actual_module_group_root['counters']['total']++;

				if (isset($actual_module_group_root['counters'][$agent['status']]))
					$actual_module_group_root['counters'][$agent['status']]++;
			}
		}
		if ($actual_module_group_root['id'] !== null) {
			$actual_module_group_root['children'][] = $actual_agent;
			$nodes[] = $actual_module_group_root;
		}

		$this->tree = $nodes;
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
