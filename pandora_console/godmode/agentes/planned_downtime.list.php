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

// Load global vars
global $config;

check_login();

$read_permisson = check_acl ($config['id_user'], 0, "AR");
$write_permisson = check_acl ($config['id_user'], 0, "AD");
$manage_permisson = check_acl ($config['id_user'], 0, "AW");
$access = ($read_permisson == true) ? 'AR' : ($write_permisson == true) ? 'AD' : ($manage_permisson == true) ? 'AW' : 'AR';

if (! $read_permisson && !$manage_permisson) {
	db_pandora_audit("ACL Violation",
		"Trying to access downtime scheduler");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_users.php');
require_once ('include/functions_events.php');
require_once ('include/functions_planned_downtimes.php');
require_once ('include/functions_reporting.php');

$malformed_downtimes = planned_downtimes_get_malformed();
$malformed_downtimes_exist = !empty($malformed_downtimes) ? true : false;

$migrate_malformed = (bool) get_parameter("migrate_malformed");
if ($migrate_malformed) {
	$migration_result = planned_downtimes_migrate_malformed_downtimes();

	if ($migration_result['status'] == false) {
		ui_print_error_message(__('An error occurred while migrating the malformed planned downtimes') . ". "
			. __('Please run the migration again or contact with the administrator'));
		echo "<br>";
	}
}

// Header
ui_print_page_header(
	__("Planned Downtime"),
	"images/gm_monitoring.png",
	false,
	"planned_downtime",
	true,
	"");

$id_downtime = (int) get_parameter ('id_downtime', 0);

$stop_downtime = (bool) get_parameter ('stop_downtime');
// STOP DOWNTIME
if ($stop_downtime) {
	$downtime = db_get_row('tplanned_downtime', 'id', $id_downtime);
	
	// Check AD permission on the downtime
	if (empty($downtime) || (! check_acl ($config['id_user'], $downtime['id_group'], "AD") && ! check_acl ($config['id_user'], $downtime['id_group'], "AW"))) {
		db_pandora_audit("ACL Violation",
			"Trying to access downtime scheduler");
		require ("general/noaccess.php");
		return;
	}
	
	$result = planned_downtimes_stop($downtime);
	
	if ($result === false) {
		ui_print_error_message(__('An error occurred stopping the planned downtime'));
	}
	else {
		echo $result['message'];
	}
}

$delete_downtime = (int) get_parameter ('delete_downtime');
// DELETE WHOLE DOWNTIME!
if ($delete_downtime) {
	$downtime = db_get_row('tplanned_downtime', 'id', $id_downtime);
	
	// Check AD permission on the downtime
	if (empty($downtime) || (! check_acl ($config['id_user'], $downtime['id_group'], "AD") && ! check_acl ($config['id_user'], $downtime['id_group'], "AW"))) {
		db_pandora_audit("ACL Violation",
			"Trying to access downtime scheduler");
		require ("general/noaccess.php");
		return;
	}
	
	// The downtime shouldn't be running!!
	if ($downtime['executed']) {
		ui_print_error_message(__('This planned downtime is running'));
	}
	else {
		$result = db_process_sql_delete('tplanned_downtime', array('id' => $id_downtime));
		
		ui_print_result_message($result,
			__('Successfully deleted'),
			__('Not deleted. Error deleting data'));
	}
}

// Filter parameters
$offset = (int) get_parameter('offset');
$filter_params = array();

$search_text 	= $filter_params['search_text'] 	= (string) get_parameter('search_text');
$date_from 		= $filter_params['date_from'] 		= (string) get_parameter('date_from');
$date_to 		= $filter_params['date_to'] 		= (string) get_parameter('date_to');
$execution_type = $filter_params['execution_type'] 	= (string) get_parameter('execution_type');
$show_archived 	= $filter_params['archived'] 		= (bool) get_parameter('archived');
$agent_id 		= $filter_params['agent_id'] 		= (int) get_parameter('agent_id');
$agent_name 	= $filter_params['agent_name'] 		= (string) (!empty($agent_id) ? get_parameter('agent_name') : '');
$module_id 		= $filter_params['module_id'] 		= (int) get_parameter('module_name_hidden');
$module_name 	= $filter_params['module_name'] 	= (string) (!empty($module_id) ? get_parameter('module_name') : '');

$filter_params_str = http_build_query($filter_params);

// Table filter
$table_form = new StdClass();
$table_form->class = 'databox filters';
$table_form->width = '100%';
$table_form->rowstyle = array();
$table_form->rowstyle[0] = "background-color: #f9faf9;";
$table_form->rowstyle[1] = "background-color: #f9faf9;";
$table_form->rowstyle[2] = "background-color: #f9faf9;";
$table_form->data = array();

$row = array();

// Search text
$row[] = __('Search') . '&nbsp;' . html_print_input_text("search_text", $search_text, '', 50, 250, true);
// Dates
$date_inputs = __('From') . '&nbsp;' . html_print_input_text('date_from', $date_from, '', 10, 10, true);
$date_inputs .= "&nbsp;&nbsp;";
$date_inputs .= __('To') . '&nbsp;' . html_print_input_text('date_to', $date_to, '', 10, 10, true);
$row[] = $date_inputs;

$table_form->data[] = $row;

$row = array();

// Execution type
$execution_type_fields = array('once' => __('Once'), 'periodically' => __('Periodically'));
$row[] = __('Execution type') . '&nbsp;' . html_print_select($execution_type_fields, 'execution_type', $execution_type, '', __('Any'), '', true, false, false);
// Show past downtimes
$row[] = __('Show past downtimes') . '&nbsp;' . html_print_checkbox ("archived", 1, $show_archived, true);

$table_form->data[] = $row;

$row = array();

// Agent
$params = array();
$params['show_helptip'] = true;
$params['input_name'] = 'agent_name';
$params['value'] = $agent_name;
$params['return'] = true;
$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_name'] = 'agent_id';
$params['hidden_input_idagent_value'] = $agent_id;
$agent_input = __('Agent') . '&nbsp;' . ui_print_agent_autocomplete_input($params);
$row[] = $agent_input;

// Module
$row[] = __('Module') . '&nbsp;' . html_print_autocomplete_modules('module_name', $module_name, false, true, '', array(), true);

$row[] = html_print_submit_button('Search', 'search', false, 'class="sub search"', true);

$table_form->data[] = $row;
// End of table filter

// Useful to know if the user has done a form filtering
$filter_performed = false;

$groups = users_get_groups (false, $access);
if (!empty($groups)) {
	$where_values = "1=1";

	$groups_string = implode (",", array_keys ($groups));
	$where_values .= " AND id_group IN ($groups_string)";
	
	// WARNING: add $filter_performed = true; to any future filter

	if (!empty($search_text)) {
		$filter_performed = true;
		
		$where_values .= " AND (name LIKE '%$search_text%' OR description LIKE '%$search_text%')";
	}

	if (!empty($execution_type)) {
		$filter_performed = true;
		
		$where_values .= " AND type_execution = '$execution_type'";
	}

	if (!empty($date_from)) {
		$filter_performed = true;
		
		$where_values .= " AND (type_execution = 'periodically' OR (type_execution = 'once' AND date_from >= '".strtotime("$date_from 00:00:00")."'))";
	}

	if (!empty($date_to)) {
		$filter_performed = true;
		
		$periodically_monthly_w = "type_periodicity = 'monthly'
			AND ((periodically_day_from <= '".date('d', strtotime($date_from))."' AND periodically_day_to >= '".date('d', strtotime($date_to))."')
				OR (periodically_day_from > periodically_day_to
					AND (periodically_day_from <= '".date('d', strtotime($date_from))."' OR periodically_day_to >= '".date('d', strtotime($date_to))."')))";
		
		$periodically_weekly_days = array();
		$date_from_aux = strtotime($date_from);
		$date_end = strtotime($date_to);
		$days_number = 0;

		while ($date_from_aux <= $date_end && $days_number < 7) {
			$weekday_actual = strtolower(date('l', $date_from_aux));
			
			$periodically_weekly_days[] = "$weekday_actual = 1";

			$date_from_aux = $date_from_aux + SECONDS_1DAY;
			$days_number++;
		}

		$periodically_weekly_w = "type_periodicity = 'weekly' AND (".implode(" OR ", $periodically_weekly_days).")";
		
		$periodically_w = "type_execution = 'periodically' AND (($periodically_monthly_w) OR ($periodically_weekly_w))";
		
		$once_w = "type_execution = 'once' AND date_to <= '".strtotime("$date_to 23:59:59")."'";
		
		$where_values .= " AND (($periodically_w) OR ($once_w))";
	}

	if (!$show_archived) {
		$filter_performed = true;
		
		$where_values .= " AND (type_execution = 'periodically' OR (type_execution = 'once' AND date_to >= '".time()."'))";
	}

	if (!empty($agent_id)) {
		$filter_performed = true;
		
		$where_values .= " AND id IN (SELECT id_downtime FROM tplanned_downtime_agents WHERE id_agent = $agent_id)";
	}

	if (!empty($module_id)) {
		$filter_performed = true;
		
		$where_values .= " AND (id IN (SELECT id_downtime
									   FROM tplanned_downtime_modules
									   WHERE id_agent_module = $module_id)
							OR id IN (SELECT id_downtime
									  FROM tplanned_downtime_agents tpda, tagente_modulo tam
									  WHERE tpda.id_agent = tam.id_agente
									  	AND tam.id_agente_modulo = $module_id
									  	AND tpda.all_modules = 1))";
	}
	
	// Columns of the table tplanned_downtime
	$columns = array(
			'id',
			'name',
			'description',
			'date_from',
			'date_to',
			'executed',
			'id_group',
			'only_alerts',
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
			'sunday',
			'periodically_time_from',
			'periodically_time_to',
			'periodically_day_from',
			'periodically_day_to',
			'type_downtime',
			'type_execution',
			'type_periodicity',
			'id_user',
		);
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$columns_str = implode(',', $columns);
			$sql = "SELECT $columns_str
					FROM tplanned_downtime
					WHERE $where_values
					ORDER BY type_execution DESC, date_from DESC
					LIMIT ".$config["block_size"]."
					OFFSET $offset";
			break;
		case "oracle":
			// Oracle doesn't have TIME type, so we should transform the DATE value
			$new_time_from = "TO_CHAR(periodically_time_from, 'HH24:MI:SS') AS periodically_time_from";
			$new_time_to = "TO_CHAR(periodically_time_to, 'HH24:MI:SS') AS periodically_time_to";
			
			$time_from_key = array_search('periodically_time_from', $columns);
			$time_to_key = array_search('periodically_time_to', $columns);
			
			if ($time_from_key !== false)
				$columns[$time_from_key] = $new_time_from;
			if ($time_to_key !== false)
				$columns[$time_to_key] = $new_time_to;
			
			$columns_str = implode(',', $columns);
			
			$set = array ();
			$set['limit'] = $config["block_size"];
			$set['offset'] = $offset;
			
			$sql = "SELECT $columns_str
					FROM tplanned_downtime
					WHERE $where_values
					ORDER BY type_execution DESC, date_from DESC";
			
			$sql = oracle_recode_query ($sql, $set);
			break;
	}

	$sql_count = "SELECT COUNT(id) AS num
				  FROM tplanned_downtime
				  WHERE $where_values";
	
	$downtimes = db_get_all_rows_sql ($sql);
	$downtimes_number_res = db_get_all_rows_sql($sql_count);
	$downtimes_number = $downtimes_number_res != false ? $downtimes_number_res[0]['num'] : 0;
}
else {
	$downtimes = array();
}

// No downtimes cause the user has not anyone
if (!$downtimes && !$filter_performed) {
	require_once ($config['homedir'] . "/general/firts_task/planned_downtime.php");
}
// No downtimes cause the user performed a search
else if (!$downtimes) {
	// Filter form
	echo "<form method='post' action='index.php?sec=estado&sec2=godmode/agentes/planned_downtime.list'>";
		html_print_table($table_form);
	echo "</form>";
	
	// Info message
	echo '<div class="nf">'.__('No planned downtime').'</div>';
	
	echo '<div class="action-buttons" style="width: 100%">';

	// Create button
	if ($write_permisson) {
		echo '&nbsp;';
		echo '<form method="post" action="index.php?sec=estado&amp;sec2=godmode/agentes/planned_downtime.editor" style="display: inline;">';
		html_print_submit_button (__('Create'), 'create', false, 'class="sub next"');
		echo '</form>';
	}
	
	echo '</div>';
}
// Has downtimes
else {
	echo "<form method='post' action='index.php?sec=estado&sec2=godmode/agentes/planned_downtime.list'>";
		html_print_table($table_form);
	echo "</form>";
	
	ui_pagination($downtimes_number, "index.php?sec=estado&sec2=godmode/agentes/planned_downtime.list&$filter_params_str", $offset);
	
	// User groups with AR, AD or AW permission
	$groupsAD = users_get_groups($config['id_user'], $access);
	$groupsAD = array_keys($groupsAD);
	
	// View available downtimes present in database (if any of them)
	$table = new StdClass();
	$table->class = 'databox data';
	$table->width = '100%';
	$table->cellstyle = array();
	
	$table->head = array();
	$table->head['name'] = __('Name #Ag.');
	$table->head['description'] = __('Description');
	$table->head['group'] = __('Group');
	$table->head['type'] = __('Type');
	$table->head['execution'] = __('Execution');
	$table->head['configuration'] = __('Configuration');
	$table->head['running'] = __('Running');
	
	if ($write_permisson || $manage_permisson) {
		$table->head['stop'] = __('Stop downtime');
		$table->head['edit'] = __('Edit');
		$table->head['delete'] = __('Delete');
	}
	
	$table->align = array();
	$table->align['group'] = "center";
	$table->align['running'] = "center";
	
	if ($write_permisson || $manage_permisson) {
		$table->align['stop'] = "center";
		$table->align['edit'] = "center";
		$table->align['delete'] = "center";
	}
	
	$table->data = array();
	
	foreach ($downtimes as $downtime) {
		$data = array();
		$total  = db_get_sql ("SELECT COUNT(id_agent)
			FROM tplanned_downtime_agents
			WHERE id_downtime = ".$downtime["id"]);
		
		$data['name'] = $downtime['name']. " ($total)";
		$data['description'] = $downtime['description'];
		$data['group'] = ui_print_group_icon ($downtime['id_group'], true);
		
		$type_text = array('quiet' => __('Quiet'),
			'disable_agents' => __('Disabled Agents'),
			'disable_agents_alerts' => __('Disabled only Alerts'));
		
		$data['type'] = $type_text[$downtime['type_downtime']];
		
		$execution_text = array('once' => __('once'),
			'periodically' => __('Periodically'));
		
		$data['execution'] = $execution_text[$downtime['type_execution']];
		
		$data['configuration'] = reporting_format_planned_downtime_dates($downtime);
		
		if ($downtime["executed"] == 0) {
			$data['running'] = html_print_image ("images/pixel_red.png", true,
				array ('width' => 20, 'height' => 20, 'title' => __('Not running')));
		}
		else {
			$data['running'] = html_print_image ("images/pixel_green.png", true,
				array ('width' => 20, 'height' => 20, 'title' => __('Running')));
		}
		
		// If user have writting permissions
		if (in_array($downtime['id_group'], $groupsAD)) {
			// Stop button
			if ($downtime['type_execution'] == 'once' && $downtime["executed"] == 1) {
				
				$data['stop'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime.list' .
					'&stop_downtime=1&id_downtime=' . $downtime['id'] . '&' . $filter_params_str . '">' .
				html_print_image("images/cancel.png", true, array("title" => __('Stop downtime')));
			}
			else {
				$data['stop'] = "";
			}
			
			// Edit & delete buttons
			if ($downtime["executed"] == 0) {
				// Edit
				$data['edit'] = '<a href="index.php?sec=estado&sec2=godmode/agentes/planned_downtime.editor&edit_downtime=1&id_downtime='.$downtime['id'].'">' .
					html_print_image("images/config.png", true, array("title" => __('Update'))) . '</a>';
				// Delete
				$data['delete'] = '<a id="delete_downtime" href="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime.list'.
					'&delete_downtime=1&id_downtime=' . $downtime['id'] . '&' . $filter_params_str . '">' .
					html_print_image("images/cross.png", true, array("title" => __('Delete')));
			}
			else if ($downtime["executed"] == 1 && $downtime['type_execution'] == 'once') {
				// Edit
				$data['edit'] = '<a href="index.php?sec=estado&sec2=godmode/agentes/planned_downtime.editor&edit_downtime=1&id_downtime=' . $downtime['id'] . '">' .
					html_print_image("images/config.png", true, array("title" => __('Update'))) . '</a>';
				// Delete
				$data['delete']= __('N/A');
			}
			else {
				$data['edit']= '';
				$data['delete']= '';
			}
		}
		else {
			$data['stop'] = '';
			$data['edit'] = '';
			$data['delete'] = '';
		}

		if (!empty($malformed_downtimes_exist) && isset($malformed_downtimes[$downtime['id']])) {
			$next_row_num = count($table->data);
			$table->cellstyle[$next_row_num][0] = 'color: red';
			$table->cellstyle[$next_row_num][1] = 'color: red';
			$table->cellstyle[$next_row_num][3] = 'color: red';
			$table->cellstyle[$next_row_num][4] = 'color: red';
			$table->cellstyle[$next_row_num][5] = 'color: red';
		}

		array_push ($table->data, $data);
	}
	
	html_print_table ($table);
	echo '<div class="action-buttons" style="width: '.$table->width.'">';

	echo '<br>';
	// CSV export button
	echo '<div style="display: inline;">';
		html_print_button(__('Export to CSV'), 'csv_export', false, 
			"location.href='godmode/agentes/planned_downtime.export_csv.php?$filter_params_str'", 'class="sub next"');
	echo '</div>';
	
	// Create button
	if ($write_permisson) {
		echo '&nbsp;';
		echo '<form method="post" action="index.php?sec=estado&amp;sec2=godmode/agentes/planned_downtime.editor" style="display: inline;">';
		html_print_submit_button (__('Create'), 'create', false, 'class="sub next"');
		echo '</form>';
	}
	
	echo '</div>';
}

ui_require_jquery_file("ui.datepicker-" . get_user_language(), "include/javascript/i18n/");

?>
<script language="javascript" type="text/javascript">

$("input[name=module_name_hidden]").val(<?php echo (int)$module_id; ?>);

$(document).ready (function () {
	$("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
	$.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);

	$("a#delete_downtime").click(function (e) {
		if (!confirm("<?php echo __('WARNING: If you delete this planned downtime, it will not be taken into account in future SLA reports'); ?>")) {
			e.preventDefault();
		}
	});

	if (<?php echo json_encode($malformed_downtimes_exist) ?> && <?php echo json_encode($migrate_malformed == false) ?>) {
		if (confirm("<?php echo __('WARNING: There are malformed planned downtimes') . '.\n' . __('Do you want to migrate automatically the malformed items?'); ?>")) {
			window.location.href = "index.php?sec=estado&sec2=godmode/agentes/planned_downtime.list&migrate_malformed=1";
		}
	}
});

</script>