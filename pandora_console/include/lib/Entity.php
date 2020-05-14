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
     * Defines a generic constructor to extract information of the object.
     *
     * @param string $table   Table.
     * @param array  $filters Filters, for instance ['id' => $id].
     *
     * @throws \Exception On error.
     */
    public function __construct(string $table, ?array $filters=null)
    {
        if (empty($table) === true) {
            throw new \Exception(
                get_class($this).' error, table name is not defined'
            );
        }

        $this->table = $table;

        if (is_array($filters) === true) {
            // New one.
            $data = \db_get_row_filter($this->table, $filters);

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
    }


    /**
     * Dynamically call methods in this object.
     *
     * @param string $methodName Name of target method or attribute.
     * @param array  $params     Arguments for target method.
     *
     * @return mixed Return of method.
     * @throws \Exception On error.
     */
    public function __call(string $methodName, ?array $params=null)
    {
        // Prioritize written methods over dynamic ones.
        if (method_exists($this, $methodName) === true) {
            return $this->{$methodName}($params);
        }

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


    /**
     * Saves current object definition to database.
     *
     * @return boolean Success or not.
     */
    public abstract function save();


}
