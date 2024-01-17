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

use PandoraFMS\Enterprise\Metaconsole\Node;

global $config;

/**
 * URL Widgets
 */
class SingleGraphWidget extends Widget
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
        $this->title = __('Agent module graph');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'single_graph';
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

        if (isset($decoder['show_full_legend']) === true) {
            $values['showLegend'] = $decoder['show_full_legend'];
        }

        if (isset($decoder['showLegend']) === true) {
            $values['showLegend'] = $decoder['showLegend'];
        }

        if (isset($decoder['type_mode_graph']) === true) {
            $values['type_mode_graph'] = $decoder['type_mode_graph'];
        }

        if (isset($decoder['projection_switch']) === true) {
            $values['projection_switch'] = $decoder['projection_switch'];
        }

        if (isset($decoder['period_projection']) === true) {
            $values['period_projection'] = $decoder['period_projection'];
        }

        if (isset($decoder['periodicity_chart']) === true) {
            $values['periodicity_chart'] = $decoder['periodicity_chart'];
        }

        if (isset($decoder['period_maximum']) === true) {
            $values['period_maximum'] = $decoder['period_maximum'];
        }

        if (isset($decoder['period_minimum']) === true) {
            $values['period_minimum'] = $decoder['period_minimum'];
        }

        if (isset($decoder['period_average']) === true) {
            $values['period_average'] = $decoder['period_average'];
        }

        if (isset($decoder['period_summatory']) === true) {
            $values['period_summatory'] = $decoder['period_summatory'];
        }

        if (isset($decoder['period_slice_chart']) === true) {
            $values['period_slice_chart'] = $decoder['period_slice_chart'];
        }

        if (isset($decoder['period_mode']) === true) {
            $values['period_mode'] = $decoder['period_mode'];
        }

        if (isset($decoder['color_chart']) === true) {
            $values['color_chart'] = $decoder['color_chart'];
        }

        if (isset($decoder['type_graph']) === true) {
            $values['type_graph'] = $decoder['type_graph'];
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

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        // Default values.
        if (isset($values['period']) === false) {
            $values['period'] = SECONDS_1DAY;
        }

        if (isset($values['period_projection']) === false) {
            $values['period_projection'] = SECONDS_1DAY;
        }

        if (isset($values['showLegend']) === false) {
            $values['showLegend'] = 1;
        }

        if (isset($values['type_graph']) === false) {
            $values['type_graph'] = CUSTOM_GRAPH_AREA;
        }

        if (isset($values['period_maximum']) === false) {
            $values['period_maximum'] = 1;
        }

        if (isset($values['period_minimum']) === false) {
            $values['period_minimum'] = 1;
        }

        if (isset($values['period_average']) === false) {
            $values['period_average'] = 1;
        }

        if (isset($values['period_slice_chart']) === false) {
            $values['period_slice_chart'] = SECONDS_1HOUR;
        }

        if (isset($values['period_mode']) === false) {
            $values['period_mode'] = CUSTOM_GRAPH_VBARS;
        }

        if (empty($values['color_chart']) === true) {
            $values['color_chart'] = $config['graph_color1'];
        }

        $blocks = [
            'row1',
            'row2',
        ];

        $inputs['blocks'] = $blocks;

        foreach ($inputs as $kInput => $vInput) {
            $inputs['inputs']['row1'][] = $vInput;
        }

        $display_periodicity_chart = ($values['periodicity_chart'] === true) ? '' : 'display:none';

        // Autocomplete agents.
        $inputs['inputs']['row1'][] = [
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
        $inputs['inputs']['row1'][] = [
            'label'     => __('Module').ui_print_help_tip(__('Warning, this requires to have data for a mid-term (days/weeks) of the source data, if not, projection will not be reliable.'), true),
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
                'required'       => 1,
            ],
        ];

        // Show legend.
        $inputs['inputs']['row1'][] = [
            'label'     => __('Show legend'),
            'arguments' => [
                'name'  => 'showLegend',
                'id'    => 'showLegend',
                'type'  => 'switch',
                'value' => $values['showLegend'],
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
                'script'        => 'check_period_warning(this, \''.__('Warning').'\', \''.__('Displaying items with extended historical data can have an impact on system performance. We do not recommend that you use intervals longer than 30 days, especially if you combine several of them in a report, dashboard or visual console.').'\')',
                'script_input'  => 'check_period_warning_manual(\'period\', \''.__('Warning').'\', \''.__('Displaying items with extended historical data can have an impact on system performance. We do not recommend that you use intervals longer than 30 days, especially if you combine several of them in a report, dashboard or visual console.').'\')',
            ],
        ];

        $inputs['inputs']['row1'][] = [
            'label'     => __('Sliced mode'),
            'arguments' => [
                'name'    => 'periodicity_chart',
                'id'      => 'periodicity_chart',
                'type'    => 'switch',
                'value'   => $values['periodicity_chart'],
                'onclick' => 'showPeriodicityOptions(this)',
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Chart color'),
            'id'        => 'div_color_chart',
            'style'     => ($values['periodicity_chart'] === true) ? 'display:none' : '',
            'arguments' => [
                'wrapper' => 'div',
                'name'    => 'color_chart',
                'type'    => 'color',
                'value'   => $values['color_chart'],
                'return'  => true,
            ],
        ];

        $options_mode = [
            CUSTOM_GRAPH_AREA => __('Area'),
            CUSTOM_GRAPH_LINE => __('Line'),
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Type graph'),
            'id'        => 'div_type_graph',
            'style'     => ($values['periodicity_chart'] === true) ? 'display:none' : '',
            'arguments' => [
                'type'     => 'select',
                'fields'   => $options_mode,
                'name'     => 'type_graph',
                'selected' => $values['type_graph'],
                'return'   => true,
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('AVG/MAX/MIN'),
            'id'        => 'div_type_mode_graph',
            'style'     => ($values['periodicity_chart'] === true) ? 'display:none' : '',
            'arguments' => [
                'name'  => 'type_mode_graph',
                'id'    => 'type_mode_graph',
                'type'  => 'switch',
                'value' => $values['type_mode_graph'],
            ],
        ];

        // Projection.
        $inputs['inputs']['row2'][] = [
            'label'     => __('Projection Graph'),
            'id'        => 'div_projection_switch',
            'style'     => ($values['periodicity_chart'] === true) ? 'display:none' : '',
            'arguments' => [
                'name'    => 'projection_switch',
                'id'      => 'projection_switch',
                'type'    => 'switch',
                'value'   => $values['projection_switch'],
                'onclick' => 'show_projection_period()',
            ],
        ];

        // Period Projection.
        $display_projection = ($values['projection_switch'] === true) ? '' : 'display:none';
        $inputs['inputs']['row2'][] = [
            'label'     => __('Period Projection'),
            'id'        => 'div_projection_period',
            'style'     => $display_projection,
            'arguments' => [
                'name'         => 'period_projection',
                'type'         => 'interval',
                'value'        => $values['period_projection'],
                'script'       => 'check_period_warning(this, \''.__('Warning').'\', \''.__('Displaying items with extended historical data can have an impact on system performance. We do not recommend that you use intervals longer than 30 days, especially if you combine several of them in a report, dashboard or visual console.').'\')',
                'script_input' => 'check_period_warning_manual(\'period\', \''.__('Warning').'\', \''.__('Displaying items with extended historical data can have an impact on system performance. We do not recommend that you use intervals longer than 30 days, especially if you combine several of them in a report, dashboard or visual console.').'\')',
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Maximum'),
            'id'        => 'div_period_maximum',
            'style'     => $display_periodicity_chart,
            'arguments' => [
                'name'  => 'period_maximum',
                'id'    => 'period_maximum',
                'type'  => 'switch',
                'value' => $values['period_maximum'],
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Minimum'),
            'id'        => 'div_period_minimum',
            'style'     => $display_periodicity_chart,
            'arguments' => [
                'name'  => 'period_minimum',
                'id'    => 'period_minimum',
                'type'  => 'switch',
                'value' => $values['period_minimum'],
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Average'),
            'id'        => 'div_period_average',
            'style'     => $display_periodicity_chart,
            'arguments' => [
                'name'  => 'period_average',
                'id'    => 'period_average',
                'type'  => 'switch',
                'value' => $values['period_average'],
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Summatory'),
            'id'        => 'div_period_summatory',
            'style'     => $display_periodicity_chart,
            'arguments' => [
                'name'  => 'period_summatory',
                'id'    => 'period_summatory',
                'type'  => 'switch',
                'value' => $values['period_summatory'],
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Slice period'),
            'id'        => 'div_period_slice_chart',
            'style'     => $display_periodicity_chart,
            'arguments' => [
                'name'          => 'period_slice_chart',
                'type'          => 'interval',
                'value'         => (string) $values['period_slice_chart'],
                'custom_fields' => [
                    SECONDS_1HOUR  => __('1 hour'),
                    SECONDS_1DAY   => __('1 day'),
                    SECONDS_1WEEK  => __('1 week'),
                    SECONDS_1MONTH => __('1 month'),
                ],
            ],
        ];

        $options_period_mode = [
            CUSTOM_GRAPH_AREA  => __('Area'),
            CUSTOM_GRAPH_LINE  => __('Line'),
            CUSTOM_GRAPH_VBARS => __('Vertical bars'),
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Type chart'),
            'id'        => 'div_period_mode',
            'style'     => $display_periodicity_chart,
            'arguments' => [
                'type'     => 'select',
                'fields'   => $options_period_mode,
                'name'     => 'period_mode',
                'selected' => $values['period_mode'],
                'return'   => true,
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
        global $config;
        // Retrieve global - common inputs.
        $values = parent::getPost();

        $values['agentId'] = \get_parameter('agentId', 0);
        $values['metaconsoleId'] = \get_parameter('metaconsoleId', 0);
        $values['moduleId'] = \get_parameter('moduleId', 0);
        $values['period'] = \get_parameter('period', 0);
        $values['type_graph'] = \get_parameter('type_graph', CUSTOM_GRAPH_AREA);
        $values['showLegend'] = \get_parameter_switch('showLegend');
        $values['type_mode_graph'] = \get_parameter_switch('type_mode_graph');
        $values['projection_switch'] = (boolean) \get_parameter_switch('projection_switch');
        $values['period_projection'] = \get_parameter('period_projection', 0);
        $values['color_chart'] = \get_parameter('color_chart', $config['graph_color1']);

        // Values periodicity chart.
        $values['periodicity_chart'] = (boolean) \get_parameter_switch('periodicity_chart');
        $values['period_maximum'] = (boolean) \get_parameter_switch('period_maximum');
        $values['period_minimum'] = (boolean) \get_parameter_switch('period_minimum');
        $values['period_average'] = (boolean) \get_parameter_switch('period_average');
        $values['period_summatory'] = (boolean) \get_parameter_switch('period_summatory');
        $values['period_slice_chart'] = \get_parameter('period_slice_chart', SECONDS_1HOUR);
        $values['period_mode'] = \get_parameter('period_mode', CUSTOM_GRAPH_VBARS);

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
        $units_name = \modules_get_unit($this->values['moduleId']);

        if (empty(parent::getPeriod()) === false) {
            $this->values['period'] = parent::getPeriod();
        }

        $trickHight = 0;
        if ($this->values['showLegend'] === 1) {
            // Needed for legend.
            $trickHight = 30;
        }

        $output = '<div class="container-center widget-mrgn-0px">';
        if ($this->values['projection_switch'] === true) {
            $params_graphic = [
                'period'             => $this->values['period'],
                'date'               => strtotime(date('Y-m-d H:i:s')),
                'only_image'         => false,
                'height'             => ((int) $size['height'] - $trickHight),
                'landscape'          => $content['landscape'],
                'return_img_base_64' => true,
                'show_legend'        => $this->values['showLegend'],
                'width'              => '100%',
                'height'             => ((int) $size['height'] - $trickHight),
                'title'              => $module_name,
                'unit'               => $units_name,
                'homeurl'            => $config['homeurl'],
                'menu'               => false,
            ];

            $params_combined = [
                'projection' => $this->values['period_projection'],
            ];

            $return['chart'] = graphic_combined_module(
                [$this->values['moduleId']],
                $params_graphic,
                $params_combined
            );
            $output .= $return['chart'];
        } else {
            if ($this->values['showLegend'] === 1 && (bool) $this->values['type_mode_graph'] === true) {
                $trickHight *= 3;
            }

            if (isset($this->values['color_chart']) === false
                || empty($this->values['color_chart']) === true
            ) {
                $this->values['color_chart'] = $config['graph_color1'];
            }

            if (isset($this->values['type_graph']) === false
                || empty($this->values['type_graph']) === true
            ) {
                $this->values['type_graph'] = CUSTOM_GRAPH_AREA;
            }

            $params = [
                'agent_module_id'    => $this->values['moduleId'],
                'width'              => '100%',
                'height'             => ((int) $size['height'] - $trickHight),
                'period'             => $this->values['period'],
                'title'              => $module_name,
                'unit'               => $units_name,
                'homeurl'            => $config['homeurl'],
                'backgroundColor'    => 'transparent',
                'show_legend'        => $this->values['showLegend'],
                'show_title'         => $module_name,
                'menu'               => false,
                'dashboard'          => true,
                'type_graph'         => (int) $this->values['type_graph'],
                'type_mode_graph'    => $this->values['type_mode_graph'],
                'period_maximum'     => $this->values['period_maximum'],
                'period_minimum'     => $this->values['period_minimum'],
                'period_average'     => $this->values['period_average'],
                'period_summatory'   => $this->values['period_summatory'],
                'period_slice_chart' => $this->values['period_slice_chart'],
                'period_mode'        => $this->values['period_mode'],
                'array_colors'       => [
                    [
                        'border' => '#000000',
                        'color'  => $this->values['color_chart'].'80',
                        'alpha'  => 75,
                    ],
                    [
                        'border' => '#000000',
                        'color'  => $this->values['color_chart'],
                        'alpha'  => 50,
                    ],
                    [
                        'border' => '#000000',
                        'color'  => $this->values['color_chart'].'60',
                        'alpha'  => 25,
                    ],
                ],
            ];

            if ((bool) $this->values['periodicity_chart'] === false) {
                $output .= \grafico_modulo_sparse($params);
            } else {
                $params['width'] = null;
                $params['height'] = (int) $size['height'];
                $output .= \graphic_periodicity_module($params);
            }
        }

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
        return __('Agent module graph');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'single_graph';
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
            'height' => 430,
        ];

        return $size;
    }


}
