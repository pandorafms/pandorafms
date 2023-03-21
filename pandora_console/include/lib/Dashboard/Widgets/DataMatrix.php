<?php
/**
 * Widget data matrix Pandora FMS Console
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
class DataMatrix extends Widget
{

    private const MAX_MODULES = 10;

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
        $this->title = __('Color tabs modules');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'single_graph';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['moduleDataMatrix']) === true) {
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

        $values['agentsDataMatrix'] = [];
        if (isset($decoder['agentsDataMatrix']) === true) {
            if (isset($decoder['agentsDataMatrix'][0]) === true
                && empty($decoder['agentsDataMatrix']) === false
            ) {
                $values['agentsDataMatrix'] = explode(
                    ',',
                    $decoder['agentsDataMatrix'][0]
                );
            }
        }

        if (isset($decoder['selectionDataMatrix']) === true) {
            $values['selectionDataMatrix'] = $decoder['selectionDataMatrix'];
        }

        $values['moduleDataMatrix'] = [];
        if (isset($decoder['moduleDataMatrix']) === true) {
            if (empty($decoder['moduleDataMatrix']) === false) {
                $values['moduleDataMatrix'] = $decoder['moduleDataMatrix'];
            }
        }

        if (isset($decoder['formatData']) === true) {
            $values['formatData'] = $decoder['formatData'];
        }

        $values['label'] = 'module';
        if (isset($decoder['label']) === true) {
            $values['label'] = $decoder['label'];
        }

        if (isset($decoder['fontColor']) === true) {
            $values['fontColor'] = $decoder['fontColor'];
        }

        if (isset($decoder['period']) === true) {
            $values['period'] = $decoder['period'];
        }

        if (isset($decoder['slice']) === true) {
            $values['slice'] = $decoder['slice'];
        }

        if (isset($decoder['limit']) === true) {
            $values['limit'] = $decoder['limit'];
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

        $blocks = [
            'row1',
            'row2',
        ];

        $inputs['blocks'] = $blocks;

        foreach ($inputs as $kInput => $vInput) {
            $inputs['inputs']['row1'][] = $vInput;
        }

        if (isset($values['formatData']) === false) {
            $values['formatData'] = 1;
        }

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

        if (isset($values['period']) === false) {
            $values['period'] = SECONDS_1DAY;
        }

        $inputs['inputs']['row1'][] = [
            'label'     => __('Periodicity'),
            'arguments' => [
                'name'          => 'period',
                'type'          => 'interval',
                'value'         => $values['period'],
                'nothing'       => __('None'),
                'nothing_value' => 0,
                'style_icon'    => 'flex-grow: 0',
            ],
        ];

        if (isset($values['slice']) === false) {
            $values['slice'] = SECONDS_5MINUTES;
        }

        $inputs['inputs']['row1'][] = [
            'label'     => __('Interval'),
            'arguments' => [
                'name'          => 'slice',
                'type'          => 'interval',
                'value'         => $values['slice'],
                'nothing'       => __('None'),
                'nothing_value' => 0,
                'style_icon'    => 'flex-grow: 0',
            ],
        ];

        if (isset($values['limit']) === false) {
            $values['limit'] = $config['block_size'];
        }

        // Limit Default block_size.
        $blockSizeD4 = \format_integer_round(($config['block_size'] / 4));
        $blockSizeD2 = \format_integer_round(($config['block_size'] / 2));
        $fields = [
            $config['block_size']       => $config['block_size'],
            $blockSizeD4                => $blockSizeD4,
            $blockSizeD2                => $blockSizeD2,
            ($config['block_size'] * 2) => ($config['block_size'] * 2),
            ($config['block_size'] * 3) => ($config['block_size'] * 3),
        ];

        $inputs['inputs']['row1'][] = [
            'label'     => \__('Limit'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'class'    => 'event-widget-input',
                'name'     => 'limit',
                'selected' => $values['limit'],
                'return'   => true,
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
                'agent_name'             => 'agentsDataMatrix[]',
                'agent_ids'              => $values['agentsDataMatrix'],
                'selectionModules'       => $values['selectionDataMatrix'],
                'selectionModulesNameId' => 'selectionDataMatrix',
                'modules_ids'            => $values['moduleDataMatrix'],
                'modules_name'           => 'moduleDataMatrix[]',
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

        $values['agentsDataMatrix'] = \get_parameter(
            'agentsDataMatrix',
            []
        );
        $values['selectionDataMatrix'] = \get_parameter(
            'selectionDataMatrix',
            0
        );

        $values['moduleDataMatrix'] = \get_parameter(
            'moduleDataMatrix'
        );

        $agColor = [];
        if (isset($values['agentsDataMatrix'][0]) === true
            && empty($values['agentsDataMatrix'][0]) === false
        ) {
            $agColor = explode(',', $values['agentsDataMatrix'][0]);
        }

        $agModule = [];
        if (isset($values['moduleDataMatrix'][0]) === true
            && empty($values['moduleDataMatrix'][0]) === false
        ) {
            $agModule = explode(',', $values['moduleDataMatrix'][0]);
        }

        $values['moduleDataMatrix'] = \get_same_modules_all(
            $agColor,
            $agModule
        );

        $values['formatData'] = \get_parameter_switch('formatData');

        $values['fontColor'] = \get_parameter('fontColor', '#2c3e50');
        $values['label'] = \get_parameter('label', 'module');

        $values['period'] = \get_parameter('period', 0);
        $values['slice'] = \get_parameter('slice', 0);
        $values['limit'] = \get_parameter('limit', 20);

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

        $output = '';
        if (count($this->values['moduleDataMatrix']) > self::MAX_MODULES) {
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

        if (is_metaconsole() === true) {
            $modules_nodes = array_reduce(
                $this->values['moduleDataMatrix'],
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
                $this->values['moduleDataMatrix']
            );
        }

        if ($modules !== false && empty($modules) === false) {
            // Datatables list.
            try {
                $info_columns = $this->columns($modules);
                $columns = $info_columns['columns'];
                $column_names = $info_columns['column_names'];
                $columns_sort = $info_columns['columns_sort'];

                $tableId = 'dataMatrix_'.$this->dashboardId.'_'.$this->cellId;
                // Load datatables user interface.
                ui_print_datatable(
                    [
                        'id'                  => $tableId,
                        'class'               => 'info_table',
                        'style'               => 'width: 99%',
                        'columns'             => $columns,
                        'column_names'        => $column_names,
                        'ajax_url'            => 'include/ajax/module',
                        'ajax_data'           => [
                            'get_data_dataMatrix' => 1,
                            'table_id'            => $tableId,
                            'period'              => $this->values['period'],
                            'slice'               => $this->values['slice'],
                            'formatData'          => $this->values['formatData'],
                            'modules'             => json_encode($modules),
                        ],
                        'default_pagination'  => $this->values['limit'],
                        'no_sortable_columns' => $columns_sort,
                        'order'               => [
                            'field'     => 'date',
                            'direction' => 'desc',
                        ],
                        'csv'                 => 0,
                        'dom_elements'        => 'frtilp',
                    ]
                );
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        } else {
            $output = '';
            $output .= '<div class="container-center">';
            $output .= \ui_print_info_message(
                __('Not found modules'),
                '',
                true
            );
            $output .= '</div>';

            return $output;
        }
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
     * Get columns.
     *
     * @param array $modules Info modules.
     *
     * @return array
     */
    private function columns(array $modules)
    {
        $columns = [];
        $columns[] = 'date';
        $column_names = [];
        $column_names[] = __('Date');
        $columns_sort = [];
        $columns_sort[] = 0;
        foreach ($modules as $key => $module) {
            $columns[] = 'Column-'.$module['id'];
            // Module name.
            $name = '';
            switch ($this->values['label']) {
                case 'agent':
                    $name = $module['alias'];
                break;

                case 'agent_module':
                    $name = $module['alias'].' / '.$module['name'];
                break;

                default:
                case 'module':
                    $name = $module['name'];
                break;
            }

            $columns_sort[] = ($key + 1);
            $column_names[] = \ui_print_truncate_text(
                \io_safe_output($name),
                'agent_small',
                false,
                true,
                false,
                '...'
            );
        }

        $data = [
            'columns'      => $columns,
            'column_names' => $column_names,
            'columns_sort' => $columns_sort,
        ];

        return $data;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Data Matrix');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'DataMatrix';
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
            'height' => 480,
        ];

        return $size;
    }


}
