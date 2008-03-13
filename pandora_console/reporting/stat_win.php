<?php
// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to Pandora FMS 1.2 graph code and new XML reporting template management

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Global & session management
include ('../include/config.php');
session_start();

include ('../include/functions.php');
include ('../include/functions_db.php');
include ('../include/languages/language_'.$config['language'].'.php');

// Access control
if (comprueba_login() != 0) {
	$REMOTE_ADDR = getenv ("REMOTE_ADDR");
	audit_db("Unknown",$REMOTE_ADDR, "ACL Violation","Trying to access graph window without auth");
	require ("../general/noaccess.php");
	exit;
}
	
// Parsing the refresh before sending any header
if (isset($_GET['refresh']) and is_numeric($_GET['refresh']) and $_GET['refresh']>0) {
	header( 'refresh: ' . $_GET['refresh'] );
	}
echo '<html>
<head><title>Pandora FMS Graph</title>
<link rel="stylesheet" href="../include/styles/pandora_minimal.css" type="text/css">';
;

// Get input parameters

if (isset($_GET["label"]))
	$label = entrada_limpia($_GET["label"]);
	
if (!isset($_GET["period"]) OR (!isset($_GET["id"]))) {
	echo "<h3 class='error'>".$lang_label["graf_error"]."</h3>";
	exit;
}
if (isset($_GET["draw_events"]))
	$draw_events = entrada_limpia($_GET["draw_events"]);
else
	$draw_events = 0;

if (isset($_GET["period"]))
	$period = entrada_limpia($_GET["period"]);
else
	$period = 3600; // 1 hour (the most fast query possible)

switch ($period) {
	case 3600: 	$period_label = $lang_label["hour"];
			break;
	case 7200: 	$period_label = $lang_label["2_hours"];
			break;
	case 21600: 	$period_label = $lang_label["6_hours"];
			break;
	case 43200: 	$period_label = $lang_label["12_hours"];
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
	default: 	$period_label = human_time_description_raw ($period);
}
	
if (isset($_GET["draw_alerts"]))
	$draw_alerts = entrada_limpia($_GET["draw_alerts"]);
else
	$draw_alerts = 0;
if (isset($_GET["avg_only"]))
	$avg_only = entrada_limpia($_GET["avg_only"]);
else
	$avg_only = 0;
if (isset($_GET["refresh"]))
	$refresh = entrada_limpia($_GET["refresh"]);
else
	$refresh = 0;
if (isset($_GET["period"]))
	$period = entrada_limpia($_GET["period"]);
else
	$period = 86400; // 1 day default period
if (isset($_GET["id"]))
	$id = entrada_limpia($_GET["id"]);
else
	$id = 0;
if (isset($_GET["width"]))
	$width = entrada_limpia($_GET["width"]);
else
	$width = 525;
if (isset($_GET["height"]))
	$height = entrada_limpia ($_GET["height"]);
else
	$height = 220;

if (isset($_GET["label"]))
	$label = entrada_limpia ($_GET["label"]);
else
	$label = "";

if (isset($_GET["zoom"])){
	$zoom = entrada_limpia ($_GET["zoom"]);
	if ($zoom > 1){
		$height=$height*($zoom/2.1);
		$width=$width*($zoom/1.4);
	}
}
else
	$zoom = "1";

if ($zoom > 1) {
	echo "
		<script type='text/javascript'>
			window.resizeTo($width + 10, $height + 80);
		</script>
	";
}

$graph_type = "sparse";
if (isset($_GET["type"]))
	$graph_type = entrada_limpia($_GET["type"]);


echo "<img src='fgraph.php?tipo=$graph_type&draw_alerts=$draw_alerts&draw_events=$draw_events&id=$id&zoom=$zoom&label=$label&height=$height&width=$width&period=$period&avg_only=$avg_only' border=0 alt=''>";

	echo "<table width=450 cellspacing=1 cellpadding=1 class='databox' style='margin-left: 20px'>";
		echo "<tr><td><b>";
		echo $lang_label["max_value"]." </b>: ". format_for_graph(return_moduledata_max_value ($id, $period));
		echo "</td><td><b>";
		echo $lang_label["avg_value"]." </b>: ". format_for_graph(return_moduledata_avg_value ($id, $period));
		echo "</td><td><b>";
		echo $lang_label["min_value"]." </b>: ". format_for_graph(return_moduledata_min_value ($id, $period));
		echo "</td></tr>";
	echo "</table>";

?>

<script type='text/javascript' src='../include/javascript/x_core.js'></script>
<script type='text/javascript' src='../include/javascript/x_event.js'></script>
<script type='text/javascript' src='../include/javascript/x_slide.js'></script>
<script type='text/javascript'><!--
	var defOffset = 2;
	var defSlideTime = 200;
	var tnActive = 0;
	var visibleMargin = 3;
	var menuW = 325;
	var menuH = 220;
	window.onload = function() {
		var d;
		d = xGetElementById('divmenu');
		d.termNumber = 1;
		xMoveTo(d, visibleMargin - menuW, 0);
		xShow(d);
		xAddEventListener(document, 'mousemove', docOnMousemove, false);
	}
	
	function docOnMousemove(evt) {
		var e = new xEvent(evt);
		var d = getTermEle(e.target);
		if (!tnActive) { // no def is active
			if (d) { // mouse is over a term, activate its def
				xSlideTo('divmenu', 0, xPageY(d), defSlideTime);
				tnActive = 1;
			}
		}
		else { // a def is active
			if (!d) { // mouse is not over a term, deactivate active def
				xSlideTo('divmenu', visibleMargin - menuW, xPageY(d), defSlideTime);
				tnActive = 0;
			}
		}
	}
	
	function getTermEle(ele) {
		//window.status = ele;
  		while(ele && !ele.termNumber) {
    			if (ele == document) return null;
    			ele = xParent(ele);
  		}
  		return ele;
	}
//-->
</script>
</head>
<body>

<div id='divmenu' class='menu'>
	<b>Pandora FMS Graph configuration menu</b><br>Please, make your changes and apply with <i>Reload</i> button

	<form method='get' action='stat_win.php'>
	<?php
	echo "<input type='hidden' name='id' value='$id'>";
	echo "<input type='hidden' name='label' value='$label'>";
	if (isset($_GET["type"]))
		echo "<input type='hidden' name='type' value='".$_GET["type"]."'>";
	?>
		<TABLE class='databox_frame' cellspacing=5>
		</td><td>
			<?php
			echo "<tr><td>";
			echo "Refresh time (sec)";
			echo "<td colspan=2>";
			echo "<input type='text' size=5 name='refresh' value='" . $refresh . "'>";

			echo "&nbsp;&nbsp;&nbsp;".$lang_label["avg_only"];
			if ($avg_only == 1)
				echo "<input type='checkbox' name='avg_only' value=1 CHECKED>";
			else
				echo "<input type='checkbox' name='avg_only' value=1>";

			echo "<tr><td>";
			echo "Zoom factor (1x)";
			echo "<td>";
			echo "<select name=zoom>";
			echo "<option value='$zoom'>"."x".$zoom;
			echo "<option value='1'>"."x1";
			echo "<option value='2'>"."x2";
			echo "<option value='3'>"."x3";
			echo "<option value='4'>"."x4";
			echo "</select>";

			echo "<tr><td>";
			echo "Range of time";
			echo "<td>";
			echo "<select name='period'>";
			echo "<option value=$period>".$period_label;
			echo "<option value=3600>".$lang_label["hour"];
			echo "<option value=7200>".$lang_label["2_hours"];
			echo "<option value=21600>".$lang_label["6_hours"];
			echo "<option value=43200>".$lang_label["12_hours"];
			echo "<option value=86400>".$lang_label["last_day"];
			echo "<option value=172800>".$lang_label["two_days"];
			echo "<option value=432000>".$lang_label["five_days"];
			echo "<option value=604800>".$lang_label["last_week"];
			echo "<option value=1296000>".$lang_label["15_days"];
			echo "<option value=2592000>".$lang_label["last_month"];
			echo "<option value=5184000>".$lang_label["two_month"];
			echo "<option value=15552000>".$lang_label["six_months"];
			echo "</select>";
			
			echo "<tr><td>";
			echo "Show events ";
			echo "<td>";
			if ($draw_events == 1)
				echo "<input type='checkbox' name='draw_events' CHECKED  value=1>";
			else
				echo "<input type='checkbox' name='draw_events'  value=1>";

		
			
			echo "<tr><td>";
			echo "Show alert ";
			echo "<td>";
			if ($draw_alerts == 1)
				echo "<input type='checkbox' name='draw_alerts' value=1  CHECKED>";
			else
				echo "<input type='checkbox' name='draw_alerts' value=1>";

		
			echo "<td>";
			echo "<input type='submit' class='sub next' value='GO'>";
			?>
		</td></tr>
		</table>
	</form>
	
</div>
</body>
</html>

