<?php
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
     * Builds a PandoraFMS\Agent object from a agent id.
     *
     * @param integer $id_agent Agent Id.
     */
    public function __construct(?int $id_agent=null)
    {
        if (is_numeric($id_agent) === true) {
            parent::__construct('tagente', ['id_agente' => $id_agent]);
        } else {
            // Create empty skel.
            parent::__construct('tagente');
        }

        // Customize certain fields.
        $this->fields['group'] = new Group($this->fields['id_grupo']);
    }


    /**
     * Saves current group definition to database.
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
            // Agent exists.
            $updates = $this->fields;

            // Remove shortcuts from values.
            unset($updates['group']);

            $this->fields['id_agente'] = \agents_create_agent(
                $updates['nombre'],
                $updates['id_grupo'],
                $updates['intervalo'],
                $updates['direccion'],
                $updates,
                $alias_as_name
            );
        } else {
            // Agent update.
            $updates = $this->fields;

            // Remove shortcuts from values.
            unset($updates['group']);

            // Clean null fields.
            foreach ($updates as $k => $v) {
                if ($v === null) {
                    unset($updates[$k]);
                }
            }

            $this->fields['id_agente'] = \agents_create_agent(
                $updates['nombre'],
                $updates['id_grupo'],
                $updates['intervalo'],
                $updates['direccion'],
                $updates,
                $alias_as_name
            );
        }

        if ($this->fields['group']->id_grupo() === null) {
            // Customize certain fields.
            $this->fields['group'] = new Group($this->fields['id_grupo']);
        }

        return false;
    }


}
