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
	return;
}


require ("godmode/db/times_incl.php");

$datos_rango3 = 0;
$datos_rango2 = 0;
$datos_rango1 = 0;

# ADQUIRE DATA PASSED AS FORM PARAMETERS
# ======================================
# Purge data using dates
# Purge data using dates
if (isset ($_POST["date_purge"])){
	$from_date = get_parameter_post ("date_purge");
	$query = sprintf ("DELETE FROM `tevento` WHERE `timestamp` < '%s'",$from_date);
	(int) $deleted = process_sql ($query);			
}
# End of get parameters block

echo "<h2>".__('Database Maintenance')." &gt; ";
echo  __('Event Database cleanup')."</h2>";

echo "<table cellpadding='4' cellspacing='4' class='databox'>";
echo "<tr><td class='datos'>";
$row = get_db_row_sql ("SELECT COUNT(*) AS total, MIN(timestamp) AS first_date, MAX(timestamp) AS latest_date FROM tevento");

echo "<b>".__('Total')."</b>";
echo "<td class='datos'>".$row["total"]." ".__('Records')."</td>";

echo "<tr>";	
echo "<td class='datos2'><b>".__('First date')."</b></td>";
echo "<td class='datos2'>".$row["first_date"]."</td></tr>";


echo "<tr><td class='datos'>";
echo "<b>".__('Latest date')."</b>";
echo "<td class='datos'>".$row["latest_date"]."</td>";
echo "</table>";
?>

<h3><?php echo __('Purge data') ?></h3>
<form name="db_audit" method="post" action="index.php?sec=gdbman&sec2=godmode/db/db_event">
<table width='300' cellpadding='4' cellspacing='4' class='databox'>
<tr><td class='datos'>
<select name="date_purge" width="255px">
<option value="<?php echo $month3 ?>"><?php echo __('Purge event data over 90 days') ?>
<option value="<?php echo $month ?>"><?php echo __('Purge event data over 30 days') ?>
<option value="<?php echo $week2 ?>"><?php echo __('Purge event data over 14 days') ?>
<option value="<?php echo $week ?>"><?php echo __('Purge event data over 7 days') ?>
<option value="<?php echo $d3 ?>"><?php echo __('Purge event data over 3 days') ?>
<option value="<?php echo $d1 ?>"><?php echo __('Purge event data over 1 day') ?>
<option value="<?php echo $all_data ?>"><?php echo __('Purge all event data') ?>
</select>

<td class="datos">
<input class="sub wand" type="submit" name="purgedb" value="<?php echo __('Do it!') ?>" onClick="if (!confirm('<?php  echo __('Are you sure?') ?>')) return false;">
</table>
</form>
