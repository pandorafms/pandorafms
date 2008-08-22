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

// Global & session management
require_once ('../include/config.php');
if (! isset($_SESSION["id_user"])) {
	session_start();
	session_write_close();
}

require_once ('../include/functions.php');
require_once ('../include/functions_db.php');

check_login ();

// Parsing the refresh before sending any header
$refresh = get_parameter ("refresh", -1);
if ($refresh != -1)
	header( 'refresh: ' . $_GET['refresh'] );

echo '<html>
<head><title>Pandora FMS Graph</title>
<link rel="stylesheet" href="../include/styles/pandora_minimal.css" type="text/css">';
echo "<script type='text/javaScript' src='../include/javascript/calendar.js'></script>";
echo "</head><body>";

// Get input parameters

$label = get_parameter ("label","");	
if (!isset($_GET["period"]) OR (!isset($_GET["id"]))) {
	echo "<h3 class='error'>".__('There was a problem locating the source of the graph')."</h3>";
	exit;
}

$period = get_parameter ( "period", 3600);

switch ($period) {
	case 3600: 	$period_label = __('One hour');
			break;
	case 7200: 	$period_label = __('Two hours');
			break;
	case 21600: 	$period_label = __('Six hours');
			break;
	case 43200: 	$period_label = __('12 hours');
			break;
	case 86400: 	$period_label = __('One day');
			break;
	case 172800: 	$period_label = __('Two days');
			break;
	case 432000: 	$period_label = __('Five days');
			break;
	case 604800: 	$period_label = __('One week');
			break;
	case 1296000: 	$period_label = __('15 days');
			break;
	case 2592000: 	$period_label = __('One month');
			break;
	case 5184000: 	$period_label = __('Two months');
			break;
	case 15552000: 	$period_label = __('Six months');
			break;
	default: 	$period_label = human_time_description_raw ($period);
}
	

$draw_alerts = get_parameter("draw_alerts", 0);
$avg_only = get_parameter ("avg_only", 0);
$period = get_parameter ("period", 86400);
$id = get_parameter ("id", 0);
$width = get_parameter ("width", 555);
$height = get_parameter ("height", 245);
$label = get_parameter ("label", "");
$start_date = get_parameter ("start_date", date("Y-m-d"));
$draw_events = get_parameter ("draw_events", 0);
$graph_type = get_parameter ("type", "sparse");
$zoom = get_parameter ("zoom", 1);
if ($zoom > 1){
	$height=$height*($zoom/2.1);
	$width=$width*($zoom/1.4);
}

if ($zoom > 1) {
	echo "
		<script type='text/javascript'>
			window.resizeTo($width + 10, $height + 80);
		</script>
	";
}

$current = date("Y-m-d");
if ($start_date != $current){
	$utime = strtotime ($start_date);
	echo "<img src='fgraph.php?tipo=$graph_type&draw_alerts=$draw_alerts&draw_events=$draw_events&id=$id&zoom=$zoom&label=$label&height=$height&width=$width&period=$period&avg_only=$avg_only&date=$utime' border=0 alt=''>";
} else 
	echo "<img src='fgraph.php?tipo=$graph_type&draw_alerts=$draw_alerts&draw_events=$draw_events&id=$id&zoom=$zoom&label=$label&height=$height&width=$width&period=$period&avg_only=$avg_only' border=0 alt=''>";

	echo "<table width=450 cellspacing=1 cellpadding=1 class='databox' style='margin-left: 20px'>";
		echo "<tr><td><b>";
		echo __('Max. Value')." </b>: ". format_for_graph(get_agent_module_value_max ($id, $period));
		echo "</td><td><b>";
		echo __('Avg. Value')." </b>: ". format_for_graph(get_agent_module_value_average ($id, $period));
		echo "</td><td><b>";
		echo __('Min. Value')." </b>: ". format_for_graph(get_agent_module_value_min ($id, $period));
		echo "</td></tr>";
	echo "</table>";

?>

<script type='text/javascript' src='../include/javascript/x_core.js'></script>
<script type='text/javascript' src='../include/javascript/x_event.js'></script>
<script type='text/javascript' src='../include/javascript/x_slide.js'></script>
<script type='text/javascript'><!--
	var defOffset = 2;
	var defSlideTime = 220;
	var tnActive = 0;
	var visibleMargin = 5;
	var menuW = 325;
	var menuH = 310;
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
			echo __('Refresh time');
			echo "<td colspan=2>";
			echo "<input type='text' size=5 name='refresh' value='" . $refresh . "'>";

			echo "&nbsp;&nbsp;&nbsp;".__('Avg. Only');
			if ($avg_only == 1)
				echo "<input type='checkbox' name='avg_only' value=1 CHECKED>";
			else
				echo "<input type='checkbox' name='avg_only' value=1>";

			echo "<tr><td>";
			echo __('Begin date');
			echo "<td>";
			echo "<input type='text' id='start_date' name='start_date' size=10 value='".substr($start_date,0,10)."'><img src='../images/calendar_view_day.png' onclick='scwShow(scwID(\"start_date\"),this);'> ";

			echo "<tr><td>";
			echo __('Zoom factor');
			echo "<td>";
			echo "<select name=zoom>";
			echo "<option value='$zoom'>"."x".$zoom;
			echo "<option value='1'>"."x1";
			echo "<option value='2'>"."x2";
			echo "<option value='3'>"."x3";
			echo "<option value='4'>"."x4";
			echo "</select>";

			echo "<tr><td>";
			echo __('Time range');
			echo "<td>";
			echo "<select name='period'>";
			echo "<option value=$period>".$period_label;
			echo "<option value=3600>".__('One hour');
			echo "<option value=7200>".__('Two hours');
			echo "<option value=21600>".__('Six hours');
			echo "<option value=43200>".__('12 hours');
			echo "<option value=86400>".__('One day');
			echo "<option value=172800>".__('Two days');
			echo "<option value=432000>".__('Five days');
			echo "<option value=604800>".__('One week');
			echo "<option value=1296000>".__('15 days');
			echo "<option value=2592000>".__('One month');
			echo "<option value=5184000>".__('Two months');
			echo "<option value=15552000>".__('Six months');
			echo "</select>";
			
			echo "<tr><td>";
			echo __('Show events');
			echo "<td>";
			if ($draw_events == 1)
				echo "<input type='checkbox' name='draw_events' CHECKED  value=1>";
			else
				echo "<input type='checkbox' name='draw_events'  value=1>";

	
			echo "<tr><td>";
			echo __('Show alert');
			echo "<td>";
			if ($draw_alerts == 1)
				echo "<input type='checkbox' name='draw_alerts' value=1  CHECKED>";
			else
				echo "<input type='checkbox' name='draw_alerts' value=1>";

		
			echo "<td>";
			echo "<input type='submit' class='sub next' value='GO'>";
echo "<br>";
			?>
		</td></tr>
		</table>
	</form>
	
</div>
</body>
</html>

