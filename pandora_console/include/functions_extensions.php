<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

$extension_file = '';


/**
 * TODO: Document extensions
 *
 * @param string $filename
 */
function extension_call_main_function ($filename) {
	global $config;
	
	$extension = &$config['extensions'][$filename];
	if ($extension['main_function'] != '') {
		$params = array ();
		call_user_func_array ($extension['main_function'], $params);
	}
}

/**
 * TODO: Document extensions
 *
 * @param string $filename
 */
function extension_call_godmode_function ($filename) {
	global $config;
	
	$extension = &$config['extensions'][$filename];
	if ($extension['godmode_function'] != '') {
		$params = array ();
		call_user_func_array ($extension['godmode_function'], $params);
	}
}

/**
 * TODO: Document extensions
 */
function extensions_call_login_function () {
	global $config;
	
	$params = array ();
	foreach ($config['extensions'] as $extension) {
		if ($extension['login_function'] == '')
			continue;
		call_user_func_array ($extension['login_function'], $params);
	}
}

/**
 * TODO: Document extensions
 *
 * @param string $page
 */
function is_extension ($page) {
	global $config;
	
	$filename = basename ($page);
	return isset ($config['extensions'][$filename]);
}

/**
 * TODO: Document extensions
 *
 * @param bool $enterprise
 */
function get_extensions ($enterprise = false) {
	$dir = EXTENSIONS_DIR;
	if ($enterprise)
		$dir = ENTERPRISE_DIR.'/'.EXTENSIONS_DIR;
	$handle = @opendir ($dir);
	if (! $handle) {
		return;
	}
	$file = readdir ($handle);
	$extensions = array ();
	$ignores = array ('.', '..');
	while ($file !== false) {
		if (in_array ($file, $ignores)) {
			$file = readdir ($handle);
			continue;
		}
		$filepath = realpath ($dir."/".$file);
		if (! is_readable ($filepath) || is_dir ($filepath) || ! preg_match ("/.*\.php$/", $filepath)) {
			$file = readdir ($handle);
			continue;
		}
		$extension['file'] = $file;
		$extension['operation_menu'] = '';
		$extension['godmode_menu'] = '';
		$extension['main_function'] = '';
		$extension['godmode_function'] = '';
		$extension['login_function'] = '';
		$extension['enterprise'] = $enterprise;
		$extension['dir'] = $dir;
		$extensions[$file] = $extension;
		$file = readdir ($handle);
	}
	
	/* Load extensions in enterprise directory */
	if (! $enterprise && file_exists (ENTERPRISE_DIR.'/'.EXTENSIONS_DIR))
		return array_merge ($extensions, get_extensions (true));
	
	return $extensions;
}

/**
 * TODO: Document extensions
 *
 * @param array $extensions
 */
function load_extensions ($extensions) {
	global $config;
	global $extension_file;
	
	foreach ($extensions as $extension) {
		$extension_file = $extension['file'];
		include_once (realpath ($extension['dir']."/".$extension_file));
	}
}

/**
 * TODO: Document extensions
 *
 * @param string $name
 */
function add_operation_menu_option ($name) {
	global $config;
	global $extension_file;
	
	/* $config['extension_file'] is set in load_extensions(), since that function must
	   be called before any function the extension call, we are sure it will 
	   be set. */
	$option_menu['name'] = substr ($name, 0, 15);
	$extension = &$config['extensions'][$extension_file];
	$option_menu['sec2'] = $extension['dir'].'/'.substr ($extension_file, 0, -4);
	$extension['operation_menu'] = $option_menu;
}

/**
 * TODO: Document extensions
 *
 * @param string $name
 * @param string $acl
 */
function add_godmode_menu_option ($name, $acl) {
	global $config;
	global $extension_file;
	
	/* $config['extension_file'] is set in load_extensions(), since that function must
	   be called before any function the extension call, we are sure it will 
	   be set. */
	$option_menu['acl'] = $acl;
	$option_menu['name'] = substr ($name, 0, 15);
	$extension = &$config['extensions'][$extension_file];
	$option_menu['sec2'] = $extension['dir'].'/'.substr ($extension_file, 0, -4);
	$extension['godmode_menu'] = $option_menu;
}

/**
 * TODO: Document extensions
 *
 * @param string $function_name
 */
function add_extension_main_function ($function_name) {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$extension['main_function'] = $function_name;
}

/**
 * TODO: Document extensions
 *
 * @param string $function_name
 */
function add_extension_godmode_function ($function_name) {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$extension['godmode_function'] = $function_name;
}

/**
 * TODO: Document extensions
 *
 * @param string $function_name
 */
function add_extension_login_function ($function_name) {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$extension['login_function'] = $function_name;
}
?>
