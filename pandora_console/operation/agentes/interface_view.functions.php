<?php
/**
 *  Pandora FMS - http://pandorafms.com
 *  ==================================================
 *  Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 *  Please see http://pandorafms.org for full contribution list
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; version 2
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 */


/**
 * Print filters for interface view.
 *
 * @param string sec Section (type of filter will vary across sections).
 */
function print_filters($sec)
{
    $table = new StdClass();
    $table->width = '100%';
    $table->cellspacing = 0;
    $table->cellpadding = 0;
    $table->class = 'databox filters';

    if ($sec === 'view') {
        $table->style[0] = 'font-weight: bold;';
        $table->style[1] = 'font-weight: bold;';
        $table->style[2] = 'font-weight: bold;';
        $table->style[3] = 'font-weight: bold;';
        $table->style[4] = 'font-weight: bold;';

        $table->data[0][0] = __('Group');
        $table->data[0][1] .= html_print_select_groups(
            $config['id_user'],
            'AR',
            true,
            'group_id',
            $ag_group,
            '',
            '',
            '0',
            true,
            false,
            false,
            '',
            false,
            '',
            false,
            false,
            'id_grupo',
            false
        );

        $table->data[0][2] = __('Recursion');

        $table->data[0][2] .= html_print_input(
            [
                'type'    => 'checkbox',
                'name'    => 'recursion',
                'return'  => true,
                'checked' => $recursion,
                'value'   => 1,
            ]
        );

        $table->data[1][0] = __('Agents');

        if (empty($agents) === true || $agents == -1) {
            $agents = [];
        }

        $table->data[1][1] = html_print_select(
            [],
            'selected_agents[]',
            '',
            '',
            '',
            0,
            true,
            true,
            true,
            '',
            false,
            'min-width: 180px; max-width: 200px;'
        );

        // Interfaces.
        $table->data[1][3] = '<b>'.__('Interfaces').'</b>';
        $table->data[1][4] = html_print_select(
            [],
            'selected_interfaces[]',
            $selected_interfaces,
            '',
            '',
            0,
            true,
            true,
            true,
            '',
            false,
            'min-width: 180px; max-width: 200px;'
        );

        $filters = '<form method="post" action="'.ui_get_url_refresh(
            [
                'selected_agents'     => $selected_agents,
                'selected_interfaces' => $selected_interfaces,
                'selected_group_id'   => $selected_group_id,
            ]
        ).'">';

        $filters .= html_print_table($table, true);

        $filters .= "<div class='height_100p right'>".html_print_submit_button(
            __('Show'),
            'uptbutton',
            false,
            'class="sub search mgn_tp_0"',
            true
        ).'</div>';

        $filters .= '</form>';
    } else {
        $table->style[0] = 'font-weight: bold;';

        $table->data[0][0] = '<b>'.__('Interfaces').'</b>';
        $table->data[0][1] = html_print_select(
            [],
            'selected_interfaces[]',
            $selected_interfaces,
            '',
            '',
            0,
            true,
            true,
            true,
            '',
            false,
            'min-width: 180px; max-width: 200px;'
        );

        $filters = '<form method="post" action="'.ui_get_url_refresh(
            [
                'selected_agents'     => $selected_agents,
                'selected_interfaces' => $selected_interfaces,
                'selected_group_id'   => $selected_group_id,
            ]
        ).'">';

        $filters .= html_print_table($table, true);

        $filters .= "<div class='height_100p right'>".html_print_submit_button(
            __('Show'),
            'uptbutton',
            false,
            'class="sub search mgn_tp_0"',
            true
        ).'</div>';

        $filters .= '</form>';
    }

    ui_toggle(
        $filters,
        __('Interface filter'),
        __('Interface filter'),
        'ui_toggle_if_filter'
    );

    unset($table);
}


/**
 * Print interfaces table.
 *
 * @param array data Array containing data of interfaces.
 * @param array selected_agents Selected agents.
 * @param array selected_interfaces Selected interfaces.
 * @param string sort_field Field used to sort table.
 * @param string sort Direction used to sort by field.
 * @param int pagination_index Active page (used for pagination).
 * @param string sec Active section of page.
 */
function print_table(
    $data,
    $selected_agents,
    $selected_interfaces,
    $sort_field,
    $sort,
    $pagination_index,
    $sec
) {
    global $config;

    $selected = true;
    $select_if_name_up = false;
    $select_if_name_down = false;
    $select_if_speed_data_up = false;
    $select_if_speed_data_down = false;
    $select_if_in_octets_up = false;
    $select_if_in_octets_down = false;
    $select_if_out_octets_up = false;
    $select_if_out_octets_down = false;
    $select_if_usage_module_data_in_up = false;
    $select_if_usage_module_data_in_down = false;
    $select_if_usage_module_data_out_up = false;
    $select_if_usage_module_data_out_down = false;
    $select_if_last_data_up = false;
    $select_if_last_data_down = false;
    $order = null;

    switch ($sort_field) {
        case 'if_agent_name':
            switch ($sort) {
                case 'up':
                    $select_if_agent_name_up = $selected;
                    $order = [
                        'field' => 'if_agent_name',
                        'order' => 'ASC',
                    ];
                break;

                case 'down':
                default:
                    $select_if_agent_name_down = $selected;
                    $order = [
                        'field' => 'if_agent_name',
                        'order' => 'DESC',
                    ];
                break;
            }
        break;

        case 'if_name':
            switch ($sort) {
                case 'up':
                    $select_if_name_up = $selected;
                    $order = [
                        'field' => 'if_name',
                        'order' => 'ASC',
                    ];
                break;

                case 'down':
                default:
                    $select_if_name_down = $selected;
                    $order = [
                        'field' => 'if_name',
                        'order' => 'DESC',
                    ];
                break;
            }
        break;

        case 'if_speed_data':
            switch ($sort) {
                case 'up':
                    $select_if_speed_data_up = $selected;
                    $order = [
                        'field' => 'if_speed_data',
                        'order' => 'ASC',
                    ];
                break;

                case 'down':
                default:
                    $select_if_speed_data_down = $selected;
                    $order = [
                        'field' => 'if_speed_data',
                        'order' => 'DESC',
                    ];
                break;
            }
        break;

        case 'if_in_octets':
            switch ($sort) {
                case 'up':
                    $select_if_in_octets_up = $selected;
                    $order = [
                        'field' => 'if_in_octets',
                        'order' => 'ASC',
                    ];
                break;

                case 'down':
                default:
                    $select_if_in_octets_down = $selected;
                    $order = [
                        'field' => 'if_in_octets',
                        'order' => 'DESC',
                    ];
                break;
            }
        break;

        case 'if_out_octets':
            switch ($sort) {
                case 'up':
                    $select_if_out_octets_up = $selected;
                    $order = [
                        'field' => 'if_out_octets',
                        'order' => 'ASC',
                    ];
                break;

                case 'down':
                default:
                    $select_if_out_octets_down = $selected;
                    $order = [
                        'field' => 'if_out_octets',
                        'order' => 'DESC',
                    ];
                break;
            }
        break;

        case 'if_usage_module_data_in':
            switch ($sort) {
                case 'up':
                    $select_if_usage_module_data_in_up = $selected;
                    $order = [
                        'field' => 'if_usage_module_data_in',
                        'order' => 'ASC',
                    ];
                break;

                case 'down':
                default:
                    $select_if_usage_module_data_in_down = $selected;
                    $order = [
                        'field' => 'if_usage_module_data_in',
                        'order' => 'DESC',
                    ];
                break;
            }
        break;

        case 'if_usage_module_data_out':
            switch ($sort) {
                case 'up':
                    $select_if_usage_module_data_out_up = $selected;
                    $order = [
                        'field' => 'if_usage_module_data_out',
                        'order' => 'ASC',
                    ];
                break;

                case 'down':
                default:
                    $select_if_usage_module_data_out_down = $selected;
                    $order = [
                        'field' => 'if_usage_module_data_out',
                        'order' => 'DESC',
                    ];
                break;
            }
        break;

        case 'if_last_data':
            switch ($sort) {
                case 'up':
                    $select_if_last_data_up = $selected;
                    $order = [
                        'field' => 'if_last_data',
                        'order' => 'ASC',
                    ];
                break;

                case 'down':
                default:
                    $select_if_last_data_down = $selected;
                    $order = [
                        'field' => 'if_last_data',
                        'order' => 'DESC',
                    ];
                break;
            }
        break;

        default:
            $select_if_agent_name = ($sec === 'view') ? true : false;
            $select_if_name_up = ($sec === 'estado') ? true : false;
            $select_if_name_down = false;
            $select_if_speed_data_up = false;
            $select_if_speed_data_down = false;
            $select_if_in_octets_up = false;
            $select_if_in_octets_down = false;
            $select_if_out_octets_up = false;
            $select_if_out_octets_down = false;
            $select_if_usage_module_data_in_up = false;
            $select_if_usage_module_data_in_down = false;
            $select_if_usage_module_data_out_up = false;
            $select_if_usage_module_data_out_down = false;
            $select_if_last_data_up = false;
            $select_if_last_data_down = false;
        break;
    }

    if ($sec === 'estado') {
        $agent_id = (int) get_parameter('id_agente', 0);

        $sort_url_page = 'ver_agente';
        $sec = 'estado';
        $query_params = '&id_agente='.$agent_id.'&tab=interface';
    } else {
        $sort_url_page = 'interface_view';
        $sec = 'view';
        $query_params = '';
    }

    // Build URLs to sort the table.
    $url_if_agent_name = 'index.php?sec='.$sec.'&sec2=operation/agentes/'.$sort_url_page.$query_params;
    $url_if_name = 'index.php?sec='.$sec.'&sec2=operation/agentes/'.$sort_url_page.$query_params;
    $url_if_speed = 'index.php?sec='.$sec.'&sec2=operation/agentes/'.$sort_url_page.$query_params;
    $url_if_in_octets = 'index.php?sec='.$sec.'&sec2=operation/agentes/'.$sort_url_page.$query_params;
    $url_if_out_octets = 'index.php?sec='.$sec.'&sec2=operation/agentes/'.$sort_url_page.$query_params;
    $url_if_bandwidth_usage_in = 'index.php?sec='.$sec.'&sec2=operation/agentes/'.$sort_url_page.$query_params;
    $url_if_bandwidth_usage_out = 'index.php?sec='.$sec.'&sec2=operation/agentes/'.$sort_url_page.$query_params;
    $last_data = 'index.php?sec='.$sec.'&sec2=operation/agentes/'.$sort_url_page.$query_params;

    $selected_agents_query_str = '';
    $selected_interfaces_query_str = '';

    foreach ($selected_agents as $key => $agent) {
        $selected_agents_query_str .= '&selected_agents['.$key.']='.$agent;
    }

    foreach ($selected_interfaces as $key => $interface) {
        $selected_interfaces_query_str .= '&selected_interfaces['.$key.']='.$interface;
    }

    $url_if_agent_name .= $selected_agents_query_str.'&'.$selected_interfaces_query_str;
    $url_if_name .= $selected_agents_query_str.'&'.$selected_interfaces_query_str;
    $url_if_speed .= $selected_agents_query_str.'&'.$selected_interfaces_query_str;
    $url_if_in_octets .= $selected_agents_query_str.'&'.$selected_interfaces_query_str;
    $url_if_out_octets .= $selected_agents_query_str.'&'.$selected_interfaces_query_str;
    $url_if_bandwidth_usage_in .= $selected_agents_query_str.'&'.$selected_interfaces_query_str;
    $url_if_bandwidth_usage_out .= $selected_agents_query_str.'&'.$selected_interfaces_query_str;
    $last_data .= $selected_agents_query_str.'&'.$selected_interfaces_query_str;

    $url_if_agent_name .= '&recursion='.$recursion;
    $url_if_name .= '&recursion='.$recursion;
    $url_if_speed .= '&recursion='.$recursion;
    $url_if_in_octets .= '&recursion='.$recursion;
    $url_if_out_octets .= '&recursion='.$recursion;
    $url_if_bandwidth_usage_in .= '&recursion='.$recursion;
    $url_if_bandwidth_usage_out .= '&recursion='.$recursion;
    $last_data .= '&recursion='.$recursion;

    $url_if_agent_name .= '&sort_field=if_agent_name&sort=';
    $url_if_name .= '&sort_field=if_name&sort=';
    $url_if_speed .= '&sort_field=if_speed_data&sort=';
    $url_if_in_octets .= '&sort_field=if_in_octets&sort=';
    $url_if_out_octets .= '&sort_field=if_out_octets&sort=';
    $url_if_bandwidth_usage_in .= '&sort_field=if_usage_module_data_in&sort=';
    $url_if_bandwidth_usage_out .= '&sort_field=if_usage_module_data_out&sort=';
    $last_data .= '&sort_field=if_last_data&sort=';

    if (empty($data) === false) {
        $table = new StdClass();
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->width = '100%';
        $table->class = 'info_table';
        $table->head = [];
        $table->data = [];
        $table->size = [];
        $table->align = [];

        $show_fields = explode(',', $config['status_monitor_fields']);

        if ($sec === 'view') {
            $table->head[0] = __('Agent');
            $table->head[0] .= ui_get_sorting_arrows(
                $url_if_agent_name.'up',
                $url_if_agent_name.'down',
                $select_if_agent_name_up,
                $select_if_agent_name_down
            );
        }

        $table->head[1] = __('IfName');
        $table->head[1] .= ui_get_sorting_arrows(
            $url_if_name.'up',
            $url_if_name.'down',
            $select_if_name_up,
            $select_if_name_down
        );

        $table->head[2] = __('Status');

        $table->head[3] = __('IfSpeed');
        $table->head[3] .= ui_get_sorting_arrows(
            $url_if_speed.'up',
            $url_if_speed.'down',
            $select_if_speed_data_up,
            $select_if_speed_data_down
        );

        $table->head[4] = __('IfInOctets');
        $table->head[4] .= ui_get_sorting_arrows(
            $url_if_in_octets.'up',
            $url_if_in_octets.'down',
            $select_if_in_octets_up,
            $select_if_in_octets_down
        );

        $table->head[5] = __('IfOutOctets');
        $table->head[5] .= ui_get_sorting_arrows(
            $url_if_out_octets.'up',
            $url_if_out_octets.'down',
            $select_if_out_octets_up,
            $select_if_out_octets_down
        );

        $table->head[6] = __('% Bandwidth usage (in)');
        $table->head[6] .= ui_get_sorting_arrows(
            $url_if_bandwidth_usage_in.'up',
            $url_if_bandwidth_usage_in.'down',
            $select_if_usage_module_data_in_up,
            $select_if_usage_module_data_in_down
        );

        $table->head[7] = __('% Bandwidth usage (out)');
        $table->head[7] .= ui_get_sorting_arrows(
            $url_if_bandwidth_usage_out.'up',
            $url_if_bandwidth_usage_out.'down',
            $select_if_usage_module_data_out_up,
            $select_if_usage_module_data_out_down
        );

        $table->head[8] = __('Last data');
        $table->head[8] .= ui_get_sorting_arrows(
            $last_data.'up',
            $last_data.'down',
            $select_if_last_data_up,
            $select_if_last_data_down
        );

        $loop_index = 0;
        $table_data = [];

        $interfaces_array = array_column($data, 'interfaces');
        $agents = array_column($data, 'name');

        $all_interfaces = [];

        foreach ($data as $value) {
            $agent_alias = agents_get_alias($value['name']);

            foreach ($value['interfaces'] as $if_name => $interface) {
                $interface['agent_id'] = $value['name'];
                $interface['agent_alias'] = $agent_alias;
                $interface['if_name'] = $if_name;
                $all_interfaces[$if_name] = $interface;
            }
        }

        if ($sec === 'estado'
            && is_array($selected_interfaces) === true
            && empty($selected_interfaces) === true
        ) {
            $filtered_interfaces = $all_interfaces;
        } else {
            // Filter interfaces array.
            $filtered_interfaces = array_filter(
                $all_interfaces,
                function ($interface) use ($selected_interfaces) {
                    return in_array(
                        $interface['status_module_id'],
                        $selected_interfaces
                    ) === true;
                }
            );
        }

        $data = [];

        foreach ($filtered_interfaces as $if_name => $agent_interfaces) {
            // Get usage modules.
            $usage_module_in = db_get_row(
                'tagente_modulo',
                'nombre',
                $if_name.'_inUsage'
            );
            $usage_module_out = db_get_row(
                'tagente_modulo',
                'nombre',
                $if_name.'_outUsage'
            );

            $usage_module_id_in = $usage_module_in['id_agente_modulo'];
            $usage_module_id_out = $usage_module_out['id_agente_modulo'];
            $usage_module_description = $usage_module_in['descripcion'];

            // Get usage modules data.
            $usage_module_data_in = modules_get_previous_data(
                $usage_module_id_in,
                time()
            );

            $usage_module_data_out = modules_get_previous_data(
                $usage_module_id_out,
                time()
            );

            // Extract ifSpeed from description of usage module.
            $if_speed_str = strstr($usage_module_description, 'Speed:');
            $if_speed_str = substr($if_speed_str, 0, -1);
            $if_speed_str = explode(':', $if_speed_str)[1];

            $matches = [];
            preg_match_all('/\d+/', $if_speed_str, $matches);

            $if_speed_value = $matches[0][0];

            // Transform ifSpeed unit.
            $divisor = 1000;
            $counter = 0;
            while ($if_speed_value >= $divisor) {
                if ($if_speed_value >= $divisor) {
                    $if_speed_value = ($if_speed_value / $divisor);
                }

                $counter++;
            }

            $if_speed_unit = 'bps';

            switch ($counter) {
                case 1:
                    $if_speed_unit = 'Kbps';
                break;

                case 2:
                    $if_speed_unit = 'Mbps';
                break;

                case 3:
                    $if_speed_unit = 'Gbps';
                break;

                case 4:
                    $if_speed_unit = 'Tbps';
                break;

                default:
                    $if_speed_unit = 'bps';
                break;
            }

            // Get in and out traffic.
            $ifInOctets = modules_get_previous_data(
                $agent_interfaces['traffic']['in'],
                time()
            );
            $ifOutOctets = modules_get_previous_data(
                $agent_interfaces['traffic']['out'],
                time()
            );

            if ($sec === 'view') {
                $table_data[$loop_index]['if_agent_name'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente='.$agent_interfaces['agent_id'].'">'.$agent_interfaces['agent_alias'].'</a>';
            }

            $table_data[$loop_index]['if_name'] = $agent_interfaces['if_name'];
            $table_data[$loop_index]['if_status_image'] = $agent_interfaces['status_image'];
            $table_data[$loop_index]['if_speed_data'] = ($if_speed_value === null) ? __('N/A') : $if_speed_value.' '.$if_speed_unit;
            $table_data[$loop_index]['if_in_octets'] = ($ifInOctets['datos'] === null) ? __('N/A') : $ifInOctets['datos'];
            $table_data[$loop_index]['if_out_octets'] = ($ifOutOctets['datos'] === null) ? __('N/A') : $ifOutOctets['datos'];
            $table_data[$loop_index]['if_usage_module_data_in'] = ($usage_module_data_in['datos'] === null) ? __('N/A') : $usage_module_data_in['datos'];
            $table_data[$loop_index]['if_usage_module_data_out'] = ($usage_module_data_out['datos'] === null) ? __('N/A') : $usage_module_data_out['datos'];
            $table_data[$loop_index]['if_last_data'] = human_time_comparation($agent_interfaces['last_contact']);

            $loop_index++;
        }

        // Sort array of previously processed table values.
        if ($sort === 'up') {
            $res = usort(
                $table_data,
                function ($a, $b) use ($sort_field) {
                    if ($a[$sort_field] > $b[$sort_field]) {
                        return 1;
                    } else {
                        return -1;
                    }
                }
            );
        }

        if ($sort === 'down') {
            $res = usort(
                $table_data,
                function ($a, $b) use ($sort_field) {
                    if ($b[$sort_field] > $a[$sort_field]) {
                        return 1;
                    } else {
                        return -1;
                    }
                }
            );
        }

        $sliced_table_data = array_slice(
            $table_data,
            $pagination_index,
            $config['block_size']
        );

        foreach ($sliced_table_data as $value) {
            array_push($table->data, array_values($value));
        }

        html_print_table($table);

        if (count($selected_interfaces) > $config['block_size']) {
            ui_pagination(count($selected_interfaces), false, $pagination_index, 0, false, 'offset', true, 'pagination-bottom');
        }
    } else {
            ui_print_info_message(['no_close' => true, 'message' => __('No search parameters')]);
    }
}
