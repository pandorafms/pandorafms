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
$id_user=$_SESSION["id_usuario"];
global $REMOTE_ADDR;

if (comprueba_login() != 0) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access map builder");
	include ("general/noaccess.php");
	exit;
}

if (give_acl($id_user, 0, "AW")!=1){
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access map builder");
	include ("general/noaccess.php");
	exit;
}

$form_report_name = "";
$form_report_private=0;
$form_report_description = "";
$createmode = 1;

if (isset($_GET["get_agent"])) {
 	$id_agent = $_POST["id_agent"];
}

// Delete module SQL code
if (isset($_GET["delete"])){
	$id_content = $_GET["delete"];
	$sql = "DELETE FROM tlayout_data WHERE id = $id_content";
	if ($res=mysql_query($sql))
		$result = "<h3 class=suc>".$lang_label["delete_ok"]."</h3>";
	else
		$result = "<h3 class=error>".$lang_label["delete_no"]."</h3>";
	echo $result;
}

// Delete module SQL code
if (isset($_GET["delete_report"])){
	$id = $_GET["delete_report"];
	$sql = "DELETE FROM tlayout_data WHERE id_layout = $id";
	$sql2 = "DELETE FROM tlayout WHERE id = $id";
	$res=mysql_query($sql);
	$res2=mysql_query($sql2);
	if ($res AND $res2)
		$result = "<h3 class=suc>".$lang_label["delete_ok"]."</h3>";
	else
		$result = "<h3 class=error>".$lang_label["delete_no"]."</h3>";
	echo $result;
}

// Create new report. First step
if (isset($_GET["create_graph"])){
	$createmode = 2;
}

// Add module SQL code
if (isset($_GET["add_module"])){
	if (isset($_POST["id_report"]))
		$id_report = $_POST["id_report"];
	else {
		audit_db($id_user,$REMOTE_ADDR, "Hack attempt","Parameter trash in map builder");
		include ("general/noaccess.php");
		exit;
	}
	$my_id_agent = entrada_limpia($_POST["id_agent"]);
	$my_id_module = entrada_limpia($_POST["id_module"]);
	$my_period = entrada_limpia($_POST["period"]);
	$my_type = entrada_limpia($_POST["type"]);
	$my_cg = entrada_limpia($_POST["id_custom_graph"]);
	$my_slamax = entrada_limpia($_POST["sla_max"]);
	$my_slamin = entrada_limpia($_POST["sla_min"]);
	$my_slalimit = entrada_limpia($_POST["sla_limit"]);
	
	$sql = "INSERT INTO tlayout_data (id_layout, id_gs, id_agent_module, type, sla_max, sla_min, sla_limit, period) VALUES ('$id_report', '$my_cg', '$my_id_module', '$my_type', '$my_slamax', '$my_slamin', '$my_slalimit', '$my_period')";
	if ($res=mysql_query($sql))
		$result = "<h3 class=suc>".$lang_label["create_ok"]."</h3>";
	else
		$result = "<h3 class=error>".$lang_label["create_no"]."</h3>";
	echo $result;
}

// Create item SQL code
if (isset($_POST["createmode"])){
	$createmode = $_POST["createmode"];
	$map_name = entrada_limpia($_POST["map_name"]);
	$map_background = entrada_limpia($_POST["map_background"]);
	$map_width = entrada_limpia($_POST["map_width"]);
	$map_height = entrada_limpia($_POST["map_height"]);
	
		
	// INSERT REPORT DATA
	if ($createmode == 1){
		$form_id_user = $id_user;
		$sql = "INSERT INTO tlayout (name, background, width, height) VALUES ('$map_name', '$map_background', '$map_width', '$map_height')";
		if ($res=mysql_query($sql))
			$result = "<h3 class=suc>".$lang_label["create_ok"]."</h3>";
		else
			$result = "<h3 class=error>".$lang_label["create_no"]."</h3>";
		$id_map = mysql_insert_id();
	// UPDATE REPORT DATA
	} else {
		$id_map = entrada_limpia($_POST["id_map"]);
		$sql = "UPDATE tlayout SET name = '$map_name', height= '$map_height', width = '$map_width', background = '$map_background' WHERE id = $id_map";
		if ($res=mysql_query($sql))
			$result = "<h3 class=suc>".$lang_label["modify_ok"]."</h3>";
		else
			$result = "<h3 class=error>".$lang_label["modify_no"]."</h3>";
	}
	echo $result;
	if ($id_map != ""){
		$_GET["id"] = $id_map;
		$createmode=0;
	}
}

// GET DATA OF REPORT
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ($createmode==2 OR isset($_GET["id"]) OR (isset($_POST["id_map"]))) {
	if (isset($_GET["id"]))
		$id_map = $_GET["id"];
	elseif (isset($_POST["id_map"]))
		$id_map = $_POST["id_map"];
	else
		$id_map = -1;
		
	if (isset($_POST["id_agent"]))
		$id_agent = $_POST["id_agent"];
	else
		$id_agent = 0;
	if ($createmode != 2){
		$createmode = 0;
		$sql = "SELECT * FROM tlayout WHERE id = $id_map";
		$res=mysql_query($sql);
		if ($row = mysql_fetch_array($res)){
			$map_name = $row["name"];
			$map_background = $row["background"];
			$map_width = $row["width"];
			$map_height = $row["height"];
		}
	} else {
		$map_name = "";
		$map_background = "";
		$map_width = "";
		$map_height = "";
		$createmode = 1;
	}

	echo "<h2>".$lang_label["reporting"]." &gt; ";
	echo $lang_label["map_builder"]."</h2>";
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/map_builder'>";
	echo "<input type='hidden' name=createmode value='$createmode'>";
	if ($createmode == 0){
		echo "<input type='hidden' name=id_map value='$id_map'>";
	}


	echo "<table width=500 cellspacing=4 cellpading=4 class='databox_color'>";
	echo "<tr><td class='datos2'>";
	echo $lang_label["report_name"];
	echo "<td class='datos2'>";
	echo "<input type=text size=35 name='map_name' value='$map_name'>";

	echo "<tr><td class='datos'>";
	echo $lang_label["background"];
	echo "</td><td class='datos'>";
	echo "<input type=text size=45 name='map_background' value='$map_background'>";

	echo "<tr><td class='datos'>";
	echo $lang_label["width"];
	echo "</td><td class='datos'>";
	echo "<input type=text size=10 name='map_width' value='$map_width'>";

	echo "<tr><td class='datos'>";
	echo $lang_label["height"];
	echo "</td><td class='datos'>";
	echo "<input type=text size=10 name='map_height' value='$map_height'>";

	// Button
	echo "</table>";
	echo "<table width=500 cellspacing=4 cellpading=4'>";
	echo "<tr><td align='right'>";
	if ($createmode == 0)
		echo "<input type='submit' class='sub next' value='".$lang_label["update"]."'>";
	else
		echo "<input type='submit' class='sub wand' value='".$lang_label["create"]."'>";
	echo "</td></tr></table>";
	echo "</form>";

	if ($createmode == 0){
		// Part 2 - Add new items to report
		// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

		echo "<h2>".$lang_label["reporting_item_add"]."</h2>";

		// Show combo with agents
		// ----------------------
		
		echo "<table width='500' cellpadding=4 cellpadding=4 class='databox_color'>";
		echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/map_builder&get_agent=1'>";
		echo "<input type='hidden' name=id_map value='$id_map'>";
		
		echo "<tr>";
		echo "<td class='datos'><b>".$lang_label["source_agent"]."</b>";
		echo "</td>";	
		echo "<td class='datos' colspan=2><select name='id_agent' style='width:180px;'>";
		if ($id_agent != 0)
			echo "<option value='$id_agent'>".dame_nombre_agente($id_agent);
		$sql1='SELECT * FROM tagente order by nombre';
		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			if ( $id_agent != $row["id_agente"])
				echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
		}
		echo '</select>';


		echo "<td class='datos' colspan=1 align='right'><input type=submit name='update_agent' class='sub upd' value='".$lang_label["get_info"]."'>";
		echo "</form>";

		// Modules combo
		// -----------------------
		echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/map_builder&add_module=1'>";
		echo "<input type='hidden' name=id_map value='$id_map'>";
		if (isset($id_agent))
			echo "<input type='hidden' name='id_agent' value='$id_agent'>";

		echo "<tr><td class='datos2'>";
		echo "<b>".$lang_label["modules"]."</b>";
		echo "<td class='datos2' colspan=3>";
		echo "<select name='id_module' size=1 style='width:180px;'>";
				echo "<option value=-1> -- </option>";
		if ($id_agent != 0){
			// Populate Module/Agent combo
			$sql1="SELECT * FROM tagente_modulo WHERE id_agente = ".$id_agent. " order by nombre";
			$result = mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
				echo "<option value=".$row["id_agente_modulo"].">".$row["nombre"]."</option>";
			}
		}
		echo "</select>";

			// Component type
		echo "<tr><td class='datos'>";
		echo "<b>".$lang_label["reporting_type"]."</b>";
		echo "<td class='datos' colspan=3>";
		echo "<select name='type' size=1 style='width:180px;'>";
		echo "<option value=1>".$lang_label["simple_graph"]."</option>";
		echo "<option value=0>".$lang_label["static_graph"]."</option>";
		echo "<option value=2>".$lang_label["line"]."</option>";
		echo "</select>";

		// Period
		echo "<tr><td class='datos2'>";
		echo "<b>".$lang_label["period"]."</b>";
		echo "<td class='datos2' colspan=3>";
		echo "<select name='period'>";
		echo "<option value=3600>"."Hour"."</option>";
		echo "<option value=7200>"."2 Hours"."</option>";
		echo "<option value=10800>"."3 Hours"."</option>";
		echo "<option value=21600>"."6 Hours"."</option>";
		echo "<option value=43200>"."12 Hours"."</option>";
		echo "<option value=86400>"."Last day"."</option>";
		echo "<option value=172800>"."Two days"."</option>";
		echo "<option value=604800>"."Last Week"."</option>";
		echo "<option value=1296000>"."15 days"."</option>";
		echo "<option value=2592000>"."Last Month"."</option>";
		echo "<option value=5184000>"."Two Month"."</option>";
		echo "<option value=15552000>"."Six Months"."</option>";
		echo "</select>";

		echo "<tr><td class='datos'>";
		echo "<b>".$lang_label["pos_x"]."</b></td>";
		echo "<td class='datos'>";
		echo "<input type=text size=6 name='pos_x'>";
		
		echo "<td class='datos'>";
		echo "<b>".$lang_label["pos_y"]."</b></td>";
		echo "<td class='datos'>";
		echo "<input type=text size=6 name='pos_y'>";
		

		echo "<tr><td class='datos2'>";
		echo "<b>".$lang_label["height"]."</b></td>";
		echo "<td class='datos2'>";
		echo "<input type=text size=6 name='height'>";
		
		echo "<td class='datos2'>";
		echo "<b>".$lang_label["width"]."</b></td>";
		echo "<td class='datos2'>";
		echo "<input type=text size=6 name='width'>";


		echo "<tr><td class='datos'>";
		echo "<b>".$lang_label["label"]."</b></td>";
		echo "<td class='datos' colspan=3>";
		echo "<input type=text size=25 name='label'>";

		echo "<tr><td class='datos2'>";
		echo "<b>".$lang_label["image"]."</b></td>";
		echo "<td class='datos2' colspan=3>";
		echo "<input type=text size=35 name='image'>";


		echo "<tr><td class='datos'>";
		echo "<b>".$lang_label["map_linked"]."</b>";
		echo "<td class='datos' colspan=3>";
		echo "<select name='map_linked' size=1 style='width:180px;'>";
		$sql_pi = "SELECT * FROM tlayout";
		$res_pi=mysql_query($sql_pi);
		echo "<option value='0'>".$row_pi["N/A"];
		while ($row_pi = mysql_fetch_array($res_pi)){
			echo "<option value='".$row_pi["id"]."'>".$row_pi["name"];
		}
		echo "</select>";

		echo "<tr><td class='datos2'>";
		echo "<b>".$lang_label["parent_item"]."</b>";
		echo "<td class='datos2' colspan=3>";
		echo "<select name='parent_item' size=1 style='width:180px;'>";
		$sql_pi = "SELECT * FROM tlayout_data WHERE id_layout = $id_map";
		$res_pi=mysql_query($sql_pi);
		echo "<option value='0'>".$row_pi["N/A"];
		while ($row_pi = mysql_fetch_array($res_pi)){
			echo "<option value='".$row_pi["id"]."'>".$row_pi["label"];
		}
		echo "</select>";

		echo "<tr><td class='datos'>";
		echo "<b>".$lang_label["label_color"]."</b>";
		echo "<td class='datos'>";
		echo "<select name='type' size=1>";
		echo "<option value='#ffffff'>".$lang_label["white"]."</option>";
		echo "<option value='#000000'>".$lang_label["black"]."</option>";
		echo "</select>";

		echo "<td class='datos'>";
		echo "<b>".$lang_label["link_color"]."</b>";
		echo "<td class='datos'>";
		echo "<select name='type' size=1 >";
		echo "<option value=1>".$lang_label["yes"]."</option>";
		echo "<option value=0>".$lang_label["no"]."</option>";
		echo "</select>";



		echo "</table>";
		

		echo "<table width=500 cellspacing=4 cellpading=4'>";
		echo "<tr><td align='right'>";
		echo "<input type='submit' class='sub wand' value='".$lang_label["add"]."'>";
		echo "</td></tr></table>";
		echo "</form>";
		

		// Part 3 - List of already assigned report items
		// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

		echo "<h2>".$lang_label["report_items"]."</h2>";
		echo "<table width=500 cellspacing=4 cellpadding=4 class='databox'>";
		echo "<tr><th>".$lang_label["type"]."</th>
		<th>".$lang_label["agent_name"]."</th>
		<th>".$lang_label["module_name"]."</th>
		<th>".$lang_label["pos_x"]."</th>
		<th>".$lang_label["pos_y"]."</th>
		<th>".$lang_label["delete"]."</th>
		</tr>";
		$sql = "SELECT * FROM tlayout_data WHERE id_layout = $id_map";
		$res=mysql_query($sql);
		$color = 0;
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
			$id_layoutdata = $row["id"];
			$type = $row["type"];
			switch ($type){
				case "0": $type_desc = "Single graph"; break;
				case "1": $type_desc = "Module graph"; break;
				case "2": $type_desc = "Line"; break;
			}
			$period = $row["period"];
			$id_am = $row["id_agente_modulo"];
			$x = $row["pos_x"];
			$y = $row["pos_y"];
			$name = "N/A";
			$agent_name = "N/A";
			if ($id_am != ""){
				$agent_name = dame_nombre_agente_agentemodulo ($id_am);
				$module_name = dame_nombre_modulo_agentemodulo ($id_am);
			}
			echo "<tr>";
			echo "<td class='$tdcolor'>".$type_desc."</td>";
			echo "<td class='$tdcolor'>".$agent_name."</td>";
			echo "<td class='$tdcolor'>".$module_name."</td>";
			echo "<td class='$tdcolor'>".$x."</td>";
			echo "<td class='$tdcolor'>".$y."</td>";
			echo "<td class='$tdcolor' align='center'>";
			echo "<a href='index.php?sec=greporting&sec2=godmode/reporting/map_builder&id=1&delete=$id_layoutdata'><img src='images/cancel.gif'></a>";
		}	
		echo "</table>";
	}
} else {
	// Map LIST Selection screen
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	echo "<h2>".$lang_label["reporting"]." &gt; ";
	echo $lang_label["map_builder"]."</h2>";
	echo "<table width='500' cellpadding=4 cellpadding=4 class='databox'>";
	echo "<tr>
	<th>".$lang_label["map_name"]."</th>
	<th>".$lang_label["background"]."</th>
	<th>".$lang_label["size"]."</th>
	<th>".$lang_label["Manage"]."</th>
	<th>".$lang_label["delete"]."</th>
	</tr>";
	$color=1;
	$sql="SELECT * FROM tlayout";
	$res=mysql_query($sql);
	while ($row = mysql_fetch_array($res)){
		if ((dame_admin($id_user)==1)){
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
			echo "<td valign='top' class='$tdcolor'>".$row["background"]."</td>";
			echo "<td valign='top' class='$tdcolor'>".$row["width"]."x".$row["height"]."</td>";		
			$id_map = $row["id"];
			echo "<td valign='middle' class='$tdcolor' align='center'><a href='index.php?sec=greporting&sec2=godmode/reporting/map_builder&id=$id_map'><img src='images/setup.png'></a>";
			echo "<td valign='middle' class='$tdcolor' align='center'><a href='index.php?sec=greporting&sec2=godmode/reporting/map_builder&delete_graph=$id_map'><img src='images/cancel.gif'></a>";
		}
	}
	echo "</table>";
	echo "<table width=500 cellpadding=4 cellpadding=4>";
	echo "<form method=post action='index.php?sec=greporting&sec2=godmode/reporting/map_builder&create_graph=1'>";
	echo "<tr><td align='right'>";
	echo "<input type=submit class='sub next' value='".$lang_label["add"]."'>";
	echo "</form>";
	echo "</table>";
}
?>