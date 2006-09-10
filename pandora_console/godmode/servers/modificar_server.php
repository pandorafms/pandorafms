<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnológicas S.L, info@artica.es
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
 	if ((give_acl($id_user, 0, "AR")==1) or (give_acl($id_user,0,"AW")) or (dame_admin($id_user)==1)) {

	if (isset($_GET["delete"])) {
		$id_server=entrada_limpia($_GET["server_del"]);
		$sql = "DELETE FROM tserver WHERE id_server='".$id_server."'";
		$result=mysql_query($sql);
		if ($result) echo "<h3 class='suc'>".$lang_label["del_server_ok"]."</h3>";
		else echo "<h3 class='suc'>".$lang_label["del_server_no"]."</h3>";
	}
	
	if (isset($_GET["update"])) {
		$name=entrada_limpia($_POST["name"]);
		$address=entrada_limpia($_POST["address"]);
		$description=entrada_limpia($_POST["description"]);
		$id_server=entrada_limpia($_POST["server"]);
		$sql = "UPDATE tserver SET name='".$name."', ip_address='".$address."', description='".$description."' WHERE id_server='".$id_server."'";
		$result=mysql_query($sql);
		if ($result) echo "<h3 class='suc'>".$lang_label["upd_server_ok"]."</h3>";
		else echo "<h3 class='suc'>".$lang_label["upd_server_no"]."</h3>";
	}
	if (isset($_GET["server"])) {
		$id_server=entrada_limpia($_GET["server"]);
		echo "<h2>".$lang_label["view_servers"]."</h2>";
		echo "<h3>".$lang_label["update_server"]."<a href='help/".$help_code."/chap7.php#7' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";

	$query="SELECT * FROM tserver WHERE id_server=".$id_server;
	$result=mysql_query($query);
	if (mysql_num_rows($result)){
		$row=mysql_fetch_array($result);
		$name = $row["name"];
		$address = $row["ip_address"];
		$status = $row["status"];
		$laststart = $row["laststart"];
		$keepalive = $row["keepalive"];
		$network_server = $row["network_server"];
		$data_server = $row["data_server"];
		$snmp_server = $row["snmp_server"];
		$master = $row["master"];
		$checksum = $row["checksum"];
		$description = $row["description"];
		echo '<form name="servers" method="POST" action="index.php?sec=gserver&sec2=godmode/servers/modificar_server&update=1">';
		echo "<table cellpadding='3' cellspacing='3' width='450'>";
		echo "<tr><td class='lb' rowspan='3' width='5'>";
		echo "<td class='datos'>".$lang_label["name"]."</td><td class='datos'><input type='text' name='name' value='".$name."' class='w200'>";
		echo "<tr><td class='datos2'>".$lang_label['ip_address']."</td><td class='datos2'><input type='text' name='address' value='".$address."' class='w200'>";
		echo "<tr><td class='datos'>".$lang_label['description']."<td class='datos'><input type='text' name='description' value='".$description."' class='w200'><input type='hidden' name='server' value='".entrada_limpia($_GET["server"])."></input>";
	}
	else {
		echo '<font class="red">'.$lang_label["no_server"].'</font>';
		}
	echo '<tr><td colspan="3"><div class="raya"></div></td></tr>';
	echo '<tr><td colspan="3" align="right">';
	echo '<input type="submit" class="sub" value="'.$lang_label["update"].'"></table>';
	}
	else
	{
	$sql='SELECT * FROM tserver';
	
	echo "<h2>".$lang_label["view_servers"]."</h2>";
	echo "<h3>".$lang_label["manage_servers"]."<a href='help/".$help_code."/chap7.php#7' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";

	// Connect DataBase
	$result=mysql_query($sql);
	if (mysql_num_rows($result)){	
	echo "<table cellpadding='3' cellspacing='3' witdh=550>";
	echo "<tr><th class='datos'>".$lang_label["name"];
	echo "<th class='datos'>".$lang_label['status'];
	echo "<th class='datos'>".$lang_label['ip_address'];
	echo "<th class='datos'>".$lang_label['description'];
	echo "<th class='datos'>".$lang_label['network'];
	echo "<th class='datos'>".$lang_label['data'];
	echo "<th class='datos'>".$lang_label['snmp'];
	echo "<th class='datos'>".$lang_label['master'];
	echo "<th class='datos'>".$lang_label['checksum'];
	echo "<th class='datos'>".$lang_label['laststart'];
	echo "<th class='datos'>".$lang_label['lastupdate'];
	echo "<th class='datos'>".$lang_label['action'];
	$color=1;
		while ($row=mysql_fetch_array($result)){
			$name = $row["name"];
			$address = $row["ip_address"];
			$status = $row["status"];
			$laststart = $row["laststart"];
			$keepalive = $row["keepalive"];
			$network_server = $row["network_server"];
			$data_server = $row["data_server"];
			$snmp_server = $row["snmp_server"];
			$master = $row["master"];
			$checksum = $row["checksum"];
			$description = $row["description"];
			$id_server = $row["id_server"];
			
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr><td class='$tdcolor'>";
			echo "<b>$name</b>";
			echo "<td class='$tdcolor' align='middle'>";
			if ($status ==0){
				echo "<img src='images/dot_red.gif'>";
			} else {
				echo "<img src='images/dot_green.gif'>";
			}
			echo "<td class='$tdcolor' align='middle'>";
			echo "$address";
			echo "<td class='".$tdcolor."f9'>".substr($description,0,25);
			echo "<td class='$tdcolor' align='middle'>";			
			if ($network_server == 1){
				echo "<img src='images/network.gif'>";
			}
			echo "<td class='$tdcolor' align='middle'>";			
			if ($data_server == 1){
				echo "<img src='images/data.gif'>";
			}
			echo "<td class='$tdcolor' align='middle'>";			
			if ($snmp_server == 1){
				echo "<img src='images/snmp.gif'>";
			}
			echo "<td class='$tdcolor' align='middle'>";			
			if ($master == 1){
				echo "<img src='images/master.gif'>";
			}
			echo "<td class='$tdcolor' align='middle'>";			
			if ($checksum == 1){
				echo "<img src='images/binary.gif'>";
			}
			echo "<td class='".$tdcolor."f9' align='middle'>".substr($keepalive,0,25);
			echo "<td class='".$tdcolor."f9' align='middle'>".substr($laststart,0,25);
			echo "<td class='".$tdcolor."f9' align='middle'><a href='index.php?sec=gserver&sec2=godmode/servers/modificar_server&server=".$id_server."'><img src='images/config.gif' border='0'></a>&nbsp;<a href='index.php?sec=gserver&sec2=godmode/servers/modificar_server&server_del=".$id_server."&delete'><img src='images/cancel.gif' border='0'>";
		}
		echo '<tr><td colspan="12"><div class="raya"></div></td></tr></table>';	
	}
	else {
		echo '<font class="red">'.$lang_label["no_server"].'</font>';
		}
	}

} else {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent view");
		require ("general/noaccess.php");
}
}
?>