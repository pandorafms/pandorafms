<?PHP

// ========================
// AGENT GENERAL DATA FORM 
// ========================
// Load global vars
require("include/config.php");

if (give_acl($id_user, 0, "AW")!=1) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
	require ("general/noaccess.php");
	exit;
};

echo "<h2>".$lang_label["agent_conf"];
if (isset($_GET["creacion"])){
echo " &gt; ".$lang_label["create_agent"]."
<a href='help/".$help_code."/chap3.php#32' target='_help' class='help'>
&nbsp;<span>".$lang_label["help"]."</span></a>";
} else {
	echo " &gt; ".$lang_label["update_agent"]."
	<a href='help/".$help_code."/chap3.php#32' target='_help' class='help'>
	&nbsp;<span>".$lang_label["help"]."</span></a>";
}
echo "</h2>";
echo "<div style='height: 25px'> </div>";

echo '<form name="conf_agent" method="post" action="index.php?sec=gagente&
sec2=godmode/agentes/configurar_agente">';
if ($creacion_agente == 1) {
	echo "<input type='hidden' name='create_agent' value='1'>";
} else {
	echo "<input type='hidden' name='update_agent' value='1'>";
	echo "<input type='hidden' name='id_agente' value='".$id_agente."'>";
}
echo '<table width="650" cellpadding="3" cellspacing="3" class="fon">';
echo "<tr><td class='lb' rowspan='9' width='1'>";
echo '<td class="datos"><b>'.$lang_label["agent_name"].'</b></td>
<td class="datos">
<input type="text" name="agente" size=30 value="'.$nombre_agente.'">';
if (isset($_GET["creacion"])){
	echo "&nbsp;";
} else {
	echo "
	<a href='index.php?sec=estado&
	sec2=operation/agentes/ver_agente&id_agente=".$id_agente."'>
	<img src='images/lupa.gif' border='0' align='middle'></a>";
} 
?>
<tr><td class="datos2"><b><?php echo $lang_label["ip_address"]?></b>
<td class="datos2">
<input type="text" name="direccion" size="30" value="
<?php echo $direccion_agente ?>"></td>
<!-- Combo for group -->
<tr><td class="datos"><b><?php echo $lang_label["group"]?></b>
<td class="datos"><select name="grupo" class="w130"> 
<?php
if (isset($grupo)){
echo "<option value='".$grupo."'>".dame_grupo($grupo);
}
$sql1='SELECT id_grupo, nombre FROM tgrupo ORDER BY nombre';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	// Group ALL cannot be selected
	if ($row["id_grupo"] != 1){
		echo "<option value='".$row["id_grupo"]."'>".$row["nombre"];
	}
}
?>
</select>
<tr><td class="datos2"><b><?php echo $lang_label["interval"]?></b></td>
<td class="datos2">
<input type="text" name="intervalo" size="15" value="
<?php echo $intervalo?>"></td>
<tr><td class="datos"><b><?php echo $lang_label["os"]?></b></td>
<td class="datos">
<select name="id_os" class="w130">
<?php
if (isset($id_os)){
	echo "<option value='".$id_os."'>".dame_so_name($id_os);
}
$sql1='SELECT id_os, name FROM tconfig_os ORDER BY name';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	echo "<option value='".$row["id_os"]."'>".$row["name"];
}
?>
</select>

<tr><td class="datos2"><b><?php echo $lang_label["server"]?></b></td>
<td class="datos2">
<select name="id_server" class="w130">
<?php
echo "<option value='".$id_server."'>".give_server_name($id_server);
$sql1='SELECT id_server, name FROM tserver where network_server = 1 ORDER BY name';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	echo "<option value='".$row["id_server"]."'>".$row["name"];
}

?>
</select>


<tr><td class="datos"><b><?php echo $lang_label["description"]?></b>
<td class="datos">
<input type="text" name="comentarios" size="55" value="
<?php echo $comentarios ?>"></td>
<tr><td class="datos2"><b><?php echo $lang_label["module_definition"]?></b>
<td class="datos2">
	<?php if ($modo == "1"){
		echo $lang_label["learning_mode"].'
		<input type="radio" class="chk" name="modo" value="1" class="mr40" checked>';
		echo $lang_label["normal_mode"].' 
		<input type="radio" class="chk" name="modo" value="0">';
	} else {
		echo $lang_label["learning_mode"].'
		<input type="radio" class="chk" name="modo" value="1" class="mr40">';
		echo $lang_label["normal_mode"].'
		<input type="radio" name="modo" class="chk" value="0" checked>';
	}
	?>
<tr><td class="datos"><b><?php echo $lang_label["status"]?></b>
<td class="datos">
<?php if ($disabled == "1"){
		echo $lang_label["disabled"].'
		<input type="radio" class="chk" name="disabled" value="1" class="mr40" checked>';
		echo $lang_label["active"].' 
		<input class="chk" type="radio" name="disabled" value="0">';
	} else {
		echo $lang_label["disabled"].'
		<input type="radio" class="chk" name="disabled" value="1" class="mr40">';
		echo $lang_label["active"].'
		<input type="radio" name="disabled" class="chk" value="0" checked>';
	}
?>
<tr><td colspan='3'><div class='raya'></div></td></tr>
<tr><td colspan="3" align="right">
<?php 
if (isset($_GET["creacion"])){
	echo "
	<input name='crtbutton' type='submit' class='sub' value='".
	$lang_label["create"]."'>";
} else {
	echo "
	<input name='uptbutton' type='submit' class='sub' value='".
	$lang_label["update"]."'>";
}
?>
</td>
</form>
</table>
