<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Reporting
 */

/**
 * Include the usual functions
 */
require_once($config["homedir"] . "/include/functions.php");
require_once($config["homedir"] . "/include/functions_db.php");
require_once($config["homedir"] . "/include/functions_agents.php");
include_once($config["homedir"] . "/include/functions_groups.php");
require_once($config["homedir"] . '/include/functions_graph.php');
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_events.php");
include_once($config['homedir'] . "/include/functions_alerts.php");
include_once($config['homedir'] . '/include/functions_users.php');
enterprise_include_once('include/functions_metaconsole.php');
enterprise_include_once('include/functions_inventory.php');
include_once($config['homedir'] . "/include/functions_forecast.php");
include_once($config['homedir'] . "/include/functions_ui.php");
include_once($config['homedir'] . "/include/functions_netflow.php");

function reporting_html_header(&$table, $mini, $title, $subtitle,
	$period, $date, $from, $to) {
	
	global $config;
	
	
	if ($mini) {
		$sizh = '';
		$sizhfin = '';
	}
	else {
		$sizh = '<h4>';
		$sizhfin = '</h4>';
	}
	
	
	$date_text = "";
	if (!empty($date)) {
		$date_text = date($config["date_format"], $date);
	}
	else if (!empty($from) && !empty($to)) {
		$date_text = 
			"(" . human_time_description_raw ($period) . ") " .
			__("From:") . " " . date($config["date_format"], $from) . "<br />" .
			__("To:") . " " . date($config["date_format"], $to);
	}
	else if ($period > 0) {
		$date_text = human_time_description_raw($period);
	}
	else if ($period === 0) {
		$date_text = __('Last data');
	}
	
	
	$data = array();
	if (empty($subtitle) && (empty($date_text))) {
		$data[] = $sizh . $title . $sizhfin;
		$table->colspan[0][0] = 3;
	}
	else if (empty($subtitle)) {
		$data[] = $sizh . $title . $sizhfin;
		$data[] = "<div style='text-align: right;'>" . $sizh . $date_text . $sizhfin . "</div>";
		$table->colspan[0][1] = 2;
	}
	else if (empty($date_text)) {
		$data[] = $sizh . $title . $sizhfin;
		$data[] = $sizh . $subtitle . $sizhfin;
		$table->colspan[0][1] = 2;
	}
	else {
		$data[] = $sizh . $title . $sizhfin;
		$data[] = $sizh . $subtitle . $sizhfin;
		$data[] = "<div style='text-align: right;'>" . $sizh . $date_text . $sizhfin . "</div>";
	}
	
	array_push ($table->data, $data);
}

function reporting_html_print_report($report, $mini = false) {
	
	foreach ($report['contents'] as $key => $item) {
		$table->size = array ();
		$table->style = array ();
		$table->width = '98%';
		$table->class = 'databox';
		$table->rowclass = array ();
		$table->rowclass[0] = 'datos3';
		$table->data = array ();
		$table->head = array ();
		$table->style = array ();
		$table->colspan = array ();
		$table->rowstyle = array ();
		
		
		reporting_html_header($table,
			$mini, $item['title'],
			$item['subtitle'],
			$item['date']['period'],
			$item['date']['date'],
			$item['date']['from'],
			$item['date']['to']);
		
		if ($item["description"] != "") {
			$table->data['description_row']['description'] = $item["description"];
			$table->colspan['description_row']['description'] = 3;
		}
		
		switch ($item['type']) {
			case 'availability':
				reporting_html_availability($table, $item);
				break;
			case 'general':
				reporting_html_general($table, $item);
				break;
			case 'sql':
				reporting_html_sql($table, $item);
				break;
			case 'simple_graph':
				reporting_html_graph($table, $item);
				break;
			case 'custom_graph':
				reporting_html_graph($table, $item);
				break;
			case 'text':
				reporting_html_text($table, $item);
				break;
			case 'url':
				reporting_html_url($table, $item, $key);
				break;
			case 'max_value':
				reporting_html_max_value($table, $item, $mini);
				break;
			case 'avg_value':
				reporting_html_avg_value($table, $item, $mini);
				break;
			case 'min_value':
				reporting_html_min_value($table, $item, $mini);
				break;
			case 'sumatory':
				reporting_html_sum_value($table, $item, $mini);
				break;
			case 'MTTR':
				reporting_html_MTTR_value($table, $item, $mini, true, true);
				break;
			case 'MTBF':
				reporting_html_MTBF_value($table, $item, $mini, true, true);
				break;
			case 'TTO':
				reporting_html_TTO_value($table, $item, $mini, false, true);
				break;
			case 'TTRT':
				reporting_html_TTRT_value($table, $item, $mini, false, true);
				break;
			case 'agent_configuration':
				reporting_html_agent_configuration($table, $item);
				break;
			case 'projection_graph':
				reporting_html_graph($table, $item);
				break;
			case 'prediction_date':
				reporting_html_prediction_date($table, $item, $mini);
				break;
			case 'simple_baseline_graph':
				reporting_html_graph($table, $item);
				break;
			case 'netflow_area':
				reporting_html_graph($table, $item);
				break;
			case 'netflow_pie':
				reporting_html_graph($table, $item);
				break;
			case 'netflow_data':
				reporting_html_graph($table, $item);
				break;
			case 'netflow_statistics':
				reporting_html_graph($table, $item);
				break;
			case 'netflow_summary':
				reporting_html_graph($table, $item);
				break;
			case 'monitor_report':
				reporting_html_monitor_report($table, $item, $mini);
				break;
			case 'sql_graph_vbar':
				reporting_html_sql_graph($table, $item);
				break;
			case 'sql_graph_hbar':
				reporting_html_sql_graph($table, $item);
				break;
			case 'sql_graph_pie':
				reporting_html_sql_graph($table, $item);
				break;
			case 'alert_report_module':
				reporting_html_alert_report_module($table, $item);
				break;
			case 'alert_report_agent':
				reporting_html_alert_report_agent($table, $item);
				break;
			case 'alert_report_group':
				reporting_html_alert_report_group($table, $item);
				break;
			case 'network_interfaces_report':
				reporting_html_network_interfaces_report($table, $item);
				break;
			case 'group_configuration':
				reporting_html_group_configuration($table, $item);
				break;
			case 'database_serialized':
				reporting_html_database_serialized($table, $item);
				break;
			case 'agent_detailed_event':
			case 'event_report_agent':
				reporting_html_event_report_agent($table, $item);
				break;
			case 'group_report':
				reporting_html_group_report($table, $item);
				break;
			case 'exception':
				reporting_html_exception($table, $item);
				break;
			case 'agent_module':
				reporting_html_agent_module($table, $item);
				break;
			case 'inventory':
				reporting_html_inventory($table, $item);
				break;
			case 'inventory_changes':
				reporting_html_inventory_changes($table, $item);
				break;
			case 'event_report_module':
				reporting_html_event_report_module($table, $item);
				break;
		}
		
		if ($item['type'] == 'agent_module')
			echo '<div style="width: 99%; overflow: auto;">';
		
		html_print_table($table);
		
		if ($item['type'] == 'agent_module')
			echo '</div>';
	}
}

function reporting_html_event_report_module($table, $item) {
	
	global $config;
	
	if (!empty($item['failed'])) {
		$table->colspan['events']['cell'] = 3;
		$table->data['events']['cell'] = $item['failed'];
	}
	else {
		$table1->width = '99%';
		$table1->data = array ();
		$table1->head = array ();
		$table1->head[0] = __('Status');
		$table1->head[1] = __('Event name');
		$table1->head[2] = __('Event type');
		$table1->head[3] = __('Criticity');
		$table1->head[4] = __('Count');
		$table1->head[5] = __('Timestamp');
		$table1->style[0] = 'text-align: center;';
		$table1->style[4] = 'text-align: center;';
		
		
		foreach ($item['data'] as $i => $event) {
			$data = array();
			
			$table1->cellclass[$i][1] =
			$table1->cellclass[$i][2] =
			$table1->cellclass[$i][3] =
			$table1->cellclass[$i][4] =
			$table1->cellclass[$i][5] =  get_priority_class($event["criticity"]);
			
			// Colored box
			switch ($event['estado']) {
				case 0:
					$img_st = "images/star.png";
					$title_st = __('New event');
					break;
				case 1:
					$img_st = "images/tick.png";
					$title_st = __('Event validated');
					break;
				case 2:
					$img_st = "images/hourglass.png";
					$title_st = __('Event in process');
					break;
			}
			
			$data[0] = html_print_image ($img_st, true, 
				array ("class" => "image_status",
					"width" => 16,
					"title" => $title_st,
					"id" => 'status_img_' . $event["id_evento"]));
			$data[1] = io_safe_output($event['evento']);
			$data[2] = $event['event_type'];
			$data[3] = get_priority_name ($event['criticity']);
			$data[4] = $event['event_rep'];
			$data[5] = date($config['date_format'], $event['timestamp_rep']);
			
			$table1->data[] = $data;
		}
		
		$table->colspan['events']['cell'] = 3;
		$table->data['events']['cell'] = html_print_table($table1, true);
	}
}

function reporting_html_inventory_changes($table, $item) {
	if (!empty($item['failed'])) {
		$table->colspan['failed']['cell'] = 3;
		$table->cellstyle['failed']['cell'] = 'text-align: center;';
		$table->data['failed']['cell'] = $item['failed'];
	}
	else {
		foreach ($item['data'] as $module_item) {
			$table1 = null;
			$table1->width = '99%';
			
			$table1->cellstyle[0][0] =
			$table1->cellstyle[0][1] =
				'background: #373737; color: #FFF;';
			$table1->data[0][0] = $module_item['agent'];
			$table1->data[0][1] = $module_item['module'];
			
			
			$table1->cellstyle[1][0] =
				'background: #373737; color: #FFF;';
			$table1->data[1][0] = $module_item['date'];
			$table1->colspan[1][0] = 2;
			
			
			$table1->cellstyle[2][0] =
				'background: #373737; color: #FFF; text-align: center;';
			$table1->data[2][0] = __('Added');
			$table1->colspan[2][0] = 2;
			
			
			$table1->data = array_merge($table1->data, $module_item['added']);
			
			
			$table1->cellstyle[3 + count($module_item['added'])][0] =
				'background: #373737; color: #FFF; text-align: center;';
			$table1->data[3 + count($module_item['added'])][0] = __('Deleted');
			$table1->colspan[3 + count($module_item['added'])][0] = 2;
			
			
			$table1->data = array_merge($table1->data, $module_item['deleted']);
			
			
			$table->colspan[
				$module_item['agent'] . "_" .$module_item['module']]['cell'] = 3;
			$table->data[
				$module_item['agent'] . "_" .$module_item['module']]['cell'] =
				html_print_table($table1, true);
		}
	}
}

function reporting_html_inventory($table, $item) {
	if (!empty($item['failed'])) {
		$table->colspan['failed']['cell'] = 3;
		$table->cellstyle['failed']['cell'] = 'text-align: center;';
		$table->data['failed']['cell'] = $item['failed'];
	}
	else {
		foreach ($item['data'] as $module_item) {
			$table1 = null;
			$table1->width = '99%';
			
			$first = reset($module_item['data']);
			$count_columns = count($first);
			
			
			$table1->cellstyle[0][0] =
				'background: #373737; color: #FFF;';
			$table1->data[0][0] = $module_item['agent_name'];
			if ($count_columns == 1)
				$table1->colspan[0][0] = $count_columns + 1; // + columm date
			else
				$table1->colspan[0][0] = $count_columns;
			
			
			$table1->cellstyle[1][0] =
			$table1->cellstyle[1][1] =
				'background: #373737; color: #FFF;';
			$table1->data[1][0] = $module_item['name'];
			if ($count_columns - 1 > 0)
				$table1->colspan[1][0] = $count_columns - 1;
			$table1->data[1][1] = $module_item['timestamp'];
			
			
			$table1->cellstyle[2] = array_pad(
				array(),
				$count_columns,
				'background: #373737; color: #FFF;');
			$table1->data[2] = array_keys($first);
			if ($count_columns - 1 == 0) {
				$table1->colspan[2][0] = $count_columns + 1; // + columm date; 
			}
			
			$table1->data = array_merge($table1->data, $module_item['data']);
			
			
			$table->colspan[
				$module_item['name'] . "_" .$module_item['id_agente']]['cell'] = 3;
			$table->data[
				$module_item['name'] . "_" .$module_item['id_agente']]['cell'] =
				html_print_table($table1, true);
			
			
		}
	}
	
}

function reporting_html_agent_module($table, $item) {
	$table->colspan['agent_module']['cell'] = 3;
	$table->cellstyle['agent_module']['cell'] = 'text-align: center;';
	
	if (!empty($item['failed'])) {
		$table->data['agent_module']['cell'] = $item['failed'];
	}
	else {
		$table_data = '<table cellpadding="1" cellspacing="4" cellspacing="0" border="0" style="background-color: #EEE;">';
		
		$table_data .= "<th>" . __("Agents") . " / " . __("Modules") . "</th>";
		
		
		$first = reset($item['data']);
		$list_modules = $first['modules'];
		
		foreach ($list_modules as $module_name => $module) {
			$file_name = string2image(
				ui_print_truncate_text($module_name, 'module_small',
					false, true, false, '...'),
				false, false, 6, 270, '#B1B1B1', 'FFF', 4, 0);
			$table_data .= '<th width="22px">' .
				html_print_image($file_name, true,
					array('title' => $module_name)) .
				"</th>";
		}
		
		foreach ($item['data'] as $row) {
			$table_data .= "<tr style='height: 35px;'>";
			switch ($row['agent_status']) {
				case 4: // Alert fired status
					$rowcolor = COL_ALERTFIRED;
					$textcolor = '#000';
					break;
				case 1: // Critical status
					$rowcolor = COL_CRITICAL;
					$textcolor = '#FFF';
					break;
				case 2: // Warning status
					$rowcolor = COL_WARNING;
					$textcolor = '#000';
					break;
				case 0: // Normal status
					$rowcolor = COL_NORMAL;
					$textcolor = '#FFF';
					break;
				case 3: 
				case -1: 
				default: // Unknown status
					$rowcolor = COL_UNKNOWN;
					$textcolor = '#FFF';
					break;
			}
			
			$file_name = string2image(
				ui_print_truncate_text($row['agent_name'], 'agent_small',
					false, true, false, '...'),
				false, false, 6, 0, $rowcolor, $textcolor, 4, 0);
			$table_data .= "<td style='background-color: " . $rowcolor . ";'>" .
				html_print_image($file_name, true,
					array('title' => $row['agent_name'])) . "</td>";
			
			foreach ($row['modules'] as $module) {
				if (is_null($module)) {
					$table_data .= "<td style='background-color: #DDD;'></td>";
				}
				else {
					$table_data .= "<td style='text-align: center; background-color: #DDD;'>";
					switch ($module) {
						case 0:
							$table_data .= ui_print_status_image(
								'module_ok.png',
								__("%s in %s : NORMAL",
									$module['name'],
									$row['agent_name']),
								true, array('width' => '20px', 'height' => '20px'));
							break;
						case 1:
							$table_data .= ui_print_status_image(
								'module_critical.png',
								__("%s in %s : CRITICAL",
									$module['name'],
									$row['agent_name']),
								true, array('width' => '20px', 'height' => '20px'));
							break;
						case 2:
							$table_data .= ui_print_status_image(
								'module_warning.png',
								__("%s in %s : WARNING",
									$module['name'],
									$row['agent_name']),
								true, array('width' => '20px', 'height' => '20px'));
							break;
						case 3:
							$table_data .= ui_print_status_image(
								'module_unknown.png',
								__("%s in %s : UNKNOWN",
									$module['name'],
									$row['agent_name']),
								true, array('width' => '20px', 'height' => '20px'));
							break;
						case 4:
							$table_data .= ui_print_status_image(
								'module_alertsfired.png',
								__("%s in %s : ALERTS FIRED",
									$module['name'],
									$row['agent_name']),
								true, array('width' => '20px', 'height' => '20px'));
							break;
					}
					$table_data .= "</td>";
				}
				
			}
		}
		
		$table_data .= "</table>";
		
		$table_data .= "<div class='legend_basic' style='width: 96%'>";
		
		$table_data .= "<table>";
		$table_data .= "<tr><td colspan='2' style='padding-bottom: 10px;'><b>" . __('Legend') . "</b></td></tr>";
		$table_data .= "<tr><td class='legend_square_simple'><div style='background-color: " . COL_ALERTFIRED . ";'></div></td><td>" . __("Orange cell when the module has fired alerts") . "</td></tr>";
		$table_data .= "<tr><td class='legend_square_simple'><div style='background-color: " . COL_CRITICAL . ";'></div></td><td>" . __("Red cell when the module has a critical status") . "</td></tr>";
		$table_data .= "<tr><td class='legend_square_simple'><div style='background-color: " . COL_WARNING . ";'></div></td><td>" . __("Yellow cell when the module has a warning status") . "</td></tr>";
		$table_data .= "<tr><td class='legend_square_simple'><div style='background-color: " . COL_NORMAL . ";'></div></td><td>" . __("Green cell when the module has a normal status") . "</td></tr>";
		$table_data .= "<tr><td class='legend_square_simple'><div style='background-color: " . COL_UNKNOWN . ";'></div></td><td>" . __("Grey cell when the module has an unknown status") . "</td></tr>";
		$table_data .= "</table>";
		$table_data .= "</div>";
		
		
		$table->data['agent_module']['cell'] = $table_data;
	}
}

function reporting_html_exception($table, $item) {
	
	if (!empty($item['failed'])) {
		$table->colspan['group_report']['cell'] = 3;
		$table->cellstyle['group_report']['cell'] = 'text-align: center;';
		$table->data['group_report']['cell'] = $item['failed'];
	}
	else {
		$table1->width = '99%';
		
		$table1->align = array();
		$table1->align['agent'] = 'left';
		$table1->align['module'] = 'left';
		$table1->align['operation'] = 'left';
		$table1->align['value'] = 'right';
		
		$table1->data = array ();
		
		$table1->head = array ();
		$table1->head['agent'] = __('Agent');
		$table1->head['module'] = __('Module');
		$table1->head['operation'] = __('Operation');
		$table1->head['value'] = __('Value');
		
		foreach ($item['data'] as $data) {
			$row = array();
			$row['agent'] = $data['agent'];
			$row['module'] = $data['module'];
			$row['operation'] = $data['operation'];
			$row['value'] = $data['formated_value'];
			
			$table1->data[] = $row;
		}
		
		$table->colspan['data']['cell'] = 3;
		$table->cellstyle['data']['cell'] = 'text-align: center;';
		$table->data['data']['cell'] = html_print_table($table1, true);
		
		if (!empty($item['chart'])) {
			$table->colspan['chart_pie']['cell'] = 3;
			$table->cellstyle['chart_pie']['cell'] = 'text-align: center;';
			$table->data['chart_pie']['cell'] = $item["chart"]["pie"];
			
			$table->colspan['chart_hbar']['cell'] = 3;
			$table->cellstyle['chart_hbar']['cell'] = 'text-align: center;';
			$table->data['chart_hbar']['cell'] = $item["chart"]["hbar"];
		}
		
		if (!empty($item['resume'])) {
			$table1 = null;
			$table1->width = '99%';
			
			$table1->align = array();
			$table1->align['min'] = 'right';
			$table1->align['avg'] = 'right';
			$table1->align['max'] = 'right';
			
			$table1->head = array ();
			$table1->head['min'] = __('Min Value');
			$table1->head['avg'] = __('Average Value');
			$table1->head['max'] = __('Max Value');
			
			$table1->data = array ();
			$table1->data[] = array(
				'min' => $item['resume']['min']['formated_value'],
				'avg' => $item['resume']['avg']['formated_value'],
				'max' => $item['resume']['max']['formated_value']);
			
			$table->colspan['resume']['cell'] = 3;
			$table->cellstyle['resume']['cell'] = 'text-align: center;';
			$table->data['resume']['cell'] = html_print_table($table1, true);
		}
	}
}

function reporting_html_group_report($table, $item) {
	global $config;
	
	$table->colspan['group_report']['cell'] = 3;
	$table->cellstyle['group_report']['cell'] = 'text-align: center;';
	$table->data['group_report']['cell'] = "<table width='100%'>
		<tr>
			<td></td>
			<td colspan='3'><div class='cellBold cellCenter'>" .
				__('Total') . "</div></td>
			<td colspan='3'><div class='cellBold cellCenter'>" .
				__('Unknown') . "</div></td>
		</tr>
		<tr>
			<td><div class='cellBold cellCenter'>" .
				__('Agents') . "</div></td>
			<td colspan='3'><div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>" .
				$item["data"]['group_stats']['total_agents'] . "</div></td>
			<td colspan='3'><div class='cellBold cellCenter cellUnknown cellBorder1 cellBig'>" .
				$item["data"]['group_stats']['agents_unknown'] . "</div></td>
		</tr>
		<tr>
			<td></td>
			<td><div class='cellBold cellCenter'>" .
				__('Total') . "</div></td>
			<td><div class='cellBold cellCenter'>" .
				__('Normal') . "</div></td>
			<td><div class='cellBold cellCenter'>" .
				__('Critical') . "</div></td>
			<td><div class='cellBold cellCenter'>" .
				__('Warning') . "</div></td>
			<td><div class='cellBold cellCenter'>" .
				__('Unknown') . "</div></td>
			<td><div class='cellBold cellCenter'>" .
				__('Not init') . "</div></td>
		</tr>
		<tr>
			<td><div class='cellBold cellCenter'>" .
				__('Monitors') . "</div></td>
			<td><div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>" .
				$item["data"]['group_stats']['monitor_checks'] . "</div></td>
			<td><div class='cellBold cellCenter cellNormal cellBorder1 cellBig'>" .
				$item["data"]['group_stats']['monitor_ok'] ."</div></td>
			<td><div class='cellBold cellCenter cellCritical cellBorder1 cellBig'>" .
				$item["data"]['group_stats']['monitor_critical'] . "</div></td>
			<td><div class='cellBold cellCenter cellWarning cellBorder1 cellBig'>" .
				$item["data"]['group_stats']['monitor_warning'] . "</div></td>
			<td><div class='cellBold cellCenter cellUnknown cellBorder1 cellBig'>" .
				$item["data"]['group_stats']['monitor_unknown'] . "</div></td>
			<td><div class='cellBold cellCenter cellNotInit cellBorder1 cellBig'>" .
				$item["data"]['group_stats']['monitor_not_init'] . "</div></td>
		</tr>
		<tr>
			<td></td>
			<td colspan='3'><div class='cellBold cellCenter'>" .
				__('Defined') . "</div></td>
			<td colspan='3'><div class='cellBold cellCenter'>" .
				__('Fired') . "</div></td>
		</tr>
		<tr>
			<td><div class='cellBold cellCenter'>" .
				__('Alerts') . "</div></td>
			<td colspan='3'><div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>" .
				$item["data"]['group_stats']['monitor_alerts'] . "</div></td>
			<td colspan='3'><div class='cellBold cellCenter cellAlert cellBorder1 cellBig'>" .
				$item["data"]['group_stats']['monitor_alerts_fired'] . "</div></td>
		</tr>
		<tr>
			<td></td>
			<td colspan='6'><div class='cellBold cellCenter'>" .
				__('Last %s', human_time_description_raw($item['date']['period'])) . "</div></td>
		</tr>
		<tr>
			<td><div class='cellBold cellCenter'>" .
				__('Events') . "</div></td>
			<td colspan='6'><div class='cellBold cellCenter cellWhite cellBorder1 cellBig'>" .
				$item["data"]["count_events"]."</div></td>
		</tr>
	</table>";
}

function reporting_html_event_report_agent($table, $item) {
	global $config;
	
	$table1->width = '99%';
	
	$table1->align = array();
	$table1->align[0] = 'center';
	$table1->align[1] = 'center';
	$table1->align[3] = 'center';
	
	$table1->data = array ();
	
	$table1->head = array ();
	$table1->head[0] = __('Status');
	$table1->head[1] = __('Count');
	$table1->head[2] = __('Name');
	$table1->head[3] = __('Type');
	$table1->head[4] = __('Criticity');
	$table1->head[5] = __('Val. by');
	$table1->head[6] = __('Timestamp');
	
	foreach ($item['data'] as $i => $event) {
		$table1->cellclass[$i][1] =
		$table1->cellclass[$i][2] = 
		$table1->cellclass[$i][4] =
		$table1->cellclass[$i][5] =
		$table1->cellclass[$i][6] =
			get_priority_class ($event["criticity"]);
		
		$data = array ();
		// Colored box
		switch ($event['status']) {
			case 0:
				$img_st = "images/star.png";
				$title_st = __('New event');
				break;
			case 1:
				$img_st = "images/tick.png";
				$title_st = __('Event validated');
				break;
			case 2:
				$img_st = "images/hourglass.png";
				$title_st = __('Event in process');
				break;
		}
		$data[] = html_print_image ($img_st, true, 
			array ("class" => "image_status",
				"width" => 16,
				"title" => $title_st));
		
		$data[] = $event['count'];
		
		$data[] = ui_print_truncate_text(
			io_safe_output($event['name']),
			140, false, true);
		//$data[] = $event['event_type'];
		$data[] = events_print_type_img ($event["type"], true);
		
		$data[] = get_priority_name ($event['criticity']);
		if (empty($event['validated_by']) && $event['status'] == EVENT_VALIDATE) {
			$data[] = '<i>' . __('System') . '</i>';
		}
		else {
			$user_name = db_get_value ('fullname', 'tusuario', 'id_user', $event['validated_by']);
			$data[] = io_safe_output($user_name);
		}
		$data[] = '<font style="font-size: 6pt;">' .
			date($config['date_format'], $event['timestamp']) . '</font>';
		array_push ($table1->data, $data);
	}
	
	$table->colspan['event_list']['cell'] = 3;
	$table->cellstyle['event_list']['cell'] = 'text-align: center;';
	$table->data['event_list']['cell'] = html_print_table($table1, true);
	
	if (!empty($item['chart']['by_user_validator'])) {
		$table1 = null;
		$table1->width = '99%';
		$table1->head = array ();
		$table1->head[0] = __('Events validated by user');
		$table1->data[0][0] = $item['chart']['by_user_validator'];
		
		$table->colspan['chart_by_user_validator']['cell'] = 3;
		$table->cellstyle['chart_by_user_validator']['cell'] = 'text-align: center;';
		$table->data['chart_by_user_validator']['cell'] = html_print_table($table1, true);
	}
	
	if (!empty($item['chart']['by_criticity'])) {
		$table1 = null;
		$table1->width = '99%';
		$table1->head = array ();
		$table1->head[0] = __('Events by criticity');
		$table1->data[0][0] = $item['chart']['by_criticity'];
		
		$table->colspan['chart_by_criticity']['cell'] = 3;
		$table->cellstyle['chart_by_criticity']['cell'] = 'text-align: center;';
		$table->data['chart_by_criticity']['cell'] = html_print_table($table1, true);
	}
	
	if (!empty($item['chart']['validated_vs_unvalidated'])) {
		$table1 = null;
		$table1->width = '99%';
		$table1->head = array ();
		$table1->head[0] = __('Amount events validated');
		$table1->data[0][0] = $item['chart']['validated_vs_unvalidated'];
		
		$table->colspan['chart_validated_vs_unvalidated']['cell'] = 3;
		$table->cellstyle['chart_validated_vs_unvalidated']['cell'] = 'text-align: center;';
		$table->data['chart_validated_vs_unvalidated']['cell'] = html_print_table($table1, true);
	}
}

function reporting_html_database_serialized($table, $item) {
	
	$table1->width = '100%';
	$table1->head = array (__('Date'));
	if (!empty($item['keys'])) {
		$table1->head = array_merge($table1->head, $item['keys']);
	}
	$table1->style[0] = 'text-align: left';
	
	$table1->data = array ();
	foreach ($item['data'] as $data) {
		foreach ($data['data'] as $data_unserialied) {
			$row = array($data['date']);
			$row = array_merge($row, $data_unserialied);
			$table1->data[] = $row;
		}
	}
	
	$table->colspan['database_serialized']['cell'] = 3;
	$table->cellstyle['database_serialized']['cell'] = 'text-align: center;';
	$table->data['database_serialized']['cell'] = html_print_table($table1, true);
}

function reporting_html_group_configuration($table, $item) {
	
	$table1->width = '100%';
	$table1->head = array ();
	$table1->data = array ();
	$cell = "";
	foreach ($item['data'] as $agent) {
		$table2->width = '100%';
		$table2->data = array ();
		reporting_html_agent_configuration(&$table2, array('data' => $agent));
		
		$cell .= html_print_table($table2, true);
	}
	
	$table->colspan['group_configuration']['cell'] = 3;
	$table->cellstyle['group_configuration']['cell'] = 'text-align: center;';
	$table->data['group_configuration']['cell'] = $cell;
}

function reporting_html_network_interfaces_report($table, $item) {
	
	if ($item['error']) {
		$table->colspan['interfaces']['cell'] = 3;
		$table->cellstyle['interfaces']['cell'] = 'text-align: left;';
		$table->data['interfaces']['cell'] =
			__('The group has no agents or none of the agents has any network interface');
	}
	else {
		
		foreach ($item['data'] as $agent) {
			$table_agent = new StdCLass();
			$table_agent->width = '100%';
			$table_agent->data = array();
			$table_agent->head = array();
			$table_agent->head[0] = sprintf(__("Agent '%s'"), $agent['name']);
			$table_agent->headstyle = array();
			$table_agent->headstyle[0] = 'font-size: 16px;';
			$table_agent->style[0] = 'text-align: center';
			
			$table_agent->data['interfaces'] = "";
			
			foreach ($agent['interfaces'] as $interface) {
				$table_interface = new StdClass();
				$table_interface->width = '100%';
				$table_interface->data = array();
				$table_interface->rowstyle = array();
				$table_interface->head = array();
				$table_interface->cellstyle = array();
				$table_interface->title = sprintf(__("Interface '%s' throughput graph"),
					$interface['name']);
				$table_interface->head['ip'] = __('IP');
				$table_interface->head['mac'] = __('Mac');
				$table_interface->head['status'] = __('Actual status');
				$table_interface->style['ip'] = 'text-align: left';
				$table_interface->style['mac'] = 'text-align: left';
				$table_interface->style['status'] = 'width: 150px; text-align: center';
				
				$data = array();
				$data['ip'] = !empty($interface['ip']) ? $interface['ip'] : "--";
				$data['mac'] = !empty($interface['mac']) ? $interface['mac'] : "--";
				$data['status'] = $interface['status_image'];
				$table_interface->data['data'] = $data;
				
				if (!empty($interface['chart'])) {
					$table_interface->data['graph'] = $interface['chart'];
					$table_interface->colspan['graph'][0] = 3;
					$table_interface->cellstyle['graph'][0] = 'text-align: center;';
				}
				
				$table_agent->data['interfaces'] .= html_print_table($table_interface, true);
				$table_agent->colspan[$interface_name][0] = 3;
			}
			
			$id = uniq_id();
			
			$table->data['agents'][$id] = html_print_table($table_agent, true);
			$table->colspan[$id][0] = 3;
		}
	}
}

function reporting_html_alert_report_group($table, $item) {
	$table->colspan['alerts']['cell'] = 3;
	$table->cellstyle['alerts']['cell'] = 'text-align: left;';
	
	$table1->width = '99%';
	$table1->head = array ();
	$table1->head['agent'] = __('Agent');
	$table1->head['module'] = __('Module');
	$table1->head['template'] = __('Template');
	$table1->head['actions'] = __('Actions');
	$table1->head['fired'] = __('Fired');
	$table1->data = array ();
	foreach ($item['data'] as $alert) {
		$row = array();
		
		$row['agent'] = $alert['agent'];
		$row['module'] = $alert['module'];
		$row['template'] = $alert['template'];
		$row['actions'] = $alert['template'];
		
		$row['actions'] = '<ul class="action_list">' . "\n";
		foreach ($alert['action'] as $action) {
			$row['actions'] .= '<li>' . $action . '</li>' . "\n";
		}
		$row['actions'] .= '</ul>';
		
		$row['fired'] = '<ul style="list-style-type: disc; margin-left: 10px;">' . "\n";
		foreach ($alert['fired'] as $fired) {
			$row['fired'] .= '<li>' . $fired . '</li>' . "\n";
		}
		$row['fired'] .= '</ul>';
		
		$table1->data[] = $row;
	}
	
	$table->data['alerts']['cell'] = html_print_table($table1, true);
}

function reporting_html_alert_report_agent($table, $item) {
	$table->colspan['alerts']['cell'] = 3;
	$table->cellstyle['alerts']['cell'] = 'text-align: left;';
	
	$table1->width = '99%';
	$table1->head = array ();
	$table1->head['module'] = __('Module');
	$table1->head['template'] = __('Template');
	$table1->head['actions'] = __('Actions');
	$table1->head['fired'] = __('Fired');
	$table1->data = array ();
	foreach ($item['data'] as $alert) {
		$row = array();
		
		$row['module'] = $alert['module'];
		$row['template'] = $alert['template'];
		$row['actions'] = $alert['template'];
		
		$row['actions'] = '<ul class="action_list">' . "\n";
		foreach ($alert['action'] as $action) {
			$row['actions'] .= '<li>' . $action . '</li>' . "\n";
		}
		$row['actions'] .= '</ul>';
		
		$row['fired'] = '<ul style="list-style-type: disc; margin-left: 10px;">' . "\n";
		foreach ($alert['fired'] as $fired) {
			$row['fired'] .= '<li>' . $fired . '</li>' . "\n";
		}
		$row['fired'] .= '</ul>';
		
		$table1->data[] = $row;
	}
	
	$table->data['alerts']['cell'] = html_print_table($table1, true);
}

function reporting_html_alert_report_module($table, $item) {
	$table->colspan['alerts']['cell'] = 3;
	$table->cellstyle['alerts']['cell'] = 'text-align: left;';
	
	$table1->width = '99%';
	$table1->head = array ();
	$table1->head['template'] = __('Template');
	$table1->head['actions'] = __('Actions');
	$table1->head['fired'] = __('Fired');
	$table1->data = array ();
	foreach ($item['data'] as $alert) {
		$row = array();
		
		$row['template'] = $alert['template'];
		$row['actions'] = $alert['template'];
		
		$row['actions'] = '<ul class="action_list">' . "\n";
		foreach ($alert['action'] as $action) {
			$row['actions'] .= '<li>' . $action . '</li>' . "\n";
		}
		$row['actions'] .= '</ul>';
		
		$row['fired'] = '<ul style="list-style-type: disc; margin-left: 10px;">' . "\n";
		foreach ($alert['fired'] as $fired) {
			$row['fired'] .= '<li>' . $fired . '</li>' . "\n";
		}
		$row['fired'] .= '</ul>';
		
		$table1->data[] = $row;
	}
	
	$table->data['alerts']['cell'] = html_print_table($table1, true);
}

function reporting_html_sql_graph($table, $item) {
	$table->colspan['chart']['cell'] = 3;
	$table->cellstyle['chart']['cell'] = 'text-align: center;';
	$table->data['chart']['cell'] = $item['chart'];
}

function reporting_html_monitor_report($table, $item, $mini) {
	if ($mini) {
		$font_size = '1.5';
	}
	else {
		$font_size = '3';
	}
	
	$table->colspan['module']['cell'] = 3;
	$table->cellstyle['module']['cell'] = 'text-align: center;';
	
	$table1->width = '99%';
	$table1->head = array ();
	$table1->data = array ();
	if ($item['data']['unknown'] == 1) {
		$table1->data['data']['unknown'] =
			'<p style="font: bold ' . $font_size . 'em Arial, Sans-serif; color: ' . COL_UNKNOWN . ';">';
		$table1->data['data']['unknown'] .= __('Unknown') . "</p>";
	}
	else {
		$table1->data['data']['ok'] =
			'<p style="font: bold ' . $font_size . 'em Arial, Sans-serif; color: ' . COL_NORMAL . ';">';
		$table1->data['data']['ok'] .=
			html_print_image("images/module_ok.png", true) . ' ' .
				__('OK') . ': ' . $item['data']["ok"]["formated_value"].' %</p>';
		
		$table1->data['data']['fail'] =
			'<p style="font: bold ' . $font_size . 'em Arial, Sans-serif; color: ' . COL_CRITICAL . ';">';
		$table1->data['data']['fail'] .=
			html_print_image("images/module_critical.png", true) . ' ' .
				__('Not OK') . ': ' . $item['data']["fail"]["formated_value"] . ' % ' . '</p>';
	}
	
	$table->data['module']['cell'] = html_print_table($table1, true);
}

function reporting_html_graph($table, $item) {
	$table->colspan['chart']['cell'] = 3;
	$table->cellstyle['chart']['cell'] = 'text-align: center;';
	$table->data['chart']['cell'] = $item['chart'];
}

function reporting_html_prediction_date($table, $item, $mini) {
	reporting_html_value($table, $item, $mini, true);
}

function reporting_html_agent_configuration(&$table, $item) {
	$table->colspan['agent']['cell'] = 3;
	$table->cellstyle['agent']['cell'] = 'text-align: left;';
	
	$table1->width = '99%';
	$table1->head = array ();
	$table1->head['name'] = __('Agent name');
	$table1->head['group'] = __('Group');
	$table1->head['os'] = __('OS');
	$table1->head['address'] = __('IP');
	$table1->head['description'] = __('Description');
	$table1->head['status'] = __('Status');
	$table1->data = array ();
	$row = array();
	$row['name'] = $item['data']['name'];
	$row['group'] = $item['data']['group_icon'];
	$row['address'] = $item['data']['os_icon'];
	$row['os'] = $item['data']['address'];
	$row['description'] = $item['data']['description'];
	if ($item['data']['enabled']) {
		$row['status'] = __('Enabled');
	}
	else {
		$row['status'] = __('Disabled');
	}
	$table1->data[] = $row;
	$table->data['agent']['cell'] = html_print_table($table1, true);
	
	
	$table->colspan['modules']['cell'] = 3;
	$table->cellstyle['modules']['cell'] = 'text-align: left;';
	
	if (empty($item['data']['modules'])) {
		$table->data['modules']['cell'] = __('Empty modules');
	}
	else {
		$table1->width = '99%';
		$table1->head = array ();
		$table1->head['name'] = __('Name');
		$table1->head['type'] = __('Type');
		$table1->head['warning_critical'] = __('Warning<br/>Critical');
		$table1->head['threshold'] = __('Threshold');
		$table1->head['group_icon'] = __('Group');
		$table1->head['description'] = __('Description');
		$table1->head['interval'] = __('Interval');
		$table1->head['unit'] = __('Unit');
		$table1->head['status'] = __('Status');
		$table1->head['tags'] = __('Tags');
		$table1->align = array();
		$table1->align['name'] = 'left';
		$table1->align['type'] = 'center';
		$table1->align['warning_critical'] = 'right';
		$table1->align['threshold'] = 'right';
		$table1->align['group_icon'] = 'center';
		$table1->align['description'] = 'left';
		$table1->align['interval'] = 'right';
		$table1->align['unit'] = 'left';
		$table1->align['status'] = 'center';
		$table1->align['tags'] = 'left';
		$table1->data = array ();
		
		foreach ($item['data']['modules'] as $module) {
			$row = array();
			
			$row['name'] = $module['name'];
			$row['type'] = $module['type_icon'];
			$row['warning_critical'] =
				$module['max_warning'] . " / " . $module['min_warning'] .
				"<br>" .
				$module['max_critical'] . " / " . $module['min_critical'];
			$row['threshold'] = $module['threshold'];
			$row['group_icon'] = ui_print_group_icon($item['data']['group'], true);
			$row['description'] = $module['description'];
			$row['interval'] = $module['interval'];
			$row['unit'] = $module['unit'];
			$row['status'] = $module['status_icon'];
			$row['tags'] = implode(",", $module['tags']);
			
			$table1->data[] = $row;
		}
		
		$table->data['modules']['cell'] = html_print_table($table1, true);
	}
}

function reporting_html_TTO_value(&$table, $item, $mini, $only_value = false, $check_empty = false) {
	reporting_html_value($table, $item, $mini);
}

function reporting_html_MTBF_value(&$table, $item, $mini, $only_value = false, $check_empty = false) {
	reporting_html_value($table, $item, $mini);
}

function reporting_html_MTTR_value(&$table, $item, $mini, $only_value = false, $check_empty = false) {
	reporting_html_value($table, $item, $mini);
}

function reporting_html_sum_value(&$table, $item, $mini) {
	reporting_html_value($table, $item, $mini);
}

function reporting_html_avg_value(&$table, $item, $mini) {
	reporting_html_value($table, $item, $mini);
}

function reporting_html_max_value(&$table, $item, $mini) {
	reporting_html_value($table, $item, $mini);
}

function reporting_html_min_value(&$table, $item, $mini) {
	reporting_html_value($table, $item, $mini);
}

function reporting_html_value(&$table, $item, $mini, $only_value = false, $check_empty = false) {
	if ($mini) {
		$font_size = '1.5';
	}
	else {
		$font_size = '3';
	}
	
	$table->colspan['data']['cell'] = 3;
	$table->cellstyle['data']['cell'] = 'text-align: left;';
	
	
	$table->data['data']['cell'] = '<p style="font: bold ' . $font_size . 'em Arial, Sans-serif; color: #000000;">';
	
	if ($check_empty && empty($item['data']['value'])) {
		$table->data['data']['cell'] .=  __('Unknown');
	}
	elseif ($only_value) {
		$table->data['data']['cell'] .= $item['data']['value'];
	}
	else {
		$table->data['data']['cell'] .= $item['data']['formated_value'];
	}
	
	$table->data['data']['cell'] .= '</p>';
}

function reporting_html_url(&$table, $item, $key) {
	$table->colspan['data']['cell'] = 3;
	$table->cellstyle['data']['cell'] = 'text-align: left;';
	$table->data['data']['cell'] = '
		<iframe id="item_' . $key . '" src ="' . $item["url"] . '" width="100%" height="100%">
		</iframe>';
	// TODO: make this dynamic and get the height if the iframe to resize this item
	$table->data['data']['cell'] .= '
		<script type="text/javascript">
			$(document).ready (function () {
				$("#item_' . $key . '").height(500);
			});
		</script>';
}

function reporting_html_text(&$table, $item) {
	$table->colspan['data']['cell'] = 3;
	$table->cellstyle['data']['cell'] = 'text-align: left;';
	$table->data['data']['cell'] = $item['data'];
}

function reporting_html_availability(&$table, $item) {
	
	if (!empty($item["data"])) {
		$table1->width = '99%';
		$table1->data = array ();
		$table1->head = array ();
		$table1->head[0] = __('Agent');
		// HACK it is saved in show_graph field.
		// Show interfaces instead the modules
		if ($item['kind_availability'] == 'address') {
			$table1->head[1] = __('IP Address');
		}
		else {
			$table1->head[1] = __('Module');
		}
		$table1->head[2] = __('# Checks');
		$table1->head[3] = __('# Failed');
		$table1->head[4] = __('% Fail');
		$table1->head[5] = __('Poling time');
		$table1->head[6] = __('Time unavailable');
		$table1->head[7] = __('% Ok');
		
		$table1->style[0] = 'text-align: left';
		$table1->style[1] = 'text-align: left';
		$table1->style[2] = 'text-align: right';
		$table1->style[3] = 'text-align: right';
		$table1->style[4] = 'text-align: right';
		$table1->style[5] = 'text-align: right';
		$table1->style[6] = 'text-align: right';
		$table1->style[7] = 'text-align: right';
		
		foreach ($item['data'] as $row) {
			$table_row = array();
			$table_row[] = $row['agent'];
			$table_row[] = $row['availability_item'];
			$table_row[] = $row['checks'];
			$table_row[] = $row['failed'];
			$table_row[] = $row['fail'];
			$table_row[] = $row['poling_time'];
			$table_row[] = $row['time_unavaliable'];
			$table_row[] = $row['ok'];
			
			$table1->data[] = $table_row;
		}
	}
	else {
		$table->colspan['error']['cell'] = 3;
		$table->data['error']['cell'] =
			__('There are no Agent/Modules defined');
	}
	
	$table->colspan[1][0] = 3;
	$data = array();
	$data[0] = html_print_table($table1, true);
	array_push ($table->data, $data);
	
	if ($item['resume'] && !empty($item["data"])) {
		$table1->width = '99%';
		$table1->data = array ();
		$table1->head = array ();
		$table1->style = array();
		$table1->head['min_text'] = '';
		$table1->head['min'] = __('Min Value');
		$table1->head['avg'] = __('Average Value');
		$table1->head['max_text'] = '';
		$table1->head['max'] = __('Max Value');
		$table1->style['min_text'] = 'text-align: left';
		$table1->style['min'] = 'text-align: right';
		$table1->style['avg'] = 'text-align: right';
		$table1->style['max_text'] = 'text-align: left';
		$table1->style['max'] = 'text-align: right';
		
		$table1->data[] = array(
			'min_text' => $item['resume']['min_text'],
			'min' => format_numeric($item['resume']['min'], 2) . "%",
			'avg' => format_numeric($item['resume']['avg'], 2) . "%",
			'max_text' => $item['resume']['max_text'],
			'max' => format_numeric($item['resume']['max'], 2) . "%"
			);
		
		$table->colspan[2][0] = 3;
		$data = array();
		$data[0] = html_print_table($table1, true);
		array_push ($table->data, $data);
	}
}

function reporting_html_general(&$table, $item) {
	
	if (!empty($item["data"])) {
		switch ($item['subtype']) {
			case REPORT_GENERAL_NOT_GROUP_BY_AGENT:
				$table1->width = '99%';
				$table1->data = array ();
				$table1->head = array ();
				$table1->head[0] = __('Agent');
				$table1->head[1] = __('Module');
				if ($item['date']['period'] != 0) {
					$table1->head[2] = __('Operation');
				}
				$table1->head[3] = __('Value');
				$table1->style[0] = 'text-align: left';
				$table1->style[1] = 'text-align: left';
				$table1->style[2] = 'text-align: left';
				$table1->style[3] = 'text-align: left';
				
				foreach ($item['data'] as $row) {
					$table1->data[] = array(
						$row['agent'],
						$row['module'],
						$row['value']);
				}
				break;
			case REPORT_GENERAL_GROUP_BY_AGENT:
				$list_modules = array();
				foreach ($item['data'] as $modules) {
					foreach ($modules as $name => $value) {
						$list_modules[$name] = null;
					}
				}
				$list_modules = array_keys($list_modules);
				
				$table1->width = '99%';
				$table1->data = array ();
				$table1->head = array_merge(array(__('Agent')), $list_modules);
				foreach ($item['data'] as $agent => $modules) {
					$row = array();
					
					$row['agent'] = $agent;
					$table1->style['agent'] = 'text-align: left;';
					foreach ($list_modules as $name) {
						$table1->style[$name] = 'text-align: right;';
						if (isset($modules[$name])) {
							$row[$name] = $value;
						}
						else {
							$row[$name] = "--";
						}
					}
					$table1->data[] = $row;
				}
				break;
		}
		
		$table->colspan['data']['cell'] = 3;
		$table->cellstyle['data']['cell'] = 'text-align: center;';
		$table->data['data']['cell'] = html_print_table($table1, true);
	}
	else {
		$table->colspan['error']['cell'] = 3;
		$table->data['error']['cell'] =
			__('There are no Agent/Modules defined');
	}
	
	if ($item['resume'] && !empty($item["data"])) {
		$table_summary->width = '99%';
		
		$table_summary->data = array ();
		$table_summary->head = array ();
		$table_summary->head_colspan = array ();
		$table_summary->align = array();
		
		$table_summary->align[0] = 'left';
		$table_summary->align[1] = 'right';
		$table_summary->align[2] = 'right';
		$table_summary->align[3] = 'left';
		$table_summary->align[4] = 'right';
		
		$table_summary->head_colspan[0] = 2;
		$table_summary->head[0] = __('Min Value');
		$table_summary->head[1] = __('Average Value');
		$table_summary->head_colspan[2] = 2;
		$table_summary->head[2] = __('Max Value');
		
		$table_summary->data[0][0] = $item['min']['agent'] . ' - ' . $item['min']['module'];
		$table_summary->data[0][1] = $item['min']['formated_value'];
		$table_summary->data[0][2] = format_for_graph($item['avg_value'], 2);
		$table_summary->data[0][3] = $item['max']['agent'] . ' - ' . $item['max']['module'];
		$table_summary->data[0][4] = $item['max']['formated_value'];
		
		$table->colspan['summary_title']['cell'] = 3;
		$table->data['summary_title']['cell'] = '<b>' . __('Summary') . '</b>';
		$table->colspan['summary_table']['cell'] = 3;
		$table->data['summary_table']['cell'] = html_print_table($table_summary, true);
	}
}

function reporting_html_sql(&$table, $item) {
	if (!$item['correct']) {
		$table->colspan['error']['cell'] = 3;
		$table->data['error']['cell'] = $item['error'];
	}
	else {
		$first = true;
		
		$table2->class = 'databox';
		$table2->width = '100%';
		
		foreach ($item['data'] as $row) {
			if ($first) {
				$first = false;
				
				// Print the header
				foreach ($row as $key => $value) {
					$table2->head[] = $key;
				}
			}
			
			$table2->data[] = $row;
		}
		
		$table->colspan['data']['cell'] = 3;
		$table->cellstyle['data']['cell'] = 'text-align: center;';
		$table->data['data']['cell'] = html_print_table($table2, true);
	}
}


/** 
 * Get the maximum value of an agent module in a period of time.
 * 
 * @param int Agent module id to get the maximum value.
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The maximum module value in the interval.
 */
function reporting_get_agentmodule_data_max ($id_agent_module, $period=0, $date = 0) {
	global $config;
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	$datelimit = $date - $period;
	
	$search_in_history_db = db_search_in_history_db($datelimit);
	
	$id_module_type = modules_get_agentmodule_type ($id_agent_module);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT *
		FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
			' AND utimestamp > ' . (int) $datelimit .
			' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Uncompressed module data
	if ($uncompressed_module) {
	
	// Compressed module data
	}
	else {
		// Get previous data
		$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($interval_data, $previous_data);
		}
		
		// Get next data
		$next_data = modules_get_next_data ($id_agent_module, $date);
		if ($next_data !== false) {
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		else if (count ($interval_data) > 0) {
			// Propagate the last known data to the end of the interval
			$next_data = array_pop ($interval_data);
			array_push ($interval_data, $next_data);
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
	}
	
	// Set initial conditions
	if (empty($iterval_data)) {
		$max = 0;
	}
	else {
		if ($uncompressed_module || $interval_data[0]['utimestamp'] == $datelimit) {
			$max = $interval_data[0]['datos'];
		}
		else {
			$max = 0;
		}
	}
	
	foreach ($interval_data as $data) {
		if ($data['datos'] > $max) {
			$max = $data['datos'];
		}
	}
	
	return $max;
}

/** 
 * Get the minimum value of an agent module in a period of time.
 * 
 * @param int Agent module id to get the minimum value.
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values in Unix time. Default current time.
 * 
 * @return float The minimum module value of the module
 */
function reporting_get_agentmodule_data_min ($id_agent_module, $period=0, $date = 0) {
	global $config;
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	$datelimit = $date - $period;

	$search_in_history_db = db_search_in_history_db($datelimit);
	
	$id_module_type = modules_get_agentmodule_type ($id_agent_module);
	$module_type = modules_get_moduletype_name ($id_module_type);
	$uncompressed_module = is_module_uncompressed ($module_type);
	
	// Get module data
	$interval_data = db_get_all_rows_sql ('SELECT *
		FROM tagente_datos 
		WHERE id_agente_modulo = ' . (int) $id_agent_module .
			' AND utimestamp > ' . (int) $datelimit .
			' AND utimestamp < ' . (int) $date .
		' ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Uncompressed module data
	if ($uncompressed_module) {
		$min_necessary = 1;
	
	// Compressed module data
	}
	else {
		// Get previous data
		$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($interval_data, $previous_data);
		}
	
		// Get next data
		$next_data = modules_get_next_data ($id_agent_module, $date);
		if ($next_data !== false) {
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		else if (count ($interval_data) > 0) {
			// Propagate the last known data to the end of the interval
			$next_data = array_pop ($interval_data);
			array_push ($interval_data, $next_data);
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
	}
	
	if (count ($interval_data) < 1) {
		return false;
	}
	
	// Set initial conditions
	$min = $interval_data[0]['datos'];
	
	foreach ($interval_data as $data) {
		if ($data['datos'] < $min) {
			$min = $data['datos'];
		}
	}
	
	return $min;
}

/** 
 * Get the sum of values of an agent module in a period of time.
 * 
 * @param int Agent module id to get the sumatory.
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return float The sumatory of the module values in the interval.
 */
function reporting_get_agentmodule_data_sum ($id_agent_module,
	$period = 0, $date = 0) {
	
	global $config;
	
	// Initialize variables
	if (empty ($date)) $date = get_system_time ();
	$datelimit = $date - $period;
	
	$search_in_history_db = db_search_in_history_db($datelimit);
	
	$id_module_type = db_get_value ('id_tipo_modulo', 'tagente_modulo',
		'id_agente_modulo', $id_agent_module);
	$module_name = db_get_value ('nombre', 'ttipo_modulo', 'id_tipo',
		$id_module_type);
	$module_interval = modules_get_interval ($id_agent_module);
	$uncompressed_module = is_module_uncompressed ($module_name);
	
	// Wrong module type
	if (is_module_data_string ($module_name)) {
		return 0;
	}
	
	// Incremental modules are treated differently
	$module_inc = is_module_inc ($module_name);
	
	// Get module data
	$interval_data = db_get_all_rows_sql('
			SELECT * FROM tagente_datos 
			WHERE id_agente_modulo = ' . (int) $id_agent_module . '
				AND utimestamp > ' . (int) $datelimit . '
				AND utimestamp < ' . (int) $date . '
			ORDER BY utimestamp ASC', $search_in_history_db);
	if ($interval_data === false) $interval_data = array ();
	
	// Uncompressed module data
	if ($uncompressed_module) {
		$min_necessary = 1;
	
	// Compressed module data
	}
	else {
		// Get previous data
		$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($interval_data, $previous_data);
		}
		
		// Get next data
		$next_data = modules_get_next_data ($id_agent_module, $date);
		if ($next_data !== false) {
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		else if (count ($interval_data) > 0) {
			// Propagate the last known data to the end of the interval
			$next_data = array_pop ($interval_data);
			array_push ($interval_data, $next_data);
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		
		$min_necessary = 2;
	}
	
	if (count ($interval_data) < $min_necessary) {
		return false;
	}
	
	// Set initial conditions
	$total = 0;
	if (! $uncompressed_module) {
		$previous_data = array_shift ($interval_data);
	}
	
	foreach ($interval_data as $data) {
		if ($uncompressed_module) {
			$total += $data['datos'];
		}
		else if ($module_inc) {
			$total += $previous_data['datos'] * ($data['utimestamp'] - $previous_data['utimestamp']);
		}
		else {
			$total += $previous_data['datos'] * ($data['utimestamp'] - $previous_data['utimestamp']) / $module_interval;
		}
		$previous_data = $data;
	}
	
	return $total;
}

/** 
 * Get SLA of a module.
 * 
 * @param int Agent module to calculate SLA
 * @param int Period to check the SLA compliance.
 * @param int Minimum data value the module in the right interval
 * @param int Maximum data value the module in the right interval. False will
 * ignore max value
 * @param int Beginning date of the report in UNIX time (current date by default).
 * @param array $dayWeek  Array of days week to extract as array('monday' => false, 'tuesday' => true....), and by default is null.
 * @param string $timeFrom Time in the day to start to extract in mysql format, by default null.
 * @param string $timeTo Time in the day to end to extract in mysql format, by default null.
 * 
 * @return float SLA percentage of the requested module. False if no data were
 * found
 */
function reporting_get_agentmodule_sla_day ($id_agent_module, $period = 0, $min_value = 1, $max_value = false, $date = 0, $daysWeek = null, $timeFrom = null, $timeTo = null) {
	global $config;
	
	if (empty($id_agent_module))
		return false;
	
	// Initialize variables
	if (empty ($date)) {
		$date = get_system_time ();
	}
	if ($daysWeek === null) {
		$daysWeek = array();
	}
	// Limit date to start searching data
	$datelimit = $date - $period;
	
	$search_in_history_db = db_search_in_history_db($datelimit);
	
	// Get interval data
	$sql = sprintf ('SELECT *
		FROM tagente_datos
		WHERE id_agente_modulo = %d
			AND utimestamp > %d AND utimestamp <= %d',
		$id_agent_module, $datelimit, $date);
	
	//Add the working times (mon - tue - wed ...) and from time to time
	$days = array();
	//Translate to mysql week days
	if ($daysWeek) {
		foreach ($daysWeek as $key => $value) {
			if (!$value) {
				if ($key == 'monday') {
					$days[] = 2;
				}
				if ($key == 'tuesday') {
					$days[] = 3;
				}
				if ($key == 'wednesday') {
					$days[] = 4;
				}
				if ($key == 'thursday') {
					$days[] = 5;
				}
				if ($key == 'friday') {
					$days[] = 6;
				}
				if ($key == 'saturday') {
					$days[] = 7;
				}
				if ($key == 'sunday') {
					$days[] = 1;
				}
			}
		}
	}
	
	if (count($days) > 0) {
		$sql .= ' AND DAYOFWEEK(FROM_UNIXTIME(utimestamp)) NOT IN (' . implode(',', $days) . ')';
	}
	
	if ($timeFrom < $timeTo) {
		$sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" AND TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
	}
	elseif ($timeFrom > $timeTo) {
		$sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" OR TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
	}
	$sql .= ' ORDER BY utimestamp ASC';
	$interval_data = db_get_all_rows_sql ($sql, $search_in_history_db);
	
	if ($interval_data === false) {
		$interval_data = array ();
	}
	
	// Calculate planned downtime dates
	$downtime_dates = reporting_get_planned_downtimes_intervals($id_agent_module, $datelimit, $date);
	
	// Get previous data
	$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
	
	if ($previous_data !== false) {
		$previous_data['utimestamp'] = $datelimit;
		array_unshift ($interval_data, $previous_data);
	}
	
	// Get next data
	$next_data = modules_get_next_data ($id_agent_module, $date);
	
	if ($next_data !== false) {
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	else if (count ($interval_data) > 0) {
		// Propagate the last known data to the end of the interval
		$next_data = array_pop ($interval_data);
		array_push ($interval_data, $next_data);
		$next_data['utimestamp'] = $date;
		array_push ($interval_data, $next_data);
	}
	
	if (count ($interval_data) < 2) {
		return false;
	}
	
	// Set initial conditions
	$bad_period = 0;
	$first_data = array_shift ($interval_data);
	
	// Do not count the empty start of an interval as 0
	if ($first_data['utimestamp'] != $datelimit) {
		$period = $date - $first_data['utimestamp'];
	}
	
	$previous_utimestamp = $first_data['utimestamp'];
	if ((($max_value > $min_value AND ($first_data['datos'] > $max_value OR $first_data['datos'] < $min_value))) OR
		($max_value <= $min_value AND $first_data['datos'] < $min_value)) {
		
		$previous_status = 1;
		foreach ($downtime_dates as $date_dt) {
			if (($date_dt['date_from'] <= $previous_utimestamp) AND ($date_dt['date_to'] >= $previous_utimestamp)) {
				$previous_status = 0;
			}
		}
	}
	else {
		$previous_status = 0;
	}
	
	
	
	foreach ($interval_data as $data) {
		// Previous status was critical
		if ($previous_status == 1) {
			$bad_period += $data['utimestamp'] - $previous_utimestamp;
		}
		
		if (array_key_exists('datos', $data)) {
			// Re-calculate previous status for the next data
			if ((($max_value > $min_value AND ($data['datos'] > $max_value OR $data['datos'] < $min_value))) OR
				($max_value <= $min_value AND $data['datos'] < $min_value)) {
				
				$previous_status = 1;
				foreach ($downtime_dates as $date_dt) {
					if (($date_dt['date_from'] <= $data['utimestamp']) AND ($date_dt['date_to'] >= $data['utimestamp'])) {
						$previous_status = 0;
					}
				}
			}
			else {
				$previous_status = 0;
			}
		}
		
		$previous_utimestamp = $data['utimestamp'];
	}
	
	// Return the percentage of SLA compliance
	return (float) (100 - ($bad_period / $period) * 100);
}

/** 
 * Get SLA of a module.
 * 
 * @param int Agent module to calculate SLA
 * @param int Period to check the SLA compliance.
 * @param int Minimum data value the module in the right interval
 * @param int Maximum data value the module in the right interval. False will
 * ignore max value
 * @param int Beginning date of the report in UNIX time (current date by default).
 * @param array $dayWeek  Array of days week to extract as array('monday' => false, 'tuesday' => true....), and by default is null.
 * @param string $timeFrom Time in the day to start to extract in mysql format, by default null.
 * @param string $timeTo Time in the day to end to extract in mysql format, by default null.
 * 
 * @return float SLA percentage of the requested module. False if no data were
 * found
 */
function reporting_get_agentmodule_sla ($id_agent_module, $period = 0,
	$min_value = 1, $max_value = false, $date = 0, $daysWeek = null,
	$timeFrom = null, $timeTo = null) {
	
	global $config;
	
	if (empty($id_agent_module))
		return false;
	
	// Set initial conditions
	$bad_period = 0;
	// Limit date to start searching data
	$datelimit = $date - $period;
	$search_in_history_db = db_search_in_history_db($datelimit);
	
	// Initialize variables
	if (empty ($date)) {
		$date = get_system_time ();
	}
	if ($daysWeek === null) {
		$daysWeek = array();
	}
	
	
	// Calculate the SLA for large time without hours
	if ($timeFrom == $timeTo) {
		// Get interval data
		$sql = sprintf ('SELECT *
			FROM tagente_datos
			WHERE id_agente_modulo = %d
				AND utimestamp > %d AND utimestamp <= %d',
			$id_agent_module, $datelimit, $date);
		
		//Add the working times (mon - tue - wed ...) and from time to time
		$days = array();
		//Translate to mysql week days
		if ($daysWeek) {
			foreach ($daysWeek as $key => $value) {
				if (!$value) {
					if ($key == 'monday') {
						$days[] = 2;
					}
					if ($key == 'tuesday') {
						$days[] = 3;
					}
					if ($key == 'wednesday') {
						$days[] = 4;
					}
					if ($key == 'thursday') {
						$days[] = 5;
					}
					if ($key == 'friday') {
						$days[] = 6;
					}
					if ($key == 'saturday') {
						$days[] = 7;
					}
					if ($key == 'sunday') {
						$days[] = 1;
					}
				}
			}
		}
		
		if (count($days) > 0) {
			$sql .= ' AND DAYOFWEEK(FROM_UNIXTIME(utimestamp)) NOT IN (' . implode(',', $days) . ')';
		}
		
		$sql .= "\n";
		$sql .= ' ORDER BY utimestamp ASC';
		$interval_data = db_get_all_rows_sql ($sql, $search_in_history_db);
		
		if ($interval_data === false) {
			$interval_data = array ();
		}
		
		// Calculate planned downtime dates
		$downtime_dates = reporting_get_planned_downtimes_intervals($id_agent_module, $datelimit, $date);
		
		// Get previous data
		$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
		
		if ($previous_data !== false) {
			$previous_data['utimestamp'] = $datelimit;
			array_unshift ($interval_data, $previous_data);
		}
		
		// Get next data
		$next_data = modules_get_next_data ($id_agent_module, $date);
		
		if ($next_data !== false) {
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		else if (count ($interval_data) > 0) {
			// Propagate the last known data to the end of the interval
			$next_data = array_pop ($interval_data);
			array_push ($interval_data, $next_data);
			$next_data['utimestamp'] = $date;
			array_push ($interval_data, $next_data);
		}
		
		if (count ($interval_data) < 2) {
			return false;
		}
		
		
		$first_data = array_shift ($interval_data);
		
		// Do not count the empty start of an interval as 0
		if ($first_data['utimestamp'] != $datelimit) {
			$period = $date - $first_data['utimestamp'];
		}
		
		$previous_utimestamp = $first_data['utimestamp'];
		if ((($max_value > $min_value AND ($first_data['datos'] > $max_value OR $first_data['datos'] < $min_value))) OR
			($max_value <= $min_value AND $first_data['datos'] < $min_value)) {
			
			$previous_status = 1;
			foreach ($downtime_dates as $date_dt) {
				if (($date_dt['date_from'] <= $previous_utimestamp) AND ($date_dt['date_to'] >= $previous_utimestamp)) {
					$previous_status = 0;
				}
			}
		}
		else {
			$previous_status = 0;
		}
		
		
		
		
		
		foreach ($interval_data as $data) {
			// Previous status was critical
			if ($previous_status == 1) {
				$bad_period += $data['utimestamp'] - $previous_utimestamp;
			}
			
			if (array_key_exists('datos', $data)) {
				// Re-calculate previous status for the next data
				if ((($max_value > $min_value AND ($data['datos'] > $max_value OR $data['datos'] < $min_value))) OR
					($max_value <= $min_value AND $data['datos'] < $min_value)) {
					
					$previous_status = 1;
					foreach ($downtime_dates as $date_dt) {
						if (($date_dt['date_from'] <= $data['utimestamp']) AND ($date_dt['date_to'] >= $data['utimestamp'])) {
							$previous_status = 0;
						}
					}
				}
				else {
					$previous_status = 0;
				}
			}
			
			$previous_utimestamp = $data['utimestamp'];
		}
		
		// Return the percentage of SLA compliance
		return (float) (100 - ($bad_period / $period) * 100);
	}
	elseif ($period <= SECONDS_1DAY) {
		
		
		return reporting_get_agentmodule_sla_day(
			$id_agent_module,
			$period,
			$min_value,
			$max_value,
			$date,
			$daysWeek,
			$timeFrom,
			$timeTo);
	}
	else {
		// Extract the data each day
		
		$sla = 0;
		
		$i = 0;
		for ($interval = 0; $interval <= $period; $interval = $interval + SECONDS_1DAY) {
			$datelimit = $date - $interval;
			
			$sla_day = reporting_get_agentmodule_sla(
				$id_agent_module,
				SECONDS_1DAY,
				$min_value,
				$max_value,
				$datelimit + $interval,
				$daysWeek,
				$timeFrom, $timeTo);
			
			
			$sla += $sla_day;
			$i++;
		}
		
		$sla = $sla / $i;
		
		return $sla;
	}
}

/** 
 * Get several SLA data for an agentmodule within a period divided on subperiods
 * 
 * @param int Agent module to calculate SLA
 * @param int Period to check the SLA compliance.
 * @param int Minimum data value the module in the right interval
 * @param int Maximum data value the module in the right interval. False will
 * ignore max value
 * @param array $days Array of days week to extract as array('monday' => false, 'tuesday' => true....), and by default is null.
 * @param string $timeFrom Time in the day to start to extract in mysql format, by default null.
 * @param string $timeTo Time in the day to end to extract in mysql format, by default null.
 * 
 * @return Array with values either 1, 2, 3 or 4 depending if the SLA percentage for this subperiod
 * is within the sla limits, on the edge, outside or with an unknown value.
 */
function reporting_get_agentmodule_sla_array ($id_agent_module, $period = 0, $min_value = 1, $max_value = false, $date = 0, $daysWeek = null, $timeFrom = null, $timeTo = null) {
	global $config;
	
	if (empty($id_agent_module))
		return false;
	
	// Initialize variables
	if (empty ($date)) {
		$date = get_system_time ();
	}
	if ($daysWeek === null) {
		$daysWeek = array();
	}

	// Hotfix: The edge values are confuse to the users
	$percent = 0;
	
	// Limit date to start searching data
	$datelimit = $date - $period;

	$search_in_history_db = db_search_in_history_db($datelimit);
	
	// Get interval data
	$sql = sprintf ('SELECT * FROM tagente_datos
		WHERE id_agente_modulo = %d
			AND utimestamp > %d AND utimestamp <= %d',
		$id_agent_module, $datelimit, $date);
	
	//Add the working times (mon - tue - wed ...) and from time to time
	$days = array();
	//Translate to mysql week days
	
	if ($daysWeek) {
		foreach ($daysWeek as $key => $value) {
			if (!$value) {
				if ($key == 'monday') {
					$days[] = 2;
				}
				if ($key == 'tuesday') {
					$days[] = 3;
				}
				if ($key == 'wednesday') {
					$days[] = 4;
				}
				if ($key == 'thursday') {
					$days[] = 5;
				}
				if ($key == 'friday') {
					$days[] = 6;
				}
				if ($key == 'saturday') {
					$days[] = 7;
				}
				if ($key == 'sunday') {
					$days[] = 1;
				}
			}
		}
	}
	
	if (count($days) > 0) {
		$sql .= ' AND DAYOFWEEK(FROM_UNIXTIME(utimestamp)) NOT IN (' . implode(',', $days) . ')';
	}
	
	if ($timeFrom != $timeTo) {
		if ($timeFrom < $timeTo) {
			$sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" AND TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
		}
		elseif ($timeFrom > $timeTo) {
			$sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" OR TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
		}
	}
	
	$sql .= ' ORDER BY utimestamp ASC';
	$interval_data = db_get_all_rows_sql ($sql, $search_in_history_db);
	
	if ($interval_data === false) {
		$interval_data = array ();
	}
	
	
	// Indexing data
	$interval_data_indexed = array();
	foreach($interval_data as $idata) {
		$interval_data_indexed[$idata['utimestamp']]['data'] = $idata['datos'];
	}
	
	//-----------Calculate unknown status events------------------------
	$events_unknown = db_get_all_rows_filter ('tevento',
		array ('id_agentmodule' => $id_agent_module,
			"utimestamp > $datelimit",
			"utimestamp < $date",
			'order' => 'utimestamp ASC'),
		array ('id_evento', 'evento', 'timestamp', 'utimestamp', 'event_type'));
	
	if ($events_unknown === false) {
		$events_unknown = array ();
	}
	
	// Add unknown periods to data
	for ($i = 0; isset($events_unknown[$i]); $i++) {
		$eu = $events_unknown[$i];
		
		if ($eu['event_type'] == 'going_unknown') {
			$interval_data_indexed[$eu['utimestamp']]['data'] = 0;
			$interval_data_indexed[$eu['utimestamp']]['status'] = 4;
			
			// Search the corresponding recovery event.
			for ($j = $i+1; isset($events_unknown[$j]); $j++) {
				$eu = $events_unknown[$j];
				
				if ($eu['event_type'] != 'going_unknown' && substr ($eu['event_type'], 0, 5) == 'going') {
					$interval_data_indexed[$eu['utimestamp']]['data'] = 0;
					$interval_data_indexed[$eu['utimestamp']]['status'] = 6;
					
					// Do not process read events again.
					$i = $j;
					break;
				}
			}
		}
	}
	
	// Get the last event before inverval to know if graph start on unknown
	$prev_event = db_get_row_filter ('tevento',
		array ('id_agentmodule' => $id_agent_module,
			"utimestamp <= $datelimit",
			'order' => 'utimestamp DESC'));
	if (isset($prev_event['event_type']) && $prev_event['event_type'] == 'going_unknown') {
		$start_unknown = true;
	}
	else {
		$start_unknown = false;
	}
	//------------------------------------------------------------------
	
	//-----------------Set limits of the interval-----------------------
	// Get previous data (This adds the first data if the begin of module data is after the begin time interval)
	$previous_data = modules_get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data !== false ) {
		$previous_value = $previous_data['datos'];
		// if ((($previous_value > ($min_value - $percent)) && ($previous_value < ($min_value + $percent))) || 
		// 		(($previous_value > ($max_value - $percent)) && ($previous_value < ($max_value + $percent)))) {//2 when value is within the edges
		// 	$previous_known_status = 2;
		// }
		// else
		if (($previous_value >= ($min_value + $percent)) && ($previous_value <= ($max_value - $percent))) { //1 when value is OK
			$previous_known_status = 1;
		}
		elseif (($previous_value <= ($min_value - $percent)) || ($previous_value >= ($max_value + $percent))) { //3 when value is Wrong
			$previous_known_status = 3;
		}
	}

	// If the starting of the graph is unknown we set it
	if ($start_unknown) {
		$interval_data_indexed[$datelimit]['data'] = 0;
		$interval_data_indexed[$datelimit]['status'] = 4;
	}
	else {
		if ($previous_data !== false ) {
			$interval_data_indexed[$datelimit]['data'] = $previous_data['datos'];
		}
		else { // If there are not data befor interval set unknown
			$interval_data_indexed[$datelimit]['data'] = 0;
			$interval_data_indexed[$datelimit]['status'] = 4;
			$previous_known_status = 1; // Assume the module was in normal status if there is no previous data.
		}
	}
	
	// Get next data (This adds data before the interval of the report)
	$next_data = modules_get_next_data ($id_agent_module, $date);
	
	if ($next_data !== false) {
		$interval_data_indexed[$date]['data'] = $previous_data['datos'];
	}
	else if (count ($interval_data_indexed) > 0) {
		// Propagate the last known data to the end of the interval (if there is no module data at the end point)
		ksort($interval_data_indexed);
		$last_data = end($interval_data_indexed);
		$interval_data_indexed[$date] = $last_data;
	}
	
	//------------------------------------------------------------------
	
	//--------Calculate planned downtime dates--------------------------
	$downtime_dates = reporting_get_planned_downtimes_intervals($id_agent_module, $datelimit, $date);

	foreach ($downtime_dates as $downtime_date) {
		// Delete data of the planned downtime and put the last data on the upper limit
		$interval_data_indexed[$downtime_date['date_from']]['data'] = 0;
		$interval_data_indexed[$downtime_date['date_from']]['status'] = 5;
		$interval_data_indexed[$downtime_date['date_to']]['data'] = 0;
		$interval_data_indexed[$downtime_date['date_to']]['status'] = 4;
		
		$last_downtime_data = false;
		foreach ($interval_data_indexed as $idi_timestamp => $idi) {
			if ($idi_timestamp != $downtime_date['date_from'] && $idi_timestamp != $downtime_date['date_to'] && 
					$idi_timestamp >= $downtime_date['date_from'] && $idi_timestamp <= $downtime_date['date_to']) {
				$last_downtime_data = $idi['data'];
				unset($interval_data_indexed[$idi_timestamp]);
			}
		}
		
		// Set the last data of the interval as limit
		if ($last_downtime_data !== false) {
			$interval_data_indexed[$downtime_date['date_to']]['data'] = $last_downtime_data;
		}
	}
	//------------------------------------------------------------------
	
	// Sort the array
	ksort($interval_data_indexed);
	
	// We need more or equal two points
	if (count ($interval_data_indexed) < 2) {
		return false;
	}
	
	//Get the percentage for the limits
	$diff = $max_value - $min_value; 
	
	// Get module type
	$id_module_type = db_get_value('id_tipo_modulo', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
	// If module is boolean don't create translation intervals (on the edge intervals)
	// if ($id_module_type == 2 or $id_module_type == 6 or $id_module_type == 9 or $id_module_type == 18){
	//      $percent = 0;
	// }
	// else {
	//      // Getting 10% of $diff --> $percent = ($diff/100)*10, so...
	//      $percent = $diff / 10;
	// }
	
	//Set initial conditions
	$first_data = array_shift ($interval_data);
	$previous_utimestamp = $date - $period;
	
	$previous_value = $first_data ['datos'];
	$previous_status = 0;
	
	if (isset($first_data['status'])) {
		// 4 for the Unknown value and 5 for planned downtime
		$previous_status = $first_data['status'];
	}
	// elseif ((($previous_value > ($min_value - $percent)) && ($previous_value < ($min_value + $percent))) || 
	// 		(($previous_value > ($max_value - $percent)) && ($previous_value < ($max_value + $percent)))) {//2 when value is within the edges
	// 	$previous_status = 2;
	// }
	elseif (($previous_value >= ($min_value + $percent)) && ($previous_value <= ($max_value - $percent))) { //1 when value is OK
		$previous_status = 1;
	}
	elseif (($previous_value <= ($min_value - $percent)) || ($previous_value >= ($max_value + $percent))) { //3 when value is Wrong
		$previous_status = 3;
	}
	
	$data_colors = array();
	$i = 0;
	
	foreach ($interval_data_indexed as $utimestamp => $data) {
		$change = false;
		$value = $data['data'];
		if (isset($data['status'])) {
			// Leaving unkown status.
			if ($data['status'] == 6) {
				$status = $previous_known_status;
			}
			// 4 unknown, 5 planned downtime.
			else {
				$status = $data['status'];
			}
		}
		// elseif ((($value > ($min_value - $percent)) && ($value < ($min_value + $percent))) || 
		// 		(($value > ($max_value - $percent)) && ($value < ($max_value + $percent)))) { //2 when value is within the edges
		// 	$status = 2;
		// }
		elseif (($value >= ($min_value + $percent)) && ($value <= ($max_value - $percent))) { //1 when value is OK
			$status = 1;
		}
		elseif (($value <= ($min_value - $percent)) || ($value >= ($max_value + $percent))) { //3 when value is Wrong
			$status = 3;
		}
		
		if ($status != $previous_status) {
			$change = true;
			$data_colors[$i]['data'] = $previous_status;
			$data_colors[$i]['utimestamp'] = $utimestamp - $previous_utimestamp;
			$i++;
			$previous_status = $status;
			$previous_utimestamp = $utimestamp;
		}
		
		// Save the last known status.
		if ($status <= 3) {
			$previous_known_status = $status;
		}
	}
	if ($change == false) {
		$data_colors[$i]['data'] = $previous_status;
		$data_colors[$i]['utimestamp'] = $date - $previous_utimestamp;
	}
	
	return $data_colors;
}

/** 
 * Get the time intervals where an agentmodule is affected by the planned downtimes.
 * 
 * @param int Agent module to calculate planned downtimes intervals.
 * @param int Start date in utimestamp.
 * @param int End date in utimestamp.
 * @param bool Whether ot not to get the planned downtimes that affect the service associated with the agentmodule.
 * 
 * @return Array with time intervals.
 */
function reporting_get_planned_downtimes_intervals ($id_agent_module, $start_date, $end_date, $check_services = false) {
	global $config;

	if (empty($id_agent_module))
		return false;

	require_once ($config['homedir'] . '/include/functions_planned_downtimes.php');

	$malformed_planned_downtimes = planned_downtimes_get_malformed();
	if (empty($malformed_planned_downtimes))
		$malformed_planned_downtimes = array();

	$sql_downtime = "SELECT DISTINCT(tpdr.id), tpdr.*
					FROM (
							SELECT tpd.*
							FROM tplanned_downtime tpd, tplanned_downtime_agents tpda, tagente_modulo tam
							WHERE tpd.id = tpda.id_downtime
								AND tpda.all_modules = 1
								AND tpda.id_agent = tam.id_agente
								AND tam.id_agente_modulo = $id_agent_module
						UNION ALL
							SELECT tpd.*
							FROM tplanned_downtime tpd, tplanned_downtime_modules tpdm
							WHERE tpd.id = tpdm.id_downtime
								AND tpdm.id_agent_module = $id_agent_module
					) tpdr
					ORDER BY tpdr.id";

	$downtimes = db_get_all_rows_sql($sql_downtime);

	if ($downtimes == false) {
		$downtimes = array();
	}
	$downtime_dates = array();
	foreach ($downtimes as $downtime) {
		$downtime_id = $downtime['id'];
		$downtime_type = $downtime['type_execution'];
		$downtime_periodicity = $downtime['type_periodicity'];
		
		if ($downtime_type == 'once') {
			$dates = array();
			$dates['date_from'] = $downtime['date_from'];
			$dates['date_to'] = $downtime['date_to'];
			$downtime_dates[] = $dates;
		}
		else if ($downtime_type == 'periodically') {

			// If a planned downtime have malformed dates, its intervals aren't taken account
			$downtime_malformed = false;
			foreach ($malformed_planned_downtimes as $malformed_planned_downtime) {
				if ($downtime_id == $malformed_planned_downtime['id']) {
					$downtime_malformed = true;
					break;
				}
			}
			if ($downtime_malformed == true) {
				continue;
			}
			// If a planned downtime have malformed dates, its intervals aren't taken account

			$downtime_time_from = $downtime['periodically_time_from'];
			$downtime_time_to = $downtime['periodically_time_to'];

			$downtime_hour_from = date("H", strtotime($downtime_time_from));
			$downtime_minute_from = date("i", strtotime($downtime_time_from));
			$downtime_second_from = date("s", strtotime($downtime_time_from));
			$downtime_hour_to = date("H", strtotime($downtime_time_to));
			$downtime_minute_to = date("i", strtotime($downtime_time_to));
			$downtime_second_to = date("s", strtotime($downtime_time_to));

			if ($downtime_periodicity == "monthly") {
				$downtime_day_from = $downtime['periodically_day_from'];
				$downtime_day_to = $downtime['periodically_day_to'];

				$date_aux = strtotime(date("Y-m-01", $start_date));
				$year_aux = date("Y", $date_aux);
				$month_aux = date("m", $date_aux);

				$end_year = date("Y", $end_date);
				$end_month = date("m", $end_date);

				while ($year_aux < $end_year || ($year_aux == $end_year && $month_aux <= $end_month)) {
					
					if ($downtime_day_from > $downtime_day_to) {
						$dates = array();
						$dates['date_from'] = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
						$dates['date_to'] = strtotime(date("Y-m-t H:i:s", strtotime("$year_aux-$month_aux-28 23:59:59")));
						$downtime_dates[] = $dates;

						$dates = array();
						if ($month_aux + 1 <= 12) {
							$dates['date_from'] = strtotime("$year_aux-".($month_aux + 1)."-01 00:00:00");
							$dates['date_to'] = strtotime("$year_aux-".($month_aux + 1)."-$downtime_day_to $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
						}
						else {
							$dates['date_from'] = strtotime(($year_aux + 1)."-01-01 00:00:00");
							$dates['date_to'] = strtotime(($year_aux + 1)."-01-$downtime_day_to $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
						}
						$downtime_dates[] = $dates;
					}
					else {
						if ($downtime_day_from == $downtime_day_to && strtotime($downtime_time_from) > strtotime($downtime_time_to)) {
							$date_aux_from = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
							$max_day_num = date('t', $date_aux);

							$dates = array();
							$dates['date_from'] = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
							$dates['date_to'] = strtotime("$year_aux-$month_aux-$downtime_day_from 23:59:59");
							$downtime_dates[] = $dates;

							if ($downtime_day_to + 1 > $max_day_num) {

								$dates = array();
								if ($month_aux + 1 <= 12) {
									$dates['date_from'] = strtotime("$year_aux-".($month_aux + 1)."-01 00:00:00");
									$dates['date_to'] = strtotime("$year_aux-".($month_aux + 1)."-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
								}
								else {
									$dates['date_from'] = strtotime(($year_aux + 1)."-01-01 00:00:00");
									$dates['date_to'] = strtotime(($year_aux + 1)."-01-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
								}
								$downtime_dates[] = $dates;
							}
							else {
								$dates = array();
								$dates['date_from'] = strtotime("$year_aux-$month_aux-".($downtime_day_to + 1)." 00:00:00");
								$dates['date_to'] = strtotime("$year_aux-$month_aux-".($downtime_day_to + 1)." $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
								$downtime_dates[] = $dates;
							}
						}
						else {
							$dates = array();
							$dates['date_from'] = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
							$dates['date_to'] = strtotime("$year_aux-$month_aux-$downtime_day_to $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
							$downtime_dates[] = $dates;
						}
					}

					$month_aux++;
					if ($month_aux > 12) {
						$month_aux = 1;
						$year_aux++;
					}
				}
			}
			else if ($downtime_periodicity == "weekly") {
				$date_aux = $start_date;
				$active_days = array();
				$active_days[0] = ($downtime['sunday'] == 1) ? true : false;
				$active_days[1] = ($downtime['monday'] == 1) ? true : false;
				$active_days[2] = ($downtime['tuesday'] == 1) ? true : false;
				$active_days[3] = ($downtime['wednesday'] == 1) ? true : false;
				$active_days[4] = ($downtime['thursday'] == 1) ? true : false;
				$active_days[5] = ($downtime['friday'] == 1) ? true : false;
				$active_days[6] = ($downtime['saturday'] == 1) ? true : false;

				while ($date_aux <= $end_date) {
					$weekday_num = date('w', $date_aux);
					
					if ($active_days[$weekday_num]) {
						$day_num = date('d', $date_aux);
						$month_num = date('m', $date_aux);
						$year_num = date('Y', $date_aux);

						$max_day_num = date('t', $date_aux);

						if (strtotime($downtime_time_from) > strtotime($downtime_time_to)) {
							$dates = array();
							$dates['date_from'] = strtotime("$year_num-$month_num-$day_num $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
							$dates['date_to'] = strtotime("$year_num-$month_num-$day_num 23:59:59");
							$downtime_dates[] = $dates;

							$dates = array();
							if ($day_num + 1 > $max_day_num) {
								if ($month_num + 1 > 12) {
									$dates['date_from'] = strtotime(($year_num + 1)."-01-01 00:00:00");
									$dates['date_to'] = strtotime(($year_num + 1)."-01-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
								}
								else {
									$dates['date_from'] = strtotime("$year_num-".($month_num + 1)."-01 00:00:00");
									$dates['date_to'] = strtotime("$year_num-".($month_num + 1)."-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
								}
							}
							else {
								$dates['date_from'] = strtotime("$year_num-$month_num-".($day_num + 1)." 00:00:00");
								$dates['date_to'] = strtotime("$year_num-$month_num-".($day_num + 1)." $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
							}
							$downtime_dates[] = $dates;
						}
						else {
							$dates = array();
							$dates['date_from'] = strtotime("$year_num-$month_num-$day_num $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
							$dates['date_to'] = strtotime("$year_num-$month_num-$day_num $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
							$downtime_dates[] = $dates;
						}
					}

					$date_aux += SECONDS_1DAY;
				}
			}
		}
	}

	if ($check_services) {
		enterprise_include_once("include/functions_services.php");
		if (function_exists("services_get_planned_downtimes_intervals")) {
			services_get_planned_downtimes_intervals($downtime_dates, $start_date, $end_date, false, $id_agent_module);
		}
	}

	return $downtime_dates;
}

/** 
 * Get the planned downtimes that affect the passed modules on an specific datetime range.
 * 
 * @param int Start date in utimestamp.
 * @param int End date in utimestamp.
 * @param array The agent modules ids.
 * 
 * @return Array with the planned downtimes that are executed in any moment of the range selected and affect the
 * agent modules selected.
 */
function reporting_get_planned_downtimes ($start_date, $end_date, $id_agent_modules = false) {
	$start_time = date("H:i:s", $start_date);
	$end_time = date("H:i:s", $end_date);

	$start_day = date("d", $start_date);
	$end_day = date("d", $end_date);

	$start_month = date("m", $start_date);
	$end_month = date("m", $end_date);

	if ($start_date > $end_date) {
		return false;
	}

	if ($end_date - $start_date >= SECONDS_1MONTH) {
		// If the date range is larger than 1 month, every monthly planned downtime will be inside
		$periodically_monthly_w = "type_periodicity = 'monthly'";
	}
	else {
		// Check if the range is larger than the planned downtime execution, or if its start or end
		// is inside the planned downtime execution.
		// The start and end time is very important.
		$periodically_monthly_w = "type_periodicity = 'monthly'
									AND (((periodically_day_from > '$start_day'
												OR (periodically_day_from = '$start_day'
													AND periodically_time_from >= '$start_time'))
											AND (periodically_day_to < '$end_day'
												OR (periodically_day_to = '$end_day'
													AND periodically_time_to <= '$end_time')))
										OR ((periodically_day_from < '$start_day' 
												OR (periodically_day_from = '$start_day'
													AND periodically_time_from <= '$start_time'))
											AND (periodically_day_to > '$start_day'
												OR (periodically_day_to = '$start_day'
													AND periodically_time_to >= '$start_time')))
										OR ((periodically_day_from < '$end_day' 
												OR (periodically_day_from = '$end_day'
													AND periodically_time_from <= '$end_time'))
											AND (periodically_day_to > '$end_day'
												OR (periodically_day_to = '$end_day'
													AND periodically_time_to >= '$end_time'))))";
	}

	$periodically_weekly_days = array();
	$date_aux = $start_date;
	$i = 0;

	if (($end_date - $start_date) >= SECONDS_1WEEK) {
		// If the date range is larger than 7 days, every weekly planned downtime will be inside.
		for ($i = 0; $i < 7; $i++) {
			$weekday_actual = strtolower(date('l', $date_aux));
			$periodically_weekly_days[] = "($weekday_actual = 1)";
			$date_aux += SECONDS_1DAY;
		}
	}
	else if (($end_date - $start_date) <= SECONDS_1DAY && $start_day == $end_day) {
		// If the date range is smaller than 1 day, the start and end days can be equal or consecutive.
		// If they are equal, the execution times have to be contained in the date range times or contain
		// the start or end time of the date range.
		$weekday_actual = strtolower(date('l', $start_date));
		$periodically_weekly_days[] = "($weekday_actual = 1
			AND ((periodically_time_from > '$start_time' AND periodically_time_to < '$end_time')
				OR (periodically_time_from = '$start_time'
					OR (periodically_time_from < '$start_time'
						AND periodically_time_to >= '$start_time'))
				OR (periodically_time_from = '$end_time'
					OR (periodically_time_from < '$end_time'
						AND periodically_time_to >= '$end_time'))))";
	}
	else {
		while ($date_aux <= $end_date && $i < 7) {

			$weekday_actual = strtolower(date('l', $date_aux));
			$day_num_actual = date('d', $date_aux);

			if ($date_aux == $start_date) {
				$periodically_weekly_days[] = "($weekday_actual = 1 AND periodically_time_to >= '$start_time')";
			}
			else if ($day_num_actual == $end_day) {
				$periodically_weekly_days[] = "($weekday_actual = 1 AND periodically_time_from <= '$end_time')";
			}
			else {
				$periodically_weekly_days[] = "($weekday_actual = 1)";
			}
			
			$date_aux += SECONDS_1DAY;
			$i++;
		}
	}

	if (!empty($periodically_weekly_days)) {
		$periodically_weekly_w = "type_periodicity = 'weekly' AND (".implode(" OR ", $periodically_weekly_days).")";
		$periodically_condition = "(($periodically_monthly_w) OR ($periodically_weekly_w))";
	}
	else {
		$periodically_condition = "($periodically_monthly_w)";
	}

	if ($id_agent_modules !== false) {
		if (empty($id_agent_modules))
			return array();

		$id_agent_modules_str = implode(",", $id_agent_modules);

		$sql_downtime = "SELECT DISTINCT(tpdr.id), tpdr.*
						FROM (
								SELECT tpd.*
								FROM tplanned_downtime tpd, tplanned_downtime_agents tpda, tagente_modulo tam
								WHERE (tpd.id = tpda.id_downtime
										AND tpda.all_modules = 1
										AND tpda.id_agent = tam.id_agente
										AND tam.id_agente_modulo IN ($id_agent_modules_str))
									AND ((type_execution = 'periodically'
											AND $periodically_condition)
										OR (type_execution = 'once'
											AND ((date_from >= '$start_date' AND date_to <= '$end_date')
												OR (date_from <= '$start_date' AND date_to >= '$end_date')
												OR (date_from <= '$start_date' AND date_to >= '$start_date')
												OR (date_from <= '$end_date' AND date_to >= '$end_date'))))
							UNION ALL
								SELECT tpd.*
								FROM tplanned_downtime tpd, tplanned_downtime_modules tpdm
								WHERE (tpd.id = tpdm.id_downtime
										AND tpdm.id_agent_module IN ($id_agent_modules_str))
									AND ((type_execution = 'periodically'
											AND $periodically_condition)
										OR (type_execution = 'once'
											AND ((date_from >= '$start_date' AND date_to <= '$end_date')
												OR (date_from <= '$start_date' AND date_to >= '$end_date')
												OR (date_from <= '$start_date' AND date_to >= '$start_date')
												OR (date_from <= '$end_date' AND date_to >= '$end_date'))))
						) tpdr
						ORDER BY tpdr.id";
	}
	else {
		$sql_downtime = "SELECT *
						FROM tplanned_downtime tpd, tplanned_downtime_modules tpdm
						WHERE (type_execution = 'periodically'
									AND $periodically_condition)
								OR (type_execution = 'once'
									AND ((date_from >= '$start_date' AND date_to <= '$end_date')
										OR (date_from <= '$start_date' AND date_to >= '$end_date')
										OR (date_from <= '$start_date' AND date_to >= '$start_date')
										OR (date_from <= '$end_date' AND date_to >= '$end_date')))";
	}

	$downtimes = db_get_all_rows_sql($sql_downtime);
	if ($downtimes == false) {
		$downtimes = array();
	}

	return $downtimes;
}

function reporting_get_stats_servers($tiny = true) {
	global $config;
	
	$server_performance = servers_get_performance();
	
	// Alerts table
	$table_srv = html_get_predefined_table();
	
	$table_srv->style[0] = $table_srv->style[2] = 'text-align: right; padding: 5px;';
	$table_srv->style[1] = $table_srv->style[3] = 'text-align: left; padding: 5px;';
	
	$tdata = array();
	$tdata[0] = html_print_image('images/module.png', true, array('title' => __('Total running modules'), 'width' => '25px'));
	$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_modules"]) . '</span>';
	
	$tdata[2] = '<span class="med_data">' . format_numeric($server_performance ["total_modules_rate"], 2) . '</span>';
	$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
	
	$table_srv->rowclass[] = '';
	$table_srv->data[] = $tdata;
	
	$tdata = array();
	$tdata[0] = '<hr style="border: 0; height: 1px; background: #DDD">';
	$table_srv->colspan[count($table_srv->data)][0] = 4;
	$table_srv->rowclass[] = '';
	$table_srv->data[] = $tdata;
	
	$tdata = array();
	$tdata[0] = html_print_image('images/database.png', true, array('title' => __('Local modules'), 'width' => '25px'));
	$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_local_modules"]) . '</span>';
	
	$tdata[2] = '<span class="med_data">' .
		format_numeric($server_performance ["local_modules_rate"], 2) . '</span>';
	$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
	
	$table_srv->rowclass[] = '';
	$table_srv->data[] = $tdata;
	
	if ($tiny) {
		$tdata = array();
		$tdata[0] = html_print_image('images/network.png', true, array('title' => __('Remote modules'), 'width' => '25px'));
		$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_remote_modules"]) . '</span>';
		
		$tdata[2] = '<span class="med_data">' . format_numeric($server_performance ["remote_modules_rate"], 2) . '</span>';
		$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
		
		$table_srv->rowclass[] = '';
		$table_srv->data[] = $tdata;
	}
	else {
		if (isset($server_performance ["total_network_modules"])) {
			$tdata = array();
			$tdata[0] = html_print_image('images/network.png', true, array('title' => __('Network modules'), 'width' => '25px'));
			$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_network_modules"]) . '</span>';
			
			$tdata[2] = '<span class="med_data">' .
				format_numeric($server_performance["network_modules_rate"], 2) .
				'</span>';
			$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
			
			$table_srv->rowclass[] = '';
			$table_srv->data[] = $tdata;
		}
		
		if (isset($server_performance ["total_plugin_modules"])) {
			$tdata = array();
			$tdata[0] = html_print_image('images/plugin.png', true, array('title' => __('Plugin modules'), 'width' => '25px'));
			$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_plugin_modules"]) . '</span>';
			
			$tdata[2] = '<span class="med_data">' . format_numeric($server_performance ["plugin_modules_rate"], 2) . '</span>';
			$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
			
			$table_srv->rowclass[] = '';
			$table_srv->data[] = $tdata;
		}
		
		if (isset($server_performance ["total_prediction_modules"])) {
			$tdata = array();
			$tdata[0] = html_print_image('images/chart_bar.png', true, array('title' => __('Prediction modules'), 'width' => '25px'));
			$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_prediction_modules"]) . '</span>';
			
			$tdata[2] = '<span class="med_data">' . format_numeric($server_performance ["prediction_modules_rate"], 2) . '</span>';
			$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
			
			$table_srv->rowclass[] = '';
			$table_srv->data[] = $tdata;
		}
		
		if (isset($server_performance ["total_wmi_modules"])) {
			$tdata = array();
			$tdata[0] = html_print_image('images/wmi.png', true, array('title' => __('WMI modules'), 'width' => '25px'));
			$tdata[1] = '<span class="big_data">' . format_numeric($server_performance ["total_wmi_modules"]) . '</span>';
			
			$tdata[2] = '<span class="med_data">' . format_numeric($server_performance ["wmi_modules_rate"], 2) . '</span>';
			$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
			
			$table_srv->rowclass[] = '';
			$table_srv->data[] = $tdata;
		}
		
		if (isset($server_performance ["total_web_modules"])) {
			$tdata = array();
			$tdata[0] = html_print_image('images/world.png', true, array('title' => __('Web modules'), 'width' => '25px'));
			$tdata[1] = '<span class="big_data">' .
				format_numeric($server_performance ["total_web_modules"]) .
				'</span>';
			
			$tdata[2] = '<span class="med_data">' .
				format_numeric($server_performance ["web_modules_rate"], 2) .
				'</span>';
			$tdata[3] = html_print_image('images/module.png', true, array('title' => __('Ratio') . ': ' . __('Modules by second'), 'width' => '16px')) . '/sec </span>';
			
			$table_srv->rowclass[] = '';
			$table_srv->data[] = $tdata;
		}
		
		$tdata = array();
		$tdata[0] = '<hr style="border: 0; height: 1px; background: #DDD">';
		$table_srv->colspan[count($table_srv->data)][0] = 4;
		$table_srv->rowclass[] = '';
		$table_srv->data[] = $tdata;
		
		
		switch ($config["dbtype"]) {
			case "mysql":
				$system_events = db_get_value_sql(
					'SELECT SQL_NO_CACHE COUNT(id_evento)
					FROM tevento');
				break;
			case "postgresql":
			case "oracle":
				$system_events = db_get_value_sql(
					'SELECT COUNT(id_evento)
					FROM tevento');
				break;
		}
		
		
		
		$tdata = array();
		$tdata[0] = html_print_image('images/lightning_go.png', true,
			array('title' => __('Total events'), 'width' => '25px'));
		$tdata[1] = '<span class="big_data">' .
			format_numeric($system_events) . '</span>';
		
		$table_srv->colspan[count($table_srv->data)][1] = 3;
		$table_srv->rowclass[] = '';
		$table_srv->data[] = $tdata;
	}
	
	$output = '<fieldset class="databox tactical_set">
				<legend>' . 
					__('Server performance') . 
				'</legend>' . 
				html_print_table($table_srv, true) . '</fieldset>';
	
	return $output;
}


function reporting_get_stats_summary($data, $graph_width, $graph_height) {
	global $config;
	
	// Alerts table
	$table_sum = html_get_predefined_table();
	
	$tdata = array();
	$table_sum->colspan[count($table_sum->data)][0] = 2;
	$table_sum->colspan[count($table_sum->data)][2] = 2;
	$table_sum->cellstyle[count($table_sum->data)][0] = 'text-align: center;';
	$table_sum->cellstyle[count($table_sum->data)][2] = 'text-align: center;';
	$tdata[0] = '<span class="med_data" style="color: #666">' . __('Module status') . '</span>';
	$tdata[2] = '<span class="med_data" style="color: #666">' . __('Alert level') . '</span>';
	$table_sum->rowclass[] = '';
	$table_sum->data[] = $tdata;
	
	$tdata = array();
	$table_sum->colspan[count($table_sum->data)][0] = 2;
	$table_sum->colspan[count($table_sum->data)][2] = 2;
	$table_sum->cellstyle[count($table_sum->data)][0] = 'text-align: center;';
	$table_sum->cellstyle[count($table_sum->data)][2] = 'text-align: center;';
	
	if ($data["monitor_checks"] > 0) {
		$tdata[0] = '<div style="margin: auto; width: ' . $graph_width . 'px;">' . graph_agent_status (false, $graph_width, $graph_height, true, true) . '</div>';
	}
	else {
		$tdata[2] = html_print_image('images/image_problem.png', true, array('width' => $graph_width));
	}
	if ($data["monitor_alerts"] > 0) {
		$tdata[2] = '<div style="margin: auto; width: ' . $graph_width . 'px;">' . graph_alert_status ($data["monitor_alerts"], $data["monitor_alerts_fired"], $graph_width, $graph_height, true, true) . '</div>';
	}
	else {
		$tdata[2] = html_print_image('images/image_problem.png', true, array('width' => $graph_width));
	}
		$table_sum->rowclass[] = '';
		$table_sum->data[] = $tdata;
	
	$output = '<fieldset class="databox tactical_set">
				<legend>' . 
					__('Summary') . 
				'</legend>' . 
				html_print_table($table_sum, true) . '</fieldset>';
	
	return $output;
}






/** 
 * Get an event reporting table.
 *
 * It construct a table object with all the events happened in a group
 * during a period of time.
 * 
 * @param int Group id to get the report.
 * @param int Period of time to get the report.
 * @param int Beginning date of the report
 * @param int Flag to return or echo the report table (echo by default).
 * 
 * @return object A table object
 */
function reporting_event_reporting ($id_group, $period, $date = 0, $return = false) {
	if (empty ($date)) {
		$date = get_system_time ();
	}
	elseif (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Status');
	$table->head[1] = __('Event name');
	$table->head[2] = __('User ID');
	$table->head[3] = __('Timestamp');
	
	$events = events_get_group_events ($id_group, $period, $date);
	if (empty ($events)) {
		$events = array ();
	}
	foreach ($events as $event) {
		$data = array ();
		if ($event["estado"] == 0)
			$data[0] = html_print_image("images/dot_red.png", true);
		else
			$data[0] = html_print_image("images/dot_green.png", true);
		$data[1] = $event['evento'];
		$data[2] = $event['id_usuario'] != '0' ? $event['id_usuario'] : '';
		$data[3] = $event["timestamp"];
		array_push ($table->data, $data);
	}
	
	if (empty ($return))
		html_print_table ($table);
	
	return $table;
}

/** 
 * Get a table report from a alerts fired array.
 * 
 * @param array Alerts fired array. 
 * @see function get_alerts_fired ()
 * 
 * @return object A table object with a report of the fired alerts.
 */
function reporting_get_fired_alerts_table ($alerts_fired) {
	$agents = array ();
	global $config;
	
	require_once ($config["homedir"].'/include/functions_alerts.php');
	
	foreach (array_keys ($alerts_fired) as $id_alert) {
		$alert_module = alerts_get_alert_agent_module ($id_alert);
		$template = alerts_get_alert_template ($id_alert);
		
		/* Add alerts fired to $agents_fired_alerts indexed by id_agent */
		$id_agent = db_get_value ('id_agente', 'tagente_modulo',
			'id_agente_modulo', $alert_module['id_agent_module']);
		if (!isset ($agents[$id_agent])) {
			$agents[$id_agent] = array ();
		}
		array_push ($agents[$id_agent], array ($alert_module, $template));
	}
	
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Agent');
	$table->head[1] = __('Alert description');
	$table->head[2] = __('Times fired');
	$table->head[3] = __('Priority');
	
	foreach ($agents as $id_agent => $alerts) {
		$data = array ();
		foreach ($alerts as $tuple) {
			$alert_module = $tuple[0];
			$template = $tuple[1];
			if (! isset ($data[0]))
				$data[0] = agents_get_name ($id_agent);
			else
				$data[0] = '';
			$data[1] = $template['name'];
			$data[2] = $alerts_fired[$alert_module['id']];
			$data[3] = get_alert_priority ($alert_module['priority']);
			array_push ($table->data, $data);
		}
	}
	
	return $table;
}

/**
 * Get a report for alerts in a group of agents.
 *
 * It prints the numbers of alerts defined, fired and not fired in a group.
 * It also prints all the alerts that were fired grouped by agents.
 *
 * @param int $id_group Group to get info of the alerts.
 * @param int $period Period of time of the desired alert report.
 * @param int $date Beggining date of the report (current date by default).
 * @param bool $return Flag to return or echo the report (echo by default).
 *
 * @return string
 */
function reporting_alert_reporting ($id_group, $period = 0, $date = 0, $return = false) {
	global $config;
	
	$output = '';
	$alerts = get_group_alerts ($id_group);
	$alerts_fired = get_alerts_fired ($alerts, $period, $date);
	
	$fired_percentage = 0;
	if (sizeof ($alerts) > 0)
		$fired_percentage = round (sizeof ($alerts_fired) / sizeof ($alerts) * 100, 2);
	$not_fired_percentage = 100 - $fired_percentage;
	
	$data = array ();
	$data[__('Alerts fired')] = $fired_percentage;
	$data[__('Alerts not fired')] = $not_fired_percentage;
	
	$output .= pie3d_graph(false, $data, 280, 150,
		__("other"),
		ui_get_full_url(false, false, false, false) . '/',
		ui_get_full_url(false, false, false, false) .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size']); 
	
	$output .= '<strong>'.__('Alerts fired').': '.sizeof ($alerts_fired).'</strong><br />';
	$output .= '<strong>'.__('Total alerts monitored').': '.sizeof ($alerts).'</strong><br />';
	
	if (! sizeof ($alerts_fired)) {
		if (!$return)
			echo $output;
		
		return $output;
	}
	$table = reporting_get_fired_alerts_table ($alerts_fired);
	$table->width = '100%';
	$table->class = 'databox';
	$table->size = array ();
	$table->size[0] = '100px';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	
	$output .= html_print_table ($table, true);
	
	if (!$return)
		echo $output;
	
	return $output;
}

/**
 * Get a report for monitors modules in a group of agents.
 *
 * It prints the numbers of monitors defined, showing those which went up and down, in a group.
 * It also prints all the down monitors in the group.
 *
 * @param int $id_group Group to get info of the monitors.
 * @param int $period Period of time of the desired monitor report.
 * @param int $date Beginning date of the report in UNIX time (current date by default).
 * @param bool $return Flag to return or echo the report (by default).
 *
 * @return string
 */
function reporting_monitor_health ($id_group, $period = 0, $date = 0, $return = false) {
	if (empty ($date)) //If date is 0, false or empty
		$date = get_system_time ();
	
	$datelimit = $date - $period;
	$output = '';
	
	$monitors = modules_get_monitors_in_group ($id_group);
	if (empty ($monitors)) //If monitors has returned false or an empty array
		return;
	$monitors_down = modules_get_monitors_down ($monitors, $period, $date);
	$down_percentage = round (count ($monitors_down) / count ($monitors) * 100, 2);
	$not_down_percentage = 100 - $down_percentage;
	
	$output .= '<strong>'.__('Total monitors').': '.count ($monitors).'</strong><br />';
	$output .= '<strong>'.__('Monitors down on period').': '.count ($monitors_down).'</strong><br />';
	
	$table = reporting_get_monitors_down_table ($monitors_down);
	$table->width = '100%';
	$table->class = 'databox';
	$table->size = array ();
	$table->size[0] = '100px';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	
	$table->size = array ();
	$table->size[0] = '100px';
	
	$output .= html_print_table ($table, true);
	
	$data = array();
	$data[__('Monitors OK')] = $down_percentage;
	$data[__('Monitors BAD')] = $not_down_percentage;
	
	$output .= pie3d_graph(false, $data, 280, 150,
		__("other"),
		ui_get_full_url(false, false, false, false) . '/',
		ui_get_full_url(false, false, false, false) .  "/images/logo_vertical_water.png",
		$config['fontpath'], $config['font_size']); 
	
	if (!$return)
		echo $output;
	
	return $output;
}

/** 
 * Get a report table with all the monitors down.
 * 
 * @param array  An array with all the monitors down
 * @see function modules_get_monitors_down()
 * 
 * @return object A table object with a monitors down report.
 */
function reporting_get_monitors_down_table ($monitors_down) {
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Agent');
	$table->head[1] = __('Monitor');
	
	$agents = array ();
	if ($monitors_down) {
		foreach ($monitors_down as $monitor) {
			/* Add monitors fired to $agents_fired_alerts indexed by id_agent */
			$id_agent = $monitor['id_agente'];
			if (!isset ($agents[$id_agent])) {
				$agents[$id_agent] = array ();
			}
			array_push ($agents[$id_agent], $monitor);
			
			$monitors_down++;
		}
		foreach ($agents as $id_agent => $monitors) {
			$data = array ();
			foreach ($monitors as $monitor) {
				if (! isset ($data[0]))
					$data[0] = agents_get_name ($id_agent);
				else
					$data[0] = '';
				if ($monitor['descripcion'] != '') {
					$data[1] = $monitor['descripcion'];
				}
				else {
					$data[1] = $monitor['nombre'];
				}
				array_push ($table->data, $data);
			}
		}
	}
	
	return $table;
}

/**
 * Get a general report of a group of agents.
 *
 * It shows the number of agents and no more things right now. 
 *
 * @param int Group to get the report
 * @param bool Flag to return or echo the report (by default).
 * 
 * @return HTML string with group report
 */
function reporting_print_group_reporting ($id_group, $return = false) {
	$agents = agents_get_group_agents ($id_group, false, "none");
	$output = '<strong>' .
		sprintf(__('Agents in group: %s'), count($agents)) .
		'</strong><br />';
	
	if ($return === false)
		echo $output;
	
	return $output;
}

/** 
 * Get a report table of the fired alerts group by agents.
 * 
 * @param int Agent id to generate the report.
 * @param int Period of time of the report.
 * @param int Beginning date of the report in UNIX time (current date by default).
 * 
 * @return object A table object with the alert reporting..
 */
function reporting_get_agent_alerts_table ($id_agent, $period = 0, $date = 0) {
	global $config;
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Type');
	$table->head[1] = __('Description');
	$table->head[2] = __('Value');
	$table->head[3] = __('Threshold');
	$table->head[4] = __('Last fired');
	$table->head[5] = __('Times fired');
	
	require_once ($config["homedir"].'/include/functions_alerts.php');
	
	$alerts = agents_get_alerts ($id_agent);
	
	foreach ($alerts['simple'] as $alert) {
		$fires = get_alert_fires_in_period ($alert['id'], $period, $date);
		if (! $fires) {
			continue;
		}
		
		$template = alerts_get_alert_template ($alert['id_alert_template']);
		$data = array ();
		$data[0] = alerts_get_alert_templates_type_name ($template['type']);
		$data[1] = $template['name'];
		
		switch ($template['type']) {
		case 'regex':
			if ($template['matches_value'])
				$data[2] = '&#8771; "'.$template['value'].'"';
			else
				$data[2] = '&#8772; "'.$template['value'].'"';
			break;
		case 'equal':
		case 'not_equal':
			$data[2] = $template['value'];
			
			break;
		case 'max-min':
			$data[2] = __('Min.').': '.$template['min_value']. ' ';
			$data[2] .= __('Max.').': '.$template['max_value']. ' ';
			
			break;
		case 'max':
			$data[2] = $template['max_value'];
			
			break;
		case 'min':
			$data[2] = $template['min_value'];
			
			break;
		}
		$data[3] = $template['time_threshold'];
		$data[4] = ui_print_timestamp (get_alert_last_fire_timestamp_in_period ($alert['id'], $period, $date), true);
		$data[5] = $fires;
		
		array_push ($table->data, $data);
	}
	
	return $table;
}

/** 
 * Get a report of monitors in an agent.
 * 
 * @param int Agent id to get the report
 * @param int Period of time of the report.
 * @param int Beginning date of the report in UNIX time (current date by default).
 * 
 * @return object A table object with the report.
 */
function reporting_get_agent_monitors_table ($id_agent, $period = 0, $date = 0) {
	$n_a_string = __('N/A').'(*)';
	$table->head = array ();
	$table->head[0] = __('Monitor');
	$table->head[1] = __('Last failure');
	$table->data = array ();
	$monitors = modules_get_monitors_in_agent ($id_agent);
	
	if ($monitors === false) {
		return $table;
	}
	foreach ($monitors as $monitor) {
		$downs = modules_get_monitor_downs_in_period ($monitor['id_agente_modulo'], $period, $date);
		if (! $downs) {
			continue;
		}
		$data = array ();
		if ($monitor['descripcion'] != $n_a_string && $monitor['descripcion'] != '')
			$data[0] = $monitor['descripcion'];
		else
			$data[0] = $monitor['nombre'];
		$data[1] = modules_get_last_down_timestamp_in_period ($monitor['id_agente_modulo'], $period, $date);
		array_push ($table->data, $data);
	}
	
	return $table;
}

/** 
 * Get a report of all the modules in an agent.
 * 
 * @param int Agent id to get the report.
 * @param int Period of time of the report
 * @param int Beginning date of the report in UNIX time (current date by default).
 * 
 * @return object
 */
function reporting_get_agent_modules_table ($id_agent, $period = 0, $date = 0) {
	$table->data = array ();
	$n_a_string = __('N/A').'(*)';
	$modules = agents_get_modules ($id_agent, array ("nombre", "descripcion"));
	if ($modules === false)
		$modules = array();
	$data = array ();
	
	foreach ($modules as $module) {
		if ($module['descripcion'] != $n_a_string && $module['descripcion'] != '')
			$data[0] = $module['descripcion'];
		else
			$data[0] = $module['nombre'];
		array_push ($table->data, $data);
	}
	
	return $table;
}

/**
 * Get a detailed report of an agent
 *
 * @param int Agent to get the report.
 * @param int Period of time of the desired report.
 * @param int Beginning date of the report in UNIX time (current date by default).
 * @param bool Flag to return or echo the report (by default).
 *
 * @return string
 */
function reporting_get_agent_detailed ($id_agent, $period = 0, $date = 0, $return = false) {
	$output = '';
	$n_a_string = __('N/A(*)');
	
	/* Show modules in agent */
	$output .= '<div class="agent_reporting">';
	$output .= '<h3 style="text-decoration: underline">' .
		__('Agent') . ' - ' . agents_get_name ($id_agent) . '</h3>';
	$output .= '<h4>'.__('Modules').'</h3>';
	$table_modules = reporting_get_agent_modules_table ($id_agent, $period, $date);
	$table_modules->width = '99%';
	$output .= html_print_table ($table_modules, true);
	
	/* Show alerts in agent */
	$table_alerts = reporting_get_agent_alerts_table ($id_agent, $period, $date);
	$table_alerts->width = '99%';
	if (sizeof ($table_alerts->data)) {
		$output .= '<h4>'.__('Alerts').'</h4>';
		$output .= html_print_table ($table_alerts, true);
	}
	
	/* Show monitor status in agent (if any) */
	$table_monitors = reporting_get_agent_monitors_table ($id_agent, $period, $date);
	if (sizeof ($table_monitors->data) == 0) {
		$output .= '</div>';
		if (! $return)
			echo $output;
		return $output;
	}
	$table_monitors->width = '99%';
	$table_monitors->align = array ();
	$table_monitors->align[1] = 'right';
	$table_monitors->size = array ();
	$table_monitors->align[1] = '10%';
	$output .= '<h4>'.__('Monitors').'</h4>';
	$output .= html_print_table ($table_monitors, true);
	
	$output .= '</div>';
	
	if (! $return)
		echo $output;
	return $output;
}

/**
 * Get a detailed report of agents in a group.
 *
 * @param mixed Group(s) to get the report
 * @param int Period
 * @param int Timestamp to start from
 * @param bool Flag to return or echo the report (by default).
 *
 * @return string
 */
function reporting_agents_get_group_agents_detailed ($id_group, $period = 0, $date = 0, $return = false) {
	$agents = agents_get_group_agents ($id_group, false, "none");
	
	$output = '';
	foreach ($agents as $agent_id => $agent_name) {
		$output .= reporting_get_agent_detailed ($agent_id, $period, $date, true);
	}
	
	if ($return === false)
		echo $output;
	
	return $output;
}


/**
 * Gets a detailed reporting of groups's events.  
 *
 * @param unknown_type $id_group Id of the group.
 * @param unknown_type $period Time period of the report.
 * @param unknown_type $date Date of the report.
 * @param unknown_type $return Whether to return or not.
 * @param unknown_type $html Whether to return HTML code or not.
 *
 * @return string Report of groups's events
 */
function reporting_get_group_detailed_event ($id_group, $period = 0,
	$date = 0, $return = false, $html = true,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
		
	global $config;
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	$table->width = '99%';
	
	$table->align = array();
	$table->align[0] = 'center';
	$table->align[2] = 'center';
	
	$table->data = array ();
	
	$table->head = array ();
	$table->head[0] = __('Status');
	$table->head[1] = __('Name');
	$table->head[2] = __('Type');
	$table->head[3] = __('Agent');
	$table->head[4] = __('Criticity');
	$table->head[5] = __('Val. by');
	$table->head[6] = __('Timestamp');
	
	$events = events_get_group_events($id_group, $period, $date,
		$filter_event_validated, $filter_event_critical,
		$filter_event_warning, $filter_event_no_validated);
	
	if ($events) {
		$note = '';
		if (count($events) >= 1000) {
			$note .= '* ' . __('Maximum of events shown') . ' (1000)<br>';
		}
		foreach ($events as $k => $event) {
			//First pass along the class of this row
			$table->cellclass[$k][1] = $table->cellclass[$k][3] =
			$table->cellclass[$k][4] = $table->cellclass[$k][5] =
			$table->cellclass[$k][6] =
				get_priority_class ($event["criticity"]);
			
			$data = array ();
			
			// Colored box
			switch ($event['estado']) {
				case 0:
					$img_st = "images/star.png";
					$title_st = __('New event');
					break;
				case 1:
					$img_st = "images/tick.png";
					$title_st = __('Event validated');
					break;
				case 2:
					$img_st = "images/hourglass.png";
					$title_st = __('Event in process');
					break;
			}
			$data[] = html_print_image ($img_st, true, 
				array ("class" => "image_status",
					"width" => 16,
					"title" => $title_st,
					"id" => 'status_img_' . $event["id_evento"]));
			
			$data[] = ui_print_truncate_text(
				io_safe_output($event['evento']),
				140, false, true);
			
			//$data[1] = $event['event_type'];
			$data[] = events_print_type_img ($event["event_type"], true);
			
			if (!empty($event['id_agente']))
				$data[] = agents_get_name($event['id_agente']);
			else
				$data[] = __('Pandora System');
			$data[] = get_priority_name ($event['criticity']);
			if (empty($event['id_usuario']) && $event['estado'] == EVENT_VALIDATE) {
				$data[] = '<i>' . __('System') . '</i>';
			}
			else {
				$user_name = db_get_value ('fullname', 'tusuario', 'id_user', $event['id_usuario']);
				$data[] = io_safe_output($user_name);
			}
			$data[] = '<font style="font-size: 6pt;">' .
				date($config['date_format'], $event['timestamp_rep']) .
				'</font>';
			array_push ($table->data, $data);
		}
		
		if ($html) {
			return html_print_table ($table, $return) . $note;
		}
		else {
			return $table;
		}
	}
	else {
		return false;
	}
}



/**
 *  This is the callback sorting function for SLA values descending
 * 
 *  @param array $a Array element 1 to compare
 *  @param array $b Array element 2 to compare
 * 
 */
function sla_value_desc_cmp($a, $b) {
	// This makes 'Unknown' values the lastest
	if (preg_match('/^(.)*Unknown(.)*$/', $a[5]))
		$a[6] = -1;
	
	if (preg_match('/^(.)*Unknown(.)*$/', $b[5]))
		$b[6] = -1;
	
	return ($a[6] < $b[6])? 1 : 0;
}

/**
 *  This is the callback sorting function for SLA values ascending
 * 
 *  @param array $a Array element 1 to compare
 *  @param array $b Array element 2 to compare
 * 
 */
function sla_value_asc_cmp($a, $b) {
	// This makes 'Unknown' values the lastest
	if (preg_match('/^(.)*Unknown(.)*$/', $a[5]))
		$a[6] = -1;
	
	if (preg_match('/^(.)*Unknown(.)*$/', $b[5]))
		$b[6] = -1;
	
	return ($a[6] > $b[6])? 1 : 0;
}

/**
 * Make the header for each content.
 */
function reporting_header_content($mini, $content, $report, &$table,
	$title = false, $name = false, $period = false) {
	
	global $config;
	
	if ($mini) {
		$sizh = '';
		$sizhfin = '';
	}
	else {
		$sizh = '<h4>';
		$sizhfin = '</h4>';
	}
	
	$data = array();
	
	$count_empty = 0;
	
	if ($title !== false) {
		$data[] = $sizh . $title . $sizhfin;
	}
	else $count_empty++;
	
	if ($name !== false) {
		$data[] = $sizh . $name . $sizhfin;
	}
	else $count_empty++;
	
	if ($period !== false && $content['period'] > 0) {
		$data[] = $sizh . $period . $sizhfin;
	}
	else if ($content['period'] == 0) {
		$es = json_decode($content['external_source'], true);
		if ($es['date'] == 0) {
			$date = __('Last data');
		}
		else {
			$date = date($config["date_format"], $es['date']);
		}
		
		$data[] = "<div style='text-align: right;'>" . $sizh . $date . $sizhfin . "</div>";
	}
	else {
		$data[] = "<div style='text-align: right;'>" . $sizh .
			"(" . human_time_description_raw ($content['period']) . ") " .
			__("From:") . " " . date($config["date_format"], $report["datetime"] - $content['period']) . "<br />" .
			__("To:") . " " . date($config["date_format"], $report["datetime"]) . "<br />" .
			$sizhfin . "</div>";
	}
	
	$table->colspan[0][2 - $count_empty] = 1 + $count_empty;
	
	array_push ($table->data, $data);
}

/** 
 * This function is used once, in reporting_viewer.php, the HTML report render
 * file. This function proccess each report item and write the render in the
 * table record.
 * 
 * @param array $content Record of treport_content table for current item
 * @param array $table HTML Table row
 * @param array $report Report contents, with some added fields.
 * @param array $mini Mini flag for reduce the size.
 * 
 */

function reporting_render_report_html_item ($content, $table, $report, $mini = false) {
	global $config;
	global $graphic_type;
	
	$only_image = (bool)$config['flash_charts'] ? false : true;
	
	if ($mini) {
		$sizem = '1.5';
		$sizgraph_w = '450';
		$sizgraph_h = '100';
	}
	else {
		$sizem = '3';
		$sizgraph_w = '900';
		$sizgraph_h = '230';
	}
	
	// Disable remote connections for netflow report items
	if ($content['type'] != 'netflow_area' &&
		$content['type'] != 'netflow_pie' &&
		$content['type'] != 'netflow_data' &&
		$content['type'] != 'netflow_statistics' &&
		$content['type'] != 'netflow_summary') {
		
		$remote_connection = 1;
	}
	else {
		$remote_connection = 0;
	}
	
	$server_name = $content ['server_name'];
	if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE') && $remote_connection == 1) {
		$connection = metaconsole_get_connection($server_name);
		if (metaconsole_load_external_db($connection) != NOERR) {
			//ui_print_error_message ("Error connecting to ".$server_name);
		}
	}
	
	$module_name = db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']);
	if ($content['id_agent_module'] != 0) {
		$agent_name = modules_get_agentmodule_agent_name ($content['id_agent_module']);
	}
	else {
		$agent_name = agents_get_name($content['id_agent']);
	}
	
	$item_title = $content['name'];
	
	switch ($content["type"]) {
		case 'SLA_monthly':
			if (function_exists("reporting_enterprise_sla_monthly"))
				reporting_enterprise_sla_monthly($mini, $content, $report, $table, $item_title);
			break;
		case 'SLA_services':
			if (function_exists("reporting_enterprise_sla_services"))
				reporting_enterprise_sla_services($mini, $content, $report, $table, $item_title);
			break;
		case 3:
		case 'SLA':
			if (empty($item_title)) {
				$item_title = __('S.L.A.');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title);
			
			$edge_interval = 10;
			
			// What show?
			$show_table = $content['show_graph'] == 0 || $content['show_graph'] == 1;
			$show_graphs = $content['show_graph'] == 1 || $content['show_graph'] == 2;
			
			//RUNNING
			$table->style[1] = 'text-align: right';
			
			// Put description at the end of the module (if exists)
			
			$table->colspan[0][1] = 2;
			$next_row = 1;
			if ($content["description"] != "") {
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			
			$slas = db_get_all_rows_field_filter ('treport_content_sla_combined',
				'id_report_content', $content['id_rc']);
			
			if ($slas === false) {
				$data = array ();
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				$data[0] = __('There are no SLAs defined');
				array_push ($table->data, $data);
				$slas = array ();
				break;
			}
			elseif ($show_table) {
				$table1->width = '99%';
				$table1->data = array ();
				$table1->head = array ();
				$table1->head[0] = __('Agent');
				$table1->head[1] = __('Module');
				$table1->head[2] = __('Max/Min Values');
				$table1->head[3] = __('SLA Limit');
				$table1->head[4] = __('SLA Compliance');
				$table1->head[5] = __('Status');
				// $table1->head[6] = __('Criticity');
				$table1->style[0] = 'text-align: left';
				$table1->style[1] = 'text-align: left';
				$table1->style[2] = 'text-align: right';
				$table1->style[3] = 'text-align: right';
				$table1->style[4] = 'text-align: right';
				$table1->style[5] = 'text-align: right';
				// $table1->style[6] = 'text-align: center';
			}
			
			// Table Planned Downtimes
			require_once ($config['homedir'] . '/include/functions_planned_downtimes.php');
			$metaconsole_on = ($config['metaconsole'] == 1) && defined('METACONSOLE');
			$downtime_malformed = false;
			
			$planned_downtimes_empty = true;
			$malformed_planned_downtimes_empty = true;
			
			if ($metaconsole_on) {
				$id_agent_modules_by_server = array();
				
				foreach ($slas as $sla) {
					$server = $sla['server_name'];
					if (empty($server))
						continue;
					
					if (!isset($id_agent_modules_by_server[$server]))
						$id_agent_modules_by_server[$server] = array();
					
					$id_agent_modules_by_server[$server][] = $sla['id_agent_module'];
				}
				
				$planned_downtimes_by_server = array();
				$malformed_planned_downtimes_by_server = array();
				foreach ($id_agent_modules_by_server as $server => $id_agent_modules) {
					//Metaconsole connection
					if (!empty($server)) {
						$connection = metaconsole_get_connection($server);
						if (!metaconsole_load_external_db($connection)) {
							continue;
						}
						
						$planned_downtimes_by_server[$server] = reporting_get_planned_downtimes(($report['datetime']-$content['period']), $report['datetime'], $id_agent_modules);
						$malformed_planned_downtimes_by_server[$server] = planned_downtimes_get_malformed();
						
						if (!empty($planned_downtimes_by_server[$server]))
							$planned_downtimes_empty = false;
						if (!empty($malformed_planned_downtimes_by_server[$server]))
							$malformed_planned_downtimes_empty = false;
						
						//Restore db connection
						metaconsole_restore_db();
					}
				}
				
				if (!$planned_downtimes_empty) {
					$table_planned_downtimes = new StdClass();
					$table_planned_downtimes->width = '100%';
					$table_planned_downtimes->title = __('This SLA has been affected by the following planned downtimes');
					$table_planned_downtimes->head = array();
					$table_planned_downtimes->head[0] = __('Server');
					$table_planned_downtimes->head[1] = __('Name');
					$table_planned_downtimes->head[2] = __('Description');
					$table_planned_downtimes->head[3] = __('Execution');
					$table_planned_downtimes->head[4] = __('Dates');
					$table_planned_downtimes->headstyle = array();
					$table_planned_downtimes->style = array();
					$table_planned_downtimes->cellstyle = array();
					$table_planned_downtimes->data = array();
					
					foreach ($planned_downtimes_by_server as $server => $planned_downtimes) {
						foreach ($planned_downtimes as $planned_downtime) {
							$data = array();
							$data[0] = $server;
							$data[1] = $planned_downtime['name'];
							$data[2] = $planned_downtime['description'];
							$data[3] = ucfirst($planned_downtime['type_execution']);
							$data[4] = "";
							switch ($planned_downtime['type_execution']) {
								case 'once':
									$data[3] = date ("Y-m-d H:i", $planned_downtime['date_from']) .
										"&nbsp;" . __('to') . "&nbsp;".
										date ("Y-m-d H:i", $planned_downtime['date_to']);
									break;
								case 'periodically':
									switch ($planned_downtime['type_periodicity']) {
										case 'weekly':
											$data[4] = __('Weekly:');
											$data[4] .= "&nbsp;";
											if ($planned_downtime['monday']) {
												$data[4] .= __('Mon');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['tuesday']) {
												$data[4] .= __('Tue');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['wednesday']) {
												$data[4] .= __('Wed');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['thursday']) {
												$data[4] .= __('Thu');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['friday']) {
												$data[4] .= __('Fri');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['saturday']) {
												$data[4] .= __('Sat');
												$data[4] .= "&nbsp;";
											}
											if ($planned_downtime['sunday']) {
												$data[4] .= __('Sun');
												$data[4] .= "&nbsp;";
											}
											$data[4] .= "&nbsp;(" . $planned_downtime['periodically_time_from']; 
											$data[4] .= "-" . $planned_downtime['periodically_time_to'] . ")";
											break;
										case 'monthly':
											$data[4] = __('Monthly:') . "&nbsp;";
											$data[4] .= __('From day') . "&nbsp;" . $planned_downtime['periodically_day_from'];
											$data[4] .= "&nbsp;" . strtolower(__('To day')) . "&nbsp;";
											$data[4] .= $planned_downtime['periodically_day_to'];
											$data[4] .= "&nbsp;(" . $planned_downtime['periodically_time_from'];
											$data[4] .= "-" . $planned_downtime['periodically_time_to'] . ")";
											break;
									}
									break;
							}
							
							if (!$malformed_planned_downtimes_empty
									&& isset($malformed_planned_downtimes_by_server[$server])
									&& isset($malformed_planned_downtimes_by_server[$server][$planned_downtime['id']])) {
								$next_row_num = count($table_planned_downtimes->data);
								$table_planned_downtimes->cellstyle[$next_row_num][0] = 'color: red';
								$table_planned_downtimes->cellstyle[$next_row_num][1] = 'color: red';
								$table_planned_downtimes->cellstyle[$next_row_num][2] = 'color: red';
								$table_planned_downtimes->cellstyle[$next_row_num][3] = 'color: red';
								$table_planned_downtimes->cellstyle[$next_row_num][4] = 'color: red';
								
								if (!$downtime_malformed)
									$downtime_malformed = true;
							}
							
							$table_planned_downtimes->data[] = $data;
						}
					}
				}
			}
			else {
				$id_agent_modules = array();
				foreach ($slas as $sla) {
					if (!empty($sla['id_agent_module']))
						$id_agent_modules[] = $sla['id_agent_module'];
				}
				
				$planned_downtimes = reporting_get_planned_downtimes(($report['datetime']-$content['period']), $report['datetime'], $id_agent_modules);
				$malformed_planned_downtimes = planned_downtimes_get_malformed();
				
				if (!empty($planned_downtimes))
					$planned_downtimes_empty = false;
				if (!empty($malformed_planned_downtimes))
					$malformed_planned_downtimes_empty = false;
				
				if (!$planned_downtimes_empty) {
					$table_planned_downtimes = new StdClass();
					$table_planned_downtimes->width = '100%';
					$table_planned_downtimes->title = __('This SLA has been affected by the following planned downtimes');
					$table_planned_downtimes->head = array();
					$table_planned_downtimes->head[0] = __('Name');
					$table_planned_downtimes->head[1] = __('Description');
					$table_planned_downtimes->head[2] = __('Execution');
					$table_planned_downtimes->head[3] = __('Dates');
					$table_planned_downtimes->headstyle = array();
					$table_planned_downtimes->style = array();
					$table_planned_downtimes->cellstyle = array();
					$table_planned_downtimes->data = array();
					
					foreach ($planned_downtimes as $planned_downtime) {
						
						$data = array();
						$data[0] = $planned_downtime['name'];
						$data[1] = $planned_downtime['description'];
						$data[2] = ucfirst($planned_downtime['type_execution']);
						$data[3] = "";
						switch ($planned_downtime['type_execution']) {
							case 'once':
								$data[3] = date ("Y-m-d H:i", $planned_downtime['date_from']) .
									"&nbsp;" . __('to') . "&nbsp;".
									date ("Y-m-d H:i", $planned_downtime['date_to']);
								break;
							case 'periodically':
								switch ($planned_downtime['type_periodicity']) {
									case 'weekly':
										$data[3] = __('Weekly:');
										$data[3] .= "&nbsp;";
										if ($planned_downtime['monday']) {
											$data[3] .= __('Mon');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['tuesday']) {
											$data[3] .= __('Tue');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['wednesday']) {
											$data[3] .= __('Wed');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['thursday']) {
											$data[3] .= __('Thu');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['friday']) {
											$data[3] .= __('Fri');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['saturday']) {
											$data[3] .= __('Sat');
											$data[3] .= "&nbsp;";
										}
										if ($planned_downtime['sunday']) {
											$data[3] .= __('Sun');
											$data[3] .= "&nbsp;";
										}
										$data[3] .= "&nbsp;(" . $planned_downtime['periodically_time_from']; 
										$data[3] .= "-" . $planned_downtime['periodically_time_to'] . ")";
										break;
									case 'monthly':
										$data[3] = __('Monthly:') . "&nbsp;";
										$data[3] .= __('From day') . "&nbsp;" . $planned_downtime['periodically_day_from'];
										$data[3] .= "&nbsp;" . strtolower(__('To day')) . "&nbsp;";
										$data[3] .= $planned_downtime['periodically_day_to'];
										$data[3] .= "&nbsp;(" . $planned_downtime['periodically_time_from'];
										$data[3] .= "-" . $planned_downtime['periodically_time_to'] . ")";
										break;
								}
								break;
						}
						
						if (!$malformed_planned_downtimes_empty && isset($malformed_planned_downtimes[$planned_downtime['id']])) {
							$next_row_num = count($table_planned_downtimes->data);
							$table_planned_downtimes->cellstyle[$next_row_num][0] = 'color: red';
							$table_planned_downtimes->cellstyle[$next_row_num][1] = 'color: red';
							$table_planned_downtimes->cellstyle[$next_row_num][2] = 'color: red';
							$table_planned_downtimes->cellstyle[$next_row_num][3] = 'color: red';
							
							if (!$downtime_malformed)
								$downtime_malformed = true;
						}
						
						$table_planned_downtimes->data[] = $data;
					}
				}
			}
			
			if ($downtime_malformed) {
				$info_malformed = ui_print_error_message(
					__('This item is affected by a malformed planned downtime') . ". " .
					__('Go to the planned downtimes section to solve this') . ".", '', true);
				
				$data = array();
				$data[0] = $info_malformed;
				$data[0] .= html_print_table($table_planned_downtimes, true);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
				break;
			}
			
			$data_graph = array ();
			// $data_horin_graph = array();
			$data_graph[__('Inside limits')] = 0;
			$data_graph[__('Out of limits')] = 0;
			$data_graph[__('On the edge')] = 0;
			$data_graph[__('Unknown')] = 0;
			// $data_horin_graph[__('Inside limits')] = 0;
			// $data_horin_graph[__('Out of limits')] = 0;
			// $data_horin_graph[__('On the edge')] = 0;
			// $data_horin_graph[__('Unknown')] = 0;
			
			$data_graph[__('Plannified downtime')] = 0;
			
			$urlImage = ui_get_full_url(false, true, false, false);
			
			$sla_failed = false;
			$total_SLA = 0;
			$total_result_SLA = 'ok';
			$sla_showed = array();
			$sla_showed_values = array();
			
			foreach ($slas as $sla) {
				$server_name = $sla ['server_name'];
				//Metaconsole connection
				if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
					$connection = metaconsole_get_connection($server_name);
					if (!metaconsole_load_external_db($connection)) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				if (modules_is_disable_agent($sla['id_agent_module'])) {
					continue;
				}
				
				//Get the sla_value in % and store it on $sla_value
				$sla_value = reporting_get_agentmodule_sla(
					$sla['id_agent_module'], $content['period'],
					$sla['sla_min'], $sla['sla_max'],
					$report["datetime"], $content,
					$content['time_from'], $content['time_to']);
				
				if (($config ['metaconsole'] == 1) && defined('METACONSOLE')) {
					//Restore db connection
					metaconsole_restore_db();
				}
				
				//Do not show right modules if 'only_display_wrong' is active
				if ($content['only_display_wrong'] == 1 &&
					$sla_value >= $sla['sla_limit']) {
					
					continue;
				}
				
				$sla_showed[] = $sla;
				$sla_showed_values[] = $sla_value;
				
			}
			
			// SLA items sorted descending ()
			if ($content['top_n'] == 2) {
				arsort($sla_showed_values);
			}
			// SLA items sorted ascending
			else if ($content['top_n'] == 1) {
				asort($sla_showed_values);
			}
			
			// Slice graphs calculation
			if ($show_graphs && !empty($slas)) {
				$tableslice->width = '99%';
				$tableslice->style[0] = 'text-align: right';
				$tableslice->data = array ();
			}
			
			foreach ($sla_showed_values as $k => $sla_value) {
				$sla = $sla_showed[$k];
				
				$server_name = $sla ['server_name'];
				//Metaconsole connection
				if (($config ['metaconsole'] == 1) && ($server_name != '') && defined('METACONSOLE')) {
					$connection = metaconsole_get_connection($server_name);
					if (metaconsole_connect($connection) != NOERR) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				//Fill the array data_graph for the pie graph
				// if ($sla_value === false) {
				// 	$data_graph[__('Unknown')]++;
				// 	// $data_horin_graph[__('Unknown')]['g']++;
				// }
				// # Fix : 100% accurance is 'inside limits' although 10% was not overrun
				// else if (($sla_value == 100 && $sla_value >= $sla['sla_limit']) ) {
				// 	$data_graph[__('Inside limits')]++;
				// 	$data_horin_graph[__('Inside limits')]['g']++;
				// }
				// else if ($sla_value <= ($sla['sla_limit']+10) && $sla_value >= ($sla['sla_limit']-10)) {
				// 	$data_graph[__('On the edge')]++;
				// 	$data_horin_graph[__('On the edge')]['g']++;
				// }
				// else if ($sla_value > ($sla['sla_limit']+10)) {
				// 	$data_graph[__('Inside limits')]++;
				// 	$data_horin_graph[__('Inside limits')]['g']++;
				// }
				// else if ($sla_value < ($sla['sla_limit']-10)) {
				// 	$data_graph[__('Out of limits')]++;
				// 	$data_horin_graph[__('Out of limits')]['g']++;
				// }
				
				// if ($sla_value === false) {
				// 	if ($total_result_SLA != 'fail')
				// 		$total_result_SLA = 'unknown';
				// }
				// else if ($sla_value < $sla['sla_limit']) {
				// 	$total_result_SLA = 'fail';
				// }
				
				$total_SLA += $sla_value;
				
				if ($show_table) {
					$data = array ();
					$data[0] = modules_get_agentmodule_agent_name ($sla['id_agent_module']);
					$data[1] = modules_get_agentmodule_name ($sla['id_agent_module']);
					$data[2] = $sla['sla_max'].'/';
					$data[2] .= $sla['sla_min'];
					$data[3] = $sla['sla_limit'].'%';
					
					if ($sla_value === false) {
						$data[4] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_UNKNOWN.';">';
						$data[5] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_UNKNOWN.';">'.__('Unknown').'</span>';
						// $data[6] = html_print_image('images/status_sets/default/severity_maintenance.png',true,array('title'=>__('Unknown')));
					}
					else {
						$data[4] = '';
						$data[5] = '';
						// $data[6] = '';
						
						if ($sla_value >= $sla['sla_limit']) {
							$data[4] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_NORMAL.';">';
							$data[5] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_NORMAL.';">'.__('OK').'</span>';
						}
						else {
							$sla_failed = true;
							$data[4] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_CRITICAL.';">';
							$data[5] = '<span style="font: bold '.$sizem.'em Arial, Sans-serif; color: '.COL_CRITICAL.';">'.__('Fail').'</span>';
						}
						
						// Print icon with status including edge
						# Fix : 100% accurance is 'inside limits' although 10% was not overrun
						// if (($sla_value == 100 && $sla_value >= $sla['sla_limit']) || ($sla_value > ($sla['sla_limit'] + $edge_interval))) {
						// 	$data[6] = html_print_image('images/status_sets/default/severity_normal.png',true,array('title'=>__('Inside limits')));
						// }
						// elseif (($sla_value <= $sla['sla_limit'] + $edge_interval)
						// 	&& ($sla_value >= $sla['sla_limit'] - $edge_interval)) {
						// 	$data[6] = html_print_image('images/status_sets/default/severity_warning.png',true,array('title'=>__('On the edge')));
						// }
						// else {
						// 	$data[6] = html_print_image('images/status_sets/default/severity_critical.png',true,array('title'=>__('Out of limits')));
						// }
						
						$data[4] .= format_numeric ($sla_value, 2). "%";
					}
					$data[4] .= "</span>";
					
					array_push ($table1->data, $data);
				}
				
				// Slice graphs calculation
				if ($show_graphs) {
					$dataslice = array();
					$dataslice[0] = modules_get_agentmodule_agent_name ($sla['id_agent_module']);
					$dataslice[0] .= "<br>";
					$dataslice[0] .= modules_get_agentmodule_name ($sla['id_agent_module']);
					
					$dataslice[1] = graph_sla_slicebar ($sla['id_agent_module'], $content['period'],
						$sla['sla_min'], $sla['sla_max'], $report['datetime'], $content, $content['time_from'],
						$content['time_to'], 650, 25, $urlImage, 1, false, false);
					
					array_push ($tableslice->data, $dataslice);
				}
				
				if ($config ['metaconsole'] == 1 && defined('METACONSOLE')) {
					//Restore db connection
					metaconsole_restore_db();
				}
			} 
			
			if ($show_table) {
				$data = array();
				$data[0] = html_print_table($table1, true);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			
			// $data = array();
			// $data_pie_graph = json_encode ($data_graph);
			if ($show_graphs && !empty($slas)) {
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				$data = array();
				$data[0] = html_print_table($tableslice, true);
				array_push ($table->data, $data);
			}
			
			if (!empty($table_planned_downtimes)) {
				$data = array();
				$data[0] = html_print_table($table_planned_downtimes, true);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			break;
		
		
		case 'event_report_group':
			if (empty($item_title)) {
				$item_title = __('Group detailed event');
			}
			reporting_header_content($mini, $content, $report, $table, $item_title,
				ui_print_truncate_text(groups_get_name($content['id_group'], true), 60, false));
				
			$next_row = 1;
			
			// Put description at the end of the module (if exists)
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			$data = array ();
			
			$style = json_decode(io_safe_output($content['style']), true);
			
			$filter_event_no_validated = $style['filter_event_no_validated'];
			$filter_event_validated = $style['filter_event_validated'];
			$filter_event_critical = $style['filter_event_critical'];
			$filter_event_warning = $style['filter_event_warning'];
			
			$event_graph_by_agent = $style['event_graph_by_agent'];
			$event_graph_by_user_validator = $style['event_graph_by_user_validator'];
			$event_graph_by_criticity = $style['event_graph_by_criticity'];
			$event_graph_validated_vs_unvalidated = $style['event_graph_validated_vs_unvalidated'];
			
			$data[0] = reporting_get_group_detailed_event(
				$content['id_group'], $content['period'],
				$report["datetime"], true, true,
				$filter_event_validated,
				$filter_event_critical,
				$filter_event_warning,
				$filter_event_no_validated);
			if(!empty($data[0])) {
				array_push ($table->data, $data);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
			}
			
			if ($event_graph_by_agent) {
				$data_graph = reporting_get_count_events_by_agent(
					$content['id_group'], $content['period'],
					$report["datetime"],
					$filter_event_validated,
					$filter_event_critical,
					$filter_event_warning,
					$filter_event_no_validated);
				
				$table_event_graph = null;
				$table_event_graph->width = '100%';
				$table_event_graph->style[0] = 'text-align: center;';
				$table_event_graph->head[0] = __('Events by agent');
				
				$table_event_graph->data[0][0] = pie3d_graph(
					false, $data_graph, 500, 150, __("other"), "",
					ui_get_full_url(false, false, false, false) . "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']);
				
				$data[0] = html_print_table($table_event_graph, true);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			
			if ($event_graph_by_user_validator) {
				$data_graph = reporting_get_count_events_validated_by_user(
					array('id_group' => $content['id_group']), $content['period'],
					$report["datetime"],
					$filter_event_validated,
					$filter_event_critical,
					$filter_event_warning,
					$filter_event_no_validated);
				
				$table_event_graph = null;
				$table_event_graph->head[0] = __('Events validated by user');
				$table_event_graph->width = '100%';
				$table_event_graph->style[0] = 'text-align: center;';
				
				$table_event_graph->data[0][0] = pie3d_graph(
					false, $data_graph, 500, 150, __("other"), "",
					ui_get_full_url(false, false, false, false) . "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']);
				
				$data[0] = html_print_table($table_event_graph, true);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			
			if ($event_graph_by_criticity) {
				$data_graph = reporting_get_count_events_by_criticity(
					array('id_group' => $content['id_group']), $content['period'],
					$report["datetime"],
					$filter_event_validated,
					$filter_event_critical,
					$filter_event_warning,
					$filter_event_no_validated);
				
				$colors = get_criticity_pie_colors($data_graph);
				
				$table_event_graph = null;
				$table_event_graph->head[0] = __('Events by criticity');
				$table_event_graph->width = '100%';
				$table_event_graph->style[0] = 'text-align: center;';
				
				$table_event_graph->data[0][0] = pie3d_graph(
					false, $data_graph, 500, 150, __("other"), "",
					ui_get_full_url(false, false, false, false) .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size'], 1, false, $colors);
				
				$data[0] = html_print_table($table_event_graph, true);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			
			if ($event_graph_validated_vs_unvalidated) {
				$data_graph = reporting_get_count_events_validated(
					array('id_group' => $content['id_group']), $content['period'],
					$report["datetime"],
					$filter_event_validated,
					$filter_event_critical,
					$filter_event_warning,
					$filter_event_no_validated);
				
				$table_event_graph = null;
				$table_event_graph->head[0] = __('Amount events validated');
				$table_event_graph->width = '100%';
				$table_event_graph->style[0] = 'text-align: center;';
				
				$table_event_graph->data[0][0] = pie3d_graph(
					false, $data_graph, 500, 150, __("other"), "",
					ui_get_full_url(false, false, false, false) .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']);
				
				$data[0] = html_print_table($table_event_graph, true);
				
				$table->colspan[$next_row][0] = 3;
				$next_row++;
				array_push ($table->data, $data);
			}
			break;
		
		case 'top_n':
			$top_n = $content['top_n'];
			
			switch ($top_n) {
				case REPORT_TOP_N_MAX:
					$type_top_n = __('Max');
					break;
				case REPORT_TOP_N_MIN:
					$type_top_n = __('Min');
					break;
				case REPORT_TOP_N_AVG:
				default:
					//If nothing is selected then it will be shown the average data
					$type_top_n = __('Avg');
					break;
			}
			
			if (empty($item_title)) {
				$item_title = 'Top '.$content['top_n_value'] . '<br>' . $type_top_n;
			}
			reporting_header_content($mini, $content, $report, $table, $item_title);
			
			$order_uptodown = $content['order_uptodown'];
			
			$top_n_value = $content['top_n_value'];
			$show_graph = $content['show_graph'];
			
			$table->style[0] = 'padding: 8px 5px 8px 5px;';
			$table->style[1] = 'text-align: right';
			
			// Put description at the end of the module (if exists)
			$table->colspan[1][0] = 3;
			if ($content["description"] != "") {
				$data_desc = array();
				$data_desc[0] = $content["description"];
				array_push ($table->data, $data_desc);
			}
			//Get all the related data
			$sql = sprintf("SELECT id_agent_module, server_name
				FROM treport_content_item
				WHERE id_report_content = %d", $content['id_rc']);
			
			$tops = db_process_sql ($sql);
			
			if ($tops === false) {
				$data = array ();
				$table->colspan[2][0] = 3;
				$data[0] = __('There are no Agent/Modules defined');
				array_push ($table->data, $data);
				break;
			}
			
			if ($show_graph != REPORT_TOP_N_ONLY_GRAPHS) {
				$table1->width = '99%';
				$table1->data = array ();
				$table1->head = array ();
				$table1->head[0] = __('Agent');
				$table1->head[1] = __('Module');
				$table1->head[2] = __('Value');
				$table1->style[0] = 'text-align: left';
				$table1->style[1] = 'text-align: left';
				$table1->style[2] = 'text-align: left';
			}
			
			//Get data of all agents (before to slide to N values)
			$data_top = array();
			
			foreach ($tops as $key => $row) {
				
				//Metaconsole connection
				$server_name = $row['server_name'];
				if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
					$connection = metaconsole_get_connection($server_name);
					if (metaconsole_load_external_db($connection) != NOERR) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				$ag_name = modules_get_agentmodule_agent_name($row ['id_agent_module']); 
				$mod_name = modules_get_agentmodule_name ($row ['id_agent_module']);
				$unit = db_get_value('unit', 'tagente_modulo',
					'id_agente_modulo', $row ['id_agent_module']); 
				
				
				switch ($top_n) {
					case REPORT_TOP_N_MAX:
						$value = reporting_get_agentmodule_data_max ($row['id_agent_module'], $content['period']);
						break;
					case REPORT_TOP_N_MIN:
						$value = reporting_get_agentmodule_data_min ($row['id_agent_module'], $content['period']);
						break;
					case REPORT_TOP_N_AVG:
					default:
						//If nothing is selected then it will be shown the average data
						$value = reporting_get_agentmodule_data_average ($row['id_agent_module'], $content['period']);
						break;
				}
				
				//If the returned value from modules_get_agentmodule_data_max/min/avg is false it won't be stored.
				if ($value !== false) {
					$data_top[$key] = $value;
					$id_agent_module[$key] = $row['id_agent_module'];
					$agent_name[$key] = $ag_name;
					$module_name[$key] = $mod_name;
					$units[$key] = $unit;
				}
				
				//Restore dbconnection
				if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE')) {
					metaconsole_restore_db();
				}
			}
			
			if (empty($data_top)) {
				$data = array ();
				$table->colspan[2][0] = 3;
				$data[0] = __('Insuficient data');
				array_push ($table->data, $data);
				break;
			}
			
			//Order to show.
			switch ($order_uptodown) {
				//Descending
				case 1:
					array_multisort($data_top, SORT_DESC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
					break;
				//Ascending
				case 2:
					array_multisort($data_top, SORT_ASC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
					break;
				//By agent name or without selection
				case 0:
				case 3:
					array_multisort($agent_name, SORT_ASC, $data_top, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
					break;
			}
			
			array_splice ($data_top, $top_n_value);
			array_splice ($agent_name, $top_n_value);
			array_splice ($module_name, $top_n_value);
			array_splice ($id_agent_module, $top_n_value);
			array_splice ($units, $top_n_value);
			
			$data_top_values = array ();
			$data_top_values['data_top'] = $data_top;
			$data_top_values['agent_name'] = $agent_name;
			$data_top_values['module_name'] = $module_name; 
			$data_top_values['id_agent_module'] = $id_agent_module;
			$data_top_values['units'] = $units;
			
			// Define truncate size depends the graph width
			$truncate_size = $sizgraph_w / (4 * ($config['font_size']))-1;
			
			if ($order_uptodown == 1 || $order_uptodown == 2) {
				$i = 0;
				$data_pie_graph = array();
				$data_hbar = array();
				foreach ($data_top as $dt) {
					$item_name = '';
					$item_name =
						ui_print_truncate_text($agent_name[$i], $truncate_size, false, true, false, "...") .
						' - ' . 
						ui_print_truncate_text($module_name[$i], $truncate_size, false, true, false, "...");
					
					
					
					//Dirty hack, maybe I am going to apply a job in Apple
					//https://www.imperialviolet.org/2014/02/22/applebug.html
					$item_name_key_pie = $item_name;
					$exist_key = true;
					while ($exist_key) {
						if (isset($data_pie_graph[$item_name_key_pie])) {
							$item_name_key_pie .= ' ';
						}
						else {
							$exist_key = false;
						}
					}
					$item_name_key_hbar = $item_name;
					$exist_key = true;
					while ($exist_key) {
						if (isset($data_hbar[$item_name_key_hbar])) {
							$item_name_key_hbar = ' ' . $item_name_key_hbar;
						}
						else {
							$exist_key = false;
						}
					}
					
					
					
					$data_hbar[$item_name]['g'] = $dt; 
					$data_pie_graph[$item_name] = $dt;
					
					if ($show_graph == 0 || $show_graph == 1) {
						$data = array();
						$data[0] = $agent_name[$i];
						$data[1] = $module_name[$i];
						
						$data[2] = format_for_graph($dt,2) . " " . $units[$i];
						array_push ($table1->data, $data);
					}
					$i++;
					if ($i >= $top_n_value) break;
				} 
			}
			else if ($order_uptodown == 0 || $order_uptodown == 3) {
				$i = 0;
				$data_pie_graph = array();
				$data_hbar = array();
				foreach ($agent_name as $an) {
					$item_name = '';
					$item_name =
						ui_print_truncate_text($agent_name[$i],
							$truncate_size, false, true, false, "...") .
						' - ' . 
						ui_print_truncate_text($module_name[$i],
							$truncate_size, false, true, false, "...");
					
					
					
					//Dirty hack, maybe I am going to apply a job in Apple
					//https://www.imperialviolet.org/2014/02/22/applebug.html
					$item_name_key_pie = $item_name;
					$exist_key = true;
					while ($exist_key) {
						if (isset($data_pie_graph[$item_name_key_pie])) {
							$item_name_key_pie .= ' ';
						}
						else {
							$exist_key = false;
						}
					}
					$item_name_key_hbar = $item_name;
					$exist_key = true;
					while ($exist_key) {
						if (isset($data_hbar[$item_name_key_hbar])) {
							$item_name_key_hbar = ' ' . $item_name_key_hbar;
						}
						else {
							$exist_key = false;
						}
					}
					
					
					
					$data_pie_graph[$item_name] = $data_top[$i];
					$data_hbar[$item_name]['g'] = $data_top[$i];
					if  ($show_graph == 0 || $show_graph == 1) {
						$data = array();
						$data[0] = $an;
						$data[1] = $module_name[$i];
						$data[2] = format_for_graph($data_top[$i],2) . " " . $units[$i];
						array_push ($table1->data, $data);
					}
					$i++;
					if ($i >= $top_n_value) break;
				}
			}
			
			unset($data);
			$table->colspan[2][0] = 3;
			$table->style[2] = 'text-align:center';
			if ($show_graph == 0 || $show_graph == 1) {
				$data = array();
				$data[0] = html_print_table($table1, true);
				array_push ($table->data, $data);
			}
			
			$table->colspan[3][0] = 3;
			$data = array();
			if ($show_graph != REPORT_TOP_N_ONLY_TABLE) {
				
				$data[0] = pie3d_graph(false, $data_pie_graph,
					$sizgraph_w, $sizgraph_h, __("other"),
					ui_get_full_url(false, true, false, false) . '/',
					ui_get_full_url(false, false, false, false) .  "/images/logo_vertical_water.png",
					$config['fontpath'], $config['font_size']);
				
				
				array_push ($table->data, $data);
				//Display bars graph
				$table->colspan[4][0] = 3;
				$table->style[0] .= 'text-align:center';
				$height = count($data_pie_graph)*20+35;
				$data = array();
				$data[0] = hbar_graph(false, $data_hbar, $sizgraph_w,
					$height, array(), array(), "", "", true,
					ui_get_full_url(false, true, false, false) . '/', $config['homedir'] .  "/images/logo_vertical_water.png", $config['fontpath'], $config['font_size'], true, 1, true);
				
				array_push ($table->data, $data);
			}
			
			if ($content['show_resume'] && count($data_top_values) > 0) {
				//Get the very first not null value 
				$i=0;
				do {
					$min = $data_top_values['data_top'][$i];
					$i++;
				}
				while ($min === false && $i < count($data_top_values));
				$max = $min;
				$avg = 0;
				
				$i=0;
				foreach ($data_top_values['data_top'] as $key => $dtv) {
					if ($dtv < $min) $min = $dtv;
					if ($dtv > $max) $max = $dtv;
					$avg += $dtv;
					$i++;
				}
				$avg = $avg / $i;
				
				unset($table_summary);
				
				$table_summary->width = '99%';
				$table_summary->data = array ();
				$table_summary->head = array ();
				$table_summary->head[0] = __('Min Value');
				$table_summary->head[1] = __('Average Value');
				$table_summary->head[2] = __('Max Value');
				
				$table_summary->data[0][0] = format_for_graph($min, 2);
				$table_summary->data[0][1] = format_for_graph($avg, 2);
				$table_summary->data[0][2] = format_for_graph($max, 2);
				
				$table->colspan[5][0] = 3;
				array_push ($table->data, array('<b>'.__('Summary').'</b>'));
				$table->colspan[6][0] = 3;
				array_push ($table->data, array(html_print_table($table_summary, true)));
			}
			break;
		
		
	}
	//Restore dbconnection
	if (($config ['metaconsole'] == 1) && $server_name != '' && defined('METACONSOLE') && $remote_connection == 1) {
		metaconsole_restore_db_force();
	}
}




/**
 * Get all the template graphs a user can see.
 *
 * @param $id_user User id to check.
 * @param $only_names Wheter to return only graphs names in an associative array
 * or all the values.
 * @param $returnAllGroup Wheter to return graphs of group All or not.
 * @param $privileges Privileges to check in user group
 *
 * @return template graphs of a an user. Empty array if none.
 */
function reporting_template_graphs_get_user ($id_user = 0, $only_names = false, $returnAllGroup = true, $privileges = 'RR') {
	global $config;
	
	if (!$id_user) {
		$id_user = $config['id_user'];
	}
	
	$groups = users_get_groups ($id_user, $privileges, $returnAllGroup);
	
	$all_templates = db_get_all_rows_in_table ('tgraph_template', 'name');
	if ($all_templates === false)
		return array ();
	
	$templates = array ();
	foreach ($all_templates as $template) {
		if (!in_array($template['id_group'], array_keys($groups)))
			continue;
		
		if ($template["id_user"] != $id_user && $template['private'])
			continue;
		
		if ($template["id_group"] > 0)
			if (!isset($groups[$template["id_group"]])){
				continue;
			}
		
		if ($only_names) {
			$templates[$template['id_graph_template']] = $template['name'];
		}
		else {
			$templates[$template['id_graph_template']] = $template;
			$templatesCount = db_get_value_sql("SELECT COUNT(id_gs_template) FROM tgraph_source_template WHERE id_template = " . $template['id_graph_template']);
			$templates[$template['id_graph_template']]['graphs_template_count'] = $templatesCount;
		}
	}
	
	return $templates;
}

/**
 * Gets a detailed reporting of groups's events.  
 *
 * @param unknown_type $id_group Id of the group.
 * @param unknown_type $period Time period of the report.
 * @param unknown_type $date Date of the report.
 * @param unknown_type $return Whether to return or not.
 * @param unknown_type $html Whether to return HTML code or not.
 *
 * @return string Report of groups's events
 */
function reporting_get_count_events_by_agent ($id_group, $period = 0,
	$date = 0,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	return events_get_count_events_by_agent($id_group, $period, $date,
		$filter_event_validated, $filter_event_critical,
		$filter_event_warning, $filter_event_no_validated);
}

/**
 * Gets a detailed reporting of groups's events.  
 *
 * @param unknown_type $filter.
 * @param unknown_type $period Time period of the report.
 * @param unknown_type $date Date of the report.
 * @param unknown_type $return Whether to return or not.
 * @param unknown_type $html Whether to return HTML code or not.
 *
 * @return string Report of groups's events
 */
function reporting_get_count_events_validated_by_user ($filter, $period = 0,
	$date = 0,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	return events_get_count_events_validated_by_user($filter, $period, $date,
		$filter_event_validated, $filter_event_critical,
		$filter_event_warning, $filter_event_no_validated);
}

/**
 * Gets a detailed reporting of groups's events.  
 *
 * @param unknown_type $id_group Id of the group.
 * @param unknown_type $period Time period of the report.
 * @param unknown_type $date Date of the report.
 * @param unknown_type $return Whether to return or not.
 * @param unknown_type $html Whether to return HTML code or not.
 *
 * @return string Report of groups's events
 */
function reporting_get_count_events_by_criticity ($filter, $period = 0,
	$date = 0,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	return events_get_count_events_by_criticity($filter, $period, $date,
		$filter_event_validated, $filter_event_critical,
		$filter_event_warning, $filter_event_no_validated);
}

/**
 * Gets a detailed reporting of groups's events.  
 *
 * @param unknown_type $id_group Id of the group.
 * @param unknown_type $period Time period of the report.
 * @param unknown_type $date Date of the report.
 * @param unknown_type $return Whether to return or not.
 * @param unknown_type $html Whether to return HTML code or not.
 *
 * @return string Report of groups's events
 */
function reporting_get_count_events_validated ($filter, $period = 0,
	$date = 0,
	$filter_event_validated = false, $filter_event_critical = false,
	$filter_event_warning = false, $filter_event_no_validated = false) {
	
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}
	
	return events_get_count_events_validated($filter, $period, $date,
		$filter_event_validated, $filter_event_critical,
		$filter_event_warning, $filter_event_no_validated);
}

function reporting_get_agents_by_status ($data, $graph_width = 250, $graph_height = 150, $links = false) {
	global $config;
	
	if ($links == false) {
		$links = array();
	}
	
	$table_agent = html_get_predefined_table();
	
	$agent_data = array();
	$agent_data[0] = html_print_image('images/agent_critical.png', true, array('title' => __('Agents critical')));
	$agent_data[1] = "<a style='color: #FC4444;' href='" . $links['agents_critical'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #FC4444;'>".format_numeric($data['agent_critical'])."</span></b></a>";
	
	$agent_data[2] = html_print_image('images/agent_warning.png', true, array('title' => __('Agents warning')));
	$agent_data[3] = "<a style='color: #FAD403;' href='" . $links['agents_warning'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #FAD403;'>".format_numeric($data['agent_warning'])."</span></b></a>";
	
	$table_agent->data[] = $agent_data;
	
	$agent_data = array();
	$agent_data[0] = html_print_image('images/agent_ok.png', true, array('title' => __('Agents ok')));
	$agent_data[1] = "<a style='color: #80BA27;' href='" . $links['agents_ok'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #80BA27;'>".format_numeric($data['agent_ok'])."</span></b></a>";
	
	$agent_data[2] = html_print_image('images/agent_unknown.png', true, array('title' => __('Agents unknown')));
	$agent_data[3] = "<a style='color: #B2B2B2;' href='" . $links['agents_unknown'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #B2B2B2;'>".format_numeric($data['agent_unknown'])."</span></b></a>";
	
	$table_agent->data[] = $agent_data;
	
	$agent_data = array();
	$agent_data[0] = html_print_image('images/agent_notinit.png', true, array('title' => __('Agents not init')));
	$agent_data[1] = "<a style='color: #5BB6E5;' href='" . $links['agents_not_init'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #5BB6E5;'>".format_numeric($data['agent_not_init'])."</span></b></a>";
	
	$agent_data[2] = "";
	$agent_data[3] = "";
	$table_agent->data[] = $agent_data;
	
	
	if (!defined('METACONSOLE')) {
		$agents_data = '<fieldset class="databox tactical_set">
					<legend>' . 
						__('Agents by status') . 
					'</legend>' . 
					html_print_table($table_agent, true) . '</fieldset>';
	}
	else{
		$table_agent->style=array();
		$table_agent->class = "tactical_view";
		$agents_data = '<fieldset class="tactical_set">
					<legend>' . 
						__('Agents by status') . 
					'</legend>' . 
					html_print_table($table_agent, true) . '</fieldset>';
	}
	
	return $agents_data;
}

function reporting_get_total_agents_and_monitors ($data, $graph_width = 250, $graph_height = 150) {
	global $config;
	
	$total_agent = $data['agent_ok'] + $data['agent_warning'] + $data['agent_critical'] + $data['gent_unknown'] + $data['agent_not_init'];
	$total_module = $data['monitor_ok'] + $data['monitor_warning'] + $data['monitor_critical'] + $data['monitor_unknown'] + $data['monitor_not_init'];
			
	$table_total = html_get_predefined_table();
		
	$total_data = array();
	$total_data[0] = html_print_image('images/agent.png', true, array('title' => __('Total agents')));
	$total_data[1] = $total_agent <= 0 ? '-' : $total_agent;
	$total_data[2] = html_print_image('images/module.png', true, array('title' => __('Monitor checks')));
	$total_data[3] = $total_module <= 0 ? '-' : $total_module;
	$table_total->data[] = $total_data;
	$total_agent_module = '<fieldset class="databox tactical_set">
					<legend>' . 
						__('Total agents and monitors') . 
					'</legend>' . 
					html_print_table($table_total, true) . '</fieldset>';
					
	return $total_agent_module;	
}

function reporting_get_total_servers ($num_servers) {
	global $config;
	
	$table_node = html_get_predefined_table();
		
	$node_data = array();
	$node_data[0] = html_print_image('images/server_export.png', true, array('title' => __('Nodes')));
	$node_data[1] = "<b><span style='font-size: 12pt; font-weight: bold; color: black;'>".format_numeric($num_servers)."</span></b>";
	$table_node->data[] = $node_data;
	
	if (!defined('METACONSOLE')){
		$node_overview = '<fieldset class="databox tactical_set">
					<legend>' . 
						__('Node overview') . 
					'</legend>' . 
					html_print_table($table_node, true) . '</fieldset>';
	}else{
		$table_node->style = array();
		$table_node->class = "tactical_view";
		$node_overview = '<fieldset class="tactical_set">
					<legend>' . 
						__('Node overview') . 
					'</legend>' . 
					html_print_table($table_node, true) . '</fieldset>';
	}
	
	return $node_overview;
}

function reporting_get_events ($data, $links = false) {
	global $config;
	
	$table_events->width = "100%";
	if (defined('METACONSOLE'))
		$style = " vertical-align:middle;";
	else
		$style = "";
	if (defined('METACONSOLE')){
		$table_events->style[0] = "background-color:#FC4444";
		$table_events->data[0][0] = html_print_image('images/module_event_critical.png', true, array('title' => __('Critical events')));
		$table_events->data[0][0] .= "&nbsp;&nbsp;&nbsp;" .
			"<a style='color:#FFF; font-size: 12pt; font-weight: bold;" . $style . "' href='" . $links['critical'] . "'>" . format_numeric($data['critical'])."</a>";
		$table_events->style[1] = "background-color:#FAD403";
		$table_events->data[0][1] = html_print_image('images/module_event_warning.png', true, array('title' => __('Warning events')));
		$table_events->data[0][1] .= "&nbsp;&nbsp;&nbsp;" .
			"<a style='color:#FFF; font-size: 12pt; font-weight: bold;" . $style . "' href='" . $links['warning'] . "'>" . format_numeric($data['warning'])."</a>";
		$table_events->style[2] = "background-color:#80BA27";
		$table_events->data[0][2] = html_print_image('images/module_event_ok.png', true, array('title' => __('OK events')));
		$table_events->data[0][2] .= "&nbsp;&nbsp;&nbsp;" .
			"<a style='color:#FFF; font-size: 12pt; font-weight: bold;" . $style . "' href='" . $links['normal'] . "'>" . format_numeric($data['normal'])."</a>";
		$table_events->style[3] = "background-color:#B2B2B2";
		$table_events->data[0][3] = html_print_image('images/module_event_unknown.png', true, array('title' => __('Unknown events')));
		$table_events->data[0][3] .= "&nbsp;&nbsp;&nbsp;" .
			"<a style='color:#FFF; font-size: 12pt; font-weight: bold;" . $style . "' href='" . $links['unknown'] . "'>" . format_numeric($data['unknown'])."</a>";
		}
	else{
		$table_events->data[0][0] = html_print_image('images/module_critical.png', true, array('title' => __('Critical events')));
		$table_events->data[0][0] .= "&nbsp;&nbsp;&nbsp;" .
			"<a style='color: #FC4444;" . $style . "' href='" . $links['critical'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #FC4444;'>".
						format_numeric($data['critical'])."</span></b></a>";
		$table_events->data[0][1] = html_print_image('images/module_warning.png', true, array('title' => __('Warning events')));
		$table_events->data[0][1] .= "&nbsp;&nbsp;&nbsp;" .
			"<a style='color: #FAD403;" . $style . "' href='" . $links['warning'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #FAD403;'>".
						format_numeric($data['warning'])."</span></b></a>";
		$table_events->data[0][2] = html_print_image('images/module_ok.png', true, array('title' => __('OK events')));
		$table_events->data[0][2] .= "&nbsp;&nbsp;&nbsp;" .
			"<a style='color: #80BA27;" . $style . "' href='" . $links['normal'] . "'><b style='font-size: 12pt; font-weight: bold; color: #80BA27;'>".
						format_numeric($data['normal'])."</b></a>";
		$table_events->data[0][3] = html_print_image('images/module_unknown.png', true, array('title' => __('Unknown events')));
		$table_events->data[0][3] .= "&nbsp;&nbsp;&nbsp;" .
			"<a style='color: #B2B2B2;" . $style . "' href='" . $links['unknown'] . "'><b><span style='font-size: 12pt; font-weight: bold; color: #B2B2B2;'>".
						format_numeric($data['unknown'])."</span></b></a>";

	}
	if (!defined('METACONSOLE')) {
		$event_view = '<fieldset class="databox tactical_set">
					<legend>' . 
						__('Events by criticity') . 
					'</legend>' . 
					html_print_table($table_events, true) . '</fieldset>';
	}
	else{
		$table_events->class="tactical_view";
		$table_events->styleTable="text-align:center;";
		$table_events->size[0]="10%";
		$table_events->size[1]="10%";
		$table_events->size[2]="10%";
		$table_events->size[3]="10%";
		
		$event_view = '<fieldset class="tactical_set">
					<legend>' . 
						__('Important Events by Criticity') . 
					'</legend>' . 
					html_print_table($table_events, true) . '</fieldset>';
	}
	
	return $event_view;
}

function reporting_get_last_activity() {
	global $config;
	
	// Show last activity from this user
	
	$table->width = '100%';
	$table->data = array ();
	$table->size = array ();
	$table->size[2] = '150px';
	$table->size[3] = '130px';
	$table->size[5] = '200px';
	$table->head = array ();
	$table->head[0] = __('User');
	$table->head[1] = '';
	$table->head[2] = __('Action');
	$table->head[3] = __('Date');
	$table->head[4] = __('Source IP');
	$table->head[5] = __('Comments');
	$table->title = '<span>' . __('Last activity in Pandora FMS console') . '</span>';
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("SELECT id_usuario,accion,fecha,ip_origen,descripcion,utimestamp
				FROM tsesion
				WHERE (`utimestamp` > UNIX_TIMESTAMP(NOW()) - " . SECONDS_1WEEK . ") 
					AND `id_usuario` = '%s' ORDER BY `utimestamp` DESC LIMIT 5", $config["id_user"]);
			break;
		case "postgresql":
			$sql = sprintf ("SELECT \"id_usuario\", accion, fecha, \"ip_origen\", descripcion, utimestamp
				FROM tsesion
				WHERE (\"utimestamp\" > ceil(date_part('epoch', CURRENT_TIMESTAMP)) - " . SECONDS_1WEEK . ") 
					AND \"id_usuario\" = '%s' ORDER BY \"utimestamp\" DESC LIMIT 5", $config["id_user"]);
			break;
		case "oracle":
			$sql = sprintf ("SELECT id_usuario, accion, fecha, ip_origen, descripcion, utimestamp
				FROM tsesion
				WHERE ((utimestamp > ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (" . SECONDS_1DAY . ")) - " . SECONDS_1WEEK . ") 
					AND id_usuario = '%s') AND rownum <= 10 ORDER BY utimestamp DESC", $config["id_user"]);
			break;
	}
	
	$sessions = db_get_all_rows_sql ($sql);
	
	if ($sessions === false)
		$sessions = array (); 
	
	foreach ($sessions as $session) {
		$data = array ();
		
		switch ($config["dbtype"]) {
			case "mysql":
			case "oracle":
				$session_id_usuario = $session['id_usuario'];
				$session_ip_origen = $session['ip_origen'];
				break;
			case "postgresql":
				$session_id_usuario = $session['id_usuario'];
				$session_ip_origen = $session['ip_origen'];
				break;
		}
		
		
		$data[0] = '<strong>' . $session_id_usuario . '</strong>';
		$data[1] = ui_print_session_action_icon ($session['accion'], true);
		$data[2] = $session['accion'];
		$data[3] =  ui_print_help_tip($session['fecha'], true) . human_time_comparation($session['utimestamp'], 'tiny');
		$data[4] = $session_ip_origen;
		$data[5] = io_safe_output ($session['descripcion']);
		
		array_push ($table->data, $data);
	}
	
	if(defined("METACONSOLE"))
		$table->class="databox_tactical";
		
	return html_print_table ($table, true);
	
}

function reporting_get_event_histogram ($events) {
	global $config;
	include_once ('../../include/graphs/functions_gd.php');
	$max_value = count($events);
	
	if (defined("METACONSOLE"))
		$max_value = SECONDS_1HOUR;
	
	
	$ttl = 1;
	$urlImage = ui_get_full_url(false, true, false, false);
	
	$colors = array(
		EVENT_CRIT_MAINTENANCE => COL_MAINTENANCE,
		EVENT_CRIT_INFORMATIONAL => COL_INFORMATIONAL,
		EVENT_CRIT_NORMAL => COL_MINOR,
		EVENT_CRIT_MINOR => COL_NORMAL,
		EVENT_CRIT_WARNING => COL_WARNING,
		EVENT_CRIT_MAJOR => COL_MAJOR,
		EVENT_CRIT_CRITICAL => COL_CRITICAL
	);
	
	if (defined("METACONSOLE")) {
		$full_legend = array();
		$cont = 0;
	}
	
	foreach ($events as $data) {
	
		switch ($data['criticity']) {
			case 0:
				$color = EVENT_CRIT_MAINTENANCE;
			break;
			case 1:
				$color = EVENT_CRIT_INFORMATIONAL;
			break;
			case 2:
				$color = EVENT_CRIT_NORMAL;
			break;
			case 3:
				$color = EVENT_CRIT_WARNING;
			break;
			case 4:
				$color = EVENT_CRIT_CRITICAL;
			break;
			case 5:
				$color = EVENT_CRIT_MINOR;
			break;
			case 6:
				$color = EVENT_CRIT_MAJOR;
			break;
			case 20:
				$color = EVENT_CRIT_NOT_NORMAL;
			break;
			case 34:
				$color = EVENT_CRIT_WARNING_OR_CRITICAL;
			break;
		}
		
		if (defined("METACONSOLE")) {
			$full_legend[$cont] = $data['timestamp'];
			$graph_data[] = array(
				'data' => $color,
				'utimestamp' => $data['utimestamp'] - get_system_time ()
				);
			$cont++;
		}
		else {
			$graph_data[] = array(
				'data' => $color,
				'utimestamp' => 1
				);
		}
	}
	
	$table->width = '100%';
	$table->data = array ();
	$table->size = array ();
	$table->head = array ();
	$table->title = '<span>' . __('Events info (1hr.)') . '</span>';
	$table->data[0][0] = "" ;
	
	if (!empty($graph_data)) {
		if (defined("METACONSOLE"))
			$slicebar = flot_slicesbar_graph($graph_data, $max_value, "100%", 35, $full_legend, $colors, $config['fontpath'], $config['round_corner'], $url);
		else
			$slicebar = slicesbar_graph($graph_data, $max_value, 700, 25, $colors, $config['fontpath'], $config['round_corner'], $urlImage, $ttl);
		
		$table->data[0][0] = $slicebar;
	}
	else {
		$table->data[0][0] = __('No events');
	}
	
	if (!defined('METACONSOLE')) {
		$event_graph = '<fieldset class="databox tactical_set">
					<legend>' . 
						__('Events info (1hr)') . 
					'</legend>' . 
					html_print_table($table, true) . '</fieldset>';
	}
	else{
		$table->class='tactical_view';
		$event_graph = '<fieldset id="event_tactical" class="tactical_set">' . 
					html_print_table($table, true) . '</fieldset>';
	}
	
	return $event_graph;
}
?>
