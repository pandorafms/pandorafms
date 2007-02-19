<?php


function RC_paragraph_write_latex ( &$RC_params, $data, $handler ) {

	fwrite ( $handler, esc_LaTeX ($data) . "\n\n" );
	
}

function RC_paragraph_write_html ( &$RC_params, $data, $handler ) {

	fwrite ( $handler, "<p>".htmlentities($data)."</p>\n" );
	
}

function RC_paragraph_write_guihtml ( &$RC_params, $identifier, $handler ) {

	$RC_type = 'RC_paragraph';

	fwrite ( $handler, "   <input type='hidden' name='". $identifier . "RC_type' value='". $RC_type ."'> <BR>\n" );
	

}


function RC_paragraph_params () {

	return array (
			'DC_type'	
			);

}


?>
