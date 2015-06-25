<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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

require_once ("include/functions_agents.php");
require_once ("include/functions_custom_graphs.php");

if (! check_acl ($config['id_user'], $id_grupo, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access (read) to agent ".agents_get_name($id_agente));
	include ("general/noaccess.php");
	return;
}

require_once ($config["homedir"] . '/include/functions_graph.php');

$draw_alerts = get_parameter("draw_alerts", 0);
$avg_only = get_parameter ("avg_only", 1);
$period = get_parameter ("period", SECONDS_1HOUR);
$width = get_parameter ("width", 555);
$height = get_parameter ("height", 245);
$label = get_parameter ("label", "");
$start_date = get_parameter ("start_date", date("Y-m-d"));
$draw_events = get_parameter ("draw_events", 0);
$zoom = get_parameter ("zoom", 1);
$modules = get_parameter('modules', array());
$filter = get_parameter('filter', 0);
$combined = (bool)get_parameter('combined', 1);


//----------------------------------------------------------------------
// Get modules of agent sorted as:
//  - modules network no proc
//  - modules network proc
//  - others
//----------------------------------------------------------------------
$list_modules = array();

$modules_networkmap_no_proc = agents_get_modules(
	$id_agente, false, array(
		'id_modulo' => 2, // networkmap type
		'id_tipo_modulo' => array(
			'<>2', // != generic_proc
			'<>6', // != remote_icmp_proc
			'<>9', // != remote_tcp_proc
			'<>6', // != remote_tcp_proc
			'<>18', // != remote_snmp_proc
			'<>21', // != async_proc
			'<>31') // != web_proc
		));
if (empty($modules_networkmap_no_proc))
	$modules_networkmap_no_proc = array();

$modules_others = agents_get_modules(
	$id_agente, false, array(
		'id_tipo_modulo' => array(
			'<>2', // != generic_proc
			'<>6', // != remote_icmp_proc
			'<>9', // != remote_tcp_proc
			'<>6', // != remote_tcp_proc
			'<>18', // != remote_snmp_proc
			'<>21', // != async_proc
			'<>31') // != web_proc
		));
if (empty($modules_others))
	$modules_others = array();

//Cleaned the duplicate $modules and other things
$modules_others = array_diff_key($modules_others,
	$modules_networkmap_no_proc);
foreach ($modules_others as $i => $m) {
	$modules_others[$i] = array(
		'optgroup' => __('Other modules'),
		'name' => $m);
}
foreach ($modules_networkmap_no_proc as $i => $m) {
	$modules_networkmap_no_proc[$i] = array(
		'optgroup' => __('Modules network no proc'),
		'name' => $m);
}


$list_modules = $modules_networkmap_no_proc +
	$modules_others;
//----------------------------------------------------------------------

if (empty($modules)) {
	//Selected the first 6 modules.
	$module_ids = array_keys($list_modules);
	$module_ids = array_slice($module_ids, 0, 6);
	$modules = $module_ids;
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->style[0] = 'font-weight: bolder; text-align: left;';
$table->size[0] = '10%';
$table->style[1] = 'font-weight: bolder; text-align: left;';
$table->size[1] = '15%';
$table->style[2] = 'font-weight: bolder; text-align: left;';
$table->size[2] = '10%';
$table->style[3] = 'font-weight: bolder; text-align: left;';
$table->size[3] = '20%';

$table->rowspan[0][0] = 7;
$table->rowspan[0][1] = 7;

$table->data[0][0] = __('Modules');
$table->data[0][1] = html_print_select($list_modules, 'modules[]',
	$modules, '', '', 0, true, true,
	true, '', false, "width: 100%; height: 200px;");

$table->rowspan[2][0] = 7;
$table->data[2][0] = "";


$table->data[2][1] = __('Begin date');
$table->data[2][2] = html_print_input_text ("start_date", substr ($start_date, 0, 10),'', 10, 40, true);
$table->data[2][2] .= html_print_image ("images/calendar_view_day.png", true, array ("onclick" => "scwShow(scwID('text-start_date'),this);"));

$table->data[3][1] = __('Zoom factor');
$options = array ();
$options[$zoom] = 'x' . $zoom;
$options[1] = 'x1';
$options[2] = 'x2';
$options[3] = 'x3';
$options[4] = 'x4';
$table->data[3][2] = html_print_select ($options, "zoom", $zoom, '', '', 0, true);

$table->data[4][1] = __('Time range');

$table->data[4][2] = html_print_extended_select_for_time('period', $period, '', '', 0, 7, true);

$table->data[5][2] = __('Show events');
$table->data[5][3] = html_print_checkbox ("draw_events", 1, (bool) $draw_events, true);
$table->data[6][2] = __('Show alerts') .
	ui_print_help_tip(__('the combined graph does not show the alerts into this graph'), true);
$table->data[6][3] = html_print_checkbox ("draw_alerts", 1, (bool) $draw_alerts, true);
$table->data[7][2] = __('Show as one combined graph');
$table->data[7][3] = 
	html_print_radio_button('combined', 1, __('one combined graph'),
		$combined, true);
$table->data[7][3] .= 
	html_print_radio_button('combined', 0, __('several graphs for each module'),
		$combined, true);

$htmlForm = '<form method="post" action="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=graphs&id_agente=' . $id_agente . '" >';
$htmlForm .= html_print_table($table, true);
$htmlForm .= html_print_input_hidden('filter', 1, true);
$htmlForm .= '<div class="action-buttons" style="width: '.$table->width.'">';
$htmlForm .= html_print_button(__('Save as custom graph'), 'save_custom_graph',
		false, 'save_custom_graph();', 'class="sub add" style=""',true) . '&nbsp;&nbsp;' .
		 html_print_submit_button (__('Filter'), 'filter_button', false, 'class="sub upd" style=""', true);
$htmlForm .= '</div>';
$htmlForm .= '</form>';

ui_toggle($htmlForm,__('Filter graphs'), __('Toggle filter(s)'), false);

$utime = get_system_time ();
$current = date("Y-m-d", $utime);

if ($start_date != $current)
	$date = strtotime($start_date);
else
	$date = $utime;

if ($combined) {
	
	echo "<div style='width: 555px;'><strong style='font-size:9pt;'>" . __('Combined graph') . '</strong>';
	echo "</div>";
	
	custom_graphs_print(0,
		$height,
		$width,
		$period,
		CUSTOM_GRAPH_LINE,
		false,
		$date,
		false,
		'white',
		$modules);
}
else {
	foreach ($modules as $id_module) {
		$title = modules_get_agentmodule_name($id_module);
		
		echo "<h4>" . $title . '</h4>';
		
		$unit = modules_get_unit ($id_module);
		
		echo grafico_modulo_sparse($id_module,
			$period,
			$draw_events,
			$width,
			$height,
			$title,
			null,
			$draw_alerts,
			$avg_only,
			false,
			$date,
			$unit);
	}
}

echo "<div style='clear: both;'></div>";

//Dialog to save the custom graph
echo "<div id='dialog_save_custom_graph' style='display: none;'>";
$table = null;
$table->width = '100%';
$table->style[0] = 'font-weight: bolder; text-align: right;';
$table->data[0][0] = __('Name custom graph');
$table->data[0][1] =
	html_print_input_text('name_custom_graph', '',
		__('Name custom graph'), 30, 50, true);

html_print_table($table);

echo "<div style='width: " . $table->width . "; text-align: right;'>";
	html_print_image('images/spinner.gif', false,
		array('style' => 'display: none',
			'class' => 'loading_save'));
	html_print_image('images/ok.png', false,
		array('style' => 'display: none',
			'class' => 'ok_save'));
	html_print_image('images/error_red.png', false,
		array('style' => 'display: none',
			'class' => 'error_save'));
	html_print_button(__('Save'), 'save_custom_graph',
		false, 'save_custom_graph_second_step();', 'class="button_save sub save"');
	echo "</div>";
echo "</div>";
?>
<script type="text/javascript">
	$(document).ready(function() {
		$("#dialog_save_custom_graph").dialog({
			title: "<?php echo __('Save custom graph');?>",
			height: 200,
			width: 500,
			modal: true,
			autoOpen: false
		});
	});
	
	function save_custom_graph() {
		$("#dialog_save_custom_graph").dialog("open");
	}
	
	function save_custom_graph_second_step() {
		$(".button_save").disable();
		$(".ok_save").hide();
		$(".error_save").hide();
		$(".loading_save").show();
		
		var params = {};
		params["id_modules"] = <?php echo json_encode($modules); ?>;
		params["name"] = $("input[name='name_custom_graph']").val();
		params["description"] = "<?php echo __('Custom graph create from the tab graphs in the agent.'); ?>";
		params["stacked"] = <?php echo CUSTOM_GRAPH_LINE; ?>;
		params["width"] = <?php echo $width ?>;
		params["height"] = <?php echo $height ?>;
		params["events"] = <?php echo $draw_events ?>;
		params["period"] = <?php echo $period ?>;
		
		params["save_custom_graph"] = 1;
		params["page"] = "include/ajax/graph.ajax";
		jQuery.ajax ({
			data: params,
			dataType: "json",
			type: "POST",
			url: "ajax.php",
			async: false,
			timeout: 10000,
			success: function (data) {
				$(".loading_save").hide();
				if (data.correct) {
					$(".ok_save").show();
				}
				else {
					$(".error_save").show();
					$(".button_save").enable();
				}
			}
		});
	}
</script>