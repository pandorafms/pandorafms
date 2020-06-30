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

use PandoraFMS\Agent;

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
     * Agent where module is stored.
     *
     * @var PandoraFMS\Agent
     */
    private $linkedAgent;


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
     * @param integer|null $id_agent_module Module id.
     * @param boolean      $link_agent      Link agent object.
     *
     * @throws \Exception On error.
     */
    public function __construct(
        ?int $id_agent_module=null,
        bool $link_agent=false
    ) {
        if (is_numeric($id_agent_module) === true
            && $id_agent_module > 0
        ) {
            parent::__construct(
                'tagente_modulo',
                ['id_agente_modulo' => $id_agent_module]
            );

            if ($this->nombre() === 'delete_pending') {
                return null;
            }

            if ($link_agent === true) {
                try {
                    $this->linkedAgent = new Agent($this->id_agente());
                } catch (\Exception $e) {
                    // Unexistent agent.
                    throw new \Exception(
                        __METHOD__.__(
                            ' error: Module has no agent assigned.'
                        )
                    );
                }
            }
        } else {
            // Create empty skel.
            parent::__construct('tagente_modulo');
        }

        try {
            // Customize certain fields.
            $this->status = new ModuleStatus($this->fields['id_agente_modulo']);
        } catch (\Exception $e) {
            $this->status = new Modulestatus();
        }

    }


    /**
     * Return agent object where module is defined.
     *
     * @return PandoraFMS\Agent Where module is defined.
     */
    public function agent()
    {
        if ($this->linkedAgent === null) {
            try {
                $this->linkedAgent = new Agent($this->id_agente());
            } catch (\Exception $e) {
                // Unexistent agent.
                return null;
            }
        }

        return $this->linkedAgent;
    }


    /**
     * Return last value reported by the module.
     *
     * @return mixed Data depending on module type.
     */
    public function lastValue()
    {
        return $this->status->datos();
    }


    /**
     * Return last status reported by the module.
     *
     * @return mixed Data depending on module type.
     */
    public function lastStatus()
    {
        return $this->status->estado();
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
     * Alias for field 'nombre'.
     *
     * @param string|null $name Name or empty if get operation.
     *
     * @return string|null Name or empty if set operation.
     */
    public function name(?string $name=null)
    {
        if ($name === null) {
            return $this->nombre();
        }

        $this->nombre($name);
    }


    /**
     * Retrieve all alert templates (ids) assigned to current module.
     *
     * @return array Of ids.
     */
    public function alertTemplatesAssigned()
    {
        if ($this->id_agente_modulo() === null) {
            // Need to be stored first.
            return [];
        }

        $result = db_get_all_rows_filter(
            'talert_template_modules',
            ['id_agent_module' => $this->id_agente_modulo()],
            'id_alert_template'
        );

        if ($result === false) {
            return [];
        }

        return array_reduce(
            $result,
            function ($carry, $item) {
                $carry[] = $item['id_alert_template'];
                return $carry;
            },
            []
        );
    }


    /**
     * Remove a alert template assignment.
     *
     * @param integer $id_alert_template Target id.
     *
     * @return boolean Success or not.
     */
    public function unassignAlertTemplate(int $id_alert_template)
    {
        if ($this->id_agente_modulo() === null) {
            // Need to be stored first.
            return false;
        }

        if (is_numeric($id_alert_template) === false
            || $id_alert_template <= 0
        ) {
            // Invalid alert template.
            return false;
        }

        return (bool) \db_process_sql_delete(
            'talert_template_modules',
            [
                'id_agent_module'   => $this->id_agente_modulo(),
                'id_alert_template' => $id_alert_template,
            ]
        );

    }


    /**
     * Add an alert template to this module.
     *
     * @param integer|null $id_alert_template Target alert template.
     *
     * @return boolean Status of adding process.
     */
    public function addAlertTemplate(?int $id_alert_template=null)
    {
        if ($this->id_agente_modulo() === null) {
            // Need to be stored first.
            return false;
        }

        if (is_numeric($id_alert_template) === false
            || $id_alert_template <= 0
        ) {
            // Invalid alert template.
            return false;
        }

        return (bool) \db_process_sql_insert(
            'talert_template_modules',
            [
                'id_agent_module'   => $this->id_agente_modulo(),
                'id_alert_template' => $id_alert_template,
                'last_reference'    => time(),
            ]
        );

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


    /**
     * Transforms results from classic mode into modern exceptions.
     *
     * @param integer|boolean $result Result received from module management.
     *
     * @return integer Module id created or result.
     * @throws \Exception On error.
     */
    public static function errorToException($result)
    {
        if ($result === ERR_INCOMPLETE) {
            throw new \Exception(
                __('Module name empty.')
            );
        }

        if ($result === ERR_GENERIC) {
            throw new \Exception(
                __('Invalid characters in module name')
            );
        }

        if ($result === ERR_EXIST) {
            throw new \Exception(
                __('Module already exists please select another name or agent.')
            );
        }

        if ($result === false) {
            throw new \Exception(
                __('Insufficent permissions to perform this action')
            );
        }

        if ($result === ERR_DB) {
            global $config;
            throw new \Exception(
                __('Error while processing: %s', $config['dbconnection']->error)
            );
        }

        return $result;
    }


    /**
     * Calculates cascade protection service value for this service.
     *
     * @param integer|null $id_node Meta searching node will use this field.
     *
     * @return integer CPS value.
     * @throws \Exception On error.
     */
    public function calculateCPS(?int $id_node=null)
    {
        if ($this->cps() < 0) {
            return $this->cps();
        }

        // 1. check parents.
        $direct_parents = db_get_all_rows_sql(
            sprintf(
                'SELECT id_service, cps, cascade_protection, name
                 FROM `tservice_element` te
                 INNER JOIN `tservice` t ON te.id_service = t.id
                 WHERE te.id_agente_modulo = %d',
                $this->id_agente_modulo()
            )
        );

        // Here could happen 2 things.
        // 1. Metaconsole service is using this method impersonating node DB.
        // 2. Node service is trying to find parents into metaconsole.
        if (empty($id_node) === true
            && is_metaconsole() === false
            && has_metaconsole() === true
        ) {
            // Node searching metaconsole.
            $mc_parents = [];
            global $config;
            $mc_db_conn = \enterprise_hook(
                'metaconsole_load_external_db',
                [
                    [
                        'dbhost' => $config['replication_dbhost'],
                        'dbuser' => $config['replication_dbuser'],
                        'dbpass' => io_output_password(
                            $config['replication_dbpass']
                        ),
                        'dbname' => $config['replication_dbname'],
                    ],
                ]
            );

            if ($mc_db_conn === NOERR) {
                $mc_parents = db_get_all_rows_sql(
                    sprintf(
                        'SELECT id_service,
                                cps,
                                cascade_protection,
                                name
                        FROM `tservice_element` te
                        INNER JOIN `tservice` t ON te.id_service = t.id
                        WHERE te.id_agente_modulo = %d',
                        $this->id_agente_modulo()
                    ),
                    false,
                    false
                );
            }

            // Restore the default connection.
            \enterprise_hook('metaconsole_restore_db');
        } else if ($id_node > 0) {
            // Impersonated node.
            \enterprise_hook('metaconsole_restore_db');

            $mc_parents = db_get_all_rows_sql(
                sprintf(
                    'SELECT id_service,
                            cps,
                            cascade_protection,
                            name
                    FROM `tservice_element` te
                    INNER JOIN `tservice` t ON te.id_service = t.id
                    WHERE te.id_agente_modulo = %d',
                    $this->id_agente_modulo()
                ),
                false,
                false
            );

            // Restore impersonation.
            \enterprise_include_once('include/functions_metaconsole.php');
            $r = \enterprise_hook(
                'metaconsole_connect',
                [
                    null,
                    $id_node,
                ]
            );

            if ($r !== NOERR) {
                throw new \Exception(__('Cannot connect to node %d', $r));
            }
        }

        $cps = 0;

        if (is_array($direct_parents) === false) {
            $direct_parents = [];
        }

        if (is_array($mc_parents) === false) {
            $mc_parents = [];
        }

        // Merge all parents (node and meta).
        $parents = array_merge($direct_parents, $mc_parents);

        foreach ($parents as $parent) {
            $cps += $parent['cps'];
            if (((bool) $parent['cascade_protection']) === true) {
                $cps++;
            }
        }

        return $cps;

    }


}
