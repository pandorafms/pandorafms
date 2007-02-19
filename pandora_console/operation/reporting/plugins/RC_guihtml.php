<?php

function RC_guihtml_write_all ( &$TC, $data, $handler ) {

	global $report_plugins, $report_plugins_functions;

	$DC_params 	= $data;
	$RC_params 	= $TC;
	$DC_type = $DC_params['DC_type'];
	$RC_type = $RC_params['RC_type'];
	$RC_params['DC_type'] = $DC_type;
	$identifier = $RC_params['RC_report_id'];
	// $identifier is like  some-text . digits . '_'
	// now, we want to extract the digits into $counter
	preg_match ('/\d+/', $identifier, $match);
	$counter = $match[0];
	
	if ( in_array( $DC_type, $report_plugins) ) {
		$func = $DC_type . "_params";
		$DC_gui_params = $func ();
	}

	if ( in_array( $RC_type, $report_plugins) ) {
		$func = $RC_type . "_params";
		$RC_gui_params = $func ();
	}

	// header
	
	fwrite ( $handler, "<h3>$counter - $RC_type </h3>" );
	fwrite ( $handler, "<div class='replev3'>" );
	

	// RC parameters
	
	fwrite ( $handler, "<h4>RC parameters</h4>\n" );
	fwrite ( $handler, "<div class='replev4'>\n" );
	if ( in_array( $RC_type, $report_plugins) ) {
		$func = $RC_type . '_write_guihtml';
		$func ( $RC_params, $identifier, $handler );
	} else { 
		fwrite ( $handler, "    No plugin found<br>\n" ); 
	}
	fwrite ( $handler, "   </div>\n" );

	// DC parameters
	
	fwrite ( $handler, "   <h4>DC <i>$DC_type</i> parameters</h4>\n" );
	fwrite ( $handler, "   <div class='replev4'>\n" );
	if ( in_array( $DC_type, $report_plugins) ) {
		$func = $DC_type . '_write_guihtml';
		$func ( $DC_params, $identifier, $handler );		
	} else { fwrite ( $handler, "    No plugin found<br>\n" ); }
	fwrite ( $handler, "   </div>\n" );



	// footer
	fwrite ( $handler, "</div>\n" );

}

function RC_guihtml_write_defaultvalues ( &$C_params, $data, $handler ) {

	global $report_plugins, $report_plugins_functions;

	$identifier = $data['identifier'];
	$C_type = $data['C_type'];
	

	// header
	
	fwrite ( $handler, "<h3> defaults for: $C_type </h3>\n" );
	fwrite ( $handler, "<div class='replev3'>\n" );
	

	// ?C parameters
	
	fwrite ( $handler, "   <h4>parameters</h4>\n" );
	fwrite ( $handler, "   <div class='replev4'>\n" );
	if ( in_array( $C_type, $report_plugins) ) {
		$func = $C_type . '_write_guihtml';
		$func ( $C_params, $identifier, $handler );
	} else { 
		fwrite ( $handler, "    No plugin found<br>\n" ); 
	}
	fwrite ( $handler, "   </div>\n" );


	// footer
	fwrite ( $handler, "</div>\n" );

}

function RC_guihtml_write_guihtml () {
}

function RC_guihtml_params () {
	return;
}



?>
