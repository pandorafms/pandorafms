<?php
/**
 * Module groups.
 *
 * @category   Extensions
 * @package    Pandora FMS
 * @subpackage Module groups view.
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

// Begin.
global $config;

check_login();
// ACL Check.
if (check_acl($config['id_user'], 0, 'AR') === 0 && check_acl($config['id_user'], 0, 'RR') === 0) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Module Groups view'
    );
    include 'general/noaccess.php';
    exit;
}

if (is_ajax()) {
    $get_info_alert_module_group = (bool) get_parameter('get_info_alert_module_group');

    if ($get_info_alert_module_group) {
        $send_tooltip = json_decode(io_safe_output(get_parameter('send_tooltip')), true);
        echo "<ul class='tooltip_counters'><h3>".__('Counters Module').'</h3>';
        echo "<li><div style='background-color: ".COL_ALERTFIRED.";'></div>".__('Alerts_Fired').': '.$send_tooltip['alerts_module_count'].'</li>';
        echo "<li><div style='background-color: ".COL_CRITICAL.";'></div>".__('Critical').': '.$send_tooltip['critical_module_count'].'</li>';
        echo "<li><div style='background-color: ".COL_WARNING.";'></div>".__('warning').': '.$send_tooltip['warning_module_count'].'</li>';
        echo "<li><div style='background-color: ".COL_UNKNOWN.";'></div>".__('Unknown').': '.$send_tooltip['unknown_module_count'].'</li>';
        echo "<li><div style='background-color: ".COL_NORMAL.";'></div>".__('OK').': '.$send_tooltip['normal_module_count'].'</li>';
        echo "<li><div style='background-color: ".COL_MAINTENANCE.";'></div>".__('Not_init').': '.$send_tooltip['notInit_module_count'].'</li></ul>';
    }
}


/**
 * The main function of module groups and the enter point to
 * execute the code.
 *
 * @return void
 */
function mainModuleGroups()
{
    global $config;

    include_once $config['homedir'].'/include/class/TreeGroup.class.php';
    include_once $config['homedir'].'/include/functions_groupview.php';

    $tree_group = new TreeGroup('group', 'group');
    $tree_group->setPropagateCounters(false);
    $tree_group->setDisplayAllGroups(true);
    $tree_group->setFilter(
        [
            'searchAgent'           => '',
            'statusAgent'           => AGENT_STATUS_ALL,
            'searchModule'          => '',
            'statusModule'          => -1,
            'groupID'               => 0,
            'tagID'                 => 0,
            'show_not_init_agents'  => 1,
            'show_not_init_modules' => 1,
        ]
    );
    $info = $tree_group->getArray();
    $info = groupview_plain_groups($info);
    $offset = get_parameter('offset', 0);
    $agent_group_search = get_parameter('agent_group_search', '');
    $module_group_search = get_parameter('module_group_search', '');

    // Check the user's group permissions.
    $user_groups = users_get_groups($config['user'], 'AR');
    $info = array_filter(
        $info,
        function ($v) use ($user_groups) {
            return $user_groups[$v['id']] != null;
        },
        ARRAY_FILTER_USE_BOTH
    );

    $info = array_filter(
        $info,
        function ($v) use ($agent_group_search) {
            return preg_match(
                '/'.$agent_group_search.'/i',
                $v['name']
            );
        },
        ARRAY_FILTER_USE_BOTH
    );

    if (empty($info) === false) {
        $groups_view = ($is_not_paginated) ? $info : array_slice(
            $info,
            $offset,
            $config['block_size']
        );
        $agents_counters = array_reduce(
            $groups_view,
            function ($carry, $item) {
                $carry[$item['id']] = $item;
                return $carry;
            },
            []
        );

        $ids_array = array_keys($agents_counters);

        $ids_group = implode(',', $ids_array);
    } else {
        $ids_group = -1;
    }

    $counter = count($info);

    $condition_critical = modules_get_state_condition(AGENT_MODULE_STATUS_CRITICAL_ALERT);
    $condition_warning  = modules_get_state_condition(AGENT_MODULE_STATUS_WARNING_ALERT);
    $condition_unknown  = modules_get_state_condition(AGENT_MODULE_STATUS_UNKNOWN);
    $condition_not_init = modules_get_state_condition(AGENT_MODULE_STATUS_NO_DATA);
    $condition_normal   = modules_get_state_condition(AGENT_MODULE_STATUS_NORMAL);

    $array_for_defect = [];
    $array_module_group = [];
    $array_data = [];

    $sql = 'SELECT id_mg, `name` FROM tmodule_group';
    $array_mod = db_get_all_rows_sql($sql);

    foreach ($array_mod as $key => $value) {
        $array_module_group[$value['id_mg']] = $value['name'];
    }

    $array_module_group[0] = 'Nothing';

    $array_module_group = array_filter(
        $array_module_group,
        function ($v) use ($module_group_search) {
            return preg_match('/'.$module_group_search.'/i', $v);
        },
        ARRAY_FILTER_USE_BOTH
    );

    foreach ($agents_counters as $key => $value) {
        $array_for_defect[$key]['gm'] = $array_module_group;
        $array_for_defect[$key]['data']['name'] = $value['name'];
        $array_for_defect[$key]['data']['parent'] = $value['parent'];
        $array_for_defect[$key]['data']['icon'] = $value['icon'];
    }

    $sql = sprintf(
        "SELECT SUM(IF(tae.alert_fired <> 0, 1, 0)) AS alerts_module_count,
            SUM(IF(%s, 1, 0)) AS warning_module_count,
            SUM(IF(%s, 1, 0)) AS unknown_module_count,
            SUM(IF(%s, 1, 0)) AS notInit_module_count,
            SUM(IF(%s, 1, 0)) AS critical_module_count,
            SUM(IF(%s, 1, 0)) AS normal_module_count,
            COUNT(tae.id_agente_modulo) AS total_count,
            tmg.id_mg,
            tmg.name as n,
            tg.id_grupo
        FROM (
            SELECT tam.id_agente_modulo,
                tam.id_module_group,
                ta.id_grupo AS g,
                tae.estado,
                SUM(IF(tatm.last_fired <> 0, 1, 0)) AS alert_fired
                FROM tagente_modulo tam
                LEFT JOIN talert_template_modules tatm
                    ON tatm.id_agent_module = tam.id_agente_modulo
                    AND tatm.times_fired = 1
                LEFT JOIN tagente_estado tae
                    ON tae.id_agente_modulo = tam.id_agente_modulo
                INNER JOIN tagente ta
                    ON ta.id_agente = tam.id_agente
                WHERE ta.disabled = 0
                    AND tam.disabled = 0
                    AND tam.id_modulo <> 0
                    AND tam.delete_pending = 0
                    AND ta.id_grupo IN (%s)
                GROUP BY tam.id_agente_modulo
            UNION ALL
            SELECT tam.id_agente_modulo,
                tam.id_module_group,
                tasg.id_group AS g,
                tae.estado,
                SUM(IF(tatm.last_fired <> 0, 1, 0)) AS alert_fired
                FROM tagente_modulo tam
                LEFT JOIN talert_template_modules tatm
                    ON tatm.id_agent_module = tam.id_agente_modulo
                    AND tatm.times_fired = 1
                LEFT JOIN tagente_estado tae
                    ON tae.id_agente_modulo = tam.id_agente_modulo
                INNER JOIN tagente ta
                    ON ta.id_agente = tam.id_agente
                INNER JOIN tagent_secondary_group tasg
                    ON ta.id_agente = tasg.id_agent
                WHERE ta.disabled = 0
                    AND tam.disabled = 0
                    AND tam.delete_pending = 0
                    AND tasg.id_group IN (%s)
                GROUP BY tam.id_agente_modulo, tasg.id_group
        ) AS tae
        RIGHT JOIN tgrupo tg
            ON tg.id_grupo = tae.g
        INNER JOIN (
            SELECT * FROM tmodule_group
            UNION ALL
            SELECT 0 AS 'id_mg', 'Nothing' AS 'name'
        ) AS tmg
            ON tae.id_module_group = tmg.id_mg
        GROUP BY tae.g, tmg.id_mg",
        $condition_warning,
        $condition_unknown,
        $condition_not_init,
        $condition_critical,
        $condition_normal,
        $ids_group,
        $ids_group
    );

    $array_data_prev = db_get_all_rows_sql($sql);

    foreach ($array_data_prev as $key => $value) {
        $array_data[$value['id_grupo']][$value['id_mg']] = $value;
    }

    // Header.
    ui_print_standard_header(
        __('Combined table of agent group and module group'),
        'images/module_group.png',
        false,
        '',
        false,
        [],
        [
            [
                'link'  => '',
                'label' => __('Monitoring'),
            ],
            [
                'link'  => '',
                'label' => __('Views'),
            ],
        ]
    );

    $output = "<form method='post'
    action='index.php?sec=view&sec2=extensions/module_groups'>";

    $output .= "<table cellpadding='4' cellspacing='4' class='filter-table-adv margin-bottom-10' width='100%'><tr>";
    $output .= '<td>';
    $output .= html_print_label_input_block(
        __('Search by agent group'),
        html_print_input_text(
            'agent_group_search',
            $agent_group_search,
            '',
            50,
            255,
            true
        )
    );

    $output .= '</td><td>';
    $output .= html_print_label_input_block(
        __('Search by module group'),
        html_print_input_text(
            'module_group_search',
            $module_group_search,
            '',
            50,
            255,
            true
        )
    );
    $output .= '</td>';
    $output .= '</tr></table>';

    $output .= html_print_div(
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

    $output .= '</form>';

    ui_toggle(
        $output,
        '<span class="subsection_header_title">'.__('Filters').'</span>',
        'filter_form',
        '',
        true,
        false,
        '',
        'white-box-content',
        'box-flat white_table_graph fixed_filter_bar'
    );

    $cell_style = '
        min-width: 60px;
        width: 100%;
        margin: 0;
        overflow:hidden;
        text-align: center;
        padding: 5px;
        padding-bottom:10px;
        font-size: 18px;
        text-align: center;
    ';

    if ($info && $array_module_group) {
        $table = new StdClass();
        $table->class = 'info_table';
        $table->style[0] = 'font-weight: bolder; min-width: 230px;';
        $table->width = '100%';

        $head[0] = __('Groups');
        $headstyle[0] = 'width: 20%;  font-weight: bolder;';
        foreach ($array_module_group as $key => $value) {
            $headstyle[] = 'min-width: 60px;max-width: 5%;text-align:center; font-weight: bolder;';
            $head[] = ui_print_truncate_text(
                $value,
                GENERIC_SIZE_TEXT,
                true,
                true,
                true,
                '&hellip;'
            );
        }

        $i = 0;
        foreach ($array_for_defect as $key => $value) {
            $deep = groups_get_group_deep($key);
            $data[$i][0] = $deep.ui_print_truncate_text(
                $value['data']['name'],
                GENERIC_SIZE_TEXT,
                true,
                true,
                true,
                '&hellip;'
            );
            $j = 1;
            if (isset($array_data[$key])) {
                foreach ($value['gm'] as $k => $v) {
                    if (isset($array_data[$key][$k])) {
                        $send_tooltip = json_encode($array_data[$key][$k]);
                        $rel = 'ajax.php?page=extensions/module_groups&get_info_alert_module_group=1&send_tooltip='.$send_tooltip;
                        $url = 'index.php?sec=estado&sec2=operation/agentes/status_monitor&status=-1&ag_group='.$key.'&modulegroup='.$k;

                        if ($array_data[$key][$k]['alerts_module_count'] != 0) {
                            $color = COL_ALERTFIRED;
                            // Orange when the cell for this model group and agent has at least one alert fired.
                        } else if ($array_data[$key][$k]['critical_module_count'] != 0) {
                            $color = COL_CRITICAL;
                            // Red when the cell for this model group and agent
                            // has at least one module in critical state and the rest in any state.
                        } else if ($array_data[$key][$k]['warning_module_count'] != 0) {
                            $color = COL_WARNING;
                            // Yellow when the cell for this model group and agent
                            // has at least one in warning state and the rest in green state.
                        } else if ($array_data[$key][$k]['unknown_module_count'] != 0) {
                            $color = COL_UNKNOWN;
                            // Grey when the cell for this model group and agent
                            // has at least one module in unknown state and the rest in any state.
                        } else if ($array_data[$key][$k]['normal_module_count'] != 0) {
                            $color = COL_NORMAL;
                            // Green when the cell for this model group and agent has OK state all modules.
                        } else if ($array_data[$key][$k]['notInit_module_count'] != 0) {
                            $color = COL_NOTINIT;
                            // Blue when the cell for this module group and all modules have not init value.
                        }

                        $data[$i][$j] = "<div style='".$cell_style.'background:'.$color.";'>";
                        $data[$i][$j] .= "<a class='info_cell white font_18px' rel='".$rel."' href='".$url."'>";
                        $data[$i][$j] .= $array_data[$key][$k]['total_count'];
                        $data[$i][$j] .= '</a></div>';
                    } else {
                        $data[$i][$j] = "<div style='background:".$background_color.';'.$cell_style."'>";
                        $data[$i][$j] .= 0;
                        $data[$i][$j] .= '</div>';
                    }

                    $j++;
                }
            } else {
                foreach ($value['gm'] as $k => $v) {
                    $data[$i][$j] = "<div class='module_gm_groups' style='background:".$background_color."'>";
                    $data[$i][$j] .= 0;
                    $data[$i][$j] .= '</div>';
                    $j++;
                }
            }

            $i++;
        }

        $table->head = $head;
        $table->headstyle = $headstyle;
        $table->data = $data;

        echo "<div class='w100p' style='overflow-x:auto;'>";
            html_print_table($table);
        echo '</div>';

        $tablePagination = ui_pagination(
            $counter,
            false,
            0,
            0,
            true,
            'offset',
            false
        );

        html_print_action_buttons(
            '',
            [ 'right_content' => $tablePagination ]
        );

        $show_legend = '<div>';
            $show_legend .= '<table>';
                $show_legend .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_ALERTFIRED.";'></div></td><td>".__('Orange cell when the module group and agent have at least one alarm fired.').'</td></tr>';
                $show_legend .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_CRITICAL.";'></div></td><td>".__('Red cell when the module group and agent have at least one module in critical status and the others in any status').'</td></tr>';
                $show_legend .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_WARNING.";'></div></td><td>".__('Yellow cell when the module group and agent have at least one in warning status and the others in grey or green status').'</td></tr>';
                $show_legend .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_UNKNOWN.";'></div></td><td>".__('Grey cell when the module group and agent have at least one in unknown status and the others in green status').'</td></tr>';
                $show_legend .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_NORMAL.";'></div></td><td>".__('Green cell when the module group and agent have all modules in OK status').'</td></tr>';
                $show_legend .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_NOTINIT.";'></div></td><td>".__('Blue cell when the module group and agent have all modules in not init status.').'</td></tr>';
            $show_legend .= '</table>';
        $show_legend .= '</div>';

        ui_toggle($show_legend, __('Legend'));
    } else {
        ui_print_info_message(['no_close' => true, 'message' => __('This table shows in columns the modules group and in rows agents group. The cell shows all modules') ]);
        ui_print_info_message(['no_close' => true, 'message' => __('There are no defined groups or module groups') ]);
    }

    ui_require_css_file('cluetip', 'include/styles/js/');
    ui_require_jquery_file('cluetip');
    ?>
    <script>
        $(document).ready (function () {
            $("a.info_cell").cluetip ({
                arrows: true,
                attribute: 'rel',
                cluetipClass: 'default',
                width: '200px'
            });
        });
    </script>
    <?php
}


extensions_add_operation_menu_option(__('Module groups'), 'estado', 'module_groups/brick.png', 'v1r1', 'view');
extensions_add_main_function('mainModuleGroups');
