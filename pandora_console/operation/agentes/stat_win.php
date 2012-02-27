<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.



// Global & session management
require_once ('../../include/config.php');
require_once ('../../include/auth/mysql.php');

if (! isset($_SESSION['id_usuario'])) {
	session_start();
	session_write_close();
}

require_once ($config['homedir'] . '/include/functions.php');
require_once ($config['homedir'] . '/include/functions_db.php');
require_once ($config['homedir'] . '/include/functions_reporting.php');
require_once ($config['homedir'] . '/include/functions_graph.php');
require_once ($config['homedir'] . '/include/functions_modules.php');
require_once ($config['homedir'] . '/include/functions_ui.php');

check_login ();

$id = get_parameter('id');
$label = base64_decode(get_parameter('label', ''));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
// Parsing the refresh before sending any header
$refresh = (int) get_parameter ("refresh", -1);
if ($refresh > 0) {
	$query = ui_get_url_refresh (false);
	
	echo '<meta http-equiv="refresh" content="'.$refresh.'; URL='.$query.'" />';
}
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Pandora FMS Graph (<?php echo modules_get_agentmodule_agent_name ($id) . ' - ' . $label; ?>)</title>
<link rel="stylesheet" href="../../include/styles/pandora_minimal.css" type="text/css" />
<script type='text/javaScript' src='../../include/javascript/calendar.js'></script>
<script type='text/javascript' src='../../include/javascript/x_core.js'></script>
<script type='text/javascript' src='../../include/javascript/x_event.js'></script>
<script type='text/javascript' src='../../include/javascript/x_slide.js'></script>
<script type='text/javascript' src='../../include/javascript/jquery.js'></script>
<script type='text/javascript'><!--
var defOffset = 2;
var defSlideTime = 220;
var tnActive = 0;
var visibleMargin = 15;
var menuW = 400;
var menuH = 310;
window.onload = function() {
	var d;
	d = xGetElementById('divmenu');
	d.termNumber = 1;
	xMoveTo(d, visibleMargin - menuW, 0);
	xShow(d);
	xAddEventListener(document, 'mousemove', docOnMousemove, false);
};

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

function show_others() {
	if (!$("#checkbox-avg_only").attr('checked')) {
		$("#hidden-show_other").val(1);
	}
	else {
		$("#hidden-show_other").val(0);
	}
}
//-->
</script>
</head>
<body bgcolor="#ffffff">
<?php

// Get input parameters
$label = get_parameter ("label","");	
if (!isset($_GET["period"]) OR (!isset($_GET["id"]))) {
	echo "<h3 class='error'>".__('There was a problem locating the source of the graph')."</h3>";
	exit;
}

$period = get_parameter ( "period", 3600);
$draw_alerts = get_parameter("draw_alerts", 0);
$avg_only = get_parameter ("avg_only", 1);
$show_other = (bool)get_parameter('show_other', false);
if ($show_other) {
	$avg_only = 0;
}
$period = get_parameter ("period", 86400);
$id = get_parameter ("id", 0);
$width = get_parameter ("width", 555);
$height = get_parameter ("height", 245);
$label = get_parameter ("label", "");
$start_date = get_parameter ("start_date", date("Y-m-d"));
$draw_events = get_parameter ("draw_events", 0);
$graph_type = get_parameter ("type", "sparse");
$zoom = get_parameter ("zoom", 1);
$baseline = get_parameter ("baseline", 0);

if ($zoom > 1) {
	$height = $height * ($zoom / 2.1);
	$width = $width * ($zoom / 1.4);

	echo "<script type='text/javascript'>window.resizeTo($width + 10, $height + 80);</script>";
}

$utime = get_system_time ();
$current = date("Y-m-d", $utime);

if ($start_date != $current)
	$date = strtotime($start_date);
else
	$date = $utime;

$urlImage = ui_get_full_url(false);

// log4x doesnt support flash yet
//
switch ($graph_type) {
	case 'boolean':
		echo grafico_modulo_boolean ($id, $period, $draw_events, $width, $height,
				$label, null, $draw_alerts, $avg_only, false, $date, false, $urlImage);
		break;
	case 'sparse':
		echo grafico_modulo_sparse ($id, $period, $draw_events, $width, $height,
			$label, null, $draw_alerts, $avg_only, false, $date, '', $baseline,
			0, true, false, $urlImage);
		break;
	case 'string':
		echo grafico_modulo_string ($id, $period, $draw_events, $width, $height,
			$label, null, $draw_alerts, 1, false, $date, false, $urlImage);
		break;
	case 'log4x':
		echo grafico_modulo_log4x ($id, $period, $draw_events, $width, $height,
			$label, $unit_name, $draw_alerts, 1, $pure, $date, 1);
		break;
	default:
		echo fs_error_image ('../images');
		break;
}

//z-index is 1 because 2 made the calendar show under the divmenu.
echo '<div id="divmenu" class="menu" style="z-index:1;"><b>'.__('Pandora FMS Graph configuration menu').'</b><br />'.__('Please, make your changes and apply with the <i>Reload</i> button');
echo '<form method="get" action="stat_win.php">';
html_print_input_hidden ("id", $id);
html_print_input_hidden ("label", $label);

if (isset($_GET["type"])) {
	$type = get_parameter_get ("type");
	html_print_input_hidden ("type", $type);
}
echo '<table class="databox_frame" cellspacing="5">';
echo '<tr><td>'.__('Refresh time').'</td><td colspan="2">';

html_print_input_text ("refresh", $refresh, '', 5);

echo '&nbsp;&nbsp;&nbsp;'.__('Avg. Only');

html_print_checkbox ("avg_only", 1, (bool) $avg_only, false, false, 'show_others()');
html_print_input_hidden('show_other', 0);

echo '<tr><td>'.__('Begin date').'</td><td>';

html_print_input_text ("start_date", substr ($start_date, 0, 10),'', 10);
html_print_image ("images/calendar_view_day.png", false, array ("onclick" => "scwShow(scwID('text-start_date'),this);"));

echo '</td></tr><tr><td>'.__('Zoom factor').'</td>';
echo '<td>';

$options = array ();
$options[$zoom] = 'x'.$zoom;
$options[1] = 'x1';
$options[2] = 'x2';
$options[3] = 'x3';
$options[4] = 'x4';

html_print_select ($options, "zoom", $zoom);

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

html_print_extended_select_for_time($options, 'period', $period, '', '', 0, 7);
//html_print_select ($options, "period_select", $period,"javascript: $('#text-period').val($('#period_select').val());");
//html_print_input_text ("period", $period, '', 10);
echo ' '.__('secs.');
echo '</td></tr><tr><td>'.__('Show events').'</td><td>';

html_print_checkbox ("draw_events", 1, (bool) $draw_events);

echo '</td></tr><tr><td>'.__('Show alerts').'</td><td>';

html_print_checkbox ("draw_alerts", 1, (bool) $draw_alerts);

if ($config['enterprise_installed'] && $graph_type == "sparse") {
	echo '</td></tr><tr><td>'.__('Draw baseline').'</td><td>';
	html_print_checkbox ("baseline", 1, (bool) $baseline);
}

echo '</td><td>';

html_print_submit_button ('GO', "submit", false, 'class="sub next"');

echo '</td></tr></table></form></div></body></html>';
?>
</body>
</html>
