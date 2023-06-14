<?php
/**
 * Widget Color tabs modules Pandora FMS Console
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
class GroupedMeterGraphs extends Widget
{
    private const STATUS_NORMAL = 'normal';
    private const STATUS_CRITICAL = 'critical';
    private const STATUS_WARNING = 'warning';
    private const RATIO_WITH_BOX = 20.1518;
    private const MAX_MODULES = 20;
    private const MAX_INCREASE = 0.10;

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
     * @var string
     */
    protected $className;

    /**
     * Values options for each widget.
     *
     * @var array
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
     * Size.
     *
     * @var array
     */
    private $size;

    /**
     * Number of boxes.
     *
     * @var float
     */
    private $boxNumber;

    /**
     * Thresholds.
     *
     * @var array
     */
    private $thresholds;


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

        // Include.
        include_once $config['homedir'].'/include/functions_reporting.php';

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
        $this->title = __('Color tabs modules');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'single_graph';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['moduleGroupedMeterGraphs']) === true) {
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

        $values['agentsGroupedMeterGraphs'] = [];
        if (isset($decoder['agentsGroupedMeterGraphs']) === true) {
            if (isset($decoder['agentsGroupedMeterGraphs'][0]) === true
                && empty($decoder['agentsGroupedMeterGraphs']) === false
            ) {
                $values['agentsGroupedMeterGraphs'] = explode(
                    ',',
                    $decoder['agentsGroupedMeterGraphs'][0]
                );
            }
        }

        if (isset($decoder['selectionGroupedMeterGraphs']) === true) {
            $values['selectionGroupedMeterGraphs'] = $decoder['selectionGroupedMeterGraphs'];
        }

        $values['moduleGroupedMeterGraphs'] = [];
        if (isset($decoder['moduleGroupedMeterGraphs']) === true) {
            if (empty($decoder['moduleGroupedMeterGraphs']) === false) {
                $values['moduleGroupedMeterGraphs'] = $decoder['moduleGroupedMeterGraphs'];
            }
        }

        if (isset($decoder['formatData']) === true) {
            $values['formatData'] = $decoder['formatData'];
        }

        if (isset($decoder['manualThresholds']) === true) {
            $values['manualThresholds'] = $decoder['manualThresholds'];
        }

        $values['label'] = 'module';
        if (isset($decoder['label']) === true) {
            $values['label'] = $decoder['label'];
        }

        $values['min_value'] = null;
        if (isset($decoder['min_value']) === true) {
            $values['min_value'] = $decoder['min_value'];
        }

        $values['max_value'] = null;
        if (isset($decoder['max_value']) === true) {
            $values['max_value'] = $decoder['max_value'];
        }

        $values['min_critical'] = null;
        if (isset($decoder['min_critical']) === true) {
            $values['min_critical'] = $decoder['min_critical'];
        }

        $values['max_critical'] = null;
        if (isset($decoder['max_critical']) === true) {
            $values['max_critical'] = $decoder['max_critical'];
        }

        $values['min_warning'] = null;
        if (isset($decoder['min_warning']) === true) {
            $values['min_warning'] = $decoder['min_warning'];
        }

        $values['max_warning'] = null;
        if (isset($decoder['max_warning']) === true) {
            $values['max_warning'] = $decoder['max_warning'];
        }

        if (isset($decoder['fontColor']) === true) {
            $values['fontColor'] = $decoder['fontColor'];
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

        $blocks = [
            'row1',
            'row2',
        ];

        $inputs['blocks'] = $blocks;

        foreach ($inputs as $kInput => $vInput) {
            $inputs['inputs']['row1'][] = $vInput;
        }

        if (empty($values['fontColor']) === true) {
            $values['fontColor'] = '#2c3e50';
        }

        $inputs['inputs']['row1'][] = [
            'label'     => __('Font color'),
            'arguments' => [
                'wrapper' => 'div',
                'name'    => 'fontColor',
                'type'    => 'color',
                'value'   => $values['fontColor'],
                'return'  => true,
            ],
        ];

        // Format Data.
        $inputs['inputs']['row1'][] = [
            'label'     => __('Format Data'),
            'arguments' => [
                'name'  => 'formatData',
                'id'    => 'formatData',
                'type'  => 'switch',
                'value' => $values['formatData'],
            ],
        ];

        $inputs['inputs']['row1'][] = [
            'class'         => 'dashboard-input-threshold',
            'direct'        => 1,
            'block_content' => [
                [
                    'label'     => __('Values'),
                    'arguments' => [],
                ],
                [
                    'label'     => __('Min'),
                    'arguments' => [
                        'name'  => 'min_value',
                        'id'    => 'min_value',
                        'type'  => 'number',
                        'value' => $values['min_value'],
                    ],
                ],
                [
                    'label'     => __('Max'),
                    'arguments' => [
                        'name'  => 'max_value',
                        'id'    => 'max_value',
                        'type'  => 'number',
                        'value' => $values['max_value'],
                    ],
                ],
            ],

        ];

        // Format Data.
        $inputs['inputs']['row1'][] = [
            'label'     => __('Manual thresholds'),
            'arguments' => [
                'name'     => 'manualThresholds',
                'id'       => 'manualThresholds',
                'type'     => 'switch',
                'value'    => $values['manualThresholds'],
                'onchange' => 'showManualThresholds(this)',
            ],
        ];

        $class_invisible = '';
        if ((bool) $values['manualThresholds'] !== true) {
            $class_invisible = 'invisible_important';
        }

        $inputs['inputs']['row1'][] = [
            'class'         => 'dashboard-input-threshold dashboard-input-threshold-warning '.$class_invisible,
            'direct'        => 1,
            'block_content' => [
                [
                    'label'     => __('Warning threshold'),
                    'arguments' => [],
                ],
                [
                    'label'     => __('Min'),
                    'arguments' => [
                        'name'  => 'min_warning',
                        'id'    => 'min_warning',
                        'type'  => 'number',
                        'value' => $values['min_warning'],
                    ],
                ],
                [
                    'label'     => __('Max'),
                    'arguments' => [
                        'name'  => 'max_warning',
                        'id'    => 'max_warning',
                        'type'  => 'number',
                        'value' => $values['max_warning'],
                    ],
                ],
            ],
        ];

        $inputs['inputs']['row1'][] = [
            'class'         => 'dashboard-input-threshold dashboard-input-threshold-critical '.$class_invisible,
            'direct'        => 1,
            'block_content' => [
                [
                    'label'     => __('Critical threshold'),
                    'arguments' => [],
                ],
                [
                    'label'     => __('Min'),
                    'arguments' => [
                        'name'  => 'min_critical',
                        'id'    => 'min_critical',
                        'type'  => 'number',
                        'value' => $values['min_critical'],
                    ],
                ],
                [
                    'label'     => __('Max'),
                    'arguments' => [
                        'name'  => 'max_critical',
                        'id'    => 'max_critical',
                        'type'  => 'number',
                        'value' => $values['max_critical'],
                    ],
                ],
            ],

        ];

        // Type Label.
        $fields = [
            'module'       => __('Module'),
            'agent'        => __('Agent'),
            'agent_module' => __('Agent / module'),
        ];

        $inputs['inputs']['row2'][] = [
            'label'     => __('Label'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'label',
                'selected' => $values['label'],
                'return'   => true,
            ],
        ];

        $inputs['inputs']['row2'][] = [
            'arguments' => [
                'type'                   => 'select_multiple_modules_filtered_select2',
                'agent_values'           => agents_get_agents_selected(0),
                'agent_name'             => 'agentsGroupedMeterGraphs[]',
                'agent_ids'              => $values['agentsGroupedMeterGraphs'],
                'selectionModules'       => $values['selectionGroupedMeterGraphs'],
                'selectionModulesNameId' => 'selectionGroupedMeterGraphs',
                'modules_ids'            => $values['moduleGroupedMeterGraphs'],
                'modules_name'           => 'moduleGroupedMeterGraphs[]',
                'notStringModules'       => true,
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

        $values['agentsGroupedMeterGraphs'] = \get_parameter(
            'agentsGroupedMeterGraphs',
            []
        );
        $values['selectionGroupedMeterGraphs'] = \get_parameter(
            'selectionGroupedMeterGraphs',
            0
        );

        $values['moduleGroupedMeterGraphs'] = \get_parameter(
            'moduleGroupedMeterGraphs'
        );

        $agColor = [];
        if (isset($values['agentsGroupedMeterGraphs'][0]) === true
            && empty($values['agentsGroupedMeterGraphs'][0]) === false
        ) {
            $agColor = explode(',', $values['agentsGroupedMeterGraphs'][0]);
        }

        $agModule = [];
        if (isset($values['moduleGroupedMeterGraphs'][0]) === true
            && empty($values['moduleGroupedMeterGraphs'][0]) === false
        ) {
            $agModule = explode(',', $values['moduleGroupedMeterGraphs'][0]);
        }

        $values['moduleGroupedMeterGraphs'] = get_same_modules_all(
            $agColor,
            $agModule
        );

        $values['formatData'] = \get_parameter_switch('formatData', 0);

        $values['fontColor'] = \get_parameter('fontColor', '#2c3e50');

        $values['label'] = \get_parameter('label', 'module');

        $values['min_value'] = \get_parameter('min_value', null);
        $values['max_value'] = \get_parameter('max_value', null);

        $values['manualThresholds'] = \get_parameter_switch('manualThresholds', 0);
        $values['min_critical'] = \get_parameter('min_critical', null);
        $values['max_critical'] = \get_parameter('max_critical', null);
        $values['min_warning'] = \get_parameter('min_warning', null);
        $values['max_warning'] = \get_parameter('max_warning', null);

        return $values;
    }


    /**
     * Draw widget.
     *
     * @return string;
     */
    public function load()
    {
        $this->size = parent::getSize();
        $this->boxNumber = ceil(($this->size['width'] * 0.65) / self::RATIO_WITH_BOX);

        $output = '';
        if (is_metaconsole() === true) {
            $modules_nodes = array_reduce(
                $this->values['moduleGroupedMeterGraphs'],
                function ($carry, $item) {
                    $explode = explode('|', $item);
                    $carry[$explode[0]][] = $explode[1];
                    return $carry;
                },
                []
            );

            $modules = [];
            foreach ($modules_nodes as $n => $mod) {
                try {
                    $node = new Node((int) $n);
                    $node->connect();
                    $node_mods = $this->getInfoModules($mod);
                    if (empty($node_mods) === false) {
                        foreach ($node_mods as $value) {
                            $value['id_node'] = $n;
                            $value['server_name'] = $node->toArray()['server_name'];
                            $modules[] = $value;
                        }
                    }

                    $node->disconnect();
                } catch (\Exception $e) {
                    // Unexistent agent.
                    $node->disconnect();
                }
            }
        } else {
            $modules = $this->getInfoModules(
                $this->values['moduleGroupedMeterGraphs']
            );
        }

        if ($modules !== false
            && empty($modules) === false
            && is_array($modules) === true
        ) {
            if (count($modules) > self::MAX_MODULES) {
                $output .= '<div class="container-center">';
                $output .= \ui_print_info_message(
                    __(
                        'The maximum number of modules to display is %d, please reconfigure the widget.',
                        self::MAX_MODULES
                    ),
                    '',
                    true
                );
                $output .= '</div>';
                return $output;
            }

            $max = null;
            $min = null;
            // Dinamic treshold.
            if ($this->values['min_critical'] !== null
                || $this->values['max_critical'] !== null
                || $this->values['min_warning'] !== null
                || $this->values['max_warning'] !== null
            ) {
                if ($this->values['max_value'] === null || $this->values['min_value'] === null) {
                    $tresholdData = [
                        ($this->values['min_critical'] ?? 0),
                        ($this->values['max_critical'] ?? 0),
                        ($this->values['min_warning'] ?? 0),
                        ($this->values['max_warning'] ?? 0),
                    ];

                    $moduleData = array_map(
                        function ($module) {
                            return ($module['data'] ?? 0);
                        },
                        $modules
                    );
                }

                if ($this->values['max_value'] === null) {
                    $max = max(
                        array_merge(
                            $moduleData,
                            $tresholdData
                        )
                    );
                } else {
                    $max = $this->values['max_value'];
                }

                // Increases max.
                if ($this->values['max_critical'] === null && $this->values['max_value'] === null) {
                    $max_increase = ($max * self::MAX_INCREASE);
                    $max = ($max + $max_increase);
                }

                if ($this->values['min_value'] === null) {
                    $min = min(
                        array_merge(
                            $moduleData,
                            $tresholdData
                        )
                    );
                } else {
                    $min = $this->values['min_value'];
                }

                $tresholds_array = [
                    'min_critical' => $this->values['min_critical'],
                    'max_critical' => $this->values['max_critical'],
                    'min_warning'  => $this->values['min_warning'],
                    'max_warning'  => $this->values['max_warning'],
                ];

                $this->thresholds = $this->calculateThreshold($max, $min, $tresholds_array);
            }

            $style = 'color:'.$this->values['fontColor'].';';
            $output .= '<div class="container-grouped-meter" style="'.$style.'">';
            foreach ($modules as $module) {
                $output .= $this->drawRowModule(
                    $module,
                    $max,
                    $min
                );
            }

            $output .= '</div>';
        } else {
            $output .= '<div class="container-center">';
            $output .= \ui_print_info_message(
                __('Not found modules'),
                '',
                true
            );
            $output .= '</div>';
        }

        return $output;
    }


    /**
     * Get info modules.
     *
     * @param array $modules Modules.
     *
     * @return array Data.
     */
    private function getInfoModules(array $modules): array
    {
        $where = sprintf(
            'tagente_modulo.id_agente_modulo IN (%s)
            AND tagente_modulo.delete_pending = 0',
            implode(',', $modules)
        );

        $sql = sprintf(
            'SELECT tagente_modulo.id_agente_modulo AS `id`,
                tagente_modulo.nombre AS `name`,
                tagente_modulo.unit AS `unit`,
                tagente_modulo.min_warning AS w_min,
                tagente_modulo.max_warning AS w_max,
                tagente_modulo.str_warning AS w_str,
                tagente_modulo.min_critical AS c_min,
                tagente_modulo.max_critical AS c_max,
                tagente_modulo.str_critical AS c_str,
                tagente_modulo.id_tipo_modulo AS type_module,
                tagente_estado.datos AS `data`,
                tagente_estado.timestamp AS `timestamp`,
                tagente_estado.estado AS `status`,
                tagente.alias
            FROM tagente_modulo
            LEFT JOIN tagente_estado
                ON tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
            LEFT JOIN tagente
                ON tagente_modulo.id_agente = tagente.id_agente
            WHERE %s',
            $where
        );

        $modules = db_get_all_rows_sql($sql);

        if ($modules === false) {
            $modules = [];
        }

        return $modules;
    }


    /**
     * Draw info module.
     *
     * @param array      $data Data module.
     * @param null|float $max  Value max.
     * @param null|float $min  Value min.
     *
     * @return string
     */
    private function drawRowModule(
        array $data,
        ?float $max,
        ?float $min
    ):string {
        global $config;

        // Dinamic.
        if ($max === null && $min === null) {
            $all_values_module = [
                ($data['w_min'] ?? 0),
                ($data['w_max'] ?? 0),
                ($data['c_min'] ?? 0),
                ($data['c_max'] ?? 0),
                ($data['data'] ?? 0),
            ];

            if ($this->values['max_value'] === null) {
                $max = max($all_values_module);
            } else {
                $max = $this->values['max_value'];
            }

            // Increases max.
            if (empty($data['c_max']) === true
                && $this->values['max_value'] === null
            ) {
                $max_increase = ($max * self::MAX_INCREASE);
                $max = ($max + $max_increase);
            }

            $min = 0;
            if ($this->values['min_value'] !== null) {
                $min = $this->values['min_value'];
            }

            $thresholds_array = [
                'min_critical' => (empty($data['c_min']) === true) ? null : $data['c_min'],
                'max_critical' => (empty($data['c_max']) === true) ? null : $data['c_max'],
                'min_warning'  => (empty($data['w_min']) === true) ? null : $data['w_min'],
                'max_warning'  => (empty($data['w_max']) === true) ? null : $data['w_max'],
            ];

            $this->thresholds = $this->calculateThreshold(
                $max,
                $min,
                $thresholds_array
            );
        }

        $module_data = $this->getBoxPercentageMaths($max, $min, (float) $data['data']);

        $output = '';
        $output .= '<div class="container-info-module-meter">';

        // Module name.
        $name = '';
        switch ($this->values['label']) {
            case 'agent':
                $name = $data['alias'];
            break;

            case 'agent_module':
                $name = $data['alias'].' / '.$data['name'];
            break;

            default:
            case 'module':
                $name = $data['name'];
            break;
        }

        $output .= '<div class="container-info-module-meter-title" title="'.$name.'">';
        $output .= $name;
        $output .= '</div>';

        // Graphs.
        $output .= '<div class="container-info-module-meter-graphs">';
        for ($i = 0; $i < $this->boxNumber; $i++) {
            $class = 'meter-graph-';
            $class .= $this->getThresholdStatus($i);

            if ($module_data > $i) {
                $class .= ' meter-graph-opacity';
            }

            $output .= '<div class="'.$class.'">';
            $output .= '</div>';
        }

        $output .= '</div>';

        // Data.
        $class = 'container-info-module-meter-data';
        $class .= ' meter-data-';
        $class .= $this->getThresholdStatus($module_data);

        $result_data = '';
        if ($data['data'] !== null && $data['data'] !== '') {
            if (isset($this->values['formatData']) === true
                && (bool) $this->values['formatData'] === true
            ) {
                $result_data .= format_for_graph(
                    $data['data'],
                    $config['graph_precision']
                );
            } else {
                $result_data .= sla_truncate(
                    $data['data'],
                    $config['graph_precision']
                );
            }

            $result_data .= ' '.$data['unit'];
        } else {
            $result_data .= '--';
        }

        $output .= '<div class="'.$class.'" title="'.$result_data.'">';
        $output .= $result_data;
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }


    /**
     * Get status.
     *
     * @return array
     */
    private static function getStatuses()
    {
        return [
            self::STATUS_CRITICAL,
            self::STATUS_WARNING,
            self::STATUS_NORMAL,
        ];
    }


    /**
     * Get tresholds.
     *
     * @param float $max              Value max.
     * @param float $min              Value min.
     * @param array $thresholds_array Array thresholds.
     *
     * @return array Array threshold.
     */
    private function calculateThreshold(float $max, float $min, array $thresholds_array)
    {
        $nMax = null;
        if ($thresholds_array['min_warning'] !== null) {
            $nMax = $this->getBoxPercentageMaths($max, $min, $thresholds_array['min_warning']);
        }

        $wMin = null;
        if ($thresholds_array['min_warning'] !== null) {
            $wMin = $this->getBoxPercentageMaths($max, $min, $thresholds_array['min_warning']);
        }

        $wMax = null;
        if ($thresholds_array['max_warning'] !== null) {
            $wMax = $this->getBoxPercentageMaths($max, $min, $thresholds_array['max_warning']);
        }

        $cMin = null;
        if ($thresholds_array['min_critical'] !== null) {
            $cMin = $this->getBoxPercentageMaths($max, $min, $thresholds_array['min_critical']);
        }

        $cMax = null;
        if ($thresholds_array['max_critical'] !== null) {
            $cMax = $this->getBoxPercentageMaths($max, $min, $thresholds_array['max_critical']);
        }

        $thresholds = [
            'normal'   => [
                'min' => $this->getBoxPercentageMaths($max, $min, $min),
                'max' => $nMax,
            ],
            'warning'  => [
                'min' => $wMin,
                'max' => $wMax,
            ],
            'critical' => [
                'min' => $cMin,
                'max' => $cMax,
            ],
        ];

        return $thresholds;
    }


    /**
     * Get porcentage.
     *
     * @param float $max   Maximum.
     * @param float $min   Minimum.
     * @param float $value Value.
     *
     * @return float
     */
    private function getBoxPercentageMaths(
        float $max,
        float $min,
        float $value
    ):float {
        if ($min === 0.00 && $max === 0.00) {
            return 0;
        }

        return (((($value - $min) / ($max - $min))) * $this->boxNumber);
    }


    /**
     * Get status compare tresholds.
     *
     * @param float $value Value to compare.
     *
     * @return string
     */
    private function getThresholdStatus(
        float $value
    ) {
        foreach (self::getStatuses() as $status) {
            if ($this->thresholds[$status]['min'] === null
                && $this->thresholds[$status]['max'] === null
            ) {
                continue;
            }

            if (($this->thresholds[$status]['min'] === null
                && $this->thresholds[$status]['max'] >= $value)
                || ($this->thresholds[$status]['max'] === null
                && $this->thresholds[$status]['min'] <= $value)
                || ($this->thresholds[$status]['min'] <= $value
                && $this->thresholds[$status]['max'] >= $value)
            ) {
                return $status;
            }
        }

        return self::STATUS_NORMAL;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Grouped meter graphs');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'GroupedMeterGraphs';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => (is_metaconsole() === true) ? 1000 : 900,
            'height' => 550,
        ];

        return $size;
    }


}
