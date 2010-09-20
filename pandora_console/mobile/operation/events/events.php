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

class EventsView {
	private $system;
	
	function __construct() {
		global $system;
		
		$this->system = $system;
	}
	
	function show() {
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
		$table->data[0][1] = print_select_groups($this->system->getConfig("id_user"), "IR", true, 'ev_group', $ev_group, '', '', 0, true, false, false, 'w130');
		$table->data[0][2] = '<span alt="' . __('Event type') . '" title="' . __('Event type') . '"><b>' . __('E') . '</b></span>';
		$table->data[0][3] = print_select ($types, 'event_type', $event_type, '', __('All'), '', true);
		$table->data[1][0] = '<span alt="' . __('Severity') . '" title="' . __('Severity') . '"><b>' . __('S') . '</b></span>';
		$table->data[1][1] = print_select (get_priorities (), "severity", $severity, '', __('All'), '-1', true);
		$table->data[1][2] = print_input_text('search', $search, '', 10, 20, true);
		$table->data[1][2] .= "<input type='submit' class='button_filter' name='submit_button' value='' alt='" . __('Filter') . "' title='" . __('Filter') . "' />";
		
		echo "<form method='post'>";
		print_table($table);
		echo "</form>";
		
		
		$groups = get_user_groups ($this->system->getConfig("id_user"), "IR");
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
		
		$sql = $sql . ' LIMIT %d,%d';
		
		$sql = sprintf($sql, $offset, $this->system->getPageSize());
		
		$count = get_db_value_sql($sql_count);
		
		$rows = get_db_all_rows_sql($sql);
		
		
		$table = null;
		$table->width = '100%';
		$table->head = array();
		$table->head[0] = '<span title="' . __('Severity') . '" alt="' . __('Severity') . '">' . __('S') . '</span>';
		$table->head[1] = '<span title="' . __('Group') . '" alt="' . __('Group') . '">' . __('G') . '</span>';
		$table->head[2] = '<span title="' . __('Type') . '" alt="' . __('Type') . '">' . __('T') . '</span>';
		$table->head[3] = '<span title="' . __('Timestamp') . '" alt="' . __('Timestamp') . '">' . __('T') . '</span>';
		$table->head[4] = '<span title="' . __('Description') . '" alt="' . __('Description') . '">' . __('Des.') . '</span>';
		$table->head[5] = '<span title="' . __('Agent') . '" alt="' . __('Agent') . '">' . __('Agent') . '</span>';
		
		$table->data = array();
		foreach ($rows as $row) {
			$data = array();
			
			switch ($row["criticity"]) {
				default:
				case 0:
					$img = "../images/status_sets/default/severity_maintenance.png";
					break;
				case 1:
					$img = "../images/status_sets/default/severity_informational.png";
					break;
				case 2:
					$img = "../images/status_sets/default/severity_normal.png";
					break;
				case 3:
					$img = "../images/status_sets/default/severity_warning.png";
					break;
				case 4:
					$img = "../images/status_sets/default/severity_critical.png";
					break;
			}
			
			$data[] = '<a href="index.php?page=events&offset=' . $offset .
				'&ev_group=' . $ev_group . '&event_type=' . $event_type .
				'&severity=' . $row["criticity"] . '&search=' . $search . '">' .
				print_image ($img, true, 
				array ("class" => "image_status",
					"width" => 12,
					"height" => 12,
					"title" => get_priority_name($row["criticity"]))) . '</a>';
				
			$data[] = '<a href="index.php?page=events&ev_group=' .
				$row["id_grupo"] .  '&event_type=' . $event_type .
				'&severity='. $severity . '&search=' . $search . '">' . 
				str_replace('images/', '../images/', print_group_icon ($row["id_grupo"], true, "groups_small", '', false))
				. '</a>';
			
			$data[] = '<a href="index.php?page=events&ev_group=' . $ev_group .
				'&event_type=' . $row["event_type"] . '&severity=' . $severity .
				'&search=' . $search . '">' .
				str_replace('images/', '../images/', print_event_type_img($row["event_type"], true)) .
				'</a>';
			$data[] = print_timestamp($row["timestamp"], true, array('units' => 'tiny'));
						
			$data[] = printTruncateText($row["evento"], 8, true, true);
			
			if ($row["event_type"] == "system") {
				$data[] = printTruncateText(__('System'), 8, true, true);
			}
			elseif ($row["id_agente"] > 0) {
				// Agent name
				$data[] = printTruncateText(get_agent_name($row["id_agente"]), 8, true, true);
			}
			else {
				$data[] = printTruncateText(__('Alert SNMP'), 8, true, true);
			}
			
			$table->data[] = $data;
		}
		
		print_table($table);
		
		$pagination = pagination ($count,
			get_url_refresh(array("offset" => $offset, "ev_group" => $ev_group,
			"event_type" => $event_type, "severity" => $severity,
			"search" => $search)), 0, 0, true);
			
		$pagination = str_replace('images/go_first.png', '../images/go_first.png', $pagination);
		$pagination = str_replace('images/go_previous.png', '../images/go_previous.png', $pagination);
		$pagination = str_replace('images/go_next.png', '../images/go_next.png', $pagination);
		$pagination = str_replace('images/go_last.png', '../images/go_last.png', $pagination);
		
		echo $pagination;
	}
}
?>