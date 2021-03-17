<?php
/**
 * ModuleStatus entity class.
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

/**
 * PandoraFMS agent entity.
 */
class ModuleStatus extends Entity
{


    /**
     * Builds a PandoraFMS\Module object from given id.
     *
     * @param integer $id_agent_module Module id.
     *
     * @throws \Exception On error.
     */
    public function __construct(?int $id_agent_module=null)
    {
        if (is_numeric($id_agent_module) === true
            && $id_agent_module > 0
        ) {
            try {
                parent::__construct(
                    'tagente_estado',
                    ['id_agente_modulo' => $id_agent_module]
                );
            } catch (\Exception $e) {
                throw new \Exception(
                    __METHOD__.' error: Status not found for module '.$id_agent_module
                );
            }
        } else {
            // Create empty skel.
            parent::__construct('tagente_estado');
        }

    }


    /**
     * Saves current definition to database.
     *
     * @return mixed Affected rows of false in case of error.
     * @throws \Exception On error.
     */
    public function save()
    {
        if ($this->fields['id_agente_modulo'] > 0) {
            // Update.
            $updates = $this->fields;

            $rs = \db_process_sql_update(
                'tagente_estado',
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

            $rs = \db_process_sql_insert(
                'tagente_estado',
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


}
