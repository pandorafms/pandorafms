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

global $config;
require_once $config['homedir'].'/include/functions_events.php';

/**
 * PandoraFMS event entity.
 */
class Event extends Entity
{

    /**
     * Agent related to this event.
     *
     * @var \PandoraFMS\Agent
     */
    private $linkedAgent;

    /**
     * Module related to this event.
     *
     * @var \PandoraFMS\Module
     */
    private $linkedModule;


    /**
     * Builds a PandoraFMS\Event object from given event id.
     *
     * @param integer $event_id Event Id.
     */
    public function __construct(?int $event_id=null)
    {
        $table = 'tevento';
        if ((bool) \is_metaconsole() === true) {
            $table = 'tmetaconsole_event';
        }

        if ($event_id === 0) {
            parent::__construct($table);
        } else if (is_numeric($event_id) === true) {
            parent::__construct($table, ['id_evento' => $event_id]);
        } else {
            // Empty skel.
            parent::__construct($table);
        }

        if ($this->id_agente() !== null) {
            $this->linkedAgent = new Agent($this->id_agente());
        }

        if ($this->id_agentmodule() !== null) {
            $this->linkedModule = new Module($this->id_agentmodule());
        }

    }


    /**
     * Get/set linked agent.
     *
     * @param Agent|null $agent New agent to link.
     *
     * @return Agent|null Agent or null if set operation.
     */
    public function agent(?Agent $agent=null) : ?Agent
    {
        if ($agent === null) {
            return $this->linkedAgent;
        }

        $this->linkedAgent = $agent;
        $this->id_agentmodule($agent->id_agentmodule());
    }


    /**
     * Get/set linked agent.
     *
     * @param Module|null $module New module to link.
     *
     * @return Module|null module or null if set operation.
     */
    public function module(?Module $module=null) : ?Module
    {
        if ($module === null) {
            return $this->linkedModule;
        }

        $this->linkedModule = $module;
        $this->id_agentmodule($module->id_agentmodule());
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
     * @return array|string|false Found events or SQL query or error.
     * @throws \Exception On error.
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
            && (bool) $config['centralized_management'] === true
        ) {
            throw new \Exception(
                'error, cannot save in centralized management environment.'
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


}
