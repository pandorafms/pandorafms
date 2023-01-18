<?php
// phpcs:disable Squiz.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 * Cluster module entity class.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Community
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

use PandoraFMS\Entity;
use PandoraFMS\Module;

/**
 * Represents AA and AP modules entity from a cluster.
 */
class ClusterModule extends Entity
{

    /**
     * Associated module.
     *
     * @var PandoraFMS\Module
     */
    private $module;


    /**
     * Builds a PandoraFMS\ClusterViewer\ClusterModule object from a id.
     *
     * @param integer $id ClusterModule Id.
     *
     * @throws \Exception On error.
     */
    public function __construct(?int $id=null)
    {
        if (is_numeric($id) === true && $id > 0) {
            try {
                parent::__construct('tcluster_item', ['id' => $id]);
            } catch (\Exception $e) {
                throw new \Exception('ClusterModule id not found.');
            }

            // Get module.
            $this->module = Module::search(
                [
                    'nombre'           => $this->name(),
                    'custom_integer_1' => $this->id_cluster(),
                ],
                1
            );
        } else {
            parent::__construct('tcluster_item');

            $this->module = new Module();
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
     * Associates a module to this clusterModule.
     *
     * @param array $params Module parameters.
     *
     * @return void
     */
    public function setModule(array $params)
    {
        $this->module = new Module();
        foreach ($params as $k => $v) {
            $this->module->{$k}($v);
        }
    }


    /**
     * Associates a module to this clusterModule.
     *
     * @param PandoraFMS\Module $module Module definition.
     *
     * @return void
     */
    public function setModuleObject(Module $module)
    {
        $this->module = $module;
    }


    /**
     * Returns current module.
     *
     * @return PandoraFMS\Module Object.
     */
    public function getModule()
    {
        return $this->module;
    }


    /**
     * Saves or retrieves value of warning_limit.
     *
     * @param float|null $value Warning value.
     *
     * @return mixed Value or empty.
     */
    public function warning_limit(?float $value=null)
    {
        if ($value !== null) {
            $this->fields['warning_limit'] = $value;
            if ($this->module !== null) {
                $this->module->min_warning($value);
            }
        } else {
            return $this->fields['warning_limit'];
        }
    }


    /**
     * Saves or retrieves value of critical_limit.
     *
     * @param float|null $value Critical value.
     *
     * @return mixed Value or empty.
     */
    public function critical_limit(?float $value=null)
    {
        if ($value !== null) {
            $this->fields['critical_limit'] = $value;
            if ($this->module !== null) {
                $this->module->min_critical($value);
            }
        } else {
            return $this->fields['critical_limit'];
        }
    }


    /**
     * Save ClusterModule.
     *
     * @return boolean True if success, false if error.
     * @throws \Exception On db error.
     */
    public function save()
    {
        $values = $this->fields;

        if ($this->module === null) {
            return false;
        }

        if (method_exists($this->module, 'save') === false) {
            throw new \Exception(
                __METHOD__.' error: Cluster module "'.$this->name().'" invalid.'
            );
        }

        if (isset($values['id']) === true && $values['id'] > 0) {
            // Update.
            $rs = \db_process_sql_update(
                'tcluster_item',
                $values,
                ['id' => $this->fields['id']]
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }

            if ($this->module === null) {
                throw new \Exception(
                    __METHOD__.' error: Cluster module "'.$this->name().'" is not defined'
                );
            }

            // Update reference.
            $this->module->custom_integer_2($this->fields['id']);

            // Update module.
            $this->module->save();

            return true;
        } else {
            // New.
            $rs = \db_process_sql_insert(
                'tcluster_item',
                $values
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }

            $this->fields['id'] = $rs;

            // Update reference.
            $this->module->custom_integer_2($this->fields['id']);

            // Update module.
            $this->module->save();

            return true;
        }

        return false;
    }


    /**
     * Erases this object and its module.
     *
     * @return void
     */
    public function delete()
    {
        if (method_exists($this->module, 'delete') === true) {
            $this->module->delete();
        }

        unset($this->fields);
        unset($this->module);

    }


}
