<?php
// phpcs:disable Squiz.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 * Module entity class.
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
class Module extends Entity
{

    /**
     * Module status (From tagente_estado).
     *
     * @var PandoraFMS\ModuleStatus
     */
    private $status;


    /**
     * Search a module in db.
     *
     * @param array   $params Search parameters (fields from tagente_modulo).
     * @param integer $limit  Limit results to N rows.
     *
     * @return PandoraFMS\Module found or null if not found.
     * @throws \Exception On error.
     */
    public static function search(array $params, ?int $limit=0)
    {
        if (empty($params) === true) {
            return null;
        }

        $rs = \db_get_all_rows_filter(
            'tagente_modulo',
            $params
        );

        if ($rs === false) {
            return null;
        }

        if (empty($rs) === true) {
            return null;
        }

        if ($limit !== 1) {
            $modules = [];
            $i = 0;
            foreach ($rs as $row) {
                if ($limit > 1 && (++$i) > $limit) {
                    break;
                }

                $modules[] = self::build($row);
            }

            return $modules;
        } else {
            return self::build($rs[0]);
        }
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
     * Creates a module object from given data. Avoid query duplication.
     *
     * @param array $data Module information.
     *
     * @return PandoraFMS\Module Object.
     */
    public static function build(array $data=[])
    {
        $obj = new Module();

        // Set values.
        foreach ($data as $k => $v) {
            $obj->{$k}($v);
        }

        if ($obj->nombre() === 'delete_pending') {
            return null;
        }

        // Customize certain fields.
        $obj->status = new ModuleStatus($obj->id_agente_modulo());

        return $obj;
    }


    /**
     * Builds a PandoraFMS\Module object from given id.
     *
     * @param integer $id_agent_module Module id.
     */
    public function __construct(?int $id_agent_module=null)
    {
        if (is_numeric($id_agent_module) === true
            && $id_agent_module > 0
        ) {
            parent::__construct(
                'tagente_modulo',
                ['id_agente_modulo' => $id_agent_module]
            );
        } else {
            // Create empty skel.
            parent::__construct('tagente_modulo');
        }

        if ($this->nombre() === 'delete_pending') {
            return null;
        }

        // Customize certain fields.
        $this->status = new ModuleStatus($this->fields['id_agente_modulo']);
    }


    /**
     * Returns current status.
     *
     * @return PandoraFMS\ModuleStatus Status of the module.
     */
    public function getStatus()
    {
        return $this->status;
    }


    /**
     * Saves current definition to database.
     *
     * @return mixed Affected rows of false in case of error.
     * @throws \Exception On error.
     */
    public function save()
    {
        if (empty($this->fields['nombre']) === true) {
            throw new \Exception(
                get_class($this).' error, "nombre" is not set'
            );
        }

        if (empty($this->fields['id_agente']) === true) {
            throw new \Exception(
                get_class($this).' error, "id_agente" is not set'
            );
        }

        if ($this->fields['id_agente_modulo'] > 0) {
            // Update.
            $updates = $this->fields;

            $rs = \db_process_sql_update(
                'tagente_modulo',
                $updates,
                ['id_agente_modulo' => $this->fields['id_agente_modulo']]
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }
        } else {
            // Creation.
            $updates = $this->fields;

            // Clean null fields.
            foreach ($updates as $k => $v) {
                if ($v === null) {
                    unset($updates[$k]);
                }
            }

            $rs = \modules_create_agent_module(
                $this->fields['id_agente'],
                $updates['nombre'],
                $updates
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }

            $this->fields['id_agente_modulo'] = $rs;
        }

        return true;
    }


    /**
     * Erases this module.
     *
     * @return void
     */
    public function delete()
    {
        \modules_delete_agent_module(
            $this->id_agente_modulo()
        );

        unset($this->fields);
        unset($this->status);
    }


}
