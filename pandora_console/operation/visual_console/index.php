<?PHP

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, <slerena@gmail.com>
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2008 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP code additions
// Please see http://pandora.sourceforge.net for full contribution list
// 
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Login check
$id_usuario=$_SESSION["id_usuario"];
global $REMOTE_ADDR;

if (comprueba_login() != 0) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

echo "<h2>".$lang_label["visual_console"]." &gt; ";
echo $lang_label["summary"]."</h2>";
$sql="SELECT * FROM tlayout";
$res=mysql_query($sql);

if (mysql_num_rows($res)) {

	echo "<table width='500' cellpadding=4 cellpadding=4 class='databox_frame'>";
	echo "<tr>
	<th>".$lang_label["name"]."</th>
	<th>".$lang_label["group"]."</th>
	<th>".$lang_label["elements"]."</th>
	<th>".$lang_label["view"]."</th>";
	$color=1;
	while ($row = mysql_fetch_array($res)){
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
		// Name
		echo "<td valign='top' class='$tdcolor'>".$row["name"]."</td>";
		$id_layout =  $row["id"];
		// Group
		echo "<td valign='top' align='center' class='$tdcolor'><img src='images/".dame_grupo_icono($row["id_group"]).".png'></td>";
		// # elements		
		$sql2="SELECT COUNT(*) FROM tlayout_data WHERE id_layout = $id_layout";
		$res2=mysql_query($sql2);
		$row2 = mysql_fetch_array($res2);
		echo "<td valign='top'align='center' class='$tdcolor'>".$row2[0]."</td>";
		// View icon
		echo "<td valign='middle' class='$tdcolor' align='center'><a href='index.php?sec=visualc&sec2=operation/visual_console/render_view&id=$id_layout'><img src='images/images.png'></a></td></tr>";
	}
	echo "</table>";
} else {
	echo "<div class='nf'>".$lang_label["no_layout_def"]."</div>";
}

?>
