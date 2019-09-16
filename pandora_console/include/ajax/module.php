<?php
/**
 * Module management.
 *
 * @category   Ajax library.
 * @package    Pandora FMS
 * @subpackage Modules.
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

// Begin.
if (check_login()) {
    global $config;

    include_once $config['homedir'].'/include/functions_agents.php';
    include_once $config['homedir'].'/include/functions_modules.php';
    include_once $config['homedir'].'/include/functions_ui.php';
    enterprise_include_once('include/functions_metaconsole.php');

    $get_plugin_macros = get_parameter('get_plugin_macros');
    $search_modules = get_parameter('search_modules');
    $get_module_detail = get_parameter('get_module_detail', 0);
    $get_module_autocomplete_input = (bool) get_parameter('get_module_autocomplete_input');
    $add_module_relation = (bool) get_parameter('add_module_relation');
    $remove_module_relation = (bool) get_parameter('remove_module_relation');
    $change_module_relation_updates = (bool) get_parameter('change_module_relation_updates');
    $get_id_tag = (bool) get_parameter('get_id_tag', 0);
    $get_type = (bool) get_parameter('get_type', 0);
    $list_modules = (bool) get_parameter('list_modules', 0);
    $get_agent_modules_json_by_name = (bool) get_parameter('get_agent_modules_json_by_name', 0);

    if ($get_agent_modules_json_by_name) {
        $agent_name = get_parameter('agent_name');

        $agent_id = agents_get_agent_id($agent_name);

        $agent_modules = db_get_all_rows_sql(
            'SELECT id_agente_modulo as id_module, nombre as name FROM tagente_modulo
											WHERE id_agente = '.$agent_id
        );

        echo json_encode($agent_modules);

        return;
    }

    if ($get_plugin_macros) {
        if (https_is_running()) {
            header('Content-type: application/json');
        }

        $id_plugin = get_parameter('id_plugin', 0);

        $plugin_macros = db_get_value(
            'macros',
            'tplugin',
            'id',
            $id_plugin
        );

        $macros = [];
        $macros['base64'] = base64_encode($plugin_macros);
        $macros['array'] = json_decode($plugin_macros, true);

        echo json_encode($macros);
        return;
    }

    if ($search_modules) {
        if (https_is_running()) {
            header('Content-type: application/json');
        }

        $id_agents = json_decode(io_safe_output(get_parameter('id_agents')));
        $filter = '%'.get_parameter('q', '').'%';
        $other_filter = json_decode(io_safe_output(get_parameter('other_filter')), true);
        // TODO TAGS agents_get_modules.
        $modules = agents_get_modules(
            $id_agents,
            false,
            (['tagente_modulo.nombre' => $filter] + $other_filter)
        );

        if ($modules === false) {
            $modules = [];
        }

        $modules = array_unique($modules);

        $modules = io_safe_output($modules);

        echo json_encode($modules);
        return;
    }

    if ($get_module_detail) {
        // This script is included manually to be included after jquery and avoid error.
        ui_include_time_picker();
        ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');

        $module_id = (int) get_parameter('id_module');
        $period = get_parameter('period', SECONDS_1DAY);
        if ($period === 'undefined') {
            $period = SECONDS_1DAY;
        } else {
            $period = (int) $period;
        }


        $group = agents_get_agentmodule_group($module_id);
        $agentId = (int) get_parameter('id_agent');
        $server_name = (string) get_parameter('server_name');

        if (is_metaconsole()) {
            $server = metaconsole_get_connection($server_name);

            if (metaconsole_connect($server) != NOERR) {
                return;
            }

            $conexion = false;
        } else {
            $conexion = false;
        }

        $freesearch = (string) get_parameter('freesearch', '');
        $free_checkbox = (bool) get_parameter('free_checkbox', false);
        $selection_mode = get_parameter('selection_mode', 'fromnow');
        $utimestamp = get_system_time();
        $date_from = (string) get_parameter('date_from', date(DATE_FORMAT, ($utimestamp - SECONDS_1DAY)));
        $time_from = (string) get_parameter('time_from', date(TIME_FORMAT, ($utimestamp - SECONDS_1DAY)));
        $date_to = (string) get_parameter('date_to', date(DATE_FORMAT, $utimestamp));
        $time_to = (string) get_parameter('time_to', date(TIME_FORMAT, $utimestamp));

        $formtable->width = '98%';
        $formtable->class = 'databox';
        $formtable->data = [];
        $formtable->size = [];

        $moduletype_name = modules_get_moduletype_name(
            modules_get_agentmodule_type($module_id)
        );

        $periods = [
            SECONDS_5MINUTES  => __('5 minutes'),
            SECONDS_30MINUTES => __('30 minutes'),
            SECONDS_1HOUR     => __('1 hour'),
            SECONDS_6HOURS    => __('6 hours'),
            SECONDS_12HOURS   => __('12 hours'),
            SECONDS_1DAY      => __('1 day'),
            SECONDS_1WEEK     => __('1 week'),
            SECONDS_15DAYS    => __('15 days'),
            SECONDS_1MONTH    => __('1 month'),
            SECONDS_3MONTHS   => __('3 months'),
            SECONDS_6MONTHS   => __('6 months'),
            SECONDS_1YEAR     => __('1 year'),
            SECONDS_2YEARS    => __('2 years'),
            SECONDS_3YEARS    => __('3 years'),
        ];

        $formtable->data[0][0] = html_print_radio_button_extended(
            'selection_mode',
            'fromnow',
            '',
            $selection_mode,
            false,
            '',
            'style="margin-right: 15px;"',
            true
        ).__('Choose a time from now');
        $formtable->data[0][1] = html_print_select($periods, 'period', $period, '', '', 0, true, false, false);
        $formtable->data[0][2] = '';
        $formtable->data[0][3] = "<a href='javascript: show_module_detail_dialog(".$module_id.', '.$agentId.', "'.$server_name.'", 0, -1,"'.modules_get_agentmodule_name($module_id)."\")'>".html_print_image('images/refresh.png', true, ['style' => 'vertical-align: middle;', 'border' => '0' ]).'</a>';
        $formtable->rowspan[0][3] = 2;
        $formtable->cellstyle[0][3] = 'vertical-align: middle;';

        $formtable->data[1][0] = html_print_radio_button_extended(
            'selection_mode',
            'range',
            '',
            $selection_mode,
            false,
            '',
            'style="margin-right: 15px;"',
            true
        ).__('Specify time range');
        $formtable->data[1][1] = __('Timestamp from:');

        $formtable->data[1][2] = html_print_input_text(
            'date_from',
            $date_from,
            '',
            10,
            10,
            true
        );
        $formtable->data[1][2] .= html_print_input_text(
            'time_from',
            $time_from,
            '',
            9,
            7,
            true
        );

        $formtable->data[1][1] .= '<br />';
        $formtable->data[1][1] .= __('Timestamp to:');

        $formtable->data[1][2] .= '<br />';
        $formtable->data[1][2] .= html_print_input_text(
            'date_to',
            $date_to,
            '',
            10,
            10,
            true
        );
        $formtable->data[1][2] .= html_print_input_text(
            'time_to',
            $time_to,
            '',
            9,
            7,
            true
        );

        $freesearch_object = '';
        if (preg_match('/_string/', $moduletype_name)) {
            $formtable->data[2][0] = __('Free search').' ';
            $formtable->data[2][1] = html_print_input_text('freesearch', $freesearch, '', 20, null, true);
            $formtable->data[2][2] = html_print_checkbox('free_checkbox', 1, $free_checkbox, true);
            $formtable->data[2][2] .= ' '.__('Exact phrase');
            $freesearch_object = json_encode(
                [
                    'value' => io_safe_output($freesearch),
                    'exact' => (bool) $free_checkbox,
                ]
            );
        }

        html_print_table($formtable);

        $offset = (int) get_parameter('offset');
        $block_size = (int) $config['block_size'];

        $columns = [];

        $datetime_from = strtotime($date_from.' '.$time_from);
        $datetime_to = strtotime($date_to.' '.$time_to);


        $columns = [
            'Data' => [
                'data',
                'modules_format_data',
                'align' => 'center',
                'width' => '230px'
            ],
        ];

        if ($config['prominent_time'] == 'comparation') {
            $columns['Time'] = [
                'utimestamp',
                'modules_format_time',
                'align' => 'center',
                'width' => '50px'
            ];
        } else {
            $columns['Timestamp'] = [
                'utimestamp',
                'modules_format_timestamp',
                'align' => 'center',
                'width' => '50px'
            ];
        }

        if ($selection_mode == 'fromnow') {
            $date = get_system_time();
            $period = $period;
        } else {
            $period = ($datetime_to - $datetime_from);
            $date = ($datetime_from + $period);
        }

        $count = modules_get_agentmodule_data(
            $module_id,
            $period,
            $date,
            true,
            $conexion,
            'ASC',
            $freesearch_object
        );

        $module_data = modules_get_agentmodule_data(
            $module_id,
            $period,
            $date,
            false,
            $conexion,
            'DESC',
            $freesearch_object
        );

        if (empty($module_data)) {
            $result = [];
        } else {
            // Paginate the result.
            $result = array_slice($module_data, $offset, $block_size);
        }

        $table->width = '100%';
        $table->class = 'databox data';
        $table->data = [];

        $index = 0;
        foreach ($columns as $col => $attr) {
            $table->head[$index] = $col;

            if (isset($attr['align'])) {
                $table->align[$index] = $attr['align'];
            }

            if (isset($attr['width'])) {
                $table->size[$index] = $attr['width'];
            }

            $index++;
        }

        $id_type_web_content_string = db_get_value(
            'id_tipo',
            'ttipo_modulo',
            'nombre',
            'web_content_string'
        );

        $post_process = db_get_value_filter('post_process', 'tagente_modulo', ['id_agente_modulo' => $module_id]);
        $unit = db_get_value_filter('unit', 'tagente_modulo', ['id_agente_modulo' => $module_id]);
        foreach ($result as $row) {
            $data = [];

            $is_web_content_string = (bool) db_get_value_filter(
                'id_agente_modulo',
                'tagente_modulo',
                [
                    'id_agente_modulo' => $row['id_agente_modulo'],
                    'id_tipo_modulo'   => $id_type_web_content_string,
                ]
            );

            foreach ($columns as $col => $attr) {
                if ($attr[1] != 'modules_format_data') {
                    $data[] = date('d F Y h:i:s A', $row['utimestamp']);
                } else if (is_snapshot_data($row[$attr[0]])) {
                    if ($config['command_snapshot']) {
                        $data[] = "<a target='_blank' href='".io_safe_input($row[$attr[0]])."'><img style='width:300px' src='".io_safe_input($row[$attr[0]])."'></a>";
                    } else {
                        $data[] = '<span>'.wordwrap(io_safe_input($row[$attr[0]]), 60, "<br>\n", true).'</span>';
                    }
                } else if (($config['command_snapshot'] == '0') && (preg_match("/[\n]+/i", $row[$attr[0]]))) {
                    // Its a single-data, multiline data (data snapshot) ?
                    // I dont why, but using index (value) method, data is automatically converted to html entities ¿?
                    $data[] = html_print_result_div($row[$attr[0]]);
                } else if ($is_web_content_string) {
                    // Fixed the goliat sends the strings from web
                    // without HTML entities.
                    $data[] = io_safe_input($row[$attr[0]]);
                } else {
                    // Fixed the data from Selenium Plugin.
                    if ($row[$attr[0]] != strip_tags($row[$attr[0]])) {
                        $data[] = html_print_result_div($row[$attr[0]]);
                    } else if (is_numeric($row[$attr[0]]) && !modules_is_string_type($row['module_type'])) {
                        switch ($row['module_type']) {
                            case 15:
                                $value = db_get_value('snmp_oid', 'tagente_modulo', 'id_agente_modulo', $module_id);
                                // System Uptime:
                                // In case of System Uptime module, shows data in format "Days hours minutes seconds" if and only if
                                // selected module unit is "_timeticks_"
                                // Take notice that selected unit may not be postrocess unit
                                if ($value == '.1.3.6.1.2.1.1.3.0' || $value == '.1.3.6.1.2.1.25.1.1.0') {
                                        $data_macro = modules_get_unit_macro($row[$attr[0]], $unit);
                                    if ($data_macro) {
                                        $data[] = $data_macro;
                                    } else {
                                        $data[] = remove_right_zeros(number_format($row[$attr[0]], $config['graph_precision']));
                                    }
                                } else {
                                    $data[] = remove_right_zeros(number_format($row[$attr[0]], $config['graph_precision']));
                                }
                            break;

                            default:
                                $data_macro = modules_get_unit_macro($row[$attr[0]], $unit);
                                if ($data_macro) {
                                    $data[] = $data_macro;
                                } else {
                                    $data[] = remove_right_zeros(number_format($row[$attr[0]], $config['graph_precision']));
                                }
                            break;
                        }
                    } else {
                        if ($row[$attr[0]] == '') {
                            $data[] = 'No data';
                        } else {
                            $data_macro = modules_get_unit_macro($row[$attr[0]], $unit);
                            if ($data_macro) {
                                $data[] = $data_macro;
                            } else {
                                $data[] = html_print_result_div($row[$attr[0]]);
                            }
                        }
                    }
                }
            }

            array_push($table->data, $data);
            if (count($table->data) > 200) {
                break;
            }
        }

        if (empty($table->data)) {
            ui_print_error_message(__('No available data to show'));
        } else {
            ui_pagination(count($count), false, $offset, 0, false, 'offset', true, 'binary_dialog');
            html_print_table($table);
        }

        if (is_metaconsole()) {
            metaconsole_restore_db();
        }

        return;
    }

    if ($get_module_autocomplete_input) {
        $id_agent = (int) get_parameter('id_agent');

        ob_clean();
        if ($id_agent > 0) {
            html_print_autocomplete_modules(
                'autocomplete_module_name',
                '',
                [$id_agent]
            );
            return;
        }

        return;
    }

    if ($add_module_relation) {
        $result = false;
        $id_module_a = (int) get_parameter('id_module_a');
        $id_module_b = (int) get_parameter('id_module_b');
        $type = (string) get_parameter('relation_type');

        if ($id_module_a < 1) {
            $name_module_a = get_parameter('name_module_a', '');
            if ($name_module_a) {
                $id_module_a = (int) db_get_value(
                    'id_agente_modulo',
                    'tagente_modulo',
                    'nombre',
                    $name_module_a
                );
            } else {
                echo json_encode($result);
                return;
            }
        }

        if ($id_module_b < 1) {
            $name_module_b = get_parameter('name_module_b', '');
            if ($name_module_b) {
                $id_module_b = (int) db_get_value(
                    'id_agente_modulo',
                    'tagente_modulo',
                    'nombre',
                    $name_module_b
                );
            } else {
                echo json_encode($result);
                return;
            }
        }

        if ($id_module_a > 0 && $id_module_b > 0) {
            $result = modules_add_relation($id_module_a, $id_module_b, $type);
        }

        echo json_encode($result);
        return;
    }

    if ($remove_module_relation) {
        $id_relation = (int) get_parameter('id_relation');
        if ($id_relation > 0) {
            $result = (bool) modules_delete_relation($id_relation);
        }

        echo json_encode($result);
        return;
    }

    if ($change_module_relation_updates) {
        $id_relation = (int) get_parameter('id_relation');
        if ($id_relation > 0) {
            $result = (bool) modules_change_relation_lock($id_relation);
        }

        echo json_encode($result);
        return;
    }

    if ($get_id_tag) {
        $tag_name = get_parameter('tag_name');

        if ($tag_name) {
            $tag_id = db_get_value('id_tag', 'ttag', 'name', $tag_name);
        } else {
            $tag_id = 0;
        }

        echo $tag_id;
        return;
    }

    if ($list_modules) {
        include_once $config['homedir'].'/include/functions_modules.php';
        include_once $config['homedir'].'/include/functions_servers.php';
        include_once $config['homedir'].'/include/functions_tags.php';
        include_once $config['homedir'].'/include/functions_clippy.php';

        $agent_a = check_acl($config['id_user'], 0, 'AR');
        $agent_w = check_acl($config['id_user'], 0, 'AW');
        $access = ($agent_a == true) ? 'AR' : (($agent_w == true) ? 'AW' : 'AR');

        $id_agent = (int) get_parameter('id_agente', 0);
        $id_agente = $id_agent;
        $show_notinit = (int) get_parameter('show_notinit', 0);
        $cluster_list = (int) get_parameter('cluster_list', 0);
        $url = 'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent;
        $selectTypeUp = false;
        $selectTypeDown = false;
        $selectNameUp = false;
        $selectNameDown = false;
        $selectStatusUp = false;
        $selectStatusDown = false;
        $selectDataUp = false;
        $selectDataDown = false;
        $selectLastContactUp = false;
        $selectLastContactDown = false;
        $sortField = get_parameter('sort_field');
        $sort = get_parameter('sort', 'none');
        $selected = true;

        $order[] = [
            'field' => 'tmodule_group.name',
            'order' => 'ASC',
        ];
        switch ($sortField) {
            case 'type':
                switch ($sort) {
                    case 'up':
                    default:
                        $selectTypeUp = $selected;
                        $order[] = [
                            'field' => 'tagente_modulo.id_modulo',
                            'order' => 'ASC',
                        ];
                    break;

                    case 'down':
                        $selectTypeDown = $selected;
                        $order[] = [
                            'field' => 'tagente_modulo.id_modulo',
                            'order' => 'DESC',
                        ];
                    break;
                }
            break;

            case 'name':
                switch ($sort) {
                    case 'up':
                        $selectNameUp = $selected;
                        $order[] = [
                            'field' => 'tagente_modulo.nombre',
                            'order' => 'ASC',
                        ];
                    break;

                    case 'down':
                        $selectNameDown = $selected;
                        $order[] = [
                            'field' => 'tagente_modulo.nombre',
                            'order' => 'DESC',
                        ];
                    break;

                    default:
                        // Ignore.
                    break;
                }
            break;

            case 'status':
                switch ($sort) {
                    case 'up':
                        $selectStatusUp = $selected;
                        $order[] = [
                            'field' => 'tagente_estado.estado=0 DESC,tagente_estado.estado=3 DESC,tagente_estado.estado=2 DESC,tagente_estado.estado=1 DESC',
                            'order' => '',
                        ];
                    break;

                    case 'down':
                        $selectStatusDown = $selected;
                        $order[] = [
                            'field' => 'tagente_estado.estado=1 DESC,tagente_estado.estado=2 DESC,tagente_estado.estado=3 DESC,tagente_estado.estado=0 DESC',
                            'order' => '',
                        ];
                    break;

                    default:
                        // Ignore.
                    break;
                }
            break;

            case 'last_contact':
                switch ($sort) {
                    case 'up':
                        $selectLastContactUp = $selected;
                        $order[] = [
                            'field' => 'tagente_estado.utimestamp',
                            'order' => 'ASC',
                        ];
                    break;

                    case 'down':
                        $selectLastContactDown = $selected;
                        $order[] = [
                            'field' => 'tagente_estado.utimestamp',
                            'order' => 'DESC',
                        ];
                    break;

                    default:
                        // Ignore.
                    break;
                }
            break;

            default:
                $selectTypeUp = false;
                $selectTypeDown = false;
                $selectNameUp = $selected;
                $selectNameDown = false;
                $selectStatusUp = false;
                $selectStatusDown = false;
                $selectDataUp = false;
                $selectDataDown = false;
                $selectLastContactUp = false;
                $selectLastContactDown = false;

                $order[] = [
                    'field' => 'tagente_modulo.nombre',
                    'order' => 'ASC',
                ];
            break;
        }

        // Fix: for tag functionality groups have to be all user_groups
        // (propagate ACL funct!).
        $groups = users_get_groups($config['id_user'], $access);

        $tags_join = '';
        $tags_sql = '';
        if ($cluster_list != 1) {
            $tags = tags_get_user_applied_agent_tags($id_agent, $access);
            if ($tags === false) {
                $tags_sql = ' AND 1=0';
            } else if (is_array($tags)) {
                $tags_sql = ' AND ttag_module.id_tag IN ('.implode(',', $tags).')';
                $tags_join = 'LEFT JOIN ttag_module
				ON ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo';
            }
        }

        $status_filter_monitor = (int) get_parameter('status_filter_monitor', -1);
        $status_text_monitor = get_parameter('status_text_monitor', '');
        $filter_monitors = (bool) get_parameter('filter_monitors', false);
        $status_module_group = get_parameter('status_module_group', -1);
        $monitors_change_filter = (bool) get_parameter('monitors_change_filter', false);

        $status_filter_sql = '1 = 1';
        if ($status_filter_monitor == AGENT_MODULE_STATUS_NOT_NORMAL) {
            // Not normal.
            $status_filter_sql = ' tagente_estado.estado <> 0';
        } else if ($status_filter_monitor != -1) {
            $status_filter_sql = 'tagente_estado.estado = '.$status_filter_monitor;
        }

        if ($status_module_group != -1) {
            $status_module_group_filter = 'tagente_modulo.id_module_group = '.$status_module_group;
        } else {
            $status_module_group_filter = 'tagente_modulo.id_module_group >= 0';
        }

        $status_text_monitor_sql = '%';
        if (!empty($status_text_monitor)) {
            $status_text_monitor_sql .= $status_text_monitor.'%';
        }

        if (!$show_notinit) {
            $monitor_filter = AGENT_MODULE_STATUS_NO_DATA;
        } else {
            $monitor_filter = -15;
        }

        // Count monitors/modules
        // Build the order sql.
        $first = true;
        foreach ($order as $ord) {
            if ($first) {
                $first = false;
            } else {
                $order_sql .= ',';
            }

            $order_sql .= $ord['field'].' '.$ord['order'];
        }

        $sql_condition = "FROM tagente_modulo
		$tags_join
		INNER JOIN tagente_estado
			ON tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
		LEFT JOIN tmodule_group
			ON tagente_modulo.id_module_group = tmodule_group.id_mg
		WHERE tagente_modulo.id_agente = $id_agente
			AND nombre LIKE '$status_text_monitor_sql'
			AND delete_pending = 0
			AND disabled = 0
			AND $status_filter_sql
			AND $status_module_group_filter
			$tags_sql
			AND tagente_estado.estado != $monitor_filter
		";

        $count_modules = db_get_all_rows_sql('SELECT COUNT(DISTINCT tagente_modulo.id_agente_modulo)'.$sql_condition);

        if (isset($count_modules[0])) {
            $count_modules = reset($count_modules[0]);
        } else {
            $count_modules = 0;
        }

        // Get monitors/modules
        // Get all module from agent
        $sql_modules_info = "SELECT tagente_estado.*, tagente_modulo.*, tmodule_group.* 
		$sql_condition
		GROUP BY tagente_modulo.id_agente_modulo ORDER BY $order_sql";

        if ($monitors_change_filter) {
            $limit = ' LIMIT '.$config['block_size'].' OFFSET 0';
        } else {
            $limit = ' LIMIT '.$config['block_size'].' OFFSET '.get_parameter('offset', 0);
        }

        $paginate_module = false;
        if (isset($config['paginate_module'])) {
            $paginate_module = $config['paginate_module'];
        }

        if ($paginate_module) {
            $modules = db_get_all_rows_sql($sql_modules_info.$limit);
        } else {
            $modules = db_get_all_rows_sql($sql_modules_info);
        }

        if (empty($modules)) {
            $modules = [];
        }

        // Urls to sort the table.
        $url_up_type = $url.'&sort_field=type&amp;sort=up&refr=&filter_monitors=1&status_filter_monitor='.$status_filter_monitor.' &status_text_monitor='.$status_text_monitor.'&status_module_group= '.$status_module_group;
        $url_down_type = $url.'&sort_field=type&amp;sort=down&refr=&filter_monitors=1&status_filter_monitor='.$status_filter_monitor.' &status_text_monitor='.$status_text_monitor.'&status_module_group= '.$status_module_group;
        $url_up_name = $url.'&sort_field=name&amp;sort=up&refr=&filter_monitors=1&status_filter_monitor='.$status_filter_monitor.' &status_text_monitor='.$status_text_monitor.'&status_module_group= '.$status_module_group;
        $url_down_name = $url.'&sort_field=name&amp;sort=down&refr=&filter_monitors=1&status_filter_monitor='.$status_filter_monitor.' &status_text_monitor='.$status_text_monitor.'&status_module_group= '.$status_module_group;
        $url_up_status = $url.'&sort_field=status&amp;sort=up&refr=&filter_monitors=1&status_filter_monitor='.$status_filter_monitor.' &status_text_monitor='.$status_text_monitor.'&status_module_group= '.$status_module_group;
        $url_down_status = $url.'&sort_field=status&amp;sort=down&refr=&filter_monitors=1&status_filter_monitor='.$status_filter_monitor.' &status_text_monitor='.$status_text_monitor.'&status_module_group= '.$status_module_group;
        $url_up_last = $url.'&sort_field=last_contact&amp;sort=up&refr=&filter_monitors=1&status_filter_monitor='.$status_filter_monitor.' &status_text_monitor='.$status_text_monitor.'&status_module_group= '.$status_module_group;
        $url_down_last = $url.'&sort_field=last_contact&amp;sort=down&refr=&filter_monitors=1&status_filter_monitor='.$status_filter_monitor.' &status_text_monitor='.$status_text_monitor.'&status_module_group= '.$status_module_group;


        $table = new stdClass();
        $table->width = '100%';
        $table->styleTable = 'border: 0;border-radius: 0;';
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->class = 'info_table';
        $table->head = [];
        $table->data = [];

        $isFunctionPolicies = enterprise_include_once('include/functions_policies.php');
        if ($agent_w) {
            $table->head[0] = "<span title='".__('Force execution')."'>".__('F.').'</span>';
        }

        if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
            $table->head[1] = "<span title='".__('Policy')."'>".__('P.').'</span>';
        }

        $table->head[2] = __('Type').ui_get_sorting_arrows($url_up_type, $url_down_type, $selectTypeUp, $selectTypeDown);
        $table->head[3] = __('Module name').ui_get_sorting_arrows($url_up_name, $url_down_name, $selectNameUp, $selectNameDown);
        $table->head[4] = __('Description');
        $table->head[5] = __('Status').ui_get_sorting_arrows($url_up_status, $url_down_status, $selectStatusUp, $selectStatusDown);
        $table->head[6] = __('Thresholds');
        $table->head[7] = __('Data');
        $table->head[8] = __('Graph');
        $table->head[9] = __('Last contact').ui_get_sorting_arrows($url_up_last, $url_down_last, $selectLastContactUp, $selectLastContactDown);
        $table->align = [];
        $table->align[0] = 'center';
        $table->align[1] = 'left';
        $table->align[2] = 'left';
        $table->align[3] = 'left';
        $table->align[4] = 'left';
        $table->align[5] = 'left';
        $table->align[6] = 'center';
        $table->align[7] = 'left';
        $table->align[8] = 'center';
        $table->align[9] = 'right';

        $table->headstyle[2] = 'min-width: 85px';
        $table->headstyle[3] = 'min-width: 130px';
        $table->size[3] = '30%';
        $table->style[3] = 'max-width: 28em;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;';
        $table->size[4] = '30%';
        $table->headstyle[5] = 'min-width: 85px';
        $table->headstyle[6] = 'min-width: 125px; text-align: center;';
        $table->headstyle[7] = 'min-width: 125px;';
        $table->headstyle[8] = 'min-width: 100px; text-align: center;';
        $table->headstyle[9] = 'min-width: 120px; text-align: right;';

        $last_modulegroup = 0;
        $rowIndex = 0;


        $id_type_web_content_string = db_get_value(
            'id_tipo',
            'ttipo_modulo',
            'nombre',
            'web_content_string'
        );

        $show_context_help_first_time = false;

        $hierachy_mode = get_parameter('hierachy_mode', false);

        if ($hierachy_mode == 'true') {
            $modules_hierachy = [];
            $modules_hierachy = get_hierachy_modules_tree($modules);

            $modules_dt = get_dt_from_modules_tree($modules_hierachy);

            $modules = $modules_dt;
        }

        foreach ($modules as $module) {
            if ($hierachy_mode !== 'true') {
                // The code add the row of 1 cell with title of group for to be more organice the list.
                if ($module['id_module_group'] != $last_modulegroup) {
                    $table->colspan[$rowIndex][0] = count($table->head);
                    $table->rowclass[$rowIndex] = 'datos4';

                    array_push($table->data, ['<b>'.$module['name'].'</b>']);

                    $rowIndex++;
                    $last_modulegroup = $module['id_module_group'];
                }

                // End of title of group.
            }

            $data = [];
            if (($module['id_modulo'] != 1) && ($module['id_tipo_modulo'] != 100)) {
                if ($agent_w) {
                    if ($module['flag'] == 0) {
                        $data[0] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;id_agente_modulo='.$module['id_agente_modulo'].'&amp;flag=1&amp;refr=60">'.html_print_image('images/target.png', true, ['border' => '0', 'title' => __('Force')]).'</a>';
                    } else {
                        $data[0] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;id_agente_modulo='.$module['id_agente_modulo'].'&amp;refr=60">'.html_print_image('images/refresh.png', true, ['border' => '0', 'title' => __('Refresh')]).'</a>';
                    }
                }
            } else {
                if ($agent_w) {
                    $data[0] = '';
                }
            }

            if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
                if ($module['id_policy_module'] != 0) {
                    $linked = policies_is_module_linked($module['id_agente_modulo']);
                    $id_policy = db_get_value_sql('SELECT id_policy FROM tpolicy_modules WHERE id = '.$module['id_policy_module']);

                    if ($id_policy != '') {
                        $name_policy = db_get_value_sql('SELECT name FROM tpolicies WHERE id = '.$id_policy);
                    } else {
                        $name_policy = __('Unknown');
                    }

                    $policyInfo = policies_info_module_policy($module['id_policy_module']);

                    $adopt = false;
                    if (policies_is_module_adopt($module['id_agente_modulo'])) {
                        $adopt = true;
                    }

                    if ($linked) {
                        if ($adopt) {
                            $img = 'images/policies_brick.png';
                            $title = '('.__('Adopted').') '.$name_policy;
                        } else {
                            $img = 'images/policies.png';
                            $title = $name_policy;
                        }
                    } else {
                        if ($adopt) {
                            $img = 'images/policies_not_brick.png';
                            $title = '('.__('Unlinked').') ('.__('Adopted').') '.$name_policy;
                        } else {
                            $img = 'images/unlinkpolicy.png';
                            $title = '('.__('Unlinked').') '.$name_policy;
                        }
                    }

                    $data[1] = '<a href="?sec=gmodules&amp;sec2=enterprise/godmode/policies/policies&amp;id='.$id_policy.'">'.html_print_image($img, true, ['title' => $title]).'</a>';
                } else {
                    $data[1] = '';
                }
            }

            $data[2] = servers_show_type($module['id_modulo']).'&nbsp;';

            if (check_acl($config['id_user'], $id_grupo, 'AW')) {
                $data[2] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$id_agente.'&amp;tab=module&amp;id_agent_module='.$module['id_agente_modulo'].'&amp;edit_module='.$module['id_modulo'].'">'.html_print_image('images/config.png', true, ['alt' => '0', 'border' => '', 'title' => __('Edit'), 'class' => 'action_button_img']).'</a>';
            }



            $data[3] = '';

            if (isset($module['deep']) && ($module['deep'] != 0)) {
                $data[3] .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $module['deep']);
                $data[3] .= html_print_image('images/icono_escuadra.png', true, ['style' => 'padding-bottom: inherit;']).'&nbsp;&nbsp;';
            }

            if ($module['quiet']) {
                $data[3] .= html_print_image(
                    'images/dot_blue.png',
                    true,
                    [
                        'border' => '0',
                        'title'  => __('Quiet'),
                        'alt'    => '',
                    ]
                ).'&nbsp;';
            }

            $data[3] .= ui_print_truncate_text($module['nombre'], 'module_medium');
            if (!empty($module['extended_info'])) {
                if ($module['extended_info'] != '') {
                    $data[3] .= ui_print_help_tip($module['extended_info'], true, '/images/default_list.png');
                }
            }

            // Adds tag context information.
            if (tags_get_modules_tag_count($module['id_agente_modulo']) > 0) {
                $data[3] .= ' <a class="tag_details" href="ajax.php?page=operation/agentes/estado_monitores&get_tag_tooltip=1&id_agente_modulo='.$module['id_agente_modulo'].'">'.html_print_image('images/tag_red.png', true, ['id' => 'tag-details-'.$module['id_agente_modulo'], 'class' => 'img_help']).'</a> ';
            }

            // Adds relations context information.
            if (modules_relation_exists($module['id_agente_modulo'])) {
                $data[3] .= ' <a class="relations_details" href="ajax.php?page=operation/agentes/estado_monitores&get_relations_tooltip=1&id_agente_modulo='.$module['id_agente_modulo'].'">'.html_print_image('images/link2.png', true, ['id' => 'relations-details-'.$module['id_agente_modulo'], 'class' => 'img_help']).'</a> ';
            }


            $data[4] = ui_print_string_substr($module['descripcion'], 60, true, 8);


            if ($module['datos'] != strip_tags($module['datos'])) {
                $module_value = io_safe_input($module['datos']);
            } else {
                $module_value = io_safe_output($module['datos']);
            }

            modules_get_status(
                $module['id_agente_modulo'],
                $module['estado'],
                $module_value,
                $status,
                $title
            );

            $data[5] = ui_print_status_image($status, $title, true);
            if (!$show_context_help_first_time) {
                $show_context_help_first_time = true;

                if ($module['estado'] == AGENT_MODULE_STATUS_UNKNOWN) {
                    $data[5] .= clippy_context_help('module_unknow');
                }
            }

            if (is_numeric($module['datos']) && !modules_is_string_type($module['id_tipo_modulo'])) {
                if ($config['render_proc']) {
                    switch ($module['id_tipo_modulo']) {
                        case 2:
                        case 6:
                        case 9:
                        case 18:
                        case 21:
                        case 31:
                            if ($module['datos'] >= 1) {
                                $salida = $config['render_proc_ok'];
                            } else {
                                $salida = $config['render_proc_fail'];
                            }
                        break;

                        default:
                            switch ($module['id_tipo_modulo']) {
                                case 15:
                                    $value = db_get_value('snmp_oid', 'tagente_modulo', 'id_agente_modulo', $module['id_agente_modulo']);
                                    if ($value == '.1.3.6.1.2.1.1.3.0' || $value == '.1.3.6.1.2.1.25.1.1.0') {
                                        if ($module['post_process'] > 0) {
                                            $salida = human_milliseconds_to_string(($module['datos'] / $module['post_process']));
                                        } else {
                                            $salida = human_milliseconds_to_string($module['datos']);
                                        }
                                    } else {
                                        $salida = remove_right_zeros(number_format($module['datos'], $config['graph_precision']));
                                    }
                                break;

                                default:
                                    $salida = remove_right_zeros(number_format($module['datos'], $config['graph_precision']));
                                break;
                            }
                        break;
                    }
                } else {
                    switch ($module['id_tipo_modulo']) {
                        case 15:
                            $value = db_get_value('snmp_oid', 'tagente_modulo', 'id_agente_modulo', $module['id_agente_modulo']);
                            if ($value == '.1.3.6.1.2.1.1.3.0' || $value == '.1.3.6.1.2.1.25.1.1.0') {
                                if ($module['post_process'] > 0) {
                                    $salida = human_milliseconds_to_string(($module['datos'] / $module['post_process']));
                                } else {
                                    $salida = human_milliseconds_to_string($module['datos']);
                                }
                            } else {
                                $salida = remove_right_zeros(number_format($module['datos'], $config['graph_precision']));
                            }
                        break;

                        default:
                            $salida = remove_right_zeros(number_format($module['datos'], $config['graph_precision']));
                        break;
                    }
                }

                // Show units ONLY in numeric data types
                if (isset($module['unit'])) {
                    $data_macro = modules_get_unit_macro($module['datos'], $module['unit']);
                    if ($data_macro) {
                        $salida = $data_macro;
                    } else {
                        $salida .= '&nbsp;<i>'.io_safe_output($module['unit']).'</i>';
                    }
                }
            } else {
                $data_macro = modules_get_unit_macro($module['datos'], $module['unit']);
                if ($data_macro) {
                    $salida = $data_macro;
                } else {
                    $salida = ui_print_module_string_value(
                        $module['datos'],
                        $module['id_agente_modulo'],
                        $module['current_interval'],
                        $module['module_name']
                    );
                }
            }

            if ($module['id_tipo_modulo'] != 25) {
                $data[6] = ui_print_module_warn_value($module['max_warning'], $module['min_warning'], $module['str_warning'], $module['max_critical'], $module['min_critical'], $module['str_critical']);
            } else {
                $data[6] = '';
            }

            $data[7] = $salida;
            $graph_type = return_graphtype($module['id_tipo_modulo']);

            $data[8] = ' ';
            if ($module['history_data'] == 1) {
                $nombre_tipo_modulo = modules_get_moduletype_name($module['id_tipo_modulo']);
                $handle = 'stat'.$nombre_tipo_modulo.'_'.$module['id_agente_modulo'];
                $url = 'include/procesos.php?agente='.$module['id_agente_modulo'];
                $win_handle = dechex(crc32($module['id_agente_modulo'].$module['nombre']));

                // Try to display the SNMP module realtime graph
                $rt_button = get_module_realtime_link_graph($module);
                if (!empty($rt_button)) {
                    $data[8] = $rt_button.'&nbsp;&nbsp;';
                }

                // Show events for boolean modules by default.
                if ($graph_type == 'boolean') {
                    $draw_events = 1;
                } else {
                    $draw_events = 0;
                }

                $link = "winopeng_var('".'operation/agentes/stat_win.php?'."type=$graph_type&amp;".'period='.SECONDS_1DAY.'&amp;id='.$module['id_agente_modulo'].'&amp;label='.rawurlencode(
                    urlencode(
                        base64_encode($module['nombre'])
                    )
                ).'&amp;refresh='.SECONDS_10MINUTES.'&amp;'."draw_events=$draw_events', 'day_".$win_handle."', 1000, 700)";
                if (!is_snapshot_data($module['datos'])) {
                    $data[8] .= '<a href="javascript:'.$link.'">'.html_print_image('images/chart_curve.png', true, ['border' => '0', 'alt' => '']).'</a> &nbsp;&nbsp;';
                }

                $server_name = '';

                $modules_get_agentmodule_name = modules_get_agentmodule_name($module['id_agente_modulo']);
                // Escape the double quotes that may have the name of the module.
                $modules_get_agentmodule_name = str_replace('&quot;', '\"', $modules_get_agentmodule_name);

                $data[8] .= "<a href='javascript: ".'show_module_detail_dialog('.$module['id_agente_modulo'].', '.$id_agente.', "'.$server_name.'", '.(0).', '.SECONDS_1DAY.', " '.$modules_get_agentmodule_name."\")'>".html_print_image('images/binary.png', true, ['border' => '0', 'alt' => '']).'</a>';
            }

            if ($module['estado'] == 3) {
                $data[9] = '<span class="redb">';
            } else {
                $data[9] = '<span>';
            }

            $data[9] .= ui_print_timestamp($module['utimestamp'], true, ['style' => 'font-size: 7pt']);
            $data[9] .= '</span>';

            array_push($table->data, $data);
            $rowIndex++;
        }

        ?>
    <script type="text/javascript">
        /* <![CDATA[ */
        $("a.tag_details").cluetip ({
            arrows: true,
            attribute: 'href',
            cluetipClass: 'default'
        })
        .click (function () {
            return false;
        });
        $("a.relations_details").cluetip ({
            width: 500,
            arrows: true,
            attribute: 'href',
            cluetipClass: 'default'
        })
        .click (function () {
            return false;
        });
        
        /* ]]> */
    </script>
        <?php
        if (empty($table->data)) {
            if ($filter_monitors) {
                ui_print_info_message([ 'no_close' => true, 'message' => __('Any monitors aren\'t with this filter.') ]);
            } else {
                ui_print_info_message([ 'no_close' => true, 'message' => __('This agent doesn\'t have any active monitors.') ]);
            }
        } else {
            $url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&refr=&filter_monitors=1&status_filter_monitor='.$status_filter_monitor.'&status_text_monitor='.$status_text_monitor.'&status_module_group='.$status_module_group;

            if ($paginate_module) {
                ui_pagination(
                    $count_modules,
                    false,
                    0,
                    0,
                    false,
                    'offset',
                    true,
                    '',
                    'pagination_list_modules(offset_param)',
                    [
                        'count'  => '',
                        'offset' => 'offset_param',
                    ]
                );
            }

            html_print_table($table);

            if ($paginate_module) {
                ui_pagination(
                    $count_modules,
                    false,
                    0,
                    0,
                    false,
                    'offset',
                    true,
                    'pagination-bottom',
                    'pagination_list_modules(offset_param)',
                    [
                        'count'  => '',
                        'offset' => 'offset_param',
                    ]
                );
            }
        }

        unset($table);
        unset($table_data);
    }

    if ($get_type) {
        $id_module = (int) get_parameter('id_module');
        $module = modules_get_agentmodule($id_module);
        $graph_type = return_graphtype($module['id_tipo_modulo']);
        echo $graph_type;
        return;
    }
}
