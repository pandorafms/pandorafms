<?php
/**
 * Widget Top N Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Top N
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

/**
 * Top N Widgets.
 */
class TopNWidget extends Widget
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

        // Includes.
        include_once $config['homedir'].'/include/functions_graph.php';
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
        $this->values = $this->getOptionsWidget();

        // Positions.
        $this->position = $this->getPositionWidget();

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Top N of agent modules');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'top_n';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['agent']) === true) {
            $this->configurationRequired = true;
        }

        $this->overflow_scrollbars = false;
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

        // Default values.
        if (isset($values['quantity']) === false) {
            $values['quantity'] = 5;
        }

        // Default values.
        if (isset($values['period']) === false) {
            $values['period'] = SECONDS_1DAY;
        }

        // Agent.
        $inputs[] = [
            'label'     => __('Agent').ui_print_help_tip(
                __('Case insensitive regular expression for agent name. For example: Network.* will match with the following agent names: network_agent1, NetworK CHECKS'),
                true
            ),
            'arguments' => [
                'name'   => 'agent',
                'type'   => 'text',
                'value'  => $values['agent'],
                'return' => true,
                'size'   => 0,
            ],
        ];

        // Module.
        $inputs[] = [
            'label'     => __('Module').ui_print_help_tip(
                __('Case insensitive regular expression or string for module name. For example: .*usage.* will match: cpu_usage, vram usage.'),
                true
            ),
            'arguments' => [
                'name'   => 'module',
                'type'   => 'text',
                'value'  => $values['module'],
                'return' => true,
                'size'   => 0,
            ],
        ];

        // Period.
        $inputs[] = [
            'label'     => __('Interval'),
            'arguments' => [
                'name'          => 'period',
                'type'          => 'interval',
                'value'         => $values['period'],
                'nothing'       => __('None'),
                'nothing_value' => 0,
                'style_icon'    => 'flex-grow: 0',
            ],
        ];

        // Quantity (n).
        $inputs[] = [
            'label'     => __('Quantity (n)'),
            'arguments' => [
                'name'   => 'quantity',
                'type'   => 'number',
                'value'  => $values['quantity'],
                'return' => true,
                'min'    => 0,
            ],
        ];

        // Order.
        $fields = [
            1 => __('Descending'),
            2 => __('Ascending'),
            3 => __('By agent name'),
        ];

        $inputs[] = [
            'label'     => __('Order'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'order',
                'selected' => $values['order'],
                'return'   => true,
                'sort'     => false,
            ],
        ];

        // Display.
        $fields = [
            REPORT_TOP_N_AVG => __('Avg.'),
            REPORT_TOP_N_MAX => __('Max.'),
            REPORT_TOP_N_MIN => __('Min.'),
        ];

        $inputs[] = [
            'label'     => __('Display'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'display',
                'selected' => $values['display'],
                'return'   => true,
                'sort'     => false,
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

        $values['agent'] = \get_parameter('agent', '');
        $values['module'] = \get_parameter('module', '');
        $values['period'] = \get_parameter('period', 0);
        $values['quantity'] = \get_parameter('quantity', 5);
        $values['order'] = \get_parameter('order', 1);
        $values['display'] = \get_parameter('display', REPORT_TOP_N_AVG);

        return $values;
    }


    /**
     * Draw widget.
     *
     * @return string
     */
    public function load()
    {
        global $config;

        $output = '';

        $size = parent::getSize();

        $quantity = $this->values['quantity'];
        $period = $this->values['period'];

        switch ($this->values['display']) {
            case 1:
                $display = 'max';
            break;

            case 2:
                $display = 'min';
            break;

            default:
            case 0:
                $display = 'avg';
            break;
        }

        switch ($this->values['order']) {
            case 2:
                $order = $display.' DESC';
            break;

            case 3:
                $order = 'alias ASC';
            break;

            default:
            case 1:
                $order = $display.' ASC';
            break;
        }

        $agentRegex = $this->values['agent'];

        $moduleRegex = '';
        if (empty($this->values['module']) === false) {
            $moduleRegex = sprintf(
                "AND tam.nombre REGEXP '%s'",
                $this->values['module']
            );
        }

        // This function check ACL.
        $agents = @agents_get_group_agents(0, ['aliasRegex' => $agentRegex]);
        $agentsId = \array_keys($agents);
        $agentsIdString = \implode(',', $agentsId);

        // Initialize variables.
        $date = \get_system_time();
        $datelimit = ($date - $period);
        $search_in_history_db = db_search_in_history_db($datelimit);

        $sql = \sprintf(
            'SELECT tam.id_agente_modulo as id_module,
                tam.id_agente as id_agent,
                ta.alias as aliasAgent,
                tam.id_tipo_modulo as type_module,
                tam.nombre as nameModule,
                tam.unit as unit,
                MIN(tad.datos) as `min`,
                MAX(tad.datos) as `max`,
                AVG(tad.datos) as `avg`
            FROM tagente_modulo tam
            INNER JOIN tagente ta
                ON ta.id_agente = tam.id_agente
            LEFT JOIN tagente_datos tad
                ON tam.id_agente_modulo = tad.id_agente_modulo
            WHERE tam.id_agente IN (%s)
                %s
                AND tad.utimestamp > %d
                AND tad.utimestamp < %d
            GROUP BY tad.id_agente_modulo
            ORDER BY %s
            LIMIT %d',
            $agentsIdString,
            $moduleRegex,
            $datelimit,
            $date,
            $order,
            $quantity
        );

        $modules = @db_get_all_rows_sql(
            $sql,
            $search_in_history_db
        );

        if (empty($modules) === true) {
            $output .= '<div class="container-center">';
            $output .= \ui_print_info_message(
                __('There are no agents/modules found matching filter set'),
                '',
                true
            );
            $output .= '</div>';
            return $output;
        }

        $data_hbar = [];
        foreach ($modules as $module) {
            $item_name = '';
            $item_name = $module['aliasAgent'].' - '.$module['nameModule'];
            $data_hbar[$item_name]['g'] = $module[$display];
        }

        $height = (count($data_hbar) * 25 + 35);
        $output .= '<div class="container-center">';
        $output .= hbar_graph(
            array_reverse($data_hbar),
            $size['width'],
            $height,
            [],
            [],
            '',
            '',
            '',
            '',
            $config['homedir'].'/images/logo_vertical_water.png',
            $config['fontpath'],
            $config['font_size'],
            true,
            1,
            $config['homeurl'],
            'white',
            'black'
        );
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
        return __('Top N of agent modules');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'top_n';
    }


}
