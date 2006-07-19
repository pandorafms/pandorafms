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
		
	echo "<h2>".$lang_label["dbmain_title"]."</h2>";
	echo "<h3>".$lang_label["db_stat_agent"]."</h3>";
	echo "<table cellspacing='3' cellpadding='3'>";
	echo "<tr><th>".$lang_label["agent_name"];
	echo "<th>".$lang_label["assigned_module"];
	echo "<th>".$lang_label["total_data"];
	$color=0;
	
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
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr><td class='$tdcolor'><b><a href='index.php?sec=gagente&sec2=operation/agentes/ver_agente&id_agente=".$row2["id_agente"]."'>".dame_nombre_agente($row2[0])."</a></b>";
		echo "<td class='$tdcolor'>".$row3c[0];
		echo "<td class='$tdcolor'>".$total_agente;
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