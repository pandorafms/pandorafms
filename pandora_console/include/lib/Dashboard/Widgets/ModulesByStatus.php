<?php
/**
 * Widget Module status Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Module status
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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
 * Module status Widgets.
 */
class ModulesByStatus extends Widget
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
        $this->title = __('Module status');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'ModulesByStatus';
        }

        // This forces at least a first configuration.
        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['status']) === true && $this->values['status'] !== '0') {
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

        if (isset($decoder['search']) === true) {
            $values['search'] = $decoder['search'];
        }

        if (isset($decoder['status']) === true) {
            $values['status'] = $decoder['status'];
        }

        if (isset($decoder['limit']) === true) {
            $values['limit'] = $decoder['limit'];
        }

        if (isset($decoder['nodes']) === true) {
            $values['nodes'] = $decoder['nodes'];
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

        // Search.
        $inputs[] = [
            'label'     => __('Free search').ui_print_help_tip(__('Search filter by Module name field content'), true),
            'arguments' => [
                'name'   => 'search',
                'type'   => 'text',
                'value'  => $values['search'],
                'return' => true,
                'size'   => 0,
            ],
        ];

        // Status fields.
        $status_fields = [];
        $status_fields[AGENT_MODULE_STATUS_NORMAL] = __('Normal');
        $status_fields[AGENT_MODULE_STATUS_CRITICAL_BAD] = __('Critical');
        $status_fields[AGENT_MODULE_STATUS_WARNING] = __('Warning');
        $status_fields[AGENT_MODULE_STATUS_UNKNOWN] = __('Unknown');
        $status_fields[AGENT_MODULE_STATUS_NOT_INIT] = __('Not init');
        $status_fields[AGENT_MODULE_STATUS_NOT_NORMAL] = __('Not normal');
        $status_selected = explode(',', $values['status']);

        (isset($values['status']) === false) ? $status_selected = AGENT_MODULE_STATUS_CRITICAL_BAD : '';

        $inputs[] = [
            'label'     => __('Status'),
            'arguments' => [
                'name'       => 'status',
                'type'       => 'select',
                'fields'     => $status_fields,
                'selected'   => $status_selected,
                'return'     => true,
                'multiple'   => true,
                'size'       => count($status_fields),
                'select_all' => false,
                'required'   => true,
            ],
        ];

        // Limit fields.
        $limit_fields = [];
        $limit_fields[5] = 5;
        $limit_fields[10] = 10;
        $limit_fields[25] = 25;
        $limit_fields[100] = 100;
        $limit_fields[200] = 200;
        $limit_fields[500] = 500;
        $limit_fields[1000] = 1000;
        $limit_selected = explode(',', $values['limit']);

        (isset($values['limit']) === false) ? $limit_selected = 5 : '';

        $inputs[] = [
            'label'     => __('Limit'),
            'arguments' => [
                'name'           => 'limit',
                'type'           => 'select',
                'fields'         => $limit_fields,
                'selected'       => $limit_selected,
                'return'         => true,
                'required'       => true,
                'select2_enable' => false,
            ],
        ];

        // Nodes.
        if (is_metaconsole() === true) {
            $nodes_fields = [];
            $servers_ids = metaconsole_get_servers();

            foreach ($servers_ids as $server) {
                $nodes_fields[$server['id']] = $server['server_name'];
            }

            $nodes_fields[0] = __('Metaconsola');

            $nodes_selected = explode(',', $values['nodes']);

            (isset($values['nodes']) === false) ? $nodes_selected = $servers_ids : '';

            $nodes_height = count($nodes_fields);
            if (count($nodes_fields) > 5) {
                $nodes_height = 5;
            }

            $inputs[] = [
                'label'     => __('Nodes'),
                'arguments' => [
                    'name'       => 'nodes',
                    'type'       => 'select',
                    'fields'     => $nodes_fields,
                    'selected'   => $nodes_selected,
                    'return'     => true,
                    'multiple'   => true,
                    'class'      => 'overflow-hidden',
                    'size'       => $nodes_height,
                    'select_all' => false,
                    'required'   => true,
                ],
            ];
        }

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

        $values['search'] = \get_parameter('search', '');
        $values['status'] = \get_parameter('status', '');
        $values['limit'] = \get_parameter('limit', '');
        $values['nodes'] = \get_parameter('nodes', '');

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

        global $config;

        $output = '';

        if (is_metaconsole() === true) {
            $modules = [];

            $servers_ids = array_column(metaconsole_get_servers(), 'id');

            foreach ($servers_ids as $server_id) {
                try {
                    $node = new Node((int) $server_id);

                    $node->connect();
                    $modules_tmp = $this->getInfoModules(
                        $this->values['search'],
                        $this->values['status'],
                        $this->values['nodes']
                    );
                    $modules[$node->id()] = $modules_tmp[0];
                    $node->disconnect();
                } catch (\Exception $e) {
                    // Unexistent modules.
                    $node->disconnect();
                }
            }
        } else {
            $modules = $this->getInfoModules(
                $this->values['search'],
                $this->values['status']
            );
        }

        if ($modules !== false && empty($modules) === false) {
            // Datatables list.
            try {
                $info_columns = $this->columns();
                $column_names = $info_columns['column_names'];
                $columns = $info_columns['columns'];

                $tableId = 'ModuleByStatus_'.$this->dashboardId.'_'.$this->cellId;
                // Load datatables user interface.
                ui_print_datatable(
                    [
                        'id'                 => $tableId,
                        'class'              => 'info_table align-left-important',
                        'style'              => 'width: 99%',
                        'columns'            => $columns,
                        'column_names'       => $column_names,
                        'ajax_url'           => 'include/ajax/module',
                        'ajax_data'          => [
                            'get_data_ModulesByStatus' => 1,
                            'table_id'                 => $tableId,
                            'search'                   => $this->values['search'],
                            'status'                   => $this->values['status'],
                            'nodes'                    => $this->values['nodes'],
                        ],
                        'default_pagination' => $this->values['limit'],
                        'order'              => [
                            'field'     => 'last_status_change',
                            'direction' => 'desc',
                        ],
                        'csv'                => 0,
                        'pagination_options' => [
                            [
                                5,
                                10,
                                25,
                                100,
                                200,
                                500,
                                1000,
                            ],
                            [
                                5,
                                10,
                                25,
                                100,
                                200,
                                500,
                                1000,
                            ],
                        ],
                        'dom_elements'       => 'frtilp',
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
     * @param string $search Free search.
     * @param string $status Modules status.
     *
     * @return array Data.
     */
    private function getInfoModules(string $search, string $status): array
    {
        if (empty($search) === false) {
            $where = 'tagente_modulo.nombre LIKE "%%'.$search.'%%" AND ';
        }

        if (str_contains($status, '6') === true) {
            $expl = explode(',', $status);
            $exist = array_search('6', $expl);
            if (isset($exist) === true) {
                unset($expl[$exist]);
            }

            array_push($expl, '1', '2');

            $status = implode(',', $expl);
        }

        $where .= sprintf(
            'tagente_estado.estado IN (%s)
            AND tagente_modulo.delete_pending = 0',
            $status
        );

        $sql = sprintf(
            'SELECT
            COUNT(*) AS "modules"
            FROM tagente_modulo
            INNER JOIN tagente
                ON tagente_modulo.id_agente = tagente.id_agente 
            INNER JOIN tagente_estado
                ON tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
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
     * @return array
     */
    private function columns()
    {
        $columns = [];
        $column_names = [];

        if (is_metaconsole() === true) {
            $column_names = [
                __('Module name'),
                __('Agent'),
                __('Node'),
                __('Last status change'),
                __('Status'),
            ];

            $columns = [
                'nombre',
                'alias',
                'server_name',
                'last_status_change',
                'estado',
            ];
        } else {
            $column_names = [
                __('Module name'),
                __('Agent'),
                __('Last status change'),
                __('Status'),
            ];

            $columns = [
                'nombre',
                'alias',
                'last_status_change',
                'estado',
            ];
        }

        $data = [
            'columns'      => $columns,
            'column_names' => $column_names,
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
        return __('Modules by status');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'ModulesByStatus';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        if (is_metaconsole() === true) {
            $nodes_fields = array_column(metaconsole_get_servers(), 'id');

            $height_counter = (((int) count($nodes_fields)) * 20);

            $size = [
                'width'  => 450,
                'height' => (520 + $height_counter),
            ];
        } else {
            $size = [
                'width'  => 450,
                'height' => 480,
            ];
        }

        return $size;
    }


}
