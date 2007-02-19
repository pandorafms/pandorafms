<?php

// downloads a xml file


	// it offers the file for download, and delete it after
	// code taken from babel project (i love open source)
	// $type will be 'zip', 'pdf', ...

	if (!$xmlfile = $_POST['xml_file']) { echo "where is the xml file?"; return; } 
	$xmlfile = stripslashes($xmlfile);
	$filesize = strlen($xmlfile);

//iconv_set_encoding('output_encoding', "UTF-8");


	// Send headers
	header("Content-type: application/report_template");  // if I write xml, it won't download
	header("Content-Length: $filesize");
	header("Content-Disposition: inline; filename=report_template.xml");

	// Output file
	echo $xmlfile;


?>