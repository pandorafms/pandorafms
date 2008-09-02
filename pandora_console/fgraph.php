<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

require_once ('../include/config.php');
require_once ($config["homedir"].'/include/functions.php');
require_once ($config["homedir"].'/include/functions_db.php');
require_once ('pandora_graph.php');

global $config;

if (!isset ($_SESSION["id_user"])){
	session_start();
	session_write_close();
}

$config["id_user"] = $_SESSION["id_usuario"];

// Session check
check_login ();

/**
 * Show a brief error message in a PNG graph
 */
function graphic_error () {
	Header ('Content-type: image/png');
	$img = imagecreatefromPng ('../images/image_problem.png');
	imagealphablending ($img, true);
	imagesavealpha ($img, true);
	imagepng ($img);
	exit;
}

/**
 * Return a MySQL timestamp date, formatted with actual date MINUS X minutes, 
 *
 * @param int Date in unix format (timestamp)
 *
 * @return string Formatted date string (YY-MM-DD hh:mm:ss)
 */
function dame_fecha ($mh) {
	$mh *= 60;
	$m_year = date ("Y", time () - $mh); 
	$m_month = date ("m", time () - $mh);
	$m_day = date ("d", time () - $mh);
	$m_hour = date ("H", time () - $mh);
	$m_min = date ("i", time () - $mh);
	$m = $m_year."-".$m_month."-".$m_day." ".$m_hour.":".$m_min.":00";
	return $m;
}

/**
 * Return a short timestamp data, D/M h:m
 *
 * @param int Date in unix format (timestamp)
 *
 * @return string Formatted date string
 */

function dame_fecha_grafico_timestamp ($timestamp) {
	return date ('d/m H:i', $timestamp);
}

/**
 * Produces a combined/user defined PNG graph
 *
 * @param array List of source modules
 * @param array List of weighs for each module
 * @param int Period (in seconds)
 * @param int Width, in pixels
 * @param int Height, in pixels
 * @param string Title for graph
 * @param string Unit name, for render in legend
 * @param int Show events in graph (set to 1)
 * @param int Show alerts in graph (set to 1)
 * @param int Pure mode (without titles) (set to 1)
 * @param int Date to start of getting info.
 */
function graphic_combined_module ($module_list, $weight_list, $periodo, $width, $height,
				$title, $unit_name, $show_event = 0, $show_alert = 0, $pure = 0, $stacked = 0, $date = 0) {

	global $config;
	
	$resolution = $config['graph_res'] * 50; // Number of "slices" we want in graph
	
	if (! $date)
		$date = time ();
	//$unix_timestamp = strtotime($mysql_timestamp) // Convert MYSQL format tio utime
	$fechatope = $date - $periodo; // limit date
	$interval = $periodo / $resolution; // Each interval is $interval seconds length
	$module_number = count ($module_list);

	// interval - This is the number of "rows" we are divided the time to fill data.
	//	     more interval, more resolution, and slower.
	// periodo - Gap of time, in seconds. This is now to (now-periodo) secs
	
	// Init tables
	for ($i = 0; $i < $module_number; $i++) {
		$real_data[$i] = array();
		$mod_data[$i] = 1; // Data multiplier to get the same scale on all modules
		if ($show_event == 1)
			$real_event[$i] = array();
		if (isset($weight_list[$i])){
			if ($weight_list[$i] == 0)
				$weight_list[$i] = 1;
		} else
			$weight_list[$i] = 1;
	}

	$max_value = 0;
	$min_value = 0;
	// FOR EACH MODULE IN module_list....
	for ($i = 0; $i < $module_number; $i++) {
		$id_agente_modulo = $module_list[$i];
		$nombre_agente = dame_nombre_agente_agentemodulo ($id_agente_modulo);
		$id_agente = dame_agente_id ($nombre_agente);
		$nombre_modulo = dame_nombre_modulo_agentemodulo ($id_agente_modulo);
		$module_list_name[$i] = substr ($nombre_agente, 0, 9)." / ".substr ($nombre_modulo, 0, 20);
		for ($j = 0; $j <= $resolution; $j++) {
			$data[$j][0] = 0; // SUM of all values for this interval
			$data[$j][1] = 0; // counter
			$data[$j][2] = $fechatope + ($interval * $j); // [2] Top limit for this range
			$data[$j][3] = $fechatope + ($interval*($j+1)); // [3] Botom limit
			$data[$j][4] = 0; // MIN
			$data[$j][5] = 0; // MAX
			$data[$j][6] = 0; // Event
		}
		// Init other general variables

		if ($show_event == 1) {
			// If we want to show events in graphs
			$sql = "SELECT utimestamp FROM tevento WHERE id_agentmodule = $id_agente_modulo AND utimestamp > $fechatope";
			$result = mysql_query ($sql);
			while ($row = mysql_fetch_array ($result)){
				$utimestamp = $row[0];
				for ($i = 0; $i <= $resolution; $i++) {
					if ( ($utimestamp <= $data[$i][3]) && ($utimestamp >= $data[$i][2]) ){
						$real_event[$i] = 1;
					}
				}
			}
		}
		$alert_high = 0;
		$alert_low = 10000000;
		if ($show_alert == 1){
			// If we want to show alerts limits
			$sql = "SELECT * FROM talerta_agente_modulo where id_agente_modulo = ".$id_agente_modulo;
			$result = mysql_query ($sql);
			while ($row = mysql_fetch_array ($result)) {
				if ($row["dis_max"] > $alert_high)
					$alert_high = $row["dis_max"];
				if ($row["dis_min"] < $alert_low)
					$alert_low = $row["dis_min"];
			}
		}
		$previous = 0;
		// Get the first data outsite (to the left---more old) of the interval given
		$sql = "SELECT datos, utimestamp FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp < $fechatope AND utimestamp >= $date ORDER BY utimestamp DESC LIMIT 1";
		$previous = get_db_sql ($sql);
		
		$sql = "SELECT datos,utimestamp FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp >= $fechatope AND utimestamp < $date";
		if ($result = mysql_query ($sql ))
		while ($row = mysql_fetch_array ($result)) {
			$datos = $row[0];
			$utimestamp = $row[1];
			for ($j = 0; $j <= $resolution; $j++) {
				if ($utimestamp <= $data[$j][3] && $utimestamp > $data[$j][2]) {
					$data[$j][0]=$data[$j][0]+$datos;
					$data[$j][1]++;
					// Init min value
					if ($data[$j][4] == 0)
						$data[$j][4] = $datos;
					else {
						// Check min value
						if ($datos < $data[$j][4])
						$data[$j][4] = $datos;
					}			
					// Check max value
					if ($datos > $data[$j][5])
						$data[$j][5] = $datos;
					break;
				}
			}
		}

		
		// Calculate Average value for $data[][0]
		for ($j = 0; $j <= $resolution; $j++) {
			if ($data[$j][1] > 0){
				$real_data[$i][$j] =  $weight_list[$i] * ($data[$j][0]/$data[$j][1]);
				$data[$j][0] = $data[$j][0]/$data[$j][1];
			} else {
				$data[$j][0] = $previous;
				$real_data[$i][$j] = $previous * $weight_list[$i];
				$data[$j][4] = $previous;
				$data[$j][5] = $previous;
			}
			// Get max value for all graph
			if ($data[$j][5] > $max_value ){
				$max_value = $data[$j][5];
			}
			// This stores in mod_data max values for each module
			if ($mod_data[$i] < $data[$j][5]){
				$mod_data[$i] = $data[$j][5];
			}
			// Take prev. value
			// TODO: CHeck if there are more than 24hours between
			// data, if there are > 24h, module down.
			$previous = $data[$j][0];
		}
	}

	for ($i = 0; $i < $module_number; $i++) {
		// Disabled autoadjusment, is not working fine :(
		// $weight_list[$i] = ($max_value / $mod_data[$i]) + ($weight_list[$i]-1);
		if ($weight_list[$i] != 1)
			$module_list_name[$i] .= " (x". format_numeric($weight_list[$i],1).")";
		$module_list_name[$i] = $module_list_name[$i]." (MAX: ".format_numeric ($mod_data[$i]).")";
	}

	if ($periodo <= 86400)
		$title_period = "Last day";
	elseif ($periodo <= 604800)
		$title_period = "Last week";
	elseif ($periodo <= 3600)
		$title_period = "Last hour";
	elseif ($periodo <= 2419200)
		$title_period = "Last month";
	else
		$title_period = "Last ".format_numeric (($periodo / (3600 * 24)), 2)." days";
	
	if ($max_value <= 0) {
		graphic_error ();
	}
	
	$factory = new PandoraGraphFactory ();
	$engine = $factory->create_graph_engine ();
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$real_data;
	$engine->legend = &$legend;
	$engine->fontpath = $config['fontpath'];
	$engine->title = '   Pandora FMS Graph - '.strtoupper ($nombre_agente)." - ".$title_period;
	$engine->subtitle = '     '.$title;
	$engine->show_title = !$pure;
	$engine->stacked = $stacked;
	$engine->legend = $module_list_name;
	$engine->xaxis_interval = $resolution;
	$events = $show_event ? $real_event : false;
	$alerts = $show_alert ? array ('low' => $alert_low, 'high' => $alert_high) : false;
	$engine->combined_graph ($data, $events, $alerts, $unit_name, $max_value, $stacked);
}

function grafico_modulo_sparse ($id_agente_modulo, $period, $show_event,
				$width, $height , $title, $unit_name,
				$show_alert, $avg_only = 0, $pure = false,
				$date = 0) {
	include ("../include/config.php");

	if (! $date)
		$date = time ();
	$resolution = $config["graph_res"] * 50; // Number of "slices" we want in graph
	$fechatope = $date - $period;
	$real_event = array ();
	
	$interval = $period / $resolution; // Each interval is $interval seconds length
	$nombre_agente = dame_nombre_agente_agentemodulo ($id_agente_modulo);
	$id_agente = dame_agente_id ($nombre_agente);
	$nombre_modulo = dame_nombre_modulo_agentemodulo ($id_agente_modulo);
	
	// Init tables
	for ($i = 0; $i <= $resolution; $i++) {
		$data[$i][0] = 0; // SUM of all values for this interval
		$data[$i][1] = 0; // counter
		$data[$i][2] = $fechatope + ($interval * $i); // [2] Top limit for this range
		$data[$i][3] = $fechatope + ($interval * ($i + 1)); // [3] Botom limit
		$data[$i][4] = 0; // MIN
		$data[$i][5] = 0; // MAX
		$data[$i][6] = 0; // Event
	}
	
	if ($show_event) {
		// If we want to show events in graphs
		$sql = sprintf ("SELECT utimestamp FROM tevento
				WHERE id_agentmodule = %d AND utimestamp > %d",
				$id_agente_modulo, $fechatope);
		$result = mysql_query ($sql);
		while ($row = mysql_fetch_array($result)) {
			$utimestamp = $row[0];
			for ($i = 0; $i <= $resolution; $i++) {
				if ($utimestamp <= $data[$i][3] && $utimestamp >= $data[$i][2]) {
					$real_event[$utimestamp] = 1;
				}
			}
		}
	}

	if ($show_alert) {
		$alert_high = false;
		$alert_low = false;
		// If we want to show alerts limits
		
		$alert_high = get_db_value ('MAX(dis_max)', 'talerta_agente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
		$alert_low = get_db_value ('MIN(dis_min)', 'talerta_agente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
		
		// if no valid alert defined to render limits, disable it
		if (($alert_low === false || $alert_low === NULL) &&
			($alert_high === false || $alert_high === NULL)) {
			$show_alert = 0;
		}
	}
	
	// Init other general variables
	// Init other general variables
	$max_value = 0;
	$min_value = 0;

	// Get the first data outsite (to the left---more old) of the interval given
	$sql = sprintf ('SELECT datos, utimestamp FROM tagente_datos 
			WHERE id_agente = %d AND id_agente_modulo = %d 
			AND utimestamp < %d ORDER BY utimestamp DESC LIMIT 1', $id_agente, $id_agente_modulo, $fechatope);
	$previous = (float) get_db_sql ($sql);
	
	$sql = sprintf ('SELECT datos,utimestamp FROM tagente_datos 
			WHERE id_agente = %d AND id_agente_modulo = %d AND utimestamp > %d',
			$id_agente, $id_agente_modulo, $fechatope);
	$result = mysql_query ($sql);
	if (mysql_num_rows ($result) == 0) {
		graphic_error ();
		return;
	}
	
	while ($row = mysql_fetch_array ($result)) {
		$datos = $row[0];
		$utimestamp = $row[1];
		for ($i = 0; $i <= $resolution; $i++) {
			if ( ($utimestamp <= $data[$i][3]) && ($utimestamp >= $data[$i][2]) ){
				$data[$i][0]=$data[$i][0]+$datos;
				$data[$i][1]++;
				// Init min value
				if ($data[$i][4] == 0)
					$data[$i][4] = $datos;
				else {
					// Check min value
					if ($datos < $data[$i][4])
						$data[$i][4] = $datos;
				}			
				// Check max value
				if ($datos > $data[$i][5])
						$data[$i][5] = $datos;
				break;
			}
		}
				
	}
	
	// Calculate Average value for $data[][0]
	for ($i = 0; $i <= $resolution; $i++) {
		if ($data[$i][1] > 0) {
			$data[$i][0] = $data[$i][0]/$data[$i][1];
		} else {
			$data[$i][0] = $previous;
			$data[$i][4] = $previous;
			$data[$i][5] = $previous;
		}
		// Get max value for all graph
		if ($data[$i][5] > $max_value) {
			$max_value = $data[$i][5];
		}
		
		// Get min value for all graph
		if ($data[$i][5] < $min_value) {
			$min_value = $data[$i][5];
		}
		// Take prev. value
		// TODO: CHeck if there are more than 24hours between
		// data, if there are > 24h, module down.
		$previous = $data[$i][0];
	}
	
	if ($max_value <= $min_value) {
		graphic_error ();
		return;
	}
	
	if ($period <= 86400)
		$title_period = "Last day";
	elseif ($period <= 604800)
		$title_period = "Last week";
	elseif ($period <= 3600)
		$title_period = "Last hour";
	elseif ($period <= 2419200)
		$title_period = "Last month";
	else
		$title_period = "Last ".format_numeric (($period / (3600 * 24)), 2)." days";
	
	$factory = new PandoraGraphFactory ();
	$engine = $factory->create_graph_engine ();
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$data;
	$engine->xaxis_interval = $resolution;
	$engine->title = '   Pandora FMS Graph - '.strtoupper ($nombre_agente)." - ".$title_period;
	$engine->subtitle = '     '.$title;
	$engine->show_title = !$pure;
	$engine->events = $show_event ? $real_event : false;
	$engine->alert_top = $show_alert ? $alert_high : false;
	$engine->alert_bottom = $show_alert ? $alert_low : false;;
	if (! $pure) {
		$engine->legend = &$legend;
	}
	$engine->fontpath = $config['fontpath'];
	set_time_limit (0);
	$engine->sparse_graph ($period, $avg_only, $min_value, $max_value, $unit_name);
}

function graphic_agentmodules ($id_agent, $width, $height) {
	global $config;
	
	$data = array ();
	$sql = sprintf ('SELECT ttipo_modulo.nombre,COUNT(id_agente_modulo)
			FROM tagente_modulo,ttipo_modulo WHERE
			id_tipo_modulo = id_tipo AND id_agente = %d
			GROUP BY id_tipo_modulo', $id_agent);
	$modules = get_db_all_rows_sql ($sql);
	foreach ($modules as $module) {
		$data[$module['nombre']] = $module[1];
	}
	generic_pie_graph ($width, $height, $data);
}

function graphic_agentaccess ($id_agent, $period, $width, $height) {
	global $config;
	
	$interval = 24;
	$fechatope = dame_fecha ($period);
	$hours = $period / $interval;
	
	// $interval now stores "ideal" interval
	// interval is the number of rows that will store data. more rows, more resolution

	// Para crear las graficas vamos a crear un array de Ix4 elementos, donde
	// I es el numero de posiciones diferentes en la grafica (30 para un mes, 7 para una semana, etc)
	// y los 4 valores en el ejeY serian los detallados a continuacion:
	// Rellenamos la tabla con un solo select, y los calculos se hacen todos sobre memoria
	// esto acelera el tiempo de calculo al maximo, aunque complica el algoritmo :-)
	
	// Creamos la tabla (array) con los valores para el grafico. Inicializacion
	for ($i = 0; $i < $interval; $i++) {
		$time[$i][0] = dame_fecha ($hours * $i); // [2] Rango superior de fecha para ese rango
		$time[$i][1] = dame_fecha ($hours * ($i + 1)); // [3] Rango inferior de fecha para ese rango
	}
	$sql = sprintf ('SELECT * FROM tagent_access 
			WHERE id_agent = %d AND timestamp > "%s"',
			$id_agent, $fechatope);
	
	$result = mysql_query ($sql);
	$data = array_pad (array (), $interval, 0);
	while ($row = mysql_fetch_array ($result)) {
		for ($i = 0; $i < $interval; $i++) {
			if (($row["timestamp"] < $time[$i][0]) && ($row["timestamp"] >= $time[$i][1])) {
				// entra en esta filas
				$data[$i]++;
			}
		} 
		
	}
	
	generic_single_graph ($width, $height, $data, $interval / 7);
}

function graphic_string_data ($id_agent_module, $periodo, $width, $height, $pure = 0, $date = "") {
	global $config;
	
	// $color = $config["color_graph1"]; //#437722"; // Green pandora 1.1 octopus color
	$color = "#437722";

	if ($date == "")
		$date = time ();
	$resolution = $config["graph_res"] * 5; // Number of "slices" we want in graph
	$fechatope = $date - $periodo;
	$interval = $periodo / $resolution; // Each interval is $interval seconds length
	$legend = array ();
	
	// Creamos la tabla (array) con los valores para el grafico. Inicializacion
	for ($i = 0; $i < $resolution; $i++) {
		$data[$i][0] = 0; // [0] Valor (contador)
		$data[$i][1] = dame_fecha_grafico_timestamp ($fechatope + ($interval * $i));
		$data[$i][2] = $fechatope + ($interval * $i); // [2] Top limit for this range
		$data[$i][3] = $fechatope + ($interval * ($i + 1)); // [3] Botom limit
		$legend[$i] = dame_fecha_grafico_timestamp ($fechatope + ($interval * $i));
	}
	$sql = "SELECT utimestamp FROM tagente_datos_string WHERE id_agente_modulo = ".$id_agent_module." and utimestamp > '".$fechatope."'";

	$result = mysql_query ($sql);
	while ($row = mysql_fetch_array ($result)) {
		for ($i = 0; $i < $resolution; $i++){
			if (($row[0] < $data[$i][3]) && ($row[0] >= $data[$i][2]) ){ 
				// entra en esta fila
				$data[$i][0]++;
			}
		} 
		
	}
	$valor_maximo = 0;
	for ($i = 0; $i < $resolution; $i++) {
		$grafica[$data[$i][2]] = $data[$i][0];
		if ($data[$i][0] > $valor_maximo)
			$valor_maximo = $data[$i][0];
	}
	
	if ($valor_maximo <= 0) {
		graphic_error ();
		return;
	}
	
	$nombre_agente = dame_nombre_agente_agentemodulo ($id_agent_module);
	$id_agente = dame_agente_id ($nombre_agente);
	$nombre_modulo = dame_nombre_modulo_agentemodulo ($id_agent_module);
	
	$factory = new PandoraGraphFactory ();
	$engine = $factory->create_graph_engine ();
	
	$engine->show_title = !$pure;
	$engine->title = '   Pandora FMS Graph - '.strtoupper ($nombre_agente)." - ".give_human_time ($periodo);
	$engine->subtitle = '     '.__('Data occurrence for module').' '.$nombre_modulo;
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$grafica;
	$engine->xaxis_interval = $config["graph_res"];
	$engine->xaxis_format = 'date';
	$engine->fontpath = $config['fontpath'];
	$engine->vertical_bar_graph ();
}


function grafico_incidente_estados () {
	$data = array (0, 0, 0, 0);
	// 0 - Abierta / Sin notas
	// 2 - Descartada
	// 3 - Caducada 
	// 13 - Cerrada
	$data = array ();
	$data[__("Open Incident")] = 0;
	$data[__("Closed Incident")] = 0;
	$data[__("Outdated")] = 0;
	$data[__("Invalid")] = 0;
	
	$sql = 'SELECT * FROM tincidencia WHERE estado IN (0,2,3,13)';
	$result = mysql_query ($sql);
	while ($row = mysql_fetch_array ($result)) {
		if ($row["estado"] == 0)
			$data[__("Open Incident")]++;
		if ($row["estado"] == 2)
			$data[__("Closed Incident")]++;
		if ($row["estado"] == 3)
			$data[__("Outdated")]++;
		if ($row["estado"] == 13)
			$data[__("Invalid")]++;
	}
	generic_pie_graph (370, 180, $data);
}

function grafico_incidente_prioridad () {
	$data_tmp = array (0, 0, 0, 0, 0, 0);
	$sql = 'SELECT COUNT(id_incidencia), prioridad
		FROM tincidencia GROUP BY prioridad
		ORDER BY 2 DESC';
	$incidents = get_db_all_rows_sql ($sql);
	foreach ($incidents as $incident) {
		if ($incident['prioridad'] < 5)
			$data_tmp[$incident[1]] = $incident[0];
		else
			$data_tmp[5] += $incident[0];
	}
	$data = array (__('Informative') => $data_tmp[0],
			__('Low') => $data_tmp[1],
			__('Medium') => $data_tmp[2],
			__('Serious') => $data_tmp[3],
			__('Very serious') => $data_tmp[4],
			__('Maintenance') => $data_tmp[5]);
	
	generic_pie_graph (320, 200, $data);
}

function graphic_incident_group () {
	$data = array ();
	$max_items = 5;
	$sql = sprintf ('SELECT COUNT(id_incidencia), nombre
			FROM tincidencia,tgrupo
			WHERE tgrupo.id_grupo = tincidencia.id_grupo
			GROUP BY tgrupo.id_grupo ORDER BY 1 DESC LIMIT %d',
			$max_items);
	$incidents = get_db_all_rows_sql ($sql);
	foreach ($incidents as $incident) {
		$name = $incident[1].' ('.$incident[0].')';
		$data[$name] = $incident[0];
	}
	generic_pie_graph (320, 200, $data);
}

function graphic_incident_user () {
	$data = array ();
	$max_items = 5;
	$sql = sprintf ('SELECT COUNT(id_incidencia), id_usuario
			FROM tincidencia GROUP BY id_usuario
			ORDER BY 1 DESC LIMIT %d', $max_items);
	$incidents = get_db_all_rows_sql ($sql);
	foreach ($incidents as $incident) {
		$name = $incident[1].' ('.$incident[0].')';
		$data[$name] = $incident[0];
	}
	generic_pie_graph (320, 200, $data);
}

function graphic_user_activity ($width = 350, $height = 230) {
	$data = array ();
	$max_items = 5;
	$sql = sprintf ('SELECT COUNT(id_usuario), id_usuario
			FROM tsesion GROUP BY id_usuario
			ORDER BY 1 DESC LIMIT %d', $max_items);
	$logins = get_db_all_rows_sql ($sql);
	foreach ($logins as $login) {
		$data[$login[1]] = $login[0];
	}
 	generic_pie_graph ($width, $height, $data);
}

function graphic_incident_source ($width = 320, $height = 200) {
	$data = array ();
	$max_items = 5;
	$sql = sprintf ('SELECT COUNT(id_incidencia), origen 
			FROM tincidencia GROUP BY `origen`
			ORDER BY 1 DESC LIMIT %d', $max_items);
	$origins = get_db_all_rows_sql ($sql);
	foreach ($origins as $origin) {
		$data[$origin[1]] = $origin[0];
	}
	generic_pie_graph ($width, $height, $data);
}

function graph_db_agentes_modulos ($width, $height) {
	$data = array ();
	
	$modules = get_db_all_rows_sql ('SELECT COUNT(id_agente_modulo),id_agente
					FROM tagente_modulo group by id_agente
					ORDER BY 1 DESC');
	foreach ($modules as $module) {
		$agent_name = dame_nombre_agente ($module['id_agente']);
		$data[$agent_name] = $module[0];
	}
	/* Swap height and width */
	generic_horizontal_bar_graph ($width, $height, $data);
}

function grafico_eventos_usuario ($width, $height) {
	$data = array ();
	$max_items = 5;
	$sql = sprintf ('SELECT COUNT(id_evento),id_usuario
			FROM tevento GROUP BY id_usuario
			ORDER BY 1 DESC LIMIT %d', $max_items);
	$events = get_db_all_rows_sql ($sql);
	foreach ($events as $event) {
		$data[$event[1]] = $event[0];
	}
	generic_pie_graph ($width, $height, $data);
}

function grafico_eventos_total ($filter = "") {
	$filter = str_replace  ( "\\" , "", $filter);
	$data = array ();
	$legend = array ();
	$total = 0;
	
	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 0 $filter";
	$data[__('Maintenance')] = get_db_sql ($sql);
	
	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 1 $filter";
	$data[__('Informational')] = get_db_sql ($sql);

	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 2 $filter";
	$data[__('Normal')] = get_db_sql ($sql);

	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 3 $filter";
	$data[__('Warning')] = get_db_sql ($sql);

	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE criticity = 4 $filter";
	$data[__('Critical')] = get_db_sql ($sql);
	
	asort ($data);
	
	generic_pie_graph (320, 200, $data);
}

function graph_event_module ($width = 300, $height = 200, $id_agent) {
	$data = array ();
	$max_items = 6;
	$sql = sprintf ('SELECT COUNT(id_evento),nombre
			FROM tevento, tagente_modulo
			WHERE id_agentmodule = id_agente_modulo
			AND disabled = 0 AND tevento.id_agente = %d
			GROUP BY id_agentmodule LIMIT %d', $id_agent, $max_items);
	$events = get_db_all_rows_sql ($sql);
	if ($events === false) {
		graphic_error ();
		return;
	}
	foreach ($events as $event) {
		$data[$event['nombre'].' ('.$event[0].')'] = $event[0];
	}
	
	/* System events */
	$sql = "SELECT COUNT(*) FROM tevento WHERE id_agentmodule = 0 AND id_agente = $id_agent";
	$value = get_db_sql ($sql);
	if ($value > 0) {
		$data[__('System').' ('.$value.')'] = $value;
	}
	asort ($data);
	
	// Take only the first $max_items values
	if (sizeof ($data) >= $max_items) {
		$data = array_slice ($data, 0, $max_items);
	}
	generic_pie_graph ($width, $height, $data, 75);
}


function grafico_eventos_grupo ($width = 300, $height = 200, $url = "") {
	global $config;
	
	$url = mysql_escape_string ($url);
	$data = array ();
	$sql = "SELECT id_agente, id_grupo, nombre FROM tagente";
	$agents = get_db_all_rows_sql ($sql);
	foreach ($agents as $agent) {
		if (! give_acl ($config['id_user'], $agent['id_grupo'], 'AR'))
			continue;
		
		$sql = sprintf ("SELECT COUNT(id_evento)
				FROM tevento WHERE 1=1 %s
				AND id_agente = %d",
				$url, $agent['id_agente']);
		$value = get_db_sql ($sql);
		if ($value > 0) {
			$data[substr ($agent['nombre'], 0, 15)] = $value;
		}
	}
	// System events
	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE 1=1 $url AND id_agente = 0";
	$value = get_db_sql ($sql);
	if ($value > 0) {
		$data[__('System')] = $value;
	}
	asort ($data);
	
	$max_items = 6;
	// Take only the first x items
	if (sizeof ($data) >= $max_items) {
		$data = array_slice ($data, 0, $max_items);
	}
	
	generic_pie_graph ($width, $height, $data);
}

function generic_single_graph ($width = 380, $height = 200, &$data, $interval = 1) {
	global $config;
	
	if (sizeof ($data) == 0)
		graphic_error ();
	
	$factory = new PandoraGraphFactory ();
	$engine = $factory->create_graph_engine ();
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$data;
	$engine->fontpath = $config['fontpath'];
	$engine->xaxis_interval = $interval;
	
	$engine->single_graph ();
}

function generic_vertical_bar_graph ($width = 380, $height = 200, &$data, &$legend) {
	global $config;
	
	if (sizeof ($data) == 0)
		graphic_error ();
	
	$factory = new PandoraGraphFactory ();
	$engine = $factory->create_graph_engine ();
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$data;
	$engine->legend = &$legend;
	$engine->fontpath = $config['fontpath'];
	$engine->vertical_bar_graph ();
}

function generic_horizontal_bar_graph ($width = 380, $height = 200, &$data, $legend = false) {
	global $config;
	
	if (sizeof ($data) == 0)
		graphic_error ();
	
	$factory = new PandoraGraphFactory ();
	$engine = $factory->create_graph_engine ();
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$data;
	$engine->legend = &$legend;
	$engine->fontpath = $config['fontpath'];
	$engine->horizontal_bar_graph ();
}

function generic_pie_graph ($width = 300, $height = 200, &$data, $zoom = 85) {
	global $config;
	
	if (sizeof ($data) == 0)
		graphic_error ();
	
	$factory = new PandoraGraphFactory ();
	$engine = $factory->create_graph_engine ();
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$data;
	$engine->zoom = $zoom;
	$engine->legend = array_keys ($data);
	$engine->show_title = true;
	$engine->zoom = 50;
	$engine->fontpath = $config['fontpath'];
	$engine->pie_graph ();
}

function grafico_db_agentes_paquetes ($width = 380, $height = 300) {
	$data = array();
	$sql1="SELECT distinct (id_agente) FROM tagente_datos";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		if (! is_null($row["id_agente"])){
			$sql1="SELECT COUNT(id_agente) FROM tagente_datos WHERE id_agente = ".$row["id_agente"];
			$result3=mysql_query($sql1);
			if ($row3=mysql_fetch_array($result3)){
				$agent_name = dame_nombre_agente($row[0]);
				if ($agent_name != "") {
					$data[str_pad ($agent_name, 15)]= $row3[0];
				}
			}
		}
	}
	
	asort ($data);
	
	generic_horizontal_bar_graph ($width, $height, $data);
}

function grafico_db_agentes_purge ($id_agent, $width, $height) {
	if ($id_agent == 0)
		$id_agent = -1;
	// All data (now)
	$purge_all = date ("Y-m-d H:i:s", time());
	
	$data = array();
	$legend = array();
	
	$d90 = time () - (2592000 * 3);
	$d30 = time () - 2592000;
	$d7 = time () - 604800;
	$d1 = time( ) - 86400;
	$fechas = array ($d90, $d30, $d7, $d1);
	$fechas_label = array ("30-90 days","7-30 days","This week","Today");

	// Calc. total packets
	$sql1 = "SELECT COUNT(id_agente_datos) FROM tagente_datos";
	$result2 = mysql_query ($sql1);
	$row2 = mysql_fetch_array ($result2);
	$total = $row2[0];
	
	for ($i = 0; $i < sizeof ($fechas); $i++){
	// 4 x intervals will be enought, increase if your database is very very fast :)
		if ($i == 3) {
			if ($id_agent == -1)
				$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp >= ".$fechas[$i];
			else
				$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE id_agente = $id_agent AND utimestamp >= ".$fechas[$i];
		} else {
			if ($id_agent == -1)
				$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp >= ".$fechas[$i]." AND utimestamp < ".$fechas[$i+1];
			else
				$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE id_agente = $id_agent AND utimestamp >= ".$fechas[$i]." AND utimestamp < ".$fechas[$i+1];
		}
		$result=mysql_query($sql1);
		$row=mysql_fetch_array($result);
		$data[$fechas_label[$i]." ( ".format_for_graph($row[0],0)." )"] = $row[0];
	}
	generic_pie_graph ($width, $height, $data);
}

// ***************************************************************************
// Draw a dynamic progress bar using GDlib directly
// ***************************************************************************

function progress_bar ($progress, $width, $height, $mode = 1) {
	// Copied from the PHP manual:
	// http://us3.php.net/manual/en/function.imagefilledrectangle.php
	// With some adds from sdonie at lgc dot com
	// Get from official documentation PHP.net website. Thanks guys :-)
	function drawRating($rating, $width, $height, $mode) {
		global $config;
		
		$rating = format_numeric($rating,1);
		if ($width == 0) {
			$width = 150;
		}
		if ($height == 0) {
			$height = 20;
		}

		//$rating = $_GET['rating'];
		$ratingbar = (($rating/100)*$width)-2;

		$image = imagecreate($width,$height);
		//colors
		$back = ImageColorAllocate($image,255,255,255);
		$border = ImageColorAllocate($image,140,140,140);
		$textcolor = ImageColorAllocate($image,60,60,60);
		$red = ImageColorAllocate($image,255,60,75);

		if ($mode == 0){
			if ($rating > 70) 
				$fill = ImageColorAllocate($image,176,255,84); // Green
			elseif ($rating > 50)
				$fill = ImageColorAllocate($image,255,230,84); // Yellow
			elseif ($rating > 30)
				$fill = ImageColorAllocate($image,255,154,83); // Orange
			else
				$fill = ImageColorAllocate($image,255,0,0); // Red
		}
		else
			$fill = ImageColorAllocate($image,44,81,150);


		$grey = ImageColorAllocate($image,230,230,210);

		if ($mode == 1){
			ImageFilledRectangle($image,0,0,$width-1,$height-1,$back);
		} else {
			ImageFilledRectangle($image,0,0,$width-1,$height-1,$grey);
		}
		if ($rating > 100)
			ImageFilledRectangle($image,1,1,$ratingbar,$height-1,$red);
		else
			ImageFilledRectangle($image,1,1,$ratingbar,$height-1,$fill);
		if ($mode == 1){
			ImageRectangle($image,0,0,$width-1,$height-1,$border);
		}
		if ($mode == 1){
			if ($rating > 50)
				if ($rating > 100)
					ImageTTFText($image, 8, 0, ($width/4), ($height/2)+($height/5), $back, $config["fontpath"], __('Out of limits'));
				else
					ImageTTFText($image, 8, 0, ($width/2)-($width/10), ($height/2)+($height/5), $back, $config["fontpath"], $rating."%");
			else
				ImageTTFText($image, 8, 0, ($width/2)-($width/10), ($height/2)+($height/5), $textcolor, $config["fontpath"], $rating."%");
		}
		imagePNG($image);
		imagedestroy($image);
	}
	Header("Content-type: image/png");
	if ($progress > 100 || $progress < 0){
		// HACK: This report a static image... will increase render in about 200% :-) useful for
		// high number of realtime statusbar images creation (in main all agents view, for example
		$imgPng = imageCreateFromPng("../images/outof.png");
		imageAlphaBlending($imgPng, true);
		imageSaveAlpha($imgPng, true);
		imagePng($imgPng); 
	} else 
		drawRating($progress,$width,$height,$mode);
}

function grafico_modulo_boolean ($id_agente_modulo, $periodo, $show_event,
				 $width, $height , $title, $unit_name, $show_alert, $avg_only = 0, $pure=0 ) {
	global $config;

	$resolution = $config['graph_res'] * 50; // Number of "slices" we want in graph
	
	//$unix_timestamp = strtotime($mysql_timestamp) // Convert MYSQL format tio utime
	$fechatope = time () - $periodo; // limit date
	$interval = $periodo / $resolution; // Each interval is $interval seconds length
	$nombre_agente = dame_nombre_agente_agentemodulo ($id_agente_modulo);
	$id_agente = dame_agente_id ($nombre_agente);
	$nombre_modulo = dame_nombre_modulo_agentemodulo ($id_agente_modulo);

	if ($show_event == 1)
		$real_event = array ();

	if ($show_alert == 1) {
		$alert_high = false;
		$alert_low = false;
		// If we want to show alerts limits
		
		$alert_high = get_db_value ('MAX(dis_max)', 'talerta_agente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
		$alert_low = get_db_value ('MIN(dis_min)', 'talerta_agente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
		
		// if no valid alert defined to render limits, disable it
		if (($alert_low === false || $alert_low === NULL) &&
			($alert_high === false || $alert_high === NULL)) {
			$show_alert = 0;
		}
	}

	// interval - This is the number of "rows" we are divided the time
	 // to fill data. more interval, more resolution, and slower.
	// periodo - Gap of time, in seconds. This is now to (now-periodo) secs
	
	// Init tables
	for ($i = 0; $i <= $resolution; $i++) {
		$data[$i][0] = 0; // SUM of all values for this interval
		$data[$i][1] = 0; // counter
		$data[$i][2] = $fechatope + ($interval * $i); // [2] Top limit for this range
		$data[$i][3] = $fechatope + ($interval * ($i + 1)); // [3] Botom limit
		$data[$i][4] = -1; // MIN
		$data[$i][5] = -1; // MAX
		$data[$i][6] = -1; // Event
	}
	// Init other general variables
	if ($show_event == 1) {
		// If we want to show events in graphs
		$sql = "SELECT utimestamp FROM tevento WHERE id_agente = $id_agente AND utimestamp > $fechatope";
		
		$result = mysql_query ($sql );
		while ($row = mysql_fetch_array ($result)) {
			$utimestamp = $row[0];
			for ($i = 0; $i <= $resolution; $i++) {
				if ( ($utimestamp <= $data[$i][3]) && ($utimestamp >= $data[$i][2]) ){
					$real_event[$utimestamp]=1;
				}
			}
		}
	}
	// Init other general variables
	$max_value = 0;
	$min_value = 0;

	// DEBUG ONLY (to get number of items for this graph)
	/*
	// Make "THE" query. Very HUGE.
		$sql1="SELECT COUNT(datos) FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp > fechatope";
		$result=mysql_query($sql1);
		$row=mysql_fetch_array($result);
		$title=$title." [C] ".$row[0];
	*/
	$previous = 0;
	// Get the first data outsite (to the left---more old) of the interval given
	$sql = sprintf ('SELECT datos,utimestamp
			FROM tagente_datos
			WHERE id_agente = %d
			AND id_agente_modulo = %d
			AND utimestamp < %d
			ORDER BY utimestamp DESC LIMIT 1',
			$id_agente, $id_agente_modulo, $fechatope);
	$previous = get_db_sql ($sql);
	
	$sql = sprintf ('SELECT datos,utimestamp
			FROM tagente_datos
			WHERE id_agente = %d
			AND id_agente_modulo = %d
			AND utimestamp > %d',
			$id_agente, $id_agente_modulo, $fechatope);
	$result = mysql_query ($sql);
	while ($row = mysql_fetch_array ($result)) {
		$datos = $row[0];
		$utimestamp = $row[1];
		
		$i = round (($utimestamp - $fechatope) / $interval);
		if (isset ($data[$i][0])) {
			$data[$i][0] += $datos;
			$data[$i][1]++;
		
			if ($data[$i][6] == -1)
				$data[$i][6]=$datos;
			
			// Init min value
			if ($data[$i][4] == -1)
				$data[$i][4] = $datos;
			else {
				// Check min value
				if ($datos < $data[$i][4])
					$data[$i][4] = $datos;
			}
			// Check max value
			if ($data[$i][5] == -1)
				$data[$i][5] = $datos;
			else
				if ($datos > $data[$i][5])
					$data[$i][5] = $datos;
		}
	}
	
	
	$last = $previous;
	// Calculate Average value for $data[][0]
	for ($i = 0; $i <= $resolution; $i++) {
		//echo $data[$i][6] . ", (" . $data[$i][4] . ", " . $data[$i][5] . ") :  ";
	
		if ($data[$i][6] == -1)
			$data[$i][6] = $last;
		else
			$data[$i][6] = $data[$i][4]; // min
			
		$last = $data[$i][5] != -1 ? $data[$i][5] : $data[$i][6]; // max
		
		if ($data[$i][1] > 0)
			$data[$i][0] = $data[$i][0]/$data[$i][1];
		else {
			$data[$i][0] = $previous;
			$data[$i][4] = $previous;
			$data[$i][5] = $previous;
		}
		// Get max value for all graph
		if ($data[$i][5] > $max_value)
			$max_value = $data[$i][5];
		// Take prev. value
		// TODO: CHeck if there are more than 24hours between
		// data, if there are > 24h, module down.
		$previous = $data[$i][0];
		
		//echo $data[$i][6];
		//echo "<br>";
	}
	
	if (! $max_value) {
		graphic_error ();
		return;
	}
	
	$grafica = array ();
	foreach ($data as $d) {
		$grafica[$d[2]] = $d[6];
	}
	
	if ($periodo <= 86400)
		$title_period = "Last day";
	elseif ($periodo <= 604800)
		$title_period = "Last week";
	elseif ($periodo <= 3600)
		$title_period = "Last hour";
	elseif ($periodo <= 2419200)
		$title_period = "Last month";
	else
		$title_period = "Last ".format_numeric (($periodo / (3600 * 24)), 2)." days";
	
	$factory = new PandoraGraphFactory ();
	$engine = $factory->create_graph_engine ();
	
	$engine->width = $width;
	$engine->height = $height;
	$engine->data = &$grafica;
	$engine->legend = array ($nombre_modulo);
	$engine->title = '   Pandora FMS Graph - '.strtoupper ($nombre_agente)." - ".$title_period;
	$engine->subtitle = '     '.$title;
	$engine->show_title = !$pure;
	$engine->events = $show_event ? $real_event : false;
	$engine->fontpath = $config['fontpath'];
	$engine->alert_top = $show_alert ? $alert_high : false;
	$engine->alert_bottom = $show_alert ? $alert_low : false;;
	
	if ($periodo < 10000)
		$engine->xaxis_interval = 8;
	else
		$engine->xaxis_interval = $resolution / 7;
	$engine->yaxis_interval = 1;
	$engine->xaxis_format = 'date';
	
	$engine->single_graph ();
	
	return;
}

// **************************************************************************
// **************************************************************************
//   MAIN Code - Parse get parameters
// **************************************************************************
// **************************************************************************

// Generic parameter handling
// **************************

$id_agent = (int) get_parameter ('id_agent');
$tipo = (string) get_parameter ('tipo');
$pure = (bool) get_parameter ('pure');
$period = (int) get_parameter ('period', 86400);
$interval = (int) get_parameter ('interval', 300);
$id = (string) get_parameter ('id');
$weight_l = (string) get_parameter ('weight_l');
$width = (int) get_parameter ('width', 450);
$height = (int) get_parameter ('height', 200);
$label = (string) get_parameter ('label', '');
$color = (string) get_parameter ('color', '#226677');
$percent = (int) get_parameter ('percent', 100);
$zoom = (int) get_parameter ('zoom', 100);
$zoom /= 100;
if ($zoom <= 0 || $zoom > 1)
	$zoom = 1;
$unit_name = (string) get_parameter ('unit_name');
$draw_events = (int) get_parameter ('draw_events');
$avg_only = (int) get_parameter ('avg_only');
$draw_alerts = (int) get_parameter ('draw_alerts');
$value1 = get_parameter ('value1');
$value2 = get_parameter ('value2');
$value3 = get_parameter("value3", 0);
$stacked = get_parameter ("stacked", 0);
$date = get_parameter ("date");
$graphic_type = (string) get_parameter ('tipo');
$mode = get_parameter ("mode", 1);

if ($graphic_type) {
	switch ($graphic_type) {
	case 'string':
		graphic_string_data ($id, $period, $width, $height, $date);
		
		break;
	case 'sparse': 
		grafico_modulo_sparse ($id, $period, $draw_events, $width, $height,
					$label, $unit_name, $draw_alerts, $avg_only, $pure, $date);
		break;
	case "boolean":
		grafico_modulo_boolean ($id, $period, $draw_events, $width, $height , $label, $unit_name, $draw_alerts, 1, $pure);
		
		break;
	case "estado_incidente":
		grafico_incidente_estados ();
		
		break;
	case "prioridad_incidente":
		grafico_incidente_prioridad ();
		
		break;
	case "db_agente_modulo":
		graph_db_agentes_modulos ($width, $height);
		
		break;
	case "db_agente_paquetes":
		grafico_db_agentes_paquetes ($width, $height);
		
		break;
	case "db_agente_purge":
		grafico_db_agentes_purge ($id, $width, $height);
		
		break;
	case "event_module":
		graph_event_module ($width, $height, $id_agent);
		
		break;
	case "group_events":
		grafico_eventos_grupo ($width, $height);
		
		break;
	case "user_events":
		grafico_eventos_usuario ($width, $height);
		
		break;
	case "total_events":
		grafico_eventos_total ();
		
		break;
	case "group_incident":
		graphic_incident_group ();
		
		break;
	case "user_incident":
		graphic_incident_user ();
		
		break;
	case "source_incident":
		graphic_incident_source ();
		
		break;
	case "user_activity":
		graphic_user_activity ($width, $height);
		
		break;
	case "agentaccess":
		graphic_agentaccess ($_GET["id"], $_GET["periodo"], $width, $height);
		
		break;
	case "agentmodules":
		graphic_agentmodules ($_GET["id"], $width, $height);
		
		break;
	case "progress": 
		$percent = $_GET["percent"];
		progress_bar ($percent,$width,$height, $mode);
		
		break;
	case "combined":
		// Split id to get all parameters
		$module_list = array();
		$module_list = split (",", $id);
		$weight_list = array();
		$weight_list = split (",", $weight_l);
		graphic_combined_module ($module_list, $weight_list, $period, $width, $height,
					$label, $unit_name, $draw_events, $draw_alerts, $pure, $stacked, $date);
		
		break;
	case "alerts_fired_pipe":
		$data = array ();
		$data[__('Alerts fired')] = (float) get_parameter ('fired');
		$data[__('Alerts not fired')] = (float) get_parameter ('not_fired');
		generic_pie_graph ($width, $height, $data);
		
		break;
	case 'monitors_health_pipe':
		$data = array ();
		$data[__('Monitors OK')] = (float) get_parameter ('not_down');
		$data[__('Monitors BAD')] = (float) get_parameter ('down');
		generic_pie_graph ($width, $height, $data);
		
		break;
	default:
		graphic_error ();
	}
} else {
	graphic_error ();
}
?>
