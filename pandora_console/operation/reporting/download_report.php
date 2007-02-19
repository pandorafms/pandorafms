<?php

// downloads a report and deletes it

// todo:  buf! this function is very dangerous

$bad_characters = array ('/', '\\', '"', '\'');
$filename = 'reports/' . str_replace ( $bad_characters, '', $_GET['filename']);
$type = 'zip';

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

?>