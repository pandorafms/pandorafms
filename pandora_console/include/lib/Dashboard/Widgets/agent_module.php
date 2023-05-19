<?php
/**
 * Widget Agent module Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Agent module
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

namespace PandoraFMS\Dashboard;
use PandoraFMS\Agent;
use PandoraFMS\Module;

/**
 * Agent module Widgets.
 */
class AgentModuleWidget extends Widget
{

    /**
     * Name widget.
     *
     * @var string
     */
    protected $name;

    /**
     * Title widget.
     *
     * @var string
     */
    protected $title;

    /**
     * Page widget;
     *
     * @var string
     */
    protected $page;

    /**
     * Class name widget.
     *
     * @var [type]
     */
    protected $className;

    /**
     * Values options for each widget.
     *
     * @var [type]
     */
    protected $values;

    /**
     * Configuration required.
     *
     * @var boolean
     */
    protected $configurationRequired;

    /**
     * Error load widget.
     *
     * @var boolean
     */
    protected $loadError;

    /**
     * Width.
     *
     * @var integer
     */
    protected $width;

    /**
     * Heigth.
     *
     * @var integer
     */
    protected $height;

    /**
     * Grid Width.
     *
     * @var integer
     */
    protected $gridWidth;

    /**
     * Cell ID.
     *
     * @var integer
     */
    protected $cellId;


    /**
     * Construct.
     *
     * @param integer      $cellId      Cell ID.
     * @param integer      $dashboardId Dashboard ID.
     * @param integer      $widgetId    Widget ID.
     * @param integer|null $width       New width.
     * @param integer|null $height      New height.
     * @param integer|null $gridWidth   Grid width.
     */
    public function __construct(
        int $cellId,
        int $dashboardId=0,
        int $widgetId=0,
        ?int $width=0,
        ?int $height=0,
        ?int $gridWidth=0
    ) {
        global $config;

        include_once $config['homedir'].'/include/functions_agents.php';
        include_once $config['homedir'].'/include/functions_modules.php';

        // WARNING: Do not edit. This chunk must be in the constructor.
        parent::__construct(
            $cellId,
            $dashboardId,
            $widgetId
        );

        // Width.
        $this->width = $width;

        // Height.
        $this->height = $height;

        // Grid Width.
        $this->gridWidth = $gridWidth;

        // Cell Id.
        $this->cellId = $cellId;

        // Options.
        $this->values = $this->decoders($this->getOptionsWidget());

        // Positions.
        $this->position = $this->getPositionWidget();

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Agent/Module View');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'agent_module';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (isset($this->values['mModules']) === false) {
            $this->configurationRequired = true;
        }

        $this->overflow_scrollbars = false;
    }


    /**
     * Decoders hack for retrocompability.
     *
     * @param array $decoder Values.
     *
     * @return array Returns the values ​​with the correct key.
     */
    public function decoders(array $decoder): array
    {
        $values = [];
        // Retrieve global - common inputs.
        $values = parent::decoders($decoder);

        if (isset($decoder['mTypeShow']) === true) {
            $values['mTypeShow'] = $decoder['mTypeShow'];
        }

        if (isset($decoder['mGroup']) === true) {
            $values['mGroup'] = $decoder['mGroup'];
        }

        if (isset($decoder['mRecursion']) === true) {
            $values['mRecursion'] = $decoder['mRecursion'];
        }

        if (isset($decoder['mModuleGroup']) === true) {
            $values['mModuleGroup'] = $decoder['mModuleGroup'];
        }

        if (isset($decoder['mAgents']) === true) {
            $values['mAgents'] = $decoder['mAgents'];
        }

        if (isset($decoder['mShowCommonModules']) === true) {
            $values['mShowCommonModules'] = $decoder['mShowCommonModules'];
        }

        if (isset($decoder['mModules']) === true) {
            $values['mModules'] = $decoder['mModules'];
        }

        return $values;
    }


    /**
     * Generates inputs for form (specific).
     *
     * @return array Of inputs.
     *
     * @throws Exception On error.
     */
    public function getFormInputs(): array
    {
        $values = $this->values;

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        $inputs[] = [
            'label' => __('Filter modules'),
        ];

        // Type show.
        $show_select = [
            0 => __('Show module status'),
            1 => __('Show module data'),
        ];

        if (empty($this->values['mModules']) === true && empty($this->values['mTypeShow'])) {
            $this->values['mTypeShow'] = 1;
        }

        $inputs[] = [
            'class'     => 'flex flex-row',
            'label'     => __('Information to be shown'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $show_select,
                'name'     => 'filtered-type-show-'.$this->cellId,
                'return'   => true,
                'id'       => 'filtered-type-show-'.$this->cellId,
                'selected' => ($this->values['mTypeShow'] === null) ? 0 : $this->values['mTypeShow'],
            ],
        ];

        $return_all_group = false;

        if (users_can_manage_group_all('RM') || $this->values['mGroup'] == 0) {
            $return_all_group = true;
        }

        $mgroup = '';
        if (isset($this->values['mGroup']) === false) {
            $sql = sprintf(
                'SELECT id_group FROM tdashboard WHERE id = %d',
                $this->dashboardId
            );

            $group_dahsboard = db_get_value_sql($sql);
            if ($group_dahsboard > 0) {
                $mgroup = $group_dahsboard;
            }
        }

        $inputs[] = [
            'class'     => 'flex flex-row',
            'id'        => 'select_multiple_modules_filtered',
            'arguments' => [
                'type'                     => 'select_multiple_modules_filtered',
                'uniqId'                   => $this->cellId,
                'mGroup'                   => (isset($this->values['mGroup']) === true) ? $this->values['mGroup'] : $mgroup,
                'mRecursion'               => (isset($this->values['mRecursion']) === true) ? $this->values['mRecursion'] : '',
                'mModuleGroup'             => (isset($this->values['mModuleGroup']) === true) ? $this->values['mModuleGroup'] : '',
                'mAgents'                  => (isset($this->values['mAgents']) === true) ? $this->values['mAgents'] : '',
                'mShowCommonModules'       => (isset($this->values['mShowCommonModules']) === true) ? $this->values['mShowCommonModules'] : '',
                'mModules'                 => (isset($this->values['mModules']) === true) ? $this->values['mModules'] : '',
                'mShowSelectedOtherGroups' => true,
                'mReturnAllGroup'          => $return_all_group,
                'mMetaFields'              => ((bool) is_metaconsole()),
                'commonModulesSwitch'      => true,
            ],
        ];

        return $inputs;
    }


    /**
     * Get Post for widget.
     *
     * @return array
     */
    public function getPost():array
    {
        // Retrieve global - common inputs.
        $values = parent::getPost();

        $values['mTypeShow'] = \get_parameter(
            'filtered-type-show-'.$this->cellId
        );

        $values['mGroup'] = \get_parameter(
            'filtered-module-group-'.$this->cellId
        );
        $values['mRecursion'] = \get_parameter_switch(
            'filtered-module-recursion-'.$this->cellId
        );
        $values['mModuleGroup'] = \get_parameter(
            'filtered-module-module-group-'.$this->cellId
        );
        $values['mAgents'] = \get_parameter(
            'filtered-module-agents-'.$this->cellId
        );
        $values['mShowCommonModules'] = \get_parameter(
            'filtered-module-show-common-modules-'.$this->cellId
        );
        $values['mModules'] = explode(
            ',',
            \get_parameter(
                'filtered-module-modules-'.$this->cellId
            )
        );

        return $values;
    }


    /**
     * Data for draw table Agent/Modules.
     *
     * @param array $agents      Agents.
     * @param array $all_modules Modules.
     *
     * @return array
     */
    private function generateDataAgentModule(
        array $agents,
        array $all_modules
    ):array {
        $return = [];
        $cont = 0;
        $name = '';

        foreach ($all_modules as $key => $module) {
            if ($module == $name) {
                $modules_by_name[($cont - 1)]['id'][] = $key;
            } else {
                $name = $module;
                $modules_by_name[$cont]['name'] = $name;
                $modules_by_name[$cont]['id'][] = $key;
                $cont++;
            }
        }

        foreach ($agents as $agent) {
            $row = [];
            $row['agent_status'] = agents_get_status(
                $agent['id_agente'],
                true
            );
            $row['agent_name'] = $agent['nombre'];
            $row['agent_alias'] = $agent['alias'];

            $sql = sprintf(
                'SELECT id_agente_modulo, nombre
                FROM tagente_modulo
                WHERE id_agente = %d',
                $agent['id_agente']
            );

            $agent_modules = db_get_all_rows_sql($sql);

            $agent_modules = array_combine(
                array_column($agent_modules, 'id_agente_modulo'),
                array_column($agent_modules, 'nombre')
            );

            $row['modules'] = [];
            foreach ($modules_by_name as $module) {
                $row['modules'][$module['name']] = null;
                foreach ($module['id'] as $module_id) {
                    if (array_key_exists($module_id, $agent_modules) === true) {
                        $row['modules'][$module['name']] = modules_get_agentmodule_status(
                            $module_id
                        );
                        break;
                    }
                }
            }

            $return[] = $row;
        }

        return $return;
    }


    /**
     * Draw table Agent/Module.
     *
     * @param array $visualData Data for draw.
     * @param array $allModules Data for th draw.
     *
     * @return string Html output.
     */
    private function generateViewAgentModule(
        array $visualData,
        array $allModules
    ):string {
        $style = 'display:flex; width:100%; margin-top: 10px;';
        $table_data = '<div style="'.$style.'">';
        $table_data .= '<table class="info_table transparent" cellpadding="1" cellspacing="0" border="0">';

        if (empty($visualData) === false) {
            $table_data .= '<th>'.__('Agents').' / '.__('Modules').'</th>';

            $array_names = [];

            foreach ($allModules as $module_name) {
                $file_name = ui_print_truncate_text(
                    \io_safe_output($module_name),
                    'module_small',
                    false,
                    true,
                    false,
                    '...'
                );
                $table_data .= '<th class="pdd_10px">'.$file_name.'</th>';
            }

            foreach ($visualData as $row) {
                $table_data .= "<tr class='height_35px'>";
                switch ($row['agent_status']) {
                    case AGENT_STATUS_ALERT_FIRED:
                        $rowcolor = COL_ALERTFIRED;
                    break;

                    case AGENT_STATUS_CRITICAL:
                        $rowcolor = COL_CRITICAL;
                    break;

                    case AGENT_STATUS_WARNING:
                        $rowcolor = COL_WARNING;
                    break;

                    case AGENT_STATUS_NORMAL:
                        $rowcolor = COL_NORMAL;
                    break;

                    case AGENT_STATUS_UNKNOWN:
                    case AGENT_STATUS_ALL:
                    default:
                        $rowcolor = COL_UNKNOWN;
                    break;
                }

                $file_name = \ui_print_truncate_text(
                    \io_safe_output($row['agent_alias']),
                    'agent_small',
                    false,
                    true,
                    false,
                    '...'
                );
                $table_data .= '<td>';
                $table_data .= '<div class="flex"><div class="div-state-agent" style="background-color: '.$rowcolor.';"></div>';
                $table_data .= $file_name;
                $table_data .= '</div>';
                $table_data .= '</td>';

                if ($row['modules'] === null) {
                    $row['modules'] = [];
                }

                foreach ($row['modules'] as $module_name => $module) {
                    if ($this->values['mTypeShow'] === '1') {
                        $style = 'text-align: left;';
                        $style .= ' background-color: transparent;';
                        $table_data .= "<td style='".$style."'>";
                        $table_data .= $module;
                        $table_data .= '</td>';
                    } else {
                        if ($module === null) {
                            if (in_array($module_name, $allModules) === true) {
                                $style = 'background-color: transparent;';
                                $table_data .= "<td style='".$style."'>";
                                $table_data .= '</td>';
                            } else {
                                continue;
                            }
                        } else {
                            $style = 'text-align: left;';
                            $style .= ' background-color: transparent;';
                            $table_data .= "<td style='".$style."'>";
                            switch ($module) {
                                case AGENT_STATUS_NORMAL:
                                    $table_data .= \ui_print_status_image(
                                        'module_ok.png',
                                        __(
                                            '%s in %s : NORMAL',
                                            $module_name,
                                            $row['agent_alias']
                                        ),
                                        true,
                                        [
                                            'width'  => '20px',
                                            'height' => '20px',
                                        ]
                                    );
                                break;

                                case AGENT_STATUS_CRITICAL:
                                    $table_data .= \ui_print_status_image(
                                        'module_critical.png',
                                        __(
                                            '%s in %s : CRITICAL',
                                            $module_name,
                                            $row['agent_alias']
                                        ),
                                        true,
                                        [
                                            'width'  => '20px',
                                            'height' => '20px',
                                        ]
                                    );
                                break;

                                case AGENT_STATUS_WARNING:
                                    $table_data .= \ui_print_status_image(
                                        'module_warning.png',
                                        __(
                                            '%s in %s : WARNING',
                                            $module_name,
                                            $row['agent_alias']
                                        ),
                                        true,
                                        [
                                            'width'  => '20px',
                                            'height' => '20px',
                                        ]
                                    );
                                break;

                                case AGENT_STATUS_UNKNOWN:
                                    $table_data .= \ui_print_status_image(
                                        'module_unknown.png',
                                        __(
                                            '%s in %s : UNKNOWN',
                                            $module_name,
                                            $row['agent_alias']
                                        ),
                                        true,
                                        [
                                            'width'  => '20px',
                                            'height' => '20px',
                                        ]
                                    );
                                break;

                                case 4:
                                    $table_data .= \ui_print_status_image(
                                        'module_no_data.png',
                                        __(
                                            '%s in %s : Not initialize',
                                            $module_name,
                                            $row['agent_alias']
                                        ),
                                        true,
                                        [
                                            'width'  => '20px',
                                            'height' => '20px',
                                        ]
                                    );
                                break;

                                case AGENT_STATUS_ALERT_FIRED:
                                default:
                                    $table_data .= \ui_print_status_image(
                                        'module_alertsfired.png',
                                        __(
                                            '%s in %s : ALERTS FIRED',
                                            $module_name,
                                            $row['agent_alias']
                                        ),
                                        true,
                                        [
                                            'width'  => '20px',
                                            'height' => '20px',
                                        ]
                                    );
                                break;
                            }

                            $table_data .= '</td>';
                        }
                    }
                }

                $table_data .= '</tr>';
            }
        } else {
            $table_data .= '<tr><td>';
            $table_data .= __(
                'Please configure this widget before usage'
            );
            $table_data .= '</td></tr>';
        }

        $table_data .= '</table>';
        $table_data .= '</div>';

        return $table_data;
    }


    /**
     * Draw widget.
     *
     * @return string;
     */
    public function load()
    {
        global $config;

        $output = '';
        if (check_acl($config['id_user'], 0, 'AR') === 0) {
            $output .= '<div class="container-center">';
            $output .= ui_print_error_message(
                __('You don\'t have access'),
                '',
                true
            );
            $output .= '</div>';
            return $output;
        }

        $reduceAllModules = array_reduce(
            $this->values['mModules'],
            function ($carry, $item) {
                if ($item === null) {
                    return $carry;
                }

                if (is_metaconsole() === true) {
                    $item = explode('|', $item);
                    $serverId = $item[0];
                    $fullname = $item[1];
                    if ($this->values['mShowCommonModules'] !== 'on') {
                        $item = explode('&#x20;&raquo;&#x20;', $fullname);
                        $name = $item[1];
                        $carry['modules_selected'][$serverId][$name] = null;
                        $carry['modules'][$name] = null;
                    } else {
                        $carry['modules'][$fullname] = null;
                    }
                } else {
                    $carry['modules'][$item] = null;
                }

                return $carry;
            }
        );

        $allModules = $reduceAllModules['modules'];
        $visualData = [];
        // Extract info agents selected.
        $target_agents = explode(',', $this->values['mAgents']);
        foreach ($target_agents as $agent_id) {
            try {
                $id_agente = $agent_id;
                if ((bool) is_metaconsole() === true) {
                    $tmeta_agent = db_get_row_filter(
                        'tmetaconsole_agent',
                        [ 'id_agente' => $id_agente ]
                    );

                    $id_agente = $tmeta_agent['id_tagente'];
                    $tserver = $tmeta_agent['id_tmetaconsole_setup'];

                    if (metaconsole_connect(null, $tserver) !== NOERR) {
                        continue;
                    }
                }

                $agent = new Agent((int) $id_agente);
                $visualData[$agent_id]['agent_status'] = $agent->lastStatus();
                $visualData[$agent_id]['agent_name'] = $agent->name();
                $visualData[$agent_id]['agent_alias'] = $agent->alias();
                $visualData[$agent_id]['modules'] = [];

                if (empty($allModules) === false) {
                    if (is_metaconsole() === true && $this->values['mShowCommonModules'] !== 'on') {
                        if (isset($reduceAllModules['modules_selected'][$tserver]) === true) {
                            $modules = $agent->searchModules(
                                ['nombre' => array_keys($reduceAllModules['modules_selected'][$tserver])]
                            );
                        } else {
                            $modules = null;
                        }
                    } else {
                        $modules = $agent->searchModules(
                            ['nombre' => array_keys($allModules)]
                        );
                    }
                }

                $visualData[$agent_id]['modules'] = $allModules;
                foreach ($modules as $module) {
                    if ($module === null) {
                        continue;
                    }

                    $key_name_module = $module->name();

                    if ($this->values['mTypeShow'] === '1') {
                        $mod = $module->toArray();
                        $mod['datos'] = $module->lastValue();
                        $module_last_value = modules_get_agentmodule_data_for_humans($mod);
                        $visualData[$agent_id]['modules'][$key_name_module] = $module_last_value;
                    } else {
                        $visualData[$agent_id]['modules'][$key_name_module] = $module->getStatus()->estado();
                    }
                }

                if ((bool) is_metaconsole() === true) {
                    metaconsole_restore_db();
                }
            } catch (\Exception $e) {
                echo 'Error: ['.$agent_id.']'.$e->getMessage();
            }
        }

        $output = $this->generateViewAgentModule(
            $visualData,
            array_keys($allModules)
        );

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Agent/Module View');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'agent_module';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => 800,
            'height' => 580,
        ];

        return $size;
    }


}
