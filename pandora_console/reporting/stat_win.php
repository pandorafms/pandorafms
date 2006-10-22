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

	if (isset($_GET["tipo"]) AND isset($_GET["id"])) {
		$tipo =entrada_limpia($_GET["tipo"]);
		$id_agente_modulo = entrada_limpia($_GET["id"]);
	}
	else {
		echo "<h3 class='error'>".$lang_label["graf_error"]."</h3>";
		exit;	
	}
	
	// Nota: En los intervalos, se han aumentado por encima de los 24 del grafico diario y los 7 del semanal para
	// que la grafica tenga mas resolucion. Cuanto mayor sea el intervalo mas tardara la grafica en generarse !!!.
	
	// TODO: Crear una variable para que se pueda utilizar como factor de resolucion de graficos y parametrizarlo en un
	// archivo de configuracion.
	
	$module_interval = give_moduleinterval($id_agente_modulo); 
	// Interval defined for this module or agent general interval, if interval for this specific module not defined
	$module_interval = $module_interval / 60; // Convert to resol / minute
	// Please be caution, interval now is in MINUTES not in seconds
	// interval is the number of rows that will store data. more rows, more resolution
	
	switch ($tipo) {
		case "mes": 	$intervalo = 30 * $config_graph_res;
				$intervalo_real = (43200 / $module_interval);
				if ($intervalo_real < $intervalo ){
					$intervalo = $intervalo_real;
				}
				$param['color'] = '6e90ff';
				$param['periodo'] = 43200;
				$param['intervalo'] = $intervalo;
				$param['label'] = $lang_label["month_graph"];
				break;

		case "dia": 	$intervalo = 24 * $config_graph_res;
				$intervalo_real = (1440 / $module_interval);
				if ($intervalo_real < $intervalo ){
					$intervalo = $intervalo_real;
				}
				$param['color'] = 'f3c330';
				$param['periodo'] = 1440;
				$param['intervalo'] = $intervalo;
				$param['label'] = $lang_label["day_graph"];
				break;
				
		case "semana": 	$intervalo = 28 * $config_graph_res;
				$intervalo_real = (10080 / $module_interval);
				if ($intervalo_real < $intervalo ) {
					$intervalo = $intervalo_real;
				}
				$param['color'] = 'e366cd';
				$param['periodo'] = 10080;
				$param['intervalo'] = $intervalo;
				$param['label'] = $lang_label["week_graph"];
				break;
				
		case "hora": 	$intervalo = 5 * $config_graph_res;
				$intervalo_real = 60 / $module_interval;
				if ($intervalo_real < $intervalo ) {
					$intervalo = $intervalo_real;
				}
				$param['color'] = '40d840';
				$param['periodo'] = 60;
				$param['intervalo'] = $intervalo;
				$param['label'] = $lang_label["hour_graph"];
				break;		

	}
	
	
	foreach ($_GET as $key => $value) {
		$param[$key] = $value;
	}
	$param['tipo']='sparse';
	$param['zoom']=isset($param['zoom'])?$param['zoom']:100;
	$param['draw_events']=isset($param['draw_events'])?$param['draw_events']:1;
	
	/*if (isset($_GET['draw_events']) and $_GET['draw_events']==0) {
		$param['draw_events'] = 0;
	}*/ 
	
	$imgtag = "<img src='fgraph.php?tipo=sparse&id=". $id_agente_modulo ;
	foreach ($param as $key => $value) {
		$imgtag .= "&" . $key . "=" . $value;
	}
	$imgtag .= "' border=0 alt=''>";
		
	echo $imgtag;
	
	$param['tipo'] = $_GET['tipo'];
	$param['id'] = $_GET['id'];
} 

?>

<script type='text/javascript' src='../include/styles/cb/x_core.js'></script>
<script type='text/javascript' src='../include/styles/cb/x_event.js'></script>
<script type='text/javascript' src='../include/styles/cb/x_slide.js'></script>
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
//--></script>
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
			foreach ($param as $key => $value) {
				echo "<input type='hidden' name='$key' value='$value'>";
				echo "<DD>$key - $value <BR>";
			}
			?>
		</td><td>
			<?php
			echo "Refresh <input type='text' name='refresh' value='" . $param['refresh'] . "'> seconds <br>";
			echo "Zoom <input type='text' name='zoom' value='" . $param['zoom'] . "'> % <br>";
			echo "Show events <input type='text' name='draw_events' value='" . $param['draw_events'] . "'> ( 1 or 0 ) <br>";
			?>
		</td></tr>
		</table>
		
		<input type="submit" value="reload">
	</form>
	
</div>


