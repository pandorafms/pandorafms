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

// Module visualization where in create mode
// MODULE VISUALIZATION
// ======================
if ( $creacion_agente != 1) {
	$sql1='SELECT * FROM tagente_modulo WHERE id_agente = "'.$id_agente.'" 
	ORDER BY nombre';
	$result=mysql_query($sql1);
	if ($row=mysql_num_rows($result)){
		?>
		<h3><?php echo $lang_label["assigned_modules"]?>
		<a href='help/<?php echo $help_code ?>/chap3.php#321' target='_help' class='help'>
		<span><?php echo $lang_label["help"] ?></span></a></h3>
		<table width="700" cellpadding="3" cellspacing="3" class="fon">
		<tr>
		<th><?php echo $lang_label["module_name"]?>
		<th><?php echo $lang_label["type"]?>
		<th><?php echo $lang_label["interval"]?>
		<th><?php echo $lang_label["description"]?>
		<th><?php echo $lang_label["module_group"]?>
		<th><?php echo $lang_label["max_min"]?>
		<th width="50"><?php echo $lang_label["action"]?>
		<?php
		$color=1;
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
			if ($id_tipo != -1)
			echo "<a href='index.php?sec=gagente&
			sec2=godmode/agentes/configurar_agente&
			id_agente=".$id_agente."&
			delete_module=".$row["id_agente_modulo"]."'>
			<img src='images/cancel.gif' border=0 alt='".$lang_label["delete"]."'>
			</b></a> &nbsp; ";
			echo "<a href='index.php?sec=gagente&
			sec2=godmode/agentes/configurar_agente&
			id_agente=".$id_agente."&
			update_module=".$row["id_agente_modulo"]."#modules'>
			<img src='images/config.gif' border=0 alt='".$lang_label["update"]."' onLoad='type_change()'></b></a>";
		}
		echo "<tr><td colspan='7'><div class='raya'></div></td></tr>";
	} else 
		echo "<div class='nf'>No modules</div>";
}
// ====================================================================================
// Module Creation / Update form
// ====================================================================================
else {

	echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'">';
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
			$module_interval = $intervalo;
		}
	}
}
?>

<h3><?php echo $lang_label["module_asociation_form"] ?>
<a href='help/<?php echo $help_code ?>/chap3.php#321' target='_help' class='help'>
&nbsp;<span><?php echo $lang_label["help"] ?></span></a></h3>
<a name="modules"> <!-- Don't Delete !! -->
<table width="650" cellpadding="3" cellspacing="3" class="fon">
<tr><td class='lb' rowspan='8' width='5'>
<!-- Module type combobox -->
<td class="datos"><?php echo $lang_label["module_type"] ?>
<td class="datos">
<?php
if ($update_module == "1") {
	echo "<input type='hidden' name='tipo' value='".$modulo_id_tipo_modulo."'>";
	echo "<span class='redi'>".$lang_label["no_change_field"]."</span>";
} else {
	echo '<select name="tipo" onChange="type_change()">';
	$sql1='SELECT id_tipo, nombre FROM ttipo_modulo ORDER BY nombre';
	$result=mysql_query($sql1);
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
	if ($update_module == "1"){
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

<tr><td class="datos2"><?php echo $lang_label["module_name"] ?>
<td class="datos2"><input type="text" name="nombre" size="20" value="<?php echo $modulo_nombre ?>"> 
<td class="datos2"><?php echo $lang_label["module_interval"] ?><td class="datos2">
<input type="text" name="module_interval" size="5" value="<?php echo $module_interval ?>"> 

<tr><td class="datos"><?php echo $lang_label["ip_target"] ?>
<td class="datos"><input type="text" name="ip_target" size="20" value="<?php echo $ip_target ?>"> 
<td class="datos"><?php echo $lang_label["tcp_port"] ?>
<td class="datos"><input type="text" name="tcp_port" size="5" value="<?php echo $tcp_port ?>"> 

<tr><td class="datos2"><?php echo $lang_label["snmp_oid"] ?>
<td class="datos2"><input type="text" name="snmp_oid" size="15" value="<?php echo $snmp_oid ?>"> 
<input type="submit" name="oid" value="Get Value">

<td class="datos2"><?php echo $lang_label["snmp_community"] ?>
<td class="datos2"><input type="text" name="snmp_community" size="20" value="<?php echo $snmp_community ?>"> 


<tr><td class="datos"><?php echo $lang_label["snmp_oid"] ?>
<td colspan=3 class="datos"><select name="combo_snmp_oid">
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

<tr><td class="datos2t"><?php echo $lang_label["tcp_send"] ?>
<td class="datos2"><textarea name="tcp_send" cols="17" rows="3"><?php echo $tcp_send ?></textarea>

<td class="datos2t"><?php echo $lang_label["tcp_rcv"] ?>
<td class="datos2"><textarea name="tcp_rcv" cols="17" rows="3"><?php echo $tcp_rcv ?></textarea>

<tr><td class="datos"><?php echo $lang_label["mindata"] ?>
<td class="datos"><input type="text" name="modulo_min" size="5" value="<?php echo $modulo_min ?>"> 
<td class="datos"><?php echo $lang_label["maxdata"] ?>
<td class="datos"><input type="text" name="modulo_max" size="5" value="<?php echo $modulo_max ?>">

<tr><td class="datos2t"><?php echo $lang_label["comments"] ?>
<td class="datos2" colspan=3>
<textarea name="descripcion" cols=52 rows=2>
<?php echo $modulo_descripcion ?>
</textarea>
<tr><td colspan='5'><div class='raya'></div></td></tr>
<tr><td colspan="5" align="right">
<?php 
	if ($update_module == "1"){
		echo '<input name="updbutton" type="submit" class="sub" value="'.$lang_label["update"].'">';
	} else {
		echo '<input name="crtbutton" type="submit" class="sub" value="'.$lang_label["add"].'">';
	}
	echo "</form>";
	echo "<form method='post' action='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=".$id_agente."'><input type='submit' class='sub' name='cancel' value='".$lang_label["cancel"]."'></form>";
?>

</table>

