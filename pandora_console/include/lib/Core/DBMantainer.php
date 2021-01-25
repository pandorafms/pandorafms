<?php
/**
 * Database mantainer class.
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

/**
 * Class to handle database mantainer (not queries).
 */
final class DBMantainer
{

    /**
     * Database user.
     *
     * @var string
     */
    private $user;

    /**
     * Database pass.
     *
     * @var string
     */
    private $pass;

    /**
     * Database port.
     *
     * @var integer
     */
    private $port;

    /**
     * Database name.
     *
     * @var string
     */
    private $name;

    /**
     * Database host.
     *
     * @var string
     */
    private $host;

    /**
     * Charset forced.
     *
     * @var string
     */
    private $charset;

    /**
     * Verifies if PandoraFMS DB schema is installed.
     *
     * @var boolean
     */
    private $installed;

    /**
     * Verifies if endpoint is connected.
     *
     * @var boolean
     */
    private $connected;

    /**
     * Connection link.
     *
     * @var mixed|null
     */
    private $dbh;

    /**
     * Last error registered.
     *
     * @var string
     */
    private $lastError;


    /**
     * Initialize DBMaintainer object.
     *
     * @param array $params Database connection definition, including:
     * - user
     * - pass
     * - database name
     * - host (default 127.0.0.1)
     * - port (default 3306)
     * If not defined some fields will use default values.
     */
    public function __construct(array $params)
    {
        $this->user = $params['user'];
        $this->pass = $params['pass'];
        $this->host = $params['host'];
        $this->port = $params['port'];
        $this->name = $params['name'];
        $this->charset = $params['charset'];

        // Try to connect.
        $this->connect();
    }


    /**
     * Connects (if not connected) to current definition.
     *
     * @return boolean True if successfully connected, false if not.
     */
    private function connect()
    {
        if ($this->connected === true) {
            return true;
        }

        $dbc = new \mysqli(
            $this->host,
            $this->user,
            $this->pass,
            $this->name,
            $this->port
        );

        if ($dbc->connect_error === false) {
            $this->dbh = null;
            $this->connected = false;
            $this->lastError = $dbc->connect_errno.': '.$dbc->connect_error;
        } else {
            $this->dbh = $dbc;
            if (empty($this->charset) === false) {
                $dbc->set_charset($this->charset);
            }

            $this->connected = true;
            $this->lastError = null;
        }

    }


    /**
     * Retrieve last error.
     *
     * @return string Error message.
     */
    public function getLastError()
    {
        if ($this->lastError !== null) {
            return $this->lastError;
        }

        return '';
    }


    /**
     * Install PandoraFMS database schema in current target.
     *
     * @return boolean Installation is success or not.
     */
    public function install()
    {
        if ($this->connect() !== true) {
            return false;
        }

        if ($this->installed === true) {
            return true;
        }

        $this->lastError = 'Pending installation';
        return false;

    }


    /**
     * Updates PandoraFMS database schema in current target.
     *
     * @return boolean Current installation is up to date.
     */
    public function update()
    {
        if ($this->connect() !== true) {
            return false;
        }

        if ($this->install() !== true) {
            return false;
        }

        $this->lastError = 'Pending update';
        return false;

    }


    /**
     * Verifies current target database is connected, installed and updated.
     *
     * @return boolean Status of the installation.
     */
    public function check()
    {
        if ($this->connect() !== true) {
            return false;
        }

        if ($this->install() !== true) {
            return false;
        }

        if ($this->update() !== true) {
            return false;
        }

        return true;
    }


}
