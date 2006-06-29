<?php

// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raúl Mateos <raulofpandora@gmail.com>, 2005-2006

// Load global vars
require("include/config.php");

if (comprueba_login() == 0)
	if (give_acl($id_user, 0, "AR")==1) {
	echo "<h2>".$lang_label["ag_title"]."</h2>";
	echo "<h3>".$lang_label["group_view"]."</h3>";

	$iduser_temp=$_SESSION['id_usuario'];
	// $mis_grupos - Define array mis_grupos to put here all groups with Agent Read permission
	
	$sql1='SELECT * FROM tgrupo';
	$result2=mysql_query($sql1);
	while ($row=mysql_fetch_array($result2)){
		if ($row["id_grupo"]!=1)
		  if (give_acl($iduser_temp,$row["id_grupo"], "AR") == 1)
			$mis_grupos[]=$row["id_grupo"]; //All my groups in an array
	}
	$contador_grupo = 0;
	$contador_agente=0;
	$array_index = 0;
	$estado_grupo_ok =0;
	$estado_grupo_down =0;
	$estado_grupo_bad =0;

// Debug
// echo "tengo un total de ".count($mis_grupos)." grupos <br><br>";
	
	// Recorro cada grupo para ver el estado de todos los modulos
	foreach ($mis_grupos as $migrupo)
	if ($migrupo != "") {
		$grupo[$array_index]["agent"]=0;
		$grupo[$array_index]["ok"]=0;
		$grupo[$array_index]["down"]=0;
		$grupo[$array_index]["bad"]=0;
		$grupo[$array_index]["icon"]=dame_grupo_icono($migrupo);
		$grupo[$array_index]["id_grupo"]=$migrupo;
		$existen_agentes =0;
		$sql1="SELECT * FROM tagente WHERE disabled=0 AND id_grupo =".$migrupo;
		if ($result1=mysql_query($sql1))
			while ($row1 = mysql_fetch_array($result1)){
				$existen_agentes =1;
				$id_agente=$row1["id_agente"];
				$ultimo_contacto = $row1["ultimo_contacto"];
				$intervalo = $row1["intervalo"];
	 			$ahora=date("Y/m/d H:i:s");
				if ($ultimo_contacto <> "")
	 				$seconds = strtotime($ahora) - strtotime($ultimo_contacto);
				else
	 				$seconds = -100000;
				# Defines if Agent is down (interval x 2 > time last contact)
				$down=0;
				if ($seconds >= ($intervalo*2)){ //  if Agent is down an alert is shown
					$grupo[$array_index]["down"]++; // Estado grupo, agent down
					$estado_grupo_down++;
					$down=1;
				}

				$grupo[$array_index]["agent"]++;
				$grupo[$array_index]["group"]=dame_nombre_grupo($migrupo);
				$contador_agente++;	//  Estado grupo, agent
				if ($down ==0){
					$sql2="SELECT * FROM tagente_modulo WHERE (id_tipo_modulo = 2 OR id_tipo_modulo = 6 OR id_tipo_modulo = 10) AND id_agente =".$row1["id_agente"];
					$result2=mysql_query($sql2);
					while ($row2 = mysql_fetch_array($result2)){
						$sql3="SELECT * FROM tagente_estado WHERE id_agente_modulo = ".$row2["id_agente_modulo"];
						$result3=mysql_query($sql3);
						$row3 = mysql_fetch_array($result3);
				 		if ($row3["datos"] !=0){
							$estado_grupo_ok++;
							$grupo[$array_index]["ok"]++; // Estado grupo, agent ok
						}
						else  {
							$estado_grupo_bad++;
							$grupo[$array_index]["bad"]++; // Estado grupo, agent BAD
						}
						
					}
				}
			}
		
		if ($existen_agentes == 1){
			$array_index++;
		}
	}
	if ($contador_agente==0) {echo "<font class='red'>".$lang_label["no_agent_def"]."</font>";}
/*
for ($a=0; $a < $array_index; $a++)
{
// Debug, show all groups parsed as valid
echo $grupo[$a]['group'];
//echo $grupo[$a]["icon"];
echo "<br>";
}
*/

	//echo "Count ".$contador_grupo."<br><br>";
 	$ancho = ceil(sqrt($array_index+1));
	
//echo "DEBUG ANCHO: $ancho <br>";
	$real_count =0; // Puedo tener una tabla con mas items en ella que los que realmente debo mostrar, real count cuenta los que voy poniendo hasta llegar a array_index que son los que hay en el array $grupo.
	echo "<table border=0 cellpadding=10 cellspacing=10>";
	for ($table=0;$table < $ancho-1;$table++){
		echo "<tr class='bot'>";
		for ($table_row=0;$table_row < $ancho;$table_row++){
			if ($real_count < $array_index){

				$group_name = $grupo[$real_count]["group"];
				$icono_grupo = $grupo[$real_count]["icon"];
				$icono_type="";
				if ($grupo[$real_count]["down"]>0) {
					$icono_type="<img src='images/dot_white.gif' alt=''>";
				}
				if ($grupo[$real_count]["bad"]>0) {
					$icono_type=$icono_type."<img src='images/dot_red.gif' alt=''>";
				}
				if ($grupo[$real_count]["ok"]>0) {
					$icono_type=$icono_type."<img src='images/dot_green.gif' alt=''>";
				}
				
				$celda = "<td class='bot'><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id=".$grupo[$real_count]["id_grupo"]."' class='info'><img class='top' src='images/groups/".$icono_grupo."_1.gif' border='0' alt=''><span>
				<table cellspacing='2' cellpadding='0' style='margin-left:20px'>
				<tr><td colspan='2' width='91' class='lb'>".$lang_label["agents"].": </td></tr>
				<tr><td colspan='2' class='datos' align='center'><b>".$grupo[$real_count]["agent"]."</b></td></tr></table>
				<table cellspacing='2' cellpadding='0' style='margin-left:20px'>
				<tr><td colspan='2' width='90' class='lb'>".ucfirst($lang_label["monitors"]).":</td></tr>
				<tr><td class='datos'><img src='images/b_green.gif' align='top' alt='' border='0'> ".$lang_label["ok"].": </td><td class='datos'><font class='greenb'>".$grupo[$real_count]["ok"]."</font></td></tr>
				<tr><td class='datos'><img src='images/b_down.gif' align='top' alt='' border='0'> ".$lang_label["down"].": </td><td class='datos'><font class='grey'>".$grupo[$real_count]["down"]."</font></td></tr>
				<tr><td class='datos'><img src='images/b_red.gif' align='top' alt='' border='0'> ".$lang_label["fail"].": </td><td class='datos'><font class='redb'>".$grupo[$real_count]["bad"]."</font></td></tr></table>
				</span></a><br><br>".$icono_type."<br><br><font class='gr'>".$group_name."</font>";

				echo $celda;
			}
			$real_count++;
		}
		echo "</tr>";
	}

	echo "</table>";

}
else {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent viewi (Grouped)");
	require ("general/noaccess.php");
}
?>