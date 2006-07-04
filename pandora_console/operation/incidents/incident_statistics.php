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
	$iduser=$_SESSION['id_usuario'];
	if (give_acl($id_user, 0, "IR")==1) {	
		echo "<h2>".$lang_label["incident_manag"]."</h2>";
		echo "<h3>".$lang_label["statistics"]."<a href='help/".substr($language_code,0,2)."/chap4.php#44' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
echo '<img src="reporting/fgraph.php?tipo=estado_incidente" border=0>';
echo "<br><br>";
echo '<img src="reporting/fgraph.php?tipo=prioridad_incidente" border=0>';
echo "<br><br>";
echo '<img src="reporting/fgraph.php?tipo=group_incident" border=0>';
echo "<br><br>";
echo '<img src="reporting/fgraph.php?tipo=user_incident" border=0>';
echo "<br><br>";
echo '<img src="reporting/fgraph.php?tipo=source_incident" border=0>';
echo "<br><br>";
	} else {
			require ("general/noaccess.php");
			audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Incident section");
        }
}
?>