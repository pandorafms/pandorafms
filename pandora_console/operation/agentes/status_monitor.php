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
 	if ((give_acl($id_user, 0, "AR")==1) or (give_acl($id_user,0,"AW")) or (dame_admin($id_user)==1)) {
	
	// Load icon index array from ttipo_modulo
	$iconindex[]="";
	$sql_tm='SELECT id_tipo, icon FROM ttipo_modulo';
	$result_tm=mysql_query($sql_tm);
	while ($row_tm=mysql_fetch_array($result_tm)){
		$iconindex[$row_tm["id_tipo"]] = $row_tm["icon"];
	}

	echo "<h2>".$lang_label["ag_title"]."</h2>";
	echo "<h3>".$lang_label["monitor_listing"]."<a href='help/".$help_code."/chap3.php#334' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	$iduser_temp=$_SESSION['id_usuario'];
	

	if (isset($_POST["ag_group"]))
		$ag_group = $_POST["ag_group"];
	elseif (isset($_GET["group_id"]))
		$ag_group = $_GET["group_id"];
	else
		$ag_group = -1;
	if (isset($_GET["ag_group_refresh"])){
		$ag_group = $_GET["ag_group_refresh"];
	}
	
	if (isset($_POST["ag_group"])){
		$ag_group = $_POST["ag_group"];
		echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60&ag_group_refresh=".$ag_group."'>";
	} else {
		echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60'>";
	}
	echo "<table border='0' cellspacing=3 cellpadding=3><tr><td valign='middle'>".$lang_label["group_name"];
	echo "<td valign='middle'>";
	echo "<select name='ag_group' onChange='javascript:this.form.submit();' class='w130'>";

	if ( $ag_group > 1 ){
		echo "<option value='".$ag_group."'>".dame_nombre_grupo($ag_group);
	} 
	echo "<option value=1>".dame_nombre_grupo(1);
	$mis_grupos[]=""; // Define array mis_grupos to put here all groups with Agent Read permission
	$sql='SELECT * FROM tgrupo';
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		if ($row["id_grupo"] != 1){
			if (give_acl($iduser_temp,$row["id_grupo"], "AR") == 1){
				echo "<option value='".$row["id_grupo"]."'>".dame_nombre_grupo($row["id_grupo"]);
				$mis_grupos[]=$row["id_grupo"]; //Put in  an array all the groups the user belongs
			}
		}
	}
	echo "</select>";

	// Module name selector
	// This code thanks for an idea from Nikum, nikun_h@hotmail.com
	if (isset($_POST["ag_modulename"])){
		$ag_modulename = $_POST["ag_modulename"];
		echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60&ag_modulename=".$ag_modulename."'>";
	} else {
		echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60'>";
	}
	echo "<tr><td valign='middle'>";
	echo $lang_label["module_name"]."<td valign='middle'> <select name='ag_modulename' onChange='javascript:this.form.submit();'>";
	if ( isset($ag_modulename)){
		echo "<option>".$ag_modulename;
	} 
	echo "<option>ALL</option>";
	$sql='SELECT DISTINCT nombre FROM tagente_modulo WHERE (id_tipo_modulo = 2) OR (id_tipo_modulo = 9) OR (id_tipo_modulo = 12) OR (id_tipo_modulo = 18) OR (id_tipo_modulo = 6) ';
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		echo "<option>".$row[0]."</option>";
	}
	echo "</select>";
	echo "<td valign='middle'><noscript><input name='uptbutton' type='submit' class='sub' value='".$lang_label["show"]."'></noscript></form>";
	

	// Show only selected names & groups
	if ($ag_group > 1) 
		$sql='SELECT * FROM tagente WHERE id_grupo='.$ag_group.' ORDER BY nombre';
	else 
		$sql='SELECT * FROM tagente ORDER BY id_grupo, nombre';
		
	echo "</table>";
	echo "<br>";
	$color =1;
	$result=mysql_query($sql);
	if (mysql_num_rows($result)){
		while ($row=mysql_fetch_array($result)){ //while there are agents	
			if ($row["disabled"] == 0) {
				if ((isset($ag_modulename)) && ($ag_modulename != "ALL"))
					$query_gen='SELECT * FROM tagente_modulo WHERE id_agente = '.$row["id_agente"].' AND nombre = "'.entrada_limpia($_POST["ag_modulename"]).'" AND ( (id_tipo_modulo = 2) OR (id_tipo_modulo = 9) OR (id_tipo_modulo = 12) OR (id_tipo_modulo = 18) OR (id_tipo_modulo = 6))';
				else
					$query_gen='SELECT * FROM tagente_modulo WHERE id_agente = '.$row["id_agente"].' AND ( (id_tipo_modulo = 2) OR (id_tipo_modulo = 9) OR (id_tipo_modulo = 12) OR (id_tipo_modulo = 18) OR (id_tipo_modulo = 6))';
				$result_gen=mysql_query($query_gen);
				if (mysql_num_rows ($result_gen)) {
					while ($data=mysql_fetch_array($result_gen)){
						if ($color == 1){
							$tdcolor="datos";
							$color =0;
						} else {
							$tdcolor="datos2";
							$color =1;
						}
						if (!isset($string)) {$string='';}
						$string=$string."<tr><td class='$tdcolor'><b>";
						$string=$string."<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$data["id_agente"]."'>".dame_nombre_agente($data["id_agente"])."</a></b>";
						$string=$string."<td class='$tdcolor'>";
						$string=$string."<img src='images/".$iconindex[$data["id_tipo_modulo"]]."' border=0>";
						$string=$string."<td class='$tdcolor'>".$data["nombre"];
						$string=$string."<td class='".$tdcolor."f9'>".substr($data["descripcion"],0,30);
						$string=$string."<td class='$tdcolor' width=25>".$data["max"]."/".$data["min"];
						$string=$string."<td class='$tdcolor' width=25>";
						if ($data["module_interval"] == 0){
							$string=$string.give_agentinterval($data["id_agente"]);
						} else {
							$string=$string.$data["module_interval"];
						}
						$query_gen2='SELECT * FROM tagente_estado WHERE id_agente_modulo = '.$data["id_agente_modulo"];
						$result_gen2=mysql_query($query_gen2);
						$data2=mysql_fetch_array($result_gen2);
						$string=$string."<td class='$tdcolor' align='center' width=20>";
						if ($data2["datos"] > 0){
							$string=$string."<img src='images/b_green.gif'>";
						} else {
							$string=$string."<img src='images/b_red.gif'>";
						}
						$string=$string."<td class='".$tdcolor."f9' width='140'>".$data2["timestamp"]."</td></tr>";
					}
				}
				else if($ag_group>1) {unset($string);}
			}
		}
		if (isset($string)) {
		echo "<table cellpadding='3' cellspacing='3' width='750'><tr><th>".$lang_label["agent"]."</th><th>".$lang_label["type"]."</th><th>".$lang_label["name"]."</th><th>".$lang_label["description"]."</th><th>".$lang_label["max_min"]."</th><th>".$lang_label["interval"]."</th><th>".$lang_label["status"]."</th><th>".$lang_label["timestamp"]."</th>";
		echo $string; //the built table of monitors
		echo "<tr><td colspan='8'><div class='raya'></div></td></tr></table>";
		echo "<br><table>";
		echo "<tr><td class='f9i'>";
		echo "<img src='images/b_green.gif'> - ".$lang_label["green_light"]."</td><td>&nbsp;</td>";
		echo "<td class='f9i'>";
		echo "<img src='images/b_red.gif'> - ".$lang_label["red_light"]."</td>";
		echo "</table>";
		}
		else {
		echo "<font class='red'>".$lang_label["no_monitors_g"]."</font>";
		}
	} else {
		echo "<font class='red'>".$lang_label["no_agent"]."</font>";
	}

} //end acl
} //end login

?>