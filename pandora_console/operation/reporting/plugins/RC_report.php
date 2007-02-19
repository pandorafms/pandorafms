<?php


function RC_report_postprocess_zip ( $tmpfolder ) {
	// compresses the contents in $tmpfolder in a zip file named
	// $tmpfolder.zip, and deletes the original files
	// Returns the file name of the zip created

	// compressing files (if any)
	
	if (!$tmpfolder) { return; }
	
	// next command zips tmpfolder in a tmpfolder.zip zip file
	// careful! -j option junks paths
	system( 'zip -qrmj ' . $tmpfolder . ' ' . $tmpfolder, $retval );
	if ($retval) { print "I cannot find or execute zip"; return; }
	
	return $tmpfolder . '.zip';
}


function RC_report_postprocess_download ( $type, $filename ) {
	// it offers the file for download, and delete it after
	// code taken from babel project (i love open source)
	// $type will be 'zip', 'pdf', ...
	$filesize = filesize($filename);
	$handle = fopen($filename, "r");
	$contents = fread($handle, $filesize);
	fclose($handle);

	// Send headers
	header("Content-type: application/" . $type);
	header("Content-Length: $filesize");
	header("Content-Disposition: inline; filename=report." . $type);

	// Read file
	$handle = fopen($filename, "r");
	$contents = fread($handle, filesize($filename));
	echo $contents;
	fclose($handle);

	// Delete file
	unlink ($filename);
}


function RC_report_write_guihtml ( &$RC_params, $identifier, $handler ) {

	global $reports_ext;
	$RC_type = 'RC_report';

	fwrite ( $handler, "   <input type='hidden' name='". $identifier . "RC_type' value='". $RC_type ."'> <BR>\n" );

	fwrite ( $handler, "

	<table>

        <tr><td class='left'>
        output format :
        </td><td class='right'>
        <select name='". $identifier ."output_format' value='".$RC_params['output_format']."'>
			<option value=''>\n");
	foreach ($reports_ext as $format => $extension) {
		fwrite ($handler, "<option value = '" . $format . "' ". 
			(($RC_params['output_format']==$format)?'SELECTED':'') ." > " . $format);
	}		
			
	fwrite ( $handler, "
	</select>		
        </td></tr>

	</table>" );

}

function RC_report_write_html ( &$RC_params ) {

	// compress the web document and offers it for download
	
	$filename = RC_report_postprocess_zip ( $RC_params['tmpfolder'] );

	if ($chunk = strrchr ($filename, '/'))  { $filename = $chunk; }
	if ($chunk = strrchr ($filename, '\\')) { $filename = $chunk; }

	if ($filename) { 
		// RC_report_postprocess_download ('zip', $filename); 
		echo ( " <br> <br> ");
		echo ( "Done");
		echo ( " <br> <br> ");
		echo ( " <a href='operation/reporting/download_report.php?filename=" . 
			$filename . ">Download report</a> <br> ");
	}
}

function RC_report_write_latex ( &$RC_params ) {

	// compiling latex document
	if (!$RC_params['tmpfolder'] or !$RC_params['filename']) { return; }
	
	$actual_path = getcwd();
	chdir($RC_params['tmpfolder']);
	exec ( 'pdflatex --interaction batchmode ' . $RC_params['filename'] , $ar_message, $retval );
	exec ( 'pdflatex --interaction batchmode ' . $RC_params['filename'] , $ar_message, $retval );
	chdir($actual_path);
	if ($retval) { print "Errors compiling latex file"; return; }
	
	// zipping
	$filename = RC_report_postprocess_zip ( $RC_params['tmpfolder'] );

	// downloading
	if ($chunk = strrchr ($filename, '/'))  { $filename = $chunk; }
	if ($chunk = strrchr ($filename, '\\')) { $filename = $chunk; }
	
	if ($filename) { 
		// RC_report_postprocess_download ('zip', $filename); 
		echo ( " <br> <br> ");
		echo ( "Done");
		echo ( " <br> <br> ");
		echo ( " <a href='operation/reporting/download_report.php?filename=" . 
			$filename . ">Download report</a> <br> ");
	}
}


function RC_report_params () {

	return array (
			'output_format'			
			);

}


?>