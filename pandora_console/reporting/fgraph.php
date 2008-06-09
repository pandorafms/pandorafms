<?php

// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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

include ('../include/config.php');
include ($config["homedir"].'/include/functions.php');
include ($config["homedir"].'/include/functions_db.php');
require ($config["homedir"].'/include/languages/language_'.$config['language'].'.php');

/**
 * Show a brief error message in a PNG graph
 */

function graphic_error () {
	Header('Content-type: image/png');
	$imgPng = imageCreateFromPng('../images/image_problem.png');
	imageAlphaBlending($imgPng, true);
	imageSaveAlpha($imgPng, true);
	imagePng($imgPng);
}

/**
 * Return a MySQL timestamp date, formatted with actual date MINUS X minutes, 
 *
 * @param int Date in unix format (timestamp)
 *
 * @return string Formatted date string (YY-MM-DD hh:mm:ss)
 */

function dame_fecha($mh){ 
	$m_year = date("Y", time()-$mh*60); 
	$m_month = date("m", time()-$mh*60);
	$m_day = date ("d", time()-$mh*60);
	$m_hour = date ("H", time()-$mh*60);
	$m_min = date ("i", time()-$mh*60);
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
	return date('d/m H:i', $timestamp);
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
 */

function graphic_combined_module (  $module_list, $weight_list, $periodo,                          $width, $height, $title, $unit_name, $show_event=0, $show_alert=0, $pure =0, $stacked = 0){

	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");
	require_once 'Image/Graph.php';
	$resolution = $config['graph_res'] * 50; // Number of "slices" we want in graph
	
	//$unix_timestamp = strtotime($mysql_timestamp) // Convert MYSQL format tio utime
	$fechatope = time() - $periodo; // limit date
	$horasint = $periodo / $resolution; // Each intervalo is $horasint seconds length
	$module_number = count($module_list);

	// intervalo - This is the number of "rows" we are divided the time to fill data.
	//             more interval, more resolution, and slower.
	// periodo - Gap of time, in seconds. This is now to (now-periodo) secs
	
	// Init tables
	for ($y = 0; $y < $module_number; $y++){
		$real_data[$y] = array();
                $mod_data[$y] = 1; // Data multiplier to get the same scale on all modules
		if ($show_event == 1)
			$real_event[$y] = array();
		if (isset($weight_list[$y])){
			if ($weight_list[$y] == 0)
				$weight_list[$y] = 1;
		} else
			$weight_list[$y] = 1;
	}

	$max_value = 0;
	$min_value = 0;
	// FOR EACH MODULE IN module_list....
	for ($y = 0; $y < $module_number; $y++){	
		$id_agente_modulo = $module_list[$y];
		$nombre_agente = dame_nombre_agente_agentemodulo($id_agente_modulo);
		$id_agente = dame_agente_id($nombre_agente);
		$nombre_modulo = dame_nombre_modulo_agentemodulo($id_agente_modulo);
		$module_list_name[$y] = substr($nombre_agente,0,9)." / ".substr($nombre_modulo,0,20);
		for ($x = 0; $x <= $resolution; $x++) {
			$valores[$x][0] = 0; // SUM of all values for this interval
			$valores[$x][1] = 0; // counter
			$valores[$x][2] = $fechatope + ($horasint * $x); // [2] Top limit for this range
			$valores[$x][3] = $fechatope + ($horasint*($x+1)); // [3] Botom limit
			$valores[$x][4] = 0; // MIN
			$valores[$x][5] = 0; // MAX
			$valores[$x][6] = 0; // Event
		}
		// Init other general variables

		if ($show_event == 1){
			// If we want to show events in graphs
			$sql1="SELECT utimestamp FROM tevento WHERE id_agente = $id_agente AND utimestamp > $fechatope";
			$result=mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
				$utimestamp = $row[0];
				for ($i=0; $i <= $resolution; $i++) {
					if ( ($utimestamp <= $valores[$i][3]) && ($utimestamp >= $valores[$i][2]) ){
						$real_event[$i]=1;
					}
				}
			}
		}
		$alert_high = 0;
		$alert_low = 10000000;
		if ($show_alert == 1){
			// If we want to show alerts limits
			$sql1="SELECT * FROM talerta_agente_modulo where id_agente_modulo = ".$id_agente_modulo;
			$result=mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
				if ($row["dis_max"] > $alert_high)
					$alert_high = $row["dis_max"];
				if ($row["dis_min"] < $alert_low)
					$alert_low = $row["dis_min"];
			}
		}
		$previous=0;
		// Get the first data outsite (to the left---more old) of the interval given
		$sql1="SELECT datos, utimestamp FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp < $fechatope ORDER BY utimestamp DESC LIMIT 1";
		if ($result=mysql_query($sql1)){
		        $row=mysql_fetch_array($result);
			$previous = $row[0];
                }
		
		$sql1="SELECT datos,utimestamp FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp >= $fechatope";
		if ($result=mysql_query($sql1))
		while ($row=mysql_fetch_array($result)){
			$datos = $row[0];
			$utimestamp = $row[1];
			for ($i=0; $i <= $resolution; $i++) {
				if ( ($utimestamp <= $valores[$i][3]) && ($utimestamp > $valores[$i][2]) ){
					$valores[$i][0]=$valores[$i][0]+$datos;
					$valores[$i][1]++;
					// Init min value
					if ($valores[$i][4] == 0)
						$valores[$i][4] = $datos;
					else {
						// Check min value
						if ($datos < $valores[$i][4])
						$valores[$i][4] = $datos;
					}			
					// Check max value
					if ($datos > $valores[$i][5])
						$valores[$i][5] = $datos;
					$i = $resolution+1; // BREAK FOR
				}
			}
		}

		
		// Calculate Average value for $valores[][0]
		for ($x =0; $x <= $resolution; $x++) {
			if ($valores[$x][1] > 0){
				$real_data[$y][$x] =  $weight_list[$y] * ($valores[$x][0]/$valores[$x][1]);
                                $valores[$x][0] = $valores[$x][0]/$valores[$x][1];
			} else {
				$valores[$x][0] = $previous;
				$real_data[$y][$x] = $previous * $weight_list[$y];
				$valores[$x][4] = $previous;
				$valores[$x][5] = $previous;
			}
			// Get max value for all graph
			if ($valores[$x][5] > $max_value ){
                                $max_value = $valores[$x][5];
                        }
                        // This stores in mod_data max values for each module
                        if ($mod_data[$y] < $valores[$x][5]){
                                $mod_data[$y] = $valores[$x][5];
                        }
			// Take prev. value
			// TODO: CHeck if there are more than 24hours between
			// data, if there are > 24h, module down.
			$previous = $valores[$x][0];
		}
	}

        for ($y = 0; $y < $module_number; $y++){
                // Disabled autoadjusment, is not working fine :(
                // $weight_list[$y] = ($max_value / $mod_data[$y]) + ($weight_list[$y]-1);
                if ($weight_list[$y] != 1)
                        $module_list_name[$y] .= " (x". format_numeric($weight_list[$y],1).")";
	        $module_list_name[$y] = $module_list_name[$y]." (MAX: ".format_numeric($mod_data[$y]).")";
        }

	// Create graph
	// *************
	$Graph =& Image_Graph::factory('graph', array($width, $height));
	// add a TrueType font


	if ($periodo == 86400)
		$title_period = "Last day";
	elseif ($periodo == 604800)
		$title_period = "Last week";
	elseif ($periodo == 3600)
		$title_period = "Last hour";
	elseif ($periodo == 2419200)
		$title_period = "Last month";
	else
		$title_period = "Last ".format_numeric(($periodo / (3600*24)),2)." days";
	if ($pure == 0){
		$Font =& $Graph->addNew('font', $config['fontpath']);
		$Font->setSize(6);
		$Graph->setFont($Font);
		$Graph->add(
		Image_Graph::vertical(
			Image_Graph::vertical(
						$Title = Image_Graph::factory('title', array('   Pandora FMS Graph - '. $title_period, 10)),
						$Subtitle = Image_Graph::factory('title', array('     '.$title, 7)),
						90
				),
			Image_Graph::vertical(
				$Plotarea = Image_Graph::factory('plotarea'),
				$Legend = Image_Graph::factory('legend'),
				80
				),
			20)
		);
		$Legend->setPlotarea($Plotarea);
		$Title->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
		$Subtitle->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
	} else {
		$Font =& $Graph->addNew('font', $config['fontpath']);
		$Font->setSize(6);
		$Graph->setFont($Font);
		$Graph->add(
			Image_Graph::vertical(
				$Plotarea = Image_Graph::factory('plotarea'),
				$Legend = Image_Graph::factory('legend'),
				85
				)
		);
		$Legend->setPlotarea($Plotarea);
	}
	// Create the dataset
	// Merge data into a dataset object (sancho)
	// $Dataset =& Image_Graph::factory('dataset');

	for ($y = 0; $y < $module_number; $y++){
		$dataset[$y] = Image_Graph::factory('dataset');
		$dataset[$y] -> setName($module_list_name[$y]);
	}
	if ($show_event == 1){
		$dataset_event = Image_Graph::factory('dataset');
		$dataset_event -> setName("Event Fired");
	}
	
	// ... and populated with data ...
	for ($cc=0; $cc < $resolution; $cc++) {
		$tdate = date('d/m', $valores[$cc][2])."\n".date('H:i', $valores[$cc][2]);
		for ($y = 0; $y < $module_number; $y++){
			$dataset[$y]->addPoint($tdate, $real_data[$y][$cc]);
			if (($show_event == 1) AND (isset($real_event[$cc]))) {
				$dataset_event->addPoint($tdate, $max_value);
			}
		}
	}

	if ($max_value > 0){
		// Show events !
		if ($show_event == 1){
			$Plot =& $Plotarea->addNew('Plot_Impulse', array($dataset_event));
			$Plot->setLineColor( 'black' );
			$Marker_event =& Image_Graph::factory('Image_Graph_Marker_Cross');
			$Plot->setMarker($Marker_event);
			$Marker_event->setFillColor( 'red' );
			$Marker_event->setLineColor( 'red' );
			$Marker_event->setSize ( 5 );
		}
		
		// Show limits (for alert or whathever you want...
		if ($show_alert == 1){
			$Plot =& $Plotarea->addNew('Image_Graph_Axis_Marker_Area', IMAGE_GRAPH_AXIS_Y);
			$Plot->setFillColor( 'blue@0.1' );
			$Plot->setLowerBound( $alert_low);
			$Plot->setUpperBound( $alert_high );
		}
		
			
		// create the 1st plot as smoothed area chart using the 1st dataset
        if ($stacked == 0){
		// Non-stacked
            $Plot =& $Plotarea->addNew('area', array(&$dataset));
        } else {
        // Stacked (> 2.0)
            $Plot =& $Plotarea->addNew('Image_Graph_Plot_Area', array(&$dataset, 'stacked'));
        }
		$Plot->setLineColor('gray@0.4');
		$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
		// $AxisX->Hide();
		$AxisY =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
		$AxisY->setLabelOption("showtext",true);
		$AxisY->setLabelInterval(ceil($max_value / 5));
		$AxisY->showLabel(IMAGE_GRAPH_LABEL_ZERO);
		if ($unit_name != "")
			$AxisY->setTitle($unit_name, 'vertical');
		$AxisX->setLabelInterval($resolution / 10);		
		//$AxisY->forceMinimum($minvalue);
		//$AxisY->forceMaximum($max_value+($max_value/12)) ;
		$GridY2 =& $Plotarea->addNew('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$GridY2->setLineColor('gray');
		$GridY2->setFillColor('lightgray@0.05');
		// set line colors
		$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');
		$Plot->setFillStyle($FillArray);
		$FillArray->addColor('#BFFF51@0.6'); // Green
		$FillArray->addColor('yellow@0.6'); // yellow
		$FillArray->addColor('#FF5FDF@0.6'); // pink
		$FillArray->addColor('orange@0.6'); // orange
		$FillArray->addColor('#7D8AFF@0.6'); // blue
		$FillArray->addColor('#FF302A@0.6'); // red
		$FillArray->addColor('brown@0.6'); // brown
		$FillArray->addColor('green@0.6');
		$AxisY_Weather =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
		$Graph->done();
	} else
		graphic_error ();
}

function grafico_modulo_sparse ( $id_agente_modulo, $periodo, $show_event,
				 $width, $height , $title, $unit_name, $show_alert, $avg_only = 0, $pure=0, $time_reference = "" ) {
	
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");
	require_once 'Image/Graph.php';

	$resolution = $config["graph_res"] * 50; // Number of "slices" we want in graph
	
    if ($time_reference != ""){
	    $unix_timestamp = strtotime($time_reference);
        $fechatope = $unix_timestamp - $periodo;
    }
    else
	    $fechatope = time() - $periodo; // limit date

	$horasint = $periodo / $resolution; // Each intervalo is $horasint seconds length
	$nombre_agente = dame_nombre_agente_agentemodulo($id_agente_modulo);
	$id_agente = dame_agente_id($nombre_agente);
	$nombre_modulo = dame_nombre_modulo_agentemodulo($id_agente_modulo);

	if ($show_event == 1)
		$real_event = array();

	if ($show_alert == 1){
		$alert_high = 0;
		$alert_low = 10000000;
		// If we want to show alerts limits
		$sql1="SELECT * FROM talerta_agente_modulo where id_agente_modulo = ".$id_agente_modulo;
		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			if ($row["dis_max"] > $alert_high)
				$alert_high = $row["dis_max"];
			if ($row["dis_min"] < $alert_low)
				$alert_low = $row["dis_min"];
		}
		// if no valid alert defined to render limits, disable it
		if (($alert_low == 10000000) && ($alert_high == 0)){
			$show_alert = 0;
		}
	}

	// intervalo - This is the number of "rows" we are divided the time
	 // to fill data. more interval, more resolution, and slower.
	// periodo - Gap of time, in seconds. This is now to (now-periodo) secs
	
	// Init tables
	for ($x = 0; $x <= $resolution; $x++) {
		$valores[$x][0] = 0; // SUM of all values for this interval
		$valores[$x][1] = 0; // counter
		$valores[$x][2] = $fechatope + ($horasint * $x); // [2] Top limit for this range
		$valores[$x][3] = $fechatope + ($horasint*($x+1)); // [3] Botom limit
		$valores[$x][4] = 0; // MIN
		$valores[$x][5] = 0; // MAX
		$valores[$x][6] = 0; // Event
		
	}
	// Init other general variables
	if ($show_event == 1){
		// If we want to show events in graphs
		$sql1="SELECT utimestamp FROM tevento WHERE id_agente = $id_agente AND utimestamp > $fechatope";
		$result=mysql_query($sql1);
		while ($row = mysql_fetch_array($result)){
			$utimestamp = $row[0];
			for ($i=0; $i <= $resolution; $i++) {
				if ( ($utimestamp <= $valores[$i][3]) && ($utimestamp >= $valores[$i][2]) ){
					$real_event[$i]=1;
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
		$sql1="SELECT COUNT(datos) FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp > $fechatope";
		$result=mysql_query($sql1);
		$row=mysql_fetch_array($result);
		$title=$title." [C] ".$row[0];
	*/
	$previous=0;
	// Get the first data outsite (to the left---more old) of the interval given
	$sql1="SELECT datos,utimestamp FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp < $fechatope ORDER BY utimestamp DESC LIMIT 1";
	$result=mysql_query($sql1);
	if ($row=mysql_fetch_array($result))
		$previous=$row[0];
	
	$sql1="SELECT datos,utimestamp FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp > $fechatope";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$datos = $row[0];
		$utimestamp = $row[1];
		if ($datos >= 0) {
			for ($i=0; $i <= $resolution; $i++) {
				if ( ($utimestamp <= $valores[$i][3]) && ($utimestamp >= $valores[$i][2]) ){
					$valores[$i][0]=$valores[$i][0]+$datos;
					$valores[$i][1]++;
					// Init min value
					if ($valores[$i][4] == 0)
						$valores[$i][4] = $datos;
					else {
						// Check min value
						if ($datos < $valores[$i][4])
						 $valores[$i][4] = $datos;
					}			
					// Check max value
					if ($datos > $valores[$i][5])
						 $valores[$i][5] = $datos;
					$i = $resolution+1; // BREAK FOR
				}
			}
		}		
	}
	
	// Calculate Average value for $valores[][0]
	for ($x =0; $x <= $resolution; $x++) {
		if ($valores[$x][1] > 0)
			$valores[$x][0] = $valores[$x][0]/$valores[$x][1];
		else {
			$valores[$x][0] = $previous;
			$valores[$x][4] = $previous;
			$valores[$x][5] = $previous;
		}
		// Get max value for all graph
		if ($valores[$x][5] > $max_value)
			$max_value = $valores[$x][5];
		// Take prev. value
		// TODO: CHeck if there are more than 24hours between
		// data, if there are > 24h, module down.
		$previous = $valores[$x][0];
	}

	// Create graph
	// *************
	$Graph =& Image_Graph::factory('graph', array($width, $height));
	// add a TrueType font
	$Font =& $Graph->addNew('font', $config['fontpath']);
	$Font->setSize(6);
	$Graph->setFont($Font);

	if ($periodo == 86400)
		$title_period = "Last day";
	elseif ($periodo == 604800)
		$title_period = "Last week";
	elseif ($periodo == 3600)
		$title_period = "Last hour";
	elseif ($periodo == 2419200)
		$title_period = "Last month";
	else
		$title_period = "Last ".format_numeric(($periodo / (3600*24)),2)." days";
	if ($pure == 0){
		$Graph->add(
		Image_Graph::vertical(
			Image_Graph::vertical(
						$Title = Image_Graph::factory('title', array('   Pandora FMS Graph - '.strtoupper($nombre_agente)." - ".$title_period, 10)),
						$Subtitle = Image_Graph::factory('title', array('     '.$title, 7)),
						90
				),
			Image_Graph::horizontal(
				$Plotarea = Image_Graph::factory('plotarea'),
				$Legend = Image_Graph::factory('legend'),
				85
				),
			15)
		);
		$Legend->setPlotarea($Plotarea);
		$Title->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
		$Subtitle->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
	} else { // Pure, without title and legends
		$Graph->add($Plotarea = Image_Graph::factory('plotarea'));
	}
	// Create the dataset
	// Merge data into a dataset object (sancho)
	// $Dataset =& Image_Graph::factory('dataset');
	if ($avg_only == 1) {
		$dataset[0] = Image_Graph::factory('dataset');
		$dataset[0]->setName("Avg.");
	} else {
		$dataset[0] = Image_Graph::factory('dataset');
		$dataset[0]->setName("Max.");
		$dataset[1] = Image_Graph::factory('dataset');
		$dataset[1]->setName("Avg.");
		$dataset[2] = Image_Graph::factory('dataset');
		$dataset[2]->setName("Min.");
	}
	// Event dataset creation
	if ($show_event == 1){
		$dataset_event = Image_Graph::factory('dataset');
		$dataset_event -> setName("Event Fired");
	}
	// ... and populated with data ...
	for ($cc=0; $cc <= $resolution; $cc++) {
		$tdate = date('d/m', $valores[$cc][2])."\n".date('H:i', $valores[$cc][2]);
		if ($avg_only == 0) {
			$dataset[1]->addPoint($tdate, $valores[$cc][0]);
			$dataset[0]->addPoint($tdate, $valores[$cc][5]);
			$dataset[2]->addPoint($tdate, $valores[$cc][4]);
		} else {
			$dataset[0]->addPoint($tdate, $valores[$cc][0]);
		}
		if (($show_event == 1) AND (isset($real_event[$cc]))) {
			$dataset_event->addPoint($tdate, $valores[$cc][5]);
		}
	}

	if ($max_value > 0){
		// Show alert limits 
		if ($show_alert == 1){
			$Plot =& $Plotarea->addNew('Image_Graph_Axis_Marker_Area', IMAGE_GRAPH_AXIS_Y);
			$Plot->setFillColor( 'blue@0.1' );
			$Plot->setLowerBound( $alert_low);
			$Plot->setUpperBound( $alert_high );
		}

		// create the 1st plot as smoothed area chart using the 1st dataset
		$Plot =& $Plotarea->addNew('area', array(&$dataset));
		if ($avg_only == 1){
			$Plot->setLineColor('black@0.1');
		} else {
			$Plot->setLineColor('yellow@0.2');
		}

		$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
		// $AxisX->Hide();
		
		$AxisY =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
		$AxisY->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', 'format_for_graph'));
		$AxisY->setLabelOption("showtext",true);
		$yinterval = $height / 30;
		$AxisY->setLabelInterval(ceil($max_value / $yinterval));
		$AxisY->showLabel(IMAGE_GRAPH_LABEL_ZERO);
		if ($unit_name != "")
			$AxisY->setTitle($unit_name, 'vertical');
		if ($periodo < 10000)
			$xinterval = 8;
		else
			$xinterval = $resolution / 7 ;
		$AxisX->setLabelInterval($xinterval) ;
		//$AxisY->forceMinimum($minvalue);
		$AxisY->forceMaximum($max_value+($max_value/12)) ;
		$GridY2 =& $Plotarea->addNew('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$GridY2->setLineColor('gray');
		$GridY2->setFillColor('lightgray@0.05');
		// set line colors
		$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');

		$Plot->setFillStyle($FillArray);
		if ($avg_only == 1){
			$FillArray->addColor('green@0.6');
		} else {
			$FillArray->addColor('yellow@0.5'); 
			$FillArray->addColor('orange@0.6'); 
			$FillArray->addColor('#e37907@0.7');
			$FillArray->addColor('red@0.7');
			$FillArray->addColor('blue@0.7');
			$FillArray->addColor('green@0.7');
			$FillArray->addColor('black@0.7');
		}
		$AxisY_Weather =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);

		// Show events !
		if ($show_event == 1){
			$Plot =& $Plotarea->addNew('Plot_Impulse', array($dataset_event));
			$Plot->setLineColor( 'red' );
			$Marker_event =& Image_Graph::factory('Image_Graph_Marker_Cross');
			$Plot->setMarker($Marker_event);
			$Marker_event->setFillColor( 'red' );
			$Marker_event->setLineColor( 'red' );
			$Marker_event->setSize ( 5 );
		}

		$Graph->done();
	} else
		graphic_error ();
}

function generic_pie_graph ($width=300, $height=200, $data, $legend) {
	require ("../include/config.php");
	require_once 'Image/Graph.php';
	require ("../include/languages/language_".$config['language'].".php");
	if (sizeof($data) > 0){
		// create the graph
		$driver=& Image_Canvas::factory('png',array('width'=>$width,'height'=>$height,'antialias' => 'native'));
		$Graph = & Image_Graph::factory('graph', $driver);
		// add a TrueType font
		$Font =& $Graph->addNew('font', $config['fontpath']);
		// set the font size to 7 pixels
		$Font->setSize(7);
		$Graph->setFont($Font);
		// create the plotarea
		$Graph->add(
			Image_Graph::horizontal(
				$Plotarea = Image_Graph::factory('plotarea'),
				$Legend = Image_Graph::factory('legend'),
			50
			)
		);
		$Legend->setPlotarea($Plotarea);
		// Create the dataset
		// Merge data into a dataset object (sancho)
		$Dataset1 =& Image_Graph::factory('dataset');
		for ($a=0;$a < sizeof($data); $a++){
			$Dataset1->addPoint(str_pad($legend[$a],15), $data[$a]);
		}
		$Plot =& $Plotarea->addNew('pie', $Dataset1);
		$Plotarea->hideAxis();
		// create a Y data value marker
		$Marker =& $Plot->addNew('Image_Graph_Marker_Value', IMAGE_GRAPH_PCT_Y_TOTAL);
		// create a pin-point marker type
		$PointingMarker =& $Plot->addNew('Image_Graph_Marker_Pointing_Angular', array(1, &$Marker));
		// and use the marker on the 1st plot
		$Plot->setMarker($PointingMarker);
		// format value marker labels as percentage values
		$Marker->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Formatted', '%0.1f%%'));
		$Plot->Radius = 15;
		$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');
		$Plot->setFillStyle($FillArray);
		
		$FillArray->addColor('green@0.7');
		$FillArray->addColor('yellow@0.7');
		$FillArray->addColor('red@0.7');
		$FillArray->addColor('orange@0.7');
		$FillArray->addColor('blue@0.7');
		$FillArray->addColor('purple@0.7');
		$FillArray->addColor('lightgreen@0.7');
		$FillArray->addColor('lightblue@0.7');
		$FillArray->addColor('lightred@0.7');
		$FillArray->addColor('grey@0.6', 'rest');
		$Plot->explode(6);
		$Plot->setStartingAngle(0);
		// output the Graph
		$Graph->done();
	} else 
		graphic_error ();
}


function graphic_agentmodules($id_agent, $width, $height) {
	include ("../include/config.php");
	require_once 'Image/Graph.php';
	require ("../include/languages/language_".$config['language'].".php");

	$sql1="SELECT * FROM ttipo_modulo";
	$result=mysql_query($sql1);
	$ax = 0;
	while ($row=mysql_fetch_array($result)){
		$data_label[$ax]=$row["nombre"]; 
		$data[$ax]=0;
		$data_id[$ax] = $row["id_tipo"];
		$ax++;
	}
	$cx=0;
	$sql1="SELECT * FROM tagente_modulo WHERE id_agente = ".$id_agent;
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$cx++;
		for ($bx=0;$bx<=$ax;$bx++){
			if (isset($data_id[$bx])){
				if ($data_id[$bx] == $row["id_tipo_modulo"]){
					$data[$bx]++;
				}
			}
		}		
	}
	$data2 = "";
	$data_label2 = "";
	$mayor = 0;
	$mayor_data =0;
	for ($a=0;$a < sizeof($data); $a++)
		if ($data[$a] > $mayor_data){
			$mayor = $a;
			$mayor_data = $data[$a];
		}
	$bx=0;
	for ($a=0;$a < sizeof($data_label); $a++){
		if ($data[$a] > 0){
			$data_label2[$bx] = $data_label[$a];
			$data2[$bx] = $data[$a];
			$bx++;
		}
	}
	generic_pie_graph ($width, $height, $data2, $data_label2);

}

function graphic_agentaccess($id_agent, $periodo, $width, $height){
	include ("../include/config.php");
	require_once 'Image/Graph.php';
	require ("../include/languages/language_".$config['language'].".php");
	$color ="#437722"; // Green pandora 1.1 octopus color
	/*
	$agent_interval = give_agentinterval($id_agent);
	$intervalo = 30 * $config['graph_res']; // Desired interval / range between dates
	$intervalo_real = (86400 / $agent_interval); // 60x60x24 secs
	if ($intervalo_real < $intervalo ) {
		$intervalo = $intervalo_real;
		
	}*/
	$intervalo = 24;
	$fechatope = dame_fecha($periodo);
	$horasint = $periodo / $intervalo;

	// $intervalo now stores "ideal" interval			}
	// interval is the number of rows that will store data. more rows, more resolution

	// Para crear las graficas vamos a crear un array de Ax4 elementos, donde
	// A es el numero de posiciones diferentes en la grafica (30 para un mes, 7 para una semana, etc)
	// y los 4 valores en el ejeY serian los detallados a continuacion:
	// Rellenamos la tabla con un solo select, y los calculos se hacen todos sobre memoria
	// esto acelera el tiempo de calculo al maximo, aunque complica el algoritmo :-)
	
	// Creamos la tabla (array) con los valores para el grafico. Inicializacion
	for ($x = 0; $x <$intervalo; $x++) {
		$valores[$x][0] = 0; // [0] Valor (contador)
		$valores[$x][1] = 0; // [0] Valor (contador)
		$valores[$x][2] = dame_fecha($horasint * $x); // [2] Rango superior de fecha para ese rango
		$valores[$x][3] = dame_fecha($horasint*($x+1)); // [3] Rango inferior de fecha para ese rango
	}
	$sql1="SELECT * FROM tagent_access WHERE id_agent = ".$id_agent." and timestamp > '".$fechatope."'";

	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		for ($i = 0; $i < $intervalo; $i++){
			if (($row["timestamp"] < $valores[$i][2]) and ($row["timestamp"] >= $valores[$i][3]) ){ 
				// entra en esta fila
				$valores[$i][0]++;
			}
		} 
		
	}
	$valor_maximo = 0;
	for ($i = 0; $i < $intervalo; $i++) { // 30 entries in graph, one by day
		$grafica[]=$valores[$i][0];
		if ($valores[$i][0] > $valor_maximo)
			$valor_maximo = $valores[$i][0];
	}

	// Create graph
	// create the graph
	$Graph =& Image_Graph::factory('graph', array($width, $height));
	// add a TrueType font
	$Font =& $Graph->addNew('font', $config['fontpath']);
	$Font->setSize(6);
	$Graph->setFont($Font);
	$Graph->add(
	Image_Graph::vertical(
		Image_Graph::factory('title', array("", 2)),
		$Plotarea = Image_Graph::factory('plotarea'),
		0)
	);
	// Create the dataset
	// Merge data into a dataset object (sancho)
	$Dataset =& Image_Graph::factory('dataset');
	for ($a=0;$a < sizeof($grafica); $a++){
		$Dataset->addPoint($a,$grafica[$a]);
	}
	// create the 1st plot as smoothed area chart using the 1st dataset
	$Plot =& $Plotarea->addNew('area', array(&$Dataset));
	// set a line color
	$Plot->setLineColor('green');
	// set a standard fill style
	$Plot->setFillColor('green@0.5');
	// $Plotarea->hideAxis();
	$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
	// $AxisX->Hide();

	$AxisY =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
	$AxisY->setLabelOption("showtext",true);
	$AxisY->setLabelInterval($valor_maximo / 2);
	
	$AxisX->setLabelInterval($intervalo / 5);

	$GridY2 =& $Plotarea->addNew('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
	$GridY2->setLineColor('green');
	$GridY2->setFillColor('green@0.2');
	$AxisY2 =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y_SECONDARY);
	$Graph->done();
}

function grafico_incidente_estados() {
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");

	$data = array(0,0,0,0);
	// 0 - Abierta / Sin notas
	// 2 - Descartada
	// 3 - Caducada 
	// 13 - Cerrada
	$sql1="SELECT * FROM tincidencia";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		if ($row["estado"] == 0)
			$data[0]=$data[0]+1;
		if ($row["estado"] == 2)
			$data[1]=$data[1]+1;
		if ($row["estado"] == 3)
			$data[2]=$data[2]+1;
		if ($row["estado"] == 13)
			$data[3]=$data[3]+1;
	}
	$mayor = 0;
	$mayor_data =0;
	for ($a=0;$a < sizeof($data); $a++){
		if ($data[$a] > $mayor_data){
			$mayor = $a;
			$mayor_data = $data[$a];
		}
	}
	$legend = array ("Open Incident", "Closed Incident", "Outdated", "Invalid");
	generic_pie_graph (370, 180,$data, $legend);
}

function grafico_incidente_prioridad() {
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");

	$data = array(0,0,0,0,0,0);
	// 0 - Abierta / Sin notas
	// 2 - Descartada
	// 3 - Caducada 
	// 13 - Cerrada
	$sql1="SELECT * FROM tincidencia";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		if ($row["prioridad"] == 0)
			$data[0]=$data[0]+1;
		if ($row["prioridad"] == 1)
			$data[1]=$data[1]+1;
		if ($row["prioridad"] == 2)
			$data[2]=$data[2]+1;
		if ($row["prioridad"] == 3)
			$data[3]=$data[3]+1;
		if ($row["prioridad"] == 4)
			$data[4]=$data[4]+1;
		if ($row["prioridad"] == 10)
			$data[5]=$data[5]+1;
	}
		
	$mayor = 0;
	$mayor_data =0;
	for ($a=0;$a < sizeof($data); $a++)
		if ($data[$a] > $mayor_data){
				$mayor = $a;
				$mayor_data = $data[$a];
		}
    $legend = array ("Informative","Low","Medium","Serious", "Very serious", "Maintance");
	generic_pie_graph (320, 200, $data, $legend);
}

function graphic_incident_group() {
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");

	$data = array();
	$legend = array();
	$sql1="SELECT distinct id_grupo FROM tincidencia ";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
			$sql1="SELECT COUNT(id_incidencia) FROM tincidencia WHERE id_grupo = ".$row[0];
			$result2=mysql_query($sql1);
			$row2=mysql_fetch_array($result2);
			$data[] = $row2[0];
			$legend[] = dame_nombre_grupo($row[0])."(".$row2[0].")";
	}
// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
	// or much better, pay me to do a special version for you, highly optimized :-))))
	for ($a=0;$a < sizeof($data);$a++){
			for ($b=$a; $b <sizeof($data); $b++)
			if ($data[$b] > $data[$a]){
					$temp = $data[$a];
					$temp_label = $legend[$a];
					$data[$a] = $data[$b];
					$legend[$a] = $legend[$b];
					$data[$b] = $temp;
					$legend[$b] = $temp_label;
			}
	}
	$mayor = 0;
	$mayor_data =0;
	for ($a=0;$a < sizeof($data); $a++){
		if ($data[$a] > $mayor_data){
				$mayor = $a;
				$mayor_data = $data[$a];
		}
	}
	generic_pie_graph (320, 200, $data, $legend);
}

function graphic_incident_user() {
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");

	$data = array();
	$legend = array();
	$sql1="SELECT distinct id_usuario FROM tincidencia ";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$sql1="SELECT COUNT(id_incidencia) FROM tincidencia WHERE id_usuario = '".$row[0]."'";
		$result2=mysql_query($sql1);
		$row2=mysql_fetch_array($result2);
		$data[] = $row2[0];
		$legend[] = $row[0]."(".$row2[0].")";
	}
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
	// or much better, pay me to do a special version for you, highly optimized :-))))
	for ($a=0;$a < sizeof($data);$a++){
			for ($b=$a; $b <sizeof($data); $b++)
			if ($data[$b] > $data[$a]){
					$temp = $data[$a];
					$temp_label = $legend[$a];
					$data[$a] = $data[$b];
					$legend[$a] = $legend[$b];
					$data[$b] = $temp;
					$legend[$b] = $temp_label;
			}
	}
	$mayor = 0;
	$mayor_data =0;
	for ($a=0;$a < sizeof($data); $a++){
			if ($data[$a] > $mayor_data){
					$mayor = $a;
					$mayor_data = $data[$a];
			}
	}
	generic_pie_graph (320, 200, $data, $legend);
}

function graphic_user_activity($width=350, $height=230) {
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");

	$data = array();
	$legend = array();
	$sql1="SELECT DISTINCT ID_usuario FROM tsesion ";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$entrada= entrada_limpia($row[0]);
		$sql1='SELECT COUNT(ID_usuario) FROM tsesion WHERE ID_usuario = "'.$entrada.'"';
		$result2=mysql_query($sql1);
		$row2=mysql_fetch_array($result2);
		$data[] = $row2[0];
		$legend[] = str_pad(substr($row[0],0,16)."(".format_for_graph($row2[0],0).")", 15);
	}

	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
	// or much better, pay me to do a special version for you, highly optimized :-))))
	for ($a=0;$a < sizeof($data);$a++){
		for ($b=$a; $b <sizeof($data); $b++)
		if ($data[$b] > $data[$a]){
			$temp = $data[$a];
			$temp_label = $legend[$a];
			$data[$a] = $data[$b];
			$legend[$a] = $legend[$b];
			$data[$b] = $temp;
			$legend[$b] = $temp_label;
		}
	}

	// Take only the first 5 items
	if (sizeof($data) >= 5){
		for ($a=0;$a < 5;$a++){
			$legend2[]= $legend[$a];
			$data2[] = $data[$a];
		}
	 	generic_pie_graph ($width, $height, $data2, $legend2);
	} else
	 	generic_pie_graph ($width, $height, $data, $legend);
}

function graphic_incident_source ($width=320, $height=200) {
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");

	$data = array();
	$legend = array();
	$sql1="SELECT DISTINCT origen FROM tincidencia";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
			$sql1="SELECT COUNT(id_incidencia) FROM tincidencia WHERE origen = '".$row[0]."'";
			$result2=mysql_query($sql1);
			$row2=mysql_fetch_array($result2);
			$data[] = $row2[0];
			$legend[] = $row[0]."(".$row2[0].")";
	}
// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
	// or much better, pay me to do a special version for you, highly optimized :-))))
	for ($a=0;$a < sizeof($data);$a++){
			for ($b=$a; $b <sizeof($data); $b++)
			if ($data[$b] > $data[$a]){
					$temp = $data[$a];
					$temp_label = $legend[$a];
					$data[$a] = $data[$b];
					$legend[$a] = $legend[$b];
					$data[$b] = $temp;
					$legend[$b] = $temp_label;
			}
	}
	// Take only the first 5 items
	if (sizeof($data) >= 5){
		for ($a=0;$a < 5;$a++){
			$legend2[]= $legend[$a];
			$data2[] = $data[$a];
		}
	 	generic_pie_graph ($width, $height, $data2, $legend2);
	} else
	 	generic_pie_graph ($width, $height, $data, $legend);
}

function grafico_db_agentes_modulos($width, $height) {
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");

	$data = array();
	$legend = array();
	$sql1="SELECT * FROM tagente";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$sql1="SELECT COUNT(id_agente_modulo) FROM tagente_modulo WHERE id_agente = ".$row["id_agente"];;
		$result2=mysql_query($sql1);
		$row2=mysql_fetch_array($result2);
		$data[] = $row2[0];
		$legend[] = $row["nombre"];
	}
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
	// or much better, pay me to do a special version for you, highly optimized :-))))
	for ($a=0;$a < sizeof($data);$a++){
		for ($b=$a; $b <sizeof($data); $b++)
		if ($data[$b] > $data[$a]){
				$temp = $data[$a];
				$temp_label = $legend[$a];
				$data[$a] = $data[$b];
				$legend[$a] = $legend[$b];
				$data[$b] = $temp;
				$legend[$b] = $temp_label;
		}
	}
	generic_bar_graph ($width, $height, $data, $legend);
}

function grafico_eventos_usuario( $width=420, $height=200) {
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");

	$data = array();
	$legend = array();
	$sql1="SELECT * FROM tusuario";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$sql1="SELECT COUNT(id_evento) FROM tevento WHERE id_usuario = '".$row["id_usuario"]."'";
		$result2=mysql_query($sql1);
		$row2=mysql_fetch_array($result2);
		if ($row2[0] > 0){
			$data[] = $row2[0];
			$legend[] = $row["id_usuario"]." ( $row2[0] )";
		}
	}
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
	// or much better, pay me to do a special version for you, highly optimized :-))))
	for ($a=0;$a < sizeof($data);$a++){
			for ($b=$a; $b <sizeof($data); $b++)
			if ($data[$b] > $data[$a]){
					$temp = $data[$a];
					$temp_label = $legend[$a];
					$data[$a] = $data[$b];
					$legend[$a] = $legend[$b];
					$data[$b] = $temp;
					$legend[$b] = $temp_label;
			}
	}
	// Take only the first 5 items
	if (sizeof($data) >= 5){
		for ($a=0;$a < 5;$a++){
			$legend2[]= $legend[$a];
			$data2[] = $data[$a];
		}
	 	generic_pie_graph ($width, $height, $data2, $legend2);
	} else
	 	generic_pie_graph ($width, $height, $data, $legend);
}

function grafico_eventos_total() {
	require ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");

	$data = array();
	$legend = array();
	$total = 0;
	
	$sql1="SELECT COUNT(id_evento) FROM tevento WHERE estado = 1 ";
	$result=mysql_query($sql1);
	$row=mysql_fetch_array($result);
	$data[] = $row[0];
	$legend[] = "Revised ( $row[0] )";
	$total = $row[0];
	
	$sql1="SELECT COUNT(id_evento) FROM tevento WHERE estado = 0 ";
	$result=mysql_query($sql1);
	$row=mysql_fetch_array($result);
	$data[] = $row[0];
	$total = $total + $row[0];
	$legend[] = "Not Revised ( $row[0] )";

	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
	// or much better, pay me to do a special version for you, highly optimized :-))))
	for ($a=0;$a < sizeof($data);$a++){
			for ($b=$a; $b <sizeof($data); $b++)
			if ($data[$b] > $data[$a]){
					$temp = $data[$a];
					$temp_label = $legend[$a];
					$data[$a] = $data[$b];
					$legend[$a] = $legend[$b];
					$data[$b] = $temp;
					$legend[$b] = $temp_label;
			}
	}
	generic_pie_graph (320, 200, $data, $legend);
}

function grafico_eventos_grupo ($width = 300, $height = 200 ) {
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");

	$data = array();
	$legend = array();
	$sql1="SELECT * FROM tgrupo";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$sql1="SELECT COUNT(id_evento) fROM tevento WHERE id_grupo = ".$row["id_grupo"];
		$result2=mysql_query($sql1);
		$row2=mysql_fetch_array($result2);
		if ($row2[0] > 0){
			$data[] = $row2[0];
			$legend[] = $row["nombre"]." ( $row2[0] )";
		}
	}
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
	// or much better, pay me to do a special version for you, highly optimized :-))))
	for ($a=0;$a < sizeof($data);$a++){
			for ($b=$a; $b <sizeof($data); $b++)
			if ($data[$b] > $data[$a]){
					$temp = $data[$a];
					$temp_label = $legend[$a];
					$data[$a] = $data[$b];
					$legend[$a] = $legend[$b];
					$data[$b] = $temp;
					$legend[$b] = $temp_label;
			}
	}
// Take only the first x items
	if (sizeof($data) >= 7){
		for ($a=0;$a < 7;$a++){
			$legend2[]= $legend[$a];
			$data2[] = $data[$a];
		}
	 	generic_pie_graph ($width, $height, $data2, $legend2);
	} else
	 	generic_pie_graph ($width, $height, $data, $legend);
}


function generic_bar_graph ( $width =380, $height = 200, $data, $legend) {
	include ("../include/config.php");
	require_once 'Image/Graph.php';
	require ("../include/languages/language_".$config['language'].".php");
	
    	if (sizeof($data) > 10){
    		$height = sizeof($legend) * 20;
    	}

	// create the graph
	$Graph =& Image_Graph::factory('graph', array($width, $height));
	// add a TrueType font
	$Font =& $Graph->addNew('font', $config['fontpath']);
	$Font->setSize(9);
	$Graph->setFont($Font);
	$Graph->add(
		Image_Graph::vertical (
			$Plotarea = Image_Graph::factory('plotarea',array('category', 'axis', 'horizontal')),
			$Legend = Image_Graph::factory('legend'),
			100
		)
	);
	
	$Legend->setPlotarea($Plotarea);
	// Create the dataset
	// Merge data into a dataset object (sancho)
	$Dataset1 =& Image_Graph::factory('dataset');
	for ($a=0;$a < sizeof($data); $a++){
		$Dataset1->addPoint(substr($legend[$a],0,22), $data[$a]);
	}
	$Plot =& $Plotarea->addNew('bar', $Dataset1);
	$GridY2 =& $Plotarea->addNew('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
	$GridY2->setLineColor('gray');
	$GridY2->setFillColor('lightgray@0.05');
	$Plot->setLineColor('gray');
	$Plot->setFillColor('blue@0.85');
	$Graph->done(); 
}

function grafico_db_agentes_paquetes ($width = 380, $height = 300) {
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");

	$data = array();
	$legend = array();
	$sql1="SELECT distinct (id_agente) FROM tagente_datos";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		if (! is_null($row["id_agente"])){
			$sql1="SELECT COUNT(id_agente) FROM tagente_datos WHERE id_agente = ".$row["id_agente"];
			$result3=mysql_query($sql1);
			if ($row3=mysql_fetch_array($result3)){
				$agent_name = dame_nombre_agente($row[0]);
				if ($agent_name != ""){
					$data[]= $row3[0];
					$legend[] = str_pad($agent_name,15);
				}
			}
		}
	}
	// Sort array by bubble method (yes, I study more methods in university, but if you want more speed, please, submit a patch :)
	// or much better, pay me to do a special version for you, highly optimized :-))))
	for ($a=0;$a < sizeof($data);$a++){
		for ($b=$a; $b <sizeof($data); $b++)
		if ($data[$b] > $data[$a]){
				$temp = $data[$a];
				$temp_label = $legend[$a];
				$data[$a] = $data[$b];
				$legend[$a] = $legend[$b];
				$data[$b] = $temp;
				$legend[$b] = $temp_label;
		}
	}
	generic_bar_graph ($width, $height, $data, $legend);
}

function grafico_db_agentes_purge ($id_agent, $width, $height) {
	include ("../include/config.php");
	require_once 'Image/Graph.php';
	require ("../include/languages/language_".$config['language'].".php");

    if ($id_agent == 0)
        $id_agent = -1;
	// All data (now)
	$purge_all=date("Y-m-d H:i:s",time());
	
	$data = array();
	$legend = array();
	
	$d90 = time()-(2592000*3);
	$d30 = time()-2592000;
	$d7 = time()-604800;
	$d1 = time()-86400;
	$fechas = array($d90, $d30, $d7, $d1);
	$fechas_label = array("30-90 days","7-30 days","This week","Today");

	// Calc. total packets
        $sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos";;
        $result2=mysql_query($sql1);
        $row2=mysql_fetch_array($result2);
        $total = $row2[0];
	
	for ($a=0; $a < sizeof ($fechas); $a++){
	// 4 x intervals will be enought, increase if your database is very very fast :)
		if ($a==3){
			if ($id_agent == -1)
				$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp >= ".$fechas[$a];
			else
				$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE id_agente = $id_agent AND utimestamp >= ".$fechas[$a];
		} else {
			if ($id_agent == -1)
				$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp >= ".$fechas[$a]." AND utimestamp < ".$fechas[$a+1];
			else
				$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE id_agente = $id_agent AND utimestamp >= ".$fechas[$a]." AND utimestamp < ".$fechas[$a+1];
		}
		$result=mysql_query($sql1);
		$row=mysql_fetch_array($result);
		$data[] =  $row[0];
		$legend[]=$fechas_label[$a]." ( ".format_for_graph($row[0],0)." )";
	}
	generic_pie_graph ($width, $height, $data, $legend);
}

function drawWarning($width,$height) {
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");
	if ($width == 0) {
		$width = 50;
	}
	if ($height == 0) {
		$height = 30;
	}
	
	
	$image = imagecreate($width,$height);
	//colors
	$back = ImageColorAllocate($image,255,255,255);
	$border = ImageColorAllocate($image,0,0,0);
	$red = ImageColorAllocate($image,255,60,75);
	$fill = ImageColorAllocate($image,44,81,150);

	ImageFilledRectangle($image,0,0,$width-1,$height-1,$back);
	ImageRectangle($image,0,0,$width-1,$height-1,$border);
	ImageTTFText($image, 8, 0, ($width/2)-($width/10), ($height/2)+($height/5), $border, $config['fontpath'], $lang_label["no_data"]);
	imagePNG($image);
	imagedestroy($image);
}


function progress_bar($progress,$width,$height) {
   // Copied from the PHP manual:
   // http://us3.php.net/manual/en/function.imagefilledrectangle.php
   // With some adds from sdonie at lgc dot com
   // Get from official documentation PHP.net website. Thanks guys :-)
   // Code ripped from Babel Project :-)
	function drawRating($rating,$width,$height) {
		include ("../include/config.php");
		require ("../include/languages/language_".$config['language'].".php");
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
		$border = ImageColorAllocate($image,0,0,0);
		$red = ImageColorAllocate($image,255,60,75);
		$fill = ImageColorAllocate($image,44,81,150);
		$rating = format_numeric ( $rating, 2);
		ImageFilledRectangle($image,0,0,$width-1,$height-1,$back);
		if ($rating > 100)
			ImageFilledRectangle($image,1,1,$ratingbar,$height-1,$red);
		else
			ImageFilledRectangle($image,1,1,$ratingbar,$height-1,$fill);
		ImageRectangle($image,0,0,$width-1,$height-1,$border);
		if ($rating > 50)
			if ($rating > 100)
				ImageTTFText($image, 8, 0, ($width/4), ($height/2)+($height/5), $back, $config['fontpath'],$lang_label["out_of_limits"]);
			else
				ImageTTFText($image, 8, 0, ($width/2)-($width/10), ($height/2)+($height/5), $back, $config['fontpath'], $rating."%");
		else
			ImageTTFText($image, 8, 0, ($width/2)-($width/10), ($height/2)+($height/5), $border, $config['fontpath'], $rating."%");
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
   		drawRating($progress,$width,$height);
}

/*NOT USED !

function graphic_test ($id, $period, $interval, $label, $width, $height){
	require_once 'Image/Graph.php';
	include ("../include/config.php");
	$color ="#437722"; // Green pandora 1.1 octopus color

	$intervalo = 500; // We want 30 slices for graph resolution.
	$now_date = dame_fecha(0);
	$horasint = $period / $intervalo;
	$top_date = dame_fecha($period);

	// Para crear las graficas vamos a crear un array de Ax4 elementos, donde
	// A es el numero de posiciones diferentes en la grafica (30 para un mes, 7 para una semana, etc)
	// y los 4 valores en el ejeY serian los detallados a continuacion:
	// Rellenamos la tabla con un solo select, y los calculos se hacen todos sobre memoria
	// esto acelera el tiempo de calculo al maximo, aunque complica el algoritmo :-)

	$total_items=5000;
	$factor = rand(1,10); $b=0;
	// This is my temporal data (only a simple static test by now)
	for ($a=0; $a < $total_items; $a++){
		$valor = 1 + cos(deg2rad($b));
		$b = $b + $factor/10;
		if ($b > 180){
			$b =0;
		}
		$valor = $valor * $b ;
		$valores[$a][0] = $valor;
		$valores[$a][1] = $a;
	}

	
	// Creamos la tabla (array) con los valores para el grafico. Inicializacion
	$valor_maximo = 0;
	$maxvalue=0;
	$minvalue=100000000;
	for ($i = $intervalo-1; $i >0; $i--) { // 30 entries in graph, one by day
		$grafica[]=$valores[$i][0];
		$legend[]=$valores[$i][1];
		if ($valores[$i][0] < $minvalue)
			$minvalue = $valores[$i][0];
		if ($valores[$i][0] > $maxvalue)
			$maxvalue = $valores[$i][0];
	}

	// Create graph 
	
		// Create graph
		// create the graph
		$Graph =& Image_Graph::factory('graph', array($width, $height));
		// add a TrueType font
		$Font =& $Graph->addNew('font', $config['fontpath']);
		$Font->setSize(6);
		$Graph->setFont($Font);
		$Graph->add(
		Image_Graph::vertical(
			Image_Graph::factory('title', array("", 2)),
			$Plotarea = Image_Graph::factory('plotarea'),
			0)
		);
		// Create the dataset
		// Merge data into a dataset object (sancho)
		$Dataset =& Image_Graph::factory('dataset');
		for ($a=0;$a < sizeof($grafica); $a++){
			$Dataset->addPoint($legend[$a],$grafica[$a]);
		}
		// create the 1st plot as smoothed area chart using the 1st dataset
		$Plot =& $Plotarea->addNew('area', array(&$Dataset));
		// set a line color
		$Plot->setLineColor('gray');
		// set a standard fill style
		$Plot->setFillColor('green@0.4');
		// $Plotarea->hideAxis();
		$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
		// $AxisX->Hide();
		$AxisY =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
		$AxisY->setLabelOption("showtext",true);
		$AxisY->setLabelInterval(ceil(($maxvalue-$minvalue)/4));
		$AxisX->setLabelInterval($intervalo / 5);
		$AxisY->forceMinimum($minvalue);
		$GridY2 =& $Plotarea->addNew('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$GridY2->setLineColor('blue');
		$GridY2->setFillColor('blue@0.1');
		$AxisY2 =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$Graph->done();

}
*/

function odo_tactic ($value1, $value2, $value3){
	require_once 'Image/Graph.php';
	include ("../include/config.php");
	
	// create the graph
	$driver=& Image_Canvas::factory('png',array('width'=>350,'height'=>260,'antialias' => 'driver'));
	$Graph = & Image_Graph::factory('graph', $driver);
	// add a TrueType font
	$Font =& $Graph->addNew('font', $config['fontpath']);
	// set the font size to 11 pixels
	$Font->setSize(8);
	$Graph->setFont($Font);


	// create the plotarea
	$Graph->add(
			Image_Graph::vertical(
				$Plotarea = Image_Graph::factory('plotarea'),
				$Legend = Image_Graph::factory('legend'),
    		80
			)
	);

	$Legend->setPlotarea($Plotarea);
	$Legend->setAlignment(IMAGE_GRAPH_ALIGN_HORIZONTAL);
	if ($value1 <0)
		$value1=0;
	if ($value2 <0)
                $value2=0;
	if ($value3 <0)
                $value3=0;
	/***************************Arrows************************/
	$Arrows = & Image_Graph::factory('dataset');
	$Arrows->addPoint('Global Health', $value1, 'GLOBAL');
	$Arrows->addPoint('Data Health', $value2, 'DATA');
	$Arrows->addPoint('Monitor Health', $value3, 'MONITOR');

	/**************************PARAMATERS for PLOT*******************/

	// create the plot as odo chart using the dataset
	$Plot =& $Plotarea->addNew('Image_Graph_Plot_Odo',$Arrows);
	$Plot->setRange(0, 100);
	$Plot->setAngles(180, 180);
	$Plot->setRadiusWidth(90);
	$Plot->setLineColor('gray');//for range and outline

	$Marker =& $Plot->addNew('Image_Graph_Marker_Value', IMAGE_GRAPH_VALUE_Y);
	$Plot->setArrowMarker($Marker);

	$Plotarea->hideAxis();
	/***************************Axis************************/
	// create a Y data value marker

	$Marker->setFillColor('transparent');
	$Marker->setBorderColor('transparent');
	$Marker->setFontSize(7);
	$Marker->setFontColor('black');

	// create a pin-point marker type
	$Plot->setTickLength(14);
	$Plot->setAxisTicks(5);
	/********************************color of arrows*************/
	$FillArray = & Image_Graph::factory('Image_Graph_Fill_Array');
	$FillArray->addColor('red@0.8', 'GLOBAL');
	$FillArray->addColor('black.6', 'DATA');
	$FillArray->addColor('blue@0.6', 'MONITOR');

	// create a line array
	$LineArray =& Image_Graph::factory('Image_Graph_Line_Array');
	$LineArray->addColor('red', 'GLOBAL');
	$LineArray->addColor('black', 'DATA');
	$LineArray->addColor('blue', 'MONITOR');
	$Plot->setArrowLineStyle($LineArray);
	$Plot->setArrowFillStyle($FillArray);

	/***************************MARKER OR ARROW************************/
	// create a Y data value marker
	$Marker =& $Plot->addNew('Image_Graph_Marker_Value', IMAGE_GRAPH_VALUE_Y);
	$Marker->setFillColor('white');
	$Marker->setBorderColor('white');
	$Marker->setFontSize(7);
	$Marker->setFontColor('black');
	// create a pin-point marker type
	$PointingMarker =& $Plot->addNew('Image_Graph_Marker_Pointing_Angular', array(20, &$Marker));
	// and use the marker on the plot
	$Plot->setMarker($PointingMarker);
	/**************************RANGE*******************/
	$Plot->addRangeMarker(0, 30);
	$Plot->addRangeMarker(30, 70);
	$Plot->addRangeMarker(70, 100);
	// create a fillstyle for the ranges
	$FillRangeArray = & Image_Graph::factory('Image_Graph_Fill_Array');
	$FillRangeArray->addColor('red@0.8');
	$FillRangeArray->addColor('yellow@0.8');
	$FillRangeArray->addColor('green@0.8');
	$Plot->setRangeMarkerFillStyle($FillRangeArray);
	// output the Graph
	$Graph->done();
}

function grafico_modulo_boolean ( $id_agente_modulo, $periodo, $show_event,
				 $width, $height , $title, $unit_name, $show_alert, $avg_only = 0, $pure=0 ) {
	
	include ("../include/config.php");
	require ("../include/languages/language_".$config['language'].".php");
	require_once 'Image/Graph.php';

	$resolution = $config['graph_res'] * 50; // Number of "slices" we want in graph
	
	//$unix_timestamp = strtotime($mysql_timestamp) // Convert MYSQL format tio utime
	$fechatope = time() - $periodo; // limit date
	$horasint = $periodo / $resolution; // Each intervalo is $horasint seconds length
	$nombre_agente = dame_nombre_agente_agentemodulo($id_agente_modulo);
	$id_agente = dame_agente_id($nombre_agente);
	$nombre_modulo = dame_nombre_modulo_agentemodulo($id_agente_modulo);

	if ($show_event == 1)
		$real_event = array();

	if ($show_alert == 1){
		$alert_high = 0;
		$alert_low = 10000000;
		// If we want to show alerts limits
		$sql1="SELECT * FROM talerta_agente_modulo where id_agente_modulo = ".$id_agente_modulo;
		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			if ($row["dis_max"] > $alert_high)
				$alert_high = $row["dis_max"];
			if ($row["dis_min"] < $alert_low)
				$alert_low = $row["dis_min"];
		}
		// if no valid alert defined to render limits, disable it
		if (($alert_low == 10000000) && ($alert_high == 0)){
			$show_alert = 0;
		}
	}

	// intervalo - This is the number of "rows" we are divided the time
	 // to fill data. more interval, more resolution, and slower.
	// periodo - Gap of time, in seconds. This is now to (now-periodo) secs
	
	// Init tables
	for ($x = 0; $x <= $resolution; $x++) {
		$valores[$x][0] = 0; // SUM of all values for this interval
		$valores[$x][1] = 0; // counter
		$valores[$x][2] = $fechatope + ($horasint * $x); // [2] Top limit for this range
		$valores[$x][3] = $fechatope + ($horasint*($x+1)); // [3] Botom limit
		$valores[$x][4] = -1; // MIN
		$valores[$x][5] = -1; // MAX
		$valores[$x][6] = -1; // Event
	}
	// Init other general variables
	if ($show_event == 1){
		// If we want to show events in graphs
		$sql1="SELECT utimestamp FROM tevento WHERE id_agente = $id_agente AND utimestamp > $fechatope";
		$result=mysql_query($sql1);
		while ($row = mysql_fetch_array($result)){
			$utimestamp = $row[0];
			for ($i=0; $i <= $resolution; $i++) {
				if ( ($utimestamp <= $valores[$i][3]) && ($utimestamp >= $valores[$i][2]) ){
					$real_event[$i]=1;
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
	$previous=0;
	// Get the first data outsite (to the left---more old) of the interval given
	$sql1="SELECT datos,utimestamp FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp < $fechatope ORDER BY utimestamp DESC LIMIT 1";
	$result=mysql_query($sql1);
	if ($row=mysql_fetch_array($result))
		$previous=$row[0];
	
	$sql1="SELECT datos,utimestamp FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp > $fechatope";
	//echo "$sql1<br>";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		$datos = $row[0];
		$utimestamp = $row[1];
		
		$i = round(($utimestamp - $fechatope) / $horasint);
		if (isset($valores[$i][0])){
			$valores[$i][0] += $datos;
			$valores[$i][1]++;
		
			if ($valores[$i][6] == -1)
				$valores[$i][6]=$datos;
			
			// Init min value
			if ($valores[$i][4] == -1)
				$valores[$i][4] = $datos;
			else {
				// Check min value
				if ($datos < $valores[$i][4])
					$valores[$i][4] = $datos;
			}
			// Check max value
			if ($valores[$i][5] == -1)
				$valores[$i][5] = $datos;
			else
				if ($datos > $valores[$i][5])
					$valores[$i][5] = $datos;
		}
	}
	
	
	$last = $previous;
	// Calculate Average value for $valores[][0]
	for ($x =0; $x <= $resolution; $x++) {
		//echo $valores[$x][6] . ", (" . $valores[$x][4] . ", " . $valores[$x][5] . ") :  ";
	
		if ($valores[$x][6] == -1)
			$valores[$x][6] = $last;
		else
			$valores[$x][6] = $valores[$x][4]; // min
			
		$last = $valores[$x][5] != -1 ? $valores[$x][5] : $valores[$x][6]; // max
		
		if ($valores[$x][1] > 0)
			$valores[$x][0] = $valores[$x][0]/$valores[$x][1];
		else {
			$valores[$x][0] = $previous;
			$valores[$x][4] = $previous;
			$valores[$x][5] = $previous;
		}
		// Get max value for all graph
		if ($valores[$x][5] > $max_value)
			$max_value = $valores[$x][5];
		// Take prev. value
		// TODO: CHeck if there are more than 24hours between
		// data, if there are > 24h, module down.
		$previous = $valores[$x][0];
		
		//echo $valores[$x][6];
		//echo "<br>";
	}
//exit;
	// Create graph
	// *************
	$Graph =& Image_Graph::factory('graph', array($width, $height));
	// add a TrueType font
	$Font =& $Graph->addNew('font', $config['fontpath']);
	$Font->setSize(6);
	$Graph->setFont($Font);

	if ($periodo == 86400)
		$title_period = "Last day";
	elseif ($periodo == 604800)
		$title_period = "Last week";
	elseif ($periodo == 3600)
		$title_period = "Last hour";
	elseif ($periodo == 2419200)
		$title_period = "Last month";
	else
		$title_period = "Last ".format_numeric(($periodo / (3600*24)),2)." days";
	if ($pure == 0){
		$Graph->add(
		Image_Graph::vertical(
			Image_Graph::vertical(
						$Title = Image_Graph::factory('title', array('   Pandora FMS Graph - '.strtoupper($nombre_agente)." - ".$title_period, 10)),
						$Subtitle = Image_Graph::factory('title', array('     '.$title, 7)),
						90
				),
			Image_Graph::horizontal(
				$Plotarea = Image_Graph::factory('plotarea'),
				$Legend = Image_Graph::factory('legend'),
				85
				),
			15)
		);
		$Legend->setPlotarea($Plotarea);
		$Title->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
		$Subtitle->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
	} else { // Pure, without title and legends
		$Graph->add($Plotarea = Image_Graph::factory('plotarea'));
	}
	// Create the dataset
	// Merge data into a dataset object (sancho)
	// $Dataset =& Image_Graph::factory('dataset');
	/*
	if ($avg_only == 1) {
		$dataset[0] = Image_Graph::factory('dataset');
		$dataset[0]->setName("Avg.");
	} else {
		$dataset[0] = Image_Graph::factory('dataset');
		$dataset[0]->setName("Max.");
		$dataset[1] = Image_Graph::factory('dataset');
		$dataset[1]->setName("Avg.");
		$dataset[2] = Image_Graph::factory('dataset');
		$dataset[2]->setName("Min.");
	}
	*/
	$dataset[0] = Image_Graph::factory('dataset');
	$dataset[0]->setName("Value");
		
	// Event dataset creation
	if ($show_event == 1){
		$dataset_event = Image_Graph::factory('dataset');
		$dataset_event -> setName("Event Fired");
	}
	// ... and populated with data ...
	for ($cc=0; $cc <= $resolution; $cc++) {
		$tdate = date('d/m', $valores[$cc][2])."\n".date('H:i', $valores[$cc][2]);
		/*
		if ($avg_only == 0) {
			$dataset[1]->addPoint($tdate, $valores[$cc][0]);
			$dataset[0]->addPoint($tdate, $valores[$cc][5]);
			$dataset[2]->addPoint($tdate, $valores[$cc][4]);
		} else {
			$dataset[0]->addPoint($tdate, $valores[$cc][6]); // 0:average 4:min 5:max 6:event
		}
		*/
		$dataset[0]->addPoint($tdate, $valores[$cc][6]);
		
		if (($show_event == 1) AND (isset($real_event[$cc]))) {
			$dataset_event->addPoint($tdate, $valores[$cc][5]);
		}
	}

	if ($max_value > 0){
		// Show alert limits
		if ($show_alert == 1){
			$Plot =& $Plotarea->addNew('Image_Graph_Axis_Marker_Area', IMAGE_GRAPH_AXIS_Y);
			$Plot->setFillColor( 'blue@0.1' );
			$Plot->setLowerBound( $alert_low);
			$Plot->setUpperBound( $alert_high );
		}

		// create the 1st plot as smoothed area chart using the 1st dataset
		$Plot =& $Plotarea->addNew('area', array(&$dataset));
		if ($avg_only == 1){
			$Plot->setLineColor('black@0.1');
		} else {
			$Plot->setLineColor('yellow@0.2');
		}

		$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
		// $AxisX->Hide();
		
		$AxisY =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
		$AxisY->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', 'format_for_graph'));
		$AxisY->setLabelOption("showtext",true);
		$yinterval = $height / 30;
		$AxisY->setLabelInterval(ceil($max_value / $yinterval));
		$AxisY->showLabel(IMAGE_GRAPH_LABEL_ZERO);
		if ($unit_name != "")
			$AxisY->setTitle($unit_name, 'vertical');
		if ($periodo < 10000)
			$xinterval = 8;
		else
			$xinterval = $resolution / 7 ;
		$AxisX->setLabelInterval($xinterval) ;
		//$AxisY->forceMinimum($minvalue);
		$AxisY->forceMaximum($max_value+($max_value/12)) ;
		$GridY2 =& $Plotarea->addNew('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$GridY2->setLineColor('gray');
		$GridY2->setFillColor('lightgray@0.05');
		// set line colors
		$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');

		$Plot->setFillStyle($FillArray);
		/*
		if ($avg_only == 1){
			$FillArray->addColor('green@0.6');
		} else {
			$FillArray->addColor('yellow@0.5');
			$FillArray->addColor('orange@0.6');
			$FillArray->addColor('#e37907@0.7');
			$FillArray->addColor('red@0.7');
			$FillArray->addColor('blue@0.7');
			$FillArray->addColor('green@0.7');
			$FillArray->addColor('black@0.7');
		}
		*/
		$FillArray->addColor('green@0.6');
		$AxisY_Weather =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);

		// Show events !
		if ($show_event == 1){
			$Plot =& $Plotarea->addNew('Plot_Impulse', array($dataset_event));
			$Plot->setLineColor( 'red' );
			$Marker_event =& Image_Graph::factory('Image_Graph_Marker_Cross');
			$Plot->setMarker($Marker_event);
			$Marker_event->setFillColor( 'red' );
			$Marker_event->setLineColor( 'red' );
			$Marker_event->setSize ( 5 );
		}

		$Graph->done();
	} else
		graphic_error ();
}

// **************************************************************************
// **************************************************************************
//   MAIN Code - Parse get parameters
// **************************************************************************
// **************************************************************************

// Generic parameter handling
// **************************

if (isset($_GET["tipo"]))
	$tipo = entrada_limpia($_GET["tipo"]);
else
	$tipo = ""; // 1 day default period

// Pure (Without title and legend)
if ( isset($_GET["pure"]))
	$pure = $_GET["pure"];
else
	$pure = 0;


if (isset($_GET["period"]))
	$period = entrada_limpia($_GET["period"]);
else
	$period = 86400; // 1 day default period

if (isset($_GET["intervalo"]))
	$intervalo = entrada_limpia($_GET["intervalo"]);
else
	$intervalo = 300; // 1 day default period

if (isset($_GET["id"]))
	$id = entrada_limpia($_GET["id"]);
else
	$id = 0;

if (isset($_GET["weight_l"]))
	$weight_l = entrada_limpia($_GET["weight_l"]);
else
	$weight_l = 0;
	
if (isset($_GET["width"]))
	$width = entrada_limpia($_GET["width"]);
else
	$width = 450;

if (isset($_GET["height"]))
	$height = entrada_limpia ($_GET["height"]);
else
	$height = 200;

if (isset($_GET["label"]))
	$label = entrada_limpia ($_GET["label"]);
else
	$label = "";

if (isset($_GET["color"]))
	$color = entrada_limpia ($_GET["color"]);
else
	$color = "#226677";

if (isset($_GET["percent"]))
	$percent = entrada_limpia ($_GET["percent"]);
else
	$percent = "100";

// Zoom
if (isset($_GET['zoom']) and
	is_numeric($_GET['zoom']) and
	$_GET['zoom']>100) 
		$zoom = $_GET['zoom'] / 100 ;
else
	$zoom = 1;

// Unit_name
if (isset($_GET["unit_name"]))
	$unit_name = entrada_limpia ($_GET["unit_name"]);
else
	$unit_name = "";


// Draw Events  ?
if ( isset($_GET["draw_events"]))
	$draw_events = $_GET["draw_events"];
else
	$draw_events = 0;

// Average values only
if ( isset($_GET["avg_only"]))
	$avg_only = $_GET["avg_only"];
else
	$avg_only = 0;

// Draw alert limits ?
if ( isset($_GET["draw_alerts"])) 
	$draw_alerts = $_GET["draw_alerts"];
else
	$draw_alerts = 0;

// Values 1 2 and 3
if ( isset($_GET["value1"]))
	$value1 = $_GET["value1"];
else
	$value1 = 0;
if ( isset($_GET["value2"]))
	$value2 = $_GET["value2"];
else
	$value2 = 0;

$value3 = get_parameter("value3",0);
$stacked = get_parameter ("stacked", 0);
$time_reference = get_parameter ("time_reference", "");

// Image handler
// *****************


if (isset($_GET["tipo"])){
	if ($_GET["tipo"] == "sparse"){
		grafico_modulo_sparse ($id, $period, $draw_events, $width, $height , $label, $unit_name, $draw_alerts, $avg_only, $pure, $time_reference);
	}
	elseif ($_GET["tipo"] =="boolean") 
		grafico_modulo_boolean ($id, $period, $draw_events, $width, $height , $label, $unit_name, $draw_alerts, 1, $pure);
	elseif ($_GET["tipo"] =="estado_incidente") 
		grafico_incidente_estados();	
	elseif ($_GET["tipo"] =="prioridad_incidente") 
		grafico_incidente_prioridad();	
	elseif ($_GET["tipo"]=="db_agente_modulo")
		grafico_db_agentes_modulos($width, $height);
	elseif ($_GET["tipo"]=="db_agente_paquetes")
		grafico_db_agentes_paquetes($width, $height);
	elseif ($_GET["tipo"] =="db_agente_purge")
		grafico_db_agentes_purge($id, $width, $height);
	elseif ($_GET["tipo"] =="group_events")
		grafico_eventos_grupo($width, $height);
	elseif ($_GET["tipo"] =="user_events")
		grafico_eventos_usuario($width, $height);
	elseif ($_GET["tipo"] =="total_events")
		grafico_eventos_total();
	elseif ($_GET["tipo"] =="group_incident")
		graphic_incident_group();
	elseif ($_GET["tipo"] =="user_incident")
		graphic_incident_user();
	elseif ($_GET["tipo"] =="source_incident")
        graphic_incident_source();
	elseif ($_GET["tipo"] =="user_activity")
        graphic_user_activity($width,$height);
	elseif ($_GET["tipo"] == "agentaccess")
		graphic_agentaccess($_GET["id"], $_GET["periodo"], $width, $height);
	elseif ($_GET["tipo"] == "agentmodules")
		graphic_agentmodules($_GET["id"], $width, $height);

	//elseif ($_GET["tipo"] == "gdirect")
//		graphic_test ($id, $period, $intervalo, $label, $width, $height);
	elseif ( $_GET["tipo"] =="progress"){
		$percent= $_GET["percent"];
		progress_bar($percent,$width,$height);
	}
	elseif ( $_GET["tipo"] == "odo_tactic"){
		odo_tactic ( $value1, $value2, $value3 );
	}
	elseif ( $_GET["tipo"] =="combined"){
		// Split id to get all parameters
		$module_list = array();
		$module_list = split ( ",", $id);
		$weight_list = array();
		$weight_list = split ( ",", $weight_l);
		graphic_combined_module ($module_list, $weight_list, $period, $width, $height , $label, $unit_name, $draw_events, $draw_alerts, $pure, $stacked);
	}
	else
		graphic_error ();
} else
	graphic_error ();
?>
