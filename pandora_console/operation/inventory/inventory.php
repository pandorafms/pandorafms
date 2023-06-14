<?php
/**
 * Inventory view.
 *
 * @category   Monitoring.
 * @package    Pandora FMS
 * @subpackage Community
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

use PandoraFMS\Enterprise\Metaconsole\Node;

// Begin.
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_inventory.php';

if (is_ajax() === true) {
    $get_csv_url = (bool) get_parameter('get_csv_url');
    $get_data_basic_info = (bool) get_parameter('get_data_basic_info');

    if ($get_csv_url) {
        // $inventory_module = get_parameter ('module_inventory_general_view', 'all');
        $inventory_module = get_parameter('module', 'all');
        $inventory_id_group = (int) get_parameter('id_group', 0);
        // 0 is All groups
        $inventory_search_string = (string) get_parameter('search_string');
        $export = (string) get_parameter('export');
        $utimestamp = (int) get_parameter('utimestamp', 0);
        $inventory_agent = (string) get_parameter('agent', '');
        $order_by_agent = (boolean) get_parameter('order_by_agent', 0);

        // Agent select.
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
        if ($result !== false) {
            foreach ($result as $row) {
                $agents[$row['id_agente']] = $row['nombre'];
            }
        }

        $agents_select = $agents;

        if (strlen($inventory_agent) == 0) {
            $inventory_id_agent = -1;
            $inventory_agent = __('All');
        } else if ($inventory_agent == __('All')) {
            $inventory_id_agent = 0;
        } else {
            $sql = 'SELECT id_agente
                FROM tagente
                WHERE nombre LIKE "'.$inventory_agent.'"';

            $result = db_get_all_rows_sql($sql);
            $inventory_id_agent = $result[0]['id_agente'];
        }

        // Single agent selected.
        if ($inventory_id_agent > 0 && isset($agents[$inventory_id_agent]) === true) {
            $agents = [$inventory_id_agent => $agents[$inventory_id_agent]];
        }

        $agents_ids = array_keys($agents);
        if (count($agents_ids) > 0) {
            $inventory_data = inventory_get_data(
                $agents_ids,
                $inventory_module,
                $utimestamp,
                $inventory_search_string,
                $export,
                false,
                $order_by_agent
            );

            if ((int) $inventory_data === ERR_NODATA) {
                $inventory_data = '';
            }
        }

        return;
    }

    if ($get_data_basic_info === true) {
        // Datatables offset, limit and order.
        $filter = get_parameter('search', []);
        $start = (int) get_parameter('start', 0);
        $length = (int) get_parameter('length', $config['block_size']);
        $order = get_datatable_order();
        $id_agent = (int) get_parameter('id_agent', 0);
        $id_group = (int) get_parameter('id_group', 0);

        $params = [
            'search'   => $filter['value'],
            'start'    => $start,
            'length'   => $length,
            'order'    => $order,
            'id_agent' => $id_agent,
            'id_group' => $id_group,
        ];

        $data = get_data_basic_info_sql($params);
        $count = get_data_basic_info_sql($params, true);

        try {
            ob_start();
            $data = array_reduce(
                $data,
                function ($carry, $agent) {
                    // Transforms array of arrays $data into an array
                    // of objects, making a post-process of certain fields.
                    $tmp = new stdClass();

                    $tmp->alias = '';
                    if (is_metaconsole() === true) {
                        $server = metaconsole_get_connection_by_id($agent['id_tmetaconsole_setup']);
                        metaconsole_connect($server);
                        $tmp->alias .= $server['server_name'].' &raquo; ';
                    }

                    if (is_metaconsole() === true) {
                        $id = !empty($agent['id_agente']) ? $agent['id_agente'] : $agent['id_tagente'];
                    } else {
                        $id = !empty($agent['id_agente']) ? $agent['id_agente'] : $agent['id_agent'];
                    }

                    $tmp->alias .= $agent['alias'];
                    $ip = '<em>'.__('N/A').'</em>';
                    if (empty($agent['direccion']) === false) {
                        $ip = $agent['direccion'];
                    }

                    $tmp->ip = $ip;

                    $secondary_ips = '';
                    foreach (agents_get_addresses($id) as $ip) {
                        if ($ip !== $agent['direccion']) {
                            $secondary_ips .= '<span class="left" style="height: 1.3em !important">'.$ip.'</span>';
                        }
                    }

                    $tmp->secondaryIp = $secondary_ips;

                    $tmp->group = groups_get_name($agent['id_grupo']);
                    $secondary_groups = enterprise_hook('agents_get_secondary_groups', [$id]);

                    if (empty($secondary_groups['for_select']) === true) {
                        $sec_group_data = '<em>'.__('N/A').'</em>';
                    } else {
                        $sec_group = [];
                        foreach ($secondary_groups['for_select'] as $name) {
                            $sec_group[] = $name;
                        }

                        $sec_group_data = implode(', ', $sec_group);
                    }

                    $tmp->secondaryGroups = $sec_group_data;

                    $tmp->os = ui_print_os_icon(
                        $agent['id_os'],
                        false,
                        true,
                        true,
                        false,
                        false,
                        false,
                        ['class' => 'main_menu_icon invert_filter']
                    );

                    $interval = human_time_description_raw($agent['intervalo'], false, 'large');
                    $last_contact = ui_print_timestamp($agent['ultimo_contacto'], true);
                    // $last_contact .= ' / '.date_w_fixed_tz($agent['ultimo_contacto_remoto']);
                    $last_status_change_agent = agents_get_last_status_change($id);
                    $time_elapsed = !empty($last_status_change_agent) ? human_time_comparation($last_status_change_agent) : '<em>'.__('N/A').'</em>';

                    $sql_fields = 'SELECT tcf.name, tcd.description, tcf.is_password_type
                                    FROM tagent_custom_fields tcf
                                    INNER JOIN tagent_custom_data tcd ON tcd.id_field=tcf.id_field
                                    WHERE tcd.id_agent='.$id.' AND tcd.description!=""';
                    $field_result = db_get_all_rows_sql($sql_fields);

                    $custom_fields_names = '';
                    $custom_fields_values = '';
                    foreach ($field_result as $field) {
                        $field_name = str_replace(' ', '&nbsp;', io_safe_output($field['name']));
                        $custom_fields_names .= '<span class="right" style="height: 1.3em !important">'.$field_name.'</span>';

                        $description = $field['description'];
                        $password_length = strlen(io_safe_output($field['description']));
                        $asterisks = '';

                        if ((int) $field['is_password_type'] === 1) {
                            for ($i = 0; $i < $password_length; $i++) {
                                $asterisks .= '&#9679;';
                            }

                            $description = $asterisks;
                        }

                        $custom_fields_values .= '<span class="left" style="height: 1.3em !important">'.$description.'</span>';
                    }

                    $tmp->description = $agent['comentarios'];
                    $tmp->interval = $interval;
                    $tmp->lastContact = $last_contact;
                    $tmp->lastStatusChange = $time_elapsed;
                    $tmp->customFields = $custom_fields_names;
                    $tmp->valuesCustomFields = $custom_fields_values;

                    if (is_metaconsole() === true) {
                        metaconsole_restore_db();
                    }

                    $carry[] = $tmp;
                    return $carry;
                },
                []
            );

            // Datatables format: RecordsTotal && recordsfiltered.
            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $count,
                    'recordsFiltered' => $count,
                ]
            );
            // Capture output.
            $response = ob_get_clean();
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        // If not valid, show error with issue.
        json_decode($response);
        if (json_last_error() == JSON_ERROR_NONE) {
            // If valid dump.
            echo $response;
        } else {
            echo json_encode(
                ['error' => $response]
            );
        }

        return;
    }

    return;
}

global $config;

check_login();

$is_metaconsole = is_metaconsole();

if (! check_acl($config['id_user'], 0, 'AR') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Inventory'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_inventory.php';


// TODO: Button
// echo "<a href='javascript: get_csv_url(\"".$inventory_module.'",'.$inventory_id_group.','.'"'.$inventory_search_string.'",'.$utimestamp.','.'"'.$inventory_agent.'",'.$order_by_agent.")'><span>".__('Export this list to CSV').'</span>'.html_print_image('images/csv.png', true, ['title' => __('Export this list to CSV')]).'</a>';
// Header.
ui_print_standard_header(
    __('Inventory'),
    'images/op_inventory.png',
    false,
    '',
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Monitoring'),
        ],
    ]
);

$inventory_id_agent = (int) get_parameter('agent_id', -1);
$inventory_agent = (string) get_parameter('agent', '');
if (strlen($inventory_agent) == 0) {
    $inventory_id_agent = -1;
    $inventory_agent = __('All');
} else if ($inventory_agent == __('All')) {
    $inventory_id_agent = 0;
}

$inventory_module = get_parameter('module_inventory_general_view', 'basic');
$inventory_id_group = (int) get_parameter('id_group');
$inventory_search_string = (string) get_parameter('search_string');
$order_by_agent = (bool) get_parameter('order_by_agent');
$export = (string) get_parameter('export');
$utimestamp = (int) get_parameter('utimestamp');
$submit_filter = (bool) get_parameter('submit_filter');

$pagination_url_parameters = [
    'inventory_id_agent' => $inventory_id_agent,
    'inventory_agent'    => $inventory_agent,
    'inventory_id_group' => $inventory_id_group,
];

$noFilterSelected = false;
// Get variables.
if ($is_metaconsole === true) {
    $nodes_connection = metaconsole_get_connections();
    $id_server = (int) get_parameter('id_server', 0);
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
}

if ($is_metaconsole === true) {
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
        }

        $result_module = db_get_all_rows_sql($sql);
        if ($submit_filter === true) {
            // Get the data.
            $rows_meta = inventory_get_datatable(
                array_keys($agents_node),
                $inventory_module,
                $utimestamp,
                $inventory_search_string,
                $export,
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
            }

            $result = db_get_all_rows_sql($sql);

            if ($result !== false) {
                $result_module = array_merge($result_module, $result);
                if ($submit_filter === true) {
                    // Get the data.
                    $rows_meta = inventory_get_datatable(
                        array_keys($agents_node),
                        $inventory_module,
                        $utimestamp,
                        $inventory_search_string,
                        $export,
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

$agent_a = (bool) check_acl($config['id_user'], 0, 'AR');
$agent_w = (bool) check_acl($config['id_user'], 0, 'AW');
$access = ($agent_a === true) ? 'AR' : (($agent_w === true) ? 'AW' : 'AR');

$searchForm = '<form method="post" action="index.php?sec=estado&sec2=operation/inventory/inventory" name="form_inventory">';
$table = new stdClass();
$table->width = '100%';
$table->size = [];
$table->size[0] = '33%';
$table->size[1] = '33%';
$table->size[2] = '33%';
$table->class = 'filter-table-adv';
$table->data = [];

if ($is_metaconsole === true) {
    // Node select.
    $nodes = [];
    foreach ($nodes_connection as $row) {
        $nodes[$row['id']] = $row['server_name'];
    }

    $table->data[-1][0] = html_print_label_input_block(
        __('Server'),
        html_print_select(
            $nodes,
            'id_server',
            $id_server,
            $filteringFunction,
            __('All'),
            0,
            true,
            false,
            true,
            '',
            false,
            'width:100%;'
        )
    );
}

// Group select.
$table->data[0][0] = html_print_label_input_block(
    __('Group'),
    html_print_select_groups(
        $config['id_user'],
        $access,
        true,
        'id_group',
        $inventory_id_group,
        $filteringFunction,
        '',
        '1',
        true,
        false,
        true,
        '',
        false
    )
);

if ($is_metaconsole === true) {
    array_unshift($fields, __('All'));
    $module_input = html_print_select(
        $fields,
        'module_inventory_general_view',
        $inventory_module,
        $filteringFunction,
        __('Basic info'),
        'basic',
        true,
        false,
        true,
        '',
        false,
        'width:100%;'
    );
} else {
    $sql = 'SELECT name as indexname, name
	FROM tmodule_inventory, tagent_module_inventory
	WHERE tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory';
    if ($inventory_id_agent > 0) {
        $sql .= ' AND id_agente = '.$inventory_id_agent;
    }

    $fields = [];
    $result = db_get_all_rows_sql($sql);
    if ($result === false) {
        $result = [];
    }

    foreach ($result as $row) {
        $id = array_shift($row);
        $value = array_shift($row);
        $fields[$id] = $value;
    }

    array_unshift($fields, __('All'));
    $module_input = html_print_select(
        $fields,
        'module_inventory_general_view',
        $inventory_module,
        '',
        __('Basic info'),
        'basic',
        true,
        false,
        false,
        '',
        false,
        'width:100%;'
    );
}

// Agent select.
if ($is_metaconsole === false) {
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

$params = [];
$params['return'] = true;
$params['show_helptip'] = true;
$params['input_name'] = 'agent';
$params['value'] = $inventory_agent;
$params['selectbox_id'] = 'module_inventory_general_view';
$params['javascript_is_function_select'] = true;
$params['javascript_function_action_after_select'] = 'this.form.submit';
$params['use_hidden_input_idagent'] = true;
$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_id'] = 'hidden-autocomplete_id_agent';
$params['hidden_input_idagent_name'] = 'agent_id';
$params['hidden_input_idagent_value'] = $inventory_id_agent;
if ($is_metaconsole === true) {
    $params['print_input_id_server'] = true;
    $params['input_id_server_id'] = 'hidden-autocomplete_id_server';
    $params['input_id_server_name'] = 'id_server_agent';
    $params['input_id_server_value'] = $inventory_id_server;
    $params['metaconsole_enabled'] = true;
}

$table->data[0][1] = html_print_label_input_block(
    __('Agent'),
    ui_print_agent_autocomplete_input($params)
);

// Module selected.
$table->data[0][2] = html_print_label_input_block(
    __('Module'),
    $module_input
);

// String search_string.
$table->data[1][0] = html_print_label_input_block(
    __('Search'),
    html_print_input_text(
        'search_string',
        $inventory_search_string,
        '',
        25,
        0,
        true,
        false,
        false,
        '',
        '',
        $filteringFunction,
        'off',
        false,
        $filteringFunction
    )
);

// Order by agent filter.
$table->data[1][1] = html_print_label_input_block(
    __('Order by agent'),
    html_print_checkbox(
        'order_by_agent',
        1,
        $order_by_agent,
        true,
        false,
        ''
    )
);

// Date filter. In Metaconsole has not reason for show.
if (is_metaconsole() === false) {
    $dates = inventory_get_dates(
        $inventory_module,
        $inventory_id_agent,
        $inventory_id_group
    );
    $table->data[1][2] = html_print_label_input_block(
        __('Date'),
        html_print_select(
            $dates,
            'utimestamp',
            $utimestamp,
            '',
            __('Last'),
            0,
            true,
            false,
            false,
            '',
            false,
            'width:100%;'
        )
    );
}

$searchForm .= html_print_table($table, true);
$searchForm .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => html_print_submit_button(
            __('Filter'),
            'srcbutton',
            false,
            [
                'icon' => 'search',
                'mode' => 'mini',
            ],
            true
        ),
    ],
    true
);

$searchForm .= '</form>';

ui_toggle(
    $searchForm,
    '<span class="subsection_header_title">'.__('Filters').'</span>',
    'filter_form',
    '',
    true,
    false,
    '',
    'white-box-content',
    'box-flat white_table_graph fixed_filter_bar'
);

if (is_metaconsole() === true) {
    $filteringFunction = 'active_inventory_submit()';
    ui_print_info_message(['no_close' => true, 'message' => __('You must select at least one filter.'), 'force_class' => 'select_one_filter']);
    ?>
    <script type="text/javascript">
        function active_inventory_submit() {
            if (
                $("#id_group").val() == 0 &&
                $("#id_server").val() == 0 &&
                $("#module_inventory_general_view").val() == 0 &&
                $("#text-search_string").val() === ''
            ) {
                $("#submit-submit_filter").attr("disabled", true);
                $(".select_one_filter").css("display", "table");
            } else {
                $("#submit-submit_filter").attr("disabled", false);
                $(".select_one_filter").css("display", "none");
            }
        }
    </script>
    <?php
} else {
    $filteringFunction = '';
}

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
                    foreach ($agent_rows['row'] as $row) {
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

                        $id_table = 'id_'.$row['id_module_inventory'].'_'.$nodo['server_uid'];

                        $table = ui_print_datatable(
                            [
                                'id'                  => $id_table,
                                'class'               => 'info_table',
                                'style'               => 'width: 99%',
                                'columns'             => $columns,
                                'column_names'        => $columns,
                                'no_sortable_columns' => [],
                                'data_element'        => $data,
                                'searching'           => true,
                                'dom_elements'        => 'lftipB',
                                'order'               => [
                                    'field'     => $columns[0],
                                    'direction' => 'asc',
                                ],
                                'zeroRecords'         => __('No inventory found'),
                                'emptyTable'          => __('No inventory found'),
                                'return'              => true,
                                'default_pagination'  => 10,
                                'no_sortable_columns' => [-1],
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
                            'white-box-content w100p',
                            'box-shadow white_table_graph w100p',
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
                        true
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
                    '',
                    false,
                    false
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
                foreach ($nodo['data'] as $module_rows) {
                    $agent = '';
                    foreach ($module_rows as $row) {
                        $columns = explode(';', io_safe_output($row['data_format']));
                        array_push($columns, 'Timestamp');
                        $data = [];

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


                        $id_table = 'id_'.$row['id_module_inventory'].'_'.$nodo['server_uid'];

                        $table = ui_print_datatable(
                            [
                                'id'                  => $id_table,
                                'class'               => 'info_table',
                                'style'               => 'width: 99%',
                                'columns'             => $columns,
                                'column_names'        => $columns,
                                'no_sortable_columns' => [],
                                'data_element'        => $data,
                                'searching'           => true,
                                'dom_elements'        => 'lftipB',
                                'order'               => [
                                    'field'     => $columns[0],
                                    'direction' => 'asc',
                                ],
                                'zeroRecords'         => __('No inventory found'),
                                'emptyTable'          => __('No inventory found'),
                                'return'              => true,
                                'default_pagination'  => 10,
                                'no_sortable_columns' => [-1],
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
                            'white-box-content w100p',
                            'box-shadow white_table_graph w100p',
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
                        $agent,
                        $module_rows[0]['name'],
                        '',
                        '',
                        true,
                        true
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
                    '',
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
                $export,
                false,
                $order_by_agent
            );
        }

        if (count($agents_ids) === 0 || (int) $rows === ERR_NODATA) {
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

                    $id_table = 'id_'.$key_row.'_'.$row['id_module_inventory'].'_'.$row['id_agente'];

                    $table = ui_print_datatable(
                        [
                            'id'                  => $id_table,
                            'class'               => 'info_table w100p',
                            'style'               => 'width: 99%',
                            'columns'             => $columns,
                            'column_names'        => $columns,
                            'no_sortable_columns' => [],
                            'data_element'        => $data,
                            'searching'           => true,
                            'dom_elements'        => 'ftip',
                            'order'               => [
                                'field'     => $columns[0],
                                'direction' => 'asc',
                            ],
                            'zeroRecords'         => __('No inventory found'),
                            'emptyTable'          => __('No inventory found'),
                            'return'              => true,
                            'default_pagination'  => 10,
                            'no_sortable_columns' => [-1],
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
                        'white-box-content w100p',
                        'box-shadow white_table_graph w100p',
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
                    '',
                    false,
                    false
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


                    $id_table = 'id_'.$row['id_module_inventory'];
                }

                if ($count_rows > 1) {
                    $table = ui_print_datatable(
                        [
                            'id'                  => $id_table,
                            'class'               => 'info_table w100p',
                            'style'               => 'width: 99%',
                            'columns'             => $columns,
                            'column_names'        => $columns,
                            'no_sortable_columns' => [],
                            'data_element'        => $data,
                            'searching'           => true,
                            'dom_elements'        => 'ftip',
                            'order'               => [
                                'field'     => $columns[0],
                                'direction' => 'asc',
                            ],
                            'zeroRecords'         => __('No inventory found'),
                            'emptyTable'          => __('No inventory found'),
                            'return'              => true,
                            'no_sortable_columns' => [],
                        ]
                    );

                    ui_toggle(
                        $table,
                        array_shift($module_rows)['name'],
                        '',
                        '',
                        false,
                        false
                    );
                } else {
                    ui_print_datatable(
                        [
                            'id'                          => $id_table,
                            'class'                       => 'info_table w100p',
                            'style'                       => 'width: 99%',
                            'columns'                     => $columns,
                            'column_names'                => $columns,
                            'no_sortable_columns'         => [],
                            'data_element'                => $data,
                            'searching'                   => true,
                            'order'                       => [
                                'field'     => $columns[0],
                                'direction' => 'asc',
                            ],
                            'zeroRecords'                 => __('No inventory found'),
                            'emptyTable'                  => __('No inventory found'),
                            'print_pagination_search_csv' => true,
                        ]
                    );

                    html_print_action_buttons(
                        '',
                        ['type' => 'form_action']
                    );
                }
            }
        }
    }
} else {
    $id_agente = $inventory_id_agent;
    $agentes = [];
    $data = [];
    $class = 'info_table';
    $style = 'width: 99%';
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

    ui_print_datatable(
        [
            'id'           => 'basic_info',
            'class'        => $class,
            'style'        => $style,
            'columns'      => $columns,
            'column_names' => $columns_names,
            'ordering'     => $ordering,
            'searching'    => $searching,
            'order'        => [
                'field'     => $columns[0],
                'direction' => 'asc',
            ],
            'ajax_url'     => 'operation/inventory/inventory',
            'ajax_data'    => [
                'get_data_basic_info' => 1,
                'id_agent'            => $id_agente,
                'id_group'            => $inventory_id_group,
            ],
            'zeroRecords'  => __('Agent info not found'),
            'emptyTable'   => __('Agent info not found'),
            'return'       => false,
        ]
    );

    html_print_action_buttons(
        '',
        ['type' => 'form_action']
    );
}

ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');
?>

<script type="text/javascript">
/* <![CDATA[ */
    $(document).ready (function () {
        <?php if (is_metaconsole() === true) : ?>
        active_inventory_submit();
        <?php endif; ?>
        $("#id_group").click (
            function () {
                $(this).css ("width", "auto");
            }
        );

        $("#id_group").blur (function () {
            $(this).css ("width", "180px");
        });

        // Reduce margins between table and pagination.
        $('.dataTables_paginate.paging_simple_numbers').css('margin-top', 10);
        $('.dataTables_paginate.paging_simple_numbers').css('margin-bottom', 10);

        // Change chevron for node icon.
        let toggle = document.querySelectorAll('.toggle-inventory-nodo');
        let src = '<?php echo $nodo_image_url; ?>';

        toggle.forEach(img => {
            img.parentElement.parentElement.style = 'cursor: pointer; border: 0';
            img.parentElement.previousElementSibling.src = src;
        });
        
        toggle.forEach(divParent => {
            let div = divParent.parentElement.parentElement;
            $(div).click(function (e) {
                div.style = 'cursor: pointer; border: 0';
                div.firstChild.src = src;
            });
        });
    });
/* ]]> */
</script>
