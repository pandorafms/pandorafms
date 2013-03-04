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

if (! check_acl ($config['id_user'], 0, "RW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

$meta = false;
if (($config['metaconsole'] == 1) && (defined('METACONSOLE'))) {
	$meta = true;
}
$show_graph_options = Array();
$show_graph_options[0] = __('Only table');
$show_graph_options[1] = __('Table & Graph');
$show_graph_options[2] = __('Only graph');

// SLA sorting options
$show_sort_options = array();
$show_sort_options[1] = __('Ascending');
$show_sort_options[2] = __('Descending');

enterprise_include('/godmode/reporting/reporting_builder.item_editor.php');
require_once ($config['homedir'] . '/include/functions_agents.php');
if (enterprise_include_once ('include/functions_metaconsole.php')) {
	$servers = enterprise_hook("metaconsole_get_connection_names");
}

$idAgent = null;
$idAgentModule = null;
$idCustomGraph = null;
$text = null;
$header = null;
$idCustom = null;
$url = null;
$field = null;
$line = null;
$group = 0;
$group_by_agent = 0;
$order_uptodown = 0;
$show_resume = 0;
$top_n = 0;
$top_n_value = 10;
$exception_condition = 0;
$exception_condition_value = 10;
$modulegroup = 0;
$period = 86400;
// Added support for projection graphs
$period_pg = 432000;
$projection_period = 432000;
$only_display_wrong = 0;
// Added support for prediction date report
$min_interval = '0.00';
$max_interval = '0.00';
$monday = true;
$tuesday = true;
$wednesday = true;
$thursday = true;
$friday = true;
$saturday = true;
$sunday = true;
$time_from = '00:00';
$time_to = '23:59';
$show_graph = 0;
$sla_sorted_by = 0;
$id_agents = '';
$inventory_modules = array();
$date = null;

//Added for events items
$filter_event_validated = false;
$filter_event_no_validated = false;
$filter_event_critical = false;
$filter_event_warning = false;

$event_graph_by_agent = false;
$event_graph_by_user_validator = false;
$event_graph_by_criticity = false;
$event_graph_validated_vs_unvalidated = false;
$netflow_filter = 0;
$max_values = 0;
$resolution = 0;

switch ($action) {
	case 'new':
		$actionParameter = 'save';
		$type = get_parameter('type', 'SLA');
		$description = null;
		$sql = null;
		$show_in_two_columns = 0;
		$show_in_landscape = 0;
		$server_name = '';
		break;
	case 'save':
	default:
		$actionParameter = 'update';
		
		// If we are creating a new report item then clean interface and display creation view
		$type = get_parameter('type', 'SLA');
		switch ($type) {
			case 'SLA':
			case 'top_n':
			case 'exception':
			case 'general':
				$get_data_editor = true;
				break;
			default:
				$actionParameter = 'save';
				$action = 'new';
				
				$type = 'SLA';
				$description = null;
				$sql = null;
				$show_in_two_columns = 0;
				$show_in_landscape = 0;
				$server_name = '';
				$get_data_editor = false;
				break;
		}
		
		// Get data to fill editor if type is not SLA, top_n, exception, general
		if ($get_data_editor) {
			
			$item = db_get_row_filter('treport_content', array('id_rc' => $idItem));
			$server_name = $item ['server_name'];
			
			// Metaconsole db connection
			if ($meta && $server_name != '') {
				$connection = metaconsole_get_connection($server_name);
				if (metaconsole_load_external_db($connection) != NOERR) {
					//ui_print_error_message ("Error connecting to ".$server_name);
				}
			}
			
			$style = json_decode(io_safe_output($item['style']), true);
			$show_in_two_columns = $style['show_in_two_columns'];
			$show_in_landscape = $style['show_in_landscape'];
			$type = $item['type'];
			
			switch ($type) {
				case 'avg_value':
					$period = $item['period'];
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					break;
				case 'simple_baseline_graph':
				case 'simple_graph':
				case 'projection_graph':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$period = $item['period'];
					// 'top_n_value' field will be reused for projection report
					if ($type == 'projection_graph'){
						$projection_period = $item['top_n_value'];
						$period_pg = $item['period'];
					}
					break;
				case 'prediction_date':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					// 'top_n' field will be reused for prediction_date report
					$max_interval = $item['top_n'];
					$min_interval = $item['top_n_value'];
					$intervals_text = $item['text'];
					// Parse intervals text field
					$max_interval = substr($intervals_text, 0, strpos($intervals_text, ';'));
					$min_interval = substr($intervals_text, strpos($intervals_text, ';') + 1);
					// 'top_n_value' field will be reused for prediction_date report			
					$period_pg = $item['period'];
					break;
				case 'custom_graph':
				case 'automatic_custom_graph':
					$description = $item['description'];
					$period = $item['period'];
					$idCustomGraph = $item['id_gs'];
					break;
				case 'SLA':
					$description = $item['description'];
					$period = $item['period'];
					$only_display_wrong = $item['only_display_wrong'];
					$monday = $item['monday'];
					$tuesday = $item['tuesday'];
					$wednesday = $item['wednesday'];
					$thursday = $item['thursday'];
					$friday = $item['friday'];
					$saturday = $item['saturday'];
					$sunday = $item['sunday'];
					$time_from = $item['time_from'];
					$time_to = $item['time_to'];
					$show_graph = $item['show_graph'];
					// 'top_n' filed will be reused for SLA sort option
					$sla_sorted_by = $item['top_n'];
					break;
				case 'monitor_report':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$idAgentModule = $item['id_agent_module'];
					$period = $item['period'];
					break;
				case 'avg_value':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$idAgentModule = $item['id_agent_module'];
					$period = $item['period'];
					break;
				case 'max_value':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$idAgentModule = $item['id_agent_module'];
					$period = $item['period'];
					break;
				case 'min_value':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$idAgentModule = $item['id_agent_module'];
					$period = $item['period'];
					break;
				case 'sumatory':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$idAgentModule = $item['id_agent_module'];
					$period = $item['period'];
					break;
				case 'text':
					$description = $item['description'];
					$text = $item['text'];
					break;
				case 'sql':
					$description = $item['description'];
					$sql = $item['external_source'];
					$idCustom = $item['treport_custom_sql_id'];
					$header = $item['header_definition'];
					$period = 0;
					break;
				case 'sql_graph_pie':
					$description = $item['description'];
					$sql = $item['external_source'];
					$idCustom = $item['treport_custom_sql_id'];
					$period = 0;
					break;
				case 'sql_graph_vbar':
					$description = $item['description'];
					$sql = $item['external_source'];
					$idCustom = $item['treport_custom_sql_id'];
					$period = 0;
					break;
				case 'sql_graph_hbar':
					$description = $item['description'];
					$sql = $item['external_source'];
					$idCustom = $item['treport_custom_sql_id'];
					$period = 0;
					break;
				case 'url':
					$description = $item['description'];
					$url = $item['external_source'];
					break;
				case 'database_serialized':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$header = $item['header_definition'];
					$field = $item['column_separator'];
					$line = $item['line_separator'];
					$period = $item['period'];
					break;
				case 'TTRT':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$period = $item['period'];
					break;
				case 'TTO':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$period = $item['period'];
					break;
				case 'MTBF':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$period = $item['period'];
					break;
				case 'MTTR':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$period = $item['period'];
					break;
				case 'alert_report_module':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$period = $item['period'];
					break;
				case 'alert_report_agent':
					$description = $item['description'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente' => $item['id_agent']));
					$period = $item['period'];
					break;
				case 'alert_report_group':
					$description = $item['description'];
					$period = $item['period'];
					$group = $item['id_group'];
					break;
				case 'event_report_agent':
					$description = $item['description'];
					$idAgent = $item['id_agent'];
					$period = $item['period'];
					
					//Added for events items
					$filter_event_no_validated = $style['filter_event_no_validated'];
					$filter_event_validated = $style['filter_event_validated'];
					$filter_event_critical = $style['filter_event_critical'];
					$filter_event_warning = $style['filter_event_warning'];
					
					$event_graph_by_agent = $style['event_graph_by_agent'];
					$event_graph_by_user_validator = $style['event_graph_by_user_validator'];
					$event_graph_by_criticity = $style['event_graph_by_criticity'];
					$event_graph_validated_vs_unvalidated = $style['event_graph_validated_vs_unvalidated'];
					break;
				case 'event_report_group':
					$description = $item['description'];
					$period = $item['period'];
					$group = $item['id_group'];
					
					//Added for events items
					$filter_event_no_validated = $style['filter_event_no_validated'];
					$filter_event_validated = $style['filter_event_validated'];
					$filter_event_critical = $style['filter_event_critical'];
					$filter_event_warning = $style['filter_event_warning'];
					
					$event_graph_by_agent = $style['event_graph_by_agent'];
					$event_graph_by_user_validator = $style['event_graph_by_user_validator'];
					$event_graph_by_criticity = $style['event_graph_by_criticity'];
					$event_graph_validated_vs_unvalidated = $style['event_graph_validated_vs_unvalidated'];
					break;
				case 'event_report_module':
					$description = $item['description'];
					$idAgentModule = $item['id_agent_module'];
					$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
					$period = $item['period'];
					break;
				case 'general':
					$description = $item['description'];
					$group_by_agent = $item['group_by_agent'];
					$period = $item['period'];
					$order_uptodown = $item['order_uptodown'];
					$show_resume = $item['show_resume'];
					$show_graph = $item['show_graph'];
					break;
				case 'group_report':
					$description = $item['description'];
					$group = $item['id_group'];
					break;
				case 'top_n':
					$description = $item['description'];
					$period = $item['period'];
					$top_n = $item ['top_n'];
					$top_n_value = $item ['top_n_value'];
					$show_resume = $item['show_resume'];
					$show_graph = $item['show_graph'];
					$order_uptodown = $item['order_uptodown'];
					break;
				case 'exception':
					$description = $item['description'];
					$period = $item['period'];
					$exception_condition = $item['exception_condition'];
					$exception_condition_value = $item['exception_condition_value'];
					$show_resume = $item['show_resume'];
					$show_graph = $item['show_graph'];
					$order_uptodown = $item['order_uptodown'];
					break;
				case 'agent_module':
					$description = $item['description'];
					$group = $item['id_group'];
					$modulegroup = $item ['id_module_group'];
					break;
				case 'inventory':
					$description = $item['description'];
					$es = json_decode($item['external_source'], true);
					$date = $es['date'];
					$inventory_modules = $es['inventory_modules'];
					$id_agents = $es['id_agents'];
					
					$idAgent = $es['id_agents'];
					$idAgentModule = $inventory_modules;
					break;
				case 'inventory_changes':
					$period = $item['period'];
					$description = $item['description'];
					$es = json_decode($item['external_source'], true);
					$inventory_modules = $es['inventory_modules'];
					$id_agents = $es['id_agents'];
					break;
				case 'agent_configuration':
					$idAgent = $item['id_agent'];
					break;
				case 'group_configuration':
					$group = $item['id_group'];
					break;
				case 'netflow_area':
				case 'netflow_pie':
				case 'netflow_data':
				case 'netflow_statistics':
				case 'netflow_summary':
					$netflow_filter = $item['text']; // Filter
					$period = $item['period'];
					$description = $item['description'];
					$resolution = $item ['top_n']; // Interval resolution
					$max_values = $item ['top_n_value']; // Max values
					break;
			}
			
			//Restore db connection
			if ($meta && $server_name != '') {
				metaconsole_restore_db();
			}
		}
		
		break;
}

$urlForm = $config['homeurl'] . 'index.php?sec=reporting&sec2=' . $config['homedir'] . '/godmode/reporting/reporting_builder&tab=item_editor&action=' . $actionParameter . '&id_report=' . $idReport;

echo '<form action="' . $urlForm . '" method="post">';
html_print_input_hidden('id_item', $idItem);
?>
<table style="" class="databox" id="" border="0" cellpadding="4" cellspacing="4" width="98%">
	<tbody>
		<tr id="row_type" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Type'); ?></td>
			<td style="">
				<?php
				if ($action == 'new') { 
					html_print_select(reports_get_report_types(false, true), 'type', $type, 'chooseType();', '', '');
				}
				else {
					$report_type = reports_get_report_types($type);
					if (!empty($report_type) and isset($report_type[$type]['name']))
						echo $report_type[$type]['name'];
					else
						echo __('Not valid');
					echo '<input type="hidden" id="type" name="type" value="' . $type . '" />';
				}
				?>
			</td>
		</tr>
		<tr id="row_netflow_filter" style="" class="datos">
			<td><?php echo __('Filter');?></td>
			<td><?php
				
				$own_info = get_user_info ($config['id_user']);
				
				// Get group list that user has access
				$groups_user = users_get_groups ($config['id_user'], "RW", $own_info['is_admin'], true);
				$groups_id = array();
				foreach($groups_user as $key => $groups){
					$groups_id[] = $groups['id_grupo'];
				}
				
				$sql_netflow = "SELECT * FROM tnetflow_filter WHERE id_group IN (".implode(',',$groups_id).")";
				html_print_select_from_sql($sql_netflow, 'netflow_filter', $netflow_filter);
				?>
			</td>
		</tr>
		<tr id="row_description" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Description'); ?></td>
			<td style="">
				<?php
				echo html_print_textarea('description', 3, 25, $description);
				?>
			</td>
		</tr>
		<tr id="row_period" style="" class="datos">
			<td style="vertical-align: top;">
				<?php
				echo __('Period');
				?>
			</td>
			<td style="">
				<?php
				html_print_extended_select_for_time ('period', $period, '', '', '0', 10);
				?></td>
		</tr>
		<tr id="row_resolution" style="" class="datos">
			<td style="vertical-align: top;">
				<?php
				echo __('Resolution');
				?>
			</td>
			<td style="">
				<?php
				html_print_extended_select_for_time ('resolution', $resolution, '', '', '0', 10);
				?></td>
		</tr>
		<tr id="row_period1" style="" class="datos">
			<td style="vertical-align: top;">
				<?php
				echo __('Period');
				if ($type == 'projection_graph') {
					echo ui_print_help_icon('projection_graph', true);
				}
				else {
					echo ui_print_help_icon('prediction_date', true);
				}
				?>
			</td>
			<td style="">
				<?php
				html_print_extended_select_for_time ('period1', $period_pg, '', '', '0', 10);
				?>
			</td>
		</tr>
		<tr id="row_estimate" style="" class="datos">
			<td style="vertical-align: top;">
				<?php
				echo __('Projection period') . ui_print_help_icon('projection_graph', true);
				?>
			</td>
			<td style="">
				<?php
				html_print_extended_select_for_time ('period2', $projection_period, '', '', '0', 10);
				?>
			</td>
		</tr>
		<tr id="row_interval" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Data range') . ui_print_help_icon('prediction_date', true); ?></td>
			<td><?php
				echo __('Max') . "&nbsp;";
				html_print_input_text('max_interval', $max_interval, '', 5, 10);
				echo "&nbsp;" . __('Min') . "&nbsp;";
				html_print_input_text('min_interval', $min_interval, '', 5, 10);
				?></td>
		</tr>
		<tr id="row_only_display_wrong" style="" class="datos">
			<td><?php echo __('Only display wrong SLAs');?></td>
			<td>
				<?php
				html_print_checkbox('checkbox_only_display_wrong', 1, $only_display_wrong);
				?>
			</td>
		</tr>
		<tr id="row_working_time">
			<td style="vertical-align: top;"><?php echo __('Working time');?></td>
			<td>
				<table border="0">
					<tr>
						<td><?php echo __('Monday'); html_print_checkbox('monday', 1, $monday);?></td>
						<td><?php echo __('Tuesday'); html_print_checkbox('tuesday', 1, $tuesday);?></td>
						<td><?php echo __('Wednesday'); html_print_checkbox('wednesday', 1, $wednesday);?></td>
						<td><?php echo __('Thursday'); html_print_checkbox('thursday', 1, $thursday);?></td>
						<td><?php echo __('Friday'); html_print_checkbox('friday', 1, $friday);?></td>
						<td><?php echo __('Saturday'); html_print_checkbox('saturday', 1, $saturday);?></td>
						<td><?php echo __('Sunday'); html_print_checkbox('sunday', 1, $sunday);?></td>
					</tr>
					<tr>
						<td><?php echo __('Time from');?></td>
						<td colspan="6"><?php html_print_input_text ('time_from', $time_from, '', 7, 7);?></td>
					</tr>
					<tr>
						<td><?php echo __('Time to');?></td>
						<td colspan="6"><?php html_print_input_text ('time_to', $time_to, '', 7, 7);?></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr id="row_group" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Group');?></td>
			<td style="">
				<?php html_print_select_groups($config['id_user'],
					"AR", true, 'combo_group', $group, '');?>
			</td>
		</tr>
		<tr id="row_module_group" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Module group');?></td>
			<td style="">
				<?php html_print_select_from_sql ("SELECT * FROM tmodule_group ORDER BY name",
				'combo_modulegroup', $modulegroup, '',__('All'));?>
			</td>
		</tr>
		<tr id="row_agent" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Agent'); ?></td>
			<td style="">
				<?php
				if ($meta) {
					$connection = metaconsole_get_connection($server_name);
					$agent_name = '';
					
					if (metaconsole_load_external_db($connection) == NOERR)
						$agent_name = db_get_value_filter('nombre', 'tagente', array('id_agente' => $idAgent));	
						// Append server name
						if (!empty($agent_name))
							$agent_name .= ' (' . $server_name . ')';
					//Restore db connection
					metaconsole_restore_db();
				}
				else {
					$agent_name = agents_get_name ($idAgent);
				}
				
				html_print_input_hidden('id_agent', $idAgent);
				html_print_input_hidden ('server_name', $server_name);
				html_print_input_hidden ('id_server', '');
				
				
				$params = array();
				$params['show_helptip'] = false;
				$params['input_name'] = 'agent';
				// Input id is only used in metaconsole events
				if ($meta) {
					$params['input_id'] = 'agent_autocomplete';
				}
				$params['value'] = $agent_name;
				
				$params['javascript_is_function_select'] = true;
				$params['selectbox_id'] = 'id_agent_module';
				$params['add_none_module'] = false;
				$params['use_hidden_input_idagent'] = true;
				$params['hidden_input_idagent_id'] = 'hidden-id_agent';
				if ($meta) {
					$params['use_input_server'] = true;
					$params['input_server_id'] = 'hidden-server_name';
					//It is a page in the new metaconsole.
					$params['metaconsole_enabled'] = true;
				}
				
				ui_print_agent_autocomplete_input($params);
				
				// Print a specific control to metaconsole events
				if ($meta) {
					$params['input_id'] = 'agent_autocomplete_events';
					$params['javascript_page'] = 'enterprise/meta/include/ajax/events.ajax';
					$params['javascript_is_function_select'] = false;
					//$params['use_hidden_input_idagent'] = false;
					$params['use_input_server'] = false;
					$params['input_name'] = 'agent_text';
					
					ui_print_agent_autocomplete_input($params);
				}
				
				?>
			</td>
		</tr>
		<tr id="row_module" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Module'); ?></td>
			<td style="max-width: 180px">
				<?php
				if($idAgent) {
					$sql = "SELECT id_agente_modulo, nombre FROM tagente_modulo WHERE id_agente =  " . $idAgent . " AND  delete_pending = 0";
					
					if ($meta) {
						$connection = metaconsole_get_connection($server_name);
						
						if (metaconsole_load_external_db($connection) == NOERR) {
							$agent_name_temp = db_get_all_rows_sql($sql);
							
							if ($agent_name_temp === false)
								$agent_name_temp = array();
							
							$result_select = array();
							foreach ($agent_name_temp as $module_element) {
								$result_select[$module_element['id_agente_modulo']] = $module_element['nombre'];
							}
							
							html_print_select($result_select, 'id_agent_module', $idAgentModule, '', '', '0');
						}
						
						//Restore db connection
						metaconsole_restore_db();
					}
					else {
						html_print_select_from_sql($sql, 'id_agent_module', $idAgentModule, '', '', '0');					
					}
				}
				else {
					?>
					<select style="max-width: 180px" id="id_agent_module" name="id_agent_module" disabled="disabled">
						<option value="0"><?php echo __('Select an Agent first'); ?></option>
					</select>
					<?php
				}
				?>
			</td>
		</tr>
		<tr id="row_agent_multi" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Agents'); ?></td>
			<td>
				<?php 
					$agents = enterprise_hook('inventory_get_agents');
					if ((empty($agents)) || $agents == -1) $agents = array();
					
					$agents_select = array();
					foreach ($agents as $a) {
						$agents_select[$a['id_agente']] = $a['nombre'];
					}
					html_print_select($agents_select, 'id_agents[]', $id_agents, $script = '', __('All'), -1, false, true, true, '', false, "min-width: 180px");
				?>
			</td>
		</tr>
		<tr id="row_module_multi" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Modules'); ?></td>
			<td>
				<?php 
					html_print_select(array(), 'inventory_modules[]', '', $script = '', __('None'), 0, false, true, true, '', false, "min-width: 180px");
					if (empty($inventory_modules)) {
						$array_inventory_modules = array(0 => 0);
					}
					else {
						$array_inventory_modules = implode(',', $inventory_modules);
					}
					html_print_input_hidden('inventory_modules_selected', $array_inventory_modules);
				?>
			</td>
		</tr>
		<tr id="row_date" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Date'); ?></td>
			<td style="max-width: 180px">
				<?php
				$dates = enterprise_hook(
					'inventory_get_dates',
						array($idAgentModule, $idAgent, $group));
				
				if ($dates === ENTERPRISE_NOT_HOOK) {
					$dates = array();
				}
				
				html_print_select($dates, 'date', '', '', __('Last'), 0, false, false, false, '', false, "min-width: 180px");
				html_print_input_hidden('date_selected',$date);
				?>
			</td>
		</tr>
		<tr id="row_custom_graph" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Custom graph'); ?></td>
			<td style="">
				<?php
				if ($meta) {
					$graphs = array();
					$graphs = metaconsole_get_custom_graphs();
					$value_selected = $idCustomGraph . '|' . $server_name;
					html_print_select($graphs, 'id_custom_graph', $value_selected, 'change_custom_graph();', __('None'), 0);
				}
				else {
					switch ($config["dbtype"]) {
						case "mysql":
							$query_sql = 'SELECT id_graph, name FROM tgraph WHERE private = 0 OR (private = 1 AND id_user = "'.$config["id_user"].'")';
							break;
						case "postgresql":
						case "oracle":
							$query_sql = 'SELECT id_graph, name FROM tgraph WHERE private = 0 OR (private = 1 AND id_user = \''.$config["id_user"].'\')';
							break;
					}
					html_print_select_from_sql($query_sql, 'id_custom_graph', $idCustomGraph, 'change_custom_graph();', __('None'), 0);
				}
				
				$style_button_create_custom_graph = 'style="display: none;"';
				$style_button_edit_custom_graph = '';
				if (empty($idCustomGraph)) {
					$style_button_create_custom_graph = '';
					$style_button_edit_custom_graph = 'style="display: none;"';
					// Select the target server
					if ($meta) {
						$metaconsole_connections = enterprise_hook('metaconsole_get_connection_names');
						if ($metaconsole_connections === false) {
							$metaconsole_connections = array();
						}
						$result_servers = array();
						foreach ($metaconsole_connections as $metaconsole_element){
							$connection_data = enterprise_hook('metaconsole_get_connection', array($metaconsole_element));
							$result_servers[$connection_data['server_name']] = $connection_data['server_name'];
						}
						// Print select combo with metaconsole servers
						if (!empty($result_servers)) {
							echo '<div id="meta_target_servers" style="display:none;">';
							echo '&nbsp;&nbsp;&nbsp;&nbsp;' . __('Target server') . '&nbsp;&nbsp;';
							html_print_select($result_servers, 'meta_servers', '', '', __('None'), 0);
							echo '</div>';
						}
						else {
							// If there are not metaconsole servers don't allow to create new custom graphs 
							$style_button_create_custom_graph = 'style="display: none;"';
						}
					}
				}
				echo "&nbsp;";
				html_print_button(__("Create"), 'create_graph', false,
					'create_custom_graph();', 'class="sub add" ' . $style_button_create_custom_graph);
					
				html_print_button(__("Edit"), 'edit_graph', false,
					'edit_custom_graph();', 'class="sub config" ' . $style_button_edit_custom_graph);
				?>
			</td>
		</tr>
		<tr id="row_text" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Text'); ?></td>
			<td style=""><?php html_print_textarea('text', 5, 25, $text); ?></td>
		</tr>
		<tr id="row_custom" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Custom SQL template'); ?></td>
			<td style=""><?php html_print_select_from_sql('SELECT id, name FROM treport_custom_sql', 'id_custom', $idCustom, 'chooseSQLquery()', '--', '0'); ?></td>
		</tr>
		<tr id="row_query" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('SQL query'); ?></td>
			<td style="" id="sql_entry">
				<?php
				html_print_textarea('sql', 5, 25, $sql);
				?>
			</td>
			<td style="" id="sql_example"></td> 
		</tr>
		<tr id="row_servers" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Server'); ?></td>
			<td style="">
				<?php
				if (!$meta)
					html_print_select ($servers, 'combo_server', $server_name, '', __('Select server'), 0, false, false, true, '', true);
				else
					html_print_select ($servers, 'combo_server', $server_name, '', __('Select server'), 0);
				?>
			</td>
		</tr>
		<tr id="row_header" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Serialized header') . ui_print_help_tip(__("The separator character is |"), true);?></td>
			<td style=""><?php html_print_input_text('header', $header, '', 40, 90); ?></td>
		</tr>
		<tr id="row_url" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('URL'); ?></td>
			<td style=""><?php html_print_input_text('url', $url, '', 40, 90); ?></td>
		</tr>
		<tr id="row_field_separator" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Field separator') . ui_print_help_tip(__("Separator for different fields in the serialized text chain"), true); ?></td>
			<td style=""><?php html_print_input_text('field', $field, '', 2, 4); ?></td>
		</tr>
		<tr id="row_line_separator" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Line separator') . ui_print_help_tip(__("Separator in different lines (composed by fields) of the serialized text chain"), true); ?></td>
			<td style=""><?php html_print_input_text('line', $line, '', 2, 4); ?></td>
		</tr>
		<tr id="row_group_by_agent" style="" class="datos">
			<td><?php echo __('Group by agent');?></td>
			<td><?php html_print_checkbox('checkbox_row_group_by_agent', 1, $group_by_agent);?></td>
		</tr>
		<tr id="row_order_uptodown" style="" class="datos">
			<td><?php echo __('Order');?></td>
			<td>
				<?php
				echo __('Ascending');
				html_print_radio_button ('radiobutton_order_uptodown', 2, '', $order_uptodown);
				echo __('Descending');
				html_print_radio_button ('radiobutton_order_uptodown', 1, '', $order_uptodown);
				echo __('By agent name');
				html_print_radio_button ('radiobutton_order_uptodown', 3, '', $order_uptodown);
				?>
			</td>
		</tr>
		<tr id="row_quantity" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Quantity (n)'); ?></td>
			<td style=""><?php html_print_input_text('quantity', $top_n_value, '', 5, 5); ?></td>
		</tr>
		<tr id="row_max_values" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Max. values'); ?></td>
			<td style=""><?php html_print_input_text('max_values', $max_values, '', 5, 5); ?></td>
		</tr>
		<tr id="row_max_min_avg" style="" class="datos">
			<td><?php echo __('Display');?></td>
			<td>
				<?php
				echo __('Max');
				html_print_radio_button ('radiobutton_max_min_avg', 1, '', $top_n);
				echo __('Min');
				html_print_radio_button ('radiobutton_max_min_avg', 2, '', $top_n);
				echo __('Avg');
				html_print_radio_button ('radiobutton_max_min_avg', 3, '', $top_n);
				?>
			</td>
		</tr>
		<tr id="row_exception_condition_value" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Value'); ?></td>
			<td style=""><?php html_print_input_text('exception_condition_value', $exception_condition_value, '', 5, 5); ?></td>
		</tr>
		<tr id="row_exception_condition" style="" class="datos">
			<td><?php echo __('Condition');?></td>
			<td><?php
				echo __('Everything');
				html_print_radio_button ('radiobutton_exception_condition', 0, '', $exception_condition);
				echo __('>=');
				html_print_radio_button ('radiobutton_exception_condition', 1, '', $exception_condition);
				echo __('<');
				html_print_radio_button ('radiobutton_exception_condition', 2, '', $exception_condition);
				echo __('OK');
				html_print_radio_button ('radiobutton_exception_condition', 3, '', $exception_condition);
				echo __('Not OK');
				html_print_radio_button ('radiobutton_exception_condition', 4, '', $exception_condition);
				?></td>
		</tr>
		<tr id="row_show_graph" style="" class="datos">
			<td><?php echo __('Show graph');?></td>
			<td><?php html_print_select ($show_graph_options, 'combo_graph_options', $show_graph);?></td>
			
		</tr>
		<tr id="row_show_resume" style="" class="datos">
			<td><?php echo __('Show resume') . ui_print_help_tip(__('Show a resume table with max, min, average of total modules on the report bottom'), true);?></td>
			<td><?php html_print_checkbox('checkbox_show_resume', 1, $show_resume);?></td>
		</tr>
		<tr id="row_event_filter" style="" class="datos">
			<td><?php echo __('Event filter'); ?></td>
			<td>
				<?php
				echo __('No Validated');
				html_print_checkbox ('filter_event_no_validated', true, $filter_event_no_validated);
				echo __('Validated');
				html_print_checkbox ('filter_event_validated', true, $filter_event_validated);
				echo __('Critical');
				html_print_checkbox ('filter_event_critical', true, $filter_event_critical);
				echo __('Warning');
				html_print_checkbox ('filter_event_warning', true, $filter_event_warning);
				?>
			</td>
		</tr>
		</tr>
		<tr id="row_event_graphs" style="" class="datos">
			<td><?php echo __('Event graphs'); ?></td>
			<td>
				<?php
				echo __('By agent');
				html_print_checkbox ('event_graph_by_agent', true, $event_graph_by_agent);
				echo __('By user validator');
				html_print_checkbox ('event_graph_by_user_validator', true, $event_graph_by_user_validator);
				echo __('By criticity');
				html_print_checkbox ('event_graph_by_criticity', true, $event_graph_by_criticity);
				echo __('Validated vs unvalidated');
				html_print_checkbox ('event_graph_validated_vs_unvalidated', true, $event_graph_validated_vs_unvalidated);
				?>
			</td>
		</tr>
		<tr id="row_show_in_two_columns" style="" class="datos">
			<td><?php echo __('Show in two columns');?></td>
			<td><?php html_print_checkbox('show_in_two_columns', 1, $show_in_two_columns, false,
				false, 'if ($(\'input[name=show_in_two_columns]\').is(\':checked\')) $(\'input[name=show_in_landscape]\').attr(\'checked\', false);');?></td>
		</tr>
		<tr id="row_sort" style="" class="datos">
			<td><?php echo __('Order') . ui_print_help_tip(__('SLA items sorted by fulfillment value'), true);?></td>
			<td><?php html_print_select ($show_sort_options, 'combo_sla_sort_options', $sla_sorted_by, '', __('None'), 0); ?></td>
		</tr>
		<tr id="row_show_in_landscape" style="" class="datos">
			<td><?php echo __('Show in landscape');?></td>
			<td>
				<?php
				html_print_checkbox('show_in_landscape', 1,
					$show_in_landscape, false, false,
					'if ($(\'input[name=show_in_landscape]\').is(\':checked\')) $(\'input[name=show_in_two_columns]\').attr(\'checked\', false);');
				?>
			</td>
		</tr>
	</tbody>
</table>
<?php
print_SLA_list('95%', $action, $idItem);
print_General_list('95%', $action, $idItem);
echo '<div class="action-buttons" style="width: 95%">';
if ($action == 'new') {
	html_print_submit_button(__('Create item'), 'create_item', false, 'class="sub wand"');
}
else {
	html_print_submit_button(__('Update item'), 'edit_item', false, 'class="sub upd"');
}
echo '</div>';
echo '</form>';

ui_require_jquery_file ("ui-timepicker-addon");
ui_require_javascript_file('pandora');
if ($enterpriseEnable) {
	reporting_enterprise_text_box();
}
//Restore db connection
if ($meta) {
	metaconsole_restore_db();
}

function print_SLA_list($width, $action, $idItem = null) {
	global $config;
	global $meta;
	
	?>
	<table class="databox" id="sla_list" border="0" cellpadding="4" cellspacing="4" width="98%">
		<thead>
			<tr>
				<th class="header" scope="col"><?php echo __('Agent');?></th>
				<th class="header" scope="col"><?php echo __('Module');?></th>
				<th class="header" scope="col"><?php echo __('SLA Min. (value)');?></th>
				<th class="header" scope="col"><?php echo __('SLA Max. (value)');?></th>
				<th class="header" scope="col"><?php echo __('SLA Limit (%)');?></th>
				<th class="header" scope="col"><?php echo __('Action');?></th>
			</tr>
		</thead>
			<?php
			switch ($action) {
				case 'new':
					?>
					<tr id="sla_template" style="" class="datos">
						<td colspan="6"><?php echo __('Please save the SLA for start to add items in this list.');?></td>
					</tr>
					<?php
					break;
				case 'save':
				case 'update':
				case 'edit':
					echo '<tbody id="list_sla">';
					$itemsSLA = db_get_all_rows_filter('treport_content_sla_combined', array('id_report_content' => $idItem));
					if ($itemsSLA === false) {
						$itemsSLA = array();
					}
					foreach ($itemsSLA as $item) {
						$server_name = $item ['server_name'];
						// Metaconsole db connection
						if ($meta && $server_name != '') {
							$connection = metaconsole_get_connection($server_name);
							if (metaconsole_load_external_db($connection) != NOERR) {
								//ui_print_error_message ("Error connecting to ".$server_name);
								continue;
							}
						}
						$idAgent = db_get_value_filter('id_agente',
							'tagente_modulo',
							array('id_agente_modulo' => $item['id_agent_module']));
						$nameAgent = agents_get_name ($idAgent);
						$nameModule = db_get_value_filter('nombre',
							'tagente_modulo',
							array('id_agente_modulo' => $item['id_agent_module']));
						
						$server_name_element = '';
						if ($meta && $server_name != '') 
							$server_name_element .= ' (' . $server_name . ')';
						
						echo '<tr id="sla_' . $item['id'] . '" style="" class="datos">
								<td>' . printSmallFont($nameAgent) . $server_name_element .  '</td>
								<td>' . printSmallFont($nameModule) . '</td>
								<td>' . $item['sla_min'] . '</td>
								<td>' . $item['sla_max'] . '</td>
								<td>' . $item['sla_limit'] . '</td>
								<td style="text-align: center;">
									<a href="javascript: deleteSLARow(' . $item['id'] . ');">' . html_print_image("images/cross.png", true) . '</a>
								</td>
							</tr>';
						if ($meta) {
							//Restore db connection
							metaconsole_restore_db();
						}
					}
					echo '</tbody>';
					?>
					<tbody id="sla_template">
						<tr id="row" style="display: none;" class="datos">
							<td class="agent_name"></td>
							<td class="module_name"></td>
							<td class="sla_min"></td>
							<td class="sla_max"></td>
							<td class="sla_limit"></td>
							<td style="text-align: center;"><a class="delete_button" href="javascript: deleteSLARow(0);"><?php html_print_image("images/cross.png", false); ?></a></td>
						</tr>
					</tbody>
					<tbody>
						<tr id="sla_form" style="" class="datos">
							<td>
								<input id="hidden-id_agent_sla" name="id_agent_sla" value="" type="hidden">
								<input id="hidden-server_name" name="server_name" value="" type="hidden">
								<?php
								$params = array();
								$params['show_helptip'] = true;
								$params['input_name'] = 'agent_sla';
								$params['value'] = '';
								$params['use_hidden_input_idagent'] = true;
								$params['hidden_input_idagent_id'] = 'hidden-id_agent_sla';
								$params['javascript_is_function_select'] = true;
								$params['selectbox_id'] = 'id_agent_module_sla';
								$params['add_none_module'] = false;
								if ($meta) {
									$params['use_input_id_server'] = true;
									$params['input_id_server_id'] = 'hidden-id_server';
									$params['disabled_javascript_on_blur_function'] = true;
								}
								ui_print_agent_autocomplete_input($params);
								?>
							<td><select id="id_agent_module_sla" name="id_agente_modulo_sla" disabled="disabled" style="max-width: 180px"><option value="0"><?php echo __('Select an Agent first'); ?></option></select></td>
							<td><input name="sla_min" id="text-sla_min" size="10" maxlength="10" type="text"></td>
							<td><input name="sla_max" id="text-sla_max" size="10" maxlength="10" type="text"></td>
							<td><input name="sla_limit" id="text-sla_limit" size="10" maxlength="10" type="text"></td>
							<td style="text-align: center;"><a href="javascript: addSLARow();"><?php html_print_image("images/disk.png", false); ?></a></td>
						</tr>
					</tbody>
					<?php
					break;
				default:
					break;
			}
			?>
	</table>
	<span style="display: none" id="module_sla_text"><?php echo __('Select an Agent first'); ?></span>
	<?php
}

function print_General_list($width, $action, $idItem = null) {
	global $config;
	global $meta;
	if (!isset($meta))
		$meta = false;
	
	
	include_once($config['homedir'] . '/include/functions_html.php');
	?>
	<table class="databox" id="general_list" border="0" cellpadding="4" cellspacing="4" width="98%">
		<thead>
			<tr>
				<th class="header" scope="col"><?php echo __('Agent');?></th>
				<th class="header" scope="col"><?php echo __('Module');?></th>
				<th class="header" scope="col"><?php echo __('Operation');?></th>
				<th class="header" scope="col"><?php echo __('Action');?></th>
			</tr>
		</thead>
			<?php
			switch ($action) {
				case 'new':
					?>
					<tr id="general_template" style="" class="datos">
						<td colspan="3"><?php echo __('Please save the report to start adding items into the list.');?></td>
					</tr>
					<?php
					break;
				case 'save':
				case 'update':
				case 'edit':
					echo '<tbody id="list_general">';
					$itemsGeneral = db_get_all_rows_filter('treport_content_item', array('id_report_content' => $idItem));
					if ($itemsGeneral === false) {
						$itemsGeneral = array();
					}
					foreach ($itemsGeneral as $item) {
						$server_name = $item ['server_name'];
						// Metaconsole db connection
						if ($meta && $server_name != '') {
							$connection = metaconsole_get_connection($server_name);
							if (metaconsole_load_external_db($connection) != NOERR) {
								//ui_print_error_message ("Error connecting to ".$server_name);
								continue;
							}
						}
						$idAgent = db_get_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $item['id_agent_module']));
						$nameAgent = agents_get_name ($idAgent);
						$nameModule = db_get_value_filter('nombre', 'tagente_modulo', array('id_agente_modulo' => $item['id_agent_module']));
						
						$server_name_element = '';
						if ($meta && $server_name != '') 
							$server_name_element .= ' (' . $server_name . ')';						
						
						echo '<tr id="general_' . $item['id'] . '" style="" class="datos">
								<td>' . printSmallFont($nameAgent) . $server_name_element .  '</td>
								<td>' . printSmallFont($nameModule) . '</td>
								<td>' . printSmallFont($item['operation']) . '</td>
								<td style="text-align: center;">
									<a href="javascript: deleteGeneralRow(' . $item['id'] . ');">' . html_print_image("images/cross.png", true) . '</a>
								</td>
							</tr>';
						if ($meta) {
							//Restore db connection
							metaconsole_restore_db();
						}
					}
					echo '</tbody>';
					?>
					<tbody id="general_template">
						<tr id="row" style="display: none;" class="datos">
							<td class="agent_name"></td>
							<td class="module_name"></td>
							<td class="operation_name"></td>
							<td style="text-align: center;"><a class="delete_button" href="javascript: deleteGeneralRow(0);"><?php html_print_image("images/cross.png", false); ?></a></td>
						</tr>
					</tbody>
					<tbody>
						<tr id="general_form" style="" class="datos">
							<td>
								<input id="hidden-id_agent_general" name="id_agent_general" value="" type="hidden">
								<input id="hidden-server_name_general" name="server_name_general" value="" type="hidden">
								<?php
								$params = array();
								$params['show_helptip'] = true;
								$params['input_name'] = 'agent_general';
								$params['value'] = '';
								$params['use_hidden_input_idagent'] = true;
								$params['hidden_input_idagent_id'] = 'hidden-id_agent_general';
								$params['javascript_is_function_select'] = true;
								$params['selectbox_id'] = 'id_agent_module_general';
								$params['add_none_module'] = false;
								if ($meta) {
									$params['use_input_id_server'] = true;
									$params['input_id_server_id'] = 'hidden-id_server';
									$params['disabled_javascript_on_blur_function'] = true;
								}
								ui_print_agent_autocomplete_input($params);
								?>
							</td>
							<td><select id="id_agent_module_general" name="id_agente_modulo_general" disabled="disabled" style="max-width: 180px"><option value="0"><?php echo __('Select an Agent first'); ?></option></select></td>
							<?php $operation = array ('avg'=>'avg','max'=>'max','min'=>'min','sum'=>'sum'); ?>
							<td><?php html_print_select ($operation, 'id_operation_module_general', 0, false, '', '', false, false, true, 'width: 200px', false); ?></td>
							<td style="text-align: center;"><a href="javascript: addGeneralRow();"><?php html_print_image("images/disk.png", false); ?></a></td>
						</tr>
					</tbody>
					<?php
					break;
				default:
					break;
			}
			?>
	</table>
	<span style="display: none" id="module_general_text"><?php echo __('Select an Agent first'); ?></span>
	<?php
}

ui_require_javascript_file ('pandora_inventory', ENTERPRISE_DIR.'/include/javascript/');

?>
<script type="text/javascript">
$(document).ready (function () {
	chooseType();
	chooseSQLquery();
	
	$("#text-time_to, #text-time_from").timepicker({
		showSecond: true,
		timeFormat: 'hh:mm:ss',
		timeOnlyTitle: '<?php echo __('Choose time');?>',
		timeText: '<?php echo __('Time');?>',
		hourText: '<?php echo __('Hour');?>',
		minuteText: '<?php echo __('Minute');?>',
		secondText: '<?php echo __('Second');?>',
		currentText: '<?php echo __('Now');?>',
		closeText: '<?php echo __('Close');?>'});
});

function create_custom_graph() {
	<?php 
	global $config;
	
	// Metaconsole activated
	if ($meta) {
	?>
		var target_server = $("#meta_servers").val();
		// If target server is not selected
		if (target_server == 0) {
			$("#meta_target_servers").fadeOut ('normal');
			$("#meta_target_servers").fadeIn ('normal');
			$("#meta_target_servers").css('display', 'inline');
		}
		else {
			
			var hash_data;
			var params1 = [];
			params1.push("get_metaconsole_hash_data=1");
			params1.push("server_name=" + target_server);
			params1.push("page=include/ajax/reporting.ajax");
			jQuery.ajax ({
				data: params1.join ("&"),
				type: 'POST',
				url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
				async: false,
				timeout: 10000,
				success: function (data) {
					hash_data = data;
				}
			});
			
			var server_url;
			var params1 = [];
			params1.push("get_metaconsole_server_url=1");
			params1.push("server_name=" + target_server);
			params1.push("page=include/ajax/reporting.ajax");
			jQuery.ajax ({
				data: params1.join ("&"),
				type: 'POST',
				url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
				async: false,
				timeout: 10000,
				success: function (data) {
					server_url = data;
				}
			});
			
			window.location.href = server_url + "/index.php?sec=reporting&sec2=godmode/reporting/graph_builder&create=Create graph" + hash_data;
		}
	<?php
	}
	else {
	?>
		window.location.href = "index.php?sec=reporting&sec2=godmode/reporting/graph_builder&create=Create graph";
	<?php
	}
	?>
}

function edit_custom_graph() {
	var id_graph = $("#id_custom_graph").val();
	<?php 
	global $config;
	
	// Metaconsole activated
	if ($meta) {
	?>
		var agent_server_temp;
		var id_element_graph;
		var id_server;
		
		if (id_graph.indexOf("|") != -1){
			agent_server_temp = id_graph.split('|');
			id_element_graph = agent_server_temp[0];
			id_server = agent_server_temp[1];
		}
		
		var hash_data;
		var params1 = [];
		params1.push("get_metaconsole_hash_data=1");
		params1.push("server_name=" + id_server);
		params1.push("page=include/ajax/reporting.ajax");
		jQuery.ajax ({
			data: params1.join ("&"),
			type: 'POST',
			url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
			async: false,
			timeout: 10000,
			success: function (data) {
				hash_data = data;
			}
		});
		
		var server_url;
		var params1 = [];
		params1.push("get_metaconsole_server_url=1");
		params1.push("server_name=" + id_server);
		params1.push("page=include/ajax/reporting.ajax");
		jQuery.ajax ({
			data: params1.join ("&"),
			type: 'POST',
			url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
			async: false,
			timeout: 10000,
			success: function (data) {
				server_url = data;
			}
		});
		
		window.location.href = server_url + "/index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&id=" + id_element_graph + hash_data;		
	<?php
	}
	else {
	?>
		window.location.href = "index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&id=" + id_graph;
	<?php
	}
	?>
}

function change_custom_graph() {
	//Hidden the button create or edit custom graph
	if ($("#id_custom_graph").val() != "0") {
		$("#meta_servers").val(0);
		$("#meta_target_servers").css('display', 'none');
		$("#button-create_graph").css("display", "none");
		$("#button-edit_graph").css("display", "");
	}
	else {
		$("#meta_servers").val(0);
		$("#meta_target_servers").css('display', 'none');
		$("#button-create_graph").css("display", "");
		$("#button-edit_graph").css("display", "none");
	}
}

function chooseSQLquery() {
	var idCustom = $("#id_custom").val();
	
	if (idCustom == 0) {
		$("#sql_example").css('display', 'none');
		$("#sql_entry").css('display', '');
		$("#sql_example").html('');
	}
	else {
		$("#sql_example").css('display', '');
		$("#sql_entry").css('display', 'none');
		
		var params1 = [];
		params1.push("get_image_path=1");
		params1.push("img_src=" + "images/spinner.gif");
		params1.push("page=include/ajax/skins.ajax");
		jQuery.ajax ({
			data: params1.join ("&"),
			type: 'POST',
			url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
			async: false,
			timeout: 10000,
			success: function (data) {
				$("#sql_example").html(data);
			}
		});
		
		var params = [];
		params.push("get_custom_sql=1");
		params.push("id=" + idCustom);
		params.push("page=include/ajax/reporting.ajax");
		jQuery.ajax ({
			data: params.join ("&"),
			type: 'POST',
			url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
			timeout: 10000,
			dataType: 'json',
			success: function (data) {
				if (data['correct']) {
					$("#sql_example").html(data['sql']);
				}
			}
		});
	}
}

function deleteSLARow(id_row) {
	//ajax to delete
	var params = [];
	params.push("delete_sla_item=1");
	params.push("id=" + id_row);
	params.push("page=include/ajax/reporting.ajax");
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
		timeout: 10000,
		dataType: 'json',
		success: function (data) {
			if (data['correct']) {
				$("#sla_" + id_row).remove();
			}
		}
	});
}

function deleteGeneralRow(id_row) {
	//ajax to delete
	var params = [];
	params.push("delete_general_item=1");
	params.push("id=" + id_row);
	params.push("page=include/ajax/reporting.ajax");
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
		timeout: 10000,
		dataType: 'json',
		success: function (data) {
			if (data['correct']) {
				$("#general_" + id_row).remove();
			}
		}
	});
}

function addSLARow() {
	var nameAgent = $("input[name=agent_sla]").val();
	var idAgent = $("input[name=id_agent_sla]").val();
	var serverId = $("input[name=id_server]").val();
	var idModule = $("#id_agent_module_sla").val();
	var nameModule = $("#id_agent_module_sla :selected").text();
	var slaMin = $("input[name=sla_min]").val();
	var slaMax = $("input[name=sla_max]").val();
	var slaLimit = $("input[name=sla_limit]").val();
	
	if ((idAgent != '') && (slaMin != '') && (slaMax != '')
		&& (slaLimit != '')) {
			//Truncate nameAgent
			var params = [];
			params.push("truncate_text=1");
			params.push("text=" + nameAgent);
			params.push("page=include/ajax/reporting.ajax");
			jQuery.ajax ({
				data: params.join ("&"),
				type: 'POST',
				url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
				async: false,
				timeout: 10000,
				success: function (data) {
					nameAgent = data;
				}
			});
			//Truncate nameModule
			var params = [];
			params.push("truncate_text=1");
			params.push("text=" + nameModule);
			params.push("page=include/ajax/reporting.ajax");
			jQuery.ajax ({
				data: params.join ("&"),
				type: 'POST',
				url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
				async: false,
				timeout: 10000,
				success: function (data) {
					nameModule = data;
				}
			});
			
			var params = [];
			params.push("add_sla=1");
			params.push("id=" + $("input[name=id_item]").val());
			params.push("id_module=" + idModule);
			params.push("sla_min=" + slaMin);
			params.push("sla_max=" + slaMax);
			params.push("sla_limit=" + slaLimit);
			params.push("server_id=" + serverId);
			
			params.push("page=include/ajax/reporting.ajax");
			jQuery.ajax ({
				data: params.join ("&"),
				type: 'POST',
				url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
				timeout: 10000,
				dataType: 'json',
				success: function (data) {
					if (data['correct']) {
						row = $("#sla_template").clone();
						
						$("#row", row).show();
						$("#row", row).attr('id', 'sla_' + data['id']);
						$(".agent_name", row).html(nameAgent);
						$(".module_name", row).html(nameModule);
						$(".sla_min", row).html(slaMin);
						$(".sla_max", row).html(slaMax);
						$(".sla_limit", row).html(slaLimit);
						$(".delete_button", row).attr('href', 'javascript: deleteSLARow(' + data['id'] + ');');
						$("#list_sla").append($(row).html());
						$("input[name=id_agent_sla]").val('');
						$("input[name=id_server]").val('');
						$("input[name=agent_sla]").val('');
						$("#id_agent_module_sla").empty();
						$("#id_agent_module_sla").attr('disabled', 'true');
						$("#id_agent_module_sla").append(
							$("<option></option>")
							.attr ("value", 0)
							.html ($("#module_sla_text").html()));
						$("input[name=sla_min]").val('');
						$("input[name=sla_max]").val('');
						$("input[name=sla_limit]").val('');
					}
				}
			});
	}
}

function addGeneralRow() {
	var nameAgent = $("input[name=agent_general]").val();
	var idAgent = $("input[name=id_agent_general]").val();
	var serverId = $("input[name=id_server]").val();
	var idModule = $("#id_agent_module_general").val();
	var operation = $("#id_operation_module_general").val();
	var nameModule = $("#id_agent_module_general :selected").text();
	var nameOperation = $("#id_operation_module_general :selected").text();
	
	if (idAgent != '') {
		//Truncate nameAgent
		var params = [];
		params.push("truncate_text=1");
		params.push("text=" + nameAgent);
		params.push("page=include/ajax/reporting.ajax");
		jQuery.ajax ({
			data: params.join ("&"),
			type: 'POST',
			url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
			async: false,
			timeout: 10000,
			success: function (data) {
				nameAgent = data;
			}
		});
		//Truncate nameModule
		var params = [];
		params.push("truncate_text=1");
		params.push("text=" + nameModule);
		params.push("page=include/ajax/reporting.ajax");
		jQuery.ajax ({
			data: params.join ("&"),
			type: 'POST',
			url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
			async: false,
			timeout: 10000,
			success: function (data) {
				nameModule = data;
			}
		});
		//Truncate nameOperation
		var params = [];
		params.push("truncate_text=1");
		params.push("text=" + nameOperation);
		params.push("page=include/ajax/reporting.ajax");
		jQuery.ajax ({
			data: params.join ("&"),
			type: 'POST',
			url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
			async: false,
			timeout: 10000,
			success: function (data) {
				nameOperation = data;
			}
		});
		var params = [];
		params.push("add_general=1");
		params.push("id=" + $("input[name=id_item]").val());
		params.push("id_module=" + idModule);
		params.push("id_server=" + serverId);
		params.push("operation=" + operation);
		
		params.push("page=include/ajax/reporting.ajax");
		jQuery.ajax ({
			data: params.join ("&"),
			type: 'POST',
			url: action= <?php echo '"' . ui_get_full_url(false, false, false, false) . '"'; ?> + "/ajax.php",
			timeout: 10000,
			dataType: 'json',
			success: function (data) {
				if (data['correct']) {
					row = $("#general_template").clone();
					
					$("#row", row).show();
					$("#row", row).attr('id', 'general_' + data['id']);
					$(".agent_name", row).html(nameAgent);
					$(".module_name", row).html(nameModule);
					$(".operation_name", row).html(nameOperation);
					$(".delete_button", row).attr('href', 'javascript: deleteGeneralRow(' + data['id'] + ');');
					
					$("#list_general").append($(row).html());
					
					$("input[name=id_agent_general]").val('');
					$("input[name=id_server]").val('');
					$("input[name=agent_general]").val('');
					$("#id_operation_module_general").val('avg');
					$("#id_agent_module_general").empty();
					$("#id_agent_module_general").attr('disabled', 'true');
					
				}
			}
		});
	}
}

function chooseType() {
	type = $("#type").val();
	
	$("#row_description").hide();
	$("#row_period").hide();
	$("#row_agent").hide();
	$("#row_module").hide();
	$("#row_period").hide();
	$("#row_period1").hide();
	$("#row_estimate").hide();
	$("#row_interval").hide();
	$("#row_custom_graph").hide();
	$("#row_text").hide();
	$("#row_query").hide();
	$("#row_header").hide();
	$("#row_custom").hide();
	$("#row_url").hide();
	$("#row_field_separator").hide();
	$("#row_line_separator").hide();
	$("#sla_list").hide();
	$("#row_custom_example").hide();
	$("#row_group").hide();
	$("#row_working_time").hide();
	$("#row_only_display_wrong").hide();
	$("#row_combo_module").hide();
	$("#row_only_display_wrong").hide();
	$("#row_group_by_agent").hide();
	$("#general_list").hide();
	$("#row_order_uptodown").hide();
	$("#row_show_resume").hide();
	$("#row_show_graph").hide();
	$("#row_max_min_avg").hide();
	$("#row_quantity").hide();
	$("#row_exception_condition_value").hide();
	$("#row_exception_condition").hide();
	$("#row_show_in_two_columns").hide();
	$("#row_show_in_landscape").hide();
	$("#row_module_group").hide();
	$("#row_servers").hide();
	$("#row_sort").hide();
	$("#row_date").hide();
	$("#row_agent_multi").hide();
	$("#row_module_multi").hide();
	$("#row_event_filter").hide();
	$("#row_event_graphs").hide();
	$("#row_netflow_filter").hide();
	$("#row_max_values").hide();
	$("#row_resolution").hide();
	
	$('#agent_autocomplete').show();
	$('#agent_autocomplete_events').hide();
	
	switch (type) {
		case 'event_report_group':
			$("#row_description").show();
			$("#row_period").show();
			$("#row_servers").show();
			$("#row_group").show();
			$("#row_show_in_two_columns").show();
			$("#row_event_filter").show();
			$("#row_event_graphs").show();
			break;
		case 'simple_graph':
		case 'simple_baseline_graph':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			$("#row_show_in_landscape").show();
			break;
		case 'projection_graph':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period1").show();
			$("#row_estimate").show();
			$("#row_show_in_two_columns").show();
			$("#row_show_in_landscape").show();
			break;
		case 'prediction_date':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_period1").show();
			$("#row_module").show();
			$("#row_interval").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'custom_graph':
		case 'automatic_custom_graph':
			$("#row_description").show();
			$("#row_period").show();
			$("#row_custom_graph").show();
			$("#row_show_in_two_columns").show();
			$("#row_show_in_landscape").show();
			break;
		case 'SLA':
			$("#row_description").show();
			$("#row_period").show();
			$("#sla_list").show();
			$("#row_working_time").show();
			$("#row_only_display_wrong").show();
			$("#row_show_graph").show();
			$("#row_show_in_two_columns").show();
			$("#row_sort").show();
			break;
		case 'monitor_report':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'avg_value':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'max_value':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'min_value':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'sumatory':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'agent_detailed':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'text':
			$("#row_description").show();
			$("#row_text").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'sql':
			$("#row_description").show();
			$("#row_query").show();
			$("#row_header").show();
			$("#row_custom").show();
			$("#row_custom_example").show();
			$("#row_show_in_two_columns").show();
			$("#row_servers").show();
			break;
		case 'sql_graph_pie':
			$("#row_description").show();
			$("#row_query").show();
			$("#row_custom").show();
			$("#row_custom_example").show();
			$("#row_show_in_two_columns").show();
			$("#row_show_in_landscape").show();
			$("#row_servers").show();
			
			break;
		case 'sql_graph_hbar':
			$("#row_description").show();
			$("#row_query").show();
			$("#row_custom").show();
			$("#row_custom_example").show();
			$("#row_show_in_two_columns").show();
			$("#row_show_in_landscape").show();
			$("#row_servers").show();
			break;
		case 'sql_graph_vbar':
			$("#row_description").show();
			$("#row_query").show();
			$("#row_custom").show();
			$("#row_custom_example").show();
			$("#row_show_in_two_columns").show();
			$("#row_show_in_landscape").show();
			$("#row_servers").show();
			break;
		case 'url':
			$("#row_description").show();
			$("#row_url").show();
			break;
		case 'database_serialized':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_header").show();
			$("#row_field_separator").show();
			$("#row_line_separator").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'TTRT':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'TTO':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'MTBF':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'MTTR':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'alert_report_module':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'alert_report_group':
			$("#row_description").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			$("#row_group").show();
			break;
		case 'alert_report_agent':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'event_report_agent':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			$("#row_event_filter").show();
			$("#row_event_graphs").show();
		
			$('#agent_autocomplete').hide();
			$('#agent_autocomplete_events').show();
			break;
		case 'event_report_module':
			$("#row_description").show();
			$("#row_agent").show();
			$("#row_module").show();
			$("#row_period").show();
			$("#row_show_in_two_columns").show();
			
			$('#agent_autocomplete').hide();
			$('#agent_autocomplete_events').show();
			break;
		case 'general':
			$("#row_description").show();
			$("#row_group_by_agent").show();
			$("#row_period").show();
			$("#general_list").show();
			$("#row_order_uptodown").show();
			$("#row_show_resume").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'group_report':
			$("#row_group").show();
			$("#row_servers").show();
			$("#row_description").show();
			break;
		case 'top_n':
			$("#row_description").show();
			$("#row_period").show();
			$("#row_max_min_avg").show();
			$("#row_quantity").show();
			$("#general_list").show();
			$("#row_order_uptodown").show();
			$("#row_show_resume").show();
			$("#row_show_graph").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'exception':
			$("#row_description").show();
			$("#row_period").show();
			$("#general_list").show();
			$("#row_exception_condition_value").show();
			$("#row_exception_condition").show();
			$("#row_order_uptodown").show();
			$("#row_show_resume").show();
			$("#row_show_graph").show();
			$("#row_show_in_two_columns").show();
			break;
		case 'agent_module':
			$("#row_description").show();
			$("#row_group").show();
			$("#row_module_group").show();
			break;
		case 'inventory_changes':
			$("#row_description").show();
			$("#row_period").show();
			$("#row_group").show();
			$("#row_agent_multi").show();
			$("#row_module_multi").show();
			$("#row_show_in_two_columns").show();
			$("#row_servers").show();
			$("#id_agents").change(agent_changed_by_multiple_agents_inventory);
			$("#id_agents").trigger('change');
			
			$("#combo_group").change(function() {
				updateAgents($(this).val());
			});
			
			break;
		case 'inventory':
			$("#row_description").show();
			$("#row_group").show();
			$("#row_agent_multi").show();
			$("#row_module_multi").show();
			$("#row_date").show();
			$("#row_show_in_two_columns").show();
			
			$("#id_agents").change(agent_changed_by_multiple_agents_inventory);
			$("#id_agents").trigger('change');
			
			$("#row_servers").show();
			
			$("#combo_group").change(function() {
				$('#hidden-date_selected').val('');
				updateInventoryDates();
				updateAgents($(this).val());
			});
			$("#id_agents").change(function() {
				$('#hidden-date_selected').val('');
				updateInventoryDates();
			});
			$("#inventory_modules").change(function() {
				$('#hidden-date_selected').val('');
				updateInventoryDates();
			});
			
			if (!$("#hidden-date_selected").val())
				updateInventoryDates();
			break;
		case 'inventory_changes':
			break;
		case 'agent_configuration':
			$("#row_agent").show();
			break;
		case 'group_configuration':
			$("#row_group").show();
			break;
		case 'netflow_area':
			$("#row_netflow_filter").show();
			$("#row_description").show();
			$("#row_period").show();
			$("#row_max_values").show();
			$("#row_resolution").show();
			$("#row_servers").show();
			break;
		case 'netflow_pie':
			$("#row_netflow_filter").show();
			$("#row_description").show();
			$("#row_period").show();
			$("#row_max_values").show();
			$("#row_resolution").show();
			$("#row_servers").show();
			break;
		case 'netflow_data':
			$("#row_netflow_filter").show();
			$("#row_description").show();
			$("#row_period").show();
			$("#row_max_values").show();
			$("#row_resolution").show();
			$("#row_servers").show();
			break;
		case 'netflow_summary':
			$("#row_netflow_filter").show();
			$("#row_description").show();
			$("#row_period").show();
			$("#row_resolution").show();
			$("#row_servers").show();
			break;
		case 'netflow_statistics':
			$("#row_netflow_filter").show();
			$("#row_description").show();
			$("#row_period").show();
			$("#row_max_values").show();
			$("#row_resolution").show();
			$("#row_servers").show();
			break;
	}
}
</script>
