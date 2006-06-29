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
 	// Todo for a good DB maintenance 
 	/* 
 		- Delete too on datos_string and and datos_inc tables 
 		
 		- A function to "compress" data, and interpolate big chunks of data (1 month - 60000 registers) 
 		  onto a small chunk of interpolated data (1 month - 600 registers)
 		
 		- A more powerful selection (by Agent, by Module, etc).
 	*/

	echo "<h2>".$lang_label["dbmain_title"]."</h2>";
	echo "<h3>".$lang_label["pandora_db"]."</h3>";
	echo "<table width=550 cellspacing=3 cellpadding=3 border=0>";
	echo "<tr><td>";
	echo "<h3>".$lang_label["current_dbsetup"]."</h3>";
	echo "<i>".$lang_label["days_compact"].":</i>&nbsp;<b>".$days_compact."</b><br><br>";
	echo "<i>".$lang_label["days_purge"].":</i>&nbsp;<b>".$days_purge."</b><br><br>";
	echo "</div>";
	echo "<tr><td><div align='justify'>";
	echo $lang_label["dbsetup_info"];
	echo "</div><br>";
	echo '<img src="reporting/fgraph.php?tipo=db_agente_purge&id=-1">';
	echo "</table>";
	} else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Database Management");
		require ("general/noaccess.php");
	}
?>
