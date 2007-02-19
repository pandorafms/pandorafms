<?php


//  data is in RC_list format

//   all columns have the same number of values (they can be "")


function RC_listpie_creating_PNG ( &$RC_params, $data, $handler ) {

// 	include 'Image/Graph.php';
	global $config_fontpath;
		
	// create the graph
	$Graph =& Image_Graph::factory('graph', array(400, 300));
	
	// add a TrueType font
	$Font =& $Graph->addNew('font', $config_fontpath);

	// set the font size to 7 pixels
	$Font->setSize(7);
	
	$Graph->setFont($Font);
	
	// create the plotarea
	$Graph->add(
	Image_Graph::vertical(
		Image_Graph::factory('title', array( $RC_params['title'], 12)),
		Image_Graph::horizontal(
		$Plotarea = Image_Graph::factory('plotarea'),
		$Legend = Image_Graph::factory('legend'),
		70
		),
		5            
	)
	);
	
	$Legend->setPlotarea($Plotarea);
		
	// create the 1st dataset
	$Dataset1 =& Image_Graph::factory('dataset');
	
	for ($cc=0; $cc<count($data[1]); $cc++) {
	
		$Dataset1->addPoint( $data[1][$cc], $data[2][$cc] );
	
	}
	
	// create the 1st plot as smoothed area chart using the 1st dataset
	$Plot =& $Plotarea->addNew('pie', array(&$Dataset1));
	$Plotarea->hideAxis();
	
	// create a Y data value marker
	$Marker =& $Plot->addNew('Image_Graph_Marker_Value', IMAGE_GRAPH_PCT_Y_TOTAL);
	// create a pin-point marker type
	$PointingMarker =& $Plot->addNew('Image_Graph_Marker_Pointing_Angular', array(20, &$Marker));
	// and use the marker on the 1st plot
	$Plot->setMarker($PointingMarker);    
	// format value marker labels as percentage values
	$Marker->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Formatted', '%0.1f%%'));
	
	$Plot->Radius = 2;
	
	$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');
	$Plot->setFillStyle($FillArray);
	$FillArray->addNew('gradient', array(IMAGE_GRAPH_GRAD_RADIAL, 'white', 'green'));
	$FillArray->addNew('gradient', array(IMAGE_GRAPH_GRAD_RADIAL, 'white', 'blue'));
	$FillArray->addNew('gradient', array(IMAGE_GRAPH_GRAD_RADIAL, 'white', 'yellow'));
	$FillArray->addNew('gradient', array(IMAGE_GRAPH_GRAD_RADIAL, 'white', 'red'));
	$FillArray->addNew('gradient', array(IMAGE_GRAPH_GRAD_RADIAL, 'white', 'orange'));
	
	$Plot->explode(5);
	
	$Plot->setStartingAngle(90);
	
	
	// output the Graph
	$newfile = $RC_params['RC_tmpfolder'] . '/' . $RC_params['RC_report_id'] . 'graph.png';
	$Graph->done( array('filename' => $newfile) );

}


function RC_listpie_write_latex ( &$RC_params, $data, $handler ) {

	// creating image
	RC_listpie_creating_PNG ( $RC_params, $data, $handler );
	
	// writing latex
	fwrite ( $handler, "\\begin{center}\n");
	fwrite ( $handler, '\includegraphics[width=0.8\textwidth]{' . $RC_params['RC_report_id'] . 'graph.png}'. "\n" );
	fwrite ( $handler, "\\end{center}\n\n" );
}


function RC_listpie_write_html ( &$RC_params, $data, $handler ) {

	// creating image
	RC_listpie_creating_PNG ( $RC_params, $data, $handler );
	
	// writing html
	fwrite ( $handler, "<center>" );
	fwrite ( $handler, '<img src="' . $RC_params['RC_report_id'] . 'graph.png" ><br>' );
	fwrite ( $handler, "</center>" );
}


function RC_listpie_write_guihtml ( &$RC_params, $identifier, $handler ) {

	$RC_type = 'RC_listpie';

	fwrite ( $handler, "   <input type='hidden' name='". $identifier . "RC_type' value='". $RC_type ."'> <BR>\n" );
	
	fwrite ( $handler, "

	<table>

        <tr><td class='left'>
        title : 
        </td><td class='right'>
        <input name='" . $identifier . "title' type='text' value='" . $RC_params['title'] . "'>
        </td></tr>

	</table>" );
}


function RC_listpie_params () {

	return array (
			'DC_type'	,
			'title'			// title of the list

			);

}




?>