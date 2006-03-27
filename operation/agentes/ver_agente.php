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
	if (isset($_GET["id_agente"])){

		//echo "<td class='datos'><a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$id_agente."&id_agente_modulo=".$row3["id_agente_modulo"]."&flag=1'><img src='images/time.gif' border=0></a>";
			
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
				require "estado_generalagente.php";
				echo "<br>";
				require "estado_ultimopaquete.php";
				echo "<br>";
				require "estado_monitores.php";
				echo "<br>";
				require "estado_alertas.php";		
				echo "<br>";
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