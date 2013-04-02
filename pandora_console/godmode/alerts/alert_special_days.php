<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Copyright (c) 2012-2013 Junichi Satoh
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

if (is_ajax ()) {
	$get_alert_command = (bool) get_parameter ('get_alert_command');
	if ($get_alert_command) {
		$id = (int) get_parameter ('id');
		$command = alerts_get_alert_command ($id);
		echo json_encode ($command);
	}
	return;
}

// Header
ui_print_page_header (__('Alerts').' &raquo; '.__('Special days list'), "images/god2.png", false, "alert_special_days", true);

$update_special_day = (bool) get_parameter ('update_special_day');
$create_special_day = (bool) get_parameter ('create_special_day');
$delete_special_day = (bool) get_parameter ('delete_special_day');

if ($create_special_day) {
	$date = (string) get_parameter ('date');
	$same_day = (string) get_parameter ('same_day');
	$values = array();
	$values['id_group'] = (string) get_parameter ('id_group');
	$values['description'] = (string) get_parameter ('description');
	
	list($year, $month, $day) = explode("-", $date);
	if ($year == '*') {
		# '0001' means every year.
		$year = '0001';
		$date = $year . '-' . $month . '-' . $day;
	}
	
	if (!checkdate ($month, $day, $year)) {
		$result = '';
	}
	else {
		$date_check = db_get_value ('date', 'talert_special_days', 'date
			', $date);
		if ($date_check == $date) {
			$result = '';
		}
		else {
			$result = alerts_create_alert_special_day ($date, $same_day, $values);
			$info = 'Date: ' . $date . ' Same day of the week: ' . $same_day . ' Description: ' . $values['description'];
		}
	}
	
	if ($result) {
		db_pandora_audit("Command management", "Create special day " . $result, false, false, $info);
	}
	else {
		db_pandora_audit("Command management", "Fail try to create special day", false, false);
	}
	
	ui_print_result_message ($result, 
		__('Successfully created'),
		__('Could not be created'));
}

if ($update_special_day) {
	$id = (int) get_parameter ('id');
	$alert = alerts_get_alert_special_day ($id);
	$date = (string) get_parameter ('date');
	$same_day = (string) get_parameter ('same_day');
	$description = (string) get_parameter ('description');
	$id_group = (string) get_parameter ('id_group');
	
	list($year, $month, $day) = explode("-", $date);
	if ($year == '*') {
		# '0001' means every year.
		$year = '0001';
		$date = $year . '-' . $month . '-' . $day;
	}
	
	$values = array ();
	$values['date'] = $date;
	$values['id_group'] = $id_group;
	$values['same_day'] = $same_day;
	$values['description'] = $description;
	
	if (!checkdate ($month, $day, $year)) {
		$result = '';
	}
	else {
		$result = alerts_update_alert_special_day ($id, $values);
		$info = 'Date: ' . $date . ' Same day of the week: ' . $same_day . ' Description: ' . $description;
	}
	
	if ($result) {
		db_pandora_audit("Command management", "Update special day " . $id, false, false, $info);
	}
	else {
		db_pandora_audit("Command management", "Fail to update special day " . $id, false, false);
	}
	
	ui_print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
}

if ($delete_special_day) {
	$id = (int) get_parameter ('id');
	
	$result = alerts_delete_alert_special_day ($id);
	
	if ($result) {
		db_pandora_audit("Command management", "Delete special day " . $id);
	}
	else {
		db_pandora_audit("Command management", "Fail to delete special day " . $id);
	}
	
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

$table->width = '98%';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Date');
$table->head[1] = __('Same day of the week');
$table->head[2] = __('Description');
$table->head[3] = __('Group');
$table->head[4] = __('Delete');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '20%';
$table->size[1] = '15%';
$table->size[2] = '55%';
$table->size[3] = '5%';
$table->size[4] = '5%';
$table->align = array ();
$table->align[3] = 'center';
$table->align[4] = 'center';

$filter = array();
if (!is_user_admin($config['id_user']))
	$filter['id_group'] = array_keys(users_get_groups(false, "LM"));

$special_days = db_get_all_rows_filter ('talert_special_days', $filter);
if ($special_days === false)
	$special_days = array ();

foreach ($special_days as $special_day) {
	$data = array ();
	
	$data[0] = '<span style="font-size: 7.5pt">';
	# '0001' means every year.
	$data[0] .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_special_days&id='.$special_day['id'].'">'.
		str_replace('0001', '*', $special_day['date']) . '</a>';
	$data[0] .= '</span>';
	switch ($special_day['same_day']) {
		case 'monday':
			$data[1] = __('Monday');
			break;
		case 'tuesday':
			$data[1] = __('Tuesday');
			break;
		case 'wednesday':
			$data[1] = __('Wednesday');
			break;
		case 'thursday':
			$data[1] = __('Thursday');
			break;
		case 'friday':
			$data[1] = __('Friday');
			break;
		case 'saturday':
			$data[1] = __('Saturday');
			break;
		case 'sunday':
			$data[1] = __('Sunday');
			break;
	} 
	$data[2] = $special_day['description'];
	$data[3] = ui_print_group_icon ($special_day["id_group"], true);
	$data[4] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days&delete_special_day=1&id='.$special_day['id'].'"
		onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.
		html_print_image("images/cross.png", true) . '</a>';
	
	array_push ($table->data, $data);
}

if(isset($data)) {
	html_print_table ($table);
}
else {
	echo "<div class='nf'>".__('No special days configured')."</div>";
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_special_days">';
html_print_submit_button (__('Create'), 'create', false, 'class="sub next"');
html_print_input_hidden ('create_special_day', 1);
echo '</form>';
echo '</div>';
?>
