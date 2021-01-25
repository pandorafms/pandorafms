<?php
/**
 * Config class.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage OpenSource
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
namespace PandoraFMS\Core;

require_once __DIR__.'/../../include/config.php';
require_once __DIR__.'/../../include/functions_config.php';

/**
 * Config class to operate console configuration.
 */
final class Config
{


    /**
     * Retrieve configuration token.
     *
     * @param string $token   Token to retrieve.
     * @param mixed  $default Default value if not found.
     *
     * @return mixed Configuration token.
     */
    public static function get(string $token, $default=null)
    {
        global $config;

        if (isset($config[$token]) === true) {
            return $config[$token];
        }

        return $default;

    }


    /**
     * Set configuration token.
     *
     * @param string $token Token to set.
     * @param mixed  $value Value to be.
     *
     * @return void
     */
    public static function set(string $token, $value)
    {
        if (self::get($token) === null) {
            config_update_value($token, $value);
        }
    }


}
