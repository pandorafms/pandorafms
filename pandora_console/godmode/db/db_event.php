<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require_once ("include/config.php");

check_login (); 

if (! give_acl ($config['id_user'], 0, "DM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Database Management Event");
	require ("general/noaccess.php");
	exit;
}

# ADQUIRE DATA PASSED AS FORM PARAMETERS
# ======================================
# Purge data using dates
if (isset ($_POST["date_purge"])){
	$from_date = (int) get_parameter_post ("date_purge");
	$query = sprintf ("DELETE FROM `tevento` WHERE `utimestamp` < %d",$from_date);
	$deleted = process_sql ($query);			
	if ($deleted !== false) {
		echo '<h3 class="suc">'.__('Successfully deleted old events').'</h3>';
	} else {
		echo '<h3 class="error">'.__('Error deleting old events').'</h3>';
	}
}
# End of get parameters block

echo "<h2>".__('Database Maintenance')." &gt; ".__('Event Database cleanup')."</h2>";

$row = get_db_row_sql ("SELECT COUNT(*) AS total, MIN(timestamp) AS first_date, MAX(timestamp) AS latest_date FROM tevento");

$table->data = array ();
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";
$table->width = 300;

$table->data[0][0] = '<b>'.__('Total').':</b>';
$table->data[0][1] = $row["total"].' '.__('Records');

$table->data[1][0] = '<b>'.__('First date').':</b>';
$table->data[1][1] = $row["first_date"];

$table->data[2][0] = '<b>'.__('Latest data').':</b>';
$table->data[2][1] = $row["latest_date"];

print_table ($table);
unset ($table);

echo '<h3>'.__('Purge data').'</h3>';

echo '<form name="db_audit" method="post" action="index.php?sec=gdbman&sec2=godmode/db/db_event">';
echo '<table width="300" cellpadding="4" cellspacing="4" class="databox">
	<tr><td class="datos">';

$time = time ();
$fields = array ();
$fields[$time - 7776000] = __('Purge event data over 90 days');
$fields[$time - 2592000] = __('Purge event data over 30 days');
$fields[$time - 1209600] = __('Purge event data over 14 days');
$fields[$time - 604800] = __('Purge event data over 7 days');
$fields[$time - 259200] = __('Purge event data over 3 days');
$fields[$time - 86400] = __('Purge event data over 1 day');
$fields[$time] = __('Purge all event data');

print_select ($fields, "date_purge", '', '', '', '0', false, false, false, "w255");

echo '</td><td class="datos">';
print_submit_button (__('Do it!'),'purgedb', false, 'class="sub wand" onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;"');
echo '</td></tr></table></form>';
?>
