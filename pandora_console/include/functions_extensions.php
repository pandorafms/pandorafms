<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Extensions
 */

$extension_file = '';


/**
 * Callback function for extensions in the console 
 *
 * @param string $filename with contents of the extension
 */
function extensions_call_main_function ($filename) {
	global $config;
	
	$extension = &$config['extensions'][$filename];
	if ($extension['main_function'] != '') {
		$params = array ();
		call_user_func_array ($extension['main_function'], $params);
	}
}

/**
 * Callback function for godmode extensions
 *
 * @param string $filename File with extension contents
 */
function extensions_call_godmode_function ($filename) {
	global $config;
	
	$extension = &$config['extensions'][$filename];
	if ($extension['godmode_function'] != '') {
		$params = array ();
		call_user_func_array ($extension['godmode_function'], $params);
	}
}

/**
 * Callback login function for extensions
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
 * Checks if the current page is an extension 
 *
 * @param string $page To check
 */
function extensions_is_extension ($page) {
	global $config;
	
	$filename = basename ($page);
	return isset ($config['extensions'][$filename]);
}

/**
 * Scan the EXTENSIONS_DIR or ENTERPRISE_DIR.'/'.EXTENSIONS_DIR for search
 * the files extensions.
 *
 * @param bool $enterprise
 */
function extensions_get_extensions ($enterprise = false) {
	$dir = EXTENSIONS_DIR;
	$handle = false;
	if ($enterprise)
		$dir = ENTERPRISE_DIR.'/'.EXTENSIONS_DIR;

	if (file_exists ($dir))
		$handle = @opendir ($dir);	
	
	if (empty ($handle))
		return;
		
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
		return array_merge ($extensions, extensions_get_extensions (true));
	
	return $extensions;
}

/**
 * Get disabled open and enterprise extensions 
 */
function extensions_get_disabled_extensions() {
	global $config;
	
	$extensions = array ();
	
	$dirs = array('open' => EXTENSIONS_DIR . '/disabled', 'enterprise' =>  ENTERPRISE_DIR . '/' . EXTENSIONS_DIR . '/disabled');
	
	foreach ($dirs as $type => $dir) {
		$handle = false;
			
		if (file_exists ($dir))
			$handle = @opendir ($dir);	
		
		if (empty ($handle))
			continue;
		
		$ignores = array ('.', '..');
		
		$file = readdir ($handle);
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
			
			//$content = file_get_contents($filepath);
			$content = '';
			
			$data = array();
			
			$data['operation_menu'] = false;
			if (preg_match("/<?php(\n|.)*extensions_add_operation_menu_option(\n|.)*?>/", $content)) {
				$data['operation_menu'] = true;
			}
			
			$data['godmode_menu'] = false;
			if (preg_match('/<\?php(\n|.)*extensions_add_godmode_menu_option(\n|.)*\?>/', $content)) {
				$data['godmode_menu'] = true;
			}
			
			$data['operation_function'] = false;
			if (preg_match('/<\?php(\n|.)*extensions_add_main_function(\n|.)*\?>/', $content)) {
				$data['operation_function'] = true;
			}
			
			$data['login_function'] = false;
			if (preg_match('/<\?php(\n|.)*extensions_add_login_function(\n|.)*\?>/', $content)) {
				$data['login_function'] = true;
			}
			
			$data['extension_ope_tab'] = false;
			if (preg_match('/<\?php(\n|.)*extensions_add_opemode_tab_agent(\n|.)*\?>/', $content)) {
				$data['extension_ope_tab'] = true;
			}
			
			$data['extension_god_tab'] = false;
			if (preg_match('/<\?php(\n|.)*extensions_add_godmode_tab_agent(\n|.)*\?>/', $content)) {
				$data['extension_god_tab'] = true;
			}
			
			$data['godmode_function'] = false;
			if (preg_match('/<\?php(\n|.)*extensions_add_godmode_function(\n|.)*\?>/', $content)) {
				$data['godmode_function'] = true;
			}
			
			$data['enterprise'] = false;
			if ($type == 'enterprise') {
				$data['enterprise'] = true;
			}
			
			$data['enabled'] = false;
			
			$extensions[$file] = $data;
			
			$file = readdir ($handle);
		}
	}
	
	return $extensions;	
}

/**
 * Get info of all extensions (enabled/disabled)
 */
function extensions_get_extension_info() {
	global $config;
	
	$return = array ();
	
	foreach($config['extensions'] as $extension) {
		$data = array();
		$data['godmode_function'] = false;
		if (!empty($extension['godmode_function'])) {
			$data['godmode_function'] = true;
		}
		
		$data['godmode_menu'] = false;
		if (!empty($extension['godmode_menu'])) {
			$data['godmode_menu'] = true;
		}
		
		$data['operation_function'] = false;
		if (!empty($extension['main_function'])) {
			$data['operation_function'] = true;
		}
		
		$data['operation_menu'] = false;
		if (!empty($extension['operation_menu'])) {
			$data['operation_menu'] = true;
		}
		
		$data['login_function'] = false;
		if (!empty($extension['login_function'])) {
			$data['login_function'] = true;
		}
		
		$data['extension_ope_tab'] = false;
		if (!empty($extension['extension_ope_tab'])) {
			$data['extension_ope_tab'] = true;
		}
		
		$data['extension_god_tab'] = false;
		if (!empty($extension['extension_god_tab'])) {
			$data['extension_god_tab'] = true;
		}
		
		$data['enterprise'] = (bool)$extension['enterprise'];
		
		$data['enabled'] = true;
		
		$return[$extension['file']] = $data;
	}
	
	$return = $return + extensions_get_disabled_extensions();
	
	return $return;
}

/**
 * Load all extensions 
 *
 * @param array $extensions
 */
function extensions_load_extensions ($extensions) {
	global $config;
	global $extension_file;
	
	foreach ($extensions as $extension) {
		$extension_file = $extension['file'];
		require_once (realpath ($extension['dir'] . "/" . $extension_file));
	}
}

/**
 * This function adds a link to the extension with the given name in Operation menu.
 *
 * @param string name Name of the extension in the Operation menu  
 * @param string fatherId Id of the parent menu item for the current extension 
 * @param string icon Path to the icon image (18x18 px). If this parameter is blank then predefined icon will be used
 */
function extensions_add_operation_menu_option ($name, $fatherId = null, $icon = null, $version="N/A") {
	global $config;
	global $extension_file;
	
	/*
	$config['extension_file'] is set in extensions_load_extensions(),
	since that function must be called before any function the extension
	call, we are sure it will be set.
	*/
	$option_menu['name'] = $name;
	
	$extension = &$config['extensions'][$extension_file];
	
	$option_menu['sec2'] = $extension['dir'] . '/' . mb_substr ($extension_file, 0, -4);
	$option_menu['fatherId'] = $fatherId;
	$option_menu['icon'] = $icon;
	$option_menu['version'] = $version;
	
	$extension['operation_menu'] = $option_menu;
}

/**
 * This function adds a link to the extension with the given name in Godmode menu.
 *
 * @param string name Name of the extension in the Godmode menu  
 * @param string acl User ACL level required to see this extension in the godmode menu 
 * @param string fatherId Id of the parent menu item for the current extension 
 * @param string icon Path to the icon image (18x18 px). If this parameter is blank then predefined icon will be used
 */
function extensions_add_godmode_menu_option ($name, $acl, $fatherId = null, $icon = null, $version="N/A") {
	global $config;
	global $extension_file;
	
	/*
	$config['extension_file'] is set in extensions_load_extensions(),
	since that function must be called before any function the extension
	call, we are sure it will be set. */
	$option_menu['acl'] = $acl;
	$option_menu['name'] = $name;
	$extension = &$config['extensions'][$extension_file];
	$option_menu['sec2'] = $extension['dir'] . '/' . mb_substr ($extension_file, 0, -4);
	$option_menu['fatherId'] = $fatherId;
	$option_menu['icon'] = $icon;
	$option_menu['version'] = $version;
	$extension['godmode_menu'] = $option_menu;
}

/**
 * Add in the header tabs in Godmode agent menu the extension tab. 
 * 
 * @param tabId Id of the extension tab   
 * @param tabName Name of the extension tab
 * @param tabIcon Path to the image icon 
 * @param tabFunction Name of the function to execute when this extension is called
 */
function extensions_add_godmode_tab_agent($tabId, $tabName, $tabIcon, $tabFunction, $version="N/A") {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$extension['extension_god_tab'] = array();
	$extension['extension_god_tab']['id'] = $tabId;
	$extension['extension_god_tab']['name'] = $tabName;
	$extension['extension_god_tab']['icon'] = $tabIcon;
	$extension['extension_god_tab']['function'] = $tabFunction;
	$extension['extension_god_tab']['version'] = $version;
}

/**
 * Add in the header tabs in Operation agent menu the extension tab.
 * 
 * @param tabId Id of the extension tab
 * @param tabName Name of the extension tab
 * @param tabIcon Path to the image icon 
 * @param tabFunction Name of the function to execute when this extension is called
 */
function extensions_add_opemode_tab_agent($tabId, $tabName, $tabIcon, $tabFunction, $version="N/A") {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$extension['extension_ope_tab'] = array();
	$extension['extension_ope_tab']['id'] = $tabId;
	$extension['extension_ope_tab']['name'] = $tabName;
	$extension['extension_ope_tab']['icon'] = $tabIcon;
	$extension['extension_ope_tab']['function'] = $tabFunction;
	$extension['extension_ope_tab']['version'] = $version;
}

/**
 * Add the function to call when user clicks on the Operation menu link
 *
 * @param string $function_name Callback function name
 */
function extensions_add_main_function ($function_name) {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$extension['main_function'] = $function_name;
}

/**
 * Add the function to call when user clicks on the Godmode menu link
 *
 * @param string $function_name Callback function name
 */
function extensions_add_godmode_function ($function_name) {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$extension['godmode_function'] = $function_name;
}

/**
 * Adds extension function when user login on Pandora console
 *
 * @param string $function_name Callback function name
 */
function extensions_add_login_function ($function_name) {
	global $config;
	global $extension_file;
	
	$extension = &$config['extensions'][$extension_file];
	$extension['login_function'] = $function_name;
}
?>
