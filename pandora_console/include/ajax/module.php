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

use PandoraFMS\Enterprise\Metaconsole\Node;
use PandoraFMS\Agent;

// Begin.
if (check_login()) {
    global $config;

    include_once $config['homedir'].'/include/functions_agents.php';
    include_once $config['homedir'].'/include/functions_modules.php';
    include_once $config['homedir'].'/include/functions_ui.php';
    include_once $config['homedir'].'/include/functions_macros.php';
    enterprise_include_once('include/functions_metaconsole.php');

    $get_plugin_macros = get_parameter('get_plugin_macros');
    $get_module_macros = get_parameter('get_module_macros');
    $is_policy = (bool) get_parameter('is_policy', 0);
    $search_modules = get_parameter('search_modules');
    $get_module_detail = get_parameter('get_module_detail', 0);
    $get_module_autocomplete_input = (bool) get_parameter(
        'get_module_autocomplete_input'
    );
    $add_module_relation = (bool) get_parameter('add_module_relation');
    $remove_module_relation = (bool) get_parameter('remove_module_relation');
    $change_module_relation_updates = (bool) get_parameter(
        'change_module_relation_updates'
    );
    $get_id_tag = (bool) get_parameter('get_id_tag', 0);
    $get_type = (bool) get_parameter('get_type', 0);
    $list_modules = (bool) get_parameter('list_modules', 0);
    $get_agent_modules_json_by_name = (bool) get_parameter(
        'get_agent_modules_json_by_name',
        0
    );
    $get_graph_module = (bool) get_parameter('get_graph_module', 0);
    $get_graph_module_interfaces = (bool) get_parameter(
        'get_graph_module_interfaces',
        0
    );

    $get_children_modules = (bool) get_parameter('get_children_modules', false);

    $get_data_dataMatrix = (bool) get_parameter(
        'get_data_dataMatrix',
        0
    );

    $get_data_ModulesByStatus = (bool) get_parameter(
        'get_data_ModulesByStatus',
        0
    );

    $load_filter_modal = get_parameter('load_filter_modal', 0);
    $save_filter_modal = get_parameter('save_filter_modal', 0);
    $get_monitor_filters = get_parameter('get_monitor_filters', 0);
    $save_monitor_filter = get_parameter('save_monitor_filter', 0);
    $update_monitor_filter = get_parameter('update_monitor_filter', 0);
    $delete_monitor_filter = get_parameter('delete_monitor_filter', 0);
    $get_cluster_module_detail = (bool) get_parameter('get_cluster_module_detail', 0);
    $get_combo_modules = (bool) get_parameter('get_combo_modules', false);

    if ($get_agent_modules_json_by_name === true) {
        $agent_name = get_parameter('agent_name');

        $agent_id = agents_get_agent_id($agent_name);

        $agent_modules = db_get_all_rows_sql(
            'SELECT id_agente_modulo as id_module,
                nombre as name FROM tagente_modulo
			WHERE id_agente = '.$agent_id
        );

        echo json_encode($agent_modules);

        return;
    }

    $id_plugin = get_parameter('id_plugin', 0);

    if ($id_plugin !== 0) {
        if ($is_policy === true) {
            $id_module_plugin = db_get_value(
                'id_plugin',
                'tpolicy_modules',
                'id',
                $get_module_macros
            );
        } else {
            $id_module_plugin = db_get_value(
                'id_plugin',
                'tagente_modulo',
                'id_agente_modulo',
                $get_module_macros
            );
        }

        if ($id_plugin !== $id_module_plugin) {
            $get_plugin_macros = true;
            $get_module_macros = 0;
        }
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

    if ($get_module_macros && $get_module_macros > 0) {
        if (https_is_running()) {
            header('Content-type: application/json');
        }

        $module_id = $get_module_macros;

        if ($is_policy === true) {
            $module_macros = db_get_value(
                'macros',
                'tpolicy_modules',
                'id',
                $module_id
            );
        } else {
            $module_macros = db_get_value(
                'macros',
                'tagente_modulo',
                'id_agente_modulo',
                $module_id
            );
        }

        $macros = [];
        $macros['base64'] = base64_encode($module_macros);
        $macros['array'] = json_decode($module_macros, true);

        echo json_encode($macros);
        return;
    }

    if ($search_modules) {
        if (https_is_running()) {
            header('Content-type: application/json');
        }

        $id_agents = json_decode(io_safe_output(get_parameter('id_agents')));
        $filter = '%'.get_parameter('q', '').'%';
        $other_filter = json_decode(
            io_safe_output(get_parameter('other_filter')),
            true
        );
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
        // This script is included manually to be
        // included after jquery and avoid error.
        ui_include_time_picker();
        ui_require_jquery_file(
            'ui.datepicker-'.get_user_language(),
            'include/javascript/i18n/'
        );

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
        $date_from = (string) get_parameter(
            'date_from',
            date(DATE_FORMAT, ($utimestamp - SECONDS_1DAY))
        );
        $time_from = (string) get_parameter(
            'time_from',
            date(TIME_FORMAT, ($utimestamp - SECONDS_1DAY))
        );
        $date_to = (string) get_parameter(
            'date_to',
            date(DATE_FORMAT, $utimestamp)
        );
        $time_to = (string) get_parameter(
            'time_to',
            date(TIME_FORMAT, $utimestamp)
        );

        // Definition of new table.
        $formtable = new stdClass();

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
        $formtable->data[0][0] = '<div style="display:flex;align-items:center;font-weight:bold;">'.html_print_radio_button_extended(
            'selection_mode',
            'fromnow',
            '',
            $selection_mode,
            false,
            '',
            'class="mrgn_right_15px"',
            true
        ).__('Choose a time from now').'</div>';
        $formtable->colspan[0][0] = 2;
        $formtable->data[0][2] = html_print_select(
            $periods,
            'period',
            $period,
            '',
            '',
            0,
            true,
            false,
            false
        );
        $formtable->data[0][3] = "<a href='javascript: show_module_detail_dialog(".$module_id.', '.$agentId.', "'.$server_name.'", 0, -1,"'.modules_get_agentmodule_name($module_id)."\")'>".html_print_image('images/refresh@svg.svg', true, ['style' => 'vertical-align: middle;', 'border' => '0', 'class' => 'main_menu_icon invert_filter' ]).'</a>';
        $formtable->rowspan[0][3] = 2;
        $formtable->cellstyle[0][3] = 'vertical-align: middle;';

        $formtable->data[1][0] = '<div style="display:flex;align-items:center;font-weight:bold;">'.html_print_radio_button_extended(
            'selection_mode',
            'range',
            '',
            $selection_mode,
            false,
            '',
            'class="mrgn_right_15px"',
            true
        ).__('Specify time range');
        $formtable->data[1][1] = '<span style="font-weight:bold">'.__('Timestamp from:').'</span></div>';

        $formtable->data[1][2] = '<div class="inputs_date_details">'.html_print_input_text(
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
            8,
            true
        );

        $formtable->data[1][1] .= '<br />';
        $formtable->data[1][1] .= '<span style="font-weight:bold">'.__('Timestamp to:').'</span>';

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
            8,
            true
        ).'</div>';

        $freesearch_object = '';
        if (preg_match('/_string/', $moduletype_name)) {
            $formtable->data[2][0] = __('Free search').' ';
            $formtable->data[2][1] = html_print_input_text(
                'freesearch',
                $freesearch,
                '',
                20,
                null,
                true
            );
            $formtable->data[2][2] = html_print_checkbox(
                'free_checkbox',
                1,
                $free_checkbox,
                true
            );
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

        if ($selection_mode === 'fromnow') {
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

        // Definition of new class.
        $table = new stdClass();

        $table->width = '100%';
        $table->class = 'databox data';
        $table->data = [];

        $index = 0;
        foreach ($columns as $col => $attr) {
            $table->head[$index] = $col;
            if ($col === 'Data') {
                $table->head[$index] .= ui_print_help_tip(
                    __('In Pandora FMS, data is stored compressed. The data visualization in database, charts or CSV exported data won\'t match, because is interpreted at runtime. Please check \'Pandora FMS Engineering\' chapter from documentation.'),
                    true
                );
            }

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

        $post_process = db_get_value_filter(
            'post_process',
            'tagente_modulo',
            ['id_agente_modulo' => $module_id]
        );

        $made_enabled = db_get_value_filter(
            'made_enabled',
            'tagente_modulo',
            ['id_agente_modulo' => $module_id]
        );

        $unit = db_get_value_filter(
            'unit',
            'tagente_modulo',
            ['id_agente_modulo' => $module_id]
        );
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
                        $imagetab = '<img class="w100p" src="';
                        $imagetab .= io_safe_input($row[$attr[0]]);
                        $imagetab .= '">';
                        $image = '<img class="w300px" src="';
                        $image .= io_safe_input($row[$attr[0]]);
                        $image .= '">';
                        $data[] = '<a style="cursor:pointer;" onclick="newTabjs(\''.base64_encode($imagetab).'\')">'.$image.'</a>';
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
                    } else if (is_numeric($row[$attr[0]])
                        && !modules_is_string_type($row['module_type'])
                    ) {
                        switch ($row['module_type']) {
                            case 15:
                                $value = db_get_value(
                                    'snmp_oid',
                                    'tagente_modulo',
                                    'id_agente_modulo',
                                    $module_id
                                );
                                // System Uptime:
                                // In case of System Uptime module,
                                // shows data in format
                                // "Days hours minutes seconds" if and only if
                                // selected module unit is "_timeticks_"
                                // Take notice that selected unit
                                // may not be postrocess unit.
                                if ($value == '.1.3.6.1.2.1.1.3.0'
                                    || $value == '.1.3.6.1.2.1.25.1.1.0'
                                ) {
                                        $data_macro = modules_get_unit_macro(
                                            $row[$attr[0]],
                                            $unit
                                        );
                                    if ($data_macro) {
                                        $data[] = $data_macro;
                                    } else {
                                        $data[] = remove_right_zeros(
                                            number_format(
                                                $row[$attr[0]],
                                                $config['graph_precision'],
                                                $config['decimal_separator'],
                                                $config['thousand_separator']
                                            )
                                        );
                                    }
                                } else {
                                    $data[] = remove_right_zeros(
                                        number_format(
                                            $row[$attr[0]],
                                            $config['graph_precision'],
                                            $config['decimal_separator'],
                                            $config['thousand_separator']
                                        )
                                    );
                                }
                            break;

                            default:
                                $data_macro = modules_get_unit_macro(
                                    $row[$attr[0]],
                                    $unit
                                );
                                if ($data_macro) {
                                    $data[] = $data_macro;
                                } else {
                                    $data[] = remove_right_zeros(
                                        number_format(
                                            $row[$attr[0]],
                                            $config['graph_precision'],
                                            $config['decimal_separator'],
                                            $config['thousand_separator']
                                        )
                                    );
                                }
                            break;
                        }
                    } else {
                        if ($row[$attr[0]] == '') {
                            $data[] = 'No data';
                        } else {
                            $data_macro = modules_get_unit_macro(
                                $row[$attr[0]],
                                $unit
                            );
                            if ($data_macro) {
                                $data[] = $data_macro;
                            } else {
                                $data[] = html_print_result_div(
                                    $row[$attr[0]]
                                );
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
            ui_print_empty_data(__('No available data to show'), '', false);
        } else {
            ui_pagination(
                count($count),
                false,
                $offset,
                0,
                false,
                'offset',
                true,
                'binary_dialog'
            );
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

        $agent_a = (bool) check_acl($config['id_user'], 0, 'AR');
        $agent_w = (bool) check_acl($config['id_user'], 0, 'AW');
        $access = ($agent_a === true) ? 'AR' : (($agent_w === true) ? 'AW' : 'AR');
        $id_agent = (int) get_parameter('id_agente');
        $id_agente = $id_agent;
        $id_grupo = agents_get_agent_group($id_agent);
        $show_notinit = (bool) get_parameter('show_notinit');
        $cluster_list = (int) get_parameter('cluster_list');
        $sortField = (string) get_parameter('sort_field');
        $sort = (string) get_parameter('sort', 'none');

        // Disable module edition in cluster module list.
        $cluster_view = false;
        $url = 'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent;
        if ((int) agents_get_os($id_agent) === CLUSTER_OS_ID) {
            $cluster = PandoraFMS\Cluster::loadFromAgentId($id_agent);
            $url = sprintf(
                'index.php?sec=estado&sec2=operation/cluster/cluster&op=view&id=%s',
                $cluster->id()
            );
            $cluster_view = true;
        }

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
        $status_text_monitor = (string) get_parameter('status_text_monitor');
        $filter_monitors = (bool) get_parameter('filter_monitors');
        $status_module_group = (int) get_parameter('status_module_group', -1);
        $monitors_change_filter = (bool) get_parameter('monitors_change_filter');

        $status_filter_sql = '1 = 1';
        if ($status_filter_monitor === AGENT_MODULE_STATUS_NOT_NORMAL) {
            // Not normal.
            $status_filter_sql = ' tagente_estado.estado <> 0';
        } else if ($status_filter_monitor !== -1) {
            $status_filter_sql = 'tagente_estado.estado = '.$status_filter_monitor;
        }

        if ($status_module_group !== -1) {
            $status_module_group_filter = 'tagente_modulo.id_module_group = '.$status_module_group;
        } else {
            $status_module_group_filter = 'tagente_modulo.id_module_group >= 0';
        }

        $status_text_monitor_sql = '%';
        if (empty($status_text_monitor) === false) {
            $status_text_monitor_sql .= $status_text_monitor.'%';
        }

        $monitor_filter = (($show_notinit === false) ? AGENT_MODULE_STATUS_NO_DATA : -15);

        // Count monitors/modules
        // Build the order sql.
        $first = true;
        foreach ($order as $ord) {
            if ($first === true) {
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

        $count_modules = db_get_all_rows_sql(
            'SELECT COUNT(DISTINCT tagente_modulo.id_agente_modulo)'.$sql_condition
        );

        $count_modules = (isset($count_modules[0]) === true) ? reset($count_modules[0]) : 0;

        // Get monitors/modules
        // Get all module from agent.
        $sql_modules_info = "SELECT tagente_estado.*, tagente_modulo.*, tmodule_group.* 
		$sql_condition
		GROUP BY tagente_modulo.id_agente_modulo ORDER BY $order_sql";

        if ($monitors_change_filter === true) {
            $limit = ' LIMIT '.$config['block_size'].' OFFSET 0';
        } else {
            $limit = ' LIMIT '.$config['block_size'].' OFFSET '.get_parameter('offset', 0);
        }

        $paginate_module = false;
        if (isset($config['paginate_module']) === true) {
            $paginate_module = (bool) $config['paginate_module'];
        }

        if ($paginate_module === true) {
            $modules = db_get_all_rows_sql($sql_modules_info.$limit);
        } else {
            $modules = db_get_all_rows_sql($sql_modules_info);
        }

        if (empty($modules) === true) {
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
        // Enterprise policies functions included.
        $isFunctionPolicies = enterprise_include_once('include/functions_policies.php');
        // Table.
        $table = new stdClass();
        $table->width = '100%';
        $table->styleTable = 'border: 0;border-radius: 0;vertical-align: baseline;';
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->class = 'tactical_table info_table';
        $table->align = [];
        $table->style = [];
        $table->head = [];
        $table->data = [];
        $table->headstyle = [];
        // Cell alignments.
        $table->align[0] = 'center';
        $table->align[1] = 'center';
        $table->align[2] = 'left';
        $table->align[3] = 'left';
        $table->align[4] = 'center';
        $table->align[5] = 'left';
        $table->align[6] = 'left';
        $table->align[7] = 'center';
        // Fixed styles.
        $table->headstyle[0] = 'width: 4%;text-align: center;';
        $table->headstyle[1] = 'width: 55px;text-align: left;';
        $table->headstyle[4] = 'width: 100px; text-align: center';
        $table->headstyle[7] = 'width: 130px; text-align: center';
        $table->headstyle[8] = 'width: 10%; text-align: center';
        $table->headstyle[9] = 'text-align: center';
        // Row class.
        $table->head[0] = ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) ? '<span title="'.__('Policy').'">'.__('P').'</span>' : '';
        $table->head[1] = '<span title="'.__('Module type').'">'.__('T').'</span>'.ui_get_sorting_arrows($url_up_type, $url_down_type, $selectTypeUp, $selectTypeDown);
        $table->head[2] = '<span>'.__('Module name').'</span>'.ui_get_sorting_arrows($url_up_name, $url_down_name, $selectNameUp, $selectNameDown);
        $table->head[3] = '<span>'.__('Description').'</span>';
        $table->head[4] = '<span>'.__('Status').'</span>'.ui_get_sorting_arrows($url_up_status, $url_down_status, $selectStatusUp, $selectStatusDown);
        $table->head[5] = '<span>'.__('Thresholds').'</span>';
        $table->head[6] = '<span>'.__('Data').'</span>';
        $table->head[7] = '<span>'.__('Last contact').'</span>'.ui_get_sorting_arrows($url_up_last, $url_down_last, $selectLastContactUp, $selectLastContactDown);
        $table->head[8] = '<span>'.__('Graphs').'</span>';
        $table->head[9] = '<span>'.__('Actions').'</span>';

        $last_modulegroup = 0;
        $rowIndex = 0;

        $id_type_web_content_string = db_get_value(
            'id_tipo',
            'ttipo_modulo',
            'nombre',
            'web_content_string'
        );

        $show_context_help_first_time = false;
        $hierachy_mode = (string) get_parameter('hierachy_mode');

        if ($hierachy_mode === 'true') {
            $modules_hierachy = [];
            $modules_hierachy = get_hierachy_modules_tree($modules);

            $modules_dt = get_dt_from_modules_tree($modules_hierachy);

            $modules = $modules_dt;
        }

        foreach ($modules as $module) {
            $idAgenteModulo = $module['id_agente_modulo'];
            if ($hierachy_mode === 'false') {
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

            $table->rowstyle[$rowIndex] = 'vertical-align: baseline';
            $data = [];
            // Module policy.
            $data[0] = '';
            if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
                if ((int) $module['id_policy_module'] !== 0) {
                    $linked = (bool) policies_is_module_linked($module['id_agente_modulo']);
                    $id_policy = db_get_value_sql('SELECT id_policy FROM tpolicy_modules WHERE id = '.$module['id_policy_module']);
                    $name_policy = (empty($id_policy) === false) ? db_get_value_sql('SELECT name FROM tpolicies WHERE id = '.$id_policy) : __('Unknown');
                    $policyInfo = policies_info_module_policy($module['id_policy_module']);
                    $adopt = policies_is_module_adopt($module['id_agente_modulo']);

                    if ((bool) $linked === true) {
                        if ((bool) $adopt === true) {
                            $img = 'images/policies_brick.png';
                            $title = '('.__('Adopted').') '.$name_policy;
                        } else {
                            $img = 'images/policy@svg.svg';
                            $title = $name_policy;
                        }
                    } else {
                        if ((bool) $adopt === true) {
                            $img = 'images/policies_not_brick.png';
                            $title = '('.__('Unlinked').') ('.__('Adopted').') '.$name_policy;
                        } else {
                            $img = 'images/unlinkpolicy.png';
                            $title = '('.__('Unlinked').') '.$name_policy;
                        }
                    }

                    $data[0] .= html_print_anchor(
                        [
                            'href'    => ui_get_full_url('?sec=gmodules&amp;sec2=enterprise/godmode/policies/policies&amp;id='.$id_policy),
                            'content' => html_print_image(
                                $img,
                                true,
                                [
                                    'title' => $title,
                                    'style' => 'margin: 0 5px;',
                                    'class' => 'main_menu_icon',
                                ]
                            ),
                        ],
                        true
                    );
                }
            }

            // Module server type.
            $data[1] = '';
            $data[1] .= ui_print_servertype_icon((int) $module['id_modulo']);

            // Module name.
            $data[2] = '';
            if (isset($module['deep']) === true && ((int) $module['deep'] !== 0)) {
                $data[2] .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $module['deep']);
                $data[2] .= html_print_image('images/icono_escuadra.png', true, ['style' => 'padding-bottom: inherit;']).'&nbsp;&nbsp;';
            }

            if ((bool) $module['quiet'] === true) {
                $data[2] .= html_print_image(
                    'images/dot_blue.png',
                    true,
                    [
                        'border' => '0',
                        'title'  => __('Quiet'),
                        'alt'    => '',
                    ]
                );
            }

            $data[2] .= '<a href ="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$id_agente.'&amp;tab=module&amp;id_agent_module='.$module['id_agente_modulo'].'&amp;edit_module='.$module['id_modulo'].'">';
            $data[2] .= ui_print_truncate_text($module['nombre'], 'module_medium', false, true, true, '&hellip;', 'font-size: 9pt;');
            $data[2] .= '</a>';
            if (empty($module['extended_info']) === false) {
                $data[2] .= ui_print_help_tip($module['extended_info'], true, '/images/default_list.png');
            }

            // Adds tag context information.
            if (tags_get_modules_tag_count($module['id_agente_modulo']) > 0) {
                $data[2] .= ' <a class="tag_details" href="ajax.php?page=operation/agentes/estado_monitores&get_tag_tooltip=1&id_agente_modulo='.$module['id_agente_modulo'].'">'.html_print_image('images/tag_red.png', true, ['id' => 'tag-details-'.$module['id_agente_modulo'], 'class' => 'img_help invert_filter']).'</a> ';
            }

            // Adds relations context information.
            if (modules_relation_exists($module['id_agente_modulo']) === true) {
                $data[2] .= ' <a class="relations_details" href="ajax.php?page=operation/agentes/estado_monitores&get_relations_tooltip=1&id_agente_modulo='.$module['id_agente_modulo'].'">'.html_print_image('images/link2.png', true, ['id' => 'relations-details-'.$module['id_agente_modulo'], 'class' => 'img_help']).'</a> ';
            }

            // Module description.
            $data[3] = '';
            $data[3] .= ui_print_string_substr($module['descripcion'], 60, true, 9);

            // Module status.
            $data[4] = '';
            if ($module['datos'] !== strip_tags($module['datos'])) {
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

            if (strlen($module['ip_target']) !== 0) {
                // Check if value is custom field.
                if ($module['ip_target'][0] == '_' && $module['ip_target'][(strlen($module['ip_target']) - 1)] == '_') {
                    $custom_field_name = substr($module['ip_target'], 1, -1);
                    $custom_value = agents_get_agent_custom_field($id_agente, $custom_field_name);
                    if (isset($custom_value) && $custom_value !== false) {
                        $title .= '<br/>IP: '.$custom_value;
                    } else {
                        $array_macros = return_agent_macros($id_agente);
                        if (isset($array_macros[$module['ip_target']])) {
                            $title .= '<br/>IP: '.$array_macros[$module['ip_target']];
                        } else {
                            $title .= '<br/>IP: '.$module['ip_target'];
                        }
                    }
                } else {
                    $title .= '<br/>IP: '.$module['ip_target'];
                }
            }

            $last_status_change_text = __('Time elapsed since last status change: ');
            $last_status_change_text .= (empty($module['last_status_change']) === false) ? human_time_comparation($module['last_status_change']) : __('N/A');

            if ($show_context_help_first_time === false) {
                $show_context_help_first_time = true;

                if ((int) $module['estado'] === AGENT_MODULE_STATUS_UNKNOWN) {
                    $data[4] .= clippy_context_help('module_unknow');
                }
            }

            $data[4] .= ui_print_status_image($status, htmlspecialchars($title), true, false, false, true, $last_status_change_text);

            // Module thresholds.
            $data[5] = '';
            if ((int) $module['id_tipo_modulo'] !== 25) {
                $data[5] = ui_print_module_warn_value($module['max_warning'], $module['min_warning'], $module['str_warning'], $module['max_critical'], $module['min_critical'], $module['str_critical'], $module['warning_inverse'], $module['critical_inverse'], 'class="font_9pt"');
            } else {
                $data[5] = '';
            }

            // Module last value.
            $data[6] = '';
            $data[6] .= '<span class="inherited_text_data_for_humans">'.modules_get_agentmodule_data_for_humans($module).'</span>';

            // Last contact.
            $data[7] = '';
            if ((int) $module['estado'] === 3) {
                $timestampClass = 'redb font_9pt';
            } else {
                $timestampClass = 'font_9pt';
            }

            $data[7] .= ui_print_timestamp($module['utimestamp'], true, ['class' => $timestampClass ]);

            // Graph buttons.
            $data[8] = '';
            if ((int) $module['history_data'] === 1) {
                if (empty((float) $module['min_warning']) === true
                    && empty((float) $module['max_warning']) === true
                    && empty($module['warning_inverse']) === true
                    && empty((float) $module['min_critical']) === true
                    && empty((float) $module['max_critical']) === true
                    && empty($module['critical_inverse']) === true
                ) {
                    $tresholds = false;
                } else {
                    $tresholds = true;
                }

                $graphButtons = [];
                $graph_type = return_graphtype($module['id_tipo_modulo']);
                $nombre_tipo_modulo = modules_get_moduletype_name($module['id_tipo_modulo']);
                $handle = 'stat'.$nombre_tipo_modulo.'_'.$module['id_agente_modulo'];
                $win_handle = dechex(crc32($module['id_agente_modulo'].$module['nombre']));
                // Show events for boolean modules by default.
                $draw_events = ($graph_type === 'boolean') ? 1 : 0;
                // Try to display the SNMP module realtime graph.
                $rt_button = get_module_realtime_link_graph($module);

                if (empty($rt_button) === false) {
                    $graphButtons[] = $rt_button;
                }

                if ($tresholds === true || $graph_type === 'boolean') {
                    $link = 'winopeng_var(\'operation/agentes/stat_win.php?type='.$graph_type.'&amp;period='.SECONDS_1DAY.'&amp;id='.$module['id_agente_modulo'].'&amp;refresh='.SECONDS_10MINUTES.'&amp;histogram=1\', \'day_'.$win_handle.'\', 800, 480)';
                    $graphButtons[] = html_print_anchor(
                        [
                            'href'    => 'javascript:'.$link,
                            'content' => html_print_image(
                                'images/event-history.svg',
                                true,
                                [
                                    'title' => __('Event history'),
                                    'class' => 'main_menu_icon forced_title',
                                ]
                            ),
                        ],
                        true
                    );
                }

                if (is_snapshot_data($module['datos']) === false) {
                    $link = 'winopeng_var(\'operation/agentes/stat_win.php?type='.$graph_type.'&amp;period='.SECONDS_1DAY.'&amp;id='.$module['id_agente_modulo'].'&amp;refresh='.SECONDS_10MINUTES.'&amp;period_graph=0&amp;draw_events='.$draw_events.'\', \'day_'.$win_handle.'\', 800, 480)';
                    $graphButtons[] = html_print_anchor(
                        [
                            'href'    => 'javascript:'.$link,
                            'content' => html_print_image(
                                'images/module-graph.svg',
                                true,
                                [
                                    'title' => __('Module graph'),
                                    'class' => 'main_menu_icon forced_title',
                                ]
                            ),
                        ],
                        true
                    );
                }

                $modules_get_agentmodule_name = modules_get_agentmodule_name($module['id_agente_modulo']);
                // Escape the double quotes that may have the name of the module.
                $modules_get_agentmodule_name = str_replace('&quot;', '\"', $modules_get_agentmodule_name);

                $graphButtons[] = html_print_anchor(
                    [
                        'href'    => 'javascript: show_module_detail_dialog('.$module['id_agente_modulo'].', '.$id_agente.', \'\', '.(0).', '.SECONDS_1DAY.', \''.$modules_get_agentmodule_name.'\')',
                        'content' => html_print_image(
                            'images/simple-value.svg',
                            true,
                            [
                                'title' => __('Module detail'),
                                'class' => 'main_menu_icon forced_title',
                            ]
                        ),
                    ],
                    true
                );

                if ($cluster_view === true) {
                    $graphButtons[] = html_print_anchor(
                        [
                            'href'    => 'javascript: show_cluster_module_detail('.$cluster->id().', \''.$modules_get_agentmodule_name.'\')',
                            'content' => html_print_image(
                                'images/plus@svg.svg',
                                true,
                                [
                                    'title' => __('Module cluster detail'),
                                    'class' => 'main_menu_icon forced_title',
                                ]
                            ),
                        ],
                        true
                    );
                }

                $data[8] = html_print_div(
                    [
                        'class'   => 'table_action_buttons',
                        'content' => implode('', $graphButtons),
                    ],
                    true
                );
            }

            // Actions.
            $data[9] = '';
            $moduleActionButtons = [];
            if (((int) $module['id_modulo'] !== 1) && ((int) $module['id_tipo_modulo'] !== 100)) {
                if ($agent_w === true) {
                    if ((int) $module['flag'] === 0) {
                        $additionalLinkAction = '&amp;flag=1';
                        $linkCaption = __('Force checks');
                        $imgaction = 'images/force@svg.svg';
                        $visibility = '';
                    } else {
                        $additionalLinkAction = '';
                        $linkCaption = __('Refresh');
                        $imgaction = 'images/go-back@svg.svg';
                        $visibility = 'visibility: initial;';
                    }

                    $moduleActionButtons[] = html_print_anchor(
                        [
                            'href'    => $url.'&amp;id_agente_modulo='.$module['id_agente_modulo'].'&amp;refr=60'.$additionalLinkAction.'"',
                            'content' => html_print_image(
                                $imgaction,
                                true,
                                [
                                    'title' => __('Force remote check'),
                                    'class' => 'main_menu_icon forced_title',
                                    'style' => $visibility,
                                ]
                            ),
                        ],
                        true
                    );
                }
            }

            if ((bool) check_acl($config['id_user'], $id_grupo, 'AW') === true
                && $cluster_view === false
            ) {
                $moduleActionButtons[] = html_print_anchor(
                    [
                        'href'    => 'index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$id_agente.'&amp;tab=module&amp;id_agent_module='.$module['id_agente_modulo'].'&amp;edit_module='.$module['id_modulo'].'"',
                        'content' => html_print_image(
                            'images/edit.svg',
                            true,
                            [
                                'title' => __('Edit configuration'),
                                'class' => 'main_menu_icon forced_title',
                            ]
                        ),
                    ],
                    true
                );
            }

            $data[9] = html_print_div(
                [
                    'class'   => 'table_action_buttons',
                    'content' => implode('', $moduleActionButtons),
                ],
                true
            );

            array_push($table->data, $data);
            $rowIndex++;
        }

        ui_require_javascript_file('pandora.js');
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
        if (empty($table->data) === true) {
            ui_print_info_message(
                [
                    'no_close' => true,
                    'message'  => ($filter_monitors === true) ? __('Any monitors aren\'t with this filter.') : __('This agent doesn\'t have any active monitors.'),
                ]
            );
        } else {
            $url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&refr=&filter_monitors=1&status_filter_monitor='.$status_filter_monitor.'&status_text_monitor='.$status_text_monitor.'&status_module_group='.$status_module_group;

            if ($paginate_module === true) {
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

            if ($paginate_module === true) {
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
        }

        unset($table);
        unset($table_data);
    }

    if ($get_type === true) {
        $id_module = (int) get_parameter('id_module');
        $module = modules_get_agentmodule($id_module);
        $graph_type = return_graphtype($module['id_tipo_modulo']);
        echo $graph_type;
        return;
    }

    if ($get_graph_module === true) {
        global $config;
        $output = '';
        $graph_data = get_parameter('graph_data', '');
        $params = json_decode(base64_decode($graph_data), true);
        $form_data = json_decode(base64_decode(get_parameter('form_data', [])), true);
        $server_id = (int) get_parameter('server_id', 0);
        include_once $config['homedir'].'/include/functions_graph.php';

        $tab_active = get_parameter('active', 'tabs-chart-module-graph');

        $output .= draw_form_stat_win($form_data, $tab_active);

        // Metaconsole connection to the node.
        if (is_metaconsole() === true && empty($server_id) === false) {
            $server = metaconsole_get_connection_by_id($server_id);
            metaconsole_connect($server);
        }

        if ($params['enable_projected_period'] === '1') {
            $params_graphic = [
                'period'             => $params['period'],
                'date'               => strtotime(date('Y-m-d H:i:s')),
                'only_image'         => false,
                'homeurl'            => ui_get_full_url(false, false, false, false).'/',
                'ttl'                => false,
                'height'             => $config['graph_image_height'],
                'landscape'          => $content['landscape'],
                'return_img_base_64' => true,
            ];

            $params_combined = [
                'projection' => $params['period_projected'],
            ];

            $return['chart'] = graphic_combined_module(
                [$params['agent_module_id']],
                $params_graphic,
                $params_combined
            );
            $output .= '<div class="stat_win_histogram">';
            $output .= $return['chart'];
            $output .= '</div>';
        } else {
            if ($params['histogram'] === true) {
                $params['id_agent_module'] = $params['agent_module_id'];
                $params['dinamic_proc'] = 1;

                $output .= '<div class="stat_win_histogram">';
                if ($params['compare'] === 'separated') {
                    $graph = \reporting_module_histogram_graph(
                        ['datetime' => ($params['begin_date'] - $params['period'])],
                        $params
                    );
                    $output .= $graph['chart'];
                }

                $graph = \reporting_module_histogram_graph(
                    ['datetime' => $params['begin_date']],
                    $params
                );
                $output .= $graph['chart'];
                $output .= '</div>';
            } else {
                if ($tab_active === 'tabs-chart-module-graph') {
                    $output .= grafico_modulo_sparse($params);
                } else {
                    $output .= '<div class="container-periodicity-graph">';
                    $output .= '<div>';
                    $output .= graphic_periodicity_module($params);
                    $output .= '</div>';
                    $output .= '</div>';
                    if ($params['compare'] === 'separated') {
                        $params['date'] = ($params['date'] - $params['period']);
                        $output .= '<div class="container-periodicity-graph">';
                        $output .= '<div>';
                        $output .= graphic_periodicity_module($params);
                        $output .= '</div>';
                        $output .= '</div>';
                    }
                }
            }
        }

        if (is_metaconsole() === true && empty($server_id) === false) {
            metaconsole_restore_db();
        }

        echo $output;
        return;
    }

    if ($get_graph_module_interfaces === true) {
        global $config;
        include_once $config['homedir'].'/include/functions_graph.php';

        $output = '';
        $graph_data = get_parameter('graph_data', '');
        $params = json_decode(base64_decode($graph_data), true);

        $modules = get_parameter('modules', '');
        $modules = json_decode(base64_decode($modules), true);

        $graph_data_combined = get_parameter('graph_data_combined', '');
        $params_combined = json_decode(
            base64_decode($graph_data_combined),
            true
        );

        $output .= graphic_combined_module(
            $modules,
            $params,
            $params_combined
        );
        echo $output;

        return;
    }

    if ($get_data_dataMatrix === true) {
        global $config;

        $table_id = get_parameter('table_id', '');
        $modules = json_decode(
            io_safe_output(
                get_parameter('modules', '')
            ),
            true
        );
        $period = get_parameter('period', 0);
        $slice = get_parameter('slice', 0);

        // Datatables offset, limit.
        $start = get_parameter('start', 0);
        $formatData = (bool) get_parameter('formatData', 0);
        $length = get_parameter(
            'length',
            $config['block_size']
        );

        $order = get_datatable_order(true);

        // Total time per page.
        $time_all_box = ($length * $slice);

        // Total number of boxes.
        $total_box = ceil($period / $slice);

        if ($start > 0) {
            $start = ($start / $length);
        }

        // Uncompress.
        try {
            $dateNow = get_system_time();
            $final = ($dateNow - $period);
            $date = ($dateNow - ($time_all_box * $start));

            if (($date - $time_all_box) > $final) {
                $datelimit = ($date - $time_all_box);
            } else {
                $datelimit = $final;
            }

            foreach ($modules as $key => $value) {
                if (is_metaconsole() === true) {
                    try {
                        $node = new Node((int) $value['id_node']);
                        $node->connect();
                    } catch (\Exception $e) {
                        // Unexistent agent.
                        $node->disconnect();
                    }
                }

                $value['thresholds'] = [
                    'min_critical' => (empty($value['c_min']) === true) ? null : $value['c_min'],
                    'max_critical' => (empty($value['c_max']) === true) ? null : $value['c_max'],
                    'min_warning'  => (empty($value['w_min']) === true) ? null : $value['w_min'],
                    'max_warning'  => (empty($value['w_max']) === true) ? null : $value['w_max'],
                ];

                $module_data = db_uncompress_module_data(
                    $value['id'],
                    $datelimit,
                    $date,
                    $slice,
                    true
                );

                $uncompressData[] = array_reduce(
                    $module_data,
                    function ($carry, $item) use ($value, $config, $formatData) {
                        // Last value.
                        $vdata = null;
                        if (is_array($item['data']) === true) {
                            foreach ($item['data'] as $v) {
                                $vdata = $v['datos'];
                            }
                        }

                        $status = get_status_data_modules(
                            $value['id'],
                            $vdata,
                            $value['thresholds']
                        );

                        $resultData = '<span class="widget-module-tabs-data" style="color:'.$status['color'].'">';
                        if ($vdata !== null && $vdata !== '' && $vdata !== false) {
                            if (isset($formatData) === true
                                && (bool) $formatData === true
                            ) {
                                $resultData .= format_for_graph(
                                    $vdata,
                                    $config['graph_precision']
                                );
                            } else {
                                $resultData .= sla_truncate(
                                    $vdata,
                                    $config['graph_precision']
                                );
                            }

                            $resultData .= ' '.$value['unit'];
                        } else {
                            $resultData .= '--';
                        }

                        $resultData .= '</span>';
                        $carry[] = [
                            'utimestamp'           => $item['utimestamp'],
                            'Column-'.$value['id'] => $resultData,
                        ];

                        return $carry;
                    },
                    []
                );

                if (is_metaconsole() === true) {
                    $node->disconnect();
                }
            }

            if (empty($uncompressData) === false) {
                $data = array_reduce(
                    $uncompressData,
                    function ($carry, $item) {
                        foreach ($item as $data_module) {
                            foreach ($data_module as $key => $value) {
                                if ($key === 'utimestamp') {
                                    $carry[$data_module['utimestamp']]['date'] = date('Y-m-d H:i', (int) $value);
                                } else {
                                    $carry[$data_module['utimestamp']][$key] = $value;
                                }
                            }
                        }

                        return $carry;
                    }
                );
            }

            if (empty($data) === false) {
                $data = array_reverse(array_values($data));
            } else {
                $data = [];
            }

            // RecordsTotal && recordsfiltered resultados totales.
            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $total_box,
                    'recordsFiltered' => $total_box,
                ]
            );
        } catch (Exception $e) {
            echo json_encode(
                ['error' => $e->getMessage()]
            );
        }
    }

    if ($get_cluster_module_detail === true) {
        global $config;
        $data = [];

        $cluster_id = get_parameter('cluster_id', 0);
        $cluster = new PandoraFMS\Cluster($cluster_id);
        $modules_ids = $cluster->getIdsModulesInvolved();
        $module_name = get_parameter('module_name', '');

        try {
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

            $tableId = 'ModuleByStatus';
            // Load datatables user interface.
            ui_print_datatable(
                [
                    'id'                         => $tableId,
                    'class'                      => 'info_table align-left-important',
                    'style'                      => 'width: 100%',
                    'columns'                    => $columns,
                    'column_names'               => $column_names,
                    'ajax_url'                   => 'include/ajax/module',
                    'ajax_data'                  => [
                        'get_data_ModulesByStatus' => 1,
                        'table_id'                 => $tableId,
                        'module_name'              => $module_name,
                        'modules_ids'              => $modules_ids,
                        'search'                   => '',
                    ],
                    'default_pagination'         => 5,
                    'order'                      => [
                        'field'     => 'last_status_change',
                        'direction' => 'desc',
                    ],
                    'csv'                        => 0,
                    'dom_elements'               => 'frtip',
                    'no_move_elements_to_action' => true,
                    'mini_pagination'            => true,
                ]
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return;
    }

    if ($get_data_ModulesByStatus === true) {
        global $config;
        $data = [];

        $table_id = get_parameter('table_id', '');
        $search = get_parameter('search', '');
        $search_agent = get_parameter('search_agent', '');
        $groupId = (int) get_parameter('groupId', 0);
        $module_name = get_parameter('module_name', '');
        $status = get_parameter('status', '');
        $start = get_parameter('start', 0);
        $modules_ids = get_parameter('modules_ids', []);
        $length = get_parameter('length', $config['block_size']);
        // There is a limit of (2^32)^2 (18446744073709551615) rows in a MyISAM table, show for show all use max nrows.
        $length = ($length != '-1') ? $length : '18446744073709551615';
        $order = get_datatable_order(true);
        $nodes = get_parameter('nodes', 0);
        $disabled_modules = (bool) get_parameter('disabled_modules', false);

        $groups_array = [];
        if ($groupId === 0) {
            if (users_can_manage_group_all('AR') === false) {
                $groups_array = users_get_groups(false, 'AR', false);
            }
        } else {
            $groups_array = [$groupId];
        }

        $where = '1=1';
        $recordsTotal = 0;

        if (empty($groups_array) === false) {
            $where .= sprintf(
                ' AND (tagente.id_grupo IN (%s)
                    OR tagent_secondary_group.id_group IN(%s))',
                implode(',', $groups_array),
                implode(',', $groups_array)
            );
        }


        if (empty($search) === false) {
            $where .= ' AND tagente_modulo.nombre LIKE "%%'.$search.'%%"';
        }

        if (empty($search_agent) === false) {
            $where .= ' AND tagente.alias LIKE "%%'.$search_agent.'%%"';
        }

        if (str_contains($status, '6') === true) {
            $expl = explode(',', $status);
            $exist = array_search('6', $expl);
            if (isset($exist) === true) {
                unset($expl[$exist]);
            }

            array_push($expl, '1', '2', '3', '4', '5');

            $status = implode(',', $expl);
        }

        if (str_contains($status, '5') === true) {
            $expl = explode(',', $status);
            $exist = array_search('5', $expl);
            if (isset($exist) === true) {
                unset($expl[$exist]);
            }

            array_push($expl, '4', '5');

            $status = implode(',', $expl);
        }

        if (empty($status) === false || $status === '0') {
            $where .= sprintf(
                ' AND tagente_estado.estado IN (%s)
                AND tagente_modulo.delete_pending = 0',
                $status,
            );
        }

        if ($disabled_modules === false) {
            $where .= ' AND tagente_modulo.disabled = 0';
        }

        if (empty($modules_ids) === false && is_array($modules_ids) === true) {
            $where .= sprintf(
                ' AND tagente_modulo.id_agente_modulo IN (%s)',
                implode(',', $modules_ids)
            );
        }

        if (empty($module_name) === false) {
            $where .= sprintf(
                ' AND tagente_modulo.nombre = "%s"',
                $module_name
            );
        }

        if (is_metaconsole() === false) {
            $order_by = '';
            switch ($order['field']) {
                case 'nombre':
                    $order_by = 'tagente_modulo.'.$order['field'].' '.$order['direction'];
                break;

                case 'alias':
                    $order_by = 'tagente.'.$order['field'].' '.$order['direction'];
                break;

                case 'last_status_change':
                    $order_by = 'tagente_estado.'.$order['field'].' '.$order['direction'];
                break;

                case 'estado':
                    $order_by = 'tagente_estado.'.$order['field'].' '.$order['direction'];
                break;

                default:
                    $order_by = 'tagente_estado.last_status_change desc';
                break;
            }

            $sql = sprintf(
                'SELECT
                tagente_modulo.nombre,
                tagente.alias,
                tagente.id_agente,
                tagente_estado.last_status_change,
                tagente_estado.estado
                FROM tagente_modulo
                INNER JOIN tagente
                    ON tagente_modulo.id_agente = tagente.id_agente 
                INNER JOIN tagente_estado
                    ON tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
                LEFT JOIN tagent_secondary_group
					ON tagente.id_agente = tagent_secondary_group.id_agent
                WHERE %s
                ORDER BY %s
                LIMIT %d, %d',
                $where,
                $order_by,
                $start,
                $length
            );
            $data = db_get_all_rows_sql($sql);

            $sql_count = sprintf(
                'SELECT COUNT(*) AS "total"
                FROM tagente_modulo
                INNER JOIN tagente
                    ON tagente_modulo.id_agente = tagente.id_agente 
                INNER JOIN tagente_estado
                    ON tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
                LEFT JOIN tagent_secondary_group
					ON tagente.id_agente = tagent_secondary_group.id_agent
                WHERE %s',
                $where
            );
            $recordsTotal = db_get_value_sql($sql_count);

            // Metaconsole.
        } else {
            // $servers_ids = array_column(metaconsole_get_servers(), 'id');
            $servers_ids = explode(',', $nodes);

            foreach ($servers_ids as $server_id) {
                try {
                    $node = new Node((int) $server_id);
                    $node->connect();

                    $sql = sprintf(
                        'SELECT
                        tagente_modulo.nombre,
                        tagente.alias,
                        tagente.id_agente,
                        tagente_estado.last_status_change,
                        tagente_estado.estado
                        FROM tagente_modulo
                        INNER JOIN tagente
                            ON tagente_modulo.id_agente = tagente.id_agente 
                        INNER JOIN tagente_estado
                            ON tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
                        LEFT JOIN tagent_secondary_group
					        ON tagente.id_agente = tagent_secondary_group.id_agent
                        WHERE %s',
                        $where
                    );

                    $res_sql = db_get_all_rows_sql($sql);

                    foreach ($res_sql as $row_sql) {
                        $row_sql['server_name'] = $node->server_name();
                        $row_sql['server_url'] = $node->server_url();
                        array_push($data, $row_sql);
                    }

                    $node->disconnect();
                } catch (\Exception $e) {
                    // Unexistent modules.
                    $node->disconnect();
                }
            }

            if (in_array(0, $servers_ids) === true) {
                $sql = sprintf(
                    'SELECT
                    tagente_modulo.nombre,
                    tagente.alias,
                    tagente.id_agente,
                    tagente_estado.last_status_change,
                    tagente_estado.estado
                    FROM tagente_modulo
                    INNER JOIN tagente
                        ON tagente_modulo.id_agente = tagente.id_agente 
                    INNER JOIN tagente_estado
                        ON tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
                    LEFT JOIN tagent_secondary_group
					    ON tagente.id_agente = tagent_secondary_group.id_agent
                    WHERE %s',
                    $where
                );

                $res_sql = db_get_all_rows_sql($sql);

                foreach ($res_sql as $row_sql) {
                    $row_sql['server_name'] = __('Metaconsole');
                    $row_sql['server_url'] = $config['homeurl'];
                    array_push($data, $row_sql);
                }
            }

            // Drop temporary table if exist.
            db_process_sql('DROP TEMPORARY TABLE IF EXISTS temp_modules_status;');

            $table_temporary = 'CREATE TEMPORARY TABLE IF NOT EXISTS temp_modules_status (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                nombre VARCHAR(600),
                alias VARCHAR(600),
                id_agente INT,
                last_status_change INT,
                estado INT,
                server_name VARCHAR(100),
                server_url VARCHAR(200),
                PRIMARY KEY (`id`),
                KEY `nombre` (`nombre`(600))
            )';
            db_process_sql($table_temporary);

            $result = db_process_sql_insert_multiple('temp_modules_status', $data);

            if (empty($result) === false) {
                $data = [];
                $sql = '';
                $where = '';

                if (empty($search) === false) {
                    $where = 'nombre LIKE "%%'.$search.'%%" AND ';
                }

                $where .= sprintf(
                    'estado IN (%s)',
                    $status
                );

                $order_by = $order['field'].' '.$order['direction'];

                $sql = sprintf(
                    'SELECT
                        nombre,
                        alias,
                        id_agente,
                        last_status_change,
                        estado,
                        server_name,
                        server_url
                    FROM temp_modules_status
                    WHERE %s
                    ORDER BY %s
                    LIMIT %d, %d',
                    $where,
                    $order_by,
                    $start,
                    $length
                );
                $data = db_get_all_rows_sql($sql);

                $sql_count = sprintf(
                    'SELECT COUNT(*) AS "total"
                    FROM temp_modules_status
                    WHERE %s',
                    $where
                );

                $recordsTotal = db_get_value_sql($sql_count);
            }
        }

        if ($data === false) {
            $data = [];
        }

        foreach ($data as $key => $row) {
            $data[$key]['nombre'] = html_ellipsis_characters($row['nombre'], 35, true);

            if (is_metaconsole() === false) {
                $name_link = '<a href="index.php?sec=estado&sec2=';
            } else {
                $name_link = '<a href="'.$row['server_url'].'index.php?sec=estado&sec2=';
            }

            $name_link .= 'operation/agentes/ver_agente&id_agente='.$row['id_agente'];
            $name_link .= '"><b>';
            $name_link .= '<span class="ellipsis-35ch">'.html_ellipsis_characters($row['alias'], 35, true).'</span>';
            $name_link .= '</b></a>';

            $data[$key]['alias'] = $name_link;

            $data[$key]['last_status_change'] = ui_print_timestamp(
                $row['last_status_change'],
                true
            );

            switch ((int) $row['estado']) {
                case AGENT_MODULE_STATUS_NORMAL:
                    $status_img = ui_print_status_image(STATUS_MODULE_OK, __('Normal'), true);
                break;

                case AGENT_MODULE_STATUS_CRITICAL_BAD:
                case AGENT_MODULE_STATUS_NOT_NORMAL:
                    $status_img = ui_print_status_image(STATUS_MODULE_CRITICAL, __('Critical'), true);
                break;

                case AGENT_MODULE_STATUS_WARNING:
                    $status_img = ui_print_status_image(STATUS_MODULE_WARNING, __('Warning'), true);
                break;

                case AGENT_MODULE_STATUS_UNKNOWN:
                    $status_img = ui_print_status_image(STATUS_MODULE_UNKNOWN, __('Unknown'), true);
                break;

                case AGENT_MODULE_STATUS_NO_DATA:
                case AGENT_MODULE_STATUS_NOT_INIT:
                    $status_img = ui_print_status_image(STATUS_MODULE_NO_DATA, __('Not init'), true);
                break;

                default:
                    $status_img = '';
                break;
            }

            $data[$key]['estado'] = $status_img;
        }

        echo json_encode(
            [
                'data'            => $data,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsTotal,
            ]
        );
    }

    if ($get_children_modules === true) {
        $parent_modules = get_parameter('parent_modulues', false);
        $children_selected = [];

        if ($parent_modules === false) {
            $children_selected = false;
        } else {
            foreach ($parent_modules as $parent) {
                $child_modules = get_children_module($parent_modules, ['nombre', 'id_agente_modulo'], true);
                if ((bool) $child_modules === false) {
                    continue;
                }

                foreach ($child_modules as $child) {
                    $module_exist = in_array($child['id_agente_modulo'], $parent_modules);
                    $child_exist = in_array($child, $children_selected);

                    if ($module_exist === false && $child_exist === false) {
                        array_push($children_selected, $child);
                    }
                }
            }
        }

        if (empty($children_selected) === true) {
            $children_selected = false;
        }

        echo json_encode($children_selected);

        return;
    }

    // Saves an event filter.
    if ($save_monitor_filter) {
        $values = [];
        $values['id_name'] = get_parameter('id_name');
        $values['id_group_filter'] = get_parameter('id_group_filter');
        $values['ag_group'] = get_parameter('ag_group');
        $values['modulegroup'] = get_parameter('modulegroup');
        $values['recursion'] = get_parameter('recursion');
        $values['status'] = get_parameter('status');
        $values['ag_modulename'] = get_parameter('ag_modulename');
        $values['ag_freestring'] = get_parameter('ag_freestring');
        $values['tag_filter'] = json_encode(get_parameter('tag_filter'));
        $values['moduletype'] = get_parameter('moduletype');
        $values['module_option'] = get_parameter('module_option');
        $values['min_hours_status'] = get_parameter('min_hours_status');
        $values['datatype'] = get_parameter('datatype');
        $values['not_condition'] = get_parameter('not_condition');
        $values['ag_custom_fields'] = get_parameter('ag_custom_fields');

        $exists = (bool) db_get_value_filter(
            'id_filter',
            'tmonitor_filter',
            $values
        );

        if ($exists === true) {
            echo 'duplicate';
        } else {
            $result = db_process_sql_insert('tmonitor_filter', $values);

            if ($result === false) {
                echo 'error';
            } else {
                echo $result;
            }
        }
    }

    if ($update_monitor_filter) {
        $values = [];
        $id = get_parameter('id');

        $values['ag_group'] = get_parameter('ag_group');
        $values['modulegroup'] = get_parameter('modulegroup');
        $values['recursion'] = get_parameter('recursion');
        $values['status'] = get_parameter('status');
        $values['ag_modulename'] = get_parameter('ag_modulename');
        $values['ag_freestring'] = get_parameter('ag_freestring');
        $values['tag_filter'] = json_encode(get_parameter('tag_filter'));
        $values['moduletype'] = get_parameter('moduletype');
        $values['module_option'] = get_parameter('module_option');
        $values['min_hours_status'] = get_parameter('min_hours_status');
        $values['datatype'] = get_parameter('datatype');
        $values['not_condition'] = get_parameter('not_condition');
        $values['ag_custom_fields'] = get_parameter('ag_custom_fields');

        $result = db_process_sql_update(
            'tmonitor_filter',
            $values,
            ['id_filter' => $id]
        );

        if ($result === false) {
            echo 'error';
        } else {
            ui_update_name_fav_element($id, 'Modules', $values['ag_modulename']);
            echo 'ok';
        }
    }

    if ($delete_monitor_filter) {
        $id = get_parameter('id');

        $user_groups = users_get_groups(
            $config['id_user'],
            'AW',
            users_can_manage_group_all('AW'),
            true
        );

        $sql = 'DELETE
            FROM tmonitor_filter
            WHERE id_filter = '.$id.' AND id_group_filter IN ('.implode(',', array_keys($user_groups)).')';

        $monitor_filters = db_process_sql($sql);

        if ($monitor_filters === false) {
            echo 'error';
        } else {
            db_process_sql_delete(
                'tfavmenu_user',
                [
                    'id_element' => $id,
                    'section'    => 'Modules',
                    'id_user'    => $config['id_user'],
                ]
            );
            echo 'ok';
        }
    }

    if ($get_monitor_filters) {
        $sql = 'SELECT id_filter, id_name FROM tmonitor_filter';

        $monitor_filters = db_get_all_rows_sql($sql);

        $result = [];

        if ($monitor_filters !== false) {
            foreach ($monitor_filters as $monitor_filter) {
                $result[$monitor_filter['id_filter']] = $monitor_filter['id_name'];
            }
        }

        echo io_json_mb_encode($result);
    }

    if ((int) $load_filter_modal === 1) {
        $user_groups = users_get_groups(
            $config['id_user'],
            'AR',
            users_can_manage_group_all('AR'),
            true
        );

        $sql = 'SELECT id_filter, id_name
		    FROM tmonitor_filter
		    WHERE id_group_filter IN ('.implode(',', array_keys($user_groups)).')';

        $event_filters = db_get_all_rows_sql($sql);

        $filters = [];
        foreach ($event_filters as $event_filter) {
            $filters[$event_filter['id_filter']] = $event_filter['id_name'];
        }

        echo '<div id="load-filter-select" class="load-filter-modal" title="'.__('Load').'">';
        echo '<form method="post" id="form_load_filter" action="index.php?sec=view&sec2=operation/agentes/status_monitor&pure=">';

        $table = new StdClass;
        $table->id = 'load_filter_form';
        $table->width = '100%';
        $table->class = 'filter-table-adv';

        $data = [];
        $table->rowid[3] = 'update_filter_row1';
        $data[0] = html_print_label_input_block(
            __('Load filter'),
            html_print_select(
                $filters,
                'filter_id',
                $current,
                '',
                __('None'),
                0,
                true,
                false,
                true,
                '',
                false
            )
        );

        $table->data[] = $data;
        $table->rowclass[] = '';

        html_print_table($table);
        html_print_div(
            [
                'class'   => 'action-buttons',
                'content' => html_print_submit_button(
                    __('Load filter'),
                    'srcbutton',
                    false,
                    [
                        'icon' => 'search',
                        'mode' => 'mini',
                    ],
                    true
                ),
            ],
            false
        );
        echo html_print_input_hidden('load_filter', 1, true);
        echo '</form>';
        echo '</div>';
        ?>

        <script type="text/javascript">
        function show_filter() {
            $("#load-filter-select").dialog({
                resizable: true,
                draggable: true,
                modal: false,
                closeOnEscape: true,
                width: 450
            });
        }

        $(document).ready(function() {
            show_filter();
        });

        </script>
        <?php
        return;
    }

    if ($save_filter_modal) {
        echo '<div id="save-filter-select" title="'.__('Save').'">';
        if (check_acl($config['id_user'], 0, 'AW')) {
            echo '<div id="#info_box"></div>';
            $table = new StdClass;
            $table->id = 'save_filter_form';
            $table->size = [];
            $table->size[0] = '50%';
            $table->size[1] = '50%';
            $table->class = 'filter-table-adv';
            $data = [];

            $table->rowid[0] = 'update_save_selector';
            $data[0][0] = html_print_label_input_block(
                __('New filter'),
                html_print_radio_button(
                    'filter_mode',
                    'new',
                    '',
                    true,
                    true
                )
            );

            $data[0][1] = html_print_label_input_block(
                __('Update/delete filter'),
                html_print_radio_button(
                    'filter_mode',
                    'update',
                    '',
                    false,
                    true
                )
            );

            $table->rowid[1] = 'save_filter_row1';
            $data[1][0] = html_print_label_input_block(
                __('Filter name'),
                html_print_input_text('id_name', '', '', 15, 255, true)
            );

            $labelInput = __('Filter group');
            if (is_metaconsole() === true) {
                $labelInput = __('Save in Group');
            }

            $user_groups_array = users_get_groups_for_select(
                $config['id_user'],
                'AW',
                users_can_manage_group_all('AW'),
                true
            );

            $data[1][1] = html_print_label_input_block(
                $labelInput,
                html_print_select(
                    $user_groups_array,
                    'id_group_filter_dialog',
                    $id_group_filter,
                    '',
                    '',
                    0,
                    true,
                    false,
                    false
                ),
                ['div_class' => 'filter-group-dialog']
            );

            $table->rowid[2] = 'save_filter_row2';
            $sql = 'SELECT id_filter, id_name FROM tmonitor_filter';
            $monitor_filters = db_get_all_rows_sql($sql);

            $_filters_update = [];

            if ($monitor_filters !== false) {
                foreach ($monitor_filters as $monitor_filter) {
                    $_filters_update[$monitor_filter['id_filter']] = $monitor_filter['id_name'];
                }
            }

            $data[2][0] = html_print_label_input_block(
                __('Overwrite filter'),
                html_print_select(
                    $_filters_update,
                    'overwrite_filter',
                    '',
                    '',
                    '',
                    0,
                    true
                )
            );

            $table->data = $data;

            html_print_table($table);

            html_print_div(
                [
                    'id'      => 'submit-save_filter',
                    'class'   => 'action-buttons',
                    'content' => html_print_submit_button(
                        __('Save current filter'),
                        'srcbutton',
                        false,
                        [
                            'icon'    => 'search',
                            'mode'    => 'mini',
                            'onclick' => 'save_new_filter();',
                        ],
                        true
                    ),
                ],
                false
            );

            $input_actions = html_print_submit_button(
                __('Delete filter'),
                'delete_filter',
                false,
                [
                    'icon'    => 'delete',
                    'mode'    => 'mini',
                    'onclick' => 'save_delete_filter();',
                ],
                true
            );

            $input_actions .= html_print_submit_button(
                __('Update filter'),
                'srcbutton',
                false,
                [
                    'icon'    => 'update',
                    'mode'    => 'mini',
                    'onclick' => 'save_update_filter();',
                ],
                true
            );

            html_print_div(
                [
                    'id'      => 'update_filter_row',
                    'class'   => 'action-buttons',
                    'content' => $input_actions,
                ],
                false
            );
        } else {
            include 'general/noaccess.php';
        }

        echo '</div>';
        ?>
    <script type="text/javascript">
    function show_save_filter() {
        $('#save_filter_row2').hide();
        $('#update_filter_row').hide();
        $('#update_delete_row').hide();
        $('.filter-group-dialog').show();
        // Filter save mode selector
        $("[name='filter_mode']").click(function() {
            if ($(this).val() == 'new') {
                $('#save_filter_row2').hide();
                $('#submit-save_filter').show();
                $('#update_filter_row').hide();
                $('#update_delete_row').hide();
                $('.filter-group-dialog').show();
            }
            else {
                $('#save_filter_row2').show();
                $('#update_filter_row').show();
                $('#submit-save_filter').hide();
                $('#update_delete_row').show();
                $('.filter-group-dialog').hide();
            }
        });
        $("#save-filter-select").dialog({
            resizable: true,
            draggable: true,
            modal: false,
            closeOnEscape: true,
            width: 450,
            height: 350
        });
    }
    
    function save_new_filter() {
        // If the filter name is blank show error
        if ($('#text-id_name').val() == '') {
            $('#show_filter_error').html("<h3 class='error'><?php echo __('Filter name cannot be left blank'); ?></h3>");
            
            // Close dialog
            $('.ui-dialog-titlebar-close').trigger('click');
            return false;
        }

        var custom_fields_values = $('input[name^="ag_custom_fields"]').map(function() {
            return this.value;
        }).get();

        var custom_fields_ids = $("input[name^='ag_custom_fields']").map(function() {
            var name = $(this).attr("name");
            var number = name.match(/\[(.*?)\]/)[1];

            return number;
        }).get();

        var ag_custom_fields = custom_fields_ids.reduce(function(result, custom_fields_id, index) {
            result[custom_fields_id] = custom_fields_values[index];
            return result;
        }, {});

        var id_filter_save;
        jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            {
                "page" : "include/ajax/module",
                "save_monitor_filter" : 1,
                "id_name": $("#text-id_name").val(),
                "id_group_filter": $("#id_group_filter_dialog").val(),
                "ag_group" : $("#ag_group").val(),
                "modulegroup" : $("#modulegroup").val(),
                "recursion" : $("#checkbox-recursion").is(':checked'),
                "status" : $("#status").val(),
                "severity" : $("#severity").val(),
                "ag_modulename" : $("#text-ag_modulename").val(),
                "ag_freestring" : $("#text-ag_freestring").val(),
                "tag_filter" : $("#tag_filter").val(),
                "moduletype" : $("#moduletype").val(),
                "module_option" : $('#module_option').val(),
                "min_hours_status" : $('#text-min_hours_status').val(),
                "datatype" : $("#datatype").val(),
                "not_condition" : $("#not_condition_switch").is(':checked'),
                "ag_custom_fields": JSON.stringify(ag_custom_fields),
            },
            function (data) {
                $("#info_box").hide();
                if (data == 'error') {
                    $("#info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "error_create_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
                else  if (data == 'duplicate') {
                    $("#info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "duplicate_create_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
                else {
                    id_filter_save = data;
                    
                    $("#info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "success_create_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
    
                // Close dialog.
                $("#save-filter-select").dialog('close');
            }
        );
    }
    
    function save_update_filter() {
        var id_filter_update =  $("#overwrite_filter").val();
        var name_filter_update = $("#overwrite_filter option[value='"+id_filter_update+"']").text();

        var custom_fields_values = $('input[name^="ag_custom_fields"]').map(function() {
            return this.value;
        }).get();

        var custom_fields_ids = $("input[name^='ag_custom_fields']").map(function() {
            var name = $(this).attr("name");
            var number = name.match(/\[(.*?)\]/)[1];

            return number;
        }).get();

        var ag_custom_fields = custom_fields_ids.reduce(function(result, custom_fields_id, index) {
            result[custom_fields_id] = custom_fields_values[index];
            return result;
        }, {});

        jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            {
                "page" : "include/ajax/module",
                "update_monitor_filter" : 1,
                "id" : $("#overwrite_filter").val(),
                "ag_group" : $("#ag_group").val(),
                "modulegroup" : $("#modulegroup").val(),
                "recursion" : $("#checkbox-recursion").is(':checked'),
                "status" : $("#status").val(),
                "severity" : $("#severity").val(),
                "ag_modulename" : $("#text-ag_modulename").val(),
                "ag_freestring" : $("#text-ag_freestring").val(),
                "tag_filter" : $("#tag_filter").val(),
                "moduletype" : $("#moduletype").val(),
                "module_option" : $('#module_option').val(),
                "min_hours_status" : $('#text-min_hours_status').val(),
                "datatype" : $("#datatype").val(),
                "not_condition" : $("#not_condition_switch").is(':checked'),
                "ag_custom_fields": JSON.stringify(ag_custom_fields),
            },
            function (data) {
                $(".info_box").hide();
                if (data == 'ok') {
                    $(".info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "success_update_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
                else {
                    $(".info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "error_create_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
            });
            
            // First remove all options of filters select
            $('#filter_id').find('option').remove().end();
            // Add 'none' option the first
            $('#filter_id').append ($('<option></option>').html ( <?php echo "'".__('none')."'"; ?> ).attr ("value", 0));    
            // Reload filters select
            jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                {
                    "page" : "include/ajax/module",
                    "get_monitor_filters" : 1
                },
                function (data) {
                    jQuery.each (data, function (i, val) {
                        s = js_html_entity_decode(val);
                        if (i == id_filter_update) {
                            $('#filter_id').append ($('<option selected="selected"></option>').html (s).attr ("value", i));
                        }
                        else {
                            $('#filter_id').append ($('<option></option>').html (s).attr ("value", i));
                        }
                    });
                },
                "json"
                );
                
            // Close dialog
            $('.ui-dialog-titlebar-close').trigger('click');
            
            // Update the info with the loaded filter
            $("#hidden-id_name").val($('#text-id_name').val());
            $('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + name_filter_update);
            return false;
    }

    function save_delete_filter() {
        var id_filter_update =  $("#overwrite_filter").val();

        jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            {
                "page" : "include/ajax/module",
                "delete_monitor_filter" : 1,
                "id" : $("#overwrite_filter").val(),
            },
            function (data) {
                $(".info_box").hide();
                if (data == 'ok') {
                    $(".info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "success_update_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
                else {
                    $(".info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "error_create_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
            });
            
        // First remove all options of filters select.
        $('#filter_id').find('option').remove().end();

        // Add 'none' option.
        $('#filter_id').append ($('<option></option>').html ( <?php echo "'".__('None')."'"; ?> ).attr ("value", 0));    

        // Reload filters select.
        jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            {
                "page" : "include/ajax/module",
                "get_monitor_filters" : 1
            },
            function (data) {
                jQuery.each (data, function (i, val) {
                    s = js_html_entity_decode(val);
                    if (i == id_filter_update) {
                        $('#filter_id').append ($('<option selected="selected"></option>').html (s).attr ("value", i));
                    }
                    else {
                        $('#filter_id').append ($('<option></option>').html (s).attr ("value", i));
                    }
                });
            },
            "json"
        );
            
        // Close dialog
        $('.ui-dialog-titlebar-close').trigger('click');

        return false;
    }
    
    $(document).ready(function() {
        show_save_filter();
    });
    </script>
        <?php
        return;
    }

    if ($get_combo_modules === true) {
        $id_agent = get_parameter('id_source');
        $modules = db_get_all_rows_filter(
            'tagente_modulo',
            ['id_agente' => $id_agent],
            [
                'id_agente_modulo as id',
                'nombre as name',
            ]
        );

        echo json_encode($modules);
        return;
    }
}
