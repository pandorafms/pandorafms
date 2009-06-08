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

/**
 * Get a list of CSS themes installed.
 *
 * @return array An indexed array with the file name in the index and the theme
 * name (if available) as the value.
 */
function get_css_themes () {
	$theme_dir = 'include/styles/';
	
	$files = list_files ($theme_dir, "pandora", 1, 0);
	
	$retval = array ();
	foreach ($files as $file) {
		$data = implode ('', file ($theme_dir.'/'.$file));
		preg_match ('|Name:(.*)$|mi', $data, $name);
		if (isset ($name[1]))
			$retval[$file] = trim ($name[1]);
		else
			$retval[$file] = $file;
	}
	
	return $retval;
}

?>
