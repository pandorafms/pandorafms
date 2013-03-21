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

include_once("../include/functions_users.php");

class Groups {
	private $correct_acl = false;
	
	private $groups = array();
	private $status = array();
	
	function __construct() {
		$system = System::getInstance();
		
		if ($system->checkACL()) {
			$this->correct_acl = true;
			
			$this->groups = $this->getListGroups();
			
			foreach ($this->groups as $key => $group) {
				$this->status[$key] = $group['status'];
				unset($this->groups[$key]['status']);
			}
		}
		else {
			$this->correct_acl = false;
		}
	}
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->show_group();
		}
	}
	
	private function show_fail_acl() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		
		$options['type'] = 'onStart';
		$options['title_text'] = __('You don\'t have access to this page');
		$options['content_text'] = __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');
		$ui->addDialog($options);
		
		$ui->showPage();
	}
	
	private function show_group() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		$ui->createDefaultHeader(__("PandoraFMS: Groups"));
		$ui->showFooter(false);
		$ui->beginContent();
			
			$table = new Table();
			$table->setClass('group_view');
			$table->importFromHash($this->groups);
			$table->setRowClass($this->status);
			$ui->contentAddHtml($table->getHTML());
			
		$ui->endContent();
		$ui->showPage();
	}
	
	private function getListGroups() {
		$return = array();
		
		$user = User::getInstance();
		
		// Get group list that user has access
		$groups_full = users_get_groups($user->getIdUser(), "AR", true, true);
		$groups = array();
		foreach ($groups_full as $group) {
			$groups[$group['id_grupo']]['name'] = $group['nombre'];
			
			if ($group['id_grupo'] != 0) {
				$groups[$group['parent']]['childs'][] = $group['id_grupo'];
				$groups[$group['id_grupo']]['prefix'] = $groups[$group['parent']]['prefix'].'&nbsp;&nbsp;&nbsp;';
			}
			else {
				$groups[$group['id_grupo']]['prefix'] = '';
			}
			
			if (!isset($groups[$group['id_grupo']]['childs'])) {
				$groups[$group['id_grupo']]['childs'] = array();
			}
		}
		
		// For each valid group for this user, take data from agent and modules
		foreach ($groups as $id_group => $group) {
			$rows = groups_get_group_row($id_group, $groups, $group, $printed_groups, false);
			if (!empty($rows))
				$return = array_merge($return, $rows);
		}
		
		return $return;
	}
}