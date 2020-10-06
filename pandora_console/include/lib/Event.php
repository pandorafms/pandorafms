<?php
/**
 * Event entity class.
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

global $config;
require_once $config['homedir'].'/include/functions_events.php';

/**
 * PandoraFMS Group entity.
 */
class Event extends Entity
{

    /**
     * List of available ajax methods.
     *
     * @var array
     */
    private static $ajaxMethods = [];


    /**
     * Builds a PandoraFMS\Group object from a group id.
     *
     * @param integer $id_group Group Id.
     */
    public function __construct(?int $id_group=null)
    {
        $table = 'tevento';
        if ((bool) \is_metaconsole() === true) {
            $table = 'tmetaconsole_event';
        }

        if ($id_group === 0) {
            parent::__construct($table);
        } else if (is_numeric($id_group) === true) {
            parent::__construct($table, ['id_grupo' => $id_group]);
        } else {
            // Empty skel.
            parent::__construct($table);
        }

    }


    /**
     * Retrieves all events matching given filters.
     *
     * @param array   $fields     Fields to retrieve.
     * @param array   $filter     Filter.
     * @param integer $offset     Offset.
     * @param integer $limit      Limit.
     * @param string  $order      Order (asc or desc).
     * @param string  $sort_field Sort field.
     * @param boolean $history    Search history.
     * @param boolean $return_sql Return sql or execute it.
     * @param string  $having     Having.
     *
     * @return array|string|falsse Found events or SQL query or error.
     */
    public static function search(
        array $fields,
        array $filter,
        ?int $offset=null,
        ?int $limit=null,
        ?string $order=null,
        ?string $sort_field=null,
        bool $history=false,
        bool $return_sql=false,
        string $having=''
    ):array {
        return \events_get_all(
            $fields,
            $filter,
            $offset,
            $limit,
            $order,
            $sort_field,
            $history,
            $return_sql,
            $having
        );
    }


    /**
     * Saves current group definition to database.
     *
     * @return mixed Affected rows of false in case of error.
     * @throws \Exception On error.
     */
    public function save()
    {
        global $config;

        if (isset($config['centralized_management']) === true
            && $config['centralized_management'] > 0
        ) {
            throw new \Exception(
                get_class($this).' error, cannot be modified while centralized management environment.'
            );
        }

        if ($this->id_evento === null) {
            // New.
            return db_process_sql_insert(
                'tgrupo',
                $this->fields
            );
        } else if ($this->fields['id_evento'] > 0) {
            // Update.
            return db_process_sql_update(
                'tgrupo',
                $this->fields,
                ['id_evento' => $this->fields['id_evento']]
            );
        }

        return false;
    }


    /**
     * Return error message to target.
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    public static function error(string $msg)
    {
        echo json_encode(['error' => $msg]);
    }


    /**
     * Verifies target method is allowed to be called using AJAX call.
     *
     * @param string $method Method to be invoked via AJAX.
     *
     * @return boolean Available (true), or not (false).
     */
    public static function ajaxMethod(string $method):bool
    {
        return in_array($method, self::$ajaxMethods) === true;
    }


}
