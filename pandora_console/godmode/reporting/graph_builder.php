<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


// Login check
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

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

$add_module = (bool) get_parameter ('add_module');

if (isset ($_GET["store_graph"])) {
	$name = get_parameter_post ("name");
	$description = get_parameter_post ("description");
	$module_number = get_parameter_post ("module_number");
	$private = get_parameter_post ("private");
	$width = get_parameter_post ("width");
	$height = get_parameter_post ("height");
	$events = get_parameter_post ("events");
	$stacked = get_parameter ("stacked", 0);
	if ($events == "") // Temporal workaround
		$events = 0;
	$period = get_parameter_post ("period");
	// Create graph
	$sql = "INSERT INTO tgraph
		(id_user, name, description, period, width, height, private, events, stacked) VALUES
		('".$config['id_user']."',
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
				$id_agentemodulo = get_parameter_post ("module_".$a);
				$id_agentemodulo_w = get_parameter_post ("module_weight_".$a);
				$sql = "INSERT INTO tgraph_source (id_graph, id_agent_module, weight) VALUES
					($id_graph, $id_agentemodulo, $id_agentemodulo_w)";
				//echo "DEBUG $sql<br>";
				mysql_query($sql);
			}
			echo "<h3 class='suc'>".__('Graph stored successfully')."</h3>";
		} else
			echo "<h3 class='error'>".__('There was a problem storing Graph')."</h3>";
	} else 
		echo "<h3 class='error'>".__('There was a problem storing Graph')."</h3>";
}

if (isset ($_GET["get_agent"])) {
	$id_agent = $_POST["id_agent"];
	if (isset($_POST["chunk"]))
		$chunkdata = $_POST["chunk"];
}

if (isset ($_GET["delete_module"] )) {
	$chunkdata = $_POST["chunk"];
	if (isset($chunkdata)) {
		$chunk1 = array();
		$chunk1 = split ("\|", $chunkdata);
		$modules="";$weights="";
		for ($a = 0; $a < count ($chunk1); $a++) {
			if (isset ($_POST["delete_$a"])) {
				$id_module = $_POST["delete_$a"];
				$deleted_id[]=$id_module;
			}	
		}
		$chunkdata2 = "";
		$module_array = array ();
		$weight_array = array ();
		$agent_array = array ();
		for ($a = 0; $a < count ($chunk1); $a++) {
			$chunk2[$a] = array();
			$chunk2[$a] = split (",", $chunk1[$a]);
			$skip_module =0;
			for ($b = 0; $b < count ($deleted_id); $b++) {
				if ($deleted_id[$b] == $chunk2[$a][1]) {
					$skip_module = 1;
				}
			}
			if (($skip_module == 0) && (strpos ($modules, $chunk2[$a][1]) == 0)) {  // Skip
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

if ($add_module) {
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
if (! isset($_GET["delete_module"])) {
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

echo "<h2>".__('Reporting')." &gt; ";
if (isset ($chunk1)) {
	echo __('Graph builder module list')."</h2>";
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&delete_module=1'>";
	if (isset($chunkdata))
		echo "<input type='hidden' name='chunk' value='$chunkdata'>";
	if ($id_agent)
		echo "<input type='hidden' name='id_agent' value='$id_agent'>";
	if ($period)
		echo "<input type='hidden' name='period' value='$period'>";

	echo "<table width='500' cellpadding=4 cellpadding=4 class='databox'>";
	echo "<tr>
	<th>".__('Agent')."</th>
	<th>".__('Module')."</th>
	<th>".__('Weight')."</th>
	<th>".__('Delete')."</th>";
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
	echo "<tr><td align='right'><input type=submit name='update_agent' class='sub delete' value='".__('Delete')."'>";
	echo "</table>";
	echo "</form>";
}

// --------------------------------------
// Parse chunkdata and render graph
// --------------------------------------
if (($render == 1) && (isset($modules))) {
	// parse chunk
	echo "<h3>".__('Combined image render')."</h3>";
	echo "<table class='databox'>";
	echo "<tr><td>";
	echo "<img src='reporting/fgraph.php?tipo=combined&id=$modules&weight_l=$weights&label=Combined%20Sample%20Graph&height=$height&width=$width&stacked=$stacked&period=$period' border=1 alt=''>";
	echo "</td></tr></table>";

}

// -----------------------
// SOURCE AGENT TABLE/FORM
// -----------------------

echo __('Graph builder')."</h2>";
echo "<table width='500' cellpadding='4' cellpadding='4' class='databox_color'>";
echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder'>";
print_input_hidden ('add_module', 1);
if (isset($period))
    echo "<input type='hidden' name='period' value='$period'>";

echo "<tr>";
echo "<td class='datos'><b>".__('Source agent')."</td>";
echo "</b>";

// Show combo with agents
echo "<td class='datos' colspan=2>";

print_select_from_sql ('SELECT id_agente, nombre FROM tagente WHERE disabled = 0 ORDER BY nombre', 'id_agent', $id_agent, '', '--', 0);

// SOURCE MODULE FORM
if (isset ($chunkdata))
	echo "<input type='hidden' name='chunk' value='$chunkdata'>";

echo "<tr><td class='datos2'>";
echo "<b>".__('Modules')."</b>";
echo "<td class='datos2' colspan=3>";
echo "<select id='id_module' name='id_module' size=1 style='width:180px;'>";
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
echo "<b>".__('Factor')."</b></td>";
echo "<td class='datos'>";
echo "<input type='text' name='factor' value='$factor' size=6></td>";
echo "<td class='datos'>";
echo "<b>".__('Width')."</b>";
echo "<td class='datos'>";
echo "<input type='text' name='width' value='$width' size=6></td>";


echo "<tr><td class='datos2'>";
echo "<b>".__('Render now')."</b></td>";
echo "<td class='datos2'>";
echo "<select name='render'>";
if ($render == 1){
	echo "<option value=1>".__('Yes')."</option>";
	echo "<option value=0>".__('No')."</option>";
} else {
	echo "<option value=0>".__('No')."</option>";
	echo "<option value=1>".__('Yes')."</option>";
}
echo "</select></td>";
echo "<td class='datos2'>";
echo "<b>".__('Height')."</b></td>";
echo "<td class='datos2'>";
echo "<input type='text' name='height' value='$height' size=6>";


switch ($period) {
	case 3600:
		$period_label = __('1 hour');
		break;
	case 7200:
		$period_label = __('2 hours');
		break;
	case 10800:
		$period_label = __('3 hours');
		break;
	case 21600:
		$period_label = __('6 hours');
		break;
	case 43200:
		$period_label = __('12 hours');
		break;
	case 86400:
		$period_label = __('1 day');
		break;
	case 172800:
		$period_label = __('2 days');
		break;
	case 345600:
		$period_label = __('4 days');
		break;
	case 604800:
		$period_label = __('Last week');
		break;
	case 1296000:
		$period_label = __('15 days');
		break;
	case 2592000:
		$period_label = __('Last month');
		break;
	case 5184000:
		$period_label = __('2 months');
		break;
	case 15552000:
		$period_label = __('6 months');
		break;
	case 31104000:
		$period_label = __('1 year');
		break;
	default:
		$period_label = __('1 day');
}


echo "<tr><td class='datos'>";
echo "<b>".__('Period')."</b></td>";
echo "<td class='datos'>";

echo "<select name='period'>";
if ($period==0) {
	echo "<option value=86400>".$period_label."</option>";
} else {
	echo "<option value=$period>".$period_label."</option>";
}
echo "<option value=3600>".__('1 hour')."</option>";
echo "<option value=7200>".__('2 hours')."</option>";
echo "<option value=10800>".__('3 hours')."</option>";
echo "<option value=21600>".__('6 hours')."</option>";
echo "<option value=43200>".__('12 hours')."</option>";
echo "<option value=86400>".__('Last day')."</option>";
echo "<option value=172800>".__('2 days')."</option>";
echo "<option value=604800>".__('Last week')."</option>";
echo "<option value=1296000>".__('15 days')."</option>";
echo "<option value=2592000>".__('Last month')."</option>";
echo "<option value=5184000>".__('2 months')."</option>";
echo "<option value=15552000>".__('6 months')."</option>";
echo "</select>";

echo "<td class='datos'>";
echo "<b>".__('View events')."</b></td>";
echo "<td class='datos'>";
echo "<select name='events'>";
if ($events == 1){
	echo "<option value=1>".__('Yes')."</option>";
	echo "<option value=0>".__('No')."</option>";
} else {
	echo "<option value=0>".__('No')."</option>";
	echo "<option value=1>".__('Yes')."</option>";
}
echo "</select></td>";

echo "<tr>";
echo "<td class='datos2'>";
echo "<b>".__('Stacked')."</b></td>";
echo "<td class='datos2'>";


$stackeds[0] = __('Area');
$stackeds[1] = __('Stacked area');
$stackeds[2] = __('Line');
print_select ($stackeds, 'stacked', $stacked, '', '', 0);
echo "</td>";


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
echo "<tr><td align='right'><input type=submit name='update_agent' class='sub upd' value='".__('Add')."/".__('Redraw')."'>";

echo "</form>";
echo "</td></tr></table>";

// -----------------------
// STORE GRAPH FORM
// -----------------------

// If we have something to save..
if (isset($module_array)){
	echo "<h3>".__('Custom graph store')."</h3>";
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
	echo "<td class='datos'><b>".__('Name')."</b></td>";
	echo "</b>";
	echo "<td class='datos'><input type='text' name='name' size='35'>";

	echo "<td class='datos'><b>".__('Private')."</b></td>";
	echo "</b>";
	echo "<td class='datos'><select name='private'>";
	echo "<option value=0>".__('No')."</option>";
	echo "<option value=1>".__('Yes')."</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr>";
	echo "<td class='datos2'><b>".__('Description')."</b></td>";
	echo "</b>";
	echo "<td class='datos2' colspan=4><textarea name='description' style='height:45px;' cols=55 rows=2>";
	echo "</textarea>";
	echo "</td></tr></table>";
	echo "<table width='500px'>";
	echo "<tr><td align='right'><input type=submit name='store' class='sub wand' value='".__('Store')."'>";


	echo "</form>";
	echo "</table>";
}

?>


<script type="text/javascript" src="include/javascript/jquery.js"></script>

<script language="javascript" type="text/javascript">

function agent_changed () {
	var id_agent = this.value;
	$('#id_module').fadeOut ('normal', function () {
		$('#id_module').empty ();
		var inputs = [];
		inputs.push ("id_agent=" + id_agent);
		inputs.push ("get_agent_modules_json=1");
		inputs.push ("page=operation/agentes/ver_agente");
		jQuery.ajax ({
			data: inputs.join ("&"),
			type: 'GET',
			url: action="ajax.php",
			timeout: 10000,
			dataType: 'json',
			success: function (data) {
				$('#id_module').append ($('<option></option>').attr ('value', 0).text ("--"));
				jQuery.each (data, function (i, val) {
					s = html_entity_decode (val['nombre']);
					$('#id_module').append ($('<option></option>').attr ('value', val['id_agente_modulo']).text (s));
				});
				$('#id_module').fadeIn ('normal');
			}
		});
	});
}

$(document).ready (function () {
	$('#id_agent').change (agent_changed);
}); 
</script>
