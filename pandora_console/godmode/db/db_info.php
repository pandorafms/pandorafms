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
	echo "<h3>".$lang_label["db_info2"]."<a href='help/".$help_code."/chap8.php#81' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	echo "<table border=0>";
	echo "<tr><td><img src='reporting/fgraph.php?tipo=db_agente_modulo'><br>";
	echo "<tr><td><br>";
	echo "<tr><td><img src='reporting/fgraph.php?tipo=db_agente_paquetes'><br>";
	echo "<br><br><a href='index.php?sec=gdbman&sec2=godmode/db/db_info_data'>".$lang_label["press_db_info"]."</a>";
	echo "</table>";
	} 
	else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Database Management Info");
		require ("general/noaccess.php");
	}
?>
