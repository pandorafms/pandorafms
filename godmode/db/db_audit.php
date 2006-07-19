<?php 

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnológicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
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
require("include/config.php");

if (comprueba_login() == 0) 
	if ((give_acl($id_user, 0, "DM")==1) or (dame_admin($id_user)==1)) {
	// Todo for a good DB maintenance 
 	/* 
 		- Delete too on datos_string and and datos_inc tables 
 		
 		- A function to "compress" data, and interpolate big chunks of data (1 month - 60000 registers) 
 		  onto a small chunk of interpolated data (1 month - 600 registers)
 		
 		- A more powerful selection (by Agent, by Module, etc).
 	*/
		
	// 1 day
	$d1_year = date("Y", time()-28800);
	$d1_month = date("m", time()-28800);
	$d1_day = date ("d", time()-28800);
	$d1_hour = date ("H", time()-28800);
	$d1 = $d1_year."-".$d1_month."-".$d1_day." ".$d1_hour.":00:00";
	
	// today + 1 hour (to purge all possible data)
	$all_year = date("Y", time()+3600);
	$all_month = date("m", time()+3600);
	$all_day = date ("d", time()+3600);
	$all_hour = date ("H", time()+3600);
	$all_data = $all_year."-".$all_month."-".$all_day." ".$all_hour.":00:00";
	
	// 3 days ago
	$d3_year = date("Y", time()-86400);
	$d3_month = date("m", time()-86400);
	$d3_day = date ("d", time()-86400);
	$d3_hour = date ("H", time()-86400);
	$d3 = $d3_year."-".$d3_month."-".$d3_day." ".$d3_hour.":00:00";
	// Date 24x7 Hours ago (a week)
	$week_year = date("Y", time()-604800);
	$week_month = date("m", time()-604800);
	$week_day = date ("d", time()-604800);
	$week_hour = date ("H", time()-604800);
	$week = $week_year."-".$week_month."-".$week_day." ".$week_hour.":00:00";
	
	// Date 24x7x2 Hours ago (two weeks)
	$week2_year = date("Y", time()-1209600);
	$week2_month = date("m", time()-1209600);
	$week2_day = date ("d", time()-1209600);
	$week2_hour = date ("H", time()-1209600);
	$week2 = $week2_year."-".$week2_month."-".$week2_day." ".$week2_hour.":00:00";
		
	// Date 24x7x30 Hours ago (one month)
	$month_year = date("Y", time()-2592000);
	$month_month = date("m", time()-2592000);
	$month_day = date ("d", time()-2592000);
	$month_hour = date ("H", time()-2592000);
	$month = $month_year."-".$month_month."-".$month_day." ".$month_hour.":00:00";
	
	// Three months ago
	$month3_year = date("Y", time()-7257600);
	$month3_month = date("m", time()-7257600);
	$month3_day = date ("d", time()-7257600);
	$month3_hour = date ("H", time()-7257600);
	$month3 = $month3_year."-".$month3_month."-".$month3_day." ".$month3_hour.":00:00";
	$datos_rango3=0;$datos_rango2=0;$datos_rango1=0;

	
	# ADQUIRE DATA PASSED AS FORM PARAMETERS
	# ======================================
	# Purge data using dates
	# Purge data using dates
	if (isset($_POST["purgedb"])){	# Fixed 2005-1-13, nil
		$from_date =$_POST["date_purge"];
		$query = "DELETE FROM tsesion WHERE fecha < '".$from_date."'";
		mysql_query($query);			

	}
	# End of get parameters block
	
	echo "<h2>".$lang_label["dbmain_title"]."</h2>";
	echo "<h3>".$lang_label["db_purge_audit"]."<a href='help/".$help_code."/chap8.php#841' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";

	echo "<table cellpadding='4' cellspacing='4' border='0'>";
	echo "<tr><td class='datos'>";
	$result_t=mysql_query("SELECT COUNT(*) FROM tsesion");
	$row=mysql_fetch_array($result_t);
	echo "<b>".$lang_label["total"]."</b>";
	echo "<td class='datos'>".$row[0]." ".$lang_label["records"];
	
	echo "<tr>";	
	$result_t=mysql_query("SELECT min(fecha) FROM tsesion");
	$row=mysql_fetch_array($result_t);
	echo "<td class='datos2'><b>".$lang_label["first_date"]."</b>";
	echo "<td class='datos2'>".$row[0];

	echo "<tr><td class='datos'>";	
	$result_t=mysql_query("SELECT max(fecha) FROM tsesion");
	$row=mysql_fetch_array($result_t);
	echo "<b>".$lang_label["latest_date"]."</b>";
	echo "<td class='datos'>".$row[0];
	echo "</table>";
?>
	<h3><?php echo $lang_label["purge_data"] ?></h3>
	<form name="db_audit" method="post" action="index.php?sec=gdbman&sec2=godmode/db/db_audit">
	<table width='300' border='0'>
	<tr><td class='datos'>
	<select name="date_purge" class="w255">
	<option value="<?php echo $month3 ?>"><?php echo $lang_label["purge_audit_90day"] ?>
	<option value="<?php echo $month ?>"><?php echo $lang_label["purge_audit_30day"] ?>
	<option value="<?php echo $week2 ?>"><?php echo $lang_label["purge_audit_14day"] ?>
	<option value="<?php echo $week ?>"><?php echo $lang_label["purge_audit_7day"] ?>
	<option value="<?php echo $d3 ?>"><?php echo $lang_label["purge_audit_3day"] ?>
	<option value="<?php echo $d1 ?>"><?php echo $lang_label["purge_audit_1day"] ?>
	<option value="<?php echo $all_data ?>"><?php echo $lang_label["purge_audit_all"] ?>
	</select>
	
	<td class="datos">
	<input class="sub" type="submit" name="purgedb" value="<?php echo $lang_label["doit"] ?>"  onClick="if (!confirm('<?php  echo $lang_label["are_you_sure"] ?>')) return false;">
	
	</table>
	</form>

	
<?php
	mysql_close();
}
else {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Database Management Audit");
	require ("general/noaccess.php");
}
?>