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

$id_agent = 0;
$id_module = 0;
$name = "Pandora FMS combined graph";
$width = 550;
$height = 210;
$period = 86401;
//$alerts= "";
$events = "";
$factor = 1;
$render=1; // by default
$stacked = 0;

// Login check
$id_usuario=$_SESSION["id_usuario"];
global $REMOTE_ADDR;

if (comprueba_login() != 0) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

if ((give_acl($id_user,0,"AW") != 1 ) AND (dame_admin($id_user)!=1)) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

if (isset($_GET["store_graph"])){
	$name = entrada_limpia($_POST["name"]);
	$description = entrada_limpia($_POST["description"]);
	$module_number = entrada_limpia($_POST["module_number"]);
	$private = entrada_limpia($_POST["private"]);
	$width = entrada_limpia($_POST["width"]);
	$height = entrada_limpia($_POST["height"]);
	$events = entrada_limpia($_POST["events"]);
	$stacked = get_parameter ("stacked",0);
    if ($events == "") // Temporal workaround
        $events = 0;
	$period = entrada_limpia($_POST["period"]);
	// Create graph
	$sql = "INSERT INTO tgraph
		(id_user, name, description, period, width, height, private, events, stacked) VALUES
		('$id_user',
		'$name',
		'$description',
		$period,
		$width,
		$height,
		$private,
		$events,
        $stacked)";
		//echo "DEBUG $sql<br>";
	$res = mysql_query($sql);
	if ($res){
		$id_graph = mysql_insert_id();
		if ($id_graph){
			for ($a=0; $a < $module_number; $a++){
				$id_agentemodulo = entrada_limpia($_POST["module_".$a]);
				$id_agentemodulo_w = entrada_limpia($_POST["module_weight_".$a]);
				$sql = "INSERT INTO tgraph_source (id_graph, id_agent_module, weight) VALUES
					($id_graph, $id_agentemodulo, $id_agentemodulo_w)";
				//echo "DEBUG $sql<br>";
				mysql_query($sql);
			}
			echo "<h3 class='suc'>".$lang_label["store_graph_suc"]."</h3>";
		} else
			echo "<h3 class='error'>".$lang_label["store_graph_error"]."</h3>";
	} else 
		echo "<h3 class='error'>".$lang_label["store_graph_error"]."</h3>";
}

if (isset($_GET["get_agent"])) {
 	$id_agent = $_POST["id_agent"];
	if (isset($_POST["chunk"]))
		$chunkdata = $_POST["chunk"];
}

if (isset($_GET["delete_module"] )) {
	$chunkdata = $_POST["chunk"];
	if (isset($chunkdata)) {
		$chunk1 = array();
		$chunk1 = split ("\|", $chunkdata);
		$modules="";$weights="";
		for ($a=0; $a < count($chunk1); $a++){
			if (isset($_POST["delete_$a"])){
				$id_module = $_POST["delete_$a"];
				$deleted_id[]=$id_module;
			}	
		}
		$chunkdata2 = "";
		$module_array = array();
		$weight_array = array();
		$agent_array = array();
		for ($a=0; $a < count($chunk1); $a++){
			$chunk2[$a] = array();
			$chunk2[$a] = split ( ",", $chunk1[$a]);
			$skip_module =0;
			for ($b=0; $b < count($deleted_id); $b++){
				if ($deleted_id[$b] == $chunk2[$a][1]){
					$skip_module = 1;
				}
			}
			if (($skip_module == 0) && (strpos($modules, $chunk2[$a][1]) == 0)){  // Skip
				$module_array[] = $chunk2[$a][1];
				$agent_array[] = $chunk2[$a][0];
				$weight_array[] = $chunk2[$a][2];
				if ($chunkdata2 == "")
					$chunkdata2 .= $chunk2[$a][0].",".$chunk2[$a][1].",".$chunk2[$a][2];
				else
					$chunkdata2 .= "|".$chunk2[$a][0].",".$chunk2[$a][1].",".$chunk2[$a][2];
				if ($modules !="")
					$modules = $modules.",".$chunk2[$a][1];
				else
					$modules = $chunk2[$a][1];
				if ($weights !="")
					$weights = $weights.",".$chunk2[$a][2];
				else
					$weights = $chunk2[$a][2];
			}
		}
		$chunkdata = $chunkdata2;
	}
}

if ( (isset($_GET["add_module"]))){
 	$id_agent = $_POST["id_agent"];
 	$id_module = $_POST["id_module"];
 	if (isset($_POST["factor"]))
 		$factor = $_POST["factor"];
 	else
 		$factor = 1;
 	$period = $_POST["period"];
 	$render = $_POST["render"];
 	$stacked = get_parameter ("stacked",0);
// 	$alerts = $_POST["alerts"];
	if (isset($_POST["chunk"]))
 		$chunkdata = $_POST["chunk"];
	$events = $_POST["events"];
	$factor = $_POST["factor"];
 	if ($_POST["width"]!= "")
 		$width = $_POST["width"];
 	if ($_POST["height"]!= "")
 		$height = $_POST["height"];
 	if ($id_module > 0){	
		if (!isset($chunkdata) OR ($chunkdata == ""))
			$chunkdata = "$id_agent,$id_module,$factor";
		else
			$chunkdata = $chunkdata."|$id_agent,$id_module,$factor";
	}
}
 
// Parse CHUNK information into showable information
// Split id to get all parameters
if (! isset($_GET["delete_module"])){
	if (isset($_POST["period"]))
		$period = $_POST["period"];
	if ((isset($chunkdata) )&& ($chunkdata != "")) {
		$module_array = array();
		$weight_array = array();
		$agent_array = array();
		$chunk1 = array();
		$chunk1 = split ("\|", $chunkdata);
		$modules="";$weights="";
		for ($a=0; $a < count($chunk1); $a++){
			$chunk2[$a] = array();
			$chunk2[$a] = split ( ",", $chunk1[$a]);
			if (strpos($modules, $chunk2[$a][1]) == 0){  // Skip dupes
				$module_array[] = $chunk2[$a][1];
				$agent_array[] = $chunk2[$a][0];
				$weight_array[] = $chunk2[$a][2];
				if ($modules !="")
					$modules = $modules.",".$chunk2[$a][1];
				else
					$modules = $chunk2[$a][1];
				if ($weights !="")
					$weights = $weights.",".$chunk2[$a][2];
				else
					$weights = $chunk2[$a][2];
			}
		}
	}
}

	echo "<h2>".$lang_label["reporting"]." &gt; ";
if (isset($chunk1)) {
	echo $lang_label["graph_builder_modulelist"]."</h2>";
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&delete_module=1'>";
	if (isset($chunkdata))
		echo "<input type='hidden' name='chunk' value='$chunkdata'>";
	if (isset($id_agent))
		echo "<input type='hidden' name='id_agent' value='$id_agent'>";
    if (isset($period))
        echo "<input type='hidden' name='period' value='$period'>";

	echo "<table width='500' cellpadding=4 cellpadding=4 class='databox'>";
	echo "<tr>
	<th>".$lang_label["agent"]."</th>
	<th>".$lang_label["module"]."</th>
	<th>Weight</th>
	<th>".$lang_label["delete"]."</th>";
	$color=0;
	for ($a=0; $a < count($module_array); $a++){
		// Calculate table line color
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}

		echo "<tr><td class='$tdcolor'>";
		echo dame_nombre_agente($agent_array[$a])."</td>";
		echo "<td class='$tdcolor'>";
		echo dame_nombre_modulo_agentemodulo($module_array[$a])."</td>";
		echo "<td class='$tdcolor'>";
		echo $weight_array[$a]."</td>";
		echo "<td class='$tdcolor' align='center'>";
		echo "<input type=checkbox name='delete_$a' value='".$module_array[$a]."'></td></tr>";
	}
	echo "</table>";
	echo "<table width='500px'>";
	echo "<tr><td align='right'><input type=submit name='update_agent' class='sub delete' value='".$lang_label["delete"]."'>";
	echo "</table>";
	echo "</form>";
}

// --------------------------------------
// Parse chunkdata and render graph
// --------------------------------------
if (($render == 1) && (isset($modules))) {
	// parse chunk
	echo "<h3>".$lang_label["combined_image"]."</h3>";
	echo "<table class='databox'>";
	echo "<tr><td>";
	echo "<img src='reporting/fgraph.php?tipo=combined&id=$modules&weight_l=$weights&label=Combined%20Sample%20Graph&height=$height&width=$width&stacked=$stacked&period=$period' border=1 alt=''>";
	echo "</td></tr></table>";

}

// -----------------------
// SOURCE AGENT TABLE/FORM
// -----------------------

if ( (!isset($_GET["add_module"]))){
	echo $lang_label["graph_builder"]."</h2>";
} else {
	echo "<h3>".$lang_label["graph_builder"]."</h3>";
}
echo "<table width='500' cellpadding=4 cellpadding=4 class='databox_color'>";
echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&get_agent=1'>";

if (isset($period))
    echo "<input type='hidden' name='period' value='$period'>";

echo "<tr>";
echo "<td class='datos'><b>".$lang_label["source_agent"]."</td>";
echo "</b>";

// Show combo with agents
echo "<td class='datos' colspan=2><select name='id_agent' style='width:180px;'>";
if ($id_agent != 0)
	echo "<option value='$id_agent'>".dame_nombre_agente($id_agent);
$sql1='SELECT * FROM tagente ORDER BY nombre';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	if ( $id_agent != $row["id_agente"])
		echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
}
echo '</select>';
if (isset($chunkdata))
	echo "<input type='hidden' name='chunk' value='$chunkdata'>";

echo "<td class='datos' colspan=1 align='right'><input type=submit name='update_agent' class='sub upd' value='".$lang_label["get_info"]."'>";
echo "</form>";

// -----------------------
// SOURCE MODULE FORM
// -----------------------
echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&add_module=1'>";
if (isset($chunkdata))
	echo "<input type='hidden' name='chunk' value='$chunkdata'>";

if (isset($id_agent))
	echo "<input type='hidden' name='id_agent' value='$id_agent'>";

echo "<tr><td class='datos2'>";
echo "<b>".$lang_label["modules"]."</b>";
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
echo "</select>";

echo "<tr><td class='datos'>";
echo "<b>".$lang_label["Factor"]."</b></td>";
echo "<td class='datos'>";
echo "<input type='text' name='factor' value='$factor' size=6></td>";
echo "<td class='datos'>";
echo "<b>".$lang_label["width"]."</b>";
echo "<td class='datos'>";
echo "<input type='text' name='width' value='$width' size=6></td>";


echo "<tr><td class='datos2'>";
echo "<b>".$lang_label["render_now"]."</b></td>";
echo "<td class='datos2'>";
echo "<select name='render'>";
if ($render == 1){
	echo "<option value=1>Yes</option>";
	echo "<option value=0>No</option>";
} else {
	echo "<option value=0>No</option>";
	echo "<option value=1>Yes</option>";
}
echo "</select></td>";
echo "<td class='datos2'>";
echo "<b>".$lang_label["height"]."</b></td>";
echo "<td class='datos2'>";
echo "<input type='text' name='height' value='$height' size=6>";


switch ($period) {
	case 3600: 	$period_label = "Hour";
			break;
	case 7200: 	$period_label = "2 Hours";
			break;
	case 10800: 	$period_label = "3 Hours";
			break;
	case 21600: 	$period_label = "6 Hours";
			break;
	case 43200: 	$period_label = "12 Hours";
			break;
	case 86400: 	$period_label = "Day";
			break;
	case 172800: 	$period_label = "Two days";
			break;
	case 345600: 	$period_label = "Four days";
			break;
	case 604800: 	$period_label = "Last Week";
			break;
	case 1296000: 	$period_label = "15 Days";
			break;
	case 2592000: 	$period_label = "Last Month";
			break;
	case 5184000: 	$period_label = "Two Month";
			break;
	case 15552000: 	$period_label = "Six Months";
			break;
	case 31104000: 	$period_label = "One year";
			break;
	default: 	$period_label = "Day";
}


echo "<tr><td class='datos'>";
echo "<b>".$lang_label["period"]."</b></td>";
echo "<td class='datos'>";

echo "<select name='period'>";
if ($period==0) {
	echo "<option value=86400>".$period_label."</option>";
} else {
	echo "<option value=$period>".$period_label."</option>";
}
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
echo "</select>";

echo "<td class='datos'>";
echo "<b>".$lang_label["view_events"]."</b></td>";
echo "<td class='datos'>";
echo "<select name='events'>";
if ($events == 1){
	echo "<option value=1>Yes</option>";
	echo "<option value=0>No</option>";
} else {
	echo "<option value=0>No</option>";
	echo "<option value=1>Yes</option>";
}
echo "</select></td>";

echo "<tr>";
echo "<td class='datos2'>";
echo "<b>".lang_string ("Stacked")."</b></td>";
echo "<td class='datos2'>";
echo "<select name='stacked'>";
if ($stacked == 1){
	echo "<option value=1>Yes</option>";
	echo "<option value=0>No</option>";
} else {
	echo "<option value=0>No</option>";
	echo "<option value=1>Yes</option>";
}
echo "</select></td>";


/*
echo "<td class='datos'>";
echo "<b>Show alert limit</b>";
echo "<td class='datos'>";
echo "<select name='alerts'>";
if ($alerts == 1){
	echo "<option value=1>Yes";
	echo "<option value=0>No";
} else {
	echo "<option value=0>No";
	echo "<option value=1>Yes";
}
echo "</select>";
*/
echo "</tr></table>";
echo "<table width='500px'>";
echo "<tr><td align='right'><input type=submit name='update_agent' class='sub upd' value='".$lang_label["add"]."/".$lang_label["redraw"]."'>";

echo "</form>";
echo "</td></tr></table>";

// -----------------------
// STORE GRAPH FORM
// -----------------------

// If we have something to save..
if (isset($module_array)){
	echo "<h3>".$lang_label["graph_store"]."</h3>";
	echo "<table width='500' cellpadding=4 cellpadding=4 class='databox_color'>";
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&store_graph=1'>";

	// hidden fields with data begin
	echo "<input type='hidden' name='module_number' value='".count($module_array)."'>";
	echo "<input type='hidden' name='width' value='$width'>";
	echo "<input type='hidden' name='height' value='$height'>";
	echo "<input type='hidden' name='period' value='$period'>";
	echo "<input type='hidden' name='events' value='$events'>";
	echo "<input type='hidden' name='stacked' value='$stacked'>";

	for ($a=0; $a < count($module_array); $a++){
			$id_agentemodulo = $module_array[$a];
			$id_agentemodulo_w = $weight_array[$a];
			echo "<input type='hidden' name='module_$a' value='$id_agentemodulo'>";
			echo "<input type='hidden' name='module_weight_$a' value='$id_agentemodulo_w'>";
	}
	// hidden fields end

	echo "<tr>";
	echo "<td class='datos'><b>".$lang_label["name"]."</b></td>";
	echo "</b>";
	echo "<td class='datos'><input type='text' name='name' size='35'>";

	echo "<td class='datos'><b>".$lang_label["private"]."</b></td>";
	echo "</b>";
	echo "<td class='datos'><select name='private'>";
	echo "<option value=0>".$lang_label["no"]."</option>";
	echo "<option value=1>".$lang_label["yes"]."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr>";
	echo "<td class='datos2'><b>".$lang_label["description"]."</b></td>";
	echo "</b>";
	echo "<td class='datos2' colspan=4><textarea name='description' style='height:45px;' cols=55 rows=2>";
	echo "</textarea>";
	echo "</td></tr></table>";
	echo "<table width='500px'>";
	echo "<tr><td align='right'><input type=submit name='store' class='sub wand' value='".$lang_label["store"]."'>";


	echo "</form>";
	echo "</table>";
}

?>
