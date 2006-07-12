<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
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

// Load global vars
require("include/config.php");

function datos_raw($id_agente_modulo, $periodo){
	require("include/config.php");
	require("include/languages/language_".$language_code.".php");
	
	// 24 hours date
	$yesterday_year = date("Y", time()-86400);
	$yesterday_month = date("m", time()-86400);
	$yesterday_day = date ("d", time()-86400);
	$yesterday_hour = date ("H", time()-86400);
	$dia = $yesterday_year."-".$yesterday_month."-".$yesterday_day." ".$yesterday_hour.":00:00";
	
		
	// 24x7 hours (one week)
	$week_year = date("Y", time()-604800);
	$week_month = date("m", time()-604800);
	$week_day = date ("d", time()-604800);
	$week_hour = date ("H", time()-604800);
	$week = $week_year."-".$week_month."-".$week_day." ".$week_hour.":00:00";
	
	// 24x7x30 Hours (one month)
	$month_year = date("Y", time()-2592000);
	$month_month = date("m", time()-2592000);
	$month_day = date ("d", time()-2592000);
	$month_hour = date ("H", time()-2592000);
	$month = $month_year."-".$month_month."-".$month_day." ".$month_hour.":00:00";
	$et = " "; 
	switch ($periodo) {
		case "mes":
				$periodo = $month;
				$et=$lang_label["last_month"];
				break;
		case "semana":
				$periodo = $week;
				$et=$lang_label["last_week"];
				break;
		case "dia":
				$periodo = $dia;
				$et=$lang_label["last_24"];
				break;
	}
		
	// Different query for string data type
	$id_tipo_modulo = dame_id_tipo_modulo_agentemodulo($id_agente_modulo);
	if ( (dame_nombre_tipo_modulo($id_tipo_modulo) == "generic_data_string" ) OR
	     (dame_nombre_tipo_modulo($id_tipo_modulo) == "remote_tcp_string" ) OR
 	     (dame_nombre_tipo_modulo($id_tipo_modulo) == "remote_snmp_string" )) {
		$sql1="SELECT * FROM tagente_datos_string WHERE id_agente_modulo = ".$id_agente_modulo." AND timestamp > '".$periodo."' ORDER BY timestamp"; 
	}
	else {
		$sql1="SELECT * FROM tagente_datos WHERE id_agente_modulo = ".$id_agente_modulo." AND timestamp > '".$periodo."' ORDER BY timestamp";
	}
	
	$result=mysql_query($sql1);
	$nombre_agente = dame_nombre_agente_agentemodulo($id_agente_modulo);
	$nombre_modulo = dame_nombre_modulo_agentemodulo($id_agente_modulo);
	
	echo "<h2>".$lang_label["data_received"]." '$nombre_agente' / '$nombre_modulo' </h2>";
	echo "<h3> $et <a href='help/".$help_code."/chap3.php#3322' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	if (mysql_num_rows($result)){
		echo "<table cellpadding='3' cellspacing='3' width='600' border='0'>";
		$color=1;
		echo "<th>".$lang_label["timestamp"]."</th>";
		echo "<th>".$lang_label["data"]."</th>";
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
				}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";	
			echo "<td class='".$tdcolor."f9 w130'>".$row["timestamp"];
			echo "<td class='".$tdcolor."'>".salida_limpia($row["datos"]);
		}
		echo "<tr><td colspan='3'><div class='raya'></div></td></tr>";
		echo "</table>";
	}
 	else  {
		echo "no_data";
	}
}	

// Comienzo de la pagina en si misma

if (comprueba_login() == 0) {
	if (isset($_GET["tipo"]) AND isset($_GET["id"])) {
		$id =entrada_limpia($_GET["id"]);
		$tipo= entrada_limpia($_GET["tipo"]);
	}
	else {
		echo "<h3 class='error'>".$lang_label["graf_error"]."</h3>";
		exit;	
	}
	
	datos_raw($id,$tipo);
}
?>