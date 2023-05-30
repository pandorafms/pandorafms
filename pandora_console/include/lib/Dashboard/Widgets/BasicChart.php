<?php
/**
 * Widget Simple graph Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

use PandoraFMS\Enterprise\Metaconsole\Node;

global $config;

/**
 * URL Widgets
 */
class BasicChart extends Widget
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
        $this->title = __('Basic chart');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'BasicChart';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['moduleId']) === true) {
            $this->configurationRequired = true;
        } else {
            try {
                if (is_metaconsole() === true
                    && $this->values['metaconsoleId'] > 0
                ) {
                    $node = new Node($this->values['metaconsoleId']);
                    $node->connect();
                }

                $check_exist = db_get_sql(
                    sprintf(
                        'SELECT id_agente_modulo
                        FROM tagente_modulo
                        WHERE id_agente_modulo = %s
                            AND delete_pending = 0',
                        $this->values['moduleId']
                    )
                );
            } catch (\Exception $e) {
                // Unexistent agent.
                if (is_metaconsole() === true
                    && $this->values['metaconsoleId'] > 0
                ) {
                    $node->disconnect();
                }

                $check_exist = false;
            } finally {
                if (is_metaconsole() === true
                    && $this->values['metaconsoleId'] > 0
                ) {
                    $node->disconnect();
                }
            }

            if ($check_exist === false) {
                $this->loadError = true;
            }
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

        if (isset($decoder['id_agent_'.$this->cellId]) === true) {
            $values['agentId'] = $decoder['id_agent_'.$this->cellId];
        }

        if (isset($decoder['agentId']) === true) {
            $values['agentId'] = $decoder['agentId'];
        }

        if (isset($decoder['metaconsoleId']) === true) {
            $values['metaconsoleId'] = $decoder['metaconsoleId'];
        }

        if (isset($decoder['id_module_'.$this->cellId]) === true) {
            $values['moduleId'] = $decoder['id_module_'.$this->cellId];
        }

        if (isset($decoder['moduleId']) === true) {
            $values['moduleId'] = $decoder['moduleId'];
        }

        if (isset($decoder['period']) === true) {
            $values['period'] = $decoder['period'];
        }

        if (isset($decoder['showLabel']) === true) {
            $values['showLabel'] = $decoder['showLabel'];
        }

        if (isset($decoder['showValue']) === true) {
            $values['showValue'] = $decoder['showValue'];
        }

        if (isset($decoder['sizeLabel']) === true) {
            $values['sizeLabel'] = $decoder['sizeLabel'];
        }

        if (isset($decoder['sizeValue']) === true) {
            $values['sizeValue'] = $decoder['sizeValue'];
        }

        if (isset($decoder['colorLabel']) === true) {
            $values['colorLabel'] = $decoder['colorLabel'];
        }

        if (isset($decoder['colorValue']) === true) {
            $values['colorValue'] = $decoder['colorValue'];
        }

        if (isset($decoder['colorChart']) === true) {
            $values['colorChart'] = $decoder['colorChart'];
        }

        if (isset($decoder['formatData']) === true) {
            $values['formatData'] = $decoder['formatData'];
        }

        if (isset($decoder['label']) === true) {
            $values['label'] = $decoder['label'];
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
        global $config;
        $values = $this->values;

        // Default values.
        if (isset($values['period']) === false) {
            $values['period'] = SECONDS_1DAY;
        }

        if (isset($values['colorChart']) === false) {
            $values['colorChart'] = $config['graph_color1'];
        }

        if (isset($values['showLabel']) === false) {
            $values['showLabel'] = 1;
        }

        if (isset($values['showValue']) === false) {
            $values['showValue'] = 1;
        }

        if (isset($values['sizeLabel']) === false) {
            $values['sizeLabel'] = 20;
        }

        if (isset($values['sizeValue']) === false) {
            $values['sizeValue'] = 20;
        }

        if (isset($values['colorLabel']) === false) {
            $values['colorLabel'] = '#333';
        }

        if (isset($values['colorValue']) === false) {
            $values['colorValue'] = '#333';
        }

        if (isset($values['formatData']) === false) {
            $values['formatData'] = 1;
        }

        if (isset($values['label']) === false) {
            $values['label'] = 'module';
        }

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        $blocks = [
            'row1',
            'row2',
        ];

        $inputs['blocks'] = $blocks;

        foreach ($inputs as $kInput => $vInput) {
            $inputs['inputs']['row1'][] = $vInput;
        }

        // Autocomplete agents.
        $inputs['inputs']['row2'][] = [
            'label'     => __('Agent'),
            'arguments' => [
                'type'               => 'autocomplete_agent',
                'name'               => 'agentAlias',
                'id_agent_hidden'    => $values['agentId'],
                'name_agent_hidden'  => 'agentId',
                'server_id_hidden'   => $values['metaconsoleId'],
                'name_server_hidden' => 'metaconsoleId',
                'return'             => true,
                'module_input'       => true,
                'module_name'        => 'moduleId',
                'module_none'        => false,
                'size'               => 0,
            ],
        ];

        // Autocomplete module.
        $inputs['inputs']['row2'][] = [
            'label'     => __('Module'),
            'arguments' => [
                'type'           => 'autocomplete_module',
                'name'           => 'moduleId',
                'selected'       => $values['moduleId'],
                'return'         => true,
                'sort'           => false,
                'agent_id'       => $values['agentId'],
                'metaconsole_id' => $values['metaconsoleId'],
                'style'          => 'width: inherit;',
                'nothing'        => __('None'),
                'nothing_value'  => 0,
            ],
        ];

        // Period.
        $inputs['inputs']['row1'][] = [
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

        $inputs['inputs']['row2'][] = [
            'label'     => __('Color chart'),
            'arguments' => [
                'wrapper' => 'div',
                'name'    => 'colorChart',
                'type'    => 'color',
                'value'   => $values['colorChart'],
                'return'  => true,
            ],
        ];

        $inputs['inputs']['row1'][] = [
            'label'     => __('Show label'),
            'arguments' => [
                'name'  => 'showLabel',
                'id'    => 'showLabel',
                'type'  => 'switch',
                'value' => $values['showLabel'],
            ],
        ];

        $fields = [
            'module'       => __('Module'),
            'agent'        => __('Agent'),
            'agent_module' => __('Agent / module'),
        ];

        $inputs['inputs']['row1'][] = [
            'label'     => __('Label'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'label',
                'selected' => $values['label'],
                'return'   => true,
            ],
        ];

        $inputs['inputs']['row1'][] = [
            'label'     => __('Label size in px'),
            'arguments' => [
                'name'   => 'sizeLabel',
                'type'   => 'number',
                'value'  => $values['sizeLabel'],
                'return' => true,
                'min'    => 5,
                'max'    => 50,
            ],
        ];

        $inputs['inputs']['row1'][] = [
            'label'     => __('Color label'),
            'arguments' => [
                'wrapper' => 'div',
                'name'    => 'colorLabel',
                'type'    => 'color',
                'value'   => $values['colorLabel'],
                'return'  => true,
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Show Value'),
            'arguments' => [
                'name'  => 'showValue',
                'id'    => 'showValue',
                'type'  => 'switch',
                'value' => $values['showValue'],
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Format'),
            'arguments' => [
                'name'  => 'formatData',
                'id'    => 'formatData',
                'type'  => 'switch',
                'value' => $values['formatData'],
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Value size in px'),
            'arguments' => [
                'name'   => 'sizeValue',
                'type'   => 'number',
                'value'  => $values['sizeValue'],
                'return' => true,
                'min'    => 5,
                'max'    => 50,
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Color value'),
            'arguments' => [
                'wrapper' => 'div',
                'name'    => 'colorValue',
                'type'    => 'color',
                'value'   => $values['colorValue'],
                'return'  => true,
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
        $values['agentId'] = \get_parameter('agentId', 0);
        $values['metaconsoleId'] = \get_parameter('metaconsoleId', 0);
        $values['moduleId'] = \get_parameter('moduleId', 0);
        $values['period'] = \get_parameter('period', 0);
        $values['showLabel'] = \get_parameter_switch('showLabel');
        $values['showValue'] = \get_parameter_switch('showValue');
        $values['sizeLabel'] = \get_parameter('sizeLabel', 0);
        $values['sizeValue'] = \get_parameter('sizeValue', 0);
        $values['colorLabel'] = \get_parameter('colorLabel');
        $values['colorValue'] = \get_parameter('colorValue');
        $values['colorChart'] = \get_parameter('colorChart');
        $values['formatData'] = \get_parameter_switch('formatData');
        $values['label'] = \get_parameter('label');

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

        $size = parent::getSize();

        include_once $config['homedir'].'/include/functions_graph.php';
        include_once $config['homedir'].'/include/functions_agents.php';
        include_once $config['homedir'].'/include/functions_modules.php';

        $module_name = \modules_get_agentmodule_name($this->values['moduleId']);
        $alias = \modules_get_agentmodule_agent_alias($this->values['moduleId']);

        $title = $module_name;
        if ($this->values['label'] === 'agent') {
            $title = $alias;
        } else if ($this->values['label'] === 'agent_module') {
            $title = $alias.'/'.$module_name;
        }

        $units_name = \modules_get_unit($this->values['moduleId']);
        $value = \modules_get_last_value($this->values['moduleId']);
        if (isset($this->values['formatData']) === true
            && (bool) $this->values['formatData'] === true
        ) {
            $value = \format_for_graph(
                $value,
                $config['graph_precision']
            );
        } else {
            $value = \sla_truncate(
                $value,
                $config['graph_precision']
            );
        }

        $color_status = \modules_get_color_status(modules_get_agentmodule_last_status($this->values['moduleId']));
        if ($color_status === COL_NORMAL) {
            $color_status = $this->values['colorValue'];
        }

        $params = [
            'agent_module_id'    => $this->values['moduleId'],
            'period'             => $this->values['period'],
            'show_events'        => false,
            'width'              => '100%',
            'height'             => $size['height'],
            'title'              => $module_name,
            'unit'               => $units_name,
            'only_image'         => false,
            'menu'               => false,
            'vconsole'           => true,
            'return_img_base_64' => false,
            'show_legend'        => false,
            'show_title'         => false,
            'dashboard'          => true,
            'backgroundColor'    => 'transparent',
            // 'server_id'          => $metaconsoleId,
            'basic_chart'        => true,
            'array_colors'       => [
                [
                    'border' => '#000000',
                    'color'  => (isset($this->values['colorChart']) === true) ? $this->values['colorChart'] : $config['graph_color1'],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ],
            ],
        ];

        $graph = \grafico_modulo_sparse($params);
        $output = '<div class="container-center widget-mrgn-0px">';
        if (str_contains($graph, '<img') === false) {
            $output .= '<div class="basic-chart-title">';
            $output .= '<span style="color:'.$this->values['colorLabel'].'; font-size:'.$this->values['sizeLabel'].'px;">';
            $output .= ((bool) $this->values['showLabel'] === true) ? $title : '';
            $output .= '</span>';
            $output .= '<span style="color:'.$color_status.'; font-size:'.$this->values['sizeValue'].'px;">';
            $output .= ((bool) $this->values['showValue'] === true) ? $value : '';
            $output .= '</span>';
            $output .= '</div>';
        }

        $output .= $graph;
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
        return __('Basic chart');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'BasicChart';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => 850,
            'height' => 430,
        ];

        return $size;
    }


}
