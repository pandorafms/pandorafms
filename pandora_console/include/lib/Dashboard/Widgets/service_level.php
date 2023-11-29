<?php
/**
 * Widget Service Level Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Service Level
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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
 * Service Level Widgets
 */
class ServiceLevelWidget extends Widget
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
        $this->title = __('Service Level Detail');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'service_level';
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

        if (isset($decoder['interval']) === true) {
            $values['interval'] = $decoder['interval'];
        } else {
            $values['interval'] = '28800';
        }

        if (isset($decoder['show_agents']) === true) {
            $values['show_agents'] = $decoder['show_agents'];
        } else {
            $values['show_agents'] = '0';
        }

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

        // Interval.
        $fields = [
            '604800' => __('1 week'),
            '172800' => __('48 hours'),
            '86400'  => __('24 hours'),
            '43200'  => __('12 hours'),
            '28800'  => __('8 hours'),

        ];

        $inputs[] = [
            'label'     => __('Interval'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'interval-'.$this->cellId,
                'selected' => $values['interval'],
                'return'   => true,
            ],
        ];

        // Show agent.
        $inputs[] = [
            'label'     => __('Show agents'),
            'arguments' => [
                'type'   => 'switch',
                'name'   => 'show_agents-'.$this->cellId,
                'value'  => $values['show_agents'],
                'return' => true,
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

        if (is_metaconsole() === true) {
            $this->values['mAgents'] = $this->getIdCacheAgent($this->values['mAgents']);
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

        $values['interval'] = \get_parameter('interval-'.$this->cellId, '28800');

        $values['show_agents'] = \get_parameter('show_agents-'.$this->cellId, '0');

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
        if (is_metaconsole() === true) {
            $values['mAgents'] = $this->getRealIdAgentNode($values['mAgents']);
        }

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

        $interval_range = [];
        $current_timestamp = time();
        $interval_range['start'] = ($current_timestamp - $this->values['interval']);
        $interval_range['end'] = $current_timestamp;

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
                if (is_metaconsole() === true && str_contains($agent_id, '|') === true) {
                    $server_agent = explode('|', $agent_id);
                } else {
                    $id_agente = $agent_id;
                }

                if ((bool) is_metaconsole() === true) {
                    if (isset($server_agent) === true) {
                        $id_agente = $server_agent[1];
                        $tserver = $server_agent[0];
                    } else {
                        $tmeta_agent = db_get_row_filter(
                            'tmetaconsole_agent',
                            [ 'id_agente' => $id_agente ]
                        );
                        $id_agente = $tmeta_agent['id_tagente'];
                        $tserver = $tmeta_agent['id_tmetaconsole_setup'];
                    }

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

                if ((bool) is_metaconsole() === true) {
                    metaconsole_restore_db();
                }

                foreach ($modules as $module) {
                    if ($module === null) {
                        continue;
                    }

                    $data_module_array = $module->toArray();
                    $visualData[$agent_id]['modules'][$module->name()] = [];
                    $last_status = $module->getStatus()->toArray();
                    $visualData[$agent_id]['modules'][$module->name()]['last_status_change'] = $last_status['last_status_change'];

                    // Mean Time Between Failure.
                    // Mean Time To Solution.
                    // Availability.
                    if ((bool) is_metaconsole() === true) {
                        $module_id = $tserver.'|'.$data_module_array['id_agente_modulo'];
                    } else {
                        $module_id = $data_module_array['id_agente_modulo'];
                    }

                    $module_data = service_level_module_data($interval_range['start'], $interval_range['end'], $module_id);
                    $visualData[$agent_id]['modules'][$module->name()]['mtrs'] = ($module_data['mtrs'] !== false) ? human_milliseconds_to_string(($module_data['mtrs'] * 100), 'short') : '-';
                    $visualData[$agent_id]['modules'][$module->name()]['mtbf'] = ($module_data['mtbf'] !== false) ? human_milliseconds_to_string(($module_data['mtbf'] * 100), 'short') : '-';
                    $visualData[$agent_id]['modules'][$module->name()]['availability'] = ($module_data['availability'] !== false) ? $module_data['availability'] : '100';
                    $visualData[$agent_id]['modules'][$module->name()]['critical_events'] = ($module_data['critical_events'] !== false) ? $module_data['critical_events'] : '';
                    $visualData[$agent_id]['modules'][$module->name()]['warning_events'] = ($module_data['warning_events'] !== false) ? $module_data['warning_events'] : '';
                    $visualData[$agent_id]['modules'][$module->name()]['last_status_change'] = ($module_data['last_status_change'] !== false) ? $module_data['last_status_change'] : '';
                    $visualData[$agent_id]['modules'][$module->name()]['agent_alias'] = ($module_data['agent_alias'] !== false) ? $module_data['agent_alias'] : '';
                    $visualData[$agent_id]['modules'][$module->name()]['module_name'] = ($module_data['module_name'] !== false) ? $module_data['module_name'] : '';
                }
            } catch (\Exception $e) {
                echo 'Error: ['.$agent_id.']'.$e->getMessage();
            }
        }

        $table = new \stdClass();

        $table->width = '100%';
        $table->class = 'databox filters filter-table-adv';

        $table->head = [];
        $show_agents = $this->values['show_agents'];
        if ($show_agents === 'on') {
            $table->head[0] = __('Agent / Modules');
        } else {
            $table->head[0] = __('Modules');
        }

        $table->head[1] = __('% Av.');
        $table->head[2] = __('MTBF');
        $table->head[3] = __('MTRS');
        $table->head[4] = __('Crit. Events').ui_print_help_tip(__('Counted only critical events generated automatic by the module'), true);
        $table->head[5] = __('Warn. Events').ui_print_help_tip(__('Counted only warning events generated automatic by the module'), true);
        $table->head[6] = __('Last change');
        $table->data = [];
        $table->cellstyle = [];
        $row = 0;
        foreach ($visualData as $agent_id => $data) {
            foreach ($data['modules'] as $module_name => $module_data) {
                if (isset($module_data) === true) {
                    if ($show_agents === 'on') {
                        $table->data[$row][0] = $module_data['agent_alias'].' / '.$module_data['module_name'];
                        $table->cellstyle[$row][0] = 'text-align:left';
                    } else {
                        $table->data[$row][0] = $module_data['module_name'];
                        $table->cellstyle[$row][0] = 'text-align:left';
                    }

                    $table->data[$row][1] = $module_data['availability'].'%';
                    $table->data[$row][2] = $module_data['mtbf'];
                    $table->data[$row][3] = $module_data['mtrs'];
                    $table->data[$row][4] = $module_data['critical_events'];
                    $table->data[$row][5] = $module_data['warning_events'];
                    $table->data[$row][6] = date(TIME_FORMAT, $module_data['last_status_change']);
                }

                $row++;
            }
        }

        $height = (count($table->data) * 32);
        $style = 'min-width:400px; min-height:'.$height.'px;';
        $output = '<div class="container-top" style="'.$style.'">';
        $output .= html_print_table($table, true);
        $output .= '</div>';

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Service Level Detail');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'service_level';
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
            'height' => 270,
        ];

        return $size;
    }


    /**
     * Return array with the real id agent and server.
     *
     * @param string $id_agents_cache String with the agents cache id.
     *
     * @return string  $agents_servers with the real id agent and server.
     */
    public function getRealIdAgentNode($id_agents_cache)
    {
        $agents_servers = [];
        $target_agents = explode(',', $id_agents_cache);
        foreach ($target_agents as $agent_id) {
            $id_agente = $agent_id;
            $tmeta_agent = db_get_row_filter(
                'tmetaconsole_agent',
                ['id_agente' => $id_agente]
            );

            $id_agente = $tmeta_agent['id_tagente'];
            $tserver = $tmeta_agent['id_tmetaconsole_setup'];
            $agents_servers[] = $tserver.'|'.$id_agente;
        }

        return implode(',', $agents_servers);
    }


    /**
     * Return string with the cache id agent in metaconsole.
     *
     * @param string $id_agents String with the agents and server id.
     *
     * @return string  $cache_id_agents with the cache id agent.
     */
    public function getIdCacheAgent($id_agents)
    {
        $target_agents = explode(',', $id_agents);
        $cache_id_agents = [];
        foreach ($target_agents as $agent_id) {
            if (str_contains($agent_id, '|') === false) {
                $cache_id_agents[] = $agent_id;
                continue;
            }

            $server_agent = explode('|', $agent_id);
            $tmeta_agent = db_get_row_filter(
                'tmetaconsole_agent',
                [
                    'id_tagente'            => $server_agent[1],
                    'id_tmetaconsole_setup' => $server_agent[0],
                ]
            );
            $cache_id_agents[] = $tmeta_agent['id_agente'];
        }

        return implode(',', $cache_id_agents);
    }


}
