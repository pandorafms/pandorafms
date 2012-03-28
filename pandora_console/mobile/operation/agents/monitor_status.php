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

class MonitorStatus {
	private $system;
	private $user;
	private $offset;
	
	public function __construct($user) {
		global $system;
		
		$this->system = $system;
		$this->user = $user;
		
		$this->offset = $this->system->getRequest("offset", 0);
	}
	
	public function show() {
		global $config;
		require_once ($config['homedir'].'/include/functions_users.php');
		require_once ($config['homedir'].'/include/functions_modules.php');


		$config['text_char_long'] = 12;
		
		$group = $this->system->getRequest("group", 0); //0 = all
		$modulegroup = $this->system->getRequest("modulegroup", 0); //0 = all
		$status = $this->system->getRequest("status", -1); //-1 = all
		$search = $this->system->getRequest('filter_text', '');
		
		$table = null;
		$table->width = '100%';
		$table->colspan[1][2] = 2;
		
		$table->data[0][0] = '<span alt="' . __('Group') . '" title="' . __('Group') . '"><b>' . __('G') . '</b></span>';
		$table->data[0][1] = html_print_select_groups($this->system->getConfig("id_user"), "IR", true, 'group', $group, '', '', 0, true, false, false, 'w130');
		$table->data[0][2] = '<span alt="' . __('Monitor status') . '" title="' . __('Monitor Status') . '"><b>' . __('M') . '</b></span>';
		$fields = array ();
		$fields[-1] = __('All');
		$fields[0] = __('Normal'); 
		$fields[1] = __('Warning');
		$fields[2] = __('Critical');
		$fields[3] = __('Unknown');
		$fields[4] = __('Not normal'); //default
		$fields[5] = __('Not init');
		foreach ($fields as $key => $field) {
			$fields[$key] = ui_print_truncate_text($field, $config['text_char_long'], false, true, false);
		}
		$table->data[0][3] = html_print_select ($fields, "status", $status, '', '', -1, true);
		$table->data[1][0] = '<span alt="' . __('Module group') . '" title="' . __('Module group') . '"><b>' . __('M') . '</b></span>';
		$table->data[1][1] = html_print_select_from_sql ("SELECT * FROM tmodule_group ORDER BY name",
			'module_group', $modulegroup, '',__('All'), 0, true);
		$table->data[1][2] = html_print_input_text('search', $search, '', 5, 20, true);
		$table->data[1][2] .= "<input type='submit' class='button_filter' name='submit_button' value='' alt='" . __('Filter') . "' title='" . __('Filter') . "' />";
		
		echo "<form method='post'>";
		html_print_table($table);
		echo "</form>";
		
		
		
		
		// Agent group selector
		if (($group > 0) && (check_acl($system->getConfig('id_user'), $group, "AR"))) {
			$sqlGroup = sprintf (" AND tagente.id_grupo = %d", $ag_group);
		}
		else {
			$user_groups_all = users_get_groups ($this->user->getIdUser(), "AR");
			$user_groups = array_keys ($user_groups_all);
			$user_groupsText = implode(',', $user_groups);
			
			// User has explicit permission on group 1 ?
			$sqlGroup = " AND tagente.id_grupo IN (" . $user_groupsText . ")";
		}
		
		
		
		// Status selector
		$sqlStatus = '';
		if ($status == 0) { //Normal
			$sqlStatus = " AND tagente_estado.estado = 0 
			AND (utimestamp > 0 OR (tagente_modulo.id_tipo_modulo IN(21,22,23,100))) ";
		}
		elseif ($status == 2) { //Critical
			$sqlStatus = " AND tagente_estado.estado = 1 AND utimestamp > 0";
		}
		elseif ($status == 1) { //Warning
			$sqlStatus = " AND tagente_estado.estado = 2 AND utimestamp > 0";	
		}
		elseif ($status == 4) { //Not normal
			$sqlStatus = " AND tagente_estado.estado <> 0";
		} 
		elseif ($status == 3) { //Unknown
			$sqlStatus = " AND tagente_estado.estado = 3";
		}
		elseif ($status == 5) { //Not init
			$sqlStatus = " AND tagente_estado.utimestamp = 0 AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100)";	
		}
		
		
		
		// Module group
		$sqlModuleGroup = '';
		if ($modulegroup > 0) {
			$sqlModuleGroup = sprintf (" AND tagente_modulo.id_module_group = '%d'", $modulegroup);
		}
		
		
		// Freestring selector
		$sqlFreeSearch = '';
		if ($search != "") {
			$sqlFreeSearch = sprintf (" AND (tagente.nombre LIKE '%%%s%%' OR tagente_modulo.nombre LIKE '%%%s%%' OR tagente_modulo.descripcion LIKE '%%%s%%')", $search, $search, $search);
		}
		
		
		$selectSQL = 'SELECT tagente_modulo.id_agente_modulo,
				tagente.intervalo AS agent_interval, tagente.nombre AS agent_name, 
				tagente_modulo.nombre AS module_name,
				tagente_modulo.id_agente_modulo, tagente_modulo.history_data,
				tagente_modulo.flag AS flag, tagente.id_grupo AS id_group,
				tagente.id_agente AS id_agent,
				tagente_modulo.id_tipo_modulo AS module_type,
				tagente_modulo.module_interval, tagente_estado.datos,
				tagente_estado.estado, tagente_estado.utimestamp AS utimestamp';
		
		$sql = ' FROM tagente, tagente_modulo, tagente_estado 
			WHERE tagente.id_agente = tagente_modulo.id_agente 
				AND tagente_modulo.disabled = 0 
				AND tagente.disabled = 0 
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				' . $sqlGroup . '
				' . $sqlStatus . '
				' . $sqlModuleGroup . '
				' . $sqlFreeSearch . '
			ORDER BY tagente.id_grupo, tagente.nombre';
		
		$total = db_get_value_sql('SELECT COUNT(*) ' . $sql);
		
		
		switch ($config["dbtype"]) {
			case "mysql":
				$rows = db_get_all_rows_sql($selectSQL . $sql . ' LIMIT ' . $this->offset . ', ' . $this->system->getPageSize());
				break;
			case "postgresql":
				$rows = db_get_all_rows_sql($selectSQL . $sql . ' LIMIT ' . $this->system->getPageSize() . ' OFFSET ' . $this->offset);
				break;
			case "oracle":
				$set = array();
				$set['limit'] = $this->system->getPageSize();
				$set['offset'] = $this->offset;
				$rows = oracle_recode_query ($selectSQL . $sql, $set, 'AND', true);
				break;	
		}
		
		if ($rows === false) $rows = array();
	
		if ($config["dbtype"] == 'oracle') {
			for ($i=0; $i < count($rows); $i++) {
				unset($rows[$i]['rnum']);		
			}
		} 	
		
		$table = null;
		$table->width = '100%';
		
		$table->data = array();
		$rowPair = false;
		$iterator = 0;
		foreach ($rows as $row) {
			if ($rowPair)
				$table->rowclass[$iterator] = 'rowPair';
			else
				$table->rowclass[$iterator] = 'rowOdd';
			$rowPair = !$rowPair;
			$iterator++;
			
			$data = array();
			
			if($row['utimestamp'] == 0 && (($row['module_type'] < 21 || $row['module_type'] > 23) && $row['module_type'] != 100)){
				$statusImg = ui_print_status_image(STATUS_MODULE_NO_DATA, __('NOT INIT'), true);
			}
			elseif ($row["estado"] == 0) {
				$statusImg = ui_print_status_image(STATUS_MODULE_OK, __('NORMAL').": ".$row["datos"], true);
			}
			elseif ($row["estado"] == 1) {
				$statusImg = ui_print_status_image(STATUS_MODULE_CRITICAL, __('CRITICAL').": ".$row["datos"], true);
			}
			elseif ($row["estado"] == 2) {
				$statusImg = ui_print_status_image(STATUS_MODULE_WARNING, __('WARNING').": ".$row["datos"], true);
			}
			else {
				$last_status =  modules_get_agentmodule_last_status($row['id_agente_modulo']);
				switch($last_status) {
					case 0:
						$statusImg = ui_print_status_image(STATUS_MODULE_OK, __('UNKNOWN')." - ".__('Last status')." ".__('NORMAL').": ".$row["datos"], true);
						break;
					case 1:
						$statusImg = ui_print_status_image(STATUS_MODULE_CRITICAL, __('UNKNOWN')." - ".__('Last status')." ".__('CRITICAL').": ".$row["datos"], true);
						break;
					case 2:
						$statusImg = ui_print_status_image(STATUS_MODULE_WARNING, __('UNKNOWN')." - ".__('Last status')." ".__('WARNING').": ".$row["datos"], true);
						break;
				}
			}
			
			$data[] = str_replace('<img src="' , '<img width="15" height="15" src="', $statusImg);
			
			$data[] = '<a href="index.php?page=agent&id=' . $row['id_agent'] . '">' . ui_print_truncate_text($row['agent_name'], 25, true, true) . '</a>';
			$data[] = '<a href="index.php?page=agent&action=view_module_graph&id=' . $row['id_agente_modulo'] . '">' . 
				ui_print_truncate_text($row['module_name'], 25, true, true) . '</a>';
			if (is_numeric($row["datos"]))
				$data[] = format_numeric($row["datos"]);
			else
				$data[] = "<span title='".$row['datos']."' style='white-space: nowrap;'>".substr(io_safe_output($row["datos"]),0,12)."</span>";
				
			$data[] = ui_print_timestamp ($row["utimestamp"], true, array('units' => 'tiny'));
			
			$table->data[] = $data;
		}
		
		html_print_table($table);
		
		$pagination = pagination ($total,
			ui_get_url_refresh (array ()),
			0, 0, true);
			
		$pagination = str_replace('images/go_first.png', '../images/go_first.png', $pagination);
		$pagination = str_replace('images/go_previous.png', '../images/go_previous.png', $pagination);
		$pagination = str_replace('images/go_next.png', '../images/go_next.png', $pagination);
		$pagination = str_replace('images/go_last.png', '../images/go_last.png', $pagination);
			
		echo $pagination;
	}
}
?>
