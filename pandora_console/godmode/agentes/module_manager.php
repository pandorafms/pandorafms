<?php
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development, project architecture and management.
// Copyright (c) 2004-2008 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP code additions
// Please see http://pandora.sourceforge.net for full contribution list
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

echo "<h2>".$lang_label["agent_conf"]." &gt; ".$lang_label["modules"]."</h2>"; 
$sql1='SELECT * FROM tagente_modulo WHERE id_agente = "'.$id_agente.'"
ORDER BY id_module_group, nombre ';
$result=mysql_query($sql1);
echo "<h3>".$lang_label["assigned_modules"]."&nbsp;<a href='#module_assignment' class='info'> <span>".$lang_label["module_asociation_form"]."</span><img src='images/wand.png'></a></h3>";
if ($row=mysql_num_rows($result)){
	echo '<table width="750" cellpadding="4" cellspacing="4" class="databox">';
	echo '<tr>';
	echo "<th>".$lang_label["module_name"]."</th>";
	echo "<th>".$lang_label["type"]."</th>";
	echo "<th>".$lang_label["interval"]."</th>";
	echo "<th>".$lang_label["description"]."</th>";
	echo "<th>".$lang_label["module_group"]."</th>";
	echo "<th>".$lang_label["max_min"]."</th>";
	echo "<th width=65>".$lang_label["action"]."</th>";
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
			echo "<tr><td class='datos3' align='center' colspan='9'><b>".$nombre_grupomodulo."</b></td></tr>";
		}

		echo "<tr><td class='".$tdcolor."_id'>".$nombre_modulo."</td>";
		echo "<td class='".$tdcolor."f9'>";
		if ($id_tipo > 0) {
			echo "<img src='images/".show_icon_type($id_tipo)."' border=0>";
		}
		echo "</td>";
		if ($module_interval2!=0){
			echo "<td class='$tdcolor'>".$module_interval2."</td>";
		} else {
			echo "<td class='$tdcolor'> N/A </td>";
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
		echo "</td>";
		echo "<td class='$tdcolor'>";
		if ($id_tipo != -1){
			echo "<a href='index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente&delete_module=".$row["id_agente_modulo"]."'".' onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;">';
			echo "<img src='images/cross.png' border=0 title='".$lang_label["delete"]."'>";
			echo "</b></a>&nbsp;";
			echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente&tab=module&update_module=".$row["id_agente_modulo"]."#modules'>";
			echo "<img src='images/config.png' border=0 title='".$lang_label["update"]."' onLoad='type_change()'></b></a>";
		}
		// Value arithmetical media fix
		if (($id_tipo != 3) AND ($id_tipo != 10) AND ($id_tipo != 17)){
			echo "&nbsp;";
			echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente&tab=module&fix_module=".$row["id_agente_modulo"]."'".' onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;">';
			echo "<img src='images/chart_curve.png' border=0 title='Normalize'></b></a>";
		}
	}
	echo "</table>";
} else
	echo "<div class='nf'>No modules</div>";

// ==========================
// Module assignment form
// ==========================

echo "<a name='module_assignment'></a>";
echo "<h3>".$lang_label["module_asociation_form"]."</h3>";

if ($update_module == "1")
	echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'">';
else // equal than previous, but without #module_assigment
	echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'#module_assignment">';

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
// Dont Delete  this <a name>!!
echo '<a name="modules"></a>';
echo '<table width="700" cellpadding="4" cellspacing="4" class="databox_color">';

echo '<tr><td class="datos3">';
echo $lang_label["network_component"];
if ($update_module != "1"){
	echo "<td class='datos3' colspan=2>";
	echo '<select name="nc">';
	$sql1='SELECT * FROM tnetwork_component ORDER BY name';
	$result=mysql_query($sql1);
	echo "<option value=-1>---".$lang_label["manual_config"]."---</option>";
	while ($row=mysql_fetch_array($result)){
		echo "<option value='".$row["id_nc"]."'>".substr($row["name"],0,20)." / ".substr($row["description"],0,20)."</option>";
	}
	echo "</select></td>";
	echo '<td class="datos3"><input align="right" name="updbutton" type="submit" class="sub" value="'.$lang_label["update"].'">';
} else {
	echo "<td class='datos3' colspan=3 align='left'>";
	echo "<span class='redi'>";
	echo $lang_label["not_available_in_edit_mode"];
	echo "</span></td>";
}

echo '</tr><tr>';
echo '<td class="datos2">'.$lang_label["module_name"]."</td>";
echo '<td class="datos2"><input type="text" name="nombre" size="25" value="'.$modulo_nombre.'"></td>';
echo '<td class="datos2">'.$lang_label["ip_target"]."</td>";
echo '<td class="datos2"><input type="text" name="ip_target" size="25" value="'.$ip_target.'"></td>';
echo '</tr>';

echo "<tr>";
echo "<td colspan='4'>";

//-- Module type combobox
echo "<tr>";
echo "<td class='datos'>".$lang_label["module_type"]."</td>";
echo "<td class='datos'>";
if ($update_module == "1") {
	echo "<input type='hidden' name='tipo' value='".$modulo_id_tipo_modulo."'>";
	echo "<span class='redi'>".$lang_label["not_available_in_edit_mode"]."</span>";
} else {
	echo '<select name="tipo" onChange="type_change()">';
	$sql1='SELECT id_tipo, nombre FROM ttipo_modulo ORDER BY nombre';
	$result=mysql_query($sql1);
	if (($update_module == "1") OR ($modulo_id_tipo_modulo != 0)) {
		echo "<option value='".$modulo_id_tipo_modulo ."'>".dame_nombre_tipo_modulo ($modulo_id_tipo_modulo)."</option>";
	}
	echo "<option>--</option>";
	while ($row=mysql_fetch_array($result)){
		echo "<option value='".$row["id_tipo"]."'>".$row["nombre"]."</option>";
	}
	echo "</select>";
}
?>

<!-- Module group selection -->
<td class="datos"><?php echo $lang_label["module_group"] ?></td>
<td class="datos">
<select name="id_module_group">
<?php
	if (($id_module_group != 0) OR ($update_module == "1")){
		echo "<option value='".$id_module_group."'>".dame_nombre_grupomodulo($id_module_group)."</option>";
	}
	$sql1='SELECT * FROM tmodule_group';
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		echo "<option value='".$row["id_mg"]."'>".$row["name"]."</option>";
	}
?>
</select>
</td></tr>

<tr>
<td class="datos2"><?php echo $lang_label["module_interval"] ?></td>
<td class="datos2">
<input type="text" name="module_interval" size="5" value="<?php echo $module_interval ?>"> 
</td>
<td class="datos2"><?php echo $lang_label["tcp_port"] ?></td>
<td class="datos2">
<input type="text" name="tcp_port" size="5" value="<?php echo $tcp_port ?>"> 
</td>
</tr>
<tr>
<td class="datos"><?php echo $lang_label["snmp_oid"] ?></td>
<td class="datos">
<input type="text" name="snmp_oid" size="25" value="<?php echo $snmp_oid ?>">
</td>
<td class="datos"><?php echo $lang_label["snmp_community"] ?></td>
<td class="datos">
<input type="text" name="snmp_community" size="25" value="<?php echo $snmp_community ?>">
</td>
</tr>
<tr><td class="datos2"><?php echo $lang_label["snmp_oid"] ?></td>
<td colspan='2' class="datos2">
<select name="combo_snmp_oid">
<?php
// FILL OID Combobox
if (isset($_POST["oid"])){
	for (reset($snmpwalk); $i = key($snmpwalk); next($snmpwalk)) {
		// OJO, al indice tengo que restarle uno, el SNMP funciona con indices a partir de 0
		// y el cabron de PHP me devuelve indices a partir de 1 !!!!!!!
   		//echo "$i: $a[$i]<br />\n";
		$snmp_output = substr($i,0,35)." - ".substr($snmpwalk[$i],0,20);
		echo "<option value=$i>".salida_limpia(substr($snmp_output,0,55))."</option>";
	}
}
?>
</select></td>
<td class="datos2">
<input type="submit" class="sub next" name="oid" value="SNMP Walk">
</td>
</tr>

<tr><td class="datost"><?php echo $lang_label["tcp_send"] ?></td>
<td class="datos">
<textarea name="tcp_send" cols="22" rows="2"><?php echo $tcp_send ?></textarea>
</td>
<td class="datost"><?php echo $lang_label["tcp_rcv"] ?></td>
<td class="datos">
<textarea name="tcp_rcv" cols="22" rows="2"><?php echo $tcp_rcv ?></textarea>
</td>
</tr>
<tr>
<td class="datos2"><?php echo $lang_label["mindata"] ?></td>
<td class="datos2">
<input type="text" name="modulo_min" size="5" value="<?php echo $modulo_min ?>">
</td>
<td class="datos2"><?php echo $lang_label["maxdata"] ?></td>
<td class="datos2">
<input type="text" name="modulo_max" size="5" value="<?php echo $modulo_max ?>">
</td>
</tr>
<tr><td class="datost"><?php echo $lang_label["comments"] ?></td>
<td class="datos" colspan=3>
<textarea name="descripcion" cols=71 rows=2>
<?php echo $modulo_descripcion ?>
</textarea></td>

</tr></table>
<table width='700'>
<tr><td align='right'>
<?php
	if ($update_module == "1"){
		echo '<input name="updbutton" type="submit" class="sub next" value="'.$lang_label["update"].'">';
	} else {
		echo '<input name="crtbutton" type="submit" class="sub wand" value="'.$lang_label["add"].'">';
	}
?>
</form></td></tr>
</table>
