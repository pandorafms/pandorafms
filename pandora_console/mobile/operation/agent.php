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
		$error['type'] = 'onStart';
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');
		if (class_exists("HomeEnterprise"))
			$home = new HomeEnterprise();
		else
			$home = new Home();
		$home->show($error);
	}
	
	
	public function ajax($parameter2 = false) {
		$system = System::getInstance();

		if (!$this->correct_acl) {
			return;
		}
		else {
			switch ($parameter2) {
				case 'render_events_bar':
					$agent_id = $system->getRequest('agent_id', '0');
					$width = $system->getRequest('width', '400');
					graph_graphic_agentevents(
						$this->id, $width, 30, SECONDS_1DAY, ui_get_full_url(false));
					exit;
			}
		}
	 }
	
	private function show_agent() {
		$ui = Ui::getInstance();
		$system = System::getInstance();
		
		$ui->createPage();
		
		if ($this->id != 0) {
			$agent_name = (string) agents_get_name ($this->id);
			
			$ui->createDefaultHeader(
				sprintf('%s', $agent_name),
				$ui->createHeaderButton(
					array('icon' => 'back',
						'pos' => 'left',
						'text' => __('Back'),
						'href' => 'index.php?page=agents')));
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
					else if ($this->agent['quiet']) {
						$agent_name = "<em>" . $agent_name . "&nbsp;" . html_print_image("images/dot_green.disabled.png", true, array("border" => '0', "title" => __('Quiet'), "alt" => "")) . "</em>";
					}
					else {
						$agent_name = $agent_name;
					}
					
					
					$addresses = agents_get_addresses($this->id);
					$address = agents_get_address($this->id);
					foreach ($addresses as $k => $add) {
						if ($add == $address) {
							unset($addresses[$k]);
						}
					}
					$ip = html_print_image('images/world.png', true, array('title' => __('IP address'))) . '&nbsp;&nbsp;';
					$ip .= empty($address) ? '<em>' . __('N/A') . '</em>' : $address;
					if (!empty($addresses)) {
						$ip .= ui_print_help_tip(__('Other IP addresses').': <br>'.implode('<br>',$addresses), true);
					}
					$ip .= '<br />';
					
					$last_contact = '<b>' . __('Last contact') . '</b>:&nbsp;'
						.ui_print_timestamp ($this->agent["ultimo_contacto"], true) . '<br />';
					
					$description = '<b>' . __('Description') . ':</b><br>';
					if (empty($agent["comentarios"])) {
						$description .= '<i>' . __('N/A') . '</i>';
					}
					else {
						$description .= $this->agent["comentarios"];
					}
					
					
					$html = '<div class="agent_details">';
					$html .= ui_print_group_icon ($this->agent["id_grupo"], true, "groups_small", "", false) . '&nbsp;&nbsp;';
					$html .= '<span class="agent_name">' . $agent_name . '</span><br />';
					$html .= $ip;
					$html .= $last_contact;
					$html .= $description;
					$html .= '</div>';
					
				$ui->contentGridAddCell($html, 'agent_details');
					ob_start();
					$html = '<div class="agent_graphs">';
					$html .= "<b>" . __('Modules by status') . "</b><br />";
					$html .= graph_agent_status ($this->id, 160, 160, true);
					$graph_js = ob_get_clean();
					$html = $graph_js . $html;
					unset($this->agent['fired_count']);
					if ($this->agent['total_count'] > 0) {
						$html .= '<span class="agents_tiny_stats agents_tiny_stats_tactical">' . reporting_tiny_stats($this->agent, true) . ' </span><br>';
					}
					$html .= "<b>" . __('Events (24h)') . "</b><br /><br />";
					$html .= '<div id="events_bar"></div>';
					$html .= '<br>';
					$html .= '</div>';
				$ui->contentGridAddCell($html, 'agent_graphs');
				$ui->contentEndGrid();
				
				
				$modules = new Modules();
				$filters = array('id_agent' => $this->id, 'all_modules' => true, 'status' => -1);
				$modules->setFilters($filters);
				$modules->disabledColumns(array('agent'));
				$ui->contentBeginCollapsible(__('Modules'));
				$ui->contentCollapsibleAddItem($modules->listModulesHtml(0, true));
				$ui->contentEndCollapsible();
				
				$alerts = new Alerts();
				$filters = array('id_agent' => $this->id, 'all_alerts' => true);
				$alerts->setFilters($filters);
				$alerts->disabledColumns(array('agent'));
				$ui->contentBeginCollapsible(__('Alerts'));
				$ui->contentCollapsibleAddItem($alerts->listAlertsHtml(true));
				$ui->contentEndCollapsible();
				
				$events = new Events();
				$events->addJavascriptDialog();
				
				$options = $events->get_event_dialog_options();
				$ui->addDialog($options);
				
				$options = $events->get_event_dialog_error_options($options);
				$ui->addDialog($options);
				
				$ui->contentAddHtml("<a id='detail_event_dialog_hook' href='#detail_event_dialog' style='display:none;'>detail_event_hook</a>");
				$ui->contentAddHtml("<a id='detail_event_dialog_error_hook' href='#detail_event_dialog_error' style='display:none;'>detail_event_dialog_error_hook</a>");
			
				$ui->contentBeginCollapsible(sprintf(__('Last %s Events'), $system->getPageSize()));
				$tabledata = $events->listEventsHtml(0, true, 'last_agent_events');
				$ui->contentCollapsibleAddItem($tabledata['table']);
				$ui->contentCollapsibleAddItem($events->putEventsTableJS($this->id));
				$ui->contentEndCollapsible();
			}
					
		$ui->contentAddLinkListener('last_agent_events');
		$ui->contentAddLinkListener('list_events');
		$ui->contentAddLinkListener('list_agent_Modules');

		$ui->contentAddHtml("<script type=\"text/javascript\">
			$(document).ready(function() {
				function set_same_heigth() {
					//Set same height to boxes
					var max_height = 0;
					if ($('.agent_details').height() > $('.agent_graphs').height()) {
						max_height = $('.agent_details').height();
						$('.agent_graphs').height(max_height);
					}
					else {
						max_height = $('.agent_graphs').height();
						$('.agent_details').height(max_height);
					}
				}
									
				if ($('.ui-block-a').css('float') != 'none') {
					set_same_heigth();
				}
				
				$('.ui-collapsible').bind('expand', function () {
					refresh_link_listener_last_agent_events();
					refresh_link_listener_list_agent_Modules();
				});
				
				function ajax_load_events_bar() {
					$('#events_bar').html('<div style=\"text-align: center\"> " . __('Loading...') . "<br /><img src=\"images/ajax-loader.gif\" /></div>');
					
					var bar_width = $('.agent_graphs').width() * 0.9;

					postvars = {};
					postvars[\"action\"] = \"ajax\";
					postvars[\"parameter1\"] = \"agent\";
					postvars[\"parameter2\"] = \"render_events_bar\";
					postvars[\"agent_id\"] = \"" . $this->id . "\";
					postvars[\"width\"] = bar_width;
					$.post(\"index.php\",
						postvars,
						function (data) {
							$('#events_bar').html(data);
							if ($('.ui-block-a').css('float') != 'none') {
								set_same_heigth();
							}
						},
						\"html\");
				}
				
				ajax_load_events_bar();
				
				// Detect orientation change to refresh dinamic content
				$(window).on({
					orientationchange: function(e) {
						// Refresh events bar
						ajax_load_events_bar();
						
						// Keep same height on boxes
						if ($('.ui-block-a').css('float') == 'none') {
							$('.agent_graphs').height('auto');
							$('.agent_details').height('auto');
						}
						else {
							set_same_heigth();
						}
					  
					}
				});
										
				if ($('.ui-block-a').css('float') != 'none') {
					set_same_heigth();
				}
			});			
			</script>");
			
		$ui->endContent();
		$ui->showPage();
	}
}
