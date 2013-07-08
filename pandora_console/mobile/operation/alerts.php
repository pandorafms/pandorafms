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

class Alerts {
	private $correct_acl = false;
	private $acl = "AR";
	
	private $default = true;
	private $default_filters = array();
	
	private $free_search = '';
	private $group = 0;
	private $status = 'all';
	private $standby = -1;
	private $id_agent = 0;
	private $all_alerts = false;
	
	private $alert_status_items = null;
	private $alert_standby_items = null;
	
	private $columns = null;
	
	function __construct() {
		$this->alert_status_items = array(
			'all_enabled' => __('All (Enabled)'),
			'all' => __('All'),
			'fired' => __('Fired'),
			'notfired' => __('Not fired'),
			'disabled' => __('Disabled'));
		
		$this->alert_standby_items = array(
			'-1' => __('All'),
			'1' => __('Standby on'),
			'0' => __('Standby off'));
		
		$this->columns = array('agent' => 1);
		
		$system = System::getInstance();
		
		if ($system->checkACL($this->acl)) {
			$this->correct_acl = true;
		}
		else {
			$this->correct_acl = false;
		}
	}
	
	private function alertsGetFilters() {
		$system = System::getInstance();
		$user = User::getInstance();
		
		
		$this->default_filters['standby'] = true;
		$this->default_filters['group'] = true;
		$this->default_filters['status'] = true;
		$this->default_filters['free_search'] = true;
		
		$this->free_search = $system->getRequest('free_search', '');
		if ($this->free_search != '') {
			$this->default = false;
			$this->default_filters['free_search'] = false;
		}
		
		$this->status = $system->getRequest('status', __("Status"));
		if (($this->status === __("Status")) || ($this->status == 'all')) {
			$this->status = 'all';
		}
		else {
			$this->default = false;
			$this->default_filters['status'] = false;
		}
		
		$this->group = $system->getRequest('group', __("Group"));
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
		
		$this->standby = $system->getRequest('standby', __('Stand by'));
		if (($this->standby === __('Stand by')) || ($this->standby == -1)) {
			$this->standby = -1;
		}
		else {
			$this->default = false;
			$this->default_filters['standby'] = false;
		}
	}
	
	public function setFilters($filters) {
		if (isset($filters['id_agent'])) {
			$this->id_agent = $filters['id_agent'];
		}
		if (isset($filters['all_alerts'])) {
			$this->all_alerts = $filters['all_alerts'];
		}
	}
	
	public function ajax($parameter2 = false) {
		$system = System::getInstance();
		
		if (!$this->correct_acl) {
			return;
		}
		else {
			switch ($parameter2) {
				case 'xxxx':
					break;
			}
		}
	}
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->alertsGetFilters();
			$this->show_alerts();
		}
	}
	
	private function show_fail_acl() {
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');
		$home = new Home();
		$home->show($error);
	}
	
	private function show_alerts() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		$ui->createDefaultHeader(__("PandoraFMS: Alerts"),
			$ui->createHeaderButton(
					array('icon' => 'back',
						'pos' => 'left',
						'text' => __('Back'),
						'href' => 'index.php')));
		$ui->showFooter(false);
		$ui->beginContent();
			$filter_title = sprintf(__('Filter Alerts by %s'),
				$this->filterAlertsGetString());
			$ui->contentBeginCollapsible($filter_title);
				$ui->beginForm();
					$options = array(
						'name' => 'page',
						'type' => 'hidden',
						'value' => 'alerts'
						);
					$ui->formAddInput($options);
					
					$system = System::getInstance();
					$groups = users_get_groups_for_select(
						$system->getConfig('id_user'), "ER", true, true, false, 'id_grupo');
					$options = array(
						'name' => 'group',
						'title' => __('Group'),
						'label' => __('Group'),
						'items' => $groups,
						'selected' => $this->group
						);
					$ui->formAddSelectBox($options);
					
					$options = array(
						'name' => 'free_search',
						'value' => $this->free_search,
						'placeholder' => __('Free search')
						);
					$ui->formAddInputSearch($options);
					
					$options = array(
						'name' => 'status',
						'title' => __('Status'),
						'label' => __('Status'),
						'items' => $this->alert_status_items,
						'selected' => $this->status
						);
					$ui->formAddSelectBox($options);
					
					$options = array(
						'name' => 'standby',
						'title' => __('Stand by'),
						'label' => __('Stand by'),
						'items' => $this->alert_standby_items,
						'selected' => $this->standby
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
			$this->listAlertsHtml();
		$ui->endContent();
		$ui->showPage();
	}
	
	public function disabledColumns($columns = null) {
		if (!empty($columns)) {
			foreach ($columns as $column) {
				$this->columns[$column] = 0;
			}
		}
	}
	
	public function listAlertsHtml ($return = false) {
		$countAlerts = alerts_get_alerts($this->group,
			$this->free_search, $this->status, $this->standby, "AR", true, $this->id_agent);
		
		$alerts = alerts_get_alerts($this->group,
			$this->free_search, $this->status, $this->standby, "AR", false, $this->id_agent);
		if (empty($alerts))
			$alerts = array();
		
		$table = array();
		foreach ($alerts as $alert) {
			if ($alert['alert_disabled']) {
				$disabled_style = "<i style='color: grey;'>%s</i>";
			}
			else {
				$disabled_style = "%s";
			}
			
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
			
			$row = array();
			if ($this->columns['agent']) {
				$row[__('Agent')] = sprintf($disabled_style,
					'<a class="ui-link" data-ajax="false" href="index.php?page=agent&id=' . $alert['id_agente'] . '">' . io_safe_output($alert['agent_name'])) . '</a>';
			}
			$row[__('Module')] = sprintf($disabled_style,
				io_safe_output($alert['module_name']));
			$row[__('Template')] = sprintf($disabled_style,
				io_safe_output($alert['template_name']));
			$row[__('Last Fired')] = sprintf($disabled_style,
				ui_print_timestamp ($alert["last_fired"], true));
			$row[__('Status')] = ui_print_status_image($status, $title, true);
			
			
			$table[] = $row;
		}
		
		$ui = UI::getInstance();
		if (empty($table)) {
			$html = '<p style="color: #ff0000;">' . __('No alerts') . '</p>';
			if (!$return) {
				$ui->contentAddHtml($html);
			}
			else {
				return $html;
			}
		}
		else {
			$tableHTML = new Table();
			$tableHTML->importFromHash($table);
			if (!$return) {
				$ui->contentAddHtml($tableHTML->getHTML());
			}
			else {
				return $tableHTML->getHTML();
			}
		}
	}
	
	private function filterAlertsGetString() {
		
		if ($this->default) {
			return __("(Default)");
		}
		else {
			$filters_to_serialize = array();
			
			if (!$this->default_filters['standby']) {
				$filters_to_serialize[] = sprintf(__("Standby: %s"),
					$this->alert_standby_items[$this->standby]);
			}
			if (!$this->default_filters['group']) {
				$filters_to_serialize[] = sprintf(__("Group: %s"),
					groups_get_name($this->group, true));
			}
			if (!$this->default_filters['status']) {
				$filters_to_serialize[] = sprintf(__("Status: %s"),
					$this->alert_status_items[$this->status]);
			}
			if (!$this->default_filters['free_search']) {
				$filters_to_serialize[] = sprintf(__("Free Search: %s"),
					$this->free_search);
			}
			
			$string = '(' . implode(' - ', $filters_to_serialize) . ')';
			
			return $string;
			
			//~ $status_text = $this->alert_status_items[$this->status];
			//~ $standby_text = $this->alert_standby_items[$this->standby];
			//~ $group_text = groups_get_name($this->group, true);
			
			//~ return sprintf(__('(Status: %s - Standby: %s - Group: %s - Free Search: %s)'),
				//~ $status_text, $standby_text, $group_text, $this->free_search);
		}
	}
}

?>