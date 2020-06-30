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


use PandoraFMS\ModuleType;

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
     * Module type matching id_tipo_modulo.
     *
     * @var PandoraFMS\ModuleType
     */
    private $moduleType;

    /**
     * Configuration data (only local modules).
     *
     * @var string
     */
    private $configurationData;

    /**
     * Configuration data (before updates) (only local modules).
     * Compatibility with classic functions.
     *
     * @var string
     */
    private $configurationDataOld;


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
        $obj->moduleType = new ModuleType($obj->id_tipo_modulo());

        // Include some enterprise dependencies.
        enterprise_include_once('include/functions_config_agents.php');

        // Load configuration data from agent configuration if available.
        $obj->configuration_data(
            \enterprise_hook(
                'config_agents_get_module_from_conf',
                [
                    $obj->id_agente(),
                    \io_safe_output($obj->nombre()),
                ]
            )
        );

        // Classic compat.
        $obj->configurationDataOld = $obj->configurationData;

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
        $this->moduleType = new ModuleType($this->id_tipo_modulo());

        // Include some enterprise dependencies.
        enterprise_include_once('include/functions_config_agents.php');

        // Load configuration data from agent configuration if available.
        $this->configuration_data(
            \enterprise_hook(
                'config_agents_get_module_from_conf',
                [
                    $this->id_agente(),
                    \io_safe_output($this->nombre()),
                ]
            )
        );

        // Backup. Classic compat.
        $this->configurationDataOld = $this->configurationData;
    }


    /**
     * Dynamically call methods in this object.
     *
     * @param string $methodName Name of target method or attribute.
     * @param array  $params     Arguments for target method.
     *
     * @return mixed Return of method.
     * @throws \Exception On error.
     */
    public function __call(string $methodName, ?array $params=null)
    {
        // Prioritize written methods over dynamic ones.
        if (method_exists($this, $methodName) === true) {
            return $this->{$methodName}($params);
        }

        if (array_key_exists($methodName, $this->fields) === true) {
            if (empty($params) === false) {
                if ($this->is_local() === true) {
                    $keyName = $methodName;
                    if ($methodName === 'nombre') {
                        $keyName = 'name';
                    }

                    if ($methodName === 'descripcion') {
                        $keyName = 'description';
                    }

                    if ($methodName === 'post_process') {
                        $keyName = 'postprocess';
                    }

                    if ($methodName === 'max_timeout') {
                        $keyName = 'timeout';
                    }

                    if ($methodName === 'max_retries') {
                        $keyName = 'retries';
                    }

                    if (in_array(
                        'module_'.$keyName,
                        [
                            'module_name',
                            'module_description',
                            'module_type',
                            'module_max',
                            'module_min',
                            'module_postprocess',
                            'module_interval',
                            'module_timeout',
                            'module_retries',
                            'module_min_critical',
                            'module_max_critical',
                            'module_min_warning',
                            'module_max_warning',
                        ]
                    ) === true
                    ) {
                        $this->updateConfigurationData(
                            'module_'.$methodName,
                            $params[0]
                        );
                    }
                }

                $this->fields[$methodName] = $params[0];
                return null;
            } else {
                return $this->fields[$methodName];
            }
        }

        throw new \Exception(
            get_class($this).' error, method '.$methodName.' does not exist'
        );
    }


    /**
     * Sets or retrieves value of id_tipo_modulo (complex).
     *
     * @param integer|null $id_tipo_modulo Id module type.
     *
     * @return PandoraFMS\ModuleType corresponding to this module type.
     * @throws \Exception On error.
     */
    public function moduleType(?int $id_tipo_modulo=null)
    {
        if ($id_tipo_modulo === null) {
            return $this->moduleType;
        }

        if (is_numeric($id_tipo_modulo) === true && $id_tipo_modulo > 0) {
            $this->moduleType = new ModuleType($id_tipo_modulo);
            $this->fields['id_tipo_modulo'] = $this->moduleType->id_tipo();
        } else {
            throw new \Exception('Invalid id_tipo_modulo '.$id_tipo_modulo);
        }
    }


    /**
     * Sets or retrieves value of id_tipo_modulo (complex).
     *
     * @param integer|null $id_tipo_modulo Id module type.
     *
     * @return integer corresponding to this module type.
     * @throws \Exception On error.
     */
    public function id_tipo_modulo(?int $id_tipo_modulo=null)
    {
        if ($id_tipo_modulo === null) {
            return $this->fields['id_tipo_modulo'];
        }

        return $this->moduleType($id_tipo_modulo);
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

        // Include some enterprise dependencies.
        enterprise_include_once('include/functions_config_agents.php');

        $updates = $this->fields;
        $updates['id_tipo_modulo'] = $this->moduleType()->id_tipo();

        if ($this->fields['id_agente_modulo'] > 0) {
            // Update.
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

            // Save configuration data if needed.
            if ($this->configurationData !== null) {
                \enterprise_hook(
                    'config_agents_update_module_in_conf',
                    [
                        $this->id_agente(),
                        $this->configurationDataOld,
                        $this->configurationData,
                    ]
                );
            }
        } else {
            // Creation.
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

            if ($rs === false || $rs < 0) {
                global $config;
                if ($rs === ERR_EXIST) {
                    throw new \Exception(
                        __METHOD__.': '.__(
                            'Module already exists: "%s"',
                            $updates['nombre']
                        )
                    );
                }

                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }

            $this->fields['id_agente_modulo'] = $rs;

            \enterprise_hook(
                'config_agents_add_module_in_conf',
                [
                    $this->id_agente(),
                    $this->configurationData,
                ]
            );
        }

        return true;
    }


    /**
     * Verifies if module is local or not.
     *
     * @return boolean Is local, or not (false).
     */
    public function is_local()
    {
        if ($this->moduleType()->is_local_datatype() === true) {
            if ($this->fields['id_modulo'] === MODULE_DATA) {
                return true;
            }
        }

        return false;
    }


    /**
     * Transforms configuration data into an array.
     *
     * @return array Configuration data in array format.
     */
    protected function configurationDataToArray()
    {
        $rr = explode("\n", $this->configurationData);

        $configuration = [];

        foreach ($rr as $line) {
            if (empty($line) === true) {
                continue;
            }

            if (preg_match('/module_begin/', $line) === 1) {
                continue;
            }

            if (preg_match('/module_end/', $line) === 1) {
                break;
            }

            $_tmp = explode(' ', $line, 2);

            $key = $_tmp[0];
            $value = $_tmp[1];

            $configuration[$key] = $value;
        }

        return $configuration;
    }


    /**
     * Updates remote configuration.
     *
     * @param string $key   Left side (module_XXX).
     * @param string $value Value, could be empty.
     *
     * @return boolean True - configurationData updated, false if not.
     */
    public function updateConfigurationData(string $key, ?string $value=null)
    {
        if ($this->is_local() !== true) {
            return false;
        }

        $cnf = $this->configurationDataToArray();

        $cnf[$key] = $value;

        $str = "module_begin\n";
        foreach ($cnf as $k => $v) {
            $str .= $k.' '.$v."\n";
        }

        $str .= "module_end\n";

        $this->configuration_data($str);

        return true;

    }


    /**
     * Get/set configuration data for current module.
     *
     * @param string|null $conf Configuration data (block).
     *
     * @return mixed Content or void if set.
     */
    public function configuration_data(?string $conf=null)
    {
        if ($conf === null) {
            return $this->configurationData;
        }

        $this->configurationData = $conf;
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
