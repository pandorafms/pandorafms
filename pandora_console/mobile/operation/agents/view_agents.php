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

require_once("../include/functions_agents.php");
require_once("../include/functions_reporting.php");
require_once("../include/functions_alerts.php");

class ViewAgents {
	private $user;
	private $system;
	private $filter;
	private $filterGroup;
	
	public function __construct() {
		global $system;
		global $user;
		
		$this->user = $user;
		$this->system = $system;
		$this->filterText = $this->system->getRequest('filter_text', '');
		$this->filterGroup = $this->system->getRequest('filter_group', 0);;
	}
	
	private function showForm() {
		echo "<form>";
		print_input_hidden('page', 'agents');
		print_select_groups($this->user->getIdUser(), "AR", true, 'filter_group', $this->filterGroup);
		print_input_text('filter_text', $this->filterText, __('Free text search'), 10, 20);
		echo "<input type='submit' class='button_filter' name='submit_button' value='' alt='" . __('Filter') . "' title='" . __('Filter') . "' />";
		echo "<form>";
	}
	
	public function show() {
		$this->showForm();
		
		// Show only selected groups	
		if ($this->filterGroup > 0) {
			$groups = $this->filterGroup;
			$agent_names = get_group_agents ($this->filterGroup, array('string' => $this->filterText), "upper");
		// Not selected any specific group
		}
		else {
			$user_group = get_user_groups ($this->user->getIdUser(), "AR");
			$groups = array_keys ($user_group);
			$agent_names = get_group_agents (array_keys ($user_group), array('string' => $this->filterText), "upper");
		}
		
		$total_agents = get_agents (array('id_agente' => array_keys ($agent_names),
			'order' => 'nombre ASC',
			'disabled' => 0,
			'id_grupo' => $groups),
			array ('COUNT(*) as total'));
		$total_agents = isset ($total_agents[0]['total']) ? $total_agents[0]['total'] : 0;
		
		$agents = get_agents(array('id_agente' => array_keys ($agent_names),
			'order' => 'nombre ASC',
			'id_grupo' => $groups,
			'offset' => (int) get_parameter ('offset'),
			'limit' => (int) $this->system->getPageSize()), array('id_agente', 'nombre', 'id_grupo'));
		
		$table = null;
		
		$table->width = '100%';
		
		$table->align = array();
		$table->align[0] = 'center';
		$table->align[2] = 'center';
		$table->align[3] = 'center';
		$table->align[4] = 'center';
		
		$table->head = array();
		$table->head[0] = '<span title="' . __('Group') . '" alt="' . __('Group') . '">' . __('G') . '</span>';
		$table->head[1] = __('Name');
		$table->head[2] = '<span title="' . __('Modules') . '" alt="' . __('Modules') . '">' . __('M') . '</span>';
		$table->head[3] = '<span title="' . __('Status') . '" alt="' . __('Status') . '">' . __('S') . '</span>';
		$table->head[4] = '<span title="' . __('Alert') . '" alt="' . __('Alert') . '">' . __('A') . '</span>';
		
		$table->data = array();
		
		if ($agents === false) $agents = array();
		
		$iterator = 0;
		$rowPair = false;
		foreach ($agents as $agent) {
			if ($rowPair)
				$table->rowclass[$iterator] = 'rowPair';
			else
				$table->rowclass[$iterator] = 'rowOdd';
			$rowPair = !$rowPair;
			
			$agent_info = get_agent_module_info ($agent["id_agente"]); //$this->system->debug($agent_info);
			
			$data = array();
			
			$truncName = printTruncateText($agent['nombre'], 10, true, true);
			
			$data[] = print_group_icon2($agent["id_grupo"], true, "../images/groups_small", '', false);
			$data[] = '<a href="index.php?page=agent&id=' . $agent['id_agente'] . '">' . $truncName . '</a>';
			
			$moduleInfo = '<b>';
			$moduleInfo .= $agent_info["modules"];
			if ($agent_info["monitor_alertsfired"] > 0)
				$moduleInfo .= ' : <span class="orange">'.$agent_info["monitor_alertsfired"].'</span>';
			if ($agent_info["monitor_critical"] > 0)
				$moduleInfo .= ' : <span class="red">'.$agent_info["monitor_critical"].'</span>';
			if ($agent_info["monitor_warning"] > 0)
				$moduleInfo .= ' : <span class="yellow">'.$agent_info["monitor_warning"].'</span>';
			if ($agent_info["monitor_unknown"] > 0)
				$moduleInfo .= ' : <span class="grey">'.$agent_info["monitor_unknown"].'</span>';
			if ($agent_info["monitor_normal"] > 0)
				$moduleInfo .= ' : <span class="green">'.$agent_info["monitor_normal"].'</span>';
			$moduleInfo .= '</b>';
			
			$data[] = $moduleInfo;
			$data[] = '<img src="../images/status_sets/default/' . str_replace('.png', '_ball.png', $agent_info['status']) . '" />';
			$data[] = '<img src="../images/status_sets/default/' . str_replace('.png', '_ball.png', $agent_info['alert_value']) . '" />';
			
			
			$table->data[] = $data;
		}
		
		print_table($table);
		
		$pagination = pagination ($total_agents,
			get_url_refresh (array ('filter_group' => $this->filterGroup, 'filter_group' => $this->filterGroup)),
			0, 0, true);
			
		$pagination = str_replace('images/go_first.png', '../images/go_first.png', $pagination);
		$pagination = str_replace('images/go_previous.png', '../images/go_previous.png', $pagination);
		$pagination = str_replace('images/go_next.png', '../images/go_next.png', $pagination);
		$pagination = str_replace('images/go_last.png', '../images/go_last.png', $pagination);
			
		echo $pagination;
	}
}

class ViewAgent {
	private $idAgent;
	private $sytem;
	private $agent;
	
	private $name;
	private $os;
	private $ips;
	private $modules;
	
	public function __construct() {
		global $system;
		
		$this->system = $system;
		
		$this->idAgent = $this->system->getRequest('id', 0);
		$this->agent = get_db_row_filter('tagente', array('id_agente' => $this->idAgent));
			
		$this->ips = get_agent_addresses($this->idAgent);
	}
	
	public function show() {
		$table = null;
		
		$table->width = '100%';
		
		$table->style[0] = 'font-weight: bolder;';
		
		$table->data[0][0] = __('Name:');
		$table->data[0][1] = $this->agent['nombre'];
		$table->data[1][0] = __('IP:');
		$table->data[1][1] = implode(',', $this->ips);
		$table->data[2][0] = __('OS:');
		$table->data[2][1] = str_replace('images/os_icons/', '../images/os_icons/', print_os_icon($this->agent['id_os']));
		$table->data[3][0] = __('Last contact');
		$table->data[3][1] = $this->agent['ultimo_contacto'];
		
		print_table($table);
		
		$sql = sprintf ("
			SELECT *
			FROM tagente_estado, tagente_modulo
				LEFT JOIN tmodule_group
				ON tmodule_group.id_mg = tagente_modulo.id_module_group
			WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND tagente_modulo.id_agente = %d 
				AND tagente_modulo.disabled = 0
				AND tagente_modulo.delete_pending = 0
				AND tagente_estado.utimestamp != 0 
			ORDER BY tagente_modulo.id_module_group , tagente_modulo.nombre ASC
			", $this->idAgent);
		
		$modules = get_db_all_rows_sql ($sql);
		if (empty ($modules)) {
			$modules = array ();
		}
		
		echo "<h3 class='title_h3'>" . __('Modules') . "</h3>";
		
		$table = null;
		$table->width = '100%';
		$table->head = array();
		$table->head[0] = __('Module');
		$table->head[1] = '<span title="' . __('Status') . '" alt="' . __('Status') . '">' . __('S') . '</span>';
		$table->head[2] = __('Data');
		$table->head[3] = '<span title="' . __('Last contact') . '" alt="' . __('Last contact') . '">' . __('L') . '</span>';
		
		$table->data = array();
		
		$iterator = 0;
		$rowPair = false;
		foreach ($modules as $module) {
			if ($rowPair)
				$table->rowclass[$iterator] = 'rowPair';
			else
				$table->rowclass[$iterator] = 'rowOdd';
			$rowPair = !$rowPair;
			
			$data = array();
			
			$data[] = printTruncateText($module["nombre"], 10, true, true);
			$status = STATUS_MODULE_WARNING;
			$title = "";
		
			if ($module["estado"] == 1) {
				$status = STATUS_MODULE_CRITICAL;
				$title = __('CRITICAL');
			} elseif ($module["estado"] == 2) {
				$status = STATUS_MODULE_WARNING;
				$title = __('WARNING');
			} elseif ($module["estado"] == 0) {
				$status = STATUS_MODULE_OK;
				$title = __('NORMAL');
			} elseif ($module["estado"] == 3) {
				$last_status =  get_agentmodule_last_status($module['id_agente_modulo']);
				switch($last_status) {
					case 0:
						$status = STATUS_MODULE_OK;
						$title = __('UNKNOWN')." - ".__('Last status')." ".__('NORMAL');
						break;
					case 1:
						$status = STATUS_MODULE_CRITICAL;
						$title = __('UNKNOWN')." - ".__('Last status')." ".__('CRITICAL');
						break;
					case 2:
						$status = STATUS_MODULE_WARNING;
						$title = __('UNKNOWN')." - ".__('Last status')." ".__('WARNING');
						break;
				}
			}
			
			if (is_numeric($module["datos"])) {
				$title .= ": " . format_for_graph($module["datos"]);
			}
			else {
				$title .= ": " . substr(safe_output($module["datos"]),0,42);
			}
		
			$data[] = str_replace('.png', '_ball.png', str_replace('images/status_sets', 
				'../images/status_sets', print_status_image($status, $title, true)));
			
			if ($module["id_tipo_modulo"] == 24) { // log4x
				switch($module["datos"]) {
				case 10: $salida = "TRACE"; $style="font-weight:bold; color:darkgreen;"; break;
				case 20: $salida = "DEBUG"; $style="font-weight:bold; color:darkgreen;"; break;
				case 30: $salida = "INFO";  $style="font-weight:bold; color:darkgreen;"; break;
				case 40: $salida = "WARN";  $style="font-weight:bold; color:darkorange;"; break;
				case 50: $salida = "ERROR"; $style="font-weight:bold; color:red;"; break;
				case 60: $salida = "FATAL"; $style="font-weight:bold; color:red;"; break;
				}
				$salida = "<span style='$style'>$salida</span>";
			} else {
				if (is_numeric($module["datos"])){
					$salida = format_numeric($module["datos"]);
				} else {
					$salida = "<span title='".$module['datos']."' style='white-space: nowrap;'>".substr(safe_output($module["datos"]),0,12)."</span>";
				}
			}
			$data[] = $salida;
			if ($module['estado'] == 3) {
				$lastTime = '<span class="redb">';
			} else {
				$lastTime = '<span>';
			}
			$lastTime .= print_timestamp ($module["utimestamp"], true, array('units' => 'tiny'));
			$lastTime .= '</span>';
			$data[] = $lastTime;
			
			
			$table->data[] = $data;
		}
		
		print_table($table);
		
		$table->head = array();
		$table->head[0] = __('Module');
		$table->head[1] = __('Template');
//		$table->head[2] = __('Action');
		$table->head[2] = '<span title="' . __('Last fired') . '" alt="' . __('Last fired') . '">' . __('Last') . '</span>';
		$table->head[3] = '<span title="' . __('Status') . '" alt="' . __('Status') . '">' . __('S') . '</span>';
		
		
		$table->align = array();
		$table->align[3] = 'right';
		$table->align[2] = 'center';
		$table->data = array();
		$table->rowclass = array();
		
		echo "<h3 class='title_h3'>" . __('Alerts') . "</h3>";
		$alertsSimple = get_agent_alerts_simple (array($this->idAgent));
		foreach ($alertsSimple as $alert) {
			if ($rowPair)
				$table->rowclass[$iterator] = 'rowPair';
			else
				$table->rowclass[$iterator] = 'rowOdd';
			$rowPair = !$rowPair;
			
			$data = array();
			
			$data[] = printTruncateText(get_agentmodule_name($alert["id_agent_module"]), 10, true, true);
			
			$template = safe_output(get_alert_template ($alert['id_alert_template']));
			$data[] = printTruncateText(safe_output($template['name']), 10, true, true);
			
//			$actions = get_alert_agent_module_actions ($alert['id'], false, false);
//			if (!empty($actions)){
//				$actionText = '<ul class="action_list">';
//				foreach ($actions as $action) {
//					$actionText .= '<li><div><span class="action_name">' . $action['name'];
//					if ($action["fires_min"] != $action["fires_max"]){
//						$actionText .=  " (".$action["fires_min"] . " / ". $action["fires_max"] . ")";
//					}
//					$actionText .= '</li></span><br></div>';
//				}
//				$actionText .= '</div></ul>';
//			}
//			else {
//				if ($actionDefault != "")
//				$actionText = get_db_sql ("SELECT name FROM talert_actions WHERE id = $actionDefault"). " <i>(".__("Default") . ")</i>";
//			}
//		
//			$data[] = $actionText;
			$data[] = print_timestamp ($alert["last_fired"], true, array('units' => 'tiny'));
			
			$status = STATUS_ALERT_NOT_FIRED;
			$title = "";
			
			if ($alert["times_fired"] > 0) {
				$status = STATUS_ALERT_FIRED;
				$title = __('Alert fired').' '.$alert["times_fired"].' '.__('times');
			} elseif ($alert["disabled"] > 0) {
				$status = STATUS_ALERT_DISABLED;
				$title = __('Alert disabled');
			} else {
				$status = STATUS_ALERT_NOT_FIRED;
				$title = __('Alert not fired');
			}
			
			$data[] = str_replace('.png', '_ball.png', str_replace('images/status_sets', 
				'../images/status_sets', print_status_image($status, $title, true)));
			
			$table->data[] = $data;
		}
		print_table($table);
		
//		echo "<h3 class='title_h3'>" . __('Alerts compound') . "</h3>";
//		
//		$alertsCombined = get_agent_alerts_compound(array($this->idAgent));
//		
//		$table->data = array();
//		foreach ($alertsCombined as $alert) {
//			$data = array();
//			
//			$table->data[] = $data;
//		}
	}
}
?>