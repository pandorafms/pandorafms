<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
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

	$sql='SELECT * FROM tserver';
	
	echo "<h2>".$lang_label["view_servers"]."</h2>";
	echo "<h3>".$lang_label["server_detail"]."<a href='help/".$help_code."/chap3.php#331' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";

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
		$color=1;
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
				}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
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
			echo "<td class='".$tdcolor."f9' align='middle'>".substr($laststart,0,25);
			echo "<td class='".$tdcolor."f9' align='middle'>".substr($keepalive,0,25);
		}
	echo '<tr><td colspan="11"><div class="raya"></div></td></tr></table>';	
	}
	else {
		echo '<font class="red">'.$lang_label["no_server"].'</font>';
	}

} else {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent view");
		require ("general/noaccess.php");
}
}
?>