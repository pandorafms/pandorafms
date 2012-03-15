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

if (! check_acl ($config['id_user'], $id_grupo, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access (read) to agent ".agents_get_name($id_agente));
	include ("general/noaccess.php");
	return;
}

require_once ($config["homedir"] . '/include/functions_graph.php');

$period = get_parameter ( "period", 3600);
$draw_alerts = get_parameter("draw_alerts", 0);
$avg_only = get_parameter ("avg_only", 1);
$period = get_parameter ("period", 3600);
$width = get_parameter ("width", 555);
$height = get_parameter ("height", 245);
$label = get_parameter ("label", "");
$start_date = get_parameter ("start_date", date("Y-m-d"));
$draw_events = get_parameter ("draw_events", 0);
$zoom = get_parameter ("zoom", 1);
$modulesChecked = get_parameter('modules', array());
$filter = get_parameter('filter', 0);

$unit = "";

$modules = agents_get_modules($id_agente);

if (!$filter) {
	foreach ($modules as $id => $module) {
		$modulesChecked[$id] = 1;
	}
}


$table = null;
$table->width = '98%';

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
	$listModules[] = '<span style="white-space: nowrap;">' . html_print_checkbox('modules[' .  $id . ']', 1, $checked, true) . ' ' . $module . '</span>';
}
$table->data[0][1] = implode(' ', $listModules);

$table->data[1][0] = __('Begin date');
$table->data[1][1] = html_print_input_text ("start_date", substr ($start_date, 0, 10),'', 10, 40, true);
$table->data[1][1] .= html_print_image ("images/calendar_view_day.png", true, array ("onclick" => "scwShow(scwID('text-start_date'),this);"));

$table->data[2][0] = __('Zoom factor');
$options = array ();
$options[$zoom] = 'x'.$zoom;
$options[1] = 'x1';
$options[2] = 'x2';
$options[3] = 'x3';
$options[4] = 'x4';
$table->data[2][1] = html_print_select ($options, "zoom", $zoom, '', '', 0, true);

$table->data[3][0] = __('Time range');

$table->data[3][1] = html_print_extended_select_for_time('period', $period, '', '', 0, 7, true);

$table->data[4][0] = __('Show events');
$table->data[4][1] = html_print_checkbox ("draw_events", 1, (bool) $draw_events, true);
$table->data[5][0] = __('Show alerts');
$table->data[5][1] = html_print_checkbox ("draw_alerts", 1, (bool) $draw_alerts, true);

$htmlForm = '<form method="post" action="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=graphs&id_agente=' . $id_agente . '" >';
$htmlForm .= html_print_table($table, true);
$htmlForm .= html_print_input_hidden('filter', 1, true);
$htmlForm .= '<div class="action-buttons" style="width: '.$table->width.'">';
$htmlForm .= html_print_submit_button (__('Filter'), 'filter_button', false, 'class="sub upd"', true);
$htmlForm .= '</div>';
$htmlForm .= '</form>';

ui_toggle($htmlForm,__('Filter graphs'), __('Toggle filter(s)'));

$utime = get_system_time ();
$current = date("Y-m-d", $utime);

if ($start_date != $current)
	$date = strtotime($start_date);
else
	$date = $utime;

foreach ($modulesChecked as $idModuleShowGraph => $value) {
	echo "<h4>" . $modules[$idModuleShowGraph] . '</h4>';
	$unit = modules_get_unit ($idModuleShowGraph);
	echo grafico_modulo_sparse($idModuleShowGraph, $period, $draw_events, $width, $height,
		$modules[$idModuleShowGraph], null, $draw_alerts, $avg_only, false, $date, $unit);
}

echo "<div style='clear: both;'></div>";
?>
