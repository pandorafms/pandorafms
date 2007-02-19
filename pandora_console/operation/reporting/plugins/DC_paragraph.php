<?php


function &DC_paragraph_calculate ( &$params ) {

	return $params['text'];

}

function DC_paragraph_write_guihtml ( &$DC_params, $identifier, $handler ) {

	$identifier .= "DC_";
	$DC_type = 'DC_paragraph';

	fwrite ( $handler, "   <input type='hidden' name='". $identifier . "type' value='". $DC_type ."'> <BR>\n" );

	fwrite ( $handler, "

	<table>

        <tr><td class='left'>
        text :
        </td><td class='right'>
        <textarea name='" . $identifier . "text'>" . htmlentities($DC_params['text']) . "</textarea>
        </td></tr>

	</table>" );
                                                                                            
}                                                                                           
                                                                                            
function DC_paragraph_params () {

	return array (
			'text'		
			);

}


?>
