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
			case 'module':
				$this->getDataModule();
				break;
			case 'tag':
				$this->getDataTag();
				break;
		}
	}
	
	public function getDataOS() {
	}
	
	private function getRecursiveGroup($parent, $limit = null) {
		$filter = array();
		
		$filter['parent'] = $parent;
		
		if (!empty($this->filter['search'])) {
			$filter['nombre'] = "%" . $this->filter['search'] . "%";
		}
		
		// First filter by name and father
		$groups = db_get_all_rows_filter('tgrupo',
			$filter,
			array('id_grupo', 'nombre', 'icon'));
		if (empty($groups))
			$groups = array();
		
		// Filter by status
		$filter_status = AGENT_STATUS_ALL;
		if (!empty($this->filter['status'])) {
			$filter_status = $this->filter['status'];
		}
		
		foreach ($groups as $iterator => $group) {
			$groups[$iterator]['counters'] = array();
			$groups[$iterator]['counters']['ok'] =
				groups_monitor_ok(array($group['id_grupo']));
			$groups[$iterator]['counters']['critical'] =
				groups_monitor_critical(array($group['id_grupo']));
			$groups[$iterator]['counters']['warning'] =
				groups_monitor_warning(array($group['id_grupo']));
			$groups[$iterator]['counters']['unknown'] =
				groups_monitor_unknown(array($group['id_grupo']));
			$groups[$iterator]['counters']['not_init'] =
				groups_monitor_not_init(array($group['id_grupo']));
			
			if ($filter_status != AGENT_STATUS_ALL) {
				$remove_group = true;
				switch ($filter_status) {
					case AGENT_STATUS_NORMAL:
						if (($count_critical == 0) &&
							($count_warning == 0) &&
							($count_unknown == 0) &&
							($count_not_init == 0)) {
							
							$remove_group = false;
						}
						break;
					case AGENT_STATUS_WARNING:
						if ($count_warning > 0)
							$remove_group = false;
						break;
					case AGENT_STATUS_CRITICAL:
						if ($count_critical > 0)
							$remove_group = false;
						break;
					case AGENT_STATUS_UNKNOWN:
						if ($count_unknown > 0)
							$remove_group = false;
						break;
					case AGENT_STATUS_NOT_INIT:
						if ($count_not_init > 0)
							$remove_group = false;
						break;
				}
				
				if ($remove_group)
					unset($groups[$iterator]);
			}

			if (is_null($limit)) {
				$groups[$iterator]['children'] =
					$this->getRecursiveGroup($group['id_grupo']);
			}
			else if ($limit >= 1) {
				$groups[$iterator]['children'] =
					$this->getRecursiveGroup(
						$group['id_grupo'],
						($limit - 1));
			}
		}
		
		if ($parent == 0) {
			$agents = array();
		}
		else {
			$agents = $this->getDataAgents('group', $parent);
		}
		
		$data = array_merge($groups, $agents);
		
		return $data;
	}
	
	public function getDataGroup() {
		
		if (!empty($this->root)) {
			$parent = $this->root;
		}
		else {
			$parent = 0;
		}
		
		$data = $this->getRecursiveGroup($parent, 1);
		switch ($this->childrenMethod) {
			case 'on_demand':
				foreach ($data as $iterator => $item) {
					if (!empty($item['children'])) {
						$data[$iterator]['searchChildren'] = 1;
						// I hate myself
						unset($data[$iterator]['children']);
					}
					else {
						$data[$iterator]['searchChildren'] = 0;
						// I hate myself
						unset($data[$iterator]['children']);
					}
				}
				break;
			
		}
		
		switch ($this->countAgentStatusMethod) {
			case 'on_demand':
				foreach ($groups as $iterator => $group) {
					if (!empty($group['counters'])) {
						$groups[$iterator]['searchCounters'] = 1;
						// I hate myself
						unset($groups[$iterator]['counters']);
					}
					else {
						$groups[$iterator]['searchCounters'] = 0;
						// I hate myself
						unset($groups[$iterator]['counters']);
					}
				}
				break;
		}
		
		// Make the data
		$this->tree = array();
		foreach ($data as $item) {
			$temp = array();
			$temp['id'] = $item['id_grupo'];
			$temp['type'] = 'group';
			$temp['name'] = $item['nombre'];
			$temp['icon'] = $item['icon'];
			$temp['searchChildren'] = $item['searchChildren'];
			$temp['searchCounters'] = $item['searchCounters'];
			
			$this->tree[] = $temp;
		}
	}
	
	public function getDataAgents($type, $id) {
		switch ($type) {
			case 'group':
				$filter = array(
					'id_grupo' => $id,
					'status' => $this->filter['status'],
					'nombre' => "%" . $this->filter['search'] . "%"
					);
				$agents = agents_get_agents($filter);
				if (empty($agents)) {
					$agents = array();
				}
				break;
		}
		
		foreach ($agents as $iterator => $agent) {
			$agents[$iterator]['type'] = 'agent';
		}
		
		return $agents;
	}
	
	public function getDataModuleGroup() {
	}
	
	public function getDataModule() {
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
