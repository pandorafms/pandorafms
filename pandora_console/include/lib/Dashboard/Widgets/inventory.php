<?php
/**
 * Widget Inventory Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget inventory
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

global $config;

require_once $config['homedir'].'/include/functions_inventory.php';

/**
 * Inventory Widget.
 */
class InventoryWidget extends Widget
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
        $this->title = __('Inventory');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'inventory';
        }

        // Must be configured before using.
        $this->configurationRequired = false;
        if (isset($this->values['idGroup']) === false) {
            $this->configurationRequired = true;
        }
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

        if (isset($decoder['agentId']) === true) {
            $values['agentId'] = $decoder['agentId'];
        }

        if (isset($decoder['agentAlias']) === true) {
            $values['agentAlias'] = $decoder['agentAlias'];
        }

        if (isset($decoder['metaconsoleId']) === true) {
            $values['metaconsoleId'] = $decoder['metaconsoleId'];
        }

        if (isset($decoder['inventoryModuleId']) === true) {
            $values['inventoryModuleId'] = $decoder['inventoryModuleId'];
        }

        if (isset($decoder['freeSearch']) === true) {
            $values['freeSearch'] = $decoder['freeSearch'];
        }

        if (isset($decoder['orderByAgent']) === true) {
            $values['orderByAgent'] = $decoder['orderByAgent'];
        }

        if (isset($decoder['idGroup']) === true) {
            $values['idGroup'] = $decoder['idGroup'];
        }

        if (isset($decoder['utimestamp']) === true) {
            $values['utimestamp'] = $decoder['utimestamp'];
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

        // Format Data.
        $inputs[] = [
            'label'     => __('Order by agent'),
            'arguments' => [
                'name'  => 'order_by_agent',
                'type'  => 'switch',
                'value' => $values['orderByAgent'],
            ],
        ];

        $inputs[] = [
            'label'     => __('Free search'),
            'arguments' => [
                'name'        => 'free_search',
                'type'        => 'text',
                'class'       => 'w96p',
                'input_class' => 'flex-row',
                'value'       => $values['freeSearch'],
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Group'),
            'arguments' => [
                'type'          => 'select_groups',
                'privilege'     => 'AR',
                'name'          => 'id_group',
                'selected'      => $values['idGroup'],
                'nothing_value' => __('All'),
                'return'        => true,
                'multiple'      => false,
            ],
        ];

        $inputs[] = [
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

        $fields = [];
        array_unshift($fields, __('All'));

        if (isset($values['inventoryModuleId']) === false) {
            $values['inventoryModuleId'] = 0;
        }

        $inputs[] = [
            'label'     => __('Module'),
            'arguments' => [
                'name'          => 'module_inventory',
                'id'            => 'module_inventory',
                'input_class'   => 'flex-row',
                'type'          => 'select',
                'selected'      => io_safe_output($values['inventoryModuleId']),
                'nothing'       => __('Basic info'),
                'nothing_value' => 'basic',
                'fields'        => $fields,
                'class'         => '',
                'return'        => true,
                'sort'          => true,
            ],
        ];

        // Date filter.
        if (is_metaconsole() === false) {
            $inputs[] = [
                'label'     => \__('Date'),
                'arguments' => [
                    'type'          => 'select',
                    'fields'        => [],
                    'name'          => 'utimestamp',
                    'selected'      => $values['utimestamp'],
                    'return'        => true,
                    'nothing'       => \__('Last'),
                    'nothing_value' => 0,
                    'class'         => 'fullwidth',
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

        $values['agentId'] = (int) \get_parameter('agentId', 0);
        $values['metaconsoleId'] = (int) \get_parameter('metaconsoleId', 0);
        $values['inventoryModuleId'] = io_safe_output(\get_parameter('module_inventory', 'basic'));
        $values['agentAlias'] = \get_parameter('agentAlias', '');
        $values['freeSearch'] = (string) \get_parameter('free_search', '');
        $values['orderByAgent'] = \get_parameter('order_by_agent', 0);
        $values['idGroup'] = \get_parameter('id_group', 0);
        $values['utimestamp'] = (int) get_parameter('utimestamp');

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
        include_once $config['homedir'].'/include/functions_inventory.php';
        $inventory_id_agent = $this->values['agentId'];
        $inventory_agent = $this->values['agentAlias'];
        $cellId = $this->cellId;

        if (strlen($inventory_agent) === 0) {
            $inventory_id_agent = -1;
            $inventory_agent = __('All');
        } else if ($inventory_agent == __('All')) {
            $inventory_id_agent = 0;
        }

        $inventory_module = io_safe_input($this->values['inventoryModuleId']);

        $inventory_id_group = (int) $this->values['idGroup'];
        $inventory_search_string = (string) $this->values['freeSearch'];
        $order_by_agent = (bool) $this->values['orderByAgent'];
        $utimestamp = (int) $this->values['utimestamp'];

        $pagination_url_parameters = [
            'inventory_id_agent' => $inventory_id_agent,
            'inventory_agent'    => $inventory_agent,
            'inventory_id_group' => $inventory_id_group,
        ];

        $noFilterSelected = false;

        // Get variables.
        if (is_metaconsole() === true) {
            $nodes_connection = metaconsole_get_connections();
            $id_server = (int) $this->values['metaconsoleId'];

            $pagination_url_parameters['id_server'] = $id_server;

            if ($inventory_id_agent > 0) {
                $inventory_id_server = (int) get_parameter('id_server_agent', -1);
                $pagination_url_parameters['inventory_id_server'] = $inventory_id_server;

                if ($inventory_id_server !== -1) {
                    $id_server = $inventory_id_server;
                    $pagination_url_parameters['id_server'] = $id_server;
                }
            }

            // No filter selected.
            $noFilterSelected = $inventory_id_agent === -1 && $inventory_id_group === 0 && $id_server === 0;

            $nodo_image_url = $config['homeurl'].'/images/node.png';
            if ($id_server > 0) {
                $connection = metaconsole_get_connection_by_id($id_server);
                $agents_node = metaconsole_get_agents_servers($connection['server_name'], $inventory_id_group);
                $node = metaconsole_get_servers($id_server);
                $nodos = [];

                if (metaconsole_connect($connection) !== NOERR) {
                    ui_print_error_message(
                        __('There was a problem connecting with the node')
                    );
                }

                $sql = 'SELECT DISTINCT name as indexname, name
                    FROM tmodule_inventory, tagent_module_inventory
                    WHERE tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory';

                if ($inventory_id_agent > 0) {
                    $sql .= ' AND id_agente = '.$inventory_id_agent;
                    $agents_node = [$inventory_id_agent => $inventory_id_agent];
                }

                $result_module = db_get_all_rows_sql($sql);
                // Get the data.
                $rows_meta = inventory_get_datatable(
                    array_keys($agents_node),
                    $inventory_module,
                    $utimestamp,
                    $inventory_search_string,
                    false,
                    false,
                    $order_by_agent,
                    $server,
                    $pagination_url_parameters
                );

                $data_tmp['server_name'] = $connection['server_name'];
                $data_tmp['dbhost'] = $connection['dbhost'];
                $data_tmp['server_uid'] = $connection['server_uid'];
                $data_tmp['data'] = $rows_meta;

                $nodos[$connection['id']] = $data_tmp;
                if ($result_data !== ERR_NODATA) {
                    $inventory_data .= $result_data;
                }

                // Restore db connection.
                metaconsole_restore_db();
            } else {
                $result_module = [];
                $nodos = [];
                foreach ($nodes_connection as $key => $server) {
                    $agents_node = metaconsole_get_agents_servers($server['server_name'], $inventory_id_group);
                    $connection = metaconsole_get_connection($server['server_name']);
                    if (metaconsole_connect($connection) !== NOERR) {
                        continue;
                    }

                    $sql = 'SELECT DISTINCT name as indexname, name
                        FROM tmodule_inventory, tagent_module_inventory
                        WHERE tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory';
                    if ($inventory_id_agent > 0) {
                        $sql .= ' AND id_agente = '.$inventory_id_agent;
                        $agents_node = [$inventory_id_agent => $inventory_id_agent];
                    }

                    $result = db_get_all_rows_sql($sql);
                    if ($result !== false) {
                        $result_module = array_merge($result_module, $result);
                        // Get the data.
                        $rows_meta = inventory_get_datatable(
                            array_keys($agents_node),
                            $inventory_module,
                            $utimestamp,
                            $inventory_search_string,
                            false,
                            false,
                            $order_by_agent,
                            $server,
                            $pagination_url_parameters
                        );

                        $data_tmp['server_name'] = $server['server_name'];
                        $data_tmp['dbhost'] = $server['dbhost'];
                        $data_tmp['server_uid'] = $server['server_uid'];
                        $data_tmp['data'] = $rows_meta;
                        $nodos[$server['id']] = $data_tmp;
                        if ($result_data !== ERR_NODATA) {
                            $inventory_data .= $result_data;
                        }
                    }

                    // Restore db connection.
                    metaconsole_restore_db();
                }
            }

            $fields = [];
            foreach ($result_module as $row) {
                $id = array_shift($row);
                $value = array_shift($row);
                $fields[$id] = $value;
            }
        }

        // Agent select.
        if (is_metaconsole() === false) {
            $agents = [];
            $sql = 'SELECT id_agente, nombre FROM tagente';
            if ($inventory_id_group > 0) {
                $sql .= ' WHERE id_grupo = '.$inventory_id_group;
            } else {
                $user_groups = implode(',', array_keys(users_get_groups($config['id_user'])));

                // Avoid errors if there are no groups.
                if (empty($user_groups) === true) {
                    $user_groups = '"0"';
                }

                $sql .= ' WHERE id_grupo IN ('.$user_groups.')';
            }

            $result = db_get_all_rows_sql($sql);
            if ($result) {
                foreach ($result as $row) {
                    $agents[$row['id_agente']] = $row['nombre'];
                }
            }
        }

        $filteringFunction = '';
        if ($inventory_module !== 'basic') {
            if (is_metaconsole() === true) {
                if ($order_by_agent === true) {
                    $count_nodos_tmp = [];
                    foreach ($nodos as $count_value) {
                        array_push($count_nodos_tmp, $count_value['server_name']);
                    }

                    $count = array_count_values($count_nodos_tmp);

                    foreach ($nodos as $nodo) {
                        $agents = '';

                        foreach ($nodo['data'] as $agent_rows) {
                            $modules = '';

                            foreach ($agent_rows['row'] as $key => $row) {
                                $columns = explode(';', io_safe_output($row['data_format']));
                                array_push($columns, 'Timestamp');
                                $data = [];
                                $data_rows = explode(PHP_EOL, $row['data']);
                                foreach ($data_rows as $data_row) {
                                    // Exclude results don't match filter.
                                    if ($inventory_search_string && preg_match('/'.io_safe_output($inventory_search_string).'/', ($data_row)) == 0) {
                                        continue;
                                    }

                                    $column_data = explode(';', io_safe_output($data_row));

                                    if ($column_data[0] !== '') {
                                        $row_tmp = [];
                                        foreach ($column_data as $key => $value) {
                                            $row_tmp[$columns[$key]] = $value;
                                        }

                                        $row_tmp['Timestamp'] = $row['timestamp'];
                                        array_push($data, (object) $row_tmp);
                                    }
                                }

                                $id_table = 'id_'.$row['id_module_inventory'].'_'.$nodo['server_uid'].'_'.$cellId;
                                $table = ui_print_datatable(
                                    [
                                        'id'                  => $id_table,
                                        'class'               => 'info_table w96p',
                                        'style'               => 'width: 100%',
                                        'columns'             => $columns,
                                        'column_names'        => $columns,
                                        'no_sortable_columns' => [],
                                        'data_element'        => $data,
                                        'searching'           => true,
                                        'dom_elements'        => 'rtilp',
                                        'order'               => [
                                            'field'     => $columns[0],
                                            'direction' => 'asc',
                                        ],
                                        'zeroRecords'         => __('No inventory found'),
                                        'emptyTable'          => __('No inventory found'),
                                        'return'              => true,
                                        'no_sortable_columns' => [-1],
                                        'csv'                 => 0,
                                        'mini_pagination'     => true,
                                        'mini_search'         => true,
                                    ]
                                );

                                $modules .= ui_toggle(
                                    $table,
                                    '<span class="title-blue">'.$row['name'].'</span>',
                                    '',
                                    '',
                                    true,
                                    true,
                                    '',
                                    'white-box-content w96p',
                                    'box-shadow white_table_graph w96p',
                                    'images/arrow_down_green.png',
                                    'images/arrow_right_green.png',
                                    false,
                                    false,
                                    false,
                                    '',
                                    '',
                                    null,
                                    null,
                                    false,
                                    $id_table
                                );
                            }

                            $agents .= ui_toggle(
                                $modules,
                                $agent_rows['agent'],
                                '',
                                '',
                                true,
                                true,
                                '',
                                'white-box-content w96p',
                                'box-shadow white_table_graph w96p',
                            );
                        }

                        $node_name = $nodo['server_name'];
                        if ($count[$nodo['server_name']] > 1) {
                            $node_name .= ' ('.$nodo['dbhost'].')';
                        }

                        ui_toggle(
                            $agents,
                            '<span class="toggle-inventory-nodo">'.$node_name.'</span>',
                            '',
                            $cellId,
                            false,
                            false,
                            '',
                            'white-box-content',
                            'box-flat white_table_graph w96p'
                        );
                    }
                } else {
                    $count_nodos_tmp = [];
                    foreach ($nodos as $count_value) {
                        array_push($count_nodos_tmp, $count_value['server_name']);
                    }

                    $count = array_count_values($count_nodos_tmp);

                    foreach ($nodos as $nodo_key => $nodo) {
                        $agents = '';

                        foreach ($nodo['data'] as $module_key => $module_rows) {
                            $agent = '';
                            $data = [];
                            foreach ($module_rows as $row) {
                                $columns = explode(';', io_safe_output($row['data_format']));
                                array_push($columns, 'Timestamp');

                                $data_explode = explode(PHP_EOL, $row['data']);
                                foreach ($data_explode as $values) {
                                    // Exclude results don't match filter.
                                    if ($inventory_search_string && preg_match('/'.io_safe_output($inventory_search_string).'/', ($values)) == 0) {
                                        continue;
                                    }

                                    $data_tmp = [];
                                    if ($values !== '') {
                                        $values_explode = explode(';', io_safe_output($values));

                                        foreach ($values_explode as $key => $value) {
                                            $data_tmp[$columns[$key]] = $value;
                                        }

                                        $data_tmp['Timestamp'] = $row['timestamp'];
                                        array_push($data, $data_tmp);
                                    }
                                }
                            }

                            $id_table = 'id_'.$row['id_module_inventory'].'_'.$nodo['server_uid'].'_'.$cellId;

                            $table = ui_print_datatable(
                                [
                                    'id'                  => $id_table,
                                    'class'               => 'info_table w96p',
                                    'style'               => 'width: 100%',
                                    'columns'             => $columns,
                                    'column_names'        => $columns,
                                    'no_sortable_columns' => [],
                                    'data_element'        => $data,
                                    'searching'           => true,
                                    'dom_elements'        => 'rtilp',
                                    'order'               => [
                                        'field'     => $columns[0],
                                        'direction' => 'asc',
                                    ],
                                    'zeroRecords'         => __('No inventory found'),
                                    'emptyTable'          => __('No inventory found'),
                                    'return'              => true,
                                    'no_sortable_columns' => [-1],
                                    'csv'                 => 0,
                                    'mini_pagination'     => true,
                                    'mini_search'         => true,
                                ]
                            );

                            $agent .= ui_toggle(
                                $table,
                                '<span class="title-blue">'.$row['name_agent'].'</span>',
                                '',
                                '',
                                true,
                                true,
                                '',
                                'white-box-content w96p',
                                'box-shadow white_table_graph w96p',
                                'images/arrow_down_green.png',
                                'images/arrow_right_green.png',
                                false,
                                false,
                                false,
                                '',
                                '',
                                null,
                                null,
                                false,
                                $id_table
                            );

                            $agents .= ui_toggle(
                                $agent,
                                $module_key,
                                '',
                                '',
                                true,
                                true,
                                '',
                                'white-box-content w96p',
                                'box-shadow white_table_graph w96p',
                            );
                        }

                        $node_name = $nodo['server_name'];
                        if ($count[$nodo['server_name']] > 1) {
                            $node_name .= ' ('.$nodo['dbhost'].')';
                        }

                        ui_toggle(
                            $agents,
                            '<span class="toggle-inventory-nodo">'.$node_name.'</span>',
                            '',
                            $cellId,
                            false,
                            false
                        );
                    }
                }
            } else {
                // Single agent selected.
                if ($inventory_id_agent > 0 && isset($agents[$inventory_id_agent]) === true) {
                    $agents = [$inventory_id_agent => $agents[$inventory_id_agent]];
                }

                $agents_ids = array_keys($agents);
                if (count($agents_ids) > 0) {
                    $rows = inventory_get_datatable(
                        $agents_ids,
                        $inventory_module,
                        $utimestamp,
                        $inventory_search_string,
                        false,
                        false,
                        $order_by_agent
                    );
                }

                if (count($agents_ids) === 0 || (int) $rows === ERR_NODATA || empty($rows) === true) {
                    ui_print_info_message(
                        [
                            'no_close' => true,
                            'message'  => __('No data found.'),
                        ]
                    );

                    return;
                }

                echo "<div id='loading_url' style='display: none; width: ".$table->width."; text-align: right;'>".html_print_image('images/spinner.gif', true).'</div>';
                ?>
                <script type="text/javascript">
                    function get_csv_url(module, id_group, search_string, utimestamp, agent, order_by_agent) {
                        $("#url_csv").hide();
                        $("#loading_url").show();
                        $.ajax ({
                            method:'GET',
                            url:'ajax.php',
                            datatype:'html',
                            data:{
                                "page" : "operation/inventory/inventory",
                                "get_csv_url" : 1,
                                "module" : module,
                                "id_group" : id_group,
                                "search_string" : search_string,
                                "utimestamp" : utimestamp,
                                "agent" : agent,
                                "export": true,
                                "order_by_agent": order_by_agent
                            },
                            success: function (data, status) {
                                $("#url_csv").html(data);
                                $("#loading_url").hide();
                                $("#url_csv").show();
                            }
                        });
                    }
                </script>
                <?php
                if ($order_by_agent === true) {
                    foreach ($rows as $agent_rows) {
                        $modules = '';
                        foreach ($agent_rows['row'] as $key_row => $row) {
                            $data = [];
                            $columns = explode(';', io_safe_output($row['data_format']));
                            array_push($columns, 'Timestamp');

                            $data_rows = explode(PHP_EOL, $row['data']);
                            foreach ($data_rows as $data_row) {
                                // Exclude results don't match filter.
                                if ($inventory_search_string && preg_match('/'.io_safe_output($inventory_search_string).'/', ($data_row)) == 0) {
                                    continue;
                                }

                                $column_data = explode(';', io_safe_output($data_row));

                                if ($column_data[0] !== '') {
                                    $row_tmp = [];
                                    foreach ($column_data as $key => $value) {
                                        $row_tmp[$columns[$key]] = $value;
                                    }

                                    $row_tmp['Timestamp'] = $row['timestamp'];
                                    array_push($data, (object) $row_tmp);
                                }
                            }

                            $id_table = 'id_'.$key_row.'_'.$row['id_module_inventory'].'_'.$row['id_agente'].'_'.$cellId;

                            $table = ui_print_datatable(
                                [
                                    'id'                  => $id_table,
                                    'class'               => 'info_table w96p',
                                    'style'               => 'width: 100%',
                                    'columns'             => $columns,
                                    'column_names'        => $columns,
                                    'no_sortable_columns' => [],
                                    'data_element'        => $data,
                                    'searching'           => true,
                                    'dom_elements'        => 'rtilp',
                                    'order'               => [
                                        'field'     => $columns[0],
                                        'direction' => 'asc',
                                    ],
                                    'zeroRecords'         => __('No inventory found'),
                                    'emptyTable'          => __('No inventory found'),
                                    'return'              => true,
                                    'no_sortable_columns' => [-1],
                                    'csv'                 => 0,
                                    'mini_pagination'     => true,
                                    'mini_search'         => true,
                                ]
                            );

                            $modules .= ui_toggle(
                                $table,
                                '<span class="title-blue">'.$row['name'].'</span>',
                                '',
                                '',
                                true,
                                true,
                                '',
                                'white-box-content w96p',
                                'box-shadow white_table_graph w96p',
                                'images/arrow_down_green.png',
                                'images/arrow_right_green.png',
                                false,
                                false,
                                false,
                                '',
                                '',
                                null,
                                null,
                                false,
                                $id_table
                            );
                        }

                        ui_toggle(
                            $modules,
                            $agent_rows['agent'],
                            '',
                            $cellId,
                            false,
                            false,
                            '',
                            'white-box-content',
                            'box-flat white_table_graph w96p'
                        );
                    }
                } else {
                    $count_rows = count($rows);
                    foreach ($rows as $module_rows) {
                        $agent = '';
                        $data = [];

                        foreach ($module_rows as $row) {
                            $columns = explode(';', io_safe_output($row['data_format']));
                            array_push($columns, 'Timestamp');
                            array_push($columns, 'Agent');

                            // Exclude results don't match filter.
                            if ($inventory_search_string && preg_match('/'.io_safe_output($inventory_search_string).'/', ($row['data'])) == 0) {
                                continue;
                            }

                            $data_tmp = [];
                            if ($row['data'] !== '') {
                                $values_explode = explode(';', io_safe_output($row['data']));

                                foreach ($values_explode as $key => $value) {
                                    $data_tmp[$columns[$key]] = $value;
                                }

                                $data_tmp['Timestamp'] = $row['timestamp'];
                                $data_tmp['Agent'] = $row['name_agent'];
                                array_push($data, $data_tmp);
                            }

                            $id_table = 'id_'.$row['id_module_inventory'].'_'.$cellId;
                        }

                        if ($count_rows > 1) {
                            $table = ui_print_datatable(
                                [
                                    'id'                  => $id_table,
                                    'class'               => 'info_table w96p',
                                    'style'               => 'width: 100%',
                                    'columns'             => $columns,
                                    'column_names'        => $columns,
                                    'no_sortable_columns' => [],
                                    'data_element'        => $data,
                                    'searching'           => false,
                                    'dom_elements'        => 'frtilp',
                                    'order'               => [
                                        'field'     => $columns[0],
                                        'direction' => 'asc',
                                    ],
                                    'zeroRecords'         => __('No inventory found'),
                                    'emptyTable'          => __('No inventory found'),
                                    'return'              => true,
                                    'no_sortable_columns' => [],
                                    'mini_search'         => false,
                                    'mini_pagination'     => true,
                                    'csv'                 => 0,
                                ]
                            );

                            ui_toggle(
                                $table,
                                array_shift($module_rows)['name'],
                                '',
                                $cellId,
                                false,
                                false
                            );
                        } else {
                            $table = ui_print_datatable(
                                [
                                    'id'                  => $id_table,
                                    'class'               => 'info_table w96p',
                                    'style'               => 'width: 100%',
                                    'columns'             => $columns,
                                    'column_names'        => $columns,
                                    'no_sortable_columns' => [],
                                    'data_element'        => $data,
                                    'searching'           => true,
                                    'dom_elements'        => 'rtilp',
                                    'order'               => [
                                        'field'     => $columns[0],
                                        'direction' => 'asc',
                                    ],
                                    'zeroRecords'         => __('No inventory found'),
                                    'emptyTable'          => __('No inventory found'),
                                    'csv'                 => 0,
                                    'mini_pagination'     => true,
                                    'mini_search'         => true,
                                ]
                            );
                        }
                    }
                }
            }
        } else {
            $id_agente = $inventory_id_agent;
            $agentes = [];
            $data = [];
            $class = 'info_table w96p';
            $style = 'width: 100%; font-size: 100px !important;';
            $ordering = true;
            $searching = false;

            $columns = [
                'alias',
                'ip',
                'secondoaryIp',
                'group',
                'secondaryGroups',
                'description',
                'os',
                'interval',
                'lastContact',
                'lastStatusChange',
                'customFields',
                'valuesCustomFields',
            ];

            $columns_names = [
                __('Alias'),
                __('IP'),
                __('Secondary IP'),
                __('Group'),
                __('Secondary groups'),
                __('Description'),
                __('OS'),
                __('Interval'),
                __('Last contact'),
                __('Last status change'),
                __('Custom fields'),
                __('Values Custom Fields'),
            ];

            $basic_info_id = 'id_'.$row['id_module_inventory'].'_'.$cellId;

            ui_print_datatable(
                [
                    'id'              => $basic_info_id,
                    'class'           => $class,
                    'style'           => $style,
                    'columns'         => $columns,
                    'column_names'    => $columns_names,
                    'ordering'        => $ordering,
                    'dom_elements'    => 'rtilp',
                    'searching'       => $searching,
                    'order'           => [
                        'field'     => $columns[0],
                        'direction' => 'asc',
                    ],
                    'ajax_url'        => 'operation/inventory/inventory',
                    'ajax_data'       => [
                        'get_data_basic_info' => 1,
                        'id_agent'            => $id_agente,
                        'id_group'            => $inventory_id_group,
                    ],
                    'zeroRecords'     => __('Agent info not found'),
                    'emptyTable'      => __('Agent info not found'),
                    'return'          => false,
                    'csv'             => 0,
                    'mini_pagination' => true,
                    'mini_search'     => true,
                ]
            );
            echo '</div>';
        }

        ui_require_jquery_file('pandora.controls');
        ui_require_jquery_file('ajaxqueue');
        ui_require_jquery_file('bgiframe');
    }


    /**
     * Aux javascript to be run after form load.
     *
     * @return string
     */
    public function getFormJS(): string
    {
        $javascript_ajax_page = ui_get_full_url('ajax.php', false, false, false);
        $selectbox_id = 'module_inventory';

        return '
        $(document).ready(function() {
            if ($("#hidden-agentId").val() < 1) {
                $("#text-agentAlias").val("'.__('All').'");
            }
            getInventoryModules();
          
            let clickedOnDynamicElement = false;
          
            $("[id^=\'ui-id-\']").on("click", function() {
              clickedOnDynamicElement = true;
              getInventoryModules();
            });
          
            $("#text-agentAlias").on("keyup", function(event) {
              if (!clickedOnDynamicElement || $("#text-agentAlias").val() === "") {
                getInventoryModules();
              } else {
                clickedOnDynamicElement = false;
              }
            });
          
            $("#text-agentAlias").focus(function() {
                    $("#hidden-agentId").val("0");
                });
            });

            function getInventoryModules() {
                const clickedId = $(this).attr(\'id\');

                $("#'.$selectbox_id.'").empty();

                if ($("#hidden-agentId").val() > 0 || $("#text-agentAlias").val() === "'.__('All').'") {
                    $("#module_inventory").enable();
                    var inputs = [];
                    var metaconsoleID = $("#hidden-metaconsoleId").val();
                    if (isNaN(metaconsoleID) === true) {
                        metaconsoleID = 0;
                    }
                    inputs.push("id_agent=" + $("#hidden-agentId").val());
                    inputs.push("get_agent_inventory_modules=1");
                    inputs.push("id_node=" + metaconsoleID);
                    inputs.push("page=operation/agentes/ver_agente");

                    jQuery.ajax({
                        data: inputs.join("&"),
                        type: "POST",
                        url: action="'.$javascript_ajax_page.'",
                        dataType: "json",
                        success: function (data) {
                            if (data) {
                                $("#'.$selectbox_id.'").append($("<option value=0>All</option>"));
                                $("#'.$selectbox_id.'").append($("<option value=\'basic\'>Basic info</option>"));
                                jQuery.each (data, function(id, value) {
                                    $("#'.$selectbox_id.'").append($("<option value=" + value.name + ">" + value.name + "</option>"));
                                });
                                $("#'.$selectbox_id.'").val("'.$this->values['inventoryModuleId'].'");
                                  
                            }
                        }
                    });
                } else {
                    $("#module_inventory").disable();
                }
                
                return false;
            }

            $("#module_inventory").change(function() {
                var inputs = [];

                $("#utimestamp").empty();
                $("#utimestamp").append($("<option value=\'0\'>'.__('Last').'</option>"));

                inputs.push("module=" + $(this).val());
                inputs.push("id_agent=" + $("#hidden-agentId").val());
                inputs.push("id_group=" + $("#id_group").val());
                inputs.push("get_agent_inventory_dates=1");
                inputs.push("page=operation/agentes/ver_agente");

                jQuery.ajax({
                    data: inputs.join("&"),
                    type: "POST",
                    url: action="'.$javascript_ajax_page.'",
                    dataType: "json",
                    success: function (data) {
                        if (data) {
                            jQuery.each (data, function(id, value) {
                                $("#utimestamp").append($("<option value=" + id + ">" + value + "</option>"));
                            });
                        }
                    }
                });
            });
        ';
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Inventory');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'inventory';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => 450,
            'height' => 330,
        ];

        return $size;
    }


}
