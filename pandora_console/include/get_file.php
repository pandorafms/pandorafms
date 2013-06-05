<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

require_once('functions.php');
require_once('functions_filemanager.php');

session_start();

require_once ("config.php");
global $config;

session_write_close ();

check_login ();

$styleError = "background:url(\"../images/err.png\") no-repeat scroll 0 0 transparent; padding:4px 1px 6px 30px; color:#CC0000;";

$file = get_parameter('file', null);

$file = base64_decode($file);

$chunks = explode('/', $file); 
$nameFile = end($chunks);

$hash = get_parameter('hash', null);

$testHash = md5($file . $config['dbpass']);

if ($hash != $testHash) {
	echo "<h3 style='" . $styleError . "'>" .
		__('Security error. Please contact the administrator.') .
		"</h3>";
}
else if (!empty($file) && !empty($hash)) {
	$file = $_SERVER['DOCUMENT_ROOT'] . $file;
	
	if (!file_exists($file)) {
		echo "<h3 style='" . $styleError . "'>" .
			__("File is missing in disk storage. Please contact the administrator.") .
			"</h3>";
	}
	else {
		header('Content-type: aplication/octet-stream;');
		header('Content-type: ' . mime_content_type($file) . ';');
		header("Content-Length: " . filesize($file));
		header('Content-Disposition: attachment; filename="' . $nameFile . '"');
		readfile($file);
	}
}
?>