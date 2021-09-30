<?php
/**
 * Entity class.
 *
 * @category   Abstract class
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
namespace PandoraFMS;


/**
 * Defines common methods for all PandoraFMS entity objects.
 */
abstract class Entity
{

    /**
     * Entity fields (from table).
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Target table.
     *
     * @var string
     */
    protected $table = '';

    /**
     * Enterprise capabilities object.
     *
     * @var object
     */
    private $enterprise;

    /**
     * MC Node id.
     *
     * @var integer|null
     */
    protected $nodeId = null;

    /**
     * Connected to external node.
     *
     * @var boolean
     */
    private $connected = false;


    /**
     * Instances a new object using array definition.
     *
     * @param array  $data      Fields data.
     * @param string $class_str Class name.
     *
     * @return object With current definition.
     */
    public static function build(array $data=[], string $class_str=__CLASS__)
    {
        $obj = new $class_str();
        // Set values.
        foreach ($data as $k => $v) {
            $obj->{$k}($v);
        }

        return $obj;
    }


    /**
     * Defines a generic constructor to extract information of the object.
     *
     * @param string      $table            Table.
     * @param array|null  $filters          Filters, for instance ['id' => $id].
     * @param string|null $enterprise_class Enterprise class name.
     * @param boolean     $cache            Use cache or not.
     *
     * @throws \Exception On error.
     */
    public function __construct(
        string $table,
        ?array $filters=null,
        ?string $enterprise_class=null,
        bool $cache=true
    ) {
        if (empty($table) === true) {
            throw new \Exception(
                get_class($this).' error, table name is not defined'
            );
        }

        $this->table = $table;

        if (is_array($filters) === true) {
            // New one.
            $data = \db_get_row_filter(
                $this->table,
                $filters,
                false,
                'AND',
                false,
                $cache
            );

            if ($data === false) {
                throw new \Exception(
                    get_class($this).' error, entity not found'
                );
            }

            // Map fields.
            foreach ($data as $k => $v) {
                $this->fields[$k] = $v;
            }
        } else {
            // Empty one.
            $data = \db_get_all_rows_sql(
                sprintf(
                    'SHOW COLUMNS FROM %s',
                    $this->table
                )
            );

            foreach ($data as $row) {
                $this->fields[$row['Field']] = null;
            }
        }

        if (\enterprise_installed() === true
            && $enterprise_class !== null
        ) {
            $this->enterprise = new $enterprise_class($this);
        }
    }


    /**
     * Dynamically call methods in this object.
     *
     * To dynamically switch between community methods and prioritize
     * enterprise ones, define method visibility as 'protected' in both
     * classes.
     *
     * For instance, in following situation:
     *  protected PandoraFMS\Agent::test()
     *  protected PandoraFMS\Enterprise\Agent::test()
     *
     * If enterprise is available, then PandoraFMS\Enterprise\Agent::test()
     * will be executed, community method otherwise.
     *
     * @param string $methodName Name of target method or attribute.
     * @param array  $params     Arguments for target method.
     *
     * @return mixed Return of method.
     * @throws \Exception On error.
     */
    public function __call(string $methodName, ?array $params=null)
    {
        // Enterprise capabilities.
        // Prioritize enterprise written methods over dynamic fields.
        if (\enterprise_installed() === true
            && $this->enterprise !== null
            && method_exists($this->enterprise, $methodName) === true
        ) {
            return $this->enterprise->$methodName(...$params);
        }

        if (method_exists($this, $methodName) === false) {
            if (array_key_exists($methodName, $this->fields) === true) {
                if (empty($params) === true) {
                    return $this->fields[$methodName];
                } else {
                    $this->fields[$methodName] = $params[0];
                }

                return null;
            }

            throw new \Exception(
                get_class($this).' error, method '.$methodName.' does not exist'
            );
        }

        // Do not return nor throw exceptions after this point, allow php
        // default __call behaviour to continue working with object method
        // defined.
        // If you're receiving NULL as result of the method invocation, ensure
        // it is not private, take in mind this method will mask any access
        // level error or notification since it is public and has limited access
        // to the object (public|protected).
    }


    /**
     * Returns current object as array.
     *
     * @return array Of fields.
     */
    public function toArray()
    {
        return $this->fields;
    }


    /**
     * Connects to current nodeId target.
     * If no nodeId is defined, then returns without doing anything.
     *
     * @return void
     * @throws \Exception On error.
     */
    public function connectNode()
    {
        if ($this->nodeId === null) {
            return;
        }

        \enterprise_include_once('include/functions_metaconsole.php');
        $r = \enterprise_hook(
            'metaconsole_connect',
            [
                null,
                $this->nodeId,
            ]
        );

        if ($r !== NOERR) {
            throw new \Exception(
                __('Cannot connect to node %d', $this->nodeId)
            );
        }

        $this->connected = true;
    }


    /**
     * Restore connection after connectNode.
     *
     * @return void
     */
    public function restoreConnection()
    {
        if ($this->connected === true) {
            \enterprise_include_once('include/functions_metaconsole.php');
            \enterprise_hook('metaconsole_restore_db');
        }

    }


    /**
     * Saves current object definition to database.
     *
     * @return boolean Success or not.
     */
    public abstract function save();


}
