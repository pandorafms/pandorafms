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
        $this->pass = io_output_password($params['pass']);
        $this->host = $params['host'];
        $this->port = (int) $params['port'];
        $this->name = $params['name'];
        $this->charset = (isset($params['charset']) === true) ? $params['charset'] : '';

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
        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        if ($this->connected === true) {
            return true;
        }

        // Problem with php 8.1.
        mysqli_report(MYSQLI_REPORT_OFF);

        $link = mysqli_init();
        $link->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
        $rc = mysqli_real_connect(
            $link,
            $this->host,
            $this->user,
            $this->pass,
            null,
            $this->port
        );

        if ($rc === false) {
            $this->dbh = null;
            $this->connected = false;
            $this->lastError = __(
                'Connection problems: %s',
                mysqli_connect_error()
            );
        } else {
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
    }


    /**
     * Return dbh object.
     *
     * @return mysqli
     */
    public function getDBH()
    {
        return $this->dbh;
    }


    /**
     * Return connection statuis.
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->connected;
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

        if ($rs !== false) {
            do {
                $row = $rs->fetch_array(MYSQLI_ASSOC);
                if ((bool) $row !== false) {
                    $results[] = $row;
                }
            } while ((bool) $row !== false);
        }

        return $results;
    }


    /**
     * Return first row available for given query.
     *
     * @param string $query Query to retrieve (1 row only).
     *
     * @return array Row.
     */
    private function getRow(string $query)
    {
        if ($this->ready !== true) {
            $this->lastError = $this->dbh->errno.': '.$this->dbh->error;
            return [];
        }

        $query .= ' LIMIT 1';
        $rs = $this->dbh->query($query);
        if ($rs !== false) {
            return $rs->fetch_array(MYSQLI_ASSOC);
        }

        // Error.
        return false;
    }


    /**
     * Retrieve value from given query.
     *
     * @param string $table  Table to query.
     * @param string $key    Field to retrieve.
     * @param array  $filter Filters to apply.
     * @param string $join   AND by default.
     *
     * @return mixed|null Value retrieved or null if not found.
     */
    private function getValue(
        string $table,
        string $key,
        array $filter,
        string $join='AND'
    ) {
        $query = sprintf(
            'SELECT %s FROM %s WHERE %s',
            $key,
            $table,
            \db_format_array_where_clause_sql($filter, $join)
        );
        $result = $this->getRow($query);
        if ($result !== false) {
            return $result[$key];
        }

        return false;
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
     * @return array Of differences.
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
     * Updates or creates a token in remote tconfig.
     *
     * @param string $token Token to be set.
     * @param mixed  $value Value for given token.
     *
     * @return boolean Success or not.
     */
    public function setConfigToken(string $token, $value)
    {
        $prev = $this->getValue('tconfig', 'value', ['token' => $token]);
        // If failed or not found, then insert.
        if ($prev === false || $prev === null) {
                // Create.
                $rs = $this->dbh->query(
                    sprintf(
                        'INSERT INTO `tconfig` (`token`, `value`)
                         VALUES ("%s", "%s")',
                        $token,
                        $value
                    )
                );
        } else {
            // Update.
            $rs = $this->dbh->query(
                sprintf(
                    'UPDATE `tconfig`
                         SET `value`= "%s"
                        WHERE `token` = "%s"',
                    $value,
                    $token
                )
            );
        }

        return ($rs !== false);
    }


    /**
     * Create database only (not schema) in target.
     *
     * @return boolean Success or not.
     */
    public function checkDatabaseDefinition()
    {
        if ($this->ready === true) {
            return true;
        }

        if ($this->dbh === null) {
            return false;
        }

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
        return true;
    }


    /**
     * Install PandoraFMS database schema in current target.
     *
     * @param boolean $check_only Check and return, do not perform actions.
     *
     * @return boolean Installation is success or not.
     */
    public function install(bool $check_only=false)
    {
        if ($this->connect() !== true) {
            return false;
        }

        if ($this->installed === true) {
            return true;
        }

        if ($this->ready !== true) {
            // Not ready, create database in target.
            if ($check_only === true) {
                $this->lastError = 'Database does not exist in target';
                return false;
            }

            if ($this->checkDatabaseDefinition() === false) {
                return false;
            }
        } else if ($this->verifySchema() === true) {
            $this->installed = true;
            $this->lastError = null;
            return true;
        }

        if ($check_only === true) {
            $this->lastError = 'Schema not applied in target';
            return false;
        }

        $result = $this->applyDump(Config::get('homedir', '').'/pandoradb.sql');

        // Set MR version according pandoradb_data.
        $data_content = file_get_contents(
            Config::get('homedir', '').'/pandoradb_data.sql'
        );
        if (preg_match('/\(\'MR\'\,\s*(\d+)\)/', $data_content, $matches) > 0) {
            $target_mr = $matches[1];
        }

        $cnf_update = $this->setConfigToken('MR', (int) $target_mr);

        return $result && $cnf_update;

    }


    /**
     * Updates PandoraFMS database schema in current target.
     *
     * @param boolean $check_only Perform only test without update.
     *
     * @return boolean Current installation is up to date.
     */
    public function update(bool $check_only=false)
    {
        if ($this->connect() !== true) {
            return false;
        }

        if ($this->install($check_only) !== true) {
            return false;
        }

        // Set MR version according pandoradb_data.
        $data_content = file_get_contents(
            Config::get('homedir', '').'/pandoradb_data.sql'
        );
        if (preg_match('/\(\'MR\'\,\s*(\d+)\)/', $data_content, $matches) > 0) {
            $target_mr = $matches[1];
        }

        // Active database MR version.
        $active_mr = (int) Config::get('MR', 0);

        // Historical database MR version.
        $last_mr_curr = (int) $this->getValue(
            'tconfig',
            'value',
            ['token' => 'MR']
        );

        if ($check_only === true) {
            if ($active_mr === $last_mr_curr) {
                return true;
            }

            $this->lastError = sprintf(
                'Database schema not up to date: #%d should be #%d',
                $last_mr_curr,
                $active_mr
            );
            if ($active_mr < $target_mr) {
                $this->lastError .= sprintf(
                    ' (latest available: #%d)',
                    $target_mr
                );
            }

            return false;
        }

        if ($last_mr_curr < $active_mr) {
            while ($last_mr_curr < $active_mr) {
                $last_mr_curr++;

                $path = Config::get('homedir', '');
                $file = sprintf('/extras/mr/%d.sql', $last_mr_curr);
                $updated_file = sprintf(
                    '/extras/mr/updated/%d.sql',
                    $last_mr_curr
                );

                $filename = $path.$file;
                if (file_exists($path.$file) !== true) {
                    // File does not exist, maybe already udpated in active DB?
                    $filename = $path.$updated_file;
                    if (file_exists($filename) !== false) {
                        $this->lastError = 'Unable to locate MR update #';
                        $this->lastError .= $last_mr_curr;
                        return false;
                    }
                }

                if ($this->applyDump($filename, true) !== true) {
                    $err = 'Unable to apply MR update #';
                    $err .= $last_mr_curr.': ';
                    $this->lastError = $err.$this->lastError;
                    return false;
                } else {
                    // Update MR value.
                    $this->setConfigToken('MR', $last_mr_curr);
                }
            }
        }

        if ($last_mr_curr !== $active_mr) {
            $this->lastError = 'Unknown database schema version, check MR in both active and historical database';
            return false;
        }

        return true;
    }


    /**
     * Process database checks perform required actions.
     * Returns true if it is connected, installed and updated.
     *
     * @return boolean Status of the installation.
     */
    public function process()
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
     * Check if target has schema updated.
     *
     * @return boolean
     */
    public function isUpdated()
    {
        return $this->update(true);
    }


    /**
     * Check if target has schema installed.
     *
     * @return boolean
     */
    public function isInstalled()
    {
        return $this->install(true);
    }


    /**
     * Checks if target is ready to connect.
     *
     * @return boolean
     */
    public function isReady()
    {
        if ($this->ready === true) {
            return true;
        }

        $this->connect();

        return $this->ready;
    }


    /**
     * Checks if current target is connected, installed and updated.
     *
     * @return boolean Status of the database schema.
     */
    public function check()
    {
        if ($this->connect() !== true) {
            return false;
        }

        if ($this->isInstalled() !== true) {
            return false;
        }

        if ($this->isUpdated() !== true) {
            return false;
        }

        return true;

    }


    /**
     * This function keeps same functionality as install.php:parse_mysqli_dump.
     *
     * @param string  $path          Path where SQL dump file is stored.
     * @param boolean $transactional Use transactions from file (true) (MRs).
     *
     * @return boolean Success or not.
     */
    private function applyDump(string $path, bool $transactional=false)
    {
        global $config;

        if (file_exists($path) === true) {
            if ($transactional === true) {
                if (class_exists('UpdateManager\Client') === true) {
                    $return = true;
                    try {
                        $umc = new \UpdateManager\Client(
                            [
                                'homedir'      => $config['homedir'],
                                'dbconnection' => $this->dbh,
                            ]
                        );

                        // Throws exceptions on error.
                        $umc->updateMR($path);
                    } catch (\Exception $e) {
                        // TODO: Send an event to notify errors.
                        $this->lastError = $e->getMessage();
                        $return = false;
                    }
                } else {
                    // Adapt to PandoraFMS classic way to do things...
                    $backup_dbhost = $config['dbhost'];
                    $backup_dbuser = $config['dbuser'];
                    $backup_dbpass = $config['dbpass'];
                    $backup_dbname = $config['dbname'];
                    $backup_dbport = $config['dbport'];
                    $backup_mysqli = $config['mysqli'];

                    $config['dbhost'] = $this->host;
                    $config['dbuser'] = $this->user;
                    $config['dbpass'] = $this->pass;
                    $config['dbname'] = $this->name;
                    $config['dbport'] = $this->port;

                    // Not using mysqli in > php 7 is a completely non-sense.
                    $config['mysqli'] = true;

                    // MR are loaded in transactions.
                    include_once $config['homedir'].'/include/db/mysql.php';
                    $return = db_run_sql_file($path);
                    if ($return === false) {
                        $this->lastError = $config['db_run_sql_file_error'];
                    }

                    // Revert global variable.
                    $config['dbhost'] = $backup_dbhost;
                    $config['dbuser'] = $backup_dbuser;
                    $config['dbpass'] = $backup_dbpass;
                    $config['dbname'] = $backup_dbname;
                    $config['dbport'] = $backup_dbport;
                    $config['mysqli'] = $backup_mysqli;
                }

                return (bool) $return;
            } else {
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
                                $this->lastError = $this->dbh->errno.': ';
                                $this->lastError .= $this->dbh->error;
                                return false;
                            }

                            $query = '';
                        }
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
