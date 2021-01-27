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
final class DBMaintainer
{
    const ESSENTIAL_TABLES = [
        'tagente_datos',
        'tagente_datos_string',
        'tevento',
        'tconfig',
    ];

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
     * Connected to engine and database.
     *
     * @var boolean
     */
    private $ready;


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
            null,
            $this->port
        );

        if ((bool) $dbc->connect_error === true) {
            $this->dbh = null;
            $this->connected = false;
            $this->lastError = $dbc->connect_errno.': '.$dbc->connect_error;
        } else {
            $this->dbh = $dbc;
            if (empty($this->charset) === false) {
                $dbc->set_charset($this->charset);
            }

            if ($this->dbh->select_db($this->name) === false) {
                $this->lastError = $this->dbh->errno.': '.$this->dbh->error;
                $this->ready = false;
            } else {
                $this->lastError = null;
                $this->ready = true;
            }

            $this->connected = true;
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
     * Retrieve all rows from given query in array format.
     *
     * @param string $query Query.
     *
     * @return array  Results.
     */
    private function getAllRows(string $query)
    {
        if ($this->ready !== true) {
            $this->lastError = $this->dbh->errno.': '.$this->dbh->error;
            return [];
        }

        $rs = $this->dbh->query($query);

        $results = [];

        do {
            $row = $rs->fetch_array(MYSQLI_ASSOC);
            if ((bool) $row !== false) {
                $results[] = $row;
            }
        } while ((bool) $row !== false);

        return $results;
    }


    /**
     * Verifies schema against running db.
     *
     * @return boolean Success or not.
     */
    public function verifySchema()
    {
        if ($this->ready !== true) {
            return false;
        }

        $missing_essential_tables = $this->verifyTables();

        return !(bool) count($missing_essential_tables);
    }


    /**
     * Verifies tables against running db.
     *
     * @return boolean Applied or not.
     */
    public function verifyTables()
    {
        global $config;

        $t = \db_get_all_rows_sql(
            sprintf(
                'SHOW TABLES FROM %s',
                $config['dbname']
            )
        );

        $tables = [];
        foreach ($t as $v) {
            $tables[] = array_shift($v);
        }

        $t = $this->getAllRows(
            sprintf(
                'SHOW TABLES FROM %s',
                $this->name
            )
        );
        $myTables = [];
        foreach ($t as $k => $v) {
            $myTables[] = array_shift($v);
        }

        $differences = array_diff($tables, $myTables);

        if (count($differences) > 0) {
            $this->lastError = sprintf(
                'Warning, following tables does not exist in target: %s',
                join(', ', $differences)
            );
        }

        // Exclude extension tables.
        $differences = array_intersect($differences, self::ESSENTIAL_TABLES);

        return $differences;
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

        if ($this->ready !== true) {
            // Not ready, create database in target.
            $rc = $this->dbh->query(
                sprintf(
                    'CREATE DATABASE %s',
                    $this->name
                )
            );

            if ($rc === false) {
                $this->lastError = $this->dbh->errno.': '.$this->dbh->error;
                return false;
            }

            if ($this->dbh->select_db($this->name) === false) {
                $this->lastError = $this->dbh->errno.': '.$this->dbh->error;
                return false;
            }

            // Already connected and ready to execute commands.
            $this->ready = true;
        } else if ($this->verifySchema() === true) {
            $this->installed = true;
            return true;
        }

        return $this->applyDump(Config::get('homedir', '').'/pandoradb.sql');

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


    /**
     * This function keeps same functionality as install.php:parse_mysqli_dump.
     *
     * @param string $path Path where SQL dump file is stored.
     *
     * @return boolean Success or not.
     */
    private function applyDump(string $path)
    {
        if (file_exists($path) === true) {
            $file_content = file($path);
            $query = '';
            foreach ($file_content as $sql_line) {
                if (trim($sql_line) !== ''
                    && strpos($sql_line, '-- ') === false
                ) {
                    $query .= $sql_line;
                    if ((bool) preg_match("/;[\040]*\$/", $sql_line) === true) {
                        $result = $this->dbh->query($query);
                        if ((bool) $result === false) {
                            $this->lastError = $this->dbh->errnum.': ';
                            $this->lastERror .= $this->dbh->error;
                            return false;
                        }

                        $query = '';
                    }
                }
            }

            return true;
        }

        // File does not exist.
        $this->lastError = sprintf(
            'File %s does not exist',
            $path
        );
        return false;
    }


}
