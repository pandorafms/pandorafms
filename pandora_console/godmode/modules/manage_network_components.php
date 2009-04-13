<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
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


// Load global vars
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
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
$plugin_parameter = "";

// ------------------
// CREATE MODULE
// ------------------

if (isset($_GET["create"])){ // Create module
	$type = get_parameter ('tipo');
	$name = get_parameter ('name');
	$description = get_parameter ('descripcion');
	$modulo_max = get_parameter ('modulo_max', 0);
	$modulo_min = get_parameter ('modulo_min', 0);
	$tcp_send = get_parameter ('tcp_send');
	$tcp_rcv = get_parameter ('tcp_rcv');
	$tcp_port = get_parameter ('tcp_port');
	$snmp_oid = get_parameter ('snmp_oid');
	$snmp_community = get_parameter ('snmp_community');
	$id_module_group = get_parameter ('id_module_group');
	$module_interval = get_parameter ('module_interval');
	$id_group = get_parameter ('id_group');
	$plugin_user = get_parameter ('plugin_user');
	$plugin_pass = get_parameter ('plugin_pass');
	$plugin_parameter = get_parameter ('plugin_parameter');
	$max_timeout = get_parameter ('max_timeout');
	$id_modulo = get_parameter ('id_modulo');
	
	$sql = "INSERT INTO tnetwork_component (name, description, module_interval, type, max, min, tcp_send, tcp_rcv, tcp_port, snmp_oid, snmp_community, id_module_group, id_group, id_modulo, plugin_user, plugin_pass, plugin_parameter, max_timeout)
	VALUES ('$name', '$description', '$module_interval', '$type', '$modulo_max', '$modulo_min', '$tcp_send', '$tcp_rcv', '$tcp_port', '$snmp_oid' ,'$snmp_community', '$id_module_group', '$id_group', '$id_modulo', '$plugin_user', '$plugin_pass', '$plugin_parameter', '$max_timeout')";

	$id_module = process_sql ($sql, 'insert_id');
	if ($id_module === false)
		echo "<h3 class='error'>".__('Not created. Error inserting data')."</h3>";
	else {
		echo "<h3 class='suc'>".__('Created successfully')."</h3>";
	}
}

// ------------------
// UPDATE MODULE
// ------------------
if (isset($_GET["update"])){ // if modified any parameter
	$id_nc = get_parameter ('id_nc');

	$type = get_parameter ('tipo');
	$name = get_parameter ('name');
	$description = get_parameter ('descripcion');
	$modulo_max = get_parameter ('modulo_max', 0);
	$modulo_min = get_parameter ('modulo_min', 0);
	$tcp_send = get_parameter ('tcp_send');
	$tcp_rcv = get_parameter ('tcp_rcv');
	$tcp_port = get_parameter ('tcp_port');
	$snmp_oid = get_parameter ('snmp_oid');
	$snmp_community = get_parameter ('snmp_community');
	$id_module_group = get_parameter ('id_module_group');
	$module_interval = get_parameter ('module_interval');
	$id_group = get_parameter ('id_group');
	$plugin_user = get_parameter ('plugin_user');
	$plugin_pass = get_parameter ('plugin_pass');
	$plugin_parameter = get_parameter ('plugin_parameter');
	$max_timeout = get_parameter ('max_timeout');

	$sql ="UPDATE tnetwork_component	SET name = '$name',
	description = '$description', snmp_oid = '$snmp_oid', snmp_community = '$snmp_community',
	id_group = '$id_group', tcp_rcv = '$tcp_rcv', tcp_send = '$tcp_send', max = '$modulo_max',
	min = '$modulo_min', tcp_port = '$tcp_port', id_module_group = '$id_module_group', type = '$type',
	module_interval = '$module_interval', plugin_user = '$plugin_user',  plugin_pass = '$plugin_pass',
	plugin_parameter = '$plugin_parameter', max_timeout = '$max_timeout' WHERE id_nc = '$id_nc'";
	$result = process_sql ($sql);
	if (! $result)
		echo "<h3 class='error'>".__('Not updated. Error updating data')."</h3>";
	else
		echo "<h3 class='suc'>".__('Updated successfully')."</h3>";
}

// ------------------
// DELETE MODULE
// ------------------
if (isset($_GET["delete"])){ // if delete
	$id_nc = entrada_limpia ($_GET["id_nc"]);
	$sql= "DELETE FROM tnetwork_component WHERE id_nc = ".$id_nc;
	$result = process_sql ($sql);
	if (! $result)
		echo "<h3 class='error'>".__('Not deleted. Error deleting data')."</h3>";
	else
		echo "<h3 class='suc'>".__('Deleted successfully')."</h3>";
	$sql = "DELETE FROM tnetwork_profile_component WHERE id_nc = ".$id_nc;
	$result = process_sql ($sql);
}

// ------------------
// SHOW MODULES
// ------------------
echo "<h2>".__('Module management')." &raquo; ";
echo __('Module component management')."</h2>";

// Show group selector
if (isset($_POST["ncgroup"])) {
	$ncgroup = $_POST["ncgroup"];
} else {
	$ncgroup = 0;
}



echo "<table cellpadding='4' cellspacing='4' class='databox'>";
echo "<tr><td>";
echo "<form method='POST' action='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components'>";
echo __('Group') . "&nbsp;";
echo "<select name='ncgroup' onChange='javascript:this.form.submit();'>";
if ($ncgroup != 0){
	echo "<option value='$ncgroup'>".give_network_component_group_name($ncgroup)."</option>";
}
echo "<option value='0'>".__('All')."</option>";
$result = mysql_query("SELECT * FROM tnetwork_component_group WHERE id_sg != '$ncgroup' ORDER BY name");
while ($row = mysql_fetch_array ($result)) {
	echo "<option value='" . $row["id_sg"] . "'>". give_network_component_group_name ($row["id_sg"])."</option>";
}
echo "</select></form></td>";
echo "<td>";
echo "<form method=post action='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components_form&create=1'>";
echo "<select name='id_modulo'>";
echo "<option value='2'>".__('Create a new network component');
echo "<option value='6'>".__('Create a new WMI component');
echo "</select>&nbsp;";
echo "<input type='submit' class='sub next' name='crt' value='".__('Create')."'>";
echo "</td></tr></table>";

if ($ncgroup != 0) {
	$sql1 = "SELECT * FROM tnetwork_component WHERE id_group = $ncgroup ORDER BY name";
} else {
	$sql1 = "SELECT * FROM tnetwork_component ORDER BY id_group,name";
}
	
$result = mysql_query ($sql1);
if ( $row = mysql_num_rows ($result)){
	echo '<table width="90%" cellpadding="4" cellspacing="4" class="databox">';
	echo '<tr>';
	echo "<th>".__('Module name')."</th>";
	echo "<th>".__('Type')."</th>";
	echo "<th>".__('Interval')."</th>";
	echo "<th>".__('Description')."</th>";
	echo "<th>".__('NC.Group')."</th>";
	//echo "<th>".__('Module group');
	echo "<th>".__('Max/Min')."</th>";
	echo "<th width=50>".__('Action')."</th>";
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
		echo "<a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components_form&update=1&id_modulo=".$row["id_modulo"]."&id_nc=".$row["id_nc"]."'>".$nombre_modulo."</a></td>";
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
		echo "<td class='$tdcolor'>";
		if ($module_max == $module_min) {
			$module_max = "N/A";
			$module_min = "N/A";
		}
		echo $module_max." / ".$module_min;
		echo "<td class='$tdcolor' align='center'>";
		echo "<a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components&delete=1&id_nc=".$row["id_nc"]."'>";
		echo "<img src='images/cross.png' border=0 alt='".__('Delete')."'></a></td>";
		echo "</tr>";
	}
	echo "</table>";
} else {
	echo "<div class='nf'>No modules</div>";
}

?>
