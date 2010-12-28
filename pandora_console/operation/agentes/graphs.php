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

if (! give_acl ($config['id_user'], $id_grupo, "AR")) {
	pandora_audit("ACL Violation",
		"Trying to access (read) to agent ".get_agent_name($id_agente));
	include ("general/noaccess.php");
	return;
}

require_once('include/fgraph.php');

$period = get_parameter ( "period", 3600);
$draw_alerts = get_parameter("draw_alerts", 0);
$avg_only = get_parameter ("avg_only", 0);
$period = get_parameter ("period", 3600);
$width = get_parameter ("width", 555);
$height = get_parameter ("height", 245);
$label = get_parameter ("label", "");
$start_date = get_parameter ("start_date", date("Y-m-d"));
$draw_events = get_parameter ("draw_events", 0);
$zoom = get_parameter ("zoom", 1);
$modulesChecked = get_parameter('modules', array());
$filter = get_parameter('filter', 0);

$modules = get_agent_modules($id_agente);

if (!$filter) {
	foreach ($modules as $id => $module) {
		$modulesChecked[$id] = 1;
	}
}


$table = null;
$table->width = '90%';

$table->size = array();
$table->size[0] = '20%';
$table->size[1] = '80%'; 

$table->style[0] = 'font-weight: bolder; text-align: right;';
$table->style[1] = '';

$table->data[0][0] = __('Modules');
$listModules = array();
foreach ($modules as $id => $module) {
	$checked = false;
	if (isset($modulesChecked[$id]))
		$checked = (bool) $modulesChecked[$id];
	$listModules[] = '<span style="white-space: nowrap;">' . print_checkbox('modules[' .  $id . ']', 1, $checked, true) . ' ' . $module . '</span>';
}
$table->data[0][1] = implode(' ', $listModules);

$table->data[1][0] = __('Begin date');
$table->data[1][1] = print_input_text ("start_date", substr ($start_date, 0, 10),'', 10, 40, true);
$table->data[1][1] .= print_image ("images/calendar_view_day.png", true, array ("onclick" => "scwShow(scwID('text-start_date'),this);"));

$table->data[2][0] = __('Zoom factor');
$options = array ();
$options[$zoom] = 'x'.$zoom;
$options[1] = 'x1';
$options[2] = 'x2';
$options[3] = 'x3';
$options[4] = 'x4';
$table->data[2][1] = print_select ($options, "zoom", $zoom, '', '', 0, true);

$table->data[3][0] = __('Time range');
$options = array ();
$options[3600] = human_time_description_raw (3600);
$options[7200] = human_time_description_raw (7200);
$options[21600] = human_time_description_raw (21600);
$options[43200] = human_time_description_raw (43200);
$options[86400] = human_time_description_raw (86400);
$options[172800] = human_time_description_raw (172800);
$options[432000] = human_time_description_raw (432000);
$options[604800] = human_time_description_raw (604800);
$options[1296000] = human_time_description_raw (1296000);
$options[2592000] = human_time_description_raw (2592000);
$options[5184000] = human_time_description_raw (5184000);
$options[15552000] = human_time_description_raw (15552000);
$table->data[3][1] = print_extended_select_for_time($options, 'period', $period, '', '', 0, 7, true) . ' ' . __('secs');

$table->data[4][0] = __('Show events');
$table->data[4][1] = print_checkbox ("draw_events", 1, (bool) $draw_events, true);
$table->data[5][0] = __('Show alerts');
$table->data[5][1] = print_checkbox ("draw_alerts", 1, (bool) $draw_alerts, true);

$htmlForm = '<form method="post" action="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=graphs&id_agente=' . $id_agente . '" >';
$htmlForm .= print_table($table, true);
$htmlForm .= print_input_hidden('filter', 1, true);
$htmlForm .= '<div class="action-buttons" style="width: '.$table->width.'">';
$htmlForm .= print_submit_button (__('Filter'), 'filter_button', false, 'class="sub upd"', true);
$htmlForm .= '</div>';
$htmlForm .= '</form>';

toggle($htmlForm,__('Filter graphs'), __('Toggle filter(s)'));

$utime = get_system_time ();
$current = date("Y-m-d", $utime);

if ($start_date != $current)
	$date = strtotime($start_date);
else
	$date = $utime;

foreach ($modulesChecked as $idModuleShowGraph => $value) {
	echo "<h3>" . $modules[$idModuleShowGraph] . '</h3>';
	if ($config['flash_charts']) {
		echo grafico_modulo_sparse ($idModuleShowGraph, $period, $draw_events, $width, $height,
			$modules[$idModuleShowGraph], $unit_name, $draw_alerts, $avg_only, $pure, $date);
	}
	else {
		$image = 'include/fgraph.php?' . 
			'tipo=sparse' . 
			'&draw_alerts=' . $draw_alerts .
			'&draw_events=' . $draw_events .
			'&id=' . $idModuleShowGraph .
			'&zoom=' . $zoom .
			'&label=' . $modules[$idModuleShowGraph] .
			'&height=' . $height .
			'&width=' . $width .
			'&period=' . $period .
			'&avg_only=' . $avg_only .
			'&date=' . $date;
	
		print_image ($image, false, array ("border" => 0));
	}
}

echo "<div style='clear: both;'></div>";
?>
