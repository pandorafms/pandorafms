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

use PandoraFMS\Agent;
use PandoraFMS\ModuleType;

/**
 * PandoraFMS module entity.
 */
class Module extends Entity
{

    const INTERFACE_STATUS = 1;
    const INTERFACE_INOCTETS = 2;
    const INTERFACE_OUTOCTETS = 3;
    const INTERFACE_HC_INOCTETS = 4;
    const INTERFACE_HC_OUTOCTETS = 5;

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
     * @return object|array|null PandoraFMS\Module found if limited, array of Modules
     *                           or null if not found.
     * @throws \Exception On error.
     */
    public static function search(
        array $params,
        ?int $limit=0
    ) {
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
     * @param  array   $data                   Module information.
     * @param  string  $class_str              Class type.
     * @param  boolean $return_deleted_modules Check.
     * @return PandoraFMS\Module Object.
     */
    public static function build(
        array $data=[],
        string $class_str='\PandoraFMS\Module',
        bool $return_deleted_modules=false
    ) {
        $obj = new $class_str();

        // Set values.
        foreach ($data as $k => $v) {
            $obj->{$k}($v);
        }

        if (($obj->nombre() === 'delete_pending'
            || $obj->nombre() === 'pendingdelete')
            && $return_deleted_modules === false
        ) {
            return null;
        }

        // Customize certain fields.
        try {
            $obj->status = new ModuleStatus($obj->id_agente_modulo());
        } catch (\Exception $e) {
            $obj->status = null;
        }

        try {
            $obj->moduleType = new ModuleType($obj->id_tipo_modulo());
        } catch (\Exception $e) {
            $obj->moduleType = null;
        }

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
     * @param integer|null $id_agent_module Module id.
     * @param boolean      $link_agent      Link agent object.
     * @param integer|null $nodeId          Target node (if metaconsole
     *                                      environment).
     *
     * @throws \Exception On error.
     */
    public function __construct(
        ?int $id_agent_module=null,
        bool $link_agent=false,
        ?int $nodeId=null
    ) {
        if (is_numeric($id_agent_module) === true
            && $id_agent_module > 0
        ) {
            if ($nodeId > 0) {
                $this->nodeId = $nodeId;
            }

            try {
                // Connect to node if needed.
                $this->connectNode();

                parent::__construct(
                    'tagente_modulo',
                    ['id_agente_modulo' => $id_agent_module]
                );

                // Restore.
                $this->restoreConnection();
            } catch (\Exception $e) {
                $this->restoreConnection();
                // Forward exception.
                throw $e;
            }

            if ($this->nombre() === 'delete_pending'
                || $this->nombre() === 'pendingdelete'
            ) {
                throw new \Exception('Object is pending to be deleted', 1);
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
            // Connect to node if needed.
            $this->connectNode();

            // Customize certain fields.
            $this->status = new ModuleStatus($this->fields['id_agente_modulo']);

            // Restore.
            $this->restoreConnection();
        } catch (\Exception $e) {
            // Restore.
            $this->restoreConnection();

            $this->status = new Modulestatus();
        }

        // Customize certain fields.
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
     * Return agent object where module is defined.
     *
     * @return PandoraFMS\Agent Where module is defined.
     */
    public function agent()
    {
        if ($this->linkedAgent === null) {
            try {
                // Connect to node if needed.
                $this->connectNode();
                $this->linkedAgent = new Agent($this->id_agente());
                // Connect to node if needed.
                $this->restoreConnection();
            } catch (\Exception $e) {
                // Connect to node if needed.
                $this->restoreConnection();

                // Unexistent agent.
                return null;
            }
        }

        return $this->linkedAgent;
    }


    /**
     * Get/set for disable field, this method also takes in mind the status of
     * assigned agent (if any).
     *
     * @param boolean|null $disabled Used in set operations.
     *
     * @return boolean|null Return disabled status for this module or null if
     *                      set operation.
     */
    public function disabled(?bool $disabled=null)
    {
        if ($disabled === null) {
            if ($this->agent() !== null) {
                return ((bool) $this->fields['disabled'] || (bool) $this->agent()->disabled());
            }

            return ((bool) $this->fields['disabled']);
        }

        $this->fields['disabled'] = $disabled;
        return null;
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

        if (is_array($this->fields) === false) {
            // Element deleted.
            return null;
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

        return parent::__call($methodName, $params);
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
     * Return last status reported by the module.
     *
     * @return mixed Data depending on module type.
     */
    public function lastStatus()
    {
        return $this->status->estado();
    }


    /**
     * Retrieves last status in text format.
     *
     * @return string Status in text format.
     */
    public function lastStatusText()
    {
        switch ($this->lastStatus()) {
            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
            case AGENT_MODULE_STATUS_CRITICAL_BAD:
            return 'critical';

            case AGENT_MODULE_STATUS_WARNING_ALERT:
            case AGENT_MODULE_STATUS_WARNING:
            return 'warning';

            case AGENT_MODULE_STATUS_UNKNOWN:
            return 'unknown';

            case AGENT_MODULE_STATUS_NO_DATA:
            case AGENT_MODULE_STATUS_NOT_INIT:
            return 'not_init';

            case AGENT_MODULE_STATUS_NORMAL_ALERT:
            case AGENT_MODULE_STATUS_NORMAL:
            default:
            return 'ok';
        }
    }


    /**
     * Return the color to image representing last status.
     *
     * @return string Hexadecimal notation color.
     */
    public function lastStatusColor()
    {
        switch ($this->lastStatus()) {
            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
            case AGENT_MODULE_STATUS_CRITICAL_BAD:
            return COL_CRITICAL;

            case AGENT_MODULE_STATUS_WARNING_ALERT:
            case AGENT_MODULE_STATUS_WARNING:
            return COL_WARNING;

            case AGENT_MODULE_STATUS_UNKNOWN:
            return COL_UNKNOWN;

            case AGENT_MODULE_STATUS_NO_DATA:
            case AGENT_MODULE_STATUS_NOT_INIT:
            return COL_NOTINIT;

            case AGENT_MODULE_STATUS_NORMAL_ALERT:
            case AGENT_MODULE_STATUS_NORMAL:
            default:
            return COL_NORMAL;
        }
    }


    /**
     * Return path to image representing last status.
     *
     * @return string Relative URL to image.
     */
    public function lastStatusImage()
    {
        switch ($this->lastStatus()) {
            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
            case AGENT_MODULE_STATUS_CRITICAL_BAD:
            return STATUS_MODULE_CRITICAL_BALL;

            case AGENT_MODULE_STATUS_WARNING_ALERT:
            case AGENT_MODULE_STATUS_WARNING:
            return STATUS_MODULE_WARNING_BALL;

            case AGENT_MODULE_STATUS_UNKNOWN:
            return STATUS_MODULE_UNKNOWN_BALL;

            case AGENT_MODULE_STATUS_NO_DATA:
            case AGENT_MODULE_STATUS_NOT_INIT:
            return STATUS_MODULE_NO_DATA_BALL;

            case AGENT_MODULE_STATUS_NORMAL_ALERT:
            case AGENT_MODULE_STATUS_NORMAL:
            default:
            return STATUS_MODULE_OK_BALL;
        }
    }


    /**
     * Return translated string representing last status of the module.
     *
     * @return string Title.
     */
    public function lastStatusTitle()
    {
        switch ($this->lastStatus()) {
            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
            case AGENT_MODULE_STATUS_CRITICAL_BAD:
            return __('CRITICAL');

            case AGENT_MODULE_STATUS_WARNING_ALERT:
            case AGENT_MODULE_STATUS_WARNING:
            return __('WARNING');

            case AGENT_MODULE_STATUS_UNKNOWN:
            return __('UNKNOWN');

            case AGENT_MODULE_STATUS_NO_DATA:
            case AGENT_MODULE_STATUS_NOT_INIT:
            return __('NO DATA');

            case AGENT_MODULE_STATUS_NORMAL_ALERT:
            case AGENT_MODULE_STATUS_NORMAL:
            default:
            return __('NORMAL');
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

        $old = $this->alertTemplatesAssigned();
        if (in_array($id_alert_template, $old) === false) {
            return (bool) \db_process_sql_insert(
                'talert_template_modules',
                [
                    'id_agent_module'   => $this->id_agente_modulo(),
                    'id_alert_template' => $id_alert_template,
                    'last_reference'    => time(),
                ]
            );
        }

        return false;

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

        if (empty($updates['debug_content']) === false) {
            $updates['debug_content'] = str_replace("'", '"', $updates['debug_content']);
        }

        // In the case of the webserver modules, debug_content special characters must be handled.
        if ($updates['id_tipo_modulo'] >= MODULE_TYPE_WEB_ANALYSIS
            && $updates['id_tipo_modulo'] <= MODULE_TYPE_WEB_CONTENT_STRING
        ) {
            $updates['debug_content'] = io_safe_input($updates['debug_content']);
        }

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
     * Return true if module represents an interface (operStatus, in/outOctets)
     *
     * @return integer > 0 if interface module, 0 if not.
     */
    public function isInterfaceModule():int
    {
        if (strstr($this->name(), '_ifOperStatus') !== false) {
            return self::INTERFACE_STATUS;
        }

        if (strstr($this->name(), '_ifInOctets') !== false) {
            return self::INTERFACE_INOCTETS;
        }

        if (strstr($this->name(), '_ifOutOctets') !== false) {
            return self::INTERFACE_OUTOCTETS;
        }

        if (strstr($this->name(), '_ifHCInOctets') !== false) {
            return self::INTERFACE_HC_INOCTETS;
        }

        if (strstr($this->name(), '_ifHCOutOctets') !== false) {
            return self::INTERFACE_HC_OUTOCTETS;
        }

        return 0;
    }


    /**
     * Return interface name if module represents an interface module.
     *
     * @return string|null Interface name or null.
     */
    public function getInterfaceName():?string
    {
        $label = null;
        switch ($this->isInterfaceModule()) {
            case self::INTERFACE_STATUS:
                $label = '_ifOperStatus';
            break;

            case self::INTERFACE_INOCTETS:
                $label = '_ifInOctets';
            break;

            case self::INTERFACE_OUTOCTETS:
                $label = '_ifOutOctets';
            break;

            case self::INTERFACE_HC_INOCTETS:
                $label = '_ifHCInOctets';
            break;

            case self::INTERFACE_HC_OUTOCTETS:
                $label = '_ifHCOutOctets';
            break;

            default:
                // Not an interface module.
            return null;
        }

        if (preg_match(
            '/^(.*?)'.$label.'$/',
            $this->name(),
            $matches
        ) > 0
        ) {
            return $matches[1];
        }

        return null;
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
     * @param integer|null $id_node   Meta searching node will use this field.
     * @param boolean      $connected Connected to a node.
     *
     * @return integer CPS value.
     * @throws \Exception On error.
     */
    public function calculateCPS(?int $id_node=null, bool $connected=false)
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
        // 3. Impersonated node searching metaconsole.
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
        } else if (is_metaconsole() === true
            // True in impersonated nodes.
            && has_metaconsole() === false
            && empty($id_node) === true
        ) {
            // Impersonated node checking metaconsole.
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
        }

        $cps = 0;

        if (is_array(($direct_parents ?? null)) === false) {
            $direct_parents = [];
        }

        if (is_array(($mc_parents ?? null)) === false) {
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
