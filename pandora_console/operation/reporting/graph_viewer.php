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

// Delete module SQL code
if (isset($_GET["delete"])){
	if ((give_acl($id_usuario,0,"AW") == 1 ) OR (dame_admin($id_user)==1)) {
		$id = $_GET["delete"];
		$sql = "DELETE FROM tgraph_source WHERE id_graph = $id";
		if ($res=mysql_query($sql))
			$result = "<h3 class=suc>".$lang_label["delete_ok"]."</h3>";
		else
			$result = "<h3 class=error>".$lang_label["delete_no"]."</h3>";
		$sql = "DELETE FROM tgraph WHERE id_graph = $id";
		if ($res=mysql_query($sql))
			$result = "<h3 class=suc>".$lang_label["delete_ok"]."</h3>";
		else
			$result = "<h3 class=error>".$lang_label["delete_no"]."</h3>";
		echo $result;
	} else {
		audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to delete a graph from access graph builder");
	include ("general/noaccess.php");
	exit;
	}
}


if (isset($_GET["view_graph"])){
	$id_graph = $_GET["view_graph"];
	$sql="SELECT * FROM tgraph WHERE id_graph = $id_graph";
	$res=mysql_query($sql);
	if ($row = mysql_fetch_array($res)){
		$id_user = $row["id_user"];
		$private = $row["private"];
		$width = $row["width"];
		$height = $row["height"];
		$period = $row["period"];
		if (isset($_POST["period"]))
			$period = $_POST["period"];
		$events = $row["events"];
		$description = $row["description"];
        $stacked = $row["stacked"];
		$name = $row["name"];
		if (($row["private"]==1) && ($row["id_user"] != $id_user)){
			audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access to a custom graph not allowed");
			include ("general/noaccess.php");
			exit;
		}
		
		$sql2="SELECT * FROM tgraph_source WHERE id_graph = $id_graph";
		$res2=mysql_query($sql2);
		while ( $row2 = mysql_fetch_array($res2)){
			$weight = $row2["weight"];
			$id_agent_module = $row2["id_agent_module"];
			if (!isset($modules)){
				$modules = $id_agent_module;
				$weights = $weight;
			} else {
				$modules = $modules.",".$id_agent_module;
				$weights = $weights.",".$weight;
			}
		}
		echo "<h2>".$lang_label["reporting"]." &gt; ";
		echo $lang_label["combined_image"]."</h2>";
		echo "<table class='databox_frame'>";
		echo "<tr><td>";
		echo "<img src='reporting/fgraph.php?tipo=combined&height=$height&width=$width&id=$modules&period=$period&stacked=$stacked&weight_l=$weights' border=1 alt=''>";
		echo "</td></tr></table>";
		switch ($period) {
			case 3600: 	$period_label = $lang_label["hour"];
					break;
			case 7200: 	$period_label = $lang_label["2_hours"];
					break;
			case 21600: 	$period_label = $lang_label["6_hours"];
					break;
			case 43200: 	$period_label = $lang_label["12_Hours"];
					break;
			case 86400: 	$period_label = $lang_label["last_day"];
					break;
			case 172800: 	$period_label = $lang_label["two_days"];
					break;
			case 432000: 	$period_label = $lang_label["five_days"];
					break;
			case 604800: 	$period_label = $lang_label["last_week"];
					break;
			case 1296000: 	$period_label = $lang_label["15_days"];
					break;
			case 2592000: 	$period_label = $lang_label["last_month"];
					break;
			case 5184000: 	$period_label = $lang_label["two_month"];
					break;
			case 15552000: 	$period_label = $lang_label["six_months"];
					break;
			default: 	$period_label = "--";
		}
		echo "<form method='POST' action='index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=$id_graph'>";
		echo "<table class='databox_frame'>";
		echo "<tr><td class='datos'>";
		echo "<b>Period</b>";
		echo "<td class='datos'>";
		echo "<select name='period'>";
		if ($period==0)
			echo "<option value=86400>".$period_label;
		else
			echo "<option value=$period>".$period_label;
		echo "<option value=3600>"."Hour";
		echo "<option value=21600>"."6 Hours";
		echo "<option value=43200>"."12 Hours";
		echo "<option value=86400>"."Last day";
		echo "<option value=172800>"."Two days";
		echo "<option value=604800>"."Last Week";
		echo "<option value=1296000>"."15 days";
		echo "<option value=2592000>"."Last Month";
		echo "<option value=5184000>"."Two Month";
		echo "<option value=15552000>"."Six Months";
		echo "</select>";
		
		echo "<td class='datos'>";
		echo "<input type=submit value='".$lang_label["update"]."' class='sub upd'>";
		echo "</table>";
		echo "</form>";		
	}
}
echo "<h2>".$lang_label["reporting"]." &gt; ";
echo  $lang_label["custom_graph_viewer"]."</h2>";

$color=1;
$sql="SELECT * FROM tgraph";
$res=mysql_query($sql);
if (mysql_num_rows($res)) {
	echo "<table width='500' cellpadding=4 cellpadding=4 class='databox_frame'>";
	echo "<tr>
		<th>".$lang_label["graph_name"]."</th>
		<th>".$lang_label["description"]."</th>
		<th>".$lang_label["view"]."</th>";
	if ((give_acl($id_usuario,0,"AW") == 1 ) OR (dame_admin($id_usuario)==1))
		echo "<th>".$lang_label["delete"]."</th>";
	echo "</tr>";

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
			echo "<td valign='top' class='$tdcolor'>".$row["name"]."</td>";
			echo "<td class='$tdcolor'>".$row["description"]."</td>";
			$id_graph =  $row["id_graph"];
			echo "<td valign='middle' class='$tdcolor' align='center'><a href='index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=$id_graph'><img src='images/images.png'></a>";
			
			if ((give_acl($id_usuario,0,"AW") == 1 ) OR (dame_admin($id_usuario)==1)) {
				echo "<td class='$tdcolor' align='center'><a href='index.php?sec=reporting&sec2=operation/reporting/graph_viewer&delete=$id_graph' ".'onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;">';
				echo "<img src='images/cross.png'></a></td>";
			}
		}
	}
	echo "</table>";
} else {
	echo "<div class='nf'>".$lang_label["no_reporting_def"]."</div>";
}

?>
