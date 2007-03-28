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
$period = "";
$alerts= "";
$events = "";
$factor = 1;

if (isset($_GET["get_agent"])) {
 	$id_agent = $_POST["id_agent"];
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
 	$graphname = $_POST["graphname"];
 	$render = $_POST["render"];
 	$alerts = $_POST["alerts"];
 	$chunkdata = $_POST["chunk"];
	$events = $_POST["events"];
	$factor = $_POST["factor"];
 	if ($_POST["width"]!= "")
 		$width = $_POST["width"];
 	if ($_POST["height"]!= "")
 		$height = $_POST["height"];
 	if ($id_module > 0){	
		if ($chunkdata == "")
			$chunkdata = "$id_agent,$id_module,$factor";
		else
			$chunkdata = $chunkdata."|$id_agent,$id_module,$factor";
	}
}
 
// Parse CHUNK information into showable information
// Split id to get all parameters
if (! isset($_GET["delete_module"])){
	if (isset($chunkdata)) {
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


if (isset($chunk1)) {
	echo "<h3>".$lang_label["graph_builder_modulelist"]."</h3>";
	echo "<form method='post' action='index.php?sec=reporting&sec2=operation/reporting/graph_builder&delete_module=1'>";
	if (isset($chunkdata))
		echo "<input type='hidden' name='chunk' value='$chunkdata'>";
	if (isset($id_agent))
		echo "<input type='hidden' name='id_agent' value='$id_agent'>";
	echo "<table width='500' cellpadding=4 cellpadding=4>";
	echo "<tr><th>Agent<th>Module<th>Weight<th>Delete";
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
		echo dame_nombre_agente($agent_array[$a]);
		echo "<td class='$tdcolor'>";
		echo dame_nombre_modulo_agentemodulo($module_array[$a]);
		echo "<td class='$tdcolor'>";
		echo $weight_array[$a];
		echo "<td class='$tdcolor'>";
		echo "<input style='height=2px;' type=checkbox name='delete_$a' value='".$module_array[$a]."'>";
	}
	echo "<tr><td colspan=4 align='right'><input type=submit name='update_agent' class=sub value='".$lang_label["delete"]."'>";
	echo "</table>";
	echo "</form>";
}

// -----------------------
// SOURCE AGENT TABLE/FORM
// -----------------------
echo "<h3>".$lang_label["graph_builder"]."</h3>";
echo "<table width='500' cellpadding=4 cellpadding=4>";
echo "<form method='post' action='index.php?sec=reporting&sec2=operation/reporting/graph_builder&get_agent=1'>";
echo "<tr>";
echo "<td class='datos'><b>".$lang_label["source_agent"];
echo "</b>";

// Show combo with agents
echo "<td class='datos'><select name='id_agent' style='width:180px;'>";
if ($id_agent != 0)
	echo "<option value='$id_agent'>".dame_nombre_agente($id_agent);
$sql1='SELECT * FROM tagente order by nombre';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	if ( $id_agent != $row["id_agente"])
		echo "<option value=".$row["id_agente"].">".$row["nombre"];
}
echo '</select>';
if (isset($chunkdata))
	echo "<input type='hidden' name='chunk' value='$chunkdata'>";

echo "<td class='datos' colspan=2 align='right'><input type=submit name='update_agent' class=sub value='".$lang_label["get_info"]."'>";
echo "</form>";

// -----------------------
// SOURCE MODULE FORM
// -----------------------
echo "<form method='post' action='index.php?sec=reporting&sec2=operation/reporting/graph_builder&add_module=1'>";
if (isset($chunkdata))
	echo "<input type='hidden' name='chunk' value='$chunkdata'>";

if (isset($id_agent))
	echo "<input type='hidden' name='id_agent' value='$id_agent'>";

echo "<tr><td class='datos2'>";
echo "<b>".$lang_label["modules"]."</b>";
echo "<td class='datos2' colspan=3>";
echo "<select name='id_module' size=1 style='width:180px;'>";
		echo "<option value=-1> --";
if ($id_agent != 0){
	// Populate Module/Agent combo
	$sql1="SELECT * FROM tagente_modulo WHERE id_agente = ".$id_agent. " order by nombre";
	$result = mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		echo "<option value=".$row["id_agente_modulo"].">".$row["nombre"];
	}
}
echo "</select>";

echo "<tr><td class='datos'>";
echo "<b>Factor</b>";
echo "<td class='datos'>";
echo "<input type='text' name='factor' value='$factor' size=6>";
echo "<td class='datos'>";
echo "<b>Width</b>";
echo "<td class='datos'>";
echo "<input type='text' name='width' value='$width' size=6>";


echo "<tr><td class='datos2'>";
echo "<b>Graph Name</b>";
echo "<td class='datos2'>";
echo "<input type='text' name='graphname' value='$name' size=25>";
echo "<td class='datos2'>";
echo "<b>Height</b>";
echo "<td class='datos2'>";
echo "<input type='text' name='height' value='$height' size=6>";


echo "<tr><td class='datos'>";
echo "<b>Period</b>";
echo "<td class='datos'>";
echo "<select name='period'>";
if ($period != ""){
	if ($period == 3600)
		echo "<option value='".$period."'>Last Hour";
	elseif ($period == 86400)
		echo "<option value='".$period."'>Last day";
	elseif ($period == 604800)
		echo "<option value='".$period."'>Last week";
	elseif ($period == 2592000)
		echo "<option value='".$period."'>Last month";
}
echo "<option value=86400>Last day";
echo "<option value=3600>Last hour";
echo "<option value=604800>Last week";
echo "<option value=2592000>Last month";
echo "</select>";

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

echo "<tr><td class='datos2'>";
echo "<b>Render now</b>";
echo "<td class='datos2'>";
echo "<select name='render'>";
if ($render == 1){
	echo "<option value=1>Yes";
	echo "<option value=0>No";
} else {
	echo "<option value=0>No";
	echo "<option value=1>Yes";
}
echo "</select>";
echo "<td class='datos2'>";
echo "<b>Show events</b>";
echo "<td class='datos2'>";
echo "<select name='events'>";
if ($events == 1){
	echo "<option value=1>Yes";
	echo "<option value=0>No";
} else {
	echo "<option value=0>No";
	echo "<option value=1>Yes";
}
echo "</select>";

echo "<tr><td colspan=4 align='right'><input type=submit name='update_agent' class=sub value='".$lang_label["add"]."/".$lang_label["redraw"]."'>";

echo "</form>";
echo "</table>";

// Parse chunkdata and render graph
if ($render == 1){
	// parse chunk
	echo "<h3>".$lang_label["combined_image"]."</h3>";
	echo "<img  src='reporting/fgraph.php?tipo=combined&id=$modules&weight_l=$weights&label=$graphname&height=$height&width=$width&period=$period' border=1 alt=''>";

}
/*
if (isset($chunkdata)){
	echo "<form method='post' action='index.php?sec=reporting&sec2=operation/reporting/graph_builder&save_graph=1'>";
	echo "<input type='hidden' name='chunk' value='$chunkdata'>";
	echo "<table width='500' cellpadding=4 cellpadding=4>";
	echo "<tr><td class='datos2'>".$lang_name["custom_graph_name"];
	echo "<td class='datos2'><input type='text' value='' size=20 name='graph_name'>";
	echo "<td class='datos2'><input type=submit name='save' class=sub value='".$lang_label["save"]."'>";
	echo "</table>";
}
*/
?>
