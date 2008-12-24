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
	audit_db ($config['id_user'],$REMOTE_ADDR, "ACL Violation",
		"Trying to access Database Management Audit");
	require ("general/noaccess.php");
	return;
}

// All data (now)
$time["all"] = get_system_time ();

// 1 day ago
$time["1day"] = $time["all"]-86400;

// 3 days ago
$time["3day"] = $time["all"]-(86400*3);

// 1 week ago
$time["1week"] = $time["all"]-(86400*7);

// 2 weeks ago
$time["2week"] = $time["all"]-(86400*14);

// 1 month ago
$time["1month"] = $time["all"]-(86400*30);

// Three months ago
$time["3month"] = $time["all"]-(86400*90);

// Todo for a good DB maintenance 
/* 
	- Delete too on datos_string and and datos_inc tables 
	
	- A function to "compress" data, and interpolate big chunks of data (1 month - 60000 registers) 
	  onto a small chunk of interpolated data (1 month - 600 registers)
	
	- A more powerful selection (by Agent, by Module, etc).
*/

# ADQUIRE DATA PASSED AS FORM PARAMETERS
# ======================================
# Purge data using dates
if (isset($_POST["purgedb"])){	# Fixed 2005-1-13, nil
	$from_date = get_parameter_post("date_purge");
	$query = sprintf("DELETE FROM `tsesion` WHERE `utimestamp` < '%s';",$from_date);
	(int) $deleted = process_sql($query);
}
# End of get parameters block

echo "<h2>".__('Database Maintenance')." &gt; ";
echo  __('Database Audit purge')."</h2>";

echo "<table cellpadding='4' cellspacing='4' class='databox'>";
echo "<tr><td class='datos'>";
$result = get_db_row_sql ("SELECT COUNT(*) AS total, MIN(fecha) AS first_date, MAX(fecha) AS latest_date FROM tsesion");

echo "<b>".__('Total')."</b></td>";
echo "<td class='datos'>".$result["total"]." ".__('Records')."</td>";

echo "<tr>";
echo "<td class='datos2'><b>".__('First date')."</b></td>";
echo "<td class='datos2'>".$result["first_date"]."</td></tr>";

echo "<tr><td class='datos'>";	
echo "<b>".__('Latest date')."</b></td>";
echo "<td class='datos'>".$result["latest_date"]."</td>";
echo "</tr></table>";
?>
<h3><?php echo __('Purge data') ?></h3>
<form name="db_audit" method="post" action="index.php?sec=gdbman&sec2=godmode/db/db_audit">
<table width='300' cellpadding='4' cellspacing='4' class='databox'>
<tr><td class='datos'>
<select name="date_purge" width="255px">
<option value="<?php echo $time["3month"] ?>"><?php echo __('Purge audit data over 90 days') ?>
<option value="<?php echo $time["1month"] ?>"><?php echo __('Purge audit data over 30 days') ?>
<option value="<?php echo $time["2week"] ?>"><?php echo __('Purge audit data over 14 days') ?>
<option value="<?php echo $time["1week"] ?>"><?php echo __('Purge audit data over 7 days') ?>
<option value="<?php echo $time["3day"] ?>"><?php echo __('Purge audit data over 3 days') ?>
<option value="<?php echo $time["1day"] ?>"><?php echo __('Purge audit data over 1 day') ?>
<option value="<?php echo $time["all"] ?>"><?php echo __('Purge all audit data') ?>
</select>

<td class="datos">
<input class="sub wand" type="submit" name="purgedb" value="<?php echo __('Do it!') ?>" onClick="if (!confirm('<?php echo __('Are you sure?') ?>')) return false;">

</table>
</form>
