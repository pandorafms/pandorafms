<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
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

if (comprueba_login() == 0) {
	if (isset($_GET["id_agente"])){
			
		$id_agente = $_GET["id_agente"];
		// get group for this id_agente
		$query="SELECT * FROM tagente WHERE id_agente = ".$id_agente;
		$res=mysql_query($query);
		$row=mysql_fetch_array($res); 
		$id_grupo = $row["id_grupo"];
		$id_usuario=$_SESSION["id_usuario"];
		if (give_acl($id_usuario, $id_grupo, "AR")==1){
			// Get the user who makes this request
			$id_usuario = $_SESSION["id_usuario"];

			// Check for Network FLAG change request
			if (isset($_GET["flag"])){
				if ($_GET["flag"]==1){
					if (give_acl($id_usuario, $id_grupo, "AW")==1){
						$query ="UPDATE tagente_modulo SET flag=1 WHERE id_agente_modulo = ".$_GET["id_agente_modulo"];
						$res=mysql_query($query);
					}
				}
			}

			if (give_acl($id_usuario,$id_grupo, "AR") == 1){
				if (isset($_GET["tab"]))
					$tab = $_GET["tab"];
				else
					$tab = "main";
				
				echo "
				<div id='menu_tab'>
				<ul class='mn'>	
				<li class='nomn'>";
					
				echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente'>Main</a>";
				echo "</li>";
				echo "<li class='nomn'>";
				echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=data'>Data</a>";
				echo "</li>";
				echo "<li class='nomn'>";
				echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=alert'>Alerts</A>";
				echo "</li>";
				echo "</ul>";
				echo "</div>";

				switch ($tab) {
				case "main":	require "estado_generalagente.php";
						echo "<br>";
						//require "estado_monitores.php";
						
						break;
				case "data": 	require "estado_ultimopaquete.php";
						break;
				case "alert": 	require "estado_alertas.php";
						break;
				}
			} else {
				audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to read data from agent ".dame_nombre_agente($id_agente));
				require ("general/noaccess.php");
			}
		} else {
			audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access (read) to agent ".dame_nombre_agente($id_agente));
			include ("general/noaccess.php");
		}
	}
}
?>