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
	
	public function  __construct($type, $childrenMethod = "on_demand", $root = null) {
		$this->type = $type;
		$this->root = $root;
		$this->childrenMethod = $childrenMethod;
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
			array('id_grupo', 'nombre'));html_debug_print($groups, true);
		if (empty($groups))
			$groups = array();
		
		
		// Filter by status
		$status = AGENT_STATUS_ALL;
		if (!empty($this->filter['status'])) {
			$status = $this->filter['status'];
		}
		
		
		
		if ($status != AGENT_STATUS_ALL) {
			foreach ($groups as $iterator => $group) {
				$count_ok = groups_monitor_ok(
					array($group['id_grupo']));
				$count_critical = groups_monitor_critical(
					array($group['id_grupo']));
				$count_warning = groups_monitor_warning(
					array($group['id_grupo']));
				$count_unknown = groups_monitor_unknown(
					array($group['id_grupo']));
				$count_not_init = groups_monitor_not_init(
						array($group['id_grupo']));
				
				$remove_group = true;
				switch ($status) {
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
				else {
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
			}
		}
		else {
			foreach ($groups as $iterator => $group) {
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
		}
		
		
		return $groups;
	}
	
	public function getDataGroup() {
		
		if (!empty($this->root)) {
			$parent = $this->root;
		}
		else {
			$parent = 0;
		}
		
		switch ($this->childrenMethod) {
			case 'on_demand':
				$groups = $this->getRecursiveGroup($parent, 1);
				foreach ($groups as $iterator => $group) {
					if (!empty($group['children'])) {
						$groups[$iterator]['searchChildren'] = 1;
						// I hate myself
						unset($groups[$iterator]['children']);
					}
					else {
						$groups[$iterator]['searchChildren'] = 0;
						// I hate myself
						unset($groups[$iterator]['children']);
					}
				}
				break;
		}
		// Make the data
		$this->tree = array();
		foreach ($groups as $group) {
			$data = array();
			$data['id'] = $group['id_grupo'];
			$data['type'] = 'group';
			$data['name'] = $group['nombre'];
			$data['searchChildren'] = $group['searchChildren'];
			
			$this->tree[] = $data;
		}
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
