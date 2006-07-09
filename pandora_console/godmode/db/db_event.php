<?php 

// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load global vars
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");
if (comprueba_login() == 0) 
	
	if ((give_acl($id_user, 0, "DM")==1) or (dame_admin($id_user)==1)) {	
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
	if (isset($_POST["date_purge"])){
		$from_date =$_POST["date_purge"];
		$query = "DELETE FROM tevento WHERE timestamp < '".$from_date."'";
		mysql_query($query);			
	}
	# End of get parameters block
	
	echo "<h2>".$lang_label["dbmain_title"]."</h2>";
	echo "<h3>".$lang_label["db_purge_event"]."<a href='help/".$help_code."/chap8.php#842' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";

	echo "<table cellpadding='4' cellspacing='4' border='0'>";
	echo "<tr><td class='datos'>";	
	$result_t=mysql_query("SELECT COUNT(*) FROM tevento");
	$row=mysql_fetch_array($result_t);
	echo "<b>".$lang_label["total"]."</b>";
	echo "<td class='datos'>".$row[0]." ".$lang_label["records"];
	
	echo "<tr>";	
	$result_t=mysql_query("SELECT min(timestamp) FROM tevento");
	$row=mysql_fetch_array($result_t);
	echo "<td class='datos2'><b>".$lang_label["first_date"]."</b>";
	echo "<td class='datos2'>".$row[0];
	
	
	echo "<tr><td class='datos'>";
	$result_t=mysql_query("SELECT max(timestamp) FROM tevento");
	$row=mysql_fetch_array($result_t);
	echo "<b>".$lang_label["latest_date"]."</b>";
	echo "<td class='datos'>".$row[0];
	echo "</table>";
?>

	<h3><?php echo $lang_label["purge_data"] ?></h3>
	<form name="db_audit" method="post" action="index.php?sec=gdbman&sec2=godmode/db/db_event">
	<table width='300' border='0'>
	<tr><td class='datos'>
	<select name="date_purge" class="w255">
	<option value="<?php echo $month3 ?>"><?php echo $lang_label["purge_event_90day"] ?>
	<option value="<?php echo $month ?>"><?php echo $lang_label["purge_event_30day"] ?>
	<option value="<?php echo $week2 ?>"><?php echo $lang_label["purge_event_14day"] ?>
	<option value="<?php echo $week ?>"><?php echo $lang_label["purge_event_7day"] ?>
	<option value="<?php echo $d3 ?>"><?php echo $lang_label["purge_event_3day"] ?>
	<option value="<?php echo $d1 ?>"><?php echo $lang_label["purge_event_1day"] ?>
	<option value="<?php echo $all_data ?>"><?php echo $lang_label["purge_event_all"] ?>
	</select>
	
	<td class="datos">
	<input class="sub" type="submit" name="purgedb" value="<?php echo $lang_label["doit"] ?>" onClick="if (!confirm('<?php  echo $lang_label["are_you_sure"] ?>')) return false;">
	</table>
	</form>
	
<?php
	mysql_close();
}
else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Database Management Event");
		require ("general/noaccess.php");
	}
?>