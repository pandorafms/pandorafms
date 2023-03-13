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
namespace PandoraFMS;

/**
 * Object user.
 */
class User extends Entity implements PublicLogin
{

    /**
     * Current 'id_usuario'.
     *
     * @var string
     */
    public $idUser;

    /**
     * User main table.
     *
     * @var string
     */
    protected $table;


    /**
     * Initializes a user object.
     *
     * @param mixed $id_user User id.
     * - Username
     */
    public function __construct($id_user)
    {
        $this->table = 'tusuario';

        if (is_string($id_user) === true
            && empty($id_user) === false
        ) {
            $filter = ['id_user' => $id_user];
            parent::__construct(
                $this->table,
                $filter
            );
        } else {
            // Create empty skel.
            parent::__construct($this->table, null);
        }
    }


    /**
     * Saves current definition to database.
     *
     * @param boolean $alias_as_name Use alias as agent name.
     *
     * @return mixed Affected rows of false in case of error.
     * @throws \Exception On error.
     */
    public function save()
    {
        if (empty($this->idUser) === false) {
            if (is_user($this->idUser) === true) {
                // User update.
                $updates = $this->fields;

                $rs = \db_process_sql_update(
                    $this->table,
                    $updates,
                    ['id_user' => $this->fields['id_user']]
                );

                if ($rs === false) {
                    global $config;
                    throw new \Exception(
                        __METHOD__.' error: '.$config['dbconnection']->error
                    );
                }
            } else {
                // User creation.
                $userData = $this->fields;

                // Clean null fields.
                foreach ($userData as $k => $v) {
                    if ($v === null) {
                        unset($userData[$k]);
                    }
                }

                $rs = create_user($userData['id_user'], $userData['password'], $userData);

                if ($rs === false) {
                    global $config;
                    $error = $config['dbconnection']->error;

                    throw new \Exception(
                        __METHOD__.' error: '.$error
                    );
                }

                $this->fields['id_user'] = $rs;
            }
        }

        return true;
    }


    /**
     * Authentication.
     *
     * @param array|null $data User information.
     * - Username
     * - PHP session ID.
     *
     * @return static
     */
    public static function auth(?array $data)
    {
        global $config;

        // Unset user.
        unset($config['id_usuario']);
        unset($_SESSION['id_usuario']);

        if (is_array($data) === true) {
            if (isset($data['phpsessionid']) === true) {
                $info = \db_get_row_filter(
                    'tsessions_php',
                    ['id_session' => io_safe_input($data['phpsessionid'])]
                );

                if ($info !== false) {
                    // Process.
                    $session_data = session_decode($info['data']);
                    $user = new self($_SESSION['id_usuario']);

                    // Valid session.
                    return $user;
                }

                return null;
            }

            if (isset($data['id_usuario']) === true
                && isset($data['password']) === true
            ) {
                $user_in_db = process_user_login(
                    $data['id_usuario'],
                    $data['password'],
                    true
                );
                if ($user_in_db !== false) {
                    $config['id_usuario'] = $user_in_db;
                    $config['id_user'] = $user_in_db;

                    // Originally at api.php.
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }

                    $_SESSION['id_usuario'] = $data['id_usuario'];
                    session_write_close();

                    $user = new self($data['id_usuario']);
                    // Valid session.
                    return $user;
                }
            }
        }
    }


    /**
     * Process login
     *
     * @param array|null $data Data.
     *
     * @return boolean
     */
    public static function login(?array $data)
    {
        $user = self::auth($data);

        if ($user->idUser === null) {
            return false;
        }

        return true;
    }


    /**
     * Generates a hash to authenticate in public views.
     *
     * @param string|null $other_secret If you need to authenticate using a
     * varable string, use this 'other_secret' to customize the hash.
     *
     * @return string Returns a hash with the authenticaction.
     */
    public static function generatePublicHash(?string $other_secret=''): string
    {
        global $config;

        $str = $config['dbpass'];
        $str .= $config['id_user'];
        $str .= $other_secret;
        return hash('sha256', $str);
    }


    /**
     * Validates a hash to authenticate in public view.
     *
     * @param string $hash         Hash to be checked.
     * @param string $other_secret Any custom string needed for you.
     *
     * @return boolean Returns true if hash is valid.
     */
    public static function validatePublicHash(
        string $hash,
        string $other_secret=''
    ): bool {
        global $config;

        if (isset($config['id_user']) === true) {
            // Already logged in.
            return true;
        }

        $userFromParams = false;
        // Try to get id_user from parameters if it is missing.
        if (isset($config['id_user']) === false) {
            $userFromParams = true;
            $config['id_user'] = get_parameter('id_user', false);
            // It is impossible to authenticate without an id user.
            if ($config['id_user'] === false) {
                unset($config['id_user']);
                return false;
            }
        } else {
            $config['public_access'] = false;
        }

        if (empty($other_secret) === true) {
            $auth_token_secret = db_get_value('auth_token_secret', 'tusuario', 'id_user', $config['id_user']);

            if (empty($auth_token_secret) === false) {
                $other_secret = $auth_token_secret;
            }
        }

        // Build a hash to check.
        $hashCheck = self::generatePublicHash($other_secret);
        if ($hashCheck === $hash) {
            // "Log" user in.
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $_SESSION['id_usuario'] = $config['id_user'];
            session_write_close();

            $config['public_access'] = true;
            $config['force_instant_logout'] = true;
            return true;
        }

        // Remove id user from config array if authentication has failed.
        if ($userFromParams === true) {
            unset($config['id_user']);
        }

        return false;
    }


}
