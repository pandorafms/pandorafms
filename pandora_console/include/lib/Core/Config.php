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
     *
     * @return void
     */
    private static function loadHistoryDBSettings()
    {
        global $config;

        if ((bool) $config['history_db_enabled'] === false) {
            return;
        }

        // Connect if needed.
        if (isset($config['history_db_connection']) === false
            || $config['history_db_connection'] === false
        ) {
            ob_start();

            $link = mysqli_init();
            $link->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
            $rc = mysqli_real_connect(
                $link,
                $config['history_db_host'],
                $config['history_db_user'],
                io_output_password($config['history_db_pass']),
                $config['history_db_name'],
                (int) $config['history_db_port']
            );

            if ($rc === false) {
                $config['history_db_connection'] = false;
            } else {
                $config['history_db_connection'] = db_connect(
                    $config['history_db_host'],
                    $config['history_db_name'],
                    $config['history_db_user'],
                    io_output_password($config['history_db_pass']),
                    (int) $config['history_db_port'],
                    false
                );
            }

            ob_get_clean();
        }

        if (isset($config['history_db_connection']) === true
            && $config['history_db_connection'] !== false
        ) {
            $data = \db_get_all_rows_sql(
                'SELECT * FROM `tconfig`',
                false,
                false,
                $config['history_db_connection']
            );
        }

        if (is_array($data) !== true) {
            return [];
        }

        self::$settings = array_reduce(
            $data,
            function ($carry, $item) {
                $carry[$item['token']] = $item['value'];
                return $carry;
            },
            []
        );
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

            return $default;
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
     * @return boolean Success or not.
     */
    public static function set(string $token, $value, bool $history_db=false)
    {
        global $config;

        $rs = false;

        if ($history_db !== false) {
            if (self::get($token, null, $history_db) === null) {
                // Create.
                $rs = \db_process_sql(
                    sprintf(
                        'INSERT INTO `tconfig` (`token`, `value`)
                         VALUES ("%s", "%s")',
                        $token,
                        $value
                    ),
                    'affected_rows',
                    $config['history_db_connection']
                );
            } else {
                // Update.
                $rs = \db_process_sql(
                    sprintf(
                        'UPDATE `tconfig`
                         SET `value`= "%s"
                        WHERE `token` = "%s"',
                        $value,
                        $token
                    ),
                    'affected_rows',
                    $config['history_db_connection']
                );
            }
        } else {
            $rs = \config_update_value($token, $value);
        }

        return ($rs !== false);
    }


}
