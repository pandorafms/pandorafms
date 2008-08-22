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

?>

<h2><?php echo __('Pandora users') ?> &gt; 
<?php echo __('Users defined in Pandora') ?></h2>

<table cellpadding="4" cellspacing="4" width="700" class='databox'>
<th width="80px"><?php echo __('UserID')?></th>
<th width="155px"><?php echo __('Last contact')?></th>
<th width="45px"><?php echo __('Profile')?></th>
<th width="120px"><?php echo __('Name')?></th>
<th><?php echo __('Description')?></th>

<?php
$color = 1;


if (give_acl($config["id_user"], 0, "UM") == 1)
    $query1="SELECT * FROM tusuario";
else
    $query1="SELECT * FROM tusuario WHERE id_usuario = '".$config["id_user"]."'";

$resq1=mysql_query($query1);
while ($rowdup=mysql_fetch_array($resq1)){
	$name=$rowdup["id_usuario"];
	$nivel=$rowdup["nivel"];
	$real_name=$rowdup["nombre_real"];
	$comments=$rowdup["comentarios"];
	$fecha_registro =$rowdup["fecha_registro"];
	if ($color == 1){
		$tdcolor = "datos";
		$color = 0;
		$tip = "tip";
	}
	else {
		$tdcolor = "datos2";
		$color = 1;
		$tip = "tip2";
	}
	echo "<tr><td class='$tdcolor'><a href='index.php?sec=usuarios&sec2=operation/users/user_edit&ver=".$name."'><b>".$name."</b></a>";
	echo "<td class='$tdcolor'><font size=1>".$fecha_registro."</font>";
	echo "<td class='$tdcolor'>";
	if ($nivel == 1) 
		echo "<img src='images/user_suit.png'>";
	else
		echo "<img src='images/user_green.png'>";
	$sql1='SELECT * FROM tusuario_perfil WHERE id_usuario = "'.$name.'"';
	$result=mysql_query($sql1);
	echo "<a href='#' class='$tip'>&nbsp;<span>";
	if (mysql_num_rows($result)){
		while ($row=mysql_fetch_array($result)){
			echo dame_perfil($row["id_perfil"])."/ ";
			echo dame_grupo($row["id_grupo"])."<br>";
		}
	}
	else { echo __('This user doesn\'t have any assigned profile/group'); }
	echo "</span></a>";
	echo "<td class='$tdcolor' width='100'>".substr($real_name,0,16)."</td>";
	echo "<td class='$tdcolor'>".$comments."</td>";
	echo "</tr>";
}

echo "</table><br>";

?>


<h3><?php echo __('Profiles defined in Pandora') ?></h3>

<table cellpadding='4' cellspacing='4' class='databox'>
<?php

	$query_del1="SELECT * FROM tperfil";
	$resq1=mysql_query($query_del1);
	echo "<tr>";
	echo "<th width='180px'>
	<font size=1>".__('Profiles')."</th>";
	echo "<th width='40px'>
	<font size=1>IR<a href='#' class='tipp'>&nbsp;<span>".__("IR")."</span></a>
	</font></th>";
	echo "<th width='40px'>
	<font size=1>IW<a href='#' class='tipp'>&nbsp;<span>".__("IW")."</span></a>
	</font></th>";
	echo "<th width='40px'>
	<font size=1>IM<a href='#' class='tipp'>&nbsp;<span>".__("IM")."</span></a>
	</font></th>";
	echo "<th width='40px'>
	<font size=1>AR<a href='#' class='tipp'>&nbsp;<span>".__("AR")."</span></a>
	</font></th>";
	echo "<th width='40px'>
	<font size=1>AW<a href='#' class='tipp'>&nbsp;<span>".__("AW")."</span></a>
	</font></th>";
	echo "<th width='40px'>
	<font size=1>LW<a href='#' class='tipp'>&nbsp;<span>".("LW")."</span></a>
	</font></th>";
	echo "<th width='40px'>
	<font size=1>UM<a href='#' class='tipp'>&nbsp;<span>".__("UM")."</span></a>
	</font></th>";
	echo "<th width='40px'>
	<font size=1>DM<a href='#' class='tipp'>&nbsp;<span>".__("DM")."</span></a>
	</font></th>";
	echo "<th width='40px'>
	<font size=1>LM<a href='#' class='tipp'>&nbsp;<span>".__("LM")."</span></a>
	</font></th>";
	echo "<th width='40px'>
	<font size=1>PM<a href='#' class='tipp'>&nbsp;<span>".__("PM")."</span></a>
	</font></th>";
	$color = 1;
	while ($rowdup=mysql_fetch_array($resq1)){
		$id_perfil = $rowdup["id_perfil"];
		$nombre=$rowdup["name"];
		$incident_view = $rowdup["incident_view"];
		$incident_edit = $rowdup["incident_edit"];
		$incident_management = $rowdup["incident_management"];
		$agent_view = $rowdup["agent_view"];
		$agent_edit =$rowdup["agent_edit"];
		$alert_edit = $rowdup["alert_edit"];
		$user_management = $rowdup["user_management"];
		$db_management = $rowdup["db_management"];
		$alert_management = $rowdup["alert_management"];
		$pandora_management = $rowdup["pandora_management"];
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr><td class='$tdcolor"."_id'>".$nombre;
		
		echo "<td class='$tdcolor'>";
		if ($incident_view == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($incident_edit == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($incident_management == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($agent_view == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($agent_edit == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($alert_edit == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($user_management == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($db_management == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($alert_management == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($pandora_management == 1) echo "<img src='images/ok.png' border=0>";

	}
} //end of page
?>
</tr></table>
