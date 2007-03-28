<?php
// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnol�icas S.L, info@artica.es
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Cargamos variables globales
session_start();
include ("../include/config.php");
include ("../include/functions.php");
include("../include/functions_db.php");
include("../include/languages/language_".$language_code.".php");

if (comprueba_login() == 0) {
	// Has access to this page ???
	
	// Parsing the refresh before sending any header
	if (isset($_GET['refresh']) and is_numeric($_GET['refresh']) and $_GET['refresh']>0) {
		header( 'refresh: ' . $_GET['refresh'] );
		}

	// CSS
	echo '<style>';
	include("../include/styles/pandora.css");
	echo '</style>';

	// Get input parameters
	if (!isset($_GET["period"]) OR (!isset($_GET["id"]))) {
		echo "<h3 class='error'>".$lang_label["graf_error"]."</h3>";
		exit;	
	}
	
	if (isset($_GET["event"]))
		$draw_events = entrada_limpia($_GET["event"]);
	else
		$draw_events = 0;
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
		$height = 200;

	if (isset($_GET["label"]))
		$label = entrada_limpia ($_GET["label"]);
	else
		$label = "";

	if (isset($_GET["zoom"])){
		$zoom = entrada_limpia ($_GET["zoom"]);
		$height=$height*$zoom;
		$width=$width*$zoom;
	}		
	else
		$zoom = "1";

	echo "<img src='fgraph.php?tipo=sparse&id=$id&zoom=$zoom&label=$label&height=$height&width=$width&period=$period'  border=0 alt=''>";
} 

?>

<script type='text/javascript' src='../operation/active_console/scripts/x_core.js'></script>
<script type='text/javascript' src='../operation/active_console/scripts/x_event.js'></script>
<script type='text/javascript' src='../operation/active_console/scripts/x_slide.js'></script>
<style type='text/css'><!--

.menu {
	color:#000; background:#ccc; margin:2px; padding:2px;
	font-family:verdana,arial,sans-serif,helvetica; font-size:10px;
	border:1px solid #000;
	position:absolute;
	margin:0; width:550px; height:220px;
	visibility:hidden;
	filter:alpha(opacity=95);
	-moz-opacity: 0.95;
	opacity: 0.95;
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}

--></style>


<script type='text/javascript'><!--
	var defOffset = 2;
	var defSlideTime = 200;
	var tnActive = 0;
	var visibleMargin = 5;
	var menuW = 550;
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
	<b>Configuration Menu</b><br>Please, make your changes and apply with <i>Reload</i> button<BR><BR>

	<form method='get' action='stat_win.php'>
		<table>
		<tr><td><B>Actual configuration</B> <BR></td>
		    <td><B>Configurable parameters</B> <BR></td>
		</tr> 
		<tr><td>
			<?php
			
			echo "<input type='hidden' name='id' value='$id'>";
			echo "<input type='hidden' name='refresh' value='$refresh'>";
			echo "<input type='hidden' name='zoom' value='$zoom'>";
			echo "<input type='hidden' name='event' value='$draw_events'>";
	
			?>
		</td><td>
			<?php
			echo "Refresh <input type='text' name='refresh' value='" . $refresh . "'> seconds <br>";
			echo "Zoom <input type='text' name='zoom' value='" . $zoom . "'> % <br>";
			echo "Show events <input type='text' name='draw_events' value='" . $draw_events . "'> ( 1 or 0 ) <br>";
			?>
		</td></tr>
		</table>
		
		<input type="submit" value="reload">
	</form>
	
</div>


