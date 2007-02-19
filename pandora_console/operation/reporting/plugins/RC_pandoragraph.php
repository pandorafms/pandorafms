<?php
 
function RC_pandoragraph_creating_PNG ( &$RC_params, $data, $handler ) {

	// opening a new handler for the image
	
	$newfile = $RC_params['RC_tmpfolder'] . '/' . $RC_params['RC_report_id'] . 'graph.png';

	modulo_grafico_draw ( 	$data['Graph_param'],
				$data['intervals'],		
				$data['MGD_data_label'],	
				$data['MGD_data_type'],		
				$data['MGD_data_color'],	
				$data['MGD_data'],		
				$data['MGD_xo'],		
				$data['MGD_xf'],
				$newfile		
	);
}


function RC_pandoragraph_write_latex ( &$RC_params, $data, $handler ) {

	// creating image
	RC_pandoragraph_creating_PNG ( $RC_params, $data, $handler );

	// writing latex code
	fwrite ( $handler, "\\begin{center}\n");
	fwrite ( $handler, '\includegraphics[width=0.8\textwidth]{' . $RC_params['RC_report_id'] . 'graph.png}'. "\n" );
	fwrite ( $handler, "\\end{center}\n\n" );
}


function RC_pandoragraph_write_html ( &$RC_params, $data, $handler ) {

	// creating image
	RC_pandoragraph_creating_PNG ( $RC_params, $data, $handler );

	// writing html code
	fwrite ( $handler, "<center>" );
	fwrite ( $handler, '<img src="' . $RC_params['RC_report_id'] . 'graph.png" >' );
	fwrite ( $handler, "</center>" );
	
}

function RC_pandoragraph_write_guihtml ( &$RC_params, $identifier, $handler ) {

	$RC_type = 'RC_pandoragraph';

	fwrite ( $handler, "   <input type='hidden' name='". $identifier . "RC_type' value='". $RC_type ."'> <BR>\n" );
	

}


function RC_pandoragraph_params () {

	return array (
			'DC_type'	
			);

}


?>