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
require ("include/config.php");
check_login ();

if ((give_acl ($id_user, 0, "DM")==1) or (dame_admin ($id_user)==1)) {
	// Todo for a good DB maintenance 
	/* 
 		- Delete too on datos_string and and datos_inc tables 
 		
 		- A function to "compress" data, and interpolate big chunks of data (1 month - 60000 registers) 
 		  onto a small chunk of interpolated data (1 month - 600 registers)
 		
 		- A more powerful selection (by Agent, by Module, etc).
 	*/
		
	echo "<h2>".$lang_label["dbmain_title"]." &gt; ";
	echo  $lang_label["db_stat_agent"]."</h2>";
	echo "<table cellspacing='4' cellpadding='4' class='databox'>";
	echo "<tr>
	<th>".$lang_label["agent_name"]."</th>";
	echo "<th>".$lang_label["assigned_module"]."</th>";
	echo "<th>".$lang_label["total_data"]."</th>";
	$color=0;
	
	$result_2=get_db_all_fields_in_table("tagente","id_agente");
	foreach($result_2 as $rownum => $row2) {
		$total_agente=0;
		$result_3=mysql_query("SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ".$row2["id_agente"]);
		$row3c = mysql_num_rows($result_3);
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
		echo "<tr>
		<td class='$tdcolor'>
		<b><a href='index.php?sec=gagente&sec2=operation/agentes/ver_agente&id_agente=".$row2["id_agente"]."'>".dame_nombre_agente($row2[0])."</a></b></td>";
		echo "<td class='$tdcolor'>".$row3c."</td>";
		echo "<td class='$tdcolor'>".$total_agente."</td></tr>";
		flush();
   		//ob_flush();
	}
	echo "</table>";
} else {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Database Management Info data");
	require ("general/noaccess.php");
}
?>
