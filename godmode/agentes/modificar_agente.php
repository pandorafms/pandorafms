<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load global vars
require("include/config.php");
if (give_acl($id_user, 0, "AW")==1) {
	if (isset($_GET["borrar_agente"])){ // if delete agent
		$id_agente = entrada_limpia($_GET["borrar_agente"]);
		$id_grupo = dame_id_grupo($id_agente);
		if (give_acl($id_user, $id_grupo, "AW")==1){
			// Firts delete from agents table
			$sql_delete= "DELETE FROM tagente WHERE id_agente = ".$id_agente;
			$result=mysql_query($sql_delete);		
			if (! $result)
				echo "<h3 class='error'>".$lang_label["delete_agent_no"]."</h3>"; 
			else
				echo "<h3 class='suc'>".$lang_label["delete_agent_ok"]."</h3>";
			// Delete agent access table
			$sql_delete = "DELETE FROM tagent_access WHERE id_agent = ".$id_agente;
			// Delete tagente_datos data
			$result=mysql_query($sql_delete);
			$sql_delete4="DELETE FROM tagente_datos WHERE id_agente=".$id_agente;
			$result=mysql_query($sql_delete4);
			// Delete tagente_datos_string data
			$result=mysql_query($sql_delete);
			$sql_delete4="DELETE FROM tagente_datos_string WHERE id_agente=".$id_agente;
			$result=mysql_query($sql_delete4);
			// Delete from tagente_datos
			$sql1='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agente;
			$result1=mysql_query($sql1);
			while ($row=mysql_fetch_array($result1)){
				$sql_delete4="DELETE FROM tagente_datos_inc WHERE id_agente_modulo=".$row["id_agente_modulo"];
				$result=mysql_query($sql_delete4);
			}
			$sql_delete2 ="DELETE FROM tagente_modulo WHERE id_agente = ".$id_agente; // delete from tagente_modulo
			$sql_delete3 ="DELETE FROM tagente_estado WHERE id_agente = ".$id_agente; // delete from tagente_estado
			$result=mysql_query($sql_delete2);
			$result=mysql_query($sql_delete3);
		} else {
				audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to delete an Agent");
				require ("general/noaccess.php");
        	}
	}
	echo "<h2>".$lang_label["agent_conf"]."</h2>";
	echo "<h3>".$lang_label["agent_defined2"]."</h3>";
	
	// Load icon index from tgrupos
	$iconindex_g[]="";
	$sql_g='SELECT * FROM tgrupo';
	$result_g=mysql_query($sql_g);
	while ($row_g=mysql_fetch_array($result_g)){
	$iconindex_g[$row_g["id_grupo"]] = $row_g["icon"];
	}
	
	$sql1='SELECT * FROM tagente ORDER BY nombre';
	$result=mysql_query($sql1);
	if (mysql_num_rows($result)){
	echo "<table cellpadding='3' cellspacing='3'>";
	echo "<th>".$lang_label["agent_name"];
	echo "<th>".$lang_label["group"];
	echo "<th>".$lang_label["description"];
	echo "<th>".$lang_label["delete"];

	while ($row=mysql_fetch_array($result)){
		$id_grupo = $row["id_grupo"];
		if (give_acl($id_user, $id_grupo, "AW")==1){
			echo "<tr><td class='datos'><b><a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=".$row["id_agente"]."'>".$row["nombre"]."</a></b>";
			echo '<td class="datos"> <img src="images/g_'.$iconindex_g[$id_grupo].'.gif" border="0"> ( '.dame_grupo($id_grupo).' )</td>';
			echo '<td class="datos">'.$row["comentarios"].'</td>';
			echo '<td class="datos" align="center"><a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&borrar_agente='.$row["id_agente"].'" onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;"><img border="0" src="images/cancel.gif"></a></td>';
 		}
	}
		echo "<tr><td colspan='4'><div class='raya'></div></td></tr>";	
		echo "<tr><td align='right' colspan='4'>";
		echo "<form method='post' action='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&creacion=1'>";
		echo "<input type='submit' class='sub' name='crt' value='".$lang_label["create_agent"]."'>";
		echo "</form></td></tr></table>";
		echo "</table>";
		} else {
		echo '<font class="red">'.$lang_label["no_agent_def"].'</font><br><br>';
		echo "<form method='post' action='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&creacion=1'>";
		echo "<input type='submit' class='sub' name='crt' value='".$lang_label["create_agent"]."'>";
		echo "</form>";
		}
} // Fin pagina
else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent Management");
		require ("general/noaccess.php");
	}
?>