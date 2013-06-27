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

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation", "Trying to access File manager");
	require ("general/noaccess.php");
	return;
}

require_once ("include/functions_filemanager.php");

// Header
ui_print_page_header (__('File manager'), "", false, "", true);

if (isset($config['filemanager']['message'])) {
	echo $config['filemanager']['message'];
	$config['filemanager']['message'] = null;
}

$directory = (string) get_parameter ('directory', "/");

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

$real_directory = realpath ($config['homedir'] . '/' . $directory);

echo '<h4>' . __('Index of %s', $directory) . '</h4>';

filemanager_file_explorer($real_directory,
	$directory,
	'index.php?sec=gsetup&sec2=godmode/setup/file_manager');
?>
