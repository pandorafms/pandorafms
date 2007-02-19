<?php


function RC_header_write_latex ( &$RC_params, $data, $handler ) {

	fwrite ( $handler, '
\documentclass[a4paper,10pt]{article}
\usepackage[pdftex]{color,graphicx}
\usepackage{longtable}
\usepackage[' . $RC_params['language'] . ']{babel}
\usepackage[latin1]{inputenc}

\title{ '. esc_LaTeX($RC_params['title']) .' }
\author{'. esc_LaTeX($RC_params['author']) .'}

\begin{document}

\maketitle

');

}


function RC_header_write_html ( &$RC_params, $data, $handler ) {

	fwrite ( $handler, "<html><head><title>" . htmlentities($RC_params['title']) . "</title></head><body>" );
	fwrite ( $handler, "<br><hr><br>" );
	fwrite ( $handler, "<b>Title : </b>" . htmlentities($RC_params['title']) . "<br>" ); 
	fwrite ( $handler, "<b>Author : </b>" . htmlentities($RC_params['author']) . "<br>" );
	fwrite ( $handler, "<b>Version : </b>" . htmlentities($RC_params['version']) . "<br>" );
	fwrite ( $handler, "<br><hr><br><br>" );

}


function RC_header_write_guihtml ( &$RC_params, $identifier, $handler ) {

	$RC_type = 'RC_header';

	fwrite ( $handler, "<input type='hidden' name='". $identifier . "RC_type' value='". $RC_type ."'> <BR>\n" );

	// language support (LaTeX with babel package only)
	$babel_languages = array ('american', 'french', 'german', 'ngerman', 'bahasa', 'basque', 'catalan', 'croatian', 'czech', 'danish', 'dutch', 'finnish', 'greek', 'icelandic', 'irish', 'italian', 'latin', 'magyar', 'norsk', 'norsk', 'portuges', 'romanian', 'russian', 'slovak', 'slovene', 'spanish', 'swedish', 'turkish', 'ukrainian');


	fwrite ( $handler, "
	
	<table>

        <tr><td class='left'>
        author :
        </td><td class='right'>
        <input name='" . $identifier . "author' type='text' value='" . $RC_params['author'] . "'>
        </td></tr>

        <tr><td class='left'>
        title :
        </td><td class='right'>
        <input name='" . $identifier . "title' type='text' value='" . $RC_params['title'] . "'>
        </td></tr>

        <tr><td class='left'>
        version :
        </td><td class='right'>
        <input name='" . $identifier . "version' type='text' value='" . $RC_params['version'] . "'>
        </td></tr>

        <tr><td class='left'>
        Language (LaTeX only) :
        </td><td class='right'>
        <select name='". $identifier ."language' value='".$RC_params['language']."'>
			<option value=''>");
	foreach ($babel_languages as $language) {
		fwrite ($handler, "<option value = '" . $language . "' ". 
			(($RC_params['language']==$language)?'SELECTED':'')  ." > " . $language);
	}
	fwrite ( $handler, "</select>
	</td></tr>
	</table>
	" );			
} 


function RC_header_params () {

	return array (
			'DC_type'	,
			'author'	,
			'title'		,
			'version'	,
			'language'
			);

}


?>