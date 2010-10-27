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

$id_report = (int) get_parameter ('id_report');

// Get Report record (to get id_group)
$report = get_db_row ('treport', 'id_report', $id_report);

// Check ACL on the report to see if user has access to the report.
if (! give_acl ($config['id_user'], $report['id_group'], "AR")) {
	pandora_audit("ACL Violation","Trying to access graph reader");
	include ("general/noaccess.php");
	exit;
}

// Include with the functions to calculate each kind of report.
require ("include/functions_reporting.php");

// Check if the report is a private report.
if ($report['private'] && ($report['id_user'] != $config['id_user'] && ! is_user_admin ($config['id_user']))) {
	include ("general/noaccess.php");
	return;
}

// Get different date to search the report.
$date = (string) get_parameter ('date', date ('Y-m-j'));
$time = (string) get_parameter ('time', date ('h:iA'));

// Standard header

$url = "index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id=$id_report&date=$date&time=$time";

if ($config["pure"] == 0) {
	$options[] = "<a href='$url&pure=1'>"
		. print_image ("images/fullscreen.png", true, array ("title" => __('Full screen mode')))
		. "</a>";
} else {
	$options[] = "<a href='$url&pure=0'>"
		. print_image ("images/normalscreen.png", true, array ("title" => __('Back to normal mode')))
		. "</a>";
}

$table->width = '99%';
$table->class = 'databox';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '50px';
$table->data = array ();
$table->data[0][0] = '<img src="images/reporting.png" width="32" height="32" />';
if ($report['description'] != '') {
	$table->data[0][1] = $report['description'];
} else {
	$table->data[0][1] = $report['name'];
}
$table->data[1][0] = __('Date');
$table->data[1][1] = print_input_text ('date', $date, '', 10, 10, true). ' ';
$table->data[1][1] .= print_input_text ('time', $time, '', 7, 7, true). ' ';
$table->data[1][1] .= print_submit_button (__('Update'), 'date_submit', false, 'class="sub next"', true);

echo '<form method="post" action="">';
print_table ($table);
print_input_hidden ('id_report', $id_report);
echo '</form>';

echo '<div id="loading">';
echo '<img src="images/wait.gif" border="0" /><br />';
echo '<strong>'.__('Loading').'...</strong>';
echo '</div>';

/* We must add javascript here. Otherwise, the date picker won't 
   work if the date is not correct because php is returning. */

require_css_file ('datepicker');
require_jquery_file ('ui.core');
require_jquery_file ('ui.datepicker');
require_jquery_file ('timeentry');
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
$datetime = strtotime ($date.' '.$time);
$report["datetime"] = $datetime;

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

$report["group_name"] = get_group_name ($report['id_group']);

$contents = get_db_all_rows_field_filter ("treport_content", "id_report", $id_report, "`order`");
if ($contents === false) {
	return;
}

foreach ($contents as $content) {
	$table->data = array ();
	$table->head = array ();
	$table->style = array ();
	$table->colspan = array ();
	$table->rowstyle = array ();
	
    render_report_html_item ($content, $table, $report);

	print_table ($table);
	flush ();
}
?>