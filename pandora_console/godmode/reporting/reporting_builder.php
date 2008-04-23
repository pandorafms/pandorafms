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
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access report builder");
	include ("general/noaccess.php");
	exit;
}

if ((give_acl($id_user,0,"AW") != 1 ) AND (dame_admin($id_user)!=1)) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
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
	$sql = "DELETE FROM treport_content WHERE id_rc = $id_content";
	if ($res=mysql_query($sql))
		$result = "<h3 class='suc'>".$lang_label["delete_ok"]."</h3>";
	else
		$result = "<h3 class='error'>".$lang_label["delete_no"]."</h3>";
	echo $result;
}

// Delete module SQL code
if (isset($_GET["delete_report"])){
	$id = $_GET["delete_report"];
	$sql = "DELETE FROM treport_content WHERE id_report = $id";
	$sql2 = "DELETE FROM treport WHERE id_report = $id";
	$res=mysql_query($sql);
	$res2=mysql_query($sql2);
	if ($res AND $res2)
		$result = "<h3 class=suc>".$lang_label["delete_reporting_ok"]."</h3>";
	else
		$result = "<h3 class=error>".$lang_label["delete_reporting_no"]."</h3>";
	echo $result;
}

// Create new report. First step
if (isset($_GET["create_report"])){
	$createmode = 2;
}

// Add module SQL code
if (isset($_GET["add_module"])){
	if (isset($_POST["id_report"]))
		$id_report = $_POST["id_report"];
	else {
		audit_db($id_user,$REMOTE_ADDR, "Hack attempt","Parameter trash in report builder");
		include ("general/noaccess.php");
		exit;
	}
	$my_id_agent = entrada_limpia($_POST["id_agent"]);
	$my_id_module = entrada_limpia($_POST["id_module"]);
	$my_period = entrada_limpia($_POST["period"]);
	$my_type = entrada_limpia($_POST["type"]);

    // event reporting (use agent not module)
    if ($my_type == 3){
        $my_id_module = $my_id_agent;
    }

	$my_cg = entrada_limpia($_POST["id_custom_graph"]);
	$my_slamax = entrada_limpia($_POST["sla_max"]);
	$my_slamin = entrada_limpia($_POST["sla_min"]);
	$my_slalimit = entrada_limpia($_POST["sla_limit"]);
	
	$sql = "INSERT INTO treport_content (id_report, id_gs, id_agent_module, type, sla_max, sla_min, sla_limit, period) VALUES ('$id_report', '$my_cg', '$my_id_module', '$my_type', '$my_slamax', '$my_slamin', '$my_slalimit', '$my_period')";
	if ($res=mysql_query($sql))
		$result = "<h3 class=suc>".$lang_label["create_reporting_ok"]."</h3>";
	else
		$result = "<h3 class=error>".$lang_label["create_reporting_no"]."</h3>";
	echo $result;
}

// Create item SQL code
if (isset($_POST["createmode"])){
	$createmode = $_POST["createmode"];
	$form_report_name = entrada_limpia($_POST["report_name"]);
	$form_report_description = entrada_limpia($_POST["report_description"]);
	if (isset($_POST["report_private"]))
		$form_report_private = entrada_limpia($_POST["report_private"]);
	else
		$form_report_private = 0;
		
	// INSERT REPORT DATA
	if ($createmode == 1){
		$form_id_user = $id_user;
		$sql = "INSERT INTO treport (name, description, id_user, private) VALUES ('$form_report_name', '$form_report_description', '$form_id_user', '$form_report_private')";
		if ($res=mysql_query($sql))
			$result = "<h3 class=suc>".$lang_label["create_reporting_ok"]."</h3>";
		else
			$result = "<h3 class=error>".$lang_label["create_reporting_no"]."</h3>";
		$id_report = mysql_insert_id();
	// UPDATE REPORT DATA
	} else {
		$form_id_report = entrada_limpia($_POST["id_report"]);
		$id_report = $form_id_report;
		$sql = "UPDATE treport SET name = '$form_report_name', description = '$form_report_description', private = '$form_report_private' WHERE id_report = $form_id_report";
		if ($res=mysql_query($sql))
			$result = "<h3 class=suc>".$lang_label["modify_ok"]."</h3>";
		else
			$result = "<h3 class=error>".$lang_label["modify_no"]."</h3>";
	}
	echo $result;
	if ($id_report != ""){
		$_GET["id"]=$id_report;
		$createmode=0;
	}
}

// GET DATA OF REPORT
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ($createmode==2 OR isset($_GET["id"]) OR (isset($_POST["id_report"]))) {
	if (isset($_GET["id"]))
		$id_report = $_GET["id"];
	elseif (isset($_POST["id_report"]))
		$id_report = $_POST["id_report"];
	else
		$id_report = -1;
		
	if (isset($_POST["id_agent"]))
		$id_agent = $_POST["id_agent"];
	else
		$id_agent = 0;
	if ($createmode != 2){
		$createmode = 0;
		$sql = "SELECT * FROM treport WHERE id_report = $id_report";
		$res=mysql_query($sql);
		if ($row = mysql_fetch_array($res)){
			$form_report_name = $row["name"];
			$form_report_description = $row["description"];
			$form_report_private = $row["private"];
			$form_id_user = $row["id_user"];
		}
	} else {
		$form_report_name = "";
		$form_report_description = "";
		$form_report_private = 0;
		$form_id_user = $id_user;
		$createmode = 1;
	}
	echo "<h2>".$lang_label["reporting"]." &gt; ";
	echo $lang_label["custom_reporting_builder"]."</h2>";
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder'>";
	echo "<input type='hidden' name=createmode value='$createmode'>";
	if ($createmode == 0){
		echo "<input type='hidden' name=id_report value='$id_report'>";
	}
	echo "<table width=500 cellspacing=4 cellpading=4 class='databox_color'>";

	echo "<tr><td class='datos2'>";
	echo $lang_label["report_name"]."</td>";
	echo "<td class='datos2'>";
	echo "<input type=text size=35 name='report_name' value='$form_report_name'>";

	echo "<tr><td class='datos'>";
	echo $lang_label["private"]."</td>";
	echo "<td class='datos'>";
	if ($form_report_private == 1)
		echo "<input type=checkbox name='report_private' value=1 CHECKED>";
	else
		echo "<input type=checkbox name='report_private' value=1>";
	echo "</td></tr>";
	echo "<tr><td class='datos2' valign='top'>";
	echo $lang_label["description"]."</td>";
	echo "<td class='datos2'>";
	echo "<textarea name='report_description' cols=40 rows=3>";
	echo $form_report_description;
	echo "</textarea>";
	echo "</td></tr>";
	echo "</table>";
	
	// Button
	echo "<table width=500 cellspacing=4 cellpading=4'>";
	echo "<tr><td align='right'>";
	if ($createmode == 0)
		echo "<input type='submit' class='sub next' value='".$lang_label["update"]."'>";
	else
		echo "<input type='submit' class='sub wand' value='".$lang_label["create"]."'>";
	echo "</td></tr>";
	echo "</table>";
	echo "</form>";

	if ($createmode == 0){
		// Part 2 - Add new items to report
		// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

		echo "<h2>".$lang_label["reporting_item_add"]."</h2>";

		// Show combo with agents
		// ----------------------
		
		echo "<table width='500' cellpadding=4 cellpadding=4 class='databox_color'>";
		echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&get_agent=1'>";
		echo "<input type='hidden' name=id_report value='$id_report'>";
		
		echo "<tr>";
		echo "<td class='datos'><b>".$lang_label["source_agent"]."</b></td>";
		echo "<td class='datos' colspan=2><select name='id_agent' style='width:180px;'>";
		if ($id_agent != 0)
			echo "<option value='$id_agent'>".dame_nombre_agente($id_agent);
		$sql1='SELECT * FROM tagente order by nombre';
		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			if ( $id_agent != $row["id_agente"])
				echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
		}
		echo '</select></td>';

		echo "<td class='datos' colspan='1' align='right'>
		<input type=submit name='update_agent' class='sub upd' value='".$lang_label["get_info"]."'>";
		echo "</td></form>";

		// Modules combo
		// -----------------------
		echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&add_module=1'>";
		echo "<input type='hidden' name=id_report value='$id_report'>";
		if (isset($id_agent))
			echo "<input type='hidden' name='id_agent' value='$id_agent'>";

		echo "<tr><td class='datos2'>";
		echo "<b>".$lang_label["modules"]."</b>";
		echo "</td>";
		echo "<td class='datos2' colspan=3>";
		echo "<select name='id_module' size=1 style='width:180px;'>";
				echo "<option value=-1> -- </option>";
		if ($id_agent != 0){
			// Populate Module/Agent combo
			$sql1="SELECT * FROM tagente_modulo WHERE id_agente = ".$id_agent. " ORDER BY nombre";
			$result = mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
				echo "<option value=".$row["id_agente_modulo"].">".$row["nombre"]."</option>";
			}
		}
		echo "</select></td></tr>";

			// Component type
		echo "<tr><td class='datos'>";
		echo "<b>".$lang_label["reporting_type"]."</b></td>";
		echo "<td class='datos' colspan=3>";
		echo "<select name='type' size=1 style='width:180px;'>";
		echo "<option value=0>".$lang_label["simple_graph"]."</option>";
		echo "<option value=1>".$lang_label["custom_graph"]."</option>";
		echo "<option value=2>".$lang_label["SLA"]."</option>";
		echo "<option value=3>".$lang_label["event_report"]."</option>";
		echo "<option value=4>".$lang_label["alert_report"]."</option>";
		echo "<option value=5>".$lang_label["monitor_report"]."</option>";
		echo "<option value=6>".$lang_label["avg_value"]."</option>";
		echo "<option value=7>".$lang_label["max_value"]."</option>";
		echo "<option value=8>".$lang_label["min_value"]."</option>";
		echo "</select></td></tr>";

		// Custom graph
		// -----------------------
		echo "<tr><td class='datos2'>";
		echo "<b>".$lang_name["custom_graph_name"]."</b></td>";
		echo "<td class='datos2' colspan=3>";
		echo "<select name='id_custom_graph' size=1 style='width:180px;'>";
		echo "<option value='-1'>".$row["N/A"]."</option>";
		$sql1="SELECT * FROM tgraph";
		$result = mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			echo "<option value=".$row["id_graph"].">".$row["name"]."</option>";
		}
		echo "</select>";

		// Period
		echo "<tr><td class='datos'>";
		echo "<b>".$lang_label["period"]."</b></td>";
		echo "<td class='datos' colspan=3>";
		echo "<select name='period'>";
		echo "<option value=3600>"."Hour</option>";
		echo "<option value=7200>"."2 Hours</option>";
		echo "<option value=10800>"."3 Hours</option>";
		echo "<option value=21600>"."6 Hours</option>";
		echo "<option value=43200>"."12 Hours</option>";
		echo "<option value=86400>"."Last day</option>";
		echo "<option value=172800>"."Two days</option>";
		echo "<option value=604800>"."Last Week</option>";
		echo "<option value=1296000>"."15 days</option>";
		echo "<option value=2592000>"."Last Month</option>";
		echo "<option value=5184000>"."Two Month</option>";
		echo "<option value=15552000>"."Six Months</option>";
		echo "</select></td></tr>";

		// SLA Max
		echo "<tr><td class='datos2'>";
		echo "<b>".$lang_label["sla_max"]."</b></td>";
		echo "<td class='datos2'>";
		echo "<input type=text size=6 name='sla_max'></td>";
		// SLA Min
		echo "<td class='datos2'>";
		echo "<b>".$lang_label["sla_min"]."</b></td>";
		echo "<td class='datos2'>";
		echo "<input type=text size=6 name='sla_min'></td>";
		
		// SLA limit
		echo "<tr><td class='datos'>";
		echo "<b>".$lang_label["sla_limit"]."</b></td>";
		echo "<td class='datos'>";
		echo "<input type='text' size='6' name='sla_limit'></td>";
		echo "</tr></table>";

		echo "<table width='500' cellspacing='4' cellpading='4'>";
		echo "<tr><td align='right'>";
		echo "<input type='submit' class='sub wand' value='".$lang_label["add"]."'>";
		echo "</td></tr>";
		echo "</table>";
		echo "</form>";
		

		// Part 3 - List of already assigned report items
		// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

		echo "<h2>".$lang_label["report_items"]."</h2>";
		echo "<table width=500 cellspacing=4 cellpadding=4 class='databox'>";
		echo "<tr>
		<th>".$lang_label["type"]."</th>
		<th>".$lang_label["agent_name"]."</th>
		<th>".$lang_label["module_name"]."</th>
		<th>".$lang_label["period"]."</th>
		<th>".$lang_label["delete"]."</th>";
		$sql = "SELECT * FROM treport_content WHERE id_report = $id_report";
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
			$id_rc = $row["id_rc"];
			$type = $row["type"];
			switch ($type){
				case "0": $type_desc = "Graph"; break;
				case "1": $type_desc = "User graph"; break;
				case "2": $type_desc = "SLA"; break;
				case "3": $type_desc = "Event report"; break;
				case "4": $type_desc = "Alert report"; break;
				case "5": $type_desc = "Monitor report"; break;
				case "6": $type_desc = "Avg.Value"; break;
				case "7": $type_desc = "Max.Value"; break;
				case "8": $type_desc = "Min.Value"; break;
			}
			$period = $row["period"];
			$id_am = $row["id_agent_module"];
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
			echo "<td class='$tdcolor'>".$period."</td>";
			echo "<td class='$tdcolor' align='center'>";
			if ($form_id_user == $id_user){
				echo "<a href='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&id=$id_report&delete=$id_rc'><img src='images/cross.png'></a>";
			}
			echo "</td></tr>";
		}
		echo "</table>";
	}
} else {
	// Report item editor / add
	
	// Report LIST
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	echo "<h2>".$lang_label["reporting"]." &gt; ";
	echo $lang_label["custom_reporting"]."</h2>";

	$sql="SELECT * FROM treport";
	$res=mysql_query($sql);
	if (mysql_num_rows($res)) {
	echo "<table width='600' cellpadding=4 cellpadding=4 class='databox'>";
	echo "<tr>
	<th>".$lang_label["report_name"]."</th>
	<th>".$lang_label["description"]."</th>
	<th>".$lang_label["Manage"]."</th>
	<th>".$lang_label["delete"]."</th>";
	$color=1;	
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
			echo "<td valign='middle' class='$tdcolor' align='center'>
			<a href='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&id=$id_report'>
			<img src='images/setup.png'></a></td>";
			echo "<td valign='middle' class='$tdcolor' align='center'>
			<a href='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&delete_report=$id_report'>
			<img src='images/cross.png'></a></td>";
		}
	}
	echo "</table>";
	echo "<table width=600 cellpadding=4 cellpadding=4>";
} else {
	echo "<div class='nf'>".$lang_label["no_reporting_def"]."</div>";
	echo "<table>";
}
	echo "<form method=post action='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&create_report=1'>";
	echo "<tr><td align='right'>";
	echo "<input type=submit class='sub next' value='".$lang_label["add"]."'>";
	echo "</form>";
	echo "</table>";
}
?>
