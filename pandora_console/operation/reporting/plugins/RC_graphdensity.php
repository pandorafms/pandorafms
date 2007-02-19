<?php
 
//  data is in RC_list format

//   all columns have the same number of values (they can be "")
 
 
//	this component receives a list (first column being timestamp)
//	and represents the density of ocurrences


function RC_graphdensity_creating_PNG ( &$RC_params, $data, $handle ) {

	global $config_fontpath;
	
	
	// translating timestamp from mysql string till epoch integer
	 	
	$timestamp = array_map ( 'mysql_time', $data[1] );
	// note that any other data columns are not used in density graphs
	
	// graph fixed to 50 points. Caculating abcise points
	if (!$RC_params['period']) {
		$points = 50;   // any value >0
		$period = ($timestamp[ count($timestamp)-1 ] - $timestamp[0]) / $points;
	} else {
		$period = $RC_params['period'];
	}
	$intervals = gms_get_intervals ( $timestamp[0],
						$timestamp[ count($timestamp)-1 ],
						$period );
	
	// next two steps required for adjusting intervals for a bar graph
 	$intervals = array_map ( create_function ('$a', 'return intval( $a + ' . $period/2 .');' ), $intervals );
	
	
	// calculating ocurrence density
	$counter = array_fill (0, count($intervals), 0 );
	for ($cc=0; $cc<count($timestamp); $cc++) {
	
		// calculating to which bar of the final graphic
		// this point will be added
		$bar = intval( ( $timestamp[$cc] - ($intervals[0] - $period/2) ) / $period ); 
		$counter[$bar] += 1;
	}
	

	// let's graph this
	
	$Graph =& Image_Graph::factory('graph', array( 	500, 220  ) ); 
	$Font =& $Graph->addNew('font', $config_fontpath);
	$Font->setSize( 8 );
	$Graph->setFont($Font);
	$Graph->add(
		Image_Graph::vertical(
		 	$Title = Image_Graph::factory('title', array ('Occurrences every ' . intval($period) . ' s ' , 10 )),
			Image_Graph::horizontal(
				$Plotarea = Image_Graph::factory('plotarea','axis'),
				$Legend = Image_Graph::factory('legend'),
				80
				),
			5
			)
		);

	$Title->setAlignment(IMAGE_GRAPH_ALIGN_LEFT);
	
 	$Grid =& $Plotarea->addNew('line_grid', false, IMAGE_GRAPH_AXIS_X);
	$Grid->setBackgroundColor('silver@0.3');
	$Grid->setBorderColor('black');
	$Plotarea->addNew('line_grid', false, IMAGE_GRAPH_AXIS_Y); 
	// the next grid is only necessary for drawing the right black line of the PlotArea
	$Grid_sec =& $Plotarea->addNew('line_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY); 
	$Grid_sec->setBorderColor('black');
	
	$Legend->setPlotarea($Plotarea);
	
	$Datasets[0] = Image_Graph::factory('dataset');
	$Datasets[0]->setName( 'occurrences' );
	
	for ($dd=0; $dd<count($intervals); $dd++ ) {
		//echo $intervals[$dd] . " - " . $counter[$dd] . "<br>\n";
		$Datasets[0]->addPoint( $intervals[$dd] , 
					$counter[$dd] );
	}
	
	
	$Plot =& $Plotarea->addNew('Image_Graph_Plot_Bar', array($Datasets[0]));
	$bar_width_per = ( $intervals[1] - $intervals[0] ) / 
			( $data[0]['tfinal'] - $data[0]['torigin'] ) * 100;
	$Plot->setBarWidth($bar_width_per, '%');
	
	$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
	//$AxisX->setLabelInterval($MGD_param['periodo']*60/10);
	if ($data[0]['torigin']) { $AxisX->forceMinimum($data[0]['torigin']); }
	if ($data[0]['tfinal'])  { $AxisX->forceMaximum($data[0]['tfinal']);  }
 	$AxisX->setFontAngle(45);
	$AxisX->setLabelOption('offset', 35); 
 	$AxisX->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', 'dame_fecha_grafico_timestamp')); 
	$AxisY =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
	
	

	// and writing results
	
	$newfile = $RC_params['RC_tmpfolder'] . '/' . $RC_params['RC_report_id'] . 'graph.png';
	$Graph->done(array('filename' => $newfile));

}


function RC_graphdensity_write_latex ( &$RC_params, $data, $handler ) {

	// creating image
	RC_graphdensity_creating_PNG ( $RC_params, $data, $handle );

	// writing latex code
	fwrite ( $handler, "\\begin{center}\n");
	fwrite ( $handler, '\includegraphics[width=0.8\textwidth]{' . $RC_params['RC_report_id'] . 'graph.png}'. "\n" );
	fwrite ( $handler, "\\end{center}\n\n" );
}


function RC_graphdensity_write_html ( &$RC_params, $data, $handler ) {

	// creating image
	RC_graphdensity_creating_PNG ( $RC_params, $data, $handle );

	// writing html code
	fwrite ( $handler, "<center>" );
	fwrite ( $handler, '<img src="' . $RC_params['RC_report_id'] . 'graph.png" >' );
	fwrite ( $handler, "</center>" );
}


function RC_graphdensity_write_guihtml ( &$RC_params, $identifier, $handler ) {

	$RC_type = 'RC_graphdensity';

	fwrite ( $handler, "   <input type='hidden' name='". $identifier . "RC_type' value='". $RC_type ."'> <BR>\n" );

	fwrite ( $handler, "

	<table>

        <tr><td class='left'>
        Period (s) :
        </td><td class='right'>
        <input type='text' name='" . $identifier . "period' value='" . $RC_params['period'] . "'>
        </td></tr>

	</table>" );
}


function RC_graphdensity_params () {

	return array (
			'DC_type'	,
			'period'	// graph period, in seconds. If ommited, graph will have 50 bars
			);

}


?>