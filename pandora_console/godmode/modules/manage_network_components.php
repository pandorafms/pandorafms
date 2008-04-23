<?PHP
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require("include/config.php");
if (comprueba_login() == 0)
  	$id_user = $_SESSION["id_usuario"];
else
	$id_user = "";

if (give_acl($id_user, 0, "PM")!=1) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
	"Trying to access Agent Management");
	require ("general/noaccess.php");
	exit;
}

$type = "0";
$name = "";
$description = "";
$modulo_max="0";
$modulo_min="0";
$tcp_send="";
$tcp_rcv="";
$tcp_port="";
$snmp_oid="";
$snmp_community="";
$id_module_group="";
$module_interval="";
$id_group = "";

// ------------------
// CREATE MODULE
// ------------------

if (isset($_GET["create"])){ // Create module
	if (isset($_POST["tipo"]))
		$type = entrada_limpia($_POST["tipo"]);
	if (isset($_POST["name"]))
		$name =  entrada_limpia($_POST["name"]);
	if (isset($_POST["descripcion"]))
		$description = entrada_limpia($_POST["descripcion"]);
	if (isset($_POST["modulo_max"]))
		$modulo_max = entrada_limpia($_POST["modulo_max"]);
	if (isset($_POST["modulo_min"]))
		$modulo_min = entrada_limpia($_POST["modulo_min"]);
	if (isset($_POST["tcp_send"]))
		$tcp_send = entrada_limpia($_POST["tcp_send"]);
	if (isset($_POST["tcp_rcv"]))
		$tcp_rcv = entrada_limpia($_POST["tcp_rcv"]);
	if (isset($_POST["tcp_port"]))
		$tcp_port = entrada_limpia($_POST["tcp_port"]);
	if (isset($_POST["snmp_oid"]))
		$snmp_oid = entrada_limpia($_POST["snmp_oid"]);
	if (isset($_POST["snmp_community"]))
		$snmp_community = entrada_limpia($_POST["snmp_community"]);
	if (isset($_POST["id_module_group"]))
		$id_module_group = entrada_limpia($_POST["id_module_group"]);
	if (isset($_POST["module_interval"]))
		$module_interval = entrada_limpia($_POST["module_interval"]);
	if (isset($_POST["id_group"]))
		$id_group = entrada_limpia($_POST["id_group"]);


	
	$sql_insert="INSERT INTO tnetwork_component (name, description, module_interval, type, max, min, tcp_send, tcp_rcv, tcp_port, snmp_oid, snmp_community, id_module_group, id_group)
	VALUES ('$name', '$description', '$module_interval', '$type', '$modulo_max', '$modulo_min', '$tcp_send', '$tcp_rcv', '$tcp_port', '$snmp_oid' ,'$snmp_community', '$id_module_group', '$id_group')";
	$result=mysql_query($sql_insert);
	if (! $result)
		echo "<h3 class='error'>".$lang_label["create_no"]."</h3>";
	else {
		echo "<h3 class='suc'>".$lang_label["create_ok"]."</h3>";
		$id_module = mysql_insert_id();
	}
}

// ------------------
// UPDATE MODULE
// ------------------
if (isset($_GET["update"])){ // if modified any parameter
	$id_nc = entrada_limpia ($_GET["id_nc"]);

	if (isset($_POST["tipo"]))
		$type = entrada_limpia($_POST["tipo"]);
	if (isset($_POST["name"]))
		$name =  entrada_limpia($_POST["name"]);
	if (isset($_POST["descripcion"]))
		$description = entrada_limpia($_POST["descripcion"]);
	if (isset($_POST["modulo_max"]))
		$modulo_max = entrada_limpia($_POST["modulo_max"]);
		if ($modulo_max == "")
			$modulo_max = 0;
	if (isset($_POST["modulo_min"]))
		$modulo_min = entrada_limpia($_POST["modulo_min"]);
		if ($modulo_min == "")
			$modulo_min = 0;
	if (isset($_POST["tcp_send"]))
		$tcp_send = entrada_limpia($_POST["tcp_send"]);
	if (isset($_POST["tcp_rcv"]))
		$tcp_rcv = entrada_limpia($_POST["tcp_rcv"]);
	if (isset($_POST["tcp_port"]))
		$tcp_port = entrada_limpia($_POST["tcp_port"]);
	if (isset($_POST["snmp_oid"]))
		$snmp_oid = entrada_limpia($_POST["snmp_oid"]);
	if (isset($_POST["snmp_community"]))
		$snmp_community = entrada_limpia($_POST["snmp_community"]);
	if (isset($_POST["id_module_group"]))
		$id_module_group = entrada_limpia($_POST["id_module_group"]);
	if (isset($_POST["module_interval"]))
		$module_interval = entrada_limpia($_POST["module_interval"]);
	if (isset($_POST["id_group"]))
		$id_group = entrada_limpia($_POST["id_group"]);

	$sql_update ="UPDATE tnetwork_component	SET name = '$name',
	description = '$description', snmp_oid = '$snmp_oid', snmp_community = '$snmp_community',
	id_group = '$id_group', tcp_rcv = '$tcp_rcv', tcp_send = '$tcp_send', max = '$modulo_max',
	min = '$modulo_min', tcp_port = '$tcp_port', id_module_group = '$id_module_group', type = '$type',
	module_interval = '$module_interval' WHERE id_nc = '$id_nc'";
	$result=mysql_query($sql_update);
	if (! $result)
		echo "<h3 class='error'>".$lang_label["modify_no"]."</h3>";
	else
		echo "<h3 class='suc'>".$lang_label["modify_ok"]."</h3>";
}

// ------------------
// DELETE MODULE
// ------------------
if (isset($_GET["delete"])){ // if delete
	$id_nc = entrada_limpia ($_GET["id_nc"]);
	$sql_delete= "DELETE FROM tnetwork_component WHERE id_nc = ".$id_nc;
	$result=mysql_query($sql_delete);
	if (! $result)
		echo "<h3 class='error'>".$lang_label["delete_no"]."</h3>";
	else
		echo "<h3 class='suc'>".$lang_label["delete_ok"]."</h3>";
	$sql_delete= "DELETE FROM tnetwork_profile_component WHERE id_nc = ".$id_nc;
	$result=mysql_query($sql_delete);
}

// ------------------
// SHOW MODULES
// ------------------
echo "<h2>".$lang_label["module_management"]." &gt; ";
echo $lang_label["network_component_management"]."</h2>";

// Show group selector
if (isset($_POST["ncgroup"]))
	$ncgroup = $_POST["ncgroup"];
else
	$ncgroup = 0;
echo "<form method='POST' action='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components'>";

echo "<table cellpadding='4' cellspacing='4' class='databox'>";
echo "<tr><td colspan='2' valign='top'>";
echo "<h3>".$lang_label["filter"]."</h3></td></tr>";
echo "<tr><td>".$lang_label["group"]."</td>";
echo "<td valign='middle'>";
echo "<select name='ncgroup' onChange='javascript:this.form.submit();'>";

if ($ncgroup != 0){
	echo "<option value='$ncgroup'>".give_network_component_group_name($ncgroup)."</option>";
}
echo "<option value='0'>".$lang_label["all"]."</option>";
$sql1 = "SELECT * FROM tnetwork_component_group WHERE id_sg != '$ncgroup' ORDER BY name";
$result = mysql_query($sql1);
while ($row = mysql_fetch_array ($result))
	echo "<option value='" . $row["id_sg"] . "'>". give_network_component_group_name ($row["id_sg"])."</option>";
echo "</select>";
echo "<td valign='middle'>";
echo "<noscript><input name='uptbutton' type='submit' class='sub' value='".$lang_label["show"]."'></noscript>";
echo "</td></form></table><br>";

if ($ncgroup != 0)
	$sql1 = "SELECT * FROM tnetwork_component WHERE id_group = $ncgroup ORDER BY name";
else
	$sql1 = "SELECT * FROM tnetwork_component ORDER BY id_group,name";
	
$result = mysql_query ($sql1);
if ( $row = mysql_num_rows ($result)){
	echo '<table width="750" cellpadding="4" cellspacing="4" class="databox">';
	echo '<tr>';
	echo "<th>".$lang_label["module_name"]."</th>";
	echo "<th>".$lang_label["type"]."</th>";
	echo "<th>".$lang_label["interval"]."</th>";
	echo "<th>".$lang_label["description"]."</th>";
	echo "<th>".$lang_label["nc.group"]."</th>";
	//echo "<th>".$lang_label["module_group"];
	echo "<th>".$lang_label["max_min"]."</th>";
	echo "<th width=50>".$lang_label["action"]."</th>";
	$color=1;
	while ($row=mysql_fetch_array($result)){
		if ($color == 1){
			$tdcolor="datos";
			$color =0;
		} else {
			$tdcolor="datos2";
			$color =1;
		}
		$id_tipo = $row["type"];
		$id_group = $row["id_group"];
		$nombre_modulo =$row["name"];
		$descripcion = $row["description"];
		$module_max = $row["max"];
		$module_min = $row["min"];
		$module_interval2 = $row["module_interval"];
		$module_group2 = $row["id_module_group"];

		echo "<tr><td class='".$tdcolor."_id'>";
		echo "<a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components_form&update=1&id_nc=".$row["id_nc"]."'>".$nombre_modulo."</a></td>";
		echo "<td class='".$tdcolor."f9'>";
		if ($id_tipo > 0) {
			echo "<img src='images/".show_icon_type($id_tipo)."' border='0'>";
		}
		if ($module_interval2!=0){
			echo "<td class='$tdcolor'>".$module_interval2;
		} else {
			echo "<td class='$tdcolor'> N/A";
		}
		echo "</td>";
		echo "<td class='$tdcolor'>".substr($descripcion,0,30)."</td>";
		echo "<td class='$tdcolor'>".give_network_component_group_name($id_group)."</td>";
		//echo "<td class='$tdcolor'>".
		//substr(dame_nombre_grupomodulo($module_group2),0,15)."</td>";
		echo "<td class='$tdcolor'>";
		if ($module_max == $module_min) {
			$module_max = "N/A";
			$module_min = "N/A";
		}
		echo $module_max." / ".$module_min;
		echo "<td class='$tdcolor' align='center'>";
		echo "<a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components&delete=1&id_nc=".$row["id_nc"]."'>";
		echo "<img src='images/cross.png' border=0 alt='".$lang_label["delete"]."'></a></td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<table width='750px'>";
	echo "<tr><td align='right'>";
	echo "<form method=post action='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components_form&create=1'>";
	echo "<input type='submit' class='sub next' name='crt' value='".$lang_label["create"]."'>";
	echo "</form></td></tr></table>";

} else
	echo "<div class='nf'>No modules</div>";

?>
