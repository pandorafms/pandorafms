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
