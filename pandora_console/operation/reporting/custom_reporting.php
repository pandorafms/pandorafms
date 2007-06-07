<?PHP

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
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

// Login check
$id_usuario=$_SESSION["id_usuario"];
global $REMOTE_ADDR;

if (comprueba_login() != 0) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

//echo "SLA for Tato: %".return_module_SLA (50, 604800, 1, 1);

echo "<h3>".$lang_label["custom_reporting"]."</h3>";
echo "<table width='500' cellpadding=4 cellpadding=4 class='databox'>";
echo "<tr><th>".$lang_label["report_name"]."<th>".$lang_label["description"]."<th>".$lang_label["view"];
$color=1;
$sql="SELECT * FROM treport";
$res=mysql_query($sql);
while ($row = mysql_fetch_array($res)){
	if (($row["private"]==0) || ($row["id_user"] == $id_user)){
		// Calculate table line color
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr>";
		echo "<td valign='top' class='$tdcolor'>".$row["name"];
		echo "<td class='$tdcolor'>".$row["description"];
		$id_report = $row["id_report"];
		echo "<td valign='middle' class='$tdcolor' align='center'><a href='index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id=$id_report'><img src='images/reporting.png'></A>";
	}
}
echo "</table>";


?>
