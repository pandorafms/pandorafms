<?php


function RC_section_write_latex ( &$RC_params, $data, $handler ) {

	$ar_level = array (	'section' 	=> '1',
				'subsection'	=> '2',
				'subsubsection'	=> '3'
		 );

	if (!array_key_exists( $RC_params['level'], $ar_level )) { return; }

	fwrite ( $handler, "\\".$RC_params['level']."{". esc_LaTeX($RC_params['title']) ."}\n\n" );
}


function RC_section_write_html ( &$RC_params, $data, $handler ) {

	$ar_level = array (	'section' 	=> '1',
				'subsection'	=> '2',
				'subsubsection'	=> '3'
		 );	
	$level = $ar_level[$RC_params['level']];

	fwrite ( $handler, "<H".$level.">".htmlentities($RC_params['title'])."</H".$level.">\n\n");
}


function RC_section_write_guihtml ( &$RC_params, $identifier, $handler ) {

	$RC_type = 'RC_section';

	fwrite ( $handler, "<input type='hidden' name='". $identifier . "RC_type' value='". $RC_type ."'> <BR>\n" );

	fwrite ( $handler, "
	
	<table>

        <tr><td class='left'>
        Title :
        </td><td class='right'>
        <input name='" . $identifier . "title' type='text' value='" . $RC_params['title'] . "'>
        </td></tr>

        <tr><td class='left'>
        Level :
        </td><td class='right'>
        <select name='". $identifier ."level' value='".$RC_params['level']."'>
			<option value=''>
			<option value='section' ". (($RC_params['level']=='section')?'SELECTED':'') ." > section
			<option value='subsection' ". (($RC_params['level']=='subsection')?'SELECTED':'') ." > subsection
			<option value='subsubsection' ". (($RC_params['level']=='subsubsection')?'SELECTED':'') ." > subsubsection
	</select>
	</td></tr>
	</table>
	" );			
} 


function RC_section_params () {

	return array (
			'DC_type'	,
			'title'		,	// title of the new section
			'level'			// level:  1, 2 (1.x) and 3 (1.x.y)
			);
}


?>