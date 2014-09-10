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

if (! check_acl ($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access downtime scheduler");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_users.php');
require_once ('include/functions_events.php');

// Header
ui_print_page_header(
	__("Planned Downtime"),
	"images/gm_monitoring.png",
	false,
	"planned_downtime",
	true,
	"");

$delete_downtime = (int) get_parameter ('delete_downtime');
$id_downtime = (int) get_parameter ('id_downtime', 0);

$stop_downtime = (bool) get_parameter ('stop_downtime');
// STOP DOWNTIME
if ($stop_downtime) {
	$downtime = db_get_row('tplanned_downtime', 'id', $id_downtime);
	
	switch ($downtime['type_execution']) {
		case 'once':
			$date_stop = date ("Y-m-j", get_system_time ());
			$time_stop = date ("h:iA", get_system_time ());
			
			$values = array(
				'executed' => 0,
				'date_to' => strtotime($date_stop . ' ' . $time_stop)
				);
			
			$result = db_process_sql_update('tplanned_downtime',
				$values, array ('id' => $id_downtime));
			break;
		case 'periodically':
			break;
	}
	
	ui_print_result_message($result,
		__('Succesful stopped the Downtime'),
		__('Unsuccesful stopped the Downtime'));
	
	if ($result) {
		events_create_event ("Manual stop downtime  ".
			$downtime['name'] . " (" . $downtime['id'] . ") by " .
			$config['id_user'], 0, 0, EVENT_STATUS_NEW, $config["id_user"],
			"system", 1);
		db_pandora_audit("Planned Downtime management",
			"Manual stop downtime " . $downtime['name'] . " (ID " . $downtime['id'] . ")",
			false, true);
		
		//Reenabled the Agents or Modules or alerts...depends of type
		$downtime = db_get_row('tplanned_downtime', 'id', $id_downtime);
		
		switch ($downtime['type_downtime']) {
			case 'quiet':
				$agents = db_get_all_rows_filter(
					'tplanned_downtime_agents',
					array('id_downtime' => $id_downtime));
				if (empty($agents))
					$agents = array();
				
				$count = 0;
				foreach ($agents as $agent) {
					if ($agent['all_modules']) {
						$result = db_process_sql_update('tagente',
							array('quiet' => 0),
							array('id_agente' => $agent['id_agent']));
						
						if ($result)
							$count++;
					}
					else {
						$modules = db_get_all_rows_filter(
							'tplanned_downtime_modules',
							array('id_agent' => $agent['id_agent'],
								'id_downtime' => $id_downtime));
						if (empty($modules))
							$modules = array();
						
						foreach ($modules as $module) {
							$result = db_process_sql_update(
								'tagente_modulo',
								array('quiet' => 0),
								array('id_agente_modulo' =>
									$module['id_agent_module']));
							
							if ($result)
								$count++;
						}
					}
				}
				break;
			case 'disable_agents':
				$agents = db_get_all_rows_filter(
					'tplanned_downtime_agents',
					array('id_downtime' => $id_downtime));
				if (empty($agents))
					$agents = array();
				
				$count = 0;
				foreach ($agents as $agent) {
					$result = db_process_sql_update('tagente',
						array('disabled' => 0),
						array('id_agente' => $agent['id_agent']));
					
					if ($result)
						$count++;
				}
				break;
			case 'disable_agents_alerts':
				$agents = db_get_all_rows_filter(
					'tplanned_downtime_agents',
					array('id_downtime' => $id_downtime));
				if (empty($agents))
					$agents = array();
				
				$count = 0;
				foreach ($agents as $agent) {
					$modules = db_get_all_rows_filter(
						'tagente_modulo',
						array('id_agente' => $agent['id_agent']));
					if (empty($modules))
						$modules = array();
					
					foreach ($modules as $module) {
						$result = db_process_sql_update(
							'talert_template_modules',
							array('disabled' => 0),
							array('id_agent_module' =>
								$module['id_agente_modulo']));
						
						if ($result)
							$count++;
					}
				}
				break;
		}
		
		ui_print_info_message(
			sprintf(__('Enabled %s elements from the downtime'), $count));
	}
}

// DELETE WHOLE DOWNTIME!
if ($delete_downtime) {
	$result = db_process_sql_delete('tplanned_downtime', array('id' => $id_downtime));
	
	if ($result === false) {
		ui_print_error_message(__('Not deleted. Error deleting data'));
	}
	else {
		ui_print_success_message(__('Successfully deleted'));
	}
}

// Filter parameters
$offset = (int) get_parameter('offset');
$search_text = (string) get_parameter('search_text');
$date_from = (string) get_parameter('date_from');
$date_to = (string) get_parameter('date_to');
$execution_type = (string) get_parameter('execution_type');
$show_archived = (bool) get_parameter('archived');
$agent_id = (int) get_parameter('agent_id');
$agent_name = !empty($agent_id) ? (string) get_parameter('agent_name') : "";
$module_id = (int) get_parameter('module_name_hidden');
$module_name = !empty($module_id) ? (string) get_parameter('module_name') : "";

$filter_params = array();
$filter_params['search_text'] = $search_text;
$filter_params['date_from'] = $date_from;
$filter_params['date_to'] = $date_to;
$filter_params['execution_type'] = $execution_type;
$filter_params['archived'] = $show_archived;
$filter_params['agent_id'] = $agent_id;
$filter_params['agent_name'] = $agent_name;
$filter_params['module_id'] = $module_id;
$filter_params['module_name'] = $module_name;

$filter_params_aux = array();
foreach ($filter_params as $name => $value) {
	$filter_params_aux[] = is_bool($value) ? $name."=".(int)$value : "$name=$value";
}
$filter_params_str = !empty($filter_params_aux) ? implode("&", $filter_params_aux) : "";

// Table filter
$table = new StdClass();
$table->class = 'databox';
$table->width = '99%';
$table->rowstyle = array();
$table->rowstyle[0] = "background-color: #f9faf9;";
$table->rowstyle[1] = "background-color: #f9faf9;";
$table->rowstyle[2] = "background-color: #f9faf9;";
$table->data = array();

$row = array();

// Search text
$row[] = __('Search') . '&nbsp;' . html_print_input_text("search_text", $search_text, '', 50, 250, true);
// Dates
$date_inputs = __('From') . '&nbsp;' . html_print_input_text('date_from', $date_from, '', 10, 10, true);
$date_inputs .= "&nbsp;&nbsp;";
$date_inputs .= __('To') . '&nbsp;' . html_print_input_text('date_to', $date_to, '', 10, 10, true);
$row[] = $date_inputs;

$table->data[] = $row;

$row = array();

// Execution type
$execution_type_fields = array('once' => __('Once'), 'periodically' => __('Periodically'));
$row[] = __('Execution type') . '&nbsp;' . html_print_select($execution_type_fields, 'execution_type', $execution_type, '', __('Any'), '', true, false, false);
// Show past downtimes
$row[] = __('Show past downtimes') . '&nbsp;' . html_print_checkbox ("archived", 1, $show_archived, true);

$table->data[] = $row;

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
$module_input = __('Module') . '&nbsp;' . html_print_autocomplete_modules('module_name', $module_name, false, true, '', array(), true);
$row[] = $module_input;

$row[] = html_print_submit_button('Search', 'search', false, 'class="sub search"', true);

$table->data[] = $row;

echo "<form method='post' action='index.php?sec=estado&sec2=godmode/agentes/planned_downtime.list'>";
html_print_table($table);
echo "</form>";

// View available downtimes present in database (if any of them)
$table = new StdClass();
$table->class = 'databox';
//Start Overview of existing planned downtime
$table->width = '98%';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name #Ag.');
$table->head[1] = __('Description');
$table->head[2] = __('Group');
$table->head[3] = __('Type');
$table->head[4] = __('Execution');
$table->head[5] = __('Configuration');
$table->head[6] = __('Running');
$table->head[7] = __('Stop downtime');
$table->head[8] = __('Edit');
$table->head[9] = __('Delete');
$table->align[2] = "center";
//$table->align[5] = "center";
$table->align[6] = "center";
$table->align[7] = "center";
$table->align[8] = "center";
$table->align[9] = "center";

$groups = users_get_groups ();
if(!empty($groups)) {
	$where_values = "1=1";

	$groups_string = implode (",", array_keys ($groups));
	$where_values .= " AND id_group IN ($groups_string)";

	if (!empty($search_text)) {
		$where_values .= " AND (name LIKE '%$search_text%' OR description LIKE '%$search_text%')";
	}

	if (!empty($execution_type)) {
		$where_values .= " AND type_execution = '$execution_type'";
	}

	if (!empty($date_from)) {
		$where_values .= " AND (type_execution = 'periodically' OR (type_execution = 'once' AND date_from >= '".strtotime("$date_from 00:00:00")."'))";
	}

	if (!empty($date_to)) {
		$periodically_monthly_w = "type_periodicity = 'monthly' AND (periodically_day_from <= '".date('d', strtotime($date_from))."' AND periodically_time_to >= '".date('d', strtotime($date_to))."')";
		
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
		$where_values .= " AND (type_execution = 'periodically' OR (type_execution = 'once' AND date_to >= '".time()."'))";
	}

	if (!empty($agent_id)) {
		$where_values .= " AND id IN (SELECT id_downtime FROM tplanned_downtime_agents WHERE id_agent = $agent_id)";
	}

	if (!empty($module_id)) {
		$where_values .= " AND (id IN (SELECT id_downtime
									   FROM tplanned_downtime_modules
									   WHERE id_agent_module = $module_id)
							OR id IN (SELECT id_downtime
									  FROM tplanned_downtime_agents tpda, tagente_modulo tam
									  WHERE tpda.id_agent = tam.id_agente
									  	AND tam.id_agente_modulo = $module_id
									  	AND tpda.all_modules = 1))";
	}

	$sql = "SELECT *
			FROM tplanned_downtime
			WHERE $where_values
			ORDER BY type_execution DESC, date_from DESC
			LIMIT ".$config["block_size"]."
			OFFSET $offset";
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

if (!$downtimes) {
	echo '<div class="nf">'.__('No planned downtime').'</div>';
}
else {
	ui_pagination($downtimes_number, "index.php?sec=estado&sec2=godmode/agentes/planned_downtime.list&$filter_params_str", $offset);

	foreach ($downtimes as $downtime) {
		$data = array();
		$total  = db_get_sql ("SELECT COUNT(id_agent)
			FROM tplanned_downtime_agents
			WHERE id_downtime = ".$downtime["id"]);
		
		$data[0] = $downtime['name']. " ($total)";
		$data[1] = $downtime['description'];
		$data[2] = ui_print_group_icon ($downtime['id_group'], true);
		
		$type_text = array('quiet' => __('Quiet'),
			'disable_agents' => __('Disabled Agents'),
			'disable_agents_alerts' => __('Disabled only Alerts'));
		
		$data[3] = $type_text[$downtime['type_downtime']];
		
		$execution_text = array('once' => __('once'),
			'periodically' => __('Periodically'));
		
		$data[4] = $execution_text[$downtime['type_execution']];
		
		switch ($downtime['type_execution']) {
			case 'once':
				$data[5] = date ("Y-m-d H:i", $downtime['date_from']) .
					"&nbsp;" . __('to') . "&nbsp;".
					date ("Y-m-d H:i", $downtime['date_to']);
				break;
			case 'periodically':
				switch ($downtime['type_periodicity']) {
					case 'weekly':
						$data[5] = __('Weekly:');
						$data[5] .= "&nbsp;";
						if ($downtime['monday']) {
							$data[5] .= __('Mon');
							$data[5] .= "&nbsp;";
						}
						if ($downtime['tuesday']) {
							$data[5] .= __('Tue');
							$data[5] .= "&nbsp;";
						}
						if ($downtime['wednesday']) {
							$data[5] .= __('Wed');
							$data[5] .= "&nbsp;";
						}
						if ($downtime['thursday']) {
							$data[5] .= __('Thu');
							$data[5] .= "&nbsp;";
						}
						if ($downtime['friday']) {
							$data[5] .= __('Fri');
							$data[5] .= "&nbsp;";
						}
						if ($downtime['saturday']) {
							$data[5] .= __('Sat');
							$data[5] .= "&nbsp;";
						}
						if ($downtime['sunday']) {
							$data[5] .= __('Sun');
							$data[5] .= "&nbsp;";
						}
						$data[5] .= "&nbsp;(" . $downtime['periodically_time_from']; 
						$data[5] .= "-" . $downtime['periodically_time_to'] . ")";
						break;
					case 'monthly':
						$data[5] = __('Monthly:');
						$data[5] .= __('From day') . "&nbsp;" . $downtime['periodically_day_from'];
						$data[5] .= "/" . __('To day') . "&nbsp;";
						$data[5] .= $downtime['periodically_day_to'];
						$data[5] .= "&nbsp;(" . $downtime['periodically_time_from'];
						$data[5] .= "-" . $downtime['periodically_time_to'] . ")";
						break;
				}
				break;
		}
		
		if ($downtime["executed"] == 0) {
			$data[6] = html_print_image ("images/pixel_red.png", true,
				array ('width' => 20, 'height' => 20, 'alt' => __('Executed')));
		}
		else {
			$data[6] = html_print_image ("images/pixel_green.png", true,
				array ('width' => 20, 'height' => 20, 'alt' => __('Not executed')));
		}
		
		if ($downtime['type_execution'] == 'once' && $downtime["executed"] == 1) {
			
			$data[7] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime.list&amp;' .
				'stop_downtime=1&amp;' .
				'id_downtime=' . $downtime['id'] . '">' .
			html_print_image("images/cancel.png", true, array("border" => '0', "alt" => __('Stop downtime')));
		}
		else {
			$data[7] = "";
		}
		
		if ($downtime["executed"] == 0) {
			$data[8] = '<a
				href="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime.editor&amp;edit_downtime=1&amp;id_downtime='.$downtime['id'].'">' .
			html_print_image("images/config.png", true, array("border" => '0', "alt" => __('Update'))) . '</a>';
			$data[9] = '<a id="delete_downtime" href="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime.list&amp;'.
				'delete_downtime=1&amp;id_downtime='.$downtime['id'].'">' .
			html_print_image("images/cross.png", true, array("border" => '0', "alt" => __('Delete')));
		}
		else {
			$data[8]= "N/A";
			$data[9]= "N/A";
		
		}
		array_push ($table->data, $data);
	}
	html_print_table ($table);
}
echo '<div class="action-buttons" style="width: '.$table->width.'">';

echo '<br>';
echo '<div style="display: inline;">';
html_print_button(__('Export to CSV'), 'csv_export', false, "location.href='godmode/agentes/planned_downtime.export_csv.php?$filter_params_str'", 'class="sub next"');
echo '</div>';
echo '&nbsp;';
echo '<form method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime.editor" style="display: inline;">';
html_print_submit_button (__('Create'), 'create', false, 'class="sub next"');
echo '</form>';
echo '</div>';


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
});

</script>