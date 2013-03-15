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

class Events {
	private $correct_acl = false;
	
	private $default = true;
	private $free_search = '';
	private $hours_old = 8;
	private $status = 2
	private $type = "";
	private $severity = -1;
	private $filter = 0;
	
	function __construct() {
		$system = System::getInstance();
		
		if ($system->checkACL("ER")) {
			$this->correct_acl = true;
		}
		else {
			$this->correct_acl = false;
		}
	}
	
	private function eventsGetFilters() {
		$system = System::getInstance();
		
		$this->hours_old = $system->getRequest('hours_old', 8);
		if ($this->hours_old != 8) {
			$this->default = false;
		}
		$this->free_search = $system->getRequest('free_search', '');
		if ($this->free_search != '') {
			$this->default = false;
		}
		$this->status = $system->getRequest('status', __("Status"));
		if ($this->status === __("Status")) {
			$this->status = 2;
		}
		else {
			$this->default = false;
		}
		$this->type = $system->getRequest('type', __("Type"));
		if ($this->type === __("Type")) {
			$this->type = "";
		}
		else {
			$this->default = false;
		}
		$this->severity = $system->getRequest('severity', __("Severity"));
		if ($this->severity === __("Severity")) {
			$this->severity = -1;
		}
		else {
			$this->default = false;
		}
		
		$this->filter = $system->getRequest('filter', __('Preset Filters'));
		if ($this->filter === __("Preset Filters")) {
			$this->filter = 0;
		}
		else {
			$this->default = false;
		}
		
		///The user set a preset filter
		$this->loadPresetFilter()
	}
	
	private loadPresetFilter() {
	}
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->eventsGetFilters();
			$this->show_events();
		}
	}
	
	private function show_fail_acl() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		$ui->addDialog(__('You don\'t have access to this page'),
			__('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database'));
		$ui->showPage();
	}
	
	private function show_events() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		$ui->createDefaultHeader(__("PandoraFMS: Events"));
		$ui->showFooter(false);
		$ui->beginContent();
		$filter_title = sprintf(__('Filter Events by %s'), $this->filterEventsGetString());
			$ui->contentBeginCollapsible($filter_title);
				$ui->beginForm();
					$options = array(
						'name' => 'page',
						'type' => 'hidden',
						'value' => 'events'
						);
					$ui->formAddInput($options);
					$items = array('caca' => 'caca', 'pis' => 'pis',
						'pedo' => 'pedo');
					$options = array(
						'name' => 'filter',
						'title' => __('Preset Filters'),
						'label' => __('Preset Filters'),
						'items' => $items
						);
					$ui->formAddSelectBox($options);
					$items = array('caca' => 'caca', 'pis' => 'pis',
						'pedo' => 'pedo');
					$options = array(
						'name' => 'group',
						'title' => __('Group'),
						'label' => __('Group'),
						'items' => $items
						);
					$items = array('caca' => 'caca', 'pis' => 'pis',
						'pedo' => 'pedo');
					$options = array(
						'name' => 'status',
						'title' => __('Status'),
						'label' => __('Status'),
						'items' => $items
						);
					$ui->formAddSelectBox($options);
					$items = array('caca' => 'caca', 'pis' => 'pis',
						'pedo' => 'pedo');
					$options = array(
						'name' => 'type',
						'title' => __('Type'),
						'label' => __('Type'),
						'items' => $items
						);
					$ui->formAddSelectBox($options);
					$items = array('caca' => 'caca', 'pis' => 'pis',
						'pedo' => 'pedo');
					$options = array(
						'name' => 'severity',
						'title' => __('Severity'),
						'label' => __('Severity'),
						'items' => $items
						);
					$ui->formAddSelectBox($options);
					$options = array(
						'name' => 'free_search',
						'value' => $this->free_search,
						'placeholder' => __('Free search')
						);
					$ui->formAddInputSearch($options);
					$options = array(
						'label' => __('Max. hours old'),
						'name' => 'hours_old',
						'value' => $this->hours_old,
						'min' => 0,
						'max' => 24 * 7,
						'step' => 8
						);
					$ui->formAddSlider($options);
					$options = array(
						'icon' => 'refresh',
						'icon_pos' => 'right',
						'text' => __('Apply Filter')
						);
					$ui->formAddSubmitButton($options);
				$html = $ui->getEndForm();
				$ui->contentCollapsibleAddItem($html);
			$ui->contentEndCollapsible();
			$this->listEvents();
		$ui->endContent();
		$ui->showPage();
	}
	
	function listEvents() {
		$ui = Ui::getInstance();
		$system = System::getInstance();
		
		$sql_post = '';
		$result = events_get_events_grouped($sql_post,
			0, $system->getPageSize(), false, false);
		
		$events = array(
			array(
				__('Status') => 'icon',
				__('Event Name') => 'nombre del evento',
				__('Timestamp') => '2 days',
				__('Agent') => 'pepito'),
			array(
				__('Status') => 'icon',
				__('Event Name') => 'nombre del evento',
				__('Timestamp') => '2 days',
				__('Agent') => 'pepito'));
		
		$ui = Ui::getInstance();
		$table = new Table();
		$table->importFromHash($events);
		$ui->contentAddHtml($table->getHTML());
	}
	
	private function filterEventsGetString() {
		if ($this->default)
			return __("(Default)");
		else {
			if ($this->filter) {
				//TODO put the name of filter
			}
			else {
				/*
				$string = sprintf(__("(Status: %s Hours: %s Type: %s Severity: %s Free Search: %s)"),
					$this->hours_old,
					
				
				return $string;
				*/
			}
		}
	}
}

?>