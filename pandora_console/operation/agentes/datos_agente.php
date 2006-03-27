<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este c�igo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Cargamos variables globales
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");

function datos_raw($id_agente_modulo, $periodo){
	// Conecto con la BBDD
	require("include/config.php");
	require("include/languages/language_".$language_code.".php");
	
	// Fecha 24 horas
	$yesterday_year = date("Y", time()-86400);
	$yesterday_month = date("m", time()-86400);
	$yesterday_day = date ("d", time()-86400);
	$yesterday_hour = date ("H", time()-86400);
	$dia = $yesterday_year."-".$yesterday_month."-".$yesterday_day." ".$yesterday_hour.":00:00";
	
		
	// Fecha 24x7 Horas (una semana)
	$week_year = date("Y", time()-604800);
	$week_month = date("m", time()-604800);
	$week_day = date ("d", time()-604800);
	$week_hour = date ("H", time()-604800);
	$week = $week_year."-".$week_month."-".$week_day." ".$week_hour.":00:00";
	
		// Fecha de hace 24x7x30 Horas (un mes)
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
	echo "<div><b> $et </b></div>";
	echo "<br>";
	if (mysql_num_rows($result)){
		echo "<table cellpadding='3' cellspacing='3' width=750 border=0>";
		echo "<th>".$lang_label["timestamp"]."</th>";
		echo "<th>".$lang_label["data"]."</th>";
		while ($row=mysql_fetch_array($result)){
			echo "<tr>";	
			echo "<td class='f9'>".$row["timestamp"];
			echo "<td class='datos'>".salida_limpia($row["datos"]);
		}
		echo "</table>";
		echo "</table>"; // I dont know why but there is an unclosed table in somewhere :-?
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
