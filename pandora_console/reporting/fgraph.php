<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP additions
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to Pandora FMS 1.2 graph code and new XML reporting template management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// Load global vars
include ("../include/config.php");
include ("../include/functions.php");
include ("../include/functions_db.php");
require ("../include/languages/language_".$language_code.".php");

function graphic_error () {
	Header("Content-type: image/png");
	$imgPng = imageCreateFromPng("../images/image_problem.png");
	imageAlphaBlending($imgPng, true);
	imageSaveAlpha($imgPng, true);
	imagePng($imgPng);
}

function dame_fecha_grafico ($mh, $format){ 
	// Date 24x7x30 hours ago (one month)
	$m_year = date("Y", time()-$mh*60);
	$m_month = date("m", time()-$mh*60);
	$m_month_word = date("M", time()-$mh*60);
	$m_day = date ("d", time()-$mh*60);
	$m_hour = date ("H", time()-$mh*60);
	$m_min = date ("i", time()-$mh*60);
	switch ($format) {
		case 1: $m = $m_month."/".$m_day." ".$m_hour.":".$m_min;
			break;
		case 2: $m = $m_year."-".$m_month."-".$m_day;
			break;
		case 3: $m = $m_day."th -".$m_month_word."\n".$m_year;
			break;
		case 4: $m = $m_day."th -".$m_month_word;
			break;
	}
	return $m;
}

function dame_fecha($mh){ 
	// Return a MySQL timestamp date, formatted with actual date MINUS X minutes, given as parameter
	$m_year = date("Y", time()-$mh*60); 
	$m_month = date("m", time()-$mh*60);
	$m_day = date ("d", time()-$mh*60);
	$m_hour = date ("H", time()-$mh*60);
	$m_min = date ("i", time()-$mh*60);
	$m = $m_year."-".$m_month."-".$m_day." ".$m_hour.":".$m_min.":00";
	return $m;	
}

function dame_fecha_grafico_timestamp ($timestamp) {
	return date('d/m H:i', $timestamp);
}

function graphic_combined_module ($module_list, $weight_list, $periodo, $width, $height , $title, $unit_name, $show_event=0, $show_alert=0 ) {
	include ("../include/config.php");
	require ("../include/languages/language_".$language_code.".php");
	require_once 'Image/Graph.php';
	$resolution = $config_graph_res * 50; // Number of "slices" we want in graph
	
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

		$module_list_name[$y] = substr($nombre_agente,0,8)." - ".substr($nombre_modulo,0,8);
		if ($weight_list[$y] != 1)
			$module_list_name[$y] .= " (x".$weight_list[$y].")";
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
		$alert_low = 0;
		if ($show_alert == 1){
			// If we want to show alerts limits
			$sql1="SELECT * FROM talerta_agente_modulo where id_agente_modulo = ".$id_agente_modulo;
			$result=mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
				if ($row["dis_max"] > $alert_high)
					$alert_high = $row["dis_max"];
				if ($row["dis_max"] > $alert_high)
				$min = $row["dis_min"];
				
			}
		}
		
		$previous=0;
		// Get the first data outsite (to the left---more old) of the interval given
		$sql1="SELECT datos,utimestamp FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp < $fechatope ORDER BY utimestamp DESC LIMIT 1";
		$result=mysql_query($sql1);
		if ($row=mysql_fetch_array($result))
			$previous=$row[0];
		
		$sql1="SELECT datos,utimestamp FROM tagente_datos WHERE id_agente = $id_agente AND id_agente_modulo = $id_agente_modulo AND utimestamp > $fechatope";
		if ($result=mysql_query($sql1))
		while ($row=mysql_fetch_array($result)){
			$datos = $row[0];
			$utimestamp = $row[1];
			if ($datos > 0) {
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
			if ($valores[$x][1] > 0){
				$valores[$x][0] = $valores[$x][0]/$valores[$x][1];
				$real_data[$y][$x] =  $weight_list[$y]*($valores[$x][0]/$valores[$x][1]);
			} else {
				$valores[$x][0] = $previous;
				$real_data[$y][$x] = $previous * $weight_list[$y];
				$valores[$x][4] = $previous;
				$valores[$x][5] = $previous;
			}
			// Get max value for all graph
			if ($valores[$x][5] * $weight_list[$y] > $max_value )
				$max_value = $valores[$x][5] * $weight_list[$y];
			// Take prev. value
			// TODO: CHeck if there are more than 24hours between
			// data, if there are > 24h, module down.
			$previous = $valores[$x][0];
		}
	}

	// Create graph
	// *************
	$Graph =& Image_Graph::factory('graph', array($width, $height));
	// add a TrueType font
	$Font =& $Graph->addNew('font', $config_fontpath);
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

	$Graph->add(
	Image_Graph::vertical(
		Image_Graph::vertical(
            		$Title = Image_Graph::factory('title', array('   Pandora FMS Graph - '.$title_period, 10)),
              		$Subtitle = Image_Graph::factory('title', array('     '.$title, 7)),
            		90
        	), 
		Image_Graph::horizontal(
			$Plotarea = Image_Graph::factory('plotarea'),
			$Legend = Image_Graph::factory('legend'),
   			80
			),
		20)
	);
	$Legend->setPlotarea($Plotarea);
	$Title->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
	$Subtitle->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
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
	for ($cc=0; $cc <= $resolution; $cc++) {
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
		$Plot =& $Plotarea->addNew('area', array(&$dataset));
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
		$AxisY->forceMaximum($max_value+($max_value/12)) ;
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

function grafico_modulo_sparse ( $id_agente_modulo, $periodo, $draw_events,
				 $width, $height , $title, $unit_name ) {
	
	include ("../include/config.php");
	require ("../include/languages/language_".$language_code.".php");
	require_once 'Image/Graph.php';

	$resolution = $config_graph_res * 50; // Number of "slices" we want in graph
	
	//$unix_timestamp = strtotime($mysql_timestamp) // Convert MYSQL format tio utime
	$fechatope = time() - $periodo; // limit date
	$horasint = $periodo / $resolution; // Each intervalo is $horasint seconds length
	$nombre_agente = dame_nombre_agente_agentemodulo($id_agente_modulo);
	$id_agente = dame_agente_id($nombre_agente);
	$nombre_modulo = dame_nombre_modulo_agentemodulo($id_agente_modulo);

	// intervalo - This is the number of "rows" we are divided the time to fill data.
	//             more interval, more resolution, and slower.
	// periodo - Gap of time, in seconds. This is now to (now-periodo) secs
	
	// Init tables
	for ($x = 0; $x <= $resolution; $x++) {
		$valores[$x][0] = 0; // SUM of all values for this interval
		$valores[$x][1] = 0; // counter
		$valores[$x][2] = $fechatope + ($horasint * $x); // [2] Top limit for this range
		$valores[$x][3] = $fechatope + ($horasint*($x+1)); // [3] Botom limit
		$valores[$x][4] = 0; // MIN
		$valores[$x][5] = 0; // MAX
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
		if ($datos > 0) {
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
	$Font =& $Graph->addNew('font', $config_fontpath);
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

	$Graph->add(
	Image_Graph::vertical(
		Image_Graph::vertical(
            		$Title = Image_Graph::factory('title', array('   Pandora FMS Graph - '.$title_period, 10)),
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
	// Create the dataset
	// Merge data into a dataset object (sancho)
	// $Dataset =& Image_Graph::factory('dataset');
	$dataset[0] = Image_Graph::factory('dataset');
	$dataset[0]->setName("Max.");
	$dataset[1] = Image_Graph::factory('dataset');
	$dataset[1]->setName("Avg.");
	$dataset[2] = Image_Graph::factory('dataset');
	$dataset[2]->setName("Min.");

	// ... and populated with data ...
	for ($cc=0; $cc <= $resolution; $cc++) {
		$tdate = date('d/m', $valores[$cc][2])."\n".date('H:i', $valores[$cc][2]);
		$dataset[1]->addPoint($tdate, $valores[$cc][0]);
		$dataset[0]->addPoint($tdate, $valores[$cc][5]);
		$dataset[2]->addPoint($tdate, $valores[$cc][4]);
		//echo "$cc -- $tdate - ".$valores[$cc][0]." -- ".$valores[$cc][4]."--".$valores[$cc][5]."<br>";
		
	}

	if ($max_value > 0){
		// create the 1st plot as smoothed area chart using the 1st dataset
		$Plot =& $Plotarea->addNew('area', array(&$dataset));
		$Plot->setLineColor('yellow@0.1');
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
		$AxisY->forceMaximum($max_value+($max_value/12)) ;
		$GridY2 =& $Plotarea->addNew('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$GridY2->setLineColor('gray');
		$GridY2->setFillColor('lightgray@0.05');
		// set line colors
		$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');
		$Plot->setFillStyle($FillArray);
		$FillArray->addColor('yellow@0.5'); 
		$FillArray->addColor('orange@0.6'); 
		$FillArray->addColor('brown@0.7');
		$FillArray->addColor('red@0.7');
		$FillArray->addColor('blue@0.7');
		$FillArray->addColor('green@0.7');
		$FillArray->addColor('black@0.7');

		$AxisY_Weather =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);



		
		$Graph->done();
	} else
		graphic_error ();
}

function graphic_agentmodules($id_agent, $width, $height) {
	include ("../include/config.php");
	require_once 'Image/Graph.php';
	require ("../include/languages/language_".$language_code.".php");

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


	if ($cx > 1){
		// create the graph
		$Graph =& Image_Graph::factory('graph', array($width, $height));
		// add a TrueType font
		$Font =& $Graph->addNew('font', $config_fontpath);
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
		for ($a=0;$a < sizeof($data2); $a++){
			$Dataset1->addPoint($data_label2[$a], $data2[$a]);
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
		$Plot->setStartingAngle(145);
		// output the Graph
		$Graph->done();
	} else 
		graphic_error ();
}


function graphic_agentaccess($id_agent, $periodo, $width, $height){
	include ("../include/config.php");
	require_once 'Image/Graph.php';
	require ("../include/languages/language_".$language_code.".php");
	$color ="#437722"; // Green pandora 1.1 octopus color
	/*
	$agent_interval = give_agentinterval($id_agent);
	$intervalo = 30 * $config_graph_res; // Desired interval / range between dates
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
	$Font =& $Graph->addNew('font', $config_fontpath);
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
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

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
        for ($a=0;$a < sizeof($data); $a++)
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }
	$graph = new PieGraph(370,180,"auto");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["incident_status"]);
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->setShadow();
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$p1 = new PiePlot3D($data);
 	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.4);
	$p1->SetCenter(0.3);
	$legend = array ("Open Incident", "Closed Incident", "Outdated", "Invalid");
	$p1->SetLegends($legend);
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();
}

function grafico_incidente_prioridad() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

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


	$graph = new PieGraph(370,180,"auto");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["incident_priority"]);
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->SetShadow();
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$p1 = new PiePlot3D($data);
  	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.4);
	$p1->SetCenter(0.3);
	$legend = array ("Informative","Low","Medium","Serious", "Very serious", "Maintance");
	$p1->SetLegends($legend);
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();
}

function graphic_incident_group() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

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

        $ajuste_altura = sizeof($data) * 20;
        $graph = new PieGraph(370,80+$ajuste_altura,'auto');        
        $graph->SetMarginColor('white@0.2');
        $graph->title->Set($lang_label["incident_group"]);
        $graph->title->SetFont(FF_FONT1,FS_BOLD);
        $graph->SetShadow();
        $graph->SetAlphaBlending();
        $graph->SetFrame(true);
        $p1 = new PiePlot3D($data);
        $p1->ExplodeSlice($mayor);
        $p1->SetSize(0.25);
        $p1->SetCenter(0.3);
        $p1->SetLegends($legend);
        $graph->Add($p1);
        $graph->img->SetAntiAliasing();
        $graph->Stroke();
}

function graphic_incident_user() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

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

        $ajuste_altura = sizeof($data) * 20;
        $graph = new PieGraph(370,80+$ajuste_altura,'auto');
        $graph->SetMarginColor('white@0.2');
        $graph->title->Set($lang_label["incident_user"]);
        $graph->title->SetFont(FF_FONT1,FS_BOLD);
        $graph->SetShadow();
        $graph->SetAlphaBlending();
        $graph->SetFrame(true);
        $p1 = new PiePlot3D($data);
        $p1->ExplodeSlice($mayor);
        $p1->SetSize(0.25);
        $p1->SetCenter(0.3);
        $p1->SetLegends($legend);
        $graph->Add($p1);
        $graph->img->SetAntiAliasing();
        $graph->Stroke();
}

function graphic_user_activity() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

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
			$legend[] = substr($row[0],0,16)."(".$row2[0].")";
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

        $ajuste_altura = sizeof($data) * 20;
        $graph = new PieGraph(500,80+$ajuste_altura,'auto');
        $graph->SetMarginColor('white@0.2');
        $graph->title->Set($lang_label["users_statistics"]);
        $graph->title->SetFont(FF_FONT1,FS_BOLD);
        $graph->SetShadow();
        $graph->SetAlphaBlending();
        $graph->SetFrame(true);
        $p1 = new PiePlot3D($data);
        $p1->ExplodeSlice($mayor);
        $p1->SetSize(0.25);
        $p1->SetCenter(0.3);
        $p1->SetLegends($legend);
	$graph->legend->Pos(0.05,0.49,"right","center");
        $graph->Add($p1);
        $graph->img->SetAntiAliasing();
        $graph->Stroke();
}

function graphic_incident_source() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

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
        $mayor = 0;
        $mayor_data =0;
        for ($a=0;$a < sizeof($data); $a++){
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }
        }

        $ajuste_altura = sizeof($data) * 20;
        $graph = new PieGraph(370,80+$ajuste_altura,'auto');
        $graph->SetMarginColor('white@0.2');
        $graph->title->Set($lang_label["incident_source"]);
        $graph->title->SetFont(FF_FONT1,FS_BOLD);
        $graph->SetShadow();
        $graph->SetAlphaBlending();
        $graph->SetFrame(true);
        $p1 = new PiePlot3D($data);
        $p1->ExplodeSlice($mayor);
        $p1->SetSize(0.25);
        $p1->SetCenter(0.3);
        $p1->SetLegends($legend);
        $graph->Add($p1);
        $graph->img->SetAntiAliasing();
        $graph->Stroke();
}
function grafico_db_agentes_modulos() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_bar.php");
	require ("../include/languages/language_".$language_code.".php");

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
	$mayor = 0;
        $mayor_data =0;
        for ($a=0;$a < sizeof($data); $a++)
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }

	$ajuste_altura = sizeof($data) * 20;	
	//$graph = new PieGraph(400,140+$ajuste_altura,"auto");
	$graph = new Graph(400,140+$ajuste_altura,'auto');
	$graph->SetScale("textlin");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["modules_per_agent"]);
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->yaxis->scale->SetGrace(0);
	$graph->yaxis->SetLabelAlign('center','bottom');
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$graph->xaxis->SetLabelMargin(5);
	$graph->Set90AndMargin(100,20,50,30);
	$p1 = new BarPlot($data);
	$p1->value->SetFormat('%.0f ');
	$p1->value->Show();
	$p1->value->SetAlign('left','center');
	$p1->SetFillColor("#00bf00");
	$p1->SetWidth(0.6);
	$p1->SetShadow();
	$graph->yaxis->SetLabelFormat('%d');
	$graph->xaxis->SetTickLabels($legend);
	$graph->legend->Pos(0.05,0.49,"right","center");
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();

}

function grafico_eventos_usuario() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

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
	$mayor = 0;
	$mayor_data =0;
	for ($a=0;$a < sizeof($data); $a++)
	if ($data[$a] > $mayor_data){
   		$mayor = $a;
   		$mayor_data = $data[$a];
	}

	$ajuste_altura = sizeof($data) * 17;
	$graph = new PieGraph(430,170+$ajuste_altura,"auto");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["events_per_user"]);
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->SetShadow();
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$p1 = new PiePlot3D($data);
	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.2);
	$p1->SetCenter(0.3);
	$p1->SetLegends($legend);
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();
}

function grafico_eventos_total() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

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
	$mayor=0; $mayor_data=0;
        for ($a=0;$a < sizeof($data); $a++)
        if ($data[$a] > $mayor_data){
                $mayor = $a;
                $mayor_data = $data[$a];
        }
	
	$graph = new PieGraph(430,200,"auto");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["event_total"]." ( $total )");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->SetShadow();
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$p1 = new PiePlot3D($data);
 	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.4);
	$p1->SetCenter(0.28);
	$p1->SetLegends($legend);
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();
}

function grafico_eventos_grupo() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_pie.php");
	include ("jpgraph/jpgraph_pie3d.php");
	require ("../include/languages/language_".$language_code.".php");

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
        $mayor=0; $mayor_data=0;
        for ($a=0;$a < sizeof($data); $a++)
        if ($data[$a] > $mayor_data){
                $mayor = $a;
                $mayor_data = $data[$a];
        }
	$total_grupos = sizeof($data);
	$ajuste_altura = $total_grupos * 10;
	
	$graph = new PieGraph(430,150+$ajuste_altura,"auto");
	$graph->SetMarginColor('white@0.2');
	$graph->title->Set($lang_label["events_per_group"]);
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->SetShadow();
	$graph->SetAlphaBlending();	
	$graph->SetFrame(true);
	$p1 = new PiePlot3D($data);
	$p1->ExplodeSlice($mayor);
	$p1->SetSize(0.35);
	$p1->SetCenter(0.28);
	$p1->SetLegends($legend);
	$graph->Add($p1);
	$graph->img->SetAntiAliasing();
	$graph->Stroke();
}

function grafico_db_agentes_paquetes() {
	include ("../include/config.php");
	include ("jpgraph/jpgraph.php");
	include ("jpgraph/jpgraph_bar.php");
	require ("../include/languages/language_".$language_code.".php");

	$data = array();
	$legend = array();
	$sql1="SELECT distinct (id_agente) FROM tagente_datos";
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		if (! is_null($row["id_agente"])){
			$sql1="SELECT COUNT(id_agente) FROM tagente_datos WHERE id_agente = ".$row["id_agente"];
			$result3=mysql_query($sql1);
			if ($row3=mysql_fetch_array($result3)){
				$data[]= $row3[0];
				$legend[] = dame_nombre_agente($row[0]);
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
	$mayor = 0;
        $mayor_data =0;
        for ($a=0;$a < sizeof($data); $a++)
                if ($data[$a] > $mayor_data){
                        $mayor = $a;
                        $mayor_data = $data[$a];
                }	

        $ajuste_altura = sizeof($data) * 20;
        $graph = new Graph(400,140+$ajuste_altura,'auto');
        $graph->SetScale("textlin");
        $graph->SetMarginColor('white@0.2');
        $graph->title->Set($lang_label["packets_by_agent"]);
        $graph->title->SetFont(FF_FONT1,FS_BOLD);
        $graph->yaxis->scale->SetGrace(0);
        $graph->yaxis->SetLabelAlign('center','bottom');
        $graph->SetAlphaBlending();
        $graph->SetFrame(true);
        $graph->xaxis->SetLabelMargin(5);
        $graph->Set90AndMargin(100,20,50,30);
        $p1 = new BarPlot($data);
	$p1->value->SetFormat('%.0f ');
        $p1->value->Show();
        $p1->value->SetAlign('left','center');
        $p1->SetFillColor("#0000fd");
        $p1->SetWidth(0.6);
        $p1->SetShadow();
        $graph->yaxis->SetLabelFormat('%d');
        $graph->xaxis->SetTickLabels($legend);
        $graph->legend->Pos(0.05,0.49,"right","center");
        $graph->Add($p1);
        $graph->img->SetAntiAliasing();
        $graph->Stroke();
}

function grafico_db_agentes_purge ($id_agente, $width, $height) {
	include ("../include/config.php");
	require_once 'Image/Graph.php';
	require ("../include/languages/language_".$language_code.".php");

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
		if ($a==3)
			$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp >= ".$fechas[$a];
		else
			$sql1="SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp >= ".$fechas[$a]." AND utimestamp < ".$fechas[$a+1];
		$result=mysql_query($sql1);
		$row=mysql_fetch_array($result);
		$data[] = $row[0];
		$legend[]=$fechas_label[$a]." ( ".$row[0]." )";
	}

	$mayor = 0;
	$mayor_data =0;
	for ($a=0;$a < sizeof($data); $a++)
		if ($data[$a] > $mayor_data){
			$mayor = $a;
			$mayor_data = $data[$a];
		}
	
	if ($total> 1){
		// create the graph
		$Graph =& Image_Graph::factory('graph', array($width, $height));
		// add a TrueType font
		$Font =& $Graph->addNew('font', $config_fontpath);
		// set the font size to 7 pixels
		$Font->setSize(7);
		$Graph->setFont($Font);
		// create the plotarea
		$Graph->add(
			Image_Graph::horizontal(
				$Plotarea = Image_Graph::factory('plotarea'),
				$Legend = Image_Graph::factory('legend'),
			70
			)
		);
		$Legend->setPlotarea($Plotarea);
		// Create the dataset
		// Merge data into a dataset object (sancho)
		$Dataset1 =& Image_Graph::factory('dataset');
		for ($a=0;$a < sizeof($data); $a++){
			$Dataset1->addPoint($legend[$a], $data[$a]);
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
		$Plot->setStartingAngle(145);
		// output the Graph
		$Graph->done();
	} else 
		graphic_error ();
}

function drawWarning($width,$height) {
	include ("../include/config.php");
	require ("../include/languages/language_".$language_code.".php");
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
	ImageTTFText($image, 8, 0, ($width/2)-($width/10), ($height/2)+($height/5), $border, $config_fontpath, $lang_label["no_data"]);
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
		require ("../include/languages/language_".$language_code.".php");
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
				ImageTTFText($image, 8, 0, ($width/4), ($height/2)+($height/5), $back, $config_fontpath,$lang_label["out_of_limits"]);
			else
				ImageTTFText($image, 8, 0, ($width/2)-($width/10), ($height/2)+($height/5), $back, $config_fontpath, $rating."%");
		else
			ImageTTFText($image, 8, 0, ($width/2)-($width/10), ($height/2)+($height/5), $border, $config_fontpath, $rating."%");
		imagePNG($image);
		imagedestroy($image);
   	}
   	Header("Content-type: image/png");
	if ($progress > 100 || $progress < 0){
		// HACK: This report a static image... will increase render in about 200% :-) useful for
		// high number of realtime statusbar images creation (in main all agents view, for example
		$imgPng = imageCreateFromPng("../images/outlimits.png");
		imageAlphaBlending($imgPng, true);
		imageSaveAlpha($imgPng, true);
		imagePng($imgPng); 
   	} else 
   		drawRating($progress,$width,$height);
}

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
		$Font =& $Graph->addNew('font', $config_fontpath);
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
if ( isset($_GET["draw_events"]) and $_GET["draw_events"]==0 )
		$draw_events = 0;
	else
		$draw_events = 1;
	
// Image handler
// *****************


if (isset($_GET["tipo"])){
	if ($_GET["tipo"] == "sparse"){
		grafico_modulo_sparse($id, $period, $draw_events, $width, $height , $label, $unit_name);
	}
	elseif ($_GET["tipo"] =="estado_incidente") 
		grafico_incidente_estados();	
	elseif ($_GET["tipo"] =="prioridad_incidente") 
		grafico_incidente_prioridad();	
	elseif ($_GET["tipo"]=="db_agente_modulo")
		grafico_db_agentes_modulos();
	elseif ($_GET["tipo"]=="db_agente_paquetes")
		grafico_db_agentes_paquetes();
	elseif ($_GET["tipo"] =="db_agente_purge")
		grafico_db_agentes_purge(-1, $width, $height);
	elseif ($_GET["tipo"] =="group_events")
		grafico_eventos_grupo();
	elseif ($_GET["tipo"] =="user_events")
		grafico_eventos_usuario();
	elseif ($_GET["tipo"] =="total_events")
		grafico_eventos_total();
	elseif ($_GET["tipo"] =="group_incident")
		graphic_incident_group();
	elseif ($_GET["tipo"] =="user_incident")
                graphic_incident_user();
	elseif ($_GET["tipo"] =="source_incident")
                graphic_incident_source();
	elseif ($_GET["tipo"] =="user_activity")
                graphic_user_activity();
	elseif ($_GET["tipo"] == "agentaccess")
		graphic_agentaccess($_GET["id"], $_GET["periodo"], $width, $height);
	elseif ($_GET["tipo"] == "agentmodules")
		graphic_agentmodules($_GET["id"], $width, $height);
	elseif ($_GET["tipo"] == "gdirect")
		graphic_test ($id, $period, $intervalo, $label, $width, $height);
	elseif ( $_GET["tipo"] =="progress"){
		$percent= $_GET["percent"];
		progress_bar($percent,$width,$height);
	}
	elseif ( $_GET["tipo"] =="combined"){
		// Split id to get all parameters
		$module_list = array();
		$module_list = split ( ",", $id);
		$weight_list = array();
		$weight_list = split ( ",", $weight_l);
		graphic_combined_module ($module_list, $weight_list, $period, $width, $height , $label, $unit_name );
	}
	else
		graphic_error ();
} else
	graphic_error ();
?>
