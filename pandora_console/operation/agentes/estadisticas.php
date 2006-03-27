<?php 

// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load global vars
require("include/config.php");
if (comprueba_login() == 0){ 
	$iduser_temp=$_SESSION['id_usuario'];
	if (give_acl($iduser_temp, 0, "AR") == 1){
		echo "<h2>".$lang_label["ag_title"]."</h2>";
		echo "<h3>".$lang_label["db_stat_agent"]."<a href='help/chap3_en.php#337' target='_help'><img src='images/ayuda.gif' border='0' class='help'></a></h3>";
		echo "<table border=0>";
		echo "<tr><td><img src='reporting/fgraph.php?tipo=db_agente_modulo'><br>";
		echo "<tr><td><br>";
		echo "<tr><td><img src='reporting/fgraph.php?tipo=db_agente_paquetes'><br>";
		echo "</table>";
	}
 	else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent estatistics");
		require ("general/noaccess.php");
	}
}
?>