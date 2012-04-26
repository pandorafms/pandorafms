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

if (is_ajax ()) {
	$search_agents = (bool) get_parameter ('search_agents');
	
	if ($search_agents) {
		
		require_once ('include/functions_agents.php');
		
		$id_agent = (int) get_parameter ('id_agent');
		$string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
		$id_group = (int) get_parameter('id_group', -1);
		$addedItems = html_entity_decode((string) get_parameter('add'));
		$addedItems = json_decode($addedItems);
		$all = (string)get_parameter('all', 'all');
		
		if ($addedItems != null) {
			foreach ($addedItems as $item) {
				echo $item . "|\n";
			}
		}
		
		$filter = array ();
		switch ($config['dbtype']) {
			case "mysql":
				$filter[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
				break;
			case "postgresql":	
				$filter[] = '(nombre LIKE \'%'.$string.'%\' OR direccion LIKE \'%'.$string.'%\' OR comentarios LIKE \'%'.$string.'%\')';
				break;
			case "oracle":
				$filter[] = '(UPPER(nombre) LIKE UPPER(\'%'.$string.'%\') OR UPPER(direccion) LIKE UPPER(\'%'.$string.'%\') OR UPPER(comentarios) LIKE UPPER(\'%'.$string.'%\'))';
				break;
		}
		
		if ($id_group != -1)
			$filter['id_grupo'] = $id_group; 
		
		switch ($all) {
			case 'enabled':
				$filter['disabled'] = 0;
				break;
		}
		
		$agents = agents_get_agents ($filter, array ('nombre', 'direccion'));
		if ($agents === false)
			return;
			
		foreach ($agents as $agent) {
			echo io_safe_output($agent['nombre'])."|".io_safe_output($agent['direccion'])."\n";
		}
		
		return;
 	}
 	
 	return;
}


// Load global vars
require_once ("include/config.php");
require_once ("include/functions_agents.php");
require_once ("include/functions_reporting.php");
require_once ('include/functions_modules.php');
require_once ('include/functions_users.php');

check_login();

if (!check_acl ($config['id_user'], 0, "AR")) {
	require ("general/noaccess.php");
	return;
}

ui_require_javascript_file ('calendar');


// Header
ui_print_page_header (__("Export data"), "images/server_export.png");

$group = get_parameter_post ('group', 0);
$agentName = get_parameter_post ('agent', 0);

switch ($config["dbtype"]) {
	case "mysql":
	case "postgresql":
		$agents = agents_get_agents (array('nombre LIKE "' . $agentName . '"'), array ('id_agente'));
		break;
	case "oracle":
		$agents = agents_get_agents (array('nombre LIKE \'%' . $agentName . '%\''), array ('id_agente'));
		break;
}
$agent = $agents[0]['id_agente'];

$module = (array) get_parameter_post ('module_arr', array ());
$start_date = get_parameter_post ('start_date', 0);
$end_date = get_parameter_post ('end_date', 0);
$start_time = get_parameter_post ('start_time', 0);
$end_time = get_parameter_post ('end_time', 0);
$export_type = get_parameter_post ('export_type', 'data');
$export_btn = get_parameter_post ('export_btn', 0);

if (!empty ($export_btn) && !empty ($module)) {

	// Disable SQL cache
	global $sql_cache;
	$sql_cache = array ('saved' => 0);


	//Convert start time and end time to unix timestamps
	$start = strtotime ($start_date." ".$start_time);
	$end = strtotime ($end_date." ".$end_time);
	$period = $end - $start;
	$data = array ();	
	
	//If time is negative or zero, don't process - it's invalid
	if ($start < 1 || $end < 1) {
		ui_print_error_message (__('Invalid time specified'));
		return;
	}

	// ***************************************************
	// Starts, ends and dividers
	// ***************************************************

	switch ($export_type) {
		case "data":
		case "avg":
		default:
			//HTML output - don't style or use XHTML just in case somebody needs to copy/paste it. (Office doesn't handle <thead> and <tbody>)
			$datastart = '<table style="width:98%;"><tr><th>'.__('Agent').'</th><th>'.__('Module').'</th><th>'.__('Data').'</th><th>'.__('Timestamp').'</th></tr>';
			$rowstart = '<tr><td style="text-align: center">';
			$divider = '</td><td style="text-align: center">';
			$rowend = '</td></tr>';
			$dataend = '</table>';
			break;
	}

	// ***************************************************
	// Data processing
	// ***************************************************

	$data = array ();
	switch ($export_type) {
		case "data":
		case "avg":
			// Show header
			echo $datastart;

			foreach ($module as $selected) {

				$output = "";
				$work_period = 120000;
				if ($work_period > $period) {
					$work_period = $period;
				}

				$work_end = $end - $period + $work_period;
				//Buffer to get data, anyway this will report a memory exhaustin

				while ($work_end <= $end) {

					$data = array (); // Reinitialize array for each module chunk
					if ($export_type == "avg") {
						$arr = array ();
						$arr["data"] = reporting_get_agentmodule_data_average ($selected, $work_period, $work_end);
						if ($arr["data"] === false) {
							continue;
						}	
						$arr["module_name"] = modules_get_agentmodule_name ($selected);
						$arr["agent_name"] = modules_get_agentmodule_agent_name ($selected);
						$arr["agent_id"] = modules_get_agentmodule_agent ($selected);
						$arr["utimestamp"] = $end;				
						array_push ($data, $arr);
					}
					else {
						$data_single = modules_get_agentmodule_data ($selected, $work_period, $work_end);
						if (!empty ($data_single)) {
							$data = array_merge ($data, $data_single);
						}
					}
					
					foreach ($data as $key => $module) {
						$output .= $rowstart;
						$output .= io_safe_output($module['agent_name']);
						$output .= $divider;
						$output .= io_safe_output($module['module_name']);
						$output .= $divider;
						$output .= $module['data'];
						$output .= $divider;
						$output .= date ("Y-m-d G:i:s", $module['utimestamp']);
						$output .= $rowend;
					}
					
					switch ($export_type) {
						default:
						case "data":
						case "avg":
							echo $output;
							break;
					}
					unset($output);
					$output = "";
					unset($data);
					unset($data_single);
					$work_end = $work_end + $work_period;
				}
				unset ($output);
				$output = "";
			} // main foreach
			echo $dataend;
			break;
	}
}
elseif (!empty ($export_btn) && empty ($module)) {
	ui_print_error_message (__('No modules specified'));
}

if (empty($export_btn)) {
	echo '<form method="post" action="index.php?sec=reporting&amp;sec2=operation/agentes/exportdata" name="export_form">';
	
	$table->width = '98%';
	$table->border = 0;
	$table->cellspacing = 3;
	$table->cellpadding = 5;
	$table->class = "databox_color";
	$table->style[0] = 'vertical-align: top;';

	$table->data = array ();

	//Group selector
	$table->data[0][0] = '<b>'.__('Group').'</b>';
		
	$groups = users_get_groups ($config['id_user'], "AR");
		
	$table->data[0][1] = html_print_select_groups($config['id_user'], "AR", true, "group", $group, 'submit_group();', '', 0, true, false, true, 'w130', false);
		
	//Agent selector
	$table->data[1][0] = '<b>'.__('Source agent').'</b>';

	if ($group > 0) {
		$filter['id_grupo'] = (array) $group;
	} else {
		$filter['id_grupo'] = array_keys ($groups);
	}

	$agents = array ();
	$rows = agents_get_agents ($filter, false, 'AR');
	if ($rows == null) $rows = array();
	foreach ($rows as $row) {
		$agents[$row['id_agente']] = $row['nombre'];
	}

	//Src code of lightning image with skins 
	$src_code = html_print_image ('images/lightning.png', true, false, true);
	$table->data[1][1] = html_print_input_text_extended ('agent', agents_get_name ($agent), 'text-agent', '', 40, 100, false, '',
		array('style' => "background: url($src_code) no-repeat right;"), true)
		. '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>';
		
	//Module selector
	$table->data[2][0] = '<b>'.__('Modules').'</b>';

	if ($agent > 0) {
		$modules = agents_get_modules ($agent);
	} else {
		$modules = array ();
	}

	$disabled_export_button = false;
	if (empty($modules)) {
		$disabled_export_button = true;
	}

	$table->data[2][1] = html_print_select ($modules, "module_arr[]", array_keys ($modules), '', '', 0, true, true, true, 'w155', false);

	//Start date selector
	$table->data[3][0] = '<b>'.__('Begin date').'</b>';

	$table->data[3][1] = html_print_input_text ('start_date', date ("Y-m-d", get_system_time () - 86400), false, 10, 10, true);
	$table->data[3][1] .= html_print_image ("images/calendar_view_day.png", true, array ("alt" => "calendar", "onclick" => "scwShow(scwID('text-start_date'),this);"));
	$table->data[3][1] .= html_print_input_text ('start_time', date ("H:i", get_system_time () - 86400), false, 10, 5, true);
		
	//End date selector
	$table->data[4][0] = '<b>'.__('End date').'</b>';
	$table->data[4][1] = html_print_input_text ('end_date', date ("Y-m-d", get_system_time ()), false, 10, 10, true);
	$table->data[4][1] .= html_print_image ("images/calendar_view_day.png", true, array ("alt" => "calendar", "onclick" => "scwShow(scwID('text-end_date'),this);"));
	$table->data[4][1] .= html_print_input_text ('end_time', date ("H:i", get_system_time ()), false, 10, 5, true);
		
	//Export type
	$table->data[5][0] = '<b>'.__('Export type').'</b>';

	$export_types = array ();
	$export_types["data"] = __('Data table');
	$export_types["csv"] = __('CSV');
	$export_types["excel"] = __('MS Excel');
	$export_types["avg"] = __('Average per hour/day');

	$table->data[5][1] = html_print_select ($export_types, "export_type", $export_type, '', '', 0, true, false, true, 'w130', false);

	html_print_table ($table);

	// Submit button
	echo '<div class="action-buttons" style="width:80%;">';
		html_print_submit_button (__('Export'), 'export_btn', $disabled_export_button, 'class="sub wand"');
	echo '</div></form>';
}

ui_require_jquery_file ('pandora.controls');
ui_require_jquery_file ('ajaxqueue');
ui_require_jquery_file ('bgiframe');
ui_require_jquery_file ('autocomplete');
?>
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	var inputActive = true;
	
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=operation/agentes/exportdata&search_agents=1&id_group=" + $("#group").val(),
		success: function(msg){
			if (msg.length == 0) {
				$("#text-agent").css ('background-color', '#FF8080');
				$("#text-agent").val("<?php echo __("No agents in this category");?>");
				$("#text-agent").attr("disabled", true);
				$("#text-agent").css ('color', '#000000');
				inputActive = false;
			}
		}
	});
	
	if (inputActive) {
		$("#text-agent").autocomplete(
			"ajax.php",
			{
				minChars: 2,
				scroll:true,
				extraParams: {
					page: "operation/agentes/exportdata",
					search_agents: 1,
					id_group: function() { return $("#group").val(); }
				},
				formatItem: function (data, i, total) {
					if (total == 0)
						$("#text-agent").css ('background-color', '#cc0000');
					else
						$("#text-agent").css ('background-color', '');
					if (data == "")
						return false;
					return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
				},
				delay: 200
			}
		);
	}
	
	$("#text-agent").result(function(event, data, formatted) {
 		this.form.submit();
	});	
	
	$("select#export_type").trigger('change');
});

$("select#export_type").change (function () {
	type = $("#export_type").val();
	var f = document.forms.export_form;
	switch (type) {
		case 'csv':
			f.action = "operation/agentes/exportdata.csv.php";
			break;
		case 'excel':
			f.action = "operation/agentes/exportdata.excel.php";
			break;
		case 'avg':
		case 'data':
			f.action = "index.php?sec=reporting&sec2=operation/agentes/exportdata";
			break;

	}		
});

function submit_group() {
	var f = document.forms.export_form;
	f.action = "index.php?sec=reporting&sec2=operation/agentes/exportdata";
	f.form.submit();
}
/* ]]> */
</script>
