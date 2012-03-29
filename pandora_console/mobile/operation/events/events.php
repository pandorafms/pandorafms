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

require_once('../include/functions_events.php');
require_once('../include/functions_users.php');

class EventsView {
	private $system;
	
	function __construct() {
		global $system;
		
		$this->system = $system;
	}
	
	function show() {
		global $config;

		require_once ($config['homedir'].'/include/functions_agents.php');
		
		$config['text_char_long'] = 12;
		
		$offset = $this->system->getRequest("offset", 0);
		$ev_group = $this->system->getRequest("ev_group", 0); //0 = all
		$event_type = get_parameter ("event_type", ''); // 0 all
		$severity = $this->system->getRequest("severity", -1); // -1 all
		$search = preg_replace ("/&([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/", "%", rawurldecode ($this->system->getRequest("search", '')));
		
		$event_view_hr = 24; //Last day of events
		
		$types = get_event_types();
		// Expand standard array to add not_normal (not exist in the array, used only for searches)
		$types["not_normal"] = __("Not normal");
		
		
		$table = null;
		$table->width = '100%';
		
		$table->colspan[1][2] = 2;
		
		$table->data[0][0] = '<span alt="' . __('Group') . '" title="' . __('Group') . '"><b>' . __('G') . '</b></span>';
		$table->data[0][1] = html_print_select_groups($this->system->getConfig("id_user"), "IR", true, 'ev_group', $ev_group, '', '', 0, true, false, false, 'w130');
		$table->data[0][2] = '<span alt="' . __('Event type') . '" title="' . __('Event type') . '"><b>' . __('E') . '</b></span>';
		$table->data[0][3] = html_print_select ($types, 'event_type', $event_type, '', __('All'), '', true);
		$table->data[1][0] = '<span alt="' . __('Severity') . '" title="' . __('Severity') . '"><b>' . __('S') . '</b></span>';
		$table->data[1][1] = html_print_select (get_priorities (), "severity", $severity, '', __('All'), '-1', true);
		$table->data[1][2] = html_print_input_text('search', $search, '', 5, 20, true);
		$table->data[1][2] .= "<input type='submit' class='button_filter' name='submit_button' value='' alt='" . __('Filter') . "' title='" . __('Filter') . "' />";
		
		echo "<form method='post'>";
		html_print_table($table);
		echo "</form>";
		
		
		$groups = users_get_groups ($this->system->getConfig("id_user"), "IR");
		$sqlGroups = '';
		//Group selection
		if ($ev_group > 0 && in_array ($ev_group, array_keys ($groups))) {
			//If a group is selected and it's in the groups allowed
			$sqlGroups = " AND id_grupo = $ev_group";
		}
		else {
			if (is_user_admin ($this->system->getConfig("id_user"))) {
				//Do nothing if you're admin, you get full access
				$sqlGroups = "";
			}
			else {
				//Otherwise select all groups the user has rights to.
				$sqlGroups = " AND id_grupo IN (".implode (",", array_keys ($groups)).")";
			}
		}
		
		
		$sqlEventType = '';
		if ($event_type != "") {
			// If normal, warning, could be several (going_up_warning, going_down_warning... too complex 
			// for the user so for him is presented only "warning, critical and normal"
			if ($event_type == "warning" || $event_type == "critical" || $event_type == "normal") {
				$sqlEventType = " AND event_type LIKE '%$event_type%' ";
			}
			elseif ($event_type == "not_normal") {
				$sqlEventType = " AND event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ";
			}
			else {
				$sqlEventType = " AND event_type = '".$event_type."'";
			}
		
		}
		
		
		$sqlSeverity = '';
		if ($severity != -1)
			$sqlSeverity = " AND criticity >= ".$severity;
		
		
		$sqlFreeSearch = '';
		if ($search != "")
			$sqlFreeSearch .= " AND evento LIKE '%".$search."%'";

			
			
		$unixtime = get_system_time () - ($event_view_hr * 3600); //Put hours in seconds
		$sqlTimestamp = " AND utimestamp > ".$unixtime;
			
		
		
		
		$sql  = 'SELECT *
			FROM tevento
			WHERE 1=1 ' . $sqlGroups . $sqlEventType . $sqlSeverity . 
			$sqlFreeSearch . $sqlTimestamp . '  AND estado = 0 ORDER BY utimestamp DESC';
		
		$sql_count = str_replace('*', 'COUNT(*)', $sql);
		
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = $sql . sprintf(' LIMIT %d,%d', $offset, $this->system->getPageSize());
				break;
			case "postgresql":
				$sql = $sql . sprintf(' LIMIT %d OFFSET %d', $this->system->getPageSize(), $offset);
				break;
			case "oracle":
				$set = array();
				$set['limit'] = $this->system->getPageSize();
				$set['offset'] = $offset;
				$sql = oracle_recode_query ($sql, $set);
				break;
		}
		
		$count = db_get_value_sql($sql_count);
		
		$rows = db_get_all_rows_sql($sql);
		if ($rows === false)
			$rows = array();
		
		
		$table = null;
		$table->width = '100%';
		$table->head = array();
		$table->head[3] = '<span title="' . __('Timestamp') . '" alt="' . __('Timestamp') . '">' . __('T') . '</span>';
		$table->head[4] = '<span title="' . __('Description') . '" alt="' . __('Description') . '">' . __('Des.') . '</span>';
		$table->head[5] = '<span title="' . __('Agent') . '" alt="' . __('Agent') . '">' . __('Agent') . '</span>';
		
		$table->data = array();
		$iterator = 0;
		foreach ($rows as $row) {
			$data = array();
			
			switch ($row["criticity"]) {
				default:
				case 0:
					$img = "../images/status_sets/default/severity_maintenance_pixel.png";
					break;
				case 1:
					$img = "../images/status_sets/default/severity_informational_pixel.png";
					break;
				case 2:
					$img = "../images/status_sets/default/severity_normal_pixel.png";
					break;
				case 3:
					$img = "../images/status_sets/default/severity_warning_pixel.png";
					break;
				case 4:
					$img = "../images/status_sets/default/severity_critical_pixel.png";
					break;
			}
			
			$table->rowclass[$iterator] = get_priority_class($row["criticity"]);
			$iterator++;
				
			$data[] = ui_print_timestamp($row["timestamp"], true, array('units' => 'tiny'));
						
			$data[] = $row["evento"];
			
			if ($row["event_type"] == "system") {
				$data[] = ui_print_truncate_text(__('System'), 20, true, true);
			}
			elseif ($row["id_agente"] > 0) {
				// Agent name
				$data[] = '<a href="index.php?page=agent&id=' . $row["id_agente"] . '">' . ui_print_truncate_text(agents_get_name($row["id_agente"]), 20, true, true) . '</a>';
			}
			else {
				$data[] = ui_print_truncate_text(__('Alert SNMP'), 20, true, true);
			}
			
			$table->data[] = $data;
		}
		
		html_print_table($table);
		
		$pagination = ui_pagination ($count,
			ui_get_url_refresh(array("offset" => $offset, "ev_group" => $ev_group,
			"event_type" => $event_type, "severity" => $severity,
			"search" => $search)), 0, 0, true);

		echo $pagination;
	}
}
?>
