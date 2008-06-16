<?PHP

// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Copyright (c) 2008 Jorge Gonzalez <jorge.gonzalez@artica.es>
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


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
if (isset($_GET["create_agent"])){
	$create_agent = 1;
	echo " &gt; ".$lang_label["create_agent"];
} else {
	echo " &gt; ".$lang_label["update_agent"];
}
echo "</h2>";
echo "<div style='height: 5px'> </div>";

// Agent remote configuration editor
$agent_md5 = md5($nombre_agente, FALSE);
if (isset($_GET["disk_conf"])){
	require ("agent_disk_conf_editor.php");
	exit;
}

echo '<form name="conf_agent" method="post" action="index.php?sec=gagente&
sec2=godmode/agentes/configurar_agente">';
if ($create_agent == 1) {
	echo "<input type='hidden' name='create_agent' value='1'>";
} else {
	echo "<input type='hidden' name='update_agent' value='1'>";
	echo "<input type='hidden' name='id_agente' value='".$id_agente."'>";
}
echo '<table width="650" cellpadding="4" cellspacing="4" class="databox_color">';
echo "<tr>";
echo '<td class="datos"><b>'.$lang_label["agent_name"].'</b></td>
<td class="datos">
<input type="text" name="agente" size=30 value="'.$nombre_agente.'">';

if ((isset($id_agente)) && ($id_agente != "")){
	echo "
	<a href='index.php?sec=estado&
	sec2=operation/agentes/ver_agente&id_agente=".$id_agente."'>
	<img src='images/lupa.png' border='0' align='middle' alt=''></a>";
} 
// Remote configuration available
if (file_exists($config["remote_config"] . "/" . $agent_md5 . ".md5")) {
	echo "
	<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente=".$id_agente."&disk_conf=" . $agent_md5 . "'>
	<img src='images/application_edit.png' border='0' align='middle' alt=''></a>";	
}

echo '<tr><td class="datos2">';
echo '<b>'.$lang_label["ip_address"].'</b>';
echo '<td class="datos2">';
echo '<input type="text" name="direccion" size="16" value="'.$direccion_agente.'">';

if ($create_agent != 1){
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";

	echo '<select name="address_list">';
	$sql1 = "SELECT * FROM taddress, taddress_agent
		WHERE taddress.id_a = taddress_agent.id_a
		AND   taddress_agent.id_agent = $id_agente";
	if ($result=mysql_query($sql1))
		while ($row=mysql_fetch_array($result)){
			echo "<option value='".salida_limpia($row["ip"])."'>".salida_limpia($row["ip"])."&nbsp;&nbsp;";
		}
	echo "</select>";

	echo "<input name='delete_ip' type=checkbox value='1'> ".$lang_label["delete_sel"];
	echo "</td>";
}



echo '<tr><td class="datos"><b>'.lang_string ("Parent").'</b>';
echo '<td class="datos">';
if ($create_agent != 1){
    form_agent_combo ($id_parent, "id_parent");
}
else
    form_agent_combo (0, "id_parent");


echo '<tr><td class="datos"><b>'.$lang_label["group"].'</b>';
echo '<td class="datos"><select name="grupo" class="w130">';
if (isset($grupo)){
echo "<option value='".$grupo."'>".dame_grupo($grupo)."</option>";
}
list_group ($id_user, 0);
echo "</select>";

echo "<tr><td class='datos2'>";
echo "<b>".lang_string("interval")."</b></td>";
echo '<td class="datos2">';

echo '<input type="text" name="intervalo" size="15" value="'.$intervalo.'"></td>';
echo '<tr><td class="datos"><b>'.lang_string("os").'</b></td>';
echo '<td class="datos">';
echo '<select name="id_os" class="w130">';

if (isset($id_os)){
	echo "<option value='".$id_os."'>".dame_so_name($id_os)."</option>";
}
$sql1='SELECT id_os, name FROM tconfig_os';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	echo "<option value='".$row["id_os"]."'>".$row["name"]."</option>";
}
?>
</select>

<?PHP

// Network server
echo '<tr><td class="datos2"><b>';
echo lang_string("Network server");
echo '</b></td><td class="datos2">';
echo '<select name="network_server" class="w130">';
echo "<option value='".$id_network_server."'>".give_server_name($id_network_server);
$sql1 = 'SELECT id_server, name FROM tserver where network_server = 1 ORDER BY name';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	echo "<option value='".$row["id_server"]."'>".$row["name"]."</option>";
}
echo '</select>';

// Plugin Server
echo '<tr><td class="datos"><b>';
echo lang_string("Plugin server");
echo '</b></td><td class="datos">';
echo '<select name="plugin_server" class="w130">';
echo "<option value='".$id_plugin_server."'>".give_server_name($id_plugin_server);
$sql1 = 'SELECT id_server, name FROM tserver where plugin_server = 1 ORDER BY name';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id_server"]."'>".$row["name"]."</option>";
}
echo '</select>';

// WMI Server
echo '<tr><td class="datos2"><b>';
echo lang_string("WMI server");
echo '</b></td><td class="datos2">';
echo '<select name="wmi_server" class="w130">';
echo "<option value='".$id_wmi_server."'>".give_server_name($id_wmi_server);
$sql1 = 'SELECT id_server, name FROM tserver where wmi_server = 1 ORDER BY name';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id_server"]."'>".$row["name"]."</option>";
}
echo '</select>';

// Prediction Server
echo '<tr><td class="datos"><b>';
echo lang_string("Prediction server");
echo '</b></td><td class="datos">';
echo '<select name="prediction_server" class="w130">';
echo "<option value='".$id_prediction_server."'>".give_server_name($id_prediction_server);
$sql1 = 'SELECT id_server, name FROM tserver where prediction_server = 1 ORDER BY name';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id_server"]."'>".$row["name"]."</option>";
}
echo '</select>';

// Description
echo '<tr><td class="datos2"><b>';
echo lang_string ("description");
echo '</b><td class="datos2">';
echo '<input type="text" name="comentarios" size="55" value="'.$comentarios.'"></td>';


// Learn mode / Normal mode 
echo '<tr><td class="datos"><b>';
echo lang_string ("module_definition");
echo '</b><td class="datos">';
if ($modo == "1"){
	echo $lang_label["learning_mode"].'
	<input type="radio" class="chk" name="modo" value="1" style="margin-right: 40px;" checked>';
	echo $lang_label["normal_mode"].' 
	<input type="radio" class="chk" name="modo" value="0">';
} else {
	echo $lang_label["learning_mode"].'
	<input type="radio" class="chk" name="modo" value="1" style="margin-right: 40px;">';
	echo $lang_label["normal_mode"].'
	<input type="radio" name="modo" class="chk" value="0" checked>';
}


// Status (Disabled / Enabled)
echo '<tr><td class="datos2"><b>'.lang_string("status").'</b>';
echo '<td class="datos2">';
if ($disabled == "1"){
	echo $lang_label["disabled"].'
	<input type="radio" class="chk" name="disabled" value="1" style="margin-right: 40px;" checked>';
	echo $lang_label["active"].' 
	<input class="chk" type="radio" name="disabled" value="0">';
} else {
	echo $lang_label["disabled"].'
	<input type="radio" class="chk" name="disabled" value="1" style="margin-right: 40px;">';
	echo $lang_label["active"].'
	<input type="radio" name="disabled" class="chk" value="0" checked>';
}

// Remote configuration
echo '<tr><td class="datos"><b>'.lang_string("Remote configuration").'</b>';
echo '<td class="datos">';
$filename = $config["remote_config"] . "/" . $agent_md5 . ".md5";
if (file_exists($filename)){
    echo date("F d Y H:i:s.", fileatime($filename));
} else {
    echo '<i>'.lang_string("Not available").'</i>';
}

echo '</table><table width="650"><tr><td  align="right">';
if ($create_agent == 1){
	echo "
	<input name='crtbutton' type='submit' class='sub wand' value='".
	$lang_label["create"]."'>";
} else {
	echo "
	<input name='uptbutton' type='submit' class='sub upd' value='".
	$lang_label["update"]."'>";
}
echo "</td></form></table>";

?>
