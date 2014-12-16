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
	
	public function  __construct($type, $root = null) {
		$this->type = $type;
		$this->root = $root;
	}
	
	public function set_type($type) {
		$this->type = $type;
	}
	
	public function set_filter($filter) {
		$this->filter = $filter;
	}
	
	public function get_data() {
		switch ($this->type) {
			case 'os':
				$this->get_data_os();
				break;
			case 'group':
				$this->get_data_group();
				break;
			case 'module_group':
				$this->get_data_module_group();
				break;
			case 'module':
				$this->get_data_module();
				break;
			case 'tag':
				$this->get_data_tag();
				break;
		}
	}
	
	public function get_data_os() {
	}
	
	public function get_data_group() {
		$filter = array();
		
		if (!empty($this->root)) {
			$filter['parent'] = $this->root;
		}
		else {
			$filter['parent'] = 0;
		}
		if (!empty($this->filter['search'])) {
			$filter['nombre'] = "%" . $this->filter['search'] . "%";
		}
		
		
		// First filter by name and father
		$groups = db_get_all_rows_filter('tgrupo',
			$filter,
			array('id_grupo', 'nombre'));
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
			}
		}
		
		// Make the data
		$this->tree = array();
		foreach ($groups as $group) {
			$data = array();
			$data['id'] = $group['id_grupo'];
			$data['name'] = $group['nombre'];
			
			$this->tree[] = $data;
		}
	}
	
	public function get_data_module_group() {
	}
	
	public function get_data_module() {
	}
	
	public function get_data_tag() {
	}
	
	public function get_json() {
		$this->get_data();
		
		return json_encode($this->tree);
	}
}
?>
