<?PHP
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
?>
<script language="JavaScript" type="text/javascript">
<!--
function type_change()
{
	// type 1-4 - Generic_xxxxxx
	if ((document.modulo.tipo.value > 0) && (document.modulo.tipo.value < 5)){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;
		document.modulo.combo_snmp_oid.style.background="#ddd";
		document.modulo.combo_snmp_oid.disabled=true;
		document.modulo.oid.disabled=true;
		document.modulo.tcp_send.style.background="#ddd";
		document.modulo.tcp_send.disabled=true;
		document.modulo.tcp_rcv.style.background="#ddd";
		document.modulo.tcp_rcv.disabled=true;
		document.modulo.tcp_port.style.background="#ddd";
		document.modulo.tcp_port.disabled=true;
		document.modulo.ip_target.style.background="#ddd";
		document.modulo.ip_target.disabled=true;
		document.modulo.modulo_max.style.background="#fff";
		document.modulo.modulo_max.disabled=false;
		document.modulo.modulo_min.style.background="#fff";
		document.modulo.modulo_min.disabled=false;
	}
	// type 15-18- SNMP
	if ((document.modulo.tipo.value > 14) && (document.modulo.tipo.value < 19 )){
		document.modulo.snmp_oid.style.background="#fff";
		document.modulo.snmp_oid.style.disabled=false;
		document.modulo.snmp_community.style.background="#fff";
		document.modulo.snmp_community.disabled=false;
		document.modulo.combo_snmp_oid.style.background="#fff";
		document.modulo.combo_snmp_oid.disabled=false;
		document.modulo.oid.disabled=false;
		document.modulo.tcp_send.style.background="#ddd";
		document.modulo.tcp_send.disabled=true;
		document.modulo.tcp_rcv.style.background="#ddd";
		document.modulo.tcp_rcv.disabled=true;
		document.modulo.tcp_port.style.background="#ddd";
		document.modulo.tcp_port.disabled=true;
		document.modulo.ip_target.style.background="#fff";
		document.modulo.ip_target.disabled=false;
		if (document.modulo.tipo.value == 18) {
			document.modulo.modulo_max.style.background="#ddd";
			document.modulo.modulo_max.disabled=true;
			document.modulo.modulo_min.style.background="#ddd";
			document.modulo.modulo_min.disabled=true;
		} else {
			document.modulo.modulo_max.style.background="#fff";
			document.modulo.modulo_max.disabled=false;
			document.modulo.modulo_min.style.background="#fff";
			document.modulo.modulo_min.disabled=false;
		}
	}
	// type 6-7 - ICMP
	if ((document.modulo.tipo.value == 6) || (document.modulo.tipo.value == 7)){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;
		document.modulo.combo_snmp_oid.style.background="#ddd";
		document.modulo.combo_snmp_oid.disabled=true;
		document.modulo.oid.disabled=true;
		document.modulo.tcp_send.style.background="#ddd";
		document.modulo.tcp_send.disabled=true;
		document.modulo.tcp_rcv.style.background="#ddd";
		document.modulo.tcp_rcv.disabled=true;
		document.modulo.tcp_port.style.background="#ddd";
		document.modulo.tcp_port.disabled=true;
		document.modulo.ip_target.style.background="#fff";
		document.modulo.ip_target.disabled=false;
		document.modulo.modulo_max.style.background="#fff";
		document.modulo.modulo_max.disabled=false;
		document.modulo.modulo_min.style.background="#fff";
		document.modulo.modulo_min.disabled=false;
	}
	// type 8-11 - TCP
	if ((document.modulo.tipo.value > 7) && (document.modulo.tipo.value < 12)){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;
		document.modulo.combo_snmp_oid.style.background="#ddd";
		document.modulo.combo_snmp_oid.disabled=true;
		document.modulo.oid.disabled=true;
		document.modulo.tcp_send.style.background="#fff";
		document.modulo.tcp_send.disabled=false;
		document.modulo.tcp_rcv.style.background="#fff";
		document.modulo.tcp_rcv.disabled=false;
		document.modulo.tcp_port.style.background="#fff";
		document.modulo.tcp_port.disabled=false;
		document.modulo.ip_target.style.background="#fff";
		document.modulo.ip_target.disabled=false;
		document.modulo.modulo_max.style.background="#ddd";
		document.modulo.modulo_max.disabled=true;
		document.modulo.modulo_min.style.background="#ddd";
		document.modulo.modulo_min.disabled=true;
	}

	// type 12 - UDP
	if (document.modulo.tipo.value == 12){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;
		document.modulo.oid.disabled=true;
		document.modulo.tcp_send.style.background="#fff";
		document.modulo.tcp_send.disabled=false;
		document.modulo.tcp_rcv.style.background="#fff";
		document.modulo.tcp_rcv.disabled=false;
		document.modulo.tcp_port.style.background="#fff";
		document.modulo.tcp_port.disabled=false;
		document.modulo.ip_target.style.background="#fff";
		document.modulo.ip_target.disabled=false;
		document.modulo.modulo_max.style.background="#ddd";
		document.modulo.modulo_max.disabled=true;
		document.modulo.modulo_min.style.background="#ddd";
		document.modulo.modulo_min.disabled=true;
	}
}

//-->
</script>


<?PHP
// Load global vars
require("include/config.php");

if (give_acl($id_user, 0, "AW")!=1) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
	require ("general/noaccess.php");
	exit;
};

// ==========================
// MODULE VISUALIZATION TABLE
// ==========================

$sql1='SELECT * FROM tagente_modulo WHERE id_agente = "'.$id_agente.'"
ORDER BY id_module_group, nombre ';
$result=mysql_query($sql1);
echo "<h2>".$lang_label["agent_conf"]." &gt; ".$lang_label["assigned_modules"]."
	<a href='help/".$help_code."/chap3.php#321' target='_help' class='help'>
	<span>".$lang_label["help"]."</span></a>";
	echo "&nbsp;&nbsp;<a class='info' href='#module_assignment'> <span>".$lang_label["module_asociation_form"]."</span><img src='images/wand.png'></a>";
	echo "</h2>";
if ($row=mysql_num_rows($result)){
	echo '<table width="750" cellpadding="3" cellspacing="3" class="fon">';
	echo '<tr>';
	echo "<th>".$lang_label["module_name"];
	echo "<th>".$lang_label["type"];
	echo "<th>".$lang_label["interval"];
	echo "<th>".$lang_label["description"];
	echo "<th>".$lang_label["module_group"];
	echo "<th>".$lang_label["max_min"];
	echo "<th width=65>".$lang_label["action"];
	$color=1;$last_modulegroup = "0";
	while ($row=mysql_fetch_array($result)){
		if ($color == 1){
			$tdcolor="datos";
			$color =0;
		} else {
			$tdcolor="datos2";
			$color =1;
		}
		$id_tipo = $row["id_tipo_modulo"];
		$nombre_modulo =$row["nombre"];
		$descripcion = $row["descripcion"];
		$module_max = $row["max"];
		$module_min = $row["min"];
		$module_interval2 = $row["module_interval"];
		$module_group2 = $row["id_module_group"];
		if ($module_group2 != $last_modulegroup ){
			// Render module group names  (fixed code)
			$nombre_grupomodulo = dame_nombre_grupomodulo ($module_group2);
			$last_modulegroup = $module_group2;
			echo "<tr><td class='datos3' align='center' colspan=9><b>".$nombre_grupomodulo."</b>";
		}

		echo "<tr><td class='".$tdcolor."_id'>".$nombre_modulo;
		echo "<td class='".$tdcolor."f9'>";
		if ($id_tipo > 0) {
			echo "<img src='images/".show_icon_type($id_tipo)."' border=0>";
		}
		if ($module_interval2!=0){
			echo "<td class='$tdcolor'>".$module_interval2;
		} else {
			echo "<td class='$tdcolor'> N/A";
		}
		echo "<td class='$tdcolor' title='$descripcion'>".substr($descripcion,0,30)."</td>";
		echo "<td class='$tdcolor'>".
		substr(dame_nombre_grupomodulo($module_group2),0,15)."</td>";
		echo "<td class='$tdcolor'>";
		if ($module_max == $module_min) {
			$module_max = "N/A";
			$module_min = "N/A";
		}
		echo $module_max." / ".$module_min;
		echo "<td class='$tdcolor'>";
		if ($id_tipo != -1){
			echo "<a href='index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente&delete_module=".$row["id_agente_modulo"]."'".' onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;">';
			echo "<img src='images/cross.png' border=0 alt='".$lang_label["delete"]."'>";
			echo "</b></a>&nbsp;";
			echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente&tab=module&update_module=".$row["id_agente_modulo"]."#modules'>";
			echo "<img src='images/config.gif' border=0 alt='".$lang_label["update"]."' onLoad='type_change()'></b></a>";
		}
		// Value arithmetical media fix
		if (($id_tipo != 3) AND ($id_tipo != 10) AND ($id_tipo != 17)){
			echo "&nbsp;";
                        echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente&tab=module&fix_module=".$row["id_agente_modulo"]."'".' onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;">';
                        echo "<img src='images/chart_curve.png' border=0></b></a>";
		}
	}
	echo "<tr><td colspan='7'><div class='raya'></div></td></tr>";
	echo "</table>";
} else
	echo "<div class='nf'>No modules</div>";

// ==========================
// Module assignment form
// ==========================

echo "<a name='module_assignment'></a>";
echo "<h2>".$lang_label["agent_conf"]." &gt; ".$lang_label["module_asociation_form"]."<a href='help/".$help_code."/chap3.php#321' target='_help' class='help'>
&nbsp;<span>".$lang_label["help"]."</span></a></h2>";

if ($update_module == "1")
	echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'#module_assignment">';
else // equal than previous, but without #module_assigment
	echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'">';

if ($update_module == "1"){
	echo '<input type="hidden" name="update_module" value=1>';
	echo '<input type="hidden" name="id_agente_modulo" value="'.$id_agente_modulo.'">';
}
else { // Create
	echo '<input type="hidden" name="insert_module" value=1>';
	// Default values for new modules
	if ($ip_target == ""){
		$ip_target = $direccion_agente;
		$snmp_community = "public";
		if ($module_interval == 0)
			$module_interval = $intervalo;
	}
}

echo '<a name="modules"> <!-- Dont Delete !! -->';
echo '<table width="700" cellpadding="3" cellspacing="3" class="fon">';
echo "<tr><td class='lb' rowspan='11' width='1'>";

echo '<tr><td class="datos3">';
echo $lang_label["network_component"];
if ($update_module != "1"){
	echo "<td class='datos3' colspan=2>";
	echo '<select name="nc">';
	$sql1='SELECT * FROM tnetwork_component ORDER BY name';
	$result=mysql_query($sql1);
	echo "<option value=-1>---".$lang_label["manual_config"]."---</option>";
	while ($row=mysql_fetch_array($result)){
		echo "<option value='".$row["id_nc"]."'>".substr($row["name"],0,20)." / ".substr($row["description"],0,20);
	}
	echo "</select>";
	echo '<td class="datos3" <input align="right" name="updbutton" type="submit" class="sub" value="'.$lang_label["update"].'">';
} else {
	echo "<td class='datos3' colspan=3 align='left'>";
	echo "<span class='redi'>";
	echo $lang_label["not_available_in_edit_mode"];
	echo "</span>";
	
}

echo '<tr>';
echo '<td class="datos2">'.$lang_label["module_name"];
echo '<td class="datos2"><input type="text" name="nombre" size="25" value="'.$modulo_nombre.'">';
echo '<td class="datos2">'.$lang_label["ip_target"];
echo '<td class="datos2"><input type="text" name="ip_target" size="25" value="'.$ip_target.'">';

echo "<tr>";
echo "<td colspan='4'>";

//-- Module type combobox
echo "<tr>";
echo "<td class='datos'>".$lang_label["module_type"];
echo "<td class='datos'>";
if ($update_module == "1") {
	echo "<input type='hidden' name='tipo' value='".$modulo_id_tipo_modulo."'>";
	echo "<span class='redi'>".$lang_label["not_available_in_edit_mode"]."</span>";
} else {
	echo '<select name="tipo" onChange="type_change()">';
	$sql1='SELECT id_tipo, nombre FROM ttipo_modulo ORDER BY nombre';
	$result=mysql_query($sql1);
	if (($update_module == "1") OR ($modulo_id_tipo_modulo != 0)) {
		echo "<option value='".$modulo_id_tipo_modulo ."'>".dame_nombre_tipo_modulo ($modulo_id_tipo_modulo);
	}
	echo "<option>--</option>";
	while ($row=mysql_fetch_array($result)){
		echo "<option value='".$row["id_tipo"]."'>".$row["nombre"];
	}
	echo "</select>";
}
?>
</select>

<!-- Module group selection -->
<td class="datos"><?php echo $lang_label["module_group"] ?>
<td class="datos">
<?php
	echo '<select name="id_module_group">';
	if (($id_module_group != 0) OR ($update_module == "1")){
		echo "<option value='".$id_module_group."'>".dame_nombre_grupomodulo($id_module_group);
	}
	$sql1='SELECT * FROM tmodule_group';
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		echo "<option value='".$row["id_mg"]."'>".$row["name"];
	}
	echo "</select>";
?>
</select>

<tr>

<td class="datos2"><?php echo $lang_label["module_interval"] ?><td class="datos2">
<input type="text" name="module_interval" size="5" value="<?php echo $module_interval ?>"> 

<td class="datos2"><?php echo $lang_label["tcp_port"] ?>
<td class="datos2"><input type="text" name="tcp_port" size="5" value="<?php echo $tcp_port ?>"> 

<tr><td class="datos"><?php echo $lang_label["snmp_oid"] ?>
<td class="datos"><input type="text" name="snmp_oid" size="25" value="<?php echo $snmp_oid ?>">


<td class="datos"><?php echo $lang_label["snmp_community"] ?>
<td class="datos"><input type="text" name="snmp_community" size="25" value="<?php echo $snmp_community ?>">


<tr><td class="datos2"><?php echo $lang_label["snmp_oid"] ?>
<td colspan=2 class="datos2"><select name="combo_snmp_oid">
<?php
// FILL OID Combobox
if (isset($_POST["oid"])){
	for (reset($snmpwalk); $i = key($snmpwalk); next($snmpwalk)) {
		// OJO, al indice tengo que restarle uno, el SNMP funciona con indices a partir de 0
		// y el cabron de PHP me devuelve indices a partir de 1 !!!!!!!
   		//echo "$i: $a[$i]<br />\n";
		$snmp_output = substr($i,0,35)." - ".substr($snmpwalk[$i],0,20);
		echo "<option value=$i>".salida_limpia(substr($snmp_output,0,55));
	}
}
?>
</select>
<td class="datos2"><input type="submit" name="oid" value="SNMP Walk">

<tr><td class="datost"><?php echo $lang_label["tcp_send"] ?>
<td class="datos"><textarea name="tcp_send" cols="22" rows="2"><?php echo $tcp_send ?></textarea>

<td class="datost"><?php echo $lang_label["tcp_rcv"] ?>
<td class="datos"><textarea name="tcp_rcv" cols="22" rows="2"><?php echo $tcp_rcv ?></textarea>

<tr><td class="datos2"><?php echo $lang_label["mindata"] ?>
<td class="datos2"><input type="text" name="modulo_min" size="5" value="<?php echo $modulo_min ?>"> 
<td class="datos2"><?php echo $lang_label["maxdata"] ?>
<td class="datos2"><input type="text" name="modulo_max" size="5" value="<?php echo $modulo_max ?>">

<tr><td class="datost"><?php echo $lang_label["comments"] ?>
<td class="datos" colspan=3>
<textarea name="descripcion" cols=71 rows=2>
<?php echo $modulo_descripcion ?>
</textarea>
<tr><td colspan='5'><div class='raya'></div></td></tr>
<tr>
<?php
	echo "<td colspan=5 align='right'>";
	if ($update_module == "1"){
		echo '<input name="updbutton" type="submit" class="sub next" value="'.$lang_label["update"].'">';
	} else {
		echo '<input name="crtbutton" type="submit" class="sub wand" value="'.$lang_label["add"].'">';
	}
	echo "</form>";


?>

</table>

