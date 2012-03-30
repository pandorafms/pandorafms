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
global $config;

// Login check
check_login ();

require_once ("include/functions_reports.php");

// Load enterprise extensions
enterprise_include ('operation/reporting/custom_reporting.php');

$enterpriseEnable = false;
if (enterprise_include_once('include/functions_reporting.php') !== ENTERPRISE_NOT_HOOK) {
	$enterpriseEnable = true;
}

//Constant with fonts directory
define ('_MPDF_TTFONTPATH', 'include/fonts/');

$activeTab = get_parameter('tab', 'main');
$action = get_parameter('action', 'list');
$idReport = get_parameter('id_report', 0);
$offset = get_parameter('offset', 0);
$idItem = get_parameter('id_item', 0);

switch ($action) {
	case 'sort_items':
		switch ($activeTab) {
			case 'list_items':
			
				$resultOperationDB = null;
				$position_to_sort = (int)get_parameter('position_to_sort', 1);
				$ids_serialize = (string)get_parameter('ids_items_to_sort', '');
				$move_to = (string)get_parameter('move_to', 'after');
				
				$countItems = db_get_sql('SELECT COUNT(id_rc)
					FROM treport_content WHERE id_report = ' . $idReport);
				
				if (($countItems < $position_to_sort) || ($position_to_sort < 1)) {
					$resultOperationDB = false;
				}
				else if (!empty($ids_serialize)) {
					$ids = explode('|', $ids_serialize);
					
					switch ($config["dbtype"]) {
						case "mysql":
							$items = db_get_all_rows_sql('SELECT id_rc, `order`
								FROM treport_content
								WHERE id_report = ' . $idReport . '
								ORDER BY `order`');
							break;
						case "oracle":
						case "postgresql":
							$items = db_get_all_rows_sql('SELECT id_rc, "order"
								FROM treport_content
								WHERE id_report = ' . $idReport . '
								ORDER BY "order"');
							break;
					}
					
					if ($items === false) $items = array();
					
					
					$temp = array();
					
					$temp = array();
					foreach ($items as $item) {
						//Remove the contents from the block to sort
						if (array_search($item['id_rc'], $ids) === false) {
							$temp[$item['order']] = $item['id_rc'];
						}
					}
					$items = $temp;
					
					$sorted_items = array();
					foreach ($items as $pos => $id_unsort) {
						if ($pos == $position_to_sort) {
							if ($move_to == 'after') {
								$sorted_items[] = $id_unsort;
							}
							
							foreach ($ids as $id) {
								$sorted_items[] = $id;
							}
							
							if ($move_to != 'after') {
								$sorted_items[] = $id_unsort;
							}
						}
						else {
							$sorted_items[] = $id_unsort;
						}
					}
					
					$items = $sorted_items;
					
					foreach ($items as $order => $id) {
						switch ($config["dbtype"]) {
							case "mysql":
								db_process_sql_update('treport_content',
									array('`order`' => ($order + 1)), array('id_rc' => $id));
								break;
							case "postgresql":
							case "oracle":
								db_process_sql_update('treport_content',
									array('"order"' => ($order + 1)), array('id_rc' => $id));
								break;
						}
					}
					
					$resultOperationDB = true;
				}
				else {
					$resultOperationDB = false;
				}
				break;
			}
		break;
	case 'delete_report':
	case 'list':
		// Report LIST
		ui_print_page_header (__('Reporting').' &raquo; '.__('Custom reporting'), "images/reporting.png", false, "");
		
		if ($action == 'delete_report') {
			$result = reports_delete_report ($idReport);
			ui_print_result_message ($result,
				__('Successfully deleted'),
				__('Could not be deleted'));
		}
	
		$own_info = get_user_info ($config['id_user']);
		if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
			$return_all_group = true;
		else
			$return_all_group = false;
		
		$reports = reports_get_reports (array ('order' => 'name'),
			array ('name', 'id_report', 'description', 'private', 'id_user', 'id_group'), $return_all_group, 'IR');
		
		$table->width = '0px';
		if (sizeof ($reports)) {
			$table->id = 'report_list';
			$table->width = '98%';
			$table->head = array ();
			$table->align = array ();
			$table->align[2] = 'center';
			$table->align[3] = 'center';
			$table->align[4] = 'center';
			$table->data = array ();
			$table->head[0] = __('Report name');
			$table->head[1] = __('Description');
			$table->head[2] = __('HTML');
			$table->head[3] = __('XML');
			
			$next = 4;
			//Calculate dinamically the number of the column
			if (enterprise_hook ('load_custom_reporting_1') !== ENTERPRISE_NOT_HOOK) {
				$next = 6;
			}
			
			//Admin options only for IW flag
			if (check_acl ($config['id_user'], 0, "IW")) {
			
				$table->head[$next] = __('Private');
				$table->size[$next] = '40px';
				$table->align[$next] = 'center';
				$next++;
				$table->head[$next] = __('Group');
				$table->align[$next] = 'center';
				$next++;
				$table->head[$next] = '<span title="Operations">' . __('Op.') . '</span>';
				$table->size = array ();
				$table->size[$next] = '60px';
			
			}
			
			foreach ($reports as $report) {
				
				if (!is_user_admin ($config["id_user"])){
					if ($report["private"] && $report["id_user"] != $config['id_user'])
						if (!check_acl ($config["id_user"], $report["id_group"], "AR"))
							continue;
					if (!check_acl ($config["id_user"], $report["id_group"], "AR"))
						continue;
				}
				
				$data = array ();
					
				if (check_acl ($config["id_user"], $report["id_group"], "AW")) {
			
					$data[0] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&action=edit&id_report='.
							$report['id_report'].'">'.$report['name'].'</a>';
						
				} else {
					$data[0] = $report['name'];
				}
						
						
				$data[1] = $report['description'];
				
				$data[2] = '<a href="index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$report['id_report'].'">' .
						html_print_image("images/reporting.png", true) . '</a>';
				$data[3] = '<a href="ajax.php?page=operation/reporting/reporting_xml&id='.$report['id_report'].'">' . html_print_image("images/database_lightning.png", true) . '</a>'; //I chose ajax.php because it's supposed to give XML anyway


				//Calculate dinamically the number of the column	
				$next = 4;
				if (enterprise_hook ('load_custom_reporting_2') !== ENTERPRISE_NOT_HOOK) {
					$next = 6;
				}
				
				//Admin options only for IW flag
				if (check_acl ($config['id_user'], 0, "IW")) {
					if ($report["private"] == 1)
						$data[$next] = __('Yes');
					else
						$data[$next] = __('No');
						
					$next++;
					
						
					$data[$next] = ui_print_group_icon($report['id_group'], true);
					$next++;
					
					$data[$next] = '<form method="post" action="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&action=edit" style="display:inline">';
					$data[$next] .= html_print_input_hidden ('id_report', $report['id_report'], true);
					$data[$next] .= html_print_input_image ('edit', 'images/config.png', 1, '', true, array ('title' => __('Edit')));
					$data[$next] .= '</form>';
							
					$data[$next] .= '&nbsp;&nbsp;<form method="post" style="display:inline" onsubmit="if (!confirm (\''.__('Are you sure?').'\')) return false">';
					$data[$next] .= html_print_input_hidden ('id_report', $report['id_report'], true);
					$data[$next] .= html_print_input_hidden ('action','delete_report', true);
					$data[$next] .= html_print_input_image ('delete', 'images/cross.png', 1, '',
						true, array ('title' => __('Delete')));
					$data[$next] .= '</form>';
				}
				
				array_push ($table->data, $data);
							
			}
			html_print_table ($table);
		} else {
			echo "<div class='nf'>".__('There are no defined reportings')."</div>";
		}
		
		
		if (check_acl ($config['id_user'], 0, "IW")) {
			echo '<form method="post" action="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=main&action=new">';
			echo '<div class="action-buttons" style="width: 98%;">';
			html_print_submit_button (__('Create report'), 'create', false, 'class="sub next"');
			echo "</div>";
			echo "</form>";
		}
		return;
		break;
	case 'new':
		switch ($activeTab) {
			case 'main':
				$reportName = '';
				$idGroupReport = 0; //All groups
				$description = '';
				$resultOperationDB = null;
				break;
			case 'item_editor':
				$resultOperationDB = null;
				$report = db_get_row_filter('treport', array('id_report' => $idReport));
				
				$reportName = $report['name'];
				$idGroupReport = $report['id_group'];
				$description = $report['description'];
				break;
		}
		break;
	case 'update':
	case 'save': 
		switch ($activeTab) {
			case 'main':
				$reportName = get_parameter('name');
				$idGroupReport = get_parameter('id_group');
				$description = get_parameter('description');
				
				if ($action == 'update') {
					if ($reportName != "" && $idGroupReport != ""){
						$resultOperationDB = (bool)db_process_sql_update('treport', array('name' => $reportName, 'id_group' => $idGroupReport, 'description' => $description), array('id_report' => $idReport));
					}
					else {
						$resultOperationDB = false;
					}
				}
				else if ($action == 'save') {
					if($reportName != "" && $idGroupReport != "") {
						$idOrResult = db_process_sql_insert('treport', array('name' => $reportName, 'id_group' => $idGroupReport, 'description' => $description));
					}
					else {
						$idOrResult = false;
					}
					
					if ($idOrResult === false) {
						$resultOperationDB = false;
					}
					else {
						$resultOperationDB = true;
						$idReport = $idOrResult;
					}
				}
				$action = 'edit';
				break;
			case 'item_editor':
				$resultOperationDB = null;
				$report = db_get_row_filter('treport', array('id_report' => $idReport));
				
				$reportName = $report['name'];
				$idGroupReport = $report['id_group'];
				$description = $report['description'];
				$good_format = false;
				switch ($action) {
					case 'update':
						$values = array();
						$values['id_report'] = $idReport;
						$values['description'] = get_parameter('description');
						$values['type'] = get_parameter('type', null);
						// Added support for projection graphs, prediction date and SLA reports
						// 'top_n_value','top_n' and 'text' fields will be reused for these types of report
						switch ($values['type']) {
							case 'projection_graph':
								$values['period'] = get_parameter('period1');
								$values['top_n_value'] = get_parameter('period2');
								$values['text'] = get_parameter('text');
								$good_format = true;
								break;
							case 'prediction_date':
								$values['period'] = get_parameter('period1');	
								$values['top_n'] = get_parameter('radiobutton_max_min_avg');
								$values['top_n_value'] = get_parameter('quantity');												
								$interval_max = get_parameter('max_interval');
								$interval_min = get_parameter('min_interval');
								// Checks intervals fields
								if (preg_match('/^(\-)*[0-9]*\.?[0-9]+$/', $interval_max) and preg_match('/^(\-)*[0-9]*\.?[0-9]+$/', $interval_min)){
									$good_format = true;
								}
								$intervals = get_parameter('max_interval') . ';' . get_parameter('min_interval');
								$values['text'] = $intervals;
								break;
							case 'SLA':
								$values['period'] = get_parameter('period');
								$values['top_n'] = get_parameter('combo_sla_sort_options',0);
								$values['top_n_value'] = get_parameter('quantity');
								$values['text'] = get_parameter('text');
								$good_format = true;
								break;
							case 'inventory':
								$values['period'] = 0;
								$es['date'] = get_parameter('date');
								$es['id_agents'] = get_parameter('id_agents');
								$es['inventory_modules'] = get_parameter('inventory_modules');
								$description = get_parameter('description');
								$values['external_source'] = json_encode($es);
								$good_format = true;
								break;
							case 'inventory_changes':
								$values['period'] = get_parameter('period');
								$es['id_agents'] = get_parameter('id_agents');
								$es['inventory_modules'] = get_parameter('inventory_modules');
								$description = get_parameter('description');
								$values['external_source'] = json_encode($es);
								$good_format = true;
								break;
							default: 
								$values['period'] = get_parameter('period');
								$values['top_n'] = get_parameter('radiobutton_max_min_avg',0);
								$values['top_n_value'] = get_parameter('quantity');
								$values['text'] = get_parameter('text');
								$good_format = true;
						}

						$values['id_agent'] = get_parameter('id_agent');
						$values['id_gs'] = get_parameter('id_custom_graph');
						$values['id_agent_module'] = get_parameter('id_agent_module');
						$values['only_display_wrong'] = get_parameter('checkbox_only_display_wrong');
						$values['monday'] = get_parameter('monday', 0);
						$values['tuesday'] = get_parameter('tuesday', 0);
						$values['wednesday'] = get_parameter('wednesday', 0);
						$values['thursday'] = get_parameter('thursday', 0);
						$values['friday'] = get_parameter('friday', 0);
						$values['saturday'] = get_parameter('saturday', 0);
						$values['sunday'] = get_parameter('sunday', 0);
						$values['time_from'] = get_parameter('time_from');
						$values['time_to'] = get_parameter('time_to');
						$values['group_by_agent'] = get_parameter ('checkbox_row_group_by_agent');
						$values['show_resume'] = get_parameter ('checkbox_show_resume');
						$values['order_uptodown'] = get_parameter ('radiobutton_order_uptodown');
						$values['exception_condition'] = get_parameter('radiobutton_exception_condition');
						$values['exception_condition_value'] = get_parameter('exception_condition_value');
						$values['show_graph'] = get_parameter('combo_graph_options');
						$values['id_module_group'] = get_parameter('combo_modulegroup');
						$values['id_group'] = get_parameter ('combo_group');
						$values['server_name'] = get_parameter ('server_name');
						if ($values['server_name'] == '')
							$values['server_name'] = get_parameter ('combo_server');
							
						if (($values['type'] == 'custom_graph') && ($values['id_gs'] == 0 || $values['id_gs'] == '')) {
							$resultOperationDB = false;
							break;
						}
						
						$id_gs = substr ($values['id_gs'], 0, strpos ($values['id_gs'], '|'));
						if ($id_gs !== false) {
							$server_name = strstr($values ['id_gs'], '|');
							$values ['id_gs'] = $id_gs;
							$values['server_name'] = substr ($server_name, 1, strlen($server_name));
						}
					
						if (($values['type'] == 'sql') OR ($values['type'] == 'sql_graph_hbar')OR ($values['type'] == 'sql_graph_vbar') OR ($values['type'] == 'sql_graph_pie')) {
							$values['treport_custom_sql_id'] = get_parameter('id_custom');
							if ($values['treport_custom_sql_id'] == 0) {
								$values['external_source'] = get_parameter('sql');
							}
						}
						else if ($values['type'] == 'url') {
							$values['external_source'] = get_parameter('url');
						}
						else if ($values['type'] == 'event_report_group') {
							$values['id_agent'] = get_parameter('group');
						}
						
						$values['header_definition'] = get_parameter('header');
						$values['column_separator'] = get_parameter('field');
						$values['line_separator'] = get_parameter('line');
						
						$style = array();
						$style['show_in_two_columns'] = get_parameter('show_in_two_columns', 0);
						$style['show_in_landscape'] = get_parameter('show_in_landscape', 0);
						$values['style'] = io_safe_input(json_encode($style));
						
						if ($good_format){
							$resultOperationDB = db_process_sql_update('treport_content', $values, array('id_rc' => $idItem));
						}
						else{
							$resultOperationDB = false;
						}
						break;
					case 'save':
						$values = array();
						$values['id_report'] = $idReport;
						$values['type'] = get_parameter('type', null);
						$values['description'] = get_parameter('description');
						// Support for projection graph, prediction date and SLA reports
						// 'top_n_value', 'top_n' and 'text' fields will be reused for these types of report
						switch ($values['type']) {
							case 'projection_graph':
								$values['period'] = get_parameter('period1');
								$values['top_n_value'] = get_parameter('period2');
								$values['text'] = get_parameter('text');
								$good_format = true;
								break;
							case 'prediction_date':
								$values['period'] = get_parameter('period1');	
								$values['top_n'] = get_parameter('radiobutton_max_min_avg');
								$values['top_n_value'] = get_parameter('quantity');												
								$interval_max = get_parameter('max_interval');
								$interval_min = get_parameter('min_interval');
								// Checks intervals fields
								if (preg_match('/^(\-)*[0-9]*\.?[0-9]+$/', $interval_max) and preg_match('/^(\-)*[0-9]*\.?[0-9]+$/', $interval_min)){
									$good_format = true;
								}
								$intervals = get_parameter('max_interval') . ';' . get_parameter('min_interval');
								$values['text'] = $intervals;
								break;
							case 'SLA':
								$values['period'] = get_parameter('period');
								$values['top_n'] = get_parameter('combo_sla_sort_options',0);
								$values['top_n_value'] = get_parameter('quantity');
								$values['text'] = get_parameter('text');
								$good_format = true;
								break;
							case 'inventory':
								$values['period'] = 0;
								$es['date'] = get_parameter('date');
								$es['id_agents'] = get_parameter('id_agents');
								$es['inventory_modules'] = get_parameter('inventory_modules');
								$values['external_source'] = json_encode($es);
								$good_format = true;
								break;
							case 'inventory_changes':
								$values['period'] = get_parameter('period');
								$es['id_agents'] = get_parameter('id_agents');
								$es['inventory_modules'] = get_parameter('inventory_modules');
								$values['external_source'] = json_encode($es);
								$good_format = true;
								break;
							default: 
								$values['period'] = get_parameter('period');
								$values['top_n'] = get_parameter('radiobutton_max_min_avg',0);
								$values['top_n_value'] = get_parameter('quantity');
								$values['text'] = get_parameter('text');
								$good_format = true;
						}

						$values['id_agent'] = get_parameter('id_agent');
						$values['id_gs'] = get_parameter('id_custom_graph');
						$values['id_agent_module'] = get_parameter('id_agent_module');
						switch ($config['dbtype']) {
							case "mysql":
							case "postgresql":
								$values['only_display_wrong'] = get_parameter('checkbox_only_display_wrong');
								break;
							case "oracle":
								$only_display_wrong_tmp = get_parameter('checkbox_only_display_wrong');
								if (empty($only_display_wrong_tmp)){
									$values['only_display_wrong'] = 0;
								}
								else{
									$values['only_display_wrong'] = $only_display_wrong_tmp;					
								}														
								break;
						}	
						$values['monday'] = get_parameter('monday', 0);
						$values['tuesday'] = get_parameter('tuesday', 0);
						$values['wednesday'] = get_parameter('wednesday', 0);
						$values['thursday'] = get_parameter('thursday', 0);
						$values['friday'] = get_parameter('friday', 0);
						$values['saturday'] = get_parameter('saturday', 0);
						$values['sunday'] = get_parameter('sunday', 0);
						switch ($config['dbtype']) {
							case "mysql":
							case "postgresql":
								$values['time_from'] = get_parameter('time_from');
								$values['time_to'] = get_parameter('time_to');
								break;
							case "oracle":
								$values['time_from'] = '#to_date(\'' . get_parameter('time_from') . '\',\'hh24:mi\')';
								$values['time_to'] = '#to_date(\'' . get_parameter('time_to') . '\', \'hh24:mi\')';
								break;		
						}							
						$values['group_by_agent'] = get_parameter ('checkbox_row_group_by_agent',0);
						$values['show_resume'] = get_parameter ('checkbox_show_resume',0);
						$values['order_uptodown'] = get_parameter ('radiobutton_order_uptodown',0);
						$values['exception_condition'] = get_parameter('radiobutton_exception_condition');
						$values['exception_condition_value'] = get_parameter('exception_condition_value');
						$values['show_graph'] = get_parameter('combo_graph_options');
						$values['id_module_group'] = get_parameter('combo_modulegroup');
						$values['id_group'] = get_parameter ('combo_group');
						$values['server_name'] = get_parameter ('server_name');
						if ($values['server_name'] == '')
							$values['server_name'] = get_parameter ('combo_server');
						
						if (($values['type'] == 'custom_graph') && ($values['id_gs'] == 0 || $values['id_gs'] == '')) {
							$resultOperationDB = false;
							break;
						}
						$id_gs = substr ($values['id_gs'], 0, strpos ($values['id_gs'], '|'));
						if ($id_gs !== false && $id_gs !== '') {
							$server_name = strstr($values ['id_gs'], '|');
							$values ['id_gs'] = $id_gs;
							$values['server_name'] = substr ($server_name, 1, strlen($server_name));
						}
						
						if (($values['type'] == 'sql') OR ($values['type'] == 'sql_graph_hbar')
							OR ($values['type'] == 'sql_graph_vbar') OR ($values['type'] == 'sql_graph_pie')) {
							
							$values['treport_custom_sql_id'] = get_parameter('id_custom');
							if ($values['treport_custom_sql_id'] == 0) {
								$values['external_source'] = get_parameter('sql');
							}
						}
						elseif ($values['type'] == 'url') {
							$values['external_source'] = get_parameter('url');
						}
						else if ($values['type'] == 'event_report_group') {
							$values['id_agent'] = get_parameter('group');
						}
						
						$values['header_definition'] = get_parameter('header');
						$values['column_separator'] = get_parameter('field');
						$values['line_separator'] = get_parameter('line');
						
						$style = array();
						$style['show_in_two_columns'] = get_parameter('show_in_two_columns', 0);
						$style['show_in_landscape'] = get_parameter('show_in_landscape', 0);
						$values['style'] = io_safe_input(json_encode($style));
						
						if ($good_format){
							$result = db_process_sql_insert('treport_content', $values);
							
							if ($result === false) {
									$resultOperationDB = false;
							}
							else {
								$idItem = $result;
								
								switch ($config["dbtype"]) {
									case "mysql":
										$max = db_get_all_rows_sql('SELECT max(`order`) AS max 
											FROM treport_content WHERE id_report = ' . $idReport . ';');
										break;
									case "postgresql":
									case "oracle":
										$max = db_get_all_rows_sql('SELECT max("order") AS max 
											FROM treport_content WHERE id_report = ' . $idReport);
										break;
								}
								if ($max === false) {
									$max = 0;
								}
								else {
									$max = $max[0]['max'];
								}
								switch ($config["dbtype"]) {
									case "mysql":
										db_process_sql_update('treport_content', array('`order`' => $max + 1), array('id_rc' => $idItem));
										break;
									case "postgresql":
									case "oracle":
										db_process_sql_update('treport_content', array('"order"' => $max + 1), array('id_rc' => $idItem));
										break;
								}
								$resultOperationDB = true;
							}
							break;
						}
						// If fields dont have good format
						else {
							$resultOperationDB = false;
						}
				}
				break;
			default:
				if ($enterpriseEnable) {
					$resultOperationDB = reporting_enterprise_update_action();
				}
				break;
		}
		break;
	case 'filter':
	case 'edit':
		$resultOperationDB = null;
		$report = db_get_row_filter('treport', array('id_report' => $idReport));
		
		$reportName = $report['name'];
		$idGroupReport = $report['id_group'];
		$description = $report['description'];
		break;
	case 'delete':
		$idItem = get_parameter('id_item');
		
		$report = db_get_row_filter('treport', array('id_report' => $idReport));
		$reportName = $report['name'];
		
		$resultOperationDB = db_process_sql_delete('treport_content_sla_combined', array('id_report_content' => $idItem));
		$resultOperationDB2 = db_process_sql_delete('treport_content_item', array('id_report_content' => $idItem));
		if ($resultOperationDB !== false) {
			$resultOperationDB = db_process_sql_delete('treport_content', array('id_rc' => $idItem));
		}
		if ($resultOperationDB2 !== false) {
			$resultOperationDB2 = db_process_sql_delete('treport_content', array('id_rc' => $idItem));
		}
		break;
	case 'order':
		$resultOperationDB = null;
		$report = db_get_row_filter('treport', array('id_report' => $idReport));
		
		$reportName = $report['name'];
		$idGroupReport = $report['id_group'];
		$description = $report['description'];
		
		$idItem = get_parameter('id_item');
		$dir = get_parameter('dir');
		$field = get_parameter('field', null);
		
		switch ($field) {
			case 'module':
			case 'agent':
			case 'type':
				switch ($field) {
					case 'module':
						$sql = "
							SELECT t1.id_rc, t2.nombre
							FROM treport_content AS t1
								LEFT JOIN tagente_modulo AS t2
									ON t1.id_agent_module = t2.id_agente_modulo
							WHERE %s
							ORDER BY nombre %s
						";
						break;
					case 'agent':
						$sql = "
							SELECT t4.id_rc, t5.nombre
							FROM
								(
								SELECT t1.*, id_agente
								FROM treport_content AS t1
									LEFT JOIN tagente_modulo AS t2
										ON t1.id_agent_module = id_agente_modulo
								) AS t4
								LEFT JOIN tagente AS t5
									ON (t4.id_agent = t5.id_agente OR t4.id_agente = t5.id_agente)
							WHERE %s
							ORDER BY t5.nombre %s
						";
						break;
					case 'type':
						$sql = "SELECT id_rc FROM treport_content WHERE %s ORDER BY type %s";
						break;
				}
				$sql = sprintf($sql, 'id_report = ' . $idReport, '%s');
				switch ($dir) {
					case 'up':
						$sql = sprintf($sql, 'ASC');
						break;
					case 'down':
						$sql = sprintf($sql, 'DESC');
						break;
				}
				
				$ids = db_get_all_rows_sql($sql);
				
				$count = 1;
				$resultOperationDB = true;
				foreach($ids as $id) {
					$result = db_process_sql_update('treport_content', array('order' => $count), array('id_rc' => $id['id_rc']));
					
					if ($result === false) {
						$resultOperationDB = false;
						break;
					}
					
					$count = $count + 1;
				}
				break;
			default:
				switch ($config["dbtype"]) {
					case "mysql":
						$oldOrder = db_get_value_sql('SELECT `order` FROM treport_content WHERE id_rc = ' . $idItem);
						break;
					case "postgresql":
					case "oracle":
						$oldOrder = db_get_value_sql('SELECT "order" FROM treport_content WHERE id_rc = ' . $idItem);
						break;
				}
				//db_get_value_filter('order', 'treport_content', array('id_rc' => $idItem));
		
				switch ($dir) {
					case 'up':
						$newOrder = $oldOrder - 1;
						break;
					case 'down':
						$newOrder = $oldOrder + 1;
						break;	
				}
				
				db_process_sql_begin();
				
				switch ($config["dbtype"]) {
					case "mysql":
						$resultOperationDB = db_process_sql_update('treport_content',
							array('`order`' => $oldOrder), array('`order`' => $newOrder, 'id_report' => $idReport));
						break;
					case "postgresql":
						$resultOperationDB = db_process_sql_update('treport_content',
							array('"order"' => $oldOrder), array('"order"' => $newOrder, 'id_report' => $idReport));
						break;
					case "oracle":
						$resultOperationDB = db_process_sql_update('treport_content',
							array('"order"' => $oldOrder), array('"order"' => $newOrder, 'id_report' => $idReport), 'AND', false);
						break;
				}
				if ($resultOperationDB !== false) {
					switch ($config["dbtype"]) {
						case "mysql":
							$resultOperationDB = db_process_sql_update('treport_content', array('`order`' => $newOrder), array('id_rc' => $idItem));
							break;
						case "postgresql":
							$resultOperationDB = db_process_sql_update('treport_content', array('"order"' => $newOrder), array('id_rc' => $idItem));
							break;
						case "oracle":
							$resultOperationDB = db_process_sql_update('treport_content', array('"order"' => $newOrder), array('id_rc' => $idItem), 'AND', false);
							break;
					}
					if ($resultOperationDB !== false) {
						db_process_sql_commit();
					}
					else {
						db_process_sql_rollback();
					}
				}
				else {
					db_process_sql_rollback();
				}
				break;
		}
		break;
}

if ($enterpriseEnable) {
	$result = reporting_enterprise_actions_DB($action, $activeTab, $idReport);
	if ($result !== null) {
		$resultOperationDB = $result;
	}
}

$buttons = array(
	'main' => array('active' => false,
		'text' => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=main&action=edit&id_report=' . $idReport . '">' . 
			html_print_image("images/reporting_edit.png", true, array ("title" => __('Main'))) .'</a>'),
	'list_items' => array('active' => false,
		'text' => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=edit&id_report=' . $idReport . '">' . 
			html_print_image("images/god6.png", true, array ("title" => __('List items'))) .'</a>'),
	'item_editor' => array('active' => false,
		'text' => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=item_editor&action=new&id_report=' . $idReport . '">' . 
			html_print_image("images/config.png", true, array ("title" => __('Item editor'))) .'</a>')
	);
	
if ($enterpriseEnable) {
	$buttons = reporting_enterprise_add_Tabs($buttons, $idReport);
}

$buttons['view'] = array('active' => false,
	'text' => '<a href="index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id=' . $idReport . '">' . 
			html_print_image("images/reporting.png", true, array ("title" => __('View report'))) .'</a>');
	
$buttons[$activeTab]['active'] = true;

if ($idReport != 0) {
	$textReportName = " &raquo; " . $reportName;
}
else {
	$temp = $buttons['main'];
	$buttons = null;
	$buttons['main'] = $temp;
	$buttons['main']['active'] = true;
	$textReportName = '';
}

ui_print_page_header(__('Reporting') . $textReportName, "images/reporting_edit.png", false, "reporting_" . $activeTab . "_tab", true, $buttons);

if ($resultOperationDB !== null) {
	ui_print_result_message ($resultOperationDB, __('Successfull action'), __('Unsuccessfull action'));
}

switch ($activeTab) {
	case 'main':
		require_once('godmode/reporting/reporting_builder.main.php');
		break;
	case 'list_items':
		require_once('godmode/reporting/reporting_builder.list_items.php');
		break;
	case 'item_editor':
		require_once('godmode/reporting/reporting_builder.item_editor.php');
		break;
	default:
		reporting_enterprise_select_tab($activeTab);
		break;
}

?>
