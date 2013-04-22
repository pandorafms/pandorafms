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

class Agent {
	private $correct_acl = false;
	private $id = 0;
	private $agent = null;
	
	function __construct() {
		$system = System::getInstance();
		
		$this->id = $system->getRequest('id', 0);
		$this->agent = agents_get_agents(array(
			'disabled' => 0,
			'id_agente' => $this->id), array('*'));
		
		if (!empty($this->agent)) {
			$this->agent = $this->agent[0];
			
		
			if ($system->checkACL('AR', $this->agent['id_grupo'])) {
				$this->correct_acl = true;
			}
			else {
				$this->correct_acl = false;
			}
		}
		else {
			$this->agent = null;
			$this->correct_acl = true;
		}
	}
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->show_agent();
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
	
	private function show_agent() {
		$ui = Ui::getInstance();
		$system = System::getInstance();
		
		$ui->createPage();
		if ($this->id != 0) {
			$agent_name = (string) agents_get_name ($this->id);
			
			$ui->createDefaultHeader(sprintf(__("PandoraFMS: %s"), $agent_name));
		}
		else {
			$ui->createDefaultHeader(__("PandoraFMS: Agents"));
		}
		$ui->showFooter(false);
		$ui->beginContent();
			if (empty($this->agent)) {
				$ui->contentAddHtml('<span style="color: red;">' . __('No agent found') . '</span>');
			}
			else {
				$ui->contentBeginGrid();
					if ($this->agent['disabled']) {
						$agent_name = "<em>" . $agent_name . "</em>" . ui_print_help_tip(__('Disabled'), true);
					}
					else {
						$agent_name = $agent_name;
					}
					
					
					$addresses = agents_get_addresses($this->id);
					$address = agents_get_address($this->id);
					foreach ($addresses as $k => $add) {
						if($add == $address) {
							unset($addresses[$k]);
						}
					}
					$ip = html_print_image('images/world.png', true, array('title' => __('IP address'))) . '&nbsp;&nbsp;';
					$ip .= empty($address) ? '<em>' . __('N/A') . '</em>' : $address;
					if (!empty($addresses)) {
						$ip .= ui_print_help_tip(__('Other IP addresses').': <br>'.implode('<br>',$addresses), true);
					}
					
					$last_contant = '<b>' . __('Last contact') . ' / ' . __('Remote') . '</b>&nbsp;'
						.ui_print_timestamp ($this->agent["ultimo_contacto"], true);
					
					$description =
						empty($agent["comentarios"]) ? '<em>' . __('N/A') . '</em>' : $this->agent["comentarios"];
					
					
					
					$html = ui_print_group_icon ($this->agent["id_grupo"], true) . '&nbsp;&nbsp;';
					$html .= $agent_name . '<br />';
					$html .= $ip . '<br />';
					$html .= $last_contant . '<br />';
					$html .= $description . '<br />';
					
				$ui->contentGridAddCell($html);
					ob_start();
					$html = graph_agent_status ($this->id, 160, 160, true);
					$graph_js = ob_get_clean();
					$html = $graph_js . $html;
					$html .= "<b>" . __('Events (24h)') . "</b><br />";
					$html .= graph_graphic_agentevents(
						$this->id, 250, 15, 86400, ui_get_full_url(false), true);
				$ui->contentGridAddCell($html);
				$ui->contentEndGrid();
				
				
				$modules = new Modules();
				$filters = array('id_agent' => $this->id, 'all_modules' => true, 'status' => -1);
				$modules->setFilters($filters);
				$modules->disabledColumns(array('agent'));
				$ui->contentBeginCollapsible(__('Modules'));
				html_debug_print(888, true);
				html_debug_print($modules, true);
				$ui->contentCollapsibleAddItem($modules->listModulesHtml(0, true));
				html_debug_print(999, true);
				$ui->contentEndCollapsible();
				
				$alerts = new Alerts();
				$filters = array('id_agent' => $this->id, 'all_alerts' => true);
				$alerts->setFilters($filters);
				$alerts->disabledColumns(array('agent'));
				$ui->contentBeginCollapsible(__('Alerts'));
				$ui->contentCollapsibleAddItem($alerts->listAlertsHtml(true));
				$ui->contentEndCollapsible();
				
				$events = new Events();
				$filters = array('id_agent' => $this->id);
				$events->setFilters($filters);
				$events->disabledColumns(array('agent'));
				$events->setReadOnly();
				$ui->contentBeginCollapsible(sprintf(__('Last %s Events'), $system->getPageSize()));
				$ui->contentCollapsibleAddItem($events->listEventsHtml(0, true));
				$ui->contentEndCollapsible();
			}
		$ui->endContent();
		$ui->showPage();
	}
}