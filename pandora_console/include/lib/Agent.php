<?php
// phpcs:disable Squiz.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 * Agent entity class.
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

/**
 * PandoraFMS agent entity.
 */
class Agent extends Entity
{

    /**
     * Agent's modules.
     *
     * @var array
     */
    private $modules = [];

    /**
     * Flag to verify if modules has been loaded.
     *
     * @var boolean
     */
    private $modulesLoaded = false;


    /**
     * Builds a PandoraFMS\Agent object from a agent id.
     *
     * @param integer $id_agent     Agent Id.
     * @param boolean $load_modules Load all modules of this agent. Be careful.
     */
    public function __construct(?int $id_agent=null, ?bool $load_modules=false)
    {
        if (is_numeric($id_agent) === true) {
            parent::__construct('tagente', ['id_agente' => $id_agent]);
            if ($load_modules === true) {
                $rows = \db_get_all_rows_filter(
                    'tagente_modulo',
                    ['id_agente' => $id_agent]
                );

                if (is_array($rows) === true) {
                    foreach ($rows as $row) {
                        $this->modules[] = Module::build($row);
                    }
                }

                $this->modulesLoaded = true;
            }
        } else {
            // Create empty skel.
            parent::__construct('tagente');

            // New agent has no modules.
            $this->modulesLoaded = true;
        }

        // Customize certain fields.
        $this->fields['group'] = new Group($this->fields['id_grupo']);
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
     * Overrides Entity method.
     *
     * @param integer $id_group Target group Id.
     *
     * @return integer|null Group Id or null.
     */
    public function id_grupo(?int $id_group=null)
    {
        if ($id_group === null) {
            return $this->fields['id_grupo'];
        } else {
            $this->fields['id_grupo'] = $id_group;
            $this->fields['group'] = new Group($this->fields['id_grupo']);
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
    public function save(bool $alias_as_name=false)
    {
        if (empty($this->fields['nombre']) === true) {
            if ($alias_as_name === true
                && (empty($this->fields['alias']) === true)
            ) {
                throw new \Exception(
                    get_class($this).' error, nor "alias" nor "nombre" are set'
                );
            } else {
                // Use alias instead.
                $this->fields['nombre'] = $this->fields['alias'];
            }
        }

        if ($this->fields['id_agente'] > 0) {
            // Agent update.
            $updates = $this->fields;

            // Remove shortcuts from values.
            unset($updates['group']);

            $rs = \db_process_sql_update(
                'tagente',
                $updates,
                ['id_agente' => $this->fields['id_agente']]
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }
        } else {
            // Agent creation.
            $updates = $this->fields;

            // Remove shortcuts from values.
            unset($updates['group']);

            // Clean null fields.
            foreach ($updates as $k => $v) {
                if ($v === null) {
                    unset($updates[$k]);
                }
            }

            $rs = \agents_create_agent(
                $updates['nombre'],
                $updates['id_grupo'],
                $updates['intervalo'],
                $updates['direccion'],
                $updates,
                $alias_as_name
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }

            $this->fields['id_agente'] = $rs;
        }

        if ($this->fields['group']->id_grupo() === null) {
            // Customize certain fields.
            $this->fields['group'] = new Group($this->fields['id_grupo']);
        }

        return true;
    }


    /**
     * Creates a module in current agent.
     *
     * @param array $params Module definition (each db field).
     *
     * @return integer Id of new module.
     * @throws \Exception On error.
     */
    public function addModule(array $params)
    {
        $err = __METHOD__.' error: ';

        if (empty($params['nombre']) === true) {
            throw new \Exception(
                $err.' module name is mandatory'
            );
        }

        $params['id_agente'] = $this->fields['id_agente'];

        $id_module = modules_create_agent_module(
            $this->fields['id_agente'],
            $params['nombre'],
            $params
        );

        if ($id_module === false) {
            global $config;
            throw new \Exception(
                $err.$config['dbconnection']->error
            );
        }

        return $id_module;

    }


    /**
     * Search for modules into this agent.
     *
     * @param array $filter Filters.
     *
     * @return PandoraFMS\Module Module found.
     */
    public function searchModules(array $filter)
    {
        $filter['id_agente'] = $this->id_agente();

        if ($this->modulesLoaded === true) {
            // Search in $this->modules.
            $results = [];

            foreach ($this->modules as $module) {
                $found = true;
                foreach ($filter as $field => $value) {
                    if ($module->{$field}() !== $value) {
                        $found = false;
                        break;
                    }
                }

                if ($found === true) {
                    $results[] = $module;
                }
            }

            return $results;
        } else {
            // Search in db.
            return Module::search($filter);
        }

    }


    /**
     * Delete agent from db.
     *
     * @return void
     */
    public function delete()
    {
        // This function also mark modules for deletion.
        \agents_delete_agent(
            $this->fields['id_agente']
        );

        unset($this->fields);
        unset($this->modules);
    }


}
