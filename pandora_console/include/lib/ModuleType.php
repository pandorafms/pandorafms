<?php
// phpcs:disable Squiz.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 * Module Type entity class.
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
class ModuleType extends Entity
{


    /**
     * Builds a PandoraFMS\ModuleType object from given id.
     *
     * @param integer $id_tipo_modulo Id tipo modulo.
     */
    public function __construct(?int $id_tipo_modulo=null)
    {
        if (is_numeric($id_tipo_modulo) === true
            && $id_tipo_modulo > 0
        ) {
            parent::__construct(
                'ttipo_modulo',
                ['id_tipo' => $id_tipo_modulo]
            );
        } else {
            // Create empty skel.
            parent::__construct('ttipo_modulo');
        }
    }


    /**
     * Verifies if module type is local or not.
     * Beware, plugins also use this kind of modules..
     *
     * @return boolean Is a local candidate! or not (false).
     */
    public function is_local_datatype()
    {
        if ((int) $this->id_tipo() === MODULE_TYPE_GENERIC_DATA
            || (int) $this->id_tipo() === MODULE_TYPE_GENERIC_PROC
            || (int) $this->id_tipo() === MODULE_TYPE_GENERIC_DATA_STRING
            || (int) $this->id_tipo() === MODULE_TYPE_GENERIC_DATA_INC
            || (int) $this->id_tipo() === MODULE_TYPE_GENERIC_DATA_INC_ABS
        ) {
            return true;
        }

        return false;
    }


    /**
     * Saves current definition to database.
     *
     * @return void No return.
     * @throws \Exception On error.
     */
    public function save()
    {
        throw new \Exception('Read only component');
    }


}
