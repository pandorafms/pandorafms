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
		$ahora = time();
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
	$param['origin']=isset($param['origin'])?$param['origin']:($ahora-($param['periodo']*60));
	
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

<!-- jscalendar -->

<style type="text/css">@import url(../include/styles/jscalendar/calendar-win2k-1.css);</style>
<script type="text/javascript" src="../include/styles/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../include/styles/jscalendar/lang/calendar-en.js"></script>
<script type="text/javascript" src="../include/styles/jscalendar/calendar-setup.js"></script>


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
	<b>Configuration Menu</b><br>Please, make your changes and apply with <i>Reload</i> button<BR>

	<form method='get' action='stat_win.php'>
		<table>
		<tr><td width='2%'> </td>
		    <td colspan='3' width='45%'></td>
		    <td width='6%'> </td>
		    <td colspan='3' width='45%'></td>
		    <td width='2%'> </td>
		</tr> 
	
		<tr>
			<td></td>
			<td>id(s): </td>
			<td><input type='text' name='id' value='<?php echo $param['id']; ?>'></td>
			<td> </td>
			
			<td></td>
			<td>origin: </td>
			<td>

<!-- jscalendar -->
<input type="hidden" name="origin" id="f_date_b" value='<?php echo $param['origin']; ?>'
       /><button type="reset" id="f_trigger_b"
       >popup calendar</button>
<script type="text/javascript">
    Calendar.setup({
	firstDay       :    1,
        inputField     :    "f_date_b",          //*
        ifFormat       :    "%s",
        showsTime      :    true,
        button         :    "f_trigger_b",       //*
        step           :    1
    });
</script>

			</td>
			<td></td>

		</tr>
		
		<tr>
			<td></td>
			<td>graph type(s): </td>
			<td><input type='text' name='graphtype' value='<?php echo $param['graphtype']; ?>'></td>
			<td> </td>
			
			<td></td>
			<td>interval: </td>
			<td>
				<select name='periodo' value='<?php echo $param['periodo']; ?>'>
					<option value='60' <?php if ($param['periodo'] == '60') { echo 'selected="selected"';} ?> >1 hour
					<option value='1440' <?php if ($param['periodo'] == '1440') { echo 'selected="selected"';} ?> >1 day
					<option value='44640' <?php if ($param['periodo'] == '44640') { echo 'selected="selected"';} ?> >31 days
					<option value='525600' <?php if ($param['periodo'] == '525600') { echo 'selected="selected"';} ?> >365 days
				</select>
			</td>
			<td></td>
			
			<td></td>
		</tr>

		<tr>
			<td></td>
			<td>draw_events: </td>
			<td>
				<input type='radio' name='draw_events' value='0' <?php if ($param['draw_events'] == '0') { echo 'checked="checked"';} ?> > no
				&nbsp; &nbsp; 
				<input type='radio' name='draw_events' value='1' <?php if ($param['draw_events'] == '1') { echo 'checked="checked"';} ?> > yes
			</td>
			<td></td>
			
			<td></td>
			<td>points </td>
			<td><input type='text' name='intervalo' value='<?php echo $param['intervalo']; ?>'></td>
			<td></td>
			
			<td></td>
		</tr>

		<tr>
			<td></td>
			<td>label: </td>
			<td><input type='text' name='label' value='<?php echo $param['label']; ?>'></td>
			<td> </td>
			
			<td></td>
			<td>zoom: </td>
			<td><input type='text' name='zoom' value='<?php echo $param['zoom']; ?>'></td>
			<td>%</td>
			
			<td></td>
		</tr>
	
		<tr>
			<td></td>
			<td> </td>
			<td> </td>
			<td> </td>
			
			<td></td>
			<td>refresh: </td>
			<td><input type='text' name='refresh' value='<?php echo $param['refresh']; ?>'></td>
			<td> s</td>
			
			<td></td>
		</tr>
		</table>

		<input type='hidden' name='tipo' value='<?php echo $param['tipo']; ?>'>
		
		&nbsp;&nbsp;&nbsp;<input type="submit" value="reload">
	</form>
	
</div>

</body>
</html>
