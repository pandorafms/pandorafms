<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Copyright (c) 2012-2016 Junichi Satoh
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
require_once ("include/ics-parser/class.iCalReader.php");

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
ui_print_page_header (__('Alerts').' &raquo; '.__('Special days list'), "images/gm_alerts.png", false, "alert_special_days", true);

$update_special_day = (bool) get_parameter ('update_special_day');
$create_special_day = (bool) get_parameter ('create_special_day');
$delete_special_day = (bool) get_parameter ('delete_special_day');
$upload_ical = (bool)get_parameter('upload_ical', 0);
$display_range = (int) get_parameter ('display_range');

if ($upload_ical) {
	$same_day = (string) get_parameter ('same_day');
	$overwrite = (bool) get_parameter ('overwrite', 0);
	$values = array();
	$values['id_group'] = (string) get_parameter ('id_group');
	$values['same_day'] = $same_day;

	$error = $_FILES['ical_file']['error'];
	$extension = substr($_FILES['ical_file']['name'], -3);

	if ($error == 0 && strcasecmp($extension, "ics") == 0) {
		$skipped_dates = '';
		#$today = date ('Ymd');
		$this_month = date ('Ym');
		$ical = new ICal($_FILES['ical_file']['tmp_name']);
		$events = $ical->events();
		foreach ($events as $event) {
			$event_date = substr($event['DTSTART'], 0, 8);
			$event_month = substr($event['DTSTART'], 0, 6);
			if ($event_month >= $this_month) {
				$values['description'] = @$event['SUMMARY'];
				$values['date'] = $event_date;
				$date = date ('Y-m-d', strtotime($event_date));
				$date_check = '';
				$filter['id_group'] = $values['id_group'];
				$filter['date'] = $date;
				$date_check = db_get_value_filter ('date', 'talert_special_days', $filter);
				if ($date_check == $date) {
					if ($overwrite) {
						$id_special_day = db_get_value_filter ('id', 'talert_special_days', $filter);
						alerts_update_alert_special_day ($id_special_day, $values);
					}
					else {
						if ($skipped_dates == '') {
							$skipped_dates = __('Skipped dates: ');
						}
						$skipped_dates .= $date . " ";
					}
				}
				else {
					alerts_create_alert_special_day ($date, $same_day, $values);
				}
			}
		}
		$result = true;
	}
	else {
		$result = false;
	}

	if ($result) {
		db_pandora_audit ("Special days list", "Upload iCalendar " . $_FILES['ical_file']['name']);
	}

	ui_print_result_message ($result, __('Success to upload iCalendar') . "<br />" . $skipped_dates, __('Fail to upload iCalendar'));
}

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
		$date_check = '';
		$filter['id_group'] = $values['id_group'];
		$filter['date'] = $date;
		$date_check = db_get_value_filter ('date', 'talert_special_days', $filter);
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
	$date_orig = (string) get_parameter ('date_orig');
	$same_day = (string) get_parameter ('same_day');
	$description = (string) get_parameter ('description');
	$id_group = (string) get_parameter ('id_group');
	$id_group_orig = (string) get_parameter ('id_group_orig');
	
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
		if ($id_group != $id_group_orig || $date != $date_orig) {
			$date_check = '';
			$filter['id_group'] = $id_group;
			$filter['date'] = $date;
			$date_check = db_get_value_filter ('date', 'talert_special_days', $filter);
			if ($date_check == $date) {
				$result = '';
			}
			else {
				$result = alerts_update_alert_special_day ($id, $values);
				$info = 'Date: ' . $date . ' Same day of the week: ' . $same_day . ' Description: ' . $description;
			}
		}
		else {
			$result = alerts_update_alert_special_day ($id, $values);
			$info = 'Date: ' . $date . ' Same day of the week: ' . $same_day . ' Description: ' . $description;
		}
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


echo "<table cellpadding='4' cellspacing='4' class='databox upload' width='100%
' style='font-weight: bold; margin-bottom: 10px;'><tr>";
echo "<form method='post' enctype='multipart/form-data' action='index.php?sec=gagente&sec2=godmode/alerts/alert_special_days'>";
echo "<td>";
echo __('iCalendar(.ics) file') . '&nbsp;';
html_print_input_file ('ical_file', false, false);
echo "</td><td>";
echo __('Same day of the week');
$days = array ();
$days["monday"] = __('Monday');
$days["tuesday"] = __('Tuesday');
$days["wednesday"] = __('Wednesday');
$days["thursday"] = __('Thursday');
$days["friday"] = __('Friday');
$days["saturday"] = __('Saturday');
$days["sunday"] = __('Sunday');
html_print_select ($days, "same_day", $same_day, '', '', 0, false, false, false);
echo "</td><td>";
echo __('Group') . '&nbsp;';
$own_info = get_user_info($config['id_user']);
if (!users_can_manage_group_all("LM"))
        $can_manage_group_all = false;
else
        $can_manage_group_all = true;
html_print_select_groups(false, "LM", $can_manage_group_all, "id_group", $id_group, false, '', 0, false, false, true, '', false, 'width:100px;');
echo "</td><td>";
echo __('Overwrite');
ui_print_help_tip(__('Check this box, if you want to overwrite existing same days.'), false);
echo "&nbsp;";
html_print_checkbox ("overwrite", 1, $overwrite, false, false, false, true);
echo "</td><td>";
html_print_input_hidden('upload_ical', 1);
echo "<input name='srcbutton' type='submit' class='sub next' value='".__('Upload')."'>";
echo "</td></form>";
echo "</tr></table>";


$this_year = date('Y');
$this_month = date('m');

$filter = array();
if (!is_user_admin($config['id_user']))
	$filter['id_group'] = array_keys(users_get_groups(false, "LM"));

// Show display range.
$html = "<table cellpadding='4' cellspacing='4' width='100%' margin-bottom: 10px;'><tr><td>" . __('Display range: ');
if ($display_range) {
	$html .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days">[' . __('Default') . ']</a>&nbsp;&nbsp;';
	if ($display_range > 1970) {
		$html .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days&display_range=';
		$html .= $display_range - 1;
		$html .= '">&lt;&lt;&nbsp;</a>';
	}
	$html .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days&display_range=' . $display_range . '" style="font-weight: bold;">[' . $display_range . ']</a>';
	$html .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days&display_range=';
	$html .= $display_range + 1;
	$html .= '">&nbsp;&gt;&gt;</a>';
}
else {
	$html .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days" style="font-weight: bold;">[' . __('Default') . ']</a>&nbsp;&nbsp;';
	$html .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days&display_range=';
	$html .= $this_year - 1;
	$html .= '">&lt;&lt;&nbsp;</a>';
	$html .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days&display_range=';
	$html .= $this_year;
	$html .= '">[';
	$html .= $this_year;
	$html .= ']</a>';
	$html .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days&display_range=';
	$html .= $this_year + 1;
	$html .= '">&nbsp;&gt;&gt;</a>';
}
$html .= "</td></tr>";
echo $html;

// Show calendar
for ($month = 1; $month <= 12; $month++) {

	if ($display_range) {
		$display_month = $month;
		$display_year = $display_range;
	}
	else {
		$display_month = $this_month + $month - 1;
		$display_year = $this_year;
	}

	if ($display_month > 12) {
		$display_month -= 12;
		$display_year += 1;
	}

	$cal_table = new stdClass();
	$cal_table->width = '100%';
	$cal_table->class = 'databox data';

	$cal_table->data = array ();
	$cal_table->head = array ();
	$cal_table->head[0] = __('Sun');
	$cal_table->head[1] = __('Mon');
	$cal_table->head[2] = __('Tue');
	$cal_table->head[3] = __('Wed');
	$cal_table->head[4] = __('Thu');
	$cal_table->head[5] = __('Fri');
	$cal_table->head[6] = __('Sat');
	$cal_table->cellstyle = array ();
	$cal_table->size = array ();
	$cal_table->size[0] = '14%';
	$cal_table->size[1] = '14%';
	$cal_table->size[2] = '14%';
	$cal_table->size[3] = '14%';
	$cal_table->size[4] = '14%';
	$cal_table->size[5] = '14%';
	$cal_table->size[6] = '14%';
	$cal_table->align = array ();
	$cal_table->border = '1';
	$cal_table->titlestyle = 'text-align:center; font-weight: bold;';
	switch ($display_month) {
	case 1:
		$cal_table->title = __('January'); 
		break;
	case 2:
		$cal_table->title = __('February');
		break;
	case 3:
		$cal_table->title = __('March');
		break;
	case 4:
		$cal_table->title = __('April');
		break;
	case 5:
		$cal_table->title = __('May');
		break;
	case 6:
		$cal_table->title = __('June');
		break;
	case 7:
		$cal_table->title = __('July');
		break;
	case 8:
		$cal_table->title = __('August');
		break;
	case 9:
		$cal_table->title = __('September');
		break;
	case 10:
		$cal_table->title = __('October');
		break;
	case 11:
		$cal_table->title = __('November');
		break;
	case 12:
		$cal_table->title = __('December');
		break;
	}
	$cal_table->title .= ' / ' . $display_year;

	$last_day = date('j', mktime(0, 0, 0, $display_month + 1, 0, $display_year));
	$cal_line = 0;

	for ($day = 1; $day < $last_day + 1; $day++) {
		$week = date('w', mktime(0, 0, 0, $display_month, $day, $display_year));
		if ($cal_line == 0 && $week != 0 && $day == 1) {
			for ($i = 0; $i < $week; $i++) {
				$cal_table->cellstyle[$cal_line][$i] = 'font-size: 18px;';
				$cal_table->data[$cal_line][$i] = '-';
			}
		}
		if ($week == 0 || $week == 6) {
 			$cal_table->cellstyle[$cal_line][$week] = 'color: red;';
	 	}

		$date = sprintf("%04d-%02d-%02d", $display_year, $display_month, $day);
		$date_wildcard = sprintf("0001-%02d-%02d", $display_month, $day);
		$special_days = '';
		$filter['date'] = array($date, $date_wildcard);
		$filter['order']['field'] = 'date';
		$filter['order']['order'] = 'DESC';
		$special_days = db_get_all_rows_filter ('talert_special_days', $filter);

		if ($special_days != '') {
			foreach ($special_days as $special_day) {
				$cal_table->data[$cal_line][$week] .= '<div style="font-size: 18px;';
				if ($special_day["same_day"] == 'sunday' || $special_day["same_day"] == 'saturday') {
					$cal_table->data[$cal_line][$week] .= 'color: red;';
				}
				$cal_table->data[$cal_line][$week] .= '">';
				$cal_table->data[$cal_line][$week] .= $day;
				$cal_table->data[$cal_line][$week] .= '</div>';
				$cal_table->data[$cal_line][$week] .= ui_print_group_icon ($special_day["id_group"], true);

				if ($special_day["date"] == $date_wildcard) {
					$cal_table->data[$cal_line][$week] .= '(' . ui_print_help_tip('This is valid every year. However, this will be ignored if indivisual setting for the same group is available.', true) . ') ';
				}
				$cal_table->data[$cal_line][$week] .= __('Same as ');
				switch ($special_day['same_day']) {
				case 'monday':
					$cal_table->data[$cal_line][$week] .= __('Monday');
					break;
				case 'tuesday':
					$cal_table->data[$cal_line][$week] .= __('Tuesday');
					break;
				case 'wednesday':
					$cal_table->data[$cal_line][$week] .= __('Wednesday');
					break;
				case 'thursday':
					$cal_table->data[$cal_line][$week] .= __('Thursday');
					break;
				case 'friday':
					$cal_table->data[$cal_line][$week] .= __('Friday');
					break;
				case 'saturday':
					$cal_table->data[$cal_line][$week] .= __('Saturday');
					break;
				case 'sunday':
					$cal_table->data[$cal_line][$week] .= __('Sunday');
					break;
				}
				$cal_table->data[$cal_line][$week] .= ui_print_help_tip($special_day['description'], true);
				if ($special_day["id_group"] || ($can_manage_group_all && $special_day["id_group"] == 0)) {
					$cal_table->data[$cal_line][$week] .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_special_days&id='.$special_day['id'] . '" title=';
					$cal_table->data[$cal_line][$week] .= __('Edit');
					$cal_table->data[$cal_line][$week] .= '>' . html_print_image("images/wrench_orange.png", true) . '</a> &nbsp;';
					$cal_table->data[$cal_line][$week] .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_special_days&delete_special_day=1&id='.$special_day['id'] . '"onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;" title=';
					$cal_table->data[$cal_line][$week] .= __('Remove');
					$cal_table->data[$cal_line][$week] .= '>'. html_print_image("images/cross.png", true) . '</a>';;
				}
				$cal_table->data[$cal_line][$week] .= '<br>';
				$cal_table->cellstyle[$cal_line][$week] = 'font-weight: bold;';
			}
		}
		else {
			$cal_table->cellstyle[$cal_line][$week] .= 'font-size: 18px;';
			$cal_table->data[$cal_line][$week] = $day . '&nbsp;';
		}
		$cal_table->data[$cal_line][$week] .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_special_days&create_special_day=1&date=' . $date . '" title=';
		$cal_table->data[$cal_line][$week] .= __('Create');
		$cal_table->data[$cal_line][$week] .= '>' . html_print_image("images/plus.png", true) . '</a>';

		if ($week == 6) {
			$cal_line++;
		}
	}
	for ($padding = $week + 1; $padding <= 6; $padding++) {
		$cal_table->cellstyle[$cal_line][$padding] = 'font-size: 18px;';
		$cal_table->data[$cal_line][$padding] = '-';
	}

	html_print_table ($cal_table);

}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_special_days">';
html_print_submit_button (__('Create'), 'create', false, 'class="sub next"');
html_print_input_hidden ('create_special_day', 1);
echo '</form>';
echo '</div>';
?>
