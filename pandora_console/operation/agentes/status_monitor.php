<?php
// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
// Copyright (c) 2004-2008 Raul Mateos Martin, raulofpandora@gmail.com
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

if (comprueba_login() != 0) {
        require ("general/noaccess.php");
        exit;
}

if ((give_acl($id_user, 0, "AR")!=1) AND (give_acl($id_user,0,"AW")!=1)) {
        audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
        "Trying to access Agent Management");
        require ("general/noaccess.php");
        exit;
}

echo "<h2>".$lang_label["ag_title"]." &gt; ";
echo $lang_label["monitor_listing"]."</h2>";


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
echo "<table cellspacing='4' cellpadding='4' width='600' class='databox'>";
echo "<tr><td valign='middle'>".$lang_label["group"]."</td>";
echo "<td valign='middle'>";
echo "<select name='ag_group' onChange='javascript:this.form.submit();' class='w130'>";

if ( $ag_group > 1 ){
	echo "<option value='".$ag_group."'>".dame_nombre_grupo($ag_group)."</option>";
} 
echo "<option value=1>".dame_nombre_grupo(1)."</option>";
list_group ($id_user);
echo "</select>";

// Module name selector
// This code thanks for an idea from Nikum, nikun_h@hotmail.com
if (isset($_POST["ag_modulename"])){
	$ag_modulename = $_POST["ag_modulename"];
	echo "<form method='post' action='index.php?sec=estado&
	sec2=operation/agentes/status_monitor&
	refr=60&ag_modulename=".$ag_modulename."'>";
} else {
	echo "<form method='post' action='index.php?sec=estado&
	sec2=operation/agentes/status_monitor&refr=60'>";
}

echo "<td class='f9' style='padding-left: 10px;'>";
echo "<img src='images/pixel_green.png' width=40 height=18><br>".$lang_label["green_light"]."</td>";
echo "<td class='f9' style='padding-left: 10px;'>";
echo "<img src='images/pixel_red.png' width=40 height=18><br>".$lang_label["red_light"]."</td>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td valign='middle'>".$lang_label["module_name"]."</td>";
echo "<td valign='middle'>
<select name='ag_modulename' onChange='javascript:this.form.submit();'>";
if ( isset($ag_modulename)){
	echo "<option>".$ag_modulename."</option>";
} 
echo "<option>".$lang_label["all"]."</option>";
$sql='SELECT DISTINCT nombre 
FROM tagente_modulo 
WHERE id_tipo_modulo in (2, 9, 12, 18, 6, 100)';
$result=mysql_query($sql);
while ($row=mysql_fetch_array($result)){
	echo "<option>".$row['0']."</option>";
}
echo "</select>";
echo "<td valign='middle'>
<noscript><input name='uptbutton' type='submit' class='sub' 
value='".$lang_label["show"]."'></noscript>
</form>";

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
			if ((isset($ag_modulename)) && ($ag_modulename != $lang_label["all"])){
				$query_gen='SELECT
				id_agente, id_tipo_modulo, module_interval, id_agente_modulo,
				nombre, descripcion
				FROM tagente_modulo 
				WHERE id_agente = '.$row["id_agente"].' 
				AND nombre = "'.entrada_limpia($_POST["ag_modulename"]).'" 
				AND
				id_tipo_modulo in (2, 9, 12, 18, 6, 100)';
				// generic_proc, remote_tcp_proc, ??, remote_snmp_proc, remote_icmp_proc
						} else {
				$query_gen='SELECT
				id_agente, id_tipo_modulo, module_interval, id_agente_modulo,
				nombre, descripcion
				FROM tagente_modulo 
				WHERE id_agente = '.$row["id_agente"].' 
				AND
				id_tipo_modulo in (2, 9, 12, 18, 6, 100)';
						}
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
					$string=$string. "<tr><td class='$tdcolor'>";
					$string=$string. "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$data["id_agente"]."&id_agente_modulo=".$data["id_agente_modulo"]."&flag=1&tab=data&refr=60'>";
					$string=$string."<img src='images/target.png'></a>";
					$string=$string. "</td><td class='$tdcolor'>";
					$string=$string."<b><a href='index.php?sec=estado&
					sec2=operation/agentes/ver_agente&
					id_agente=".$data["id_agente"]."'>".
					strtoupper(substr(dame_nombre_agente($data["id_agente"]),0,21))."</a></b>";
					$string=$string."</td><td class='$tdcolor'>";
					$string=$string."
					<img src='images/".show_icon_type($data["id_tipo_modulo"])."' border=0>
					</td>";
					$string=$string."<td class='$tdcolor'>".
					substr($data["nombre"],0,21)."</td>";
					$string=$string."<td class='".$tdcolor."f9' title='".$data["descripcion"]."'>".
					substr($data["descripcion"],0,30)."</td>";
					
					$string=$string."<td class='$tdcolor' align='center' width=25>";
					if ($data["module_interval"] == 0){
						$my_interval = give_agentinterval($data["id_agente"]);
					} else {
						$my_interval = $data["module_interval"];						
					}
					$string .= $my_interval;
					
					$query_gen2='SELECT * FROM tagente_estado 
					WHERE id_agente_modulo = '.$data["id_agente_modulo"];
					$result_gen2=mysql_query($query_gen2);
					$data2=mysql_fetch_array($result_gen2);
					$string=$string."<td class='$tdcolor' align='center' width=20>";
					if ($data2["datos"] > 0){
						$string=$string."<img src='images/pixel_green.png' width=40 height=18 title='".$lang_label["green_light"]."'>";
					} else {
						$string=$string."<img src='images/pixel_red.png' width=40 height=18 title='".$lang_label["red_light"]."'>";
					}
					
					$string=$string."<td class='".$tdcolor."f9'>";
					$seconds = time() - $data2["utimestamp"];
					if ($seconds >= ($my_interval*2))
						$string .= "<span class='redb'>";
					else
						$string .= "<span>";

					$string .= human_time_comparation($data2["timestamp"])."
					</span></td></tr>";
				}
			}
		}
	}
	if (isset($string)) {
		echo "
		<table cellpadding='4' cellspacing='4' width='750' class='databox'>
		<tr>
		<th>
		<th>".$lang_label["agent"]."</th>
		<th>".$lang_label["type"]."</th>
		<th>".$lang_label["name"]."</th>
		<th>".$lang_label["description"]."</th>
		<th>".$lang_label["interval"]."</th>
		<th>".$lang_label["status"]."</th>
		<th>".$lang_label["timestamp"]."</th>";
		echo $string; //the built table of monitors
		echo "</table>";
	} else {
		echo "<div class='nf'>".$lang_label["no_monitors_g"]."</div>";
	}
} else {
	echo "<div class='nf'>".$lang_label["no_agent"]."</div>";
}

?>
