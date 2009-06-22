<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

define ('MIME_UNKNOWN', 0);
define ('MIME_DIR', 1);
define ('MIME_IMAGE', 2);
define ('MIME_ZIP', 3);

if (!function_exists ('mime_content_type')) {
	/**
	 * Gets the MIME type of a file.
	 *
	 * Help function in case mime_magic is not loaded on PHP.
	 *
	 * @param string Filename to get MIME type.
	 *
	 * @return The MIME type of the file.
	 */
	function mime_content_type ($filename) {
		$mime_types = array (
			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',
			// images
			'png' => 'image/png',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',
			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',
			'gz' => 'application/x-gzip',
			'gz' => 'application/x-bzip2',
			// audio/video
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',
			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',
			// ms office
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',
			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
		);

		$ext = strtolower (array_pop (explode ('.', $filename)));
		if (array_key_exists ($ext, $mime_types)) {
			return $mime_types[$ext];
		} elseif (function_exists ('finfo_open')) {
			$finfo = finfo_open (FILEINFO_MIME);
			$mimetype = finfo_file ($finfo, $filename);
			finfo_close ($finfo);
			return $mimetype;
		} else {
			return 'application/octet-stream';
		}
	}
}


/**
 * Get the available directories of the file manager.
 *
 * @return array An array with all the directories where the file manager can
 * operate.
 */
function get_file_manager_available_directories () {
	global $config;
	
	$dirs = array ();
	$dirs['images'] = "images";
	$dirs['attachment'] = "attachment";
	$dirs['languages'] = "include/languages";
	
	foreach ($dirs as $dirname) {
		$dirpath = realpath ($config['homedir'].'/'.$dirname);
		$dir = opendir ($dirpath);
		while ($file = @readdir ($dir)) {
			/* Ignore hidden files */
			if ($file[0] == '.')
				continue;
			$filepath = $dirpath.'/'.$file;
			if (is_dir ($filepath)) {
				$dirs[$dirname.'/'.$file] = $dirname.'/'.$file;
			}
		}
	}
	
	return $dirs;
}

/**
 * Check if a dirname is available for the file manager.
 *
 * @param string Dirname to check.
 * 
 * @return array An array with all the directories where the file manager can
 * operate.
 */
function is_file_manager_available_directory ($dirname) {
	$dirs = get_file_manager_available_directories ();
	
	return isset ($dirs[$dirname]);
}

/**
 * Check if a directory is writable.
 *
 * @param string Directory path to check.
 * @param bool If set, it will try to make the directory writeable if it's not.
 *
 * @param bool Wheter the directory is writeable or not.
 */
function is_file_manager_writable_dir ($dirpath, $force = false) {
	if (is_file_manager_available_directory (basename ($dirpath)))
		return is_writable ($dirpath);
	if (is_file_manager_writable_dir (realpath ($dirpath.'/..')))
		return true;
	else if (! $force)
			return is_writable ($dirpath);
	
	return (is_writable ($dirpath) || @chmod ($dirpath, 0755));
}

/**
 * Check if a directory is writable.
 *
 * @param string Directory path to check.
 * @param bool If set, it will try to make the directory writeable if it's not.
 *
 * @param bool Wheter the directory is writeable or not.
 */
function get_file_manager_file_info ($filepath) {
	global $config;
	
	$realpath = realpath ($filepath);
	
	$info = array ('mime' => MIME_UNKNOWN,
		'mime_extend' => mime_content_type ($filepath),
		'link' => 0,
		'is_dir' => false,
		'name' => basename ($realpath),
		'url' => $config['homeurl'].str_ireplace ($config['homedir'], '', $realpath),
		'realpath' => $realpath,
		'size' => filesize ($realpath),
		'last_modified' => filemtime ($realpath)
	);
	
	$zip_mimes = array ('application/zip',
		'application/x-rar-compressed',
		'application/x-gzip',
		'application/x-bzip2');
	if (is_dir ($filepath)) {
		$info['mime'] = MIME_DIR;
		$info['is_dir'] = true;
		$info['size'] = 0;
	} else if (strpos ($info['mime_extend'], 'image') === 0) {
		$info['mime'] = MIME_IMAGE;
	} else if (in_array ($info['mime_extend'], $zip_mimes)) {
		$info['mime'] = MIME_ZIP;
	}
	
	return $info;
}

/**
 * Check if a directory is writable.
 *
 * @param string Directory path to check.
 * @param bool If set, it will try to make the directory writeable if it's not.
 *
 * @param bool Wheter the directory is writeable or not.
 */
function list_file_manager_dir ($dirpath) {
	$files = array ();
	$dirs = array ();
	$dir = opendir ($dirpath);
	while ($file = @readdir ($dir)) {
		/* Ignore hidden files */
		if ($file[0] == '.')
			continue;
		$info = get_file_manager_file_info ($dirpath.'/'.$file);
		if ($info['is_dir']) {
			$dirs[$file] = $info;
		} else {
			$files[$file] = $info;
		}
	}
	ksort ($files);
	ksort ($dirs);
	closedir ($dir);
	
	return array_merge ($dirs, $files);
}
?>
