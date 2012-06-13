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
require_once("../include/functions_modules.php");
require_once('../include/functions_users.php');

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
		html_print_input_hidden('page', 'agents');
		global $config;
		$config['text_char_long'] = 12;
		html_print_select_groups($this->user->getIdUser(), "AR", true, 'filter_group', $this->filterGroup);
		html_print_input_text('filter_text', $this->filterText, __('Free text search'), 5, 20);
		echo "<input type='submit' class='button_filter' name='submit_button' value='' alt='" . __('Filter') . "' title='" . __('Filter') . "' />";
		echo "<form>";
	}
	
	public function show() {
		$this->showForm();
		
		// Show only selected groups	
		if ($this->filterGroup > 0) {
			$groups = $this->filterGroup;
			$agent_names = agents_get_group_agents ($this->filterGroup, array('string' => $this->filterText), "upper");
		// Not selected any specific group
		}
		else {
			$user_group = users_get_groups ($this->user->getIdUser(), "AR");
			$groups = array_keys ($user_group);
			$agent_names = agents_get_group_agents (array_keys ($user_group), array('string' => $this->filterText), "upper");
		}
		
		$total_agents = agents_get_agents (array('id_agente' => array_keys ($agent_names),
			'order' => 'nombre ASC',
			'disabled' => 0,
			'id_grupo' => $groups),
			array ('COUNT(*) as total'));
		$total_agents = isset ($total_agents[0]['total']) ? $total_agents[0]['total'] : 0;
		
		$agents = agents_get_agents(array('id_agente' => array_keys ($agent_names),
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
			$iterator++;
			
			$agent_info = reporting_get_agent_module_info ($agent["id_agente"]); //$this->system->debug($agent_info);
			
			$data = array();
			
			$truncName = ui_print_truncate_text($agent['nombre'], 25, true, true);
			
			$data[] = ui_print_group_icon_path($agent["id_grupo"], true, "../images/groups_small", '', false);
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
			$data[] = '<img width="12" height="12" src="../images/status_sets/default/' . $agent_info['status'] . '" />';
			$data[] = '<img width="12" height="12" src="../images/status_sets/default/' . $agent_info['alert_value'] . '" />';
			
			
			$table->data[] = $data;
		}
		
		html_print_table($table);
		
		$pagination = ui_pagination ($total_agents,
			ui_get_url_refresh (array ('filter_group' => $this->filterGroup, 'filter_group' => $this->filterGroup)),
			0, 0, true);

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
		$this->agent = db_get_row_filter('tagente', array('id_agente' => $this->idAgent));
			
		$this->ips = agents_get_addresses($this->idAgent);
	}
	
	public function show() {
		$idGroup = $this->agent['id_grupo'];
		if (! check_acl ($this->system->getConfig('id_user'), $idGroup, "AR")) {
			db_pandora_audit("ACL Violation",
				"Trying to access (read) to agent ".agents_get_name($this->idAgent));
			include ("../general/noaccess.php");
			return;
		}
		
		$table = null;
		
		$table->width = '100%';
		
		$table->style[0] = 'font-weight: bolder;';
		
		$table->data[0][0] = __('Name:');
		$table->data[0][1] = $this->agent['nombre'];
		$table->data[1][0] = __('IP:');
		$table->data[1][1] = implode(',', $this->ips);
		$table->data[2][0] = __('OS:');
		$table->data[2][1] = str_replace('images/os_icons/', '../images/os_icons/', ui_print_os_icon($this->agent['id_os'], true, true));
		$table->data[3][0] = __('Last contact');
		$table->data[3][1] = $this->agent['ultimo_contacto'];
		
		html_print_table($table);
		
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
		
		$modules = db_get_all_rows_sql ($sql);
		if (empty ($modules)) {
			$modules = array ();
		}
		
		echo "<h3 class='title_h3'>" . __('Modules') . "</h3>";
		
		$table = null;
		//$table->width = '100%';
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
			$iterator++;
			
			$data = array();
			
			$data[] = '<a href="index.php?page=agent&action=view_module_graph&id=' . $module['id_agente_modulo'] . '">' . 
				ui_print_truncate_text($module["nombre"], 20, true, true) . '</a>';
			$status = STATUS_MODULE_WARNING;
			$title = "";
		
			if ($module["estado"] == 1) {
				$status = STATUS_MODULE_CRITICAL;
				$title = __('CRITICAL');
			}
			elseif ($module["estado"] == 2) {
				$status = STATUS_MODULE_WARNING;
				$title = __('WARNING');
			}
			elseif ($module["estado"] == 0) {
				$status = STATUS_MODULE_OK;
				$title = __('NORMAL');
			}
			elseif ($module["estado"] == 3) {
				$last_status =  modules_get_agentmodule_last_status($module['id_agente_modulo']);
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
				$title .= ": " . substr(io_safe_output($module["datos"]),0,42);
			}
		
			$data[] = str_replace(array('images/status_sets', '<img'), 
				array('/images/status_sets', '<img height="15" width="15"') , ui_print_status_image($status, $title, true));
			
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
			}
			else {
				if (is_numeric($module["datos"])){
					$salida = format_numeric($module["datos"]);
				}
				else {
					$salida = "<span title='".$module['datos']."' style='white-space: nowrap;'>".substr(io_safe_output($module["datos"]),0,12)."</span>";
				}
			}
			$data[] = $salida;
			if ($module['estado'] == 3) {
				$lastTime = '<span class="redb">';
			}
			else {
				$lastTime = '<span>';
			}
			$lastTime .= ui_print_timestamp ($module["utimestamp"], true, array('units' => 'tiny'));
			$lastTime .= '</span>';
			$data[] = $lastTime;
			
			
			$table->data[] = $data;
		}
		
		html_print_table($table);
		
		$table->head = array();
		$table->head[0] = __('Module');
		$table->head[1] = __('Template');
		$table->head[2] = '<span title="' . __('Last fired') . '" alt="' . __('Last fired') . '">' . __('Last') . '</span>';
		$table->head[3] = '<span title="' . __('Status') . '" alt="' . __('Status') . '">' . __('S') . '</span>';
		
		
		$table->align = array();
		$table->align[3] = 'right';
		$table->align[2] = 'center';
		$table->data = array();
		$table->rowclass = array();
		
		echo "<h3 class='title_h3'>" . __('Alerts') . "</h3>";
		$alertsSimple = agents_get_alerts_simple (array($this->idAgent));
		$rowPair = false;
		$iterator = 0;
		foreach ($alertsSimple as $alert) {
			if ($rowPair)
				$table->rowclass[$iterator] = 'rowPair';
			else
				$table->rowclass[$iterator] = 'rowOdd';
			$rowPair = !$rowPair;
			$iterator++;
			
			$data = array();
			
			$data[] = ui_print_truncate_text(modules_get_agentmodule_name($alert["id_agent_module"]), 20, true, true);
			
			$template = io_safe_output(alerts_get_alert_template ($alert['id_alert_template']));
			$data[] = ui_print_truncate_text(io_safe_output($template['name']), 20, true, true);
			
			$data[] = ui_print_timestamp ($alert["last_fired"], true, array('units' => 'tiny'));
			
			$status = STATUS_ALERT_NOT_FIRED;
			$title = "";
			
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
			
			$data[] = str_replace(array('images/status_sets', '<img'), 
				array('/images/status_sets', '<img width="15" height="15"'), ui_print_status_image($status, $title, true));
			
			$table->data[] = $data;
		}
		html_print_table($table);
	}
}

class viewGraph {
	private $system;
	private $idAgentModule;
	
	function __construct($idAgentModule = 0) {
		global $system;
		
		$this->system = $system;
		$this->idAgentModule = $idAgentModule;
		$this->agentModule = db_get_row_filter('tagente_modulo', array('id_agente_modulo' => $this->idAgentModule));
		
		$this->period = $this->system->getRequest('period', 86400);
		$this->offset = $this->system->getRequest("offset", 0);
		
		$this->agent = db_get_row_filter('tagente', array('id_agente' => $this->agentModule['id_agente']));
	}
	
	function show() {
		$idGroup = $this->agent['id_grupo'];
		if (! check_acl ($this->system->getConfig('id_user'), $idGroup, "AR")) {
			db_pandora_audit("ACL Violation",
				"Trying to access (read) to agent ".agents_get_name($this->idAgent));
			include ("../general/noaccess.php");
			return;
		}
		
		echo "<h3 class='title_h3'><a href='index.php?page=agent&id=" . $this->agentModule['id_agente'] . "'>" . modules_get_agentmodule_agent_name($this->idAgentModule)."</a> / ".io_safe_output($this->agentModule['nombre']) . "</h3>";
		
		echo "<h3 class='title_h3'>" . __('Graph') . "</h3>";
		
		echo grafico_modulo_sparse($this->idAgentModule, $this->period, 0, 240,
			200, io_safe_output($this->agentModule['nombre']), null, false,
			false, true, 0, '', true, false, true, true, '../');
		
		echo "<h3 class='title_h3'>" . __('Data') . "</h3>";
		
		echo "<form method='post' action='index.php?page=agent&action=view_module_graph&id=" . $this->idAgentModule . "'>";
		echo __("Choose period:");
		echo html_print_extended_select_for_time ('period', $this->period, 'this.form.submit();', '', '0', 5);
		echo "</form><br />";
		
		$moduletype_name = modules_get_moduletype_name (modules_get_agentmodule_type ($this->idAgentModule));
		
		if ($moduletype_name == "log4x") {
			$sql_body = sprintf ("FROM tagente_datos_log4x
				WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $this->idAgentModule, (get_system_time () - $this->period));
			
			$columns = array(
				
				//"Timestamp" => array("utimestamp",				"modules_format_timestamp", 	"align" => "center" ),
				"Sev" 		=> array("severity", 				"format_data", 			"align" => "center", "width" => "70px"),
				"Message"	=> array("message", 				"modules_format_verbatim",		"align" => "left", "width" => "45%"),
				"StackTrace" 		=> array("stacktrace",				"modules_format_verbatim", 			"align" => "left", "width" => "50%")
			);
		}
		else if (preg_match ("/string/", $moduletype_name)) {
			$sql_body = sprintf (" FROM tagente_datos_string
				WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $this->idAgentModule, (get_system_time () - $this->period));
			
			$columns = array(
				//"Timestamp"	=> array("utimestamp", 			"modules_format_timestamp", 		"align" => "center"),
				"Data" 		=> array("datos", 				"format_data", 				"align" => "center"),
				"Time" 		=> array("utimestamp", 			"modules_format_time", 				"align" => "center")
			);
		}
		else {
			$sql_body = sprintf (" FROM tagente_datos
				WHERE id_agente_modulo = %d AND utimestamp > %d
				ORDER BY utimestamp DESC", $this->idAgentModule, (get_system_time () - $this->period));
			
			$columns = array(
				"Data" 		=> array("datos", 				"format_data", 			"align" => "center"),
				"Time" 		=> array("utimestamp", 			"modules_format_time", 			"align" => "center")
			);
		}
		
		$sql_count = 'SELECT COUNT(*) ' . $sql_body;
		
		$count = db_get_value_sql($sql_count);
		
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = 'SELECT * ' . $sql_body . ' LIMIT ' . $this->offset . ',' . $this->system->getPageSize();
				break;
			case "postgresql":
				$sql = 'SELECT * ' . $sql_body . ' LIMIT ' . $this->system->getPageSize() . ' OFFSET ' . $this->offset;
				break;
			case "oracle":
				$set = array();
				$set['limit'] = $this->system->getPageSize();
				$set['offset'] = $this->offset;
				$sql = oracle_recode_query ('SELECT * ' . $sql_body, $set);
				break;
		}
		
		$result = db_get_all_rows_sql ($sql);

		if (($config["dbtype"] == 'oracle') && ($result !== false)) {
			// Delete rnum row generated by oracle_recode_query() function
			for ($i=0; $i < count($result); $i++) {
				unset($result[$i]['rnum']);		
			}	
		}	
		
		$table = null;
		$table->width = '100%';
		$table->head = array();
		$index = 0;
		foreach($columns as $col => $attr) {
			$table->head[$index] = $col;
			
			if (isset($attr["align"]))
				$table->align[$index] = $attr["align"];
			
			if (isset($attr["width"]))
				$table->size[$index] = $attr["width"];
		
			$index++;
		}
		
		$table->data = array(); //$this->system->debug($result);
		$rowPair = false;
		$iterator = 0;
		foreach ($result as $row) {
			if ($rowPair)
				$table->rowclass[$iterator] = 'rowPair';
			else
				$table->rowclass[$iterator] = 'rowOdd';
			$rowPair = !$rowPair;
			$iterator++;
			
			$data = array ();
		
			foreach($columns as $col => $attr){
				$data[] = $attr[1] ($row[$attr[0]]);
			}
		
			array_push ($table->data, $data);
		}

		html_print_table($table);

		$pagination = ui_pagination ($count,
			ui_get_url_refresh (array ('period' => $this->period)),
			0, 0, true);

		echo $pagination;
	}
}
?>
