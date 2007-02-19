<?php

//  data is an array as follows:
//	data[0] =	list parameters 
// 		  array (	'titles' => ( title column 1, title column 2, ... ),
// 				param1   => value, ...
				
//	data[1] = array ( 1st value column 1, 2nd value column 1, ... )
//	data[2] = array ( 1st value column 2, ... )

//   all columns have the same number of values (they can be "")


function RC_list_write_latex ( &$RC_params, $data, $handler ) {

	if (!is_array($data[0])) { return; }
	
	// escaping special characters in data
	for ($cc=0; $cc<count($data); $cc++) {
		$data[$cc] = array_map ( 'esc_LaTeX', $data[$cc]);
	}
	
	// some initialitations
	$ar_titles = $data[0]['titles'];
	$n_columns = count($ar_titles);
	
	
	// calculating column relative widths
	if ( 	$widths = explode(':', $RC_params['widths']) and 
		$widths_sum = array_sum ( explode(':', $RC_params['widths']) ) ) {
		$widths = array_map ( 
				create_function ( '$a', 'return $a/' . $widths_sum . ';' ), 
				$widths );
	} else {
		// equal spaced columns
		$widths = array_fill(0, $n_columns, 1/$n_columns);
	}
	
	for ($cc=0; $cc<$n_columns; $cc++) {
		$longtable_format .= '|p{' . $widths[$cc] . '\textwidth}';
	}
	$longtable_format .= '|';

	// titles
	$titles = '\hline';
	for ($cc=0; $cc<$n_columns; $cc++) {
		$glue = ($cc)?' & ':'';
		$titles .= $glue . '\multicolumn{1}{|c|}{\textbf{'. $ar_titles[$cc] .'}}';
	}
	$titles .= '\\\\ \hline ';
	
	// begining header
	fwrite ($handler, '
\begin{center}
\begin{longtable}{'.$longtable_format.'} 
\caption['.esc_LaTeX($RC_params['title']).']{'.esc_LaTeX($RC_params['title']).'} \\\\
'.$titles.'
\endfirsthead

'.$titles.'
\endhead

\hline \multicolumn{'.$n_columns.'}{|r|}{{Continued on next page}} \\\\ \hline
\endfoot

\hline \hline
\endlastfoot
');
	
	// writing values
	// TODO: adjust column sizes !!
	
	for ($rr=0; $rr<count($data[1]); $rr++) {
		for ($cc=0; $cc<$n_columns; $cc++) {
			$glue = ($cc)?' & ':'';
			fwrite ($handler,  $glue . $data[$cc+1][$rr] );
		}
		fwrite ($handler, ' \\\\ ' . "\n");
	}
	
	fwrite ($handler, '
\end{longtable}
\end{center}  

');
}





function RC_list_write_html ( &$RC_params, $data, $handler ) {

	fwrite ($handler, $RC_params['title'] . "<br><br>");

	if (!is_array($data[0])) { return; }
	$ar_titles = $data[0]['titles'];
	$n_columns = count($ar_titles);
	
	// calculating column relative widths
	if ( 	$widths = explode(':', $RC_params['widths']) and 
		$widths_sum = array_sum ( explode(':', $RC_params['widths']) ) ) {
		$widths = array_map ( 
				create_function ( '$a', 'return $a/' . $widths_sum . ';' ), 
				$widths );
	} else {
		// equal spaced columns
		$widths = array_fill(0, $n_columns, 1/$n_columns);
	}
	
	// header of table
	fwrite ($handler, "<table>\n<TR>\n");
	
	// writing titles
	// TODO: adjust column sizes !!
	
	for ($cc=0; $cc<$n_columns; $cc++) {
		fwrite ($handler, "<TD width='". ($widths[$cc]*100) ."%'>" . htmlentities($ar_titles[$cc]) . "</TD>");
	}
	fwrite ($handler, "\n</TR>\n");
	
	// writing values
	// TODO: adjust column sizes !!
	
	for ($rr=0; $rr<count($data[1]); $rr++) {
		fwrite ($handler, "<TR>\n");
		for ($cc=0; $cc<$n_columns; $cc++) {
			fwrite ($handler, "<TD width='". ($widths[$cc]*100) ."%'>" . htmlentities($data[$cc+1][$rr]) . "</TD>");
		}
		fwrite ($handler, "\n</TR>\n");
	}
	
	fwrite ($handler, "</table>");
}


function RC_list_write_guihtml ( &$RC_params, $identifier, $handler ) {

	$RC_type = 'RC_list';

	fwrite ( $handler, "   <input type='hidden' name='". $identifier . "RC_type' value='". $RC_type ."'> <BR>\n" );
	
	fwrite ( $handler, "

	<table>

        <tr><td class='left'>
        title :
        </td><td class='right'>
        <input name='" . $identifier . "title' type='text' value='" . $RC_params['title'] . "'>
        </td></tr>

        <tr><td class='left'>
        widths (w1:w2:...) :
        </td><td class='right'>
        <input name='" . $identifier . "widths' type='text' value='" . $RC_params['widths'] . "'>
        </td></tr>

	</table>" );


}


function RC_list_params () {

	return array (
			'DC_type'	,
			'title'		,	// title of the list
			'widths'		// relative column widths.
						// values separated with ':'

			);

}


?>
