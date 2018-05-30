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

ob_start();
require_once ('../include/functions_networkmap.php');
ob_get_clean(); //Fixed unused javascript code.

class Networkmaps {
	private $correct_acl = false;
	private $acl = "AR";
	
	private $default = true;
	private $default_filters = array();
	
	private $group = 0;
	private $type = 0;
	
	private $list_types = null;
	
	function __construct() {
		$system = System::getInstance();
		
		if ($system->checkACL($this->acl)) {
			$this->correct_acl = true;
		}
		else {
			$this->correct_acl = false;
		}
	}
	
	public function ajax($parameter2 = false) {
		$system = System::getInstance();
		
		if (!$this->correct_acl) {
			return;
		}
		else {
			switch ($parameter2) {
				case 'xxx':
					//$this->getFilters();
					//$page = $system->getRequest('page', 0);
					break;
			}
		}
	}
	
	private function getFilters() {
		$system = System::getInstance();
		$user = User::getInstance();
		
		$this->default_filters['group'] = true;
		$this->default_filters['type'] = true;
		
		$this->group = (int)$system->getRequest('group', __("Group"));
		if (!$user->isInGroup($this->acl, $this->group)) {
			$this->group = 0;
		}
		if (($this->group === __("Group")) || ($this->group == 0)) {
			$this->group = 0;
		}
		else {
			$this->default = false;
			$this->default_filters['group'] = false;
		}
		
		$this->type = $system->getRequest('type', __("Type"));
		if (($this->type === __("Type")) || ($this->type === '0')) {
			$this->type = '0';
		}
		else {
			$this->default = false;
			$this->default_filters['type'] = false;
		}
	}
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->getFilters();
			$this->show_networkmaps();
		}
	}
	
	private function show_fail_acl() {
		$error['type'] = 'onStart';
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = System::getDefaultACLFailText();
		if (class_exists("HomeEnterprise"))
			$home = new HomeEnterprise();
		else
			$home = new Home();
		$home->show($error);
	}
	
	private function show_networkmaps() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		$ui->createDefaultHeader(__("Networkmaps"),
			$ui->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Back'),
					'href' => 'index.php?page=home')));
		$ui->showFooter(false);
		$ui->beginContent();
			$filter_title = sprintf(__('Filter Networkmaps by %s'),
				$this->filterNetworkmapsGetString());
			$ui->contentBeginCollapsible($filter_title);
				$ui->beginForm("index.php?page=networkmaps");
					$system = System::getInstance();
					$groups = users_get_groups_for_select(
						$system->getConfig('id_user'), "AR", true, true, false, 'id_grupo');
					$options = array(
						'name' => 'group',
						'title' => __('Group'),
						'label' => __('Group'),
						'items' => $groups,
						'selected' => $this->group
						);
					$ui->formAddSelectBox($options);
					
					$networkmap_types = networkmap_get_filter_types();
					$networkmap_types[0] = __('All');
					$options = array(
						'name' => 'type',
						'title' => __('Type'),
						'label' => __('Type'),
						'items' => $networkmap_types,
						'selected' => $this->type
						);
					$ui->formAddSelectBox($options);
					
					$options = array(
						'icon' => 'refresh',
						'icon_pos' => 'right',
						'text' => __('Apply Filter')
						);
					$ui->formAddSubmitButton($options);
				$html = $ui->getEndForm();
				$ui->contentCollapsibleAddItem($html);
			$ui->contentEndCollapsible();
			$this->listNetworkmapsHtml();
		$ui->endContent();
		$ui->showPage();
	}
	
	private function listNetworkmapsHtml() {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		// Create filter
		$where = array();
		// Order by type field
		$where['order'] = 'type';
		
		if ($this->group != '0') {
			$where['store_group'] = $this->group;
		}
		else {
			$where['store_group'] = array_keys(users_get_groups());
		}
		
		if ($this->type != '0')
			$where['type'] = $this->type;
		
		$network_maps = db_get_all_rows_filter('tnetwork_map',
			$where);
		if (empty($network_maps)) {
			$network_maps = array();
		}
		$list = array();
		foreach ($network_maps as $networkmap) {
			// ACL
			if (! $system->checkACL("AR", $networkmap['store_group']))
				continue;
			
			// If enterprise not loaded then skip this code
			if ($networkmap['type'] == 'policies' and (!defined('PANDORA_ENTERPRISE')))
				continue;
			$row = array();
			$row[__('Name')] = '<a class="ui-link" data-ajax="false" href="index.php?page=networkmap&id=' . $networkmap['id_networkmap'] . '">' . io_safe_output($networkmap['name']) . '</a>';
			$row[__('Type')] = $networkmap['type'];
			$row[__('Group')] = ui_print_group_icon($networkmap["store_group"], true, "groups_small", "" , false);
			$list[] = $row;
		}
		
		if (count($network_maps) == 0) {
			$ui->contentAddHtml('<p style="color: #ff0000;">' . __('No networkmaps') . '</p>');
		}
		else {
			$table = new Table();
			$table->id = 'list_networkmaps';
			$table->importFromHash($list);
			$ui->contentAddHtml($table->getHTML());
		}
				
		$ui->contentAddLinkListener('list_networkmaps');
	}
	
	private function filterNetworkmapsGetString() {
		if ($this->default) {
			return __("(Default)");
		}
		else {
			$filters_to_serialize = array();
			
			if (!$this->default_filters['group']) {
				$filters_to_serialize[] = sprintf(__("Group: %s"),
					groups_get_name($this->group, true));
			}
			if (!$this->default_filters['type']) {
				$networkmap_types = networkmap_get_filter_types();
				$networkmap_types[0] = __('All');
				
				$filters_to_serialize[] = sprintf(__("Type: %s"),
					$networkmap_types[$this->type]);
			}
			
			$string = '(' . implode(' - ', $filters_to_serialize) . ')';
			
			
			//~ $networkmap_types = networkmap_get_filter_types();
			//~ $networkmap_types[0] = __('All');
			//~ $type = $networkmap_types[$this->type];
			//~ $group = groups_get_name($this->group, true);
			//~ 
			//~ 
			//~ $string = sprintf(
				//~ __("(Type: %s - Group: %s)"),
				//~ $type, $group);
			
			return $string;
		}
	}
}
?>
