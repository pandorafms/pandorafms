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
if (comprueba_login() == 0) {

?>

<h2><?php echo $lang_label["users_"] ?></h3>
<h3><?php echo $lang_label["users_pandora"] ?></h3>

<table width="500">
<tr><td>
<div align="justify">
<img src='images/muchos_pulpos.gif' align='right'>
<?php echo $lang_label["users_msg"] ?>
</div>
</td></tr>
</table><br>


<h3><?php echo $lang_label["users"] ?></h3>

<table cellpadding="3" cellspacing="3" width="700">
<th class="w80"><?php echo $lang_label["user_ID"]?>
<th class="w155"><?php echo $lang_label["last_contact"]?>
<th class="w45"><?php echo $lang_label["profile"]?>
<th class="w120"><?php echo $lang_label["name"]?>
<th><?php echo $lang_label["description"]?>

<?php
$color = 1;
$query1="SELECT * FROM tusuario";
$resq1=mysql_query($query1);
while ($rowdup=mysql_fetch_array($resq1)){
	$nombre=$rowdup["id_usuario"];
	$nivel =$rowdup["nivel"];
	$comentarios =$rowdup["comentarios"];
	$fecha_registro =$rowdup["fecha_registro"];
	if ($color == 1){
		$tdcolor = "datos";
		$color = 0;
	}
	else {
		$tdcolor = "datos2";
		$color = 1;
	}
	echo "<tr><td class='$tdcolor'><a href='index.php?sec=usuarios&sec2=operation/users/user_edit&ver=".$nombre."'><b>".$nombre."</b></a>";
	echo "<td class='$tdcolor'><font size=1>".$fecha_registro."</font>";
	echo "<td class='$tdcolor'>";
	if ($nivel == 1) 
		echo "<img src='images/admin.gif'>";
	else
		echo "<img src='images/user.gif'>";
	$sql1='SELECT * FROM tusuario_perfil WHERE id_usuario = "'.$nombre.'"';
	$result=mysql_query($sql1);
	echo "<a href='#' class='tip'>&nbsp;<span>";
	if (mysql_num_rows($result)){
		while ($row=mysql_fetch_array($result)){
			echo dame_perfil($row["id_perfil"])."/ ";
			echo dame_grupo($row["id_grupo"])."<br>";
		}
	}
	else { echo $lang_label["no_profile"]; }
	echo "</span></a>";
	echo "<td class='$tdcolor' width='100'>".substr($rowdup["nombre_real"],0,16);
	echo "<td class='$tdcolor'>".$comentarios;
}

echo "<tr><td colspan='5'><div class='raya'></div></td></tr></table><br>";

?>


<h3><?php echo $lang_label["definedprofiles"] ?></h3>

<table cellpadding=3 cellspacing=3 border=0>
<?php

	$query_del1="SELECT * FROM tperfil";
	$resq1=mysql_query($query_del1);
	echo "<tr>";
	echo "<th class='w180d'><font size=1>".$lang_label["profiles"];
	echo "<th class='w40d'><font size=1>IR<a href='#' class='tip2'>&nbsp;<span>".$help_label["IR"]."</span></a>";
	echo "<th class='w40d'><font size=1>IW<a href='#' class='tip2'>&nbsp;<span>".$help_label["IW"]."</span></a>";
	echo "<th class='w40d'><font size=1>IM<a href='#' class='tip2'>&nbsp;<span>".$help_label["IM"]."</span></a>";
	echo "<th class='w40d'><font size=1>AR<a href='#' class='tip2'>&nbsp;<span>".$help_label["AR"]."</span></a>";
	echo "<th class='w40d'><font size=1>AW<a href='#' class='tip2'>&nbsp;<span>".$help_label["AW"]."</span></a>";
	echo "<th class='w40d'><font size=1>LW<a href='#' class='tip2'>&nbsp;<span>".$help_label["LW"]."</span></a>";
	echo "<th class='w40d'><font size=1>UM<a href='#' class='tip2'>&nbsp;<span>".$help_label["UM"]."</span></a>";
	echo "<th class='w40d'><font size=1>DM<a href='#' class='tip2'>&nbsp;<span>".$help_label["DM"]."</span></a>";
	echo "<th class='w40d'><font size=1>LM<a href='#' class='tip2'>&nbsp;<span>".$help_label["LM"]."</span></a>";
	echo "<th class='w40d'><font size=1>PM<a href='#' class='tip2'>&nbsp;<span>".$help_label["PM"]."</span></a>";
	$color = 0;
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
		if ($incident_view == 1) echo "<img src='images/ok.gif' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($incident_edit == 1) echo "<img src='images/ok.gif' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($incident_management == 1) echo "<img src='images/ok.gif' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($agent_view == 1) echo "<img src='images/ok.gif' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($agent_edit == 1) echo "<img src='images/ok.gif' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($alert_edit == 1) echo "<img src='images/ok.gif' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($user_management == 1) echo "<img src='images/ok.gif' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($db_management == 1) echo "<img src='images/ok.gif' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($alert_management == 1) echo "<img src='images/ok.gif' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($pandora_management == 1) echo "<img src='images/ok.gif' border=0>";

	}
} //end of page
?>
<tr><td colspan='11'><div class='raya'></div></td></tr></table>