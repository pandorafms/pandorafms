<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load global vars
require("include/config.php");
if (comprueba_login() == 0)
   $id_user = $_SESSION["id_usuario"];
   if (give_acl($id_user, 0, "PM")==1) {
   
	if (isset($_POST["crear_grupo"])){ // Create group
		$nombre = entrada_limpia($_POST["nombre"]);
		$sql_insert="INSERT INTO tgrupo (nombre, icon) VALUES ('".$nombre."', '".$icon."') ";
		$result=mysql_query($sql_insert);	
		if (! $result)
			echo "<h3 class='error'>".$lang_label["create_group_no"]."</h3>";
		else {
			echo "<h3 class='suc'>".$lang_label["create_group_ok"]."</h3>"; 
			$id_grupo = mysql_insert_id();
		}
	}

	if (isset($_POST["update_grupo"])){ // se ha modificado algun parametro de agente
		$nombre = entrada_limpia($_POST["nombre"]);
		$id_grupo = entrada_limpia($_POST["id_grupo"]);
		$icon = entrada_limpia($_POST["icon"]);
	    $sql_update ="UPDATE tgrupo SET nombre = '".$nombre."', icon = '".$icon."' WHERE id_grupo = '".$id_grupo."'";
		$result=mysql_query($sql_update);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["modify_group_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["modify_group_ok"]."</h3>";
	}
	
	if (isset($_GET["borrar_grupo"])){ // se ha modificado algun parametro
		$id_borrar_modulo = entrada_limpia($_GET["id_grupo"]);
		
		// First delete from tagente_modulo
		$sql_delete= "DELETE FROM tgrupo WHERE id_grupo = ".$id_borrar_modulo;
		$result=mysql_query($sql_delete);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["delete_group_no"]."</h3>"; 
		else
			echo "<h3 class='suc'>".$lang_label["delete_group_ok"]."</h3>";
	}
	echo "<h2>".$lang_label["group_management"]."</h2>";	
	echo "<h3>".$lang_label["definedgroups"]."</h3>";

	// Load icon index from tgrupos
	$iconindex_g[]="";
	echo "<table cellpadding=3 cellspacing=3>";
	echo "<th class='w40'>".$lang_label["icon"]."</th>";
	echo "<th class='w180'>".$lang_label["group_name"]."</th>";
	echo "<th class='w80'>".$lang_label["delete"]."</th>";
	$sql1='SELECT * FROM tgrupo ORDER BY nombre';
	$result=mysql_query($sql1);
	
	while ($row=mysql_fetch_array($result)){
	$iconindex_g[$row["id_grupo"]] = $row["icon"];
	if ($row["id_grupo"] != 1){
		echo "<tr><td class='datos' align='center'><img src='images/g_".$iconindex_g[$row["id_grupo"]].".gif' border='0'>"	;
		echo "<td class='datos'><b><a href='index.php?sec=gagente&sec2=godmode/grupos/configurar_grupo&id_grupo=".$row["id_grupo"]."'>".$row["nombre"]."</a></b>";
		echo '<td class="datos" align="center"><a href="index.php?sec=gagente&sec2=godmode/grupos/lista_grupos&id_grupo='.$row["id_grupo"].'&borrar_grupo='.$row["id_grupo"].'" onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;"><img border=0 src="images/cancel.gif"></a>';
	}
	}
	echo "<tr><td colspan='3'><div class='raya'></div></td></tr>";
	echo "<tr><td colspan='3' align='right'>";
	echo "<form method=post action='index.php?sec=gagente&sec2=godmode/grupos/configurar_grupo&creacion_grupo=1'>";
	echo "<input type='submit' class='sub' name='crt' value='".$lang_label["create_group"]."'>";
	echo "</form></table>";

   } // Fin pagina
   else {
			audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Group Management");
			require ("general/noaccess.php");
        }

?>