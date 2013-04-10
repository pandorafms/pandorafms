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

check_login (); 

ui_print_page_header (__('Database maintenance').' &raquo; '.__('Event database cleanup'), "images/gm_db.png", false, "", true);

if (! check_acl ($config['id_user'], 0, "DM")) {
	db_pandora_audit("ACL Violation", "Trying to access Database Management Event");
	require ("general/noaccess.php");
	exit;
}

# ADQUIRE DATA PASSED AS FORM PARAMETERS
# ======================================
# Purge data using dates
if (isset ($_POST["date_purge"])){
	$from_date = (int) get_parameter_post ("date_purge");
	
	$deleted = db_process_sql_delete('tevento', array('utimestamp' => '< ' . $from_date));
	
	if ($deleted !== false) {
		echo '<h3 class="suc">'.__('Successfully deleted old events').'</h3>';
	}
	else {
		echo '<h3 class="error">'.__('Error deleting old events').'</h3>';
	}
}
# End of get parameters block

$row = db_get_row_sql ("SELECT COUNT(*) AS total, MIN(timestamp) AS first_date, MAX(timestamp) AS latest_date FROM tevento");

$table->data = array ();
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";
$table->width = '98%';

$table->data[0][0] = '<b>'.__('Total').':</b>';
$table->data[0][1] = $row["total"].' '.__('Records');

$table->data[1][0] = '<b>'.__('First date').':</b>';
$table->data[1][1] = $row["first_date"];

$table->data[2][0] = '<b>'.__('Latest data').':</b>';
$table->data[2][1] = $row["latest_date"];

html_print_table ($table);
unset ($table);

echo '<h4>'.__('Purge data').'</h4>';

echo '<form name="db_audit" method="post" action="index.php?sec=gdbman&sec2=godmode/db/db_event">';
echo '<table width="98%" cellpadding="4" cellspacing="4" class="databox">
	<tr><td class="datos">';

$time = get_system_time ();
$fields = array ();
$fields[$time - SECONDS_3MONTHS] = __('Purge event data over 90 days');
$fields[$time - SECONDS_1MONTH] = __('Purge event data over 30 days');
$fields[$time - SECONDS_2WEEK] = __('Purge event data over 14 days');
$fields[$time - SECONDS_1WEEK] = __('Purge event data over 7 days');
$fields[$time - (SECONDS_1WEEK * 3)] = __('Purge event data over 3 days');
$fields[$time - SECONDS_1DAY] = __('Purge event data over 1 day');
$fields[$time] = __('Purge all event data');

html_print_select ($fields, "date_purge", '', '', '', '0', false, false, false, "w255");

echo '</td><td class="datos">';
html_print_submit_button (__('Do it!'),'purgedb', false, 'class="sub wand" onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;"');
echo '</td></tr></table></form>';
?>
