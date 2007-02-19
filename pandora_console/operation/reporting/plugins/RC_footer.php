<?php


function RC_footer_write_latex ( &$RC_params, $data, $handler ) {

	fwrite ( $handler, '\end{document}');

}

function RC_footer_write_html ( &$RC_params, $data, $handler ) {

	fwrite ( $handler, "</body></html>");

}

function RC_footer_write_guihtml ( &$RC_params, $identifier, $handler ) {

	$RC_type = 'RC_footer';

	fwrite ( $handler, "<input type='hidden' name='". $identifier . "RC_type' value='". $RC_type ."'> <BR>\n" );


} 


function RC_footer_params () {

	return array (
			'DC_type'	
			);
}


?>