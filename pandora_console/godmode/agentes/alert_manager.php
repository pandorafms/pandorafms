<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP additions
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to Pandora FMS 1.2 graph code and new XML reporting template management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// ====================================================================================
// VIEW ALERTS
// ====================================================================================<br>

$sql1='SELECT * FROM tagente_modulo WHERE id_agente = "'.$id_agente.'"';
$result=mysql_query($sql1);
	if ($row=mysql_num_rows($result)){

		echo "<h3>".$lang_label["assigned_alerts"]."<a href='help/".$help_code."/chap3.php#3222' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";

		$color=1;
		$string='';
		while ($row=mysql_fetch_array($result)){  // All modules of this agent
			$id_tipo = $row["id_tipo_modulo"];
			$nombre_modulo =$row["nombre"];
			$sql2='SELECT * FROM ttipo_modulo WHERE id_tipo = "'.$id_tipo.'"';
			$result2=mysql_query($sql2);
			$row2=mysql_fetch_array($result2);
			//module type modulo is $row2["nombre"];
			
			$sql3='SELECT * 
				FROM talerta_agente_modulo 
				WHERE id_agente_modulo = '.$row["id_agente_modulo"];
				// From all the alerts give me which are from my agent
			$result3=mysql_query($sql3);
			while ($row3=mysql_fetch_array($result3)){
				if ($color == 1){
					$tdcolor="datos";
					$color =0;
				} else {
					$tdcolor="datos2";
					$color =1;
				}
				$sql4='SELECT nombre FROM talerta WHERE id_alerta = '.$row3["id_alerta"];
				$result4=mysql_query($sql4);
				$row4=mysql_fetch_array($result4);
				// Alert name defined by  $row4["nombre"]; 
				$tipo_modulo = $row2["nombre"];
				$nombre_alerta = $row4["nombre"];
				$string = $string."<tr><td class='$tdcolor'>".$nombre_modulo."/".$tipo_modulo;
				$string = $string."<td class=$tdcolor>".$nombre_alerta;
				$string = $string."<td class='$tdcolor'>".$row3["time_threshold"];
		
				$mytempdata = fmod($row3["dis_min"], 1);
				if ($mytempdata == 0)
					$mymin = intval($row3["dis_min"]);
				else
					$mymin = $row3["dis_min"];
				$mymin = format_for_graph($mymin );

				$mytempdata = fmod($row3["dis_max"], 1);
				if ($mytempdata == 0)
					$mymax = intval($row3["dis_max"]);
				else
					$mymax = $row3["dis_max"];
				$mymax =  format_for_graph($mymax );
				// We have alert text ?
				if ($row3["alert_text"] != "")
					$string = $string."<td class='$tdcolor'>".$lang_label["text"];
				else	
					$string = $string."<td class='$tdcolor'>".$mymin." / ".$mymax;
				$string = $string."<td class='$tdcolor'>".salida_limpia($row3["descripcion"]);
				$string = $string."<td class='$tdcolor'>";
			 	$id_grupo = dame_id_grupo($id_agente);
				if (give_acl($id_user, $id_grupo, "LW")==1){
					$string = $string."<a href='index.php?sec=gagente&
					sec2=godmode/agentes/configurar_agente&tab=alert&
					id_agente=".$id_agente."&delete_alert=".$row3["id_aam"]."'>
					<img src='images/cross.png' border=0 alt='".$lang_label["delete"]."'></a>  &nbsp; ";
					$string = $string."<a href='index.php?sec=gagente&
					sec2=godmode/agentes/configurar_agente&tab=alert&
					id_agente=".$id_agente."&update_alert=".$row3["id_aam"]."#alerts'>
					<img src='images/config.gif' border=0 alt='".$lang_label["update"]."'></a>";		
				}
				$string = $string."</td>";
			}
		}
		if (isset($string) & $string!='') {
		echo "<table cellpadding='3' cellspacing='3' width='700' class='fon'>
		<tr><th>".$lang_label["name_type"]."</th>
		<th>".$lang_label["alert"]."</th>
		<th>".$lang_label["time_threshold"]."</th>
		<th>".$lang_label["min_max"]."</th>
		<th>".$lang_label["description"]."</th>
		<th width='50'>".$lang_label["action"]."</th></tr>";
		echo $string;
		echo "<tr><td colspan='6'><div class='raya'></div></td></tr></table>";
		} else {
			echo "<div class='nf'>".$lang_label["no_alerts"]."</div>";
		}
	} else {
		echo "<div class='nf'>".$lang_label["no_modules"]."</div>";
	}
?>

<h3><?php echo $lang_label["alert_asociation_form"] ?><a href='help/<?php echo $help_code ?>/chap3.php#3222' target='_help' class='help'>&nbsp;<span><?php echo $lang_label["help"] ?></span></a></h3>


<?php
// ==================================================================================
// Add alerts
// ==================================================================================
echo '<form name="agente" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'">';
if (! isset($update_alert))
	$update_alert = -1;
if ($update_alert != 1) {
	echo '<input type="hidden" name="insert_alert" value=1>';
} else {
	echo '<input type="hidden" name="update_alert" value=1>';
	echo '<input type="hidden" name="id_aam" value="'.$alerta_id_aam.'"';
}
?>
<input type="hidden" name="id_agente" value="<?php echo $id_agente ?>">
<table width=600 cellpadding="4" cellspacing="4" class="fon" border=0>
<tr><td class='lb' rowspan='10' width='3'>
<td class="datos"><?php echo $lang_label["alert_type"]?>
<td class="datos">
<select name="tipo_alerta"> 
<?php
if (isset($tipo_alerta)){
	echo "<option value='".$tipo_alerta."'>".dame_nombre_alerta($tipo_alerta)."</option>";
}
$sql1='SELECT id_alerta, nombre FROM talerta ORDER BY nombre';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	echo "<option value='".$row["id_alerta"]."'>".$row["nombre"]."</option>";
}
?>
</select>
<a name="alerts"> <!-- Don't Delete !! -->

<tr><td class="datos2"><?php echo $lang_label["min_value"] ?>
<td class="datos2"><input type="text" name="minimo" size="5" value="<?php echo $alerta_dis_min ?>" style="margin-right: 70px;">
<?php echo $lang_label["max_value"] ?> &nbsp;&nbsp;&nbsp;
<input type="text" name="maximo" size="5" value="<?php echo $alerta_dis_max ?>">

<!-- FREE TEXT ALERT -->
<tr><td class="datos"><?php echo $lang_label["alert_text"] ?> <a href='#' class='tip'>&nbsp;<span>
Regular Expression Supported
</span></a>
<td class="datos"><input type="text" name="alert_text" size="39" value ="<?php echo $alert_text ?>">

<tr><td class="datos2"><?php echo $lang_label["description"] ?>
<td class="datos2"><input type="text" name="descripcion" size="39" value ="<?php echo $alerta_descripcion ?>">

<tr><td class="datos"><?php echo $lang_label["field1"] ?> <a href='#' class='tip'>&nbsp;<span>
<b>Macros:</b><br>
_agent_<br>
_timestamp_<br>
_data_<br>
</span></a>
<td class="datos"><input type="text" name="campo_1" size="39" value="<?php echo $alerta_campo1 ?>">
<tr><td class="datos2"><?php echo $lang_label["field2"] ?> <a href='#' class='tip'>&nbsp;<span>
<b>Macros:</b><br>
_agent_<br>
_timestamp_<br>
_data_<br>
</span></a>
<td class="datos2"><input type="text" name="campo_2" size="39" value="<?php echo $alerta_campo2 ?>">
<tr><td class="datos"><?php echo $lang_label["field3"] ?> <a href='#' class='tip'>&nbsp;<span>
<b>Macros:</b><br>
_agent_<br>
_timestamp_<br>
_data_<br>
</span></a>
<td class="datos"><textarea name="campo_3" style='height:55px;' cols="36" rows="2"><?php echo $alerta_campo3 ?></textarea>
<tr><td class="datos2"><?php echo $lang_label["time_threshold"] ?>
<td class="datos2">
<select name="time_threshold" style="margin-right: 60px;">
<?php
	if ($alerta_time_threshold != ""){ 
		echo "<option value='".$alerta_time_threshold."'>".give_human_time($alerta_time_threshold)."</option>";
	}
?>
<option value=300>5 Min.</option>
<option value=600>10 Min.</option>
<option value=900>15 Min.</option>
<option value=1800>30 Min.</option>
<option value=3600>1 Hour</option>
<option value=7200>2 Hour</option>
<option value=18000>5 Hour</option>
<option value=43200>12 Hour</option>
<option value=86400>1 Day</option>
<option value=604800>1 Week</option>
<option value=-1>Other value</option>
</select>

<?php echo $lang_label["other"] ?>
&nbsp;&nbsp;&nbsp;
<input type="text" name="other" size="5">

<tr><td class="datos"><?php echo $lang_label["min_alerts"] ?>
<td class="datos">
<input type="text" name="min_alerts" size="5" value="<?php  if (isset($alerta_min_alerts)) {echo$alerta_min_alerts;} ?>" style="margin-right: 10px;">
<?php echo $lang_label["max_alerts"] ?>
&nbsp;&nbsp;&nbsp;
<input type="text" name="max_alerts" size="5" value="<?php if (isset($alerta_max_alerts)) {echo $alerta_max_alerts;} ?>">


<tr><td class="datos2"><?php echo $lang_label["assigned_module"] ?>
<td class="datos2">
<?php

if ($update_alert != 1) {
	echo '<select name="agente_modulo"> ';
	$sql2='SELECT id_agente_modulo, id_tipo_modulo, nombre FROM tagente_modulo WHERE id_agente = '.$id_agente;
	$result2=mysql_query($sql2);
	while ($row2=mysql_fetch_array($result2)){
		if ($row2["id_tipo_modulo"] != -1) {
			$sql1='SELECT nombre FROM ttipo_modulo WHERE id_tipo = '.$row2["id_tipo_modulo"];
			$result=mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
				echo "<option value='".$row2["id_agente_modulo"]."'>".$row["nombre"]."/".$row2["nombre"];
			}
		} else // for -1, is a special module, keep alive monitor !!
			echo "<option value='".$row2["id_agente_modulo"]."'>".$row2["nombre"]."</option>";
	}
	echo "</select>";
} else {
	echo "<span class='redi'>".$lang_label["no_change_field"]."</span>";
}

 // End block only if $creacion_agente != 1;

echo '<tr><td colspan="3"><div class="raya"></div></td></tr>';
echo '<tr><td colspan="3" align="right">';
	if ($update_alert== "1"){
		echo '<input name="updbutton" type="submit" class="sub upd" value="'.$lang_label["update"].'">';
	} else {
		echo '<input name="crtbutton" type="submit" class="sub wand" value="'.$lang_label["add"].'">';
	}
	echo '</form>';
echo '</td></tr></table>';

