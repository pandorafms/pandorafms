<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage HTML
 */


/**
 * Get a list of CSS themes installed.
 *
 * @param bool List all css files of an specific path without filter "pandora*" pattern
 * Note: If you want to exclude a Css file from the resulting list put "Exclude css from visual styles" in the file header
 *
 * @return array An indexed array with the file name in the index and the theme
 * name (if available) as the value.
 */
function themes_get_css($path=false)
{
    if ($path) {
        $theme_dir = $path;
    } else {
        $theme_dir = 'include/styles/';
    }

    if ($path) {
        $files = list_files($theme_dir, 'pandora', 0, 0);
    } else {
        $files = list_files($theme_dir, 'pandora', 1, 0);
    }

    $retval = [];
    foreach ($files as $file) {
        if ($file === 'pandora_green_old.css') {
            continue;
        }

        // Skip '..' and '.' entries and files not ended in '.css'.
        if ($path && ($file == '.' || $file == '..' || strtolower(substr($file, (strlen($file) - 4))) !== '.css')) {
            continue;
        }

        $data = implode('', file($theme_dir.'/'.$file));
        if (preg_match('|Exclude css from visual styles|', $data)) {
            continue;
        }

        preg_match('|Name:(.*)$|mi', $data, $name);
        if (isset($name[1])) {
            $retval[$file] = trim($name[1]);
        } else {
            $retval[$file] = $file;
        }
    }

    return $retval;
}
