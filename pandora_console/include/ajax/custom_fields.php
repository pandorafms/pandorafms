<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Custom fields View
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

if (check_login()) {
    global $config;

    include_once $config['homedir'].'/include/functions_agents.php';
    include_once $config['homedir'].'/include/functions_modules.php';
    include_once $config['homedir'].'/include/functions_ui.php';
    include_once $config['homedir'].'/include/functions_custom_fields.php';
    enterprise_include_once('include/functions_metaconsole.php');

    $get_custom_fields_data = (bool) get_parameter('get_custom_fields_data', 0);
    $build_table_custom_fields = (bool) get_parameter(
        'build_table_custom_fields',
        0
    );
    $build_table_child_custom_fields = (bool) get_parameter(
        'build_table_child_custom_fields',
        0
    );
    $build_table_save_filter = (bool) get_parameter(
        'build_table_save_filter',
        0
    );
    $append_tab_filter = (bool) get_parameter('append_tab_filter', 0);
    $create_filter_cf = (bool) get_parameter('create_filter_cf', 0);
    $update_filter_cf = (bool) get_parameter('update_filter_cf', 0);
    $delete_filter_cf = (bool) get_parameter('delete_filter_cf', 0);
    $change_name_filter = (bool) get_parameter('change_name_filter', 0);
    $check_csv_button = (bool) get_parameter('check_csv_button', 0);

    if ($check_csv_button) {
        if (check_acl($config['id_user'], 0, 'PM')) {
            echo json_encode($permission);
            return;
        } else {
            exit;
        }
    }

    if ($get_custom_fields_data) {
        $name_custom_fields = get_parameter('name_custom_fields', 0);
        $array_custom_fields_data = get_custom_fields_data($name_custom_fields);
        echo json_encode($array_custom_fields_data);
        return;
    }

    if ($build_table_custom_fields) {
        $order = get_parameter('order', '');
        $length = get_parameter('length', 20);
        $start = get_parameter('start', 0);
        $draw = get_parameter('draw', 0);
        $search = get_parameter('search', '');
        $indexed_descriptions = json_decode(
            io_safe_output(
                get_parameter('indexed_descriptions', '')
            ),
            true
        );
        $module_status = get_parameter('module_status');
        $id_status = get_parameter('id_status');

        // Order query.
        $order_column = $order[0]['column'];
        $type_order = $order[0]['dir'];
        switch ($order_column) {
            default:
            case '1':
                $order_by = 'ORDER BY temp.name_custom_fields '.$type_order;
            break;
            case '4':
                $order_by = 'ORDER BY tma.server_name '.$type_order;
            break;

            case '2':
                $order_by = 'ORDER BY tma.alias '.$type_order;
            break;

            case '3':
                $order_by = 'ORDER BY tma.direccion '.$type_order;
            break;
        }

        // Table temporary for save array in table
        // by order and search custom_field data.
        $table_temporary = 'CREATE TEMPORARY TABLE temp_custom_fields (
		id_server int(10),
		id_agent int(10),
		name_custom_fields varchar(2048),
        critical_count int,
        warning_count int,
        unknown_count int,
        notinit_count int,
        normal_count int,
        total_count int,
		`status` int(2),
		KEY `data_index_temp_1` (`id_server`, `id_agent`)
	)';
        db_process_sql($table_temporary);

        // Insert values array in table temporary.
        $values_insert = [];
        foreach ($indexed_descriptions as $key => $value) {
            $values_insert[] = '('.$value['id_server'].', '.$value['id_agente'].", '".$value['description']."', '".$value['critical_count']."', '".$value['warning_count']."', '".$value['unknown_count']."', '".$value['notinit_count']."', '".$value['normal_count']."', '".$value['total_count']."', ".$value['status'].')';
        }

        $values_insert_implode = implode(',', $values_insert);
        $query_insert = 'INSERT INTO temp_custom_fields VALUES '.$values_insert_implode;
        db_process_sql($query_insert);

        // Search table for alias, custom field data, server_name, direction.
        $search_query = '';
        if ($search['value'] != '') {
            $search_query = ' AND (tma.alias LIKE "%'.$search['value'].'%"';
            $search_query .= ' OR tma.server_name LIKE "%'.$search['value'].'%"';
            $search_query .= ' OR tma.direccion LIKE "%'.$search['value'].'%"';
            $search_query .= ' OR temp.name_custom_fields LIKE "%'.$search['value'].'%" ) ';
        }

        // Search for status module.
        $status_agent_search = '';
        if (isset($id_status) === true && is_array($id_status) === true) {
            if (in_array(-1, $id_status) === false) {
                if (in_array(AGENT_MODULE_STATUS_NOT_NORMAL, $id_status) === false) {
                    $status_agent_search = ' AND temp.status IN ('.implode(',', $id_status).')';
                } else {
                    // Not normal statuses.
                    $status_agent_search = ' AND temp.status IN (1,2,3,4,5)';
                }
            }
        }

        // Search for status module.
        $status_module_search = '';
        if (isset($module_status) === true && is_array($module_status) === true) {
            if (in_array(-1, $module_status) === false) {
                if (in_array(AGENT_MODULE_STATUS_NOT_NORMAL, $module_status) === false) {
                    if (count($module_status) > 0) {
                        $status_module_search = ' AND ( ';
                        foreach ($module_status as $key => $value) {
                            $status_module_search .= ($key != 0) ? ' OR (' : ' (';
                            switch ($value) {
                                default:
                                case AGENT_STATUS_NORMAL:
                                    $status_module_search .= ' temp.normal_count > 0) ';
                                break;
                                case AGENT_STATUS_CRITICAL:
                                    $status_module_search .= ' temp.critical_count > 0) ';
                                break;

                                case AGENT_STATUS_WARNING:
                                    $status_module_search .= ' temp.warning_count > 0) ';
                                break;

                                case AGENT_STATUS_UNKNOWN:
                                    $status_module_search .= ' temp.unknown_count > 0) ';
                                break;

                                case AGENT_STATUS_NOT_INIT:
                                    $status_module_search .= ' temp.notinit_count > 0) ';
                                break;
                            }
                        }

                        $status_module_search .= ' ) ';
                    }
                } else {
                    // Not normal.
                    $status_module_search = ' AND ( temp.critical_count > 0 OR temp.warning_count > 0 OR temp.unknown_count > 0 AND temp.notinit_count > 0 )';
                }
            }
        }

        // Query all fields result.
        $query = sprintf(
            'SELECT
			tma.id_agente,
			tma.id_tagente,
			tma.id_tmetaconsole_setup,
			tma.alias,
			tma.direccion,
			tma.server_name,
			temp.name_custom_fields,
			temp.status
		FROM tmetaconsole_agent tma
		INNER JOIN temp_custom_fields temp
			ON temp.id_agent = tma.id_tagente
			AND temp.id_server = tma.id_tmetaconsole_setup
		WHERE tma.disabled = 0
		%s
        %s
        %s
		%s
		LIMIT %d OFFSET %d
		',
            $search_query,
            $status_agent_search,
            $status_module_search,
            $order_by,
            $length,
            $start
        );

        $result = db_get_all_rows_sql($query);

        // Query count.
        $query_count = sprintf(
            'SELECT
			COUNT(tma.id_agente) AS `count`
            FROM tmetaconsole_agent tma
            INNER JOIN temp_custom_fields temp
                ON temp.id_agent = tma.id_tagente
                AND temp.id_server = tma.id_tmetaconsole_setup
            WHERE tma.disabled = 0
            %s
            %s
            %s
            ',
            $search_query,
            $status_agent_search,
            $status_module_search
        );

        $count = db_get_sql($query_count);

        // For link nodes.
        $array_nodes = metaconsole_get_connections();
        if (isset($array_nodes) && is_array($array_nodes)) {
            $hash_array_nodes = [];
            foreach ($array_nodes as $key => $server) {
                $pwd = $server['auth_token'];
                $auth_serialized = json_decode($pwd, true);

                if (is_array($auth_serialized)) {
                    $pwd = $auth_serialized['auth_token'];
                    $api_password = $auth_serialized['api_password'];
                    $console_user = $auth_serialized['console_user'];
                    $console_password = $auth_serialized['console_password'];
                }

                $user = $config['id_user'];
                $user_rot13 = str_rot13($config['id_user']);
                $hashdata = $user.$pwd;
                $hashdata = md5($hashdata);
                $url_hash = '&amp;loginhash=auto&amp;loginhash_data='.$hashdata.'&amp;loginhash_user='.$user_rot13;

                $hash_array_nodes[$server['id']]['hashurl'] = $url_hash;
                $hash_array_nodes[$server['id']]['server_url'] = $server['server_url'];
            }
        }

        // Prepare rows for table dinamic.
        $data = [];
        foreach ($result as $values) {
            $image_status = agents_get_image_status($values['status']);

            // Link nodes.
            $agent_link = '<a href="'.$hash_array_nodes[$values['id_tmetaconsole_setup']]['server_url'].'/index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$values['id_tagente'].$hash_array_nodes[$values['id_tmetaconsole_setup']]['hashurl'].'">';

            $agent_alias = ui_print_truncate_text(
                $values['alias'],
                'agent_small',
                false,
                true,
                true,
                '[&hellip;]',
                'font-size:7.5pt;'
            );

            if (can_user_access_node()) {
                $agent = $agent_link.'<b>'.$agent_alias.'</b></a>';
            } else {
                $agent = $agent_alias;
            }

            $data[] = [
                'ref'               => $referencia,
                'data_custom_field' => ui_bbcode_to_html($values['name_custom_fields']),
                'server'            => $values['server_name'],
                'agent'             => $agent,
                'IP'                => $values['direccion'],
                'status'            => "<div id='reload_status_agent_".$values['id_tmetaconsole_setup'].'_'.$values['id_tagente']."'>".$image_status.'</div>',
                'id_agent'          => $values['id_tagente'],
                'id_server'         => $values['id_tmetaconsole_setup'],
                'status_value'      => $values['status'],
            ];
        }

        $result = [
            'draw'            => $draw,
            'recordsTotal'    => count($data),
            'recordsFiltered' => $count,
            'data'            => $data,
        ];
        echo json_encode($result);
        return;
    }

    if ($build_table_child_custom_fields) {
        $id_agent = get_parameter('id_agent', 0);
        $id_server = get_parameter('id_server', 0);
        $module_search = str_replace('amp;', '', get_parameter('module_search', ''));
        $module_status = get_parameter('module_status', 0);

        if (!$id_server || !$id_agent) {
            return false;
        }

        if ($module_search != '') {
            $name_where = " AND tam.nombre LIKE '%".$module_search."%'";
        }

        // Filter by status module.
        $and_module_status = '';
        if (is_array($module_status)) {
            if (!in_array(-1, $module_status)) {
                if (!in_array(AGENT_MODULE_STATUS_NOT_NORMAL, $module_status)) {
                    if (count($module_status) > 0) {
                        $and_module_status = ' AND ( ';
                        foreach ($module_status as $key => $value) {
                            $and_module_status .= ($key != 0) ? ' OR (' : ' (';
                            switch ($value) {
                                default:
                                case AGENT_STATUS_NORMAL:
                                    $and_module_status .= ' tae.estado = 0 OR tae.estado = 300 ) ';
                                break;
                                case AGENT_STATUS_CRITICAL:
                                    $and_module_status .= ' tae.estado = 1 OR tae.estado = 100 ) ';
                                break;

                                case AGENT_STATUS_WARNING:
                                    $and_module_status .= ' tae.estado = 2 OR tae.estado = 200 ) ';
                                break;

                                case AGENT_STATUS_UNKNOWN:
                                    $and_module_status .= ' tae.estado = 3 ) ';
                                break;

                                case AGENT_STATUS_NOT_INIT:
                                    $and_module_status .= ' tae.estado = 4 OR tae.estado = 5 ) ';
                                break;
                            }
                        }

                        $and_module_status .= ' ) ';
                    }
                } else {
                    // Not normal.
                    $and_module_status = 'AND tae.estado <> 0 AND tae.estado <> 300 ';
                }
            }
        }

        if (is_metaconsole()) {
            $server = metaconsole_get_connection_by_id($id_server);
            metaconsole_connect($server);
        }

        $query = sprintf(
            'SELECT tam.nombre,
			tam.min_warning, tam.max_warning,
			tam.min_critical, tam.max_critical,
			tam.str_warning, tam.str_critical,
			tam.id_tipo_modulo,
			tae.estado, tae.current_interval,
			tae.utimestamp, tae.datos
		FROM tagente_modulo tam
		INNER JOIN tagente_estado tae
			ON tam.id_agente_modulo = tae.id_agente_modulo
		WHERE tam.id_agente = %d
		%s %s',
            $id_agent,
            $name_where,
            $and_module_status
        );

        $modules = db_get_all_rows_sql($query);

        $table_modules = new stdClass();
        $table_modules->width = '100%';
        $table_modules->class = 'databox data';

        $table_modules->head = [];
        $table_modules->head[0] = __('Module name');
        $table_modules->head[1] = __('Data');
        $table_modules->head[2] = __('Threshold');
        $table_modules->head[3] = __('Current interval');
        $table_modules->head[4] = __('Timestamp');
        $table_modules->head[5] = __('Status');

        $table_modules->data = [];

        if (isset($modules) && is_array($modules)) {
            foreach ($modules as $key => $value) {
                $table_modules->data[$key][0] = $value['nombre'];
                if ($value['id_tipo_modulo'] != 3
                    && $value['id_tipo_modulo'] != 10
                    && $value['id_tipo_modulo'] != 17
                    && $value['id_tipo_modulo'] != 23
                    && $value['id_tipo_modulo'] != 33
                ) {
                    $table_modules->data[$key][1] = remove_right_zeros(
                        number_format(
                            $value['datos'],
                            $config['graph_precision'],
                            $config['decimal_separator'],
                            $config['thousand_separator']
                        )
                    );
                } else {
                    $table_modules->data[$key][1] = $value['datos'];
                }

                $table_modules->data[$key][2] = ui_print_module_warn_value(
                    $value['max_warning'],
                    $value['min_warning'],
                    $value['str_warning'],
                    $value['max_critical'],
                    $value['min_critical'],
                    $value['str_critical']
                );

                $table_modules->data[$key][3] = $value['current_interval'];
                $table_modules->data[$key][4] = ui_print_timestamp(
                    $value['utimestamp'],
                    true
                );
                switch ($value['estado']) {
                    case 0:
                    case 300:
                        $table_modules->data[$key][5] = html_print_image(
                            'images/status_sets/default/severity_normal.png',
                            true,
                            [
                                'title' => __('Modules normal'),
                            ]
                        );
                    break;

                    case 1:
                    case 100:
                        $table_modules->data[$key][5] = html_print_image(
                            'images/status_sets/default/severity_critical.png',
                            true,
                            [
                                'title' => __('Modules critical'),
                            ]
                        );
                    break;

                    case 2:
                    case 200:
                        $table_modules->data[$key][5] = html_print_image(
                            'images/status_sets/default/severity_warning.png',
                            true,
                            [
                                'title' => __('Modules warning'),
                            ]
                        );
                    break;

                    case 3:
                        $table_modules->data[$key][5] = html_print_image(
                            'images/status_sets/default/severity_maintenance.png',
                            true,
                            [
                                'title' => __('Modules unknown'),
                            ]
                        );
                    break;

                    case 4:
                    case 5:
                        $table_modules->data[$key][5] = html_print_image(
                            'images/status_sets/default/severity_informational.png',
                            true,
                            [
                                'title' => __('Modules no init'),
                            ]
                        );
                    break;

                    default:
                        $table_modules->data[$key][5] = html_print_image(
                            'images/status_sets/default/severity_normal.png',
                            true,
                            [
                                'title' => __('Modules normal'),
                            ]
                        );
                    break;
                }
            }
        }

        // Status agents from tagente.
        $sql_info_agents = 'SELECT * fROM tagente WHERE id_agente ='.$id_agent;
        $info_agents = db_get_row_sql($sql_info_agents);
        $status_agent = agents_get_status_from_counts($info_agents);

        if (is_metaconsole()) {
            metaconsole_restore_db();
        }

        $data['modules_table'] = html_print_table($table_modules, true);
        $data['img_status_agent'] = agents_get_image_status($status_agent);
        echo json_encode($data);
        return;
    }

    if ($build_table_save_filter) {
        $type_form = get_parameter('type_form', '');

        if ($type_form == 'save') {
            $tabs = '<div id="tabs" class="height_95p">';
            $tabs .= "<ul class='tab_save_filter'>";
                $tabs .= '<li>';
                    $tabs .= "<a href='#extended_create_filter' id='link_create'>";
                        $tabs .= html_print_image('images/lightning_go.png', true);
                        $tabs .= '<span>'.__('New Filter').'</span>';
                    $tabs .= '</a>';
                $tabs .= '</li>';

                $tabs .= '<li>';
                    $tabs .= "<a href='#extended_update_filter' id='link_update'>";
                        $tabs .= html_print_image('images/zoom.png', true);
                        $tabs .= '<span>'.__('Existing Filter').'</span>';
                    $tabs .= '</a>';
                $tabs .= '</li>';
            $tabs .= '</ul>';

            $tabs .= '<div id="extended_create_filter">';
            $tabs .= '</div>';
            $tabs .= '<div id="extended_update_filter">';
            $tabs .= '</div>';
            $tabs .= '</div>';
            echo $tabs;
        } else {
            $table = new StdClass;
            $table->id = 'save_filter_form';
            $table->width = '100%';
            $table->class = 'databox';

            $array_filters = get_filters_custom_fields_view(0, true);

            $table->data[0][0] = __('Filter name');
            $table->data[0][1] = html_print_select(
                $array_filters,
                'id_name',
                '',
                '',
                '',
                '',
                true,
                false,
                true,
                '',
                false
            );

            $table->data[0][3] = html_print_submit_button(
                __('Load filter'),
                'load_filter',
                false,
                'class="sub upd"',
                true
            );

            echo "<form action='' method='post'>";
            html_print_table($table);
            echo '</form>';
        }

        return;
    }

    if ($append_tab_filter) {
        $filters = json_decode(
            io_safe_output(
                get_parameter('filters', '')
            ),
            true
        );

        $table = new StdClass;
        $table->id = 'save_filter_form';
        $table->width = '100%';
        $table->class = 'databox';
        $table->rowspan = [];

        if ($filters['id'] == 'extended_create_filter') {
            echo "<div id='msg_error_create'></div>";
            $table->data[0][0] = __('Filter name');
            $table->data[0][1] = html_print_input_text(
                'id_name',
                '',
                '',
                15,
                255,
                true
            );

            $table->data[1][0] = __('Group');
            $table->data[1][1] = html_print_select_groups(
                $config['id_user'],
                'AR',
                true,
                'group_search_cr',
                0,
                '',
                '',
                '0',
                true,
                false,
                false,
                '',
                false,
                'width:180px;',
                false,
                false,
                'id_grupo',
                false
            );

            $table->rowspan[0][2] = 2;
            $table->data[0][2] = html_print_submit_button(
                __('Create filter'),
                'create_filter',
                false,
                'class="sub upd"',
                true
            );
        } else {
            echo "<div id='msg_error_update'></div>";
            echo "<div id='msg_error_delete'></div>";
            $array_filters = get_filters_custom_fields_view(0, true);
            $table->data[0][0] = __('Filter name');
            $table->data[0][1] = html_print_select(
                $array_filters,
                'id_name',
                '',
                'filter_name_change_group(this.value)',
                __('None'),
                -1,
                true,
                false,
                true,
                '',
                false
            );

            $table->data[1][0] = __('Group');
            $table->data[1][1] = html_print_select_groups(
                $config['id_user'],
                'AR',
                true,
                'group_search_up',
                $group,
                '',
                '',
                '0',
                true,
                false,
                false,
                '',
                false,
                'width:180px;',
                false,
                false,
                'id_grupo',
                false
            );

            $table->data[0][2] = html_print_submit_button(
                __('Delete filter'),
                'delete_filter',
                false,
                'class="sub upd"',
                true
            );
            $table->data[1][2] = html_print_submit_button(
                __('Update filter'),
                'update_filter',
                false,
                'class="sub upd"',
                true
            );
        }

        html_print_table($table);
        return;
    }

    if ($create_filter_cf) {
        // Initialize result.
        $result_array = [];
        $result_array['error'] = 0;
        $result_array['msg'] = '';

        // Initialize vars.
        $filters = json_decode(
            io_safe_output(get_parameter('filters', '')),
            true
        );
        $name_filter = get_parameter('name_filter', '');
        $group_search = get_parameter('group_search', 0);

        // Check that the name is not empty.
        if ($name_filter == '') {
            $result_array['error'] = 1;
            $result_array['msg'] = ui_print_error_message(
                __('The name must not be empty'),
                '',
                true
            );
            echo json_encode($result_array);
            return;
        }

        $name_exists = get_filters_custom_fields_view(0, false, $name_filter);

        if ($name_exists) {
            $result_array['error'] = 1;
            $result_array['msg'] = ui_print_error_message(
                __('Filter name already exists in the bbdd'),
                '',
                true
            );
            echo json_encode($result_array);
            return;
        }

        // Check custom field is not empty.
        if ($filters['id_custom_fields'] == '') {
            $result_array['error'] = 1;
            $result_array['msg'] = ui_print_error_message(
                __('Please, select a custom field'),
                '',
                true
            );
            echo json_encode($result_array);
            return;
        }

        // Insert.
        $values = [];
        $values['name'] = $name_filter;
        $values['group_search'] = $group_search;
        $values['id_group'] = $filters['group'];
        $values['id_custom_field'] = $filters['id_custom_fields'];
        $values['id_custom_fields_data'] = json_encode(
            $filters['id_custom_fields_data']
        );
        $values['id_status'] = json_encode($filters['id_status']);
        $values['module_search'] = $filters['module_search'];
        $values['module_status'] = json_encode($filters['module_status']);
        $values['recursion'] = $filters['recursion'];

        $insert = db_process_sql_insert('tagent_custom_fields_filter', $values);

        // Check error insert.
        if ($insert) {
            $result_array['error'] = 0;
            $result_array['msg'] = ui_print_success_message(
                __('Success create filter.'),
                '',
                true
            );
        } else {
            $result_array['error'] = 1;
            $result_array['msg'] = ui_print_error_message(
                __('Error create filter.'),
                '',
                true
            );
        }

        echo json_encode($result_array);
        return;
    }

    if ($update_filter_cf) {
        // Initialize result.
        $result_array = [];
        $result_array['error'] = 0;
        $result_array['msg'] = '';

        // Initialize vars.
        $filters = json_decode(io_safe_output(get_parameter('filters', '')), true);
        $id_filter = get_parameter('id_filter', '');
        $group_search = get_parameter('group_search', 0);

        // Check selected filter.
        if ($id_filter == -1) {
            $result_array['error'] = 1;
            $result_array['msg'] = ui_print_error_message(
                __('please, select a filter'),
                '',
                true
            );
            echo json_encode($result_array);
            return;
        }

        // Array condition update.
        $condition = [];
        $condition['id'] = $id_filter;

        // Check selected custom fields.
        if ($filters['id_custom_fields'] == '') {
            $result_array['error'] = 1;
            $result_array['msg'] = ui_print_error_message(
                __('please, select a custom field'),
                '',
                true
            );
            echo json_encode($result_array);
            return;
        }

        // Array values update.
        $values = [];
        $values['id_group'] = $filters['group'];
        $values['group_search'] = $group_search;
        $values['id_custom_field'] = $filters['id_custom_fields'];
        $values['id_custom_fields_data'] = json_encode($filters['id_custom_fields_data']);
        $values['id_status'] = json_encode($filters['id_status']);
        $values['module_search'] = $filters['module_search'];
        $values['module_status'] = json_encode($filters['module_status']);
        $values['recursion'] = $filters['recursion'];

        // Update.
        $update = db_process_sql_update('tagent_custom_fields_filter', $values, $condition);

        // Check error insert.
        if ($update) {
            $result_array['error'] = 0;
            $result_array['msg'] = ui_print_success_message(
                __('Success update filter.'),
                '',
                true
            );
        } else {
            $result_array['error'] = 1;
            $result_array['msg'] = ui_print_error_message(
                __('Error update filter.'),
                '',
                true
            );
        }

        echo json_encode($result_array);
        return;
    }

    if ($delete_filter_cf) {
        // Initialize result.
        $result_array = [];
        $result_array['error'] = 0;
        $result_array['msg'] = '';

        // Initialize vars.
        $filters = json_decode(io_safe_output(get_parameter('filters', '')), true);
        $id_filter = get_parameter('id_filter', '');

        // Check selected filter.
        if ($id_filter == -1) {
            $result_array['error'] = 1;
            $result_array['msg'] = ui_print_error_message(
                __('please, select a filter'),
                '',
                true
            );
            echo json_encode($result_array);
            return;
        }

        // Array condition update.
        $condition = [];
        $condition['id'] = $id_filter;

        // Delete.
        $delete = db_process_sql_delete('tagent_custom_fields_filter', $condition);

        // Check error insert.
        if ($delete) {
            $result_array['error'] = 0;
            $result_array['msg'] = ui_print_success_message(
                __('Success delete filter.'),
                '',
                true
            );
        } else {
            $result_array['error'] = 1;
            $result_array['msg'] = ui_print_error_message(
                __('Error delete filter.'),
                '',
                true
            );
        }

        echo json_encode($result_array);
        return;
    }

    if ($change_name_filter) {
        $id_filter = get_parameter('id_filter', 0);
        if (isset($id_filter)) {
            $res = get_group_filter_custom_field_view($id_filter);
            echo json_encode($res);
            return;
        }

        return json_encode(false);
    }
}
