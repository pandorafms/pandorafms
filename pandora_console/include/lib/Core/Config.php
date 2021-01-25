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

require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../functions_config.php';

/**
 * Config class to operate console configuration.
 */
final class Config
{

    /**
     * History database settings (tconfig).
     *
     * @var array
     */
    private static $settings = [];


    /**
     * Load history database settings.
     */
    private static function loadHistoryDBSettings()
    {
        if (self::$settings === null) {
            $data = \db_get_all_rows_filter('tconfig', [], false, 'AND', true);
            self::$settings = array_reduce(
                $data,
                function ($carry, $item) {
                    $carry[$item['token']] = $item['value'];
                },
                []
            );
        }
    }


    /**
     * Retrieve configuration token.
     *
     * @param string  $token      Token to retrieve.
     * @param mixed   $default    Default value if not found.
     * @param boolean $history_db Search for token in history_db.
     *
     * @return mixed Configuration token.
     */
    public static function get(
        string $token,
        $default=null,
        bool $history_db=false
    ) {
        if ($history_db === true) {
            self::loadHistoryDBSettings();
            if (isset(self::$settings[$token]) === true) {
                return self::$settings[$token];
            }
        } else {
            global $config;

            if (isset($config[$token]) === true) {
                return $config[$token];
            }
        }

        return $default;

    }


    /**
     * Set configuration token.
     *
     * @param string  $token      Token to set.
     * @param mixed   $value      Value to be.
     * @param boolean $history_db Save to history_db settings.
     *
     * @return void
     */
    public static function set(string $token, $value, bool $history_db=false)
    {
        if ($history_db !== false) {
            if (self::get($token, null, $history_db) === null) {
            }
        } else {
            if (self::get($token) === null) {
                config_update_value($token, $value);
            }
        }
    }


}
