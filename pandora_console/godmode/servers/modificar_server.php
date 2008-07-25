<?php
// Pandora FMS - the Flexible monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
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

// Load global vars
require("include/config.php");

check_login();

if ((give_acl($config["id_user"], 0, "AR")!=1) AND (give_acl($id_user, 0, "AW") != 1)) {
	audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to access Server Management");
	require ("general/noaccess.php");
}

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
	echo "<h2>".$lang_label["view_servers"]." &gt; ";
	echo $lang_label["update_server"]."</h2>";

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
		echo '<form name="servers" method="POST" action="index.php?sec=gservers&sec2=godmode/servers/modificar_server&update=1">';
		echo "<table cellpadding='4' cellspacing='4' width='450' class='databox_color'>";
		echo "<tr>";
		echo "<td class='datos'>".$lang_label["name"]."</td><td class='datos'><input type='text' name='name' value='".$name."' width='200px'>";
		echo "<tr><td class='datos2'>".$lang_label['ip_address']."</td><td class='datos2'><input type='text' name='address' value='".$address."' width='200px'>";
		echo "<tr><td class='datos'>".$lang_label['description']."<td class='datos'><input type='text' name='description' value='".$description."'><input type='hidden' name='server' value='".entrada_limpia($_GET["server"])."'></input>";
	}
	else {
		echo "<div class='nf'>".$lang_label["no_server"]."</div>";
	}
	echo '</table>';
	echo '<table cellpadding="4" cellspacing="4" width="450">';
	echo '<tr><td align="right">';
	echo '<input type="submit" class="sub upd" value="'.$lang_label["update"].'"></table>';
} 
else {

	$sql='SELECT * FROM tserver';
	echo "<h2>".$lang_label["view_servers"]." &gt; ";
	echo $lang_label["manage_servers"]."</h2>";

	$result=mysql_query($sql);
	if (mysql_num_rows($result)){
		echo "<table cellpadding='4' cellspacing='4' witdh='550' class='databox'>";
		echo "<tr><th class='datos'>".$lang_label["name"]."</th>";
		echo "<th class='datos'>".$lang_label['status']."</th>";
		echo "<th class='datos'>".$lang_label['ip_address']."</th>";
		echo "<th class='datos'>".$lang_label['description']."</th>";
		echo "<th class='datos' width=80>".$lang_label['type']."</th>";
		echo "<th class='datos'>".$lang_label['laststart']."</th>";
		echo "<th class='datos'>".$lang_label['lastupdate']."</th>";
		echo "<th class='datos'>".$lang_label['delete']."</th>";
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
			echo "<a href='index.php?sec=gservers&sec2=godmode/servers/modificar_server&server=".$id_server."'><b>$name</b></a>";
			echo "</td><td class='$tdcolor' align='middle'>";
			if ($status ==0){
				echo "<img src='images/dot_red.png'>";
			} else {
				echo "<img src='images/dot_green.png'>";
			}
			echo "</td><td class='$tdcolor' align='middle'>";
			echo "$address";
			echo "</td><td class='".$tdcolor."f9'>".substr($description,0,25);
			echo "</td><td class='$tdcolor' align='middle'>";			
			if ($network_server == 1){
				echo "&nbsp; <img src='images/network.png'>";
			}		
			if ($data_server == 1){
				echo "&nbsp; <img src='images/data.png'>";
			}		
			if ($snmp_server == 1){
				echo "&nbsp; <img src='images/snmp.png'>";
			}		
			if ($master == 1){
				echo "&nbsp; <img src='images/master.png'>";
			}		
			if ($checksum == 1){
				echo "&nbsp; <img src='images/binary.png'>";
			}
			echo "</td>";
			echo "<td class='".$tdcolor."f9' align='middle'>".substr($laststart,0,25)."</td>";
			echo "<td class='".$tdcolor."f9' align='middle'>".substr($keepalive,0,25)."</td>";
			echo "<td class='".$tdcolor."f9' align='middle'>
			<a href='index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_del=".$id_server."&delete'>
			<img src='images/cross.png' border='0'></td></tr>";
		}
		echo '</table>';
		echo "<table cellpadding=2 cellspacing=0>";
		echo "
		<tr>
		 <td>
		  <span class='net'>".$lang_label["network_server"]."</span>
		 </td>
		 <td>
		  <span class='master'>".$lang_label["master"]."</span>
		 </td>
		 <td>
		  <span class='data'>".$lang_label["data_server"]."</span>
		 </td>
		 <td>
		  <span class='binary'>".$lang_label["md5_checksum"]."</span>
		 </td>
		 <td>
		  <span class='snmp'>".$lang_label["snmp_console"]."</span>
		 </td>
		</tr>";
		echo "</table>";	
	}
	else {
		echo "<div class='nf'>".$lang_label["no_server"]."</div>";
	}
}

?>
