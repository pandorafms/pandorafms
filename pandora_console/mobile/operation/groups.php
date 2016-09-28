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
include_once("../include/functions_groupview.php");

class Groups {
	private $correct_acl = false;
	private $acl = 'AR';
	
	private $groups = array();
	private $status = array();
	
	function __construct() {
		$system = System::getInstance();
		
		if ($system->checkACL($this->acl)) {
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
				$url_agent = 'index.php?page=agents&group=%s&status=%s';
				$url_modules = 'index.php?page=modules&group=%s&status=%s';
				
				foreach ($this->groups as $group) {
					// Calculate entire row color
					if ($group["_monitors_alerts_fired_"] > 0) {
						$color_class = 'group_view_alrm';
						$status_image = ui_print_status_image ('agent_alertsfired_ball.png', "", true);
					}
					elseif ($group["_monitors_critical_"] > 0) {
						$color_class = 'group_view_crit';
						$status_image = ui_print_status_image ('agent_critical_ball.png', "", true);
					}
					elseif ($group["_monitors_warning_"] > 0) {
						$color_class = 'group_view_warn';
						$status_image = ui_print_status_image ('agent_warning_ball.png', "", true);
					}
					elseif ($group["_monitors_ok_"] > 0)  {
						
						$color_class = 'group_view_ok';
						$status_image = ui_print_status_image ('agent_ok_ball.png', "", true);
					}
					elseif (($group["_monitors_unknown_"] > 0) ||  ($group["_agents_unknown_"] > 0)) {
						$color_class = 'group_view_unk';
						$status_image = ui_print_status_image ('agent_no_monitors_ball.png', "", true);
					}
					else {
						$color_class = '';
						$status_image = ui_print_status_image ('agent_no_data_ball.png', "", true);
					}
					$group['icon'] = ($group['icon'] == '') ? 'world' : $group['icon'];
					$ui->contentAddHtml('
						<style type="text/css">
							.ui-icon-group_' . $count . ' {
								background: url("../images/groups_small/'.$group['icon'].'.png") no-repeat scroll 0 0 #F3F3F3 !important;
								width: 24px;
								height: 24px;
								margin-top: -12px !important;
							}
						</style>
						');
					$ui->contentAddHtml('<div data-collapsed-icon="group_' . $count . '" ' .
						'data-expanded-icon="group_' . $count . '" ' .
						'data-iconpos="right" data-role="collapsible" ' .
						'data-collapsed="true" data-theme="' . $color_class . '" data-content-theme="d">');
					$ui->contentAddHtml('<h4>' . $group['_name_'] . '</h4>');
					$ui->contentAddHtml('<ul data-role="listview" class="groups_sublist">');
					
					$ui->contentAddHtml('<li data-icon="false"><a href="' . sprintf($url_agent, $group['_id_'], AGENT_STATUS_ALL) . '">' .
						'<span class="name_count">' . html_print_image('images/agent.png', true, false,false, false, false, true) . __('Total agents') . '</span>' .
						'<span class="number_count">' . $group['_total_agents_'] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . sprintf($url_agent, $group['_id_'], AGENT_STATUS_NOT_INIT) . '">' .
						'<span class="name_count">' . html_print_image('images/agent_notinit.png', true, false,false, false, false, true) . __('Agents not init') . '</span>' .
						'<span class="number_count">' . $group['_agents_not_init_'] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . sprintf($url_agent, $group['_id_'], AGENT_STATUS_CRITICAL) . '">' .
						'<span class="name_count">' . html_print_image('images/agent_critical.png', true, false,false, false, false, true) . __('Agents critical') . '</span>' .
						'<span class="number_count">' . $group['_agents_critical_'] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . sprintf($url_agent, $group['_id_'], AGENT_STATUS_UNKNOWN) . '">' .
						'<span class="name_count">' . html_print_image('images/agent_unknown.png', true, false,false, false, false, true) . __('Agents unknown') . '</span>' .
						'<span class="number_count">' . $group['_agents_unknown_'] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . sprintf($url_modules, $group['_id_'], AGENT_MODULE_STATUS_UNKNOWN) . '">' .
						'<span class="name_count">' . html_print_image('images/module_unknown.png', true, false,false, false, false, true) . __('Unknown modules') . '</span>' .
						'<span class="number_count">' . $group['_monitors_unknown_'] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . sprintf($url_modules, $group['_id_'], AGENT_MODULE_STATUS_NOT_INIT) . '">' .
						'<span class="name_count">' . html_print_image('images/module_notinit.png', true, false,false, false, false, true) . __('Not init modules') . '</span>' .
						'<span class="number_count">' . $group['_monitors_not_init_'] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . sprintf($url_modules, $group['_id_'], AGENT_MODULE_STATUS_NORMAL) . '">' .
						'<span class="name_count">' . html_print_image('images/module_ok.png', true, false,false, false, false, true) . __('Normal modules') . '</span>' .
						'<span class="number_count">' . $group['_monitors_ok_'] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . sprintf($url_modules, $group['_id_'], AGENT_MODULE_STATUS_WARNING) . '">' .
						'<span class="name_count">' . html_print_image('images/module_warning.png', true, false,false, false, false, true) . __('Warning modules') . '</span>' .
						'<span class="number_count">' . $group['_monitors_warning_'] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="' . sprintf($url_modules, $group['_id_'], AGENT_MODULE_STATUS_CRITICAL_BAD) . '">' .
						'<span class="name_count">' . html_print_image('images/module_critical.png', true, false,false, false, false, true) . __('Critical modules') . '</span>' .
						'<span class="number_count">' . $group['_monitors_critical_'] . '</span>' .
						'</a></li>');
					$ui->contentAddHtml('<li data-icon="false"><a href="">' .
						'<span class="name_count">' . html_print_image('images/bell_error.png', true, false,false, false, false, true) . __('Alerts fired') . '</span>' .
						'<span class="number_count">' . $group['_monitors_alerts_fired_'] . '</span>' .
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
