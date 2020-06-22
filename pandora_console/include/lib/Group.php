<?php
/**
 * Group entity class.
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
 * PandoraFMS Group entity.
 */
class Group extends Entity
{


    /**
     * Builds a PandoraFMS\Group object from a group id.
     *
     * @param integer $id_group  Group Id.
     * @param boolean $recursive Create parents as objects.
     */
    public function __construct(?int $id_group=null, bool $recursive=false)
    {
        if ($id_group === 0) {
            parent::__construct('tgrupo');

            $this->fields['id'] = 0;
            $this->fields['nombre'] = 'All';
        } else if (is_numeric($id_group) === true) {
            parent::__construct('tgrupo', ['id_grupo' => $id_group]);
            if ($recursive === true) {
                // Customize certain fields.
                $this->fields['parent'] = new Group($this->fields['parent']);
            }
        } else {
            // Empty skel.
            parent::__construct('tgrupo');
        }

    }


    /**
     * Saves current group definition to database.
     *
     * @return mixed Affected rows of false in case of error.
     */
    public function save()
    {
        global $config;

        if (isset($config['centralized_management'])
            && $config['centralized_management'] > 0
        ) {
            throw new \Exception(
                get_class($this).' error, cannot be modified in a centralized management environment.'
            );
        }

        if ($this->fields['id_grupo'] > 0) {
            $updates = $this->fields;
            if (is_numeric($updates['parent']) === false) {
                $updates['parent'] = $this->parent()->id_grupo();
            }

            return db_process_sql_update(
                'tgrupo',
                $this->fields,
                ['id_grupo' => $this->fields['id_grupo']]
            );
        }

        return false;
    }


}
