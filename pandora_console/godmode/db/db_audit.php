<?php 
// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnol�gicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2008-2008 Evi Vanoost <vanooste@rcbi.rochester.edu> 
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
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

if ((give_acl ($id_user, 0, "DM")==1) or (dame_admin ($id_user)==1)) {
	// Todo for a good DB maintenance 
 	/* 
 		- Delete too on datos_string and and datos_inc tables 
 		
 		- A function to "compress" data, and interpolate big chunks of data (1 month - 60000 registers) 
 		  onto a small chunk of interpolated data (1 month - 600 registers)
 		
 		- A more powerful selection (by Agent, by Module, etc).
 	*/
	require("godmode/db/times_incl.php");
	
	$datos_rango3=0;
	$datos_rango2=0;
	$datos_rango1=0;

	
	# ADQUIRE DATA PASSED AS FORM PARAMETERS
	# ======================================
	# Purge data using dates
	# Purge data using dates
	if (isset($_POST["purgedb"])){	# Fixed 2005-1-13, nil
		$from_date = get_parameter_post("date_purge");
		$query = sprintf("DELETE FROM `tsesion` WHERE `fecha` < '%s';",$from_date);
		(int) $deleted = process_sql($query);
	}
	# End of get parameters block
	
	echo "<h2>".$lang_label["dbmain_title"]." &gt; ";
	echo  $lang_label["db_purge_audit"]."</h2>";

	echo "<table cellpadding='4' cellspacing='4' class='databox'>";
	echo "<tr><td class='datos'>";
	$result = get_db_row_sql ("SELECT COUNT(*) AS total, MIN(fecha) AS first_date, MAX(fecha) AS latest_date FROM tsesion");
	
	echo "<b>".$lang_label["total"]."</b></td>";
	echo "<td class='datos'>".$result["total"]." ".$lang_label["records"]."</td>";
	
	echo "<tr>";
	echo "<td class='datos2'><b>".$lang_label["first_date"]."</b></td>";
	echo "<td class='datos2'>".$result["first_date"]."</td></tr>";

	echo "<tr><td class='datos'>";	
	echo "<b>".$lang_label["latest_date"]."</b></td>";
	echo "<td class='datos'>".$result["latest_date"]."</td>";
	echo "</tr></table>";
?>
	<h3><?php echo $lang_label["purge_data"] ?></h3>
	<form name="db_audit" method="post" action="index.php?sec=gdbman&sec2=godmode/db/db_audit">
	<table width='300' cellpadding='4' cellspacing='4' class='databox'>
	<tr><td class='datos'>
	<select name="date_purge" width="255px">
	<option value="<?php echo $month3 ?>"><?php echo $lang_label["purge_audit_90day"] ?>
	<option value="<?php echo $month ?>"><?php echo $lang_label["purge_audit_30day"] ?>
	<option value="<?php echo $week2 ?>"><?php echo $lang_label["purge_audit_14day"] ?>
	<option value="<?php echo $week ?>"><?php echo $lang_label["purge_audit_7day"] ?>
	<option value="<?php echo $d3 ?>"><?php echo $lang_label["purge_audit_3day"] ?>
	<option value="<?php echo $d1 ?>"><?php echo $lang_label["purge_audit_1day"] ?>
	<option value="<?php echo $all_data ?>"><?php echo $lang_label["purge_audit_all"] ?>
	</select>
	
	<td class="datos">
	<input class="sub wand" type="submit" name="purgedb" value="<?php echo $lang_label["doit"] ?>" onClick="if (!confirm('<?php echo $lang_label["are_you_sure"] ?>')) return false;">
	
	</table>
	</form>

	
<?php
} else {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Database Management Audit");
	require ("general/noaccess.php");
}
?>
