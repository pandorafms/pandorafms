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
			//~ foreach ($this->groups as $key => $group) {
				//~ $this->status[$key] = $group['status'];
				//~ unset($this->groups[$key]['status']);
			//~ }
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
		$error['type'] = 'onStart';
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');
		if (class_exists("HomeEnterprise"))
			$home = new HomeEnterprise();
		else
			$home = new Home();
		$home->show($error);
	}
	
	private function show_group() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		$ui->createDefaultHeader(__("Groups"), $ui->createHeaderButton(
					array('icon' => 'back',
						'pos' => 'left',
						'text' => __('Back'),
						'href' => 'index.php?page=home')));
		$ui->showFooter(false);
		$ui->beginContent();
			
			$ui->contentAddHtml('<div class="list_groups" data-role="collapsible-set" data-theme="a" data-content-theme="d">');
				$count = 0;
				foreach ($this->groups as $group) {
					$ui->contentAddHtml('
						<style type="text/css">
							.ui-icon-group_' . $count . ' {
								background: url("' . $group['group_icon'] . '") no-repeat scroll 0 0 #F3F3F3 !important;
								width: 24px;
								height: 24px;
								margin-top: -12px !important;
							}
						</style>
						');
					$ui->contentAddHtml('<div data-collapsed-icon="group_' . $count . '" ' .
						'data-expanded-icon="group_' . $count . '" ' .
						'data-iconpos="right" data-role="collapsible" ' .
						'data-collapsed="true" data-theme="' . $group['status'] . '" data-content-theme="d">');
					$ui->contentAddHtml('<h4>' . $group['group_name'] . '</h4>');
					$ui->contentAddHtml('<ul data-role="listview" class="groups_sublist">');
					
					foreach ($group['counts'] as $k => $v) {
						if($v == 0) {
							$group['counts'][$k] = '-';
						}
					}
					
					$ui->contentAddHtml('<li data-icon="false"><a href="' . $group['links'][__('Agents')] . '">' .
						'<span class="name_count">' . html_print_image('images/agent.png', true) . __('Total agents') . '</span>' .
						'<span class="number_count">' . $group['counts'][__('Agents')] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . $group['links'][__('Agents unknown')] . '">' .
						'<span class="name_count">' . html_print_image('images/agent_unknown.png', true) . __('Agents unknown') . '</span>' .
						'<span class="number_count">' . $group['counts'][__('Agents unknown')] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . $group['links'][__('Unknown')] . '">' .
						'<span class="name_count">' . html_print_image('images/module_unknown.png', true) . __('Unknown modules') . '</span>' .
						'<span class="number_count">' . $group['counts'][__('Unknown')] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . $group['links'][__('Not init')] . '">' .
						'<span class="name_count">' . html_print_image('images/module_notinit.png', true) . __('Not init modules') . '</span>' .
						'<span class="number_count">' . $group['counts'][__('Not init')] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . $group['links'][__('Normal')] . '">' .
						'<span class="name_count">' . html_print_image('images/module_ok.png', true) . __('Normal modules') . '</span>' .
						'<span class="number_count">' . $group['counts'][__('Normal')] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . $group['links'][__('Warning')] . '">' .
						'<span class="name_count">' . html_print_image('images/module_warning.png', true) . __('Warning modules') . '</span>' .
						'<span class="number_count">' . $group['counts'][__('Warning')] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . $group['links'][__('Critical')] . '">' .
						'<span class="name_count">' . html_print_image('images/module_critical.png', true) . __('Critical modules') . '</span>' .
						'<span class="number_count">' . $group['counts'][__('Critical')] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . $group['links'][__('Alerts fired')] . '">' .
						'<span class="name_count">' . html_print_image('images/bell_error.png', true) . __('Alerts fired') . '</span>' .
						'<span class="number_count">' . $group['counts'][__('Alerts fired')] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('</ul>');
					$ui->contentAddHtml('</div>');
					
					$count++;
				}
			$ui->contentAddHtml('</div>');
			
			//$ui->contentAddHtml(ob_get_clean());
			//~ $table = new Table();
			//~ $table->setId('list_groups');
			//~ $table->setClass('group_view');
			//~ $table->importFromHash($this->groups);
			//~ $table->setRowClass($this->status);
			//~ $ui->contentAddHtml($table->getHTML());
			
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
			$rows = groups_get_group_row_data($id_group, $groups, $group, $printed_groups);
			
			if (!empty($rows))
				$return = array_merge($return, $rows);
		}
		
		return $return;
	}
}
