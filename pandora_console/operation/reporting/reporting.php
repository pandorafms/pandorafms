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
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access reporting");
	include ("general/noaccess.php");
	exit;
}

$sql1="SELECT COUNT(id_report) FROM treport";
$res1=mysql_query($sql1);
$row1 = mysql_fetch_array ($res1);
$sql2="SELECT COUNT(id_graph) FROM tgraph";
$res2=mysql_query($sql2);
$row2 = mysql_fetch_array ($res2);

echo "<h2>".$lang_label["reporting"]." &gt; ";
echo $lang_label["summary"]."</h2>";


if ($row1[0] == 0 && $row2[0] == 0) {
	echo "<div class='nf'>".$lang_label["no_reporting_def"]."</div>";
} else {
	echo "
	<table width='300' cellpadding='4' cellpadding='4' class='databox'>
	<tr>
	<th>".$lang_label["custom_reporting"]."</th>
	<th>".$lang_label["custom_graphs"]."</th>
	</tr>
	<td>".$row1[0]."</td>
	<td>".$row2[0]."</td>
	</tr>
	</table>";
}

?>
