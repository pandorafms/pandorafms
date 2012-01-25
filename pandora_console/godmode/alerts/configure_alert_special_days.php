<?php 

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Copyright (c) 2012 Junichi Satoh
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
require_once ("include/functions_alerts.php");

check_login ();

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

ui_require_javascript_file ('calendar');

$id = (int) get_parameter ('id');

$name = '';
$command = '';
$description = '';
$date = '';
$same_day = '';
if ($id) {
	$special_day = alerts_get_alert_special_day ($id);
	$date = str_replace('0001', '*', $special_day['date']);
	$same_day = $special_day['same_day'];
	$description = $special_day['description'];
}

if ($date == '') {
	$date = date ("Y-m-d", get_system_time());
}

// Header
ui_print_page_header (__('Alerts').' &raquo; '.__('Configure special day'), "images/god2.png", false, "", true);

$table->width = '98%';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '20%';
$table->data = array ();
$table->data[0][0] = __('Date');
$table->data[0][1] = html_print_input_text ('date', $date, '', 10, 10, true);
$table->data[0][1] .= html_print_image ("images/calendar_view_day.png", true, array ("alt" => "calendar", "onclick" => "scwShow(scwID('text-date'),this);"));
$table->data[1][0] = __('Same day of the week');
$days = array ();
$days["monday"] = __('Monday');
$days["tuesday"] = __('Tuesday');
$days["wednesday"] = __('Wednesday');
$days["thursday"] = __('Thursday');
$days["friday"] = __('Friday');
$days["saturday"] = __('Saturday');
$days["sunday"] = __('Sunday');
$table->data[1][1] = html_print_select ($days, "same_day", $same_day, '', '', 0, true, false, false);

#$table->data[1][1] = html_print_input_text ('same_day', $same_day, '', 80, 255, true);

$table->data[2][0] = __('Description');
$table->data[2][1] = html_print_textarea ('description', 10, 30, $description, '', true);

echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days">';
html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	html_print_input_hidden ('id', $id);
	html_print_input_hidden ('update_special_day', 1);
	html_print_submit_button (__('Update'), 'create', false, 'class="sub upd"');
} else {
	html_print_input_hidden ('create_special_day', 1);
	html_print_submit_button (__('Create'), 'create', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';
?>
