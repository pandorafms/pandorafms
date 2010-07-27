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

if (! give_acl ($config['id_user'], 0, "IW")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once ("include/functions_reports.php");

$enterpriseEnable = false;
if (enterprise_include_once('include/functions_reporting.php') !== ENTERPRISE_NOT_HOOK) {
	$enterpriseEnable = true;
}


$activeTab = get_parameter('tab', 'main');
$action = get_parameter('action', 'list');
$idReport = get_parameter('id_report', 0);
$offset = get_parameter('offset', 0);
$idItem = get_parameter('id_item', 0);

switch ($action) {
	case 'delete_report':
	case 'list':
		// Report LIST
		print_page_header (__('Reporting').' &raquo; '.__('Custom reporting'), "images/reporting_edit.png", false, "", true);
		
		if ($action == 'delete_report') {
			$result = delete_report ($idReport);
			print_result_message ($result,
				__('Successfully deleted'),
				__('Could not be deleted'));
		}
	
		$reports = get_reports (array ('order' => 'name'),
			array ('name', 'id_report', 'description', 'private', 'id_user', 'id_group'));
		$table->width = '0px';
		if (sizeof ($reports)) {
			$table->id = 'report_list';
			$table->width = '720px';
			$table->head = array ();
			$table->align = array ();
			$table->align[2] = 'center';
			$table->align[3] = 'center';
			$table->align[4] = 'center';
			$table->data = array ();
			$table->head[0] = __('Report name');
			$table->head[1] = __('Description');
			$table->head[2] = __('Private');
			$table->head[3] = __('Group');
			$table->head[4] = __('Delete');
			$table->size = array ();
			$table->size[4] = '40px';
			
			foreach ($reports as $report) {
			
				if (!is_user_admin ($config["id_user"])){
					if ($report["private"] && $report["id_user"] != $config['id_user'])
						if (!give_acl ($config["id_user"], $report["id_group"], "AW"))
							continue;
					if (!give_acl ($config["id_user"], $report["id_group"], "AW"))
						continue;
				}
	
				$data = array ();
				$data[0] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&action=edit&id_report='.
						$report['id_report'].'">'.$report['name'].'</a>';
				$data[1] = $report['description'];
				if ($report["private"] == 1)
					$data[2] = __('Yes');
				else
					$data[2] = __('No');
					
				$data[3] = print_group_icon($report['id_group'], true);
				$data[4] = '<form method="post" style="display:inline" onsubmit="if (!confirm (\''.__('Are you sure?').'\')) return false">';
				$data[4] .= print_input_hidden ('id_report', $report['id_report'], true);
				$data[4] .= print_input_hidden ('action','delete_report', true);
				$data[4] .= print_input_image ('delete', 'images/cross.png', 1, '',
					true, array ('title' => __('Delete')));
					$data[4] .= '</form>';
				
				array_push ($table->data, $data);
							
			}
			print_table ($table);
		} else {
			echo "<div class='nf'>".__('There are no defined reportings')."</div>";
		}
		
		echo '<form method="post" action="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=main&action=new">';
		echo '<div class="action-buttons" style="width: 720px;">';
		print_submit_button (__('Create report'), 'create', false, 'class="sub next"');
		echo "</div>";
		echo "</form>";
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
				$report = get_db_row_filter('treport', array('id_report' => $idReport));
				
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
					$resultOperationDB = (bool)process_sql_update('treport', array('name' => $reportName, 'id_group' => $idGroupReport, 'description' => $description), array('id_report' => $idReport));
				}
				else if ($action == 'save') {
					$idOrResult = process_sql_insert('treport', array('name' => $reportName, 'id_group' => $idGroupReport, 'description' => $description));
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
				$report = get_db_row_filter('treport', array('id_report' => $idReport));
				
				$reportName = $report['name'];
				$idGroupReport = $report['id_group'];
				$description = $report['description'];
				switch ($action) {
					case 'update':
						$values = array();
						$values['id_report'] = $idReport;
						$values['description'] = get_parameter('description');
						$values['period'] = get_parameter('period');
						$values['id_agent'] = get_parameter('id_agent');
						$values['id_gs'] = get_parameter('id_custom_graph');
						$values['text'] = get_parameter('text');
						$values['id_agent_module'] = get_parameter('id_agent_module');
						$values['type'] = get_parameter('type', null);
						$values['monday'] = get_parameter('monday', 0);
						$values['tuesday'] = get_parameter('tuesday', 0);
						$values['wednesday'] = get_parameter('wednesday', 0);
						$values['thursday'] = get_parameter('thursday', 0);
						$values['friday'] = get_parameter('friday', 0);
						$values['saturday'] = get_parameter('saturday', 0);
						$values['sunday'] = get_parameter('sunday', 0);
						$values['time_from'] = get_parameter('time_from');
						$values['time_to'] = get_parameter('time_to');
					
						if ($values['type'] == 'sql') {
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
						
						$resultOperationDB = process_sql_update('treport_content', $values, array('id_rc' => $idItem));
						break;
					case 'save':
						$values = array();
						$values['id_report'] = $idReport;
						$values['type'] = get_parameter('type', null);
						$values['description'] = get_parameter('description');
						$values['period'] = get_parameter('period');
						$values['id_agent'] = get_parameter('id_agent');
						$values['id_gs'] = get_parameter('id_custom_graph');
						$values['text'] = get_parameter('text');
						$values['id_agent_module'] = get_parameter('id_agent_module');
						$values['monday'] = get_parameter('monday', 0);
						$values['tuesday'] = get_parameter('tuesday', 0);
						$values['wednesday'] = get_parameter('wednesday', 0);
						$values['thursday'] = get_parameter('thursday', 0);
						$values['friday'] = get_parameter('friday', 0);
						$values['saturday'] = get_parameter('saturday', 0);
						$values['sunday'] = get_parameter('sunday', 0);
						$values['time_from'] = get_parameter('time_from');
						$values['time_to'] = get_parameter('time_to');
						
						if ($values['type'] == 'sql') {
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
						
						$result = process_sql_insert('treport_content', $values);
						
						if ($result === false) {
								$resultOperationDB = false;
						}
						else {
							$idItem = $result;
							
							$max = get_db_all_rows_sql('select max(`order`) as max from treport_content;'); $max = $max[0]['max'];
							process_sql_update('treport_content', array('`order`' => $max + 1), array('id_rc' => $idItem));
							
							$resultOperationDB = true;
						}
						break;
				}
				break;
			default:
				if ($enterpriseEnable) {
					$resultOperationDB = enterprise_updateAction();
				}
				break;
		}
		break;
	case 'filter':
	case 'edit':
		$resultOperationDB = null;
		$report = get_db_row_filter('treport', array('id_report' => $idReport));
		
		$reportName = $report['name'];
		$idGroupReport = $report['id_group'];
		$description = $report['description'];
		break;
	case 'delete':
		$idItem = get_parameter('id_item');
		
		$report = get_db_row_filter('treport', array('id_report' => $idReport));
		$reportName = $report['name'];
		
		$resultOperationDB = process_sql_delete('treport_content_sla_combined', array('id_report_content' => $idItem));
		
		if ($resultOperationDB !== false) {
			$resultOperationDB = process_sql_delete('treport_content', array('id_rc' => $idItem));
		}
		break;
	case 'order':
		$resultOperationDB = null;
		$report = get_db_row_filter('treport', array('id_report' => $idReport));
		
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
				
				$ids = get_db_all_rows_sql($sql);
				
				$count = 1;
				$resultOperationDB = true;
				foreach($ids as $id) {
					$result = process_sql_update('treport_content', array('order' => $count), array('id_rc' => $id['id_rc']));
					
					if ($result === false) {
						$resultOperationDB = false;
						break;
					}
					
					$count = $count + 1;
				}
				break;
			default:
				$oldOrder = get_db_value_sql('SELECT `order` FROM treport_content WHERE id_rc = ' . $idItem);
				//get_db_value_filter('order', 'treport_content', array('id_rc' => $idItem));
		
				switch ($dir) {
					case 'up':
						$newOrder = $oldOrder - 1;
						break;
					case 'down':
						$newOrder = $oldOrder + 1;
						break;	
				}
				
				process_sql_begin();
				$resultOperationDB = process_sql_update('treport_content', array('`order`' => $oldOrder), array('`order`' => $newOrder, 'id_rc' => $idReport));
				if ($resultOperationDB !== false) {
					$resultOperationDB = process_sql_update('treport_content', array('`order`' => $newOrder), array('id_rc' => $idItem));
					if ($resultOperationDB !== false) {
						process_sql_commit();
					}
					else {
						process_sql_rollback();
					}
				}
				else {
					process_sql_rollback();
				}
				break;
		}
		break;
}

if ($enterpriseEnable) {
	$result = enterprise_actionsDB($action, $activeTab, $idReport);
	if ($result !== null) {
		$resultOperationDB = $result;
	}
}

$buttons = array(
	'main' => array('active' => false,
		'text' => '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=main&action=edit&id_report=' . $idReport . '">' . 
			print_image("images/reporting_edit.png", true, array ("title" => __('Main'))) .'</a>'),
	'list_items' => array('active' => false,
		'text' => '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=edit&id_report=' . $idReport . '">' . 
			print_image("images/god6.png", true, array ("title" => __('List items'))) .'</a>'),
	'item_editor' => array('active' => false,
		'text' => '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=item_editor&action=new&id_report=' . $idReport . '">' . 
			print_image("images/config.png", true, array ("title" => __('Item editor'))) .'</a>')
	);
	
if ($enterpriseEnable) {
	$buttons = enterprise_addTabs($buttons, $idReport);
}

$buttons['preview'] = array('active' => false,
	'text' => '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=preview&action=edit&id_report=' . $idReport . '">' . 
			print_image("images/reporting.png", true, array ("title" => __('Preview'))) .'</a>');
	
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

print_page_header(__('Reporting') . $textReportName, "images/reporting_edit.png", false, "reporting_" . $activeTab . "_tab", true, $buttons);

if ($resultOperationDB !== null) {
	print_result_message ($resultOperationDB, __('Successfully action'), __('Unsuccessfully action'));
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
	case 'preview':
		require_once('godmode/reporting/reporting_builder.preview.php');
		break;
	default:
		enterprise_selectTab($activeTab);
		break;
}

?>
