<?php 
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2005
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
	echo "<h3>".$lang_label["db_stat_agent"]."</h3>";
	echo "<table cellspacing='3' cellpadding='3'>";
	echo "<tr><th>".$lang_label["agent_name"];
	echo "<th>".$lang_label["assigned_module"];
	echo "<th>".$lang_label["total_data"];
	
	$result_2=mysql_query("SELECT id_agente FROM tagente");
	while ($row2=mysql_fetch_array($result_2)){	
		$total_agente=0;
		$result_3c=mysql_query("SELECT COUNT(id_agente_modulo) FROM tagente_modulo WHERE id_agente = ".$row2["id_agente"]);
		$row3c=mysql_fetch_array($result_3c);
		$result_3=mysql_query("SELECT * FROM tagente_modulo WHERE id_agente = ".$row2["id_agente"]);
		// for all data_modules belongs to an agent
		while ($row3=mysql_fetch_array($result_3)){	
			$result_4=mysql_query("SELECT COUNT(id_agente_modulo) FROM tagente_datos WHERE id_agente_modulo = ".$row3["id_agente_modulo"]);
			$row4=mysql_fetch_array($result_4);
			$total_agente=$total_agente + $row4[0];
		}	
		echo "<tr><td class='datos'><b><a href='index.php?sec=gagente&sec2=operation/agentes/ver_agente&id_agente=".$row2["id_agente"]."'>".dame_nombre_agente($row2[0])."</a></b>";
		echo "<td class=datos>".$row3c[0];
		echo "<td class=datos>".$total_agente;
		flush();
   		//ob_flush();
	}
	echo "<tr><td colspan='3'><div class='raya'></div></td></tr></table>";
}
else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Database Management Info data");
		require ("general/noaccess.php");
	}
?>