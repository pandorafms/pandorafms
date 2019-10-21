<?php
/**
 * PHP script to manage Pandora FMS websockets.
 *
 * @category   Websocket
 * @package    Pandora FMS
 * @subpackage Console
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
namespace PandoraFMS;

/**
 * Object user.
 */
class User
{

    /**
     * Current 'id_usuario'.
     *
     * @var string
     */
    public $idUser;

    /**
     * Current PHP session ids.
     *
     * @var array
     */
    public $sessions = [];


    /**
     * Initializes a user object.
     *
     * @param array $data User information
     * - Username
     * - PHP session ID.
     */
    public function __construct($data)
    {
        global $config;

        // Unset user.
        unset($config['id_usuario']);
        unset($_SESSION['id_usuario']);

        if (is_array($data) === true) {
            if (isset($data['phpsessionid']) === true) {
                $this->sessions[$data['phpsessionid']] = 1;
                $info = \db_get_row_filter(
                    'tsessions_php',
                    ['id_session' => $data['phpsessionid']]
                );

                if ($info !== false) {
                    // Process.
                    $session_data = session_decode($info['data']);
                    $this->idUser = $_SESSION['id_usuario'];

                    // Valid session.
                    return $this;
                }

                return null;
            }

            if (isset($data['id_usuario']) === true
                && isset($data['password']) === true
            ) {
                $user_in_db = process_user_login($user, $password, true);
                if ($user_in_db !== false) {
                    $config['id_usuario'] = $user_in_db;
                    $correctLogin = true;

                    // Originally at api.php.
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }

                    $_SESSION['id_usuario'] = $user;
                    session_write_close();

                    $this->idUser = $data['id_usuario'];
                    // Valid session.
                    return $this;
                }
            }
        }

        return null;

    }


}
