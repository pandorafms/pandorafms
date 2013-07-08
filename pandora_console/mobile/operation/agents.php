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

class Agents {
	private $correct_acl = false;
	private $acl = "AR";
	
	private $default = true;
	private $default_filters = array();
	
	private $group = 0;
	private $status = -1;
	private $free_search = '';
	
	private $list_status = null;
	
	function __construct() {
		$system = System::getInstance();
		
		$this->list_status = array(
			-1 => __('All'),
			AGENT_MODULE_STATUS_CRITICAL_BAD => __('Critical'),
			AGENT_MODULE_STATUS_CRITICAL_ALERT => __('Alert'),
			AGENT_MODULE_STATUS_NORMAL => __('Normal'),
			AGENT_MODULE_STATUS_WARNING => __('Warning'),
			AGENT_MODULE_STATUS_UNKNOW => __('Unknow'));
		
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
				case 'get_agents':
					$this->getFilters();
					$page = $system->getRequest('page', 0);
					
					$agents = array();
					$end = 1;
					
					$listAgents = $this->getListAgents($page, true);
					
					if (!empty($listAgents['agents'])) {
						$end = 0;
						
						$agents = array();
						foreach ($listAgents['agents'] as $key => $agent) {
							$agent[0] = '<b class="ui-table-cell-label">' . 
								__('Agent') . '</b>' . $agent[0];
							//~ $agent[1] = '<b class="ui-table-cell-label">' . 
								//~ __('Description') . '</b>' . $agent[1];
							$agent[2] = '<b class="ui-table-cell-label">' . 
								__('OS') . '</b>' . $agent[2];
							$agent[3] = '<b class="ui-table-cell-label">' . 
								__('Group') . '</b>' . $agent[3];
							//~ $agent[4] = '<b class="ui-table-cell-label">' . 
								//~ __('Interval') . '</b>' . $agent[4];
							$agent[5] = '<b class="ui-table-cell-label">' . 
								__('Modules') . '</b>' . $agent[5];
							$agent[6] = '<b class="ui-table-cell-label">' . 
								__('Status') . '</b>' . $agent[6];
							$agent[7] = '<b class="ui-table-cell-label">' . 
								__('Alerts') . '</b>' . $agent[7];
							$agent[8] = '<b class="ui-table-cell-label">' . 
								__('Last contact') . '</b>' . $agent[8];
							
							$agents[$key] = $agent;
						}
					}
					
					echo json_encode(array('end' => $end, 'agents' => $agents));
					break;
			}
		}
	}
	
	private function getFilters() {
		$system = System::getInstance();
		$user = User::getInstance();
		
		$this->default_filters['group'] = true;
		$this->default_filters['status'] = true;
		$this->default_filters['free_search'] = true;
		
		$this->free_search = $system->getRequest('free_search', '');
		if ($this->free_search != '') {
			$this->default = false;
			$this->default_filters['free_search'] = false;
		}
		
		$this->status = $system->getRequest('status', __("Status"));
		if (($this->status === __("Status")) || ($this->status == -1)) {
			$this->status = -1;
		}
		else {
			$this->default = false;
			$this->default_filters['status'] = false;
		}
		
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
	}
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->getFilters();
			$this->show_agents();
		}
	}
	
	private function show_fail_acl() {
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');
		$home = new Home();
		$home->show($error);
	}
	
	private function show_agents() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		$ui->createDefaultHeader(__("PandoraFMS: Agents"),
			$ui->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Back'),
					'href' => 'index.php')));
		$ui->showFooter(false);
		$ui->beginContent();
			$filter_title = sprintf(__('Filter Agents by %s'),
				$this->filterEventsGetString());
			$ui->contentBeginCollapsible($filter_title);
				$ui->beginForm("index.php?page=agents");
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
					
					$options = array(
						'name' => 'status',
						'title' => __('Status'),
						'label' => __('Status'),
						'items' => $this->list_status,
						'selected' => $this->status
						);
					$ui->formAddSelectBox($options);
					
					$options = array(
						'name' => 'free_search',
						'value' => $this->free_search,
						'placeholder' => __('Free search')
						);
					$ui->formAddInputSearch($options);
					
					$options = array(
						'icon' => 'refresh',
						'icon_pos' => 'right',
						'text' => __('Apply Filter')
						);
					$ui->formAddSubmitButton($options);
				$html = $ui->getEndForm();
				$ui->contentCollapsibleAddItem($html);
			$ui->contentEndCollapsible();
			$this->listAgentsHtml();
		$ui->endContent();
		$ui->showPage();
	}
	
	private function getListAgents($page = 0, $ajax = false) {
		$system = System::getInstance();
		
		$total = 0;
		$agents = array();
		
		$search_sql = '';
		if (!empty($this->free_search)) {
			$search_sql = " AND (
				nombre COLLATE utf8_general_ci LIKE '%" . $this->free_search . "%'
				OR direccion LIKE '%" . $this->free_search . "%'
				OR comentarios LIKE '%" . $this->free_search . "%') ";
		}
		
		$total = agents_get_agents(array(
			'disabled' => 0,
			'id_grupo' => $this->group,
			'search' => $search_sql,
			'status' => $this->status),
			array ('COUNT(*) AS total'), 'AR', false);
		$total = isset($total[0]['total']) ? $total[0]['total'] : 0;
		
		$order = array('field' => 'nombre COLLATE utf8_general_ci',
			'field2' => 'nombre COLLATE utf8_general_ci', 'order' => 'ASC');
		$agents_db = agents_get_agents(array(
			'disabled' => 0,
			'id_grupo' => $this->group,
			'search' => $search_sql,
			'status' => $this->status,
			'offset' => (int) $page * $system->getPageSize(),
			'limit' => (int) $system->getPageSize()),
			array ('id_agente',
				'id_grupo',
				'id_os',
				'nombre',
				'ultimo_contacto',
				'intervalo',
				'comentarios description'),
			'AR', $order);
		
		if (empty($agents_db))
			$agents_db = array();
		
		foreach ($agents_db as $agent) {
			$row = array();
			
			$agent_info["monitor_alertsfired"] = agents_get_alerts_fired ($agent["id_agente"]);
			$agent_info["monitor_critical"] = agents_monitor_critical ($agent["id_agente"]);
			$agent_info["monitor_warning"] = agents_monitor_warning ($agent["id_agente"]);
			$agent_info["monitor_unknown"] = agents_monitor_unknown ($agent["id_agente"]);
			$agent_info["monitor_normal"] = agents_monitor_ok ($agent["id_agente"]);
			
			$img_status = agetns_tree_view_status_img ($agent_info["monitor_critical"],
				$agent_info["monitor_warning"], $agent_info["monitor_unknown"]);
			
			$img_alert = agents_tree_view_alert_img ($agent_info["monitor_alertsfired"]);
			
			
			$row[0] = $row[__('Agent')] =
				'<a class="ui-link" data-ajax="false" href="index.php?page=agent&id=' . $agent['id_agente'] . '">' . io_safe_output($agent['nombre']) . '</a>';
			//~ $row[1] = $row[__('Description')] = '<span class="small">' .
				//~ ui_print_truncate_text($agent["description"], 'description', false, true) .
				//~ '</span>';
			
			$row[2] = $row[__('OS')] = ui_print_os_icon ($agent["id_os"], false, true);
			$row[3] = $row[__('Group')] = ui_print_group_icon ($agent["id_grupo"], true);
			//~ $row[4] = $row[__('Interval')] = '<span class="show_collapside" style="vertical-align: 0%; display: none; font-weight: bolder;">&nbsp;&nbsp;' . __('I.') . ' </span>' .
				//~ '<span style="vertical-align: 0%;">' . human_time_description_raw($agent["intervalo"]) . '</span>';
			
			
			$row[5] = $row[__('Status')] = '<span class="show_collapside" style="vertical-align: 10%; display: none; font-weight: bolder;">' . __('S.') . ' </span>' .
				$img_status;
			$row[6] = $row[__('Alerts')] = '<span class="show_collapside" style="vertical-align: 10%; display: none; font-weight: bolder;">&nbsp;&nbsp;' . __('A.') . ' </span>' .
				$img_alert;
			
			$row[7] = $row[__('Modules')] =
				'<span class="show_collapside" style="display: none; vertical-align: top;">' .
					$img_status . '</span>' . '&nbsp;' .
					'<span class="show_collapside" style="display: none; vertical-align: middle;">' . $img_alert . '</span>' .
				'<span class="show_collapside" style="vertical-align: 0%; display: none; font-weight: bolder;">&nbsp;&nbsp;' . __('M.') . ' </span>' .
				reporting_tiny_stats($agent, true);
			
			$last_time = strtotime ($agent["ultimo_contacto"]);
			$now = time ();
			$diferencia = $now - $last_time;
			$time = ui_print_timestamp ($last_time, true, array('style' => 'font-size:6.5pt'));
			$style = '';
			if ($diferencia > ($agent["intervalo"] * 2))
				$row[8] = $row[__('Last contact')] = '<b><span style="color: #ff0000;">'.$time.'</span></b>';
			else
				$row[8] = $row[__('Last contact')] = $time;
			
			$row[8] = $row[__('Last contact')] = '<span class="show_collapside" style="vertical-align: 0%; display: none; font-weight: bolder;">&nbsp;&nbsp;' . __('L.') . ' </span>' .
				$row[__('Last contact')];
			
			if (!$ajax) {
				unset($row[0]);
				unset($row[1]);
				unset($row[2]);
				unset($row[3]);
				unset($row[4]);
				unset($row[5]);
				unset($row[6]);
				unset($row[7]);
				unset($row[8]);
			}
			
			$agents[$agent['id_agente']] = $row;
			
		}
		
		return array('agents' => $agents, 'total' => $total);
	}
	
	private function listAgentsHtml($page = 0) {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		$listAgents = $this->getListAgents($page);
		
		if ($listAgents['total'] == 0) {
			$ui->contentAddHtml('<p style="color: #ff0000;">' . __('No agents') . '</p>');
		}
		else {
			$table = new Table();
			$table->id = 'list_agents';
			$table->importFromHash($listAgents['agents']);
			$ui->contentAddHtml($table->getHTML());
			
			if ($system->getPageSize() < $listAgents['total']) {
				$ui->contentAddHtml('<div id="loading_rows">' .
						html_print_image('images/spinner.gif', true) .
						' ' . __('Loading...') .
					'</div>');
				
				$this->addJavascriptAddBottom();
			}
		}
	}
	
	private function addJavascriptAddBottom() {
		$ui = Ui::getInstance();
		
		$ui->contentAddHtml("<script type=\"text/javascript\">
				var load_more_rows = 1;
				var page = 1;
				$(document).ready(function() {
					$(window).bind(\"scroll\", function () {
						
						if (load_more_rows) {
							if ($(this).scrollTop() + $(this).height()
								>= ($(document).height() - 100)) {
								
								load_more_rows = 0;
								
								postvars = {};
								postvars[\"action\"] = \"ajax\";
								postvars[\"parameter1\"] = \"agents\";
								postvars[\"parameter2\"] = \"get_agents\";
								postvars[\"group\"] = $(\"select[name='group']\").val();
								postvars[\"status\"] = $(\"select[name='status']\").val();
								postvars[\"free_search\"] = $(\"input[name='free_search']\").val();
								postvars[\"page\"] = page;
								page++;
								
								$.post(\"index.php\",
									postvars,
									function (data) {
										if (data.end) {
											$(\"#loading_rows\").hide();
										}
										else {
											$.each(data.agents, function(key, agent) {
												$(\"table#list_agents tbody\")
													.append(\"<tr class=''>\" +
														\"<th class='head_vertical'></th>\" +
														\"<td class='cell_0'>\" + agent[0] + \"</td>\" +
														// \"<td class='cell_1'>\" + agent[1] + \"</td>\" +
														\"<td class='cell_1'>\" + agent[2] + \"</td>\" +
														\"<td class='cell_2'>\" + agent[3] + \"</td>\" +
														// \"<td class='cell_4'>\" + agent[4] + \"</td>\" +
														\"<td class='cell_3'>\" + agent[5] + \"</td>\" +
														\"<td class='cell_4'>\" + agent[6] + \"</td>\" +
														\"<td class='cell_5'>\" + agent[7] + \"</td>\" +
														\"<td class='cell_6'>\" + agent[8] + \"</td>\" +
													\"</tr>\");
												});
											
											load_more_rows = 1;
										}
										
										
									},
									\"json\");
							}
						}
					});
				});
			</script>");
	}
	
	private function filterEventsGetString() {
		if ($this->default) {
			return __("(Default)");
		}
		else {
			$filters_to_serialize = array();
			
			if (!$this->default_filters['group']) {
				$filters_to_serialize[] = sprintf(__("Group: %s"),
					groups_get_name($this->group, true));
			}
			if (!$this->default_filters['status']) {
				$filters_to_serialize[] = sprintf(__("Status: %s"),
					$this->list_status[$this->status]);
			}
			if (!$this->default_filters['free_search']) {
				$filters_to_serialize[] = sprintf(__("Free Search: %s"),
					$this->free_search);
			}
			
			$string = '(' . implode(' - ', $filters_to_serialize) . ')';
			
			//~ $status = $this->list_status[$this->status];
			//~ $group = groups_get_name($this->group, true);
			//~ 
			//~ 
			//~ $string = sprintf(
				//~ __("(Status: %s - Group: %s - Free Search: %s)"),
				//~ $status, $group, $this->free_search);
			
			return $string;
		}
	}
}

?>