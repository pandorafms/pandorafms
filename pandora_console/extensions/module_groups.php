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
global $config;

check_login();

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
    $counter = count($info);
    $offset = get_parameter('offset', 0);
    $agent_group_search = get_parameter('agent_group_search', '');
    $module_group_search = get_parameter('module_group_search', '');

    $info = array_filter(
        $info,
        function ($v, $k) use ($agent_group_search) {
            return preg_match(
                '/'.$agent_group_search.'/i',
                $v['name']
            );
        },
        ARRAY_FILTER_USE_BOTH
    );

    if (!empty($info)) {
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
        function ($v, $k) use ($module_group_search) {
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

    ui_print_page_header(
        __('Combined table of agent group and module group'),
        'images/module_group.png',
        false,
        'module_groups_view',
        false,
        ''
    );

    echo "<table cellpadding='4' cellspacing='4' class='databox filters' width='100%' style='font-weight: bold; margin-bottom: 10px;'>
		<tr>";
    echo "<form method='post'
		action='index.php?sec=view&sec2=extensions/module_groups'>";

    echo '<td>';
    echo __('Search by agent group').'&nbsp;';
    html_print_input_text('agent_group_search', $agent_group_search);

    echo '</td><td>';
    echo __('Search by module group').'&nbsp;';
    html_print_input_text('module_group_search', $module_group_search);

    echo '</td><td>';
    echo "<input name='srcbutton' type='submit' class='sub search' value='".__('Search')."'>";
    echo '</form>';
    echo '<td>';
    echo '</tr></table>';

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

    if (true) {
        $table = new StdClass();
        $table->style[0] = 'color: #ffffff; background-color: #373737; font-weight: bolder; min-width: 230px;';
        $table->width = '100%';

        if ($config['style'] === 'pandora_black') {
            $background_color = '#333';
        } else {
            $background_color = '#fff';
        }

        $head[0] = __('Groups');
        $headstyle[0] = 'width: 20%;  font-weight: bolder;';
        foreach ($array_module_group as $key => $value) {
            $headstyle[] = 'min-width: 60px;max-width: 5%;text-align:center; color: #ffffff; background-color: #373737; font-weight: bolder;';
            $head[] = ui_print_truncate_text($value, GENERIC_SIZE_TEXT, true, true, true, '&hellip;', 'color:#FFF');
        }

        $i = 0;
        foreach ($array_for_defect as $key => $value) {
            $deep = groups_get_group_deep($key);
            $data[$i][0] = $deep.ui_print_truncate_text($value['data']['name'], GENERIC_SIZE_TEXT, true, true, true, '&hellip;', 'color:#FFF');
            $j = 1;
            if (isset($array_data[$key])) {
                foreach ($value['gm'] as $k => $v) {
                    if (isset($array_data[$key][$k])) {
                        $send_tooltip = json_encode($array_data[$key][$k]);
                        $rel = 'ajax.php?page=extensions/module_groups&get_info_alert_module_group=1&send_tooltip='.$send_tooltip;
                        $url = 'index.php?sec=estado&sec2=operation/agentes/status_monitor&status=-1&ag_group='.$key.'&modulegroup='.$k;

                        if ($array_data[$key][$k]['alerts_module_count'] != 0) {
                            $color = '#FFA631';
                            // Orange when the cell for this model group and agent has at least one alert fired.
                        } else if ($array_data[$key][$k]['critical_module_count'] != 0) {
                            $color = '#e63c52';
                            // Red when the cell for this model group and agent has at least one module in critical state and the rest in any state.
                        } else if ($array_data[$key][$k]['warning_module_count'] != 0) {
                            $color = '#f3b200';
                            // Yellow when the cell for this model group and agent has at least one in warning state and the rest in green state.
                        } else if ($array_data[$key][$k]['unknown_module_count'] != 0) {
                            $color = '#B2B2B2 ';
                            // Grey when the cell for this model group and agent has at least one module in unknown state and the rest in any state.
                        } else if ($array_data[$key][$k]['normal_module_count'] != 0) {
                            $color = '#82b92e';
                            // Green when the cell for this model group and agent has OK state all modules.
                        } else if ($array_data[$key][$k]['notInit_module_count'] != 0) {
                            $color = '#5BB6E5';
                            // Blue when the cell for this module group and all modules have not init value.
                        }

                        $data[$i][$j] = "<div style='".$cell_style.'background:'.$color.";'>";
                        $data[$i][$j] .= "<a class='info_cell' rel='$rel' href='$url' style='color:white;font-size: 18px;'>";
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
                    $data[$i][$j] = "<div style='background:".$background_color."; min-width: 60px;max-width:5%;overflow:hidden; margin-left: auto; margin-right: auto; text-align: center; padding: 5px;padding-bottom:10px;font-size: 18px;line-height:25px;'>";
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

        ui_pagination($counter);

        echo "<div style='width:100%; overflow-x:auto;'>";
            html_print_table($table);
        echo '</div>';

        ui_pagination($counter);

        echo "<div class='legend_basic' style='width: 98.6%'>";
            echo '<table >';
                echo "<tr><td colspan='2' style='padding-bottom: 10px;'><b>".__('Legend').'</b></td></tr>';
                echo "<tr><td class='legend_square_simple'><div style='background-color: ".COL_ALERTFIRED.";'></div></td><td>".__('Orange cell when the module group and agent have at least one alarm fired.').'</td></tr>';
                echo "<tr><td class='legend_square_simple'><div style='background-color: ".COL_CRITICAL.";'></div></td><td>".__('Red cell when the module group and agent have at least one module in critical status and the others in any status').'</td></tr>';
                echo "<tr><td class='legend_square_simple'><div style='background-color: ".COL_WARNING.";'></div></td><td>".__('Yellow cell when the module group and agent have at least one in warning status and the others in grey or green status').'</td></tr>';
                echo "<tr><td class='legend_square_simple'><div style='background-color: ".COL_UNKNOWN.";'></div></td><td>".__('Grey cell when the module group and agent have at least one in unknown status and the others in green status').'</td></tr>';
                echo "<tr><td class='legend_square_simple'><div style='background-color: ".COL_NORMAL.";'></div></td><td>".__('Green cell when the module group and agent have all modules in OK status').'</td></tr>';
                echo "<tr><td class='legend_square_simple'><div style='background-color: ".COL_MAINTENANCE.";'></div></td><td>".__('Blue cell when the module group and agent have all modules in not init status.').'</td></tr>';
            echo '</table>';
        echo '</div>';
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
