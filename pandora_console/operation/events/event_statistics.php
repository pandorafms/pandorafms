<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Cargamos variables globales
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");
if (comprueba_login() == 0) {
	$id_usuario =$_SESSION["id_usuario"];
        if (give_acl($id_usuario, 0, "AR")==1) {
		echo "<h2>".$lang_label["events"]."</h2>";
		echo "<h3>".$lang_label["event_statistics"]."<a href='help/".substr($language_code,0,2)."/chap5.php#51' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
		echo '<img src="reporting/fgraph.php?tipo=total_events" border=0>';
		echo "<br><br>";
		echo '<img src="reporting/fgraph.php?tipo=user_events" border=0>';
		echo "<br><br>";
		echo '<img src="reporting/fgraph.php?tipo=group_events" border=0>';
		echo "<br><br>";
 	} else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access event viewer");
		require ("general/noaccess.php");
	}
}
?>