<?php
/**
 * Class to manage some advanced operations over strings.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Tools
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.
namespace PandoraFMS\Tools;

global $config;

/**
 * Files class definition.
 */
class Strings
{


    /**
     * Retrieves a diff output for given strings.
     *
     * @param string $a A.
     * @param string $b B.
     *
     * @return string Diff or error.
     */
    public static function diff(string $a, string $b)
    {
        $A = sys_get_temp_dir().'/A-'.uniqid();
        $B = sys_get_temp_dir().'/B-'.uniqid();

        file_put_contents($A, \io_safe_output($a));
        file_put_contents($B, \io_safe_output($b));

        $cmd = 'diff -u '.$A.' '.$B.' 2>&1';
        exec($cmd, $output, $rc);

        unlink($A);
        unlink($B);

        $output = join("\n", $output);

        if ($rc <= 1) {
            return $output;
        }

        return 'Error ['.$rc.']: '.$output;
    }


}
