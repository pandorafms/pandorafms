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

// Login check
global $config;

check_login();

$id_report = (int) get_parameter ('id');

if (! $id_report) {
	db_pandora_audit("HACK Attempt",
		"Trying to access graph viewer withoud ID");
	include ("general/noaccess.php");
	return;
}

// Get Report record (to get id_group)
$report = db_get_row ('treport', 'id_report', $id_report);

// Check ACL on the report to see if user has access to the report.
if (! check_acl ($config['id_user'], $report['id_group'], "AR")) {
	db_pandora_audit("ACL Violation","Trying to access graph reader");
	include ("general/noaccess.php");
	exit;
}

// Include with the functions to calculate each kind of report.
require_once ('include/functions_reporting.php');
require_once ('include/functions_groups.php');

enterprise_include("include/functions_reporting.php");

// Check if the report is a private report.
if ($report['private'] && ($report['id_user'] != $config['id_user'] && ! is_user_admin ($config['id_user']))) {
	include ("general/noaccess.php");
	return;
}

// Get different date to search the report.
$date = (string) get_parameter ('date', date ('Y-m-j'));
$time = (string) get_parameter ('time', date ('h:iA'));

$datetime = strtotime ($date.' '.$time);
$report["datetime"] = $datetime;

// Calculations in order to modify init date of the report
$date_init_less = strtotime(date ('Y-m-j')) - 86400;
$date_init = get_parameter('date_init', date ('Y-m-j', $date_init_less));
$time_init = get_parameter('time_init', date ('h:iA', $date_init_less));
$datetime_init = strtotime ($date_init.' '.$time_init);
$enable_init_date = get_parameter('enable_init_date', 0);

// Standard header

$url = "index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id=$id_report&date=$date&time=$time";

$options['setup'] = "<a href='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&action=edit&id_report=$id_report'>"
. html_print_image ("images/setup.png", true, array ("title" => __('Setup')))
. "</a>";

if ($config["pure"] == 0) {
	$options['screen'] = "<a href='$url&pure=1'>"
		. html_print_image ("images/fullscreen.png", true, array ("title" => __('Full screen mode')))
		. "</a>";
}
else {
	$options['screen'] = "<a href='$url&pure=0'>"
		. html_print_image ("images/normalscreen.png", true, array ("title" => __('Back to normal mode')))
		. "</a>";
}

ui_print_page_header (__('Reporting'). " &raquo;  ". __('Custom reporting'). " - ".$report["name"],
	"images/reporting.png", false, "", false, $options);

$table->width = '99%';
$table->class = 'databox';
$table->style = array ();
$table->style[0] = 'width: 60px;';

// Set initial conditions for these controls, later will be modified by javascript
if (!$enable_init_date) {
	$table->style[0] .= 'font-weight: bold; display: none';
	$table->style[1] = 'display: none';
	$table->style[2] = 'display: ""';
	$table->style[3] = 'display: none';
	//~ $table->style[5] = 'width: 380.583px';
}
else {
	$table->style[0] .= 'font-weight: bold; display: ""';
	$table->style[1] = 'display: ""';
	$table->style[2] = 'display: none';
	$table->style[3] = 'display: ""';
	$table->style[5] = 'display: none';
}

$table->size = array ();
$table->size[0] = '60px';
$table->size[2] = '60px';
$table->colspan[0][1] = 2;
$table->data = array ();
$table->data[0][0] = html_print_image("images/reporting.png", true, array("width" => "32", "height" => "32")); 
if ($report['description'] != '') {
	$table->data[0][1] = $report['description'];
}
else {
	$table->data[0][1] = $report['name'];
}

$table->data[0][3] = '<span style="text-align:right;width:100%">'.__('Set initial date of all reports') . html_print_checkbox('enable_init_date', 1, $enable_init_date, true).'</span>';
$html_enterprise = enterprise_hook('reporting_print_button_PDF', array($id_report));
if ($html_enterprise !== ENTERPRISE_NOT_HOOK) {
	$table->data[0][3] .= $html_enterprise;
}

$table->data[1][0] = '<b>' . __('From') . ':</b>';
$table->data[1][1] = html_print_input_text ('date_init', $date_init, '', 12, 10, true). ' ';
$table->data[1][1] .= html_print_input_text ('time_init', $time_init, '', 7, 7, true). ' ';
//~ $table->data[1][1] .= html_print_submit_button (__('Update'), 'date_submit_init', false, 'class="sub next"', true);	
$table->data[1][2] = '<b>' . __('Day before') . ':</b>';
$table->data[1][3] = '<b>' . __('to') . ':</b>';
$table->data[1][4] = html_print_input_text ('date', $date, '', 12, 10, true). ' ';
$table->data[1][4] .= html_print_input_text ('time', $time, '', 7, 7, true). ' ';
$table->data[1][4] .= html_print_submit_button (__('Update'), 'date_submit', false, 'class="sub next"', true);
$table->data[1][5] = '';

echo '<form method="post" action="">';
html_print_table ($table);
html_print_input_hidden ('id_report', $id_report);
echo '</form>';

echo '<div id="loading">';
echo html_print_image("images/wait.gif", true, array("border" => '0'));
echo '<strong>'.__('Loading').'...</strong>';
echo '</div>';

/* We must add javascript here. Otherwise, the date picker won't 
   work if the date is not correct because php is returning. */

ui_require_css_file ('datepicker');
ui_require_jquery_file ('ui.core');
ui_require_jquery_file ('ui.datepicker');
ui_require_jquery_file ('timeentry');
?>
<script language="javascript" type="text/javascript">

$(document).ready (function () {
	$("#loading").slideUp ();
	$("#text-time").timeEntry ({spinnerImage: 'images/time-entry.png', spinnerSize: [20, 20, 0]});
	$("#text-date").datepicker ();
	$.datepicker.regional["<?php echo $config['language']; ?>"];
});
</script>

<?php

if ($datetime === false || $datetime == -1) {
	echo '<h3 class="error">'.__('Invalid date selected').'</h3>';
	return;
}

// TODO: Evaluate if it's better to render blocks when are calculated (enabling realtime flush) or if it's better to wait report to be finished before showing anything (this could break the execution by overflowing the running PHP memory on HUGE reports).


$table->size = array ();
$table->style = array ();
$table->width = '99%';
$table->class = 'databox report_table';
$table->rowclass = array ();
$table->rowclass[0] = 'datos3';

$report["group_name"] = groups_get_name ($report['id_group']);

switch ($config["dbtype"]) {
	case "mysql":
		$contents = db_get_all_rows_field_filter ("treport_content", "id_report", $id_report, "`order`");
		break;
	case "postgresql":
		$contents = db_get_all_rows_field_filter ("treport_content", "id_report", $id_report, '"order"');
		break;
	case "oracle":
		$contents = db_get_all_rows_field_filter ("treport_content", "id_report", $id_report, '"order"');
		break;
}
if ($contents === false) {
	return;
}

foreach ($contents as $content) {
	$table->data = array ();
	$table->head = array ();
	$table->style = array ();
	$table->colspan = array ();
	$table->rowstyle = array ();
	
	// Calculate new inteval for all reports
	if ($enable_init_date){
		$datetime_init = strtotime ($date_init.' '.$time_init);
		$new_interval = $report['datetime'] - $datetime_init; 		
		$content['period'] = $new_interval;
	}		
	
	reporting_render_report_html_item ($content, $table, $report);

	html_print_table ($table);
	flush ();
}
?>

<script type="text/javascript">

$(document).ready (function () {
	$("*", "#table1-0").css("display", ""); //Re-show the first row of form.
	
	$("#loading").slideUp ();
	$("#text-time").timeEntry ({spinnerImage: 'images/time-entry.png', spinnerSize: [20, 20, 0]});
	$("#text-date").datepicker ();

	$('[id^=text-date_init]').datepicker ();
	$('[id^=text-time_init]').timeEntry ({spinnerImage: 'images/time-entry.png', spinnerSize: [20, 20, 0]});

	$.datepicker.regional["<?php echo $config['language']; ?>"];
	
	/* Show/hide begin date reports controls */
	$("#checkbox-enable_init_date").click(function() {
		flag = $("#checkbox-enable_init_date").is(':checked');
		if (flag == true) {
			$("#table1-1-0").css("display", "");
			$("#table1-1-1").css("display", "");
			$("#table1-1-2").css("display", "none");
			$("#table1-1-3").css("display", "");
			$("#table1-1-6").css("display", "none");
		}
		else {
			$("#table1-1-0").css("display", "none");
			$("#table1-1-1").css("display", "none");
			$("#table1-1-2").css("display", "");
			$("#table1-1-3").css("display", "none");
			$("#table1-1-6").css("display", "");
			$("#table1-1-6").css("width", "380.583px");
		}
	});
});

</script>
