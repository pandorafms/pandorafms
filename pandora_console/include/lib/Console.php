<?php
// phpcs:disable Squiz.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 * Console entity class.
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
 * PandoraFMS console entity.
 */
class Console extends Entity
{


    /**
     * Builds a PandoraFMS\Console object from given id.
     *
     * @param integer $id Id console.
     */
    public function __construct(?int $id=null)
    {
        $table = 'tconsole';
        $filter = ['id' => $id];

        $this->existsInDB = false;

        if (is_numeric($id) === true
            && $id > 0
        ) {
            parent::__construct(
                $table,
                $filter,
                null,
                false
            );
            $this->existsInDB = true;
        } else {
            // Create empty skel.
            parent::__construct($table, null);
        }
    }


    /**
     * Saves current definition of a Console to database.
     *
     * @return mixed Affected rows of false in case of error.
     * @throws \Exception On error.
     */
    public function save()
    {
        if ($this->fields['id'] > 0) {
            // Update.
            $updates = $this->fields;

            $rs = \db_process_sql_update(
                $this->table,
                $updates,
                ['id' => $this->fields['id']]
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }
        } else {
            // Creation.
            $inserts = $this->fields;

            // Clean null fields.
            foreach ($inserts as $k => $v) {
                if ($v === null) {
                    unset($inserts[$k]);
                }
            }

            $rs = \db_process_sql_insert(
                $this->table,
                $inserts
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }

            $this->fields['id'] = $rs;
        }

        return true;
    }


    /**
     * Remove this Console.
     *
     * @return void
     */
    public function delete()
    {
        if ($this->existsInDB === true) {
            \db_process_delete_temp(
                $this->table,
                'id',
                $this->fields['id']
            );
        }
    }


}
