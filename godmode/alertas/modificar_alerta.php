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
if ( (give_acl($id_user, 0, "LM")==1)){
	if (isset($_POST["update_alerta"])){ // se ha modificado algun parametro de agente
		$id_alerta = entrada_limpia($_POST["id_alerta"]);
	    $nombre =  entrada_limpia($_POST["nombre"]);
	    $comando =  entrada_limpia($_POST["comando"]);
	    $descripcion=  entrada_limpia($_POST["descripcion"]);
	    $sql_update ="UPDATE talerta SET nombre = '".$nombre."', comando = '".$comando."', descripcion = '".$descripcion."' WHERE id_alerta= '".$id_alerta."'";
		$result=mysql_query($sql_update);	
		if (! $result)
			echo "<h3 class='error'>".$lang_label["update_alert_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["update_alert_ok"]."</h3>";
	}

	if (isset($_POST["crear_alerta"])){ // se ha modificado algun parametro de agente
		// $id_alerta = entrada_limpia($_POST["id_alerta"]);
	    $nombre =  entrada_limpia($_POST["nombre"]);
	    $comando =  entrada_limpia($_POST["comando"]);
	    $descripcion=  entrada_limpia($_POST["descripcion"]);
	    $sql_update ="INSERT talerta (nombre, comando, descripcion) VALUES ('".$nombre."', '".$comando."', '".$descripcion."')";
		$result=mysql_query($sql_update);	
		if (! $result)
			echo "<h3 class='error'>".$lang_label["create_alert_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["create_alert_ok"]."</h3>";  
	}
	
	if (isset($_GET["borrar_alerta"])){ // se ha modificado algun parametro
		$id_alerta = entrada_limpia($_GET["borrar_alerta"]);
		// Primero borramos de la tabla de tagente_modulo
		$sql_delete= "DELETE FROM talerta WHERE id_alerta = ".$id_alerta;
		$result=mysql_query($sql_delete);		
		if (! $result)
			echo "<h3 class='error'>".$lang_label["delete_alert_no"]."</h3>"; 
		else
			echo "<h3 class='suc'>".$lang_label["delete_alert_ok"]."</h3>"; 

		$sql_delete2 ="DELETE FROM talerta_agente_modulo WHERE id_alerta = ".$id_alerta; 
		$result=mysql_query($sql_delete2);
	}

    echo "<h2>".$lang_label["alert_config"]."</h2>";
	echo "<h3>".$lang_label["alert_defined2"]."<a href='help/".substr($language_code,0,2)."/chap3.php#3221' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	echo "<table width='500' cellpadding='3' cellspacing='3'>";
	echo "<th class='w100'>".$lang_label["alertname"];
	echo "<th>".$lang_label["description"];
	echo "<th>".$lang_label["delete"];
	$color=1;
	$sql1='SELECT * FROM talerta ORDER BY nombre';
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
			}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr><td class='$tdcolor'><b><a href='index.php?sec=galertas&sec2=godmode/alertas/configurar_alerta&id_alerta=".$row["id_alerta"]."'>".$row["nombre"]."</a></b>";
		echo "<td class='$tdcolor'>".$row["descripcion"];
		echo "<td class='$tdcolor' align='center'><a href='index.php?sec=gagente&sec2=godmode/alertas/modificar_alerta&borrar_alerta=".$row["id_alerta"]."' onClick='if (!confirm(\' ".$lang_label["are_you_sure"]."\')) return false;'><img border='0' src='images/cancel.gif'></a>";
	}
	
	echo "<tr><td colspan='3'><div class='raya'></div></td></tr>";
	echo "<tr><td align='right' colspan='3'>";
	echo "<form method=post action='index.php?sec=galertas&sec2=godmode/alertas/configurar_alerta&creacion=1'>";
	echo "<input type='submit' class='sub' name='crt' value='".$lang_label["create_alert"]."'>";
	echo "</form>";
	echo "</td></tr></table>";
	
} // Fin pagina
else {
                audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Alert Management");
                require ("general/noaccess.php");
        }
?>