<?php 

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
global $config;

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation", "Trying to access File manager");
	require ("general/noaccess.php");
	return;
}

require_once ("include/functions_filemanager.php");

//$delete_file = (bool) get_parameter ('delete_file');
//$upload_file = (bool) get_parameter ('upload_file');
//$create_dir = (bool) get_parameter ('create_dir');

// Header
print_page_header (__('File manager'), "", false, "", true);

if (isset($config['filemanager']['message'])) {
	echo $config['filemanager']['message'];
	$config['filemanager']['message'] = null;
}

//// Upload file
//if ($upload_file) {
//	if (isset ($_FILES['file']) && $_FILES['file']['name'] != "") {
//		$filename = $_FILES['file']['name'];
//		$filesize = $_FILES['file']['size'];
//		$directory = (string) get_parameter ('directory');
//		
//		// Copy file to directory and change name
//		$nombre_archivo = $config['homedir'].'/'.$directory.'/'.$filename;
//		if (! @copy ($_FILES['file']['tmp_name'], $nombre_archivo )) {
//			echo "<h3 class=error>".__('attach_error')."</h3>";
//		} else {
//			// Delete temporal file
//			unlink ($_FILES['file']['tmp_name']);
//		}
//		
//	}
//}

//if ($delete_file) {
//	$filename = (string) get_parameter ('filename');
//	echo "<h3>".__('Deleting')." ".$filename."</h3>";
//	if (is_dir ($filename)) {		
//		rmdir ($filename);
//	} else {
//		unlink ($filename);
//	}
//}


$directory = (string) get_parameter ('directory', "/");

//// CREATE DIR
//if ($create_dir) {
//	$dirname = (string) get_parameter ('dirname');
//	if ($dirname) {
//		@mkdir ($directory.'/'.$dirname);
//		echo '<h3>'.__('Created directory %s', $dirname).'</h3>';
//	}
//}

// A miminal security check to avoid directory traversal
if (preg_match ("/\.\./", $directory))
	$directory = "images";
if (preg_match ("/^\//", $directory))
	$directory = "images";
if (preg_match ("/^manager/", $directory))
	$directory = "images";

/* Add custom directories here */
$fallback_directory = "images";

$banned_directories['include'] = true;
$banned_directories['godmode'] = true;
$banned_directories['operation'] = true;
$banned_directories['reporting'] = true;
$banned_directories['general'] = true;
$banned_directories[ENTERPRISE_DIR] = true;

if (isset ($banned_directories[$directory]))
	$directory = $fallback_directory;

// Current directory
$available_directories[$directory] = $directory;

$real_directory = realpath ($config['homedir'].'/'.$directory);

//box_upload_file_explorer($real_directory, $directory);


echo '<h3>'.__('Index of %s', $directory).'</h3>';

file_explorer($real_directory, $directory, 'index.php?sec=gsetup&sec2=godmode/setup/file_manager');
?>
