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
if (!isset ($config["auth"])) {
	require_once ($config["homedir"]."/include/auth/mysql.php");
} else {
	require_once ($config["homedir"]."/include/auth/".$config["auth"]["scheme"].".php");
}
	
if (! isset($_SESSION["id_user"])) {
	session_start();
	session_write_close();
}

require_once ('../include/functions.php');
require_once ('../include/functions_db.php');

check_login ();

// Parsing the refresh before sending any header
$refresh = (int) get_parameter ("refresh", -1);
if ($refresh > 0) {
	header('Refresh: '.$refresh);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Pandora FMS Graph</title>
<link rel="stylesheet" href="../include/styles/pandora_minimal.css" type="text/css" />
<script type='text/javaScript' src='../include/javascript/calendar.js'></script>
<script type='text/javascript' src='../include/javascript/x_core.js'></script>
<script type='text/javascript' src='../include/javascript/x_event.js'></script>
<script type='text/javascript' src='../include/javascript/x_slide.js'></script>
<script type='text/javascript'><!--
var defOffset = 2;
var defSlideTime = 220;
var tnActive = 0;
var visibleMargin = 15;
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
</head><body>
<?php

// Get input parameters
$label = get_parameter ("label","");	
if (!isset($_GET["period"]) OR (!isset($_GET["id"]))) {
	echo "<h3 class='error'>".__('There was a problem locating the source of the graph')."</h3>";
	exit;
}

$period = get_parameter ( "period", 3600);
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

if ($zoom > 1) {
	$height = $height * ($zoom / 2.1);
	$width = $width * ($zoom / 1.4);

	echo "<script type='text/javascript'>window.resizeTo($width + 10, $height + 80);</script>";
}

$utime = get_system_time ();
$current = date("Y-m-d", $utime);
$image = "fgraph.php?tipo=".$graph_type."&draw_alerts=".$draw_alerts."&draw_events=".$draw_events."&id=".$id."&zoom=".$zoom."&label=".$label."&height=".$height."&width=".$width."&period=".$period."&avg_only=".$avg_only;

if ($start_date != $current){
	$image .= "&date=".$utime;
	print_image ($image, false, array ("border" => 0));
} else {
	print_image ($image, false, array ("border" => 0));
	
	echo '<table width="450" cellspacing="1" cellpadding="1" class="databox" style="margin-left: 20px">';
	echo '<tr><td><b>'.__('Max. Value').'</b>: '.format_for_graph (get_agentmodule_data_max ($id, $period));
	echo '</td><td><b>'.__('Avg. Value').'</b>: '.format_for_graph (get_agentmodule_data_average ($id, $period));
	echo '</td><td><b>'.__('Min. Value').'</b>: '.format_for_graph (get_agentmodule_data_min ($id, $period));
	echo '</td></tr></table>';
}

echo '<div id="divmenu" class="menu"><b>'.__('Pandora FMS Graph configuration menu').'</b><br />'.__('Please, make your changes and apply with the <i>Reload</i> button');
echo '<form method="get" action="stat_win.php">';
print_input_hidden ("id", $id);
print_input_hidden ("label", $label);

if (isset($_GET["type"])) {
	$type = get_parameter_get ("type");
	print_input_hidden ("type", $type);
}
echo '<table class="databox_frame" cellspacing="5">';
echo '<tr><td>'.__('Refresh time').'</td><td colspan="2">';

print_input_text ("refresh", $refresh, '', 5);

echo '&nbsp;&nbsp;&nbsp;'.__('Avg. Only');

print_checkbox ("avg_only", 1, (bool) $avg_only);

echo '<tr><td>'.__('Begin date').'</td><td>';

print_input_text ("start_date", substr ($start_date, 0, 10),'', 10);
print_image ("../images/calendar_view_day.png", false, array ("onclick" => "scwShow(scwID('text-start_date'),this);"));

echo '</td></tr><tr><td>'.__('Zoom factor').'</td>';
echo '<td>';

$options = array ();
$options[$zoom] = 'x'.$zoom;
$options[1] = 'x1';
$options[2] = 'x2';
$options[3] = 'x3';
$options[4] = 'x4';

print_select ($options, "zoom", $zoom);

echo '</td></tr><tr><td>'.__('Time range').'</td><td>';

$options = array ();
$options[3600] = human_time_description_raw (3600);
$options[7200] = human_time_description_raw (7200);
$options[21600] = human_time_description_raw (21600);
$options[43200] = human_time_description_raw (43200);
$options[86400] = human_time_description_raw (86400);
$options[172800] = human_time_description_raw (172800);
$options[432000] = human_time_description_raw (432000);
$options[604800] = human_time_description_raw (604800);
$options[1296000] = human_time_description_raw (1296000);
$options[2592000] = human_time_description_raw (2592000);
$options[5184000] = human_time_description_raw (5184000);
$options[15552000] = human_time_description_raw (15552000);

print_select ($options, "period", $period);

echo '</td></tr><tr><td>'.__('Show events').'</td><td>';

print_checkbox ("draw_events", 1, (bool) $draw_events);

echo '</td></tr><tr><td>'.__('Show alerts').'</td><td>';

print_checkbox ("draw_alerts", 1, (bool) $draw_alerts);

echo '</td><td>';

print_submit_button ('GO', "submit", false, 'class="sub next"');

echo '</td></tr></table></form></div></body></html>';